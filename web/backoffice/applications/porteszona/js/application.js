/**
 * Gestion PortesZonas
 */
function onStartApplication(){
	if ( PAGE_NAME=='list' ){
		//autocomplete pais
		$j("#_pais").focus();
		$j("#_id_pais").click( function(){$j(this).val("");$j( "#_pais" ).val('')} );
		$j("#_pais").click( function(){$j(this).val("");$j( "#_id_pais" ).val('')} );
		$j( "#_pais" ).autocomplete({
			minLength: 2,
			source: listaPais,
			select: function(event, ui) {        	
				$j( "#_id_pais" ).val( ui.item.id );
				$j( "#_pais" ).val( ui.item.label );
			}
		});
		//autocomplete provincias
		$j("#_id_provincia").click( function(){$j(this).val("");$j( "#_provincia" ).val('')} );
		$j("#_provincia").click( function(){$j(this).val("");$j( "#_id_provincia" ).val('')} );
		$j( "#_provincia" ).autocomplete({
			minLength: 2,
			source: listaProvincias,
			select: function(event, ui) {        	
				$j( "#_id_provincia" ).val( ui.item.id );
				$j( "#_provincia" ).val( ui.item.label );
			}
		});
	}
	
	if ( PAGE_NAME=='edit' ){
		$j('#zona').focus();
		$j('#id_pais').change(function(){
			$j('#seleccion').empty();
			$j(listaProvincias).each(function(i,item){
				if ( item.id_pais==$j('#id_pais').val() )
					$j('#seleccion').append( $j('<option value='+item.id+'>'+item.provincia+'</option>') );
			});
		});
		$j('#seleccion').dblclick(function(){
			if ( !$j('#seleccion option:selected') ) return;
			var copy = $j('#seleccion option:selected');
			$j('#provincias').append($j(copy).clone());
			$j(copy).remove();
		});
		$j('#id_pais').trigger("change");//por si viene algo selecionado
		$j('#provincias').dblclick(function(){
			if ( !$j('#provincias option:selected') ) return;
			var copy = $j('#provincias option:selected');
			$j('#seleccion').append($j(copy).clone());
			$j(copy).remove();
		});
		
		if ( $j("#id").val()=="" ){
			$j("#btEliminar").css("display","none");
			$j(".boxMenuButtons").css("display","none");
		}
	}
}

/**
 * Metodo para la búsqueda en esta aplicación. Prepara la invocación a la clase con los parametros del filtro a utilizar.
 * Si la respuesta del servidor es correcta se procesa el metodo callBack que tiene los datos de vuelta, si por el contrario
 * hay un error, lo mostramos al usuario.
 */
function doSearch(){
	blockScreeen();
	var execute = {"type":"data","asynchronous":false,"class":$F('class'),"do":"filter","sessionclass":$F('sessionclass'),
			"_id_zona":$F('_id_zona'),"_id_pais":$F('_id_pais'),"_id_provincia":$F('_id_provincia'),"_estado":$F('_estado')};
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
	//si no hay datos lo indicamos al pie de la ventana
	if ( !$j(json).length ) {
		var row = $j('<tr/>',{class:'pagination'}).appendTo( $j("#tbCount") );
		$j('<td/>',{class:'pagination'}).html('--- <b>Sin resultados</b> ---').appendTo( row );
		return;
	}
	
	//Iteracion por el array json de datos
	$j.each(json, function(i, item) {	
		var row = $j('<tr/>',{class:'rowDataOff',id:'tr_rowdata_'+item.id,title:'click para ver ficha'}).appendTo($j("#tbLista"));
		$j('<td/>',{class:'colIcon '+(( item.estado=='ON' )?'filaactiva':'filainactiva')}).append("&nbsp;").appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.zona).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.provincias).appendTo(row);
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
	var title = prompt('Nombre del acceso directo...', 'PortesZona');
	if ( !title )
		return;
	var win = getWindowsHandle(WINDOWS_HANDLE);
	var img = WEB_PATH+'/applications/porteszona/images/zonas.png';
	
	var filter,doAc;
	if ( PAGE_NAME == 'list' ){
		filter = '&_id_pais='+$F('_id_pais')+'&_id_zona='+$F('_id_zona')+'&_id_provincia='+$F('_id_provincia')+'&_estado='+$F('_estado');
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
	
	if ( $j('#zona').val().replace(/^\s*/, '')=="" ){
		error += "<li>Hay que dar un nombre a la zona</li>";
		$j('#zona').addClass("error");
	}
	
	if ( $j('#provincias option').length==0 ){
		error += "<li>La zona tiene que abarcar alguna Provincia</li>";
		$j('#provincias').addClass("error");
	}
	$j('#provincias option').prop('selected', true);
	
	if ( $j('#estado').val().replace(/^\s*/, '')=="" ){
		error += "<li>Estado</li>";
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
	$j( "<div>Se va a intentar eliminar este porte\n&iquest;Seguimos adelante?</div>" ).dialog({
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