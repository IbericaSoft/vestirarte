<?
/**
 * @author Dobleh Software - Antonio Gámez
 * @abstract Gestión de Clientes
 * @version 1.0 01.2012 creacion
 * @version 1.1 03.2012 Preparamos el módulo para la compartición de CSS/JS/IMAGES
 * @version 1.2 03.2012 Se añade la funcionalidad popup que consiste una mini ventana para buscar clientes.
 * 			Necesita del metodo doEdit devuelva los datos en formato JSON con lo que ahora devuelve los datos como listAll que
 * 			soporta los dos formatos 
 * @version 1.3 04.2012 Dejamos de usar a nivel de módulo ciertas variables locales que subimos a la clase Applications
 * 		*Funcionalidad PDF en el listado y en la ficha
 * @version 1.4 05.2012 Fix. Al crear un link no guardaba la información correcta que permitia que se cargue el filtro según se abre la ventana
 * @version 1.5 09.2012 Nuevos campos tipo de cliente y comercial. El campo observaciones deja de ser un textarea enriquecido.
 * @version 2.0 01.2014 Evolución para DobleHgest. Intentamos convertirlo en un modulo mas generico
 * @version 2.0 02.2014 Personalizacion L&M
 */

	require_once ( OS_ROOT . '/kernel/dompdf/dompdf_config.inc.php');
	class Clientesweb extends Applications {
		
		public $VERSION = 'Version: 2.0 (02.2014)<br><br><i>Personalización L&M</i><br><br><b>Dobleh Software 2014</b>';
		public $PATHCLASS = '/applications/clientesweb';
		private $clientes_cache = null;//carga los clientes la primera vez
		private $emails_cache = null;//carga los emails clientes la primera vez
		private $cache_pais = null;//lista paises solo se carga al cargar el modulo
		private $cache_provincias = null;//lista codigos/provincia solo se carga al cargar el modulo
		
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
			//if ($this->clientes_cache == null ){
				$this->oLogger->debug("cacheando datos clientes y emails");
				$rows = array();
				$rows2 = array();
				$result = $this->computeSQL("SELECT id,nombre,email FROM clientes WHERE estado NOT IN ('XXX');",null);
				while ( $row = $this->oSystem->getConnection()->getColumnas($result) ){
					array_push( $rows, array("id"=>$row['id'],"label"=>utf8_encode($row['nombre'])) );
					array_push( $rows2, array("id"=>$row['id'],"label"=>utf8_encode($row['email'])) );
				}
				$this->clientes_cache = json_encode($rows);
				$this->emails_cache = json_encode($rows2);
			//}
			$this->computeTemplate("clientes_cache", $this->clientes_cache, null);
			$this->computeTemplate("emails_cache", $this->emails_cache, null);
			
			//if ($this->cache_pais == null ){
				$this->oLogger->debug("cacheando datos pais");
				$rows = array();
				$result = $this->computeSQL("SELECT id,long_name pais FROM pais WHERE estado IN ('ON') ORDER BY (CASE WHEN long_name='España' THEN 1 ELSE 2 END),pais;",null);
				while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
					array_push( $rows, array("id"=>$row['id'],"pais"=>utf8_encode($row['pais'])) );
				$this->cache_pais = json_encode($rows);
			//}
			$this->computeTemplate("cache_pais", $this->cache_pais, null);
			
			//if ($this->cache_provincias == null ){
				$this->oLogger->debug("cacheando cache_provincias");
				$rows = array();
				$result = $this->computeSQL("SELECT * FROM provincias ORDER BY id_pais,provincia;",null);
				while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
					array_push( $rows, array("id"=>$row['id'],"id_pais"=>utf8_encode($row['id_pais']),"provincia"=>utf8_encode($row['provincia'])) );
				$this->cache_provincias = json_encode($rows);
			//}
			$this->computeTemplate("cache_provincias", $this->cache_provincias, null);
			
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
			$this->filtroSQL = "SELECT p.long_name pais, pro.provincia, pf.long_name fpais, prof.provincia fprovincia, cli.* 
				FROM clientes cli LEFT JOIN pais p ON (cli.id_pais=p.id) LEFT JOIN provincias pro ON (cli.id_provincia=pro.id) 
				LEFT JOIN pais pf ON (cli.f_id_pais=pf.id) LEFT JOIN provincias prof ON (cli.f_id_provincia=prof.id) WHERE";
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
					array_push( $rows, array("id"=>$row[id],"nombre"=>utf8_encode($row['nombre']),"telefono"=>utf8_encode($row['telefono']),"email"=>utf8_encode($row['email']),"estado"=>$row[estado]) );
				$key = 'json';
				$template = '';
				$pagination = $this->oSystem->getConnection()->getPaginacionJSON();
				if ( $this->externQuery )
					$datosJson = $rows;
				else
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
				$this->filtroSQL .= " cli.estado='$_REQUEST[_estado]'";
			else
				$this->filtroSQL .= " cli.estado NOT IN ('XXX')";
			
			if ( $_REQUEST['_id_cliente'] )
				$this->filtroSQL .= " AND cli.id=\"$_REQUEST[_id_cliente]\"";
			
			if ( !$_REQUEST['_id_cliente'] && $_REQUEST['_cliente'] )
				$this->filtroSQL .= " AND (cli.nombre LIKE '%$_REQUEST[_cliente]%' OR cli.razon LIKE '%$_REQUEST[_cliente]%')";			
				
			if ( $_REQUEST['_email'] )
				$this->filtroSQL .= " AND cli.email LIKE '%$_REQUEST[_email]%'";
			
			if ( $_REQUEST['_telefono'] )
				$this->filtroSQL .= " AND telefono LIKE '%$_REQUEST[_telefono]%'";
				
			if ( $_REQUEST['_desde'] ){
				$desde = substr($_REQUEST['_desde'], 6).'-'.substr($_REQUEST['_desde'], 3,2).'-'.substr($_REQUEST['_desde'], 0,2);
				$this->filtroSQL .= " AND falta >= '$desde'";
			}
				
			if ( $_REQUEST['_hasta'] ){
				$desde = substr($_REQUEST['_hasta'], 6).'-'.substr($_REQUEST['_hasta'], 3,2).'-'.substr($_REQUEST['_hasta'], 0,2);
				$this->filtroSQL .= " AND falta <= '$desde'";
			}
				
			$this->filtroSQL .= ' ORDER BY cli.nombre';
				
		}
		
		/**
		 * Edición de datos 
		 */
		public function doEdit(){
			$id = (int)$_REQUEST[id];
	
			$sql = "SELECT *, date_format(falta,'%d-%m-%Y %H:%i:%s') falta, date_format(fmodificacion,'%d-%m-%Y %H:%i:%s') fmodificacion, (select nombre from os_administradores where id=id_administrador) usuario FROM clientes WHERE id=$id AND estado!='XXX';";
			$resultado = $this->computeSQL($sql, false);
			if ( !$this->oSystem->getConnection()->hayResultados() ){
				$datos = array('titulo'=>'Error en la edición','mensaje'=>"No encuentro el registro indicado $id",'url'=>"$_SERVER[PHP_SELF]",'class'=>get_class($this),'do'=>'listAll','sessionclass'=>$this->persistenceName);
				throw new DobleOSException('Error en la edición', 999, $datos);
			}
			
			/** lectura del registro */
			$datos = $this->oSystem->getConnection()->getColumnas($resultado);
			
			/** El canal d la petición cliente nos dice si montamos los datos de una forma u otra, con plantilla o sin ella */
			if ( $this->oSystem->getOrderActionClass()->getChannel()=='html' ){	
				$template = $this->pathApp . '/edicion.html';
				$key = strtolower(get_class($this));
				$datos[operacion] = 'Edición';
			} else {
				$rows = array();
				$datosJson = array("error"=>"NO","callBack"=>"");
				array_push( $rows, array("id"=>$datos[id],"nombre"=>utf8_encode($datos['nombre']),"nifcif"=>utf8_encode($datos['nifcif']),"email"=>utf8_encode($datos['email']),"telefonos"=>utf8_encode($datos['telefonos']),"estado"=>$datos[estado],"direccion"=>utf8_encode($datos['direccion']),"poblacion"=>utf8_encode($datos['poblacion']),"provincia"=>utf8_encode($datos['provincia']),"cpostal"=>utf8_encode($datos['cpostal']),"pais"=>utf8_encode($datos['pais'])) );
				$datosJson['callBack']="callBack(".json_encode($rows).")";
				$datos = json_encode( $datosJson );
				$key = 'json';
				$template = '';
			}
			
			$this->bindingsData();
			$this->computeTemplate($key, $datos, $template);
		}
		
		/**
		 * Alta de datos
		 */
		public function doNew(){
			$this->oSystem->getLogger()->debug( "Alta de datos" );
			$key = strtolower(get_class($this));
			$this->bindingsData();
			$datos[operacion] = 'Alta';
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);			
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doDelete()
		 */
		public function doDelete(){
			$id = (int)$_REQUEST[id];
			
			/** Regla 1. si de este cliente tiene alguna compra, no se puede borrar, pero lo dejamos inhabilitado */
			$sql = "SELECT id,pedido FROM pedidos WHERE estado IN ('ACT') AND id_cliente=$id LIMIT 1;";
			$admin = $this->oSystem->getUser()->getId();
			
			$resultado = $this->computeSQL($sql, false);
			if ( $this->oSystem->getConnection()->hayResultados() ){
				$sql = "UPDATE clientes SET estado='OFF',fmodificacion=now(),id_administrador=$admin WHERE id = $id";
				$this->computeSQL($sql, false);
				$message = 'El cliente no se puedo borrar porque tiene pedidos, pero ha quedado inhabilitado. Esto quiere decir que aunque lo tenemos en los listado, no se puede usar.';
				throw new DobleOSException($message,0,array("title"=>"Aviso","message"=>$message,"type"=>"warnnig","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
			} else {			
				$sql = "UPDATE clientes SET estado='XXX',fmodificacion=now(),id_administrador=$admin WHERE id = $id";
				$this->computeSQL($sql, false);
			}
			header("Location: ".OS_WEB_PATH."?class=$_REQUEST[class]&do=listAll&sessionclass=$_REQUEST[sessionclass]");
		}
		
		/**
		 * (non-PHPdoc)
		 * @see IBackEnd::doUpdate()
		 */
		public function doUpdate(){		
			$id = (int)$_REQUEST[id];
			$datos = array();
			$datos[nombre] 		= $_REQUEST[nombre];
			$datos[razon]= $_REQUEST[razon];
			$datos[nifcif] 		= $_REQUEST[nifcif];	
			$datos[telefono] 	= $_REQUEST[telefono];
			$datos[email] 		= $_REQUEST[email];
			$datos[password] 	= $_REQUEST[password];
			$datos[suscripcion]	= $_REQUEST[suscripcion]?$_REQUEST[suscripcion]:'N';
			$datos[estado] 		= $_REQUEST[estado];
			$datos[direccion] 	= $_REQUEST[direccion];
			$datos[poblacion] 	= $_REQUEST[poblacion];
			$datos[id_provincia]= $_REQUEST[id_provincia];
			$datos[cpostal] 	= $_REQUEST[cpostal];
			$datos[id_pais] 	= $_REQUEST[id_pais];
			$datos[fdireccion] 	= $_REQUEST[fdireccion];
			$datos[fpoblacion] 	= $_REQUEST[fpoblacion];
			$datos[f_id_provincia]= $_REQUEST[f_id_provincia];
			$datos[fcpostal] 	= $_REQUEST[fcpostal];
			$datos[f_id_pais] 	= $_REQUEST[f_id_pais];
			$datos[observaciones] 	= $_REQUEST[observaciones];
			/** auditoria */			
			$datos[fmodificacion]	= date("Y-m-d H:i:s");
			$datos[id_administrador]= $this->oSystem->getUser()->getId();
			if ( !$id )
				$datos[falta] 			= date("Y-m-d H:i:s");
			
			if ($id > 0){
				while(list($key,$val)= each($datos)){
					if ($sqlUpdate) $sqlUpdate .= ',';
		   			$sqlUpdate .= "$key=\"".addslashes(trim($val))."\"";
				}
				$sql = "UPDATE clientes SET $sqlUpdate WHERE id=$id";
			}else{
				$datos[id] = Utils_OS::getValueAPP($this->oSystem->getConnection() , 'CLIENT_ID');
				Utils_OS::updateValueAPProl($this->oSystem->getConnection(), 'CLIENT_ID', ($datos[id]+1),$this->oSystem->getUser()->getId());
				while(list($key,$val)= each($datos)){
					if ($sqlFields) {$sqlFields.=',';$sqlValues.=',';}
		   			$sqlFields .= "$key";
		   			$sqlValues .= "\"".addslashes(trim($val))."\"";
				}
				$sql = "INSERT INTO clientes ($sqlFields) VALUES ($sqlValues)";
			}			
			$this->computeSQL($sql, false);
						
			if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
				$message = 'Los datos no se han salvado. Detalles: '.addslashes($this->oSystem->getConnection()->getError());
				throw new DobleOSException($message,0,array("title"=>"Aviso de error","message"=>$message,"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"listAll") );
			}
			
			header("Location: ".OS_WEB_PATH."?class=$_REQUEST[class]&do=listAll&sessionclass=$_REQUEST[sessionclass]");
		}
		
		/**
		 * Generar fichero de datos filtrados CSV
		 * exportacion de datos csv
		 */
		public function exportCSV(){
			parent::generalExportCSV( 'csv', array('Id','Cliente','Razón','NIF/CIF','Email','Teléfono','Suscripciones','Dirección envío','Población envío','Provincia envío','Cpostal envío','País envío','Dirección facturación','Población facturación','Provincia facturación','Cpostal facturación','País facturación','Observaciones','Estado'), 
				array('id','nombre','razon','nifcif','email','telefono','suscripcion','direccion','poblacion','provincia','cpostal','pais','fdireccion','fpoblacion','fprovincia','fcpostal','fpais','observaciones','estado'),$this->pathClass . '/csv.html' );
			return true;
		}
		
		/**
		* Generar fichero de datos filtrados PDF
		*/
		public function doListPrint(){
			parent::pdfGenericList(array('nombre','email','telefono','nifcif'),"../_commons/css","Listado Clientes.pdf");
		}
		
		/**
		 * Ventana para buscar articulos. La usan otros módulos del sistema que puedean necesitar datos de este módulo.
		 * Si llega specialFilter, es un tipo de filtro para la busqueda concreto que la aplicacion cliente nos pasa.
		 */
		public function doMiniSearch(){
			parent::doMiniSearch();
		}
	}
?>