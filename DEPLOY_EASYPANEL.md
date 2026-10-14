# Despliegue de AutoControl en EasyPanel

## Arquitectura

- Servicio de aplicación: construir la imagen con el `Dockerfile` de este repositorio.
- Base de datos: crear un servicio MySQL en EasyPanel y mantenerlo privado en la red interna.
- Dominio: asignar `autocontrol.programamega.com` al servicio de aplicación y activar HTTPS.
- Puerta de acceso: Apache exige usuario y contraseña en el sitio y en la API. Usa credenciales fuertes y no las guardes en Git.

La autenticación de la aplicación que aparece después de la puerta Basic Auth es del lado del navegador; no reemplaza una autenticación de servidor. Comparte el acceso del sitio únicamente con personas autorizadas.

## Preparar la base de datos

1. Crea en EasyPanel una base de datos y un usuario dedicados a AutoControl. No uses el usuario root.
2. Importa `Base_de_datos/autocontrol_db.sql` en la base nueva. La cuenta SQL de ejemplo `admin` / `admin123` pertenece a la tabla `administradores` y no autentica el frontend actual.
3. Guarda el host interno, puerto, nombre de base y usuario para usarlos como variables de entorno.

No importes `autocontrol.sql`: es un volcado antiguo de otro esquema y no corresponde al almacenamiento JSON de la versión actual.

Si la tabla `almacenamiento` está vacía, el frontend crea datos de demostración y usa inicialmente el acceso interno `admin` / `1234`. Cambia ese acceso desde la configuración de administrador antes de guardar información real. Ese inicio de sesión se valida en el navegador; la protección de servidor de esta guía es la barrera de acceso efectiva.

## Crear el servicio en EasyPanel

1. Crea una aplicación desde el repositorio Git y selecciona la raíz del proyecto como contexto de construcción.
2. Selecciona construcción mediante `Dockerfile` y el archivo `Dockerfile`.
3. Configura el puerto interno `80`.
4. Añade el dominio `autocontrol.programamega.com` y habilita HTTPS.
5. Configura estas variables en la sección de entorno/secretos del servicio:

   | Variable | Valor |
   |---|---|
   | `AUTOCTRL_BASIC_AUTH_USER` | Usuario elegido para proteger el sitio |
   | `AUTOCTRL_BASIC_AUTH_PASSWORD` | Contraseña larga y única para proteger el sitio |
   | `AUTOCTRL_DB_HOST` | Host interno del servicio MySQL |
   | `AUTOCTRL_DB_PORT` | Puerto de MySQL, normalmente `3306` |
   | `AUTOCTRL_DB_NAME` | Nombre de la base importada |
   | `AUTOCTRL_DB_USER` | Usuario MySQL dedicado |
   | `AUTOCTRL_DB_PASS` | Contraseña MySQL |

6. Despliega y comprueba que el dominio solicite la autenticación del servidor y que la app abra después de autenticarte.

## Actualizaciones y copias de seguridad

- Configura copias de seguridad periódicas de MySQL antes de comenzar a guardar datos reales.
- Los datos de la app se guardan en la tabla `almacenamiento`. No elimines esa tabla al actualizar.
- Las variables de base de datos y las credenciales Basic Auth se configuran en EasyPanel; no las añadas a archivos del repositorio.
- Si cambias la estructura de la app, vuelve a desplegar desde el repositorio y verifica la conexión a la base de datos.

## Recordatorios de WhatsApp

Los recordatorios no se ejecutan automáticamente dentro del servicio web. Para habilitarlos, configura una tarea programada de PHP CLI con `Backend/procesar_recordatorios.php`, con las mismas variables `AUTOCTRL_DB_*` y las variables `AUTOCTRL_WA_*` descritas en [Backend/RECORDATORIOS_WHATSAPP.md](Backend/RECORDATORIOS_WHATSAPP.md). No añadas tokens de WhatsApp al frontend ni al repositorio.

## Pruebas locales

La imagen requiere Docker para construir y ejecutar. También puedes probar la app con PHP, PDO MySQL y Apache; el frontend espera que `Frontend` y `Backend` conserven esos nombres y que la API esté disponible en `../Backend/api.php`.
