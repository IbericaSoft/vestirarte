/**
 * Gestion Proyectos
 */
function onStartApplication(){
	
	//spinner completado	%
	$j.widget( "ui.completado", $j.ui.spinner, {
	    _format: function( value ) { return value +  ' %'; },
	    _parse: function(value) { return parseInt(value); }
	});
	$j( "#_porcentaje_completado" ).completado({min:0,max:100,step:10}).attr('readonly', 'readonly');//.val("0 %");
	if ( !$j( "#_porcentaje_completado" ).val() ) $j( "#_porcentaje_completado" ).val("0 %");
	
	//spinner finalizan	semanas
	$j.widget( "ui.semanas", $j.ui.spinner, {
	    _format: function( value ) { return value + ' semana(s)'; },
	    _parse: function(value) { return parseInt(value); }
	});
	$j( "#_termina" ).semanas({min:0,max:36,step:1}).attr('readonly', 'readonly');//.val("0 semana(s)");
	if ( !$j( "#_termina" ).val() ) $j( "#_termina" ).val("0 semana(s)");
	
	//spinner modificado	%	
	$j.widget( "ui.modificado", $j.ui.spinner, {
	    _format: function( value ) { return value +  ' dias'; },
	    _parse: function(value) { return parseInt(value); }
	});
	$j( "#_fmodificacion" ).modificado({min:0,max:56,step:7}).attr('readonly', 'readonly');//.val("0 dias");
	if ( !$j( "#_fmodificacion" ).val() ) $j( "#_fmodificacion" ).val("0 dias");
	
	//no quiero que aparezcan las flechas de los spinners en el azul del tema de jquery ui	
	$j( "a.ui-state-default" ).css("background","#444");

	//fechas
	$j("#_desde").datepicker( {showWeek: true,  defaultDate: "+1w",
		changeMonth: true,
		numberOfMonths: 3,
		onClose: function( selectedDate ) {
		$j( "#_hasta" ).datepicker( "option", "minDate", selectedDate );
		}
	} );
	$j("#_hasta").datepicker( {showWeek: true, defaultDate: "+1w",
		changeMonth: true,
		numberOfMonths: 3,
		onClose: function( selectedDate ) {
		$j( "#_desde" ).datepicker( "option", "maxDate", selectedDate );
		}
	} );
	$j("#_desde").click( function(){$j(this).val("");} );
	$j("#_hasta").click( function(){$j(this).val("");} );

	//mascaras edicion
	new pMask();

	//pagina edicion/lectura
	if ( $j("#proyecto").length ){
		
	}

}

/**
 * Metodo para la búsqueda en esta aplicación. Prepara la invocación a la clase con los parametros del filtro a utilizar.
 * Si la respuesta del servidor es correcta se procesa el metodo callBack que tiene los datos de vuelta, si por el contrario
 * hay un error, lo mostramos al usuario.
 */
function doSearch(){
	var execute = {"type":"data","asynchronous":false,"class":$F('class'),"do":"filter","sessionclass":$F('sessionclass')
			,"_proyecto":$F('_proyecto')
			,"_cliente":$F('_cliente')
			,"_estado":$F('_estado')
			,"_desde":$F('_desde')
			,"_hasta":$F('_hasta')			
			,"_fmodificacion":$F('_fmodificacion').replace(" dias","")
			,"_termina":$F('_termina').replace(" semana(s)","")
			,"_porcentaje_completado":$F('_porcentaje_completado').replace(" %","")
	};
	queryJson(execute);
}

