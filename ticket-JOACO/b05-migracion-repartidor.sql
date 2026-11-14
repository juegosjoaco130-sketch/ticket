-- ============================================================================
-- AVISO HISTÓRICO (corrección final B03/B05): NO EJECUTAR este script.
-- La estructura actual de Dely (database/init.sql) ya utiliza los roles
-- `encargado` y `repartidor`, y el sistema de pedidos se implementó en
-- B05 sobre esas tablas, por lo que esta migración quedó obsoleta. Se
-- conserva únicamente como registro histórico del trabajo realizado.
-- ============================================================================

-- ============================================================================
-- DELY — B05: migración definitiva del rol docente -> repartidor (histórica)
-- ============================================================================
-- Qué hace: permite 'repartidor' en usuarios.rol, convierte los registros
-- 'docente' existentes y retira el valor viejo. No borra usuarios ni tablas.
--
-- ORDEN OBLIGATORIO (no saltear pasos):
--   0. Inspección (solo lectura).
--   1. ALTER para AGREGAR 'repartidor' al ENUM (no toca datos).
--   2. UPDATE docente -> repartidor + comprobación (docentes_restantes = 0).
--   3. SOLO SI el paso 2 dio 0: ALTER para QUITAR 'docente' del ENUM.
--   4. Verificación final.
--
-- Por qué en este orden: MySQL no permite quitar un valor ENUM que todavía
-- existe en filas (error o pérdida de datos), ni guardar 'repartidor' antes
-- de que el ENUM lo admita. Este orden evita ambos problemas.
--
-- Reversibilidad: el script puede correrse dos veces sin daño (el UPDATE de
-- la 2da vez afecta 0 filas). Si algo sale mal ANTES del paso 3, ver
-- "Plan B" al final. Después del paso 3, 'docente' deja de existir.
--
-- NOTA SESIONES: los usuarios con sesión abierta como 'docente' deberán
-- volver a iniciar sesión tras la migración (su rol de sesión queda obsoleto).
-- ============================================================================

-- ----------------------------------------------------------------------------
-- PASO 0) Inspección previa (solo lectura). Ejecutar y revisar ANTES de seguir.
-- ----------------------------------------------------------------------------
SHOW COLUMNS FROM usuarios LIKE 'rol';

SELECT rol, COUNT(*) AS cantidad
FROM usuarios
GROUP BY rol;

-- Si ya ves 'repartidor' y cero 'docente', la migración ya está hecha:
-- no ejecutes los pasos 1-3, salta a la verificación final (paso 4).

-- ----------------------------------------------------------------------------
-- PASO 1) Agregar 'repartidor' al ENUM. No modifica ningún dato existente.
-- (Se conserva NOT NULL y sin valor por defecto, igual que el original.)
-- ----------------------------------------------------------------------------
ALTER TABLE usuarios
    MODIFY rol ENUM('docente', 'encargado', 'repartidor') NOT NULL;

-- ----------------------------------------------------------------------------
-- PASO 2) Convertir los registros y COMPROBAR el resultado.
-- ----------------------------------------------------------------------------
UPDATE usuarios
    SET rol = 'repartidor'
    WHERE rol = 'docente';

-- El cliente MySQL informa las filas afectadas. Además, este conteo DEBE dar 0:
SELECT COUNT(*) AS docentes_restantes
FROM usuarios
WHERE rol = 'docente';

SELECT rol, COUNT(*) AS cantidad
FROM usuarios
GROUP BY rol;

-- ----------------------------------------------------------------------------
-- PASO 3) Quitar 'docente' del ENUM.
-- *** Ejecutar SOLO SI docentes_restantes = 0 en el paso 2. ***
-- ----------------------------------------------------------------------------
ALTER TABLE usuarios
    MODIFY rol ENUM('encargado', 'repartidor') NOT NULL;

-- ----------------------------------------------------------------------------
-- PASO 4) Verificación final (solo lectura).
-- ----------------------------------------------------------------------------
SHOW COLUMNS FROM usuarios LIKE 'rol';

SELECT rol, COUNT(*) AS cantidad
FROM usuarios
GROUP BY rol;

-- Esperado: rol admite únicamente 'encargado' y 'repartidor'; ningún registro
-- con otro valor. El código B05 ya trabaja con esos dos valores.

-- ============================================================================
-- PLAN B (rollback, solo si algo salió mal ANTES del paso 3):
--   1) ALTER TABLE usuarios MODIFY rol ENUM('docente','encargado','repartidor') NOT NULL;
--   2) UPDATE usuarios SET rol = 'docente' WHERE <condición de los revertidos>;
--      (identificarlos por id_usuario anotados antes de migrar, o por fecha
--      si la tabla tuviera auditoría; por eso el paso 0 recomienda revisar
--      primero cuántos 'docente' había).
--   3) Avisar a SrJota antes de reintentar.
-- ============================================================================
