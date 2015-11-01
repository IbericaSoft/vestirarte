/** Plugin para crear listas de detalles al vuelo para DobleOS */
(function($){
	$.fn.extend({
		columns : []
		,callbackDelete: null
		,detaiList: function(columns){
			$.extend(this.columns,columns);
			//console.debug("columnas:"+columns.length);
			var table = $("<table/>",{width:"100%"});
			
			//cabecera
			var head = $("<tr/>");
			//columna icono,numero fila,etc.
			var td = $("<td/>",{"width":"auto"});
			head.append( td.html("&nbsp;") );
			
			$(columns).each(function(i,col){
				//console.debug(i+":"+col.title);
				if ( null==col.title ) return;
				var td = $("<td/>",{"width":col.width});
				head.append( td.text(col.title) );
			});
			$(table).append(head);
			
			//creacion de la tabla en el documento
			this.append(table);
			return this;
		}
		,addRowData: function(data){
			//alert(JSON.stringify(data));
			//busco si en las columnas hay un campo unique y me quedo con su nombre
			var unique = null;
			$(this.columns).each(function(i,col){
				if ( col.unique ) unique=col.id;
			});
			
			$this = this;
			$(data).each(function(i,col){
				try {
					if ( unique!=null )
						$this.checkUnique(unique,col[unique]);
					$this.addRow();
					var currentTR = $this.find("table tr").last();
					for (key in col){
						var field = $(currentTR).find("."+key);
						if ( $(field).prop("tagName")=="INPUT" )
							$(field).val(col[key]);
						else
							$(field).text(col[key]);
					}
				} catch(e){console.error(e);}
			});
		}
		,addRow: function(){
			$this = this;
			var table = $(this).find("table");
			//filas
			var row = $("<tr/>");
			//icono, numero fila
			var cell = $("<td/>",{"width":"1%"}).html("&#x2327;").click(function(){$this.delete($(this).parent());});
			row.append( cell );
			$(this.columns).each(function(i,col){
				//console.debug("columna:"+col.title+":"+col.width);
				if ( null==col.title ) return;//si tiene title, es un campo visible
				var cell = $("<td/>",{"width":col.width});
				if ( col.mask ){
					var input = $("<input/>",{'class':col.clazz,'width':'75%'}).addClass( col.id );
					if ( col.longtext )
						input = $("<textarea/>",{'class':col.clazz,'width':'75%','height':'auto'}).addClass( col.id );
					
					if ( col.readOnly )						
						$(input).prop("readOnly","readOnly");					
					if ( col.css )
						$(input).css(col.css);
					switch ( col.mask ){
						case 'currency':
							$(input).val(col.value).maskMoney({thousands:'', decimal:'.', allowNegative: true, allowZero:true, precision:2});break;
						case 'integer':
							$(input).val(col.value).maskMoney({thousands:'', decimal:'.', allowNegative: true, allowZero:true, precision:0});break;
						case 'text':
							$(input).text(col.value);break;
					}
					if ( col.blur ){
						$(input).blur(eval(col.blur));
					}
					$(cell).append( input );
				} else {
					$(cell).attr("class",col.id);
				}	
				row.append( cell );
			});
			//campos ocultos
			//var currentTD = $(this).find("table tr td:first-child").last();			
			var currentTD = $(row).find("td:first-child");
			$(this.columns).each(function(i,col){
				if ( null!=col.title ) return;//si NO tiene title, es un campo oculto
				$("<input/>",{'type':'hidden','class':col.id}).val(col.value).appendTo( currentTD );				
			});
			//insertamos la fila en la tabla maestra			
			$(table).append(row);
			return this;
		}
		,checkUnique: function(key,value){			
			$("."+key).each(function(i){			
				if ( $(this).val()==value ){
					throw 999;
				}
			});
		}
		,delete: function(row){
			$(row).remove();
			if ( this.callbackDelete ) 
				eval(this.callbackDelete+"()" );
		}
		,deleteRow: function( callback ){
			this.callbackDelete = callback;
		}
	});
})(jQuery);