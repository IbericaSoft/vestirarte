/*
SQLyog Community v12.03 (64 bit)
MySQL - 5.5.28 : Database - vestirarte
*********************************************************************
*/

/*!40101 SET NAMES utf8 */;

/*!40101 SET SQL_MODE=''*/;

/*!40014 SET @OLD_UNIQUE_CHECKS=@@UNIQUE_CHECKS, UNIQUE_CHECKS=0 */;
/*!40014 SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS, FOREIGN_KEY_CHECKS=0 */;
/*!40101 SET @OLD_SQL_MODE=@@SQL_MODE, SQL_MODE='NO_AUTO_VALUE_ON_ZERO' */;
/*!40111 SET @OLD_SQL_NOTES=@@SQL_NOTES, SQL_NOTES=0 */;
/*Table structure for table `_navigator` */

DROP TABLE IF EXISTS `_navigator`;

CREATE TABLE `_navigator` (
  `secuencia` int(11) DEFAULT NULL,
  `accion` varchar(25) NOT NULL DEFAULT '',
  `metodo_pre_accion` varchar(255) DEFAULT NULL,
  `plantilla` varchar(255) DEFAULT NULL,
  `estado` char(3) DEFAULT 'ACT',
  `descripcion` varchar(255) DEFAULT NULL,
  `accion_salida_true` varchar(255) DEFAULT NULL,
  `accion_salida_false` varchar(255) DEFAULT NULL,
  UNIQUE KEY `accion` (`accion`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `_navigator_details` */

DROP TABLE IF EXISTS `_navigator_details`;

CREATE TABLE `_navigator_details` (
  `secuencia` int(6) DEFAULT '0',
  `accion` varchar(25) DEFAULT NULL,
  `descripcion` varchar(255) DEFAULT NULL,
  `bloque` varchar(25) DEFAULT NULL,
  `orden` int(2) DEFAULT NULL,
  `exclusiones` varchar(255) NOT NULL DEFAULT '',
  `paquete` varchar(255) DEFAULT NULL,
  `pagina` varchar(255) DEFAULT NULL,
  `estado` char(3) DEFAULT 'ACT'
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

/*Table structure for table `app_config` */

DROP TABLE IF EXISTS `app_config`;

CREATE TABLE `app_config` (
  `descripcion` varchar(255) COLLATE latin1_spanish_ci NOT NULL,
  `clave` varchar(255) COLLATE latin1_spanish_ci NOT NULL,
  `valor` longtext COLLATE latin1_spanish_ci NOT NULL,
  `editable` enum('S','N') COLLATE latin1_spanish_ci NOT NULL DEFAULT 'N',
  `fmodificacion` datetime NOT NULL,
  `id_administrador` int(6) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `articulos` */

DROP TABLE IF EXISTS `articulos`;

CREATE TABLE `articulos` (
  `id` int(6) unsigned NOT NULL COMMENT 'Identificador único para este elemento',
  `articulo` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL COMMENT 'nombre del elemento',
  `id_subfamilia` int(11) unsigned NOT NULL COMMENT 'enlace con tabla subfamilias',
  `id_iva` int(11) NOT NULL COMMENT 'enlace tabla iva',
  `id_autor` int(11) NOT NULL COMMENT 'enlace con tabla autores',
  `peso` float(7,3) NOT NULL COMMENT 'Lo necesitamos para los portes',
  `codigo` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL COMMENT 'Codigo interno del elemento',
  `codigo_proveedor` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `descripcion` varchar(15000) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL COMMENT 'Texto comercial',
  `precio` float(7,2) NOT NULL COMMENT 'Precio del Articulo',
  `oferta` float(7,2) DEFAULT NULL COMMENT 'Precio Rebajado del Articulo',
  `estado` enum('ON','OFF','XXX') CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL DEFAULT 'OFF' COMMENT 'habilitar, deshabilitar o eliminar el elemento',
  `vendible` enum('SI','NO') CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL DEFAULT 'SI' COMMENT 'Producto vendible',
  `enlace` varchar(255) DEFAULT NULL,
  `personalizable` enum('SI','NO') CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL DEFAULT 'NO' COMMENT 'Producto personalizable',
  `foto_1` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL COMMENT 'foto principal del elemento',
  `foto_2` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `foto_3` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `foto_4` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `fichero_1` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `fichero_2` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `falta` datetime NOT NULL COMMENT 'auditoria',
  `fmodificacion` datetime NOT NULL COMMENT 'auditoria',
  `id_administrador` int(11) NOT NULL COMMENT 'auditoria',
  PRIMARY KEY (`id`),
  UNIQUE KEY `i_codigo` (`codigo`),
  UNIQUE KEY `i_articulo` (`articulo`),
  KEY `i_familia` (`id_subfamilia`),
  KEY `i_autor` (`id_autor`),
  CONSTRAINT `FK_subfamilias` FOREIGN KEY (`id_subfamilia`) REFERENCES `subfamilias` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `articulos_variedad` */

DROP TABLE IF EXISTS `articulos_variedad`;

CREATE TABLE `articulos_variedad` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT COMMENT 'Identificador único para este elemento ',
  `id_articulo` int(6) NOT NULL COMMENT 'identificador del articulo origen',
  `id_talla` int(6) NOT NULL COMMENT 'identificador del talla a vincular',
  `stock` int(11) NOT NULL,
  `codigo` varchar(255) NOT NULL,
  `estado` enum('ON','OFF') CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL DEFAULT 'ON' COMMENT 'estado de la vinculacion',
  `falta` datetime NOT NULL COMMENT 'auditoria',
  `fmodificacion` datetime NOT NULL COMMENT 'auditoria',
  `id_administrador` int(11) NOT NULL COMMENT 'auditoria',
  PRIMARY KEY (`id`),
  UNIQUE KEY `i_articulo_talla` (`id_articulo`,`id_talla`),
  KEY `i_articulo` (`id_articulo`)
) ENGINE=InnoDB AUTO_INCREMENT=34 DEFAULT CHARSET=utf8;

/*Table structure for table `articulos_vinculados` */

DROP TABLE IF EXISTS `articulos_vinculados`;

CREATE TABLE `articulos_vinculados` (
  `id_articulo` int(11) unsigned NOT NULL COMMENT 'identificador del articulo origen',
  `id_articulo_vinculo` int(11) unsigned NOT NULL COMMENT 'identificador del articulo a vincular',
  `tipo` enum('EQUIVALENTE','RELACIONADO') COLLATE latin1_spanish_ci NOT NULL COMMENT 'tipo de relaccion',
  UNIQUE KEY `articulo_vinculo` (`id_articulo`,`id_articulo_vinculo`),
  KEY `FK_articulos_vinculados2` (`id_articulo_vinculo`),
  CONSTRAINT `FK_articulos_vinculados` FOREIGN KEY (`id_articulo`) REFERENCES `articulos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;

/*Table structure for table `autores` */

DROP TABLE IF EXISTS `autores`;

CREATE TABLE `autores` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'identificador unico del elemento',
  `autor` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL COMMENT 'nombre',
  `descripcion` varchar(2500) DEFAULT NULL,
  `estado` enum('ON','OFF','XXX') CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL DEFAULT 'ON',
  `falta` datetime NOT NULL,
  `fmodificacion` datetime NOT NULL,
  `id_administrador` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=7 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `categorias` */

DROP TABLE IF EXISTS `categorias`;

CREATE TABLE `categorias` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'identificador unico para el elemento',
  `categoria` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL COMMENT 'nombre del elemento',
  `estado` enum('ON','OFF','XXX') CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL DEFAULT 'OFF' COMMENT 'habilita o deshabilita el elemento',
  `posicion` int(1) NOT NULL DEFAULT '1' COMMENT 'posicion de presentacion del elemento',
  `descripcion` text CHARACTER SET latin1 COLLATE latin1_spanish_ci COMMENT 'descripcion',
  `foto_1` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'foto principal del elemento',
  `foto_2` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'foto 2 del elemento',
  `foto_3` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'foto 3 del elemento',
  `falta` datetime NOT NULL COMMENT 'auditoria',
  `fmodificacion` datetime NOT NULL COMMENT 'auditoria',
  `id_administrador` int(6) NOT NULL COMMENT 'auditoria',
  PRIMARY KEY (`id`),
  UNIQUE KEY `categoria` (`categoria`)
) ENGINE=InnoDB AUTO_INCREMENT=9 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `clientes` */

DROP TABLE IF EXISTS `clientes`;

CREATE TABLE `clientes` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT COMMENT 'identificar unico de cliente',
  `nombre` varchar(255) COLLATE latin1_spanish_ci NOT NULL COMMENT 'nombre cliente o nombre comercial',
  `razon` varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'razon social usadado para las facturas',
  `nifcif` varchar(15) COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'nif o cif es indistinto',
  `email` varchar(255) COLLATE latin1_spanish_ci NOT NULL COMMENT 'email que además le sirve para entrar en el sistema',
  `password` varchar(255) COLLATE latin1_spanish_ci NOT NULL,
  `telefono` varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'telefono o movil de contacto',
  `direccion` varchar(255) COLLATE latin1_spanish_ci NOT NULL COMMENT 'direccion de envio',
  `poblacion` varchar(255) COLLATE latin1_spanish_ci NOT NULL COMMENT 'localidad de envio',
  `id_provincia` int(11) NOT NULL COMMENT 'provincia del envio',
  `cpostal` varchar(255) COLLATE latin1_spanish_ci NOT NULL COMMENT 'cpostal del envio',
  `id_pais` int(11) NOT NULL COMMENT 'pais del envio',
  `fdireccion` varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'direccion de la factura',
  `fpoblacion` varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'localidad de la factura',
  `f_id_provincia` int(11) DEFAULT NULL COMMENT 'provincia de la factura',
  `fcpostal` varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'cpostal de la factura',
  `f_id_pais` int(11) DEFAULT NULL COMMENT 'pais de la factura',
  `suscripcion` enum('S','N') COLLATE latin1_spanish_ci DEFAULT 'N' COMMENT 'le podemos enviar ofertas por email',
  `observaciones` varchar(2000) COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'Observaciones de cada cliente',
  `falta` datetime NOT NULL COMMENT 'auditoria',
  `fmodificacion` datetime NOT NULL COMMENT 'auditoria',
  `estado` enum('ON','OFF','XXX') COLLATE latin1_spanish_ci NOT NULL DEFAULT 'ON' COMMENT 'activo,inactivo,eliminado',
  `id_administrador` int(6) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `iemail` (`email`),
  KEY `ibuscacomun` (`email`,`password`,`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `colores` */

DROP TABLE IF EXISTS `colores`;

CREATE TABLE `colores` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'identificador unico del elemento',
  `color` varchar(255) COLLATE latin1_spanish_ci NOT NULL COMMENT 'color',
  `estado` enum('ON','OFF','XXX') COLLATE latin1_spanish_ci NOT NULL DEFAULT 'ON',
  `falta` datetime NOT NULL,
  `fmodificacion` datetime NOT NULL,
  `id_administrador` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `NewIndex1` (`color`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;

/*Table structure for table `familias` */

DROP TABLE IF EXISTS `familias`;

CREATE TABLE `familias` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'identificador unico del elemento',
  `familia` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL COMMENT 'nombre del elemento',
  `id_categoria` int(11) NOT NULL COMMENT 'identificar de la categoria a la que pertenece. Cumple integridad',
  `estado` enum('ON','OFF','XXX') CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL DEFAULT 'OFF' COMMENT 'habilita o deshabilita el elemento',
  `posicion` int(1) NOT NULL COMMENT 'posicion de presentacion del elemnto',
  `descripcion` text CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL COMMENT 'descripcion',
  `foto_1` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL COMMENT 'foto principal del elemento',
  `foto_2` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'foto 2 del elemento',
  `foto_3` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'foto 3 del elemento',
  `falta` datetime NOT NULL COMMENT 'auditoria',
  `fmodificacion` datetime NOT NULL COMMENT 'auditoria',
  `id_administrador` int(6) NOT NULL COMMENT 'auditoria',
  PRIMARY KEY (`id`),
  UNIQUE KEY `familia` (`familia`,`id_categoria`),
  KEY `FK_familias` (`id_categoria`),
  CONSTRAINT `FK_familias_1` FOREIGN KEY (`id_categoria`) REFERENCES `categorias` (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=16 DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `iva` */

DROP TABLE IF EXISTS `iva`;

CREATE TABLE `iva` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'identificador unico para el elemento',
  `descripcion` varchar(255) COLLATE latin1_spanish_ci NOT NULL COMMENT 'descripcion del iva',
  `iva` float(4,2) NOT NULL COMMENT 'valor del iva',
  `falta` datetime NOT NULL COMMENT 'auditoria',
  `fmodificacion` datetime NOT NULL COMMENT 'auditoria',
  `id_administrador` int(6) NOT NULL COMMENT 'auditoria',
  `estado` enum('ON','XXX') COLLATE latin1_spanish_ci NOT NULL DEFAULT 'ON',
  PRIMARY KEY (`id`),
  UNIQUE KEY `iva` (`iva`)
) ENGINE=InnoDB AUTO_INCREMENT=3 DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `os_administradores` */

DROP TABLE IF EXISTS `os_administradores`;

CREATE TABLE `os_administradores` (
  `id` int(3) unsigned NOT NULL AUTO_INCREMENT,
  `fmodificacion` datetime DEFAULT NULL,
  `usuario` varchar(255) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nombre` varchar(255) NOT NULL,
  `id_perfil` int(1) NOT NULL DEFAULT '1',
  `email` varchar(255) NOT NULL,
  `estado` enum('ACT','DES','XXX') NOT NULL DEFAULT 'ACT',
  PRIMARY KEY (`id`),
  UNIQUE KEY `usuario` (`usuario`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB AUTO_INCREMENT=4 DEFAULT CHARSET=latin1;

/*Table structure for table `os_administradores_perfiles` */

DROP TABLE IF EXISTS `os_administradores_perfiles`;

CREATE TABLE `os_administradores_perfiles` (
  `perfil` int(1) NOT NULL DEFAULT '0',
  `desc_perfil` enum('ROOT','ADMIN','USER','SYSTEM') NOT NULL DEFAULT 'ROOT'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `os_applications` */

DROP TABLE IF EXISTS `os_applications`;

CREATE TABLE `os_applications` (
  `id` int(3) NOT NULL,
  `application` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL,
  `icon` varchar(255) NOT NULL DEFAULT 'application.png',
  `rol` int(3) NOT NULL DEFAULT '0',
  `fecha_install` datetime NOT NULL,
  `win_title` varchar(255) NOT NULL DEFAULT 'Sin título',
  `win_width` int(3) NOT NULL DEFAULT '600',
  `win_height` int(3) NOT NULL DEFAULT '400',
  `win_modal` tinyint(1) NOT NULL DEFAULT '1',
  `win_close` tinyint(1) NOT NULL DEFAULT '1',
  `win_minimize` tinyint(1) NOT NULL DEFAULT '1',
  `win_maximize` tinyint(1) NOT NULL DEFAULT '1',
  `win_resize` tinyint(1) NOT NULL DEFAULT '1',
  `text` varchar(255) NOT NULL,
  `description` varchar(255) NOT NULL,
  `position` int(11) NOT NULL DEFAULT '0',
  `action` varchar(255) NOT NULL DEFAULT 'start'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `os_config` */

DROP TABLE IF EXISTS `os_config`;

CREATE TABLE `os_config` (
  `rol` int(1) NOT NULL DEFAULT '1',
  `clave` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL,
  `valor` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL,
  `fecha` datetime NOT NULL,
  `descripcion` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `os_files` */

DROP TABLE IF EXISTS `os_files`;

CREATE TABLE `os_files` (
  `id_file` int(11) NOT NULL,
  `id_folder` int(11) NOT NULL,
  `file` varchar(255) NOT NULL,
  `fecha` datetime NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `os_folder` */

DROP TABLE IF EXISTS `os_folder`;

CREATE TABLE `os_folder` (
  `id_folder` int(11) NOT NULL,
  `folder` varchar(255) NOT NULL,
  `fecha` datetime NOT NULL,
  `id_rol` int(1) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `os_icons_user` */

DROP TABLE IF EXISTS `os_icons_user`;

CREATE TABLE `os_icons_user` (
  `user_id` int(6) NOT NULL,
  `icon_id` varchar(255) NOT NULL,
  `_class` varchar(255) NOT NULL,
  `_do` varchar(255) DEFAULT NULL,
  `_width` int(11) DEFAULT NULL,
  `_height` int(11) DEFAULT NULL,
  `_top` int(11) DEFAULT NULL,
  `_left` int(11) DEFAULT NULL,
  `_maximize` varchar(5) DEFAULT NULL,
  `_minimize` varchar(5) DEFAULT NULL,
  `_resizable` varchar(5) DEFAULT NULL,
  `_closable` varchar(5) DEFAULT NULL,
  `_status` varchar(255) DEFAULT NULL,
  `_parameters` varchar(255) DEFAULT NULL,
  `_title` varchar(255) DEFAULT NULL,
  `_icon` varchar(255) DEFAULT NULL,
  `_itop` int(255) DEFAULT NULL,
  `_ileft` int(255) DEFAULT NULL,
  `_ititle` varchar(255) DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `os_preferences_user` */

DROP TABLE IF EXISTS `os_preferences_user`;

CREATE TABLE `os_preferences_user` (
  `id_user` int(3) NOT NULL,
  `property` varchar(255) NOT NULL,
  `value` varchar(255) NOT NULL,
  `fecha` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `os_process_user` */

DROP TABLE IF EXISTS `os_process_user`;

CREATE TABLE `os_process_user` (
  `user_id` int(6) NOT NULL,
  `process_id` varchar(255) NOT NULL,
  `_class` varchar(255) NOT NULL,
  `_do` varchar(255) NOT NULL,
  `_title` varchar(255) DEFAULT NULL,
  `_width` int(11) DEFAULT NULL,
  `_height` int(11) DEFAULT NULL,
  `_top` int(11) DEFAULT NULL,
  `_left` int(11) DEFAULT NULL,
  `_maximize` varchar(5) DEFAULT NULL,
  `_minimize` varchar(5) DEFAULT NULL,
  `_resizable` varchar(5) DEFAULT NULL,
  `_closable` varchar(5) DEFAULT NULL,
  `_parameters` varchar(255) DEFAULT NULL,
  `_status` varchar(255) DEFAULT NULL,
  `date` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `os_sessions` */

DROP TABLE IF EXISTS `os_sessions`;

CREATE TABLE `os_sessions` (
  `session` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL,
  `fecha_creacion` datetime NOT NULL,
  `fecha_actualizacion` datetime NOT NULL,
  `user_ip` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL,
  `user_agent` varchar(255) NOT NULL,
  `id_user` int(3) NOT NULL DEFAULT '0'
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `os_task` */

DROP TABLE IF EXISTS `os_task`;

CREATE TABLE `os_task` (
  `id` int(3) unsigned NOT NULL AUTO_INCREMENT,
  `id_usuario` int(6) NOT NULL,
  `id_usuario_target` int(6) NOT NULL,
  `prioridad` enum('NORMAL','ALTA','URGENTE') NOT NULL DEFAULT 'NORMAL',
  `descripcion` varchar(50) NOT NULL,
  `tarea` varchar(2500) NOT NULL,
  `fecha_limite` datetime NOT NULL,
  `fecha_fin` datetime DEFAULT NULL,
  `comentarios` varchar(2500) DEFAULT NULL,
  `estado` enum('ACT','FIN','XXX') NOT NULL DEFAULT 'ACT',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB AUTO_INCREMENT=2 DEFAULT CHARSET=latin1;

/*Table structure for table `os_themes` */

DROP TABLE IF EXISTS `os_themes`;

CREATE TABLE `os_themes` (
  `id` int(3) NOT NULL,
  `theme` varchar(255) NOT NULL DEFAULT 'default',
  `alias` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `os_wallpapers` */

DROP TABLE IF EXISTS `os_wallpapers`;

CREATE TABLE `os_wallpapers` (
  `id` int(3) NOT NULL,
  `wallpaper` varchar(255) NOT NULL DEFAULT 'default',
  `alias` varchar(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

/*Table structure for table `pais` */

DROP TABLE IF EXISTS `pais`;

CREATE TABLE `pais` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `short_name` varchar(80) CHARACTER SET latin1 NOT NULL,
  `long_name` varchar(80) CHARACTER SET latin1 NOT NULL,
  `iso2` char(2) CHARACTER SET latin1 DEFAULT NULL,
  `iso3` char(3) CHARACTER SET latin1 DEFAULT NULL,
  `numcode` varchar(6) CHARACTER SET latin1 DEFAULT NULL,
  `un_member` varchar(12) CHARACTER SET latin1 DEFAULT NULL,
  `calling_code` varchar(8) CHARACTER SET latin1 DEFAULT NULL,
  `cctld` varchar(5) CHARACTER SET latin1 DEFAULT NULL,
  `estado` enum('ON','OFF') COLLATE latin1_spanish_ci NOT NULL DEFAULT 'OFF',
  PRIMARY KEY (`id`),
  UNIQUE KEY `iso` (`iso2`)
) ENGINE=InnoDB AUTO_INCREMENT=251 DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `pedidos` */

DROP TABLE IF EXISTS `pedidos`;

CREATE TABLE `pedidos` (
  `id` int(11) unsigned NOT NULL COMMENT 'identificador de pedido',
  `id_cliente` int(6) unsigned NOT NULL COMMENT 'identificador de cliente',
  `cliente` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'nombre del cliente',
  `email` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL,
  `telefono` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL,
  `direccion` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'dirección de entrega',
  `poblacion` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'poblacion entrega',
  `provincia` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'provincia entrega',
  `pais` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'pais entrega',
  `cpostal` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'codigo postal entrega',
  `razon` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `nifcif` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `fdireccion` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `fpoblacion` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `fprovincia` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `fpais` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `fcpostal` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `forma_pago` enum('TPV') CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL COMMENT 'Canal de venta (transferencia, tpv, etc)',
  `gastos_imponible` float(7,2) NOT NULL COMMENT 'Gastos',
  `gastos_total` float(7,2) DEFAULT NULL,
  `gastos_tipo_iva` float(5,2) NOT NULL,
  `gastos_total_iva` float(7,2) NOT NULL,
  `pedido_tipo_iva` float(5,2) NOT NULL COMMENT 'descuento si aplica',
  `pedido_total_iva` float(7,2) NOT NULL COMMENT 'iva',
  `pedido_imponible` float(7,2) NOT NULL COMMENT 'total descuento',
  `pedido_subtotal` float(7,2) NOT NULL,
  `pedido_total` float(7,2) NOT NULL COMMENT 'importe del pedido',
  `peso` float(7,3) NOT NULL,
  `observaciones` varchar(5000) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'Observaciones adicionales del pedido',
  `entidad_respuesta` int(11) DEFAULT NULL COMMENT 'Codigo respuesta entidad',
  `entidad_report` varchar(2500) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'Report entidad',
  `entidad_fecha` datetime DEFAULT NULL,
  `fenvio` datetime DEFAULT NULL COMMENT 'fecha en la que entregamos el pedido',
  `ffinalizado` datetime DEFAULT NULL,
  `observaciones_internas` varchar(5000) CHARACTER SET latin1 COLLATE latin1_spanish_ci DEFAULT NULL,
  `falta` datetime NOT NULL COMMENT 'fecha de entrada del pedido',
  `fmodificacion` datetime NOT NULL COMMENT 'fecha de actualizacion del pedido',
  `id_administrador` int(6) NOT NULL DEFAULT '0' COMMENT 'administrador que modifica el este registro',
  `estado` enum('PRESUPUESTO','PEDIDO','RECHAZADO','ENVIADO','FINALIZADO','XXX') CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL,
  UNIQUE KEY `Indice-Pedido` (`id`),
  KEY `Indice-Cliente` (`id_cliente`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `pedidos_detalle` */

DROP TABLE IF EXISTS `pedidos_detalle`;

CREATE TABLE `pedidos_detalle` (
  `id_pedido` int(11) unsigned NOT NULL,
  `id_variedad` int(11) unsigned NOT NULL,
  `cantidad` int(5) NOT NULL,
  `precio` float(7,2) NOT NULL,
  `iva` float(5,2) NOT NULL,
  `subtotal` float(7,2) NOT NULL,
  `total` float(7,2) NOT NULL,
  KEY `Indice-Pedido` (`id_pedido`),
  KEY `Indice-Variedad` (`id_variedad`),
  CONSTRAINT `FK_pedidos_detalle` FOREIGN KEY (`id_pedido`) REFERENCES `pedidos` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT `FK_variedad` FOREIGN KEY (`id_variedad`) REFERENCES `articulos_variedad` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `portes` */

DROP TABLE IF EXISTS `portes`;

CREATE TABLE `portes` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_zona` int(11) unsigned NOT NULL,
  `peso` float(7,3) unsigned NOT NULL,
  `importe` float(7,2) unsigned NOT NULL,
  `fmodificacion` datetime NOT NULL,
  `falta` datetime NOT NULL,
  `id_administrador` int(11) unsigned NOT NULL,
  `estado` enum('ON','OFF','XXX') COLLATE latin1_spanish_ci NOT NULL DEFAULT 'ON',
  PRIMARY KEY (`id`),
  UNIQUE KEY `i_zona` (`id_zona`,`peso`),
  CONSTRAINT `FK_portes` FOREIGN KEY (`id_zona`) REFERENCES `zonas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB AUTO_INCREMENT=49 DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `proveedores` */

DROP TABLE IF EXISTS `proveedores`;

CREATE TABLE `proveedores` (
  `id` int(11) NOT NULL AUTO_INCREMENT COMMENT 'identificador unico del elemento',
  `proveedor` varchar(255) COLLATE latin1_spanish_ci NOT NULL COMMENT 'nombre del proveedor',
  `estado` enum('ON','OFF','XXX') COLLATE latin1_spanish_ci NOT NULL DEFAULT 'ON',
  `falta` datetime NOT NULL,
  `fmodificacion` datetime NOT NULL,
  `id_administrador` int(11) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `provincias` */

DROP TABLE IF EXISTS `provincias`;

CREATE TABLE `provincias` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `id_pais` int(11) unsigned NOT NULL,
  `provincia` varchar(255) COLLATE latin1_spanish_ci NOT NULL,
  `cpostal` varchar(255) COLLATE latin1_spanish_ci NOT NULL,
  PRIMARY KEY (`id`),
  KEY `idx_idPais` (`id_pais`),
  CONSTRAINT `FK_pais_zonas` FOREIGN KEY (`id_pais`) REFERENCES `pais` (`id`) ON DELETE NO ACTION
) ENGINE=InnoDB AUTO_INCREMENT=55 DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;

/*Table structure for table `subfamilias` */

DROP TABLE IF EXISTS `subfamilias`;

CREATE TABLE `subfamilias` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT COMMENT 'identificador unico del elemento',
  `subfamilia` varchar(255) COLLATE latin1_spanish_ci NOT NULL COMMENT 'nombre del elemento',
  `id_familia` int(11) NOT NULL COMMENT 'identificar de la categoria a la que pertenece. Cumple integridad',
  `estado` enum('ON','OFF','XXX') COLLATE latin1_spanish_ci NOT NULL DEFAULT 'OFF' COMMENT 'habilita o deshabilita el elemento',
  `posicion` int(1) NOT NULL COMMENT 'posicion de presentacion del elemnto',
  `descripcion` text COLLATE latin1_spanish_ci NOT NULL COMMENT 'descripcion',
  `foto_1` varchar(255) COLLATE latin1_spanish_ci NOT NULL COMMENT 'foto principal del elemento',
  `foto_2` varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'foto 2 del elemento',
  `foto_3` varchar(255) COLLATE latin1_spanish_ci DEFAULT NULL COMMENT 'foto 3 del elemento',
  `falta` datetime NOT NULL COMMENT 'auditoria',
  `fmodificacion` datetime NOT NULL COMMENT 'auditoria',
  `id_administrador` int(6) NOT NULL COMMENT 'auditoria',
  PRIMARY KEY (`id`),
  UNIQUE KEY `i_subfamilia` (`subfamilia`,`id_familia`)
) ENGINE=InnoDB AUTO_INCREMENT=21 DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC;

/*Table structure for table `tallas` */

DROP TABLE IF EXISTS `tallas`;

CREATE TABLE `tallas` (
  `id` int(6) unsigned NOT NULL AUTO_INCREMENT COMMENT 'identificador unico del elemento',
  `talla` varchar(255) CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL COMMENT 'talla',
  `descripcion` varchar(255) DEFAULT NULL,
  `estado` enum('ON','OFF','XXX') CHARACTER SET latin1 COLLATE latin1_spanish_ci NOT NULL DEFAULT 'ON',
  `falta` datetime NOT NULL,
  `fmodificacion` datetime NOT NULL,
  `id_administrador` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `NewIndex1` (`talla`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=utf8;

/*Table structure for table `zonas` */

DROP TABLE IF EXISTS `zonas`;

CREATE TABLE `zonas` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `zona` varchar(255) COLLATE latin1_spanish_ci NOT NULL,
  `fmodificacion` datetime NOT NULL,
  `falta` datetime NOT NULL,
  `id_administrador` int(11) unsigned NOT NULL,
  `estado` enum('ON','OFF','XXX') COLLATE latin1_spanish_ci NOT NULL DEFAULT 'ON',
  PRIMARY KEY (`id`),
  UNIQUE KEY `i_zona` (`zona`,`estado`)
) ENGINE=InnoDB AUTO_INCREMENT=5 DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;

/*Table structure for table `zonas_detalle` */

DROP TABLE IF EXISTS `zonas_detalle`;

CREATE TABLE `zonas_detalle` (
  `id_zona` int(11) unsigned NOT NULL,
  `id_provincia` int(11) unsigned NOT NULL,
  UNIQUE KEY `i_provincia_detalle` (`id_provincia`),
  KEY `i_zona_detalle` (`id_zona`),
  CONSTRAINT `FK_zonas_detalle` FOREIGN KEY (`id_zona`) REFERENCES `zonas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=latin1 COLLATE=latin1_spanish_ci;

/*!40101 SET SQL_MODE=@OLD_SQL_MODE */;
/*!40014 SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS */;
/*!40014 SET UNIQUE_CHECKS=@OLD_UNIQUE_CHECKS */;
/*!40111 SET SQL_NOTES=@OLD_SQL_NOTES */;
