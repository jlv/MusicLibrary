<?php

require "MusicRequire.inc";


print "Testing Log System\n\n";

log_init("Test-Log", NULL);

logp("info",array("test array 1", "test array 2", "test array 3"));

logp("echo,exit2","Test log.");

logp("echo,info,exit2","Test info log.");

 ?>
