/**
 * Gestion Módulo
 */
function onStartApplication(){
	/**Listado*************************************************************************/
	if ( $j("body").hasClass("list") ){
		$j("#_articulos").focus();
		
		$j("input").keypress(function(event){
	    	if ( event.which == 13 ) {
	    	   grid1.pqGrid( "refreshDataAndView" );
	    	   event.preventDefault();
	    	}
	    });
	    $j("select").selectmenu( { change:function(event,ui){
	    	grid1.pqGrid( "refreshDataAndView" );
	    }});
		$j(".calendario").datepicker().change(function(e){
			grid1.pqGrid( "refreshDataAndView" );
		});		
		
	    var colModel = [
	        {title:"Id", dataIndx: "id", width:'50',dataType:"float"},            
	        {title:"Categoría", dataIndx: "categoria", width:'125',dataType:"string"},
	        {title:"Familia", dataIndx: "familia", width:'125',dataType:"string"},
	        {title:"SubFamilia", dataIndx: "subfamilia", width:'125',dataType:"string"},
	        {title:"Artículo", dataIndx: "articulo", width:'125', dataType:"string"},
	        {title:"Autor", dataIndx: "autor", width:'125', dataType:"string"},
	        {title:"Estado", dataIndx: "festado", width:'75',dataType:"string"},
	        {title:"Descripción", dataIndx: "descripcion", width:'200', dataType:"string"},	        
	        {title:"Foto",width:'75', dataIndx: "foto_1", dataType:"string", render:function( ui ){return "<img class='thum' src='"+PWEB_PATH+"/images/"+ui.rowData.foto_1+"'/>";}},
	        {title:"Precio", dataIndx: "precio", width:'75',dataType:"float",render:function( ui ){return ui.rowData.fprecio;}},
	        {title:"Oferta", dataIndx: "oferta", width:'75',dataType:"float",render:function( ui ){return ui.rowData.foferta;}},
	        {title:"Modificado", width:'125', dataIndx: "fmodificacion", dataType:"date",render:function( ui ){return ui.rowData.ffmodificacion;}},
	    	{title:"Gestor", width:'125', dataIndx: "gestor", dataType:"string"}
	    ];
	    
	    var dataModel = {
	    	cache: true,
	    	paging: 'local',
	    	sorting: 'local',
	    	rPP:20,
	    	rPPOptions:[20,40,100],
	    	dataType: 'json',
	    	getUrl: function(){
	    		blockScreeen("Cargando artículos ...");
	    		var data = "class="+$j("#class").val()+"&do=listAll&channel=json&sessionclass="+$j('#sessionclass').val()+"&"
	    			+ "_articulo="+$j('#_articulo').val() 
	    			+ "&" + "_categoria="+$j('#_categoria').val() 
	    			+ "&" + "_familia="+$j('#_familia').val()
	    			+ "&" + "_subfamilia="+$j('#_subfamilia').val()
	    			+ "&" + "_autor="+$j('#_autor').val();
	    		return { url: WEB_PATH+"?"+data };
	    	},
	    	getData: function(json){
	    		unBlockScreen();
	    		return { data: json }; 
	    	}
	    };
	    	    
	    /** cada vez que se ordena, nos quedamos con el campo utilizado y la dirección para enviarlo en ciertas operaciones de vuelta al servidor */
	    var sort = function(event,ui){
	    	$j("#filtro_campo").val(ui.dataModel.sortIndx);
	    	$j("#filtro_orden").val(ui.dataModel.sortDir);	    	
	    }
	    
	    /** al refrescar el grid, nosotros lo usamos para detectar el cambio de pagina */
	    var refresh = function(event,ui){
	    	$j("#filtro_pagina").val(ui.dataModel.curPage);
	    }
	    
	    /** click en una fila */
	    var rowClick = function(event,ui){	    	
	    	$j("#id").val( ui.dataModel.data[ui.rowIndx][0] );
	    	sendForm("doView");	    	
	    }
	    
	    var grid1 = $j("#grid").pqGrid({
	    	dataModel: $j.extend(true,dataModel,dataModelPHP),
	        colModel: colModel,
	        sort: sort,
	        rowClick: rowClick,
	        refresh: refresh,
	    	editable:false,    	
			bottomVisible : true,
	    	flexHeight:true,
	    	//flexWidth: true,
	    	width: 900,
	    	resizable: true,
	    	topVisible: false,
	    	//numberCell: false,
	    	//scrollModel: true,
	    	columnBorders: true,
	    	freezeCols: 1
	    });	    
	}
	
	/**Edicion*************************************************************************/
	if ( $j("body").hasClass("edit") ){	
		if ( $j("#id").val()=="" ){//el alta no permite exportaciones, ni nada, ocultamos la barra de botones
			$j(".botones").css("display","none");
		}
		
		
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
			    formData: {"channel":"json","class":"Upload","folder":$j(this).attr("folder-write"),"do":"save"},
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
		    			$j($j(this).find("div")[2]).text( data.result.file );
		    			$j($j(this).find("div")[1]).html("");
		    			$j($j(this).find("input")[1]).val( data.result.file );
		    			if ( $j(this).attr("type")=="image" )
		    				$j('<img/>',{class:"fotos",title:data.result.file,src:$j(this).attr("folder-read")+"/"+data.result.file}).appendTo( $j(this).find("div")[1] );
		    			else
		    				$j('<img/>',{class:"fotos",title:data.result.file,src:$j(this).attr("folder-read")+"/"+"document.png"}).appendTo( $j(this).find("div")[1] );
			    	} else {
			    		//error
			    		$j($j(this).find("div")[2]).text( data.result.description );
			    	}
			    }
			});		
		});
	}

}

