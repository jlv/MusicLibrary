<?php

  require "MusicRequire.inc";

  logp_init("ServerFix", "");

  // funtion serverFix($base_folder, $add_folder, $new_base_folder, $file, $options)
  //  $base_folder - initial root folder
  //  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
  //       full path name.  $add_folder can be blank to start (and usually is).  Used by
  //       recursive function to crawl.
  //  $new_base_folder - target base folder for functions that are moving/writing files
  //       from a base to a new_base
  //  $file - name of file passed to function
  //  $options - array of options passed to function
  //
  // serverFix function - fixes both cue file and associated .wav files of the music server
  //                        Fixes cue files first and then changes .wav files
  function serverFix($base_folder, $add_folder, $new_base_folder, $file, $options){
    // only looks at cue files
    // changes the directory to $base_folder because we will always stay in it
    chdir($base_folder . '/' . $add_folder);
    // $list - array of $add_folder split between every /
    $list = preg_split("/\//", $add_folder);
    // $album - last input of $list, which will be the album title
    $album = $list[count($list) - 1];

    // checks if there is a cue file. if there isn't, ERROR
    if(!file_exists($album . '.cue')){
      logp("error", "ERROR: no .cue file found in {$base_folder}/{$add_folder}/{$file}");
    }

    if(preg_match('/\.cue$/i', $file)){

      // checks to see if cue file needs any fixing
      if(!cueGood($base_folder, $add_folder, $file)){
        // if !cueGood, runs cueFix function
        $checkSpecial = specialFix($base_folder, $add_folder, $file);
        // checks for fixCUE using specialFix
        if($checkSpecial === 1){
          logp("notify", "Warning: fixCUE ran specialFix on {$base_folder}/{$add_folder}. Please confirm validity of new files");
        }
      }
    }
  }

  // function cueGood($base_folder, $add_folder, $file)
  //  $base_folder - initial root folder
  //  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
  //       full path name.  $add_folder can be blank to start (and usually is).  Used by
  //       recursive function to crawl.
  //  $file - name of file passed to function
  //
  // cueGood function - verifys cue file
  //  returns true if cue file has no ERRORs, else returns false
  function cueGood($base_folder, $add_folder, $file){
    $cuefile = file($file, FILE_IGNORE_NEW_LINES);

    foreach ($cuefile as $input) {

      if (preg_match ( '/^\a*FILE/', $input ) === 1 )
      {
        //gets part just between quotes
        $title = preg_replace("/FILE \"/", '', $input);
        $title = preg_replace("/\".*$/", '', $title);

        //checks if it starts with a number and space
        $num2 = "/^\d{2,3} /";
        //checks for - after beginning number
        $character1 = "/^\d{2,3} -/";
        //checks if character after whitespace is non-whitespace
        $character = "/^\d{2,3} \s/";
        //checks for all other special characters
        $special = "/[~\?\*\+\[\]\{\}\^\$\|<>:;\/\"]/";
        //checks for ending in .wav
        $wav = "/\.wav/i";
        //checks if name exists in directory
        $fileExists = file_exists($title);

        //checks backslash
        if(preg_match('/\\\/', $title)){
          return false;
        }

        //$num3 = "/\d\d\d /";
        else if(!preg_match($num2, $title)){
          return false;
        }

        //$character2 = "/\d\d\d -/";
        else if(preg_match($character1, $title)){
          return false;
        }

        else if(preg_match($character, $title)){
          return false;
        }

        //"
        else if(preg_match($special, $title)){
          return false;
        }

        else if(!preg_match($wav,$title)){
          return false;
        }

        else if(!$fileExists){
          return false;
        }

        else{
          return true;
        }
      }
    }
  }

  // function specialFix($base_folder, $add_folder, $file)
  //  $base_folder - initial root folder
  //  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
  //       full path name.  $add_folder can be blank to start (and usually is).  Used by
  //       recursive function to crawl.
  //  $line - FILE line from .cue file
  //
  // specialFix function - new fix function to see if it works. Go back to GitHub if there are big problems
  // returns 0 on failure
  function specialFix($base_folder, $add_folder, $file){
    logp("info", "SpecialFix has started on {$base_folder}/{$add_folder}");
    // $wav is a 2D aray of [tracknum][old/new]
    $wav = array();

    // $cuefile is array of outdated cue file
    $cuefile = file($file, FILE_IGNORE_NEW_LINES);

    // gets $artist and $album
    $list = preg_split("/\//", $add_folder);
    $album = $list[count($list) - 1];
    $artist = $list[count($list) - 2];

    // fixes () and . if they are in album/title
    $album = fixParens($album);
    $artist = fixParens($artist);
    $album = fixDot($album);
    $artist = fixDot($artist);
    $album = fixBrackets($album);
    $artist = fixBrackets($artist);

    // checks if in artist/album directory
    $checkDir = "/{$artist}\/{$album}/";
    if(!preg_match($checkDir, $add_folder)){
      logp("error", "ERROR: cue file in incorrect directory");
      return 0;
    }

    // $cuefile is array of outdated cue file
    $cuefile = file($file, FILE_IGNORE_NEW_LINES);

    // crawls through lines in $cuefile
    for($i = 0; $i < count($cuefile); $i++){
      if(preg_match ( '/^\a*FILE/', $cuefile[$i] ) === 1){
        // defines $song
        $song = preg_replace("/^FILE \"/", '', $cuefile[$i]);
        $song = preg_replace("/\" WAVE$/", '', $song);
        $song = preg_replace("/\\\/", '', $song);
        $song = preg_replace("/{$artist}{$album}/", '', $song);

        // checks that .wav file exists for FILE line
        // Also checks for too long file name (i.e. NN~AAA~1.wav)
        $tooLong = "";
        $wavIndex = 0;
        if(preg_match("/^\d\d\d/", $song)){
          $tooLong = substr($song, 0, 3);
        }else {
          $tooLong = substr($song, 0, 2);
        }
        // reassigns $artist to artist name without \
        $tooLongArtist = $list[count($list) - 2];
        // begins making $tooLong
        $wavIndex = intval($tooLong);
        $wav[$wavIndex] = array();
        $tooLong = $tooLong .  "-" . strtoupper(substr($tooLongArtist, 0, 3)) . "~" . "1.wav";
        // as long as a song file exists, will assign old name of track to $wav array
        if(file_exists($song)){
          $wav[$wavIndex]["old"] = $song;
          $cuefile[$i] = fixFILE($base_folder, $add_folder, $cuefile[$i], $wav[$wavIndex]);
        }else if(file_exists($tooLong)){
          $wav[$wavIndex]["old"] = $tooLong;
          $cuefile[$i] = fixFILE($base_folder, $add_folder, $cuefile[$i], $wav[$wavIndex]);
        }else{
          logp("error", "ERROR: {$song} or {$tooLong} file does not exist");
          return 0;
        }

        // double checks that fixFILE worked
        if($cuefile[$i] === 0 && $i < count($cuefile)){
          logp("error", "ERROR: fixFILE function has failed");
          return 0;
        }

        // checks that song num matches track num
        $trackNum = "";
        if(preg_match("/^\d\d\d/", $song)){
          $trackNum = substr($song, 0, 3);
          $trackNum = substr($song, 1, 2);
        }else {
          $trackNum = substr($song, 0, 2);
        }
        $trackCheck = $i + 1;
        if(!preg_match("/{$trackNum}/", $cuefile[$trackCheck])){
          logp("error", "ERROR: {$trackNum} does not match song number {$cuefile[$trackCheck]}");
        }
      }
    }

    // renames old cue file
    rename($file, $file . ".old");
    // writes array $cuefile into an actual cue file
    addLines($cuefile);
    $goodCue = file_put_contents($file, $cuefile);

    fixWAV($base_folder, $add_folder, $wav);
  }

  // function fixFILE($base_folder, $add_folder, $file, &$wav)
  //  $base_folder - initial root folder
  //  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
  //       full path name.  $add_folder can be blank to start (and usually is).  Used by
  //       recursive function to crawl.
  //  $line - FILE line from .cue file
  //  &$wav - array of file names needed to rename .wav files in album
  //
  // fixFILe function - fixes the file line to new, correct standard
  // returns a string which is the correct line, else returns 0 on failure
  function fixFILE($base_folder, $add_folder, $line, &$wav){
    // gets $artist and $album
    $list = preg_split("/\//", $add_folder);
    $album = $list[count($list) - 1];
    $artist = $list[count($list) - 2];

    // fixes () and . if they are in album/title
    $album = fixParens($album);
    $artist = fixParens($artist);
    $album = fixDot($album);
    $artist = fixDot($artist);
    $album = fixBrackets($album);
    $artist = fixBrackets($artist);

    // defines $song
    $song = preg_replace("/^FILE \"/", '', $line);
    $song = preg_replace("/\" WAVE$/", '', $song);
    $song = preg_replace("/\\\/", '', $song);
    $song = preg_replace("/{$artist}{$album}/", '', $song);

    // checks to see that $album and $artist are correctly in $song. ERROR and exit if not
    if((!preg_match("/{$artist} /", $song) || !preg_match("/{$album} /", $song))
        && ((preg_match("/ ~ .* ~ /", $song) || preg_match("/ - .* - /", $song)))){
      logp("error", "ERROR: {$artist}/{$album} in .cue file does not match .wav file");
      return 0;
    }
    // checks for ~ or - delimeter, then fixes song title
    $tilda = "/^\d*.* ~/";
    $dash = "/^\d*.* -/";

    if(preg_match($tilda, $song)){
      $song = preg_replace("/~ {$artist} ~ /", '', $song);
      $song = preg_replace("/{$album} ~ /", '', $song);
      $song = preg_replace("/~ /", '', $song);
    }else if(preg_match($dash, $song)){
      $song = preg_replace("/- {$artist} /", '', $song);
      $song = preg_replace("/{$artist} /", '', $song);
      $song = preg_replace("/- {$album} - /", '', $song);
    }
    // finally replaces NN. or NNN.
    $cutout = "/^\d{2,3}\./";
    $replace = "";
    if(preg_match("/^\d\d\d/", $song)){
      $replace = substr($song, 0, 3);
    }else {
      $replace = substr($song, 0, 2);
    }
    $song = preg_replace($cutout, $replace, $song);
    // cuts all double spaces and extra .
    $song = preg_replace("/\s+/", " ", $song);
    $song = preg_replace("/\.+/", ".", $song);
    // checks if replacement proccess worked. if not, return failure
    if($song == null){
      logp("error", "ERROR: preg_replace failure in .cue");
      return 0;
    }
    $line = "FILE \"{$song}\" WAVE";
    $wav["new"] = $song;
    return $line;
  }

  // function fixWAV($base_folder, $add_folder, &$wav)
  //  $base_folder - initial root folder
  //  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
  //       full path name.  $add_folder can be blank to start (and usually is).  Used by
  //       recursive function to crawl.
  //  &$wav - array of file names needed to rename .wav files in album
  //
  // fixWAV function - fixes the .wav files in the album
  function fixWAV($base_folder, $add_folder, &$wav){
    foreach ($wav as $type) {
      $goodRename = rename($type["old"],
                            $type["new"]);
      if(!$goodRename){
        logp("error", "ERROR: failure on renaming {$type["old"]} file");
      }
    }
  }

  // function addLines(&$array)
  //  &$array - array of strings that will make up a file
  //  NOTE &$array is a reference variable
  // returns nothing
  //
  // addLines function - adds \n (aka line breaks) to an array of strings in order for it to be passed
  //   into a cue file correctly
  function addLines(&$array){
    for($i = 0; $i < count($array); $i++){
      $array[$i] .= "\r\n";
    }
  }

  // function fixParens($str)
  //  $str - given to string that is to be fixed
  //
  // fixDash function - changes () to \(\) for regex functions. NOTE only use when you want str in regex
  // returns fixed string
  function fixParens($str){
    $str = preg_replace("/\(/", "\\(", $str);
    $str = preg_replace("/\)/", "\\)", $str);
    return $str;
  }

  // function fixParens($str)
  //  $str - given to string that is to be fixed
  //
  // fixDash function - changes [] to \[\] for regex functions. NOTE only use when you want str in regex
  // returns fixed string
  function fixBrackets($str){
    $str = preg_replace("/\[/", "\\[", $str);
    $str = preg_replace("/\]/", "\\]", $str);
    return $str;
  }

  // function fixDot($str)
  //  $str - given to string that is to be fixed
  //
  // fixDot function - changes all . to \. for regex function
  // retruns fixed $str
  function fixDot($str){
    $str = preg_replace("/\./", "\\.", $str);
    return $str;
  }

  // TONTO directory starts
  // $test = "C:/Quentin/MusicReference/Music";

  // HECTOR directory starts
  // $test = "D:/Quentin/MusicProgramming/RenameTest";
  // $test = "D:/Quentin/MusicProgramming/MultiDiskTest";
  $test = "D:/Quentin/MusicProgramming/ServerFixTest";

  crawl($test, '', '', "serverFix", array());

 ?>