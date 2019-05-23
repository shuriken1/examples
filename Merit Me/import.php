<?php
set_time_limit(300);
session_start();
include("includes/db.php");
include("includes/functions.php");
include("includes/functions_import.php");

ini_set("display_errors", "on");
error_reporting(E_ALL);

// NOTES
// When years are added, they need to create sec_group for classes and forms

if(!isset($_POST['r'])) {
	$_SESSION['error']['msg'] = "Sorry, something seems to have gone wrong. Please contact Technical Support.";
	$_SESSION['error']['type'] = "fatal";
	$_SESSION['error']['source'] = $_SERVER['REQUEST_URI'].":".__LINE__;
	header("Location: index.php");
	exit;
}

function Output($message, $to_session = true, $to_file = true, $section = "", $level = "") {
	if($to_session) {
		global $_SESSION;
		$_SESSION['import']['messages'] .= $message."<br>\n";
	}
	if($to_file) {
		global $log_file;
		fwrite($log_file, date("d/m/Y H:i:s")." ".$section." ".$level."\t ".$message."\n");
	}
}

// Initialize variables
$_SESSION['import']['messages'] = "";

if(is_writable("import.log")) {
	$log_file = @fopen("import.log", "a");
} else {
	Output("<span style='color: #DD0000;'>Could not open log file, please consult Technical Support.</span>", true, false);
	header("Location: ".$_POST['r']);
	exit;
}

