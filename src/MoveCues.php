<?php

require 'MusicRequire.inc';

log_init("MoveCues");

// function scanCues($directory, $trashBin)
//  $directory - target directory which the function checks
//  $trashBin - target directory for old cue files
function scanCues($directory, $trashBin){

  $goodCue = '';

  // sees if there are multiple cue files for album. Runs multiMove is there are
  $dir = opendir($directory);
  while(($file = readdir($dir)) !== false){
    // NOTE $file is name.cue

    if(getSuffix($file) === "cue"){
        // gets album title of cue file
        $title = substr($file, 0, strlen($file) - 4);

        // checks if the cd is part of a multi cd
        if(preg_match("/1$/", $title)){

          // sees if there's a second cue file with a 2 at the end
          $yesDash = substr($file, 0, strlen($file) - 6);
          $noDash = substr($file, 0, strlen($file) - 5);
          if(file_exists($directory . "/" . $yesDash . "2.cue") || file_exists($directory . "/" . $noDash . "2.cue")){
            $goodCue = multiMove($directory, $trashBin, $file);

          }else
          // loops through cuefile itself to see if real album title matches with cue file name
          {
            $cue = file($directory . "/" . $file, FILE_IGNORE_NEW_LINES);
            $album = '';
            foreach($cue as $line){
              if(preg_match( '/^\a*FILE/', $line ) === 1 ){
                // $list - array of $add_folder split between every \
                $list = preg_split("/\\\/", $line);

                $album = $list[count($list) - 2];
              }

            }
            if(!($album == $title)){
              $goodCue = multiMove($directory, $trashBin, $file);
            }
          }

        }else{
          $goodCue = $file;

        }
    }

  }

  closedir($dir);

  //
  $dir = opendir($directory);
  while(($file = readdir($dir)) !== false){
    // NOTE $file is  name.cue
    if(getSuffix($file) === "cue"){
      cleanCue($directory, $file);
    }
  }

  closedir($dir);

}

