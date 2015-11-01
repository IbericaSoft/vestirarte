<?
/**
 * Guarda la posición en la que nos encontramos dentro del backend
 * @author Antonio Gámez Moro
 *
 */
		
	class OrderActionClass {
		
		private $class;
		private $action;
		private $classSession;
		private $channel;
		private $oLogger;
		
		/** 
		 * Contruye la clase con la clase, su alias, la acción y el canl que se solicita en la peticion del usuario 
		 */
		public function __construct($action,$class,$sessionclass=null,$channel='html'){
			$this->oLogger = Logger::getRootLogger();
			$this->action = $action;
			$this->class = $class;
			$this->classSession = $sessionclass;
			$this->channel = $channel;
			$this->oLogger->debug("La navegación solicitada es <class:$this->class> <action:$this->action> <channel:$this->channel> <session:$this->classSession>");
		}
		
		public function setClass($class){
			$this->class = $class;
		}
		
		public function getClass(){
			return $this->class;
		}
		
		public function setAction($action){
			$this->action = $action;
		}
		
		public function getAction(){
			return $this->action;
		}
		
		public function setClassSession($classsession){
			$this->classSession = $classsession;
		}
		
		public function getClassSession(){
			return $this->classSession;
		}
		
		public function setChannel($channel){
			$this->channel = $channel;
		}
		
		public function getChannel(){
			return $this->channel;
		}
		
	}
?>