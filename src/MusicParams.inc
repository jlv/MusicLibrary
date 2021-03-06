<?php
//
// Music Library
// Tools and to maintain music library
//

//
// Parameters
//

//
// Timezone setting
//  -- the string must come from a PHP list found here:
//       http://php.net/manual/en/timezones.php
$sys_time_zone='America/Los_Angeles';


//
// Host-based Param Section
//  Params can change as hostname changes
//
// If a param is not set when we get to defaults, it is set
//  using $homedir

// get $hostname to determine host-based parameters
$hostname = gethostname();

if ($hostname == "ProdHost")
{
// Production Host

//
// $homedir - for systems that are built around a single home directory
$homedir="E:\MusicLibrary";

//
// Log file directory
$logdir = "e:/MusicLibrary/Log";
//
// Log print flag: if set to TRUE, prints log messages to screen 
//  and to log file
//$logprint = TRUE;
$logprint = FALSE;

// source directory of wave files and cue files
$srcdir = "e:/MusicLibrary/Music Ref";

// source directory of wave files and cue files
$cuedir = "c:/quentin/MusicWorking/cuefiles";

// destimation MP3 directory
$mp3dir = "e:/MusicLibrary/Music Out";
}
elseif ($hostname == "Rising-Sun")
{
  // Rising-Sun

  // $homedir - for systems that are built around a single home directory
  $homedir="D:\MusicLibrary";

}
elseif ($hostname == "hector")
{
  // hector development machine

  // $homedir - for systems that are built around a single home directory
  $homedir="D:\MusicLibrary";
}
elseif ($hostname == "tonto")
{
  // tonto development machine

  // $homedir - for systems that are built around a single home directory
  $homedir="C:Quentin\MusicLibrary";
}
else
{
  // default system home directory

  // $homedir - for systems that are built around a single home directory
  $homedir="C:\MusicLibrary";
}

//
// Default parameters if not set on a host basis
//

//
// $HomeDir - for systems that are built around a single home directory
if (! isset($homedir)) $homedir="c:\MusicLibrary";

//
// Log file directory
if (! isset($logdir)) $logdir=$homedir . "\Log";
//
// Log print flag: if set to TRUE, prints log messages to screen 
//  and to log file
if (! isset($logprint)) $logprint = TRUE;
//$logprint = FALSE;

// source directory of wave files and cue files
if (! isset($srcdir)) $srcdir=$homedir . "\MusicWav";

// destimation MP3 directory
if (! isset($mp3dir)) $mpcdir=$homedir . "\MusicMP3";

// trash directory
if (! isset($trash)) $mpcdir=$homedir . "\Trash";

//
// other parameters, non-host-based
//

// location of coverter binary to be invoked through system call
if (! isset($converter)) $converter = "C:\Apps\xrecode3\xrecode3cx64.exe";

// parameters to feed converter
$convparams = "/dest mp3 /cbr 192 /pfilename %track2%";



?>