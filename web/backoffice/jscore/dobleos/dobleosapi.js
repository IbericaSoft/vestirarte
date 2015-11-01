/**
 * @author Doble H Software www.dobleh.com
 * @author Antonio Gámez
 * dobleOS system API
 */

var DobleOSAPI = {
	DEBUG: _DEBUG,
	VERSION: 1.0,
	showVersion: function(){
		return showVersion;
	},
	require: function(filename) {
		var fileref=null;
		try{
			var js = /\.js$/i;
			var css= /\.css$/i;
			var ico= /\.ico$/i;
			if ( (js).test(filename) ){
				fileref=document.createElement('script');
				fileref.setAttribute("type","text/javascript");
				fileref.setAttribute("src", filename);
			}
			if ( (css).test(filename) ){
				fileref=document.createElement("link");
				fileref.setAttribute("rel", "stylesheet");
				fileref.setAttribute("type", "text/css");
				fileref.setAttribute("href", filename);
			}
			if ( (ico).test(filename) ){
				fileref=document.createElement("link");
				fileref.setAttribute("rel", "shortcut icon");
				fileref.setAttribute("href", filename);
			}
			document.getElementsByTagName('head')[0].appendChild(fileref);
	    } catch(e) {
	    	alert ("Problemas cargando los ficheros "+filename);
	    }
	},
	/** 
	 * Lanzar una operación contra el servidor. Puede ser de tres tipos:
	 *  para levantar una aplicación (icon y application),
	 *  para pedir datos (data)
	 *  para enviar un formulario (form)*/
	executeApplication: function(executer){
		if ( executer.type=='icon' || executer.type=='application' ){		
			DobleosWindows.newWindows(executer);
		}
		if ( executer.type=='data'){
			return this.requestJsonData(executer);
		}
		if ( executer.type=='form'){
			return this.sendFormData(executer);
		}
	},
	/** 
	 * Pide a una aplicacion del servidor datos para realizar alguna operacion en el cliente
	 * */
	requestJsonData: function(executable){
		if ( this.DEBUG ) alert ( "JsonData:"+Object.toJSON(executable) );
		if ( executable.asynchronous==null){
			alert("Error en los parametros de invocación DobleOS.resquestJsonData(). Falta asynchronous");
			return null;
		}
		if ( executable['class']==null){
			alert("Error en los parametros de invocación DobleOS.resquestJsonData(). Falta class");
			return null;
		}
		if ( executable['do']==null){
			alert("Error en los parametros de invocación DobleOS.resquestJsonData(). Falta do");
			return null;
		}
		
		var resultJSON = [{'error':'SI','errDescripcion':'No se han recibido datos desde el servidor'}];
		executable.channel = 'json';
		new Ajax.Request(WEB_PATH+'/', {
			  parameters: executable,
			  requestHeaders: {Accept: 'application/json'},
			  method:'get',
			  //encoding:'ISO-8859-1',
			  asynchronous: executable.asynchronous,
			  onComplete : function(transport){
				  if ( this.DEBUG ) alert ( "JsonDATA:"+transport.responseText );
				  var json = transport.responseText.evalJSON(true);
				  
				  //if ( json.callBack )
					//  json.callBack.each( function(call){ eval(call); } );
				  resultJSON = json;
			  },
			  onFailure : function(transport){
				  var json = transport.responseText.evalJSON(true);
				  alert ( json.error );
			  }
		} );
		return resultJSON;
	},
	/** Envia un formulario via AJAX */
	sendFormData: function(executable){
		var resultJSON = [{'error':'SI','errDescripcion':'No se han recibido datos desde el servidor'}];
		if ( this.DEBUG ) alert ( "sendFormData:"+Object.toJSON(executable) );
		if ( executable.form==null){
			alert("Error en los parametros de invocación DobleOS.sendFormData(). Falta form");
			return null;
		}
		
		$(executable.form).request({
			method:'get',
			asynchronous: false,
		 	requestHeaders: {Accept: 'application/json'},
			onComplete: function(transport){
				if ( this.DEBUG ) alert ( transport.responseText );
		 		resultJSON = transport.responseText.evalJSON(true);
			}
		});
		return resultJSON;
	}
	
};

DobleOSAPI.require(WEB_PATH+'/applications/images/favicon.ico');
DobleOSAPI.require(WEB_PATH+'/themes/'+WINDOWS_STYLE+'/desktop/desktop.css');
DobleOSAPI.require(WEB_PATH+'/themes/'+WINDOWS_STYLE+'/windows/theme/default.css');
DobleOSAPI.require(WEB_PATH+'/themes/'+WINDOWS_STYLE+'/windows/theme/windows.css');
DobleOSAPI.require(WEB_PATH+'/themes/'+WINDOWS_STYLE+'/contextmenu/proto.menu.css');

