<?php
/**
 * Date_Interval represents a period of time, but not any specific point in time.
 */
class Date_Interval
{
  	private $months;
	private $seconds;
    
	/**
	 * Creates a new date interval.
	 * Either pass a string in the format "1Y1M1W1D1h1m1s", or individual
	 * components y, m, d, h, i, s. Note that in the string form, all
	 * components are optional, and that weeks are not available when passing
	 * each component as a separate actual parameter.
	 */
    public function __construct() {
	
		$argc = func_num_args();
		$argv = func_get_args();
		
		$years = $months = $days = $hours = $minutes = $seconds = 0;
		
		if ($argc == 0) {
			
			$this->months = $this->seconds = 0;
			return;
		
		} elseif ($argc == 1) {
		    
		    $duration = $argv[0];
		    
		    if (is_numeric($duration)) {
		        $this->seconds = (int) $duration;
		    } else {
				if ($duration[0] == '-') {
					$mul = -1;
		            $duration = substr($duration, 1);
		        } else {
					$mul = 1;
				}
				if (!preg_match('/^(\d+[a-z])+$/i', $duration)) {
					throw new Error_IllegalArgument;
				}
				preg_match_all('/(\d+)([a-z])/i', $duration, $matches, PREG_SET_ORDER);
				foreach ($matches as $bit) {
					switch ($bit[2]) {
						case 'Y': $years += $bit[1] * $mul; break;
						case 'M': $months += $bit[1] * $mul; break;
						case 'W': $days += $bit[1] * 7 * $mul; break;
						case 'D': $days += $bit[1] * $mul;  break;
						case 'h': $hours += $bit[1] * $mul; break;
						case 'm': $minutes += $bit[1] * $mul; break;
						case 's': $seconds += $bit[1] * $mul; break;
						default: throw new Error_IllegalArgument;
					}
				}
				
            }

		} elseif ($argc == 2) {
		
			$this->months	= (int) $argv[0];
			$this->seconds	= (int) $argv[1];
			return;
			
		} elseif ($argc == 6) {
		    
		    $years      = (int) $argv[0];
			$months     = (int) $argv[1];
			$days       = (int) $argv[2];
			$hours      = (int) $argv[3];
			$minutes    = (int) $argv[4];
			$seconds    = (int) $argv[5];
			
		} else {
			
			throw new Error_IllegalArgument("I don't know how to deal with {$argc} arguments");
			
		}
		
		$this->months   = $years * 12 + $months;
		
		$this->seconds  = $seconds;
		$this->seconds += $minutes * 60;
		$this->seconds += $hours * 3600;
		$this->seconds += $days * 86400;
		
	}

	public function years() { return $this->round($this->months / 12); }
	public function months() { return $this->months % 12; }
	public function days() { return $this->round($this->seconds / 86400); }
	public function hours() { return $this->round($this->seconds / 3600) % 24; }
	public function minutes() { return $this->round($this->seconds / 60) % 60; }
	public function seconds() { return $this->seconds % 60; }
	
	public function total_months() { return $this->months; }
	public function total_seconds() { return $this->seconds; }
	
    public function negate() {
        return new Date_Interval($this->months * -1, $this->seconds * -1);
    }
    
	public function compare_to(Date_Interval $d) {
	    $l = $this->months * 30 * 86400 + $this->seconds;
	    $r = $d->months * 30 * 86400 + $d->seconds;
	    return $l - $r;
    }
	
	public function equals(Date_Interval $d) {
	    return $this->compare_to($d) == 0;
	}
	
	public function add(Date_Interval $d) {
		return new Date_Interval($this->months + $d->months, $this->seconds + $d->seconds);
	}
	
	public function subtract(Date_Interval $d) {
		return $this->add($d->negate());
	}
	
	private function round($v) {
		return $v < 0 ? ceil($v) : floor($v);
	}
}
?>