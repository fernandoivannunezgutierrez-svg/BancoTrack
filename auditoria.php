<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_role(['admin', 'auditor']);
$pdo = get_pdo();

$importaciones = $pdo->query(
    'SELECT i.*, u.nombre AS usuario_nombre, r.nombre AS rol_nombre
     FROM importaciones_csv i
     INNER JOIN usuarios u ON u.id = i.usuario_id
     INNER JOIN roles r ON r.id = u.rol_id
     ORDER BY i.id DESC
     LIMIT 20'
)->fetchAll();

$gestiones = $pdo->query(
    'SELECT g.*, c.rut, c.nombre_completo AS cliente_nombre, u.nombre AS usuario_nombre
     FROM gestiones g
     INNER JOIN clientes c ON c.id = g.cliente_id
     INNER JOIN usuarios u ON u.id = g.usuario_id
     ORDER BY g.fecha_gestion DESC, g.id DESC
     LIMIT 25'
)->fetchAll();

render_header('Auditoría y Trazabilidad', 'auditoria');
?>
<div class="card">
  <h1>Auditoría y Trazabilidad</h1>
  <p class="muted" style="margin:0;">Vista de control y solo lectura sobre cargas masivas CSV y gestiones comerciales.</p>
</div>

<div class="card">
  <h2>Registro de Importaciones CSV</h2>
  <?php if (!$importaciones): ?>
    <p class="muted">Sin registros de importación.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Archivo</th>
          <th>Procesados</th>
          <th>Errores</th>
          <th>Usuario</th>
          <th>Rol</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($importaciones as $imp): ?>
          <tr>
            <td><?= e($imp['created_at']) ?></td>
            <td><code><?= e($imp['nombre_archivo']) ?></code></td>
            <td><span class="badge badge-ok"><?= (int) $imp['registros_procesados'] ?></span></td>
            <td><span class="badge <?= (int) $imp['registros_error'] > 0 ? 'badge-warn' : 'badge-info' ?>"><?= (int) $imp['registros_error'] ?></span></td>
            <td><?= e($imp['usuario_nombre']) ?></td>
            <td><code><?= e($imp['rol_nombre']) ?></code></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>

<div class="card">
  <h2>Últimas Gestiones Registradas</h2>
  <?php if (!$gestiones): ?>
    <p class="muted">Sin gestiones registradas aún.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>Fecha</th>
          <th>Cliente</th>
          <th>Tipo</th>
          <th>Estado</th>
          <th>Monto CLP</th>
          <th>Responsable</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($gestiones as $g): ?>
          <tr>
            <td><?= e($g['fecha_gestion']) ?></td>
            <td>
              <a href="cliente_detalle.php?id=<?= (int) $g['cliente_id'] ?>"><?= e($g['cliente_nombre']) ?></a>
              <span class="muted">(<?= e($g['rut']) ?>)</span>
            </td>
            <td><?= e($g['tipo_gestion']) ?></td>
            <td><span class="badge badge-info"><?= e($g['estado']) ?></span></td>
            <td>$<?= number_format((int) $g['monto_estimado_clp'], 0, ',', '.') ?></td>
            <td><?= e($g['usuario_nombre']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php
render_footer();
