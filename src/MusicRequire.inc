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
$_base["release_version"]="0.6";


// base on OStype, set EOL character, filesep character, and filesep to be used in strings
if ($OStype == "Windows") {
  $_base['EOL'] = "\r\n";
  $_base['filesep'] = '\\';
  $_base['filesepstr'] = '\\\\\\';
} else {
  $_base['EOL'] = "\n";
  $_base['filesep'] = '/';
  $_base['filesepstr'] = '\\/';
}

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
//$debug=TRUE;
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
/// logp_init() sets up logging at the first of the routine
/// logp() - log individual messages
/// logp_close() - closes log files. Not needed if an "exit" is used with
///                logp
///
/// Global array _logctl is populated with control information for all
///  log types.
///   Structure:
///     ["_default"] - default control settings
///     ['_file'] - filename root -- also used to identify where log has been initialized
///     ['_logroot'] - $logroot part of _file
///     ['_types'] - indexed array of log types available (set as a constant)
///     [$logtype] - structure for each type of log
///          ['open'] - TRUE means file has been opened
///          ['handle'] - FILE handle
///          ['echo'] - TRUE means option set to echo when this type of log is output
///     ['_log_err'] - set to true if logging a log message had already been attempted
///     ['_err_err'] - set to true if logging an err has been attempted
///     ['_default_str'] - copy of latest _default_ctl string from logp_init
///     ['_options_str'] - copy of latest options string from logp_init

// function logp_init($logroot, $default_ctl, $options)
//   $logroot - rootname of log file, after which date and extension are added
//              example: mylog produces file mylog-20181228-074823.log
//   $default_ctl - control string that sets defaults. For control options, see
//              logp function below.
//              To clear current default_ctl, use "clear" or "none"
//   $options - options that are set globally for log system, generally of the form
//              option1[=foo],option2[=bar],..., or just option,option2,...
//              To clear current options, use "clear" or "none"
//              Options available:
//                echo[$logtype][=false] - if specified, echos messages for logtype.
//                                         Defaults to TRUE if no =. FALSE means doesn't
//                                         echo messages as a results of calling logtype.
//
// initializes or changes log control for further use by logp.
//   - if values are set to null, no changes take place
//
// Uses globals:
//   - $logdir: directory for log files set in musicparams.inc
//   - $log_default_name - default Logroot name if not specified
//   - $log_detault_options - default options if not specified on initial call
//   - $_base: internal structure for base data
//

function logp_init($logroot, $default_ctl=NULL, $options=NULL)
{
  global $logdir;
  global $log_default_name;
  global $log_default_options;
  global $_base;

  $initial = TRUE;

  // check if $logdir is a directory
  if (! is_dir($logdir))
  {
    print "LOG ERROR: log directory $logdir does not exist.\n";
    print "    Please create or adjust logdir in the parameters file.\n";
    exit(1);
  }

  // check if file (initialized indicator) is already set and handle input as replacement
  if (isset ($_base['log']['_file']))
  {
    // remove initial flag because it's set
    $initial = FALSE;
//    logp("log",
//      "WARNING: logp_init called additonal time after already being initialized. Using latest call's default params.");

    // check if file root has changed
    if ( $logroot != NULL && $logroot != "" && $_base['log']['_logroot'] != $logroot )
    {
      logp("log",
        array("WARNING: additional logp_init call does not match file from previous call:",
            "  Previous file: ". $_base['log']['_file'],
            "  New file in call: " . $logdir . "/" . $logroot . "-" . date("Ymd-His"),
            " Closing previous files and opening new log files.",
            " Continuing logging with new file."));

      // close current logs in preparation for new file
      logp_close();

      // set time and full logfile name, logroot
      $_base['log']['_file'] = $logdir . "/" . $logroot . "-" . date("Ymd-His");
      $_base['log']['_logroot'] = $logroot;
    }
  }
  else  // not already initialized
  {
    // check $logroot is set or apply defaults
    if (! isset($logroot) || $logroot == "")
      if (isset($log_default_name)) $logroot = $log_default_name;
      else $logroot = "Log-Default";

    // set time and full logfile name, logroot
    $_base['log']['_file'] = $logdir . "/" . $logroot . "-" . date("Ymd-His");
    $_base['log']['_logroot'] = $logroot;

    // look for log_default_options
    if (! isset($options) || $options == "")
      if (isset($log_default_options)) $options = $log_default_options;
  }

  // handle default control string array
  if ( $initial === TRUE || $default_ctl != NULL )
  {
    // check for clear
    if ( $default_ctl == "clear" || $default_ctl == "CLEAR" || $default_ctl == "none"
          || $default_ctl == "NONE")
      $def_str=NULL; else $def_str=$default_ctl;
    $_base['log']['_default_str'] = $def_str;
    $_base['log']['_default'] = logp_int_ctlstring($def_str);
  }

  // handle options string
  if ( $initial === TRUE || $options != NULL )
  {
    // check for clear
    if ( $options == "clear" || $options == "CLEAR" || $options == "none"
          || $options == "NONE")
      $opt_str=NULL; else $opt_str=$options;

    // log in string
    $_base['log']['_options_str'] = $opt_str;

    // parse options and set
    $opt_index = explode(",", $opt_str);
    foreach ($opt_index as $sub_opt)
    {
      $opt_parts = explode("=", $sub_opt);

      // test only 1 =
      if ( isset($opt_parts[2]))
        logp("error,echo,exit1", "FATAL ERROR: logp_init called with two = in param, '$sub_opt'");

      // split var
      $var_parts = explode("[", $opt_parts[0]);
      $var = $var_parts[0];
      if (isset($var_parts[1]))
        $ind = rtrim($var_parts[1], "]");
      else $ind = NULL;

//      printf("var:{$var}, ind:{$ind}, val:{$opt_parts[1]}");

      // dispatch
      switch ($var)
      {
       case NULL: break;
       case "echo":
         // check if valid $ind
         if (! isset($_base['log']['_types']["$ind"]))
           logp_int_error(array("LOG ERROR: logp_init called with unrecognizable log type \"$sub_opt\".",
                                "     Exiting."), TRUE);
         // assign
         if (isset($opt_parts[1])) $val=$opt_parts[1]; else $val=TRUE;
         $_base['log']["$ind"]['echo'] = $val;
         break;

       default:
         logp_int_error(array("LOG ERROR: logp_init called with unrecognizable option \"$sub_opt\".",
                                "     Exiting."), TRUE);
         break;
      }  // switch
    }  // foreach
  } // if option

  // if log is open, log the init call
//  if (( isset($_base['log']['log']['open'])) || $_base['log']['log']['open'] == TRUE)
  if ( isset($_base['log']['log']['open']))
    logp("log",
      "logp_init: Default_ctl:{$_base['log']['_default_str']}  Options:{$_base['log']['_options_str']}");
  return;
}

// function logp($control_string, $message)
//  $message - text string of message, or array of strings to log
//  returns nothing
//
// logs message to appropriate files and echos to terminal if set
//
// Control string is a comma-separated list of options:
// options:
//  log - just log a message.  Not needed if other options besides debug are
//        called as all messages are logged.
//  nolog - do not log to regular logfile
//  echo  - echo/print to terminal
//  noecho - do not print to terminal
//  nnl or nonl  - no newline on echo to terminal statement
//  error - log in error log as well as regular log (.err).  Will be picked up
//           in next error log roll-up (w/in 24 hrs)
//  notify - log in notification log (.not).  Will be picked up for notification
//           in next run, usually hourly
//  info - log in "info" log
//  exitX - exit entire routine with code X. If no code, 0 assumed
//  exit-nologX - exit with code X  without creating a log entry
//  complete - script complete.  Logs in logfile and creates .cmplt file with
//              message and ==COMPLETE== at end
//  debug - debugging line, only returned if debug() != 0
//
// Example: logp("echo,info", "This is a log message.")
//     Logs in log file, info file, and echos to screen
//

