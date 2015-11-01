<?
	define("HOST",$_SERVER['SERVER_NAME']);

	if (HOST=='local.vestirarte.com'){
		$env = 'desarrollo.cfg.php';
		$osPath = '/backoffice';
		$publicPath = '/frontoffice';
		$mode = 'development';
	}else if (HOST=='test.vestirarte.com'){
		$env = 'test.cfg.php';
		$osPath = '/backoffice';
		$publicPath = '/frontoffice';
		$mode = 'test';
	}else if (HOST=='www.vestirarte.com'||HOST=='vestirarte.com'){
		$env = 'production.cfg.php';
		$osPath = '/backoffice';
		$publicPath = '/frontoffice';
		$mode = 'production';
	}
		
	define("OS_MODE",$mode);
	define("ENVIRONMENT_FILE",$env);
	define("OS_WEB_PATH", $osPath);
	define("PUBLIC_WEB_PATH", $publicPath);
	define("PREFIX_URL", "$osPath/desktop");
	define("OS_ROOT", $_SERVER['DOCUMENT_ROOT'].$osPath );
?>