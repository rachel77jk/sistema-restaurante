<?php
/**
 * MENU CLIENTE - Restaurante Inteligente v4
 * IMPORTANTE: Todo el procesamiento POST debe ir ANTES de header_cliente.php
 */
require_once '../includes/config.php';

$db = getDB();

// ============================================================
// PROCESAMIENTO DE FORMULARIOS (ANTES de cualquier output)
// ============================================================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['cart_action'] ?? '';

    if ($action === 'add') {
        $producto_id = intval($_POST['producto_id'] ?? 0);
        $cantidad = max(1, intval($_POST['cantidad'] ?? 1));
        $notas = sanitize($_POST['notas'] ?? '');

        $stmt = $db->prepare("SELECT id, nombre, precio FROM productos WHERE id = ? AND disponible = 1");
        $stmt->execute([$producto_id]);
        $producto = $stmt->fetch();

        if ($producto) {
            if (!isset($_SESSION['carrito'])) $_SESSION['carrito'] = [];

            $found = false;
            foreach ($_SESSION['carrito'] as &$item) {
                if ($item['id'] == $producto_id) {
                    $item['cantidad'] += $cantidad;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $_SESSION['carrito'][] = [
                    'id' => $producto['id'],
                    'nombre' => $producto['nombre'],
                    'precio' => $producto['precio'],
                    'cantidad' => $cantidad,
                    'notas' => $notas
                ];
            }

            redirect('menu.php', 'success', 'Producto agregado al carrito');
        }
    }

    if ($action === 'update') {
        $index = intval($_POST['index'] ?? -1);
        $cantidad = max(1, intval($_POST['cantidad'] ?? 1));
        if (isset($_SESSION['carrito'][$index])) {
            $_SESSION['carrito'][$index]['cantidad'] = $cantidad;
        }
        redirect('menu.php', 'success', 'Cantidad actualizada');
    }

    if ($action === 'remove') {
        $index = intval($_POST['index'] ?? -1);
        if (isset($_SESSION['carrito'][$index])) {
            array_splice($_SESSION['carrito'], $index, 1);
        }
        redirect('menu.php', 'success', 'Producto eliminado');
    }

    if ($action === 'checkout') {
        if (empty($_SESSION['carrito'])) {
            redirect('menu.php', 'error', 'El carrito esta vacio');
        }

        if (!isLoggedIn()) {
            redirect('../login.php', 'warning', 'Debes iniciar sesion para realizar un pedido');
        }

        $tipo = sanitize($_POST['tipo'] ?? 'Mesa');
        $mesa_id = !empty($_POST['mesa_id']) ? intval($_POST['mesa_id']) : null;
        $notas_pedido = sanitize($_POST['notas_pedido'] ?? '');

        $total = 0;
        foreach ($_SESSION['carrito'] as $item) {
            $total += $item['precio'] * $item['cantidad'];
        }

        $stmt = $db->prepare("INSERT INTO pedidos (cliente_id, mesa_id, tipo, total, notas) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$_SESSION['usuario_id'], $mesa_id, $tipo, $total, $notas_pedido]);
        $pedido_id = $db->lastInsertId();

        foreach ($_SESSION['carrito'] as $item) {
            $subtotal = $item['precio'] * $item['cantidad'];
            $stmt = $db->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal, notas) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$pedido_id, $item['id'], $item['cantidad'], $item['precio'], $subtotal, $item['notas']]);
        }

        if ($mesa_id) {
            $db->prepare("UPDATE mesas SET estado = 'Ocupada' WHERE id = ?")->execute([$mesa_id]);
        }

        createNotification('Nuevo pedido', 'Pedido #' . $pedido_id . ' recibido', 'Pedido');
        unset($_SESSION['carrito']);
        redirect('mis_pedidos.php', 'success', 'Pedido #' . $pedido_id . ' realizado correctamente');
    }

    if ($action === 'clear') {
        unset($_SESSION['carrito']);
        redirect('menu.php', 'info', 'Carrito vaciado');
    }
}

// ============================================================
// AQUI EMPIEZA EL OUTPUT HTML
// ============================================================
$pageTitle = 'Menu';
require_once 'header_cliente.php';

// Obtener categorias y productos
$categoria_filter = $_GET['categoria'] ?? '';
$categorias = $db->query("SELECT id, nombre, icono FROM categorias WHERE activo = 1 ORDER BY orden, nombre")->fetchAll();

