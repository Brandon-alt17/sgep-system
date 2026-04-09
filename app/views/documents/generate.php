<h1>Generar GFPI-F-023</h1>
<form method="post" action="<?= e(APP_BASE_PATH) ?>/documentos/generar">
    <input type="hidden" name="aprendiz_id" value="<?= (int) ($aprendiz_id ?? 0) ?>">
    <label><input type="checkbox" name="partes[]" value="info" checked> Información general</label><br>
    <label><input type="checkbox" name="partes[]" value="M1" checked> Momento 1</label><br>
    <label><input type="checkbox" name="partes[]" value="M2" checked> Momento 2</label><br>
    <label><input type="checkbox" name="partes[]" value="M3" checked> Momento 3</label><br>
    <label>Formato
        <select name="formato">
            <option value="docx">Word (.docx)</option>
            <option value="pdf">PDF</option>
        </select>
    </label><br>
    <button type="submit">Generar y descargar</button>
</form>
