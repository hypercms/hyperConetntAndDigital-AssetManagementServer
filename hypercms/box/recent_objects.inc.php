<?php
// ---------------------- RECENT OBJECTS ---------------------

$objectinfo_array = rdbms_searchuser ("", $user);

if (is_array ($objectinfo_array) && sizeof ($objectinfo_array) > 0)
{
  // prepare array
  $object_array = array();
  
  foreach ($objectinfo_array as $hash => $objectinfo)
  {
    if (!empty ($objectinfo['objectpath'])) $object_array[$hash] = $objectinfo['objectpath'];
  }

  // output
  if (is_array ($object_array) && sizeof ($object_array) > 0)
  {
    $object_array = array_unique ($object_array);
  
    if ($is_mobile) $width = "92%";
    else $width = "320px";
    
    echo "
    <div id=\"recent\" class=\"hcmsHomeBox\" style=\"margin:10px; width:".$width."; height:400px; float:left;\">
      <div class=\"hcmsHeadline\" style=\"margin:2px;\">".getescapedtext ($hcms_lang['my-recent-objects'][$lang])."</div>";
    
    array_reverse ($object_array);
    reset ($object_array);
    $i = 0;
    
    foreach ($object_array as $hash => $objectpath)
    {
      // show only object items
      if (getobject ($objectpath) != ".folder" && $i < 20)
      {
        // get site
        $item_site = getpublication ($objectpath);        
        // get category
        $item_cat = getcategory ($item_site, $objectpath); 
        
        if (valid_publicationname ($item_site) && $item_cat != "")
        {
          // get location
          $item_location_esc = getlocation ($objectpath);
          // get location in file system
          $item_location = deconvertpath ($item_location_esc, "file");           
          // get location name
          $item_locationname = getlocationname ($item_site, $item_location_esc, $item_cat, "path");        
          // get object name
          $item_object = getobject ($objectpath);  
          $item_object = correctfile ($item_location, $item_object, $user); 
          $item_fileinfo = getfileinfo ($item_site, $item_location.$item_object, $item_cat);
          $item_objectinfo = getobjectinfo ($item_site, $item_location, $item_object, $user);
          
          // check access permission
          $ownergroup = accesspermission ($item_site, $item_location, $item_cat);
          $setlocalpermission = setlocalpermission ($item_site, $ownergroup, $item_cat);
          
          if ($ownergroup != false && $setlocalpermission['root'] == 1 && valid_locationname ($item_location) && valid_objectname ($item_object))
          {
            // open on double click
            $openObject = "onClick=\"hcms_openWindow('frameset_content.php?ctrlreload=yes&site=".url_encode($item_site)."&cat=".url_encode($item_cat)."&location=".url_encode($item_location_esc)."&page=".url_encode($item_object)."', '".$item_objectinfo['container_id']."','status=yes,scrollbars=no,resizable=yes', ".windowwidth ("object").", ".windowheight ("object").");\"";
          
            echo "
            <div ".$openObject." style=\"display:block; cursor:pointer;\" title=\"".$item_locationname.$item_fileinfo['name']."\"><img src=\"".getthemelocation()."img/".$item_fileinfo['icon']."\" class=\"hcmsIconList\" />&nbsp;".showshorttext($item_fileinfo['name'], 30)."&nbsp;</div>";
            $i++;
          }
        }
      }
    }
    
    echo "
    </div>\n";
  }
}
?>