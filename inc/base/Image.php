<?php
class Error_UnsupportedImageType extends Exception {}

/**
 * Image class - simple chainable wrapper around GD.
 *
 * Quick usage example:
 *
 * $i = new Image('test.png');
 * $i->type('jpg')->constrain(160, 90)->grayscale()->output();
 */
class Image
{
    private $gd         = null;
    private $type       = null;
    private $filename   = null;
    private $parent     = null;
    
    public function __construct($arg1, $arg2 = null) {
        if ($arg2 && is_numeric($arg1) && is_numeric($arg2)) {
            $this->gd = $this->create_new_image($arg1, $arg2);
        } elseif ($arg1 instanceof Image) {
            $this->gd = $arg1->raw_copy();
            $this->type = $arg1->type;
            $this->parent = $arg1;
        } elseif (file_exists($arg1)) {
            if ($info = @getimagesize($arg1)) {
                $func = $this->image_type_to_constructor_function($info[2]);
                $this->gd = $func($arg1);
                $this->type = $info[2];
                $this->filename = $arg1;
            } else {
                throw new Error_UnsupportedImageType;
            }
        } else {
            if (!$this->gd = imagecreatefromstring($arg1)) {
                throw new Error_UnsupportedImageType("Couldn't recognise format of image string");
            }
            $this->type($arg2 === null ? 'jpg' : $arg2);
        }
    }
    
    public function __destruct() {
        if ($this->gd !== null) {
            imagedestroy($this->gd);
        }
    }
    
    //
    // Types
    
    public function type($new_type = null) {
        if ($new_type === null) {
            return $this->type;
        } elseif (is_numeric($new_type)) {
            $this->type = $new_type;
        } else {
            $this->type = $this->string_to_image_type($new_type);
        }
        return $this;
    }
    
    private function string_to_image_type($string) {
        switch ($string) {
            case 'gif':     return IMAGETYPE_GIF;
            case 'jpg':
            case 'jpeg':    return IMAGETYPE_JPEG;
            case 'png':     return IMAGETYPE_PNG;
            default:        throw new Error_UnsupportedImageType;
        }
    }
    
    private function image_type_to_constructor_function($type) {
        switch ($type) {
            case IMAGETYPE_GIF: return 'imagecreatefromgif';
            case IMAGETYPE_PNG: return 'imagecreatefrompng';
            case IMAGETYPE_JPEG: return 'imagecreatefromjpeg';
            default: throw new Error_UnsupportedImageType;
        }
    }
    
    private function image_type_to_output_function($type) {
        switch ($type) {
            case IMAGETYPE_GIF: return 'imagegif';
            case IMAGETYPE_PNG: return 'imagepng';
            case IMAGETYPE_JPEG: return 'imagejpeg';
            default: throw new Error_UnsupportedImageType;
        }
    }
    
    //
    // Clones
    
    /**
     * Returns a clone of this image. The clone will be linked to $this image,
     * so it is possible to revert to $this during chained calls by calling
     * back()
     */
    public function dup() {
        return new Image($this);
    }
    
    /**
     * Returns the 'parent', defined as the image which created $this image
     * as a result of a call to dup()
     *
     * Usage:
     * $i = new Image('foo.jpg')
     * $i->dup()->constrain(100)->save('thumbnail.jpg')->back()->constrain(640)->output()
     */
    public function back() {
        if ($this->parent === null) {
            throw new Error_IllegalState("Can't go back - no parent image");
        }
        return $this->parent;
    }
    
    //
    // Output stuff
    
    private function get_output_function() {
        return $this->image_type_to_output_function($this->type);
    }
    
    private function prepare_for_output() {
        // This doesn't seem to be necessary:
        // if ($this->type == IMAGETYPE_GIF && $this->is_true_color()) {
        //     imagetruecolortopalette($this->gd, false, 255);
        // }
        return $this->gd;
    }
    
    private function normalise_quality($q) {
        if ($q === null) return null;
        if ($this->type == IMAGETYPE_JPEG) {
            return $q;
        } elseif ($this->type == IMAGETYPE_PNG) {
            return min(9, floor($q / 10));
        } else {
            return null;
        }
    }
    
    private function dump($filename, $quality) {
        $func = $this->get_output_function();
        $gd = $this->prepare_for_output();
        if ($quality !== null) {
            $func($gd, $filename, $this->normalise_quality($quality));
        } else {
            $func($gd, null);
        }
        if ($gd != $this->gd) imagedestroy($gd);
    }
    
    public function data($quality = null) {
        ob_start();
        $this->dump(null, $quality);
        return ob_get_clean();
    }
    
    public function output($quality = null) {
        header("Content-Type: " . $this->mime_type());
        $this->dump(null, $quality);
    }
    
    public function save($filename = null, $quality = null) {
        if ($filename) $this->filename = $filename;
        if ($this->filename === null) {
            // TODO: throw error
        }
        $this->dump($this->filename, $quality);
        return $this;
    }
    
    public function mime_type() {
        return image_type_to_mime_type($this->type);
    }
    
