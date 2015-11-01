/**
 * Gestion Administradores/Gestores/Vendedores. Utilidad de busqueda
 */
function onStartApplication(){
	$('_nombre').focus();
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
	var execute = {"type":"data","asynchronous":false,"class":$F('class'),"do":"filter","sessionclass":$F('sessionclass'),"_nombre":$F('_nombre')};
	queryJson(execute);
	return false;
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
				
				var tr = new Element('TR',{id:item.id,title:'Click para seleccionar','class':'rowDataOff',nombre:item.nombre,perfil:item.desc_perfil});
				var td1= new Element('TD',{'class':'colIcon',title:(item.estado=='DES')?"Atencion: Deshabilitado":""} );
				var td2= new Element('TD',{'class':'colSort'} );
				var td3= new Element('TD',{'class':'colSort'} );

				if ( item.estado=='ACT' )
					tr.insert(td1.update('&nbsp;'));
				else
					tr.insert(td1.update(imgLock));
				tr.insert(td2.update(item.nombre));				
				tr.insert(td3.update(item.desc_perfil));

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

/**
 * Invocamos al método callback del padre (la ventana) de esta peticion
 */
function callBack(json){
	win = getWindowsHandle(PARENT);
	json.each( 
			function(items){
				eval( "$(win.content).contentWindow."+CALLBACK+"(items)" );
			}
	);
}

/**
 * Recuperamos los datos del listado y los servimos via JSON
 */
function doSelection(item){
	var json = [{"id":item.getAttribute("id"),"nombre":item.getAttribute("nombre"),"perfil":item.getAttribute("perfil")}];
	callBack(json);
	close();
}
