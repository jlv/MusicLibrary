<?php

// functional definition:
// rename directory and cuefile to new filename specified in Rename.inc.
//
//  Note: the rename does it's best to rename the file in it's current
//        format. It does not FIX the file.  That must occur from serverFix.
//
//  Method: form the new cuefile and load the wav file changes into the
//          $wav array.  Confirm the changes with the user, then execute.
//
//  wav array: The 2D array uses Index as first dimension and as 2nd dim
//           "old", "new", "old_dir", and "new_dir" for data values.

require "MusicRequire.inc";
logp_init("Rename", "", "echo[error],echo[info]");

// check options
getArgOptions($argv, $options);
checkDryRunOption($options);

// check if Rename.inc exists in local directory, then require if exists
if (file_exists("Rename.inc"))
  require "./Rename.inc";
else
{
  logp ("echo,error,exit1",
        "Error: Could not find variables file \"Rename.inc\" in working directory. Exiting.");
}

// initialize Variables
$wav=array();

// first, much check that $oldDir exists
if(!is_dir($oldDir))
  logp("error,exit1", "FATAL ERROR: source directory '{$oldDir}' does not exist. Exiting.");

// next, works on cuefile. Errors if no Cue file
$oldcue=$oldDir . "/" . $oldDir . ".cue";
if(!file_exists($oldcue))
  logp("error,exit1",
        "FATAL ERROR: .cue file does not exist by same name in {$oldDir}");
$cuefile = file($oldcue, FILE_IGNORE_NEW_LINES);

if (! setupAlbum($cuefile, $wav)) exit;
print "made it\n";

if (! confirm($wav)) exit;

logp("echo","Completing rename...");
if (completeRename($oldcue, $cuefile, $wav))
  logp("echo","  ...finished Rename to {$newDir}");
else
  logp("error,exit1","There were errors on rename. See logs.");

// safety
exit;


//
// *********************************************************************************
//

// function: confirm(&$cuefile, &$wav)
// function intro($currentDir, $oldDir, $newDir, $trackExcerpt)
//  $oldDir - old album name to be changed
//  $newDir - new name of album that user wants
//  $trackExcerpt - part of tracks that the user wants the program to cut out
//
// intro function - takes in user input to confirm what is being renamed to what
// returns 0 on failure
function confirm(&$wav) {
  global $oldDir;
  global $newDir;
  global $trackExcerpt;

print_r($wav);

  // display parameters
  print "\n\n********************************************************\n";
  print "CONFIRM RENAME PARAMETERS\n\n";
  print $oldDir . "  ---->  " . "\n    " . $newDir . "\n";
  print "Track Excerpt: {$trackExcerpt}\n\n";

  foreach($wav as $index)
    print "{$index["old"]}\n  --> {$index["new"]}\n\n";

  print "\n\n";

  $response = readline("\nAre these new track names correct and directories? >");
  if(strtoupper($response) != "Y") exit();

  $response = readline("\nAre you sure you want to rename? >");
  if(strtoupper($response) != "Y") exit();

  return true;
} // end of confirm


// function setupAlbum(&$cuefile, &$wav)

