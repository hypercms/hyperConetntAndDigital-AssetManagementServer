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
$action = getrequest ("action");
$multiobject = getrequest ("multiobject");
$location = getrequest ("location", "locationname");
$folder = getrequest ("folder", "objectname");
$page = getrequest ("page", "objectname");
$wf_token = getrequest ("wf_token");
$from_page = getrequest ("from_page");
$token = getrequest ("token");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);

// check session of user
checkusersession ($user, false);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#000000" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- load screen --> 
<div id="hcmsLoadScreen" class="hcmsLoadScreen" style="display:inline;"></div>

<?php
// --------------------------------- logic section ----------------------------------

// flush
ob_implicit_flush (true);
ob_end_flush ();
sleep (1);

// initalize
$show = "";
$add_onload = "";
$multiobject_array = array();
$result = array();

// correct location for access permission
if ($folder != "")
{
  $location_ACCESS = $location.$folder."/";
}
else
{
  $location_ACCESS = $location;
}

// check authorization
$authorized = false;

if ($setlocalpermission['root'] == 1 && checktoken ($token, $user))
{
  if (($action == "delete" || $action == "deletemark" || $action == "restore") && (($page != "" && $setlocalpermission['delete'] == 1) || ($folder != "" && $setlocalpermission['folderdelete'] == 1))) $authorized = true;
  elseif (($action == "cut" || $action == "copy" || $action == "linkcopy") && (($page != "" && $setlocalpermission['rename'] == 1) || ($folder != "" && $setlocalpermission['folderrename'] == 1))) $authorized = true;
  elseif (($action == "page_favorites_create" || $action == "page_favorites_delete") && $setlocalpermission['create'] == 1) $authorized = true;
  elseif ($action == "page_unlock" && ($page != "" && $setlocalpermission['create'] == 1) || ($folder != "" && $setlocalpermission['foldercreate'] == 1)) $authorized = true;
  elseif ($action == "paste" && ($setlocalpermission['rename'] == 1 || $setlocalpermission['folderrename'] == 1)) $authorized = true;
  elseif (($action == "publish" || $action == "unpublish") && $setlocalpermission['publish'] == 1) $authorized = true;
  elseif ($action == "unzip") $authorized = true;
}

