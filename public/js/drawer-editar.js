// js/drawer-editar.js
(function() {
    'use strict';
    let currentAprendizId = null;

    function init() {
        console.log('Inicializando popup...');
        setupEventListeners();
        setupRowClicks();
    }

    function setupEventListeners() {
        const closeBtn = document.getElementById('closePopupBtn');
        const cancelBtn = document.getElementById('cancelPopupBtn');
        const saveBtn = document.getElementById('saveDocsBtn'); // Corregido ID
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
                // Solo cierra si el click es directamente en el overlay (fondo oscuro)
                if (e.target === overlay) {
                    closePopup();
                }
            };
        }

        const popup = document.getElementById('documentPopup');

        if (popup) {
            // Evita que los clicks dentro del popup lleguen al overlay o a la tabla
            popup.addEventListener('click', function(e) {
                e.stopPropagation();
            });
            // Eliminado stopPropagation en 'wheel' para permitir el scroll interno
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
            // Ignorar popup
            if (e.target.closest('#documentPopup')) return;
            // Ignorar overlay
            if (e.target.closest('#popupOverlay')) return;

            // Buscar fila
            const row = e.target.closest('tr[data-id]');
            if (!row) return;

            // Ignorar elementos interactivos
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
        
        // Mostrar popup y overlay con transición
        popup.style.transform = 'translateX(0%)';
        overlay.style.display = 'block';
        // Forzar reflow para que la transición de opacidad funcione
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
        }, 300); // Coincide con la duración de la transición
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
        
        document.querySelectorAll('#documentPopup .doc-checkbox').forEach(cb => {
            if (cb.id) formData[cb.id] = cb.checked;
        });
        
        const ids = ['fecha_entrega', 'estado_aprendiz', 'observaciones', 'observaciones_novedad', 'cambio_modalidad'];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) formData[id] = el.value;
        });
        
        const reingreso = document.getElementById('reingreso');
        if (reingreso) formData.reingreso = reingreso.checked;
        
        localStorage.setItem(`formulario_${currentAprendizId}`, JSON.stringify(formData));
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