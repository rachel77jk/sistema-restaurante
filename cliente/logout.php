<?php
require_once '../includes/config.php';

session_destroy();
redirect(APP_URL . '/login.php', 'info', 'Sesion cerrada correctamente');
?>
