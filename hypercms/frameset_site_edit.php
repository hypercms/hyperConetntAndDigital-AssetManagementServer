<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// input parameters
$site = url_encode (getrequest ("site", "url")); // site can be *Null* which is not a valid name!
$site_name = url_encode (getrequest ("site_name", "url"));
$preview = url_encode (getrequest ("preview", "url"));

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=<?php echo windowwidth ("object"); ?>, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
</head>

<?php
// iPad and iPhone requires special CSS settings
if ($is_iphone) $css_iphone = " overflow:scroll !important; -webkit-overflow-scrolling:touch !important;";
else $css_iphone = "";
?>
<body>
  <iframe id="controlFrame2" name="controlFrame2" scrolling="no" src="<?php echo "site_edit_menu.php?site=".$site."&preview=".$preview."&site_name=".$site_name; ?>" frameBorder="0" style="position:fixed; top:0; left:0; width:100%; height:24px; border:0; margin:0; padding:0;"></iframe>
  <div style="position:fixed; top:24px; right:0; bottom:0; left:0; margin:0; padding:0; <?php echo $css_iphone; ?>">
    <iframe id="mainFrame2" name="mainFrame2" scrolling="auto" src="<?php echo "site_edit_form.php?site=".$site."&preview=".$preview."&site_name=".$site_name; ?>" frameBorder="0" style="width:100%; height:100%; border:0; margin:0; padding:0;"></iframe>
  </div>
</body>
</html>