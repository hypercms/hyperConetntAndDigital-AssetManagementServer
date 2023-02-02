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
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$contenttype = getrequest_esc ("contenttype");
$db_connect = getrequest_esc ("db_connect", "objectname");
$id = getrequest_esc ("id", "objectname");
$label = getrequest_esc ("label");
$tagname = getrequest_esc ("tagname", "objectname");
$value = getrequest_esc ("value");
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

// initialize
$contentbot = "";

// load object file and get container
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");
$container_id = getcontentcontainerid ($contentfile); 

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

header ('Content-Type: text/html; charset='.$charset);

// read content using db_connect when db_connect is not used
if (!empty ($db_connect) && valid_objectname ($db_connect) && is_file ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
{
  include ($mgmt_config['abs_path_data']."db_connect/".$db_connect);

  $db_connect_data = db_read_text ($site, $contentfile, "", $id, "", $user);

  if ($db_connect_data != false) $contentbot = $db_connect_data['text'];
}  

// read content from content container when db_connect is not used
if (empty ($db_connect_data))
{
  $filedata = loadcontainer ($contentfile, "work", $user);

  if ($filedata != "")
  {
    $temp_array = selectcontent ($filedata, "<text>", "<text_id>", $id);

    if (!empty ($temp_array[0]))
    {
      $temp_array = getcontent ($temp_array[0], "<textcontent>");
      if (!empty ($temp_array[0])) $contentbot = $temp_array[0];
    }
  }
}

// set default value given eventually by tag
if (empty ($contentbot) && !empty ($default)) $contentbot = $default;

if ($value == $contentbot) $checked = " checked";
else $checked = "";

if ($label == "") $label = $id;
else $label = getlabel ($label, $lang);

// create secure token
$token = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo $charset; ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css?v=<?php echo getbuildnumber(); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>?v=<?php echo getbuildnumber(); ?>" />
<script type="text/javascript" src="javascript/main.min.js?v=<?php echo getbuildnumber(); ?>"></script>
<script type="text/javascript" src="javascript/jquery/jquery.min.js"></script>
<script language="JavaScript">
  
function setsavetype (type)
{
  document.forms['editor'].elements['savetype'].value = type;
  document.forms['editor'].submit();
}

// check for modified content
function checkUpdatedContent ()
{
  $.ajax({
    type: 'POST',
    url: "<?php echo cleandomain ($mgmt_config['url_path_cms'])."service/checkupdatedcontent.php"; ?>",
    data: {container_id:"<?php echo $container_id; ?>",tagname:"text",tagid:"<?php echo $id; ?>"},
    success: function (data)
    {
      if (data.message.length !== 0)
      {
        console.log('The same content has been modified by another user');
        var update = confirm (hcms_entity_decode(data.message));
        if (update == true) location.reload();
      }
    },
    dataType: "json",
    async: false
  });
}

setInterval (checkUpdatedContent, 3000);
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php echo showtopbar ($label, $lang, $mgmt_config['url_path_cms']."page_view.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame"); ?>

<!-- form for content -->
<div class="hcmsWorkplaceFrame">
  <form name="editor" method="post" action="<?php echo $mgmt_config['url_path_cms']; ?>service/savecontent.php">
    <input type="hidden" name="contenttype" value="<?php echo $contenttype; ?>" />
    <input type="hidden" name="site" value="<?php echo $site; ?>" />
    <input type="hidden" name="cat" value="<?php echo $cat; ?>" />
    <input type="hidden" name="location" value="<?php echo $location_esc; ?>" />
    <input type="hidden" name="page" value="<?php echo $page; ?>" />
    <input type="hidden" name="db_connect" value="<?php echo $db_connect; ?>" />
    <input type="hidden" name="tagname" value="<?php echo $tagname; ?>" />
    <input type="hidden" name="id" value="<?php echo $id; ?>" />
    <input type="hidden" name="value" value="<?php echo $value; ?>" />
    <input type="hidden" name="savetype" value="" />
    <input type="hidden" name="<?php echo $tagname."[".$id."]"; ?>" value="" />
    <input type="hidden" name="token" value="<?php echo $token; ?>" />

    <table class="hcmsTableStandard">
      <tr>
        <td>
          <img name="Button_so" src="<?php echo getthemelocation(); ?>img/button_save.png" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editorc_so');" alt="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save'][$lang], $charset, $lang); ?>" />
          <img name="Button_sc" src="<?php echo getthemelocation(); ?>img/button_saveclose.png" class="hcmsButton hcmsButtonSizeSquare" onClick="setsavetype('editorc_sc');" alt="<?php echo getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang); ?>" title="<?php echo getescapedtext ($hcms_lang['save-and-close'][$lang], $charset, $lang); ?>" />
         </td>
       </tr>
       <tr>
         <td>
          <input type="hidden" name="<?php echo $tagname."[".$id."]"; ?>" id="dummy" value="" />
          <label><input type="checkbox" name="<?php echo $tagname."[".$id."]"; ?>" onclick="if (this.ckecked) document.getElementById('dummy').disabled=true;" value="<?php echo $value; ?>"<?php echo $checked; ?> /> <?php echo $value; ?></label>
        </td>
      </tr>
    </table>
  </form>
</div>

<?php includefooter(); ?>
</body>
</html>