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
$ip = getrequest ("ip");

// ------------------------------ permission section --------------------------------

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

if ($ip != "")
{
  // timeout after 5 seconds
  ini_set ("default_socket_timeout", 5);
  // use external service
  $json = file_get_contents ("http://ip-api.com/json/".$ip);
  if (!empty ($json)) $data = json_decode ($json, true);
}
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<link rel="stylesheet" href="<?php echo getthemelocation()."css/".($is_mobile ? "mobile.css" : "desktop.css"); ?>" />
<script type="text/javascript" src="javascript/main.min.js"></script>
<script type="text/javascript" src="javascript/click.min.js"></script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
echo showtopbar ($hcms_lang['geo-location-of'][$lang]." ".$ip, $lang);
?>

<!-- content -->
<div class="hcmsWorkplaceFrame">
  <?php
  if (!empty ($data) && is_array ($data)) 
  {
  ?>
  <iframe frameborder="0" scrolling="no" style="width:620px; height:400px; margin:10px; border:0; overflow:hidden;" src="https://maps.google.de/maps?ll=<?php echo @$data['lat']; ?>,<?php echo @$data['lon']; ?>&amp;ie=UTF8&amp;om=1&amp;iwloc=near&amp;z=13&amp;iwloc=addr&amp;output=embed"></iframe>
  
  <div style="margin:10px;">
  <?php
    echo "
    <table class=\"hcmsTableStandard\" style=\"width:620px;\">
      <tr class=\"hcmsRowData1\"><td style=\"width:180px;\">Country </td><td>".@$data['country']." </td></tr>
      <tr class=\"hcmsRowData2\"><td>Region </td><td>".@$data['regionName']." </td></tr>
      <tr class=\"hcmsRowData1\"><td>City </td><td>".@$data['city']." </td></tr>
      <tr class=\"hcmsRowData2\"><td>ZIP code </td><td>".@$data['zip']." </td></tr>
      <tr class=\"hcmsRowData1\"><td>Latitude </td><td>".@$data['lat']." </td></tr>
      <tr class=\"hcmsRowData2\"><td>Longitude </td><td>".@$data['lon']." </td></tr>
    </table>";
  ?>
  </div>
  <?php
  }
  else echo "Error for ".$ip;
  ?>
</div>

<?php include_once ("include/footer.inc.php"); ?>
</body>
</html>
