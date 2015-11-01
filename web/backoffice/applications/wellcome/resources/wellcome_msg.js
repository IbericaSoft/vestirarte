/**
 * Iniciamos todas las acciones del escritorio en el onload method del body
 */
function start(){
	
	shortCut("F1",document, "parent.continuar()" );
	shortCut("F11",document, "parent.continuar()" );
	shortCut("F5",document, "" );
	
	$('wellcome').insert("<div id= \"next\">..o click</div>" );
	$('next').onclick = parent.continuar;
}

 