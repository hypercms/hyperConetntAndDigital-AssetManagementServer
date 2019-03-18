<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");


// input parameters
$site = getrequest ("site", "publicationname");

// ------------------------------ permission section --------------------------------

// check permissions
if (!checkglobalpermission ($site, 'template') || !checkglobalpermission ($site, 'tpl') || !valid_publicationname ($site)) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
<script>
function applyconstraints ()
{
  var constraint = document.forms['valid'].elements['mediatype'].value; 
  
  opener.document.forms['template_edit'].elements['constraints'].value = constraint;
  opener.format_tag('mediafile');
  self.close();
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<div id="WorkplaceFrameLayer" class="hcmsWorkplaceFrame">
  <form name="valid" onsubmit="return applyconstraints();">
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    
    <table border="0" cellspacing="2">
      <tr> 
        <td colspan="3" style="white-space:nowrap; vertical-align:top;" class="hcmsHeadline"><?php echo getescapedtext ($hcms_lang['assigned-media-types'][$lang]); ?></td>
      </tr>
      <tr> 
        <td style="white-space:nowrap; vertical-align:top;"><?php echo getescapedtext ($hcms_lang['select-media-type'][$lang]); ?> </td>
        <td style="white-space:nowrap; vertical-align:top;">
          <select name="mediatype">
            <option value=""><?php echo getescapedtext ($hcms_lang['all-types'][$lang]); ?></option>
            <option value="audio">audio</option>
            <option value="compressed">compressed</option>
            <option value="flash">flash</option>
            <option value="image">image</option>
            <option value="text">text</option>
            <option value="video">video</option>
          </select>
        </td>
      </tr>
      <tr> 
        <td colspan="2" style="white-space:nowrap; vertical-align:top;">&nbsp;</td>
      </tr>    
      <tr>
        <td style="white-space:nowrap; vertical-align:top;">&nbsp;</td>
        <td style="white-space:nowrap; vertical-align:top;"><input name="apply" type="button" id="apply" value="<?php echo getescapedtext ($hcms_lang['assign'][$lang]); ?>" onClick="applyconstraints();" />
        <input name="cancel" type="button" id="cancel" value="<?php echo getescapedtext ($hcms_lang['cancel'][$lang]); ?>" onClick="self.close();" /></td>
      </tr>  
    </table>
  </form>
</div>

</body>
</html>
