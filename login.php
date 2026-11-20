<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/layout.php';

if (is_logged_in()) {
    header('Location: index.php');
    exit;
}

$dbOk = false;
$dbError = '';
$pdo = null;

try {
    $pdo = get_pdo();
    $dbOk = true;
} catch (PDOException $ex) {
    $dbError = $ex->getMessage();
}

$error = '';
$emailValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $dbOk && $pdo instanceof PDO) {
    verify_csrf();
    $emailValue = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($emailValue === '' || $password === '') {
        $error = 'Ingresa correo y contraseña.';
    } elseif (attempt_login($pdo, $emailValue, $password)) {
        set_flash('ok', 'Sesión iniciada correctamente.');
        header('Location: index.php');
        exit;
    } else {
        $error = 'Credenciales inválidas o usuario inactivo.';
    }
}

render_header('Iniciar sesión');
?>
<div style="max-width: 520px; margin: 40px auto;">
  <div class="card">
    <h1 style="margin-bottom: 6px;">BancoTrack</h1>
    <p class="muted" style="margin-top: 0;">
      Gestión de cartera, seguimiento comercial e importación CSV.
    </p>
    <p>
      <strong>PHP:</strong> <?= e(PHP_VERSION) ?> &nbsp;|&nbsp;
      <strong>MySQL:</strong>
      <?php if ($dbOk): ?>
        <span class="badge badge-ok">Conectado</span>
      <?php else: ?>
        <span class="badge badge-warn">Desconectado</span>
      <?php endif; ?>
    </p>

    <?php if (!$dbOk): ?>
      <div class="flash flash-err">
        No se pudo conectar a la base de datos: <code><?= e($dbError) ?></code><br>
        Importa <code>database/01_schema_xampp_mysql.sql</code> en phpMyAdmin.
      </div>
    <?php endif; ?>

    <?php if ($error !== ''): ?>
      <div class="flash flash-err"><?= e($error) ?></div>
    <?php endif; ?>

    <form method="post" action="login.php" style="display:flex; flex-direction:column; gap:14px;">
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="email">Correo electrónico</label>
        <input type="email" id="email" name="email" value="<?= e($emailValue) ?>" required autocomplete="username" placeholder="ejecutivo@bancotrack.cl">
      </div>
      <div class="form-group">
        <label for="password">Contraseña</label>
        <input type="password" id="password" name="password" required autocomplete="current-password" placeholder="••••••••">
      </div>
      <button type="submit" class="btn btn-primary" <?= $dbOk ? '' : 'disabled' ?>>Ingresar</button>
    </form>
  </div>

  <div class="card">
    <h3 style="margin-bottom: 8px;">Cuentas Demo</h3>
    <p class="muted" style="margin-top: 0;">Contraseña para todos los perfiles demo: <code>BancoTrack2026!</code></p>
    <table>
      <thead>
        <tr>
          <th>Rol</th>
          <th>Correo</th>
          <th>Acción</th>
        </tr>
      </thead>
      <tbody>
        <tr>
          <td><code>admin</code></td>
          <td>admin@bancotrack.cl</td>
          <td><button type="button" class="btn btn-secondary btn-sm" onclick="fillDemo('admin@bancotrack.cl')">Usar</button></td>
        </tr>
        <tr>
          <td><code>ejecutivo</code></td>
          <td>ejecutivo@bancotrack.cl</td>
          <td><button type="button" class="btn btn-secondary btn-sm" onclick="fillDemo('ejecutivo@bancotrack.cl')">Usar</button></td>
        </tr>
        <tr>
          <td><code>auditor</code></td>
          <td>auditor@bancotrack.cl</td>
          <td><button type="button" class="btn btn-secondary btn-sm" onclick="fillDemo('auditor@bancotrack.cl')">Usar</button></td>
        </tr>
      </tbody>
    </table>
  </div>
</div>

<script>
function fillDemo(email) {
  document.getElementById('email').value = email;
  document.getElementById('password').value = 'BancoTrack2026!';
}
</script>
<?php
render_footer();
