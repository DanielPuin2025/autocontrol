-- =====================================================================
-- Script de datos de ejemplo para AutoControl
-- Ejecutar después de importar autocontrol_db.sql
-- =====================================================================

USE autocontrol_db;

-- Insertar clientes de ejemplo
INSERT INTO clientes (nombre, apellido, documento, telefono, whatsapp, correo) VALUES
('Juan', 'Pérez García', '1234567890', '3001234567', '3001234567', 'juan.perez@email.com'),
('María', 'Rodríguez López', '0987654321', '3109876543', '3109876543', 'maria.rodriguez@email.com'),
('Carlos', 'González Martínez', '1122334455', '3201122334', '3201122334', 'carlos.gonzalez@email.com'),
('Ana', 'Hernández Silva', '5544332211', '3155443322', '3155443322', 'ana.hernandez@email.com');

-- Insertar vehículos de ejemplo
INSERT INTO vehiculos (id_cliente, placa, marca, modelo, anio, tipo_vehiculo, kilometraje_actual) VALUES
(1, 'ABC123', 'Toyota', 'Corolla', 2020, 'carro', 45000),
(1, 'XYZ789', 'Yamaha', 'FZ16', 2019, 'moto', 22000),
(2, 'DEF456', 'Chevrolet', 'Spark', 2021, 'carro', 18000),
(3, 'GHI789', 'Mazda', 'CX-5', 2022, 'camioneta', 12000),
(4, 'JKL012', 'Honda', 'Civic', 2018, 'carro', 67000);

-- Los servicios ya están insertados en autocontrol_db.sql, pero aquí hay algunos adicionales
INSERT INTO servicios (nombre_servicio, descripcion, precio, duracion_km, duracion_dias, estado) VALUES
('Revisión de frenos', 'Inspección y ajuste del sistema de frenos', 35000, 10000, 180, 'activo'),
('Cambio de batería', 'Reemplazo de batería del vehículo', 180000, NULL, 730, 'activo'),
('Limpieza de inyectores', 'Limpieza profunda del sistema de inyección', 120000, 20000, 365, 'activo'),
('Cambio de filtro de aire', 'Reemplazo del filtro de aire del motor', 25000, 15000, 180, 'activo');

-- Insertar usuarios de ejemplo (contraseñas: todas son "password123")
-- Hash generado con: password_hash('password123', PASSWORD_DEFAULT)
INSERT INTO usuarios (id_cliente, usuario, contrasena, estado) VALUES
(1, 'juan.perez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo'),
(2, 'maria.rodriguez', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'activo');

-- Insertar mantenimientos de ejemplo
INSERT INTO mantenimientos (id_cliente, id_vehiculo, id_servicio, fecha_servicio, proximo_mantenimiento, kilometraje_actual, kilometraje_proximo, estado) VALUES
(1, 1, 1, '2024-01-15', '2024-07-15', 45000, 50000, 'realizado'),
(1, 1, 4, '2024-01-15', '2024-07-15', 45000, 50000, 'realizado'),
(2, 3, 1, '2024-02-20', '2024-08-20', 18000, 23000, 'realizado'),
(3, 4, 2, '2024-03-10', '2025-03-10', 12000, 52000, 'realizado');

-- Insertar facturas de ejemplo
INSERT INTO facturas (id_cliente, id_vehiculo, fecha, kilometraje_actual, kilometraje_proximo, subtotal, total, estado) VALUES
(1, 1, '2024-01-15 10:30:00', 45000, 50000, 70000, 70000, 'pagada'),
(2, 3, '2024-02-20 14:15:00', 18000, 23000, 45000, 45000, 'pagada'),
(3, 4, '2024-03-10 09:00:00', 12000, 52000, 90000, 90000, 'pendiente');

-- Insertar detalles de facturas
INSERT INTO detalle_factura (id_factura, id_servicio, cantidad, precio, subtotal) VALUES
-- Factura 1: Cambio de aceite + Alineación
(1, 1, 1, 45000, 45000),
(1, 4, 1, 40000, 40000),
-- Factura 2: Solo cambio de aceite
(2, 1, 1, 45000, 45000),
-- Factura 3: Cambio de bandas
(3, 2, 1, 90000, 90000);

-- Actualizar totales de facturas (por si hay diferencia)
UPDATE facturas f SET
  subtotal = (SELECT SUM(subtotal) FROM detalle_factura WHERE id_factura = f.id_factura),
  total = (SELECT SUM(subtotal) FROM detalle_factura WHERE id_factura = f.id_factura);

-- Insertar alertas de ejemplo
INSERT INTO alertas (id_cliente, id_vehiculo, id_mantenimiento, mensaje, fecha_alerta, estado) VALUES
(3, 4, NULL, 'Próximo cambio de aceite en 5000 km', '2024-04-01 08:00:00', 'pendiente'),
(1, 1, NULL, 'Recordatorio: Revisión de frenos programada', '2024-05-15 08:00:00', 'pendiente');

-- Mensaje de confirmación
SELECT 'Datos de ejemplo insertados correctamente' AS Resultado;
