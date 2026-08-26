<?php
namespace App\Core;

use App\Core\Database;
use Exception;
use Throwable;

/**
 * Clase de seguridad centralizada
 * 
 * @package App\Core
 */
class Security
{
    private Database $db;
    private string $encryptionKey;
    private string $cipherMethod = 'aes-256-gcm';

    public function __construct(Database $db, ?string $encryptionKey = null)
    {
        $this->db = $db;
        $this->encryptionKey = $encryptionKey ?? $this->generateSecureKey();
    }

    /**
     * Valida un email
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Genera una clave segura para encriptación
     */
    public function generateSecureKey(int $length = 32): string
    {
        return bin2hex(random_bytes($length));
    }

    /**
     * Encripta datos con AES-256-GCM
     */
    public function encrypt(string $data, ?string $key = null): string
    {
        $key = $key ?? $this->encryptionKey;
        $iv = random_bytes(12);
        $tag = null;

        $encrypted = openssl_encrypt(
            $data,
            $this->cipherMethod,
            hex2bin($key),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($encrypted === false) {
            throw new Exception('Error de encriptación');
        }

        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Desencripta datos
     */
    public function decrypt(string $encryptedData, ?string $key = null): string
    {
        $key = $key ?? $this->encryptionKey;
        $decoded = base64_decode($encryptedData);

        // Extraer IV (12 bytes), Tag (16 bytes) y datos encriptados
        $iv = substr($decoded, 0, 12);
        $tag = substr($decoded, 12, 16);
        $ciphertext = substr($decoded, 28);

        $decrypted = openssl_decrypt(
            $ciphertext,
            $this->cipherMethod,
            hex2bin($key),
            OPENSSL_RAW_DATA,
            $iv,
            $tag
        );

        if ($decrypted === false) {
            throw new Exception('Error de desencriptación - datos corruptos');
        }

        return $decrypted;
    }

    /**
     * Genera hash seguro con salt
     */
    public function hash(string $data, ?string $salt = null): string
    {
        $salt = $salt ?? random_bytes(32);
        $hash = hash_hmac('sha256', $data . $salt, $this->encryptionKey, true);
        return base64_encode($salt . $hash);
    }

    /**
     * Verifica hash
     */
    public function verifyHash(string $data, string $storedHash): bool
    {
        $decoded = base64_decode($storedHash);
        if (strlen($decoded) < 32) {
            return false;
        }
        $salt = substr($decoded, 0, 32);
        $hash = substr($decoded, 32);

        $computed = hash_hmac('sha256', $data . $salt, $this->encryptionKey, true);
        return hash_equals($hash, $computed);
    }

    /**
     * Genera firma digital
     */
    public function generateSignature(string $data): string
    {
        $timestamp = time();
        $nonce = random_bytes(16);
        $signatureData = json_encode([
            'data' => $data,
            'timestamp' => $timestamp,
            'nonce' => base64_encode($nonce)
        ]);

        return hash_hmac('sha512', $signatureData, $this->encryptionKey);
    }

    /**
     * Verifica firma digital
     */
    public function verifySignature(string $data, string $signature): bool
    {
        $expected = $this->generateSignature($data);
        return hash_equals($expected, $signature);
    }

    /**
     * Sanitiza entrada
     */
    public function sanitizeInput($input)
    {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }

        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $input = preg_replace('/\bon\w+\s*=/i', 'data-${0}', $input);

        return $input;
    }

    /**
     * Valida y limpia datos según reglas
     */
    public function validateAndSanitize(array $data, array $rules): array
    {
        $sanitized = [];
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $type = $rule['type'] ?? 'string';
            $required = $rule['required'] ?? false;
            $min = $rule['min'] ?? null;
            $max = $rule['max'] ?? null;
            $pattern = $rule['pattern'] ?? null;
            $in = $rule['in'] ?? null;

            if ($required && ($value === null || $value === '')) {
                $errors[$field] = "El campo {$field} es requerido";
                continue;
            }

            if ($value === null || $value === '') {
                $sanitized[$field] = null;
                continue;
            }

            // Sanitizar según tipo
            switch ($type) {
                case 'int':
                    $sanitized[$field] = filter_var($value, FILTER_VALIDATE_INT);
                    if ($sanitized[$field] === false) {
                        $errors[$field] = "El campo {$field} debe ser un número entero";
                    }
                    break;

                case 'float':
                case 'numeric':
                    $sanitized[$field] = filter_var($value, FILTER_VALIDATE_FLOAT);
                    if ($sanitized[$field] === false) {
                        $errors[$field] = "El campo {$field} debe ser un número";
                    }
                    break;

                case 'email':
                    $sanitized[$field] = filter_var($value, FILTER_VALIDATE_EMAIL);
                    if ($sanitized[$field] === false) {
                        $errors[$field] = "El campo {$field} debe ser un email válido";
                    }
                    break;

                case 'url':
                    $sanitized[$field] = filter_var($value, FILTER_VALIDATE_URL);
                    if ($sanitized[$field] === false) {
                        $errors[$field] = "El campo {$field} debe ser una URL válida";
                    }
                    break;

                case 'boolean':
                    $sanitized[$field] = filter_var($value, FILTER_VALIDATE_BOOLEAN);
                    break;

                case 'array':
                    $sanitized[$field] = is_array($value) ? $value : [];
                    break;

                default:
                    $sanitized[$field] = $this->sanitizeInput($value);
            }

            // Validar si está en lista de valores permitidos
            if ($in !== null && !in_array($sanitized[$field], $in)) {
                $errors[$field] = "El campo {$field} tiene un valor no permitido";
            }

            // Validar longitud (solo para strings)
            if (is_string($sanitized[$field])) {
                if ($min !== null && strlen($sanitized[$field]) < $min) {
                    $errors[$field] = "El campo {$field} debe tener al menos {$min} caracteres";
                }

                if ($max !== null && strlen($sanitized[$field]) > $max) {
                    $errors[$field] = "El campo {$field} no debe exceder {$max} caracteres";
                }
            }

            // Validar patrón regex
            if ($pattern !== null && !preg_match($pattern, $sanitized[$field])) {
                $errors[$field] = "El campo {$field} tiene un formato inválido";
            }
        }

        if (!empty($errors)) {
            throw new Exception(json_encode($errors));
        }

        return $sanitized;
    }

    /**
     * Genera CSRF token
     */
    public function generateCsrfToken(): string
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    /**
     * Verifica CSRF token
     */
    public function verifyCsrfToken(?string $token): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token ?? '');
    }

    /**
     * Rate limiting por IP
     */
    public function checkRateLimit(string $ip, string $endpoint, int $limit = 100, int $timeWindow = 3600): bool
    {
        $result = $this->db->fetch(
            "SELECT COUNT(*) as count
             FROM api_access_logs
             WHERE ip_address = ?
             AND endpoint = ?
             AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)",
            [$ip, $endpoint, $timeWindow]
        );

        if (($result['count'] ?? 0) >= $limit) {
            throw new Exception(
                "Rate limit excedido. Límite: {$limit} peticiones cada {$timeWindow} segundos",
                429
            );
        }

        return true;
    }

    /**
     * Genera UUID v4
     */
    public function generateUuid(): string
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }

    /**
     * Escapa HTML de forma segura
     */
    public function escapeHtml(string $str): string
    {
        return htmlspecialchars($str, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    /**
     * Escapa JSON de forma segura
     */
    public function escapeJson(array $data): string
    {
        return json_encode($data, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
    }
}