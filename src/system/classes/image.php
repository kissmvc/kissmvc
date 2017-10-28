<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
	Note: Thanks to Nette for inspiration
*/

class Image {

	const FIT = 0; //same or smaller
	const SHRINK_ONLY = 1; //only shrink, not zoom
	const STRETCH = 2; //stretch to dimensions
	const FILL = 4; //one dimension fit, second could overflow
	const CROP = 8; //exact dimensions
	const EXACT = 8; //same as crop
	const FULL = 16; //like fit but fill smaller part by background to fit dimensions

	private $width = 0;
	private $height = 0;
	private $type = null;
	
	private $bgcolor = null;
	
	private $file = '';
	private $image = null;
	
	public function __construct($file = null) {
		if ($file) {
			$this->fromImage($file);
		}
	}
	
	public function fromImage($file) {
	
		if (file_exists($file)) {
		
			if (!extension_loaded('gd')) {
				trigger_error('PHP extension GD is required, please install and enable it.', E_USER_ERROR);
			}
			$this->file = $file;
			
			$this->bgcolor = $this->html2rgb('FFFFFF', 0);
			
			$tmp = getimagesize($file);
			
			$this->width = $tmp[0];
			$this->height = $tmp[1];
			$this->type = $tmp['mime'];
			
			switch ($this->type) {
				case 'image/gif':
					$this->image = imagecreatefromgif($file);
					break;
				case 'image/png':
					$this->image = imagecreatefrompng($file);
					break;
				case 'image/jpeg':
					$this->image = imagecreatefromjpeg($file);
					break;
				   
				default:
					trigger_error('Image Type is not compatible.', E_USER_ERROR);
					break;
			}
			
			imagealphablending($this->image, true);
			
		} else {
			trigger_error('File does not exist.', E_USER_ERROR);
		}
		
		return $this;
	}
	
	public function __destruct() {
		imagedestroy($this->image);
	}
	
	public function getWidth() {
		return $this->width;
	}
	
	public function getHeight() {
		return $this->height;
	}
	
	public function &getImage() {
		return $this->image;
	}
	
    public function setBackground($color, $transparent = false) {
		
		$transparent = (int)$transparent;
		$this->bgcolor = $this->html2rgb($color, $transparent);
		
		return $this;
		
	}
	
    public function setTransparent($color) { //not working right now
		
		$tr_color = $this->html2rgb($color);
		
		$background = imagecolorallocate($this->image, $tr_color['r'], $tr_color['g'], $tr_color['b']);
		imagecolortransparent($this->image, $background);
		
		return $this;
		
	}
	
	public function resize($width, $height, $flags = self::FIT, $scalelimit = 2) {
	
		$scale = 1;
		
		$org_width = $this->width;
		$org_height = $this->height;
		
		$new_width = (int) abs($width);
		$new_height = (int) abs($height);
		
		$scale = array($new_width / $this->width, $new_height / $this->height);
		
		$xpos = 0;
		$ypos = 0;
		$xsrc = 0;
		$ysrc = 0;
		
		if ($flags & self::STRETCH) { // non-proportional
		
			if (empty($new_width) || empty($new_height)) {
				trigger_error('Both width and height must be specified.', E_USER_ERROR);
			}
			
			if ($flags & self::SHRINK_ONLY) {
				$new_width = (int) round($this->width * min(1, $new_width / $this->width));
				$new_height = (int) round($this->height * min(1, $new_height / $this->height));
			}
			
		} else { // proportional
		
			if (empty($new_width) && empty($new_height)) {
				trigger_error('At least width or height must be specified.', E_USER_ERROR);
			}
			
			$scale = array();
			
			if ($new_width > 0) { // fit width
				$scale[] = $new_width / $this->width;
			}
			
			if ($new_height > 0) { // fit height
				$scale[] = $new_height / $this->height;
			}
			
			if ($flags & self::FILL || $flags & self::EXACT) {
				$scale = array(max($scale));
			}
			
			if ($flags & self::SHRINK_ONLY) {
				$scale[] = 1;
			}
			
			$scale = min($scale);
			
			$new_width = (int) round($this->width * $scale);
			$new_height = (int) round($this->height * $scale);
			
			if ($flags & self::FULL) {
				$xpos = (int)(($width - $new_width) / 2);
				$ypos = (int)(($height - $new_height) / 2);
			}
			
			if ($flags & self::EXACT) {
				$xsrc = abs((int)((($width - $new_width) / 2) * (1/$scale)));
				$ysrc = abs((int)((($height - $new_height) / 2) * (1/$scale)));
				$new_width = $width;
				$new_height = $height;
				$org_width = (int)($width * (1/$scale));
				$org_height = (int)($height * (1/$scale));
			}
		}
		
		$new_width = max($new_width, 1);
		$new_height = max($new_height, 1);
		
		if ($flags & self::FULL) {
			$image = imagecreatetruecolor($width, $height);
		} else {
			$image = imagecreatetruecolor($new_width, $new_height);
		}
		
		if ($this->bgcolor['a'] > 0) {
		
			//echo 'Alpha - R:'.$this->bgcolor['r'].', G: '.$this->bgcolor['g'].', B: '.$this->bgcolor['b'].', A: '.$this->bgcolor['a'];
			
			imagealphablending($image, false);
			imagesavealpha($image, true);
			$background = imagecolorallocatealpha($image, $this->bgcolor['r'], $this->bgcolor['g'], $this->bgcolor['b'], $this->bgcolor['a']);
			imagecolortransparent($image, $background);
			
		} else {
		
			$background = imagecolorallocate($image, $this->bgcolor['r'], $this->bgcolor['g'], $this->bgcolor['b']);
			
		}
		
		imagefill($image, 0, 0, $background);
		
		//echo '$xpos: '.$xpos.', $ypos: '.$ypos.', $xsrc: '.$xsrc.', $ysrc: '.$ysrc.', $new_width: '.$new_width.', $new_height: '.$new_height.', $org_width:'.$org_width.', $org_height: '.$org_height;
		
        imagecopyresampled($image, $this->image, $xpos, $ypos, $xsrc, $ysrc, $new_width, $new_height, $org_width, $org_height);
		imagedestroy($this->image);
		
        $this->image = $image;
		
		if ($flags & self::FULL) {
			$this->width = $width;
			$this->height = $height;
		} else {
			$this->width = $new_width;
			$this->height = $new_height;
		}
		
		return $this;
	
	}
	
