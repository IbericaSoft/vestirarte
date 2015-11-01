<?
/**
 * Usuario en session
 * @author Antonio G�mez Moro
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
			$this->oLogger->debug("A�adiendo tema: $theme");
			$this->theme = $theme;
		}
		
		public function getWallPaper(){
			return $this->wallPaper;
		}
		
		public function setWallPaper($wallPaper){
			$this->oLogger->debug("A�adiendo fondo: $wallPaper");
			$this->wallPaper = $wallPaper;
		}
		
		
	}
?>