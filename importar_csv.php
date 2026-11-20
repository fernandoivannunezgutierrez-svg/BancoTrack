<?php
declare(strict_types=1);

require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/rut.php';
require_once __DIR__ . '/includes/layout.php';

$user = require_role(['admin', 'ejecutivo']);
$pdo = get_pdo();

/**
 * Procesa un archivo CSV delimitado por ';' (UTF-8) e inserta/actualiza clientes.
 */
function procesar_csv_clientes(PDO $pdo, string $filePath, string $fileName, int $userId, ?int $ejecutivoId): array
{
    $handle = fopen($filePath, 'rb');
    if ($handle === false) {
        throw new RuntimeException('No fue posible abrir el archivo CSV.');
    }

    $segmentosPermitidos = ['Retail', 'Preferente', 'Pyme', 'Corporativo'];
    $estadosPermitidos = ['Activo', 'En Evaluación', 'Inactivo'];
    $expectedHeader = ['rut', 'nombre_completo', 'email', 'telefono', 'comuna', 'region', 'segmento', 'estado'];

    $procesados = 0;
    $errores = 0;
    $detalleErrores = [];

    try {
        $header = fgetcsv($handle, 0, ';', '"', '');
        if ($header === false) {
            throw new RuntimeException('El archivo CSV está vacío.');
        }

        // Eliminar posible BOM UTF-8 en la primera columna
        $header[0] = preg_replace('/^\xEF\xBB\xBF/', '', (string) $header[0]) ?? (string) $header[0];
        $headerNorm = array_map(static fn($col) => trim((string) $col), $header);

        if ($headerNorm !== $expectedHeader) {
            throw new RuntimeException(
                'Encabezado inválido. Se esperaba: ' . implode(';', $expectedHeader)
            );
        }

        $pdo->beginTransaction();

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

        $lineNum = 1;
        while (($row = fgetcsv($handle, 0, ';', '"', '')) !== false) {
            $lineNum++;
            if ($row === [null] || count($row) === 0) {
                continue;
            }
            if (count($row) < 8) {
                $errores++;
                $detalleErrores[] = "Línea {$lineNum}: columnas insuficientes (" . count($row) . ').';
                continue;
            }

            $rut = normalizar_rut((string) $row[0]);
            $nombre = trim((string) $row[1]);
            $email = trim((string) $row[2]);
            $telefono = trim((string) $row[3]);
            $comuna = trim((string) $row[4]);
            $region = trim((string) $row[5]);
            $segmento = trim((string) $row[6]);
            $estado = trim((string) $row[7]);

            if ($rut === null || $nombre === '') {
                $errores++;
                $detalleErrores[] = "Línea {$lineNum}: RUT inválido o nombre vacío.";
                continue;
            }

            if (!in_array($segmento, $segmentosPermitidos, true)) {
                $errores++;
                $detalleErrores[] = "Línea {$lineNum}: segmento '{$segmento}' no permitido.";
                continue;
            }

            if (!in_array($estado, $estadosPermitidos, true)) {
                $errores++;
                $detalleErrores[] = "Línea {$lineNum}: estado '{$estado}' no permitido.";
                continue;
            }

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
            $procesados++;
        }

        $logStmt = $pdo->prepare(
            'INSERT INTO importaciones_csv (usuario_id, nombre_archivo, registros_procesados, registros_error)
             VALUES (:usuario_id, :nombre_archivo, :procesados, :errores)'
        );
        $logStmt->execute([
            'usuario_id' => $userId,
            'nombre_archivo' => mb_substr($fileName, 0, 180),
            'procesados' => $procesados,
            'errores' => $errores,
        ]);

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    } finally {
        fclose($handle);
    }

    return [
        'procesados' => $procesados,
        'errores' => $errores,
        'detalles' => $detalleErrores,
    ];
}

