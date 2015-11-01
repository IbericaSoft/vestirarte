<?
/**
 * Metodo que ejecutamos PRIMERO y SIEMPRE en todas las peticiones
 * @param Navigator $instance
 * @param Tienda $shop
 */
function _cache(Navigator $instance, Tienda $shop){
	if  ( $shop->email_host==null && OS_MODE=='development'){
		Navigator::$log->debug("Cacheando");
		$shop->email_alerts = explode(',', Utils_OS::getValueOS($instance->getConnection(), 'mail_alerts'));
		$shop->email_strategy= Utils_OS::getValueOS($instance->getConnection(), 'mail_strategy');
		$shop->email_host= Utils_OS::getValueOS($instance->getConnection(), 'mail_host');
		$shop->email_account= Utils_OS::getValueOS($instance->getConnection(), 'mail_account');
		$shop->email_user= Utils_OS::getValueOS($instance->getConnection(), 'mail_user');
		$shop->email_password= Utils_OS::getValueOS($instance->getConnection(), 'mail_password');
		$shop->email_alias= Utils_OS::getValueOS($instance->getConnection(), 'mail_alias');	
		$shop->notifications = explode(',', Utils_OS::getValueAPP($instance->getConnection(), 'EMAIL'));
		$shop->download_path = Utils_OS::getSystemAliasFolders( $instance->getConnection(), "ficheros", 2 );		
	}
	
	/** Todas las tiendas tienen estos valores */
	if ( strtolower(Utils_OS::getValueAPP($instance->getConnection(), 'STATUS'))=='off' )
		$instance->setAction('offline');
	
	if ( $_REQUEST[categoria] ){
		$shop->categoria_actual=($_REQUEST[categoria]);
		$shop->familia_actual=null;
		$_REQUEST[categoria]=$shop->categoria_actual;
	}
	
	if ( $_REQUEST[familia] ){		
		$shop->familia_actual=($_REQUEST[familia]);
		$shop->subfamilia_actual=null;
		$_REQUEST[familia]=$shop->familia_actual;
	}
	
	if ( $_REQUEST[subfamilia] ){
		$shop->subfamilia_actual=($_REQUEST[subfamilia]);
		$_REQUEST[subfamilia]=$shop->subfamilia_actual;
	}
	
	if ( $_REQUEST[articulo] ){
		$shop->articulo_actual=($_REQUEST[articulo]);
		$_REQUEST[articulo]=$shop->articulo_actual;
	}
	
	if ( $_REQUEST[pagina] ){
		$shop->pagina_actual=($_REQUEST[pagina]);
		$_REQUEST[articulo]=$shop->pagina_actual;
	}
	
	
	$instance->replaceAll("{web}", "/frontoffice");
	$instance->replaceAll("{titulo}", "VestirArte");
	
	if ( $instance->getAction()=="contactar" ){
		$instance::$log->debug("regenerar");
		$shop->regenerarCaptcha();
	}
}

/**
 * Tratamiento comun de pagina
 * */
function pagina(Navigator $instance, Tienda $shop){
}

/** cambiar el idioma del site */
function changeLang(Navigator $instance, Tienda $shop){
	$instance->setFolderLand( $_REQUEST['lang'] );
	return true;
}

/**
 * Detectamos si la petici�n viene de fuera del dominio... cosa que es un ataque
 * @param $msg opcional
 */
function requestIsValid(){
	$domain   = stripos($_SERVER['HTTP_REFERER'], HOST);
	$remoteIP = $_SERVER['REMOTE_ADDR'];
	$referer  = $_SERVER['HTTP_REFERER']; 
	
	if ( $referer!='' && $domain===false ){
		Navigator::addData("notice", $msg);
		$email = new Email( $shop->email_strategy,$shop->email_host, $shop->email_user, $shop->email_password, $shop->email_account, null);
		$log = "Desde la IP remota ($remoteIP) y desde la URL ($referer), se detecta una petici�n inv�lida. Detalle del mensaje a mostrar al usuario: $msg";
		foreach ( $shop->email_alerts as $correo )
			$email->enviar($correo, HOST, "Detectado petici�n inv�lida en ".HOST, $log, "");
		return false;
	}
	
	return true;
}

/**
 * Envio de email para auditar una situación que tenemos que mirar
 * @param Navigator $instance
 * @param Tienda $shop
 * @param String $mensaje
 * Apunte para el futuro. Todo va en UTF8, tando el mensaje(el fichero php!!! esta en utf8), el PhpMailer(envia en utf8), asi que no  hay
 * que convertir ningun charset, si no, salen cosas raras
 */
