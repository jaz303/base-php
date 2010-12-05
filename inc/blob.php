<?php
/**
 * Blobs represent typed data.
 *
 * @package BasePHP
 * @author Jason Frame
 */
abstract class Blob
{
    protected $mime_type = null;
    
    public function mime_type() { 
        if (!$this->mime_type) {
            return $this->infer_mime_type();
        } else {
            return $this->mime_type;
        }
    }
    
    public function set_mime_type($ct) { $this->mime_type = $ct; }
    
    public function is_image() { return \Image::is_supported_type($this->mime_type()); }
    public function to_image() { return new \Image($this->data(), $this->mime_type()); }
}

/**
 * MemoryBlob is a blob whose data is stored in memory.
 */
class MemoryBlob extends Blob
{
    private $data;
    
    public function __construct($data, $mime_type = null) {
        $this->data = $data;
        $this->set_mime_type($mime_type);
    }
    
    public function data() { return $this->data; }
    public function length() { return strlen($this->data); }
    
    protected function infer_mime_type() { return 'application/octet-stream'; }
}

/**
 * FileBlob is a blob whose data is stored in a file
 */
class FileBlob extends Blob
{
    private $file;
    
    public function __construct($file, $mime_type = null) {
        $this->file = $file;
        $this->set_mime_type($mime_type);
    }
    
    public function data() { return file_get_contents($this->file); }
    public function length() { return filesize($this->file); }
    
    protected function infer_mime_type() { return \MIME::for_file($this->file); }
}
?>