/**
 * 
 */
var ROLL_OVER_ON   = {"background":"#CCC","cursor":"pointer"};
var ROLL_OVER_OFF  = {"background":"none"};

/**
 * Este metodo es invocado al cargar la pagina y prepara los eventos de los componentes de la pagina o inicializa
 * los datos necesarios para la ejecución de esta aplicación
 */
function onStartApplication(){
	setEffectsItems();
	

}

/**
 * Asigna los eventos de rollover a los botones de menú de la ventana
 */
function setEffectsItems(){
	$$('table.thumbnail').each(
		function(item){
			item.onmouseover 	= function(){ item.setStyle(ROLL_OVER_ON); };
			item.onmouseout 	= function(){ item.setStyle(ROLL_OVER_OFF); };
			item.title 			= 'Clic para aplicar';
			item.onclick		= function() { apply(item.getAttribute('idalias'),item.getAttribute('alias'),item.getAttribute('aliastype')); };
		}
	);
}

/**
 * Cambia el fondo de escritorio/tema
 * @param id identificador del fondo/tema
 * @param alias nombre del fichero de fondo o estilo del tema
 * @param type tipo de objeto con el trabajar. Wallpapers o themes.
 */
function apply(id,alias,type){
	var method = (type=='wallpaper')?'applyWallPaper':'applyTheme';
	var params = {"type":"data","asynchronous":false,"class":"Panel_Control","do":method,"id":id,"alias":alias};
	var responseJson = executeApplication(params);
	
	eval(method+"('"+responseJson.alias+"')");
}



/**
 * Muestra la ventana wallpapers/themes
 */
function showVentana(ventana){
	$$('div.wallpapers','div.themes').each(
			function(item){
				if ( item.getAttribute('class')==ventana)
					item.setStyle({'display':'block'});
				else
					item.setStyle({'display':'none'});
			}
	);
}