$resultado = null;
$errorMsg = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $modo = (string) ($_POST['modo'] ?? 'upload');
    $ejecutivoId = $user['rol_nombre'] === 'ejecutivo' ? $user['id'] : null;

    try {
        if ($modo === 'demo') {
            $demoPath = __DIR__ . '/data/clientes_prueba.csv';
            if (!is_file($demoPath)) {
                throw new RuntimeException('No se encontró data/clientes_prueba.csv.');
            }
            $resultado = procesar_csv_clientes($pdo, $demoPath, 'clientes_prueba.csv (demo)', $user['id'], $ejecutivoId);
        } else {
            if (!isset($_FILES['archivo_csv']) || $_FILES['archivo_csv']['error'] !== UPLOAD_ERR_OK) {
                throw new RuntimeException('Selecciona un archivo CSV válido.');
            }
            $tmpPath = (string) $_FILES['archivo_csv']['tmp_name'];
            $origName = basename((string) $_FILES['archivo_csv']['name']);
            $resultado = procesar_csv_clientes($pdo, $tmpPath, $origName, $user['id'], $ejecutivoId);
        }
    } catch (Throwable $e) {
        $errorMsg = $e->getMessage();
    }
}

$historial = $pdo->query(
    'SELECT i.*, u.nombre AS usuario_nombre
     FROM importaciones_csv i
     INNER JOIN usuarios u ON u.id = i.usuario_id
     ORDER BY i.id DESC
     LIMIT 10'
)->fetchAll();

render_header('Importar CSV', 'importar');
?>
<div class="card">
  <h1>Importador Masivo de Clientes (CSV)</h1>
  <p class="muted">
    Formato requerido: UTF-8 delimitado por <code>;</code> con columnas
    <code>rut;nombre_completo;email;telefono;comuna;region;segmento;estado</code>.
    Los RUT existentes se actualizan de forma idempotente (<code>ON DUPLICATE KEY UPDATE</code>).
  </p>

  <?php if ($errorMsg !== ''): ?>
    <div class="flash flash-err"><?= e($errorMsg) ?></div>
  <?php endif; ?>

  <?php if ($resultado !== null): ?>
    <div class="flash <?= $resultado['errores'] > 0 ? 'flash-warn' : 'flash-ok' ?>">
      Importación finalizada: <strong><?= (int) $resultado['procesados'] ?></strong> registros procesados,
      <strong><?= (int) $resultado['errores'] ?></strong> con error.
    </div>
    <?php if (!empty($resultado['detalles'])): ?>
      <ul style="color: var(--err); font-size:0.9rem;">
        <?php foreach ($resultado['detalles'] as $det): ?>
          <li><?= e($det) ?></li>
        <?php endforeach; ?>
      </ul>
    <?php endif; ?>
  <?php endif; ?>

  <div class="form-grid" style="align-items: end; margin-top: 16px;">
    <form method="post" action="importar_csv.php" enctype="multipart/form-data" style="display:flex; gap:10px; align-items:end; flex-wrap:wrap;">
      <?= csrf_field() ?>
      <input type="hidden" name="modo" value="upload">
      <div class="form-group" style="flex:1; min-width:240px;">
        <label for="archivo_csv">Subir archivo .csv</label>
        <input type="file" id="archivo_csv" name="archivo_csv" accept=".csv,text/csv" required>
      </div>
      <button type="submit" class="btn btn-primary">Importar archivo</button>
    </form>

    <form method="post" action="importar_csv.php">
      <?= csrf_field() ?>
      <input type="hidden" name="modo" value="demo">
      <button type="submit" class="btn btn-secondary">Cargar dataset demo (<code>data/clientes_prueba.csv</code>)</button>
    </form>
  </div>
</div>

<div class="card">
  <h2>Historial de Importaciones</h2>
  <?php if (!$historial): ?>
    <p class="muted">No hay importaciones registradas todavía.</p>
  <?php else: ?>
    <table>
      <thead>
        <tr>
          <th>ID</th>
          <th>Fecha</th>
          <th>Archivo</th>
          <th>Procesados</th>
          <th>Errores</th>
          <th>Ejecutado por</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($historial as $h): ?>
          <tr>
            <td>#<?= (int) $h['id'] ?></td>
            <td><?= e($h['created_at']) ?></td>
            <td><code><?= e($h['nombre_archivo']) ?></code></td>
            <td><span class="badge badge-ok"><?= (int) $h['registros_procesados'] ?></span></td>
            <td><span class="badge <?= (int) $h['registros_error'] > 0 ? 'badge-warn' : 'badge-info' ?>"><?= (int) $h['registros_error'] ?></span></td>
            <td><?= e($h['usuario_nombre']) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>
</div>
<?php
render_footer();