function auditoria(Navigator $instance, Tienda $shop, $mensaje){
	if ( $shop->html_email_auditoria == null )
		$shop->html_email_auditoria = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/frontoffice/esp/emails/email_auditoria.html');	
	foreach (getallheaders() as $name => $value) {
		$headers.="$name: $value\n";
	}
	$mensaje = '<strong>Detectada situación en '.HOST.' que requiere atención</strong>:<br>'.$mensaje.
	"<br><br><strong>Datos de usuario</strong>(si los hay):<br>Cliente: ".$shop->cliente->nombre."<br>Email: ".$shop->cliente->email.
	"<br><br><strong>Datos IP</strong>:<br>".$shop->portes->geo.
	"<br><br><strong>Analisis:</strong><br>$headers";
	//$html = (preg_replace('/\{MENSAJE\}/', utf8_encode($mensaje), $shop->html_email_auditoria));
	$html = (preg_replace('/\{MENSAJE\}/', utf8_decode($mensaje), $shop->html_email_auditoria));
	$html = (preg_replace('/\{HOST\}/',HOST,$html));
	foreach($shop->email_auditoria as $cuenta){
		$instance::$log->debug("Enviando email a $cuenta");
		$shop->email->enviar($cuenta, null, "Auditoria ".HOST, "$html", null);
		$instance::$log->error( $shop->email->getErrorEnvio() );
	}
}

/**
 * Envio de email informativo
 * @param Navigator $instance
 * @param Tienda $shop
 * @param String $mensaje
 * Apunte para el futuro. Todo va en UTF8, tando el mensaje(el fichero php!!! esta en utf8), el PhpMailer(envia en utf8), asi que no  hay
 * que convertir ningun charset, si no, salen cosas raras
 */
function informativo(Navigator $instance, Tienda $shop, $mensaje){
	if ( $shop->html_email_info==null )
		$shop->html_email_info = file_get_contents($_SERVER['DOCUMENT_ROOT'].'/frontoffice/esp/emails/email_informacion.html');
	$html = (preg_replace('/\{MENSAJE\}/', utf8_decode($mensaje), $shop->html_email_info));
	$html = (preg_replace('/\{HOST\}/',HOST,$html));
	foreach($shop->email_nofiticaciones as $cuenta)
		$shop->email->enviar($cuenta, null, utfd_server("Información ".HOST), "$html", null);
}

/* ==== Detect the OS ==== */
function detectDevice(){
	$ua = $_SERVER["HTTP_USER_AGENT"];
	$os = '';
	if (strpos($ua, 'Android')){
		$os = 'Android';
	} else if ( strpos($ua, 'BlackBerry')){
		$os = 'BlackBerry';
	} else if ( strpos($ua, 'iPhone') ) {
		$os = 'iPhone';
	} else if ( strpos($ua, 'PaTienda') ){
		$os = 'Palm';
	} else if ( strpos($ua, 'Linux') ){
		$os = 'Linux';
	} else if ( strpos($ua, 'Macintosh') ){
		$os = 'Macintosh';
	} else if ( strpos($ua, 'Windows') ) {
		$os = 'Windows';
	} else
		$os = 'Desconocido ('.$ua.')';	
	return $os;
}

function detectBrowser(){
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
		$bw = 'Desconocido ('.$ua.')';
	return $bw;
}

/**
 * Hace un subtring inteligente para no cortar palabras. Para ello busca desde la posici�n de recorte, la pr�xima posici�n de
 * caracteres como el punto, exclamaci�n, interrogaci�n o el tag de cierre de html para ampliar el recorte hasta esa posici�n detectada.
 * @param unknown $str
 * @param unknown $len
 * @return unknown|string
 */
function intelligentCrop($str, $len){
	if ( strlen($str) <= $len ) {
		return $str;
	}

	// find the longest possible match
	$pos = 0;
	//foreach ( array('/>','/> ','. ', '? ', '! ','; ') as $punct ) {
	foreach ( array('</','/>','. ',', ','</p>') as $punct ) {
		$npos = strpos($str, $punct);
		if ( $npos > $pos && $npos < $len ) {
			$pos = $npos-1;//como se coloca en una posicion que termina asi (bla bla bla</) recortamos un caracter para no coger el (<), por eso el -1
		}
	}

	if ( !$pos ) {
		// substr $len-3, because the ellipsis adds 3 chars
		return substr($str, 0, $len-3) . '...';
	}
	else {
		// $pos+1 to grab punctuation mark
		return substr($str, 0, $pos+1) . '...' ;
	}
}

function url_friendly($url){
	// Tranformamos todo a minusculas
	//$url = strtolower(utf8_decode($url));
	
	//Rememplazamos caracteres especiales latinos
	//$find = array('á', 'é', 'í', 'ó', 'ú','');
	//$repl = array('a', 'e', 'i', 'o', 'u','');
	$url = str_replace ($find, $repl, $url);
	
	// A�aadimos los guiones
	$find = array(' ', '&', '\r\n', '\n', '+');
	$url = str_replace ($find, ' ', $url);
	
	// Eliminamos y Reemplazamos dem�s caracteres especiales
	//$find = array('/[^a-z0-9\-<>]/', '/[\-]+/', '/<[^>]*>/');
	//$repl = array('', '-', '');
	//$url = preg_replace ($find, $repl, $url);
	
	return $url;
}

function utf_server($cadena){
	if ( OS_MODE=='development'||OS_MODE=="test")
		//return $cadena;
		return utf8_encode($cadena);
	return utf8_encode($cadena);
}

function utfd_server($cadena){
	if ( OS_MODE=='development'||OS_MODE=="test")
		return utf8_decode($cadena);
	return utf8_decode($cadena);
}
?>