<?php

require "MusicRequire.inc";

require "MultiDisk.inc";

log_init("MultiDist");

// function intro($currentDir, $oldDir, $newDir, $trackExcerpt)
// $finalDir - directory of combined multi-disk
// $multiDisks - array of all dist add ones (i.e. Disc 1, (Disc 1), or nothing)
//
// intro function - takes in user input to confirm what is being renamed to what
//                  NOTE - user must be in artist directory for system to work
// returns 0 on failure
function intro(){
  global $finalDir;
  global $multiDisks;

  // defines readline function if it doesn't exist
  if(!function_exists("readline")) {
    function readline($prompt = null){
      if($prompt){
          echo $prompt;
      }
      $fp = fopen("php://stdin","r");
      $line = rtrim(fgets($fp, 1024));
      return $line;
    }
  }

  // checks that all cds actually exist
  foreach($multiDisks as $disk){
    if(!is_dir($disk)){
      plog("ERROR: given directory handle does not exist: {$disk}");
      return 0;
    }
  }

  // checks with user about $finalDir and $multiDisks
  print "Is this finalDir: " . $finalDir . "? Y/N\n";
  $response = readline();
  if(strtoupper($response) == "Y"){

    print "Are you sure? Y/N\n";
    $response = readline();

    if(strtoupper($response) == "Y"){

      print "Are these the multiple disks? Y/N\n";
      foreach($multiDisks as $disk){
        print $disk . "\n";
      }
      $response = readline();

      if(strtoupper($response) == "Y"){

        print "Are you sure? Y/N\n";
        $response = readline();

        if(strtoupper($response) == "Y"){

          print "Affirmative: Comencing combine\n";
          combine($finalDir, $multiDisks);

        }else if(strtoupper($response) == "N"){
          print "Affirmative. Rename Aborted";
          return 0;
        }else{
          print "Invalid input. Rename Aborted";
          return 0;
        }

      }else if(strtoupper($response) == "N"){
        print "Affirmative. Rename Aborted";
        return 0;
      }else{
        print "Invalid input. Rename Aborted";
        return 0;
      }

    }else if(strtoupper($response) == "N"){
      print "Affirmative. Rename Aborted";
      return 0;
    }else{
      print "Invalid input. Rename Aborted";
      return 0;
    }

  }else if(strtoupper($response) == "N"){
    print "Affirmative. Rename Aborted";
    return 0;
  }else{
    print "Invalid input. Rename Aborted";
    return 0;
  }
}

