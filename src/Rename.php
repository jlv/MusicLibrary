<?php

  require "MusicRequire.inc";

  require "Rename.inc";

  logp_init("Rename", "");

  // function intro($currentDir, $oldDir, $newDir, $trackExcerpt)
  //  $oldDir - old album name to be changed
  //  $newDir - new name of album that user wants
  //  $trackExcerpt - part of tracks that the user wants the program to cut out
  //
  // intro function - takes in user input to confirm what is being renamed to what
  // returns 0 on failure
  function intro(){
    global $oldDir;
    global $newDir;
    global $trackExcerpt;

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

    // converts $trackExcerpt into proper regex
    $trackExcerpt = "/" . $trackExcerpt . "/";
    $trackExcerpt = preg_replace("/\./", "\\.", $trackExcerpt);
    $trackExcerpt = preg_replace("/\(/", "\\(", $trackExcerpt);
    $trackExcerpt = preg_replace("/\)/", "\\)", $trackExcerpt);

    // first, much check that $oldDir exists
    if(!is_dir($oldDir)){
      logp("error", "ERROR: given directory handle does not exist");
      return 0;
    }
    print $oldDir . "  ---->  " . $newDir . "\n";
    print "Is this correct rename? Y/N\n";
    $response = readline();
    if(strtoupper($response) == "Y"){

      print "Are you sure? Y/N\n";
      $response = readline();
      if(strtoupper($response) == "Y"){

        print "Affirmative. Is this correct trackExcerpt: {$trackExcerpt}? Y/N\n";
        $response = readline();

        if(strtoupper($response) == "Y"){

          print "Are you sure? Y/N\n";
          $response = readline();

          if(strtoupper($response) == "Y"){
            print "Affirmative. Checking new track names\n";

            // now confirms with user about the new track names
            $dir = opendir($oldDir);
            while(($file = readdir($dir)) !== false){
              if(getSuffix($file) === "wav"){
                $newWav = preg_replace($trackExcerpt, "", $file);
                print $file . "  ---->  " . $newWav . "\n";
              }
            }
            closedir($dir);
            print "Are these new track names correct? Y/N\n";
            $response = readline();

            if(strtoupper($response) == "Y"){

              print "Are you sure? Y/N\n";
              $response = readline();

              if(strtoupper($response) == "Y"){

                print "Affirmative. Commencing rename\n";
                editAlbum($oldDir, $newDir, $trackExcerpt);
                print "Finished Rename";

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

  // function editAlbum($currentDir, $oldDir, $newDir, $trackExcerpt)
  //  $oldDir - old album name to be changed
  //  $newDir - new name of album that user wants
  //  $trackExcerpt - part of tracks that the user wants the program to cut out. NOTE must be in regex form
  //
  // editAlbum function - renames user given directory to new user given directory
  // returns 0 on failure
  function editAlbum($oldDir, $newDir, $trackExcerpt){
    // first, renames $oldDir to $newDir
    rename($oldDir, $newDir);

    // next, creates $wav 2D array for renaming .wav files later
    $wav = array();

    // next, works on cuefile. Errors if no Cue file
    if(!file_exists($newDir . "/" . $oldDir . ".cue")){
      logp("error", "ERROR: .cue file does not exist");
      return 0;
    }
    $newCue = file($newDir . "/" . $oldDir . ".cue", FILE_IGNORE_NEW_LINES);

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

        $wav[$i]["old"] = $song;

        // fixes up FILE line and $song
        $newCue[$i] = preg_replace($trackExcerpt, '', $newCue[$i]);
        $goodSong = preg_replace($trackExcerpt, '', $song);

        $wav[$i]["new"] = $goodSong;
      }

    }

    addLines($newCue);

    // renames old .cue file
    rename($newDir . "/" . $oldDir . ".cue", $newDir . "/" . $oldDir . ".cue.old");

    // now puts $newCue back into a .cue file
    file_put_contents($newDir . "/" . $newDir . ".cue", $newCue);

    // now fixes .wav files in $newDir
    foreach ($wav as $index) {
      $goodWav = rename($newDir . "/" . $index["old"], $newDir . "/" . $index["new"]);
      if(!$goodWav){
        logp("error", "ERROR: failure on renaming .wav file");
      }
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

  intro();

 ?>
