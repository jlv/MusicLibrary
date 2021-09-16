<?php

require 'MusicRequire.inc';

// this is the log that will be made
log_init("Print");

function crawlPrint($base_folder, $add_folder, $new_base_folder, $filename, $array_of_params)
{
  plog($filename . "\r\n");
}

$empty = array();
$startPoint = "C:\Quentin\MusicReference\Music";
$startPoint = $homedir . "\MusicRef";

crawl($startPoint, "", "", "crawlPrint", $empty);

?>