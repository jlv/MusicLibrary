<?php

require "MusicRequire.inc";

logp_init("mp3Crawl", "");

// function mp3Crawl($base_folder, $add_folder, $new_base_folder, $file, $options)
//  $base_folder - initial root folder
//  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
//       full path name.  $add_folder can be blank to start (and usually is).  Used by
//       recursive function to crawl.
//  $new_base_folder - target base folder for functions that are moving/writing files
//       from a base to a new_base; in this case, will be endpoint of mp3 files
//  $file - name of file passed to function
//  $options - array of options passed to function
//
// mp3Crawl function - converts each .wav file into a .mp3 file by feeding it to an mp3 converter
function mp3Crawl($base_folder, $add_folder, $new_base_folder, $file, $options){
  // first much check if the album and songs exist in $new_base_folder
  $list = preg_split("/\//", $add_folder);
  $album = $list[count($list) - 1];
  $artist = $list[count($list) - 2];
  if(file_exists("{$new_base_folder}/{$artist}/{$album}/{$file}")){
    logp("notify", "{$artist}/{$album}/{$file} found to already exist in mp3 folder");
    return;
  }
}

// function verify($base_folder, $add_folder, $new_base_folder, $file, $options)
//  $base_folder - initial root folder
//  $add_folder - the folder path to add to $base_folder (or $new_base_folder) to achieve
//       full path name.  $add_folder can be blank to start (and usually is).  Used by
//       recursive function to crawl.
//  $new_base_folder - target base folder for functions that are moving/writing files
//       from a base to a new_base
//  $file - name of file passed to function
//  $options - array of options passed to function
//
// verify function - makes sure that .cue file is in correct format without bad symbols
function verify($base_folder, $add_folder, $new_base_folder, $file, $options){
  // $list - array of $add_folder split between every /
  $list = preg_split("/\//", $add_folder);
  // $album - last input of $list, which will be the album title
  $album = $list[count($list) - 1];

  if(!file_exists($base_folder . '/' . $add_folder . '/' . $album . '.cue')){
    logp("error", "ERROR: no .cue file found in {$base_folder}/{$add_folder}")
  }

  else if(preg_match('/\.cue/i', $file)){

    $cuefile = file($base_folder . '/' . $add_folder . '/' . $file, FILE_IGNORE_NEW_LINES);

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
        $fileExists = file_exists($base_folder . '/' . $add_folder . '/' . $title);

        //checks backslash
        if(preg_match('/\\\/', $title)){
          logp("error", "ERROR: {$title} has \ in {$base_folder}/{$add_folder}");
        }

        //$num3 = "/\d\d\d /";
        else if(!preg_match($num2, $title)){
          logp("error", "ERROR: {$title} does not start with a number followed by a space in {$base_folder}/{$add_folder}/{$file}");
        }

        //$character2 = "/\d\d\d -/";
        else if(preg_match($character1, $title)){
          logp("error", "ERROR: {$title} has - after number in {$base_folder}/{$add_folder}");
        }

        else if(preg_match($character, $title)){
          logp("error", "ERROR: {$title} has two white spaces in a row in {$base_folder}/{$add_folder}");
        }

        //"
        else if(preg_match($special, $title)){
          logp("error", "ERROR: {$title} has invalid special character in {$base_folder}/{$add_folder}");
        }

        else if(!preg_match($wav,$title)){
          logp("error", "ERROR: {$title} does not end in .wav in {$base_folder}/{$add_folder}");
        }

        else if(!$fileExists){
          logp("error", "ERROR: {$title} file does not exist in {$base_folder}/{$add_folder}");
        }

        else{
          return 0;
        }
      }
    }
  }

}

 ?>