/**
 * Peticion de alta de datos
 */
function doNew(){
	sendForm("doNew");
}

/**
 * Ver editar datos
 */
function doEdit(){
	sendForm("doEdit");
}

/**
 * Enviamos el formulario de datos
 */
function doAccept(){
	if ( !valida() )
		return false;
	sendForm("doInsert");
	return false;
}

/**
 * El objetivo es ir a una modificacion de datos que realizar una operacion especial
 */
function doUpdate(){
	if ( !valida() )
		return false;
	sendForm("doUpdate");
	return false;
}

/** valida el formulario de datos */
function valida(){
	var error = "";

	if ( $j('#articulo').val()=="" ) 
		error += "&#9888; El artículo está vacío<br>";
	
	if ( $j('#id_subfamilia').val()=="" ) 
		error += "&#9888; La Subfamilia está vacía<br>";
	
	if ( $j('#id_iva').val()=="" ) 
		error += "&#9888; El artículo no tiene iva<br>";
	
	if ( $j('#id_author').val()=="" ) 
		error += "&#9888; El artículo no tiene autor<br>";
	
	if ( !$j('#peso').val()||parseFloat($j('#peso').val())<=0 )
		error += "&#9888; El peso no es correcto<br>";
	
	if ( !$j('#precio').val()||parseFloat($j('#precio').val())<=0 )
		error += "&#9888; El precio no es correcto<br>";
	
	if ( $j('input[name=vendible]:checked').val()==undefined )
		error += "&#9888; Es necesario indicar si el artículo lo vendemos nosotros o no<br>";
	else{
		if ( $j('input[name=vendible]:checked').val()=="NO" && $j('#enlace').val()=="")
			error += "&#9888; El artículo al no ser vendible, tiene que tener una enlace externo (http://)<br>";
	}
	
	if ( $j('input[name=estado]:checked').val()==undefined )
		error += "&#9888; Es necesario indicar si el artículo está online o no<br>";
	
	if ( $j('#descripcion').val()=="" ) 
		error += "&#9888; El artículo no tiene descripción<br>";
	
	if ( $j('#foto_1').val()=="" ) 
		error += "&#9888; Es necesario indicar la foto principal<br>";
	
	if ( $j('.variedades').length==0 ) 
		error += "&#9888; El artículo no tiene variedades<br>";
	
	if ( error ){
		showAlert ("El artículo no es válido porque tiene estos errores:<br>"+error);
		return false;
	}
	
	mountVariety();
	return true;
}

