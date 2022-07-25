<?php

// functional definition
//
// After EAC ripping, scans each cuefile in a directory, touches up any issues
//  and moves the cuefile to the appropriate directory.

require 'MusicRequire.inc';

// function scanCues($directory, $trashBin)
//  $directory - target directory which the function checks
//  $trashBin - target directory for old cue files
//function scanCues($directory, $trashBin){
function scanCues($directory)  {
  // initialize
  global $cue_rip_dir_tag;
  $matches = array();
  $return=TRUE;
  $setup_reg = FALSE;

  // check that we are called from a ripping directory
  //  Requires that a "safe tag" file exists
  if (! file_exists( $directory . "/" . $cue_rip_dir_tag))
    logp("error,exit1", array(
        "FATAL ERROR: not called from a ripping directory.",
        "  The file '{$cue_rip_dir_tag}' must exist in this directory. Exiting."));

  // check that trash directory exists or can be made
  if (! moveToTrash(array())) return FALSE;
//  $goodCue = '';

  // sees if there are multiple cue files for album. Runs multiMove is there are
  $dir_r = opendir($directory);
  while(($file = readdir($dir_r)) !== false) {
    // NOTE $file is name.cue
print "\n\nNextFILE: {$file}\n";
    // initialize variables to process each file
    $wav=array();
    $trash=array();
    $cuefile=array();

    // handle only cue files
    if(getSuffix($file) === "cue")  {
      // // initialize on every new cue file
      // $multi_files=array();

      // ftitle and starting base_title (which may be replaced)
      $ftitle = substr($file, 0, strlen($file) - 4);
      $base_title = $ftitle;
      // get album title from cue file (errors will have already displayed)
      if (($cue_title = getCueInfo("album", $file)) == FALSE) continue;

print "reading cue:\n  File '{$ftitle}'\n  Cue '{$cue_title}'\n";
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

print "in multicheck\n  Base '{$base_title}'\n";
        // check cue title
        if ($cue_title == $ftitle)
          // look for multi, otherwise process as single
          if (findMulti(array("1", "-1", "2", "-2"), $ftitle) == TRUE) continue;
          else $setupRet = setupSingle($file, $cuefile, $wav, $trash);
//          $setupRet = setupSingle($file, $cuefile, $wav, $trash);
        elseif ($cue_title != $base_title) {
          // orphaned file
// JLV
          logp("error", array(
            "ERROR: XX TITLE in file does not match file or match multidisk pattern.",
            "  File: '{$file}'"));
          $return = FALSE;
          continue;
        }

        // if we can find a file with the $base, or $base[-]2, then we have
        //  a multi file
        if(findMulti(array("","2", "-2"), $base_title) == FALSE) {
          // not multi, not single. Error.
          logp("error", array(
                  "ERROR: file does not qualify as a single file or multifile. Skipping.",
                  "  File: '{$file}'"));
          $return = FALSE;
          continue;
        }
print "MADE to setupMulti\n";
        // made it here.  It's a multi with this as the lead file.
        $setupRet = setupMulti($file, $cuefile, $wav, $trash);

      }  // if preg match 1.cue

      // multi: check if title[-][12] files exist,then skip for multi
      elseif (findMulti(array("1", "-1", "2", "-2"), $ftitle) == TRUE) {
        print "FINDMULTI worked for -12\n";
        continue;
      }


      // multi: if title matches, process as single since no evidence
      //         of multi in above test
      elseif  ($ftitle == $cue_title)
      {
        print "SINGLE passed\n";

        $setupRet = setupSingle($file, $cuefile, $wav, $trash);
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
        print "Number passed **Len:" . strlen($ext_suf) . "\n";
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
                  "ERROR: file does not qualify as a single file or multifile. Skipping.",
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
                "ERROR: file does not qualify as a single file or multifile. Skipping.",
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
      if ($setupRet != TRUE) return FALSE;

      // get artist and album, then check if cue file exists in directory
      if ( ($artist = getCueInfo("artist",$file,$cuefile)) == FALSE ) return FALSE;
      if ( ($album = getCueInfo("album",$file,$cuefile)) == FALSE ) return FALSE;
      $dir = $artist . "/" . $album;
      $newfile = $base_title . ".cue";
      $newpath = $dir . "/" . $base_title;

//      logp("log","Processing cuefile '{$file}', '{$artist}'");
      logp("echo","Processing cuefile '{$file}', '{$artist}'");

      if (file_exists($newpath))  {
        logp("error",array(
               "ERROR: cuefile already exists where routing would write a new one. Skipping.",
               "  File: $newpath"));
        return FALSE;
      }

      // check if over 99 tracks and set $pad
      $count_arr = countTracks($cuefile);
      if ($count_arr["return"] =! TRUE) return FALSE;
      $pad = $count_arr["max_pad"];

      // trackify file to appropriate tracks, and populate $wav array
      if (! trackify($cuefile, $wav, $pad)) return FALSE;
      // finish wav array with directories
      foreach($wav as $wavkey=>$wavfile) {
        $wav[$wavkey]["old_dir"] = $dir;
        $wav[$wavkey]["new_dir"] = $dir;
      }

//print_r($wav);
//print "\nCompleted trackify\n";
readline("post trackify pause>");
      // make Convertable
      if (! makeCueConvertable($cuefile)) return FALSE;

      // add line termination
      addLineTerm($cuefile);

      // write candidate file
      if ( ! file_put_contents($newpath . ".cue.cand", $cuefile))  {
        logp("error,exit1", array("FATAL ERROR: could not write candidate cuefile",
                  "  '${newpath}.cue.cand'"));
        $return=FALSE;
        continue;
      }

      // verify current cuefile array, without file testing
      if (! verifyCue( '', $dir, $newfile, FALSE, $cuefile, TRUE)) {
        logp("error", array(
               "ERROR: proposed cuefile array did not verify. Skipping", " File '{$file}'"));
        $return=FALSE;
        continue;
      }
print "\nCompleted no file verify\n";
      // move songs
      if (! moveWav($wav)) {
        logp("error","ERROR: error moving wav files. Check logs.");
        $return=FALSE;
        continue;
      }

      // verify current cuefile array, with file testing
      if (! verifyCue( '', $dir, $newpath . ".cand")) {
        logp("error", array(
               "ERROR: proposed cuefile did not verify. Skipping", " File '{$file}'"));
        $return=FALSE;
        continue;
      }

      // rename candidate file
      if (! isDryRun()) {
        logp("log", "rename candidate to cue, '{$newfile}'");
        if ( ! rename($newpath . ".cand", $newpath))
          logp("error,exit1","FATAL ERROR: could not rename candidate '{$newpath}'");
      }  // dryRun

      // move to trash
      moveToTrash($trash);

    }  // end get suffix = cue
  }  // end while directory
  // close directory on the way out
  closedir($dir_r);

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
print "mluti cand file: '{$cand_file}'\n  POST: base '{$base_title}'\n";
    if ( file_exists($cand_file))
      if (($cue_title = getCueInfo("album", $cand_file)) == FALSE) continue;
      elseif ($cue_title == $base_title) return TRUE;
 }
 // foreach

  // got here with no matches
  return FALSE;
}