if ($authorized == true)
{
  // empty clipboard
  setsession ('hcms_temp_clipboard', "");

  // perform actions
  // priority for processing due to all variables (multiobject, folder, page) 
  // will be posted from the context menu:
  // 1. multiobject
  // 2. folder
  // 3. object

  // unzip
  if ($action == "unzip")
  {
    // action for unzip is below
  }
  // delete
  elseif ($action == "delete" || $action == "deletemark" || $action == "deleteunmark" || $action == "restore") 
  {
    // reset action
    if ($from_page != "recyclebin" && $action == "delete" && !empty ($mgmt_config['recyclebin'])) $action = "deletemark";

    if (is_string ($multiobject) && strlen ($multiobject) > 6) $multiobject_array = link_db_getobject ($multiobject);

    if (is_array ($multiobject_array) && sizeof ($multiobject_array) > 1)
    {
      $result['result'] = true;

      // delete objects
      foreach ($multiobject_array as $objectpath)
      {
        if ($objectpath != "" && $result['result'] == true)
        {
          $site = getpublication ($objectpath);
          $location = getlocation ($objectpath);
          $page = getobject ($objectpath);

          if ($page != "")
          {
            // delete object
            if ($action == "delete") $result = deleteobject ($site, $location, $page, $user);
            // mark object as deleted
            elseif ($action == "deletemark") $result = deletemarkobject ($site, $location, $page, $user);
            // unmark object as deleted
            elseif ($action == "restore" || $action == "deleteunmark") $result = deleteunmarkobject ($site, $location, $page, $user);

            $add_onload = $result['add_onload'];
            $show = $result['message'];
          }
        }
      }
    }
    elseif ($page != "")
    {
      // delete object
      if ($action == "delete") $result = deleteobject ($site, $location, $page, $user);
      // mark object as deleted
      elseif ($action == "deletemark") $result = deletemarkobject ($site, $location, $page, $user);
      // unmark object as deleted
      elseif ($action == "restore" || $action == "deleteunmark") $result = deleteunmarkobject ($site, $location, $page, $user);

      $add_onload = $result['add_onload'];
      $show = $result['message'];       
    }    
  }
  // cut, copy, linkcopy
  elseif ($action == "cut" || $action == "copy" || $action == "linkcopy") 
  {
    if (is_string ($multiobject) && strlen ($multiobject) > 6) $multiobject_array = link_db_getobject ($multiobject);

    if (is_array ($multiobject_array) && sizeof ($multiobject_array) > 1)
    {
      $result['result'] = true;

      foreach ($multiobject_array as $objectpath)
      {
        if ($objectpath != "" && $result['result'] == true)
        {
          $site = getpublication ($objectpath);
          $location = getlocation ($objectpath);
          $page = getobject ($objectpath);

          if ($site != "" && $location != "" && $page != "")
          {
            if ($action == "cut") $result = cutobject ($site, $location, $page, $user, true);
            elseif ($action == "copy") $result = copyobject ($site, $location, $page, $user, true);
            elseif ($action == "linkcopy") $result = copyconnectedobject ($site, $location, $page, $user, true);

            if (!empty ($result['add_onload'])) $add_onload = $result['add_onload'];
            if (!empty ($result['message'])) $show = $result['message'];   
          }
        }
      }
    }
    elseif ($folder != "")
    {
      if ($action == "cut") $result = cutobject ($site, $location, $folder, $user);
      elseif ($action == "copy") $result = copyobject ($site, $location, $folder, $user);
      elseif ($action == "linkcopy") $result = copyconnectedobject ($site, $location, $folder, $user);

      if (!empty ($result['add_onload'])) $add_onload = $result['add_onload'];
      if (!empty ($result['message'])) $show = $result['message'];    
    }     
    elseif ($page != "")
    {
      if ($action == "cut") $result = cutobject ($site, $location, $page, $user);
      elseif ($action == "copy") $result = copyobject ($site, $location, $page, $user);
      elseif ($action == "linkcopy") $result = copyconnectedobject ($site, $location, $page, $user);

      if (!empty ($result['add_onload'])) $add_onload = $result['add_onload'];
      if (!empty ($result['message'])) $show = $result['message'];   
    }
  }
  // delete objects from favorites
  elseif (($action == "page_favorites_create" || $action == "page_favorites_delete") && $setlocalpermission['root'] == 1)
  {
    if (is_string ($multiobject) && strlen ($multiobject) > 6) $multiobject_array = link_db_getobject ($multiobject);

    if (is_array ($multiobject_array) && sizeof ($multiobject_array) > 1)
    {
      $result['result'] = true;

      foreach ($multiobject_array as $multiobject_item)
      {
        if ($multiobject_item != "" && $result['result'] == true)
        {
          $site = getpublication ($multiobject_item);
          $page = getobject ($multiobject_item);
          $location = getlocation ($multiobject_item);
          $location = deconvertpath ($location, "file");

          if ($action == "page_favorites_create") $result['result'] = createfavorite ($site, $location, $page, "", $user);
          elseif ($action == "page_favorites_delete") $result['result'] = deletefavorite ($site, $location, $page, "", $user);
        }
      }
    }
    elseif ($folder != "" && is_dir ($location.$folder))
    {
      if ($action == "page_favorites_create") $result['result'] = createfavorite ($site, $location.$folder."/", ".folder", "", $user);
      elseif ($action == "page_favorites_delete") $result['result'] = deletefavorite ($site, $location.$folder."/", ".folder", "", $user);
    }
    elseif ($page != "" && $page != ".folder" && is_file ($location.$page))
    {
      if ($action == "page_favorites_create") $result['result'] = createfavorite ($site, $location, $page, "", $user);
      elseif ($action == "page_favorites_delete") $result['result'] = deletefavorite ($site, $location, $page, "", $user);
    }

    // check result
    if ($result['result'] == false) 
    {
      $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['error-occured'][$lang])."</span>";
      $add_onload = "";
    }
    else 
    {
      $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang])."</span>";
      $add_onload = "if (opener && opener.parent.frames['mainFrame']) opener.parent.frames['mainFrame'].location.reload();
