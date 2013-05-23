#! /usr/bin/php
<?php

require 'SimpleTemplate.php';

$passed = $failed = $total = 0;

$set = true;
$trace = false;

if ($set) {
	$page = new SimpleTemplate('testfiles/if.html');
	if ($trace) { $page->traceOn(); }
	$page->add('cond', true);
	compare($page->render(), 'testfiles/if.html.true', '$if(true)$');

	$page = new SimpleTemplate('testfiles/if.html');
	if ($trace) { $page->traceOn(); }
	$page->add('cond', false);
	compare($page->render(), 'testfiles/if.html.false', '$if(false)$');

	$page = new SimpleTemplate('testfiles/complexif.html');
	if ($trace) { $page->traceOn(); }
	$page->add('condtrue', true);
	$page->add('condfalse', false);
	compare($page->render(), 'testfiles/complexif.html.out', '$if(complexcond)$');

	$page = new SimpleTemplate('testfiles/ifelse.html');
	if ($trace) { $page->traceOn(); }
	$page->add('cond', true);
	compare($page->render(), 'testfiles/ifelse.html.true', '$ifelse(true)$');

	$page = new SimpleTemplate('testfiles/ifelse.html');
	if ($trace) { $page->traceOn(); }
	$page->add('cond', false);
	compare($page->render(), 'testfiles/ifelse.html.false', '$ifelse(false)$');

	$page = new SimpleTemplate('testfiles/body.html');
	if ($trace) { $page->traceOn(); }
	$page->add('title', 'The Title');
	compare($page->render(), 'testfiles/body.html.out', '$template()$');

	$page = new SimpleTemplate('testfiles/body2.html');
	if ($trace) { $page->traceOn(); }
	$page->add('cond', true);
	$page->add('title', 'The Title');
	compare($page->render(), 'testfiles/body2.html.true', 'recursion(w\ true)');

	$page = new SimpleTemplate('testfiles/body2.html');
	if ($trace) { $page->traceOn(); }
	$page->add('cond', false);
	$page->add('title', 'The Title');
	compare($page->render(), 'testfiles/body2.html.false', 'recursion(w\ false)');

	$page = new SimpleTemplate('testfiles/basic.html');
	if ($trace) { $page->traceOn(); }
	$page->add('var', 'potato');
	$page->add('var2', 'tomato');
	compare($page->render(), 'testfiles/basic.html.potato', 'basic');

	$page = new SimpleTemplate('testfiles/live.html');
	if ($trace) { $page->traceOn(); }
	$page->add('fruit', array('apple', 'orange', 'banana'));
	$page->add('title', 'The Title');
	$page->add('name', 'Mark');
	compare($page->render(), 'testfiles/live.html.out', 'live TEMPLATE');

	#
	$page = new SimpleTemplate('testfiles/map.html');
	if ($trace) { $page->traceOn(); }
	$page->add('fruit', array('apple', 'orange', 'banana'));
	compare($page->render(), 'testfiles/map.html.out', 'map TEMPLATE');

	$list = array();
	$list[] = array("name" => "John", "height" => "175");
	$list[] = array("name" => "Peter", "height" => "177");
	$list[] = array("name" => "Mathew", "height" => "179");
	$list[] = array("name" => "Mark", "height" => "181");
	$page = new SimpleTemplate('testfiles/map2.html');
	if ($trace) { $page->traceOn(); }
	$page->add('list', $list);
	#$page->add('list', array('apple', 'orange', 'banana'));
	compare($page->render(), 'testfiles/map2.html.out', 'map (object) TEMPLATE');

	# complex var (hash)
	$page = new SimpleTemplate('testfiles/hash.html');
	$page->add('user', array('name' => 'John', 'age' => '35', 'hair' => 'blue'));
	compare($page->render(), 'testfiles/hash.html.out', 'complex (hash) VAR');

	# complex var (hash) (recursive template)
	$page = new SimpleTemplate('testfiles/rhash.html');
	$page->add('user', array('name' => 'John', 'age' => '35', 'hair' => 'blue'));
	compare($page->render(), 'testfiles/rhash.html.out', 'complex (hash) (recursive) VAR');
}

print "done. Passed $passed of $total\n";

exit(0);

function compareWithDiff($string, $file, $name) {
	echo "\n\nCHECKING WITH SDIFF\n\n";
	echo "Render | Expected Output\n";
	$tmpfile = "_tmpfile";
	file_put_contents($tmpfile, $string);
	passthru("sdiff $tmpfile $file");
	unlink($tmpfile);
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
