<?php
//
// Music Library
// Tools and to maintain music library
//

//
// Parameters
//

$hostname = gethostname();

//
// Params can change as hostname changes
//

if ($hostname != "Rising-Sun")
{
// NOn-Rising-Sun Machines

//
// Log file directory
$logdir = "c:/quentin/MusicWorking/Log";
//
// Log print flag: if set to TRUE, prints log messages to screen 
//  and to log file
$logprint = TRUE;
//$logprint = FALSE;

// source directory of wave files and cue files
$srcdir = "c:/quentin/MusicWorking/MusicSrc";

// source directory of wave files and cue files
$cuedir = "c:/quentin/MusicWorking/cuefiles";

// destimation MP3 directory
$mp3dir = "c:/quentin/MusicWorking/MusicMP3";


}
else
{
// Rising-Sun

//
// Log file directory
$logdir = "d:/Music Programming/Log";
//
// Log print flag: if set to TRUE, prints log messages to screen 
//  and to log file
$logprint = TRUE;
//$logprint = FALSE;

// source directory of wave files and cue files
$srcdir = "D:/Music Programming/Music Ref";

// destimation MP3 directory
$mp3dir = "D:/Music Programming/Music Out";
}



// location of coverter binary to be invoked through system call
$converter = "C:\Apps\xrecode3\xrecode3cx64.exe";

// parameters to feed converter
$convparams = "/dest mp3 /cbr 192 /pfilename %track2%";


// Timezone setting
//  -- the string must come from a PHP list found here:
//       http://php.net/manual/en/timezones.php
$sys_time_zone='America/Los_Angeles';

?>