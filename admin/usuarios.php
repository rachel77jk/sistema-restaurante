<?php
$pageTitle = 'Gestion de Usuarios';

require_once '../includes/config.php';
$db = getDB();

// Procesar acciones
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $nombre = sanitize($_POST['nombre'] ?? '');
        $email = sanitize($_POST['email'] ?? '');
        $rol = $_POST['rol'] ?? 'Cliente';
        $telefono = sanitize($_POST['telefono'] ?? '');
        $direccion = sanitize($_POST['direccion'] ?? '');
        $activo = isset($_POST['activo']) ? 1 : 0;
        $id = $_POST['id'] ?? null;

        if (empty($nombre) || empty($email)) {
            redirect('usuarios.php', 'error', 'Nombre y email son obligatorios');
        }

        if ($action === 'create') {
            $password = password_hash($_POST['password'] ?? 'admin123', PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol, telefono, direccion, activo) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$nombre, $email, $password, $rol, $telefono, $direccion, $activo]);
            createNotification('Nuevo usuario', 'Se registro el usuario: ' . $nombre, 'Sistema');
            redirect('usuarios.php', 'success', 'Usuario creado correctamente');
        } else {
            $sql = "UPDATE usuarios SET nombre = ?, email = ?, rol = ?, telefono = ?, direccion = ?, activo = ?";
            $params = [$nombre, $email, $rol, $telefono, $direccion, $activo];

            if (!empty($_POST['password'])) {
                $sql .= ", password = ?";
                $params[] = password_hash($_POST['password'], PASSWORD_DEFAULT);
            }
            $sql .= " WHERE id = ?";
            $params[] = $id;

            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            redirect('usuarios.php', 'success', 'Usuario actualizado correctamente');
        }
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $stmt = $db->prepare("DELETE FROM usuarios WHERE id = ?");
        $stmt->execute([$id]);
        redirect('usuarios.php', 'success', 'Usuario eliminado correctamente');
    }

    if ($action === 'toggle') {
        $id = $_POST['id'] ?? 0;
        $stmt = $db->prepare("UPDATE usuarios SET activo = NOT activo WHERE id = ?");
        $stmt->execute([$id]);
        redirect('usuarios.php', 'success', 'Estado actualizado');
    }
}


require_once 'header.php';

$db = getDB();

// Obtener usuarios
$search = $_GET['search'] ?? '';
$sql = "SELECT * FROM usuarios WHERE 1=1";
$params = [];

if (!empty($search)) {
    $sql .= " AND (nombre LIKE ? OR email LIKE ? OR telefono LIKE ?)";
    $params = ["%$search%", "%$search%", "%$search%"];
}

$sql .= " ORDER BY fecha_registro DESC";
$result = paginate($sql, $params, 10);
$usuarios = $result['data'];
?>

<h1 class="page-title"><i class="fas fa-users"></i> Gestion de Usuarios</h1>
<p class="page-subtitle">Administra los usuarios del sistema</p>

