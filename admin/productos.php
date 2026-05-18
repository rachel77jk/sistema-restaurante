<?php
$pageTitle = 'Gestion de Productos';

require_once '../includes/config.php';
$db = getDB();


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $categoria_id = intval($_POST['categoria_id'] ?? 0);
        $nombre = sanitize($_POST['nombre'] ?? '');
        $descripcion = sanitize($_POST['descripcion'] ?? '');
        $precio = floatval($_POST['precio'] ?? 0);
        $disponible = isset($_POST['disponible']) ? 1 : 0;
        $destacado = isset($_POST['destacado']) ? 1 : 0;
        $id = $_POST['id'] ?? null;

        if (empty($nombre) || $precio <= 0 || $categoria_id <= 0) {
            redirect('productos.php', 'error', 'Complete todos los campos obligatorios');
        }

        $imagen = 'default.jpg';
        if (!empty($_FILES['imagen']['name'])) {
            $upload = uploadImage($_FILES['imagen']);
            if ($upload['success']) {
                $imagen = $upload['filename'];
            }
        }

        if ($action === 'create') {
            $stmt = $db->prepare("INSERT INTO productos (categoria_id, nombre, descripcion, precio, imagen, disponible, destacado) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$categoria_id, $nombre, $descripcion, $precio, $imagen, $disponible, $destacado]);
            redirect('productos.php', 'success', 'Producto creado correctamente');
        } else {
            $sql = "UPDATE productos SET categoria_id = ?, nombre = ?, descripcion = ?, precio = ?, disponible = ?, destacado = ?";
            $params = [$categoria_id, $nombre, $descripcion, $precio, $disponible, $destacado];
            if ($imagen !== 'default.jpg') {
                $sql .= ", imagen = ?";
                $params[] = $imagen;
            }
            $sql .= " WHERE id = ?";
            $params[] = $id;
            $stmt = $db->prepare($sql);
            $stmt->execute($params);
            redirect('productos.php', 'success', 'Producto actualizado correctamente');
        }
    }

    if ($action === 'delete') {
        $id = $_POST['id'] ?? 0;
        $stmt = $db->prepare("DELETE FROM productos WHERE id = ?");
        $stmt->execute([$id]);
        redirect('productos.php', 'success', 'Producto eliminado correctamente');
    }
}

// Filtros
$categoria_filter = $_GET['categoria'] ?? '';
$search = $_GET['search'] ?? '';

$sql = "SELECT p.*, c.nombre as categoria_nombre FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE 1=1";
$params = [];

