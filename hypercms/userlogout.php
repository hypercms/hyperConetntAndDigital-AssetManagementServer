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
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// version info
require ("version.inc.php");
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<meta name="viewport" content="width=380; initial-scale=0.9; maximum-scale=1.0; user-scalable=0;">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsStartScreen" onload="location.href='userlogin.php';">

<?php
// delete session file of user
$test = killsession ($user);

if (empty ($lang)) $lang = "en";

if ($test == true) 
{
  @session_destroy();
  $answer = $hcms_lang['logged-out'][$lang];
}
else
{ 
  @session_destroy();
  $answer = $hcms_lang['session-cannot-be-closed'][$lang];
}
?>

<div class="hcmsStartBar">
  <div style="position:absolute; top:10px; left:10px; float:left; text-align:left;"><img src="<?php echo getthemelocation(); ?>img/logo.png" alt="hyperCMS" /></div>
  <div style="position:absolute; top:48px; right:10px; text-align:right;"><?php echo $version; ?></div>
</div>

<p class="hcmsTextGreen">
  <?php echo "&gt;&gt; ".$user." ".$answer."&nbsp;"; ?>
  <img name="Button" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="location.href='userlogin.php';" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
</p>

</body>
</html>
