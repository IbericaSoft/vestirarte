<?
/**
 * devuelve todas las categorias-Familias
 */
function menuFamilias(Navigator $instance, Tienda $shop){	
	$sql = "SELECT cat.categoria,fam.* FROM familias fam LEFT JOIN categorias cat ON (fam.id_categoria=cat.id) 
			WHERE fam.estado IN ('ON') order by id_categoria, fam.posicion;";
	$resultado 	= $instance->getConnection()->query($sql);
	$datos = array();
	while ( $rows = $instance->getConnection()->getColumnas($resultado) ){
		foreach (array_keys($rows) as $k)
			if (is_int($k) ) continue;
			else $row[$k]=utf8_encode($rows[$k]);
		array_push($datos, $row);
	}
	Navigator::addData("familias", $datos);
}

/**
 * Devuelve todas las subfamilias de una familia
 */
function menuSubFamilias(Navigator $instance, Tienda $shop){
	$sql = "SELECT * FROM subfamilias 
	WHERE id_familia=(select id from familias where familia='".utfd_server($shop->familia_actual)."') AND estado IN ('ON') 
	ORDER BY posicion;";
	$resultado 	= $instance->getConnection()->query($sql);
	$datos = array();
	Navigator::$log->debug( $instance->getConnection()->totalRegistros() );
	while ( $rows = $instance->getConnection()->getColumnas($resultado) ){
		foreach (array_keys($rows) as $k)
			if (is_int($k) ) continue;
			else $row[$k]=utf8_encode($rows[$k]);
		array_push($datos, $row);
	}
	Navigator::addData("subfamilias", $datos);
}

/**
 * Seleccion de articulos de una categoria
 */
function articulos_categoria(Navigator $instance, Tienda $shop){	
		$sql  = "SELECT art.id,art.articulo,art.foto_1,FORMAT(precio,2,'de_DE')precio,FORMAT(oferta,2,'de_DE')oferta,iv.iva,fam.familia,sub.subfamilia
			FROM articulos art
			LEFT JOIN subfamilias sub ON (art.id_subfamilia=sub.id)
			LEFT JOIN familias fam ON (sub.id_familia=fam.id)
			LEFT JOIN categorias cat ON (fam.id_categoria=cat.id)
			LEFT JOIN iva iv ON (art.id_iva=iv.id)
			WHERE categoria='".utfd_server($shop->categoria_actual)."' ORDER BY art.fmodificacion LIMIT 10;";
	$resultado 	= $instance->getConnection()->query($sql);
	$datos = array();
	while ( $rows = $instance->getConnection()->getColumnas($resultado) ){
		foreach (array_keys($rows) as $k)
			if (is_int($k) ) continue;
			else $row[$k]=utf8_encode($rows[$k]);
		array_push($datos, $row);
	}

	Navigator::addData("articulos", $datos);
}

/**
 * Seleccion de articulos de una familia
 */
function articulos_familia(Navigator $instance, Tienda $shop){
	$sql  = "SELECT art.id,art.articulo,art.foto_1,FORMAT(precio,2,'de_DE')precio,FORMAT(oferta,2,'de_DE')oferta,fam.familia,sub.subfamilia
			FROM articulos art
			LEFT JOIN subfamilias sub ON (art.id_subfamilia=sub.id)
			LEFT JOIN familias fam ON (sub.id_familia=fam.id)
			LEFT JOIN categorias cat ON (fam.id_categoria=cat.id)
			WHERE categoria='".utfd_server($shop->categoria_actual)."' AND familia='".utfd_server($shop->familia_actual)."' ORDER BY art.fmodificacion;";
	$resultado 	= $instance->getConnection()->query($sql);
	Navigator::$log->debug( $instance->getConnection()->totalRegistros() );
	$datos = array();
	while ( $rows = $instance->getConnection()->getColumnas($resultado) ){
		foreach (array_keys($rows) as $k)
			if (is_int($k) ) continue;
			else $row[$k]=utf8_encode($rows[$k]);
		array_push($datos, $row);
	}
	
	//if ( !count($datos) ) $instance->changeOnFlyPageBlock("piezas/nohayresultados.html");
	Navigator::addData("articulos", $datos);
}

/**
 * Seleccion de articulos de una familia
 */
