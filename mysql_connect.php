<?php
/*
 MySQL connection script
 */

// Create connection
$dbc = @mysql_connect('localhost', 'scoresystem_user', 'fPZyNZUDPEuGTLS2') OR exit('FATAL ERROR: Could not connect to MySQL.<br><br>' . mysql_error());

// Select database
@mysql_select_db('scoresystem_v1_04') OR exit('FATAL ERROR: Could not select database.<br><br>' . mysql_error());
?>