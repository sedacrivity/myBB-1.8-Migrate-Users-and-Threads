<?php

define("IN_MYBB", 1);

// Load MyBB core files
require_once dirname(__FILE__)."/inc/init.php";

// User handling
require_once MYBB_ROOT."inc/datahandlers/user.php";

// Admin
require_once MYBB_ROOT."admin/inc/class_page.php";
require_once MYBB_ROOT."admin/inc/class_form.php";
require_once MYBB_ROOT."admin/inc/class_table.php";

require_once MYBB_ROOT."admin/styles/default/style.php";

// Our own utitilies
require_once MYBB_ROOT."sds_utilities.php";

// Create the session
require_once MYBB_ROOT."inc/class_session.php";
$session = new session;
$session->init();

// Load the language we'll be using
if(!isset($mybb->settings['bblanguage']))
{
	$mybb->settings['bblanguage'] = "english";
}


$action = $mybb->input['action'];

switch($action)
{
	case 'create':
	

		// Grab input
		$input_file = $mybb->input['input_file'];
		$simulate = $mybb->input['simulate'];
		$initalpassword = $mybb->input['initalpassword'];


		// inform user about received data
		sds_echo_header("Starting Creation Users");
		sds_echo_info("Received input data:");
		sds_echo_info("-> input file = ".$input_file);
		sds_echo_info("-> simulate = ".$simulate);
		
		// Check for required input
		if ( empty( $input_file ) ) {
		
			die("Error: input file cannot be empty");
		}
		
						
		// Try to load the data XML file					
		$xml=simplexml_load_file($input_file) or die("Error: Cannot open data xml file ".$input_file);
		
		
		// Are we simulation or not ?
		if ( $simulate == '1' )
		{
    
			// Simulate			
			sds_echo_warning("<br>Validity of the data is not checked in simulation modus so there might still be errors though ...");    
    
    		}
    		
    		echo "<br>";
		
		
		$created_count=0;
		
		
		// Go over the posts
		foreach($xml->children() as $user) { 

			// Generate a user
			sds_generate_user($user, $initalpassword, $simulate, $created_count);

		}

		
		if ( $simulate == '0' )
		{
		
			echo "<br>"; 
			echo "<br>"; 
			echo "Created users = " . $created_count;
		
		}
	
		// Leave ( LAST LINE )
		break;
	default:
				
		$form = new Form("sds_create_users.php?action=create","POST");
				
		$form_container = new FormContainer("Generate Users");		
		
		
		$form_container->output_row(
					"Input File",
					"The input XML file?",
					$form->generate_text_box('input_file', "data_users_test.xml", array('id' => 'input_file')));				

		$form_container->output_row(
					"Initial Password",
					"The initial password for the user account",
					$form->generate_text_box('initalpassword', "Initial1234", array('id' => 'initalpassword')));	
				
		$form_container->output_row(
					"Simulate",
					"Simulate the creation ( no actual data will be saved )",
					$form->generate_check_box('simulate', '1', "",array('checked'=>true) )
				);
				
		$form_container->end();
				
		$buttons[] = $form->generate_submit_button("Generate");
		$form->output_submit_wrapper($buttons);
				
		$form->end();
		break;
}

die();	
	


function sds_generate_user($user, $initalpassword, $simulate, $created_count )
{

//  Print info	
    echo "<br>"; 
    
//  Set local variables    
    $username = $user->username;
    $useremail = $user->email;
    $realname = $user->firstname." ".$user->lastname;
    
    echo $username . ", ";
    echo $useremail . ", ";
    echo $realname; 
    
    echo "<br>"; 
    
//  Check if user exists
    $user = get_user_by_username($username);
    
//  If the user exists already	    
    if($user['uid'])
    {
		
	sds_echo_warning("Skipping as User exists already!");
		
    }
    else
    {
	// Are we simulation or not ?
	if ( $simulate == '1' )
	{
    
		// Simulate
		sds_echo_info("<br>The user does not exist and would be created if not in simulation modus");
    
    	}
    	else
    	{
    	
	    	sds_echo_info("User does not exist yet so creating");
	    	
	    	// Custom profile fields
	    	$custom_fields = array(
	    			  "fid4" => $realname  // Real Name
	    			);  
		// New User data
		$new_user = array("username" => $username,
				  "password" => $initalpassword,
				  "password2" => $initalpassword,
			      	  "email" => $useremail,
				  "usergroup" => 2, // Registered users
				  "displaygroup" => 2,
				  "profile_fields" => $custom_fields,
				  "profile_fields_editable" => true,
						);
						
		$userhandler = new UserDataHandler('insert');
		$userhandler->set_data($new_user);	

		if(!$userhandler->verify_username())
		{ 
			sds_echo_error("Invalid user id");
		
		}

		if($userhandler->validate_user())
		{
			$userhandler->insert_user();
			$created_count++;	
	
			sds_echo_info("User successfully created ...");
		}
		else 
		{
		
			sds_echo_error("User is NOT valid !");		
		
			$errors = $userhandler->get_friendly_errors();
		
			sds_display_errors($errors);
							
		}
	}
	

    }   
    echo "<br>"; 
	
} 


?>