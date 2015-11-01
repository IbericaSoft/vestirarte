<?
/**
 * @author ...
 * @abstract Gestión de valores de configuracion de la empresa (APP_CONFIG)
 * @version 1.0 03.2012 creacion
 * @version 1.1 03.2012 Preparamos el módulo para la compartición de CSS/JS/IMAGES
 * @version 1.2 05.2012 Nuevos valores: <texto legal, lopd, mercantil>
 * @version 1.2.1 06.2012 Fix. Error al salvar datos con caracteres especiales que no eran escapados
 */
	
	

	class Appconfig extends Applications {
		
		public $VERSION = 'Version: 1.2.1 (06.2012)<br><br><i>Fix. Error al salvar datos con caracteres especiales que no eran escapados</i><br><br><b>Dobleh Software 2012</b>';
		public $pathApp;

		
		/**
		 * En el constructor de la clase recibimos el objeto DobleOS que nos permite disponer de acceso a la gestion el appconfig
		 * @param $system Es el objeto Session que nos pasan por 'referencia'
		 */
		public function __construct( DobleOS $system ){
			parent::__construct($system);
			$this->oLogger = Logger::getRootLogger();
			$this->pathApp = OS_ROOT.'/applications/appconfig';
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
			$this->encadenarOnload = null;
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
			$this->filtroSQL = 'SELECT *,date_format(fmodificacion,"%d-%m-%Y") fecha FROM app_config';
			$this->computeFilter();			
			$this->listAll();
		}
		
		/**
		 * listado de datos con el filtro actual
		 */
		public function listAll(){
			$this->bindingsData();
			$key = 'appconfig';
			$datos = array();
			$datos = $this->computeSQL($this->filtroSQL,true);
			if ( $this->oSystem->getOrderActionClass()->getChannel()=='html' ){	
				$template = $this->pathApp . '/listado.html';
				
			} else {
				$datosJson = array("error"=>"NO","callBack"=>"");
				$rows = array();
				while ( $row = $this->oSystem->getConnection()->getColumnas($datos) )
					array_push( $rows, array("clave"=>$row['clave'],"valor"=>htmlentities($row['valor']),"fmodificacion"=>$row['fmodificacion'],"descripcion"=>utf8_encode($row['descripcion']),"editable"=>$row['editable'] ) );
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
			$this->oSystem->getLogger()->debug( "Aplicando filtro " . get_class($this) );
		}
		
			
		/**
		 * Edición de datos
		 * @see root/system/IApplications::doEdit()
		 */
		public function doEdit(){
			$this->oSystem->getLogger()->debug( "Edición de datos" );
			$clave = $_REQUEST[id];
	
			$sql = "SELECT *,(select nombre from os_administradores where id=id_administrador) usuario FROM app_config WHERE clave='$clave';";
			$resultado = $this->computeSQL($sql, false);
			if ( !$this->oSystem->getConnection()->hayResultados() )				
				throw new DobleOSException("El registro $id no existe!!!",111,array("title"=>"Aviso de error","message"=>"El registro solicitado ¡no existe!. Se ha generado un alerta con el error pero sería de ayuda que comunique con el servicio técnico","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"SQL no da resultados: $sql") );
			
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			if ( $datos[editable]=='N' )	
				throw new DobleOSException("Este dato no se puede modificar!!!",222,array("title"=>"Aviso de seguridad","message"=>"Este dato no se puede modificar","type"=>"warn","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"Este dato de app_config no se puede modificar: $id") );						
			
			$key = strtolower(get_class($this));		
			$datos[operacion] = 'Edición';
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
			$this->oSystem->getDataTemplate()->addData($key, $datos);
		}
		
		/**
		 * Nuevo registro
		 * @see root/system/IApplications::doNew()
		 */
		public function doNew(){
			throw new DobleOSException("No es posible crear parámetros de app_config desde este módulo!!!",333,array("title"=>"Aviso de seguridad","message"=>"No es posible crear parámetros de app_config desde este módulo","type"=>"warn","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"No es posible crear parámetros de app_config desde este módulo") );
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doDelete()
		 */
		public function doDelete(){
			throw new DobleOSException("No es posible eliminar parámetros de app_config desde este módulo!!!",333,array("title"=>"Aviso de seguridad","message"=>"No es posible eliminar parámetros de app_config desde este módulo","type"=>"warn","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"No es posible eliminar parámetros de app_config desde este módulo") );
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doUpdate()
		 */
		public function doUpdate(){
			$this->oSystem->getLogger()->debug( "Actualización de datos" );			
			
			$clave = $_REQUEST[id];
			$datos				= array();
			$datos[valor] 		= trim(addslashes($_REQUEST[valor]));

			/** datos auditores */
			$datos[fmodificacion]	= date("Y-m-d H:i:s");
			$datos[id_administrador]= $this->oSystem->getUser()->getId();
			
			while(list($key,$val)= each($datos)){
				if ($sqlUpdate) $sqlUpdate .= ',';
		   		$sqlUpdate .= "$key=\"$val\"";
			}
			$sql = "UPDATE app_config SET $sqlUpdate WHERE clave='$clave'";
			
			
			$this->computeSQL($sql, false);
						
			if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
				$this->oSystem->getLogger()->error( $this->oSystem->getConnection()->getError() );
				$message = 'Los datos no se han salvado. Detalles: '.addslashes($this->oSystem->getConnection()->getError());
			}else{
				$message = 'Operación realizada con éxito';
			}
	
			$this->listAll();
		}
		
		/**
		 * @see IBackEnd::exportCSV()
		 */
		public function exportCSV(){
			
		}
	}
?>
