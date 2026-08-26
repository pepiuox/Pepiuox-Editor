<?php
namespace App\Services;

use App\Core\Database;
use App\Core\Auth;
use App\Core\Security;
use PDO;
use Exception;
use Throwable;

/**
 * Servicio de autenticación API
 * 
 * @package App\Services
 */
class ApiAuthService
{
    private Database $db;
    private SecurityService $security;
    private ActivityLoggerService $logger;

    public function __construct(
        Database $db,
        SecurityService $security,
        ActivityLoggerService $logger
    ) {
        $this->db = $db;
        $this->security = $security;
        $this->logger = $logger;
    }

    /**
     * Registra una nueva aplicación
     */
    public function registerApplication(array $data): array
    {
        try {
            // Validar datos
            $validated = $this->security->validateAndSanitize($data, [
                'app_name' => ['required' => true, 'type' => 'string', 'min' => 3, 'max' => 255],
                'description' => ['required' => false, 'type' => 'string'],
                'allowed_ips' => ['required' => false, 'type' => 'array'],
                'rate_limit' => ['required' => false, 'type' => 'int'],
                'permissions' => ['required' => false, 'type' => 'array']
            ]);

            // Generar credenciales
            $appKey = bin2hex(random_bytes(16));
            $appSecret = $this->security->encrypt(bin2hex(random_bytes(32)));
            $apiKey = 'pk_' . bin2hex(random_bytes(24));
            $apiSecret = $this->security->encrypt('sk_' . bin2hex(random_bytes(32)));

            // Insertar aplicación
            $appId = $this->db->insert(
                "INSERT INTO api_applications (
                    app_name, app_key, app_secret, api_key, api_secret,
                    description, allowed_ips, rate_limit, permissions, created_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $validated['app_name'],
                    $appKey,
                    $appSecret,
                    $apiKey,
                    $apiSecret,
                    $validated['description'] ?? null,
                    json_encode($validated['allowed_ips'] ?? []),
                    $validated['rate_limit'] ?? 100,
                    json_encode($validated['permissions'] ?? ['read']),
                    Auth::id()
                ]
            );

            // Registrar log
            $this->logger->logCurrentUser(
                'api_application_created',
                "Aplicación API creada: {$validated['app_name']} (ID: {$appId})"
            );

            return [
                'success' => true,
                'application_id' => $appId,
                'app_key' => $appKey,
                'app_secret' => $this->security->decrypt($appSecret),
                'api_key' => $apiKey,
                'api_secret' => $this->security->decrypt($apiSecret),
                'message' => 'Aplicación registrada exitosamente'
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Valida API Key
     */
    public function validateApiKey(string $apiKey, ?string $apiSecret = null): array
    {
        try {
            $app = $this->db->fetch(
                "SELECT * FROM api_applications
                 WHERE api_key = ? AND status = 'active'
                 AND (expires_at IS NULL OR expires_at > NOW())",
                [$apiKey]
            );

            if (!$app) {
                throw new Exception('API Key inválida o expirada', 401);
            }

            // Verificar API secret si se proporciona
            if ($apiSecret !== null) {
                $decryptedSecret = $this->security->decrypt($app['api_secret']);
                if (!hash_equals($decryptedSecret, $apiSecret)) {
                    throw new Exception('API Secret inválido', 401);
                }
            }

            // Verificar IP permitida
            $allowedIps = json_decode($app['allowed_ips'], true);
            if (!empty($allowedIps)) {
                $clientIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
                if (!in_array($clientIp, $allowedIps)) {
                    throw new Exception('IP no autorizada', 403);
                }
            }

            // Actualizar último uso
            $this->db->execute(
                "UPDATE api_applications SET last_used_at = NOW() WHERE id = ?",
                [$app['id']]
            );

            return [
                'success' => true,
                'application' => $app
            ];
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode() ?: 401
            ];
        }
    }

    /**
     * Genera token de acceso
     */
    public function generateToken(
        string $apiKey,
        string $apiSecret,
        ?array $scopes = null,
        int $expiresIn = 3600
    ): array {
        try {
            // Validar credenciales
            $validation = $this->validateApiKey($apiKey, $apiSecret);
            if (!$validation['success']) {
                return $validation;
            }

            $app = $validation['application'];

            // Verificar permisos
            $allowedScopes = json_decode($app['permissions'], true);
            if ($scopes !== null && !empty(array_diff($scopes, $allowedScopes))) {
                throw new Exception('Scopes no permitidos para esta aplicación', 403);
            }

            // Generar tokens
            $token = bin2hex(random_bytes(32));
            $refreshToken = bin2hex(random_bytes(32));

            // Guardar token
            $expiresAt = date('Y-m-d H:i:s', time() + $expiresIn);
            $refreshExpiresAt = date('Y-m-d H:i:s', time() + ($expiresIn * 2));

            $tokenId = $this->db->insert(
                "INSERT INTO api_tokens (
                    application_id, token, refresh_token, scopes,
                    expires_at, refresh_expires_at, ip_address, user_agent
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [
                    $app['id'],
                    $this->security->encrypt($token),
                    $this->security->encrypt($refreshToken),
                    json_encode($scopes ?? $allowedScopes),
                    $expiresAt,
                    $refreshExpiresAt,
                    $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                    $_SERVER['HTTP_USER_AGENT'] ?? null
                ]
            );

            return [
                'success' => true,
                'access_token' => $token,
                'refresh_token' => $refreshToken,
                'token_type' => 'Bearer',
                'expires_in' => $expiresIn,
                'expires_at' => $expiresAt,
                'scopes' => $scopes ?? $allowedScopes,
                'token_id' => $tokenId
            ];
        } catch (Throwable $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Valida token de acceso
     */
    public function validateToken(string $token): array
    {
        try {
            $stmt = $this->db->getConnection()->prepare(
                "SELECT t.*, a.app_name, a.permissions as app_permissions, a.rate_limit
                 FROM api_tokens t
                 JOIN api_applications a ON t.application_id = a.id
                 WHERE t.revoked = 0
                 AND t.expires_at > NOW()"
            );
            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $decryptedToken = $this->security->decrypt($row['token']);
                if (hash_equals($decryptedToken, $token)) {
                    // Verificar rate limit
                    $this->checkTokenRateLimit($row['id'], $row['rate_limit']);

                    return [
                        'success' => true,
                        'token_data' => $row
                    ];
                }
            }

            throw new Exception('Token inválido o expirado', 401);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode() ?: 401
            ];
        }
    }

    /**
     * Refresca token expirado
     */
    public function refreshToken(string $refreshToken): array
    {
        try {
            $stmt = $this->db->getConnection()->prepare(
                "SELECT * FROM api_tokens
                 WHERE refresh_expires_at > NOW() AND revoked = 0"
            );
            $stmt->execute();

            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $decryptedRefresh = $this->security->decrypt($row['refresh_token']);
                if (hash_equals($decryptedRefresh, $refreshToken)) {
                    // Revocar token actual
                    $this->db->execute(
                        "UPDATE api_tokens SET revoked = 1 WHERE id = ?",
                        [$row['id']]
                    );

                    // Obtener la aplicación
                    $app = $this->db->fetch(
                        "SELECT * FROM api_applications WHERE id = ?",
                        [$row['application_id']]
                    );

                    if (!$app) {
                        throw new Exception('Aplicación no encontrada', 404);
                    }

                    // Generar nuevo token
                    return $this->generateToken(
                        $app['api_key'],
                        $this->security->decrypt($app['api_secret']),
                        json_decode($row['scopes'], true),
                        3600
                    );
                }
            }

            throw new Exception('Refresh token inválido o expirado', 401);
        } catch (Throwable $e) {
            return [
                'success' => false,
                'error' => $e->getMessage(),
                'code' => $e->getCode() ?: 401
            ];
        }
    }

