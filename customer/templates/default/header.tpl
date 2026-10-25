<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<HEAD>
	<link rel="shortcut icon" href="{$FAVICONPATH}">
	<title>{php}echo basename($_SESSION["last_page"],".php"){/php} | {$CCMAINTITLE}</title>
	<meta charset="UTF-8" name="viewport" content="width=device-width, initial-scale=1.0">
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/>

    <link href="templates/{$SKIN_NAME}/css/main.css" rel="stylesheet" type="text/css">
    <link href="templates/{$SKIN_NAME}/css/menu.css" rel="stylesheet" type="text/css">
{if LANGUAGE == "farsi" }
        <link href="templates/{$SKIN_NAME}/css/right2left.css" rel="stylesheet" type="text/css">
{/if}
<!--[if lt IE 7]>
	<link rel="stylesheet" type="text/css" href="templates/{$SKIN_NAME}/css/style-ie.css" />
<![endif]-->
	<link href="templates/{$SKIN_NAME}/css/style-def.css" rel="stylesheet" type="text/css">
	<link href="templates/{$SKIN_NAME}/css/invoice.css" rel="stylesheet" type="text/css">
	<link href="templates/{$SKIN_NAME}/css/receipt.css" rel="stylesheet" type="text/css">
{if ($popupwindow != 0)}
	<link href="templates/{$SKIN_NAME}/css/popup.css" rel="stylesheet" type="text/css">
{else}
	<script type="text/javascript" src="./javascript/popupResult.js"></script>
	<script src="./javascript/alertBase.js"></script>
{/if}
	<script type="text/javascript">
	    var IMAGE_PATH = "templates/{$SKIN_NAME}/images/";
	</script>
	<script src="./javascript/jquery/jquery-1.7.2.min.js"></script>
	<script src="./javascript/jquery/jquery.debug.js"></script>
	<script src="./javascript/jquery/ilogger.js"></script>
	<script src="./javascript/jquery/handler_jquery.js"></script>
	<script src="./javascript/misc.js"></script>
</HEAD>
<BODY leftmargin="0" topmargin="0" marginwidth="0" marginheight="0">

<div id="page-wrap">
	<div id="inside">