if (opener && parent.frames['objFrame']) parent.frames['objFrame'].location.reload();
if (opener && parent.frames['mainFrame']) parent.frames['mainFrame'].location.reload();";
      $location = "";
      $page = "";
      $pagename = "";  
      $multiobject = "";
    }
  }  
  // check-in / unlock objects
  elseif ($action == "page_unlock" && checkrootpermission ("desktopcheckedout") && $setlocalpermission['root'] == 1)
  {
    if (is_string ($multiobject) && strlen ($multiobject) > 6) $multiobject_array = link_db_getobject ($multiobject);

    if (is_array ($multiobject_array) && sizeof ($multiobject_array) > 1)
    {
      $result['result'] = true;

      foreach ($multiobject_array as $multiobject_item)
      {
        if ($multiobject_item != "" && $result['result'] == true)
        {
          $site = getpublication ($multiobject_item);
          $page = getobject ($multiobject_item);
          $location = getlocation ($multiobject_item);
          $location = deconvertpath ($location, "file");

          $result = unlockobject ($site, $location, $page, $user);
        }
      }
    }
    elseif ($folder != "" && is_dir ($location.$folder))
    {
      $result = unlockobject ($site, $location.$folder."/", ".folder", $user);
    }
    elseif ($page != "" && $page != ".folder" && is_file ($location.$page))
    {
      $result = unlockobject ($site, $location, $page, $user);
    }

    // check result
    if ($result['result'] == false) 
    {
      $show = $result['message'];
      $add_onload = "";
    }
    else 
    {
      $show = $result['message'];
      $add_onload = $result['add_onload'];
      $location = "";
      $page = "";
      $pagename = "";  
      $multiobject = "";
    }
  }  
  // paste
  elseif ($action == "paste") 
  {
    $result = pasteobject ($site, $location, $user);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];      
  }
  // publish
  elseif ($action == "publish") 
  {
    $result = publishobject ($site, $location, $page, $user);
    $add_onload = "opener.frameReload(); ".$result['add_onload'];
    $show = $result['message'];  
  }
  // unpublish
  elseif ($action == "unpublish") 
  {
    $result = unpublishobject ($site, $location, $page, $user);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];  
  }
}
else
{
  $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['you-do-not-have-permissions-to-execute-this-function'][$lang])."</span>";
}

// show loading screen for unzip 
if ($action == "unzip" && $authorized == true)
{
  // load object file and get container and media file
  $objectdata = loadfile ($location, $page);
  $mediafile = getfilename ($objectdata, "media");    
  $mediapath = getmedialocation ($site, $mediafile, "abs_path_media");
  $media_info = getfileinfo ($site, $location.$page, $cat);
    
  // flush
  ob_implicit_flush (true);
  ob_end_flush ();
  sleep (1);

  // unzip file in assets
  if ($cat == "comp" && $mediapath != "" && $mediafile != "" && $location != "")
  {
    $result_unzip = unzipfile ($site, $mediapath.$site.'/'.$mediafile, $location, $media_info['name'], $cat, $user);
  }
  // unzip file in pages
  elseif ($cat == "page" && $location != "" && $page != "")
  {
    $result_unzip = unzipfile ($site, $location.$page, $location, $media_info['name'], $cat, $user);
  }
  else $result_unzip = false;
 
  if (!empty ($result_unzip))
  {
    $result['result'] = true;
    $add_onload = "document.getElementById('hcmsLoadScreen').style.display='none'; if (opener && opener.parent.frames['mainFrame']) opener.parent.frames['mainFrame'].location.reload();\n";
    $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['file-extracted-succesfully'][$lang])."</span><br />\n";
  }
  else
  {
    $result['result'] = false;
    $add_onload = "document.getElementById('hcmsLoadScreen').style.display='none';\n";
    $show = "<span class=\"hcmsHeadline\">".getescapedtext ($hcms_lang['file-could-not-be-extracted'][$lang])."</span><br />\n";
  }
}
?>

<!-- top bar -->
<?php echo showtopbar ("<img src=\"".getthemelocation()."img/info.png\" class=\"hcmsButtonSizeSquare\" />&nbsp;".getescapedtext ($hcms_lang['information'][$lang]), $lang); ?>

<div class="hcmsWorkplaceFrame">
  <table class="hcmsTableNarrow" style="width:100%; height:140px;">
    <tr>
      <td style="text-align:center; vertical-align:middle;"><?php echo $show; ?></td>
    </tr>
  </table>
</div>

<script type="text/javascript">
// load screen
if (document.getElementById('hcmsLoadScreen')) document.getElementById('hcmsLoadScreen').style.display = 'none';

// focus
function popupfocus ()
{
  self.focus();
  setTimeout('popupfocus()', 500);
}

popupfocus ();

<?php
echo $add_onload;

if (!empty ($result['result']))
{
  echo "
// close window
function popupclose ()
{
  self.close();
}

setTimeout('popupclose()', 1500);";
}
?>
</script>

</body>
</html>
