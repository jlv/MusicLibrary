<?php

  // functional definition

  require "MusicRequire.inc";

  logp_init("ServerFix", NULL, "echo[error],echo[info]");
  logp("log","ServerFix Beginning");

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
    // changes the directory to $base_folder because we will always stay in it
//    logp("log","ServerFix checking '{$base_folder}', '{$file}'");
    chdir($base_folder . '/' . $add_folder);

    // $list - array of $add_folder split between every /
    $list = preg_split("/\//", $add_folder);

    // $album - last input of $list, which will be the album title
    $album = $list[count($list) - 1];

    // if there is a .$album.nocue file, return out of function (.nocue signals no work in this directory)

    // checks if there is a cue file. if there isn't, ERROR
    //if(!file_exists($album . '.cue')){
    //  logp("error", "ERROR: no .cue file found associated with {$base_folder}/{$add_folder}/{$file}");
    //}
    if (! checkCueCovered($base_folder, $add_folder, "continue")) return TRUE;
    // {
    //   logp("error","ERROR: Returning. Failed checkCueCovered.");
    //   return false;
    // }

    // check for .jpg file and if it is named folder
//    if(preg_match("/\.jpg$/", $file))
    if (getSuffix($file) == "jpg")
      if ( ! file_exists("folder.jpg"))
        if ( rename($file, "folder.jpg"))
          logp("log", "JPG rename {$file} to folder.jpg in {$base_folder}/{$add_folder}");
        else
          logp("error", "ERROR: Failure on renaming {$file} to folder.jpg in {$base_folder}/{$add_folder}");

//      if(! preg_match("/folder.jpg/", $file))
//      {
//        $check = rename($file, "folder.jpg");
//      }
//      else
//      {
//        $check = true;
//      }
//      if($check === false){
//        logp("error", "Warning: Failure on renaming {$file}, Probably multiple .jpg in directory");
//      }
//    }

    // if cue file, check then fix if needed
//    if(preg_match('/\.cue$/i', $file)){
    if (getSuffix($file) == "cue")
      // checks to see if cue file needs any fixing
