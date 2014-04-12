<?php

class SimpleTemplate {
	protected $templateInUse;
	protected $page;
	protected $attr;
	protected $trace = 0; # for debugging
	public $version = "0.9.0";

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

			}
			$this->trace("template in use = ".$this->templateInUse);
			$this->loadTemplate($file);
		}
		$this->parent = Array();
	}

	function addparent($parent) {
		if ($parent->trace == 1) $this->traceOn();
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

	// recursively check for attribute: var
	function hasAttr($var) {
		if ($x = isset($this->attr[$var]))
		{
			return $x;
		}
		foreach ($this->parent as $parent) {
			if ($x = $parent->hasAttr($var)) {
				return $x;
			}
		}
		return false;
	}

	// recursively check for attribute: var[key]
	function hasAttr2($var, $key) {
		if ($x = isset($this->attr[$var][$key])) {
			return $x;
		}
		foreach ($this->parent as $parent) {
			if ($x = $parent->hasAttr2($var, $key)) {
				return $x;
			}
		}
		return false;
	}

	// recursively fetch attribute: var
	function getAttr($var) {
		if (isset($this->attr[$var])) {
			return $this->attr[$var];
		}
		foreach ($this->parent as $parent) {
			if ($parent->hasAttr($var)) {
				return $parent->getAttr($var);
			}
		}
		$this->fatal("GETATTR > attr for $var not found");
	}

	// recursively fetch attribute: var[key]
	function getAttr2($var, $key) {
		if (isset($this->attr[$var][$key])) {
			return $this->attr[$var][$key];
		}
		foreach ($this->parent as $parent) {
			if ($parent->hasAttr2($var, $key)) {
				return $parent->getAttr2($var, $key);
			}
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
			if ($this->hasAttr($var)) $result = $this->getAttr($var);

		} else if ($this->matchVarKey($condition, $var, $key)) {
			if ($this->hasAttr2($var, $key)) $result = $this->getAttr2($var, $key);
		}

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

	// parse: $var:{alias|_template_}$ or $var[key]:{alias|_template_}$
	function parseLIVE($string) {
		$this->trace("parseLIVE > checking : [[[ $string ]]]");
		if (! preg_match('/\$([^: ]+):{([^|]+)\|([^}]+)}\$/', $string, $matches)) {
			$this->fatal("Parse LIVE (#1) failed: couldn't find \$var:{alias|livetemplate}\$ in string: $string");
		}
		$var = $matches[1];
		$alias = $matches[2];
		$templatetext = $matches[3];
		$this->trace("var = $var\nalias = $alias\ntext = [[[ $templatetext ]]]");

		$result = '';
		// check if we have a $var$ or a $var[key]$...
		if (preg_match('/([a-zA-Z0-9-_]+)\[([^\]]+)\]/', $var, $found)) {
			$value = $this->getAttr2($found[1], $found[2]);
			$this->trace("TRACE1");
		} else {
			$value = $this->getAttr($var);
			$this->trace("TRACE2");
		}
		if (is_array($value)) {
			$autoindex = 0;
			foreach ($value as $attr) {
				$subpage = new SimpleTemplate($templatetext, 1);
				$subpage->addparent($this);
				$subpage->add('i0', $autoindex);
				$subpage->add('i1', $autoindex + 1);
				$subpage->add($alias, $attr);

				// carry these settings into the subpage
				$subpage->templateDir = $this->templateDir;
				$subpage->suffix = $this->suffix;

				$result .= $subpage->render();

				$autoindex ++;
			}
		} else {
			$subpage = new SimpleTemplate($templatetext, 1);
			$subpage->addparent($this);
			$subpage->add($alias, $value);

			// carry these settings into the subpage
			$subpage->templateDir = $this->templateDir;
			$subpage->suffix = $this->suffix;

			$result .= $subpage->render();
		}
		return $result;
	} // end parseLIVE()

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

	#
	# search and replace instances of
	# 	$var$
	#
	function matchAndReplaceVAR($string) {
		$this->trace("\n\nchecking for VAR");
		if (preg_match_all('/\$([a-zA-Z0-9-_]+)\$/', $string, $matches, PREG_SET_ORDER) > 0) {
			foreach ($matches as $found) {
				$var = $found[1];
				$this->trace("found \$var$ == $var");
				if (!$this->hasAttr($var)) {
					$this->fatal("Parse VAR: no attribute for \$$var\$");
				}
				$value = $this->getAttr($var);
				$this->trace("and it's set to : $value");
				$string = str_replace($found[0], htmlentities($value), $string);
			}
		}
		return $string;
	}

	#
	# search and replace instances of
	# 	$var[key]$
	#
	function matchAndReplaceARRAYVAR($string) {
		// $var[key]$
		$this->trace("\n\nchecking for ARRAY VAR");
		if (preg_match_all('/\$([a-zA-Z0-9-_]+)\[([^\]]+)\]\$/', $string, $matches, PREG_SET_ORDER) > 0) {
			foreach ($matches as $found) {
				$var = $found[1];
				$idx = $found[2];
				if (! $this->hasAttr2($var, $idx)) {
					$this->fatal("Parse ARRAYVAR: no attribute found for \${$var}[$idx]\$");
				}
				$value = $this->getAttr2($var, $idx);
				$string = str_replace($found[0], htmlentities($value), $string);
			}
		}
		return $string;
	}

	#
	# search and replace instances of
	#	$templatefile()$
	#
	function matchAndReplaceTEMPLATE($string) {
		$this->trace("\n\nchecking for TEMPLATE");
		if (preg_match_all('/\$([a-zA-Z0-9-_]+)\(\)\$(\n|$)?/', $string, $matches, PREG_SET_ORDER) > 0) {
			foreach ($matches as $match) {
				$found = $match[0];
				$templatefile = $match[1];
				$this->trace("found \$template()\$ => $templatefile()");
				$templatetext = file_get_contents("$this->templateDir/$templatefile.$this->suffix");
				$render = $this->renderTemplate($templatetext);
				$string = str_replace($found, $render, $string);
			}
		}
		return $string;
	}

	#
	# search and replace instances of
	#	$var:template(alias)$ and/or $var[key]:template(alias)$
	#
	function matchAndReplaceMAP($string) {
		$this->trace("\n\nchecking for MAP");
		if (preg_match_all('/\$([a-zA-Z0-9-_\[\]]+):([a-zA-Z0-9-_]+)\(([^)]*)\)\$/', $string, $matches, PREG_SET_ORDER) > 0) {
			foreach ($matches as $match) {
				$found = $match[0];
				$var = $match[1];
				$template = $match[2];
				$alias = $match[3] == "" ? 'attr' : "$match[3]";
				$this->trace("matchAndReplaceMAP:: var = $var\ntemplate = $template\nalias = $alias");
				$templatetext = file_get_contents("$this->templateDir/$template.$this->suffix");
				$render = $this->parseLIVE("\$$var:".'{'."$alias|$templatetext".'}'."\$");
				$string = str_replace($found, $render, $string);
			}
		}
		return $string;
	}

	#
	# search and replace instances of
	#	$var:{alias|anonymous template}$
	#
	function matchAndReplaceANONYMOUSTEMPLATE($string) {
		$this->trace("\n\nchecking for LIVE TEMPLATE");
		if (preg_match_all('/\$[a-zA-Z0-9-_\[\]]+:{[^}]+}\$/', $string, $matches) > 0) {
			foreach ($matches[0] as $found) {
				$this->trace("found \$live template$ == $found");
				$string = str_replace($found, $this->parseLIVE($found), $string);
			}
		}
		return $string;
	}

	function renderTemplate($string) {
		global $x, $y;

		if (count($this->parent) > 0) {
			$this->trace("rendering THIS: $string");
			$this->trace("using these: ".print_r($this->attr, 1));
			$this->trace("and these from the parent: ".print_r($this->parent[0]->attr, 1));
			// and the parents parents, etc.
		}

		$anonymousTemplates = $this->maskAnonymousTemplates($string);

		$string = $this->matchAndReplaceIF($string);
		$string = $this->matchAndReplaceVAR($string);
		$string = $this->matchAndReplaceARRAYVAR($string);
		$string = $this->matchAndReplaceTEMPLATE($string);
		$string = $this->matchAndReplaceMAP($string);

		$string = $this->restoreAnonymousTemplates($string, $anonymousTemplates);

		$string = $this->matchAndReplaceANONYMOUSTEMPLATE($string);

		return $string;
	}

	# @param string(reference) - template text
	# @return array - array of anonymous templates stripped from template text
	function maskAnonymousTemplates(&$string) {
		$anonymousTemplates = Array();
		$maskCount = 0;
		while (preg_match('/\$[a-zA-Z0-9-_\[\]]+:{[^}]+}\$/', $string, $match) > 0) {
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
