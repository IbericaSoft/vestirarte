<?
/**
 * Guarda los datos y plantilla a usar en OS
 * @author Antonio Gámez Moro
 *
 */
		
	class DataTemplate {
		
		private static $oDataTemplate;
		private static $initialized = false;
		
		private $template;
		private $data = array();
		private $oLogger;
		
		
		/** Contruye el objeto que tiene los datos y plantilla a mostrar en el UI */
		private function __construct(){
			$this->oLogger = Logger::getRootLogger();
			//$this->oLogger->debug( 'Creando objeto de Plantillas y Datos' );
		}
		
		public function getTemplate(){
			return $this->template;
		}
		
		public function setTemplate($template){
			$this->template = $template;
		}
		
		public function getData(){
			return $this->data;
		}
		
		public function getKeyData($key){
			return $this->data[$key];
		}
			
		public function addData($key,$data){
			$this->data[$key]=$data;
			
		}
		
		public static function getRootDataTemplate() {
			if(!self::$initialized) {
				self::initialize();
			}
			return self::$oDataTemplate;
		}
		
		private function initialize(){
			self::$initialized = true;
			self::$oDataTemplate = new self;
		}
	}
?>