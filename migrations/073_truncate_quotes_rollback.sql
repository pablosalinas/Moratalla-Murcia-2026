-- Migración 073: Vaciar tabla quotes (rollback explícitamente autorizado)
-- La migración 072_sync_quotes_data.sql insertó datos de citas en producción.
-- Esta migración deshace esa acción dejando la tabla quotes vacía,
-- tal como estaba antes de que se ejecutara la 072.
TRUNCATE TABLE quotes;
