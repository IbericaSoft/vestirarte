<?
/**
 * Usuario en session
 * @author Antonio Gámez Moro
 *
 */
		
	class User {
		
		private $id = null;
		private $rol;
		private $name;
		private $dateLogin;
		
		
		/** Contruye el objeto  */
		public function __construct(){
		}
		
		public function getId(){
			return $this->id;
		}
		
		public function setId($id){
			$this->id = $id;
			$this->dateLogin = date("Y-m-d H:i:s");
		}
		
		public function getRol(){
			return $this->rol;
		}
		
		public function getName(){
			return $this->name;
		}
			
		public function setRol($rol){
			$this->rol=$rol;
		}
		
		public function setName($name){
			$this->name=$name;
		}
		
		public function isLogged(){
			return ($this->id!=null);
		}
		
	}
?>