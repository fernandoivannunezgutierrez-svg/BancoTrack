<?php
declare(strict_types=1);

/**
 * Plantilla HTML común para BancoTrack.
 */

function render_header(string $title, string $activeNav = ''): void
{
    $user = current_user();
    $flash = get_flash();
    ?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($title) ?> — BancoTrack</title>
  <style>
    :root {
      color-scheme: light;
      --bg: #f4f6f9;
      --card: #ffffff;
      --text: #1e293b;
      --muted: #64748b;
      --primary: #0f4c81;
      --primary-hover: #0c3d68;
      --ok: #15803d;
      --ok-bg: #dcfce7;
      --warn: #b45309;
      --warn-bg: #fef3c7;
      --err: #b91c1c;
      --err-bg: #fee2e2;
      --border: #e2e8f0;
    }
    * { box-sizing: border-box; }
    body {
      margin: 0;
      font-family: system-ui, -apple-system, Segoe UI, Roboto, sans-serif;
      background: var(--bg);
      color: var(--text);
      line-height: 1.5;
    }
    .topbar {
      background: var(--primary);
      color: #fff;
      padding: 0 24px;
      display: flex;
      align-items: center;
      justify-content: space-between;
      flex-wrap: wrap;
      gap: 12px;
      min-height: 58px;
    }
    .topbar-brand {
      font-weight: 700;
      font-size: 1.15rem;
      color: #fff;
      text-decoration: none;
      letter-spacing: -0.01em;
    }
    .topbar-nav {
      display: flex;
      align-items: center;
      gap: 6px;
      flex-wrap: wrap;
    }
    .topbar-nav a {
      color: rgba(255, 255, 255, 0.85);
      text-decoration: none;
      padding: 7px 12px;
      border-radius: 6px;
      font-size: 0.92rem;
      font-weight: 500;
    }
    .topbar-nav a:hover, .topbar-nav a.active {
      background: rgba(255, 255, 255, 0.16);
      color: #fff;
    }
    .topbar-user {
      display: flex;
      align-items: center;
      gap: 10px;
      font-size: 0.88rem;
    }
    .role-pill {
      background: rgba(255, 255, 255, 0.2);
      padding: 2px 9px;
      border-radius: 999px;
      font-weight: 600;
      text-transform: uppercase;
      font-size: 0.75rem;
    }
    .container {
      max-width: 1120px;
      margin: 28px auto;
      padding: 0 20px;
    }
    .card {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 22px 24px;
      margin-bottom: 20px;
      box-shadow: 0 1px 3px rgba(0, 0, 0, 0.04);
    }
    h1, h2, h3 { margin-top: 0; color: var(--primary); }
    .grid-kpi {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
      gap: 16px;
      margin-bottom: 20px;
    }
    .kpi {
      background: var(--card);
      border: 1px solid var(--border);
      border-radius: 10px;
      padding: 18px 20px;
    }
    .kpi-label { color: var(--muted); font-size: 0.85rem; font-weight: 600; }
    .kpi-value { font-size: 1.8rem; font-weight: 700; color: var(--primary); margin-top: 4px; }
    .badge {
      display: inline-block;
      padding: 3px 10px;
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 600;
    }
    .badge-ok { background: var(--ok-bg); color: var(--ok); }
    .badge-warn { background: var(--warn-bg); color: var(--warn); }
    .badge-err { background: var(--err-bg); color: var(--err); }
    .badge-info { background: #e0f2fe; color: #0369a1; }
    .flash {
      padding: 12px 16px;
      border-radius: 8px;
      margin-bottom: 18px;
      font-weight: 500;
      border: 1px solid transparent;
    }
    .flash-ok { background: var(--ok-bg); color: var(--ok); border-color: #bbf7d0; }
    .flash-warn { background: var(--warn-bg); color: var(--warn); border-color: #fde68a; }
    .flash-err { background: var(--err-bg); color: var(--err); border-color: #fecaca; }
    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }
    th, td {
      text-align: left;
      padding: 10px 12px;
      border-bottom: 1px solid var(--border);
      font-size: 0.92rem;
    }
    th { color: var(--muted); font-weight: 600; background: #f8fafc; }
    tr:hover td { background: #f8fafc; }
    .form-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
      gap: 14px;
    }
    .form-group { display: flex; flex-direction: column; gap: 5px; }
    .form-group label { font-size: 0.85rem; font-weight: 600; color: var(--muted); }
    input[type="text"], input[type="email"], input[type="password"], input[type="number"], input[type="file"], select, textarea {
      width: 100%;
      padding: 8px 10px;
      border: 1px solid #cbd5e1;
      border-radius: 6px;
      font-size: 0.94rem;
      font-family: inherit;
      background: #fff;
    }
    .btn {
      display: inline-block;
      padding: 8px 15px;
      border-radius: 6px;
      border: 1px solid transparent;
      font-size: 0.9rem;
      font-weight: 600;
      cursor: pointer;
      text-decoration: none;
      font-family: inherit;
    }
    .btn-primary { background: var(--primary); color: #fff; }
    .btn-primary:hover { background: var(--primary-hover); }
    .btn-secondary { background: #e2e8f0; color: var(--text); }
    .btn-secondary:hover { background: #cbd5e1; }
    .btn-sm { padding: 5px 10px; font-size: 0.82rem; }
    code {
      background: #f1f5f9;
      padding: 2px 6px;
      border-radius: 4px;
      font-size: 0.88em;
    }
    .muted { color: var(--muted); font-size: 0.9rem; }
  </style>
</head>
<body>
  <?php if ($user): ?>
    <header class="topbar">
      <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap;">
        <a class="topbar-brand" href="index.php">BancoTrack</a>
        <nav class="topbar-nav">
          <a href="index.php" class="<?= $activeNav === 'dashboard' ? 'active' : '' ?>">Dashboard</a>
          <a href="clientes.php" class="<?= $activeNav === 'clientes' ? 'active' : '' ?>">Cartera de Clientes</a>
          <?php if (has_role(['admin', 'ejecutivo'])): ?>
            <a href="importar_csv.php" class="<?= $activeNav === 'importar' ? 'active' : '' ?>">Importar CSV</a>
          <?php endif; ?>
          <?php if (has_role(['admin', 'auditor'])): ?>
            <a href="auditoria.php" class="<?= $activeNav === 'auditoria' ? 'active' : '' ?>">Auditoría</a>
          <?php endif; ?>
        </nav>
      </div>
      <div class="topbar-user">
        <span><?= e($user['nombre']) ?></span>
        <span class="role-pill"><?= e($user['rol_nombre']) ?></span>
        <a class="btn btn-secondary btn-sm" href="logout.php">Salir</a>
      </div>
    </header>
  <?php endif; ?>
  <main class="container">
    <?php if ($flash): ?>
      <div class="flash flash-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
    <?php endif; ?>
    <?php
}

function render_footer(): void
{
    ?>
  </main>
</body>
</html>
    <?php
}
