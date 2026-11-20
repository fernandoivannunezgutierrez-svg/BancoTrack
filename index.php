<?php
declare(strict_types=1);

/**
 * BancoTrack - Punto de entrada principal (XAMPP / PHP 8+)
 */

$dbHost = getenv('DB_HOST') ?: '127.0.0.1';
$dbName = getenv('DB_NAME') ?: 'bancotrack';
$dbUser = getenv('DB_USER') ?: 'root';
$dbPass = getenv('DB_PASS') ?: '';

$dbStatus = 'Desconectado';
$dbDetail = '';
$clientCount = null;

try {
    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', $dbHost, $dbName);
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $dbStatus = 'Conectado';
    $stmt = $pdo->query('SELECT COUNT(*) AS total FROM clientes');
    $row = $stmt->fetch();
    $clientCount = $row ? (int) $row['total'] : 0;
} catch (PDOException $e) {
    $dbDetail = $e->getMessage();
}

$csvPath = __DIR__ . '/data/clientes_prueba.csv';
$csvExists = is_file($csvPath);
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>BancoTrack — Gestión de Cartera</title>
  <style>
    :root {
      color-scheme: light;
      --bg: #f4f6f9;
      --card: #ffffff;
      --text: #1e293b;
      --muted: #64748b;
      --primary: #0f4c81;
      --ok: #15803d;
      --warn: #b45309;
      --border: #e2e8f0;
    }
    body {
      margin: 0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.5;
    }
    .container {
      max-width: 920px;
      margin: 40px auto;
      padding: 0 20px;
    }
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 24px;
      margin-bottom: 20px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    h1 {
      margin-top: 0;
      color: var(--primary);
      font-size: 1.8rem;
    }
    .badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 0.85rem;
      font-weight: 600;
    }
    .badge-ok { background: #dcfce7; color: var(--ok); }
    .badge-warn { background: #fef3c7; color: var(--warn); }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      text-align: left;
      padding: 10px 12px;
      border-bottom: 1px solid var(--border);
      font-size: 0.95rem;
    }
    th { color: var(--muted); font-weight: 600; }
    code {
      background: #f1f5f9;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 0.9em;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="card">
      <h1>BancoTrack</h1>
      <p>Plataforma de seguimiento de cartera, gestiones comerciales e importación de clientes.</p>
      <p>
        <strong>PHP:</strong> <?= htmlspecialchars(PHP_VERSION, ENT_QUOTES, 'UTF-8') ?> &nbsp;|&nbsp;
        <strong>Base de datos MySQL:</strong>
        <?php if ($dbStatus === 'Conectado'): ?>
          <span class="badge badge-ok">Conectado (<?= (int) $clientCount ?> clientes)</span>
        <?php else: ?>
          <span class="badge badge-warn">Pendiente de importar esquema</span>
        <?php endif; ?>
      </p>
      <?php if ($dbDetail !== ''): ?>
        <p style="color: var(--muted); font-size: 0.9rem;">
          Detalle conexión: <code><?= htmlspecialchars($dbDetail, ENT_QUOTES, 'UTF-8') ?></code><br>
          Importa <code>database/01_schema_xampp_mysql.sql</code> en phpMyAdmin para inicializar la base de datos.
        </p>
      <?php endif; ?>
    </div>

    <div class="card">
      <h2>Roles Demo</h2>
      <table>
        <thead>
          <tr>
            <th>Rol</th>
            <th>Usuario</th>
            <th>Alcance</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td><code>admin</code></td>
            <td>admin@bancotrack.cl</td>
            <td>Administración general, usuarios y auditoría</td>
          </tr>
          <tr>
            <td><code>ejecutivo</code></td>
            <td>ejecutivo@bancotrack.cl</td>
            <td>Cartera de clientes, gestiones e importación CSV</td>
          </tr>
          <tr>
            <td><code>auditor</code></td>
            <td>auditor@bancotrack.cl</td>
            <td>Solo lectura sobre reportes y trazabilidad</td>
          </tr>
        </tbody>
      </table>
    </div>

    <div class="card">
      <h2>Dataset de prueba</h2>
      <p>
        Archivo CSV de ejemplo (separado por <code>;</code>):
        <code>data/clientes_prueba.csv</code>
        — <?= $csvExists ? '<span class="badge badge-ok">Disponible</span>' : '<span class="badge badge-warn">No encontrado</span>' ?>
      </p>
    </div>
  </div>
</body>
</html>
