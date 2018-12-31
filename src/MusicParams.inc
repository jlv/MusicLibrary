<?php
//
// Music Library
// Tools and to maintain music library
//

//
// Parameters
//



//
// Log file directory
$logdir = "d:/Music Programming/Log";
//
// Log print flag: if set to TRUE, prints log messages to screen 
//  and to log file
$logprint = TRUE;
//$logprint = FALSE;

//
// Delete files in destination directory
//$dir = "C:/Quentin/MusicReference";

// source directory of wave files and cue files
$srcdir = "D:/Music Programming/Music Ref";

// destimation MP3 directory
$mp3dir = "D:/Music Programming/Music Out";

// location of coverter binary to be invoked through system call
$converter = "C:\Apps\xrecode3\xrecode3cx64.exe";

// parameters to feed converter
$convparams = "/dest mp3 /cbr 192 /pfilename %track2%";


// Timezone setting
//  -- the string must come from a PHP list found here:
//       http://php.net/manual/en/timezones.php
$sys_time_zone='America/Los_Angeles';

?>