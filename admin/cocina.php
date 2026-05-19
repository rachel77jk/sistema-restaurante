<?php
/**
 * PANEL COCINA - Restaurante Inteligente v4
 * IMPORTANTE: Todo el procesamiento GET/POST debe ir ANTES de header.php
 */
require_once '../includes/config.php';

// Solo cocineros y administradores
requireRole(['Cocinero', 'Administrador']);

$db = getDB();

// ============================================================
// PROCESAMIENTO GET (ANTES de cualquier output)
// ============================================================
if (isset($_GET['estado']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $estado = sanitize($_GET['estado']);
    $estados_validos = ['EnPreparacion', 'Listo'];

    if (in_array($estado, $estados_validos)) {
        $stmt = $db->prepare("UPDATE pedidos SET estado = ? WHERE id = ? AND estado IN ('Pendiente', 'EnPreparacion')");
        $stmt->execute([$estado, $id]);

        if ($stmt->rowCount() > 0) {
            createNotification('Pedido actualizado', 'Pedido #' . $id . ' ahora esta: ' . $estado, 'Pedido');
            redirect('cocina.php', 'success', 'Pedido #' . $id . ' marcado como ' . $estado);
        }
    }
}

// ============================================================
// AQUI EMPIEZA EL OUTPUT HTML
// ============================================================
$pageTitle = 'Panel de Cocina';
require_once 'header.php';

// Obtener pedidos pendientes y en preparacion
$stmt = $db->query("
    SELECT p.*, u.nombre as cliente_nombre, m.numero as mesa_numero, m.ubicacion as mesa_ubicacion
    FROM pedidos p
    LEFT JOIN usuarios u ON p.cliente_id = u.id
    LEFT JOIN mesas m ON p.mesa_id = m.id
    WHERE p.estado IN ('Pendiente', 'EnPreparacion')
    ORDER BY 
        CASE p.estado
            WHEN 'Pendiente' THEN 1
            WHEN 'EnPreparacion' THEN 2
        END,
        p.fecha_pedido ASC
");
$pedidos_cocina = $stmt->fetchAll();

// Estadisticas del dia
$pedidos_hoy = $db->query("SELECT COUNT(*) FROM pedidos WHERE DATE(fecha_pedido) = CURDATE() AND estado != 'Cancelado'")->fetchColumn();
$pedidos_pendientes = $db->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'Pendiente'")->fetchColumn();
$pedidos_preparacion = $db->query("SELECT COUNT(*) FROM pedidos WHERE estado = 'EnPreparacion'")->fetchColumn();
$pedidos_listos_hoy = $db->query("SELECT COUNT(*) FROM pedidos WHERE DATE(fecha_pedido) = CURDATE() AND estado = 'Listo'")->fetchColumn();
?>

<style>
.pedido-card {
    background: var(--color-white);
    border-radius: var(--radius-md);
    border: 2px solid var(--color-border);
    overflow: hidden;
    transition: var(--transition);
    box-shadow: var(--shadow-sm);
}
.pedido-card:hover {
    box-shadow: var(--shadow-md);
    transform: translateY(-2px);
}
.pedido-card.Pendiente { border-left: 5px solid var(--color-warning); }
.pedido-card.EnPreparacion { border-left: 5px solid var(--color-info); }

.pedido-header {
    padding: 1rem 1.2rem;
    background: linear-gradient(135deg, #f8f9fa, #e9ecef);
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid var(--color-border);
}
.pedido-header .numero {
    font-size: 1.3rem;
    font-weight: 700;
    color: var(--color-secondary);
}
.pedido-header .tiempo {
    font-size: 0.85rem;
    color: var(--color-gray);
    display: flex;
    align-items: center;
    gap: 5px;
}
.pedido-header .tiempo.urgente {
    color: var(--color-danger);
    font-weight: 700;
    animation: pulse 1.5s infinite;
}

.pedido-body {
    padding: 1.2rem;
}
.pedido-meta {
    display: flex;
    gap: 1rem;
    margin-bottom: 1rem;
    flex-wrap: wrap;
}
.pedido-meta span {
    font-size: 0.9rem;
    color: var(--color-gray);
    display: flex;
    align-items: center;
    gap: 5px;
}
.pedido-meta .cliente {
    font-weight: 600;
    color: var(--color-secondary);
}

.item-list {
    list-style: none;
    padding: 0;
    margin: 0;
}
.item-list li {
    padding: 0.6rem 0;
    border-bottom: 1px dashed var(--color-border);
    display: flex;
    justify-content: space-between;
    align-items: flex-start;
}
.item-list li:last-child {
    border-bottom: none;
}
.item-list .cantidad {
    background: var(--color-primary);
    color: white;
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
}
.item-list .nombre {
    flex: 1;
    margin-left: 0.8rem;
    font-weight: 600;
}
.item-list .notas {
    font-size: 0.8rem;
    color: var(--color-gray);
    font-style: italic;
    display: block;
    margin-top: 2px;
}

.pedido-footer {
    padding: 1rem 1.2rem;
    background: #f8f9fa;
    border-top: 1px solid var(--color-border);
    display: flex;
    gap: 10px;
    justify-content: flex-end;
}

.pedidos-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
    gap: 1.5rem;
}

@media (max-width: 576px) {
    .pedidos-grid {
        grid-template-columns: 1fr;
    }
}
</style>

<h1 class="page-title"><i class="fas fa-fire"></i> Panel de Cocina</h1>
<p class="page-subtitle">Gestion de pedidos en tiempo real</p>

<!-- Estadisticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <h3><?php echo $pedidos_pendientes; ?></h3>
            <p>Pendientes</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon info"><i class="fas fa-fire"></i></div>
        <div class="stat-info">
            <h3><?php echo $pedidos_preparacion; ?></h3>
            <p>En Preparacion</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <h3><?php echo $pedidos_listos_hoy; ?></h3>
            <p>Listos Hoy</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-info">
            <h3><?php echo $pedidos_hoy; ?></h3>
            <p>Pedidos Hoy</p>
        </div>
    </div>
</div>

<!-- Pedidos -->
<div class="card">
    <div class="card-header">
        <h3><i class="fas fa-list"></i> Pedidos Activos</h3>
        <button class="btn btn-sm btn-outline" onclick="location.reload()">
            <i class="fas fa-sync-alt"></i> Actualizar
        </button>
    </div>
    <div class="card-body">
        <?php if (!empty($pedidos_cocina)): ?>
        <div class="pedidos-grid">
            <?php foreach ($pedidos_cocina as $pedido): 
                $stmt = $db->prepare("
                    SELECT dp.*, p.nombre as producto_nombre 
                    FROM detalle_pedidos dp 
                    JOIN productos p ON dp.producto_id = p.id 
                    WHERE dp.pedido_id = ?
                ");
                $stmt->execute([$pedido['id']]);
                $items = $stmt->fetchAll();

                $minutos = floor((time() - strtotime($pedido['fecha_pedido'])) / 60);
                $urgente = $minutos > 20;
            ?>
            <div class="pedido-card <?php echo $pedido['estado']; ?>">
                <div class="pedido-header">
                    <span class="numero">#<?php echo $pedido['id']; ?></span>
                    <span class="tiempo <?php echo $urgente ? 'urgente' : ''; ?>">
                        <i class="fas fa-clock"></i>
                        <?php 
                        if ($minutos < 1) echo 'Hace momentos';
                        elseif ($minutos < 60) echo $minutos . ' min';
                        else echo floor($minutos/60) . 'h ' . ($minutos%60) . 'm';
                        ?>
                    </span>
                </div>
                <div class="pedido-body">
                    <div class="pedido-meta">
                        <span class="cliente"><i class="fas fa-user"></i> <?php echo $pedido['cliente_nombre'] ?: 'Sin cliente'; ?></span>
                        <span><i class="fas fa-<?php echo $pedido['tipo'] == 'Mesa' ? 'chair' : 'shopping-bag'; ?>"></i> 
                            <?php echo $pedido['tipo'] == 'Mesa' ? ($pedido['mesa_numero'] ?: 'Sin mesa') : 'Para Llevar'; ?>
                        </span>
                        <span><i class="fas fa-tag"></i> <?php echo $pedido['tipo']; ?></span>
                    </div>

                    <ul class="item-list">
                        <?php foreach ($items as $item): ?>
                        <li>
                            <div style="display: flex; align-items: flex-start;">
                                <span class="cantidad"><?php echo $item['cantidad']; ?></span>
                                <div class="nombre">
                                    <?php echo $item['producto_nombre']; ?>
                                    <?php if ($item['notas']): ?>
                                    <span class="notas"><i class="fas fa-sticky-note"></i> <?php echo $item['notas']; ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </li>
                        <?php endforeach; ?>
                    </ul>

                    <?php if ($pedido['notas']): ?>
                    <div class="alert alert-warning" style="margin-top: 1rem; margin-bottom: 0;">
                        <i class="fas fa-sticky-note"></i> <strong>Nota:</strong> <?php echo $pedido['notas']; ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div class="pedido-footer">
                    <?php if ($pedido['estado'] == 'Pendiente'): ?>
                    <a href="cocina.php?estado=EnPreparacion&id=<?php echo $pedido['id']; ?>" class="btn btn-info">
                        <i class="fas fa-fire"></i> Iniciar Preparacion
                    </a>
                    <?php elseif ($pedido['estado'] == 'EnPreparacion'): ?>
                    <a href="cocina.php?estado=Listo&id=<?php echo $pedido['id']; ?>" class="btn btn-success">
                        <i class="fas fa-check-circle"></i> Marcar Listo
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-mug-hot" style="font-size: 5rem;"></i>
            <h3>No hay pedidos activos</h3>
            <p>Los pedidos nuevos apareceran aqui automaticamente</p>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Auto-refresh cada 30 segundos -->
<script>
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php require_once 'footer.php'; ?>