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
$savetype = getrequest ("savetype");
$location = getrequest_esc ("location", "locationname");
$page = getrequest ("page", "objectname");
$contenttype = getrequest_esc ("contenttype");
$token = getrequest ("token");
$appendcontent = getrequest ("appendcontent");

// get publication and category
$site = getpublication ($location);
$cat = getcategory ($site, $location); 

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
// load publication configuration
if (valid_publicationname ($site)) $publ_config = parse_ini_file ($mgmt_config['abs_path_rep']."config/".$site.".ini");

// ------------------------------ permission section --------------------------------

// check access permissions
$ownergroup = accesspermission ($site, $location, $cat);
$setlocalpermission = setlocalpermission ($site, $ownergroup, $cat);  
if ($ownergroup == false || $setlocalpermission['root'] != 1 || $setlocalpermission['create'] != 1 || !valid_publicationname ($site) || !valid_locationname ($location) || !valid_objectname ($page)) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

// helper function for appending text content in multiedit mode
function appendcontent_helper ($xmlcontent, $text, $delimiter=" ")
{
  if (is_array ($text) && sizeof ($text) > 0 && !empty ($xmlcontent))
  {
    foreach ($text as $key=>$value)
    {
      // get content
      $temp = selectcontent ($xmlcontent, "<text>", "<text_id>", $key);
      if (!empty ($temp[0])) $temp_content = getcontent ($temp[0], "<textcontent>");
      
      // append content
      if (!empty ($temp_content[0])) $text[$key] = $temp_content[0].$delimiter.$value;
    }
  }
  
  return $text;
}

// extract character set from content-type
$result_charset = getcharset ($site, $contenttype);

if ($result_charset != false) $charset = $result_charset['charset'];
else $charset = "";

// Autosave
// data submitted by jquery post need to be converted
if ($savetype == "auto")
{
	$auto = true;
	$message = array();

  // convert jquery post since it is always UTF-8
  if ($charset != "" && strtolower ($charset) != "utf-8")
  {
    $_POST = convertchars ($_POST, "UTF-8", $charset);
  }
}
else
{
	$auto = false;
}

// input parameters
$savetype = getrequest ("savetype");
$autosave = getrequest ("autosave");
$forward = getrequest ("forward");
$view = getrequest ("view");
$location = getrequest_esc ("location", "locationname");
$page = getrequest_esc ("page", "objectname");
$db_connect = getrequest_esc ("db_connect", "objectname");
$media = getrequest_esc ("media", "objectname");
$ctrlreload = getrequest_esc ("ctrlreload");
$tagname = getrequest_esc ("tagname", "objectname");
$id = getrequest_esc ("id", "objectname");
$toolbar = getrequest_esc ("toolbar");
$width = getrequest_esc ("width", "numeric");
$height = getrequest_esc ("height", "numeric");
$constraint = getrequest_esc ("constraint");

$linkhref = getrequest ("linkhref", "array");
$linktarget = getrequest_esc ("linktarget", "array");
$targetlist = getrequest_esc ("targetlist", "array");
$linktext = getrequest ("linktext", "array");
$artlinkhref = getrequest ("artlinkhref", "array");
$artlinktarget = getrequest ("artlinktarget", "array");
$artlinktext = getrequest ("artlinktext", "array");

$arttitle = getrequest ("arttitle", "array");
$artstatus = getrequest ("artstatus", "array");
$artdatefrom = getrequest ("artdatefrom", "array");
$artdateto = getrequest ("artdateto", "array");

$textf = getrequest ("textf", "array");
$arttextf = getrequest ("arttextf", "array");
$textu = getrequest ("textu", "array");
$arttextu = getrequest ("arttextu", "array");
$textl = getrequest ("textl", "array");
$arttextl = getrequest ("arttextl", "array");
$textc = getrequest ("textc", "array");
$arttextc = getrequest ("arttextc", "array");
$textk = getrequest ("textk", "array");
$value = getrequest_esc ("value");
$textd = getrequest ("textd", "array");
$arttextd = getrequest ("arttextd", "array");
$format = getrequest_esc ("format");
$commentu = getrequest ("commentu", "array");
$commentf = getrequest ("commentf", "array");

