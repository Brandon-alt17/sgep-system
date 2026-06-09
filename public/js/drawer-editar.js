// js/drawer-editar.js
(function() {
    'use strict';
    let currentAprendizId = null;

    function localStorageKeyForRow(row) {
        const id = row.dataset.id || '';
        const currentKey = 'formulario_' + id;
        const legacyId = row.dataset.identificacion || '';
        const legacyKey = legacyId ? 'formulario_' + legacyId : '';
        if (legacyKey && legacyKey !== currentKey) {
            const legacyData = localStorage.getItem(legacyKey);
            if (legacyData && !localStorage.getItem(currentKey)) {
                localStorage.setItem(currentKey, legacyData);
                localStorage.removeItem(legacyKey);
            }
        }
        return currentKey;
    }

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
            const saved = localStorage.getItem(localStorageKeyForRow(row));
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
            'cert_carnet': 'doc-status-cert-carnet',
            'cert_saber_tyt': 'doc-status-cert-saber-tyt'
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
        
        // Actualizar reingreso - buscar por clase o posición relativa
        if (data.reingreso !== undefined) {
            // Buscar la celda que contiene el texto actual de reingreso
            const cells = row.querySelectorAll('td');
            // La columna de reingreso está después de "Estado etapa" (que tiene clase estado-etapa o similar)
            // Alternativa: buscar por el texto actual o usar un data attribute
            let reingresoCell = null;
            for (let i = 0; i < cells.length; i++) {
                // Buscar una celda que normalmente contiene 'Sí' o 'No' y está cerca de Estado etapa
                if (cells[i].previousElementSibling && 
                    cells[i].previousElementSibling.textContent.includes('En ejecución') ||
                    cells[i].previousElementSibling && 
                    cells[i].previousElementSibling.textContent.includes('Finalizado')) {
                    reingresoCell = cells[i];
                    break;
                }
            }
            // Como fallback, usa índice aproximado (ajusta según tu tabla)
            if (!reingresoCell && cells[31]) reingresoCell = cells[31];
            
            if (reingresoCell) {
                reingresoCell.innerHTML = data.reingreso ? 'Sí' : 'No';
                console.log('Actualizado reingreso:', data.reingreso);
            }
        }
        
        // Actualizar cambio modalidad - usar clase específica
        if (data.cambio_modalidad !== undefined) {
            // Buscar por clase si existe, o por índice
            let cambioCell = row.querySelector('td:nth-child(33)'); // Ajusta este índice
            if (cambioCell) {
                cambioCell.innerHTML = data.cambio_modalidad || '—';
                console.log('Actualizado cambio modalidad:', data.cambio_modalidad);
            }
        }
        
        // Actualizar observaciones novedad
        if (data.observaciones_novedad !== undefined) {
            let novedadCell = row.querySelector('td:nth-child(34)'); // Ajusta este índice
            if (novedadCell) {
                novedadCell.innerHTML = data.observaciones_novedad || '—';
                console.log('Actualizado observaciones novedad');
            }
        }
        
        // Actualizar fecha entrega - usar clase CSS
        if (data.fecha_entrega !== undefined) {
            const fechaCell = row.querySelector('.fecha-entrega');
            if (fechaCell) {
                fechaCell.innerHTML = data.fecha_entrega || '';
                console.log('Actualizado fecha entrega:', data.fecha_entrega);
            }
        }
        
        // Actualizar estado aprendiz - usar clase CSS
        if (data.estado_aprendiz !== undefined) {
            const estadoCell = row.querySelector('.estado-aprendiz');
            if (estadoCell) {
                estadoCell.innerHTML = data.estado_aprendiz || '';
                console.log('Actualizado estado aprendiz:', data.estado_aprendiz);
            }
        }
        
        // Actualizar observaciones - usar clase CSS
        if (data.observaciones !== undefined) {
            const obsCell = row.querySelector('.observaciones');
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
                            // Solo cambiar si el valor es diferente para evitar bucles
                            if (element.checked !== value) {
                                element.checked = value;
                                // Actualizar visual del switch
                                const toggleDiv = element.nextElementSibling;
                                if (toggleDiv) {
                                    if (value) {
                                        toggleDiv.style.backgroundColor = '#059669';
                                        const innerDiv = toggleDiv.querySelector('div');
                                        if (innerDiv) innerDiv.style.transform = 'translateX(20px)';
                                    } else {
                                        toggleDiv.style.backgroundColor = '#e5e7eb';
                                        const innerDiv = toggleDiv.querySelector('div');
                                        if (innerDiv) innerDiv.style.transform = 'translateX(0px)';
                                    }
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

    function collectRowsFromLocalStorage() {
        const rows = [];
        document.querySelectorAll('tr[data-id]').forEach(function (row) {
            const aprendizId = parseInt(row.dataset.id || '0', 10);
            if (!aprendizId) return;
            const saved = localStorage.getItem(localStorageKeyForRow(row));
            if (!saved) return;
            try {
                rows.push({ aprendiz_id: aprendizId, campos: JSON.parse(saved) });
            } catch (e) {
                console.error('Error leyendo localStorage para aprendiz', aprendizId, e);
            }
        });
        return rows;
    }

    function syncReporteCamposToServer(rows) {
        const payload = rows || collectRowsFromLocalStorage();
        if (!payload.length) {
            return Promise.resolve();
        }
        const base = window.APP_BASE_PATH || '';
        return fetch(base + '/reportes/sync', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
            },
            body: JSON.stringify({ rows: payload }),
        }).then(function (response) {
            return response.json().catch(function () {
                return { ok: false };
            }).then(function (data) {
                if (!response.ok || !data.ok) {
                    throw new Error(data.message || 'No se pudieron sincronizar los datos del reporte.');
                }
                return data;
            });
        });
    }

    window.prepareExportData = function () {
        const rows = collectRowsFromLocalStorage();
        rows.forEach(function (entry) {
            const row = document.querySelector('tr[data-id="' + entry.aprendiz_id + '"]');
            if (row) {
                updateTableRow(row, entry.campos);
            }
        });
        return syncReporteCamposToServer(rows);
    };

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
        
        localStorage.setItem(`formulario_${currentAprendizId}`, JSON.stringify(formData));

        const row = document.querySelector(`tr[data-id="${currentAprendizId}"]`);
        if (row) {
            updateTableRow(row, formData);
        }

        const aprendizId = parseInt(currentAprendizId, 10);
        syncReporteCamposToServer([{ aprendiz_id: aprendizId, campos: formData }])
            .then(function () {
                showNotification('Datos guardados correctamente', 'success');
                closePopup();
            })
            .catch(function (err) {
                console.error(err);
                showNotification('Guardado local OK, pero falló la sincronización con el servidor.', 'error');
            });
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