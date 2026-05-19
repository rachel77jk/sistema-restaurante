<?php
/**
 * HEADER CLIENTE - Restaurante Inteligente v6
 * Solo visualizacion de menu publico
 */
require_once '../includes/config.php';

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
        text-decoration: none;
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
        text-decoration: none;
    }
    .client-nav a:hover, .client-nav a.active {
        color: var(--color-white);
        border-bottom-color: var(--color-primary);
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
        text-decoration: none;
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
    
    /* Dropdown de usuario */
    .user-menu {
        position: relative;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .user-menu-btn {
        background: rgba(255,255,255,0.1);
        border: 1px solid rgba(255,255,255,0.2);
        color: var(--color-white);
        padding: 8px 16px;
        border-radius: var(--radius-md);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 8px;
        font-weight: 500;
        transition: var(--transition);
    }
    .user-menu-btn:hover {
        background: rgba(255,255,255,0.2);
    }
    .user-dropdown {
        position: absolute;
        top: 50px;
        right: 0;
        background: var(--color-white);
        border-radius: var(--radius-md);
        box-shadow: var(--shadow-lg);
        min-width: 200px;
        display: none;
        overflow: hidden;
        z-index: 1000;
    }
    .user-dropdown.show {
        display: block;
    }
    .user-dropdown a {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        color: var(--color-secondary);
        text-decoration: none;
        transition: var(--transition);
        border-bottom: 1px solid var(--color-border);
    }
    .user-dropdown a:last-child {
        border-bottom: none;
    }
    .user-dropdown a:hover {
        background: var(--color-gray-light);
        color: var(--color-primary);
    }
    .user-dropdown a i {
        width: 20px;
        text-align: center;
    }
    .user-dropdown .logout-item {
        color: var(--color-danger);
    }
    .user-dropdown .logout-item:hover {
        background: #fff5f5;
        color: var(--color-danger);
    }
    
    @media (max-width: 768px) {
        .client-nav { display: none; }
        .hero-section h1 { font-size: 1.8rem; }
        .user-menu-btn span { display: none; }
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
        </nav>

        <div>
            <?php if (isLoggedIn() && hasRole('Cliente')): ?>
            <!-- Usuario logueado: Menu desplegable -->
            <div class="user-menu">
                <button class="user-menu-btn" onclick="toggleUserMenu()">
                    <i class="fas fa-user-circle"></i>
                    <span><?php echo $_SESSION['usuario_nombre']; ?></span>
                    <i class="fas fa-chevron-down" style="font-size: 0.7rem;"></i>
                </button>
                <div class="user-dropdown" id="userDropdown">
                    <a href="acerca.php"><i class="fas fa-info-circle"></i> Acerca de</a>
                    <div style="border-top: 1px solid var(--color-border);"></div>
                    <a href="logout.php" class="logout-item"><i class="fas fa-sign-out-alt"></i> Cerrar Sesion</a>
                </div>
            </div>
            <?php else: ?>
            <!-- Usuario NO logueado: Boton Ingresar -->
            <a href="../login.php" class="btn btn-primary btn-sm"><i class="fas fa-sign-in-alt"></i> Ingresar</a>
            <?php endif; ?>
        </div>
    </header>

    <main class="client-content">
    
    <script>
    function toggleUserMenu() {
        document.getElementById('userDropdown').classList.toggle('show');
    }
    
    // Cerrar dropdown al hacer click fuera
    document.addEventListener('click', function(e) {
        var menu = document.querySelector('.user-menu');
        var dropdown = document.getElementById('userDropdown');
        if (menu && !menu.contains(e.target)) {
            dropdown.classList.remove('show');
        }
    });
    </script>