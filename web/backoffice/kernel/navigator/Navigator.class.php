<?	
	/**
	*	@author Antonio Gámez
	*   
	*   @version 5.4 08.2014 Optamos por dejar lo mas limpio posible el script index.php y llevarnos toda la logica de carga de clases, conexion a datos
	*   y ejecucion del framewor a esta clase, que es el framework Navigator.
	*   @version 5.3 08.2014 Cambios menores. Shop por Tienda_Instanciador
	*	@version 5.2 02.2013 Ajustamos el nivel del log y las trazas para poder hacer un seguimiento dentro del caos de trazas que tenemos
	*	@version 5.1 11.2012	Cambios a public el metodo getData($key) para poder acceder desde las clases de negocio a los datos aqui cacheados
	*	@version 5.0 (12/11)
	*		Cambio radical. Se singletoniza la clase y se repasa todo el funcionamiento cacheando la navegaciï¿½n. Se termina de integrar el sistema
	*		de log.
	*	@version 4.0 (5/11)
	*		Los errores se notifican por email
	*		Multi-idioma (no funcionaba bien)
	*		Cambiamos la forma de usar la clase. Primero se instancia, despuï¿½s se pasa el objeto conexiï¿½n a datos, despues podemos cachear los objetos que queramos y finalmente lanzamos la ejecucion
	*	@version 3.1 (10/5/09) 
	*		Se habilita la posibilidad de ejecuciï¿½n de metodos estandard definidos en acciones con el prefijo '$', es decir, que independientemente de la acciï¿½n solicitada
	*		se ejecutaran todas las acciones con el prefijo indicado. No son acciones encadenables.
	*	@version 3.0 (16/9/08) Las acciones con "PRE ACCION" ahora se evaluan con true o false, y llevan a una navegaciï¿½n nueva, antes solo ocurrï¿½a en caso false. Este tipo
	*		de navegaciï¿½n es ahora como un IF para encadenar condiciones si es necesario
	*	@version 2.3 (1/8/08) El atributo _METO.DO tiene el metodo utilizado en el paso de parametros, POST o GET
	*	@Version 2.2 (19/03/2008)
	*		Se cargan todos los ficheros de negocio en vez de solo los solicitados por la accion.
	*	@Version 2.1 (01/03/2008)
	*		El sistema permite previa evaluaciï¿½n del metodo de verificaciï¿½n de las acciones, cambiar el flujo de navegaciï¿½n volviendo a emprezar.
	*	@Version 2.0 (26/02/2008)
	*		En la versiï¿½n anterior, un archivo plantilla estaba formado por piezas. Esas piezas llamaban un metodo getPiezas(xxx) para generar los datos de la misma. Pero
	*		un dato que se necesitava en dos de estas piezas no podia ser pasado. Ahora un nuevo metodo paint() utiliza buffers para pintar los datos (el html). Podemos 
	*		colocar datos en el bloque del codigo (super) para que un metodo los recoja y los pinte putDatosSuper()
	*	@Version 1.0 (14/02/2008) creacion
	*	
	*	Sistema que lee de una tabla las acciones solicitadas por el usuario para pintar una plantilla. Opcionalmente se ejecuta un metodo que permite
	*	determinar si esta accion ha de llegar a fin o el sistema cambia el flujo de navegaciï¿½n, volviendo a empezar.
	*/	
	

	/** cargamos clases de uso comun */
	require_once ( OS_ROOT . '/kernel/environment/Environment.class.php' );
	require_once ( OS_ROOT . '/kernel/log4php/Logger.php' );
	require_once ( OS_ROOT . '/kernel/conexion/Conexion.class.php' );		
	require_once ( OS_ROOT . '/kernel/mailer/class.phpmailer.php' );
	require_once ( OS_ROOT . '/kernel/mailer/Email.class.php' );
	require_once ( OS_ROOT . '/kernel/Utils.class.php' );
	require_once ( OS_ROOT . '/kernel/quickskin/class.quickskin.php' );
	require_once ( OS_ROOT . '/kernel/dompdf/dompdf_config.inc.php');
	
	/** configuramos el logger aqui para poder tirar trazas desde el principio */
	Logger::configure( OS_ROOT . '/kernel/configurations/log4php.public.properties' );
	
	/** cargamos configuracion del sistema */
	$oEnvironment = Environment::getInstance( OS_ROOT . '/kernel/configurations/' . ENVIRONMENT_FILE );
			
	/** conexion a datos */
	$oConnection = new Conexion( $oEnvironment->getParams('host'),
		$oEnvironment->getParams('usuario'),$oEnvironment->getParams('password'),
			$oEnvironment->getParams('bbdd'),$oEnvironment->getParams('port'));
			
	/** Esta clase gestiona la persistencia de las clases propias de este sistema */
	require_once ( OS_ROOT . '/kernel/negocio/classes/Tienda_Instanciador.class.php' );
	
	/** ---->> Esta clase es la que tenemos que personalizar para cada tienda */
	require_once ( OS_ROOT . "/kernel/negocio/classes/Tienda.class.php" );
	
	/** Framework - Navigator ... cargamos valores y ejecutamos */
	$oNavigator = Navigator::getInstance();
	$oNavigator->setConnection( $oConnection );
	$oNavigator->setShop ( Tienda_Instanciador::getInstance('Tienda') );	
	$oNavigator->setWorkFolder( OS_ROOT . '/kernel/negocio/workflow');
	$oNavigator->setFolderTemplates( $_SERVER['DOCUMENT_ROOT'] . PUBLIC_WEB_PATH );
	$oNavigator->replaceAll("{ROOT_PATH}",PUBLIC_WEB_PATH);
	$oNavigator->execute();

	/********************************** NAVIGATOR ************************************************************/
	class Navigator {
		
		const VERSION 				= '5.4';
		const DEFAULT_FOLDERTEMPLATE= '/';
		const DEFAULT_FOLDERLANG 	= 'esp';
		const DEFAULT_WORKFLODER 	= './workflow/';
		const DEFAULT_CLASSFOLDER 	= './classes/';
		const DEFAULT_ACTION	 	= 'home';
		const SQL_NAVIGATOR 		= 'SELECT * FROM _navigator WHERE estado!="XXX" ORDER BY accion;';
		const SQL_NAVITATORDETAIL 	= 'SELECT * FROM _navigator_details WHERE estado!="XXX" ORDER BY orden;';
		const PRODUCTION_MODE		= true;
		
		public static $shop;
		private static $instance;
		public static $log;
		private $err 				= false;

		private $dataActions 		= null;
		private $dataDetailActions 	= null;
		private $plantilla 			= null;
		public $block				= null;
		public $pageBlock			= null;
		private static $replaceItems= array();
		private static $dataCache	= array();
		public static $loadTime = 0;
		
		/** configurables */
		private $emailSupport 		= null;
		private $folderTemplate		= null;
		private $folderlang 		= null;		
		public static $connection 	= null;
		private $workFolder 		= null;
		private $classFolder 		= null;
		private $action 			= null;
		
		
		/**
		 * Al ser un objeto singleton intentamos recuperar la unica instancia de la sesion, 
		 * si no lo conseguimos creamos el objeto nuevo
		 */
		public static function getInstance(){
			self::$loadTime = microtime(true);
	        self::$log = Logger::getRootLogger();
	        self::$log->info( "*********************************************************".$_SERVER[REMOTE_ADDR]." ".Utils_OS::detectClientOS()."/".Utils_OS::detectClientBrowser() );
			if( isset($_SESSION[__CLASS__]) ){
				self::$instance = unserialize($_SESSION[__CLASS__]);
				self::notifyRecover();
	        }else
				self::$instance = new self;
		    return self::$instance;
		}
		
		/**
		 * Solo notificamos la creacion del objeto
		 */
		private function __construct( ){
			self::$log->debug( "Creamos instancia de ".__CLASS__ );
		}	
		
		/**
		 * Al destruir el objeto cerramos la conexion y persistimos la instancia
		 */
		public function __destruct(){
			//self::$log->debug( "Persistiendo la instancia de ".__CLASS__ );
			self::$connection->closeConnection();
			$_SESSION[__CLASS__] = serialize( self::$instance );
			self::$log->info( "*********************************************************".$_SERVER[REMOTE_ADDR]." (Querys:".self::getConnection()->numQuerys.") (Tiempo carga:".number_format((microtime(true) - self::$loadTime),3)." segundos)" );
		}
		
		/**
		 * Nos sirve de punto similar al constructor al recuperar la instancia cacheada, ademï¿½s
		 * notificamos el objecto instanciado persistido/cacheado que recuperamos de la sesion.
		 * Para entornos NO PRODUCCION recarga la cache constantemente (ya que la borra con null) para coger
		 * los cambios
		 */
		private function notifyRecover(){
			//self::$log->debug( "Recuperamos instancia de ".__CLASS__ );
			
			if ( !self::PRODUCTION_MODE ){
				//self::$log->debug( "No estamos en modo producciï¿½n");
				self::$instance->dataActions = null;
				self::$instance->dataDetailActions = null;				
			} else {
				//no generamos trazas para no entorpecer el rendimiento
				self::$log->shutdown();
			}
				
		}
		
		public function getPlantilla(){
			return $this->plantilla;
		}
		
		/**
		 * El framework tiene que buscar en una tabla la navegaciï¿½n requeriada por el usuario,
		 * necesitamos una conexion a datos
		 * @param Conexion $conn
		 */
		public function setConnection(Conexion $conn){
			self::$connection = $conn;
			self::$connection->getConnection();
		}
		
		/**
		 * La funciones de workflow necesitan de una conexion. Utilizamos la misma en todo el aplicativo
		 */
		public function getConnection(){			
			return self::$connection;
		}
		
		/**
		 * El framework tiene una clase raiz Shop desde donde extienden las clases especificas del negocio
		 * @param Shop $shop 
		 */
		public function setShop(Tienda_Instanciador $shop){
			self::$shop = $shop;
		}
		
		/**
		 * Instancia Shop
		 */
		public function getShop(){
			return self::$shop;
		}
		
		/**
		 * Carpeta donde estan las funciones de negocio del aplicativo
		 * @param $dir carpeta/directorio absoluto al servidor
		 */
		public function setWorkFolder($dir){
			//self::$log->debug( "WorkFolder es $dir" );
			$this->workFolder = $dir;
		}
		
		/**
		 * Devuelve la carpeta/directorio donde encontrar las funciones
		 * del aplicativo. Si esta sin inicializar asignamos el valor default
		 */
		public function getWorkFolder(){
			if ( !$this->workFolder )
				$this->setWorkFolder( Navigator::DEFAULT_WORKFLODER );
			return $this->workFolder;
		}
		
		/**
		 * Es una subcarpeta de FolderTemplate y es donde estarï¿½n las plantillas a cargar del paï¿½s en cuestiï¿½n
		 * @param $dir carpeta/directorio relativo al documentroot
		 */		
		public function setFolderLand($dir){
			//self::$log->debug( "FolderLand es $dir" );
			$this->folderlang = $dir;
		}
		
		/**
		 * Devuelve la subcarpeta PAIS de la carpeta templates desde donde cargar todas las plantillas
		 * traducidades del pais indicado. Si no se indica por defecto cargarï¿½ el valor default
		 */
		public function getFolderLand(){
			if ( !$this->folderlang )
				$this->setFolderLand( Navigator::DEFAULT_FOLDERLANG );
			return $this->folderlang;
		}
		
		/**
		 * Carpeta raiz de las plantillas de todos los idiomas.
		 * @param $dir carpeta/directorio relativo al documentroot
		 */		
		public function setFolderTemplates($dir){
			//self::$log->debug( "FolderTemplates es $dir" );
			$this->folderTemplate = $dir;
		}
		
		/**
		 * Devuelve la carpeta raiz de las plantillas de todos los idiomas
		 * Si no se indica por defecto cargarï¿½ el valor default
		 */
		public function getFolderTemplates(){
			if ( !$this->folderTemplate )
				$this->setFolderTemplates( Navigator::DEFAULT_FOLDERTEMPLATE );
			return $this->folderTemplate;
		}
		
		/**
		 * Acciï¿½n/operaciï¿½n requerida por el usuario
		 * @param $action valor reflejado en BD que identifica 
		 * el fichero PHP y funciï¿½n a invocar por este Framework
		 */
		public function setAction($action){
			self::$log->debug( ">>>Action es: $action" );
			$this->action = $action;
		}
		
		/**
		 * Devuelve la acciï¿½n/operaciï¿½n requerida por usuario.
		 * Si no se indica por defecto cargarï¿½ el valor default
		 */
		public function getAction(){
			if ( !$this->action )
				$this->setAction( Navigator::DEFAULT_ACTION );
			return $this->action;
		}
		
		/**
		 * Carga todos los fichero que gestionan el negocio (ficheros PHP)
		 */
		private function loadWorkFolder(){
			if ( $gestor = opendir($this->workFolder) ) {
    			while (false !== ($archivo = readdir($gestor))) {
    				if ( !is_file($this->workFolder.'/'.$archivo) ) continue;
    				//self::$log->debug( "Cargando fichero $archivo" );
    				require_once ($this->workFolder.'/'.$archivo);
    			}
    			closedir($gestor);		        	
        	}
		}
		
		/**
		 * Carga toda la tabla de navegacion maestra (navigator) y los detalles(parts) 
		 */
		private function cacheDataActions(){
			//self::$log->debug( "Cacheando data actions" );
			
			// array para la tabla maestra. tiene las acciones posibles y las acciones de autostart ($...)
			$this->dataActions = array();
			$result = self::$connection->query(Navigator::SQL_NAVIGATOR);
			while ( $rows = self::$connection->getColumnas($result) ){
				$data = array("action"=>$rows[accion],"preaction"=>$rows[metodo_pre_accion],"plantilla"=>$rows[plantilla],"accion_salida_false"=>$rows[accion_salida_false],"accion_salida_true"=>$rows[accion_salida_true]);
				$this->dataActions[$rows[accion]]=$data;
			}
			
			// array para la tabla detalles. tiene los detalles de todas las acciones y las acciones comunes (ALL)
			$this->dataDetailActions = array();
			$result = self::$connection->query(Navigator::SQL_NAVITATORDETAIL);
			while ( $rows = self::$connection->getColumnas($result) ){
				$data = array("action"=>$rows[accion],"bloque"=>$rows[bloque],"orden"=>$rows[orden],"exclusiones"=>$rows[exclusiones],"paquete"=>$rows[paquete],"pagina"=>$rows[pagina]);
				$this->dataDetailActions[$rows[accion]][]=$data;
			}
		}
		
		/**
		 * Buscamos en la cache que la action solicitada por el usuario este cacheada, si no, es un error por que
		 * es una navegaciÃ³n desconocida para el sistema
		 */
		private function existsAction(){
			if (!array_key_exists($this->getAction(), $this->dataActions))
				throw new Exception("Houston!!!, tenemos un problema. No sabemos como mostrar la pagina $this->action");
		}
		
		/**
		 * Las auto start actions son acciones cuyo nombre tiene el prefijo $
		 * Estas se ejecutan siempre, en cada peticion de navegaciï¿½n del usuario
		 */
		private function autoStartActions(){
			foreach( array_keys($this->dataActions) as $action){
				if ( strpos($action, "$")===false ) continue;
						
				//self::$log->debug("Hay que lanzar la auto-action $action");
				$pos 	 = strrpos($this->dataActions[$action]['preaction'],'.');
				$metodo  = substr($this->dataActions[$action]['preaction'],$pos+1);
				$paquete = substr($this->dataActions[$action]['preaction'],0,$pos);
				$paquete = ereg_replace("\.","/",$paquete).'.php';
				
				//self::$log->debug("Debemos invocar a $metodo()");
				if ( !function_exists( $metodo ) )
					throw new Exception("La operaciï¿½n ::$action hace referencia al metodo $metodo() pero no lo hemos encontrado!!!");

				$metodo(self::$instance, self::$shop);
			}
		}
		
		/**
		 * Ejecuta la operaciï¿½n solicitada por el usuario, lanzando los metodos necesarios, cargando las plantilas, etc.
		 * Aqui cargamos la plantilla "esqueleto" de la pagina que verï¿½ el usuario
		 */
		private function executeAction(){
			self::$log->info("La action es: ".$this->getAction());
			
			/** Es el momento de preguntar si la accion solicita existe, de lo contrario, no podemos continuar */
			$this->existsAction();

			$this->plantilla = $this->getFolderTemplates().'/'.$this->getFolderLand().'/'.$this->dataActions[$this->getAction()]['plantilla']; //esqueleto
			//self::$log->debug("La plantilla para ".$this->getAction()." ahora es $this->plantilla");
			
			/** 
			 * Hay preaction. es un caso especial de navegacion que quiere decir que tenemos que evaluar una condicion y continuar la 
			 * navegaciï¿½n por otro sitio, es como una action que selecciona otras actions. Hay dos posibilidades, el action para el resultado
			 * true y el action para el resultado false */						
			if ( $this->dataActions[$this->getAction()]['preaction'] ) {				
				$pos 	 = strrpos($this->dataActions[$this->getAction()]['preaction'],'.');
				$metodo  = substr($this->dataActions[$this->getAction()]['preaction'],$pos+1);
				$paquete = substr($this->dataActions[$this->getAction()]['preaction'],0,$pos);
				$paquete = ereg_replace("\.","/",$paquete).'.php';
				self::$log->debug("Hay que lanzar la pre-action $metodo()");

				if ( !function_exists( $metodo ) )
					throw new Exception("La operaciï¿½n ::".$this->getAction()." hace referencia al metodo $metodo() pero no lo hemos encontrado!!!");
				
				$resultado = $metodo(self::$instance, self::$shop);
				self::$log->debug("El resultado de invocar a $metodo() es ".($resultado)?"true":"false");
				
				if ( $resultado )
					$this->setAction($this->dataActions[$this->getAction()]['accion_salida_true']);
				else
					$this->setAction($this->dataActions[$this->getAction()]['accion_salida_false']);
				$this->executeAction();
			}	
		}
		
		/**
		 * La plantilla pide bloques (pequeï¿½os html) que son generados a partir de los PHP de la carpeta workflow
		 * @throws Exception Si se invoca a un metodo inexistente
		 */
		private function executePackage(){
			
			$this->pageBlock = $this->getFolderTemplates().'/'.$this->getFolderLand().'/'.$this->block['pagina'];
			//self::$log->debug( "Pagina para este action#bloque#orden#pagina es: ". $this->getAction().'#'.$this->block['bloque'].'#'.$this->block['orden'].'#'.$this->pageBlock );
			
			/** obtenemos la funcion php asociada a este bloque html... si la tiene */			
			if ( $this->block['paquete'] ){
				$pos 	 = strrpos($this->block['paquete'],'.');
				$metodo  = substr($this->block['paquete'],$pos+1);
				$paquete = substr($this->block['paquete'],0,$pos);
				$paquete = ereg_replace("\.","/",$paquete).'.php';
			
				if ( !function_exists( $metodo ) )
					throw new Exception("La operaciï¿½n ::".$this->getAction()." en su bloque ".$this->block['bloque']." hace referencia al metodo ".$this->block['paquete']." pero no lo hemos encontrado!!!");
				
				self::$log->debug("Ejecutamos metodo: $metodo");
				$metodo(self::$instance, self::$shop);
			}

			//self::$log->debug("Ahora hago el require de $this->pageBlock");
			require ( $this->pageBlock );
		}
		
		public static function addData($key,$datos){
			self::$log->debug( "AÃ±adiendo datos en $key" );
			self::$dataCache[$key] = $datos;
		}
		
		public static function getData($key){
			self::$log->debug( "Recuperando datos de $key" );
			if ( self::$dataCache[$key]==null )
				self::$log->error( "Datos de $key son nulos" );
			return self::$dataCache[$key];
		}
		
		public function changeOnFlyPageBlock($newPage){
			$this->pageBlock = $this->getFolderTemplates().'/'.$this->getFolderLand().'/'.$newPage;
		}
		
		/**
		 * Ya tenemos la plantilla y solo nos resta pintarla pero utilizamos los buffers para controlar la escritura
		 */
		private function paint(){
			ob_start();
			try {
				//self::$log->debug( "Pintado $this->plantilla" );
				require_once ( $this->plantilla );
				
				/** si hay que reemplazar cadenas de forma global, este atributo tiene la lista de cadenas a reemplazar con su valor */
				if ( self::$replaceItems!=null ){
					self::$log->debug("Reemplanzando cadenas antes de soltar el buffer");
					$buffer = ob_get_clean();
					foreach ( array_keys(self::$replaceItems) as $key )
						$buffer = preg_replace("/$key/", self::$replaceItems[$key], $buffer);
					ob_end_flush();			
					if( !ob_start("ob_gzhandler") ) ob_start();
					echo $buffer;
				}
				
				ob_end_flush();
			} catch (Excention $e){
				ob_clean();
				self::$log->error( "Error grave...$e" );
			}
		}
		
		/**
		 * Inicia la navegaciï¿½n del usuario cargando las funciones del workflow, plantillas
		 * del idioma, buscando la acciï¿½n requeridad por el usuario, etc.
		 */
		public function execute(){
			try {
				/** si nos indican cambio de lenguaje */
				if (isset($_REQUEST[lang] ))
					$this->setFolderLand( $_REQUEST[lang] );			
				
				/** navegacion solicitida */
				if (isset($_REQUEST['do']))
					$this->setAction( $_REQUEST['do']);
				else
					$this->setAction( Navigator::DEFAULT_ACTION );
				
				/** cargamos toda la navegacion(master) y sus detalles(detail)*/
				if ( $this->dataActions==null )
					$this->cacheDataActions();	
				
				/** cargamos ficheros de negocio */
				$this->loadWorkFolder();
				
				/** ejecutamos auto-actions. Operaciones generales y automaticas */
				$this->autoStartActions();
				
				/** ejecutamos peticion usuario. lanzando metodos, cargando plantillas, etc */
				//self::$log->debug("Inicio el proceso de ejecuciï¿½n de la action");
				$this->executeAction();
				//self::$log->debug("Finalizo el proceso de ejecuciï¿½n de la action");
				
				//self::$log->debug("Inicio el proceso de ejecuciï¿½n de los bloques de la plantilla");
				$this->paint();
				//self::$log->debug("Finalizo el proceso de ejecuciï¿½n de los bloques de la plantilla");
				
			} catch (Exception $e){
				self::$log->error( "Se ha producido un error: $e");
				echo $e->getMessage();
				
				//TODO email
				//TODO pantall aviso
			}	
		}	
		
		/**
		 * Procesa y devuelve el bloque solicitado dentro del esqueleto de la plantilla actual
		 * @param $bloque El bloque solicitado. Indican la zona de la plantilla que se esta pidiento y el codigo html
		 * que irï¿½a ahï¿½. Son por ejemplo, BODY, LEFT, RIGHT, HEAD, BOTTOM, etc. 
		 */
		public function executeBlock($bloque){
			//self::$log->debug( "Solicitado bloque $bloque" );
			if ( $this->err ) {
				self::$log->debug( "Hay un error previo y se cancela el proceso de este bloque" );
				return;
			}
			
			/** me quedo con los details cuyo action sea el actual y ALL para el bloque $bloque */
			foreach ( array_keys($this->dataDetailActions) as $actions ){
				if ( $actions!=$this->action && $actions!='ALL' ) continue;
				/** lo que llega aqui son los details de la actions ALL y $this->action pero de cualquier bloque*/
				foreach( $this->dataDetailActions[$actions] as $details ){
					if ( $details['bloque']!=$bloque ) continue;
					//self::$log->debug(strstr($details['exclusiones'],$this->action.":"));
					if ( strstr($details['exclusiones'],$this->action.":")==true ) continue;
					$this->block = $details;
					$this->executePackage();
				}
				
			}
		}
		
		/**
		 * 
		 * Enter description here ...
		 * @param unknown_type $patter
		 * @param unknown_type $string
		 */
		public static function replaceAll($patter,$string){
			if ( self::$replaceItems==null )
				self::$replaceItems = array();
			self::$replaceItems[$patter]=$string;	
		}
		
	}
?>