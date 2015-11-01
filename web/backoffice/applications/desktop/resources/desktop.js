/**
 * El api dobleosapiclient llama a este metodo en el onload de la pagina
 * 
 * @version 1.2 Las ventanas nuevas se abren intentando no pisarse completamente unas a otras con un pequeño desplazamiento 
 */
function start(){
	
	/** wallpaper */
	$$('img.desktop').last().setAttribute('src',DESKTOP_WALLPAPER);
	
	/** El api se encarga de las tareas iniciales, solo tenemos que definir los objetos de la sesion */
	startDeskTop();
	
	showIcons();

	showApplications();
	
	showMenus();
	
	//Effect.Grow('userinfo');	
	//Effect.SlideDown('userinfo', { duration: 3.0 });
	//new Effect.toggle('userinfo', 'appear',{ queue: 'front' });
	//Effect.toggle('userinfo', 'blind');
	//Effect.SlideDown('userinfo');
	//alert ( document.body.offsetHeight )
	//new Effect.Move('userinfo', { x: 0, y: document.body.offsetHeight-35, mode: 'relative' });
	//new Effect.SlideUp('userinfo', { queue: 'end',duration:0.5 });
	//new Effect.SlideDown('userinfo', { queue: 'end',scaleFrom:0,scaleTo:100, duration:1.5 });
	
	/** tareas pendientes */
	reloadTask();
	new PeriodicalExecuter(reloadTask, SECONS_RELOAD_TASK);

}

/**
 * Carga todos los iconos que tenga el usuario
 */
function showIcons(){
	icons.each ( function (icon){
			id = addDesktopIcon(icon.ititle,icon.icon,icon.ileft,icon.itop,icon);
			addMenuContext('icon_container_'+id,'Ejecutar aplicación','launchApp','executeIcon("'+id+'")');
			addMenuContext('icon_container_'+id,'Eliminar acceso directo','deleteLink','deleteIcon('+Object.toJSON(id)+')');
			addMenuContext('icon_container_'+id,'Ver propiedades','propertiesLink','viewIconProperties('+Object.toJSON(id)+')');
			repaintContextMenu('icon_container_'+id);
	} );
}

/**
 * Carga los menus de usuario
 */
function showMenus(){
	menus.each ( function (menu){
		addMenuIcon(menu.title,menu.icon,"javascript:launchApplication('"+menu.position+"');");
	} );
}

/** 
 * Lanza la aplicacion con el nombre que recibe. Se hace asi para no sobre-cargar el metodo que añade menu, que solo acepta 
 * @param app
 * @returns
 */
var winLastPosition = 10;
var winLastUse = 1;
var winOffset = 0;
function launchApplication(app){
	menus.each ( function (menu){
		if (menu.position!=app) return;
		
		var execute = {"type":"data","class":"System","do":"getHandle","asynchronous":false};
		var responseJson  = DobleOSAPI.executeApplication(execute);
		
		var params = {"handle":responseJson.handle,
				"type":"application",
				"class":menu["class"],
				"do":menu["do"],
				//"parameters":menu["parameters"],
				"title":menu["title"],
				"width":menu["width"],
				"height":menu["height"],
				"closable":menu["closable"],
				"minimize":menu["minimize"],
				"maximize":menu["maximize"],
				"resizable":menu["resizable"],
				"modal":menu["modal"],
				"top":(winLastUse*winLastPosition)+(winOffset*10),
				"left":(winLastUse*winLastPosition)+(winOffset*10),
				"registry":true
				};
		
		DobleOSAPI.executeApplication ( params );
		if ( winLastUse>2 ) {winLastUse=0;winOffset++;};
		if ( winOffset>3 ) { winOffset=0;}
		winLastUse++;
		
	});
}

/**
 * Carga todas las aplicaciones que el usuario tuviera abiertas
 */
function showApplications(){
	process.each ( function (app){
		var params = {
				"type":'application',
				"handle":app.process_id,
				"sessionclass":app.process_id, //especial para que la ventana no lo calcule ( handle+class ) 
				"class":app["class"],
				"do":app["do"],
				"parameters":app["parameters"],
				"title":app["title"],
				"width":app["width"],
				"height":app["height"],
				"closable":app["closable"],
				"minimize":app["minimize"],
				"maximize":app["maximize"],
				"resizable":app["resizable"],
				"modal":app["modal"],
				"top":app["top"],
				"left":app["left"],
				"registry":true
				};
		DobleOSAPI.executeApplication ( params );
	} );

}

/**
 * Lee las tareas que el usuario tiene pendientes para pintarlas en pantalla 
*/
function reloadTask(){
	$('taskinfo').update("Actualizando tareas...");
	var execute = {"type":"data","class":"Tareas","do":"getUserTask","asynchronous":false};
	var responseJson  = DobleOSAPI.executeApplication(execute);
	if ( responseJson.error=="NO" && responseJson.total!="0" ){
		$('taskinfo').setStyle({"display":"block"});
		if ( responseJson.urgente )
			$('taskinfo').update("<a class=\"urgente\" href=\"#\" onclick=\"showListMyTask()\">Tienes tareas urgentes!!!</a>");
		else
			$('taskinfo').update("<a class=\"\" href=\"#\" onclick=\"showListMyTask()\">Tienes "+responseJson.total+" tareas pendientes</a>");
		
	} else 
		$('taskinfo').setStyle({"display":"none"});
}

/**
 * Lanza la ventan con el listado de tareas pendientes del usuario
 */
function showListMyTask(){
	
	var execute = {"type":"data","class":"System","do":"getHandle","asynchronous":false};
	var responseJson  = DobleOSAPI.executeApplication(execute);
	
	var params = {"handle":responseJson.handle,
			"type":"application",
			"class":"Tareas",
			"do":"getListMyTask",
			//"parameters":menu["parameters"],
			"title":"Mis Tareas",
			"width":"600",
			"height":"350",
			"closable":true,
			"minimize":false,
			"maximize":false,
			"resizable":true,
			"modal":true,
			"top":(150),
			"left":(50),
			"registry":false
			};
	
	DobleOSAPI.executeApplication ( params );
}