    /**
     * Verifica rate limit por token
     */
    private function checkTokenRateLimit(int $tokenId, int $limit): void
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count
             FROM api_access_logs
             WHERE token_id = ?
             AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)",
            [$tokenId]
        );

        if (($result['count'] ?? 0) >= $limit) {
            throw new Exception("Rate limit excedido para este token", 429);
        }
    }

    /**
     * Revoca token
     */
    public function revokeToken(string $token): bool
    {
        return $this->db->execute(
            "UPDATE api_tokens SET revoked = 1 WHERE token = ?",
            [$this->security->encrypt($token)]
        );
    }

    /**
     * Registra acceso a la API
     */
    public function logAccess(
        int $applicationId,
        int $tokenId,
        string $endpoint,
        string $method,
        int $responseCode,
        float $executionTime,
        ?array $requestData = null
    ): bool {
        return $this->db->execute(
            "INSERT INTO api_access_logs (
                application_id, token_id, endpoint, method,
                ip_address, user_agent, request_data, response_code, execution_time
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)",
            [
                $applicationId,
                $tokenId,
                $endpoint,
                $method,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                $_SERVER['HTTP_USER_AGENT'] ?? null,
                $requestData ? json_encode($requestData) : null,
                $responseCode,
                $executionTime
            ]
        );
    }

    /**
     * Obtiene aplicaciones API de un usuario
     */
    public function getUserApplications(int $userId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM api_applications WHERE created_by = ? ORDER BY created_at DESC",
            [$userId]
        );
    }

    /**
     * Obtiene tokens de una aplicación
     */
    public function getApplicationTokens(int $applicationId): array
    {
        return $this->db->fetchAll(
            "SELECT * FROM api_tokens 
             WHERE application_id = ? 
             ORDER BY created_at DESC",
            [$applicationId]
        );
    }

    /**
     * Revoca todos los tokens de una aplicación
     */
    public function revokeAllTokens(int $applicationId): bool
    {
        return $this->db->execute(
            "UPDATE api_tokens SET revoked = 1 WHERE application_id = ?",
            [$applicationId]
        );
    }
}