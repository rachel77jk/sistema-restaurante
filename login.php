<?php
/**
 * LOGIN - Restaurante Inteligente v4
 * NO debe haber espacios ni saltos de linea antes de <?php
 */
require_once 'includes/config.php';

if (isLoggedIn()) {
    if (hasRole('Cliente')) {
        redirect(CLIENTE_URL . '/menu.php');
    } elseif (hasRole('Cocinero')) {
        redirect(ADMIN_URL . '/cocina.php');
    } elseif (hasRole('Mesero')) {
        redirect(ADMIN_URL . '/mesero.php');
    } else {
        redirect(ADMIN_URL . '/dashboard.php');
    }
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'Por favor complete todos los campos';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT id, nombre, email, password, rol FROM usuarios WHERE email = ? AND activo = 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['usuario_id'] = $user['id'];
            $_SESSION['usuario_nombre'] = $user['nombre'];
            $_SESSION['usuario_email'] = $user['email'];
            $_SESSION['usuario_rol'] = $user['rol'];

            if ($user['rol'] === 'Cliente') {
                redirect(CLIENTE_URL . '/menu.php', 'success', 'Bienvenido, ' . $user['nombre']);
            } elseif ($user['rol'] === 'Cocinero') {
                redirect(ADMIN_URL . '/cocina.php', 'success', 'Bienvenido, ' . $user['nombre']);
            } elseif ($user['rol'] === 'Mesero') {
                redirect(ADMIN_URL . '/mesero.php', 'success', 'Bienvenido, ' . $user['nombre']);
            } else {
                redirect(ADMIN_URL . '/dashboard.php', 'success', 'Bienvenido, ' . $user['nombre']);
            }
        } else {
            $error = 'Credenciales incorrectas';
        }
    }
}
?><!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Iniciar Sesion - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-utensils"></i>
            <h1><?php echo APP_NAME; ?></h1>
            <p>Sistema de Gestion para Restaurantes</p>
        </div>

        <div class="login-card animate-fadeInUp">
            <h2><i class="fas fa-lock"></i> Iniciar Sesion</h2>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <?php $flash = getFlash(); if ($flash): ?>
            <div class="alert alert-<?php echo $flash['tipo']; ?>">
                <i class="fas fa-info-circle"></i>
                <?php echo $flash['mensaje']; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-envelope"></i> Correo Electronico</label>
                    <input type="email" name="email" class="form-control" placeholder="admin@restaurante.com" required autofocus>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fas fa-key"></i> Contraseña</label>
                    <input type="password" name="password" class="form-control" placeholder="admin123" required>
                </div>

                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-sign-in-alt"></i> Ingresar
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem;">
                <a href="cliente/registro.php" style="color: var(--color-primary); font-weight: 600;">
                    <i class="fas fa-user-plus"></i> Crear cuenta de cliente
                </a>
            </div>
        </div>

        <div class="login-footer">
            <p><i class="fas fa-info-circle"></i> Credenciales : admin@restaurante.com Contra: password</p>
            <p><i class="fas fa-user-shield"></i> Tambien: chef@restaurante.com / mesero@restaurante.com</p>
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?> v<?php echo APP_VERSION; ?></p>
        </div>
    </div>
</body>
</html>