/**
 * Paginacion JSON de resultados
 * @param pagination informacion html de la paginacion
 * @param json array con los datos
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
		//var info = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/ballon.png',title:item.observaciones});
		//var contact = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/contact.png',title:item.contactos});
		var edit = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/edit.png',title:'clic para ver proyecto'});
		var lock = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/lock.png',title:'proyecto inhabilitado'});		
		var row = $j('<tr/>',{class:'rowDataOff',id:'tr_rowdata_'+item.proyecto,title:'click para ver proyecto'}).appendTo($j("#tbLista"));
		$j('<td/>',{class:'colSort'}).append(edit).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.proyecto).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.finicio).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.estado).appendTo(row);
		$j('<td/>',{class:'colSort'}).append("<div id='progress' class='graph'><div class='project-ok' id='bar' title='' style='width:"+item.porcentaje_completado+"%'><p>"+item.porcentaje_completado+"%</p></div></div>").appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.hitos_completados+"/"+item.hitos).appendTo(row);
		$j('<td/>',{class:'colSort'}).append("<div id='progress' class='graph' title='"+item.consumido+" de "+item.duracion+" d&iacute;as :: del "+item.finicio+" al "+item.ffin+"'><div class='project-stable' id='bar' porcen='"+item.porcentaje_consumido+"' style='width:"+item.porcentaje_consumido+"%'><p>"+item.porcentaje_consumido+"%</p></div></div>").appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.cliente).appendTo(row);
		$j('<td/>',{class:'colNumber'}).append(item.coste + "&euro;").appendTo(row);
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

/**
 * cambia el estilo de una barra de progreso en funcion de uno de sus atributos
 * -- en estos momentos no lo utilizamos --
 */
function fillColorProgressBars(){
	//resaltar la barra de progreso cuando los porcentajes este entre X e Y
	$j(".project-stable").filter(function(){ return $j(this).attr("porcen")>=75 && $j(this).attr("porcen") <=89}).removeClass("project-stable").addClass("project-warning");
	$j(".project-stable").filter(function(){ return $j(this).attr("porcen")>=90 && $j(this).attr("porcen") <=99}).removeClass("project-stable").addClass("project-alert");
	$j(".project-stable").filter(function(){ return $j(this).attr("porcen")==100}).removeClass("project-stable").addClass("project-end");
}

/**
 * Petición de la pantalla de alta de datos
 */
function doNew(){
	sendForm('doNew','html');
}

/**
 * Petición para imprimir la vista actual
 */
function doPrint(){
	if ( PAGE_NAME=='edit' && !$j('#proyecto').val() ){ 
		alert ("Solo se puede imprimir a partir de datos guardados");
		return;
	}
	if ( PAGE_NAME=='edit' )
		$j('#do').val('doPrint');
	else 
		$j('#do').val('doListPrint');
	
	sendForm($j('#do').val(),"pdf");
}

/**
 * Exportar datos de esta vista a formato excel
 */
function doExport(){
	sendForm("exportCSV","csv");
}

/**
 * Crear un acceso directo para esta vista
 */
function doLink(){
	var title = prompt('Nombre del acceso directo...', 'Proyectos');
	if ( !title )
		return;
	var win = getWindowsHandle(WINDOWS_HANDLE);
	var img = WEB_PATH+'/applications/proyectos/images/proyectos.png';
	
	var filter,doAc;
	if ( PAGE_NAME == 'list' ){
		filter = '&_proyecto='+$j('#_proyecto').val()+'&_cliente='+$j('#_cliente').val()+'&_estado='+$j('#_estado').val()+'&_porcentaje_completado='+$j('#_porcentaje_completado').val().replace(' %','')+'&_desde='+$j('#_desde').val()+'&_hasta='+$j('#_hasta').val()+'&_termina='+$j('#_termina').val().replace(' semana(s)','')+'&_fmodificacion='+$j('#_fmodificacion').val().replace(' dias','');
		doAc   = 'filter';
	} else {
		filter = '&id='+$j('#id');
		doAc   = 'doEdit';	
	}
	var icon = {icon_id:null,"class":$j('#class').val(),"do":doAc,"parameters":filter,'width':win.width,'height':win.height,'top':win.options.top,'left':win.options.left,'closable':win.options.closable,'resizable':win.options.resizable,'maximize':win.options.maximizable,'minimize':win.options.minimizable,'itop':10,'ileft':10,'ititle':title,'icon':img,'title':title };
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

	if ( $('id_cliente').value.replace(/^\s*/, '')=="" )
		error += ">Hay que seleccionar un cliente\n";
	if ( $('id_responsable').value.replace(/^\s*/, '')=="" )
		error += ">Hay que seleccionar un gestor\n";
	
	var currentTime = new Date();
	var month = currentTime.getMonth() + 1;
	var day = currentTime.getDate();
	var year = currentTime.getFullYear();

	if ( $('finicio').value.replace(/^\s*/, '')=="" )
		$('finicio').value = day +"/"+month+"/"+year;
		//error += ">Fecha de incio\n";
	if ( $('ffin').value.replace(/^\s*/, '')=="" )
		$('ffin').value = day +"/"+month+"/"+year;
		//error += ">Fecha de fin\n";
	
	$('condiciones').value = FCKeditorAPI.GetInstance("condiciones").GetHTML();
	$('historico').value = FCKeditorAPI.GetInstance("historico").GetHTML();
	
	if ( error ){
		alert ("Los siguientes campos tienen errores:\n"+error);
		return false;
	}	
	
	if ( !saveDetails() ){		
		alert ("Hay algún error en los detalles de la obra/pedido!!!\n\n->Recuerda que no puede haber cantidades con valor cero o negativo (solo en abonos)\n->Tampoco precios a cero\n ...por favor revisa los datos antes de salvar la obra/pedido");
		return false;
	}
	
	$('do').value = 'doUpdate';
	$('channel').value = 'html';
	$('fapplication').method='POST';
	$('fapplication').submit();
	return false;
}

