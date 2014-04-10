<?php

class SimpleTemplate {
	protected $templateInUse;
	protected $page;
	protected $attr;
	protected $trace = 0; # for debugging
	public $version = "0.8.0";

	function SimpleTemplate($file, $live = 0) {
		if ($live) {
			$this->templateInUse = "live";
			$this->page = $file; // in this case, $file is actually an anonymous template
			$parts = Array();
			$this->templateDir = "";
			$this->suffix = "";
		} else {
			$parts = pathinfo($file);
			if (! isset($this->templateDir)) {
				$this->templateDir = $parts['dirname'];
				$this->suffix = $parts['extension'];
				$this->templateInUse = $file;
			}
				
			if ( strcmp($parts['dirname'], $this->templateDir) != 0) {	 // same base OR... assume is relative
				$this->templateInUse = $this->templateDir."/".$file.$this->suffix;

//				$this->loadTemplate("$this->templateDir/$file$this->suffix");
			}
			$this->trace("template in use = ".$this->templateInUse);

			$this->loadTemplate($file);
//			$this->loadTemplate("$this->templateDir/$file.$this->suffix");
		}
		$this->parent = Array();
	}

	function addparent($parent) {
		$this->trace("parent added!");
		$this->parent[] = $parent;
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

	function hasAttr($var) {
		if ($x = isset($this->attr[$var])) { return $x; }
		foreach ($this->parent as $parent) {
			if ($x = isset($parent->attr[$var])) { return $x; }
		}
		return false;
	}

	function hasAttr2($var, $key) {
		return isset($this->attr[$var][$key]);

		if ($x = isset($this->attr[$var][$key])) { return $x; }
		foreach ($this->parent as $parent) {
			if ($x = isset($parent->attr[$var][$key])) { return $x; }
		}
		return false;
	}

	function getAttr($var) {
		if (isset($this->attr[$var])) { return $this->attr[$var]; }
		foreach ($this->parent as $parent) {
			if (isset($parent->attr[$var])) { return $parent->attr[$var]; }
		}
		$this->fatal("GETATTR > attr for $var not found");
	}

	function getAttr2($var, $key) {
		if (isset($this->attr[$var][$key])) { return $this->attr[$var][$key]; }
		foreach ($this->parent as $parent) {
			if (isset($parent->attr[$var][$key])) { return $parent->attr[$var][$key]; }
		}
		$this->fatal("GETATTR2 > attr for $var [$key] not found");
	}

	function matchVar($string, &$var) {
		if (preg_match('/\$([a-zA-Z0-9-_]+)\$/', $string, $found)) { // check for $var$
			$var = $found[1];
			return 1;
		}
		return 0;
	}

	function matchVarKey($string, &$var, &$key) {
		if (preg_match('/\$([a-zA-Z0-9-_]+)\[([^\]]+)\]\$/', $string, $found)) { // check for $var[key]$
			$var = $found[1];
			$key = $found[2];
			return 1;
		}
		return 0;
	}

	//
	// @return: boolean
	//
	function parseCOND($condition) {
		# tokenise, substitute, evaluate
		$this->trace("parseCOND: $condition");

		$reverseResult = false;
		if ($condition[0] == '!') {
			$reverseResult = true;
			$condition = str_replace('!', '', $condition);
		}

		$result = false;
		if ($this->hasAttr($condition)) {
			$result = $this->getAttr($condition);
		} else if ($this->matchVar($condition, $var)) {
#			if (! $this->hasAttr($var)) {
#				$this->fatal("ParseCOND > VAR: no attribute for \$$var\$ in $condition");
#			}
			if ($this->hasAttr($var)) $result = $this->getAttr($var);

		} else if ($this->matchVarKey($condition, $var, $key)) {
#			if (! $this->hasAttr2($var, $key)) {
#				$this->fatal("Parse COND > ARRAYVAR: no attribute found for \${$var}[$key]\$");
#			}
			if ($this->hasAttr2($var, $key)) $result = $this->getAttr2($var, $key);
		}
#		else {
#			$result = false;
#		}

		return $reverseResult ? ! $result : $result;
	}

	/*
	 * parseIF - parse an IF clause.
	 *  fail if IF-clause not found
	 */
	function parseIF($string) {
		$this->trace("parsing IF");
		$pattern = '/\$if\(([^)]+)\)\$/';
		if (! preg_match($pattern, $string, $matches)) {
			$this->fatal("Parse IF (#1) failed: couldn't find \$if()\$ in string: $string");
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
			$this->fatal("Parse IF (#2) failed: \$endif\$ not found");
		}
	}

	function parseTEMPLATE($string) {
		$this->trace("parseTEMPLATE > checking : [[[ $string ]]]");
		if (! preg_match('/\$([a-zA-Z0-9-_]+)\(\)\$/', $string, $matches)) {
			$this->fatal("Parse TEMPLATE (#1) failed: couldn't find \$template()\$ in string: $string");
		}
		$name = $matches[1];
		$this->trace("extracted template name as: $name");
		return $this->renderTemplate(file_get_contents("$this->templateDir/$name.$this->suffix"));
	}

	// parse: $var:{alias|_template_}$
	function parseLIVE($string) {
		$this->trace("parseLIVE > checking : [[[ $string ]]]");
		if (! preg_match('/\$([^: ]+):{([^|]+)\|([^}]+)}\$/', $string, $matches)) {
			$this->fatal("Parse LIVE (#1) failed: couldn't find \$var:{alias|livetemplate}\$ in string: $string");
		}
		$var = $matches[1];
		$alias = $matches[2];
		$templatetext = $matches[3];
		$this->trace("var = $var\nalias = $alias\ntext = [[[ $templatetext ]]]");
		if (! isset($this->attr[$var])) {
			$this->fatal("Parse LIVE (#2): attribute \"$var\" not set. Expected array");
		}

#		if (! is_array($this->attr[$var])) {
#			$this->fatal("Parse LIVE (#4): \"$var\" is not an array in template string: $string");
#		}

		$r = '';
		if (is_array($this->attr[$var])) {
			foreach ($this->attr[$var] as $attr) {
				$subpage = new SimpleTemplate($templatetext, 1);
				$subpage->add($alias, $attr);
				$subpage->addparent($this);

				// this is an anonymous template, but we still want to preserve these settings
				$subpage->templateDir = $this->templateDir;
				$subpage->suffix = $this->suffix;

				$r .= $subpage->render();
	//			if (is_array($attr)) {
	//				$templ = $text;
	//				foreach ($attr as $subkey => $subvalue) {
	//					$templ = str_replace("\$$alias"."[$subkey]\$", htmlentities($subvalue), $templ);
	//				}
	//				$r .= $templ;
	//			} else {
	////					$r .= str_replace("\$$alias\$", htmlentities($attr), $text, $count);
	//				$r .= str_replace("\$$alias\$", htmlentities($attr), $text, $count);
	//			}
			}
		} else {
			$subpage = new SimpleTemplate($templatetext, 1);
			$subpage->add($alias, $this->attr[$var]);
			$subpage->addparent($this);

			// this is an anonymous template, but we still want to preserve these settings
			$subpage->templateDir = $this->templateDir;
			$subpage->suffix = $this->suffix;

			$r .= $subpage->render();
		}
		return $r;
	} // end parseLIVE()

	// parse: $var:template_file(alias)$
	function parseMAP($string) {
		$this->trace("parseMAP > checking : [[[ $string ]]]");
		if (! preg_match('/\$([a-zA-Z0-9-_]+):([a-zA-Z0-9-_]+)\(([^)]*)\)\$/', $string, $matches)) {
			$this->fatal("Parse MAP (#1) failed: couldn't find \$var:sub_template()\$ in string: $string");
		}
		$var = $matches[1];
		$template = $matches[2];
		$alias = $matches[3] == "" ? 'attr' : "$matches[3]";

		$this->trace("var = $var\ntemplate = $template\nalias = $alias");
		$text = file_get_contents("$this->templateDir/$template.$this->suffix");
		return $this->parseLIVE("\$$var:".'{'."$alias|$text".'}'."\$");
	}

	# this function deprecated after version 0.6
	function parseVAR($string) {
		$this->trace("checking : [[[ $string ]]]");
		if (! preg_match('/\$([a-zA-Z0-9-Z]+)\$/', $string, $matches)) {
			$this->fatal("Parse VAR (#1) failed: couldn't find \$var\$ in string: $string");
		}
		$var = $matches[1];
		if (!isset($this->attr[$var])) {
			$this->fatal("Parse VAR (#2): missing attribute for \"$var\"");
		}
		return $this->attr[$var];
	}

	function matchAndReplaceIF($string) {
		// $if(cond)$ ... [$else$] ... $endif$
		$this->trace("\n\nchecking for IF");
		$regex_IF_ENDIF = '/
		#	\n?			# match the newline when possible
			\$if\([^)]+\)\$
			(.*?)		# be ungreedy here!
			\$endif\$
			\n?			# match the newline when possible
			/sx';
		if (preg_match_all($regex_IF_ENDIF, $string, $matches) > 0) {
			foreach ($matches[0] as $ifstmt) {
				$this->trace("found \$if()\$...\$endif\$ in [$ifstmt]");
				$string = str_replace($ifstmt, $this->parseIF($ifstmt), $string);
			}
		}
		return $string;
	}

