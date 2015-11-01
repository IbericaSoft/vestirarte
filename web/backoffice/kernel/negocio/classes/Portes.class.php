<?
	/**
	 * Almacen de datos del porte de la cesta
	 */
	class Portes extends Tienda_Instanciador{
		public $listaPortesZona = null;//array de clase Porte con todos los peso/importe de la zona(pais-provincia) en la que estamos)
		
		public $id_zona = null;
		public $peso 	= null;
		public $importe = null;
		public $iva_portes = null;
		
		public function __construct(){
		}
		
		/**
		 * Con el codigo de provincia que es el que determina la zona de portes (pais_zonas->id) podemos ir a la tabla de portes para cargar todos
		 * los portes aplicables a esa zona
		 * @param unknown $id_provincia codigo id de la provincia (no confundir con el codigo zip)
		 */
		public function cargarPortes($id_provincia){
			$conn = Navigator::$connection;
			$resultado = $conn->query("SELECT * FROM portes WHERE id_zona=$id_provincia ORDER BY peso;");
			$this->listaPortesZona = array();
			while ( $rows = $conn->getColumnas($resultado) ){
				parent::$log->debug("cargando portes para zona $rows[id_zona], peso $rows[peso], importe $rows[importe]");
				array_push( $this->listaPortesZona, new BeanPorte($rows[id],$rows[id_pais],$rows[id_zona],$rows[peso],$rows[importe]));
			}
		}
		
		/** recibe el peso a buscar en el array de valores peso/importe y extralos datos del porte a aplicar, almacenandolos en los atributos simples id,pais,zona,peso... */
		public function calculaPorte($peso){
			foreach ($this->listaPortesZona as $porte){
				//parent::$log->debug("Buscando peso porte para $porte->peso <= $peso");
				if ( $porte->peso >= $peso ){
					parent::$log->debug("El importe para este porte es Peso($peso) Importe($porte->importe) encontrado en el rango hasta ($porte->peso)");
					$this->id_zona = $porte->id_zona;
					$this->peso = $peso;
					$this->importe = $porte->importe;
					return;
				}
			}
		}
		
		/** calcula el valor del porte con iva */
		public function porte_con_iva(){
			$total = (($this->iva_portes/100)+1)*$this->importe;
			return $total;
		}
		
		/** calcula el valor del porte SIN iva */
		public function porte_sin_iva(){
			return $this->importe;
		}
		
		/** calcula los impuestos del porte... solo impuestos */
		public function impuestos(){
			$total = $this->porte_con_iva()-$this->porte_sin_iva();
			return $total;
		}
		
		/** Cual es el iva de los portes... es un valor que se da por configuracion en la tabla app_conf */
		public function tipo_iva_portes(){
			return $this->iva_portes;
		}
		
	}
?>
