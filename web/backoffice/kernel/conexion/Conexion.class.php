<?	
	/**
		Autor Antonio Gámez 
		Version 2.3 (03/2012) cambio en los literales de la paginación para que no muestra N de 0 si no hay datos y tambien se añade a este literal el total de registros
		Version 2.2 (16/11/2011) paginación para JSON
		Version 2.1 (13/04/2011) setHtmlPagination nos permite eliminar la necesidad de mantener cuatro ficheros html con las flechas de paginación
		Version 2.0 (13/03/2011) Utilizamos el destructor de la clase para cerrar la conexion abierta, ademas, empezamos a usar el constructor
								 de los llamados metodos magic (__contruct,__destruct, etc.)
		Version 1.8 (17/05/2010) La conexiones ya no son persistentes por defecto
		Version 1.7 (16/11/2008) Se añade puerto a la conexion. 
		Version 1.6 (21/09/2008) Error en Mover puntero de resultados. 
		Version 1.5 (07/08/2008) Mover puntero de resultados
		Version 1.4 (22/07/2008) Paginación
		Version 1.3 (23/02/2008) Se crea el metodo hayResultados() que permite chequear si la última select devolvio resultados
		Version 1.2 (14/02/2008) Se crea el metodo lastQueryIsOK() que permite validar si la ultima operación contra bbdd funciono
		Version 1.1 (28/12/2007) Si ya hay una conexion establecida el método getConnection no trata de crear otra conexión y la devuelve
		Version 1.0 (28/12/2007) Creacion
		Clase simple para interacturar con una base de datos MySQL
	*/
	class Conexion {

		var $host				=	null;
		var $usuario		=	null;
		var $password		=	null;
		var $bbdd				=	null;
		var $port				=	null;
		var $error			=	null;
		var $connection	=	null;
		var $ultimoResultado  = null;
		//paginacion
		var $ultimoResultadoTotalPaginado = null;
		var $pagina     = null;
		var $limite     = null;

		private $linkPaginacion;
		private $firstPage;
		private $prevPage;
		private $nextPage;
		private $lastPage;
		protected static $log;
		
//_________________________________________________________________________________________		
		public function __construct($host,$usuario,$password,$bbdd,$port=3306){
			$this->host 		= "$host:$port";
			$this->usuario 	= $usuario;
			$this->password	= $password;
			$this->bbdd 		= $bbdd;
			self::$log = Logger::getRootLogger();
		}
		
		public function __destruct(){	
			//mysql_close($this->connection);
			$this->connection = null;
		}
		
		/**
		 * 
		 * Enter description here ...
		 * @param unknown_type $htmlFirst
		 * @param unknown_type $htmlPrevious
		 * @param unknown_type $htmlNext
		 * @param unknown_type $htmlLast
		 */
		public function setHtmlPagination($htmlFirst,$htmlPrevious,$htmlNext,$htmlLast){
			$this->firstPage = $htmlFirst;
			$this->prevPage  = $htmlPrevious;	
			$this->nextPage  = $htmlNext;
			$this->lastPage  = $htmlLast;
		}
//_________________________________________________________________________________________		
		/*Conecta a la base de datos. Retorna una conexión*/
		function getConnection() {
			$this->error = null;
			
			if ( $this->connection != null ) 
				return $this->connection;
			$connection = null;
			
			if ( !$this->host || !$this->usuario || !$this->password || !$this->bbdd ){
				$this->error = 'Error en los parámetros de conexión';
				return false;
			}else{
				$connection = mysql_pconnect( $this->host,$this->usuario,$this->password );
			}
			
			if ( !$connection ) {
 				$this->error = 'No es posible conectar a la base de datos con las credenciales actuales';
 				return false;
 			}
 			
 			if (!mysql_select_db( $this->bbdd, $connection )) {
 				echo mysql_errno();
 				$this->error = 'Error al seleccionar la base de datos ' . mysql_errno();
 				return false;
			}

			$this->connection = $connection;//me guardo la conexion para utilizarla despues			
			return $connection;
		}

//_________________________________________________________________________________________		
		/*Querys. Retorna un resultset*/
		function query($sql){
			if ( self::$log )
				self::$log->debug("Query: $sql");
			
			$this->ultimoResultado = mysql_query($sql,$this->connection);			
			if (!$this->ultimoResultado)
				return false;
			return $this->ultimoResultado;
		}
//_________________________________________________________________________________________		
		/*Consultas con paginación (SOLO CONSULTAS SELECT). Retorna un resultset*/
		function queryPaginada($sql,$pagina,$limite){
			//if ( self::$log )
			//	self::$log->debug("Query: $sql");
			
			//query sin limitar para determinar el total de registros
			$this->ultimoResultadoTotalPaginado = mysql_query($sql,$this->connection);			
			
			//query limitada para paginar
			if (!$pagina) $pagina = 1;
			if (!$limite) $limite = 100;
			$this->pagina = $pagina;
			$this->limite = $limite;
			//paginacion real para la bbdd, es decir, son multiplos del limite
			$pagina = ($pagina-1) * $limite;
			
			if ( $pagina > $this->totalRegistros() )
				die("La pagina solicitada sobrepasa los resultados !!! ");
			
			$sql .= " limit $pagina,$limite";			
			$this->ultimoResultado = mysql_query($sql,$this->connection);			
			
			if ($this->error)
				die ($this->error);
			
			if (!$this->ultimoResultado)
				return false;
			
			
			return $this->ultimoResultado;
		} 
//_________________________________________________________________________________________		
		/*Devuelve las columnas de una fila de resultados*/
		function getColumnas($resultado){
			return mysql_fetch_array($resultado);
		}
//_________________________________________________________________________________________		
		/*Devuelve true si la ultima operacion insert update delete, modifico el número de filas que se indica en el parámetro*/
		function lastQueryIsOK($maxFilas){
			$maxFilas = ((int)$maxFilas>0)?$maxFilas:1;
			return ( mysql_affected_rows() == $maxFilas )?true:false;
		}
//_________________________________________________________________________________________		
		/*Devuelve true si la ultima operacion insert update delete, modifico el número de filas que se indica en el parámetro*/
		function getFilasAfectadas(){			
			return ( mysql_affected_rows() );
		}
//_________________________________________________________________________________________		
		/*Devuelve el número de resultados si la ultima operacion select*/
		function hayResultados(){			
			return mysql_num_rows($this->ultimoResultado);
		}
//_________________________________________________________________________________________		
		/*Devuelve el total de registro de la query */
		function totalRegistros(){			
			 if ( $this->ultimoResultadoTotalPaginado )
			 	return mysql_num_rows($this->ultimoResultadoTotalPaginado);
			 else
			 	return mysql_num_rows($this->ultimoResultado);
		}
//_________________________________________________________________________________________		
		/*Pagina actual*/
		function paginaActual(){
			return $this->pagina;
		}
//_________________________________________________________________________________________		
		/*Primera pagina de la paginacion*/
		function primeraPagina(){
			return 1;
		}
//_________________________________________________________________________________________		
		/*Ultima pagina de la paginacion*/
		function ultimaPagina(){
			$grupos = $this->totalRegistros() / $this->limite;
			$grupos_inexactos = ($grupos-intval($grupos))*100;
			$grupos_exactos   = intval($grupos);
			if ($grupos_inexactos>0) 
				$grupos_exactos+=1;
			return $grupos_exactos;
		}
		
//_________________________________________________________________________________________		
		/*Pinta la paginacion, los iconos */
		function pintaPaginacion(){
			$html = '';
			if ($this->paginaActual() > $this->primeraPagina() )
				//include("primeraPagina.inc.html");
				$html.= $this->firstPage;
			if ($this->paginaActual() > $this->primeraPagina() )
				//include("anteriorPagina.inc.html");
				$html.= $this->prevPage;
			$html.= 'P&aacute;gina '.$this->paginaActual().' de '.$this->ultimaPagina().' ('.$this->totalRegistros().' registros)';
			if ($this->paginaActual() < $this->ultimaPagina() )
				//include("siguientePagina.inc.html");
				$html.= $this->nextPage;
			if ($this->paginaActual() < $this->ultimaPagina() )
				//include("ultimaPagina.inc.html");
				$html.= $this->lastPage;
			if ( $this->ultimaPagina()==0 )
				$html='No hay datos';
			echo $html;
		}
//_________________________________________________________________________________________		
		/*Pinta la paginacion, los iconos */
		function getPaginacionJSON(){
			/** la paginacion de la conexion se puede preparar al conocer la clase invocada */
			$static =  OS_WEB_PATH . '/applications/_commons/_images';
			$htmlFirst 		= "<a href='javascript:pagination(".$this->primeraPagina().")' title='Primera p&aacute;gina' ><IMG id='btFirst' SRC='$static/first.png'/></a>&nbsp;";
			$htmlPrevious 	= "<a href='javascript:pagination(".($this->paginaActual()-1).")' title='P&aacute;gina anterior' ><IMG id='btPrev'  SRC='$static/previous.png'></a>&nbsp;";
			$htmlNext 		= "<a href='javascript:pagination(".($this->paginaActual()+1).")' title='P&aacute;gina siguiente' ><IMG id='btNext' SRC='$static/next.png'/></a>&nbsp;";
			$htmlLast 		= "<a href='javascript:pagination(".$this->ultimaPagina().")' title='&Uacute;ltima p&aacute;gina' ><IMG id='btLast' SRC='$static/last.png'/></a>&nbsp;";
			$this->setHtmlPagination($htmlFirst, $htmlPrevious, $htmlNext, $htmlLast);
			
			$html = '';
			if ($this->paginaActual() > $this->primeraPagina() )
				//include("primeraPagina.inc.html");
				$html.= $this->firstPage;
			if ($this->paginaActual() > $this->primeraPagina() )
				//include("anteriorPagina.inc.html");
				$html.= $this->prevPage;
			$html.= 'P&aacute;gina '.$this->paginaActual().' de '.$this->ultimaPagina().' ('.$this->totalRegistros().' registros)';
			if ($this->paginaActual() < $this->ultimaPagina() )
				//include("siguientePagina.inc.html");
				$html.= $this->nextPage;
			if ($this->paginaActual() < $this->ultimaPagina() )
				//include("ultimaPagina.inc.html");
				$html.= $this->lastPage;
			if ( $this->ultimaPagina()==0 )
				$html='No hay datos';
			return $html;
		}
//_________________________________________________________________________________________		
		/*cierrar conexion persistente*/
		function closeConnection(){
			mysql_close($this->connection);
		}
//_________________________________________________________________________________________		
		/*mueve el puntero de resultado a la fila indicada*/
		function posicionateEnFila($datos,$fila){
			return mysql_data_seek($datos, $fila);
		}
//_________________________________________________________________________________________		
		/* Nos devuelve el último error detectado */
		function getError(){			
			return mysql_error($this->connection); //$this->error;
		}

		public function isConnected(){
			return ( $this->connection )?true:false;
		}
	}//fin class
?>