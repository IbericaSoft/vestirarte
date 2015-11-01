<?
/**
 * Pago con Sermepa
 * @author Antonio Gámez
 * @version 1.0 05.2013 creacion
 * @version 2.0 10.2014 mejoras varias. intentos
 * @version 2.1 11.2014 version que inicializa la clase cuando se necesita, no antes
 */
class Sermepa {
	
	public $inicializado = false;
	public $_TPV_ENDPOINT;
	public $_FUC;
	public $_TERMINAL;
	public $_CURRENCY;
	public $_TRANTYPE;
	public $_KEY_COMMERCE;
	public $_URL_NOTIFY;
	public $_URL_RETURN;
	public $_TOTAL;
	public $_ORDER;
	public $_SIGNATURE = null;
	public $intentos;
	public $_MAXATTEMPT = 5;
	public $ERR_INTENTOS = "";
	
	/**
	 * Construye el objeto TPV
	 */
	public function __construct(){
	}
	
	private function resetSermepa(){
		$this->_TPV_ENDPOINT= Utils_OS::getValueAPP(Navigator::$connection, 'SERMEPA_ENDPOINT');
		$this->_FUC 		= Utils_OS::getValueAPP(Navigator::$connection, 'SERMEPA_FUC');
		$this->_TERMINAL 	= Utils_OS::getValueAPP(Navigator::$connection, 'SERMEPA_TERMINAL');
		$this->_CURRENCY 	= Utils_OS::getValueAPP(Navigator::$connection, 'SERMEPA_CURRENCY');
		$this->_TRANTYPE 	= Utils_OS::getValueAPP(Navigator::$connection, 'SERMEPA_TRANTYPE');
		$this->_KEY_COMMERCE= Utils_OS::getValueAPP(Navigator::$connection, 'SERMEPA_SIGNATURE');
		$this->_URL_NOTIFY 	= Utils_OS::getValueAPP(Navigator::$connection, 'SERMEPA_ENDPOINT_NOTIFY');
		$this->_URL_RETURN 	= Utils_OS::getValueAPP(Navigator::$connection, 'SERMEPA_RETURN');
		$this->inicializado = true;
	}
	
	/**
	 * Recibe el html del formulario y reemplaza los valores que este necesita. El formulario es una plantilla que ya tenemos
	 * @param unknown $html
	 * @return mixed
	 */
	public function htmlForm($amount,$order){
		if ( !$this->inicializado )
			$this->resetSermepa();

		//if ( $this->intentos > $this->_MAXATTEMPT )
		//	throw new Exception( utf8_encode("Atención, lleva demasiados intentos de compra y no es posible continuar") ); 
		
		$html = "<form id=\"formulario_tpv\" action=\"{END_POINT}\" method=\"POST\" >
		<input type=hidden name=\"Ds_Merchant_Amount\" 			value=\"{AMOUNT}\" />
		<input type=hidden name=\"Ds_Merchant_PayMethods\" 			value=\"C\" />		
		<input type=hidden name=\"Ds_Merchant_Currency\" 			value=\"{CURRENCY}\" />
		<input type=hidden name=\"Ds_Merchant_Order\"  			value=\"{ORDER}\" />
		<input type=hidden name=\"Ds_Merchant_MerchantCode\" 		value=\"{FUC}\" />
		<input type=hidden name=\"Ds_Merchant_Terminal\" 			value=\"{TERMINAL}\" />
		<input type=hidden name=\"Ds_Merchant_TransactionType\" 	value=\"{TYPE}\" />	
		<input type=hidden name=\"Ds_Merchant_MerchantSignature\" value=\"{SIGNATURE}\" />
		<input type=hidden name=\"Ds_Merchant_ProductDescription\" value=\"--\" />
		<input type=hidden name=\"Ds_Merchant_MerchantURL\" 		value=\"{NOTIFY}\" />
		<input type=hidden name=\"Ds_Merchant_UrlOK\" 			value=\"{RETURN}\" />
		<input type=hidden name=\"Ds_Merchant_UrlKO\" 			value=\"{RETURN}\" />
		</form>";
		Navigator::$log->debug($amount);
		$this->_TOTAL = $amount*100;//esto le quita los decimales
		Navigator::$log->debug($this->_TOTAL);
		$this->_ORDER =  ($order==null)?time():$order;//generamos un order aleatorio o nos lo pasan
		$message = $this->_TOTAL.$this->_ORDER.$this->_FUC.$this->_CURRENCY.$this->_TRANTYPE.$this->_URL_NOTIFY.$this->_KEY_COMMERCE;
		$this->_SIGNATURE = strtoupper(sha1($message));		
		$html = preg_replace("/\{AMOUNT\}/", $this->_TOTAL, $html);
		$html = preg_replace("/\{FUC\}/", $this->_FUC, $html);
		$html = preg_replace("/\{TERMINAL\}/", $this->_TERMINAL, $html);
		$html = preg_replace("/\{CURRENCY\}/", $this->_CURRENCY, $html);
		$html = preg_replace("/\{SIGNATURE\}/", $this->_SIGNATURE, $html);
		$html = preg_replace("/\{TYPE\}/", $this->_TRANTYPE, $html);
		$html = preg_replace("/\{ORDER\}/", $this->_ORDER, $html);
		$html = preg_replace("/\{END_POINT\}/", $this->_TPV_ENDPOINT, $html);
		$html = preg_replace("/\{NOTIFY\}/", $this->_URL_NOTIFY, $html);
		$html = preg_replace("/\{RETURN\}/", $this->_URL_RETURN, $html);
		$html = preg_replace("/\{RETURN\}/", $this->_URL_RETURN, $html);
		$this->intentos++;
		return $html;
	}
	
