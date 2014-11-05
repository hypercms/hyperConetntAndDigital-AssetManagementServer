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
$site = url_encode (getrequest ("site", "url"));
$location = url_encode (getrequest ("location", "url"));
$page = url_encode (getrequest ("page", "url"));
$cat = url_encode (getrequest ("cat", "url"));
$template = url_encode (getrequest ("template", "url"));

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo $lang_codepage[$lang]; ?>" />
<meta name="viewport" content="width=800; initial-scale=1.0; user-scalable=1;" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" language="JavaScript" type="text/javascript"></script>
<script language="JavaScript">
<!--
function adjust_height ()
{
  var height = hcms_getDocHeight();  
  
  var setheight = height - 80;
  if (document.getElementById('mainFrame2')) document.getElementById('mainFrame2').style.height = setheight + "px";
}
-->
</script>
</head>

<body style="width:100%; height:100%; margin:0; padding:0;" onload="adjust_height();" onresize="adjust_height();">
  <iframe id="controlFrame2" name="controlFrame2" scrolling="no" src="<?php echo "template_change.php?location=".$location."&page=".$page; ?>" style="position:fixed; top:0; left:0; width:100%; height:80px; border:0; margin:0; padding:0;"></iframe>
  <iframe id="mainFrame2" name="mainFrame2" scrolling="auto" src="<?php echo "template_view.php?site=".$site."&cat=".$cat."&template=".$template; ?>" style="position:fixed; top:80px; left:0; width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
</body>
</html>