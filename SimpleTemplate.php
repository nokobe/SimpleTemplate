<?php

class SimpleTemplate {
	protected $templateInUse;
	protected $page;
	protected $attr;
	protected $trace = 0; # for debugging
	public $version = "0.6";

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

	function parseCOND($condition) {
		# tokenise, substitute, evaluate
		$this->trace("parseCOND: $condition");

		$reverseResult = false;
		if ($condition[0] == '!') {
			$reverseResult = true;
			$condition = str_replace('!', '', $condition);
		}
		if (isset($this->attr[$condition])) {
			$result = is_bool($this->attr[$condition]) ? $this->attr[$condition] : true;
		} else {
			$result = false;
		}
		return $reverseResult ? ! $result : $result;
	}

	/*
	 * parseIF
	 */
	function parseIF($string) {
		$this->trace("parsing IF");
		$pattern = '/\$if\(([^)]+)\)\$/';
		if (! preg_match($pattern, $string, $matches)) {
			$this->fatal("parseIF.1 failed: couldn't find $pattern in $string");
		}
		$ifCOND = $this->parseCOND($matches[1]);
		$this->trace("ifCOND is : ".($ifCOND ? "true" : "false"));
		$this->trace("now checking for ELSE");
		if (preg_match('/\$if\([^)]+\)\$\n?(.*)\$else\$\n?(.*?)\$endif\$\n?/s', $string, $matches)) {
			$this->trace("has ELSE");
			$bodyTrue = $matches[1];
			$bodyFalse = $matches[2];

			if (!preg_match('/\n\$else\$\n/s', $string)) {
				# need to allow any newline to be preserved
				if (preg_match('/\$endif\$\n/s', $string)) {
					$bodyTrue .= "\n";
				}
			}
			if (!preg_match('/\n\$endif\$\n/s', $string)) {
				# need to allow any newline to be preserved
				if (preg_match('/\$endif\$\n/s', $string)) {
					$bodyFalse .= "\n";
				}
			}
			$this->trace("bodyTrue = [$bodyTrue]");
			$this->trace("bodyFalse = [$bodyFalse]");
			return $ifCOND ? $bodyTrue : $bodyFalse;
		}
		else if (preg_match('/\$if\([^)]+\)\$\n?(.*)\$endif\$/s', $string, $matches)) {
			$this->trace("no ELSE");
			$bodyTrue = $matches[1];
			$bodyFalse = '';
			if (!preg_match('/\n\$endif\$\n/s', $string)) {
				# need to allow any newline to be preserved
				if (preg_match('/\$endif\$\n/s', $string)) {
					$bodyTrue .= "\n";
				}
			}
			$this->trace("bodyTrue = [$bodyTrue]");
			$this->trace("bodyFalse = [$bodyFalse]");
			return $ifCOND ? $bodyTrue : $bodyFalse;
		}
		else {
			$this->fatal("parseIF.2 failed. Missing \$endif\$ ?");
		}
	}

	function parseTEMPLATE($string) {
		$this->trace("checking : [[[ $string ]]]");
		if (! preg_match('/\$([a-zA-Z0-9-_]+)\(\)\$/', $string, $matches)) {
			$this->fatal("parseTEMPLATE.1 failed: couldn't find '/\$([a-zA-Z0-9-_]+)\(\)\$/' in $string");
		}
		$name = $matches[1];
		$this->trace("extracted template name as: $name");
		return $this->renderTemplate(file_get_contents("$this->templateDir/$name.$this->suffix"));
	}

	function parseLIVE($string) {
		$this->trace("checking : [[[ $string ]]]");
		if (! preg_match('/\$([^: ]+):{([^|]+)\|([^}]+)}\$/', $string, $matches)) {
			$this->fatal("parseLIVE.1 failed: couldn't find '/\$([^: ]+):{([^|]+)\|([^}]+)}\$/' in $string");
		}
		$var = $matches[1];
		$alias = $matches[2];
		$text = $matches[3];
		$this->trace("var = $var\nalias = $alias\ntext = $text");
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
		$this->trace("checking : [[[ $string ]]]");
		if (! preg_match('/\$([a-zA-Z0-9-_]+):([a-zA-Z0-9-_]+)\(([^)]*)\)\$/', $string, $matches)) {
			$this->fatal("parseMAP.1 failed: couldn't find '/\$([a-zA-Z0-9-_]+):([a-zA-Z0-9-_]+)\(([^)]*)\)\$/' in $string");
		}
		$var = $matches[1];
		$template = $matches[2];
		$alias = $matches[3] == "" ? 'attr' : "$matches[3]";
		$this->trace("var = $var\ntemplate = $template\nalias = $alias");
#		$text = trim(file_get_contents("$this->templateDir/$template.$this->suffix"), "\n");
		$text = file_get_contents("$this->templateDir/$template.$this->suffix");
		return $this->parseLIVE("\$$var:".'{'."$alias|$text".'}'."\$");
	}

	function parseVAR($string) {
		$this->trace("checking : [[[ $string ]]]");
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
		$this->trace("\n\nchecking for IF");
		$regex = '/
		#	\n?			# match the newline when possible
			\$if\([^)]+\)\$
			(.*?)		# be ungreedy here!
			\$endif\$
			\n?			# match the newline when possible
			/sx';
		if (preg_match_all($regex, $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				$this->trace("found \$if()\$...\$endif\$ in [$found]");
				$string = str_replace($found, $this->parseIF($found), $string);
			}
		}

		$this->trace("\n\nchecking for TEMPLATE");
		if (preg_match_all('/\$[a-zA-Z0-9-_]+\(\)\$(\n|$)?/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				$trimmed = trim($found);
				$this->trace("found \$template()$ == [$found]");
				$this->trace("found trimmed \$template()$ == [$trimmed]");
				$string = str_replace($found, $this->parseTEMPLATE($trimmed), $string);
			}
		}
		$this->trace("\n\nchecking for LIVE TEMPLATE");
		if (preg_match_all('/\$[a-zA-Z0-9-_]+:{[^}]+}\$/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				$this->trace("found \$live template$ == $found");
				$string = str_replace($found, $this->parseLIVE($found), $string);
			}
		}
		$this->trace("\n\nchecking for MAP");
		if (preg_match_all('/\$[a-zA-Z0-9-_]+:[a-zA-Z0-9-_]+\([^)]*\)\$/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				$this->trace("found \$map template$ == $found");
				$string = str_replace($found, $this->parseMAP($found), $string);
			}
		}
		$this->trace("\n\nchecking for VAR");
		if (preg_match_all('/\$[a-zA-Z0-9-_]+\$/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				$this->trace("found \$var$ == $found");
				$string = str_replace($found, $this->parseVAR($found), $string);
			}
		}
		return $string;
	}

	function fatal($message) {
			$templateInUse = $this->templateInUse;
			die("$templateInUse :: $message");
	}

	function traceOn() {
		$this->trace = 1;
	}
	function traceOff() {
		$this->trace = 0;
	}
	function trace($s) {
		if ($this->trace) {
			print str_replace("\n", "\n| \t", $s);
			print "\n";
		}
	}
}

# vim:ts=4:sw=4
?>
