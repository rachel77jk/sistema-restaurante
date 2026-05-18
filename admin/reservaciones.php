<?php
$pageTitle = 'Gestion de Reservaciones';

require_once '../includes/config.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $cliente_id = intval($_POST['cliente_id'] ?? 0);
        $mesa_id = !empty($_POST['mesa_id']) ? intval($_POST['mesa_id']) : null;
        $fecha_reserva = $_POST['fecha_reserva'] ?? '';
        $hora_reserva = $_POST['hora_reserva'] ?? '';
        $num_personas = intval($_POST['num_personas'] ?? 2);
        $estado = $_POST['estado'] ?? 'Pendiente';
        $notas = sanitize($_POST['notas'] ?? '');
        $id = $_POST['id'] ?? null;

        if (empty($fecha_reserva) || empty($hora_reserva)) {
            redirect('reservaciones.php', 'error', 'Fecha y hora son obligatorias');
        }

        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO reservaciones (cliente_id, mesa_id, fecha_reserva, hora_reserva, num_personas, estado, notas) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$cliente_id, $mesa_id, $fecha_reserva, $hora_reserva, $num_personas, $estado, $notas]);
            createNotification('Nueva reservacion', 'Reservacion para ' . $fecha_reserva . ' a las ' . $hora_reserva, 'Reservacion');
            redirect('reservaciones.php', 'success', 'Reservacion creada correctamente');
        } else {
            $stmt = $db->prepare("UPDATE reservaciones SET cliente_id = ?, mesa_id = ?, fecha_reserva = ?, hora_reserva = ?, num_personas = ?, estado = ?, notas = ? WHERE id = ?");
            $stmt->execute([$cliente_id, $mesa_id, $fecha_reserva, $hora_reserva, $num_personas, $estado, $notas, $id]);
            redirect('reservaciones.php', 'success', 'Reservacion actualizada correctamente');
        }
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $stmt = $db->prepare("DELETE FROM reservaciones WHERE id = ?");
        $stmt->execute([$id]);
        redirect('reservaciones.php', 'success', 'Reservacion eliminada correctamente');
    }
}

// Filtros
$estado_filter = $_GET['estado'] ?? '';
$fecha_filter = $_GET['fecha'] ?? '';

$sql = "SELECT r.*, u.nombre as cliente_nombre, u.telefono as cliente_telefono, m.numero as mesa_numero, m.ubicacion as mesa_ubicacion FROM reservaciones r LEFT JOIN usuarios u ON r.cliente_id = u.id LEFT JOIN mesas m ON r.mesa_id = m.id WHERE 1=1";
$params = [];

if (!empty($estado_filter)) {
    $sql .= " AND r.estado = ?";
    $params[] = $estado_filter;
}
if (!empty($fecha_filter)) {
    $sql .= " AND r.fecha_reserva = ?";
    $params[] = $fecha_filter;
}

$sql .= " ORDER BY r.fecha_reserva DESC, r.hora_reserva DESC";
$result = paginate($sql, $params, 12);
$reservaciones = $result['data'];


require_once 'header.php';

$db = getDB();

$clientes = $db->query("SELECT id, nombre FROM usuarios WHERE rol = 'Cliente' AND activo = 1 ORDER BY nombre")->fetchAll();
$mesas = $db->query("SELECT id, numero, ubicacion FROM mesas WHERE estado = 'Disponible' ORDER BY numero")->fetchAll();
?>

<h1 class="page-title"><i class="fas fa-calendar-check"></i> Gestion de Reservaciones</h1>
<p class="page-subtitle">Administra las reservaciones de mesas</p>

