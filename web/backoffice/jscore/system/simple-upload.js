/** Plugin para subir ficheros para DobleOS */
(function($){
	alert("cargado");
	$.fn.upload = function(){
		init: function(){
			$(this).each(function(){
				//Efectos
				$j(this).on("dragover",function(){	
					$j(this).addClass("dragOver").removeClass("dragOut");
					console.log("Evento dragover");
				}).on("dragleave",function(){
					$j(this).addClass("dragOut").removeClass("dragOver");
					console.log("Evento dragleave");
				}).on("drop",function(){
					$j(this).addClass("drop").removeClass("dragOver").removeClass("dragOut");
					console.log("Evento drop");
				}) ;
				$j(this).find("span").click(function(){$j(this).parent().parent().find("input").click();}); //trigger para lanzar el dialogo upload al pinchar sobre el titulo del contenedor de los uploads
				//upload
				$j(this).fileupload({
					url: ".",
					dropZone: $j(this).find("div")[1],
				    dataType: 'json',
				    formData: {"channel":"json","class":"Upload","folder":$j(this).attr("folder-write"),"do":"save"},
				    add: function (e, data) {
				    	blockScreeen();
				    	for (i=0;i<data.files.length;i++){
				       		console.log( data.files[i].name );
				       		$j($j(this).find("div")[2]).text( "Subiendo..." );			       		
				       		data.submit();
				       	}
				    },
				    done: function (e,data){
				    	console.log(data);
				    	unBlockScreen();
				    	if ( data.result.result=="ok" ){
					    	//todo ok
			    			$j($j(this).find("div")[2]).text( data.result.file );
			    			$j($j(this).find("div")[1]).html("");
			    			$j($j(this).find("input")[1]).val( data.result.file );
			    			if ( $j(this).attr("type")=="image" )
			    				$j('<img/>',{class:"fotos",title:data.result.file,src:$j(this).attr("folder-read")+"/"+data.result.file}).appendTo( $j(this).find("div")[1] );
			    			else
			    				$j('<img/>',{class:"fotos",title:data.result.file,src:$j(this).attr("folder-read")+"/"+"document.png"}).appendTo( $j(this).find("div")[1] );
				    	} else {
				    		//error
				    		$j($j(this).find("div")[2]).text( data.result.description );
				    	}
				    }
				});
			});
		}
	};
})(jQuery);
	
//	
//	
//$j(".fileupload").each(function(){
//			//Efectos
//			$j(this).on("dragover",function(){	
//				$j(this).addClass("dragOver").removeClass("dragOut");
//				console.log("Evento dragover");
//			}).on("dragleave",function(){
//				$j(this).addClass("dragOut").removeClass("dragOver");
//				console.log("Evento dragleave");
//			}).on("drop",function(){
//				$j(this).addClass("drop").removeClass("dragOver").removeClass("dragOut");
//				console.log("Evento drop");
//			}) ;
//			$j(this).find("span").click(function(){$j(this).parent().parent().find("input").click();}); //trigger para lanzar el dialogo upload al pinchar sobre el titulo del contenedor de los uploads
//			//upload
//			$j(this).fileupload({
//				url: ".",
//				dropZone: $j(this).find("div")[1],
//			    dataType: 'json',
//			    formData: {"channel":"json","class":"Upload","folder":$j(this).attr("folder-write"),"do":"save"},
//			    add: function (e, data) {
//			    	blockScreeen();
//			    	for (i=0;i<data.files.length;i++){
//			       		console.log( data.files[i].name );
//			       		$j($j(this).find("div")[2]).text( "Subiendo..." );			       		
//			       		data.submit();
//			       	}
//			    },
//			    done: function (e,data){
//			    	console.log(data);
//			    	unBlockScreen();
//			    	if ( data.result.result=="ok" ){
//				    	//todo ok
//		    			$j($j(this).find("div")[2]).text( data.result.file );
//		    			$j($j(this).find("div")[1]).html("");
//		    			$j($j(this).find("input")[1]).val( data.result.file );
//		    			if ( $j(this).attr("type")=="image" )
//		    				$j('<img/>',{class:"fotos",title:data.result.file,src:$j(this).attr("folder-read")+"/"+data.result.file}).appendTo( $j(this).find("div")[1] );
//		    			else
//		    				$j('<img/>',{class:"fotos",title:data.result.file,src:$j(this).attr("folder-read")+"/"+"document.png"}).appendTo( $j(this).find("div")[1] );
//			    	} else {
//			    		//error
//			    		$j($j(this).find("div")[2]).text( data.result.description );
//			    	}
//			    }
//			});
//		
//		});