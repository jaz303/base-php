<?php
class Base
{
    /**
     * Performs some magic transformations on script input to make life a little
     * more bearable.
     */
    public static function rewire() {
        
        self::do_rewire($_POST);
        self::do_rewire($_GET);
        self::do_rewire($_REQUEST);
        self::do_rewire($_COOKIE);
        
        // For each uploaded file, create a corresponding file upload object in
        // $_POST. This allows us to deal with uploaded files in a more
        // elegant manner. It is not a security issue because there is no way
        // to inject object instances into $_POST.
        foreach ($_FILES as $key => $file) {
            if (is_string($file['name'])) {
                $_POST[$key] = new File_Upload($file);
            } elseif (is_array($file['name'])) {
                if (!is_array($_POST[$key])) {
                    $_POST[$key] = array();
                }
                self::recurse_files(
                    $file['name'],
                    $file['type'],
                    $file['tmp_name'],
                    $file['error'],
                    $file['size'],
                    $_POST[$key]
                );
            }
        }
        
    }
    
    private static function recurse_files($n, $ty, $tm, $e, $s, &$target) {
        foreach ($n as $k => $v) {
            if (is_string($v)) {
                $target[$k] = new File_Upload(array('name'      => $v,
                                                    'type'      => $ty[$k],
                                                    'tmp_name'  => $tm[$k],
                                                    'error'     => $e[$k],
                                                    'size'      => $s[$k]));
            } else {
                if (!is_array($target[$k])) {
                    $target[$k] = array();                
                }
                self::recurse_files($n[$k], $ty[$k], $tm[$k], $e[$k], $s[$k], $target[$k]);
            }
        }
    }
    
    private static function do_rewire(&$array) {
        foreach (array_keys($array) as $k) {
            try {
                if ($k[0] == '@') {
                    $array[substr($k, 1)] = Date::for_param($array[$k]);
                    unset($array[$k]);
                // } elseif ($k[0] == '$') {
                //     $array[substr($k, 1)] = Money::for_param($array[$k]);
                //     unset($array[$k]);
                } elseif (is_array($array[$k])) {
                    self::do_rewire($array[$k]);
                }
            } catch (Exception $e) {
                $array[$k] = null;
            }
        }
    }
}
?>
