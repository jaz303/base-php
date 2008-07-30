<?php
class File_Upload extends File
{
    private $original_name;
    private $type;
    private $error;
    private $size;

    public function __construct($stub) {
        parent::__construct($stub['tmp_name']);
        $this->original_name    = $stub['name'];
        $this->type             = $stub['type'];
        $this->error            = $stub['error'];
        $this->size             = $stub['size'];
    }
    
    public function size() { return $this->size; }
    public function basename() { return $this->moved ? basename($this->path) : $this->original_name; }
    
    public function extension() {
        $p = pathinfo($this->moved ? $this->path : $this->original_name);
        return $p['extension'];
    }
    
    public function filename() {
        $p = pathinfo($this->moved ? $this->path : $this->original_name);
        return $p['filename'];
    }
    
    public function type() { return $this->type; }
    
    public function exists() { return $this->success() && parent::exists(); }
    
    public function is_file() {
        if (!$this->success()) return false;
        return parent::is_file();
    }
    
    public function is_dir() {
        if (!$this->success()) return false;
        return parent::is_dir();
    }
    
    //
    // Upload specific

    public function success() { return $this->error == UPLOAD_ERR_OK; }
    public function upload_attempted() { return $this->error != UPLOAD_ERR_NO_FILE; }
    public function error() { return $this->error; }
}
?>