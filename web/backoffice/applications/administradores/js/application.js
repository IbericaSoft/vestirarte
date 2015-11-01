/**
 * Gestion Administradores del sistema
 */
function onStartApplication(){
	//foco por defecto
	$j("#_nombre").focus();
	
	if ( PAGE_NAME=='edit' && $j("#id").val()=="" ){
		$j("#btEliminar").css("display","none");
		$j(".boxMenuButtons").css("display","none");
	}
		
	//autocomplete
	$j( "#_nombre" ).autocomplete({
		minLength: 2,
    	source: list,
        select: function(event, ui) {
        	//$j( "#_id_nombre" ).val( ui.item.id );
        	$j( "#_nombre" ).val( ui.item.label );
		}
	});
	
	//ocultar el boton close de las cajas modales de jquery-ui
	$j(".noclose").css("display","none");
	$j(".ui-dialog-titlebar-close").css("display","none");
}


/**
 * Metodo para la búsqueda en esta aplicación. Prepara la invocación a la clase con los parametros del filtro a utilizar.
 * Si la respuesta del servidor es correcta se procesa el metodo callBack que tiene los datos de vuelta, si por el contrario
 * hay un error, lo mostramos al usuario.
 */
function doSearch(){
	blockScreeen();
	var execute = {"type":"data","asynchronous":false,"class":$F('class'),"do":"filter","sessionclass":$F('sessionclass'),"_nombre":$F('_nombre'),"_estado":$F('_estado')};
	queryJson(execute);
	return false;
}


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
		var lock = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/lock.png',title:'inhabilitado'});		
		var row = $j('<tr/>',{class:'rowDataOff',id:'tr_rowdata_'+item.id,title:'click para ver ficha'}).appendTo($j("#tbLista"));
		
		if ( item.estado=='ACT' )
			$j('<td/>',{class:'colSort'}).append(edit).appendTo(row);
		else
			$j('<td/>',{class:'colSort'}).append(lock).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.nombre+"&nbsp;").appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.desc_perfil+"&nbsp;").appendTo(row);
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
 * Petición de la pantalla de alta de datos
 */
function doNew(){
	sendForm('doNew','html');
}

/**
 * Petición para imprimir la vista actual
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
	var title = prompt('Nombre del acceso directo...', 'Administradores');
	if ( !title )
		return;
	var win = getWindowsHandle(WINDOWS_HANDLE);
	var img = WEB_PATH+'/applications/administradores/images/administradores.png';
	
	var filter,doAc;
	if ( PAGE_NAME == 'list' ){
		filter = '&_nombre='+$F('_nombre')+'&_estado='+$F('_estado');
		doAc   = 'filter';
	} else {
		filter = '&id='+$j('#id').val();
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
		error += "<li>Nombre completo de usuario</li>";
		$j('#nombre').addClass("error");
	}
	
	if ( $j('#usuario').val().replace(/^\s*/, '')=="" ){
		error += "<li>Usuario(alias) de la cuenta</li>";
		$j('#usuario').addClass("error");
	}
	
	if ( $j('#password').val().replace(/^\s*/, '')=="" ){
		error += "<li>Password de la cuenta</li>";
		$j('#password').addClass("error");
	}
	
	if ( $j('#email').val().replace(/^\s*/, '')=="" ){
		error += "<li>Email de la cuenta</li>";
		$j('#email').addClass("error");
	}
	
	if ( $j('#id_perfil').val().replace(/^\s*/, '')=="" ){
		error += "<li>Perfil de la cuenta</li>";
		$j('#id_perfil').addClass("error");
	}
	
	if ( $j('#estado').val().replace(/^\s*/, '')=="" ){
		error += "<li>Estado de la cuenta</li>";
		$j('#estado').addClass("error");
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
	$j( "<div>Se va a intentar eliminar este administrador\n&iquest;Seguimos adelante?</div>" ).dialog({
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
