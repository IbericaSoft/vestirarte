/**
 * Gestion Global de ventanas applicatión
 * @version 3.0 01.2015 Evolución al nuevo modelo
 * @version 3.1 03.2015 MaskInput. Método por separado para aplicar mascara a los campos input dinámicos
 */

/** compatibilidad prototype-jquery */
jQuery.noConflict();
var $j = jQuery;

/** Inicializacion */
$j(document).ready(function(){
	if ( $j(".application").length ){		
		load();//->carga los modulos solicitados en la variable IMPORT
		configureUI();//->configura los componentes de UI
		configureEvents();//->aplica eventos a los objetos del DOM						
		onStartApplication();//->este metodo lo tiene que implementar el JS propio del modulo
	}
});

/** Carga las librerias y hojas de estilo que necesite el modulo en ejecucion */
function load(){		
	var EXTRAS = typeof IMPORT == 'undefined' ? "" : IMPORT;
	
	loadJS(WEB_PATH+"/jscore/dobleos/dobleosapiclient.v2.js");/** API de llamadas al core */
	loadCSS(WEB_PATH+"/applications/_commons/_css/application.v3.css");/** estilo generico */
	loadCSS(WEB_PATH+"/applications/"+MODULE_NAME+"/css/application.css");/** estilo propio de la ventana*/
	/** Jquer UI */
	loadCSS(WEB_PATH+"/jscore/system/jquery-ui.min.css");
	loadJS(WEB_PATH+"/jscore/system/jquery-ui.min.js");
	/** GRID **/
	loadCSS(WEB_PATH+"/jscore/system/pqgrid.min.css");
	loadJS(WEB_PATH+"/jscore/system/pqgrid.min.js");
	loadJS(WEB_PATH+"/jscore/system/pq-localize-es.js");
	/** Bloqueo pantalla */
	loadJS(WEB_PATH+"/jscore/system/jquery.blockUI.min.js");
	/** mascaras en los campos */
	loadJS(WEB_PATH+"/jscore/extras/jquery.maskMoney.min.js");
	/** JS modulo en ejecucion */
	loadJS(WEB_PATH+"/applications/"+MODULE_NAME+"/js/application.js");
	/** grid lista */
	if ( EXTRAS.indexOf('simple-details')!=-1 )
		loadJS(WEB_PATH+"/jscore/system/simple-details.js");
	/** updload */
	if ( EXTRAS.indexOf('upload')!=-1 ){		
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

/** Configura los componentes de UI */
function configureUI(){
	/** El GRID es castellano */
	$j.paramquery.pqPager.setDefaults($j.paramquery.pqPager.regional['es']);
	
	/** Tipo UI BUTTON para estos componentes  */	
	$j( "input[type=submit], a, button" ).button().click(function( event ) {event.preventDefault();}); //UI BOTONES
	$j( "select" ).selectmenu();
	$j( ".radio-ui" ).buttonset();
	
	/** Calendarios, configuracion regional */
	$j.datepicker.regional['es'] = {
	        closeText: 'Cerrar',
	        prevText: '<Ant',
	        nextText: 'Sig>',
	        currentText: 'Hoy',
	        monthNames: ['Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre'],
	        monthNamesShort: ['Ene','Feb','Mar','Abr', 'May','Jun','Jul','Ago','Sep', 'Oct','Nov','Dic'],
	        dayNames: ['Domingo', 'Lunes', 'Martes', 'Miercoles', 'Jueves', 'Viernes', 'Sabado'],
	        dayNamesShort: ['Dom','Lun','Mar','Mie','Juv','Vie','Sab'],
	        dayNamesMin: ['Do','Lu','Ma','Mi','Ju','Vi','Sa'],
	        weekHeader: 'Sm',
	        dateFormat: 'dd-mm-yy',
	        firstDay: 1,
	        showMonthAfterYear: false,
	        yearSuffix: ''
	 };
	 $j.datepicker.setDefaults($j.datepicker.regional['es']);
	 
	/** Calendario DatePicker sencillo */	
	$j(".calendario").datepicker({changeMonth: true,changeYear: true}).css("max-width","75px");
	
	/** Calendario DatePicker rango de fechas */
	$j(".calendario-from").datepicker({
		defaultDate: "+1w",
		changeMonth: true,
		changeYear: true,
		numberOfMonths: 2,
		onClose: function( selectedDate ) { $j(".calendario-to").datepicker( "option", "minDate", selectedDate ); }
	}).css("max-width","75px");
	$j(".calendario-to").datepicker({
		defaultDate: "+1w",
		changeMonth: true,
		numberOfMonths: 2,
		onClose: function( selectedDate ) {$j(".calendario-from").datepicker( "option", "maxDate", selectedDate ); }
	}).css("max-width","75px");
	
	/** tooltips, objetos a los que se aplica */
	$j( document ).tooltip({ items: "img[title],div[title],button[title]" });
	
	/** Tabs */
	$j( "#tabs" ).tabs();
	
	/** mascaras */
	maskInput();		
}

function maskInput(){
	/** mascaras */
	$j(".decimal").maskMoney({thousands:'', decimal:'.', allowZero:true, precision:3}).css("text-align","right");
	$j(".integer").maskMoney({thousands:'', allowZero:true, precision:0}).css("text-align","right");
	$j(".null").maskMoney({thousands:'', allowZero:false, precision:0}).css("text-align","left");
	$j(".currency").maskMoney({thousands:'', decimal:'.', allowZero:true, precision:2}).css("text-align","right");
}

/**
 * Asignacion de eventos a la ventana
 */
function configureEvents(){
	/** esto asigna al evento onclick del objeto, la llamada al metodo doXXX */
	$j('button, .menuBotones').each(function(){
		try {
			$j(this).click( function(){				
				var newMethod = $j(this).attr("id").replace('bt','do');
				eval(newMethod+"()");
				return false;
			});
		} catch (e){console.error(e);}
	});
	
	return;
	
	//Eventos documento, capturados!!!
	for (f=1;f<13;f++)
		shortCut( 'F'+f, document, null );
	
	//$(document).bind('keydown', 'alt', windowOnTop);

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
 * Mostrar informacion de esta aplicaci�n
 */
function doInfo(){
	var params = {"handle":$j('#sessionclass').val(),"type":"application","class":$j('#class').val(),"do":"info","title":"Informaci�n","width":"300","height":"200","closable":true,"modal":true };
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
	$j('form').attr('method','post');
	$j('form').submit();
	if ( channel!='html' )
		unBlockScreen();
}

/**
 * Monta una capa con estilo que muestra un mensaje con el API blockUI que bloquea la pantalla
 * @param message
 */
function blockScreeen(message){
	if ( !message )
		message = 'Un momento por favor';
	
	if ( !$j("#_blocking").length )
		$j("body").append('<div id="_blocking" style="display:none"><span></span></div>');
	
	$j('#_blocking').find("SPAN:first").text(message);
	$j.blockUI({ message: $j('#_blocking'),css: {border: 'none',padding: '15px','border-radius': '10px'} });
}

/**
 * Oculta el mensaje de y desbloquea la pantalla
 */
function unBlockScreen(){
	$j.unblockUI();
}

/**
 * Envia una peticion y recibe los datos en formato json invocando al callback correspondiente, ok o ko
 * @param data
 * @param sucess
 * @param error
 */
function queryDobleOSJson(parameters,ifok,iferr){
	var turl = (WEB_PATH!="")?WEB_PATH:"/";
	$j.ajax({
		  type: "POST",
		  url: turl,
		  data: parameters,
		  success: ifok,
		  error: iferr,
		  dataType: "json"
	});
}


function showAlert(msg){
	$j( "<div>"+msg+"</div>" ).dialog({
	      modal: true,
	      buttons: {
	        VALE: function(){ $j( this ).dialog( "close" ); }	        
	      }
	});
	return false;
}

/**
 * Envio del formulario y mensaje de espera
 */
function sendForm(action,channel){
	blockScreeen(null);
	$j('#do').val(action);
	if ( null==channel ) channel = 'html';
	$j('#channel').val(channel);
	$j('form').attr('method','post');
	$j('form').submit();
	if ( channel!='html' )
		unBlockScreen();
}