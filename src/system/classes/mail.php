<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS config file
	Version: 1.0
*/

class Mail {

	const EOL = "\r\n";
	
	const EMAIL_HIGH = 1;
	const EMAIL_NORMAL = 3;
	const EMAIL_LOW = 5;
	
	private $headers = array();
	private $attachments = array();
	private $images = array();
	private $to = array();
	
	private $html_body = '';
	private $text_body = '';
	
	private $_to = '';
	private $_headers = '';
	private $_message = '';
	private $_subject = '';
	
    private $boundary;
    private $boundary_content;
	
	
	public function __construct() {
	
		$this->addHeader('Date', date('D, d M Y H:i:s O'));
		$this->addHeader('X-Mailer',  'PHP/KISS.' . phpversion());
		
        $this->boundary = md5(rand());
        $this->boundary_content = md5(rand());
		
	}
	
	public function addFrom($email, $name = null) {
		$this->addHeader('From', $this->formatEmail($email, $name));
		return $this;
	}
	
	public function addTo($email, $name = null) {
		$this->to[$email] = array($email, $name);
		return $this;
	}
	
	public function addCc($email, $name = null) {
		$this->addHeader('Cc', $this->formatEmail($email, $name), true);
		return $this;
	}
	
	public function addBcc($email, $name = null) {
		$this->addHeader('Bcc', $this->formatEmail($email, $name), true);
		return $this;
	}
	
	public function addReplyTo($email, $name = null) {
		$this->addHeader('Reply-To', $this->formatEmail($email, $name));
		return $this;
	}
	
	public function addSubject($subject) {
		$this->_subject = $subject;
		return $this;
	}
	
	public function addHtmlBody($body) {
		$this->html_body = $body;
		return $this;
	}
	
	public function addBody($body) {
		return $this->addHtmlBody($body);
	}
	
	public function addTextBody($body) {
		$this->text_body = $body;
		return $this;
	}
	
	public function addReturnPath($email) {
		$this->addHeader('Return-Path', $email);
		return $this;
	}
	
    public function addAttachment($path, $content_id = null) {
		$chk = md5($path);
		$this->attachments[$chk] = array('path' => $path, 'id' => $content_id);
		return $this;
    }
	
    public function addFile($path, $content_id = null) {
		return $this->addAttachment($path, $content_id);
    }
	
    public function addImage($path, $content_id = null) {
		$chk = md5($path);
		$this->images[$chk] = array('path' => $path, 'id' => $content_id);
		return $this;
    }
	
	public function addHeader($name, $value, $append = false) {
		if ($value == null) {
			unset($this->headers[$name]);
		} else {
			if ($append) {
				if (!is_array($this->headers[$name])) {
					$this->headers[$name] = array($this->headers[$name], $value);
				} else {
					$this->headers[$name][] = $value;
				}
			} else {
				$this->headers[$name] = $value;
			}
		}
		return $this;
	}
	
    public function getTo($path, $content_id = null) {
		return $this->_to;
    }
	
    public function getSubject($path, $content_id = null) {
		return $this->_subject;
    }
	
    public function getHeaders($path, $content_id = null) {
		return $this->_headers;
    }
	
    public function getMessage($path, $content_id = null) {
		return $this->_message;
    }
	
	public function setPriority($value) {
		$this->addHeader('X-Priority', (int)$value);
		return $this;
	}
	
	public function send() {
	
		$this->compose();
		
		// Function mail()
        return mail($this->_to, '=?UTF-8?B?'.base64_encode($this->_subject).'?=', $this->_message, $this->_headers);
	}
	
	public function save($filename) {
		
		$this->compose();
		
		file_put_contents($filename, 'To: '.$this->_to.self::EOL.'Subject: =?UTF-8?B?'.base64_encode($this->_subject).'?='.self::EOL.$this->_headers.$this->_message);
		
	}
	
