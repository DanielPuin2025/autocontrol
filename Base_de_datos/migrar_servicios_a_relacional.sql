-- SCRIPT DE MIGRACIÓN: Consolidar servicios duplicados a estructura relacional
-- Ejecutar DESPUÉS de crear la tabla servicios_vehiculos
-- Este script identifica servicios duplicados y los consolida

USE autocontrol_db;

-- Paso 1: Identificar servicios base únicos por nombre
-- Este paso MANUAL requiere revisar los servicios actuales

-- Ejemplo de consolidación:
-- Si tienes:
-- ID 1: "Cambio de aceite" - $50,000
-- ID 2: "Cambio de aceite moto" - $30,000
-- ID 3: "Cambio de aceite camioneta" - $60,000

-- Paso 1: Mantener servicio base (ID 1) como "Cambio de aceite"
-- UPDATE servicios SET nombre_servicio = 'Cambio de aceite' WHERE id_servicio = 1;

-- Paso 2: Crear relaciones para cada tipo
-- INSERT INTO servicios_vehiculos (id_servicio, tipo_vehiculo, precio_especifico) VALUES
-- (1, 'carro', 50000.00),
-- (1, 'moto', 30000.00),
-- (1, 'camioneta', 60000.00);

-- Paso 3: Eliminar servicios duplicados
-- DELETE FROM servicios WHERE id_servicio IN (2, 3);

-- =====================================================================
-- SCRIPT AUTOMÁTICO DE CONSOLIDACIÓN
-- =====================================================================

-- Crear tabla temporal para análisis
CREATE TEMPORARY TABLE temp_servicios_consolidados AS
SELECT
    MIN(id_servicio) as id_base,
    LOWER(TRIM(REGEXP_REPLACE(nombre_servicio, '(moto|motocicleta|carro|automovil|camioneta)', ''))) as nombre_base,
    COUNT(*) as cantidad,
    GROUP_CONCAT(id_servicio ORDER BY id_servicio) as ids_duplicados
FROM servicios
GROUP BY nombre_base
HAVING cantidad > 1;

-- Ver resultados del análisis
SELECT * FROM temp_servicios_consolidados;

-- NOTA: La consolidación real debe hacerse manualmente o con script JS
-- porque requiere decisiones sobre qué precio usar para cada tipo

SELECT '⚠️ ATENCIÓN: Revisar temp_servicios_consolidados para planificar migración' AS Mensaje;
SELECT 'Ejecutar script de consola migrar_a_tabla_relacional.js después' AS ProximoPaso;
