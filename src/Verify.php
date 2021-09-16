<?php

// functional definition

require "MusicRequire.inc";

logp_init("Verify", "");

function verify($base_folder, $add_folder, $new_base_folder, $file, $options)  {
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

crawl($startdir, '', '', "verify", array());

 ?>
