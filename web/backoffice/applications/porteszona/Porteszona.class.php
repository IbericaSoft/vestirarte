<?
/**
 * @author Dobleh software. Antonio Gámez
 * @abstract Gestión de zonas de portes
 * @version 1.0 10.2014 Creación
 */
	
	require_once ( OS_ROOT . '/kernel/dompdf/dompdf_config.inc.php');
	class Porteszona extends Applications {
		
		public $VERSION = 'Version: 1.0 (10.2014)<br><br><i>Personalización L&M</i><br><br><b>Dobleh Software 2014</b>';
		public $PATHCLASS = '/applications/porteszona';
		public $cache_pais = null;
		public $cache_provincias = null;
		public $cache_zonas = null;
		
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
		 * Datos supuestamente constantes. Cacheado para optimizar
		 */
		public function bindingsData(){
			$this->oLogger->debug("cacheando datos zonas");
			$rows = array();
			$result = $this->computeSQL("SELECT * FROM zonas WHERE estado IN ('ON') ORDER BY zona;",null);
			while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
				array_push( $rows, array("id"=>$row['id'],"zona"=>utf8_encode($row['zona'])) );
			$this->cache_zonas = json_encode($rows);
			$this->computeTemplate("cache_zonas", $this->cache_zonas, null);
			
			if ( $this->cache_pais == null ){
				$this->oLogger->debug("cacheando datos pais");
				$rows = array();
				$result = $this->computeSQL("SELECT * FROM pais WHERE estado IN ('ON') ORDER BY long_name;",null);
				while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
					array_push( $rows, array("id"=>$row['id'],"label"=>utf8_encode($row['long_name'])) );
				$this->cache_pais = json_encode($rows);
			}
			$this->computeTemplate("cache_pais", $this->cache_pais, null);
			
			if ($this->cache_provincias == null ){
				$this->oLogger->debug("cacheando datos provincias");
				$rows = array();
				$result = $this->computeSQL("SELECT * FROM provincias ORDER BY id_pais,provincia;",null);
				while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
					array_push( $rows, array("id"=>$row['id'],"id_pais"=>$row['id_pais'],"label"=>utf8_encode($row['provincia']),"provincia"=>utf8_encode($row['provincia'])) );
				$this->cache_provincias = json_encode($rows);
			}
			$this->computeTemplate("cache_provincias", $this->cache_provincias, null);
			
			$rows = array();
			$result = $this->computeSQL("SELECT * FROM provincias WHERE id NOT IN (select id_provincia from zonas_detalle) ORDER BY id_pais,provincia;",null);
			while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
				array_push( $rows, array("id"=>$row['id'],"id_pais"=>$row['id_pais'],"label"=>utf8_encode($row['provincia']),"provincia"=>utf8_encode($row['provincia'])) );
			$this->computeTemplate("provincias_libres", json_encode($rows), null);
		}
		
		/**
		 * Metodo de inicio de la clase
		 */
		public function start(){
			$this->oLogger->debug( "Inicio clase" );
			//$this->noFilter();
			$this->filter();
		}
		
		/**
		 * Aplicacion de filtros
		 */
		public function filter(){
			$this->pagina = 1;
			$this->filtroSQL = "SELECT 
					zon.id, zon.zona, lower(group_concat(pro.provincia separator ', ')) provincias, zon.estado
					FROM zonas zon LEFT JOIN zonas_detalle zond ON (zon.id=zond.id_zona) LEFT JOIN provincias pro ON (zond.id_provincia=pro.id)
					WHERE";
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
					array_push( $rows, array("id"=>$row[id],"zona"=>utf8_encode($row['zona']),"provincias"=>utf8_encode($row['provincias']),"estado"=>$row[estado]) );
				$key = 'json';
				$template = null;
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
			$this->oSystem->getLogger()->debug( "Aplicando filtro " . get_class($this) );
			
			if ( $_REQUEST['_estado'] )
				$this->filtroSQL .= " zon.estado='$_REQUEST[_estado]'";
			else
				$this->filtroSQL .= " zon.estado IN ('ON','OFF')";
			
			if ( $_REQUEST['_id_zona'] )
				$this->filtroSQL .= " AND zon.id = '$_REQUEST[_id_zona]";
			
			if ( $_REQUEST['_id_pais'] )
				$this->filtroSQL .= " AND pro.id_pais ='$_REQUEST[_id_pais]'";
			
			if ( $_REQUEST['_id_provincia'] )
				$this->filtroSQL .= " AND pro.id ='$_REQUEST[_id_provincia]'";
			
			
			$this->filtroSQL .= " GROUP BY zon.id ORDER BY zon.zona";
		}
		
		/** 
		 * @see IBackEnd::edit()
		 */
		public function doEdit(){
			$sql = "SELECT zon.*, (select nombre from os_administradores where id=zon.id_administrador) usuario FROM zonas zon WHERE zon.id=$_REQUEST[id] AND zon.estado!='XXX';";
			$resultado = $this->computeSQL($sql, false);
			if ( !$this->oSystem->getConnection()->hayResultados() )
				throw new DobleOSException("Error, no encuentro los datos!!!",0,array("title"=>"Error, no encuentro los datos","message"=>$sql,"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);			
			$this->bindingsData();
			$datos[operacion] = 'Edici&oacute;n';
			$key = strtolower(get_class($this));
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
			
			//provincias del porte
			$provincias = array();
			$result = $this->computeSQL("SELECT * FROM provincias WHERE id IN(select id_provincia from zonas_detalle where id_zona=$_REQUEST[id]) ORDER BY id_pais,provincia;",null);
			while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
				array_push( $provincias, array("id"=>$row['id'],"id_pais"=>$row['id_pais'],"label"=>utf8_encode($row['provincia']),"provincia"=>($row['provincia'])) );
			$this->oSystem->getDataTemplate()->addData('provincias', $provincias);
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doNew()
		 */
		public function doNew(){
			$this->bindingsData();
			$datos[operacion] = 'Alta';
			$datos[estado] = 'ON';
			$datos['id_pais'] = '209';
			$key = strtolower(get_class($this));
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
		}
		
		/**
		 * Baja de datos. Eliminar el porte.
		 * @throws DobleOSException
		 */
		public function doDelete(){
			$sql = "UPDATE zonas SET estado='XXX' WHERE id=$_REQUEST[id];";
			$this->computeSQL($sql, false);
			if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) )				
				throw new DobleOSException("Error, Operación no completada!!!",0,array("title"=>"Error, Operación no completada","message"=>"SQL error: ".addslashes($this->oSystem->getConnection()->getError()),"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
			
			header("Location: ".OS_WEB_PATH."?class=$_REQUEST[class]&do=listAll&sessionclass=$_REQUEST[sessionclass]");
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doUpdate()
		 */
		public function doUpdate(){			
			$datos				= array();
			$id 				= (int)$_REQUEST[id];
			$datos[zona] 		= $_REQUEST[zona];
			$datos[estado] 		= $_REQUEST[estado];
			/** auditoria */
			$datos[fmodificacion]	= date("Y-m-d H:i:s");
			$datos[id_administrador]= $this->oSystem->getUser()->getId();
			if ( !$id )
				$datos[falta] 			= date("Y-m-d H:i:s");

			//Provincias
			if ( count($_POST[provincias])==0 )
				throw new DobleOSException($message,0,array("title"=>"Aviso de error","message"=>"No hay provincias para la zona!!!","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
			
			$this->computeSQL("START TRANSACTION;", false);
			if ($id > 0){
				while(list($key,$val)= each($datos)){
					if ($sqlUpdate) $sqlUpdate .= ',';
					$sqlUpdate .= "$key=\"".addslashes(trim($val))."\"";
				}
				$sql = "UPDATE zonas SET $sqlUpdate WHERE id=$id";
			}else{
				$datos[id] = Utils_OS::getValueAPP($this->oSystem->getConnection() , 'ZONAPORTE_ID');
				Utils_OS::updateValueAPProl($this->oSystem->getConnection(), 'ZONAPORTE_ID', ($datos[id]+1),$this->oSystem->getUser()->getId());
				$id = $datos[id];
				while(list($key,$val)= each($datos)){
					if ($sqlFields) {$sqlFields.=',';$sqlValues.=',';}
					$sqlFields .= "$key";
					$sqlValues .= "\"".addslashes(trim($val))."\"";
				}
				$sql = "INSERT INTO zonas ($sqlFields) VALUES ($sqlValues)";
			}
			$this->computeSQL($sql, false);
			
			if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
				$message = 'Los datos no se han salvado. Detalles: '.addslashes($this->oSystem->getConnection()->getError());
				$this->computeSQL("ROLLBACK;", false);
				throw new DobleOSException($message,0,array("title"=>"Aviso de error","message"=>$message,"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
			}

			//provincias
			$this->computeSQL("DELETE FROM zonas_detalle WHERE id_zona=$id;", false);			
			foreach ($_POST[provincias] as $item){
				$this->computeSQL("INSERT INTO zonas_detalle VALUES($id,$item);", false);
				if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
					$this->computeSQL("ROLLBACK;", false);
					$message = 'Los datos no se han salvado. Detalles: '.addslashes($this->oSystem->getConnection()->getError());
					throw new DobleOSException($message,0,array("title"=>"Aviso de error","message"=>$message,"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
				}
			}
			$this->computeSQL("COMMIT;", false);
			header("Location: ".OS_WEB_PATH."?class=$_REQUEST[class]&do=listAll&sessionclass=$_REQUEST[sessionclass]");
		}
		
		/**
		 * 
		 * exportacion de datos csv
		 */
		public function exportCSV(){
			parent::generalExportCSV( 'csv', array('Id','Paï¿½s','Zona','Peso','Importe','Estado'), array('id','pais','zona','peso','importe','estado'),$this->pathClass . '/csv.html' );
			return true;
		}
		
		/**
		* Generar fichero de datos filtrados PDF
		*/
		public function doListPrint(){
			parent::pdfGenericList(array('pais','zona','peso','importe'),"../_commons/css","Listado Portes.pdf");
		}
		
		/**
		 * Ventana para buscar articulos. La usan otros mï¿½dulos del sistema que puedean necesitar datos de este mï¿½dulo.
		 * Si llega specialFilter, es un tipo de filtro para la busqueda concreto que la aplicacion cliente nos pasa.
		 */
		public function doMiniSearch(){
			parent::doMiniSearch();
		}
		
	}
?>