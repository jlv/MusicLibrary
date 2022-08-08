<?php

// functional definition

require "MusicRequire.inc";

logp_init("ServerFix");
logp("log","ServerFix Beginning...");

// check options
// get options
getArgOptions($argv, $options);
checkDryRunOption($options);

//crawl($test, '', '', "serverFix", array());
if (crawl($srcdir, '', '', "serverFix", array()))
  logp("echo,exit0","ServerFix completed crawl of directory.");
else
  logp("echo,exit0","ServerFix encountered errors in crawl of directory. Plesae check.");

// safety
exit;

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
// Returns TRUE on success, FALSE if errors.
//
// serverFix function - fixes both cue file and associated .wav files of the music server
//                        Fixes cue files first and then changes .wav files

function serverFix($base_folder, $add_folder, $new_base_folder, $file, $options){
  $return = TRUE;

  // operate from directory location
  chdir($base_folder . '/' . $add_folder);

  // $list - array of $add_folder split between every /
  $list = preg_split("/\//", $add_folder);

  // $album - last input of $list, which will be the album title
  $album = $list[count($list) - 1];

  // if there is a .$album.nocue file, return out of function (.nocue signals no work in this directory)

  // checks if there is a cue file. if there isn't, ERROR
  if (! checkCueCovered($base_folder, $add_folder, "continue")) return TRUE;

  // check for .jpg file and if it is named folder
  if (getSuffix($file) == "jpg")
    if ( ! file_exists("folder.jpg"))
      if ( rename($file, "folder.jpg"))
        logp("log", "JPG rename {$file} to folder.jpg in {$base_folder}/{$add_folder}");
      else
        logp("error", "ERROR: Failure on renaming {$file} to folder.jpg in {$base_folder}/{$add_folder}");

  // if cue file
  if (getSuffix($file) == "cue")
    // checks to see if cue file needs any fixing
    // JLV: may make a command line option to fix everything
    if(! verifyCue($base_folder, $add_folder, $file, TRUE))  {
      // if !cueGood, runs cueFix function
      $checkSpecial = cueFileFix($base_folder, $add_folder, $file);
      // checks for fixCUE using cueFileFix
      if($checkSpecial === TRUE)
        logp("info", array(
                "Warning: fixCUE ran cueFileFix on {$base_folder}/{$add_folder}.",
                " Please confirm validity of new files"));

    }
  return TRUE;

} // end of function


// function cueFileFix($base_folder, $add_folder, $file)
//  $base_folder - initial root folder
//  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
//       full path name.  $add_folder can be blank to start (and usually is).  Used by
//       recursive function to crawl.
//  $line - FILE line from .cue file
//
// cueFileFix function - new fix function to see if it works. Go back to GitHub if there are big problems

function cueFileFix($base_folder, $add_folder, $file){
  logp("log", "cueFileFix starting on: {$base_folder}/{$add_folder}/{$file}");
  // $wav is a 2D aray of [tracknum][old/new/old_dir/new_dir]
  $wav = array();

  // gets $artist and $album
  $list = preg_split("/\//", $add_folder);
  $list_cnt = count($list);
  if ($list_cnt < 2) {
    logp("error",array(
          "ERROR ServerFix: add_folder must be of the form ..artist/album",
          "  add_folder:'{$add_folder}'"));
    return FALSE;
  }

  $album = $list[$list_cnt - 1];
  $artist = $list[$list_cnt - 2];

  // checks if in artist/album directory
  $checkDir = "/{$artist}\/{$album}/";
  if(! preg_match($checkDir, $add_folder))  {
    logp("error,info", "ERROR: cue file in incorrect directory '{$add_folder}'");
    return false;
  }

  // $cuefile is array of current cue file
  $cuefile = file($file, FILE_IGNORE_NEW_LINES);
  if ( $cuefile === FALSE )
    logp("error,exit1","FATAL ERROR: could not read cue file '{$file}'. Exiting.");

  // process FILE statements
  if (! processFILEtag($add_folder, $cuefile, NULL, $wav, "fixup")) {
    logp("error", "ERROR: processFILEtag returned error.");
    return FALSE;
  }

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
  if (! moveWav($wav)) {
    logp("error","ERROR: error moving wav files. Check logs.");
    return FALSE;
  }

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
      moveWav($wav, "reverse");

      logp("info","ServerFix failed to transform '{$file}' in '{$add_folder}'. Check if undo was successful.");
    }
  }
  else // DryRun
    logp("info","DryRun would transform '{$file}' in '{$add_folder}'");

  return $return;

} // end of function



 ?>
