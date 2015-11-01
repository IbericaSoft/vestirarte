<?
/**
 * @author IbericaSoft
 * @abstract Gestión de familias
 * @version 1.0 11.2011 creacion
 * @version 1.1 02.2012 Mostramos en la ficha la pestaña de auditoria y nueva gestion de subida de ficheros
 * @version 2.0 07.2012 Nuevo modelo gestión de recursos (css/js)
 * @version 3.0 03.2015 Evolución grid sin paginación, filtros, listall, vista, edición, insert, actualización, preparestatement, executestament, transacciones ...
 */
	class Familias extends Applications {
		
		public $VERSION = 'Version: 3.0 (03.2015)<br><br><i>Evolución</i><br><br><b>IbericaSoft 2015</b>';
		public $PATHCLASS = '/applications/familias';
		public $id,$dir_images=null,$filtro_categoria,$filtro_familia,$filtro_campo="categoria",$filtro_orden="up",$filtro_pagina=1;
		
		/**
		 * Creación e inicialización del módulo
		 */
		public function __construct( DobleOS $system ){
			parent::__construct($system);			
			$this->dir_images = Utils_OS::getValueAPP($this->oSystem->getConnection() , 'IMAGE_ARTICULOS_DIR');
		}
		
		/**
		 * Recuperar de la sesion esta clase implica invocar este metodo, no se vuelve a construir
		 */
		public function setInstance( DobleOS $system ){			
			parent::setInstance($system);

			if ( isset($_REQUEST['id']) )
				$this->id 	= $_REQUEST['id'];
			if ( isset($_REQUEST['_categoria']) )
				$this->filtro_categoria = $_REQUEST['_categoria'];
			if ( isset($_REQUEST['_familia']) )
				$this->filtro_familia = $_REQUEST['_familia'];
			if ( $_REQUEST['filtro_campo'] )
				$this->filtro_campo = $_REQUEST['filtro_campo'];
			if ( $_REQUEST['filtro_orden'] )
				$this->filtro_orden = $_REQUEST['filtro_orden'];
			if ( $_REQUEST['filtro_pagina'] )
				$this->filtro_pagina= $_REQUEST['filtro_pagina'];
		}
		
		/**
		 * Persiste esta clase en sesión
		 */
		public function __destruct(){
			if ( $this->isPesistance )
				$_SESSION[$this->oSystem->getOrderActionClass()->getClassSession()] = serialize( $this );	
		}

		/**
		 * Datos de otros módulos
		 */
		public function bindingsData(){
			$rows = array();
			$result = $this->computeSQL("SELECT id,categoria FROM categorias WHERE estado NOT IN ('XXX');",null);
			while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
				array_push( $rows, array_map("utf8_encode", $row) );
			$this->categorias = json_encode($rows);
		}		
		
		public function start(){
			$this->computeTemplate(null, null, $this->pathApp . '/listado.html');
		}
		
		/**
		 * SQL posible a esta clase para buscar elementos
		 */
		public function filter(){
			$this->filtroSQL = "SELECT fam.*, cat.categoria, cat.estado catestado";
			$this->filtroSQL .= ",date_format(fam.fmodificacion,'%d-%m-%Y %H:%i:%s') ffmodificacion";
			$this->filtroSQL .= ",(select nombre from os_administradores adm where adm.id=fam.id_administrador) gestor";
			$this->filtroSQL .= ",(CASE fam.estado WHEN 'ON' THEN 'Online' WHEN 'OFF' THEN 'Offline' ELSE 'Elimado/a' END) festado";
			$this->filtroSQL .= " FROM categorias cat LEFT JOIN familias fam ON (cat.id=fam.id_categoria) WHERE fam.estado IN ('ON','OFF')";
			
			if ( $_REQUEST['_categoria'] )
				$this->filtroSQL .= " AND cat.categoria LIKE '%$this->filtro_categoria%'";
			
			if ( $_REQUEST['_familia'] )
				$this->filtroSQL .= " AND fam.familia LIKE '%$this->filtro_familia%'";
			
			if ( $this->filtro_campo )
				$this->filtroSQL .=" ORDER BY $this->filtro_campo".($this->filtro_orden=="up"?"":" desc");			
		}	
		
		/**
		 * Recuperacion de datos
		 */
		public function listAll(){
			$this->filter();
			$key = strtolower(get_class($this));
			$datos = array();
			$resultados = $this->computeSQL($this->filtroSQL,false);
			while ( $row = $this->oSystem->getConnection()->getColumnas($resultados) )
				array_push( $datos, array_map("utf8_encode", $row) );
				
			if ( $this->oSystem->getOrderActionClass()->getChannel()=='json' ){
				$key = "json";
				$datos = json_encode($datos);
			}
			$this->oSystem->getDataTemplate()->addData($key, $datos);
		}
		
		/**
		 * Nuevo
		 */
		public function doNew(){
			$key = strtolower(get_class($this));
			$datos[operacion] = 'Alta';
			$datos[fecha] = date("d-m-Y");
			$datos[estado] = 'ACT';
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
			$this->bindingsData();//para cargar las categorias
		}
		
		/**
		 * Vista de dados
		 */
		public function doView(){
			$general = $this->getDatos();
			$key = strtolower(get_class($this));
			$datos = $general['datos'];
			$datos[operacion] = 'Consulta';
			$template = $this->pathApp . '/vista.html';
			$this->computeTemplate($key, $datos, $template);
			$this->oSystem->getDataTemplate()->addData("detalles", $general['detalles']);
		}
		
		/**
		 * Edición de datos
		 */
		public function doModify(){
			$general = $this->getDatos();
			$key = strtolower(get_class($this));
			$datos = $general['datos'];
			$datos[operacion] = 'Edición';
			$template = $this->pathApp . '/edicion.html';
			$this->computeTemplate($key, $datos, $template);
			$this->oSystem->getDataTemplate()->addData("detalles", $general['detalles']);
			$this->bindingsData();//para cargar las categorias
		}
		
		/**
		 * Recoger los datos de este módulo
		 */
		private function getDatos(){
			//master
			$sql = "SELECT fam.*,(select nombre from os_administradores where id=fam.id_administrador) usuario, cat.categoria, cat.estado catestado
			,date_format(fam.fmodificacion,'%d-%m-%Y %H:%i:%s') ffmodificacion
			,(CASE fam.estado WHEN 'ON' THEN 'Online' WHEN 'OFF' THEN 'Offline' ELSE 'Elimado/a' END) festado
			FROM familias fam, categorias cat WHERE cat.id=fam.id_categoria AND fam.id=$this->id AND fam.estado IN ('ON','OFF');";
			$resultado1 = $this->computeSQL($sql,false);
			if ( !$this->oSystem->getConnection()->hayResultados() )
				throw new DobleOSException("",111,array("title"=>"Aviso de error","message"=>"El registro $this->categoria ¡no existe!<br><br>$sql","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"start","report"=>"SQL no da resultados: $sql") );
							
			//detail
			$sql= "";
			$resultado2 = null;
			return $resultados = array("datos"=>$this->oSystem->getConnection()->getColumnas($resultado1),"detalles"=>$resultado2);
		}
		
		/**
		 * Insercion de datos
		 */
		public function doInsert(){
			$datos = $this->prepareStatement();
			while(list($key,$val)= each($datos)){
				if ($sqlFields) {
					$sqlFields.=',';
					$sqlValues.=',';
				}
				$sqlFields .= "$key";
				$sqlValues .= "\"$val\"";
			}
			$sql = "INSERT INTO familias ($sqlFields) VALUES ($sqlValues)";
			$this->executeStatement($sql);
			header("Location: ?class=$_REQUEST[class]&sessionclass=$_REQUEST[sessionclass]&do=start");
		}
		
		/**
		 * Modificación de datos
		 */
		public function doUpdate(){
			$datos = $this->prepareStatement();
			while(list($key,$val)= each($datos)){
				if ($sqlUpdate)
					$sqlUpdate .= ',';
				if ( substr($val,0,1)=='@' )
					$val=substr($val,1);
				else
					$val = "\"$val\"";
				$sqlUpdate .= "$key=$val";
			}
			$sql = "UPDATE familias SET $sqlUpdate WHERE id='$this->id';";
			$this->executeStatement($sql);
			header("Location: ?class=$_REQUEST[class]&sessionclass=$_REQUEST[sessionclass]&do=start");
		}
		
		/**
		 * Datos que vienen en un formulario y que incluimos en un SQL
		 */
		private function prepareStatement(){
			$datos = array();
			$datos[id_categoria]= $_REQUEST[id_categoria];
			$datos[familia]		= trim(addslashes($_REQUEST[familia]));
			$datos[descripcion]	= trim(addslashes($_REQUEST[descripcion]));
			$datos[posicion] 	= $_REQUEST[posicion];
			$datos[estado] 		= $_REQUEST[estado];
			$datos[foto_1] 		= $_REQUEST[foto_1];
			if ( $_REQUEST[foto_2] )
				$datos[foto_2] 		= $_REQUEST[foto_2];
			if ( $_REQUEST[foto_3] )
				$datos[foto_3] 		= $_REQUEST[foto_3];
			/** Auditores */
			$datos[falta]			= date("Y-m-d H:i:s");
			$datos[fmodificacion]	= date("Y-m-d H:i:s");
			$datos[id_administrador]= $this->oSystem->getUser()->getId();
			return $datos;
		}
		
		/**
		 * Ejecución de SQLs bajo transacción
		 */
		private function executeStatement($sql){			
			try {
				$this->computeSQL("START TRANSACTION", false);
				$this->computeSQL($sql, false);
				if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) )
					throw new Exception('Error en los datos. Detalles: '.addslashes($this->oSystem->getConnection()->getError()));
				$this->computeSQL("COMMIT", false);
			} catch(Exception $e) {
				$this->computeSQL("ROLLBACK", false);
				$this->oSystem->getLogger()->error( $e->getMessage() );
				throw new DobleOSException($e->getMessage(),0,array("title"=>"Aviso de error","message"=>$e->getMessage(),"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"start","report"=>"") );
			}		
		}
			
		/**
		 * Baja de datos
		 */
		public function doDelete(){
			/** Si de esta familia depende alguna subfamilia activa no se puede borrar */
			$sql = "SELECT subfamilia FROM subfamilias WHERE id_familia=$this->id AND estado NOT IN ('XXX') LIMIT 5;";				
			$resultado = $this->computeSQL($sql, false);
			if ( $this->oSystem->getConnection()->hayResultados() ){
				$message = 'No es posible eliminar la familia porque hay subfamilias que dependen de ella';
				while ( $datos = $this->oSystem->getConnection()->getColumnas($resultado) )
					$message .= addslashes( "<li>$datos[subfamilia]</li>" );
				throw new DobleOSException($message,0,array("title"=>"Aviso de error","message"=>$message,"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"start") );
			}
			/** marcamos registro como eliminado */
			$adm= $this->oSystem->getUser()->getId();
			$sql = "UPDATE familias SET estado='XXX',fmodificacion=now(),id_administrador=$adm WHERE id=$this->id;";
			$this->executeStatement($sql);
			header("Location: ?class=$_REQUEST[class]&sessionclass=$_REQUEST[sessionclass]&do=start");
		}
		
		/**
		 * Generar fichero CSV
		 */
		public function exportCSV(){
			$this->filter();
			parent::generalExportCSV( 'csv', array('Id','Categoria','Familia','Posición','Descripción','Estado','Modificado','Gestor'),
					array('id','categoria','familia','posicion','descripcion','festado','ffmodificacion','gestor'),$this->pathClass . '/csv.html' );
		}
		
		/**
		 * Generar fichero PDF
		 */
		public function doListPrint(){
			$this->filter();
			parent::pdfGenericList(array('categoria','familia','festado'),"../_commons/css","Listado-Familias.pdf");
		}
	}
?>