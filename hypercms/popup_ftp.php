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
$site = getrequest ("site", "publicationname");

// logon
$sentserver = getrequest ("sentserver");
$sentuser = getrequest ("sentuser");
$sentpasswd = getrequest ("sentpasswd");
$ssl = getrequest ("ssl");

// path
$path = getrequest ("path");
$multi = getrequest ("multi");

$action = getrequest ("action");
$token = getrequest ("token");

// publication management config
if (valid_publicationname ($site)) require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");

// ------------------------------ permission section --------------------------------

// check permissions
if (empty ($mgmt_config['ftp_download'])) killsession ($user);

// check session of user
checkusersession ($user, false);

// --------------------------------- logic section ----------------------------------

$show = "";
$conn_id = false;
$ftp_connection = false;

// logout from FTP server (empty connection info of session)
if ($action == "logout")
{
  setsession ("hcms_temp_ftp_connection", "", true);
}

// get existing FTP connection
$ftp_connection = getsession ("hcms_temp_ftp_connection");

// check for existing FTP connection
if (!empty ($ftp_connection))
{
  // get FTP connection data
  $ftp_array = getsession ($ftp_connection);

  // set FTP logon data
  $sentserver = $ftp_connection;
  if (!empty ($ftp_array['ftp_user'])) $sentuser = $ftp_array['ftp_user'];
  if (!empty ($ftp_array['ftp_password'])) $sentpasswd = $ftp_array['ftp_password'];
  if (!empty ($ftp_array['ftp_ssl'])) $ssl = $ftp_array['ftp_ssl'];
}

// logon to FTP server
if ((($action == "logon" && checktoken ($token, $user)) || !empty ($ftp_connection)) && !empty ($sentserver) && !empty ($sentuser) && !empty ($sentpasswd))
{
  if (!empty ($ssl)) $ssl = true;
  else $ssl = false;
  
  $conn_id = ftp_userlogon ($sentserver, $sentuser, $sentpasswd, $ssl);

  if (!$conn_id)
  {
    $show = getescapedtext ($hcms_lang['login-incorrect'][$lang]);
  }
  else
  {
    $ftp_array = array();
    $ftp_array['ftp_user'] = $sentuser;
    $ftp_array['ftp_password'] = $sentpasswd;
    $ftp_array['ftp_ssl'] = $ssl;
    
    // save current server name as FTP connection
    setsession ("hcms_temp_ftp_connection", $sentserver);
    // save FTP logon data 
    setsession ($sentserver, $ftp_array, true);
  }
}

// create secure token
$token_new = createtoken ($user);
?>
<!DOCTYPE html>
<html>
<head>
<title>hyperCMS</title>
<meta charset="<?php echo getcodepage ($lang); ?>" />
<meta name="theme-color" content="#464646" />
<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=1" />
<link rel="stylesheet" href="<?php echo getthemelocation(); ?>css/main.css" />
<script src="javascript/main.js" type="text/javascript"></script>
<script type="text/javascript">
function submitfiles ()
{
  var selected = document.getElementsByTagName('input');
  var file_object;
  
  for (var i = 0; i < selected.length; i++)
  {
    if ((selected[i].type == 'checkbox' && selected[i].checked) || (selected[i].type == 'radio' && selected[i].checked))
    { 
      file_info = selected[i].value.split("|"); ;
      
      if (file_info[0] != "" && file_info[1] != "" && file_info[2] != "")
      {
        opener.insertFTPFile (file_info[0], file_info[1], file_info[2]);
      }
    }
  }
}

function ftp_logout ()
{
  document.location.href = "?action=logout&multi=<?php echo $multi; ?>";
}
</script>
</head>

<body class="hcmsWorkplaceGeneric">

<!-- top bar -->
<?php
echo showtopbar ($hcms_lang['file-download-from-ftp-server'][$lang], $lang);
?>

