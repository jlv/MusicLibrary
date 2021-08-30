<?php

require "MusicRequire.inc";

print "Testing Log System - Multiple Inits\n\n";

//logp_init("Test-Log-Multi", NULL);
logp_init("Test-Log-Multi", "echo,nonl");

logp("echo,info","Explicit first log statement, multi-init");

logp("echo","Statement before second init");

logp_init("Test-Log-Multi", "echo,nonl");

logp("info",
  array("test1 array 1 - info", "test1 array 2 - info", "test1 array 3 - info"));

logp("error","Error log message 1, default echo,nonl.");

logp("error,nl","Error log message 2, default echo, explicit nl.");

logp("error,nl","Error log message 3, default echo, explicit nl.");


logp_init("Test-Log-Multi-2", "info");

logp("notify","Should default to info, and hit notify and log");

logp("echo,notify","Second message should have NL beforenad");

logp("exit0","Exit with no errors.")


?>
