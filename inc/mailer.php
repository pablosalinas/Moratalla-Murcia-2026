<?php
// inc/mailer.php - Cifrado AES-256 + HMAC y cliente SMTP autenticado (Ionos)

if (!defined('MM_CRYPTO_SECRET')) {
    $envKey = function_exists('env') ? env('APP_CRYPTO_KEY', '') : '';
    define('MM_CRYPTO_SECRET', $envKey ?: 'MM2026_Moratalla_CryptoKey_#9821_SecureVault');
}

// Credenciales por defecto pre-encriptadas con AES-256-CBC + HMAC-SHA256 (nunca en texto plano en el código)
define('MM_DEFAULT_SMTP_USER_ENC', 'ENCv1:uUbSu/e83SQAXs6GOGscogvq22TPx8ZTkII0gYm8BhKIGT/NlcUv4xvpsISxCyTcPtK4O3Nc9zBkur0U3A4BNvI9QgsvpuMpYk+VyM8/UgI=');
define('MM_DEFAULT_SMTP_PASS_ENC', 'ENCv1:YTXXzOvpW6mbCB/ur824nHy/8S9YDRoEMocyVivDSOEGdBMFabHCEzSkvCZepozRXBYPTZA5L7AJ/Bxdq1BE+RVuijqbaI7pCH74r/hrz80=');

/**
 * Encripta un texto sensible usando AES-256-CBC + HMAC-SHA256
 */
function encryptSecret($plainText) {
    if ($plainText === null || $plainText === '') return '';
    if (strpos($plainText, 'ENCv1:') === 0) return $plainText; // Ya está encriptado

    $key = hash('sha256', MM_CRYPTO_SECRET, true);
    $iv = random_bytes(16);
    $cipher = openssl_encrypt($plainText, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);
    $mac = hash_hmac('sha256', $iv . $cipher, $key, true);
    return 'ENCv1:' . base64_encode($iv . $mac . $cipher);
}

/**
 * Desencripta un valor previamente encriptado con encryptSecret()
 */
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

/**
 * Obtiene y sincroniza la configuración de correo encriptada en la tabla settings
 */
function getMailConfig($pdo = null) {
    $settings = [];
    if ($pdo) {
        try {
            $stmt = $pdo->query("SELECT setting_key, setting_value FROM settings");
            $settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);
        } catch (Exception $e) {
            $settings = [];
        }
    }

    // Inicializar valores encriptados en la base de datos si aún no existen
    $defaultsToSeed = [];
    if (empty($settings['smtp_host'])) {
        $settings['smtp_host'] = 'smtp.ionos.es';
        $defaultsToSeed['smtp_host'] = 'smtp.ionos.es';
    }
    if (empty($settings['smtp_port'])) {
        $settings['smtp_port'] = '587';
        $defaultsToSeed['smtp_port'] = '587';
    }
    if (empty($settings['smtp_encryption'])) {
        $settings['smtp_encryption'] = 'tls';
        $defaultsToSeed['smtp_encryption'] = 'tls';
    }
    if (empty($settings['smtp_user_enc'])) {
        $settings['smtp_user_enc'] = MM_DEFAULT_SMTP_USER_ENC;
        $defaultsToSeed['smtp_user_enc'] = MM_DEFAULT_SMTP_USER_ENC;
    }
    if (empty($settings['smtp_pass_enc'])) {
        $settings['smtp_pass_enc'] = MM_DEFAULT_SMTP_PASS_ENC;
        $defaultsToSeed['smtp_pass_enc'] = MM_DEFAULT_SMTP_PASS_ENC;
    }

    $defaultRecipient = decryptSecret(MM_DEFAULT_SMTP_USER_ENC);
    if (empty($settings['admin_email']) || $settings['admin_email'] === 'pablosalinas@moratalla-murcia.com') {
        $settings['admin_email'] = $defaultRecipient;
        $defaultsToSeed['admin_email'] = $defaultRecipient;
    }

    if ($pdo && !empty($defaultsToSeed)) {
        try {
            $upsert = $pdo->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
            foreach ($defaultsToSeed as $k => $v) {
                $upsert->execute([$k, $v, $v]);
            }
        } catch (Exception $e) {
            // Silencioso si la tabla es de solo lectura temporalmente
        }
    }

    $smtpUser = decryptSecret($settings['smtp_user_enc']);
    $smtpPass = decryptSecret($settings['smtp_pass_enc']);

    return [
        'admin_email'     => !empty($settings['admin_email']) ? $settings['admin_email'] : $defaultRecipient,
        'smtp_host'       => $settings['smtp_host'],
        'smtp_port'       => (int)$settings['smtp_port'],
        'smtp_encryption' => strtolower($settings['smtp_encryption']),
        'smtp_user'       => $smtpUser ?: $defaultRecipient,
        'smtp_pass'       => $smtpPass ?: decryptSecret(MM_DEFAULT_SMTP_PASS_ENC),
        'smtp_user_enc'   => $settings['smtp_user_enc'],
        'smtp_pass_enc'   => $settings['smtp_pass_enc'],
    ];
}

