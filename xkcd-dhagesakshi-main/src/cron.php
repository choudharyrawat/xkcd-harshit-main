#!/usr/bin/php
<?php
// Set a higher time limit for script execution, just in case.
set_time_limit(300); 

// Ensure the script is running from the correct directory context
// This is important for cron jobs to find included files.
chdir(__DIR__);

require_once 'functions.php';

// Execute the main function to send comics.
sendXKCDUpdatesToSubscribers();

?>