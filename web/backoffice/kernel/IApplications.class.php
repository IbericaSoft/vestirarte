<?
	/**
	 * 
	 * Todas las aplicaciones del sistema dobleOS deben implementar esta interface
	 * o extender de una subclase de esta interface
	 * @author tony
	 *
	 */
	interface IApplications {
		
		/**
		 * Contrucción del objeto recibiendo la referencia del sistema 
		 * @param DobleOS $os
		 */
		public function __construct(DobleOS $os);
		
		/**
		 * Inicializacion de la instancia con la referencia del sistema
		 * @param DobleOS $os
		 */
		public function setInstance(DobleOS $os);
		
		/**
		 * @method Metodo de inicio de una clase de gestion de datos
		 */
		public function start();
		
	}
?>