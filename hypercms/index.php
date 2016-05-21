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

// set forward URL
$forward = "";

// ----------------------- on access event ----------------------

// call on access event to analyze request
if ($eventsystem['onaccess'] == 1) onaccess ($_REQUEST);

// ------------------- access link parameters -------------------

//   new hash parameter for mail-link
$al = getrequest ("al", "url");

if ($al != "")
{
  $forward = "userlogin.php?al=".url_encode($al);
}

// deprecated since version 5.6.1 but still supported:
//   standard input parameters (mail-link logon)
$hcms_user = getrequest ("hcms_user", "url");
$hcms_pass = getrequest ("hcms_pass", "url");
$hcms_objref = getrequest ("hcms_objref", "url");
$hcms_objcode = getrequest ("hcms_objcode", "url");

if ($hcms_user != "" && $hcms_pass != "" && $hcms_objref != "" && $hcms_objcode != "")
{
  $forward = "userlogin.php?hcms_user=".url_encode($hcms_user)."&hcms_pass=".url_encode($hcms_pass)."&hcms_objref=".url_encode($hcms_objref)."&hcms_objcode=".url_encode($hcms_objcode);
}

// deprecated since version 5.6.1 but still supported:
//   secure input parameters (mail-link logon)
$hcms_user_token = getrequest ("hcms_user_token", "url");

if ($hcms_user_token != "")
{
  $forward = "userlogin.php?hcms_user_token=".url_encode($hcms_user_token);
}

// ------------- wrapper and download link parameters -------------

// media conversion
$type = getrequest ("type"); // format = file extension
$mediacfg = getrequest ("mediacfg"); // media config to be used (see config.inc.php)

$add = "";

if ($type != "") $add .= "&type=".url_encode($type);
if ($mediacfg != "") $add .= "&mediacfg=".url_encode($mediacfg);

//   new hash parameter for wrapper-link
$wl = getrequest ("wl", "url");

if ($wl != "")
{
  $forward = "service/mediawrapper.php?wl=".url_encode($wl).$add;
}

//   new hash parameter for download-link
$dl = getrequest ("dl", "url");

if ($dl != "")
{
  $forward = "service/mediadownload.php?dl=".url_encode($dl).$add;
}

// deprecated since version 5.6.1 but still supported:
//   standard input parameters
$hcms_objid = getrequest ("hcms_objid", "url");
$hcms_token = getrequest ("hcms_token", "url");
$type = getrequest ("type", "url");

if ($type == "dl") $file = "service/mediadownload.php";
else $file = "service/mediawrapper.php";

if ($hcms_objid != "" && $hcms_token != "")
{
  $forward = $file."?hcms_objid=".url_encode($hcms_objid)."&hcms_token=".url_encode($hcms_token).$add;
}

// deprecated since version 5.6.1 but still supported:
//   secure input parameters
$hcms_id_token = getrequest ("hcms_id_token", "url");

if ($hcms_id_token != "")
{
  $forward = $file."?hcms_id_token=".url_encode($hcms_id_token).$add;
}

// ------------- wrapper and download media parameters -------------

// new encrypted media string for wrapper-link
$wm = getrequest ("wm", "url");

if ($wm != "")
{
  $forward = "service/mediastream.php?wm=".url_encode($wm).$add;
}

// new encrypted media string for wrapper-link
$dm = getrequest ("dm", "url");

if ($dm != "")
{
  $forward = "service/mediadownload.php?dm=".url_encode($dm).$add;
}

// ------------------------- forward ------------------------------

// use full CMS URL to avoid session issues with multiple domain names
if ($forward != "") header ("Location: ".$mgmt_config['url_path_cms'].$forward);
else header ("Location: ".$mgmt_config['url_path_cms']."userlogin.php");
?>