	function renderTemplate($string) {
		global $x, $y;

		if (count($this->parent) > 0) {
			$this->trace("rendering THIS: $string");
			$this->trace("using these: ".print_r($this->attr, 1));
			$this->trace("and these: ".print_r($this->parent[0]->attr, 1));
		}

		$anonymousTemplates = $this->maskAnonymousTemplates($string);

		$string = $this->matchAndReplaceIF($string);

		// $var$
		$this->trace("\n\nchecking for VAR");
		if (preg_match_all('/\$([a-zA-Z0-9-_]+)\$/', $string, $matches, PREG_SET_ORDER) > 0) {
			foreach ($matches as $found) {
				$var = $found[1];
				$this->trace("found \$var$ == $var");
				if (!isset($this->attr[$var])) {
					$this->fatal("Parse VAR: no attribute for \$$var\$");
				}
				$this->trace("and it's set to : ".print_r($this->attr[$var], 1));
				$string = str_replace($found[0], htmlentities($this->attr[$var]), $string);
			}
		}
		// $var[key]$
		$this->trace("\n\nchecking for ARRAY VAR");
		if (preg_match_all('/\$([a-zA-Z0-9-_]+)\[([^\]]+)\]\$/', $string, $matches, PREG_SET_ORDER) > 0) {
			foreach ($matches as $found) {
				$var = $found[1];
				$idx = $found[2];
				if (! isset($this->attr[$var][$idx])) {
					$this->fatal("Parse ARRAYVAR: no attribute found for \${$var}[$idx]\$");
				}
				$string = str_replace($found[0], htmlentities($this->attr[$var][$idx]), $string);
			}
		}

		// $templatefile()$
		$this->trace("\n\nchecking for TEMPLATE");
		if (preg_match_all('/\$[a-zA-Z0-9-_]+\(\)\$(\n|$)?/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				$trimmed = trim($found);
				$this->trace("found \$template()$ == [$found]");
				$this->trace("found trimmed \$template()$ == [$trimmed]");
				$string = str_replace($found, $this->parseTEMPLATE($trimmed), $string);
			}
		}

		// $var:template()$
		$this->trace("\n\nchecking for MAP");
		if (preg_match_all('/\$[a-zA-Z0-9-_]+:[a-zA-Z0-9-_]+\([^)]*\)\$/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				$this->trace("found \$map template$ == $found");
				$string = str_replace($found, $this->parseMAP($found), $string);
			}
		}

		$string = $this->restoreAnonymousTemplates($string, $anonymousTemplates);

		// $var:{alias|anonymous template}$
		$this->trace("\n\nchecking for LIVE TEMPLATE");
		if (preg_match_all('/\$[a-zA-Z0-9-_]+:{[^}]+}\$/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				$this->trace("found \$live template$ == $found");
				$string = str_replace($found, $this->parseLIVE($found), $string);
			}
		}
		return $string;
	}

	# @param string(reference) - template text
	# @return array - array of anonymous templates stripped from template text
	function maskAnonymousTemplates(&$string) {
		$anonymousTemplates = Array();
		$maskCount = 0;
		while (preg_match('/\$[a-zA-Z0-9-_]+:{[^}]+}\$/', $string, $match) > 0) {
			$anonymousTemplates[] = $match[0];
			$maskCount ++;
			$mask = "MASK6238973498.$maskCount";
			$string = str_replace($match[0], $mask, $string);
		}
		return $anonymousTemplates;
	}

	# @param array - array of anonymous templates stripped from template text
	function restoreAnonymousTemplates($string, $templates) {
		$maskCount = 0;
		foreach ($templates as $template) {
			$maskCount ++;
			$mask = "MASK6238973498.$maskCount";
			$string = str_replace($mask, $template, $string);
		}
		return $string;
	}

	function fatal($message) {
			$templateInUse = $this->templateInUse;
			die("Error processing template($templateInUse):: $message\n");
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
