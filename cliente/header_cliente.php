<?php
/**
 * HEADER CLIENTE - Restaurante Inteligente v4
 * NO debe haber espacios ni saltos de linea antes de <?php
 */
require_once '../includes/config.php';

// Verificar que sea cliente o permitir acceso publico a menu
$public_pages = ['menu.php', 'registro.php'];
$current_page = basename($_SERVER['PHP_SELF']);

if (!in_array($current_page, $public_pages)) {
    requireRole('Cliente');
}

$cart_count = 0;
if (isset($_SESSION['carrito'])) {
    foreach ($_SESSION['carrito'] as $item) {
        $cart_count += $item['cantidad'];
    }
}

$current_page_name = basename($_SERVER['PHP_SELF'], '.php');
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
    .client-header {
        position: fixed;
        top: 0;
        left: 0;
        right: 0;
        height: 70px;
        background: linear-gradient(135deg, var(--color-dark), #16213e);
        color: var(--color-white);
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0 2rem;
        z-index: 900;
        box-shadow: var(--shadow-md);
    }
    .client-header .logo {
        font-size: 1.4rem;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--color-primary);
    }
    .client-header .logo i { font-size: 1.6rem; }
    .client-nav {
        display: flex;
        align-items: center;
        gap: 2rem;
    }
    .client-nav a {
        color: rgba(255,255,255,0.8);
        font-weight: 500;
        padding: 8px 0;
        border-bottom: 2px solid transparent;
        transition: var(--transition);
    }
    .client-nav a:hover, .client-nav a.active {
        color: var(--color-white);
        border-bottom-color: var(--color-primary);
    }
    .client-header-actions {
        display: flex;
        align-items: center;
        gap: 1rem;
    }
    .cart-btn {
        position: relative;
        background: var(--color-primary);
        border: none;
        color: var(--color-white);
        width: 45px;
        height: 45px;
        border-radius: 50%;
        cursor: pointer;
        font-size: 1.2rem;
        transition: var(--transition);
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .cart-btn:hover { transform: scale(1.1); }
    .cart-btn .cart-count {
        position: absolute;
        top: -5px;
        right: -5px;
        background: var(--color-danger);
        color: var(--color-white);
        font-size: 0.75rem;
        font-weight: 700;
        width: 22px;
        height: 22px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    .client-content {
        padding-top: 70px;
        min-height: 100vh;
    }
    .hero-section {
        background: linear-gradient(135deg, var(--color-dark), #16213e);
        color: var(--color-white);
        padding: 4rem 2rem;
        text-align: center;
    }
    .hero-section h1 {
        font-size: 2.5rem;
        margin-bottom: 1rem;
    }
    .hero-section p {
        font-size: 1.2rem;
        opacity: 0.8;
        max-width: 600px;
        margin: 0 auto;
    }
    .category-filter {
        display: flex;
        gap: 10px;
        padding: 1.5rem 2rem;
        background: var(--color-white);
        border-bottom: 1px solid var(--color-border);
        overflow-x: auto;
        flex-wrap: wrap;
        justify-content: center;
    }
    .category-filter a {
        padding: 8px 20px;
        border-radius: 25px;
        background: var(--color-gray-light);
        color: var(--color-secondary);
        font-weight: 600;
        font-size: 0.9rem;
        transition: var(--transition);
        white-space: nowrap;
    }
    .category-filter a:hover, .category-filter a.active {
        background: var(--color-primary);
        color: var(--color-white);
    }
    .client-footer {
        background: var(--color-dark);
        color: rgba(255,255,255,0.6);
        padding: 2rem;
        text-align: center;
    }
    .client-footer a { color: var(--color-primary); }
    @media (max-width: 768px) {
        .client-nav { display: none; }
        .hero-section h1 { font-size: 1.8rem; }
    }
    </style>
</head>
<body>
    <header class="client-header">
        <a href="menu.php" class="logo">
            <i class="fas fa-utensils"></i>
            <span><?php echo APP_NAME; ?></span>
        </a>

        <nav class="client-nav">
            <a href="menu.php" class="<?php echo $current_page_name == 'menu' ? 'active' : ''; ?>"><i class="fas fa-utensils"></i> Menu</a>
            <?php if (isLoggedIn() && hasRole('Cliente')): ?>
            <a href="mis_pedidos.php" class="<?php echo $current_page_name == 'mis_pedidos' ? 'active' : ''; ?>"><i class="fas fa-clipboard-list"></i> Mis Pedidos</a>
            <?php endif; ?>
        </nav>

        <div class="client-header-actions">
            <button class="cart-btn" onclick="toggleCart()">
                <i class="fas fa-shopping-cart"></i>
                <?php if ($cart_count > 0): ?>
                <span class="cart-count" id="cartCount"><?php echo $cart_count; ?></span>
                <?php endif; ?>
            </button>

            <?php if (isLoggedIn()): ?>
            <div style="display: flex; align-items: center; gap: 10px;">
                <span style="font-weight: 600;"><?php echo $_SESSION['usuario_nombre']; ?></span>
                <a href="logout.php" class="btn btn-sm btn-outline" style="border-color: rgba(255,255,255,0.3); color: var(--color-white);">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
            <?php else: ?>
            <a href="../login.php" class="btn btn-primary btn-sm"><i class="fas fa-sign-in-alt"></i> Ingresar</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="client-content">