<?php


$target="foo";
$link="bar";

$cwd = getcwd();

print "current working dir:" . $cwd . "\n";

symlink($target, $link);

print "link created\n";

if (is_link ($link))
	print "link detected\n";

$new_link = readlink($link);

print "new link " . $new_link . "\n";

exit;




//test
  $line = "FILE \"Thelonious Monk\\At Carnegie Hall\\02 Evidence.wav\" WAVE";

  print $line . "\n";

  $replacePart = "/FILE \".*\\\/";
  // " (ignore this)

  $fillerPart = "FILE \"";
  // " (ignore this)

  $stuff = preg_replace($replacePart, $fillerPart, $line);
  print $stuff . "\n";

  exit;

?>