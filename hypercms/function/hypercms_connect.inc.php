<?php
/*
 * This file is part of
 * hyper Content Management Server - http://www.hypercms.com
 * Copyright (c) by hyper CMS Content Management Solutions GmbH
 *
 * You should have received a copy of the License along with hyperCMS.
 */
 
// ===================================== FTP FUNCTIONS =========================================

// ----------------------------------------- ftp_userlogon ---------------------------------------------
// function: ftp_userlogon()
// input: FTP servername or IP, user name, password, SSL [true,false] (optional)
// output: true / false on error

// description:
// this function connects and performs logon to an FTP server

function ftp_userlogon ($server, $user, $passwd, $ssl=false)
{
  global $mgmt_config;
  
  if ($server != "" && $user != "" && $passwd != "")
  {
    $conn_id = false;

    // connect to FTP server
    if ($ssl) $conn_id = ftp_ssl_connect ($server);
    else $conn_id = ftp_connect ($server);

    // verify connection
    if (!$conn_id)
    {
      $error[] = date('Y-m-d H:i:s')."|hypercms_connect.inc.php|error|20101|FTP: connection to ".$server." failed";
    }
    else
    {
      // login to FTP server
      $login_result = ftp_login ($conn_id, $user, $passwd);

      if (!$login_result)
      {
        $error[] = date('Y-m-d H:i')."|hypercms_connect.inc.php|information|20102|FTP: logon to ".$server." for FTP user ".$user." failed";
        
        // close connection
        ftp_close ($conn_id);
        
        $conn_id = false;
      }
    }
    
    // save log
    savelog (@$error);

    return $conn_id;
  }
  else return false;
}

// ----------------------------------------- ftp_userlogout ---------------------------------------------
// function: ftp_userlogout()
// input: FTP connection
// output: true / false on error

// description:
// this function disconnects from an FTP server

function ftp_userlogout ($conn_id)
{
  global $mgmt_config;
  
  if ($conn_id != "")
  {
    // close the FTP Connection
    return ftp_close ($conn_id);
  }
  else return false;
}

// ----------------------------------------- ftp_getfile ---------------------------------------------
// function: ftp_getfile()
// input: FTP connection, path to file on FTP server, passive mode [true,false] (optional)
// output: true / false on error

// description:
// this function gets a file from the FTP server

function ftp_getfile ($conn_id, $remote_file, $local_file, $passive=true)
{
  global $mgmt_config;
  
  if ($conn_id != "" && $local_file != "" && $remote_file != "" && ($passive == true || $passive == false))
  {
    $download = false;
    
    // set mode
    ftp_pasv ($conn_id, $passive);

    // download file
    $download = ftp_get ($conn_id, $local_file, $remote_file, FTP_BINARY);

    // verify download
    if (!$download) $error[] = date('Y-m-d H:i')."|hypercms_connect.inc.php|error|20201|FTP: download of ".$remote_file." to ".$local_file." has failed";
  
    // save log
    savelog (@$error);
    
    return $download;
  }
  else return false;
}

// ----------------------------------------- ftp_putfile ---------------------------------------------
// function: ftp_putfile()
// input: FTP connection, path to local file, path to file on FTP server, passive mode [true,false] (optional)
// output: true / false on error

// description:
// this function puts a file to the FTP server

function ftp_putfile ($conn_id, $local_file, $remote_file, $passive=true)
{
  global $mgmt_config;
  
  if ($conn_id != "" && $local_file != "" && $remote_file != "" && ($passive == true || $passive == false))
  {
    $upload = false;
    
    // set mode
    ftp_pasv ($conn_id, $passive);

    // upload file
    if (is_file ($local_file))
    {
      $upload = ftp_put ($conn_id, $remote_file, $local_file, FTP_BINARY);

      // verify upload
      if (!$upload) $error[] = date('Y-m-d H:i')."|hypercms_connect.inc.php|error|20103|FTP: upload of ".$local_file." to ".$remote_file." has failed";
    }
    else $error[] = date('Y-m-d H:i')."|hypercms_connect.inc.php|error|20105|FTP: local file ".$local_file." does not exist";
    
    // save log
    savelog (@$error);
    
    return $upload;
  }
  else return false;
}

// ----------------------------------------- ftp_filelist ---------------------------------------------
// function: ftp_filelist()
// input: FTP connection, path to remote directory (optional), passive mode [true,false] (optional)
// output: result array / false on error

// description:
// this function gets a file/directory listing of the FTP server

function ftp_filelist ($conn_id, $path=".", $passive=true)
{
  if ($conn_id != "")
  {
    ftp_pasv ($conn_id, true);
    
    if (is_array ($children = @ftp_rawlist ($conn_id, $path)))
    {
      $folders = array();
      $files = array();
      $items = array();
      
      foreach ($children as $child)
      {
        $chunks = preg_split ("/\s+/", $child);
        
        list ($item['rights'], $item['number'], $item['user'], $item['group'], $item['size'], $item['month'], $item['day'], $item['time']) = $chunks;
        
        // file or directory
        $item['type'] = $chunks[0]{0} === 'd' ? 'directory' : 'file';
        
        array_splice ($chunks, 0, 8);
        
        $name = implode (" ", $chunks);
        
        if ($item['type'] == "directory") $folders[$name] = $item;
        else $files[$name] = $item;
      }
      
      ksort ($folders, SORT_NATURAL);
      ksort ($files, SORT_NATURAL);
      
      $items = array_merge ($folders, $files);

      return $items;
    }
    else return false;
  }
  else return false;
}
?>