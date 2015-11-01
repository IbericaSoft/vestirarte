<?
	/**
	 * 	@author Antonio Gamez 2014
	 * 	Clase especializada en la persistencia y control de una tienda. Esta clase se debe personalizar si fuera necesario
	 *  para cada tienda. Esta dise�ada para ser en la medida de lo posible lo mas est�ndar posible.
	 *  Aqui tendr�amos que a�adir las clases nuevas o las personalizaciones ***Custom.class.php de cada tienda
	 */
	
	require_once ( OS_ROOT . '/kernel/negocio/classes/BeanGenerico.class.php' );
	require_once ( OS_ROOT . '/kernel/negocio/classes/BeanCesta.class.php' );
	require_once ( OS_ROOT . '/kernel/negocio/classes/BeanCestaCustom.class.php' );
	require_once ( OS_ROOT . '/kernel/negocio/classes/BeanArticulo.class.php' );
	require_once ( OS_ROOT . '/kernel/negocio/classes/BeanArticuloCustom.class.php' );
	require_once ( OS_ROOT . '/kernel/negocio/classes/BeanPorte.class.php' );
	require_once ( OS_ROOT . '/kernel/negocio/classes/PortesZonas.class.php' );
	require_once ( OS_ROOT . '/kernel/negocio/classes/Cesta.class.php' );
	require_once ( OS_ROOT . '/kernel/negocio/classes/CestaCustom.class.php' );
	require_once ( OS_ROOT . '/kernel/negocio/classes/Cliente.class.php' );
	require_once ( OS_ROOT . '/kernel/negocio/classes/ClienteCustom.class.php' );
	require_once ( OS_ROOT . '/kernel/negocio/classes/Sermepa.class.php' );
	require_once ( OS_ROOT . '/kernel/negocio/classes/ClientAgent.class.php' );
	
	class Tienda extends Tienda_Instanciador {
		/** Todas las tienda tienen la misma estructura (de momento) categoria->familia->subfamilia->articulo,cliente,cesta,portes,tpv */
		public $categoria_actual = null;//tiene en todo momento la categoria con la que trabajar
		public $familia_actual = null;//tiene en todo momento la familia con la que trabajar
		public $subfamilia_actual = null;//tiene en todo momento la subfamilia con la que trabajar
		public $articulo_actual = null;//tiene en todo momento el articulo con el que trabajar
		public $cesta = null;//es una clase que tiene el contenido de Cesta.class
		public $cliente = null;//es una clase que tiene los datos del cliente Cliente.class
		public $portes = null;//es una clase que tiene los datos del porte actual Portes.class
		public $tpv = null;//es una clase que tiene los datos del TPV, es decir, la configuracion q Sermepa nos dat
		public $email = null;//es una clase para envio de emails
		
		/** Atributos para persistir los valores comunes de cualquier tienda relativos a seguridad, email, ... */		
		public $email_strategy=null;
		public $email_host=null;
		public $email_account=null;
		public $email_password=null;
		public $email_user=null;
		public $email_alias=null;
		public $email_nofiticaciones=null;
		public $email_auditoria=null;
		public $email_pedidos=null;
		
		/** Incluir aqui atributos especificos de esta tienda */
		public $captcha_result = null;
		public $captcha_string = null;
		public $captcha_image  = null;
		public $mensaje_texto_paginas_redireccionadas = null;
		public $link_redirecciones = null;
		public $html_email_info = null;
		public $html_email_pedido = null;
		public $html_email_auditoria = null;
				
		/** El construct es invocado por index.html */
		public function __construct(){
			Navigator::$log->debug("Inicializando y cacheando datos");
			
			$this->cesta = new CestaCustom();
			$this->cliente = new ClienteCustom();
			$this->portes = new PortesZonas();
			$this->tpv = new Sermepa();
			
			//Datos de configuracion email			
			$this->email_strategy= Utils_OS::getValueOS(Navigator::$connection, 'mail_strategy');
			$this->email_host= Utils_OS::getValueOS(Navigator::$connection, 'mail_host');
			$this->email_account= Utils_OS::getValueOS(Navigator::$connection, 'mail_account');
			$this->email_user= Utils_OS::getValueOS(Navigator::$connection, 'mail_user');
			$this->email_password= Utils_OS::getValueOS(Navigator::$connection, 'mail_password');
			$this->email_alias= Utils_OS::getValueOS(Navigator::$connection, 'mail_alias');
			$this->email_pedidos = explode(',', Utils_OS::getValueAPP(Navigator::$connection, 'EMAIL_PEDIDOS'));
			$this->email_nofiticaciones = explode(',', Utils_OS::getValueAPP(Navigator::$connection, 'EMAIL_NOTIFICACIONES'));
			$this->email_auditoria 	= explode(',',Utils_OS::getValueAPP(Navigator::$connection, 'EMAIL_AUDITORIA'));
					
			//el gestor de correo esta listo
			$this->email = new Email($this->email_strategy,$this->email_host,$this->email_account,$this->email_password,$this->email_user,$this->email_alias);			
		}
		
		public function regenerarCaptcha(){
			$op1 = rand(5,10);
			$op2 = rand(1,5);
			$signo = str_shuffle("+-");
			$this->captcha_string = "$op1$signo[0]$op2=";
			if ( $signo[0]=="-")
				$this->captcha_result = $op1-$op2;
			else
				$this->captcha_result = $op1+$op2;
			parent::$log->debug("Captcha: $this->captcha_string $this->captcha_result");
			
			ob_start();//para capturar la salida del buffer a una variable... si no, tenia que ser a fichero y luego leer, borrar, ....
			$im = imagecreate(75, 30);//tama�o
			$bg = imagecolorallocate($im, 255, 255, 255);//fondo blanco
			$textcolor = imagecolorallocate($im, 0, rand(0,255), rand(0,255));//color aleatorio
			// Write the string at the top left
			imagestring($im, 5, 10, 7, $this->captcha_string, $textcolor);
			//imagestring($im, 5, 10, 7, "* * * * * *", imagecolorallocate($im, 0, 255));
			imagepng($im);
			imagedestroy($im);
			$this->captcha_image = 'data:image/png;base64,'.base64_encode(ob_get_clean());//en base64 para pintarla en bruto
		}
	}
?>
