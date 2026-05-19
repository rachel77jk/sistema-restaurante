<?php
/**
 * RESTAURANTE INTELIGENTE v4
 * Configuracion principal - Compatible PHP 7.4+
 * IMPORTANTE: Este archivo NO debe tener espacios ni saltos de linea antes de <?php
 */

// ==================== SESION (PRIMERO, ANTES DE CUALQUIER OUTPUT) ====================
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    session_start();
}

// ==================== CONSTANTES BASE ====================
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__) . '/');
}

// ==================== CONFIGURACION BD ====================
define('DB_HOST', 'localhost');
define('DB_USER', 'restaurante_user');
define('DB_PASS', 'TuPassword123!');
define('DB_NAME', 'restaurante_inteligente');
define('DB_CHARSET', 'utf8mb4');

// ==================== CONFIGURACION APP ====================
define('APP_NAME', 'Restaurante Inteligente');
define('APP_VERSION', '4.0');
define('APP_URL', 'http://78.12.240.135');
define('ADMIN_URL', APP_URL . '/admin');
define('CLIENTE_URL', APP_URL . '/cliente');
define('ASSETS_URL', APP_URL . '/assets');

// ==================== CONEXION BD ====================
function getDB() {
    static $pdo = null;
    if ($pdo === null) {
        try {
            $dsn = 'mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=' . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            die('Error de conexion: ' . $e->getMessage());
        }
    }
    return $pdo;
}

// ==================== FUNCIONES AUXILIARES ====================

function redirect($url, $tipo = '', $mensaje = '') {
    if (!empty($tipo) && !empty($mensaje)) {
        $_SESSION['flash'] = ['tipo' => $tipo, 'mensaje' => $mensaje];
    }
    if (!headers_sent()) {
        header('Location: ' . $url);
        exit();
    } else {
        // Fallback si headers ya fueron enviados
        echo '<script>window.location.href="' . $url . '";</script>';
        echo '<noscript><meta http-equiv="refresh" content="0;url=' . $url . '"></noscript>';
        exit();
    }
}

