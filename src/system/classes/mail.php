<?php
/*
	This file is part of the KISS Framework
	Copyright (c) 2016 Anton Pitak (http://www.softpae.com)
	Module: KISS Mail class file
	Version: 1.1
*/

class Mail {

	const EOL = "\r\n";
	const CRLF = "\r\n";
	const TAB = "\t";
	
	const EMAIL_HIGH = 1;
	const EMAIL_NORMAL = 3;
	const EMAIL_LOW = 5;
	
	private $headers = array();
	private $attachments = array();
	private $images = array();
	private $from = null;
	private $to = array();
	private $cc = array();
	private $bcc = array();
	
	private $html_body = '';
	private $text_body = '';
	
	private $_message = '';
	private $_subject = '';
	
    private $server = null;
    private $port = 25;
    private $username = null;
    private $password = null;
    private $connect_timeout = 30;
    private $response_timeout = 8;
	
	private $_recipients = array();
	
    private $socket = null;
    private $use_smtp = false;
    private $use_tls = true;
    private $use_ssl = false;
    private $use_log = false;
	
	private $log = '';
	
    private $boundary;
    private $boundary_content;
	
	
	public function __construct($server = null, $port = 25, $username = null, $password = null, $connection_timeout = 30, $response_timeout = 8) {
	
		$this->addHeader('Mime-Version',  '1.0');
		$this->addHeader('X-Mailer',  'PHP/KISS.' . phpversion());
		//$this->addHeader('Date', date('D, d M Y H:i:s O'));
		
        $this->boundary = md5(rand());
        $this->boundary_content = md5(rand());
		
		$this->server = $server;
		$this->port = $port;
		$this->username = $username;
		$this->password = $password;
		$this->connection_timeout = $connection_timeout;
		$this->response_timeout = $response_timeout;
		
	}
	
	public function useSmtp($value = true) {
		$this->use_smtp = $value;
		return $this;
	}
	
	public function useTls($value = true) {
		$this->use_tls = $value;
		return $this;
	}
	
	public function useSsl($value = true) {
		$this->use_ssl = $value;
		return $this;
	}
	
	public function useLog($value = true) {
		$this->use_log = $value;
		return $this;
	}
	
	public function setFrom($email, $name = null) {
		$this->from = array($email, $name);
		return $this;
	}
	
	public function addTo($email, $name = null) {
		$this->to[] = array($email, $name);
		return $this;
	}
	
	public function addCc($email, $name = null) {
		$this->cc[] = array($email, $name);
		return $this;
	}
	
	public function addBcc($email, $name = null) {
		$this->bcc[] = array($email, $name);
		return $this;
	}
	
	public function addReplyTo($email, $name = null) {
		$this->addHeader('Reply-To', $this->formatEmail($email, $name));
		return $this;
	}
	
	public function setSubject($subject) {
		$this->_subject = $subject;
		return $this;
	}
	
	public function setHtmlBody($body) {
		$this->html_body = $body;
		return $this;
	}
	
	public function setBody($body) {
		return $this->setHtmlBody($body);
	}
	
	public function setTextBody($body) {
		$this->text_body = $body;
		return $this;
	}
	