<?php
// no FTP connection
if (empty ($conn_id))
{
?>
<div class="hcmsLogonScreen" style="margin-top:60px;">
  <form name="login" method="post" action="">
    <input type="hidden" name="action" value="logon" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" />

    <table style="border:0; padding:0; border-spacing:2; border-collapse:collapse;">
      <tr>
        <td>&nbsp;</td>
        <td class="hcmsTextOrange"><strong><?php echo $show; ?></strong></td>
      </tr>
      <tr>
        <td><b><?php echo getescapedtext ($hcms_lang['server'][$lang]); ?></b></td>
        <td>
          <input type="text" name="sentserver" maxlength="100" style="width:150px; height:16px;" />
        </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>
          <input type="checkbox" name="ssl" value="1" /> <?php echo getescapedtext ($hcms_lang['use-ssl'][$lang]); ?>
        </td>
      </tr>
      <tr>
        <td><b><?php echo getescapedtext ($hcms_lang['user'][$lang]); ?></b></td>
        <td>
          <input type="text" name="sentuser" maxlength="100" style="width:150px; height:16px;" />
        </td>
      </tr>
      <tr>
        <td><b><?php echo getescapedtext ($hcms_lang['password'][$lang]); ?></b></td>
        <td>
          <input type="password" name="sentpasswd" maxlength="100" style="width:150px; height:16px;" />
        </td>
      </tr>
      <tr>
        <td>&nbsp;</td>   
        <td>
          <button class="hcmsButtonGreen" style="width:155px; heigth:20px;" onClick="document.forms['login'].submit();">Log in</button>
        </td>
      </tr>
      <tr>
        <td>&nbsp;</td>
        <td>&nbsp;</td>
      </tr>          
    </table>
  </form>
</div>
<?php
}
// connection was successful
else
{
  // set default path for FTP server
  if ($path == "") $path = ".";

  // get file list from FTP server
  $file_array = ftp_filelist ($conn_id, $path);

  // show messages
  echo showmessage ($show, 360, 70, $lang, "position:fixed; left:15px; top:15px;");
?>
<div>
  <form name="publish" method="post" action="">
    <input type="hidden" name="action" value="<?php echo $action; ?>" />
    <input type="hidden" name="token" value="<?php echo $token_new; ?>" /> 
    
    <table cellpadding="0" cellspacing="0" style="border:0; width:100%; height:340px; table-layout:fixed;">
      <tr height="16">
        <td width="20" nowrap="nowrap">
          &nbsp;
        </td>
        <td nowrap="nowrap" class="hcmsHeadline">
          &nbsp;<?php echo getescapedtext ($hcms_lang['name'][$lang]); ?>
        </td>
        <td width="120" nowrap="nowrap" class="hcmsHeadline">
          &nbsp;<?php echo getescapedtext ($hcms_lang['date-modified'][$lang]); ?>
        </td>
        <td width="100" align="right" nowrap="nowrap" class="hcmsHeadline">
          &nbsp;<?php echo getescapedtext ($hcms_lang['size-in-kb'][$lang]); ?>&nbsp;
        </td>
      </tr>
      <?php
      if (is_array ($file_array))
      {
        if ($path != ".")
        {
          $link = "<a href=\"?path=".url_encode(getlocation($path))."&multi=".$multi."\"><span class=\"hcmsStandardText\">".getescapedtext ($hcms_lang['go-to-parent-folder'][$lang])."</span></a>";
          
          echo "<tr height=\"16\" class=\"hcmsWorkplaceObjectlist\"><td nowrap=\"nowrap\">&nbsp;</td><td nowrap=\"nowrap\"><img src=\"".getthemelocation()."img/back.png\" class=\"hcmsIconList\" align=\"absmiddle\" />&nbsp;".$link."</td><td nowrap=\"nowrap\">&nbsp;</td><td align=\"right\" nowrap=\"nowrap\">&nbsp;</td></tr>\n";
        }
        
        foreach ($file_array as $name => $file)
        {
          if ($name != "." && substr ($name, 0, 1) != ".")
          {
            // if directory
            if ($file['type'] == "directory")
            {
              // icon
              $file_info['icon'] = "folder.png";
              
              // file size
              $file_size = "";
              
              // default path
              if ($path == ".") $path = "/";
              
              // no select
              $checkbox = "";
              
              $link = "<a href=\"?path=".url_encode($path.$name."/")."&multi=".$multi."\"><span class=\"hcmsStandardText\">".showshorttext($name, 40)."</span></a>";
            }
            // if file
            else
            {
              // icon
              if ($file['type'] == "file") $file_info = getfileinfo ($site, $name, "comp");
              
              // file size
              if ($file['size'] > 0) $file_size = number_format (ceil ($file['size'] / 1024), 0, "", ".");
            
              // single or multi select
              if ($multi == "true") $input_type = "checkbox";
              else $input_type = "radio";
              
              $checkbox = "&nbsp;<input name=\"select\" type=\"".$input_type."\" value=\"".$name."|".$file['size']."|ftp://".$sentserver.$path.$name."\" />";
              
              $link = showshorttext ($name, 40);
            }
            
            // output
            echo "<tr height=\"16\" class=\"hcmsWorkplaceObjectlist\"><td nowrap=\"nowrap\">".$checkbox."</td><td nowrap=\"nowrap\"><img src=\"".getthemelocation()."img/".$file_info['icon']."\" class=\"hcmsIconList\" align=\"absmiddle\" />&nbsp;".$link."</td><td nowrap=\"nowrap\">&nbsp;".$file['month']."".$file['day']." ".$file['time']."</td><td align=\"right\" nowrap=\"nowrap\">&nbsp;".$file_size."&nbsp;</td></tr>\n";
          }
        }
      }
      ?>
      <tr class="hcmsWorkplaceObjectlist">
        <td colspan="4">
          &nbsp;
        </td>
      </tr>
    </table>

    <div class="hcmsWorkplaceControl" style="position:fixed; left:0; bottom:0; width:100%; padding:10px;"> 
      <button type="button" class="hcmsButtonGreen" onClick="submitfiles();"><?php echo getescapedtext ($hcms_lang['select-files'][$lang]); ?></button>
      <button type="button" class="hcmsButtonOrange" onClick="ftp_logout();"><?php echo getescapedtext ($hcms_lang['logout'][$lang]); ?></button>
    </div>
    
  </form>
</div>
<?php
}

// logout since FTP session is not persistant
ftp_userlogout ($conn_id);
?>

</body>
</html>