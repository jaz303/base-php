<?php
/**
 * BasePHP's Date/Date_Time classes wrap around PHP's own DateTime implementation whose
 * API, IMO, is ghastly.
 *
 * Main differences:
 *   1. This implementation is immutable
 *   2. Accessor methods for component parts
 *   3. Distinction between date and date-and-time 
 *
 * @todo add/subtract intervals
 * @todo get interval between two dates
 */
class Date
{
    //
    // These have just been lifted from PHP's Date_Time
    
    const ATOM     = 'Y-m-d\TH:i:sP';
    const COOKIE   = 'l, d-M-y H:i:s T';
    const ISO8601  = 'Y-m-d\TH:i:sO';
    const RFC822   = 'D, d M y H:i:s O';
    const RFC850   = 'l, d-M-y H:i:s T';
    const RFC1036  = 'D, d M y H:i:s O';
    const RFC1123  = 'D, d M Y H:i:s O';
    const RFC2822  = 'D, d M Y H:i:s O';
    const RFC3339  = 'Y-m-d\TH:i:sP';
    const RSS      = 'D, d M Y H:i:s O';
    const W3C      = 'Y-m-d\TH:i:sP';
    
    //
    // Some variations
    
    const ISO8601_DATE                      = 'Y-m-d';
    const ISO8601_DATE_TIME                 = 'Y-m-d\TH:i:s';
    const ISO8601_DATE_TIME_WITH_TIMEZONE   = 'Y-m-d\TH:i:sO';
    
    //
    // Default
    
    private static $default_timezone    = null;
    private static $utc_timezone        = null;
    
    public static function default_timezone() {
        if (self::$default_timezone === null) {
            self::$default_timezone = new DateTimeZone(date_default_timezone_get());
        }
        return self::$default_timezone;
    }
    
    public static function utc_timezone() {
        if (self::$utc_timezone === null) {
            self::$utc_timezone = new DateTimeZone('UTC');
        }
        return self::$utc_timezone;
    }
    
    //
    // Request parsing
    
    public static function from_request($value) {
        $class = get_called_class();
        try {
            if (empty($value)) { // nothing submitted
                return null;
            } elseif (is_array($value)) {
                if (isset($value['year'])) {
                    if (isset($value['month']) && isset($value['day'])) {
                        $args = array($value['year'], $value['month'], $value['day']);
                        if (isset($value['hours']) && isset($value['minutes']) && isset($value['seconds'])) {
                            $args[] = $value['hours'];
                            $args[] = $value['minutes'];
                            $args[] = $value['seconds'];
                            if (isset($value['timezone'])) {
                                $args[] = $value['timezone'];
                            }
                        }
                        return new $class($args);
                    } else {
                        return null;
                    }
                } else {
                    return new $class($value);
                }
            } else { // assume string
                if (is_numeric($value)) $value = '@' . $value; // unix timestamp
                return new $class($value);
            }
        } catch (InvalidArgumentException $e) {
            return null;
        }
    }
    
    //
    // Safe parsing anywhere else
    
    public static function parse_date($value) {
        if ($value === null) {
            return null;
        } elseif ($value instanceof Date_Time) {
            return $value->to_date();
        } elseif ($value instanceof Date) {
            return $value;
        } else {
            try {
                return new Date($value);
            } catch (Exception $e) {
                return null;
            }
        }
    }
    
    public static function parse_date_time($value) {
        if ($value === null) {
            return null;
        } elseif ($value instanceof Date_Time) {
            return $value;
        } elseif ($value instanceof Date) {
            return $value->to_date_time();
        } else {
            try {
                return new Date_Time($value);
            } catch (Exception $e) {
                return null;
            }
        }
    }
    
    //
    // Serialization
    
    public function __sleep() {
        return array('y', 'm', 'd', 'h', 'i', 's', 'timezone_name');
    }
    
    public function __wakeup() {
        $this->set_timezone($this->timezone_name);
        $this->generate_native();
    }
    
    // Native DateTime instance
	protected $native = null;
	
	protected $y, $m, $d, $h, $i, $s;
	protected $timezone, $timezone_name;
	
	public function __construct($args = null) {
	    if (!is_array($args)) $args = func_get_args();
	    switch (func_num_args()) {
			case 0: // now
			    $this->set_native(new DateTime);
			    break;
			case 1: // native DateTime object or string
			    if ($args[0] instanceof DateTime) {
			        $this->set_native($args[0]);
			    } else {
			        $this->set_native(new DateTime($args[0]));
			    }
			    break;
			case 2: // string/null + timezone
			    $this->set_native(new DateTime($args[0], $args[1]));
			    break;
			case 3: // y/m/d
			    $this->set_date($args[0], $args[1], $args[2]);
			    $this->set_timezone(null);
			    break;
			case 4: // y/m/d + timezone
			    $this->set_date($args[0], $args[1], $args[2]);
			    $this->set_timezone($args[3]);
			    break;
			case 6: // y/m/d/h/i/s
			    $this->set_date($args[0], $args[1], $args[2]);
			    $this->set_time($args[3], $args[4], $args[5]);
			    $this->set_timezone(null);
			    break;
			case 7: // y/m/d/h/i/s + timezone
    		    $this->set_date($args[0], $args[1], $args[2]);
    		    $this->set_time($args[3], $args[4], $args[5]);
			    $this->set_timezone($args[6]);
			default:
				throw new InvalidArgumentException();
		}
		
		if ($this->native === null) {
		    $this->generate_native();
		} else {
		    $this->y = (int) $this->native->format('Y');
		    $this->m = (int) $this->native->format('m');
		    $this->d = (int) $this->native->format('d');
		    $this->set_time($this->native->format('H'),
		                    $this->native->format('i'),
		                    $this->native->format('s'));
		    $this->timezone = $this->native->getTimezone();
		}
		
	}
	
