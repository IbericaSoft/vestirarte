<?
/**
 * @author ANANsoft
 * @abstract Operaciones del sistema
 * @version 1.0 11.2011 creacion
 * @version 1.1 03.2012 getHandle() cambia su funcionamiento. Antes se pedia un ID de ventana leyendo de BBDD, ahora simplmente
 * 					se calcula la hora/minuto/segundo y se devuelve como ID de ventana
 */
	class System extends Tools {		
		
		private $pathApp;
		private $err;
		private $oLogger;

		
		/**
		 * En el constructor de la clase recibimos el objeto DobleOS que nos permite disponer de acceso a la gestion el sistema
		 * @param $system Es el objeto Session que nos pasan por 'referencia'
		 */
		public function __construct( DobleOS $system ){
			parent::__construct($system);
			$this->oLogger = Logger::getRootLogger();
			$this->pathApp = OS_ROOT.'/applications/system';
		}
		
		public function __destruct(){}
		
		/** 
		 * Actualiza la posicion de un icono en la tabla de iconos del usuario
		 */
		public function refreshIcon() {			
			$this->oLogger->debug("Refrescando posicion icono");			
			$callback = array();
			//array_push($callback, utf8_encode("alert('OK');"));
			$datos 	= array("error"=>'NO',"callBack"=>$callback);
			
			$sql = "UPDATE os_icons_user SET itop=\"$_REQUEST[itop]\",ileft=\"$_REQUEST[ileft]\" WHERE icon_id=$_REQUEST[icon_id];";
			$this->computeSQL($sql,false);
			
			//$this->oLogger->debug( $datos );
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}
		
		public function refreshProcess() {			
			$this->oLogger->debug("Refrescando proceso");
			$callback = array();
			array_push($callback, utf8_encode("alert('OK');"));
			$datos 	= array("error"=>'NO',"callBack"=>$callback);
			
			$id 				= (int)$_REQUEST[process_id];
			$datos				= array();
			$datos[user_id] 	= $_REQUEST[user_id];
			$datos['class'] 		= $_REQUEST['class'];
			$datos['do'] 		= $_REQUEST['do'];
			$datos[sessionclass] 	= $_REQUEST[sessionclass];
			$datos[width] 		= $_REQUEST[width];
			$datos[height] 		= $_REQUEST[height];
			
			if ($id > 0){
				while(list($key,$val)= each($datos)){
					if ($sqlUpdate) $sqlUpdate .= ',';
		   		$sqlUpdate .= "$key=\"$val\"";
				}
				$sql = "UPDATE os_process_user SET $sqlUpdate WHERE process_id=$id";
			}else{		
				while(list($key,$val)= each($datos)){
					if ($sqlFields) {$sqlFields.=',';$sqlValues.=',';}
		   		$sqlFields .= "$key";
		   		$sqlValues .= "\"$val\"";
				}
				$sql = "INSERT INTO os_process_user ($sqlFields) VALUES ($sqlValues)";
			}
			
			$this->computeSQL($sql, false);
			
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}
		
		/** 
		 * Pide un HANDLE para la venta que vamos abrir
		 */
		public function getHandle() {			
			$this->oLogger->debug("getHandle");						
			
//			$reqHan = ($_REQUEST[handleType])?$_REQUEST[handleType]:'handle_id';
//			$handle = (int) Utils_OS::getValueOS($this->oSystem->getConnection(), $reqHan);
//			$this->oLogger->debug( "Conseguido handle $handle");
//			Utils_OS::updateValueOS($this->oSystem->getConnection(), $reqHan, ($handle+1));		
			$handle = date("His");	
			
			$datos 	= array("error"=>'NO',"handle"=>$handle);
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}
		
		/** Insertar un proceso (nueva ventana) en la tabla de procesos */
		public function windowProcess(){
			$this->oLogger->debug("Nuevo proceso");
			
			$datos				= array();
			$datos[user_id] 	= $this->oSystem->getUser()->getId();
			$datos['process_id']	= $_REQUEST['process_id'];
			$datos['_class'] 	= $_REQUEST['_class'];
			$datos['_do'] 		= $_REQUEST['_do'];
			$datos['_width'] 		= $_REQUEST['_width'];
			$datos['_height'] 		= $_REQUEST['_height'];
			$datos['_top'] 		= $_REQUEST['_top'];
			$datos['_left'] 		= $_REQUEST['_left'];
			$datos['_minimize'] 	= $_REQUEST['_minimize'];
			$datos['_maximize'] 	= $_REQUEST['_maximize'];
			$datos['_closable'] 	= $_REQUEST['_closable'];
			$datos['_resizable'] 	= $_REQUEST['_resizable'];
			$datos['_status']		= $_REQUEST['_status'];
			$datos['_title']		= utf8_decode($_REQUEST['_title']);
			$datos['_parameters']	= $_REQUEST['_parameters'];
			$datos['date']			= date("Y-m-d H:i:s");
			
			while(list($key,$val)= each($datos)){
				if ($sqlFields) {
					$sqlFields.=',';
					$sqlValues.=',';
				}
		   		$sqlFields .= "$key";
		   		$sqlValues .= "\"$val\"";
			}
			//primero borro el proceso si existe (caso de recargar el applicativo)
			$this->closeProcess();
			
			//insertamos el proceso
			$sql = "INSERT INTO os_process_user ($sqlFields) VALUES ($sqlValues)";
			$this->computeSQL($sql, false);
			$datos 	= array("error"=>'NO');
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}
		
		/** Actualiza atributos de un proceso abierto */
		public function updateProcess(){
			$this->oLogger->debug("Actualizando proceso");
			$user 	= $this->oSystem->getUser()->getId();
			$sql = "UPDATE os_process_user SET _width=$_REQUEST[_width],_height=$_REQUEST[_height],_top=$_REQUEST[_top],_left=$_REQUEST[_left],_status='$_REQUEST[_status]' WHERE user_id=$user AND process_id='$_REQUEST[process_id]';";
			$this->computeSQL($sql, false);
			$datos 	= array("error"=>'NO');
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}
		
		/** Elimina un proceso en la tabla de procesos */
		public function closeProcess(){
			$this->oLogger->debug("Cerrar proceso");
			$user 		= $this->oSystem->getUser()->getId();
			$process	= $_REQUEST['process_id'];
			$sql = "DELETE FROM os_process_user WHERE user_id='$user' AND process_id='$process';";
			$this->computeSQL($sql, false);
			$datos 	= array("error"=>'NO');
			
			unset($_SESSION[$process]);
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}
		
		/** Elimina todos los procesos en la tabla de procesos del usuario */
		private function closeAllProcess(){
			$this->oLogger->debug("Cerrar todos los procesos");
			$user 		= $this->oSystem->getUser()->getId();
			$sql = "DELETE FROM os_process_user WHERE user_id='$user';";
			$this->computeSQL($sql, false);
			$datos 	= array("error"=>'NO');
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}
		
		public function closeSession(){
			$this->closeAllProcess();
			$this->oLogger->debug("Destruyendo sesion");
			session_destroy();
			$path = ( OS_WEB_PATH=='' )?'/':OS_WEB_PATH;
			$datos 	= array("error"=>'NO',"callBack"=>utf8_encode("top.document.location='$path';"));
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}
		
		public function viewCloseSession(){
			$this->oLogger->debug("Ver cerrar sesión");
			$this->oDataTemplate = DataTemplate::getRootDataTemplate();
			$this->oDataTemplate->setTemplate($this->pathApp.'/exit.html');
			return true;
		}
		
		/** Insertar un icono en la tabla de iconos de usuario */
		public function iconProcess(){
			$this->oLogger->debug("Nuevo icono");
			
			$datos				= array();
			$datos[user_id] 	= $this->oSystem->getUser()->getId();
			$datos['icon_id']	= $_REQUEST['icon_id'];
			$datos['_class'] 	= $_REQUEST['_class'];
			$datos['_do'] 		= $_REQUEST['_do'];
			$datos['_width'] 	= $_REQUEST['_width'];
			$datos['_height'] 	= $_REQUEST['_height'];
			$datos['_top'] 		= $_REQUEST['_top'];
			$datos['_left'] 	= $_REQUEST['_left'];
			$datos['_minimize'] = $_REQUEST['_minimize'];
			$datos['_maximize'] = $_REQUEST['_maximize'];
			$datos['_closable'] = $_REQUEST['_closable'];
			$datos['_resizable']= $_REQUEST['_resizable'];
			$datos['_status']	= 	$_REQUEST['_status'];
			$datos['_parameters']	= ($_REQUEST['_parameters']);
			$datos['_icon']	= ($_REQUEST['_icon']);
			$datos['_itop']	= ($_REQUEST['_itop']);
			$datos['_ileft']	= ($_REQUEST['_ileft']);
			$datos['_ititle']	= ($_REQUEST['_ititle']);
			
			while(list($key,$val)= each($datos)){
				if ($sqlFields) {
					$sqlFields.=',';
					$sqlValues.=',';
				}
		   		$sqlFields .= "$key";
		   		$sqlValues .= "\"$val\"";
			}
			$sql = "INSERT INTO os_icons_user ($sqlFields) VALUES ($sqlValues)";
			$this->computeSQL($sql, false);
			$datos 	= array("error"=>'NO');
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}

		/**
		 * Actualiza algunas de los atributos del icono del usuario
		 */
		public function iconUpdate() {
			$this->oLogger->debug("Actualizando icono");
			$user 	= $this->oSystem->getUser()->getId();
			$sql = "UPDATE os_icons_user SET _itop=$_REQUEST[_itop],_ileft=$_REQUEST[_ileft] WHERE user_id=$user AND icon_id=$_REQUEST[icon_id];";
			$this->computeSQL($sql, false);
			$datos 	= array("error"=>'NO');
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}
		
		/**
		 * Muestra la ventana que mostrara las propiedades del icono
		 */
		public function viewIconProperties(){
			$key	  = 'info';
			$template = $this->pathApp . '/viewIconProperties.html';
			$datos    = array();
			$this->computeTemplate($key, $datos, $template);
			$this->oSystem->getDataTemplate()->addData($key, $datos);
		}
		
		/**
		 * Elimina el icono de usuario
		 */
		public function deleteIcon(){
			$user 	= $this->oSystem->getUser()->getId();
			$icon	= $_REQUEST['icon_id'];
			$sql = "DELETE FROM os_icons_user WHERE user_id='$user' AND icon_id='$icon';";
			$this->computeSQL($sql, false);
			$datos 	= array("error"=>'NO');
			$this->oSystem->getDataTemplate()->addData('json', json_encode($datos) );
			return true;
		}
	}
?>