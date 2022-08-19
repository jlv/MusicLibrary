<?php

// functional definition
//
// creates logfile report of every file in system
//
$help = array(
  "Report",
  "  no options"
);

require 'MusicRequire.inc';
logp_init("Report");

// check options
getArgOptions($argv, $options, $help);
checkDryRunOption($options);

// set crawl in motion
if (crawl($srcdir, '', '', "report", array()))
  logp("echo,exit0","Report completed crawl of directory.");
else
  logp("echo,exit0","Report encountered errors in crawl of directory. Please check.");

// safety
exit;


//  function report($base_folder, $add_folder, $new_base_folder, $file, $options)
//  $base_folder - initial root folder
//  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
//       full path name.  $add_folder can be blank to start (and usually is).  Used by
//       recursive function to crawl.
//  $new_base_folder - target base folder for functions that are moving/writing files
//       from a base to a new_base
//  $file - file given from crawl($base_folder, $add_folder, $new_base_folder, $ufunction, $options). never NULL
//  $options - array of options passed to $ufunction
//
//  reporting function: logs all files in a folder to Report folder. Use in tandem to crawl function
function report($base_folder, $add_folder, $new_base_folder, $file, $options){

  // use info log to generate report
  logp("info", $add_folder . "/" . $file);
}



?>
