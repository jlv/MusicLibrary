<?php

// MoveCues [--reorder]
//  --reorder - reorders tracks in any compbined files
//
// After EAC ripping, scans each cuefile in a directory, touches up any issues
//  and moves the cuefile to the appropriate directory.
$help = array(
  "MoveCues [--reorder]",
  "  --reorder - reorders tracks in any compbined files"
);

require 'MusicRequire.inc';

logp_init("MoveCues");

// check options
// get options
getArgOptions($argv, $options, $help);
checkDryRunOption($options);

// scanCues($testDir, $trashDir)
if (moveCues(".", $options))
  logp("echo,exit0","MoveCues complete.");
else
  logp("error,echo,exit1","MoveCues complete, but with errors.  Please check.");

// safety
exit;


// function moveCues($directory)
//  $directory - target directory which the function checks
//  $options - options array

function moveCues($directory, $options)  {
  // initialize
  global $cue_rip_dir_tag;
  $matches = array();
  $files_used = array();
  $return=TRUE;
  $cue_found=FALSE;

  // check that we are called from a ripping directory
  //  Requires that a "safe tag" file exists
  if (! file_exists( $directory . "/" . $cue_rip_dir_tag))
    logp("error,exit1", array(
        "FATAL ERROR: not called from a ripping directory.",
        "  The file '{$cue_rip_dir_tag}' must exist in this directory. Exiting."));

  // Two Passes!  Once $check=TRUE, then $check=FALSE to execute
  // sees if there are multiple cue files for album. Runs multiMove is there are
  foreach(array(TRUE, FALSE) as $check) {
    logp("log","Pass for all files where check is " . $check);

    $dir_r = opendir($directory);
    $setup_reg = FALSE;
    $trash = array();
    $trashed = array();

    // check that trash directory exists or can be made
    if ($check === FALSE)
      if (! moveToTrash()) return FALSE;


    while(($file = readdir($dir_r)) !== FALSE) {
      // initialize variables to process each file
      $wav=array();
      $cuefile=array();

      // handle only cue files
      if(getSuffix($file) != "cue") continue;

      // skip if file is in $trashed list
      if(fileChecked($file, $trashed, FALSE, TRUE)) continue;
      $cue_found=TRUE;

      // ftitle and starting base_title (which may be replaced)
      $ftitle = substr($file, 0, strlen($file) - 4);
      $base_title = $ftitle;

      // get album title from cue file (errors will have already displayed)
      if (($cue_title = getCueInfo("album", $file)) === FALSE) continue;

      // logic for establishing multi mode
      //  - if ends in [ ][-][ ]1:
      //     - check if title matches with the [ ][-][ ]1:
      //         if so, look for subsequent versions, otherwise process as single
      //     - elseif check if other files exist (and title matches)
      //     - else error
      //  - elseif title[ ][-][ ][12] files exist, check titles & skip (going multi)
      //  - elseif title matches, process as single
      //  - elseif ends in \d, check if a [-]1 exists and title matches & skip
      //  - else error
      //
      // multi check [-]1

      if(preg_match("/( ?)(-?)( ?)1$/", $ftitle, $matches)){
        // set vars
        $base_title = substr($ftitle, 0, -strlen($matches[1] . $matches[2] . $matches[3] . "1"));

        // check cue title
        if ($cue_title == $ftitle)
          // look for multi files, otherwise process as single
          if (findMulti(
               array("1", "-1", " -1", "- 1", " - 1", "2", "-2", " -2", "- 2", " - 2"),
             $ftitle) === TRUE) continue;
          else $setupRet = setupSingle($file, $cuefile, $files_used, $trash);
        elseif ($cue_title != $base_title) {
          // orphaned file
          logp("error", array(
            "ERROR: TITLE in cue file does not match file or match multidisk pattern.",
            "  File: '{$file}'"));
          $return = FALSE;
          continue;
        }

        // if we can find a file with the $base, or $base[ - ]2, then we have
        //  a multi file
        if(findMulti(array("", "2", "-2", " -2", "- 2", " - 2"), $base_title) === FALSE) {
          // not multi, not single. Error.
          logp("error", array(
                  "ERROR: file does not qualify as a single disc or multi disc. Skipping.",
                  "  Could not find the next file in sequence.",
                  "  File: '{$file}'"));
          $return = FALSE;
          continue;
        }

        // made it here.  It's a multi with this as the lead file.
        $setupRet = setupMulti($file, $cuefile, $files_used, $trash);

      }  // if preg match 1.cue

      // multi: check if title[-][12] files exist,then skip for multi
      elseif (findMulti(
               array("1", "-1", " -1", "- 1", " - 1", "2", "-2", " -2", "- 2", " - 2"),
               $ftitle) === TRUE)
        continue;

      // multi: if title matches, process as single since no evidence
      //         of multi in above test
      elseif  ($ftitle == $cue_title)
        $setupRet = setupSingle($file, $cuefile, $files_used, $trash);

      // multi check \d*.cue and matches, skip
      elseif (preg_match("/(-?)(\d*)$/", $ftitle, $matches)) {
        // could be a suffix on a file, or could be a bigger part of
        //  the actual file.  Chop it down to find a match.
        $ext_suf = $matches[1] . $matches[2];
        $ext_base = substr($ftitle, 0, -strlen($ext_suf));

        // set condition test for loop looking for a match for
        //   cue_title.
        $ext_return = FALSE;
        for($m=1; $m <= strlen($ext_suf); $m++) {
          if ($cue_title == $ext_base . substr($ext_suf, 0, -$m)) {
            $ext_return = TRUE;
            break;
          }
        }  // for

        // if we found a match, skip (continue); otherwise error
        if ($ext_return === TRUE) continue;
        else {
          logp("error", array(
                  "ERROR: file does not qualify as a single disc or multi disc. Skipping.",
                  "  File ends with numbers but cannot find a multi-cue match for another",
                  "  file, and in-cue title doesn't match the file name.",
                  "  File: '{$file}'"));
          $return = FALSE;
          continue;
        }
      } // end elseif number

      // multi: none of the normal conditions apply. Error.
      else {
        logp("error", array(
                "ERROR: file does not qualify as a single disc or multi disc. Skipping.",
                "  File and in-cue title don't match.",
                "  File: '{$file}'"));
          $return = FALSE;
          continue;
      } // end long if/elseif/else branch


      // multi or single set up is complete. Now to process:
      //  (seqence is important, especially with album modification)
      //  - set up new/old vars
      //  - check that new targets match each other
      //  - check that cuefile does not already exist
      //  - finish cuefile with processing steps
      //  - validate candidate cuefile in directory, without songfile check
      //  - move songs from wav array
      //  - move source files to trash
      //  - final verify of cuefile in place

      // check setupRet
      if ($setupRet != TRUE) {
        $return=FALSE; continue;
      }

      // get artist and album, then check if cue file exists in directory
      if ( ($artist = getCueInfo("artist",$file,$cuefile)) === FALSE ) {
        $return=FALSE; continue;
      }
      if ( ($old_album = getCueInfo("album",$file,$cuefile)) === FALSE ) {
        $return=FALSE; continue;
      }

      // strip from $old album, then compare
      $new_album = str_replace('.', '', $old_album);

      // strip any . from $base_title
      $new_title = str_replace('.', '', $base_title);

      // compare new title in filename to album within file
      if ($new_album != $new_title) {
        logp("error", array(
          //  - check that setup succeeded
              "ERROR: Target cue file name and directory do not match. Skipping.",
              "  Filename: '{$new_title}'",
              "  Album: '{$new_album}'"));
        $return = FALSE; continue;
      }

      // var setup
      $old_dir = $artist . "/" . $old_album;
      $new_dir = $artist . "/" . $new_album;
      $newfile = $new_title . ".cue";
      $newpath = $new_dir . "/" . $newfile;

      // check that cue file doesn't already exist
      if (file_exists($newpath))  {
        logp("error",array(
               "ERROR: cuefile already exists where moving cues would write a new one. Skipping.",
               "  File: $newpath"));
        $return=FALSE; continue;
      }

      // process FILE statements
      //  Note: using $old_dir so replacements happen correctly. Then we replace with $new_dir
      if (! processFILEtag($old_dir, $cuefile, NULL, $wav, "normal"))  {
        $return=FALSE; continue;
      }

      // if $new_album is different, change in cuefile
      if ($old_album != $new_album) {
        $title_found = FALSE;
        for ($i=0; $i < count($cuefile); $i++) {
          // end at first FILE
          if (preg_match("/^\s*FILE\s+/", $cuefile[$i])) break;

          if (preg_match("/^\s*TITLE\s+\"/", $cuefile[$i]))  {
            $cuefile[$i] = str_replace($old_album, $new_album, $cuefile[$i]);
            $title_found = TRUE;
            break;
          }
        }  // end of for

        // if TITLE not found, error
        if ($title_found === FALSE)  {
          logp("error", array(
                "ERROR: could not replace old album name with new album in cue file.",
                "  Old Name: '{$old_album}'",
                "  New Name: '{$new_album}'"));
          $return = FALSE; continue;
        }
      } // end if $old_album


//        logp("error,exit1", "ERROR: processFILEtag returned error.");

      // set reorder option and trackify file to appropriate tracks
      if (isset($options["reorder"]) && $options["reorder"] === TRUE)
         $option="reorder";
      else $option="normal";

      if (! trackifyCue($cuefile, $wav, $option)) {
        $return=FALSE; continue;
      }

      // finish wav array with directories
      foreach($wav as $wavkey=>$wavfile) {
        $wav[$wavkey]["old_dir"] = $old_dir;
        $wav[$wavkey]["new_dir"] = $new_dir;
      }

      // make Convertable
      if (! makeCueConvertable($cuefile)) {
        $return=FALSE; continue;
      }

      // add line termination
      addLineTerm($cuefile);

      // create dir if needed and write candidate file if not in CHECK mode
      if ($check === FALSE) {
        if (! is_dir($new_dir))
          if (! mkdir($new_dir)) {
            logp("error", array(
                   "ERROR: could not make new directory for album. Skipping move.",
                   "  New dir: '{$new_dir}'"));
            $return=FALSE; continue;
          }

        if ( ! file_put_contents($newpath . ".cand", $cuefile))  {
          logp("error,exit1", array("FATAL ERROR: could not write candidate cuefile",
                    "  '${newpath}.cue.cand'"));
          $return=FALSE; continue;
        }
      }  // end if check === FALSE

      // verify current cuefile array, without file testing
      if (! verifyCue( '', $new_dir, $newfile, "override-nofile", $cuefile)) {
        logp("error", array(
               "ERROR: proposed cuefile array did not verify. Skipping", " File '{$file}'"));
        $return=FALSE;  continue;
      }

      // move songs (or test file existance if in check mode)
      if (! moveWav($wav, array("test-exist"=>$check))) {
        logp("error","ERROR: error moving wav files. Check logs.");
        $return=FALSE; continue;
      }

      // if $check is false, verify actual file and make moves
      // verify current cuefile array, with file testing
      if ($check === FALSE) {
        if (! isDryRun() && ! verifyCue( '', $new_dir, $newfile . ".cand")) {
          logp("error", array(
                 "ERROR: proposed cuefile did not verify. Skipping", " File '{$newfile}'"));
          $return=FALSE; continue;
        }

        // rename candidate file
        if (! isDryRun()) {
          logp("log", "rename candidate to cue, '{$newfile}'");
          if ( ! rename($newpath . ".cand", $newpath))
            logp("error,exit1","FATAL ERROR: could not rename candidate '{$newpath}'");
        }  // dryRun

        // move to trash
        moveToTrash($trash, $trashed);

        // move any other files if directory moved
        moveDirContents($old_dir, $new_dir);

      } // end of $check == FALSE (just above)
    }  // end while directory/read file loop

    // if in check mode, check for error condition, then reinitialize vars as needed
    if ($check === TRUE) {
      // check for cues
      if ($cue_found === FALSE) {
        logp("error","No cue files found.");
        return FALSE;
      }
      // check if errors
      if ($return != TRUE) {
        logp("error",
               "ERRORS were found on check pass. Returning without processing. Check errors.");
        return FALSE;
      }

      // read through directory and make sure we hit every cue
      closedir($dir_r);
      $dir_r = opendir($directory);
      while(($file = readdir($dir_r)) !== FALSE) {
        if(getSuffix($file) != "cue") continue;

        // skip if file is in $trashed list
        if(fileChecked($file, $trashed, FALSE, TRUE)) continue;

        //  is file in files_used list
        if (! fileChecked($file, $files_used, $check)) return FALSE;
      } // while
    } // Check = TRUE

    // close directory
    closedir($dir_r);
  } // end check mode

  return $return;
}


