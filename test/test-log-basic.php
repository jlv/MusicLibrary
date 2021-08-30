<?php

require "MusicRequire.inc";


print "Testing Log System - Basic Messages\n\n";

logp_init("Test-Log-Basic", NULL);

logp("log","Explicit log statement.");

logp("info",
  array("test1 array 1 - info", "test1 array 2 - info", "test1 array 3 - info"));

logp("error","Error log message.");

// null tests

logp("","Log message with null ctl string");

logp("","Next message is a log with null ctl string and null message");

logp("","");

logp("","After message with null ctl string and null message");

//
// debug testing
//

logp("","next message: debug message 1 should not show up");

logp("debug","debug message 1 with debug turned off.");

$debug=TRUE;
logp("debug","debug message 2 with debug turned on. 1;");

logp("log","Previous line debug-only statement. Should not see a debug statement before this line in log.");

logp("info","Info statement 1 should show in log, info, and debug");

logp("debug,info","Info statement 2 should show in log, info, and debug");

logp("debug,nnl",array("debug no NL array 1; ","debug no NL array 2; "));

logp("debug,nnl","debug no NL string;");

logp("debug","debug message with debug turned on, after NL. 2;");

logp("debug","debug message with debug turned on. 3;");

// debug and logp
logp("debug,log","debug with explicit log on;");

// no echo
logp("debug,noecho","This debug line should not echo");

$debug=FALSE;
logp("debug","debug message with debug turned off. 2");

// end of debug test

logp("notify","notify log: outputs in notify and log?");

logp("complete,notify",array("array for complete and notify 1;","array for comp/not 2"));

logp("error,nnl",array("error array no nl 1; ", "error array no nl 2; ", "error array no nl 3; "));

// echo variants

logp("echo","echo, logged as default");

logp("echo",array("echo array 1","echo array 2","echo array 3"));

logp("echo,nolog","echo but no log. Should not see in log.");

logp("echo,error,nnl",array("echo and error, array, nnl 1;","echo and error, array, nnl 2;"));

logp("echo,error","echo and error, finishing line after nnl;");

// exit

logp("echo,exit2","Test log on exit, 2.");

logp("echo,info,exit2","Test info log on exit - should not reach this point, 2.");


?>
