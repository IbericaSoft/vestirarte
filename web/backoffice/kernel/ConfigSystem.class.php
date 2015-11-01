<?
	/**
		Autor Antonio Gámez 

	*/	
	class ConfigSystem {
		
	
		private $data = array();
		private $oLogger;
		
		/** Contruye el objeto que tiene los datos y plantilla a mostrar en el UI */
		public function __construct($conf){
			$this->oLogger = Logger::getRootLogger();
			foreach ( array_keys($conf) as $data  ){
				$this->addData( $data, $conf[$data]);
				//$this->oLogger->debug("Añadiendo $data=$conf[$data]");
			}
		}
			
		public function getData(){
			return $this->data;
		}
		
		public function getKeyData($key){
			return $this->data[$key];
		}
			
		public function addData($key,$data){
			//$this->oLogger->debug("Recuperar $this->data[$key]=$data");
			$this->data[$key]=$data;
		}
	}
?>