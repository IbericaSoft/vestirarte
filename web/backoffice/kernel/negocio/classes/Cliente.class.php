<?
	/**
	 * 	Clase generica que almacena los datos de cliente
	 * 	Almacena los datos comunes de un cliente dentro de la tienda
	 * 
	 * 	@author Antonio Gámez
	 * 	@version 1.0 08.2014 Creacion
	 *
	 */

	class Cliente extends Tienda_Instanciador {		
		public $id=null;		
		public $nombre=null;
		public $email=null;
		public $telefono=null;
		public $password=null;
		public $direccion=null;
		public $poblacion=null;
		public $provincia=null;
		public $provincia_=null;
		public $cpostal=null;
		public $pais=null;
		public $pais_=null;			
		public $observaciones=null;
		
		
		/**
		 * Creamos el objeto cliente. 
		 * Dotamos al creador de la calse de un la posibilidad de determinar donde esta ubicado tomando su direccion IP
		 */
		public function __construct(){
			parent::$log->debug("Creando cliente");
			$this->ip = ClientAgent::remoteIP();			
		}
		
		/**
		 * Como su nombre indica, mapea los campos recibidos de una request, una query o lo que sea, que venga en un array
		 * a los atributos de esta clase
		 * @param unknown $array datos del formulario cliente, ficha cliente
		 */
		public function mapearDatos($array){
			parent::$log->debug("Mapeando datos de un array a Cliente.class... id=".$array[id]."...email=".$array[email]);
			if ( !$this->id&&$array[id] )//no tiene ID y viene uno
				$this->id = $array[id];
			$this->nombre 				= $array[nombre];			
			$this->email 				= $array[email];
			$this->password 			= $array[password];
			$this->telefono 			= $array[telefono];
			$this->observaciones 		= $array[observaciones];
			$this->razon 				= $array[razon];
			$this->nifcif 				= $array[nifcif];

			
		}
		
	}
?>
