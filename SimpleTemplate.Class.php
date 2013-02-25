<?php

class SimpleTemplate {
	protected $page;
	protected $attr;

	function SimpleTemplate($file) {
		$this->getTemplate($file);
	}

	function getTemplate($file) {

		$this->page = file_get_contents($file);
	}

	function add($name, $value) {
		$this->attr[$name] = $value;
	}

	function render() {
		global $x, $y;
		foreach ($this->attr as $name => $value) {
			$x = $name;
			$y = $value;
			if (is_array($value)) {
				// find $name:{var|string}$
				$this->page = preg_replace_callback('/\$[^: ]+:{[^}]+}\$/',
					function($match) {
						global $x, $y;
						preg_match('/\$([^: ]+):{([^|]+)\|([^}]+)}\$/', $match[0], $matches);
						$var = $matches[1];
						$alias = $matches[2];
						$text = $matches[3];
						if ($x != $var) {
							return $match[0];
						}
//						print "var = $var\nalias = $alias\ntext = $text\n";
//						print "x = $x, y = $y\n";
						$r = "";
						foreach ($y as $attr) {
							$r .= str_replace("\$$alias\$", $attr, $text);
						}
						return $r;
//						return "[[ $match[0] ]]";

						return str_replace("\$$alias\$", "DUMMY", $text);
					},
					$this->page);
			} else {
				$this->page = str_replace("\$$name\$", "$value", $this->page);
			}
		}
		print $this->page;
	}
}

# vim:filetype=html:ts=4:sw=4
?>
