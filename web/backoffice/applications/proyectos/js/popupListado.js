/**
 * Gestion Obras. Utilidad de búsqueda
 */
function onStartApplication(){
	$('_obra').focus();
}

function close(){
	getWindowsHandle(WINDOWS_HANDLE).hide();
	getWindowsHandle(WINDOWS_HANDLE).destroy();
}

/** sobre escribo este metodo por que aqui no editamos */
function controlRows(){
	$$('tr.rowDataOff').each(function(item){
		if ( item.id=="" ) return;
		$(item.id).onmouseover = function(){ item.className = 'rowDataOn'; };
		$(item.id).onmouseout  = function(){ item.className = 'rowDataOff'; };		
		$(item.id).onclick = function(){
			doSelection(item);
		};
	});
}


/**
 * Metodo para la búsqueda en esta aplicación. Prepara la invocación a la clase con los parametros del filtro a utilizar.
 * Si la respuesta del servidor es correcta se procesa el metodo callBack que tiene los datos de vuelta, si por el contrario
 * hay un error, lo mostramos al usuario.
 */
function doSearch(){
	var execute = {"type":"data","asynchronous":false,"class":$F('class'),"do":"externFilter","sessionclass":$F('sessionclass'),"_obra":$F('_obra'),"_cliente":$F('_cliente'),"_filtro_especial":$F('_filtro_especial')};
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
				var tr = new Element('TR',{title:'Click para seleccionar','class':'rowDataOff','id':item.id,'proyecto':item.proyecto,'cliente':item.cliente,'razon':item.razon,'importe':item.imponible,'id_cliente':item.id_cliente,'direccion':item.direccion});
				var td1= new Element('TD',{'class':'colSort'} );
				var td2= new Element('TD',{'class':'colSort'} );
				var td3= new Element('TD',{'class':'colSort'} );
				
				tr.insert(td1.update(item.estado));
				tr.insert(td2.update(item.proyecto));
				tr.insert(td3.update(item.cliente));				


				$('tbLista').insert(tr);
			}	
	);
	
	//Fila vacia para crear espacio
	var tr = new Element('TR',{'class':'rowDataOff'});
	var td = new Element('TD',{'class':'colSort','colspan':10}).update('&nbsp;');
	tr.insert(td);
	$('tbLista').insert(tr);
	
	//anexa eventos a las filas
	controlRows();
}

function callBack(json){
	win = getWindowsHandle(PARENT);
	json.each( 
			function(items){
				eval( "$(win.content).contentWindow."+CALLBACK+"(items)" );
			}
	);
}

/**
 * Pedidos los datos del registro via JSON
 */
function doSelection(item){
	var json = [{"id":item.getAttribute("id"),"cliente":item.getAttribute("cliente"),"id_cliente":item.getAttribute("id_cliente"),"importe":item.getAttribute("importe"),"direccion":item.getAttribute("direccion")}];
	callBack(json);
	close();
}
