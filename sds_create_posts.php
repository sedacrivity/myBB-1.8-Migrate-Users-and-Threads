<?php

define("IN_MYBB", 1);

// Load MyBB core files
require_once dirname(__FILE__)."/inc/init.php";

// Functions
require_once MYBB_ROOT."inc/functions.php"; 

// User handling
require_once MYBB_ROOT."inc/datahandlers/user.php";

// Post handling
require_once MYBB_ROOT."inc/datahandlers/post.php";

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
		$att_folder = $mybb->input['att_folder'];
		$destination_forum = $mybb->input['destination_forum'];
		$simulate = $mybb->input['simulate'];


		// inform user about received data
		echo "<H1>Starting Creation Posts</H1>";
		echo "<P>Received input data:<br></br>";
		echo "-> input file = ".$input_file."<br>";
		echo "-> attachments source folder = ".$att_folder."<br>";
		echo "-> destination forum = ".$destination_forum."<br>";
		echo "-> simulate = ".$simulate."<br>";
		echo "</P>";
		
		// Check for required input
		if ( empty( $input_file ) ) {
		
			die("Error: input file cannot be empty");
		}
		
		if ( empty( $destination_forum ) ) {
		
			die("Error: destination forum cannot be empty");
		}
						
		// Try to load the data XML file					
		$xml=simplexml_load_file($input_file) or die("Error: Cannot open data xml file ".$input_file);
		
		// Go over the posts
		foreach($xml->children() as $post) { 

			// Generate a thread
			sds_generate_thread($post, $att_folder, $destination_forum, $simulate );

		}



		// Leave ( LAST LINE )
		break;
	default:
				
		$form = new Form("sds_create_posts.php?action=create","POST");
				
		$form_container = new FormContainer("Generate Threads Data");		
		
		
		$form_container->output_row(
					"Input File",
					"The input XML file?",
					$form->generate_text_box('input_file', "data_posts_test.xml", array('id' => 'input_file')));		
			
		$form_container->output_row(
					"Attachment source folder( on server )",
					"Attachment source folder on server which holds the attachments referenced within your input file - do NOT start with '/'",
					$form->generate_text_box('att_folder', "conversion", array('id' => 'att_folder')));					
			
		$form_container->output_row(
					"Forums",
					"Select the forums in which you would like to generate the threads in.",
					$form->generate_forum_select('destination_forum', "", array('id' => 'forum','multiple'=>false,'size'=>10)),
					'forum'
				);	
			
			
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


function sds_generate_thread($post_element, $att_folder, $destination_forum_id, $simulate )
{
	// Grab content
	$post_id = $post_element->post_id;
	$post_subject = $post_element->title;
	$post_user    = $post_element->username;
	$post_message = $post_element->content;
	$post_datetime = sds_get_datetime($post_element->creation_datetime);
	
	$localDate = new DateTime("@$post_datetime");	

	// Try to determine the potential user
	$user = sds_get_user($post_user);
	
	// Determine unique post hash
	$post_hash = md5($user['uid'].random_str());
				
	// Set new post data							
	$new_thread = array("fid" => $destination_forum_id,
	                    "prefix" => 0,
			    "subject" => $post_subject,
			    "icon" => 0,
			    "uid" => $user['uid'],
			    "username" => $user['username'],
			    "message" => $post_message,
			    "dateline" => $localDate->getTimestamp(),
			    "ipaddress" => get_ip(),
			    "posthash" =>$post_hash
			   );


	// Start of Inform		
	sds_echo_header("Generating thread/post with ID '".$post_id ."' and subject '".$post_subject."' for user '".$user['username']."' created on '".date_format($localDate,'Y-m-d H:i:s')."' with unique post hash '".$post_hash."'");

	// Data handler
	$posthandler = new PostDataHandler("insert");
	$posthandler->action = "thread";
	$posthandler->set_data($new_thread);
	
	// Validate the newly to be thread
	if($posthandler->validate_thread())
	{
	
		// Are we simulation or not ?
		if ( $simulate == '1' )
		{
			// Simulate
			sds_echo_info("<br>The new thread data is VALID and would be created if not in simulation modus");
			
			// Create the attachments ( if any )
			sds_create_attachements($att_folder, $post_element, 0, 0, $post_hash, $user['uid'], $post_datetime, $simulate);

		}
		else
		{
			// Create the new thread
			$thread_info = $posthandler->insert_thread();
			
			// Info
			sds_echo_success("<br>The new thread is created successfully with thread ID '".$thread_info['tid']."' and post id '".$thread_info['pid']."'");
			
			// Create the attachments ( if any )
			sds_create_attachements($att_folder, $post_element, $thread_info['tid'], $thread_info['pid'], $post_hash, $user['uid'], $post_datetime, $simulate);
			
		}	
		
		// Check if we have replies as well
		$replies = $post_element->xpath('replies/reply');
		
		// If we do
		if ( $replies != false )
		{
		
			echo "<br><br>The thread has ".count($replies)." replies ... those will be created now";		
			
			// Process the replies
			foreach($replies as $x => $x_reply)
			{
			
				// Generate a reply
				sds_generate_replies($x_reply, $att_folder, $destination_forum_id, $thread_info['tid'], $thread_info['pid'], $simulate);
			}
			
		}	
		

	}
	else 
	{
		echo "<br>New THREAD is NOT valid !";
		
		sds_display_errors($posthandler->get_friendly_errors());
	}

	// End of Inform
	echo "</P>";	
}

