<?
	/**
	* 	Clase para cargar un fichero de configuración y mapearlo en un array. Desde la versión 4 esta clase ya no gestiona ficheros por
	*   entorno ni atributos varios del fichero de configuración, sin embargo, se persiste automáticamente quedando cacheados los datos
	*	de este fichero lo que redunda en un mejor performance del sistema 
	*	
	*	@author Dobleh software. Antonio Gámez 
	*	@copyright Dobleh software 2011	- agamez@dobleh.com
	*	@version 4.0 (12/2011) Cambio radical. Renace como clase estática para cachear los datos del fichero de configuración que carge.
	*		Ya no se gestionan ficheros por entorno ya que nos llega el path del fichero a cargar. La clase se persite con su propio nombre de
	*		clase usando el destructor para ello.	
	*	@version 3.0 (01/08/2008) Se permite mantenimiento del fichero de entorno, con edición de sus datos
	*	@version 2.0 (02/01/2008) El número de parámetros a configurar se hace variable y una funcion devuelve el parametro solicitado
	*	@version 1.0 (24/12/2007) creación
	*/	
	class Environment {
		private $log;
		private static $file;
		private $params;
		private $_params;
		private $caching = true;
		private static $instance;
		
		
		/**
		 * Recuperar instancia de esta clase. Solo mantenemos una instancia por sesion
		 */
		public static function getInstance($file){
			self::$file = $file;
			if (  !self::$instance instanceof self ){
	            if( isset($_SESSION[get_class(self::$instance)]) ){
	            	self::$instance = unserialize($_SESSION[get_class(self::$instance)]);
	            	//self::$instance->recover();
	            	self::fix();
	            }else{
	            	self::$instance = new self(self::$file);
	            }
			}
		    return self::$instance;
		}
		
		/**
		 * Este fix es necesario debido a que esta clase se utiliza tanto en el back como en el front-office y se crea una situacion 
		 * extraña (no lanza el destructor). Al ser usada desde el back, no persiste los datos pero si que queda en sesion. Al ir a recuperar esta instancia
		 * inconsistente al ser usada desde el Front y deserializada no obtenemos el objeto en cuention
		 */
		private function fix(){
			if (self::$instance instanceof self)
	        	self::$instance->recover();
			else
	        	self::$instance = new self(self::$file);
		}
		
		/**
		 * Solo para notificar que estamos recuperando la instancia
		 */
		private function recover(){
			$this->log = Logger::getRootLogger();
			$this->log->debug( "Recuperados de cache los datos de ".get_class(self::$instance) );
		}
		
		/**
		 * Cargamos el fichero indicado y volcamos sus valores en el array de parametros.
		 * Solo lo haremos una vez, la primera vez que nos instancien. Despues todo será recuperado de cache
		 * @param $conf ruta absoluta al fichero de configuración
		 */
		private function __construct($file) {
			$this->log = Logger::getRootLogger();
			$this->log->debug( "Cargando fichero de configuración. Solo esta vez!!!" );
			try {
				require_once ( $file );
				$this->_params = $ENTORNO;
				if (!$this->_params)
					throws;
				$this->loadConfig();
			} catch (Exception $e){
				$this->caching = false;
				$this->log->error("No he podido cargar el fichero de configuración $file");
				throw new Exception("No he podido cargar el fichero de configuración $file");
			}
		}
		
		public function __destruct(){
			if ( $this->caching ){
				$this->log->debug( "Persistimos el objecto ".get_class(self::$instance) );
				$_SESSION[ get_class($this) ] = serialize( self::$instance );
			}
		}
		
		/** 
		 * Lee los paremetros declarados y los mapea en el array params 
		 */
		private function loadConfig(){
			//$this->log->debug( "Mapeando parametros del fichero configuracion al array de parametros" );	
			$this->params = array();
			foreach ( array_keys($this->_params) as $key)
				$this->params[$key]=$this->_params[$key];
		}
		
				
		/** 
		 * Devuelve el parametro requerido o null si no exite 
		 * */
		public function getParams($param){
			//$this->log->debug( "Solicito parametro $param=".$this->params[$param] );
			if ( array_key_exists($param, $this->params) )
    			return $this->params[$param];
			$this->log->error( "No tengo este parametro $param");
    		return null;
		}
				
		/** 
		 * Añade un parametro al array de parametros 
		 */
		public function addParam($key,$value){
			//$this->log->debug("Añado el parametro $key con valor $value");
			$this->params[$key]=$value;
		}
		
		/**
		 * Resetea los datos cacheados por esta clase, para ello, indica en un flag que no se 
		 * guarse en sesion esta clase y además elimina de la sesion actual los datos de esta clase.
		 */
		public function resetCache(){
			//$this->log->debug( "Reseteando cache de ".get_class($this) );
			$this->caching = false;
			unset( $_SESSION[ get_class($this) ] );
		}
	}
?>