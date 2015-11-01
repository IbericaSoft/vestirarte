/**
 * Gestion Pedidos Online 
 */
function onStartApplication(){
	//foco por defecto
	$j("#_pedido").focus();	 
	$j("#_desde").datepicker();
	$j("#_hasta").datepicker();
	$j("#_desde").click( function(){$j(this).val("");} );
	$j("#_hasta").click( function(){$j(this).val("");} );	
	
	if ( PAGE_NAME=='view' ){		
		if ( $j('#estado').val()=="FINALIZADO"||$j('#estado').val()=="RECHAZADO" )
			$j('#btAvanzar').css("display","none");
	}
}

/**
 */
function doSearch(){
	var execute = {"type":"data","asynchronous":false,"class":$F('class'),"do":"filter","sessionclass":$F('sessionclass'),
			"_pedido":$F('_pedido'),
			"_cliente":$F('_cliente'),
			"_email":$F('_email'),
			"_nifcif":$F('_nifcif'),
			"_estado":$F('_estado'),
			"_desde":$F('_desde'),
			"_hasta":$F('_hasta')};
	queryJson(execute);
}


/**
 */
function refreshList(pagination,json){
	//crea con los datos recibidos json la lista de datos
	var rows = 0;
	json.each( 
			function(item){
				rows++;
				var red   = new Element('IMG',{title:'pendiente',src:WEB_PATH+'/applications/_commons/_images/bullet-red-icon.png'});
				var green = new Element('IMG',{title:'finalizado',src:WEB_PATH+'/applications/_commons/_images/bullet-green-icon.png'});
				var white = new Element('IMG',{title:'rechazado',src:WEB_PATH+'/applications/_commons/_images/bullet-white-icon.png'});
				var tr = new Element('TR',{id:'tr_rowdata_'+item.id,title:'ver datos','class':'rowDataOff'});
								
				switch ( item.estado ){
				case 'FINALIZADO': 
					tr.insert(new Element('TD',{'class':'colIcon'} ).update(green));
					break;
				case 'PEDIDO':
				case 'ENVIADO':
					tr.insert(new Element('TD',{'class':'colIcon'} ).update(red));
					break;
				default:
					tr.insert(new Element('TD',{'class':'colIcon'} ).update(white));
				}

				tr.insert(new Element('TD',{'class':'colIcon'} ).update(item.id));
				tr.insert(new Element('TD',{'class':'colIcon'} ).update(item.estado));
				tr.insert(new Element('TD',{'class':'colIcon'} ).update(item.cliente));
				tr.insert(new Element('TD',{'class':'colIcon'} ).update(item.fpedido));
				tr.insert(new Element('TD',{'class':'colIcon'} ).update(item.fenvio));
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
 * 
 */
function doNew(){
}

/**
 * Petici�n para imprimir la vista actual
 */
function doPrint(){
	if ( PAGE_NAME=='view'){
		$('do').value = 'pdfPrintAlmacen';
		$('channel').value="PDF";
		$('fapplication').method='POST';
		$('fapplication').submit();
	}else{
		$('do').value = 'pdfGenericList';
		$('channel').value="PDF";
		$('fapplication').method='POST';
		$('fapplication').submit();
	}
	return false;
}

/**
 * Exportar datos de esta vista a formato excel
 */
function doExport(){
	$('do').value="exportCSV";
	$('channel').value="csv";
	$('fapplication').method='POST';
	$('fapplication').submit();
}

/**
 * Crear un acceso directo para esta vista
 */
function doLink(){
	var title = prompt('Nombre del acceso directo...', 'Pedidos');
	if ( !title )
		return;
	var win = getWindowsHandle(WINDOWS_HANDLE);
	var img = WEB_PATH+'/applications/pedidosweb/images/pedidos.png';
	
	var filter,doAc;
	if ( PAGE_NAME == 'list' ){
		filter = '&_pedido='+$F('_pedido')+'&_cliente='+$F('_cliente')+'&_nifcif='+$F('_nifcif')+'&_email='+$F('_email')+'&_estado='+$F('_estado')+'&_desde='+$F('_desde')+'&_hasta='+$F('_hasta');
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
 * Enviamos el formulario de datos para actualizar
 */
function doAceptar(){
	blockScreeen();
	$('do').value = 'doUpdate';
	$('fapplication').method='POST';
	$('channel').value="html";
	$('fapplication').submit();
	return false;
}


/**
 * Cancelar y volver nos deja otra vez en el listado
 */
function doCancelar(){
	blockScreeen();
	$('do').value = 'listAll';
	$('fapplication').method='POST';
	$('channel').value="html";
	$('fapplication').submit();
	return false;
}

/**
 * Avanza el pedido al siguiente estado
 */
function doAvanzar(){	
	$j('#avanzar_estado').val("OK");
	doAceptar();
	return false;
}
