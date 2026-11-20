<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_login();
$pdo = get_pdo();

$totalClientes = (int) $pdo->query('SELECT COUNT(*) FROM clientes')->fetchColumn();
$clientesActivos = (int) $pdo->query("SELECT COUNT(*) FROM clientes WHERE estado = 'Activo'")->fetchColumn();
$gestionesAbiertas = (int) $pdo->query("SELECT COUNT(*) FROM gestiones WHERE estado IN ('Pendiente', 'En Proceso')")->fetchColumn();
$totalImportaciones = (int) $pdo->query('SELECT COUNT(*) FROM importaciones_csv')->fetchColumn();

$segmentos = $pdo->query(
    'SELECT segmento, COUNT(*) AS cantidad
     FROM clientes
     GROUP BY segmento
     ORDER BY cantidad DESC'
)->fetchAll();

$ultimasImportaciones = $pdo->query(
    'SELECT i.id, i.nombre_archivo, i.registros_procesados, i.registros_error, i.created_at, u.nombre AS usuario_nombre
     FROM importaciones_csv i
     INNER JOIN usuarios u ON u.id = i.usuario_id
     ORDER BY i.id DESC
     LIMIT 5'
)->fetchAll();

render_header('Dashboard', 'dashboard');
?>
<div class="card">
  <h1 style="margin-bottom: 6px;">Panel Principal</h1>
  <p class="muted" style="margin: 0;">
    Bienvenido, <strong><?= e($user['nombre']) ?></strong> · Rol: <code><?= e($user['rol_nombre']) ?></code>
  </p>
</div>

<div class="grid-kpi">
  <div class="kpi">
    <div class="kpi-label">Total Clientes</div>
    <div class="kpi-value"><?= $totalClientes ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label">Clientes Activos</div>
    <div class="kpi-value"><?= $clientesActivos ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label">Gestiones Abiertas</div>
    <div class="kpi-value"><?= $gestionesAbiertas ?></div>
  </div>
  <div class="kpi">
    <div class="kpi-label">Cargas CSV</div>
    <div class="kpi-value"><?= $totalImportaciones ?></div>
  </div>
</div>

<div class="form-grid" style="align-items: start;">
  <div class="card">
    <h2>Distribución por Segmento</h2>
    <?php if (!$segmentos): ?>
      <p class="muted">Aún no hay clientes registrados. Puedes cargar <code>data/clientes_prueba.csv</code> desde el importador.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Segmento</th>
            <th>Clientes</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($segmentos as $seg): ?>
            <tr>
              <td><?= e($seg['segmento']) ?></td>
              <td><strong><?= (int) $seg['cantidad'] ?></strong></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>

  <div class="card">
    <h2>Últimas Importaciones CSV</h2>
    <?php if (!$ultimasImportaciones): ?>
      <p class="muted">No se han registrado importaciones aún.</p>
    <?php else: ?>
      <table>
        <thead>
          <tr>
            <th>Archivo</th>
            <th>Procesados</th>
            <th>Errores</th>
            <th>Usuario</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($ultimasImportaciones as $imp): ?>
            <tr>
              <td><?= e($imp['nombre_archivo']) ?></td>
              <td><span class="badge badge-ok"><?= (int) $imp['registros_procesados'] ?></span></td>
              <td><span class="badge <?= (int) $imp['registros_error'] > 0 ? 'badge-warn' : 'badge-info' ?>"><?= (int) $imp['registros_error'] ?></span></td>
              <td><?= e($imp['usuario_nombre']) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>
  </div>
</div>
<?php
render_footer();
