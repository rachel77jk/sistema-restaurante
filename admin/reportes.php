<?php
$pageTitle = 'Reportes y Estadisticas';
require_once 'header.php';

$db = getDB();

// Ventas por dia (ultimos 30 dias)
$ventas_dia = $db->query("
    SELECT DATE(fecha_pedido) as fecha, COUNT(*) as total_pedidos, SUM(total) as total_ventas
    FROM pedidos WHERE estado != 'Cancelado' AND fecha_pedido >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
    GROUP BY DATE(fecha_pedido) ORDER BY fecha
")->fetchAll();

// Ventas por categoria
$ventas_categoria = $db->query("
    SELECT c.nombre as categoria, SUM(dp.subtotal) as total
    FROM detalle_pedidos dp
    JOIN productos p ON dp.producto_id = p.id
    JOIN categorias c ON p.categoria_id = c.id
    JOIN pedidos pe ON dp.pedido_id = pe.id
    WHERE pe.estado != 'Cancelado'
    GROUP BY c.id, c.nombre ORDER BY total DESC
")->fetchAll();

// Productos mas vendidos
$top_productos = $db->query("
    SELECT p.nombre, SUM(dp.cantidad) as cantidad, SUM(dp.subtotal) as total
    FROM detalle_pedidos dp
    JOIN productos p ON dp.producto_id = p.id
    JOIN pedidos pe ON dp.pedido_id = pe.id
    WHERE pe.estado != 'Cancelado'
    GROUP BY p.id ORDER BY cantidad DESC LIMIT 10
")->fetchAll();

// Pedidos por estado
$pedidos_estado = $db->query("
    SELECT estado, COUNT(*) as total FROM pedidos GROUP BY estado
")->fetchAll();

// Totales generales
$total_ventas_mes = $db->query("SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE estado != 'Cancelado' AND MONTH(fecha_pedido) = MONTH(CURDATE()) AND YEAR(fecha_pedido) = YEAR(CURDATE())")->fetchColumn();
$total_pedidos_mes = $db->query("SELECT COUNT(*) FROM pedidos WHERE MONTH(fecha_pedido) = MONTH(CURDATE()) AND YEAR(fecha_pedido) = YEAR(CURDATE())")->fetchColumn();
$promedio_venta = $db->query("SELECT COALESCE(AVG(total), 0) FROM pedidos WHERE estado != 'Cancelado'")->fetchColumn();
?>

<h1 class="page-title"><i class="fas fa-chart-bar"></i> Reportes y Estadisticas</h1>
<p class="page-subtitle">Analisis de ventas y rendimiento</p>

<!-- Resumen -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-dollar-sign"></i></div>
        <div class="stat-info">
            <h3><?php echo formatMoney($total_ventas_mes); ?></h3>
            <p>Ventas este mes</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-shopping-cart"></i></div>
        <div class="stat-info">
            <h3><?php echo $total_pedidos_mes; ?></h3>
            <p>Pedidos este mes</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <h3><?php echo formatMoney($promedio_venta); ?></h3>
            <p>Promedio por pedido</p>
        </div>
    </div>
</div>

<div class="grid grid-2">
    <!-- Grafico Ventas por Dia -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-area"></i> Ventas por Dia (Ultimos 30 dias)</h3>
        </div>
        <div class="card-body">
            <canvas id="ventasDiaChart" height="250"></canvas>
        </div>
    </div>

    <!-- Grafico Ventas por Categoria -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie"></i> Ventas por Categoria</h3>
        </div>
        <div class="card-body">
            <canvas id="ventasCategoriaChart" height="250"></canvas>
        </div>
    </div>
</div>

<div class="grid grid-2 mt-3">
    <!-- Top Productos -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-trophy"></i> Productos Mas Vendidos</h3>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($top_productos as $i => $prod): ?>
                        <tr>
                            <td><strong><?php echo $i + 1; ?></strong></td>
                            <td><?php echo $prod['nombre']; ?></td>
                            <td><span class="badge badge-info"><?php echo $prod['cantidad']; ?></span></td>
                            <td><strong><?php echo formatMoney($prod['total']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Pedidos por Estado -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-chart-pie"></i> Pedidos por Estado</h3>
        </div>
        <div class="card-body">
            <canvas id="pedidosEstadoChart" height="250"></canvas>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
// Colores del tema
const colors = {
    primary: '#e67e22',
    primaryDark: '#d35400',
    success: '#27ae60',
    danger: '#e74c3c',
    warning: '#f39c12',
    info: '#3498db',
    secondary: '#2c3e50'
};

// Ventas por Dia
const ventasDiaCtx = document.getElementById('ventasDiaChart').getContext('2d');
new Chart(ventasDiaCtx, {
    type: 'line',
    data: {
        labels: <?php echo json_encode(array_map(function($v) { return date('d/m', strtotime($v['fecha'])); }, $ventas_dia)); ?>,
        datasets: [{
            label: 'Ventas ($)',
            data: <?php echo json_encode(array_map(function($v) { return floatval($v['total_ventas']); }, $ventas_dia)); ?>,
            borderColor: colors.primary,
            backgroundColor: 'rgba(230, 126, 34, 0.1)',
            borderWidth: 2,
            fill: true,
            tension: 0.4,
            pointBackgroundColor: colors.primary,
            pointRadius: 4
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: {
                beginAtZero: true,
                ticks: {
                    callback: function(value) {
                        return '$' + value.toFixed(0);
                    }
                }
            }
        }
    }
});

// Ventas por Categoria
const ventasCatCtx = document.getElementById('ventasCategoriaChart').getContext('2d');
new Chart(ventasCatCtx, {
    type: 'doughnut',
    data: {
        labels: <?php echo json_encode(array_map(function($v) { return $v['categoria']; }, $ventas_categoria)); ?>,
        datasets: [{
            data: <?php echo json_encode(array_map(function($v) { return floatval($v['total']); }, $ventas_categoria)); ?>,
            backgroundColor: [colors.primary, colors.success, colors.info, colors.warning, colors.danger, colors.secondary],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: {
                position: 'bottom',
                labels: { padding: 15, usePointStyle: true }
            }
        }
    }
});

// Pedidos por Estado
const pedidosEstadoCtx = document.getElementById('pedidosEstadoChart').getContext('2d');
new Chart(pedidosEstadoCtx, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_map(function($v) { return $v['estado']; }, $pedidos_estado)); ?>,
        datasets: [{
            label: 'Pedidos',
            data: <?php echo json_encode(array_map(function($v) { return intval($v['total']); }, $pedidos_estado)); ?>,
            backgroundColor: [colors.warning, colors.info, colors.success, colors.primary, colors.danger],
            borderRadius: 6
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        },
        scales: {
            y: { beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});
</script>

<?php require_once 'footer.php'; ?>