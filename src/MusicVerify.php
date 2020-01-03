<?php
/*Has Verify function
*/

require 'MusicRequire.inc';

function verify($input)  {
	//gets part just between quotes
	$title = preg_replace("/FILE \"/", '', $input);
	$title = preg_replace("/\".*$/", '', $title);

	//checks backslash
	if(preg_match('/\\\/', $title))
		plog("ERROR: has \ in title");

	//checks if it starts with a number and space
	$num2 = "/^\d{2,3} /";
	//$num3 = "/\d\d\d /";
	else if(!preg_match($num2, $title))
		plog("ERROR: does not start with a number followed by a space");

	//checks for - after beginning number
	$character1 = "/^\d{2,3} -/";
	//$character2 = "/\d\d\d -/";
	else if(preg_match($character1, $title))
		plog("ERROR: has -  after number");

	//checks if character after whitespace is non-whitespace
	$character = "/^\d{2,3} \s/";
	else if(preg_match($character, $title))
		plog("ERROR: two white spaces in a row");

	//checks for all other special characters
	$special = "/[~\?\*\+\[\]\(\)\{\}\^\$\|<>:;\/\"]/";
	//"
	else if(preg_match($special, $title))
		plog("ERROR: invalid special character");

	//checks for ending in .wav
	$wav = "/\.wav/i";
	else if(!preg_match($wav,$title))
		plog("ERROR: does not end in .wav");

	//checks if name exists in directory
	$fileExists = file_exists($title);
	else if(!$fileExists){
	    plog("ERROR: file does not exist");
	}

	else{
		return 0;
	}
}


//test
    $cuefile = file ( $cuedir . '/' . 'FailureCase.cue' , FILE_IGNORE_NEW_LINES );

	// read each line and look for matches to replace strings
    foreach ($cuefile as $line)
    {
      if (preg_match ( '/^\a*FILE/', $line ) === 1 )
      {
      	$check = verify($line);
      	print $line . "\n";
      	print $check . "\n";
      }
    }



?>