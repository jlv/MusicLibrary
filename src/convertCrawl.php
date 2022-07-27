<?php

// functional definition

require "MusicRequire.inc";

//logp_init("convertCrawl", "", "echo[error],echo[info]");

logp_init("convertCrawl");

//crawl($srcdir, "", $conversion_base_dir, "convertFromCue", array());
crawl($srcdir, "", $conversion_base_dir, "convertFromCue");

 ?>
