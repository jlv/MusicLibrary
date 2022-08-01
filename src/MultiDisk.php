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
$wav = array();
$trash = array();


// setup the merge
if (! setupMultiMerge($cuefile, $cue_meta, $wav, $trash, "fixup"))
  logp("error,exit1","FATAL ERROR: setup multi disk merge failed.");

// confirm parameters with user
if (! confirmMerge($cuefile, $wav)) exit;

// execute
if (! executeMerge($cuefile, $wav, $trash)) exit;

logp("echo,exit0","MultiDisk successfully merged directories.  See logs for details.");

// end of script

// Basic flow:
//  Find files, check artist/album, load in cuefile
//  Trackify fixup to load files in $wav and fix tracks on each cuefile
//  Load each cuefile path into $trash for later processing
//  Trackify again reorder without fixup once we have every disk loaded


function setupMultiMerge(&$cuefile, &$cue_meta, &$wav, &$trash, $options) {
  // globals from parameter
  global $finalDir;
  global $multiDisks;
  $gartist = "";
  $galbum = "";
  $ncuefile=array();
  $disc_first = TRUE;

  // loop through each dir/name, reading file and merging to array
  foreach ($multiDisks as $disc) {
    //
    $cuepath = $disc . "/" . $disc . ".cue";
//print "\nNCUE path:{$cuepath}\n";

    // get ncuefile
    if (! file_exists($cuepath))
      logp("error,exit1","FATAL ERROR: cannot find cuefile '{$cuepath}'.");

    // read file
    if (($ncuefile = file($cuepath, FILE_IGNORE_NEW_LINES)) == FALSE)
      logp("error,exit1","FATAL ERROR: cannot open '{$cuepath}'.");

    // get artist from cue file (errors will have already displayed)
    if (($artist = getCueInfo("artist", $cuepath)) == FALSE)
      logp("error,exit1",array("FATAL ERROR: could not find artist in cuefile",
                                 "  {$cuepath}"));

    // get album from cue file (errors will have already displayed)
    if (($album = getCueInfo("album", $cuepath)) == FALSE)
      logp("error,exit1",array("FATAL ERROR: could not find album in cuefile",
                                 "  {$cuepath}"));

    // compare with $disc
    if ($album != $disc)
      logp("error,exit1", array(
                  "FATAL ERROR: album in cuefile does not match album name (directory).",
                  "  Album in file: '{$album}'",
                  "  Directory/name: '${disc}'",
                  "  Cuefile read: '{$cuepath}'"));

    // assign or compare to global artist
    if ($disc_first == TRUE) $gartist = $artist;
    elseif ($artist != $gartist)
      logp("error,exit1", array(
                  "FATAL ERROR: artist in cuefile does not match artist in first file.",
                  "  Artist in file: '{$artist}'",
                  "  Artist in first file: '${gartist}'",
                  "  Cuefile read: '{$cuepath}'"));

    // trackify Cue
//    if (! trackifyCue($ncuefile, $wav, "fixup")) exit 1;

    // load ncuefile into cuefile, first disc vs. others
    if ($disc_first == TRUE) {
      $disc_first = FALSE;

      $cuefile = $ncuefile;
      $index = count($cuefile);

      // fill cue_meta[index][dir]
      $title_found = FALSE;
      for($i=0; $i < $index; $i++)  {
        $cue_meta[$i]["dir"] = $disc;
        $cue_meta[$i]["album"] = $disc;

        // manually replace album title (first TITLE)
        if ($title_found == FALSE && preg_match("/^\s*TITLE\s/",$cuefile[$i])) {
//          print "**** IN title '{$finalDir}'\n";
          $cuefile[$i] = preg_replace("/^(\s*TITLE\s+\")(.*)(\".*)$/",
                        '${1}' . $finalDir . '${3}',  $cuefile[$i]);
          $title_found = TRUE;
        }
      } // for $i loop

    } else {
      // iterate through each line of file, find first FILE directive, then add
      $fileFound = FALSE;

      // cleanup file first
//      if (! processFILEtag($artist . "/" . $album, $cuefile, $cue_meta, $wav))
//        logp("error,exit1", "ERROR: processFILEtag returned error.");

      foreach($ncuefile as $nline)  {
        // mark when we get to FILE
        if (preg_match( '/^\a*FILE/', $nline )) $fileFound = TRUE;

        if ($fileFound == TRUE ) {
          $cuefile[$index] = $nline;
          $cue_meta[$index]["dir"] = $disc;
          $cue_meta[$index]["album"] = $disc;
          $index++;
        }
      }  // end of foreach
    } // else from $dist_first

    // add cuefile path to trash
    $trash[] = $cuepath;

  }  // foreach multidisc

  // process FILE statements
  if (! processFILEtag($artist . "/" . $album, $cuefile, $cue_meta, $wav, "fixup"))
    logp("error,exit1", "ERROR: processFILEtag returned error.");

  // add finalDir
  for($i=0; $i < count($wav); $i++)
    $wav[$i]["new_dir"] = $finalDir;

  // trackify entire file new file
  if (! trackifyCue($cuefile, $wav, "reorder"))
    logp("error,exit1", "ERROR: trackifyCue returned error.");

//print_r($cuefile);
//print_r($wav);

  return TRUE;
} // end of setupMultiMerge function




