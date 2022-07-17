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

  logp("log","Checking {$add_folder}/{$file}");

  $return = true;

  // if we haven't already errored the directory, check for cue, then check cue

//  if ( $verify_no_cue_dir == "{$base_folder}/{$add_folder}" )
//    return $return;
//  elseif ( checkCueExists($base_folder, $add_folder))
  if ( checkCueExists($base_folder, $add_folder))
    $return = verifyCue($base_folder, $add_folder, $file);
  else
  {
//    $verify_no_cue_dir = "{$base_folder}/{$add_folder}";
    $return = false;
  }
  return $return;
}


// begin function
logp_init("Verify", "");

// execute through crawl
crawl($srcdir, '', '', "verify", array());

?>