// function findMulti( $base_title, $endings)
//  $base_title - base_title for file creation and title comparison
//  $endings - array of end of file and suffix to append to base_title
//
// Helper function to determine if files with specified endings could
//  part of a multi-disk rip
//  Returns TRUE if one of the files matches for multi-disk

function findMulti( $endings, $base_title )  {
  foreach ($endings as $ending) {
    $cand_file = $base_title . $ending . ".cue";
    if ( file_exists($cand_file))
      if (($cue_title = getCueInfo("album", $cand_file)) === FALSE) continue;
      elseif ($cue_title == $base_title) return TRUE;
  }  // foreach

  // got here with no matches
  return FALSE;
}

// function: setupSingle($file, $cuefile, &$files_used, &$trash
//  $file - filename full (but no directories)
//  $cuefile - cuefile array to be returned
//  $files_used - array of cue files that have been used. Adds to this array.
//  $trash - array for to be trashed
//
// Returns TRUE if successul, FALSE on failure
//
// sets up arrays for a single album

function setupSingle($file, &$cuefile, &$files_used, &$trash) {
  $cuefile = file($file, FILE_IGNORE_NEW_LINES);
  if ( $cuefile === FALSE )
    logp("error,exit1","FATAL ERROR: could not read cue file '{$file}'. Exiting.");

  // check track count and rewrite if needed

  if (file_exists($file)) {
    $cuefile = file($file, FILE_IGNORE_NEW_LINES);
    if ( $cuefile === FALSE )
      logp("error,exit1","FATAL ERROR in getCueInfo: could not read file '{$file}'. Exit");
  } else
    logp("error,exit1","FATAL ERROR in setupMulti: could not find file '{$file}'. Exit");

  // log
  logp("log","Processing cuefile '{$file}'");

  // get artist and album
  if ( ($artist = getCueInfo("artist",$file,$cuefile)) === FALSE ) return FALSE;
  if ( ($album = getCueInfo("album",$file,$cuefile)) === FALSE ) return FALSE;
  $dir = $artist . "/" . $album;

  // check that directory exists
  if (! is_dir($dir))  {
    logp("error", array(
          "ERROR in setupSingle: artist/album directory does not exist. Skipping album.",
          "  Dir: '{$dir}'"));
    return FALSE;
  }

  // add to files_used, and trash
  $files_used[] = $file;
  $trash[] = $file;

  return TRUE;
}