function getFlash() {
    if (isset($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

function isLoggedIn() {
    return isset($_SESSION['usuario_id']) && !empty($_SESSION['usuario_id']);
}

function hasRole($rol) {
    return isLoggedIn() && isset($_SESSION['usuario_rol']) && $_SESSION['usuario_rol'] === $rol;
}

function requireRole($roles) {
    if (!isLoggedIn()) {
        redirect(APP_URL . '/login.php', 'error', 'Debes iniciar sesion');
    }
    if (is_array($roles)) {
        if (!in_array($_SESSION['usuario_rol'], $roles)) {
            redirect(APP_URL . '/login.php', 'error', 'No tienes permisos');
        }
    } else {
        if ($_SESSION['usuario_rol'] !== $roles) {
            redirect(APP_URL . '/login.php', 'error', 'No tienes permisos');
        }
    }
}

function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
}

function formatMoney($amount) {
    return '$' . number_format((float)$amount, 2);
}

function formatDate($date) {
    return date('d/m/Y H:i', strtotime($date));
}

function generateToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function uploadImage($file, $directory = 'uploads/') {
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
    $maxSize = 2 * 1024 * 1024;

    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Error al subir archivo'];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return ['success' => false, 'message' => 'Formato no permitido. Use: ' . implode(', ', $allowed)];
    }

    if ($file['size'] > $maxSize) {
        return ['success' => false, 'message' => 'Archivo muy grande. Max 2MB'];
    }

    $filename = uniqid() . '_' . time() . '.' . $ext;
    $uploadDirPath = BASE_PATH . $directory;

    if (!is_dir($uploadDirPath)) {
        if (!mkdir($uploadDirPath, 0755, true) && !is_dir($uploadDirPath)) {
            return ['success' => false, 'message' => 'No se pudo crear el directorio de subida'];
        }
    }

    $uploadPath = $uploadDirPath . $filename;

    if (move_uploaded_file($file['tmp_name'], $uploadPath)) {
        return ['success' => true, 'filename' => $filename];
    }

    return ['success' => false, 'message' => 'Error al mover archivo'];
}

function getDashboardStats() {
    $db = getDB();
    $stats = [];

    $stats['total_pedidos'] = $db->query("SELECT COUNT(*) FROM pedidos")->fetchColumn();
    $stats['pedidos_hoy'] = $db->query("SELECT COUNT(*) FROM pedidos WHERE DATE(fecha_pedido) = CURDATE()")->fetchColumn();
    $stats['total_ventas'] = $db->query("SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE estado != 'Cancelado'")->fetchColumn();
    $stats['ventas_hoy'] = $db->query("SELECT COALESCE(SUM(total), 0) FROM pedidos WHERE DATE(fecha_pedido) = CURDATE() AND estado != 'Cancelado'")->fetchColumn();
    $stats['total_clientes'] = $db->query("SELECT COUNT(*) FROM usuarios WHERE rol = 'Cliente'")->fetchColumn();
    $stats['total_productos'] = $db->query("SELECT COUNT(*) FROM productos WHERE disponible = 1")->fetchColumn();
    $stats['mesas_ocupadas'] = $db->query("SELECT COUNT(*) FROM mesas WHERE estado = 'Ocupada'")->fetchColumn();
    $stats['mesas_disponibles'] = $db->query("SELECT COUNT(*) FROM mesas WHERE estado = 'Disponible'")->fetchColumn();

    return $stats;
}

function getRecentOrders($limit = 10) {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT p.*, u.nombre as cliente_nombre, m.numero as mesa_numero 
        FROM pedidos p 
        LEFT JOIN usuarios u ON p.cliente_id = u.id 
        LEFT JOIN mesas m ON p.mesa_id = m.id 
        ORDER BY p.fecha_pedido DESC 
        LIMIT ?
    ");
    $stmt->execute([$limit]);
    return $stmt->fetchAll();
}

function getUnreadNotifications($userId = null) {
    $db = getDB();
    if ($userId) {
        $stmt = $db->prepare("SELECT * FROM notificaciones WHERE usuario_id = ? AND leido = 0 ORDER BY fecha_creacion DESC LIMIT 10");
        $stmt->execute([$userId]);
    } else {
        $stmt = $db->query("SELECT * FROM notificaciones WHERE leido = 0 ORDER BY fecha_creacion DESC LIMIT 10");
    }
    return $stmt->fetchAll();
}

function createNotification($titulo, $mensaje, $tipo = 'Sistema', $usuarioId = null) {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO notificaciones (usuario_id, tipo, titulo, mensaje) VALUES (?, ?, ?, ?)");
    return $stmt->execute([$usuarioId, $tipo, $titulo, $mensaje]);
}

function getEstadoBadge($estado) {
    $badges = [
        'Pendiente' => 'badge-warning',
        'EnPreparacion' => 'badge-info',
        'Listo' => 'badge-success',
        'Entregado' => 'badge-primary',
        'Cancelado' => 'badge-danger',
        'Disponible' => 'badge-success',
        'Ocupada' => 'badge-danger',
        'Reservada' => 'badge-warning',
        'Mantenimiento' => 'badge-secondary',
        'Confirmada' => 'badge-success',
        'Completada' => 'badge-primary'
    ];
    return isset($badges[$estado]) ? $badges[$estado] : 'badge-secondary';
}

function getRolIcon($rol) {
    $icons = [
        'Administrador' => 'fa-user-shield',
        'Cocinero' => 'fa-utensils',
        'Mesero' => 'fa-concierge-bell',
        'Cliente' => 'fa-user'
    ];
    return isset($icons[$rol]) ? $icons[$rol] : 'fa-user';
}

function paginate($query, $params = [], $perPage = 10) {
    $db = getDB();
    $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
    $offset = ($page - 1) * $perPage;

    $countStmt = $db->prepare("SELECT COUNT(*) FROM (" . $query . ") as t");
    $countStmt->execute($params);
    $total = $countStmt->fetchColumn();

    $stmt = $db->prepare($query . " LIMIT " . $perPage . " OFFSET " . $offset);
    $stmt->execute($params);
    $data = $stmt->fetchAll();

    $totalPages = ceil($total / $perPage);

    return [
        'data' => $data,
        'page' => $page,
        'perPage' => $perPage,
        'total' => $total,
        'totalPages' => $totalPages,
        'hasNext' => $page < $totalPages,
        'hasPrev' => $page > 1
    ];
}

function logActivity($accion, $detalle = '') {
    $logFile = BASE_PATH . 'logs/activity.log';
    $logDir = dirname($logFile);
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    $line = date('Y-m-d H:i:s') . " | " . $_SESSION['usuario_nombre'] . " | " . $accion . " | " . $detalle . "
";
    file_put_contents($logFile, $line, FILE_APPEND | LOCK_EX);
}
