<?php
/**
 * PEDIDOS - Restaurante Inteligente v4
 * IMPORTANTE: Todo el procesamiento GET/POST debe ir ANTES de header.php
 */
require_once '../includes/config.php';

requireRole(['Administrador', 'Cocinero', 'Mesero']);

$db = getDB();

// ============================================================
// PROCESAMIENTO GET (ANTES de cualquier output)
// ============================================================
if (isset($_GET['estado']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $estado = sanitize($_GET['estado']);
    $estados_validos = ['Pendiente', 'EnPreparacion', 'Listo', 'Entregado', 'Cancelado'];

    if (in_array($estado, $estados_validos)) {
        $stmt = $db->prepare("UPDATE pedidos SET estado = ? WHERE id = ?");
        $stmt->execute([$estado, $id]);

        $pedidoInfo = $db->prepare("SELECT p.*, u.nombre as cliente_nombre FROM pedidos p LEFT JOIN usuarios u ON p.cliente_id = u.id WHERE p.id = ?");
        $pedidoInfo->execute([$id]);
        $ped = $pedidoInfo->fetch();

        createNotification('Pedido #' . $id, 'Estado actualizado a: ' . $estado, 'Pedido');
        redirect('pedidos.php', 'success', 'Estado actualizado a: ' . $estado);
    }
}

// ============================================================
// AQUI EMPIEZA EL OUTPUT HTML
// ============================================================
$pageTitle = 'Gestion de Pedidos';
require_once 'header.php';

// Ver detalle de pedido
$ver_id = $_GET['ver'] ?? 0;
$pedido_detalle = null;
$detalle_items = [];

if ($ver_id > 0) {
    $stmt = $db->prepare("
        SELECT p.*, u.nombre as cliente_nombre, u.email as cliente_email, u.telefono as cliente_telefono,
               m.numero as mesa_numero, m.ubicacion as mesa_ubicacion
        FROM pedidos p
        LEFT JOIN usuarios u ON p.cliente_id = u.id
        LEFT JOIN mesas m ON p.mesa_id = m.id
        WHERE p.id = ?
    ");
    $stmt->execute([$ver_id]);
    $pedido_detalle = $stmt->fetch();

    if ($pedido_detalle) {
        $stmt = $db->prepare("
            SELECT dp.*, pr.nombre as producto_nombre, pr.imagen as producto_imagen
            FROM detalle_pedidos dp
            JOIN productos pr ON dp.producto_id = pr.id
            WHERE dp.pedido_id = ?
        ");
        $stmt->execute([$ver_id]);
        $detalle_items = $stmt->fetchAll();
    }
}

// Filtros
$estado_filter = $_GET['estado'] ?? '';
$tipo_filter = $_GET['tipo'] ?? '';
$fecha_desde = $_GET['desde'] ?? '';
$fecha_hasta = $_GET['hasta'] ?? '';

$sql = "SELECT p.*, u.nombre as cliente_nombre, m.numero as mesa_numero FROM pedidos p LEFT JOIN usuarios u ON p.cliente_id = u.id LEFT JOIN mesas m ON p.mesa_id = m.id WHERE 1=1";
$params = [];

if (!empty($estado_filter)) {
    $sql .= " AND p.estado = ?";
    $params[] = $estado_filter;
}
if (!empty($tipo_filter)) {
    $sql .= " AND p.tipo = ?";
    $params[] = $tipo_filter;
}
if (!empty($fecha_desde)) {
    $sql .= " AND DATE(p.fecha_pedido) >= ?";
    $params[] = $fecha_desde;
}
if (!empty($fecha_hasta)) {
    $sql .= " AND DATE(p.fecha_pedido) <= ?";
    $params[] = $fecha_hasta;
}

$sql .= " ORDER BY p.fecha_pedido DESC";
$result = paginate($sql, $params, 15);
$pedidos = $result['data'];
?>

<h1 class="page-title"><i class="fas fa-clipboard-list"></i> Gestion de Pedidos</h1>
<p class="page-subtitle">Administra todos los pedidos del restaurante</p>

<!-- Filtros -->
<div class="card mb-3">
    <div class="card-body">
        <form method="GET" class="d-flex gap-2 flex-wrap">
            <select name="estado" class="form-control" style="max-width: 150px;" onchange="this.form.submit()">
                <option value="">Todos los estados</option>
                <option value="Pendiente" <?php echo $estado_filter == 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                <option value="EnPreparacion" <?php echo $estado_filter == 'EnPreparacion' ? 'selected' : ''; ?>>En Preparacion</option>
                <option value="Listo" <?php echo $estado_filter == 'Listo' ? 'selected' : ''; ?>>Listo</option>
                <option value="Entregado" <?php echo $estado_filter == 'Entregado' ? 'selected' : ''; ?>>Entregado</option>
                <option value="Cancelado" <?php echo $estado_filter == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
            </select>
            <select name="tipo" class="form-control" style="max-width: 150px;" onchange="this.form.submit()">
                <option value="">Todos los tipos</option>
                <option value="Mesa" <?php echo $tipo_filter == 'Mesa' ? 'selected' : ''; ?>>Mesa</option>
                <option value="ParaLlevar" <?php echo $tipo_filter == 'ParaLlevar' ? 'selected' : ''; ?>>Para Llevar</option>
            </select>
            <input type="date" name="desde" class="form-control" value="<?php echo $fecha_desde; ?>" style="max-width: 150px;" onchange="this.form.submit()">
            <input type="date" name="hasta" class="form-control" value="<?php echo $fecha_hasta; ?>" style="max-width: 150px;" onchange="this.form.submit()">
            <?php if ($estado_filter || $tipo_filter || $fecha_desde || $fecha_hasta): ?>
            <a href="pedidos.php" class="btn btn-secondary"><i class="fas fa-times"></i> Limpiar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Lista de pedidos -->
<div class="card">
    <div class="card-header">
        <h3>Lista de Pedidos</h3>
        <span class="badge badge-info"><?php echo $result['total']; ?> pedidos</span>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Cliente</th>
                        <th>Mesa/Tipo</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos as $p): ?>
                    <tr>
                        <td><strong>#<?php echo $p['id']; ?></strong></td>
                        <td><i class="fas fa-user"></i> <?php echo $p['cliente_nombre'] ?: '<span class="text-muted">Sin cliente</span>'; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $p['tipo'] == 'Mesa' ? 'primary' : 'warning'; ?>">
                                <i class="fas fa-<?php echo $p['tipo'] == 'Mesa' ? 'chair' : 'shopping-bag'; ?>"></i>
                                <?php echo $p['tipo'] == 'Mesa' ? ($p['mesa_numero'] ?: 'Sin mesa') : 'Para Llevar'; ?>
                            </span>
                        </td>
                        <td><strong class="text-primary"><?php echo formatMoney($p['total']); ?></strong></td>
                        <td>
                            <span class="badge <?php echo getEstadoBadge($p['estado']); ?>">
                                <?php echo $p['estado']; ?>
                            </span>
                        </td>
                        <td><?php echo formatDate($p['fecha_pedido']); ?></td>
                        <td>
                            <div class="d-flex gap-1">
                                <a href="pedidos.php?ver=<?php echo $p['id']; ?>" class="btn btn-sm btn-info" title="Ver detalle">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <?php if ($p['estado'] != 'Cancelado' && $p['estado'] != 'Entregado'): ?>
                                <div class="dropdown" style="position: relative;">
                                    <button class="btn btn-sm btn-warning" onclick="toggleDropdown(this)" title="Cambiar estado">
                                        <i class="fas fa-exchange-alt"></i>
                                    </button>
                                    <div class="dropdown-menu" style="display: none; position: absolute; right: 0; background: white; border: 1px solid #dee2e6; border-radius: 6px; box-shadow: 0 4px 12px rgba(0,0,0,0.15); z-index: 100; min-width: 160px;">
                                        <?php if ($p['estado'] == 'Pendiente'): ?>
                                        <a href="pedidos.php?estado=EnPreparacion&id=<?php echo $p['id']; ?>" class="dropdown-item" style="display: block; padding: 8px 12px; color: #333;"><i class="fas fa-fire text-info"></i> En Preparacion</a>
                                        <?php endif; ?>
                                        <?php if ($p['estado'] == 'EnPreparacion'): ?>
                                        <a href="pedidos.php?estado=Listo&id=<?php echo $p['id']; ?>" class="dropdown-item" style="display: block; padding: 8px 12px; color: #333;"><i class="fas fa-check text-success"></i> Listo</a>
                                        <?php endif; ?>
                                        <?php if ($p['estado'] == 'Listo'): ?>
                                        <a href="pedidos.php?estado=Entregado&id=<?php echo $p['id']; ?>" class="dropdown-item" style="display: block; padding: 8px 12px; color: #333;"><i class="fas fa-hand-holding text-primary"></i> Entregado</a>
                                        <?php endif; ?>
                                        <a href="pedidos.php?estado=Cancelado&id=<?php echo $p['id']; ?>" class="dropdown-item" style="display: block; padding: 8px 12px; color: #e74c3c;" onclick="return confirm('Cancelar este pedido?');"><i class="fas fa-times text-danger"></i> Cancelar</a>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <?php if (empty($pedidos)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No hay pedidos</h3>
        </div>
        <?php endif; ?>

        <!-- Paginacion -->
        <?php if ($result['totalPages'] > 1): ?>
        <div class="pagination">
            <?php if ($result['hasPrev']): ?>
            <a href="?page=<?php echo $result['page'] - 1; ?>&estado=<?php echo urlencode($estado_filter); ?>&tipo=<?php echo urlencode($tipo_filter); ?>&desde=<?php echo urlencode($fecha_desde); ?>&hasta=<?php echo urlencode($fecha_hasta); ?>"><i class="fas fa-chevron-left"></i></a>
            <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-left"></i></span>
            <?php endif; ?>

            <?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
            <?php if ($i == $result['page']): ?>
            <span class="active"><?php echo $i; ?></span>
            <?php else: ?>
            <a href="?page=<?php echo $i; ?>&estado=<?php echo urlencode($estado_filter); ?>&tipo=<?php echo urlencode($tipo_filter); ?>&desde=<?php echo urlencode($fecha_desde); ?>&hasta=<?php echo urlencode($fecha_hasta); ?>"><?php echo $i; ?></a>
            <?php endif; ?>
            <?php endfor; ?>

            <?php if ($result['hasNext']): ?>
            <a href="?page=<?php echo $result['page'] + 1; ?>&estado=<?php echo urlencode($estado_filter); ?>&tipo=<?php echo urlencode($tipo_filter); ?>&desde=<?php echo urlencode($fecha_desde); ?>&hasta=<?php echo urlencode($fecha_hasta); ?>"><i class="fas fa-chevron-right"></i></a>
            <?php else: ?>
            <span class="disabled"><i class="fas fa-chevron-right"></i></span>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Modal Detalle Pedido -->
<?php if ($pedido_detalle): ?>
<div class="modal-overlay active" id="detalleModal">
    <div class="modal" style="max-width: 700px;">
        <div class="modal-header">
            <h3><i class="fas fa-receipt"></i> Pedido #<?php echo $pedido_detalle['id']; ?></h3>
            <a href="pedidos.php" class="modal-close">&times;</a>
        </div>
        <div class="modal-body">
            <div class="grid grid-2 mb-3">
                <div>
                    <p><strong><i class="fas fa-user"></i> Cliente:</strong> <?php echo $pedido_detalle['cliente_nombre'] ?: 'Sin cliente'; ?></p>
                    <p><strong><i class="fas fa-envelope"></i> Email:</strong> <?php echo $pedido_detalle['cliente_email'] ?: 'N/A'; ?></p>
                    <p><strong><i class="fas fa-phone"></i> Telefono:</strong> <?php echo $pedido_detalle['cliente_telefono'] ?: 'N/A'; ?></p>
                </div>
                <div>
                    <p><strong><i class="fas fa-<?php echo $pedido_detalle['tipo'] == 'Mesa' ? 'chair' : 'shopping-bag'; ?>"></i> Tipo:</strong> 
                        <span class="badge badge-<?php echo $pedido_detalle['tipo'] == 'Mesa' ? 'primary' : 'warning'; ?>">
                            <?php echo $pedido_detalle['tipo'] == 'Mesa' ? 'En Mesa' : 'Para Llevar'; ?>
                        </span>
                    </p>
                    <?php if ($pedido_detalle['tipo'] == 'Mesa'): ?>
                    <p><strong><i class="fas fa-chair"></i> Mesa:</strong> <?php echo $pedido_detalle['mesa_numero'] ?: 'N/A'; ?> <?php echo $pedido_detalle['mesa_ubicacion'] ? '(' . $pedido_detalle['mesa_ubicacion'] . ')' : ''; ?></p>
                    <?php endif; ?>
                    <p><strong><i class="fas fa-calendar"></i> Fecha:</strong> <?php echo formatDate($pedido_detalle['fecha_pedido']); ?></p>
                </div>
            </div>

            <div style="margin-bottom: 1rem;">
                <strong>Estado:</strong>
                <span class="badge <?php echo getEstadoBadge($pedido_detalle['estado']); ?>" style="font-size: 1rem;">
                    <?php echo $pedido_detalle['estado']; ?>
                </span>
            </div>

            <?php if ($pedido_detalle['notas']): ?>
            <div class="alert alert-info">
                <strong><i class="fas fa-sticky-note"></i> Notas:</strong> <?php echo $pedido_detalle['notas']; ?>
            </div>
            <?php endif; ?>

            <h4 style="margin: 1.5rem 0 1rem; color: var(--color-secondary);"><i class="fas fa-list"></i> Productos</h4>
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Producto</th>
                            <th>Cantidad</th>
                            <th>Precio Unit.</th>
                            <th>Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalle_items as $item): ?>
                        <tr>
                            <td>
                                <div class="d-flex align-center gap-2">
                                    <div style="width: 40px; height: 40px; background: #f8f9fa; border-radius: 6px; display: flex; align-items: center; justify-content: center;">
                                        <i class="fas fa-utensils" style="color: var(--color-primary);"></i>
                                    </div>
                                    <div>
                                        <strong><?php echo $item['producto_nombre']; ?></strong>
                                        <?php if ($item['notas']): ?>
                                        <br><small class="text-muted"><?php echo $item['notas']; ?></small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </td>
                            <td><?php echo $item['cantidad']; ?></td>
                            <td><?php echo formatMoney($item['precio_unitario']); ?></td>
                            <td><strong><?php echo formatMoney($item['subtotal']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot>
                        <tr style="background: #f8f9fa;">
                            <td colspan="3" class="text-right"><strong style="font-size: 1.2rem;">TOTAL:</strong></td>
                            <td><strong style="font-size: 1.3rem; color: var(--color-primary);"><?php echo formatMoney($pedido_detalle['total']); ?></strong></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <a href="pedidos.php" class="btn btn-secondary">Cerrar</a>
            <button class="btn btn-primary no-print" onclick="window.print()">
                <i class="fas fa-print"></i> Imprimir
            </button>
        </div>
    </div>
</div>
<?php endif; ?>

<script>
function toggleDropdown(btn) {
    var menu = btn.nextElementSibling;
    var allMenus = document.querySelectorAll('.dropdown-menu');
    allMenus.forEach(function(m) {
        if (m !== menu) m.style.display = 'none';
    });
    menu.style.display = menu.style.display === 'block' ? 'none' : 'block';
}

document.addEventListener('click', function(e) {
    if (!e.target.closest('.dropdown')) {
        document.querySelectorAll('.dropdown-menu').forEach(function(m) {
            m.style.display = 'none';
        });
    }
});
</script>

<?php require_once 'footer.php'; ?>