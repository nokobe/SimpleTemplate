<?php

require 'SimpleTemplate.Class.php';

$fruit = array('apple', 'orange', 'banana');
$apples = array('royal gala', 'pink lady', 'jonathan', 'golden delicious', 'fuji', 'granny smith');

$headings = array("Food type", "Commonly seen as", "My view");
$body = array("a", "b", "c");

$page = new SimpleTemplate('templates/audit.html');
$page->add('base', '.');
$page->add('list', $body);
$page->add('title', "Hello World");
$page->add('name', "Mark");
$page->add('fruit', $fruit);
$page->add('justapplies', $apples);
$page->add('table_head', $headings);
$page->add('table_row', $body);
$page->add('version', '0.1');
print $page->render();

# vim:filetype=html:ts=4:sw=4
?>
