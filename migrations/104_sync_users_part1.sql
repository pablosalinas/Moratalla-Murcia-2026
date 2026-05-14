-- Migración 104: Sincronización de tabla users (Parte 1)
-- Generada: 2026-05-14 12:30:43

SET NAMES 'utf8mb4' COLLATE 'utf8mb4_unicode_ci';
SET FOREIGN_KEY_CHECKS = 0;
TRUNCATE TABLE `users`;

REPLACE INTO `users` (`id`, `username`, `password`, `role`, `failed_attempts`, `locked_until`, `created_at`) VALUES ('2', 'pablo salinas marin', '$2y$10$QqUGJAEombBqF39oVEfdveVPIjYxSGYwYbjFm/cm7WMF6pNPhDuEy', 'admin', '0', NULL, '2026-05-08 11:35:03');
REPLACE INTO `users` (`id`, `username`, `password`, `role`, `failed_attempts`, `locked_until`, `created_at`) VALUES ('3', 'admin', '$2y$10$giebiGhyDUdO9RSiYgUDqelzghpb6bCmZsjmrXLPAlyeS2qq0H5mW', 'admin', '0', NULL, '2026-05-08 11:36:00');

SET FOREIGN_KEY_CHECKS = 1;