    public function get_width() { return imagesx($this->gd); }
    public function get_height() { return imagesy($this->gd); }
    public function is_true_color() { return imageistruecolor($this->gd); }
    
    //
    // Cropping/Resizing
    
    public function crop($x, $y, $w, $h) {
		$this->resample($x, $y, $w, $h, $w, $h);
		return $this;
	}
	
	public function auto_crop($new_width, $new_height, $gravity = 'c') {
	    
	    $gravity = ' ' . $gravity;
	    
	    $width = $this->get_width();
	    $height = $this->get_height();
	    
		if ($new_width > $width) $new_width = $width;
		if ($new_height > $height) $new_height = $height;
		
		if (strpos($gravity, 'w')) {
			$x = 0;
		} elseif (strpos($gravity, 'e')) {
			$x = $width - $new_width;
		} else {
			$x = ($width - $new_width) / 2;
		}
		
		if (strpos($gravity, 'n')) {
			$y = 0;
		} elseif (strpos($gravity, 's')) {
			$y = $height - $new_height;
		} else {
			$y = ($height - $new_height) / 2;
		}
		
		$this->resample($x, $y, $new_width, $new_height, $new_width, $new_height);
		
		return $this;
		
	}
	
	public function constrain($max_w = null, $max_h = null) {
		$d = $this->constrained_size($max_w, $max_h);
		return $this->resize($d[0], $d[1]);
	}
	
	public function resize($w = null, $h = null) {
	    if ($w === null) $w = $this->get_width();
	    if ($h === null) $h = $this->get_height();
		$this->resample(0, 0, $this->get_width(), $this->get_height(), $w, $h);
		return $this;
	}
    
    public function scale($percent) {
        $s = $percent / 100;
		$w = floor($s * $this->get_width());
		$h = floor($s * $this->get_height());
		return $this->resize($w, $h);
    }
    
    private function constrained_size($max_w, $max_h) {
        
        if ($max_h === null) $max_h = $max_w;
		
		$w = $this->get_width();
		$h = $this->get_height();
		
		if ($max_w !== null && $w > $max_w) {
			$s = $max_w / $w;
			$w = $max_w;
			$h = floor($s * $h);
		}
	
		if ($max_h !== null && $h > $max_h) {
			$s = $max_h / $h;
			$h = $max_h;
			$w = floor($s * $w);
		}
		
		return array($w, $h);
	
	}
	
	private function resample($x, $y, $w, $h, $nw, $nh) {
		$nw = (int) $nw;
		$nh = (int) $nh;
		$dst = $this->create_new_image($nw, $nh);
		imagecopyresampled($dst, $this->gd, 0, 0, $x, $y, $nw, $nh, $w, $h);
        imagedestroy($this->gd);
        $this->gd = $dst;
	}
    
    //
    // Filtering
    
    public function correct_gamma($input_gamma, $output_gamma) {
        imagegammacorrect($this->gd, $input_gamma, $output_gamma);
    }
    
    public function convolve(array $matrix, $div, $offset) {
        imageconvolution($this->gd, $matrix, $div, $offset);
    }
    
    public function negate() {
        imagefilter($this->gd, IMG_FILTER_NEGATE);
        return $this;
    }
    
    public function grayscale() {
        imagefilter($this->gd, IMG_FILTER_GRAYSCALE);
        return $this;
    }
    
    public function adjust_brightness($level) {
        imagefilter($this->gd, IMG_FILTER_BRIGHTNESS, $level);
        return $this;
    }
    
    public function adjust_contrast($level) {
        imagefilter($this->gd, IMG_FILTER_CONTRAST, $level);
        return $this;
    }
    
    public function colorize($r, $g, $b, $a = 0) {
        imagefilter($this->gd, IMG_FILTER_COLORIZE, $r, $g, $b, $a);
        return $this;
    }
    
    public function edge_detect() {
        imagefilter($this->gd, IMG_FILTER_EDGEDETECT);
        return $this;
    }
    
    public function emboss() {
        imagefilter($this->gd, IMG_FILTER_EMBOSS);
        return $this;
    }
    
    public function blur() {
        imagefilter($this->gd, IMG_FILTER_GAUSSIAN_BLUR);
        return $this;
    }
    
    public function selective_blur() {
        imagefilter($this->gd, IMG_FILTER_SELECTIVE_BLUR);
        return $this;
    }
    
    public function mean_removal() {
        imagefilter($this->gd, IMG_FILTER_MEAN_REMOVAL);
        return $this;
    }
    
    public function smooth($level) {
        imagefilter($this->gd, IMG_FILTER_SMOOTH, $level);
        return $this;
    }
    
    //
    //
    
    private function create_new_image($width, $height) {
        return imagecreatetruecolor($width, $height);
    }
    
    private function raw_copy() {
        
        if ($this->is_true_color()) {
            $gd = imagecreatetruecolor($this->get_width(), $this->get_height());
        } else {
            $gd = imagecreate($this->get_width(), $this->get_height());
        }
        
        imagecopy($gd, $this->gd, 0, 0, 0, 0, $this->get_width(), $this->get_height());
        
        return $gd;
        
    }
}
?>