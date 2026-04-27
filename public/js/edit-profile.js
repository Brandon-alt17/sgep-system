function abrirModalEditar() {
    const modal = document.getElementById('modal-editar-aprendiz');
    if (modal) {
        modal.classList.remove('hidden');
        document.body.style.overflow = 'hidden';
        
        // Resetear a la pestaña de aprendiz por defecto
        cambiarTab('aprendiz');
    }
}

function cerrarModal(event) {
    // Si se pasó un evento, evitar propagación
    if (event) {
        event.stopPropagation();
    }
    
    const modal = document.getElementById('modal-editar-aprendiz');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = '';
    }
}

function cambiarTab(tab) {
    const aprendizDiv = document.getElementById('contenido-aprendiz');
    const empresaDiv = document.getElementById('contenido-empresa');
    const tabAprendiz = document.getElementById('tab-aprendiz');
    const tabEmpresa = document.getElementById('tab-empresa');
    const modalContainer = document.getElementById('modal-container');
    
    if (!aprendizDiv || !empresaDiv || !modalContainer) return;
    
    if (tab === 'aprendiz') {
        // Mostrar contenido de aprendiz
        aprendizDiv.classList.remove('hidden');
        empresaDiv.classList.add('hidden');
        
        // Actualizar estilos de tabs
        if (tabAprendiz) {
            tabAprendiz.classList.remove('inactive');
            tabAprendiz.classList.add('active');
        }
        if (tabEmpresa) {
            tabEmpresa.classList.remove('active');
            tabEmpresa.classList.add('inactive');
        }
        
        // Cambiar tamaño del modal a más ancho
        modalContainer.classList.remove('empresa-size');
        modalContainer.classList.add('aprendiz-size');
        
    } else {
        // Mostrar contenido de empresa
        aprendizDiv.classList.add('hidden');
        empresaDiv.classList.remove('hidden');
        
        // Actualizar estilos de tabs
        if (tabEmpresa) {
            tabEmpresa.classList.remove('inactive');
            tabEmpresa.classList.add('active');
        }
        if (tabAprendiz) {
            tabAprendiz.classList.remove('active');
            tabAprendiz.classList.add('inactive');
        }
        
        // Cambiar tamaño del modal a más compacto
        modalContainer.classList.remove('aprendiz-size');
        modalContainer.classList.add('empresa-size');
    }
}

// Evitar que clicks dentro del modal cierren el modal
document.addEventListener('DOMContentLoaded', function() {
    const modalContainer = document.getElementById('modal-container');
    if (modalContainer) {
        modalContainer.addEventListener('click', function(event) {
            event.stopPropagation();
        });
    }
});