/**
 * El api dobleosapiclient llama a este metodo en el onload de la pagina
 */
function init(){
	
	shortCut( 'esc', window, 'close()' );
	
	//Eventos documento
	shortCut( 'F11', document, null );
	shortCut( 'F5', document, null );
	shortCut( 'F1', document, null );
	
	var icon = getIconHandle(ICON).icon;

	$('text1').innerHTML = 'Clase para ejecutar:<br>&nbsp;' + icon['class'];
	$('text2').innerHTML = 'Operación a procesar:<br>&nbsp;' + icon['do'];
	$('text3').innerHTML = 'Parametros para la ejecución:<br>&nbsp;' + icon['parameters'];
	//$('title').innerHTML = 'Clase para ejecutar: ' + icon['class'];
	//$('title').innerHTML = 'Clase para ejecutar: ' + icon['class'];
	
	
} 

function close(){
	//getWindowsHandle(WINDOWS_HANDLE).destroy();
	//parent.WindowCloseKey.init();
}