	public function crop($width, $height) {
		return $this->resize($width, $height, self::CROP);
	}
	
	public function zoom($scale = 1) {
	
		$width = $this->width * (int)$scale;
		$height = $this->height * (int)$scale;
		
		return $this->resize($width, $height);
	}
	
	public function sharpen() {
	
		imageconvolution($this->image, array(
			array(-1, -1, -1),
			array(-1, 24, -1),
			array(-1, -1, -1),
		), 16, 0);
		
		return $this;
	}
	
	function invert() {
		imagefilter($this->image, IMG_FILTER_NEGATE);
		return $this;
	}
	
	function grayscale() {
		imagefilter($this->image, IMG_FILTER_GRAYSCALE);
		return $this;
	}
	
	function brightness($level) {
		imagefilter($this->image, IMG_FILTER_BRIGHTNESS, $level);
		return $this;
	}
	
	function contrast($level) {
		imagefilter($this->image, IMG_FILTER_CONTRAST, $level);
		return $this;
	}
	
	function smooth($level) {
		imagefilter($this->image, IMG_FILTER_SMOOTH, $level);
		return $this;
	}
	
	function blur($selective = false) {
		imagefilter($this->image, ($selective ? IMG_FILTER_SELECTIVE_BLUR : IMG_FILTER_GAUSSIAN_BLUR));
		return $this;
	}
	
	function sketch() {
		imagefilter($this->image, IMG_FILTER_MEAN_REMOVAL);
		return $this;
	}
	
	function emboss() {
		imagefilter($this->image, IMG_FILTER_EMBOSS);
		return $this;
	}
	
	function sepia() {
		imagefilter($this->image, IMG_FILTER_GRAYSCALE);
		imagefilter($this->image, IMG_FILTER_COLORIZE, 90, 60, 45);
		return $this;
	}
	
	function pixelate($size) {
		imagefilter($this->image, IMG_FILTER_PIXELATE, $size, true);
		return $this;
	}
	
    public function send($type = 'jpg', $quality = 90) {
		//header('Content-Type: ' . image_type_to_mime_type($type));
		header('Content-Type: image/'.$type);
		$this->save(null, $quality, $type);
	}
	
    public function save($filename = null, $quality = 90, $type = 'jpg') {
	
		if ($filename) {
			$tmp = pathinfo($filename);
			$ext = strtolower($tmp['extension']);
		} else {
			$ext = strtolower($type);
		}
		
        try {
            switch ($ext) {
            	case 'jpeg':
            	case 'jpg':
					imagejpeg($this->image, $filename, $quality);
					break;
            	case 'png':
					imagepng($this->image, $filename);
					break;
            	case 'gif':
					imagegif($this->image, $filename);
					break;
            	default:
            	    trigger_error('Only JPG, PNG and GIF is available for save.', E_USER_ERROR);
            		break;
            }
			
        } catch (Exception $e) {
            trigger_error($e->getMessage(), E_USER_ERROR);
        }
		
		return $this;
  		
    }
	
	private function html2rgb($color, $alpha = false) {
	
		$color = ltrim($color, '#');
		
		if (strlen($color) == 6) {
			list($r, $g, $b) = array($color[0].$color[1], $color[2].$color[3], $color[4].$color[5]);
		} elseif (strlen($color) == 3) {
			list($r, $g, $b) = array($color[0].$color[0], $color[1].$color[1], $color[2].$color[2]);
		} else {
			return false;
		}
		
		$r = hexdec($r);
		$g = hexdec($g);
		$b = hexdec($b);
		
		$colors = array();
		
		$colors['r'] = $r;
		$colors['g'] = $g;
		$colors['b'] = $b;
		
		if ($alpha !== false) {
			$colors['a'] = $alpha;
		} else {
			$colors['a'] = 0;
		}
		
		return $colors;
   
	}

}
