<?
/**
 * @author Dobleh Software
 * @abstract Gestión de proyectos
 * @version 1.0 01.2014 creacion
 */
	require_once ( OS_ROOT . '/kernel/fckeditor/fckeditor.php');
	require_once ( OS_ROOT . '/kernel/dompdf/dompdf_config.inc.php');
	
	
	class Proyectos extends Applications {
		
		public $VERSION = 'Version: 1.0 (01.2014)<br><br><i>Creación</i><br><br><b>Dobleh Software 2014</b>';
		public $PATHCLASS = '/applications/proyectos';
		private $cache_estados = null;
		
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
			/*Estados posibles de un proyecto... eliminado el estado de baja*/
			if ( $this->cache_estados==null ){
				$this->oLogger->debug("cacheando estados proyecto");			
				$sql = "SHOW COLUMNS FROM proyectos WHERE field = 'estado';";
				$resultado = $this->computeSQL($sql, false);
				$datos = $this->oSystem->getConnection()->getColumnas($resultado);
				$result = str_replace(array("enum('", "')", "''"), array('', '', "'"), $datos[Type]);
				$this->cache_estados = array_diff(explode("','", $result), array("XXX"));
			}			
			$this->oSystem->getDataTemplate()->addData('cache_estados', $this->cache_estados );
		}
		
		/**
		 * Al lanzar la clase invocamos este metodo que no devuelve resultados, lo dejamos asi
		 * para obligar al usuario a usar filtros
		 * @see Applications::start()
		 */
		public function start(){
			$this->oLogger->debug( "Inicio clase" );
			$this->noFilter();
			//$this->filter();
			$this->listAll();//en la carga inicial no hay filtro
		}
		
		/**
		 * aplicamos un filtro para buscar datos
		 * @see root/system/Applications::filter()
		 */
		public function filter(){
			/**
				SELECT LPAD(p.id,5,'0') proyecto, LOWER(p.estado) estado, DATE_FORMAT(p.finicio,'%d-%m-%Y') finicio, DATE_FORMAT(p.ffin,'%d-%m-%Y') ffin, DATEDIFF(ffin,finicio) duracion, 
					IFNULL(ptc.tiempo_consumido,0) consumidos, IFNULL(ptc.porcentaje_tiempo_consumido,0) porcentaje_consumido, pth.hitos, IFNULL(phc.hitos_completados,0) hitos_completados,
					ROUND((IFNULL(phc.hitos_completados,0)*100)/pth.hitos,2) porcentaje_completado,	
					cli.cliente, FORMAT(coste,2) coste, p.contactos, p.observaciones
				FROM proyectos p LEFT JOIN proyectos_hitos_completados phc ON (p.id=phc.id) LEFT JOIN proyectos_tiempo_consumido ptc ON (p.id=ptc.id) LEFT JOIN proyectos_total_hitos pth ON (p.id=pth.id_proyecto) LEFT JOIN clientes cli ON (p.id_cliente=cli.id)
				WHERE 
					p.id=ph.id_proyecto AND p.id_cliente=cli.id
				GROUP BY p.id;
			 */
			$this->pagina = 1;
			$this->filtroSQL = "SELECT LPAD(p.id,5,'0') proyecto, LOWER(p.estado) estado, DATE_FORMAT(p.finicio,'%d-%m-%Y') finicio, DATE_FORMAT(p.ffin,'%d-%m-%Y') ffin, DATEDIFF(ffin,finicio) duracion,"; 
			$this->filtroSQL.= " IFNULL(ptc.tiempo_consumido,0) consumido, IFNULL(ptc.porcentaje_tiempo_consumido,0) porcentaje_consumido, pth.hitos, IFNULL(phc.hitos_completados,0) hitos_completados,";
			$this->filtroSQL.= " ROUND((IFNULL(phc.hitos_completados,0)*100)/pth.hitos,2) porcentaje_completado,";
			$this->filtroSQL.= " cli.cliente, FORMAT(coste,2) coste";
			$this->filtroSQL.= " FROM proyectos p LEFT JOIN proyectos_hitos_completados phc ON (p.id=phc.id) LEFT JOIN proyectos_tiempo_consumido ptc ON (p.id=ptc.id) LEFT JOIN proyectos_total_hitos pth ON (p.id=pth.id_proyecto) LEFT JOIN clientes cli ON (p.id_cliente=cli.id)";
			$this->filtroSQL.= " WHERE";		
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
		
		public function computeFilter(){
			$this->oSystem->getLogger()->debug( "Aplicando filtro " . get_class($this) );
		
			if ( $_REQUEST['_estado'] )
				$this->filtroSQL .= " p.estado='$_REQUEST[_estado]'";
			else
				$this->filtroSQL .= " p.estado!='XXX'";
			
			if ( $_REQUEST['_cliente'] )
				$this->filtroSQL .= " AND (cli.cliente LIKE '%$_REQUEST[_cliente]%' OR cli.razon_social LIKE '%$_REQUEST[_cliente]%')";
			   
			if ( $_REQUEST['_proyecto'] )
				$this->filtroSQL .= " AND p.id LIKE '%$_REQUEST[_proyecto]'";		   	

			if ( $_REQUEST['_recientes'] )
				$this->filtroSQL .= " AND p.fmodificacion >= DATE_SUB(CURRENT_DATE(), INTERVAL $_REQUEST[_recientes] DAY)";	
			   	
			if ( $_REQUEST['_desde'] ){
				$desde = substr($_REQUEST['_desde'], 6).'-'.substr($_REQUEST['_desde'], 3,2).'-'.substr($_REQUEST['_desde'], 0,2);
				$this->filtroSQL .= " AND p.finicio >= '$desde'";
			}
			
			if ( $_REQUEST['_hasta'] ){
				$desde = substr($_REQUEST['_hasta'], 6).'-'.substr($_REQUEST['_hasta'], 3,2).'-'.substr($_REQUEST['_hasta'], 0,2);
				$this->filtroSQL .= " AND p.finicio <= '$desde'";
			}
			
			if ( $_REQUEST['_termina'] )
				$this->filtroSQL .= " AND (DATEDIFF(ffin,curdate())>=0&&DATEDIFF(ffin,curdate())<=".($_REQUEST[_termina]*7).")";
			
			if ( $_REQUEST['_fmodificacion'] )
				$this->filtroSQL .= " AND DATEDIFF(CURDATE(),p.fmodificacion) <= $_REQUEST[_fmodificacion]";
			
			
			if ( $_REQUEST[_filtro_especial] ){
				switch ( $_REQUEST[_filtro_especial] ){
					case 'solovalidos': 
						$this->filtroSQL .= " AND p.estado NOT IN ('XXX')";break;
					case 'encurso':
						$this->filtroSQL .= " AND p.estado IN ('EN EJECUCION')";break;
				}
			}
			
			$this->filtroSQL.= " GROUP BY p.id";
			if ( $_REQUEST['_porcentaje_completado'] )
				$this->filtroSQL.= " HAVING porcentaje_completado <= $_REQUEST[_porcentaje_completado]";
			
			$this->filtroSQL.= " ORDER BY p.falta desc";			
		}
		
		/**
		 * listado de datos con el filtro actual
		 */
		public function listAll(){
			$this->bindingsData();
			$key = strtolower(get_class($this));
			$template = $this->pathApp . '/listado.html';
			$datos = array();
			$datos = $this->computeSQL($this->filtroSQL, (($this->externQuery)?false:true) );
			if ( $this->oSystem->getOrderActionClass()->getChannel()=='json' ){	
				$datosJson = array("error"=>"NO","callBack"=>"");
				$rows = array();
				while ( $row = $this->oSystem->getConnection()->getColumnas($datos) )
					array_push( $rows, array("proyecto"=>$row[proyecto],"finicio"=>$row[finicio],"ffin"=>$row[ffin],"estado"=>$row[estado],"porcentaje_consumido"=>$row[porcentaje_consumido],"porcentaje_completado"=>$row[porcentaje_completado],"duracion"=>$row[duracion],"consumido"=>$row[consumido],"hitos"=>$row[hitos],"hitos_completados"=>$row[hitos_completados],"cliente"=>utf8_encode($row[cliente]),"coste"=>$row[coste])  );
				$key = 'json';
				$template = '';
				$pagination = $this->oSystem->getConnection()->getPaginacionJSON();
				$datosJson['callBack']="refreshList(".json_encode($pagination).",".json_encode($rows).")";
				$datos = json_encode( $datosJson );
			}
			$this->computeTemplate($key, $datos, $template);
		}
		
		/**
		 * 
		 * @see IBackEnd::doNew()
		 */
		public function doNew(){
			$this->oLogger->debug( "Alta de datos" );
			$this->bindingsData();			
			$datos[fproyecto] = '---';
			$datos[falta] = date("d-m-Y");
			$datos[estado] = 'PRESUPUESTO';
			$datos[condiciones] = Utils_OS::getValueAPP($this->oSystem->getConnection() , 'condiciones');
			$template = $this->pathApp . '/edicion.html';
			$key = strtolower(get_class($this));
			$this->computeTemplate($key, $datos, $template);
		}
		
		/** 
		 * @see IBackEnd::edit()
		 */
		public function doEdit(){
			$id = (int)$_REQUEST[id];
			
			return;
			
			$sql = "SELECT cli.*,concat(fijo,'  ',movil) telefonos, adm.nombre responsable, o.*, LPAD(o.id,4,'0') proyecto, ";
			$sql.= "date_format(finicio,'%d-%m-%Y') finicio, date_format(ffin,'%d-%m-%Y') ffin, date_format(o.falta,'%d-%m-%Y') falta, ";
			$sql.= "(select nombre from os_administradores where id=o.id_administrador) usuario, ";
			$sql.= "(abs(o.imponible) - round(ifnull((select sum(importe) from caja where id_pedido=o.id),0),2)) 'pendiente' ";
			$sql.= "FROM proyectos o, clientes cli, os_administradores adm WHERE o.id_cliente=cli.id AND o.id_responsable=adm.id AND o.id=$id;";
			$resultado = $this->computeSQL($sql,false);
			if ( !$this->oSystem->getConnection()->hayResultados() )
				throw new DobleOSException("El registro $id no existe!!!",111,array("title"=>"Aviso de error","message"=>"El registro solicitado ¡no existe!","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"SQL no da resultados: $sql") );
			
			$this->bindingsData();
			$this->aDetalles = array();	
			$key = strtolower(get_class($this));
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			$datos[operacion] = 'Edición';
			if ( $datos[forzar_saldado]=='S' )
				$datos[pendiente]='0.00';
				
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
			$this->oSystem->getDataTemplate()->addData($key, $datos);
			
			$this->oSystem->getDataTemplate()->addData('detalles', $this->detallesObra());
			
			/* operacion de caja de esta obra*/
			$sql = "SELECT importe,concepto,date_format(fecha,'%d-%m-%Y') fecha, ";
			$sql .= "	(case fpago ";
			$sql .= "	when 'CHE' then 'Cheque' ";
			$sql .= "	when 'TAR' then 'Tarjeta' ";
			$sql .= "	when 'EFE' then 'Efectivo' ";
			$sql .= "	when 'TA' then 'Tranferencia' ";
			$sql .= "	else 'Otros' end) 'fpago', ";
			$sql .= "	(case tipo ";
			$sql .= "	when 'COBRO' then concat('Recibí de:',origen) ";
			$sql .= "	when 'PAGO' then concat('Pago a:',origen) ";
			$sql .= "	else '' end) 'origen' ";
			$sql .= "FROM caja ";
			$sql .= "WHERE id_pedido = $id;";
			$resultado3 = $this->computeSQL($sql,false);
			$this->oSystem->getDataTemplate()->addData('caja', $resultado3);
			
			/* operacion de facturacion de esta obra*/
			$sql = "SELECT *,date_format(fecha,'%d-%m-%Y') fecha FROM facturas WHERE factura= (SELECT id_factura FROM facturas_detalles WHERE id_pedido=$id GROUP BY id_factura);";
			$resultado4 = $this->computeSQL($sql,false);
			$this->oSystem->getDataTemplate()->addData('facturacion', $resultado4);
		}
		
		/**
		 * Detalles del pedido de obra
		 */
		private function detallesObra(){
			$this->oLogger->debug( "detallesObra" );
			$sql= "SELECT det.*, LPAD(det.id_obra,4,'0') proyecto, art.articulo, art.codigo, art.precio_compra, iva"; 
			$sql.=" FROM proyectos_detalles det, articulos art, iva";
			$sql.=" WHERE det.id_articulo=art.id AND art.id_iva=iva.id AND det.id_obra=$_REQUEST[id] ORDER BY det.posicion;";
			$resultado = $this->computeSQL($sql,false);
			
			if ( $this->oSystem->getOrderActionClass()->getChannel()=='html' ||  $this->oSystem->getOrderActionClass()->getChannel()=='pdf'){
				$this->oLogger->debug( "formato html" );
				return $resultado;
			} else if ( $this->oSystem->getOrderActionClass()->getChannel()=='json' ){
				$this->oLogger->debug( "formato json" );
				$rows = array();
				while ( $row = $this->oSystem->getConnection()->getColumnas($resultado) ){
					array_push( $rows, array("id_obra"=>$row[id_obra],"proyecto"=>$row[proyecto],"cantidad"=>$row[cantidad],"id_articulo"=>$row[id_articulo],"articulo"=>utf8_encode($row[articulo]),"precio"=>$row[precio],"dto"=>$row[dto],"importe"=>$row[importe],"iva"=>$row[iva],"codigo"=>$row[codigo],"precio_compra"=>$row[precio_compra]) );
				}
				return json_encode($rows);
			} else {
				$this->oLogger->debug("Error, channel no esperado");
				return null;
			}
		}
		
		/**
		 * Peticion de los detalles del pedido de la obra
		 */
		public function doEditDetalles(){
			$this->oLogger->debug( "doEditDetalles" );
			$this->computeTemplate('json',  $this->detallesObra(), '/respuestaJSON');
		}
		
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doDelete()
		 */
		public function doDelete(){
			$this->oLogger->debug( "Baja de datos" );			
			
						
			/** Regla 1. solo los presupuestos se pueden borrar */
			
			
			
			if ( $_REQUEST[estado]!='PRESUPUESTO' ){
				$message = 'Solo los presupuestos se pueden dar de baja!!!';
				throw new DobleOSException($message,0,array("title"=>"Aviso de error","message"=>$message,"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"SQL: -") );
			} else {
				//podemos borrar el dato
				$datos[fmodificacion]	= date("Y-m-d H:i:s");
				$admin = $this->oSystem->getUser()->getId();
				$message = 'Operación realizada con éxito</br></br>';
				$sql = "UPDATE proyectos SET estado='XXX',fmodificacion=now(),id_administrador=$admin WHERE id = $_REQUEST[id]";
				$this->computeSQL($sql, false);
			}
			
			$this->listAll();
		}
		
		/**
		 * Almacenamos un detalle (una linea del pedido) en el array de detalles. Lo hemos inicializado al editar o en el alta.
		 */
		public function saveDetails(){
			$this->oSystem->getLogger()->debug( "Recibido detalles de Pedido: $_REQUEST[posicion]/$_REQUEST[id_articulo]/$_REQUEST[cantidad]/$_REQUEST[precio]/$_REQUEST[dto]/$_REQUEST[importe]" );
			$detalle = array("posicion"=>$_REQUEST[posicion],"id_articulo"=>$_REQUEST[id_articulo],"cantidad"=>$_REQUEST[cantidad],
				"precio"=>$_REQUEST[precio],"dto"=>$_REQUEST[dto],"importe"=>$_REQUEST[importe] );
			array_push( $this->aDetalles, $detalle );
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doUpdate()
		 */
		public function doUpdate(){
			$this->oSystem->getLogger()->debug( "Actualización de datos" );			
			
			$id = (int)$_REQUEST[id];
			$datos = array();
			$datos[id]					= $id;
			$datos[id_cliente] 			= addslashes( trim($_REQUEST[id_cliente]) 	);
			$datos[id_responsable] 		= addslashes( trim($_REQUEST[id_responsable]));
			$datos[telefono]			= addslashes( trim($_REQUEST[telefono]));
			$datos[contacto]			= addslashes( trim($_REQUEST[contacto]));
			$datos[historico]			= addslashes( trim($_REQUEST[historico]));
			$datos[finicio]				= substr($_REQUEST[finicio],6,4).'-'.substr($_REQUEST[finicio],3,2).'-'.substr($_REQUEST[finicio],0,2);
			$datos[ffin]				= substr($_REQUEST[ffin],6,4).'-'.substr($_REQUEST[ffin],3,2).'-'.substr($_REQUEST[ffin],0,2);
			$datos[condiciones]			= addslashes( trim($_REQUEST[condiciones]));
			
			$imponible = 0;
			foreach ( $this->aDetalles as $detalle ){
				$imponible+=$detalle[importe];
			}
			$datos[imponible]=$imponible;
			$datos[estado] 				= $_REQUEST[estado];
		
			
			/** Auditores */
			$datos[fmodificacion]	= date("Y-m-d H:i:s");
			$datos[id_administrador]= $this->oSystem->getUser()->getId();
			if ( !$id ){
				$datos[falta] 			= date("Y-m-d H:i:s");
				$datos[historico] = date("d-m-Y").":$_REQUEST[estado]";
			}
			
			if ($id > 0){
				while(list($key,$val)= each($datos)){
					if ($sqlUpdate) $sqlUpdate .= ',';
		   		$sqlUpdate .= "$key=\"$val\"";
				}
				$sql = "UPDATE proyectos SET $sqlUpdate WHERE id=$id";
			}else{
				$datos[id] = Utils_OS::getValueAPP($this->oSystem->getConnection() , 'pp_id');
				Utils_OS::updateValueAPProl($this->oSystem->getConnection(), 'pp_id', ($datos[id]+1),$this->oSystem->getUser()->getId());
				while(list($key,$val)= each($datos)){
					if ($sqlFields) {$sqlFields.=',';$sqlValues.=',';}
		   		$sqlFields .= "$key";
		   		$sqlValues .= "\"$val\"";
				}
				$sql = "INSERT INTO proyectos ($sqlFields) VALUES ($sqlValues)";
			}
			$this->computeSQL($sql, false);
						
			if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
				$this->oSystem->getLogger()->error( $this->oSystem->getConnection()->getError() );
				throw new DobleOSException("Los datos no se han salvado ver SQL: $sql",999,array("title"=>"Aviso de error","message"=>"Los datos no se han salvado. Los detalles son: ".$this->oSystem->getConnection()->getError(),"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"$sql") );
			}else{
				$message = 'Operación realizada con éxito';
				//ahora los detalles
				if ( $_REQUEST['estado']=='PRESUPUESTO' || $_REQUEST['estado']=='PROYECTO' || ($_REQUEST['estado']=='ABONO' AND $datos[id])) {
					$sql = "DELETE FROM proyectos_detalles WHERE id_obra=$datos[id];";
					$this->computeSQL($sql, false);
					foreach ( $this->aDetalles as $detalle ){
						$sql = "INSERT INTO proyectos_detalles (id_obra,id_articulo,posicion,cantidad,precio,dto,importe) 
							VALUES ($datos[id],$detalle[id_articulo],$detalle[posicion],$detalle[cantidad],$detalle[precio],$detalle[dto],$detalle[importe]);";
						$this->computeSQL($sql, false);
					}
				}
			}
			
			switch ( $_REQUEST[estado] ){
				case 'PRESUPUESTO':
					header("Location: /?class=$_REQUEST[class]&do=doHacerPagoCobro&sessionclass=$_REQUEST[sessionclass]&id=$datos[id]");
					break;
				default:
					header("Location: /?class=$_REQUEST[class]&do=listAll&sessionclass=$_REQUEST[sessionclass]");
			}
				
			
		}
		
		/** Actualiza unos pocos datos del pedido */
		public function doMiniUpdate(){			
			$datos = array();
			$datos[id]				= $_REQUEST[id];
			$datos[historico]	= addslashes( trim($_REQUEST[historico]));
			$datos[condiciones]	= addslashes( trim($_REQUEST[condiciones]));
			
			/** Auditores */
			$datos[fmodificacion]	= date("Y-m-d H:i:s");
			$datos[id_administrador]= $this->oSystem->getUser()->getId();
			
			while(list($key,$val)= each($datos)){
				if ($sqlUpdate) $sqlUpdate .= ',';
		   		if ( substr($val,0,1)=='@' )
		   			$val=substr($val,1);
		   		else
		   			$val = "\"$val\"";
				$sqlUpdate .= "$key=$val";
			}
			$sql = "UPDATE proyectos SET $sqlUpdate WHERE id='$datos[id]';";
			$this->computeSQL($sql, false);
			
			header("Location: /?class=$_REQUEST[class]&do=listAll&sessionclass=$_REQUEST[sessionclass]");
		}
		
		/** a los datos dependiento de su estado les tenemos que cambiar algun dato que no hemos conseguido desde el SQL */
		private function tratamientosEspeciales(&$datos){
			/** tratamientos especiales */
				if ( $datos[estado]=='PRESUPUESTO') $datos[pendiente]='0.00';
				if ( ($datos[estado]=='FINALIZADO' || $datos[estado]=='PROYECTO') && $datos[pendiente]<0 )
						$datos[pendiente]='0.00';
				if ( $datos[estado]=='ABONO' && $datos[pendiente]>0 )
						$datos[pendiente]='0.00';
				if ( $datos[forzar_saldado]=='S' )
					$datos[pendiente]='0.00';
		}
		
		/**
		 * seleccionamos todas las proyectos en marcha y presupuestos con menos de 15 días
		 * y tomamos su fechas de proyectos para pintar un grafico de gantt
		 */
		public function doWorkDates(){
			/*Fechas de primera y ultima proyectos del gantt*/
			$sql= "SELECT curdate() inicio, max(ffin) fin FROM proyectos WHERE (estado='PROYECTO' AND ffin>=now());";
			$resultado = $this->computeSQL($sql,false);
			$gantt = $this->oSystem->getConnection()->getColumnas($resultado);
			$this->oSystem->getDataTemplate()->addData('gantt', $gantt);
			
			/* detalles */
			$sql = "SELECT LPAD(id,4,'0') obra, lower(estado) estado, finicio, ffin, To_days( ffin ) - TO_DAYS( finicio ) + 1 duracion FROM proyectos WHERE (estado='PRESUPUESTO' AND ffin>=now()) or (estado='PROYECTO' AND ffin>=now()) ORDER BY finicio;";
			$resultado = $this->computeSQL($sql,false);
			$this->oSystem->getDataTemplate()->addData('proyectos', $resultado);
			
			$template = $this->pathApp . '/ganttObras.html';
			$this->computeTemplate($key, null, $template);	
		}
		
		/**
		* Ventana para buscar clientes. La usan otros módulos del sistema que puedean necesitar datos de este módulo
		*/
		public function doMiniSearch(){
			$this->isPesistance = false;
			$template = $this->pathApp . '/popupListado.html';
			$this->computeTemplate(null, null, $template);
		}
		
		public function doHacerPagoCobro(){
			$template = $this->pathApp . '/pagosYcobros.html';
			$this->computeTemplate(null, null, $template);
		}
		
		/** cambia el estado del pedido */
		public function doChangeStatus(){
			$this->oSystem->getLogger()->debug( "doChangeStatus" );			

			$datos = array();
			$datos[id]					= $_POST[id];
			$datos[estado] 				= $_POST[estado];
			$datos[historico]			= "@concat(historico,\",".date("Y-m-d").":$_POST[estado]\")";
			
			/** Auditores */
			$datos[fmodificacion]	= date("Y-m-d H:i:s");
			$datos[id_administrador]= $this->oSystem->getUser()->getId();
			
			while(list($key,$val)= each($datos)){
				if ($sqlUpdate) $sqlUpdate .= ',';
		   		if ( substr($val,0,1)=='@' )
		   			$val=substr($val,1);
		   		else
		   			$val = "\"$val\"";
				$sqlUpdate .= "$key=$val";
			}
			$sql = "UPDATE proyectos SET $sqlUpdate WHERE id=$datos[id];";
			$this->computeSQL($sql, false);
			header("Location: /?class=$_POST[class]&do=listAll&sessionclass=$_POST[sessionclass]");
		}
		
		/** Salda un pedido */
		public function doPendiente(){
			$datos = array();
			$datos[id]					= $_POST[id];
			$datos[forzar_saldado] 		= 'S';
			$datos[historico]			= "@concat(historico,\" ".date("Y-m-d H:s")." Se salda de forma manual.\")";
				
			/** Auditores */
			$datos[fmodificacion]	= date("Y-m-d H:i:s");
			$datos[id_administrador]= $this->oSystem->getUser()->getId();
				
			while(list($key,$val)= each($datos)){
				if ($sqlUpdate) $sqlUpdate .= ',';
				if ( substr($val,0,1)=='@' )
				$val=substr($val,1);
				else
				$val = "\"$val\"";
				$sqlUpdate .= "$key=$val";
			}
			$sql = "UPDATE proyectos SET $sqlUpdate WHERE id=$datos[id];";
			$this->computeSQL($sql, false);
			header("Location: /?class=$_POST[class]&do=listAll&sessionclass=$_POST[sessionclass]");
		}
		
		public function doChangeToAbono(){
			$this->oSystem->getLogger()->debug( "Edición de datos para un ABONO" );
			$id = $_REQUEST[id];
			
			$sql = "SELECT cli.*,concat(fijo,'  ',movil) telefonos, adm.nombre responsable, o.*, LPAD(o.id,4,'0') proyecto, ";
			$sql.= "date_format(finicio,'%d-%m-%Y') finicio, date_format(ffin,'%d-%m-%Y') ffin, ";
			$sql.= "(select nombre from os_administradores where id=o.id_administrador) usuario, ";
			$sql.= "(abs(o.imponible) - round(ifnull((select sum(importe) from caja where id_pedido=o.id),0),2)) 'pendiente' ";
			$sql.= "FROM proyectos o, clientes cli, os_administradores adm WHERE o.id_cliente=cli.id AND o.id_responsable=adm.id AND o.id=$id;";
			$resultado = $this->computeSQL($sql,false);
			if ( !$this->oSystem->getConnection()->hayResultados() )
				throw new DobleOSException("El registro $id no existe!!!",111,array("title"=>"Aviso de error","message"=>"El registro solicitado ¡no existe!","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"SQL no da resultados: $sql") );
			
			$this->bindingsData();
			$this->aDetalles = array();	
			$key = strtolower(get_class($this));
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			$datos[operacion] = 'Edición';
			$datos[estado]='ABONO';
			$datos[id]=0;
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
			$this->oSystem->getDataTemplate()->addData($key, $datos);
			
			/* detalles */
			$sql= "SELECT det.*,(det.cantidad*-1) cantidad, art.articulo, art.codigo"; 
			$sql.=" FROM proyectos_detalles det, articulos art";
			$sql.=" WHERE det.id_articulo=art.id AND det.id_obra=$id ORDER BY det.posicion;";
			$resultado2 = $this->computeSQL($sql,false);
			$this->oSystem->getDataTemplate()->addData('detalles', $resultado2);
		}
		
		/**
		 * Generar fichero de datos filtrados CSV
		 */
		public function exportCSV(){
			parent::generalExportCSV( 'csv', array('Proyecto','Inicio','Fin','Estado','Duracion días','Consumido días', '% Consumido', 'Hitos','Hitos completos','% Completado','Coste','Cliente'),
					array('proyecto','finicio','ffin','estado','duracion','consumido', 'porcentaje_consumido','hitos','hitos_completados','porcentaje_completado','cliente','coste'),$this->pathClass . '/csv.html' );
			return true;
		}	
		
		/**
		 * Generar fichero de datos filtrados PDF
		 */
		public function doListPrint(){
			parent::pdfGenericList(array('proyecto','finicio','ffin','estado','@pporcentaje_completado','hitos_completados','hitos','@pporcentaje_consumido','cliente','@ccoste'),"../_commons/css","Listado Proyectos.pdf");
		}
			
		/** PDF para el pedido */
		public function pedidoPDF(){
			$this->oSystem->getLogger()->debug( "pedidoPDF" );
			
			/** datos */
			$id = $_REQUEST[id];
			$sql = "SELECT cli.*,concat(fijo,'/',movil) telefonos, adm.nombre responsable, o.*, LPAD(o.id,4,'0') proyecto, ";
			$sql.= "date_format(finicio,'%d-%m-%Y') finicio, date_format(ffin,'%d-%m-%Y') ffin, date_format(o.falta,'%d-%m-%Y') falta, ";
			$sql.= "(select nombre from os_administradores where id=o.id_administrador) usuario, ";
			$sql.= "(abs(o.imponible) - round(ifnull((select sum(importe) from caja where id_pedido=o.id),0),2)) 'pendiente' ";
			$sql.= "FROM proyectos o, clientes cli, os_administradores adm WHERE o.id_cliente=cli.id AND o.id_responsable=adm.id AND o.id=$id;";
			$resultado = $this->computeSQL($sql,false);
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			$estado = $datos[estado];
			$proyecto = $datos[proyecto];
			$html =  file_get_contents( OS_ROOT."/applications/proyectos/print_pedido.html") ;
			$html = str_replace("{WEB_PATH}",OS_ROOT,$html);
			$html = str_replace("{observaciones}",$_REQUEST[observaciones],$html);
			
			$fields = array('telefono','contacto','@ncondiciones','estado','proyecto','falta','cliente','razon_social','responsable','finicio','ffin','@cimponible');
			
			$this->tratamientosEspeciales(&$datos);
						
			foreach ( $fields as $field ){
					$format = substr($field, 0, 2);
					switch ( $format ){
							case '@n'://no format
								$field = substr($field, 2);								
								break;
							case '@p'://porcen
								$field = substr($field, 2);
								$datos[$field] = number_format($datos[$field],2,',','.')." %";
								break;
							case '@c'://currency
								$field = substr($field, 2);
								$datos[$field] = number_format($datos[$field],2,',','.')." &#0128;";
								break;
							default://sin formatos
								$datos[$field] = htmlentities( $datos[$field] );
					}				
					$html = str_replace('{'.$field.'}',$datos[$field],$html);
			}
			
			/** detalles */
			//$color = "#CDCDCD";
			$detalles = $this->detallesObra();
			$fields = array('cantidad','codigo','articulo','@cprecio','@pdto','@cimporte');
			ereg("(<!--LIST1)(.*)(LIST1-->)",$html,$reg);
			while ( $datos = $this->oSystem->getConnection()->getColumnas($detalles) ){
				
				if ( $color=="#EEEEEE" )
						$color="#D3D3D3";
				else
						$color="#EEEEEE";
				$html = str_replace('{color}',"$color",$html);
					
				foreach ( $fields as $field ){
					$format = substr($field, 0, 2);
					switch ( $format ){
							case '@n'://no format
								$field = substr($field, 2);								
								break;
							case '@p'://porcen
								$field = substr($field, 2);
								$datos[$field] = number_format($datos[$field],2,',','.')." %";
								break;
							case '@c'://currency
								$field = substr($field, 2);
								$datos[$field] = number_format($datos[$field],2,',','.')." &#0128;";
								break;
							default://sin formatos
								$datos[$field] = htmlentities( $datos[$field] );
					}				
					$html = str_replace('{'.$field.'}',$datos[$field],$html);
				}
			
				$html = str_replace("<!--LIST1","",$html);
				$html = str_replace("LIST1-->","<!--NEXT-->",$html);
				$html = str_replace("<!--NEXT-->","\n$reg[1]$reg[2]$reg[3]\n",$html);
			}
			
			//presupuesto-aceptacion
			if ( $estado=='PRESUPUESTO')
				$html = str_replace("{aceptacion}","Para la aceptaci&oacute;n del presupuesto es necesario firmar todas las hojas del documento<br>",$html);
			else
				$html = str_replace("{aceptacion}","",$html);
			
			$dompdf = new DOMPDF();
			$dompdf->set_base_path ( "../_commons/css" );
			$dompdf->load_html( $html );
			$dompdf->render();
			$dompdf->stream("$estado $proyecto.pdf");
		}
		
		/** marcar el pedido como liquidado */
		public function liquidarPedido($pedido,$observaciones){
			$this->isPesistance = false;
			$this->computeSQL("UPDATE proyectos SET liquidado='S',observaciones=concat(observaciones,'\n','$observaciones') WHERE id=$pedido;", false);
		}
	}
?>