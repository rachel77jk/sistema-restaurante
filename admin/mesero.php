<?php
/**
 * PANEL MESERO - Restaurante Inteligente v4
 * IMPORTANTE: Todo el procesamiento POST debe ir ANTES de header.php
 */
require_once '../includes/config.php';

// Solo meseros y administradores
requireRole(['Mesero', 'Administrador']);

$db = getDB();

// ============================================================
// PROCESAMIENTO DE FORMULARIOS (ANTES de cualquier output)
// ============================================================

// Actualizar estado de pedido (entregar)
if (isset($_GET['entregar']) && isset($_GET['id'])) {
    $id = intval($_GET['id']);
    $stmt = $db->prepare("UPDATE pedidos SET estado = 'Entregado' WHERE id = ? AND estado = 'Listo'");
    $stmt->execute([$id]);

    if ($stmt->rowCount() > 0) {
        $pedido = $db->prepare("SELECT mesa_id, tipo FROM pedidos WHERE id = ?");
        $pedido->execute([$id]);
        $p = $pedido->fetch();
        if ($p && $p['mesa_id'] && $p['tipo'] == 'Mesa') {
            $db->prepare("UPDATE mesas SET estado = 'Disponible' WHERE id = ?")->execute([$p['mesa_id']]);
        }
        createNotification('Pedido entregado', 'Pedido #' . $id . ' ha sido entregado', 'Pedido');
        redirect('mesero.php', 'success', 'Pedido #' . $id . ' entregado correctamente');
    }
}

// Crear pedido rapido desde mesero
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_pedido'])) {
    $mesa_id = intval($_POST['mesa_id'] ?? 0);
    $tipo = sanitize($_POST['tipo'] ?? 'Mesa');
    $notas = sanitize($_POST['notas'] ?? '');
    $productos_pedido = $_POST['productos'] ?? [];
    $cantidades = $_POST['cantidades'] ?? [];

    if (empty($productos_pedido)) {
        redirect('mesero.php', 'error', 'Debe seleccionar al menos un producto');
    }

    $total = 0;
    $items = [];

    foreach ($productos_pedido as $idx => $producto_id) {
        $cantidad = max(1, intval($cantidades[$idx] ?? 1));
        $stmt = $db->prepare("SELECT id, nombre, precio FROM productos WHERE id = ? AND disponible = 1");
        $stmt->execute([$producto_id]);
        $prod = $stmt->fetch();

        if ($prod) {
            $items[] = [
                'id' => $prod['id'],
                'cantidad' => $cantidad,
                'precio' => $prod['precio'],
                'subtotal' => $prod['precio'] * $cantidad
            ];
            $total += $prod['precio'] * $cantidad;
        }
    }

    if (!empty($items)) {
        $stmt = $db->prepare("INSERT INTO pedidos (cliente_id, mesa_id, tipo, total, notas) VALUES (NULL, ?, ?, ?, ?)");
        $stmt->execute([$mesa_id > 0 ? $mesa_id : null, $tipo, $total, $notas]);
        $pedido_id = $db->lastInsertId();

        foreach ($items as $item) {
            $stmt = $db->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$pedido_id, $item['id'], $item['cantidad'], $item['precio'], $item['subtotal']]);
        }

        if ($mesa_id > 0) {
            $db->prepare("UPDATE mesas SET estado = 'Ocupada' WHERE id = ?")->execute([$mesa_id]);
        }

        createNotification('Nuevo pedido desde mesero', 'Pedido #' . $pedido_id . ' creado', 'Pedido');
        redirect('mesero.php', 'success', 'Pedido #' . $pedido_id . ' creado correctamente');
    }
}

// ============================================================
// AQUI EMPIEZA EL OUTPUT HTML
// ============================================================
$pageTitle = 'Panel de Mesero';
require_once 'header.php';

