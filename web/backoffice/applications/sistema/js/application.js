/**
 * Gestion IVAs
 */
function onStartApplication(){
	//mensajes de aviso, alerta
	if ( PAGE_NAME=='list' )
		if ( MSG_INFO )
			alert ( MSG_INFO );
	
	if ( PAGE_NAME=='edit' ){
		//definir pestañas
		initTabs('tabs',Array('Sistema','Control'),0);

	}
}

/**
 * SOBREESCRITO. necesitamos que el envio sea POST
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
			$('do').value = 'doEdit';
			$('channel').value="";
			$('fapplication').method='POST';
			$('fapplication').submit();
		};
	});
}

/**
 * Metodo para la búsqueda en esta aplicación. Prepara la invocación a la clase con los parametros del filtro a utilizar.
 * Si la respuesta del servidor es correcta se procesa el metodo callBack que tiene los datos de vuelta, si por el contrario
 * hay un error, lo mostramos al usuario.
 */
function doSearch(){
	var execute = {"type":"data","asynchronous":false,"class":$F('class'),"do":"filter","sessionclass":$F('sessionclass'),"_descripcion":$F('_descripcion')};
	queryJson(execute);
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
				var imgLapiz = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/edit.png'});
				
				var tr = new Element('TR',{id:'tr_rowdata_'+item.clave,title:'click para editar','class':'rowDataOff'});
				var td1= new Element('TD',{'class':'colIcon'} );
				var td2= new Element('TD',{'class':'colSort'} );
				var td3= new Element('TD',{'class':'colSort'} );
				var td4= new Element('TD',{'class':'colSort'} );
				

				tr.insert(td1.update(imgLapiz));
				tr.insert(td2.update(item.clave));				
				tr.insert(td3.update(item.valor));
				tr.insert(td4.update(item.descripcion));
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
 * Parametros de la empresa (app_config)
 */
function doAppConfig(){
	var params = {"handle":'',"type":"application","class":"appconfig","do":"start","title":"Par&aacute;metros de la empresa","width":"750","height":"500","closable":true,"modal":true };
	executeApplication(params);
}


/**
 * Enviamos el formulario de datos
 */
function doAceptar(){
	var error = "";
	if ( $('clave').value.replace(/^\s*/, '')=="" )
		error += ">Clave\n";
	if ( $('valor').value.replace(/^\s*/, '')=="" )
		error += ">Valor\n";
	if ( $('descripcion').value.replace(/^\s*/, '')=="" )
		error += ">Descricion\n";
	if ( $('fecha').value.replace(/^\s*/, '')=="" )
		error += ">Fecha\n";
	
	
	if ( error ){
		alert ("Los siguiente campos no son válidos:\n"+error);
		return false;
	}	
	
	$('do').value = 'doUpdate';
	$('fapplication').submit();
	return false;
}


/**
 * Cancelar y volver nos deja otra vez en el listado
 */
function doCancelar(){
	$('do').value = 'listAll';
	$('fapplication').submit();
	return false;
}