	public function year() { return $this->y; }
    public function month() { return $this->m; }
    public function day() { return $this->d; }
    public function hours() { return $this->h; }
    public function minutes() { return $this->i; }
    public function seconds() { return $this->s; }
    public function timezone() { return $this->timezone; }
    
    public function weekday() { return (int) $this->native->format('w'); }
    public function is_leap_year() { return (bool) $this->native->format('L'); }
    public function days_in_month() { return (int) $this->native->format('t'); }
    public function is_utc() { return $this->timezone->getName() == 'UTC'; }
    public function timestamp() { return $this->native->getTimestamp(); }
    public function format($f) { return $this->native->format($f); }
    
    public function iso_date() { return $this->native->format(self::ISO8601_DATE); }
    public function iso_date_time() { return $this->native->format(self::ISO8601_DATE_TIME); }
    public function iso_date_time_with_timezone() { return $this->native->format(self::ISO8601_DATE_TIME_WITH_TIMEZONE); }
    
    public function to_date() { return $this; }
    public function to_date_time() { return new Date_Time($this->native); }
    
    public function to_timezone($tz) {
        $tz = is_string($tz) ? new DateTimeZone($tz) : $tz;
        if ($tz->getName() == $this->timezone->getName()) {
            return $this;
        } else {
            $dt = clone $this->native;
            $dt->setTimezone($tz);
            $class = get_class($this);
            return new $class($dt);
        }
    }
    
    public function to_utc() { return $this->to_timezone(self::utc_timezone()); }
    
    /**
    * Compares this date with another.
    * There's probably a better algorithm for this.
    *
    * @todo this should perform timezone conversions!!!
    *       (at the moment it's broken if you compare two values with different timezones)
    *
    * @param $r date to compare with
    * @return 0 if the argument date is equal to this date, -1 if this date
    *         is before the date argument, and 1 if this date is after the
    *         date argument
    */
    public function compare_to(Date $r) {
        
        $l = $this;
        $r = $r->to_timezone($this->timezone);
        
        if ($l->y > $r->y) return 1; elseif ($l->y < $r->y) return -1;
        if ($l->m > $r->m) return 1; elseif ($l->m < $r->m) return -1;
        if ($l->d > $r->d) return 1; elseif ($l->d < $r->d) return -1;
        if ($l->h > $r->h) return 1; elseif ($l->h < $r->h) return -1;
        if ($l->i > $r->i) return 1; elseif ($l->i < $r->i) return -1;
        if ($l->s > $r->s) return 1; elseif ($l->s < $r->s) return -1;
 
        return 0;
         
    }
    
    protected function set_native(DateTime $dt) {
        $dt->setTime(0, 0, 0);
        $this->native = $dt;
    }
    
    protected function generate_native() {
        $this->native = new DateTime(
	        sprintf(
	            "%04d-%02d-%02dT%02d:%02d:%02d",
	            $this->y, $this->m, $this->d,
	            $this->h, $this->i, $this->s
	        ),
	        $this->timezone
	    );
    }
    
    protected function set_date($y, $m, $d) {
        if (!checkdate($m, $d, $y)) {
            throw new InvalidArgumentException("invalid date: $y-$m-$d");
        }
        $this->y = (int) $y;
        $this->m = (int) $m;
        $this->d = (int) $d;
    }
    
    protected function set_time($h, $i, $s) {
        $this->h = 0;
        $this->i = 0;
        $this->s = 0;
    }
    
    protected function set_timezone($tz) {
        
        if ($tz === null) {
            $tz = self::default_timezone();
        } elseif (is_string($tz)) {
            $tz = new DateTimeZone($tz);
        }
        
        if (($tz instanceof DateTimeZone)) {
            $this->timezone = $tz;
            $this->timezone_name = $tz->getName();
        } else {
            throw new InvalidArgumentException("invalid timezone");
        }
        
    }
}

class Date_Time extends Date
{
	public function to_date() { return new Date($this->native); }
    public function to_date_time() { return $this; }
    
    protected function set_native(DateTime $dt) {
        $this->native = $dt;
    }
    
    protected function set_time($h, $i, $s) {
        if ($h == 24 && $i == 0 && $s == 0) $h = 0;
        if ($h < 0 || $h > 23 || $i < 0 || $i > 59 || $s < 0 || $s > 59) {
            throw new InvalidArgumentException("invalid time: $h:$i:$s");
        }
        $this->h = (int) $h;
        $this->i = (int) $i;
        $this->s = (int) $s;
    }
}
?>