/**
 * Gestion Módulo
 */
function onStartApplication(){
	/**Listado*************************************************************************/
	if ( $j("body").hasClass("list") ){
		$j("#_categoria").focus();
		
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
	        {title:"Posición", dataIndx: "posicion", width:'50', dataType:"float"},
	        {title:"Estado", dataIndx: "festado", width:'75',dataType:"string"},
	        {title:"Descripción", dataIndx: "descripcion", width:'200', dataType:"string", render:function( ui ){
	        	var cutat= ui.rowData.descripcion.lastIndexOf(' ',250);
	        	if(cutat!=-1)var string=ui.rowData.descripcion.substring(0,cutat)+'...';
	        	return string;
	        	}},	        
	        {title:"Foto",width:'75', dataIndx: "foto_1", dataType:"string", render:function( ui ){return "<img class='thum' src='"+PWEB_PATH+"/images/"+ui.rowData.foto_1+"'/>";}},
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
	    		blockScreeen("Cargando familias ...");
	    		var data = "class="+$j("#class").val()+"&do=listAll&channel=json&sessionclass="+$j('#sessionclass').val()+"&"
	    			+ "_categoria="+$j('#_categoria').val() + "&" + "_familia="+$j('#_familia').val() ;
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
	    	width: 700,
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
		$j("#id_categoria").focus();
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

	if ( $j('#id_categoria').val()=="" ) 
		error += "&#9888; La categoría está vacía<br>";
	
	if ( $j('#familia').val()=="" ) 
		error += "&#9888; La familia está vacía<br>";
	
	if ( !$j('#posicion').val()||parseFloat($j('#posicion').val())<=0 )
		$j('#posicion').val("1");
	
	if ( $j('input[name=estado]:checked').val()==undefined )
		error += "&#9888; Es necesario indicar si la categoría está online o no<br>";
	
	if ( $j('#foto_1').val()=="" ) 
		error += "&#9888; Es necesario indicar la foto principal<br>";
		
	if ( error ){
		showAlert ("La familia no es válida porque tiene estos errores:<br>"+error);
		return false;
	}
	return true;
}

/**
* Baja de datos
*/
function doDelete(){
	$j( "<div>Esta operación eliminará esta familia si no tiene dependencias. ¿Seguimos adelante?</div>" ).dialog({
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