<style>
    #modal-visitas {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.5);
        z-index: 99999;
        align-items: center;
        justify-content: center;
    }

    #modal-visitas.hidden {
        display: none !important;
    }

    #modal-visitas:not(.hidden) {
        display: flex !important;
    }

    /* Overlay */
    #modal-overlay-visitas {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        z-index: 99998;
        cursor: pointer;
    }

    /* Contenedor */
    .modal-container {
        position: relative;
        background: white;
        border-radius: 16px;
        box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04);
        z-index: 99999;

        display: flex;
        flex-direction: column;
    }

    /* Tamaño */
    .modal-container.aprendiz-size {
        max-width: 720px;
        width: 90%;
        max-height: 85vh;
    }

    /* BODY SCROLL */
    .modal-body {
        overflow-y: auto;
        max-height: calc(85vh - 130px);
        padding-right: 6px;
    }

    /* Scroll bonito */
    .modal-body::-webkit-scrollbar {
        width: 6px;
    }

    .modal-body::-webkit-scrollbar-thumb {
        background: #d1d5db;
        border-radius: 10px;
    }

    /* Cards */
    .visita-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 1rem;
        background: #f9fafb;
    }

    /* Badges */
    .badge {
        font-size: 0.75rem;
        padding: 2px 8px;
        border-radius: 999px;
        font-weight: 500;
    }

    .badge-success {
        background: #d1fae5;
        color: #065f46;
    }

    .badge-warning {
        background: #fef3c7;
        color: #92400e;
    }

    /* Responsive */
    @media (max-width: 640px) {
        .modal-container.aprendiz-size {
            width: 95%;
        }
    }
</style>
<div id="modal-visitas" class="hidden">
    
    <div id="modal-overlay-visitas" onclick="cerrarModalVisitas()"></div>

    <div class="modal-container aprendiz-size">

        <!-- HEADER -->
        <div class="p-6 pb-2 border-b">
            <div class="flex justify-between items-start">
                <div>
                    <h2 class="flex items-center gap-2 text-lg font-semibold text-gray-900">
                        <span class="w-4 h-4 [&_svg]:w-4 [&_svg]:h-4">
                            <?= ui_icon('calendar') ?>
                        </span>
                        Programar visitas
                    </h2>
                    <p class="text-sm text-gray-500 mt-1">
                        Gestiona las tres visitas principales del seguimiento.
                    </p>
                </div>

                <button onclick="cerrarModalVisitas()" class="text-gray-400 hover:text-gray-600 text-2xl">
                    &times;
                </button>
            </div>
        </div>

        <!-- BODY -->
        <div class="modal-body p-6 pt-4">

            <form class="space-y-4">

                <!-- MOMENTO 1 -->
                <div class="visita-card">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-700">
                            Momento 1
                            <span class="badge badge-success ml-2">Completada</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">

                        <div>
                            <label class="form-label">Fecha</label>
                            <input type="date" class="form-input">
                        </div>

                        <div>
                            <label class="form-label">Modalidad</label>
                            <select class="form-input">
                                <option>Presencial</option>
                                <option>Virtual</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2 mt-6">
                            <input type="checkbox" checked>
                            <label class="text-sm text-gray-600">
                                Marcar como completada
                            </label>
                        </div>

                    </div>
                </div>

                <!-- MOMENTO 2 -->
                <div class="visita-card">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-700">
                            Momento 2
                            <span class="badge badge-warning ml-2">Vencida</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">

                        <div>
                            <label class="form-label">Fecha</label>
                            <input type="date" class="form-input">
                        </div>

                        <div>
                            <label class="form-label">Modalidad</label>
                            <select class="form-input">
                                <option>Presencial</option>
                                <option>Virtual</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2 mt-6">
                            <input type="checkbox">
                            <label class="text-sm text-gray-600">
                                Marcar como completada
                            </label>
                        </div>

                    </div>
                </div>

                <!-- MOMENTO 3 -->
                <div class="visita-card">
                    <div class="flex items-center justify-between mb-3">
                        <span class="text-sm font-medium text-gray-700">
                            Momento 3
                            <span class="badge badge-warning ml-2">Vencida</span>
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3 items-end">

                        <div>
                            <label class="form-label">Fecha</label>
                            <input type="date" class="form-input">
                        </div>

                        <div>
                            <label class="form-label">Modalidad</label>
                            <select class="form-input">
                                <option>Presencial</option>
                                <option>Virtual</option>
                            </select>
                        </div>

                        <div class="flex items-center gap-2 mt-6">
                            <input type="checkbox">
                            <label class="text-sm text-gray-600">
                                Marcar como completada
                            </label>
                        </div>

                    </div>
                </div>

                <!-- AGREGAR -->
                <button type="button"
                    class="w-full border border-gray-200 rounded-lg py-2 text-sm text-gray-600 hover:bg-gray-50 transition">
                    + Agregar visita extraordinaria
                </button>

            </form>
        </div>

        <!-- FOOTER -->
        <div class="flex justify-end gap-3 p-6 border-t">
            <button onclick="cerrarModalVisitas()" type="button" class="btn-cancelar">
                Cancelar
            </button>

            <button type="submit" class="btn-guardar">
                Guardar visitas
            </button>
        </div>

    </div>
</div>