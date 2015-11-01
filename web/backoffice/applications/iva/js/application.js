/**
 * Gestion IVAs
 */
function onStartApplication(){
	if ( PAGE_NAME=='edit' && $j("#id").val()=="" ){
		$j("#btEliminar").css("display","none");
		$j(".boxMenuButtons").css("display","none");
	}
	$j("#descripcion").focus();
}

/**
 * No tiene busqueda por su simpleza
 */
function doSearch(){
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
		var edit = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/edit.png',title:'click para ver ficha'});
		var lock = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/lock.png',title:'inactivo'});		
		var row = $j('<tr/>',{class:'rowDataOff',id:'tr_rowdata_'+item.id,title:'click para ver ficha'}).appendTo($j("#tbLista"));
		
		if ( item.estado=='ON' )
			$j('<td/>',{class:'colIcon'}).append(edit).appendTo(row);
		else
			$j('<td/>',{class:'colIcon'}).append(lock).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.descripcion).appendTo(row);
		$j('<td/>',{class:'colNumber'}).append(item.iva).appendTo(row);
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
	
}

/**
 * Enviamos el formulario de datos
 */
function doAceptar(){
	var error = "";
	$j(".error").each( function(){ $j(this).removeClass("error");});
	
	if ( $j('#descripcion').val().replace(/^\s*/, '')=="" ){
		error += "<li>Descripci&oacute;n</li>";
		$j('#descripcion').addClass("error");
	}
	
	if ( $j('#iva').val().replace(/^\s*/, '')=="" || parseFloat($j('#iva').val())==0 ){
		error += "<li>IVA</li>";
		$j('#iva').addClass("error");
	}
	
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
	$j( "<div>Se va a intentar eliminar este IVA \n&iquest;Seguimos adelante?</div>" ).dialog({
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
