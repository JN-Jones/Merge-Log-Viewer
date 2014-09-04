<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

define("PER_PAGE", 50);

define("MERGE_ERROR",		1);
define("MERGE_WARNING",		2);
define("MERGE_EVENT",		3);
define("MERGE_TRACE0",		4);
define("MERGE_TRACE1",		5);
define("MERGE_TRACE2",		6);
define("MERGE_TRACE3",		7);
define("MERGE_DATATRACE",	8);

$page->add_breadcrumb_item("Debuglogs", "index.php?module=merge-logs");
$page->output_header("Merge Debuglogs");

if(!$mybb->input['action'])
{
	if(!isset($mybb->input['page']))
		$mybb->input['page'] = 1;
    
	if($mybb->request_method != "post")
	{
		$mybb->input['sort'] = "timestamp";
		$mybb->input['order'] = "desc";
		$mybb->input['filter_type'] = range(1, 8);
		$mybb->input['filer_message'] = "";
	}
	$desc_bool = $asc_bool = false;
	if($mybb->input['order'] == "desc")
	    $desc_bool = true;
	else
		$asc_bool = true;


	$form = new Form("index.php?module=merge-logs", "post");
	$form_container = new FormContainer("Options");
	
	$sortable = array(
		"dlid" => "ID",
		"timestamp" => "Timestamp",
		"type" => "Type"
	);
	$sortfield = $form->generate_select_box("sort", $sortable, $mybb->input['sort']);
	$asc = $form->generate_radio_button("order", "asc", "Ascending", array("checked" => $asc_bool));
	$desc = $form->generate_radio_button("order", "desc", "Descending", array("checked" => $desc_bool));
	$form_container->output_row("Sort by", "", $sortfield."<br /><br />".$asc." ".$desc);

	$filter_type_array = array();
	for($i = 1; $i<=8; $i++)
	    $filter_type_array[$i] = get_friendly_type($i);
	$filter_type = $form->generate_select_box("filter_type[]", $filter_type_array, $mybb->input['filter_type'], array("multiple" => true));
	$form_container->output_row("Filter by Type", $filter_type);

	$filter_message = $form->generate_text_box("filter_message", $mybb->input['filter_message']);
	$form_container->output_row("Filter by Message", $filter_message);

	$form_container->end();
	
	$buttons[] = $form->generate_submit_button("Submit");
	$form->output_submit_wrapper($buttons);
	$form->end();
	
	echo "<br />";

	$table = new Table;
	$table->construct_header("Date");
	$table->construct_header("Type");
	$table->construct_header("Message");
	
	if(!$db->table_exists("debuglogs"))
	{
		$table->construct_cell("The debuglogs table doesn't exist", array("colspan" => 2));
		$table->construct_row();
	}
	else
	{
		// Build our query
		$query = "SELECT * FROM ".TABLE_PREFIX."debuglogs WHERE ";
		if(!empty($mybb->input['filter_type']))
		{
			$where[] = "type IN ('".implode("','", $mybb->input['filter_type'])."')";
		}
		if(!empty($mybb->input['filter_message']))
		{
			$where[] = "message LIKE '%".$db->escape_string($mybb->input['filter_message'])."%'";
		}
		$query .= implode(" AND ", $where);
		
		$dir = "DESC";
		if($asc_bool)
		    $dir = "ASC";
		switch($mybb->input['sort'])
		{
			case "dlid":
				$query .= " ORDER BY dlid {$dir}";
				break;
			case "type":
				$query .= " ORDER BY type {$dir}";
				break;
			case "timestamp":
			default:
				$query .= " ORDER BY timestamp {$dir}, dlid {$dir}"; // dlid is used here too as the timestamp is often the same
				break;
		}

		$squery = $db->query($query);
		if($db->num_rows($squery) < 1)
		{
			$table->construct_cell("There's nothing to display", array("colspan" => 2));
			$table->construct_row();
		}
		else
		{
			$multipage = draw_admin_pagination($mybb->input['page'], PER_PAGE, $db->num_rows($squery), "index.php?module=merge-logs");
			$start = ($mybb->input['page']-1)*PER_PAGE;
			$query .= " LIMIT {$start}, ".PER_PAGE;
			$squery = $db->query($query);
			echo $multipage;
			while($entry = $db->fetch_array($squery))
			{
				$date = my_date("relative", $entry['timestamp']);
				//$table->construct_cell("<a href=\"index.php?module=merge-logs&action=view&id={$entry['dlid']}\">{$date}</a>");
				$table->construct_cell($date);
				$table->construct_cell(get_friendly_type($entry['type']));
				$table->construct_cell(nl2br(str_replace(" ", "&nbsp;", $entry['message'])));
				$table->construct_row();
			}
		}
	}
	$table->output("Debuglogs");
	if(isset($multipage))
	    echo $multipage;
}

$page->output_footer();

function get_friendly_type($type)
{
	switch($type)
	{
		case MERGE_ERROR:
			return "Error";
		case MERGE_WARNING:
			return "Warning";
		case MERGE_EVENT:
			return "Event";
		case MERGE_TRACE0:
			return "Trace 0";
		case MERGE_TRACE1:
			return "Trace 1";
		case MERGE_TRACE2:
			return "Trace 2";
		case MERGE_TRACE3:
			return "Trace 3";
		case MERGE_DATATRACE:
			return "Datatrace";
		default:
			return "Unknown";
	}
}