// function multiMove($directory, $trashBin, $hasDash)
//  $directory - target directory in which the cue files lie
//  $trashBin - target directory for old cue files
//  $file - tells which file we are looking at that caused multiMove
// returns $var.cue
//
// multiMove funcion - moves the cue files of a multi CD into one cuefile with proper track numbers
function multiMove($directory, $trashBin, $file){
  $trackNum = 2;

  // checks for dash in cue file title first, goes into if statement if it does have a dash
  if(preg_match("/-\d.cue$/", $file)){
    // gets album title
    $album = substr($file, 0, strlen($file) - 6);

    // starting number for cue files
    $index =  1;

    // $track is the track number of the album
    $track = 0;

    // combined cue file
    $newCue = file($directory . "/" . $album . "-" . $index . ".cue", FILE_IGNORE_NEW_LINES);

    // checks track numbering format
    foreach($newCue as $line){
      if(preg_match ( '/^\a*FILE/', $line ) === 1 ){
        // $list - array of $add_folder split between every \
        $list = preg_split("/\\\/", $line);

        $song = $list[count($list) - 1];
        if(preg_match("/\d\d\d/", $song))
          $trackNum = 3;

        // incriments the track number as it finds songs
        $track++;
      }
    }

    // moves the old bad cue file to the trash directory
    rename($directory . "/" . $album . "-" . $index . ".cue", $trashBin . "/" . $album . "-" . $index . ".cue");

    $index++;

    // compiles all cue files of album into one
    while(file_exists($directory . "/" . $album . "-" . $index . ".cue")){
      array_push($newCue, "REM CD " . $index);

      // reads next cue file into an array
      $nextCue = file($directory . "/" . $album . "-" . $index . ".cue", FILE_IGNORE_NEW_LINES);

      // cuts off the beginning part of a cue file up to the first FILE line
      $cutLength = 0;
      while(!preg_match( '/^\a*FILE/', $nextCue[$cutLength] )){
        $cutLength += 1;
      }

      array_splice($nextCue, 0, $cutLength);

      // changes the TRACK number to correct track number
      $track = changeTracks($track, $trackNum, $nextCue);

      // combines cue files together with $nextCue being the second one
      $newCue = array_merge($newCue, $nextCue);

      // moves the old bad cue file to the trash directory
      rename($directory . "/" . $album . "-" . $index . ".cue", $trashBin . "/" . $album . "-" . $index . ".cue");

      $index++;
    }

    // checks to see if ripper made cue file that's just album title
    if(file_exists($directory . "/" . $album . ".cue")){
      array_push($newCue, "REM CD " . $index++);

      $nextCue = file($directory . "/" . $album . ".cue", FILE_IGNORE_NEW_LINES);

      // cuts off the beginning part of a cue file up to the first FILE line
      $cutLength = 0;
      while(!preg_match( '/^\a*FILE/', $nextCue[$cutLength] )){
        $cutLength += 1;
      }

      array_splice($nextCue, 0, $cutLength);

      // changes the TRACK number to correct track number
      $track = changeTracks($track, $trackNum, $nextCue);

      // combines cue files together with $nextCue being the second one
      $newCue = array_merge($newCue, $nextCue);

      // moves the old bad cue file to the trash directory
      rename($directory . "/" . $album . ".cue", $trashBin . "/" . $album . ".cue");

    }

    // adds lines to cue file array
    addLines($newCue);

    // puts $newCue into an actual cue file
    $finished = file_put_contents($directory . "/" . $album . ".cue", $newCue);

    // returns $finished as fully made cue file
    return $finished;

  }else
// This is WITHOUT a dash
  {
    // gets album title
    $album = substr($file, 0, strlen($file) - 5);


    // starting number for cue files
    $index =  1;

    // $track is the track number of the album
    $track = 0;

    // combined cue file
    $newCue = file($directory . "/" . $album . $index . ".cue", FILE_IGNORE_NEW_LINES);

    // checks track numbering format
    foreach($newCue as $line){
      if(preg_match ( '/^\a*FILE/', $line ) === 1 ){
        // $list - array of $add_folder split between every \
        $list = preg_split("/\\\/", $line);

        $song = $list[count($list) - 1];
        if(preg_match("/\d\d\d/", $song))
          $trackNum = 3;

        // incriments the track number as it finds songs
        $track++;
      }
    }

    // moves the old bad cue file to the trash directory
    rename($directory . "/" . $album . $index . ".cue", $trashBin . "/" . $album . $index . ".cue");

    $index++;

    // compiles all cue files of album into one
    while(file_exists($directory . "/" . $album . $index . ".cue")){
      array_push($newCue, "REM CD " . $index);

      // reads next cue file into an array
      $nextCue = file($directory . "/" . $album . $index . ".cue", FILE_IGNORE_NEW_LINES);

      // cuts off the beginning part of a cue file up to the first FILE line
      $cutLength = 0;
      while(!preg_match( '/^\a*FILE/', $nextCue[$cutLength] )){
        $cutLength += 1;
      }

      array_splice($nextCue, 0, $cutLength);

      // changes the TRACK number to correct track number
      $track = changeTracks($track, $trackNum, $nextCue);

      // combines cue files together with $nextCue being the second one
      $newCue = array_merge($newCue, $nextCue);

      // moves the old bad cue file to the trash directory
      rename($directory . "/" . $album . $index . ".cue", $trashBin . "/" . $album . $index . ".cue");

      $index++;
    }

    // checks to see if ripper made cue file that's just album title
    if(file_exists($directory . "/" . $album . ".cue")){
      array_push($newCue, "REM CD " . $index++);

      $nextCue = file($directory . "/" . $album . ".cue", FILE_IGNORE_NEW_LINES);

      // cuts off the beginning part of a cue file up to the first FILE line
      $cutLength = 0;
      while(!preg_match( '/^\a*FILE/', $nextCue[$cutLength] )){
        $cutLength += 1;
      }

      array_splice($nextCue, 0, $cutLength);

      // changes the TRACK number to correct track number
      $track = changeTracks($track, $trackNum, $nextCue);

      // combines cue files together with $nextCue being the second one
      $newCue = array_merge($newCue, $nextCue);

      // moves the old bad cue file to the trash directory
      rename($directory . "/" . $album . ".cue", $trashBin . "/" . $album . ".cue");
    }

    // adds lines to cue file array
    addLines($newCue);

    // puts $newCue into an actual cue file
    $finished = file_put_contents($directory . "/" . $album . ".cue", $newCue);

    // returns $finished as fully made cue file
    return $finished;

  }


}

// function addLines(&$array)
//  &$array - array of strings that will make up a file
//  NOTE &$array is a reference variable
// returns nothing
//
// addLines function - adds \n (aka line breaks) to an array of strings in order for it to be passed
//   into a cue file correctly
function addLines(&$array){
  for($i = 0; $i < count($array); $i++){
    $array[$i] .= "\r\n";
  }
}

