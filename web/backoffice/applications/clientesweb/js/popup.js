/**
 * Gestion Clientes Estándar. Utilidad de búsqueda popup
 */
function onStartApplication(){
	$j('#_cliente').focus();
	$j(document).bind('keyup', 'return', function(e){
		console.log("evento ENTER capturado");
		if($j("#_cliente").val().length>2||$j("#_telefonos").val().length>2){ 
			doSearchPopup();
			return false;
		}
	});
}

/**
 * 
 */
function doSearchPopup(){
	blockScreeen(null);
	//refreshList = function(arguments,arguments){ //sobre-escritura forzada del metodo. refreshList ya esta declarada en un JS, pero aqui reimplementamos el metodo
	//	refreshListPopup(arguments,arguments);
	//}
	var execute = {"type":"data","asynchronous":false,"class":$F('class'),"do":"filter","sessionclass":$F('sessionclass'),"_cliente":$F('_cliente'),"_telefonos":$F('_telefonos')};
	queryJson(execute);
}

/**
 * Refresco de los resultado desde un JSON de datos del servidor
 * @param pagination
 * @param json
 */
function refreshList(pagination,json){
	//si no hay datos lo indicamos al pie de la ventana
	if ( !$j(json).length ) {
		var row = $j('<tr/>',{class:'pagination'}).appendTo( $j("#tbCount") );
		$j('<td/>',{class:'pagination'}).html('--- <b>Sin resultados</b> ---').appendTo( row );
		return;
	}
	
	//Iteracion por el array json de datos
	$j.each(json, function(i, item) {
		var info = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/ballon.png',title:item.observaciones});
		var edit = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/edit.png',title:'clic para ver cliente'});
		var lock = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/lock.png',title:'cliente inhabilitado'});		
		var row = $j('<tr/>',{class:'rowDataOff',id:'tr_rowdata_'+item.id,title:'click para ver cliente'}).appendTo($j("#tbLista"));
		if ( item.estado=='ACT' )
			$j('<td/>',{class:'colSort'}).append(edit).appendTo(row);
		else
			$j('<td/>',{class:'colSort'}).append(lock).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.cliente+"&nbsp;").appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.telefonos+"&nbsp;").appendTo(row);
		$j(row).click(function(){
			queryJson({"type":"data","asynchronous":false,"class":$j('#class').val(),"do":"doEdit","sessionclass":"","id":item.id,"channel":"json"});
			close();
		});
	});
	
	//espacio en la ultima fila
	var row = $j('<tr/>',{class:'rowDataOff'}).appendTo( $j("#tbLista") );
	$j('<td/>',{class:'colSort',colspan:'10'}).html('&nbsp;').appendTo(row);
	
	//Datos de paginacion
	var row = $j('<tr/>',{class:'pagination'}).appendTo( $j("#tbCount") );
	$j('<td/>',{class:'pagination'}).html('<b>'+pagination+'</b>').appendTo( row );
	
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

function close(){
	getWindowsHandle(WINDOWS_HANDLE).hide();
	getWindowsHandle(WINDOWS_HANDLE).destroy();
}