-- ============================================================================
-- DELY: eliminar la columna id_prioridad de la tabla pedidos
-- ============================================================================
-- QUÉ HACE (solo 3 cambios, nada más):
--   1. Elimina la clave foránea fk_pedido_prioridad de `pedidos`.
--   2. Elimina el índice idx_pedido_prioridad_fecha de `pedidos`.
--   3. Elimina la columna id_prioridad de `pedidos`.
--
-- QUÉ NO HACE:
--   - No elimina la tabla `prioridades` (queda como está, sin uso).
--   - No borra usuarios, pedidos, estados ni historial.
--   - No toca ninguna otra tabla, columna o índice.
--
-- ORDEN DE APLICACIÓN (importante):
--   1. Primero publicar el código nuevo (ya no usa id_prioridad).
--   2. Después ejecutar este script en phpMyAdmin (pestaña SQL de la
--      base `dely`, usuario con permiso ALTER).
--   El código nuevo funciona con la columna todavía presente, así que
--   no hay corte: publicar primero, migrar después.
--
-- SEGURIDAD: antes de ejecutar, exportar un respaldo desde phpMyAdmin
-- (Exportar > SQL). Si algo falla, no reintentar a ciegas: leer el
-- mensaje de error y avisar.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- PASO 0) Comprobación previa (solo lectura): debe mostrar la FK, el índice
-- y la columna que se van a eliminar. Si alguna fila falta, detenerse.
-- ----------------------------------------------------------------------------
SELECT CONSTRAINT_NAME AS foranea, TABLE_NAME AS tabla, COLUMN_NAME AS columna
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'pedidos'
  AND CONSTRAINT_NAME = 'fk_pedido_prioridad';

SHOW INDEX FROM pedidos WHERE Key_name = 'idx_pedido_prioridad_fecha';

SHOW COLUMNS FROM pedidos LIKE 'id_prioridad';

-- ----------------------------------------------------------------------------
-- PASO 1) Eliminar la clave foránea de prioridad.
-- (Obligatorio antes de tocar la columna: MySQL no deja eliminar una
-- columna que todavía tiene una FK apuntándola.)
-- ----------------------------------------------------------------------------
ALTER TABLE pedidos
    DROP FOREIGN KEY fk_pedido_prioridad;

-- ----------------------------------------------------------------------------
-- PASO 2) Eliminar el índice de prioridad.
-- ----------------------------------------------------------------------------
ALTER TABLE pedidos
    DROP INDEX idx_pedido_prioridad_fecha;

-- ----------------------------------------------------------------------------
-- PASO 3) Eliminar la columna id_prioridad.
-- (El índice implícito de la FK vieja se elimina solo con la columna.)
-- ----------------------------------------------------------------------------
ALTER TABLE pedidos
    DROP COLUMN id_prioridad;

-- ----------------------------------------------------------------------------
-- PASO 4) Verificación final (solo lectura).
-- Esperado: 3 foráneas en pedidos (encargado, repartidor, estado),
-- 4 índices (fecha, estado_fecha, encargado_fecha, repartidor_fecha),
-- y la columna id_prioridad ausente. Los pedidos siguen intactos.
-- ----------------------------------------------------------------------------
SELECT CONSTRAINT_NAME AS foranea, COLUMN_NAME AS columna,
       REFERENCED_TABLE_NAME AS referencia
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'pedidos'
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY CONSTRAINT_NAME;

SELECT INDEX_NAME AS indice, GROUP_CONCAT(COLUMN_NAME ORDER BY SEQ_IN_INDEX) AS columnas
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE()
  AND TABLE_NAME = 'pedidos'
GROUP BY INDEX_NAME
ORDER BY INDEX_NAME;

SHOW COLUMNS FROM pedidos LIKE 'id_prioridad';

SELECT COUNT(*) AS total_pedidos FROM pedidos;
SELECT COUNT(*) AS total_historial FROM historial_pedidos;
