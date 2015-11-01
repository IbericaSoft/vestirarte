<?
/**
 * @author Dobleh software. Antonio Gámez
 * @abstract Migración de datos Laguardia-Moreira
 * @version 1.0 02.2014 Creación
 */
	require_once ( OS_ROOT . '/applications/migracion/AsynRequest.class.php');
	require_once ( OS_ROOT . '/applications/migracion/ArticlesMigration.class.php');
	require_once ( OS_ROOT . '/kernel/dompdf/dompdf_config.inc.php');
	require_once ( OS_ROOT . '/kernel/mailer/Email.class.php');
	class Migracion extends Applications {
		
		public $VERSION 	= 'Version: 1.0 (02.2014)<br><br><i>Creación</i><br><br><b>Dobleh Software 2014</b>';
		public $PATHCLASS 	= '/applications/migracion';
		private $folder_migration = null;
		private $file = null;
		private $module = null;
		private $operation = null;
		private $who = null;
		private $emails_notify = null;
		
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
			$this->isPesistance = true;
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
		 * Datos externos que necesita este módulo
		 */
		public function bindingsData(){
			if ( $this->folder_migration==null )
				$this->folder_migration=Utils_OS::getValueAPP($this->oSystem->getConnection() , 'MIGRATION_DIR');
			$this->computeTemplate("folder_migration", $this->folder_migration, null);
			$this->emails_notify = explode(",",Utils_OS::getValueAPP($this->oSystem->getConnection() , 'EMAIL_ALERTAS'));
		}
		
		/**
		 * Método invocado al iniciar un clase sin acción definida
		 */
		public function start(){
			$this->noFilter();
			$this->filter();
		}
		
		/**
		 * Filtro de datos
		 */
		public function filter(){
			$this->pagina = 1;
			$this->filtroSQL = "SELECT id,operacion,modulo,date_format(fecha,'%d-%m-%Y %H:%i:%s') fecha,date_format(ffin,'%d-%m-%Y %H:%i:%s') ffin,resultado FROM migracion WHERE";
			$this->computeFilter();			
			$this->listAll();
		}
		
		/**
		 * listado de datos con el filtro actual
		 */
		public function listAll(){
			$this->bindingsData();
			$key = strtolower(get_class($this));
			$datos = array();
			$datos = $this->computeSQL($this->filtroSQL, (($this->externQuery)?false:true) );
			if ( $this->oSystem->getOrderActionClass()->getChannel()=='html' ){	
				$template = $this->pathApp . '/listado.html';
			} else {
				$datosJson = array("error"=>"NO","callBack"=>"");
				$rows = array();
				while ( $row = $this->oSystem->getConnection()->getColumnas($datos) )
					array_push( $rows, array("id"=>$row[id],"operacion"=>$row['operacion'],"modulo"=>$row['modulo'],"fecha"=>$row['fecha'],"ffin"=>$row['ffin'],"resultado"=>$row[resultado]) );
				$key = 'json';
				$template = '';
				$pagination = $this->oSystem->getConnection()->getPaginacionJSON();
				$datosJson['callBack']="refreshList(".json_encode($pagination).",".json_encode($rows).")";
				$datos = json_encode( $datosJson );
			}
			$this->computeTemplate($key, $datos, $template);
		}
		
		/**
		 * filtro SQL aplicable a esta clase
		 */
		public function computeFilter(){
			if ( $_REQUEST['_estado'] )
				$this->filtroSQL .= " resultado='$_REQUEST[_estado]'";
			else
				$this->filtroSQL .= " resultado NOT IN ('XXX')";
			
			if ( $_REQUEST['_desde'] ){
				$desde = substr($_REQUEST['_desde'], 6).'-'.substr($_REQUEST['_desde'], 3,2).'-'.substr($_REQUEST['_desde'], 0,2);
				$this->filtroSQL .= " AND fecha >= '$desde'";
			}
			
			if ( $_REQUEST['_hasta'] ){
				$desde = substr($_REQUEST['_hasta'], 6).'-'.substr($_REQUEST['_hasta'], 3,2).'-'.substr($_REQUEST['_hasta'], 0,2);
				$this->filtroSQL .= " AND fecha <= '$desde'";
			}
				
			$this->filtroSQL .= " ORDER BY fecha desc, ffin desc";	
		}
		
		/** 
		 * @see IBackEnd::edit()
		 */
		public function doEdit(){
			$id = (int)$_REQUEST[id];
			$sql = "SELECT *,date_format(fecha,'%d-%m-%Y %H:%i:%s') fecha,date_format(ffin,'%d-%m-%Y %H:%i:%s') ffin,(select nombre from os_administradores where id=id_administrador) usuario FROM migracion WHERE id=$id;";
			$resultado = $this->computeSQL($sql, false);
			if ( !$this->oSystem->getConnection()->hayResultados() )
				throw new DobleOSException("El registro $id no existe!!!",111,array("title"=>"Aviso de error","message"=>"El registro solicitado ¡no existe!","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"SQL no da resultados: $sql") );

			$this->bindingsData();
			
			/** lectura del registro */
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			
			/** El canal d la petición cliente nos dice si montamos los datos de una forma u otra, con plantilla o sin ella */
			if ( $this->oSystem->getOrderActionClass()->getChannel()=='html' ){
				if ( $datos[operacion]=='IMPORTAR' )	
					$template = $this->pathApp . '/edicion.html';
				else
					$template = $this->pathApp . '/edicion_export.html';
				$key = strtolower(get_class($this));
			} else {
				$rows = array();
				$datosJson = array("error"=>"NO","callBack"=>"");
				array_push( $rows, array("id"=>$datos[id],"articulo"=>utf8_encode($datos['articulo']),"precio"=>utf8_encode($datos['precio']),"fprecio"=>utf8_encode($datos['fprecio']),"codigo"=>utf8_encode($datos['codigo']),"estado"=>$datos[estado]) );
				$datosJson['callBack']="callBack(".json_encode($rows).")";
				$datos = json_encode( $datosJson );
				$key = 'json';
				$template = '';
			}
			
			$datos[log] = file_get_contents($_SERVER[DOCUMENT_ROOT].$this->folder_migration.'/migration/import/'.$datos[info]);
			$datos[xml] = file_get_contents($_SERVER[DOCUMENT_ROOT].$this->folder_migration.'/migration/import/'.$datos[fichero]);
			$this->computeTemplate($key, $datos, $template);
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doNew()
		 */
		public function doNew(){
		}
		
		/**
		 * Baja de un articulo
		 * Hay una regla para no eliminar
		 */
		public function doDelete(){
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doUpdate()
		 */
		public function doUpdate(){
		}
		
		
		/**
		 * Generar fichero de datos filtrados CSV
		 * exportacion de datos csv
		 */
		public function exportCSV(){
			parent::generalExportCSV( 'csv', array('Id','Tipo','Artículo','Código','Precio','Precio Compra','IVA','Proveedor','Stock','Rendimiento','Observaciones','Estado'), 
				array('id','tipo','articulo','codigo','precio','precio_compra','iva','proveedor','stock','rendimiento','observaciones','estado'),$this->pathClass . '/csv.html' );
		}
		
		/**
		* Generar fichero de datos filtrados PDF
		*/
		public function pdfGenericList(){
			parent::pdfGenericList(array('articulo','codigo','@cprecio'),"../_commons/css","Listado Artículos.pdf");
		}
		
		/** avanzar en el proceso de migración */
		public function doFordward(){			
			if ( $_REQUEST[migration]=='OPERATION' ){
				$template = $this->pathApp . '/step_operacion.html';
			}elseif ( $_REQUEST[migration]=='SELECT_OPERATION'){
				if ( $_REQUEST[choose]=='ERP'){				
					$template = $this->pathApp . '/step_erp.html';
					$this->operation = "IMPORTAR";
				}else{
					$template = $this->pathApp . '/step_backoffice.html';
					$this->operation = "EXPORTAR";
				}
			}elseif ( $_REQUEST[migration]=='MODULE'){
				$this->file = $_REQUEST[fichero];
				$template = $this->pathApp . '/step_modulo.html';
			}elseif ( $_REQUEST[migration]=='RUN'){				
				$this->module = $_REQUEST[choose];
				$this->who = $this->oSystem->getUser()->getId();
				if ( $this->operation=='IMPORTAR' )
					$method = 'ERPimport';
				else
					$method = 'BackofficeExport';
				$params = array("file"=>$this->file,"module"=>$this->module,"operation"=>$this->operation,"truncate"=>$_REQUEST[truncate],"who"=>$this->who,"class"=>'migracion',"do"=>$method,"ticket"=>"y");				
				AsynRequest::call(HOST, 80, PREFIX_URL, $params);
				throw new DobleOSException("Migración lanzada con éxito",0,array("title"=>"Migración","message"=>"El proceso de migración ha comenzado. Este proceso puede durar varios minutos. Al finalizar, recibira un email en su cuenta de aviso y podrá ver todos los detalles.","type"=>"warn","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
				return;
			}
			
			$this->bindingsData();
			$this->computeTemplate($key, $datos, $template);
		}
		
		/**
		 * Proceso de exportacion
		 */
		public function BackofficeExport(){
			set_time_limit(300);
			$adm = $this->oSystem->getUser()->getId();
			$this->bindingsData();
			$xml = date("YmdHis").'.xml';
			$haveErrors = false;
			/* registramos la operacion */
			list($id) = ($this->oSystem->getConnection()->getColumnas( $this->computeSQL("SELECT max(id)+1 id FROM migracion;", false) ));
			if ( !$id ) $id=1;
			$sql = "INSERT INTO migracion (id,fichero,operacion,modulo,resultado,fecha,id_administrador) VALUES($id,'$xml','$_REQUEST[operation]','$_REQUEST[module]','PROCESANDO',now(),$adm);";
			$this->computeSQL($sql, false);
			
			$path = $this->folder_migration.'/migration/export/'.$xml;
			switch(strtoupper($_REQUEST[module])){
				case "ARTICULOS":
					$class=new ArticlesMigration($this->oSystem);
					$haveErrors = $class->export($path);
					break;
					
			}		
					
			/* registro de fin y envio de email */
			$this->oLogger->debug("he terminado");
			$sql = "UPDATE migracion SET resultado='TODO-OK',ffin=now() WHERE id=$id;";
			$this->computeSQL($sql, false);
			
			$this->oSystem->getEmail()->enviar("agamez@dobleh.com", NULL, "Proceso de migración finalizado", "BODY", "");
			$this->oLogger->debug( $this->oSystem->getEmail()->getErrorEnvio() );
			
		}
		
		/**
		 * Proceso de migracion ERP a BackOffice... es asincrono y no tiene valores de sesion
		 */
		public function ERPimport(){
			set_time_limit(300);
			$adm = $this->oSystem->getUser()->getId();
			$this->bindingsData();
			
			$log = date("YmdHis").'.log';
			$haveErrors = false;
			/* registramos la operacion */
			list($id) = ($this->oSystem->getConnection()->getColumnas( $this->computeSQL("SELECT max(id)+1 id FROM migracion;", false) ));
			if ( !$id ) $id=1;
			$sql = "INSERT INTO migracion (id,fichero,operacion,modulo,resultado,fecha,id_administrador,info) VALUES($id,'$_REQUEST[file]','$_REQUEST[operation]','$_REQUEST[module]','PROCESANDO',now(),$adm,'$log');";			
			$this->computeSQL($sql, false);
			
			try {
				/* cargamos el XML */
				$this->oLogger->debug( $this->folder_migration."migration/import/$_REQUEST[file]" );
				$xml = simplexml_load_file( $this->folder_migration."migration/import/$_REQUEST[file]" );
				if (!$xml)
					throw new Exception("XML mal formado");
				
				if ( $_REQUEST[module]!=strtoupper($xml->getName()) )
					throw new Exception("El modulo del XML que se intenta migrar no coincide con el que ha indicado ($_REQUEST[module]));");

				$this->computeSQL("SET AUTOCOMMIT=0;", false);
				/* procesamos cada tipo de migracion en su clase especifica */
				switch(strtoupper($xml->getName())){
					case "ARTICULOS": 
						$class=new ArticlesMigration($this->oSystem);
						$haveErrors = $class->import($xml,$this->folder_migration.'migration/import/'.$log);
						break;
					case "PORTES": 
						$class=new ShippingMigration($this->oSystem);
						$haveErrors = $class->import($xml,$this->folder_migration.'migration/import/'.$log);
						break;
					case "PROVEEDORES": 
						$class=new ProvidersMigration($this->oSystem);
						$haveErrors = $class->import($xml,$this->folder_migration.'migration/import/'.$log);
						break;
				}
				
				/* registro de fin y envio de email */
				$this->oLogger->debug("he terminado");
				if ( $haveErrors )
					$sql = "UPDATE migracion SET resultado='AVISOS',ffin=now() WHERE id=$id;";
				else
					$sql = "UPDATE migracion SET resultado='TODO-OK',ffin=now() WHERE id=$id;";
				$this->computeSQL($sql, false);
				
				foreach ($this->emails_notify as $email){
					$this->oSystem->getEmail()->enviar($email, NULL, "Proceso de migración finalizado", "Este email es solamente informativo", "");
					$this->oLogger->debug( $this->oSystem->getEmail()->getErrorEnvio() );
				}
			} catch (Exception $e){				
				$this->oLogger->error($e->getMessage());
				$sql = "UPDATE migracion SET resultado='ERROR',ffin=now(),info=\"".$e->getMessage()."\" WHERE id=$id;";
				$this->computeSQL($sql, false);
			}
			
			$this->computeSQL("SET AUTOCOMMIT=1;", false);
		}		
		
		public function doDownload(){
			$f = $this->folder_migration."$_REQUEST[xml]";	
			$f = str_replace("/", DIRECTORY_SEPARATOR, $f);
			$this->oLogger->debug($f);
			print( file_get_contents($f) );
		}
		
		public function doDownloadLog(){			
			$f = $this->folder_migration."$_REQUEST[log]";
			$f = str_replace("/", DIRECTORY_SEPARATOR, $f);
			$this->oLogger->debug($f);
			print( file_get_contents($f) );
		}
	}
?>