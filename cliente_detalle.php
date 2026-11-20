<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_login();
$pdo = get_pdo();
$canEdit = has_role(['admin', 'ejecutivo']);

$clienteId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
if (!$clienteId || $clienteId <= 0) {
    set_flash('err', 'Cliente no especificado.');
    header('Location: clientes.php');
    exit;
}

$tiposGestion = ['Llamada', 'Reunión', 'Evaluación Crediticia', 'Seguimiento'];
$estadosGestion = ['Pendiente', 'En Proceso', 'Cerrada'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    require_role(['admin', 'ejecutivo']);
    verify_csrf();

    $accion = (string) ($_POST['accion'] ?? '');

    if ($accion === 'crear_gestion') {
        $tipo = (string) ($_POST['tipo_gestion'] ?? 'Seguimiento');
        $estado = (string) ($_POST['estado'] ?? 'Pendiente');
        $monto = max(0, (int) ($_POST['monto_estimado_clp'] ?? 0));
        $obs = trim((string) ($_POST['observaciones'] ?? ''));

        if (!in_array($tipo, $tiposGestion, true)) {
            $tipo = 'Seguimiento';
        }
        if (!in_array($estado, $estadosGestion, true)) {
            $estado = 'Pendiente';
        }

        $stmt = $pdo->prepare(
            'INSERT INTO gestiones (cliente_id, usuario_id, tipo_gestion, estado, monto_estimado_clp, observaciones)
             VALUES (:cliente_id, :usuario_id, :tipo_gestion, :estado, :monto, :obs)'
        );
        $stmt->execute([
            'cliente_id' => $clienteId,
            'usuario_id' => $user['id'],
            'tipo_gestion' => $tipo,
            'estado' => $estado,
            'monto' => $monto,
            'obs' => $obs !== '' ? $obs : null,
        ]);

        set_flash('ok', 'Gestión registrada correctamente.');
    } elseif ($accion === 'actualizar_estado') {
        $gestionId = (int) ($_POST['gestion_id'] ?? 0);
        $nuevoEstado = (string) ($_POST['nuevo_estado'] ?? '');

        if ($gestionId > 0 && in_array($nuevoEstado, $estadosGestion, true)) {
            $stmt = $pdo->prepare(
                'UPDATE gestiones SET estado = :estado WHERE id = :id AND cliente_id = :cliente_id'
            );
            $stmt->execute([
                'estado' => $nuevoEstado,
                'id' => $gestionId,
                'cliente_id' => $clienteId,
            ]);
            set_flash('ok', 'Estado de gestión actualizado.');
        }
    }

    header('Location: cliente_detalle.php?id=' . $clienteId);
    exit;
}

$stmt = $pdo->prepare(
    'SELECT c.*, u.nombre AS ejecutivo_nombre
     FROM clientes c
     LEFT JOIN usuarios u ON u.id = c.ejecutivo_id
     WHERE c.id = :id'
);
$stmt->execute(['id' => $clienteId]);
$cliente = $stmt->fetch();

if (!$cliente) {
    set_flash('err', 'Cliente no encontrado.');
    header('Location: clientes.php');
    exit;
}

$stmtGestiones = $pdo->prepare(
    'SELECT g.*, u.nombre AS usuario_nombre
     FROM gestiones g
     INNER JOIN usuarios u ON u.id = g.usuario_id
     WHERE g.cliente_id = :cliente_id
     ORDER BY g.fecha_gestion DESC, g.id DESC'
);
$stmtGestiones->execute(['cliente_id' => $clienteId]);
$gestiones = $stmtGestiones->fetchAll();

