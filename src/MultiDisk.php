<?php

// functional definition


require "MusicRequire.inc";
logp_init("MultiDisk", "");

// check if Rename.inc exists in local directory, then require if exists
if (file_exists("./MultiDisk.inc"))
  require "./MultiDisk.inc";
else {
  logp ("echo,error,exit1",
        "FATAL ERROR: Could not find variables file \"Multidisk.inc\" in calling directory. Exiting");
}

// initialize globals
$cuefile = array();
$cue_meta = array();




// setup the merge

// confirm parameters with user


// execute



// Basic flow:
//  Find files, check artist/album, load in cuefile with metadata
//  Trackify and fixup where needed
//  Load each cuefile path into $trash for later processing
//


function setupMultiMerge(&$cuefile, &$cue_meta, &$wav, &$trash, $options)
  // globals from parameter
  global $finalDir;
  global $multiDisks;

  // locate and make if needed finalDir
//  if (! is_dir($finalDir))
//    if (! mkdir($fi))

  // loop through each dir/name, reading file and merging to array
  foreach ($multiDisks as $disc) {
    //
    // get ncuefile
    if (! file_exists($disc . "/" . $disc . ".cue")
      log("error,exit1","FATAL ERROR: cannot find cuefile '{$disk}/{$disc}.cue'.");

    if (($ncuefile = file)
      log("error,exit1","FATAL ERROR: cannot find cuefile '{$disk}/{$disc}.cue'.");

  }




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
      logp("error", "ERROR: given directory handle does not exist: {$disk}");
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
          logp("error", "ERROR: Failure on changing song title in {$disk}");
          return 0;
        }

        if(isDryRun()){
          logp("notify", "Would be renaming {$newSong}");
        }else{
          // actually moves the .wav file to $finalDir
          $goodWav = rename($disk . "/" . $song, $finalDir . "/" . $newSong);
          if(!$goodWav){
            logp("error", "ERROR: Could not rename {$song} as {$newSong}");
            return 0;
          }
        }

        $trackNum++;
      }

    }

    // now cue should be all good to add and discard $oldCue
    $newCue = array_merge($newCue, $oldCue);
    if(isDryRun()){
      logp("notify", "Would be deleting {$disk}.cue");
    }else{
      unlink($disk . "/" . $disk . ".cue");
    }

    // must check if $disk = $finalDir because this will happen sometimes
    if($disk != $finalDir){
      // sees if $disk has any other files in it and moves them to $finalDir
      $dir = opendir($disk);
      while(($file = readdir($dir)) !== false){
        if(getSuffix($file) === "cue" || getSuffix($file) === "wav"){
          logp("error", "ERROR: Found {$file} still in {$disk} when it should have been moved/deleted");
          return 0;
        }else if($file != "." && $file != ".."){
          if(isDryRun()){
            logp("notify", "Would be renaming {$disk}/{$file} as {$disk}/{$file}-{$cdNum}.{$end}");
          }else{
            $cdNum = $num + 1;
            $end = getSuffix($file);
            $name = preg_replace("/\....$/", "", $file);
            rename($disk . "/" . $file, $finalDir . "/" . $name . "-" . $cdNum . "." . $end);
          }
        }
      }
      closedir($dir);

      if(isDryRun()){
        logp("notify", "Would be deleting {$disk} folder");
      }else{
        // deletes $disk directory
        unlink($disk);
        logp("info", "{$disk} has been deleted. Moving onto next disk");
      }
    }
    logp("info", "{$finalDir} has been compiled. Function combine completed");

  }

  // puts $newCue back into a .cue file and puts it in the $finalDir folder
  addLineTerm($newCue);
  if(isDryRun()){
    logp("notify", "Would be making {$finalDir}.cue from $newCue");
  }else{
    file_put_contents($finalDir . "/" . $finalDir . ".cue", $newCue);
  }
}

//
// Main program
//

intro();

 ?>
