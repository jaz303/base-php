<?php
/**
 * Internally, <tt>Date</tt> represents weekdays as integers 1..7, 1 denoting
 * Monday. You can modify this by defining DATE_WEEK_START.
 */
if (!defined('DATE_WEEK_START')) {
	define('DATE_WEEK_START', 1);
}

/** 
 * You can also offset this weekday value. For example, set this to -1 if you
 * want to deal with day numbers 0..6.
 */
if (!defined('DATE_WEEK_OFFSET')) {
	define('DATE_WEEK_OFFSET', 0);
}

/**
 * Date class
 *
 * @todo add method for returning interval between two dates (in terms of months)
 * @todo add method for returning interval between two dates (in terms of seconds)
 *
 * @package BasePHP
 * @author Jason Frame
 */
class Date
{
    public static function date_for($d) {
        if ($d === null) {
            return null;
        } elseif ($d instanceof Date) {
            return $d->to_date();
        } else {
            return new Date($d);
        }
    }
    
    public static function datetime_for($d) {
        if ($d === null) {
            return null;
        } elseif ($d instanceof Date) {
            return $d->to_datetime();
        } else {
            return new Date_Time($d);
        }
    }

	public static function is_leap_year($year) {
	    return ($year % 4 == 0) && (($year % 100 != 0) || ($year % 400 == 0));
	}

	public static function days_for_month($month, $year = null) {
		if ($year === null) $year = date('Y');
		if ($month == 2) {
			return self::is_leap_year($year) ? 29 : 28;
		} else {
			return self::$month_days[$month];
		}
	}
    
    private static $month_days = array(
        1   => 31,
        2   => null,
        3   => 31,
        4   => 30,
        5   => 31,
        6   => 30,
        7   => 31,
        8   => 31,
        9   => 30,
        10  => 31,
        11  => 30,
        12  => 31
    );
    
	// Constant representing an ISO8601 datetime
	// This regex detects fractional seconds although the implementation ignores them.
	// We currently don't support/do anything with timezones.
	const ISO_8601 = '/^(\d{4})-(\d{2})-(\d{2})((T| )(\d{2}):(\d{2}):(\d{2})(\.(\d+))?)?$/';
	
    protected   $y, $m, $d;
	protected   $h = 0, $i = 0, $s = 0;
	private     $unix = null;
	
	public function __construct() {
	
		$x = func_num_args();
		
		// 0 args; current time and date
		if ($x == 0) {
			
			$this->from_unix(time());
			
		// 1 arg - could either be:
		// ISO8601
		// Component array
		// UNIX timestamp
		} elseif ($x == 1) {
			
			$a = func_get_arg(0);
			
			// If it's a number we'll assume it's a unix timestamp
			if (is_numeric($a)) {
				
				$this->from_unix($a);
				
			// Attempt to parse any other scalar as an ISO-8601 date string (sans timezone) or date string
			} elseif (is_scalar($a)) {
			    
			    if (preg_match(self::ISO_8601, $a, $m)) {
			        $this->set_date($m[1], $m[2], $m[3]);
    				if (isset($m[4])) {
    				    $this->set_time($m[6], $m[7], $m[8]);
    				}
			    } elseif ($unix = strtotime($a)) {
			        $this->from_unix($unix);
			    } else {
			        throw new Error_IllegalArgument;
			    }

			// Component array
			// We accept either a 3 element array (which will set the date), or
			// a >= 5 element array (which will set the time as well, defaulting
			// the seconds fields to 0)
			} elseif (is_array($a) && isset($a[0]) && count($a) >= 1) {
			    
			    if (count($a) == 1) {
			        array_push($a, 1, 1, 0, 0, 0);
			    } elseif (count($a) == 2) {
			        array_push($a, 1, 0, 0, 0);
			    } elseif (count($a) < 6) {
			        array_push($a, 0, 0, 0);
			    }
			    
			    $this->set_date($a[0], $a[1], $a[2]);
			    $this->set_time($a[3], $a[4], $a[5]);
			
			// Associative array    
			} elseif (is_array($a)) {
			    
			    $this->set_date(isset($a['year']) ? $a['year'] : date('Y'),
			                    isset($a['month']) ? $a['month'] : 1,
			                    isset($a['day']) ? $a['day'] : 1);
			                    
			    $this->set_time(isset($a['hour']) ? $a['hour'] : 0,
			                    isset($a['minute']) ? $a['minute'] : 0,
			                    isset($a['second']) ? $a['second'] : 0);
			    
			// Any other single argument is invalid
			} else {
				throw new Error_IllegalArgument("Error creating date instance - I couldn't work out what format you wanted");
			} 
			
		// 3 args - explicit year, month, day
		} elseif ($x == 3) {
			$y = func_get_args();
			$this->set_date($y[0], $y[1], $y[2]);
			
		// 5/6 args - explicit year, month, day, hours, minutes [, seconds]
		} elseif ($x == 5 || $x == 6) {
			$y = func_get_args();
			$this->set_date($y[0], $y[1], $y[2]);
			$this->set_time($y[3], $y[4], isset($y[5]) ? $y[5] : 0);
			
		} else {
			throw new Error_IllegalArgument("Error creating date instance");
		}
		
	}
	
