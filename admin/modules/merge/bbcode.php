<?php
// Disallow direct access to this file for security reasons
if(!defined("IN_MYBB"))
{
	die("Direct initialization of this file is not allowed.<br /><br />Please make sure IN_MYBB is defined.");
}

$page->add_breadcrumb_item("BBCode Parser", "index.php?module=merge-bbcode");
$page->output_header("Merge Debuglogs");

if(!is_dir(MYBB_ROOT."merge/boards"))
    die("Merge System not available");

if($mybb->request_method == "post")
{
	// Build our code parser thing
	// First: set up the board class
	require_once MYBB_ROOT."merge/resources/class_converter.php";
	require_once MYBB_ROOT."merge/boards/{$mybb->input['board']}.php";
	$class = strtoupper($mybb->input['board'])."_Converter";
	$board = new $class;

	// Next: get the parser up
	// Plain class is needed as parent class anyways
	require_once MYBB_ROOT."merge/resources/bbcode_plain.php";
	// If we're using the plain class or we don't have a custom one -> set it up
	if($board->parser_class == "plain" || ($board->parser_class != "html" && !file_exists(MYBB_ROOT."merge/boards/".$mybb->input['board']."/bbcode_parser.php")))
	{
		$bbcode_parser = new BBCode_Parser_Plain();
		$using = "Plain";
	}
	// Using the HTML class? No need for extra checks
	else if($board->parser_class == "html")
	{
		require_once MYBB_ROOT."merge/resources/bbcode_html.php";
		$bbcode_parser = new BBCode_Parser_HTML();
		$using = "HTML";
	}
	// The only other case is a custom parser. A check whether the class exists is in the first if
	else
	{
		// It's possible that the custom handler is based on the html handler so we need to include it too
		require_once MYBB_ROOT."merge/resources/bbcode_html.php";
		require_once MYBB_ROOT."merge/boards/".$mybb->input['board']."/bbcode_parser.php";
		$bbcode_parser = new BBCode_Parser();
		$using = "Custom";
	}

	// Setting up our fake module class
	$module = new module();

	// And finally: Do our parser magic
	if($mybb->input['board'] == "phpbb3")
		$text = $bbcode_parser->convert($mybb->input['text'], $mybb->input['uid']);
	else
		$text = $bbcode_parser->convert($mybb->input['text']);

	// Now show what we did :D
	$table = new Table;
	$table->construct_header("Output");

	// First some notes
	$table->construct_cell("A few notes about this fake parser:<br />
		We we're using the {$using} parser<br />
		ID's should be incremented by 1 (eg in FluxBB's [thread] tag)<br />
		Attachment codes should look like '[attachment=o{id}]' where {id} is the old id (note: does not work with phpBB)<br />
		The parsed version could look a bit ugly, simply as we're in the acp here");
	$table->construct_row();

	$table->construct_cell(nl2br(htmlentities($text)));
	$table->construct_row();

	// Now do the mybb magic
	require_once MYBB_ROOT."inc/class_parser.php";
	$parser = new postParser;
	$options = array(
		"allow_html"		=> 0,
		"allow_smilies"		=> 0, // Disabled as not merged
		"allow_mycode"		=> 1,
		"allow_imgcode"		=> 1,
		"allow_videocode"	=> 1,
		"nl2br"				=> 1,
		"filter_badwords"	=> 0
	);
	$parsed = $parser->parse_message($text, $options);
	$table->construct_cell($parsed);
	$table->construct_row();

	$table->output("Output");
	echo "<br />";
}

// Show the form
// First: get all of our boards
$dh = opendir(MYBB_ROOT."merge/boards");
while(($file = readdir($dh)) !== false)
{
	if($file != "." && $file != ".." && get_extension($file) == "php")
	{
		$bb_name = str_replace(".php", "", $file);
		$board_script = file_get_contents(MYBB_ROOT."merge/boards/{$file}");
		// Match out board name
		preg_match("#var \\\$bbname \= \"(.*?)\"\;#i", $board_script, $version_info);
		if($version_info[1])
		{
			$board_array[$bb_name] = $version_info[1];
		}
	}
}

$form = new Form("index.php?module=merge-bbcode", "post");
$form_container = new FormContainer("BBCode Parser");

$boards = $form->generate_select_box("board", $board_array, $mybb->input['board'], array("id"=>"board"));
$form_container->output_row("Board", "The parser of which board should be used?", $boards);

$text = $form->generate_text_area("text", $mybb->input['text']);
$form_container->output_row("Text", "Which text should be parsed?\nYou should paste the value as saved in the database (which is not alawys the same as showed in the editor)", $text);

$uid = $form->generate_text_box("uid", $mybb->input['uid']);
$form_container->output_row("UID", "(Only phpBB) If your bbcode contains an id (eg [b:2020]bold[/b]) the id should be entered here (eg 2020)", $uid, '', array(), array("id"=>"uid"));

$form_container->end();

$buttons[] = $form->generate_submit_button("Submit");
$buttons[] = $form->generate_reset_button("Reset");
$form->output_submit_wrapper($buttons);
$form->end();

echo '<script type="text/javascript" src="./jscripts/peeker.js"></script>
<script type="text/javascript">
	$(document).ready(function() {
		new Peeker($("#board"), $("#uid"), /phpbb3/, false);
	});
</script>';

$page->output_footer();

// Some fake classes and functions to simulate we're really doing a merge
class module
{
	function __construct()
	{
		$this->get_import = new dumb_cache;
	}
}

class dumb_cache
{
	function tid($oid)
	{
		return $oid+1;
	}
	function pid($pid)
	{
		return $pid+1;
	}
	function uid($uid)
	{
		if($uid==0)
		    return $uid;
		return $uid+1;
	}
	function fid($fid)
	{
		return $fid+1;
	}
}

function utf8_unhtmlentities($string)
{
	// Replace numeric entities
	$string = @preg_replace('~&#x([0-9a-f]+);~ei', 'unichr(hexdec("\\1"))', $string);
	$string = @preg_replace('~&#([0-9]+);~e', 'unichr("\\1")', $string);

	// Replace literal entities
	$trans_tbl = get_html_translation_table(HTML_ENTITIES);
	$trans_tbl = array_flip($trans_tbl);

	return strtr($string, $trans_tbl);
}