if (!empty($categoria_filter)) {
    $sql .= " AND p.categoria_id = ?";
    $params[] = $categoria_filter;
}
if (!empty($search)) {
    $sql .= " AND (p.nombre LIKE ? OR p.descripcion LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY p.destacado DESC, p.nombre";
$result = paginate($sql, $params, 12);
$productos = $result['data'];


require_once 'header.php';

$db = getDB();

$categorias = $db->query("SELECT id, nombre FROM categorias WHERE activo = 1 ORDER BY nombre")->fetchAll();
?>

<h1 class="page-title"><i class="fas fa-hamburger"></i> Gestion de Productos</h1>
<p class="page-subtitle">Administra el menu del restaurante</p>

<div class="card">
    <div class="card-header">
        <div class="d-flex gap-2" style="flex: 1;">
            <form method="GET" class="d-flex gap-2" style="flex: 1;">
                <select name="categoria" class="form-control" style="max-width: 200px;" onchange="this.form.submit()">
                    <option value="">Todas las categorias</option>
                    <?php foreach ($categorias as $cat): ?>
                    <option value="<?php echo $cat['id']; ?>" <?php echo $categoria_filter == $cat['id'] ? 'selected' : ''; ?>>
                        <?php echo $cat['nombre']; ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="search" class="form-control" placeholder="Buscar producto..." value="<?php echo htmlspecialchars($search); ?>" style="max-width: 250px;">
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i></button>
                <?php if ($categoria_filter || $search): ?>
                <a href="productos.php" class="btn btn-secondary btn-sm"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>
        <button class="btn btn-success" onclick="openModal('createModal')">
            <i class="fas fa-plus"></i> Nuevo Producto
        </button>
    </div>
    <div class="card-body">
        <div class="product-grid">
            <?php foreach ($productos as $prod): ?>
            <div class="product-card">
                <div class="product-image">

                    <?php 
                    $imgFile = $prod['imagen'] ?? '';
                    $uploadDir = dirname(__DIR__) . '/uploads/';
                    $imgExists = !empty($imgFile) && $imgFile !== 'default.jpg' && file_exists($uploadDir . $imgFile);
                    ?>
                    <?php if ($imgExists): ?>
                        <img src="../uploads/<?php echo $imgFile; ?>" alt="<?php echo $prod['nombre']; ?>">
                    <?php else: ?>
                        <img src="../assets/img/chessecake.jpg" alt="Sin imagen disponible">
                    <?php endif; ?>

                    <?php if ($prod['destacado']): ?>
                    <span class="badge badge-warning" style="position: absolute; top: 10px; right: 10px;">
                        <i class="fas fa-star"></i> Destacado
                    </span>
                    <?php endif; ?>
                </div>
                <div class="product-info">
                    <span class="badge badge-secondary" style="margin-bottom: 0.5rem;"><?php echo $prod['categoria_nombre']; ?></span>
                    <h3><?php echo $prod['nombre']; ?></h3>
                    <p><?php echo $prod['descripcion'] ?: 'Sin descripcion'; ?></p>
                    <div class="product-price"><?php echo formatMoney($prod['precio']); ?></div>
                    <div class="d-flex justify-between align-center">
                        <span class="badge <?php echo $prod['disponible'] ? 'badge-success' : 'badge-danger'; ?>">
                            <?php echo $prod['disponible'] ? 'Disponible' : 'No disponible'; ?>
                        </span>
                        <div class="d-flex gap-1">
                            <button class="btn btn-sm btn-info" onclick='editProd(<?php echo json_encode($prod); ?>)'>
                                <i class="fas fa-edit"></i>
                            </button>
                            <form method="POST" style="display: inline;" onsubmit="return confirm('Eliminar este producto?');">
                                <input type="hidden" name="action" value="delete">
                                <input type="hidden" name="id" value="<?php echo $prod['id']; ?>">
                                <button type="submit" class="btn btn-sm btn-danger"><i class="fas fa-trash"></i></button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php if (empty($productos)): ?>
        <div class="empty-state">
            <i class="fas fa-box-open"></i>
            <h3>No se encontraron productos</h3>
        </div>
        <?php endif; ?>

        <!-- Paginacion -->
        <?php if ($result['totalPages'] > 1): ?>
        <div class="pagination">
            <?php if ($result['hasPrev']): ?>
            <a href="?page=<?php echo $result['page'] - 1; ?>&categoria=<?php echo urlencode($categoria_filter); ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-left"></i></a>
            <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
            <?php if ($i == $result['page']): ?>
            <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="?page=<?php echo $i; ?>&categoria=<?php echo urlencode($categoria_filter); ?>&search=<?php echo urlencode($search); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
            <?php endfor; ?>

            <?php if ($result['hasNext']): ?>
            <a href="?page=<?php echo $result['page'] + 1; ?>&categoria=<?php echo urlencode($categoria_filter); ?>&search=<?php echo urlencode($search); ?>"><i class="fas fa-chevron-right"></i></a>
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
            <h3><i class="fas fa-plus"></i> Nuevo Producto</h3>
            <button class="modal-close" onclick="closeModal('createModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="action" value="create">
                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select name="categoria_id" class="form-control" required>
                        <option value="">Seleccione...</option>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripcion</label>
                    <textarea name="descripcion" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Precio ($)</label>
                    <input type="number" name="precio" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Imagen</label>
                    <input type="file" name="imagen" class="form-control" accept="image/*">
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="disponible" value="1" checked style="width: auto;">
                        <span>Disponible</span>
                    </label>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="destacado" value="1" style="width: auto;">
                        <span>Producto destacado</span>
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
            <h3><i class="fas fa-edit"></i> Editar Producto</h3>
            <button class="modal-close" onclick="closeModal('editModal')">&times;</button>
        </div>
        <form method="POST" enctype="multipart/form-data">
            <div class="modal-body">
                <input type="hidden" name="action" value="update">
                <input type="hidden" name="id" id="edit_id">
                <div class="form-group">
                    <label class="form-label">Categoria</label>
                    <select name="categoria_id" id="edit_categoria" class="form-control" required>
                        <?php foreach ($categorias as $cat): ?>
                        <option value="<?php echo $cat['id']; ?>"><?php echo $cat['nombre']; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Descripcion</label>
                    <textarea name="descripcion" id="edit_descripcion" class="form-control" rows="2"></textarea>
                </div>
                <div class="form-group">
                    <label class="form-label">Precio ($)</label>
                    <input type="number" name="precio" id="edit_precio" class="form-control" step="0.01" min="0" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Nueva Imagen (dejar vacio para mantener actual)</label>
                    <input type="file" name="imagen" class="form-control" accept="image/*">
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="disponible" id="edit_disponible" value="1" style="width: auto;">
                        <span>Disponible</span>
                    </label>
                </div>
                <div class="form-group">
                    <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                        <input type="checkbox" name="destacado" id="edit_destacado" value="1" style="width: auto;">
                        <span>Producto destacado</span>
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
function editProd(prod) {
    document.getElementById('edit_id').value = prod.id;
    document.getElementById('edit_categoria').value = prod.categoria_id;
    document.getElementById('edit_nombre').value = prod.nombre;
    document.getElementById('edit_descripcion').value = prod.descripcion || '';
    document.getElementById('edit_precio').value = prod.precio;
    document.getElementById('edit_disponible').checked = prod.disponible == 1;
    document.getElementById('edit_destacado').checked = prod.destacado == 1;
    openModal('editModal');
}
document.querySelectorAll('.modal-overlay').forEach(function(o) {
    o.addEventListener('click', function(e) { if (e.target === this) this.classList.remove('active'); });
});
</script>

<?php require_once 'footer.php'; ?>