<?php
$pageTitle = 'Gestion de Mesas';

require_once '../includes/config.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $numero = sanitize($_POST['numero'] ?? '');
        $capacidad = intval($_POST['capacidad'] ?? 4);
        $ubicacion = sanitize($_POST['ubicacion'] ?? '');
        $estado = $_POST['estado'] ?? 'Disponible';
        $id = $_POST['id'] ?? null;

        if (empty($numero)) {
            redirect('mesas.php', 'error', 'El numero de mesa es obligatorio');
        }

        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO mesas (numero, capacidad, ubicacion, estado) VALUES (?, ?, ?, ?)");
            $stmt->execute([$numero, $capacidad, $ubicacion, $estado]);
            redirect('mesas.php', 'success', 'Mesa creada correctamente');
        } else {
            $stmt = $db->prepare("UPDATE mesas SET numero = ?, capacidad = ?, ubicacion = ?, estado = ? WHERE id = ?");
            $stmt->execute([$numero, $capacidad, $ubicacion, $estado, $id]);
            redirect('mesas.php', 'success', 'Mesa actualizada correctamente');
        }
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $stmt = $db->prepare("DELETE FROM mesas WHERE id = ?");
        $stmt->execute([$id]);
        redirect('mesas.php', 'success', 'Mesa eliminada correctamente');
    }
}


require_once 'header.php';

$db = getDB();

$mesas = $db->query("SELECT * FROM mesas ORDER BY numero")->fetchAll();
?>

<h1 class="page-title"><i class="fas fa-chair"></i> Gestion de Mesas</h1>
<p class="page-subtitle">Administra las mesas del restaurante</p>

<div class="card">
    <div class="card-header">
        <h3>Mapa de Mesas</h3>
        <button class="btn btn-success" onclick="openModal('createModal')">
            <i class="fas fa-plus"></i> Nueva Mesa
        </button>
    </div>
    <div class="card-body">
        <div class="mesa-grid">
            <?php foreach ($mesas as $mesa): ?>
            <div class="mesa-card <?php echo $mesa['estado']; ?>" onclick='editMesa(<?php echo json_encode($mesa); ?>)'>
                <div class="mesa-icon">
                    <i class="fas fa-<?php echo $mesa['estado'] == 'Disponible' ? 'chair' : ($mesa['estado'] == 'Ocupada' ? 'user' : ($mesa['estado'] == 'Reservada' ? 'clock' : 'wrench')); ?>"></i>
                </div>
                <div class="mesa-numero"><?php echo $mesa['numero']; ?></div>
                <div class="mesa-capacidad"><i class="fas fa-users"></i> <?php echo $mesa['capacidad']; ?> personas</div>
                <div style="margin-top: 0.5rem;">
                    <span class="badge <?php echo getEstadoBadge($mesa['estado']); ?>"><?php echo $mesa['estado']; ?></span>
                </div>
                <?php if ($mesa['ubicacion']): ?>
                <div style="font-size: 0.8rem; color: var(--color-gray); margin-top: 0.3rem;">
                    <i class="fas fa-map-marker-alt"></i> <?php echo $mesa['ubicacion']; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($mesas)): ?>
        <div class="empty-state">
            <i class="fas fa-chair"></i>
            <h3>No hay mesas registradas</h3>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Crear -->
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Nueva Mesa</h3>
            <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label class="form-label">Numero de Mesa</label>
                    <input type="text" name="numero" class="form-control" placeholder="M-01" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Capacidad (personas)</label>
                    <input type="number" name="capacidad" class="form-control" value="4" min="1" max="20" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ubicacion</label>
                    <input type="text" name="ubicacion" class="form-control" placeholder="Terraza, Salon Principal, etc.">
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select name="estado" class="form-control">
                        <option value="Disponible">Disponible</option>
                        <option value="Ocupada">Ocupada</option>
                        <option value="Reservada">Reservada</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                    </select>
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
            <h3><i class="fas fa-edit"></i> Editar Mesa</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label class="form-label">Numero de Mesa</label>
                    <input type="text" name="numero" id="edit_numero" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Capacidad</label>
                    <input type="number" name="capacidad" id="edit_capacidad" class="form-control" min="1" max="20" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Ubicacion</label>
                    <input type="text" name="ubicacion" id="edit_ubicacion" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Estado</label>
                    <select name="estado" id="edit_estado" class="form-control">
                        <option value="Disponible">Disponible</option>
                        <option value="Ocupada">Ocupada</option>
                        <option value="Reservada">Reservada</option>
                        <option value="Mantenimiento">Mantenimiento</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="closeModal('editModal')">Cancelar</button>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Actualizar</button>
                <button type="button" class="btn btn-danger" onclick="if(confirm('Eliminar esta mesa?')){document.getElementById('deleteForm').submit();}">
                    <i class="fas fa-trash"></i> Eliminar
                </button>
            </div>
        </form>
        <form method="POST" id="deleteForm" style="display: none;">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete_id">
        </form>
    </div>
</div>

<script>
function openModal(id) { document.getElementById(id).classList.add('active'); }
function closeModal(id) { document.getElementById(id).classList.remove('active'); }
function editMesa(mesa) {
    document.getElementById('edit_id').value = mesa.id;
    document.getElementById('delete_id').value = mesa.id;
    document.getElementById('edit_numero').value = mesa.numero;
    document.getElementById('edit_capacidad').value = mesa.capacidad;
    document.getElementById('edit_ubicacion').value = mesa.ubicacion || '';
    document.getElementById('edit_estado').value = mesa.estado;
    openModal('editModal');
}
document.querySelectorAll('.modal-overlay').forEach(function(o) {
    o.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
});
</script>

<?php require_once 'footer.php'; ?>