<?
/**
 * @author Dobleh Software. Antonio Gámez
 * @abstract Gestión de Delegaciones
 * @version 1.0 07.2014	creación
 */
	require_once ( OS_ROOT . '/kernel/dompdf/dompdf_config.inc.php');
	class Delegaciones extends Applications {
		
		public $VERSION = 'Version: 1.0 (07.2014)<br><br><i>Delegaciones L&M</i><br><br><b>Dobleh Software 2014</b>';
		public $PATHCLASS = '/applications/delegaciones';
		public $list_cache = null;
		
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
		 * Datos externos vinculados con esta clase (tablas externas)
		 * @see Applications::bindingsData()
		 */
		public function bindingsData(){
			if ($this->list_cache == null ){
				$this->oLogger->debug("cacheando datos");
				$rows = array();
				$result = $this->computeSQL("SELECT * FROM delegaciones WHERE estado NOT IN ('XXX');",null);
				while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
					array_push( $rows, array("id"=>$row['id'],"label"=>utf8_encode($row['delegacion'])) );
				$this->list_cache = json_encode($rows);
			}
			$this->computeTemplate("list_cache", $this->list_cache, null);
		}
		
		/**
		 * Al lanzar la clase invocamos este metodo que no devuelve resultados, lo dejamos asi
		 * para obligar al usuario a usar filtros
		 * @see root/system/Applications::start()
		 */
		public function start(){
			$this->oLogger->debug( "Inicio clase" );
			$this->noFilter();
			$this->filter();
		}
		
		/**
		 * aplicamos un filtro para buscar datos
		 * @see root/system/Applications::filter()
		 */
		public function filter(){
			$this->pagina = 1;			
			$this->filtroSQL = "SELECT * FROM delegaciones d";
			$this->computeFilter();			
			$this->listAll();
		}
		
		/**
		 * Para pedir los datos de esta clase desde otras clases
		 */
		public function externFilter(){
			$this->externQuery = true;
			$this->isPesistance = false;
			$this->filter();
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
					array_push( $rows, array("id"=>$row['id'],"delegacion"=>utf8_encode($row['delegacion']),"estado"=>$row['estado'] ) );
				$key = 'json';
				$template = '';
				$pagination = $this->oSystem->getConnection()->getPaginacionJSON();
				$datosJson['callBack']="refreshList(".json_encode($pagination).",".json_encode($rows).")";
				$datos = json_encode( $datosJson );
			}
			$this->oLogger->debug($template);
			$this->computeTemplate($key, $datos, $template);
		}
		
		/**
		 * filtro SQL aplicable a esta clase
		 */
		public function computeFilter(){
			$this->oLogger->debug( "Aplicando filtro " . get_class($this) );
			
			if ( $_REQUEST['_estado'] )
				$this->filtroSQL .= " WHERE d.estado='$_REQUEST[_estado]'";
			else
				$this->filtroSQL .= " WHERE d.estado!='XXX'";					
				
			if ( $_REQUEST['_delegacion'] )
				$this->filtroSQL .= " AND d.delegacion LIKE '%".utf8_decode($_REQUEST[_delegacion])."%'";
			
			$this->filtroSQL .= " ORDER BY d.delegacion";
		}

		/**
		 * Edición de datos
		 */
		public function doEdit(){
			$this->oSystem->getLogger()->debug( "Edición de datos" );
			$id = (int)$_REQUEST[id];
	
			$sql = "SELECT *,(select nombre from os_administradores where id=id_administrador) usuario FROM delegaciones WHERE id=$id AND estado!='XXX';";
			$resultado = $this->computeSQL($sql,false);
			if ( !$this->oSystem->getConnection()->hayResultados() )
				throw new DobleOSException("El registro $id no existe!!!",111,array("title"=>"Aviso de error","message"=>"El registro solicitado ¡no existe!","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"SQL no da resultados: $sql") );
						
			$key = strtolower(get_class($this));
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
						
			$datos[operacion] = 'Edición';
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
		}
		
		/**
		 * Nuevo registro
		 * @see root/system/IApplications::doNew()
		 */
		public function doNew() {
			$key = strtolower(get_class($this));
			$datos[operacion] = 'Alta';
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doDelete()
		 */
		public function doDelete(){
			$id 				= (int)$_REQUEST[id];
			$datos[estado] 		= 'XXX';
			$datos[fmodificacion]= date("Y-m-d H:i:s");
			
			while(list($key,$val)= each($datos)){
					if ($sqlUpdate) $sqlUpdate .= ',';
		   		$sqlUpdate .= "$key=\"$val\"";
			}
			$sql = "UPDATE delegaciones SET $sqlUpdate WHERE id=$id";
			
			$this->computeSQL($sql, false);
						
			if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
				$this->oSystem->getLogger()->error( $this->oSystem->getConnection()->getError() );
				$message = 'Los datos no se han salvado. Detalles: '.addslashes($this->oSystem->getConnection()->getError());
				$this->oSystem->getDataTemplate()->addData('messages', array("msg"=>$message));
			}else{
				$message = 'Operación realizada con éxito';
			}
			
			header("Location: ".OS_WEB_PATH."?class=$_REQUEST[class]&do=listAll&sessionclass=$_REQUEST[sessionclass]");
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doUpdate()
		 */
		public function doUpdate(){
			$this->oSystem->getLogger()->debug( "Actualización de datos" );			
			
			$id 				= (int)$_REQUEST[id];
			$datos				= array();
			$datos[delegacion] 		= trim($_POST[delegacion]);
			$datos[estado] 		= $_REQUEST[estado];
			/** auditoria */			
			$datos[fmodificacion]	= date("Y-m-d H:i:s");
			$datos[id_administrador]= $this->oSystem->getUser()->getId();
			if ( !$datos[id] )
				$datos[falta] 			= date("Y-m-d H:i:s");
			
			if ($id > 0){
				while(list($key,$val)= each($datos)){
					if ($sqlUpdate) $sqlUpdate .= ',';
		   			$sqlUpdate .= "$key=\"$val\"";
				}
				$sql = "UPDATE delegaciones SET $sqlUpdate WHERE id=$id";
			}else{		
				while(list($key,$val)= each($datos)){
					if ($sqlFields) {$sqlFields.=',';$sqlValues.=',';}
					$sqlFields .= "$key";
					$sqlValues .= "\"".addslashes(trim($val))."\"";
				}
				$sql = "INSERT INTO delegaciones ($sqlFields) VALUES ($sqlValues)";
			}
			
			$this->computeSQL($sql, false);
			
			if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
				$message = 'Los datos no se han salvado. Detalles: '.addslashes($this->oSystem->getConnection()->getError());
				throw new DobleOSException($message,0,array("title"=>"Aviso de error","message"=>$message,"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
			}					
								
			header("Location: ?class=$_REQUEST[class]&do=listAll&sessionclass=$_REQUEST[sessionclass]");
		}
		
		/**
		 * @see IBackEnd::exportCSV()
		 */
		public function exportCSV(){
			parent::generalExportCSV( 'csv', array('Id','Delegacion','Estado'), array('id','delegacion','estado'),$this->pathClass . '/csv.html' );
			return true;
		}
		
		/**
		 * Generar fichero de datos filtrados PDF
		 */
		public function doListPrint(){
			parent::pdfGenericList(array('delegacion','estado'),"../_commons/css","Listado delegaciones L&M.pdf");
		}
		
		
	}
?>
