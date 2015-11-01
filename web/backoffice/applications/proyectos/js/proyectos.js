/**********************************************************************************************
 * RUTINAS DEL PEDIDO
 * 
 * 
 **********************************************************************************************/


/**
 * Invocar a la ventana seleccion de cliente
 */
function doBuscadorClientes(){
	var params = {"handle":"","type":"application","class":'clientesplus',"do":"doMiniSearch","title":"Buscador Clientes","width":"600","height":"350","parameters":"callBack=selectClient&parent="+WINDOWS_HANDLE,"closable":true,"modal":true };
	executeApplication(params);
	return false;
}

/** 
 * Callback de vuelta con los datos del cliente seleccionado 
 */
function selectClient(cliente){
	$('id_cliente').value = cliente.id;
	$('cliente').value = cliente.cliente.toLowerCase();
	$('cli_telefonos').value = cliente.telefonos;
	$('cli_email').value = cliente.email.toLowerCase();
	$('cli_direccion').value = cliente.direccion;
	$('cli_poblacion').value = cliente.poblacion;
	$('cli_provincia').value = cliente.provincia;
	$('cli_cpostal').value = cliente.cpostal;
}

/**
 * Invocar a la ventana seleccion de vendedor
 */
function doBuscadorGestor(){
	var params = {"handle":"","type":"application","class":'administradores',"do":"doMiniSearch","title":"Buscador Gestores","width":"600","height":"350","parameters":"callBack=selectGestor&parent="+WINDOWS_HANDLE,"closable":true,"modal":true };
	executeApplication(params);
	return false;
}

/** 
 * Callback de vuelta con los datos del cliente seleccionado 
 */
function selectGestor(gestor){
	$('id_responsable').value = gestor.id;
	$('responsable').value = gestor.nombre;
}

/** Boton para ver y ocultar la ventanita de detalles del cliente */
function doDetallesClientes(){
	if ( $('clientDetails').getStyle('display')=='inline-block'  )
		$('clientDetails').setStyle({'display':'none'});
	else {
		$('clientDetails').setStyle({'display':'inline-block'});
		Effect.Shake($('clientDetails'));
	}
	return false;
}

/** Boton X ocultar la ventanita de detalles del cliente */
function doCerrarDetallesClientes(){
	doDetallesClientes();
	return false;
}

/** Boton para ver y ocultar la ventanita de detalles de la obra */
function doDetallesObra(){
	if ( $('obraDetails').getStyle('display')=='inline-block'  )
		$('obraDetails').setStyle({'display':'none'});
	else {
		$('obraDetails').setStyle({'display':'inline-block'});
		Effect.Shake($('obraDetails'));
	}
	return false;
}

/** Boton X ocultar la ventanita de detalles de la obra */
function doCerrarDetallesObra(){
	doDetallesObra();
	return false;
}

/**
 * Invocar a la ventana seleccion de articulos
 */
function doBuscadorArticulos(){
	var params = {"handle":"","type":"application","class":'articulos_app',"do":"doMiniSearch","title":"Buscador Artículos y servicios","width":"650","height":"400","parameters":"callBack=addArticulo&parent="+WINDOWS_HANDLE,"closable":true,"modal":true };
	executeApplication(params);
	return false;
}

/** Añadimos una fila detalle item */
function addArticulo(dataJson){
	//lo primero, miramos si el item añadido existe y de ser asi le incrementaremos una unidad, totalizamos y salimos de la rutina
	var existe = false;
	$$('input.cantidades[id="'+dataJson.id+'"]').each( function(item) {
		existe = true;
		item.value = (parseFloat(item.value)+1).toFixed(2);
		totalizar();
	});
	if ( existe )
		return;

	var cantidad = (dataJson.cantidad!=null)?dataJson.cantidad:0.00;
	var dto = (dataJson.dto!=null)?dataJson.dto:0.00;
	
	//nuevo item, no esta en el pedido.
	//columnas
	var ico = '&nbsp;';
	if ( !dataJson.readOnly )
		ico = new Element('IMG',{src:WEB_PATH+'/applications/_commons/_images/delete.png'});	
	var tr = new Element('TR',{'id':dataJson.id,'title':'click para eliminar','class':'detalle grid'}); 
	var td0= new Element('TD',{'class':'rowgrid_par','width':'30'} );
	var td1= new Element('TD',{'class':'rowgrid_par','width':'65'} );
	var td2= new Element('TD',{'class':'rowgrid_par','width':'105'} );
	var td3= new Element('TD',{'class':'rowgrid_par','width':'440'} );
	var td4= new Element('TD',{'class':'rowgrid_par','width':'75'} );
	var td5= new Element('TD',{'class':'rowgrid_par','width':'65'} );
	var td6= new Element('TD',{'class':'rowgrid_par','width':'75'} );
	//campos
	var in1= new Element('INPUT',{'id':dataJson.id,'type':'text','class':'p-mask grid text-number cantidades','size':'5','alt':"{type:'number', stripMask:true}",'value':cantidad,'disabled':dataJson.readOnly} );
	var in2= new Element('INPUT',{'id':dataJson.id,'type':'text','class':'gridisabled codigos','size':'12','disabled':'true','value':dataJson.codigo} );
	var in3= new Element('INPUT',{'id':dataJson.id,'type':'text','class':'gridisabled productos','size':'68','disabled':'true','value':dataJson.articulo,'title':dataJson.articulo} );
	var in4= new Element('INPUT',{'id':dataJson.id,'type':'text','class':'p-mask grid text-number precios','size':'8','alt':"{type:'number', stripMask:true}",'value':dataJson.precio,'disabled':dataJson.readOnly} );
	var in5= new Element('INPUT',{'id':dataJson.id,'type':'text','class':'p-mask grid text-number descuentos','size':'5','alt':"{type:'number', stripMask:true}",'value':dto,'disabled':dataJson.readOnly} );
	var in6= new Element('INPUT',{'id':dataJson.id,'type':'text','class':'gridisabled text-number importes','size':'8','disabled':'true'} );
	//eventos
	if ( !dataJson.readOnly )
		tr.onclick = function (){delArticulo(dataJson.id);};
	in1.onblur = totalizar;
	in4.onblur = totalizar;
	in5.onblur = totalizar;
	in1.onfocus = totalizar;
	in4.onfocus  = totalizar;
	in5.onfocus  = totalizar;
	//insertamos todo en el dom
	td0.insert(ico);
	td1.insert(in1);
	td2.insert(in2);
	td3.insert(in3);
	td4.insert(in4);
	td5.insert(in5);
	td6.insert(in6);
	tr.insert(td0);
	tr.insert(td1);
	tr.insert(td2);
	tr.insert(td3);
	tr.insert(td4);
	tr.insert(td5);
	tr.insert(td6);
	$('tablegrid').insert(tr);
	//cada añadido necesita refrescar los totales
	totalizar();
	//hay que aplicar las mascaras a los nuevos campos
	new pMask();
}