/**
* Baja de datos
*/
function doEliminar(){
	if (!confirm("Se va eliminar este proyecto\n¿Seguimos adelante?")) 
		return;
	$('do').value = 'doDelete';
	$('channel').value="html";
	$('fapplication').submit();
	return false;
}

/**
 * Cancelar y volver nos deja otra vez en el listado
 */
function doCancelar(){
	$('do').value = 'listAll'; 
	$('channel').value="html";
	$('fapplication').method='POST';
	$('fapplication').submit();
	return false;
}


/**
 * Finalizar la obra cambiando el estado
 */
function doFinalizar(){
	if (!confirm("¿Marcar proyectos como finalizado?")) 
		return;
	$('do').value = 'doChangeStatus'; 
	$('channel').value="html";
	$('fapplication').method = 'POST';
	$('estado').value = 'FINALIZADO';
	$('fapplication').submit();
	return false;
}

/**
 * Muestra la ventana de caja para hacer un cobro del pedido
 */
function doCaja(){
	var params = {"handle":"","type":"application","class":'caja',"do":"doExternalNew","title":"Cobros por proyecto nº "+$F('proyecto')
		,"width":"700","height":"500","parameters":"id="+$F('id'),"closable":true,"modal":true };
	executeApplication(params);
	return false;
}

/**
 * Recarga la ventana con los datos de un PEDIDO para generar desde el un abono
 */
function doAbono(){
	$('do').value = 'doChangeToAbono';
	$('channel').value="html";
	$('fapplication').method = 'POST';
	$('id').value = $F('id');
	$('fapplication').submit();
	return false;
}

/** La obra-pedido tiene saldo pendiente pero por diversas casuisticas no es correcto
 * y lo queremos dejar a cero para que el pedido figure como pagado
 */
function doPendiente(){
	if (!confirm("¿El importe pendiente es de "+$F('pendiente')+"€. Saldamos este importe de forma manual?")) 
		return;
	$('do').value = 'doPendiente'; 
	$('channel').value="html";
	$('fapplication').method = 'POST';
	$('fapplication').submit();
	return false;
}

function soloLectura(){
	$('btAceptar').onclick = function(){
		$('do').value = 'doMiniUpdate';
		$('channel').value = 'html';
		$('fapplication').submit();
		return false;
	}
	
	$('btBuscadorClientes').disabled = true;
	$('btBuscadorGestor').disabled = true;
	$('btFechasObra').disabled = true;
	$('finicio').disabled = true;
	$('ffin').disabled = true;
}

/**
 * Dialogo buscar cliente
 * @returns {Boolean}
 */
function doBuscadorClientes(){
	var params = {"handle":"","type":"application","class":'clientesplus',"do":"doMiniSearch","title":"Buscador Clientes","width":"600","height":"350","parameters":"callBack=selectClient&parent="+WINDOWS_HANDLE,"closable":true,"modal":true };
	executeApplication(params);
	return false;
}

/** 
 * Callback de vuelta con los datos del cliente seleccionado 
 */
function selectClient(json){	
	$('id_cliente').value = json.id;
	$('cliente').value = json.cliente;
	return;
	$('cli_telefonos').value = cliente.telefonos;
	$('cli_email').value = cliente.email.toLowerCase();
	$('cli_direccion').value = cliente.direccion;
	$('cli_poblacion').value = cliente.poblacion;
	$('cli_provincia').value = cliente.provincia;
	$('cli_cpostal').value = cliente.cpostal;
}