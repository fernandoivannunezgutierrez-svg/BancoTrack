<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rut.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_login();
$pdo = get_pdo();
$canEdit = has_role(['admin', 'ejecutivo']);

$segmentosPermitidos = ['Retail', 'Preferente', 'Pyme', 'Corporativo'];
$estadosPermitidos = ['Activo', 'En Evaluación', 'Inactivo'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(['admin', 'ejecutivo']);
    verify_csrf();

    $rut = normalizar_rut((string) ($_POST['rut'] ?? ''));
    $nombre = trim((string) ($_POST['nombre_completo'] ?? ''));
    $email = trim((string) ($_POST['email'] ?? ''));
    $telefono = trim((string) ($_POST['telefono'] ?? ''));
    $comuna = trim((string) ($_POST['comuna'] ?? ''));
    $region = trim((string) ($_POST['region'] ?? ''));
    $segmento = (string) ($_POST['segmento'] ?? 'Retail');
    $estado = (string) ($_POST['estado'] ?? 'Activo');

    if ($rut === null || $nombre === '') {
        set_flash('err', 'RUT válido y nombre completo son obligatorios.');
        header('Location: clientes.php');
        exit;
    }

    if (!in_array($segmento, $segmentosPermitidos, true)) {
        $segmento = 'Retail';
    }
    if (!in_array($estado, $estadosPermitidos, true)) {
        $estado = 'Activo';
    }

    $ejecutivoId = $user['rol_nombre'] === 'ejecutivo' ? $user['id'] : null;

    $stmt = $pdo->prepare(
        'INSERT INTO clientes (rut, nombre_completo, email, telefono, comuna, region, segmento, estado, ejecutivo_id)
         VALUES (:rut, :nombre, :email, :telefono, :comuna, :region, :segmento, :estado, :ejecutivo_id)
         ON DUPLICATE KEY UPDATE
           nombre_completo = VALUES(nombre_completo),
           email = VALUES(email),
           telefono = VALUES(telefono),
           comuna = VALUES(comuna),
           region = VALUES(region),
           segmento = VALUES(segmento),
           estado = VALUES(estado)'
    );
    $stmt->execute([
        'rut' => $rut,
        'nombre' => $nombre,
        'email' => $email !== '' ? $email : null,
        'telefono' => $telefono !== '' ? $telefono : null,
        'comuna' => $comuna !== '' ? $comuna : null,
        'region' => $region !== '' ? $region : null,
        'segmento' => $segmento,
        'estado' => $estado,
        'ejecutivo_id' => $ejecutivoId,
    ]);

    set_flash('ok', 'Cliente guardado correctamente (' . $rut . ').');
    header('Location: clientes.php');
    exit;
}

$q = trim((string) ($_GET['q'] ?? ''));
$filtroSegmento = (string) ($_GET['segmento'] ?? '');
$filtroEstado = (string) ($_GET['estado'] ?? '');

$where = [];
$params = [];

if ($q !== '') {
    $where[] = '(c.rut LIKE :q1 OR c.nombre_completo LIKE :q2 OR c.email LIKE :q3)';
    $like = '%' . $q . '%';
    $params['q1'] = $like;
    $params['q2'] = $like;
    $params['q3'] = $like;
}

if (in_array($filtroSegmento, $segmentosPermitidos, true)) {
    $where[] = 'c.segmento = :segmento';
    $params['segmento'] = $filtroSegmento;
}

if (in_array($filtroEstado, $estadosPermitidos, true)) {
    $where[] = 'c.estado = :estado';
    $params['estado'] = $filtroEstado;
}

$sql = 'SELECT c.*, u.nombre AS ejecutivo_nombre
        FROM clientes c
        LEFT JOIN usuarios u ON u.id = c.ejecutivo_id';

if ($where) {
    $sql .= ' WHERE ' . implode(' AND ', $where);
}

$sql .= ' ORDER BY c.updated_at DESC, c.id DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$clientes = $stmt->fetchAll();

