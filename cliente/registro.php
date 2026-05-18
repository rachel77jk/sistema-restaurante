<?php
$pageTitle = 'Registro de Cliente';
require_once '../includes/config.php';

if (isLoggedIn()) {
    redirect('menu.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = sanitize($_POST['nombre'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';
    $telefono = sanitize($_POST['telefono'] ?? '');
    $direccion = sanitize($_POST['direccion'] ?? '');

    if (empty($nombre) || empty($email) || empty($password)) {
        $error = 'Todos los campos obligatorios deben completarse';
    } elseif ($password !== $password_confirm) {
        $error = 'Las contrasenas no coinciden';
    } elseif (strlen($password) < 6) {
        $error = 'La contrasena debe tener al menos 6 caracteres';
    } else {
        $db = getDB();

        // Verificar email unico
        $stmt = $db->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Este correo ya esta registrado';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $db->prepare("INSERT INTO usuarios (nombre, email, password, rol, telefono, direccion) VALUES (?, ?, ?, 'Cliente', ?, ?)");
            $stmt->execute([$nombre, $email, $hash, $telefono, $direccion]);

            // Auto-login
            $userId = $db->lastInsertId();
            $_SESSION['usuario_id'] = $userId;
            $_SESSION['usuario_nombre'] = $nombre;
            $_SESSION['usuario_email'] = $email;
            $_SESSION['usuario_rol'] = 'Cliente';

            createNotification('Nuevo cliente', 'Bienvenido ' . $nombre . ' al sistema', 'Sistema', $userId);
            redirect('menu.php', 'success', 'Bienvenido, ' . $nombre . '! Tu cuenta ha sido creada.');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registro - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-logo">
            <i class="fas fa-user-plus"></i>
            <h1>Crear Cuenta</h1>
            <p>Registrate para realizar pedidos en <?php echo APP_NAME; ?></p>
        </div>

        <div class="login-card animate-fadeInUp">
            <h2><i class="fas fa-user-plus"></i> Registro de Cliente</h2>

            <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
            </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="form-group">
                    <label class="form-label"><i class="fas fa-user"></i> Nombre Completo *</label>
                    <input type="text" name="nombre" class="form-control" required value="<?php echo isset($_POST['nombre']) ? htmlspecialchars($_POST['nombre']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fas fa-envelope"></i> Correo Electronico *</label>
                    <input type="email" name="email" class="form-control" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                </div>

                <div class="grid grid-2">
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-key"></i> Contrasena *</label>
                        <input type="password" name="password" class="form-control" required minlength="6" placeholder="Minimo 6 caracteres">
                    </div>
                    <div class="form-group">
                        <label class="form-label"><i class="fas fa-key"></i> Confirmar Contrasena *</label>
                        <input type="password" name="password_confirm" class="form-control" required minlength="6">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fas fa-phone"></i> Telefono</label>
                    <input type="text" name="telefono" class="form-control" value="<?php echo isset($_POST['telefono']) ? htmlspecialchars($_POST['telefono']) : ''; ?>">
                </div>

                <div class="form-group">
                    <label class="form-label"><i class="fas fa-map-marker-alt"></i> Direccion</label>
                    <textarea name="direccion" class="form-control" rows="2"><?php echo isset($_POST['direccion']) ? htmlspecialchars($_POST['direccion']) : ''; ?></textarea>
                </div>

                <button type="submit" class="btn btn-success btn-block btn-lg">
                    <i class="fas fa-user-plus"></i> Crear Cuenta
                </button>
            </form>

            <div style="text-align: center; margin-top: 1.5rem;">
                <p>Ya tienes cuenta? <a href="../login.php" style="color: var(--color-primary); font-weight: 600;">Inicia sesion aqui</a></p>
            </div>
        </div>

        <div class="login-footer">
            <p>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?></p>
        </div>
    </div>
</body>
</html>