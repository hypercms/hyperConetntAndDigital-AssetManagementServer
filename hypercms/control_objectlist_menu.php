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
$action = getrequest ("action", "objectname");
$multiobject = getrequest ("multiobject");
$location = getrequest_esc ("location", "locationname");
$folder = getrequest_esc ("folder", "objectname");
$foldernew = getrequest_esc ("foldernew", "objectname");
$page = getrequest_esc ("page", "objectname");
$pagenew = getrequest_esc ("pagenew", "objectname");
$contexttype = getrequest_esc ("contexttype"); // contextmenu context-types (folder, object, media)
$from_page = getrequest ("from_page");
$virtual = getrequest ("virtual", "numeric");
$token = getrequest ("token");
$convert_type = getrequest ("convert_type");
$convert_cfg = getrequest ("convert_cfg");

// initalize object linking
if ($location == "" && is_array ($hcms_linking)) 
{
  if (!empty ($hcms_linking['publication']) && valid_publicationname ($hcms_linking['publication'])) $site = $hcms_linking['publication'];
  if (!empty ($hcms_linking['cat']) && valid_objectname ($hcms_linking['cat'])) $cat = $hcms_linking['cat'];
  if (!empty ($hcms_linking['location']) && valid_locationname ($hcms_linking['location'])) $location = $location_ACCESS = $hcms_linking['location'];
  if (!empty ($hcms_linking['object']) && valid_objectname ($hcms_linking['object'])) $page = $hcms_linking['object'];
}
// location has been provided
else
{
  // correct location for access permission
  if ($folder != "") $location_ACCESS = $location.$folder."/";
  else $location_ACCESS = $location;
  
  // get publication and category
  $site = getpublication ($location_ACCESS);
  $cat = getcategory ($site, $location_ACCESS); 
}

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
// load publication configuration
if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location_ACCESS, $cat);
$setlocalpermission_ACCESS = setlocalpermission ($site, $ownergroup, $cat);

// we check for general root element access since localpermissions are checked later
if ($virtual != 1 && ($ownergroup == false || $setlocalpermission_ACCESS['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location))) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

$show = "";
$add_onload = "";
$usedby = "";

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

$multiobject_count = 0;

// if multiobject
if ($multiobject != "")
{
  $multiobject_count = sizeof (link_db_getobject ($multiobject));

  if ($multiobject_count > 1)
  {
    $pagename = $multiobject_count." ".getescapedtext ($hcms_lang['objects-selected'][$lang]);
  }
  elseif ($multiobject_count == 1)
  {
    $object = getobject ($multiobject);

    if (is_dir ($location.$object))
    {
      $page = ".folder";
      $folder = $object;
      $pagename = specialchr_decode ($folder);
      $media = "";
      $file_info = getfileinfo ($site, $location.$page, $cat);  
    }
    else
    {
      $location_esc = convertpath ($site, $location, $cat);
      $page = correctfile ($location, $object);
      $file_info = getfileinfo ($site, $location.$object, $cat);
      $pagename = $file_info['name'];
      $folder = "";
    }
  }
}
// if object
elseif ($location != "" && $page != "")
{
  // folder
  if ($folder != "")
  {
    $page = ".folder";
    $pagename = specialchr_decode ($folder);
    $file_info = getfileinfo ($site, $page, $cat);
  }
  // object
  else
  {
    $page = correctfile ($location, $page);
    $file_info = getfileinfo ($site, $page, $cat);
    $pagename = $file_info['name'];
  } 
}
else
{
  $pagename = "";
  $file_info = Null;
}

// load object file and get container and media file
$objectdata = loadfile ($location_ACCESS, $page);
$contentfile = getfilename ($objectdata, "content");
$container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));  
$media = getfilename ($objectdata, "media");

// set local permissions for current location
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);

// set local permission for parent folder
$location_down = getlocation ($location);
$location_down_esc = getlocation ($location_esc);
$ownergroup_down = accesspermission ($site, $location_down_esc, $cat);
$setlocalpermission_down = setlocalpermission ($site, $ownergroup_down, $cat);


