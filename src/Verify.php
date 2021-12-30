<?php

// functional definition
//
// verifies cue file is well-formed and has matching music files
//

require "MusicRequire.inc";

function verify($base_folder, $add_folder, $new_base_folder, $file, $options)  {
  logp("log","Checking {$add_folder}/{$file}");

  $return = true;

  if ( checkNoCue($base_folder, $add_folder))
    $return = verifyCue($base_folder, $add_folder, $file);
  else
    $return = false;

  return $return;
}

// begin function
logp_init("Verify", "");

// execute through crawl
crawl($srcdir, '', '', "verify", array());

?>
