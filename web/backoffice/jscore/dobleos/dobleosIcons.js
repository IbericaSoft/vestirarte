/**
 * @author Doble H Software www.dobleh.com
 * @author Antonio Gámez
 * dobleOS system API
 * Gestion de iconos de escritorio
 * @version 1.1 FIX. El metodo iconEndDrag que recoge la posicion top/left del icono arrastrado fallaba en algunas versiones de browser.
 * 
 */
var VERSION = 1.1;
/**
 * Este array mantiene todas las referencias a los iconos para poder ser gestionados
 * El array en cada posicion tiene un objeto JSON con dos claves: 
 * 	>handle que será la primary-key para localicar el icono
 *  >params que almacena otro objeto JSON con los atributos de la clase del servidor a la que responde
 * Formato:   
 * { 'handle':handle, 'params':
 * 		{'icon_id':'.','type':'icon','title':'.','image':'.','left':'.','top':'.','appWidth':'.','appHeight':'.','class':'.','do':'.'}
 * }
 */
var arrIcons = new Array();


/** 
 * Preparar los iconos del desktop
 * De momento funcion si uso 
 * */
function setDesktopIcons(){	}

/**
 * Añade un icono al escritorio
 */
function addDesktopIcon(title,icon,leftt,topp,iconJson){
	
	var id = null;
	if ( !iconJson.icon_id ){
		var execute = {"type":"data","class":"System","do":"getHandle","handleType":"icon_id","asynchronous":false};
		var id  = DobleOSAPI.executeApplication(execute).handle;
	} else {
		id = iconJson.icon_id;
	}
	
	var handle 			= id;
	var handleContainer = 'container@'+handle;
	var handleLabel     = 'label@'+handle;
	var handleIcon		= 'icon@'+handle;
	
	//contenedores(capas), padre, icono y label
	var parent = new Element('div',{id:handleContainer,'class':'icon_container_'+id} );
	var label = new Element('div',{id:handleLabel,'class':'icon_label'}).update(title.replace(' ','<br>'));
	var item = new Element('img',{id:handleIcon,src:icon,width:ICONS_SIZE,height:ICONS_SIZE,title:title,'class':'icon_image',ondblclick:'executeIcon('+id+')'});
	parent.setStyle({position:'absolute',top:topp,left:leftt});//posicionamos el container despues, antes no funciona ¿?
	
	//Insertamos los nuevos elementos en el DOM
	parent.insert(item);
	parent.insert(label);
	$(document.body).insert(parent);
	
	//Array de iconos 
	arrIcons[arrIcons.length] 	= {'handle':id,'icon':iconJson};
	
	//Efecto arrastre
	draggable( handleContainer,id );
	
	if ( DobleOSAPI.DEBUG )	alert ( "ICONO:"+Object.toJSON(iconJson));
	//registro en el servidor si es nuevo icono
	if ( !iconJson.icon_id ){
		iconJson.icon_id = id;
		registryIcon(iconJson);
	}
	
	return id;
	
}

/**
 * 
 * @param item
 * @param handle
 */
function draggable( item,handle ){
	//Efectos arrastre
	new Draggable(item, { onEnd: function(){ iconEndDrag(item,handle); } } );
}

/** 
 * Al arrastrar y soltar notificamos al servidor su nueva posicion
 * */
function iconEndDrag(item, handle){
	Effect.Shake(item);
	position = $(item).cumulativeOffset();
	
	//var icon = getIconHandle(handle)
	var params = {"type":"data","class":"System","do":"iconUpdate",'icon_id':handle,'asynchronous':true,'_itop':position[1],'_ileft':position[0] };
	DobleOSAPI.executeApplication(params);
}

/** 
 * Devuelve de la lista de iconos el solicitado
 * por su handle 
 */
function getIconHandle( handle ){
	var icon;
	arrIcons.each( function(ico) {
		if ( ico.handle == handle ){			
			icon = ico;
		}
	});
	//alert ( "getICON:"+Object.toJSON(icon) );
	return icon;
}

/**
 * Llamada a la ejecucion de la aplicación vinculada al icono
 * @param id
 */
function executeIcon( id ){
	icon = getIconHandle(id);
	
	var execute = {"type":"data","class":"System","do":"getHandle","asynchronous":false};
	var responseJson  = DobleOSAPI.executeApplication(execute);
	
	var params = {"handle":responseJson.handle,
			"type":"icon",
			"class":icon.icon["class"],
			"do":icon.icon["do"],
			"parameters":icon.icon["parameters"],
			"title":icon.icon["title"],
			"width":icon.icon["width"],
			"height":icon.icon["height"],
			"closable":icon.icon["closable"],
			"minimize":icon.icon["minimize"],
			"maximize":icon.icon["maximize"],
			"resizable":icon.icon["resizable"],
			"modal":icon.icon["modal"],
			"top":icon.icon["top"],
			"left":icon.icon["left"],
			"registry":true
			};
	DobleOSAPI.executeApplication ( params );
}

function registryIcon( icon ){
	var iconUser = {};
	
	iconUser['icon_id']		= icon.icon_id;
	iconUser['do']			= 'iconProcess';
	iconUser['class']		= 'System';
	iconUser['type']		= 'data';
	iconUser['asynchronous']=false;
	iconUser['_class']    = icon['class'];
	iconUser['_do']    	= icon['do'];
	iconUser['_width']	= icon['width'];
	iconUser['_height']	= icon['height'];
	iconUser['_top']		= icon['top'];
	iconUser['_left']		= icon['left'];
	iconUser['_minimize']	= icon['minimize'];
	iconUser['_maximize']	= icon['maximize'];
	iconUser['_closable']	= icon['closable'];
	iconUser['_resizable']= icon['resizable'];
	iconUser['_status'] =	'custom';
	iconUser['_parameters']	= icon['parameters'];
	iconUser['_icon']		= icon['icon'];
	iconUser['_ititle']	=  icon['ititle'];
	iconUser['_itop']		=  icon['itop'];
	iconUser['_ileft']	=  icon['ileft'];
	DobleOSAPI.executeApplication(iconUser);
}


function viewIconProperties( id ){
	icon = getIconHandle(id).icon;
	var params = {"handle":-1,"type":"application","class":"System","do":"viewIconProperties","title":icon.ititle,"width":"300","height":"200","closable":true,"modal":true,'parameters':'&icon_id='+icon.icon_id };
	DobleOSAPI.executeApplication(params);
}

function deleteIcon( id ){
	icon = getIconHandle(id).icon;
	if ( !confirm("¿Eliminamos este icono?") )
		return;
	
	var params = {"type":"data","class":"System","do":"deleteIcon",'icon_id':icon.icon_id,'asynchronous':true };
	DobleOSAPI.executeApplication(params);
	
	$('container@'+icon.icon_id).remove();
}
