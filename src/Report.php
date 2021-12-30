<?php

  // functional definition
  //
  // creates logfile report of every file in system
  //

  require 'MusicRequire.inc';

  //  function reporting($base_folder, $add_folder, $new_base_folder, $file, $options)
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
  function reporting($base_folder, $add_folder, $new_base_folder, $file, $options){

    // use info log to generate report
    logp("info", $add_folder . "/" . $file);
  }

  //
  // begin function - main
  //
  logp_init("Reporting", "");

  // execute through crawl
  crawl($srcdir, '', '', "reporting", array());

 ?>
