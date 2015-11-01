
/**
 * Este metodo es invocado al cargar la pagina y prepara los eventos de los componentes de la pagina o inicializa
 * los datos necesarios para la ejecución de esta aplicación
 */
function init(){
	
}

function closeme(){
	var execute = {"type":"data","asynchronous":false,"class":"System","do":"closeSession"};
	var responseJson=executeApplication(execute);
	if ( responseJson.error=='NO' )
		eval(responseJson.callBack);
	else
		alert(responseJson.errDescription);
}

function cancel(){
	getWindowsHandle(WINDOWS_HANDLE).close();
}