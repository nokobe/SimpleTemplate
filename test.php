#! /usr/bin/php
<?php

require 'SimpleTemplate.php';

$passed = $failed = $total = 0;

$set = true;

#	debugOn();
if ($set) {
	$page = new SimpleTemplate('tt/if.html');
	$page->add('cond', true);
	compare($page->render(), 'tt/if.html.true', '$if(true)$');

	$page = new SimpleTemplate('tt/if.html');
	$page->add('cond', false);
	compare($page->render(), 'tt/if.html.false', '$if(false)$');

	$page = new SimpleTemplate('tt/complexif.html');
	$page->add('condtrue', true);
	$page->add('condfalse', false);
	compare($page->render(), 'tt/complexif.html.out', '$if(complexcond)$');

	$page = new SimpleTemplate('tt/ifelse.html');
	$page->add('cond', true);
	compare($page->render(), 'tt/ifelse.html.true', '$ifelse(true)$');

	$page = new SimpleTemplate('tt/ifelse.html');
	$page->add('cond', false);
	compare($page->render(), 'tt/ifelse.html.false', '$ifelse(false)$');

	$page = new SimpleTemplate('tt/body.html');
	$page->add('title', 'The Title');
	compare($page->render(), 'tt/body.html.out', '$template()$');

	$page = new SimpleTemplate('tt/body2.html');
	$page->add('cond', true);
	$page->add('title', 'The Title');
	compare($page->render(), 'tt/body2.html.true', 'recursion(w\ true)');

	$page = new SimpleTemplate('tt/body2.html');
	$page->add('cond', false);
	$page->add('title', 'The Title');
	compare($page->render(), 'tt/body2.html.false', 'recursion(w\ false)');

	$page = new SimpleTemplate('tt/basic.html');
	$page->add('var', 'potato');
	$page->add('var2', 'tomato');
	compare($page->render(), 'tt/basic.html.potato', 'basic');

	$page = new SimpleTemplate('tt/live.html');
	$page->add('fruit', array('apple', 'orange', 'banana'));
	$page->add('title', 'The Title');
	$page->add('name', 'Mark');
	compare($page->render(), 'tt/live.html.out', 'live TEMPLATE');

	#
	$page = new SimpleTemplate('tt/map.html');
	$page->add('fruit', array('apple', 'orange', 'banana'));
	compare($page->render(), 'tt/map.html.out', 'map TEMPLATE');

	$list = array();
	$list[] = array("name" => "John", "height" => "175");
	$list[] = array("name" => "Peter", "height" => "177");
	$list[] = array("name" => "Mathew", "height" => "179");
	$list[] = array("name" => "Mark", "height" => "181");
	$page = new SimpleTemplate('tt/map2.html');
	$page->add('list', $list);
	#$page->add('list', array('apple', 'orange', 'banana'));
	compare($page->render(), 'tt/map2.html.out', 'map (object) TEMPLATE');

}

print "done. Passed $passed of $total\n";

exit(0);

function compareWithDiff($string, $file, $name) {
	echo "\n\nCHECKING WITH SDIFF\n\n";
	echo "Render | Expected Output\n";
	$tmpfile = "_xyz";
	file_put_contents($tmpfile, $string);
	passthru("sdiff $tmpfile $file");
#	print "showing render:\n";
#	passthru("cat $tmpfile");
#	print "---end render---\n";
}

function compare($string, $file, $name) {
	global $passed, $failed, $total;
	$total ++;
	$string2 = file_get_contents($file);

	print "$total: ";
#	$string = preg_replace('/^\n+/', '', $string);
#	$string = preg_replace('/\n+$/', "\n", $string);
	if ($string == $string2) {
		print "$file-$name: * * * Passed * * *\n";
		$passed ++;
	} else {
		print "$file-$name: * * * FAILED * * *\n";
		compareWithDiff($string, $file, $name);
#		print "Expected: [$string2]\nGot: [$string]\n";
		$failed ++;
	}
}
?>
