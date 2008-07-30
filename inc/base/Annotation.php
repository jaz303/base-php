<?php
/**
 * Annotation support.
 *
 * @author Jason Frame
 * @package BasePHP
 */
class Annotation
{
    const PROPERTY      = 1;
    const METHOD        = 2;
    
	/**
	 * Parse the annotations for a given Reflector.
	 * Annotations are derived from doc comments, and are similar to Java's.
	 * I've never really looked at them in detail, just seen the syntax.
	 *
	 * Annotation syntax is simple:
	 *
	 * :foo = expr
	 *
	 * Where 'expr' is a valid JSON expression containing no new lines.
	 * We also support single values, not nested in arrays/objects.
	 * You can't use any null expressions - this would be seen as a syntax
	 * error. You can, of course, create arrays/objects containing nulls.
	 *
	 * The JSON is subject to whatever nuances affect PHP's json_decode().
	 * Particularly, string keys must always be enclosed in quotes, and
	 * all string quoting must be done with double quotes.
	 *
	 * Example usage:
	 *
	 * :requires_super_user = true
	 * :requires_privileges = { "foo": "crude" }
	 *
	 * @param $r <tt>Reflector</tt> for which to parse annotations
	 * @return associative array of annotations for <tt>$r</tt>
	 */
	public static function parse_annotations(Reflector $r) {
		
		$comment = $r->getDocComment();
		if (strlen($comment) == 0 || strpos($comment, ':') === false) {
			return array();
		}
		
		$annotations = array();
		preg_match_all('/:(\w+)\s*=\s*(.*)/', $comment, $matches, PREG_SET_ORDER);
		foreach ($matches as $m) {
			$json = trim($m[2]);
			if ($json[0] == '[' || $json[0] == '{') {
				$decode = json_decode($json, true);
			} else {
				$decode = json_decode('[' . $json . ']', true);
				if (is_array($decode)) {
					$decode = $decode[0];
				}
			}
			if ($decode === null) {
				throw new Error_Syntax();
			}
			$annotations[$m[1]] = $decode;
		}
		
		return $annotations;
		
	}
	
	/**
	 * Returns the annotations for a given class.
	 *
	 * @param $class class name
	 * @return associative array of annotations for <tt>$class</tt>
	 */
	public static function for_class($class) {
	    return self::parse_annotations(new ReflectionClass($class));
	}

	/**
	 * Returns the annotations for a given method.
	 *
	 * @param $class class name
	 * @param $method method name
	 * @return associative array of annotations for <tt>$class::$method</tt>
	 */	
	public static function for_method($class, $method) {
	    return self::parse_annotations(new ReflectionMethod($class, $method));
	}
	
	/**
	 * Returns an array of multiple annotations for a class.
	 *
	 * @param $class class name to select annotations from
	 * @param $include bitmask specifying search-space (properties and/or methods, default: both)
	 * @param $with optional annotation key which must be present for annotation to be present in output set
	 * @return array of entries, each entry is array(Reflector, annotations)
	 */
	public static function select($class, $include = null, $with = null) {
	    
	    if ($include === null) {
	        $include = self::PROPERTY | self::METHOD;
	    }
	    
	    $reflector = new ReflectionClass($class);
	    $found = array();
	    
	    if ($include & self::PROPERTY) {
	        foreach ($reflector->getProperties() as $property) {
	            $annotations = self::parse_annotations($property);
	            if (!count($annotations)) continue;
	            if ($with && !isset($annotations[$with])) continue;
	            $found[] = array($property, $annotations);
	        }
	    }
	    
	    if ($include & self::METHOD) {
	        foreach ($reflector->getMethods() as $method) {
	            $annotations = self::parse_annotations($method);
	            if (!count($annotations)) continue;
	            if ($with && !isset($annotations[$with])) continue;
	            $found[] = array($method, $annotations);
	        }
	    }
	    
	    return $found;
	    
	}
}
?>