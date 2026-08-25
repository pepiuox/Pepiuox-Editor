<?php
namespace App\Services;

class MailService
{
    /**
     * Envía un email.
     * 
     * @param string $to Email destinatario
     * @param string $subject Asunto
     * @param string $html Contenido HTML
     * @param array $options Opciones adicionales (cc, bcc, reply_to, attachments)
     */
    public static function send(string $to, string $subject, string $html, array $options = []): bool
    {
        $cfg = require CONFIG_PATH . '/config.php';

        // 1. Registrar en log (siempre)
        $logDir = $cfg['logs']['path'] ?? ROOT_PATH . '/storage/logs';
        if (!is_dir($logDir)) @mkdir($logDir, 0775, true);
        
        $entry = [
            'to' => $to,
            'subject' => $subject,
            'html_length' => strlen($html),
            'options' => array_keys($options),
            'sent_at' => date('c'),
        ];
        file_put_contents(
            $logDir . '/mail.log',
            json_encode($entry, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n---\n",
            FILE_APPEND
        );

        // 2. Si hay SMTP configurado, enviar de verdad
        if (!empty($cfg['mail']['smtp_host'])) {
            return self::sendSmtp($to, $subject, $html, $cfg['mail'], $options);
        }

        // 3. Fallback: mail() nativo
        $from = $cfg['mail']['from'] ?? 'no-reply@local.test';
        $fromName = $cfg['mail']['from_name'] ?? 'PePiuoX Maker';
        
        $headers = [
            "MIME-Version: 1.0",
            "Content-type: text/html; charset=utf-8",
            "From: $fromName <$from>",
            "Reply-To: $from",
            "X-Mailer: PePiuoX-Maker/1.0",
        ];
        
        if (!empty($options['cc'])) {
            $headers[] = "Cc: " . (is_array($options['cc']) ? implode(',', $options['cc']) : $options['cc']);
        }
        if (!empty($options['bcc'])) {
            $headers[] = "Bcc: " . (is_array($options['bcc']) ? implode(',', $options['bcc']) : $options['bcc']);
        }
        
        return @mail($to, $subject, $html, implode("\r\n", $headers));
    }

    private static function sendSmtp(string $to, string $subject, string $html, array $m, array $options = []): bool
    {
        $fp = @fsockopen($m['smtp_host'], $m['smtp_port'] ?? 587, $errno, $errstr, 10);
        if (!$fp) return false;
        
        $read = fn() => fgets($fp, 512);
        $write = fn($s) => fwrite($fp, $s . "\r\n");
        
        try {
            $read();
            $write("EHLO localhost"); $read();
            $write("AUTH LOGIN"); $read();
            $write(base64_encode($m['smtp_user'])); $read();
            $write(base64_encode($m['smtp_pass'])); $read();
            $write("MAIL FROM:<{$m['from']}>"); $read();
            $write("RCPT TO:<$to>"); $read();
            $write("DATA"); $read();
            
            $from = $m['from'];
            $fromName = $m['from_name'] ?? 'PePiuoX Maker';
            
            $write("From: $fromName <$from>");
            $write("To: $to");
            $write("Subject: $subject");
            $write("MIME-Version: 1.0");
            $write("Content-Type: text/html; charset=utf-8");
            $write("Date: " . date('r'));
            $write("Message-ID: <" . uniqid() . "@pepiuox.local>");
            $write("");
            $write($html);
            $write("."); 
            $read();
            $write("QUIT"); 
            $read();
            
            return true;
        } finally {
            fclose($fp);
        }
    }
}