// function combine($currentDir, $oldDir, $newDir, $trackExcerpt)
// $finalDir - directory of combined multi-disk
// $multiDisks - array of all dist add ones (i.e. Disc 1, (Disc 1), or nothing)
//
// combine function - actually combines the multiple disks into one directory as $finalDir
//                    NOTE - all directories must be in new, correct format
// returns 0 on failure
function combine($finalDir, $multiDisks){
  // creates $finalDir if it doesn't exist
  if(!is_dir($finalDir)){
    mkdir($finalDir);
  }
  // all variables needed to combine $multiDisks
  $newCue = array();
  $trackNum = 1;
  // uses foreach to go through $multiDisks and the .cue files. May need to change
  foreach($multiDisks as $num => $disk){
    $oldCue = file($disk . "/" . $disk . ".cue", FILE_IGNORE_NEW_LINES);
    if($num != 0){
      $cdNum = $num + 1;
      array_push($newCue, "REM CD " . $cdNum);
      // cuts off the beginning part of a cue file up to the first FILE line
      $cutLength = 0;
      while(!preg_match( '/^\a*FILE/', $oldCue[$cutLength] )){
        $cutLength += 1;
      }

      array_splice($oldCue, 0, $cutLength);
    }else{
      if(!$changedAlbum && preg_match("/TITLE/", $oldCue[$i])){
        $oldCue[$i] = "TITLE \"" . $disk . "\"";
        $changedAlbum = true;
      }
    }

    // $changedAlbum lets turns true when the first TITLE (aka title of Album) has been changed
    $changedAlbum = false;
    // puts together the track and changes .wav
    for($i = 0; $i < count($oldCue); $i++){

      if(preg_match ( '/^\a*FILE/', $oldCue[$i] ) === 1){
        $isTwoDigit = true;
        // $song is just the song title with no FILE
        $song = preg_replace("/FILE \"/", '', $oldCue[$i]);
        $song = preg_replace("/\" WAVE/", '', $song);

        if(preg_match("/^\d\d\d /", $song)){
          $isTwoDigit = false;
        }

        $newSong = "";
        if($trackNum < 10 && $isTwoDigit){
          // renaming the old .wav file to new .wav file in $finalDir
          $newSong = preg_replace("/^\d\d/", "0{$trackNum}", $song);
          // renames FILE line
          $oldCue[$i] = preg_replace("/\"\d\d /", "\"0{$trackNum} ", $oldCue[$i]);
          // changes the TRACK number
          $oldCue[$i+1] = preg_replace("/\d\d /", "0{$trackNum} ", $oldCue[$i+1]);
        }else if($trackNum >= 10 && $isTwoDigit){
          // renaming the old .wav file to new .wav file in $finalDir
          $newSong = preg_replace("/^\d\d/", "{$trackNum}", $song);
          // renames FILE line
          $oldCue[$i] = preg_replace("/\"\d\d /", "\"{$trackNum} ", $oldCue[$i]);
          // changes the TRACK number
          $oldCue[$i+1] = preg_replace("/\d\d /", "{$trackNum} ", $oldCue[$i+1]);
        }else if($trackNum < 10 && !$isTwoDigit){
          // renaming the old .wav file to new .wav file in $finalDir
          $newSong = preg_replace("/^\d\d\d/", "00{$trackNum}", $song);
          // renames FILE line
          $oldCue[$i] = preg_replace("/\"\d\d\d /", "\"00{$trackNum} ", $oldCue[$i]);
          // changes the TRACK number
          $oldCue[$i+1] = preg_replace("/\d\d\d /", "00{$trackNum} ", $oldCue[$i+1]);
        }else if($trackNum >= 10 && $trackNum < 100 && !$isTwoDigit){
          // renaming the old .wav file to new .wav file in $finalDir
          $newSong = preg_replace("/^\d\d\d/", "0{$trackNum}", $song);
          // renames FILE line
          $oldCue[$i] = preg_replace("/\"\d\d\d /", "\"0{$trackNum} ", $oldCue[$i]);
          // changes the TRACK number
          $oldCue[$i+1] = preg_replace("/\d\d\d /", "0{$trackNum} ", $oldCue[$i+1]);
        }else if($trackNum >= 100 && !$isTwoDigit){
          // renaming the old .wav file to new .wav file in $finalDir
          $newSong = preg_replace("/^\d\d\d/", "{$trackNum}", $song);
          // renames FILE line
          $oldCue[$i] = preg_replace("/\"\d\d\d /", "\"{$trackNum} ", $oldCue[$i]);
          // changes the TRACK number
          $oldCue[$i+1] = preg_replace("/\d\d\d /", "{$trackNum} ", $oldCue[$i+1]);
        }

        if($newSong == ""){
          plog("ERROR: Failure on changing song title in {$disk}");
          return 0;
        }

        $goodWav = rename($disk . "/" . $song, $finalDir . "/" . $newSong);
        if(!$goodWav){
          plog("ERROR: Could not rename {$song} as {$newSong}");
          return 0;
        }

        $trackNum++;
      }

    }

    // now cue should be all good to add and discard $oldCue
    $newCue = array_merge($newCue, $oldCue);
    unlink($disk . "/" . $disk . ".cue");

    // must check if $disk = $finalDir because this will happen sometimes
    if($disk != $finalDir){
      // sees if $disk has any other files in it and moves them to $finalDir
      $dir = opendir($disk);
      while(($file = readdir($dir)) !== false){
        if(getSuffix($file) === "cue" || getSuffix($file) === "wav"){
          plog("ERROR: Found {$file} still in {$disk} when it should have been moved/deleted");
          return 0;
        }else if($file != "." && $file != ".."){
          $cdNum = $num + 1;
          $end = getSuffix($file);
          $name = preg_replace("/\....$/", "", $file);
          rename($disk . "/" . $file, $finalDir . "/" . $name . "-" . $cdNum . "." . $end);
        }
      }
      closedir($dir);

      // deletes $disk directory
      unlink($disk);
    }

  }

  // puts $newCue back into a .cue file and puts it in the $finalDir folder
  addLines($newCue);
  file_put_contents($finalDir . "/" . $finalDir . ".cue", $newCue);
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

intro();

 ?>
