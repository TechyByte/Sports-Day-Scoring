<?php
/*
 Main script for Scoring System.
 Handles page layout, content, and sessions.
 */

/*
 Make sure magic quotes are turned off for the duration of the script
 ##NOTE## Magic quotes are due to be removed from PHP anyway, but I'd
 like to make sure they're off
 */
ini_set('magic_quotes_gpc', '0');

/*
 Start session
 */
session_name('ScoreSysSession');
session_start();

/*
 Include MySQL connection script
 */
@require_once('mysql_connect.php');

/*
 Include help functions
 */
@require_once('helper_functions.php');

/*
 If logged in, reload user details into session
 */
if(isset($_SESSION['user_id']))
{
	// Construct SQL query
	$query = "SELECT users.name AS user_name, usergroups.name AS group_name, perm_admin, perm_daymanage, perm_scoreedit, perm_scoreview, perm_stats FROM users, usergroups";
	$query .= " WHERE users.u_id='" . mysql_real_escape_string($_SESSION['user_id']);
	$query .= "' AND users.ug_id=usergroups.ug_id LIMIT 1";
	
	// Execute SQL query
	$result = @mysql_query($query);
	if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
	
	// Read result
	if(mysql_num_rows($result) == 0)
	{
		// User stopped existing? o_O Log them out, I guess... this will only occur if the admin deletes a logged-on user
		logUserAction("Stopped existing (perhaps was deleted from database?), so was logged out.");
		$_SESSION = array();									// Empty session superglobal array
		session_destroy();										// Destroy session server data
		setcookie(session_name(), '', time()-300, '/', '', 0);	// Destroy session client cookie
		mysql_free_result($result);
	} else {
		// Update session data
		$row = mysql_fetch_array($result);
		$_SESSION['user_name'] = $row['user_name'];
		$_SESSION['group_name'] = $row['group_name'];
		$_SESSION['perm_admin'] = $row['perm_admin'];
		$_SESSION['perm_daymanage'] = $row['perm_daymanage'];
		$_SESSION['perm_scoreedit'] = $row['perm_scoreedit'];
		$_SESSION['perm_scoreview'] = $row['perm_scoreview'];
		$_SESSION['perm_stats'] = $row['perm_stats'];
		mysql_free_result($result);
	}
}

/*
 Load active Day, if there is one
 */
// Construct SQL query
$query = "SELECT d_id, name FROM days WHERE is_active_day='1' LIMIT 1";

// Execute SQL query
$result = @mysql_query($query);
if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());

// Read result
if(mysql_num_rows($result) == 1)
{	
	// Load active day data
	$row = mysql_fetch_array($result);
	$curactivedayid = $row['d_id'];
	$curpageheadline = htmlspecialchars($row['name']);
} else {
	$curpageheadline = "Scoring System - Please select an Active Day";
} # Note: if there is no active day then $curactivedayid does not get set, however a default page headline is still generated
mysql_free_result($result);

/*
 Redirect if attempting to access non-existant page or
 a page permissions are lacking for; otherwise, load
 page name, title and help page
 */
// Obtain page name requested (blank if nothing requested)
$curpage = "";						// Current page (system name)
$curpagetitle = "";					// Current page title
$curpagehelp = "";					// Current page help page anchor
if(isset($_GET['page'])) $curpage = $_GET['page'];

// Perform any necessary redirects, or load page name and title
switch($curpage)
{
	case 'login':
		if(isset($_SESSION['user_id']))
		{
			// Redirect if already logged in
			redirect('index.php?page=welcome');
		}
		// Page Title
		$curpagetitle = "Log In";
		$curpagehelp = "#use-login";
		break;
	case 'logout':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		}
		// Page Title
		$curpagetitle = "Log Out";
		$curpagehelp = "#use-login";
		break;
	case 'accountsettings':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		}
		// Page Title
		$curpagetitle = "Account Settings - '" . htmlspecialchars($_SESSION['user_name']) . "'";
		$curpagehelp = "#use-accountsettings";
		break;
	case 'welcome':		
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		}
		// Page Title
		$curpagetitle = "Welcome!";
		$curpagehelp = "#use-welcome";
		break;
	case 'adminpanel':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_admin'] == 0) {
			// Redirect if lacking admin permissions
			redirect('index.php?page=welcome');
		}
		// Page Title
		$curpagetitle = "Administration Panel";
		$curpagehelp = "#use-admin";
		break;
	case 'admin_usergroupsettings':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_admin'] == 0 || !isset($_GET['ug_id'])) {
			// Redirect if lacking admin permissions or group not specified
			redirect('index.php?page=welcome');
		}
		// Load name of requested group from database
		$query = "SELECT name FROM usergroups WHERE ug_id='" . mysql_real_escape_string($_GET['ug_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(mysql_num_rows($result) == 0)
		{
			// Redirect if group does not exist
			redirect('index.php?page=adminpanel');
		}
		$row = mysql_fetch_array($result);
		mysql_free_result($result);
		// Page Title
		$curpagetitle = "User Group Settings - '" . htmlspecialchars($row['name']) . "'";
		$curpagehelp = "#use-groupsettings";
		break;
	case 'admin_deleteusergroup':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_admin'] == 0 || !isset($_GET['ug_id'])) {
			// Redirect if lacking admin permissions or group not specified
			redirect('index.php?page=welcome');
		}
		// Load name of requested group from database
		$query = "SELECT name FROM usergroups WHERE ug_id='" . mysql_real_escape_string($_GET['ug_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(mysql_num_rows($result) == 0)
		{
			// Redirect if group does not exist
			redirect('index.php?page=adminpanel');
		}
		$row = mysql_fetch_array($result);
		mysql_free_result($result);
		// Page Title
		$curpagetitle = "Delete User Group - '" . htmlspecialchars($row['name']) . "'";
		$curpagehelp = "#use-deletegroup";
		break;
	case 'admin_usersettings':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_admin'] == 0 || !isset($_GET['u_id'])) {
			// Redirect if lacking admin permissions or user not specified
			redirect('index.php?page=welcome');
		}
		// Load name of requested user from database
		$query = "SELECT name FROM users WHERE u_id='" . mysql_real_escape_string($_GET['u_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(mysql_num_rows($result) == 0)
		{
			// Redirect if user does not exist
			redirect('index.php?page=adminpanel');
		}
		$row = mysql_fetch_array($result);
		mysql_free_result($result);
		// Page Title
		$curpagetitle = "User Settings - '" . htmlspecialchars($row['name']) . "'";
		$curpagehelp = "#use-usersettings";
		break;
	case 'admin_deleteuser':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_admin'] == 0 || !isset($_GET['u_id'])) {
			// Redirect if lacking admin permissions or user not specified
			redirect('index.php?page=welcome');
		}
		// Load name of requested user from database
		$query = "SELECT name FROM users WHERE u_id='" . mysql_real_escape_string($_GET['u_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(mysql_num_rows($result) == 0)
		{
			// Redirect if user does not exist
			redirect('index.php?page=adminpanel');
		}
		$row = mysql_fetch_array($result);
		mysql_free_result($result);
		// Page Title
		$curpagetitle = "Delete User - '" . htmlspecialchars($row['name']) . "'";
		$curpagehelp = "#use-deleteuser";
		break;
	case 'admin_logs':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_admin'] == 0) {
			// Redirect if lacking admin permissions
			redirect('index.php?page=welcome');
		}
		// Page Title
		$curpagetitle = "Logs";
		$curpagehelp = "#use-logs";
		break;
	case 'comppanel':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_daymanage'] == 0) {
			// Redirect if lacking day management permissions
			redirect('index.php?page=welcome');
		}
		// Page Title
		$curpagetitle = "Competition Management Panel";
		$curpagehelp = "#use-compmanage";
		break;
	case 'comp_templatesettings':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_daymanage'] == 0 || !isset($_GET['ct_id'])) {
			// Redirect if lacking day management permissions or template is not specified
			redirect('index.php?page=welcome');
		}
		// Load name of requested template from database
		$query = "SELECT name FROM compotemplates WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(mysql_num_rows($result) == 0)
		{
			// Redirect if template does not exist
			redirect('index.php?page=comppanel');
		}
		$row = mysql_fetch_array($result);
		mysql_free_result($result);
		// Page Title
		$curpagetitle = "Template Settings - '" . htmlspecialchars($row['name']) . "'";
		$curpagehelp = "#use-templatesettings";
		break;
	case 'comp_deletetemplate':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_daymanage'] == 0 || !isset($_GET['ct_id'])) {
			// Redirect if lacking day management permissions or template is not specified
			redirect('index.php?page=welcome');
		}
		// Load name of requested template from database
		$query = "SELECT name FROM compotemplates WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(mysql_num_rows($result) == 0)
		{
			// Redirect if template does not exist
			redirect('index.php?page=comppanel');
		}
		$row = mysql_fetch_array($result);
		mysql_free_result($result);
		// Page Title
		$curpagetitle = "Delete Template - '" . htmlspecialchars($row['name']) . "'";
		$curpagehelp = "#use-deletetemplate";
		break;
	case 'comp_daysettings':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_daymanage'] == 0 || !isset($_GET['d_id'])) {
			// Redirect if lacking day management permissions or day is not specified
			redirect('index.php?page=welcome');
		}
		// Load name of requested day from database
		$query = "SELECT name FROM days WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(mysql_num_rows($result) == 0)
		{
			// Redirect if day does not exist
			redirect('index.php?page=comppanel');
		}
		$row = mysql_fetch_array($result);
		mysql_free_result($result);
		// Page Title
		$curpagetitle = "Day Settings - '" . htmlspecialchars($row['name']) . "'";
		$curpagehelp = "#use-daysettings";
		break;
	case 'comp_adjustday':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_daymanage'] == 0 || !isset($_GET['d_id'])) {
			// Redirect if lacking day management permissions or day is not specified
			redirect('index.php?page=welcome');
		}
		// Load name of requested day from database
		$query = "SELECT name FROM days WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(mysql_num_rows($result) == 0)
		{
			// Redirect if day does not exist
			redirect('index.php?page=comppanel');
		}
		$row = mysql_fetch_array($result);
		mysql_free_result($result);
		// Page Title
		$curpagetitle = "Adjust Day Components - '" . htmlspecialchars($row['name']) . "'";
		$curpagehelp = "#use-adjustday";
		break;
	case 'comp_deleteday':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_daymanage'] == 0 || !isset($_GET['d_id'])) {
			// Redirect if lacking day management permissions or day is not specified
			redirect('index.php?page=welcome');
		}
		// Load name of requested day from database
		$query = "SELECT name FROM days WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(mysql_num_rows($result) == 0)
		{
			// Redirect if day does not exist
			redirect('index.php?page=comppanel');
		}
		$row = mysql_fetch_array($result);
		mysql_free_result($result);
		// Page Title
		$curpagetitle = "Delete Day - '" . htmlspecialchars($row['name']) . "'";
		$curpagehelp = "#use-deleteday";
		break;
	case 'comp_managerecords':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_daymanage'] == 0 || !isset($_GET['d_id'])) {
			// Redirect if lacking day management permissions or day is not specified
			redirect('index.php?page=welcome');
		}
		// Load name of requested day from database
		$query = "SELECT name FROM days WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(mysql_num_rows($result) == 0)
		{
			// Redirect if day does not exist
			redirect('index.php?page=comppanel');
		}
		$row = mysql_fetch_array($result);
		mysql_free_result($result);
		// Page Title
		$curpagetitle = "Manage Records - '" . htmlspecialchars($row['name']) . "'";
		$curpagehelp = "#use-managerecords";
		break;
	case 'scoreentry':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_scoreedit'] == 0 || !isset($curactivedayid)) {
			// Redirect if lacking score editting permissions or no day is active
			redirect('index.php?page=welcome');
		}
		// Page Title
		$curpagetitle = "Score Entry";
		$curpagehelp = "#use-scoreentry";
		break;
	case 'scoreentry_editscore':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_scoreedit'] == 0 || !isset($_GET['e_id']) || !isset($curactivedayid)) {
			// Redirect if lacking score editting permissions, event is not specified or no day is active
			redirect('index.php?page=welcome');
		}
		// Load name of requested event from database, so long as it is in the active day
		$query = "SELECT name FROM day_events WHERE e_id='" . mysql_real_escape_string($_GET['e_id']) . "'";
		$query .= " AND d_id='" . mysql_real_escape_string($curactivedayid) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(mysql_num_rows($result) == 0)
		{
			// Redirect if event does not exist
			redirect('index.php?page=scoreentry');
		}
		$row = mysql_fetch_array($result);
		mysql_free_result($result);
		// Page Title
		$curpagetitle = "Editing Event Scores - '" . htmlspecialchars($row['name']) . "'";
		$curpagehelp = "#use-editscore";
		break;
	case 'scoreviewer':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_scoreview'] == 0 || !isset($curactivedayid)) {
			// Redirect if lacking score viewing permissions or no day is active
			redirect('index.php?page=welcome');
		}
		// Page Title
		$curpagetitle = "Live Results";
		$curpagehelp = "#use-liveresults";
		break;
	case 'statspanel':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_stats'] == 0) {
			// Redirect if lacking statistics permissions
			redirect('index.php?page=welcome');
		}
		// Page Title
		$curpagetitle = "Statistical Analysis";
		$curpagehelp = "#use-stats";
		break;
	case 'stats_days':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_stats'] == 0) {
			// Redirect if lacking statistics permissions
			redirect('index.php?page=welcome');
		}
		// Page Title
		$curpagetitle = "Analyse Days";
		$curpagehelp = "#use-stats";
		break;
	case 'stats_events':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_stats'] == 0) {
			// Redirect if lacking statistics permissions
			redirect('index.php?page=welcome');
		}
		// Page Title
		$curpagetitle = "Analyse Events";
		$curpagehelp = "#use-stats";
		break;
	case 'stats_teams':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_stats'] == 0) {
			// Redirect if lacking statistics permissions
			redirect('index.php?page=welcome');
		}
		// Page Title
		$curpagetitle = "Analyse Teams";
		$curpagehelp = "#use-stats";
		break;
	case 'stats_competitors':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_stats'] == 0) {
			// Redirect if lacking statistics permissions
			redirect('index.php?page=welcome');
		}
		// Page Title
		$curpagetitle = "Analyse Competitors";
		$curpagehelp = "#use-stats";
		break;
	case 'stats_records':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_stats'] == 0) {
			// Redirect if lacking statistics permissions
			redirect('index.php?page=welcome');
		}
		// Page Title
		$curpagetitle = "Analyse Records";
		$curpagehelp = "#use-stats";
		break;
	case 'stats_scoresheet':
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		} elseif($_SESSION['perm_stats'] == 0) {
			// Redirect if lacking statistics permissions
			redirect('index.php?page=welcome');
		}
		// Page Title
		$curpagetitle = "Produce Scoresheet";
		$curpagehelp = "#use-stats";
		break;
	case 'help':		
		if(!isset($_SESSION['user_id']))
		{
			// Redirect if not logged in
			redirect('index.php?page=login');
		}
		// Page Title
		$curpagetitle = "Help";
		$curpagehelp = "#use-help";
		break;
	default:
		// Redirect as this page does not exist
		redirect('index.php?page=welcome');
		exit();
		break;
}

/*
 Process any forms or preload actions
 */
