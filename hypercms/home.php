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
// template engine
require ("function/hypercms_tplengine.inc.php");
// version info
require ("version.inc.php");


// input parameters
$action = getrequest ("action");
$homeboxes = getrequest ("homeboxes");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// set boxes for user
if ($action == "save")
{
  setboxes ($homeboxes, $user);
}

// wallpaper
$wallpaper = "";

if ($hcms_themename != "mobile")
{
  if (is_file ($mgmt_config['abs_path_cms']."theme/".$hcms_themename."/img/wallpaper.jpg")) $wallpaper = cleandomain ($mgmt_config['url_path_cms']."theme/".$hcms_themename."/img/wallpaper.jpg");
  elseif (is_file ($mgmt_config['abs_path_cms']."theme/".$hcms_themename."/img/wallpaper.png")) $wallpaper = cleandomain ($mgmt_config['url_path_cms']."theme/".$hcms_themename."/img/wallpaper.png");
  elseif (!empty ($mgmt_config['wallpaper'])) $wallpaper = $mgmt_config['wallpaper'];
  else $wallpaper = getwallpaper ($mgmt_config['version']);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=0.9, maximum-scale=1.0, user-scalable=0" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/jquery/jquery-1.10.2.min.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
<script src="javascript/main.js" type="text/javascript"></script>

<style>
video#videoScreen
{ 
    position: fixed;
    top: 50%;
    left: 50%;
    min-width: 100%;
    min-height: 100%;
    width: auto;
    height: auto;
    z-index: -100;
    -ms-transform: translateX(-50%) translateY(-50%);
    -moz-transform: translateX(-50%) translateY(-50%);
    -webkit-transform: translateX(-50%) translateY(-50%);
    transform: translateX(-50%) translateY(-50%);
    background: url('<?php echo getthemelocation(); ?>/img/backgrd_start.png') no-repeat;
    background-size: cover; 
}

@media screen and (max-device-width: 800px)
{
  #videoScreen
  {
    display: none;
  }
}
</style>

<script type="text/javascript">
// callback for hcms_geolocation
function hcms_geoposition (position)
{
  if (position)
  {
    var latitude = position.coords.latitude;
    var longitude = position.coords.longitude;
  }
  else return false;
  
  if (latitude != "" && longitude != "")
  {
    // AJAX request to set geo location
    $.post("<?php echo $mgmt_config['url_path_cms']; ?>service/setgeolocation.php", {latitude: latitude, longitude: longitude});

    return true;
  }
  else return false;
}

function insertOption (newtext, newvalue)
{
  var selectbox = document.forms['box_form'].elements['box_array'];
  newentry = new Option (newtext, newvalue, false, true);
  var i;
  
  if (selectbox.length > 0)
  {  
    var position = -1;

    for (i=0; i<selectbox.length; i++)
    {
      if (selectbox.options[i].selected) position = i;
      // duplicate entry
      if (selectbox.options[i].value == newvalue) return false;
    }
    
    if (position != -1)
    {
      selectbox.options[selectbox.length] = new Option();
    
      for (i=selectbox.length-1; i>position; i--)
      {
        selectbox.options[i].text = selectbox.options[i-1].text;
        selectbox.options[i].value = selectbox.options[i-1].value;
      }
      
      selectbox.options[position+1] = newentry;
    }
    else selectbox.options[selectbox.length] = newentry;
  }
  else selectbox.options[selectbox.length] = newentry;
}

function moveSelected (select, down)
{
  if (select.selectedIndex != -1)
  {
    if (down)
    {
      if (select.selectedIndex != select.options.length - 1)
        var i = select.selectedIndex + 1;
      else
        return;
    }
    else
    {
      if (select.selectedIndex != 0)
        var i = select.selectedIndex - 1;
      else
        return;
    }

    var swapOption = new Object();

    swapOption.text = select.options[select.selectedIndex].text;
    swapOption.value = select.options[select.selectedIndex].value;
    swapOption.selected = select.options[select.selectedIndex].selected;

    for (var property in swapOption) select.options[select.selectedIndex][property] = select.options[i][property];
    for (var property in swapOption) select.options[i][property] = swapOption[property];
  }
}

function deleteSelected (select)
{
  if (select.length > 0)
  {
    for(var i=0; i<select.length; i++)
    {
      if (select.options[i].selected == true) select.remove(i);
    }
  }
}

function selectAllOptions (select)
{
  for (var i=0; i<select.options.length; i++)
  {
    select.options[i].selected = true;
  }
}

function submitHomeBoxes ()
{
  var form = document.forms['box_form'];
  var select = form.elements['box_array'];
  var homeboxes = "";

  if (select.options.length > 0)
  {
    for(var i=0; i<select.options.length; i++)
    {
      homeboxes = homeboxes + select.options[i].value + "|";
    }
  }

  form.elements['homeboxes'].value = homeboxes;
  form.submit();
}

function setwallpaper ()
{
  <?php if (!empty ($wallpaper) && is_image ($wallpaper)) { ?>
  document.body.style.backgroundImage = "url('<?php echo $wallpaper; ?>')";
  return true;
  <?php } elseif (!empty ($wallpaper) && is_video ($wallpaper)) { ?>
  if (hcms_html5file())
  {
    document.getElementById('videoScreen').src = "<?php echo $wallpaper; ?>";
  }
  return true;
  <?php } else { ?>
  return false;
  <?php } ?>
}
</script>
</head>

<body class="hcmsStartScreen" onload="<?php if (empty ($_SESSION['hcms_temp_latitude']) || empty ($_SESSION['hcms_temp_longitude'])) echo "hcms_geolocation(); "; ?>setwallpaper();">

<?php if (!empty ($wallpaper) && is_video ($wallpaper)) { ?>
<video playsinline autoplay muted loop poster="<?php echo getthemelocation(); ?>/img/backgrd_start.png" id="videoScreen">
  <source src="<?php echo $wallpaper; ?>" type="video/mp4">
</video>
<?php } ?>

<div style="width:100%; height:100%; overflow:auto;">

  <!-- logo -->
  <div id="logo" style="position:fixed; top:10px; left:10px;">
    <img src="<?php echo getthemelocation(); ?>img/logo_server.png" style="width:<?php if ($is_mobile) echo "320px"; else echo "420px"; ?>" />
  </div>
  
  <!-- plus/minus button -->
  <?php if (!$is_mobile) { ?>
  <div id="plusminus" style="position:fixed; top:5px; right:25px; z-index:200;">
    <img id="button_plusminus" onClick="hcms_switchInfo('menubox');" class="hcmsButton" style="width:43px; height:22px;" src="<?php echo getthemelocation(); ?>img/button_plusminus.png" alt="+/-" title="+/-" />
  </div>
  <?php } ?>
  
  <!-- add / remove boxes menu -->
  <div id="menubox" class="hcmsHomeBox" style="position:fixed; top:32px; right:25px; z-index:200; display:none;">
    <form id="box_form" name="box_form" action="" method="post">
      <input type="hidden" name="action" value="save" />
      <input type="hidden" name="homeboxes" value="" />
      
      <table border="0" cellspacing="4" cellpadding="0">
        <tr>
          <td valign="top" align="left">
            <span class="hcmsHeadline" style="padding:3px 0px 3px 0px; display:block;"><?php echo getescapedtext ($hcms_lang['select-object'][$lang]); ?></span>
            <?php
            // display all home boxes for selection          
            $boxes_dir = $mgmt_config['abs_path_cms']."box/";
            
            if ($handle = opendir ($boxes_dir))
            {
              $select_array = array();

              while (false !== ($entry = readdir ($handle)))
              {
                if (is_file ($boxes_dir.$entry) && substr ($entry, -8) == ".inc.php")
                {
                  $box = str_replace (".inc.php", "", $entry);
                  $name = ucfirst (str_replace ("_", " ", $box));
                  
                  $select_array[$box] = "
                <div onclick=\"insertOption('".$name."', '".$box."');\" style=\"display:block; cursor:pointer;\" title=\"".$name."\"><img src=\"".getthemelocation()."img/log_info.png\" align=\"absmiddle\" class=\"hcmsIconList\" />&nbsp;".showshorttext($name, 30)."&nbsp;</div>";
                }
              }

              if (sizeof ($select_array) > 0)
              {
                ksort ($select_array);
                reset ($select_array);
                foreach ($select_array as $select) echo $select;
              }

              closedir ($handle);
            }
            ?>
          </td>
          <td valign="top" align="left">
            <span class="hcmsHeadline" style="padding:3px 0px 3px 0px; display:block;"><?php echo getescapedtext ($hcms_lang['selected-object'][$lang]); ?></span>
            <select id="box_array" name="box_array" size="8" style="width:250px;">
              <?php
              // get boxes of user
              $box_array = getboxes ($user);
              
              // set default boxes
              if (!is_array ($box_array) && !empty ($mgmt_config['homeboxes']))
              {
                $box_array = explode (";", trim ($mgmt_config['homeboxes'], ";"));
              }
                        
              if (is_array ($box_array) && sizeof ($box_array) > 0)
              {
                foreach ($box_array as $box)
                {
                  if ($box != "")
                  {
                    $name = ucfirst (str_replace ("_", " ", $box));
                    echo "<option value=\"".$box."\">".showshorttext($name, 40)."</option>\n";
                  }
                }
              }
              ?>
            </select>
          </td>
          <td align="left" valign="middle">
            <img onClick="moveSelected(document.forms['box_form'].elements['box_array'], false)" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonUp" src="<?php echo getthemelocation(); ?>img/button_moveup.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['move-up'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['move-up'][$lang]); ?>" /><br />                     
            <img onClick="deleteSelected(document.forms['box_form'].elements['box_array'])" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDelete" src="<?php echo getthemelocation(); ?>img/button_delete.png" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" alt="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['delete'][$lang]); ?>" /><br />            
            <img onClick="moveSelected(document.forms['box_form'].elements['box_array'], true)" class="hcmsButtonTiny hcmsButtonSizeSquare" name="ButtonDown" src="<?php echo getthemelocation(); ?>img/button_movedown.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" alt="<?php echo getescapedtext ($hcms_lang['move-down'][$lang]); ?>" title="<?php echo getescapedtext ($hcms_lang['move-down'][$lang]); ?>" /><br />
            <img onclick="submitHomeBoxes();" align="absmiddle" name="Button" src="<?php echo getthemelocation(); ?>img/button_ok.png" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button','','<?php echo getthemelocation(); ?>img/button_ok_over.png',1)" alt="OK" title="OK" />
           </td>
        </tr>
      </table>
    </form>
  </div>
  
  <div id="spacer" style="width:94%; height:32px; display:block;"></div>

  <!-- show boxes -->
  <?php 
  if (!empty ($box_array) && is_array ($box_array))
  { 
    // remove duplicates
    $box_array = array_unique ($box_array);
  
    // show boxes
    foreach ($box_array as $box)
    {
      if ($box != "" && valid_objectname ($box) && is_file ($mgmt_config['abs_path_cms']."box/".$box.".inc.php"))
      {
        include ($mgmt_config['abs_path_cms']."box/".$box.".inc.php");
      }
    }
  }
  ?>

</div>

</body>
</html>