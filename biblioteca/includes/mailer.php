<?php
/**
 * includes/mailer.php
 * Envío de notificaciones por correo electrónico utilizando la configuración SMTP
 * de la web moratalla-murcia.com (Ionos).
 */

if (!defined('MM_CRYPTO_SECRET')) {
    $envKey = function_exists('env') ? env('APP_CRYPTO_KEY', '') : (getenv('APP_CRYPTO_KEY') ?: '');
    define('MM_CRYPTO_SECRET', $envKey ?: 'MM2026_Moratalla_CryptoKey_#9821_SecureVault');
}

if (!defined('MM_DEFAULT_SMTP_USER_ENC')) {
    define('MM_DEFAULT_SMTP_USER_ENC', 'ENCv1:uUbSu/e83SQAXs6GOGscogvq22TPx8ZTkII0gYm8BhKIGT/NlcUv4xvpsISxCyTcPtK4O3Nc9zBkur0U3A4BNvI9QgsvpuMpYk+VyM8/UgI=');
}
if (!defined('MM_DEFAULT_SMTP_PASS_ENC')) {
    define('MM_DEFAULT_SMTP_PASS_ENC', 'ENCv1:YTXXzOvpW6mbCB/ur824nHy/8S9YDRoEMocyVivDSOEGdBMFabHCEzSkvCZepozRXBYPTZA5L7AJ/Bxdq1BE+RVuijqbaI7pCH74r/hrz80=');
}

