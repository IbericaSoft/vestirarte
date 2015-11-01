<?
/** 
 * formulario con todos los datos del cliente. esta pantalla muestra los datos del cliente, que podra cambiar si lo desea. es un paso previo a la compra,
 * aunque tambien es una operacion de mantenimiento de la cuenta del cliente 
 **/
function formulario_mis_datos(Navigator $instance, Tienda$shop){
	if ( !$shop->cliente->provincia ){ //podríamos dejar los datos de ubicacion del cliente, vacios, pero gracias a su IP sabemos de donde es. no es 100% seguro
		$instance::$log->info("la provincia esta vacia asi que pongo en la cuenta del cliente los datos default de provincia,pais,etc.");
		$shop->cliente->provincia = $shop->portes->id_provincia;
		$shop->cliente->pais = $shop->portes->id_pais;
		$shop->cliente->provincia_ = $shop->portes->nombre_provincia;
		$shop->cliente->pais_ = $shop->portes->nombre_pais;
		$shop->cliente->provincia_facturacion = $shop->portes->id_provincia;
		$shop->cliente->pais_facturacion = $shop->portes->id_pais;
		$shop->cliente->provincia_facturacion_ = $shop->portes->nombre_provincia;
		$shop->cliente->pais_facturacion_ = $shop->portes->nombre_pais;		
	}
}

/** 
 * metodo para guardar los datos de la cuenta de cliente
 **/ 
function guardar_mis_datos(Navigator $instance, Tienda$shop){
	//bloque de datos de envio
	$shop->cliente->nombre 					= trim($_POST[nombre]);
	$shop->cliente->email 					= trim($_POST[email]);
	$shop->cliente->telefono 				= trim($_POST[telefono]);
	$shop->cliente->direccion 				= trim($_POST[direccion]);
	$shop->cliente->poblacion 				= trim($_POST[poblacion]);
	$shop->cliente->provincia 				= trim($_POST[provincia]);
	$shop->cliente->cpostal 				= trim($_POST[cpostal]);
	$shop->cliente->pais 					= trim($_POST[pais]);
	//facturacion
	$shop->cliente->nifcif 					= $_POST[nifcif];
	$shop->cliente->razon 					= $_POST[razon];
	$shop->cliente->direccion_facturacion	= $_POST[direccion_facturacion];
	$shop->cliente->poblacion_facturacion	= $_POST[poblacion_facturacion];
	$shop->cliente->provincia_facturacion	= $_POST[provincia_facturacion];
	$shop->cliente->cpostal_facturacion		= $_POST[cpostal_facturacion];
	$shop->cliente->pais_facturacion 		= $_POST[pais_facturacion];
	//personalizacion
	$shop->cliente->persona					= trim($_POST[persona]);
	//zonas de portes
	$shop->portes->id_provincia = $shop->cliente->provincia;
	$shop->portes->id_pais = $shop->cliente->pais;	
	//verificamos que todo sea correcto
	if ( checkErroresFormulario($instance, $shop) ){
		$instance->changeOnFlyPageBlock("piezas/datos_envio.html");//hay un error, le volvemos a dejar en la misma pagina
		return;
	}
	//todo ok
	$shop->portes->cargarPortesZona();
	//hay cesta
	if ( $shop->cesta->hayCesta() ){
		$instance->changeOnFlyPageBlock("piezas/resumen.html");
		infoCesta($instance, $shop);
	}else{
		$instance->changeOnFlyPageBlock("piezas/home.html");
	}	
}

/** 
 * validamos los datos de la cuenta de usuario 
 **/
