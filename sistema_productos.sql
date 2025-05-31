-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Servidor: 127.0.0.1:3306
-- Tiempo de generación: 31-05-2025 a las 04:20:03
-- Versión del servidor: 8.0.30
-- Versión de PHP: 8.3.14

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Base de datos: `sistema_productos`
--

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `categorias`
--

DROP TABLE IF EXISTS `categorias`;
CREATE TABLE IF NOT EXISTS `categorias` (
  `id` int NOT NULL AUTO_INCREMENT,
  `nombre` varchar(50) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `categorias`
--

INSERT INTO `categorias` (`id`, `nombre`) VALUES
(1, 'Comidas'),
(2, 'Bebidas');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `configuracion`
--

DROP TABLE IF EXISTS `configuracion`;
CREATE TABLE IF NOT EXISTS `configuracion` (
  `id` int NOT NULL AUTO_INCREMENT,
  `titulo` varchar(100) NOT NULL DEFAULT 'Mi Restaurante',
  `color_principal` varchar(7) NOT NULL DEFAULT '#2196F3',
  `fondo` varchar(100) NOT NULL DEFAULT 'fondo-default.jpg',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `configuracion`
--

INSERT INTO `configuracion` (`id`, `titulo`, `color_principal`, `fondo`) VALUES
(1, 'Mi Restaurante', '#2196F3', 'fondo-default.jpg');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `detalles_pedido`
--

DROP TABLE IF EXISTS `detalles_pedido`;
CREATE TABLE IF NOT EXISTS `detalles_pedido` (
  `id` int NOT NULL AUTO_INCREMENT,
  `pedido_id` int NOT NULL,
  `producto_id` int NOT NULL,
  `cantidad` int NOT NULL,
  `precio_unitario` decimal(10,2) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `pedido_id` (`pedido_id`),
  KEY `producto_id` (`producto_id`)
) ENGINE=InnoDB AUTO_INCREMENT=18 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `detalles_pedido`
--

INSERT INTO `detalles_pedido` (`id`, `pedido_id`, `producto_id`, `cantidad`, `precio_unitario`) VALUES
(5, 4, 3, 1, 50.00),
(6, 4, 2, 1, 32.00),
(8, 5, 2, 1, 32.00),
(10, 6, 2, 1, 32.00),
(12, 7, 2, 1, 32.00),
(13, 8, 3, 1, 50.00),
(14, 10, 1, 1, 25.00),
(15, 10, 3, 1, 50.00);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `pedidos`
--

DROP TABLE IF EXISTS `pedidos`;
CREATE TABLE IF NOT EXISTS `pedidos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `usuario_id` int NOT NULL,
  `fecha` datetime DEFAULT CURRENT_TIMESTAMP,
  `estado` enum('pendiente','completado') DEFAULT 'pendiente',
  PRIMARY KEY (`id`),
  KEY `usuario_id` (`usuario_id`)
) ENGINE=InnoDB AUTO_INCREMENT=14 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `pedidos`
--

INSERT INTO `pedidos` (`id`, `usuario_id`, `fecha`, `estado`) VALUES
(4, 2, '2025-05-30 15:12:08', 'completado'),
(5, 2, '2025-05-30 15:13:54', 'completado'),
(6, 2, '2025-05-30 15:21:24', 'completado'),
(7, 2, '2025-05-30 15:59:04', 'completado'),
(8, 2, '2025-05-30 16:01:09', 'completado'),
(9, 2, '2025-05-30 16:01:30', 'completado'),
(10, 2, '2025-05-30 16:02:15', 'completado');

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `productos`
--

DROP TABLE IF EXISTS `productos`;
CREATE TABLE IF NOT EXISTS `productos` (
  `id` int NOT NULL AUTO_INCREMENT,
  `categoria_id` int DEFAULT NULL,
  `nombre` varchar(100) NOT NULL,
  `descripcion` text,
  `precio` decimal(10,2) NOT NULL,
  `imagen` varchar(255) DEFAULT NULL,
  `stock` int NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `fk_producto_categoria` (`categoria_id`)
) ENGINE=InnoDB AUTO_INCREMENT=11 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `productos`
--

INSERT INTO `productos` (`id`, `categoria_id`, `nombre`, `descripcion`, `precio`, `imagen`, `stock`) VALUES
(1, 1, 'a', 'Carne de res, lechuga, jitomate y cebolla', 25.00, 'WhatsApp Image 2025-05-28 at 6.00.04 PM.jpeg', 45),
(2, 1, 'Hamburguesa hawaiana', 'Carne de res, piña, jamón y queso derretido', 32.00, 'WhatsApp Image 2025-05-28 at 5.56.43 PM.jpeg', 34),
(3, 1, 'Hamburguesa doble carne', 'Dos carnes de res, queso, tocino y aderezos', 50.00, 'WhatsApp Image 2025-05-28 at 6.00.44 PM.jpeg', 25),
(4, 1, 'Hamburguesa Triple Carne', 'Tres carnes de res, queso y todos los complementos', 60.00, 'WhatsApp Image 2025-05-28 at 6.01.31 PM.jpeg', 20),
(5, 1, 'Hot Dogs Sencillos', 'Pan, salchicha, cebolla, tomate y condimentos (promoción 3x$25)', 25.00, 'jochos.jpg', 60),
(6, 2, 'Michelada', 'Cerveza preparada con limón, sal, chile y salsas especiales (1 litro)', 70.00, 'WhatsApp Image 2025-05-28 at 6.08.03 PM.jpeg', 30),
(7, 2, 'Azulito', 'Bebida refrescante con vodka y saborizante azul (1 litro)', 75.00, 'WhatsApp Image 2025-05-28 at 6.02.27 PM.jpeg', 25),
(8, 2, 'Jugo Boing', 'Jugo de frutas en diferentes sabores (250 ml)', 10.00, 'WhatsApp Image 2025-05-28 at 6.07.42 PM.jpeg', 90),
(9, 2, 'Coca-Cola', 'Refresco de cola (600 ml)', 20.00, 'coca-cola.jpg', 70);

-- --------------------------------------------------------

--
-- Estructura de tabla para la tabla `usuarios`
--

DROP TABLE IF EXISTS `usuarios`;
CREATE TABLE IF NOT EXISTS `usuarios` (
  `id` int NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `rol` enum('admin','vendedor','cliente') NOT NULL,
  `nombre` varchar(100) NOT NULL,
  `activo` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  UNIQUE KEY `username` (`username`)
) ENGINE=InnoDB AUTO_INCREMENT=12 DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Volcado de datos para la tabla `usuarios`
--

INSERT INTO `usuarios` (`id`, `username`, `password`, `rol`, `nombre`, `activo`) VALUES
(1, 'Daniel', '$2y$10$gjls3d4yOkiClBV4rIPkvOSWbioCgI5j3D7siHqICndUuGKLdR.Gu', 'admin', 'Daniel Lira', 1),
(2, 'Pablo', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'vendedor', 'Pablo Avelar', 1),
(3, 'Cliente', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'cliente', 'Cliente', 1);

--
-- Restricciones para tablas volcadas
--

--
-- Filtros para la tabla `detalles_pedido`
--
ALTER TABLE `detalles_pedido`
  ADD CONSTRAINT `detalles_pedido_ibfk_1` FOREIGN KEY (`pedido_id`) REFERENCES `pedidos` (`id`),
  ADD CONSTRAINT `detalles_pedido_ibfk_2` FOREIGN KEY (`producto_id`) REFERENCES `productos` (`id`);

--
-- Filtros para la tabla `pedidos`
--
ALTER TABLE `pedidos`
  ADD CONSTRAINT `pedidos_ibfk_1` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`);

--
-- Filtros para la tabla `productos`
--
ALTER TABLE `productos`
  ADD CONSTRAINT `fk_producto_categoria` FOREIGN KEY (`categoria_id`) REFERENCES `categorias` (`id`);
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
