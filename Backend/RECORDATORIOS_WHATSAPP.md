# Recordatorios automáticos por WhatsApp

El envío automático necesita PHP CLI, MySQL, una tarea diaria de Windows y una cuenta de WhatsApp Business Cloud API. La aplicación no envía mensajes en segundo plano desde el navegador.

## Preparar WhatsApp Business

1. Configura una cuenta de WhatsApp Business y registra el número del taller en Meta.
2. Crea y consigue la aprobación de una plantilla en español de Colombia (`es_CO`) con cuatro variables en el cuerpo. Ejemplo:

   `Hola {{1}}, tu mantenimiento de {{2}} está programado para el {{3}}. {{4}}`

3. Configura la plantilla con una categoría permitida por Meta para recordatorios de servicio. Los mensajes iniciados por el taller fuera de la ventana de atención de 24 horas deben usar una plantilla aprobada.
4. Recopila autorización del cliente. El registro permite marcarla; en clientes existentes, el administrador debe marcar la autorización únicamente cuando el cliente la haya dado. El procesador omite clientes sin `whatsappConsent: true`.

## Variables de entorno

Configura estas variables en Windows para la cuenta que ejecutará la tarea. No guardes el token dentro del frontend ni en el repositorio.

- `AUTOCTRL_WA_TOKEN`: token de acceso de WhatsApp Business Cloud API.
- `AUTOCTRL_WA_PHONE_NUMBER_ID`: ID del número de teléfono de WhatsApp en Meta.
- `AUTOCTRL_WA_TEMPLATE`: nombre exacto de la plantilla aprobada.
- `AUTOCTRL_WA_TEMPLATE_LANGUAGE`: opcional; por defecto `es_CO`.
- `AUTOCTRL_WA_GRAPH_VERSION`: opcional; por defecto `v23.0`.
- `AUTOCTRL_DB_HOST`, `AUTOCTRL_DB_PORT`, `AUTOCTRL_DB_NAME`, `AUTOCTRL_DB_USER` y `AUTOCTRL_DB_PASS`: conexión MySQL del procesador; en EasyPanel usa las mismas variables configuradas para la aplicación web.

El número del cliente se toma primero de `whatsapp` y luego de `telefono`. Los números colombianos de diez dígitos reciben automáticamente el prefijo `57`.

## Base de datos

La instalación de AutoControl debe tener la tabla `almacenamiento`, usada por `api.php`. La definición y la tabla de deduplicación `recordatorios_enviados` están en `Base_de_datos/autocontrol_db.sql`. Para bases ya existentes, ejecuta esas dos definiciones desde phpMyAdmin si las tablas aún no existen. El procesador también crea ambas tablas si puede conectarse con permisos DDL.

## Programador de tareas de Windows

1. Abre el Programador de tareas y crea una tarea diaria para reglas de días y meses, por ejemplo a las 8:00 a. m. (zona `America/Bogota`). Para reglas de horas/minutos, programa ejecuciones cada minuto.
2. Programa que se ejecute aunque el usuario no tenga una sesión abierta y configura la tarea para no iniciar otra instancia si la anterior sigue ejecutándose.
3. Acción: iniciar programa `C:\xampp\php\php.exe`.
4. Argumentos: ruta completa a `Backend\procesar_recordatorios.php`.
5. Carpeta de inicio: ruta de la carpeta `Backend`.
6. Ejecuta la tarea manualmente una vez y revisa su historial. El archivo imprime un JSON con `activo`, `ultimaRevision`, cantidad de envíos y error, si lo hubo.

El indicador de Historial solo mostrará **Recordatorios automáticos activos** cuando el procesador haya ejecutado recientemente y la API no haya reportado errores. La tabla `recordatorios_enviados` evita duplicados por alerta, regla y fecha.

## Fechas utilizadas

Los recordatorios se calculan con la fecha de cada registro del módulo Alertas. Los ocho valores iniciales se administran desde **Historial > Editar periodos de recordatorios**. Se pueden agregar segundos, minutos, horas, días o meses antes/después; los cambios se aplican a las siguientes ejecuciones del procesador.

**Precisión:** actualmente las alertas guardan una fecha, no la hora del servicio. Los intervalos de segundos/minutos/horas se calculan tomando el inicio del día (`00:00`, hora de Bogotá) como referencia. Las reglas de horas/minutos requieren que el Programador de tareas ejecute el worker cada minuto. Una regla de segundos requiere un servicio/proceso persistente que lo ejecute cada segundo; el Programador de tareas estándar no ofrece esa precisión por sí solo. Sin esa frecuencia, el mensaje no puede garantizar el segundo exacto.

Las facturas alimentan el historial de servicios realizados; una alerta coincidente por cliente y servicio aporta la fecha del próximo mantenimiento.

## Estimación por servicio

Cada servicio tiene un intervalo recomendado editable en **Administrador > Servicios**: kilometraje y máximo de días. Al guardar una factura, se crea un registro de mantenimiento separado por cada servicio realizado, con fecha y kilometraje actuales, próximo kilometraje y fecha estimada.

La fecha estimada es el límite que llegue primero: el intervalo máximo en días o el kilometraje recomendado según el promedio diario observado en hasta las tres lecturas anteriores de la misma placa. Si no hay suficientes lecturas, se usa el intervalo de días. Los valores iniciales son orientativos, no sustituyen el manual del fabricante ni una inspección del vehículo; el taller debe ajustarlos según condiciones, carga y estilo de conducción.

En **Historial**, cada mantenimiento puede quedar **Activo** o **Finalizado**. Al finalizarlo, su alerta asociada se marca como realizada y el procesador deja de enviar recordatorios para ese registro. Si se reactiva, la alerta vuelve a pendiente.
