<?
/**
 * @author ANANsoft
 * @abstract Gestión simple para mostra los mensajes de error/aviso del sistema
 * @version 1.0 11.2011 creacion
 * @version 1.1 03.2012 mejora en los estilos css para alinear y ajustar el boton al pie con efecto rollover
 */
	class Messages extends Tools {		
		
		const VERSION = '1.1';
		public $PATHCLASS = '/applications/messages';
		public $pagina;
		public $pathApp;
		public $isPesistance;
		public $filtroSQL;
		public $persistenceName;
		private $oLogger;
		
		/**
		 * En el constructor de la clase recibimos el objeto DobleOS que nos permite disponer de acceso a la gestion el sistema
		 * @param $system Es el objeto Session que nos pasan por 'referencia'
		 */
		public function __construct( DobleOS $system ){
			parent::__construct($system);
			$this->oLogger = Logger::getRootLogger();
			$this->pathApp = OS_ROOT.$this->PATHCLASS;
		}
		
		/**
		 * Si la clase requiere persistencia, es aqui donde la guardamos en sesion con el nombre especifico
		 * para persistir
		 * @see Applications::__destruct()
		 */
		public function __destruct(){			
			
		}
		
		/** 
		 * Recibe los datos de un mensaje para pintarlos
		 */
		public function showMessage( $message ){
			
			$this->oSystem->getDataTemplate()->addData('messages', $message);
			$this->oSystem->getDataTemplate()->setTemplate( $this->pathApp.'/messages.html' );
			return true;
		}

	}
?>