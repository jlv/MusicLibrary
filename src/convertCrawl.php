<?php

// functional definition

require "MusicRequire.inc";
logp_init("convertCrawl");

// check options
getArgOptions($argv, $options);
checkDryRunOption($options);

// set crawl in motion
if (crawl($srcdir, "", $conversion_base_dir, "convertFromCue", $options))
  logp("echo,exit0","convertCrawl completed crawl of directory.");
else
  logp("echo,exit0","convertCrawl encountered errors in crawl of directory. Please check.");


?>
