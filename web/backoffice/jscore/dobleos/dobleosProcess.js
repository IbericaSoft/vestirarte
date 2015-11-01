/**
 * @author Doble H Software www.dobleh.com
 * @author Antonio Gámez
 * dobleOS system API
 * Gestion de procesos lanzados
 */
var VERSION = 1.0;
/**
 * Este array mantiene todos los procesos del escritorio. Entiendase por procesos las aplicaciones que se solicitan 
 * al servidor. En el servidor almacenamos los procesos que el usuario tiene abiertos de forma que podemos recuperar
 * lo que el usuario esta utilizando. En cada posición del array tenemos un objeto JSON con este formato : 
 * Formato:   
 * {
 *  'type':			tipo de origen del proceso (icon-application-menu)
 *  'process_id':	identificador del proceso 
 * ,'class':		nombre de la clase que atiende esta peticion
 * ,'do':			metodo de la clase utilizado
 * ,'sessionclass':
 * ,'width':
 * ,'height':
 * 'top':
 * 'left':
 * 'maximize':
 * 'minimize':
 * 'resizable':
 * 'closable':
 * 'status': custom, maximize, minimize
 * 'params': parametros de la ventana
 * }
 */
var DobleosProcess = {
	VERSION: 1.0,
	arrProcessList: new Array(),
	/** devuelve la version de este api */
	showVersion: function(){
		return showVersion;
	},
	/** ver la lista de procesos */
	viewProcessList: function(){
		list="Historico de procesos:\n";
		this.arrProcessList.each( function(proc) {
			//alert ( Object.toJSON(proc) );
			list+='>Proceso-ID:'+proc.process_id+' ('+proc._class+')  Estado:'+proc.status+"\n";
		} );
		alert(list);
	},
	/** Guarda un proceso */
	startProcess: function( executer ){
		//alert ( 'iniciando el proceso' );
		var newProcess = {};
		newProcess['process_id']= executer['sessionclass'];
		newProcess['do']		= 'windowProcess';
		newProcess['class']		= 'System';//es la clase System del servidor la que se hace cargo de esta tarea
		newProcess['type']		= 'data';
		newProcess['asynchronous']=false;
		newProcess['_class']    = executer['class'];
		newProcess['_do']    	= executer['do'];
		newProcess['_width']	= executer['width'];
		newProcess['_height']	= executer['height'];
		newProcess['_top']		= executer['top'];
		newProcess['_left']		= executer['left'];
		newProcess['_minimize']	= executer['minimize'];
		newProcess['_maximize']	= executer['maximize'];
		newProcess['_closable']	= executer['closable'];
		newProcess['_resizable']= executer['resizable'];
		newProcess['_status']	= 'custom';
		newProcess['_title']    = executer['title'];
		newProcess['_parameters']=executer['parameters'];
		newProcess['status']	= 'alive';
		this.arrProcessList[this.arrProcessList.length] = newProcess;
		DobleOSAPI.executeApplication(newProcess);
		//this.viewProcessList();
		return true;
	},
	/** 
	 * Notifica la finalizacion de un proceso. Lo que hacemos
	 * es sacarlo del array!! 
	 */
	endProcess: function( process ){
		//alert ( 'Se pide el proceso '+process + ' para terminarlo');		
		var encontrado = false;
		this.arrProcessList.each( function(proc) {
			if ( process==proc.process_id ){
				proc.status = 'die';
				encontrado=true;
			}
		});
		
		if ( !encontrado ) return;//si el proceso no existe (caso de ventanas de ayuda, info, otras) no mandamos peticion al server
		
		var newProcess = {};
		newProcess['class']		= 'System';//es la clase System del servidor la que se hace cargo de esta tarea
		newProcess['do']		= 'closeProcess';
		newProcess['type']		= 'data';
		newProcess['process']	= 'close';
		newProcess['process_id']= process;
		newProcess['asynchronous']=false;
		DobleOSAPI.executeApplication(newProcess);
		return true;
	},
	/**
	 * Actualiza los datos de un proceso abierto
	 */
	updateProcess: function ( process ){
		//alert ( 'Se pide el proceso '+process + ' para actualizarlo');
		//this.viewProcessList();
		var win = DobleosWindows.getWindowsHandle(process);
		var newProcess = {};
		newProcess['class']		= 'System';
		newProcess['do']		= 'updateProcess';
		newProcess['type']		= 'data';
		newProcess['process_id']= process;
		newProcess['_width']	= win.width;
		newProcess['_height']	= win.height;
		newProcess['_top']		= $(win.getId()).cumulativeOffset().top;
		newProcess['_left']		= $(win.getId()).cumulativeOffset().left;
		var winstatus = win.isMinimized()?'minimize':win.isMaximized()?'maximize':'custom';
		newProcess['_status']   = winstatus;
		newProcess['asynchronous']=true;
		DobleOSAPI.executeApplication(newProcess);
		return true;
	}
	
};