	public function compose($exclude_to = true) {
	
		$to_header = '';
		$headers = '';
		
		// Compose headers
		foreach ($this->headers as $key => $header) {
			
			if (is_array($header)) {
				
				$line = '';
				
				foreach ($header as $value) {
					$line .= (empty($line) ? '' : ', ').$value;
				}
				
				if ($key == 'To') {
					$to_header = $line;
				} else {
					$headers .= $key.': '.$line.self::EOL;
				}
				
			} else {
			
				if ($key == 'To') {
					$to_header = $header;
				} else {
					$headers .= $key.': '.$header.self::EOL;
				}
				
			}
			
		}
		
		// Compose To
		$to = '';
		
		foreach ($this->to as $value) {
			$to .= (empty($to) ? '' : ', ').$this->formatEmail($value[0], $value[1]);
		}
		
		$headers .= 'Mime-Version: 1.0'.self::EOL;
		$headers .= 'Content-Type: multipart/related; boundary="'.$this->boundary.'"'.self::EOL;
		$headers .= self::EOL;
		
		// Message Body
		$msg = self::EOL;
        $msg .= '--'.$this->boundary.self::EOL;
        $msg .= 'Content-Type: multipart/alternative;'.self::EOL;
        $msg .= ' boundary="'.$this->boundary_content.'"'.self::EOL;
		$msg .= self::EOL;
		
		// Text Body
		$msg .= '--'.$this->boundary_content.self::EOL;
		$msg .= 'Content-Type: text/plain; charset=utf-8'.self::EOL;
		
		if (!empty($this->text_body)) {
			$msg .= $this->text_body.self::EOL;
		} else {
			$msg .= strip_tags($this->html_body).self::EOL;
		}
		$msg .= self::EOL;
		
		// Html Body
		$msg .= '--'.$this->boundary_content.self::EOL;
		$msg .= 'Content-Type: text/html; charset=utf-8'.self::EOL;
		$msg .= 'Content-Transfer-Encoding: quoted-printable'.self::EOL;
		$msg .= self::EOL;
		
		if (!empty($this->html_body)) {
		
			preg_match_all('#<img[^>]*src="([^"]*)"#i', $this->html_body, $matches);
			
			if (isset($matches[0])) {
				foreach ($matches[0] as $index=>$img) {
				
					$id = 'img'.$index;
					$src = $matches[1][$index];
					
					echo 'SRC: '.$src.BR;
					
					$this->addImage($src, $id);
					
					$this->html_body = preg_replace('#<img[^>]*src="'.$src.'"#i', '<img src="cid:'.$id.'"', $this->html_body);
				}
			}
			
			$msg .= str_replace("=", "=3D", $this->html_body).self::EOL;
			
		} else {
			$msg .= str_replace("=", "=3D", $this->text_body).self::EOL;
		}
		$msg .= self::EOL;
		
		$msg .= '--'.$this->boundary_content.'--'.self::EOL;
		
		foreach ($this->attachments as $file) {
		
			$attachment = $this->prepareAttachment($file);
			if ($attachment !== false) {
				$msg .= self::EOL;
				$msg .= '--'.$this->boundary.self::EOL;
				$msg .= $attachment;
			}
		
		}
		
		foreach ($this->images as $file) {
		
			$attachment = $this->prepareImage($file);
			if ($attachment !== false) {
				$msg .= self::EOL;
				$msg .= '--'.$this->boundary.self::EOL;
				$msg .= $attachment;
			}
		
		}
		
		$msg .= self::EOL;
		$msg .= '--'.$this->boundary.'--'.self::EOL;
		
		$this->_to = $to;
		$this->_headers = $headers;
		$this->_message = $msg;
		
		return $this;
		
 	}

