/**
 * Gestion Global de ventanas application
 * @version 2.0 01.2014 Integrando Jquery y mejorando rutinas
 */

/** compatibilidad prototype-jquery */
jQuery.noConflict();
var $j = jQuery;

/**
 * Este metodo es invocado al cargar la pagina y prepara los eventos de los componentes basicos de la pagina. Tambi�n
 * llama al metodo onStartApplication para inicializar el comportamiento especifico del application en cuesti�n.
 */
$j(document).ready(function(){
	if ( $j(".application").length ){		
		//carga los modulos solicitados en la variable IMPORT
		configure();
		aplyConfigure();			
		//eventos menu, botones y filtros
		controlButtonsRollOver();
		//eventos para las filas de datos*/
		controlRows();
		//metodo implementado en el modulo lanzado
		onStartApplication(); 
	}
});

/**
 * Carga las librerias y hojas de estilo que necesite el modulo en ejecucion 
 * */
function configure(){
	/** API de llamadas al core */
	loadJS(WEB_PATH+"/jscore/dobleos/dobleosapiclient.v2.js");
	
	//TEMA DE LA VENTANA//
	loadCSS(WEB_PATH+"/applications/_commons/"+WINDOWS_STYLE+"/css/application.css");
	loadCSS(WEB_PATH+"/applications/"+MODULE_NAME+"/css/application.css");
	
	
	//Jquery UI siempre y el tema elegido
	loadCSS(WEB_PATH+"/jscore/system/jquery-themes/"+FORM_STYLE+"/jquery-ui-1.10.4.custom.css");
	loadJS(WEB_PATH+"/jscore/system/jquery-ui-1.10.4.custom.js");
	
	//Comun
	//loadJS(WEB_PATH+"/jscore/system/jquery.hotkeys.min.js");
	loadJS(WEB_PATH+"/jscore/system/jquery.blockUI.min.js");
	
	/** Javascript de la ventana en ejecucion */
	loadJS(WEB_PATH+"/applications/"+MODULE_NAME+"/js/application.js");
	if ( IMPORT.indexOf('dobleosPopup')!=-1 ){
		loadJS(WEB_PATH+"/applications/"+MODULE_NAME+"/js/popup.js");
	}
	/** FIN */
	if ( IMPORT.indexOf('dobleos')!=-1 )
		loadJS(WEB_PATH+"/jscore/system/prototype.js");
	
	if ( IMPORT.indexOf('formats')!=-1 )
		loadJS(WEB_PATH+"/jscore/extras/util.formats.js");
	
	//if ( IMPORT.indexOf('numeric')!=1 )
		loadJS(WEB_PATH+"/jscore/extras/jquery.maskMoney.min.js");
	
	if ( IMPORT.indexOf('upload')!=-1 ){
		loadJS(WEB_PATH+"/jscore/extras/upload/jquery.fileupload.js");
		loadJS(WEB_PATH+"/jscore/extras/upload/jquery.ui.widget.js");
	}
}

/**
 * function to load a given css file 
 */ 
function loadCSS(href) {
    var cssLink = $j("<link rel='stylesheet' type='text/css' href='"+href+"'>");
    $j("head").append(cssLink); 
};

/**
* function to load a given js file 
*/ 
function loadJS(src) {
    var jsLink = $j("<script type='text/javascript' src='"+src+"'>");
    $j("head").append(jsLink); 
}; 

/**
 * Una vez cargados los modulos requerido, invocamos a los metodos de esos modulos que inician cosas!
 */
