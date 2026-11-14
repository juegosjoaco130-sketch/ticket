-- ============================================================================
-- DELY — B03/B05: Auditoría de la base de datos (SOLO LECTURA)
-- ============================================================================
-- Uso:
--   mysql -h <host> -P <puerto> -u <usuario> -p dely < b03-auditoria-db.sql
-- o copiar cada bloque en phpMyAdmin.
--
-- Esquema canónico: database/init.sql con 5 tablas:
--   usuarios, prioridades, estados, pedidos, historial_pedidos (+ 7 FKs)
--
-- Tabla `usuarios`: id_usuario, cedula, nombre, apellido, usuario,
-- password_hash, rol, activo, fecha_creacion. Roles: encargado, repartidor.
-- (B05: este es el esquema canónico; ignora versiones anteriores.)
--
-- Cómo leer los resultados: cada bloque indica lo esperado. Si una consulta
-- falla por tabla/columna inexistente, ESO MISMO es un hallazgo: anotalo
-- como diferencia entre el código y la base real.
-- ============================================================================

-- ----------------------------------------------------------------------------
-- 0) Base actual y lista de tablas (se esperan 5)
-- ----------------------------------------------------------------------------
SELECT DATABASE() AS base_actual;

SELECT TABLE_NAME AS tabla, TABLE_ROWS AS filas_aprox, ENGINE AS motor
FROM INFORMATION_SCHEMA.TABLES
WHERE TABLE_SCHEMA = DATABASE()
ORDER BY TABLE_NAME;

-- ----------------------------------------------------------------------------
-- 1) Columnas reales de `usuarios` (compatibilidad con el backend B05)
-- Se esperan: id_usuario, cedula, nombre, apellido, usuario, password_hash,
-- rol, activo, fecha_creacion. Revisar tipos, NOT NULL y valores por defecto.
-- ----------------------------------------------------------------------------
SHOW COLUMNS FROM usuarios;

SELECT COLUMN_NAME AS columna, IS_NULLABLE AS nulo,
       COLUMN_DEFAULT AS por_defecto, COLUMN_TYPE AS tipo
FROM INFORMATION_SCHEMA.COLUMNS
WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'usuarios'
ORDER BY ORDINAL_POSITION;

-- ----------------------------------------------------------------------------
-- 2) Roles y estados de usuarios
-- Esperado: rol solo 'encargado'/'repartidor'; activo solo 0/1.
-- ----------------------------------------------------------------------------
SELECT rol, COUNT(*) AS cantidad
FROM usuarios
GROUP BY rol;

SELECT activo, COUNT(*) AS cantidad
FROM usuarios
GROUP BY activo;

-- ----------------------------------------------------------------------------
-- 3) Claves primarias (se espera una por tabla)
-- ----------------------------------------------------------------------------
SELECT TABLE_NAME AS tabla, COLUMN_NAME AS columna_pk,
       ORDINAL_POSITION AS posicion
FROM INFORMATION_SCHEMA.STATISTICS
WHERE TABLE_SCHEMA = DATABASE() AND INDEX_NAME = 'PRIMARY'
ORDER BY tabla, posicion;

-- ----------------------------------------------------------------------------
-- 4) Claves foráneas (se esperan 7: 4 de pedidos + 3 de historial) y reglas
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
-- 5) Catálogos: estados (3) y prioridades (4)
-- Estados: En preparación, En camino, Entregado.
-- Prioridades: Baja, Normal, Alta, Urgente.
-- ----------------------------------------------------------------------------
SELECT * FROM estados;

SELECT COUNT(*) AS total_estados FROM estados;

SELECT * FROM prioridades;

SELECT COUNT(*) AS total_prioridades FROM prioridades;

-- ----------------------------------------------------------------------------
-- 6) Volúmenes generales y usuarios inactivos
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
-- 8) Coherencia pedidos/historial
-- Todo pedido en estado distinto del inicial debería tener historial;
-- ningún historial debe apuntar a un pedido inexistente (lo garantiza la FK).
-- ----------------------------------------------------------------------------
SELECT e.nombre AS estado, COUNT(*) AS cantidad
FROM pedidos AS p
INNER JOIN estados AS e ON e.id_estado = p.id_estado
GROUP BY e.nombre;

SELECT COUNT(*) AS historial_sin_pedido
FROM historial_pedidos AS h
LEFT JOIN pedidos AS p ON p.id_pedido = h.id_pedido
WHERE p.id_pedido IS NULL;

-- ============================================================================
-- FIN. Si todo coincide con database/init.sql, el backend B05 es compatible.
-- ============================================================================