function articulos_subfamilia(Navigator $instance, Tienda $shop){
	$sql  = "SELECT art.id,art.articulo,art.foto_1,FORMAT(precio,2,'de_DE')precio,FORMAT(oferta,2,'de_DE')oferta,fam.familia,sub.subfamilia
			FROM articulos art
			LEFT JOIN subfamilias sub ON (art.id_subfamilia=sub.id)
			LEFT JOIN familias fam ON (sub.id_familia=fam.id)
			LEFT JOIN categorias cat ON (fam.id_categoria=cat.id)
			WHERE categoria='".utfd_server($shop->categoria_actual)."' AND familia='".utfd_server($shop->familia_actual)."' AND subfamilia='".utfd_server($shop->subfamilia_actual)."' ORDER BY art.fmodificacion;";
	$resultado 	= $instance->getConnection()->query($sql);
	Navigator::$log->debug( $instance->getConnection()->totalRegistros() );
	$datos = array();
	while ( $rows = $instance->getConnection()->getColumnas($resultado) ){
		foreach (array_keys($rows) as $k)
			if (is_int($k) ) continue;
		else $row[$k]=utf8_encode($rows[$k]);
		array_push($datos, $row);
	}

	//if ( !count($datos) ) $instance->changeOnFlyPageBlock("piezas/nohayresultados.html");
	Navigator::addData("articulos", $datos);
}

/**
 * Ficha de un artiulo
 */
function ficha(Navigator $instance, Tienda $shop){
	$sql  = "SELECT 
		categoria,familia,subfamilia,autor,art.*,FORMAT(precio,2,'de_DE')precio,FORMAT(oferta,2,'de_DE')oferta,iv.iva
		FROM articulos art
		LEFT JOIN autores aut ON (art.id_autor=aut.id)
		LEFT JOIN subfamilias sub ON (art.id_subfamilia=sub.id)
		LEFT JOIN familias fam ON (sub.id_familia=fam.id)
		LEFT JOIN categorias cat ON (fam.id_categoria=cat.id)
		LEFT JOIN iva iv ON (art.id_iva=iv.id)
		WHERE articulo='".utfd_server($shop->articulo_actual)."' AND art.estado='on';";
	$resultado 	= $instance->getConnection()->query($sql);
	$rows = $instance->getConnection()->getColumnas($resultado);
	$id = $rows[id];
	$datos = array();
	foreach (array_keys($rows) as $k){
		if (is_int($k) ) continue;
		else $row[$k]=utf8_encode($rows[$k]);
		array_push($datos, $row);
	}
	Navigator::addData("articulo", $rows);
	
	/** variedades */
	$sql  = "SELECT
		talla,descripcion,var.*
		FROM articulos_variedad var
		LEFT JOIN tallas tal ON (var.id_talla=tal.id)
		WHERE id_articulo=$id AND var.estado='ON';";
	$resultado 	= $instance->getConnection()->query($sql);
	$datos = array();
	while ( $rows = $instance->getConnection()->getColumnas($resultado) ){
		foreach (array_keys($rows) as $k)
			if (is_int($k) ) continue;
		else $row[$k]=utf8_encode($rows[$k]);
		array_push($datos, $row);
	}
	Navigator::addData("variedades", $datos);
}

/** contactar validacion */
function contactar_envio(Navigator $instance, Tienda$shop){
	$instance::$log->debug("recoger contacto");
	$captcha_actual = $shop->captcha_result;
	$shop->regenerarCaptcha();

	$nombre = trim($_POST[nombre]);
	$email = ($_POST[email]);
	$consulta = (trim($_POST[consulta]));
	$dpto	= $_POST[dpto];
	$captcha	= (trim($_POST[captcha]));

	$errores = array();

	if ( $nombre=="" )
		array_push($errores, array("key"=>"nombre","motivo"=>"Nombre necesario"));
	if ( $email=="" )
		array_push($errores, array("key"=>"email","motivo"=>utf8_encode("Email o teléfono necesario")));
	if ( $consulta=="" )
		array_push($errores, array("key"=>"consulta","motivo"=>"No ha realizado su consulta"));
	if ( $dpto=="" )
		array_push($errores, array("key"=>"dpto","motivo"=>"Departamento necesario"));
	if ( $captcha!=$captcha_actual)
		array_push($errores, array("key"=>"captcha","motivo"=>utf8_encode("El resultado de la operación aritmética no es correcto")));
	//hay errores
	if ( count($errores) ){
		Navigator::addData("errores", $errores);
		$instance->changeOnFlyPageBlock("piezas/contactar.html");
		return;
	}

	//enviar email de contacto
	$msg = ("Acabamos de recibir la siguiente consulta:<br><br><strong>Teléfono/Email:</strong><br>$email<br><br><strong>Consulta:</strong><br>$comentarios");
	informativo($instance,$shop,$msg);

	$shop->mensaje_texto_paginas_redireccionadas = "<strong>Muchas gracias!!!</strong><br><br>Acabamos de recibir su consulta. Nos pondremos en contacto con usted si así lo desea. Gracias por su interés";
	$instance->addData("json", new BeanGenerico(0, array("avanzar"=>"/gracias.html")));
}
?>