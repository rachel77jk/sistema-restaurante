<?php
/**
 * RESPALDO DE BASE DE DATOS - Restaurante Inteligente v4
 * Permite al administrador generar copias de seguridad de la base de datos
 * Solo accesible para Administradores
 */
require_once '../includes/config.php';

// Solo administradores pueden acceder
requireRole(['Administrador']);

$db = getDB();

// ============================================================
// PROCESAMIENTO DE RESPALDO (ANTES de cualquier output)
// ============================================================
if (isset($_POST['crear_backup'])) {
    try {
        // Obtener nombre de la base de datos desde la configuracion
        $dbName = DB_NAME ?? 'restaurante_inteligente';
        $fecha = date('Y-m-d_H-i-s');
        $nombreArchivo = "backup_{$dbName}_{$fecha}.sql";

        // Crear directorio de backups si no existe
        $backupDir = dirname(__DIR__) . '/backups/';
        if (!is_dir($backupDir)) {
            mkdir($backupDir, 0755, true);
        }

        $rutaArchivo = $backupDir . $nombreArchivo;

        // Obtener todas las tablas
        $tables = [];
        $result = $db->query("SHOW TABLES");
        while ($row = $result->fetch(PDO::FETCH_NUM)) {
            $tables[] = $row[0];
        }

        $sql = "-- ============================================\n";
        $sql .= "-- RESPALDO BASE DE DATOS: {$dbName}\n";
        $sql .= "-- FECHA: " . date('d/m/Y H:i:s') . "\n";
        $sql .= "-- GENERADO POR: " . $_SESSION['usuario_nombre'] . " ({$_SESSION['usuario_rol']})\n";
        $sql .= "-- SISTEMA: Restaurante Inteligente v4\n";
        $sql .= "-- ============================================\n\n";

        $sql .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

        foreach ($tables as $table) {
            // Estructura de la tabla
            $sql .= "-- ------------------------------------------\n";
            $sql .= "-- Estructura de la tabla: `{$table}`\n";
            $sql .= "-- ------------------------------------------\n";

            $stmt = $db->query("SHOW CREATE TABLE `{$table}`");
            $row = $stmt->fetch(PDO::FETCH_NUM);
            $sql .= "DROP TABLE IF EXISTS `{$table}`;\n";
            $sql .= $row[1] . ";\n\n";

            // Datos de la tabla
            $stmt = $db->query("SELECT * FROM `{$table}`");
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($rows)) {
                $sql .= "-- Datos de la tabla: `{$table}`\n";

                $columns = array_keys($rows[0]);
                $columnList = '`' . implode('`, `', $columns) . '`';

                $batchSize = 100;
                $totalRows = count($rows);

                for ($i = 0; $i < $totalRows; $i += $batchSize) {
                    $batch = array_slice($rows, $i, $batchSize);
                    $values = [];

                    foreach ($batch as $rowData) {
                        $rowValues = [];
                        foreach ($rowData as $value) {
                            if ($value === null) {
                                $rowValues[] = 'NULL';
                            } else {
                                $rowValues[] = $db->quote($value);
                            }
                        }
                        $values[] = '(' . implode(', ', $rowValues) . ')';
                    }

                    $sql .= "INSERT INTO `{$table}` ({$columnList}) VALUES\n";
                    $sql .= implode(",\n", $values) . ";\n";
                }
                $sql .= "\n";
            }
        }

        $sql .= "SET FOREIGN_KEY_CHECKS = 1;\n";
        $sql .= "\n-- ============================================\n";
        $sql .= "-- FIN DEL RESPALDO\n";
        $sql .= "-- ============================================\n";

        // Guardar archivo
        if (file_put_contents($rutaArchivo, $sql) === false) {
            throw new Exception('No se pudo escribir el archivo de respaldo');
        }

        // Descargar el archivo
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Content-Length: ' . filesize($rutaArchivo));
        header('Cache-Control: no-cache, must-revalidate');

        readfile($rutaArchivo);
        exit;

    } catch (Exception $e) {
        redirect('backup.php', 'error', 'Error al crear el respaldo: ' . $e->getMessage());
    }
}

