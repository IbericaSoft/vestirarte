<?
	/** Si es necesario capturar algun error para depurar el sistema, descomentar esta linea */
	error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_STRICT & ~E_DEPRECATED);
	
	require_once ( 'DobleOS.class.php' );
	$oDobleOS = DobleOS::getInstance();
	try {
		$oDobleOS->processRequest();
	} catch (Exception $e){
		//nos hemos dado un castañazo!!!
		$oDobleOS->getLogger()->error( $e->getTrace() );
	}
?>