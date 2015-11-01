/**
 * Gestion Tareas
 */
function onStartApplication(){
	$j("#fecha_limite").datepicker();

	if ( PAGE_NAME=='edit' && !$j("#id").val() ){
		$j(".boxMenuButtons").css("display","none");
	}
}


/**
 * Metodo para la búsqueda en esta aplicación. Prepara la invocación a la clase con los parametros del filtro a utilizar.
 * Si la respuesta del servidor es correcta se procesa el metodo callBack que tiene los datos de vuelta, si por el contrario
 * hay un error, lo mostramos al usuario.
 */
function doSearch(){
	var execute = {"type":"data","asynchronous":false,"class":$F('class'),"do":"filter","sessionclass":$F('sessionclass'),"_destinatario":$F('_destinatario'),"_prioridad":$F('_prioridad'),"_estado":$F('_estado')};
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
				var imgLock = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/padlock.gif'});
				var imgLapiz = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/lapiz.gif'});
				
				var tr = new Element('TR',{id:'tr_rowdata_'+item.id,title:'click para editar','class':'rowDataOff'});
				var td1= new Element('TD',{'class':'colIcon',title:(item.estado=='FIN')?"Finalizada":""} );
				var td2= new Element('TD',{'class':'colIcon'} );
				var td3= new Element('TD',{'class':'colSort'} );
				var td4= new Element('TD',{'class':'colSort'} );
				var td5= new Element('TD',{'class':'colNumber'} );
				var td6= new Element('TD',{'class':'colNumber'} );
				
				
				if ( item.estado=='ACT' )
					tr.insert(td1.update('&nbsp;'));
				else
					tr.insert(td1.update(imgLock));
				tr.insert(td2.update(imgLapiz));
				tr.insert(td3.update(item.nombre));				
				tr.insert(td4.update(item.prioridad));
				tr.insert(td5.update(item.fecha_limite));
				tr.insert(td6.update(item.fecha_fin));

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
	sendForm("doNew","html");
}

/**
 * Petición para imprimir la vista actual
 */
function doPrint(){
	if ( PAGE_NAME=='list')
		sendForm("doListPrint","pdf");
	if ( PAGE_NAME=='edit')
		sendForm("doDetailtPrint","pdf");
}

/**
 * Exportar datos de esta vista a formato excel
 */
function doExport(){
	$('do').value="exportCSV";
	$('channel').value="csv";
	$('fapplication').submit();
}

/**
 * Crear un acceso directo para esta vista
 */
function doLink(){
	var title = prompt('Nombre del acceso directo...', 'Tareas');
	if ( !title )
		return;
	var win = getWindowsHandle(WINDOWS_HANDLE);
	var img = WEB_PATH+'/applications/tareas/images/task.png';
	
	var filter,doAc;
	if ( PAGE_NAME == 'list' ){
		filter = '&_destinatario='+$F('_destinatario')+'&_prioridad='+$F('_prioridad')+'&_estado='+$F('_estado');
		doAc   = 'filter';
	} else {
		filter = '&id='+$F('id');
		doAc   = 'doEdit';	
	}	
	var icon = {icon_id:null,"class":$F('class'),"do":doAc,"parameters":filter,'width':win.width,'height':win.height,'top':win.options.top,'left':win.options.left,'closable':win.options.closable,'resizable':win.options.resizable,'maximize':win.options.maximizable,'minimize':win.options.minimizable,'itop':10,'ileft':10,'ititle':title,'icon':img,'title':title };
	var id = addDesktopIcon(title,img,10,10,icon);
	addMenuContext('icon_container_'+id,'Ejecutar aplicación','launchApp','executeIcon("'+id+'")');
	addMenuContext('icon_container_'+id,'Eliminar acceso directo','deleteLink','deleteIcon('+id+')');
	addMenuContext('icon_container_'+id,'Ver propiedades','propertiesLink','viewIconProperties('+id+')');
	repaintContextMenu('icon_container_'+id);
}

/**
 * Enviamos el formulario de datos
 */
function doAceptar(){
	var error = "";
	
	$('tarea').value = FCKeditorAPI.GetInstance("tarea").GetHTML();
	
	if ( $('id_usuario_target').value.replace(/^\s*/, '')=="" )
		error += ">Destinatario de la tarea\n";
	if ( $('descripcion').value.replace(/^\s*/, '')=="" )
		error += ">Descripción corta de la tarea\n";
	if ( $('prioridad').value.replace(/^\s*/, '')=="" )
		error += ">Prioridad de esta tarea\n";
	if ( $('tarea').value.replace(/^\s*/, '')==""||$('tarea').value=="<br>" )
		error += ">Texto de la tarea\n";
	if ( $('fecha_limite').value.replace(/^\s*/, '')=="" )
		error += ">Fecha límite para cumplir la tarea\n";
	
	if ( $F('estado')=='FIN' )
		if ( !confirm('Al finalizar la tarea se enviará un email al creador de la tarea para notificarle esta acción. ¿Seguimos adelante?') )
			return;
	
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
	if (!confirm("Se va a intentar eliminar esta tarea\n¿Seguimos adelante?"))
		return;
	$('do').value = 'doDelete';
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