// Eliminar un backup existente
if (isset($_POST['eliminar_backup'])) {
    $archivo = basename($_POST['archivo']);
    $ruta = dirname(__DIR__) . '/backups/' . $archivo;

    if (file_exists($ruta) && is_file($ruta)) {
        unlink($ruta);
        redirect('backup.php', 'success', 'Respaldo eliminado correctamente');
    } else {
        redirect('backup.php', 'error', 'El archivo no existe');
    }
}

// ============================================================
// AQUI EMPIEZA EL OUTPUT HTML
// ============================================================
$pageTitle = 'Respaldo de Base de Datos';
require_once 'header.php';

// Listar backups existentes
$backupDir = dirname(__DIR__) . '/backups/';
$backups = [];

if (is_dir($backupDir)) {
    $files = glob($backupDir . 'backup_*.sql');
    foreach ($files as $file) {
        $backups[] = [
            'nombre' => basename($file),
            'tamano' => filesize($file),
            'fecha' => filemtime($file)
        ];
    }

    // Ordenar por fecha descendente
    usort($backups, function($a, $b) {
        return $b['fecha'] - $a['fecha'];
    });
}

// Estadisticas de la base de datos
$totalTablas = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()")->fetchColumn();
$totalRegistros = 0;
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    $count = $db->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
    $totalRegistros += $count;
}

function formatBytes($bytes) {
    if ($bytes >= 1073741824) return number_format($bytes / 1073741824, 2) . ' GB';
    if ($bytes >= 1048576) return number_format($bytes / 1048576, 2) . ' MB';
    if ($bytes >= 1024) return number_format($bytes / 1024, 2) . ' KB';
    return $bytes . ' B';
}
?>

<h1 class="page-title"><i class="fas fa-database"></i> Respaldo de Base de Datos</h1>
<p class="page-subtitle">Genera copias de seguridad de toda la informacion del sistema</p>

<!-- Estadisticas de la BD -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-table"></i></div>
        <div class="stat-info">
            <h3><?php echo $totalTablas; ?></h3>
            <p>Tablas</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-list-ol"></i></div>
        <div class="stat-info">
            <h3><?php echo number_format($totalRegistros); ?></h3>
            <p>Registros Totales</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-save"></i></div>
        <div class="stat-info">
            <h3><?php echo count($backups); ?></h3>
            <p>Respaldos Guardados</p>
        </div>
    </div>
</div>

