<?
/**
 * Excepcion del sistema
 * @author Antonio Gámez Moro
 *
 */
		
	class DobleOSException extends Exception {
		
		private $datos = null;
		private $codigo;
		
		
		/** Contruye el objeto  */
		public function __construct($message, $code, $datos){
			parent::__construct($message, $code);
			$this->datos = $datos;
		}
		
		public function getDatos(){
			return $this->datos;
		}
		
	}
?>