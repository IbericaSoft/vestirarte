<?
/**
 * @author ANANsoft
 * @abstract Gestión de valores de configuracion del sistema
 * @version 1.0 11.2011 creacion
 * @version 1.1 02.2012	se añade el filtro que permite mostrar los datos en funcion del perfil ADMIN o ROOT. se añaden estilos unificados
 * al html
 * @version 1.2 03.2012 en la tabla os_config aparece una nueva columna (rol) que nos indica quien puede visualizar ese registro (ADMIN o solo ROOT). 
 * 				Esto es necesario xq un ADMIN no debería poder editar ciertos datos de la configuracion.
 * @version 1.3 03.2012 Link para lanzar el módulo Appconfig que gestiona los parametros propios de la empresa (iva,gastos,paginacion,etc)
 * @version 1.4 05.2012 Fix. Errores varios al unificar módulos CSS y JS
 */
	
	require_once ( OS_ROOT . '/kernel/fckeditor/fckeditor.php');
	
	class Sistema extends Applications {
		
		public $VERSION = 'Version: 1.4 (05.2012)<br><br><i>Fix. Errores varios al unificar módulos CSS y JS</i><br><br><b>Dobleh Software 2012</b>';
		public $PATHCLASS = '/applications/sistema';
		const ADMIN = 2;
		const ROOT = 4;
		
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
			$this->filtroSQL = 'SELECT *,date_format(fecha,"%d-%m-%Y") fecha FROM os_config';
			$this->computeFilter();			
			$this->listAll();
		}
		
		/**
		 * listado de datos con el filtro actual
		 */
		public function listAll(){
			$this->bindingsData();
			$key = 'sistema';
			$datos = array();
			$datos = $this->computeSQL($this->filtroSQL,true);
			if ( $this->oSystem->getOrderActionClass()->getChannel()=='html' ){	
				$template = $this->pathApp . '/listado.html';
				
			} else {
				$datosJson = array("error"=>"NO","callBack"=>"");
				$rows = array();
				while ( $row = $this->oSystem->getConnection()->getColumnas($datos) )
					array_push( $rows, array("clave"=>$row['clave'],"valor"=>utf8_encode($row['valor']),"fecha"=>$row['fecha'],"descripcion"=>utf8_encode($row['descripcion']) ) );
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
			
			if ( $this->oSystem->getUser()->getRol()==self::ADMIN )
				$this->filtroSQL .= " WHERE rol <= ".self::ADMIN;
			
			if ( $this->oSystem->getUser()->getRol()==self::ROOT)
				$this->filtroSQL .= " WHERE rol <= ".self::ROOT;
				
			if ( $_REQUEST['clave'] )
				$this->filtroSQL .= " AND clave LIKE '%$_REQUEST[clave]%'";
				
			if ( $_REQUEST['descripcion'] )
				$this->filtroSQL .= " AND descripcion LIKE '%$_REQUEST[descripcion]%'";
		}
		
			
		/**
		 * Edición de datos
		 * @see root/system/IApplications::doEdit()
		 */
		public function doEdit(){
			$this->oSystem->getLogger()->debug( "Edición de datos" );
			$clave = $_REQUEST[id];
	
			$sql = "SELECT * FROM os_config WHERE clave='$clave';";
			$resultado = $this->computeSQL($sql, false);
			if ( !$this->oSystem->getConnection()->hayResultados() )				
				throw new DobleOSException("El registro $id no existe!!!",111,array("title"=>"Aviso de error","message"=>"El registro solicitado ¡no existe!. Se ha generado un alerta con el error pero sería de ayuda que comunique con el servicio técnico","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"SQL no da resultados: $sql") );
			
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
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
			$datos = array('titulo'=>'Error de llamada','mensaje'=>'No es posible crear parámetros de sistema desde esta herramienta','url'=>"$_SERVER[PHP_SELF]",'class'=>get_class($this),'do'=>'start','sessionclass'=>$this->persistenceName);
			throw new DobleOSException('Error de llamada', 999, $datos);
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doDelete()
		 */
		public function doDelete(){
			$datos = array('titulo'=>'Error de llamada','mensaje'=>'No es posible eliminar parámetros de sistema desde esta herramienta','url'=>"$_SERVER[PHP_SELF]",'class'=>get_class($this),'do'=>'start','sessionclass'=>$this->persistenceName);
			throw new DobleOSException('Error de llamada', 999, $datos);
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doUpdate()
		 */
		public function doUpdate(){
			$this->oSystem->getLogger()->debug( "Actualización de datos !!!" );			
			
			$clave = $_REQUEST[id];
			$datos				= array();
			$datos[valor] 		= trim($_REQUEST[valor]);
			$datos[descripcion] = trim(addslashes($_REQUEST[descripcion]));
			$datos[fecha]		= date("Y-m-d H:i:s");
			
			while(list($key,$val)= each($datos)){
				if ($sqlUpdate) $sqlUpdate .= ',';
		   		$sqlUpdate .= "$key=\"$val\"";
			}
			$sql = "UPDATE os_config SET $sqlUpdate WHERE clave='$clave'";
			
			
			$this->computeSQL($sql, false);
						
			if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
				$this->oSystem->getLogger()->error( $this->oSystem->getConnection()->getError() );
				$message = 'Los datos no se han salvado. Detalles: '.addslashes($this->oSystem->getConnection()->getError());
			}else{
				$message = 'Operación realizada con éxito';
			}
			
			/** cargamos las preferencias/configuracion del systema */
			$this->oSystem->setConfigSystem(new ConfigSystem( Utils_OS::getConfigSystem( $this->oSystem->getConnection() ) ));
			
			$this->listAll();
		}
		

	}
?>
