<?php

require 'SimpleTemplate.Class.php';

$passed = $failed = $total = 0;

if (true) {
	$page = new SimpleTemplate('tt/if.html');
	$page->add('cond', true);
	compare($page->render(), 'tt/if.html.true', '$if(true)$');

	$page = new SimpleTemplate('tt/if.html');
	$page->add('cond', false);
	compare($page->render(), 'tt/if.html.false', '$if(false)$');

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
}

print "done. Passed $passed of $total\n";

function compare($string, $file, $name) {
	global $passed, $failed, $total;
	$total ++;
	$string2 = file_get_contents($file);

	print "$total: ";
	$string = preg_replace('/^\n+/', '', $string);
	$string = preg_replace('/\n+$/', "\n", $string);
	if ($string == $string2) {
		print "$name: Passed\n";
		$passed ++;
	} else {
		print "$name: FAILED\n";
		print "Got: $string\nExpected: $string2\n";
		$failed ++;
	}
}
?>
