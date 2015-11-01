/**
 * Gestion MisTareas
 */
function onStartApplication(){
	
	//mensajes de aviso, alerta
	if ( PAGE_NAME=='list' )
		if ( MSG_INFO )
			alert ( MSG_INFO );
	
	if ( PAGE_NAME=='edit' ){
		
	}
}

/**
 * SOBREESCRITO. necesitamos cambiar el metodo de edicion
 * Este método recorre todas las filas de datos, para ello utiliza el filtro de busqueda tr.class que le permite
 * localizar lo que son filas de datos. A cada fila le anexa los eventos para el efecto de filas, y el onclick para 
 * la edición del registro
 */
function controlRows(){
	$$('tr.rowDataOff').each(function(item){
		if ( item.id=="" ) return;
		$(item.id).onmouseover = function(){ item.className = 'rowDataOn'; };
		$(item.id).onmouseout  = function(){ item.className = 'rowDataOff'; };		
		$(item.id).onclick = function(){
			$('id').value = item.getAttribute("id").replace('tr_rowdata_',"");
			$('do').value = 'doMinEdit';
			$('channel').value="";
			$('fapplication').method='POST';
			$('fapplication').submit();
		};
	});
}

/**
 * Enviamos el formulario de datos
 */
function doAceptar(){
	var error = "";
	
	if ( $('comentarios').value.replace(/^\s*/, '')=="" )
		error += ">Tienes que indicar el motivo del cierre de la tarea\n";
	
	if ( error ){
		alert ("Los siguiente campos no son válidos:\n"+error);
		return false;
	}	
	
	$('do').value = 'doUpdateByEnd';
	$('fapplication').submit();
	return false;
}

/**
 * Cancelar y volver nos deja otra vez en el listado
 */
function doCancelar(){
	$('do').value = 'getListMyTask';
	$('fapplication').submit();
	return false;
}