$sql = "SELECT p.*, c.nombre as categoria_nombre, c.icono as categoria_icono FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE p.disponible = 1 AND c.activo = 1";
$params = [];

if (!empty($categoria_filter)) {
    $sql .= " AND p.categoria_id = ?";
    $params[] = $categoria_filter;
}

$sql .= " ORDER BY p.destacado DESC, p.nombre";
$productos = $db->prepare($sql);
$productos->execute($params);
$productos = $productos->fetchAll();

$mesas = $db->query("SELECT id, numero, capacidad, ubicacion FROM mesas WHERE estado = 'Disponible' ORDER BY numero")->fetchAll();

$cart_total = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cart_total += $item['precio'] * $item['cantidad'];
    }
}
?>

<!-- Hero -->
<div class="hero-section">
    <h1><i class="fas fa-utensils"></i> Nuestro Menu</h1>
    <p>Descubre nuestras deliciosas especialidades preparadas con los mejores ingredientes</p>
</div>

<!-- Filtros de categoria -->
<div class="category-filter">
    <a href="menu.php" class="<?php echo empty($categoria_filter) ? 'active' : ''; ?>">
        <i class="fas fa-th-large"></i> Todos
    </a>
    <?php foreach ($categorias as $cat): ?>
    <a href="menu.php?categoria=<?php echo $cat['id']; ?>" class="<?php echo $categoria_filter == $cat['id'] ? 'active' : ''; ?>">
        <i class="fas <?php echo $cat['icono']; ?>"></i> <?php echo $cat['nombre']; ?>
    </a>
    <?php endforeach; ?>
</div>

<div style="padding: 2rem;">
    <?php $flash = getFlash(); if ($flash): ?>
    <div class="alert alert-<?php echo $flash['tipo']; ?>" style="max-width: 800px; margin: 0 auto 1.5rem;">
        <i class="fas fa-<?php echo $flash['tipo'] == 'success' ? 'check-circle' : ($flash['tipo'] == 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
        <?php echo $flash['mensaje']; ?>
    </div>
    <?php endif; ?>

    <div class="product-grid">
        <?php foreach ($productos as $prod): ?>
        <div class="product-card animate-fadeInUp">
            <div class="product-image" style="position: relative;">
                <?php if ($prod['imagen'] && $prod['imagen'] != 'default.jpg' && file_exists(BASE_PATH . 'uploads/' . $prod['imagen'])): ?>
                <img src="../uploads/<?php echo $prod['imagen']; ?>" alt="<?php echo $prod['nombre']; ?>">
                <?php else: ?>
                <i class="fas fa-utensils"></i>
                <?php endif; ?>
                <?php if ($prod['destacado']): ?>
                <span class="badge badge-warning" style="position: absolute; top: 10px; right: 10px;">
                    <i class="fas fa-star"></i> Destacado
                </span>
                <?php endif; ?>
            </div>
            <div class="product-info">
                <span class="badge badge-secondary" style="margin-bottom: 0.5rem;">
                    <i class="fas <?php echo $prod['categoria_icono']; ?>"></i> <?php echo $prod['categoria_nombre']; ?>
                </span>
                <h3><?php echo $prod['nombre']; ?></h3>
                <p><?php echo $prod['descripcion'] ?: 'Delicioso platillo preparado con ingredientes frescos'; ?></p>
                <div class="product-price"><?php echo formatMoney($prod['precio']); ?></div>
                <form method="POST" style="margin-top: 1rem;">
                    <input type="hidden" name="cart_action" value="add">
                    <input type="hidden" name="producto_id" value="<?php echo $prod['id']; ?>">
                    <div class="d-flex gap-2 align-center">
                        <input type="number" name="cantidad" value="1" min="1" max="20" class="form-control" style="width: 70px; text-align: center;">
                        <button type="submit" class="btn btn-primary btn-sm" style="flex: 1;">
                            <i class="fas fa-cart-plus"></i> Agregar
                        </button>
                    </div>
                    <input type="text" name="notas" class="form-control" placeholder="Notas especiales..." style="margin-top: 0.5rem; font-size: 0.85rem;">
                </form>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($productos)): ?>
    <div class="empty-state">
        <i class="fas fa-box-open"></i>
        <h3>No hay productos disponibles</h3>
    </div>
    <?php endif; ?>
</div>

