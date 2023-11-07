<?php

// MultiDisk [--override-nofile]
//  --override-nofile - overrides completion of disk merge skipping non-existant
//                       files
//
$help = array(
  "CompareTree ",
  " - no options"
);

require "MusicRequire.inc";
logp_init("CompareTree");

//
// Main routine
//

// check options
getArgOptions($argv, $options, $help);
checkDryRunOption($options);

// check if Rename.inc exists in local directory, then require if exists
if (file_exists("CompareTree.inc"))
  require "CompareTree.inc";
else {
  logp ("echo,error,exit1",
        "FATAL ERROR: Could not find variables file \"CompareTree.inc\" the program directory. Exiting.");
}

// initialize globals
$compared = array();

// First pass: compare cmpDir to baseDir
logp("info", array("Comparing tree nodes:", " Base node: {$baseDir}",
   " Comparison node: {$cmpDir}"));

crawl($baseDir, "", $cmpDir, "compare_node", array("compare"=>FALSE));

// Second pass: compare baseDir to cmpDir
logp('info', array("Comparing tree nodes with Cmp as base:", " Base node: {$baseDir}",
    " Comparison node: {$cmpDir}"));

crawl($cmpDir, "", $baseDir, "compare_node", array("compare"=>TRUE));


// safety
exit;



//
// functions
//

// function compare_node
// Returns:
//   TRUE - if comparison was made and logged
//   FALSE - if comparison could not be made (any cases here?)


function compare_node($base_folder, $add_folder, $new_base_folder, $filename, $array_of_options) {
  // global campared array
  global $compared;

  // set compare TRUE/FALSE
  if ($array_of_options['compare'] === TRUE) $cmpopt = TRUE;
  else $cmpopt = FALSE;

  $subpath = $add_folder . '/' . $filename;
  $cmppath = $new_base_folder . '/' . $subpath;

  // log check
  if ($cmpopt === TRUE) $ind = " (2)"; else $ind = "";
  logp("log","File{$ind}: {$subpath}");

  // check if $add_folder/$filename is already compared
  if ($cmpopt === TRUE && isset($compared[$subpath])) return TRUE;

  // if file doesn't exist, log and move on
  if (! file_exists($cmppath)) {
    logp("info,echo",array("Comparison file not found{$ind}:", "  {$cmppath}"));
    return TRUE;
  }

  // compare files
  if (files_compare($base_folder . '/' . $subpath, $cmppath) !== TRUE)
    logp("info,echo", array("Comparison failed between files{$ind}:", "  {$cmppath}"));

  // set array if first pass
  if ($cmpopt === FALSE) $compared[$subpath] = TRUE;

  // default return
  return TRUE;
}

//
// end of Functions
//


?>
