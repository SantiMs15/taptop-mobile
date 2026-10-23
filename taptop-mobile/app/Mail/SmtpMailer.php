<?php
/**
 * Cliente SMTP mínimo (TLS implícito, AUTH LOGIN) y armado del mensaje. Sin dependencias.
 * Los mensajes de error nunca incluyen destinatarios ni datos del cliente.
 */

declare(strict_types=1);

namespace Taptop\Mail;

use InvalidArgumentException;
use RuntimeException;

final class SmtpMailer
{
    public function __construct(
        private string $host,
        private int $port,
        private string $username,
        private string $password,
        private int $timeout = 15,
    ) {
    }

    /** Mensaje completo (cabeceras + cuerpo base64), con saltos \r\n. */
    public static function buildMessage(string $from, string $fromName, string $to, string $subject, string $body, ?string $replyTo = null): string
    {
        foreach (['from' => $from, 'to' => $to, 'replyTo' => (string) $replyTo, 'subject' => $subject, 'fromName' => $fromName] as $name => $value) {
            if (preg_match('/[\r\n]/', $value) === 1) {
                throw new InvalidArgumentException("Salto de línea no permitido en {$name}");
            }
        }
        $isAscii = static fn (string $text): bool => preg_match('/^[\x20-\x7E]*$/', $text) === 1;
        $encode = static fn (string $text): string => $isAscii($text) ? $text : '=?UTF-8?B?' . base64_encode($text) . '?=';
        $domain = substr((string) strrchr($from, '@'), 1);
        if ($domain === '') {
            $domain = 'localhost';
        }
        if ($fromName === '') {
            $fromHeader = $from;
        } else {
            $display = $isAscii($fromName) ? '"' . addcslashes($fromName, '"\\') . '"' : $encode($fromName);
            $fromHeader = $display . ' <' . $from . '>';
        }

        $headers = [
            'Date: ' . date('r'),
            'From: ' . $fromHeader,
            'To: ' . $to,
        ];
        if ($replyTo !== null && $replyTo !== '') {
            $headers[] = 'Reply-To: ' . $replyTo;
        }
        $headers[] = 'Subject: ' . $encode($subject);
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Content-Transfer-Encoding: base64';
        $headers[] = 'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $domain . '>';

        return implode("\r\n", $headers) . "\r\n\r\n" . chunk_split(base64_encode($body), 76, "\r\n");
    }

    /** Envía por SMTP. Lanza RuntimeException (con el código SMTP) si algo falla. */
    public function send(string $from, string $to, string $message): void
    {
        foreach ([$from, $to] as $address) {
            if (preg_match('/[\r\n<>]/', $address) === 1) {
                throw new InvalidArgumentException('Dirección de correo inválida');
            }
        }
        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client('ssl://' . $this->host . ':' . $this->port, $errno, $errstr, $this->timeout);
        if ($socket === false) {
            throw new RuntimeException('SMTP: no se pudo conectar (' . $errno . ')');
        }
        try {
            stream_set_timeout($socket, $this->timeout);
            $this->expect($socket, [220]);
            $site = parse_url((string) config('site_url'), PHP_URL_HOST);
            $this->command($socket, 'EHLO ' . (is_string($site) && $site !== '' ? $site : 'localhost'), [250]);
            $this->command($socket, 'AUTH LOGIN', [334]);
            $this->command($socket, base64_encode($this->username), [334]);
            $this->command($socket, base64_encode($this->password), [235]);
            $this->command($socket, 'MAIL FROM:<' . $from . '>', [250]);
            $this->command($socket, 'RCPT TO:<' . $to . '>', [250, 251]);
            $this->command($socket, 'DATA', [354]);
            // Dot-stuffing: toda línea que empieza con "." lleva otro "." delante.
            $data = str_replace(["\r\n", "\r"], "\n", $message);
            $data = (string) preg_replace('/^\./m', '..', $data);
            $data = str_replace("\n", "\r\n", $data);
            $this->command($socket, rtrim($data, "\r\n") . "\r\n.", [250]);
            try {
                $this->command($socket, 'QUIT', [221]);
            } catch (RuntimeException) {
                // El correo ya fue aceptado; un QUIT mal respondido no lo deshace.
            }
        } finally {
            fclose($socket);
        }
    }

    /** @param resource $socket */
    private function command($socket, string $line, array $expected): string
    {
        if (fwrite($socket, $line . "\r\n") === false) {
            throw new RuntimeException('SMTP: no se pudo escribir');
        }
        return $this->expect($socket, $expected);
    }

    /**
     * Lee una respuesta completa (las líneas "250-..." continúan hasta "250 ...").
     * @param resource $socket
     */
    private function expect($socket, array $expected): string
    {
        $reply = '';
        do {
            $line = fgets($socket, 1024);
            if ($line === false) {
                throw new RuntimeException('SMTP: sin respuesta del servidor');
            }
            $reply .= $line;
        } while (strlen($line) >= 4 && $line[3] === '-');
        $code = (int) substr($reply, 0, 3);
        if (!in_array($code, $expected, true)) {
            throw new RuntimeException('SMTP: respuesta inesperada ' . $code);
        }
        return $reply;
    }
}
