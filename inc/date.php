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
    //
    
    private static $default_timezone = null;
    
    public static function default_timezone() {
        if (self::$default_timezone === null) {
            self::$default_timezone = new DateTimeZone(date_default_timezone_get());
        }
        return self::$default_timezone;
    }
    
    public static function parse() {
        
    }
    
    //
    //
    
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
    
    //
    //
    
    protected $y, $m, $d, $h, $i, $s;
    protected $timezone;
    
    protected $utc;
    protected $weekday = null;
    protected $unix = null;
    
    public function __construct($y, $m, $d, $tz = null) {
        $this->set_timezone($tz);
        $this->set_date($y, $m, $d);
        $this->set_time(0, 0, 0);
    }
    
    //
    // Components
    
    public function year() { return $this->y; }
    public function month() { return $this->m; }
    public function day() { return $this->d; }
    public function hours() { return $this->h; }
    public function minutes() { return $this->i; }
    public function seconds() { return $this->s; }
    public function timezone() { }
    
    public function weekday() {
        
        if ($this->weekday === null) {
            
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

            $this->weekday = $this->adjust_day($ret);
            
        }
        
        return $this->weekday;

    }

    public function leap_year() {
        return self::is_leap_year($this->y);
    }

    public function days_in_month() {
        return self::days_for_month($this->m, $this->y);
    }
    
    public function is_utc() {
        return $this->utc;
    }
    
    public function timestamp() {
        if ($this->unix === null) {
            if ($this->y < 1970) throw new IllegalStateException("can't get timestamps for dates before 1970");
            
        }
        return $this->unix;
    }
    
    //
    // Conversions
    
    public function format($format) {
        
    }
    
    public function to_utc() {
        if ($this->is_utc()) {
            return $this;
        } else {
            // TODO: 
        }
    }
    
    public function to_date() {
        return $this;
    }
    
    public function to_date_time() {
        return new Date_Time($this->y, $this->m, $this->d, 0, 0, 0);
    }
     
    //
    // Relative dates/times
    
    public function midnight() {
        return $this->create_new($this->year(), $this->month(), $this->day(), 0, 0, 0);
    }

    public function beginning_of_month() {
        return $this->create_new($this->year(), $this->month(), 1, 0, 0, 0);
    }

    public function beginning_of_week() {
        $diff = $this->weekday() - 1;
        if ($diff > 0) {
            return $this->subtract($diff . 'D'); // TODO
        } else {
            return $this;
        }        
    }
    
    //
    // Comparisons
    
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
        
        $l = $this->to_utc();
        $r = $d->to_utc();

        if ($l->y > $r->y) return 1; elseif ($l->y < $r->y) return -1;
        if ($l->m > $r->m) return 1; elseif ($l->m < $r->m) return -1;
        if ($l->d > $r->d) return 1; elseif ($l->d < $r->d) return -1;
        if ($l->h > $r->h) return 1; elseif ($l->h < $r->h) return -1;
        if ($l->i > $r->i) return 1; elseif ($l->i < $r->i) return -1;
        if ($l->s > $r->s) return 1; elseif ($l->s < $r->s) return -1;
 
        return 0;
         
    }

    public function equals(Date $d) {
        return $this->compare_to($d) == 0;
    }
    
    //
    //
    
    public function add() {
        
    }
    
    public function subtract() {
        
    }
    
    public function diff() {
        
    }
    
    //
    // Helpers
    
    protected function set_timezone($tz) {
        if ($tz === null) {
            $tz = self::default_timezone();
        } elseif (is_string($tz)) {
            $tz = new DateTimeZone($tz);
        }
        if (($tz instanceof DateTimeZone)) {
            $this->timezone = $tz;
        } else {
            throw new IllegalArgumentException("invalid timezone");
        }
        $this->utc = $this->timezone->getName() == 'UTC';
    }
    
    protected function set_date($y, $m, $d) {
 
        if (!checkdate($m, $d, $y)) {
            throw new IllegalArgumentException("invalid date: $y-$m-$d");
        }
 
        $this->y = (int) $y;
        $this->m = (int) $m;
        $this->d = (int) $d;
 
    }
     
    protected function set_time($h, $i, $s) {
        if ($h == 24 && $i == 0 && $s == 0) $h = 0;
        if ($h < 0 || $h > 23 || $i < 0 || $i > 59 || $s < 0 || $s > 59) {
            throw new IllegalArgumentException("invalid time: $h:$i:$s");
        }
        $this->h = $h;
        $this->i = $i;
        $this->s = $s;
    }
    
    protected function create_new($y, $m, $d, $h, $i, $s, $tz = null) {
        return new Date($y, $m, $d, $tz);
    }
    
    // Takes a weekday number and converts it based on configuration
    protected function adjust_day($d) {
        
        $k = $d - DATE_WEEK_START;
        $v = $k % 7;
        if ($v < 0) $v += 7;
        
        return $v + 1 + DATE_WEEK_OFFSET;
        
    }
}

