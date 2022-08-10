<?php

// functional definition
//
// verifies cue file exists, is well-formed and has matching music files
//   - To skip a directory, add a file $directory.nocue
//
//

require "MusicRequire.inc";
logp_init("Verify", "", "echo[error]");

// check options
getArgOptions($argv, $options);
checkDryRunOption($options);

// execute through crawl
if (crawl($srcdir, '', '', "verify"))
  logp("echo","Verification pass completed.");
else
  logp("echo","Verification pass completed, but with errors.  Check logs.");

// safety
exit;


//
// Global Variables
//

// verify_no_cue_dir:
$verify_no_cue_dir="";

function verify($base_folder, $add_folder, $new_base_folder, $file, $options)  {
  $return = true;

  if ( checkCueCovered($base_folder, $add_folder, "continue")) {
    $return = verifyCue($base_folder, $add_folder, $file);
    if ( $return === FALSE)
      logp("error", array("Verified FAILED!: {$add_folder},",  "   '{$file}'"));
  }

  return $return;
}

?>
