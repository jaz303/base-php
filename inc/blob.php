<?php
/**
 * Blobs represent:
 *  1. some readable data
 *  2. with a mime type
 *  3. with a filename (optional)
 *
 * @package BasePHP
 * @author Jason Frame
 */
abstract class Blob
{
    protected $mime_type = null;
    protected $file_name = null;
    
    public function mime_type() { 
        if (!$this->mime_type) {
            return $this->infer_mime_type();
        } else {
            return $this->mime_type;
        }
    }
    
    public function set_mime_type($ct) { $this->mime_type = $ct; }
    
    public function file_name() {
        if ($this->file_name === null) {
            return $this->infer_file_name();
        } else {
            return $this->file_name;
        }
    }
    
    public function set_file_name($fn) { $this->file_name = $fn; }
    
    public function is_image() { return \Image::is_supported_type($this->mime_type()); }
    public function to_image() { return new \Image($this->data(), $this->mime_type()); }
    
    /**
     * Returns true if this blob is stored in a file. This is an optimisation for use
     * in Zing, allowing data to be streamed efficiently if possible.
     *
     * @return true is this blob is stored in a file, false otherwise
     */
    public function is_file() { return false; }
    
    /**
     * Returns the path to this blob's data.
     *
     * @return the path to this blob's data.
     * @throws UnsupportedOperationException if this blob is not backed by a file
     */
    public function file_path() { throw new UnsupportedOperationException; }
    
    protected abstract function infer_mime_type();
    protected abstract function infer_file_name();
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
    protected function infer_file_name() { return null; }
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
    protected function infer_file_name() { return basename($this->file); }
    
    public function is_file() { return true; }
    public function file_path() { return $this->file; }
}
?>