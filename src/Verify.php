<?php

// functional definition
//
// verifies cue file exists, is well-formed and has matching music files
//   - To skip a directory, add a file $directory.nocue
//
//

require "MusicRequire.inc";

//
// Global Variables
//

// verify_no_cue_dir:
$verify_no_cue_dir="";


function verify($base_folder, $add_folder, $new_base_folder, $file, $options)  {
//  global $verify_no_cue_dir;

//  logp("log","Checking {$add_folder}/{$file}");

  $return = true;

  // if we haven't already errored the directory, check for cue, then check cue

//  if ( $verify_no_cue_dir == "{$base_folder}/{$add_folder}" )
//    return $return;
//  elseif ( checkCueCovered($base_folder, $add_folder))
  if ( checkCueCovered($base_folder, $add_folder, "continue")) {
    $return = verifyCue($base_folder, $add_folder, $file);
    if ( $return == FALSE)
      logp("error", array("Verified FAILED!: {$add_folder},",  "   '{$file}'"));
  }

  return $return;
}


// begin function
//logp_init("Verify", "", "echo[error],echo[info]");
logp_init("Verify", "", "echo[error]");

// execute through crawl
if (crawl($srcdir, '', '', "verify"))
  logp("echo","Verification pass completed.");
else
  logp("echo","Verification pass completed, but with errors.  Check logs.");

?>