function setupAlbum(&$cuefile, &$wav) {
  global $oldDir;
  global $newDir;
  global $trackExcerpt;
  global $albumRenameOff;

  // $changedAlbum lets us know if we already changed the album title so that TITLE of tracks remains the same
  $changedAlbum = false;
  for($i = 0; $i < count($cuefile); $i++){
    if(!$changedAlbum && preg_match("/TITLE/", $cuefile[$i])){
      $cuefile[$i] = "TITLE \"" . $newDir . "\"";
      $changedAlbum = true;
    }

    // begins looking at FILE and fixing the titles
    if(preg_match ( '/^\a*FILE/', $cuefile[$i] ) === 1){
      $wav[$i] = array();
      // $songfile
      $songfile = preg_replace("/FILE \"/", '', $cuefile[$i]);
      $songfile = preg_replace("/\" WAVE/", '', $songfile);

      // for legacy artist\Album, replace everything up to backslash
      $songfile = preg_replace("/^.*\\\/", '', $songfile);


      // replace old Dir with newDir in file name if an option
      // check file existance:
      // try normal filename renames
//        $pfix = " - ";
//        $cuefile[$i] = str_replace($pfix . $oldDir . $pfix, $pfix . $newDir . $pfix, $cuefile[$i]);
//        $pfix = " ~ ";
//        $cuefile[$i] = str_replace($pfix . $oldDir . $pfix, $pfix . $newDir . $pfix, $cuefile[$i]);

      $wav[$i]["old"] = $songfile;

      // for legacy, replace oldDir with newDir
      //$cuefile[$i] = str_replace("\\{$oldDir}\\","\\{$newDir}\\",$cuefile[$i]);



      // fixes up FILE line and $songfile
      //$cuefile[$i] = preg_replace($trackExcerpt, '', $cuefile[$i]);


      // edit $songfile
      $newSongfile = $songfile;
      if ($albumRenameOff != TRUE)
      {
        // try normal filename renames
        $pfix = " - ";
        $newSongfile = str_replace($pfix . $oldDir . $pfix, $pfix . $newDir . $pfix, $newSongfile);
        $pfix = " ~ ";
        $newSongfile = str_replace($pfix . $oldDir . $pfix, $pfix . $newDir . $pfix, $newSongfile);
      }

      $newSongfile = str_replace($trackExcerpt, '', $newSongfile);
      $wav[$i]["new"] = $newSongfile;

      $cuefile[$i] = "FILE \"{$newSongfile}\" WAVE";

      // add directories
//        $wav[$i]["old_dir"]=$oldDir;
//        $wav[$i]["new_dir"]=$newDir;
    }

  } // end of for

  return TRUE;
} // end of setupAlbum function


// function editAlbum($currentDir, $oldDir, $newDir, $trackExcerpt)
//  $oldDir - old album name to be changed
function completeRename($oldcue, &$cuefile, &$wav) {
  global $oldDir;
  global $newDir;
  global $trackExcerpt;
  global $albumRenameOff;

  // add line termination
  addLineTerm($cuefile);

  if (! file_put_contents($oldcue . ".rename", $cuefile))
    logp("error,exit1","FATAL ERROR: could not write rename cue file: '{$oldcue}.rename'");

  if(isDryRun())
  {
    logp("info", "DryRun: rename '{$oldDir}.cue' as '{$oldDir}.cue.pre-rename'");
    logp("info", "DryRun: rename directory '{$oldDir}' as '{$newDir}'");
  } else {
    //
    // rename old .cue file
    if (file_exists($oldcue . ".pre-rename"))
      logp("error,exit1","FATAL ERROR: storage pre-rename file already exists '{$oldcue}.pre-rename'");
    elseif (! rename($oldcue, $oldcue . ".pre-rename"))
      logp("error,exit1","FATAL ERROR: could not rename cue file: '{$oldcue}'");

    // rename directories
    if (! rename($oldDir, $newDir))
      logp("error,exit1",array("FATAL ERROR: could not rename directory, '{$oldDir}'",
           " to '{$newDir}'"));

    // rename .rename file
    if (! rename($newDir . "/" . $oldDir . ".cue.rename", $newDir . "/" . $newDir . ".cue"))
        logp("error,exit1","FATAL ERROR: could not rename cue file: '{$newDir}/{$oldDir}.cue.rename'");

    logp("log","Renamed {$newDir}/{$oldDir}.cue.rename to {$newDir}/{$newDir}.cue");

  } // isDry


  // rename wav files
  if (isDryRun()) chdir($oldDir); else chdir($newDir);
  moveWav($wav);

  logp("info",array("Rename complete: {$oldDir} ->", "  {$newDir}"));

  // foreach ($wav as $index) {
  //   if(isDryRun()){
  //     logp("notify,echo", "Would be renaming {$index["old"]} as {$index["new"]}");
  //   }else{
  //     $goodWav = rename($newDir . "/" . $index["old"], $newDir . "/" . $index["new"]);
  //     if(! $goodWav)
  //       logp("error,echo", "ERROR: failure on renaming {$index["old"]} file");
  //     else
  //       logp("log", "Renamed file {$index["old"]} as {$index["new"]}");
  //     }
  //   }
  //}
  return TRUE;
} // end of completeRename function


 ?>
