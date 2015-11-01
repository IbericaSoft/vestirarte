<?
	/**
	 * Clase que almacena el resultado de una operacion.
	 * "Este objeto JSON se envia al cliente para informarle de resultado satisfactorio o erroneo de algo"
	 * codigo = 0 nos dice que todo ok
	 * objeto puede ser cualquier clase, cadena, ... es un atributo flexible que el cliente(usuario) debe conocer de antemano y intrepretar(parsear)
	 */
	class BeanGenerico {
		public $codigo = null;
		public $objeto = null;
		public $motivo = null;
		public $captcha= null;
		public function __construct($codigo,$objeto){
			$this->codigo = $codigo;
			$this->objeto = $objeto;
		}
	}
?>