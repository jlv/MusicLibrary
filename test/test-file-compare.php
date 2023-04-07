<?php

//require "MusicRequire.inc";

// functional definition

require "../src/MusicRequire.inc";

logp_init("FileCompare", "");

$file_a = "d:/tmp-Rising-D/FileCmp1.exe";
$file_b = "d:/tmp-Rising-D/FileCmp2.exe";
//$file_b = "d:/tmp-Rising-D/FileCmp3.jpg";
//$file_b = "d:/tmp-Rising-D/NoFile.jpg";

if ( files_compare($file_a, $file_b) === TRUE )
//if ( $i == 1)
{
  print "Files match!\n";
} else {
  print "Files do not match.\n";
}

// show files
print " File a: {$file_a}\n";
print " File b: {$file_b}\n";

logp_close();

exit();

?>
