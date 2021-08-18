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
  function cueFix($base_folder, $add_folder, $file){

  }
 ?>
