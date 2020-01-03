<?php

require 'MusicRequire.inc';

$isMulti = false;

// function moveCues($directory)
//  $directory - the directory given to check for cue files to move
//  $multiGroup - if this is part of a multiple cd collection, it is added to a place holder group
function moveCues($directory, $multiGroup)
{
  // opens directory and checks all the files
  $dir = opendir($directory);
  while(($file = readdir($dir)) !== false)
    if(getSuffix($file) === "cue")
    {
      // gets just title before cue
      $title = substr($file, 0, strlen($file) - 3);

      // checks for multiple CDs
      $endChecker = "/\d$/";
      if(preg_match($endChecker, $title))
      {
        // checks if there is a first disk
      }else{
        // runs program for one file
        singleMove($file);
      }
    }

  closedir($dir);

}

// function singleMove($file)
//  $file - single cue file given to move to a spot
function singleMove($file)
{
  $fileLines = file($file);
  $newFileLines = array();
  for($i = 0; $i <= count($fileLines); $i++)
  {
    $fileMarker = "/FILE/";
    if(preg_match($fileMarker, $fileLines[$i]))
    {
      $newFileLines[$i] = $fileLines[$i];
      $replacePart = "/FILE \".*\\\/";
      // " (ignore this)
      $fillerPart = "/FILE \"/";
      // " (ignore this)
      preg_replace($replacePart, $fillerPart, $newFileLines[i]);
    }else{
      $newFileLines[$i] = $fileLines[$i];
    }
  }
}

// function test()
// basic test function
function test()
{
  $line = "FILE \"Thelonious Monk\At Carnegie Hall\02 Evidence.wav\" WAVE";
  $replacePart = "/FILE \".*\\\/";
  // " (ignore this)
  $fillerPart = "/FILE \"/";
  // " (ignore this)
  $stuff = preg_replace($replacePart, $fillerPart, $line);
  print $stuff . "\n";
}
?>