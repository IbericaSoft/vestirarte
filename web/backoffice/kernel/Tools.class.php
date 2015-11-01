<?
	/**
	 * Esta clase implementa los metodo comunes y permite que las aplicaciones implementen
	 * sus metodos propios. Todas las aplicaciones deben extender esta clase
	 * @author tony
	 *
	 */	

	abstract class Tools implements IApplications {

		/** el atributo oSystem tiene es una referencia a DobleOS */
		public $oSystem;
		
		public function __construct(DobleOS $os){
			$this->setInstance($os);
		}
		
		public function setInstance(DobleOS $os){
			$this->oSystem = $os;
		}
		
		
		abstract public function __destruct();
			
		public function start(){
			$this->oSystem->getLogger()->debug( 'No debería haber llegado aquí' );
		}	
					
		/**
		 * Inicializa la plantilla y los datos para la vista
		 * @param $key
		 * @param $datos
		 * @param $template
		 */
		public function computeTemplate($key, $datos, $template){			
			$this->oSystem->getLogger()->debug( "Cargada plantilla: ".$template );
			$this->oSystem->getDataTemplate()->setTemplate( $template );
			$this->oSystem->getLogger()->debug( "Cargados datos en key: ".$key );
			$this->oSystem->getDataTemplate()->addData($key, $datos);
		}
		
			/**
		 * Lanza una query con o sin paginacion
		 * @param $sql Sentencia a ejecutar
		 * @return Array con los resultados
		 */
		public function computeSQL($sql,$paginar){
			if ( $paginar ){				
				$this->oSystem->getLogger()->debug("Query paginada(".$this->getPage()."): $sql");
				$datos = $this->oSystem->getConnection()->queryPaginada( $sql, $this->getPage(), $this->oSystem->getConfigSystem()->getKeyData('pagination'));
			} else {
				$this->oSystem->getLogger()->debug("Query: $sql");
				$datos = $this->oSystem->getConnection()->query( $sql );
			}
			$this->oSystem->getLogger()->debug( "Cargando resultados: ". $this->oSystem->getConnection()->totalRegistros() );
	  		return $datos;
		}
		
	}
?>