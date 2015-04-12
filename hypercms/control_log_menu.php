<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
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
// hyperCMS UI
require ("function/hypercms_ui.inc.php");


// input parameters
$action = getrequest ("action");
$token = getrequest ("token"); 

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkrootpermission ('site')) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";

// clear event log
if ((checkrootpermission ('site') || checkrootpermission ('user')) && $action == "clear" && checktoken ($token, $user))
{
  $result = deletelog ();
  
  $add_onload =  $result['add_onload'];
  $show = $result['message'];  
}

// security token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>
<script language="JavaScript" type="text/javascript">
<!--
function warning_delete()
{
  check = confirm(hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-remove-all-events'][$lang]); ?>"));

  if (check == true)
  {  
    document.location.href='<?php echo "control_log_menu.php?action=clear&token=".$token_new; ?>';
  }
}
// -->
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onLoad="<?php echo $add_onload; ?>">

<?php
echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
?>

<div class="hcmsLocationBar">
  <table border=0 cellspacing=0 cellpadding=0>
    <tr>
      <td class="hcmsHeadline"><?php echo $hcms_lang['system-events'][$lang]; ?></td>
    </tr>
    <tr>
      <td>&nbsp;</td>
    </tr>  
  </table>
</div>

<!-- toolbar -->
<div class="hcmsToolbar">
  <div class="hcmsToolbarBlock">
    <img onClick="location.href='log_export.php';" class="hcmsButton hcmsButtonSizeSquare" name="media_export" src="<?php echo getthemelocation(); ?>img/button_file_upload.gif" alt="<?php echo $hcms_lang['export-list-comma-delimited'][$lang]; ?>" title="<?php echo $hcms_lang['export-list-comma-delimited'][$lang]; ?>" />

    <img onClick="warning_delete();" class="hcmsButton hcmsButtonSizeSquare" name="media_delete" src="<?php echo getthemelocation(); ?>img/button_file_delete.gif" alt="<?php echo $hcms_lang['clear-all-events'][$lang]; ?>" title="<?php echo $hcms_lang['clear-all-events'][$lang]; ?>" />

    <img onClick="parent['mainFrame'].location.href='log_list.php';" class="hcmsButton hcmsButtonSizeSquare" name="media_view" src="<?php echo getthemelocation(); ?>img/button_view_refresh.gif" alt="<?php echo $hcms_lang['refresh'][$lang]; ?>" title="<?php echo $hcms_lang['refresh'][$lang]; ?>" />
  </div>
  <div class="hcmsToolbarBlock">
    <?php
    if (!$is_mobile && file_exists ("help/adminguide_".$hcms_lang_shortcut[$lang].".pdf"))
    {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/adminguide_".$hcms_lang_shortcut[$lang].".pdf','help','scrollbars=no,resizable=yes','800','600');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$hcms_lang['help'][$lang]."\" title=\"".$hcms_lang['help'][$lang]."\" /></a>\n";}
    elseif (!$is_mobile && file_exists ("help/adminguide_en.pdf"))
    {echo "<a href=# onMouseOut=\"hcms_swapImgRestore()\" onMouseOver=\"hcms_swapImage('pic_obj_help','','".getthemelocation()."img/button_help_over.gif',1)\" onClick=\"hcms_openWindow('help/adminguide_en.pdf','help','scrollbars=no,resizable=yes','800','600');\"><img name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.gif\" class=\"hcmsButtonBlank hcmsButtonSizeSquare\" alt=\"".$hcms_lang['help'][$lang]."\" title=\"".$hcms_lang['help'][$lang]."\" /></a>\n";}
    ?> 
  </div>
</div>

</body>
</html>
