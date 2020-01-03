<?php

require 'MusicRequire.inc';

$isMulti = false;

// function moveCues($directory)
//  $directory - the directory given to check for cue files to move
function moveCues($directory)
{
  // opens directory and checks all the files
  $dir = opendir($directory);
  while(($file = readdir($dir)) !== false)
    if(getSuffix($file) = "cue")
    {
      // gets just title before cue
      $title = substr($file, 0, strlen($file) - 3);
    }

  closedir($dir);

}


?>