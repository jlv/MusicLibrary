<?php
//
// Music Library
// Tools and to maintain music library
//

//
// Parameters
//

//
// Host-based Param Section
//  Params can change as hostname changes
//
// If a param is not set when we get to defaults, it is set
//  using $homedir
//
// Forward slash should work on windows and linux
// OStype is a system global that should be set with each host
//

//
// Default params (which can be overwritten by host-specification below)
//
// OStype  Values: Windows, Linux
$OStype="Windows";

//////////////
//
// Host-based Params
//
// get $hostname to determine host-based parameters
$hostname = gethostname();

if ($hostname == "ProdHost")
{
  // Production Host

  // $homedir - for systems that are built around a single home directory
  $homedir="E:\MusicLibrary";

  //
  // Log file directory
  //$logdir = "e:/MusicLibrary/Log";
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

  // $homedir - for systems that are built around a single home directory
  $homedir="D:\MusicLibrary";

  // source directory of wave files and cue files
  $srcdir = "N:/A";

  // source directory of wave files and cue files
  $cuedir = "N:/Cuefiles";

  // destimation MP3 directory
  $mp3dir = "e:/MP3";

}
elseif ($hostname == "Hector")
{
  // hector development machine

  // $homedir - for systems that are built around a single home directory
  $homedir="D:/Quentin/MusicProgramming";

}
elseif ($hostname == "tonto")
{
  // tonto development machine

  // $homedir - for systems that are built around a single home directory
  $homedir="C:Quentin\MusicLibrary";

  // source directory of wave files and cue files
  $cuedir = "c:/quentin/MusicWorking/cuefiles";

}
else
{
  // default system home directory with no specification

  // $homedir - for systems that are built around a single home directory
  $homedir="C:\MusicLibrary";
}

//
// Default parameters if not set on a host basis
//

// OStype - default to windows
if (! isset($OStype)) $OStype="Windows";

//
// $HomeDir - for systems that are built around a single home directory
if (! isset($homedir)) $homedir="C:\MusicLibrary";

//
// Log file directory, as default
if (! isset($logdir)) $logdir=$homedir . "/Log";

// source directory of wave files and cue files, as default
if (! isset($srcdir)) $srcdir=$homedir . "/MusicWav";

// destimation MP3 directory, as default
if (! isset($mp3dir)) $mpcdir=$homedir . "/MusicMP3";

// trash directory, as default
if (! isset($trash)) $mpcdir=$homedir . "/Trash";

//
// other parameters, non-host-based
//

// default log root
$log_default_name = "MsLib-Generic";

// location of coverter binary to be invoked through system call
if (! isset($converter)) $converter = "C:\Apps\xrecode3\xrecode3cx64.exe";

// parameters to feed converter
$convparams = "/dest mp3 /cbr 192 /pfilename %track2%";

//
// Timezone setting
//  -- the string must come from a PHP list found here:
//       http://php.net/manual/en/timezones.php
$sys_time_zone='America/Los_Angeles';

//
// isDryRun - used by ServerFix to trial run vs. real thing
//
$isDryRun=false;
$isDryRun=true;

// function isDryRun()
//
// isDryRun function - used to exctract global in conditionals
// returns global $isDryRun
function isDryRun(){
  global $isDryRun;
  return $isDryRun;
}


// end of MusicParams.inc
?>
