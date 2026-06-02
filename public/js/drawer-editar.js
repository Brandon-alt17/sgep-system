// js/drawer-editar.js
(function() {
    'use strict';
    let currentAprendizId = null;

    function init() {
        console.log('Inicializando popup...');
        setupEventListeners();
        setupRowClicks();
        loadAllTableData(); // Cargar todos los datos guardados en la tabla al iniciar
    }

    // Función para cargar todos los datos guardados en la tabla al iniciar
    function loadAllTableData() {
        console.log('Cargando datos guardados...');
        document.querySelectorAll('tr[data-id]').forEach(row => {
            const aprendizId = row.dataset.id;
            const saved = localStorage.getItem(`formulario_${aprendizId}`);
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    console.log('Cargando datos para:', aprendizId, data);
                    updateTableRow(row, data);
                } catch(e) {
                    console.error('Error loading data for row:', e);
                }
            }
        });
    }

    // Función para actualizar una fila específica de la tabla usando clases
    function updateTableRow(row, data) {
        console.log('Actualizando fila con datos:', data);
        
        // Mapeo de IDs del formulario a las clases CSS de las celdas
        const classMapping = {
            'doc_gfpi_165': 'doc-status-gfpi-165',
            'doc_momento_1': 'doc-status-momento-1',
            'doc_bitacora_1': 'doc-status-bitacora-1',
            'doc_bitacora_2': 'doc-status-bitacora-2',
            'doc_bitacora_3': 'doc-status-bitacora-3',
            'doc_bitacora_4': 'doc-status-bitacora-4',
            'doc_bitacora_5': 'doc-status-bitacora-5',
            'doc_bitacora_6': 'doc-status-bitacora-6',
            'doc_momento_final': 'doc-status-momento-final',
            'cert_doc_identidad': 'doc-status-cert-doc-identidad',
            'cert_paz_salvo': 'doc-status-cert-paz-salvo',
            'cert_gfpi_023': 'doc-status-cert-gfpi-023',
            'cert_bitacoras': 'doc-status-cert-bitacoras',
            'cert_cumplimiento': 'doc-status-cert-cumplimiento',
            'cert_ape': 'doc-status-cert-ape',
            'cert_carnet': 'doc-status-cert-carnet'
        };
        
        // Actualizar checkboxes (documentos)
        for (const [key, className] of Object.entries(classMapping)) {
            if (data[key] !== undefined) {
                const cell = row.querySelector('.' + className);
                if (cell) {
                    if (data[key]) {
                        cell.innerHTML = '✓';
                        cell.style.color = '#059669';
                        cell.style.fontWeight = 'bold';
                    } else {
                        cell.innerHTML = '—';
                        cell.style.color = '';
                        cell.style.fontWeight = '';
                    }
                    console.log(`Actualizado ${key} en celda .${className}:`, data[key]);
                } else {
                    console.warn(`No se encontró celda con clase .${className}`);
                }
            }
        }
        
        // Actualizar reingreso
        if (data.reingreso !== undefined) {
            const reingresoCell = row.querySelector('td:nth-child(32)'); // Columna 32
            if (reingresoCell) {
                reingresoCell.innerHTML = data.reingreso ? 'Sí' : 'No';
                console.log('Actualizado reingreso:', data.reingreso);
            }
        }
        
        // Actualizar cambio modalidad (columna 33)
        if (data.cambio_modalidad !== undefined) {
            const cambioCell = row.querySelector('td:nth-child(33)');
            if (cambioCell) {
                cambioCell.innerHTML = data.cambio_modalidad || '—';
                console.log('Actualizado cambio modalidad:', data.cambio_modalidad);
            }
        }
        
        // Actualizar observaciones novedad (columna 34)
        if (data.observaciones_novedad !== undefined) {
            const novedadCell = row.querySelector('td:nth-child(34)');
            if (novedadCell) {
                novedadCell.innerHTML = data.observaciones_novedad || '—';
                console.log('Actualizado observaciones novedad');
            }
        }
        
        // Actualizar fecha entrega (columna 51)
        if (data.fecha_entrega !== undefined) {
            const fechaCell = row.querySelector('td:nth-child(51)');
            if (fechaCell) {
                fechaCell.innerHTML = data.fecha_entrega || '';
                console.log('Actualizado fecha entrega:', data.fecha_entrega);
            }
        }
        
        // Actualizar estado aprendiz (columna 52)
        if (data.estado_aprendiz !== undefined) {
            const estadoCell = row.querySelector('td:nth-child(52)');
            if (estadoCell) {
                estadoCell.innerHTML = data.estado_aprendiz || '';
                console.log('Actualizado estado aprendiz:', data.estado_aprendiz);
            }
        }
        
        // Actualizar observaciones (columna 53)
        if (data.observaciones !== undefined) {
            const obsCell = row.querySelector('td:nth-child(53)');
            if (obsCell) {
                obsCell.innerHTML = data.observaciones || '—';
                console.log('Actualizado observaciones');
            }
        }
    }

    function setupEventListeners() {
        const closeBtn = document.getElementById('closePopupBtn');
        const cancelBtn = document.getElementById('cancelPopupBtn');
        const saveBtn = document.getElementById('saveDocsBtn');
        const overlay = document.getElementById('popupOverlay');
        
        if (closeBtn) {
            closeBtn.onclick = function(e) {
                e.preventDefault();
                closePopup();
            };
        }
        
        if (cancelBtn) {
            cancelBtn.onclick = function(e) {
                e.preventDefault();
                closePopup();
            };
        }
        
        if (saveBtn) {
            saveBtn.onclick = function(e) {
                e.preventDefault();
                saveData();
            };
        }
        
        if (overlay) {
            overlay.onclick = function(e) {
                if (e.target === overlay) {
                    closePopup();
                }
            };
        }

        const popup = document.getElementById('documentPopup');
        if (popup) {
            popup.addEventListener('click', function(e) {
                e.stopPropagation();
            });
        }
        
        document.onkeydown = function(e) {
            if (e.key === 'Escape') {
                const popup = document.getElementById('documentPopup');
                if (popup && popup.style.transform === 'translateX(0%)') {
                    closePopup();
                }
            }
        };
    }

    function setupRowClicks() {
        document.addEventListener('click', function(e) {
            if (e.target.closest('#documentPopup')) return;
            if (e.target.closest('#popupOverlay')) return;

            const row = e.target.closest('tr[data-id]');
            if (!row) return;

            if (e.target.closest('button') || e.target.closest('input') || 
                e.target.closest('textarea') || e.target.closest('select') || 
                e.target.closest('label') || e.target.closest('a')) {
                return;
            }

            const aprendizId = row.dataset.id;
            const aprendizNombre = row.dataset.nombre;

            if (!aprendizId) return;

            openPopup(aprendizId, aprendizNombre || 'Aprendiz');
        });
    }

    function openPopup(aprendizId, aprendizNombre) {
        const popup = document.getElementById('documentPopup');
        const overlay = document.getElementById('popupOverlay');
        const nombreSpan = document.getElementById('popupAprendizNombre');
        
        if (!popup || !overlay) return;
        
        currentAprendizId = aprendizId;
        if (nombreSpan) nombreSpan.textContent = aprendizNombre;
        
        loadSavedData(aprendizId);
        
        popup.style.transform = 'translateX(0%)';
        overlay.style.display = 'block';
        requestAnimationFrame(() => {
            overlay.style.opacity = '1';
            overlay.style.pointerEvents = 'auto';
        });
        document.body.style.overflow = 'hidden';
    }

    function closePopup() {
        const popup = document.getElementById('documentPopup');
        const overlay = document.getElementById('popupOverlay');
        
        if (!popup || !overlay) return;
        
        popup.style.transform = 'translateX(100%)';
        overlay.style.opacity = '0';
        overlay.style.pointerEvents = 'none';
        
        setTimeout(() => {
            overlay.style.display = 'none';
        }, 300);
        document.body.style.overflow = '';
    }

    function loadSavedData(aprendizId) {
        const saved = localStorage.getItem(`formulario_${aprendizId}`);
        if (saved) {
            try {
                const data = JSON.parse(saved);
                for (const [key, value] of Object.entries(data)) {
                    const element = document.getElementById(key);
                    if (element) {
                        if (element.type === 'checkbox') {
                            element.checked = value;
                            const toggleDiv = element.nextElementSibling; 
                            if (toggleDiv) {
                                if (value) {
                                    toggleDiv.style.backgroundColor = '#059669';
                                    toggleDiv.querySelector('div').style.transform = 'translateX(20px)';
                                } else {
                                    toggleDiv.style.backgroundColor = '#e5e7eb';
                                    toggleDiv.querySelector('div').style.transform = 'translateX(0px)';
                                }
                            }
                        } else {
                            element.value = value;
                        }
                    }
                }
            } catch(e) {
                console.error('Error loading data:', e);
                clearForm();
            }
        } else {
            clearForm();
        }
    }

    function clearForm() {
        document.querySelectorAll('#documentPopup .doc-checkbox').forEach(cb => {
            cb.checked = false;
            const toggleDiv = cb.nextElementSibling;
            if (toggleDiv) {
                toggleDiv.style.backgroundColor = '#e5e7eb';
                const innerDiv = toggleDiv.querySelector('div');
                if (innerDiv) innerDiv.style.transform = 'translateX(0px)';
            }
        });
        
        const ids = ['fecha_entrega', 'observaciones', 'observaciones_novedad'];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        
        const selects = ['estado_aprendiz', 'cambio_modalidad'];
        selects.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.selectedIndex = 0;
        });
        
        const reingreso = document.getElementById('reingreso');
        if (reingreso) reingreso.checked = false;
    }

    function saveData() {
        if (!currentAprendizId) {
            showNotification('Error: No hay aprendiz seleccionado', 'error');
            return;
        }
        
        const formData = {};
        
        // Guardar checkboxes
        document.querySelectorAll('#documentPopup .doc-checkbox').forEach(cb => {
            if (cb.id) {
                formData[cb.id] = cb.checked;
                console.log(`Guardando ${cb.id}: ${cb.checked}`);
            }
        });
        
        // Guardar inputs
        const ids = ['fecha_entrega', 'estado_aprendiz', 'observaciones', 'observaciones_novedad', 'cambio_modalidad'];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                formData[id] = el.value;
                console.log(`Guardando ${id}: ${el.value}`);
            }
        });
        
        // Guardar reingreso
        const reingreso = document.getElementById('reingreso');
        if (reingreso) {
            formData.reingreso = reingreso.checked;
            console.log(`Guardando reingreso: ${reingreso.checked}`);
        }
        
        // Guardar en localStorage
        localStorage.setItem(`formulario_${currentAprendizId}`, JSON.stringify(formData));
        console.log('Datos guardados en localStorage:', formData);
        
        // Actualizar la tabla inmediatamente
        const row = document.querySelector(`tr[data-id="${currentAprendizId}"]`);
        if (row) {
            console.log('Actualizando fila en la tabla...');
            updateTableRow(row, formData);
        } else {
            console.error('No se encontró la fila con data-id:', currentAprendizId);
        }
        
        showNotification('Datos guardados correctamente', 'success');
        closePopup();
    }

    function showNotification(message, type = 'success') {
        const notification = document.createElement('div');
        notification.textContent = message;
        notification.style.cssText = `
            position: fixed; bottom: 20px; right: 20px; padding: 12px 24px;
            background-color: ${type === 'success' ? '#059669' : '#dc2626'};
            color: white; border-radius: 8px; z-index: 10001; font-size: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1); transition: opacity 0.3s ease; opacity: 0;
            z-index: 10002;
        `;
        document.body.appendChild(notification);
        
        setTimeout(() => { notification.style.opacity = '1'; }, 10);
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 300);
        }, 2000);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();