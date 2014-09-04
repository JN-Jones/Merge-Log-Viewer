<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item("Delete", "index.php?module=merge-delete");

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
			$db->drop_table("debuglogs");
			flash_message("Table deleted", 'success');
			admin_redirect("index.php?module=merge-logs");
		} else {
			$page->output_confirm_action("index.php?module=merge-delete", "Are you sure you want to delete the table?");
			exit;
		}
	}
}