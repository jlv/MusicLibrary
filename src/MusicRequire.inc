<?php
//
// Music Library
// Tools and to maintain music library
//

//
// Library functions used by multiple routines
//

// global _base for base system, including logs
$_base = array();

// global _logctl for log system
$_base['log']['_types'] = array("log"=>"log", "debug"=>"dbg", "error"=>"err", "info"=>"info",
                                 "notify"=>"not", "complete"=>"cmplt" );

//
// load params
//
require "MusicParams.inc";

///
/// globals definitions
///

// software release version
$_base["release_version"]="0.5";


// Set endofline char based on OStype
if ($OStype == "Windows")
  $_base['EOL'] = "\r\n";
else
  $_base['EOL']="\n";

//
// set time zone
//
date_default_timezone_set($sys_time_zone);

//
// function: debug
//  returns true if $debug is true, otherwise returns false
//
//  use in programming: if (debug()) {}
$debug=FALSE;
$debug=TRUE;
//
function debug()
{
    global $debug;
    if ( $debug === TRUE ) return(TRUE); else return(FALSE);
}


///
///
/// Log Functions
///
///
/// log_init() sets up logging at the first of the routine
/// logp() - log individual messages
/// log_close() - closes log files. Not needed if an "exit" is used with
///                logp
///
/// Global array _logctl is populated with control information for all
///  log types.
///   Structure:
///     ["_default"] - default control settings
///     ['_file'] - filename root
///     ['_types'] - indexed array of types that are open
///     [$logtype] - structure for each type of log
///          ["open"] - TRUE means file has been opened
///          ['handle'] - FILE handle
///     ['_log_err'] - set to true if logging a log message had already been attempted
///     ['_err_err'] - set to true if logging an err has been attempted

// function log_init($logroot)
//   $logroot - rootname of log file, after which date and extension are added
//              example: mylog produces file mylog-20181228-074823.log
//
// initializes log control for further use by plog
//
// Uses the following global parameters:
//   - logdir: directory for log files set in musicparams.inc
//   - logfile: path and filename for lag (without suffix)
//   - logprint: boolean flag to
// requires that $logdir be defined.  Uses $logroot as the name of the logfile.
//
function log_init($logroot = "MsLib-Generic", $default_ctl)
{
  global $logdir;
  global $_base;

  // check if $logdir is a directory
  if (! is_dir($logdir))
  {
    print "LOG ERROR: log directory $logdir does not exist.\n";
    print "    Please create or adjust logdir in the parameters file.\n";
    exit(1);
  }

  // look for $logroot and assign generic root if $logroot not assigned
  //if ( $logroot == "" ) $logroot = "MsLib-Generic";

  // set time and full logfile name
  $_base['log']['_file'] = $logdir . "/" . $logroot . "-" . date("Ymd-His");

  // handle default control string
  $_base['log']['_default'] = log_int_ctlstring($default_ctl);

  return;
}

// function logp($control_string, $message)
//  $message - text string of message
//  returns nothing
//
// logs error to log file and prints if $logprint is set to TRUE
//
// Control string is a comma-separated list of options:
// options:
//  log - just log a message.  Not needed if other options besides debug are
//        called. Just a value for the variable placeholder
//  nolog - do not log to regular logfile
//  echo  - echo/print to terminal
//  noecho - do not print to terminal
//  nnl or nonl  - no newline on echo to terminal statement
//  debug - 
//  error - log in error log as well as regular log (.err).  Will be picked up
//           in next error log roll-up (w/in 24 hrs)
//  notify - log in notification log (.not).  Will be picked up for notification
//           in next run, usually hourly
//  info - log in "info" log
//  exitX - exit entire routine with code X. If no code, 0 assumed
//  exit-nologX - exit with code X  without creating a log entry
//  complete - script complete.  Logs in logfile and creates .cmplt file with
//              ==COMPLETE== at end
//  debug - debugging line, only returned if $debug != 0
//
// Example: plog("echo,error", "This is a log message.")
//     Logs in log file, logs message in error file, and echo's to screen
//

