// js/drawer-editar.js
(function() {
    'use strict';
    let currentAprendizId = null;
    let currentFechaFinPlataforma = '';
    let lastDrawerRow = null;

    /** Índice de columna (tbody) → id de sección del drawer */
    const DRAWER_SECTION_BY_COLUMN = [
        null, null, null, null, null, null, null,
        's_programa', 's_programa', 's_programa',
        null,
        's0', 's0', 's0', 's0',
        's_aprendiz', 's_aprendiz', 's_aprendiz', 's_aprendiz', 's_aprendiz', 's_aprendiz',
        's_etapa_info', 's_etapa_info', 's_etapa_info', 's_etapa_info', 's_etapa_info',
        's_etapa_info', 's_etapa_info', 's_etapa_info', 's_etapa_info', 's_etapa_info',
        's_etapa', 's_etapa', 's_etapa', 's_etapa', 's_etapa', 's_etapa',
        's1', 's1', 's1', 's1', 's1', 's1', 's1', 's1', 's1',
        's2', 's2', 's2', 's2', 's2', 's2', 's2', 's2',
        's2', 's2', 's2',
        null, null, null,
    ];

    function parseDmY(value) {
        const match = String(value || '').trim().match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (!match) return null;
        const day = parseInt(match[1], 10);
        const month = parseInt(match[2], 10) - 1;
        const year = parseInt(match[3], 10);
        const date = new Date(year, month, day);
        if (date.getFullYear() !== year || date.getMonth() !== month || date.getDate() !== day) {
            return null;
        }
        return date;
    }

    function formatDmY(date) {
        const day = String(date.getDate()).padStart(2, '0');
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const year = date.getFullYear();
        return day + '/' + month + '/' + year;
    }

    function formatIso(date) {
        const year = date.getFullYear();
        const month = String(date.getMonth() + 1).padStart(2, '0');
        const day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    function isoToDmY(iso) {
        const match = String(iso || '').trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) {
            return String(iso || '').trim();
        }
        return match[3] + '/' + match[2] + '/' + match[1];
    }

    function dmYToIso(value) {
        const match = String(value || '').trim().match(/^(\d{1,2})\/(\d{1,2})\/(\d{4})$/);
        if (!match) {
            return '';
        }
        const day = String(parseInt(match[1], 10)).padStart(2, '0');
        const month = String(parseInt(match[2], 10)).padStart(2, '0');
        const year = match[3];
        return year + '-' + month + '-' + day;
    }

    function addMonths(date, months) {
        const copy = new Date(date.getTime());
        copy.setMonth(copy.getMonth() + months);
        return copy;
    }

    function fechaToDmY(value) {
        const v = String(value || '').trim();
        if (/^\d{4}-\d{2}-\d{2}$/.test(v)) {
            return isoToDmY(v);
        }
        return v;
    }

    /**
     * @param {boolean} [recomputeVencimiento] Por defecto true (recalcula y sobreescribe el campo
     *   de vencimiento con la sugerencia automática — lo correcto cuando el usuario acaba de
     *   cambiar la fecha fin de plataforma). Pasar false al abrir el drawer, donde el vencimiento
     *   ya se cargó desde el valor efectivo guardado (manual o automático) y no debe pisarse.
     */
    function syncFinPlataformaFromInput(recomputeVencimiento) {
        const finInput = document.getElementById('fecha_fin_plataforma');
        if (finInput && finInput.value) {
            currentFechaFinPlataforma = isoToDmY(finInput.value);
        } else if (finInput && !finInput.value) {
            currentFechaFinPlataforma = '';
        }
        if (recomputeVencimiento === false) {
            return;
        }
        updateReglamentoPreview();
    }

    function setupProgramaListeners() {
        const finInput = document.getElementById('fecha_fin_plataforma');
        if (finInput) {
            finInput.addEventListener('change', syncFinPlataformaFromInput);
        }
    }

    function selectedReglamentoAcuerdo() {
        const selected = document.querySelector('input[name="reglamento_acuerdo"]:checked');
        return selected ? selected.value : '';
    }

    /** Semáforo a partir de una fecha de vencimiento ya resuelta (automática o editada a mano). */
    function semaforoFromDate(vencimientoDate) {
        const hoy = new Date();
        hoy.setHours(0, 0, 0, 0);
        const vencCompare = new Date(vencimientoDate.getTime());
        vencCompare.setHours(0, 0, 0, 0);
        if (vencCompare < hoy) {
            return '🔴 VENCIDO';
        }
        const diffMs = vencCompare.getTime() - hoy.getTime();
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));
        if (diffDays < 90) {
            return '🟠 PRÓXIMO';
        }
        return '🟢 VIGENTE';
    }

    function semaforoFromVencimientoIso(iso) {
        const match = String(iso || '').trim().match(/^(\d{4})-(\d{2})-(\d{2})$/);
        if (!match) return 'SIN FECHA';
        const date = new Date(parseInt(match[1], 10), parseInt(match[2], 10) - 1, parseInt(match[3], 10));
        if (isNaN(date.getTime())) return 'SIN FECHA';
        return semaforoFromDate(date);
    }

    /** Sugerencia automática (fecha fin de plataforma + meses del acuerdo) — el usuario puede sobreescribirla. */
    function computeReglamentoPreview(fechaFin, acuerdo) {
        if (!fechaFin || (acuerdo !== '007' && acuerdo !== '009')) {
            return { vencimientoIso: '', vencimiento: '', semaforo: 'SIN FECHA' };
        }
        const base = parseDmY(fechaFin);
        if (!base) {
            return { vencimientoIso: '', vencimiento: '', semaforo: 'SIN FECHA' };
        }
        const months = acuerdo === '007' ? 18 : 12;
        const vencimientoDate = addMonths(base, months);
        return {
            vencimientoIso: formatIso(vencimientoDate),
            vencimiento: formatDmY(vencimientoDate),
            semaforo: semaforoFromDate(vencimientoDate),
        };
    }

    /** Sugiere automáticamente el vencimiento (y su semáforo) al cambiar acuerdo/fecha fin de plataforma. */
    function updateReglamentoPreview() {
        const preview = computeReglamentoPreview(currentFechaFinPlataforma, selectedReglamentoAcuerdo());
        const vencimientoInput = document.getElementById('vencimiento_terminos');
        const semaforoInput = document.getElementById('semaforo_vencimiento_display');
        if (vencimientoInput) vencimientoInput.value = preview.vencimientoIso;
        if (semaforoInput) semaforoInput.value = preview.semaforo;
        return preview;
    }

    function setReglamentoRadios(acuerdo007, acuerdo009) {
        const value = acuerdo007 ? '007' : (acuerdo009 ? '009' : '');
        document.querySelectorAll('input[name="reglamento_acuerdo"]').forEach(function (radio) {
            radio.checked = radio.value === value;
        });
        updateReglamentoPreview();
    }

    function hydrateReglamentoFromRow(row) {
        if (!row) return;
        currentFechaFinPlataforma = row.dataset.fechaFinPlataforma || '';
        const ac007 = row.dataset.acuerdo007 === '1';
        const ac009 = row.dataset.acuerdo009 === '1';
        // Sugerencia automática como base (por si la fila aún no tiene nada calculado).
        setReglamentoRadios(ac007, ac009);

        // El servidor ya resolvió el vencimiento efectivo (automático o ajustado a mano) para esta
        // fila — se usa tal cual en vez de la sugerencia recién calculada en el navegador, para no
        // perder de vista un ajuste manual ya guardado.
        const vencimientoCell = row.querySelector('.vencimiento-terminos');
        const vencimientoInput = document.getElementById('vencimiento_terminos');
        if (vencimientoCell && vencimientoInput) {
            const iso = dmYToIso((vencimientoCell.textContent || '').trim());
            if (iso) {
                vencimientoInput.value = iso;
            }
        }
        const semaforoCell = row.querySelector('.semaforo-vencimiento');
        const semaforoInput = document.getElementById('semaforo_vencimiento_display');
        if (semaforoCell && semaforoInput) {
            const text = (semaforoCell.textContent || '').trim();
            if (text) {
                semaforoInput.value = text;
            }
        }
    }

    function setupReglamentoListeners() {
        document.querySelectorAll('input[name="reglamento_acuerdo"]').forEach(function (radio) {
            radio.addEventListener('change', updateReglamentoPreview);
        });
    }

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
        setupReglamentoListeners();
        setupProgramaListeners();
        setupEventListeners();
        setupRowClicks();
        loadAllTableData(); // Cargar todos los datos guardados en la tabla al iniciar
    }

    // Función para cargar todos los datos guardados en la tabla al iniciar
    function loadAllTableData() {
        document.querySelectorAll('tr[data-id]').forEach(row => {
            const aprendizId = row.dataset.id;
            const saved = localStorage.getItem(localStorageKeyForRow(row));
            if (saved) {
                try {
                    const data = JSON.parse(saved);
                    updateTableRow(row, data);
                } catch(e) {
                    console.error('Error loading data for row:', e);
                }
            }
        });
    }

    // Función para actualizar una fila específica de la tabla usando clases
    function updateTableRow(row, data) {
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
                        cell.textContent = '✓';
                        cell.style.color = '#059669';
                        cell.style.fontWeight = 'bold';
                    } else {
                        cell.textContent = '—';
                        cell.style.color = '';
                        cell.style.fontWeight = '';
                    }
                } else {
                    console.warn(`No se encontró celda con clase .${className}`);
                }
            }
        }
        
        if (data.acuerdo_007 !== undefined || data.acuerdo_009 !== undefined) {
            const ac007Cell = row.querySelector('.acuerdo-007');
            const ac009Cell = row.querySelector('.acuerdo-009');
            if (ac007Cell) ac007Cell.textContent = data.acuerdo_007 ? 'X' : '';
            if (ac009Cell) ac009Cell.textContent = data.acuerdo_009 ? 'X' : '';
        }

        const fechaFin = fechaToDmY(data.fecha_fin_plataforma || row.dataset.fechaFinPlataforma || '');
        const acuerdo = data.acuerdo_007 ? '007' : (data.acuerdo_009 ? '009' : selectedReglamentoAcuerdo());
        const reglamento = computeReglamentoPreview(fechaFin, acuerdo);
        const vencimientoCell = row.querySelector('.vencimiento-terminos');
        const semaforoCell = row.querySelector('.semaforo-vencimiento');
        if (vencimientoCell && (data.acuerdo_007 !== undefined || data.acuerdo_009 !== undefined || data.vencimiento_terminos !== undefined)) {
            vencimientoCell.textContent = data.vencimiento_terminos ? fechaToDmY(data.vencimiento_terminos) : (reglamento.vencimiento || '');
        }
        if (semaforoCell && (data.acuerdo_007 !== undefined || data.acuerdo_009 !== undefined || data.semaforo_vencimiento !== undefined)) {
            semaforoCell.textContent = data.semaforo_vencimiento || reglamento.semaforo || 'SIN FECHA';
        }

        // Novedades de la etapa productiva
        const novedadesFields = {
            estado_etapa: '.estado-etapa',
            llamados_atencion: '.llamados-atencion',
            otros_novedad: '.otros-novedad',
            comite_evaluacion: '.comite-evaluacion',
            reingreso_vencimiento: '.reingreso-vencimiento',
        };
        for (const [key, selector] of Object.entries(novedadesFields)) {
            if (data[key] !== undefined) {
                const cell = row.querySelector(selector);
                if (!cell) continue;
                const display = data[key] || '—';
                if (key === 'estado_etapa') {
                    cell.textContent = display === '—' ? '' : display;
                } else {
                    cell.textContent = display;
                }
            }
        }
        // Compatibilidad: reingreso booleano antiguo
        if (data.reingreso !== undefined && data.reingreso_vencimiento === undefined) {
            const reingresoCell = row.querySelector('.reingreso-vencimiento');
            if (reingresoCell) {
                reingresoCell.textContent = data.reingreso ? 'Sí' : '—';
            }
        }
        // Compatibilidad: cambio_modalidad → comité
        if (data.cambio_modalidad !== undefined && data.comite_evaluacion === undefined) {
            const comiteCell = row.querySelector('.comite-evaluacion');
            if (comiteCell && data.cambio_modalidad) {
                comiteCell.textContent = data.cambio_modalidad;
            }
        }
        
        // Actualizar observaciones novedad (solo campos del panel, no importación)
        const obsNovedad = data.observaciones_novedad ?? data.observaciones;
        if (obsNovedad !== undefined) {
            const novedadCell = row.querySelector('.obs-novedad');
            if (novedadCell) {
                novedadCell.textContent = obsNovedad || '—';
            }
        }
        
        // Fechas de plataforma / etapa productiva (programa)
        const programaDates = {
            fecha_inicio_plataforma: '.fecha-inicio-plataforma',
            inicio_etapa_productiva: '.inicio-etapa-productiva',
            fecha_fin_plataforma: '.fecha-fin-plataforma',
        };
        for (const [key, selector] of Object.entries(programaDates)) {
            if (data[key] !== undefined) {
                const cell = row.querySelector(selector);
                if (!cell) continue;
                const display = data[key] ? fechaToDmY(data[key]) : '';
                cell.textContent = display;
                if (key === 'fecha_fin_plataforma') {
                    row.dataset.fechaFinPlataforma = display;
                }
            }
        }

        // Fechas de inicio/fin de la etapa productiva
        const etapaDates = {
            fecha_inicio_etapa: '.fecha-inicio-etapa',
            fecha_fin_etapa: '.fecha-fin-etapa',
        };
        for (const [key, selector] of Object.entries(etapaDates)) {
            if (data[key] !== undefined) {
                const cell = row.querySelector(selector);
                if (!cell) continue;
                cell.textContent = data[key] ? fechaToDmY(data[key]) : '';
            }
        }

        // Actualizar fecha aval modalidad
        if (data.fecha_aval_modalidad !== undefined) {
            const avalCell = row.querySelector('.fecha-aval-modalidad');
            if (avalCell) {
                avalCell.textContent = data.fecha_aval_modalidad ? isoToDmY(data.fecha_aval_modalidad) : '';
            }
        }

        // Actualizar fecha entrega - usar clase CSS
        if (data.fecha_entrega !== undefined) {
            const fechaCell = row.querySelector('.fecha-entrega');
            if (fechaCell) {
                fechaCell.textContent = data.fecha_entrega || '';
            }
        }
        
        // Actualizar estado aprendiz - usar clase CSS
        if (data.estado_aprendiz !== undefined) {
            const estadoCell = row.querySelector('.estado-aprendiz');
            if (estadoCell) {
                estadoCell.textContent = data.estado_aprendiz || '';
            }
        }
        
        // Actualizar observaciones - usar clase CSS
        if (data.observaciones !== undefined) {
            const obsCell = row.querySelector('.observaciones');
            if (obsCell) {
                obsCell.textContent = data.observaciones || '—';
            }
        }
    }

    function resolveDrawerSectionFromCell(cell) {
        if (!cell) {
            return 's0';
        }
        if (cell.dataset && cell.dataset.drawerSection) {
            return cell.dataset.drawerSection;
        }
        if (cell.tagName === 'TD') {
            const byIndex = DRAWER_SECTION_BY_COLUMN[cell.cellIndex];
            if (byIndex) {
                return byIndex;
            }
            const className = cell.className || '';
            if (/fecha-inicio-plataforma|inicio-etapa-productiva|fecha-fin-plataforma/.test(className)) {
                return 's_programa';
            }
            if (/acuerdo-007|acuerdo-009|vencimiento-terminos|semaforo-vencimiento/.test(className)) {
                return 's0';
            }
            if (/fecha-aval-modalidad/.test(className)) {
                return 's_aprendiz';
            }
            if (/estado-etapa|llamados-atencion|otros-novedad|comite-evaluacion|reingreso-vencimiento|obs-novedad/.test(className)) {
                return 's_etapa';
            }
            if (/doc-status-gfpi-165|doc-status-momento-|doc-status-bitacora-/.test(className)) {
                return 's1';
            }
            if (/doc-status-cert-|fecha-entrega|estado-aprendiz|observaciones/.test(className)) {
                return 's2';
            }
        }
        return 's0';
    }

    function resolveDrawerRowFromClick(target) {
        const row = target.closest('tr[data-id]');
        if (row) {
            return row;
        }
        if (lastDrawerRow) {
            return lastDrawerRow;
        }
        return document.querySelector('tr[data-id]');
    }

    function resetDrawerSectionsExpanded() {
        document.querySelectorAll('#documentPopup .section-content').forEach(function (content) {
            content.style.display = 'block';
            const icon = document.querySelector('.section-icon[data-section="' + content.id + '"]');
            if (icon) {
                icon.textContent = '⌄';
            }
        });
        const scrollContainer = document.querySelector('#documentPopup .flex-1.overflow-y-auto');
        if (scrollContainer) {
            scrollContainer.scrollTop = 0;
        }
    }

    function focusDrawerSection(sectionId) {
        const scrollContainer = document.querySelector('#documentPopup .flex-1.overflow-y-auto');
        if (!scrollContainer) {
            return;
        }

        document.querySelectorAll('#documentPopup .section-content').forEach(function (content) {
            content.style.display = 'block';
            const icon = document.querySelector('.section-icon[data-section="' + content.id + '"]');
            if (icon) {
                icon.textContent = '⌄';
            }
        });

        const toggle = document.querySelector('.section-toggle[data-section="' + sectionId + '"]');
        const block = toggle ? toggle.closest('.border') : document.getElementById(sectionId);
        if (!block) {
            return;
        }

        requestAnimationFrame(function () {
            const blockTop = block.getBoundingClientRect().top;
            const containerTop = scrollContainer.getBoundingClientRect().top;
            scrollContainer.scrollTop += blockTop - containerTop - 8;
        });
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
        const table = document.querySelector('[data-reporte-table]');
        if (!table) {
            return;
        }

        table.addEventListener('click', function(e) {
            if (e.target.closest('#documentPopup')) return;
            if (e.target.closest('#popupOverlay')) return;

            const headerTh = e.target.closest('thead th');
            if (headerTh) {
                const sectionId = headerTh.dataset.drawerSection
                    || DRAWER_SECTION_BY_COLUMN[headerTh.cellIndex];
                if (!sectionId) {
                    return;
                }
                const row = resolveDrawerRowFromClick(e.target);
                if (!row) {
                    return;
                }
                const aprendizId = row.dataset.id;
                const aprendizNombre = row.dataset.nombre;
                if (!aprendizId) {
                    return;
                }
                openPopup(aprendizId, aprendizNombre || 'Aprendiz', sectionId);
                return;
            }

            const row = e.target.closest('tr[data-id]');
            if (!row) return;

            if (e.target.closest('button') || e.target.closest('input') ||
                e.target.closest('textarea') || e.target.closest('select') ||
                e.target.closest('label') || e.target.closest('a')) {
                return;
            }

            lastDrawerRow = row;
            const aprendizId = row.dataset.id;
            const aprendizNombre = row.dataset.nombre;
            if (!aprendizId) return;

            const cell = e.target.closest('td, th');
            const sectionId = resolveDrawerSectionFromCell(cell);
            openPopup(aprendizId, aprendizNombre || 'Aprendiz', sectionId);
        });
    }

    function openPopup(aprendizId, aprendizNombre, sectionId) {
        sectionId = sectionId || 's0';
        const popup = document.getElementById('documentPopup');
        const overlay = document.getElementById('popupOverlay');
        const nombreSpan = document.getElementById('popupAprendizNombre');
        
        if (!popup || !overlay) return;
        
        currentAprendizId = aprendizId;
        if (nombreSpan) nombreSpan.textContent = aprendizNombre;

        const row = document.querySelector(`tr[data-id="${aprendizId}"]`);
        if (row) {
            hydrateReglamentoFromRow(row);
        }
        
        const hadSaved = !!localStorage.getItem(`formulario_${aprendizId}`);
        loadSavedData(aprendizId);
        if (!hadSaved && row) {
            hydrateReglamentoFromRow(row);
            hydrateNovedadesFromRow(row);
            hydrateAprendizFromRow(row);
            hydrateProgramaFromRow(row);
            hydrateEtapaFechasFromRow(row);
        }
        // false: solo sincroniza currentFechaFinPlataforma para futuros cálculos en vivo — no debe
        // pisar el vencimiento ya cargado arriba desde el valor efectivo (manual o automático).
        syncFinPlataformaFromInput(false);

        popup.style.transform = 'translateX(0%)';
        overlay.style.display = 'block';
        requestAnimationFrame(() => {
            overlay.style.opacity = '1';
            overlay.style.pointerEvents = 'auto';
        });
        document.body.style.overflow = 'hidden';

        setTimeout(function () {
            focusDrawerSection(sectionId);
        }, 320);
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
            resetDrawerSectionsExpanded();
        }, 300);
        document.body.style.overflow = '';
    }

    function loadSavedData(aprendizId) {
        const saved = localStorage.getItem(`formulario_${aprendizId}`);
        if (saved) {
            try {
                const data = migrateLegacyNovedades(JSON.parse(saved));
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
                        } else if (element.type === 'date' && value) {
                            element.value = /^\d{4}-\d{2}-\d{2}$/.test(String(value)) ? value : dmYToIso(value);
                        } else {
                            element.value = value;
                        }
                    }
                }
                if (data.acuerdo_007 !== undefined || data.acuerdo_009 !== undefined) {
                    setReglamentoRadios(!!data.acuerdo_007, !!data.acuerdo_009);
                } else if (data.reglamento_acuerdo !== undefined) {
                    document.querySelectorAll('input[name="reglamento_acuerdo"]').forEach(function (radio) {
                        radio.checked = radio.value === String(data.reglamento_acuerdo || '');
                    });
                    updateReglamentoPreview();
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
        
        const ids = ['fecha_entrega', 'observaciones', 'observaciones_novedad', 'estado_etapa', 'llamados_atencion', 'otros_novedad', 'comite_evaluacion', 'reingreso_vencimiento', 'fecha_aval_modalidad', 'fecha_inicio_plataforma', 'inicio_etapa_productiva', 'fecha_fin_plataforma', 'fecha_inicio_etapa', 'fecha_fin_etapa', 'vencimiento_terminos'];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.value = '';
        });
        const semaforoInput = document.getElementById('semaforo_vencimiento_display');
        if (semaforoInput) semaforoInput.value = '';
        
        const selects = ['estado_aprendiz'];
        selects.forEach(id => {
            const el = document.getElementById(id);
            if (el) el.selectedIndex = 0;
        });

        document.querySelectorAll('input[name="reglamento_acuerdo"]').forEach(function (radio) {
            radio.checked = radio.value === '';
        });
        
        updateReglamentoPreview();
    }

    function hydrateProgramaFromRow(row) {
        if (!row) return;
        const map = {
            fecha_inicio_plataforma: '.fecha-inicio-plataforma',
            inicio_etapa_productiva: '.inicio-etapa-productiva',
            fecha_fin_plataforma: '.fecha-fin-plataforma',
        };
        for (const [fieldId, selector] of Object.entries(map)) {
            const cell = row.querySelector(selector);
            const el = document.getElementById(fieldId);
            if (!cell || !el) continue;
            el.value = dmYToIso((cell.textContent || '').trim());
        }
    }

    function hydrateAprendizFromRow(row) {
        if (!row) return;
        const avalCell = row.querySelector('.fecha-aval-modalidad');
        const avalInput = document.getElementById('fecha_aval_modalidad');
        if (avalCell && avalInput) {
            avalInput.value = dmYToIso((avalCell.textContent || '').trim());
        }

        const estadoCell = row.querySelector('.estado-aprendiz');
        const estadoSelect = document.getElementById('estado_aprendiz');
        if (estadoCell && estadoSelect) {
            const value = (estadoCell.textContent || '').trim();
            if (value !== '' && value !== '—') {
                const options = Array.from(estadoSelect.options);
                const match = options.find(function (opt) {
                    return opt.value === value || opt.textContent === value;
                });
                if (match) {
                    estadoSelect.value = match.value;
                }
            }
        }
    }

    function hydrateEtapaFechasFromRow(row) {
        if (!row) return;
        const map = {
            fecha_inicio_etapa: '.fecha-inicio-etapa',
            fecha_fin_etapa: '.fecha-fin-etapa',
        };
        for (const [fieldId, selector] of Object.entries(map)) {
            const cell = row.querySelector(selector);
            const el = document.getElementById(fieldId);
            if (!cell || !el) continue;
            el.value = dmYToIso((cell.textContent || '').trim());
        }
    }

    function hydrateNovedadesFromRow(row) {
        if (!row) return;
        const map = {
            estado_etapa: '.estado-etapa',
            llamados_atencion: '.llamados-atencion',
            otros_novedad: '.otros-novedad',
            comite_evaluacion: '.comite-evaluacion',
            reingreso_vencimiento: '.reingreso-vencimiento',
            observaciones_novedad: '.obs-novedad',
        };
        for (const [fieldId, selector] of Object.entries(map)) {
            const cell = row.querySelector(selector);
            const el = document.getElementById(fieldId);
            if (!cell || !el) continue;
            let value = (cell.textContent || '').trim();
            if (value === '—') value = '';
            el.value = value;
        }
    }

    function migrateLegacyNovedades(data) {
        if (data.reingreso === true && !data.reingreso_vencimiento) {
            data.reingreso_vencimiento = 'Sí';
        }
        if (data.cambio_modalidad && !data.comite_evaluacion) {
            const legacy = String(data.cambio_modalidad).trim();
            if (legacy !== '' && legacy !== 'Ninguno') {
                data.comite_evaluacion = legacy;
            }
        }
        return data;
    }

    function collectRowsFromLocalStorage() {
        const rows = [];
        document.querySelectorAll('tr[data-id]').forEach(function (row) {
            const aprendizId = parseInt(row.dataset.id || '0', 10);
            if (!aprendizId) return;
            const saved = localStorage.getItem(localStorageKeyForRow(row));
            if (!saved) return;
            try {
                rows.push({ aprendiz_id: aprendizId, campos: migrateLegacyNovedades(JSON.parse(saved)) });
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
            }
        });
        
        // Guardar inputs y selects de novedades
        const ids = [
            'fecha_entrega', 'estado_aprendiz', 'observaciones', 'observaciones_novedad',
            'estado_etapa', 'llamados_atencion', 'otros_novedad', 'comite_evaluacion', 'reingreso_vencimiento',
            'fecha_aval_modalidad', 'fecha_inicio_plataforma', 'inicio_etapa_productiva', 'fecha_fin_plataforma',
            'fecha_inicio_etapa', 'fecha_fin_etapa', 'vencimiento_terminos',
        ];
        ids.forEach(id => {
            const el = document.getElementById(id);
            if (el) {
                formData[id] = el.value;
            }
        });

        const acuerdoSeleccionado = selectedReglamentoAcuerdo();
        formData.reglamento_acuerdo = acuerdoSeleccionado;
        formData.acuerdo_007 = acuerdoSeleccionado === '007';
        formData.acuerdo_009 = acuerdoSeleccionado === '009';
        // El vencimiento ya quedó en formData.vencimiento_terminos (arriba, vía el ids.forEach) con
        // lo que esté en el campo AHORA MISMO — la sugerencia automática si el usuario no la tocó,
        // o su valor editado a mano si sí. El semáforo se deriva de ese valor final, sea cual sea.
        formData.semaforo_vencimiento = semaforoFromVencimientoIso(formData.vencimiento_terminos);
        const semaforoInput = document.getElementById('semaforo_vencimiento_display');
        if (semaforoInput) semaforoInput.value = formData.semaforo_vencimiento;
        
        localStorage.setItem(`formulario_${currentAprendizId}`, JSON.stringify(formData));

        const row = document.querySelector(`tr[data-id="${currentAprendizId}"]`);
        if (row) {
            updateTableRow(row, formData);
        }

        const aprendizId = parseInt(currentAprendizId, 10);
        const syncPayload = Object.assign({}, formData);
        delete syncPayload.vencimiento_terminos;
        delete syncPayload.semaforo_vencimiento;
        syncReporteCamposToServer([{ aprendiz_id: aprendizId, campos: syncPayload }])
            .then(function () {
                showNotification('Datos guardados correctamente', 'success');
                if (formData.estado_aprendiz === 'Finalizado' && !document.querySelector('input[name="mostrar_finalizados"]')?.checked) {
                    if (row) {
                        row.remove();
                    }
                }
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