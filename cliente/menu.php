<?php
/**
 * MENU CLIENTE - Restaurante Inteligente v5
 * Solo visualizacion de productos. Sin opciones de pedido.
 */
require_once '../includes/config.php';

$db = getDB();

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

<?php require_once 'footer_cliente.php'; ?>