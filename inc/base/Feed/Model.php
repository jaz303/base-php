<?php
/**
 * Usage:
 * $feed = new Feed;
 * $feed->title('My feed')
 *      ->link('http://onehackoranother.com')
 *      ->copyright('2008 Jason Frame');
 * $feed->add('My first post', 'blah blah blah', new Date_Time);
 * $feed->display_atom();
 */
class Feed implements IteratorAggregate, Countable
{
    // You get a magic public method for each of these attributes
    // Call with no params to retrieve the value, or with one
    // param to set new value and return $this (i.e. chainable)
    private $title              = null;
    private $link               = null;
    private $copyright          = null;
    private $description        = null;
    private $date               = null;
    private $language           = null;
    
    private $items              = array();
    
    public function __call($method, $args) {
        if (property_exists($this, $method)) {
            $param = count($args) ? $args[0] : null;
            if ($param === null) {
                return $this->$method;
            } else {
                if ($method == 'date') {
                    $param = $param instanceof Date ? $param : new Date_Time($param);
                }
                $this->$method = $param;
                return $this;
            }
        } else {
            throw new Error_MethodMissing;
        }
    }
    
    public function add($title = null, $description = null, $date = null, $link = null) {
        
        if ($title instanceof Feed_RSS_Item) {
            
            $item = $title;
        
        } elseif (is_array($title)) {
            
            $item = new Feed_RSS_Item;
            foreach ($item as $k => $v) $item->$k($v);
        
        } else {
            
            $item = new Feed_RSS_Item;
            if ($title !== null)        $item->title($title);
            if ($description !== null)  $item->description($description);
            if ($date !== null)         $item->date($date);
            if ($link !== null)         $item->link($link);

        }
        
        $this->items[] = $item;
        return $item;
        
    }
    
    public function to_rss() {
        
    }
    
    public function display_rss() {
        
    }
    
    public function to_atom() {
        
    }
    
    public function display_atom() {
        
    }
    
    public function items() {
        return $this->items;
    }
    
    public function getIterator() {
        return new ArrayIterator($this->items);
    }
    
    public function count() {
        return count($this->items);
    }
}

class Feed_Item
{
    private $title              = null;
    private $link               = null;
    private $description        = null;
    private $date               = null;
    private $guid               = null;
    private $permalink          = true;
    
    private $categories         = array();
    
    public function __call($method, $args) {
        if (property_exists($this, $method)) {
            $param = count($args) ? $args[0] : null;
            if ($param === null) {
                return $this->$method;
            } else {
                if ($method == 'date') {
                    $param = $param instanceof Date ? $param : new Date_Time($param);
                }
                $this->$method = $param;
                return $this;
            }
        } else {
            throw new Error_MethodMissing;
        }
    }

    public function guid($g = null, $permalink = true)  {
        if ($g === null) {
            return $this->guid;
        } else {
            $this->guid = $g;
            $this->permalink((bool) $permalink);
            return $this;
        }
    }

    public function add_category($category) {
        $this->categories[] = $category;
        return $this;
    }
    
    public function categories($categories = null) {
        if ($categories === null) {
            return $this->categories;
        } elseif (!is_array($categories)) {
            $this->categories = func_get_args();
        } else {
            $this->categories = $categories;
        }
        return $this;
    }
}
?>