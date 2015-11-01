<?
/**
 * @author IbericaSoft
 * @abstract Gestión de Articulos de tienda
 * @version 1.0 08.2012 Creación
 * @version 2.0 02.2014 Personalización L&M
 * @version 3.0 03.2015 Evolución
 */	
	class Articulos extends Applications {
		
		public $VERSION = 'Version: 3.0 (03.2015)<br><br><i>Evolución</i><br><br><b>IbericaSoft 2015</b>';
		public $PATHCLASS = '/applications/articulos';
		public $id,$autor_variedad,$codigo,$dir_images=null,$subfamilias,$variedades,$iva,$autor,$tallas,$filtro_categoria,$filtro_familia,$filtro_subfamilia,$filtro_articulo,$filtro_autor,$filtro_campo="categoria",$filtro_orden="up",$filtro_pagina=1;
		
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
			if ( isset($_REQUEST['id_autor']) )
				$this->autor_variedad = $_REQUEST['id_autor'];//para gestion de la variedad
			if ( isset($_REQUEST['codigo']) )
				$this->codigo = $_REQUEST['codigo'];//para gestion de la variedad
			
			if ( isset($_REQUEST['_articulo']) )
				$this->filtro_articulo = $_REQUEST['_articulo'];
			if ( isset($_REQUEST['_categoria']) )
				$this->filtro_categoria = $_REQUEST['_categoria'];
			if ( isset($_REQUEST['_familia']) )
				$this->filtro_familia = $_REQUEST['_familia'];
			if ( isset($_REQUEST['_subfamilia']) )
				$this->filtro_subfamilia = $_REQUEST['_subfamilia'];
			if ( isset($_REQUEST['_autor']) )
				$this->filtro_autor = $_REQUEST['_autor'];
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
			//categoria>familias>subfamilia
			$rows = array();
			$sql = "SELECT sfam.id, CONCAT(categoria,'/',familia,'/',subfamilia) subfamilia
				FROM subfamilias sfam LEFT JOIN familias fam ON (sfam.id_familia=fam.id) LEFT JOIN categorias cat ON (fam.id_categoria=cat.id)
				WHERE sfam.estado NOT IN ('XXX') ORDER BY subfamilia;";				
			$result = $this->computeSQL($sql,null);
			while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
				array_push( $rows, array_map("utf8_encode", $row) );
			$this->subfamilias = json_encode($rows);
			//ivas
			$rows = array();
			$result = $this->computeSQL("SELECT id, iva FROM iva WHERE estado IN ('ON');",null);
			while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
				array_push( $rows, array_map("utf8_encode", $row) );
			$this->iva = json_encode($rows);
			//autores
			$rows = array();
			$result = $this->computeSQL("SELECT id, autor FROM autores WHERE estado IN ('ON');",null);
			while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
				array_push( $rows, array_map("utf8_encode", $row) );
			$this->autor = json_encode($rows);
			//tallas
			$rows = array();
			$result = $this->computeSQL("SELECT id, talla FROM tallas WHERE estado IN ('ON');",null);
			while ( $row = $this->oSystem->getConnection()->getColumnas($result) )
				array_push( $rows, array_map("utf8_encode", $row) );
			$this->tallas = json_encode($rows);
		} 
		
		public function start(){
			$this->computeTemplate(null, null, $this->pathApp . '/listado.html');
		}
		
		/**
		 * SQL posible a esta clase para buscar elementos
		 */
		public function filter(){
			$this->filtroSQL = "SELECT art.*, cat.categoria, fam.familia, sfam.subfamilia, autor
				,date_format(art.fmodificacion,'%d-%m-%Y %H:%i:%s') ffmodificacion
				,format(art.precio,2,'de_DE') fprecio
				,format(art.oferta,2,'de_DE') foferta
				,(select nombre from os_administradores adm where adm.id=art.id_administrador) gestor
				,(CASE art.estado WHEN 'ON' THEN 'Online' WHEN 'OFF' THEN 'Offline' ELSE 'Elimado/a' END) festado				
				FROM articulos art
				LEFT JOIN subfamilias sfam ON art.id_subfamilia=sfam.id
				LEFT JOIN familias fam ON sfam.id_familia=fam.id
				LEFT JOIN categorias cat ON fam.id_categoria=cat.id
				LEFT JOIN autores aut ON art.id_autor=aut.id
				WHERE art.estado IN ('ON','OFF')";
						
			if ( $_REQUEST['_articulo'] )
				$this->filtroSQL .= " AND art.articulo LIKE '%$this->filtro_articulo%'";
			if ( $_REQUEST['_categoria'] )
				$this->filtroSQL .= " AND categoria LIKE '%$this->filtro_categoria%'";
			if ( $_REQUEST['_familia'] )
				$this->filtroSQL .= " AND familia LIKE '%$this->filtro_familia%'";
			if ( $_REQUEST['_subfamilia'] )
				$this->filtroSQL .= " AND subfamilia LIKE '%$this->filtro_subfamilia%'";
			if ( $_REQUEST['_autor'] )
				$this->filtroSQL .= " AND autor LIKE '%$this->filtro_autor%'";
			
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
			$this->bindingsData();//para cargar las categorias-familias
			$this->variedades = null;
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
			$this->variedades = $general['detalles'];
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
			$this->bindingsData();//para cargar las categorias-familias
		}
		
		/**
		 * Recoger los datos de este módulo
		 */
		private function getDatos(){
			//master
			$sql = "SELECT art.*,(select nombre from os_administradores where id=sfam.id_administrador) usuario, 
			CONCAT(categoria,'/',familia,'/',subfamilia) subfamilia
			,format(iva,2,'de_DE') fiva,format(peso,3,'de_DE') fpeso,format(precio,2,'de_DE') fprecio,format(oferta,2,'de_DE') foferta
			,autor
			,date_format(art.fmodificacion,'%d-%m-%Y %H:%i:%s') ffmodificacion
			,(CASE art.estado WHEN 'ON' THEN 'Online' WHEN 'OFF' THEN 'Offline' ELSE 'Elimado/a' END) festado 
			FROM articulos art
				LEFT JOIN iva i ON art.id_iva=i.id
				LEFT JOIN autores aut ON art.id_autor=aut.id
				LEFT JOIN subfamilias sfam ON art.id_subfamilia=sfam.id
				LEFT JOIN familias fam ON sfam.id_familia=fam.id
				LEFT JOIN categorias cat ON fam.id_categoria=cat.id
				WHERE art.estado IN ('ON','OFF') AND art.id=$this->id;";
			$resultado1 = $this->computeSQL($sql,false);
			if ( !$this->oSystem->getConnection()->hayResultados() )
				throw new DobleOSException("",111,array("title"=>"Aviso de error","message"=>"El registro $this->categoria ¡no existe!<br><br>$sql","type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"start","report"=>"SQL no da resultados: $sql") );
				
			//detail
			$sql= "SELECT a.*,t.talla FROM articulos_variedad a LEFT JOIN tallas t ON (a.id_talla=t.id) WHERE a.id_articulo=$this->id;";
			$resultado2 = $this->computeSQL($sql,false);
			$datos = array();
			while ( $row = $this->oSystem->getConnection()->getColumnas($resultado2) )
				array_push( $datos, array_map("utf8_encode", $row) );	
			return $resultados = array("datos"=>$this->oSystem->getConnection()->getColumnas($resultado1),"detalles"=>json_encode($datos));
		}
		
		/**
		 * Insercion de datos
		 */
		public function doInsert(){
			$datos = $this->prepareStatement();
			$datos['id'] = Utils_OS::getValueAPP($this->oSystem->getConnection() , 'CODIGO_ARTICULO');
			$datos[codigo]  = str_pad($datos['id'], 4,"0",STR_PAD_LEFT);
			Utils_OS::updateValueAPProl($this->oSystem->getConnection() , 'CODIGO_ARTICULO',($datos['id']+1),$this->oSystem->getUser()->getId());
			$this->id = $datos['id'];
			$this->codigo = $datos['codigo'];
			$this->autor_variedad = $datos['id_autor'];
			
			while(list($key,$val)= each($datos)){
				if ($sqlFields) {
					$sqlFields.=',';
					$sqlValues.=',';
				}
				$sqlFields .= "$key";
				$sqlValues .= "\"$val\"";
			}
			$sql = "INSERT INTO articulos ($sqlFields) VALUES ($sqlValues)";
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
			$sql = "UPDATE articulos SET $sqlUpdate WHERE id='$this->id';";
			$this->executeStatement($sql);
			header("Location: ?class=$_REQUEST[class]&sessionclass=$_REQUEST[sessionclass]&do=start");
		}
		
		/**
		 * Datos que vienen en un formulario y que incluimos en un SQL
		 */
		private function prepareStatement(){
			$datos = array();
			$datos[articulo]	= trim(addslashes($_REQUEST[articulo]));
			$datos[id_subfamilia]	= $_REQUEST[id_subfamilia];
			$datos[id_iva]		= $_REQUEST[id_iva];
			$datos[id_autor]	= $_REQUEST[id_autor];
			//codigo-> es calculado
			$datos[codigo_proveedor]= trim(addslashes($_REQUEST[codigo_proveedor]));			
			$datos[peso] 		= $_REQUEST[peso];
			$datos[precio] 		= $_REQUEST[precio];
			$datos[oferta] 		= ($_REQUEST[oferta]?$_REQUEST[oferta]:'0.00');
			$datos[vendible] 	= $_REQUEST[vendible];
			$datos[enlace] 		= $_REQUEST[enlace];
			$datos[estado] 		= $_REQUEST[estado];			
			$datos[descripcion]	= trim(addslashes($_REQUEST[descripcion]));
			$datos[foto_1] 		= $_REQUEST[foto_1];
			if ( $_REQUEST[foto_2] )
				$datos[foto_2] 		= $_REQUEST[foto_2];
			if ( $_REQUEST[foto_3] )
				$datos[foto_3] 		= $_REQUEST[foto_3];
			if ( $_REQUEST[foto_4] )
				$datos[foto_4] 		= $_REQUEST[foto_4];						
			/** Auditores */
			$datos[falta]			= date("Y-m-d H:i:s");
			$datos[fmodificacion]	= date("Y-m-d H:i:s");
			$datos[id_administrador]= $this->oSystem->getUser()->getId();
			//variedades-> se trata aparte
			return $datos;
		}
		
		/**
		 * Ejecución de SQLs bajo transacción
		 */
		private function executeStatement($sql,$varieties=true){
			try {
				$this->computeSQL("START TRANSACTION", false);
				$this->computeSQL($sql, false);
				if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) )
					throw new Exception('Error en los datos. Detalles: '.addslashes($this->oSystem->getConnection()->getError()));
				if ($varieties)
					$this->variety(); //variedades del articulo				
				$this->computeSQL("COMMIT", false);
			} catch(Exception $e) {
				$this->computeSQL("ROLLBACK", false);
				$this->oSystem->getLogger()->error( $e->getMessage() );
				throw new DobleOSException($e->getMessage(),0,array("title"=>"Aviso de error","message"=>$e->getMessage(),"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"start","report"=>"") );
			}
		}

		/**
		 * Inserta/actualiza las lineas de detalle (variedades)
		 */
		private function variety(){
			$this->oLogger->debug("tratando variedades");
			$detalles = utf8_encode($_REQUEST['lista_variedades']);
			$detalles = json_decode($detalles);
			$adm = $this->oSystem->getUser()->getId();
			
			if ( count($detalles)==0 )
				throw new Exception("No hay variedades");
			
			foreach ( $detalles as $detalle ){
				$sql = "SELECT * FROM articulos_variedad WHERE id_articulo=$this->id AND id_talla=$detalle->talla;";
				$this->computeSQL($sql, false);
				$codigo = str_pad($this->autor_variedad, 3,"0",STR_PAD_LEFT) . $this->codigo . str_pad($detalle->talla, 2,"0",STR_PAD_LEFT);
				
				if ( !$this->oSystem->getConnection()->hayResultados() ){				
					$sql = "INSERT INTO articulos_variedad (id_articulo,id_talla,codigo,stock,estado,falta,fmodificacion,id_administrador)
								VALUES ($this->id,$detalle->talla,'$codigo',$detalle->stock,'$detalle->estado',now(),now(),$adm);";
				} else {
					$sql = "UPDATE articulos_variedad SET stock=$detalle->stock,estado='$detalle->estado',codigo='$codigo',fmodificacion=now(),id_administrador=$adm
								WHERE id_articulo=$this->id AND id_talla=$detalle->talla;";
				}
				$this->computeSQL($sql, false);
				if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) )
					throw new Exception($this->oSystem->getConnection()->getError());
			}
		}
		/**
		 * Baja de datos
		 */
		public function doDelete(){
			/** Si de esta subfamilia depende algun articulo activo no se puede borrar */
			$sql = "SELECT * FROM pedidos_detalle FROM id_articulo=$this->id LIMIT 5;";
			$resultado = $this->computeSQL($sql, false);
			if ( $this->oSystem->getConnection()->hayResultados() ){
			$message = 'No es posible eliminar el artículo porque se ha vendido. Puede dejarlo en estado offline';
			while ( $datos = $this->oSystem->getConnection()->getColumnas($resultado) )
				$message .= addslashes( "<li>$datos[articulo]</li>" );
				throw new DobleOSException($message,0,array("title"=>"Aviso de error","message"=>$message,"type"=>"error","class"=>get_class($this),"sessionclass"=>$this->oSystem->getOrderActionClass()->getClassSession(),"do"=>"start") );
			}
			/** marcamos registro como eliminado */
			$adm= $this->oSystem->getUser()->getId();
			$sql = "UPDATE articulos SET estado='XXX',fmodificacion=now(),id_administrador=$adm WHERE id=$this->id;";
			$this->executeStatement($sql,false);
			header("Location: ?class=$_REQUEST[class]&sessionclass=$_REQUEST[sessionclass]&do=start");
		}
		
		/**
		* Generar fichero CSV
		*/
		public function exportCSV(){
			$this->filter();
			parent::generalExportCSV( 'csv', array('Id','Categoria','Familia','Subfamilia','Precio','Oferta','Posición','Descripción','Estado','Modificado','Gestor'),
				array('id','categoria','familia','subfamilia','fprecio','foferta','posicion','descripcion','festado','ffmodificacion','gestor'),$this->pathClass . '/csv.html' );
		}
		
		/**
		* Generar fichero PDF
		*/
		public function doListPrint(){
		 	$this->filter();
		 	parent::pdfGenericList(array('articulo','categoria','familia','subfamilia','autor','fprecio'),"../_commons/css","Listado-Artículos.pdf");
		 }		
	}
?>