$mediacat = getrequest ("mediacat", "array");
$mediafile = getrequest ("mediafile", "array");
$mediaobject = getrequest ("mediaobject", "array");
$mediaalttext = getrequest ("mediaalttext", "array");
$mediaalign = getrequest ("mediaalign", "array");
$mediawidth = getrequest ("mediawidth", "array");
$mediaheight = getrequest ("mediaheight", "array");
$mediatype = getrequest ("mediatype", "array");
$artmediafile = getrequest ("artmediafile", "array");
$artmediaobject = getrequest ("artmediaobject", "array");
$artmediaalttext = getrequest ("artmediaalttext", "array");
$artmediaalign = getrequest ("artmediaalign", "array");
$artmediawidth = getrequest ("artmediawidth", "array");
$artmediaheight = getrequest ("artmediaheight", "array");

$component = getrequest ("component", "array");
$artcomponent = getrequest ("artcomponent", "array");
$components = getrequest ("components", "array");
$artcomponents = getrequest ("artcomponents", "array");
$componentm = getrequest ("componentm", "array");
$artcomponentm = getrequest ("artcomponentm", "array");
$condition = getrequest ("condition", "array");

$geolocation = getrequest ("geolocation");

$faces = getrequest ("faces");

// base64 encoded JPEG annotation image
$medianame = getrequest ("medianame");
$mediadata = getrequest ("mediadata");

if (isset ($_REQUEST['pagetitle'])) $pagetitle = getrequest ("pagetitle");
if (isset ($_REQUEST['pageauthor'])) $pageauthor = getrequest ("pageauthor");
if (isset ($_REQUEST['pagedescription'])) $pagedescription = getrequest ("pagedescription");
if (isset ($_REQUEST['pagekeywords'])) $pagekeywords = getrequest ("pagekeywords");
if (isset ($_REQUEST['pagecontenttype'])) $pagecontenttype = getrequest ("pagecontenttype");
if (isset ($_REQUEST['pagelanguage'])) $pagelanguage = getrequest ("pagelanguage");
if (isset ($_REQUEST['pagerevisit'])) $pagerevisit = getrequest ("pagerevisit");
if (isset ($_REQUEST['pagetracking'])) $pagetracking = getrequest ("pagetracking");

$wf_token = getrequest_esc ("wf_token");
$token = getrequest ("token");

// convert location
$location = deconvertpath ($location, "file");
$location_esc = convertpath ($site, $location, $cat);

// load object file and get container
$objectdata = loadfile ($location, $page);
$contentfile = getfilename ($objectdata, "content");

// check locked by user
$result_containername = getcontainername ($contentfile);
$usedby = $result_containername['user'];

