<?php
/*
 * This file is part of
 * hyper Content & Digital Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 */

// session
define ("SESSION", "create");
// management configuration
require ("../config.inc.php");
// hyperCMS API
require ("../function/hypercms_api.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$contenttype = getrequest_esc ("contenttype");
$contentbot = getrequest_esc ("contentbot");
$db_connect = getrequest_esc ("db_connect", "objectname");
$id = getrequest_esc ("id", "objectname");
$label = getrequest_esc ("label");
$tagname = getrequest_esc ("tagname", "objectname");
$list = getrequest_esc ("list");
$default = getrequest_esc ("default");
$token = getrequest ("token");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1) killsession ($user);

// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------

// load object file and get container
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");

// define content-type if not set
if ($contenttype == "") 
{
  $contenttype = "text/html; charset=".$mgmt_config[$site]['default_codepage'];
  $charset = $mgmt_config[$site]['default_codepage'];
}
elseif (strpos ($contenttype, "charset") > 0)
{
  $charset = getattribute ($contenttype, "charset");
}
else $charset = $mgmt_config[$site]['default_codepage'];

// create secure token
$token = createtoken ($user);

if ($label == "") $label = $id;
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo $charset; ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="../javascript/main.js" type="text/javascript">
</script>
<script language="JavaScript">
function setsavetype(type)
{
  document.forms['editor'].elements['savetype'].value = type;
  document.forms['editor'].submit();
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">
<?php
// read content using db_connect
if (!empty ($db_connect) && $db_connect != false && file_exists ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
{
  include ($mgmt_config['abs_path_data']."db_connect/".$db_connect);
  
  $db_connect_data = db_read_text ($site, $contentfile, "", $id, "", $user);
  
  if ($db_connect_data != false) $contentbot = $db_connect_data['text'];
  else $contentbot = false;
}  
else $contentbot = false;

// read content from content container
if ($contentbot == false) 
{
  $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml")); 
  
  $filedata = loadcontainer ($contentfile, "work", $user);
  
  if ($filedata != "")
  {
    $contentarray = selectcontent ($filedata, "<text>", "<text_id>", $id);
    $contentarray = getcontent ($contentarray[0], "<textcontent>");
    $contentbot = $contentarray[0];
  }
}

// set default value given eventually by tag
if ($contentbot == "" && $default != "") $contentbot = $default;

if (!empty ($list))
{
  // escape special characters
  $list = str_replace (array("\"", "<", ">"), array("&quot;", "&lt;", "&gt;"), $list);  
  
  // get list entries
  $list = rtrim ($list, "|");
  $list_array = explode ("|", $list);
}
?>

<!-- top bar -->
<?php echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame"); ?>

<!-- form for content -->
<div class="hcmsWorkplaceFrame">
  <form name="editor" method="post" action="<?php echo $mgmt_config['url_path_cms']; ?>service/savecontent.php">
    <input type="hidden" name="contenttype" value="<?php echo $contenttype; ?>">
    <input type="hidden" name="site" value="<?php echo $site; ?>">
    <input type="hidden" name="cat" value="<?php echo $cat; ?>">
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>">
    <input type="hidden" name="page" value="<?php echo $page; ?>">
    <input type="hidden" name="db_connect" value="<?php echo $db_connect; ?>">
    <input type="hidden" name="tagname" value="<?php echo $tagname; ?>">
    <input type="hidden" name="id" value="<?php echo $id; ?>">
    <input type="hidden" name="list" value="<?php echo $list; ?>">
    <input type="hidden" name="savetype" value="">
    <input type="hidden" name="token" value="<?php echo $token; ?>">
    
    <table class="hcmsTableStandard">
      <tr>
        <td>
          <img name="Button_so" src="<?php echo getthemelocation(); ?>img/button_save.png" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editorl_so');" alt="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" />    
          <img name="Button_sc" src="<?php echo getthemelocation(); ?>img/button_saveclose.png" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editorl_sc');" alt="<?php echo getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang); ?>" />
          <br />
          <select name="<?php echo $tagname."[".$id."]"; ?>">
          <?php
          if (!empty ($list_array) && is_array ($list_array))
          {
            foreach ($list_array as $list_entry)
            {
              $list_entry = trim ($list_entry);
              $end_val = strlen ($list_entry)-1;
              
              if (($start_val = strpos($list_entry, "{")) > 0 && strpos($list_entry, "}") == $end_val)
              {
                $diff_val = $end_val-$start_val-1;
                $list_value = substr ($list_entry, $start_val+1, $diff_val);
                $list_text = substr ($list_entry, 0, $start_val);
              } 
              else $list_value = $list_text = $list_entry;
                
              echo "
                  <option value=\"".$list_value."\""; 
              if ($list_value == $contentbot) echo " selected"; 
              echo  ">".$list_text."</option>";
            }
          }
          ?>
          </select>
        </td>
      </tr>
    </table>
  </form>
</div>

</body>
</html>
