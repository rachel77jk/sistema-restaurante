<?php
$pageTitle = 'Gestion de Categorias';

require_once '../includes/config.php';
$db = getDB();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $nombre = sanitize($_POST['nombre'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $icono = sanitize($_POST['icono'] ?? 'fa-utensils');
        $orden = intval($_POST['orden'] ?? 0);
        $activo = isset($_POST['activo']) ? 1 : 0;
        $id = $_POST['id'] ?? null;

        if (empty($nombre)) {
            redirect('categorias.php', 'error', 'El nombre es obligatorio');
        }

        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO categorias (nombre, descripcion, icono, orden, activo) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $descripcion, $icono, $orden, $activo]);
            redirect('categorias.php', 'success', 'Categoria creada correctamente');
        } else {
            $stmt = $db->prepare("UPDATE categorias SET nombre = ?, descripcion = ?, icono = ?, orden = ?, activo = ? WHERE id = ?");
            $stmt->execute([$nombre, $descripcion, $icono, $orden, $activo, $id]);
            redirect('categorias.php', 'success', 'Categoria actualizada correctamente');
        }
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        try {
            $stmt = $db->prepare("DELETE FROM categorias WHERE id = ?");
            $stmt->execute([$id]);
            redirect('categorias.php', 'success', 'Categoria eliminada correctamente');
        } catch (PDOException $e) {
            redirect('categorias.php', 'error', 'No se puede eliminar: tiene productos asociados');
        }
    }
}


require_once 'header.php';

$db = getDB();

$categorias = $db->query("SELECT c.*, COUNT(p.id) as total_productos FROM categorias c LEFT JOIN productos p ON c.id = p.categoria_id GROUP BY c.id ORDER BY c.orden, c.nombre")->fetchAll();
?>

<h1 class="page-title"><i class="fas fa-tags"></i> Gestion de Categorias</h1>
<p class="page-subtitle">Organiza tus productos por categorias</p>

<div class="card">
    <div class="card-header">
        <h3>Lista de Categorias</h3>
        <button class="btn btn-success" onclick="openModal('createModal')">
            <i class="fas fa-plus"></i> Nueva Categoria
        </button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Icono</th>
                        <th>Nombre</th>
                        <th>Descripcion</th>
                        <th>Productos</th>
                        <th>Estado</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($categorias as $cat): ?>
                    <tr>
                        <td><?php echo $cat['orden']; ?></td>
                        <td><i class="fas <?php echo $cat['icono']; ?>" style="font-size: 1.5rem; color: var(--color-primary);"></i></td>
                        <td><strong><?php echo $cat['nombre']; ?></strong></td>
                        <td><?php echo $cat['descripcion'] ?: '-'; ?></td>
                        <td><span class="badge badge-info"><?php echo $cat['total_productos']; ?> productos</span></td>
                        <td>
                            <span class="badge <?php echo $cat['activo'] ? 'badge-success' : 'badge-danger'; ?>">
                                <?php echo $cat['activo'] ? 'Activa' : 'Inactiva'; ?>
                            </span>
                        </td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick='editCat(<?php echo json_encode($cat); ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Eliminar esta categoria?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $cat['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal Crear -->
<div class="modal-overlay" id="createModal">
    <div class="modal">
        <div class="modal-header">
            <h3><i class="fas fa-plus"></i> Nueva Categoria</h3>
            <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripcion</label>
                    <textarea name="descripcion" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Icono (clase FontAwesome)</label>
                    <input type="text" name="icono" class="form-control" value="fa-utensils" placeholder="fa-utensils">
                </div>
                <div class="form-group">
                    <label class="form-label">Orden</label>
                    <input type="number" name="orden" class="form-control" value="0">
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="activo" value="1" checked style="width: auto;">
                        <span>Categoria activa</span>
                    </label>
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
            <h3><i class="fas fa-edit"></i> Editar Categoria</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripcion</label>
                    <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Icono</label>
                    <input type="text" name="icono" id="edit_icono" class="form-control">
                </div>
                <div class="form-group">
                    <label class="form-label">Orden</label>
                    <input type="number" name="orden" id="edit_orden" class="form-control">
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="activo" id="edit_activo" value="1" style="width: auto;">
                        <span>Categoria activa</span>
                    </label>
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
function editCat(cat) {
    document.getElementById('edit_id').value = cat.id;
    document.getElementById('edit_nombre').value = cat.nombre;
    document.getElementById('edit_descripcion').value = cat.descripcion || '';
    document.getElementById('edit_icono').value = cat.icono;
    document.getElementById('edit_orden').value = cat.orden;
    document.getElementById('edit_activo').checked = cat.activo == 1;
    openModal('editModal');
}
document.querySelectorAll('.modal-overlay').forEach(function(o) {
    o.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
});
</script>

<?php require_once 'footer.php'; ?>