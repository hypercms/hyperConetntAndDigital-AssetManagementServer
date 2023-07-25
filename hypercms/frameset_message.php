<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the license (license.txt) along with hyper Content & Digital Management Server
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// write and close session (non-blocking other frames)
suspendsession ();
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=<?php echo windowwidth ("object"); ?>, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />>
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript">

function openPopup (link)
{
  if (link != "")
  {
    document.getElementById('objectview').src = link;
    hcms_showFormLayer('objectviewLayer',0);
  }
}

function closePopup ()
{
  document.getElementById('objectview').src = '';
  hcms_hideFormLayer('objectviewLayer');
}
</script>
</head>

<body class="hcmsTransBackground">

  <!-- load screen --> 
  <div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:inline;"></div>

  <!-- popup for preview/live-view (do not used nested fixed positioned div-layers due to MS IE and Edge issue) --> 
  <div id="objectviewLayer" style="display:none;">
    <div style="position:fixed; right:4px; top:4px; z-index:9001;">
      <img name="hcms_mediaClose" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="closePopup();" />
    </div>
    <div class="hcmsWorkplaceExplorer" style="overflow:hidden; position:fixed; margin:0; padding:0; left:0; top:0; right:0; bottom:0; z-index:9000;">
      <iframe id="objectview" name="objectview" frameBorder="0" src="" <?php if (!$is_mobile) echo 'scrolling="no"'; else echo 'scrolling="yes"'; ?> style="<?php if (!$is_mobile) echo "overflow:hidden;"; else echo "overflow:scroll;"; ?> <?php if (!$is_iphone) echo "width:100%; height:100%; border:0; margin:0; padding:0;"; ?>" sandbox="allow-top-navigation allow-same-origin allow-scripts allow-forms" allowFullScreen="true" webkitallowfullscreen="true" mozallowfullscreen="true"></iframe>
    </div>
  </div>

  <!-- frames -->
  <iframe id="controlFrame" name="controlFrame" src="control_message_menu.php" frameborder="0" scrolling="no" style="position:fixed; top:0; left:0; width:100%; height:78px; border:0; margin:0; padding:0; overflow:hidden;"></iframe>
  <div id="mainLayer" style="position:fixed; top:78px; right:0; bottom:0; left:0; margin:0; padding:0;">
    <iframe id="mainFrame" name="mainFrame" src="message_objectlist.php" frameborder="0" scrolling="no" style="width:100%; height:100%; border:0; margin:0; padding:0; overflow:hidden;"></iframe>
  </div>
  
</body>
</html>