<?
	/**
	 * Almacen de datos del porte de la cesta
	 */
	class PortesZonas extends Tienda_Instanciador{
		public $listaPortesZona 	= null;//array de clase Porte con todos los peso/importe de la zona(pais-provincia) en la que estamos)				
		public $inicializado 		= false;
		public $geo					= null;
		public $id_zona 			= null;
		public $nombre_zona			= null;
		public $paises 				= null;
		public $provincias 			= null;
		public $iva_portes 			= 0;
		public $nombre_pais 		= 'España';//valor por defecto
		public $id_pais 			= 209;//valor por defecto
		public $nombre_provincia 	= 'Madrid';//valor por defecto
		public $id_provincia 		= 29;//valor por defecto
		public $direccion			= null;
		public $poblacion			= null;
		public $codigo_postal		= null;
		public $peso 				= null;
		public $importe 			= 0;
		public $dias_entrega_pedido = null;		
		public $fecha_entrega 		= null;
		public $sin_portes_desde_euros = 0;
		
		/**
		 * En el constructor de la clase es necesario cargar los paises/provincias xq si no, el usuario no se
		 * puede dar de alta
		 */
		public function __construct(){
			parent::$log->debug("Cargando tablas de paises, provincias y buscando por IP la zona del cliente");
			$this->cacheaPaises();
			$this->cacheaProvincias();
			$this->buscarZonaPortesIp();
			$this->inicializa(); //de perdidos al rio
		}
		
		/**
		 * El que exista este metodo se debe a que solo cuando el usuario empieza añadir productos en la cesta, inicializamos los
		 * datos de portes (iva, dias de entrega, zonas, geo localizacion. Hacerlo de inicio es un coste extra que puede resultar
		 * innecesario
		 */
		private function inicializa(){
			$this->inicializado = true;
			$this->sin_portes_desde_euros = Utils_OS::getValueAPP(Navigator::$connection, 'SIN_PORTES');
			$this->iva_portes = Utils_OS::getValueAPP(Navigator::$connection, 'IVA_PORTES');
			$this->dias_entrega_pedido = Utils_OS::getValueAPP(Navigator::$connection, 'DIAS_ENTREGA_PEDIDO');
			$this->cargarPortesZona();
		}		
		
		/**
		 * Cachea los paises posibles de los portes
		 */
		private function cacheaPaises(){
			$resultado = Navigator::$connection->query("SELECT id,long_name pais FROM pais WHERE estado IN ('ON') ORDER BY pais;");
			$datos = array();
			while ( $rows = Navigator::$connection->getColumnas($resultado) )
				array_push( $datos, array("id"=>$rows['id'],"pais"=>utf8_encode($rows['pais'])) );
			$this->paises = $datos;
		}
		
		/**
		 * Cachea las provincias/estados/zonas posibles de los portes
		 */
		private function cacheaProvincias(){
			$resultado = Navigator::$connection->query("SELECT * FROM provincias ORDER BY id_pais, provincia;");
			$datos = array();
			while ( $rows = Navigator::$connection->getColumnas($resultado) )
				array_push( $datos, array("id"=>$rows['id'],"id_pais"=>$rows['id_pais'],"provincia"=>utf8_encode($rows['provincia'])) );
			$this->provincias = $datos;
		}

		/**
		 * Con la IP del cliente es posible que sepamos en que zona de portes vamos a trabajar
		 */
		private function buscarZonaPortesIp(){
			$this->geo = trim(ClientAgent::infoIpLocation());//json con la info
			$json = json_decode($this->geo);
			$conn = Navigator::$connection;
			if ( $json->country_code3 ){
				$resultado = $conn->query("SELECT * FROM pais WHERE iso3='$json->country_code3';");
				$datos = $conn->getColumnas($resultado);
				$this->nombre_pais = $datos[long_name];
				$this->id_pais = $datos[id];
			}
			
			if ( $json->postal_code && $this->id_pais==209 ){ //solo España
				$cp = substr($json->postal_code,0,2); //nos vienen 5 numeros, solo queremos los dos primeros
				$resultado = $conn->query("SELECT * FROM provincias WHERE id_pais=$this->id_pais AND cpostal=$cp;");
				$datos = $conn->getColumnas($resultado);
				$this->nombre_provincia = $datos['provincia'];
				$this->id_provincia = $datos['id'];
			}
			
			if ( $this->id_pais==6 ){ //solo Andorra
				$resultado = $conn->query("SELECT * FROM provincias WHERE id_pais=$this->id_pais AND provincia='andorra';");
				$datos = $conn->getColumnas($resultado);
				$this->nombre_provincia = $datos['provincia'];
				$this->id_provincia = $datos['id'];
			}
			
			if ( $this->id_pais==177 ){ //solo Portugal
				$resultado = $conn->query("SELECT * FROM provincias WHERE id_pais=$this->id_pais AND provincia='portugal';");
				$datos = $conn->getColumnas($resultado);
				$this->nombre_provincia = $datos['provincia'];
				$this->id_provincia = $datos['id'];
			}
		}
		
		/**
		 * Cargamos todos los importes posibles de la zona indicada (la provincia nos dice en que zona estamos)
		 */
		public function cargarPortesZona(){
			$conn = Navigator::$connection;
			//detalles de la zona
			$resultado = $conn->query("SELECT zon.* FROM zonas zon LEFT JOIN zonas_detalle det ON (zon.id=det.id_zona) WHERE zon.estado IN('ON') AND det.id_provincia=$this->id_provincia;");
			$datos = $conn->getColumnas($resultado);
			$this->id_zona = $datos['id'];
			$this->nombre_zona = $datos[zona];
			
			//importes posibles para esta zona
			$resultado = $conn->query("SELECT * FROM portes WHERE id_zona=$this->id_zona ORDER BY peso;");
			$this->listaPortesZona = array();
			while ( $rows = $conn->getColumnas($resultado) ){
				parent::$log->debug("cargando portes $rows[peso]kg, importe $rows[importe]euros");
				array_push( $this->listaPortesZona, new BeanPorte($rows[id],$this->id_pais,$this->id_zona,$rows[peso],$rows[importe]));
			}
		}
		
		/** 
		 * Recibe el peso y lo busca en el array de valores peso/importe que tenemos en cache
		 * Si el sistema de portes no esta inicializado, es el momento! 
		 */
		public function calculaPorte($peso){
			if ( !$this->inicializado )
				$this->inicializa();
			
			foreach ($this->listaPortesZona as $porte){
				if ( $porte->peso >= $peso ){
					parent::$log->debug("La cesta pesa ($peso)kg y su importe es ($porte->importe), encontrado en el rango de hasta ($porte->peso) kilos");
					$this->peso = $peso;
					$this->importe = $porte->importe;
					return $this->importe;
				}
			}
		}
		
		/**
		 * ELimina los portes, por el motivo que sea
		 */
		public function eliminarPortes(){
			$this->importe = 0;
			parent::$log->debug("El importe para este porte es 0 debido a que no tiene cargo en los portes");
			return $this->importe;
		}
		
		/** calcula el valor del porte con iva */
		public function porte_con_iva(){
			$total = round((($this->iva_portes/100)+1)*$this->importe,2);
			return $total;
		}
		
		/** con formato */
		public function porte_con_iva_formato(){
			$importe = $this->porte_con_iva();
			$formatter = new NumberFormatter('de_DE', NumberFormatter::CURRENCY);
			return $formatter->formatCurrency($importe, 'EUR');
		}
		
		/** calcula el valor del porte SIN iva */
		public function porte_sin_iva(){
			return $this->importe;
		}
		
		/** calcula los impuestos del porte... solo impuestos */
		public function impuestos(){
			$total = $this->porte_con_iva()-$this->porte_sin_iva();
			return $total;
		}
		
		/** Cual es el iva de los portes... es un valor que se da por configuracion en la tabla app_conf */
		public function tipo_iva_portes(){
			return $this->iva_portes;
		}
		
		/** tipo de iva en los portes pero con formato */
		public function tipo_iva_cesta_formato(){
			$iva = $this->tipo_iva_cesta();
			return number_format($iva,2,",",".").'%';
		}
		
		public function fecha_entrega_condicionada($listArticulos){
			$entrega = $this->dias_entrega_pedido;
			foreach ($listArticulos as $articulo){
				if ( $articulo->stock!=0 ){
					$entrega = 0;
					break;
				}
			}
				
			setlocale(LC_TIME, 'es_ES');
			if ( $entrega==0 )
				return "La fecha de entrega se le comunicará via mail/teléfono";
			else
				return strftime('%d de %B de %Y',$this->fecha_entrega());
		}
		
		/**
		 * Desde la fecha de hoy, calcula la fecha de entrega del pedido
		 * En $tiempo tenemos que indicar los días que tardamos en entrega el pedido, según nuestro comercio
		 */
		private function fecha_entrega(){
			$tiempo = $this->dias_entrega_pedido;
			parent::$log->debug($tiempo);
			//if ( $this->fecha_entrega==null ){
			list($dia,$mes,$anio,$hora,$minuto) = explode("/",date("d/m/Y/H/i"));
			$diaHabil = null;
			$usado = false;
			while ( true ){
				$diaHabil = mktime($hora,$minuto,0,$mes,$dia+$tiempo,$anio);
				if ( date("w",$diaHabil)>5||date("w",$diaHabil)==0 ) //es fin de semana
					$tiempo++;
				elseif( date("H",$diaHabil)>=9 && !$usado ){ //hora limite de pedido entre semana
					$tiempo++;$usado=true;
				}else{
					$this->fecha_entrega = mktime($hora,$minuto,0,$mes,$dia+$tiempo,$anio);
					return $this->fecha_entrega;
				}
			}
			//}
		}
		
		/** Formato largo de fecha: dia mes de año */
		public function fecha_entrega_formateada(){
			setlocale(LC_TIME, 'es_ES');
			if ( $this->fecha_entrega==null )
				$this->fecha_entrega();
			return strftime('%d de %B de %Y',$this->fecha_entrega);
		}
		
		/**
		 * Guarda los datos de usuario relativos a los portes
		 * @param $array suele ser un _POST de un formulario de cliente
		 */
		public function mapearDatos($array){			
			if ( !$this->inicializado )
				$this->inicializa();
			Navigator::$log->debug($array);
			$this->direccion = $array[direccion];
			$this->poblacion = $array[poblacion];
			$this->codigo_postal = $array[cpostal];
			$this->id_pais = ($array[pais])?$array[pais]:$array[id_pais];
			foreach ($this->paises as $item)
				if ($item[id]==$this->id_pais)
					$this->nombre_pais = $item[pais];
			$this->id_provincia = ($array[provincia])?$array[provincia]:$array[id_provincia];
			foreach ($this->provincias as $item)
				if ($item[id]==$this->id_provincia)
					$this->nombre_provincia = $item[provincia];
			$this->cargarPortesZona();
			if ( $this->peso!=null )
				$this->calculaPorte($this->peso);
		}
	}
?>