// if not locked by another user
if ($usedby == "" || $usedby == $user)
{
  // include hyperCMS Event System
  @include_once ($mgmt_config['abs_path_data']."eventsystem/hypercms_eventsys.inc.php");
  
  // ------------------------------------include db_connect functions ----------------------------------
  if (isset ($db_connect) && valid_objectname ($db_connect) && file_exists ($mgmt_config['abs_path_data']."db_connect/".$db_connect)) 
  {
    // include db_connect functions
    @include_once ($mgmt_config['abs_path_data']."db_connect/".$db_connect); 
  }
  
  // ---------------------------------------load content container -------------------------------------

  // load content container
  $container_id = substr ($contentfile, 0, strpos ($contentfile, ".xml"));
  $contentdata = loadcontainer ($container_id, "work", $user);

  // check if content is not empty
  if ($contentdata != false)
  {
    $contentdatanew = $contentdata;
  
    // check if date-from is greater than date-to in article content
    if (is_array ($artstatus))
    {
      for ($i = 1; $i <= sizeof ($artstatus); $i++)
      {
        // get key (position) of array item
        $artid = key ($artstatus); 
        
        if (isset ($artdatefrom[$artid]) && $artdatefrom[$artid] != "" && isset ($artdateto[$artid]) && $artdateto[$artid] != "")
        {
          $artdatefromcheck = str_replace ("-", "", $artdatefrom[$artid]);
          $artdatefromcheck = str_replace (" ", "", $artdatefromcheck);
          $artdatefromcheck = str_replace (":", "", $artdatefromcheck);
          $artdatetocheck = str_replace ("-", "", $artdateto[$artid]);
          $artdatetocheck = str_replace (" ", "", $artdatetocheck);
          $artdatetocheck = str_replace (":", "", $artdatetocheck);
    
          // check if date-from is greater than date-to
          if ($artdatetocheck < $artdatefromcheck)
          {
            echo "<!DOCTYPE html>\n";
            echo "<html lang=\"".getsession("hcms_lang", "en")."\">\n";
            echo "<head>\n";
            echo "<title>hyperCMS</title>\n";
            echo "<meta charset=\"".getcodepage($lang)."\" />\n";
            echo "<link rel=\"stylesheet\" href=\"".getthemelocation()."css/main.css\">\n";
            echo "</head>\n";
            echo "<body class=\"hcmsWorkplaceGeneric\">\n";
            echo "<p class=hcmsHeadline>".$hcms_lang['the-end-date-is-before-the-start-date-of-the-article'][$lang]."</p>\n";
            echo $hcms_lang['please-go-back-and-correct-the-date-settings'][$lang]."\n";
            echo "<a href=\"#\" onlick=\"history.back();\">".$hcms_lang['back'][$lang]."</a><br />\n";
            echo "</body>\n</html>";
            exit;
          }
        }
        
        // correct dates based on users time zone
        if (!empty ($_SESSION['hcms_timezone']) && ini_get ('date.timezone'))
        {
          if ($artdatefrom[$artid] != "")
          {
            $datenew = convertdate ($artdatefrom[$artid], $_SESSION['hcms_timezone'], "Y-m-d H:i", ini_get ('date.timezone'), "Y-m-d H:i");
            if (!empty ($datenew)) $artdatefrom[$artid] = $datenew;
          }
          
          if ($artdateto[$artid] != "")
          {
            $datenew = convertdate ($artdateto[$artid], $_SESSION['hcms_timezone'], "Y-m-d H:i", ini_get ('date.timezone'), "Y-m-d H:i");
            if (!empty ($datenew)) $artdateto[$artid] = $datenew;
          }
        }
        
        next ($artstatus);
      }
    }
  
    // write content in container if security token is available and matches the crypted location of the object (absolute path in file system is used as input for encryption!)
    if (checktoken ($token, $user) && valid_locationname ($location) && valid_objectname ($page))
    {
      // ----------------------------------- write content -------------------------------------- 
      
      // set atricle
      if ($contentdatanew != false && is_array ($artstatus)) $contentdatanew = setarticle ($site, $contentdatanew, $contentfile, $arttitle, $artstatus, $artdatefrom, $artdateto, $user, $user);
    
      // face detection data
      if (isset ($faces))
      {
        // remove empty entries
        $textu['Faces-JSON'] = str_replace (", , ", ", ", $faces);
      }
      
      // append content (only for textu, textf, textk)
      if (!empty ($appendcontent))
      {
        $textf = appendcontent_helper ($contentdatanew, $textf);
        $arttextf = appendcontent_helper ($contentdatanew, $arttextf);
        $textu = appendcontent_helper ($contentdatanew, $textu);
        $arttextu = appendcontent_helper ($contentdatanew, $arttextu);
        $textk = appendcontent_helper ($contentdatanew, $textk, ",");
      }

      // text content
      if (isset ($textf) && is_array ($textf) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $textf, "f", "no", $user, $user, $charset);
      if (isset ($arttextf) && is_array ($arttextf) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $arttextf, "f", "yes", $user, $user, $charset);
      if (isset ($textu) && is_array ($textu) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $textu, "u", "no", $user, $user, $charset);
      if (isset ($arttextu) && is_array ($arttextu) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $arttextu, "u", "yes", $user, $user, $charset);
      if (isset ($textl) && is_array ($textl) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $textl, "l", "no", $user, $user, $charset);
      if (isset ($arttextl) && is_array ($arttextl) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $arttextl, "l", "yes", $user, $user, $charset);
      if (isset ($textc) && is_array ($textc) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $textc, "c", "no", $user, $user, $charset);
      if (isset ($arttextc) && is_array ($arttextc) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $arttextc, "c", "yes", $user, $user, $charset);
      if (isset ($textd) && is_array ($textd) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $textd, "d", "no", $user, $user, $charset);
      if (isset ($arttextd) && is_array ($arttextd) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $arttextd, "d", "yes", $user, $user, $charset);
      // keywords only apply for metadata templates (no support for articles)
      if (isset ($textk) && is_array ($textk) && $contentdatanew != false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $textk, "k", "no", $user, $user, $charset);
      // only if autosaving is not used
      if (isset ($commentu) && is_array ($commentu) && $contentdatanew != false && $auto == false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $commentu, "u", "no", $user, $user, $charset, true);
      if (isset ($commentf) && is_array ($commentf) && $contentdatanew != false && $auto == false) $contentdatanew = settext ($site, $contentdatanew, $contentfile, $commentf, "f", "no", $user, $user, $charset, true);
      
      // get media
      $object_info = getobjectinfo ($site, $location, $page, $user);

      // write meta data to media file
      if (trim ($object_info['media']) != "")
      {
        // define text array
        $textmeta = array();
        
        if (isset ($textf) && is_array ($textf)) $textmeta = array_merge ($textmeta, $textf);
        if (isset ($textu) && is_array ($textu)) $textmeta = array_merge ($textmeta, $textu);
        if (isset ($textl) && is_array ($textl)) $textmeta = array_merge ($textmeta, $textl);
        if (isset ($textc) && is_array ($textc)) $textmeta = array_merge ($textmeta, $textc);
        if (isset ($textd) && is_array ($textd)) $textmeta = array_merge ($textmeta, $textd);
        if (isset ($textk) && is_array ($textk)) $textmeta = array_merge ($textmeta, $textk);

        // get media file location and name
        $mediafile_location = getmedialocation ($site, $object_info['media'], "abs_path_media");
        $mediafile_name = $object_info['media'];
        
        // correct path to media file
        if (!is_file ($mediafile_location.$mediafile_name)) $mediafile_location = $mediafile_location.$site."/";

        // prepare media file
        $temp = preparemediafile ($site, $mediafile_location, $mediafile_name, $user);
        
        // if encrypted
        if (!empty ($temp['result']) && !empty ($temp['crypted']))
        {
          $object_mediafile = $temp['templocation'].$temp['tempfile'];
        }
        // if restored
        elseif (!empty ($temp['result']) && !empty ($temp['restored']))
        {
          $object_mediafile = $temp['location'].$temp['file'];
        }
        else
        {
          $object_mediafile = $mediafile_location.$mediafile_name;
        }

        if (is_file ($object_mediafile))
        {
          // ------------------------------- write annotation image ----------------------------------   

          if (!empty ($medianame) && !empty ($mediadata))
          {
            $thumbfile_location = getmedialocation ($site, ".hcms.".$object_info['media'], "abs_path_media").$site."/";
            
            // if symbolic link
            if (is_link ($thumbfile_location.$medianame))
            {
              $target_path = readlink ($thumbfile_location.$medianame);
              $target_location = getlocation ($target_path);
            }
            else $target_location = $thumbfile_location;
            
            $annotationfile = base64_to_file ($mediadata, $target_location, $medianame);

            // save to cloud storage
            if (!empty ($annotationfile) && function_exists ("savecloudobject")) savecloudobject ($site, $thumbfile_location, $medianame, $user);
          }
        
          // ----------------------------------- write metadata --------------------------------------  
          
          // write IPTC data to media file
          $result_iptc = false;
          
          if (!empty ($mgmt_config['iptc_save']))
          {
            $iptc = iptc_create ($site, $textmeta);
  
            if (is_array ($iptc))
            {
              $result_iptc = iptc_writefile ($object_mediafile, $iptc, true, false);
            }
          }
          
          // write XMP data to media file
          $result_xmp = false;
          
          if (!empty ($mgmt_config['xmp_save']))
          {
            $xmp = xmp_create ($site, $textmeta);
  
            if (is_array ($xmp))
            {
              $result_xmp = xmp_writefile ($object_mediafile, $xmp, true, false);   
            }
          }
          
          // write ID3 data to media file
          $result_id3 = false;
          
          if (!empty ($mgmt_config['id3_save']))
          { 
            $id3 = id3_create ($site, $textmeta);
  
            if (is_array ($id3))
            {
              $result_id3 = id3_writefile ($object_mediafile, $id3, true, false);   
            }
          }
          
          // touch thumbnail file of documents to update the timestamp / avoid recreation of annotation images)
          if (is_document ($object_mediafile))
          {
            // get file name without extensions
            $object_thumbfile = strrev (substr (strstr (strrev ($object_mediafile), "."), 1)).".thump.pdf";
            
            // update timestamp
            if (is_file ($object_thumbfile)) touch ($object_thumbfile);
          }
            
          // save media stats and move temp file on success
          if (!empty ($result_iptc) || !empty ($result_xmp) || !empty ($result_id3))
          {
            // write updated media information to DB
            if (!empty ($container_id))
            {
              $md5_hash = md5_file ($object_mediafile);
              $filesize = round (@filesize ($object_mediafile) / 1024, 0);
              rdbms_setmedia ($container_id, $filesize, "", "", "", "", "", "", "", "", $md5_hash);
            }
            
            // encrypt and save file if required
            if ($temp['result']) movetempfile ($mediafile_location, $mediafile_name, true);

            // save to cloud storage
            if (function_exists ("savecloudobject")) savecloudobject ($site, $mediafile_location, $mediafile_name, $user);
          }
          
          // set modified date in DB
          rdbms_setcontent ($site, $container_id);
        }
      }

      // media content
      if ($contentdatanew != false && isset ($mediafile) && is_array ($mediafile)) $contentdatanew = setmedia ($site, $contentdatanew, $contentfile, $mediafile, $mediaobject, $mediaalttext, $mediaalign, $mediawidth, $mediaheight, "no", $user, $user, $charset);
      if ($contentdatanew != false && isset ($artmediafile) && is_array ($artmediafile)) $contentdatanew = setmedia ($site, $contentdatanew, $contentfile, $artmediafile, $artmediaobject, $artmediaalttext, $artmediaalign, $artmediawidth, $artmediaheight, "yes", $user, $user, $charset);
  
      // page link content
      if ($contentdatanew != false && isset ($linkhref) && is_array ($linkhref)) $contentdatanew = setpagelink ($site, $contentdatanew, $contentfile, $linkhref, $linktarget, $linktext, "no", $user, $user, $charset);
      if ($contentdatanew != false && isset ($artlinkhref) && is_array ($artlinkhref)) $contentdatanew = setpagelink ($site, $contentdatanew, $contentfile, $artlinkhref, $artlinktarget, $artlinktext, "yes", $user, $user, $charset);    
  
      // component content
      if ($contentdatanew != false && isset ($component) && is_array ($component)) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, $component, $condition, "no", $user, $user);
      if ($contentdatanew != false && isset ($artcomponent) && is_array ($artcomponent)) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, $artcomponent, $condition, "yes", $user, $user);    
      if ($contentdatanew != false && isset ($components) && is_array ($components)) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, $components, $condition, "no", $user, $user);
      if ($contentdatanew != false && isset ($artcomponents) && is_array ($artcomponents)) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, $artcomponents, $condition, "yes", $user, $user);    
      if ($contentdatanew != false && isset ($componentm) && is_array ($componentm)) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, $componentm, $condition, "no", $user, $user);
      if ($contentdatanew != false && isset ($artcomponentm) && is_array ($artcomponentm)) $contentdatanew = setcomplink ($site, $contentdatanew, $contentfile, $artcomponentm, $condition, "yes", $user, $user);    
  
      // head content
      if (isset ($pagetitle)) $headcontent['pagetitle'] = $pagetitle;
      if (isset ($pageauthor)) $headcontent['pageauthor'] = $pageauthor;
      if (isset ($pagedescription)) $headcontent['pagedescription'] = $pagedescription;
      if (isset ($pagekeywords)) $headcontent['pagekeywords'] = $pagekeywords;
      if (isset ($pagecontenttype)) $headcontent['pagecontenttype'] = $pagecontenttype;
      if (isset ($pagelanguage)) $headcontent['pagelanguage'] = $pagelanguage;
      if (isset ($pagerevisit)) $headcontent['pagerevisit'] = $pagerevisit;
      if (isset ($pagetracking)) $headcontent['pagetracking'] = $pagetracking;

      if ($contentdatanew != false && isset ($headcontent) && is_array ($headcontent)) $contentdatanew = sethead ($site, $contentdatanew, $contentfile, $headcontent, $user);

      // geo location
      if ($geolocation != "")
      {
        list ($latitude, $longitude) = explode (",", $geolocation);

        $sql = "UPDATE container SET latitude=".floatval($latitude).", longitude=".floatval($longitude)." WHERE id=".intval($container_id);                
        $result = rdbms_externalquery ($sql);
      }
    }

    // ----------------------------------- write data into content container --------------------------------------
    if ($contentdatanew != false)
    {
      // create new version of content on save
      if (!empty ($mgmt_config['contentversions']) && !empty ($mgmt_config['contentversions_all']))
      {
        createversion ($site, $contentfile);
      }
    
      // eventsystem
      if ($eventsystem['onsaveobject_pre'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0)) 
      {
        $contentdataevent = onsaveobject_pre ($site, $cat, $location, $page, $contentfile, $contentdatanew, $user);

        // check if event returns a string, if so, the event returns the container and not true or false 
        if (!empty ($contentdataevent) && strlen ($contentdataevent) > 10) $contentdatanew = $contentdataevent;
      }

      // insert new date into content file
      $contentdatanew = setcontent ($contentdatanew, "<hyperCMS>", "<contentdate>", $mgmt_config['today'], "", "");

      // set encoding 
      $charset_old = getcharset ("", $contentdatanew);

      if (empty ($charset_old['charset']) || strtolower ($charset_old['charset']) != strtolower ($charset))
      {
        // write XML declaration parameter for text encoding
        if ($charset != "") $contentdatanew = setxmlparameter ($contentdatanew, "encoding", $charset);
      }

      // save working xml content container file
      $savefile = savecontainer ($container_id, "work", $contentdatanew, $user);

      // test if file could be saved
      if ($savefile == false)
      {
        // define meta tag
        $add_onload =  "";
        
        if ($auto)
        {
        	$message[] = $hcms_lang['you-do-not-have-write-permissions-for-the-content-container'][$lang];
        	$message[] = $hcms_lang['without-write-permission-the-content-cant-be-edited'][$lang];
        }
        else
        {
  	      //define message to display
      	  $message = "<p class=hcmsHeadline>".$hcms_lang['you-do-not-have-write-permissions-for-the-content-container'][$lang]."</p>\n".$hcms_lang['without-write-permission-the-content-cant-be-edited'][$lang]."<br />\n";
        }
      }
      else
      {
        // eventsystem
        if ($eventsystem['onsaveobject_post'] == 1 && (!isset ($eventsystem['hide']) || $eventsystem['hide'] == 0))
        {
          $contentdataevent = onsaveobject_post ($site, $cat, $location, $page, $contentfile, $contentdatanew, $user);
        }
        
        // check if event returns a string, if so, the event returns the container and not true or false 
        if (!empty ($contentdataevent) && strlen ($contentdataevent) > 10) $contentdatanew = $contentdataevent;

        // information log
        $errcode = "00101";
        $error[] = date('Y-m-d H:i')."|savecontent.php|information|".$errcode."|object '".$location_esc.$page."' has been edited and saved by user '".$user."'";

        // notification
        notifyusers ($site, $location, $page, "onedit", $user);

      	if (!$auto)
        {
         	// define forward to URL
         	if ($savetype == "editorf_so" || $savetype == "editorf_wysiwyg")
          {
         	  $add_onload =  "document.location='".$mgmt_config['url_path_cms']."editor/editorf.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&contenttype=".url_encode($contenttype)."&width=".url_encode($width)."&height=".url_encode($height)."&toolbar=".url_encode($toolbar)."';\n";
         	}
          elseif ($savetype == "editoru_so")
         	{
            $add_onload =  "document.location='".$mgmt_config['url_path_cms']."editor/editoru.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&constraint=".url_encode($constraint)."&contenttype=".url_encode($contenttype)."&width=".url_encode($width)."&height=".url_encode($height)."';\n";
         	}
          elseif ($savetype == "editorl_so")
         	{
            $add_onload =  "document.location='".$mgmt_config['url_path_cms']."editor/editorl.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&list=".url_encode($list)."&contenttype=".url_encode($contenttype)."';\n";
         	}
          elseif ($savetype == "editorc_so")
         	{
            $add_onload =  "document.location='".$mgmt_config['url_path_cms']."editor/editorc.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&value=".url_encode($value)."&contenttype=".url_encode($contenttype)."';\n";
         	}
          elseif ($savetype == "editord_so")
         	{
            $add_onload =  "document.location='".$mgmt_config['url_path_cms']."editor/editord.php?site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&tagname=".url_encode($tagname)."&id=".url_encode($id)."&format=".url_encode($format)."&contenttype=".url_encode($contenttype)."&wf_token=".url_encode($wf_token)."';\n";
         	}        
          elseif ($savetype == "form_so")
         	{
            if ($forward == "") $add_onload =  "document.location='".$mgmt_config['url_path_cms']."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';\n";
            else $add_onload = "document.location='".$forward."';\n";
         	}
         	elseif ($savetype == "form_sc")
         	{
            $add_onload =  "document.location='".$mgmt_config['url_path_cms']."page_view.php?view=cmsview&site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';\n";
          }
         	elseif ($savetype == "documentviewerconfig_so")
         	{
            $add_onload =  "document.location='".$mgmt_config['url_path_cms']."document_viewerconfig.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode(convertpath($site, $location, $cat)).'&page='.url_encode($page)."&title=".(!empty ($textu['Title']) ? url_encode($textu['Title']) : "")."&wf_token=".url_encode($wf_token)."';\n";
         	} 
         	elseif ($savetype == "imagerendering_so")
         	{
            // define image editor
            if (!empty ($mgmt_config['imageeditor']) && strtolower ($mgmt_config['imageeditor']) == "minipaint") $imageeditor = "image_minipaint.php";
            else $imageeditor = "image_rendering.php";

            $add_onload =  "document.location='".$mgmt_config['url_path_cms'].$imageeditor."?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode(convertpath($site, $location, $cat))."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';\n";
          }
         	elseif ($savetype == "imageviewerconfig_so")
         	{
            $add_onload =  "document.location='".$mgmt_config['url_path_cms']."image_viewerconfig.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode(convertpath($site, $location, $cat)).'&page='.url_encode($page)."&title=".(!empty ($textu['Title']) ? url_encode($textu['Title']) : "")."&wf_token=".url_encode($wf_token)."';\n";
         	} 
         	elseif ($savetype == "mediarendering_so")
         	{
            $add_onload =  "document.location='".$mgmt_config['url_path_cms']."media_rendering.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode(convertpath($site, $location, $cat))."&page=".url_encode($page)."&wf_token=".url_encode($wf_token)."';\n";
          } 
         	elseif ($savetype == "mediaplayerconfig_so")
         	{
            $add_onload =  "document.location='".$mgmt_config['url_path_cms']."media_playerconfig.php?site=".url_encode($site)."&cat=".url_encode($cat)."&location=".url_encode(convertpath($site, $location, $cat)).'&page='.url_encode($page)."&title=".(!empty ($textu['Title']) ? url_encode($textu['Title']) : "")."&wf_token=".url_encode($wf_token)."';\n";
         	}                   
          else
         	{
            $add_onload =  "document.location='".$mgmt_config['url_path_cms']."page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&ctrlreload=no';\n";
         	}

         	// define message to display
         	$message = "<p class=hcmsHeadline>".$hcms_lang['refreshing-view-'][$lang]."</p>\n";
         	$message .= "<a href=\"page_view.php?view=".url_encode($view)."&site=".url_encode($site)."&cat=".url_encode($cat)."&db_connect=".url_encode($db_connect)."&location=".url_encode($location_esc)."&page=".url_encode($page)."&ctrlreload=no\">".$hcms_lang['manual-refresh'][$lang]."</a>\n";
      	}
      }

      // ----------------------------------- DB Connectivity --------------------------------------   
      // db_connect will save content in provided database connectivity 
      if (!empty ($db_connect)) 
      {      
        // write data
        $test = db_write_container ($site, $contentfile, $contentdatanew, $user);    
        
        if ($test == false)
        {
          $errcode = "20101";
          $error[] = $mgmt_config['today']."|page_save.inc.php|error|$errcode|unable to save data of container '$contentfile' using db_connect '$db_connect'";          
        }
      }
    }
    else
    {
      // define meta tag
      $add_onload =  "";
      // define message to display
      if ($auto)
      {
      	$message[] = $hcms_lang['functional-error-occured'][$lang];
      }
      else
      {
      	$message = "<p class=hcmsHeadline>".$hcms_lang['functional-error-occured'][$lang]."</p>\n<a href=\"page_view.php?site=".$site."&location=".$location_esc."&page=".$page."\">".$hcms_lang['manual-refresh'][$lang]."</a>";
      }
    }
  }
  // if content file isn't available
  elseif (!is_file (getcontentlocation ($container_id, 'abs_path_content').$contentfile.".wrk"))
  {
    // define meta tag
    $add_onload =  "";

    // define message to display
    if ($auto)
    {
    	// define message to display
    	$message[] = $hcms_lang['content-container-is-missing'][$lang];
    	$message[] = $hcms_lang['the-content-of-this-object-is-missing'][$lang];
    	$message[] = $hcms_lang['to-create-a-new-content-container-please-delete-the-object-and-create-a-new-one'][$lang];
    }
    else
    {
    	$message = "<p class=hcmsHeadline>".$hcms_lang['content-container-is-missing'][$lang]."</p>\n".$hcms_lang['the-content-of-this-object-is-missing'][$lang]."<br />\n".$hcms_lang['to-create-a-new-content-container-please-delete-the-object-and-create-a-new-one'][$lang]."<br />\n";
    }
  }
  else
  {
    // define meta tag
    $add_onload = "";

    // define message to display
    if ($auto) 
    {
  	  $message[] = $hcms_lang['content-container-is-missing'][$lang];
    }
    else
    {
    	// define message to display
    	$message = "<p class=hcmsHeadline>".$hcms_lang['content-container-is-missing'][$lang]."</p>\n";
    }
  }
}
else
{
  // define meta tag
  $add_onload = "";

  // define message to display
  if ($auto) 
  {
	  $message[] = $hcms_lang['you-do-not-have-write-permissions-for-the-content-container'][$lang];
  }
  else
  {
  	// define message to display
  	$message = "<p class=hcmsHeadline>".$hcms_lang['you-do-not-have-write-permissions-for-the-content-container'][$lang]."</p>\n";
  }
}

// save log
savelog (@$error);

// json answer
if ($auto)
{
  if ($usedby != "" && $usedby != $user)
  {
    $message[] = $hcms_lang['you-do-not-have-write-permissions-for-the-content-container'][$lang];
  }

  // request from autosave
  header ('Content-Type: application/json; charset=utf-8');
	echo json_encode (array('message' => implode(", ", $message)));  
}
else
{
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css">
<script type="text/javascript">
<?php echo $add_onload; ?>
</script>
<script src="../javascript/main.js" type="text/javascript"></script>
<script src="../javascript/click.js" type="text/javascript"></script>
</head>
<body class="hcmsWorkplaceGeneric">
<div style="padding:4px;">
  <?php echo $message; ?>
</div>
</body>
</html>
<?php 
}
?>