/**
* Baja de datos
*/
function doDelete(){
	$j( "<div>Esta operación eliminará este artículo. ¿Seguimos adelante?</div>" ).dialog({
	      modal: true,
	      buttons: {
	        "NO": function(){ $j( this ).dialog( "close" ); },
	        "SI": function(){ $j( this ).dialog( "close" ); sendForm("doDelete"); }
	      }
	});
}

/**
 * Cancelar y volver nos deja otra vez en el listado
 */
function doCancel(){
	sendForm("start");
	return false;
}

/**
 * Ir a Modificar el registro
 */
function doModify(){
	sendForm("doModify");
	return false;
}

/**
 * Exportar a PDF
 */
function doPrint(){
	if ( $j("body").hasClass("list") ){
		if ( $j("#grid") && $j("#grid").pqGrid("option","dataModel").totalRecords > 100 ){
			$j( "<div>La creación del documento puede tardar un poco al ser mas de 100 resultados. Seguimos adelante?</div>" ).dialog({
		      modal: true,
		      buttons: {
		        NO: function(){ $j( this ).dialog( "close" ); },
		        SI: function(){ $j( this ).dialog( "close" ); sendForm("doListPrint","pdf"); }
		      }
			});
		} else
			sendForm("doListPrint","pdf");
	}
	if ( $j("body").hasClass("view") ){
		sendForm("doPrintDocument","pdf");		
	}
	return false;
}

/**
 * Exportar a CSV
 */
function doExport(){
	sendForm("exportCSV","csv");
}

/**
 * Añadir variedad a la tabla
 */
function doAddVar(){
	if ( $j("#talla").val()=="" )
		return showAlert("Selecciona una talla porfavor");
	if ( parseFloat($j("#stock").val())==0 )
		return showAlert("El stock no puede ser cero");
	//busco si ya existe la variedad
	var found = false;
	$j(".variedades").each(function(){
		if ( $j(this).attr('alt')==$j("#talla").val() ){
			found = true;
			return showAlert("Variedad ya registrada para el artículo");
		}
	});
	if ( found ) return;
	var row = $j("<tr/>",{'class':'variedades','alt':$j("#talla").val()}).appendTo($j("#tblVariedades"));	
	$j('<td/>').append( $j("#talla").find(":selected").text() ).appendTo(row);
	$j('<td/>').append("?").appendTo(row);
	var stock = $j("<INPUT/>",{'type':'text','class':'stock_variedad integer derecha','value':$j("#stock").val()});
	$j('<td/>').append(stock).appendTo(row);
	var estado = $j("<SELECT/>",{'class':'estado_variedad'});
	$j("<option/>",{'value':'ON','text':'Online','selected':'true'}).appendTo( estado );
	$j("<option/>",{'value':'OFF','text':'Offline'}).appendTo( estado );
	$j('<td/>').append(estado).appendTo(row);
	maskInput();
}

/** Monta las variedades en formato JSON y luego lo pasa a string en un campo del formulario */
function mountVariety(){
	try {
		var detalles = [];
		$j("tr.variedades").each(function(i){
			if ( !$j(this).attr("alt") ) return;//detalle vacio
			var talla = $j(this).attr("alt");
			var codigo = $j(this).find("td:nth-child(2)").html();
			var stock = $j(this).find(".stock_variedad").val();
			var estado = $j(this).find(".estado_variedad").val();
			var data = {"talla":talla,"codigo":codigo,"stock":stock,"estado":estado};
			detalles.push(data);
		});
	} catch(e){console.error(e);return false;}
		
	$j("#lista_variedades").val( JSON.stringify(detalles) );
	return true;
}