/**	Totales del pedido-obra */
function totalizar(){
	var total = 0.00;
	var items = 0;
	$$('input.cantidades').each(
			function(item){
				items++;
				var cantidad = item.value;
				//if ( cantidad==0 ) item.value = 0.00;//un error JS
				var subtotal = 0;
				var dto      = 0;					
				$$('input.precios[id="'+item.id+'"]').each( function(precio) {
					if (precio.value<0) precio.value=1.00;
					subtotal = cantidad * precio.value;
				});
				$$('input.descuentos[id="'+item.id+'"]').each( function(descuento) {
					if (descuento.value<0) descuento.value=0.00;
					dto = subtotal * (descuento.value/100);
				});
				$$('input.importes[id="'+item.id+'"]').each( function(importe) {
					importe.value = (subtotal - dto).toFixed(2);
					total += parseFloat(importe.value);
				});
			}
	);
	
	$('elementos').innerHTML = items;
	$('total').innerHTML = total.toFixed(2) + '&euro;';
	$('itotal').value = total.toFixed(2);
}

/** borramos la linea detalle de la obra-pedido y totalizamos*/
function delArticulo(id){
	var descripcion;
	$$('input.productos[id="'+id+'"]').each( function(producto) {descripcion = producto.value;});
	if ( !confirm("¿Eliminamos del pedido \""+descripcion+"\"?") )
		return;

	$$('tr.detalle[id="'+id+'"]').each( function(tr){ Element.remove(tr);} );
	totalizar();
}

/**
 * Invocar a la ventana seleccion de fechas obra
 */
function doFechasObra(){
	var params = {"handle":"","type":"application","class":'proyectos',"do":"doWorkDates","title":"Fechas Proyecto","width":"600","height":"500","parameters":"callBack=selectWorkDates&parent="+WINDOWS_HANDLE,"closable":true,"modal":true };
	executeApplication(params);
	return false;
}

/** 
 * Callback de vuelta con los datos de fechas de obra seleccionadas
 */
function selectWorkDates(fechas){
	$('finicio').value = fechas.inicio;
	$('ffin').value = fechas.fin;	
}

/**
 * salva los detalles de la obra-pedido, para ello, envia el detalle de cada línea al servidor
 * antes del submit de datos de la obra-pedido.
 * Recorremos cada linea y montamos peticion ajax
 */
function saveDetails(){
	var ok = true;
	var posicion = 0;

	//if ( parseFloat( $F('itotal'),10) ==0 ) 
	//	error=2;
	
	$$('input.cantidades').each(
			function(item){
				var cantidad  = item.value;
				if ( cantidad==0 ) //regla 1. cantidad cero error
					ok=false;
				if ( $F('estado')!='ABONO' && cantidad < 0 )  //regla 2. solo abonos admiten cantidades negativas
					ok=false;
				var id_articulo = item.id;
				var precio = 0.00;
				var dto = 0.00;
				var importe = 0.00;
				posicion++;
				$$('input.precios[id="'+item.id+'"]').each( function(sitem) { precio = sitem.value;	});
				if ( precio==0 || ($F('estado')!='ABONO' && precio < 0) )//regla 3. precios negativos solo en abonos
					ok=false;
				$$('input.descuentos[id="'+item.id+'"]').each( function(sitem) { dto = sitem.value;	});
				$$('input.importes[id="'+item.id+'"]').each( function(sitem) { importe = sitem.value; });
				if ( !ok )
					return;
				var queryJson = {"asynchronous":false,"type":"data","sessionclass":$F('sessionclass'),"class":"proyectos","do":"saveDetails",
						"posicion":posicion,"id_articulo":id_articulo,"cantidad":cantidad,"precio":precio,"dto":dto,"importe":importe };
				executeApplication(queryJson);
			}
	);
	
	return ok;
//	switch ( error ){
//		case 0: return true;break;
//		case 1: alert("Solo en los abonos puede indicar cantidades en negativo");return false;break;
//		case 2: alert('El total del pedido no puede ser cero');return false;break;
//	}
	
}
