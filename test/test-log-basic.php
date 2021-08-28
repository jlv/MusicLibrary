<?php

require "MusicRequire.inc";


print "Testing Log System\n\n";

logp_init("Test-Log", NULL);

logp("log","Explicit log statement.");

logp("info",
  array("test array 1 in info log", "test array 2 in info l", "test array 3 in info l"));

logp("error","Error log message.");

logp("debug","debug message with debug turned off.");

$debug=TRUE;
logp("debug","debug message with debug turned on. 1;");

logp("log","Previous line debug-only statement. Should not see a debug statement before this line in log.");

logp("debug,nnl",array("debug no NL array 1; ","debug no NL array 2; "));

logp("debug,nnl","debug no NL string;");

logp("debug","debug message with debug turned on, after NL. 2;");

logp("debug","debug message with debug turned on. 3;");

$debug=FALSE;
logp("debug","debug message with debug turned off. 2");

logp("notify","notify log: outputs in notify and log?");

logp("complete,notify",array("array for complete and notify 1;","array for comp/not 2"));

logp("error,nnl",array("error array no nl 1; ", "error array no nl 2; ", "error array no nl 3; "));

logp("echo,exit2","Test log on exit.");

logp("echo,info,exit2","Test info log on exit.");

 ?>
