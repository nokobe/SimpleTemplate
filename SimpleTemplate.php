<?php

class SimpleTemplate {
	protected $templateInUse;
	protected $page;
	protected $attr;
	public $version = "0.51";

	function SimpleTemplate($file) {
		$this->templateInUse = $file;
		$this->loadTemplate($file);
		$parts = pathinfo($file);
		$this->templateDir = $parts['dirname'];
		$this->suffix = $parts['extension'];
	}

	function loadTemplate($file) {
		$this->page = file_get_contents($file);
	}

	function add($name, $value) {
		$this->attr[$name] = $value;
	}

	function render() {
		return $this->renderTemplate($this->page);
	}

	/*
	 * parseIF
	 */
	function parseIF($string) {
		debug("checking : [[[ $string ]]]");
		if (! preg_match('/\$if\(([^)]+)\)\$/', $string, $matches)) {
			$this->fatal("parseIF.1 failed: couldn't find '/\$if\(([^)]+)\)\$/' in $string");
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
			$this->fatal("parseIF.2 failed");
		}
	}

	function parseTEMPLATE($string) {
		debug("checking : [[[ $string ]]]");
		if (! preg_match('/\$([a-zA-Z0-9-_]+)\(\)\$/', $string, $matches)) {
			$this->fatal("parseTEMPLATE.1 failed: couldn't find '/\$([a-zA-Z0-9-_]+)\(\)\$/' in $string");
		}
		$name = $matches[1];
		debug("extracted template name as: $name");
		return $this->renderTemplate(file_get_contents("$this->templateDir/$name.$this->suffix"));
	}

	function parseLIVE($string) {
		debug("checking : [[[ $string ]]]");
		if (! preg_match('/\$([^: ]+):{([^|]+)\|([^}]+)}\$/', $string, $matches)) {
			$this->fatal("parseLIVE.1 failed: couldn't find '/\$([^: ]+):{([^|]+)\|([^}]+)}\$/' in $string");
		}
		$var = $matches[1];
		$alias = $matches[2];
		$text = $matches[3];
		debug("var = $var\nalias = $alias\ntext = $text");
		if (! isset($this->attr[$var])) {
			$this->fatal("parseLIVE: missing (array) attribute for $var\n");
		}
		if (is_array($this->attr[$var])) {
			$r = '';
			foreach ($this->attr[$var] as $attr) {
				if (is_array($attr)) {
					$templ = $text;
					foreach ($attr as $subkey => $subvalue) {
						$templ = str_replace("\$$alias"."[$subkey]\$", $subvalue, $templ);
					}
					$r .= $templ;
				} else {
					$r .= str_replace("\$$alias\$", $attr, $text, $count);
					if ($count == 0) {
						$this->fatal("parseLIVE: Iterating over \"$var\", couldn't find \$$alias\$ in template text: $text\n");
					}
				}
			}
			return $r;
		}
		else {
			$this->fatal("parseLIVE: $var is not an array!\n");
		}
	}

	function parseMAP($string) {
		debug("checking : [[[ $string ]]]");
		if (! preg_match('/\$([a-zA-Z0-9-_]+):([a-zA-Z0-9-_]+)\(([^)]*)\)\$/', $string, $matches)) {
			$this->fatal("parseMAP.1 failed: couldn't find '/\$([a-zA-Z0-9-_]+):([a-zA-Z0-9-_]+)\(([^)]*)\)\$/' in $string");
		}
		$var = $matches[1];
		$template = $matches[2];
		$alias = $matches[3] == "" ? 'attr' : "$matches[3]";
		debug("var = $var\ntemplate = $template\nalias = $alias");
#		$text = trim(file_get_contents("$this->templateDir/$template.$this->suffix"), "\n");
		$text = file_get_contents("$this->templateDir/$template.$this->suffix");
		return $this->parseLIVE("\$$var:".'{'."$alias|$text".'}'."\$");
	}

	function parseVAR($string) {
		debug("checking : [[[ $string ]]]");
		if (! preg_match('/\$([a-zA-Z0-9-Z]+)\$/', $string, $matches)) {
			$this->fatal("parseVAR.1 failed: couldn't find '/\$([a-zA-Z0-9-Z]+)\$/' in $string");
		}
		$var = $matches[1];
		if (!isset($this->attr[$var])) {
			$this->fatal("parseVAR: missing attribute for $var\n");
		}
		return $this->attr[$var];
	}

	function renderTemplate($string) {
		global $x, $y;
		debug("\n\nchecking for IF");
		if (preg_match_all('/\$if\([^)]+\)\$.*\$endif\$/Us', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				debug("found \$if()\$ == $found");
				$string = str_replace($found, $this->parseIF($found), $string);
			}
		}
		debug("\n\nchecking for TEMPLATE");
		if (preg_match_all('/\$[a-zA-Z0-9-_]+\(\)\$/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				debug("found \$template()$ == $found");
				$string = str_replace($found, $this->parseTEMPLATE($found), $string);
			}
		}
		debug("\n\nchecking for LIVE TEMPLATE");
		if (preg_match_all('/\$[a-zA-Z0-9-_]+:{[^}]+}\$/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				debug("found \$live template$ == $found");
				$string = str_replace($found, $this->parseLIVE($found), $string);
			}
		}
		debug("\n\nchecking for MAP");
		if (preg_match_all('/\$[a-zA-Z0-9-_]+:[a-zA-Z0-9-_]+\([^)]*\)\$/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				debug("found \$map template$ == $found");
				$string = str_replace($found, $this->parseMAP($found), $string);
			}
		}
		debug("\n\nchecking for VAR");
		if (preg_match_all('/\$[a-zA-Z0-9-_]+\$/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				debug("found \$var$ == $found");
				$string = str_replace($found, $this->parseVAR($found), $string);
			}
		}
		return $string;
	}

	function fatal($message) {
			$templateInUse = $this->templateInUse;
			die("$templateInUse :: $message");
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
		print "$s\n";
	}
}

# vim:ts=4:sw=4
?>