render_header('Detalle de Cliente', 'clientes');
?>
<div class="card">
  <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
    <div>
      <h1 style="margin-bottom:4px;"><?= e($cliente['nombre_completo']) ?></h1>
      <p class="muted" style="margin:0;">
        RUT: <code><?= e($cliente['rut']) ?></code> ·
        Segmento: <strong><?= e($cliente['segmento']) ?></strong> ·
        Estado: <strong><?= e($cliente['estado']) ?></strong>
      </p>
    </div>
    <a class="btn btn-secondary" href="clientes.php">← Volver a cartera</a>
  </div>
  <hr style="border:none; border-top:1px solid var(--border); margin:16px 0;">
  <div class="form-grid">
    <div><span class="muted">Correo:</span><br><strong><?= e($cliente['email'] ?? '—') ?></strong></div>
    <div><span class="muted">Teléfono:</span><br><strong><?= e($cliente['telefono'] ?? '—') ?></strong></div>
    <div><span class="muted">Comuna / Región:</span><br><strong><?= e(($cliente['comuna'] ?? '—') . ', ' . ($cliente['region'] ?? '—')) ?></strong></div>
    <div><span class="muted">Ejecutivo asignado:</span><br><strong><?= e($cliente['ejecutivo_nombre'] ?? 'Sin asignar') ?></strong></div>
  </div>
</div>

<?php if ($canEdit): ?>
  <div class="card">
    <h2>Registrar Nueva Gestión</h2>
    <form method="post" action="cliente_detalle.php?id=<?= (int) $cliente['id'] ?>" class="form-grid">
      <?= csrf_field() ?>
      <input type="hidden" name="accion" value="crear_gestion">
      <div class="form-group">
        <label for="tipo_gestion">Tipo de gestión</label>
        <select id="tipo_gestion" name="tipo_gestion">
          <?php foreach ($tiposGestion as $t): ?>
            <option value="<?= e($t) ?>"><?= e($t) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="estado">Estado</label>
        <select id="estado" name="estado">
          <?php foreach ($estadosGestion as $est): ?>
            <option value="<?= e($est) ?>"><?= e($est) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="form-group">
        <label for="monto_estimado_clp">Monto estimado (CLP)</label>
        <input type="number" id="monto_estimado_clp" name="monto_estimado_clp" min="0" step="1000" value="0">
      </div>
      <div class="form-group" style="grid-column: 1 / -1;">
        <label for="observaciones">Observaciones</label>
        <textarea id="observaciones" name="observaciones" rows="2" placeholder="Detalle del contacto o evaluación..."></textarea>
      </div>
      <div>
        <button type="submit" class="btn btn-primary">Guardar gestión</button>
      </div>
    </form>
  </div>
<?php endif; ?>

<div class="card">
  <h2>Historial de Gestiones (<?= count($gestiones) ?>)</h2>
  <?php if (!$gestiones): ?>
    <p class="muted">Este cliente aún no tiene gestiones registradas.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Tipo</th>
          <th>Estado</th>
          <th>Monto CLP</th>
          <th>Responsable</th>
          <th>Observaciones</th>
          <?php if ($canEdit): ?><th>Actualizar</th><?php endif; ?>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($gestiones as $g): ?>
          <tr>
            <td><?= e($g['fecha_gestion']) ?></td>
            <td><?= e($g['tipo_gestion']) ?></td>
            <td>
              <?php
                $badgeClass = match ($g['estado']) {
                    'Cerrada' => 'badge-ok',
                    'En Proceso' => 'badge-info',
                    default => 'badge-warn',
                };
              ?>
              <span class="badge <?= $badgeClass ?>"><?= e($g['estado']) ?></span>
            </td>
            <td>$<?= number_format((int) $g['monto_estimado_clp'], 0, ',', '.') ?></td>
            <td><?= e($g['usuario_nombre']) ?></td>
            <td><?= e($g['observaciones'] ?? '—') ?></td>
            <?php if ($canEdit): ?>
              <td>
                <form method="post" action="cliente_detalle.php?id=<?= (int) $cliente['id'] ?>" style="display:flex; gap:6px; align-items:center;">
                  <?= csrf_field() ?>
                  <input type="hidden" name="accion" value="actualizar_estado">
                  <input type="hidden" name="gestion_id" value="<?= (int) $g['id'] ?>">
                  <select name="nuevo_estado" style="width:auto; padding:4px 8px; font-size:0.85rem;">
                    <?php foreach ($estadosGestion as $est): ?>
                      <option value="<?= e($est) ?>" <?= $g['estado'] === $est ? 'selected' : '' ?>><?= e($est) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button type="submit" class="btn btn-secondary btn-sm">OK</button>
                </form>
              </td>
            <?php endif; ?>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php
render_footer();