	//
	// Readers
	
	public function year() { return $this->y; }
	public function month() { return $this->m; }
	public function day() { return $this->d; }
	public function hour() { return $this->h; }
	public function minute() { return $this->i; }
	public function second() { return $this->s; }
	
	//
	// Conversions
	
	public function to_date() {
	    return $this;
	}
	
	public function to_datetime() {
	    return new Date_Time($this->y, $this->m, $this->d, 0, 0, 0);
	}
	
	public function to_iso() {
	    return $this->to_iso_date();
	}
	
	public function to_iso_date() {
		return sprintf("%04d-%02d-%02d", $this->y, $this->m, $this->d);
	}
	
	public function to_iso_datetime() {
		return sprintf(
			"%04d-%02d-%02dT%02d:%02d:%02d",
			$this->y, $this->m, $this->d, $this->h, $this->i, $this->s
		);
	}
	
	public function to_rfc822_datetime() {
	    return $this->to_format('r');
	}
	
	public function to_unix() {
		if ($this->unix == null) {
		    if ($this->y < 1970) {
				throw new Error_UnsupportedOperation("Can't convert date to UNIX timestamp");
			} else {
				$this->unix = mktime($this->h, $this->i, $this->s, $this->m, $this->d, $this->y);
			}
		}
		return $this->unix;
	}
	
	public function to_format($f) {
		return date($f, $this->to_unix());
	}
	
	public function __toString() {
	    return $this->to_iso();
	}
	
    //
	// Static methods to directly format ISO strings
	
	/**
	 * Format an ISO string directly. This will only work on dates which
	 * can be represented as Unix timestamps. Also, all input is assumed
	 * to be valid.
	 *
	 * @param $v ISO8601 date string
	 * @param $f format (same as PHP's date() builtin)
	 */
	public static function format($v, $f) {
        $date = new Date_Time($v);
        return $date->to_format($f);
	}
	
	//
	// Worker functions
	
	protected function from_unix($u) {
		$this->unix = $u;
		$s = getdate($u);
		$this->set_date($s['year'], $s['mon'], $s['mday']);
		$this->set_time($s['hours'], $s['minutes'], $s['seconds']);
	}
	
	protected function set_date($y, $m, $d) {
	    
	    if (!checkdate($m, $d, $y)) {
			throw new Error_IllegalArgument();
		}
		
		$this->y = (int) $y;
		$this->m = (int) $m;
		$this->d = (int) $d;
		
	}
	
	protected function set_time($h, $i, $s) {
		if ($h == 24 && $i == 0 && $s == 0) $h = 0;
		if ($h < 0 || $h > 23 || $i < 0 || $i > 59 || $s < 0 || $s > 59) {
			throw new Error_IllegalArgument();
		}
		$this->really_set_time($h, $i, $s);
	}
	
	protected function really_set_time($h, $i, $s) {
	    $this->h = 0;
	    $this->i = 0;
	    $this->s = 0;
	}
	
	/**
	 * Compares this date with another.
	 * There's probably a better algorithm for this.
	 *
	 * @param $d date to compare with
	 * @return 0 if the argument date is equal to this date, -1 if this date
	 *         is before the date argument, and 1 if this date is after the
	 *         date argument
	 */
	public function compare_to(Date $d) {
	
        if ($this->y > $d->y) return 1; elseif ($this->y < $d->y) return -1;
		if ($this->m > $d->m) return 1; elseif ($this->m < $d->m) return -1;
		if ($this->d > $d->d) return 1; elseif ($this->d < $d->d) return -1;
		if ($this->h > $d->h) return 1; elseif ($this->h < $d->h) return -1;
		if ($this->i > $d->i) return 1; elseif ($this->i < $d->i) return -1;
		if ($this->s > $d->s) return 1; elseif ($this->s < $d->s) return -1;
		
		return 0;
	    		
	}
	
	public function equals(Date $d) {
	    return $this->compare_to($d) == 0;
	}
	
