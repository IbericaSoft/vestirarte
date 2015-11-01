<?
/**
 * Monta la pagina desktop del sistema
 * @author ...
 * @version 1.0 11.2011 Creación
 *
 */
	class Desktop extends Tools {		
		
		public $VERSION = 'Version: 1.0 (01.2011)<br><br><i>Creación</i><br><br><b>Dobleh Software 2012</b>';
		private $pathApp;
		private $err = 'Usuario o contraseña no válidos';
		private $oLogger;
		private $oDataTemplate;
		private $pathApplications;
		
		/**
		 * En el constructor de la clase recibimos el objeto DobleOS que nos permite disponer de acceso a la gestion el sistema
		 * @param $system Es el objeto Session que nos pasan por 'referencia'
		 */
		public function __construct( DobleOS $system ){
			parent::__construct($system);
			$this->oLogger = Logger::getRootLogger();
			$this->pathApp = OS_ROOT.'/applications/desktop';
			$this->pathApplications = OS_WEB_PATH.'/applications/';
		}
		
		public function __destruct(){}
		
		/** 
		 * Pinta el escritorio
		 */
		public function show() {			
			$this->oLogger->debug("cargando escritorio");			
			$datos = array();
			
			/** menu de aplicaciones para el usuario */
			$datos[menus] = Utils_OS::getApplicationsUser( $this->oSystem->getConnection() , $this->oSystem->getUser()->getRol() );
			$datos[menu] = array();
			foreach ( $datos[menus] as $mnu ){
				array_push( $datos[menu], array(
					"position"=>$mnu['position'],
					"icon"=>$this->pathApplications.$mnu['icon'],
					"class"=>$mnu['application'],
					"do"=>$mnu['action'],				
					"title"=>utf8_encode($mnu['win_title']),
					"width"=>$mnu[win_width],"height"=>$mnu[win_height],
					"maximize"=>($mnu[win_maximize]==1),"minimize"=>($mnu[win_minimize]==1),
					"resizable"=>($mnu[win_resize]==1),"closable"=>($mnu[win_close]==1),
					"modal"=>($mnu[win_modal]==1)
					));				
			}
			$datos[menu] = json_encode($datos[menu]);
			
			
			
			/** iconos de aplicaciones para el usuario */
			$datos[icon] = Utils_OS::getIconsUser( $this->oSystem->getConnection() , $this->oSystem->getUser()->getId() );
			$datos[icons] = array();
			foreach ( $datos[icon] as $icon ){
				array_push( $datos[icons], array(
					//"user_id"=>$icon[user_id],
					"icon_id"=>$icon[icon_id],
					"class"=>$icon['_class'],
					"do"=>$icon['_do'],
					"width"=>$icon[_width],"height"=>$icon[_height],"top"=>$icon[_top],"left"=>$icon[_left],
					"maximize"=>$icon[_maximize],"minimize"=>$icon[_minimize],"resizable"=>$icon[_resizable],"closable"=>$icon[_closable],"status"=>$icon[_status],
					"parameters"=>$icon[_parameters],
					"title"=>utf8_encode($icon['_title']),"icon"=>$icon[_icon],"itop"=>$icon[_itop],"ileft"=>$icon[_ileft],"ititle"=>utf8_encode($icon['_ititle'])
					));				
			}
			$datos[icons] = json_encode($datos[icons]);
			
			
			
			/** procesos */
			$datos[proc] = Utils_OS::getProcessUser( $this->oSystem->getConnection() , $this->oSystem->getUser()->getId() );
			$datos[process] = array();
			foreach ( $datos[proc] as $proc ){
				array_push( $datos[process], array(
					"process_id"=>$proc[process_id],
					"class"=>$proc['_class'],
					"do"=>$proc['_do'],				
					"title"=>utf8_encode($proc['_title']),
					"width"=>$proc[_width],"height"=>$proc[_height],"top"=>$proc[_top],"left"=>$proc[_left],
					"maximize"=>$proc[_maximize],"minimize"=>$proc[_minimize],"resizable"=>$proc[_resizable],"closable"=>$proc[_closable],"status"=>$proc[_status],
					"parameters"=>$proc[_parameters],
					));				
			}
			$datos[process] = json_encode($datos[process]);
			
			
			//info user
			$datos[usuario] = $this->oSystem->getUser()->getName();
			
			$this->oSystem->getDataTemplate()->addData('desktop', $datos);
			$this->oSystem->getDataTemplate()->setTemplate( $this->pathApp.'/desktop.html' );
			return true;
		}
		
		


	}
?>