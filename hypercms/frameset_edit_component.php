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


// input parameters
$view = url_encode (getrequest ("view", "url"));
$site = url_encode (getrequest ("site", "url"));
$cat = url_encode (getrequest ("cat", "url"));
$compcat = url_encode (getrequest ("compcat", "url"));
$location = url_encode (getrequest ("location", "url"));
$page = url_encode (getrequest ("page", "url"));
$id = url_encode (getrequest ("id", "url"));
$tagname = url_encode (getrequest ("tagname", "url"));
$mediatype = url_encode (getrequest ("mediatype", "url")); 

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// write and close session (non-blocking other frames)
suspendsession ();

// publication management config
if (valid_publicationname ($site) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
{
  require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
}

// define media type based on DAM setting
if (empty ($mgmt_config[$site]['dam'])) $mediatype = "component";
?>
<!DOCTYPE HTML>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=<?php echo windowwidth ("object"); ?>, initial-scale=1.0, maximum-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript">

function minNavFrame ()
{
  if (document.getElementById('navFrame2'))
  {
    var width = 36;
    
    document.getElementById('navLayer').style.transition = "0.3s";
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('mainLayer').style.transition = "0.3s";
    document.getElementById('mainLayer').style.left = width + 'px';
    window.frames['navFrame2'].document.getElementById('Navigator').style.display = 'none';
    window.frames['navFrame2'].document.getElementById('NavFrameButtons').style.left = '0px';
    window.frames['navFrame2'].document.getElementById('NavFrameButtons').style.right = '';
  }
}

function maxNavFrame ()
{
  if (document.getElementById('navFrame2'))
  {
    var width = 260;
    
    document.getElementById('navLayer').style.transition = "0.3s";
    document.getElementById('navLayer').style.width = width + 'px';
    document.getElementById('mainLayer').style.transition = "0.3s";
    document.getElementById('mainLayer').style.left = width + 'px';
    window.frames['navFrame2'].document.getElementById('Navigator').style.display = 'block';
    window.frames['navFrame2'].document.getElementById('NavFrameButtons').style.left = '';
    window.frames['navFrame2'].document.getElementById('NavFrameButtons').style.right = '0px';
  }
}
</script>
</head>

<body>
  <?php
  echo "
  <div id=\"navLayer\" style=\"position:fixed; top:0; bottom:0; left:0; width:260px; margin:0; padding:0;\">
    <iframe id=\"navFrame2\" name=\"navFrame2\" src=\"component_edit_explorer.php?site=".$site."&cat=".$cat."&compcat=".$compcat."&location=".$location."&page=".$page."&mediatype=".$mediatype."\" frameborder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;\"></iframe>
  </div>";

  if ($compcat == "single")
  {
    echo "
  <div id=\"mainLayer\" style=\"position:fixed; top:0; right:0; bottom:0; left:260px; margin:0; padding:0;\">
    <iframe id=\"mainFrame2\" name=\"mainFrame2\" src=\"component_edit_page_single.php?view=".$view."&site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&id=".$id."&tagname=".$tagname."&compcat=".$compcat."\" frameborder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;\"></iframe>
  </div>";
  }
  elseif ($compcat == "multi")
  {
    echo "
  <div id=\"mainLayer\" style=\"position:fixed; top:0; right:0; bottom:0; left:260px; margin:0; padding:0;\">
    <iframe id=\"mainFrame2\" name=\"mainFrame2\" src=\"component_edit_page_multi.php?view=".$view."&site=".$site."&cat=".$cat."&location=".$location."&page=".$page."&id=".$id."&tagname=".$tagname."&compcat=".$compcat."\" frameborder=\"0\" style=\"width:100%; height:100%; border:0; margin:0; padding:0; overflow:auto;\"></iframe>
  </div>";
  }
  ?>
</body>
</html>