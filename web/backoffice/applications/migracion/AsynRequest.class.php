<?
class AsynRequest {
	
	public static $oLogger;
	
  	public static function call($host, $port, $path, $params) {
  		self::$oLogger = Logger::getRootLogger();
  		self::$oLogger->debug("Petion asincrona de migracion");
  		foreach ($params as $key => &$val) {
  			if (is_array($val)) $val = implode(',', $val);
  			$post_params[] = $key.'='.urlencode($val);
  		}
  		$post_string = implode('&', $post_params);
  		//$parts=parse_url($path);
  		$fp = fsockopen($host, $port, $errno, $errstr, 30);
  		$out = "POST ".$path." HTTP/1.1\r\n";
  		$out.= "Host: ".$host."\r\n";
  		$out.= "Content-Type: application/x-www-form-urlencoded\r\n";
  		$out.= "Content-Length: ".strlen($post_string)."\r\n";
  		$out.= "Connection: Close\r\n\r\n";
  		if (isset($post_string)) $out.= $post_string;
  		fwrite($fp, $out);
  		fclose($fp);
  	}
}
?>