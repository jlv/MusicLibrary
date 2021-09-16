<?php
//
// Music Library
//
//

require "MusicRequire.inc";

// initialize log file
log_init("MusicExample");

// show that we're debugging as an example
if (debug()) print "Debug is turned on.\n\n";

plog("A test log message");


// function verify($base_folder, $folder, $file_name, $new_base_folder, $options)
//   $folder - file system folder (contains album)
//   $file_name - name of file
//   returns nothing
//
function verify($base_folder, $folder, $new_base_folder, $file_name)
{
 print "File Verify: ". $file_name . " Folder: " . $base_folder . '/' . $folder . "\n";
  // get suffix on filename
  $cue = getSuffix($file_name);
  // if suffix is "cue", then print file
  if ($cue == "cue")
    print "File: " . $cue . " " . $file_name . " " . $base_folder .'/'. $folder . "\n";

}



// function fixfile($folder, $file_name)
//   $folder - file system folder (contains album)
//   $file_name - name of file
//   returns nothing
//
function changefile($base_folder, $folder, $new_base_folder, $file_name, $options)
{
//  print "ChangeFile: ". $file_name . $folder . "\n";
  // get suffix on filename
  $cue = getSuffix($file_name);
  // if suffix is "cue", then print file
  if ($cue == "cue")
  {
    print "In CUE:" . $cue . " " . $file_name . " " . $folder . "\n";

    // set up cueoutfile to write, then read cuefile
    $cueoutfile = array();
    $cnt = 0;
    $cuefile = file ( $base_folder . '/' . $folder . '/' . $file_name, FILE_IGNORE_NEW_LINES );

	// read each line and look for matches to replace strings
    foreach ($cuefile as $line)
    {
      if (preg_match ( '/^\a*FILE/', $line ) === 1 )
      {
     // print "IN MATCH"
     	// replace
        $musfile = preg_replace ( '/^.*\\\/', '', $line );
        $musfile = preg_replace ( '/".*$/', '', $musfile );

     	print "\nMUSFILE" . $musfile;

        // find root name

        // find blank space
        $ptr=1;
        $delim='';
        $track='';

        if (substr($musfile, $ptr, 1) == ' ')
        {
          $delim=substr($musfile, $ptr + 1, 1);
          $track=substr($musfile, 0, $ptr);
        }
        elseif (substr($musfile, $ptr, 1) == ' ')
        {
          $delim=substr($musfile, $ptr + 1, 1);
          $track=substr($musfile, 0, $ptr);
        }
        elseif (substr($musfile, ++$ptr, 1) == ' ')
        {
          $delim=substr($musfile, $ptr + 1, 1);
          $track=substr($musfile, 0, $ptr);
        }

        if ($delim != ' ')
          $rootfile = $track . ' ' . preg_replace('/^.*' . $delim . '.*' . $delim . ' /', '', $musfile);
//         $rootfile = $track . ' ' . preg_replace('/^.*-.*- /', '', $musfile);
//        print "\nDEL: ". $delim .":". $rootfile . ":" . $track;

        // move music file
        rename($base_folder . '/' . $folder . '/' . $musfile, $base_folder . '/' . $folder . '/' , $rootfile);

        $cueoutfile[$cnt++] = 'FILE "' . $rootfile . '" WAVE' . "\r\n";
      }
      else
        $cueoutfile[$cnt++] = $line . "\r\n";
    }

  // rename old cuefile and write new cuefile
  rename($base_folder . '/' . $folder . '/' . $file_name, $base_folder . '/' . $folder . '/' . $file_name . '.OLD');

  file_put_contents($base_folder . '/' . $folder . '/' . $file_name, $folder . "/" . $cueoutfile);

  }

}


// function convert($base_folder, $folder, $file_name, $new_base_folder, $options)
//   $folder - file system folder (contains album)
//   $file_name - name of file
//   returns nothing
//
function convert($folder, $file_name)
{
//  print "Here ". $file_name . $folder . "\n";
  // get suffix on filename
  $cue = getSuffix($file_name);
  // if suffix is "cue", then print file
  if ($cue == "cue")
  {
    print "In CUE:" . $cue . " " . $file_name . " " . $folder . "\n";

    // convert music, then rewrite

    // call system function to complete
    print "\nSYS: system(". "${converter} /i ${folder}/${file_name} ";
    print "\nSYS: system(". "${converter} /i ${basefolder}/${folder}/${file_name} ${params} /o ${new_base_folder}/${folder}/{$file_name};";

$dir = "D:/Music Programming/Music Ref";
$outdir = "D:/Music Programming/Music Out";
$params = "/dest mp3 /cbr 192 /pfilename %track2%";
$converter = "C:\Apps\xrecode3\xrecode3cx64.exe";


    // read cuefile
    $cueoutfile = array();
    $cnt = 0;
    $filecnt = 0;
    $cuefile = file ( $base_folder . '/' . $folder . "/" . $file_name, FILE_IGNORE_NEW_LINES );



    foreach ($cuefile as $line)
    {
      if (preg_match ( '/^\a*FILE/', $line ) === 1 )
      {
     // print "IN MATCH"
        $musfile = preg_replace ( '/^.*\\\/', '', $line );
        $musfile = preg_replace ( '/".*$/', '', $musfile );
        print "\nMUSFILE" . $musfile;

        // find root name

        // find blank space
        $ptr=1;
        $delim='';
        $track='';

        if (substr($musfile, $ptr, 1) == ' ')
        {
          $delim=substr($musfile, $ptr + 1, 1);
          $track=substr($musfile, 0, $ptr);
        }
        elseif (substr($musfile, ++$ptr, 1) == ' ')
        {
          $delim=substr($musfile, $ptr + 1, 1);
          $track=substr($musfile, 0, $ptr);
        }
        elseif (substr($musfile, ++$ptr, 1) == ' ')
        {
          $delim=substr($musfile, $ptr + 1, 1);
          $track=substr($musfile, 0, $ptr);
        }

        if ($delim != ' ')
          $rootfile = $track . ' ' . preg_replace('/^.*' . $delim . '.*' . $delim . ' /', '', $musfile);
//         $rootfile = $track . ' ' . preg_replace('/^.*-.*- /', '', $musfile);
//        print "\nDEL: ". $delim .":". $rootfile . ":" . $track;

        // move file
        rename($musfile, $rootfile);

        $cueoutfile[$cnt++] = 'FILE "' . $rootfile . '" WAVE' . "\r\n";
      }
      else
        $cueoutfile[$cnt++] = $line . "\r\n";
    }

  // rename old cuefile and write new cuefile
  rename($file_name, $file_name . '.OLD');

  file_put_contents($file_name, $cueoutfile);

  }

}




//
//
// * Main section of program
//
//

$new_base_folder=NULL;
$options=NULL;

//
// Delete files in destination directory
//$dir = "C:/Quentin/MusicReference";
$dir = "D:/Music Programming/Music Ref";
$outdir = "D:/Music Programming/Music Out";
$params = "/dest mp3 /cbr 192 /pfilename %track2%";
$converter = "C:\Apps\xrecode3\xrecode3cx64.exe";


crawl($srcdir, '', $new_base_folder, "verify", $options); // Runs the crawl
//crawl($srcdir, '', $new_base_folder, "changefile", $options); // Runs the crawl


//crawl($dir, '', $new_base_folder, "convert", $options); // Runs the crawl


//
// process complete
//
print "Process Complete.\n";

?>