	/**
	 * Add an interval to this date, returning the resultant date.
	 *
	 * @todo this method has no awareness of daylight savings or any other
	 *       Gregorian logic bombs.
	 *
	 * @param $interval interval to add to this date, expressed as either a
	 *        <tt>Date_Interval</tt> instance, or a valid interval string.
	 * @return resultant <tt>Date</tt> or <tt>Date_Time</tt> instance.
	 */
	public function add($interval) {
	
	    if (!($interval instanceof Date_Interval)) {
	        $interval = new Date_Interval($interval);
	    }
	    
	    $months = $this->y * 12 + $this->m - 1 + $interval->total_months();

		$y = floor($months / 12);
		$m = ($months % 12) + 1;

		$d = min($this->day(), self::days_for_month($m, $y));
        $h = $this->hour();
        $i = $this->minute();
        $s = $this->second();

		$days_to_add = $interval->days();
		$month_days = self::days_for_month($m, $y);
		
		while ($days_to_add > 0) {
			$d++; $days_to_add--;
			if ($d > $month_days) {
				$d = 1; $m++;
				if ($m == 13) {
					$m = 1; $y++;
				}
				$month_days = self::days_for_month($m, $y);
			}
		}
		
		while ($days_to_add < 0) {
			$d--; $days_to_add++;
			if ($d == 0) {
				$m--;
				if ($m == 0) {
					$m = 12; $y--;
				}
				$d = $month_days = self::days_for_month($m, $y);
			}
		}
		
		//
		// Implementation relies on fact that interval's hours(), minutes()
		// and seconds() methods can never return more than 23, 59 and 59.
		// Basically, there's no chance that the month could ever change
		// by more than ±1
		
		$s += $interval->hours() * 3600 +
		      $interval->minutes() * 60 +
 			  $interval->seconds();

		if ($s > 0) {
			$i += floor($s / 60); $s %= 60;
			$h += floor($i / 60); $i %= 60;
			$d += floor($h / 24); $h %= 24;
			if ($d > $month_days) {
				$d -= $month_days;
				$m++;
				if ($m == 13) {
					$m = 1; $y++;
				}
			}
		} elseif ($s < 0) {
			list($s, $i) = $this->do_the_mod_thing($s, $i, 60);
			if ($i < 0) {
				list($i, $h) = $this->do_the_mod_thing($i, $h, 60);
				if ($h < 0) {
					list($h, $d) = $this->do_the_mod_thing($h, $d, 24);
					if ($d < 1) {
						$m--;
						if ($m == 0) {
							$m = 12; $y--;
						}
						$d = self::days_for_month($m, $y) + $d;
					}
				}
			}
		}
		
		return $this->create_new($y, $m, $d, $h, $i, $s);
	    
    }

	private function do_the_mod_thing($s, $l, $base) {
		$s = abs($s);
		$l -= floor($s / $base);
		$s %= $base;
		if ($s > 0) {
			$l--;
			$s = $base - $s;
		}
		return array($s, $l);
	}

	public function subtract($interval) {
	
	    if (!($interval instanceof Date_Interval)) {
	        $interval = new Date_Interval($interval);
	    }
	    
	    return $this->add($interval->negate());
	
	}
	
	protected function create_new($y, $m, $d, $h, $i, $s) {
	    $class = get_class($this);
	    return new $class($y, $m, $d, $h, $i, $s);
	}
	
	public function midnight() {
	    return $this->create_new($this->year(), $this->month(), $this->day(), 0, 0, 0);
	}
	
	public function at_beginning_of_month() {
	    return $this->create_new($this->year(), $this->month(), 1, 0, 0, 0);
	}
	
	public function at_beginning_of_week() {
        $diff = $this->weekday() - 1;
        if ($diff > 0) {
            return $this->subtract($diff . 'D');
        } else {
            return $this;
        }        
	}
	
	public function weekday() {
		
		if ($this->month < 3) {
			$m = $this->month + 12;
			$y = $this->year - 1;
		} else {
			$m = $this->month;
			$y = $this->year;
		}
		
		$ret = ($this->d +
			    (2 * $m) +
			    floor(6 * ($m + 1) / 10) +
			    $y +
			    floor($y / 4) -
			    floor($y / 100) +
			    floor($y / 400)) % 7;
			
		return $this->adjust_day($ret);
		
	}
	
	public function leap_year() {
		return self::is_leap_year($this->y);
	}
	
	public function days_in_month() {
		return self::days_for_month($this->m, $this->y);
	}
	
	private function adjust_day($d) {
		return $this->real_mod($d - DATE_WEEK_START, 7) + 1 + DATE_WEEK_OFFSET;
	}
	
	private function real_mod($k, $m) {
		$v = $k % $m;
		if ($v < 0) $v += $m;
		return $v;
	}
	
	public function to_params() {
	    return array('year'     => $this->y,
	                 'month'    => $this->m,
	                 'day'      => $this->d);
	}
}
?>