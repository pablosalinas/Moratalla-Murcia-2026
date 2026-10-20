-- Guardar correo del administrador y contraseña encriptada (AES-256 + HMAC) para envío SMTP en Ionos
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('admin_email', 'info@moratalla-murcia.com'),
('smtp_host', 'smtp.ionos.es'),
('smtp_port', '587'),
('smtp_encryption', 'tls'),
('smtp_user_enc', 'ENCv1:uUbSu/e83SQAXs6GOGscogvq22TPx8ZTkII0gYm8BhKIGT/NlcUv4xvpsISxCyTcPtK4O3Nc9zBkur0U3A4BNvI9QgsvpuMpYk+VyM8/UgI='),
('smtp_pass_enc', 'ENCv1:YTXXzOvpW6mbCB/ur824nHy/8S9YDRoEMocyVivDSOEGdBMFabHCEzSkvCZepozRXBYPTZA5L7AJ/Bxdq1BE+RVuijqbaI7pCH74r/hrz80=')
ON DUPLICATE KEY UPDATE `setting_value` = VALUES(`setting_value`);
