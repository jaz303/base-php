<?php
/**
 * Wrapper around incoming client request. <tt>ArrayAccess</tt> and
 * <tt>IteratorAggregate</tt> implementations allow instances of
 * <tt>Request</tt> to be used in place of <tt>$_REQUEST</tt>.
 *
 * @author Jason Frame
 * @package BasePHP
 */
class Request implements ArrayAccess, IteratorAggregate
{
    private $timestamp  = null;
    private $time       = null;
    private $language   = null;
    
    public function __construct() {
        $this->timestamp = $_SERVER['REQUEST_TIME'];
    }
    
    //
    // Host/URL
    
    /**
     * Returns the full URL for this request.
     *
     * @return full URL for this request, for example: http://foobar.com:3000/index.php
     */
    public function url() {
        return $this->protocol() . '://' . $this->server() . $this->path();
    }
    
    /**
     * Returns the host for this request.
     *
     * @return host for this request, for example: foobar.com
     */
    public function host() {
        return $_SERVER['HTTP_HOST'];
    }
    
    /**
     * Returns the port for this request.
     *
     * @return port for this request, for example: 3000
     */
    public function port() {
        return (int) $_SERVER['SERVER_PORT'];
    }
    
    /**
     * Returns the server for this request, for example: foobar.com:3000.
     * Port will only be appended if not equal to 80.
     *
     * @return server for this request
     */
    public function server() {
        $server = $this->host();
        $port = $this->port();
        if ($port != 80) $server .= ":$port";
        return $server;
    }
    
    /**
     * Returns the protocol for this request, for example: http
     *
     * @return protocol for this request
     */
    public function protocol() {
        return $this->is_secure() ? 'https' : 'http';
    }
    
    /**
     * Returns the path for this request, for example: /index.php
     *
     * @return path for this request
     */
    public function path() {
        return $_SERVER['REQUEST_URI'];
    }
    
    /**
     * Returns the query string for this request.
     *
     * @return query string for this request
     */
    public function query_string() {
        return $_SERVER['QUERY_STRING'];
    }
    
    //
    // Referer
    
    /**
     * Returns the HTTP referer for this request.
     *
     * @return HTTP referer for this request, or null if none is present.
     */
    public function referer() {
        return isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : null;
    }
    
    //
    // HTTP method
    
    /**
     * Returns the HTTP method for this request.
     *
     * @return HTTP method for this request.
     */
    public function method() { return $_SERVER['REQUEST_METHOD']; }
    
    public function is_get() { return $this->method() == 'GET'; }
    public function is_post() { return $this->method() == 'POST'; }
    public function is_put() { return $this->method() == 'PUT'; }
    public function is_delete() { return $this->method() == 'DELETE'; }
    
    //
    // Time
    
    public function timestamp() {
        return $this->timestamp;
    }
    
    public function time() {
        if (!$this->time) $this->time = new Date_Time($this->timestamp);
        return $this->time;
    }
    
    //
    // SSL
    
    /**
     * Returns true if this request was made over a secure connection, false
     * otherwise.
     *
     * @return true if this request was made over a secure connection, false
     *         otherwise
     */
    public function is_secure() {
        return isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on';
    }
    
    //
    // Language
    
    public function language() {
        if ($this->language === null) $this->detect_language();
        return $this->language;
    }
    
    //
    // User Agent
    
    public function is_xhr() {
        
    }
    
    //
    // Wants
    
    public function wants_html() {
        
    }
    
    public function wants_json() {
        
    }
    
    public function wants_xml() {
        
    }
    
    public function wants_image() {
        
    }
    
    //
    // Array access for getting to $_REQUEST
    
    public function offsetExists($key) {
        return array_key_exists($key, $_REQUEST);
    }
    
    public function offsetGet($key) {
        return array_key_exists($key, $_REQUEST) ? $_REQUEST[$key] : null;
    }
    
    public function offsetSet($key, $value) {
        $_REQUEST[$key] = $value;
    }
    
    public function offsetUnset($key) {
        unset($_REQUEST[$key]);
    }
    
    public function getIterator() {
        return new ArrayIterator($_REQUEST);
    }
    
    
    private function detect_language() {
        if (!isset($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return null;
        } else {
            
        }
        
        
        
    }
    
    private static $language_map = array(
        
        
        
    );
}
?>