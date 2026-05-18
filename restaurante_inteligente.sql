-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1
-- Tiempo de generación: 17-05-2026 a las 04:28:41
-- Versión del servidor: 10.4.32-MariaDB
-- Versión de PHP: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `restaurante_inteligente`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

CREATE TABLE `categorias` (
  `id` int(11) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `icono` varchar(50) DEFAULT 'fa-utensils',
  `orden` int(11) NOT NULL DEFAULT 0,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`, `descripcion`, `icono`, `orden`, `activo`, `fecha_creacion`) VALUES
(1, 'Entradas', 'Deliciosas entradas para comenzar', 'fa-bread-slice', 1, 1, '2026-05-16 22:36:47'),
(2, 'Platos Fuertes', 'Nuestros principales platos', 'fa-drumstick-bite', 2, 1, '2026-05-16 22:36:47'),
(3, 'Bebidas', 'Refrescos, jugos y mas', 'fa-glass-water', 3, 1, '2026-05-16 22:36:47'),
(4, 'Postres', 'Dulces tentaciones', 'fa-ice-cream', 4, 1, '2026-05-16 22:36:47'),
(5, 'Especialidades', 'Platos de la casa', 'fa-star', 5, 1, '2026-05-16 22:36:47');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalle_pedidos`
--

CREATE TABLE `detalle_pedidos` (
  `id` int(11) UNSIGNED NOT NULL,
  `pedido_id` int(11) UNSIGNED NOT NULL,
  `producto_id` int(11) UNSIGNED NOT NULL,
  `cantidad` int(11) NOT NULL DEFAULT 1,
  `precio_unitario` decimal(10,2) NOT NULL,
  `subtotal` decimal(10,2) NOT NULL,
  `notas` text DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `detalle_pedidos`
--

INSERT INTO `detalle_pedidos` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`, `subtotal`, `notas`) VALUES
(1, 1, 3, 1, 24.99, 24.99, ''),
(2, 2, 5, 1, 12.00, 12.00, NULL),
(3, 3, 3, 1, 24.99, 24.99, ''),
(4, 3, 5, 2, 12.00, 24.00, 'Sin mucho chipotle'),
(5, 4, 8, 1, 9.00, 9.00, ''),
(6, 5, 2, 2, 10.00, 20.00, ''),
(7, 6, 3, 1, 24.99, 24.99, '');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `mesas`
--

CREATE TABLE `mesas` (
  `id` int(11) UNSIGNED NOT NULL,
  `numero` varchar(20) NOT NULL,
  `capacidad` int(11) NOT NULL DEFAULT 4,
  `ubicacion` varchar(100) DEFAULT NULL,
  `estado` enum('Disponible','Ocupada','Reservada','Mantenimiento') NOT NULL DEFAULT 'Disponible'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `mesas`
--

INSERT INTO `mesas` (`id`, `numero`, `capacidad`, `ubicacion`, `estado`) VALUES
(1, 'M-01', 2, 'Terraza', 'Mantenimiento'),
(2, 'M-02', 4, 'Terraza', 'Disponible'),
(3, 'M-03', 4, 'Salon Principal', 'Ocupada'),
(4, 'M-04', 6, 'Salon Principal', 'Disponible'),
(5, 'M-05', 8, 'Salon Principal', 'Disponible'),
(6, 'M-06', 2, 'Barra', 'Disponible'),
(7, 'M-07', 4, 'Jardin', 'Disponible'),
(8, 'M-08', 10, 'VIP', 'Disponible');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `notificaciones`
--

CREATE TABLE `notificaciones` (
  `id` int(11) UNSIGNED NOT NULL,
  `usuario_id` int(11) UNSIGNED DEFAULT NULL,
  `tipo` enum('Pedido','Reservacion','Sistema','Alerta') NOT NULL DEFAULT 'Sistema',
  `titulo` varchar(200) NOT NULL,
  `mensaje` text NOT NULL,
  `leido` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `notificaciones`
--

INSERT INTO `notificaciones` (`id`, `usuario_id`, `tipo`, `titulo`, `mensaje`, `leido`, `fecha_creacion`) VALUES
(1, 5, 'Sistema', 'Nuevo cliente', 'Bienvenido Filomeno al sistema', 0, '2026-05-16 22:42:59'),
(2, NULL, 'Reservacion', 'Nueva reservacion', 'Reservacion para 2026-05-27 a las 12:00', 0, '2026-05-16 22:49:05'),
(3, NULL, 'Pedido', 'Nuevo pedido', 'Pedido #1 recibido', 0, '2026-05-16 23:01:39'),
(4, NULL, 'Pedido', 'Pedido actualizado', 'Pedido #1 ahora esta: EnPreparacion', 0, '2026-05-16 23:03:37'),
(5, NULL, 'Pedido', 'Pedido #1', 'Estado actualizado a: Listo', 0, '2026-05-16 23:04:17'),
(6, NULL, 'Pedido', 'Pedido entregado', 'Pedido #1 ha sido entregado', 0, '2026-05-16 23:05:57'),
(7, NULL, 'Pedido', 'Nuevo pedido desde mesero', 'Pedido #2 creado', 0, '2026-05-16 23:06:50'),
(8, NULL, 'Pedido', 'Pedido actualizado', 'Pedido #2 ahora esta: EnPreparacion', 0, '2026-05-16 23:09:23'),
(9, NULL, 'Pedido', 'Pedido actualizado', 'Pedido #2 ahora esta: Listo', 0, '2026-05-16 23:09:29'),
(10, NULL, 'Pedido', 'Pedido entregado', 'Pedido #2 ha sido entregado', 0, '2026-05-16 23:10:57'),
(11, NULL, 'Sistema', 'Nuevo usuario', 'Se registro el usuario: Luciano', 0, '2026-05-16 23:29:26'),
(12, NULL, 'Pedido', 'Nuevo pedido', 'Pedido #3 recibido', 0, '2026-05-16 23:38:31'),
(13, NULL, 'Pedido', 'Pedido actualizado', 'Pedido #3 ahora esta: EnPreparacion', 0, '2026-05-16 23:39:06'),
(14, NULL, 'Pedido', 'Pedido actualizado', 'Pedido #3 ahora esta: Listo', 0, '2026-05-16 23:39:21'),
(15, NULL, 'Pedido', 'Pedido #3', 'Estado actualizado a: Entregado', 0, '2026-05-16 23:39:57'),
(16, NULL, 'Pedido', 'Nuevo pedido', 'Pedido #4 recibido', 0, '2026-05-16 23:40:45'),
(17, NULL, 'Pedido', 'Pedido actualizado', 'Pedido #4 ahora esta: EnPreparacion', 0, '2026-05-16 23:41:02'),
(18, NULL, 'Pedido', 'Pedido actualizado', 'Pedido #4 ahora esta: Listo', 0, '2026-05-16 23:41:04'),
(19, NULL, 'Pedido', 'Pedido entregado', 'Pedido #4 ha sido entregado', 0, '2026-05-16 23:41:33'),
(20, NULL, 'Reservacion', 'Nueva reservacion', 'Reservacion para 2026-05-29 a las 12:00', 0, '2026-05-16 23:49:13'),
(21, NULL, 'Pedido', 'Nuevo pedido', 'Pedido #5 recibido', 0, '2026-05-17 00:39:23'),
(22, NULL, 'Pedido', 'Nuevo pedido', 'Pedido #6 recibido', 0, '2026-05-17 00:43:39');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

CREATE TABLE `pedidos` (
  `id` int(11) UNSIGNED NOT NULL,
  `cliente_id` int(11) UNSIGNED DEFAULT NULL,
  `mesa_id` int(11) UNSIGNED DEFAULT NULL,
  `tipo` enum('Mesa','Domicilio','ParaLlevar') NOT NULL DEFAULT 'Mesa',
  `estado` enum('Pendiente','EnPreparacion','Listo','Entregado','Cancelado') NOT NULL DEFAULT 'Pendiente',
  `total` decimal(10,2) NOT NULL DEFAULT 0.00,
  `notas` text DEFAULT NULL,
  `fecha_pedido` timestamp NOT NULL DEFAULT current_timestamp(),
  `fecha_actualizacion` timestamp NULL DEFAULT NULL ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `cliente_id`, `mesa_id`, `tipo`, `estado`, `total`, `notas`, `fecha_pedido`, `fecha_actualizacion`) VALUES
(1, 5, 6, 'Mesa', 'Entregado', 24.99, 'Hágalo con muchos vegetales', '2026-05-16 23:01:39', '2026-05-16 23:05:57'),
(2, NULL, NULL, 'ParaLlevar', 'Entregado', 12.00, 'Ninguna', '2026-05-16 23:06:50', '2026-05-16 23:10:57'),
(3, 5, NULL, 'ParaLlevar', 'Entregado', 48.99, 'El pescado', '2026-05-16 23:38:31', '2026-05-16 23:39:57'),
(4, 5, NULL, 'Domicilio', 'Entregado', 9.00, 'Direccion: calle Napoleon, No.200, col.Hidalgo', '2026-05-16 23:40:45', '2026-05-16 23:41:33'),
(5, 5, 2, 'Mesa', 'Cancelado', 20.00, '', '2026-05-17 00:39:23', '2026-05-17 01:32:18'),
(6, 5, 3, 'Mesa', 'Cancelado', 24.99, '', '2026-05-17 00:43:39', '2026-05-17 01:32:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

CREATE TABLE `productos` (
  `id` int(11) UNSIGNED NOT NULL,
  `categoria_id` int(11) UNSIGNED NOT NULL,
  `nombre` varchar(150) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `precio` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT 'default.jpg',
  `disponible` tinyint(1) NOT NULL DEFAULT 1,
  `destacado` tinyint(1) NOT NULL DEFAULT 0,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `descripcion`, `precio`, `imagen`, `disponible`, `destacado`, `fecha_creacion`) VALUES
(2, 1, 'Alitas BBQ', 'Alitas de pollo con salsa BBQ casera, acompañadas de apio', 10.00, '6a091a43a93f3_1778981443.jpg', 1, 0, '2026-05-16 22:36:47'),
(3, 2, 'Filete Mignon', 'Filete de res a la parrilla con salsa de vino tinto y vegetales', 24.99, '6a0918a575aa5_1778981029.jpg', 1, 1, '2026-05-16 22:36:47'),
(4, 2, 'Pasta Alfredo', 'Fettuccine en salsa cremosa de parmesano con pollo', 15.50, '6a091a7d89704_1778981501.jpg', 1, 0, '2026-05-16 22:36:47'),
(5, 2, 'Tacos de Pescado', 'Tacos de tilapia empanizada con repollo y salsa de chipotle', 12.00, '6a0918dd12606_1778981085.jpg', 1, 1, '2026-05-16 22:36:47'),
(6, 3, 'Margarita Clasica', 'Tequila, triple sec y jugo de limon fresco', 7.50, '6a091a6d59c3d_1778981485.jpg', 1, 0, '2026-05-16 22:36:47'),
(7, 3, 'Limonada Natural', 'Limonada fresca con hierbabuena', 4.00, '6a091a600108b_1778981472.jpg', 1, 0, '2026-05-16 22:36:47'),
(8, 4, 'Cheesecake', 'Cheesecake de NY con salsa de frutos rojos', 9.00, '6a0918190c9e1_1778980889.jpg', 1, 1, '2026-05-16 22:36:47'),
(9, 4, 'Flan Napolitano', 'Flan casero con caramelo', 5.00, '6a091a507fdf6_1778981456.jpg', 1, 0, '2026-05-16 22:36:47'),
(10, 5, 'Paella Valenciana', 'Arroz con mariscos, pollo y chorizo español', 30.00, '6a091840e8630_1778980928.jpg', 1, 1, '2026-05-16 22:36:47'),
(11, 1, 'Pizza de pepperoni', 'Pizza al sartén de pepperoni', 12.00, '6a091a8a9681f_1778981514.jpg', 1, 0, '2026-05-16 23:33:15');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `reservaciones`
--

CREATE TABLE `reservaciones` (
  `id` int(11) UNSIGNED NOT NULL,
  `cliente_id` int(11) UNSIGNED NOT NULL,
  `mesa_id` int(11) UNSIGNED DEFAULT NULL,
  `fecha_reserva` date NOT NULL,
  `hora_reserva` time NOT NULL,
  `num_personas` int(11) NOT NULL DEFAULT 2,
  `estado` enum('Pendiente','Confirmada','Cancelada','Completada') NOT NULL DEFAULT 'Pendiente',
  `notas` text DEFAULT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `reservaciones`
--

INSERT INTO `reservaciones` (`id`, `cliente_id`, `mesa_id`, `fecha_reserva`, `hora_reserva`, `num_personas`, `estado`, `notas`, `fecha_creacion`) VALUES
(2, 4, 8, '2026-05-29', '12:00:00', 7, 'Confirmada', 'Ninguna', '2026-05-16 23:49:13');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `roles`
--

CREATE TABLE `roles` (
  `id` int(11) UNSIGNED NOT NULL,
  `nombre` varchar(50) NOT NULL,
  `slug` varchar(50) NOT NULL,
  `descripcion` text DEFAULT NULL,
  `color` varchar(20) DEFAULT '#e67e22',
  `icono` varchar(50) DEFAULT 'fa-user',
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `roles`
--

INSERT INTO `roles` (`id`, `nombre`, `slug`, `descripcion`, `color`, `icono`, `activo`, `fecha_creacion`) VALUES
(1, 'Administrador', 'administrador', 'Acceso total al sistema', '#e74c3c', 'fa-user-shield', 1, '2026-05-17 00:38:12'),
(2, 'Cocinero', 'cocinero', 'Gestion de cocina y pedidos', '#f39c12', 'fa-utensils', 1, '2026-05-17 00:38:12'),
(3, 'Mesero', 'mesero', 'Atencion a clientes y pedidos', '#3498db', 'fa-concierge-bell', 1, '2026-05-17 00:38:12'),
(4, 'Cliente', 'cliente', 'Cliente del restaurante', '#27ae60', 'fa-user', 1, '2026-05-17 00:38:12');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

CREATE TABLE `usuarios` (
  `id` int(11) UNSIGNED NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('Administrador','Cocinero','Mesero','Cliente') NOT NULL DEFAULT 'Cliente',
  `rol_id` int(11) UNSIGNED DEFAULT NULL,
  `telefono` varchar(20) DEFAULT NULL,
  `direccion` text DEFAULT NULL,
  `activo` tinyint(1) NOT NULL DEFAULT 1,
  `fecha_registro` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `nombre`, `email`, `password`, `rol`, `rol_id`, `telefono`, `direccion`, `activo`, `fecha_registro`) VALUES
(1, 'Administrador', 'admin@restaurante.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrador', 1, '555-0100', NULL, 1, '2026-05-16 22:36:47'),
(2, 'Chef Roberto', 'chef@restaurante.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Cocinero', 2, '555-0101', NULL, 1, '2026-05-16 22:36:47'),
(3, 'Mesero Juan', 'mesero@restaurante.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Mesero', 3, '555-0102', NULL, 1, '2026-05-16 22:36:47'),
(4, 'Rebeca', 'lebeca@demo.com', '$2y$10$9hYb1eL9vSn0bW2CaCY6quX2B5v7B0TBUOJxhLkoO85lj81wIhOFC', 'Cliente', 4, '8348865576', '', 1, '2026-05-16 22:36:47'),
(5, 'Filomeno', 'filomeno@mail.com', '$2y$10$V9lRlSYF763t3mI.dr5rKeu/uWD7bUJ19BzLSVOln1IGNBHpU55wa', 'Cliente', 4, '8347707865', 'Ninguna', 1, '2026-05-16 22:42:59'),
(6, 'Chef Luciano', 'luciano@mail.com', '$2y$10$OODiu2CdDXI4LmRkpNoRt.HghtWoT3ODB1h.II7RYFJERnWuvUUX.', 'Cocinero', 2, '8342534323', '', 1, '2026-05-16 23:29:26');

--
-- Índices para tablas volcadas
--

--
-- Indices de la tabla `categorias`
--
ALTER TABLE `categorias`
  ADD PRIMARY KEY (`id`);

--
-- Indices de la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_pedido` (`pedido_id`),
  ADD KEY `fk_producto` (`producto_id`);

--
-- Indices de la tabla `mesas`
--
ALTER TABLE `mesas`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_numero` (`numero`);

--
-- Indices de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_notif_usuario` (`usuario_id`);

--
-- Indices de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_cliente` (`cliente_id`),
  ADD KEY `fk_mesa` (`mesa_id`);

--
-- Indices de la tabla `productos`
--
ALTER TABLE `productos`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_categoria` (`categoria_id`);

--
-- Indices de la tabla `reservaciones`
--
ALTER TABLE `reservaciones`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_res_cliente` (`cliente_id`),
  ADD KEY `fk_res_mesa` (`mesa_id`);

--
-- Indices de la tabla `roles`
--
ALTER TABLE `roles`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_slug` (`slug`);

--
-- Indices de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uk_email` (`email`),
  ADD KEY `fk_usuarios_rol` (`rol_id`);

--
-- AUTO_INCREMENT de las tablas volcadas
--

--
-- AUTO_INCREMENT de la tabla `categorias`
--
ALTER TABLE `categorias`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT de la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=8;

--
-- AUTO_INCREMENT de la tabla `mesas`
--
ALTER TABLE `mesas`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=9;

--
-- AUTO_INCREMENT de la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT de la tabla `pedidos`
--
ALTER TABLE `pedidos`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT de la tabla `productos`
--
ALTER TABLE `productos`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT de la tabla `reservaciones`
--
ALTER TABLE `reservaciones`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT de la tabla `roles`
--
ALTER TABLE `roles`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT de la tabla `usuarios`
--
ALTER TABLE `usuarios`
  MODIFY `id` int(11) UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalle_pedidos`
--
ALTER TABLE `detalle_pedidos`
  ADD CONSTRAINT `fk_detalle_pedido` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_detalle_producto` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `notificaciones`
--
ALTER TABLE `notificaciones`
  ADD CONSTRAINT `fk_notif_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `fk_pedidos_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL,
  ADD CONSTRAINT `fk_pedidos_mesa` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_productos_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`) ON DELETE CASCADE;

--
-- Filtros para la tabla `reservaciones`
--
ALTER TABLE `reservaciones`
  ADD CONSTRAINT `fk_res_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `usuarios` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `fk_res_mesa` FOREIGN KEY (`mesa_id`) REFERENCES `mesas` (`id`) ON DELETE SET NULL;

--
-- Filtros para la tabla `usuarios`
--
ALTER TABLE `usuarios`
  ADD CONSTRAINT `fk_usuarios_rol` FOREIGN KEY (`rol_id`) REFERENCES `roles` (`id`) ON DELETE SET NULL;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
