<?php

require 'MusicRequire.inc';

log_init("MoveCues");



// function scanCues($directory, $trashBin)
//  $directory - target directory which the function checks
//  $trashBin - target directory for old cue files
function scanCues($directory, $trashBin){

  $goodCue = '';

  $dir = opendir($directory);
  while(($file = readdir($dir)) !== false){

    if(getSuffix($file) === "cue"){
        // gets album title of cue file
        $title = substr($file, 0, strlen($file) - 4);

        // checks if the cd is part of a multi cd
        if(preg_match("/\d$/", $title) && is_dir($directory . "/". substr($file, 0, strlen($file) - 5))){
          if(preg_match("/-\d$/", $title)){
            $goodCue = multiMove($directory, $trashBin, $file, true);
          }else{
            $goodCue = multiMove($directory, $trashBin, $file, false);
          }

        }else{

        }
    }

  }

  closedir($directory);

}

// function multiMove($directory, $trashBin, $hasDash)
//  $directory - target directory in which the cue files lie
//  $trashBin - target directory for old cue files
//  $file - tells which file we are looking at that caused multiMove
//  $hasDash - tells function whether or not cue files are using the dash system
function multiMove($directory, $trashBin, $file, $hasDash){
  if($hasDash){
    // gets album title
    $album = substr($file, 0, strlen($file) - 6);

    // starting number for cue files
    $index = 1;

    // new cue file var
    $newCue = file($album . "-" . $index . ".cue", FILE_IGNORE_NEW_LINES);

    // DEBUGGING checker
    $checker = rename($directory . "/" . $album . "-" . $index . ".cue", $trashBin . "/" . $album . "-" . $index . ".cue");
    if(!$checker)
      plog("ERROR: could not rename old cue file");
    $index++;

    // compiles all cue files of album into one
    while(file_exists($directory . "/" . $album . "-" . $index . ".cue")){
      array_push($newCue, "REM CD " . $index);

      // reads next cue file into an array
      $nextCue = file($directory . "/" . $album . "-" . $index . ".cue", FILE_IGNORE_NEW_LINES);
      array_merge($newCue, $nextCue);

      // DEBUGGING checker
      $checker = rename($directory . "/" . $album . "-" . $index . ".cue", $trashBin . "/" . $album . "-" . $index . ".cue");
      if(!$checker)
        plog("ERROR: could not rename old cue file");

      $index++;
    }

    // checks to see if ripper made cue file that's just album title
    if(file_exists($directory . "/" . $album . ".cue")){
      array_push($newCue, "REM CD " . $index++);

      $nextCue = file($directory . "/" . $album . ".cue", FILE_IGNORE_NEW_LINES);
      array_merge($newCue, $nextCue);
    }

    // puts $newCue into an actual cue file
    $finished = file_put_contents($directory . "/" . $album . ".cue", $newCue);

    // returns $finished as fully made cue file
    return $finished;

  }else{
    // getse album title
    $album = substr($file, 0, strlen($file) - 5);

  }
}

//


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

$testDir = "C:/Quentin/ReferenceMusic-RippingTool/0 Classical";
$test = opendir($testDir);

while(($file = readdir($test)) !== false){
  if(getSuffix($file) === "cue")
    echo substr($file, 0, strlen($file) - 6) . "\n";
}


?>