	private function formatEmail($email, $name = null) {
	
		if (!$name && preg_match('#^(.+) +<(.*)>\z#', $email, $matches)) {
			$value = array($matches[2], $matches[1]);
		} else {
			$value = array($email, $name);
		}
		
		if (isset($value[1]) && !empty($value[1])) {
			return '=?UTF-8?B?'.base64_encode($value[1]).'?=<'.$value[0].'>';
		} else {
			return '=?UTF-8?B?'.base64_encode(stristr($value[0], '@', true)).'?= <'. $value[0].'>';
		}
	}

    private function prepareImage($file) {
	
		$path = $file['path'];
		$content_id = $file['id'];
		
		$mime = $this->getImageMimeType($path);
		
        if (file_exists($path)) {
		
            $file = fopen($path, "r");
            $attachment = fread($file, filesize($path));
            $attachment = chunk_split(base64_encode($attachment));
            fclose($file);
			
            $msg = 'Content-Type: '.$mime.'; name="'.basename($path).'"'.self::EOL;
            $msg .= 'Content-Transfer-Encoding: base64'.self::EOL;
			if (isset($content_id)) {
				$msg .= 'Content-ID: <'.$content_id.'>'.self::EOL;
			} else {
				$msg .= 'Content-ID: <'.basename($path).'>'.self::EOL;
			}
			
			$msg .= self::EOL;
            $msg .= $attachment;
			$msg .= self::EOL.self::EOL;
			
            return $msg;
			
        } else {
            return false;
        }
    }

    private function prepareAttachment($file) {
	
		$path = $file['path'];
		
        if (file_exists($path)) {
		
            $file = fopen($path, "r");
            $attachment = fread($file, filesize($path));
            $attachment = chunk_split(base64_encode($attachment));
            fclose($file);
			
            $msg = 'Content-Type: \'application/octet-stream\'; name="'.basename($path).'"'.self::EOL;
            $msg .= 'Content-Transfer-Encoding: base64'.self::EOL;
            $msg .= 'Content-ID: <'.basename($path).'>'.self::EOL;
			//$msg .= 'X-Attachment-Id: ebf7a33f5a2ffca7_0.1'.self::EOL;
			
			$msg .= self::EOL;
            $msg .= $attachment;
			$msg .= self::EOL.self::EOL;
			
            return $msg;
			
        } else {
            return false;
        }
    }
	
	private function getImageMimeType($path) {
	
		$handle   = fopen($path, 'r');
		$contents = fread($handle, 12);
		fclose($handle);
		
		$_0_8  = substr($contents, 0, 8);
		$_0_4  = substr($contents, 0, 4);
		$_6_4  = substr($contents, 6, 4);
		$_20_4 = substr($contents, 20, 4);
		
		if ($_0_4 == "MM\x00\x2A" || $_0_4 == "II\x2A\x00") {
			return 'image/tif';
		}
		
		if ($_0_8 == "\x89PNG\x0D\x0A\x1A\x0A") {
			return 'image/png';
		}
		
		if ($_0_4 == 'GIF8') {
			return 'mage/gif';
		}
		
		if ($_6_4 == 'JFIF' || $_6_4 == 'Exif' || ($_0_4 == "\xFF\xD8\xFF\xED" && $_20_4 == "8BIM")) {
			return 'image/jpeg';
		}
		
		return NULL;
	}
	
	private function buildText($html) {
	
		$text = Strings::replace($html, array(
			'#<(style|script|head).*</\\1>#Uis' => '',
			'#<t[dh][ >]#i' => ' $0',
			'#<a\s[^>]*href=(?|"([^"]+)"|\'([^\']+)\')[^>]*>(.*?)</a>#is' =>  '$2 &lt;$1&gt;',
			'#[\r\n]+#' => ' ',
			'#<(/?p|/?h\d|li|br|/tr)[ >/]#i' => "\n$0",
		));
		$text = html_entity_decode(strip_tags($text), ENT_QUOTES, 'UTF-8');
		$text = Strings::replace($text, '#[ \t]+#', ' ');
		return trim($text);
	}

}
