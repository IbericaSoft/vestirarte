<?
/**
 * Pantallas de inicio del sistema
 * @version 1.0 11.2011 creacion
 * @version 1.1 03.2012	Añadimos un botón para ir al login en la pantalla de wellcome. Hacemos esto para los navegadores de los tablets que noç
 * 					tienen teclas de función
 *
 */
	class Wellcome extends Tools {		
		
		public $VERSION = 'Version: 1.1 (03.2012)<br><br><i>Añadimos un botón para ir al login en la pantalla de wellcome</i><br><br><b>Dobleh Software 2012</b>';
		public $PATHCLASS = '/applications/wellcome';
		private $pathApp;
		private $oLogger;
		private $oDataTemplate;
		
		/**
		 * En el constructor de la clase recibimos el objeto DobleOS que nos permite disponer de acceso a la gestion el sistema
		 * @param $system Es el objeto Session que nos pasan por 'referencia'
		 */
		public function __construct( DobleOS $system ){
			parent::__construct($system);
			$this->oLogger = Logger::getRootLogger();
			$this->pathApp = OS_ROOT.$this->PATHCLASS;
		}
		
		public function __destruct(){
			
		}
		
		public function start(){
			
		}
		
		public function wellcome(){
			$datos = array();
			$datos[theme]   = $this->oSystem->getConfigSystem()->getKeyData('default_theme');
			$datos[title]   = $this->oSystem->getConfigSystem()->getKeyData('app_title');
			$datos[version] = $this->oSystem->getConfigSystem()->getKeyData('app_version');  
			
			$this->oDataTemplate = DataTemplate::getRootDataTemplate();
			$this->oDataTemplate->addData('kernel', $datos);
			$this->oDataTemplate->setTemplate($this->pathApp.'/wellcome.html');
			return true;
		}
		
		public function viewWellcomeMsg(){
			$this->oDataTemplate = DataTemplate::getRootDataTemplate();
			$datos[wellcome_msg]   = $this->oSystem->getConfigSystem()->getKeyData('wellcome_msg');
			$this->oDataTemplate->addData('kernel', $datos);
			$this->oDataTemplate->setTemplate($this->pathApp.'/wellcome_msg.html');
			return true;
		}
		
	}
?>