$(document).ready(function(){				
	//logo
	$( "#logogif" ).draggable();
	
	
	
	//
	var vida= false
    $('#boton_movil').click(function() {
    	if(vida){
    		$('#menu_moviles').css({'left' : '0px'});
            vida = false;
    	}else{
    		$('#menu_moviles').css({'left' : '-310px'});
    		vida = true;
    	}
    });
	//
	var vida2=false;
	$('#boton_menu_prendas').click(function() {
		if(vida2){
			$('#lista_tipos_prendas').css({'right' : '-7px'})
			vida2 = false
		}else{
			$('#lista_tipos_prendas').css({'right' : '-165px'})
			vida2 = true
    	}
	});
	
	
});

function showAlert(tipo,msg){
	$( '<div title="'+tipo+'"><p><span class="ui-icon ui-icon-circle-check" style="float:left; margin:0 7px 50px 0;"></span>'+msg+'</p>' ).dialog({
	      modal: true,
	      buttons: {
	        Enterado: function() {
	          $( this ).dialog( "close" );
	        }
	      }
	 });
}

/**
 * LLamada comun al servicio de la tienda
 * @param url patron del servicio
 * @param invokeifsuccesss callback si todo va bien
 * @param invokeiferror callback si algo sale mal
 */
function requestShop(url,data,invokeifsuccess,invokeiferror,method){
	$.ajax({
		url: url, 
		data: data,
		dataType: 'json',
		async: true,
		type: (method)?method:'get', 
		success: invokeifsuccess,
		error: invokeiferror
	});
}

/** Bloqueo de la pantalla con un mensaje personalizado */
function wait(msg){
	$.blockUI({ message: '<div class="ajax">'+msg+'</div>',overlayCSS: { opacity:.3 },blockMsgClass: 'waitBox' });
}

/** Desbloqueo pantalla */
function stopWait(){
	$.unblockUI();
}