$message = "";						// For keeping track of messages to the user
switch($curpage)
{
	case 'login':
		// Log in form parsing
		if(isset($_POST['submitted']) && isset($_POST['username']) && isset($_POST['password']))
		{
			// Validate inputs
			$message = "";
			# Was a username enterred?
			if(empty($_POST['username'])) $message = "<span class=\"errormessage\">You must enter a username!</span><br>\n";
			# Was the username within the length limits?
			if(strlen($_POST['username']) > 50) $message = "<span class=\"errormessage\">Username cannot be longer than 50 characters!</span><br>\n";
			
			// Continue if no errors...
			if($message == "")
			{
				// Get user data from database
				$query = "SELECT u_id, users.name AS user_name, usergroups.name AS group_name, perm_admin, perm_daymanage, perm_scoreedit, perm_scoreview, perm_stats";
				$query .= " FROM users, usergroups";
				$query .= " WHERE users.name='" . mysql_real_escape_string($_POST['username']);
				$query .= "' AND password=SHA1('" . mysql_real_escape_string($_POST['password']);
				$query .= "') AND users.ug_id=usergroups.ug_id LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Process query result
				if(mysql_num_rows($result) == 0)
				{
					// Log in details not correct
					// Log failure
					logUserAction("Failed login attempt for user '" . htmlspecialchars($_POST['username']) . "'.");
					// Generate error message
					$message = "<span class=\"errormessage\">Username and password combination not recognised!</span><br>\n";
					mysql_free_result($result);
				} else {
					// Log in details correct
					// Set up sessions data
					$row = mysql_fetch_array($result);
					$_SESSION['user_id'] = $row['u_id'];
					$_SESSION['user_name'] = $row['user_name'];
					$_SESSION['group_name'] = $row['group_name'];
					$_SESSION['perm_admin'] = $row['perm_admin'];
					$_SESSION['perm_daymanage'] = $row['perm_daymanage'];
					$_SESSION['perm_scoreedit'] = $row['perm_scoreedit'];
					$_SESSION['perm_scoreview'] = $row['perm_scoreview'];
					$_SESSION['perm_stats'] = $row['perm_stats'];
					mysql_free_result($result);
					// Log action
					logUserAction("Logged in.");
					// Redirect
					redirect('index.php?page=welcome');
				}
			}
		}
		break;
		
	case 'logout':
		// Log user out
		logUserAction("Logged out.");
		$_SESSION = array();									// Empty session superglobal array
		session_destroy();										// Destroy session server data
		setcookie(session_name(), '', time()-300, '/', '', 0);	// Destroy session client cookie
		break;
		
	case 'accountsettings':
		// Change password form parsing
		if(isset($_POST['submitted']) && isset($_POST['oldpass']) && isset($_POST['newpass1']) && isset($_POST['newpass2']))
		{
			// Validate inputs
			$message = "";
			# Do the old and new passwords match?
			if($_POST['oldpass'] == $_POST['newpass1']) $message = "<span class=\"errormessage\">New password and old password are the same!</span><br>\n";
			# Do the two new passwords match?
			if($_POST['newpass1'] != $_POST['newpass2']) $message = "<span class=\"errormessage\">New passwords do not match!</span><br>\n";
			# Does the old password match the actual old password?
			$query = "SELECT u_id FROM users WHERE u_id='" . mysql_real_escape_string($_SESSION['user_id']) . "'";
			$query .= " AND password=SHA1('" . mysql_real_escape_string($_POST['oldpass']) . "') LIMIT 1";
			$result = @mysql_query($query);
			if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
			if(mysql_num_rows($result) == 0) $message = "<span class=\"errormessage\">Old password is incorrect!</span><br>\n";
			mysql_free_result($result);
			
			// Continue if no errors...
			if($message == "")
			{
				// Update user data in database
				$query = "UPDATE users SET password=SHA1('" . mysql_real_escape_string($_POST['newpass1']) . "')";
				$query .= " WHERE u_id='" . mysql_real_escape_string($_SESSION['user_id']) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Check for success, log result and generate a message to the user
				if(mysql_affected_rows() == 1)
				{
					// Database update successful
					// Log success
					logUserAction("Changed their password.");
					// Geneate success message
					$message = "<span class=\"successmessage\">Your password was successfully changed!</span><br>\n";
				} else {
					// Database update failed
					// Log failure
					logUserAction("Attempted to change their password but was prevented by a database error.");
					// Generate an error message
					$message = "<span class=\"errormessage\">Your password could not be changed due to a database error!</span><br>\n";
				}
			}
		}
		break;
		
	case 'welcome':
		// Nothing to process for this page
		break;
		
	case 'adminpanel':
		// Extract suboption
		$sub = "";
		if(isset($_GET['sub'])) $sub = $_GET['sub'];
		
		// Parse user group creation submission
		if($sub == "create_ug" && isset($_POST['newug_name']))
		{
			// Validate inputs
			$message = "";
			# Was a name enterred?
			if(empty($_POST['newug_name'])) $message = "<span class=\"errormessage\">You must enter a name for the User Group!</span><br>\n";
			# Was the name within the length limits?
			if(strlen($_POST['newug_name']) > 50) $message = "<span class=\"errormessage\">User Group name cannot be longer than 50 characters!</span><br>\n";
			
			// Continue if no errors...
			if($message == "")
			{
				// Create user group in database
				$query = "INSERT INTO usergroups (name) VALUES ('" . mysql_real_escape_string($_POST['newug_name']) . "')";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Note primary key of new user group
				$row_id = mysql_insert_id();
				
				// Redirect if success, otherwise show error message
				if(mysql_affected_rows() == 1)
				{
					// Creation successful
					// Log success
					logUserAction("Added new User Group '" . htmlspecialchars($_POST['newug_name']). "'.");
					// Redirect to edit page for the new user group
					redirect('index.php?page=admin_usergroupsettings&ug_id=' . $row_id);
				} else {
					// Creation failed
					// Log failure
					logUserAction("Attempted to add a new User Group, but was prevented by a database error.");
					// Generate an error message
					$message = "<span class=\"errormessage\">The new User Group could not be added due to a database error!</span><br>\n";
				}
			}
		}
		
		// Parse user group modification submission
		if($sub == "edit_ug" && isset($_POST['editug_name']))
		{
			// Redirect to settings panel
			redirect('index.php?page=admin_usergroupsettings&ug_id=' . $_POST['editug_name']);
		}
		
		// Parse user group deletion submission
		if($sub == "delete_ug" && isset($_POST['deleteug_name']))
		{
			// Redirect to deletion panel
			redirect('index.php?page=admin_deleteusergroup&ug_id=' . $_POST['deleteug_name']);
		}
		
		// Parse user creation submission
		if($sub == "create_u" && isset($_POST['newu_name']))
		{
			// Validate inputs
			$message = "";
			# Was a name enterred?
			if(empty($_POST['newu_name'])) $message = "<span class=\"errormessage\">You must enter a name for the User!</span><br>\n";
			# Was the name within the length limits?
			if(strlen($_POST['newu_name']) > 50) $message = "<span class=\"errormessage\">User name cannot be longer than 50 characters!</span><br>\n";
			
			// Continue if no errors...
			if($message == "")
			{
				// Create user in database
				$query = "INSERT INTO users (name, ug_id) VALUES ('" . mysql_real_escape_string($_POST['newu_name']) . "', '1')";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Note primary key of new user
				$row_id = mysql_insert_id();
				
				// Redirect if success, otherwise show error message
				if(mysql_affected_rows() == 1)
				{
					// Creation successful
					// Log success
					logUserAction("Added new User '" . htmlspecialchars($_POST['newu_name']) . "'.");
					// Redirect to the edit page for the new user
					redirect('index.php?page=admin_usersettings&u_id=' . $row_id);
				} else {
					// Creation failed
					// Log failure
					logUserAction("Attempted to add a new User, but was prevented by a database error.");
					// Generate an error message
					$message = "<span class=\"errormessage\">The new User could not be added due to a database error!</span><br>\n";
				}
			}
		}
		
		// Parse user group modification submission
		if($sub == "edit_u" && isset($_POST['editu_name']))
		{
			// Redirect to settings panel
			redirect('index.php?page=admin_usersettings&u_id=' . $_POST['editu_name']);
		}
		
		// Parse user group deletion submission
		if($sub == "delete_u" && isset($_POST['deleteu_name']))
		{
			// Redirect to deletion panel
			redirect('index.php?page=admin_deleteuser&u_id=' . $_POST['deleteu_name']);
		}
		break;
		
	case 'admin_usergroupsettings':
		// Prepare form default values
		// Load values from database
		$query = "SELECT name, perm_admin, perm_daymanage, perm_scoreedit, perm_scoreview, perm_stats FROM usergroups";
		$query .= " WHERE ug_id='" . mysql_real_escape_string($_GET['ug_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$row = mysql_fetch_array($result);
		// Store values for later use
		$v_name = $row['name'];
		$v_pa = ($row['perm_admin'] == 1)? " checked=\"checked\"" : "";
		$v_dm = ($row['perm_daymanage'] == 1)? "checked=\"checked\"" : "";
		$v_se = ($row['perm_scoreedit'] == 1)? "checked=\"checked\"" : "";
		$v_sv = ($row['perm_scoreview'] == 1)? "checked=\"checked\"" : "";
		$v_st = ($row['perm_stats'] == 1)? "checked=\"checked\"" : "";
		mysql_free_result($result);
		
		// Settings form parsing
		if(isset($_POST['submitted']) && isset($_POST['groupname']))
		{
			// Validate inputs
			$message = "";
			# Was a name enterred?
			if(empty($_POST['groupname'])) $message = "<span class=\"errormessage\">You must enter a name for the User Group!</span><br>\n";
			# Was the name within the length limits?
			if(strlen($_POST['groupname']) > 50) $message = "<span class=\"errormessage\">User Group name cannot be longer than 50 characters!</span><br>\n";
			
			// Hold values of checkboxes
			$t_pa = (isset($_POST['perm_admin']))? 1 : 0;
			$t_dm = (isset($_POST['perm_daymanage']))? 1 : 0;
			$t_se = (isset($_POST['perm_scoreedit']))? 1 : 0;
			$t_sv = (isset($_POST['perm_scoreview']))? 1 : 0;
			$t_st = (isset($_POST['perm_stats']))? 1 : 0;
			
			// Update form default values to submitted values (instead of database values)
			$v_name = $_POST['groupname'];
			$v_pa = (isset($_POST['perm_admin']))? " checked=\"checked\"" : "";
			$v_dm = (isset($_POST['perm_daymanage']))? "checked=\"checked\"" : "";
			$v_se = (isset($_POST['perm_scoreedit']))? "checked=\"checked\"" : "";
			$v_sv = (isset($_POST['perm_scoreview']))? "checked=\"checked\"" : "";
			$v_st = (isset($_POST['perm_stats']))? "checked=\"checked\"" : "";
			
			// Continue if no errors...
			if($message == "")
			{
				// Update user group settings in database
				$query = "UPDATE usergroups SET name='" . mysql_real_escape_string($_POST['groupname']);
				$query .= "', perm_admin='" . mysql_real_escape_string($t_pa);
				$query .= "', perm_daymanage='" . mysql_real_escape_string($t_dm);
				$query .= "', perm_scoreedit='" . mysql_real_escape_string($t_se);
				$query .= "', perm_scoreview='" . mysql_real_escape_string($t_sv);
				$query .= "', perm_stats='" . mysql_real_escape_string($t_st);
				$query .= "' WHERE ug_id='" . mysql_real_escape_string($_GET['ug_id']) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Done!
				// Log success
				logUserAction("Updated the settings for User Group '" . htmlspecialchars($_POST['groupname']) . "'.");
				// Generate a success message
				$message = "<span class=\"successmessage\">User Group successfully updated!</span><br>\n";
				// Update page title
				$curpagetitle = "User Group Settings - '" . htmlspecialchars($_POST['groupname']) . "'";
			}
		}
		break;
		
	case 'admin_deleteusergroup':
		// Prepare form default values
		$del_done = FALSE;				// Whether a deletion has completed or not
		// Load values from database
		$query = "SELECT name FROM usergroups WHERE ug_id='" . mysql_real_escape_string($_GET['ug_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$row = mysql_fetch_array($result);
		// Store values for later use
		$v_name = $row['name'];
		mysql_free_result($result);
		
		// Confirm delete form parsing
		if(isset($_POST['submitted']) && isset($_POST['confirm']))
		{
			// Validate inputs
			$message = "";
			# Does the radio box have a valid value?
			if($_POST['confirm'] != 'Y' && $_POST['confirm'] != 'N') $message = "<span class=\"errormessage\">Invalid choice!</span><br>\n";
			
			// Continue if no errors...
			if($message == "")
			{
				// Which option was picked?
				if($_POST['confirm'] == 'Y')
				{
					// Check that the user group is empty
					$query = "SELECT u_id FROM users WHERE ug_id='" . mysql_real_escape_string($_GET['ug_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					if(mysql_num_rows($result) != 0)
					{
						// Cannot delete because there are users still in the group
						// Display error message
						$message = "<span class=\"errormessage\">You cannot delete this User Group as there are Users still in it. Remove these Users from the Group and try again.</span><br>\n";
					}
					mysql_free_result($result);
					
					// Delete the user group if no further errors were encountered
					if($message == "")
					{
						// Delete user group from database
						$query = "DELETE FROM usergroups WHERE ug_id='" . mysql_real_escape_string($_GET['ug_id']) . "' LIMIT 1";
						$result = @mysql_query($query);
						if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
						
						// Done!
						// Log success
						logUserAction("Deleted the User Group '" . htmlspecialchars($v_name) . "'.");
						// Generate success message
						$message = "<span class=\"successmessage\">User Group '" . htmlspecialchars($v_name) . "' was successfully deleted!</span><br>\n";
						// Note that a deletion has completed (for display code later)
						$del_done = TRUE;
					}
				} else {
					// Redirect to admin panel
					redirect('index.php?page=adminpanel');
				}
			}
		}
		break;
		
	case 'admin_usersettings':
		// Prepare form default values
		// Load values from database
		$query = "SELECT name, ug_id FROM users";
		$query .= " WHERE u_id='" . mysql_real_escape_string($_GET['u_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$row = mysql_fetch_array($result);
		// Store values for later use
		$v_name = $row['name'];
		$v_group = $row['ug_id'];
		mysql_free_result($result);
		
		// Settings form parsing
		if(isset($_POST['submitted_normal']) && isset($_POST['username']) && isset($_POST['group']))
		{
			// Validate inputs
			$message = "";
			# Is the user group id numeric?
			if(!is_numeric($_POST['group']))  $message = "<span class=\"errormessage\">Invalid User Group!</span><br>\n";
			# Was a name enterred?
			if(empty($_POST['username'])) $message = "<span class=\"errormessage\">You must enter a name for the User!</span><br>\n";
			# Was the name within the length limits?
			if(strlen($_POST['username']) > 50) $message = "<span class=\"errormessage\">User name cannot be longer than 50 characters!</span><br>\n";
			
			// Update form default values to submitted values (instead of database values)
			$v_name = $_POST['username'];
			$v_group = $_POST['group'];
			
			// Continue if no errors...
			if($message == "")
			{
				// Update user in database
				$query = "UPDATE users SET name='" . mysql_real_escape_string($_POST['username']);
				$query .= "', ug_id='" . mysql_real_escape_string($_POST['group']);
				$query .= "' WHERE u_id='" . mysql_real_escape_string($_GET['u_id']) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Done!
				// Log success
				logUserAction("Updated the settings for User '" . htmlspecialchars($_POST['username']) . "'.");
				// Generate success message
				$message = "<span class=\"successmessage\">User successfully updated!</span><br>\n";
				// Update page title
				$curpagetitle = "User Settings - '" . htmlspecialchars($_POST['username']) . "'";
			}
		}
		
		// Password form parsing
		if(isset($_POST['submitted_password']) && isset($_POST['newpass1']) && isset($_POST['newpass2']))
		{
			// Validate inputs
			$message = "";
			# Do the two new passwords match?
			if($_POST['newpass1'] != $_POST['newpass2']) $message = "<span class=\"errormessage\">New passwords do not match!</span><br>\n";
			
			// Continue if no errors...
			if($message == "")
			{
				// Update user in database
				$query = "UPDATE users SET password=SHA1('" . mysql_real_escape_string($_POST['newpass1']);
				$query .= "') WHERE u_id='" . mysql_real_escape_string($_GET['u_id']) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Done!
				// Log success
				logUserAction("Updated the password for User '" . htmlspecialchars($v_name) . "'.");
				// Generate success message
				$message = "<span class=\"successmessage\">Password successfully updated!</span><br>\n";
			}
		}
		break;
		
	case 'admin_deleteuser':
		// Prepare form default values
		$del_done = FALSE;				// Whether a deletion has completed or not
		// Load values from database
		$query = "SELECT name FROM users WHERE u_id='" . mysql_real_escape_string($_GET['u_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$row = mysql_fetch_array($result);
		// Store values for later use
		$v_name = $row['name'];
		mysql_free_result($result);
		
		// Confirm delete form parsing
		if(isset($_POST['submitted']) && isset($_POST['confirm']))
		{
			// Validate inputs
			$message = "";
			# Does the radio box have a valid value?
			if($_POST['confirm'] != 'Y' && $_POST['confirm'] != 'N') $message = "<span class=\"errormessage\">Invalid choice!</span><br>\n";
			
			// Continue if no errors...
			if($message == "")
			{
				// Which option was picked?
				if($_POST['confirm'] == 'Y')
				{
					// Delete user from database
					$query = "DELETE FROM users WHERE u_id='" . mysql_real_escape_string($_GET['u_id']) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					
					// Done!
					// Log success
					logUserAction("Deleted the User '" . htmlspecialchars($v_name) . "'.");
					// Generate success message
					$message = "<span class=\"successmessage\">User '" . htmlspecialchars($v_name) . "' was successfully deleted!</span><br>\n";
					$del_done = TRUE;
				} else {
					// Redirect to admin panel
					redirect('index.php?page=adminpanel');
				}
			}
		}
		break;
		
	case 'admin_logs':
		// Nothing to do
		break;
		
	case 'comppanel':
		// Extract suboption from address
		$sub = "";
		if(isset($_GET['sub'])) $sub = $_GET['sub'];
		
		// Parse template creation submission
		if($sub == "create_tem" && isset($_POST['newtem_name']))
		{
			// Validate inputs
			$message = "";
			# Was a name enterred?
			if(empty($_POST['newtem_name'])) $message = "<span class=\"errormessage\">You must enter a name for the Template!</span><br>\n";
			# Was the name within the length limits?
			if(strlen($_POST['newtem_name']) > 50) $message = "<span class=\"errormessage\">Template name cannot be longer than 50 characters!</span><br>\n";
			
			// Continue if no errors...
			if($message == "")
			{
				// Create template in database
				$query = "INSERT INTO compotemplates (name) VALUES ('" . mysql_real_escape_string($_POST['newtem_name']) . "')";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Note primary key of new template
				$row_id = mysql_insert_id();
				
				// Redirect if success, otherwise show error message
				if(mysql_affected_rows() == 1)
				{
					// Creation successful
					// Log success
					logUserAction("Added new Competition Template '" . htmlspecialchars($_POST['newtem_name']) . "'.");
					// Redirect to edit page for the new template
					redirect('index.php?page=comp_templatesettings&ct_id=' . $row_id);
				} else {
					// Creation failed
					// Log failure
					logUserAction("Attempted to add new Competition Template, but was prevented by a database error.");
					// Generate an error message
					$message = "<span class=\"errormessage\">The new Template could not be added due to a database error!</span><br>\n";
				}
			}
		}
		
		// Parse template modification submission
		if($sub == "edit_tem" && isset($_POST['edittem_name']))
		{
			// Redirect to settings panel
			redirect('index.php?page=comp_templatesettings&ct_id=' . $_POST['edittem_name']);
		}
		
		// Parse template deletion submission
		if($sub == "delete_tem" && isset($_POST['deletetem_name']))
		{
			// Redirect to deletion panel
			redirect('index.php?page=comp_deletetemplate&ct_id=' . $_POST['deletetem_name']);
		}
		
		// Parse day creation submission
		if($sub == "create_day" && isset($_POST['newday_name']))
		{
			// Validate inputs
			$message = "";
			# Has a template been specified?
			if(isset($_POST['newday_template']))
			{
				# Is the template id a number?
				if(!is_numeric($_POST['newday_template'])) $message = "<span class=\"errormessage\">Invalid Template!</span><br>\n";
				# Does the template exist?
				$query = "SELECT name FROM compotemplates WHERE ct_id='" . mysql_real_escape_string($_POST['newday_template']) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				if(mysql_num_rows($result) == 0) $message = "<span class=\"errormessage\">Invalid Template!</span><br>\n";
				mysql_free_result($result);
			} else {
				$message = "<span class=\"errormessage\">You must specify a Template for the Day!</span><br>\n";
			}
			# Was a name enterred?
			if(empty($_POST['newday_name'])) $message = "<span class=\"errormessage\">You must enter a name for the Day!</span><br>\n";
			# Was the name within the length limits?
			if(strlen($_POST['newday_name']) > 50) $message = "<span class=\"errormessage\">Day name cannot be longer than 50 characters!</span><br>\n";
			
			// Continue if no errors...
			if($message == "")
			{
				// Load template base settings
				$query = "SELECT max_events_per_competitor FROM compotemplates WHERE ct_id='" . mysql_real_escape_string($_POST['newday_template']) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$row = mysql_fetch_array($result);
				mysql_free_result($result);
				
				// Create new day in database
				$query = "INSERT INTO days (name, max_events_per_competitor) VALUES ('" . mysql_real_escape_string($_POST['newday_name']) . "',";
				$query .= " '" . mysql_real_escape_string($row['max_events_per_competitor']) . "')";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$newdayid = mysql_insert_id();					// Note primary key of new day
				
				// Copy dependent teams from template into new day in database
				$query = "SELECT name FROM comtem_teams WHERE ct_id='" . mysql_real_escape_string($_POST['newday_template']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$query2 = "INSERT INTO day_teams (d_id, name) VALUES ";
				while($row = mysql_fetch_array($result))
				{
					$query2 .= "('" . mysql_real_escape_string($newdayid) . "', '" . mysql_real_escape_string($row['name']) . "'), ";
				}
				$query2 = substr($query2, 0, strlen($query2) - 2);
				$result2 = @mysql_query($query2);
				if(!$result2) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				mysql_free_result($result);
				
				// Copy dependent subgroups from template into new day in database
				$query = "SELECT sub_id, name FROM comtem_subgroups WHERE ct_id='" . mysql_real_escape_string($_POST['newday_template']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				while($row = mysql_fetch_array($result))
				{
					$query2 = "INSERT INTO day_subgroups (d_id, name) VALUES ";
					$query2 .= "('" . mysql_real_escape_string($newdayid) . "', '" . mysql_real_escape_string($row['name']) . "')";
					$result2 = @mysql_query($query2);
					if(!$result2) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					$sqlsubmap[$row['sub_id']] = mysql_insert_id();
				}
				mysql_free_result($result);
				
				// Copy dependent scoring schemes from template into new day in database
				$query = "SELECT ss_id, name, count_entrants_per_team, result_order, result_type, result_units, result_units_dp FROM comtem_scoreschemes";
				$query .= " WHERE ct_id='" . mysql_real_escape_string($_POST['newday_template']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				while($row = mysql_fetch_array($result))
				{
					$query2 = "INSERT INTO day_scoreschemes (d_id, name, count_entrants_per_team, result_order, result_type, result_units, result_units_dp) VALUES ";
					$query2 .= "('" . mysql_real_escape_string($newdayid) . "', ";
					$query2 .= "'" . mysql_real_escape_string($row['name']) . "', ";
					$query2 .= "'" . mysql_real_escape_string($row['count_entrants_per_team']) . "', ";
					$query2 .= "'" . mysql_real_escape_string($row['result_order']) . "', ";
					$query2 .= "'" . mysql_real_escape_string($row['result_type']) . "', ";
					$query2 .= "'" . mysql_real_escape_string($row['result_units']) . "', ";
					$query2 .= "'" . mysql_real_escape_string($row['result_units_dp']) . "')";
					$result2 = @mysql_query($query2);
					if(!$result2) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					$sqlschememap[$row['ss_id']] = mysql_insert_id();
				}
				mysql_free_result($result);
				
				// Copy dependent scoring scheme scores from template into new day in database
				$query = "SELECT comtem_scorescheme_scores.ss_id AS scheme_id, comtem_scorescheme_scores.place AS score_place, comtem_scorescheme_scores.score AS score_score";
				$query .= " FROM comtem_scorescheme_scores, comtem_scoreschemes";
				$query .= " WHERE comtem_scorescheme_scores.ss_id=comtem_scoreschemes.ss_id";
				$query .= " AND comtem_scoreschemes.ct_id='" . mysql_real_escape_string($_POST['newday_template']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$query2 = "INSERT INTO day_scorescheme_scores (ss_id, place, score) VALUES ";
				while($row = mysql_fetch_array($result))
				{
					$query2 .= "('" . mysql_real_escape_string($sqlschememap[$row['scheme_id']]) . "', ";
					$query2 .= "'" . mysql_real_escape_string($row['score_place']) . "', ";
					$query2 .= "'" . mysql_real_escape_string($row['score_score']) . "'), ";
				}
				$query2 = substr($query2, 0, strlen($query2) - 2);
				$result2 = @mysql_query($query2);
				if(!$result2) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				mysql_free_result($result);
				
				// Copy dependent events from template into new day and create blank records in database
				$query = "SELECT e_id, name, ss_id, counts_to_limit FROM comtem_events WHERE ct_id='" . mysql_real_escape_string($_POST['newday_template']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$recquery = "INSERT INTO day_records (d_id, e_id) VALUES";				// Database query for record insertion to gradually build up
				while($row = mysql_fetch_array($result))
				{
					$query2 = "INSERT INTO day_events (d_id, name, ss_id, counts_to_limit) VALUES ";
					$query2 .= "('" . mysql_real_escape_string($newdayid) . "', ";
					$query2 .= "'" . mysql_real_escape_string($row['name']) . "', ";
					$query2 .= "'" . mysql_real_escape_string($sqlschememap[$row['ss_id']]) . "', ";
					$query2 .= "'" . mysql_real_escape_string($row['counts_to_limit']) . "')";
					$result2 = @mysql_query($query2);
					if(!$result2) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					
					// Use primary key of newly inserted event to append a new section to the record insertion query
					$sqleventmap[$row['e_id']] = mysql_insert_id();
					$recquery .= "('" . mysql_real_escape_string($newdayid) . "', '" . mysql_real_escape_string($sqleventmap[$row['e_id']]) . "'), ";
				}
				mysql_free_result($result);
				$recquery = substr($recquery, 0, strlen($recquery) - 2);					// Remove final ', ' from end of record query
				$recresult = @mysql_query($recquery);										// Execute fully built record insertion query
				if(!$recresult) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Copy dependent group eligibilities from template into new day in database
				$query = "SELECT comtem_groupeligibility.sub_id AS subgroup_id, comtem_groupeligibility.e_id AS event_id";
				$query .= " FROM comtem_groupeligibility, comtem_events";
				$query .= " WHERE comtem_groupeligibility.e_id=comtem_events.e_id";
				$query .= " AND comtem_events.ct_id='" . mysql_real_escape_string($_POST['newday_template']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$query2 = "INSERT INTO day_groupeligibility (sub_id, e_id) VALUES ";
				while($row = mysql_fetch_array($result))
				{
					$query2 .= "('" . mysql_real_escape_string($sqlsubmap[$row['subgroup_id']]) . "', ";
					$query2 .= "'" . mysql_real_escape_string($sqleventmap[$row['event_id']]) . "'), ";
				}
				$query2 = substr($query2, 0, strlen($query2) - 2);
				$result2 = @mysql_query($query2);
				if(!$result2) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				mysql_free_result($result);
				
				// Log addition of day
				logUserAction("Added new Day '" . htmlspecialchars($_POST['newday_name']) . "'.");
				
				// Redirect to the edit page for the new day
				redirect('index.php?page=comp_daysettings&d_id=' . $newdayid);
			}
		}
		
		// Parse day modification submission
		if($sub == "edit_day" && isset($_POST['editday_name']))
		{
			// Redirect to settings panel
			redirect('index.php?page=comp_daysettings&d_id=' . $_POST['editday_name']);
		}
		
		// Parse day adjustment submission
		if($sub == "adjust_day" && isset($_POST['adjustday_name']))
		{
			// Redirect to settings panel
			redirect('index.php?page=comp_adjustday&d_id=' . $_POST['adjustday_name']);
		}
		
		// Parse day deletion submission
		if($sub == "delete_day" && isset($_POST['deleteday_name']))
		{
			// Redirect to deletion panel
			redirect('index.php?page=comp_deleteday&d_id=' . $_POST['deleteday_name']);
		}
		
		// Parse set active day submission
		if($sub == "set_activeday" && isset($_POST['setactiveday_name']))
		{
			// (No inputs to validate)
			$message = "";
			
			// Deactivate all days
			$query = "UPDATE days SET is_active_day='0'";
			$result = @mysql_query($query);
			if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
			
			// Activate this day, if it exists
			$query = "UPDATE days SET is_active_day='1' WHERE d_id='" . mysql_real_escape_string($_POST['setactiveday_name']) . "' LIMIT 1";
			$result = @mysql_query($query);
			if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
			
			// Output appropriate response message
			if(mysql_affected_rows() == 1)
			{
				// 1 row updated, so day exists and was updated
				// Log success
				logUserAction("Changed the Active Day to database ID " . htmlspecialchars($_POST['setactiveday_name']) . ".");
				// Generate success message
				$message = "<span class=\"successmessage\">Active Day successfully changed!</span><br>\n";
			} else {
				// No rows updated, so day does not exist
				// Log failure
				logUserAction("Attempted to change the Active Day, but specified an invalid Day.");
				// Generate an error message
				$message = "<span class=\"errormessage\">Invalid Day!</span><br>\n";
			}
		}
		
		// Parse manage records submission
		if($sub == "manage_records" && isset($_POST['managerecords_name']))
		{
			// Redirect to management panel
			redirect('index.php?page=comp_managerecords&d_id=' . $_POST['managerecords_name']);
		}
		break;
		
	case 'comp_templatesettings':
		// Prepare form default values and form building scripts
		// Basic settings
		$query = "SELECT name, max_events_per_competitor FROM compotemplates WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$row = mysql_fetch_array($result);
		$v_name = $row['name'];
		$v_max_events_per_competitor = $row['max_events_per_competitor'];
		mysql_free_result($result);
		
		// Teams
		$query = "SELECT name FROM comtem_teams WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$v_script_teambuilder = "";
		$i = 0;
		while($row = mysql_fetch_array($result))
		{
			$i++;
			$v_script_teambuilder .= "\t\t\t\t\t\t\t\t$(\"#btn_ct_addteam\").click();\n";
			$v_script_teambuilder .= "\t\t\t\t\t\t\t\t$(\"#teamnamebox$i\").val(\"" . htmlspecialchars($row['name']) . "\");\n";
		}
		mysql_free_result($result);
		
		// Subgroups
		$query = "SELECT sub_id, name FROM comtem_subgroups WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$v_script_subbuilder = "";
		$i = 0;
		while($row = mysql_fetch_array($result))
		{
			$i++;
			$submap[$row['sub_id']] = $i;
			$v_script_subbuilder .= "\t\t\t\t\t\t\t\t$(\"#btn_ct_addsub\").click();\n";
			$v_script_subbuilder .= "\t\t\t\t\t\t\t\t$(\"#subnamebox$i\").val(\"" . htmlspecialchars($row['name']) . "\");\n";
		}
		mysql_free_result($result);
		
		// Scoring Schemes
		$query = "SELECT ss_id, name, count_entrants_per_team, result_order, result_type, result_units, result_units_dp FROM comtem_scoreschemes";
		$query .= " WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$v_script_schemebuilder = "";
		$i = 0;
		while($row = mysql_fetch_array($result))
		{
			$i++;
			$schememap[$row['ss_id']] = $i;
			$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#btn_ct_addscheme\").click();\n";
			$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#schemenamebox$i\").val(\"" . htmlspecialchars($row['name']) . "\");\n";
			$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#schemeentrantsbox$i\").val(\"" . htmlspecialchars($row['count_entrants_per_team']) . "\").change();\n";
			$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#schemeorderselect$i\").val(\"" . htmlspecialchars($row['result_order']) . "\");\n";
			$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#schemetypebox$i\").val(\"" . htmlspecialchars($row['result_type']) . "\");\n";
			$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#schemeunitbox$i\").val(\"" . htmlspecialchars($row['result_units']) . "\");\n";
			$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#schemeunitdpbox$i\").val(\"" . htmlspecialchars($row['result_units_dp']) . "\");\n";
		}
		mysql_free_result($result);
		
		// Scoring Schemes Scores
		$query = "SELECT comtem_scorescheme_scores.ss_id AS ss_id, comtem_scorescheme_scores.place AS place, comtem_scorescheme_scores.score AS score";
		$query .= " FROM comtem_scorescheme_scores, comtem_scoreschemes";
		$query .= " WHERE comtem_scorescheme_scores.ss_id=comtem_scoreschemes.ss_id";
		$query .= " AND comtem_scoreschemes.ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$v_script_scorebuilder = "";
		while($row = mysql_fetch_array($result))
		{
			$v_script_scorebuilder .= "\t\t\t\t\t\t\t\t$(\"#scorebox" . htmlspecialchars($schememap[$row['ss_id']]) . "_";
			$v_script_scorebuilder .= htmlspecialchars($row['place']) . "\").val(\"" . truncate_number($row['score']) . "\");\n";
		}
		mysql_free_result($result);
		
		// Events
		$query = "SELECT comtem_events.e_id AS event_id, comtem_events.name AS eventname, comtem_events.ss_id AS scheme_id,";
		$query .= " comtem_events.counts_to_limit AS counts_to_limit, comtem_scoreschemes.count_entrants_per_team AS entrants_per_team";
		$query .= " FROM comtem_events, comtem_scoreschemes";
		$query .= " WHERE comtem_events.ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
		$query .= " AND comtem_events.ss_id=comtem_scoreschemes.ss_id";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$v_script_eventbuilder = "";
		$i = 0;
		while($row = mysql_fetch_array($result))
		{
			$i++;
			$v_script_eventbuilder .= "\t\t\t\t\t\t\t\t$(\"#btn_ct_addevent\").click();\n";
			$v_script_eventbuilder .= "\t\t\t\t\t\t\t\t$(\"#eventnamebox$i\").val(\"" . htmlspecialchars($row['eventname']) . "\");\n";
			$v_script_eventbuilder .= "\t\t\t\t\t\t\t\t$(\"#evententrantsbox$i\").val(\"" . htmlspecialchars($row['entrants_per_team']) . "\").change();\n";
			$v_script_eventbuilder .= "\t\t\t\t\t\t\t\t$(\"#eventschemeselect$i\").val(\"" . htmlspecialchars($schememap[$row['scheme_id']]) . "\");\n";
			$v_script_eventbuilder .= "\t\t\t\t\t\t\t\t$(\"#eventlimitchk$i\").prop(\"checked\", " . ($row['counts_to_limit'] == 1 ? "true" : "false") . ");\n";
			$query2 = "SELECT sub_id FROM comtem_groupeligibility WHERE e_id='" . mysql_real_escape_string($row['event_id']) . "'";
			$result2 = @mysql_query($query2);
			if(!$result2) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
			while($row2 = mysql_fetch_array($result2))
			{
				$v_script_eventbuilder .= "\t\t\t\t\t\t\t\t$(\"#eventsuboption$i" . "_" . htmlspecialchars($submap[$row2['sub_id']]) . "\").prop(\"selected\", \"selected\");\n";
			}
			mysql_free_result($result2);
		}
		mysql_free_result($result);
		
		// Update form parsing
		if(isset($_POST['submitted']) && isset($_POST['name']) && isset($_POST['max_events']))
		{
			// Validate inputs
			$message = "";
			# Validate events
			if(isset($_POST['eventsubs']))
			{
				foreach($_POST['eventsubs'] as $subs)
				{
					foreach($subs as $isub)
					{
						# Does the sub id exist?
						if(!isset($_POST['subname'][$isub]))
						{
							$message = "<span class=\"errormessage\">Invalid Subgroup for Event!</span><br>\n";
						}
						# Is the sub id a number?
						if(!is_numeric($isub)) $message = "<span class=\"errormessage\">Invalid Subgroup for Event!</span><br>\n";
					}
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Event!</span><br>\n";
			}
			if(isset($_POST['eventscoreschemes']))
			{
				foreach($_POST['eventscoreschemes'] as $schemes)
				{
					# Does the scheme id exist?
					if(!isset($_POST['schemename'][$schemes]))
					{
						$message = "<span class=\"errormessage\">Invalid Scoring Scheme for Event!</span><br>\n";
					}
					# Is the scheme id a number?
					if(!is_numeric($schemes)) $message = "<span class=\"errormessage\">Invalid Scoring Scheme for Event!</span><br>\n";
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Event!</span><br>\n";
			}
			if(isset($_POST['evententrants']))
			{
				foreach($_POST['evententrants'] as $entrants)
				{
					# Is the entrant count a number?
					if(!is_numeric($entrants)) $message = "<span class=\"errormessage\">Entrants per team must be a number!</span><br>\n";
					# Is the entrant count greater than 0?
					if($entrants <= 0) $message = "<span class=\"errormessage\">Entrants per team must be greater than 0!</span><br>\n";
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Event!</span><br>\n";
			}
			if(isset($_POST['eventname']))
			{
				foreach($_POST['eventname'] as $name)
				{
					# Was a name enterred?
					if(empty($name)) $message = "<span class=\"errormessage\">Event names cannot be blank!</span><br>\n";
					# Was the name within the length limits?
					if(strlen($name) > 50) $message = "<span class=\"errormessage\">Event names cannot be longer than 50 characters!</span><br>\n";
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Event!</span><br>\n";
			}
			# Validate scoring scheme scores
			if(isset($_POST['score']))
			{
				foreach($_POST['score'] as $scoreset)
				{
					foreach($scoreset as $score)
					{
						# Is the score a number?
						if(!is_numeric($score)) $message = "<span class=\"errormessage\">Scores must be a number!</span><br>\n";
					}
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Scoring Scheme!</span><br>\n";
			}
			# Validate scoring schemes
			if(isset($_POST['schemeresultunitdp']))
			{
				foreach($_POST['schemeresultunitdp'] as $unitdp)
				{
					# Is the decimal place count within bounds?
					if($unitdp < 1 || $unitdp > 6) $message = "<span class=\"errormessage\">Result unit decimal place count must be between 1 and 6 inclusive!</span><br>\n";
					# Is the decimal place count a number?
					if(!is_numeric($unitdp)) $message = "<span class=\"errormessage\">Result unit decimal place count must be a number!</span><br>\n";
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Scoring Scheme!</span><br>\n";
			}
			if(isset($_POST['schemeresultunit']))
			{
				foreach($_POST['schemeresultunit'] as $unit)
				{
					# Was a unit enterred?
					if(empty($unit)) $message = "<span class=\"errormessage\">Result units cannot be blank!</span><br>\n";
					# Was the unit within the length limits?
					if(strlen($unit) > 20) $message = "<span class=\"errormessage\">Result units cannot be longer than 50 characters!</span><br>\n";
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Scoring Scheme!</span><br>\n";
			}
			if(isset($_POST['schemeresulttype']))
			{
				foreach($_POST['schemeresulttype'] as $type)
				{
					# Was a type enterred?
					if(empty($type)) $message = "<span class=\"errormessage\">Result types cannot be blank!</span><br>\n";
					# Was the type within the length limits?
					if(strlen($type) > 30) $message = "<span class=\"errormessage\">Result types cannot be longer than 30 characters!</span><br>\n";
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Scoring Scheme!</span><br>\n";
			}
			if(isset($_POST['schemeresultorder']))
			{
				foreach($_POST['schemeresultorder'] as $order)
				{
					# Is the result order valid?
					if($order != "asc" && $order != "desc") $message = "<span class=\"errormessage\">Invalid result order!</span><br>\n";
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Scoring Scheme!</span><br>\n";
			}
			if(isset($_POST['schemeentrants']))
			{
				foreach($_POST['schemeentrants'] as $entrants)
				{
					# Is the entrant count a number?
					if(!is_numeric($entrants)) $message = "<span class=\"errormessage\">Entrants per team must be a number!</span><br>\n";
					# Is the entrant count greater than 0?
					if($entrants <= 0) $message = "<span class=\"errormessage\">Entrants per team must be greater than 0!</span><br>\n";
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Scoring Scheme!</span><br>\n";
			}
			if(isset($_POST['schemename']))
			{
				foreach($_POST['schemename'] as $name)
				{
					# Was a name enterred?
					if(empty($name)) $message = "<span class=\"errormessage\">Scoring Scheme names cannot be blank!</span><br>\n";
					# Was the name within the length limits?
					if(strlen($name) > 50) $message = "<span class=\"errormessage\">Scoring Scheme names cannot be longer than 50 characters!</span><br>\n";
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Scoring Scheme!</span><br>\n";
			}
			# Validate subgroups
			if(isset($_POST['subname']))
			{
				foreach($_POST['subname'] as $name)
				{
					# Was a name enterred?
					if(empty($name)) $message = "<span class=\"errormessage\">Subgroup names cannot be blank!</span><br>\n";
					# Was the name within the length limits?
					if(strlen($name) > 50) $message = "<span class=\"errormessage\">Subgroup names cannot be longer than 50 characters!</span><br>\n";
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Subgroup!</span><br>\n";
			}
			# Validate teams
			if(isset($_POST['teamname']))
			{
				foreach($_POST['teamname'] as $name)
				{
					# Was a name enterred?
					if(empty($name)) $message = "<span class=\"errormessage\">Team names cannot be blank!</span><br>\n";
					# Was the name within the length limits?
					if(strlen($name) > 50) $message = "<span class=\"errormessage\">Team names cannot be longer than 50 characters!</span><br>\n";
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Team!</span><br>\n";
			}
			# Is the maximum event field a number?
			if(!is_numeric($_POST['max_events'])) $message = "<span class=\"errormessage\">Maximum events per competitor must be a number!</span><br>\n";
			# Is the maximum event field greater than 0?
			if($_POST['max_events'] <= 0) $message = "<span class=\"errormessage\">Maximum events per competitor must be greater than 0!</span><br>\n";
			# Was a name enterred?
			if(empty($_POST['name'])) $message = "<span class=\"errormessage\">You must enter a name for the Template!</span><br>\n";
			# Was the name within the length limits?
			if(strlen($_POST['name']) > 50) $message = "<span class=\"errormessage\">Template name cannot be longer than 50 characters!</span><br>\n";
			
			// Update form default values and form building scripts
			// Basic settings
			$v_name = $_POST['name'];
			$v_max_events_per_competitor = $_POST['max_events'];
			
			// Teams
			$v_script_teambuilder = "";
			$i = 0;
			if(isset($_POST['teamname']))
			{
				foreach($_POST['teamname'] as $name)
				{
					$i++;
					$v_script_teambuilder .= "\t\t\t\t\t\t\t\t$(\"#btn_ct_addteam\").click();\n";
					$v_script_teambuilder .= "\t\t\t\t\t\t\t\t$(\"#teamnamebox$i\").val(\"" . htmlspecialchars($name) . "\");\n";
				}
			}
			
			// Subgroups
			$v_script_subbuilder = "";
			$i = 0;
			if(isset($_POST['subname']))
			{
				foreach($_POST['subname'] as $key => $name)
				{
					$i++;
					$submap[$key] = $i;
					$v_script_subbuilder .= "\t\t\t\t\t\t\t\t$(\"#btn_ct_addsub\").click();\n";
					$v_script_subbuilder .= "\t\t\t\t\t\t\t\t$(\"#subnamebox$i\").val(\"" . htmlspecialchars($name) . "\");\n";
				}
			}
			
			// Scoring Schemes
			$v_script_schemebuilder = "";
			$i = 0;
			if(isset($_POST['schemename']) && isset($_POST['schemeentrants']) && isset($_POST['schemeresultorder']) && isset($_POST['schemeresulttype'])
				&& isset($_POST['schemeresultunit']))
			{
				foreach($_POST['schemename'] as $key => $name)
				{
					$i++;
					$schememap[$key] = $i;
					$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#btn_ct_addscheme\").click();\n";
					$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#schemenamebox$i\").val(\"" . htmlspecialchars($_POST['schemename'][$key]) . "\");\n";
					$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#schemeentrantsbox$i\").val(\"" . htmlspecialchars($_POST['schemeentrants'][$key]) . "\").change();\n";
					$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#schemeorderselect$i\").val(\"" . htmlspecialchars($_POST['schemeresultorder'][$key]) . "\");\n";
					$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#schemetypebox$i\").val(\"" . htmlspecialchars($_POST['schemeresulttype'][$key]) . "\");\n";
					$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#schemeunitbox$i\").val(\"" . htmlspecialchars($_POST['schemeresultunit'][$key]) . "\");\n";
					$v_script_schemebuilder .= "\t\t\t\t\t\t\t\t$(\"#schemeunitdpbox$i\").val(\"" . htmlspecialchars($_POST['schemeresultunitdp'][$key]) . "\");\n";
				}
			}
			
			// Scoring Schemes Scores
			$v_script_scorebuilder = "";
			if(isset($_POST['score']))
			{
				foreach($_POST['score'] as $ssidkey => $scoreset)
				{
					foreach($scoreset as $placekey => $score)
					{
						$v_script_scorebuilder .= "\t\t\t\t\t\t\t\t$(\"#scorebox" . htmlspecialchars($schememap[$ssidkey]) . "_";
						$v_script_scorebuilder .= htmlspecialchars($placekey) . "\").val(\"" . truncate_number($score) . "\");\n";
					}
				}
			}
			
			// Events
			$v_script_eventbuilder = "";
			$i = 0;
			if(isset($_POST['eventname']) && isset($_POST['evententrants']))
			{
				foreach($_POST['eventname'] as $key => $name)
				{
					$i++;
					$v_script_eventbuilder .= "\t\t\t\t\t\t\t\t$(\"#btn_ct_addevent\").click();\n";
					$v_script_eventbuilder .= "\t\t\t\t\t\t\t\t$(\"#eventnamebox$i\").val(\"" . htmlspecialchars($_POST['eventname'][$key]) . "\");\n";
					$v_script_eventbuilder .= "\t\t\t\t\t\t\t\t$(\"#evententrantsbox$i\").val(\"" . htmlspecialchars($_POST['evententrants'][$key]) . "\").change();\n";
					if(isset($_POST['eventscoreschemes'][$key]))
					{
						$v_script_eventbuilder .= "\t\t\t\t\t\t\t\t$(\"#eventschemeselect$i\").val(\"" . htmlspecialchars($schememap[$_POST['eventscoreschemes'][$key]]) . "\");\n";
					}
					$v_script_eventbuilder .= "\t\t\t\t\t\t\t\t$(\"#eventlimitchk$i\").prop(\"checked\", " . (isset($_POST['eventcountstolimit'][$key]) ? "true" : "false") . ");\n";
					foreach($_POST['eventsubs'] as $subs)
					{
						foreach($subs as $eventkey => $isub)
						{
							if($eventkey == $key)
							{
								$v_script_eventbuilder .= "\t\t\t\t\t\t\t\t$(\"#eventsuboption$i" . "_" . htmlspecialchars($submap[$isub]) . "\").prop(\"selected\", \"selected\");\n";
							}
						}
					}
				}
			}
			
			// Continue if no errors...
			if($message == "")
			{
				// Remove all the old stuff for this template from database
				// Delete dependent group eligibilities from database
				$query = "DELETE comtem_groupeligibility FROM comtem_groupeligibility, comtem_events";
				$query .= " WHERE comtem_groupeligibility.e_id=comtem_events.e_id AND comtem_events.ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				// Delete dependent scoring scheme scores from database
				$query = "DELETE comtem_scorescheme_scores FROM comtem_scorescheme_scores, comtem_scoreschemes";
				$query .= " WHERE comtem_scorescheme_scores.ss_id=comtem_scoreschemes.ss_id AND comtem_scoreschemes.ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				// Delete dependent events from database
				$query = "DELETE FROM comtem_events WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				// Delete dependent scoring schemes from database
				$query = "DELETE FROM comtem_scoreschemes WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				// Delete dependent subgroups from database
				$query = "DELETE FROM comtem_subgroups WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				// Delete dependent teams from database
				$query = "DELETE FROM comtem_teams WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Insert all the new stuff for this template into database
				// Update template in database
				$query = "UPDATE compotemplates SET name='" . mysql_real_escape_string($_POST['name']);
				$query .= "', max_events_per_competitor='" . mysql_real_escape_string($_POST['max_events']);
				$query .= "' WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				// Insert dependent teams into database
				$query = "INSERT INTO comtem_teams (ct_id, name) VALUES ";
				foreach($_POST['teamname'] as $name)
				{
					$query .= "('" . mysql_real_escape_string($_GET['ct_id']) . "', '" . mysql_real_escape_string($name) . "'), ";
				}
				$query = substr($query, 0, strlen($query) - 2);
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				// Insert dependent subgroups into database
				foreach($_POST['subname'] as $key => $name)
				{
					$query = "INSERT INTO comtem_subgroups (ct_id, name) VALUES ";
					$query .= "('" . mysql_real_escape_string($_GET['ct_id']) . "', '" . mysql_real_escape_string($name) . "')";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					$sqlsubmap[$key] = mysql_insert_id();			// Build a mapping of subgroup form ids to new database primary keys
				}
				// Insert dependent scoring schemes into database
				foreach($_POST['schemename'] as $key => $name)
				{
					$query = "INSERT INTO comtem_scoreschemes (ct_id, name, count_entrants_per_team, result_order, result_type, result_units, result_units_dp) VALUES ";
					$query .= "('" . mysql_real_escape_string($_GET['ct_id']) . "', ";
					$query .= "'" . mysql_real_escape_string($name) . "', ";
					$query .= "'" . mysql_real_escape_string($_POST['schemeentrants'][$key]) . "', ";
					$query .= "'" . mysql_real_escape_string($_POST['schemeresultorder'][$key]) . "', ";
					$query .= "'" . mysql_real_escape_string($_POST['schemeresulttype'][$key]) . "', ";
					$query .= "'" . mysql_real_escape_string($_POST['schemeresultunit'][$key]) . "', ";
					$query .= "'" . mysql_real_escape_string($_POST['schemeresultunitdp'][$key]) . "')";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					$sqlschememap[$key] = mysql_insert_id();		// Build a mapping of scoring scheme form ids to new database primary keys
				}
				// Insert dependent scoring scheme scores into database
				$query = "INSERT INTO comtem_scorescheme_scores (ss_id, place, score) VALUES ";
				foreach($_POST['score'] as $schemekey => $scoreset)
				{
					foreach($scoreset as $placekey => $score)
					{
						$query .= "('" . mysql_real_escape_string($sqlschememap[$schemekey]) . "', ";
						$query .= "'" . mysql_real_escape_string($placekey) . "', ";
						$query .= "'" . mysql_real_escape_string($score) . "'), ";
					}
				}
				$query = substr($query, 0, strlen($query) - 2);
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				// Insert dependent events into database
				foreach($_POST['eventname'] as $key => $name)
				{
					$query = "INSERT INTO comtem_events (ct_id, name, ss_id, counts_to_limit) VALUES ";
					$query .= "('" . mysql_real_escape_string($_GET['ct_id']) . "', ";
					$query .= "'" . mysql_real_escape_string($name) . "', ";
					$query .= "'" . mysql_real_escape_string($sqlschememap[$_POST['eventscoreschemes'][$key]]) . "', ";
					$query .= "'" . (isset($_POST['eventcountstolimit'][$key]) ? "1" : "0") . "')";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					$sqleventmap[$key] = mysql_insert_id();			// Build a mapping of event form ids to new database primary keys
				}
				// Insert dependent group eligibilities into database
				$query = "INSERT INTO comtem_groupeligibility (sub_id, e_id) VALUES ";
				foreach($_POST['eventsubs'] as $subs)
				{
					foreach($subs as $eventkey => $isub)
					{
						$query .= "('" . mysql_real_escape_string($sqlsubmap[$isub]) . "', '" . mysql_real_escape_string($sqleventmap[$eventkey]) . "'), ";
					}
				}
				$query = substr($query, 0, strlen($query) - 2);
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Done!
				// Log update
				logUserAction("Updated the settings for Competition Template '" . htmlspecialchars($_POST['name']) . "'.");
				// Generate success message
				$message = "<span class=\"successmessage\">Template successfully updated!</span><br>\n";
				// Update page title
				$curpagetitle = "Template Settings - '" . mysql_real_escape_string($_POST['name']) . "'";
			}
		}
		break;
	
	case 'comp_deletetemplate':
		// Prepare form default values
		$del_done = FALSE;				// Whether a deletion has completed or not
		// Load values from database
		$query = "SELECT name FROM compotemplates WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$row = mysql_fetch_array($result);
		// Store values for later use
		$v_name = $row['name'];
		mysql_free_result($result);
		
		// Confirm delete form parsing
		if(isset($_POST['submitted']) && isset($_POST['confirm']))
		{
			// Validate inputs
			$message = "";
			# Does the radio box have a valid value?
			if($_POST['confirm'] != 'Y' && $_POST['confirm'] != 'N') $message = "<span class=\"errormessage\">Invalid choice!</span><br>\n";
			
			// Continue if no errors...
			if($message == "")
			{
				// Which option was picked?
				if($_POST['confirm'] == 'Y')
				{
					// Delete dependent group eligibilities from database
					$query = "DELETE comtem_groupeligibility FROM comtem_groupeligibility, comtem_events";
					$query .= " WHERE comtem_groupeligibility.e_id=comtem_events.e_id AND comtem_events.ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent scoring scheme scores from database
					$query = "DELETE comtem_scorescheme_scores FROM comtem_scorescheme_scores, comtem_scoreschemes";
					$query .= " WHERE comtem_scorescheme_scores.ss_id=comtem_scoreschemes.ss_id AND comtem_scoreschemes.ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent events from database
					$query = "DELETE FROM comtem_events WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent scoring schemes from database
					$query = "DELETE FROM comtem_scoreschemes WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent subgroups from database
					$query = "DELETE FROM comtem_subgroups WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent teams from database
					$query = "DELETE FROM comtem_teams WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete template itself from database
					$query = "DELETE FROM compotemplates WHERE ct_id='" . mysql_real_escape_string($_GET['ct_id']) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					
					// Done!
					// Log deletion
					logUserAction("Deleted the Competition Template '" . htmlspecialchars($v_name) . "'.");
					// Generate success message
					$message = "<span class=\"successmessage\">Template '" . htmlspecialchars($v_name) . "' was successfully deleted!</span><br>\n";
					// Note that a deletion has completed (for display code later)
					$del_done = TRUE;
				} else {
					// Redirect to competition management panel
					redirect('index.php?page=comppanel');
				}
			}
		}
		break;
		
	case 'comp_daysettings':
		// Prepare form default values
		// Load values from database
		$query = "SELECT name, year FROM days WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$row = mysql_fetch_array($result);
		// Store values for later use
		$v_name = $row['name'];
		$v_year = $row['year'];
		mysql_free_result($result);
		
		// Update form parsing
		if(isset($_POST['submitted']) && isset($_POST['name']) && isset($_POST['year']) && isset($_POST['recordimport']))
		{
			// Validate inputs
			$message = "";
			# Validate hidden link data
			if(isset($_POST['cidlink']))
			{
				foreach($_POST['cidlink'] as $linkdata)
				{
					# Is the link target a number?
					if(!is_numeric($linkdata)) $message = "<span class=\"errormessage\">Invalid link data! Please refrain from messing with the hidden inputs! :/</span><br>\n";
				}
			}
			# Validate competitors
			if(isset($_POST['competitorname']))
			{
				foreach($_POST['competitorname'] as $competitorperteam)
				{
					foreach($competitorperteam as $competitorminilist)
					{
						foreach($competitorminilist as $competitor)
						{
							# Was a name enterred?
							if(empty($competitor)) $message = "<span class=\"errormessage\">You must enter a name for the Competitor!</span><br>\n";
							# Was the name within the length limits?
							if(strlen($competitor) > 50)
							{
								$message = "<span class=\"errormessage\">Competitor names cannot be longer than 50 characters! (";
								$message .= htmlspecialchars($competitor) . " is too long)</span><br>\n";
							}
						}
					}
				}
			}
			# Validate team initial scores
			if(isset($_POST['teamscore']))
			{
				foreach($_POST['teamscore'] as $teamscore)
				{
					# Is the score a number?
					if(!is_numeric($teamscore)) $message = "<span class=\"errormessage\">Invalid initial Team score!</span><br>\n";
				}
			} else {
				$message = "<span class=\"errormessage\">There must be at least one Team score box!</span><br>\n";
			}
			# Is value of day to import records from valid? (0 or in database, but not this day's id)
			// If the value isn't 0...
			if($_POST['recordimport'] != 0)
			{
				// Fetch all days from database which are not this day from database
				$query = "SELECT d_id FROM days WHERE d_id<>'" . mysql_real_escape_string($_GET['d_id']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				// Does the day exist in this list?
				$dayexists = false;
				while($row = mysql_fetch_array($result))
				{
					if($row['d_id'] == $_POST['recordimport']) $dayexists = true;
				}
				mysql_free_result($result);
				// Day does not exist, so generate an error message
				if(!$dayexists) $message = "<span class=\"errormessage\">Invalid Day to import Records from!</span><br>\n";
			}
			# Is the year a number?
			if(!is_numeric($_POST['year'])) $message = "<span class=\"errormessage\">Year must be a number!</span><br>\n";
			# Was a name enterred?
			if(empty($_POST['name'])) $message = "<span class=\"errormessage\">You must enter a name for the Day!</span><br>\n";
			# Was the name within the length limits?
			if(strlen($_POST['name']) > 50) $message = "<span class=\"errormessage\">Day name cannot be longer than 50 characters!</span><br>\n";
			
			// Update default form values
			$v_name = $_POST['name'];
			$v_year = $_POST['year'];
			
			// Continue if no errors...
			if($message == "")
			{
				// Update day in database
				$query = "UPDATE days SET name='" . mysql_real_escape_string($_POST['name']) . "', ";
				$query .= "year='" . mysql_real_escape_string($_POST['year']) . "'";
				$query .= " WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Update day teams in database
				foreach($_POST['teamscore'] as $key => $teamscore)
				{
					$query = "UPDATE day_teams SET initscore='" . mysql_real_escape_string($teamscore) . "'";
					$query .= " WHERE t_id='" . mysql_real_escape_string($key) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				}
				
				// Update day competitors in database
				if(isset($_POST['competitorname']))
				{
					foreach($_POST['competitorname'] as $teamkey => $competitorperteam)
					{
						foreach($competitorperteam as $subkey => $competitorminilist)
						{
							foreach($competitorminilist as $cokey => $competitor)
							{
								if(isset($_POST['cidlink'][$cokey]))
								{
									// If the competitor is marked in the form as already existing, update their existing database entry
									$query = "UPDATE day_competitors SET name='" . mysql_real_escape_string($competitor) . "', ";
									$query .= "t_id='" . mysql_real_escape_string($teamkey) . "', ";
									$query .= "sub_id='" . mysql_real_escape_string($subkey) . "'";
									$query .= " WHERE c_id='" . mysql_real_escape_string($_POST['cidlink'][$cokey]) . "'";
								} else {
									// If the competitor does not have a marker in the form for already existing, create a new database entry for them
									$query = "INSERT INTO day_competitors (d_id, name, t_id, sub_id)";
									$query .= " VALUES ('" . mysql_real_escape_string($_GET['d_id']) . "', ";
									$query .= "'" . mysql_real_escape_string($competitor) . "', ";
									$query .= "'" . mysql_real_escape_string($teamkey) . "', ";
									$query .= "'" . mysql_real_escape_string($subkey) . "')";
								}
								$result = @mysql_query($query);
								if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
							}
						}
					}
				}
				
				// Delete existing day competitors that don't feature in new forms
				if(isset($_POST['cidlink']) && isset($_POST['competitorname']))
				{
					foreach($_POST['cidlink'] as $linkkey => $linkdata)
					{
						$competitor_not_deleted = false;
						foreach($_POST['competitorname'] as $competitorperteam)
						{
							foreach($competitorperteam as $competitorminilist)
							{
								foreach($competitorminilist as $cokey => $competitor)
								{
									if($linkkey == $cokey) $competitor_not_deleted = true;
								}
							}
						}
						if($competitor_not_deleted == false)
						{
							$query = "DELETE FROM day_competitors WHERE c_id='" . mysql_real_escape_string($linkdata) . "' LIMIT 1";
							$result = @mysql_query($query);
							if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
						}
					}
				}
				
				// Update records if required
				if($_POST['recordimport'] != 0)
				{
					// Fetch list of broken records for old day
					$newrecords = get_newrecords($_POST['recordimport']);
					
					// Fetch year of old day from database
					$query = "SELECT year FROM days WHERE d_id='" . mysql_real_escape_string($_POST['recordimport']) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					$row = mysql_fetch_array($result);
					$oldday_year = $row['year'];
					mysql_free_result($result);
					
					// Fetch base records for old day from database
					$query = "SELECT day_records.e_id AS event_id, day_events.name AS event_name, day_records.name_competitor AS name_competitor,";
					$query .= " day_records.name_team AS name_team, day_records.result AS result, day_records.yearset AS yearset";
					$query .= " FROM day_records, day_events";
					$query .= " WHERE day_records.d_id='" . mysql_real_escape_string($_POST['recordimport']) . "'";
					$query .= " AND day_records.e_id=day_events.e_id";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					while($row = mysql_fetch_array($result))
					{
						// Load data for this record
						$record_event_id = $row['event_id'];
						$record_event_name = $row['event_name'];
						$record_competitor_name = $row['name_competitor'];
						$record_team_name = $row['name_team'];
						$record_result = $row['result'];
						$record_yearset = $row['yearset'];
						
						// Update data for this record if this record was broken on the old day
						foreach($newrecords as $recorddata)
						{
							if($recorddata['event_id'] == $record_event_id)
							{
								// Is this record better than the last one, or simply equal to it?
								if($recorddata['score'] == $record_result)
								{
									// Equal to, so append the record
									$record_competitor_name .= ' / ' . $recorddata['competitor_name'];
									$record_team_name .= ' / ' . $recorddata['team_name'];
									$record_result = $recorddata['score'];
									$record_yearset = $oldday_year;
								} else {
									// Better than, so replace the record
									$record_competitor_name = $recorddata['competitor_name'];
									$record_team_name = $recorddata['team_name'];
									$record_result = $recorddata['score'];
									$record_yearset = $oldday_year;
								}
							}
						}
						
						// Edge-case validation check...
						// It's very rare and practically impossible, but if a record is tied then the
						// names of competitors and teams could overflow the 50-character limit imposed
						// on those fields; so, if that happens, we shall truncate the string and place
						// ellipsis on the end
						if(strlen($record_competitor_name) > 50) $record_competitor_name = substr($record_competitor_name, 0, 47) . '...';
						if(strlen($record_team_name) > 50) $record_team_name = substr($record_team_name, 0, 47) . '...';
						
						// Find event on new day with same name as old day's event in database
						$query2 = "SELECT e_id FROM day_events WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
						$query2 .= " AND name='" . mysql_real_escape_string($record_event_name) . "' LIMIT 1";
						$result2 = @mysql_query($query2);
						if(!$result2) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
						if(mysql_num_rows($result2) == 1)
						{
							// Event exists
							$row2 = mysql_fetch_array($result2);
							// Update record for new day in database
							$query3 = "UPDATE day_records SET name_competitor='" . mysql_real_escape_string($record_competitor_name) . "',";
							$query3 .= " name_team='" . mysql_real_escape_string($record_team_name) . "',";
							$query3 .= " result='" . mysql_real_escape_string($record_result) . "',";
							$query3 .= " yearset='" . mysql_real_escape_string($record_yearset) . "'";
							$query3 .= " WHERE e_id='" . mysql_real_escape_string($row2['e_id']) . "' LIMIT 1";
							$result3 = @mysql_query($query3);
							if(!$result3) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
						} else {
							// Event does not exist, so move on to next record
						}
						mysql_free_result($result2);
					}
					mysql_free_result($result);
				}
				
				// Done!
				// Log update
				logUserAction("Updated the settings for Day '" . htmlspecialchars($_POST['name']) . "'.");
				// Generate success message
				$message = "<span class=\"successmessage\">Day successfully updated!</span><br>\n";
				// Update page title
				$curpagetitle = "Day Settings - '" . htmlspecialchars($_POST['name']) . "'";
			}
		}
		break;
		
	case 'comp_adjustday':
		// Adjustment form parsing
		if(isset($_POST['submitted']) && isset($_POST['teamname']) && isset($_POST['subname']) && isset($_POST['schemename']) && isset($_POST['schemeresultorder']) && isset($_POST['schemeresulttype']) && isset($_POST['schemeresultunit']) && isset($_POST['schemeresultunitdp']) && isset($_POST['score']) && isset($_POST['eventname']))
		{
			// Validate inputs
			$message = "";
			# Validate events
			foreach($_POST['eventname'] as $name)
			{
				# Was a name enterred?
				if(empty($name)) $message = "<span class=\"errormessage\">Event names cannot be blank!</span><br>\n";
				# Was the name within the length limits?
				if(strlen($name) > 50) $message = "<span class=\"errormessage\">Event names cannot be longer than 50 characters!</span><br>\n";
			}
			# Validate scoring scheme scores
			foreach($_POST['score'] as $score)
			{
				# Is the score a number?
				if(!is_numeric($score)) $message = "<span class=\"errormessage\">Scores must be a number!</span><br>\n";
			}
			# Validate scoring schemes
			foreach($_POST['schemeresultunitdp'] as $unitdp)
			{
				# Is the decimal place count within bounds?
				if($unitdp < 1 || $unitdp > 6) $message = "<span class=\"errormessage\">Result unit decimal place count must be between 1 and 6 inclusive!</span><br>\n";
				# Is the decimal place count a number?
				if(!is_numeric($unitdp)) $message = "<span class=\"errormessage\">Result unit decimal place count must be a number!</span><br>\n";
			}
			foreach($_POST['schemeresultunit'] as $unit)
			{
				# Was a unit enterred?
				if(empty($unit)) $message = "<span class=\"errormessage\">Result units cannot be blank!</span><br>\n";
				# Was the unit within the length limits?
				if(strlen($unit) > 20) $message = "<span class=\"errormessage\">Result units cannot be longer than 50 characters!</span><br>\n";
			}
			foreach($_POST['schemeresulttype'] as $type)
			{
				# Was a type enterred?
				if(empty($type)) $message = "<span class=\"errormessage\">Result types cannot be blank!</span><br>\n";
				# Was the type within the length limits?
				if(strlen($type) > 30) $message = "<span class=\"errormessage\">Result types cannot be longer than 30 characters!</span><br>\n";
			}
			foreach($_POST['schemeresultorder'] as $order)
			{
				# Is the result order valid?
				if($order != "asc" && $order != "desc") $message = "<span class=\"errormessage\">Invalid result order!</span><br>\n";
			}
			foreach($_POST['schemename'] as $name)
			{
				# Was a name enterred?
				if(empty($name)) $message = "<span class=\"errormessage\">Scoring Scheme names cannot be blank!</span><br>\n";
				# Was the name within the length limits?
				if(strlen($name) > 50) $message = "<span class=\"errormessage\">Scoring Scheme names cannot be longer than 50 characters!</span><br>\n";
			}
			# Validate subgroups
			foreach($_POST['subname'] as $name)
			{
				# Was a name enterred?
				if(empty($name)) $message = "<span class=\"errormessage\">Subgroup names cannot be blank!</span><br>\n";
				# Was the name within the length limits?
				if(strlen($name) > 50) $message = "<span class=\"errormessage\">Subgroup names cannot be longer than 50 characters!</span><br>\n";
			}
			# Validate teams
			foreach($_POST['teamname'] as $name)
			{
				# Was a name enterred?
				if(empty($name)) $message = "<span class=\"errormessage\">Team names cannot be blank!</span><br>\n";
				# Was the name within the length limits?
				if(strlen($name) > 50) $message = "<span class=\"errormessage\">Team names cannot be longer than 50 characters!</span><br>\n";
			}
			
			// Continue if no errors...
			if($message == "")
			{
				// Update Day components in database
				// Update teams
				foreach($_POST['teamname'] as $key => $name)
				{
					$query = "UPDATE day_teams SET name='" . mysql_real_escape_string($name) . "' WHERE t_id='";
					$query .= mysql_real_escape_string($key) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				}
				// Update subgroups
				foreach($_POST['subname'] as $key => $name)
				{
					$query = "UPDATE day_subgroups SET name='" . mysql_real_escape_string($name) . "' WHERE sub_id='";
					$query .= mysql_real_escape_string($key) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				}
				// Update scoring schemes
				foreach($_POST['schemename'] as $key => $name)
				{
					$query = "UPDATE day_scoreschemes SET name='" . mysql_real_escape_string($name) . "' WHERE ss_id='";
					$query .= mysql_real_escape_string($key) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				}
				foreach($_POST['schemeresultorder'] as $key => $order)
				{
					$query = "UPDATE day_scoreschemes SET result_order='" . mysql_real_escape_string($order) . "' WHERE ss_id='";
					$query .= mysql_real_escape_string($key) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				}
				foreach($_POST['schemeresulttype'] as $key => $type)
				{
					$query = "UPDATE day_scoreschemes SET result_type='" . mysql_real_escape_string($type) . "' WHERE ss_id='";
					$query .= mysql_real_escape_string($key) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				}
				foreach($_POST['schemeresultunit'] as $key => $unit)
				{
					$query = "UPDATE day_scoreschemes SET result_units='" . mysql_real_escape_string($unit) . "' WHERE ss_id='";
					$query .= mysql_real_escape_string($key) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				}
				foreach($_POST['schemeresultunitdp'] as $key => $unitdp)
				{
					$query = "UPDATE day_scoreschemes SET result_units_dp='" . mysql_real_escape_string($unitdp) . "' WHERE ss_id='";
					$query .= mysql_real_escape_string($key) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				}
				// Update scoring scheme scores
				foreach($_POST['score'] as $key => $score)
				{
					$query = "UPDATE day_scorescheme_scores SET score='" . mysql_real_escape_string($score) . "' WHERE sssc_id='";
					$query .= mysql_real_escape_string($key) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				}
				// Update events
				foreach($_POST['eventname'] as $key => $name)
				{
					$query = "UPDATE day_events SET name='" . mysql_real_escape_string($name) . "' WHERE e_id='";
					$query .= mysql_real_escape_string($key) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				}
				$query = "UPDATE day_events SET counts_to_limit='0' WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				foreach($_POST['eventcountstolimit'] as $key => $ctl)
				{
					$query = "UPDATE day_events SET counts_to_limit='1' WHERE e_id='" . mysql_real_escape_string($key) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				}
				
				// Done!
				// Log update
				logUserAction("Adjusted components in Day '" . htmlspecialchars($_POST['name']) . "'.");
				// Generate success message
				$message = "<span class=\"successmessage\">Template successfully updated!</span><br>\n";
			}
		}
		break;
		
	case 'comp_deleteday':
		// Prepare form default values
		$del_done = FALSE;				// Whether a deletion has completed or not
		// Load values from database
		$query = "SELECT name FROM days WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "' LIMIT 1";
		$result = @mysql_query($query);
		if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
		$row = mysql_fetch_array($result);
		// Store values for later use
		$v_name = $row['name'];
		mysql_free_result($result);
		
		// Confirm delete form parsing
		if(isset($_POST['submitted']) && isset($_POST['confirm']))
		{
			// Validate inputs
			$message = "";
			# Does the radio box have a valid value?
			if($_POST['confirm'] != 'Y' && $_POST['confirm'] != 'N') $message = "<span class=\"errormessage\">Invalid choice!</span><br>\n";
			
			// Continue if no errors...
			if($message == "")
			{
				// Which option was picked?
				if($_POST['confirm'] == 'Y')
				{
					// Delete associated records from database
					$query = "DELETE FROM day_records WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent group eligibilities from database
					$query = "DELETE day_groupeligibility FROM day_groupeligibility, day_events";
					$query .= " WHERE day_groupeligibility.e_id=day_events.e_id AND day_events.d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent scoring scheme scores from database
					$query = "DELETE day_scorescheme_scores FROM day_scorescheme_scores, day_scoreschemes";
					$query .= " WHERE day_scorescheme_scores.ss_id=day_scoreschemes.ss_id AND day_scoreschemes.d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent events from database
					$query = "DELETE FROM day_events WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent scoring schemes from database
					$query = "DELETE FROM day_scoreschemes WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent subgroups from database
					$query = "DELETE FROM day_subgroups WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent teams from database
					$query = "DELETE FROM day_teams WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent competitors from database
					$query = "DELETE FROM day_competitors WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent records from database
					$query = "DELETE FROM day_records WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete dependent scores from database
					$query = "DELETE FROM day_scores WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					// Delete day itself from database
					$query = "DELETE FROM days WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "' LIMIT 1";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					
					// Done!
					// Log success
					logUserAction("Deleted the Day '" . htmlspecialchars($v_name) . "'.");
					// Generate success message
					$message = "<span class=\"successmessage\">Day '" . htmlspecialchars($v_name) . "' was successfully deleted!</span><br>\n";
					// Note that a deletion has completed (for display code later)
					$del_done = TRUE;
				} else {
					// Redirect to competition management panel
					redirect('index.php?page=comppanel');
				}
			}
		}
		break;
		
	case 'comp_managerecords':
		// Update record form parsing
		if(isset($_POST['submitted']) && isset($_POST['event']) && isset($_POST['name']) && isset($_POST['team']) && isset($_POST['score']) && isset($_POST['yearset']))
		{
			// Validate inputs
			$message = "";
			# Is the year numeric?
			if(!is_numeric($_POST['yearset'])) $message = "<span class=\"errormessage\">Year must be a number!</span><br>\n";
			# Is the score numeric?
			if(!is_numeric($_POST['score'])) $message = "<span class=\"errormessage\">Score must be a number!</span><br>\n";
			# Was a team name enterred?
			if(empty($_POST['team'])) $message = "<span class=\"errormessage\">You must enter a Team name for the Record!</span><br>\n";
			# Was the team name within the length limits?
			if(strlen($_POST['team']) > 50) $message = "<span class=\"errormessage\">Team name cannot be longer than 50 characters!</span><br>\n";
			# Was a competitor name enterred?
			if(empty($_POST['name'])) $message = "<span class=\"errormessage\">You must enter a Competitor name for the Record!</span><br>\n";
			# Was the competitor name within the length limits?
			if(strlen($_POST['name']) > 50) $message = "<span class=\"errormessage\">Competitor name cannot be longer than 50 characters!</span><br>\n";
			# Is the event id numeric?
			if(!is_numeric($_POST['event'])) $message = "<span class=\"errormessage\">Invalid Event!</span><br>\n";
			
			// Continue if no errors...
			if($message == "")
			{
				// Update record in database
				$query = "UPDATE day_records SET name_competitor='" . mysql_real_escape_string($_POST['name']) . "', ";
				$query .= "name_team='" . mysql_real_escape_string($_POST['team']) . "', ";
				$query .= "result='" . mysql_real_escape_string($_POST['score']) . "', ";
				$query .= "yearset='" . mysql_real_escape_string($_POST['yearset']) . "' WHERE e_id='" . mysql_real_escape_string($_POST['event']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Done!
				// Log success
				logUserAction("Updated the Record for Event with database ID: " . htmlspecialchars($_POST['event']) . ".");
				// Generate success message
				$message = "<span class=\"successmessage\">Record successfully updated!</span><br>\n";
			}
		}
		break;
		
	case 'scoreentry':
		// Nothing to do
		break;
		
	case 'scoreentry_editscore':
		// Update scores form parsing
		if(isset($_POST['submitted']) && isset($_POST['position']) && isset($_POST['competitor']) && isset($_POST['result']))
		{
			// Create new competitors if needed
			$newcompetitorcount = 0;
			foreach($_POST['competitor'] as $teamkey => $scoredata)
			{
				foreach($scoredata as $scorekey => $scorecompetitor)
				{
					// Has a new competitor been requested?
					if($scorecompetitor == -1)
					{
						// Have the appropriate fields been filled in?
						if(isset($_POST['newcomp_name'][$teamkey][$scorekey]) && isset($_POST['newcomp_group'][$teamkey][$scorekey]))
						{
							// Validate name and subgroup; set competitor to none selected if any part is invalid
							# Is the subgroup id numeric?
							if(!is_numeric($_POST['newcomp_group'][$teamkey][$scorekey])) $_POST['competitor'][$teamkey][$scorekey] = 0;
							# Was a name enterred?
							if(empty($_POST['newcomp_name'][$teamkey][$scorekey])) $_POST['competitor'][$teamkey][$scorekey] = 0;
							# Was the name within the length limits?
							if(strlen($_POST['newcomp_name'][$teamkey][$scorekey]) > 50) $_POST['competitor'][$teamkey][$scorekey] = 0;
							
							// Proceed if validation succeeded
							if($_POST['competitor'][$teamkey][$scorekey] != 0)
							{
								// Create the new competitor in the database
								$query = "INSERT INTO day_competitors (d_id, name, t_id, sub_id) VALUES";
								$query .= " ('" . mysql_real_escape_string($curactivedayid) . "', ";
								$query .= "'" . mysql_real_escape_string($_POST['newcomp_name'][$teamkey][$scorekey]) . "', ";
								$query .= "'" . mysql_real_escape_string($teamkey) . "', ";
								$query .= "'" . mysql_real_escape_string($_POST['newcomp_group'][$teamkey][$scorekey]) . "')";
								$result = @mysql_query($query);
								if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
								
								// Change this competitor to the id of the newly inserted competitor
								$_POST['competitor'][$teamkey][$scorekey] = mysql_insert_id();
								
								// Increment new competitor count
								$newcompetitorcount++;
							}
						} else {
							// Change this competitor to none selected
							$_POST['competitor'][$teamkey][$scorekey] = 0;
						}
					}
				}
			}
			// Log any newly added competitors
			if($newcompetitorcount > 0) logUserAction("Added $newcompetitorcount new Competitors to Day '" . htmlspecialchars($curpageheadline) . "' while scoring.");
			
			// Validate inputs
			$message = "";
			# Validate results
			foreach($_POST['result'] as $teamkey => $scoredata)
			{
				foreach($scoredata as $scorekey => $scoreresult)
				{
					# If the score is blank, is the position DNC?
					if($scoreresult == '')
					{
						if(!(isset($_POST['position'][$teamkey][$scorekey]) && $_POST['position'][$teamkey][$scorekey] == 0))
						{
							$message = "<span class=\"errormessage\">Result must be a number!</span><br>\n";
						}
					} else {
						# Is the result a number?
						if(!is_numeric($scoreresult)) $message = "<span class=\"errormessage\">Result must be a number!</span><br>\n";
					}
				}
			}
			# Validate competitors
			foreach($_POST['competitor'] as $teamkey => $scoredata)
			{
				foreach($scoredata as $scorekey => $scorecompetitor)
				{
					# Is the competitor id a number?
					if(!is_numeric($scorecompetitor)) $message = "<span class=\"errormessage\">Invalid Competitor!</span><br>\n";
					# If the competitor is blank, is the position DNC?
					if($scorecompetitor == 0)
					{
						if(!(isset($_POST['position'][$teamkey][$scorekey]) && $_POST['position'][$teamkey][$scorekey] == 0))
						{
							$message = "<span class=\"errormessage\">Invalid Competitor!</span><br>\n";
						}
					}
				}
			}
			# Validate positions
			foreach($_POST['position'] as $scoredata)
			{
				foreach($scoredata as $scoreposition)
				{
					# Is the position a number?
					if(!is_numeric($scoreposition)) $message = "<span class=\"errormessage\">Invalid Position!</span><br>\n";
				}
			}
			
			// Continue if no errors...
			if($message == "")
			{
				// Delete old score data for this event from database
				$query = "DELETE FROM day_scores WHERE e_id='" . mysql_real_escape_string($_GET['e_id']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Get data about the event from database
				$query = "SELECT d_id, name, ss_id FROM day_events WHERE e_id='" . mysql_real_escape_string($_GET['e_id']) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$row = mysql_fetch_array($result);
				$event_dayid = $row['d_id'];
				$event_name = $row['name'];
				$event_ssid = $row['ss_id'];
				mysql_free_result($result);
				
				// Calculate points value for each rank
				// 1) Fetch scoring scheme for this event from database
				$query = "SELECT place, score FROM day_scorescheme_scores WHERE ss_id='" . mysql_real_escape_string($event_ssid) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				while($row = mysql_fetch_array($result))
				{
					$rankdefaultworth[$row['place']] = $row['score'];
					$ranknewworth[$row['place']] = $rankdefaultworth[$row['place']];
					$rankcount[$row['place']] = 0;
				}
				mysql_free_result($result);
				// 2) Count number of occurrences of each position in event
				foreach($_POST['position'] as $scoredata)
				{
					foreach($scoredata as $scoreposition)
					{
						$rankcount[$scoreposition]++;
					}
				}
				// 3) Calculate correctly balanced scores for each position based on how many
				//    competitors came in that position, so that scores are tied fairly but
				//    total points awarded is constant
				foreach($ranknewworth as $rankkey => $rankworth)
				{
					if($rankkey != 0)
					{
						$ranknewworth[$rankkey] = 0;
						for($i = $rankkey; $i < $rankkey + $rankcount[$rankkey]; $i++)
						{
							if(isset($rankdefaultworth[$i])) $ranknewworth[$rankkey] += $rankdefaultworth[$i];
						}
						if($rankcount[$rankkey] != 0) $ranknewworth[$rankkey] /= $rankcount[$rankkey];
					}
				}
				
				// Insert new score data for this event into database
				$query = "INSERT INTO day_scores (d_id, e_id, c_id, t_id, place, result, worth) VALUES";
				foreach($_POST['position'] as $teamkey => $scoredata)
				{
					foreach($scoredata as $scorekey => $scoreposition)
					{
						$query .= " ('" . mysql_real_escape_string($event_dayid) . "', ";
						$query .= "'" . mysql_real_escape_string($_GET['e_id']) . "', ";
						$query .= "'" . mysql_real_escape_string($_POST['competitor'][$teamkey][$scorekey]) . "', ";
						$query .= "'" . mysql_real_escape_string($teamkey) . "', ";
						$query .= "'" . mysql_real_escape_string($_POST['position'][$teamkey][$scorekey]) . "', ";
						if($_POST['position'][$teamkey][$scorekey] == 0) $_POST['result'][$teamkey][$scorekey] = 0;
						$query .= "'" . mysql_real_escape_string($_POST['result'][$teamkey][$scorekey]) . "', ";
						$query .= "'" . mysql_real_escape_string($ranknewworth[$_POST['position'][$teamkey][$scorekey]]) . "'), ";
					}
				}
				$query = substr($query, 0, strlen($query) - 2);
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				
				// Done!
				// Log update
				logUserAction("Updated the Scores for Event '" . htmlspecialchars($event_name) . "' on Day '" . htmlspecialchars($curpageheadline) . "'.");
				// Genereate success message
				$message = "<span class=\"successmessage\">Scores for '" . htmlspecialchars($event_name) . "' were successfully updated!</span><br>\n";
				
				// Trigger revalidation of scores
				validate_scores($_GET['e_id']);
			}
		}
		break;
		
	case 'scoreviewer':
		// Nothing to do
		break;
		
	case 'statspanel':
		// Nothing to do
		break;
		
	case 'stats_days':
		// Generate analysis table form parsing
		if(isset($_POST['submitted']) && isset($_POST['days']) && isset($_POST['outputmode']))
		{
			// Produce array of all valid days to process
			$day_list = array();
			foreach($_POST['days'] as $dayid)
			{
				// If day exists, add it to array
				// Preserves order; does not remove duplicates
				$query = "SELECT d_id FROM days WHERE d_id='" . mysql_real_escape_string($dayid) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				if(mysql_num_rows($result) == 1)
				{
					$day_list[] = $dayid;
				}
				mysql_free_result($result);
			}
			
			// Prepare HTML output
			$v_statdata = "<table class=\"datatable bordered\">\n<tr class=\"darkgrey\">\n<th>&nbsp;</th>\n";
			// Output names of days
			foreach($day_list as $dayid)
			{
				$query = "SELECT name FROM days WHERE d_id='" . mysql_real_escape_string($dayid) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$row = mysql_fetch_array($result);
				$v_statdata .= "<th>" . htmlspecialchars($row['name']) . "</th>\n";
				mysql_free_result($result);
			}
			$v_statdata .= "</tr>\n<tr class=\"darkgrey\">\n<th colspan=\"". (count($day_list) + 1) . "\"><i>Summary Statistics:</i></th>\n</tr>\n";
			$v_statdata .= "<tr class=\"lightgrey\">\n<th>Final Team Scores</th>\n";
			// Output final team scores
			foreach($day_list as $dayid)
			{
				// Fetch data array
				$totalscores = get_currenttotalscores($dayid);
				
				// Output score data
				$v_statdata .= "<td>";
				foreach($totalscores as $data)
				{
					$v_statdata .= "<b>";
					if($data['tied']) $v_statdata .= "=";
					$v_statdata .= $data['place'] . "<sup>" . getNumberSuffix($data['place']) . "</sup>:</b> ";
					$v_statdata .= htmlspecialchars($data['teamname']) . " - ";
					$v_statdata .= truncate_number($data['final_score']) . "<br>";
				}
				$v_statdata .= "</td>\n";
			}
			$v_statdata .= "</tr>\n<tr class=\"lightgrey\">\n<th>Events Won per Team</th>\n";
			// Output events won per team
			foreach($day_list as $dayid)
			{
				// Fetch data array
				$eventwincount = get_totalteampositions($dayid);
				
				// Output win data
				$v_statdata .= "<td>";
				foreach($eventwincount as $t_id => $windata)
				{
					$v_statdata .= "<b>" . htmlspecialchars($windata['name']) . "</b><br>";
					foreach($windata['position'] as $position => $total)
					{
						$v_statdata .= "&nbsp;&nbsp;&nbsp;" . htmlspecialchars($position) . "<sup>" . getNumberSuffix($position) . "</sup>: " . htmlspecialchars($total);
					}
					$v_statdata .= "<br>";
				}
				$v_statdata .= "</td>\n";
			}
			$v_statdata .= "</tr>\n<tr class=\"lightgrey\">\n<th>Highest Individual Scores per Subgroup</th>\n";
			// Output highest individual scores per subgroup
			foreach($day_list as $dayid)
			{
				// Fetch data array
				$highscores = get_individualhighscores($dayid);
				
				// Output high score data
				$v_statdata .= "<td>";
				foreach($highscores as $subname => $data)
				{
					$v_statdata .= "<b>" . htmlspecialchars($data['subname']) . "</b><br>";
					if(isset($data['name']) && isset($data['team']))
					{
						foreach($data['name'] as $key => $namedata)
						{
							$v_statdata .= "&nbsp;&nbsp;&nbsp;" . htmlspecialchars($data['name'][$key]);
							$v_statdata .= " (" . htmlspecialchars($data['team'][$key]) . ")";
							$v_statdata .= " - " . htmlspecialchars(truncate_number($data['highscore'])) . " points<br>";
						}
					}
				}
				$v_statdata .= "</td>\n";
			}
			$v_statdata .= "</tr>\n<tr class=\"lightgrey\">\n<th>Records Broken</th>\n";
			// Output records broken
			foreach($day_list as $dayid)
			{
				// Fetch data array
				$newrecords = get_newrecords($dayid);
				
				// Output record data
				$v_statdata .= "<td>";
				if(count($newrecords) == 0)
				{
					$v_statdata .= "<b>No Records were broken.</b><br>";
				} elseif(count($newrecords) == 1) {
					$v_statdata .= "<b>1 Record broken!</b><br>";
				} else {
					$v_statdata .= "<b>" . count($newrecords) . " Records broken!</b><br>";
				}
				$v_statdata .= "<ul>";
				foreach($newrecords as $data)
				{
					$v_statdata .= "<li>" . htmlspecialchars($data['competitor_name']);
					$v_statdata .= " (" . htmlspecialchars($data['team_name']) . ") - ";
					if($data['score_units'] == "seconds" && $data['score'] >= 60)
					{
						$v_statdata .= htmlspecialchars(convertToMinutes($data['score'], $data['score_units_dp'])) . " minutes, ";
					} else {
						$v_statdata .= htmlspecialchars(truncate_number($data['score'], $data['score_units_dp'])) . " " . htmlspecialchars($data['score_units']) . ", ";
					}
					$v_statdata .= htmlspecialchars($data['event_name']) . "</li>";
				}
				$v_statdata .= "</ul></td>\n";
			}
			$v_statdata .= "</tr>\n<tr class=\"darkgrey\">\n<th colspan=\"" . (count($day_list) + 1) . "\"><i>Event Statistics:</i></th>\n</tr>\n";
			// Generate array of all events for all days, linking similarly named events
			$combinedeventlist = array();
			foreach($day_list as $dayid)
			{
				$query = "SELECT e_id, name FROM day_events WHERE d_id='" . mysql_real_escape_string($dayid) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				while($row = mysql_fetch_array($result))
				{
					$combinedeventlist[$row['name']][$dayid] = $row['e_id'];
				}
				mysql_free_result($result);
			}
			// Output all event rows
			foreach($combinedeventlist as $eventname => $idlist)
			{
				$v_statdata .= "<tr class=\"lightgrey\">\n<th>" . htmlspecialchars($eventname) ."</th>\n";
				// Output event totals per team
				foreach($day_list as $dayid)
				{
					// Open cell
					$v_statdata .= "<td>";
					
					// Does this day have this event?
					if(isset($idlist[$dayid]))
					{
						// Yes
						// Fetch data array
						$teamresults = get_event_teamresults($idlist[$dayid]);
						
						// Output record data
						foreach($teamresults as $place => $data)
						{
							$v_statdata .= "<b>";
							if($data['tiedposition'] != "") $v_statdata .= "=";
							$v_statdata .= $data['finalposition'] . "<sup>" . getNumberSuffix($data['finalposition']) . "</sup>:</b> ";
							$v_statdata .= htmlspecialchars($data['name']) . " - ";
							$v_statdata .= htmlspecialchars($data['score']) . "<br>";
						}
					} else {
						// No
						$v_statdata .= "-----";
					}
					
					// Close cell
					$v_statdata .= "</td>\n";
				}
				$v_statdata .= "</tr>\n";
			}
			$v_statdata .= "</table>";
			
			// Do we want to produce a Word document?
			if($_POST['outputmode'] == "document")
			{
				// Generate Word document
				header("Content-type: application/vnd.ms-word");									// MS Word Document header
				header("Content-Disposition: attachment; Filename=analysis_days.doc");				// Attachment settings
				echo "<html>\n<head>\n";															// Begin document
				echo "<style type=\"text/css\">\n";													// Style sheet
				include("statsdoc.css");
				echo "\n</style>\n";
				echo "<meta http-equiv=\"Content-type\" content=\"text/html;charset=UTF-8\">\n";	// Character set and content type
				echo "</head>\n<body>\n";
				echo $v_statdata;																	// Output prepared statistics data
				echo "\n</body>\n</html>";															// Finish document
				exit();																				// Do not generate site
			}
			
			// Do we want to produce an Excel spreadsheet?
			if($_POST['outputmode'] == "spreadsheet")
			{
				// Generate Word document
				header("Content-type: application/vnd.ms-excel");									// MS Word Document header
				header("Content-Disposition: attachment; Filename=analysis_days.xls");				// Attachment settings
				echo "<html>\n<head>\n";															// Begin document
				echo "<style type=\"text/css\">\n";													// Style sheet
				include("statsdoc.css");
				echo "\n</style>\n";
				echo "<meta http-equiv=\"Content-type\" content=\"text/html;charset=UTF-8\">\n";	// Character set and content type
				echo "</head>\n<body>\n";
				echo $v_statdata;																	// Output prepared statistics data
				echo "\n</body>\n</html>";															// Finish document
				exit();																				// Do not generate site
			}
		}
		break;
		
	case 'stats_events':
		if(isset($_POST['submitted']) && isset($_POST['days']) && isset($_POST['events']) && isset($_POST['outputmode']))
		{
			// Produce array of all valid events to process
			$event_list = array();
			foreach($_POST['events'] as $key => $eventid)
			{
				// If event exists, add it to array
				// Preserves order; does not remove duplicates
				$query = "SELECT e_id FROM day_events WHERE e_id='" . mysql_real_escape_string($eventid) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				if(mysql_num_rows($result) == 1)
				{
					$event_list[] = array('dayid' => $_POST['days'][$key], 'eventid' => $eventid);
				}
				mysql_free_result($result);
			}
			
			// Prepare HTML output
			$v_statdata = "<table class=\"datatable bordered\">\n<tr class=\"darkgrey\">\n<th>&nbsp;</th>\n";
			// Output names of events
			foreach($event_list as $eventdata)
			{
				$query = "SELECT days.name AS day_name, day_events.name AS event_name FROM days, day_events";
				$query .= " WHERE days.d_id=day_events.d_id AND day_events.e_id='" . mysql_real_escape_string($eventdata['eventid']) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$row = mysql_fetch_array($result);
				$v_statdata .= "<th>" . htmlspecialchars($row['day_name']) . " - " . htmlspecialchars($row['event_name']) . "</th>\n";
				mysql_free_result($result);
			}
			$v_statdata .= "</tr>\n<tr class=\"darkgrey\">\n<th colspan=\"". (count($event_list) + 1) . "\"><i>Summary Statistics:</i></th>\n</tr>\n";
			$v_statdata .= "<tr class=\"lightgrey\">\n<th>Final Team Scores</th>\n";
			// Output event totals per team
			foreach($event_list as $eventdata)
			{
				// Fetch data array
				$teamresults = get_event_teamresults($eventdata['eventid']);
				
				// Output record data
				$v_statdata .= "<td>";
				foreach($teamresults as $place => $data)
				{
					$v_statdata .= "<b>";
					if($data['tiedposition'] != "") $v_statdata .= "=";
					$v_statdata .= $data['finalposition'] . "<sup>" . getNumberSuffix($data['finalposition']) . "</sup>:</b> ";
					$v_statdata .= htmlspecialchars($data['name']) . " - ";
					$v_statdata .= htmlspecialchars($data['score']) . "<br>";
				}
				$v_statdata .= "</td>\n";
			}
			$v_statdata .= "</tr>\n<tr class=\"lightgrey\">\n<th>Event Results</th>\n";
			// Output individual results per competitor
			foreach($event_list as $eventdata)
			{
				// Fetch data array
				$individualresults = get_event_individualresults($eventdata['eventid']);
				
				// Output result data
				$v_statdata .= "<td>";
				foreach($individualresults as $resultdata)
				{
					$v_statdata .= "<b>";
					if($resultdata['tied']) $v_statdata .= "=";
					$v_statdata .= htmlspecialchars($resultdata['place']);
					if(is_numeric($resultdata['place'])) $v_statdata .= "<sup>" . htmlspecialchars(getNumberSuffix($resultdata['place'])) . "</sup>";
					$v_statdata .= ": </b>" . htmlspecialchars($resultdata['name']) . " (" . htmlspecialchars($resultdata['team_name']) . ")<br>";
					if($resultdata['units'] == "seconds" && $resultdata['result'] >= 60)
					{
						$v_statdata .= "&nbsp;&nbsp;&nbsp;" . htmlspecialchars(convertToMinutes($resultdata['result'], $resultdata['units_dp'])) . " minutes<br>";
					} else {
						$v_statdata .= "&nbsp;&nbsp;&nbsp;" . htmlspecialchars(truncate_number($resultdata['result'], $resultdata['units_dp'])) . " " . htmlspecialchars($resultdata['units']) . "<br>";
					}
				}
				$v_statdata .= "</td>\n";
			}
			$v_statdata .= "</tr>\n<tr class=\"lightgrey\">\n<th>Event Record</th>\n";
			// Output record for each event
			foreach($event_list as $eventdata)
			{
				// Fetch data array
				$currentrecord = get_currentrecord($eventdata['eventid']);
				
				// Output record data
				$v_statdata .= "<td>";
				if($currentrecord['brokenthisday']) $v_statdata .= "<b>New Record!</b><br>"; else $v_statdata .= "<b>Old Record not broken.</b><br>";
					$v_statdata .= htmlspecialchars($currentrecord['competitor_name']) . " (" . htmlspecialchars($currentrecord['team_name']) . ")<br>";
					if($currentrecord['score_units'] == "seconds" && $currentrecord['score'] >= 60)
					{
						$v_statdata .= htmlspecialchars(convertToMinutes($currentrecord['score'], $currentrecord['score_units_dp'])) . " minutes<br>";
					} else {
						$v_statdata .= htmlspecialchars(truncate_number($currentrecord['score'], $currentrecord['score_units_dp'])) . " " . htmlspecialchars($currentrecord['score_units']) . "<br>";
					}
					$v_statdata .= htmlspecialchars($currentrecord['yearset']);
				$v_statdata .= "</td>\n";
			}
			$v_statdata .= "</tr>\n";
			$v_statdata .= "</table>";
			
			// Do we want to produce a Word document?
			if($_POST['outputmode'] == "document")
			{
				// Generate Word document
				header("Content-type: application/vnd.ms-word");									// MS Word Document header
				header("Content-Disposition: attachment; Filename=analysis_events.doc");			// Attachment settings
				echo "<html>\n<head>\n";															// Begin document
				echo "<style type=\"text/css\">\n";													// Style sheet
				include("statsdoc.css");
				echo "\n</style>\n";
				echo "<meta http-equiv=\"Content-type\" content=\"text/html;charset=UTF-8\">\n";	// Character set and content type
				echo "</head>\n<body>\n";
				echo $v_statdata;																	// Output prepared statistics data
				echo "\n</body>\n</html>";															// Finish document
				exit();																				// Do not generate site
			}
			
			// Do we want to produce an Excel spreadsheet?
			if($_POST['outputmode'] == "spreadsheet")
			{
				// Generate Word document
				header("Content-type: application/vnd.ms-excel");									// MS Word Document header
				header("Content-Disposition: attachment; Filename=analysis_events.xls");			// Attachment settings
				echo "<html>\n<head>\n";															// Begin document
				echo "<style type=\"text/css\">\n";													// Style sheet
				include("statsdoc.css");
				echo "\n</style>\n";
				echo "<meta http-equiv=\"Content-type\" content=\"text/html;charset=UTF-8\">\n";	// Character set and content type
				echo "</head>\n<body>\n";
				echo $v_statdata;																	// Output prepared statistics data
				echo "\n</body>\n</html>";															// Finish document
				exit();																				// Do not generate site
			}
		}
		break;
		
	case 'stats_teams':
		if(isset($_POST['submitted']) && isset($_POST['days']) && isset($_POST['teams']) && isset($_POST['outputmode']))
		{
			// Produce array of all valid teams to process
			$team_list = array();
			foreach($_POST['teams'] as $key => $teamid)
			{
				// If team exists, add it to array
				// Preserves order; does not remove duplicates
				$query = "SELECT t_id FROM day_teams WHERE t_id='" . mysql_real_escape_string($teamid) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				if(mysql_num_rows($result) == 1)
				{
					$team_list[] = array('dayid' => $_POST['days'][$key], 'teamid' => $teamid);
				}
				mysql_free_result($result);
			}
			
			// Prepare HTML output
			$v_statdata = "<table class=\"datatable bordered\">\n<tr class=\"darkgrey\">\n<th>&nbsp;</th>\n";
			// Output names of teams
			foreach($team_list as $teamdata)
			{
				$query = "SELECT days.name AS day_name, day_teams.name AS team_name FROM days, day_teams";
				$query .= " WHERE days.d_id=day_teams.d_id AND day_teams.t_id='" . mysql_real_escape_string($teamdata['teamid']) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$row = mysql_fetch_array($result);
				$v_statdata .= "<th>" . htmlspecialchars($row['day_name']) . " - " . htmlspecialchars($row['team_name']) . "</th>\n";
				mysql_free_result($result);
			}
			$v_statdata .= "</tr>\n<tr class=\"darkgrey\">\n<th colspan=\"". (count($team_list) + 1) . "\"><i>Summary Statistics:</i></th>\n</tr>\n";
			$v_statdata .= "<tr class=\"lightgrey\">\n<th>Final Position and Score</th>\n";
			// Output final team position and score
			foreach($team_list as $teamdata)
			{
				// Fetch data array
				$totalscores = get_currenttotalscores($teamdata['dayid']);
				
				// Output score data
				$v_statdata .= "<td>";
				foreach($totalscores as $place => $data)
				{
					if($data['t_id'] == $teamdata['teamid'])
					{
						$v_statdata .= "<b>" . $place . "<sup>" . getNumberSuffix($place) . "</sup>:</b> ";
						$v_statdata .= truncate_number($data['final_score']) . " points";
					}
				}
				$v_statdata .= "</td>\n";
			}			
			$v_statdata .= "</tr>\n<tr class=\"lightgrey\">\n<th>Events Won</th>\n";
			// Output events won by this team
			foreach($team_list as $teamdata)
			{
				// Fetch data array
				$eventwincount = get_totalteampositions($teamdata['dayid']);
				
				// Output win data
				$v_statdata .= "<td>";
				foreach($eventwincount as $t_id => $windata)
				{
					if($t_id == $teamdata['teamid'])
					{
						foreach($windata['position'] as $position => $total)
						{
							$v_statdata .= "<b>" . htmlspecialchars($position) . "<sup>" . getNumberSuffix($position) . "</sup></b>: " . htmlspecialchars($total) . "<br>";
						}
					}
				}
				$v_statdata .= "</td>\n";
			}
			$v_statdata .= "</tr>\n<tr class=\"lightgrey\">\n<th>Records Broken</th>\n";
			// Output records broken by this team
			foreach($team_list as $teamdata)
			{
				// Fetch data array
				$newrecords = get_newrecords($teamdata['dayid']);
				
				// Output record data
				$v_statdata .= "<td>";
				$brokenrecords = 0;
				foreach($newrecords as $data)
				{
					if($data['team_id'] == $teamdata['teamid']) $brokenrecords++;
				}
				if($brokenrecords == 0)
				{
					$v_statdata .= "<b>No Records were broken.</b><br>";
				} elseif($brokenrecords == 1) {
					$v_statdata .= "<b>1 Record broken!</b><br>";
				} else {
					$v_statdata .= "<b>" . $brokenrecords . " Records broken!</b><br>";
				}
				$v_statdata .= "<ul>";
				foreach($newrecords as $data)
				{
					if($data['team_id'] == $teamdata['teamid'])
					{
						$v_statdata .= "<li>" . htmlspecialchars($data['competitor_name']) . " - ";
							if($data['score_units'] == "seconds" && $data['score'] >= 60)
							{
								$v_statdata .= htmlspecialchars(convertToMinutes($data['score'], $data['score_units_dp'])) . " minutes, ";
							} else {
								$v_statdata .= htmlspecialchars(truncate_number($data['score'], $data['score_units_dp'])) . " " . htmlspecialchars($data['score_units']) . ", ";
							}
						$v_statdata .= htmlspecialchars($data['event_name']) . "</li>";
					}
				}
				$v_statdata .= "</ul></td>\n";
			}
			$v_statdata .= "</tr>\n<tr class=\"darkgrey\">\n<th colspan=\"" . (count($team_list) + 1) . "\"><i>Event Statistics:</i></th>\n</tr>\n";
			// Generate array of all events for all days, linking similarly named events
			$combinedeventlist = array();
			foreach($team_list as $teamdata)
			{
				$query = "SELECT e_id, name FROM day_events WHERE d_id='" . mysql_real_escape_string($teamdata['dayid']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				while($row = mysql_fetch_array($result))
				{
					$combinedeventlist[$row['name']][$teamdata['dayid']] = $row['e_id'];
				}
				mysql_free_result($result);
			}
			// Output all event rows
			foreach($combinedeventlist as $eventname => $idlist)
			{
				$v_statdata .= "<tr class=\"lightgrey\">\n<th>" . htmlspecialchars($eventname) ."</th>\n";
				// Output event totals per team
				foreach($team_list as $teamdata)
				{
					// Open cell
					$v_statdata .= "<td>";
					
					// Does this day have this event?
					if(isset($idlist[$teamdata['dayid']]))
					{
						// Yes
						// Fetch data array
						$teamresults = get_event_teamresults($idlist[$teamdata['dayid']]);
						
						// Output record data
						foreach($teamresults as $place => $data)
						{
							if($data['t_id'] == $teamdata['teamid'])
							{
								$v_statdata .= "<b>";
								if($data['tiedposition'] != "") $v_statdata .= "=";
								$v_statdata .= $data['finalposition'] . "<sup>" . getNumberSuffix($data['finalposition']) . "</sup> place</b> - ";
								$v_statdata .= htmlspecialchars($data['score']) . " points";
							}
						}
					} else {
						// No
						$v_statdata .= "-----";
					}
					
					// Close cell
					$v_statdata .= "</td>\n";
				}
				$v_statdata .= "</tr>\n";
			}
			$v_statdata .= "</table>";
			
			// Do we want to produce a Word document?
			if($_POST['outputmode'] == "document")
			{
				// Generate Word document
				header("Content-type: application/vnd.ms-word");									// MS Word Document header
				header("Content-Disposition: attachment; Filename=analysis_teams.doc");				// Attachment settings
				echo "<html>\n<head>\n";															// Begin document
				echo "<style type=\"text/css\">\n";													// Style sheet
				include("statsdoc.css");
				echo "\n</style>\n";
				echo "<meta http-equiv=\"Content-type\" content=\"text/html;charset=UTF-8\">\n";	// Character set and content type
				echo "</head>\n<body>\n";
				echo $v_statdata;																	// Output prepared statistics data
				echo "\n</body>\n</html>";															// Finish document
				exit();																				// Do not generate site
			}
			
			// Do we want to produce an Excel spreadsheet?
			if($_POST['outputmode'] == "spreadsheet")
			{
				// Generate Word document
				header("Content-type: application/vnd.ms-excel");									// MS Word Document header
				header("Content-Disposition: attachment; Filename=analysis_teams.xls");				// Attachment settings
				echo "<html>\n<head>\n";															// Begin document
				echo "<style type=\"text/css\">\n";													// Style sheet
				include("statsdoc.css");
				echo "\n</style>\n";
				echo "<meta http-equiv=\"Content-type\" content=\"text/html;charset=UTF-8\">\n";	// Character set and content type
				echo "</head>\n<body>\n";
				echo $v_statdata;																	// Output prepared statistics data
				echo "\n</body>\n</html>";															// Finish document
				exit();																				// Do not generate site
			}
		}
		break;
		
	case 'stats_competitors':
		if(isset($_POST['submitted']) && isset($_POST['days']) && isset($_POST['competitors']) && isset($_POST['outputmode']))
		{
			// Produce array of all valid competitors to process
			$competitor_list = array();
			foreach($_POST['competitors'] as $key => $competitorid)
			{
				// If competitor exists, add it to array
				// Preserves order; does not remove duplicates
				$query = "SELECT c_id FROM day_competitors WHERE c_id='" . mysql_real_escape_string($competitorid) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				if(mysql_num_rows($result) == 1)
				{
					$competitor_list[] = array('dayid' => $_POST['days'][$key], 'competitorid' => $competitorid);
				}
				mysql_free_result($result);
			}
			
			// Prepare HTML output
			$v_statdata = "<table class=\"datatable bordered\">\n<tr class=\"darkgrey\">\n<th>&nbsp;</th>\n";
			// Output names of competitors
			foreach($competitor_list as $competitordata)
			{
				$query = "SELECT days.name AS day_name, day_competitors.name AS competitor_name FROM days, day_competitors";
				$query .= " WHERE days.d_id=day_competitors.d_id AND day_competitors.c_id='" . mysql_real_escape_string($competitordata['competitorid']) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$row = mysql_fetch_array($result);
				$v_statdata .= "<th>" . htmlspecialchars($row['day_name']) . " - " . htmlspecialchars($row['competitor_name']) . "</th>\n";
				mysql_free_result($result);
			}
			$v_statdata .= "</tr>\n<tr class=\"darkgrey\">\n<th colspan=\"". (count($competitor_list) + 1) . "\"><i>Summary Statistics:</i></th>\n</tr>\n";
			$v_statdata .= "<tr class=\"lightgrey\">\n<th>Total Points Scored</th>\n";
			// Output total score for each competitor
			foreach($competitor_list as $competitordata)
			{
				// Fetch sum of this competitor's scores from database and output
				$query = "SELECT SUM(worth) AS totalscore FROM day_scores WHERE c_id='" . mysql_real_escape_string($competitordata['competitorid']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$row = mysql_fetch_array($result);
				$v_statdata .= "<td>" . truncate_number($row['totalscore']) . "</td>\n";
				mysql_free_result($result);
			}
			$v_statdata .= "</tr>\n<tr class=\"lightgrey\">\n<th>Events Entered</th>\n";
			// Output events entered for each competitor
			foreach($competitor_list as $competitordata)
			{
				// Fetch number of events entered by this competitor from database and output
				$query = "SELECT COUNT(worth) AS eventcount FROM day_scores WHERE c_id='" . mysql_real_escape_string($competitordata['competitorid']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$row = mysql_fetch_array($result);
				$v_statdata .= "<td>" . htmlspecialchars($row['eventcount']) . "</td>\n";
				mysql_free_result($result);
			}
			$v_statdata .= "</tr>\n<tr class=\"lightgrey\">\n<th>Records Broken</th>\n";
			// Output records broken by this competitor
			foreach($competitor_list as $competitordata)
			{
				// Fetch data array
				$newrecords = get_newrecords($competitordata['dayid']);
				
				// Output record data
				$v_statdata .= "<td>";
				$brokenrecords = 0;
				foreach($newrecords as $data)
				{
					if($data['competitor_id'] == $competitordata['competitorid']) $brokenrecords++;
				}
				if($brokenrecords == 0)
				{
					$v_statdata .= "<b>No Records were broken.</b><br>";
				} elseif($brokenrecords == 1) {
					$v_statdata .= "<b>1 Record broken!</b><br>";
				} else {
					$v_statdata .= "<b>" . $brokenrecords . " Records broken!</b><br>";
				}
				$v_statdata .= "<ul>";
				foreach($newrecords as $data)
				{
					if($data['competitor_id'] == $competitordata['competitorid'])
					{
						if($data['score_units'] == "seconds" && $data['score'] >= 60)
						{
							$v_statdata .= "<li>" . htmlspecialchars(convertToMinutes($data['score'], $data['score_units_dp'])) . " minutes, ";
						} else {
							$v_statdata .= "<li>" . htmlspecialchars(truncate_number($data['score'], $data['score_units_dp'])) . " " . htmlspecialchars($data['score_units']) . ", ";
						}
						$v_statdata .= htmlspecialchars($data['event_name']) . "</li>";
					}
				}
				$v_statdata .= "</ul></td>\n";
			}
			$v_statdata .= "</tr>\n<tr class=\"darkgrey\">\n<th colspan=\"" . (count($competitor_list) + 1) . "\"><i>Event Statistics:</i></th>\n</tr>\n";
			// Generate array of all events that competitors take part in, linking similarly named events
			$combinedeventlist = array();
			foreach($competitor_list as $competitordata)
			{
				$query = "SELECT day_events.e_id AS e_id, day_events.name AS name FROM day_events, day_scores";
				$query .= " WHERE day_events.e_id=day_scores.e_id AND day_scores.c_id='" . mysql_real_escape_string($competitordata['competitorid']) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				while($row = mysql_fetch_array($result))
				{
					$combinedeventlist[$row['name']][$competitordata['competitorid']] = $row['e_id'];
				}
				mysql_free_result($result);
			}
			// Output all event rows
			foreach($combinedeventlist as $eventname => $idlist)
			{
				$v_statdata .= "<tr class=\"lightgrey\">\n<th>" . htmlspecialchars($eventname) ."</th>\n";
				// Output event positions per competitor
				foreach($competitor_list as $competitordata)
				{
					// Open cell
					$v_statdata .= "<td>";
					
					// Did this competitor compete in this event?
					if(isset($idlist[$competitordata['competitorid']]))
					{
						// Yes
						// Fetch competitor's score from database
						$query = "SELECT day_scores.place AS place, day_scores.result AS result, day_scoreschemes.result_units AS units,";
						$query .= " day_scoreschemes.result_units_dp AS units_dp FROM day_scores, day_scoreschemes, day_events";
						$query .= " WHERE day_events.e_id='" . mysql_real_escape_string($idlist[$competitordata['competitorid']]) . "'";
						$query .= " AND day_events.ss_id=day_scoreschemes.ss_id AND day_events.e_id=day_scores.e_id";
						$query .= " AND day_scores.c_id='" . mysql_real_escape_string($competitordata['competitorid']) . "'";
						$result = @mysql_query($query);
						if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
						$row = mysql_fetch_array($result);
						$competitorresults = array();
						$competitorresults['place'] = ($row['place'] == 0) ? "DNC" : $row['place'];
						$competitorresults['result'] = $row['result'];
						$competitorresults['units'] = $row['units'];
						$competitorresults['units_dp'] = $row['units_dp'];
						mysql_free_result($result);
						
						// Was competitor tied?
						$query = "SELECT COUNT(sc_id) AS ties FROM day_scores WHERE e_id='" . mysql_real_escape_string($idlist[$competitordata['competitorid']]) . "'";
						$query .= " AND place='" . mysql_real_escape_string($row['place']) . "'";
						$result = @mysql_query($query);
						if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
						$row = mysql_fetch_array($result);
						$competitorresults['tied'] = ($row['ties'] > 1);
						mysql_free_result($result);
						
						// Output result data
						$v_statdata .= "<b>";
						if($competitorresults['tied']) $v_statdata .= "=";
						$v_statdata .= $competitorresults['place'];
						if(is_numeric($competitorresults['place'])) $v_statdata .= "<sup>" . getNumberSuffix($competitorresults['place']) . "</sup> place";
						$v_statdata .= "</b> - ";
						if($competitorresults['units'] == "seconds" && $competitorresults['result'] >= 60)
						{
							$v_statdata .= convertToMinutes($competitorresults['result'], $competitorresults['units_dp']) . " minutes";
						} else {
							$v_statdata .= truncate_number($competitorresults['result'], $competitorresults['units_dp']) . " " . htmlspecialchars($competitorresults['units']);
						}
					} else {
						// No
						$v_statdata .= "-----";
					}
					
					// Close cell
					$v_statdata .= "</td>\n";
				}
				$v_statdata .= "</tr>\n";
			}
			$v_statdata . "</table>";
			
			// Do we want to produce a Word document?
			if($_POST['outputmode'] == "document")
			{
				// Generate Word document
				header("Content-type: application/vnd.ms-word");									// MS Word Document header
				header("Content-Disposition: attachment; Filename=analysis_competitors.doc");		// Attachment settings
				echo "<html>\n<head>\n";															// Begin document
				echo "<style type=\"text/css\">\n";													// Style sheet
				include("statsdoc.css");
				echo "\n</style>\n";
				echo "<meta http-equiv=\"Content-type\" content=\"text/html;charset=UTF-8\">\n";	// Character set and content type
				echo "</head>\n<body>\n";
				echo $v_statdata;																	// Output prepared statistics data
				echo "\n</body>\n</html>";															// Finish document
				exit();																				// Do not generate site
			}
			
			// Do we want to produce an Excel spreadsheet?
			if($_POST['outputmode'] == "spreadsheet")
			{
				// Generate Word document
				header("Content-type: application/vnd.ms-excel");									// MS Word Document header
				header("Content-Disposition: attachment; Filename=analysis_competitors.xls");		// Attachment settings
				echo "<html>\n<head>\n";															// Begin document
				echo "<style type=\"text/css\">\n";													// Style sheet
				include("statsdoc.css");
				echo "\n</style>\n";
				echo "<meta http-equiv=\"Content-type\" content=\"text/html;charset=UTF-8\">\n";	// Character set and content type
				echo "</head>\n<body>\n";
				echo $v_statdata;																	// Output prepared statistics data
				echo "\n</body>\n</html>";															// Finish document
				exit();																				// Do not generate site
			}
		}
		break;
		
	case 'stats_records':
		// Generate analysis table form parsing
		if(isset($_POST['submitted']) && isset($_POST['days']) && isset($_POST['outputmode']))
		{
			// Produce array of all valid days to process
			$day_list = array();
			foreach($_POST['days'] as $dayid)
			{
				// If day exists, add it to array
				// Preserves order; does not remove duplicates
				$query = "SELECT d_id FROM days WHERE d_id='" . mysql_real_escape_string($dayid) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				if(mysql_num_rows($result) == 1)
				{
					$day_list[] = $dayid;
				}
				mysql_free_result($result);
			}
			
			// Prepare HTML output
			$v_statdata = "<table class=\"datatable bordered\">\n<tr class=\"darkgrey\">\n<th>&nbsp;</th>\n";
			// Output names of days
			foreach($day_list as $dayid)
			{
				$query = "SELECT name FROM days WHERE d_id='" . mysql_real_escape_string($dayid) . "' LIMIT 1";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$row = mysql_fetch_array($result);
				$v_statdata .= "<th>" . htmlspecialchars($row['name']) . "</th>\n";
				mysql_free_result($result);
			}
			$v_statdata .= "</tr>\n<tr class=\"darkgrey\">\n<th colspan=\"" . (count($day_list) + 1) . "\"><i>Records:</i></th>\n</tr>\n";
			// Generate array of all events for all days, linking similarly named events
			$combinedeventlist = array();
			foreach($day_list as $dayid)
			{
				$query = "SELECT e_id, name FROM day_events WHERE d_id='" . mysql_real_escape_string($dayid) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				while($row = mysql_fetch_array($result))
				{
					$combinedeventlist[$row['name']][$dayid] = $row['e_id'];
				}
				mysql_free_result($result);
			}
			// Output all event rows
			foreach($combinedeventlist as $eventname => $idlist)
			{
				$v_statdata .= "<tr class=\"lightgrey\">\n<th>" . htmlspecialchars($eventname) ."</th>\n";
				// Output event totals per team
				foreach($day_list as $dayid)
				{
					// Open cell
					$v_statdata .= "<td";
					
					// Does this day have this event?
					if(isset($idlist[$dayid]))
					{
						// Yes
						// Fetch data array
						$currentrecord = get_currentrecord($idlist[$dayid]);
						
						// Output record data
						if($currentrecord['brokenthisday']) $v_statdata .= " class=\"highlighted\"";
						$v_statdata .= ">" . htmlspecialchars($currentrecord['competitor_name']) . " (" . htmlspecialchars($currentrecord['team_name']) . ")<br>";
						if($currentrecord['score_units'] == "seconds" && $currentrecord['score'] >= 60)
						{
							$v_statdata .= convertToMinutes($currentrecord['score'], $currentrecord['score_units_dp']) . " minutes<br>";
						} else {
							$v_statdata .= truncate_number($currentrecord['score'], $currentrecord['score_units_dp']) . " " . htmlspecialchars($currentrecord['score_units']) . "<br>";
						}
						$v_statdata .= htmlspecialchars($currentrecord['yearset']);
					} else {
						// No
						$v_statdata .= ">-----";
					}
					
					// Close cell
					$v_statdata .= "</td>\n";
				}
				$v_statdata .= "</tr>\n";
			}
			$v_statdata .= "</table>";
			
			// Do we want to produce a Word document?
			if($_POST['outputmode'] == "document")
			{
				// Generate Word document
				header("Content-type: application/vnd.ms-word");									// MS Word Document header
				header("Content-Disposition: attachment; Filename=analysis_records.doc");			// Attachment settings
				echo "<html>\n<head>\n";															// Begin document
				echo "<style type=\"text/css\">\n";													// Style sheet
				include("statsdoc.css");
				echo "\n</style>\n";
				echo "<meta http-equiv=\"Content-type\" content=\"text/html;charset=UTF-8\">\n";	// Character set and content type
				echo "</head>\n<body>\n";
				echo $v_statdata;																	// Output prepared statistics data
				echo "\n</body>\n</html>";															// Finish document
				exit();																				// Do not generate site
			}
			
			// Do we want to produce an Excel spreadsheet?
			if($_POST['outputmode'] == "spreadsheet")
			{
				// Generate Word document
				header("Content-type: application/vnd.ms-excel");									// MS Word Document header
				header("Content-Disposition: attachment; Filename=analysis_records.xls");			// Attachment settings
				echo "<html>\n<head>\n";															// Begin document
				echo "<style type=\"text/css\">\n";													// Style sheet
				include("statsdoc.css");
				echo "\n</style>\n";
				echo "<meta http-equiv=\"Content-type\" content=\"text/html;charset=UTF-8\">\n";	// Character set and content type
				echo "</head>\n<body>\n";
				echo $v_statdata;																	// Output prepared statistics data
				echo "\n</body>\n</html>";															// Finish document
				exit();																				// Do not generate site
			}
		}
		break;
		
	case 'stats_scoresheet':
		// Generate analysis table form parsing
		if(isset($_POST['submitted']) && isset($_POST['day']) && isset($_POST['outputmode']))
		{
			// Produce array of all teams on this day
			$team_list = array();
			$query = "SELECT t_id, name FROM day_teams WHERE d_id='" . mysql_real_escape_string($_POST['day']) . "'";
			$result = @mysql_query($query);
			if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
			while($row = mysql_fetch_array($result))
			{
				$team_list[$row['t_id']] = $row['name'];
			}
			mysql_free_result($result);
			
			// Produce array of all subgroups on this day
			$group_list = array();
			$query = "SELECT sub_id, name FROM day_subgroups WHERE d_id='" . mysql_real_escape_string($_POST['day']) . "'";
			$result = @mysql_query($query);
			if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
			while($row = mysql_fetch_array($result))
			{
				$group_list[$row['sub_id']] = $row['name'];
			}
			mysql_free_result($result);
			
			// Produce array of all events on this day, so long as at least one of the selected subgroups is eligible for them
			$event_list = array();
			$query = "SELECT day_events.e_id, day_events.name FROM day_events, day_groupeligibility";
			$query .= " WHERE day_events.e_id=day_groupeligibility.e_id AND day_events.d_id='" . mysql_real_escape_string($_POST['day']) . "'";
			if(isset($_POST['showgroup']))
			{
				$query .= " AND (";
				foreach($_POST['showgroup'] as $eligiblegroupid => $value)
				{
					$query .= "day_groupeligibility.sub_id='" . mysql_real_escape_string($eligiblegroupid) . "' OR ";
				}
				$query .= "'1'='0')";
			}
			$query .= " GROUP BY day_events.e_id";
			$result = @mysql_query($query);
			if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
			while($row = mysql_fetch_array($result))
			{
				$event_list[$row['e_id']] = $row['name'];
			}
			mysql_free_result($result);
			
			// Find out the maximum number of entrants per team across all events on this day
			$query = "SELECT MAX(count_entrants_per_team) AS max_ept FROM day_scoreschemes WHERE d_id='" . mysql_real_escape_string($_POST['day']) . "' LIMIT 1";
			$result = @mysql_query($query);
			if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
			$row = mysql_fetch_array($result);
			$max_ept = $row['max_ept'];
			mysql_free_result($result);
			
			// Prepare HTML output
			$v_statdata = "";
			// Overall table
			if(isset($_POST['showsummary']))
			{
				$v_statdata .= "<table class=\"datatable bordered\">\n<tr class=\"darkgrey\">\n<th>&nbsp;</th>\n";
				// Output names of teams
				foreach($team_list as $teamname)
				{
					$v_statdata .= "<th>" . htmlspecialchars($teamname) . "</th>\n";
				}
				$v_statdata .= "</tr>\n";
				// Output data for each subgroup
				foreach($group_list as $groupid => $groupname)
				{
					$v_statdata .= "<tr>\n";
					$v_statdata .= "<th class=\"darkgrey\">" . htmlspecialchars($groupname) . "</th>\n";
					$teamsubgroupscores = get_teamsubgroupscores($groupid);
					foreach($team_list as $teamid => $teamname)
					{
						if(isset($teamsubgroupscores[$teamid]))
						{
							if($teamsubgroupscores[$teamid]['position'] == 1)
							{
								$v_statdata .= "<td class=\"highlighted\"><b>" . htmlspecialchars(truncate_number($teamsubgroupscores[$teamid]['score'])) . "</b></td>\n";
							} else {
								$v_statdata .= "<td>" . htmlspecialchars(truncate_number($teamsubgroupscores[$teamid]['score'])) . "</td>\n";
							}
						} else {
							$v_statdata .= "<td></td>\n";
						}
					}
					$v_statdata .= "</tr>\n";
				}
				// Output totals
				$v_statdata .= "<tr class=\"lightgrey\">\n<td colspan=\"" . (count($team_list) + 1) . "\">&nbsp;</td>\n</tr>\n";
				$totalscores = get_currenttotalscores($_POST['day']);
				$v_statdata .= "<tr>\n<th class=\"darkgrey\"><i>Sub-Totals</i></th>\n";
				foreach($team_list as $teamid => $teamname)
				{
					$teamdone = false;
					foreach($totalscores as $scoredata)
					{
						if($scoredata['t_id'] == $teamid)
						{
							$teamdone = true;
							$v_statdata .= "<td><i>" . htmlspecialchars(truncate_number($scoredata['total_score'])) . "</i></td>\n";
						}
					}
					if(!$teamdone) $v_statdata .= "<td></td>\n";
				}
				$v_statdata .= "</tr>\n<tr>\n<th class=\"darkgrey\"><i>Prior Scores</i></th>\n";
				foreach($team_list as $teamid => $teamname)
				{
					$teamdone = false;
					foreach($totalscores as $scoredata)
					{
						if($scoredata['t_id'] == $teamid)
						{
							$teamdone = true;
							$v_statdata .= "<td><i>" . htmlspecialchars(truncate_number($scoredata['prior_score'])) . "</i></td>\n";
						}
					}
					if(!$teamdone) $v_statdata .= "<td></td>\n";
				}
				$v_statdata .= "</tr><tr>\n<th class=\"darkgrey\"><i>Totals</i></th>\n";
				foreach($team_list as $teamid => $teamname)
				{
					$teamdone = false;
					foreach($totalscores as $scoredata)
					{
						if($scoredata['t_id'] == $teamid)
						{
							$teamdone = true;
							if($scoredata['place'] == 1)
							{
								$v_statdata .= "<td class=\"highlighted\"><b><i>" . htmlspecialchars(truncate_number($scoredata['final_score'])) . "</i></b></td>\n";
							} else {
								$v_statdata .= "<td><i>" . htmlspecialchars(truncate_number($scoredata['final_score'])) . "</i></td>\n";
							}
						}
					}
					if(!$teamdone) $v_statdata .= "<td></td>\n";
				}
				$v_statdata .= "</tr>\n";
				$v_statdata .= "</table><br><br>\n";
			}
			
			// Scores table
			$v_statdata .= "<table class=\"datatable bordered\">\n<tr class=\"darkgrey\">\n<th>&nbsp;</th>\n";
			// Output positions
			for($i = 1; $i <= $max_ept * count($team_list); $i++)
			{
				$v_statdata .= "<th>$i" . getNumberSuffix($i) . "</th>\n";
			}
			// Output names of teams
			foreach($team_list as $teamname)
			{
				$v_statdata .= "<th>" . htmlspecialchars($teamname) . "</th>\n";
			}
			$v_statdata .= "</tr>\n";
			// Output data for each event
			foreach($event_list as $eventid => $eventname)
			{
				$v_statdata .= "<tr>\n";
				$v_statdata .= "<th class=\"darkgrey\">" . htmlspecialchars($eventname) . "</th>\n";
				// Individual results
				$individualresults = get_event_individualresults($eventid);
				for($i = 0; $i < $max_ept * count($team_list); $i++)
				{
					$v_statdata .= "<td>";
					if(isset($individualresults[$i]))
					{
						$v_statdata .= htmlspecialchars($individualresults[$i]['name']);
						if($individualresults[$i]['tied']) $v_statdata .= " <b>(=)</b>";
						$v_statdata .= "<br>" . htmlspecialchars($individualresults[$i]['team_name']) . "<br>";
						if($individualresults[$i]['units'] == "seconds" && $individualresults[$i]['result'] >= 60)
						{
							$v_statdata .= htmlspecialchars(convertToMinutes($individualresults[$i]['result'], $individualresults[$i]['units_dp']));
							if(isset($_POST['showunits'])) $v_statdata .= " minutes";
						} else {
							$v_statdata .= htmlspecialchars(truncate_number($individualresults[$i]['result'], $individualresults[$i]['units_dp']));
							if(isset($_POST['showunits']))  $v_statdata .= " " . htmlspecialchars($individualresults[$i]['units']);
						}
					}
					$v_statdata .= "</td>\n";
				}
				// Total team scores
				$teamresults = get_event_teamresults($eventid);
				foreach($team_list as $teamid => $teamname)
				{
					$v_statdata .= "<td class=\"lightgrey\">";
					foreach($teamresults as $teamdata)
					{
						if($teamdata['t_id'] == $teamid)
						{
							$v_statdata .= htmlspecialchars($teamdata['score']);
						}
					}
					$v_statdata .= "</td>\n";
				}
				$v_statdata .= "</tr>\n";
			}
			$v_statdata .= "</table>";
			
			// Do we want to produce a Word document?
			if($_POST['outputmode'] == "document")
			{
				// Generate Word document
				header("Content-type: application/vnd.ms-word");									// MS Word Document header
				header("Content-Disposition: attachment; Filename=analysis_scoresheet.doc");		// Attachment settings
				echo "<html>\n<head>\n";															// Begin document
				echo "<style type=\"text/css\">\n";													// Style sheet
				include("statsdoc.css");
				echo "\n</style>\n";
				echo "<meta http-equiv=\"Content-type\" content=\"text/html;charset=UTF-8\">\n";	// Character set and content type
				echo "</head>\n<body>\n";
				echo $v_statdata;																	// Output prepared statistics data
				echo "\n</body>\n</html>";															// Finish document
				exit();																				// Do not generate site
			}
			
			// Do we want to produce an Excel spreadsheet?
			if($_POST['outputmode'] == "spreadsheet")
			{
				// Generate Word document
				header("Content-type: application/vnd.ms-excel");									// MS Word Document header
				header("Content-Disposition: attachment; Filename=analysis_scoresheet.xls");		// Attachment settings
				echo "<html>\n<head>\n";															// Begin document
				echo "<style type=\"text/css\">\n";													// Style sheet
				include("statsdoc.css");
				echo "\n</style>\n";
				echo "<meta http-equiv=\"Content-type\" content=\"text/html;charset=UTF-8\">\n";	// Character set and content type
				echo "</head>\n<body>\n";
				echo $v_statdata;																	// Output prepared statistics data
				echo "\n</body>\n</html>";															// Finish document
				exit();																				// Do not generate site
			}
		}
		break;
		
	case 'help':
		// Nothing to do
		break;
		
	default:
		// Nothing to do
		break;
}
?>
<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>

<head>
	<title><?php /* Page Title */ echo $curpagetitle; ?></title>
	<link rel="stylesheet" type="text/css" href="scoresystem.css">
	<script type="text/javascript" src="jquery.js"></script>
	<script type="text/javascript" src="scoresystem.js"></script>
	<meta http-equiv="Content-type" content="text/html;charset=UTF-8">
</head>

<body>
	<!-- Header -->
	<div id="header">
		<h1><?php /* Page Headline */ echo $curpageheadline; ?></h1>
	</div>
	
	<!-- Content Container -->
	<div id="container">
		<!-- Side Bar -->
		<div id="sidebar">
			<!-- Menu Box -->
			<div id="menubox">
				<ul><?php
				// Function to generate HTML for each menu item
				function generate_menu_markup($pagename, $menutext)
				{
					global $curpage;
					$resultmarkup = '<li><a class="';
					$resultmarkup .= ($curpage == $pagename)? 'menuselected' : 'menunormal';
					$resultmarkup .= '" href="index.php?page=' . $pagename . '">' . $menutext . '</a></li>';
					return $resultmarkup;
				}
				
				echo "\n";
				if(isset($_SESSION['user_id']))
				{
					// Menu for logged in user
					// Some links are only displayed if the user has certain permissions set
					echo "\t\t\t\t\t" . generate_menu_markup('welcome', 'Home') . "\n";
					if($_SESSION['perm_admin'] == 1) echo "\t\t\t\t\t" . generate_menu_markup('adminpanel', 'Admin Panel') . "\n";
					if($_SESSION['perm_daymanage'] == 1) echo "\t\t\t\t\t" . generate_menu_markup('comppanel', 'Manage Competitions') . "\n";
					if($_SESSION['perm_scoreedit'] == 1 && isset($curactivedayid)) echo "\t\t\t\t\t" . generate_menu_markup('scoreentry', 'Edit Scores') . "\n";
					if($_SESSION['perm_scoreview'] == 1 && isset($curactivedayid)) echo "\t\t\t\t\t" . generate_menu_markup('scoreviewer', 'Live Results') . "\n";
					if($_SESSION['perm_stats'] == 1) echo "\t\t\t\t\t" . generate_menu_markup('statspanel', 'Analyse Statistics') . "\n";
					echo "\t\t\t\t\t" . generate_menu_markup('help', 'Help') . "\n";
				} else {
					// Menu for guest user
					echo "\t\t\t\t\t" . generate_menu_markup('login', 'Log In') . "\n";
				}
				?>
				</ul>
			</div>
			<!-- User Box -->
			<div id="userbox">
				<?php
				// Display login status
				if(isset($_SESSION['user_id']))
				{
					echo "You are currently logged in as <i>" . htmlspecialchars($_SESSION['user_name']) . "</i>.\n";
				} else {
					echo "You are not currently logged in.\n";
				}
				if(isset($_SESSION['user_id']))
				{?>
				<br><br>
				<a href="index.php?page=accountsettings">Settings</a>
				<a href="index.php?page=logout">Log Out</a><?php } else { ?>
				<br><br>
				<a href="index.php?page=login">Log In</a><?php } ?>
			</div>
			<!-- System Info Box -->
			<div id="systembox">
				&copy;2012 James Wallis<br>
				All rights reserved.<br><br>
				Version 1.04
			</div>
		</div>
		
		<!-- Page Content -->
		<?php if(isset($_SESSION['user_id'])) { ?><div id="helpbutton">
			<a href="index.php?page=help<?php echo $curpagehelp; ?>"><img src="helpbutton.png" alt="Help" title="Get help for this page"></a>
		</div><?php } ?>
		<div id="pagecontent">
			<?php
			/*
			 Output page content
			 */
			// Page title and possible sub-links
			echo "<h1>$curpagetitle</h1>\n";
			if($curpage == 'admin_usergroupsettings') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=adminpanel\">&lt;&lt; Return to Administration Panel</a>\n";
			if($curpage == 'admin_deleteusergroup') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=adminpanel\">&lt;&lt; Return to Administration Panel</a>\n";
			if($curpage == 'admin_usersettings') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=adminpanel\">&lt;&lt; Return to Administration Panel</a>\n";
			if($curpage == 'admin_deleteuser') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=adminpanel\">&lt;&lt; Return to Administration Panel</a>\n";
			if($curpage == 'admin_logs') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=adminpanel\">&lt;&lt; Return to Administration Panel</a>\n";
			if($curpage == 'comp_templatesettings') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=comppanel\">&lt;&lt; Return to Competition Management Panel</a>\n";
			if($curpage == 'comp_deletetemplate') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=comppanel\">&lt;&lt; Return to Competition Management Panel</a>\n";
			if($curpage == 'comp_daysettings') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=comppanel\">&lt;&lt; Return to Competition Management Panel</a>\n";
			if($curpage == 'comp_adjustday') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=comppanel\">&lt;&lt; Return to Competition Management Panel</a>\n";
			if($curpage == 'comp_deleteday') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=comppanel\">&lt;&lt; Return to Competition Management Panel</a>\n";
			if($curpage == 'comp_managerecords') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=comppanel\">&lt;&lt; Return to Competition Management Panel</a>\n";
			if($curpage == 'scoreentry_editscore') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=scoreentry\">&lt;&lt; Return to Score Entry</a>\n";
			if($curpage == 'stats_days') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=statspanel\">&lt;&lt; Return to Statistical Analysis</a>\n";
			if($curpage == 'stats_events') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=statspanel\">&lt;&lt; Return to Statistical Analysis</a>\n";
			if($curpage == 'stats_teams') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=statspanel\">&lt;&lt; Return to Statistical Analysis</a>\n";
			if($curpage == 'stats_competitors') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=statspanel\">&lt;&lt; Return to Statistical Analysis</a>\n";
			if($curpage == 'stats_records') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=statspanel\">&lt;&lt; Return to Statistical Analysis</a>\n";
			if($curpage == 'stats_scoresheet') echo "\t\t\t<a class=\"returnlink\" href=\"index.php?page=statspanel\">&lt;&lt; Return to Statistical Analysis</a>\n";
			echo "\t\t\t<br>\n";
			
			// Page specific content
			switch($curpage)
			{
	// Log In
				case 'login': ?>
			<p>
				<form action="index.php?page=login" method="post">
					<fieldset>
						<legend>You must log in to use this system</legend>
						<?php /* Output any messages */ echo $message; ?>
						<table class="formtable">
							<tr>
								<th>Username:</th>
								<td><input type="text" name="username" <?php if(isset($_POST['username'])) echo "value=\"" . htmlspecialchars($_POST['username']) . "\" "; ?>size="30" maxlength="50"></td>
							</tr>
							<tr>
								<th>Password:</th>
								<td><input type="password" name="password" size="30"></td>
							</tr>
							<tr>
								<td class="submitcell" colspan="2"><button type="submit">Log In</button></td>
							</tr>
						</table>
					</fieldset>
					<input type="hidden" name="submitted" value="TRUE">
				</form>
			</p><?php echo "\n";
					break;
					
	// Log Out
				case 'logout': ?>
			<p>You have been successfully logged out.</p>
			<p><a href="index.php?page=login">Click here to log back in.</a></p><?php echo "\n";
					break;
					
	// Account Settings
				case 'accountsettings': ?>
			<p>These are your account settings:</p>
			<p>
				<table class="formtable">
					<tr>
						<th>Username:</th>
						<td><?php echo htmlspecialchars($_SESSION['user_name']); ?></td>
					</tr>
					<tr>
						<th>User Group:</th>
						<td><?php echo htmlspecialchars($_SESSION['group_name']); ?></td>
					</tr>
				</table>
			</p>
			<p>
				<form action="index.php?page=accountsettings" method="post">
					<fieldset>
						<legend>Change password</legend>
						<?php /* Output any messages */ echo $message; ?>
						If you wish to change your password, enter your old password, your new password, and confirm your new password below:
						<table class="formtable">
							<tr>
								<th>Old Password:</th>
								<td><input type="password" name="oldpass" size="30"></td>
							</tr>
							<tr>
								<th>New Password:</th>
								<td><input type="password" name="newpass1" size="30"></td>
							</tr>
							<tr>
								<th>Confirm New Password:</th>
								<td><input type="password" name="newpass2" size="30"></td>
							</tr>
							<tr>
								<td class="submitcell" colspan="2"><button type="submit">Change Password</button></td>
							</tr>
						</table>
					</fieldset>
					<input type="hidden" name="submitted" value="TRUE">
				</form>
			</p><?php echo "\n";
					break;
					
	// Welcome
				case 'welcome': ?>
			<p>
				<b>&lt;&lt;</b> To start, select an action from the left.
			</p>
			<?php
					echo "\n";
					
					// Warning about competitors who have competed in too many events
					if(isset($curactivedayid))
					{
						// Fetch list of competitors over limit and what the limit is
						$competitorsoverlimit = get_competitorsoverlimit($curactivedayid);
						$query = "SELECT max_events_per_competitor FROM days WHERE d_id='" . mysql_real_escape_string($curactivedayid) . "' LIMIT 1";
						$result = @mysql_query($query);
						$row = mysql_fetch_array($result);
						$max_events = $row['max_events_per_competitor'];
						mysql_free_result($result);
						
						// Output warning if there are any such competitors
						if(count($competitorsoverlimit) > 0)
						{
							echo "\t\t\t<p>\n";
							echo "\t\t\t\t<span class=\"errormessage\"><b>Warning:</b></span> ";
							echo "The following competitors have competed in more than the permitted number of <b>$max_events</b> events:<br>\n";
							echo "\t\t\t\t<ul>\n";
							foreach($competitorsoverlimit as $c_id)
							{
								// Fetch competitor name and team id from database
								$query = "SELECT name, t_id FROM day_competitors WHERE c_id='" . mysql_real_escape_string($c_id) . "' LIMIT 1";
								$result = @mysql_query($query);
								$row = mysql_fetch_array($result);
								$t_id = $row['t_id'];
								echo "\t\t\t\t\t<li>" . htmlspecialchars($row['name']) . ", ";
								mysql_free_result($result);
								
								// Fetch competitor's team name from database
								$query = "SELECT name FROM day_teams WHERE t_id='" . mysql_real_escape_string($t_id) . "' LIMIT 1";
								$result = @mysql_query($query);
								$row = mysql_fetch_array($result);
								echo htmlspecialchars($row['name']) . "\n";
								mysql_free_result($result);
								
								// Output events competed in by this competitor
								$query = "SELECT day_events.name AS event FROM day_events, day_scores WHERE c_id='" . mysql_real_escape_string($c_id) . "'";
								$query .= " AND day_events.e_id=day_scores.e_id AND day_events.counts_to_limit='1'";
								$result = @mysql_query($query);
								echo "\t\t\t\t\t<ul><li><b>";
								echo mysql_num_rows($result) . " events:</b><br>";
								while($row = mysql_fetch_array($result))
								{
									echo htmlspecialchars($row['event']) . "<br>";
								}
								echo "</li></ul>\n\t\t\t\t\t</li>\n";
								mysql_free_result($result);
							}
							echo "\t\t\t\t</ul>\n";
							echo "\t\t\t</p>\n";
						}
					}
					break;
					
	// Admin Panel
				case 'adminpanel': ?>
			<p><?php /* Output any messages */ echo $message; ?></p>
			<p>Please select an action:</p>
			<p>
				<table class="paneltable">
					<!-- User Group Management -->
					<tr>
						<th class="sectionheader" colspan="3">Manage User Groups</th>
					</tr>
					<tr>
						<th class="itemlabel">Create new User Group</th>
						<form action="index.php?page=adminpanel&sub=create_ug" method="post">
						<td><input type="text" class="clearOnFirstFocus" name="newug_name" value="Enter a name" size="30" maxlength="50" id="usergroupcreate"></td>
						<td><button type="submit" id="btn_usergroupcreate" disabled="disabled">Create</button></td>
						</form>
					</tr>
					<tr>
						<th class="itemlabel">Edit existing User Group</th>
						<form action="index.php?page=adminpanel&sub=edit_ug" method="post">
						<td>
							<select class="disallowOptionZero" name="editug_name" id="usergroupedit">
								<option value="0" selected="selected" disabled="disabled">Select a group:</option><?php
								// Generate selection box options
								$query = "SELECT ug_id, name FROM usergroups";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo "\n\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['ug_id']) . "\">" . htmlspecialchars($row['name']) . "</option>";
								}
								mysql_free_result($result);
								echo "\n";
								?>
							</select>
						</td>
						<td><button type="submit" id="btn_usergroupedit" disabled="disabled">Edit</button></td>
						</form>
					</tr>
					<tr>
						<th class="itemlabel">Delete existing User Group</th>
						<form action="index.php?page=adminpanel&sub=delete_ug" method="post">
						<td>
							<select class="disallowOptionZero" name="deleteug_name" id="usergroupdelete">
								<option value="0" selected="selected" disabled="disabled">Select a group:</option><?php
								// Generate selection box options
								$query = "SELECT ug_id, name FROM usergroups";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo "\n\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['ug_id']) . "\">" . htmlspecialchars($row['name']) . "</option>";
								}
								mysql_free_result($result);
								echo "\n";
								?>
							</select>
						</td>
						<td><button type="submit" id="btn_usergroupdelete" disabled="disabled">Delete</button></td>
						</form>
					</tr>
					<!-- User Management -->
					<tr>
						<th class="sectionheader" colspan="3">Manage Users</th>
					</tr>
					<tr>
						<th class="itemlabel">Create new User</th>
						<form action="index.php?page=adminpanel&sub=create_u" method="post">
						<td><input type="text" class="clearOnFirstFocus" name="newu_name" value="Enter a name" size="30" maxlength="50" id="usercreate"></td>
						<td><button type="submit" id="btn_usercreate" disabled="disabled">Create</button></td>
						</form>
					</tr>
					<tr>
						<th class="itemlabel">Edit existing User</th>
						<form action="index.php?page=adminpanel&sub=edit_u" method="post">
						<td>
							<select class="disallowOptionZero" name="editu_name" id="useredit">
								<option value="0" selected="selected" disabled="disabled">Select a user:</option><?php
								// Generate selection box options
								$query = "SELECT u_id, name FROM users ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo "\n\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['u_id']) . "\">" . htmlspecialchars($row['name']) . "</option>";
								}
								mysql_free_result($result);
								echo "\n";
								?>
							</select>
						</td>
						<td><button type="submit" id="btn_useredit" disabled="disabled">Edit</button></td>
						</form>
					</tr>
					<tr>
						<th class="itemlabel">Delete existing User</th>
						<form action="index.php?page=adminpanel&sub=delete_u" method="post">
						<td>
							<select class="disallowOptionZero" name="deleteu_name" id="userdelete">
								<option selected="selected" disabled="disabled">Select a user:</option><?php
								// Generate selection box options
								$query = "SELECT u_id, name FROM users ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo "\n\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['u_id']) . "\">" . htmlspecialchars($row['name']) . "</option>";
								}
								mysql_free_result($result);
								echo "\n";
								?>
							</select>
						</td>
						<td><button type="submit" id="btn_userdelete" disabled="disabled">Delete</button></td>
						</form>
					</tr>
					<!-- Logs -->
					<tr>
						<th class="sectionheader" colspan="3">View Action Logs</th>
					</tr>
					<tr>
						<td colspan="3"><a href="index.php?page=admin_logs">Click here to view all actions taken on the system</a></td>
					</tr>
				</table>
			</p><?php echo "\n";
					break;
					
	// Admin Panel >> User Group Settings
				case 'admin_usergroupsettings': ?>
			<p>
				<form action="index.php?page=admin_usergroupsettings&ug_id=<?php echo htmlspecialchars($_GET['ug_id']); ?>" method="post">
					<fieldset>
						<legend>Enter User Group settings, then click 'Save Changes'</legend>
						<?php /* Output any messages */ echo $message; ?>
						<table class="formtable">
							<tr>
								<th>Name:</th>
								<td><input type="text" name="groupname" value="<?php echo htmlspecialchars($v_name); ?>" size="30" maxlength="50"></td>
							</tr>
							<tr>
								<th>Permissions:</th>
								<td>
									<input type="checkbox" name="perm_admin" value="1" id="cb_pa" <?php echo htmlspecialchars($v_pa); ?>><label for="cb_pa">Admin Panel</label><br>
									<input type="checkbox" name="perm_daymanage" value="1" id="cb_dm" <?php echo htmlspecialchars($v_dm); ?>><label for="cb_dm">Manage Days</label><br>
									<input type="checkbox" name="perm_scoreedit" value="1" id="cb_se" <?php echo htmlspecialchars($v_se); ?>><label for="cb_se">Edit Scores</label><br>
									<input type="checkbox" name="perm_scoreview" value="1" id="cb_sv" <?php echo htmlspecialchars($v_sv); ?>><label for="cb_sv">View Scores</label><br>
									<input type="checkbox" name="perm_stats" value="1" id="cb_st" <?php echo htmlspecialchars($v_st); ?>><label for="cb_st">Analyse Statistics</label>
								</td>
							</tr>
							<tr>
								<td class="submitcell" colspan="2"><button type="submit">Save Changes</button></td>
							</tr>
						</table>
					</fieldset>
					<input type="hidden" name="submitted" value="TRUE">
				</form>
			</p><?php echo "\n";
					break;
					
	// Admin Panel >> Delete User Group
				case 'admin_deleteusergroup': ?>
			<p><?php
				if($del_done)
				{
					echo "\t\t\t\t$message";
				} else { ?>
				<form action="index.php?page=admin_deleteusergroup&ug_id=<?php echo htmlspecialchars($_GET['ug_id']); ?>" method="post">
					<fieldset>
						<legend>Confirm Delete</legend>
						<?php /* Output any messages */ echo $message; ?>
						<table class="formtable">
							<tr>
								<th>Are you <i>sure</i> you want to delete the User Group <i><?php echo htmlspecialchars($v_name); ?></i>?</th>
							</tr>
							<tr>
								<td>
									<input type="radio" name="confirm" value="Y" id="r_y"><label for="r_y">Yes, I'm sure I want to delete this</label><br>
									<input type="radio" name="confirm" value="N" id="r_n" checked="checked"><label for="r_n">No, take me back to the Administration Panel</label>
								</td>
							</tr>
							<tr>
								<td class="submitcell"><button type="submit">Make Choice</button></td>
							</tr>
						</table>
					</fieldset>
					<input type="hidden" name="submitted" value="TRUE">
				</form><?php } ?>
			</p><?php echo "\n";
					break;
					
	// Admin Panel >> User Settings
				case 'admin_usersettings': ?>
			<p>
				<form action="index.php?page=admin_usersettings&u_id=<?php echo htmlspecialchars($_GET['u_id']); ?>" method="post">
					<fieldset>
						<legend>Enter User settings, then click 'Save Changes'</legend>
						<?php /* Output any messages */ if(isset($_POST['submitted_normal'])) echo $message; ?>
						<table class="formtable">
							<tr>
								<th>Name:</th>
								<td><input type="text" name="username" value="<?php echo htmlspecialchars($v_name); ?>" size="30" maxlength="50"></td>
							</tr>
							<tr>
								<th>User Group:</th>
								<td>
									<select name="group"><?php
										// Generate selection box options
										$query = "SELECT ug_id, name FROM usergroups";
										$result = @mysql_query($query);
										while($row = mysql_fetch_array($result))
										{
											echo "\n\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['ug_id']) . "\"";
											if($row['ug_id'] == $v_group) echo " selected=\"selected\"";
											echo ">" . htmlspecialchars($row['name']) . "</option>";
										}
										mysql_free_result($result);
										echo "\n";
										?>
									</select>
								</td>
							</tr>
							<tr>
								<td class="submitcell" colspan="2"><button type="submit">Save Changes</button></td>
							</tr>
						</table>
					</fieldset>
					<input type="hidden" name="submitted_normal" value="TRUE">
				</form>
			</p>
			<p>
				<form action="index.php?page=admin_usersettings&u_id=<?php echo htmlspecialchars($_GET['u_id']); ?>" method="post">
					<fieldset>
						<legend>Change User password</legend>
						<?php /* Output any messages */ if(isset($_POST['submitted_password'])) echo $message; ?>
						<table class="formtable">
							<tr>
								<th>New Password:</th>
								<td><input type="password" name="newpass1" size="30"></td>
							</tr>
							<tr>
								<th>Confirm New Password:</th>
								<td><input type="password" name="newpass2" size="30"></td>
							</tr>
							<tr>
								<td class="submitcell" colspan="2"><button type="submit">Change Password</button></td>
							</tr>
						</table>
					</fieldset>
					<input type="hidden" name="submitted_password" value="TRUE">
				</form>
			</p><?php echo "\n";
					break;
					
	// Admin Panel >> Delete User
				case 'admin_deleteuser': ?>
			<p><?php
				// Do not display deletion form if the deletion has already been successfully processed
				if($del_done)
				{
					echo "\t\t\t\t$message";
				} else { ?>
				<form action="index.php?page=admin_deleteuser&u_id=<?php echo htmlspecialchars($_GET['u_id']); ?>" method="post">
					<fieldset>
						<legend>Confirm Delete</legend>
						<?php /* Output any messages */ echo $message; ?>
						<table class="formtable">
							<tr>
								<th>Are you <i>sure</i> you want to delete the User <i><?php echo htmlspecialchars($v_name); ?></i>?</th>
							</tr>
							<tr>
								<td>
									<input type="radio" name="confirm" value="Y" id="r_y"><label for="r_y">Yes, I'm sure I want to delete this</label><br>
									<input type="radio" name="confirm" value="N" id="r_n" checked="checked"><label for="r_n">No, take me back to the Administration Panel</label>
								</td>
							</tr>
							<tr>
								<td class="submitcell"><button type="submit">Make Choice</button></td>
							</tr>
						</table>
					</fieldset>
					<input type="hidden" name="submitted" value="TRUE">
				</form><?php } ?>
			</p><?php echo "\n";
					break;
					
	// Admin Panel >> Logs
				case 'admin_logs': ?>
			<p>
				<table class="datatable bordered">
					<tr class="darkgrey">
						<th>Date</th>
						<th>Time</th>
						<th>User</th>
						<th>Action</th>
					</tr><?php
					// Load usernames from database (not done using a join in log entry query to account for Guest users)
					$query = "SELECT u_id, name FROM users";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					$userlist = array();
					$userlist[0] = "<i>Guest</i>";					// Add entry for Guest users
					while($row = mysql_fetch_array($result))
					{
						$userlist[$row['u_id']] = htmlspecialchars($row['name']);
					}
					mysql_free_result($result);
					
					// Values defining which slice of the logs to display
					$log_range = 20;								// How many entries to display per page
					if(isset($_GET['logpage']))						// Which page of entries to display
					{
						// Calculate start position if page number specified
						if(is_numeric($_GET['logpage'])) $log_start = $log_range * ($_GET['logpage'] - 1); else $log_start = 0;
					} else {
						// Otherwise default to start of list
						$log_start = 0;
					}
					
					// Fetch log entries from database and display them
					$query = "SELECT DATE_FORMAT(datetime, '%d/%m/%Y') AS date, TIME_FORMAT(datetime, '%k:%i') AS time,";
					$query .= " u_id, actiondata FROM logs ORDER BY datetime DESC LIMIT $log_start, $log_range";
					$result = @mysql_query($query);
					if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
					while($row = mysql_fetch_array($result))
					{ ?>
					<tr>
						<td><?php echo $row['date']; ?></td>
						<td><?php echo $row['time']; ?></td>
						<td><?php if(isset($userlist[$row['u_id']])) echo $userlist[$row['u_id']]; else echo "<i>Deleted</i>"; ?></td>
						<td><?php echo $row['actiondata']; ?></td>
					</tr><?php }
					mysql_free_result($result);
					echo "\n";
					?>
				</table>
			</p>
			<p><?php
				// Pagination
				// Fetch total number of log entries from database
				$query = "SELECT COUNT(log_id) AS total FROM logs";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				$row = mysql_fetch_array($result);
				mysql_free_result($result);
				
				// Output page selection bar
				echo "Page:&nbsp;\n";
				for($i = 1; $i <= ceil($row['total'] / $log_range); $i++)
				{
					if(($log_start / $log_range) + 1 == $i)
					{
						echo "<b><i>$i</i></b>&nbsp;\n";
					} else {
						echo "<a href=\"index.php?page=admin_logs&logpage=$i\">$i</a>&nbsp;\n";
					}
				}
				?>
			</p><?php echo "\n";
					break;
					
	// Competition Management Panel
				case 'comppanel': ?>
			<p><?php /* Output any messages */ echo $message; ?></p>
			<p>Please select an action:</p>
			<p>
				<table class="paneltable">
					<!-- Template Management -->
					<tr>
						<th class="sectionheader" colspan="3">Manage Competition Templates</th>
					</tr>
					<tr>
						<th class="itemlabel">Create new Competition Template</th>
						<form action="index.php?page=comppanel&sub=create_tem" method="post">
						<td><input type="text" class="clearOnFirstFocus" name="newtem_name" value="Enter a name" size="30" maxlength="50" id="templatecreate"></td>
						<td><button type="submit" id="btn_templatecreate" disabled="disabled">Create</button></td>
						</form>
					</tr>
					<tr>
						<th class="itemlabel">Edit existing Competition Template</th>
						<form action="index.php?page=comppanel&sub=edit_tem" method="post">
						<td>
							<select class="disallowOptionZero" name="edittem_name" id="templateedit">
								<option value="0" selected="selected" disabled="disabled">Select a template:</option><?php
								// Generate selection box options
								$query = "SELECT ct_id, name FROM compotemplates ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo "\n\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['ct_id']) . "\">" . htmlspecialchars($row['name']) . "</option>";
								}
								mysql_free_result($result);
								echo "\n";
								?>
							</select>
						</td>
						<td><button type="submit" id="btn_templateedit" disabled="disabled">Edit</button></td>
						</form>
					</tr>
					<tr>
						<th class="itemlabel">Delete existing Competition Template</th>
						<form action="index.php?page=comppanel&sub=delete_tem" method="post">
						<td>
							<select class="disallowOptionZero" name="deletetem_name" id="templatedelete">
								<option value="0" selected="selected" disabled="disabled">Select a template:</option><?php
								// Generate selection box options
								$query = "SELECT ct_id, name FROM compotemplates ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo "\n\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['ct_id']) . "\">" . htmlspecialchars($row['name']) . "</option>";
								}
								mysql_free_result($result);
								echo "\n";
								?>
							</select>
						</td>
						<td><button type="submit" id="btn_templatedelete" disabled="disabled">Delete</button></td>
						</form>
					</tr>
					<!-- Day Management -->
					<tr>
						<th class="sectionheader" colspan="3">Manage Days</th>
					</tr>
					<tr>
						<th class="itemlabel">Create new Day</th>
						<form action="index.php?page=comppanel&sub=create_day" method="post">
						<td>
						<input type="text" class="clearOnFirstFocus" name="newday_name" value="Enter a name" size="30" maxlength="50" id="daycreate"><br>
						<select name="newday_template">
							<option value="0" selected="selected" disabled="disabled">Choose a base template:</option><?php
							// Generate selection box options
							$query = "SELECT ct_id, name FROM compotemplates ORDER BY name ASC";
							$result = @mysql_query($query);
							while($row = mysql_fetch_array($result))
							{
								echo "\n\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['ct_id']) . "\">" . htmlspecialchars($row['name']) . "</option>";
							}
							mysql_free_result($result);
							echo "\n";
							?>
						</select>
						</td>
						<td><button type="submit" id="btn_daycreate" disabled="disabled">Create</button></td>
						</form>
					</tr>
					<tr>
						<th class="itemlabel">Edit existing Day</th>
						<form action="index.php?page=comppanel&sub=edit_day" method="post">
						<td>
							<select class="disallowOptionZero" name="editday_name" id="dayedit">
								<option value="0" selected="selected" disabled="disabled">Select a day:</option><?php
								// Generate selection box options
								$query = "SELECT d_id, name FROM days ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo "\n\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['d_id']) . "\">" . htmlspecialchars($row['name']) . "</option>";
								}
								mysql_free_result($result);
								echo "\n";
								?>
							</select>
						</td>
						<td><button type="submit" id="btn_dayedit" disabled="disabled">Edit</button></td>
						</form>
					</tr>
					<tr>
						<th class="itemlabel">Adjust components of existing Day</th>
						<form action="index.php?page=comppanel&sub=adjust_day" method="post">
						<td>
							<select class="disallowOptionZero" name="adjustday_name" id="dayadjust">
								<option value="0" selected="selected" disabled="disabled">Select a day:</option><?php
								// Generate selection box options
								$query = "SELECT d_id, name FROM days ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo "\n\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['d_id']) . "\">" . htmlspecialchars($row['name']) . "</option>";
								}
								mysql_free_result($result);
								echo "\n";
								?>
							</select>
						</td>
						<td><button type="submit" id="btn_dayadjust" disabled="disabled">Adjust</button></td>
						</form>
					</tr>
					<tr>
						<th class="itemlabel">Delete existing Day</th>
						<form action="index.php?page=comppanel&sub=delete_day" method="post">
						<td>
							<select class="disallowOptionZero" name="deleteday_name" id="daydelete">
								<option value="0" selected="selected" disabled="disabled">Select a day:</option><?php
								// Generate selection box options
								$query = "SELECT d_id, name FROM days ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo "\n\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['d_id']) . "\">" . htmlspecialchars($row['name']) . "</option>";
								}
								mysql_free_result($result);
								echo "\n";
								?>
							</select>
						</td>
						<td><button type="submit" id="btn_daydelete" disabled="disabled">Delete</button></td>
						</form>
					</tr>
					<!-- Set Active Day -->
					<tr>
						<th class="sectionheader" colspan="3">Set Active Day</th>
					</tr>
					<tr>
						<th class="itemlabel">Set which Day the system should use</th>
						<form action="index.php?page=comppanel&sub=set_activeday" method="post">
						<td>
							<select class="disallowOptionZero" name="setactiveday_name" id="activedayset">
								<option value="0" selected="selected" disabled="disabled">Select a day:</option><?php
								// Generate selection box options
								$query = "SELECT d_id, name, is_active_day FROM days ORDER BY name ASC";
								$result = @mysql_query($query);
								$optionselected = false;
								while($row = mysql_fetch_array($result))
								{
									echo "\n\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['d_id']) . "\"";
									if($row['is_active_day'] == 1)
									{
										echo " selected=\"selected\"";
										$optionselected = true;
									}
									echo ">" . htmlspecialchars($row['name']) . "</option>";
								}
								mysql_free_result($result);
								echo "\n";
								?>
							</select>
						</td>
						<td><button type="submit" id="btn_activedayset"<?php if(!$optionselected) echo " disabled=\"disabled\""; ?>>Set as Active Day</button></td>
						</form>
					</tr>
					<!-- Manage Records -->
					<tr>
						<th class="sectionheader" colspan="3">Manage Records</th>
					</tr>
					<tr>
						<th class="itemlabel">Select the Day to manage Records for:</th>
						<form action="index.php?page=comppanel&sub=manage_records" method="post">
						<td>
							<select class="disallowOptionZero" name="managerecords_name" id="recordsmanage">
								<option value="0" selected="selected" disabled="disabled">Select a day:</option><?php
								// Generate selection box options
								$query = "SELECT d_id, name FROM days ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo "\n\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['d_id']) . "\">" . htmlspecialchars($row['name']) . "</option>";
								}
								mysql_free_result($result);
								echo "\n";
								?>
							</select>
						</td>
						<td><button type="submit" id="btn_recordsmanage" disabled="disabled">Manage Records</button></td>
						</form>
					</tr>
				</table>
			</p><?php echo "\n";
					break;
					
	// Competition Management Panel >> Template Settings
				case 'comp_templatesettings': ?>
			<p>
				<form action="index.php?page=comp_templatesettings&ct_id=<?php echo htmlspecialchars($_GET['ct_id']); ?>" method="post">
					<fieldset>
						<legend>Enter Template settings, then click 'Save Changes'</legend>
						<?php /* Output any messages */ echo $message; ?>
						<!-- Basic Settings -->
						<table class="formtable">
							<tr>
								<th>Name:</th>
								<td><input type="text" name="name" value="<?php echo htmlspecialchars($v_name); ?>" size="30" maxlength="50"></td>
							</tr>
							<tr>
								<th>Maximum Events per Competitor:</th>
								<td><input type="text" name="max_events" value="<?php echo htmlspecialchars($v_max_events_per_competitor); ?>" size="3"></td>
							</tr>
						</table>
						<br>
						<!-- Teams -->
						<table class="formtable" id="tbl_ct_teams">
							<tr>
								<th colspan="2">Teams:</th>
							</tr>
							<tr>
								<td><i>Name</i></td>
								<td>&nbsp;</td>
							</tr>
						</table>
						<button type="button" id="btn_ct_addteam">Add New Team</button>
						<input type="hidden" id="teambuildertrigger">
						<script type="text/javascript">
							// Rebuild team list
							$("#teambuildertrigger").change(function(){<?php echo "\n$v_script_teambuilder"; ?>
							});
						</script>
						<br><br>
						<!-- Subgroups -->
						<table class="formtable" id="tbl_ct_subs">
							<tr>
								<th colspan="2">Subgroups:</th>
							</tr>
							<tr>
								<td><i>Name</i></td>
								<td>&nbsp;</td>
							</tr>
						</table>
						<button type="button" id="btn_ct_addsub">Add New Subgroup</button>
						<input type="hidden" id="subbuildertrigger">
						<script type="text/javascript">
							// Rebuild sub list
							$("#subbuildertrigger").change(function(){<?php echo "\n$v_script_subbuilder"; ?>
							});
						</script>
						<br><br>
						<!-- Scoring Schemes -->
						<table class="formtable" id="tbl_ct_schemes">
							<tr>
								<th colspan="6">Scoring Schemes:</th>
							</tr>
							<tr>
								<td><i>Name</i></td>
								<td><i>Entrants<br>per Team</i></td>
								<td><i>Good results<br>are</i></td>
								<td><i>Result type</i></td>
								<td><i>Units</i></td>
								<td><i>Minimum Decimal<br>Places Displayed</i></td>
								<td>&nbsp;</td>
							</tr>
						</table>
						<button type="button" id="btn_ct_addscheme">Add New Scoring Scheme</button>
						<input type="hidden" id="schemebuildertrigger">
						<input type="hidden" id="scorebuildertrigger">
						<script type="text/javascript">
							// Rebuild scheme list
							$("#schemebuildertrigger").change(function(){<?php echo "\n$v_script_schemebuilder"; ?>
							});
							
							// Rebuild score data
							$("#scorebuildertrigger").change(function(){<?php echo "\n$v_script_scorebuilder"; ?>
							});
						</script>
						<br><br>
						<!-- Events -->
						<table class="formtable" id="tbl_ct_events">
							<tr>
								<th colspan="5">Events:</th>
							</tr>
							<tr>
								<td><i>Name</i></td>
								<td><i>Entrants per Team</i></td>
								<td><i>Scoring Scheme</i></td>
								<td><i>Eligible Subgroups</i></td>
								<td><i>Counts to Max Events<br>per Competitor limit</i></td>
								<td>&nbsp;</td>
							</tr>
						</table>
						<button type="button" id="btn_ct_addevent">Add New Event</button>
						<input type="hidden" id="eventbuildertrigger">
						<script type="text/javascript">
							// Rebuild event list
							$("#eventbuildertrigger").change(function(){<?php echo "\n$v_script_eventbuilder"; ?>
							});
						</script>
						<br><br><br>
						<!-- Submit Button -->
						<button type="submit">Save Changes</button>
						<input type="hidden" name="submitted" value="TRUE">
					</fieldset>
				</form>
			</p><?php echo "\n";
					break;
					
	// Competition Management Panel >> Delete Template
				case 'comp_deletetemplate': ?>
			<p><?php
				if($del_done)
				{
					echo "\t\t\t\t$message";
				} else { ?>
				<form action="index.php?page=comp_deletetemplate&ct_id=<?php echo htmlspecialchars($_GET['ct_id']); ?>" method="post">
					<fieldset>
						<legend>Confirm Delete</legend>
						<?php /* Output any messages */ echo $message; ?>
						<table class="formtable">
							<tr>
								<th>Are you <i>sure</i> you want to delete the Template <i><?php echo htmlspecialchars($v_name); ?></i>?</th>
							</tr>
							<tr>
								<td>
									<input type="radio" name="confirm" value="Y" id="r_y"><label for="r_y">Yes, I'm sure I want to delete this</label><br>
									<input type="radio" name="confirm" value="N" id="r_n" checked="checked"><label for="r_n">No, take me back to the Competition Management Panel</label>
								</td>
							</tr>
							<tr>
								<td class="submitcell"><button type="submit">Make Choice</button></td>
							</tr>
						</table>
					</fieldset>
					<input type="hidden" name="submitted" value="TRUE">
				</form><?php } ?>
			</p><?php echo "\n";
					break;
					
	// Competition Management Panel >> Day Settings
				case 'comp_daysettings': ?>
			<p>
				<form action="index.php?page=comp_daysettings&d_id=<?php echo htmlspecialchars($_GET['d_id']); ?>" method="post">
					<fieldset>
						<legend>Enter Day settings, then click 'Save Changes'</legend>
						<?php /* Output any messages */ echo $message; ?>
						<!-- Basic Settings -->
						<table class="formtable">
							<tr>
								<th>Name:</th>
								<td><input type="text" name="name" value="<?php echo htmlspecialchars($v_name); ?>" size="30" maxlength="50"></td>
							</tr>
							<tr>
								<th>Year:</th>
								<td><input type="text" name="year" value="<?php echo htmlspecialchars($v_year); ?>" size="30"></td>
							</tr>
							<tr>
								<th>Import Records from:</th>
								<td>
									<select name="recordimport">
										<option value="0" selected="selected">-- Do not import records --</option><?php
										// Generate selection box options
										$query = "SELECT d_id, name FROM days WHERE d_id<>'" . mysql_real_escape_string($_GET['d_id']) . "'";
										$result = @mysql_query($query);
										while($row = mysql_fetch_array($result))
										{
											echo "\n\t\t\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['d_id']) . "\"";
											echo ">" . htmlspecialchars($row['name']) . "</option>";
										}
										mysql_free_result($result);
										echo "\n";
										?>
									</select>
									<br>
									<i>Note: This matches Records from the given day by name, leaving unmatchable Records blank.</i>
								</td>
							</tr>
						</table>
						<br>
						<hr>
						<br>
						<!-- Initial Scores -->
						<table class="formtable">
							<tr>
								<th colspan="2">Initial Team Scores:</th>
							</tr><?php
								// Generate score input area
								$query = "SELECT t_id, name, initscore FROM day_teams WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo "\n\t\t\t\t\t\t\t<tr>";
									echo "\n\t\t\t\t\t\t\t\t<th>" . htmlspecialchars($row['name']) . ":</th>";
									echo "\n\t\t\t\t\t\t\t\t<td><input type=\"text\" name=\"teamscore[" . htmlspecialchars($row['t_id']) . "]\" value=\"";
									// Output either submitted score if it exists, or otherwise database score
									if(isset($_POST['teamscore'][$row['t_id']]))
									{
										echo $_POST['teamscore'][$row['t_id']];
									} else {
										echo truncate_number($row['initscore']);
									}
									echo "\" size=\"5\"></td>";
									echo "\n\t\t\t\t\t\t\t</tr>";
								}
								mysql_free_result($result);
								echo "\n";
								?>
						</table>
						<br>
						<br>
						<!-- Competitors -->
						<table class="formtable">
							<tr>
								<th colspan="2">Competitors:</th>
							</tr><?php
								// Generate all competitor input boxes
								$v_buildscript = "";
								$next_cid_value = 0;
								$query = "SELECT t_id, name FROM day_teams WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
								$result = @mysql_query($query);
								// Loop through all teams
								while($row = mysql_fetch_array($result))
								{
									$query2 = "SELECT sub_id, name FROM day_subgroups WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
									$result2 = @mysql_query($query2);
									// Loop through all subgroups (for the current team)
									while($row2 = mysql_fetch_array($result2))
									{
										// Make build script - simulate click on add competitors button for this box
										$v_buildscript .= "\n\t\t\t\t\t\t\t\t$(\"#btn_d_addcompetitor" . htmlspecialchars($row['t_id']) . "_";
										$v_buildscript .= htmlspecialchars($row2['sub_id']) . "\").click();";
										// Add table header row
										echo "\n\t\t\t\t\t\t\t<tr colspan=\"2\">";
										echo "\n\t\t\t\t\t\t\t\t<th>" . htmlspecialchars($row['name']) . " - " . htmlspecialchars($row2['name']) . ":</th>";
										echo "\n\t\t\t\t\t\t\t</tr>";
										// Add table data row
										echo "\n\t\t\t\t\t\t\t<tr colspan=\"2\" id=\"datainputrow" . htmlspecialchars($row['t_id']) . "_";
										echo htmlspecialchars($row2['sub_id']) . "\">";
										$v_cidlinks = "\n\t\t\t\t\t\t\t\t\t&nbsp;";
										$v_existingnames = "";
										// Was data previously submitted?
										/*
										if(isset($_POST['submitted']) && isset($_POST['competitorname'][$row['t_id']][$row2['sub_id']]))
										{
											// Re-output names
											foreach($_POST['competitorname'][$row['t_id']][$row2['sub_id']] as $competitor)
											{
												if($competitor == "")
												{
													// Add blank line for empty names
													$v_existingnames .= " \n";
												} else {
													// Add name to box
													$v_existingnames .= htmlspecialchars($competitor) . "\n";
												}
											}
										}
										*/
										// Get names from database
										$query3 = "SELECT c_id, name FROM day_competitors WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
										$query3 .= " AND t_id='" . mysql_real_escape_string($row['t_id']) . "'";
										$query3 .= " AND sub_id='" . mysql_real_escape_string($row2['sub_id']) . "'";
										$result3 = @mysql_query($query3);
										while($row3 = mysql_fetch_array($result3))
										{
											// Generate new cid links
											$next_cid_value++;
											$v_cidlinks .= "\n\t\t\t\t\t\t\t\t\t<input type=\"hidden\" name=\"cidlink[$next_cid_value]\" value=\"";
											$v_cidlinks .= htmlspecialchars($row3['c_id']) . "\">";
											// Add name to box
											$v_existingnames .= htmlspecialchars($row3['name']) . "\n";
										}
										mysql_free_result($result3);
										// Generate form elements
										echo "\n\t\t\t\t\t\t\t\t<td><textarea rows=\"6\" cols=\"30\" name=\"competitorentry[" . htmlspecialchars($row['t_id']) . "][";
										echo htmlspecialchars($row2['sub_id']) . "]\" id=\"competitorentrybox" . htmlspecialchars($row['t_id']) . "_";
										echo htmlspecialchars($row2['sub_id']) . "\">$v_existingnames</textarea></td>";
										echo "\n\t\t\t\t\t\t\t</tr>";
										echo "\n\t\t\t\t\t\t\t<tr colspan=\"2\">";
										echo "\n\t\t\t\t\t\t\t\t<td><button type=\"button\" id=\"btn_d_addcompetitor" . htmlspecialchars($row['t_id']) . "_";
										echo htmlspecialchars($row2['sub_id']) . "\">Add Competitors</button></td>";
										echo "\n\t\t\t\t\t\t\t</tr>";
										echo "\n\t\t\t\t\t\t\t<tr colspan=\"2\">";
										echo "\n\t\t\t\t\t\t\t\t<td>";
										echo $v_cidlinks;
										echo "\n\t\t\t\t\t\t\t\t</td>";
										echo "\n\t\t\t\t\t\t\t</tr>";
									}
									mysql_free_result($result2);
								}
								mysql_free_result($result);
								echo "\n";
								?>
						</table>
						<input type="hidden" id="competitorlistbuildertrigger">
						<script type="text/javascript">
							$("#competitorlistbuildertrigger").change(function(){<?php echo $v_buildscript . "\n"; ?>
							});
						</script>
						<br><br>
						<!-- Submit Button -->
						<button type="submit">Save Changes</button>
						<input type="hidden" name="submitted" value="TRUE">
					</fieldset>
				</form>
			</p><?php echo "\n";
					break;
					
	// Competition Management Panel >> Adjust Day Components
				case 'comp_adjustday': ?>
			<p>
				<form action="index.php?page=comp_adjustday&d_id=<?php echo htmlspecialchars($_GET['d_id']); ?>" method="post">
					<fieldset>
						<legend>Adjust Day components, then click 'Save Changes'</legend>
						<?php /* Output any messages */ echo $message; ?>
						<i>Note: To ensure data integrity, you may only make adjustments that do not affect the <b>structure</b> of the data.</i><br><br>
						<!-- Teams -->
						<table class="formtable">
							<tr>
								<th>Teams:</th>
							</tr>
							<tr>
								<td><i>Name</i></td>
							</tr><?php
							// Generate team inputs
							$query = "SELECT t_id, name FROM day_teams WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
							$result = @mysql_query($query);
							if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
							while($row = mysql_fetch_array($result))
							{
								$v_name = isset($_POST['teamname'][$row['t_id']]) ? $_POST['teamname'][$row['t_id']] : $row['name'];
								echo "\n\t\t\t\t\t\t\t<tr>";
								echo "\n\t\t\t\t\t\t\t\t<td><input type=\"text\" name=\"teamname[" . htmlspecialchars($row['t_id']) . "]\" value=\"";
								echo htmlspecialchars($v_name) . "\" size=\"30\" maxlength=\"50\"></td>";
								echo "\n\t\t\t\t\t\t\t</tr>";
							}
							mysql_free_result($result);
							echo "\n";
							?>
						</table>
						<br><br>
						<!-- Subgroups -->
						<table class="formtable">
							<tr>
								<th>Subgroups:</th>
							</tr>
							<tr>
								<td><i>Name</i></td>
							</tr><?php
							// Generate subgroup inputs
							$query = "SELECT sub_id, name FROM day_subgroups WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
							$result = @mysql_query($query);
							if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
							while($row = mysql_fetch_array($result))
							{
								$v_name = isset($_POST['subname'][$row['sub_id']]) ? $_POST['subname'][$row['sub_id']] : $row['name'];
								echo "\n\t\t\t\t\t\t\t<tr>";
								echo "\n\t\t\t\t\t\t\t\t<td><input type=\"text\" name=\"subname[" . htmlspecialchars($row['sub_id']) . "]\" value=\"";
								echo htmlspecialchars($v_name) . "\" size=\"30\" maxlength=\"50\"></td>";
								echo "\n\t\t\t\t\t\t\t</tr>";
							}
							mysql_free_result($result);
							echo "\n";
							?>
						</table>
						<br><br>
						<!-- Scoring Schemes -->
						<table class="formtable">
							<tr>
								<th colspan="5">Scoring Schemes:</th>
							</tr>
							<tr>
								<td><i>Name</i></td>
								<td><i>Entrants<br>per Team</i></td>
								<td><i>Good results<br>are</i></td>
								<td><i>Result type</i></td>
								<td><i>Units</i></td>
								<td><i>Minimum Decimal<br>Places Displayed</i></td>								
							</tr><?php
							// Generate scoring scheme inputs
							$query = "SELECT ss_id, name, count_entrants_per_team, result_order, result_type, result_units, result_units_dp";
							$query .= " FROM day_scoreschemes WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
							$result = @mysql_query($query);
							if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
							while($row = mysql_fetch_array($result))
							{
								# Scheme data
								$v_name = isset($_POST['schemename'][$row['ss_id']]) ? $_POST['schemename'][$row['ss_id']] : $row['name'];
								$v_result_order = isset($_POST['schemeresultorder'][$row['ss_id']]) ? $_POST['schemeresultorder'][$row['ss_id']] : $row['result_order'];
								$v_result_type = isset($_POST['schemeresulttype'][$row['ss_id']]) ? $_POST['schemeresulttype'][$row['ss_id']] : $row['result_type'];
								$v_result_units = isset($_POST['schemeresultunit'][$row['ss_id']]) ? $_POST['schemeresultunit'][$row['ss_id']] : $row['result_units'];
								$v_result_units_dp = isset($_POST['schemeresultunitdp'][$row['ss_id']]) ? $_POST['schemeresultunitdp'][$row['ss_id']] : $row['result_units_dp'];
								echo "\n\t\t\t\t\t\t\t<tr>";
								echo "\n\t\t\t\t\t\t\t\t<td><input type=\"text\" name=\"schemename[" . htmlspecialchars($row['ss_id']) . "]\" value=\"";
								echo htmlspecialchars($v_name) . "\" size=\"20\" maxlength=\"50\"></td>";
								echo "\n\t\t\t\t\t\t\t\t<td>" . htmlspecialchars($row['count_entrants_per_team']) . "</td>";
								echo "\n\t\t\t\t\t\t\t\t<td><select name=\"schemeresultorder[" . htmlspecialchars($row['ss_id']) . "]\">";
								echo "<option value=\"desc\"";
								if($v_result_order == "desc") echo " selected=\"selected\"";
								echo ">High</option><option value=\"asc\"";
								if($v_result_order == "asc") echo " selected=\"selected\"";
								echo ">Low</option></select></td>";
								echo "\n\t\t\t\t\t\t\t\t<td><input type=\"text\" name=\"schemeresulttype[" . htmlspecialchars($row['ss_id']) . "]\" value=\"";
								echo htmlspecialchars($v_result_type) . "\" size=\"15\" maxlength=\"30\"></td>";
								echo "\n\t\t\t\t\t\t\t\t<td><input type=\"text\" name=\"schemeresultunit[" . htmlspecialchars($row['ss_id']) . "]\" value=\"";
								echo htmlspecialchars($v_result_units) . "\" size=\"10\" maxlength=\"20\"></td>";
								echo "\n\t\t\t\t\t\t\t\t<td><input type=\"text\" name=\"schemeresultunitdp[" . htmlspecialchars($row['ss_id']) . "]\" value=\"";
								echo htmlspecialchars($v_result_units_dp) . "\" size=\"3\"></td>";
								echo "\n\t\t\t\t\t\t\t</tr>";
								# Scheme scores
								echo "\n\t\t\t\t\t\t\t<tr>";
								echo "\n\t\t\t\t\t\t\t\t<td colspan=\"6\">";
								$query2 = "SELECT sssc_id, place, score FROM day_scorescheme_scores WHERE ss_id='" . mysql_real_escape_string($row['ss_id']) . "'";
								$query2 .= " AND place<>'0' ORDER BY place ASC";
								$result2 = @mysql_query($query2);
								if(!$result2) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
								while($row2 = mysql_fetch_array($result2))
								{
									$v_score = isset($_POST['score'][$row2['sssc_id']]) ? $_POST['score'][$row2['sssc_id']] : truncate_number($row2['score']);
									echo htmlspecialchars($row2['place']) . "<sup>" . getNumberSuffix($row2['place']) . "</sup> ";
									echo "<input type=\"text\" name=\"score[" . htmlspecialchars($row2['sssc_id']) . "]\"";
									echo " value=\"" . htmlspecialchars($v_score) . "\" size=\"1\"> ";
								}
								mysql_free_result($result2);
								$query2 = "SELECT sssc_id, score FROM day_scorescheme_scores WHERE ss_id='" . mysql_real_escape_string($row['ss_id']) . "' AND place='0' LIMIT 1";
								$result2 = @mysql_query($query2);
								if(!$result2) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
								$row2 = mysql_fetch_array($result2);
								$v_score = isset($_POST['score'][$row2['sssc_id']]) ? $_POST['score'][$row2['sssc_id']] : truncate_number($row2['score']);
								echo "DNC <input type=\"text\" name=\"score[0]\" value=\"";
								echo htmlspecialchars($v_score) . "\" size=\"1\">";
								mysql_free_result($result2);
								echo "</td>\n\t\t\t\t\t\t\t</tr>";
							}
							mysql_free_result($result);
							echo "\n";
							?>
						</table>
						<br><br>
						<!-- Events -->
						<table class="formtable">
							<tr>
								<th colspan="4">Events:</th>
							</tr>
							<tr>
								<td><i>Name</i></td>
								<td><i>Entrants per Team</i></td>
								<td><i>Scoring Scheme</i></td>
								<td><i>Eligible Subgroups</i></td>
								<td><i>Counts to Max Events<br>per Competitor limit</i></td>
							</tr><?php
							// Generate event inputs
							$query = "SELECT day_events.e_id AS e_id, day_events.name AS name, day_scoreschemes.name AS ss_name, day_scoreschemes.count_entrants_per_team AS ss_ept,";
							$query .= " day_events.counts_to_limit AS counts_to_limit FROM day_events, day_scoreschemes WHERE day_events.ss_id=day_scoreschemes.ss_id";
							$query .= " AND day_events.d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
							$result = @mysql_query($query);
							if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
							while($row = mysql_fetch_array($result))
							{
								$v_name = isset($_POST['eventname'][$row['e_id']]) ? $_POST['eventname'][$row['e_id']] : $row['name'];
								if(isset($_POST['submitted']))
								{
									$v_counts_to_limit = isset($_POST['eventcountstolimit'][$row['e_id']]) ? 1 : 0;
								} else {
									$v_counts_to_limit = $row['counts_to_limit'];
								}
								echo "\n\t\t\t\t\t\t\t<tr>";
								echo "\n\t\t\t\t\t\t\t\t<td><input type=\"text\" name=\"eventname[" . htmlspecialchars($row['e_id']) . "]\" value=\"";
								echo htmlspecialchars($v_name) . "\" size=\"20\" maxlength=\"50\"></td>";
								echo "\n\t\t\t\t\t\t\t\t<td>" . htmlspecialchars($row['ss_ept']) . "</td>";
								echo "\n\t\t\t\t\t\t\t\t<td>" . htmlspecialchars($row['ss_name']) . "</td>";
								echo "\n\t\t\t\t\t\t\t\t<td>";
								$query2 = "SELECT day_subgroups.name AS name FROM day_subgroups, day_groupeligibility";
								$query2 .= " WHERE day_subgroups.sub_id=day_groupeligibility.sub_id AND day_groupeligibility.e_id='" . mysql_real_escape_string($row['e_id']) . "'";
								$result2 = @mysql_query($query2);
								if(!$result2) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
								$i = false;
								while($row2 = mysql_fetch_array($result2))
								{
									if($i) echo ", ";
									echo htmlspecialchars($row2['name']);
									$i = true;
								}
								mysql_free_result($result2);
								echo "</td>";
								echo "\n\t\t\t\t\t\t\t\t<td><td><input type=\"checkbox\" name=\"eventcountstolimit[" . htmlspecialchars($row['e_id']) . "]\"";
								if($v_counts_to_limit == 1) echo "checked=\"checked\"";
								echo "></td>";
								echo "\n\t\t\t\t\t\t\t</tr>";
							}
							mysql_free_result($result);
							echo "\n";
							?>
						</table>
						<br><br><br>
						<button type="submit">Save Changes</button>
						<input type="hidden" name="submitted" value="TRUE">
					</fieldset>
				</form>
			</p><?php echo "\n";
					break;
					
	// Competition Management Panel >> Delete Day
				case 'comp_deleteday': ?>
			<p><?php
				if($del_done)
				{
					echo "\t\t\t\t$message";
				} else { ?>
				<form action="index.php?page=comp_deleteday&d_id=<?php echo htmlspecialchars($_GET['d_id']); ?>" method="post">
					<fieldset>
						<legend>Confirm Delete</legend>
						<?php /* Output any messages */ echo $message; ?>
						<table class="formtable">
							<tr>
								<th>Are you <i>sure</i> you want to delete the Day <i><?php echo htmlspecialchars($v_name); ?></i>?</th>
							</tr>
							<tr>
								<td>
									<input type="radio" name="confirm" value="Y" id="r_y"><label for="r_y">Yes, I'm sure I want to delete this</label><br>
									<input type="radio" name="confirm" value="N" id="r_n" checked="checked"><label for="r_n">No, take me back to the Competition Management Panel</label>
								</td>
							</tr>
							<tr>
								<td class="submitcell"><button type="submit">Make Choice</button></td>
							</tr>
						</table>
					</fieldset>
					<input type="hidden" name="submitted" value="TRUE">
				</form><?php } ?>
			</p><?php echo "\n";
					break;
					
	// Competition Management Panel >> Manage Records
				case 'comp_managerecords': ?>
			<p>
				<form action="index.php?page=comp_managerecords&d_id=<?php echo htmlspecialchars($_GET['d_id']); ?>" method="post">
					<fieldset>
						<legend>Select an Event to edit the Record for</legend>
						<table class="formtable">
							<tr>
								<th>Event:</th>
								<td>
									<select name="event" id="drop_mr_eventpicker"><?php
									// Generate selection box options
									$query = "SELECT e_id, name FROM day_events WHERE d_id='" . mysql_real_escape_string($_GET['d_id']) . "'";
									$result = @mysql_query($query);
									while($row = mysql_fetch_array($result))
									{
										echo "\n\t\t\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['e_id']) . "\"";
										if(isset($_POST['event']))
										{
											if($_POST['event'] == $row['e_id']) echo " selected=\"selected\"";
										}
										echo ">" . htmlspecialchars($row['name']) . "</option>";
									}
									mysql_free_result($result);
									echo "\n"; ?>
									</select>
								</td>
							</tr>
						</table>
						<br>
						<hr>
						<br>
						<?php /* Output any messages */ echo $message; ?>
						Enter new Record and click 'Save Changes':
						<br>
						<table class="formtable">
							<tr>
								<th>Name:</th>
								<td><input type="text" name="name" size="30" maxlength="50" id="mr_name"></td>
							</tr>
							<tr>
								<th>Team:</th>
								<td><input type="text" name="team" size="30" maxlength="50" id="mr_team"></td>
							</tr>
							<tr>
								<th>Score:</th>
								<td><input type="text" name="score" size="30" id="mr_score"></td>
							</tr>
							<tr>
								<th>Year Set:</th>
								<td><input type="text" name="yearset" size="30" id="mr_yearset"></td>
							</tr>
							<tr>
								<td class="submitcell" colspan="2"><button type="submit">Save Changes</button></td>
							</tr>
						</table>
					</fieldset>
					<input type="hidden" name="submitted" value="TRUE">
				</form>
			</p><?php echo "\n";
					break;
					
	// Score Entry
				case 'scoreentry': ?>
			<p><?php
			// Obtain overall statistics for score entry completion
			// Not done
			$query = "SELECT COUNT(e_id) AS total FROM day_events WHERE d_id='" . mysql_real_escape_string($curactivedayid) . "' AND scoring_status='not_done'";
			$result = @mysql_query($query);
			if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
			$row = mysql_fetch_array($result);
			$countscores_notdone = $row['total'];
			mysql_free_result($result);
			
			// Done wrong
			$query = "SELECT COUNT(e_id) AS total FROM day_events WHERE d_id='" . mysql_real_escape_string($curactivedayid) . "' AND scoring_status='done_wrong'";
			$result = @mysql_query($query);
			if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
			$row = mysql_fetch_array($result);
			$countscores_donewrong = $row['total'];
			mysql_free_result($result);
			
			// Done correct
			$query = "SELECT COUNT(e_id) AS total FROM day_events WHERE d_id='" . mysql_real_escape_string($curactivedayid) . "' AND scoring_status='done_correct'";
			$result = @mysql_query($query);
			if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
			$row = mysql_fetch_array($result);
			$countscores_donecorrect = $row['total'];
			mysql_free_result($result);
			
			// Total
			$countscores_total = $countscores_notdone + $countscores_donewrong + $countscores_donecorrect;
			echo "\n"; ?>
				<table class="datatable">
					<tr class="green">
						<th>Events scored correctly:</th>
						<td class="wide"><?php echo htmlspecialchars($countscores_donecorrect); ?></td>
						<td class="wide">(<?php echo round(100 * $countscores_donecorrect / $countscores_total, 0); ?>%)</td>
					</tr>
					<tr class="red">
						<th>Events scored incorrectly:</th>
						<td class="wide"><?php echo htmlspecialchars($countscores_donewrong); ?></td>
						<td class="wide">(<?php echo round(100 * $countscores_donewrong / $countscores_total, 0); ?>%)</td>
					</tr>
					<tr class="lightgrey">
						<th>Events not yet scored:</th>
						<td class="wide"><?php echo htmlspecialchars($countscores_notdone); ?></td>
						<td class="wide">(<?php echo round(100 * $countscores_notdone / $countscores_total, 0); ?>%)</td>
					</tr>
					<tr class="darkgrey">
						<th>Total number of Events:</th>
						<td class="wide"><b><?php echo htmlspecialchars($countscores_total); ?></b></td>
						<td class="wide"><b>(100%)</b></td>
					</tr>
				</table>
			</p>
			<br>
			<p><?php
			// Count number of teams
			$query = "SELECT COUNT(t_id) AS total FROM day_teams WHERE d_id='" . mysql_real_escape_string($curactivedayid) . "'";
			$result = @mysql_query($query);
			if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
			$row = mysql_fetch_array($result);
			$count_teams = $row['total'];
			mysql_free_result($result);
			echo "\n"; ?>
				Choose a score to edit:
				<table class="datatable bordered"><?php
				// Output header row
				echo "\n\t\t\t\t\t<tr class=\"darkgrey\">";
				echo "\n\t\t\t\t\t\t<th>Event</th>";
				for($i = 1; $i <= $count_teams; $i++)
				{
					echo "\n\t\t\t\t\t\t<th>$i<sup>" . getNumberSuffix($i) . "</sup></th>";
				}
				echo "\n\t\t\t\t\t</tr>";
				// Output data rows
				$query = "SELECT e_id, name, scoring_status FROM day_events WHERE d_id='" . mysql_real_escape_string($curactivedayid) . "'";
				$result = @mysql_query($query);
				if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
				while($row = mysql_fetch_array($result))
				{
					echo "\n\t\t\t\t\t<tr class=\"";
					if($row['scoring_status'] == 'not_done') echo "lightgrey";
					if($row['scoring_status'] == 'done_wrong') echo "red";
					if($row['scoring_status'] == 'done_correct') echo "green";
					echo "\">\n\t\t\t\t\t\t<td><a href=\"index.php?page=scoreentry_editscore&e_id=" . htmlspecialchars($row['e_id']) . "\">";
					echo htmlspecialchars($row['name']) . "</a></td>";
					$teamresults = get_event_teamresults($row['e_id']);
					for($i = 1; $i <= $count_teams; $i++)
					{
						echo "\n\t\t\t\t\t\t<td>";
						if($row['scoring_status'] != 'not_done')
						{
							echo htmlspecialchars($teamresults[$i]['name']) . " <i>(" . htmlspecialchars($teamresults[$i]['score']);
							echo ")</i> <i>" . $teamresults[$i]['tiedposition'] . "</i>";
						}
						echo "</td>";
					}
					echo "\n\t\t\t\t\t</tr>";
				}
				mysql_free_result($result);
				echo "\n"; ?>
				</table>
			</p><?php echo "\n";
					break;
					
	// Score Entry >> Edit Event Scores
				case 'scoreentry_editscore': ?>
			<p>
				<form action="index.php?page=scoreentry_editscore&e_id=<?php echo htmlspecialchars($_GET['e_id']); ?>" method="post">
					<fieldset>
						<legend>Enter the new scores for each team</legend>
						<?php
						/* Output any messages */ echo $message;
						
						// Get event scoring status from database
						$query = "SELECT scoring_status FROM day_events WHERE e_id='" . mysql_real_escape_string($_GET['e_id']) . "' LIMIT 1";
						$result = @mysql_query($query);
						if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
						$row = mysql_fetch_array($result);
						
						// Output scoring status message bar
						echo "\n\t\t\t\t\t\t<table class=\"datatable\">";
						echo "\n\t\t\t\t\t\t\t<tr class=\"";
						if($row['scoring_status'] == "not_done") echo "lightgrey";
						if($row['scoring_status'] == "done_wrong") echo "red";
						if($row['scoring_status'] == "done_correct") echo "green";
						echo "\">";
						echo "\n\t\t\t\t\t\t\t\t<td><b>This Event has ";
						if($row['scoring_status'] == "not_done") echo "not yet been scored.";
						if($row['scoring_status'] == "done_wrong") echo "been scored, but contains inconsistent data that needs correcting.";
						if($row['scoring_status'] == "done_correct") echo "has been scored consistently.";
						echo "</b></td>";
						echo "\n\t\t\t\t\t\t\t</tr>";
						echo "\n\t\t\t\t\t\t</table>\n\t\t\t\t\t\t<br>\n";
						mysql_free_result($result);
						?>
						<table class="formtable"><?php
							// Get scoring scheme data from database
							$query = "SELECT day_scoreschemes.count_entrants_per_team AS entrants_per_team, day_scoreschemes.result_order AS result_order, ";
							$query .= "day_scoreschemes.result_type AS result_type, day_scoreschemes.result_units AS result_units, day_scoreschemes.result_units_dp AS result_units_dp";
							$query .= " FROM day_scoreschemes, day_events";
							$query .= " WHERE day_scoreschemes.ss_id=day_events.ss_id AND day_events.e_id='" . mysql_real_escape_string($_GET['e_id']) . "'";
							$query .= " LIMIT 1";
							$result = @mysql_query($query);
							if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
							$row = mysql_fetch_array($result);
							$scorescheme_entrantsperteam = $row['entrants_per_team'];
							$scorescheme_resultorder = $row['result_order'];
							$scorescheme_resulttype = $row['result_type'];
							$scorescheme_resultunits = $row['result_units'];
							$scorescheme_resultunits_dp = $row['result_units_dp'];
							mysql_free_result($result);
							
							// Get scoring scheme score data from database
							$query = "SELECT day_scorescheme_scores.place AS place, day_scorescheme_scores.score AS score";
							$query .= " FROM day_scorescheme_scores, day_events";
							$query .= " WHERE day_scorescheme_scores.ss_id=day_events.ss_id AND day_events.e_id='" . mysql_real_escape_string($_GET['e_id']) . "'";
							$result = @mysql_query($query);
							if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
							while($row = mysql_fetch_array($result))
							{
								$scorescheme_score[$row['place']] = $row['score'];
							}
							mysql_free_result($result);
							
							// Get subgroup eligibilties from database
							$query = "SELECT day_groupeligibility.sub_id AS sub_id, day_subgroups.name AS name FROM day_groupeligibility, day_subgroups";
							$query .= " WHERE day_groupeligibility.e_id='" . mysql_real_escape_string($_GET['e_id']) . "'";
							$query .= " AND day_groupeligibility.sub_id=day_subgroups.sub_id";
							$result = @mysql_query($query);
							if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
							while($row = mysql_fetch_array($result))
							{
								$subgrouplist[$row['sub_id']] = $row['name'];
							}
							mysql_free_result($result);
							
							// Get competitor data from database
							$query = "SELECT day_competitors.c_id AS c_id, day_competitors.name AS name, day_competitors.t_id AS t_id, day_competitors.sub_id AS sub_id";
							$query .= " FROM day_competitors, day_events";
							$query .= " WHERE day_competitors.d_id=day_events.d_id AND day_events.e_id='" . mysql_real_escape_string($_GET['e_id']) . "'";
							$query .= " ORDER BY day_competitors.name ASC";
							$result = @mysql_query($query);
							if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
							while($row = mysql_fetch_array($result))
							{
								$competitorlist[$row['c_id']]['name'] = $row['name'];
								$competitorlist[$row['c_id']]['t_id'] = $row['t_id'];
								$competitorlist[$row['c_id']]['sub_id'] = $row['sub_id'];
							}
							mysql_free_result($result);
							
							// Get team list from database
							$query = "SELECT t_id, name FROM day_teams WHERE d_id='" . mysql_real_escape_string($curactivedayid) . "'";
							$result = @mysql_query($query);
							if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
							while($row = mysql_fetch_array($result))
							{
								// Output team's scoring rows
								// Header row
								echo "\n\t\t\t\t\t\t\t<tr>";
								echo "\n\t\t\t\t\t\t\t\t<th colspan=\"3\">{$row['name']}</th>";
								echo "\n\t\t\t\t\t\t\t</tr>";
								echo "\n\t\t\t\t\t\t\t<tr>";
								echo "\n\t\t\t\t\t\t\t\t<td><i>Position</i></td>";
								echo "\n\t\t\t\t\t\t\t\t<td><i>Competitor</i></td>";
								echo "\n\t\t\t\t\t\t\t\t<td><i>" . htmlspecialchars($scorescheme_resulttype) . " (" . htmlspecialchars($scorescheme_resultunits) . ")</i></td>";
								echo "\n\t\t\t\t\t\t\t</tr>";
								// Input rows - generated with current positions already selected if they exist
								$query2 = "SELECT c_id, place, result FROM day_scores";
								$query2 .= " WHERE t_id='" . mysql_real_escape_string($row['t_id']) . "'";
								$query2 .= " AND e_id='" . mysql_real_escape_string($_GET['e_id']) . "'";
								$result2 = @mysql_query($query2);
								if(!$result2) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
								for($i = 0; $i < $scorescheme_entrantsperteam; $i++)
								{
									$row2 = mysql_fetch_array($result2);
									$v_score_place = $row2['place'];
									$v_score_c_id = $row2['c_id'];
									$v_score_result = $row2['result'];
									if(isset($_POST['position'][$row['t_id']][$i])) $v_score_place = $_POST['position'][$row['t_id']][$i];
									if(isset($_POST['competitor'][$row['t_id']][$i])) $v_score_c_id = $_POST['competitor'][$row['t_id']][$i];
									if(isset($_POST['result'][$row['t_id']][$i])) $v_score_result = $_POST['result'][$row['t_id']][$i];
									echo "\n\t\t\t\t\t\t\t<tr>";
									echo "\n\t\t\t\t\t\t\t\t<td>";
									echo "\n\t\t\t\t\t\t\t\t\t<select name=\"position[" . htmlspecialchars($row['t_id']) . "][$i]\">";
									echo "\n\t\t\t\t\t\t\t\t\t\t<option></option>";
									foreach($scorescheme_score as $placekey => $scorevalue)
									{
										echo "\n\t\t\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($placekey) . "\"";
										if(strval($placekey) == strval($v_score_place)) echo " selected=\"selected\"";
										echo ">";
										if($placekey == 0)
										{
											echo "DNC";
										} else {
											echo htmlspecialchars($placekey) . getNumberSuffix($placekey);
										}
										echo "</option>";
									}
									echo "\n\t\t\t\t\t\t\t\t\t</select>";
									echo "\n\t\t\t\t\t\t\t\t</td>";
									echo "\n\t\t\t\t\t\t\t\t<td>";
									echo "\n\t\t\t\t\t\t\t\t\t<select name=\"competitor[" . htmlspecialchars($row['t_id']) . "][$i]\"";
									echo " id=\"drop_es_competitor" . htmlspecialchars($row['t_id']) . "_$i\">";
									echo "\n\t\t\t\t\t\t\t\t\t\t<option value=\"0\"></option>";
									echo "\n\t\t\t\t\t\t\t\t\t\t<option value=\"-1\">-- Missing From List --</option>";
									foreach($competitorlist as $cidkey => $competitor)
									{
										if($competitor['t_id'] == $row['t_id'] && isset($subgrouplist[$competitor['sub_id']]))
										{
											echo "\n\t\t\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($cidkey) . "\"";
											if($cidkey == $v_score_c_id) echo " selected=\"selected\"";
											echo ">" . htmlspecialchars($competitor['name']) . "</option>";
										}
									}
									echo "\n\t\t\t\t\t\t\t\t\t</select>";
									echo "\n\t\t\t\t\t\t\t\t</td>";
									echo "\n\t\t\t\t\t\t\t\t<td><input type=\"text\" name=\"result[" . htmlspecialchars($row['t_id']) . "][$i]\" value=\"";
									echo truncate_number($v_score_result, $scorescheme_resultunits_dp) . "\" size=\"5\"></td>";
									echo "\n\t\t\t\t\t\t\t</tr>";
									echo "\n\t\t\t\t\t\t\t<tr id=\"tr_es_newcomp" . htmlspecialchars($row['t_id']) . "_$i\" style=\"display: none\">";
									echo "\n\t\t\t\t\t\t\t\t<td>&nbsp;</td>";
									echo "\n\t\t\t\t\t\t\t\t<td colspan=\"2\">";
									echo "Name: <input type=\"text\" name=\"newcomp_name[" . htmlspecialchars($row['t_id']) . "][$i]\" size=\"30\" maxlength=\"50\"><br>";
									echo "Subgroup: <select name=\"newcomp_group[" . htmlspecialchars($row['t_id']) . "][$i]\">";
									if(count($subgrouplist) > 1) echo "<option selected=\"selected\" disabled=\"disabled\"></option>";
									foreach($subgrouplist as $groupid => $groupname)
									{
										echo "<option value=\"" . htmlspecialchars($groupid) . "\">" . htmlspecialchars($groupname) . "</option>";
									}
									echo "</select></td>";
									echo "\n\t\t\t\t\t\t\t</tr>";
								}
								mysql_free_result($result2);
								echo "\n\t\t\t\t\t\t\t<tr><td>&nbsp;</td></tr>";
							}
							mysql_free_result($result);
							echo "\n"; ?>
							<tr>
								<td class="submitcell" colspan="3"><button type="submit">Save Changes</button></td>
							</tr>
						</table>
					</fieldset>
					<input type="hidden" name="submitted" value="TRUE">
				</form>
			</p><?php echo "\n";
					break;
					
	// Live Results
				case 'scoreviewer': ?>
			<!-- Control Panel -->
			<p>
				<form method="post" id="lr_refresh_form">
					Auto-refresh every: <input type="text" value="5" size="3" id="lr_autorefreshtime"> seconds
					<br>
					<button type="button" id="lr_forcerefresh_button">Manual Update</button>
				</form>
			</p>
			<!-- Current Scores -->
			<div class="resultcontainer">
				<a class="headerlink" href="#" id="sv_header1">Current Scores</a>
				<div class="contentbox" id="sv_content1">
					<input type="hidden" <?php echo "value=\"" . htmlspecialchars($curactivedayid) . "\""; ?> id="lr_totalscore_updatetrigger">
					<table class="datatable bordered">
						<tr class="darkgrey">
							<th>&nbsp;</th>
							<th>Team</th>
							<th>Prior Score</th>
							<th>Current Score</th>
							<th>Total Score</th>
						</tr><?php
						// Make enough rows for all teams
						$query = "SELECT COUNT(t_id) AS teamcount FROM day_teams WHERE d_id='" . mysql_real_escape_string($curactivedayid) . "' LIMIT 1";
						$result = @mysql_query($query);
						if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
						$row = mysql_fetch_array($result);
						for($i = 0; $i < $row['teamcount']; $i++)
						{
							echo "\n"; ?>
						<tr>
							<th class="darkgrey" id=<?php echo "lr_totalscore_cell" . ($i * 5 + 0); ?>></th>
							<td <?php echo "id=\"lr_totalscore_cell" . ($i * 5 + 1) . "\""; ?>></td>
							<td <?php echo "id=\"lr_totalscore_cell" . ($i * 5 + 2) . "\""; ?>></td>
							<td <?php echo "id=\"lr_totalscore_cell" . ($i * 5 + 3) . "\""; ?>></td>
							<td <?php echo "id=\"lr_totalscore_cell" . ($i * 5 + 4) . "\""; ?>></td>
						</tr><?php
						}
						mysql_free_result($result);
						?>
					</table>
				</div>
			</div>
			<br>
			<!-- New Records -->
			<div class="resultcontainer">
				<a class="headerlink" href="#" id="sv_header2">New Records Set</a>
				<div class="contentbox" id="sv_content2">
					<input type="hidden" <?php echo "value=\"" . htmlspecialchars($curactivedayid) . "\""; ?> id="lr_newrecord_updatetrigger">
					<ul id="lr_newrecord_list">
					</ul>
				</div>
			</div>
			<br>
			<!-- Individual High Scores -->
			<div class="resultcontainer">
				<a class="headerlink" href="#" id="sv_header3">Current Highest Individual Scores</a>
				<div class="contentbox" id="sv_content3">
					<input type="hidden" <?php echo "value=\"" . htmlspecialchars($curactivedayid) . "\""; ?> id="lr_highscore_updatetrigger">
					<table class="datatable bordered">
						<tr class="darkgrey">
							<th>&nbsp;</th>
							<th>Name</th>
							<th>Score</th>
							<th>Team</th>
						</tr><?php
						// Make row for each subgroup
						$query = "SELECT name FROM day_subgroups WHERE d_id='" . mysql_real_escape_string($curactivedayid) . "' ORDER BY sub_id ASC";
						$result = @mysql_query($query);
						if(!$result) exit('FATAL ERROR: An unexpected database error occurred.<br><br>' . mysql_error());
						$i = 0;
						while($row = mysql_fetch_array($result))
						{
							echo "\n"; ?>
						<tr>
							<th class="darkgrey"><?php echo htmlspecialchars($row['name']); ?></th>
							<td <?php echo "id=\"lr_highscore_cell" . ($i * 3 + 0) . "\""; ?>></td>
							<td <?php echo "id=\"lr_highscore_cell" . ($i * 3 + 1) . "\""; ?>></td>
							<td <?php echo "id=\"lr_highscore_cell" . ($i * 3 + 2) . "\""; ?>></td>
						</tr><?php
							$i++;
						}
						mysql_free_result($result);
						?>
					</table>
				</div>
			</div>
			<br>
			<!-- Event Scoring Progress -->
			<div class="progressbar">
				<input type="hidden" <?php echo "value=\"" . htmlspecialchars($curactivedayid) . "\""; ?> id="lr_scoreprogress_updatetrigger">
				<div class="progressbarfill" id="lr_scoreprogress_bar" style="width: 0%;">
					Events Scored: 0 / 0 (0%)
				</div>
			</div>
			<br>
			<!-- Current Event Scores -->
			<div class="resultcontainer">
				<a class="headerlink" href="#" id="sv_header4">Current Event Scores (click an Event for more detail)</a>
				<div class="contentbox" id="sv_content4">
					<input type="hidden" <?php echo "value=\"" . htmlspecialchars($curactivedayid) . "\""; ?> id="lr_eventscore_updatetrigger">
					<table class="datatable bordered" id="lr_eventscore_table">
					</table>
				</div>
			</div><?php echo "\n";
					break;
					
	// Statistical Analysis Panel
				case 'statspanel': ?>
			<p>
				Select what you wish to analyse:
			</p>
			<p>
				<table class="optiontable">
					<tr><th><a href="index.php?page=stats_days">Analyse Days</a></th></tr>
					<tr>
						<td>Compare side-by-side an overview of results from several Days, including Team scores, comparisons between Events and summary statistics.</td>
					</tr>
					<tr><th><a href="index.php?page=stats_events">Analyse Events</a></th></tr>
					<tr>
						<td>Compare side-by-side the results from several Events, including those from different Days.</td>
					</tr>
					<tr><th><a href="index.php?page=stats_teams">Analyse Teams</a></th></tr>
					<tr>
						<td>Compare side-by-side a summary of the performance of different Teams, including Teams that competed on different Days.</td>
					</tr>
					<tr><th><a href="index.php?page=stats_competitors">Analyse Competitors</a></th></tr>
					<tr>
						<td>Compare side-by-side a summary of the performance of several Competitors across different Days and different Events.</td>
					</tr>
					<tr><th><a href="index.php?page=stats_records">Analyse Records</a></th></tr>
					<tr>
						<td>Compare how Records have progressed across one or several Days.</td>
					</tr>
					<tr><th><a href="index.php?page=stats_scoresheet">Produce Scoresheet</a></th></tr>
					<tr>
						<td>Produce a scoresheet of Teams against Subgroups.</td>
					</tr>
				</table>
			</p><?php echo "\n";
					break;
					
	// Statistical Analysis Panel >> Analyse Days
				case 'stats_days':
					/* Output any messages */ echo $message;
					?>
			<p>
				<form action="index.php?page=stats_days" method="post">
					<fieldset>
						<legend>Select which Days to analyse</legend>
						<table class="formtable" id="tbl_stat_days">
						</table>
						<button type="button" id="btn_stat_addday">Add</button>
						<br><br>
						<button type="submit" id="btn_gen_norm">Generate New Analysis Table (below)</button>
						<button type="submit" id="btn_gen_doc">Generate New Analysis Table (as Word document)</button>
						<button type="submit" id="btn_gen_spread">Generate New Analysis Table (as Excel spreadsheet)</button>
						<input type="hidden" name="outputmode" value="normal" id="hdn_outputmode">
						<input type="hidden" name="submitted" value="TRUE">
						<input type="hidden" id="statspanelbuildertrigger">
						<script type="text/javascript">
							// Dynamic form data
							statday_dayoptions = '<option value="0" selected="selected" disabled="disabled">Select a day:</option><?php
								// Generate selection box options
								$query = "SELECT d_id, name FROM days ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo str_replace("'", "\'", "<option value=\"" . htmlspecialchars($row['d_id']) . "\">" . htmlspecialchars($row['name']) . "</option>");
								}
								mysql_free_result($result);
								?>';
							
							// Rebuild submitted form
							$("#statspanelbuildertrigger").change(function(){<?php
								echo "\n";
								// Generate rebuild script
								if(isset($_POST['submitted']) && isset($_POST['days']))
								{
									foreach($day_list as $dayid)
									{
										echo "\t\t\t\t\t\t\t\t$(\"#btn_stat_addday\").click();\n";
										echo str_replace("'", "\'", "\t\t\t\t\t\t\t\t$(\"#dayselect_\" + statday_next_row).attr(\"value\", \"" . htmlspecialchars($dayid) . "\");\n");
									}
								}
								?>
							});
						</script>
					</fieldset>
				</form>
			</p><?php
					// If form submitted, output data table
					if(isset($_POST['submitted']) && isset($_POST['days']))
					{
						echo "\n\t\t\t<hr>\n\t\t\t<p>\n$v_statdata\t\t\t</p>";
					}
					echo "\n";
					break;
					
	// Statistical Analysis Panel >> Analyse Events
				case 'stats_events':
					/* Output any messages */ echo $message;
					?>
			<p>
				<form action="index.php?page=stats_events" method="post">
					<fieldset>
						<legend>Select which Events to analyse</legend>
						<table class="formtable" id="tbl_stat_events">
						</table>
						<button type="button" id="btn_stat_addevent">Add</button>
						<br><br>
						<button type="submit" id="btn_gen_norm">Generate New Analysis Table (below)</button>
						<button type="submit" id="btn_gen_doc">Generate New Analysis Table (as Word document)</button>
						<button type="submit" id="btn_gen_spread">Generate New Analysis Table (as Excel spreadsheet)</button>
						<input type="hidden" name="outputmode" value="normal" id="hdn_outputmode">
						<input type="hidden" name="submitted" value="TRUE">
						<input type="hidden" id="statspanelbuildertrigger">
						<script type="text/javascript">
							// Dynamic form data
							statevent_dayoptions = '<option value="0" selected="selected" disabled="disabled">Select a day:</option><?php
								// Generate selection box options, and prepare events array
								$eventoptions = array();
								$query = "SELECT d_id, name FROM days ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo str_replace("'", "\'", "<option value=\"" . htmlspecialchars($row['d_id']) . "\">" . htmlspecialchars($row['name']) . "</option>");
									$eventoptions[$row['d_id']] = "";
								}
								mysql_free_result($result);
								?>';
							statevent_eventoptions = new Array();<?php
								// Generate selection box options
								$query = "SELECT e_id, d_id, name FROM day_events ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									if(isset($eventoptions[$row['d_id']]))
									{
										$eventoptions[$row['d_id']] .= str_replace("'", "\'", "<option value=\"" . htmlspecialchars($row['e_id']) . "\">" . htmlspecialchars($row['name']) . "</option>");
									}
								}
								mysql_free_result($result);
								foreach($eventoptions as $d_id => $optionlist)
								{
									echo "\n\t\t\t\t\t\t\tstatevent_eventoptions[" . htmlspecialchars($d_id) . "] = ";
									echo "'<option value=\"0\" selected=\"selected\" disabled=\"disabled\">Select an event:</option>$optionlist';";
								}
								?>
							
							// Rebuild submitted form
							$("#statspanelbuildertrigger").change(function(){<?php
								echo "\n";
								// Generate rebuild script
								if(isset($_POST['submitted']) && isset($_POST['days']) && isset($_POST['events']))
								{
									foreach($event_list as $eventdata)
									{
										echo "\t\t\t\t\t\t\t\t$(\"#btn_stat_addevent\").click();\n";
										echo str_replace("'", "\'", "\t\t\t\t\t\t\t\t$(\"#dayselect_\" + statevent_next_row).attr(\"value\", \"" . htmlspecialchars($eventdata['dayid']) . "\");\n");
										echo "\t\t\t\t\t\t\t\t$(\"#dayselect_\" + statevent_next_row).change();\n";
										echo str_replace("'", "\'", "\t\t\t\t\t\t\t\t$(\"#eventselect_\" + statevent_next_row).attr(\"value\", \"" . htmlspecialchars($eventdata['eventid']) . "\");\n");
									}
								}
								?>
							});
						</script>
					</fieldset>
				</form>
			</p><?php
					// If form submitted, output data table
					if(isset($_POST['submitted']) && isset($_POST['days']) && isset($_POST['events']))
					{
						echo "\n\t\t\t<hr>\n\t\t\t<p>\n$v_statdata\t\t\t</p>";
					}
					echo "\n";
					break;
					
	// Statistical Analysis Panel >> Analyse Teams
				case 'stats_teams':
					/* Output any messages */ echo $message;
					?>
			<p>
				<form action="index.php?page=stats_teams" method="post">
					<fieldset>
						<legend>Select which Teams to analyse</legend>
						<table class="formtable" id="tbl_stat_teams">
						</table>
						<button type="button" id="btn_stat_addteam">Add</button>
						<br><br>
						<button type="submit" id="btn_gen_norm">Generate New Analysis Table (below)</button>
						<button type="submit" id="btn_gen_doc">Generate New Analysis Table (as Word document)</button>
						<button type="submit" id="btn_gen_spread">Generate New Analysis Table (as Excel spreadsheet)</button>
						<input type="hidden" name="outputmode" value="normal" id="hdn_outputmode">
						<input type="hidden" name="submitted" value="TRUE">
						<input type="hidden" id="statspanelbuildertrigger">
						<script type="text/javascript">
							// Dynamic form data
							statteam_dayoptions = '<option value="0" selected="selected" disabled="disabled">Select a day:</option><?php
								// Generate selection box options, and prepare teams array
								$teamoptions = array();
								$query = "SELECT d_id, name FROM days ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo str_replace("'", "\'", "<option value=\"" . htmlspecialchars($row['d_id']) . "\">" . htmlspecialchars($row['name']) . "</option>");
									$teamoptions[$row['d_id']] = "";
								}
								mysql_free_result($result);
								?>';
							statteam_teamoptions = new Array();<?php
								// Generate selection box options
								$query = "SELECT t_id, d_id, name FROM day_teams ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									if(isset($teamoptions[$row['d_id']]))
									{
										$teamoptions[$row['d_id']] .= str_replace("'", "\'", "<option value=\"" . htmlspecialchars($row['t_id']) . "\">" . htmlspecialchars($row['name']) . "</option>");
									}
								}
								mysql_free_result($result);
								foreach($teamoptions as $d_id => $optionlist)
								{
									echo "\n\t\t\t\t\t\t\tstatteam_teamoptions[" . htmlspecialchars($d_id) . "] = ";
									echo "'<option value=\"0\" selected=\"selected\" disabled=\"disabled\">Select a team:</option>$optionlist';";
								}
								?>
							
							// Rebuild submitted form
							$("#statspanelbuildertrigger").change(function(){<?php
								echo "\n";
								// Generate rebuild script
								if(isset($_POST['submitted']) && isset($_POST['days']) && isset($_POST['teams']))
								{
									foreach($team_list as $teamdata)
									{
										echo "\t\t\t\t\t\t\t\t$(\"#btn_stat_addteam\").click();\n";
										echo str_replace("'", "\'", "\t\t\t\t\t\t\t\t$(\"#dayselect_\" + statteam_next_row).attr(\"value\", \"" . htmlspecialchars($teamdata['dayid']) . "\");\n");
										echo "\t\t\t\t\t\t\t\t$(\"#dayselect_\" + statteam_next_row).change();\n";
										echo str_replace("'", "\'", "\t\t\t\t\t\t\t\t$(\"#teamselect_\" + statteam_next_row).attr(\"value\", \"" . htmlspecialchars($teamdata['teamid']) . "\");\n");
									}
								}
								?>
							});
						</script>
					</fieldset>
				</form>
			</p><?php
					// If form submitted, output data table
					if(isset($_POST['submitted']) && isset($_POST['days']) && isset($_POST['teams']))
					{
						echo "\n\t\t\t<hr>\n\t\t\t<p>\n$v_statdata\t\t\t</p>";
					}
					echo "\n";
					break;
					
	// Statistical Analysis Panel >> Analyse Competitors
				case 'stats_competitors':
					/* Output any messages */ echo $message;
					?>
			<p>
				<form action="index.php?page=stats_competitors" method="post">
					<fieldset>
						<legend>Select which Competitors to analyse</legend>
						<table class="formtable" id="tbl_stat_competitors">
						</table>
						<button type="button" id="btn_stat_addcompetitor">Add</button>
						<br><br>
						<button type="submit" id="btn_gen_norm">Generate New Analysis Table (below)</button>
						<button type="submit" id="btn_gen_doc">Generate New Analysis Table (as Word document)</button>
						<button type="submit" id="btn_gen_spread">Generate New Analysis Table (as Excel spreadsheet)</button>
						<input type="hidden" name="outputmode" value="normal" id="hdn_outputmode">
						<input type="hidden" name="submitted" value="TRUE">
						<input type="hidden" id="statspanelbuildertrigger">
						<script type="text/javascript">
							// Dynamic form data
							statcompetitor_dayoptions = '<option value="0" selected="selected" disabled="disabled">Select a day:</option><?php
								// Generate selection box options, and prepare competitors array
								$competitoroptions = array();
								$query = "SELECT d_id, name FROM days ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo str_replace("'", "\'", "<option value=\"" . htmlspecialchars($row['d_id']) . "\">" . htmlspecialchars($row['name']) . "</option>");
									$competitoroptions[$row['d_id']] = "";
								}
								mysql_free_result($result);
								?>';
							statcompetitor_competitoroptions = new Array();<?php
								// Generate selection box options
								$query = "SELECT c_id, d_id, name FROM day_competitors ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									if(isset($competitoroptions[$row['d_id']]))
									{
										$competitoroptions[$row['d_id']] .= str_replace("'", "\'", "<option value=\"" . htmlspecialchars($row['c_id']) . "\">" . htmlspecialchars($row['name']) . "</option>");
									}
								}
								mysql_free_result($result);
								foreach($competitoroptions as $d_id => $optionlist)
								{
									echo "\n\t\t\t\t\t\t\tstatcompetitor_competitoroptions[" . htmlspecialchars($d_id) . "] = ";
									echo "'<option value=\"0\" selected=\"selected\" disabled=\"disabled\">Select a competitor:</option>$optionlist';";
								}
								?>
							
							// Rebuild submitted form
							$("#statspanelbuildertrigger").change(function(){<?php
								echo "\n";
								// Generate rebuild script
								if(isset($_POST['submitted']) && isset($_POST['days']) && isset($_POST['competitors']))
								{
									foreach($competitor_list as $competitordata)
									{
										echo "\t\t\t\t\t\t\t\t$(\"#btn_stat_addcompetitor\").click();\n";
										echo str_replace("'", "\'", "\t\t\t\t\t\t\t\t$(\"#dayselect_\" + statcompetitor_next_row).attr(\"value\", \"" . htmlspecialchars($competitordata['dayid']) . "\");\n");
										echo "\t\t\t\t\t\t\t\t$(\"#dayselect_\" + statcompetitor_next_row).change();\n";
										echo str_replace("'", "\'", "\t\t\t\t\t\t\t\t$(\"#competitorselect_\" + statcompetitor_next_row).attr(\"value\", \"" . htmlspecialchars($competitordata['competitorid']) . "\");\n");
									}
								}
								?>
							});
						</script>
					</fieldset>
				</form>
			</p><?php
					// If form submitted, output data table
					if(isset($_POST['submitted']) && isset($_POST['days']) && isset($_POST['competitors']))
					{
						echo "\n\t\t\t<hr>\n\t\t\t<p>\n$v_statdata\t\t\t</p>";
					}
					echo "\n";
					break;
					
	// Statistical Analysis Panel >> Analyse Records
				case 'stats_records':
					/* Output any messages */ echo $message;
					?>
			<p>
				<form action="index.php?page=stats_records" method="post">
					<fieldset>
						<legend>Select which Days to analyse the Records for</legend>
						<table class="formtable" id="tbl_stat_records">
						</table>
						<button type="button" id="btn_stat_addrecord">Add</button>
						<br><br>
						<button type="submit" id="btn_gen_norm">Generate New Analysis Table (below)</button>
						<button type="submit" id="btn_gen_doc">Generate New Analysis Table (as Word document)</button>
						<button type="submit" id="btn_gen_spread">Generate New Analysis Table (as Excel spreadsheet)</button>
						<input type="hidden" name="outputmode" value="normal" id="hdn_outputmode">
						<input type="hidden" name="submitted" value="TRUE">
						<input type="hidden" id="statspanelbuildertrigger">
						<script type="text/javascript">
							// Dynamic form data
							statrecord_dayoptions = '<option value="0" selected="selected" disabled="disabled">Select a day:</option><?php
								// Generate selection box options
								$query = "SELECT d_id, name FROM days ORDER BY name ASC";
								$result = @mysql_query($query);
								while($row = mysql_fetch_array($result))
								{
									echo str_replace("'", "\'", "<option value=\"" . htmlspecialchars($row['d_id']) . "\">" . htmlspecialchars($row['name']) . "</option>");
								}
								mysql_free_result($result);
								?>';
							
							// Rebuild submitted form
							$("#statspanelbuildertrigger").change(function(){<?php
								echo "\n";
								// Generate rebuild script
								if(isset($_POST['submitted']) && isset($_POST['days']))
								{
									foreach($day_list as $dayid)
									{
										echo "\t\t\t\t\t\t\t\t$(\"#btn_stat_addrecord\").click();\n";
										echo str_replace("'", "\'", "\t\t\t\t\t\t\t\t$(\"#dayselect_\" + statrecord_next_row).attr(\"value\", \"" . htmlspecialchars($dayid) . "\");\n");
									}
								}
								?>
							});
						</script>
					</fieldset>
				</form>
			</p><?php
					// If form submitted, output data table
					if(isset($_POST['submitted']) && isset($_POST['days']))
					{
						echo "\n\t\t\t<hr>\n\t\t\t<p>\n$v_statdata\t\t\t</p>";
					}
					echo "\n";
					break;
					
	// Statistical Analysis Panel >> Produce Scoresheet
				case 'stats_scoresheet':
					/* Output any messages */ echo $message;
					?>
			<p>
				<form action="index.php?page=stats_scoresheet" method="post">
					<fieldset>
						<legend>Select which Day to analyse</legend>
						<table class="formtable" id="tbl_stat_scoresheet">
							<tr>
								<td>
									<select name="day">
										<option value="0" selected="selected" disabled="disabled">Select a day:</option><?php
										// Generate selection box options
										$query = "SELECT d_id, name FROM days ORDER BY name ASC";
										$result = @mysql_query($query);
										while($row = mysql_fetch_array($result))
										{
											echo "\n\t\t\t\t\t\t\t\t\t\t<option value=\"" . htmlspecialchars($row['d_id']) . "\"";
											if(isset($_POST['day']) && $_POST['day'] == $row['d_id']) echo " selected=\"selected\"";
											echo ">" . htmlspecialchars($row['name']) . "</option>";
										}
										mysql_free_result($result);
										echo "\n";
										?>
									</select>
								</td>
							</tr>
							<tr><td><br></td></tr>
							<tr>
								<td>
									<input type="checkbox" name="showunits" value="1" id="cb_showunits"<?php if(isset($_POST['showunits'])) echo " checked=\"checked\""; ?>>
									<label for="cb_showunits">Show Units</label>
								</td>
							</tr>
							<tr>
								<td>
									<input type="checkbox" name="showsummary" value="1" id="cb_showsummary"<?php if(isset($_POST['showsummary'])) echo " checked=\"checked\""; elseif(!isset($_POST['submitted'])) echo " checked=\"checked\""; ?>>
									<label for="cb_showsummary">Show Summary Table</label>
								</td>
							</tr>
							<tr><td><br></td></tr>
							<tr>
								<td>Show data for Events open to these Subgroups:</td>
							</tr><?php
							// Generate list of subgroup checkboxes, ticked by default
							$query = "SELECT sub_id, name FROM day_subgroups WHERE d_id='" . mysql_real_escape_string($curactivedayid) . "'";
							$result = @mysql_query($query);
							while($row = mysql_fetch_array($result))
							{
								echo "\n\t\t\t\t\t\t\t<tr>\n\t\t\t\t\t\t\t\t<td>\n";
								echo "\n\t\t\t\t\t\t\t\t\t<input type=\"checkbox\" name=\"showgroup[" . htmlspecialchars($row['sub_id']) . "]\" value=\"1\"";
								echo " id=\"cb_showgroup_" . htmlspecialchars($row['sub_id']) . "\"";
								if(isset($_POST['showgroup'][htmlspecialchars($row['sub_id'])])) echo " checked=\"checked\""; elseif(!isset($_POST['submitted'])) echo " checked=\"checked\"";
								echo ">\n\t\t\t\t\t\t\t\t\t<label for=\"cb_showgroup_" . htmlspecialchars($row['sub_id']) . "\">" . htmlspecialchars($row['name']) . "</label>";
								echo "\n\t\t\t\t\t\t\t\t</td>\n\t\t\t\t\t\t\t</tr>";
							}
							mysql_free_result($result);
							?>
						</table>
						<br>
						<button type="submit" id="btn_gen_norm">Generate New Analysis Table (below)</button>
						<button type="submit" id="btn_gen_doc">Generate New Analysis Table (as Word document)</button>
						<button type="submit" id="btn_gen_spread">Generate New Analysis Table (as Excel spreadsheet)</button>
						<input type="hidden" name="outputmode" value="normal" id="hdn_outputmode">
						<input type="hidden" name="submitted" value="TRUE">
					</fieldset>
				</form>
			</p><?php
					// If form submitted, output data table
					if(isset($_POST['submitted']) && isset($_POST['day']))
					{
						echo "\n\t\t\t<hr>\n\t\t\t<p>\n$v_statdata\t\t\t</p>";
					}
					echo "\n";
					break;
					
	// Help
				case 'help':
					include('userguide.htm');
					echo "\n";
					break;
					
	// Default - should not occur
				default:
					break;
			}
			?>
		</div>
	</div>
</body>

</html>
<?php
/*
 End of script stuff
 */
 
 /*
  Close MySQL connection
  */
 mysql_close();
 ?>