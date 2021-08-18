<?php

  require "MusicRequire.inc";

  log_init("ServerReport");

  $crawlDir = "C:/Quentin/ReferenceMusic-RippingTool";

  // funciton crawlName($base_folder, $add_folder, $new_base_folder, $filename, $array_of_options)
  //  $base_folder - initial root folder
  //  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
  //       full path name.  $add_folder can be blank to start (and usually is).  Used by
  //       recursive function to crawl.
  //  $new_base_folder - target base folder for functions that are moving/writing files
  //       from a base to a new_base
  //  $filename - name of file passed to function
  //  $options - array of options passed to function
  function crawlName($base_folder, $add_folder, $new_base_folder, $filename, $options){
    $wav = "/\.wav$/";
    $cue = "/\.cue$/";
    if(preg_match($wav, $filename) || preg_match($cue, $filename)){
      plog($add_folder . "/" . $filename);
    }
  }

  crawl($crawlDir, "", "", "crawlName", array());

 ?>