function sds_generate_replies($post_element, $att_folder, $destination_forum_id, $thread_id, $parent_id, $simulate )
{


	// Grab content
	$post_id      = $post_element->reply_id;
	$post_subject = $post_element->title;
	$post_user    = $post_element->username;
	$post_message = $post_element->content;
	$post_datetime = sds_get_datetime($post_element->creation_datetime);
	
	$localDate = new DateTime("@$post_datetime");

	// Try to determine the potential user
	$user = sds_get_user($post_user);
	
	// Determine unique post hash
	$post_hash = md5($user['uid'].random_str());
						
	// Set new post data							
	$post = array(
		"tid" => $thread_id,
		"replyto" => $parent_id ,
		"fid" => $destination_forum_id,
		"subject" => $post_subject,
		"icon" => 0,
		"uid" => $user['uid'],
		"username" => $user['username'],
		"message" => $post_message,
		"dateline" => $localDate->getTimestamp(),
		"ipaddress" => get_ip(),
		"posthash" => $post_hash
						);


	// Start of Inform	
	sds_echo_header("-> Generating post with ID '".$post_id ."' and subject '".$post_subject."' for user '".$user['username']."' created on '".date_format($localDate,'Y-m-d H:i:s')."' with unique post hash '".$post_hash."'");

	// Data handler						
	$posthandler = new PostDataHandler("insert");
	$posthandler->action = "post";
	$posthandler->set_data($post);
	
	// Validate the newly to be thread
	if($posthandler->validate_post())
	{
	
		// Are we simulation or not ?
		if ( $simulate == '1' )
		{
			// Info
			sds_echo_info("The new post data is VALID and would be created if not in simulation modus");
			
			
			// Create the attachments ( if any )
			sds_create_attachements($att_folder, $post_element, 0, 0, $post_hash, $user['uid'], $post_datetime, $simulate);
		}
		else
		{
			// Sleep 
			//sleep(2);
		
			// Create the new thread
			$post_info = $posthandler->insert_post();
			
			// Info
			echo "<br>The new post is created successfully with ID '".$post_info['pid']."'";
			
			// Create the attachments ( if any )
			sds_create_attachements($att_folder, $post_element, $thread_id, $post_info['pid'], $post_hash, $user['uid'], $post_datetime, $simulate);
		}	
		
		// Check if we have replies as well
		$replies = $post_element->xpath('replies/reply');
		
		// If we do
		if ( $replies != false )
		{
		
			echo "<br><br>The post has ".count($replies)." replies ... those will be created now";				
		
			// Process the replies
			foreach($replies as $x => $x_reply)
			{
			
				// Generate a reply
				sds_generate_replies($x_reply, $destination_forum_id, $thread_id, $post_info['pid'], $simulate );
			}
			
		}	
		

	}
	else 
	{
		echo "<br>New THREAD is NOT valid !";
		
		sds_display_errors($posthandler->get_friendly_errors());
	}	

	// End of Inform
	echo "</P>";	

} 


function sds_get_user($userid)
{
        $options = array(
              'fields' => array('username', 'usergroup', 'additionalgroups', 'displaygroup')
        );

	$user =	get_user_by_username($userid, $options);
	
	if ( empty($user['username']) ) 
	{
		
		$user = get_user_by_username("admin", $options);
	}

	/*
 	echo "<br>Found user = ";
	sds_display_array($user);*/
	
	return $user;
}


function sds_create_attachements($att_folder, $post_element, $thread_id, $post_id, $posthash, $userID, $CreationTime, $simulate)
{

	// Check if we have any attachemnts
	$attachments = $post_element->xpath('attachments/attachment');

	// Set counters
	$attachment_count  = 0;
	$attachment_failed = 0;
	
	// If we do
	if ( $attachments != false )
	{
		
		// Process the attachments
     	   	foreach($attachments as $x => $x_attachment)
		{
		
			// Increase attachment counter
			$attachment_count++;
			
			// Get the attachment info
			$attachment_filename = $x_attachment->filename;
			$attachment_type     = $x_attachment->type;
			
			// Are we simulation or not ?
			if ( $simulate == '1' )
			{
				sds_echo_info("The attachment file '".$attachment_filename."' of type '".$attachment_type."' would be created if not in simulation modus<br>");
			}
			
			// Upload the attachment
			sds_upload_attachment($att_folder,$thread_id, $post_id, $posthash, $attachment_filename, $attachment_type, $userID, $CreationTime, $simulate);
			
			// Line break
			sds_echo_linebreak();
			
		}
		
		if ( $attachment_failed == 0 )
		{
	
			// Not simulating
			if ( $simulate != '1' )
			{
	
				// Info
				sds_echo_success("Attachments created successfully");	
			
			}
	
			// Return we are fine
			return true;
		
		}
		else 
		{ 
	
			// Info
			sds_echo_error("Could not create attachments");	
	
			// Return we had issues
			return false;
		}
	
	}
	
	return true;
}

