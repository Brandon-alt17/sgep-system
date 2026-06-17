function resetEditAprendizForm() {
    const form = document.getElementById('form-editar-aprendiz');
    if (!form) return;

    form.reset();

    const comboboxRoot = form.querySelector('[data-combobox-root]');
    if (comboboxRoot) {
        const hidden = comboboxRoot.querySelector('[data-combobox-value]');
        const searchInput = comboboxRoot.querySelector('[data-combobox-input]');
        const value = hidden ? (hidden.value || '') : '';
        let selected = null;
        comboboxRoot.querySelectorAll('[data-combobox-option]').forEach(function (opt) {
            if (!selected && (opt.getAttribute('data-value') || '') === value) {
                selected = opt;
            }
        });
        if (searchInput) {
            searchInput.value = selected
                ? (selected.getAttribute('data-label') || selected.textContent || '')
                : '';
        }
    }

    if (typeof window.sgRestoreEditAprendizEmpresaDisplay === 'function') {
        window.sgRestoreEditAprendizEmpresaDisplay();
    }
}

function abrirModalEditar() {
    const modal = document.getElementById('modal-editar-aprendiz');
    if (!modal) return;

    resetEditAprendizForm();
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    cambiarTab('aprendiz');
}

function cerrarModal(event) {
    if (event) {
        event.preventDefault();
        event.stopPropagation();
    }

    resetEditAprendizForm();

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

        modalContainer.classList.remove('empresa-size');
        modalContainer.classList.add('aprendiz-size');
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

        modalContainer.classList.remove('aprendiz-size');
        modalContainer.classList.add('empresa-size');
        if (typeof window.initComboboxes === 'function') {
            window.initComboboxes(document.getElementById('contenido-empresa') || document);
        }
    }
}

document.addEventListener('DOMContentLoaded', function () {
    const modalContainer = document.getElementById('modal-container');
    if (modalContainer) {
        modalContainer.addEventListener('click', function (event) {
            event.stopPropagation();
        });
    }

    const form = document.getElementById('form-editar-aprendiz');
    if (!form) return;

    const empresaHidden = document.getElementById('edit-aprendiz-empresa-id');
    const jefeSelect = document.getElementById('select-jefe-id');
    const readonlyPanel = document.getElementById('edit-empresa-readonly-panel');
    const readonlyNombre = document.getElementById('edit-empresa-readonly-nombre');
    const readonlyNit = document.getElementById('edit-empresa-readonly-nit');
    const readonlyDireccion = document.getElementById('edit-empresa-readonly-direccion');
    const jefeWrap = document.getElementById('edit-jefe-vinculo-wrap');
    const sinEmpresaAviso = document.getElementById('edit-sin-empresa-aviso');
    const jefeSelectTemplate = jefeSelect ? jefeSelect.innerHTML : '';

    let empresasCatalog = [];
    try {
        empresasCatalog = JSON.parse(form.getAttribute('data-empresas-catalog') || '[]');
    } catch (e) {
        empresasCatalog = [];
    }

    const jefesUrl = form.getAttribute('data-jefes-url') || '';
    const initialEmpresaId = parseInt(form.getAttribute('data-initial-empresa-id') || '0', 10);
    const initialJefeId = form.getAttribute('data-initial-jefe-id') || '';
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
        } else if (hasEmpresa) {
            setReadonlyField(readonlyNombre, '');
            setReadonlyField(readonlyNit, '');
            setReadonlyField(readonlyDireccion, '');
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

        if (keep !== '' && !Array.prototype.some.call(jefeSelect.options, function (o) {
            return o.value === keep;
        })) {
            const legacy = document.createElement('option');
            legacy.value = keep;
            legacy.textContent = 'Supervisor actual (#' + keep + ')';
            legacy.selected = true;
            jefeSelect.appendChild(legacy);
        }
    }

    function loadJefesForEmpresa(empresaId, preferredJefeId) {
        const id = parseInt(String(empresaId), 10);
        if (!jefeSelect) return Promise.resolve();
        if (id <= 0) {
            return Promise.resolve();
        }

        if (!jefesUrl) {
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
                const selected = preferredJefeId != null ? preferredJefeId : jefeSelect.value;
                rebuildJefeOptions(data.jefes || [], selected);
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
        loadJefesForEmpresa(id, '');
    }

    window.sgRestoreEditAprendizEmpresaDisplay = function () {
        displayedEmpresaId = initialEmpresaId;
        updateEmpresaReadonlyPanel();
        if (jefeSelect && jefeSelectTemplate) {
            jefeSelect.innerHTML = jefeSelectTemplate;
        }
    };

    if (empresaHidden) {
        empresaHidden.addEventListener('change', onEmpresaVinculoSelected);
    }

    updateEmpresaReadonlyPanel();
});

function abrirModalVisitas() {
    const modal = document.getElementById('modal-visitas');
    if (!modal) return;

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function cerrarModalVisitas() {
    const modal = document.getElementById('modal-visitas');
    if (!modal) return;

    modal.classList.add('hidden');
    document.body.style.overflow = '';
}
