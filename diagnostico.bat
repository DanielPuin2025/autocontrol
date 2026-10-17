@echo off
REM Script de diagnóstico para AutoControl en Windows
REM Ejecutar: diagnostico.bat

echo ========================================
echo AutoControl - Diagnóstico del Sistema
echo ========================================
echo.

echo [1] Verificando PHP...
php --version
if %errorlevel% neq 0 (
    echo ERROR: PHP no está instalado o no está en el PATH
    pause
    exit /b 1
)
echo.

echo [2] Verificando extensiones PHP...
php -m | findstr /i "pdo_mysql mysqli"
if %errorlevel% neq 0 (
    echo ERROR: PDO MySQL no está habilitado
    echo Solución: Editar php.ini y descomentar extension=pdo_mysql
    pause
    exit /b 1
)
echo.

echo [3] Verificando MySQL...
mysql --version
if %errorlevel% neq 0 (
    echo ERROR: MySQL no está instalado o no está en el PATH
    pause
    exit /b 1
)
echo.

echo [4] Verificando puerto MySQL (3306)...
netstat -an | findstr "3306"
if %errorlevel% neq 0 (
    echo ADVERTENCIA: MySQL puede no estar ejecutándose en el puerto 3306
)
echo.

echo [5] Probando conexión a base de datos...
php verificar_db.php
echo.

echo [6] Verificando archivos del proyecto...
if exist "Backend\api.php" (
    echo OK: Backend\api.php existe
) else (
    echo ERROR: Backend\api.php no existe
)

if exist "Backend\db.php" (
    echo OK: Backend\db.php existe
) else (
    echo ERROR: Backend\db.php no existe
)

if exist "Base_de_datos\autocontrol_db.sql" (
    echo OK: Base_de_datos\autocontrol_db.sql existe
) else (
    echo ERROR: Base_de_datos\autocontrol_db.sql no existe
)
echo.

echo [7] Listando APIs disponibles...
dir /b Backend\api_*.php
echo.

echo ========================================
echo Diagnóstico completado
echo ========================================
echo.
echo Si hay errores, consulta SOLUCION_PROBLEMAS.md
echo.
pause
