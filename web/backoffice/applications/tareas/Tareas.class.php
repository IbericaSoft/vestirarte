<?
/**
 * @author ...
 * @abstract Gestión de Tareas
 * @version 1.0 03.2012 creacion
 * @version 1.1 06.2012 Adaptación del módulo al nuevo modelo de recursos comunes
 * @version 1.2 09.2012 Permitimos accesos directos al alta. Anteriorme, al entrar en doEdit suponiamos que ibamos con un ID de tarea
 *  y lanzabamos error de registros inexistente. Ahora reemplazamos este comportamiento dirigiendo el flujo a doNew.
 * @version 1.3 Actualizacion JQuery 
 * 
 */
	
	require_once ( OS_ROOT . '/kernel/fckeditor/fckeditor.php');
	require_once ( OS_ROOT . '/kernel/dompdf/dompdf_config.inc.php');
	class Tareas extends Applications {
		
		public $VERSION = 'Version: 1.3 (02.2014)<br><br><i>Actualizacion JQuery</i><br><br><b>Dobleh Software 2014</b>';
		public $pagina;
		public $pathApp;
		public $isPesistance;
		public $filtroSQL;
		public $persistenceName;		
		public $PATHCLASS = '/applications/tareas';
		private $admin_cache = null;
		
		/**
		 * En el constructor de la clase recibimos el objeto DobleOS que nos permite disponer de objetos globales
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
		 * Datos externos, de otras tablas, que utiliza esta clase
		 * @see Applications::bindingsData()
		 */
		public function bindingsData(){
			if ( $this->admin_cache==null ){
				$this->oLogger->debug("cacheando administradores");
				$rows = array();
				$result = $this->computeSQL("SELECT id,nombre FROM os_administradores WHERE estado IN ('ACT') ORDER BY nombre;",null);
				while ( $row = $this->oSystem->getConnection()->getColumnas($result) ){
					array_push( $rows, array("id"=>$row['id'],"nombre"=>utf8_encode($row['nombre'])) );
				}
				$this->admin_cache = json_encode($rows);
			}
			$this->computeTemplate("admin_cache", $this->admin_cache, null);
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
			$this->filtroSQL = 'SELECT t.*,date_format(t.fecha_limite,"%d-%m-%Y") fecha_limite,date_format(t.fecha_fin,"%d-%m-%Y") fecha_fin, adm.nombre, adm.id_perfil rol FROM os_task t, os_administradores adm';
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
					array_push( $rows, array("id"=>$row[id],"nombre"=>utf8_encode($row['nombre']),"prioridad"=>utf8_encode($row['prioridad']),"descripcion"=>utf8_encode($row['descripcion']),"fecha_limite"=>utf8_encode($row['fecha_limite']),"fecha_fin"=>utf8_encode($row['fecha_fin']),"estado"=>$row[estado]) );
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
			
			//$user = $this->oSystem->getUser()->getId();
			$rol  = $this->oSystem->getUser()->getRol();

			/**los usuarios pueden ver las tareas cuyo rol del propietario de la tarea sea igual o inferior al suyo propio*/ 
			$this->filtroSQL .= " WHERE t.id_usuario_target=adm.id AND adm.id_perfil<=$rol";
			
			if ( $_REQUEST['_estado'] )
				$this->filtroSQL .= " AND t.estado='$_REQUEST[_estado]'";
				
			if ( $_REQUEST['_prioridad'] )
				$this->filtroSQL .= " AND t.prioridad='$_REQUEST[_prioridad]'";
				
			if ( $_REQUEST['_destinatario'] )
				$this->filtroSQL .= " AND t.id_usuario_target = '$_REQUEST[_destinatario]'";
			
			$this->filtroSQL .= " ORDER BY t.fecha_limite";
		}
		
			
		/**
		 * Edición de datos
		 * @see root/system/IApplications::doEdit()
		 */
		public function doEdit(){
			$sql = 'SELECT t.*,date_format(t.fecha_limite,"%d-%m-%Y") fecha_limite,date_format(t.fecha_fin,"%d-%m-%Y") fecha_fin,adm.nombre, adm.id_perfil rol FROM os_task t, os_administradores adm';
			$sql.= " WHERE t.id=$_REQUEST[id];"; 
			$resultado = $this->computeSQL($sql,false);
			if ( !$this->oSystem->getConnection()->hayResultados() ){
				//throw new DobleOSException("La tarea $id no existe!!!",111,array("title"=>"Aviso de error","message"=>"La tarea solicitada ¡no existe!","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"SQL no da resultados: $sql") );
				$this->doNew();
				return;
			}
			
			$this->bindingsData();	
			$key = strtolower(get_class($this));
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			$datos[operacion] = 'Edición';
			$template = $this->pathApp . '/edicion.html';
			if ( $datos[estado]=='FIN' ){
				$datos[operacion] = 'Lectura';
				$template = $this->pathApp . '/ver.html';
			}
			$this->computeTemplate($key, $datos, $template);
		}
		
		/**
		 * Nuevo registro
		 * @see root/system/IApplications::doNew()
		 */
		public function doNew(){
			$this->bindingsData();
			$key = strtolower(get_class($this));
			$datos[operacion] = 'Alta';
			$datos[estado] = 'ACT';
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doDelete()
		 */
		public function doDelete(){
			
		}
		
		/**
		 * Inserta o actualiza una tarea. En el caso de crear una tarea nueva, notifica también por email al receptor de la misma.
		 * (non-PHPdoc)
		 * @see IBackEnd::doUpdate()
		 */
		public function doUpdate(){
			$this->oSystem->getLogger()->debug( "Actualización de datos" );			
			$enviarEmail 				= false;
			$id 						= (int)$_REQUEST[id];
			$datos						= array();
			$datos[id_usuario] 			= $this->oSystem->getUser()->getId();
			$datos[id_usuario_target]	= $_REQUEST[id_usuario_target];			
			$datos[descripcion] 		= trim($_REQUEST[descripcion]);
			$datos[prioridad] 			= $_REQUEST[prioridad];
			$datos[tarea] 				= addslashes($_REQUEST[tarea]);
			$datos[fecha_limite] 		= substr($_REQUEST[fecha_limite], 6).'-'.substr($_REQUEST[fecha_limite], 3,2).'-'.substr($_REQUEST[fecha_limite], 0,2);
			$datos[estado] 				= $_REQUEST[estado];
			if ( !$id ) //una tarea nueva no puede tener estado FIN
				$datos[estado] = 'ACT';
			if ( $_REQUEST[estado]=='FIN' )
				$datos[fecha_fin] = date("Y-m-d H:i");
				
			if ($id > 0){
				while(list($key,$val)= each($datos)){
					if ($sqlUpdate) $sqlUpdate .= ',';
		   		$sqlUpdate .= "$key=\"$val\"";
				}
				$sql = "UPDATE os_task SET $sqlUpdate WHERE id=$id";
			}else{		
				while(list($key,$val)= each($datos)){
					if ($sqlFields) {$sqlFields.=',';$sqlValues.=',';}
		   		$sqlFields .= "$key";
		   		$sqlValues .= "\"$val\"";
				}
				$sql = "INSERT INTO os_task ($sqlFields) VALUES ($sqlValues)";
				$enviarEmail = true;
			}
			
			$this->computeSQL($sql, false);
						
			if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
				$this->oSystem->getLogger()->error( $this->oSystem->getConnection()->getError() );
				$message = 'Los datos no se han salvado. Detalles: '.addslashes($this->oSystem->getConnection()->getError());
				$this->oSystem->getDataTemplate()->addData('messages', array("msg"=>$message));
			}else{
				$message = 'Operación realizada con éxito';
				if ( $enviarEmail ){
					$datos = $this->oSystem->getConnection()->getColumnas($this->computeSQL("select email,nombre from os_administradores where id=$_REQUEST[id_usuario_target];",false));
					$email = $this->oSystem->getEmail();
					$exito = $email->enviar($datos[email], $datos[nombre], trim($_REQUEST[descripcion]), "<b>Esta es una notificación automática del sistema ".$this->oSystem->getConfigSystem()->getKeyData('app_title')."<br>Tienes tarea(s) pendiente(s).<br> Por favor, revisa tu cuenta para ver los detalles ;)</b>", null);
					if ( !$exito ){
						$this->oSystem->getLogger()->error( "Error enviando email: ".$email->getErrorEnvio() );
						$this->oSystem->getDataTemplate()->addData('messages', array("msg"=>'La tarea se ha creado pero el email de notificación al destinatario no ha sido entregado. '.$email->getErrorEnvio()));
					}
				}
			}
			
			$this->listAll();
		}
		
		/**
		 * Recopila las tareas del usuario para hacer una notificación en la ventana del cliente
		 */
		public function getUserTask(){
			$datos = array();
			$user = $this->oSystem->getUser()->getId();
			$sql="select count(id) total,prioridad from os_task where id_usuario_target=$user AND estado='ACT' GROUP BY prioridad;";
			$datos = $this->computeSQL($sql,false);
			$datosJson = array();
			$rows = array();
			$total = 0;
			$urgente = false;
			while ( $row = $this->oSystem->getConnection()->getColumnas($datos) ){
				$total = $row[total];
				if ( $row[prioridad]=='URGENTE')
					$urgente = true;
			}
			//		array_push( $rows, array("prioridad"=>$row[prioridad],"total"=>$row['total']));
			$row = $this->oSystem->getConnection()->getColumnas($datos);
			$datosJson["error"]="NO";
			$datosJson["total"]=$total;
			$datosJson["urgente"]=$urgente;
			$key = 'json';
			$template = '';			
			$this->oLogger->debug($template);
			$this->computeTemplate($key, json_encode($datosJson), $template);
		}
		
		/**
		 * Listado de las tareas que tiene el usuario. Se utiliza para la pantalla que el usuario ve al tratar sus tareas pendientes
		 */
		public function getListMyTask(){
			$this->oSystem->getLogger()->debug( "Listado de tareas del usuario" );
			$user = $this->oSystem->getUser()->getId();
			$sql = 'SELECT t.*,date_format(t.fecha_limite,"%d-%m-%Y") fecha_limite,date_format(t.fecha_fin,"%d-%m-%Y") fecha_fin,adm.nombre, adm.id_perfil rol FROM os_task t, os_administradores adm';
			$sql.= " WHERE t.id_usuario_target=adm.id AND t.id_usuario_target=$user AND t.estado='ACT' ";
			$sql.= "ORDER BY CASE prioridad WHEN 'URGENTE' THEN 1 WHEN 'ALTA' THEN 2 WHEN 'NORMAL' THEN 3 END, fecha_limite;"; 
			$datos = $this->computeSQL($sql,false);
			$key = strtolower(get_class($this));
			$template = $this->pathApp . '/listado_mistareas.html';
			$this->computeTemplate($key, $datos, $template);
		}
		
		/**
		 * Edicio de la tarea para comentar y cerrar
		 * @throws DobleOSException Se pueden dar tres situaciones de error. 
		 * 	1.Que la tarea no exista (tiene que ser una peticion mal intencionada desde URL preparada)
		 * 	2.Que la tarea este finalizada (es una situación de concurrencia no controlada pero es posible que mientras uno edita otra cierra la misma tarea)
		 * 	3.Que la tarea no sea propiedad del usuario (tiene que ser una peticion mal intencionada desde URL preparada) 
		 */
		public function doMinEdit(){
			$this->oSystem->getLogger()->debug( "Edición de la tarea para comentar y cerrar" );
			$rol = $this->oSystem->getUser()->getRol();
			$sql = 'SELECT t.*,date_format(t.fecha_limite,"%d-%m-%Y") fecha_limite,date_format(t.fecha_fin,"%d-%m-%Y %H:%m") fecha_fin,adm.nombre, adm.id_perfil rol FROM os_task t, os_administradores adm';
			$sql.= " WHERE t.id_usuario_target=adm.id AND t.id=$_REQUEST[id];"; 
			$resultado = $this->computeSQL($sql,false);
			if ( !$this->oSystem->getConnection()->hayResultados() )
				throw new DobleOSException("La tarea $_REQUEST[id] no existe!!!",111,array("title"=>"Aviso de error","message"=>"La tarea solicitada ($_REQUEST[id]) ¡no existe!","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"getListMyTask","report"=>"SQL no da resultados: $sql") );
				
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			if ( $datos[estado]=='FIN' )
				throw new DobleOSException("La tarea $id está finalizada!!!",111,array("title"=>"Aviso de error","message"=>"La tarea solicitada ($_REQUEST[id]) ¡está finalizada desde el: $datos[fecha_fin]!","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"getListMyTask","report"=>"SQL no da resultados: $sql") );
			
			if ( $datos[rol]<$rol )
				throw new DobleOSException("Tarea con rol $rol y el usuario tiene el rol $datos[rol]!!!",111,array("title"=>"Aviso de error","message"=>"No esta permitido cerrar tareas de usuarios cuyo rol sea distinto al suyo","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"getListMyTask","report"=>"Mi rol es $rol. La tarea es $_REQUEST[id]") );
				
			$key = strtolower(get_class($this));	
			$template = $this->pathApp . '/edicion_mistareas.html';
			$this->computeTemplate($key, $datos, $template);
		}
		
		/**
		 * Cerramos la tarea indicando el motivo y notificando via email en la cuenta del sistema
		 */
		public function doUpdateByEnd(){
			$datos						= array();
			$datos[comentarios] 		= addslashes(trim($_REQUEST[comentarios]));
			$datos[estado] 				= 'FIN';
			$datos[fecha_fin] 			= date("Y-m-d H:i");
			while(list($key,$val)= each($datos)){
				if ($sqlUpdate) $sqlUpdate .= ',';
		   		$sqlUpdate .= "$key=\"$val\"";
			}
			$sql = "UPDATE os_task SET $sqlUpdate WHERE id=$_REQUEST[id]";
			$this->computeSQL($sql, false);
						
			if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
				$this->oSystem->getLogger()->error( $this->oSystem->getConnection()->getError() );
				$message = 'Los datos no se han salvado. Detalles: '.addslashes($this->oSystem->getConnection()->getError());
				$this->oSystem->getDataTemplate()->addData('messages', array("msg"=>$message));
			}else{
				$message = 'Operación realizada con éxito';
			}
			
			$this->getListMyTask();
		}
		
		/**
		 * Generar fichero de datos filtrados CSV
		 * exportacion de datos csv
		 */
		public function exportCSV(){
			parent::generalExportCSV( 'csv', array('Id','Cliente','Razón','NIF/CIF','Email','Teléfono','Boletín','Dirección envío','Población envío','Provincia envío','Cpostal envío','Dirección facturación','Población facturación','Provincia facturación','Cpostal facturación','Observaciones','Estado'),
					array('id','cliente','razon_social','nifcif','email','telefono','boletin','env_dir','env_loc','env_pro','env_cp','fac_dir','fac_loc','fac_pro','fac_cp','observaciones','estado'),$this->pathClass . '/csv.html' );
			return true;
		}
		
		/**
		 * Generar fichero de datos filtrados PDF
		 */
		public function doListPrint(){
			parent::pdfGenericList(array('nombre','prioridad','descripcion','fecha_limite','fecha_fin'),"../_commons/css","Listado Tareas.pdf");
		}
		
	}
?>
