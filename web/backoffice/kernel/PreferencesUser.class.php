<?
/**
 * Usuario en session
 * @author Antonio Gámez Moro
 *
 */
		
	class PreferencesUser {
		
		private $theme;
		private $wallPaper;
		private $icons;
		private $oLogger;
		
		/** Contruye el objeto  */
		public function __construct(){
			//$this->icons = new Icons();
			$this->oLogger = Logger::getRootLogger();
			$this->oLogger->debug("Creado objeto PreferencesUser");
		}
		
		public function __destruct(){
			
		}
		
		public function getTheme(){
			return $this->theme;
		}
		
		public function setTheme($theme){
			$this->oLogger->debug("Añadiendo tema: $theme");
			$this->theme = $theme;
		}
		
		public function getWallPaper(){
			return $this->wallPaper;
		}
		
		public function setWallPaper($wallPaper){
			$this->oLogger->debug("Añadiendo fondo: $wallPaper");
			$this->wallPaper = $wallPaper;
		}
		
		
	}
?>