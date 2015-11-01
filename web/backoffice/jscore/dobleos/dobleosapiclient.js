/**
 * Iniciamos todas las acciones del escritorio en el onload method del body
 */
var DobleosapiClient = {
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
	}
};

window.onload = start;

function start(){
	//DobleosapiClient.require(WEB_PATH+'/jscore/system/prototype.js');
	//DobleosapiClient.require(WEB_PATH+'/jscore/system/shortcut.js');
	initd();
}

function executeApplication(executable){
	var dataJSON  = parent.DobleOSAPI.executeApplication(executable);	
	//if ( dataJSON.callBack )
	//	dataJSON.callBack.each( function(call){ eval(call); } );
//	if ( dataJSON.error &&  dataJSON.error=='SI' )
//		alert ( dataJSON.errDescripcion );
	
	return dataJSON;
}

function endDobleosProcess( proc ){
	parent.endDobleosProcess( proc );
}

function addParamDobleosProcess( proc, params ){
	parent.addParamDobleosProcess( proc, params );
}

function viewParamDobleosProcess(){
	parent.viewParamDobleosProcess();
}

function getWindowsHandle(handle){
	return parent.DobleosWindows.getWindowsHandle(handle);
}

function getWindowsHandleID(handle){
	return parent.DobleosWindows.getWindowsHandleID(handle);
}

function getWindowsByClassName(handle){
	return parent.DobleosWindows.getWindowsByClassName(handle);
}

function shortCut(key,target,ffunction){
	parent.shortcut.add(key,function() { eval(ffunction); },{'type':'keydown','propagate':false,'target':target} );
}

function addDesktopIcon(title,icon,left,top,iconJson){
	return parent.addDesktopIcon(title,icon,left,top,iconJson);
}

function addMenuContext( className, text, type, link ){
	parent.addMenuContext( className, text, type, link );
}

function repaintContextMenu(className){
	parent.repaintContextMenu(className);
}

function getIconHandle( handle ){
	return parent.getIconHandle( handle );
}

function applyWallPaper(img){
	parent.applyWallPaper(img);
}

function applyTheme(theme){
	parent.applyTheme(theme);
}

function reloadTask(){
	parent.reloadTask();
}