<?php

  require "MusicRequire.inc";

  log_init("Rename");

  // function intro($currentDir, $oldDir, $newDir, $trackExcerpt)
  //  $currentDir - directory up to artist
  //  $oldDir - old album name to be changed
  //  $newDir - new name of album that user wants
  //  $trackExcerpt - part of tracks that the user wants the program to cut out. NOTE must be in regex form
  //
  // intro function - takes in user input to confirm what is being renamed to what
  // returns 0 on failure
  function intro(){
    // asks and gets $currentDir, $oldDir, $newDir, $trackExcerpt from user
    $currentDir = readline("What is currentDir (directory up to and including artist)? ");
    $oldDir = readline("What is oldDir (the old album name)? ");
    $newDir = readline("What is newDir (the new album name)? If you don't want to rename the directory, retype oldDir. ");
    $trackExcerpt   = readline("What do you want to remove from all tracks in album? ");

    // converts $trackExcerpt into proper regex
    $trackExcerpt = "/" . $trackExcerpt . "/";
    $trackExcerpt = preg_replace("/\./", "\\.", $trackExcerpt);
    $trackExcerpt = preg_replace("/\(/", "\\(", $trackExcerpt);
    $trackExcerpt = preg_replace("/\)/", "\\)", $trackExcerpt);

    // first, much check that $oldDir exists
    if(!is_dir($currentDir . "/" . $oldDir)){
      plog("ERROR: given directory handle does not exist");
      return 0;
    }
    print $currentDir . "/" . $oldDir . "  ---->  " . $currentDir . "/" . $newDir . "\n";
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
            $dir = opendir($currentDir . "/" . $oldDir);
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
              print "Affirmative. Commencing rename";
              editAlbum($currentDir, $oldDir, $newDir, $trackExcerpt);

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
  //  $currentDir - directory up to artist
  //  $oldDir - old album name to be changed
  //  $newDir - new name of album that user wants
  //  $trackExcerpt - part of tracks that the user wants the program to cut out. NOTE must be in regex form
  //
  // editAlbum function - renames user given directory to new user given directory
  // returns 0 on failure
  function editAlbum($currentDir, $oldDir, $newDir, $trackExcerpt){

  }

  intro();

 ?>
