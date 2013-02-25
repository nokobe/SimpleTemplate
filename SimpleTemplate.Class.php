<?php

class SimpleTemplate {
	protected $page;
	protected $attr;
	public $version = "0.2";

	function SimpleTemplate($file) {
		$this->getTemplate($file);
		$this->templateDir = dirname($file);
		$this->suffix = "html";
	}

	function getTemplate($file) {

		$this->page = file_get_contents($file);
	}

	function add($name, $value) {
		$this->attr[$name] = $value;
	}

	function render() {
		return $this->template($this->page);
	}

	/*
	 * parseIF
	 */
	function parseIF($string) {
		debug("checking : [[[ $string ]]]");
		if (! preg_match('/\$if\(([^)]+)\)\$/', $string, $matches)) {
			die("parseIF.1 failed");
		}
		$cond = $matches[1];
		debug("cond = $cond");
		if (isset($this->attr[$cond])) {
			$ifCOND = is_bool($this->attr[$cond]) ? $this->attr[$cond] : true;
		} else {
			$ifCOND = false;
		}
		debug("ifCOND is : ".($ifCOND ? "true" : "false"));
		# check if there is an ELSE 
		debug("checking : [[[ $string ]]]");
		if (preg_match('/\$if\([^)]+\)\$(.*)\$else\$(.*)\$endif\$/s', $string, $matches)) {
			$bodyTrue = $matches[1];
			$bodyFalse = $matches[2];
			return $ifCOND ? $bodyTrue : $bodyFalse;
		}
		else if (preg_match('/\$if\([^)]+\)\$(.*)\$endif\$/s', $string, $matches)) {
			$body = $matches[1];
			return $ifCOND ? $body : "";
		}
		else {
			die("parseIF.2 failed");
		}
	}

	function parseTEMPLATE($string) {
		debug("checking : [[[ $string ]]]");
		if (! preg_match('/\$([a-zA-Z0-9-_]+)\(\)\$/', $string, $matches)) {
			die("parseTEMPLATE.1 failed");
		}
		$name = $matches[1];
		debug("extracted template name as: $name");
		return $this->template(file_get_contents("$this->templateDir/$name.$this->suffix"));
	}

	function parseLIVETEMPLATE($string) {
		debug("checking : [[[ $string ]]]");
		if (! preg_match('/\$([^: ]+):{([^|]+)\|([^}]+)}\$/', $string, $matches)) {
			die("parseLIVETEMPLATE.1 failed");
		}
		$var = $matches[1];
		$alias = $matches[2];
		$text = $matches[3];
		debug("var = $var\nalias = $alias\ntext = $text");
		if (! isset($this->attr[$var])) {
			die("missing (array) attribute for $var\n");
		}
		if (is_array($this->attr[$var])) {
			$r = '';
			foreach ($this->attr[$var] as $attr) {
				$r .= str_replace("\$$alias\$", $attr, $text);
			}
			return $r;
		}
		else {
			die("$var is not an array!\n");
		}
	}

	function parseVAR($string) {
		debug("checking : [[[ $string ]]]");
		if (! preg_match('/\$([a-zA-Z0-9-Z]+)\$/', $string, $matches)) {
			die("parseVAR.1 failed");
		}
		$var = $matches[1];
		if (!isset($this->attr[$var])) {
			die("missing attribute for $var\n");
		}
		return $this->attr[$var];
	}

	function template($string) {
		global $x, $y;
		if (preg_match_all('/\$if\([^)]+\)\$.*\$endif\$/Us', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				debug("found \$if()\$ == $found");
				$string = str_replace($found, $this->parseIF($found), $string);
			}
		}
		if (preg_match_all('/\$[a-zA-Z0-9-_]+\(\)\$/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				debug("found \$template()$ == $found");
				$string = str_replace($found, $this->parseTEMPLATE($found), $string);
			}
		}
		debugOn();
		if (preg_match_all('/\$[a-zA-Z0-9-_]+:{[^}]+}\$/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				debug("found \$live template$ == $found");
				$string = str_replace($found, $this->parseLIVETEMPLATE($found), $string);
			}
		}
//		if (preg_match_all('/\$[a-zA-Z0-9-_]+:[a-zA-Z0-9-_]\([^)]+\)\$/', $string, $matches) > 0) {
//		}
		if (preg_match_all('/\$[a-zA-Z0-9-_]+\$/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				debug("found \$var$ == $found");
				$string = str_replace($found, $this->parseVAR($found), $string);
			}
		}
		return $string;
	}
}

function debugOn() {
	global $debug;
	$debug = 1;
}
function debugOff() {
	global $debug;
	$debug = 0;
}
function debug($s) {
	global $debug;
	if ($debug) {
#		print "$s\n";
	}
}

# vim:filetype=html:ts=4:sw=4
?>
