<?php
/*
 Serves record data to Live Results >> Current Scores AJAX display.
 */

// Connect to MySQL
@require_once('mysql_connect.php');

// Include helper functions
@require_once('helper_functions.php');

// Process request
if(isset($_POST['d_id']))
{
	// Fetch data array
	$totalscores = get_currenttotalscores($_POST['d_id']);
	
	// Output score data
	foreach($totalscores as $key => $data)
	{
		if($key > 1) echo "\n";
		if($data['tied']) echo "=";
		echo htmlspecialchars($data['place']) . "<sup>" . getNumberSuffix($data['place']) . "</sup>\n";
		echo htmlspecialchars($data['teamname']) . "\n";
		echo truncate_number($data['prior_score']) . "\n";
		echo truncate_number($data['total_score']) . "\n";
		echo truncate_number($data['final_score']);
	}
}

// Close MySQL connection
mysql_close();
?>