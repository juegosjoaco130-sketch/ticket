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
-- Tabla `usuarios` real: id, nombre, apellido, usuario, password, rol,
-- estado, fecha_creacion. Roles: encargado, repartidor. Estados: activo,
-- inactivo. (B03-corr: ya no se usan id_usuario, cedula, password_hash
-- ni activo: eran columnas de la plantilla vieja.)
--
-- Cómo leer los resultados: cada bloque indica lo esperado. Si una consulta
-- falla por tabla/columna inexistente, ESO MISMO es un hallazgo: anotalo
-- como diferencia entre el código y la base real.
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
-- 1) Columnas reales de `usuarios` (compatibilidad con el backend B03-corr)
-- Se esperan: id, nombre, apellido, usuario, password, rol, estado,
-- fecha_creacion. Revisar tipos, NOT NULL y valores por defecto.
-- ----------------------------------------------------------------------------
SHOW COLUMNS FROM usuarios;

SELECT COLUMN_NAME AS columna, IS_NULLABLE AS nulo,
       COLUMN_DEFAULT AS por_defecto, COLUMN_TYPE AS tipo
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'
ORDER BY ORDINAL_POSITION;

-- ----------------------------------------------------------------------------
-- 2) Valores del rol y del estado (punto de partida de la migración B05)
-- Esperado: rol solo 'encargado'/'repartidor'; estado solo
-- 'activo'/'inactivo'. Si aparece 'docente', falta correr la migración B05.
-- ----------------------------------------------------------------------------
SELECT rol, COUNT(*) AS cantidad
FROM usuarios
GROUP BY rol;

SELECT estado, COUNT(*) AS cantidad
FROM usuarios
GROUP BY estado;

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
-- 6) Volúmenes generales y usuarios inactivos
-- ----------------------------------------------------------------------------
SELECT COUNT(*) AS total_usuarios FROM usuarios;
SELECT COUNT(*) AS total_pedidos FROM pedidos;
SELECT COUNT(*) AS total_historial FROM historial_pedidos;
SELECT COUNT(*) AS usuarios_inactivos FROM usuarios WHERE estado = 'inactivo';

-- ----------------------------------------------------------------------------
-- 7) Unicidad del nombre de usuario (se esperan 0 filas)
-- El alta de usuarios rechaza nombres duplicados.
-- ----------------------------------------------------------------------------
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
-- FIN. Siguiente paso: informe B03-corr. La adaptación tickets -> pedidos
-- corresponde a B04. Para ella se necesita el volcado real:
--   SHOW CREATE TABLE pedidos; SHOW CREATE TABLE estados_pedido;
--   SHOW CREATE TABLE historial_pedidos; SHOW CREATE TABLE usuarios;
-- ============================================================================
