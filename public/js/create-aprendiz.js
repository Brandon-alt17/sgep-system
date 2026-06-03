function cambiarTabAprendizForm(tab) {
    const aprendizDiv = document.getElementById('contenido-aprendiz');
    const empresaDiv = document.getElementById('contenido-empresa');
    const tabAprendiz = document.getElementById('tab-aprendiz');
    const tabEmpresa = document.getElementById('tab-empresa');

    if (!aprendizDiv || !empresaDiv) return;

    if (tab === 'aprendiz') {
        aprendizDiv.classList.remove('hidden');
        empresaDiv.classList.add('hidden');
        if (tabAprendiz) {
            tabAprendiz.classList.remove('inactive');
            tabAprendiz.classList.add('active');
        }
        if (tabEmpresa) {
            tabEmpresa.classList.remove('active');
            tabEmpresa.classList.add('inactive');
        }
    } else {
        aprendizDiv.classList.add('hidden');
        empresaDiv.classList.remove('hidden');
        if (tabEmpresa) {
            tabEmpresa.classList.remove('inactive');
            tabEmpresa.classList.add('active');
        }
        if (tabAprendiz) {
            tabAprendiz.classList.remove('active');
            tabAprendiz.classList.add('inactive');
        }
        if (typeof window.initComboboxes === 'function') {
            window.initComboboxes(empresaDiv);
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const form = document.getElementById('form-nuevo-aprendiz');
    if (!form) return;

    const empresaHidden = document.getElementById('nuevo-aprendiz-empresa-id');
    const jefeSelect = document.getElementById('select-jefe-id');
    const readonlyPanel = document.getElementById('edit-empresa-readonly-panel');
    const readonlyNombre = document.getElementById('edit-empresa-readonly-nombre');
    const readonlyNit = document.getElementById('edit-empresa-readonly-nit');
    const readonlyDireccion = document.getElementById('edit-empresa-readonly-direccion');
    const jefeWrap = document.getElementById('edit-jefe-vinculo-wrap');
    const sinEmpresaAviso = document.getElementById('edit-sin-empresa-aviso');

    let empresasCatalog = [];
    try {
        empresasCatalog = JSON.parse(form.getAttribute('data-empresas-catalog') || '[]');
    } catch (e) {
        empresasCatalog = [];
    }

    const jefesUrl = form.getAttribute('data-jefes-url') || '';
    const initialEmpresaId = parseInt(form.getAttribute('data-initial-empresa-id') || '0', 10);
    let displayedEmpresaId = initialEmpresaId;

    function setReadonlyField(el, value) {
        if (!el) return;
        const t = (value || '').trim();
        if (t === '') {
            el.innerHTML = '<span class="italic text-gray-400">Dato no registrado</span>';
        } else {
            el.textContent = t;
        }
    }

    function findEmpresaInCatalog(empresaId) {
        const id = parseInt(String(empresaId), 10);
        if (!id) return null;
        for (let i = 0; i < empresasCatalog.length; i++) {
            if (parseInt(String(empresasCatalog[i].id), 10) === id) {
                return empresasCatalog[i];
            }
        }
        return null;
    }

    function updateEmpresaReadonlyPanel() {
        const id = displayedEmpresaId;
        const hasEmpresa = id > 0;
        const emp = hasEmpresa ? findEmpresaInCatalog(id) : null;

        if (readonlyPanel) readonlyPanel.classList.toggle('hidden', !hasEmpresa);
        if (jefeWrap) jefeWrap.classList.toggle('hidden', !hasEmpresa);
        if (sinEmpresaAviso) sinEmpresaAviso.classList.toggle('hidden', hasEmpresa);

        if (hasEmpresa && emp) {
            setReadonlyField(readonlyNombre, emp.nombre || '');
            setReadonlyField(readonlyNit, emp.nit || '');
            setReadonlyField(readonlyDireccion, emp.direccion || '');
        }
    }

    function rebuildJefeOptions(jefes, selectedId) {
        if (!jefeSelect) return;
        const keep = String(selectedId || '');
        jefeSelect.innerHTML = '';
        const emptyOpt = document.createElement('option');
        emptyOpt.value = '';
        emptyOpt.textContent = 'Sin supervisor asignado';
        jefeSelect.appendChild(emptyOpt);

        (jefes || []).forEach(function (row) {
            const opt = document.createElement('option');
            opt.value = String(row.id);
            opt.textContent = row.label || ('Supervisor #' + row.id);
            if (keep !== '' && keep === opt.value) {
                opt.selected = true;
            }
            jefeSelect.appendChild(opt);
        });
    }

    function loadJefesForEmpresa(empresaId, preferredJefeId) {
        const id = parseInt(String(empresaId), 10);
        if (!jefeSelect || id <= 0 || !jefesUrl) {
            return Promise.resolve();
        }

        return fetch(jefesUrl + '?empresa_id=' + encodeURIComponent(String(id)), {
            headers: { Accept: 'application/json' },
        })
            .then(function (res) {
                return res.json();
            })
            .then(function (data) {
                if (!data || !data.ok) return;
                rebuildJefeOptions(data.jefes || [], preferredJefeId);
            })
            .catch(function () {});
    }

    function onEmpresaVinculoSelected() {
        const empresaId = empresaHidden ? empresaHidden.value : '';
        const id = parseInt(String(empresaId), 10);
        if (!Number.isFinite(id) || id <= 0) {
            return;
        }

        displayedEmpresaId = id;
        updateEmpresaReadonlyPanel();
        const preferredJefe = form.getAttribute('data-initial-jefe-id') || '';
        form.removeAttribute('data-initial-jefe-id');
        loadJefesForEmpresa(id, preferredJefe);
    }

    if (empresaHidden) {
        empresaHidden.addEventListener('change', onEmpresaVinculoSelected);
    }

    updateEmpresaReadonlyPanel();
    if (initialEmpresaId > 0) {
        const preferredJefe = form.getAttribute('data-initial-jefe-id') || '';
        loadJefesForEmpresa(initialEmpresaId, preferredJefe);
    }
});
