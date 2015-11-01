<?
/**
 * Rutinas para recuperar el sistema y el browser del cliente y mas cosas
 * @author Antonio Gámez
 * @version 1.0 05.2014 creacion
 * @version 1.1 11.2014 añadimos mas funcionalidad
 *
 */
class ClientAgent extends Tienda_Instanciador{

	public static function remoteIP(){
		if ( OS_MODE=='development')
			return '88.0.155.8';//IP de madrid para pruebas
		else
			return $_SERVER[REMOTE_ADDR];
	}
	
	/**
	 * Acude al servicio web http://freegeoip.net/json/83.52.138.139 para determinar la localizacion del cliente
	 */
	public static function infoIpLocation(){
		$url = "http://www.telize.com/geoip/".self::remoteIP();
		$ctx = stream_context_create(array(
				'http' => array(
						'method' => 'GET',
						'timeout' => 5, //<---- Here (That is in seconds)
				)
		));
		$geo = file_get_contents($url,false,$ctx);
		parent::$log->debug($geo);
		return $geo;		
	}
	
	public static function remoteOS(){
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
	
	public static function remoteBrowser(){
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