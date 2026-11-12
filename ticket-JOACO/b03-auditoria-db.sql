-- ============================================================================
-- DELY — B03: Auditoría de la base de datos (SOLO LECTURA, no modifica nada)
-- ============================================================================
-- Uso:
--   mysql -h <host> -P <puerto> -u <usuario> -p dely < b03-auditoria-db.sql
-- o copiar cada bloque en phpMyAdmin.
--
-- Base esperada: `dely` con 4 tablas:
--   usuarios, pedidos, estados_pedido, historial_pedidos (+ 6 claves foráneas)
--
-- Cómo leer los resultados: cada bloque indica lo esperado. Si una consulta
-- falla por tabla/columna inexistente, ESO MISMO es un hallazgo: anotalo como
-- diferencia entre el código y la base real (ver informe B03).
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 0) Base actual y lista de tablas (se esperan 4)
-- ----------------------------------------------------------------------------
SELECT DATABASE() AS base_actual;

SELECT TABLE_NAME AS tabla, TABLE_ROWS AS filas_aprox, ENGINE AS motor
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- ----------------------------------------------------------------------------
-- 1) Columnas de `usuarios` (compatibilidad con el backend actual)
-- El código PHP usa: id_usuario, cedula, usuario, password_hash, rol, activo.
-- ----------------------------------------------------------------------------
SHOW COLUMNS FROM usuarios;

SELECT COLUMN_NAME AS columna, IS_NULLABLE AS nulo,
       COLUMN_DEFAULT AS por_defecto, COLUMN_TYPE AS tipo
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'
ORDER BY ORDINAL_POSITION;

-- ----------------------------------------------------------------------------
-- 2) Valores del rol (punto de partida de la migración B05)
-- Esperado hoy: solo 'docente' y/o 'encargado'.
-- ----------------------------------------------------------------------------
SHOW COLUMNS FROM usuarios LIKE 'rol';

SELECT rol, COUNT(*) AS cantidad
FROM usuarios
GROUP BY rol;

-- ----------------------------------------------------------------------------
-- 3) Claves primarias (se espera una por tabla)
-- ----------------------------------------------------------------------------
SELECT TABLE_NAME AS tabla, COLUMN_NAME AS columna_pk,
       ORDINAL_POSITION AS posicion
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND INDEX_NAME = 'PRIMARY'
ORDER BY tabla, posicion;

-- ----------------------------------------------------------------------------
-- 4) Claves foráneas (se esperan 6) y sus reglas
-- ----------------------------------------------------------------------------
SELECT CONSTRAINT_NAME AS restriccion, TABLE_NAME AS tabla,
       COLUMN_NAME AS columna, REFERENCED_TABLE_NAME AS referencia_tabla,
       REFERENCED_COLUMN_NAME AS referencia_columna
FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE
WHERE TABLE_SCHEMA = DATABASE()
  AND REFERENCED_TABLE_NAME IS NOT NULL
ORDER BY tabla, ORDINAL_POSITION;

SELECT CONSTRAINT_NAME AS restriccion, TABLE_NAME AS tabla,
       UPDATE_RULE AS al_actualizar, DELETE_RULE AS al_borrar
FROM INFORMATION_SCHEMA.REFERENTIAL_CONSTRAINTS
WHERE CONSTRAINT_SCHEMA = DATABASE()
ORDER BY tabla;

-- ----------------------------------------------------------------------------
-- 5) Catálogo de estados (se esperan 3: En preparación, En camino, Entregado)
-- ----------------------------------------------------------------------------
SELECT * FROM estados_pedido;

SELECT COUNT(*) AS total_estados FROM estados_pedido;

-- ----------------------------------------------------------------------------
-- 6) Volúmenes generales
-- ----------------------------------------------------------------------------
SELECT COUNT(*) AS total_usuarios FROM usuarios;
SELECT COUNT(*) AS total_pedidos FROM pedidos;
SELECT COUNT(*) AS total_historial FROM historial_pedidos;
SELECT COUNT(*) AS usuarios_inactivos FROM usuarios WHERE activo = FALSE;

-- ----------------------------------------------------------------------------
-- 7) Unicidad asumida por el backend (se esperan 0 filas en ambas)
-- El alta de usuarios rechaza cédula y nombre de usuario duplicados.
-- ----------------------------------------------------------------------------
SELECT cedula, COUNT(*) AS veces
FROM usuarios
GROUP BY cedula
HAVING veces > 1;

SELECT usuario, COUNT(*) AS veces
FROM usuarios
GROUP BY usuario
HAVING veces > 1;

-- ----------------------------------------------------------------------------
-- 8) Registros huérfanos (completar con las columnas del punto 4 y descomentar)
-- Se esperan 0 filas en cada conteo.
-- ----------------------------------------------------------------------------
-- Pedidos que apuntan a un usuario inexistente:
-- SELECT COUNT(*) AS pedidos_huerfanos_de_usuario
-- FROM pedidos AS p
-- LEFT JOIN usuarios AS u ON u.<pk_usuario> = p.<fk_usuario>
-- WHERE p.<fk_usuario> IS NOT NULL AND u.<pk_usuario> IS NULL;

-- Pedidos que apuntan a un estado inexistente:
-- SELECT COUNT(*) AS pedidos_huerfanos_de_estado
-- FROM pedidos AS p
-- LEFT JOIN estados_pedido AS e ON e.<pk_estado> = p.<fk_estado>
-- WHERE p.<fk_estado> IS NOT NULL AND e.<pk_estado> IS NULL;

-- Historial que apunta a un pedido inexistente:
-- SELECT COUNT(*) AS historial_huerfano_de_pedido
-- FROM historial_pedidos AS h
-- LEFT JOIN pedidos AS p ON p.<pk_pedido> = h.<fk_pedido>
-- WHERE h.<fk_pedido> IS NOT NULL AND p.<pk_pedido> IS NULL;

-- ============================================================================
-- FIN. Siguiente paso: informe B03 (diferencias código <-> BD) y luego la
-- migración B05 (b05-migracion-repartidor.sql) para el renombre del rol.
-- Para adaptar el código de tickets -> pedidos se necesita el volcado real:
--   SHOW CREATE TABLE pedidos; SHOW CREATE TABLE estados_pedido;
--   SHOW CREATE TABLE historial_pedidos; SHOW CREATE TABLE usuarios;
-- ============================================================================
