/**
 * @author Dobleh Software. Antonio Gámez
 * @author Antonio Gámez
 * dobleOS system API
 * Gestion de ventanas de escritorio
 * @version 1.0 10.2011 creacion
 * @version 1.1 04.2012 las ventanas se abren y se cierran sin efectos FADE... las hace un poco mas rapidas
 */

var DobleosWindows = {
	VERSION: 1.1,
	arrWindows: new Array(),
	/** devuelve la version de este api */
	showVersion: function(){
		return showVersion;
	},	
	/** levanta una ventana nueva */
	newWindows: function(executable){
		if ( DobleOSAPI.DEBUG ) alert ( "WIN: " + Object.toJSON(executable) );
		if ( executable.handle==null){
			alert("Error en los parametros de invocación DobleosWindows.newWindows(). Falta handle");
			return null;
		}
		if ( executable['class']==null){
			alert("Error en los parametros de invocación DobleosWindows.newWindows(). Falta class");
			return null;
		}
		if ( executable['do']==null){
			alert("Error en los parametros de invocación DobleosWindows.newWindows(). Falta do");
			return null;
		}
		if ( !executable['sessionclass'] )
			executable['sessionclass'] = executable['class']+executable.handle;
		win = new Window(executable['sessionclass'],{className: WINDOWS_STYLE, title: 'Cargando...', 
			width:executable.width, height:executable.height, top:executable.top, left:executable.left, destroyOnClose: true, 
				minimizable:eval(executable.minimize), maximizable:eval(executable.maximize), closable:eval(executable.closable), 
				resizable:eval(executable.resizable), 
				onClose: function(){ DobleosProcess.endProcess(executable['sessionclass']); }, 
				onEndMove: function() {DobleosProcess.updateProcess(executable['sessionclass']);},
				onEndResize: function() {DobleosProcess.updateProcess(executable['sessionclass']);},
				onMinimize: function() {DobleosProcess.updateProcess(executable['sessionclass']);},
				onMaximize: function() {DobleosProcess.updateProcess(executable['sessionclass']);},
				showEffect: Element.show,
				hideEffect: Element.hide
				});
		
		var objectWindows = {'handle':executable['sessionclass'],'handleid':executable.handle,'windows':win,'class':executable['class'],'do':executable['do'],'parameters':executable.parameters };
		this.arrWindows[this.arrWindows.length] 	= objectWindows;
	
		var url = WEB_PATH+'/?';		
		url+='class='+executable['class']+'&do='+executable['do']+'&sessionclass='+executable['sessionclass'];
		//url+='class='+executable['class']+'&do='+executable['do']+'&sessionclass='+executable['class']+executable.handle;
		
		if ( executable.parameters )
			url+="&"+executable.parameters;
		if ( DobleOSAPI.DEBUG )	alert ( url );
		
		if ( executable.registry )
			DobleosProcess.startProcess( executable );
		
		win.setURL(url);
		//win.setLocation(executable.top,executable.left);
		win.setTitle(executable.title);
		if ( executable.modal )
			win.showCenter(true);
		else
			win.show(false);
	},
	/** devuelve una ventana por su handle */
	getWindowsHandle: function( handle ){
		var win;
		this.arrWindows.each( function(w) {
			if ( DobleOSAPI.DEBUG )	alert ('encontrada: '+w.handle);
			if ( w.handle == handle ){
				if ( DobleOSAPI.DEBUG )	alert ('devuelvo '+w.windows.getId());
				win = w.windows;
			}
		});
		return win;
	},
	/** devuelve una ventana por su handle id */
	getWindowsHandleID: function( handle ){
		var win;
		this.arrWindows.each( function(w) {
			//alert ('encontrada: '+proc.handle);
			if ( w.handleid == handle ){
				if ( DobleOSAPI.DEBUG )	alert ('devuelvo '+w.windows.getId());
				win = w.windows;
			}
		});
		return win;
	},
	/** devuelve una ventana por su nombre de clase */
	getWindowsByClassName: function( nameClass ){
		var win;
		this.arrWindows.each( function(w) {
			//alert ('encontrada: '+proc.handle);
			if ( w['class'] == nameClass ){
				if ( DobleOSAPI.DEBUG )	alert ('devuelvo '+w.windows.getId());
				win = w.windows;
			}
		});
		return win;
	}
};