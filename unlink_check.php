<?php
@unlink('check_dirs.php');
echo "Deleted check_dirs.php\n";
@unlink(__FILE__);
echo "Self-deleted unlink_check.php\n";
