<?
/**
 * @author Dobleh Software. Antonio Gámez
 * @abstract Gestión de ivas
 * @version 1.0 03.2012 creacion
 * @version 1.1 04.2012 Preparamos el módulo para la compartición de CSS/JS/IMAGES
 * @version 1.2 Evolución JQuery
 */

	class Iva extends Applications {
		
		public $VERSION = 'Version: 1.2 (02.2014)<br><br><i>Evolución JQuery</i><br><br><b>Dobleh Software 2014</b>';
		public $PATHCLASS = '/applications/iva';

		
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
		 * Datos externos vinculantes con esta clase (tablas externas)
		 * @see Applications::bindingsData()
		 */
		public function bindingsData(){
		}
		
		/**
		 * Al lanzar la clase invocamos este metodo que no devuelve resultados, lo dejamos asi
		 * para obligar al usuario a usar filtros
		 * @see root/system/Applications::start()
		 */
		public function start(){
			$this->oLogger->debug( "Inicio clase" );
			//$this->noFilter();
			$this->filter();
		}
		
		/**
		 * aplicamos un filtro para buscar datos
		 * @see root/system/Applications::filter()
		 */
		public function filter(){
			$this->pagina = 1;
			$this->filtroSQL = 'SELECT * FROM iva WHERE ';
			$this->computeFilter();			
			$this->listAll();
		}
		
		/**
		 * Para pedir los datos de esta clase desde otras clases
		 */
		public function externFilter(){
			$this->externQuery = true;
			$_REQUEST[estado] = null;
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
					array_push( $rows, array("id"=>$row[id],"descripcion"=>utf8_encode($row['descripcion']),"iva"=>utf8_encode($row['iva']),"estado"=>$row[estado]) );
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
			$this->oSystem->getLogger()->debug( "Aplicando filtro " . get_class($this) );
			
			if ( $_REQUEST['_estado'] )
				$this->filtroSQL .= " estado='$_REQUEST[_estado]'";
			else
				$this->filtroSQL .= " estado!='XXX'";
			

		}
		
		/** 
		 * @see IBackEnd::edit()
		 */
		public function doEdit(){
			$this->oSystem->getLogger()->debug( "Edición de datos" );
			$id = (int)$_REQUEST[id];
	
			if ($id){
				$sql = "SELECT *,(select nombre from os_administradores where id=id_administrador) usuario FROM iva WHERE id=$id AND estado!='XXX';";
				$resultado = $this->computeSQL($sql, false);
				if ( !$this->oSystem->getConnection()->hayResultados() ){
					$datos = array('titulo'=>'Error en la edición','mensaje'=>"No encuentro el registro indicado $id",'url'=>"$_SERVER[PHP_SELF]",'class'=>get_class($this),'do'=>'listAll','sessionclass'=>$this->persistenceName);
					throw new DobleOSException('Error en la edición', 999, $datos);
				}
			}
			
			$key = strtolower(get_class($this));
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			$datos[operacion] = 'Edición';
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
			$this->oSystem->getDataTemplate()->addData($key, $datos);
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doNew()
		 */
		public function doNew(){
			$this->oSystem->getLogger()->debug( "Alta de datos" );
			$key = strtolower(get_class($this));
			$datos[operacion] = 'Alta';
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
			$this->oSystem->getDataTemplate()->addData($key, $datos);
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doDelete()
		 */
		public function doDelete(){
			$this->oSystem->getLogger()->debug( "Baja de datos" );
						
			$id = (int)$_REQUEST[id];
			
			/** Regla. si este iva esta en uso por algún artículo/servicio no se podría borrar */
			$sql = "SELECT art.articulo FROM articulos art WHERE art.id_iva = $id AND art.estado!='XXX' LIMIT 3;";
			$resultado = $this->computeSQL($sql, false);
			if ( $this->oSystem->getConnection()->hayResultados() ){
				$message = 'No es posible eliminar este IVA porque hay artículos que dependen de el como por ejemplo:';
				while ( $datos = $this->oSystem->getConnection()->getColumnas($resultado) )
					$message .= addslashes( "<li>$datos[articulo]</li>" );
				throw new DobleOSException($message,0,array("title"=>"Aviso de error","message"=>$message,"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
			} else {
				//marcamos como eliminados categoria y sus dependencias (familias)
				$adm= $this->oSystem->getUser()->getId();
				$sql = "UPDATE iva SET estado='XXX',fmodificacion=now(),id_administrador=$adm WHERE id = $id;";
				$this->computeSQL($sql, false);
			}
			
			header("Location: ".OS_WEB_PATH."?class=$_REQUEST[class]&do=listAll&sessionclass=$_REQUEST[sessionclass]");
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doUpdate()
		 */
		public function doUpdate(){
			$this->oSystem->getLogger()->debug( "Actualización de datos" );			
			
			$id = (int)$_REQUEST[id];
			$datos				= array();
			$datos[descripcion] 	= trim($_REQUEST[descripcion]);
			$datos[iva] 	= $_REQUEST[iva];
			$datos[estado] 			= $_REQUEST[estado];
			$datos[fmodificacion]	= date("Y-m-d H:i:s");
			$datos[id_administrador]= $this->oSystem->getUser()->getId();
			if ( !$id )
				$datos[falta] 			= date("Y-m-d H:i:s");
				
			if ($id > 0){
				while(list($key,$val)= each($datos)){
					if ($sqlUpdate) $sqlUpdate .= ',';
					$sqlUpdate .= "$key=\"".addslashes(trim($val))."\"";
				}
				$sql = "UPDATE iva SET $sqlUpdate WHERE id=$id";
			}else{		
				while(list($key,$val)= each($datos)){
					if ($sqlFields) {$sqlFields.=',';$sqlValues.=',';}
					$sqlFields .= "$key";
					$sqlValues .= "\"".addslashes(trim($val))."\"";
				}
				$sql = "INSERT INTO iva ($sqlFields) VALUES ($sqlValues)";
			}
			
			$this->computeSQL($sql, false);
						
			if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
				$message = 'Los datos no se han salvado. Detalles: '.addslashes($this->oSystem->getConnection()->getError());
				throw new DobleOSException($message,0,array("title"=>"Aviso de error","message"=>$message,"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
			}
			
			header("Location: ".OS_WEB_PATH."?class=$_REQUEST[class]&do=listAll&sessionclass=$_REQUEST[sessionclass]");
		}
		
		/**
		 * 
		 * exportacion de datos csv
		 */
		public function exportCSV(){
			parent::generalExportCSV( 'csv', array('Id','Categoria','Foto','Estado'), array('id','categoria','foto','estado'),$this->pathClass . '/csv.html' );
			return true;
		}
		
	}
?>