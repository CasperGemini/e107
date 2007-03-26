<?php
/*
+ ----------------------------------------------------------------------------+
|     e107 website system
|
|     �Steve Dunstan 2001-2002
|     http://e107.org
|     jalist@e107.org
|
|     Released under the terms and conditions of the
|     GNU General Public License (http://gnu.org).
|
|     $Source: /cvs_backup/e107_0.8/contact.php,v $
|     $Revision: 1.2 $
|     $Date: 2007-03-26 06:34:56 $
|     $Author: e107coders $
+----------------------------------------------------------------------------+
*/
require_once("class2.php");

    // security image may be disabled by removing the appropriate shortcodes from the template.
	require_once(e_HANDLER."secure_img_handler.php");
	$sec_img = new secure_image;


require_once(HEADERF);

if (!$CONTACT_FORM) {
	if (file_exists(THEME."contact_template.php")) {
		require_once(THEME."contact_template.php");
	} else {
		require_once(e_THEME."templates/contact_template.php");
	}
}

if(isset($_POST['send-contactus'])){

	$error = "";

	$sender_name = $tp->toEmail($_POST['author_name'],TRUE,"rawtext");
	$sender = check_email($_POST['email_send']);
	$subject = $tp->toEmail($_POST['subject'],TRUE,"rawtext");
	$body = $tp->toEmail($_POST['body'],TRUE);

// Check Image-Code
    if (isset($_POST['rand_num']) && !$sec_img->verify_code($_POST['rand_num'], $_POST['code_verify']))
	{
		$error .= LANCONTACT_15."\\n";
	}

// Check message body.
	if(strlen(trim($_POST['body'])) < 15)
	{
		$error .= LANCONTACT_12."\\n";
    }

// Check subject line.
	if(strlen(trim($_POST['subject'])) < 2)
	{
		$error .= LANCONTACT_13."\\n";
    }

	if(!strpos(trim($_POST['email_send']),"@"))
	{
		$error .= LANCONTACT_11."\\n";
    }


// Check email address on remote server (if enabled).
	if ($pref['signup_remote_emailcheck'] && $error == "")
	{
		require_once(e_HANDLER."mail_validation_class.php");
		list($adminuser,$adminhost) = split ("@", SITEADMINEMAIL);
		$validator = new email_validation_class;
		$validator->localuser= $adminuser;
		$validator->localhost= $adminhost;
		$validator->timeout=3;
		//	$validator->debug=1;
		//	$validator->html_debug=1;
		if($validator->ValidateEmailBox($sender) != 1)
		{
			$error .= LANCONTACT_11."\\n";
		}

	}

// No errors - so proceed to email the admin and the user (if selected).
    if(!$error)
	{
		$body .= "\n\nIP:\t".USERIP."\n";
		$body .= "User:\t#".USERID." ".USERNAME."\n";

		if(!$_POST['contact_person'] && isset($pref['sitecontacts'])) // only 1 person, so contact_person not posted.
		{
    		if($pref['sitecontacts'] == e_UC_MAINADMIN)
			{
        		$query = "user_perms = '0' OR user_perms = '0.' ";
			}
			elseif($pref['sitecontacts'] == e_UC_ADMIN)
			{
				$query = "user_admin = 1 ";
			}
			else
			{
        		$query = $pref['sitecontacts'] . " IN (user_class) ";
			}
		}
		else
		{
      		$query = "user_id = ".$_POST['contact_person'];
		}

    	if($sql -> db_Select("user", "user_name,user_email",$query." LIMIT 1"))
		{
    		$row = $sql -> db_Fetch();
    		$send_to = $row['user_email'];
			$send_to_name = $row['user_name'];
		}
    	else
		{
		    $send_to = SITEADMINEMAIL;
			$send_to_name = ADMIN;
		}

    	require_once(e_HANDLER."mail.php");
 		$message =  (sendemail($send_to,"[".SITENAME."] ".$subject, $body,$send_to_name,$sender,$sender_name)) ? LANCONTACT_09 : LANCONTACT_10;
    	if(isset($pref['contact_emailcopy']) && $pref['contact_emailcopy'] && $_POST['email_copy'] == 1){
			sendemail($sender,"[".SITENAME."] ".$subject, $body,ADMIN,$sender,$sender_name);
    	}
    	$ns -> tablerender('', $message);
		require_once(FOOTERF);
		exit;
    }
	else
	{
		require_once(e_HANDLER."message_handler.php");
		message_handler("P_ALERT", $error);
	}

}

if(SITECONTACTINFO && $CONTACT_INFO)
{
	$text = $tp->toHTML($CONTACT_INFO,"","parse_sc");
	$ns -> tablerender(LANCONTACT_01, $text,"contact");
}

if(isset($pref['sitecontacts']) && $pref['sitecontacts'] != 255)
{
	require_once(e_FILE."shortcode/batch/contact_shortcodes.php");
	$text = $tp->parseTemplate($CONTACT_FORM, TRUE, $contact_shortcodes);

	if(trim($text) != "")
	{
		$ns -> tablerender(LANCONTACT_02, $text, "contact");
	}
}
require_once(FOOTERF);
exit;
?>