	/**
	 * Valida que los datos recibidos desde la entidad son autenticos y de serlo devuelve true, ademas
	 * reinicia el contador de intentos
	 */
	public function isOK(){
		if ( !$this->inicializado )
			$this->resetSermepa();
		$this->_SIGNATURE = strtoupper(sha1($_POST[Ds_Amount].$_POST[Ds_Order].$_POST[Ds_MerchantCode].$_POST[Ds_Currency].$_POST[Ds_Response].$this->_KEY_COMMERCE));
		if ( $_POST[Ds_Signature]==$this->_SIGNATURE ){
			$this->intentos = 0;
			return true;
		}
		return false;
	}
	
	/**
	 * Monta y devuelve la response que nos da la entidad
	 * @return string
	 */
	public function report(){
		$report='';
		foreach (array_keys($_POST) as $dato)
			$report.= "$dato=$_POST[$dato];";
		return $report;
	}
	
	public function getResultCode(){
		return $_POST[Ds_Response];
	}
	
	public function getPostOrder(){
		return $_POST[Ds_Order];
	}
	
	/**
	 * Recupera todos los datos de la response de la entidad y los persiste en fichero
	 * @return string
	 */
	public function persist_report($file){
		file_put_contents($file, $this->report(), FILE_APPEND);
		return true;
	}
	