// function: setupSingle($file, $cuefile, $wav, $trash
//  $file - filename full (but no directories)
//  $cuefile - cuefile array to be returned
//  $wav - wav array to transport wav files
//  $trash - array of files to move to trashBin
//
// sets up arrays for a single album

function setupSingle($file, &$cuefile, $wav, $trash) {
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

  // add to trash
  $trash[] = $file;

  return TRUE;
}

// function: setupMulti($file, $cuefile, $wav, $trash
//  $file - filename full (but no directories)
//  $cuefile - cuefile array to be returned
//  $wav - wav array to transport wav files
//  $trash - array of files to move to trashBin
//
// sets up arrays for a multidisk album

function setupMulti($file, &$cuefile, $wav, $trash) {
  // initialize
  $files = array();

  if (file_exists($file)) {
    $cuefile = file($file, FILE_IGNORE_NEW_LINES);
    if ( $cuefile === false )
      logp("error,exit1","FATAL ERROR in setupMulti: could not read file '{$file}'. Exit");
  } else
    logp("error,exit1","FATAL ERROR in setupMulti: could not find file '{$file}'. Exit");

  // multi check [-]1
  if(preg_match("/(.*)(-?)1.cue$/", $file, $matches)){
    // set base title
    $base_title = $matches[1];
    $trash[] = $file;

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
        print "Multi find files: k={$k}, end '{$end}', base:'{$base_title}'\n";
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

        // add to trashBin
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


// function trackify(&cuefile, &$wav, $pad)
//  &$
//  $cuefile - cuefile array to be returned
//  $wav - wav array to transport wav files
//  $pad - number of zero-padded digits for this album
//
//  Helper function to rewrite tracks in wav file, remove extra directories
//   in path name, etc.  Loads $wav

function trackify(&$cuefile, &$wav, $pad)  {
  // initialize
  $matches = array();
  $tracks = array();

  // get artist and album, then check if cue file exists in directory
  if ( ($artist = getCueInfo("artist", '', $cuefile)) == FALSE ) return FALSE;
  if ( ($album = getCueInfo("album", '', $cuefile)) == FALSE ) return FALSE;

//print_r($cuefile);
  $track="";
  for($i=0; $i < count($cuefile); $i++)
  {
    // look for FILE lines
    if( preg_match("/^\s*FILE\s+\"/", $cuefile[$i])) {
      // replace artist and album
print "trackify----\n  artist:{$artist}\n  album:{$album}\n";
      $cuefile[$i] = str_replace("{$artist}\\", '', $cuefile[$i]);
      $cuefile[$i] = str_replace("{$album}\\", '', $cuefile[$i]);
print "CUE:{$cuefile[$i]}\n";

      // check if any \ and error
      if (preg_match('/\\\/', $cuefile[$i])) {
        logp("error",array("ERROR: FILE title has a backslash.  Skipping entire cuefile.",
                  "  Line: '{$cuefile[$i]}'"));
        return FALSE;
      }

      // get trackno and replace with padded version
      if( preg_match("/^(\s*FILE\s+\")(\d+)( .*)\"/", $cuefile[$i], $matches))  {
        // set key vars
        $track_no=$matches[2];
        $track = intval($track_no);
        $curfilebase = $matches[3];
        $new_track_no = str_pad($track, $pad, "0", STR_PAD_LEFT);

        // store in wav
        $wav[$track_no]["old"] = $track_no . $curfilebase;
        $wav[$track_no]["new"] = $new_track_no . $curfilebase;

print "track={$track}, ";
        // check that track hasn't already be used, otherwise set
        if (isset($tracks[$track]))  {
          logp("error", array(
                  "ERROR: duplicate track number found, '{$track}'. Skipping cuefile",
                  "  Line: '{$cuefile[$i]}'"));
          return FALSE;
        } else
          $tracks[$track] = 1;

        $cuefile[$i] = preg_replace("/^(\s*FILE\s+\")(\d+)( )/",
              '${1}'. $new_track_no . " ",
              $cuefile[$i]);
      } else {
        logp("error",array("ERROR: could not find track.  Skipping entire cuefile.",
                "  Line: '{$cuefile[$i]}'"));
        return FALSE;
      }
    } // end of if preg FILE

    // replace TRACK
    if( preg_match("/^\s*TRACK\s+/", $cuefile[$i])) {
      if ($track != "") {
        $cuefile[$i] = preg_replace("/(^\s*TRACK\s+)(\d+)(.*)/",
                 '${1}' . str_pad($track, 2, "0", STR_PAD_LEFT) . '${3}',
                  $cuefile[$i]);
        $track="";
      }
    } // end of if TRACK

  } // end of for - reading file

  return TRUE;
}

// function multiMove($directory, $trashBin, $hasDash)
//  $directory - target directory in which the cue files lie
//  $trashBin - target directory for old cue files
//  $file - tells which file we are looking at that caused multiMove
// returns $var.cue
//
// multiMove funcion - moves the cue files of a multi CD into one cuefile with proper track numbers
function multiMove($directory, $trashBin, $file){
  $trackNum = 2;

  // checks for dash in cue file title first, goes into if statement if it does have a dash
  if(preg_match("/-\d.cue$/", $file)){
    // gets album title
    $album = substr($file, 0, strlen($file) - 6);

    // starting number for cue files
    $index =  1;

    // $track is the track number of the album
    $track = 0;

    // combined cue file
    $newCue = file($directory . "/" . $album . "-" . $index . ".cue", FILE_IGNORE_NEW_LINES);

    // checks track numbering format
    foreach($newCue as $line){
      if(preg_match ( '/^\a*FILE/', $line ) === 1 ){
        // $list - array of $add_folder split between every \
        $list = preg_split("/\\\/", $line);

        $song = $list[count($list) - 1];
        if(preg_match("/\d\d\d/", $song))
          $trackNum = 3;

        // incriments the track number as it finds songs
        $track++;
      }
    }

    // moves the old bad cue file to the trash directory
    rename($directory . "/" . $album . "-" . $index . ".cue", $trashBin . "/" . $album . "-" . $index . ".cue");

    $index++;

    // compiles all cue files of album into one
    while(file_exists($directory . "/" . $album . "-" . $index . ".cue")){
      array_push($newCue, "REM CD " . $index);

      // reads next cue file into an array
      $nextCue = file($directory . "/" . $album . "-" . $index . ".cue", FILE_IGNORE_NEW_LINES);

      // cuts off the beginning part of a cue file up to the first FILE line
      $cutLength = 0;
      while(!preg_match( '/^\a*FILE/', $nextCue[$cutLength] )){
        $cutLength += 1;
      }

      array_splice($nextCue, 0, $cutLength);

      // changes the TRACK number to correct track number
      $track = changeTracks($track, $trackNum, $nextCue);

      // combines cue files together with $nextCue being the second one
      $newCue = array_merge($newCue, $nextCue);

      // moves the old bad cue file to the trash directory
      rename($directory . "/" . $album . "-" . $index . ".cue", $trashBin . "/" . $album . "-" . $index . ".cue");

      $index++;
    }

    // checks to see if ripper made cue file that's just album title
    if(file_exists($directory . "/" . $album . ".cue")){
      array_push($newCue, "REM CD " . $index++);

      $nextCue = file($directory . "/" . $album . ".cue", FILE_IGNORE_NEW_LINES);

      // cuts off the beginning part of a cue file up to the first FILE line
      $cutLength = 0;
      while(!preg_match( '/^\a*FILE/', $nextCue[$cutLength] )){
        $cutLength += 1;
      }

      array_splice($nextCue, 0, $cutLength);

      // changes the TRACK number to correct track number
      $track = changeTracks($track, $trackNum, $nextCue);

      // combines cue files together with $nextCue being the second one
      $newCue = array_merge($newCue, $nextCue);

      // moves the old bad cue file to the trash directory
      rename($directory . "/" . $album . ".cue", $trashBin . "/" . $album . ".cue");

    }

    // adds lines to cue file array
    addLineTerm($newCue);

    // puts $newCue into an actual cue file
    $finished = file_put_contents($directory . "/" . $album . ".cue", $newCue);

    // returns $finished as fully made cue file
    return $finished;

  }else
// This is WITHOUT a dash
  {
    // gets album title
    $album = substr($file, 0, strlen($file) - 5);


    // starting number for cue files
    $index =  1;

    // $track is the track number of the album
    $track = 0;

    // combined cue file
    $newCue = file($directory . "/" . $album . $index . ".cue", FILE_IGNORE_NEW_LINES);

    // checks track numbering format
    foreach($newCue as $line){
      if(preg_match ( '/^\a*FILE/', $line ) === 1 ){
        // $list - array of $add_folder split between every \
        $list = preg_split("/\\\/", $line);

        $song = $list[count($list) - 1];
        if(preg_match("/\d\d\d/", $song))
          $trackNum = 3;

        // incriments the track number as it finds songs
        $track++;
      }
    }

    // moves the old bad cue file to the trash directory
    rename($directory . "/" . $album . $index . ".cue", $trashBin . "/" . $album . $index . ".cue");

    $index++;

    // compiles all cue files of album into one
    while(file_exists($directory . "/" . $album . $index . ".cue")){
      array_push($newCue, "REM CD " . $index);

      // reads next cue file into an array
      $nextCue = file($directory . "/" . $album . $index . ".cue", FILE_IGNORE_NEW_LINES);

      // cuts off the beginning part of a cue file up to the first FILE line
      $cutLength = 0;
      while(!preg_match( '/^\a*FILE/', $nextCue[$cutLength] )){
        $cutLength += 1;
      }

      array_splice($nextCue, 0, $cutLength);

      // changes the TRACK number to correct track number
      $track = changeTracks($track, $trackNum, $nextCue);

      // combines cue files together with $nextCue being the second one
      $newCue = array_merge($newCue, $nextCue);

      // moves the old bad cue file to the trash directory
      rename($directory . "/" . $album . $index . ".cue", $trashBin . "/" . $album . $index . ".cue");

      $index++;
    }

    // checks to see if ripper made cue file that's just album title
    if(file_exists($directory . "/" . $album . ".cue")){
      array_push($newCue, "REM CD " . $index++);

      $nextCue = file($directory . "/" . $album . ".cue", FILE_IGNORE_NEW_LINES);

      // cuts off the beginning part of a cue file up to the first FILE line
      $cutLength = 0;
      while(!preg_match( '/^\a*FILE/', $nextCue[$cutLength] )){
        $cutLength += 1;
      }

      array_splice($nextCue, 0, $cutLength);

      // changes the TRACK number to correct track number
      $track = changeTracks($track, $trackNum, $nextCue);

      // combines cue files together with $nextCue being the second one
      $newCue = array_merge($newCue, $nextCue);

      // moves the old bad cue file to the trash directory
      rename($directory . "/" . $album . ".cue", $trashBin . "/" . $album . ".cue");
    }

    // adds lines to cue file array
    addLineTerm($newCue);

    // puts $newCue into an actual cue file
    $finished = file_put_contents($directory . "/" . $album . ".cue", $newCue);

    // returns $finished as fully made cue file
    return $finished;

  }


}

// function changeTracks(&$track, &$array)
//  &$track - track number of the multi CD disk
//  &$trackNum - var givin to tell whether or not the track is using 2 digit number or 3 digit
//  &$array - array of strings which make up cue file
//  NOTE &$track, &$trackNum and &$array are reference variables
// returns $track, an int that is the track number that the function ended on
//
// changeTracks function - correctly changes the track number of the cd to the track of the entire album
function changeTracks(&$track, &$trackNum, &$array){
  for($i = 0; $i < count($array); $i++){
    $numTrack = $track + 1;
    if(preg_match('/^\s\sTRACK/', $array[$i]) && $trackNum = 2 && $track > 9){
      // $list - array of $add_folder split between every \
      $list = preg_split("/\\\/", $array[$i-1]);

      $song = $list[count($list) - 1];

      // checks if the track number in the FILE line matches the track number in TRACK
      if(!preg_match("/^0*{$numTrack}/", $song)){
        logp("error", "ERROR: FILE song number {$array[$i-1]} does not match TRACK number: {$numTrack}");
      }

      $array[$i] = "  TRACK " . $numTrack . " AUDIO";
      $track++;

    }else if(preg_match('/^\s\sTRACK/', $array[$i]) && $trackNum = 3 && $track > 9 && $track < 100){
      // $list - array of $add_folder split between every \
      $list = preg_split("/\\\/", $array[$i-1]);

      $song = $list[count($list) - 1];

      // checks if the track number in the FILE line matches the track number in TRACK
      if(!preg_match("/^0*{$numTrack}/", $song)){
        logp("error", "ERROR: FILE song number {$array[$i-1]} does not match TRACK number: {$numTrack}");
      }

      $array[$i] = "  TRACK 0" . $numTrack . " AUDIO";
      $track++;

    }else if(preg_match('/^\s\sTRACK/', $array[$i]) && $trackNum = 3 && $track > 100){
      // $list - array of $add_folder split between every \
      $list = preg_split("/\\\/", $array[$i-1]);

      $song = $list[count($list) - 1];

      // checks if the track number in the FILE line matches the track number in TRACK
      if(!preg_match("/^0*{$numTrack}/", $song)){
        logp("error", "ERROR: FILE song number {$array[$i-1]} does not match TRACK number: {$numTrack}");
      }

      $array[$i] = "  TRACK " . $numTrack . " AUDIO";
      $track++;

    }else if(preg_match('/^\s\sTRACK/', $array[$i]) && $trackNum = 2 && $track < 10){
      // $list - array of $add_folder split between every \
      $list = preg_split("/\\\/", $array[$i-1]);

      $song = $list[count($list) - 1];

      // checks if the track number in the FILE line matches the track number in TRACK
      if(!preg_match("/^0*{$numTrack}/", $song)){
        logp("error", "ERROR: FILE song number {$array[$i-1]} does not match TRACK number: {$numTrack}");
      }

      $array[$i] = "  TRACK 0" . $numTrack . " AUDIO";
      $track++;

    }else if(preg_match('/^\s\sTRACK/', $array[$i]) && $trackNum = 3 && $track < 10){
      // $list - array of $add_folder split between every \
      $list = preg_split("/\\\/", $array[$i-1]);

      $song = $list[count($list) - 1];

      // checks if the track number in the FILE line matches the track number in TRACK
      if(!preg_match("/^0*{$numTrack}/", $song)){
        logp("error", "ERROR: FILE song number {$array[$i-1]} does not match TRACK number: {$numTrack}");
      }

      $array[$i] = "  TRACK 00" . $numTrack . " AUDIO";
      $track++;
    }
  }

  return $track;
}

// function cleanCue($directory, &$file)
//  $directory - the starting directory where all the cue files are sitting
//  &$file - the cue file that we are checking and cleaning up
//   NOTE $file must be compilation of all multi CDs of an album
//   NOTE &$file is a reference variable
// returns nothing
//
// cleanCue function - removes all \ from FILE lines of cue file, then puts files in correct artist/album directory
function cleanCue($directory, &$file){

  // $album and $artist are name holders to get cue file in correct directory
  $album = "";
  $artist = "";

  // sets opens the file
  $fixing = file($directory . "/" . $file, FILE_IGNORE_NEW_LINES);

  // deletes old cue file
  unlink($directory . "/" . $file);

  for($i = 0; $i < count($fixing); $i++){
    if(preg_match ( '/^\a*FILE/', $fixing[$i] ) && preg_match('/\\\/', $fixing[$i])){

      $list = preg_split("/\\\/", $fixing[$i]);

      // attaches propper song title and album title
      $song = $list[count($list) - 1];
      $album = $list[count($list) - 2];
      // artist requires more work because of FILE "
      $artist = $list[count($list) - 3];
      $artist = substr($artist, 6);

      // fixes FILE line to just song title
      $fixing[$i] = "FILE \"" . $song;

      // double checks if wave file of song actually exists. ERROR if doesn't
      $song = preg_replace("/\".*$/", '', $song);
      if(!file_exists($directory . "/" . $artist . "/" . $album . "/" . $song)){
        logp("error", "ERROR: {$song} files does not exist in {$directory}/{$artist}/{$album}");
      }

    }
  }

  // fix up array for turning back into file
  addLineTerm($fixing);

  // puts $fixing into a .cue.ori, where it has all INDEX as the orignal .cue
  file_put_contents($directory . "/" . $artist . "/" . $album . "/" . $album . ".cue.orig", $fixing);

  // changes all INDEX to be correct for mp3
  for($i = 0; $i < count($fixing); $i++){
    if(preg_match("/INDEX 00 /", $fixing[$i])){
      $cuefile[$i] = "    INDEX 01 00:00:00\n";
    }else
    if(preg_match("/INDEX \d\d/", $fixing[$i]) && !preg_match("/INDEX 01 00:00:00/", $fixing[$i])){
      array_splice($fixing, $i, 1);
      $i--;
    }
  }

  // puts $fixing into a .cue file
  $file = file_put_contents($directory . "/" . $artist . "/" . $album . "/" . $album . ".cue", $fixing);

}


//
// begin function - main
//
logp_init("MoveCues", "");

//$testDir = "C:/Quentin/ReferenceMusic-RippingTool/0 Jazz";
//$trashDir = "C:/Quentin/MusicWorking/MoveCuesTrash";

// scanCues($testDir, $trashDir)
if (scanCues("."))
  logp("echo","MoveCues complete.");
else
  logp("error,echo","MoveCues complete, but with errors.  Please check.");

?>
