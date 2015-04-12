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
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);
if ($ownergroup == false || $setlocalpermission['root'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// get name 
$object_info = getobjectinfo ($site, $location, $page, $user);
$pagename = $object_info['name'];
$mediafile = $object_info['media'];
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta http-equiv="Content-Type" content="text/html; charset=<?php echo getcodepage ($lang); ?>">
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script src="javascript/main.js" type="text/javascript"></script>
<script src="javascript/click.js" type="text/javascript"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
echo showtopbar ($hcms_lang['meta-information-of-'][$lang]." ".$pagename, $lang, $mgmt_config['url_path_cms']."page_info.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode($location_esc)."&page=".url_encode($page), "objFrame");
?>

<!-- content -->
  <?php
  if ($mediafile != "")
  {
    $mediadir = getmedialocation ($site, $mediafile, "abs_path_media").$site."/";
    
    // EXIFTOOL
    $exiftool = false;
    
    // get file info
    $file_info = getfileinfo ($site, $mediafile, "comp");
    
    // define executable
    foreach ($mgmt_mediametadata as $extensions => $executable)
    {
      if (substr_count ($extensions.".", $file_info['ext'].".") > 0)
      {
        $metadata_array = extractmetadata ($mediadir.$mediafile);

        if (is_array ($metadata_array)) echo showmetadata ($metadata_array, $lang, "hcmsRowHead2");
        else echo "&nbsp;".$hcms_lang['no-meta-inforamtion-available'][$lang]."\n";
        
        $exiftool = true;
        break;
      }
    }

    // EXIFTOOL is not available
    if ($exiftool == false)
    {
      // EXIF
      echo "<div class=\"hcmsRowHead2\" style=\"width:100%;\"><div class=\"hcmsHeadline\">EXIF</div></div>\n";
      $exif = exif_getdata ($mediadir.$mediafile);
      
      if (is_array ($exif)) echo showmetadata ($exif);
      else echo "&nbsp;".$hcms_lang['no-meta-inforamtion-available'][$lang]."\n";
      
      // IPTC
      echo "<div class=\"hcmsRowHead2\" style=\"width:100%;\"><div class=\"hcmsHeadline\">IPTC</div></div>\n";
      $iptc = iptc_getdata ($mediadir.$mediafile);
      
      if (is_array ($iptc)) echo showmetadata ($iptc);
      else echo "&nbsp;".$hcms_lang['no-meta-inforamtion-available'][$lang]."\n";
      
      // XMP
      echo "<div class=\"hcmsRowHead2\" style=\"width:100%;\"><div class=\"hcmsHeadline\">XMP</div></div>\n";
      $xmp = xmp_getdata ($mediadir.$mediafile);
  
      if (is_array ($xmp)) echo showmetadata ($xmp);
      else echo "&nbsp;".$hcms_lang['no-meta-inforamtion-available'][$lang]."\n";
    }
  }
  else
  {
    echo $hcms_lang['no-meta-inforamtion-available'][$lang];
  } 
  ?>

</body>
</html>
