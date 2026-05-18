<?php
/**
 * HEADER ADMIN - Restaurante Inteligente v4
 * NO debe haber espacios ni saltos de linea antes de <?php
 */
require_once '../includes/config.php';

// Verificar que el usuario este logueado y tenga rol adecuado
$roles_permitidos = ['Administrador', 'Cocinero', 'Mesero'];
requireRole($roles_permitidos);

// Contar notificaciones no leidas
$notificaciones = getUnreadNotifications();
$count_notif = count($notificaciones);

// Obtener pagina actual
$current_page = basename($_SERVER['PHP_SELF'], '.php');

// Redirigir a panel especifico segun rol si esta en dashboard
if ($current_page == 'dashboard' && !hasRole('Administrador')) {
    if (hasRole('Cocinero')) {
        redirect('cocina.php');
    } elseif (hasRole('Mesero')) {
        redirect('mesero.php');
    }
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($pageTitle) ? $pageTitle . ' - ' : ''; ?><?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <div class="logo">
                <i class="fas fa-utensils"></i>
                <span><?php echo APP_NAME; ?></span>
            </div>
        </div>

        <div class="sidebar-user">
            <div class="avatar">
                <?php echo strtoupper(substr($_SESSION['usuario_nombre'], 0, 1)); ?>
            </div>
            <div class="info">
                <div class="name"><?php echo $_SESSION['usuario_nombre']; ?></div>
                <div class="role"><i class="fas <?php echo getRolIcon($_SESSION['usuario_rol']); ?>"></i> <?php echo $_SESSION['usuario_rol']; ?></div>
            </div>
        </div>

        <ul class="sidebar-nav">
            <?php if (hasRole('Administrador')): ?>
            <li>
                <a href="dashboard.php" class="<?php echo $current_page == 'dashboard' ? 'active' : ''; ?>">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasRole('Cocinero') || hasRole('Administrador')): ?>
            <li>
                <a href="cocina.php" class="<?php echo $current_page == 'cocina' ? 'active' : ''; ?>">
                    <i class="fas fa-fire"></i>
                    <span>Panel de Cocina</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasRole('Mesero') || hasRole('Administrador')): ?>
            <li>
                <a href="mesero.php" class="<?php echo $current_page == 'mesero' ? 'active' : ''; ?>">
                    <i class="fas fa-concierge-bell"></i>
                    <span>Panel de Mesero</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasRole('Administrador')): ?>
            <li>
                <a href="usuarios.php" class="<?php echo $current_page == 'usuarios' ? 'active' : ''; ?>">
                    <i class="fas fa-users"></i>
                    <span>Usuarios</span>
                </a>
            </li>
            <li>
                <a href="categorias.php" class="<?php echo $current_page == 'categorias' ? 'active' : ''; ?>">
                    <i class="fas fa-tags"></i>
                    <span>Categorias</span>
                </a>
            </li>
            <?php endif; ?>

            <?php if (hasRole('Administrador')): ?>
            <li>
                <a href="productos.php" class="<?php echo $current_page == 'productos' ? 'active' : ''; ?>">
                    <i class="fas fa-hamburger"></i>
                    <span>Productos</span>
                </a>
            </li>
            <?php endif; ?>

            <li>
                <a href="pedidos.php" class="<?php echo $current_page == 'pedidos' ? 'active' : ''; ?>">
                    <i class="fas fa-clipboard-list"></i>
                    <span>Pedidos</span>
                </a>
            </li>

            <?php if (hasRole('Administrador') || hasRole('Mesero')): ?>
            <li>
                <a href="mesas.php" class="<?php echo $current_page == 'mesas' ? 'active' : ''; ?>">
                    <i class="fas fa-chair"></i>
                    <span>Mesas</span>
                </a>
            </li>
            <?php endif; ?>

            <li>
                <a href="reservaciones.php" class="<?php echo $current_page == 'reservaciones' ? 'active' : ''; ?>">
                    <i class="fas fa-calendar-check"></i>
                    <span>Reservaciones</span>
                </a>
            </li>

            <?php if (hasRole('Administrador')): ?>
            <li>
                <a href="reportes.php" class="<?php echo $current_page == 'reportes' ? 'active' : ''; ?>">
                    <i class="fas fa-chart-bar"></i>
                    <span>Reportes</span>
                </a>
            </li>
            <?php endif; ?>

            <li style="margin-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1); padding-top: 1rem;">
                <a href="../cliente/menu.php" target="_blank">
                    <i class="fas fa-external-link-alt"></i>
                    <span>Ver Menu Cliente</span>
                </a>
            </li>

            <li>
                <a href="logout.php">
                    <i class="fas fa-sign-out-alt"></i>
                    <span>Cerrar Sesion</span>
                </a>
            </li>
        </ul>
    </aside>

    <!-- Topbar -->
    <header class="topbar">
        <div class="topbar-left">
            <button class="menu-toggle" id="menuToggle">
                <i class="fas fa-bars"></i>
            </button>
            <nav class="breadcrumb">
                <a href="<?php echo hasRole('Administrador') ? 'dashboard.php' : (hasRole('Cocinero') ? 'cocina.php' : 'mesero.php'); ?>"><i class="fas fa-home"></i></a>
                <i class="fas fa-chevron-right" style="font-size: 0.7rem;"></i>
                <span><?php echo isset($pageTitle) ? $pageTitle : 'Dashboard'; ?></span>
            </nav>
        </div>

        <div class="topbar-right">
            <!--
            <button class="topbar-icon" id="notifToggle" title="Notificaciones">
                <i class="fas fa-bell"></i>
                <?php if ($count_notif > 0): ?>
                <span class="badge-count"><?php echo $count_notif; ?></span>
                <?php endif; ?>
            </button>
            -->

            <div style="display: flex; align-items: center; gap: 8px;">
                <span style="font-weight: 600; color: var(--color-secondary);"><?php echo $_SESSION['usuario_nombre']; ?></span>
                <div class="avatar" style="width: 35px; height: 35px; font-size: 0.9rem;">
                    <?php echo strtoupper(substr($_SESSION['usuario_nombre'], 0, 1)); ?>
                </div>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="content-wrapper">

            <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?php echo $flash['tipo']; ?>" id="flashAlert">
                <i class="fas fa-<?php echo $flash['tipo'] == 'success' ? 'check-circle' : ($flash['tipo'] == 'error' ? 'exclamation-circle' : 'info-circle'); ?>"></i>
                <?php echo $flash['mensaje']; ?>
                <button class="alert-close" onclick="this.parentElement.remove()">&times;</button>
            </div>
            <?php endif; ?>