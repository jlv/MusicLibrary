<?php


$file="TestFile -1.cue";

if(preg_match("/( *)(-?)1.cue$/", $file, $matches)){
	// set base title
	$base_title = substr($file, 0, -strlen($matches[1] . $matches[2] . "1.cue"));

	print_r($matches);
	print "base:{$base_title}:\n";
}

exit;



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