/**
 * Lee la respuesta multilínea de un servidor SMTP
 */
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

/**
 * Envía un comando SMTP y verifica el código de respuesta esperado
 */
function smtpSendCommand($socket, $command, $expectedCodes = [250]) {
    if ($command !== null) {
        fwrite($socket, $command . "\r\n");
    }
    $response = smtpReadResponse($socket);
    $code = (int)substr($response, 0, 3);
    if (!in_array($code, (array)$expectedCodes, true)) {
        throw new Exception("Error SMTP (esperado " . implode('/', (array)$expectedCodes) . ", recibido $code): " . trim($response));
    }
    return $response;
}

/**
 * Realiza una conexión y envío SMTP en un puerto/cifrado concreto
 */
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
        throw new Exception("No se pudo conectar a $remote ($errno: $errstr)");
    }

    stream_set_timeout($socket, 12);

    try {
        // Saludo inicial
        smtpSendCommand($socket, null, [220]);
        $ehloHost = isset($_SERVER['HTTP_HOST']) && !empty($_SERVER['HTTP_HOST']) ? preg_replace('/:\d+$/', '', $_SERVER['HTTP_HOST']) : 'moratalla-murcia.com';
        smtpSendCommand($socket, "EHLO " . $ehloHost, [250]);

        // Negociar STARTTLS si el modo es TLS (puerto 587)
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
                throw new Exception("No se pudo activar el cifrado STARTTLS con el servidor SMTP.");
            }
            smtpSendCommand($socket, "EHLO " . $ehloHost, [250]);
        }

        // Autenticación AUTH LOGIN
        smtpSendCommand($socket, "AUTH LOGIN", [334]);
        smtpSendCommand($socket, base64_encode($user), [334]);
        smtpSendCommand($socket, base64_encode($pass), [235]);

        // Remitente y destinatarios
        smtpSendCommand($socket, "MAIL FROM:<" . $fromEmail . ">", [250]);
        $recipients = array_unique(array_filter(array_map('trim', explode(',', $toEmail))));
        foreach ($recipients as $rcpt) {
            smtpSendCommand($socket, "RCPT TO:<" . $rcpt . ">", [250, 251]);
        }

        // Contenido del mensaje
        smtpSendCommand($socket, "DATA", [354]);

        $boundary = "=_MM_Boundary_" . md5(uniqid((string)mt_rand(), true));
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
        $headers[] = "Message-ID: <" . uniqid('mm_', true) . "@moratalla-murcia.com>";
        $headers[] = "MIME-Version: 1.0";
        $headers[] = "Content-Type: multipart/alternative; boundary=\"{$boundary}\"";
        $headers[] = "X-Mailer: MoratallaMurcia-SMTP/2.0";

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

        // Escapar puntos iniciales según RFC 5321 y cerrar bloque DATA
        $messageData = str_replace("\r\n.", "\r\n..", $messageData);
        smtpSendCommand($socket, $messageData . "\r\n.", [250]);

        @fwrite($socket, "QUIT\r\n");
        @fclose($socket);
        return true;
    } catch (Exception $e) {
        @fclose($socket);
        throw $e;
    }
}

/**
 * Envía el mensaje del formulario de contacto a info@moratalla-murcia.com (y admin_email si es distinto)
 * usando la cuenta SMTP de Ionos encriptada.
 */
