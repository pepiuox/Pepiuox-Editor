<?php
namespace App\Controllers;

/**
 * Secure Encryption Class
 *
 * Características de seguridad:
 * - AES-256-GCM con autenticación (vs CBC sin autenticación)
 * - Derivación de clave PBKDF2 (vs hash SHA256 simple)
 * - IV aleatorio por operación (vs IV estático)
 * - Protección contra timing attacks
 * - Validación de integridad de datos
 * - Soporte para rotación de claves
 *
 * @author Lab eMotion / PePiuoX
 * @version 2.0
 */

class SecureEncryption {

    // Constantes de seguridad (cambiadas a public para acceso externo)
    public const ENCRYPT_METHOD = 'aes-256-gcm';
    public const HASH_ALGO = 'sha512';
    public const PBKDF2_ITERATIONS = 100000;
    public const IV_LENGTH = 12;
    public const TAG_LENGTH = 16;
    public const SALT_LENGTH = 32;

    private string $encryption_key;
    private array $key_rotation = [];
    private bool $use_legacy_mode = false;

    /**
     * Constructor - Inicializa con clave maestra
     *
     * @param string|null $master_key Clave maestra (opcional)
     * @param array $legacy_keys Claves para rotación (formato: ['key_id' => 'key_value'])
     */
    public function __construct(?string $master_key = null, array $legacy_keys = []) {
        if ($master_key === null) {
            // Generar clave segura si no se proporciona
            $this->encryption_key = $this->generateSecureKey(32);
        } else {
            $this->encryption_key = $master_key;
        }

        $this->key_rotation = $legacy_keys;

        // Verificar que OpenSSL soporte GCM
        if (!in_array('aes-256-gcm', openssl_get_cipher_methods())) {
            $this->use_legacy_mode = true;
            trigger_error('AES-256-GCM no está disponible, usando modo compatible', E_USER_WARNING);
        }
    }

    /**
     * Encripta datos con autenticación integrada
     *
     * @param string $data Datos a encriptar
     * @param string|null $key Clave específica (opcional)
     * @param array $additional_data Datos adicionales para autenticar (opcional)
     * @return string Datos encriptados en formato base64
     * @throws Exception Error en encriptación
     */
    public function encrypt(string $data, ?string $key = null, array $additional_data = []): string {
        $key = $key ?? $this->encryption_key;

        try {
            // Generar IV aleatorio
            $iv = random_bytes(self::IV_LENGTH);

            // Derivar clave segura
            $derived_key = $this->deriveKey($key, $iv);

            // Preparar datos adicionales para autenticar
            $aad = $this->prepareAdditionalData($additional_data);

            if (!$this->use_legacy_mode && function_exists('openssl_encrypt')) {
                // Modo moderno con GCM
                $tag = '';
                $encrypted = openssl_encrypt(
                    $data,
                    self::ENCRYPT_METHOD,
                    $derived_key,
                    OPENSSL_RAW_DATA,
                    $iv,
                    $tag,
                    $aad,
                    self::TAG_LENGTH
                );

                if ($encrypted === false) {
                    throw new Exception('Error en encriptación OpenSSL');
                }

                // Formato: IV + Tag + Datos Encriptados
                $result = $iv . $tag . $encrypted;

            } else {
                // Modo legacy (CBC con HMAC)
                $result = $this->encryptLegacy($data, $derived_key, $iv, $aad);
            }

            // Codificar en base64 con información de versión
            $version = $this->use_legacy_mode ? '01' : '02';
            return $version . base64_encode($result);

        } catch (Exception $e) {
            throw new Exception('Error en encriptación: ' . $e->getMessage());
        }
    }

    /**
     * Desencripta datos verificando autenticación e integridad
     *
     * @param string $encrypted_data Datos encriptados
     * @param string|null $key Clave específica (opcional)
     * @param array $additional_data Datos adicionales para verificar
     * @return string Datos desencriptados
     * @throws Exception Error en desencriptación o datos corruptos
     */
    public function decrypt(string $encrypted_data, ?string $key = null, array $additional_data = []): string {
        $key = $key ?? $this->encryption_key;

        try {
            // Extraer versión
            $version = substr($encrypted_data, 0, 2);
            $encrypted_data = base64_decode(substr($encrypted_data, 2));

            if ($encrypted_data === false) {
                throw new Exception('Datos encriptados inválidos');
            }

            if ($version === '02' && !$this->use_legacy_mode) {
                // Modo moderno GCM
                $iv = substr($encrypted_data, 0, self::IV_LENGTH);
                $tag = substr($encrypted_data, self::IV_LENGTH, self::TAG_LENGTH);
                $ciphertext = substr($encrypted_data, self::IV_LENGTH + self::TAG_LENGTH);

                // Derivar clave
                $derived_key = $this->deriveKey($key, $iv);

                // Preparar datos adicionales
                $aad = $this->prepareAdditionalData($additional_data);

                // Desencriptar
                $decrypted = openssl_decrypt(
                    $ciphertext,
                    self::ENCRYPT_METHOD,
                    $derived_key,
                    OPENSSL_RAW_DATA,
                    $iv,
                    $tag,
                    $aad
                );

                if ($decrypted === false) {
                    throw new Exception('Error en desencriptación - datos corruptos o manipulados');
                }

                return $decrypted;

            } else {
                // Modo legacy
                return $this->decryptLegacy($encrypted_data, $key);
            }

        } catch (Exception $e) {
            throw new Exception('Error en desencriptación: ' . $e->getMessage());
        }
    }