function checkErroresFormulario(Navigator $instance, Tienda$shop){
	//validaciones
	$errores = array();
	
	if ( $shop->cliente->nombre=="" )
		array_push($errores, array("key"=>"nombre","motivo"=>"Nombre completo obligatorio"));
	
	if ( $shop->cliente->email=="" )
		array_push($errores, array("key"=>"email","motivo"=>"Email obligatorio"));
	else {
		if (!filter_var($shop->cliente->email, FILTER_VALIDATE_EMAIL)) {
			array_push($errores, array("key"=>"email","motivo"=>"Email incorrecto"));
		}
	}
	
	if ( $shop->cliente->telefono=="" )
		array_push($errores, array("key"=>"telefono","motivo"=>"Teléfono/movil obligatorio"));
	else {
		if ( strlen($shop->cliente->telefono)<9 ) 
			array_push($errores, array("key"=>"telefono","motivo"=>"Teléfono/movil incorrecto"));
	}
	
	if ( $shop->cliente->direccion=="" )
		array_push($errores, array("key"=>"direccion","motivo"=>"Dirección de envío obligatoria"));
	
	if ( $shop->cliente->poblacion=="" )
		array_push($errores, array("key"=>"poblacion","motivo"=>"Población/Municipio de envío obligatorio"));
	
	if ( $shop->cliente->provincia=="" )
		array_push($errores, array("key"=>"provincia","motivo"=>"Provincia de envío obligatoria"));
	
	if ( $shop->cliente->cpostal=="" )
		array_push($errores, array("key"=>"cpostal","motivo"=>"Código postal de envío obligatorio"));
	
	if ( $shop->cliente->pais=="" )
		array_push($errores, array("key"=>"pais","motivo"=>"País de envío obligatorio"));
	
	if ( $shop->cliente->nifcif=="" )
			array_push($errores, array("key"=>"nifcif","motivo"=>"NIF de facturación obligatoria"));
	//hacemos obligatorio la entrada de NIF-cif
	if ( $shop->cliente->razon=="" )
		array_push($errores, array("key"=>"razon","motivo"=>"Empresa de facturación obligatoria"));
	
	if ( $shop->cliente->direccion_facturacion=="" )
		array_push($errores, array("key"=>"direccion_facturacion","motivo"=>"Dirección de facturación obligatoria"));

	if ( $shop->cliente->poblacion_facturacion=="" )
		array_push($errores, array("key"=>"poblacion_facturacion","motivo"=>"Población/Municipio de facturación obligatorio"));

	if ( $shop->cliente->provincia_facturacion=="" )
		array_push($errores, array("key"=>"provincia_facturacion","motivo"=>"Provincia de facturación obligatoria"));

	if ( $shop->cliente->cpostal_facturacion=="" )
		array_push($errores, array("key"=>"cpostal_facturacion","motivo"=>"Código postal de facturación obligatorio"));

	if ( $shop->cliente->pais_facturacion=="" )
		array_push($errores, array("key"=>"pais_facturacion","motivo"=>"País de facturación obligatorio"));
		
	Navigator::addData("errores", $errores);
	return count($errores);
}

/** 
 * resumen de la cesta. Antes de formalizar el pedido e ir al TPV, esta pantalla muestra todos los datos de la compra 
 **/
function resumen(Navigator $instance, Tienda$shop){
	
	//estan todos los datos
	if ( checkErroresFormulario($instance, $shop) ){
		$instance->changeOnFlyPageBlock("piezas/datos_envio.html");
		return;
	}
	
	//hay cesta
	if ( !$shop->cesta->hayCesta() ){
		$instance->changeOnFlyPageBlock("piezas/cesta.html");
		infoCesta($instance, $shop);
		return;
	}
	
// 	//voy a buscar los literas de pais y provincia, no los tengo
// 	$sql  = "select long_name from pais where id=".$shop->cliente->pais;
// 	$resultado 	= $instance->getConnection()->query($sql);
// 	$datos = $instance->getConnection()->getColumnas($resultado);
// 	$shop->cliente->pais_ = $datos[long_name];
	
// 	$sql  = "select provincia from provincias where id=".$shop->cliente->provincia;
// 	$resultado 	= $instance->getConnection()->query($sql);
// 	$datos = $instance->getConnection()->getColumnas($resultado);
// 	$shop->cliente->provincia_ = $datos[provincia];
	
// 	$sql  = "select long_name from pais where id=".$shop->cliente->pais_facturacion;
// 	$resultado 	= $instance->getConnection()->query($sql);
// 	$datos = $instance->getConnection()->getColumnas($resultado);
// 	$shop->cliente->pais_facturacion_ = $datos[long_name];
	
// 	$sql  = "select provincia from provincias where id=".$shop->cliente->provincia_facturacion;
// 	$resultado 	= $instance->getConnection()->query($sql);
// 	$datos = $instance->getConnection()->getColumnas($resultado);
// 	$shop->cliente->provincia_facturacion_ = $datos[provincia];
	
	totalizar($instance, $shop);	
}

