<?php
$pageTitle = 'Dashboard';
require_once 'header.php';

$stats = getDashboardStats();
$recentOrders = getRecentOrders(8);
?>

<h1 class="page-title"><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
<p class="page-subtitle">Resumen general del restaurante</p>

<!-- Estadisticas -->
<div class="stats-grid">
    <div class="stat-card animate-fadeInUp">
        <div class="stat-icon primary">
            <i class="fas fa-shopping-cart"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $stats['total_pedidos']; ?></h3>
            <p>Total Pedidos</p>
        </div>
    </div>

    <div class="stat-card animate-fadeInUp" style="animation-delay: 0.1s;">
        <div class="stat-icon success">
            <i class="fas fa-dollar-sign"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo formatMoney($stats['total_ventas']); ?></h3>
            <p>Ventas Totales</p>
        </div>
    </div>

    <div class="stat-card animate-fadeInUp" style="animation-delay: 0.2s;">
        <div class="stat-icon info">
            <i class="fas fa-calendar-day"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $stats['pedidos_hoy']; ?></h3>
            <p>Pedidos Hoy</p>
        </div>
    </div>

    <div class="stat-card animate-fadeInUp" style="animation-delay: 0.3s;">
        <div class="stat-icon warning">
            <i class="fas fa-coins"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo formatMoney($stats['ventas_hoy']); ?></h3>
            <p>Ventas Hoy</p>
        </div>
    </div>

    <div class="stat-card animate-fadeInUp" style="animation-delay: 0.4s;">
        <div class="stat-icon secondary">
            <i class="fas fa-users"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $stats['total_clientes']; ?></h3>
            <p>Clientes</p>
        </div>
    </div>

    <div class="stat-card animate-fadeInUp" style="animation-delay: 0.5s;">
        <div class="stat-icon primary">
            <i class="fas fa-hamburger"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $stats['total_productos']; ?></h3>
            <p>Productos Activos</p>
        </div>
    </div>

    <div class="stat-card animate-fadeInUp" style="animation-delay: 0.6s;">
        <div class="stat-icon danger">
            <i class="fas fa-chair"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $stats['mesas_ocupadas']; ?>/<?php echo $stats['mesas_ocupadas'] + $stats['mesas_disponibles']; ?></h3>
            <p>Mesas Ocupadas</p>
        </div>
    </div>

    <div class="stat-card animate-fadeInUp" style="animation-delay: 0.7s;">
        <div class="stat-icon success">
            <i class="fas fa-check-circle"></i>
        </div>
        <div class="stat-info">
            <h3><?php echo $stats['mesas_disponibles']; ?></h3>
            <p>Mesas Disponibles</p>
        </div>
    </div>
</div>

<!-- Pedidos Recientes -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-clock"></i> Pedidos Recientes</h3>
        <a href="pedidos.php" class="btn btn-sm btn-primary">
            <i class="fas fa-list"></i> Ver Todos
        </a>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Mesa</th>
                        <th>Tipo</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recentOrders as $order): ?>
                    <tr>
                        <td><strong>#<?php echo $order['id']; ?></strong></td>
                        <td><?php echo $order['cliente_nombre'] ?: 'Sin cliente'; ?></td>
                        <td><?php echo $order['mesa_numero'] ?: 'N/A'; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $order['tipo'] == 'Mesa' ? 'primary' : ($order['tipo'] == 'Domicilio' ? 'info' : 'warning'); ?>">
                                <i class="fas fa-<?php echo $order['tipo'] == 'Mesa' ? 'chair' : ($order['tipo'] == 'Domicilio' ? 'motorcycle' : 'shopping-bag'); ?>"></i>
                                <?php echo $order['tipo']; ?>
                            </span>
                        </td>
                        <td><strong><?php echo formatMoney($order['total']); ?></strong></td>
                        <td>
                            <span class="badge <?php echo getEstadoBadge($order['estado']); ?>">
                                <?php echo $order['estado']; ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($order['fecha_pedido']); ?></td>
                        <td>
                            <a href="pedidos.php?ver=<?php echo $order['id']; ?>" class="btn btn-sm btn-info" title="Ver detalle">
                                <i class="fas fa-eye"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recentOrders)): ?>
                    <tr>
                        <td colspan="8" class="text-center">
                            <div class="empty-state">
                                <i class="fas fa-inbox"></i>
                                <h3>No hay pedidos aun</h3>
                            </div>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'footer.php'; ?>