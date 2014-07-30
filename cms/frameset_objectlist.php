<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// input parameters
$location = url_encode (getrequest ("location", "url"));
$virtual = url_encode (getrequest ("virtual", "numeric"));
$action = url_encode (getrequest ("action", "url"));
$search_dir = url_encode (getrequest ("search_dir", "url"));
$search_expression = url_encode (getrequest ("search_expression", "url"));
$maxhits = url_encode (getrequest ("maxhits", "url"));

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
<meta name="viewport" content="width=1024; initial-scale=1.0; user-scalable=1;">
<script language="JavaScript">
<!--
function adjust_height ()
{
  height = hcms_getDocHeight();  
  
  setheight = height - 100;
  document.getElementById('mainFrame').style.height = setheight + "px";
  document.getElementById('sidebarFrame').style.height = setheight + "px";
}

window.onresize = adjust_height;
-->
</script>
</head>
<body style="width:100%; height:100%; margin:0; padding:0;" onload="adjust_height();">
<?php
// scrolling for control frame
if ($is_mobile) $scrolling = "YES";
else $scrolling = "NO";

// object list width in %
if ($temp_sidebar && !$is_mobile) $objectlist_width = 75;
else $objectlist_width = 100;

// standard object explorer for given location
if (($location != "" || is_array ($hcms_linking)))
{
  if (!isset ($virtual)) $virtual = 0;

  // control
  echo "<iframe id=\"controlFrame\" name=\"controlFrame\" scrolling=\"".$scrolling."\" src=\"control_objectlist_menu.php?location=".$location."&virtual=".$virtual."\" border=\"0\" style=\"width:100%; height:100px; border:0; margin:0; padding:0; float:left;\"></iframe>\n";
  // object list
  echo "<iframe id=\"mainFrame\" name=\"mainFrame\" scrolling=\"NO\" src=\"explorer_objectlist.php?location=".$location."&virtual=".$virtual."\" border=\"0\" style=\"width:".$objectlist_width."%; height:100%; border:0; margin:0; padding:0; float:left;\"></iframe>\n";
  // sidebar
  if (!$is_mobile) echo "<iframe id=\"sidebarFrame\" scrolling=\"auto\" name=\"sidebarFrame\" src=\"explorer_preview.php\" border=\"0\" style=\"width:".(100 - $objectlist_width)."%; height:100%; border:0; margin:0; padding:0; float:left;\"></iframe>\n";
}
// search from top frame
elseif ($action == "base_search")
{
  // control
  echo "<iframe id=\"controlFrame\" name=\"controlFrame\" scrolling=\"".$scrolling."\" src=\"loading.php\" border=\"0\" style=\"width:100%; height:100px; border:0; margin:0; padding:0; float:left;\"></iframe>\n";
  // object list
  echo "<iframe id=\"mainFrame\" name=\"mainFrame\" scrolling=\"NO\" src=\"search_script_rdbms.php?action=".$action."&search_dir=".$search_dir."&search_expression=".$search_expression."&maxhits=".$maxhits."\" border=\"0\" style=\"width:".$objectlist_width."%; height:100%; border:0; margin:0; padding:0; float:left;\"></iframe>\n";
  // sidebar
  if (!$is_mobile) echo "<iframe id=\"sidebarFrame\" scrolling=\"auto\" name=\"sidebarFrame\" src=\"explorer_preview.php\" border=\"0\" style=\"width:".(100 - $objectlist_width)."%; height:100%; border:0; margin:0; padding:0; float:left;\"></iframe>\n";
}
// checked out objects
elseif ($action == "checkedout")
{
  // control
  echo "<iframe id=\"controlFrame\" name=\"controlFrame\" scrolling=\"".$scrolling."\" src=\"control_objectlist_menu.php?virtual=1&from_page=checkedout\" border=\"0\" style=\"width:100%; height:100px; border:0; margin:0; padding:0; float:left;\"></iframe>\n";
  // object list
  echo "<iframe id=\"mainFrame\" name=\"mainFrame\" scrolling=\"NO\" src=\"page_checkedout.php\" border=\"0\" style=\"width:".$objectlist_width."%; height:100%; border:0; margin:0; padding:0; float:left;\"></iframe>\n";
  // sidebar
  if (!$is_mobile) echo "<iframe id=\"sidebarFrame\" scrolling=\"auto\" name=\"sidebarFrame\" src=\"explorer_preview.php\" border=\"0\" style=\"width:".(100 - $objectlist_width)."%; height:100%; border:0; margin:0; padding:0; float:left;\"></iframe>\n";
}
?>
</body>
</html>