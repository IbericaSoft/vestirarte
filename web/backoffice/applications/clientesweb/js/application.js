/**
 * Gestion Clientes Est�ndar
 */
function onStartApplication(){
	//foco por defecto
	$j("#_nombre").focus();	 
	$j("#_desde").datepicker();
	$j("#_hasta").datepicker();
	$j("#_desde").click( function(){$j(this).val("");} );
	$j("#_hasta").click( function(){$j(this).val("");} );

	$j(".telephone").maskMoney({thousands:'', decimal:'', allowZero:false, precision:0})
	
	//autocomplete
	if ( $j("#_id_cliente").length ){
		$j("#_id_cliente").click( function(){$j(this).val("");$j( "#_nombre" ).val('')} );
		$j( "#_nombre" ).autocomplete({
			minLength: 4,
			source: listCli,
			select: function(event, ui) {        	
				$j( "#_nombre" ).val( ui.item.label );
				$j( "#_id_cliente" ).val( ui.item.id );
			}
		});
	}
	
	if ( $j("#_email").length ){
		$j( "#_email" ).autocomplete({
			minLength: 2,
			source: listMail,
			select: function(event, ui) {        	
				$j( "#_email" ).val( ui.item.label );
			}
		});
	}
	
	//habilitamos el boton eliminar si hay registro
	if ( PAGE_NAME=='edit' && $j("#id").val()=="" ){
		$j("#btEliminar").css("display","none");
		$j(".boxMenuButtons").css("display","none");
	}
	
	if ( PAGE_NAME=='edit' ){
		refreshPaisZona( $j('#id_pais'), $j('#id_provincia'));
		$j('#id_pais').trigger("change");
		$j('#id_provincia').val( $j('#id_provincia').attr("auto-selection") );
		refreshPaisZona( $j('#f_id_pais'), $j('#f_id_provincia'));
		$j('#f_id_pais').trigger("change");
		$j('#f_id_provincia').val( $j('#f_id_provincia').attr("auto-selection") );
	}
}

/**
 * Metodo para la b�squeda en esta aplicaci�n. Prepara la invocaci�n a la clase con los parametros del filtro a utilizar.
 * Si la respuesta del servidor es correcta se procesa el metodo callBack que tiene los datos de vuelta, si por el contrario
 * hay un error, lo mostramos al usuario.
 */
function doSearch(){
	blockScreeen(null);
	var execute = {"type":"data","asynchronous":false,"class":$F('class'),"do":"filter","sessionclass":$F('sessionclass'),
			"_id_cliente":$F('_id_cliente'), "_nombre":$F('_nombre'), "_telefono":$F('_telefono'), "_email":$F('_email'), "_estado":$F('_estado'), "_desde":$F('_desde'),"_hasta":$F('_hasta')};
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
		var edit = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/edit.png',title:'clic para ver ficha'});
		var lock = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/lock.png',title:'cliente inhabilitado'});		
		var row = $j('<tr/>',{class:'rowDataOff',id:'tr_rowdata_'+item.id,title:'click para ver cliente'}).appendTo($j("#tbLista"));

		if ( item.estado=='ON' )
			$j('<td/>',{class:'colSort'}).append(edit).appendTo(row);
		else
			$j('<td/>',{class:'colSort'}).append(lock).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.nombre).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.telefono+"&nbsp;").appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.email+"&nbsp;").appendTo(row);
		$j(row).mouseover(function(){$j(this).removeClass('rowDataOff').addClass('rowDataOn');
		}).mouseout(function(){	$j(this).removeClass('rowDataOn').addClass('rowDataOff');
		}).click(function(){$j("#id").val( $j(this).attr('id').replace('tr_rowdata_','') );	sendForm('doEdit','html'); });
	});
	
	//espacio en la ultima fila
	var row = $j('<tr/>',{class:'rowDataOff'}).appendTo( $j("#tbLista") );
	$j('<td/>',{class:'colSort',colspan:'10'}).html('&nbsp;').appendTo(row);
	
	//Datos de paginacion
	var row = $j('<tr/>',{class:'pagination'}).appendTo( $j("#tbCount") );
	$j('<td/>',{class:'pagination'}).html('<b>'+pagination+'</b>').appendTo( row );
}

/**
 * Petici�n de la pantalla de alta de datos
 */
function doNew(){
	sendForm('doNew','html');
}

/**
 * Petici�n para imprimir la vista actual
 */
