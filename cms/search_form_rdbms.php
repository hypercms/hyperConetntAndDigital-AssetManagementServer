<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */

// session parameters
require ("include/session.inc.php");
// management configuration
require ("config.inc.php");
// hyperCMS API
require ("function/hypercms_api.inc.php");
// hyperCMS UI
require ("function/hypercms_ui.inc.php");
// load formats/file extensions
require_once ("include/format_ext.inc.php");
// language file
require_once ("language/search_form.inc.php");


// input parameters
$location = getrequest_esc ("location", "locationname");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  

if (!valid_publicationname ($site) || !valid_locationname ($location)) killsession ($user);
// check session of user
checkusersession ($user);

// --------------------------------- logic section ----------------------------------
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/jquery/jquery-1.9.1.min.js"></script>
<script src="https://maps.googleapis.com/maps/api/js?sensor=false"></script>
<script language="JavaScript">
<!--
function hidefields (select)
{
  if (select.elements['category'].value != "text")
  {
    select.elements['artid'].value = "";
    select.elements['artid'].disabled = true;
    select.elements['artid'].className = "hcmsWorkplaceGeneric";
    
    select.elements['id'].value = "";
    select.elements['id'].disabled = true;
    select.elements['id'].className = "hcmsWorkplaceGeneric";
  }
  else
  {
    select.elements['artid'].disabled = false;
    select.elements['artid'].style.background = "white";
    
    select.elements['id.disabled'] = false;
    select.elements['id'].style.background = "white";
  }
}

function checkForm(select)
{
  if (select.elements['search_expression'].value == "")
  {
    alert (hcms_entity_decode("<?php echo $text0[$lang]; ?>"));
    select.elements['search_expression'].focus();
    return false;
  }
  
  select.submit();
}

function checkDate(select, min, max) 
{
  var errors='';
  
  val = select.value;

  if (val<min || max<val) errors+='<?php echo $text20[$lang]; ?> '+min+' <?php echo $text21[$lang]; ?> '+max+' <?php echo $text22[$lang]; ?>.\n';
  
  if (errors) 
  {
    select.focus();
    alert (hcms_entity_decode('<?php echo $text23[$lang]; ?>:\n'+errors));
  }
  else
  {
    if (max > 31)
    {
      if (val.length == 1) select.value = '190'+val;
      else if (val.length == 2) select.value = '19';
      else if (val.length == 3) select.value = '1';
    }
    else
    {
      if (val.length == 1) select.value = '0'+val;
      else if (val.length < 1) select.value = '00';
    }    
  }
  
  return false;
}

function loadForm ()
{
  selectbox = document.forms['searchform_advanced'].elements['template'];
  template = selectbox.options[selectbox.selectedIndex].value;  
  hcms_loadPage('contentLayer',null,'search_form_advanced.php?location=<?php echo url_encode($location_esc); ?>&template=' + template);
}

function startSearch (form)
{
  if (eval (document.forms[form]))
  {
    parent.parent.frames['controlFrame'].location.href = 'loading.php';
    document.forms[form].submit();
  }
  else return false;
}
//-->
</script>

<style type="text/css">
#map
{
  width: 500px;
  height: 240px;
}
</style>

</head>
<?php
if (!isset ($cat) || $cat == "") $cat = getcategory ($site, $location);
if ($cat == "file") $cat = "page";
$searcharea = getlocationname ($site, $location, $cat, "path");

// date
$year = date ("Y", time());
$month = date ("m", time());
$day = date ("d", time());

// define template
if ($cat == "page") $template = "default.page.tpl";
elseif ($cat == "comp") $template = "default.meta.tpl";
elseif ($cat == "comp") $template = "default.comp.tpl";
?>
<body class="hcmsWorkplaceGeneric" onLoad="hcms_loadPage('contentLayer',null,'search_form_advanced.php?<?php echo "location=".url_encode($location_esc)."&template=".url_encode($template); ?>')" leftmargin=5 topmargin=5 marginwidth=0 marginheight=0>