function logp($ctl, $message)
{
  global $_base;

  // defaults:
  $msg_logged = FALSE;
  $debug_log = FALSE;
  
  
  // TTD:
  //  write tests
  //  use array to mark file writing?
  //  open log file and keep open?  can you make an array of streams for different file types?
  //  use explde on control structure, then move to associate array?
  //

  // if file hasn't been written, start file

  // check for initialization
  if (! isset($_base['log']['_file']))
  {
    print "LOG ERROR: function logp called without initializing with log_init.\n";
    print "    Exiting.\n";
    exit(1);
  }
  //  parse control options
  $ctlarr = log_int_ctlstring($ctl);

  // set EOL character
  if ($_ctlarr['nl'] === FALSE) $eol = ""; else $eol = $_base['EOL'];

  // if in debug mode and debug set, write debug
  if ( debug() && $ctlarr['debug'] === TRUE )
  {
    log_int_write("debug","$message, "DEBUG:", $eol);
    $debug_log = TRUE;
    $msg_logged = TRUE;
  }

  // loop through non-logging types and if set, log message
  foreach (array("info", "complete" ) as $type)
    if ( $ctlarr[$type] === TRUE)
    {
      log_int_write($type,$message,NULL,$eol);
      $msg_logged = TRUE;
    }

  // add complete tag if needed
  if ( $ctlarr['complete'] === TRUE ) 
    log_int_write("complete","==COMPLETE==", NULL, $eol);
  
  
  // determine if log needed
  //  logic: follow if explicitly set, otherwise, 
  //    if not $msg_logged, write
  if ( isset($ctlarr['log_ctl']))
    // log was explicitly set
    if ( $ctlarr['log_ctl'] === TRUE )
      $types = array('log');
  elseif ( $msg_logged === FALSE )
      $types = array('log');

  // check if error and send
  if ( isset($ctlarr['error']) && $ctlarr['error'] === TRUE )
    $types = array_merge($type, array("error"));

  // output more file types
  foreach ($types as $type)
    log_int_write($type,$message,NULL,$eol);

  // echo to terminal if explicitly set

  // determine if log needed
  //  logic: follow if explicitly set, otherwise, 
  //    if not $msg_logged, write
  if ( isset($ctlarr['log_ctl'])
    // log was explicitly set
    if ( $ctlarr['log_ctl'] === TRUE )
      $types = array('log');
  elseif ( $msg_logged === FALSE )
      $types = array('log');
      
  // determine echo
  //  logic: follow if explicitly set, otherwise, 
  //         if debug, output
  if ( isset($ctlarr['echo_ctl'])
    // log was explicitly set
    if ( $ctlarr['echo_ctl'] === TRUE )
      $types = array('log');
  elseif ( $msg_logged === FALSE )
      $types = array('log');
      
  
  // handle exit
  if ( isset($ctlarr['exit']) && $ctlarr['exit'] === TRUE )
  {
    // log message
    log_int_write("log","Exit {$ctlarr['exit']}",NULL,$eol);
    
    // close log files
    log_close();
    
    // exit
    exit ($ctlarr['exit']);
  } 

  return;
}


// log_close

function log_close ()
{
  // close all log files
  foreach ( $_base['log']['_types'] as $type )
    if ( isset($_base['log'][$types]['handle'] )
    {
      $log_handle = $_base['log'][$types]['handle'];
    
      if(! fclose ($log_handle))
        log_int_error(array("LOG ERROR: logp could not close file",
                          "    {$_base['log']['_file']}.{$_base['log']['_types'][$type]}.",
                          "    Skipping close."), FALSE);
    }

  return;
}


// internal function: logp_int_write ($type, $message, $prefix, $eol)
//  $type - log type (e.g. log, error, ...)
//  $message - message string or array of strings to log
//  $prefix - printed before each message line
//  $eol - end-of-line (OS specific and controlled by param)
//

function logp_int_write($type, $message, $prefix, $eol)
{
  global $_base;

  print "logp_int_write {$type}\n";
  print_r($_base['log']);

  // check for type and get file suffix, or error
  if (isset($_base['log']['_types'][$type]))
    $suffix = $_base['log']['_types'][$type];
  else
    log_int_error("LOG ERROR in logp: type \"{$type}\" was not found. Exiting.", TRUE);

  $filename = $_base['log']['_file'] . '/' . $suffix;
  $logdate = date("Y-m-d-H:i:s: ");

  // if file has not been opened, start the file
  if (( ! isset($_base['log'][$type]["_open"])) ||
       $_base['log'][$type]["_open"] !== TRUE)
  {
    // open the file
    if (! ($_base['log'][$type]['handle'] =
        fopen( $_base['log']['_file'] . "." . $_base['log']['_types'][$type], 'a')))
      log_int_error(array("LOG ERROR: logp could not open",
                          "    {$_base['log']['_file']}.{$_base['log']['_types'][$type]}.",
                          "    Exiting."), TRUE);

    // write log header
    if (! fwrite($_base['log'][$type]['handle'],
                  "{$logdate}MusicLib Log - {$_base['log']['_file']}.{$_base['log']['_types'][$type]}{$_base['EOL']}" .
                  "{$logdate} Software Release {$_base['release_version']}{$_base['EOL']}"))
      log_int_error(array("LOG ERROR: logp could not write to,",
                          "    {$_base['log']['_file']}.{$_base['log']['_types'][$type]}.",
                          "    Exiting."), TRUE);

    if (! fflush($_base['log'][$type]['handle']))
      log_int_error(array("LOG ERROR: logp could not flush to",
                          "    {$_base['log']['_file']}.{$_base['log']['_types'][$type]}.",
                          "    Exiting"), TRUE);

    // set open bit
    $_base['log'][$type]['_open'] = TRUE;
  }

  // normalize message into array
  if (is_array($message)) $msgarr = $message; else  $msgarr = array($message);

  // write log message
  foreach ($msgarr as $msg)
    if (! fwrite($_base['log'][$type]['handle'],"{$logdate}{$msg}{$eol}"))
      log_int_error(array("LOG ERROR: logp could not write to",
                          "    {$_base['log']['_file']}.{$_base['log']['_types'][$type]}.",
                          "    Exiting."), TRUE);

  if (! fflush($_base['log'][$type]['handle']))
    log_int_error(array("LOG ERROR: logp could not flush to",
                        "    {$_base['log']['_file']}.{$_base['log']['_types'][$type]}.",
                        "    Exiting."), TRUE);

  return;
}

// internal function: log_int_error ($message, $fatal)
//  $message - message to log or print
//  $fatal - TRUE means routine will close when log/print attempts have been completed 
//
// logs an error message if possible, otherwise prints to terminal
//

function log_int_error ($message, $fatal)
{
  $print = FALSE;

  // if already tried error, then set $print
  if (isset($_base['log']['_err_err']) && ($_base['log']['_err_err'] === TRUE))
    $print = TRUE;
  else
  {
    $_base['log']['_err_err'] = TRUE;
    logp_int_write("error", $message, NULL, $_base['EOL']);
  }

  // if already tried log, then set $print
  if (isset($_base['log']['_log_err']) && ($_base['log']['_log_err'] === TRUE))
    $print = TRUE;
  else
  {
    $_base['log']['_log_err'] = TRUE;
    logp_int_write("log", $message, NULL, $_base['EOL']);
  }

  if ($print === TRUE || $fatal === TRUE)
  {
    // normalize message into array
    if (is_array($message)) $msgarr = $message; else  $msgarr = array($message);

    // write log message
    foreach ($msgarr as $msg) print "$msg\n";
  }

  // exit if $fatal or $print
  if ($print === TRUE || $fatal === TRUE)
    exit(1);
}


// internal function: log_int_ctlstring ($ctl)
//  $ctl - comma-separated list of control options (see logp for details on controls)
//

function log_int_ctlstring ($ctl)
{
  global $_base;

  $ctl_index = explode(",", $ctl);

  /* set up defaults in ctl_array */
  if (isset($_base['log']['_basectl']))
    $ctl_array = $_base['log']['_basectl'];
  else
    $ctl_array = array();

  foreach ($ctl_index as $option)
  {
    print "ctlstring option:{$option}\n";
    switch ($option)
    {
      case "log": $ctl_array["log_ctl"] = TRUE; break;
      case "nolog": $ctl_array["log_ctl"] = FALSE; break;
      case "echo": $ctl_array["echo_ctl"] = TRUE; break;
      case "noecho": $ctl_array["echo_ctl"] = FALSE; break;
      case "debug": $ctl_array["debug"] = TRUE; break;
      case "notify": $ctl_array["notify"] = TRUE; break;
      case "info": $ctl_array["info"] = TRUE; break;
      case "complete": $ctl_array["complete"] = TRUE; break;
      case "nl": $ctl_array["nl"] = TRUE; break;
      case "nonl": $ctl_array["nl"] = FALSE; break;
      case "nnl": $ctl_array["nl"] = FALSE; break;
      case (preg_match('/^exit\d*$/',$option) ? TRUE : FALSE):
        $ctl_array["exit"] = TRUE;
        $ctl_array["exit_level"] = intval(substr($option, 4));
        if ($ctl_array["exit_level"] == "") $ctl_array["exit_level"] = 0;
        break;
      default:
        log_int_error(array("LOG ERROR: log_int_ctlstring called with unrecognizable option \"$option\".",
                            "     Exiting."), TRUE);
        break;
    }
  }

  return $ctl_array;
}

//
// BACKWARD COMPATIBLE - TEMPORARY - Old Log Functions
//
// function plog($control_string, $message)
//  $error_msg - text string of message
//  returns nothing
//
// logs error to log file and prints if $logprint is set to TRUE
//
// Control string is a comma-separated list of array_of_options
//
// options:
//  log - just log a message.  Not needed if other options besides debug are
//        called. Just a value for the variable placeholder
//  nolog - do not log to regular logfile
//  echo/noecho  - echo/print to terminal
//  nnl  - no newline on echo to terminal statement
//  error - log in error log as well as regular log (.err).  Will be picked up
//           in next error log roll-up (w/in 24 hrs)
//  notify - log in notification log (.not).  Will be picked up for notification
//           in next run, usually hourly
//  exitX - exit entire routine with code X. If no code, 0 assumed
//  exit-nologX - exit with code X  without creating a log entry
//  complete - script complete.  Logs in logfile and creates .cmplt file
//  debug - debugging line, only returned if $debug != 0
//
// Example: logp("echo,error", "This is a log message.")
//     Logs in log file, logs message in error file, and echo's to screen
//

function plog($ctl, $message)
{
  global $logfile;
  global $logprint;

  // TTD:
  //  write tests
  //  use array to mark file writing?
  //  open log file and keep open?  can you make an array of streams for different file types?
  //  use explde on control structure, then move to associate array?
  //

  // if file hasn't been written, start file

  // date-stamp message
  $lmessage = date("Y-m-d-H:i:s: ") . $message . "\r\n";

  // append to end of log
  file_put_contents($logfile, $lmessage , FILE_APPEND | LOCK_EX);

  // if $logprint, print to std out
  if ( $logprint === TRUE )
    print $lmessage;

  return;
}


///
///
/// Shared Functions
///
///


// function crawl($base_folder, $add_folder, $new_base_folder, $ufunction, $options)
//  $base_folder - initial root folder
//  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
//       full path name.  $add_folder can be blank to start (and usually is).  Used by
//       recursive function to crawl.
//  $new_base_folder - target base folder for functions that are moving/writing files
//       from a base to a new_base
//  $ufunction - string of user function name to perform on non-folders.  This
//       function is called on every directory entry that is not a directory itself.
//       matching format of function:
//         ufunction($base_folder, $add_folder, $new_base_folder, $filename, $array_of_options)
//  $options - array of options passed to $ufunction
//  no return value;
//    NOTE: should we return something?
//
// crawl function - crawls each directory under $base folder and executes $function
//       on any files that are not a directory.
//
// A typical call to crawl would start with $add_folder set to "".
//
// Note: uses '/' to delimit directories.  This works on windows platforms as well.
//

function crawl($base_folder, $add_folder, $new_base_folder, $ufunction, $options)
{
  global $debug;	// global debug flag

  // concat $base_folder and $add_folder if $add_folder exists
  //   $catfolder is full folder name
  //   $catfile is delimter based on whether we are adding to existing $add_folder
  if ($add_folder == '' )
  {
    $catfolder = $base_folder;
    $catfile = '';
  }
  else
  {
    $catfolder = $base_folder . "/" . $add_folder;
    $catfile = '/';
  }

  if ( $debug == 1 ) print "\n\nCrawl: in FOLDER:" . $catfolder . "\n\n";

  // check if $catfolder is a directory, then jump in
  if((is_dir($catfolder)) && ($catfolder != ".") && ($catfolder != ".."))
  {
    // Procedes if $folder is a directory
    //   Open directory, read directory contents, and iterate through
    $dir = opendir($catfolder); // Creates a directory handle
    while (($file = readdir($dir)) !== false)
      // crawl further if $file is a directory
      //   Note: avoid .  and .. file structure
      if(($file == ".") || ($file == ".."))
        print '';
      else if((is_dir($catfolder . '/' .$file)))
        // nested crawl.  Note concating $file to extend $add_folder
        crawl($base_folder, $add_folder . $catfile . $file, $new_base_folder, $ufunction, $options);
      else
        // call user function on $file
        if (is_callable($ufunction, FALSE))
          call_user_func($ufunction, $base_folder, $add_folder, $new_base_folder, $file, $options);
        else
          plog("User function '" . $ufunction . "' is not callable.");

     // close directory
     closedir($dir);
  }
  else
    // If selected file is not a folder, print error
    plog("Error: shouldn't call crawl function without a folder.");

} // end of crawl function


// function getSuffix($file_name)
//   $file_name - name of file, including full-path file names
//   returns string suffix
//
function getSuffix($file_name)
{
	$suffix = strrchr($file_name, "."); // Sets suffix to the file type, either jpg or mov
	$suffix = substr($suffix, 1, strlen($suffix) - 1); // Cleans up $suffix
	return $suffix;
} // end of function

// function fileAge($file_name)
//   $file_name - name of file, including full-path file names
//   returns days age of file
//
function fileAge($file_name)
{ // Returns the file age in days
	$systime=time();
	$filetime = filemtime ($file_name);
	$diff = $systime - $filetime;

	$daydiff = ($systime - $filetime ) / (60*60*24);
	return $daydiff;
}// end of function



?>
