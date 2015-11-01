<?
/**
 * Gestion sistema dobleOS
 * @author Antonio Gámez Moro
 * @version 0.1 2-2011 creación. bocetos iniciales
 * @version 1.0 5-2011 Cerramos version tras cambiar radicalmente la forma de gestionar peticiones
 * @version 1.1 10-2011 Simplificamos el funcionamiento
 * @version 1.2 12-2011 Añadimos mas variables al array de datos kernel que se calcula en includekernelsData()
 * @version 1.3 01-2012 
 * 		Soporte para cambiar temas y fondos de escritorio. Es total. 
 * 		Fix gestion de error. Clase Messages. Nuevo diseño para mostrar errores 
 * 		Soporte para envio de emails
 * @version 1.3.1 01-2012 Se eliminan algunos atributos como los viejos $_ACTION, $_CHANNEL, etc. que ya no se utilizan.
 * 		Tampoco se utilizan $this->_OCLASS->filtroSQL o $this->_OCLASS->encadenarOnload del método includekernelsData()
 *		Se deshabilitan un grupo trazas y se coloca como primera comprobación la activación del log en init()
 * @version 1.3.2 03.2012 Fix. Existiar un error al utilizar el URL de entrada al sistema cuando ya estabamos logados previamente
 * 		Se elimina app_version de la bbdd se toma del atributo constante VERSION de esta clase. Añadimos un parametro nuevo en
 * 		os_config (default_wallpaper) para poder personalizar el fondo inicial del sistema.
 * @version 1.3.3 05.2012 Activamos el log si el modo debug es true y no estamos en entorno development
 * @version 1.3.4 09.2012 FIX. Expira la sesion con la ventana del navegador abierta y lanzaba error en pantalla
 * @version 1.3.5 02.2014 Evolución para uso de Jquery en los módulos.
 * @version 1.3.6 03.2014 Permitimos mediante un parametro ejecutar una rutina del sistema en una llamada REST sin login
 * @version 1.3.7 11.2014 Añadimos los tipo MIME log y txt para enviar cabeceras de descarga
 * @version	1.3.8 03.2015 Procestemplace añade varios datos, entre ellos la clase modulo actual, para que desde los html se utilize de forma simple con la variable $module 
 */
	
	/** 
	 * Cargamos el fichero de entorno, el cual nos dira en que entorno estamos, de donde cargar el fichero
	 * de configuracion de acceso a datos o que pathes reconoce el sistema 
	 */
	require_once ( $_SERVER['DOCUMENT_ROOT'] . '/detect_environment.inc.php' );

	/** cargamos clases */
	require_once ( OS_ROOT . '/kernel/environment/Environment.class.php' );
	require_once ( OS_ROOT . '/kernel/log4php/Logger.php' );
	require_once ( OS_ROOT . '/kernel/conexion/Conexion.class.php' );
	require_once ( OS_ROOT . '/kernel/OrderActionClass.class.php' );
	require_once ( OS_ROOT . '/kernel/DataTemplate.class.php' );
	require_once ( OS_ROOT . '/kernel/IApplications.class.php' );
	require_once ( OS_ROOT . '/kernel/Applications.class.php' );
	require_once ( OS_ROOT . '/kernel/Tools.class.php' );
	require_once ( OS_ROOT . '/kernel/Utils.class.php' );
	require_once ( OS_ROOT . '/kernel/User.class.php' );
	require_once ( OS_ROOT . '/kernel/PreferencesUser.class.php' );
	require_once ( OS_ROOT . '/kernel/ConfigSystem.class.php' );
	require_once ( OS_ROOT . '/kernel/DobleOSException.class.php' );
	require_once ( OS_ROOT . '/applications/messages/Messages.class.php' );
	require_once ( OS_ROOT . '/kernel/mailer/Email.class.php' );
	require_once ( OS_ROOT . '/kernel/mailer/class.phpmailer.php' );
		
	class DobleOS {
		
		/** objectos de la sesion */
		const VERSION = '1.3.7';
		const NAME_SESSION = 'DobleOS';
		private $idSession;
		private $dateSession;	
		/** Referencia a la clase actual en ejecucion */	
		private  $_OCLASS;
		
		/** objetos */
		private static $instancia;
		private $oUser;
		private $oPreferencesUser;
		private $oOrderActionClass;
		private $oDataTemplate;
		private $oEnvironment;
		private $oConnection;
		private $oLogger;
		private $oConfigSystem;
		private $oEmail;		
		
		/**
		 * DoblehOS es un Singleton que persiste el estado de todo.
		 *  
		 * Al pedir una instancia comprobamos que este inicializado el atributo $instancia y sea del tipo Session,
		 * después, miramos si en la sesión de trabajo existe 'Session'. Si existe, dentro tenemos el
		 * Singleton que deserializamos y lo asignamos a la variable estática de esta clase. Si no exite,
		 * creamos una instancia nueva de esta clase que almacenamos en esta atributo estático.
		 */
		public static function getInstance(){  
			session_start();
			/** configuramos el logger aqui para poder tirar trazas desde el principio */
			Logger::configure( OS_ROOT . '/kernel/configurations/log4php.properties' );
			if (  !self::$instancia instanceof self ){
	            if( isset($_SESSION[self::NAME_SESSION]) ){
	            	self::$instancia = unserialize($_SESSION[self::NAME_SESSION]);
	            }else{
	            	self::$instancia = new self;
	            }
			}
			
			self::$instancia->init();
		    return self::$instancia;
		}
   		
		/**
		 * Solo se ejecutara la primera vez con la instancia nueva, suficiente para inicializar los datos de conexión a BD,
		 */
		private function __construct(){
			$this->oLogger = Logger::getRootLogger();
			$this->oLogger->debug( "<------------------------------------------------------------------------>" );
			//$this->oLogger->info( 'DobleOS creando instancia y cargado configuracion de entorno' );
			
			/** cargamos configuracion del sistema */
			$this->oEnvironment = Environment::getInstance( OS_ROOT . '/kernel/configurations/' . ENVIRONMENT_FILE );
			
			/** conexion a datos */
			$this->oConnection = new Conexion( $this->oEnvironment->getParams('host'),
				$this->oEnvironment->getParams('usuario'),$this->oEnvironment->getParams('password'),
					$this->oEnvironment->getParams('bbdd'),$this->oEnvironment->getParams('port'));	
			
			/** conectamos a bbdd */
			$this->oConnection->getConnection();
			if ( !$this->oConnection->isConnected() )
				die ("Imposible continuar, hay un error en la conexion al servidor de base de datos");
			//$this->oLogger->info( "Conectando al servidor de datos");
			$this->idSession = session_id();
			$this->dateSession = date("Y-m-d H:i:s");
			
			/** Inicializamos las clase que tienen los datos del usuario y sus preferencias */
			$this->oUser = new User();
			$this->oPreferencesUser = new PreferencesUser();
			
			/** cargamos la configuracion del sistema, solo esta vez. tabla os_config */
			$this->oConfigSystem = new ConfigSystem( Utils_OS::getConfigSystem( $this->oConnection ) );
			
			/** Gestor de envio emails */
			$this->oEmail = new Email( $this->oConfigSystem->getKeyData('mail_strategy'),$this->oConfigSystem->getKeyData('mail_host'), $this->oConfigSystem->getKeyData('mail_user'), $this->oConfigSystem->getKeyData('mail_password'), $this->oConfigSystem->getKeyData('mail_account'), $this->oConfigSystem->getKeyData('mail_alias'));
			
			/** registramos conexion de la sesion */
			Utils_OS::registrySession($this->oConnection, array('session'=>$this->idSession,'fecha_creacion'=>$this->dateSession,'fecha_actualizacion'=>date("Y-m-d H:i:s"),'user_ip'=>$_SERVER['REMOTE_ADDR'],'user_agent'=>$_SERVER['HTTP_USER_AGENT'],'id_user'=>0));
		}
		
		/**
		 * Antes de destruir esta instacia guardamos una copia de esta clase que tenemos almacenada
		 * en el atributo estático $instancia bajo el nombre de la propia clase
		 */
		public function __destruct(){
			//$this->oLogger->info( 'Destruimos el objecto '.get_class($this).' y salvamos la sesion en ' . self::NAME_SESSION );
//			$this->oConnection->closeConnection();			
			$_SESSION[ self::NAME_SESSION ] = serialize( self::$instancia );
			//foreach ( array_keys($_SESSION) as $objeto )
			//	$this->oLogger->debug("En session: $objeto");
			
		}
		
		/**
		 * Este metodo se ejecuta cada vez que recuperamos la instancia de la session
		 * carga las clases basicas y prepara ciertos objetos como la conexion, el logger, etc.
		 */
		private function init(){
			$this->oLogger = Logger::getRootLogger();
			$this->oLogger->debug( "<------------------------------------------------------------------------>" );			
			/** desactivamos log si se solicita en la configuracion */
			//if ( OS_MODE!='development' && $this->getConfigSystem()->getKeyData('debug')=='false')
			//	$this->oLogger->shutdown();
						
			/** conectamos a bbdd */
			$this->oConnection->getConnection();
			if ( !$this->oConnection->isConnected() )
				die ("Imposible continuar, hay un error en la conexion al servidor de base de datos");
			//$this->oLogger->info( "Conectando al servidor de datos");
						
			
			/** refrescamos datos de la conexion de la sesion */
			Utils_OS::registrySession($this->oConnection, array('session'=>$this->idSession,'fecha_actualizacion'=>date("Y-m-d H:i:s"),'id_user'=>((int)$this->oUser->getId()) ));

			/** Recargamos las preferencias/configuracion del sistema si no estamos en modo producción*/
			//if ( OS_MODE!='production')
			//	$this->oConfigSystem = new ConfigSystem( Utils_OS::getConfigSystem( $this->oConnection ) );
			
			/** procesamos la request para determinar que hay que hacer */
			$this->getUserRequest();
		}
		
		/** Recupera los datos de la peticion del usuario */
		private function getUserRequest(){
			/** que operacion nos han pedido (la primera letra en minuscula) */
			$action  = ($_REQUEST["do"]);
			if ( $action==null )
				if ( $this->oUser->isLogged() )
					$action = 'wellcome';
				else
					$action = $this->oConfigSystem->getKeyData('default_action');
			
			/** con la operacion sabemos que clase tenemos que cargar, eso si, por nomenclatura convertimos la primera letra en mayúsculas 
			 * y los caracteres precedidos por guion bajo */
			$nameClassTokens = explode('_', strtolower($_REQUEST["class"]));
			$nameClass;
			foreach ( $nameClassTokens as $token ){
				if (!$token) continue;
				if ($nameClass)
					$nameClass.='_';
				$nameClass.=ucwords($token);
			}
			$class =  $nameClass;
			if ( $class==null )
				if ( $this->oUser->isLogged() )
					$class = 'Desktop';
				else
					$class = $this->oConfigSystem->getKeyData('default_class');
			
			$class_session =  $_REQUEST["sessionclass"];
			
			/** canal de peticion de los datos de entrega al cliente (siempre en minusculas) */
			$channel = strtolower($_REQUEST["channel"]);
			if ( $channel==null )
				$channel = $this->oConfigSystem->getKeyData('default_channel');
			
			/** contruimos objeto position, ahora que tenemos los datos de la request */
			$this->oOrderActionClass = new OrderActionClass($action,$class,$class_session,$channel);
			
			/** accesos para request no interactivo*/
			if ( $_REQUEST[ticket] ){
				if ( true ){
					$this->oLogger->debug("Permitido acceso sin login");
					$this->getUser()->setId( $_REQUEST[who] );
					$this->getUser()->setRol('ROOT');
					$this->getOrderActionClass()->setChannel("json");
				} else {
					$this->oLogger->debug("Bloqueado acceso sin login");
				} 
			}
			
			/** inicializamos gestor de datos y plantillas */
			//$this->oDataTemplate = new DataTemplate();
			$this->oDataTemplate = DataTemplate::getRootDataTemplate();
		}
		
		/**
		 * Todas las peticiones de request.php nos llevan aqui
		 */
		public function processRequest(){
			try {
				
				/**FIX. Si estamos logados y estamos usando el URL de entra al sistea fallamos */
				if ( $this->oUser->isLogged() && $this->oOrderActionClass->getAction()==$this->oConfigSystem->getKeyData('default_action') ){
					$this->oLogger->debug( "Detectada situacion login-prefix" );
					header("Location: ".PREFIX_URL);
					return;
				}
				
				/** validaciones sobre la peticion */
				$this->checkLogin();
			
				/** preparamos localizacion de la clase solicitada */
				$package   = strtolower($this->oOrderActionClass->getClass());
				$nameClass = $this->oOrderActionClass->getClass();
				$pathClass = OS_ROOT.'/applications/'.$package.'/'.$nameClass.'.class.php';
				$this->_OCLASS = null; //instancia de la clase
				$this->oLogger->debug( "Cargando la clase $pathClass" ); 
				require ( $pathClass );
				
				/** determinamos si debemos buscarla en la sesion o es una nueva peticion */
				if ( $this->oOrderActionClass->getClassSession() && $_SESSION[$this->oOrderActionClass->getClassSession()]!=null ){
					/** en la request viene este parametro que nos dice que busquemos en la sesion esta clase por este nombre especial */
					$this->oLogger->debug( 'Recuperando la clase de la session(1) '.$this->oOrderActionClass->getClassSession() );
					$this->_OCLASS = unserialize($_SESSION[$this->oOrderActionClass->getClassSession()]);
					call_user_method('setInstance', $this->_OCLASS, $this);
				} else if ( $_SESSION[$this->oOrderActionClass->getClass()]!=null ) {
					/** la clase esta en sesion pero con su nombre original de clase */
					$this->oLogger->debug( 'Recuperando la clase de la session(2) '.$this->oOrderActionClass->getClass() );
					$this->_OCLASS = unserialize($_SESSION[$this->oOrderActionClass->getClass()]);
					call_user_method('setInstance', $this->_OCLASS, $this);
				} else {
					/** la clase no esta en sesion, es la primera vez que se invoca o no es persistible */
					$this->oLogger->debug( "Instanciando la clase(3) $nameClass" );
					$this->_OCLASS = new $nameClass($this);
				}			
			} catch (Exception $e){
				$this->oLogger->error( "Error irrecuperable(1)!!!" );
				//FIX. Expira la sesion con la ventana del navegador abierta, el usuario intenta navegar como si nada pero ya no hay sesion y la accion no es el login
				if ( !$this->oUser->isLogged() ){
					header("Location: /");
					return;
				}							
				$oClass = new Messages($this);
				$oClass->showMessage ( array("title"=>"Error inesperado(1) ;(","message"=>"Se ha producido un error no controlado. Por favor, ponte en contacto con soporte técnico para intentar reproducir el error y determinar la causa.","type"=>"error") );
			}
			
			/** ya tenemos la clase carga en respuesta a la peticion actual, invocamos el metodo Action */
			try {
				$this->oLogger->info("Invocando al metodo ".$this->oOrderActionClass->getAction());
				call_user_method($this->oOrderActionClass->getAction(), $this->_OCLASS);
			} catch (DobleOSException $e){
				$this->oLogger->error( $e->getMessage()."::".$e->getCode() );
				$oClass = new Messages($this);
				$oClass->showMessage ( $e->getDatos() );				
				
			} catch (Exception $e){
				$this->oLogger->error( "Error irrecuperable(2)!!!" );							
				$oClass = new Messages($this);
				$oClass->showMessage ( array("title"=>"Error inesperado(2) ;(","message"=>"Se ha producido un error no controlado. Por favor, ponte en contacto con soporte técnico para intentar reproducir el error y determinar la causa.","type"=>"error") );
			}
			
			/** entrega de datos según formato solicitado */
			try {
				$this->processTemplate();
			} catch (Exception $e){
				$this->oLogger->error( "Error irrecuperable(3)!!! No puedo procesar la template y no se pintará la pantalla del cliente" );	
				$oClass = new Messages($this);
				$oClass->showMessage ( array("title"=>"Error inesperado(3) ;(","message"=>"Se ha producido un error no controlado. Por favor, ponte en contacto con soporte técnico para intentar reproducir el error y determinar la causa.","type"=>"error") );
				$this->processTemplate();
			}
			
			return true;
		}
				
		/** 
		 * Comprobamos que la peticion se realiza en una situacion logada. 
		 * Si no es asi, solo admitimos peticiones para las clases que no necesitan validacion
		 * 
		 * NOTAS. hay que hacer una lista de clases que permitan ejecucion sin login para no hardcodear
		 */
		private function checkLogin(){	
			if ( !$this->oUser->isLogged() ){
				if ( strtolower( $this->oConfigSystem->getKeyData('default_class') )==strtolower($this->oOrderActionClass->getClass()) ||
					 strtolower($this->oOrderActionClass->getClass())=='login' || 
					 strtolower($this->oOrderActionClass->getClass())=='system' )
					return;
				$this->oLogger->info( 'Usuario no logado' );
				throw new Exception('Usuario no logado', 99);
			}
		}
		
		/**
		 * Envia la plantilla con los datos al cliente
		 */
		private function processTemplate(){
			$this->oLogger->debug('channel :'.$this->oOrderActionClass->getChannel());
			if ( $this->oOrderActionClass->getChannel()=='html' ){
				$this->oLogger->debug("pintando-> ".$this->oDataTemplate->getTemplate());
				$kernel  = $this->includekernelsData();
				$module  = $this->_OCLASS;
				$kernel  =  $this->oDataTemplate->getKeyData('kernel');
				$datos   =  $this->oDataTemplate->getKeyData(strtolower($this->getOrderActionClass()->getClass()));
				header('Content-Type: text/html; charset=ISO-8859-1');
				require ( $this->oDataTemplate->getTemplate() );
			} else if ( $this->oOrderActionClass->getChannel()=='json' ) {
				$this->oLogger->debug("datos json:".$this->oDataTemplate->getKeyData('json'));
				header('Content-Type: application/json');
				print $this->oDataTemplate->getKeyData('json');
			} else if ( $this->oOrderActionClass->getChannel()=='log' ) {
				$this->oLogger->debug("channel LOG");
				header('Content-disposition: attachment; filename=fichero_log_'.date("d-m-y").'.log');
				header("Pragma: no-cache");
				header("Expires: 0");
			} else if ( $this->oOrderActionClass->getChannel()=='txt' ) {
				$this->oLogger->debug("channel TXT");
				header('Content-disposition: attachment; filename=fichero_txt_'.date("d-m-y").'.txt');
				header("Pragma: no-cache");
				header("Expires: 0");
			} else if ( $this->oOrderActionClass->getChannel()=='csv' ) {
				$this->oLogger->debug("channel csv");
				header('Content-Type: application/vnd.ms-excel');
				header("Content-disposition: attachment; filename=fichero_csv_".date("d-m-y").".csv");
				header("Pragma: no-cache");
				header("Expires: 0");
				print $this->oDataTemplate->getKeyData('csv');
			} else if ( $this->oOrderActionClass->getChannel()=='pdf' ) {
				$this->oLogger->debug("channel PDF");
				header('Content-Type: application/pdf');
				header("Pragma: no-cache");
				header("Expires: 0");
			} else if ( $this->oOrderActionClass->getChannel()=='xml' ) {
				$this->oLogger->debug("channel XML");
				header('Content-disposition: attachment; filename=fichero_xml_'.date("d-m-y").'.xml');
				header("Pragma: no-cache");
				header("Expires: 0");				
			} else {
				$this->oLogger->error( "channel no esperado ".$this->oOrderActionClass->getChannel() );
			}
			return true;
		}
		

		/**
		 * Devuelve el objeto que almacena la información de usuario
		 */
		public function getUser(){
			return $this->oUser;
		}
		
		/**
		 * Devuelve el objeto que almacena la información de preferencias del usuario
		 */
		public function getPreferencesUser(){
			return ($this->oPreferencesUser);
		}
		
		/**
		 * Devuelve el objeto que almacena los datos y las plantillas de
		 * la interfaz de usuario
		 */
		public function getDataTemplate(){			
			return $this->oDataTemplate;
		}

		/**
		 * Devuelve el objeto Environment
		 */
		public function getEnvironment(){
			return $this->oEnvironment;
		}
		
		/**
		 * Devuelve el objeto Conexion
		 */
		public function getConnection(){
			return $this->oConnection;
		}
		
		/** Devuelve el objeto para enviar emails */
		public function getEmail(){
			return $this->oEmail;
		}		
		
		/** Devuelve el objeto logger que nos permite escribir trazas */
		public function getLogger(){
			return $this->oLogger;
		}
		
		/**
		 * Nos devuelve el valor recibido de la peticion del usuario
		 * @param $data El nombre del parametro recibido
		 */
		public function getOrderActionClass(){
			return $this->oOrderActionClass;
		}
		
		/**
		 * Devuelve el objeto que almacena las preferencias del kernela
		 */
		public function getConfigSystem(){
			return $this->oConfigSystem;
		}
		
		/**
		 * Devuelve el objeto que almacena las preferencias del kernela
		 */
		public function setConfigSystem(ConfigSystem $conf){
			$this->oConfigSystem = $conf;
		}
		
		/**
		 * Incluye en los datos de las template del canal HTML los datos fijos del sistema
		 * como pathes, mensajes, urls, etc.
		 */
		private function includekernelsData(){
			$this->oLogger->debug( 'Incluyendo datos del kernel en la plantilla' );
			$datos = array();
			if ( $this->getPreferencesUser()->getTheme()!=null ){
				$datos[theme]   	= $this->getPreferencesUser()->getTheme();
				$datos[wallpaper]   = $this->getPreferencesUser()->getWallPaper();
			}else{
				$datos[theme]   	= $this->getConfigSystem()->getKeyData('default_theme');
				$datos[wallpaper]  	= $this->getConfigSystem()->getKeyData('default_wallpaper');
			}
			$datos[title]   		= $this->getConfigSystem()->getKeyData('app_title');
			$datos['class']			= strtolower($this->getOrderActionClass()->getClass());
			$datos[handle]   		= $_REQUEST["sessionclass"];
			$datos[wellcome_msg] 	= $this->getConfigSystem()->getKeyData('wellcome_msg');
			$datos[debug] 			= $this->getConfigSystem()->getKeyData('debug');
			$datos[icon_size] 		= $this->getConfigSystem()->getKeyData('icon_size');
			$datos[dockmenu_label] 	= $this->getConfigSystem()->getKeyData('dockmenu_label');
			$datos[refresh_task] 	= $this->getConfigSystem()->getKeyData('refresh_task');
			$datos[system_version]	= $this->getConfigSystem()->getKeyData('app_title') . ' v.'.self::VERSION;
			/** clase actual.nos da el nombre con el que esta guardado en sesion */
			$datos[sessionclass] = $this->_OCLASS->persistenceName;
			
			$this->oDataTemplate->addData('kernel', $datos);
			return $datos;
		}
		
	}
?>