function aplyConfigure(){
	/**botones */
	$j("button,input[type=button],input[type=submit]").button();
	
//	/** Eventos teclado ... no funcionan bien... los de prototype van mejor*/
//	$j("*").bind('keydown', 'F1', function(e){console.log("evento f1 capturado");return false;});
//	$j("*").bind('keydown', 'F2', function(e){console.log("evento f2 capturado");return false;});
//	$j("*").bind('keydown', 'F3', function(e){console.log("evento f3 capturado");return false;});
//	$j("*").bind('keydown', 'F4', function(e){console.log("evento f4 capturado");return false;});
//	$j("*").bind('keydown', 'F5', function(e){console.log("evento f5 capturado");return false;});
//	$j("*").bind('keydown', 'F6', function(e){console.log("evento f6 capturado");return false;});
//	$j("*").bind('keydown', 'F7', function(e){console.log("evento f7 capturado");return false;});
//	$j("*").bind('keydown', 'F8', function(e){console.log("evento f8 capturado");return false;});
//	$j("*").bind('keydown', 'F9', function(e){console.log("evento f9 capturado");return false;});
//	$j("*").bind('keydown', 'F10', function(e){console.log("evento f10 capturado");return false;});
//	$j("*").bind('keydown', 'F11', function(e){console.log("evento f11 capturado");return false;});
//	$j("*").bind('keydown', 'F12', function(e){console.log("evento f12 capturado");return false;});	
	
	//Eventos documento, capturados!!!
	for (f=1;f<13;f++)
		shortCut( 'F'+f, document, null );
	
	//$(document).bind('keydown', 'alt', windowOnTop);
	/** Campos fecha */
	$j.datepicker.regional['es'] = {
	        closeText: 'Cerrar',
	        prevText: '<Ant',
	        nextText: 'Sig>',
	        currentText: 'Hoy',
	        monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
	        monthNamesShort: ['Ene','Feb','Mar','Abr', 'May','Jun','Jul','Ago','Sep', 'Oct','Nov','Dic'],
	        dayNames: ['Domingo', 'Lunes', 'Martes', 'Mi�rcoles', 'Jueves', 'Viernes', 'S�bado'],
	        dayNamesShort: ['Dom','Lun','Mar','Mi�','Juv','Vie','S�b'],
	        dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','S�'],
	        weekHeader: 'Sm',
	        dateFormat: 'dd-mm-yy',
	        firstDay: 1,
	        isRTL: false,
	        showMonthAfterYear: false,
	        yearSuffix: ''
	 };
	 $j.datepicker.setDefaults($j.datepicker.regional['es']);
	 
	 //tooltips
	 $j( document ).tooltip({ items: "img[title],div[title],button[title]" });
	 
	 if ( IMPORT.indexOf("tabs")!=-1)
		$j( "#tabs" ).tabs().css("display","block");
	 
	 if ( IMPORT.indexOf("blockui")!=-1 )
		$j("body").append('<div id="blocking" style="display:none"><span></span></div>');
	 
	 if ( IMPORT.indexOf("numeric")!=-1 ){
		$j(".decimal").maskMoney({thousands:'', decimal:'.', allowZero:true, precision:3}).css("text-align","right");
		$j(".integer").maskMoney({thousands:'', allowZero:true, precision:0}).css("text-align","right");
		$j(".null").maskMoney({thousands:'', allowZero:false, precision:0}).css("text-align","left");
		$j(".currency").maskMoney({thousands:'', decimal:'.', allowZero:true, precision:2}).css("text-align","right");
	 }
}

/**
 * Eventos de rollover para los menus de la ventana, 
 * Evento onclick para los botones del menu
 * Evento onclick para los input tipo button que su antecesor sea un class buttons
 * Evento onclick para los buttons class fields
 * Capturamos envento <enter> para los campos que estan en el bloque filtro. Su antecesor es class boxFilters
 */
function controlButtonsRollOver(){
	$$('div.boxMenuButton').each(
		function(item){
			$(item).onmouseover = function(){ item.className = 'boxMenuButtonOver'; };
			$(item).onmouseout  = function(){ item.className = 'boxMenuButton'; };	
			item.onclick		= eval( item.id.replace('mnu','do') );
		}
	);

	$$('input[type="button"]').each(
			function(item){
				try {
					//if ( $(item.id).descendantOf('buttons') )
					if ( $(item.id.lastIndexOf('bt')===0) )
						item.onclick = eval( item.id.replace('bt','do') );
				} catch (e){}
			}
	);
	
	$$('button.fields').each(
			function(item){
				try {
						item.onclick = eval( item.id.replace('bt','do') );
				} catch (e){}
			}
	);
	
	if ( $('boxFilters')==null ) 
		return;
	
	$$('input').each(
			function(item){
				if ( $(item.id).descendantOf('boxFilters') )
					shortCut( 'Enter', $(item.id), 'doSearch()' );
			} 
	);
	
	$$('select').each(
			function(item){
				if ( $(item.id).descendantOf('boxFilters') )
					shortCut( 'Enter', $(item.id), 'doSearch()' );
			} 
	);
	
	$$('radio').each(
			function(item){
				if ( $(item.id).descendantOf('boxFilters') )
					shortCut( 'Enter', $(item.id), 'doSearch()' );
			} 
	);
}

