<?
/**
 * @author Dobleh Software. Antonio Gámez
 * @abstract Gestión de Administradores del sistema
 * @version 1.0 11.2011	creación
 * @version 1.1 02.2012 fix Fallo de seguridad que permitia a una URL malintencionada editar datos sin privilegios. 
 * se añaden estilos unificados al html (hoja css comun)
 * @version 1.2 02.2012 Nuevas funcionalidades. La ficha del administrador muestra sus preferencias: 
 * Su tema de ventana, su fondo de escritorios, los iconos que tiene y los procesos actuales
 * @version 1.3 03.2012 Preparamos el módulo para la compartición de CSS/JS/IMAGES
 * @version 1.4 03.2012 Nuevo perfil SYSTEM para ser usado en procesos propios del sistema
 * @version 1.5 03.2012	Se añade la funcionalidad popup que consiste en una mini ventana para buscar administradores/gestores desde otros módulos
 * @version 1.6 02.2014 Personalizacion L&M
 */
	require_once ( OS_ROOT . '/kernel/dompdf/dompdf_config.inc.php');
	class Administradores extends Applications {
		
		public $VERSION = 'Version: 1.6 (02.2014)<br><br><i>Personalizacion L&M</i><br><br><b>Dobleh Software 2014</b>';
		public $PATHCLASS = '/applications/administradores';
		public $list_cache = null;
		
		const DEFAULT_THEME = 1;
		const DEFAULT_WALLPAPER = 1;
		const USER = 1;
		const ROOT = 4;
		const SYSTEM=8;
		
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
				$result = $this->computeSQL("SELECT id,nombre FROM os_administradores WHERE estado NOT IN ('XXX');",null);
				while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
					array_push( $rows, array("id"=>$row['id'],"label"=>utf8_encode($row['nombre'])) );
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
			//$this->noFilter();
			$this->filter();
		}
		
		/**
		 * aplicamos un filtro para buscar datos
		 * @see root/system/Applications::filter()
		 */
		public function filter(){
			$this->pagina = 1;
			$rol = ( $this->oSystem->getUser()->getRol()==self::ROOT )?self::SYSTEM:$this->oSystem->getUser()->getRol();
			$this->filtroSQL = "SELECT * FROM os_administradores adm, os_administradores_perfiles rol WHERE adm.id_perfil=rol.perfil AND adm.id_perfil<=$rol";
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
					array_push( $rows, array("id"=>$row['id'],"nombre"=>utf8_encode($row['nombre']),"estado"=>$row['estado'],"desc_perfil"=>$row['desc_perfil'] ) );
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
				$this->filtroSQL .= " AND estado='$_REQUEST[_estado]'";
			else
				$this->filtroSQL .= " AND estado!='XXX'";
			
			$this->filtroSQL .= " AND id_perfil<".self::SYSTEM;
				
			if ( $_REQUEST['_nombre'] )
				$this->filtroSQL .= " AND nombre LIKE '%".utf8_decode($_REQUEST[_nombre])."%'";
			
			$this->filtroSQL .= " ORDER BY nombre";
		}

		/**
		 * Edición de datos
		 */
		public function doEdit(){
			$this->oSystem->getLogger()->debug( "Edición de datos" );
			$id = (int)$_REQUEST[id];
	
			$sql = "SELECT * FROM os_administradores WHERE id=$id AND estado!='XXX';";
			$resultado = $this->computeSQL($sql,false);
			if ( !$this->oSystem->getConnection()->hayResultados() )
				throw new DobleOSException("El registro $id no existe!!!",111,array("title"=>"Aviso de error","message"=>"El registro solicitado ¡no existe!","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"SQL no da resultados: $sql") );
			
			
			$key = strtolower(get_class($this));
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			
			if ( $this->oSystem->getUser()->getRol()==self::USER )
				if ( $this->oSystem->getUser()->getId()!=$datos[id] )
					throw new DobleOSException("No tienes privilegios para editar los datos de otros usuarios!!!",999,array("title"=>"Aviso de seguridad","message"=>"No tienes privilegios para editar los datos de otros usuarios!!!","type"=>"warn","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
			
			if ( $datos[id_perfil]==self::SYSTEM )
				throw new DobleOSException("Las cuentas de sistema no se puede editar!!!",999,array("title"=>"Aviso de seguridad","message"=>"Las cuentas de sistema no se puede editar!!!","type"=>"warn","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
			
			$datos[operacion] = 'Edición';
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
					
			$sql = "select the.theme, the.alias, pref.fecha";
			$sql.= " FROM os_preferences_user pref, os_themes the";
			$sql.= " WHERE pref.value=the.id AND pref.id_user=$id AND pref.property='theme';";
			$resultado = $this->computeSQL($sql,false);
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			$this->oSystem->getDataTemplate()->addData('theme',$datos);
			
			$sql = "select wa.wallpaper, wa.alias, pref.fecha";
			$sql.= " FROM os_preferences_user pref, os_wallpapers wa";
			$sql.= " WHERE pref.value=wa.id AND pref.id_user=$id AND pref.property='wallpaper';";
			$resultado = $this->computeSQL($sql,false);
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			$this->oSystem->getDataTemplate()->addData('wallpaper',$datos);
			
			$sql = "SELECT * FROM os_icons_user WHERE user_id=$id;";
			$resultado = $this->computeSQL($sql,false);
			$this->oSystem->getDataTemplate()->addData('iconos',$resultado);
			
			$sql = "SELECT *,date_format(date,'%d-%m-%Y %H:%i:%s') fecha FROM os_process_user WHERE user_id=$id;";
			$resultado = $this->computeSQL($sql,false);
			$this->oSystem->getDataTemplate()->addData('procesos',$resultado);
		}
		
		/**
		 * Nuevo registro
		 * @see root/system/IApplications::doNew()
		 */
		public function doNew() {
			$this->oSystem->getLogger()->debug( "Alta de datos. Rol:".$this->oSystem->getUser()->getRol() );
			if ( $this->oSystem->getUser()->getRol()==self::USER )
				throw new DobleOSException("No tienes privilegios para crear usuarios!!!",999,array("title"=>"Aviso de seguridad","message"=>"No tienes privilegios para crear usuarios!!!","type"=>"warn","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
				
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
			$this->oSystem->getLogger()->debug( "Baja de datos. Rol:".$this->oSystem->getUser()->getRol() );
			if ( $this->oSystem->getUser()->getRol()==self::USER )
				throw new DobleOSException("No tienes privilegios para eliminar usuarios!!!",999,array("title"=>"Aviso de seguridad","message"=>"No tienes privilegios para eliminar usuarios!!!","type"=>"warn","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
			
			$id 				= (int)$_REQUEST[id];
			$datos[estado] 		= 'XXX';
			$datos[fmodificacion]= date("Y-m-d H:i:s");
			
			while(list($key,$val)= each($datos)){
					if ($sqlUpdate) $sqlUpdate .= ',';
		   		$sqlUpdate .= "$key=\"$val\"";
			}
			$sql = "UPDATE os_administradores SET $sqlUpdate WHERE id=$id";
			
			$this->computeSQL($sql, false);
						
			if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
				$this->oSystem->getLogger()->error( $this->oSystem->getConnection()->getError() );
				$message = 'Los datos no se han salvado. Detalles: '.addslashes($this->oSystem->getConnection()->getError());
				$this->oSystem->getDataTemplate()->addData('messages', array("msg"=>$message));
			}else{
				$message = 'Operación realizada con éxito';
			}
			
			$this->listAll();
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doUpdate()
		 */
		public function doUpdate(){
			$this->oSystem->getLogger()->debug( "Actualización de datos" );			
			
			$id 				= (int)$_REQUEST[id];
			$datos				= array();
			$datos[nombre] 		= trim($_POST[nombre]);
			$datos[usuario] 	= trim($_POST[usuario]);
			$datos[password] 	= trim($_POST[password]);
			$datos[email] 		= trim($_POST[email]);
			$datos[id_perfil] 	= trim($_POST[id_perfil]);
			$datos[estado] 		= $_REQUEST[estado];
			$datos[fmodificacion]= date("Y-m-d H:i:s");
			
			if ($id > 0){
				while(list($key,$val)= each($datos)){
					if ($sqlUpdate) $sqlUpdate .= ',';
		   			$sqlUpdate .= "$key=\"$val\"";
				}
				$sql = "UPDATE os_administradores SET $sqlUpdate WHERE id=$id";
			}else{		
				$datos[id] = Utils_OS::getValueOS($this->oSystem->getConnection() , 'user_id');
				Utils_OS::updateValueOS($this->oSystem->getConnection(), 'user_id', ($datos[id]+1));
				while(list($key,$val)= each($datos)){
					if ($sqlFields) {$sqlFields.=',';$sqlValues.=',';}
		   		$sqlFields .= "$key";
		   		$sqlValues .= "\"$val\"";
				}
				$sql = "INSERT INTO os_administradores ($sqlFields) VALUES ($sqlValues)";
			}
			
			$this->computeSQL($sql, false);
						
			if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) )
				throw new DobleOSException(date("YmdHis")."==>".$this->oSystem->getConnection()->getError(),null,array("title"=>"Error","message"=>"La operación no se ha completado. El error ha quedado registrado con este identificador: ".date("YmdHis"),"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
			
			/* si el usuario es nuevo hay que darle de alta en la tabla os_preferences_user para que pueda tener preferencias */
			if ( !$id ){
				$sql = "INSERT INTO os_preferences_user (id_user,property,value,fecha) VALUES ($datos[id],'theme',".self::DEFAULT_THEME.",now())";
				$this->computeSQL($sql, false);
				$sql = "INSERT INTO os_preferences_user (id_user,property,value,fecha) VALUES ($datos[id],'wallpaper',".self::DEFAULT_WALLPAPER.",now())";
				$this->computeSQL($sql, false);
			}
								
			header("Location: ?class=$_REQUEST[class]&do=listAll&sessionclass=$_REQUEST[sessionclass]");
		}
		
		/**
		 * @see IBackEnd::exportCSV()
		 */
		public function exportCSV(){
			parent::generalExportCSV( 'csv', array('Id','Nombre','Usuario','Password','Perfil'), array('id','nombre','usuario','password','desc_perfil'),$this->pathClass . '/csv.html' );
			return true;
		}
		
		/**
		 * Generar fichero de datos filtrados PDF
		 */
		public function doListPrint(){
			parent::pdfGenericList(array('nombre','usuario','estado','desc_perfil'),"../_commons/css","Listado Administradores del sistema.pdf");
		}
		

	}
?>