function logp($ctl, $message)
{
  global $_base;

  // flags initial state
  $log_exec = FALSE;
  $debug_logged = FALSE;
  $echo_exec = FALSE;
  $prefix = NULL;

  // check for initialization
  if (! isset($_base['log']['_file']))
  {
    print "LOG ERROR: function logp called without first initializing with logp_init.\n";
    print "    Exiting.\n";
    exit(1);
  }
  //  parse control options
  $ctlarr = logp_int_ctlstring($ctl);

  // set EOL character for file and echo
  if ($ctlarr['nl'] === FALSE)
  {
    $eol = "";
    $echo_eol = "";
  }
  else
  {
    $eol = $_base['EOL'];
    $echo_eol = "\n";
  }

  // handle debug logging
  if ( $ctlarr['debug'] === TRUE )
  {
    // signal logger not to log based on just this line
    $debug_logged = TRUE;
    // write debug if in debug state
    if ( debug() ) logp_int_write("debug", $message, "DEBUG:", $eol);
  }

  // loop through non-logging types and if set, log message
  foreach (array("info", "complete", "notify", "error" ) as $type)
    if ( isset($ctlarr[$type]) && $ctlarr[$type] === TRUE)
    {
      logp_int_write($type, $message, NULL, $eol);

      // check for echo option set
      if (isset($_base['log']["$type"]['echo']))
            $echo_exec = $_base['log']["$type"]['echo'];

      // set log flag
      $log_exec = TRUE;
    }

  // add complete tag if needed
  if ( isset($ctlarr['complete']) && $ctlarr['complete'] === TRUE )
    logp_int_write("complete", "==COMPLETE==", NULL, $eol);

  // determine if log needed
  //  logic: follow if explicitly set, otherwise,
  //    follow flag or defaultto TRUE if not debug_logged
  if ( isset($ctlarr['log_ctl']))
  {
    if ( $ctlarr['log_ctl'] === FALSE ) $log_exec = FALSE;
    else $log_exec = TRUE;
  }
  elseif ( $debug_logged === FALSE ) $log_exec = TRUE;

  // execute log and debug write as well
  if ( $log_exec === TRUE )
  {
    logp_int_write("log", $message, NULL, $eol);
    // write debug log if debug is on
    if ( $debug_logged === FALSE && debug() )
      logp_int_write("debug", $message, NULL, $eol);
  }

  // determine echo
  //  logic: follow if explicitly set, otherwise,
  //         if debug, output
  if ( isset($ctlarr['echo_ctl']))
  {
    // echo was explicitly set or explicitly not set
//    if ( $ctlarr['echo_ctl'] === TRUE )
    if ( $ctlarr['echo_ctl'] == TRUE )
      $echo_exec = TRUE;
    elseif ( $ctlarr['echo_ctl'] == FALSE )
      $echo_exec = FALSE;
  }
  elseif ( $debug_logged === TRUE && debug() )
  {
    $echo_exec = TRUE;
    $prefix = "DEBUG:";
  }

  // execute echo
  if ( $echo_exec === TRUE )
  {
    // normalize message into array
    if (is_array($message)) $msgarr = $message; else  $msgarr = array($message);

    // echo message
    foreach ($msgarr as $msg)
      print $prefix . $msg . $echo_eol;
  }

  // handle exit
  if ( isset($ctlarr['exit']) && $ctlarr['exit'] === TRUE )
  {
    // log message
    logp_int_write("log", "Exit {$ctlarr['exit']}", NULL, $eol);

    // close log files
    logp_close();

    // exit
    exit ($ctlarr['exit_level']);
  }

  return;
}


// logp_close

function logp_close ()
{
  global $_base;

  // close all log files
  foreach ( $_base['log']['_types'] as $type )
    if ( isset($_base['log'][$type]['handle'] ))
    {
      $log_handle = $_base['log'][$type]['handle'];

      if(! fclose ($log_handle))
        logp_int_error(array("LOG ERROR: logp could not close file",
                          "    {$_base['log']['_file']}.{$_base['log']['_types'][$type]}.",
                          "    Skipping close."), FALSE);

      // unset handle and open
      unset($_base['log'][$type]['handle']);
      unset($_base['log'][$type]['open']);
    }

  // unset file params
  unset( $_base['log']['_file'] );
  unset( $_base['log']['_logroot'] );
  unset( $_base['log']['_basectl'] );
  unset( $_base['log']['_default'] );

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

//  print "logp_int_write {$type}\n";
//  print_r($_base['log']);

  // check for type and get file suffix, or error
  if (isset($_base['log']['_types'][$type]))
    $suffix = $_base['log']['_types'][$type];
  else
    logp_int_error("LOG ERROR in logp: type \"{$type}\" was not found. Exiting.", TRUE);

  $filename = $_base['log']['_file'] . '/' . $suffix;
  $logdate = date("Y-m-d-H:i:s: ");

  // if file has not been opened, start the file
  if (( ! isset($_base['log'][$type]['open'])) ||
       $_base['log'][$type]['open'] !== TRUE)
  {
    // open the file, or use logp_int_error to report fail
    if (! ($_base['log'][$type]['handle'] =
        fopen( $_base['log']['_file'] . "." . $_base['log']['_types'][$type], 'a')))
      logp_int_error(array("LOG ERROR: logp could not open",
                          "    {$_base['log']['_file']}.{$_base['log']['_types'][$type]}.",
                          "    Exiting."), TRUE);

    // write log header
    if (! fwrite($_base['log'][$type]['handle'],
                  "{$logdate}MusicLib Log - {$_base['log']['_file']}.{$_base['log']['_types'][$type]}{$eol}" .
                  "{$logdate} Default_ctl:{$_base['log']['_default_str']}  Options:{$_base['log']['_options_str']}{$eol}" .
                  "{$logdate} Software Release {$_base['release_version']}{$eol}"))
      logp_int_error(array("LOG ERROR: logp could not write to,",
                          "    {$_base['log']['_file']}.{$_base['log']['_types'][$type]}.",
                          "    Exiting."), TRUE);

    if (! fflush($_base['log'][$type]['handle']))
      logp_int_error(array("LOG ERROR: logp could not flush to",
                          "    {$_base['log']['_file']}.{$_base['log']['_types'][$type]}.",
                          "    Exiting"), TRUE);

    // set open bit
    $_base['log'][$type]['open'] = TRUE;
  }

  // normalize message into array
  if (is_array($message)) $msgarr = $message; else  $msgarr = array($message);

  // write log message
  foreach ($msgarr as $msg)
    if (! fwrite($_base['log'][$type]['handle'],"{$logdate}{$prefix}{$msg}{$eol}"))
      logp_int_error(array("LOG ERROR: logp could not write to",
                          "    {$_base['log']['_file']}.{$_base['log']['_types'][$type]}.",
                          "    Exiting."), TRUE);

  if (! fflush($_base['log'][$type]['handle']))
    logp_int_error(array("LOG ERROR: logp could not flush to",
                        "    {$_base['log']['_file']}.{$_base['log']['_types'][$type]}.",
                        "    Exiting."), TRUE);

  return;
}

// internal function: logp_int_error ($message, $fatal)
//  $message - message to log or print
//  $fatal - TRUE means routine will close when log/print attempts have been completed
//
// logs an error message if possible, otherwise prints to terminal
//

