/**
 * Gestion Obras. Utilidad se�al pedido
 */
function onStartApplication(){
	
}

/** no aceptamos el presupuesto... es como un cancelar */
function doNO(){
	$('do').value = 'listAll'; 
	$('fapplication').submit();
	return false;
}

/** aceptamos el presupuesto pero si se�al */
function doSIsin(){
	$('do').value = 'doChangeStatus'; 
	$('fapplication').method = 'POST';
	$('estado').value = 'PROYECTO';
	$('fapplication').submit();
	return false;
	
}

/** aceptamos el presupuesto pero con se�al */
function doSIcon(){
	var params = {"handle":"","type":"application","class":'caja',"do":"doExternalNew","title":"Se�alizar la Obra-Pedidos"
		,"width":"700","height":"500","parameters":"id="+$F('id'),"closable":true,"modal":true };
	executeApplication(params);
	
	doSIsin();
}