function doPrint(){
	sendForm("doListPrint","pdf");
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
	var title = prompt('cliente del acceso directo...', 'ClientesWeb');
	if ( !title )
		return;
	var win = getWindowsHandle(WINDOWS_HANDLE);
	var img = WEB_PATH+'/applications/clientesweb/images/clientes.png';
	
	var filter,doAc;
	if ( PAGE_NAME == 'list' ){
		filter = "&_nombre="+$F('_nombre')+"&_telefono="+$F('_telefono')+"&_email="+$F('_email')+"&_estado="+$F('_estado')+"&_desde="+$F('_desde')+"&_hasta="+$F('_hasta');
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
	$j(".error").each( function(){ $j(this).removeClass("error");});
	
	if ( $j('#nombre').val().replace(/^\s*/, '')=="" ){
		error += "<li>Cliente</li>";
		$j('#nombre').addClass("error");
	}
	
	if ( $j('#email').val().replace(/^\s*/, '')=="" || !isValidEmailAddress($j('#email').val()) ){
		error += "<li>E-mail no es correcto</li>";
		$j('#email').addClass("error");
	}
	
	if ( $j('#password').val().replace(/^\s*/, '')=="" ){
		error += "<li>Contrase&ntilde;a</li>";
		$j('#password').addClass("error");
	}else{
		if ( $j('#password').val().length < 3 ){
			error += "<li>Contrase&ntilde;a corta (min. 4)</li>";
			$j('#password').addClass("error");
		}
	}
	
	if ( $j('#estado').val().replace(/^\s*/, '')=="" ){
		error += "<li>Estado</li>";
		$j('#estado').addClass("error");
	}
	
	if ( $j('#suscripcion').val().replace(/^\s*/, '')=="" ){
		error += "<li>Recibir informaci&oacute;n</li>";
		$j('#suscripcion').addClass("error");
	}
	
	if ( $j('#direccion').val().replace(/^\s*/, '')=="" ){
		error += "<li>Direccion de env&iacute;o</li>";
		$j('#direccion').addClass("error");
	}	
	
	if ( $j('#poblacion').val().replace(/^\s*/, '')=="" ){
		error += "<li>Poblaci&oacute;n de env&iacute;o</li>";
		$j('#poblacion').addClass("error");
	}
	
	if ( $j('#id_provincia').val().replace(/^\s*/, '')=="" ){
		error += "<li>Provincia de env&iacute;o</li>";
		$j('#id_provincia').addClass("error");
	}
	
	if ( $j('#cpostal').val().replace(/^\s*/, '')=="" ){
		error += "<li>Codigo Postal de env&iacute;o</li>";
		$j('#cpostal').addClass("error");
	}
	
	if ( $j('#f_id_pais').val().replace(/^\s*/, '')!="" ){		
		if ( $j('#razon').val().replace(/^\s*/, '')=="" ){
			error += "<li>Razon social</li>";
			$j('#razon').addClass("error");
		}
		
		if ( $j('#nifcif').val().replace(/^\s*/, '')=="" ){
			error += "<li>DNI/CIF</li>";
			$j('#nifcif').addClass("error");
		}else{
			if ( $j('#nifcif').val().length < 7 ){
				error += "<li>DNI/CIF no es correcto</li>";
				$j('#nifcif').addClass("error");
			}
		}
		
		if ( $j('#fdireccion').val().replace(/^\s*/, '')=="" ){
			error += "<li>Direcci&oacute;n de facturaci&oacute;n</li>";
			$j('#fdireccion').addClass("error");
		}	
		
		if ( $j('#fpoblacion').val().replace(/^\s*/, '')=="" ){
			error += "<li>Poblaci&oacute;n de facturaci&oacute;n</li>";
			$j('#fpoblacion').addClass("error");
		}
		
		if ( $j('#f_id_provincia').val().replace(/^\s*/, '')=="" ){
			error += "<li>Provincia de facturaci&oacute;n</li>";
			$j('#f_id_provincia').addClass("error");
		}
		
		if ( $j('#fcpostal').val().replace(/^\s*/, '')=="" ){
			error += "<li>Codigo Postal de facturaci&oacute;n</li>";
			$j('#fcpostal').addClass("error");
		}
	}
	
	
	if ( error ){
		$j( "<div>Los siguientes campos resaltados tienen alg&uacute;n error:<br><br>"+error+"<br></div>" ).dialog({
		      modal: true,
		      buttons: {
		        VALE: function(){ $j( this ).dialog( "close" ); }	        
		      }
		});
		return false;
	}	
	sendForm("doUpdate","html");
}

/**
* Baja de datos
*/
function doEliminar(){
	$j( "<div>Se va a intentar eliminar este cliente\n&iquest;Seguimos adelante?</div>" ).dialog({
	      modal: true,
	      buttons: {
	        SI: function() {
	        	sendForm("doDelete","html");
	        	$j( this ).dialog( "close" );
	        },
	        NO: function(){ $j( this ).dialog( "close" ); }	        
	      }
	});	
}

/**
 * Cancelar y volver nos deja otra vez en el listado
 */
function doCancelar(){
	sendForm("listAll","html");
}

/**
 * Combo para refrescar la lista de zonas/codigo/provincia en funcion del pais. listaZonas es un array json que tiene las zonas y su pais
 */
function refreshPaisZona(source,target){
	$j(source).change(function(){		
		$j(target).empty();
		$j(target).append('<option value="">--Seleccionar--</option>');
		$j(listaProvincias).each(function(i,item){
			if ( item.id_pais==$j(source).val() )
				$j(target).append( $j('<option value='+item.id+'>'+item.provincia+'</option>') );
		});
	});
}