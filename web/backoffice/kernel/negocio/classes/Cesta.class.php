<?
	/**
	 * Cesta comun de cualquier tienda
	 * @version 1.0 06.2014 creacion
	 * @version 1.1 11.2014 quitamos los metodos que calculaban la fecha de entrega para ponerlos en portes
	 */
	class Cesta extends Tienda_Instanciador {
		public $listArticulos = null;		
		
		public function __construct(){			
			$this->listArticulos = array();			
		}
		
		/**
		 * Comprueba si la cesta tiene articulos
		 */
		public function hayCesta(){
			return (count($this->listArticulos)>0)?1:0;
		}
		
		/**
		 * Elimina el articulo indicado de la cesta
		 */
		public function borrarArticulo($articulo){
			parent::$log->debug("Borrar $articulo de la cesta");
			$cesta = new ArrayObject($this->listArticulos);
			$iterador = $cesta->getIterator();
			while($iterador->valid()){
				if (strtolower($iterador->current()->id) == $articulo){
					unset($this->listArticulos[$iterador->key()]);
					return;
				}
				$iterador->next();
			}
		}
				
		/** Busca en la cesta indicado y si existe devuelve true */
		public function existeArticulo($articulo){
			$articulo = $this->getArticulo($articulo);
			if ( $articulo!=null )
				return true;
			parent::$log->debug("No tengo $articulo en la cesta");
			return false;
		}
		
		/** 
		 * Itera en el array de la cesta para entregar el articulo deseado. Esta operacion
		 * es comun a practicamente todos los metodos de la clase
		 */
		public function getArticulo($variedad){
			$cesta = new ArrayObject($this->listArticulos);
			$iterador = $cesta->getIterator();
			while($iterador->valid()){				
				parent::$log->debug( ">>>iterando ".$iterador->current()->id );
				if ($iterador->current()->id == $variedad){
					parent::$log->debug( ">>>iguales" );
					return $iterador->current();
				}
				$iterador->next();
			}
			parent::$log->error(">>>>no encontre la variedad:$variedad");
			return null;
		}
		
		/** devuelve el numero total de articulos de la cesta */
		public function total_articulos(){
			if (count($this->listArticulos)==0) return 0;
			$total = 0;
			foreach ($this->listArticulos as $elArticulo)
				$total+=$elArticulo->unidades;
			return $total;
		}
		
		/** calcula el valor de la cesta sin iva */
		public function cesta_sin_iva(){
			$total = 0;
			foreach ($this->listArticulos as $elArticulo){
				$precio = $elArticulo->oferta>0?$elArticulo->oferta:$elArticulo->precio;
				$total+= round($elArticulo->unidades*$precio,2);				
			}
			return $total;
		}
		
		/** igual que la funcion anterior pero formateada la cantidad */
		public function cesta_sin_iva_formato(){
			$importe = $this->cesta_sin_iva();
			$formatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
			return $formatter->formatCurrency($importe, 'EUR');
		}
		
		/** calcula el valor de la cesta con iva */
		public function cesta_con_iva(){
			$total = 0;
			foreach ($this->listArticulos as $elArticulo){
				$precio = $elArticulo->oferta_iva>0?$elArticulo->oferta_iva:$elArticulo->precio_iva;
				$total+= round($elArticulo->unidades*$precio,2);
			}
			return $total;
		}
		
		/** igual que la funcion anterior pero formateada la cantidad */
		public function cesta_con_iva_formato(){
			$importe = $this->cesta_con_iva();
			$formatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);			
			return $formatter->formatCurrency($importe, 'EUR');
		}
		
		/**
		 * Impuestos resultantes de esta cesta... solo impuestos
		 */
		public function iva_articulos(){
			$total = $this->cesta_con_iva()-$this->cesta_sin_iva();			
			return $total;
		}
		
		/** igual que la funcion anterior pero formateada la cantidad */
		public function iva_articulos_formato(){
			$importe = $this->iva_articulos();
			$formatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
			return $formatter->formatCurrency($importe, 'EUR');
		}
		
		/** peso de la cesta */
		public function peso_cesta(){
			$peso = 0;
			foreach ($this->listArticulos as $elArticulo){
				$peso+=($elArticulo->peso*$elArticulo->unidades);
				parent::$log->debug("Peso $elArticulo->articulo= $elArticulo->peso(kg) * $elArticulo->unidades(u) = ".($elArticulo->unidades*$elArticulo->peso)." Peso acumulado de la cesta=".$peso);
			}
			return $peso;
		}
		
		/** igual que la funcion anterior pero formateado */
		public function peso_cesta_formato(){
			$p = $this->peso_cesta();
			return number_format($p,2,",",".");
		}
		
		/** Cual es el iva de la cesta... me vale con leer este valor del primer articulo */
		public function tipo_iva_cesta(){
			foreach ($this->listArticulos as $elArticulo)
				return $elArticulo->iva;
		}
		
		/** tipo de de iva de la cesta pero con formato */
		public function tipo_iva_cesta_formato(){
			$iva = $this->tipo_iva_cesta();			
			return number_format($iva,2,",",".").'%';
		}
		
	}
?>