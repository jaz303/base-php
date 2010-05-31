<?php
abstract class AbstractFile
{
    public static function extension_for($filename) {
        return (($p = strrpos($filename, '.')) === false) ? '' : substr($filename, $p + 1);
    }
    
    public abstract function path();
    public function dirname() { return dirname($this->path()); }
    public function basename() { return basename($this->path()); }
    public function extension() { return self::extension_for($this->basename()); }
    
    public function size() { return filesize($this->path()); }
    public function content_type() { return MIME::for_file($this()); }
    
    public function is_readable() { return is_readable($this->path()); }
    
    public function read() {
        $contents = file_get_contents($this->path());
        if ($contents === false) throw new IOException;
        return $content;
    }
    
    public function move($new_path) {
        if (rename($this->path(), $new_path)) {
            return new File($new_path);
        } else {
            throw new IOException("couldn't move {$this->path()} to $new_path");
        }
    }
    
    public function delete() {
        if (!unlink($this->path())) {
            throw new IOException("couldn't delete $this->path");
        }
    }
    
    public function is_supported_image() {
        return Image::is_supported_type($this->content_type());
    }
    
    public function to_image() {
        return new Image($this->path());
    }
}

class File extends AbstractFile
{
    private $path;
    public function __construct($path) { $this->path = $path; }
    public function path() { return $this->path; }
}

class UploadedFile extends AbstractFile
{
    private $upload_path;
    private $original_name;
    private $content_type;
    private $size;
    
    public function __construct(array $upload_data) {
        
        $this->upload_path      = $upload_data['tmp_name'];
        $this->original_name    = $upload_data['name'];
        $this->content_type     = $upload_data['type'];
        $this->size             = $upload_data['size'];
        
        if (!is_uploaded_file($this->upload_path)) {
            throw new SecurityException("{$this->upload_path} is not an uploaded path");
        }
        
    }
    
    public function ok() { return true; }
    public function was_upload_attempted() { return true; }
    
    public function path() { return $this->upload_path; }
    public function basename() { return $this->original_name; }
    
    public function size() { return $this->size; }
    public function content_type() { return $this->content_type; }
}

/**
 * Represents a file which could not be uploaded due to an error
 *
 * UploadedFile and UploadedFileError both support the ok() method, so
 * use this to determine if the upload was successful (don't use instanceof)
 */
class UploadedFileError
{
    private $error;
    
    public function __construct($error) {
        $this->error = $error;
    }
    
    public function ok() { return false; }
    
    public function was_upload_attempted() {
        return $this->error != UPLOAD_ERR_NO_FILE;
    }
    
    public function is_max_size_exceeded() { 
        return $this->error == UPLOAD_ERR_INI_SIZE
                || $this->error == UPLOAD_ERR_FORM_SIZE;
    }
    
    public function is_partial_upload() {
        return $this->error == UPLOAD_ERR_PARTIAL;
    }
    
    public function is_internal_error() {
        return $this->error == UPLOAD_ERR_NO_TMP_DIR
                || $this->error == UPLOAD_ERR_CANT_WRITE
                || $this->error == UPLOAD_ERR_EXENSION;
    }
    
    public function is_supported_image() {
        return false;
    }
}
?>