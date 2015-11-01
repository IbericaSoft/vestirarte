/**
 * El api dobleosapiclient llama a este metodo en el onload de la pagina
 */
function onStartApplication(){

	$('usuario').focus();
	
	shortCut( 'Enter', $('usuario'), 'doLogin()' );
	shortCut( 'Enter', $('password'), 'doLogin()' );
	
	//Eventos documento
	for (f=1;f<13;f++)
		shortCut( 'F'+f, document, null );
	
} 

function doLogin(){
	var params = {"type":"form","form":$("fapplication")};
	var responseJson  = executeApplication(params);
	if ( responseJson.error=='NO' )
		responseJson.callBack.each( function(call){ eval(call);} );
	else {
		$('err').innerHTML=responseJson.errDescription;
		$('usuario').select();
		$('usuario').focus();
	}
}
