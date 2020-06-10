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
require ("../../../config.inc.php");
// hyperCMS API
require ("../../../function/hypercms_api.inc.php");
// language file of plugin
require_once ("../lang/control.inc.php");


// input parameters
$plugin = getrequest_esc ("plugin");
$page = getrequest_esc ("page", "locationname");

// only german and english is supported by plugin
if ($lang != "en" && $lang != "de") $lang = "en";

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script src="../../../javascript/click.js" type="text/javascript"></script>
<script src="../../../javascript/main.js" type="text/javascript"></script>

<?php
// invert button colors
if (!empty ($hcms_themeinvertcolors))
{
  echo "<style>";
  // invert all buttons
  echo invertcolorCSS ("div.hcmsToolbarBlock", 100);
  // revert on hover
  echo invertcolorCSS (".hcmsButton:hover", 100);
  echo "</style>";
}
?>

</head>

  <body class="hcmsWorkplaceControlWallpaper">

    <!-- workplace control title -->
    <div class="hcmsLocationBar">
      <table class="hcmsTableNarrow">
        <tr>
          <td class="hcmsHeadline">Test Plugin</td>
        </tr>
        <tr>
          <td>&nbsp;</td>
        </tr>  
      </table>
    </div>

    <!-- toolbar -->
    <div class="hcmsToolbar">
      <div class="hcmsToolbarBlock">
        <img onClick="parent.frames['mainFrame'].location='page.php?<?php echo 'plugin='.url_encode($plugin).'&page='.url_encode($page); ?>&content=featureA';" class="hcmsButton hcmsButtonSizeSquare" name="button1" src="../img/button_a.png" alt="<?php echo getescapedtext ($hcms_lang['feature-a'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['feature-a'][$lang]); ?>" />
        <img onClick="parent.frames['mainFrame'].location='page.php?<?php echo 'plugin='.url_encode($plugin).'&page='.url_encode($page); ?>&content=featureB';" class="hcmsButton hcmsButtonSizeSquare" name="button2" src="../img/button_b.png" alt="<?php echo getescapedtext ($hcms_lang['feature-b'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['feature-b'][$lang]); ?>" />
        <img onClick="parent.frames['mainFrame'].location='page.php?<?php echo 'plugin='.url_encode($plugin).'&page='.url_encode($page); ?>&content=featureC';" class="hcmsButton hcmsButtonSizeSquare" name="button3" src="../img/button_c.png" alt="<?php echo getescapedtext ($hcms_lang['feature-c'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['feature-c'][$lang]); ?>" />
      </div>
    </div>

  </body>
  
</html>