// function: setupMulti($file, $cuefile, &$files_used, &trash
//  $file - filename full (but no directories)
//  $cuefile - cuefile array to be returned
//  $files_used - array of cue files that have been used. Adds to this array.
//  $trash - array for to be trashed
//
// Returns TRUE if successul, FALSE on failure
//
// sets up arrays for a multidisk album

function setupMulti($file, &$cuefile, &$files_used, &$trash) {
  // initialize
  $files = array();

  if (file_exists($file)) {
    $cuefile = file($file, FILE_IGNORE_NEW_LINES);
    if ( $cuefile === FALSE )
      logp("error,exit1","FATAL ERROR in setupMulti: could not read file '{$file}'. Exit");
  } else
    logp("error,exit1","FATAL ERROR in setupMulti: could not find file '{$file}'. Exit");

  // multi check [-]1
  if(preg_match("/( ?)(-?)1.cue$/", $file, $matches)){
    // set base title
    $base_title = substr($file, 0, -strlen($matches[1] . $matches[2] . "1.cue"));
    // bookkeeping
    $files_used[] = $file;
    $trash[] = $file;

    // log cuefile
    logp("log","Processing multi disc cuefile '{$file}'");

    // get artist and album
    if ( ($artist = getCueInfo("artist",$file,$cuefile)) === FALSE ) return FALSE;
    if ( ($album = getCueInfo("album",$file,$cuefile)) === FALSE ) return FALSE;
    $dir = $artist . "/" . $album;

    // check that directory exists
    if (! is_dir($dir))  {
      logp("error", array(
            "ERROR in setupMulti: artist/album directory does not exist. Skipping album.",
            "  Dir: '{$dir}'"));
      return FALSE;
    }

    // loop until a sequence number of file doesn't exist
    //  k=1 is no end on file. k=0 is finishing loop
    $k = 2;
    $endCheck = 0;
    while ( $k > 0 ) {
      if ($k == 1) $ends=array(""); else $ends=array($k,"-{$k}");
      foreach($ends as $end) {
        $nfile = $base_title . $end . ".cue";
        if ( file_exists($base_title . $end . ".cue")) {
          // reset endCheck
          $endCheck = 0;
          // break from foreach
          break;
        }
        else $nfile = "";
      }

      // check if file:
      //  No file causes end state process:
      //  - first bump increment and make sure there isn't a file there
      //      endCheck=1
      //  - then look for no-end on file, which often terminates set
      //      endCheck=2
      //  - If endCheck is 2, then set k=0 to terminate
      //
      // Otherwise if file, process that file
      //   Note that k must be set to 0 with in the else if k=1 to terminate

      if ( $nfile == "")
        if ( $endCheck == 0) {$k++; $endCheck = 1; }
        elseif ( $endCheck == 1 ) {$k=1; $endCheck = 2; }
        else $k=0;
      else {
        // if k==1, then set to 0 to terminate; otherwise increment.
        if ($k == 1) $k=0; else $k++;

        // read file
        logp("log","Adding multi disc file '{$nfile}'");

        $ncuefile = file($nfile, FILE_IGNORE_NEW_LINES);
        if ( $ncuefile === FALSE )
          logp("error,exit1","FATAL ERROR in setupMulti: could not read file '{$nfile}'. Exit");

        // check for matching artist and album
        if ( ($nartist = getCueInfo("artist",$file,$cuefile)) === FALSE ) return FALSE;
        if ( ($nalbum = getCueInfo("album",$file,$cuefile)) === FALSE ) return FALSE;
        if ( $nartist != $artist || $nalbum != $album ) {
          logp("error", array(
              "ERROR: artist or album in cue sheet does not match that of multi master. Skipping",
              "  Master artist: '{$artist}'",
              "  Master album: '{$album}'",
              "  File artist: '{$nartist}'",
              "  File album: '{$nalbum}'",
              "  File: '{$nfile}'"
            ));
          return FALSE;
        }

        // add REM separator
        $cuefile[] = "REM Next CD";

        // iterate through each line of file, find first FILE directive
        $fileFound = FALSE;
        foreach($ncuefile as $nline)  {
          // mark when we get to FILE
          if (preg_match( '/^\a*FILE/', $nline )) $fileFound = TRUE;

          if ($fileFound === TRUE ) $cuefile[] = $nline;
        }  // end of foreach

        // add to files used, trash
        $files_used[] = $nfile;
        $trash[] = $nfile;

      }  // end of else nfile (!= "")
    }  // end of while K
  } // end of preg_match [-]1
  else
    // did not match for multi file format - error
    logp("error,exit1", array(
            "FATAL ERROR in setupMulti: beginning file not of the form *[-]1.cue",
            "  File: '{$file}'"));

  return TRUE;
}


// function: fileChecked($file, $files_used, $check, [$skip_error])
//  $file - file
//  $files_useds - array maintained of every cue file used
//  $check - if $check = FALSE, perform file check
//  $skip_error - if TRUE, skips reporting error
//
// Returns true if either check is true or if file passed a check
//
// Helper function to check file

function fileChecked($file, $files_used, $check, $skip_error = FALSE) {
  if ($check === TRUE) return TRUE;

  // loop through array looking for match
  foreach($files_used as $file_check)
    if ($file_check == $file) return TRUE;

  // made it through array without a match. error.
  if ($skip_error === FALSE)
    logp("error",array(
           "ERROR: cue file not used in pass as single title or part of multi CD.",
           "   Usually a file name is out of whack or a file in a multi CD rip",
           "   is missing. Please check files and run again. No cues were moved.",
           "   File: '{$file}'"));

  return FALSE;
}

?>
