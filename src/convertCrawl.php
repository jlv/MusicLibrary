<?php

// functional definition

require "MusicRequire.inc";

logp_init("convertCrawl", "", "echo[error],echo[info]");

crawl($srcdir, "", $conversion_base_dir, "convertFromCue", array());

 ?>