// function changeTracks(&$track, &$array)
//  &$track - track number of the multi CD disk
//  &$trackNum - var givin to tell whether or not the track is using 2 digit number or 3 digit
//  &$array - array of strings which make up cue file
//  NOTE &$track, &$trackNum and &$array are reference variables
// returns $track, an int that is the track number that the function ended on
//
// changeTracks function - correctly changes the track number of the cd to the track of the entire album
function changeTracks(&$track, &$trackNum, &$array){
  for($i = 0; $i < count($array); $i++){
    $numTrack = $track + 1;
    if(preg_match('/^\s\sTRACK/', $array[$i]) && $trackNum = 2 && $track > 9){
      // $list - array of $add_folder split between every \
      $list = preg_split("/\\\/", $array[$i-1]);

      $song = $list[count($list) - 1];

      // checks if the track number in the FILE line matches the track number in TRACK
      if(!preg_match("/^0*{$numTrack}/", $song)){
        plog("ERROR: FILE song number does not match TRACK number");
        plog("\t{$array[$i-1]}");
        plog("\t  TRACK {$numTrack} AUDIO");
      }

      $array[$i] = "  TRACK " . $numTrack . " AUDIO";
      $track++;

    }else if(preg_match('/^\s\sTRACK/', $array[$i]) && $trackNum = 3 && $track > 9 && $track < 100){
      // $list - array of $add_folder split between every \
      $list = preg_split("/\\\/", $array[$i-1]);

      $song = $list[count($list) - 1];

      // checks if the track number in the FILE line matches the track number in TRACK
      if(!preg_match("/^0*{$numTrack}/", $song)){
        plog("ERROR: FILE song number does not match TRACK number");
        plog("\t{$array[$i-1]}");
        plog("\t  TRACK 0{$numTrack} AUDIO");
      }

      $array[$i] = "  TRACK 0" . $numTrack . " AUDIO";
      $track++;

    }else if(preg_match('/^\s\sTRACK/', $array[$i]) && $trackNum = 3 && $track > 100){
      // $list - array of $add_folder split between every \
      $list = preg_split("/\\\/", $array[$i-1]);

      $song = $list[count($list) - 1];

      // checks if the track number in the FILE line matches the track number in TRACK
      if(!preg_match("/^0*{$numTrack}/", $song)){
        plog("ERROR: FILE song number does not match TRACK number");
        plog("\t{$array[$i-1]}");
        plog("\t  TRACK {$numTrack} AUDIO");
      }

      $array[$i] = "  TRACK " . $numTrack . " AUDIO";
      $track++;

    }else if(preg_match('/^\s\sTRACK/', $array[$i]) && $trackNum = 2 && $track < 10){
      // $list - array of $add_folder split between every \
      $list = preg_split("/\\\/", $array[$i-1]);

      $song = $list[count($list) - 1];

      // checks if the track number in the FILE line matches the track number in TRACK
      if(!preg_match("/^0*{$numTrack}/", $song)){
        plog("ERROR: FILE song number does not match TRACK number");
        plog("\t{$array[$i-1]}");
        plog("\t  TRACK 0{$numTrack} AUDIO");
      }

      $array[$i] = "  TRACK 0" . $numTrack . " AUDIO";
      $track++;

    }else if(preg_match('/^\s\sTRACK/', $array[$i]) && $trackNum = 3 && $track < 10){
      // $list - array of $add_folder split between every \
      $list = preg_split("/\\\/", $array[$i-1]);

      $song = $list[count($list) - 1];

      // checks if the track number in the FILE line matches the track number in TRACK
      if(!preg_match("/^0*{$numTrack}/", $song)){
        plog("ERROR: FILE song number does not match TRACK number");
        plog("\t{$array[$i-1]}");
        plog("\t  TRACK 00{$numTrack} AUDIO");
      }

      $array[$i] = "  TRACK 00" . $numTrack . " AUDIO";
      $track++;
    }
  }

  return $track;
}

// function cleanCue($directory, &$file)
//  $directory - the starting directory where all the cue files are sitting
//  &$file - the cue file that we are checking and cleaning up
//   NOTE $file must be compilation of all multi CDs of an album
//   NOTE &$file is a reference variable
// returns nothing
//
// cleanCue function - removes all \ from FILE lines of cue file, then puts files in correct artist/album directory
function cleanCue($directory, &$file){

  // $album and $artist are name holders to get cue file in correct directory
  $album = "";
  $artist = "";

  // sets opens the file
  $fixing = file($directory . "/" . $file, FILE_IGNORE_NEW_LINES);

  // deletes old cue file
  unlink($directory . "/" . $file);

  for($i = 0; $i < count($fixing); $i++){
    if(preg_match ( '/^\a*FILE/', $fixing[$i] ) && preg_match('/\\\/', $fixing[$i])){

      $list = preg_split("/\\\/", $fixing[$i]);

      // attaches propper song title and album title
      $song = $list[count($list) - 1];
      $album = $list[count($list) - 2];
      // artist requires more work because of FILE "
      $artist = $list[count($list) - 3];
      $artist = substr($artist, 6);

      // fixes FILE line to just song title
      $fixing[$i] = "FILE \"" . $song;

    }
  }

  // fix up array for turning back into file
  addLines($fixing);

  // puts $fixing back into a cue file
  $file = file_put_contents($directory . "/" . $artist . "/" . $album . "/" . $album . ".cue", $fixing);

  // verifies the cue file to make sure it's working well NOTE finish later

}

$testDir = "C:/Quentin/ReferenceMusic-RippingTool/0 Jazz";
$trashDir = "C:/Quentin/MusicWorking/MoveCuesTrash";

scanCues($testDir, $trashDir);



?>