//      if(!cueGood($base_folder, $add_folder, $file)){
      if(! verifyCue($base_folder, $add_folder, $file, TRUE))  {
        // if !cueGood, runs cueFix function
        $checkSpecial = cueFileFix($base_folder, $add_folder, $file);
        // checks for fixCUE using cueFileFix
        if($checkSpecial === 1)
          logp("info", "Warning: fixCUE ran cueFileFix on {$base_folder}/{$add_folder}. Please confirm validity of new files");

      }

  }


  // function cueFileFix($base_folder, $add_folder, $file)
  //  $base_folder - initial root folder
  //  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
  //       full path name.  $add_folder can be blank to start (and usually is).  Used by
  //       recursive function to crawl.
  //  $line - FILE line from .cue file
  //
  // cueFileFix function - new fix function to see if it works. Go back to GitHub if there are big problems
  // returns 0 on failure

  function cueFileFix($base_folder, $add_folder, $file){
    logp("log", "cueFileFix starting on: {$base_folder}/{$add_folder}/{$file}");
    // $wav is a 2D aray of [tracknum][old/new/old_dir/new_dir]
    $wav = array();

    // $cuefile is array of orig cue file
//    $cuefile = file($file, FILE_IGNORE_NEW_LINES);

    // gets $artist and $album
    $list = preg_split("/\//", $add_folder);
    $album = $list[count($list) - 1];
    $artist = $list[count($list) - 2];

    // fixes () and . if they are in album/title
    $album = fixParens($album);
    $artist = fixParens($artist);
    $album = fixDot($album);
    $artist = fixDot($artist);
    $album = fixBrackets($album);
    $artist = fixBrackets($artist);

    // checks if in artist/album directory
    $checkDir = "/{$artist}\/{$album}/";
    if(! preg_match($checkDir, $add_folder)){
      logp("error,info", "ERROR: cue file in incorrect directory '{$add_folder}'");
      return false;
    }

    // $cuefile is array of current cue file
    $cuefile = file($file, FILE_IGNORE_NEW_LINES);
    if ( $cuefile === false )
      logp("error,exit1","FATAL ERROR: could not read cue file '{$file}'. Exiting.");

    // check if over 99 tracks and set $pad
    $count_arr = countTracks($cuefile);
    if ($count_arr["return"] =! TRUE) return FALSE;
    $pad = $count_arr["max_pad"];

//    print_r($count_arr);
//    $foo=7;
//    print "pad:" . str_pad($foo,$count_arr["cnt_pad"],"0",STR_PAD_LEFT);

    // crawls through lines in $cuefile
    for($i = 0; $i < count($cuefile); $i++){

      // file line
      if(preg_match ( '/^\s*FILE/', $cuefile[$i] )) {
        // initialize and increment vars
        fixFileLine($add_folder, $cuefile, $i, $cue_meta, $wav, "fixup");


        $tooLong = "";

        // defines $song
        $song = preg_replace("/^\s*FILE \"/", '', $cuefile[$i]);
        $song = preg_replace("/\" WAVE$/", '', $song);
//        $song = preg_replace("/\\\/", '', $song);
//        $song = preg_replace("/{$artist}{$album}/", '', $song);
        $song = preg_replace("/{$artist}\\\/", '', $song);
        $song = preg_replace("/{$album}\\\/", '', $song);
        // remove any file path
        $song = preg_replace("/\\\/", '', $song);

//print("SONG:{$song}:\n");

        // Calculate Long file name:
        // Also checks for too long file name (i.e. NN~AAA~1.wav)
        $tooLong = "";
        $wavIndex = 0;
        if(preg_match("/^\d\d\d/", $song)){
          $tooLong = substr($song, 0, 3);
        }else {
          $tooLong = substr($song, 0, 2);
        }
        // reassigns $artist to artist name without \
        $tooLongArtist = $list[count($list) - 2];

        // begin making $tooLong
        $wavIndex = intval($tooLong);
        $wav[$wavIndex] = array();
        $tooLong = $tooLong .  "-" . strtoupper(substr($tooLongArtist, 0, 3)) . "~" . "1.wav";

        // If file exists, capture:
        // as long as a song file exists, will assign old name of track to $wav array
        if(file_exists($song)) $songfile=$song;
        elseif(file_exists($tooLong)) $songfile=$tooLong;
        else
        {
          logp("error", array("ERROR: file '{$song}' or", "  '{$tooLong}' specified in cuefile does not exist."));
          return false;
        }

        // change FILE line and set $wav
        $wav[$wavIndex]["old"] = $song;
        $cuefile[$i] = fixFileLine($add_folder, $cuefile[$i], $wav[$wavIndex], $pad);
        //if($cuefile[$i] === 0 && $i < count($cuefile)){
        if($cuefile[$i] === 0 && $i < count($cuefile)){
          logp("error", array("ERROR: fixFileLine function has failed to write new songfile",
                         "  {$songfile}"));
          return FALSE;
        }

      } // file line
    } // for

    // Write a cue file
//    if(isDryRun())
//    {
//      logp("info", "DryRun: Writing pre-converatble cuefile as {$file}.orig");
//      $goodCue = file_put_contents($file . ".orig.new", $cuefile);
//    }
//    else
//    {
//      // puts cue file as all good
//      $goodCue = file_put_contents($file . ".orig", $cuefile);
//    }

    // write original, pre-Convertable cuefile
    logp("log","Writing original, pre-convertable cuefile as '${file}.orig'");
    $orig = $cuefile;
    // add line terminators
    addLineTerm($orig);
    if ( ! file_put_contents($file . ".orig", $orig))
      logp("error,exit1","FATAL ERROR: could not write original cuefile '${file}.orig'");

    // finally gets to making a .cue file that is mp3 converter friendly
    if (! makeCueConvertable($cuefile))  {
      logp("error,info","ERROR: could not make file '{$file}' to a convertable file. Check errors.");
      return FALSE;
    }

    // add line terminators
    addLineTerm($cuefile);

    // Sequence:
    //  - write candidate .cue.new file
    //  - move wav files to match
    //  - move .cue.new to .cue after moving current .cue to .cue.old
    //  - verify .cue.new file with files, etc.
    //  - if verify fails, attempt to reverse renames

    logp("log","Writing candidate cuefile as '${file}.new'");
    if ( ! file_put_contents($file . ".new", $cuefile))
      logp("error,exit1","FATAL ERROR: could not write candidate cuefile '${file}.new'");

    // rewrite wav files
    moveWav($wav);
//        moveWav($base_folder, $add_folder, $wav);

    // verify sequence
    if(! isDryRun())
    {
      // rename old cue file
      logp("log", "rename current cue '{$file}' as '{$file}.old'");
      if ( ! rename($file, $file . ".old"))
        logp("error,exit1","FATAL ERROR: could not rename current cue '{$file}' as '{$file}.old'");

      // rename .new to cue file
      logp("log", "rename new cue '{$file}.new' as '{$file}'");
      if ( ! rename($file . ".new", $file))
        logp("error,exit1","FATAL ERROR: could not rename new cue '{$file}.new' as '{$file}'");

      // if verify, rename files, log and complete
      logp("log","Verifying new cue file...");
      if (verifyCue($base_folder, $add_folder, $file, false))
        // log conversion complete
        logp("info",array("ServerFix successfully transformed '{$file}'",
                          "  in '{$add_folder}'"));
      else  // cleanup
      {
        // unwind all the files to original state
        logp("error","Verify failed on '{$file}.new' in '{$add_folder}'");

        // undo rename .new to cue file
        logp("log", "  rename undo cue '{$file}' as '{$file}.new'");
        if ( ! rename($file, $file . ".new"))
          logp("error,exit1","FATAL ERROR: could not rename cue '{$file}' as '{$file}.new'");

        // undo rename old cue file
	      logp("log", "  rename undo old '{$file}.old' as '{$file}'");
        if ( ! rename($file . ".old", $file))
          logp("error,exit1","FATAL ERROR: could not rename (undo) old '{$file}.old' as '{$file}'");

        // remove .orig file
        logp("log", "  remove '{$file}.orig'");
        if ( ! unlink($file . ".orig"))
          logp("error,exit1","FATAL ERROR: could not remove '{$file}.orig'");

        // reverse wav files
        logp("error","  Attempting to restore wav files...");
//        moveWav($base_folder, $add_folder, $wav, TRUE);
        moveWav($wav, TRUE);

        logp("info","ServerFix failed to transform '{$file}' in '{$add_folder}'. Check if undo was successful.");
      }
    }
    else // DryRun
      logp("info","DryRun would transform '{$file}' in '{$add_folder}'");

  }



  //crawl($test, '', '', "serverFix", array());
  crawl($srcdir, '', '', "serverFix", array());

 ?>
