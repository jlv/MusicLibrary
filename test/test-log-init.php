<?php

require "MusicRequire.inc";

// functional definition

require "MusicRequire.inc";

logp_init("LogTestInit", "");
print_r($_base);

logp("log","ServerFix Beginning");
print_r($_base);

logp_init(NULL, "noecho", "echo[error],echo[info]");
print_r($_base);

logp_init(NULL, NULL, NULL);
print_r($_base);

logp_init(NULL, "clear", "echo[error]=false,echo[info]");
print_r($_base);


logp_init("LogTestInit-New", NULL, "none");
print_r($_base);

logp_init(NULL, "noecho", NULL);
print_r($_base);

logp("log","ServerFix1 Beginning");
print_r($_base);

logp_init(NULL, NULL, "none");
print_r($_base);

logp_init(NULL, "noecho", "echo[error],echo[info]=FALSE");
print_r($_base);

logp_init(NULL, "NONE", "none");
print_r($_base);

print("At end of test\n");
exit();

?>
