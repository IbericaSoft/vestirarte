<?
/** 
 * A�ade o incrementa un articulo de la cesta
 * Gestionamos el error de que se intente a�adir un articulo que no existe en BBDD
 */
function masCesta(Navigator $instance, Tienda $shop){
	if ( !$shop->cesta->existeArticulo($_REQUEST[variedad]) ){
		Navigator::$log->debug("añadiendo a la cesta");
 		$sql  = "SELECT var.id variedad,i.iva,tal.talla,art.*
 			FROM articulos_variedad var
 			LEFT JOIN articulos art ON (var.id_articulo=art.id)
 			LEFT JOIN iva i ON (art.id_iva=i.id)
 			LEFT JOIN tallas tal ON (var.id_talla=tal.id)
 			WHERE var.id=$_REQUEST[variedad];";
 		$resultado 	= $instance->getConnection()->query($sql);
 		
 		/**esto seria un error*/
 		if ( $instance->getConnection()->hayResultados()==0 ){
 			$instance->addData("json", new BeanGenerico(11, utf8_encode("No existe el art�culo indicado!")) );
 			return;
 		}
 		
 		//a�ado o incremento (segun corresponda) el articulo en la cesta y devuelve el resultado de la cesta
 		$datos = $instance->getConnection()->getColumnas($resultado);
 		$shop->cesta->anadeArticulo(
 				$datos[variedad],
 				$datos[articulo],
 				$_REQUEST[unidades],
 				$datos[precio],
 				$datos[oferta],
 				$datos[iva],
 				$datos[peso],
 				$datos[foto_1],
 				$datos[talla]);//llamada para a�adir a la cesta
 		
	} else {
		Navigator::$log->debug("incrementando la cesta en $_REQUEST[unidades] unidades");
		$shop->cesta->masArticulo($_REQUEST[variedad], $_REQUEST[unidades]);//llamada para incrementar unidades
	}
	
 	totalizar($instance, $shop);
}

/**
 * Elimina o disminuye un articulo de la cesta
 * Gestionamos el error de que se intente decrementar un articulo que no existe en la cesta
 */
function menosCesta(Navigator $instance, Tienda $shop){
	Navigator::$log->debug("restando de la cesta $_REQUEST[variedad] con $_REQUEST[unidades] unidades");
	/**esto seria un error*/
	if ( !$shop->cesta->existeArticulo($_REQUEST[variedad]) ){
		$instance->addData("json", new BeanGenerico(11, utf8_encode("No existe el art�culo \"$shop->articulo_actual\"")) );
 		return;
	}	
	$shop->cesta->menosArticulo($_REQUEST[variedad],$_REQUEST[unidades]);//llamada para restar unidades
	totalizar($instance, $shop);
}

/**
 * Vacia la cesta
 */
function vaciarCesta(Navigator $instance, Tienda $shop){
	$shop->cesta = new CestaCustom();
	$shop->portes = new PortesZonas();
	totalizar($instance, $shop);
}

/**
 * Despues de hacer operaciones con la cesta, totalizamos y añadimos datos al array de datos que el cliente puede ver y pintar en pantalla
 */
function totalizar(Navigator $instance, Tienda $shop){
	//refrescar los portes
	$shop->portes->calculaPorte( $shop->cesta->peso_cesta() );
	//calcular todos los detalles
	$formatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
	$bc = new BeanCestaCustom();
	$bc->total_articulos = $shop->cesta->total_articulos();
	$bc->importe_articulos_con_iva = $shop->cesta->cesta_con_iva_formato();
	$bc->importe_articulos_sin_iva = $shop->cesta->cesta_sin_iva_formato();
	$bc->importe_iva_articulos = $shop->cesta->iva_articulos_formato();
	$bc->importe_portes_con_iva = $shop->portes->porte_con_iva_formato();
	$bc->importe_portes_sin_iva = $shop->portes->porte_sin_iva();
	$bc->importe_iva_portes = $shop->portes->impuestos();
	$bc->iva_aplicado_articulos = $shop->cesta->tipo_iva_cesta_formato();
	$bc->iva_aplicado_portes = $shop->portes->tipo_iva_portes();	
	$bc->total_cesta_con_iva = $formatter->formatCurrency(( $shop->cesta->cesta_con_iva() + $shop->portes->porte_con_iva() ), 'EUR');
	$bc->total_cesta_sin_iva = $formatter->formatCurrency(( $shop->cesta->cesta_sin_iva() + $shop->portes->porte_sin_iva() ), 'EUR');
	$bc->peso_cesta = $shop->cesta->peso_cesta_formato();
	$bc->lista_articulos = $shop->cesta->listArticulos;
	$instance->addData("json",$bc);
}

/**
 * ver la cesta
 */
function detalleCesta(Navigator $instance, Tienda $shop){
	$datos = array();
	foreach ($shop->cesta->listArticulos as $elArticulo){
		$precio   = ($elArticulo->oferta>0)?$elArticulo->oferta:$elArticulo->precio;
		$subtotal = $elArticulo->unidades*$precio;
		$peso = number_format($elArticulo->peso*$elArticulo->unidades,3,',','.');
		array_push($datos, array("articulo"=>$elArticulo->articulo,"foto"=>$elArticulo->foto,"unidades"=>$elArticulo->unidades,"peso"=>$peso,"precio"=>number_format($precio,2,',','.'),"subtotal"=>number_format($subtotal,2,',','.'),"oferta"=>number_format($elArticulo->oferta,2,',','.')));		
	}
	$instance->addData("cesta",$datos);
	//costes envio
	$instance->addData("portes",number_format($shop->portes->porte_sin_iva(),2,',','.'));
	$total = $shop->cesta->cesta_con_iva()+$shop->portes->porte_con_iva();
	$instance->addData("impuestos",number_format($shop->cesta->impuestos()+$shop->portes->impuestos(),2,',','.'));
	$instance->addData("total",number_format($total,2,',','.'));
	$instance->addData("tipo_iva_cesta",number_format($shop->cesta->tipo_iva_cesta(),2,',','.'));
	//costes en tienda
	$total_tienda = $shop->cesta->cesta_con_iva();
	$instance->addData("impuestos_tienda",number_format($shop->cesta->impuestos(),2,',','.'));
	$instance->addData("total_tienda",number_format($total_tienda,2,',','.'));		
}

/**
 * Lee la informacion de la cesta y la almacena en BeanCesta
 * Desde la web se lee: self::getData("infoCesta")->total_articulos ... (ver propiedades de la clase BeanCesta)
 */
function infoCesta(Navigator $instance, Tienda $shop){
	if ( !$shop->cesta->hayCesta() ){
		$shop->mensaje_texto_paginas_redireccionadas = "... no tienes nada en la cesta";
		$shop->link_redirecciones = "/";
		$instance->changeOnFlyPageBlock("piezas/mensajes_comunes.html");
		return;
	}	
	totalizar($instance, $shop);
}

?>