	public function setReturnPath($email) {
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
	
    public function getLog() {
		return $this->log;
    }
	
    public function getTo() {
		return $this->buildEmailList($this->to);
    }
	
    public function getSubject() {
		return $this->_subject;
    }
	
    public function getHeaders() {
		return $this->_headers;
    }
	
    public function getMessage() {
		return $this->_message;
    }
	
	public function setPriority($value) {
		$this->addHeader('X-Priority', (int)$value);
		return $this;
	}
	
	public function send() {
		
		if (!$this->use_smtp) {
		
			// Use native php mail()
			
			// Prepare headers
			$this->headers['From'] = $this->formatEmail($this->from);
			if (!empty($this->cc)) {
				$this->headers['Cc'] = $this->buildEmailList($this->cc);
			}
			$this->headers['Date'] = date('D, d M Y H:i:s O');
			
			$headers = $this->buildHeaders($this->headers, true);
			
			// Build body
			$this->compose();
			
			//var_dump($headers);
			
			// Send
			return mail($this->getTo(), '=?UTF-8?B?'.base64_encode($this->_subject).'?=', $this->_message, $headers);
			
		} else {
		
			// Use SMTP
			
			// Prepare headers
			$this->headers['From'] = $this->formatEmail($this->from);
			$this->headers['To'] = $this->buildEmailList($this->to);
			if (!empty($this->cc)) {
				$this->headers['Cc'] = $this->buildEmailList($this->cc);
			}
			$this->headers['Subject'] = '=?UTF-8?B?'.base64_encode($this->_subject).'?=';
			$this->headers['Date'] = date('D, d M Y H:i:s O');
			
			$headers = $this->buildHeaders($this->headers, true);
			
			//var_dump($headers);
			
			// Build body
			$this->compose();
		
			// Connect
			$this->socket = fsockopen(($this->use_ssl ? 'ssl://'.$this->server : ($this->use_tls ? 'tcp://'.$this->server : $this->server)), $this->port, $err_number, $err_string, $this->connect_timeout);
			
			if (empty($this->socket)) {
				return false;
			}
			
			// Start
			$this->getResponse();
			$this->sendCmd('EHLO '.getenv('SERVER_NAME'));
			
			if ($this->use_tls) {
			
				$this->sendCmd('STARTTLS');
				stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
				$this->sendCmd('EHLO '.getenv('SERVER_NAME'));
				
			}
			
			// Log in
			$this->sendCmd('AUTH LOGIN');
			$this->sendCmd(base64_encode($this->username));
			$this->sendCmd(base64_encode($this->password));
			$this->sendCmd('MAIL FROM: <' . $this->from[0] . '>');
			
			$this->_recipients = array_merge($this->to, $this->cc, $this->bcc);
			
			// Recipients
			foreach (array_merge($this->_recipients) as $address) {
				$this->sendCmd('RCPT TO: <' . $address[0] . '>');
			}
			
			$this->sendCMD('DATA');
			
			// Finish
			$ret = $this->sendCMD($headers . self::CRLF . $this->_message . self::CRLF . '.');
			$this->sendCMD('QUIT');
			
			fclose($this->socket);
			
			return substr($ret, 0, 3) == '250';
		
		}
		
	}
	
	public function save($filename) {
		
		// Prepare headers
		$this->headers['From'] = $this->formatEmail($this->from);
		$this->headers['To'] = $this->buildEmailList($this->to);
		if (!empty($this->cc)) {
			$this->headers['Cc'] = $this->buildEmailList($this->cc);
		}
		$this->headers['Subject'] = '=?UTF-8?B?'.base64_encode($this->_subject).'?=';
		$this->headers['Date'] = date('D, d M Y H:i:s O');
		
		$headers = $this->buildHeaders($this->headers, true);
		
		var_dump($headers);
		
		// Build body
		$this->compose();
		
		$ret = file_put_contents($filename, $headers.$this->_message);
		
		return ($ret === false ? false : true);
		
	}
	
	private function compose() {
		
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
		
		// Include images
		if (!empty($this->html_body)) {
		
			preg_match_all('#<img[^>]*src="([^"]*)"#i', $this->html_body, $matches);
			
			if (isset($matches[0])) {
				foreach ($matches[0] as $index=>$img) {
				
					$id = 'img'.$index;
					$src = $matches[1][$index];
					
					//echo 'SRC: '.$src.BR;
					
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
		
		// Add attachments
		foreach ($this->attachments as $file) {
		
			$attachment = $this->prepareAttachment($file);
			if ($attachment !== false) {
				$msg .= self::EOL;
				$msg .= '--'.$this->boundary.self::EOL;
				$msg .= $attachment;
			}
		
		}
		
		// Add images
		foreach ($this->images as $file) {
		
			$attachment = $this->prepareImage($file);
			if ($attachment !== false) {
				$msg .= self::EOL;
				$msg .= '--'.$this->boundary.self::EOL;
				$msg .= $attachment;
			}
		
		}
		
		// Finish
		$msg .= self::EOL;
		$msg .= '--'.$this->boundary.'--'.self::EOL;
		
		$this->_message = $msg;
		
		return $this;
		
 	}
	
	private function buildHeaders($value, $multipart = false) {
	
		$headers = '';
		
		foreach ($value as $key => $val) {
			$headers .= $key . ': ' . $val . self::CRLF;
		}
		if ($multipart) {
			$headers .= 'Content-Type: multipart/related; boundary="'.$this->boundary.'"'.self::EOL;
		}
		return $headers;
		
	}
	
	private function formatEmail($email, $name = null) {
	
		if (is_array($email)) {
			$value = $email;
		} else {
			if (!$name && preg_match('#^(.+) +<(.*)>\z#', $email, $matches)) {
				$value = array($matches[2], $matches[1]);
			} else {
				$value = array($email, $name);
			}
		}
		
		if (isset($value[1]) && !empty($value[1])) {
			return '=?UTF-8?B?'.base64_encode($value[1]).'?=<'.$value[0].'>';
		} else {
			return '=?UTF-8?B?'.base64_encode(stristr($value[0], '@', true)).'?= <'. $value[0].'>';
		}
	}
	
	private function buildEmailList($addresses) {
	
		$list = '';
		
		foreach ($addresses as $address) {
			$list .= (empty($list) ? '' : ', ').$this->formatEmail($address);
		}
        return $list;
		
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
	
	private function Log($msg) {
		$this->log .= (empty($this->log) ? $msg : self::CRLF.$msg );
	}
	
    private function getResponse() {
	
        stream_set_timeout($this->socket, $this->response_timeout);
		
        $response = '';
		
        while (($line = fgets($this->socket, 515)) != false) {
            $response .= trim($line) . "\n";
            if (substr($line, 3, 1) == ' ') {
                break;
            }
        }
		
        return trim($response);
    }
	
    private function sendCmd($command) {
	
        fputs($this->socket, $command.self::CRLF);
		
		$ret = $this->getResponse();
		
		if ($this->use_log) {
			$this->Log($command.self::CRLF.$ret);
		}
		
        return $ret;
		
    }

}
