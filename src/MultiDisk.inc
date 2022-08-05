<?php

  print "Accessed MultiDisk.inc\n\n";

  // $finalDir - directory of combined multi-disk
  //    (which is also name of cue, name of album)
  $finalDir = "Christmas Oratorio";

  // $multiDisks - array of all disks and their full name.
  //    Must be put in order (i.e. disk 1, then disk 2, etc.)
  //    Should be directories under calling directory.
  $multiDisks = array(
    "Christmas Oratorio",
    "Christmas Oratorio (Disk2)"
  );

  // albumRename renames the " - album -" and "~ album ~" formats. When set to
  //  TRUE, turns this feature off.
  $albumRenameOff = FALSE;

 ?>
