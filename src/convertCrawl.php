<?php

// functional definition

$help = array(
  "convertCrawl",
  "  no options",
  "Converts entire tree in srcdir to conversion directory and sub",
  " directories specified in MusicParams.inc"
);

require "MusicRequire.inc";
logp_init("convertCrawl");

// check options
getArgOptions($argv, $options, $help);
checkDryRunOption($options);

// set crawl in motion
if (crawl($srcdir, "", $conversion_base_dir, "convertFromCue", $options))
  logp("echo,exit0","convertCrawl completed crawl of directory.");
else
  logp("echo,exit0","convertCrawl encountered errors in crawl of directory. Please check.");


?>
