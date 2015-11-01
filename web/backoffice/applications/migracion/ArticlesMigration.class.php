<?
class ArticlesMigration extends Applications {
	
	public function __destruct(){}
	public function computeFilter(){}
	public function bindingsData(){}
	public function start(){}
	public function __construct(DobleOS $system){parent::__construct($system);$this->oLogger = Logger::getRootLogger();}
	
	/**
	 * 
	 * @param SimpleXMLElement $xml
	 * @throws Exception
	 */
	public function import(SimpleXMLElement $xml,$log){
		
		$haveErrors = false;
		
		/** log de la migracion */
		$fp = fopen($log, 'w');

		/** el usuario decide vaciar la tabla antes de comenzar la migración */ 
		if ( $_REQUEST[truncate]=='S' ){
			$this->computeSQL("delete from articulos;", false);
			fwrite($fp, date("H:i:s")." DELETE from articulos;". PHP_EOL );
		}
		
		foreach ($xml->item as $article){
			try {				
				$this->oLogger->debug("<<<<<<<<< Procesando ARTICULO >>>>>>>>>>>>");
				$this->computeSQL("START TRANSACTION;", false);
				$insert = ($article->id)?false:true;//modo de trabajo, insertar o actualizar
				$datos = array();
				$sqlUpdate = '';
				$sqlFields = '';
				$sqlValues = '';
				
				/** Un nuevo articulo tiene que estar ubicado en alguna tienda */
				if ( $insert && count($article->tiendas)==0 ){
					$msg = "El articulo no se puede tratar porque no esta vinculado a tiendas";
					throw new Exception($msg);
				}
							
				/** Un nuevo articulo tiene que tener peso */
				if ( $insert && $article->peso==0 ){
					$msg = "El articulo no tiene peso";
					throw new Exception($msg);
				}
				
							
				/** comprobación de seguridad. si nos dicen que es update porque llevamos ID, comprobamos que exista y si no, lo cambiamos a insert */
				if ( !$insert ){
					$result = $this->computeSQL("SELECT id FROM articulos WHERE id=$article->id;", false);
					$this->oLogger->debug($this->oSystem->getConnection()->hayResultados());
					if ( !$this->oSystem->getConnection()->hayResultados() ){
						$insert=!$insert;
						fwrite($fp, "Articulo $article->id lo trataremos como ALTA". PHP_EOL );
					}
				}
				
				if ( $article->articulo )
					$datos[articulo] 		= utf8_decode($article->articulo);
				if ( $article->stock )
					$datos[stock]			= $article->stock;
				if ( $article->iva )
					$datos[id_iva]			= $article->iva;
				if ( $article->subfamilia )
					$datos[id_subfamilia]	= $article->subfamilia;
				if ( $article->codigo )
					$datos[codigo] 			= $article->codigo;
				if ( $article->codigo_proveedor )
					$datos[codigo_proveedor]= $article->codigo_proveedor;
				if ( $article->proveedor )
					$datos[id_proveedor] 	= $article->proveedor;
				if ( $article->peso )
					$datos[peso] 			= $article->peso;
				if ( $article->peso_interno )
					$datos[peso_interno]	= $article->peso_interno;
				if ( $article->alto )
					$datos[alto]			= $article->alto;
				if ( $article->ancho )
					$datos[ancho]			= $article->ancho;
				if ( $article->fondo )
					$datos[fondo]			= $article->fondo;
				if ( $article->estado )
					$datos[estado] 			= $article->estado;
				if ( $article->caracteristicas )
					$datos[caracteristicas]	= utf8_decode($article->caracteristicas);
				if ( $article->foto_1 )
					$datos[foto_1]			= utf8_decode($article->foto_1);
				if ( $article->foto_2 )
					$datos[foto_2]			= utf8_decode($article->foto_2);
				if ( $article->foto_3 )
					$datos[foto_3]			= utf8_decode($article->foto_3);
				if ( $article->fichero_1 )
					$datos[fichero_1]		= utf8_decode($article->fichero_1);
				if ( $article->fichero_2 )
					$datos[fichero_2]		= utf8_decode($article->fichero_2);
				if ( $article->fichero_3 )
					$datos[fichero_3]		= utf8_decode($article->fichero_3);
				/** auditoria */
				$datos[fmodificacion]	= date("Y-m-d H:i:s");
				$datos[id_administrador]= $this->oSystem->getUser()->getId();
				if ( $insert )
					$datos[falta] 			= date("Y-m-d H:i:s");

				if ($insert){
					/** cogemos el ultimo ID de articulo para sumarle uno y obtener el nuevo ID de articulo */
					/** esto es debido a que la migración puede crear ID desde fuera de este sistema, asi que no nos vale una secuencia almacenada en esta bbdd */
					list($datos[id]) = ($this->oSystem->getConnection()->getColumnas( $this->computeSQL("SELECT max(id)+1 id FROM articulos;", false) ));
					if ( !$datos[id] ) $datos[id]=1;//la primera vez no hay registros
					while(list($key,$val)= each($datos)){
						if ($sqlFields) {$sqlFields.=',';$sqlValues.=',';}
						$sqlFields .= "$key";
						$sqlValues .= "\"".addslashes(trim($val))."\"";
					}
					$sql = "INSERT INTO articulos ($sqlFields) VALUES ($sqlValues)";
				}else{
					$datos[id]=$article->id;
					while(list($key,$val)= each($datos)){
						if ($sqlUpdate) $sqlUpdate .= ',';
						$sqlUpdate .= "$key=\"".addslashes(trim($val))."\"";
					}
					$sql = "UPDATE articulos SET $sqlUpdate WHERE id=$datos[id]";
				}				
				$this->computeSQL($sql, false);
				fwrite($fp, date("H:i:s ").$sql. PHP_EOL );
				
				/** comprobamos si los datos se han registrado bien */
				if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
					$msg = "El articulo $article->codigo  ha devuelto un error SQL::".$this->oSystem->getConnection()->getError();					
					throw new Exception($msg);
				}		
				fwrite($fp, date("H:i:s ")."SQL OK". PHP_EOL );
				
				/** vinculo con precios y tiendas */
				if ($article->tiendas){
					$this->computeSQL("DELETE FROM articulos_tienda WHERE id_articulo=$datos[id];", false);
					fwrite($fp, date("H:i:s ")."DELETE FROM articulos_tienda WHERE id_articulo=$datos[id];". PHP_EOL );
					foreach ($article->tiendas as $shop){
						$datos2 = array();
						$sqlFields = '';
						$sqlValues = '';
						$datos2[tienda] = $shop->tienda;
						$datos2[id_articulo] = $datos[id];
						if ( $shop->precio )
							$datos2[precio]	= $shop->precio;
						if ( $shop->oferta )
							$datos2[oferta]	= $shop->oferta;
						if ( $shop->liquidacion )
							$datos2[liquidacion] = $shop->liquidacion;
						while(list($key,$val)= each($datos2)){
							if ($sqlFields) {$sqlFields.=',';$sqlValues.=',';}
							$sqlFields .= "$key";
							$sqlValues .= "\"".addslashes(trim($val))."\"";
						}
						$sql = "INSERT INTO articulos_tienda ($sqlFields) VALUES ($sqlValues)";					
						$this->computeSQL($sql, false);
						fwrite($fp, date("H:i:s ").$sql. PHP_EOL );
						if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
							$msg = "Los precios del articulo $article->articulo han devuelto un error SQL::".$this->oSystem->getConnection()->getError();							
							throw new Exception($msg);
						}
						fwrite($fp, date("H:i:s ")."SQL OK". PHP_EOL );
					}
				}
				
				/** vinculo con equivalentes y relacionados */
				if ($article->vinculados){
					$this->computeSQL("DELETE FROM articulos_vinculados WHERE id_articulo=$datos[id];", false);
					fwrite($fp, date("H:i:s ")."DELETE FROM articulos_vinculados WHERE id_articulo=$datos[id];". PHP_EOL );
					foreach ($article->vinculados as $vinculo){
						$datos3 = array();
						$sqlFields = '';
						$sqlValues = '';
						$datos3[id_articulo] = $datos[id];
						$datos3[id_articulo_vinculo] = $vinculo->vinculo;
						$datos3[tipo] = $vinculo->tipo;
						
						while(list($key,$val)= each($datos3)){
							if ($sqlFields) {$sqlFields.=',';$sqlValues.=',';}
							$sqlFields .= "$key";
							$sqlValues .= "\"".addslashes(trim($val))."\"";
						}
						$sql = "INSERT INTO articulos_vinculados ($sqlFields) VALUES ($sqlValues)";
						$this->computeSQL($sql, false);
						fwrite($fp, date("H:i:s ").$sql. PHP_EOL );
						if ( !$this->oSystem->getConnection()->lastQueryIsOK(1) ){
							$msg = "Los vinculos del articulo $article->articulo han devuelto un error SQL::".$this->oSystem->getConnection()->getError();
							throw new Exception($msg);
						}
						fwrite($fp, date("H:i:s ")."SQL OK". PHP_EOL );
					}
				}
				
				
								
				$this->computeSQL("COMMIT;", false);
				fwrite($fp, date("H:i:s")." COMMIT". PHP_EOL );
			} catch (Exception $e){
				$this->oLogger->error($e->getMessage());
				$this->computeSQL("ROLLBACK;", false);
				fwrite($fp, date("H:i:s ").$e->getMessage(). PHP_EOL );
				fwrite($fp, date("H:i:s ")."ROLLBACK". PHP_EOL );
				$haveErrors = true;
			}
		}
		fclose($fp);
		return $haveErrors;
	}
	
	/**
	 * 
	 */
	public function export($file){
		$sql = "select * from articulos where estado!='XXX';";
		$result = $this->computeSQL($sql, false);
		$xml = new SimpleXMLExtended('<articulos/>');			
		while ( $row = $this->oSystem->getConnection()->getColumnas($result) ){
			$item = $xml->addChild("item");
			$item->addChild("id",$row[id]);
			$item->addChild("articulo")->addCDATA(utf8_encode($row[articulo]));
			$item->addChild("caracteristicas")->addCDATA(utf8_encode($row[caracteristicas]));
			$item->addChild("subfamilia",$row[id_subfamilia]);
			$item->addChild("proveedor",$row[id_proveedor]);
			$item->addChild("iva",$row[id_iva]);
			$item->addChild("peso",$row[peso]);
			$item->addChild("peso_interno",$row[peso_interno]);
			$item->addChild("alto",$row[alto]);
			$item->addChild("ancho",$row[ancho]);
			$item->addChild("fondo",$row[fondo]);
			$item->addChild("codigo",$row[codigo]);
			$item->addChild("codigo_proveedor",$row[codigo_proveedor]);
			$item->addChild("stock",$row[stock]);
			$item->addChild("estado",$row[estado]);			
			$item->addChild("foto_1",$row[foto_1]);
			$item->addChild("foto_2",$row[foto_2]);
			$item->addChild("foto_3",$row[foto_3]);
			$item->addChild("fichero_1",$row[fichero_1]);
			$item->addChild("fichero_2",$row[fichero_2]);
			$item->addChild("fichero_3",$row[fichero_3]);
			
			$sql = "select * from articulos_tienda where id_articulo=$row[id];";
			$result2 = $this->computeSQL($sql, false);
			while ( $row2 = $this->oSystem->getConnection()->getColumnas($result2) ){
				$tiendas = $item->addChild("tiendas");
				$tiendas->addChild("tienda",$row2[tienda]);
				$tiendas->addChild("precio",$row2[precio]);
				$tiendas->addChild("oferta",$row2[oferta]);
				$tiendas->addChild("liquidacion",$row2[liquidacion]);
			}
			
			$sql = "select * from articulos_vinculados where id_articulo=$row[id];";
			$result3 = $this->computeSQL($sql, false);
			while ( $row3 = $this->oSystem->getConnection()->getColumnas($result3) ){
				$vinculados = $item->addChild("vinculados");
				//$vinculados->addChild("id_articulo",$row3[id_articulo]);
				$vinculados->addChild("vinculo",$row3[id_articulo_vinculado]);
				$vinculados->addChild("tipo",$row2[tipo]);
			}
		}
		
		/** log de la migracion */
		$xml->saveXML($file);
		
	}
	
// 	public function addChildWithCDATA($name, $value = NULL) {
// 		$new_child = $this->addChild($name);
	
// 		if ($new_child !== NULL) {
// 			$node = dom_import_simplexml($new_child);
// 			$no   = $node->ownerDocument;
// 			$node->appendChild($no->createCDATASection($value));
// 		}
	
// 		return $new_child;
// 	}
	
}

class SimpleXMLExtended extends SimpleXMLElement {
	public function addCDATA($cData) {
		$node = dom_import_simplexml($this);
		$no = $node->ownerDocument;
		$node->appendChild($no->createCDATASection($cData));
	}
}
?>