<?
	/**
	 * Utilidades del sistema OS
	 * @author Antonio Gamez agamez@dobleh.com
	 * @version 1.0 05-2011 creación aproximada
	 * @version 1.1 01-2012 nuevos metodos para trabajar con la tabla app_config
	 * @version 1.2 02-2012 getSystemAliasFolders nuevo metodo para indicar alias de carpeta y rol ya que getSystemFolders solo usa rol y no sirve en todos los casos
	 * @version 1.3 03.2012 updateValueAPProl reemplaza z updateValueAPP para actualizar con datos de auditoria en la tabla app_config
	 * @version 1.4 08.2012 Metodo getSystemId para obtener el ID de usuario del systema (que es propio) para operaciones en las que no participa un administrador, si no,
	 * 	un usuario de fuera o un proceso automático
	 * @version 1.5 02.2013 Metodos para recuperar el sistema operativo y client-agent del usuario/administrador
	 */
	abstract class Utils_OS  {
		
		const VERSION = '1.5';
		private static $connection;
		
		/**
		 * Este método nos permite conocer el path del servidor donde subir ficheros. El filtro es el alias de la carpeta y el rol.
		 * El alias identifica la finalidad de la carpeta, es decir, un alias=images, nos indica que es una carpeta para albergar imágenes.
		 * Si ademas el rol=1, estamos indicando que cualquiera puede subir ficheros ahí por que el rol 1 es el más bajo de todos
		 * @param Conexion $conn conexión a datos
		 * @param $alias identificación de carpeta
		 * @param $rol identificación de rol para la carpeta indicada
		 */
		public static function getSystemAliasFolders( Conexion $conn, $alias, $rol ){
			Utils_OS::$connection = $conn;
			$sql = "SELECT *,date_format(fecha,'%d-%m-%Y') fecha FROM os_folder WHERE id_rol=$rol AND alias='$alias';";
			$resultado = Utils_OS::$connection->query($sql);
			return Utils_OS::$connection->getColumnas($resultado);
		}
		
		public static function getSystemFolders( Conexion $conn, $rol ){
			Utils_OS::$connection = $conn;
			$sql = "SELECT *,date_format(fecha,'%d-%m-%Y') fecha FROM os_folder WHERE id_rol=$rol;";
			$resultado = Utils_OS::$connection->query($sql);
			$app = array();
			while ($datos=Utils_OS::$connection->getColumnas($resultado))
				array_push($app, $datos );
			return $app;
		}
		
		public static function getApplicationsUser(Conexion $conn, $rol){
			//$oLogger = Logger::getRootLogger();
			Utils_OS::$connection = $conn;
			$sql = 'SELECT app.*';
			$sql.= ' FROM os_applications app';
			$sql.= " WHERE app.rol<=$rol";
			$sql.= ' ORDER BY position;';
			//$oLogger->debug( $sql );
			$resultado = Utils_OS::$connection->query($sql);
			$app = array();
			while ($datos=Utils_OS::$connection->getColumnas($resultado))
				array_push($app, $datos );
			return $app;
		}
		
		public static function getInfoApplications(Conexion $conn, $app){
			$oLogger = Logger::getRootLogger();
			Utils_OS::$connection = $conn;
			$sql = 'SELECT app.version';
			$sql.= ' FROM os_applications app';
			$sql.= " WHERE app.application='$app';";
			$oLogger->debug( $sql );
			$resultado = Utils_OS::$connection->query($sql);
			$datos = Utils_OS::$connection->getColumnas($resultado);
			return $datos['version'];
		}
		
		public static function getIconsUser(Conexion $conn, $user){
			$oLogger = Logger::getRootLogger();
			Utils_OS::$connection = $conn;
			$sql = 'SELECT *';
			$sql.= ' FROM os_icons_user icons';
			$sql.= " WHERE icons.user_id=$user;";
			$oLogger->debug( $sql );
			$resultado = Utils_OS::$connection->query($sql);
			$app = array();
			while ($datos=Utils_OS::$connection->getColumnas($resultado))
				array_push($app, $datos );
			return $app;
		}
			
		public static function getProcessUser(Conexion $conn, $user){
			$oLogger = Logger::getRootLogger();
			Utils_OS::$connection = $conn;
			$sql = 'SELECT *';
			$sql.= ' FROM os_process_user proc';
			$sql.= " WHERE proc.user_id=$user;";
			$oLogger->debug( $sql );
			$resultado = Utils_OS::$connection->query($sql);
			$app = array();
			while ($datos=Utils_OS::$connection->getColumnas($resultado))
				array_push($app, $datos );
			return $app;
		}
		
		public static function getConfigSystem(Conexion $conn){
			Utils_OS::$connection = $conn;
			$sql = "SELECT * FROM os_config;";
			$resultado = Utils_OS::$connection->query($sql);
			$datos = array();
			while ( $data = Utils_OS::$connection->getColumnas($resultado) )
				$datos[$data['clave']] = $data['valor'];
			return $datos;
		}
		
		public static function getPreferencesUser(Conexion $conn, $user){
			$oLogger = Logger::getRootLogger();
			Utils_OS::$connection = $conn;
			$sql = "SELECT ";
			$sql.= "(SELECT theme.theme FROM os_themes theme WHERE theme.id=";
			$sql.= "(SELECT value FROM os_preferences_user users WHERE users.id_user=$user AND property='theme')) theme,";
			$sql.= "(SELECT wpp.wallpaper FROM os_wallpapers wpp WHERE wpp.id=";
			$sql.= "(SELECT value FROM os_preferences_user users WHERE users.id_user=$user AND property='wallpaper')) wallpaper;";
			$oLogger->debug( $sql );
			$resultado = Utils_OS::$connection->query($sql);
			return Utils_OS::$connection->getColumnas($resultado);			
		}
		
		public static function registrySession(Conexion $conn, $data){
			Utils_OS::$connection = $conn;
			$sql = 'SELECT * FROM os_sessions WHERE session="'.$data[session].'";';
			Utils_OS::$connection->query($sql);
			if ( Utils_OS::$connection->totalRegistros() ){
				while(list($key,$val)= each($data)){
					if ($sqlUpdate) 
						$sqlUpdate .= ',';
		   			$sqlUpdate .= "$key=\"$val\"";
				}
				$sql = "UPDATE os_sessions SET $sqlUpdate WHERE session='$data[session]'";
			}else{		
				while(list($key,$val)= each($data)){
					if ($sqlFields) {$sqlFields.=',';$sqlValues.=',';}
		   			$sqlFields .= "$key";
		   			$sqlValues .= "\"$val\"";
				}
				$sql = "INSERT INTO os_sessions ($sqlFields) VALUES ($sqlValues)";
			}
			Utils_OS::$connection->query($sql);
		}
		
		public static function getValueOS(Conexion $conn, $key){
			Utils_OS::$connection = $conn;
			$sql = "SELECT valor FROM os_config WHERE clave='$key';";
			$resultado = Utils_OS::$connection->query($sql);
			$datos = Utils_OS::$connection->getColumnas($resultado);
			return $datos['valor'];
		}
		
		public static function updateValueOS(Conexion $conn, $key, $value){
			Utils_OS::$connection = $conn;
			$sql = "UPDATE os_config SET valor='$value',fecha=now() WHERE clave='$key';";
			Utils_OS::$connection->query($sql);
		}
		
		public static function getWallPapers(Conexion $conn){
			$oLogger = Logger::getRootLogger();
			Utils_OS::$connection = $conn;
			$sql = 'SELECT *';
			$sql.= ' FROM os_wallpapers;';
			$resultado = Utils_OS::$connection->query($sql);
			$app = array();
			while ($datos=Utils_OS::$connection->getColumnas($resultado))
				array_push($app, $datos );
			return $app;
		}
		
		public static function getThemes(Conexion $conn){
			$oLogger = Logger::getRootLogger();
			Utils_OS::$connection = $conn;
			$sql = 'SELECT *';
			$sql.= ' FROM os_themes;';
			$resultado = Utils_OS::$connection->query($sql);
			$app = array();
			while ($datos=Utils_OS::$connection->getColumnas($resultado))
				array_push($app, $datos );
			return $app;
		}
		
		public static function getValueAPP(Conexion $conn, $key){
			Utils_OS::$connection = $conn;
			$sql = "SELECT valor FROM app_config WHERE clave='$key';";
			$resultado = Utils_OS::$connection->query($sql);
			$datos = Utils_OS::$connection->getColumnas($resultado);
			return $datos['valor'];
		}
			
		public static function updateValueAPProl(Conexion $conn, $key, $value, $admin){
			Utils_OS::$connection = $conn;
			$sql = "UPDATE app_config SET valor='$value',fmodificacion=now(),id_administrador=$admin WHERE clave='$key';";
			//$oLogger = Logger::getRootLogger();
			//$oLogger->debug( $sql );
			Utils_OS::$connection->query($sql);
		}
		
		public static function getSystemId(Conexion $conn){
			Utils_OS::$connection = $conn;
			$sql = "SELECT id FROM os_administradores WHERE id_perfil=8 LIMIT 1;";
			$resultado = Utils_OS::$connection->query($sql);
			$datos = Utils_OS::$connection->getColumnas($resultado);
			return $datos['id'];
		}
		
		public static function detectClientOS(){
			$ua = $_SERVER["HTTP_USER_AGENT"];
			$os = '';
			if (strpos($ua, 'Android')){
				$os = 'Android';
			} else if ( strpos($ua, 'BlackBerry')){
				$os = 'BlackBerry';
			} else if ( strpos($ua, 'iPhone') ) {
				$os = 'iPhone';
			} else if ( strpos($ua, 'Palm') ){
				$os = 'Palm';
			} else if ( strpos($ua, 'Linux') ){
				$os = 'Linux';
			} else if ( strpos($ua, 'Macintosh') ){
				$os = 'Macintosh';
			} else if ( strpos($ua, 'Windows') ) {
				$os = 'Windows';
			} else
				$os = 'Desconocido';
			return $os;
		}
		
		public static function detectClientBrowser(){
			$ua = $_SERVER["HTTP_USER_AGENT"];
			$bw = '';
		
			if ( strpos($ua, 'Chrome') ){
				$bw = 'Chrome';
			} else if ( strpos($ua, 'Firefox') ){
				$bw = 'Firefox';
			} else if ( strpos($ua, 'MSIE') ) {
				$bw = 'IExplorer';
			} else if ( preg_match("/\bOpera\b/i", $ua) ){
				$bw = 'Opera';
			} else if ( strpos($ua, 'Safari') ) {
				$bw = 'Safari';
			} else
				$bw = 'Desconocido';
			return $bw;
		}
	}
	
?>
