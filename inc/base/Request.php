<?php
/**
 * Wrapper around incoming client request. <var>ArrayAccess</var> and
 * <var>IteratorAggregate</var> implementations allow instances of
 * <var>Request</var> to be used in place of <var>$_REQUEST</var>.
 *
 * @author Jason Frame
 * @package BasePHP
 */
class HTTP_Request implements ArrayAccess, IteratorAggregate
{
    private $timestamp  = null;
    private $time       = null;
    private $wants      = null;
    private $languages  = null;
    
    // Memo for raw POST data
    private $post_raw   = null;
    
    // Memo for SimpleXML tree of posted XML
    private $post_xml   = null;
    
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
    // Note: country codes are disregarded
    
    /**
     * Returns an array of language codes accepted by the client.
     * Array is map of language code => priority.
     * Sorting is in descending order (e.g. highest priority first)
     */
    public function languages() {
        if ($this->languages === null) {
            $this->languages = $this->detect_languages();
        }
        return $this->languages;
    }
    
    public function language($accept = null, $default = null) {
        $languages = $this->languages();
        if (count($languages) == 0) {
            return $default;
        } elseif (is_array($accept)) {
            $best = array(0, $default);
            foreach ($accept as $a) {
                if (isset($languages[$a]) && $languages[$a] > $best[0]) {
                    $best = array($languages[$a], $a);
                }
            }
            return $best[1];
        } else {
            reset($languages);
            list($code, $priority) = each($languages);
            return $code;
        }
    }
    
    //
    // Data
    
    /**
     * Returns the raw POST data for this request.
     *
     * @return the raw POST data for this request.
     */
    public function raw_post_data() {
        if ($this->post_raw === null) {
            $this->post_raw = file_get_contents('php://input');
        }
        return $this->post_raw;
    }
    
    public function xml() {
        if ($this->post_xml === null) {
            $this->post_xml = new SimpleXMLElement($this->raw_post_data());
        }
        return $this->post_xml;
    }
    
    //
    // User Agent
    
    public function is_xml_rpc() {
        return $this->is_post()
                && $_SERVER['CONTENT_TYPE'] == 'text/xml'
                && XML_RPC::is_request($this->xml());
    }
    
    public function is_xhr() {
        return isset($_SERVER['HTTP_X_REQUESTED_WITH'])
                && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest';
    }
    
    //
    // Wants
    
    public function wants() {
        if ($this->wants === null) {
            $this->wants = explode(',', $_SERVER['HTTP_ACCEPT']);
        }
        return $this->wants;
    }
    
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
    
    //
    // Privates
    
    private function detect_languages() {
        
        if (empty($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
            return array();
        }
        
        try {
            
            $detected = array();
            foreach (explode(',', $_SERVER['HTTP_ACCEPT_LANGUAGE']) as $lang) {
                $bits = array_map('trim', explode(';q=', $lang));
                $bits[] = 1;
                if (preg_match('/^([a-z]+)(\-[a-z]+)?$/i', $bits[0], $matches)) {
                    if (preg_match('/^\d+(\.\d+)?$/', $bits[1])) {
                        $detected[$matches[1]] = floatval($bits[1]);
                    } else {
                        throw new Exception;
                    }
                } else {
                    throw new Exception;
                }
            }
            
            krsort($detected);
            return $detected;
            
        } catch (Exception $e) {
            return array();
        }

    }
}
?>