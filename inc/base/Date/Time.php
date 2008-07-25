<?php
class Date_Time extends Date
{
    // FTHAX. 
  	protected function really_set_time($h, $i, $s) {
	    $this->h = (int) $h;
	    $this->i = (int) $i;
	    $this->s = (int) $s;
	}
  	
  	//
	// Conversions
	
	public function to_date() {
	    return new Date($this->y, $this->m, $this->d);
	}
	
	public function to_datetime() {
        return $this;
	}
	
	public function to_iso() {
	    return $this->to_iso_datetime();
	}
	
	public function to_params() {
	    return array('year'     => $this->y,
	                 'month'    => $this->m,
	                 'day'      => $this->d,
	                 'hour'     => $this->h,
	                 'minute'   => $this->i,
	                 'second'   => $this->s);
	}
}
?>