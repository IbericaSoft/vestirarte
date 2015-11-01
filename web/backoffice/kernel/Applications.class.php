<?
	/**
	 * Esta clase implementa los metodo comunes y permite que las aplicaciones implementen
	 * sus metodos propios. Todas las aplicaciones deben extender esta clase
	 * @author Antonio Gamez
	 * @version 1.0 10.2011 creacion
	 * @version 1.1 03.2012 declarmos e implementamos método info y help
	 * @version 1.2 04.2012 metodos pdfGenericList y doMiniSearch. 
	 *  *nos traemos atributos publicos declaramos aqui en vez de en las clases descendientes
	 * @version 1.2.1 08.2012 Fix. En pdfGenericList incluimos sentencias para permitir la ejecución larga y ampliamos la memoria máxima
	 * @version 1.3 01-2014 Aplicamos estilo a las filas pares e impares en los listados genericos PDF (pdfGenericList)
	 * @version 1.4 03.2015
	 * @version 1.5 04.2015 Enconding UTF8 class
	 */	
	
	require_once ( OS_ROOT . '/kernel/dompdf/dompdf_config.inc.php');
	require_once ( OS_ROOT . '/kernel/Encoding.class.php');
	use \ForceUTF8\Encoding;
	abstract class Applications implements IApplications {

		/** el atributo oSystem tiene es una referencia a DobleOS */
		public $oSystem;
		public $pathApp;
		public $isPesistance;
		public $filtroSQL;
		public $persistenceName;
		public $oLogger;
		public $version_info, $version_detail;
		
		/**
		 * Al instanciar la clase por primera vez recibimos la referencia del objeto "sistema" que nos permitira
		 * interactual con el y gestionar sus objetos
		 * @param DobleOS $os
		 */
		public function __construct(DobleOS $os){
			$this->setInstance($os);
			$this->oLogger = Logger::getRootLogger();
			$this->pathApp = OS_ROOT.$this->PATHCLASS;
			$this->version_info = substr($this->VERSION,0,strpos($this->VERSION,'<'));
			$ini = strpos($this->VERSION,'<i>')+3;
			$fin = strpos($this->VERSION,'</i>');
			$this->version_detail = substr($this->VERSION,$ini,($fin-$ini));
		}		
		
		/**
		 * Una vez instanciada la clase todas las peticiones de esta clase se gestionan desde aqui, es decir,
		 * recuperar el objeto de la sesion no tiene misterior $_SESSION[xxx], pero la referencia del objeto "sistema"
		 * hay que pasarla recuperar la clase, y esto lo hacemos invocando este metodo 
		 * @see IApplications::setInstance()
		 */
		public function setInstance(DobleOS $os){
			$this->oSystem = $os;
			$this->oLogger = Logger::getRootLogger();
			$this->oLogger->debug( "cargando clase ".get_class($this) );
			$this->isPesistance = true;
			$this->persistenceName = $this->oSystem->getOrderActionClass()->getClassSession();
		}
		
		abstract public function __destruct();
		
		abstract public function bindingsData();
				
		/**
		 * Lanza una query con o sin paginacion
		 */
		public function computeSQL($sql,$paginar){
			if ( $paginar ){				
				$this->oSystem->getLogger()->debug("Query paginada(".$this->getPage()."): $sql");
				$datos = $this->oSystem->getConnection()->queryPaginada( $sql, $this->getPage(), $this->oSystem->getConfigSystem()->getKeyData('pagination'));
			} else {
				$this->oSystem->getLogger()->debug("Query: $sql");
				$datos = $this->oSystem->getConnection()->query( $sql );
			}
			if ( strpos(strtolower($sql),"select ")!==FALSE )
				$this->oSystem->getLogger()->debug( "RESULTADOS: ". $this->oSystem->getConnection()->totalRegistros() );
			else
				$this->oSystem->getLogger()->debug( "AFECTADOS: ". $this->oSystem->getConnection()-> getFilasAfectadas() );
	  		return $datos;
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
		 * Exporta los datos de la vista actual a formato CSV
		 * @param unknown_type $key
		 * @param unknown_type $titles
		 * @param unknown_type $columns
		 * @param unknown_type $template
		 * @throws Exception La vista actual tiene que estar filtrada
		 */
		public function generalExportCSV($key, $titles, $columns, $template){
			$this->oSystem->getLogger()->debug( "Exportando resultados CSV" );
			if ( $this->filtroSQL==null ){
				$datos = array('titulo'=>'Error en la exportarción','mensaje'=>'Hay que aplicar un filtro antes de poder exportar datos','url'=>"$_SERVER[PHP_SELF]",'class'=>get_class($this),'do'=>'listAll','sessionclass'=>$this->persistenceName);
				throw new DobleOSException('Error en la exportación CSV', 999, $datos);
			}
			
			foreach ( $titles as $title )
				$contenido .= "$title;";
			$contenido .= "\n\n";
			
			$resultado = $this->computeSQL($this->filtroSQL);
			while ( $datos = $this->oSystem->getConnection()->getColumnas($resultado) ){
				foreach ( $columns as $column )
					$contenido.= preg_replace('/;/','',preg_replace('/\r\n/','',html_entity_decode($datos[$column]))).';'; 
				$contenido.= "\n";
			}
			
			$this->computeTemplate($key, $contenido, $template);
		}
		
		/**
		 * Inicializamos la pagina en la navegacion por listas
		 */
		private function getPage(){
			if ( $_REQUEST[pagina]==null && $this->pagina == null  )
				$this->pagina = 1;
			else if ( $_REQUEST[pagina]!=null )
				$this->pagina = (int)$_REQUEST[pagina];
			return $this->pagina;
		}
		
	
		/**
		 * Impresion de un listado en PDF
		 * @param $fields array de campos con la nomenclatura de formatos ya conocida
		 * @param $css path donde encontrar las hojas de estilo
		 * @param $fileName nombre resultando del documento
		 */
		public function pdfGenericList($fields,$css,$fileName){			
			set_time_limit(0);//problema de tiempo y espacio, con esto solucionado
			ini_set("memory_limit", "999M");
			ini_set("max_execution_time", "999");
			$resultado = $this->computeSQL($this->filtroSQL,false);			
			$html =  file_get_contents( OS_ROOT."/applications/".strtolower(get_class($this))."/print_listado.html") ;
			$html = str_replace("{WEB_PATH}",OS_ROOT,$html);
			$html = str_replace("{hoy}",date("d/m/Y"),$html);
			ereg("(<!--LIST1)(.*)(LIST1-->)",$html,$reg);
			$par=false;
			while ( $datos = $this->oSystem->getConnection()->getColumnas($resultado) ){
				/** colorear las filas pares e impares con estilos */ 
				if ( $par )
					$html = str_replace('{alt}',"alt",$html);
				else
					$html = str_replace('{alt}',"",$html);
				$par=!$par;
				/** fin colorear */ 
				foreach ( $fields as $field ){
					$format = substr($field, 0, 2);
					switch ( $format ){
							case '@n'://no format
								$field = substr($field, 2);								
								break;
							case '@p'://porcen
								$field = substr($field, 2);
								$datos[$field] = number_format($datos[$field],2,',','.')." %";
								break;
							case '@c'://currency
								$field = substr($field, 2);
								$datos[$field] = number_format($datos[$field],2,',','.')." &#0128;";
								break;
							case '@i'://minusculas
								$field = substr($field, 2);
								$datos[$field] = strtolower($datos[$field]);
								break;	
							default://sin formatos
								$datos[$field] = Encoding::fixUTF8($datos[$field]);
					}				
					$html = str_replace('{'.$field.'}',$datos[$field],$html);
				}
				
				$html = str_replace("<!--LIST1","",$html);
				$html = str_replace("LIST1-->","<!--NEXT-->",$html);
				$html = str_replace("<!--NEXT-->","\n$reg[1]$reg[2]$reg[3]\n",$html);
			}
				
			$dompdf = new DOMPDF();
			$dompdf->set_base_path ( $css );
			$dompdf->load_html( $html );
			$dompdf->render();
			$dompdf->stream( $fileName );
		}
		
	}
?>