<?php
namespace App\Controllers;
use App\Core\{Database, Auth};
class Security {
    private $pdo;
    private $encryption_key;
    private $cipher_method = 'aes-256-gcm';

    public function __construct($pdo, $encryption_key = null) {
        $this->pdo = $pdo;
        $this->encryption_key = $encryption_key ?? $this->generateSecureKey();
    }

    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL);
    }

    /**
     * Genera una clave segura para encriptación
     */
    public function generateSecureKey($length = 32) {
        return bin2hex(random_bytes($length));
    }

    /**
     * Encripta datos con AES-256-GCM (autenticación incluida)
     */
    public function encrypt($data, $key = null) {
        $key = $key ?? $this->encryption_key;
        $iv = random_bytes(12); // GCM recomienda 12 bytes
        $tag = null;

        $encrypted = openssl_encrypt(
            $data,
            $this->cipher_method,
            hex2bin($key),
                                     OPENSSL_RAW_DATA,
                                     $iv,
                                     $tag
        );

        if ($encrypted === false) {
            throw new Exception('Error de encriptación');
        }

        // Retorna IV + Tag + Datos encriptados (todos en base64)
        return base64_encode($iv . $tag . $encrypted);
    }

    /**
     * Desencripta datos verificando integridad
     */
    public function decrypt($encrypted_data, $key = null) {
        $key = $key ?? $this->encryption_key;
        $decoded = base64_decode($encrypted_data);

        // Extraer IV (12 bytes), Tag (16 bytes) y datos encriptados
        $iv = substr($decoded, 0, 12);
        $tag = substr($decoded, 12, 16);
        $ciphertext = substr($decoded, 28);

        $decrypted = openssl_decrypt(
            $ciphertext,
            $this->cipher_method,
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
    public function hash($data, $salt = null) {
        $salt = $salt ?? random_bytes(32);
        $hash = hash_hmac('sha256', $data . $salt, $this->encryption_key, true);
        return base64_encode($salt . $hash);
    }

    /**
     * Verifica hash
     */
    public function verifyHash($data, $stored_hash) {
        $decoded = base64_decode($stored_hash);
        $salt = substr($decoded, 0, 32);
        $hash = substr($decoded, 32);

        $computed = hash_hmac('sha256', $data . $salt, $this->encryption_key, true);
        return hash_equals($hash, $computed);
    }

    /**
     * Genera firma digital para contenido
     */
    public function generateSignature($data) {
        $timestamp = time();
        $nonce = random_bytes(16);
        $signature_data = json_encode([
            'data' => $data,
            'timestamp' => $timestamp,
            'nonce' => base64_encode($nonce)
        ]);

        return hash_hmac('sha512', $signature_data, $this->encryption_key);
    }

    /**
     * Verifica firma digital
     */
    public function verifySignature($data, $signature) {
        $expected = $this->generateSignature($data);
        return hash_equals($expected, $signature);
    }

    /**
     * Protege contra SQL Injection (además de prepared statements)
     */
    public function sanitizeInput($input) {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }

        // Eliminar caracteres peligrosos
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Prevenir XSS en atributos
        $input = preg_replace('/\bon\w+\s*=/i', 'data-${0}', $input);

        return $input;
    }

    /**
     * Valida y limpia datos según tipo esperado
     */
    public function validateAndSanitize($data, $rules) {
        $sanitized = [];
        $errors = [];

        foreach ($rules as $field => $rule) {
            $value = $data[$field] ?? null;
            $type = $rule['type'] ?? 'string';
            $required = $rule['required'] ?? false;
            $min = $rule['min'] ?? null;
            $max = $rule['max'] ?? null;
            $pattern = $rule['pattern'] ?? null;

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

                default:
                    $sanitized[$field] = $this->sanitizeInput($value);
            }

            // Validar longitud
            if ($min !== null && strlen($sanitized[$field]) < $min) {
                $errors[$field] = "El campo {$field} debe tener al menos {$min} caracteres";
            }

            if ($max !== null && strlen($sanitized[$field]) > $max) {
                $errors[$field] = "El campo {$field} no debe exceder {$max} caracteres";
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
    public function generateCsrfToken() {
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    /**
     * Verifica CSRF token
     */
    public function verifyCsrfToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Rate limiting por IP
     */
    public function checkRateLimit($ip, $endpoint, $limit = 100, $timeWindow = 3600) {
        $stmt = $this->pdo->prepare("
        SELECT COUNT(*) as count
        FROM api_access_logs
        WHERE ip_address = ?
        AND endpoint = ?
        AND created_at > DATE_SUB(NOW(), INTERVAL ? SECOND)
        ");
        $stmt->execute([$ip, $endpoint, $timeWindow]);
        $result = $stmt->fetch();

        if ($result['count'] >= $limit) {
            throw new Exception("Rate limit excedido. Límite: {$limit} peticiones cada {$timeWindow} segundos", 429);
        }

        return true;
    }
}
?>
