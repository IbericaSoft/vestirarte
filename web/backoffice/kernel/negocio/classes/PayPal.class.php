<?
/**
 * Pago con PayPal
 * 1-Crear una cuenta PayPal tipo Bussiness. Apuntar los datos de acceso. Activar IPN e indicar una URL para que PayPal nos llame y ejecutemos el metodo getPayPalResponse().
 * 2-Instanciar y llamar a addItemsDetails y beginTransaction con los parametros necesarios (ver metodos). Esto nos devuelve un token.
 * 3-Con estos datos se puede navegar a la pagina de pago de PayPal. PayPal nos volvera a dejar en nuestra web.
 * 4-PayPal nos llamara la IPN indicado cuando acepte la operacion de pago y al termina nos deja devuelta en la web. En ese momento nosotros ya sabemos ha ido todo.
 * 5-Tenemos que cerrar la operacion desde nuestro lado para paypal y para asegurarnos que no han manipulado datos. Llamar a confirm con los parametros necesarios.
 * @author ComercioSoft
 *
 */
class PayPal {
	
	public $_PAYPAL_ENDPOINT;
	public $_PAYPAL_URLFORM;
	private $_user;
	private $_pass;
	private $_sign;
	private $_requestDetails=null;
	
	/**
	 * 
	 * @param PayPal_Environments $endpoint
	 * @param unknown $user
	 * @param unknown $password
	 * @param unknown $signature
	 */
	public function __construct($endpoint, $user, $password, $signature){
		$this->_PAYPAL_ENDPOINT = ($endpoint=='DEVELOPER')?PayPal_EndPoins::DEVELOPER:PayPal_EndPoins::LIVE;
		$this->_PAYPAL_URLFORM = ($endpoint=='DEVELOPER')?PayPal_UrlForms::DEVELOPER:PayPal_UrlForms::LIVE;
		$this->_user = $user;
		$this->_pass = $password;
		$this->_sign = $signature;
	}
	
	/**
	 * Para que el usuario vea en la lista de PayPal los articulos que va a pagar, tenemos que pasarlos primero nosotros.
	 * Es opcional.
	 * @param unknown $description
	 * @param unknown $price
	 * @param unknown $quantity
	 * @param unknown $reference
	 */
	public function addItemsDetails($description,$price,$quantity,$reference){
		if ( $this->_requestDetails==null )
			$this->_requestDetails = array();
		$nItem = count($this->_requestDetails);
		array_push($this->_requestDetails, array("L_PAYMENTREQUEST_0_NAME".$nItem=>$description,"L_PAYMENTREQUEST_0_AMT".$nItem=>$price,"L_PAYMENTREQUEST_0_QTY".$nItem=>$quantity,"L_PAYMENTREQUEST_0_NUMBER".$nItem=>$reference ));
	}
	
	/**
	 * Devuelve el TOKEN de operacion con la que realizar un pago
	 * @param unknown $amount
	 * @param unknown $currency
	 */
	public function beginTransaction($amount,$currency,$returnURL,$cancelURL,$ipn){
		$request = 
			"&USER=".urlencode($this->_user).
			"&PWD=".urlencode($this->_pass).			
			"&SIGNATURE=".urlencode($this->_sign).
			"&METHOD=".urlencode(PayPal_Methods::BEGIN).
			"&VERSION=".urlencode(PayPal_Version::VERSION).
			"&PAYMENTREQUEST_0_PAYMENTACTION=Sale".
			"&PAYMENTREQUEST_0_AMT=".urlencode((number_format($amount,2))).
			"&PAYMENTREQUEST_0_CURRENCYCODE=".urlencode($currency).
			"&RETURNURL=".urlencode($returnURL).
			"&CANCELURL=".urlencode($cancelURL).
			"&PAYMENTREQUEST_0_NOTIFYURL=".urlencode($ipn);
			//"&LOCALECODE=ES";
		foreach ( $this->_requestDetails as $items )
			foreach ( array_keys($items) as $item )
				$request.="&$item=".urlencode(utf8_encode($items[$item]));
		
		$textplain = $this->execute($request);
		if (!$textplain)
			throw new Exception(PayPal_Methods::BEGIN." failed: ".curl_error($this->_CURL));
		
		$response = array(); /** la respuesta la pasamos a un array para buscar por valores */
		foreach (explode("&", $textplain) as $value) {
			$tmpAr = explode("=", $value);
			if(sizeof($tmpAr) > 1)
				$response[$tmpAr[0]] = urldecode($tmpAr[1]);
		}
		
		$fp = fopen('logs/setExpressCheckout.txt', 'w');
		foreach ($response as $key=>$value)
			fwrite($fp, "$key=$value\n\r");
		fclose($fp);
		
		if ( strtoupper($response['ACK'])=="SUCCESS" || strtoupper($response['ACK'])=="SUCCESSWITHWARNING" )
			return $response['TOKEN'];
		else
			throw new Exception("Error: ACK=".$response['ACK']);
	}
	
