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

// Crear pedido desde mesero
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['nuevo_pedido'])) {
    $mesa_id = intval($_POST['mesa_id'] ?? 0);
    $cliente_id = !empty($_POST['cliente_id']) ? intval($_POST['cliente_id']) : null;
    $tipo = sanitize($_POST['tipo'] ?? 'Mesa');
    $notas = sanitize($_POST['notas'] ?? '');
    $productos_pedido = $_POST['productos'] ?? [];
    $cantidades = $_POST['cantidades'] ?? [];

    if (empty($productos_pedido)) {
        redirect('mesero.php', 'error', 'Debe seleccionar al menos un producto');
    }

    // Validar que se seleccione cliente
    if (empty($cliente_id)) {
        redirect('mesero.php', 'error', 'Debe seleccionar un cliente');
    }

    // Validar mesa si es tipo Mesa
    if ($tipo == 'Mesa' && empty($mesa_id)) {
        redirect('mesero.php', 'error', 'Debe seleccionar una mesa para pedidos en mesa');
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
        $stmt = $db->prepare("INSERT INTO pedidos (cliente_id, mesa_id, tipo, total, notas) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$cliente_id, $mesa_id > 0 ? $mesa_id : null, $tipo, $total, $notas]);
        $pedido_id = $db->lastInsertId();

        foreach ($items as $item) {
            $stmt = $db->prepare("INSERT INTO detalle_pedidos (pedido_id, producto_id, cantidad, precio_unitario, subtotal) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$pedido_id, $item['id'], $item['cantidad'], $item['precio'], $item['subtotal']]);
        }

        if ($mesa_id > 0 && $tipo == 'Mesa') {
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
$clientes = $db->query("SELECT id, nombre, telefono FROM usuarios WHERE rol = 'Cliente' AND activo = 1 ORDER BY nombre")->fetchAll();
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
.entrega-card .cliente {
    font-size: 0.95rem;
    color: var(--color-secondary);
    margin: 0.3rem 0;
    font-weight: 600;
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

/* Estilo para el selector de cliente */
.cliente-select-group {
    position: relative;
}
.cliente-select-group .form-control {
    padding-right: 40px;
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
            <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 1rem;">
                <?php foreach ($pedidos_listos as $p): ?>
                <div class="entrega-card" onclick="if(confirm('Marcar pedido #<?php echo $p['id']; ?> como entregado?')) location.href='mesero.php?entregar=1&id=<?php echo $p['id']; ?>'">
                    <div class="numero">#<?php echo $p['id']; ?></div>
                    <div class="cliente">
                        <i class="fas fa-user"></i> <?php echo $p['cliente_nombre'] ?: 'Sin cliente'; ?>
                    </div>
                    <div class="mesa">
                        <i class="fas fa-<?php echo $p['tipo'] == 'Mesa' ? 'chair' : 'shopping-bag'; ?>"></i> 
                        <?php echo $p['tipo'] == 'Mesa' ? ($p['mesa_numero'] ?: 'Sin mesa') : 'Para Llevar'; ?>
                    </div>
                    <div class="tipo">
                        <?php echo formatMoney($p['total']); ?>
                    </div>
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

    <!-- Nuevo Pedido -->
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-plus-circle"></i> Nuevo Pedido</h3>
        </div>
        <div class="card-body">
            <form method="POST" id="pedidoForm">
                <input type="hidden" name="nuevo_pedido" value="1">

                <!-- Tipo de pedido -->
                <div class="form-group">
                    <label class="form-label">Tipo de Pedido *</label>
                    <div style="display: flex; gap: 1rem;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 20px; border: 2px solid var(--color-border); border-radius: var(--radius-sm); flex: 1; justify-content: center; transition: var(--transition);" id="labelMesa" onclick="seleccionarTipo('Mesa')">
                            <input type="radio" name="tipo" value="Mesa" id="tipoMesa" checked style="width: auto;" onchange="cambiarTipo()">
                            <i class="fas fa-chair"></i> En Mesa
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer; padding: 10px 20px; border: 2px solid var(--color-border); border-radius: var(--radius-sm); flex: 1; justify-content: center; transition: var(--transition);" id="labelLlevar" onclick="seleccionarTipo('ParaLlevar')">
                            <input type="radio" name="tipo" value="ParaLlevar" id="tipoLlevar" style="width: auto;" onchange="cambiarTipo()">
                            <i class="fas fa-shopping-bag"></i> Para Llevar
                        </label>
                    </div>
                </div>

                <!-- Cliente -->
                <div class="form-group">
                    <label class="form-label">Cliente *</label>
                    <select name="cliente_id" class="form-control" required>
                        <option value="">Seleccione un cliente...</option>
                        <?php foreach ($clientes as $c): ?>
                        <option value="<?php echo $c['id']; ?>">
                            <?php echo $c['nombre']; ?> 
                            <?php if ($c['telefono']): ?>| <?php echo $c['telefono']; ?><?php endif; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Mesa (solo para tipo Mesa) -->
                <div class="form-group" id="mesaGroup">
                    <label class="form-label">Mesa *</label>
                    <select name="mesa_id" class="form-control" id="mesaSelect">
                        <option value="">Seleccione una mesa...</option>
                        <?php foreach ($mesas_disponibles as $m): ?>
                        <option value="<?php echo $m['id']; ?>">
                            <?php echo $m['numero']; ?> - <?php echo $m['ubicacion']; ?> (<?php echo $m['capacidad']; ?> pers.)
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-group">
                    <label class="form-label">Notas</label>
                    <textarea name="notas" class="form-control" rows="2" placeholder="Instrucciones especiales..."></textarea>
                </div>

                <div class="form-group">
                    <label class="form-label">Seleccionar Productos *</label>
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
                        <th>Mesa/Tipo</th>
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
                        <td><i class="fas fa-user"></i> <?php echo $p['cliente_nombre'] ?: '-'; ?></td>
                        <td>
                            <span class="badge badge-<?php echo $p['tipo'] == 'Mesa' ? 'primary' : 'warning'; ?>">
                                <i class="fas fa-<?php echo $p['tipo'] == 'Mesa' ? 'chair' : 'shopping-bag'; ?>"></i>
                                <?php echo $p['tipo'] == 'Mesa' ? ($p['mesa_numero'] ?: 'Sin mesa') : 'Para Llevar'; ?>
                            </span>
                        </td>
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
function seleccionarTipo(tipo) {
    document.getElementById('tipoMesa').checked = (tipo === 'Mesa');
    document.getElementById('tipoLlevar').checked = (tipo === 'ParaLlevar');
    cambiarTipo();
}

function cambiarTipo() {
    var esMesa = document.getElementById('tipoMesa').checked;
    var mesaGroup = document.getElementById('mesaGroup');
    var mesaSelect = document.getElementById('mesaSelect');
    var labelMesa = document.getElementById('labelMesa');
    var labelLlevar = document.getElementById('labelLlevar');

    if (esMesa) {
        mesaGroup.style.display = 'block';
        mesaSelect.setAttribute('required', 'required');
        labelMesa.style.borderColor = 'var(--color-primary)';
        labelMesa.style.background = 'rgba(230, 126, 34, 0.1)';
        labelLlevar.style.borderColor = 'var(--color-border)';
        labelLlevar.style.background = 'transparent';
    } else {
        mesaGroup.style.display = 'none';
        mesaSelect.removeAttribute('required');
        mesaSelect.value = '';
        labelLlevar.style.borderColor = 'var(--color-primary)';
        labelLlevar.style.background = 'rgba(230, 126, 34, 0.1)';
        labelMesa.style.borderColor = 'var(--color-border)';
        labelMesa.style.background = 'transparent';
    }
}

// Inicializar estado
cambiarTipo();

// Seleccion de productos con click en la tarjeta
document.querySelectorAll('.producto-check').forEach(function(div) {
    div.addEventListener('click', function(e) {
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
document.getElementById('pedidoForm').addEventListener('submit', function(e) {
    var checked = this.querySelectorAll('input[name="productos[]"]:checked');
    if (checked.length === 0) {
        e.preventDefault();
        alert('Debe seleccionar al menos un producto');
        return false;
    }
    
    var tipo = document.getElementById('tipoMesa').checked ? 'Mesa' : 'ParaLlevar';
    if (tipo === 'Mesa') {
        var mesa = document.getElementById('mesaSelect').value;
        if (!mesa) {
            e.preventDefault();
            alert('Debe seleccionar una mesa para pedidos en mesa');
            return false;
        }
    }
});

// Auto-refresh cada 30 segundos
setTimeout(function() {
    location.reload();
}, 30000);
</script>

<?php require_once 'footer.php'; ?>