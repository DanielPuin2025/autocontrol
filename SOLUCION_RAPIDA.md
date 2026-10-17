# Solución Rápida - Error de Conexión a Base de Datos

## ⚠️ Problema Detectado

Tu instalación de PHP **no tiene** configurado el driver `pdo_mysql` que es necesario para conectarse a MySQL.

## ✅ Solución Rápida (5 minutos)

### Opción 1: Usar XAMPP (Recomendado para Windows)

Si estás usando PHP standalone, es más fácil instalar XAMPP que ya viene con todo configurado:

1. **Descargar XAMPP:**
   - Ir a: https://www.apachefriends.org/
   - Descargar XAMPP para Windows (incluye PHP 8+, MySQL, Apache)

2. **Instalar XAMPP:**
   - Ejecutar el instalador
   - Instalar en `C:\xampp`

3. **Copiar AutoControl a XAMPP:**
   ```bash
   xcopy /E /I "C:\Users\DANIEL_PUIN\Documents\Autocontrol" "C:\xampp\htdocs\autocontrol"
   ```

4. **Iniciar servicios en XAMPP:**
   - Abrir "XAMPP Control Panel"
   - Hacer clic en "Start" para Apache
   - Hacer clic en "Start" para MySQL

5. **Crear la base de datos:**
   - Clic en "Shell" en XAMPP
   - Ejecutar:
   ```bash
   mysql -u root
   CREATE DATABASE autocontrol_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   exit;
   
   mysql -u root autocontrol_db < C:\xampp\htdocs\autocontrol\Base_de_datos\autocontrol_db.sql
   mysql -u root autocontrol_db < C:\xampp\htdocs\autocontrol\Base_de_datos\datos_ejemplo.sql
   ```

6. **Abrir la aplicación:**
   - http://localhost/autocontrol/Frontend/

---

### Opción 2: Configurar PHP Actual

Si prefieres usar tu PHP actual en `C:\php`:

1. **Localizar php.ini:**
   ```bash
   # Buscar si existe php.ini
   dir C:\php\php.ini
   
   # Si no existe, copiar desde php.ini-development
   copy C:\php\php.ini-development C:\php\php.ini
   ```

2. **Editar php.ini:**
   Abrir `C:\php\php.ini` y buscar estas líneas, quitarles el `;` al inicio:
   ```ini
   extension_dir = "C:\php\ext"
   extension=pdo_mysql
   extension=mysqli
   extension=mbstring
   extension=curl
   ```

3. **Verificar que existen los archivos DLL:**
   ```bash
   dir C:\php\ext\php_pdo_mysql.dll
   dir C:\php\ext\php_mysqli.dll
   ```

   **Si no existen los archivos**, necesitas reinstalar PHP o descargar una versión que incluya extensiones.

4. **Reiniciar el servidor web:**
   - Si usas Apache: reiniciar Apache
   - Si usas `php -S`: detener y volver a iniciar

5. **Verificar:**
   ```bash
   php -m | findstr "pdo_mysql"
   ```
   Debería mostrar: `pdo_mysql`

---

### Opción 3: Usar Docker (Más avanzado)

Si tienes Docker instalado:

1. **Construir y ejecutar:**
   ```bash
   cd C:\Users\DANIEL_PUIN\Documents\Autocontrol
   
   # Construir imagen
   docker build -t autocontrol .
   
   # Ejecutar con MySQL local
   docker run -d -p 8080:80 ^
     -e AUTOCTRL_DB_HOST=host.docker.internal ^
     -e AUTOCTRL_DB_PORT=3306 ^
     -e AUTOCTRL_DB_NAME=autocontrol_db ^
     -e AUTOCTRL_DB_USER=root ^
     -e AUTOCTRL_DB_PASS= ^
     --name autocontrol ^
     autocontrol
   ```

2. **Acceder:**
   - http://localhost:8080

---

## 🔍 Verificar que funcionó

Después de aplicar cualquiera de las soluciones:

1. **Verificar extensiones PHP:**
   ```bash
   php -m | findstr "pdo_mysql mysqli"
   ```
   Deberías ver ambas extensiones listadas.

2. **Probar conexión:**
   ```bash
   cd C:\xampp\htdocs\autocontrol
   php verificar_db.php
   ```

3. **Abrir test.php:**
   - http://localhost/autocontrol/test.php
   - Todos los checks deberían estar en verde ✓

4. **Abrir la aplicación:**
   - http://localhost/autocontrol/Frontend/
   - Ya no debería aparecer el error rojo

---

## 📋 Mi Recomendación

**Para desarrollo local en Windows: usa XAMPP** ✅

Es la forma más rápida y confiable. Ya viene con:
- PHP 8+ con todas las extensiones
- MySQL/MariaDB configurado
- Apache configurado
- phpMyAdmin para gestionar la base de datos

Total: ~10 minutos de configuración vs horas resolviendo problemas de extensiones.

---

## ❓ ¿Qué opción elegir?

- **Si eres nuevo o quieres algo rápido:** → Opción 1 (XAMPP)
- **Si ya tienes PHP y sabes configurarlo:** → Opción 2 (Configurar PHP)
- **Si trabajas con containers:** → Opción 3 (Docker)

---

## 🆘 Si nada funciona

Envía esta información:

```bash
php --version
php -m
php --ini
dir C:\php\ext
```

Y capturas de pantalla de:
- http://localhost/autocontrol/test.php
- El error que aparece