function logp_int_error ($message, $fatal)
{
  global $_base;
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


// internal function: logp_int_ctlstring ($ctl)
//  $ctl - comma-separated list of control options (see logp for details on controls)
//
// return array with default options

function logp_int_ctlstring ($ctl)
{
  global $_base;

  $ctl_index = explode(",", $ctl);

  /* set up defaults in ctl_array */
  if (isset($_base['log']['_default']))
    $ctl_array = $_base['log']['_default'];
  else
    $ctl_array = array();

  // defaults inherent in system if not set
  if (! isset($ctl_array['nl']) ) $ctl_array['nl'] = TRUE;
  if (! isset($ctl_array['debug']) ) $ctl_array['debug'] = FALSE;

  foreach ($ctl_index as $option)
  {
  //  print "ctlstring option:{$option}\n";
    switch ($option)
    {
      case "": break;
      case "log": $ctl_array["log_ctl"] = TRUE; break;
      case "nolog": $ctl_array["log_ctl"] = FALSE; break;
      case "echo": $ctl_array["echo_ctl"] = TRUE; break;
      case "noecho": $ctl_array["echo_ctl"] = FALSE; break;
      case "error": $ctl_array["error"] = TRUE; break;
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
        logp_int_error(array("LOG ERROR: logp_int_ctlstring called with unrecognizable option \"$option\".",
                            "     Exiting."), TRUE);
        break;
    }
  }

  return $ctl_array;
}

///
///
/// Shared Functions
///
///


// function isDryRun()
//
// isDryRun function - used to exctract global in conditionals
// returns global $isDryRun
function isDryRun(){
  global $isDryRun;
  if (isset($isDryRun)) return $isDryRun; else return FALSE;
}

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

function crawl($base_folder, $add_folder, $new_base_folder, $ufunction, $options = array())
{
  global $debug;	// global debug flag
  $return = TRUE;

  // concat $base_folder and $add_folder if $add_folder exists
  //   $catfolder is full folder name
  //   $catfile is delimter based on whether we are adding to existing $add_folder
  if ($add_folder == '' )  {
    $catfolder = $base_folder;
    $catfile = '';
  } else {
    $catfolder = $base_folder . "/" . $add_folder;
    $catfile = '/';
  }

  if ( $debug == 1 ) print "\n\nCrawl: in FOLDER:" . $catfolder . "\n\n";

  // check if $catfolder is a directory, then jump in
  if((is_dir($catfolder)) && ($catfolder != ".") && ($catfolder != "..")) {
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
        $return = crawl($base_folder, $add_folder . $catfile . $file, $new_base_folder, $ufunction, $options);
      else
        // call user function on $file
        if (is_callable($ufunction, FALSE))
          $return = call_user_func($ufunction, $base_folder, $add_folder, $new_base_folder, $file, $options);
        else
          logp("echo,error,exit1",
                  "FATAL ERROR crawl: User function '" . $ufunction . "' is not callable.");

     // close directory
     closedir($dir);
  }
  else
    // If selected file is not a folder, print error
    logp("echo,error,exit1","Error: folder \"$catfolder\" is not a folder. Exiting.");

  return $return;
} // end of crawl function


// function getSuffix($file_name)
//   $file_name - name of file, including full-path file names
//   returns string suffix
//
function getSuffix($file_name)
{
	// $suffix = strrchr($file_name, "."); // Sets suffix to the file type, either jpg or mov
	// $suffix = substr($suffix, 1, strlen($suffix) - 1); // Cleans up $suffix
  $suffix = "";
  $matches = array();

  if (preg_match("/^(.*)(\.)(.*)$/", $file_name, $matches)) {
    $suffix = strtolower($matches[3]);
  }

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


// function checkNoCue($path)
//  $path - path to file or directory
//
// Returns: TRUE if directory is a "no cue" directory in which cue files should
//   not be tested or expect.  FALSE otherwise.
//
// Tests whether the directory in the path has a .nocue file present.
// if $path is a file, the directory is taken

function checkNoCue($path)  {
  global $_base;

  // trim path if trailing /
  $path = preg_replace("/\/$/", '', $path);

  $flist = explode('/', $path);
  $fcount = count($flist);
  $name = NULL;
  $prepath = NULL;
  $matches = array();

// print "\nSTART checknoCUE {$fcount}\n";
//   print_r($flist);

  // check if directory and get name, otherwise find name from file
  if (is_dir($path)) {
    if ($fcount > 1) {
      $name = $flist[$fcount - 1];
      $prepath = $path;
    }
  } else {
    if ($fcount > 1) {
      $name = $flist[$fcount - 2];
      if (preg_match("/^(.*)\//", $path, $matches))
           $prepath = $matches[1];

    }
  } // end else is_dir

  // if no name, use cwd
  if ($name == NULL) {
//print "  in NONAME, base:{$_base['filesep']}:{$_base['filesepstr']}:\n";
    $cwd = getcwd();
    // replace filesep with /
    $cwd = str_replace($_base['filesep'], '/', $cwd);
//    $cwd_array = explode($_base['filesep'], $cwd);
    $cwd_array = explode('/', $cwd);
    $name = end($cwd_array);
    $prepath = ".";
    if ($name == NULL) return FALSE;
  }

//print "NOCUE:{$fcount}:${path}:\n  :{$prepath}:\n  :{$name}:\n";
  // look for nocue
  if (file_exists( $prepath . "/" . $name . ".nocue"))  return TRUE;

  return FALSE;
}


// function checkCueCovered($base_folder, $add_folder, $file, $check_type)
//  $base_folder - initial root folder
//  $add_folder - the folder path to added to $base_folder as crawl proceeds
//  $check_type - "continue" - will continue if cuefile is found and depth at $cue_file_dir_depth
//                   so that the cuefile can be used.  Will error if there should be a cuefile there.
//                   "error" will error if cuefile is not found and .nocue is not present
//                   and there should be a cuefile there.
//
//  checkCueCovered checks if cue file exists in directory. If $album.nocue exists
//   returns success even though there is not a cue file.
//
//  returns FALSE if cue file is needed, TRUE if not needed

function checkCueCovered($base_folder, $add_folder, $check_type = "error") {
  global $cue_file_dir_depth;

  // $flist - array of $add_folder split between every /
  $flist = preg_split("/\//", $add_folder);

  // $album - last input of $list, which will be the album title
  $fcnt=count($flist);
  $album = $flist[$fcnt - 1];

  // look for cuefile if file depth greater than 2 (album level in file heirachy)
  if( $check_type == "continue" ) {
    if ( $fcnt >= $cue_file_dir_depth &&
       file_exists($base_folder . '/' . $add_folder . '/' . $album . '.cue'))
      return true;
    elseif ( $fcnt < $cue_file_dir_depth ||
            file_exists($base_folder . '/' . $add_folder . '/' . $album . '.nocue'))
      return false;
    else {
     logp("error", array("ERROR checkCueCovered: cue file not found. Fcnt:{$fcnt}",
                         "  '{$base_folder}/{$add_folder}'"));
      return false;
    }
  }
  elseif ( $check_type == "error" )
    if ( $fcnt >= $cue_file_dir_depth &&
       ! file_exists($base_folder . '/' . $add_folder . '/' . $album . '.cue') &&
       ! file_exists($base_folder . '/' . $add_folder . '/' . $album . '.nocue'))
    {
     logp("error", array("ERROR checkCueCovered: no .cue file found.  Fcnt:{$fcnt}",
                          "    {$base_folder}/{$add_folder}"));
      return false;
    }
    else
      return true;
  else // back $check_type
  {
      logp("error", "ERROR in checkCueCovered: $check_type \"{$check_type}\" must be either 'continue' or 'error'.");
      return false;
  }

} // end of function


// function verifyCue($base_folder, $add_folder, $file, [$silent = false], [&$cuefile] [skip_file])
//  $base_folder - initial root folder
//  $add_folder - the folder path to added to $base_folder as crawl proceeds
//  $file - filename within containing folder
//  $silent - performs function without producing errors or verification in logs. Used when
//             evaluation is needed rather than error reporting.
//  $cuefile - cuefile array to be tested. if $cuefile is provided, it is used
//             instead of reading the file.
//  $skip_file - if TRUE, skip file testing
//
// Returns TRUE if successul, FALSE on failure
//
//
//  verifyCue verifies that the file is a cuefile, it is well-formed
//   and the files it references exist. If not a cue file, returns TRUE to
//   skip.
//
//  if $cuefile is provided, it is used instead of reading the file.
//
//  The file must be named *.cue or *.cue.cand

function verifyCue($base_folder, $add_folder, $file, $silent = false,
                     &$cuefile = array(), $skip_file = false) {
  // initialize values
  $matches=array();
  // start with no error
  $return = true;

  // build $full_path
  $pre_path = "";
  if ( $base_folder != "" ) $pre_path = $base_folder . '/';
  if ( $add_folder != "" ) $pre_path = $pre_path . $add_folder . '/';
  $full_path = $pre_path . $file;

  // if .nocue file exists, we don't check anything.  Just return.
  //
  // $flist - array of $add_folder split between every /
  $flist = preg_split("/\//", $add_folder);
// JLV try this:
// $flist = explode('/', $add_folder);
  // get artist/album
  $list_count=count($flist);
  // if less than album/artist, return
  if ($list_count < 2) return TRUE;

  $album = $flist[$list_count - 1];
  $artist = $flist[$list_count - 2];

  // check for non-null $album, $artist
  if ($album == NULL)
  {
    logp("error",array("ERROR verify: album portion of add_folder is null. ",
           "  File:{$full_path}"));
    return FALSE;
  }

  // check for non-null $album, $artist
  if ($artist == NULL)
  {
    logp("error",array("ERROR verify: artist/performer portion of add_folder is null. ",
           "  File:{$full_path}"));
    return FALSE;
  }

  // check for .nocue existance
//  if(file_exists($pre_path . $album . '.nocue'))
  if (checkNoCue($pre_path))  {
    logp("log","Verify found .nocue file. Skipping verification of {$pre_path}'");
    return TRUE;
  }

  //
  // check for cue
  //
  // if file is a cue file or candidate (.cue.cand) file
  if(preg_match('/\.cue$/i', $file) || preg_match('/\.cue\.cand$/i', $file)) {
    // general log
    // keep it quiet?
    if ( $silent == false )
      logp("log", array("Verifying: {$add_folder},", "   '{$file}'"));

    //
    // check if cue file base matches folder album name
    //
    // strip getSuffix
    $base = preg_replace("/\.cand$/", '', $file);
    $base = preg_replace("/\.cue$/", '', $base);
//print "BASE:{$base}\n";

    if ($base != $album)
    {
       logp("error",
         array("ERROR verify: '{$file}'",
             "  cue file found has name that does not match containing folder album:",
             "  Album:{$album}",
             "  File:{$file}"));
       return false;
    }

    // use or read cue file
    if (! $cuefile) {
      $cuefile = file($full_path, FILE_IGNORE_NEW_LINES);
      if ( $cuefile === false )
        logp("error,exit1","FATAL ERROR verify: could not read cue file '{$full_path}'. Exiting.");
    }

    // initialize state for scan
    $in_tracks=FALSE;
    $found_track_title=TRUE;
    $found_track_index1=TRUE;

    // loop through each line of file checking various attributes
    //foreach ($cuefile as $input) {
    for($i = 0; $i < count($cuefile); $i++)
    {
      $input=$cuefile[$i];

      // check artist line
      if($in_tracks == FALSE && preg_match("/(^\s*PERFORMER \")(.*)\".*/",$input,$matches))
      {
        if ($matches[2] != $artist)
        {
          if ( $silent == false )
            logp("error", array(
              "ERROR verify: artist/performer from directory path, '{$artist}', does not",
              "  match that from cufile, '{$matches[2]}'.",
              "  File:{$full_path}"));
          return FALSE;
        }
      }

      // check album line
      if($in_tracks == FALSE && preg_match("/(^\s*TITLE \")(.*)\".*/",$input,$matches))
        if ($matches[2] != $album)
        {
          if ( $silent == false )
          logp("error", array(
            "ERROR verify: album from directory path, '{$album}', does not",
            "  match that from cuefile, '{$matches[2]}'.",
            "  File:{$full_path}"));
          return FALSE;
        }

      // check each file line as a start of next track
      if (preg_match ( '/^\a*FILE/', $input ))
      {
        // Check variables from last track, then reinitialize per-track vars
        if ($found_track_title == FALSE)
        {
          if ( $silent == false )
            logp("error",array("ERROR verify: Could not find track title in track {$track_no}",
                   "  File:{$full_path}"));
          $return=FALSE;
        }
        // Check variables from last track, then reinitialize per-track vars
        if ($found_track_index1 == FALSE)
        {
          if ( $silent == false )
            logp("error",array("ERROR verify: Could not find INDEX 01 in track {$track_no}",
                    "  File:{$full_path}"));
          $return=FALSE;
        }

        // (re)initialize for start of new track
        $in_tracks=TRUE;
        $found_track_title=FALSE;
        $found_track_index1=FALSE;
        $track_no="";
        $ftrack_no="";

        //gets part just between quotes
        // trckfile - root filename
        // stitle - song title with trackNO
        $trkfile = preg_replace("/FILE \"/", '', $input);
        $trkfile = preg_replace("/\".*$/", '', $trkfile);

        // get track no and check format
        if (preg_match("/(^\d{2,}) /", $trkfile, $matches))
          $ftrack_no=$matches[1];
        else
        {
          if ( $silent == false )
            logp("error", array("ERROR verify: track filename does not start with a track number followed by a space",
                    "  Track file: {$trkfile}", "  File: {$full_path}"));
          $return = FALSE;
          continue;
        }

        // get song title
        $stitle = preg_replace("/.wav$/", '', $trkfile);
        $stitle = preg_replace("/(^\d{2,}) /", '', $stitle);

        //checks if track file name exists in directory
//        $fileExists = file_exists($pre_path . $trkfile);
//        if(! $fileExists){
        if ( $skip_file != TRUE && ! file_exists($pre_path . $trkfile)) {
          if ( $silent == false )
            logp("error", array("ERROR verify: file in track {$ftrack_no} does not exist.",
                    "  Track file: {$trkfile}", "  File: {$full_path}"));
          $return = FALSE;
        }

        //checks backslash
        if(preg_match('/\\\/', $trkfile)) {
          if ( $silent == false )
            logp("error", array("ERROR verify: filename in track {$ftrack_no} has backslash",
                 "  Track file: {$trkfile}", "  File:{$full_path}"));
          $return = FALSE;
        }

        // check format starts with tracknum
        if(! preg_match("/^\d{2,3} /", $trkfile)) {
          if ( $silent == false )
            logp("error", array("ERROR verify: filename does not start with a track number followed by a space",
                    "  Track file: {$trkfile}", "  File: {$full_path}"));
          $return = FALSE;
        }

        // -, whitespace, not allowed after tracknum
        if(preg_match("/^\d{2,3} [-\s]/", $trkfile)) {
          if ( $silent == false )
            logp("error", array("ERROR verify: filename in track {$ftrack_no} has - or space after track number",
                    "  Track file: {$trkfile}", "  File: {$full_path}"));
          $return = FALSE;
        }

        //"
        if(preg_match("/([~\?\*\+\[\]\{\}\^\$\|<>:;\/\"])/", $trkfile, $matches)){
          if ( $silent == false )
            logp("error", array(
                    "ERROR verify: file name in track {$ftrack_no} has invalid special character, '{$matches[1]}'",
                    "  Track file: {$trkfile}", "  File: {$full_path}"));
          $return = FALSE;
        }

        if(!preg_match("/\.wav$/i",$trkfile)){
          if ( $silent == false )
            logp("error", array("ERROR: filename in track {$ftrack_no} does not end in .wav",
                    "  Track file: {$trkfile}", "  File: {$full_path}"));
          $return = FALSE;
        }

        // look ahead up to two lines for TRACK directive
        $j=1;
        while($found_track_title == FALSE && $j < 3)
          if(preg_match("/(^ *TRACK )(\d+)/", $cuefile[$i+$j++], $matches))
          {

            $track_no = $matches[2];
            $found_track_title = TRUE;

            // check against $ftrack_no
            if (intval($track_no) != intval($ftrack_no))
            {
              if ( $silent == false )
                logp("error",array(
                  "ERROR verify: track number in file name, '{$ftrack_no}', does not match track number",
                  "  in TRACK directive, '{$track_no}'",
                    "  Track file: {$trkfile}", "  File: {$full_path}"));
              $return=FALSE;
            }
          }

      } // file line

      // check for INDEX 00 for correct setting
//      if(preg_match("/(^\s*INDEX 00 )([0-9:]*)(.*)/",$input,$matches))
      if(preg_match("/(^\s*INDEX 00 )([\d:]*)(.*)/",$input,$matches))
        if ($matches[2] != "00:00:00")
        {
          if ( $silent == false )
            logp("error", array(
                  "WARNING verify: Non-standard INDEX 00 '{$matches[2]}' found in current cuefile in track {$ftrack_no}.",
                  "  File: {$full_path}"));
          $return = FALSE;
        }

      // check for INDEX 01 for correct setting
//      if(preg_match("/(^\s*INDEX 01 )([0-9:]*)(.*)/",$input,$matches))
      if(preg_match("/(^\s*INDEX 01 )([\d:]*)(.*)/",$input,$matches))
        if ($matches[2] == "00:00:00")
          $found_track_index1=TRUE;
        else
        {
          if ( $silent == false )
            logp("error", array(
              "WARNING verify: Non-standard INDEX 01 '{$matches[2]}' found in current cuefile in track {$ftrack_no}.",
              "  Track file: {$trkfile}", "  File: {$full_path}"));
          $return = FALSE;
        }
    }  // foreach line of file
  }  // if cue file
 return $return;
} // end of function


// function: getCueInfo($infotype, $file, [&$cuefile])
//  $infotype - artist or album
//  $file - file name (full path)
//  $cuefile - if exists, ignores $file and uses $cuefile
//
// Returns information element in file or FALSE for error in reading
//
// Gets information element from file

function getCueInfo($infotype, $file, &$cuefile=array()) {
  // initialize
  $matches=array();
  $filemsg = " File: '{$file}'";

  // check for file or cuefile
  if ($cuefile)
    $filemsg = "Cuefile provided, " . $filemsg;
  else
    if (file_exists($file)) {
      $cuefile = file($file, FILE_IGNORE_NEW_LINES);
      if ( $cuefile === false )
        logp("error,exit1","FATAL ERROR in getCueInfo: could not read file '{$file}'. Exiting.");
    } else {
      logp("error","ERROR in getCueInfo: file '{$file}' does not exist.");
      return FALSE;
    }

  // load infotag
  if ($infotype == "album") $infotag = "TITLE";
  elseif ($infotype == "artist") $infotag = "PERFORMER";
  else
    log("error,exit1","FATAL ERROR: getCueInfo call with bad info type '{$infotype}'");
//print "INFOTYPE:{$infotype}\n";
//readline("getcueinfo pause>");
  // read input, look for title
  foreach ($cuefile as $line)
    if (preg_match("/^\s*{$infotag}\s*\"(.*)\"$/", $line, $matches))
       return $matches[1];
    elseif (preg_match("/^\s*FILE /", $line, $matches)) {
      // if we find file, there was not title previously.  Error
      logp("error",array(
            "ERROR in getCueInfo: reached FILE tag without finding {$intofype}/{$infotag} tag.",
               $filemsg));
      return FALSE;
    }

  // reached here without finding info element.  Error out.
  logp("error",array(
        "ERROR in getCueInfo: reached end of file without finding {$intofype}/{$infotag} tag.",
                     $filemsg));
  return FALSE;
}


// function: countTracks(&$cuefile)
//
// returns an array of various attributes of the cuefile:
//  [song_file_cnt] - number of FILE directives
//  [track_cnt] - number TRACK directives
//  [max_track] - maximum specified track number in a TRACK directive
//  [max_file_track] - maximum specified track number by track number in file
//                     FILE directive
//  [max_all] - maximum of all values
//  [cnt_pad] - calculcated pad number based on counting TRACK stmts (2 vs 3)
//  [file_cnt_pad] - calculcated pad number based on counting FILE (2 vs 3)
//  [track_pad] - calculcated pad number based track definition (2 vs 3)
//
//  the file and track counts should be the same.  Will return FALSE if not.

function countTracks(&$cuefile) {
  // initialize Variables
  $song_file_cnt=0;
  $track_cnt=0;
  $max_track=0;
  $max_file_track=0;
  $matches=array();
  $return=TRUE;

  // loop through file
  foreach ($cuefile as $line)
  {
    // FILE
    if( preg_match ( '/^\s*FILE/', $line))
    {
      $song_file_cnt++;

      // find track from file title
      $song = preg_replace("/^\s*FILE \"/", '', $line);
      $song = preg_replace("/^.*\\\/", '', $song);
      if (preg_match("/(^\d{2,3}) /", $song, $matches))
        $max_file_track=max(intval($matches[1]), $max_file_track);
      else
      {
        logp("error", array("ERROR: cannot find track number in FILE line",
                 "  {$line}"));
        $return=FALSE;
      }
    }

    elseif ( preg_match ( '/^\s*TRACK /', $line))
    {
      $track_cnt++;

      // find TRACK from line
      if (preg_match("/(^\s*TRACK )(\d{2,3})/", $line, $matches))
        $max_track=max(intval($matches[2]), $max_track);
      else
      {
        logp("error", array("ERROR: cannot find track number in TRACK line",
                 "  {$line}"));
        $return=FALSE;
      }
    } // elseif TRACK
  } // foreach

  // simple analysis
  $max_all = max($song_file_cnt, $track_cnt, $max_track, $max_file_track);

  if ($song_file_cnt > $track_cnt)  {
    logp("error", array(
      "ERROR: number of FILE ({$song_file_cnt}) is greater than TRACK ({$track_cnt}) statements.");
    $return=FALSE;
  }

  if ($track_cnt > 99) $cnt_pad=3; else $cnt_pad=2;
  if ($song_file_cnt > 99) $file_cnt_pad=3; else $file_cnt_pad=2;
  if ($max_track > 99) $track_pad=3; else $track_pad=2;
  if ($max_all > 99) $max_pad=3; else $max_pad=2;

  // return
  return array("song_file_cnt"=>$song_file_cnt, "track_cnt"=>$track_cnt,
               "max_track"=>$max_track, "max_file_track"=>$max_file_track,
               "max_all"=>$max_all, "cnt_pad"=>$cnt_pad,
               "track_pad"=>$track_pad, "file_cnt_pad"=>$file_cnt_pad,
               "max_pad"=>$max_pad, "return"=>$return);
}  // function


// function trackifyCue(&cuefile, &$wav, ["reorder"])
// function trackifyCue(&cuefile, ["reorder"])
//  $cuefile - cuefile array to be returned
//  $wav - wav array to transport wav files
//  ??$pad - number of zero-padded digits for this album
//  ??$cue_meta - meta data for each line of cuefile (format [line][element])
//  $option:
//     reorder - reorder tracks into 1-x sequence, disregarding current track
//     fixup - fixup FILE line, keeping old file
//
// Returns TRUE if successul, FALSE on failure
//
//  Helper function to rewrite tracks in wav file, remove extra directories
//   in path name, etc.  Loads $wav

function trackifyCue(&$cuefile, $option = FALSE)  {
  // initialize
  $matches = array();
  $tracks = array();
  $cur_track = 1;

  // get artist and album, then check if cue file exists in directory
  if ( ($artist = getCueInfo("artist", '', $cuefile)) == FALSE ) return FALSE;
  if ( ($album = getCueInfo("album", '', $cuefile)) == FALSE ) return FALSE;

  // check if over 99 tracks and set $pad
  $count_arr = countTracks($cuefile);
  if ($count_arr["return"] =! TRUE) return FALSE;
  // pick pad
  if ($option = "reorder")
    $pad = $count_arr["cnt_pad"];
  else
    $pad = $count_arr["max_pad"];

//print_r($cuefile);
  // loop through each line. If track was not found after FILE, error
  //  using $track_found.
  $track="";
  $track_found = TRUE;
  $cur_track = 1;
  for($i=0; $i < count($cuefile); $i++)
  {
    // look for FILE lines
    if( preg_match("/^\s*FILE\s+\"/", $cuefile[$i])) {
      // replace artist and album
//print "trackify----\n  artist:{$artist}\n  album:{$album}\n";

// JLV: deal with this functionality in fixFileLine
      // foreach(array($artist, $album) as $component) {
      //   // get component without dots
      //   $comp_no_dot = str_replace('.', '', $component);
      //   foreach(array($component, $comp_no_dot) as $repl)
      //     $cuefile[$i] = str_replace("{$repl}\\", '', $cuefile[$i]);
      // }

      // if fixup option, fix line


      // check if any \ and error
      if (preg_match('/\\\/', $cuefile[$i])) {
        logp("error",array("ERROR: FILE title has a backslash.  Skipping entire cuefile.",
                  "  Line: '{$cuefile[$i]}'"));
        return FALSE;
      }

      // get trackno and replace with padded version
      if( preg_match("/^(\s*FILE\s+\")(\d+)( .*)\"/", $cuefile[$i], $matches))  {
        // check if we saw a track before this FILE
        if ($track_found != TRUE) {
          logp("error",array(
                  "ERROR: found FILE statement before preceding FILE's TRACK statement.",
                  "  Stmt: '{$cuefile[$i]}'"));
          return FALSE;
        }
        $track_found = FALSE;

        // set key vars
        $curfilebase = $matches[3];
        $track_file=$matches[2];

        // choose track type
        if ($option == "reorder")
          $track = $cur_track;
        else
          $track = intval($track_file);
        $new_track = str_pad($track, $pad, "0", STR_PAD_LEFT);

        // store in wav
        // $wav[$track_no]["old"] = $track_no . $curfilebase;
        // $wav[$track_no]["new"] = $new_track_no . $curfilebase;

//print "track={$track}, ";

        $cuefile[$i] = preg_replace("/^(\s*FILE\s+\")(\d+)( )/",
              '${1}'. $new_track . " ",
              $cuefile[$i]);
      } else {
        logp("error",array(
                "ERROR: could not find track in FILE line.  Skipping entire cuefile.",
                "  Line: '{$cuefile[$i]}'"));
        return FALSE;
      }
    } // end of if preg FILE

    // find TRACK and replace track number
    if( preg_match("/^\s*TRACK\s+/", $cuefile[$i])) {
      // extract track_track or error
      if( preg_match("/^\s*TRACK\s+(\d+)/", $cuefile[$i], $matches)) {
        $track_track = $matches[1];
      else {
        logp("error",array(
                "ERROR trackify: TRACK statement found without a track number. Please check.",
                "  Stmt: '{$cuefile[$i]}'" ));
        return FALSE;
      }

      // if this track is from the FILE statement, compare with what we found in FILE
      if ($track_found == FALSE  && $track_track != $track_file) {
        logp("error",;array(
                "ERROR: track from FILE and track TRACK did not match. Please check.",
                "  Track from FILE line: '{$track_file}'",
                "  Track from TRACK line: '{$track_track}'",
                "  Line: {$cuefile[$i]}"));
        return FALSE;
      }

      // if this track is a subsequent track on the same file, use extractioing or calc
      if ($track_found == TRUE)
        if ($option == "reorder")
          $track = $cur_track;
        else
          $track = intval($track_track);
        $new_track = str_pad($track, $pad, "0", STR_PAD_LEFT);

      // check that track hasn't already be used, otherwise set
      if (isset($tracks[$new_track]))  {
        logp("error", array(
                "ERROR: duplicate track number found, '{$new_track}'. Skipping cuefile",
                "  New track '{$new_track}'",
                "  Line: '{$cuefile[$i]}'"));
        return FALSE;
      } else
        $tracks[$new_track] = 1;

      if ($new_track != "") {
        $cuefile[$i] = preg_replace("/(^\s*TRACK\s+)(\d+)(.*)/",
                  '${1}' . $new_track . '${3}',
                  $cuefile[$i]);
  //               '${1}' . str_pad($track, 2, "0", STR_PAD_LEFT) . '${3}',
        $track="";
      }
      else {
        // track is bad
        logp("error",array(
                 "ERROR: track processing reduced track to a NULL. Unclear why."
                 "   Line: {$cuefile[$i]}"));
      }

      // increment cur_track
      $cur_track++;

    } // end of if TRACK

  } // end of for - reading file

  return TRUE;
}



// function fixFileLine($add_folder, &$cuefile, $cueindex, $cue_meta, &$wav, []$command])
//  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
//       full path name.  $add_folder can be blank to start (and usually is).  Used by
//       recursive function to crawl.
//  $line - FILE line from .cue file
//  &$wav - array of file names needed to rename .wav files in album
//  $pad - pad width for track numbers
//  $command - normal, fixup
//  $options
//
// Returns string which is the updated line, else returns FALSE on failure
//
// fixFileLine - fixes a previous file format to new, correct standard
//   - adds old and new file line to $wav array
//
// loads
//
// Issues:
//  calling twice after we fix, then retrack
//    if we don't call twice, we must keep track of which cuefile contibutes which line
// NOTES (important):
//  - ** Must be called only once per line of final cuefile **
//       Here's why:
//        because fixFileLine loads $wav with the old flie and new file, the song/track
//        rewrite needs to be done as a single step.  Otherwise, we need to maintain
//        changes and updates to the $wav array which seems more fragile and complex.
//        So, figure out total tracks in file and keep track of each contributing cuefile
//        before this routine is called.
//  - $artst/$album are intended to be embedded as the last two levels in the $add_folder
//  - $album isn't checked


function fixFileLine($add_folder, &$cuefile, $cueindex, $cue_meta, &$wav, $command = "normal") {
  // initialize
  $matches = array();

  // gets $artist and $album
  $list = preg_split("/\//", $add_folder);
  $list_cnt=count($list);
  if ($list_cnt < 2) {
    logp("error","ERROR: add_folder must be of the form ..artist/album");
    return FALSE;
  }
  $album = $list[$list_cnt - 1];
  $artist = $list[$list_cnt - 2];

// JLV: needed?
  // fixes () and . if they are in album/title
  // $reg_album = regify($album);
  // $reg_artist = regify($artist);
  // $album = fixDot($album);
  // $artist = fixDot($artist);
  // $album = fixBrackets($album);
  // $artist = fixBrackets($artist);

  // get $song from FILE
  $songfile = preg_replace("/^\s*FILE\s+\"/", '', $line);
  // safety check - if not a file line
  if ($songfile == $line) {
    logp("error",array(
            "ERROR fixFileLine: fed line that is not a file line.",
            "  Line: $line"));
    return FALSE;
  }
  $songfile = preg_replace("/\"\s+WAVE.*$/", '', $songfile);


  // replace $artist and $album directory level 0.  Keep space.
  $songfile = str_replace("{$artist}\\{$album}\\", ' ', $songfile);

  // check for additional directory delimiters and error if they exist
  //  note: can upgrade this to using parameterized delimiters from $_base.
  if (preg_match("/\\\/", $songfile)) {
    logp("error", array(
           "ERROR in fixFileLine: file has remaining directory delimiters after fixup.",
           "  Filename:{$songfile}"))
    return FALSE;
  }

  // get current track no
  if (preg_match("/^(\d+)", $songfile, $matches))
    $track_no = $matches[1];
  else {
    logp("error",array(
           "ERROR in fixFileLine: cannot find track at the start of the song file name",
           "  Filename:{$songfile}"));
    return FALSE;
  }
//  $track_pad = str_pad($track_no, $pad, "0", STR_PAD_LEFT);

//  $tooLong = $track_no .  "-" . strtoupper(substr($artist, 0, 3)) . "~" . "1.wav";

  // if $songfile is a tooLong (i.e. NN~AAA~1.wav), create a new songfile line
  if ($songfile == $track_no .  "-" . strtoupper(substr($artist, 0, 3)) . "~" . "1.wav")
    // look ahead and find title
    $song_found = FALSE;
    for($j=$index; $j < count($cuefile); $j++) {
      // break if we find file
      if (preg_match("/^\s*FILE\s+/", $cuefile[$j])) break;

      // look for title
      if (preg_match("/^\s*TITLE\s+\"(.*)\"/", $cuefile[$j], $matches))  {
        $cue_song = $matches[1];
        $song_found = TRUE;
        break;
      }
    } // end of for

    if ($song_found == FALSE) {
      logp("error",array(
             "ERROR in fixFileLine: in attempting to rebuild song title file name for a",
             "  title that was originally too long, could not find a TITLE line for the",
             "  song title.",
             "  Original name: {$songfile}"));
      return FALSE;
    }

    // remove any problem characters fron $cue_song and make it $newSong
    $cue_song = str_replace('.', '', $cue_song);
    $cue_song = str_replace('/', '', $cue_song);

    $new_song = $track_no . " " . $cue_song
  // if $songfile not a tooLong, fix up newsong if needed
  else {
    // string .wav while fixing
    $new_song = preg_replace("/\.[wW][aA[vV]$/", '', $songfile);

    // if command is "fixup"
    if ($command == "fixup")  {
      // check for ~ or - delimeter, then fix song title
      if(preg_match("/~/", $new_song)) {
        $new_song = str_replace("/~ {$artist} ~ /", '', $new_song);
        $new_song = str_replace("/{$album} ~ /", '', $new_song);
        $new_song = str_replace("/~ /", '', $new_song);
      }
      elseif(preg_match("/-/", $new_songfile)){
        $new_song = str_replace("/- {$artist} /", '', $new_song);
        $new_song = str_replace("/{$artist} /", '', $new_song);
        $new_song = str_replace("/- {$album} - /", '', $new_song);
      }
    } // end if fixup
  } // end else tooLong test

  // cuts all double spaces any .
  $new_song = preg_replace("/\s+/", " ", $new_song);
  $new_song = preg_replace("/\./", ".", $new_song);

  // checks if replacement proccess worked. if not, return failure
  if($new_song == null){
    logp("error", "ERROR fixFileLine: replace failures in .cue");
    return FALSE;

  // add wav
  $new_song .= ".wav";

  // write to cuefile
  $cuefile[$index] = "FILE \"" . $new_song . " WAVE"

  // create $wav entry
  $wav[] = array (
    "old" => $songfile,
    "new" => $new_song,
    "old_dir" => $cue_meta[$index]["dir"],
    "new_dir" => ""
  )

  return TRUE;
} // end of function



function fixFileLine_DEPR($command, $add_folder, $line, &$wav, $pad, $old_dir, $new_dir){
    // initialize
    $matches = array();

    // gets $artist and $album
    $list = preg_split("/\//", $add_folder);
    $list_cnt=count($list);
    if ($list_cnt < 2) {
      logp("error","ERROR: add_folder must be of the form ..artist/album");
      return FALSE;
    }
    $album = $list[$list_cnt - 1];
    $artist = $list[$list_cnt - 2];

    // fixes () and . if they are in album/title
    $reg_album = regify($album);
    $reg_artist = regify($artist);
    $album = fixDot($album);
    $artist = fixDot($artist);
    $album = fixBrackets($album);
    $artist = fixBrackets($artist);

    // get $song from FILE
    $song = preg_replace("/^\s*FILE\s+\"/", '', $line);
    $song = preg_replace("/\"\s+WAVE.*$/", '', $song);

    $song = preg_replace("/\\\/", '', $song);
    $song = preg_replace("/{$artist}{$album}/", '', $song);

    // checks to see that $album and $artist are correctly in $song. ERROR and exit if not
    if((!preg_match("/{$artist} /", $song) || !preg_match("/{$album} /", $song))
        && ((preg_match("/ ~ .* ~ /", $song) || preg_match("/ - .* - /", $song)))){
      logp("error", "ERROR: {$artist}/{$album} in .cue file does not match .wav file");
      return 0;
    }

    // check for ~ or - delimeter, then fixes song title
    if(preg_match("/^\d+.* ~/", $song)){
      $song = preg_replace("/~ {$artist} ~ /", '', $song);
      $song = preg_replace("/{$album} ~ /", '', $song);
      $song = preg_replace("/~ /", '', $song);
    }
    elseif(preg_match("/^\d+.* -/", $song)){
      $song = preg_replace("/- {$artist} /", '', $song);
      $song = preg_replace("/{$artist} /", '', $song);
      $song = preg_replace("/- {$album} - /", '', $song);
    }

    // update tracknum
    if (preg_match("/(^\d+)/", $song, $matches))
      $track_no = intval($matches[1]);
    else {
      logp("error",array(
               "ERROR fixFileLine: cannot find track number in song title.",
               "  {$song}"));
      return FALSE;
    }
    // cut tracknumber and potential . then add padded track no
    $song = preg_replace("/^\d+\.*/", '', $song);
    $song = str_pad($track_no, $pad, "0", STR_PAD_LEFT) . $song;

    // // replace the track getprotobynumber
    // $cutout = "/^\d{2,3}\./";
    // $replace = "";
    // if(preg_match("/^\d\d\d/", $song)){
    //   $replace = substr($song, 0, 3);
    // }else {
    //   $replace = substr($song, 0, 2);
    // }
    // $song = preg_replace($cutout, $replace, $song);

    // cuts all double spaces and extra .
    $song = preg_replace("/\s+/", " ", $song);
    $song = preg_replace("/\.+/", ".", $song);
    // checks if replacement proccess worked. if not, return failure
    if($song == null){
      logp("error", "ERROR fixFileLine: preg_replace failure in .cue");
      return false;
    }
    $line = "FILE \"{$song}\" WAVE";

    // load new $song into $wav
    $wav["new"] = $song;
    return $line;
  } // end of function


// function fixParens($str)
//  $str - given to string
//
// Returns altered (or not) string
//
// Esacpes characters in strings
function regify($str){
  $str = str_replace("/\(/", "\\(", $str);
  $str = str_replace("/\)/", "\\)", $str);
  $str = str_replace("/\[/", "\\[", $str);
  $str = str_replace("/\]/", "\\]", $str);
  $str = str_replace("/\./", "\\.", $str);
  return $str;
}

    // function fixParens($str)
    //  $str - given to string that is to be fixed
    //
    // fixDash function - changes [] to \[\] for regex functions. NOTE only use when you want str in regex
    // returns fixed string
    function fixBrackets_DEPR($str){
      $str = preg_replace("/\[/", "\\[", $str);
      $str = preg_replace("/\]/", "\\]", $str);
      return $str;
    }

    // function fixDot($str)
    //  $str - given to string that is to be fixed
    //
    // fixDot function - changes all . to \. for regex function
    // retruns fixed $str
    function fixDot_DEPR($str){
      $str = preg_replace("/\./", "\\.", $str);
      return $str;
    }



// function: makeCueConvertable(&$cuefile)
//
// Makes a cue file convertable by xrecode3.
//
//
// Returns TRUE if successul, FALSE on failure
//
//  To convert property, INDEX 01 must be at 00:00:00.  Comments out INDEX 01,
//   and uses that figure for a PREGAP setting that must occur before
//   the first INDEX statement

function makeCueConvertable(&$cuefile) {
  global $_base;

  // changes all INDEX to be correct for mp3
  $cue_count=count($cuefile);
  for($i = 0; $i < $cue_count; $i++){
    $matches = array();
    if(preg_match("/(^\s*INDEX\s+00\s+)([0-9:]+)/", $cuefile[$i], $matches)){
      // safety check NN:NN:NN
      if ($matches[2] != "00:00:00")
        logp("info","WARNING: Non-standard INDEX 00 '{$matches[2]}' found in current cuefile. Check log for file.");

      // look at INDEX 01 and capture time
      $matches = array();
      if (preg_match("/(^\s*INDEX\s+01\s+)([0-9:]+)/", $cuefile[$i+1], $matches))
      {
        // if INDEX 01 not, 00:00:00, then add PREGAP before INDEX 00
        if ($matches[2] != "00:00:00")
        {
          // add PREGAP
          array_splice($cuefile,$i,0,array("    PREGAP {$matches[2]}"));
// may need to fix for multi
//          array_splice($cuefile,$i,0,array("    PREGAP {$matches[2]}{$_base['EOL']}"));
          $cue_count++;
          $i++;

          // skip 00 line and remark INDEX 1 line
          $i++;
          $cuefile[$i] = "    REM orig: " . $cuefile[$i];

          // move to next line and add new index line starting at 00:00:00
          $i++;
// may need to fix for multi
//          array_splice($cuefile,$i,0,array("    INDEX 01 00:00:00{$_base['EOL']}"));
          array_splice($cuefile,$i,0,array("    INDEX 01 00:00:00"));
          $cue_count++;
        } // matches not 00:00:00
      }  // preg match 01
    }   // preg match 00

    // safety check for an INDEX 01 that is not 00:00:00.
    //  Currently returns false aborting conversion.
    $matches = array();
    if(preg_match("/(^ *INDEX\s+01\s+)([0-9:]+)/", $cuefile[$i], $matches) && $matches[2] != "00:00:00")
    {
      logp("error,info",array(
             "WARNING: Non-converted INDEX 01 with value '{$matches[2]}' found in current",
             "  cuefile.  Value should be '00:00:00'.",
             "  Check log above or info file below this warning for offending file.",
             "  Stopping conversion process for this file.")
          );
      return FALSE;
    }
  }  // loop
  return TRUE;
}

// function convertFromCue($base_folder, $add_folder, $new_base_folder, $file, $options)
//  $base_folder - initial root folder
//  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
//       full path name.  $add_folder can be blank to start (and usually is).  Used by
//       recursive function to crawl.
//  $new_base_folder - target base folder for functions that are moving/writing files
//       from a base to a new_base; in this case, will be endpoint of mp3 files
//  $file - name of file passed to function
//  $options - array of options passed to function
//
//
// Returns TRUE if successul, FALSE on failure
//
// uses cuefile to look up individual wav files and convert to formats
//
// Future work:
//  could implement $options to do things like only convert one of the conversion options
//

function convertFromCue($base_folder, $add_folder, $new_base_folder, $file, $options){
  global $conversions;

  // return if not a .cue file
  if (! preg_match('/\.cue$/i', $file))  return TRUE;
//logp("log","convert: {$add_folder}, {$file}");
  // Makes sure that the cue file is all good
  if (verifyCue($base_folder, $add_folder, $file) !== true){
    logp("error", array("ERROR: folder '{$add_folder}',",
                        "  file '{$file}'",
                        " failed on verifyCue call. Returning."));
    return false;
  }

  $pre_path = $base_folder;
  if ( $base_folder != "" ) $pre_path .= '/';
  $pre_path .= $add_folder;

  // check for noCue
  if (checkNoCue($pre_path)) return TRUE;

  // get info from folder

  $list = preg_split("/\//", $add_folder);

  // first much check if the album and songs exist in $new_base_folder
  $list_count = count($list);
  if ($list_count < 2) return TRUE;
  // if ($list_count < 2)
  //   logp("error,exit2",array(
  //         "FATAL ERROR in convertFromCue: $add_folder param must have an artist level",
  //         "  and album level. Offending value is: '{$add_folder}'")
  //       );

  $album = $list[$list_count - 1];
  $artist = $list[$list_count - 2];

  // check if new base_folder exists
//  if (! file_exists($new_base_folder))
//    log("error,exit1","FATAL ERROR in convertFromCue: base folder '{$new_base_folder}' does not exist.");

  // cycle through each conversion in $conversions
  foreach ($conversions as $key=>$conversion)
  {
    // only process if convert is TRUE
    if ($conversion["convert"] == TRUE)
    {
      // logp
      logp("log","Converting to {$key}: {$base_folder}, {$add_folder}, {$file}");

      // change directory if specified in params
      if ($conversion["cd_dir"] == TRUE) chdir($base_folder . '/' . $add_folder);

      // make base_folder if needed
      if (isDryRun())
        logp("log","mkdir, recursive: {$conversion["base_folder"]}");
      elseif (! file_exists($conversion["base_folder"]) && ! mkdir($conversion["base_folder"],NULL,TRUE))
          logp("error,exit1",array(
                 "FATAL ERROR in convertFromCue: could not create base folder",
                 " '{$conversion["base_folder"]}'.")
               );

      // make base_folder if needed
      $out_dir = $conversion["base_folder"] . "/" . $add_folder;
      if (isDryRun())
        logp("log","mkdir, recursive: {$out_dir}");
      elseif (! file_exists($out_dir) && ! mkdir($out_dir,NULL,TRUE))
         logp("error,exit1",array(
               "FATAL ERROR in convertFromCue: could not create output folder",
               " '{$outdir}'.")
                    );
      // execute the conversion on the command line
      $command = "{$conversion["converter"]}{$conversion["pre-cue"]}\"{$file}\"{$conversion["pre-out"]}";
      $command = $command . "\"{$out_dir}\"{$conversion["post-out"]}";

      logp("log", "Command:" . $command);
      // exec command
      if (! isDryRun())
      {
        $rescode=0;
        system($command,$rescode);
        if ($rescode != 0)
        {
          logp("error","ERROR: conversion command failed for '{$add_folder}', file '{$file}'");
          return FALSE;
        }
      } // DryRun
    } // if convert
  }  // foreach

  // inform
  logp("info",array("Converted '{$add_folder}',",
                "   '{$file}', ",
                "   from '{$base_folder}'"));
  return;
}



// function moveWav(&$wav, [$reverse = false], [$test_exist = false])
//  $wav - array of wav file info (see below)
//  $reverse - optional parameter when set to "reverse" renames the files in the other direction.
//
// Returns TRUE if successul, FALSE on failure
//
// moveWav function - moves array of wav files
//
//  wav array: The 2D array uses Index as first dimension and as 2nd dim
//           "old", "new", "old_dir", and "new_dir" for data values.
//         e.g. $wav[1]["new"]

function moveWav(&$wav, $reverse = false, $test_exist = false)  {
  $return = TRUE;
  if ($test_exist == FALSE) $msg = "Renaming"; else $msg="Test existance";

  // general message if dryRun
  if (isDryRun()) logp("log","DryRun: the following would be renamed:");

  //print_r($wav);
  foreach ($wav as $key=>$index) {
    // test existance or hard error
    if (! isset($index["old"]) || $index["old"] == NULL)
      logp("error,exit1","FATAL ERROR in moveWav: null 'old' value in key '{$key}'");

    if (! isset($index["new"]) || $index["new"] == NULL)
      logp("error,exit1","FATAL ERROR in moveWav: null 'new' value in key '{$key}'");

    // set file names
    if (isset($index["old_dir"]) && $index["old_dir"] != NULL)
      $old_path = $index["old_dir"] . "/" . $index["old"];
    else
      $old_path = $index["old"];

    if (isset($index["new_dir"]) && $index["new_dir"] != NULL)
      $new_path = $index["new_dir"] . "/" . $index["new"];
    else
      $new_path = $index["new"];

    // if same, no need to rewrite
    if ($old_path == $new_path)
      logp("log","Old/New are the same. No rewrite: {$old_path}");
    // rename based on direction
    elseif ( $reverse != true ) {
      logp("log", array("{$msg} '{$old_path}'", "  as '{$new_path}'."));

      if (file_exists($old_path)) {
        if (! isDryRun() && $test_exist == FALSE && ! rename($old_path, $new_path))  {
          logp("error", array("ERROR moveWav: failure on renaming '{$old_path}'",
                 " to '{$new_path}'"));
          $return = FALSE;
        }
      } else {
        logp("error","ERROR moveWav: file does not exist, '{$old_path}'");
        $return=FALSE;
      }
    } else {   // reverse the rename
      logp("log", array("Reverse {$msg} '{$new_path}'", " as '{$old_path}'."));

      if (file_exists($new_path)) {
        if (! isDryRun && $test_exist == FALSE && ! rename($new_path, $old_path))  {
          logp("error", array("ERROR moveWav: failure on reverse renaming '{$new_path}'",
                  " as '{$old_path}'"));
          $return = FALSE;
        }
      } else {
        logp("error","ERROR moveWav: file does not exist, '{$new_path}'");
        $return=FALSE;
      }
    } // else reverse
  } // foreach

  return $return;

} // function

// function moveToTrash($trash)
//  $trash - array of file names or file paths to move to trash directory
//  $trashed - array of file names or file paths that have been moved to trash directory
//
// Returns TRUE on success, FALSE on failure
//
// moves (renames) each file in array to trash directory in current directory,
//  removes file from list, then moves file name to trashed list


function moveToTrash(&$trash = array(), &$trashed = array()) {
  global $trash_name;
  $return = TRUE;

  // if trash directory doesn't exist, make it
  if (! is_dir($trash_name))
    if (! mkdir($trash_name))
      logp("error,exit1",
            "FATAL ERROR: trash directory, '{$trash_name}' does not exist and cannot be made.");

  // if .nocue file doesn't exist, make it
  if (! file_exists($trash_name . "/" . $trash_name . ".nocue"))
    if (! file_put_contents($trash_name . "/" . $trash_name . ".nocue", array("No cue")))
      logp("error,exit1",
            "FATAL ERROR: trash no cue file, '{$trash_name}.nocue' does not exist and cannot be made.");
//print "IN Trash\n";
//print_r($trash);
//print_r($trashed);
  // loop through and move files
  foreach($trash as $key=>$file) {
    // strip off and precending path
// JLV: is there one?
    $filename = preg_replace("/^.*\//", '', $file);
    $filename = preg_replace("/^.*\\\/", '', $filename);

//print "Trash File:{$file}\nFilename:{$filename}\n";

    // Dry Run vs. real
    if (isDryRun())
      logp("log","DryRun: moving file to trash: '{$file}'");
    elseif (! rename($file, $trash_name . "/" . $filename)) {
      logp("error", array("ERROR: could not move '{$file}' to '{$trash_name}'.",
                    " Please check."));
      $return = FALSE;
    }

    // bookkeeping
    unset($trash[$key]);
    $trashed[] = $file;

  } // end of foreach

  return $return;
}

// function addLineTerm(&$array)
//  &$array - array of strings that will make up a file
//
//  NOTE &$array is a reference variable
// returns nothing
//
// addLineTerm function - line break / EOL character to each line in array
function addLineTerm(&$array){
  global $_base;
  for($i = 0; $i < count($array); $i++){
    $array[$i] .= $_base['EOL'];
  }
}

// function readline($prompt)
//  $prompt - text before input
//
// reads line from prompt input
// defines readline function if it doesn't exist

if(!function_exists("readline")) {
  function readline($prompt = null){
    if($prompt){
        echo $prompt;
    }
    $fp = fopen("php://stdin","r");
    $line = rtrim(fgets($fp, 1024));
    return $line;
  }
}

// function getArgOptions($argv, &$options)
//  $argv - argv from command line
//  $options - array in which to return options
//
// Returns: TRUE if option loaded, FALSE if no option found
//
// Takes a regular argv, looks though each arg: if starts with --
//  assumes it's an option.  If --option=value, then creates a key=>value
//  pair in $options, otherwise creates a key=>TRUE pair.

function getArgOptions($argv, &$options = array())  {
  $return = FALSE;
  $options=array();
  $arg_arr=array();

  // look through options, skipping [0]
  for ($i=1; $i < count($argv); $i++) {
    // if arg starts with --
    if (substr($argv[$i],0,2) == '--') {
      $arg_arr = explode('=',substr($argv[$i],2));
        if (isset($arg_arr[1]))
          $options[$arg_arr[0]] = $arg_arr[1];
        else
          $options[$arg_arr[0]] = TRUE;

      $return=TRUE;
    }
  } // end of for loop

  return $return;
}



// show that we're debugging as an example
if (debug()) logp("echo","Debugging turned on.");

?>
