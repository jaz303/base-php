<?php
class Date_Range
{
    private $negative;
    private $from;
    private $to;
    
    /**
     * Create a new Date_Range instance.
     * 
     * @param $from start date for this range
     * @param $to end date for this range
     */
    public function __construct(Date $from, Date $to) {
        $this->negative = $from->compare_to($to) > 0;
        $this->from     = $from;
        $this->to       = $to;
    }
    
    /**
     * Returns a representation of this date range such that the 'from' date is
     * the earlier of the two dates.
     */
    public function normalize() {
        return $this->negative ? new Date_Range($this->to, $this->from) : $this;
    }
    
    public function start() { return $this->from; }
    public function end() { return $this->to; }
    
    public function earliest() { return $this->negative ? $this->to : $this->from; }
    public function latest() { return $this->negative ? $this->from : $this->to; }
    
    /** Inclusive test */
    public function contains(Date $d) {
        $cmp1 = $d->compare_to($this->earliest());
        $cmp2 = $d->compare_to($this->latest());
        return $cmp1 >= 0 && $cmp2 <= 0;
    }
    
    /** Exclusive test */
    public function _contains_(Date $d) {
        $cmp1 = $d->compare_to($this->earliest());
        $cmp2 = $d->compare_to($this->latest());
        return $cmp1 > 0 && $cmp2 < 0;
    }
    
    /** End-exclusive test */
    public function contains_(Date $d) {
        $cmp1 = $d->compare_to($this->from);
        $cmp2 = $d->compare_to($this->to);
        return $this->negative
               ? ($cmp1 <= 0 && $cmp2 > 0)
               : ($cmp1 >= 0 && $cmp2 < 0);
    }
    
    /** Start-exclusive test */
    public function _contains(Date $d) {
        $cmp1 = $d->compare_to($this->from);
        $cmp2 = $d->compare_to($this->to);
        return $this->negative
               ? ($cmp1 < 0 && $cmp2 >= 0)
               : ($cmp1 > 0 && $cmp2 <= 0);
    }
}