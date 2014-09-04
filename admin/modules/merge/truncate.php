<?php
/**
 * MyBB 1.8
 * Copyright 2014 MyBB Group, All Rights Reserved
 *
 * Website: http://www.mybb.com
 * License: http://www.mybb.com/about/license
 *
 */

// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item("Truncate", "index.php?module=merge-truncate");

if(!$db->table_exists("debuglogs"))
{
	$page->output_header("Delete");
	echo "Table debuglogs doesn't exist";
	$page->output_footer();
}
else
{
	if($mybb->input['no'])
	{
		admin_redirect("index.php?module=merge-logs");
	}
	else
	{
		if($mybb->request_method == "post") {
			$db->write_query("TRUNCATE TABLE ".TABLE_PREFIX."debuglogs");
			flash_message("Table truncated", 'success');
			admin_redirect("index.php?module=merge-logs");
		} else {
			$page->output_confirm_action("index.php?module=merge-truncate", "Are you sure you want to truncate the table?");
			exit;
		}
	}
}