DobleOSAPI.require(WEB_PATH+'/jscore/system/prototype.js');
DobleOSAPI.require(WEB_PATH+'/jscore/system/shortcut.js');
DobleOSAPI.require(WEB_PATH+'/jscore/scriptaculous/scriptaculous.js');
DobleOSAPI.require(WEB_PATH+'/jscore/contextmenu/proto.menu.js');
DobleOSAPI.require(WEB_PATH+'/jscore/dobleos/dobleosIcons.js');
DobleOSAPI.require(WEB_PATH+'/jscore/dobleos/dobleosProcess.js');
DobleOSAPI.require(WEB_PATH+'/jscore/dobleos/dobleosWindows.js');

DobleOSAPI.require(WEB_PATH+'/jscore/windows/window.js');
DobleOSAPI.require(WEB_PATH+'/jscore/windows/effects.js');
DobleOSAPI.require(WEB_PATH+'/jscore/windows/tooltip.js');
DobleOSAPI.require(WEB_PATH+'/jscore/windows/window_effects.js');
DobleOSAPI.require(WEB_PATH+'/jscore/windows/window_ext.js');
DobleOSAPI.require(WEB_PATH+'/jscore/windows/window.js');

//DobleOSAPI.require(WEB_PATH+'/jscore/menu/euDock.2.0.js');
//DobleOSAPI.require(WEB_PATH+'/jscore/menu/euDock.Image.js');
//DobleOSAPI.require(WEB_PATH+'/jscore/menu/euDock.Label.js');


/** INICIO DEL DESKTOP */
function startDeskTop(){
	captureEventsDesktop();
	hookKeys();
	setMenuApplications();
	setDesktopIcons();
	//loadContextMenus('icon_container', contextMenuIcon);
	//loadContextMenus('desktop', contextMenuDesktop);
	//applyEffects(...);
	
	addMenuContext('desktop','Ver procesos','process','DobleosProcess.viewProcessList()');
	addMenuContext('desktop','Soporte e información','desktop','about()');
	repaintContextMenu('desktop');
	
}

/** Capturamos los eventos de cierre de la ventana del desktop para poder guardar las preferencias de pantalla actuales */
function captureEventsDesktop(){
	Event.observe(window,'unload',closeAll);
	Event.observe(window,'close',closeAll);
}

/** Control del estas teclas del teclado */
function hookKeys(){
	shortcut.add("F11",function() {
		alert('La tecla F11 está deshabilitada.\n\nEl modo pantalla completa es el óptimo para este sistema');
	},{
		'type':'keydown',
		'propagate':false,
		'target':document
	});
	
	shortcut.add("F5",function() {
		alert('La tecla F5 está deshabilitada.\n\nSi refrescas la pantalla se perderán las preferencias de pantalla que estes utilizando');
	},{
		'type':'keydown',
		'propagate':false,
		'target':document
	});
}

function shortCut(key,target,ffunction){
	shortcut.add(key,function() { eval(ffunction); },{'type':'keydown','propagate':false,'target':target} );
}

/** Nos permite volver a habilitar una tecla deshabilitada */
function enabledHotKey(key){
	for ( i=0;i<Hotkeys.hotkeys.length; i++ )
		if ( Hotkeys.hotkeys[i].combo==key )
			Hotkeys.hotkeys[i]=new Hotkey('null', null, null);
	alert ("<"+key+"> vuelve a estar habilitado");
}

/**
 * Añade un icon en el menu macosx
 */
function addMenuIcon(title,icon,link){
	if ( dock==null ) return;
	dock.addIcon(
			new Array(
					{euImage:{image: icon}},
					{euLabel:{object: {euImage:{image: icon}}, txt: title,
						style :MENU_LABEL_STYLE,
						anchor:euDOWN,
						offsetX:0,
						offsetY:-100 }
					}
					),{link:link});
}

/** Prepara el menu macosx */
var dock = null;
function setMenuApplications(){
	if ( null==dock ) return;
	dock.setScreenAlign(euDOWN,3);
	dock.setAnimation(euMOUSE,0.3);
	dock.setIconsOffset(5);
}

/** Carga los menus de contexto */
var arrContextualMenus = new Array();
function addMenuContext( className, text, type, link ){
	//creamos un array para el tipo de menu nuevo
	if ( arrContextualMenus[className]==null )
		arrContextualMenus[className] = [];
	var menu = { 'selector':className, 'name': text, 'className':type, 'callback': function(e){ eval(link) } };
	
	arrContextualMenus[className][arrContextualMenus[className].length] = menu
	
}

/** repinta, recalcula, el menu contextual */ 
function repaintContextMenu(className){
	new Proto.Menu({
	       selector:  '.'+arrContextualMenus[className][0].selector,
	       className: 'menu desktop',
	       menuItems: arrContextualMenus[className]
	});
}

/** Algunos efectos para el desktop */
function applyEffects( item ){
	Reflection.add(item, { height: 2/3, opacity: 1/3 });
}

/** al cerrar el sistema */
function closeAll(){	
	//de momento no hacemos nada
}

function applyWallPaper(img){
	$$('img.desktop').each( function(item){
		item.src=img;
	}
	);
}

function applyTheme(theme){	
	window.location.reload();
}

function about(){
	alert("...");
}