function sds_upload_attachment($att_folder,$thread_id, $post_id, $posthash, $attachment_filename, $attachment_type, $userID, $CreationTime, $simulate)
{

	global $db;
	
	// Target uploads folder
	$uploadspath = "uploads";
	$sourcepath = $att_folder;

	// Generate time instance	
	$localCreationTime = new DateTime("@$CreationTime");
	
	// Check if the attachment directory (YYYYMM) exists, if not, create it
	$month_dir = date_format($localCreationTime,"Ym");
	
	//sds_echo_debug("Directory month ".$month_dir);
	
	// Directory for the fime
	$file_dir = $uploadspath."/".$month_dir;
	
	// If the directory does not yet exists
	if(!@is_dir($file_dir))
	{
	
		// If we are simulating
		if ( $simulate == '1' )
		{
		
			sds_echo_info("Upload directory '".$file_dir."' would be created if not simulating");
		
		}
		else
		{
	
			// Create the directory
			@mkdir($file_dir);
		
			// Still doesn't exist - oh well, throw it in the main directory
			if(!@is_dir($file_dir))
			{
				$file_dir = $uploadspath;
				$month_dir = '';
			}
			else
			{
				$index = @fopen($file_dir."/index.html", 'w');
				@fwrite($index, "<html>\n<head>\n<title></title>\n</head>\n<body>\n&nbsp;\n</body>\n</html>");
				@fclose($index);
			}
		}
	}
	

	// New file name
	$filename = "post_".$userID."_".$CreationTime."_".md5(random_str()).".attach";

	// Source & Target
	$fileSource      = $sourcepath."/".$attachment_filename;	
	$fileDestination = $file_dir."/".$filename;
	
	// File size
	$fileSize = filesize($fileSource);
	
	
	// If we are simulating
	if ( $simulate == '1' )
	{
	
		sds_echo_info("Source file '".$fileSource."' would be copied to destination '".$fileDestination."' with file size '".$fileSize."' bytes'");
		
	}
	else 
	{	
		// Let's copy the file from the conversion folder towards the uploads folder
		if (!copy ($fileSource , $fileDestination )) 
		{
	
			// Show error
			sds_echo_error("Unable to copy source file '".$fileSource."' to destination '".$fileDestination."' with file size '".$fileSize."' bytes'");
			
			// Return
			return false;
		}
		
		// Determine the attachment name
		$attachmentName = $month_dir."/".$filename;
		
		
		// Generate the array for the insert_query
		$attacharray = array(
			"pid" => $post_id,
			"posthash" => $posthash,
			"uid" => $userID,
			"filename" => $db->escape_string($attachment_filename),
			"filetype" => $db->escape_string($attachment_type),
			"filesize" => $fileSize,
			"attachname" => $attachmentName,
			"downloads" => 0,
			"dateuploaded" => $CreationTime,
			"visible" => 1
		);
		
		// Get the file extension
		$ext = get_extension($attachment_filename);
		
//		sds_echo_debug("Attachment file extentions = ".$ext); 
		
		// If we're uploading an image, check the MIME type compared to the image type and attempt to generate a thumbnail
		if($ext == "gif" || $ext == "png" || $ext == "jpg" || $ext == "jpeg" || $ext == "jpe")
		{

			// Get image library
			require_once MYBB_ROOT."inc/functions_image.php";
			
			// Get thumbname
			$thumbname = str_replace(".attach", "_thumb.$ext", $filename);
			
			// Generate thumbnail
			$thumbnail = generate_thumbnail($fileDestination, $file_dir, $thumbname, 96, 96);

			// If we got one
			if($thumbnail['filename'])
			{
				// Set our data for sql
				$attacharray['thumbnail'] = $month_dir."/".$thumbname;
			}
			elseif($thumbnail['code'] == 4)
			{
				// Set our data for sql
				$attacharray['thumbnail'] = "SMALL";
			}		
		}			
	
	}
	
	
	// Let's insert the attachment
	$aid = $db->insert_query("attachments", $attacharray);

	// If we have a post ID
	if($post_id)
	{
		update_thread_counters($thread_id, array("attachmentcount" => "+1"));
	}	
	
	
	// Return we are fine
	return true;
	
}

function sds_get_datetime($post_datetime)
{

	if ($post_datetime)
	{
	
		return $post_datetime;
	
	}
	else 
	{	
		$date = new DateTime();
	
		return $date->getTimestamp();	
	}

}
?>