/**
 * Llamada para lanzar el TPV... se pintan en pantalla el TPV
 * @throws Tiene que ser YA cliente
 * @throws Tiene que tener cesta mayor de cero euros
 * @throws Si falla el registro del pedido en bbdd
 */
function lanzar_tpv(Navigator $instance, Tienda $shop){
	$instance::$log->debug("Lanzar TPV");
	if ( $shop->cliente->nombre==null ){
		//no deberiamos llegar aqui, a no ser que tecleemos nosotros la url /lanzar-tpv.html
		$instance::$log->error("Vaya, NO SOY cliente y estoy lanzando el TPV");
		$instance->addData("json",new BeanGenerico(99, array("motivo"=>("Atención, está intentado conectar con el TPV de esta
				tienda y no detectamos que este registrado. Esta operación esta siendo auditada para detectar errores y evitar usos fraudulentos."))));
		//auditoria($instance,$shop,"Detectada situación de un usuario no registrado que intenta lanzar el TPV.");
		return;
	}

	if ( $shop->cesta->cesta_con_iva()<=0 ){
		//no deberiamos llegar aqui
		$instance::$log->error("Vaya, NO TENGO cesta y estoy lanzando el TPV");
		$instance->addData("json",new BeanGenerico(99, array("motivo"=>("Atención, está intentado conectar con el TPV de esta
				tienda y el importe de su compra es cero. Esta operación esta siendo auditada para detectar errores y evitar usos fraudulentos."))));
		//auditoria($instance,$shop,"Detectada situación de un usuario registrado que tiene una cesta con cero euros o menos.");
		return;
	}

	try {				
		$total = $shop->cesta->cesta_con_iva()+$shop->portes->porte_con_iva();
		$instance->addData("json",new BeanGenerico(0, array("form"=>$shop->tpv->htmlForm($total,null))));//si llevamos mas de cinco intentos, esto da error (por seguridad)
		$shop->cliente->observaciones = trim($_POST[observaciones]);
		registrarPedido($instance,$shop);
	} catch (Exception $e){		
		$instance->addData("json",new BeanGenerico(99, array("motivo"=>utf8_encode("Tenemos que pedirle disculpas. Hemos detectado un error en nuestro sistema al preparar su pedido. Vuelva a intentarlo y si persiste el error, le rogamos se ponga en contacto con nosotros para solucionar el problema"))));
		auditoria($instance,$shop,$e->getMessage());
	}
	//todo OK...deberiamos ver el TPV en pantalla
}

/**
 * Registramos el pedido como un PRESUPUESTO. Se convertira en pedido en cuanto la forma de pago actual (normalmente TPV) nos devuelva un resultado
 * @throws Si falla el insert del pedido
 * @throws Si fallan los detalles del pedido... además, borramos el pedido que esta a medias
 */
function registrarPedido(Navigator $instance, Tienda $shop){
	$instance->getConnection()->query("START TRANSACTION;");
	try{
		//PEDIDO
		$datos					= array();
		$datos[id]				= $shop->tpv->_ORDER;
		$datos[id_cliente] 		= 0;//no hay clientes, peticion de vestirarte
		$datos[cliente] 		= utf8_decode($shop->cliente->nombre);
		$datos[email] 			= $shop->cliente->email;
		$datos[telefono] 		= $shop->cliente->telefono;
		$datos[direccion]		= utf8_decode($shop->cliente->direccion);
		$datos[poblacion] 		= utf8_decode($shop->cliente->poblacion);
		$datos[provincia] 		= utf8_decode($shop->cliente->provincia);
		$datos[pais] 			= utf8_decode($shop->cliente->pais);
		$datos[cpostal] 		= $shop->cliente->cpostal;
		$datos[razon] 			= utf8_decode($shop->cliente->razon);
		$datos[nifcif] 			= $shop->cliente->nifcif;
		$datos[fdireccion]		= utf8_decode($shop->cliente->direccion_facturacion);
		$datos[fpoblacion]		= utf8_decode($shop->cliente->poblacion_facturacion);
		$datos[fprovincia]		= utf8_decode($shop->cliente->provincia_facturacion);
		$datos[fpais]			= utf8_decode($shop->cliente->pais_facturacion);
		$datos[fcpostal]		= $shop->cliente->cpostal_facturacion;
		$datos[forma_pago] 		= 'TPV';
		$datos[gastos_imponible]= $shop->portes->porte_sin_iva();
		$datos[gastos_total] 	= $shop->portes->porte_con_iva();		
		$datos[gastos_tipo_iva] = $shop->portes->tipo_iva_portes();
		$datos[gastos_total_iva]= $shop->portes->impuestos();
		$datos[pedido_tipo_iva]	= $shop->cesta->tipo_iva_cesta();
		$datos[pedido_total_iva]= $shop->cesta->iva_articulos()+$shop->portes->impuestos();		
		$datos[pedido_imponible]= $shop->cesta->cesta_sin_iva();
		$datos[pedido_subtotal] = $shop->cesta->cesta_con_iva();
		$datos[pedido_total]	= $shop->cesta->cesta_con_iva()+$shop->portes->porte_con_iva();
		$datos[peso] 			= $shop->portes->peso;
		$datos[observaciones]	= utf8_decode($shop->cliente->observaciones);
		$datos[regalo]			= utf8_decode($shop->cliente->persona);//personalizacion del producto
		$datos[falta]			= date("Y-m-d H:i:s");
		$datos[id_administrador]= 1;
		$datos[estado] 			= 'PRESUPUESTO';
		$datos[fmodificacion]	= date("Y-m-d H:i:s");
	
		while(list($key,$val)= each($datos)){
			if ($sqlFields) {$sqlFields.=',';$sqlValues.=',';}
			$sqlFields .= "$key";
			$sqlValues .= "\"$val\"";
		}
		$sql = "INSERT INTO pedidos ($sqlFields) VALUES ($sqlValues)";
		$instance->getConnection()->query($sql);
		if ( $instance->getConnection()->getFilasAfectadas()!=1 )
			throw new Exception("Error al insertar el pedido. SQL lanzado ($sql). Error capturado: ".$instance->getConnection()->getError());		

		//DETALLES
		$articulo = new ArrayObject($shop->cesta->listArticulos);//esta clase nos da funcionalidad extra con los objetos, como las iteraciones
		$iterador = $articulo->getIterator();
		while($iterador->valid()){
			$bean = clone $iterador->current(); //clone hace una copia del objeto BeanArticulo (solo de objetos, no de otra cosa xq da error)
			$id = $shop->tpv->_ORDER;
			$bean->iva = $iterador->current()->iva;
			if ( $bean->oferta_iva > 0 ){
				$precio = $iterador->current()->oferta_iva;
				$precio_sin = $iterador->current()->oferta_iva;
			}else{
				$precio = $iterador->current()->precio_iva;
				$precio_sin = $iterador->current()->precio_iva;
			}
			//$subtotal=$precio_sin*$bean->unidades;
			$total=$precio*$bean->unidades;
			$sql = "INSERT INTO pedidos_detalle (id_pedido,id_variedad,cantidad,precio,iva,subtotal,total) VALUES ($id,$bean->id,$bean->unidades,$precio,0,0,$total);";
			$instance->getConnection()->query($sql);
			if ( $instance->getConnection()->getFilasAfectadas()!=1 )
				throw new Exception("Error al insertar uno de los detalles. SQL lanzado ($sql). Error capturado: ".$instance->getConnection()->getError());
			$iterador->next();
		}
		$instance->getConnection()->query("COMMIT;");
		//Perfecto
	} catch(Exception $e){
		$instance->getConnection()->query("ROLLBACK;");
		Navigator::$log->error($e->getMessage());
		throw new Exception($e);
		//salimos con error
	}
}

/**
 * Este metodo lo ejecuta la entidad al contactar con nosotros para informarnos del resultado. Es muy poco probable que un usuario
 * pudiese invocar esta operacion por medio de su url /contacto-tpv.html pero controlamos el origen de la peticion para evitar intrusiones.
 * Aqui es donde se aceptar o rechaza el pedido conforme a los datos de la request de la entidad
 * @param Navigator $instance
 * @param Tienda $shop
 */
function notificacion_tpv(Navigator $instance, Tienda $shop){
	if ( $shop->tpv->isOK() ){
		resultadoPedido($instance,$shop);
	} else /** la firma no es valida, lo consideramos respuesta fraudulenta*/ {
		Navigator::$log->info("llega request de la entidad");
		$filefraund = OS_ROOT."/logs/".date("dmYHis").'_fraudTPV.txt';
		$shop->tpv->persist_report($filefraund);
		auditoria($instance,$shop,"Houston, hemos recibido un mensaje de respuesta del TPV que es falso. Los detalles están es el fichero $filefraund");
	}
	//fin, ya tenemos que saber si el pedido es valido o no
}

/** marcar el pedido como aceptado o rechazado segun nos diga la entidad */
function resultadoPedido(Navigator $instance, Tienda $shop){
	$p = $shop->tpv->getPostOrder();
	$r = $shop->tpv->getResultCode();
	$rp= $shop->tpv->report();
	auditoria($instance,$shop,"Houston, hemos recibido una conexión de respuesta del TPV que nos dice...<br><br>$rp");
	if ($shop->tpv->getResultCode()>99) {//han denegado la operacion		
		if ( $_POST['Ds_ErrorCode'] )
			$e = $shop->tpv->err($_POST['Ds_ErrorCode']);
		informativo($instance,$shop,"La entidad bancaria nos informa que el pedido $p ha sido RECHAZADO por el motivo " . $e );
		$sql = "UPDATE pedidos SET estado='RECHAZADO',fmodificacion=now(),id_administrador=1,entidad_respuesta='$r',entidad_report='$rp',entidad_fecha=now() WHERE id=$p;";
	} else {
		informativo($instance,$shop,"La entidad bancaria nos informa que el pedido $p ha sido ¡¡ACEPTADO!!");
		$sql = "UPDATE pedidos SET estado='PEDIDO',fmodificacion=now(),id_administrador=1,entidad_respuesta='$r',entidad_report='$rp',entidad_fecha=now() WHERE id=$p;";
	}
	$instance->getConnection()->query($sql);
	if ( $instance->getConnection()->getFilasAfectadas()!=1 ){
		auditoria($instance,$shop,"Houston, al tirar el SQL para actualizar el estado del pedido que nos ha pasado la entidad (<strong>".($shop->tpv->getResultCode()>99)?'DENEGANDO':'ACEPTANDO'."</strong>) hemos fallado. El error ha sido <<". $instance->getConnection()->getError().".>> El pedido es el número:<strong>$p</strong> y los detalles son...<br><br>$rp");
		return;
	}
	if ($shop->tpv->getResultCode()==0)//todo OK
		emailPedidoCliente($instance,$shop);	
}

/** 
 * Email del PEDIDO AL CLIENTE 
 **/
function emailPedidoCliente(Navigator $instance, Tienda $shop){
	$shop->html_email_pedido = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/frontoffice/esp/emails/email_pedido.html');
	$p = $shop->tpv->getPostOrder();
	$resultado = $instance->getConnection()->query("SELECT 
			(select long_name from pais where id=ped.pais) f_pais
			,(select long_name from pais where id=ped.fpais) f_fpais
			,(select provincia from provincias where id=ped.provincia) f_provincia
			,(select provincia from provincias where id=ped.fprovincia) f_fprovincia
			,date_format(ped.falta,'%d/%m/%Y')falta
			,format(ped.pedido_tipo_iva,2,'de_DE') fiva	
			,format(ped.pedido_total,2,'de_DE') ftotal
			,ped.*
			FROM pedidos ped
			WHERE ped.id=$p;");

	$datos = $instance->getConnection()->getColumnas($resultado);
	$email_cliente = $datos[email];
	$html = $shop->html_email_pedido;
	$html = str_replace("{HOST}",HOST,$html);
	$html = str_replace("{PEDIDO}",$p,$html);
	$envio = "$datos[cliente]<br/>$datos[email]<br/>$datos[telefono]<br/>$datos[direccion]<br/>$datos[poblacion]<br/>$datos[f_provincia]($datos[cpostal])<br/>$datos[f_pais]";
	$html = str_replace("{ENVIO}",$envio,$html);
	$facturacion = "$datos[nifcif]<br/>$datos[razon]<br/>$datos[fdireccion]<br/>$datos[fpoblacion]<br/>$datos[f_fprovincia]($datos[fcpostal])<br/>$datos[f_fpais]";
	$html = str_replace('{FACTURACION}',$facturacion,$html);
	$html = str_replace('{REGALO}',$datos[regalo],$html);
	$html = str_replace('{TOTAL}',"$datos[ftotal] &euro;",$html);
	$html = str_replace('{IVA}',"$datos[fiva] %",$html);
	$html = str_replace("{FENTREGA}",$datos[fecha_prevista_entrega],$html);
	$html = str_replace('{OBSERVACIONES}',$datos[observaciones],$html);

	$resultado = $instance->getConnection()->query("SELECT 
			art.articulo
			,art.foto_1
			,tal.talla
			,format(det.precio,2,'de_DE') fprecio
			,format(det.total,2,'de_DE') ftotal
			,det.*
			FROM pedidos_detalle det
			LEFT JOIN articulos_variedad var ON (det.id_variedad=var.id)
			LEFT JOIN tallas tal ON (var.id_talla=tal.id)
			LEFT JOIN articulos art ON (var.id_articulo=art.id)
			WHERE det.id_pedido=$p;");
	ereg("(<!--LIST1)(.*)(LIST1-->)",$html,$reg);
	while ( $datos = $instance->getConnection()->getColumnas($resultado) ){
		$html = str_replace('{foto}','https://'.HOST.'/frontoffice/images/'.$datos[foto_1],$html);
		$html = str_replace('{articulo}',$datos[articulo],$html);
		$html = str_replace('{talla}',$datos[talla],$html);
		$html = str_replace('{unidades}',$datos[cantidad],$html);
		$html = str_replace('{precio}',"$datos[fprecio] &euro;",$html);
		$html = str_replace('{subtotal}',"$datos[ftotal] &euro;",$html);

		$html = str_replace("<!--LIST1","",$html);
		$html = str_replace("LIST1-->","<!--NEXT-->",$html);
		$html = str_replace("<!--NEXT-->","\n$reg[1]$reg[2]$reg[3]\n",$html);
	}

	$shop->email->enviar($email_cliente, null, utfd_server("Confirmación del pedido $p de ".HOST), $html, null);

	//copia interna
	foreach($shop->email_pedidos as $cuenta){
		$instance::$log->debug("Copia pedido a $cuenta");
		$shop->email->enviar($cuenta, null, utfd_server("(COPIA) Confirmación del pedido $p de ".HOST), $html, null);
	}
}

/** Ya sabemos el resultado, pero el usuario no, esta pagina muestra el resultado al cliente.
 * El TPV nos deja de vuelta aqui
 **/
function fin_tpv(Navigator $instance, Tienda $shop){
	$p = $shop->tpv->_ORDER;
	$sql = "SELECT * FROM pedidos WHERE id=$p;";
	$result = $instance->getConnection()->query($sql);
	if ( !$instance->getConnection()->totalRegistros() ) {
		auditoria($instance,$shop,"Houston, la pagina fin TPV, llega con el pedido $p, pero no existe. Es posible
		que álguien o algo esta consumiento la url /tpv-fin.html y eso desencadena este mensaje");

		$instance->addData("tpv-resultado","SIN-PEDIDO");
		return;
	}

	$datos = $instance->getConnection()->getColumnas($result);

	if ($datos[estado]=="PRESUPUESTO"){
		$instance->addData("tpv-resultado","?");
		$instance->addData("tpv-continuar","/resumen.html");
		$instance->addData("tpv-titulo","OPERACION CANCELADA!!!");
		return;
	}
	if ($datos[estado]=="RECHAZADO"){
		$instance->addData("tpv-pedido","$p");
		$instance->addData("tpv-resultado","PEDIDO-RECHAZADO");
		$instance->addData("tpv-continuar","/resumen.html");
		$instance->addData("tpv-titulo","OH NOO!!! La entidad a RECHAZADO la operación");
		return;
	}
	if ($datos[estado]=="PEDIDO"){
		$instance->addData("tpv-resultado","PEDIDO-ACEPTADO");
		$rep = strpos($datos[entidad_report],'Ds_AuthorisationCode=' ) + 21;
		$instance->addData("tpv-titulo","EUREKA!!! La entidad a AUTORIZADO la operación");
		$instance->addData("tpv-codigo",rtrim(substr($datos[entidad_report],$rep),";"));
		$instance->addData("tpv-pedido","$p");
		$instance->addData("tpv-email",$datos[email]);
		$instance->addData("tpv-continuar","/");
		vaciarCesta($instance, $shop);
		return;
	}
}
?>