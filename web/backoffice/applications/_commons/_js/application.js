/**
 * Gestion Global de ventanas application
 * @version 1.0 03.2012 creacion
 * @version 1.1 03.2012 asignamos onclick a los buttons.fields del documento, al igual que hacemos con los input
 * @version 1.2 04.2012 controlButtonsRollOver() input[type="button"] ahora buscamos que el ID del objeto empieze con 'bt' para asignarle evento
 */


/**
 * Este metodo es invocado al cargar la pagina y prepara los eventos de los componentes basicos de la pagina. También
 * llama al metodo startApplication para inicializar el comportamiento especifico del application en cuestión.
 */
function initd(){
	$$('body.application').each( 
			function(){ 
				initd1();
				try { onStartApplication(); } catch (e){}
			} 
	);
}

/** Inicializacion de la ventana standard (menus, filtros y botones) */
function initd1(){
	//eventos menu, botones y filtros
	controlButtonsRollOver();
	
	//eventos para las filas de datos*/
	controlRows();
	
	//Eventos documento, capturados!!!
	for (f=1;f<13;f++)
		shortCut( 'F'+f, document, null );
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
 * Este método recorre todas las filas de datos, para ello utiliza el filtro de busqueda tr.class que le permite
 * localizar lo que son filas de datos. A cada fila le anexa los eventos para el efecto de filas, y el onclick para 
 * la edición del registro
 */
function controlRows(){
	$$('tr.rowDataOff').each(function(item){
		if ( item.id=="" ) return;
		$(item.id).onmouseover = function(){ item.className = 'rowDataOn'; };
		$(item.id).onmouseout  = function(){ item.className = 'rowDataOff'; };		
		$(item.id).onclick = function(){
			$('id').value = item.getAttribute("id").replace('tr_rowdata_',"");
			$('do').value = 'doEdit';
			$('channel').value="";
			$('fapplication').submit();
		};
	});
}

/**
 * Nos permite movernos por la paginación disponible
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
}


/**
 * Este método borra la lista de datos. Se suele utiliza antes de empezar una búsqueda.
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
 * Mostrar informacion de esta aplicación
 */
function doInfo(){
	var params = {"handle":$F('sessionclass'),"type":"application","class":$F('class'),"do":"info","title":"Información","width":"300","height":"200","closable":true,"modal":true };
	executeApplication(params);
}

/**
 * Mostrar ayuda de esta aplicación
 */
function doHelp(){
	var params = {"handle":$F('sessionclass'),"type":"application","class":$F('class'),"do":"help","title":"Ayuda","width":"400","height":"300","parameters":"#"+PAGE_NAME,"closable":true,"modal":true };
	executeApplication(params);
}
