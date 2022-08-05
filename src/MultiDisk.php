<?php

// MultiDisk [--override-nofile]
//  --override-nofile - overrides completion of disk merge skipping non-existant
//                       files
//

require "MusicRequire.inc";
logp_init("MultiDisk", "");

getArgOptions($argv, $options);

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
if (! executeMerge($cuefile, $wav, $trash, $options)) exit;

logp("echo,exit0","MultiDisk successfully merged directories.  See logs for details.");

// end of script

// safety
exit;



// function setupMultiMerge(&$cuefile, &$cue_meta, &$wav, &$trash, $options)
//  $add_folder - the folder path to add to $base_folder in crawl function.
//                serves to capture artist/album
//  $cuefile - cuefile array
//  $cue_meta - cuefile meta data. Mirrors cuefile element for element so that
//              array can be referced with same index. Tagged elements form
//              second dimension of array, e.g. ['dir'],['artist'],...
//  $wav - wav array (see definition of array in moveWAV)
//  $options - array of options
//
// Basic flow:
//  Find files, check artist/album, load into single cuefile, capturing some
//    needed meta data.
//  Process cuefile through all steps, which loads $wav and is ready to confirm
//    and execute.  Note that $wav is used to store a runtime piece of data in
//    tooLongExists

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
          $cuefile[$i] = preg_replace("/^(\s*TITLE\s+\")(.*)(\".*)$/",
                        '${1}' . $finalDir . '${3}',  $cuefile[$i]);
          $title_found = TRUE;
        }
      } // for $i loop

    } else {
      // iterate through each line of file, find first FILE directive, then add
      $fileFound = FALSE;

      // read each line of cuefile, look for first FILE, then add to cuefile
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

  // check file existance from moveWav
  if (! moveWav($wav, "test-exist"))
    logp("error,exit1", "ERROR: moveWav testing file existance returned error.");


  return TRUE;
} // end of setupMultiMerge function




// function confirmMerge($cuefile, $wav)
//  $cuefile - cuefile array
//  $wav - wav array (see definition of array in moveWAV)
//
// Returns TRUE on success
//
// Confirms titles and track file changes with user. Exits if not confirmed.

function confirmMerge($cuefile, $wav)  {
  global $finalDir;
  global $multiDisks;

  print "\n\n*** Confirming MultiDisk Details\n\n";

  print "Disc Parameters:\n";
  // checks that all cds actually exist
  $i=1;
  foreach($multiDisks as $disk)
    print "  Disc " . $i++ . ":           {$disk}\n";

  print "  Final dir/artist: {$finalDir}\n";

  // confirm
  if (strtoupper(readline("\nConfirm >")) != "Y") exit;

  // confirm
  if (strtoupper(readline("\nDouble checking final direcotry. Confirm >")) != "Y") exit;

  // show wav
  print "\n\nTrack Changes:\n\n";

  foreach ($wav as $song) {
    // get correct file,if a tooLong
    if (isset($song['tooLongExists']) && $song['tooLongExists'] == TRUE)
      $old_song = $song["old_long"];
    else
      $old_song = $song["old"];

    print "Dir:  {$song["old_dir"]}\nSong: {$old_song}\n";
    print " --> '{$song["new"]}'\n\n";
  }

  // confirm
  if (strtoupper(readline("\nConfirm >")) != "Y") exit;

  return TRUE;

} // end of confim function


// function executeMerge(&$cuefile, &$wav, &$trash, $options)
//  $cuefile - cuefile array
//  $cue_meta - cuefile meta data. Mirrors cuefile element for element so that
//              array can be referced with same index. Tagged elements form
//              second dimension of array, e.g. ['dir'],['artist'],...
//  $wav - wav array (see definition of array in moveWAV)
//  $options - array of options
//
// Returns TRUE on success, FALSE if errors
// Executes merge set up by setup merge.

function executeMerge(&$cuefile, &$wav, &$trash, $options)  {
  // globals from parameter
  global $finalDir;
  global $multiDisks;
  $return = TRUE;

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
  if (! moveWav($wav, $options))
    logp("error,exit1","FATAL ERROR: error moving wav files. Check logs.");

  // verify with files in place
  //  note hack to ../ on base dir so file works
  if (! isDryRun() && ! verifyCue( '..', $dir, $newfile . ".cand"))
    logp("error,exit1",
         "FATAL ERROR: proposed cuefile did not verify but wav files have been moved.");

  // move to trash.  Note using parent as base trash directory.
  //  also note: we move to Trash before we rename .cand file in case
  //   the finalDir is one of the contributing dirs (which would remove
  //   it's cue file)
  if (! moveToTrash($trash, $trashed, "..")) $return = FALSE;

  // rename candidate file
  if (! isDryRun()) {
    logp("log", "rename candidate to cue, '{$newpath}'");
    if ( ! rename($newpath . ".cand", $newpath))
      logp("error,exit1","FATAL ERROR: could not rename candidate '{$newpath}'");
  }  // dryRun


  // move remaining files source disks
  foreach ($multiDisks as $disc)
    if (! moveDirContents($disc, $finalDir)) {
      logp("error","ERROR: could not clear a directory, '{$disc}'");
      $return = FALSE;
    }

  return $return;
} // end of executeMerge function



?>
