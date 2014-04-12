#! /usr/bin/php
<?php

require 'SimpleTemplate.php';

$passed = $failed = $total = 0;

$trace = false;

$startFrom = 1;
$traceFrom = 0; // 0 == off

if (count($argv) > 1) {
	$startFrom = $argv[1];
	print "Starting testing from test number: $startFrom\n";
	if (count($argv) > 2) {
		$traceFrom = $argv[2];
		print "Tracing from test number: $startFrom\n";
	}
}

$testNumber = 1;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/if.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('cond', true);
	compare(1, $page->render(), 'testfiles/if.html.true', '$if(true)$');
}

$testNumber = 2;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/if.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('cond', false);
	compare($testNumber, $page->render(), 'testfiles/if.html.false', '$if(false)$');
}

$testNumber = 3;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/complexif.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('condtrue', true);
	$page->add('condfalse', false);
	compare($testNumber, $page->render(), 'testfiles/complexif.html.out', '$if(complexcond)$');
}

$testNumber = 4;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/complexif2.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	
	$cond['a'] = false;
	$cond['b'] = true;

	$page->add('cond', $cond);

	compare($testNumber, $page->render(), 'testfiles/complexif2.html.out', '$if($cond[]$)$');
}

$testNumber = 5;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/ifelse.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('cond', true);
	compare($testNumber, $page->render(), 'testfiles/ifelse.html.true', '$ifelse(true)$');
}

$testNumber = 6;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/ifelse.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('cond', false);
	compare($testNumber, $page->render(), 'testfiles/ifelse.html.false', '$ifelse(false)$');
}

$testNumber = 7;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/body.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('title', 'The Title');
	compare($testNumber, $page->render(), 'testfiles/body.html.out', '$template()$');
}

$testNumber = 8;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/body2.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('cond', true);
	$page->add('title', 'The Title');
	compare($testNumber, $page->render(), 'testfiles/body2.html.true', 'recursion(w\ true)');
}

$testNumber = 9;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/body2.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('cond', false);
	$page->add('title', 'The Title');
	compare($testNumber, $page->render(), 'testfiles/body2.html.false', 'recursion(w\ false)');
}

$testNumber = 10;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/basic.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('var', 'potato');
	$page->add('var2', 'tomato');
	compare($testNumber, $page->render(), 'testfiles/basic.html.potato', 'basic');
}

$testNumber = 11;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/live.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('fruit', array('apple', 'orange', 'banana'));
	$page->add('title', 'The Title');
	$page->add('name', 'Mark');
	compare($testNumber, $page->render(), 'testfiles/live.html.out', 'live TEMPLATE');
}

	#
$testNumber = 12;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/map.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('fruit', array('apple', 'orange', 'banana'));
	compare($testNumber, $page->render(), 'testfiles/map.html.out', 'map TEMPLATE');
}

$testNumber = 13;
if ($startFrom <= $testNumber) {
	$list = array();
	$list[] = array("name" => "John", "height" => "175");
	$list[] = array("name" => "Peter", "height" => "177");
	$list[] = array("selected" => "1", "name" => "Mathew", "height" => "179");
	$list[] = array("name" => "Mark", "height" => "181");
	$page = new SimpleTemplate('testfiles/map2.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('list', $list);
	#$page->add('list', array('apple', 'orange', 'banana'));
	compare($testNumber, $page->render(), 'testfiles/map2.html.out', 'map (object) TEMPLATE');
}

$testNumber = 14;
if ($startFrom <= $testNumber) {
	$items = Array('doh', 'ray', 'me');
	$page = new SimpleTemplate('testfiles/map3.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('items', $items);
	compare($testNumber, $page->render(), 'testfiles/map3.html.out', 'multi-level include');
}

$testNumber = 15;
if ($startFrom <= $testNumber) {
	# complex var (hash)
	$page = new SimpleTemplate('testfiles/hash.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('user', array('name' => 'John', 'age' => '35', 'hair' => 'blue'));
	compare($testNumber, $page->render(), 'testfiles/hash.html.out', 'complex (hash) VAR');
}

$testNumber = 16;
if ($startFrom <= $testNumber) {
	# complex var (hash) (recursive template)
	$page = new SimpleTemplate('testfiles/rhash.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('user', array('name' => 'John', 'age' => '35', 'hair' => 'blue'));
	compare($testNumber, $page->render(), 'testfiles/rhash.html.out', 'complex (hash) (recursive) VAR');
}

$testNumber = 17;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/ifsubpage.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('cond', true);
	$page->add('var1', '((I am var one))');
	$key = 'xyz';
	$vartwo[$key] = '((I am arrayvar two))';
	$page->add('var2', $vartwo);
	$page->add('key2', $key);
	$page->add('subvar', '((hello from the subpage))');
	$cat = Array();
	compare($testNumber, $page->render(), 'testfiles/ifsubpage.html.true', 'if (true) with subpages');

$testNumber = 18;
	$page->add('cond', false);
	compare($testNumber, $page->render(), 'testfiles/ifsubpage.html.false', 'if (false) with subpages');
}

$testNumber = 19;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/triple_recurse.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('title', 'Triple Recurse Test');
	$cat['dog'] = 'the cat for dog';
	$cat['array'] = Array('doh', 'ray', 'me');
	$page->add('cat', $cat);
	$rows = Array();
	$rows[] = Array('name' => 'John', 'age' => '18', 'addr' => '123 Evergreen St' );
	$rows[] = Array('name' => 'Mary', 'age' => '25', 'addr' => '763 Nosey Cres' );
	$page->add('rows', $rows);
	compare($testNumber, $page->render(), 'testfiles/triple_recurse.html.out', 'triple recurse');
}

$testNumber = 20;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/autoindex.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('title', 'auto index test');
	$rows = Array();
	$rows[] = Array('name' => 'John', 'age' => '18', 'addr' => '123 Evergreen St' );
	$rows[] = Array('name' => 'Mary', 'age' => '25', 'addr' => '763 Nosey Cres' );
	$choices = Array();
	$choices[] = "Don't worry";
	$choices[] = "Be happy";
	$choices[] = "Don't have a cow, man";
	$choices[] = "Be free";
	$page->add('items', $rows);
	$page->add('choices', $choices);
	compare($testNumber, $page->render(), 'testfiles/autoindex.html.out', 'auto index test');
}

$testNumber = 21;
if ($startFrom <= $testNumber) {
	$page = new SimpleTemplate('testfiles/htmle.html');
	if ($traceFrom > 0 && $traceFrom <= $testNumber) { $page->traceOn(); }
	$page->add('title', 'html entity test');
	$page->add('item', 'hello, normal item...<br /> <script>');
	$page->add('array', Array('item' => 'an array item...<br /> <script>'));
	$page->add('neitem', 'hello, normal item...<br /> <script>', 0);
	$page->add('nearray', Array('item' => 'an array item...<br /> <script>'), 0);
	compare($testNumber, $page->render(), 'testfiles/htmle.html.out', 'html entity test');
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

function compare($testNumber, $string, $file, $name) {
	global $passed, $failed, $total;
	$total ++;
	$string2 = file_get_contents($file);

	print "$testNumber: ";
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