	/**
	 * 
	 * @param unknown $token
	 */
	public function confirmTransaction($amount,$currency,$token,$payer,$ipn){
		$request =
		"&USER=".urlencode($this->_user).
		"&PWD=".urlencode($this->_pass).
		"&SIGNATURE=".urlencode($this->_sign).
		"&METHOD=".urlencode(PayPal_Methods::CONFIRM).
		"&VERSION=".urlencode(PayPal_Version::VERSION).
		"&PAYMENTREQUEST_0_PAYMENTACTION=Sale".
		"&PAYMENTREQUEST_0_AMT=".urlencode((number_format($amount,2))).
		"&PAYMENTREQUEST_0_CURRENCYCODE=".urlencode($currency).
		"&PAYERID=".urlencode($payer).
		"&TOKEN=".urlencode($token).
		"&PAYMENTREQUEST_0_NOTIFYURL=".urlencode($ipn);
		
		//echo $request;exit;
		$textplain = $this->execute($request);
		echo $textplain;exit;
		if (!$textplain)
			throw new Exception(PayPal_Methods::CONFIRM." failed: ".curl_error($this->_CURL));
		
		$response = array(); /** la respuesta la pasamos a un array para buscar por valores */
		foreach (explode("&", $textplain) as $value) {
			$tmpAr = explode("=", $value);
			if(sizeof($tmpAr) > 1)
				$response[$tmpAr[0]] = urldecode($tmpAr[1]);
		}
		
		$fp = fopen('DoExpressCheckoutPayment.txt', 'w');
		foreach ($response as $key=>$value)
			fwrite($fp, "$key=$value\n\r");
		fclose($fp);
		
		if ( strtoupper($response['ACK'])=="SUCCESS" || strtoupper($response['ACK'])=="SUCCESSWITHWARNING" )
			return $response;
		else
			throw new Exception("Algo salido mal. PayPal nos dice ACK=$response[ACK]&PAYMENTINFO_0_ERRORCODE=$_REQUEST[PAYMENTINFO_0_ERRORCODE]");
	}
	
	/**
	 * Respues de PayPal. Nos llaman ellos. Tratamos la respuesta aqui
	 */
	public function getPayPalResponse(){
		
	}
	
	/**
	 * Ejecuta la operacion contra el endpoint
	 * @param unknown $request
	 * @return mixed
	 */
	private function execute($request){
		$_CURL = curl_init();
		curl_setopt($_CURL, CURLOPT_URL, $this->_PAYPAL_ENDPOINT);
		curl_setopt($_CURL, CURLOPT_VERBOSE, 1);
		curl_setopt($_CURL, CURLOPT_SSL_VERIFYPEER, FALSE);
		curl_setopt($_CURL, CURLOPT_SSL_VERIFYHOST, FALSE);
		curl_setopt($_CURL, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($_CURL, CURLOPT_POST, true);
		curl_setopt($_CURL, CURLOPT_POSTFIELDS, $request);
		$data = curl_exec($_CURL);
		curl_close($_CURL);
		return $data;
	}
}

class PayPal_EndPoins {
	const DEVELOPER = 'https://api-3t.sandbox.paypal.com/nvp';
	const LIVE = 'https://api-3t.paypal.com/nvp';
}

class PayPal_UrlForms {
	const DEVELOPER = 'https://www.sandbox.paypal.com/webscr';
	const LIVE = 'https://www.paypal.com/webscr';
}

class PayPal_Version {
	const VERSION = '62.0';
}

class PayPal_Methods {
	const BEGIN   = 'SetExpressCheckout';
	const DETAILS = '';
	const CONFIRM = 'DoExpressCheckoutPayment';
}
?>