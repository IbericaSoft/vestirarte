<?	
	/**
		Autor Antonio Gámez 
		Contacto agamez@dobleh.com
		Version 1.0 (28/08/2010) creacion
		Version 2.0 (01/2012) se crean los atributos y se añade ambito a los metodos
		Clase para enviar un email con PHPMailer
	*/
	class Email {
			
			private $oLogger;
			private $exito = true;
			private $mail  = null;
			private $debug = 0; //2 nos da todas las trazas smtp
			private $timeOut = 10;
			private $mailer = null;
			private $host = null;
			private $user = null;
			private $password = null;
			private $from = null;
			private $fromName = null;
			

			public function __construct($modo='mail',$host,$user,$password,$from,$fromName){
				$this->mailer= $modo; //mail|smtp
				$this->host= $host;				
				$this->user= $user;
				$this->password= $password;
				$this->from= $from;
				$this->fromName= $fromName;
			}
			
			
			
			public function enviar($to,$nameTo,$subject,$body,$altBody){
				$this->oLogger = Logger::getRootLogger();
				$this->oLogger->debug("Intentando envio de email con: $this->mailer,$this->host,$this->user, $this->password,$this->from,$to,$subject");
				$this->mail = new PHPMailer();
				$this->mail->IsHTML(true);
				$this->mail->SMTPDebug  = $this->debug;
				$this->mail->PluginDir 	= "";
				$this->mail->SMTPAuth 	= true;
				$this->mail->Timeout	= $this->timeOut;
				$this->mail->Mailer 	= $this->mailer;
				$this->mail->Host 		= $this->host;
				$this->mail->Username 	= $this->user;
				$this->mail->Password 	= $this->password;
				$this->mail->From 		= $this->from;
				$this->mail->FromName 	= $this->fromName;
				$this->mail->Timeout	= $this->timeOut;
				
				if ($nameTo)
				  	$this->mail->AddAddress( $to,$nameTo );
				  else
				  	$this->mail->AddAddress( $to );
				  $this->mail->Subject 		= $subject;
				  $this->mail->Body 			= $body;
				  if ( $altBody )
				  	$this->mail->AltBody    = $altBody;
				  $this->exito = $this->mail->Send();
					$this->mail->ClearAddresses();
					return $this->exito;
			}
			
			public function getErrorEnvio(){
				return $this->mail->ErrorInfo;
			}	
	}