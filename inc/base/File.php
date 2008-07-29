<?php
class File
{
    protected $path;
    protected $moved    = false;
    
    public function __construct($path) {
        $this->path = $path;
    }
    
    public function size() { return filesize($this->path); }
    public function path() { return $this->path; }
    public function directory() { return dirname($this->path); }
    public function basename() { return basename($this->path); }
    public function filename() { $p = pathinfo($this->path); return $p['filename']; }
    public function extension() { $p = pathinfo($this->path); return $p['extension']; }
    public function type() { return MIME::for_filename($this->path()); }
    
    public function exists() { return file_exists($this->path()); }
    public function is_file() { return is_file($this->path()); }
    public function is_dir() { return is_dir($this->path()); }
    
    public function read() {
        $data = @file_get_contents($this->path);
        if ($data === false) {
            throw new Error_IO("Error reading file $this->path");
        }
        return $data;
    }
    
    public function move_to($target, $preserve_extension = false) {
        if (is_dir($target)) {
            $target = preg_replace('/\/$/', "/{$this->basename()}", $target);
        } elseif ($preserve_extension && (strlen($extension = $this->extension()))) {
            $target .= ".$extension";
        }
        if (!@rename($this->path, $target)) {
            throw new Error_IO("Error renaming {$this->path} to $target");
        }
        $this->path = $target;
        $this->moved = true;
    }
    
    public function is_image() {
        return getimagesize($this->path()) !== false;
    }
    
    public function is_supported_image() {
        
    }
    
    public function create_image() {
        return new Image($this->path());
    }
}
?>