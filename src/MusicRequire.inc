<?php
//
// Music Library
// Tools and to maintain music library
//

//
// Library functions used by multiple routines
//

require "MusicParams.inc";

// software release version
$release_version="0.5";

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
/// Use log_init() to set up logging at the first of the routine
/// Use plog to log individual messages
///  logroot is the root name of the log/err/notify file

// function log_init($logroot)
//   $logroot - rootname of log file, after which date and extension are added
//              example: mylog produces file mylog-20181228-074823.log
//
// initializes log file for further use by plog
//
//
// Uses the following globals:
//   - logdir: directory for log files
//   - logfile: path and filename for lag (without suffix)
//   - logprint: boolean flag to
// requires that $logdir be defined.  Uses $logroot as the name of the logfile.
//
function log_init($logroot)
{
  global $logdir;
  global $logfile;
  global $logprint;

  // check if $logdir is a directory
  if (! is_dir($logdir))
  {
    print "ERROR: log directory $logdir does not exist.\n";
    print "       Please create or adjust logdir in the parameters file.\n";
    exit;
  }

  // look for $logroot and assign generic root if $logroot not assigned
  if ( $logroot == "" ) $logroot = "MsLib-Generic";

  // set time and full logfile name
  $logfile = $logdir . "/" . $logroot . "-" . date("Ymd-His") . ".log";

  // add initial message to log file
  $logtext = "Music Library Log File - " . $logroot . "  " . date("Y M d H:i:s") . "\n";

  file_put_contents($logfile, $logtext , FILE_APPEND | LOCK_EX);

  // if $logprint, print to std out
  if ( $logprint === TRUE )
    print $logtext;

  return;

}


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
//  echo  - echo/print to terminal
//  nnl  - no newline on echo to terminal statement
//  error - log in error log as well as regular log (.err).  Will be picked up
//           in next error log roll-up (w/in 24 hrs)
//  notify - log in notification log (.not).  Will be picked up for notification
//           in next run, usually hourly
//  exitX - exit entire routine with code X. If no code, 0 assumed
//  exit-nologX - exit with code X  without creating a log entry
//  complete - script complete.  Logs in logfile and creates .cmplt file
//  debug - debugging line, only returned if $debug != 0
//  debug-noecho - like debug but will not echo to terminal
//
// Example: plog("echo,error", "This is a log message.")
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

function plog_int_write($suffix, $lmessage)
{
  global $logfile;
  global $logprint;

  $filename = "$logfile" . '/' . $suffix
  // if file does not exist, initialize with header
  if ( ! file_exists ($filename))
  {

  }
  // append to end of log
  file_put_contents($logfile, $lmessage , FILE_APPEND | LOCK_EX);

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