if($_POST['m'] == "full_import") {
	
	$uploaddir = './uploads/temp/';
	date_default_timezone_set('Europe/London');
	
	if(isset($_POST['dry_run'])) {
		$dry_run = true;
	} else {
		$dry_run = false;
	}
	Output("============ STARTING IMPORT ============", false);
	if($dry_run) {
		Output("============ DRY RUN ============", false);
	}
	
	$school_phase = GetConfigValue("school_phase");
	if(is_null($school_phase)) {
		Output("School Phase not found. Please contact Technical Support immediately.", true, true, "INIT", "ERROR");
		header("Location: ".$_POST['r']);
		exit;
	}
	
	if($school_phase == "primary") {
		Output("School Phase is set to Primary.", false, true, "INIT", "INFO");
	} elseif($school_phase == "secondary") {
		Output("School Phase is set to Secondary.", false, true, "INIT", "INFO");
	} else {
		Output("School Phase not recognised.", true, true, "INIT", "ERROR");
		header("Location: ".$_POST['r']);
		exit;
	}
	$short_group_name = (boolean)GetConfigValue("import_short_group_name");
	if(is_null($short_group_name)) {
		Output("Short Group Name not found. Please contact Technical Support immediately.", true, true, "INIT", "ERROR");
		header("Location: ".$_POST['r']);
		exit;
	}
	
	//echo"<pre>"; print_r($_FILES); echo"</pre>"; exit;
	
	// Check for upload errors
	$ERRORS = array(
		0=>"There is no error, the file uploaded with success.",
		1=>"The uploaded file exceeds the maximum upload size, please confirm you are uploading the correct file.",
		2=>"The uploaded file exceeds the form's maximum upload size, please confirm you are uploading the correct file.",
		3=>"The uploaded file was only partially uploaded",
		4=>"No file was uploaded",
		6=>"Missing a temporary folder"
	);
	foreach($_FILES['file']['error'] as $file => $error) {
		if($error != UPLOAD_ERR_OK) {
			Output("The following error was encountered when processing the '".$file."' file: ".$ERRORS[$error].".", true, true, "FILES", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
	}
	unset($ERRORS);
	
	// Check the filetype of each file, they should all be XMLs
	foreach($_FILES['file']['type'] as $file => $type) {
		if($type != "text/xml") {
			Output("Wrong file type for file '".$_FILES['file'][$file]['name']."'", true, true, "FILES", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
	}
	
	// Make sure the correct files have been uploaded.
	// FIX Add general check for common files too?
	if($school_phase == "secondary") {
		if(empty($_FILES['file']['name']['owners']) OR empty($_FILES['file']['name']['classes']) OR empty($_FILES['file']['name']['class_memberships'])) {
			Output("Some necessary files have not been uploaded.", true, true, "FILES", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
	}
	
	// Move each file to temp import directory, rename to original name at the same time.
	foreach($_FILES['file']['tmp_name'] as $file => $tmp_name) {
		if(!move_uploaded_file($_FILES['file']['tmp_name'][$file], $uploaddir.$_FILES['file']['name'][$file])) {
			Output("Couldn't move file '".$_FILES['file'][$file]['name']."'", true, true, "FILES", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
	}
	
	// File checks complete
	
	// Open files
	$FILES = array();
	//$FILE_HANDLES = array();
	//
	//foreach($_FILES['file']['name'] as $file => $name) {
	//	$FILE_HANDLES[$file] = fopen($uploaddir.$name, "r");
	//	if(!$FILE_HANDLES[$file]) {
	//		Output("Couldn't open '".$name."' file.", true, true, "FILES", "ERROR");
	//		header("Location: ".$_POST['r']);
	//		exit;
	//	}
	//}
	//
	//echo"<pre>";print_r($FILE_HANDLES);echo"</pre>";
	
	// Parse files ready to build OLD arrays
	// Parse users
	Output("Parsing users file.", false, true, "FILES", "INFO");
	$xml = simplexml_load_file($uploaddir.$_FILES['file']['name']['users']);
	$FILES['users'] = array();
	foreach($xml->Record as $record) {
		array_push($FILES['users'], array(
		"mis_id" => (string)$record->ID,
		"first_name" => (string)$record->ChosenName,
		"surname" => (string)$record->Surname,
		"initials" => (string)$record->Initials,
		"title" => (string)$record->Title,
		"start_date" => (string)$record->appointment_date,
		"end_date" => (string)$record->employment_end));
	}
	unset($xml);
	Output("Users file parsed.", false, true, "FILES", "INFO");
	
	if(unlink($uploaddir.$_FILES['file']['name']['users'])) {
		Output("Users file deleted.", false, true, "FILES", "INFO");
	} else {
		Output("Users file was not deleted.", true, true, "FILES", "ERROR");
	}
	
	// Parse subjects
	Output("Parsing subjects file.", false, true, "FILES", "INFO");
	$xml = simplexml_load_file($uploaddir.$_FILES['file']['name']['subjects']);
	$FILES['subjects'] = array();
	foreach($xml->Record as $record) {
		array_push($FILES['subjects'], array(
		"mis_id" => (string)$record->ID,
		"name" => (string)$record->Description,
		"code" => (string)$record->Name));
	}
	unset($xml);
	Output("Subjects filed parsed.", false, true, "FILES", "INFO");
	
	if(unlink($uploaddir.$_FILES['file']['name']['subjects'])) {
		Output("Subjects file deleted.", false, true, "FILES", "INFO");
	} else {
		Output("Subjects file was not deleted.", true, true, "FILES", "ERROR");
	}
	
	// Parse classes
	Output("Parsing classes file.", false, true, "FILES", "INFO");
	$xml = simplexml_load_file($uploaddir.$_FILES['file']['name']['classes']);
	$FILES['classes'] = array();
	foreach($xml->Record as $record) {
		array_push($FILES['classes'], array(
		"mis_id" => (string)$record->ID,
		"name" => (string)$record->ShortName,
		"subject_mis_id" => (string)$record->SubjectID));
	}
	unset($xml);
	Output("Classes file parsed.", false, true, "FILES", "INFO");
	
	if(unlink($uploaddir.$_FILES['file']['name']['classes'])) {
		Output("Classes file deleted.", false, true, "FILES", "INFO");
	} else {
		Output("Classes file was not deleted.", true, true, "FILES", "ERROR");
	}
	
	// Parse forms
	Output("Parsing forms file.", false, true, "FILES", "INFO");
	$xml = simplexml_load_file($uploaddir.$_FILES['file']['name']['forms']);
	$FILES['forms'] = array();
	foreach($xml->Record as $record) {
		array_push($FILES['forms'], array(
		"mis_id" => (string)$record->id,
		"name" => (string)$record->short_name,
		"year" => (string)$record->yrGroup,
		"user_mis_id" => (string)$record->ID1));
	}
	unset($xml);
	Output("Forms file parsed.", false, true, "FILES", "INFO");
	
	if(unlink($uploaddir.$_FILES['file']['name']['forms'])) {
		Output("Forms file deleted.", false, true, "FILES", "INFO");
	} else {
		Output("Forms file was not deleted.", true, true, "FILES", "ERROR");
	}
	
	// Parse students
	Output("Parsing students file.", false, true, "FILES", "INFO");
	$xml = simplexml_load_file($uploaddir.$_FILES['file']['name']['students']);
	$FILES['students'] = array();
	foreach($xml->Record as $record) {
		array_push($FILES['students'], array(
		"mis_id" => (string)$record->ID,
		"upn" => (string)$record->UPN,
		"first_name" => (string)$record->ChosenName,
		"surname" => (string)$record->Surname,
		"form_mis_id" => (string)$record->id1,
		"house" => (string)$record->House));
	}
	unset($xml);
	Output("Students file parsed.", false, true, "FILES", "INFO");
	
	if(unlink($uploaddir.$_FILES['file']['name']['students'])) {
		Output("Students file deleted.", false, true, "FILES", "INFO");
	} else {
		Output("Students file was not deleted.", true, true, "FILES", "ERROR");
	}
	
	// Parse owners
	Output("Parsing owners file.", false, true, "FILES", "INFO");
	$xml = simplexml_load_file($uploaddir.$_FILES['file']['name']['owners']);
	$FILES['owners'] = array();
	foreach($xml->Record as $record) {
		array_push($FILES['owners'], array(
		"class_mis_id" => (string)$record->ID,
		"user_mis_id" => (string)$record->ID1,
		"main" => (string)$record->Main,
		"start_date" => (string)$record->StartDate,
		"end_date" => (string)$record->EndDate));
	}
	unset($xml);
	Output("Owners file parsed.", false, true, "FILES", "INFO");
	
	if(unlink($uploaddir.$_FILES['file']['name']['owners'])) {
		Output("Owners file deleted.", false, true, "FILES", "INFO");
	} else {
		Output("Owners file was not deleted.", true, true, "FILES", "ERROR");
	}
	
	// Parse class memberships
	Output("Parsing class memberships file.", false, true, "FILES", "INFO");
	$xml = simplexml_load_file($uploaddir.$_FILES['file']['name']['class_memberships']);
	$FILES['class_memberships'] = array();
	foreach($xml->Record as $record) {
		array_push($FILES['class_memberships'], array(
		"class_mis_id" => (string)$record->ID,
		"student_mis_id" => (string)$record->person_id));
	}
	unset($xml);
	Output("Class memberships file parsed.", false, true, "FILES", "INFO");
	
	if(unlink($uploaddir.$_FILES['file']['name']['class_memberships'])) {
		Output("Class memberships file deleted.", false, true, "FILES", "INFO");
	} else {
		Output("Class memberships file was not deleted.", true, true, "FILES", "ERROR");
	}
	
	
	
	// Initialise variables for comparisons
	$CHANGES = array();
	$OLD = array();
	$NEW = array();
	
	// USERS
	// Build Users CHANGES array
	$CHANGES['users'] = array();
	foreach($FILES['users'] as $key => $data) {
		
		// Create temporary variables and tidy them up.
		$mis_id = $data['mis_id'];
		$first_name = CleanName($data['first_name']);
		$surname = CleanName($data['surname']);
		$initials = strtoupper(trim($data['initials']));
		$title = ucwords(trim($data['title']));
		if(!empty($data['start_date'])) {
			$start_date = date_parse($data['start_date']);
			$start_timestamp = mktime($start_date['hour'], $start_date['minute'], $start_date['second'], $start_date['month'], $start_date['day'], $start_date['year']);
		}
		if(!empty($data['end_date'])) {
			$end_date = date_parse($data['end_date']);
			$end_timestamp = mktime($end_date['hour'], $end_date['minute'], $end_date['second'], $end_date['month'], $end_date['day'], $end_date['year']);
		}
		
		Output("Checking user '".$first_name." ".$surname."' (MIS ID: ".$mis_id.")..", false, true, "USERS", "INFO");
		
		// Check for empty values and skip if necessary.
		// Clean up input here so that the comparison is correct. if it's corrected when adding the user the comparison will be wrong here.
		if(empty($initials)) {
			$initials = strtoupper(substr($first_name, 0, 1).substr($surname, 0, 1));
		}
		$initials = CheckUserInitials($initials, $mis_id);
		
		if(empty($title)) {
			$title = "Mr";
		}
		
		$find_result = mysqli_query($dbi, "SELECT * FROM users WHERE user_mis_id = '".$mis_id."' LIMIT 2") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
		if(mysqli_num_rows($find_result) == 1) {
			$find_row = mysqli_fetch_assoc($find_result);
			
			// Compare fields for any necessary updates
			// Apply changes immediate so that future comparisons are correct
			if($find_row['user_first_name'] != $first_name) {
				$change_data = array("user_id" => $find_row['user_id'], "field" => "user_first_name", "old_value" => $find_row['user_first_name'], "new_value" => $first_name);
				array_push($CHANGES['users'], array("action" => "update",
				"data" => $change_data,
				"description" => "Update first name for user '".$find_row['user_first_name']." ".$find_row['user_surname']."' (ID: ".$find_row['user_id'].") from '".$find_row['user_first_name']."' to '".$first_name."'.",
				"result" => UpdateUser($change_data)));
			}
			if($find_row['user_surname'] != $surname) {
				$change_data = array("user_id" => $find_row['user_id'], "field" => "user_surname", "old_value" => $find_row['user_surname'], "new_value" => $surname);
				array_push($CHANGES['users'], array("action" => "update",
				"data" => $change_data,
				"description" => "Update surname for user '".$find_row['user_first_name']." ".$find_row['user_surname']."' (ID: ".$find_row['user_id'].") from '".$find_row['user_surname']."' to '".$surname."'.",
				"result" => UpdateUser($change_data)));
			}
			if($find_row['user_initials'] != $initials) {
				$change_data = array("user_id" => $find_row['user_id'], "field" => "user_initials", "old_value" => $find_row['user_initials'], "new_value" => $initials);
				array_push($CHANGES['users'], array("action" => "update",
				"data" => $change_data,
				"description" => "Update initials for user '".$find_row['user_first_name']." ".$find_row['user_surname']."' (ID: ".$find_row['user_id'].") from '".$find_row['user_initials']."' to '".$initials."'.",
				"result" => UpdateUser($change_data)));
			}
			if($find_row['user_title'] != $title) {
				$change_data = array("user_id" => $find_row['user_id'], "field" => "user_title", "old_value" => $find_row['user_title'], "new_value" => $title);
				array_push($CHANGES['users'], array("action" => "update",
				"data" => $change_data,
				"description" => "Update title for user '".$find_row['user_first_name']." ".$find_row['user_surname']."' (ID: ".$find_row['user_id'].") from '".$find_row['user_title']."' to '".$title."'.",
				"result" => UpdateUser($change_data)));
			}
		} elseif(mysqli_num_rows($find_result) == 0) {
			// Add user
			// If start timestamp is set and before now or not set - AND - end timestamp is set and after now or not set
			if(((isset($start_timestamp) AND $start_timestamp < time()) OR !isset($start_timestamp)) AND ((isset($end_timestamp) AND $end_timestamp > time()) OR !isset($end_timestamp))) {
				$change_data = array("user_mis_id" => $mis_id, "first_name" => $first_name, "surname" => $surname, "initials" => $initials, "title" => $title);
				array_push($CHANGES['users'], array("action" => "add",
				"data" => $change_data,
				"description" => "Add user '".$first_name." ".$surname."'.",
				"result" => AddUser($change_data)));
			}
		} else {
			// More than one user found with the same ID!
			Output("Multiple users found with the same MIS ID (MIS ID: ".$mis_id.")!", true, true, "USERS", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
		
		// change_data variable needs to be cleared, used for the immediate update
		unset($mis_id, $first_name, $surname, $initials, $title, $start_date, $start_timestamp, $end_date, $end_timestamp, $change_data);
	}
	
	// Check for users that no longer exist.
	// Only check users with a MIS ID set otherwise it'll remove manually added users.
	$users_result = mysqli_query($dbi, "SELECT * FROM users WHERE user_mis_id <> 0") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
	while($users_row = mysqli_fetch_assoc($users_result)) {
		// Check to see if the current DB user is in the file
		$present = false;
		foreach($FILES['users'] as $key => $data) {
			if($users_row['user_mis_id'] == $data['mis_id']) {
				// Parse end date if given
				if(!empty($data['end_date'])) {
					$end_date = date_parse($data['end_date']);
					$end_timestamp = mktime($end_date['hour'], $end_date['minute'], $end_date['second'], $end_date['month'], $end_date['day'], $end_date['year']);
				}
				
				// If end date is in the future or not supplied, they are a current member of staff
				if((isset($end_timestamp) AND $end_timestamp > time()) OR !isset($end_timestamp)) {
					$present = true;
				}
			}
		}
		
		// If it's not in the file, it must be old, so remove it
		if(!$present) {
			$change_data = array("user_id" => $users_row['user_id']);
			array_push($CHANGES['users'], array("action" => "remove",
			"data" => $change_data,
			"description" => "Remove user '".$users_row['user_first_name']." ".$users_row['user_surname']."' (ID: ".$users_row['user_id'].").",
			"result" => RemoveUser($change_data)));
		}
		unset($present, $end_date, $end_timestamp, $change_data);
	}
	
	
	
	// SUBJECTS
	// Build Subjects CHANGES array
	$CHANGES['subjects'] = array();
	foreach($FILES['subjects'] as $key => $data) {
		
		// Create temporary variables and tidy them up.
		$mis_id = $data['mis_id'];
		$name = ucwords(trim($data['name']));
		$code = trim($data['code']);
		
		Output("Checking subject '".$name."' (MIS ID: ".$mis_id.")..", false, true, "SUBJECTS", "INFO");
		
		// Check for empty values and skip if necessary.
		// Clean up input here so that the comparison is correct. if it's corrected when adding the user the comparison will be wrong here.
		
		
		$find_result = mysqli_query($dbi, "SELECT * FROM subjects WHERE subject_mis_id = '".$mis_id."' LIMIT 2") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
		if(mysqli_num_rows($find_result) == 1) {
			$find_row = mysqli_fetch_assoc($find_result);
			
			// Compare fields for any necessary updates
			// Apply changes immediate so that future comparisons are correct
			if($find_row['subject_name'] != $name) {
				$change_data = array("subject_id" => $find_row['subject_id'], "field" => "subject_name", "old_value" => $find_row['subject_name'], "new_value" => $name);
				array_push($CHANGES['subjects'], array("action" => "update",
				"data" => $change_data,
				"description" => "Update name for subject '".$find_row['subject_name']."' (ID: ".$find_row['subject_id'].") from '".$find_row['subject_name']."' to '".$name."'.",
				"result" => UpdateSubject($change_data)));
			}
			if($find_row['subject_code'] != $code) {
				$change_data = array("subject_id" => $find_row['subject_id'], "field" => "subject_code", "old_value" => $find_row['subject_code'], "new_value" => $code);
				array_push($CHANGES['subjects'], array("action" => "update",
				"data" => $change_data,
				"description" => "Update code for subject '".$find_row['subject_name']."' (ID: ".$find_row['subject_id'].") from '".$find_row['subject_code']."' to '".$code."'.",
				"result" => UpdateSubject($change_data)));
			}
		} elseif(mysqli_num_rows($find_result) == 0) {
			// Add subject - if it's being used.
			$used = false;
			foreach($FILES['classes'] as $key => $data) {
				$temp_mis_id = $data['subject_mis_id'];
				if($mis_id == $temp_mis_id) {
					$used = true;
					break;
				}
			}
			
			if($used) {
				$change_data = array("subject_mis_id" => $mis_id, "name" => $name, "code" => $code);
				array_push($CHANGES['subjects'], array("action" => "add",
				"data" => $change_data,
				"description" => "Add subject '".$name."'.",
				"result" => AddSubject($change_data)));
			}
			unset($used);
		} else {
			// More than one subject found with the same ID!
			Output("Multiple subjects found with the same MIS ID (MIS ID: ".$mis_id.")!", true, true, "SUBJECTS", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
		
		// change_data variable needs to be cleared, used for the immediate update
		unset($mis_id, $name, $code, $change_data);
	}
	
	// Check for old subjects is done later so that the classes can be updated first.
	
	
	
	// FORMS & CLASSES
	// Build Forms CHANGES array - Use Classes array as they're effectively the same.
	$CHANGES['classes'] = array();
	foreach($FILES['forms'] as $key => $data) {
		
		// Create temporary variables and tidy them up.
		$mis_id = $data['mis_id'];
		$year_name = trim(str_ireplace("Year", "", $data['year'])); // Get just the year number from the "Year ??" string.
		$group_name = strtoupper(trim(str_ireplace($year_name, "", $data['name']))); // Remove the year number from the form name to get the group name.
		$user_mis_id = $data['user_mis_id'];
		
		Output("Checking form class '".$year_name.$group_name."' (MIS ID: ".$mis_id.")..", false, true, "FORMS", "INFO");
		
		// Check for empty values and skip if necessary.
		// Clean up input here so that the comparison is correct. if it's corrected when adding the user the comparison will be wrong here.
		
		// GetSet Year
		$year_id = GetSetYearID($year_name);
		if(!$year_id) {
			Output("A year could not be created.", true, true, "YEARS", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
		
		// Get group id
		$group_id = GetSetGroupID($group_name, $year_id, "form");
		if(!$group_id) {
			Output("A group could not be created.", true, true, "GROUPS", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
		
		// Get "Form" subject ID
		$form_subject_id = GetSubjectID("Form");
		if(!$form_subject_id) {
			Output("A subject ID could not be found.", true, true, "SUBJECTS", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
		
		$find_result = mysqli_query($dbi, "SELECT * FROM classes WHERE class_mis_id = '".$mis_id."' LIMIT 2") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
		if(mysqli_num_rows($find_result) == 1) {
			$find_row = mysqli_fetch_assoc($find_result);
			
			// Compare fields for any necessary updates
			if($find_row['subject_id'] != $form_subject_id) {
				array_push($CHANGES['classes'], array("action" => "update",
				"data" => array("class_id" => $find_row['class_id'], "field" => "subject_id", "old_value" => $find_row['subject_id'], "new_value" => $form_subject_id),
				"description" => "Update subject ID for form class (ID: ".$find_row['class_id'].") from '".$find_row['subject_id']."' to '".$form_subject_id."'.",
				"result" => null));
			}
			if($find_row['group_id'] != $group_id) {
				array_push($CHANGES['classes'], array("action" => "update",
				"data" => array("class_id" => $find_row['class_id'], "field" => "group_id", "old_value" => $find_row['group_id'], "new_value" => $group_id),
				"description" => "Update group ID for form class (ID: ".$find_row['class_id'].") from '".$find_row['group_id']."' to '".$group_id."'.",
				"result" => null));
			}
		} elseif(mysqli_num_rows($find_result) == 0) {
			// Add form class
			array_push($CHANGES['classes'], array("action" => "add",
			"data" => array("class_mis_id" => $mis_id, "subject_id" => $form_subject_id, "group_id" => $group_id, "user_mis_id" => $user_mis_id),
			"description" => "Add form class (MIS ID: ".$mis_id.").",
			"result" => null));
		} else {
			// More than one form class found with the same ID!
			Output("Multiple form classes found with the same MIS ID (MIS ID: ".$mis_id.")!", true, true, "FORMS", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
		
		// change_data variable needs to be cleared, used for the immediate update
		unset($mis_id, $year_name, $group_name, $year_id, $group_id, $form_subject_id, $change_data);
	}
	
	// Build Classes CHANGES array - Use Classes array as they're effectively the same.
	foreach($FILES['classes'] as $key => $data) {
		
		// Create temporary variables and tidy them up.
		$mis_id = $data['mis_id'];
		$class_name = $data['name'];
		$subject_mis_id = $data['subject_mis_id'];
		
		Output("Checking class '".$class_name."' (MIS ID: ".$mis_id.")..", false, true, "CLASSES", "INFO");
		
		// Parse class name.
		list($year_name, $group_name) = SplitClassName($class_name, $subject_mis_id, $short_group_name);
		
		// GetSet Year
		$year_id = GetSetYearID($year_name);
		if(!$year_id) {
			Output("A year could not be created.", true, true, "YEARS", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
		
		// Get group id
		$group_id = GetSetGroupID($group_name, $year_id, "teaching");
		if(!$group_id) {
			Output("A group could not be created.", true, true, "GROUPS", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
		
		// Get subject ID
		$subject_id = GetSubjectIDFromMisID($subject_mis_id);
		if(!$subject_id) {
			Output("A subject ID could not be found.", true, true, "SUBJECTS", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
		
		$find_result = mysqli_query($dbi, "SELECT * FROM classes WHERE class_mis_id = '".$mis_id."' LIMIT 2") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
		if(mysqli_num_rows($find_result) == 1) {
			$find_row = mysqli_fetch_assoc($find_result);
			
			// Compare fields for any necessary updates
			// Apply changes immediate so that future comparisons are correct
			if($find_row['subject_id'] != $subject_id) {
				array_push($CHANGES['classes'], array("action" => "update",
				"data" => array("class_id" => $find_row['class_id'], "field" => "subject_id", "old_value" => $find_row['subject_id'], "new_value" => $subject_id),
				"description" => "Update subject ID for class (ID: ".$find_row['class_id'].") from '".$find_row['subject_id']."' to '".$subject_id."'.",
				"result" => null));
			}
			if($find_row['group_id'] != $group_id) {
				array_push($CHANGES['classes'], array("action" => "update",
				"data" => array("class_id" => $find_row['class_id'], "field" => "group_id", "old_value" => $find_row['group_id'], "new_value" => $group_id),
				"description" => "Update group ID for class (ID: ".$find_row['class_id'].") from '".$find_row['group_id']."' to '".$group_id."'.",
				"result" => null));
			}
			
			// FIX Check/Update owner here?
			
		} elseif(mysqli_num_rows($find_result) == 0) {
			// Add class
			array_push($CHANGES['classes'], array("action" => "add",
			"data" => array("class_mis_id" => $mis_id, "subject_id" => $subject_id, "group_id" => $group_id),
			"description" => "Add class (MIS ID: ".$mis_id.").",
			"result" => null));
		} else {
			// More than one class found with the same ID!
			Output("Multiple classes found with the same MIS ID (MIS ID: ".$mis_id.")!", true, true, "CLASSES", "ERROR");
			header("Location: ".$_POST['r']);
			exit;
		}
		
		// change_data variable needs to be cleared, used for the immediate update
		unset($mis_id, $year_name, $group_name, $subject_mis_id, $year_id, $group_id, $subject_id, $change_data);
	}
	
	// Check for classes that no longer exist.
	// Only check classes with a MIS ID set otherwise it'll remove manually added classes.
	$result = mysqli_query($dbi, "SELECT * FROM classes WHERE class_mis_id <> 0") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
	while($row = mysqli_fetch_assoc($result)) {
		// Check to see if the current DB subject is in the file
		$present = false;
		foreach($FILES['forms'] as $key => $data) {
			if($row['class_mis_id'] == $data['mis_id']) {
				$present = true;
				break;
			}
		}
		foreach($FILES['classes'] as $key => $data) {
			if($row['class_mis_id'] == $data['mis_id']) {
				$present = true;
				break;
			}
		}
		
		// If it's not in the file, it must be old, so remove it
		// The removal is not done immediately.
		if(!$present) {
			array_push($CHANGES['classes'], array("action" => "remove",
			"data" => array("class_id" => $row['class_id']),
			"description" => "Remove class (ID: ".$row['class_id'].").",
			"result" => null));
		}
		unset($present, $change_data);
	}
	
	// Apply Class changes now so that the group check (below) is accurate.
	ApplyChanges("classes");
	
	// Remove any groups that are no longer associated with any classes.
	$CHANGES['groups'] = array();
	$result = mysqli_query($dbi, "SELECT * FROM groups WHERE group_id NOT IN(SELECT DISTINCT group_id FROM classes)") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
	while($row = mysqli_fetch_assoc($result)) {
		array_push($CHANGES['groups'], array("action" => "remove",
		"data" => array("group_id" => $row['group_id']),
		"description" => "Remove group (ID: ".$row['group_id'].").",
		"result" => null));
	}
	
	// Remove only as any additions should have been made in-line by GetSetGroupID when checking the classes
	ApplyChanges("groups", "remove");
	
	
	
	// OWNERS
	// Build Owners OLD array
	/*$OLD['owners'] = array();
	$owners_result = mysqli_query($dbi, "SELECT * FROM class_ownership") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
	while($owners_row = mysqli_fetch_assoc($owners_result)) {
		if(!isset($OLD['owners'][$owners_row['class_id']])) {
			$OLD['owners'][$owners_row['class_id']] = array();
		}
		array_push($OLD['owners'][$owners_row['class_id']], array("ownership_id" => $owners_row['ownership_id'], "user_id" => $owners_row['user_id'], "priority" => $owners_row['ownership_priority']));
	}*/
	
	// Build Owners NEW array
	$NEW['owners'] = array();
	foreach($FILES['forms'] as $key => $data) {
		
		// Create temporary variables and tidy them up.
		// Only interested in the IDs to add/remove owners
		$class_mis_id = $data['mis_id'];
		$user_mis_id = $data['user_mis_id'];
		
		// Get MM IDs
		$class_id = GetClassIDFromMisID($class_mis_id);
		if(!$class_id) {
			Output("A class (MIS ID: ".$class_mis_id.") could not be found.", true, true, "CLASSES", "ERROR");
			exit;
		}
		
		$user_id = GetUserIDFromMisID($user_mis_id);
		if(!$user_id) {
			Output("A user (MIS ID: ".$user_mis_id.") could not be found.", true, true, "USERS", "ERROR");
			exit;
		}
		
		if(!isset($NEW['owners'][$class_id])) {
			$NEW['owners'][$class_id] = array();
		}
		array_push($NEW['owners'][$class_id], array("user_id" => $user_id, "main" => true));
	}
	
	// Set timezone for date/time functions later
	date_default_timezone_set('UTC');
	
	foreach($FILES['owners'] as $key => $data) {
		
		// Create temporary variables and tidy them up.
		// Only interested in the IDs to add/remove owners
		$class_mis_id = $data['class_mis_id'];
		$user_mis_id = $data['user_mis_id'];
		$main = $data['main'];
		$start_date = $data['start_date'];
		$end_date = $data['end_date'];
		
		// Convert priority field to boolean
		if($main == "T") {
			$main = true;
		} elseif($main == "F") {
			$main = false;
		} else {
			Output("An ownership priority could not be interpreted.", true, true, "OWNERS", "ERROR");
			exit;
		}
		
		// Convert date fields to unix timestamps
		$start_date = date_parse($start_date);
		$start_time = mktime($start_date['hour'], $start_date['minute'], $start_date['second'], $start_date['month'], $start_date['day'], $start_date['year']);
		$end_date = date_parse($end_date);
		$end_time = mktime($end_date['hour'], $end_date['minute'], $end_date['second'], $end_date['month'], $end_date['day'], $end_date['year']);
		
		// Check to see if the ownership applies today. If it doesn't, there's no point importing it.
		if($start_time >= time() OR $end_time < time()) {
			continue;
		}
		
		// Get MM IDs
		$class_id = GetClassIDFromMisID($class_mis_id);
		if(!$class_id) {
			Output("A class (MIS ID: ".$class_mis_id.") could not be found.", true, true, "CLASSES", "ERROR");
			exit;
		}
		
		$user_id = GetUserIDFromMisID($user_mis_id);
		if(!$user_id) {
			Output("A user (MIS ID: ".$user_mis_id.") could not be found.", true, true, "USERS", "ERROR");
			exit;
		}
		
		if(!isset($NEW['owners'][$class_id])) {
			$NEW['owners'][$class_id] = array();
		} else { // The following check is only necessary if the class sub-array already exists. There can't be duplicates if it's just been created!
			// The Owners file is pretty messed up, check that the owner isn't already in the NEW array
			$duplicate = false;
			foreach($NEW['owners'][$class_id] as $key => $user_data) {
				if($user_data['user_id'] == $user_id AND $user_data['main'] == $main) {
					//continue 2; // 2 so that it skips out of the check loop and the file loop
					$duplicate = true;
				}
			}
			
			if($duplicate) { // Owner already in array, skip.
				unset($duplicate);
				continue;
			}
		}
		
		//  SIMS erroneously duplicates primary users twice per class, once as main and once as not main.
		// The following deals with those duplicates.
		if($main) {
			foreach($NEW['owners'][$class_id] as $key => $user_data) {
				if($user_data['user_id'] == $user_id AND $user_data['main'] == false) {
					//Output("Duplicate non-main owner found when adding main user (ID: ".$user_id."), non-main owner removed.", false, true, "OWNERS", "INFO");
					unset($NEW['owners'][$class_id][$key]);
				}
			}
		}
		$duplicate = false;
		if(!$main) {
			foreach($NEW['owners'][$class_id] as $key => $user_data) {
				if($user_data['user_id'] == $user_id AND $user_data['main'] == true) {
					//Output("Duplicate main owner found when adding non-main user (ID: ".$user_id."), non-main owner skipped.", false, true, "OWNERS", "INFO");
					$duplicate = true;
				}
			}
		}
		
		if(!$duplicate) {
			array_push($NEW['owners'][$class_id], array("user_id" => $user_id, "main" => $main));
		}
	}
	
	// Correct NEW array based on sensible logic.
	foreach($NEW['owners'] as $class_id => $data) {
		$mains = 0;
		foreach($data as $key1 => $user_data) {
			if($user_data['main'] == true) {
				$mains++;
			}
		}
		
		if($mains == 0) { // No owners are set as main, arbitrarily pick the first owner.
			$NEW['owners'][$class_id][0]['main'] = true;
			Output("No main owners set for class (ID: ".$class_id."). Setting only owner (ID: ".$NEW['owners'][$class_id][0]['user_id'].") as main.", true, true, "OWNERS", "WARNING");
		} elseif($mains > 1) {
			foreach($data as $key1 => $user_data) { // More than one owner is set as main, set all to non-main.
				$NEW['owners'][$class_id][$key1]['main'] = false;
			}
			
			// Arbitrarily pick the first owner to be main
			$NEW['owners'][$class_id][0]['main'] = true;
			Output("Too many main owners set for class (ID: ".$class_id."). First owner (ID: ".$user_data['user_id'].") set as main.", true, true, "OWNERS", "WARNING");
		}
	}
	
	// Build Owners CHANGES array
	$CHANGES['owners'] = array();
	foreach($NEW['owners'] as $class_id => $data) {
		foreach($data as $key1 => $user_data) {
			$find_result = mysqli_query($dbi, "SELECT * FROM class_ownership WHERE class_id = '".$class_id."' AND user_id = '".$user_data['user_id']."'") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
			if(mysqli_num_rows($find_result) == 1) {
				$find_row = mysqli_fetch_assoc($find_result);
				
				// Compare fields for any necessary updates
				// Apply changes immediate so that future comparisons are correct
				if($user_data['main'] == true AND $find_row['ownership_priority'] != 0) {
					$change_data = array("ownership_id" => $find_row['ownership_id'], "field" => "ownership_priority", "old_value" => $find_row['ownership_priority'], "new_value" => "main", "class_id" => $class_id);
					array_push($CHANGES['owners'], array("action" => "update",
					"data" => $change_data,
					"description" => "Update priority for ownership (ID: ".$find_row['ownership_id'].") from '".$find_row['ownership_priority']."' to 'main'.",
					"result" => UpdateOwner($change_data)));
				} elseif($user_data['main'] == false AND $find_row['ownership_priority'] == 0) {
					$change_data = array("ownership_id" => $find_row['ownership_id'], "field" => "ownership_priority", "old_value" => $find_row['ownership_priority'], "new_value" => "non-main", "class_id" => $class_id);
					array_push($CHANGES['owners'], array("action" => "update",
					"data" => $change_data,
					"description" => "Update priority for ownership (ID: ".$find_row['ownership_id'].") from '".$find_row['ownership_priority']."' to 'non-main'.",
					"result" => UpdateOwner($change_data)));
				}
			} elseif(mysqli_num_rows($find_result) == 0) {
				// Add owner
				$change_data = array("class_id" => $class_id, "user_id" => $user_data['user_id'], "main" => $user_data['main']);
				array_push($CHANGES['owners'], array("action" => "add",
				"data" => $change_data,
				"description" => "Add owner (Class ID: ".$class_id.", User ID: ".$user_data['user_id'].").",
				"result" => AddOwner($change_data)));
			} else {
				// More than one class found with the same ID!
				Output("Multiple owners records found (Class ID: ".$class_id.", User ID: ".$user_data['user_id'].")!", true, true, "OWNERS", "ERROR");
				exit;
			}
		}
	}
	
	// Clean up NEW arrays
	unset($NEW['owners']);
	
	// Apply Owner changes now.
	ApplyChanges("owners");
	
	
	
	// SUBJECTS
	// Remove any subjects that are no longer associated with any classes. Run separately from main subject section as the classes (and their subject dependencies) need to be right first.
	$CHANGES['groups'] = array();
	$result = mysqli_query($dbi, "SELECT * FROM subjects WHERE subject_id NOT IN(SELECT DISTINCT subject_id FROM classes)") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
	while($row = mysqli_fetch_assoc($result)) {
		array_push($CHANGES['subjects'], array("action" => "remove",
		"data" => array("subject_id" => $row['subject_id']),
		"description" => "Remove subject (ID: ".$row['subject_id'].").",
		"result" => null));
	}
	
	// Remove only as any additions should have been made in-line by GetSetGroupID when checking the classes
	ApplyChanges("subjects", "remove");
	
	
	
	// STUDENTS
	// Build Students CHANGES array
	$CHANGES['students'] = array();
	foreach($FILES['students'] as $key => $data) {
		
		// Create temporary variables and tidy them up.
		$mis_id = $data['mis_id'];
		$upn = md5($data['upn']);
		$first_name = CleanName($data['first_name']);
		$surname = CleanName($data['surname']);
		$form_mis_id = $data['form_mis_id'];
		$house_name = ucwords(trim($data['house']));
		
		Output("Checking student '".$first_name." ".$surname."' (MIS ID: ".$mis_id.")..", false, true, "STUDENTS", "INFO");
		
		$form_id = GetClassIDFromMisID($form_mis_id);
		if(!$form_id) {
			Output("A form class (MIS ID: ".$form_mis_id.") could not be found.", true, true, "CLASSES", "ERROR");
			exit;
		}
		$house_id = GetSetHouseID($house_name);
		if($house_id === false) { // Precise comparison as valid return can be zero.
			Output("A house (Name: ".$house_name.") could not be found.", true, true, "HOUSES", "ERROR");
			exit;
		}
		
		$find_result = mysqli_query($dbi, "SELECT * FROM students WHERE student_mis_id = '".$mis_id."' LIMIT 2") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
		if(mysqli_num_rows($find_result) == 1) {
			$find_row = mysqli_fetch_assoc($find_result);
			
			// Compare fields for any necessary updates
			// Apply changes immediate so that future comparisons are correct
			// Student UPN has been replaced by MIS ID - still recorded in case of emergencies
			if($find_row['student_upn'] != $upn) {
				array_push($CHANGES['students'], array("action" => "update",
				"data" => array("student_id" => $find_row['student_id'], "field" => "student_upn", "old_value" => $find_row['student_upn'], "new_value" => $upn),
				"description" => "Update UPN for student '".$find_row['student_first_name']." ".$find_row['student_surname']."' (ID: ".$find_row['student_id'].") from '".$find_row['student_upn']."' to '".$upn."'.",
				"result" => null));
			}
			if($find_row['student_first_name'] != $first_name) {
				array_push($CHANGES['students'], array("action" => "update",
				"data" => array("student_id" => $find_row['student_id'], "field" => "student_first_name", "old_value" => $find_row['student_first_name'], "new_value" => $first_name),
				"description" => "Update first name for student '".$find_row['student_first_name']." ".$find_row['student_surname']."' (ID: ".$find_row['student_id'].") from '".$find_row['student_first_name']."' to '".$first_name."'.",
				"result" => null));
			}
			if($find_row['student_surname'] != $surname) {
				array_push($CHANGES['students'], array("action" => "update",
				"data" => array("student_id" => $find_row['student_id'], "field" => "student_surname", "old_value" => $find_row['student_surname'], "new_value" => $surname),
				"description" => "Update surname for student '".$find_row['student_first_name']." ".$find_row['student_surname']."' (ID: ".$find_row['student_id'].") from '".$find_row['student_surname']."' to '".$surname."'.",
				"result" => null));
			}
			if($find_row['student_form_id'] != $form_id) {
				array_push($CHANGES['students'], array("action" => "update",
				"data" => array("student_id" => $find_row['student_id'], "field" => "student_form_id", "old_value" => $find_row['student_form_id'], "new_value" => $form_id),
				"description" => "Update form for student '".$find_row['student_first_name']." ".$find_row['student_surname']."' (ID: ".$find_row['student_id'].") from '".$find_row['student_form_id']."' to '".$form_id."'.",
				"result" => null));
			}
			if($find_row['student_house_id'] != $house_id) {
				array_push($CHANGES['students'], array("action" => "update",
				"data" => array("student_id" => $find_row['student_id'], "field" => "student_house_id", "old_value" => $find_row['student_house_id'], "new_value" => $house_id),
				"description" => "Update house for student '".$find_row['student_first_name']." ".$find_row['student_surname']."' (ID: ".$find_row['student_id'].") from '".$find_row['student_house_id']."' to '".$house_id."'.",
				"result" => null));
			}
			
			// The following values aren't supplied by SIMS, but should be updated if they're empty. Not normal comparisons though as it may have been manually changed.
			$username = GenerateStudentUsername($data['first_name'], $data['surname']);
			
			if(empty($find_row['student_username'])) {
				array_push($CHANGES['students'], array("action" => "update",
				"data" => array("student_id" => $find_row['student_id'], "field" => "student_username", "old_value" => $find_row['student_username'], "new_value" => $username),
				"description" => "Update username for student '".$find_row['student_first_name']." ".$find_row['student_surname']."' (ID: ".$find_row['student_id'].") from '".$find_row['student_username']."' to '".$username."'.",
				"result" => null));
			}
			if(empty($find_row['student_password']) OR empty($find_row['student_password_salt'])) {
				$password = GenerateStudentPassword(); // This is in functions.php
				array_push($CHANGES['students'], array("action" => "update",
				"data" => array("student_id" => $find_row['student_id'], "field" => "student_password", "old_value" => "(inconsistent/empty)", "new_value" => $password),
				"description" => "Update password for student '".$find_row['student_first_name']." ".$find_row['student_surname']."' (ID: ".$find_row['student_id'].").",
				"result" => null));
			}
		} elseif(mysqli_num_rows($find_result) == 0) {
			// Add user
			array_push($CHANGES['students'], array("action" => "add",
			"data" => array("student_mis_id" => $mis_id, "first_name" => $first_name, "surname" => $surname, "form_id" => $form_id, "house_id" => $house_id),
			"description" => "Add student '".$first_name." ".$surname."'.",
			"result" => null));
		} else {
			// More than one user found with the same ID!
			Output("Multiple students found with the same MIS ID (MIS ID: ".$mis_id.")!", true, true, "STUDENTS", "ERROR");
			//header("Location: ".$_POST['r']);
			exit;
		}
		
		// change_data variable needs to be cleared, used for the immediate update
		unset($mis_id, $first_name, $surname, $form_mis_id, $form_id, $house_mis_id, $house_id, $change_data);
	}
	
	// Check for students that no longer exist.
	// Only check students with a MIS ID set otherwise it'll remove manually added students.
	$students_result = mysqli_query($dbi, "SELECT * FROM students WHERE student_mis_id <> 0") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
	while($students_row = mysqli_fetch_assoc($students_result)) {
		// Check to see if the current DB user is in the file
		$present = false;
		foreach($FILES['students'] as $key => $data) {
			if($students_row['student_mis_id'] == $data['mis_id']) {
				$present = true;
			}
		}
		
		// If it's not in the file, it must be old, so remove it
		if(!$present) {
			array_push($CHANGES['students'], array("action" => "remove",
			"data" => array("student_id" => $students_row['student_id']),
			"description" => "Remove student '".$students_row['student_first_name']." ".$students_row['student_surname']."' (ID: ".$students_row['student_id'].").",
			"result" => null));
		}
		unset($present, $change_data);
	}
	
	//Apply student changes now.
	ApplyChanges("students");
	
	
	
	// MEMBERSHIPS
	// Build Memberships OLD array
	$OLD['memberships'] = array();
	$memberships_result = mysqli_query($dbi, "SELECT * FROM class_memberships") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
	while($memberships_row = mysqli_fetch_assoc($memberships_result)) {
		if(!isset($OLD['memberships'][$memberships_row['class_id']])) {
			$OLD['memberships'][$memberships_row['class_id']] = array();
		}
		array_push($OLD['memberships'][$memberships_row['class_id']], array("membership_id" => $memberships_row['membership_id'], "student_id" =>$memberships_row['student_id']));
	}
	
	// Build Memberships NEW array
	$NEW['memberships'] = array();
	foreach($FILES['students'] as $key => $data) {
		
		// Create temporary variables and tidy them up.
		// Only interested in the IDs to add/remove owners
		$student_mis_id = $data['mis_id'];
		$form_mis_id = $data['form_mis_id'];
		
		// Get MM IDs
		$student_id = GetStudentIDFromMisID($student_mis_id);
		if(!$student_id) {
			Output("A student (MIS ID: ".$student_mis_id.") could not be found.", true, true, "STUDENTS", "ERROR");
			exit;
		}
		$form_id = GetClassIDFromMisID($form_mis_id);
		if(!$form_id) {
			Output("A form class (MIS ID: ".$form_mis_id.") could not be found.", true, true, "CLASSES", "ERROR");
			exit;
		}
		
		if(!isset($NEW['memberships'][$form_id])) {
			$NEW['memberships'][$form_id] = array();
		}
		array_push($NEW['memberships'][$form_id], $student_id);
	}
	
	foreach($FILES['class_memberships'] as $key => $data) {
		
		// Create temporary variables and tidy them up.
		// Only interested in the IDs to add/remove owners
		$class_mis_id = $data['class_mis_id'];
		$student_mis_id = $data['student_mis_id'];
		
		// Get MM IDs
		$class_id = GetClassIDFromMisID($class_mis_id);
		if(!$class_id) {
			Output("A class (MIS ID: ".$class_mis_id.") could not be found.", true, true, "CLASSES", "ERROR");
			exit;
		}
		$student_id = GetStudentIDFromMisID($student_mis_id);
		if(!$student_id) {
			Output("A student (MIS ID: ".$student_mis_id.") could not be found.", true, true, "STUDENTS", "ERROR");
			exit;
		}
		
		if(!isset($NEW['memberships'][$class_id])) {
			$NEW['memberships'][$class_id] = array();
		}
		
		array_push($NEW['memberships'][$class_id], $student_id);
	}
	
	// Build Memberships CHANGES array
	$CHANGES['memberships'] = array();
	foreach($NEW['memberships'] as $class_id => $students) {
		foreach($students as $key1 => $student_id) {
			$student_present = false;
			if(isset($OLD['memberships'][$class_id])) {
				foreach($OLD['memberships'][$class_id] as $key => $student_data) {
					if($student_data['student_id'] == $student_id) {
						$student_present = true;
					}
				}
			}
			
			if(!$student_present) {
				array_push($CHANGES['memberships'], array("action" => "add",
				"data" => array("class_id" => $class_id, "student_id" => $student_id),
				"description" => "Add membership (Class ID: ".$class_id.", Student ID: ".$student_id.").",
				"result" => null));
			}
		}
	}
	
	// Remove any Memberships that are not in the NEW array
	foreach($OLD['memberships'] as $class_id => $student_data) {
		foreach($student_data as $key1 => $membership) {
			$student_present = false;
			if(isset($NEW['memberships'][$class_id])) {
				foreach($NEW['memberships'][$class_id] as $key => $student_id) {
					if($student_id == $membership['student_id']) {
						$student_present = true;
					}
				}
			}
			
			if(!$student_present) {
				array_push($CHANGES['memberships'], array("action" => "remove",
				"data" => array("membership_id" => $membership['membership_id']),
				"description" => "Remove membership (Membership ID: ".$membership['membership_id'].").",
				"result" => null));
			}
		}
	}
	
	// Clean up OLD and NEW arrays
	unset($OLD['memberships'], $NEW['memberships']);
	
	// Apply Memberships changes now.
	ApplyChanges("memberships");
	
	
	
	Output("Finalising import..", false, true, "FINAL", "INFO");
	
	// Final Bits
	// Remove empty classes
	$empty_result = mysqli_query($dbi, "SELECT * FROM classes AS c WHERE 0 = (SELECT COUNT(*) FROM class_memberships WHERE c.class_id = class_memberships.class_id)") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
	while($empty_row = mysqli_fetch_assoc($empty_result)) {
		RemoveClass(array("class_id" => $empty_row['class_id']));
	}
	
	// Remove empty houses
	$empty_result = mysqli_query($dbi, "SELECT * FROM houses AS h WHERE 0 = (SELECT COUNT(*) FROM students WHERE h.house_id = students.student_house_id)") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
	while($empty_row = mysqli_fetch_assoc($empty_result)) {
		RemoveHouse(array("house_id" => $empty_row['house_id']));
	}
	
	// Remove invalid class ownership records
	$empty_result = mysqli_query($dbi, "SELECT * FROM class_ownership WHERE user_id NOT IN (SELECT user_id FROM users) OR class_id NOT IN (SELECT class_id FROM classes)") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
	while($empty_row = mysqli_fetch_assoc($empty_result)) {
		RemoveOwner(array("ownership_id" => $empty_row['ownership_id']));
	}
	
	// Remove invalid class membership records
	$empty_result = mysqli_query($dbi, "SELECT * FROM class_memberships WHERE student_id NOT IN (SELECT student_id FROM students) OR class_id NOT IN (SELECT class_id FROM classes)") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
	while($empty_row = mysqli_fetch_assoc($empty_result)) {
		RemoveMembership(array("membership_id" => $empty_row['membership_id']));
	}
	
	// Form multipliers
	$average_result = mysqli_query($dbi, "SELECT AVG(count_column1) as avg FROM (SELECT class_id, COUNT(*) AS count_column1 FROM class_memberships WHERE class_id IN (SELECT classes.class_id FROM classes LEFT JOIN groups ON groups.group_id = classes.group_id WHERE groups.group_type = 'form') GROUP BY class_id) as t1") or die(MysqlError($_SERVER['REQUEST_URI'], __LINE__, mysqli_error($dbi)));
	$average_row = mysqli_fetch_assoc($average_result);
	$form_average = round($average_row['avg'], 2);
	
	if(!SetConfigValue("form_average", $form_average)) {
		Output("There was a problem updating the master form average.", true, true, "FINAL", "ERROR");
	}
	
	UpdateFormMultipliers($form_average);
	
	Output("Import completed.", true, true, "FINAL", "INFO");
	
	//echo $_SESSION['messages'];
	//unset($_SESSION['messages']);
	
	
	
	$_SESSION['import']['success'] = true;
	header("Location: ".$_POST['r']);
}

?>