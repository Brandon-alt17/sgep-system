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
            overlay.onclick = function() {
                closePopup();
            };
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
        const table = document.querySelector('table');
        if (!table) return;
        
        table.onclick = function(e) {
            const popup = document.getElementById('documentPopup');
            if (popup && popup.style.transform === 'translateX(0%)') {
                return;
            }
            
            const row = e.target.closest('tr');
            if (!row) return;
            
            if (row.closest('thead')) return;
            
            if (e.target.closest('button') || e.target.closest('input') || 
                e.target.closest('textarea') || e.target.closest('select')) {
                return;
            }
            
            const aprendizId = row.getAttribute('data-id');
            const aprendizNombre = row.getAttribute('data-nombre');
            
            if (aprendizId) {
                e.preventDefault();
                openPopup(aprendizId, aprendizNombre || 'Aprendiz');
            }
        };
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
        document.body.style.overflow = 'hidden';
    }
    
    function closePopup() {
        const popup = document.getElementById('documentPopup');
        const overlay = document.getElementById('popupOverlay');
        
        if (!popup || !overlay) return;
        
        popup.style.transform = 'translateX(100%)';
        overlay.style.display = 'none';
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
                            // Actualizar visual del checkbox
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
        
        const fechaEntrega = document.getElementById('fecha_entrega');
        if (fechaEntrega) fechaEntrega.value = '';
        
        const observaciones = document.getElementById('observaciones');
        if (observaciones) observaciones.value = '';
        
        const observacionesNovedad = document.getElementById('observaciones_novedad');
        if (observacionesNovedad) observacionesNovedad.value = '';
        
        const estadoAprendiz = document.getElementById('estado_aprendiz');
        if (estadoAprendiz) estadoAprendiz.selectedIndex = 0;
        
        const cambioModalidad = document.getElementById('cambio_modalidad');
        if (cambioModalidad) cambioModalidad.selectedIndex = 0;
        
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
        
        const fechaEntrega = document.getElementById('fecha_entrega');
        if (fechaEntrega) formData.fecha_entrega = fechaEntrega.value;
        
        const estadoAprendiz = document.getElementById('estado_aprendiz');
        if (estadoAprendiz) formData.estado_aprendiz = estadoAprendiz.value;
        
        const observaciones = document.getElementById('observaciones');
        if (observaciones) formData.observaciones = observaciones.value;
        
        const observacionesNovedad = document.getElementById('observaciones_novedad');
        if (observacionesNovedad) formData.observaciones_novedad = observacionesNovedad.value;
        
        const cambioModalidad = document.getElementById('cambio_modalidad');
        if (cambioModalidad) formData.cambio_modalidad = cambioModalidad.value;
        
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
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 12px 24px;
            background-color: ${type === 'success' ? '#059669' : '#dc2626'};
            color: white;
            border-radius: 8px;
            z-index: 10001;
            font-size: 14px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        `;
        document.body.appendChild(notification);
        
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