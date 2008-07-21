<?php
/**
 * Annotation support.
 *
 * @author Jason Frame
 * @package BasePHP
 */
class Annotation
{
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
	
	public static function for_class($class) {
	    return self::parse_annotations(new ReflectionClass($class));
	}
	
	public static function for_method($class, $method) {
	    return self::parse_annotations(new ReflectionMethod($class, $method));
	}
}
?>