// Script para LIMPIAR servicios duplicados en MySQL
// Ejecutar UNA VEZ en la consola del navegador (F12)

async function limpiarServiciosDuplicados() {
  console.log('🧹 Iniciando limpieza de servicios duplicados...');

  try {
    // Obtener todos los servicios de MySQL
    const res = await fetch('../Backend/api_servicios.php');
    if (!res.ok) {
      console.error('❌ No se pudo obtener servicios');
      return;
    }

    const servicios = await res.json();
    console.log('📦 Total de servicios en MySQL:', servicios.length);

    // Agrupar por nombre para detectar duplicados
    const porNombre = {};
    servicios.forEach(s => {
      const nombre = s.nombre_servicio.toLowerCase().trim();
      if (!porNombre[nombre]) {
        porNombre[nombre] = [];
      }
      porNombre[nombre].push(s);
    });

    // Identificar duplicados
    let totalDuplicados = 0;
    const idsAEliminar = [];

    for (const nombre in porNombre) {
      const grupo = porNombre[nombre];
      if (grupo.length > 1) {
        console.log(`🔍 "${grupo[0].nombre_servicio}" tiene ${grupo.length} copias`);

        // Ordenar por ID (más antiguo primero)
        grupo.sort((a, b) => a.id_servicio - b.id_servicio);

        // Mantener el PRIMERO (más antiguo), eliminar los demás
        for (let i = 1; i < grupo.length; i++) {
          idsAEliminar.push(grupo[i].id_servicio);
          totalDuplicados++;
        }
      }
    }

    console.log('\n📊 ANÁLISIS:');
    console.log('Total de servicios:', servicios.length);
    console.log('Servicios únicos:', Object.keys(porNombre).length);
    console.log('Duplicados a eliminar:', totalDuplicados);

    if (idsAEliminar.length === 0) {
      console.log('✅ No hay duplicados para eliminar');
      return;
    }

    // Confirmar antes de eliminar
    console.log('\n⚠️ Se eliminarán estos servicios duplicados (IDs):', idsAEliminar);
    console.log('⏳ Eliminando...');

    let eliminados = 0;
    let errores = 0;

    for (const id of idsAEliminar) {
      try {
        const resDel = await fetch(`../Backend/api_servicios.php?id=${id}`, {
          method: 'DELETE'
        });

        if (resDel.ok) {
          eliminados++;
          console.log(`✅ Servicio ID ${id} eliminado`);
        } else {
          errores++;
          console.error(`❌ No se pudo eliminar servicio ID ${id}`);
        }

        // Pequeña pausa
        await new Promise(resolve => setTimeout(resolve, 100));

      } catch (error) {
        errores++;
        console.error(`❌ Error eliminando servicio ID ${id}:`, error.message);
      }
    }

    console.log('\n📊 RESUMEN DE LIMPIEZA:');
    console.log('✅ Eliminados:', eliminados);
    console.log('❌ Errores:', errores);
    console.log('📦 Servicios restantes:', servicios.length - eliminados);

    console.log('\n🎉 ¡Limpieza completada!');
    console.log('👉 Recarga la página (Ctrl+Shift+R) para ver los cambios');

  } catch (error) {
    console.error('❌ Error en la limpieza:', error);
  }
}

// Ejecutar
limpiarServiciosDuplicados();