    /**
     * Encriptación legacy con CBC + HMAC (compatibilidad)
     */
    private function encryptLegacy(string $data, string $key, string $iv, string $aad): string {
        $encrypted = openssl_encrypt(
            $data,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($encrypted === false) {
            throw new Exception('Error en encriptación legacy');
        }

        // Crear HMAC para autenticación
        $hmac = hash_hmac('sha256', $iv . $encrypted . $aad, $key, true);

        return $iv . $hmac . $encrypted;
    }

    /**
     * Desencriptación legacy con verificación HMAC
     */
    private function decryptLegacy(string $encrypted_data, string $key): string {
        $iv = substr($encrypted_data, 0, 16);
        $hmac = substr($encrypted_data, 16, 32);
        $ciphertext = substr($encrypted_data, 48);

        // Verificar HMAC
        $expected_hmac = hash_hmac('sha256', $iv . $ciphertext, $key, true);
        if (!hash_equals($hmac, $expected_hmac)) {
            throw new Exception('Integridad de datos comprometida');
        }

        $decrypted = openssl_decrypt(
            $ciphertext,
            'aes-256-cbc',
            $key,
            OPENSSL_RAW_DATA,
            $iv
        );

        if ($decrypted === false) {
            throw new Exception('Error en desencriptación legacy');
        }

        return $decrypted;
    }

    /**
     * Deriva clave segura usando PBKDF2
     */
    private function deriveKey(string $key, string $salt): string {
        // Usar salt de la derivación
        $derivation_salt = substr(hash('sha256', $salt, true), 0, 32);

        return hash_pbkdf2(
            'sha256',
            $key,
            $derivation_salt,
            self::PBKDF2_ITERATIONS,
            32,
            true
        );
    }

    /**
     * Prepara datos adicionales para autenticación
     */
    private function prepareAdditionalData(array $data): string {
        if (empty($data)) {
            return '';
        }

        ksort($data); // Ordenar para consistencia
        return hash('sha256', json_encode($data), true);
    }

    /**
     * Genera clave segura criptográficamente
     */
    public function generateSecureKey(int $bytes = 32): string {
        return bin2hex(random_bytes($bytes));
    }

    /**
     * Genera token seguro (para sesiones, APIs, etc.)
     */
    public function generateSecureToken(int $bytes = 64): string {
        $token = random_bytes($bytes);
        return rtrim(strtr(base64_encode($token), '+/', '-_'), '=');
    }

    /**
     * Genera hash seguro con salt aleatorio
     */
    public function secureHash(string $data, ?string $salt = null): array {
        $salt = $salt ?? random_bytes(32);
        $iterations = self::PBKDF2_ITERATIONS;

        $hash = hash_pbkdf2(
            self::HASH_ALGO,
            $data,
            $salt,
            $iterations,
            64,
            true
        );

        return [
            'hash' => base64_encode($hash),
            'salt' => base64_encode($salt),
            'iterations' => $iterations,
            'algo' => self::HASH_ALGO
        ];
    }

    /**
     * Verifica hash generado con secureHash
     */
    public function verifySecureHash(string $data, array $stored_hash): bool {
        $salt = base64_decode($stored_hash['salt']);
        $iterations = $stored_hash['iterations'] ?? self::PBKDF2_ITERATIONS;
        $algo = $stored_hash['algo'] ?? self::HASH_ALGO;

        $hash = hash_pbkdf2(
            $algo,
            $data,
            $salt,
            $iterations,
            64,
            true
        );

        $computed = base64_encode($hash);
        return hash_equals($stored_hash['hash'], $computed);
    }

    /**
     * Genera firma digital para datos
     */
    public function signData(string $data, ?string $key = null): string {
        $key = $key ?? $this->encryption_key;
        $timestamp = time();
        $nonce = random_bytes(16);

        $signature_data = json_encode([
            'data' => $data,
            'timestamp' => $timestamp,
            'nonce' => base64_encode($nonce)
        ]);

        return hash_hmac('sha512', $signature_data, $key);
    }

    /**
     * Verifica firma digital
     */
    public function verifySignature(string $data, string $signature, ?string $key = null): bool {
        $key = $key ?? $this->encryption_key;
        $expected = $this->signData($data, $key);
        return hash_equals($expected, $signature);
    }

    /**
     * Encripta datos para almacenamiento en base de datos
     * Incluye metadatos para facilitar rotación de claves
     */
    public function encryptForDatabase(string $data, string $key_id = 'primary'): string {
        $metadata = [
            'version' => '2.0',
            'key_id' => $key_id,
            'timestamp' => time(),
            'algorithm' => self::ENCRYPT_METHOD
        ];

        $encrypted = $this->encrypt($data);
        $metadata_encoded = base64_encode(json_encode($metadata));

        return $metadata_encoded . ':' . $encrypted;
    }

    /**
     * Desencripta datos de base de datos con rotación automática
     */
    public function decryptFromDatabase(string $encrypted_data): string {
        $parts = explode(':', $encrypted_data, 2);

        if (count($parts) !== 2) {
            // Formato antiguo
            return $this->decrypt($encrypted_data);
        }

        $metadata = json_decode(base64_decode($parts[0]), true);
        $data = $parts[1];

        // Verificar si necesita rotación de clave
        $key = $this->getKeyForId($metadata['key_id'] ?? 'primary');

        return $this->decrypt($data, $key);
    }

    /**
     * Obtiene clave por ID para rotación
     */
    private function getKeyForId(string $key_id): string {
        if ($key_id === 'primary') {
            return $this->encryption_key;
        }

        if (isset($this->key_rotation[$key_id])) {
            return $this->key_rotation[$key_id];
        }

        throw new Exception("Key ID '{$key_id}' no encontrada");
    }

    /**
     * Sanitiza input (mejora del método procheck original)
     */
    public function sanitizeInput($input, $preserve_html = false): string {
        if (is_array($input)) {
            return array_map([$this, 'sanitizeInput'], $input);
        }

        if ($preserve_html) {
            return htmlspecialchars(trim($input), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // Prevenir XSS en atributos
        $input = preg_replace('/\bon\w+\s*=/i', 'data-${0}', $input);

        return trim($input);
    }

    /**
     * Genera código aleatorio seguro (mejora de randToken)
     */
    public function generateRandomCode(int $length = 64, string $type = 'alphanum'): string {
        $charsets = [
            'alphanum' => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
            'alphanum_special' => '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ#$%&@[]{}',
            'hex' => '0123456789abcdef',
            'base64' => 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789+/'
        ];

        $charset = $charsets[$type] ?? $charsets['alphanum'];
        $max = strlen($charset) - 1;
        $result = '';

        for ($i = 0; $i < $length; $i++) {
            $result .= $charset[random_int(0, $max)];
        }

        return $result;
    }

    /**
     * Método de compatibilidad con código antiguo
     */
    public function ende_crypter(string $action, string $string, string $secret_key, string $secret_iv): string {
        // Método legacy para compatibilidad
        if ($action === 'encrypt') {
            return $this->encrypt($string, $secret_key);
        } else {
            return $this->decrypt($string, $secret_key);
        }
    }

    /**
     * Encripta datos en múltiples capas (máxima seguridad)
     */
    public function multiLayerEncrypt(string $data, array $keys): string {
        $encrypted = $data;

        foreach ($keys as $key) {
            $encrypted = $this->encrypt($encrypted, $key);
        }

        return $encrypted;
    }

    /**
     * Desencripta datos multícapa
     */
    public function multiLayerDecrypt(string $data, array $keys): string {
        $decrypted = $data;

        // Desencriptar en orden inverso
        foreach (array_reverse($keys) as $key) {
            $decrypted = $this->decrypt($decrypted, $key);
        }

        return $decrypted;
    }

    /**
     * Obtener información de seguridad (método público para acceder a constantes)
     */
    public function getSecurityInfo(): array {
        return [
            'encrypt_method' => self::ENCRYPT_METHOD,
            'hash_algo' => self::HASH_ALGO,
            'pbkdf2_iterations' => self::PBKDF2_ITERATIONS,
            'iv_length' => self::IV_LENGTH,
            'tag_length' => self::TAG_LENGTH,
            'salt_length' => self::SALT_LENGTH,
            'legacy_mode' => $this->use_legacy_mode
        ];
    }
}
?>