// Obtener pedidos listos para entregar
$pedidos_listos = $db->query("
    SELECT p.*, u.nombre as cliente_nombre, m.numero as mesa_numero, m.ubicacion as mesa_ubicacion
    FROM pedidos p
    LEFT JOIN usuarios u ON p.cliente_id = u.id
    LEFT JOIN mesas m ON p.mesa_id = m.id
    WHERE p.estado = 'Listo'
    ORDER BY p.fecha_pedido ASC
")->fetchAll();

// Obtener pedidos pendientes y en preparacion
$pedidos_activos = $db->query("
    SELECT p.*, u.nombre as cliente_nombre, m.numero as mesa_numero
    FROM pedidos p
    LEFT JOIN usuarios u ON p.cliente_id = u.id
    LEFT JOIN mesas m ON p.mesa_id = m.id
    WHERE p.estado IN ('Pendiente', 'EnPreparacion')
    ORDER BY p.fecha_pedido DESC
    LIMIT 10
")->fetchAll();

// Estadisticas
$pedidos_hoy = $db->query("SELECT COUNT(*) FROM pedidos WHERE DATE(fecha_pedido) = CURDATE() AND estado != 'Cancelado'")->fetchColumn();
$entregados_hoy = $db->query("SELECT COUNT(*) FROM pedidos WHERE DATE(fecha_pedido) = CURDATE() AND estado = 'Entregado'")->fetchColumn();
$listos = count($pedidos_listos);

// Datos para nuevo pedido
$productos = $db->query("SELECT p.id, p.nombre, p.precio, c.nombre as categoria FROM productos p JOIN categorias c ON p.categoria_id = c.id WHERE p.disponible = 1 ORDER BY c.nombre, p.nombre")->fetchAll();
$mesas_disponibles = $db->query("SELECT id, numero, capacidad, ubicacion FROM mesas WHERE estado = 'Disponible' ORDER BY numero")->fetchAll();
?>

<style>
.entrega-card {
    background: linear-gradient(135deg, #d4edda, #c3e6cb);
    border: 2px solid var(--color-success);
    border-radius: var(--radius-md);
    padding: 1.5rem;
    text-align: center;
    transition: var(--transition);
    cursor: pointer;
}
.entrega-card:hover {
    transform: translateY(-3px);
    box-shadow: var(--shadow-md);
}
.entrega-card .numero {
    font-size: 2rem;
    font-weight: 700;
    color: var(--color-success);
}
.entrega-card .mesa {
    font-size: 1.1rem;
    color: var(--color-secondary);
    margin: 0.5rem 0;
}
.entrega-card .tipo {
    font-size: 0.9rem;
    color: var(--color-gray);
}

.pedido-rapido-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
    gap: 10px;
    max-height: 300px;
    overflow-y: auto;
    padding: 10px;
    background: #f8f9fa;
    border-radius: var(--radius-sm);
}
.producto-check {
    background: var(--color-white);
    border: 2px solid var(--color-border);
    border-radius: var(--radius-sm);
    padding: 10px;
    cursor: pointer;
    transition: var(--transition);
    display: flex;
    align-items: center;
    gap: 10px;
}
.producto-check:hover {
    border-color: var(--color-primary);
}
.producto-check input[type="checkbox"] {
    width: 20px;
    height: 20px;
    cursor: pointer;
    flex-shrink: 0;
}
.producto-check .prod-info {
    flex: 1;
    cursor: pointer;
}
.producto-check .prod-info strong {
    display: block;
    font-size: 0.9rem;
}
.producto-check .prod-info small {
    color: var(--color-gray);
    font-size: 0.8rem;
}
.producto-check .precio {
    color: var(--color-primary);
    font-weight: 700;
    font-size: 0.9rem;
    white-space: nowrap;
}
.producto-check .qty-input {
    width: 50px;
    text-align: center;
    border: 1px solid var(--color-border);
    border-radius: var(--radius-sm);
    padding: 4px;
}
.producto-check.selected {
    border-color: var(--color-success);
    background: rgba(39, 174, 96, 0.1);
}
</style>

<h1 class="page-title"><i class="fas fa-concierge-bell"></i> Panel de Mesero</h1>
<p class="page-subtitle">Gestion de pedidos y entregas</p>

<!-- Estadisticas -->
<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon primary"><i class="fas fa-calendar-day"></i></div>
        <div class="stat-info">
            <h3><?php echo $pedidos_hoy; ?></h3>
            <p>Pedidos Hoy</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon success"><i class="fas fa-check-double"></i></div>
        <div class="stat-info">
            <h3><?php echo $entregados_hoy; ?></h3>
            <p>Entregados Hoy</p>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon warning"><i class="fas fa-bell"></i></div>
        <div class="stat-info">
            <h3><?php echo $listos; ?></h3>
            <p>Listos para Entregar</p>
        </div>
    </div>
</div>

<div class="grid grid-2">
    <!-- Pedidos Listos -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-bell"></i> Listos para Entregar</h3>
            <button class="btn btn-sm btn-outline" onclick="location.reload()">
                <i class="fas fa-sync-alt"></i>
            </button>
        </div>
        <div class="card-body">
            <?php if (!empty($pedidos_listos)): ?>
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 1rem;">
                <?php foreach ($pedidos_listos as $p): ?>
                <div class="entrega-card" onclick="if(confirm('Marcar pedido #<?php echo $p['id']; ?> como entregado?')) location.href='mesero.php?entregar=1&id=<?php echo $p['id']; ?>'">
                    <div class="numero">#<?php echo $p['id']; ?></div>
                    <div class="mesa">
                        <i class="fas fa-chair"></i> <?php echo $p['mesa_numero'] ?: 'Sin mesa'; ?>
                    </div>
                    <div class="tipo">
                        <i class="fas fa-<?php echo $p['tipo'] == 'Mesa' ? 'chair' : ($p['tipo'] == 'Domicilio' ? 'motorcycle' : 'shopping-bag'); ?>"></i>
                        <?php echo $p['tipo']; ?> | <?php echo formatMoney($p['total']); ?>
                    </div>
                    <?php if ($p['cliente_nombre']): ?>
                    <div style="margin-top: 0.5rem; font-size: 0.85rem; color: var(--color-gray);">
                        <i class="fas fa-user"></i> <?php echo $p['cliente_nombre']; ?>
                    </div>
                    <?php endif; ?>
                    <button class="btn btn-success btn-sm" style="margin-top: 1rem; width: 100%;" onclick="event.stopPropagation(); if(confirm('Marcar pedido #<?php echo $p['id']; ?> como entregado?')) location.href='mesero.php?entregar=1&id=<?php echo $p['id']; ?>'">
                        <i class="fas fa-hand-holding"></i> Entregar
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash" style="font-size: 4rem;"></i>
                <h3>No hay pedidos listos</h3>
                <p>Los pedidos listos apareceran aqui</p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Nuevo Pedido Rapido -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle"></i> Nuevo Pedido Rapido</h3>
        </div>
        <div class="card-body">
            <form method="POST" id="pedidoRapidoForm">
                <input type="hidden" name="nuevo_pedido" value="1">

                <div class="grid grid-2 mb-2">
                    <div class="form-group">
                        <label class="form-label">Tipo</label>
                        <select name="tipo" class="form-control" id="tipoPedido" onchange="toggleMesaRapido()">
                            <option value="Mesa">En Mesa</option>
                            <option value="Domicilio">Domicilio</option>
                            <option value="ParaLlevar">Para Llevar</option>
                        </select>
                    </div>
                    <div class="form-group" id="mesaRapidoGroup">
                        <label class="form-label">Mesa</label>
                        <select name="mesa_id" class="form-control">
                            <option value="">Seleccione...</option>
                            <?php foreach ($mesas_disponibles as $m): ?>
                            <option value="<?php echo $m['id']; ?>"><?php echo $m['numero']; ?> - <?php echo $m['ubicacion']; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="2" placeholder="Instrucciones especiales..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Seleccionar Productos</label>
                    <div class="pedido-rapido-grid" id="productosGrid">
                        <?php foreach ($productos as $prod): ?>
                        <div class="producto-check" data-id="<?php echo $prod['id']; ?>">
                            <input type="checkbox" name="productos[]" value="<?php echo $prod['id']; ?>" id="prod_<?php echo $prod['id']; ?>">
                            <label class="prod-info" for="prod_<?php echo $prod['id']; ?>">
                                <strong><?php echo $prod['nombre']; ?></strong>
                                <small><?php echo $prod['categoria']; ?></small>
                            </label>
                            <span class="precio"><?php echo formatMoney($prod['precio']); ?></span>
                            <input type="number" name="cantidades[]" value="1" min="1" max="20" class="qty-input">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <button type="submit" class="btn btn-success btn-block btn-lg">
                    <i class="fas fa-paper-plane"></i> Crear Pedido
                </button>
            </form>
        </div>
    </div>
</div>

<!-- Pedidos Activos -->
<div class="card mt-3">
    <div class="card-header">
        <h3><i class="fas fa-clock"></i> Pedidos en Proceso</h3>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table">
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Cliente</th>
                        <th>Mesa</th>
                        <th>Tipo</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Tiempo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pedidos_activos as $p): 
                        $minutos = floor((time() - strtotime($p['fecha_pedido'])) / 60);
                    ?>
                    <tr>
                        <td><strong>#<?php echo $p['id']; ?></strong></td>
                        <td><?php echo $p['cliente_nombre'] ?: '-'; ?></td>
                        <td><?php echo $p['mesa_numero'] ?: 'N/A'; ?></td>
                        <td><span class="badge badge-<?php echo $p['tipo'] == 'Mesa' ? 'primary' : ($p['tipo'] == 'Domicilio' ? 'info' : 'warning'); ?>"><?php echo $p['tipo']; ?></span></td>
                        <td><strong><?php echo formatMoney($p['total']); ?></strong></td>
                        <td><span class="badge <?php echo getEstadoBadge($p['estado']); ?>"><?php echo $p['estado']; ?></span></td>
                        <td class="<?php echo $minutos > 20 ? 'text-danger' : ''; ?>" style="font-weight: <?php echo $minutos > 20 ? '700' : '400'; ?>;">
                            <?php echo $minutos < 1 ? 'Hace momentos' : $minutos . ' min'; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if (empty($pedidos_activos)): ?>
        <div class="empty-state">
            <i class="fas fa-inbox"></i>
            <h3>No hay pedidos en proceso</h3>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function toggleMesaRapido() {
    var tipo = document.getElementById('tipoPedido').value;
    document.getElementById('mesaRapidoGroup').style.display = tipo === 'Mesa' ? 'block' : 'none';
}

// Seleccion de productos con click en la tarjeta
document.querySelectorAll('.producto-check').forEach(function(div) {
    div.addEventListener('click', function(e) {
        // No activar si se hizo click en el input number o checkbox directamente
        if (e.target.type === 'number' || e.target.type === 'checkbox') {
            if (e.target.type === 'checkbox') {
                this.classList.toggle('selected', e.target.checked);
            }
            return;
        }

        var checkbox = this.querySelector('input[type="checkbox"]');
        checkbox.checked = !checkbox.checked;
        this.classList.toggle('selected', checkbox.checked);
    });
});

// Validar formulario
document.getElementById('pedidoRapidoForm').addEventListener('submit', function(e) {
    var checked = this.querySelectorAll('input[name="productos[]"]:checked');
    if (checked.length === 0) {
        e.preventDefault();
        alert('Debe seleccionar al menos un producto');
    }
});

// Auto-refresh cada 30 segundos
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php require_once 'footer.php'; ?>