/**
 * Este m�todo recorre todas las filas de datos, para ello utiliza el filtro de busqueda tr.class que le permite
 * localizar lo que son filas de datos. A cada fila le anexa los eventos para el efecto de filas, y el onclick para 
 * la edici�n del registro
 */
function controlRows(){
	$$('tr.rowDataOff').each(function(item){
		if ( item.id=="" ) return;
		$(item.id).onmouseover = function(){ item.className = 'rowDataOn'; };
		$(item.id).onmouseout  = function(){ item.className = 'rowDataOff'; };		
		$(item.id).onclick = function(){
			$('id').value = item.getAttribute("id").replace('tr_rowdata_',"");
			sendForm("doEdit","html");
		};
	});
}

/**
 * Nos permite movernos por la paginaci�n disponible
 * @param pagina
 */
function pagination(pagina){
	var execute = {"type":"data","asynchronous":false,"class":$F('class'),"do":"listAll","sessionclass":$F('sessionclass'),"pagina":pagina};
	queryJson(execute);
}

/**
 * Lanza la peticon ajax con respuesta JSON a la query lanzada en execute
 * @param execute parametros de la peticion ajax
 */
function queryJson(execute){
	removeDataList();
	removeDataNavigation();
	var responseJson  = executeApplication(execute);
	if ( responseJson.error=='NO' )
		eval(responseJson.callBack);
	else
		alert(responseJson.errDescription);
	unBlockScreen();
}


/**
 * Este m�todo borra la lista de datos. Se suele utiliza antes de empezar una b�squeda.
 */
function removeDataList(){
	//borra todas las filas de la lista de datos
	$$('tr.rowDataOff').each( function(tr){ Element.remove(tr);} );
}

function removeDataNavigation(){
	//borra todas las filas de la lista de datos
	$$('tr.pagination').each( function(tr){ Element.remove(tr);} );
}

/**
 * Mostrar informacion de esta aplicaci�n
 */
function doInfo(){
	var params = {"handle":$F('sessionclass'),"type":"application","class":$F('class'),"do":"info","title":"Informaci�n","width":"300","height":"200","closable":true,"modal":true };
	executeApplication(params);
}

/**
 * Mostrar ayuda de esta aplicaci�n
 */
function doHelp(){
	var params = {"handle":$F('sessionclass'),"type":"application","class":$F('class'),"do":"help","title":"Ayuda","width":"400","height":"300","parameters":"#"+PAGE_NAME,"closable":true,"modal":true };
	executeApplication(params);
}

/**
 * Envio del formulario y mensaje de espera
 */
function sendForm(action,channel){
	blockScreeen(null);
	$j('#do').val(action);
	$j('#channel').val(channel);
	$j('#fapplication').attr('method','post');
	$j('#fapplication').submit();
	if ( channel!='html' )
		unBlockScreen();
}

/**
 * Monta una capa con estilo que muestra un mensaje con el API blockUI que bloquea la pantalla
 * @param message
 */
function blockScreeen(message){
	if ( !message )
		message = 'Enviando su peticion al servidor. Un momento por favor...';
	$j('#blocking').find("SPAN:first").text(message);
	$j('#blocking').css({"margin":"5px 5px","background-image":"url(../applications/_commons/_images/ajax-loader.gif)","background-position":"5px 5px","background-repeat":"no-repeat"});
	$j('#blocking').find("SPAN:first").css({"margin":"10px 0px","font":"12px arial","font-weight":"bold","font-size":"13px","padding-left":"50px"});
	$j.blockUI({ message: $j('#blocking') });
}

/**
 * Oculta el mensaje de y desbloquea la pantalla
 */
function unBlockScreen(){
	$j.unblockUI();
}

/**
 * Alert con caja modal de Jquery UI
 * @param msg
 * @returns {Boolean}
 */
function showAlert(msg){
	$j( "<div>"+msg+"</div>" ).dialog({
	      modal: true,
	      buttons: {
	        VALE: function(){ $j( this ).dialog( "close" ); }	        
	      }
	});
	return false;
}