<div class="grid grid-2">
    <!-- Panel de Crear Respaldo -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-cloud-download-alt"></i> Crear Nuevo Respaldo</h3>
        </div>
        <div class="card-body">
            <div class="empty-state" style="padding: 2rem;">
                <i class="fas fa-database" style="font-size: 4rem; color: var(--color-primary);"></i>
                <h3 style="margin-top: 1rem;">Copia de Seguridad Completa</h3>
                <p style="color: var(--color-gray); margin: 1rem 0;">
                    Se generara un archivo SQL con toda la estructura y datos de la base de datos.
                    El archivo incluye todas las tablas, registros y configuraciones del sistema.
                </p>

                <div style="background: #f8f9fa; border-radius: var(--radius-sm); padding: 1rem; margin: 1rem 0; text-align: left;">
                    <p style="margin: 0.3rem 0; font-size: 0.9rem;"><i class="fas fa-check text-success"></i> Estructura de todas las tablas</p>
                    <p style="margin: 0.3rem 0; font-size: 0.9rem;"><i class="fas fa-check text-success"></i> Todos los registros de datos</p>
                    <p style="margin: 0.3rem 0; font-size: 0.9rem;"><i class="fas fa-check text-success"></i> Relaciones y restricciones (FK)</p>
                    <p style="margin: 0.3rem 0; font-size: 0.9rem;"><i class="fas fa-check text-success"></i> Formato SQL estandar</p>
                </div>

                <form method="POST" style="margin-top: 1.5rem;">
                    <button type="submit" name="crear_backup" class="btn btn-success btn-lg btn-block">
                        <i class="fas fa-download"></i> Descargar Respaldo (.sql)
                    </button>
                </form>

                <p style="font-size: 0.8rem; color: var(--color-gray); margin-top: 1rem;">
                    <i class="fas fa-info-circle"></i> El archivo se descargara automaticamente en su navegador.
                </p>
            </div>
        </div>
    </div>

    <!-- Lista de Respaldos Existentes -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-history"></i> Respaldos Existentes</h3>
        </div>
        <div class="card-body">
            <?php if (!empty($backups)): ?>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre del Archivo</th>
                            <th>Tamano</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($backups as $backup): 
                            // Extraer info del nombre del archivo
                            preg_match('/backup_(.+?)_(\d{4}-\d{2}-\d{2})_(\d{2}-\d{2}-\d{2})\.sql/', $backup['nombre'], $matches);
                            $dbName = $matches[1] ?? 'desconocido';
                            $fechaStr = isset($matches[2]) ? str_replace('-', '/', $matches[2]) : '';
                            $horaStr = isset($matches[3]) ? str_replace('-', ':', $matches[3]) : '';
                        ?>
                        <tr>
                            <td>
                                <i class="fas fa-file-code" style="color: var(--color-primary);"></i>
                                <strong><?php echo $backup['nombre']; ?></strong>
                                <br><small class="text-muted">BD: <?php echo $dbName; ?></small>
                            </td>
                            <td><span class="badge badge-info"><?php echo formatBytes($backup['tamano']); ?></span></td>
                            <td><?php echo date('d/m/Y H:i', $backup['fecha']); ?></td>
                            <td>
                                <div class="d-flex gap-1">
                                    <a href="../backups/<?php echo $backup['nombre']; ?>" download class="btn btn-sm btn-primary" title="Descargar">
                                        <i class="fas fa-download"></i>
                                    </a>
                                    <form method="POST" style="display: inline;" onsubmit="return confirm('Eliminar este respaldo?');">
                                        <input type="hidden" name="eliminar_backup" value="1">
                                        <input type="hidden" name="archivo" value="<?php echo $backup['nombre']; ?>">
                                        <button type="submit" class="btn btn-sm btn-danger" title="Eliminar">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-folder-open" style="font-size: 4rem;"></i>
                <h3>No hay respaldos guardados</h3>
                <p>Los respaldos generados se guardaran aqui automaticamente</p>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Informacion Adicional -->
<div class="card mt-3">
    <div class="card-header">
        <h3><i class="fas fa-info-circle"></i> Informacion Importante</h3>
    </div>
    <div class="card-body">
        <div class="grid grid-2">
            <div>
                <h4 style="color: var(--color-secondary); margin-bottom: 0.5rem;"><i class="fas fa-shield-alt"></i> Recomendaciones</h4>
                <ul style="line-height: 1.8; color: var(--color-gray);">
                    <li>Realice respaldos periodicamente (semanal o mensual)</li>
                    <li>Guarde los archivos en una ubicacion segura externa</li>
                    <li>Verifique que los respaldos se descarguen correctamente</li>
                    <li>Mantenga al menos 3 copias de seguridad recientes</li>
                </ul>
            </div>
            <div>
                <h4 style="color: var(--color-secondary); margin-bottom: 0.5rem;"><i class="fas fa-exclamation-triangle"></i> Consideraciones</h4>
                <ul style="line-height: 1.8; color: var(--color-gray);">
                    <li>El respaldo incluye toda la base de datos completa</li>
                    <li>El archivo SQL puede ser restaurado con phpMyAdmin</li>
                    <li>Los respaldos locales se guardan en <code>/backups/</code></li>
                    <li>Se recomienda descargar y almacenar en otro dispositivo</li>
                </ul>
            </div>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>
