<?php
//
// Music Library
//
//

require "MusicRequire.inc";

// initialize log file
logp_init("MusicExample","");

// show that we're debugging as an example
if (debug()) logp("echo", "Debug is turned on.");

logp("","A test log message");

// check machine name

logp("echo","Hostname: $hostname");

//
// process complete
//
logp("echo","Check Complete.");

?>