function sendContactEmail($pdo, $senderName, $senderPhone, $senderEmail, $messageText, &$errorDetail = null) {
    $cfg = getMailConfig($pdo);

    $fromEmail = $cfg['smtp_user'];
    $fromName = "Web Moratalla-Murcia (" . $senderName . ")";

    // Asegurar que siempre se envíe a info@moratalla-murcia.com y al admin_email configurado
    $targetList = [$cfg['smtp_user']];
    if (!empty($cfg['admin_email']) && strtolower($cfg['admin_email']) !== strtolower($cfg['smtp_user'])) {
        $targetList[] = $cfg['admin_email'];
    }
    $toEmails = implode(',', array_unique($targetList));

    $subject = "Nuevo mensaje de contacto de $senderName - moratalla-murcia.com";

    $phoneDisplay = !empty($senderPhone) ? $senderPhone : 'No facilitado';
    $dateDisplay = date('d/m/Y H:i:s');

    $plainBody  = "Has recibido un nuevo mensaje desde el formulario de contacto de moratalla-murcia.com\r\n";
    $plainBody .= "=====================================================================\r\n\r\n";
    $plainBody .= "DATOS DEL REMITENTE:\r\n";
    $plainBody .= "- Nombre:   {$senderName}\r\n";
    $plainBody .= "- Correo:   {$senderEmail}\r\n";
    $plainBody .= "- Teléfono: {$phoneDisplay}\r\n";
    $plainBody .= "- Fecha:    {$dateDisplay}\r\n\r\n";
    $plainBody .= "MENSAJE:\r\n";
    $plainBody .= "{$messageText}\r\n\r\n";
    $plainBody .= "---------------------------------------------------------------------\r\n";
    $plainBody .= "Puedes responder directamente a este correo para contestar a {$senderName} ({$senderEmail}).\r\n";

    $safeName = htmlspecialchars($senderName, ENT_QUOTES, 'UTF-8');
    $safeEmail = htmlspecialchars($senderEmail, ENT_QUOTES, 'UTF-8');
    $safePhone = htmlspecialchars($phoneDisplay, ENT_QUOTES, 'UTF-8');
    $safeMsg = nl2br(htmlspecialchars($messageText, ENT_QUOTES, 'UTF-8'));

    $htmlBody = "<!DOCTYPE html><html lang='es'><body style='font-family: Arial, sans-serif; background:#f4f6f5; padding:20px; color:#2d3436;'>
        <div style='max-width:620px; margin:0 auto; background:#ffffff; border-radius:14px; overflow:hidden; border:2px solid #2d6a4f; box-shadow:0 6px 20px rgba(0,0,0,0.08);'>
            <div style='background:#2d6a4f; color:#ffffff; padding:18px 24px;'>
                <h2 style='margin:0; font-size:20px;'>Nuevo mensaje de contacto - moratalla-murcia.com</h2>
            </div>
            <div style='padding:24px;'>
                <p style='margin-top:0;'>Se ha recibido un nuevo mensaje desde el formulario de contacto de la web principal:</p>
                <table style='width:100%; border-collapse:collapse; margin-bottom:20px; background:#f8faf9; border-radius:8px;'>
                    <tr><td style='padding:8px 12px; font-weight:bold; width:110px; color:#1b4332;'>Nombre:</td><td style='padding:8px 12px;'>{$safeName}</td></tr>
                    <tr><td style='padding:8px 12px; font-weight:bold; color:#1b4332;'>Correo:</td><td style='padding:8px 12px;'><a href='mailto:{$safeEmail}' style='color:#2d6a4f;'>{$safeEmail}</a></td></tr>
                    <tr><td style='padding:8px 12px; font-weight:bold; color:#1b4332;'>Teléfono:</td><td style='padding:8px 12px;'>{$safePhone}</td></tr>
                    <tr><td style='padding:8px 12px; font-weight:bold; color:#1b4332;'>Fecha:</td><td style='padding:8px 12px;'>{$dateDisplay}</td></tr>
                </table>
                <div style='background:#f1f3f2; border-left:4px solid #d4af37; padding:16px; border-radius:6px; line-height:1.6;'>
                    <strong style='display:block; margin-bottom:8px; color:#1b4332;'>Mensaje:</strong>
                    {$safeMsg}
                </div>
                <p style='font-size:12px; color:#636e72; margin-top:20px; margin-bottom:0;'>
                    Este mensaje también ha quedado registrado en la Bandeja de Mensajes del panel de administración. Puedes pulsar «Responder» en tu gestor de correo para contestar directamente a <strong>{$safeEmail}</strong>.
                </p>
            </div>
        </div>
    </body></html>";

    // Intentar primero con el puerto/cifrado configurado y, si falla, probar la alternativa de Ionos (587 TLS <-> 465 SSL)
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
                $toEmails,
                $senderEmail,
                $senderName,
                $subject,
                $plainBody,
                $htmlBody
            );
            return true;
        } catch (Exception $e) {
            $errors[] = "[{$cfg['smtp_host']}:{$attempt['port']}/{$attempt['enc']}] " . $e->getMessage();
        }
    }

    // Fallback final a mail() nativo del servidor si los sockets salientes estuvieran bloqueados
    $fallbackHeaders  = "From: {$fromEmail}\r\n";
    $fallbackHeaders .= "Reply-To: {$senderEmail}\r\n";
    $fallbackHeaders .= "MIME-Version: 1.0\r\n";
    $fallbackHeaders .= "Content-Type: text/plain; charset=UTF-8\r\n";
    $fallbackOk = @mail($toEmails, "=?UTF-8?B?" . base64_encode($subject) . "?=", $plainBody, $fallbackHeaders, "-f " . $fromEmail);

    if (!$fallbackOk) {
        $errorDetail = implode(" | ", $errors);
        @file_put_contents(__DIR__ . '/../error_mail.log', date('Y-m-d H:i:s') . " - " . $errorDetail . PHP_EOL, FILE_APPEND);
    }

    return $fallbackOk;
}