<!-- Carrito Sidebar -->
<div class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
        <h3><i class="fas fa-shopping-cart"></i> Tu Carrito</h3>
        <button onclick="toggleCart()" style="background: none; border: none; color: white; font-size: 1.5rem; cursor: pointer;">&times;</button>
    </div>

    <div class="cart-items">
        <?php if (!empty($_SESSION['carrito'])): ?>
        <?php foreach ($_SESSION['carrito'] as $index => $item): ?>
        <div class="cart-item">
            <div class="cart-item-image">
                <i class="fas fa-utensils"></i>
            </div>
            <div class="cart-item-info">
                <h4><?php echo $item['nombre']; ?></h4>
                <div class="price"><?php echo formatMoney($item['precio']); ?> c/u</div>
                <div class="cart-item-actions">
                    <form method="POST" style="display: flex; align-items: center; gap: 5px;">
                        <input type="hidden" name="cart_action" value="update">
                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                        <button type="button" class="qty-btn" onclick="this.parentElement.querySelector('input[type=number]').stepDown(); this.parentElement.submit();">-</button>
                        <input type="number" name="cantidad" value="<?php echo $item['cantidad']; ?>" min="1" max="20" class="qty-input" onchange="this.form.submit()">
                        <button type="button" class="qty-btn" onclick="this.parentElement.querySelector('input[type=number]').stepUp(); this.parentElement.submit();">+</button>
                    </form>
                    <form method="POST" style="margin-left: auto;">
                        <input type="hidden" name="cart_action" value="remove">
                        <input type="hidden" name="index" value="<?php echo $index; ?>">
                        <button type="submit" style="background: none; border: none; color: var(--color-danger); cursor: pointer;">
                            <i class="fas fa-trash"></i>
                        </button>
                    </form>
                </div>
                <?php if ($item['notas']): ?>
                <small style="color: var(--color-gray);"><i class="fas fa-sticky-note"></i> <?php echo $item['notas']; ?></small>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-shopping-cart"></i>
            <h3>Tu carrito esta vacio</h3>
            <p>Agrega productos del menu</p>
        </div>
        <?php endif; ?>
    </div>

    <?php if (!empty($_SESSION['carrito'])): ?>
    <div class="cart-footer">
        <div class="cart-total">
            <span>Total:</span>
            <span><?php echo formatMoney($cart_total); ?></span>
        </div>

        <form method="POST">
            <input type="hidden" name="cart_action" value="checkout">
            <div class="form-group">
                <label class="form-label">Tipo de Pedido</label>
                <select name="tipo" class="form-control" id="tipoPedido" onchange="toggleMesa()" required>
                    <option value="Mesa">En Mesa</option>
                    <option value="Domicilio">A Domicilio</option>
                    <option value="ParaLlevar">Para Llevar</option>
                </select>
            </div>
            <div class="form-group" id="mesaGroup">
                <label class="form-label">Seleccionar Mesa</label>
                <select name="mesa_id" class="form-control">
                    <option value="">Seleccione una mesa...</option>
                    <?php foreach ($mesas as $m): ?>
                    <option value="<?php echo $m['id']; ?>"><?php echo $m['numero']; ?> - <?php echo $m['ubicacion']; ?> (<?php echo $m['capacidad']; ?> pers.)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Notas del pedido</label>
                <textarea name="notas_pedido" class="form-control" rows="2" placeholder="Instrucciones especiales..."></textarea>
            </div>
            <button type="submit" class="btn btn-success btn-block btn-lg">
                <i class="fas fa-check-circle"></i> Realizar Pedido
            </button>
        </form>

        <form method="POST" style="margin-top: 0.5rem;">
            <input type="hidden" name="cart_action" value="clear">
            <button type="submit" class="btn btn-outline btn-block btn-sm" style="border-color: var(--color-danger); color: var(--color-danger);">
                <i class="fas fa-trash"></i> Vaciar Carrito
            </button>
        </form>
    </div>
    <?php endif; ?>
</div>

<!-- Overlay para cerrar carrito -->
<div id="cartOverlay" style="display: none; position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(0,0,0,0.5); z-index: 999;" onclick="toggleCart()"></div>

<script>
function toggleCart() {
    var sidebar = document.getElementById('cartSidebar');
    var overlay = document.getElementById('cartOverlay');
    sidebar.classList.toggle('active');
    overlay.style.display = sidebar.classList.contains('active') ? 'block' : 'none';
}

function toggleMesa() {
    var tipo = document.getElementById('tipoPedido').value;
    var mesaGroup = document.getElementById('mesaGroup');
    mesaGroup.style.display = tipo === 'Mesa' ? 'block' : 'none';
}
</script>

<?php require_once 'footer_cliente.php'; ?>