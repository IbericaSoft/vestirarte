<?
	/**
	 * 	Clase Singleton que se encarga de persistir la instancia de la clase RAIZ que mantiene los datos de la tienda.
	 *  Esta clase no se deberia tocar ya que todas las tiendas funcionan en su base de la misma forma.
	 * 	@author Antonio G�mez
	 * 	@version 1.0 (12/2011) Creaci�n
	 *  @version 1.1 (08/2014) Evitamos que el destructor guarde en sesion todas las clases que extienden de esta clase, ya que nos vale solo con
	 *  persistir la instancia de Tienda.class.php ya que es esta la que almacena al resto
	 *  @version 1.2 (06.2015) metodos utiles utf_server, utfd_server
	 */

	class Tienda_Instanciador {		
		
		public static $log;
		private static $caching = true;
		private static $instance;
		private static $nameClass;

		/**
		 * Recuperar instancia de esta clase. Solo mantenemos una instancia por sesion
		 */
		public static function getInstance($className){
			self::$log = Logger::getRootLogger();
			self::$nameClass = $className;
			if ( isset($_SESSION[self::$nameClass]) ){
				self::$instance = unserialize($_SESSION[self::$nameClass]);
				self::notifyRecover();
			}else{
	        	self::$instance = new self::$nameClass;
			}			
		    return self::$instance;
		}
		
		/**
		 *	Notificamos el objeto que instanciamos
		 */
		public function __construct() {
			self::$log->debug( "Nueva instancia de ".self::$nameClass."!!!" );
		}
		
		/**
		 * Persistimos la clase conservando los ultimos cambios. Tenemos la posibilidad de resetear los objetos
		 * cacheados si $caching esta false. Nos puede interesar para hacer un inicio de sesion de objetos nuevo
		 */
		public function __destruct(){
			if ( self::$caching ){
				//self::$log->debug( "Persistiendo la instancia de ".get_class($this) );
				$_SESSION[ self::$nameClass ] = serialize( self::$instance );
			}
			self::$caching = false;//solo una vez, salvamos Tienda.class.php. Si no hago esto cada clase que extienda de Tienda_Instanciador invoca este metodo
		}
		
		/**
		 * Notificamos el objecto instanciado persistido/cacheado que recuperamos de la sesion
		 */
		public function notifyRecover(){
			//self::$log->debug( "Recuperamos instancia de ".self::$nameClass );
		}
		
		/**
		 * Podemos resetear la cache de objetos persistidos en la sesion. Para ello indicamos en un flag que no queremos persistir
		 * el objeto que estamos tratando y ademas eliminamos de la sesi�n el objeto actual.
		 */
		public function resetCache(){
			self::$log->debug( "Reseteando cache de ".self::$nameClass );
			self::$caching = false;
			unset( $_SESSION[ self::$nameClass ] );
		}
		
		public function utf_server($cadena){
			if ( OS_MODE=='development'||OS_MODE=="test")
				return utf8_encode($cadena);
			return $cadena;
		}
		
		public function utfd_server($cadena){
			if ( OS_MODE=='development'||OS_MODE=="test")
				return utf8_decode($cadena);
			return $cadena;
		}
	}
?>
