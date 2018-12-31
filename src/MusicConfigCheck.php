<?php
//
// Music Library
//
//

require "MusicRequire.inc";

// initialize log file
log_init("MusicExample");

// show that we're debugging as an example
if (debug()) print "Debug is turned on.\n\n";

plog("A test log message");

// check machine name

print "Hostname: $hostname\n";

print "Logfile: $logfile\n";

//
// process complete
//
print "Check Complete.\n";

?>