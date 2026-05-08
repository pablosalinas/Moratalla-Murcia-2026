-- Migración 007: Asegurar usuario admin y resetear contraseña
-- Fecha: 2026-05-08

-- 1. Eliminar posibles duplicados
DELETE FROM `users` WHERE `username` = 'admin';

-- 2. Insertar admin limpio con pass: admin123
INSERT INTO `users` (`username`, `password`, `role`, `failed_attempts`, `locked_until`) 
VALUES ('admin', '$2y$10$WkG.C4oB3n0O.z8kL4A2QOTM8R7A6SHTgT./QcOzT3Y5T.S/oR6J6', 'admin', 0, NULL);
