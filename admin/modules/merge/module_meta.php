<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

function merge_meta()
{
	global $page, $lang, $plugins, $mybb;

	$sub_menu = array();
	$sub_menu['10'] = array("id" => "logs", "title" => "View debug logs", "link" => "index.php?module=merge-logs");
	$sub_menu['20'] = array("id" => "truncate", "title" => "Truncate table", "link" => "index.php?module=merge-truncate");
	$sub_menu['30'] = array("id" => "delete", "title" => "Delete table", "link" => "index.php?module=merge-delete");
	$sub_menu['40'] = array("id" => "run", "title" => "Run merge system", "link" => $mybb->settings['bburl']."/merge/index.php");

	$sub_menu = $plugins->run_hooks("admin_merge_menu", $sub_menu);

	$page->add_menu_item("Merge System", "merge", "index.php?module=merge", 100, $sub_menu);
	return true;
}

function merge_action_handler($action)
{
	global $page, $lang, $plugins;

	$page->active_module = "merge";

	$actions = array(
		'logs' => array('active' => 'logs', 'file' => 'debuglogs.php'),
		'truncate' => array('active' => 'truncate', 'file' => 'truncate.php'),
		'delete' => array('active' => 'delete', 'file' => 'delete.php')
	);

	$actions = $plugins->run_hooks("admin_merge_action_handler", $actions);

	if(isset($actions[$action]))
	{
		$page->active_action = $actions[$action]['active'];
		return $actions[$action]['file'];
	}
	else
	{
		$page->active_action = "logs";
		return "debuglogs.php";
	}
}