<!-- top bar -->
<?php echo showtopbar ($text28[$lang], $lang, $mgmt_config['url_path_cms']."explorer_objectlist.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc), "_parent"); ?>

<div id="Layer_menu" style="position:absolute; width:500px; height:22px; visibility: visible; z-index:1; left: 5px; top: 35px;">
  <table border=0 cellspacing=0 cellpadding=0 height=22>
    <tr align="left" valign="top"> 
      <td align="left" valign="top" class="hcmsTab">
        &nbsp;<a href="#" onClick="hcms_showHideLayers('Layer_tab1','','show','Layer_tab2','','hide','Layer_tab3','','hide','Layer_tab4','','hide','searchtab_general','','show','searchtab_advanced','','hide','contentLayer','','hide','searchtab_replace','','hide','searchtab_images','','hide')"><?php echo $text29[$lang]; ?></a>
      </td>
      <td width="3"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:20px; border:0;" /></td>
      <td align="left" valign="top" class="hcmsTab">
        &nbsp;<a href="#" onClick="hcms_showHideLayers('Layer_tab1','','hide','Layer_tab2','','show','Layer_tab3','','hide','Layer_tab4','','hide','searchtab_general','','hide','searchtab_advanced','','show','contentLayer','','show','searchtab_replace','','hide','searchtab_images','','hide')"><?php echo $text30[$lang]; ?></a>
      </td>
      <?php if ($setlocalpermission['create'] == 1) { ?>
      <td width="3"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:20px; border:0;" /></td>
      <td align="left" valign="top" class="hcmsTab">
        &nbsp;<a href="#" onClick="hcms_showHideLayers('Layer_tab1','','hide','Layer_tab2','','hide','Layer_tab3','','show','Layer_tab4','','hide','searchtab_general','','hide','searchtab_advanced','','hide','contentLayer','','hide','searchtab_replace','','show','searchtab_images','','hide')"><?php echo $text31[$lang]; ?></a>
      </td>
      <?php } ?> 
      <?php if ($cat == "comp") { ?>
      <td width="3"><img src="<?php echo getthemelocation(); ?>img/backgrd_tabs_spacer.gif" style="width:3px; height:20px; border:0;" /></td>
      <td align="left" valign="top" class="hcmsTab">
        &nbsp;<a href="#" onClick="hcms_showHideLayers('Layer_tab1','','hide','Layer_tab2','','hide','Layer_tab3','','hide','Layer_tab4','','show','searchtab_general','','hide','searchtab_advanced','','hide','contentLayer','','hide','searchtab_replace','','hide','searchtab_images','','show')"><?php echo $text70[$lang]; ?></a>
      </td>
      <?php } ?>          
    </tr>
  </table>
</div>

<div id="Layer_tab1" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; visibility:visible; z-index:5; left:6px; top:57px;"> </div> 

<div id="Layer_tab2" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; visibility:hidden; z-index:6; left:129px; top:57px;"> </div> 

<div id="Layer_tab3" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; visibility:hidden; z-index:7; left:252px; top:57px;"> </div> 

<?php if ($cat == "comp") { ?>
<div id="Layer_tab4" class="hcmsWorkplaceGeneric" style="position:absolute; width:118px; height:2px; visibility:hidden; z-index:7; left:375px; top:57px;"> </div> 
<?php } ?>

<div id="searchtab_general" style="position:absolute; width:520px; height:360px; z-index:2; left:5px; top:57px; visibility:visible;"> 
  <form name="searchform_general" method="post" action="search_script_rdbms.php" target="_parent">
    <input type="hidden" name="search_dir" value="<?php echo $location_esc; ?>" />
     
    <table border=0 cellspacing=1 cellpadding=2 width="100%" height="100%" bgcolor="#000000">
      <tr class="hcmsWorkplaceGeneric"> 
        <td valign="top"> 
          <table border="0" cellspacing="0" cellpadding="3" width="100%">
            <tr align="left" valign="middle" class="hcmsWorkplaceExplorer">
              <td colspan="2" class="hcmsHeadlineTiny"><?php echo $text32[$lang]; ?></td>
            </tr>          
            <tr align="left" valign="top">
              <td width="180" nowrap="nowrap"><?php echo $text1[$lang]; ?>:</td>
              <td>
                <input type="text" name="search_expression" style="width:200px;" maxlength="60" />
              </td>
            </tr>
            <tr align="left" valign="top">
              <td nowrap="nowrap"><?php echo $text2[$lang]; ?>:</td>
              <td>
                <input type="text" name="folder" value="<?php echo $searcharea; ?>" style="width:200px;" disabled="disabled" />
              </td>
            </tr>
            <tr align="left" valign="top">
              <td nowrap="nowrap"><?php echo $text3[$lang]; ?>:</td>
              <td class="hcmsHeadlineTiny">
                <input type="checkbox" name="search_cat" value="file" /> <?php echo $text6[$lang]; ?>
                <?php if ($cat == "page") { ?><input type="hidden" name="search_format[object]" value="page" /><?php } ?>
              </td>
            </tr>
            <?php if ($cat == "comp") { ?>
            <tr id="row_searchformat" align="left" valign="top">
              <td><?php echo $text4[$lang]; ?>:</td>
              <td>
                <input type="checkbox" name="search_format[object]" value="comp" checked="checked" /><?php echo $text8[$lang]; ?><br />
                <input type="checkbox" name="search_format[image]" value="image" checked="checked" /><?php echo $text43[$lang]; ?><br />
                <input type="checkbox" name="search_format[document]" value="document" checked="checked" /><?php echo $text44[$lang]; ?><br />
                <input type="checkbox" name="search_format[video]" value="video" checked="checked" /><?php echo $text45[$lang]; ?><br />
                <input type="checkbox" name="search_format[audio]" value="audio" checked="checked" /><?php echo $text46[$lang]; ?><br />
              </td>
            </tr>          
            <?php } ?>
            <tr align="left" valign="top">
              <td nowrap="nowrap"><input type="checkbox" name="date_modified" value="yes">&nbsp;<?php echo $text17[$lang]; ?>:</td>
              <td>
                <table border="0" cellspacing="0" cellpadding="0">     
                  <tr>
                    <td> 
                    <?php echo $text18[$lang]; ?>:&nbsp;&nbsp;
                    </td>
                    <td class="hcmsHeadlineTiny">
                    <?php echo $text14[$lang]; ?>
                    <input type="text" name="year_from" value="<?php echo $year; ?>" size="4" maxlength="4" onBlur="checkDate(document.forms['searchform_general'].elements['year_from'],1000,9000); return document.returnValue;">
                    <?php echo $text15[$lang]; ?>
                    <input type="text" name="month_from" value="<?php echo $month; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_general'].elements['month_from'],1,12); return document.returnValue;">
                    <?php echo $text16[$lang]; ?>
                    <input type="text" name="day_from" value="<?php echo $day; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_general'].elements['day_from'],1,31); return document.returnValue;"><br />
                    </td>
                  </tr>
                  <tr>
                    <td>
                    <?php echo $text19[$lang]; ?>:&nbsp;&nbsp; 
                    </td>
                    <td class="hcmsHeadlineTiny">
                    <?php echo $text14[$lang]; ?>
                    <input type="text" name="year_to" value="<?php echo $year; ?>" size="4" maxlength="4" onBlur="checkDate(document.forms['searchform_general'].elements['year_to'],1000,9000); return document.returnValue;">
                    <?php echo $text15[$lang]; ?>
                    <input type="text" name="month_to" value="<?php echo $month; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_general'].elements['month_to'],1,12); return document.returnValue;">
                    <?php echo $text16[$lang]; ?>
                    <input type="text" name="day_to" value="<?php echo $day; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_general'].elements['day_to'],1,31); return document.returnValue;">        
                    </td>
                  </tr>
                </table>
              </td>
            </tr>
            <tr align="left" valign="middle">
              <td nowrap="nowrap" colspan="2">
                <script type="text/javascript">
                <!--
                // Google Maps JavaScript API v3: Map Simple
                var map;
                var markers = {};
                var bounds = null;
                // add markers to map
                /*
                var name = 'Object name';
                markers[name] = {};
                markers[name].id = 1;
                markers[name].lat = 53.801279;
                markers[name].lng = -1.548567;
                markers[name].state = 'Online';
                markers[name].position = new google.maps.LatLng(0, 0);
                markers[name].selected = false;
                */
                
                $(document).ready(function ()
                {
                  var mapOptions = {
                      zoom: 1,
                      center: new google.maps.LatLng(0, 0),
                      mapTypeId: google.maps.MapTypeId.ROADMAP
                  };
                  
                  map = new google.maps.Map(document.getElementById('map'), mapOptions);
                  var infowindow = new google.maps.InfoWindow();
                  
                  // set markers on map
                  if (markers)
                  {
                    for (var key in markers)
                    {
                      var marker = new google.maps.Marker({
                          position: new google.maps.LatLng(markers[key].lat, markers[key].lng),
                          map: map
                      });
                      
                      markers[key].marker = marker;
                  
                      google.maps.event.addListener(marker, 'click', (function (marker, key)
                      {
                        return function ()
                        {
                          infowindow.setContent(key);
                          infowindow.open(map, marker);
                        }
                      })(marker, key));
                    }
                  }
                
                  // start drag rectangle to select markers
                  var shiftPressed = false;
                
                  $(window).keydown(function (evt)
                  {
                    if (evt.which === 16) shiftPressed = true;
                  }).keyup(function (evt)
                  {
                    if (evt.which === 16) shiftPressed = false;
                  });
                
                  var mouseDownPos, gribBoundingBox = null,
                      mouseIsDown = 0;
                  var themap = map;
                
                  google.maps.event.addListener(themap, 'mousemove', function (e)
                  {
                    if (mouseIsDown && shiftPressed)
                    {
                      // box exists
                      if (gribBoundingBox !== null)
                      {
                        bounds.extend(e.latLng);
                        // if this statement is enabled, you lose mouseUp events           
                        gribBoundingBox.setBounds(bounds);
                      }
                      // create bounding box
                      else
                      {
                        bounds = new google.maps.LatLngBounds();
                        bounds.extend(e.latLng);
                        gribBoundingBox = new google.maps.Rectangle({
                            map: themap,
                            bounds: bounds,
                            fillOpacity: 0.15,
                            strokeWeight: 0.9,
                            clickable: false
                        });
                      }
                    }
                  });
                
                  google.maps.event.addListener(themap, 'mousedown', function (e)
                  {
                    if (shiftPressed)
                    {
                      mouseIsDown = 1;
                      mouseDownPos = e.latLng;
                      themap.setOptions({
                          draggable: false
                      });
                    }
                  });
                
                  google.maps.event.addListener(themap, 'mouseup', function (e)
                  {
                    if (mouseIsDown && shiftPressed)
                    {
                      mouseIsDown = 0;
                      
                      // box exists
                      if (gribBoundingBox !== null)
                      {
                        var boundsSelectionArea = new google.maps.LatLngBounds(gribBoundingBox.getBounds().getSouthWest(), gribBoundingBox.getBounds().getNorthEast());                
                        var borderSW = gribBoundingBox.getBounds().getSouthWest();
                        var borderNE = gribBoundingBox.getBounds().getNorthEast();
                        
                        document.forms['searchform_general'].elements['geo_border_sw'].value = borderSW;
                        document.forms['searchform_general'].elements['geo_border_ne'].value = borderNE;
                        
                        // looping through markers collection (if set)
                        if (markers)
                        {
                          for (var key in markers)
                          {
                            if (gribBoundingBox.getBounds().contains(markers[key].marker.getPosition())) 
                            {
                              markers[key].marker.setIcon("http://maps.google.com/mapfiles/ms/icons/blue.png")
                            }
                            else
                            {
                              markers[key].marker.setIcon("http://maps.google.com/mapfiles/ms/icons/red.png")
                            }
                          }
                        }
                        
                        // remove the rectangle
                        gribBoundingBox.setMap(null); 
                      }
                      
                      gribBoundingBox = null;
                    }
                
                    themap.setOptions({
                        draggable: true
                    });
                  });
                });
                </script>
                
                <?php echo $text74[$lang]; ?>:<br />
                <span class="hcmsHeadlineTiny"><?php echo $text75[$lang]; ?></span>
                <div id="map" style="border:1px solid grey; margin-top:4px;"></div>         
              </td>
            </tr>
            <tr align="left" valign="middle">
              <td nowrap="nowrap"><?php echo $text76[$lang]; ?>:</td>
              <td><input type="text" name="geo_border_sw" style="width:200px;" maxlength="100" /></td>
            </tr>
            <tr align="left" valign="middle">
              <td nowrap="nowrap"><?php echo $text77[$lang]; ?>:</td>
              <td><input type="text" name="geo_border_ne" style="width:200px;" maxlength="100" /></td>
            </tr>
            <tr align="left" valign="middle">
              <td nowrap="nowrap"><?php echo $text24[$lang]; ?>:</td>
              <td>
                <select name="maxhits">
                  <option value="100" selected="selected">100</option>
                  <option value="200">200</option>
                  <option value="300">300</option>
                  <option value="400">400</option>
                  <option value="500">500</option>
                  <option value="1000">1000</option>
                </select>
              </td>
            </tr>
            <tr align="left" valign="middle">
              <td nowrap="nowrap"><?php echo $text41[$lang]; ?>:</td>
              <td>
          			<img name="Button1" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="startSearch('searchform_general');" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
              </td>
            </tr>			
          </table>
        </td>
      </tr>
    </table>    
  </form>
</div>

<div id="searchtab_advanced" style="position:absolute; width:520px; height:360px; z-index:3; left:5px; top:57px; visibility:hidden;"> 
  <form name="searchform_advanced" method="post" action="search_script_rdbms.php" target="_parent">
    <input type="hidden" name="search_dir" value="<?php echo $location_esc; ?>" /> 
    
    <table border=0 cellspacing=1 cellpadding=2 width="100%" height="100%" bgcolor="#000000">
      <tr class="hcmsWorkplaceGeneric"> 
        <td valign="top"> 
          <table border="0" cellspacing="0" cellpadding="3" width="100%">
            <tr align="left" valign="middle" class="hcmsWorkplaceExplorer"> 
              <td colspan="2" class="hcmsHeadlineTiny"><?php echo $text33[$lang]; ?></td>
            </tr>
            <tr align="left" valign="middle"> 
              <td width="180" nowrap="nowrap"><?php echo $text37[$lang]; ?>:</td>
              <td>
                <select name="template" onChange="loadForm();">
              <?php
              // load publication inheritance setting
              if ($mgmt_config[$site]['inherit_tpl'] == true)
              {
                $inherit_db = inherit_db_read ();
                $site_array = inherit_db_getparent ($inherit_db, $site);
                
                // add own publication
                $site_array[] = $site;
              }
              else $site_array[] = $site;
              
              foreach ($site_array as $site_source)
              {
                $dir_template = dir ($mgmt_config['abs_path_template'].$site_source."/");
      
                if ($dir_template != false)
                {
                  while ($entry = $dir_template->read())
                  {
                    if ($entry != "." && $entry != ".." && !is_dir ($entry) && !preg_match ("/.inc.tpl/", $entry) && !preg_match ("/.tpl.v_/", $entry))
                    {
                      if ($cat == "page" && strpos ($entry, ".page.tpl") > 0)
                      {
                        $template_array[] = $entry;
                      }
                      elseif ($cat == "comp" && strpos ($entry, ".comp.tpl") > 0)
                      {
                        $template_array[] = $entry;
                      }
                      elseif ($cat == "comp" && strpos ($entry, ".meta.tpl") > 0)
                      {
                        $template_array[] = $entry;
                      }                  
                    }
                  }
      
                  $dir_template->close();
                }
              }
    
              if (is_array ($template_array) && sizeof ($template_array) >= 1)
              {
                // remove double entries (double entries due to parent publications won't be listed)
                $template_array = array_unique ($template_array);
                sort ($template_array);
                reset ($template_array);
                
                foreach ($template_array as $value)
                {
                  if (strpos ($value, ".page.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".page.tpl"))." (".$text38[$lang].")";
                  elseif (strpos ($value, ".comp.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".comp.tpl"))." (".$text39[$lang].")";
                  elseif (strpos ($value, ".meta.tpl") > 0) $tpl_name = substr ($value, 0, strpos ($value, ".meta.tpl"))." (".$text40[$lang].")";
                  
                  echo "<option value=\"".$value."\""; if ($value == $template) echo " selected=\"selected\""; echo ">".$tpl_name."</option>\n";
                }
              }
              else 
              {
                echo "<option value=\"\"> ----------------- </option>\n";
              }
              ?>
                </select>
              </td>
            </tr>
            <tr align="left" valign="middle"> 
              <td nowrap="nowrap"><?php echo $text2[$lang]; ?>:</td>
              <td>
                <input type="text" name="folder" value="<?php echo $searcharea; ?>" style="width:200px;" disabled="disabled" /> 
              </td>
            </tr>
			      <tr align="left" valign="middle"> 
              <td nowrap="nowrap"><img src="<?php echo getthemelocation(); ?>img/blank.gif" width=1 height=150 /></td>
              <td>
                <iframe id="contentFRM" name="contentFRM" width="0px" height="0px" frameborder="0"></iframe> 
                <div id="contentLayer" class="hcmsWorkplaceExplorer" style="position:absolute; border:1px solid #000000; width:486px; height:150px; z-index:8; left:6px; top:80px; overflow:auto; visibility:hidden;"></div>              
              </td>			  
            </tr>
            <?php if ($cat == "comp") { ?>
            <tr align="left" valign="top">
              <td nowrap="nowrap"><?php echo $text4[$lang]; ?>:</td>
              <td>
                <input type="checkbox" name="search_format[object]" value="comp" checked="checked" /><?php echo $text8[$lang]; ?><br />
                <input type="checkbox" name="search_format[image]" value="image" checked="checked" /><?php echo $text43[$lang]; ?><br />
                <input type="checkbox" name="search_format[document]" value="document" checked="checked" /><?php echo $text44[$lang]; ?><br />
                <input type="checkbox" name="search_format[video]" value="video" checked="checked" /><?php echo $text45[$lang]; ?><br />
                <input type="checkbox" name="search_format[audio]" value="audio" checked="checked" /><?php echo $text46[$lang]; ?><br />
              </td>
            </tr>
            <?php } ?>  
            <tr align="left" valign="middle"> 
              <td nowrap="nowrap"><?php echo $text72[$lang]; ?>:</td>
              <td>
                <input type="text" name="object_id" value="" style="width:200px;" /> 
              </td>
            </tr>
            <tr align="left" valign="middle"> 
              <td nowrap="nowrap"><?php echo $text73[$lang]; ?>:</td>
              <td>
                <input type="text" name="container_id" value="" style="width:200px;" /> 
              </td>
            </tr>
            <tr align="left" valign="top">
              <td nowrap="nowrap"><input type="checkbox" name="date_modified" value="yes" />&nbsp;<?php echo $text17[$lang]; ?>:</td>
              <td>
                <table border="0" cellspacing="0" cellpadding="0">     
                  <tr>
                    <td> 
                    <?php echo $text18[$lang]; ?>:&nbsp;&nbsp;
                    </td>
                    <td class="hcmsHeadlineTiny">
                    <?php echo $text14[$lang]; ?>
                    <input type="text" name="year_from" value="<?php echo $year; ?>" size="4" maxlength="4" onBlur="checkDate(document.forms['searchform_advanced'].elements['year_from'],1000,9000); return document.returnValue;" />
                    <?php echo $text15[$lang]; ?>
                    <input type="text" name="month_from" value="<?php echo $month; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_advanced'].elements['month_from'],1,12); return document.returnValue;" />
                    <?php echo $text16[$lang]; ?>
                    <input type="text" name="day_from" value="<?php echo $day; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_advanced'].elements['day_from'],1,31); return document.returnValue;" /><br />
                    </td>
                  </tr>
                  <tr>
                    <td>
                    <?php echo $text19[$lang]; ?>:&nbsp;&nbsp; 
                    </td>
                    <td class="hcmsHeadlineTiny">
                    <?php echo $text14[$lang]; ?>
                    <input type="text" name="year_to" value="<?php echo $year; ?>" size="4" maxlength="4" onBlur="checkDate(document.forms['searchform_advanced'].elements['year_to'],1000,9000); return document.returnValue;" />
                    <?php echo $text15[$lang]; ?>
                    <input type="text" name="month_to" value="<?php echo $month; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_advanced'].elements['month_to'],1,12); return document.returnValue;" />
                    <?php echo $text16[$lang]; ?>
                    <input type="text" name="day_to" value="<?php echo $day; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_advanced'].elements['day_to'],1,31); return document.returnValue;" />        
                    </td>
                  </tr>
                </table>
              </td>
            </tr> 
            <tr align="left" valign="middle">
              <td nowrap="nowrap"><?php echo $text24[$lang]; ?>:</td>
              <td>
                <select name="maxhits">
                  <option value="100" selected="selected">100</option>
                  <option value="200">200</option>
                  <option value="300">300</option>
                  <option value="400">400</option>
                  <option value="500">500</option>
                  <option value="1000">1000</option>
                </select>
              </td>
            </tr>            		
            <tr align="left" valign="middle">
              <td nowrap="nowrap"><?php echo $text41[$lang]; ?>:</td>
              <td>
          			<img name="Button2" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="startSearch('searchform_advanced');" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button2','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
              </td>
            </tr>			
          </table>
        </td>
      </tr>
    </table>
  </form>
</div>

<?php if ($setlocalpermission['create'] == 1) { ?>
<div id="searchtab_replace" style="position:absolute; width:520px; height:360px; z-index:4; left:5px; top:57px; visibility:hidden;"> 
  <form name="searchform_replace" method="post" action="search_script_rdbms.php" target="_parent">
    <input type="hidden" name="search_dir" value="<?php echo $location_esc; ?>" />
     
    <table border=0 cellspacing=1 cellpadding=2 width="100%" height="100%" bgcolor="#000000">
      <tr class="hcmsWorkplaceGeneric"> 
        <td valign="top"> 
          <table border="0" cellspacing="0" cellpadding="3" width="100%">
            <tr align="left" valign="middle" class="hcmsWorkplaceExplorer">
              <td colspan="2" class="hcmsHeadlineTiny"><?php echo $text34[$lang]; ?></td>
            </tr>            
            <tr align="left" valign="middle"> 
              <td width="180" nowrap="nowrap"><?php echo $text1[$lang]; ?>:</td>
              <td> 
                <input type="text" name="search_expression" style="width:200px;" />
              </td>
            </tr>
            <tr align="left" valign="middle"> 
              <td nowrap="nowrap"><?php echo $text35[$lang]; ?>:</td>
              <td> 
                <input type="text" name="replace_expression" style="width:200px;" />
              </td>
            </tr>          
            <tr align="left" valign="middle"> 
              <td nowrap="nowrap"><?php echo $text2[$lang]; ?>:</td>
              <td> 
                <input type="text" name="folder" value="<?php echo $searcharea; ?>" style="width:200px;" disabled="disabled" />
              </td>
            </tr>
            <?php if ($cat == "comp") { ?>
            <tr align="left" valign="top">
              <td nowrap="nowrap"><?php echo $text4[$lang]; ?>:</td>
              <td>
                <input type="checkbox" name="search_format[object]" value="comp" checked="checked" /><?php echo $text8[$lang]; ?><br />
                <input type="checkbox" name="search_format[image]" value="image" checked="checked" /><?php echo $text43[$lang]; ?><br />
                <input type="checkbox" name="search_format[document]" value="document" checked="checked" /><?php echo $text44[$lang]; ?><br />
                <input type="checkbox" name="search_format[video]" value="video" checked="checked" /><?php echo $text45[$lang]; ?><br />
                <input type="checkbox" name="search_format[audio]" value="audio" checked="checked" /><?php echo $text46[$lang]; ?><br />
              </td>
            </tr>
            <?php } ?>   
            <tr align="left" valign="top">
              <td nowrap="nowrap"><input type="checkbox" name="date_modified" value="yes" />&nbsp;<?php echo $text17[$lang]; ?>:</td>
              <td>
                <table border="0" cellspacing="0" cellpadding="0">     
                  <tr>
                    <td> 
                    <?php echo $text18[$lang]; ?>:&nbsp;&nbsp;
                    </td>
                    <td class="hcmsHeadlineTiny">
                    <?php echo $text14[$lang]; ?>
                    <input type="text" name="year_from" value="<?php echo $year; ?>" size="4" maxlength="4" onBlur="checkDate(document.forms['searchform_replace'].elements['year_from'],1000,9000); return document.returnValue;" />
                    <?php echo $text15[$lang]; ?>
                    <input type="text" name="month_from" value="<?php echo $month; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_replace'].elements['month_from'],1,12); return document.returnValue;" />
                    <?php echo $text16[$lang]; ?>
                    <input type="text" name="day_from" value="<?php echo $day; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_replace'].elements['day_from'],1,31); return document.returnValue;" /><br />
                    </td>
                  </tr>
                  <tr>
                    <td>
                    <?php echo $text19[$lang]; ?>:&nbsp;&nbsp; 
                    </td>
                    <td class="hcmsHeadlineTiny">
                    <?php echo $text14[$lang]; ?>
                    <input type="text" name="year_to" value="<?php echo $year; ?>" size="4" maxlength="4" onBlur="checkDate(document.forms['searchform_replace'].elements['year_to'],1000,9000); return document.returnValue;" />
                    <?php echo $text15[$lang]; ?>
                    <input type="text" name="month_to" value="<?php echo $month; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_replace'].elements['month_to'],1,12); return document.returnValue;" />
                    <?php echo $text16[$lang]; ?>
                    <input type="text" name="day_to" value="<?php echo $day; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_replace'].elements['day_to'],1,31); return document.returnValue;" />        
                    </td>
                  </tr>
                </table>
              </td>
            </tr>             
            <tr align="left" valign="middle">
              <td nowrap="nowrap"><?php echo $text41[$lang]; ?>:</td>
              <td>
			          <img name="Button3" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="checkForm(document.forms['searchform_replace']);" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button3','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
              </td>
            </tr>		            
            <tr align="left" valign="top"> 
              <td colspan="2"><img src="<?php echo getthemelocation(); ?>img/info.gif" style="float:left;" /><div style="margin-left:4px; float:left;" class="hcmsTextSmall"><?php echo $text36[$lang]; ?></div></td>
            </tr>   	         
          </table>
        </td>
      </tr>
    </table>
  </form>  
</div>
<?php } ?>

<?php if ($cat == "comp") { ?>
<div id="searchtab_images" style="position:absolute; width:520px; height:360px; z-index:2; left:5px; top:57px; visibility:hidden;"> 
  <form name="searchform_images" method="post" action="search_script_rdbms.php" target="_parent">
    <input type="hidden" name="search_dir" value="<?php echo $location_esc; ?>" />
     
    <table border=0 cellspacing=1 cellpadding=2 width="100%" height="100%" bgcolor="#000000">
      <tr class="hcmsWorkplaceGeneric"> 
        <td valign="top"> 
          <table border="0" cellspacing="0" cellpadding="3" width="100%">
            <tr align="left" valign="middle" class="hcmsWorkplaceExplorer">
              <td colspan="2" class="hcmsHeadlineTiny"><?php echo $text71[$lang]; ?></td>
            </tr>          
            <tr align="left" valign="top">
              <td width="180" nowrap="nowrap"><?php echo $text1[$lang]; ?>:</td>
              <td>
                <input type="text" name="search_expression" style="width:200px;" maxlength="60" />
              </td>
            </tr>
            <tr align="left" valign="top">
              <td nowrap="nowrap"><?php echo $text2[$lang]; ?>:</td>
              <td>
                <input type="text" name="folder" value="<?php echo $searcharea; ?>" style="width:200px;" disabled="disabled" />
              </td>
            </tr>
            <tr align="left" valign="top">
              <td nowrap="nowrap"><?php echo $text3[$lang]; ?>:</td>
              <td class="hcmsHeadlineTiny">
                <input type="checkbox" name="search_cat" value="file" /> <?php echo $text6[$lang]; ?>
              </td>
            </tr>                     
            <tr id="row_imagesize" align="left" valign="top">
              <td nowrap="nowrap"><?php echo $text47[$lang]; ?>:</td>
              <td><input type="hidden" name="search_format[image]" value="image" />
                <select name="search_imagesize" style="width:140px;" onchange="if (this.options[this.selectedIndex].value=='exact') document.getElementById('searchfield_imagesize').style.display='inline'; else document.getElementById('searchfield_imagesize').style.display='none';">
                  <option value="" selected="selected"><?php echo $text5[$lang]; ?></option>
                  <option value="1024-9000000"><?php echo $text51[$lang]; ?></option>
                  <option value="640-1024"><?php echo $text52[$lang]; ?></option>
                  <option value="0-640"><?php echo $text53[$lang]; ?></option>
                  <option value="exact"><?php echo $text54[$lang]; ?></option>
                </select>
                <div id="searchfield_imagesize" style="display:none;">
                  <input type="text" name="search_imagewidth" style="width:40px;" maxlength="8" /> x 
                  <input type="text" name="search_imageheight" style="width:40px;" maxlength="8" /> px
                </div>
              </td>
            </tr>
            <tr id="row_imagecolor" align="left" valign="top">
              <td nowrap="nowrap"><?php echo $text48[$lang]; ?>:</td>
              <td>
                <div style="width:320px; margin:1px; padding:0; float:left;"><div style="float:left;"><input style="margin:2px; padding:0;" type="radio" name="search_imagecolor" value="" checked="checked" /></div>&nbsp;<?php echo $text5[$lang]; ?></div>
                <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#000000; float:left;"><input style="margin:2px; padding:0;" type="radio" name="search_imagecolor" value="K" /></div>&nbsp;<?php echo $text55[$lang]; ?></div>
                <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#FFFFFF; float:left;"><input style="margin:2px; padding:0;" type="radio" name="search_imagecolor" value="W" /></div>&nbsp;<?php echo $text56[$lang]; ?></div>
                <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#808080; float:left;"><input style="margin:2px; padding:0;" type="radio" name="search_imagecolor" value="E" /></div>&nbsp;<?php echo $text57[$lang]; ?></div>
                <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#FF0000; float:left;"><input style="margin:2px; padding:0;" type="radio" name="search_imagecolor" value="R" /></div>&nbsp;<?php echo $text58[$lang]; ?></div>
                <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#00C000; float:left;"><input style="margin:2px; padding:0;" type="radio" name="search_imagecolor" value="G" /></div>&nbsp;<?php echo $text59[$lang]; ?></div>
                <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#0000FF; float:left;"><input style="margin:2px; padding:0;" type="radio" name="search_imagecolor" value="B" /></div>&nbsp;<?php echo $text60[$lang]; ?></div>
                <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#00FFFF; float:left;"><input style="margin:2px; padding:0;" type="radio" name="search_imagecolor" value="C" /></div>&nbsp;<?php echo $text61[$lang]; ?></div>
                <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#FF0090; float:left;"><input style="margin:2px; padding:0;" type="radio" name="search_imagecolor" value="M" /></div>&nbsp;<?php echo $text62[$lang]; ?></div>
                <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#FFFF00; float:left;"><input style="margin:2px; padding:0;" type="radio" name="search_imagecolor" value="Y" /></div>&nbsp;<?php echo $text63[$lang]; ?></div>
                <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#FF8A00; float:left;"><input style="margin:2px; padding:0;" type="radio" name="search_imagecolor" value="O" /></div>&nbsp;<?php echo $text64[$lang]; ?></div>
                <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#FFCCDD; float:left;"><input style="margin:2px; padding:0;" type="radio" name="search_imagecolor" value="P" /></div>&nbsp;<?php echo $text65[$lang]; ?></div>
                <div style="width:85px; margin:1px; padding:0; float:left;"><div style="border:1px solid #999999; background:#A66500; float:left;"><input style="margin:2px; padding:0;" type="radio" name="search_imagecolor" value="N" /></div>&nbsp;<?php echo $text66[$lang]; ?></div>
              </td>
            </tr>
            <tr id="row_imagetype" align="left" valign="top">
              <td nowrap="nowrap"><?php echo $text49[$lang]; ?>:</td>
              <td>
                <select name="search_imagetype" style="width:140px;">
                  <option value="" selected="selected"><?php echo $text5[$lang]; ?></option>
                  <option value="landscape"><?php echo $text67[$lang]; ?></option>
                  <option value="portrait"><?php echo $text68[$lang]; ?></option>
                  <option value="square"><?php echo $text69[$lang]; ?></option>
                </select>
              </td>
            </tr>            
            <tr align="left" valign="top">
              <td nowrap="nowrap"><input type="checkbox" name="date_modified" value="yes">&nbsp;<?php echo $text17[$lang]; ?>:</td>
              <td>
                <table border="0" cellspacing="0" cellpadding="0">     
                  <tr>
                    <td> 
                    <?php echo $text18[$lang]; ?>:&nbsp;&nbsp;
                    </td>
                    <td class="hcmsHeadlineTiny">
                    <?php echo $text14[$lang]; ?>
                    <input type="text" name="year_from" value="<?php echo $year; ?>" size="4" maxlength="4" onBlur="checkDate(document.forms['searchform_general'].elements['year_from'],1000,9000); return document.returnValue;">
                    <?php echo $text15[$lang]; ?>
                    <input type="text" name="month_from" value="<?php echo $month; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_general'].elements['month_from'],1,12); return document.returnValue;">
                    <?php echo $text16[$lang]; ?>
                    <input type="text" name="day_from" value="<?php echo $day; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_general'].elements['day_from'],1,31); return document.returnValue;"><br />
                    </td>
                  </tr>
                  <tr>
                    <td>
                    <?php echo $text19[$lang]; ?>:&nbsp;&nbsp; 
                    </td>
                    <td class="hcmsHeadlineTiny">
                    <?php echo $text14[$lang]; ?>
                    <input type="text" name="year_to" value="<?php echo $year; ?>" size="4" maxlength="4" onBlur="checkDate(document.forms['searchform_general'].elements['year_to'],1000,9000); return document.returnValue;">
                    <?php echo $text15[$lang]; ?>
                    <input type="text" name="month_to" value="<?php echo $month; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_general'].elements['month_to'],1,12); return document.returnValue;">
                    <?php echo $text16[$lang]; ?>
                    <input type="text" name="day_to" value="<?php echo $day; ?>" size="2" maxlength="2" onBlur="checkDate(document.forms['searchform_general'].elements['day_to'],1,31); return document.returnValue;">        
                    </td>
                  </tr>
                </table>
              </td>
            </tr> 
            <tr align="left" valign="middle">
              <td nowrap="nowrap"><?php echo $text24[$lang]; ?>:</td>
              <td>
                <select name="maxhits">
                  <option value="100" selected="selected">100</option>
                  <option value="200">200</option>
                  <option value="300">300</option>
                  <option value="400">400</option>
                  <option value="500">500</option>
                  <option value="1000">1000</option>
                </select>
              </td>
            </tr>
            <tr align="left" valign="middle">
              <td nowrap="nowrap"><?php echo $text41[$lang]; ?>:</td>
              <td>
          			<img name="Button1" src="<?php echo getthemelocation(); ?>img/button_OK.gif" class="hcmsButtonTinyBlank hcmsButtonSizeSquare" onclick="startSearch('searchform_images');" onMouseOut="hcms_swapImgRestore()" onMouseOver="hcms_swapImage('Button1','','<?php echo getthemelocation(); ?>img/button_OK_over.gif',1)" align="absmiddle" title="OK" alt="OK" />
              </td>
            </tr>			
          </table>
        </td>
      </tr>
    </table>    
  </form>
</div>
<?php } ?>

</body>
</html>
