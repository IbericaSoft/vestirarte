/**
 * Gestion parametros Empresa
 */
function onStartApplication(){
	//mensajes de aviso, alerta
	if ( PAGE_NAME=='list' )
		if ( MSG_INFO )
			alert ( MSG_INFO );
	
	if ( PAGE_NAME=='edit' ){
		//definir pestañas
		initTabs('tabs',Array('Par&aacute;metros','Control'),0);
	}
}


/**
 * Metodo para la búsqueda en esta aplicación. Prepara la invocación a la clase con los parametros del filtro a utilizar.
 * Si la respuesta del servidor es correcta se procesa el metodo callBack que tiene los datos de vuelta, si por el contrario
 * hay un error, lo mostramos al usuario.
 */
function doSearch(){
	
}

/**
 * Este método se encarga de refrescar la lista de datos después de utilizar el filtro de búsqueda. Las listas de datos son siempre
 * filas y columnas. El procedimiento es el siguiente. Se recorren los resultados y se pintan, a continuación se anexan los eventos
 * correspondientes a las filas
 * @param json Lista de datos devuelta por el servidor a petición de un filtro
 */
function refreshList(pagination,json){
	//crea con los datos recibidos json la lista de datos
	var rows = 0;
	json.each( 
			function(item){
				rows++;
				var imgLock = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/padlock.gif'});
				var imgLapiz = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/lapiz.gif'});
				
				var tr = new Element('TR',{id:'tr_rowdata_'+item.id,title:'click para editar','class':'rowDataOff'});
				var td1= new Element('TD',{'class':'colIcon',title:(item.estado=='DES')?"Atencion: Deshabilitado":""} );
				var td2= new Element('TD',{'class':'colIcon'} );
				var td3= new Element('TD',{'class':'colSort'} );
				var td4= new Element('TD',{'class':'colSort'} );
				
				tr.insert(td1.update('&nbsp;'));
				
				tr.insert(td2.update(imgLapiz));
				tr.insert(td3.update(item.descripcion));				
				tr.insert(td4.update(item.valor));

				$('tbLista').insert(tr);
			}	
	);
	
	//Fila vacia para crear espacio
	var tr = new Element('TR',{'class':'rowDataOff'});
	var td = new Element('TD',{'class':'colSort','colspan':10}).update('&nbsp;');
	tr.insert(td);
	$('tbLista').insert(tr);

	
	if ( rows==0 ) {//si no hay resultados
		var tr = new Element('TR',{'class':'pagination'});
		var td1= new Element('TD',{'class':'pagination'}).update('--- No hay resultados ---');
		tr.insert(td1);
		$('tbCount').insert(tr);
		return;
	}
	
	//datos para la paginacion
	var tr = new Element('TR',{'class':'pagination'});
	var td1= new Element('TD',{'class':'pagination'});
	tr.insert(td1.update(pagination));
	$('tbCount').insert(tr);
	
	//anexa eventos a las filas
	controlRows();
}

/**
 * Petición de la pantalla de alta de datos
 */
function doNew(){
	
}

/**
 * Petición para imprimir la vista actual
 */
function doPrint(){
	
}


/**
 * Exportar datos de esta vista a formato excel
 */
function doExport(){
	
}

/**
 * Crear un acceso directo para esta vista
 */
function doLink(){
	
}


/**
 * Enviamos el formulario de datos
 */
function doAceptar(){
	var error = "";
	
	//$('valor').value = FCKeditorAPI.GetInstance("valor").GetHTML();
	if ( $('valor').value.replace(/^\s*/, '')=="" )
		error += ">Valor\n";

	if ( error ){
		alert ("Los siguiente campos no son válidos:\n"+error);
		return false;
	}	
	
	$('do').value = 'doUpdate';
	$('fapplication').submit();
	return false;
}

/**
* Baja de datos
*/
function doEliminar(){

}

/**
 * Cancelar y volver nos deja otra vez en el listado
 */
function doCancelar(){
	$('do').value = 'listAll';
	$('fapplication').submit();
	return false;
}
