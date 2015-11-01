<?
/**
 * @author Antonio Gámez
 * @abstract Gestion de la configuracion de temas y fondos desktop del sistema
 * @version 1.0 12.2011 creacion
 * @version 1.1 04.2012 Preparamos el módulo para la compartición de CSS/JS/IMAGES
 */

	class Panel_Control extends Tools {
		
		const VERSION='1.1';
		public $PATHCLASS = '/applications/panel_control';
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
		 * Recuperar de la sesion esta clase implica invocar este metodo, no se vuelve a construir
		 * @see root/system/Applications::setInstance()
		 */
		public function setInstance( DobleOS $system ){			
			parent::setInstance($system);
			$this->oLogger = Logger::getRootLogger();
			$this->oLogger->debug( "setIntance ".get_class($this) );
			$this->isPesistance = false;
			$this->persistenceName = $this->oSystem->getOrderActionClass()->getClassSession();
		}
		
		/**
		 * Si la clase requiere persistencia, es aqui donde la guardamos en sesion con el nombre especifico
		 * para persistir
		 * @see Applications::__destruct()
		 */
		public function __destruct(){
			if ( $this->isPesistance ){
				$this->oLogger->debug ("persistiendo ".$this->oSystem->getOrderActionClass()->getClassSession());
				$_SESSION[$this->oSystem->getOrderActionClass()->getClassSession()] = serialize( $this );
			}	
		}
		
		/**
		 * Al lanzar la clase invocamos este metodo que no devuelve resultados, lo dejamos asi
		 * para obligar al usuario a usar filtros
		 * @see root/system/Applications::start()
		 */
		public function start(){
			$this->oLogger->debug( "Inicio clase" );
			$this->oSystem->getDataTemplate()->addData('wallpapers', Utils_OS::getWallPapers( $this->oSystem->getConnection() ) );
			$this->oSystem->getDataTemplate()->addData('themes', Utils_OS::getThemes( $this->oSystem->getConnection() ) );
			$this->oSystem->getDataTemplate()->setTemplate($this->pathApp.'/index.html');
		}
		
		public function applyWallPaper(){
			$this->oLogger->debug("Actualizando wallpaper");
			$user 	= $this->oSystem->getUser()->getId();
			$sql = "UPDATE os_preferences_user SET value=$_REQUEST[id],fecha=now() WHERE id_user=$user AND property='wallpaper';";
			$this->computeSQL($sql, false);
			$this->oSystem->getPreferencesUser()->setWallPaper($_REQUEST[alias]);
			$datos 	= array("error"=>'NO','alias'=>OS_WEB_PATH."/wallpapers/$_REQUEST[alias]" );
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}
		
		public function applyTheme(){
			$this->oLogger->debug("Actualizando theme");
			$user 	= $this->oSystem->getUser()->getId();
			$sql = "UPDATE os_preferences_user SET value=$_REQUEST[id],fecha=now() WHERE id_user=$user AND property='theme';";
			$this->computeSQL($sql, false);
			$this->oSystem->getPreferencesUser()->setTheme($_REQUEST[alias]);
			$datos 	= array("error"=>'NO','alias'=>$_REQUEST[alias] );
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}
		
		
		
		
	}
?>
