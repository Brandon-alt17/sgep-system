function abrirModalEditar() {
    document.getElementById('modal-editar-aprendiz').classList.remove('hidden');
}

function cerrarModal() {
    document.getElementById('modal-editar-aprendiz').classList.add('hidden');
}

function cambiarTab(tab) {
    const aprendiz = document.getElementById('contenido-aprendiz');
    const empresa = document.getElementById('contenido-empresa');
    const modal = document.getElementById('modal-content');

    const tabAprendiz = document.getElementById('tab-aprendiz');
    const tabEmpresa = document.getElementById('tab-empresa');

    if (tab === 'aprendiz') {
        aprendiz.classList.remove('hidden');
        empresa.classList.add('hidden');

        tabAprendiz.classList.add('border-app-link');
        tabEmpresa.classList.remove('border-app-link');
    } else {
        aprendiz.classList.add('hidden');
        empresa.classList.remove('hidden');

        tabEmpresa.classList.add('border-app-link');
        tabAprendiz.classList.remove('border-app-link');
    }
}