class Date_Time extends Date
{
    public function __construct($y, $m, $d, $h, $i, $s, $tz = null) {
        $this->set_timezone($tz);
        $this->set_date($y, $m, $d);
        $this->set_time($h, $i, $s);
    }
    
    public function to_date() {
        return new Date($this->y, $this->m, $this->d);
    }
    
    public function to_date_time() {
        return $this;
    }
    
    protected function create_new($y, $m, $d, $h, $i, $s, $tz = null) {
        return new Date_Time($y, $m, $d, $h, $i, $s, $tz);
    }
}



// 
// /**
//  * Date class
//  *
//  * @todo add method for returning interval between two dates (in terms of months)
//  * @todo add method for returning interval between two dates (in terms of seconds)
//  *
//  * @package BasePHP
//  * @author Jason Frame
//  */
// class Date
// {
//     public static function date_for($d) {
//         if ($d === null) {
//             return null;
//         } elseif ($d instanceof Date) {
//             return $d->to_date();
//         } else {
//             return new Date($d);
//         }
//     }
//     
//     public static function datetime_for($d) {
//         if ($d === null) {
//             return null;
//         } elseif ($d instanceof Date) {
//             return $d->to_datetime();
//         } else {
//             return new Date_Time($d);
//         }
//     }
// 

//  
//  public function __construct() {
//  
//      $x = func_num_args();
//      
//      // 0 args; current time and date
//      if ($x == 0) {
//          
//          $this->from_unix(time());
//          
//      // 1 arg - could either be:
//      // ISO8601
//      // Component array
//      // UNIX timestamp
//      } elseif ($x == 1) {
//          
//          $a = func_get_arg(0);
//          
//          // If it's a number we'll assume it's a unix timestamp
//          if (is_numeric($a)) {
//              
//              $this->from_unix($a);
//              
//          // Attempt to parse any other scalar as an ISO-8601 date string (sans timezone) or date string
//          } elseif (is_scalar($a)) {
//              
//              if (preg_match(self::ISO_8601, $a, $m)) {
//                  $this->set_date($m[1], $m[2], $m[3]);
//                  if (isset($m[4])) {
//                      $this->set_time($m[6], $m[7], $m[8]);
//                  }
//              } elseif ($unix = strtotime($a)) {
//                  $this->from_unix($unix);
//              } else {
//                  throw new Error_IllegalArgument;
//              }
// 
//          // Component array
//          // We accept either a 3 element array (which will set the date), or
//          // a >= 5 element array (which will set the time as well, defaulting
//          // the seconds fields to 0)
//          } elseif (is_array($a) && isset($a[0]) && count($a) >= 1) {
//              
//              if (count($a) == 1) {
//                  array_push($a, 1, 1, 0, 0, 0);
//              } elseif (count($a) == 2) {
//                  array_push($a, 1, 0, 0, 0);
//              } elseif (count($a) < 6) {
//                  array_push($a, 0, 0, 0);
//              }
//              
//              $this->set_date($a[0], $a[1], $a[2]);
//              $this->set_time($a[3], $a[4], $a[5]);
//          
//          // Associative array    
//          } elseif (is_array($a)) {
//              
//              $this->set_date(isset($a['year']) ? $a['year'] : date('Y'),
//                              isset($a['month']) ? $a['month'] : 1,
//                              isset($a['day']) ? $a['day'] : 1);
//                              
//              $this->set_time(isset($a['hour']) ? $a['hour'] : 0,
//                              isset($a['minute']) ? $a['minute'] : 0,
//                              isset($a['second']) ? $a['second'] : 0);
//              
//          // Any other single argument is invalid
//          } else {
//              throw new Error_IllegalArgument("Error creating date instance - I couldn't work out what format you wanted");
//          } 
//          
//      // 3 args - explicit year, month, day
//      } elseif ($x == 3) {
//          $y = func_get_args();
//          $this->set_date($y[0], $y[1], $y[2]);
//          
//      // 5/6 args - explicit year, month, day, hours, minutes [, seconds]
//      } elseif ($x == 5 || $x == 6) {
//          $y = func_get_args();
//          $this->set_date($y[0], $y[1], $y[2]);
//          $this->set_time($y[3], $y[4], isset($y[5]) ? $y[5] : 0);
//          
//      } else {
//          throw new Error_IllegalArgument("Error creating date instance");
//      }
//      
//  }
//  

?>