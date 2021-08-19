<?php

  require "MusicRequire.inc";

  log_init("ServerFix");

  // funtion serverFix($base_folder, $add_folder, $new_base_folder, $file, $options)
  //  $base_folder - initial root folder
  //  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
  //       full path name.  $add_folder can be blank to start (and usually is).  Used by
  //       recursive function to crawl.
  //  $new_base_folder - target base folder for functions that are moving/writing files
  //       from a base to a new_base
  //  $file - name of file passed to function
  //  $options - array of options passed to function
  //
  // serverFix function - fixes both cue file and associated .wav files of the music server
  //                        Fixes cue files first and then changes .wav files
  function serverFix($base_folder, $add_folder, $new_base_folder, $file, $options){
    // only looks at cue files
    if(preg_match('/\.cue/i', $file)){

      // checks to see if cue file needs any fixing
      if(!cueGood($base_folder, $add_folder, $file)){
        // if !cueGood, runs cueFix function
        cueFix($base_folder, $add_folder, $file);
      }
    }
  }

  // function cueGood($base_folder, $add_folder, $file)
  //  $base_folder - initial root folder
  //  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
  //       full path name.  $add_folder can be blank to start (and usually is).  Used by
  //       recursive function to crawl.
  //  $file - name of file passed to function
  //
  // cueGood function - verifys cue file
  //  returns true if cue file has no ERRORs, else returns false
  function cueGood($base_folder, $add_folder, $file){
    $cuefile = file($base_folder . '/' . $add_folder . '/' . $file, FILE_IGNORE_NEW_LINES);

    foreach ($cuefile as $input) {

      if (preg_match ( '/^\a*FILE/', $input ) === 1 )
      {
        //gets part just between quotes
        $title = preg_replace("/FILE \"/", '', $input);
        $title = preg_replace("/\".*$/", '', $title);

        //checks if it starts with a number and space
        $num2 = "/^\d{2,3} /";
        //checks for - after beginning number
        $character1 = "/^\d{2,3} -/";
        //checks if character after whitespace is non-whitespace
        $character = "/^\d{2,3} \s/";
        //checks for all other special characters
        $special = "/[~\?\*\+\[\]\(\)\{\}\^\$\|<>:;\/\"]/";
        //checks for ending in .wav
        $wav = "/\.wav/i";
        //checks if name exists in directory
        $fileExists = file_exists($base_folder . '/' . $add_folder . '/' . $title);

        //checks backslash
        if(preg_match('/\\\/', $title)){
          plog("ERROR: has \ in title");
          plog("\t{$base_folder}/{$add_folder}/{$file}");
          plog("\t{$title}");
        }

        //$num3 = "/\d\d\d /";
        else if(!preg_match($num2, $title)){
          return false;
        }

        //$character2 = "/\d\d\d -/";
        else if(preg_match($character1, $title)){
          return false;
        }

        else if(preg_match($character, $title)){
          return false;
        }

        //"
        else if(preg_match($special, $title)){
          return false;
        }

        else if(!preg_match($wav,$title)){
          return false;
        }

        else if(!$fileExists){
          return false;
        }

        else{
          return true;
        }
      }
  }

  // function cueFix($base_folder, $add_folder, $file)
  //  $base_folder - initial root folder
  //  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
  //       full path name.  $add_folder can be blank to start (and usually is).  Used by
  //       recursive function to crawl.
  //  $file - name of file passed to function
  //
  // cueFix function - actually changes the cue file given
  //  returns 0 if failure to fix file
  function cueFix($base_folder, $add_folder, $file){
    // $wav is a 2D aray of [tracknum][old/new]
    $wav = array();

    // $cuefile is array of outdated cue file
    $cuefile = file($base_folder . '/' . $add_folder . '/' . $file, FILE_IGNORE_NEW_LINES);

    for($i = 0; $i < count($cuefile); $i++){
      if(preg_match ( '/^\a*FILE/', $input ) === 1){
        // splits FILE line into artist, album, and song components
        $list = preg_split("/\\\/", $cuefile[$i]);
        $song = $list[count($list) - 1];
        $song = preg_replace("/\".*$/", '', $song);
        $album = $list[count($list) - 2];
        // artist requires more work because of FILE "
        $artist = $list[count($list) - 3];
        $artist = substr($artist, 6);

        // checks if in artist/album directory
        $checkDir = "/{$artist}\/{$album}/";
        if(!preg_match($checkDir, $add_folder)){
          plog("ERROR: cue file in incorrect directory");
        }

        // checks that .wav file exists for FILE line
        // Also checks for too long file name (i.e. NN~AAA~1.wav)
        $tooLong = "";
        $wavIndex = 0;
        if(preg_match("/^\d\d\d/", $song)){
          $tooLong = substr($song, 0, 3);
          $wavIndex = intval($tooLong);
          $wav[$wavIndex] = array();
        }else {
          $tooLong = substr($song, 0, 2);
          $wavIndex = intval($tooLong);
          $wav[$wavIndex] = array();
        }
        $tooLong += "-" . strtoupper(substr($artist, 0, 3)) . "~" . "1.wav";
        // as long as a song file exists, will assign old name of track to $wav array
        if(file_exists($base_folder . "/" . $add_folder . "/" . $song)){
          $wav[$wavIndex]["old"] = $song;
          $cuefile[$i] = fixFILE($base_folder, $add_folder, $cuefile[$i], $wav);
        }else if(file_exists($base_folder . "/" . $add_folder . "/" . $tooLong)){
          $wav[$wavIndex]["old"] = $toolong;
          $cuefile[$i] = fixFILE($base_folder, $add_folder, $cuefile[$i], $wav);
        }else{
          plog("ERROR: .wav file does not exist");
          return 0;
        }
      }
    }
  }

  // function fixFILE($base_folder, $add_folder, $file, &$wav)
  //  $base_folder - initial root folder
  //  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
  //       full path name.  $add_folder can be blank to start (and usually is).  Used by
  //       recursive function to crawl.
  //  $line - FILE line from .cue file
  //  &$wav - array of file names needed to rename .wav files in album
  //
  // fixFILe function - fixes the file line to new, correct standard
  // returns a string which is the correct line
  function fixFILE($base_folder, $add_folder, $line, &$wav){
    // first, must split $line again to gain neccessary components
    $list = preg_split("/\\\/", $line);
    $song = $list[count($list) - 1];
    $song = preg_replace("/\".*$/", '', $song);
    $album = $list[count($list) - 2];
    // artist requires more work because of FILE "
    $artist = $list[count($list) - 3];
    $artist = substr($artist, 6);

    // checks for ~ or - delimeter
    
  }

 ?>