<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <select name="estado" class="form-control" style="max-width: 150px;" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                <option value="Pendiente" <?php echo $estado_filter == 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                <option value="Confirmada" <?php echo $estado_filter == 'Confirmada' ? 'selected' : ''; ?>>Confirmada</option>
                <option value="Cancelada" <?php echo $estado_filter == 'Cancelada' ? 'selected' : ''; ?>>Cancelada</option>
                <option value="Completada" <?php echo $estado_filter == 'Completada' ? 'selected' : ''; ?>>Completada</option>
            </select>
            <input type="date" name="fecha" class="form-control" value="<?php echo $fecha_filter; ?>" style="max-width: 150px;" onchange="this.form.submit()">
            <?php if ($estado_filter || $fecha_filter): ?>
            <a href="reservaciones.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpiar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h3>Lista de Reservaciones</h3>
        <button class="btn btn-success" onclick="openModal('createModal')">
            <i class="fas fa-plus"></i> Nueva Reservacion
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Mesa</th>
                        <th>Fecha</th>
                        <th>Hora</th>
                        <th>Personas</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($reservaciones as $r): ?>
                    <tr>
                        <td><strong>#<?php echo $r['id']; ?></strong></td>
                        <td>
                            <div>
                                <strong><?php echo $r['cliente_nombre'] ?: 'Sin cliente'; ?></strong>
                                <?php if ($r['cliente_telefono']): ?>
                                <br><small class="text-muted"><i class="fas fa-phone"></i> <?php echo $r['cliente_telefono']; ?></small>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php echo $r['mesa_numero'] ? $r['mesa_numero'] . ' <small class="text-muted">(' . $r['mesa_ubicacion'] . ')</small>' : '<span class="text-muted">Sin asignar</span>'; ?>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($r['fecha_reserva'])); ?></td>
                        <td><?php echo date('H:i', strtotime($r['hora_reserva'])); ?></td>
                        <td><span class="badge badge-info"><i class="fas fa-users"></i> <?php echo $r['num_personas']; ?></span></td>
                        <td>
                            <span class="badge <?php echo getEstadoBadge($r['estado']); ?>">
                                <?php echo $r['estado']; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick='editRes(<?php echo json_encode($r); ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Eliminar esta reservacion?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $r['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($reservaciones)): ?>
        <div class="empty-state">
            <i class="fas fa-calendar-times"></i>
            <h3>No hay reservaciones</h3>
        </div>
        <?php endif; ?>

        <?php if ($result['totalPages'] > 1): ?>
        <div class="pagination">
            <?php if ($result['hasPrev']): ?>
            <a href="?page=<?php echo $result['page'] - 1; ?>&estado=<?php echo urlencode($estado_filter); ?>&fecha=<?php echo urlencode($fecha_filter); ?>"><i class="fas fa-chevron-left"></i></a>
            <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
            <?php if ($i == $result['page']): ?>
            <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="?page=<?php echo $i; ?>&estado=<?php echo urlencode($estado_filter); ?>&fecha=<?php echo urlencode($fecha_filter); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
            <?php endfor; ?>
            <?php if ($result['hasNext']): ?>
            <a href="?page=<?php echo $result['page'] + 1; ?>&estado=<?php echo urlencode($estado_filter); ?>&fecha=<?php echo urlencode($fecha_filter); ?>"><i class="fas fa-chevron-right"></i></a>
            <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Crear -->
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Nueva Reservacion</h3>
            <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label class="form-label">Cliente</label>
                    <select name="cliente_id" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($clientes as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Mesa (opcional)</label>
                    <select name="mesa_id" class="form-control">
                        <option value="">Sin asignar</option>
                        <?php foreach ($mesas as $m): ?>
                        <option value="<?php echo $m['id']; ?>"><?php echo $m['numero']; ?> - <?php echo $m['ubicacion']; ?> (<?php echo $m['capacidad']; ?> pers.)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha_reserva" class="form-control" required min="<?php echo date('Y-m-d'); ?>">
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hora</label>
                        <input type="time" name="hora_reserva" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Numero de Personas</label>
                    <input type="number" name="num_personas" class="form-control" value="2" min="1" max="20" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-control">
                        <option value="Pendiente">Pendiente</option>
                        <option value="Confirmada">Confirmada</option>
                        <option value="Cancelada">Cancelada</option>
                        <option value="Completada">Completada</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('createModal')">Cancelar</button>
                <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Editar -->
<div class="modal-overlay" id="editModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-edit"></i> Editar Reservacion</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label class="form-label">Cliente</label>
                    <select name="cliente_id" id="edit_cliente" class="form-control" required>
                        <?php foreach ($clientes as $c): ?>
                        <option value="<?php echo $c['id']; ?>"><?php echo $c['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Mesa</label>
                    <select name="mesa_id" id="edit_mesa" class="form-control">
                        <option value="">Sin asignar</option>
                        <?php foreach ($mesas as $m): ?>
                        <option value="<?php echo $m['id']; ?>"><?php echo $m['numero']; ?> - <?php echo $m['ubicacion']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="fecha_reserva" id="edit_fecha" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Hora</label>
                        <input type="time" name="hora_reserva" id="edit_hora" class="form-control" required>
                    </div>
                </div>
                <div class="form-group">
                    <label class="form-label">Numero de Personas</label>
                    <input type="number" name="num_personas" id="edit_personas" class="form-control" min="1" max="20" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select name="estado" id="edit_estado" class="form-control">
                        <option value="Pendiente">Pendiente</option>
                        <option value="Confirmada">Confirmada</option>
                        <option value="Cancelada">Cancelada</option>
                        <option value="Completada">Completada</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" id="edit_notas" class="form-control" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar</button>
            </div>
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function editRes(r) {
    document.getElementById('edit_id').value = r.id;
    document.getElementById('edit_cliente').value = r.cliente_id;
    document.getElementById('edit_mesa').value = r.mesa_id || '';
    document.getElementById('edit_fecha').value = r.fecha_reserva;
    document.getElementById('edit_hora').value = r.hora_reserva;
    document.getElementById('edit_personas').value = r.num_personas;
    document.getElementById('edit_estado').value = r.estado;
    document.getElementById('edit_notas').value = r.notas || '';
    openModal('editModal');
}
document.querySelectorAll('.modal-overlay').forEach(function(o) {
    o.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
});
</script>

<?php require_once 'footer.php'; ?>