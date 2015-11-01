<?php


/**
 * Handle file uploads via XMLHttpRequest
 */
class qqUploadedFileXhr {
	
	public $path;
	private $log;
	function __construct($log){
		$this->log = $log;
	}
	
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) { 

    	$this->log->debug( $path );
    	$this->path = $path;
    	
        $input = fopen("php://input", "r");
        $temp = tmpfile();
       
        $realSize = stream_copy_to_stream($input, $temp);
        fclose($input);
        
        
        if ($realSize != $this->getSize()){            
            return false;
        }
        
        $target = fopen($path, "w");
        fseek($temp, 0, SEEK_SET);
        stream_copy_to_stream($temp, $target);
        fclose($target);
        
        
        $this->log->debug( "Fichero subido al servidor desde $temp hasta $target" );
        
        return true;
    }
    function getName() {
        return $_GET['qqfile'];
    }
    
    function getSize() {
        if (isset($_SERVER["CONTENT_LENGTH"])){
            return (int)$_SERVER["CONTENT_LENGTH"];            
        } else {
            throw new Exception('Getting content length is not supported.');
        }      
    }   
}

/**
 * Handle file uploads via regular form post (uses the $_FILES array)
 */
class qqUploadedFileForm { 

private $log;
	function __construct($log){
		$this->log = $log;
	}
	
    /**
     * Save the file to the specified path
     * @return boolean TRUE on success
     */
    function save($path) {
        if(!move_uploaded_file($_FILES['qqfile']['tmp_name'], $path)){
            return false;
        }
        return true;
    }
    function getName() {
        return $_FILES['qqfile']['name'];
    }
    function getSize() {
        return $_FILES['qqfile']['size'];
    }
}

class qqFileUploader {
    private $allowedExtensions = array();
    private $sizeLimit = 10485760;
    public $file;
	private $log;
    function __construct(array $allowedExtensions = array(), $sizeLimit = 10485760, $log){        
        $allowedExtensions = array_map("strtolower", $allowedExtensions);
        $this->log = Logger::getRootLogger();
        $this->log->debug ("estoy en qqFileUploader");
        $this->allowedExtensions = $allowedExtensions;        
        $this->sizeLimit = $sizeLimit;
        
        $this->checkServerSettings();       

        if (isset($_GET['qqfile'])) {
            $this->file = new qqUploadedFileXhr($this->log);
        } elseif (isset($_FILES['qqfile'])) {
            $this->file = new qqUploadedFileForm($this->log);
        } else {
            $this->file = false; 
        }
    }
    
    private function checkServerSettings(){        
        $postSize = $this->toBytes(ini_get('post_max_size'));
        $uploadSize = $this->toBytes(ini_get('upload_max_filesize'));        
        
//        if ($postSize < $this->sizeLimit || $uploadSize < $this->sizeLimit){
//            $size = max(1, $this->sizeLimit / 1024 / 1024) . 'M';             
//            die("{'error':'increase post_max_size and upload_max_filesize to $size'}");    
//        }   

    }
    
    private function toBytes($str){
        $val = trim($str);
        $last = strtolower($str[strlen($str)-1]);
        switch($last) {
            case 'g': $val *= 1024;
            case 'm': $val *= 1024;
            case 'k': $val *= 1024;        
        }
        return $val;
    }
    
    /**
     * Returns array('success'=>true) or array('error'=>'error message')
     */
    function handleUpload($uploadDirectory, $replaceOldFile = FALSE){   	
    	$this->log->debug($uploadDirectory);

        if (!is_writable($uploadDirectory)){
        	$this->log->debug ("no hay permisos de escritura");
            return array('error' => "No hay permisos de escritura para $uploadDirectory");
        }
        
         $this->log->debug ('tengo permisos');
         
        if (!$this->file){
        	 $this->log->debug ('No se ha subido ningún fichero.');
            return array('error' => 'No se ha subido ningún fichero.');
        }
        
        $this->log->debug ('tengo fichero');
        
       
        $size = $this->file->getSize();
        
        if ($size == 0) {
        	 $this->log->debug ( 'Fichero vacio!!!' );
            return array('error' => 'Fichero vacío');
        }
        
        $this->log->debug ('size no es cero');
        
//        if ($size > $this->sizeLimit) {
//        	$this->log->debug( $size . ' ' . $this->sizeLimit);
//            return array('error' => 'Fichero demasiado grande');
//        }
        
        $pathinfo = pathinfo($this->file->getName());
        $filename = $pathinfo['filename'];
        //$filename = md5(uniqid());
        $ext = $pathinfo['extension'];

        if($this->allowedExtensions && !in_array(strtolower($ext), $this->allowedExtensions)){
            $these = implode(', ', $this->allowedExtensions);
            $this->log->debug ( 'Extensión de fichero no válida, debería ser una de estas: '. $these . '.' );
            return array('error' => 'Extensión de fichero no válida, debería ser una de estas: '. $these . '.');
        }
       
        if(!$replaceOldFile){
            /// don't overwrite previous files that were uploaded
            while (file_exists($uploadDirectory . $filename . '.' . $ext)) {
                $filename .= rand(10, 99);
            }
        }
        
        if ($this->file->save($uploadDirectory . $filename . '.' . $ext)){
             $this->log->debug ("ok ;)");
        	return array('success'=>true);
        } else {
        	$this->log->debug ("KO ;(");
            return array('error'=> 'Fichero no subido.' . 'Operación cancelada o hay un error');
        }
        
    }    
}



