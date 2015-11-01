<?
	/**
	 * Clase para subir ficheros
	 * @version 1.0 01.2013 creacion
	 * @version 1.1 11.2014 fix para que la carpeta folder detecte si estamos trabajando con una ruta relativa, con lo que añade DOCUMENT_ROOT, o absoluta,
	 * con lo que no tiene que hacer nada.
	 *
	 */
	class Upload extends Tools {		
		const PATH_ABSOLUTE = false;
		private $err;
		private $folder;
		private $oLogger;
		private $allowedExtensions = array("image/jpeg", "image/jpg", "image/png", "application/pdf", "text/xml");
		public $sizeLimit = 20485760;
		
		/**
		 * En el constructor de la clase recibimos el objeto DobleOS que nos permite disponer de acceso a la gestion el sistema
		 * @param $system Es el objeto Session que nos pasan por 'referencia'
		 */
		public function __construct( DobleOS $system ){
			parent::__construct($system);
			$this->oLogger = Logger::getRootLogger();
		}
		
		/**
		 * Recuperar de la sesion esta clase implica invocar este metodo, no se vuelve a construir
		 * @see root/system/Applications::setInstance()
		 */
		public function setInstance( DobleOS $system ){			
			parent::setInstance($system);
			$this->oLogger = Logger::getRootLogger();
		}
		
		public function __destruct(){			
		}
		
		public function save(){
			$this->oSystem->getLogger()->debug( "Upload ficheros" );
			set_time_limit(0);
			$result = array();			
			$field = 'files';
			$this->oSystem->getLogger()->debug( $_FILES[$field]["tmp_name"][0] );
			$this->oSystem->getLogger()->debug( $_FILES[$field]["name"][0] );
			$this->oSystem->getLogger()->debug( $_FILES[$field]["type"][0] );
			$this->oSystem->getLogger()->debug( $_FILES[$field]["size"][0] / 1024 );
			$this->oSystem->getLogger()->debug( $_REQUEST[folder] );
			
			$tmp  =  $_FILES[$field]["tmp_name"][0];
			$name =  $_FILES[$field]["name"][0];
			$type =  $_FILES[$field]["type"][0];
			$size =  $_FILES[$field]["size"][0] / 1024;
 			//if (PATH_ABSOLUTE) $folder=$_REQUEST[folder];
			//else 
			$folder=$_SERVER['DOCUMENT_ROOT'].$_REQUEST[folder];
			
			$this->oLogger->debug($folder);
			
			if ( !in_array($type,$this->allowedExtensions) )
				$result = array("result"=>"ko","description"=>"tipo de fichero no permitido");
			else if ( $size >= $this->sizeLimit )
				$result = array("result"=>"ko","description"=>"tamaño excesivo");
			else if ( !is_writable($folder) )
				$result = array("result"=>"ko","description"=>"Sin permisos en $_REQUEST[folder]");
			else {
				$res = move_uploaded_file($tmp, "$folder/$name");
				if ( $res )
					$result = array("result"=>"ok","file"=>$name);
				else 
					$result = array("result"=>"ko","description"=>$res);
			}
			
			$this->oSystem->getLogger()->debug( $result );
			
			if ( $_REQUEST[channel]=="html")
				$this->oSystem->getDataTemplate()->addData('upload', $result);
			else
				$this->oSystem->getDataTemplate()->addData('json', json_encode($result));
			return;
		}
	}
?>