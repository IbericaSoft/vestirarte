<?
	/**
	 * Clase que almacena la información de un porte, es decir, de un solo valor de peso, pais, zonas, importe.
	 * "Este es un objeto JSON que forma parte de un array en Portes.class.php/PortesZonas.class.php"
	 * @author Antonio Gamez
	 * @version 1.0 06.14 Creación
	 */
	class BeanPorte {
		public $id;
		public $id_pais;
		public $id_zona;
		public $peso;
		public $importe;
		public function __construct($id,$id_pais,$id_zona,$peso,$importe){
			$this->id = $id;
			$this->id_pais = $id_pais;
			$this->id_zona = $id_zona;
			$this->peso = $peso;
			$this->importe = $importe;
		}
	}
?>
