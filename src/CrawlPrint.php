<?php

require 'MusicRequire.inc';

// this is the log that will be made
log_init("Print Log");

function crawlPrint($base_folder, $add_folder, $new_base_folder, $filename, $array_of_params)
{
  plog($filename . "\n\j");
}

$empty = array();
$startPoint = "C:\Quentin\MusicReference\Music";
crawl($startPoint, "", "", "crawlPrint", $empty);

?>