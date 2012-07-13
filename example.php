<?php
//ini_set('display_errors', 1);
//error_reporting(E_ALL);

require_once ('microdata_parser.php');

if (isset($_GET['url']))
{
	$doc = new DOMDocument();
	$doc->preserveWhiteSpace = false;
	if ($doc->loadHTMLFile($_GET['url']))
	{
		var_dump(ParseMicrodata($doc->documentElement));
	}
	else
		print ('Failed to load "url"');
}
else
	print ('Pass a GET variable named "url"');

?>