render_header('Cartera de Clientes', 'clientes');
?>
<div class="card">
  <h1>Cartera de Clientes</h1>
  <form method="get" action="clientes.php" class="form-grid" style="align-items: end;">
    <div class="form-group">
      <label for="q">Buscar (RUT, nombre o correo)</label>
      <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Ej: 15.234.567-8 o Camila">
    </div>
    <div class="form-group">
      <label for="segmento">Segmento</label>
      <select id="segmento" name="segmento">
        <option value="">Todos</option>
        <?php foreach ($segmentosPermitidos as $seg): ?>
          <option value="<?= e($seg) ?>" <?= $filtroSegmento === $seg ? 'selected' : '' ?>><?= e($seg) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div class="form-group">
      <label for="estado">Estado</label>
      <select id="estado" name="estado">
        <option value="">Todos</option>
        <?php foreach ($estadosPermitidos as $est): ?>
          <option value="<?= e($est) ?>" <?= $filtroEstado === $est ? 'selected' : '' ?>><?= e($est) ?></option>
        <?php endforeach; ?>
      </select>
    </div>
    <div style="display:flex; gap:8px;">
      <button type="submit" class="btn btn-primary">Filtrar</button>
      <a href="clientes.php" class="btn btn-secondary">Limpiar</a>
    </div>
  </form>
</div>

<?php if ($canEdit): ?>
  <div class="card">
    <h2>Registrar / Actualizar Cliente</h2>
    <p class="muted" style="margin-top:0;">Si el RUT ya existe, sus datos se actualizarán automáticamente.</p>
    <form method="post" action="clientes.php" class="form-grid">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="f_rut">RUT *</label>
        <input type="text" id="f_rut" name="rut" required placeholder="15.234.567-8">
      </div>
      <div class="form-group">
        <label for="f_nombre">Nombre completo *</label>
        <input type="text" id="f_nombre" name="nombre_completo" required placeholder="Camila Andrea Rojas">
      </div>
      <div class="form-group">
        <label for="f_email">Correo</label>
        <input type="email" id="f_email" name="email" placeholder="cliente@ejemplo.cl">
      </div>
      <div class="form-group">
        <label for="f_telefono">Teléfono</label>
        <input type="text" id="f_telefono" name="telefono" placeholder="+56981234567">
      </div>
      <div class="form-group">
        <label for="f_comuna">Comuna</label>
        <input type="text" id="f_comuna" name="comuna" placeholder="Providencia">
      </div>
      <div class="form-group">
        <label for="f_region">Región</label>
        <input type="text" id="f_region" name="region" placeholder="Metropolitana de Santiago">
      </div>
      <div class="form-group">
        <label for="f_segmento">Segmento</label>
        <select id="f_segmento" name="segmento">
          <?php foreach ($segmentosPermitidos as $seg): ?>
            <option value="<?= e($seg) ?>"><?= e($seg) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="f_estado">Estado</label>
        <select id="f_estado" name="estado">
          <?php foreach ($estadosPermitidos as $est): ?>
            <option value="<?= e($est) ?>"><?= e($est) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div style="grid-column: 1 / -1;">
        <button type="submit" class="btn btn-primary">Guardar cliente</button>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="card">
  <h2>Listado (<?= count($clientes) ?>)</h2>
  <?php if (!$clientes): ?>
    <p class="muted">No se encontraron clientes con los filtros actuales.</p>
  <?php else: ?>
    <div style="overflow-x:auto;">
      <table>
        <thead>
          <tr>
            <th>RUT</th>
            <th>Nombre</th>
            <th>Segmento</th>
            <th>Estado</th>
            <th>Comuna / Región</th>
            <th>Ejecutivo</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($clientes as $c): ?>
            <tr>
              <td><code><?= e($c['rut']) ?></code></td>
              <td>
                <strong><?= e($c['nombre_completo']) ?></strong><br>
                <span class="muted"><?= e($c['email'] ?? '—') ?></span>
              </td>
              <td><span class="badge badge-info"><?= e($c['segmento']) ?></span></td>
              <td>
                <?php
                  $badgeClass = match ($c['estado']) {
                      'Activo' => 'badge-ok',
                      'En Evaluación' => 'badge-warn',
                      default => 'badge-err',
                  };
                ?>
                <span class="badge <?= $badgeClass ?>"><?= e($c['estado']) ?></span>
              </td>
              <td><?= e(($c['comuna'] ?? '—') . ', ' . ($c['region'] ?? '—')) ?></td>
              <td><?= e($c['ejecutivo_nombre'] ?? 'Sin asignar') ?></td>
              <td>
                <a class="btn btn-secondary btn-sm" href="cliente_detalle.php?id=<?= (int) $c['id'] ?>">Ver gestiones</a>
              </td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  <?php endif; ?>
</div>
<?php
render_footer();
