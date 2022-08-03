<?php

// MoveCues [--reorder]
//  --reorder - reorders tracks in any compbined files
//
// After EAC ripping, scans each cuefile in a directory, touches up any issues
//  and moves the cuefile to the appropriate directory.

require 'MusicRequire.inc';


// function moveCues($directory)
//  $directory - target directory which the function checks
//
//function scanCues($directory, $trashBin){

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


//  if (! moveToTrash(array())) return FALSE;
//  $goodCue = '';

  // sees if there are multiple cue files for album. Runs multiMove is there are
  foreach(array(TRUE, FALSE) as $check) {
    logp("log","Pass for all files where check is " . $check);

    $dir_r = opendir($directory);
    $setup_reg = FALSE;
    $trash = array();
    $trashed = array();

    // check that trash directory exists or can be made
    if ($check == FALSE)
      if (! moveToTrash()) return FALSE;


    while(($file = readdir($dir_r)) !== false) {
      // NOTE $file is name.cue
//  print "\n\nNext ({$check}) FILE: {$file}\n";
      // initialize variables to process each file
      $wav=array();
  //    $trash=array();
      $cuefile=array();

      // handle only cue files
      if(getSuffix($file) != "cue") continue;

      // skip if file is in $trashed list
      if(fileChecked($file, $trashed, FALSE, TRUE)) continue;
      $cue_found=TRUE;
//print "passed trash check\n";
      // if in second pass (if $check is FALSE), check that file was
      //     picked up for use in first pass in the files_used list
//      if (! fileChecked($file, $files_used, $check)) return FALSE;

      // // initialize on every new cue file
      // $multi_files=array();

      // ftitle and starting base_title (which may be replaced)
      $ftitle = substr($file, 0, strlen($file) - 4);
      $base_title = $ftitle;
      // get album title from cue file (errors will have already displayed)
      if (($cue_title = getCueInfo("album", $file)) == FALSE) continue;

//print "reading cue:\n  File '{$ftitle}'\n  Cue '{$cue_title}'\n";
      // logic for establishing multi mode
      //  - if ends in [-]1:
      //     - check if title matches with the [-]1:
      //         if so, look for subsequent versions, otherwise process as single
      //     - elseif check if other files exist (and title matches)
      //     - else error
      //  - elseif title[-][12] files exist, check titles & skip (going multi)
      //  - elseif title matches, process as single
      //  - elseif ends in \d, check if a [-]1 exists and title matches & skip
      //  - else error
      //
      // multi check [-]1
//readline("pause>");
      if(preg_match("/(-?)1$/", $ftitle, $matches)){
        // set vars
        $base_title = substr($ftitle, 0, -strlen($matches[1] . "1"));
//        if(isset($matches[2])) $ifdash = $matches[2]; else $ifdash="";
// not needed

//print "in multicheck\n  Base '{$base_title}'\n";
        // check cue title
        if ($cue_title == $ftitle)
          // look for multi, otherwise process as single
          if (findMulti(array("1", "-1", "2", "-2"), $ftitle) == TRUE) continue;
          else $setupRet = setupSingle($file, $cuefile, $files_used, $trash);
//          $setupRet = setupSingle($file, $cuefile, $wav, $trash);
        elseif ($cue_title != $base_title) {
          // orphaned file
// JLV
          logp("error", array(
            "ERROR: TITLE in cue file does not match file or match multidisk pattern.",
            "  File: '{$file}'"));
          $return = FALSE;
          continue;
        }

        // if we can find a file with the $base, or $base[-]2, then we have
        //  a multi file
        if(findMulti(array("","2", "-2"), $base_title) == FALSE) {
          // not multi, not single. Error.
          logp("error", array(
                  "ERROR: file does not qualify as a single disc or multi disc. Skipping.",
                  "  File: '{$file}'"));
          $return = FALSE;
          continue;
        }
//print "MADE to setupMulti\n";
        // made it here.  It's a multi with this as the lead file.
        $setupRet = setupMulti($file, $cuefile, $files_used, $trash);

      }  // if preg match 1.cue

      // multi: check if title[-][12] files exist,then skip for multi
      elseif (findMulti(array("1", "-1", "2", "-2"), $ftitle) == TRUE) {
  //  print "FINDMULTI worked for -12\n";
        continue;
      }


      // multi: if title matches, process as single since no evidence
      //         of multi in above test
      elseif  ($ftitle == $cue_title)
      {
//        print "SINGLE passed\n";

        $setupRet = setupSingle($file, $cuefile, $files_used, $trash);
      }

      // multi check \d*.cue and matches, skip
      elseif (preg_match("/(-?)(\d*)$/", $ftitle, $matches)) {
        // could be a suffix on a file, or could be a bigger part of
        //  the actual file.  Chop it down to find a match.
        $ext_suf = $matches[1] . $matches[2];
        $ext_base = substr($ftitle, 0, -strlen($ext_suf));

        // set condition test for loop looking for a match for
        //   cue_title.
        $ext_return = FALSE;
//        print "Number passed **Len:" . strlen($ext_suf) . "\n";
//        print_r($matches);
        for($m=1; $m <= strlen($ext_suf); $m++) {
//          print "Testing:\n   cue:{$cue_title}\n   ext:" . $ext_base . substr($ext_suf, 0, -$m) . "\n";
          if ($cue_title == $ext_base . substr($ext_suf, 0, -$m)) {
//            print "Found matching file at m={$m}, {$cue_title}\n";
            $ext_return = TRUE;
            break;
          }
        }  // for

        // if we found a match, skip (continue); otherwise error
        if ($ext_return == TRUE) continue;
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
//readline("multi/single assesment pause>");
      // multi or single is set up. Now to process:
      //  - check that setup succeeded
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
      if ( ($artist = getCueInfo("artist",$file,$cuefile)) == FALSE ) {
        $return=FALSE; continue;
      }
      if ( ($album = getCueInfo("album",$file,$cuefile)) == FALSE ) {
        $return=FALSE; continue;
      }
      $dir = $artist . "/" . $album;
      // strip any . from $base_title
      $new_title = str_replace('.', '', $base_title);
      $newfile = $new_title . ".cue";
      $newpath = $dir . "/" . $newfile;

//      logp("log","Processing cuefile '{$file}', '{$artist}'");
//      logp("echo","Processing cuefile '{$file}', '{$artist}'");

      if (file_exists($newpath))  {
        logp("error",array(
               "ERROR: cuefile already exists where routing would write a new one. Skipping.",
               "  File: $newpath"));
        $return=FALSE; continue;
      }

      // process FILE statements
      if (! processFILEtag($dir, $cuefile, NULL, $wav, "normal"))
        logp("error,exit1", "ERROR: processFILEtag returned error.");

      // trackify file to appropriate tracks, and populate $wav array
//      if (! trackifyCue($cuefile, $wav, $pad)) {
      if (isset($options["reorder"]) && $options["reorder"] === TRUE)
         $option="reorder";
      else $option="normal";

      if (! trackifyCue($cuefile, $wav, $option)) {
        $return=FALSE; continue;
      }

      // finish wav array with directories
      foreach($wav as $wavkey=>$wavfile) {
        $wav[$wavkey]["old_dir"] = $dir;
        $wav[$wavkey]["new_dir"] = $dir;
      }

//print_r($wav);
//print "\nCompleted trackify\n";
//readline("post trackify pause>");
      // make Convertable
      if (! makeCueConvertable($cuefile)) {
        $return=FALSE; continue;
      }

      // add line termination
      addLineTerm($cuefile);

      // write candidate file if not in CHECK mode
      if ($check == FALSE) {
//        print "In Check False Write\n";
        if ( ! file_put_contents($newpath . ".cand", $cuefile))  {
          logp("error,exit1", array("FATAL ERROR: could not write candidate cuefile",
                    "  '${newpath}.cue.cand'"));
          $return=FALSE; continue;
        }
      }

      // verify current cuefile array, without file testing
      if (! verifyCue( '', $dir, $newfile, FALSE, $cuefile, TRUE)) {
        logp("error", array(
               "ERROR: proposed cuefile array did not verify. Skipping", " File '{$file}'"));
        $return=FALSE;  continue;
      }
//print "\nCompleted no file verify\n";
      // move songs (or test file existance if in check mode)
      if (! moveWav($wav, NULL, $check)) {
        logp("error","ERROR: error moving wav files. Check logs.");
        $return=FALSE; continue;
      }

      // if $check is false, verify actual file and make moves
      // verify current cuefile array, with file testing
      if ($check == FALSE) {
        if (! isDryRun() && ! verifyCue( '', $dir, $newfile . ".cand")) {
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
      } // end of $check == FALSE (just above)
    //}  // end get suffix = cue
    }  // end while directory/read file loop

    // if in check mode, check for error condition, then reinitialize vars as needed
    if ($check == TRUE) {
      // check for cues
      if ($cue_found == FALSE) {
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
      while(($file = readdir($dir_r)) !== false) {
        if(getSuffix($file) != "cue") continue;

        // skip if file is in $trashed list
        if(fileChecked($file, $trashed, FALSE, TRUE)) continue;

        //  is file in files_used list
        if (! fileChecked($file, $files_used, $check)) return FALSE;
      } // while
    } // Check = TURE

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
//print "mluti cand file: '{$cand_file}'\n  POST: base '{$base_title}'\n";
    if ( file_exists($cand_file))
      if (($cue_title = getCueInfo("album", $cand_file)) == FALSE) continue;
      elseif ($cue_title == $base_title) return TRUE;
 }
 // foreach

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
  if ( $cuefile === false )
    logp("error,exit1","FATAL ERROR: could not read cue file '{$file}'. Exiting.");

  // check track count and rewrite if needed

  if (file_exists($file)) {
    $cuefile = file($file, FILE_IGNORE_NEW_LINES);
    if ( $cuefile === false )
      logp("error,exit1","FATAL ERROR in getCueInfo: could not read file '{$file}'. Exit");
  } else
    logp("error,exit1","FATAL ERROR in setupMulti: could not find file '{$file}'. Exit");

  // log
  logp("log","Processing cuefile '{$file}'");

  // get artist and album
  if ( ($artist = getCueInfo("artist",$file,$cuefile)) == FALSE ) return FALSE;
  if ( ($album = getCueInfo("album",$file,$cuefile)) == FALSE ) return FALSE;
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
    if ( $cuefile === false )
      logp("error,exit1","FATAL ERROR in setupMulti: could not read file '{$file}'. Exit");
  } else
    logp("error,exit1","FATAL ERROR in setupMulti: could not find file '{$file}'. Exit");

  // multi check [-]1
  if(preg_match("/(-?)1.cue$/", $file, $matches)){
    // set base title
    $base_title = substr($file, 0, -strlen($matches[1] . "1.cue"));
    // bookkeeping
    $files_used[] = $file;
    $trash[] = $file;

    // log cuefile
    logp("log","Processing multi disc cuefile '{$file}'");

    // get artist and album
    if ( ($artist = getCueInfo("artist",$file,$cuefile)) == FALSE ) return FALSE;
    if ( ($album = getCueInfo("album",$file,$cuefile)) == FALSE ) return FALSE;
    $dir = $artist . "/" . $album;

    // check that directory exists
    if (! is_dir($dir))  {
      logp("error", array(
            "ERROR in setupMulti: artist/album directory does not exist. Skipping album.",
            "  Dir: '{$dir}'"));
      return FALSE;
    }

    // loop until a sequence number of file doesn't exist
    //  k=1 is no end on file. k=0 is finish loop
    $k = 2;
    $endCheck = 0;
    while ( $k > 0 ) {
      if ($k == 1) $ends=array(""); else $ends=array($k,"-{$k}");
      foreach($ends as $end) {
// print "Multi find files: k={$k}, end '{$end}',\n  base:'{$base_title}'\n";
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
        if ( $ncuefile === false )
          logp("error,exit1","FATAL ERROR in setupMulti: could not read file '{$nfile}'. Exit");

        // check for matching artist and album
        if ( ($nartist = getCueInfo("artist",$file,$cuefile)) == FALSE ) return FALSE;
        if ( ($nalbum = getCueInfo("album",$file,$cuefile)) == FALSE ) return FALSE;
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

          if ($fileFound == TRUE ) $cuefile[] = $nline;
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
  if ($check == TRUE) return TRUE;

  // loop through array looking for match
  foreach($files_used as $file_check)
    if ($file_check == $file) return TRUE;

  // made it through array without a match. error.
  if ($skip_error == FALSE)
    logp("error",array(
           "ERROR: cue file not used in pass as single title or part of multi CD.",
           "   Usually a file name is out of whack or a file in a multi CD rip",
           "   is missing. Please check files and run again. No cues were moved.",
           "   File: '{$file}'"));

  return FALSE;
}


//
// begin function - main
//
logp_init("MoveCues", "");

getArgOptions($argv, $options);

// scanCues($testDir, $trashDir)
if (moveCues(".", $options))
  logp("echo,exit0","MoveCues complete.");
else
  logp("error,echo,exit1","MoveCues complete, but with errors.  Please check.");

?>
