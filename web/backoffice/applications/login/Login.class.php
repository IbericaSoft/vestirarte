<?
/**
 * Clase para gestionar el Login del sistema
 * @author ...
 * @version 1.0 10.2011 creación
 * @version 1.1 04.2012 Preparamos el módulo para la compartición de CSS/JS/IMAGES
 * @version 1.2 04.2012 Globalizacion de estilos CSS
 * @version 1.3 02.2014 Se unifica la tabla perfiles y administradores y cambiamos la query
 *
 */
	class Login extends Tools {		
		
		public $VERSION = 'Version: 1.3 (02.2014)<br><br><i>Se unifica la tabla perfiles</i><br><br><b>Dobleh Software 2014</b>';
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
			$this->pathApp = OS_ROOT.'/applications/login';
		}
		
		public function __destruct(){}
		
		public function start(){}
		
		/** 
		 * Pinta el formulario de login si no estamos logados o envia los datos de peticion de carga
		 * en la nueva pagina del escritorio 
		 */
		public function checkLogin() {
			$callback = array();
			if ( $this->oSystem->getUser()->isLogged() ) {
				array_push($callback, utf8_encode("top.document.location='".PREFIX_URL."';"));
				$datos 	= array("error"=>'NO',"callBack"=>$callback);
			} else {
				$datos 	= array("error"=>'SI',"errDescription"=>utf8_encode("Usuario o contraseña no válidos"));
			}
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}
		
		/** 
		 * Intentar login 
		 */
		public function tryLogin() {			
			$sql= "SELECT * FROM os_administradores admin WHERE 
				usuario='$_REQUEST[usuario]' AND password='$_REQUEST[password]' AND estado='ACT';";
		  	
			$res = $this->oSystem->getConnection()->query($sql);
		  	if ( $this->oSystem->getConnection()->totalRegistros()==0 ){
		  		$this->oLogger->debug("no se puede hacer login con estos credenciales");
			} else {		
				$datos = $this->oSystem->getConnection()->getColumnas($res);
				$this->startSession($datos);
			}
			$this->checkLogin();
		}
		
		/**
		 * 
		 * carga los datos del usuario que acaba de hacer login en el objeto correspondiente
		 * @param $datos array con los valores del usuario
		 */
		private function startSession($datos){
			$this->oLogger->debug("inicio session con los datos del usuario $datos[nombre]");
			$this->oSystem->getUser()->setId( $datos['id'] );
			$this->oSystem->getUser()->setName( $datos['nombre'] );
			$this->oSystem->getUser()->setRol( $datos['id_perfil'] );
			
			$datos = Utils_OS::getPreferencesUser($this->oSystem->getConnection(), $this->oSystem->getUser()->getId() );
			$this->oSystem->getPreferencesUser()->setTheme( $datos[theme] );
			$this->oSystem->getPreferencesUser()->setWallPaper( $datos[wallpaper] );
			
		}
		
		public function wellcome(){
			$datos = array();
			$datos[theme]   = $this->oSystem->getConfigSystem()->getKeyData('default_theme');
			$datos[title]   = $this->oSystem->getConfigSystem()->getKeyData('app_title');
			$datos[version] = $this->oSystem->getConfigSystem()->getKeyData('app_version');
			
			$this->oDataTemplate = DataTemplate::getRootDataTemplate();
			$this->oLogger->debug( 'Metiendo datos en la plantilla' );
			$this->oDataTemplate->addData('login', $datos);
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
		
		public function viewLogin(){
			$this->oDataTemplate = DataTemplate::getRootDataTemplate();
			$this->oDataTemplate->setTemplate($this->pathApp.'/login.html');
			return true;
		}

	}
?>