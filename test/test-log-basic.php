<?php

require "MusicRequire.inc";

print "Testing Log System\n\n";

log_init("Test-Log", NULL);

logp("echo,exit2","Test log.");

logp("echo,info,exit2","Test info log.");

 ?>
