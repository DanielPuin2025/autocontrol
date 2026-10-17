# Solución de Problemas - AutoControl

## Error: "No se guardó en la base de datos de EasyPanel"

Este error ocurre cuando la aplicación no puede conectarse a MySQL. Las causas más comunes son:

### 1. PHP no tiene habilitado el driver MySQL (PDO)

**Para XAMPP:**
1. Abrir `C:\xampp\php\php.ini`
2. Buscar y descomentar (quitar el `;`):
   ```ini
   extension=pdo_mysql
   extension=mysqli
   ```
3. Reiniciar Apache

**Para verificar:**
```bash
php -m | grep -i mysql
```

Deberías ver:
```
mysqli
pdo_mysql
```

### 2. La base de datos no está creada

```bash
# Conectar a MySQL
mysql -u root -p

# Crear la base de datos
CREATE DATABASE IF NOT EXISTS autocontrol_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# Salir
exit;

# Importar el esquema
mysql -u root -p autocontrol_db < Base_de_datos/autocontrol_db.sql

# (Opcional) Importar datos de ejemplo
mysql -u root -p autocontrol_db < Base_de_datos/datos_ejemplo.sql
```

### 3. MySQL no está ejecutándose

**XAMPP:**
- Abrir XAMPP Control Panel
- Iniciar "MySQL"

**Verificar que MySQL esté corriendo:**
```bash
# Windows
netstat -an | findstr "3306"

# Linux/Mac
netstat -an | grep 3306
```

### 4. Credenciales incorrectas

Por defecto, AutoControl usa:
- Host: `localhost`
- Puerto: `3306`
- Base de datos: `autocontrol_db`
- Usuario: `root`
- Contraseña: (vacía)

**Si usas credenciales diferentes, configúralas en:**

**Opción A: Variables de entorno**
```bash
# Windows
set AUTOCTRL_DB_HOST=localhost
set AUTOCTRL_DB_PORT=3306
set AUTOCTRL_DB_NAME=autocontrol_db
set AUTOCTRL_DB_USER=root
set AUTOCTRL_DB_PASS=tu_contraseña

# Linux/Mac
export AUTOCTRL_DB_HOST=localhost
export AUTOCTRL_DB_PORT=3306
export AUTOCTRL_DB_NAME=autocontrol_db
export AUTOCTRL_DB_USER=root
export AUTOCTRL_DB_PASS=tu_contraseña
```

**Opción B: Editar Backend/db.php directamente** (solo para desarrollo local)
```php
$DB_HOST = 'localhost';
$DB_PORT = '3306';
$DB_NAME = 'autocontrol_db';
$DB_USER = 'root';
$DB_PASS = 'tu_contraseña';
```

### 5. Scripts de diagnóstico

#### A. Verificar extensiones PHP:
```bash
php -i | grep -i "pdo\|mysql"
```

#### B. Probar conexión:
Visitar: `http://localhost/autocontrol/verificar_db.php`

#### C. Probar API:
```bash
curl http://localhost/autocontrol/Backend/api.php?clave=test
```

Debería responder con JSON.

### 6. Pasos completos de instalación (XAMPP)

```bash
# 1. Iniciar MySQL en XAMPP
# 2. Crear la base de datos
mysql -u root -p
CREATE DATABASE autocontrol_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit;

# 3. Importar esquema
mysql -u root -p autocontrol_db < C:\xampp\htdocs\autocontrol\Base_de_datos\autocontrol_db.sql

# 4. Importar datos de ejemplo (opcional)
mysql -u root -p autocontrol_db < C:\xampp\htdocs\autocontrol\Base_de_datos\datos_ejemplo.sql

# 5. Verificar que php.ini tenga habilitado PDO MySQL
# Editar C:\xampp\php\php.ini y verificar:
extension=pdo_mysql
extension=mysqli

# 6. Reiniciar Apache en XAMPP

# 7. Probar
http://localhost/autocontrol/test.php
```

### 7. Verificar que la tabla almacenamiento existe

```sql
mysql -u root -p autocontrol_db

SHOW TABLES;

-- Si no aparece 'almacenamiento', crearla:
CREATE TABLE IF NOT EXISTS almacenamiento (
  clave VARCHAR(100) NOT NULL PRIMARY KEY,
  valor LONGTEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

exit;
```

### 8. Logs de error

**Verificar logs de PHP:**
- XAMPP: `C:\xampp\apache\logs\error.log`
- Linux: `/var/log/apache2/error.log`

**Verificar logs de MySQL:**
- XAMPP: `C:\xampp\mysql\data\mysql_error.log`

### 9. Prueba rápida con línea de comandos

```bash
# Probar si MySQL está accesible
mysql -h localhost -P 3306 -u root -p autocontrol_db

# Una vez dentro, probar:
SHOW TABLES;
SELECT * FROM almacenamiento;
```

### 10. Solución rápida - Reinstalar base de datos

```bash
cd C:\xampp\htdocs\autocontrol

# Borrar base de datos existente (⚠️ CUIDADO: perderás datos)
mysql -u root -p -e "DROP DATABASE IF EXISTS autocontrol_db;"

# Crear nueva base de datos
mysql -u root -p -e "CREATE DATABASE autocontrol_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importar esquema completo
mysql -u root -p autocontrol_db < Base_de_datos\autocontrol_db.sql

# Importar datos de ejemplo
mysql -u root -p autocontrol_db < Base_de_datos\datos_ejemplo.sql

# Probar
http://localhost/autocontrol/Frontend/
```

## Errores comunes y soluciones

| Error | Causa | Solución |
|-------|-------|----------|
| `could not find driver` | PDO MySQL no habilitado | Habilitar en php.ini |
| `Access denied for user` | Credenciales incorrectas | Verificar usuario/contraseña |
| `Unknown database` | Base de datos no existe | Crear e importar autocontrol_db.sql |
| `Table 'almacenamiento' doesn't exist` | Tabla faltante | Importar autocontrol_db.sql completo |
| `Can't connect to MySQL server` | MySQL no está corriendo | Iniciar MySQL en XAMPP |

## Contacto y soporte

Si después de seguir estos pasos el problema persiste:
1. Ejecuta `http://localhost/autocontrol/test.php`
2. Captura el resultado
3. Revisa los logs de error de Apache y MySQL
