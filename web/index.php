<?
	/**
	 * Este script es el que se ejecuta en cada peticion de usuario en este sistema de tiendas. Se consigue gracias
	 * al fichero .htaccess que por medio de patrones URL nos redirige aqui siempre. Desde aqui utilizamos nuestro framework
	 * de tiendas Navigator para mover todo esto.
	 */

	/**
	 * Establecemos el nivel de errores
	 */
	error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED & ~E_STRICT);
	
	/** Iniciar sesión */
	session_start();
	
	/**
	 * Cargar el fichero de deteccion de entorno que definira la configuración a utilizar
	 */
	require_once ( $_SERVER['DOCUMENT_ROOT'] . '/detect_environment.inc.php' );
	
	/**
	 * Carga Framework
	 */
	require_once ( OS_ROOT . '/kernel/navigator/Navigator.class.php' );
?>