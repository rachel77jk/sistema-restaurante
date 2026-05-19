<?php
/**
 * MIS PEDIDOS - Restaurante Inteligente v5 (Simplificado)
 * Solo pedidos para llevar del cliente
 */
require_once '../includes/config.php';

if (!isLoggedIn() || !hasRole('Cliente')) {
    redirect('../login.php', 'warning', 'Debes iniciar sesion como cliente');
}

$db = getDB();

// Cancelar pedido
if (isset($_GET['cancelar'])) {
    $id = intval($_GET['cancelar']);
    $stmt = $db->prepare("UPDATE pedidos SET estado = 'Cancelado' WHERE id = ? AND cliente_id = ? AND estado = 'Pendiente'");
    $stmt->execute([$id, $_SESSION['usuario_id']]);
    if ($stmt->rowCount() > 0) {
        redirect('mis_pedidos.php', 'success', 'Pedido cancelado correctamente');
    } else {
        redirect('mis_pedidos.php', 'error', 'No se puede cancelar este pedido');
    }
}

$pageTitle = 'Mis Pedidos';
require_once 'header_cliente.php';

// Obtener SOLO pedidos para llevar del cliente
$estado_filter = $_GET['estado'] ?? '';
$sql = "SELECT p.* FROM pedidos p WHERE p.cliente_id = ? AND p.tipo = 'ParaLlevar'";
$params = [$_SESSION['usuario_id']];

if (!empty($estado_filter)) {
    $sql .= " AND p.estado = ?";
    $params[] = $estado_filter;
}

$sql .= " ORDER BY p.fecha_pedido DESC";
$result = paginate($sql, $params, 10);
$pedidos = $result['data'];
?>

<div style="padding: 2rem; max-width: 1200px; margin: 0 auto;">
    <h1 class="page-title"><i class="fas fa-shopping-bag"></i> Mis Pedidos para Llevar</h1>
    <p class="page-subtitle">Historial de tus pedidos</p>

    <div class="card mb-3">
        <div class="card-body">
            <form method="GET" class="d-flex gap-2">
                <select name="estado" class="form-control" style="max-width: 200px;" onchange="this.form.submit()">
                    <option value="">Todos los estados</option>
                    <option value="Pendiente" <?php echo $estado_filter == 'Pendiente' ? 'selected' : ''; ?>>Pendiente</option>
                    <option value="EnPreparacion" <?php echo $estado_filter == 'EnPreparacion' ? 'selected' : ''; ?>>En Preparacion</option>
                    <option value="Listo" <?php echo $estado_filter == 'Listo' ? 'selected' : ''; ?>>Listo</option>
                    <option value="Entregado" <?php echo $estado_filter == 'Entregado' ? 'selected' : ''; ?>>Entregado</option>
                    <option value="Cancelado" <?php echo $estado_filter == 'Cancelado' ? 'selected' : ''; ?>>Cancelado</option>
                </select>
                <?php if ($estado_filter): ?>
                <a href="mis_pedidos.php" class="btn btn-secondary"><i class="fas fa-times"></i></a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Pedido #</th>
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
                            <td><strong class="text-primary"><?php echo formatMoney($p['total']); ?></strong></td>
                            <td>
                                <span class="badge <?php echo getEstadoBadge($p['estado']); ?>">
                                    <i class="fas fa-shopping-bag"></i> <?php echo $p['estado']; ?>
                                </span>
                            </td>
                            <td><?php echo formatDate($p['fecha_pedido']); ?></td>
                            <td>
                                <?php if ($p['estado'] == 'Pendiente'): ?>
                                <a href="mis_pedidos.php?cancelar=<?php echo $p['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Cancelar este pedido?');">
                                    <i class="fas fa-times"></i> Cancelar
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if (empty($pedidos)): ?>
            <div class="empty-state">
                <i class="fas fa-shopping-bag"></i>
                <h3>No tienes pedidos para llevar aun</h3>
                <a href="menu.php" class="btn btn-primary mt-2"><i class="fas fa-utensils"></i> Ver Menu</a>
            </div>
            <?php endif; ?>

            <?php if ($result['totalPages'] > 1): ?>
            <div class="pagination">
                <?php if ($result['hasPrev']): ?>
                <a href="?page=<?php echo $result['page'] - 1; ?>&estado=<?php echo urlencode($estado_filter); ?>"><i class="fas fa-chevron-left"></i></a>
                <?php else: ?>
                <span class="disabled"><i class="fas fa-chevron-left"></i></span>
                <?php endif; ?>
                <?php for ($i = 1; $i <= $result['totalPages']; $i++): ?>
                <?php if ($i == $result['page']): ?>
                <span class="active"><?php echo $i; ?></span>
                <?php else: ?>
                <a href="?page=<?php echo $i; ?>&estado=<?php echo urlencode($estado_filter); ?>"><?php echo $i; ?></a>
                <?php endif; ?>
                <?php endfor; ?>
                <?php if ($result['hasNext']): ?>
                <a href="?page=<?php echo $result['page'] + 1; ?>&estado=<?php echo urlencode($estado_filter); ?>"><i class="fas fa-chevron-right"></i></a>
                <?php else: ?>
                <span class="disabled"><i class="fas fa-chevron-right"></i></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once 'footer_cliente.php'; ?>