// execute action
if (checktoken ($token, $user))
{
  // create folder
  if ($action == "folder_create" && $setlocalpermission['root'] == 1 && $setlocalpermission['foldercreate'] == 1) 
  {
    $result = createfolder ($site, $location, $foldernew, $user);
  
    $add_onload = $result['add_onload'];
    $show = $result['message'];
  }
  // rename folder
  elseif ($action == "folder_rename" && $setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1) 
  {
    $result = renamefolder ($site, $location, $folder, $foldernew, $user);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];
    $folder = $result['folder'];
    $pagename = $result['name'];  
  }
  // rename object
  elseif ($action == "page_rename" && $page != ".folder" && $setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
  {
    $result = renameobject ($site, $location, $page, $pagenew, $user);
    
    $add_onload = $result['add_onload'];
    $show = $result['message'];  
    $page = $result['object'];
    $pagename = $result['name'];
    $filetype = $result['objecttype'];
  }
  // create zip
  elseif ($action == "zip" && $setlocalpermission['root'] == 1)
  {
    $zipFolder = $mgmt_config['abs_path_temp'];
   
    if ($multiobject != "")
    {
      $multiobject_array = link_db_getobject ($multiobject);
    }
    elseif ($folder != "" && is_dir ($location.$folder))
    {
      $multiobject_array[0] = $location.$folder;
    }
    elseif ($page != "" && $page != ".folder" && is_file ($location.$page))
    {
      $multiobject_array[0] = $location.$page;
    }
  
    $result = zipfiles ($site, $multiobject_array, $zipFolder, $pagenew, $user);
  
    if ($result == true) $result = createmediaobject ($site, $location, $pagenew.".zip", $zipFolder.$pagenew.".zip", $user);
    else $result['result'] = false;
    
    if ($result['result'] == true)
    {
      $add_onload = "parent.frames['mainFrame'].location.reload();";
      $show = getescapedtext ($hcms_lang['the-file-'][$lang].$pagenew.$hcms_lang['zip-was-created'][$lang]);
      $page = $result['object'];
      $pagename = $result['name'];
      $filetype = $result['objecttype'];  
    }
    else
    {
      // max file size default value is 2000 MB
      if (!isset ($mgmt_config['maxzipsize'])) $mgmt_config['maxzipsize'] = 2000;
      $show = getescapedtext ($hcms_lang['the-file-'][$lang].$pagenew.str_replace ("%filesize%", $mgmt_config['maxzipsize'], $hcms_lang['zip-could-not-be-created-max'][$lang]));
    }
  }
  // add to favorites
  elseif (($action == "page_favorite_add" || $action == "page_favorite_delete") && $setlocalpermission['root'] == 1) 
  {
    if ($multiobject != "")
    {
      $multiobject_array = link_db_getobject ($multiobject);
    }
    elseif ($folder != "" && is_dir ($location.$folder))
    {
      $multiobject_array[0] = $location.$folder;
    }
    elseif ($page != "" && $page != ".folder" && is_file ($location.$page))
    {
      $multiobject_array[0] = $location.$page;
    }
    
    foreach ($multiobject_array as $temp)
    {
      if ($action == "page_favorite_add") createfavorite (getpublication ($temp), getlocation ($temp), getobject ($temp), "", $user);
      elseif ($action == "page_favorite_delete") deletefavorite (getpublication ($temp), getlocation ($temp), getobject ($temp), "", $user);
    }

    $show = "";
  }
  // import metadata from CSV file
  elseif ($action == "import" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
  {
    $file_temp = $mgmt_config['abs_path_temp'].uniqid ("tmp").".csv";
    
    if (!empty ($_FILES["importfile"]) && move_uploaded_file ($_FILES["importfile"]["tmp_name"], $file_temp))
    {
      $import = importmetadata ($site, $location, $file_temp, $user, "", "", "", "utf-8");
      
      deletefile (getlocation ($file_temp), getobject ($file_temp), 0);
    }
    
    if (!empty ($import)) $show = getescapedtext ($hcms_lang['the-data-was-saved-successfully'][$lang]);
    else $show = getescapedtext ($hcms_lang['the-data-could-not-be-saved'][$lang]);
  }
  // export selected objects as CSV
  elseif ($action == "export" && $setlocalpermission['root'] == 1)
  {
    if ($multiobject != "")
    {
      $multiobject_array = link_db_getobject ($multiobject);
    }
    elseif ($folder != "" && is_dir ($location.$folder))
    {
      $multiobject_array[0] = $location_esc.$folder;
    }
    elseif ($page != "" && $page != ".folder" && is_file ($location.$page))
    {
      $multiobject_array[0] = $location_esc.$page;
    }
  
    // get all text content/metadata as array
    $assoc_array = getmetadata_multiobjects ($multiobject_array, $user);

    // CSV export
    create_csv ($assoc_array, "export.csv");
  }
}

// define message if object is checked out by another user
if (!empty ($contentfile))
{
  $usedby_array = getcontainername ($contentfile);
  
  if (is_array ($usedby_array) && !empty ($usedby_array['user'])) $usedby = $usedby_array['user'];
  
  if ($usedby != "" && $usedby != $user) $show = getescapedtext ($hcms_lang['object-is-checked-out-by-user'][$lang])." '".$usedby."'";
  else $show = "";
}

// get file info
if ($page != "") 
{
  // correct object file name
  $page = correctfile ($location_ACCESS, $page, $user);      
  // get file info
  $file_info = getfileinfo ($site, $location_ACCESS.$page, $cat);
  $filetype = $file_info['type'];
}
else
{
  $file_info = Null;
  $filetype = "";
}

// define object category name
if ($filetype == "Page")
{
  $pagecomp = getescapedtext ($hcms_lang['page'][$lang]);
}
elseif ($filetype == "Component")
{
  $pagecomp = getescapedtext ($hcms_lang['component'][$lang]);
}
else
{
  $pagecomp = getescapedtext ($hcms_lang['asset'][$lang]);
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script type="text/javascript" src="javascript/jquery/jquery-3.3.1.min.js"></script>
<script type="text/javascript" src="javascript/click.js"></script>
<script type="text/javascript" src="javascript/main.js"></script>
<script type="text/javascript" src="javascript/chat.js"></script>
<script type="text/javascript">
var locklayer = false;
var sidebar = <?php if ($temp_sidebar) echo "true"; else echo "false"; ?>;

function submitToWindow (url, action, windowname, features, width, height)
{
  if (eval (parent.frames['mainFrame'].document.forms['contextmenu_object']))
  {
    if (features == undefined) features = 'scrollbars=no,resizable=no';
    if (width == undefined) width = 400;
    if (height == undefined) height = 180;
    if (windowname == '') windowname = Math.floor(Math.random()*9999999);
    
    hcms_openWindow('', windowname, features, width, height);
    
    var form = parent.frames['mainFrame'].document.forms['contextmenu_object'];
    
    form.attributes['action'].value = url;
    form.elements['action'].value = action;
    form.elements['site'].value = '<?php echo $site; ?>';
    form.elements['cat'].value = '<?php echo $cat; ?>';
    form.elements['location'].value = '<?php echo $location_esc; ?>';
    form.elements['page'].value = '<?php echo $page; ?>';
    form.elements['pagename'].value = '<?php echo $pagename; ?>';
    form.elements['folder'].value = '<?php echo $folder; ?>';
    form.elements['force'].value = 'start';
    form.elements['token'].value = '<?php echo $token_new; ?>';
    form.target = windowname;
    form.submit();
  }
  else alert ('<?php echo getescapedtext ($hcms_lang['please-close-the-search-window'][$lang]); ?>');
}

function submitToSelf (action)
{
  if (parent.frames['mainFrame'].document.forms['contextmenu_object'])
  {
    var form = parent.frames['mainFrame'].document.forms['contextmenu_object'];
    
    form.attributes['action'].value = '<?php echo $_SERVER['PHP_SELF']; ?>';
    form.elements['action'].value = action;
    form.elements['site'].value = '<?php echo $site; ?>';
    form.elements['cat'].value = '<?php echo $cat; ?>';
    form.elements['location'].value = '<?php echo $location_esc; ?>';
    form.elements['page'].value = '<?php echo $page; ?>';
    form.elements['pagename'].value = '<?php echo $pagename; ?>';
    form.elements['folder'].value = '<?php echo $folder; ?>';
    form.elements['media'].value = '<?php echo $media; ?>';
    form.elements['multiobject'].value = '<?php echo $multiobject; ?>';
    form.elements['filetype'].value = '<?php echo $filetype; ?>';
    form.elements['contexttype'].value = '<?php echo $contexttype; ?>';
    form.elements['force'].value = 'start';
    form.elements['token'].value = '<?php echo $token_new; ?>';
    form.target = 'controlFrame';
    form.submit();
  }
  else alert ('<?php echo getescapedtext ($hcms_lang['please-close-the-search-window'][$lang]); ?>');
}

function checkForm_delete ()
{
  check = confirm ("<?php echo getescapedtext ($hcms_lang['are-you-sure-you-want-to-remove-the-item'][$lang]); ?>");

  if (check == true)
  {    
    <?php 
    if ((isset ($multiobject) && $multiobject_count > 1) || (isset ($folder) && $folder != ""))
    {
      echo "submitToWindow('popup_status.php', 'delete', '', 'scrollbars=no,resizable=no', 400, 180);\n";
    }
    elseif (isset ($page) && $page != "" && $page != ".folder")
    {
      echo "hcms_openWindow('popup_action.php?site=".url_encode($site)."&cat=".url_encode($cat)."&action=delete&location=".url_encode($location_esc)."&page=".url_encode($page)."&from_page=".url_encode($from_page)."&multiobject=".url_encode($multiobject)."&token=".$token_new."', '', 'location=no,scrollbars=no,resizable=no,titlebar=no', 400, 180);\n";
    }
    ?>
  }
}

function checkForm_chars(text, exclude_chars)
{
	<?php if (isset ($mgmt_config[$site]['specialchr_disable']) && $mgmt_config[$site]['specialchr_disable']) { ?>
  exclude_chars = exclude_chars.replace(/[-[\]{}()*+?.,\\^$|#\s]/g, "\\$&");
  var expr = new RegExp ("[^a-zA-Z0-9" + exclude_chars + "]", "g");
  <?php } else { ?>
  var expr = new RegExp ('[,;/\\\\~`!@#$%^&:*?<>{}=|]', "g");
  <?php } ?>
	var separator = ', ';
	var found = text.match(expr); 
	
  if (found)
  {
		var addText = '';
    
		for(var i = 0; i < found.length; i++)
    {
			addText += found[i]+separator;
		}
    
		addText = addText.substr(0, addText.length-separator.length);
		alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-do-not-use-the-following-special-characters'][$lang]); ?> ") + addText);
		return false;
	}
  else
  {
		return true;
	}
}

function checkForm_folder_create()
{
  var form = document.forms['folder_create'];
  
  if (form.elements['foldernew'].value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
    form.elements['foldernew'].focus();
    return false;
  }
  
  if (!checkForm_chars(form.elements['foldernew'].value, ".-_"))
  {
    form.elements['foldernew'].focus();
    return false;
  }
  
  form.submit();
  return true;
}

function checkForm_folder_rename()
{
  var form = document.forms['folder_rename'];

  if (form.elements['foldernew'].value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
    form.elements['foldernew'].focus();
    return false;
  }
  
  if (!checkForm_chars(form.elements['foldernew'].value, ".-_"))
  {
    form.elements['foldernew'].focus();
    return false;
  }
  
  form.submit();
  return true;
}

function checkForm_page_rename()
{
  var form = document.forms['page_rename'];
  
  if (form.elements['pagenew'].value.trim() == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
    form.elements['pagenew'].focus();
    return false;
  }
  
  if (!checkForm_chars(form.elements['pagenew'].value, ".-_"))
  {
    form.elements['pagenew'].focus();
    return false;
  }
  
  form.submit()
  return true;
}

function checkForm_zip()
{
  var form = document.forms['page_zip'];
  
  if (form.elements['pagenew'].value == "")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['a-name-is-required'][$lang]); ?>"));
    form.elements['pagenew'].focus();
    return false;
  }
  
  if (!checkForm_chars(form.elements['pagenew'].value, ".-_"))    
  {
    form.elements['pagenew'].focus();
    return false;
  }
  
  form.submit();
  return true;
}

function docConvert (type)
{
  if (parent.frames['mainFrame'].document.forms['contextmenu_object'])
  {
    var form = parent.frames['mainFrame'].document.forms['contextmenu_object'];
    
    form.attributes['action'].value = '<?php echo $_SERVER['PHP_SELF']; ?>';
    form.elements['convert_type'].value = type;
    
    submitToSelf ('download');
    hcms_showHideLayers('downloadLayer','','show');
  }
  else return false; 
}

function imgConvert (type, config)
{
  if (parent.frames['mainFrame'].document.forms['contextmenu_object'])
  {
    var form = parent.frames['mainFrame'].document.forms['contextmenu_object'];
    
    form.attributes['action'].value = '<?php echo $_SERVER['PHP_SELF']; ?>';
    form.elements['convert_type'].value = type;
    form.elements['convert_cfg'].value = config;

    submitToSelf ('download');
    hcms_showHideLayers('downloadLayer','','show');
  }
  else return false; 
}

function vidConvert (type)
{
  if (parent.frames['mainFrame'].document.forms['contextmenu_object'])
  {
    var form = parent.frames['mainFrame'].document.forms['contextmenu_object'];
    
    form.attributes['action'].value = '<?php echo $_SERVER['PHP_SELF']; ?>';
    form.elements['convert_type'].value = type;
    
    submitToSelf ('download');
    hcms_showHideLayers('downloadLayer','','show');
  }
  else return false; 
}

function checkForm_import()
{
  var form = document.forms['import'];
  var filename = form.elements['importfile'].value;
  
  if (filename.trim() == "" || filename.substr((filename.lastIndexOf('.') + 1)).toLowerCase() != "csv")
  {
    alert (hcms_entity_decode("<?php echo getescapedtext ($hcms_lang['please-select-a-file-to-upload'][$lang]); ?>"));
    form.elements['foldernew'].focus();
    return false;
  }

  form.submit();
  return true;
}

function switchview (view)
{
  if (view == "large" || view == "medium" || view == "small" || view == "detail")
  {
    document.forms['memory'].elements['view'].value = view;
    
    // AJAX request to set view
    $.post("<?php echo $mgmt_config['url_path_cms']; ?>service/toggleview.php", {view: view});
    
    // change view in object list
    if (eval (parent.frames['mainFrame']) && typeof parent.frames['mainFrame'].toggleview == 'function') parent.frames['mainFrame'].toggleview (view);
    
    // set icon
    document.getElementById('pic_obj_view').src="<?php echo getthemelocation(); ?>img/button_view_gallery_" + view + ".png";  
    document.getElementById('select_obj_view').style.visibility = 'hidden';

    return true;
  }
  else return false;
}

function switchsidebar ()
{  
  if (!sidebar) view = true;
  else view = false;
  
  if (view == true || view == false)
  {
    // AJAX request to set view
    $.post("<?php echo $mgmt_config['url_path_cms']; ?>service/togglesidebar.php", {view: view});
    
    // change view in object list
    if (eval (parent.frames['mainFrame']) && eval (parent.frames['sidebarFrame']))
    {
      if (view)
      {
        parent.document.getElementById('mainLayer').style.right = "330px";
        parent.document.getElementById('sidebarLayer').style.width = "330px";
        parent.frames['mainFrame'].resizecols();
        sidebar = true;
      }
      else
      {
        parent.document.getElementById('mainLayer').style.right = "0px";
        parent.document.getElementById('sidebarLayer').style.width = "0px";
        parent.frames['mainFrame'].resizecols();
        sidebar = false;
      }
        
      // set sidebar variable in object list 
      parent.frames['mainFrame'].hcms_setGlobalVar('sidebar', sidebar);
    }

    return true;
  }
  else return false;
}

function switchfilter ()
{
  if (eval (document.getElementById('filterLayer')))
  {
    var filterset = document.getElementById('filterLayer');
    var form = document.forms['filter_set'];
    var elem = form.elements;
    var locked = false;

    for (var i=0; i<elem.length; i++)
    {
      if (elem[i].checked == true) locked = true;
    }

    if (filterset.style.visibility == 'hidden') filterset.style.visibility = 'visible';
    else if (locked == false) filterset.style.visibility = 'hidden';
     
    return true;
  }
  else return false;
}

function setfilter (filter)
{
  if (eval (document.forms['filter_set']))
  {
    var form = document.forms['filter_set'];
    var elem = form.elements;
    
    form.submit();
    return true;
  }
  else return false;
}

function openobjectview (location, object, view)
{
  if (location != "" && object != "" && parent.document.getElementById('objectview'))
  {
    parent.openobjectview(location, object, view);
  }
  else return false;
}

function hideselectors ()
{
  hcms_hideSelector('select_obj_view');
  hcms_hideSelector('select_obj_edit');
  hcms_hideSelector('select_obj_convert');
}

<?php if ($is_mobile && isset ($mgmt_config['chat']) && $mgmt_config['chat'] == true) { ?>
// start chat
var chat =  new Chat();

function sendtochat (text)
{
  if (text != "")
  {
    var username = '<?php echo $user; ?>';
    // strip tags
    username = username.replace(/(<([^>]+)>)/ig,"");
    chat.send(text, username);
  }
}
<?php } ?>
</script>
</head>

<body class="hcmsWorkplaceControlWallpaper" onload="<?php echo $add_onload; ?>">

<?php if (!$is_mobile) echo showinfobox ($hcms_lang['move-the-mouse-over-the-icons-to-get-more-information'][$lang], $lang, "position:fixed; top:10px; right:20px;"); ?>

<?php
// define location name
if ($cat == "page")
{
  $abs_path_root = $mgmt_config[$site]['abs_path_page'];      
  $location_name = getlocationname ($site, $location, $cat, "path");
}
elseif ($cat == "comp")
{
  $abs_path_root = $mgmt_config['abs_path_comp'];
  $location_name = getlocationname ($site, $location, $cat, "path");
}
else
{
  $abs_path_root = "";
  $location_name = "&nbsp;";
}

// define object name
if (($page != "" && $page != ".folder") || $multiobject_count > 1)
{
  $item = $pagecomp;
  $object_name = $pagename;
}
elseif ($folder != "")
{
  $item = getescapedtext ($hcms_lang['folder'][$lang]);
  $object_name = specialchr_decode ($folder);
}
else
{
  $item = "&nbsp;";
  $object_name = "&nbsp;";
}
?>

<div class="hcmsLocationBar">
  <?php if (!$is_mobile) { ?>
  <table cellpadding="0" cellspacing="0" style="border:0; width:100%; table-layout:fixed;">
    <tr>
      <?php
      // location
      if ($cat == "page" || $cat == "comp")
      {
        echo "
      <td style=\"white-space:nowrap; width:20px;\"><img src=\"".getthemelocation()."img/folder.png\" title=\"".getescapedtext ($hcms_lang['location'][$lang])."\" align=\"absmiddle\" class=\"hcmsIconList\" />&nbsp;</td>
      <td class=\"hcmsHeadlineTiny\" style=\"white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">".str_replace ("/", " &gt; ", trim ($location_name, "/"))."</td>";
      }
      else 
      {
        echo "
      <td style=\"white-space:nowrap; width:20px;\">&nbsp;</td>
      <td class=\"hcmsHeadlineTiny\">&nbsp;</td>";    
      }
      ?>
    </tr>
    <tr>
      <?php
      // object
      if (empty ($file_info['icon'])) $file_info['icon'] = "Null_media.gif";

      echo "
      <td style=\"white-space:nowrap; width:20px;\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" title=\"".$item."\" align=\"absmiddle\" class=\"hcmsIconList\" />&nbsp;</td>
      <td class=\"hcmsHeadlineTiny\" style=\"white-space:nowrap; overflow:hidden; text-overflow:ellipsis;\">".$object_name."</td>";
      ?>
    </tr>
  </table>
  <?php } else { ?>
  <span class="hcmsHeadlineTiny" style="display:block; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo str_replace ("/", " &gt; ", trim ($location_name, "/"))." &gt; ".$object_name; ?></span>
  <?php } ?>
</div>

<!-- toolbar -->
<div class="hcmsToolbar" <?php if (!$is_mobile) echo "style=\"white-space:nowrap; min-width:820px;\""; ?>>
  <?php if (!$is_mobile) { ?>
  <div class="hcmsToolbarBlock">
    <?php
    if (
         $from_page != "recyclebin" && 
         (
           !valid_locationname (@$hcms_linking['location']) && valid_locationname ($abs_path_root) && valid_locationname ($location_down) && (@substr_count ($abs_path_root, $location) < 1 && $setlocalpermission_down['root'] == 1)
         ) 
         || 
         ( 
           valid_locationname (@$hcms_linking['location']) && valid_locationname ($location) && substr_count (@$hcms_linking['location'], $location) < 1
         )
       )
    {
       echo "
       <img ".
       "onClick=\"if (locklayer == false) parent.location='frameset_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_down_esc)."';\" ".
       "class=\"hcmsButton hcmsButtonSizeSquare\" ".
       "name=\"pic_folder_back\" ".
       "src=\"".getthemelocation()."img/button_back.png\" ".
       "alt=\"".getescapedtext ($hcms_lang['go-to-parent-folder'][$lang])."\" title=\"".getescapedtext ($hcms_lang['go-to-parent-folder'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation()."img/button_back.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?> 
  </div>
  <?php } ?>
  
  <div class="hcmsToolbarBlock">
    <?php
    // filter
    if (!$is_mobile)
    {
      if ($from_page == "" && $cat != "page")
      {
        echo "
      <img src=\"".getthemelocation()."img/button_filter.png\" class=\"hcmsButton hcmsButtonSizeSquare\" onclick=\"switchfilter();\" title=\"".getescapedtext ($hcms_lang['filter-by-file-type'][$lang])."\" alt=\"".getescapedtext ($hcms_lang['filter-by-file-type'][$lang])."\" />";
      }
      else
      {
        echo "
      <img src=\"".getthemelocation()."img/button_filter.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
    }
    ?>    
  </div>

  <div class="hcmsToolbarBlock">
    <?php
    // Preview Button
    if ($container_id > 0 && $multiobject_count <= 1 && $page != "" && $cat != "" && $setlocalpermission['root'] == 1)
    {
      if ($page != ".folder") echo "
      <img onClick=\"openobjectview('".url_encode($location_esc)."', '".url_encode($page)."', 'preview');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" src=\"".getthemelocation()."img/button_file_preview.png\" alt=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" title=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" />";
      elseif ($page == ".folder") echo "
      <img onClick=\"openobjectview('".url_encode($location_esc.$folder)."/', '".url_encode($page)."', 'preview');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" src=\"".getthemelocation()."img/button_file_preview.png\" alt=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" title=\"".getescapedtext ($hcms_lang['preview'][$lang])."\" />";
    }
    else echo "
    <img src=\"".getthemelocation()."img/button_file_preview.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";

    // Live-View Button
    if ($multiobject_count <= 1 && $from_page != "recyclebin" && 
        !empty ($file_info['published']) && $page != ".folder" && $page != "" && valid_publicationname ($site) && 
        $setlocalpermission['root'] == 1 && 
        empty ($media) && $cat == "page"
    )
    {
      echo "
    <img onClick=\"openobjectview('".url_encode($location_esc)."', '".url_encode($page)."', 'liveview');\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_liveview\" src=\"".getthemelocation()."img/button_file_liveview.png\" alt=\"".getescapedtext ($hcms_lang['view-live'][$lang])."\" title=\"".getescapedtext ($hcms_lang['view-live'][$lang])."\" />";
    }
    else echo "
    <img src=\"".getthemelocation()."img/button_file_liveview.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    ?>
  </div>
  
  <div class="hcmsToolbarBlock">
    <?php
    // object edit buttons
    echo "
    <div id=\"button_obj_edit\" onClick=\"hcms_hideSelector('select_obj_view'); hcms_hideSelector('select_obj_convert'); hcms_switchSelector('select_obj_edit');\" class=\"hcmsButton hcmsButtonSizeWide\">
      <img src=\"".getthemelocation()."img/button_edit.png\" class=\"hcmsButtonSizeSquare\" id=\"pic_obj_edit\" name=\"pic_obj_edit\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" /><img src=\"".getthemelocation()."img/pointer_select.png\" class=\"hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" />

      <div id=\"select_obj_edit\" class=\"hcmsSelector\" style=\"position:relative; top:-52px; left:40px; visibility:hidden; z-index:999; width:".(36*7)."px; max-height:38px; overflow:auto; overflow-x:auto; overflow-y:hidden; white-space:nowrap;\">";

    // Edit Button   
    if (
         $container_id > 0 && 
         $from_page != "recyclebin" && 
         $multiobject_count <= 1 && 
         $page != "" && 
         (($media == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1) || 
         ($media != "" && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1))
    )
    {
      if ($page != ".folder") echo "
      <img onClick=\"document.getElementById('button_obj_edit').display='none'; hcms_openWindow('frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."', '".$container_id."', 'location=no,status=yes,scrollbars=no,resizable=yes,titlebar=no', ".windowwidth("object").", ".windowheight("object").");\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_edit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" />";
      elseif ($page == ".folder") echo "
      <img onClick=\"document.getElementById('button_obj_edit').display='none'; hcms_openWindow('frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/&page=".url_encode($page)."', '".$container_id."', 'location=no,status=yes,scrollbars=no,resizable=yes,titlebar=no', ".windowwidth("object").", ".windowheight("object").");\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_edit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" />";
    }
    // Edit button to edit the fileds which are equal across all selected files
    elseif ($container_id > 0 && $multiobject_count > 1 && $from_page != "recyclebin")
    {
      echo "
      <img onClick=\"submitToWindow('page_multiedit.php', '', 'multiedit', 'status=yes,scrollbars=yes,resizable=yes', ".windowwidth("object").", ".windowheight("object")."); document.getElementById('button_obj_edit').click();\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_edit\" src=\"".getthemelocation()."img/button_edit.png\" alt=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" title=\"".getescapedtext ($hcms_lang['edit'][$lang])."\" />";
    }
    else 
    {
      echo "
      <img src=\"".getthemelocation()."img/button_edit.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    
    // Rename Object Button
    if ($multiobject_count <= 1 && $from_page != "recyclebin" && $page != "" && $page != ".folder" && $setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
    {
      echo "
      <img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_showHideLayers(".
                                                      "'foldercreateLayer','','hide',".
                                                      "'folderrenameLayer','','hide',".
                                                      "'fileuploadLayer','','hide',".
                                                      "'objrenameLayer','','show',".
                                                      "'importLayer','','hide',".
                                                      "'hcms_messageLayer','','hide'); document.getElementById('button_obj_edit').display='none';\" name=\"pic_obj_rename\" src=\"".getthemelocation()."img/button_rename.png\" alt=\"".getescapedtext ($hcms_lang['rename'][$lang])."\" title=\"".getescapedtext ($hcms_lang['rename'][$lang])."\" />";
    }
    // Rename Folder Button
    elseif ($multiobject_count <= 1 && $from_page != "recyclebin" && $folder != "" && $setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1)
    {
      echo "
      <img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"if (locklayer == false) hcms_showHideLayers(".
                                                      "'foldercreateLayer','','hide',".
                                                      "'folderrenameLayer','','show',".
                                                      "'fileuploadLayer','','hide',".
                                                      "'objrenameLayer','','hide',".
                                                      "'importLayer','','hide',".
                                                      "'hcms_messageLayer','','hide'); document.getElementById('button_obj_edit').display='none';\" name=\"pic_folder_rename\" src=\"".getthemelocation()."img/button_rename.png\" alt=\"".getescapedtext ($hcms_lang['rename-folder'][$lang])."\" title=\"".getescapedtext ($hcms_lang['rename-folder'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation()."img/button_rename.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    
    // Delete Button
    if (
         ($usedby == "" || $usedby == $user) && 
         (
           ($page != "" && $page != ".folder" && $setlocalpermission['root'] == 1 && $setlocalpermission['delete'] == 1) || 
           (($folder != "" || $page == ".folder") && $setlocalpermission['root'] == 1 && $setlocalpermission['folderdelete'] == 1)
         )
       )
    {
      echo "
      <img onClick=\"if (locklayer == false) checkForm_delete(); document.getElementById('button_obj_edit').display='none';\" ".
           "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_delete\" src=\"".getthemelocation()."img/button_delete.png\" alt=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" title=\"".getescapedtext ($hcms_lang['delete'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation()."img/button_delete.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }    
    ?>
    
    <?php
    // Cut, Copy, Linked-Copy Button
    if ($page != "" && $page != ".folder" && $from_page != "recyclebin")
    {
      if (@$hcms_linking['type'] != "Object" && $setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
      {
        echo "
        <img onClick=\"if (locklayer == false) submitToWindow('popup_action.php', 'cut', ''); document.getElementById('button_obj_edit').display='none';\" ".
                     "class=\"hcmsButton hcmsButtonSizeSquare\" ".
                     "name=\"pic_obj_cut\" ".
                     "src=\"".getthemelocation()."img/button_file_cut.png\" alt=\"".getescapedtext ($hcms_lang['cut'][$lang])."\" title=\"".getescapedtext ($hcms_lang['cut'][$lang])."\" />";
      }
      else
      {
        echo "
        <img src=\"".getthemelocation()."img/button_file_cut.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
  
      if (@$hcms_linking['type'] != "Object" && $setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
      {
        echo "
        <img onClick=\"if (locklayer == false) submitToWindow('popup_action.php', 'copy', ''); document.getElementById('button_obj_edit').display='none';\" ".
                     "class=\"hcmsButton hcmsButtonSizeSquare\" ".
                     "name=\"pic_obj_copy\" ".
                     "src=\"".getthemelocation()."img/button_file_copy.png\" alt=\"".getescapedtext ($hcms_lang['copy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['copy'][$lang])."\" />";
      }
      else
      {
        echo "
        <img src=\"".getthemelocation()."img/button_file_copy.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\">";
      }
      
      if ($container_id > 0 && @$hcms_linking['type'] != "Object" && $setlocalpermission['root'] == 1 && $setlocalpermission['rename'] == 1)
      {
        echo "
        <img onClick=\"if (locklayer == false) submitToWindow('popup_action.php', 'linkcopy', ''); document.getElementById('button_obj_edit').display='none';\" ".
                     "class=\"hcmsButton hcmsButtonSizeSquare\" ".
                     "name=\"pic_obj_linkedcopy\" ".
                     "src=\"".getthemelocation()."img/button_file_copylinked.png\" alt=\"".getescapedtext ($hcms_lang['connected-copy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['connected-copy'][$lang])."\">";
      }
      else
      {
        echo "
        <img src=\"".getthemelocation()."img/button_file_copylinked.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }    
    }
    elseif ($folder != "" && $from_page != "recyclebin")
    {
      if ($setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1)
      {
        echo "
        <img onClick=\"if (locklayer == false) submitToWindow('popup_action.php', 'cut', ''); document.getElementById('button_obj_edit').display='none';\" ".
                     "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_cut\" src=\"".getthemelocation()."img/button_file_cut.png\" alt=\"".getescapedtext ($hcms_lang['cut'][$lang])."\" title=\"".getescapedtext ($hcms_lang['cut'][$lang])."\" /></a>";
      }
      else
      {
        echo "
        <img src=\"".getthemelocation()."img/button_file_cut.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
  
      if ($setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1)
      {
        echo "
        <img onClick=\"if (locklayer == false) submitToWindow('popup_action.php', 'copy', ''); document.getElementById('button_obj_edit').display='none';\" ".
                     "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_copy\" src=\"".getthemelocation()."img/button_file_copy.png\" alt=\"".getescapedtext ($hcms_lang['copy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['copy'][$lang])."\" /></a>";
      }
      else
      {
        echo "
        <img src=\"".getthemelocation()."img/button_file_copy.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
      
      if ($container_id > 0 && $setlocalpermission['root'] == 1 && $setlocalpermission['folderrename'] == 1 && $setlocalpermission['foldercreate'] == 1)
      {
        echo "
        <img onClick=\"if (locklayer == false) submitToWindow('popup_action.php', 'linkcopy', ''); document.getElementById('button_obj_edit').display='none';\" ".
                     "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_linkedcopy\" src=\"".getthemelocation()."img/button_file_copylinked.png\" alt=\"".getescapedtext ($hcms_lang['connected-copy'][$lang])."\" title=\"".getescapedtext ($hcms_lang['connected-copy'][$lang])."\" />";
      }
      else
      {
        echo "
        <img src=\"".getthemelocation()."img/button_file_copylinked.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }      
    }
    else
    {
      echo "
      <img src=\"".getthemelocation()."img/button_file_cut.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />
      <img src=\"".getthemelocation()."img/button_file_copy.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />
      <img src=\"".getthemelocation()."img/button_file_copylinked.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
     // Paste Button
    if (@$hcms_linking['type'] != "Object" && $from_page == "" && ($setlocalpermission['root'] == 1 && ($setlocalpermission['rename'] == 1 || $setlocalpermission['folderrename'] == 1)))
    {
      echo "
      <img onClick=\"if (locklayer == false) submitToWindow('popup_status.php', 'paste', ''); document.getElementById('button_obj_edit').display='none';\" ".
                   "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_paste\" src=\"".getthemelocation()."img/button_file_paste.png\" alt=\"".getescapedtext ($hcms_lang['paste'][$lang])."\" title=\"".getescapedtext ($hcms_lang['paste'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation()."img/button_file_paste.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    
    echo "
      </div>
    </div>";
    ?>    
  </div>
  
  <div class="hcmsToolbarBlock">
    <?php
    // Upload Button (HTML5 file upload)
    $popup_upload = "popup_upload_html.php";
    
    if (@$hcms_linking['type'] != "Object" && $from_page == "" && ($cat != "page" || !empty($mgmt_config[$site]['upload_pages'])) && $setlocalpermission['root'] == 1 && $setlocalpermission['upload'] == 1)
    {
      echo "
      <img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_openWindow('".$popup_upload."?uploadmode=multi&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."', '', 'location=no,status=yes,scrollbars=yes,resizable=yes,titlebar=no', 800, 600);\" name=\"pic_obj_upload\" src=\"".getthemelocation()."img/button_file_upload.png\" alt=\"".getescapedtext ($hcms_lang['upload-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['upload-file'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation()."img/button_file_upload.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }

    // Download/Convert Button (also for folders) 
    // get media file extension
    $media_info = getfileinfo ($site, $media, $cat);
    
    $doc_rendering = false;
    $img_rendering = false;
    $vid_rendering = false;
    
    foreach ($mgmt_docpreview as $docpreview_ext => $docpreview)
    {
      // check file extension
      if (substr_count ($docpreview_ext.".", $media_info['ext'].".") > 0 ) $doc_rendering = true;      
    }
    
    $doc_rendering = $doc_rendering && is_array($mgmt_docconvert) && array_key_exists($media_info['ext'], $mgmt_docconvert);
    
    foreach ($mgmt_imagepreview as $imgpreview_ext => $imgpreview)
    {
      // check file extension
      if (substr_count ($imgpreview_ext.".", $media_info['ext'].".") > 0 )
  		{
  			// check if there are more options for providing the image in other formats
  			if (is_array ($mgmt_imageoptions) && !empty($mgmt_imageoptions))
  			{	
  				foreach ($mgmt_imageoptions as $config_fileext => $config_array) 
  				{
  					foreach ($config_array as $config_name => $value) 
  					{
  						if ($config_name != "thumbnail" && $config_name != "original") 
  						{
  							$img_rendering = true;
  							break 3;
  						}
  					}	
  				}
  			}
  		}      
    }
    
    $vid_rendering = is_supported ($mgmt_mediapreview, $media);
    
    // rendering options
    $perm_rendering = $setlocalpermission['root'] == 1 && $setlocalpermission['download'] == 1;
    $lock_rendering = ($usedby == "" || $usedby == $user);
    $dropbox_rendering = (is_array ($mgmt_config) && array_key_exists ("dropbox_appkey", $mgmt_config) && !empty ($mgmt_config['dropbox_appkey']));
    
    if ($multiobject_count <= 1 && $perm_rendering && $lock_rendering && $page != "" && $media != "" && ($doc_rendering || $img_rendering || $dropbox_rendering))
    {
      echo "
      <div id=\"button_obj_convert\" class=\"hcmsButton hcmsButtonSizeWide\" onClick=\"hcms_hideSelector('select_obj_view'); hcms_hideSelector('select_obj_edit'); hcms_switchSelector('select_obj_convert');\">
        <img src=\"".getthemelocation()."img/button_file_download.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" id=\"pic_obj_convert\" name=\"pic_obj_convert\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /><img src=\"".getthemelocation()."img/pointer_select.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" />

        <div id=\"select_obj_convert\" class=\"hcmsSelector\" style=\"position:relative; top:-52px; left:40px; visibility:hidden; z-index:999; width:180px; max-height:70px; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;\">";
        
      // original file
      if (empty ($downloadformats) || (!is_document ($media_info['ext']) && !is_image ($media_info['ext']) && !is_video ($media_info['ext'])) || (is_document ($media_info['ext']) && !empty ($downloadformats['document']['original'])) || (is_image ($media_info['ext']) && !empty ($downloadformats['image']['original'])) || (is_video ($media_info['ext']) && !empty ($downloadformats['video']['original'])))
      {
        // function imgConvert must be used in order to reset the rendering options
        echo "
          <div class=\"hcmsSelectorItem\" onclick=\"document.getElementById('button_obj_convert').click(); imgConvert ('','');\"><img src=\"".getthemelocation()."img/".$media_info['icon']."\" class=\"hcmsIconList\" align=\"absmiddle\" />".getescapedtext ($hcms_lang['original'][$lang])."&nbsp;</div>";
      }
      
      // document download options
      if ($doc_rendering)
      {
        foreach ($mgmt_docoptions as $ext => $value)
        {
          if ($ext != "" && $value != "")
          {
            $ext_array = explode (".", trim ($ext, "."));
            $doc_type = $ext_array[0];          
            $doc_info = getfileinfo ($site, "file".$ext, "comp");

            if ((empty ($downloadformats) || !empty ($downloadformats['document'][$doc_type])) && in_array ($ext, $mgmt_docconvert[$media_info['ext']]))
            {
              echo "
            <div class=\"hcmsSelectorItem\" onclick=\"document.getElementById('button_obj_convert').click(); docConvert ('".$doc_type."');\"><img src=\"".getthemelocation()."img/".$doc_info['icon']."\" class=\"hcmsIconList\" align=\"absmiddle\" />".$doc_info['type']." (".strtoupper($doc_type).")&nbsp;</div>";
            }
          }
        }
      }
      
      // image download options
      if ($img_rendering)
      {
        foreach ($mgmt_imageoptions as $ext => $config_array) 
        {
          if (is_array ($config_array)) 
          {
            $ext_array = explode (".", trim ($ext, "."));
            $image_type = $ext_array[0];
            $img_info = getfileinfo ($site, $media_info['filename'].".".$image_type, $cat);
            
            foreach ($config_array as $config_name => $config_parameter) 
            {
              if ((empty ($downloadformats) || !empty ($downloadformats['image'][$image_type][$config_name])) && $config_name != "thumbnail" && $config_name != "original") 
              {
                echo "
            <div class=\"hcmsSelectorItem\" onclick=\"document.getElementById('button_obj_convert').click(); imgConvert ('".$image_type."', '".$config_name."');\"><img src=\"".getthemelocation()."img/".$img_info['icon']."\" class=\"hcmsIconList\" align=\"absmiddle\" />".strtoupper($image_type)." ".$config_name."&nbsp;</div>";
              }
            }
          }
        }
      }
      
      // video download options
      if ($vid_rendering && is_video ($media))
      {
        if (empty ($downloadformats) || !empty ($downloadformats['video']['origthumb'])) echo "
          <div class=\"hcmsSelectorItem\" onclick=\"vidConvert('origthumb'); document.getElementById('button_obj_convert').click();\"><img src=\"".getthemelocation()."img/file_mpg.png\" class=\"hcmsIconList\" align=\"absmiddle\" />".getescapedtext ($hcms_lang['preview'][$lang])."&nbsp;</div>";
          
        if (empty ($downloadformats) || !empty ($downloadformats['video']['jpg'])) echo "
          <div class=\"hcmsSelectorItem\" onclick=\"vidConvert('jpg'); document.getElementById('button_obj_convert').click();\"><img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" align=\"absmiddle\" />".getescapedtext ($hcms_lang['images'][$lang])." (JPG)&nbsp;</div>";
          
        if (empty ($downloadformats) || !empty ($downloadformats['video']['png'])) echo "
          <div class=\"hcmsSelectorItem\" onclick=\"vidConvert('png'); document.getElementById('button_obj_convert').click();\"><img src=\"".getthemelocation()."img/file_image.png\" class=\"hcmsIconList\" align=\"absmiddle\" />".getescapedtext ($hcms_lang['images'][$lang])." (PNG)&nbsp;</div>";
      }
			
			//save to dropbox
			if ($dropbox_rendering)
			{
				echo "
            <div class=\"hcmsSelectorItem\" onclick=\"document.getElementById('button_obj_convert').click(); submitToWindow('popup_save_dropbox.php', 'Save to Dropbox', '', 'status=yes,scrollbars=yes,resizable=yes,width=600,height=400', 600, 400);\"><img src=\"".getthemelocation()."img/file_dropbox.png\" class=\"hcmsIconList\" align=\"absmiddle\" />".getescapedtext ($hcms_lang['dropbox'][$lang])."&nbsp;</div>";
			}
      
      echo "
        </div>
      </div>";
    }
    // folder/file download without options
    elseif ($perm_rendering && $lock_rendering && ($media != "" || $page == ".folder" || $multiobject_count > 1) && $page != "")
    {
      echo "
      <div class=\"hcmsButton hcmsButtonSizeWide\" onClick=\"submitToSelf('download'); hcms_showHideLayers('downloadLayer','','show');\">
        <img class=\"hcmsButtonTinyBlank hcmsButtonSizeSquare\" name=\"pic_obj_liveview\" src=\"".getthemelocation()."img/button_file_download.png\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /><img src=\"".getthemelocation()."img/pointer_select.png\" class=\"hcmsButtonTinyBlank hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /></div>";
    }
    else
    {
      echo "
        <div class=\"hcmsButtonOff hcmsButtonSizeWide\"><img src=\"".getthemelocation()."img/button_file_download.png\" class=\"hcmsButtonSizeSquare\" /><img src=\"".getthemelocation()."img/pointer_select.png\" class=\"hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['download-file'][$lang])."\" /></div>";
    }
    ?>    
  </div>
  
  <div class="hcmsToolbarBlock">
    <?php
    // New Folder Button
    if (@$hcms_linking['type'] != "Object" && $from_page == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['foldercreate'] == 1)
    {
      echo "
      <img class=\"hcmsButton hcmsButtonSizeSquare\" ".
           "onClick=\"if (locklayer == false) hcms_showHideLayers(".
                                                      "'select_obj_view','','hide',".
                                                      "'select_obj_edit','','hide',".
                                                      "'select_obj_convert','','hide',".
                                                      "'foldercreateLayer','','show',".
                                                      "'folderrenameLayer','','hide',".
                                                      "'fileuploadLayer','','hide',".
                                                      "'objrenameLayer','','hide',".
                                                      "'importLayer','','hide',".
                                                      "'hcms_messageLayer','','hide'".
                                                      ");\" name=\"pic_folder_create\" src=\"".getthemelocation()."img/button_folder_new.png\" alt=\"".getescapedtext ($hcms_lang['create-folder'][$lang])."\" title=\"".getescapedtext ($hcms_lang['create-folder'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation()."img/button_folder_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }

    // New Object Button
    if (@$hcms_linking['type'] != "Object" && $from_page == "" && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1)
    {
      echo "
      <img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"hcms_openWindow('frameset_content.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."', '', 'location=no,status=yes,scrollbars=no,resizable=yes,titlebar=no', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_new\" src=\"".getthemelocation()."img/button_file_new.png\" alt=\"".getescapedtext ($hcms_lang['new-object'][$lang])."\" title=\"".getescapedtext ($hcms_lang['new-object'][$lang])."\" />";
    }
    else
    {
      echo "
      <img src=\"".getthemelocation()."img/button_file_new.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>    
  </div>
  
  <div class="hcmsToolbarBlock">
    <?php
    // ZIP Button
    if (@$hcms_linking['type'] != "Object" && $from_page == "" && $mgmt_compress['.zip'] != "" && 
        ($usedby == "" || $usedby == $user) && 
         $page != "" &&  
         $setlocalpermission['root'] == 1 && $cat != "page"
       )
    {
      echo "
      <img class=\"hcmsButton hcmsButtonSizeSquare\" ".
           "onClick=\"if (locklayer == false) hcms_showHideLayers(".
                                                      "'select_obj_view','','hide',".
                                                      "'select_obj_edit','','hide',".
                                                      "'select_obj_convert','','hide',".
                                                      "'foldercreateLayer','','hide',".
                                                      "'folderrenameLayer','','hide',".
                                                      "'fileuploadLayer','','hide',".
                                                      "'objrenameLayer','','hide',".
                                                      "'importLayer','','hide',".
                                                      "'hcms_messageLayer','','hide',".
                                                      "'zipLayer','','show'".
                                                      ");\" name=\"pic_zip_create\" src=\"".getthemelocation()."img/button_zip.png\" alt=\"".getescapedtext ($hcms_lang['compress-files'][$lang])."\" title=\"".getescapedtext ($hcms_lang['compress-files'][$lang])."\" />\n";
    }
    else echo "
    <img src=\"".getthemelocation()."img/button_zip.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";

    // UNZIP Button
    if ( 
         $mgmt_uncompress['.zip'] != "" && $from_page == "" && 
         $multiobject_count <= 1 &&
         $filetype == "compressed" &&
         ($usedby == "" || $usedby == $user) && 
         $page != "" && 
         $setlocalpermission['root'] == 1
       )
    {    
      echo "
    <img class=\"hcmsButton hcmsButtonSizeSquare\" ".
           "onClick=\"var test = confirm('".getescapedtext ($hcms_lang['existing-objects-will-be-replaced'][$lang])."'); if (locklayer == false && test) hcms_openWindow('popup_action.php?action=unzip&site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&from_page=".url_encode($from_page)."&token=".$token_new."', '', 'location=no,scrollbars=no,resizable=no,titlebar=no', 400, 180);\" ".
           "name=\"pic_unzip\" src=\"".getthemelocation()."img/button_unzip.png\" alt=\"".getescapedtext ($hcms_lang['extract-file'][$lang])."\" title=\"".getescapedtext ($hcms_lang['extract-file'][$lang])."\" />";
    }
    else echo "
    <img src=\"".getthemelocation()."img/button_unzip.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    ?>    
  </div>
  
  <div class="hcmsToolbarBlock">       
    <?php    
    // SendMail Button
    if ($container_id > 0 && $from_page != "recyclebin" && $page != "" && !empty ($mgmt_config['smtp_host']) && !empty ($mgmt_config[$site]['sendmail']) && $setlocalpermission['root'] == 1 && $setlocalpermission['sendlink'] == 1 && !empty ($mgmt_config['db_connect_rdbms']))
    {
      echo "
    <img onClick=\"submitToWindow('user_sendlink.php', '', 'sendlink', 'scrollbars=yes,resizable=no', 600, 800);\" ".
      "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_preview\" ".
      "src=\"".getthemelocation()."img/button_user_sendlink.png\" ".
      "alt=\"".getescapedtext ($hcms_lang['send-mail-link'][$lang])."\" title=\"".getescapedtext ($hcms_lang['send-mail-link'][$lang])."\" />";
    }
    else
    {
      echo "
    <img src=\"".getthemelocation()."img/button_user_sendlink.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }

    // Send to Chat Button
    if ($is_mobile && !$is_iphone)
    {
      if ($container_id > 0 && $from_page != "recyclebin" && $is_mobile && !$is_iphone && $page != "" && $setlocalpermission['root'] == 1 && !empty ($mgmt_config['chat']))
      {
        if ($page != ".folder") $chatcontent = "hcms_openWindow(\\'frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page)."\\', \\'\\', \\'location=no,status=yes,scrollbars=no,resizable=yes,titlebar=no\\', ".windowwidth("object").", ".windowheight("object").");";
        elseif ($page == ".folder") $chatcontent = "hcms_openWindow(\\'frameset_content.php?site=".url_encode($site)."&ctrlreload=yes&cat=".url_encode($cat)."&location=".url_encode($location_esc.$folder)."/&page=".url_encode($page)."\\', \\'\\', \\'location=no,status=yes,scrollbars=no,resizable=yes,titlebar=no\\', ".windowwidth("object").", ".windowheight("object").");";
        
        echo "
      <img onClick=\"sendtochat('".$chatcontent."');\" ".
        "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_chat\" ".
        "src=\"".getthemelocation()."img/button_chat.png\" ".
        "alt=\"".getescapedtext ($hcms_lang['send-to-chat'][$lang])."\" title=\"".getescapedtext ($hcms_lang['send-to-chat'][$lang])."\" />";
      }
      else
      {
        echo "
      <img src=\"".getthemelocation()."img/button_chat.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
    }
    ?>
  </div>
  
  <div class="hcmsToolbarBlock">
    <?php
    // un/publish object
    if ($container_id > 0 && $page != "" && $page != ".folder" && $from_page != "recyclebin")
    {
      if (($filetype != "" || $multiobject != "") && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
      {
        echo "
      <img onClick=\"if (locklayer == false) ";
        if ($mgmt_config['db_connect_rdbms'] != "") echo "submitToWindow('popup_publish.php', 'publish', '', 'scrollbars=no,resizable=no', 400, 400);";
        else echo "submitToWindow('popup_status.php', 'publish', '', 'scrollbars=no,resizable=no', 400, 400);";
        echo "\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_publish\" src=\"".getthemelocation()."img/button_file_publish.png\" alt=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" />\n";
      }
      else
      {
        echo "
      <img src=\"".getthemelocation()."img/button_file_publish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
  
      if (($filetype != "" || $multiobject != "") && $setlocalpermission['root'] == 1 && $setlocalpermission['publish'] == 1)
      {
      	echo "
      <img onClick=\"if (locklayer == false) ";
        if ($mgmt_config['db_connect_rdbms'] != "") echo "submitToWindow('popup_publish.php', 'unpublish', '', 'scrollbars=no,resizable=no', 400, 400);";
        else echo "submitToWindow('popup_status.php', 'unpublish', '', 'scrollbars=no,resizable=no', 400, 400);";   		
      	echo "\" ".
      	"class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_unpublish\" src=\"".getthemelocation()."img/button_file_unpublish.png\" alt=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" />";
      }
      else
      {
      	echo "
      <img src=\"".getthemelocation()."img/button_file_unpublish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
      }
    }
    // un/publish folder
    elseif ($container_id > 0 && $folder != "" && $from_page != "recyclebin")
    {
      if ($setlocalpermission_ACCESS['root'] == 1 && $setlocalpermission_ACCESS['publish'] == 1)
      {
        echo "
      <img onClick=\"if (locklayer == false) ";
        if ($mgmt_config['db_connect_rdbms'] != "") echo "submitToWindow('popup_publish.php', 'publish', '', 'scrollbars=no,resizable=no', 400, 370);";
        else echo "submitToWindow('popup_status.php', 'publish', '', 'scrollbars=no,resizable=no', 400, 370);"; 
        echo "\" ".
        "class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_publish\" src=\"".getthemelocation()."img/button_file_publish.png\" alt=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['publish'][$lang])."\" />\n";
      }
      else
      {
        echo "<img src=\"".getthemelocation()."img/button_file_publish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      }
  
      if ($setlocalpermission_ACCESS['root'] == 1 && $setlocalpermission_ACCESS['publish'] == 1)
      {
      	echo "
      <img onClick=\"if (locklayer == false) ";
      	if ($mgmt_config['db_connect_rdbms'] != "") echo "submitToWindow('popup_publish.php', 'unpublish', '', 'scrollbars=no,resizable=no', 400, 370);";
        else echo "submitToWindow('popup_status.php', 'unpublish', '', 'scrollbars=no,resizable=no', 400, 370);";
      	echo "\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"pic_obj_unpublish\" src=\"".getthemelocation()."img/button_file_unpublish.png\" alt=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\" title=\"".getescapedtext ($hcms_lang['unpublish'][$lang])."\">\n";
      }
      else
      {
        echo "
      <img src=\"".getthemelocation()."img/button_file_unpublish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />\n";
      }    
    }
    // deactivate buttons
    else
    {
      echo "
     <img src=\"".getthemelocation()."img/button_file_publish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />
     <img src=\"".getthemelocation()."img/button_file_unpublish.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>    
  </div>
  
  <?php if (!$is_mobile) { ?>
  <div class="hcmsToolbarBlock">
    <?php
    // if link references to an object and not a folder, disable search
    if ((!is_array ($hcms_linking) || @$hcms_linking['type'] != "Object") && $location != "" && $mgmt_config['db_connect_rdbms'] != "" && $from_page != "recyclebin")
    {
      echo "
    <img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"if (locklayer == false) parent.mainFrame.location='search_form.php?location=".url_encode($location_esc)."';\" name=\"pic_obj_search\" src=\"".getthemelocation()."img/button_search.png\" alt=\"".getescapedtext ($hcms_lang['search'][$lang])."\" title=\"".getescapedtext ($hcms_lang['search'][$lang])."\" />";
    }
    else
    {
      echo "
    <img src=\"".getthemelocation()."img/button_search.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    
    // CSV import
    if ((!is_array ($hcms_linking) || @$hcms_linking['type'] != "Object") && $setlocalpermission['root'] == 1 && $setlocalpermission['create'] == 1 && $from_page == "")
    {
      echo "
    <img onClick=\"if (locklayer == false) hcms_showHideLayers(".
                                                      "'select_obj_view','','hide',".
                                                      "'select_obj_edit','','hide',".
                                                      "'select_obj_convert','','hide',".
                                                      "'foldercreateLayer','','hide',".
                                                      "'folderrenameLayer','','hide',".
                                                      "'fileuploadLayer','','hide',".
                                                      "'objrenameLayer','','hide',".
                                                      "'importLayer','','show',".
                                                      "'hcms_messageLayer','','hide'".
                                                      ");\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media_import\" src=\"".getthemelocation()."img/button_import.png\" alt=\"".getescapedtext ($hcms_lang['import-list-comma-delimited'][$lang])."\" title=\"".getescapedtext ($hcms_lang['import-list-comma-delimited'][$lang])."\" />";
    }
    else
    {
      echo "
    <img src=\"".getthemelocation()."img/button_import.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    
    // CSV export
    if (($usedby == "" || $usedby == $user) && ($page != "" || $multiobject_count > 0) && $mgmt_config['db_connect_rdbms'] != "" && $from_page != "recyclebin")
    {
      echo "
    <img onClick=\"submitToSelf('export')\" class=\"hcmsButton hcmsButtonSizeSquare\" name=\"media_export\" src=\"".getthemelocation()."img/button_export_page.png\" alt=\"".getescapedtext ($hcms_lang['export-list-comma-delimited'][$lang])."\" title=\"".getescapedtext ($hcms_lang['export-list-comma-delimited'][$lang])."\" />";
    }
    else
    {
      echo "
    <img src=\"".getthemelocation()."img/button_export_page.png\" class=\"hcmsButtonOff hcmsButtonSizeSquare\" />";
    }
    ?>    
  </div>
  <?php } ?>
  
  <div class="hcmsToolbarBlock">
    <?php
    // reload button
    if ($from_page == "") $refresh = "location='explorer_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."'";
    else $refresh = "location.reload();";
      
    echo "
    <img class=\"hcmsButton hcmsButtonSizeSquare\" onClick=\"if (locklayer == false) parent.frames['mainFrame'].".$refresh.";\" name=\"pic_obj_refresh\" src=\"".getthemelocation()."img/button_view_refresh.png\" alt=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\" title=\"".getescapedtext ($hcms_lang['refresh'][$lang])."\">";  
    ?>
  </div>
  
  <div class="hcmsToolbarBlock">
    <?php
    // help button
    if (file_exists ($mgmt_config['abs_path_cms']."help/usersguide_".$hcms_lang_shortcut[$lang].".pdf"))
    {
      echo "<img  onClick=\"hcms_openWindow('help/usersguide_".$hcms_lang_shortcut[$lang].".pdf', 'help', 'location=no,scrollbars=no,resizable=yes,titlebar=no', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />";
    }
    elseif (file_exists ($mgmt_config['abs_path_cms']."help/usersguide_en.pdf"))
    {
      echo "<img  onClick=\"hcms_openWindow('help/usersguide_en.pdf', 'help', 'location=no,scrollbars=no,resizable=yes,titlebar=no', ".windowwidth("object").", ".windowheight("object").");\" name=\"pic_obj_help\" src=\"".getthemelocation()."img/button_help.png\" class=\"hcmsButton hcmsButtonSizeSquare\" alt=\"".getescapedtext ($hcms_lang['help'][$lang])."\" title=\"".getescapedtext ($hcms_lang['help'][$lang])."\" />";
    }
    ?>
  </div>
  
  <div style="float:right; margin:0px 8px 0px 0px">
    <?php
    // object list views
    echo "
      <form name=\"memory\" style=\"display:none;\">
        <input name=\"view\" type=\"hidden\" value=\"".$temp_explorerview."\" />
      </form>
      <div id=\"button_obj_view\" onClick=\"hcms_hideSelector('select_obj_edit'); hcms_hideSelector('select_obj_convert'); hcms_switchSelector('select_obj_view');\" class=\"hcmsButton hcmsButtonSizeWide\">
        <img src=\"".getthemelocation()."img/button_view_gallery_".$temp_explorerview.".png\" class=\"hcmsButtonSizeSquare\" id=\"pic_obj_view\" name=\"pic_obj_view\" alt=\"".getescapedtext ($hcms_lang['thumbnail-gallery'][$lang])."\" title=\"".getescapedtext ($hcms_lang['thumbnail-gallery'][$lang])."\" /><img src=\"".getthemelocation()."img/pointer_select.png\" class=\"hcmsButtonSizeNarrow\" alt=\"".getescapedtext ($hcms_lang['thumbnail-gallery'][$lang])."\" title=\"".getescapedtext ($hcms_lang['thumbnail-gallery'][$lang])."\" />

        <div id=\"select_obj_view\" class=\"hcmsSelector\" style=\"position:relative; top:-52px; left:-180px; visibility:hidden; z-index:999; width:180px; max-height:".($is_mobile ? "50px" : "70px")."; overflow:auto; overflow-x:hidden; overflow-y:auto; white-space:nowrap;\">
          <div class=\"hcmsSelectorItem\" onclick=\"switchview ('large'); document.getElementById('button_obj_view').click();\"><img src=\"".getthemelocation()."img/button_view_gallery_large.png\" class=\"hcmsIconList\" align=\"absmiddle\" />".getescapedtext ($hcms_lang['large-thumbnails'][$lang])."&nbsp;</div>
          <div class=\"hcmsSelectorItem\" onclick=\"switchview ('medium'); document.getElementById('button_obj_view').click();\"><img src=\"".getthemelocation()."img/button_view_gallery_medium.png\" class=\"hcmsIconList\" align=\"absmiddle\" />".getescapedtext ($hcms_lang['medium-thumbnails'][$lang])."&nbsp;</div>
          <div class=\"hcmsSelectorItem\" onclick=\"switchview ('small'); document.getElementById('button_obj_view').click();\"><img src=\"".getthemelocation()."img/button_view_gallery_small.png\" class=\"hcmsIconList\" align=\"absmiddle\" />".getescapedtext ($hcms_lang['small-thumbnails'][$lang])."&nbsp;</div>
          <div class=\"hcmsSelectorItem\" onclick=\"switchview ('detail'); document.getElementById('button_obj_view').click();\"><img src=\"".getthemelocation()."img/button_view_gallery_detail.png\" class=\"hcmsIconList\" align=\"absmiddle\" />".getescapedtext ($hcms_lang['details'][$lang])."&nbsp;</div>
        </div>
      </div>";
    ?>
    
    <?php
    // sidebar
    if (!$is_mobile)
    {
        echo "
      <img src=\"".getthemelocation()."img/button_sidebar.png\" class=\"hcmsButton hcmsButtonSizeSquare\" onclick=\"switchsidebar();\" title=\"".getescapedtext ($hcms_lang['preview-window'][$lang])."\" alt=\"".getescapedtext ($hcms_lang['preview-window'][$lang])."\" />";
    }
    ?>
  </div>

</div>

<!-- filter bar -->
<?php if (!$is_mobile && $from_page == "" && $cat != "page") { ?>
<div id="filterLayer" style="position:fixed; bottom:3px; left:3px; margin:0; padding:0; visibility:<?php if (isset ($objectfilter) && is_array ($objectfilter) && sizeof ($objectfilter) > 0) echo "visible"; else echo "hidden"; ?>">
  <form name="filter_set" action="explorer_objectlist.php" target="mainFrame" method="get">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="virtual" value="<?php echo $virtual; ?>" />
    <img src="<?php echo getthemelocation(); ?>img/button_filter.png" class="hcmsIconList" style="vertical-align:middle;" />
    <input type="hidden" name="filter[dummy]" value="1" />
    <input type="checkbox" id="filter1" onclick="setfilter();" name="filter[comp]" value="1" <?php if (isset ($objectfilter['comp']) && $objectfilter['comp'] == 1) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter1"><?php echo getescapedtext ($hcms_lang['component'][$lang]); ?></label>&nbsp;&nbsp;
    <input type="checkbox" id="filter2" onclick="setfilter();" name="filter[image]" value="1" <?php if (isset ($objectfilter['image']) && $objectfilter['image'] == 1) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter2"><?php echo getescapedtext ($hcms_lang['image'][$lang]); ?></label>&nbsp;&nbsp;
    <input type="checkbox" id="filter3" onclick="setfilter();" name="filter[document]" value="1" <?php if (isset ($objectfilter['document']) && $objectfilter['document'] == 1) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter3"><?php echo getescapedtext ($hcms_lang['document'][$lang]); ?></label>&nbsp;&nbsp;
    <input type="checkbox" id="filter4" onclick="setfilter();" name="filter[video]" value="1" <?php if (isset ($objectfilter['video']) && $objectfilter['video'] == 1) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter4"><?php echo getescapedtext ($hcms_lang['video'][$lang]); ?></label>&nbsp;&nbsp;
    <input type="checkbox" id="filter5" onclick="setfilter();" name="filter[audio]" value="1" <?php if (isset ($objectfilter['audio']) && $objectfilter['audio'] == 1) echo "checked=\"checked\""; ?>/>&nbsp;<label for="filter5"><?php echo getescapedtext ($hcms_lang['audio'][$lang]); ?></label>&nbsp;&nbsp;
  </form>
</div>
<?php } ?>

<?php
echo showmessage ($show, 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
?>

<div id="foldercreateLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:1; left:15px; top:15px; visibility:hidden">
<form name="folder_create" action="" method="post" onsubmit="return checkForm_folder_create();">
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="action" value="folder_create" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table width="100%" height="60" border="0" cellspacing="4" cellpadding="0">
    <tr>
      <td valign="middle">
        <?php echo getescapedtext ($hcms_lang['create-folder'][$lang]); ?> 
        <input type="text" name="foldernew" maxlength="<?php if (!empty ($mgmt_config['max_digits_filename']) && intval ($mgmt_config['max_digits_filename']) > 0) echo intval ($mgmt_config['max_digits_filename']); else echo "200"; ?>" style="width:220px;" />
        <img name="Button1" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_folder_create();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose1" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose1','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('foldercreateLayer','','hide');" />
      </td>      
    </tr>
  </table>
</form>
</div>

<div id="folderrenameLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:2; left:15px; top:15px; visibility:hidden;">
<form name="folder_rename" action="" method="post" onsubmit="return checkForm_folder_rename();">
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
  <input type="hidden" name="action" value="folder_rename" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table width="100%" height="60" border="0" cellspacing="4" cellpadding="0">
    <tr>
      <td valign="middle">
        <?php echo getescapedtext ($hcms_lang['rename-folder'][$lang]); ?>
        <span style="white-space:nowrap;">
          <input type="text" name="foldernew" maxlength="<?php if (!empty ($mgmt_config['max_digits_filename']) && intval ($mgmt_config['max_digits_filename']) > 0) echo intval ($mgmt_config['max_digits_filename']); else echo "200"; ?>" style="width:220px;" value="<?php echo $pagename; ?>" />
          <img name="Button2" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_folder_rename();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" align="absmiddle" alt="OK" title="OK" />
        </span>
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose2" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose2','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('folderrenameLayer','','hide');" />
      </td>        
    </tr>
  </table>
</form>
</div>

<div id="objrenameLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:3; left:15px; top:15px; visibility:hidden">
<form name="page_rename" action="" onsubmit="return checkForm_folder_rename();">
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="action" value="page_rename" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle">
        <?php echo getescapedtext ($hcms_lang['rename'][$lang]);  if ($filetype == "Page" || $filetype == "Component") echo " (".getescapedtext ($hcms_lang['name-without-ext'][$lang]).")"; ?>
        <span style="white-space:nowrap;">
          <input type="text" name="pagenew" maxlength="<?php if (!empty ($mgmt_config['max_digits_filename']) && intval ($mgmt_config['max_digits_filename']) > 0) echo intval ($mgmt_config['max_digits_filename']); else echo "200"; ?>" style="width:220px;" value="<?php echo substr ($pagename, 0, strrpos ($pagename, ".")); ?>" />
          <img name="Button5" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_page_rename();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button5','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" align="absmiddle" alt="OK" title="OK" />
        </span>
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose3" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose3','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('objrenameLayer','','hide');" />
      </td>      
    </tr>
  </table>
</form>
</div>

<div id="zipLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "650px"; ?>; height:60px; z-index:4; left:15px; top:15px; visibility:hidden">
<form name="page_zip" action="">
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="page" value="<?php echo $page; ?>" />
  <input type="hidden" name="folder" value="<?php echo $folder; ?>" />
  <input type="hidden" name="action" value="zip" />
  <input type="hidden" name="multiobject" value="<?php echo $multiobject ?>" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>" />
  
  <table width="100%" height="60" border="0" cellspacing="2" cellpadding="0">
    <tr>
      <td valign="middle">
        <?php echo getescapedtext ($hcms_lang['create-zip-file-without-ext'][$lang]); ?>
        <span style="white-space:nowrap;">
          <input type="text" name="pagenew" maxlength="100" style="width:220px;" value="<?php echo substr ($pagename, 0, strrpos ($pagename, ".")); ?>" />
          <img name="Button6" src="<?php echo getthemelocation(); ?>img/button_ok.png" onclick="checkForm_zip();" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button6','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" align="absmiddle" alt="OK" title="OK" />
        </span>
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose4" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose4','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('zipLayer','','hide');" />
      </td>       
    </tr>
  </table>
</form>
</div>

<div id="importLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "90%"; else echo "850px"; ?>; height:75px; z-index:1; left:15px; top:10px; visibility:hidden">
<form name="import" action="" method="post" enctype="multipart/form-data" onsubmit="return checkForm_import();">
  <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
  <input type="hidden" name="action" value="import" />
  <input type="hidden" name="token" value="<?php echo $token_new; ?>">
  
  <table width="100%" height="75" border="0" cellspacing="4" cellpadding="0">
    <tr>
      <td valign="middle">
        <?php echo str_replace ("(", "<br/>(", getescapedtext ($hcms_lang['upload-csv-file'][$lang])); ?><br />
        <input name="importfile" type="file" size="60" accept="text/*" />
        <img name="Button7" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm_import();" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button7','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" align="absmiddle" alt="OK" title="OK" />
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose7" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose7','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('importLayer','','hide');" />
      </td>      
    </tr>
  </table>
</form>
</div>

<div id="downloadLayer" class="hcmsMessage" style="position:absolute; width:<?php if ($is_mobile) echo "80%"; else echo "650px"; ?>; height:60px; z-index:15; left:15px; top:15px; visibility:<?php echo ($action == 'download' ? 'visible' : 'hidden'); ?>;" >
  <table width="100%" height="60" border=0 cellspacing=0 cellpadding=3 class="hcmsMessage">
    <tr>
      <td align="left" valign="middle">
        <div style="width:100%; height:100%; z-index:10; overflow:auto;">
          <?php
          // iPhone download
          if ($action == "download" && $is_iphone)
          { 
            $downloadlink = createmultidownloadlink ($site, $multiobject, $media, $location.$folder, $pagename, $user, $convert_type, $convert_cfg, "wrapper");
            
            echo "<a href=\"".$downloadlink."\" class=\"button hcmsButtonGreen\" target=\"_blank\">".getescapedtext ($hcms_lang['downloadview-file'][$lang])."</a>";
          }
          else
          {
            echo getescapedtext ($hcms_lang['please-wait-while-your-download-is-being-processed'][$lang]);
          }
          ?>
        </div>
      </td>
      <td width="16" align="right" valign="top">
        <img name="hcms_mediaClose6" src="<?php echo getthemelocation(); ?>img/button_close.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['close'][$lang]); ?>" onMouseOut="hcms_swapImgRestore();" onMouseOver="hcms_swapImage('hcms_mediaClose6','','<?php echo getthemelocation(); ?>img/button_close_over.png',1);" onClick="hcms_showHideLayers('downloadLayer','','hide');" />
      </td>        
    </tr>
  </table>
</div>
<?php 
if ($action == "download" && !$is_iphone)
{
  $downloadlink = createmultidownloadlink ($site, $multiobject, $media, $location.$folder, $pagename, $user, $convert_type, $convert_cfg);

  if ($downloadlink != "")
  {
?>
<script type="text/javascript">
function downloadFile()
{
  hcms_showHideLayers('downloadLayer','','hide');
  location.replace('<?php echo $downloadlink; ?>');
}

setTimeout('downloadFile()', 2000);
</script>  
<?php
  }
  // download failed (zip file could not be created)
  else
  {
    echo showmessage (str_replace ("%filesize%", $mgmt_config['maxzipsize'], $hcms_lang['download-failed-max'][$lang]), 650, 60, $lang, "position:fixed; left:15px; top:15px; ");
  }
}
?>

</body>
</html>
