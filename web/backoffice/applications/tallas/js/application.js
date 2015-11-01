/**
 * Gestion Módulo
 */
function onStartApplication(){
	/**Listado*************************************************************************/
	if ( $j("body").hasClass("list") ){
		$j("#_talla").focus();
		
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
	        {title:"Talla", dataIndx: "talla", width:'100',dataType:"string"},
	        {title:"Descripción", dataIndx: "descripcion", width:'175',dataType:"string"},
	        {title:"Estado", dataIndx: "festado", width:'75',dataType:"string"},
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
	    		blockScreeen("Cargando tallas ...");
	    		var data = "class="+$j("#class").val()+"&do=listAll&channel=json&sessionclass="+$j('#sessionclass').val()+"&"
	    			+ "_talla="+$j('#_talla').val();
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
	    	width: 550,
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
		$j("#talla").focus();
		
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
	
	if ( $j('#talla').val()=="" ) 
		error += "&#9888; La talla está vacía<br>";
	
	if ( $j('input[name=estado]:checked').val()==undefined )
		error += "&#9888; Es necesario indicar si la talla está online o no<br>"; 

	if ( error ){
		showAlert ("La talla no es válida porque tiene estos errores:<br>"+error);
		return false;
	}
	return true;
}
/**
* Baja de datos
*/
function doDelete(){
	$j( "<div>Esta operación eliminará esta talla si no tiene dependencias. ¿Seguimos adelante?</div>" ).dialog({
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