// function confirmMerge($cuefile, $wav)
//
//
// $finalDir - directory of combined multi-disk
// $multiDisks - array of all dist add ones (i.e. Disc 1, (Disc 1), or nothing)
//
// intro function - takes in user input to confirm what is being renamed to what
//                  NOTE - user must be in artist directory for system to work
// returns 0 on failure

function confirmMerge($cuefile, $wav)  {
  global $finalDir;
  global $multiDisks;

print_r($wav);
  print "\n\n*** Confirming MultiDisk Details\n\n";

  // diskfreespace

  print "Disc Parameters:\n";
  // checks that all cds actually exist
  $i=1;
  foreach($multiDisks as $disk)
    print "  Disc " . $i++ . ":           {$disk}\n";

  print "  Final dir/artist: {$finalDir}\n";

  // confirm
  if (strtoupper(readline("\nConfirm >")) != "Y") exit;

  // show wav
  print "\n\nTrack Changes:\n\n";

  foreach ($wav as $song) {
    print "Dir:  {$song["old_dir"]}\nSong: {$song["old"]}\n";
    print " --> '{$song["new"]}'\n\n";
  }

  // confirm
  if (strtoupper(readline("\nConfirm >")) != "Y") exit;


  return TRUE;

}




function executeMerge(&$cuefile, &$wav, &$trash)  {
  // globals from parameter
  global $finalDir;

  $dir= getArtistFromCwd() . "/" . $finalDir;
  $newfile = $finalDir . ".cue";
  $newpath = $finalDir . "/" . $newfile;

  // make directory if needed
  if (! is_dir($finalDir))
    if (! mkdir($finalDir))
      logp("error,exit1","FATAL ERROR: could not make final Directory, '{$finalDir}'");

  if (file_exists($newfile))
    if ( ! rename($newpath, $newpath . ".pre-merge"))
      logp("error,exit1","FATAL ERROR: could not rename old cue '{$newpath}'");

  // make Convertable
  if (! makeCueConvertable($cuefile)) {
    return FALSE;
  }

  // add line termination
  addLineTerm($cuefile);

  // write candidate file
  if ( ! file_put_contents($newpath . ".cand", $cuefile))
    logp("error,exit1", array("FATAL ERROR: could not write candidate cuefile",
              "  '${newpath}.cand'"));

//print_r($cuefile);
  // verify current cuefile array, without file testing
  if (! verifyCue( '', $dir, $newfile, FALSE, $cuefile, TRUE))
    logp("error,exit1",
       "FATAL ERROR: proposed cuefile array did not verify.");

  // move songs (or test file existance if in check mode)
  if (! moveWav($wav))
    logp("error,exit1","FATAL ERROR: error moving wav files. Check logs.");

  // verify with files in place
  //  note hack to ../ on base dir so file works
  if (! isDryRun() && ! verifyCue( '..', $dir, $newfile . ".cand"))
    logp("error,exit1",
         "FATAL ERROR: proposed cuefile did not verify but wav files have been moved.");

  // rename candidate file
  if (! isDryRun()) {
    logp("log", "rename candidate to cue, '{$newpath}'");
    if ( ! rename($newpath . ".cand", $newpath))
      logp("error,exit1","FATAL ERROR: could not rename candidate '{$newpath}'");
  }  // dryRun

  // move to trash.  Note using parent as base trash directory
  moveToTrash($trash, $trashed, "..");

  return TRUE;
} // end of executeMerge function



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
