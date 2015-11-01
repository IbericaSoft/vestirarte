<?
	/**
	 * Clase que almacena la información de la cesta. 
	 * "Este es un objeto JSON que se envia al cliente"
	 * @author Antonio Gamez
	 * @version 1.0 06.14 Creación
	 */
	class BeanCesta {		
		public $total_articulos = 0;  /** cuantas unidades de articulos/productos tiene la cesta */
		public $importe_articulos_con_iva = 0; /** importe total de los articulos de la cesta, incluido iva */
		public $importe_articulos_sin_iva = 0; /** importe total de los articulos de la cesta, sin incluir iva */
		public $importe_iva_articulos; /** importe del iva de los articulos de esta cesta */
		public $importe_portes_con_iva = 0;
		public $importe_portes_sin_iva = 0;
		public $importe_iva_portes = 0;		
		public $iva_aplicado_articulos = 0;
		public $iva_aplicado_portes = 0;
		public $lista_articulos = null; /** array de objetos BeanArticulo/*Custom con los detalles de cada articulo de la cesta */
		public $total_cesta_con_iva = 0; /** importe total de la cesta, lo que tiene que pagar el cliente */
		public $total_cesta_sin_iva = 0;
		public $peso_cesta = 0;
		public $total_iva_cesta = 0;
		
		public function __construct($ta,$ia,$ias,$iia,$ipi,$ips,$iip,$iaa,$iap,$tci,$tcs,$pc,$itc,$articulos){
			$this->total_articulos = $ta; //unidades de la cesta
			$this->importe_articulos_con_iva = $ia; //importe de los articulos, solo articulos, incluido su iva
			$this->importe_articulos_sin_iva = $ias; //importe de los articulos pero sin iva
			$this->importe_iva_articulos = $iia; //importe del iva de los articulos
			$this->importe_portes_con_iva = $ipi; //importe de los portes, incluido su iva
			$this->importe_portes_sin_iva = $ips; //importe de los portes pero sin iva
			$this->importe_iva_portes = $iip; //importe del iva de los portes
			$this->iva_aplicado_articulos = $iaa; //iva de los articulos
			$this->iva_aplicado_portes = $iap; //iva de los portes
			$this->total_cesta_con_iva = $tci; //total de la cesta con iva y formateado
			$this->total_cesta_sin_iva = $tcs; //total de la cesta sin iva y formateado
			$this->peso_cesta = $pc; //peso de la cesta formateado
			$this->total_iva_cesta = $itc; //importe total del iva de la cesta 
			$this->lista_articulos = $articulos; //array de objesto BeanArticuloCustom con los detalles de cada articulo
		}
	}
?>