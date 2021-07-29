<?php

require "MusicRequire.inc";

log_init("Verify");

function verify($base_folder, $add_folder, $new_base_folder, $file, $options)  {
  // $list - array of $add_folder split between every /
  $list = preg_split("/\//", $add_folder);
  // $album - last input of $list, which will be the album title
  $album = $list[count($list) - 1];
  
  if(!file_exists($base_folder . '/' . $add_folder . '/' . $album . '.cue')){
    plog("ERROR: no .cue file found");
    plog("\t{$base_folder}/{$add_folder}/{$file}");
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
        $special = "/[~\?\*\+\[\]\(\)\{\}\^\$\|<>:;\/\"]/";
        //checks for ending in .wav
        $wav = "/\.wav/i";
        //checks if name exists in directory
        $fileExists = file_exists($base_folder . '/' . $add_folder . '/' . $title);

        //checks backslash
        if(preg_match('/\\\/', $title)){
          plog("ERROR: has \ in title");
          plog("\t{$base_folder}/{$add_folder}/{$file}");
          plog("\t{$title}");
        }

        //$num3 = "/\d\d\d /";
        else if(!preg_match($num2, $title)){
          plog("ERROR: does not start with a number followed by a space");
          plog("\t{$base_folder}/{$add_folder}/{$file}");
          plog("\t{$title}");
        }

        //$character2 = "/\d\d\d -/";
        else if(preg_match($character1, $title)){
          plog("ERROR: has - after number");
          plog("\t{$base_folder}/{$add_folder}/{$file}");
          plog("\t{$title}");
        }

        else if(preg_match($character, $title)){
          plog("ERROR: two white spaces in a row");
          plog("\t{$base_folder}/{$add_folder}/{$file}");
          plog("\t{$title}");
        }

        //"
        else if(preg_match($special, $title)){
          plog("ERROR: invalid special character");
          plog("\t{$base_folder}/{$add_folder}/{$file}");
          plog("\t{$title}");
        }

        else if(!preg_match($wav,$title)){
          plog("ERROR: does not end in .wav");
          plog("\t{$base_folder}/{$add_folder}/{$file}");
          plog("\t{$title}");
        }

        else if(!$fileExists){
          plog("ERROR: file does not exist");
          plog("\t{$base_folder}/{$add_folder}/{$file}");
          plog("\t{$title}");
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
