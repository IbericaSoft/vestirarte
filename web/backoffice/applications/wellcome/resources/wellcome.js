/**
 * Iniciamos todas las acciones del escritorio en el onload method del body
 */
function start(){
	
	//var execute = {"type":"data","class":"System","do":"getHandle","asynchronous":false};
	//var responseJson  = DobleOSAPI.executeApplication(execute);
	/** wallpaper */
	$$('img.desktop').last().setAttribute('src',DESKTOP_WALLPAPER);
	
	var params = {"handle":-1,"type":"application","class":"Wellcome","do":"viewWellcomeMsg","title":_SYSVERSION,"width":"400","height":"200","modal":true };	
	DobleOSAPI.executeApplication(params);

	shortcut.add("F1",function() {
		continuar();
	},{
		'type':'keydown',
		'propagate':false,
		'target':document
	});
	
	shortcut.add("F11",function() {
		continuar();
	},{
		'type':'keydown',
		'propagate':true,
		'target':document
	});
	
	shortcut.add("F5",function() {
		//
	},{
		'type':'keydown',
		'propagate':false,
		'target':document
	});
	
}

function continuar(){
	//Dialog.closeInfo();
	//DobleosWindows.getWindowsHandle(WINDOWS_HANDLE).destroy();
	
	//var execute = {"type":"data","class":"System","do":"getHandle","asynchronous":false};
	//var responseJson  = DobleOSAPI.executeApplication(execute);
	
	var params = {"handle":-1,"type":"application","class":"Login","do":"viewLogin","title":"","width":"400","height":"170","modal":true };
	DobleOSAPI.executeApplication(params);
	
	shortcut.add("F11",function() {
		//cancelado f11
	},{
		'type':'keydown',
		'propagate':false,
		'target':document
	});
}

 