	public function err($code){
		$errs = "SIS0007 Error al desmontar el XML de entrada MSG0008
			SIS0008 Error falta Ds_Merchant_MerchantCode MSG0008
			SIS0009 Error de formato en Ds_Merchant_MerchantCode MSG0008
			SIS0010 Error falta Ds_Merchant_Terminal MSG0008
			SIS0011 Error de formato en Ds_Merchant_Terminal MSG0008
			SIS0014 Error de formato en Ds_Merchant_Order MSG0008
			SIS0015 Error falta Ds_Merchant_Currency MSG0008
			SIS0016 Error de formato en Ds_Merchant_Currency MSG0008
			SIS0017 Error no se admiten operaciones en pesetas MSG0008
			SIS0018 Error falta Ds_Merchant_Amount MSG0008
			SIS0019 Error de formato en Ds_Merchant_Amount MSG0008
			SIS0020 Error falta Ds_Merchant_MerchantSignature MSG0008
			SIS0021 Error la Ds_Merchant_MerchantSignature viene vacï¿½a MSG0008
			SIS0022 Error de formato en Ds_Merchant_TransactionType MSG0008
			SIS0023 Error Ds_Merchant_TransactionType desconocido MSG0008
			SIS0024 Error Ds_Merchant_ConsumerLanguage tiene mas de 3 posiciones MSG0008
			SIS0025 Error de formato en Ds_Merchant_ConsumerLanguage MSG0008
			SIS0026 Error No existe el comercio / terminal enviado MSG0008
			SIS0027 Error Moneda enviada por el comercio es diferente a la que tiene asignada para ese terminal MSG0008
			SIS0028 Error Comercio / terminal estï¿½ dado de baja MSG0008
			SIS0030 Error en un pago con tarjeta ha llegado un tipo de operaciï¿½n que no es ni pago ni preautorizaciï¿½n MSG0000
			SIS0031 Mï¿½todo de pago no definido MSG0000
			SIS0033 Error en un pago con mï¿½vil ha llegado un tipo de operaciï¿½n que no es ni pago ni preautorizaciï¿½n MSG0000
			SIS0034 Error de acceso a Base de Datos MSG0000
			SIS0037 El nï¿½mero de telï¿½fono no es vï¿½lido MSG0000
			SIS0038 Error en java MSG0000
			SIS0040 Error el comercio / terminal no tiene ningï¿½n mï¿½todo de pago asignado MSG0008
			SIS0041 Error en el cï¿½lculo de la HASH de datos del comercio. MSG0008
			SIS0042 La firma enviada no es correcta MSG0008
			SIS0043 Error al realizar la notificaciï¿½n online MSG0008
			SIS0046 El bin de la tarjeta no estï¿½ dado de alta MSG0002
			SIS0051 Error nï¿½mero de pedido repetido MSG0001
			SIS0054 Error no existe operaciï¿½n sobre la que realizar la devoluciï¿½n MSG0008
			SIS0055 Error existe mï¿½s de un pago con el mismo nï¿½mero de pedido MSG0008
			SIS0056 La operaciï¿½n sobre la que se desea devolver no estï¿½ autorizada MSG0008
			SIS0057 El importe a devolver supera el permitido MSG0008
			SIS0058 Inconsistencia de datos, en la validaciï¿½n de una confirmaciï¿½n MSG0008
			SIS0059 Error no existe operaciï¿½n sobre la que realizar la confirmaciï¿½n MSG0008
			SIS0060 Ya existe una confirmaciï¿½n asociada a la preautorizaciï¿½n MSG0008
			SIS0061 La preautorizaciï¿½n sobre la que se desea confirmar no estï¿½ autorizada MSG0008
			SIS0062 El importe a confirmar supera el permitido MSG0008
			SIS0063 Error. Nï¿½mero de tarjeta no disponible MSG0008
			SIS0064 Error. El nï¿½mero de tarjeta no puede tener mï¿½s de 19 posiciones MSG0008
			SIS0065 Error. El nï¿½mero de tarjeta no es numï¿½rico MSG0008
			SIS0066 Error. Mes de caducidad no disponible MSG0008
			SIS0067 Error. El mes de la caducidad no es numï¿½rico MSG0008
			SIS0068 Error. El mes de la caducidad no es vï¿½lido MSG0008
			SIS0069 Error. Aï¿½o de caducidad no disponible MSG0008
			SIS0070 Error. El Aï¿½o de la caducidad no es numï¿½rico MSG0008
			SIS0071 Tarjeta caducada MSG0000
			SIS0072 Operaciï¿½n no anulable MSG0000
			SIS0074 Error falta Ds_Merchant_Order MSG0008
			SIS0075 Error el Ds_Merchant_Order tiene menos de 4 posiciones o mï¿½s de 12 MSG0008
			SIS0076 Error el Ds_Merchant_Order no tiene las cuatro primeras posiciones numï¿½ricas MSG0008
			SIS0077 Error el Ds_Merchant_Order no tiene las cuatro primeras posiciones numï¿½ricas. No se utiliza MSG0000
			SIS0078 Mï¿½todo de pago no disponible MSG0005
			SIS0081 La sesiï¿½n es nueva, se han perdido los datos almacenados MSG0007
			SIS0084 El valor de Ds_Merchant_Conciliation es nulo MSG0008
			SIS0085 El valor de Ds_Merchant_Conciliation no es numï¿½rico MSG0008
			SIS0086 El valor de Ds_Merchant_Conciliation no ocupa 6 posiciones MSG0008
			SIS0089 El valor de Ds_Merchant_ExpiryDate no ocupa 4 posiciones MSG0008
			SIS0092 El valor de Ds_Merchant_ExpiryDate es nulo MSG0008
			SIS0093 Tarjeta no encontrada en la tabla de rangos MSG0006
			SIS0094 La tarjeta no fue autenticada como 3D Secure MSG0004
			SIS0097 Valor del campo Ds_Merchant_CComercio no vï¿½lido MSG0008
			SIS0098 Valor del campo Ds_Merchant_CVentana no vï¿½lido MSG0008
			SIS0112 Error El tipo de transacciï¿½n especificado en Ds_Merchant_Transaction_Type no esta permitido MSG0008
			SIS0113 Excepciï¿½n producida en el servlet de operaciones MSG0008
			SIS0114 Error, se ha llamado con un GET en lugar de un POST MSG0000
			SIS0115 Error no existe operaciï¿½n sobre la que realizar el pago de la cuota MSG0008
			SIS0116 La operaciï¿½n sobre la que se desea pagar una cuota no es una operaciï¿½n vï¿½lida MSG0008
			SIS0117 La operaciï¿½n sobre la que se desea pagar una cuota no estï¿½ autorizada MSG0008
			SIS0118 Se ha excedido el importe total de las cuotas MSG0008
			SIS0119 Valor del campo Ds_Merchant_DateFrecuency no vï¿½lido MSG0008
			SIS0120 Valor del campo Ds_Merchant_ChargeExpiryDate no vï¿½lido MSG0008
			SIS0121 Valor del campo Ds_Merchant_SumTotal no vï¿½lido MSG0008
			SIS0122 Valor del campo Ds_Merchant_DateFrecuency o no Ds_Merchant_SumTotal tiene formato incorrecto MSG0008
			SIS0123 Se ha excedido la fecha tope para realizar transacciones MSG0008
			SIS0124 No ha transcurrido la frecuencia mï¿½nima en un pago recurrente sucesivo MSG0008
			SIS0132 La fecha de Confirmaciï¿½n de Autorizaciï¿½n no puede superar en mas de 7 dï¿½as a la de Preautorizaciï¿½n. MSG0008
			SIS0133 La fecha de Confirmaciï¿½n de Autenticaciï¿½n no puede superar en mas de 45 dï¿½as a la de Autenticaciï¿½n Previa. MSG0008
			SIS0139 Error el pago recurrente inicial estï¿½ duplicado MSG0008
			SIS0142 Tiempo excedido para el pago MSG0000
			SIS0197 Error al obtener los datos de cesta de la compra en operaciï¿½n tipo pasarela MSG0000
			SIS0198 Error el importe supera el lï¿½mite permitido para el comercio MSG0000
			SIS0199 Error el nï¿½mero de operaciones supera el lï¿½mite permitido para el comercio MSG0008
			SIS0200 Error el importe acumulado supera el lï¿½mite permitido para el comercio MSG0008
			SIS0214 El comercio no admite devoluciones MSG0008
			SIS0216 Error Ds_Merchant_CVV2 tiene mas de 3 posiciones MSG0008
			SIS0217 Error de formato en Ds_Merchant_CVV2 MSG0008
			SIS0218 El comercio no permite operaciones seguras por la entrada /operaciones MSG0008
			SIS0219 Error el nï¿½mero de operaciones de la tarjeta supera el lï¿½mite permitido para el comercio MSG0008
			SIS0220 Error el importe acumulado de la tarjeta supera el lï¿½mite permitido para el comercio MSG0008
			SIS0221 Error el CVV2 es obligatorio MSG0008
			SIS0222 Ya existe una anulaciï¿½n asociada a la preautorizaciï¿½n MSG0008
			SIS0223 La preautorizaciï¿½n que se desea anular no estï¿½ autorizada MSG0008
			SIS0224 El comercio no permite anulaciones por no tener firma ampliada MSG0008
			SIS0225 Error no existe operaciï¿½n sobre la que realizar la anulaciï¿½n MSG0008
			SIS0226 Inconsistencia de datos, en la validaciï¿½n de una anulaciï¿½n MSG0008
			SIS0227 Valor del campo Ds_Merchant_TransactionDate no vï¿½lido MSG0008
			SIS0229 No existe el cï¿½digo de pago aplazado solicitado MSG0008
			SIS0252 El comercio no permite el envï¿½o de tarjeta MSG0008
			SIS0253 La tarjeta no cumple el check-digit MSG0006
			SIS0254 El nï¿½mero de operaciones de la IP supera el lï¿½mite permitido por el comercio MSG0008
			SIS0255 El importe acumulado por la IP supera el lï¿½mite permitido por el comercio MSG0008
			SIS0256 El comercio no puede realizar preautorizaciones MSG0008
			SIS0257 Esta tarjeta no permite operativa de preautorizaciones MSG0008
			SIS0258 Inconsistencia de datos, en la validaciï¿½n de una confirmaciï¿½n MSG0008
			SIS0261 Operaciï¿½n detenida por superar el control de restricciones en la entrada al SIS MSG0008
			SIS0270 El comercio no puede realizar autorizaciones en diferido MSG0008
			SIS0274 Tipo de operaciï¿½n desconocida o no permitida por esta entrada al SIS MSG0008";
		preg_match("/$code (.*)/", $errs, $description);
		return $description[1];
	}
}
?>