if (!function_exists('decryptSecret')) {
    function decryptSecret($cipherText) {
        if ($cipherText === null || $cipherText === '') return '';
        if (strpos($cipherText, 'ENCv1:') !== 0) {
            return $cipherText;
        }

        $raw = base64_decode(substr($cipherText, 6), true);
        if ($raw === false || strlen($raw) <= 48) {
            return '';
        }

        $key = hash('sha256', MM_CRYPTO_SECRET, true);
        $iv = substr($raw, 0, 16);
        $mac = substr($raw, 16, 32);
        $cipher = substr($raw, 48);

        $calcMac = hash_hmac('sha256', $iv . $cipher, $key, true);
        if (!hash_equals($mac, $calcMac)) {
            return '';
        }

        $plain = openssl_decrypt($cipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
        return ($plain !== false) ? $plain : '';
    }
}

/**
 * Obtiene la configuración de correo utilizando los valores encriptados de Ionos
 * configurados en la web moratalla-murcia.com (inc/mailer.php).
 */
function getBibliotecaMailConfig() {
    $smtpUser = decryptSecret(MM_DEFAULT_SMTP_USER_ENC);
    $smtpPass = decryptSecret(MM_DEFAULT_SMTP_PASS_ENC);

    return [
        'to_email'        => 'info@moratalla-murcia.com',
        'smtp_host'       => 'smtp.ionos.es',
        'smtp_port'       => 587,
        'smtp_encryption' => 'tls',
        'smtp_user'       => $smtpUser ?: 'info@moratalla-murcia.com',
        'smtp_pass'       => $smtpPass,
    ];
}

if (!function_exists('smtpReadResponse')) {
    function smtpReadResponse($socket) {
        $response = '';
        while ($line = fgets($socket, 515)) {
            $response .= $line;
            if (isset($line[3]) && $line[3] === ' ') {
                break;
            }
        }
        return $response;
    }
}

if (!function_exists('smtpSendCommand')) {
    function smtpSendCommand($socket, $command, $expectedCodes = [250]) {
        if ($command !== null) {
            fwrite($socket, $command . "\r\n");
        }
        $response = smtpReadResponse($socket);
        $code = (int)substr($response, 0, 3);
        if (!in_array($code, (array)$expectedCodes, true)) {
            throw new \Exception("Error SMTP (esperado " . implode('/', (array)$expectedCodes) . ", recibido $code): " . trim($response));
        }
        return $response;
    }
}

if (!function_exists('smtpSendRaw')) {
    function smtpSendRaw($host, $port, $encryption, $user, $pass, $fromEmail, $fromName, $toEmail, $replyToEmail, $replyToName, $subject, $plainBody, $htmlBody) {
        $remote = ($encryption === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;
        $context = stream_context_create([
            'ssl' => [
                'verify_peer'       => false,
                'verify_peer_name'  => false,
                'allow_self_signed' => true,
            ]
        ]);

        $errno = 0;
        $errstr = '';
        $socket = @stream_socket_client($remote, $errno, $errstr, 12, STREAM_CLIENT_CONNECT, $context);
        if (!$socket) {
            throw new \Exception("No se pudo conectar a $remote ($errno: $errstr)");
        }

        stream_set_timeout($socket, 12);

        try {
            smtpSendCommand($socket, null, [220]);
            $ehloHost = isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST'])
                ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST'])
                : 'moratalla-murcia.com';
            smtpSendCommand($socket, "EHLO " . $ehloHost, [250]);

            if ($encryption === 'tls') {
                smtpSendCommand($socket, "STARTTLS", [220]);
                $cryptoMethod = STREAM_CRYPTO_METHOD_TLS_CLIENT;
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT;
                }
                if (defined('STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT')) {
                    $cryptoMethod |= STREAM_CRYPTO_METHOD_TLSv1_3_CLIENT;
                }
                if (!@stream_socket_enable_crypto($socket, true, $cryptoMethod)) {
                    throw new \Exception("No se pudo activar el cifrado STARTTLS con el servidor SMTP.");
                }
                smtpSendCommand($socket, "EHLO " . $ehloHost, [250]);
            }

            smtpSendCommand($socket, "AUTH LOGIN", [334]);
            smtpSendCommand($socket, base64_encode($user), [334]);
            smtpSendCommand($socket, base64_encode($pass), [235]);

            smtpSendCommand($socket, "MAIL FROM:<" . $fromEmail . ">", [250]);
            $recipients = array_unique(array_filter(array_map('trim', explode(',', $toEmail))));
            foreach ($recipients as $rcpt) {
                smtpSendCommand($socket, "RCPT TO:<" . $rcpt . ">", [250, 251]);
            }

            smtpSendCommand($socket, "DATA", [354]);

            $boundary = "=_MM_Biblio_" . md5(uniqid((string)mt_rand(), true));
            $encodedSubject = "=?UTF-8?B?" . base64_encode($subject) . "?=";
            $encodedFromName = "=?UTF-8?B?" . base64_encode($fromName) . "?=";
            $encodedReplyName = "=?UTF-8?B?" . base64_encode($replyToName) . "?=";

            $headers = [];
            $headers[] = "Date: " . date('r');
            $headers[] = "From: {$encodedFromName} <{$fromEmail}>";
            $headers[] = "To: <" . implode('>, <', $recipients) . ">";
            if (!empty($replyToEmail)) {
                $headers[] = "Reply-To: {$encodedReplyName} <{$replyToEmail}>";
            }
            $headers[] = "Subject: {$encodedSubject}";
            $headers[] = "Message-ID: <" . uniqid('biblio_', true) . "@moratalla-murcia.com>";
            $headers[] = "MIME-Version: 1.0";
            $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
            $headers[] = "X-Mailer: MoratallaMurcia-Biblioteca-SMTP/1.0";

            $messageData = implode("\r\n", $headers) . "\r\n\r\n";
            $messageData .= "--{$boundary}\r\n";
            $messageData .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $messageData .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $messageData .= chunk_split(base64_encode($plainBody)) . "\r\n";

            $messageData .= "--{$boundary}\r\n";
            $messageData .= "Content-Type: text/html; charset=UTF-8\r\n";
            $messageData .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $messageData .= chunk_split(base64_encode($htmlBody)) . "\r\n";
            $messageData .= "--{$boundary}--\r\n";

            $messageData = str_replace("\r\n.", "\r\n..", $messageData);
            smtpSendCommand($socket, $messageData . "\r\n.", [250]);

            @fwrite($socket, "QUIT\r\n");
            @fclose($socket);
            return true;
        } catch (\Exception $e) {
            @fclose($socket);
            throw $e;
        }
    }
}

/**
 * Envía un correo a info@moratalla-murcia.com indicando que hay un alta de usuario
 * pendiente de confirmación en la Biblioteca.
 */
function enviarCorreoAltaPendiente($username, &$errorDetail = null) {
    $cfg = getBibliotecaMailConfig();

    $fromEmail = $cfg['smtp_user'];
    $fromName  = "Biblioteca Dalias y Tortas";
    $toEmail   = "info@moratalla-murcia.com";

    $subject = "Alta de usuario pendiente de confirmación - Biblioteca ({$username})";
    $dateDisplay = date('d/m/Y H:i:s');
    $ipAddress = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'Desconocida';
    $panelUrl = "https://www.moratalla-murcia.com/biblioteca/usuarios.php";

    $plainBody  = "Aviso de alta de usuario pendiente de confirmación en la Biblioteca\r\n";
    $plainBody .= "=====================================================================\r\n\r\n";
    $plainBody .= "Se ha registrado una nueva solicitud de acceso en la Biblioteca y ha quedado en estado DESHABILITADO hasta que un supervisor la autorice.\r\n\r\n";
    $plainBody .= "DATOS DEL USUARIO:\r\n";
    $plainBody .= "- Usuario: {$username}\r\n";
    $plainBody .= "- Estado:  Deshabilitado (Pendiente de confirmación)\r\n";
    $plainBody .= "- Fecha:   {$dateDisplay}\r\n";
    $plainBody .= "- IP:      {$ipAddress}\r\n\r\n";
    $plainBody .= "Para autorizar y habilitar este usuario, accede al panel de gestión de usuarios:\r\n";
    $plainBody .= "{$panelUrl}\r\n";

    $safeUsername = htmlspecialchars($username, ENT_QUOTES, 'UTF-8');
    $safeIp = htmlspecialchars($ipAddress, ENT_QUOTES, 'UTF-8');

    $htmlBody = "<!DOCTYPE html><html lang='es'><body style='font-family: Arial, sans-serif; background:#f3f4f6; padding:20px; color:#1f2937;'>
        <div style='max-width:600px; margin:0 auto; background:#ffffff; border-radius:12px; overflow:hidden; border:1px solid #e5e7eb; box-shadow:0 4px 12px rgba(0,0,0,0.06);'>
            <div style='background:#4f46e5; color:#ffffff; padding:18px 24px;'>
                <h2 style='margin:0; font-size:20px;'>Alta de usuario pendiente de confirmación</h2>
                <p style='margin:4px 0 0; font-size:13px; opacity:0.9;'>Biblioteca Dalias y Tortas - moratalla-murcia.com</p>
            </div>
            <div style='padding:24px;'>
                <p style='margin-top:0; line-height:1.5;'>Se ha registrado un nuevo usuario en la <strong>Biblioteca</strong>. La cuenta se ha guardado en estado <strong>deshabilitado</strong> a la espera de que un supervisor autorice su acceso.</p>
                <table style='width:100%; border-collapse:collapse; margin:16px 0 20px; background:#f9fafb; border-radius:8px; border:1px solid #e5e7eb;'>
                    <tr><td style='padding:10px 14px; font-weight:bold; width:130px; color:#374151;'>Usuario:</td><td style='padding:10px 14px; font-weight:bold; color:#111827;'>{$safeUsername}</td></tr>
                    <tr><td style='padding:10px 14px; font-weight:bold; color:#374151;'>Estado:</td><td style='padding:10px 14px;'><span style='background:#fef3c7; color:#92400e; padding:3px 10px; border-radius:9999px; font-size:12px; font-weight:bold;'>Deshabilitado (Pendiente)</span></td></tr>
                    <tr><td style='padding:10px 14px; font-weight:bold; color:#374151;'>Fecha:</td><td style='padding:10px 14px;'>{$dateDisplay}</td></tr>
                    <tr><td style='padding:10px 14px; font-weight:bold; color:#374151;'>IP:</td><td style='padding:10px 14px;'>{$safeIp}</td></tr>
                </table>
                <div style='text-align:center; margin:24px 0 12px;'>
                    <a href='{$panelUrl}' style='display:inline-block; background:#4f46e5; color:#ffffff; text-decoration:none; font-weight:bold; padding:12px 24px; border-radius:8px;'>Ir a Gestión de Usuarios para Autorizar</a>
                </div>
            </div>
        </div>
    </body></html>";

    $attempts = [
        ['port' => $cfg['smtp_port'], 'enc' => $cfg['smtp_encryption']],
    ];
    if ((int)$cfg['smtp_port'] === 587) {
        $attempts[] = ['port' => 465, 'enc' => 'ssl'];
    } else {
        $attempts[] = ['port' => 587, 'enc' => 'tls'];
    }

    $errors = [];
    foreach ($attempts as $attempt) {
        try {
            smtpSendRaw(
                $cfg['smtp_host'],
                $attempt['port'],
                $attempt['enc'],
                $cfg['smtp_user'],
                $cfg['smtp_pass'],
                $fromEmail,
                $fromName,
                $toEmail,
                $fromEmail,
                $fromName,
                $subject,
                $plainBody,
                $htmlBody
            );
            return true;
        } catch (\Exception $e) {
            $errors[] = "[{$cfg['smtp_host']}:{$attempt['port']}/{$attempt['enc']}] " . $e->getMessage();
        }
    }

    // Fallback a mail() nativo si los sockets salientes estuvieran bloqueados
    $fallbackHeaders  = "From: {$fromEmail}\r\n";
    $fallbackHeaders .= "MIME-Version: 1.0\r\n";
    $fallbackHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $fallbackOk = @mail($toEmail, "=?UTF-8?B?" . base64_encode($subject) . "?=", $plainBody, $fallbackHeaders, "-f " . $fromEmail);

    if (!$fallbackOk) {
        $errorDetail = implode(" | ", $errors);
        error_log("Error enviando correo de alta pendiente Biblioteca: " . $errorDetail);
    }

    return $fallbackOk;
}
