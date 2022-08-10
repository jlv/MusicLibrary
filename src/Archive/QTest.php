//test
  $line = "FILE \"Thelonious Monk\At Carnegie Hall\02 Evidence.wav\" WAVE";
  $replacePart = "/FILE \".*\\\/";
  // " (ignore this)
  $fillerPart = "/FILE \"/";
  // " (ignore this)
  $stuff = preg_replace($replacePart, $fillerPart, $line);
  print $stuff . "\n";
