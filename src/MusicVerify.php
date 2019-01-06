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
		return 1;

	//checks if it starts with a number and space
	$num2 = "/^\d{2,3} /";
	//$num3 = "/\d\d\d /";
	if(!preg_match($num2, $title))
		return 1;

	//checks for - after beginning number
	$character1 = "/^\d{2,3} -/";
	//$character2 = "/\d\d\d -/";
	if(preg_match($character1, $title))
		return 1;

	//checks if character after whitespace is non-whitespace
	$character = "/^\d{2,3} \s/";
	if(preg_match($character, $title))
		return 1;

	//checks for all other special characters
	$special = "/[~\?\*\+\[\]\(\)\{\}\^\$\|<>:;\/\"]/";
	//"
	if(preg_match($special, $title))
		return 1;

	//checks for ending in .wav
	$wav = "/\.wav/i";
	if(!preg_match($wav,$title))
		return 1;

	//checks if name exists in directory
	return 0;
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