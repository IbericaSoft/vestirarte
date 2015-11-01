/**
 * Gestion GANTT
 */

function onStartApplication(){
	gantt();
	
	if ( $('_desde') ){
		Calendar.setup({
				align: "Tc",				
				dateFormat : "%d-%m-%Y",
				inputField : "_desde",
				trigger    : "calendar_desde",
				onSelect   : function() { this.hide() }
		});
	}
	if ( $('_hasta') ){
		Calendar.setup({
				align: "Tc",				
				dateFormat : "%d-%m-%Y",
				inputField : "_hasta",
				trigger    : "calendar_hasta",
				onSelect   : function() { this.hide() }
		});
	}
}

var ganttStart=true;
function gantt(){
	$$('td.diamesseleccion').each(function(item){
		item.title="Click para seleccionar día: "+item.getAttribute("day");
		$(item).onclick = function(){
			ganttStart=!ganttStart;
			
			if ( !ganttStart ){
				clearGanttSelections();
				$$('input.ganttEnd').each( function(field){ field.value = ""; } );
				$$('input.ganttStart').each( function(field){ field.value = item.getAttribute("day"); } );
				item.className = 'diamesseleccion diamesUserSeleccion';
			}else {
				$$('input.ganttEnd').each( function(field){ field.value = item.getAttribute("day"); } );
				item.className = 'diamesseleccion diamesUserSeleccion';
				fillGantSelections();
			}
			
		};
	});
}

function clearGanttSelections(){
	$$('td.diamesUserSeleccion').each(function(item){
		item.className = 'diamesseleccion';
	});
}

function fillGantSelections(){
	var filler = false;
	var cuenta = 0;
	$$('td.diamesseleccion').each(function(item){
		if ( item.className == 'diamesseleccion diamesUserSeleccion' ){
			filler = !filler;
			cuenta++;
		} else {
			if ( filler ){
				item.className = 'diamesseleccion diamesUserSeleccion';
				cuenta++;
			}
		}
	});
	$('cont_dias').innerHTML = cuenta + ' días';
}

function close(){
	getWindowsHandle(WINDOWS_HANDLE).hide();
	getWindowsHandle(WINDOWS_HANDLE).destroy();
}

/**
 * Invocamos al método callback del padre (la ventana) de esta peticion
 */
function callBack(json){
	win = getWindowsHandle(PARENT);
	json.each( 
			function(items){
				eval( "$(win.content).contentWindow."+CALLBACK+"(items)" );
			}
	);
}

/**
 * Cerramos la ventana
 */
function doCancelar(){
	close();
}

/**
 * Invocamos al metodo callback del Padre de esta ventana para q recoja los datos y cerramos
 */
function doAceptar(){
	var json = [{"inicio":$F('_desde'),"fin":$F('_hasta')}];
	callBack(json);
	close();
}