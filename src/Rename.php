<?php

  // functional definition:
  // rename directory and cuefile to new filename specified in Rename.inc

  require "MusicRequire.inc";
  logp_init("Rename", "", "echo[error],echo[info]");

  // check if Rename.inc exists in local directory, then require if exists
  if (file_exists("Rename.inc"))
    require "./Rename.inc";
  else
  {
    logp ("echo,error,exit1",
          "Error: Could not find variables file \"Rename.inc\" in working directory. Exiting.");
  }

  // function intro($currentDir, $oldDir, $newDir, $trackExcerpt)
  //  $oldDir - old album name to be changed
  //  $newDir - new name of album that user wants
  //  $trackExcerpt - part of tracks that the user wants the program to cut out
  //
  // intro function - takes in user input to confirm what is being renamed to what
  // returns 0 on failure
  function intro() {
    global $oldDir;
    global $newDir;
    global $trackExcerpt;

    // converts $trackExcerpt into proper regex
    $trackExcerpt = "/" . $trackExcerpt . "/";
    $trackExcerpt = preg_replace("/\./", "\\.", $trackExcerpt);
    $trackExcerpt = preg_replace("/\(/", "\\(", $trackExcerpt);
    $trackExcerpt = preg_replace("/\)/", "\\)", $trackExcerpt);

    // first, much check that $oldDir exists
    if(!is_dir($oldDir))
      logp("error,exit1", "FATAL ERROR: source directory '{$oldDir}' does not exist. Exiting.");

    // display parameters
    print $oldDir . "  ---->  " . "\n    " . $newDir . "\n";
    print "Track Excerpt: {$trackExcerpt}\n\n";

    // track conversion
    print "Track changes:";
    $dir = opendir($oldDir);
    while(($file = readdir($dir)) !== false){
      if(getSuffix($file) === "wav"){
        $newWav = preg_replace($trackExcerpt, "", $file);
        print "  " . $file . "  ---->  " . "\n    " . $newWav . "\n";
      }
    }
    closedir($dir);

    $response = readline("\nAre these new track names correct? >");
    if(strtoupper($response) != "Y") exit();

    $response = readline("\nAre you sure? >");
    if(strtoupper($response) != "Y") exit();

    return true;
  }

  // function editAlbum($currentDir, $oldDir, $newDir, $trackExcerpt)
  //  $oldDir - old album name to be changed
  //  $newDir - new name of album that user wants
  //  $trackExcerpt - part of tracks that the user wants the program to cut out. NOTE must be in regex form
  //
  // editAlbum function - renames user given directory to new user given directory
  // returns 0 on failure
  function editAlbum($oldDir, $newDir, $trackExcerpt){
    // first, renames $oldDir to $newDir
//    rename($oldDir, $newDir);

    // next, creates $wav 2D array for renaming .wav files later
    $wav = array();

    // next, works on cuefile. Errors if no Cue file
    $oldcue=$oldDir . "/" . $oldDir . ".cue";
    if(!file_exists($oldcue))
      logp("error,exit1", "ERROR: .cue file does not exist in {$oldDir}");
    $newCue = file($oldcue, FILE_IGNORE_NEW_LINES);

    // $changedAlbum lets us know if we already changed the album title so that TITLE of tracks remains the same
    $changedAlbum = false;
    for($i = 0; $i < count($newCue); $i++){
      if(!$changedAlbum && preg_match("/TITLE/", $newCue[$i])){
        $newCue[$i] = "TITLE \"" . $newDir . "\"";
        $changedAlbum = true;
      }

      // begins looking at FILE and fixing the titles
      if(preg_match ( '/^\a*FILE/', $newCue[$i] ) === 1){
        $wav[$i] = array();
        // $song is just the song title with no FILE
        $song = preg_replace("/FILE \"/", '', $newCue[$i]);
        $song = preg_replace("/\" WAVE/", '', $song);

        // for legacy artist\Album, replace everything up to backslash
        $song = preg_replace("/^.*\\\/", '', $song);

        $wav[$i]["old"] = $song;

        // for legacy, replace oldDir with newDir
        $newCue[$i] = str_replace("\\{$oldDir}\\","\\{$newDir}\\",$newCue[$i]);

        // fixes up FILE line and $song
        $newCue[$i] = preg_replace($trackExcerpt, '', $newCue[$i]);


        // edit song
        $goodSong = preg_replace($trackExcerpt, '', $song);
        $wav[$i]["new"] = $goodSong;
      }

    } // end of for

    // add line termination
    addLineTerm($newCue);
    if (! file_put_contents($oldcue . ".rename", $newCue))
      logp("error,exit1","FATAL ERROR: could not write rename cue file: '{$oldcue}.rename'");

    if(isDryRun())
    {
      logp("info", "DryRun: rename '{$oldDir}.cue' as '{$oldDir}.cue.pre-rename'");
      logp("info", "DryRun: rename directory '{$oldDir}' as '{$newDir}'");
    } else {
      //
      // rename old .cue file
      if (! rename($oldcue, $oldcue . ".pre-rename"))
        logp("error,exit1","FATAL ERROR: could not rename cue file: '{$oldcue}'");

      // rename directories
      if (! rename($oldDir, $newDir))
        logp("error,exit1","FATAL ERROR: could not rename directory, '{$oldDir}' to '{$newDir}'");

      // rename .rename file
      if (! rename($newDir . "/" . $oldDir . ".cue.rename", $newDir . "/" . $newDir . ".cue"))
          logp("error,exit1","FATAL ERROR: could not rename cue file: '{$newDir}/{$oldDir}.cue.rename'");

      logp("log","Renamed {$newDir}/{$oldDir}.cue.rename to {$newDir}/{$newDir}.cue");

    } // isDry


    // rename wav files
    if (isDryRun()) chdir($oldDir); else chdir($newDir);
    fixWav($wav);

    logp("info",array("Rename complete: {$oldDir} ->", "  {$newDir}"));

    // foreach ($wav as $index) {
    //   if(isDryRun()){
    //     logp("notify,echo", "Would be renaming {$index["old"]} as {$index["new"]}");
    //   }else{
    //     $goodWav = rename($newDir . "/" . $index["old"], $newDir . "/" . $index["new"]);
    //     if(! $goodWav)
    //       logp("error,echo", "ERROR: failure on renaming {$index["old"]} file");
    //     else
    //       logp("log", "Renamed file {$index["old"]} as {$index["new"]}");
    //     }
    //   }
    //}
  } // function

  intro();

  print "\nCommencing rename...\n";
  editAlbum($oldDir, $newDir, $trackExcerpt);
  print "  ...finished Rename\n\n";

 ?>
