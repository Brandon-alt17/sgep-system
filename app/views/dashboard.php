<h1>Dashboard SGEP</h1>

<h2>Conteo por estado</h2>
<ul>
    <?php foreach (($counts ?? []) as $row): ?>
        <li><?= e((string) $row['estado']) ?>: <?= e((string) $row['total']) ?></li>
    <?php endforeach; ?>
</ul>

<h2>Próximas visitas (30 días)</h2>
<ul>
    <?php foreach (($alerts ?? []) as $alert): ?>
        <li>
            <a href="<?= e(APP_BASE_PATH) ?>/aprendices/show?id=<?= (int) $alert['id'] ?>"><?= e((string) $alert['nombre_completo']) ?></a>
            - <?= e((string) $alert['proxima_visita']) ?>
        </li>
    <?php endforeach; ?>
</ul>
