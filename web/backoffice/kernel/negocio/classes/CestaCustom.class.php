<?
	/**
	 * Cesta "especifica" de VestirArte
	 */
	class CestaCustom extends Cesta {

		/** añade un articulo a la cesta */
		public function anadeArticulo($id,$articulo,$unidades,$precio,$oferta,$iva,$peso,$foto,$talla){
			parent::$log->debug("Añado articulo \"$articulo\" a la cesta con $_REQUEST[unidades] unidades con precio $precio  oferta  $oferta peso $peso y talla $talla");
			
			$oArticulo = new BeanArticuloCustom();
			$oArticulo->id = $id;
			$oArticulo->articulo = $articulo;
			$oArticulo->unidades = $unidades;
			$oArticulo->precio = $precio;
			$oArticulo->oferta = $oferta;
			$oArticulo->precio_iva = round(((($iva/100)+1)*$precio),2);
			$oArticulo->oferta_iva = round(((($iva/100)+1)*$oferta),2);
			$oArticulo->iva = $iva;
			$oArticulo->peso = $peso;
			$oArticulo->foto = $foto;
			$oArticulo->talla = $talla;
			
			$com = ($oArticulo->oferta>0)?$oArticulo->oferta:$oArticulo->precio;			
			$oArticulo->subtotal = round($com*$unidades,2);
			$com = ($oArticulo->oferta_iva>0)?$oArticulo->oferta_iva:$oArticulo->precio_iva;
			$oArticulo->subtotal_iva = round($com*$unidades,2);			
			$formatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
			$oArticulo->precio_formato = $formatter->formatCurrency($precio, 'EUR');
			$oArticulo->oferta_formato = $formatter->formatCurrency($oferta, 'EUR');
			$oArticulo->precio_iva_formato = $formatter->formatCurrency($oArticulo->precio_iva, 'EUR');
			$oArticulo->oferta_iva_formato = $formatter->formatCurrency($oArticulo->oferta_iva, 'EUR');
			$oArticulo->iva_formato = $formatter->formatCurrency($iva, 'EUR');
			$oArticulo->subtotal_formato = $formatter->formatCurrency($oArticulo->subtotal, 'EUR');
			$oArticulo->subtotal_iva_formato = $formatter->formatCurrency($oArticulo->subtotal_iva, 'EUR');			
			$oArticulo->peso_formato = number_format($peso, '3',',','.');
			
			array_push( $this->listArticulos, $oArticulo);			
		}
		
		/**
		 * Incrementa unidades del articulo en la cesta
		 */
		public function masArticulo($variedad,$unidades){
			parent::$log->debug( "Incrementando unidades y recalculando" );
			$oArticulo = $this->getArticulo($variedad);
			$oArticulo->unidades+=$unidades;
 			$com = ($oArticulo->oferta>0)?$oArticulo->oferta:$oArticulo->precio;
 			$oArticulo->subtotal = round($com*$oArticulo->unidades,2);
 			$com = ($oArticulo->oferta_iva>0)?$oArticulo->oferta_iva:$oArticulo->precio_iva;
 			$oArticulo->subtotal_iva = round($com*$oArticulo->unidades,2);
 			$formatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
 			$oArticulo->precio_formato = $formatter->formatCurrency($precio, 'EUR');
 			$oArticulo->oferta_formato = $formatter->formatCurrency($oferta, 'EUR');
 			$oArticulo->precio_iva_formato = $formatter->formatCurrency($oArticulo->precio_iva, 'EUR');
 			$oArticulo->oferta_iva_formato = $formatter->formatCurrency($oArticulo->oferta_iva, 'EUR');
 			$oArticulo->iva_formato = $formatter->formatCurrency($iva, 'EUR');
 			$oArticulo->subtotal_formato = $formatter->formatCurrency($oArticulo->subtotal, 'EUR');
 			$oArticulo->subtotal_iva_formato = $formatter->formatCurrency($oArticulo->subtotal_iva, 'EUR');
 			$oArticulo->peso_formato = number_format($peso, '3',',','.');
		}
		
		/**
		 * Decrementa unidades del articulo en la cesta.
		 * Tenemos en cuenta que al decrementar las unidades del articulo no sean -1, en cuyo caso
		 * es una operacion de borrado... tambien la usamos de eliminar articulo
		 */
		public function menosArticulo($variedad, $unidades){
			parent::$log->debug( "Restando unidades y recalculando" );
			$oArticulo = $this->getArticulo($variedad);
			$oArticulo->unidades-=$unidades;
			$com = ($oArticulo->oferta>0)?$oArticulo->oferta:$oArticulo->precio;
			$oArticulo->subtotal = round($com*$oArticulo->unidades,2);
			$com = ($oArticulo->oferta_iva>0)?$oArticulo->oferta_iva:$oArticulo->precio_iva;
			$oArticulo->subtotal_iva = round($com*$oArticulo->unidades,2);
			$formatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
			$oArticulo->precio_formato = $formatter->formatCurrency($precio, 'EUR');
			$oArticulo->oferta_formato = $formatter->formatCurrency($oferta, 'EUR');
			$oArticulo->precio_iva_formato = $formatter->formatCurrency($oArticulo->precio_iva, 'EUR');
			$oArticulo->oferta_iva_formato = $formatter->formatCurrency($oArticulo->oferta_iva, 'EUR');
			$oArticulo->iva_formato = $formatter->formatCurrency($iva, 'EUR');
			$oArticulo->subtotal_formato = $formatter->formatCurrency($oArticulo->subtotal, 'EUR');
			$oArticulo->subtotal_iva_formato = $formatter->formatCurrency($oArticulo->subtotal_iva, 'EUR');
			$oArticulo->peso_formato = number_format($peso, '3',',','.');
			if ( $oArticulo->unidades<1 )
				$this->borrarArticulo($variedad);
				
		}
		
	}
?>