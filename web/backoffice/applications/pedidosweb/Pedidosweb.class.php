<?
/**
 * @author Antonio Gamez
 * @abstract Gestión de pedidos web
 * @version 1.0 03-2012 creacion
 * @version 1.1 03.2012 La llama a updateValueAPP se cambia por updateValueAPProl para gestionar datos de auditoria
 * @version 1.2 11.2012 Modificaciones en el alta, en la obtención de los valores del estado, ...
 * @version 2.0 01.2015 Actualización del módulo
 */
	require_once ( OS_ROOT . '/kernel/dompdf/dompdf_config.inc.php');
	
	class Pedidosweb extends Applications {
		
		public $VERSION = 'Version: 2.0 (01.2014)<br><br><i>Nueva versión</i><br><br><b>Dobleh Software 2015</b>';
		public $PATHCLASS = '/applications/pedidosweb';
		
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
			//Estados de un pedido//
			$resultado = $this->computeSQL("DESCRIBE pedidos estado;");
			$rows = $this->oSystem->getConnection()->getColumnas($resultado);
			$row = explode(",",preg_replace("/^,/","",preg_replace("/,$/","",preg_replace("/PRESUPUESTO/","",preg_replace("/XXX/","",preg_replace("/'/","",preg_replace("/\)/","",preg_replace("/enum\(/","",$rows[Type]))))))));
			$this->oSystem->getDataTemplate()->addData('estados',$row);

		}
		
		/**
		 * Al lanzar la clase invocamos este metodo que no devuelve resultados, lo dejamos asi
		 * para obligar al usuario a usar filtros
		 * @see Applications::start()
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
			$this->filtroSQL = 'SELECT *, date_format(entidad_fecha,"%d-%m-%Y") fpedido, date_format(fenvio,"%d-%m-%Y") fenvio, cliente, email FROM pedidos WHERE';
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
				$this->filtroSQL .= " estado='$_REQUEST[_estado]'";
			else
				$this->filtroSQL .= " estado NOT IN ('XXX','PRESUPUESTO')";
			
			if ( $_REQUEST['_pedido'] )
				$this->filtroSQL .= " AND id LIKE '%$_REQUEST[_id]'";
			
			if ( $_REQUEST['_cliente'] )
				$this->filtroSQL .= " AND (cliente LIKE '%$_REQUEST[_cliente]%' OR razon LIKE '%$_REQUEST[_cliente]%')";
			
			if ( $_REQUEST['_nifcif'] )
				$this->filtroSQL .= " AND nifcif LIKE '%$_REQUEST[_nifcif]%'";
			   
			if ( $_REQUEST['_email'] )
				$this->filtroSQL .= " AND email LIKE '%$_REQUEST[_email]%'";
			   	
			if ( $_REQUEST['_desde'] ){
				$desde = substr($_REQUEST['_desde'], 6).'-'.substr($_REQUEST['_desde'], 3,2).'-'.substr($_REQUEST['_desde'], 0,2).' 00:00:01';
				$this->filtroSQL .= " AND entidad_fecha >= '$desde'";
			}
			
			if ( $_REQUEST['_hasta'] ){
				$desde = substr($_REQUEST['_hasta'], 6).'-'.substr($_REQUEST['_hasta'], 3,2).'-'.substr($_REQUEST['_hasta'], 0,2).' 23:59:59';
				$this->filtroSQL .= " AND entidad_fecha <= '$desde'";
			}
			
			$this->filtroSQL .= ' ORDER BY entidad_fecha desc, razon';
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
					array_push( $rows, array("id"=>$row[id],"cliente"=>utf8_encode($row['cliente']),"email"=>utf8_encode($row['email']),"fpedido"=>$row[fpedido],"fenvio"=>$row[fenvio],"estado"=>$row[estado]) );
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
		 * Ver los detalles del pedido
		 * @throws DobleOSException si no existe
		 */
		public function doEdit(){
			$sql = "SELECT ped.*, format(total_gastos,2,'de_DE') total_gastos, 
				format(iva,2,'de_DE') iva, 
				format(tiva,2,'de_DE') tiva,
				format(total_pedido,2,'de_DE') total_pedido,
				date_format(falta,'%d-%m-%Y %H:%i') falta, 
				date_format(fenvio,'%d-%m-%Y') fenvio,
				date_format(fmodificacion,'%d-%m-%Y %H:%i') fmodificacion, 
			(select nombre from os_administradores where id=id_administrador) usuario FROM pedidos ped WHERE id=$_REQUEST[id];";
			$resultado = $this->computeSQL($sql,false);
			if ( !$this->oSystem->getConnection()->hayResultados() )
				throw new DobleOSException("El registro $_REQUEST[id] no existe!!!",111,array("title"=>"Aviso de error","message"=>"El registro solicitado ¡no existe!","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll","report"=>"SQL no da resultados: $sql") );
			
			$this->bindingsData();
			$key = strtolower(get_class($this));
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			$datos[operacion] = 'Edición';
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
			$this->oSystem->getDataTemplate()->addData($key, $datos);
			
			/* detalles */
			$sql= "SELECT art.articulo, det.cantidad, FORMAT(det.precio,2,'de_DE') precio, FORMAT(det.iva,2,'de_DE') iva, FORMAT(det.subtotal,2,'de_DE') subtotal, FORMAT(det.total,2,'de_DE') total";
			$sql.=" FROM pedidos_detalle det";
			$sql.=" LEFT JOIN articulos art ON (id_articulo=art.id)";
			$sql.=" WHERE id_pedido=$_REQUEST[id];";
			$this->oSystem->getDataTemplate()->addData('detalles', $this->computeSQL($sql,false));			
		}
		
		/**
		 * Nuevo pedido. Podemos hacer nosotros los pedidos en lugar del cliente
		 */
		public function doNew(){
		}
		
		/**
		 * Borrar un pedido por un motivo extraordinario
		 */
		public function doDelete(){		
			$datos[fmodificacion]	= date("Y-m-d H:i:s");
			$admin = $this->oSystem->getUser()->getId();
			$message = 'Operación realizada con éxito</br></br>';
			$sql = "UPDATE pedidos SET estado='XXX',fmodificacion=now(),id_administrador=$admin,observaciones_internas=CONCAT(IFNULL(observaciones_internas,''),'¡Borrado manual bajo demanda!'), WHERE id = $id;";
			$this->computeSQL($sql, false);
			header("Location: ".OS_WEB_PATH."?class=$_REQUEST[class]&do=listAll&sessionclass=$_REQUEST[sessionclass]");
		}
		
		/**
		 */
		public function doUpdate(){		
			$id = (int)$_REQUEST[id];

			$datos = array();
			$datos[observaciones_internas] = $_REQUEST[observaciones_internas];
			if ( $_REQUEST[avanzar_estado] ){
				$datos[estado] = 'FINALIZADO';
				$datos[ffinalizado]	= date("Y-m-d H:i:s");
			}
			
			/** Auditores */
			$datos[fmodificacion]	= date("Y-m-d H:i:s");
			$datos[id_administrador]= $this->oSystem->getUser()->getId();
			
			while(list($key,$val)= each($datos)){
				if ($sqlUpdate) 
					$sqlUpdate .= ',';
		   		$sqlUpdate .= "$key=\"$val\"";
			}
			$sql = "UPDATE pedidos SET $sqlUpdate WHERE id=$id";			
			
			
			$this->computeSQL($sql, false);
						
			
			header("Location: ".OS_WEB_PATH."?class=$_REQUEST[class]&do=listAll&sessionclass=$_REQUEST[sessionclass]");
		}
		
		/**
		 * Generar fichero de datos filtrados CSV
		 */
		public function exportCSV(){
			parent::generalExportCSV( 'csv', array('Id','Cliente','Razón Social','NIF/CIF','Teléfono','Móvil','Email','Boletín','VIP','Dirección envío','Localidad envío','Provincia envío','Cpostal envío','País envío','Dirección factura','Localidad factura','Provincia factura','Cpostal factura','País factura','Observaciones','Cliente interno','Estado'), 
				array('id','cliente','razon_social','nifcif','telefono','movil','email','boletin','vip','envdir','envloc','envpro','envcp','envpais','facdir','facloc','facpro','faccp','facpais','observaciones','interno','estado'),$this->pathClass . '/csv.html' );
			return true;
		}	
		
		/**
		 * Listado pedidos PDF
		 */
		public function pdfGenericList(){
			parent::pdfGenericList(array('id','estado','cliente','fpedido','fenvio'),"../_commons/css","Listado Pedidos.pdf");
		}
		

		
		/** PDF para el almacén */
		public function pdfPrintAlmacen(){
			$sql = "SELECT ped.*, format(total_gastos,2,'de_DE') total_gastos, 
				format(iva,2,'de_DE') iva, 
				format(tiva,2,'de_DE') tiva,
				format(total_pedido,2,'de_DE') total_pedido,
				date_format(falta,'%d-%m-%Y %H:%i') falta, 
				date_format(fenvio,'%d-%m-%Y') fenvio,
				date_format(fmodificacion,'%d-%m-%Y %H:%i') fmodificacion, 
			(select nombre from os_administradores where id=id_administrador) usuario FROM pedidos ped WHERE id=$_REQUEST[id];";
			$resultado = $this->computeSQL($sql,false);
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			
			$html =  file_get_contents( OS_ROOT."/applications/pedidosweb/print_pedido.html") ;
			$html = str_replace("{WEB_PATH}",OS_ROOT,$html);
			$html = str_replace("{observaciones}",$_REQUEST[observaciones],$html);
			$fields = array('id','falta','fenvio','cliente','email','telefono','iva','tiva','total_pedido','total_gastos'
			,'entienda_direccion','direccion','poblacion','provincia','cpostal','pais','razon','nifcif'
			,'fdireccion','fpoblacion','fprovincia','fcpostal','fpais');			
			foreach ( $fields as $field )
				$html = str_replace('{'.$field.'}',htmlentities($datos[$field]),$html);
			
			
			/* detalles */
			$sql= "SELECT art.articulo, det.cantidad, FORMAT(det.precio,2,'de_DE') precio, FORMAT(det.iva,2,'de_DE') iva, FORMAT(det.subtotal,2,'de_DE') subtotal, FORMAT(det.total,2,'de_DE') total";
			$sql.=" FROM pedidos_detalle det";
			$sql.=" LEFT JOIN articulos art ON (id_articulo=art.id)";
			$sql.=" WHERE id_pedido=$_REQUEST[id];";
			$detalles = $this->computeSQL($sql,false);
			$fields = array('cantidad','articulo','precio','total','iva');
			ereg("(<!--LIST1)(.*)(LIST1-->)",$html,$reg);
			while ( $datos2 = $this->oSystem->getConnection()->getColumnas($detalles) ){
				foreach ( $fields as $field )
					$html = str_replace('{'.$field.'}',htmlentities($datos2[$field]),$html);
				$html = str_replace("<!--LIST1","",$html);
				$html = str_replace("LIST1-->","<!--NEXT-->",$html);
				$html = str_replace("<!--NEXT-->","\n$reg[1]$reg[2]$reg[3]\n",$html);
			}
			
			$dompdf = new DOMPDF();
			$dompdf->set_base_path ( "css" );
			$dompdf->load_html( $html );
			$dompdf->render();
			$dompdf->stream("Pedido_$datos[id].pdf");
			//echo $html;
			//exit;
		}
		
	}
?>