<div class="card">
    <div class="card-header">
        <div class="d-flex gap-2" style="flex: 1;">
            <form method="GET" class="d-flex gap-2" style="flex: 1; max-width: 400px;">
                <input type="text" name="search" class="form-control" placeholder="Buscar usuario..." value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i>
                </button>
            </form>
        </div>
        <button class="btn btn-success" onclick="openModal('createModal')">
            <i class="fas fa-plus"></i> Nuevo Usuario
        </button>
    </div>

    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nombre</th>
                        <th>Email</th>
                        <th>Rol</th>
                        <th>Telefono</th>
                        <th>Estado</th>
                        <th>Registro</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($usuarios as $u): ?>
                    <tr>
                        <td><?php echo $u['id']; ?></td>
                        <td>
                            <div class="d-flex align-center gap-2">
                                <div class="avatar" style="width: 35px; height: 35px; font-size: 0.9rem;">
                                    <?php echo strtoupper(substr($u['nombre'], 0, 1)); ?>
                                </div>
                                <strong><?php echo $u['nombre']; ?></strong>
                            </div>
                        </td>
                        <td><?php echo $u['email']; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $u['rol'] == 'Administrador' ? 'danger' : ($u['rol'] == 'Cocinero' ? 'warning' : ($u['rol'] == 'Mesero' ? 'info' : 'primary')); ?>">
                                <i class="fas <?php echo getRolIcon($u['rol']); ?>"></i> <?php echo $u['rol']; ?>
                            </span>
                        </td>
                        <td><?php echo $u['telefono'] ?: '-'; ?></td>
                        <td>
                            <form method="POST" style="display: inline;">
                                <input type="hidden" name="action" value="toggle">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="badge <?php echo $u['activo'] ? 'badge-success' : 'badge-danger'; ?>" style="border: none; cursor: pointer;">
                                    <i class="fas <?php echo $u['activo'] ? 'fa-check' : 'fa-times'; ?>"></i>
                                    <?php echo $u['activo'] ? 'Activo' : 'Inactivo'; ?>
                                </button>
                            </form>
                        </td>
                        <td><?php echo date('d/m/Y', strtotime($u['fecha_registro'])); ?></td>
                        <td>
                            <button class="btn btn-sm btn-info" onclick='editUser(<?php echo json_encode($u); ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Esta seguro de eliminar este usuario?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($usuarios)): ?>
                    <tr>
                        <td colspan="8">
                            <div class="empty-state">
                                <i class="fas fa-users-slash"></i>
                                <h3>No se encontraron usuarios</h3>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Paginacion -->
        <?php if ($result['totalPages'] > 1): ?>
        <div class="pagination">
            <?php if ($result['hasPrev']): ?>
            <a href="?page=<?php echo $result['page'] - 1; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-left"></i></a>
            <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
            <?php if ($i == $result['page']): ?>
            <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
            <?php endfor; ?>

            <?php if ($result['hasNext']): ?>
            <a href="?page=<?php echo $result['page'] + 1; ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-right"></i></a>
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
            <h3><i class="fas fa-user-plus"></i> Nuevo Usuario</h3>
            <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">

                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Correo Electronico</label>
                    <input type="email" name="email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Contrasena</label>
                    <input type="password" name="password" class="form-control" placeholder="Dejar vacio para 'admin123'">
                </div>

                <div class="form-group">
                    <label class="form-label">Rol</label>
                    <select name="rol" class="form-control" required>
                        <option value="Administrador">Administrador</option>
                        <option value="Cocinero">Cocinero</option>
                        <option value="Mesero">Mesero</option>
                        <option value="Cliente" selected>Cliente</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Telefono</label>
                    <input type="text" name="telefono" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Direccion</label>
                    <textarea name="direccion" class="form-control" rows="2"></textarea>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="activo" value="1" checked style="width: auto;">
                        <span>Usuario activo</span>
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
            <h3><i class="fas fa-edit"></i> Editar Usuario</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">

                <div class="form-group">
                    <label class="form-label">Nombre Completo</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Correo Electronico</label>
                    <input type="email" name="email" id="edit_email" class="form-control" required>
                </div>

                <div class="form-group">
                    <label class="form-label">Nueva Contrasena (dejar vacio para no cambiar)</label>
                    <input type="password" name="password" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Rol</label>
                    <select name="rol" id="edit_rol" class="form-control" required>
                        <option value="Administrador">Administrador</option>
                        <option value="Cocinero">Cocinero</option>
                        <option value="Mesero">Mesero</option>
                        <option value="Cliente">Cliente</option>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Telefono</label>
                    <input type="text" name="telefono" id="edit_telefono" class="form-control">
                </div>

                <div class="form-group">
                    <label class="form-label">Direccion</label>
                    <textarea name="direccion" id="edit_direccion" class="form-control" rows="2"></textarea>
                </div>

                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="activo" id="edit_activo" value="1" style="width: auto;">
                        <span>Usuario activo</span>
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
function openModal(id) {
    document.getElementById(id).classList.add('active');
}
function closeModal(id) {
    document.getElementById(id).classList.remove('active');
}
function editUser(user) {
    document.getElementById('edit_id').value = user.id;
    document.getElementById('edit_nombre').value = user.nombre;
    document.getElementById('edit_email').value = user.email;
    document.getElementById('edit_rol').value = user.rol;
    document.getElementById('edit_telefono').value = user.telefono || '';
    document.getElementById('edit_direccion').value = user.direccion || '';
    document.getElementById('edit_activo').checked = user.activo == 1;
    openModal('editModal');
}

// Cerrar modal al hacer click fuera
document.querySelectorAll('.modal-overlay').forEach(function(overlay) {
    overlay.addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.remove('active');
        }
    });
});
</script>

<?php require_once 'footer.php'; ?>