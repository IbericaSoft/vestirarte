/**
 * Gestion Obras. Utilidad señal pedido
 */
function onStartApplication(){
	
}

/** no aceptamos el presupuesto... es como un cancelar */
function doNO(){
	$('do').value = 'listAll'; 
	$('fapplication').submit();
	return false;
}

/** aceptamos el presupuesto pero si señal */
function doSIsin(){
	$('do').value = 'doChangeStatus'; 
	$('fapplication').method = 'POST';
	$('estado').value = 'PROYECTO';
	$('fapplication').submit();
	return false;
	
}

/** aceptamos el presupuesto pero con señal */
function doSIcon(){
	var params = {"handle":"","type":"application","class":'caja',"do":"doExternalNew","title":"Señalizar la Obra-Pedidos"
		,"width":"700","height":"500","parameters":"id="+$F('id'),"closable":true,"modal":true };
	executeApplication(params);
	
	doSIsin();
}