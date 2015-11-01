/**
 * Gestion Articulos de Tienda
 */
function onStartApplication(){
	$j("#_desde").datepicker();
	$j("#_hasta").datepicker();
	$j("#_desde").click( function(){$j(this).val("");} );
	$j("#_hasta").click( function(){$j(this).val("");} );
		
	//radios
	$j( "#choose" ).buttonset();	
	
	$j(".fileupload").each(function(){
		//Efectos
		$j(this).on("dragover",function(){	
			$j(this).addClass("dragOver").removeClass("dragOut");
			console.log("Evento dragover");
		}).on("dragleave",function(){
			$j(this).addClass("dragOut").removeClass("dragOver");
			console.log("Evento dragleave");
		}).on("drop",function(){
			$j(this).addClass("drop").removeClass("dragOver").removeClass("dragOut");
			console.log("Evento drop");
		}) ;
		$j(this).find("span").click(function(){$j(this).parent().parent().find("input").click();}); //trigger para lanzar el dialogo upload al pinchar sobre el titulo del contenedor de los uploads
		//upload
		$j(this).fileupload({
			url: ".",
			dropZone: $j(this).find("div")[1],
		    dataType: 'json',
		    formData: {"channel":"json","class":"Upload","folder":$j(this).attr("folder"),"do":"save"},
		    add: function (e, data) {
		    	blockScreeen();
		    	for (i=0;i<data.files.length;i++){
		       		console.log( data.files[i].name );
		       		$j($j(this).find("div")[2]).text( "Subiendo..." );			       		
		       		data.submit();
		       	}
		    },
		    done: function (e,data){
		    	console.log(data);
		    	unBlockScreen();
		    	if ( data.result.result=="ok" ){
			    	//todo ok
		    		console.log ( "Upload OK" );
	    			$j($j(this).find("div")[2]).text( data.result.file );
	    			$j($j(this).find("div")[1]).html("");
	    			$j($j(this).find("input")[1]).val( data.result.file );
	    			if ( $j(this).attr("type")=="image" )
	    				$j('<img/>',{class:"fotos",title:data.result.file,src:$j(this).attr("folder")+data.result.file}).appendTo( $j(this).find("div")[1] );
	    			if ( $j(this).attr("type")=="file" )
	    				$j('<img/>',{class:"fotos",title:data.result.file,src:$j(this).attr("folder")+"pdf.png"}).appendTo( $j(this).find("div")[1] );
	    			if ( $j(this).attr("type")=="xml" )
	    				$j('<img/>',{class:"fotos",title:data.result.file,src:$j(this).attr("folder-icon")+"xml.png"}).appendTo( $j(this).find("div")[1] );	    			
		    	} else {
		    		console.log ( "Upload KO" );
		    		$j($j(this).find("div")[2]).text( "" );
		    		showAlert ( data.result.description );
		    	}
		    }
		});
	
	});
}

/**
 */
function doSearch(){
	blockScreeen();
	var execute = {"type":"data","asynchronous":false,"class":$F('class'),"do":"filter","sessionclass":$F('sessionclass'),
			"_desde":$F('_desde'),"_hasta":$F('_hasta'),"_estado":$F('_estado')};
	queryJson(execute);
	return false;
}


/**
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
		var row = $j('<tr/>',{class:'rowDataOff',id:'tr_rowdata_'+item.id,title:'click para ver ficha'}).appendTo($j("#tbLista"));
		
		$j('<td/>',{class:'colSort'}).append(edit).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.fecha).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.operacion).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.modulo).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.resultado).appendTo(row);
		$j('<td/>',{class:'colSort'}).append(item.ffin).appendTo(row);
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
	var title = prompt('Nombre del acceso directo...', 'Migracion');
	if ( !title )
		return;
	var win = getWindowsHandle(WINDOWS_HANDLE);
	var img = WEB_PATH+'/applications/migracion/images/migracion.png';
	
	var filter,doAc;
	if ( PAGE_NAME == 'list' ){
		filter = "&_desde="+$F('_desde')+"&_hasta="+$F('_hasta')+"&_estado="+$F('_estado');
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
}

/**
* Baja de datos
*/
function doEliminar(){
}

/**
 * Cancelar y volver nos deja otra vez en el listado
 */
function doCancelar(){
	sendForm("listAll","html");
}

/**
 * Avanzar
 */
function doFordward(){
	
	if ( $j("#fichero").length ) {
		if ($j("#fichero").val()=="" ){			
			showAlert("Se necesita un fichero XML que procesar");
			return false;
		}
		if ($j("#fichero").val().indexOf(".xml")==-1 ){
			showAlert("El fichero tiene que ser un XML");
			return false;
		}
	}
	
	if ( $j("#migration").val()=="RUN" )
		$j("#module").val( $j("#choose :checked").val());
	
	sendForm("doFordward","html");
}



function doDownloadXML(){
	sendForm("doDownload","xml");
}

function doDownloadLOG(){
	sendForm("doDownloadLog","log");
}
