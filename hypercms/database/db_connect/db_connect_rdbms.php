<?php
// ================================================ db connect ================================================
// this file handles the data access and storage for relational database management systems. 
  
// ============================================ database functions ============================================
// the following input parameters are passed to the functions:

// $container_id: ID of the content container [integer]
// $object: converted path to the object [string]
// $template: name of the used template [string]
// $container: name of the content container: $container_id [string] (is unique inside hyperCMS over all sites)
// $text_array: content inside the XML-text-nodes of the content container 
// $user: name of the user who created the container [string]

// Class that manages the database access
class hcms_db
{
  private static $_ERR_TYPE = "Not supported type";
  
  // @var mysqli
  private $_db = NULL;
  
  private $_error = array();
  
  /**
   *
   * @var mysqli_result
   */
  public $_result = array();

  // Constructor that builds up the database connection
  // $type = Name of the database type
  // $user = Username who has access
  // $pass = Password for the user
  // $db = Name of the database
  // $host = Hostname of the database Server
  // $charset = Charset if applicable
  
  public function __construct ($type, $host, $user, $pass, $db, $charset="")
  {
    switch ($type)
    {
      case 'mysql':
        $this->_db = new mysqli ($host, $user, $pass, $db);
        if (mysqli_connect_error()) die ('Could not connect: ('.mysqli_connect_errno().') '.mysqli_connect_error());        
        if ($charset != "") $this->_db->set_charset ($charset);
        else $this->_db->set_charset ("utf8");
        break;
      case 'odbc':
        $this->_db = odbc_connect ($db, $user, $pass, SQL_CUR_USE_ODBC);
        if($this->_db == false) die ('Could not connect to odbc');
        break;
      default:
        die (self::$_ERR_TYPE.': '.$type);
    }
  }
  
  // Escapes the String according to the used dbtype
  // $string String to be escaped
  // Returns Escaped String
  
  public function escape_string ($string)
  {
    if ($this->_isMySqli())
    {
      if (is_array ($string))
      {
        foreach ($string as &$value) $value = $this->_db->escape_string($string);
        return $string;
      }
      else  return $this->_db->escape_string($string);
    }
    elseif ($this->_isODBC())
    {
      if (is_array ($string))
      {
        foreach ($string as &$value) $value = odbc_escape_string ($this->_db, $string);
        return $string;
      }
      else return odbc_escape_string ($this->_db, $string);
    }
    else
    {
      $this->_typeError();
    }
  }
  
  // Send a query to the database
  // $sql Statement to be sent to the server
  // $errCode Code for the Error which is inserted into the log
  // $date Date of the Query
  // $num Number where the result shall be stored. Needed for getRowCount and getResultRow
  // Returns true on success, false on failure
  
  public function query ($sql, $errCode, $date, $num=1)
  {
    global $mgmt_config;
    
    if (!is_string ($sql) && $sql == "")
    {
      $this->_typeError ();
    }
    elseif ($this->_isMySqli())
    {
      // log
      if ($mgmt_config['rdbms_log'])
      {
        $time_start = time();
        $log = array();
        $log[] = $mgmt_config['today']."|QUERY: ".$sql;
      }
    
      $result = $this->_db->query ($sql);
      
      // log
      if ($mgmt_config['rdbms_log'])
      {    
        $time_stop = time();
        $time = $time_stop - $time_start;
        $log[] = $mgmt_config['today']."|EXEC-TIME: ".$time." sec";
        savelog ($log, "sql");
      }   
      
      if ($result == false)
      {
        $this->_error[] = $date."|db_connect_rdbms.php|error|$errCode|".$this->_db->error.", SQL:".$sql;
        $this->_result[$num] = false;
        return false;
      }
      else
      {
        $this->_result[$num] = $result;
        return true;
      }
    }
    elseif ($this->_isODBC())
    {
      $result = odbc_exec ($this->_db, $sql);
      
      if ($result == false)
      {
        $this->_error[] = $date."|db_connect_rdbms.php|error|$errCode|ODBC Error Number: ".odbc_error().", SQL:".$sql;
        $this->_result[$num] = false;
        return false;
      }
      else
      {
        $this->_result[$num] = $result;
        return $result;
      }
    }
    else
    {
      $this->_typeError();
    }
  }
  
  // Returns the Errors that happened
  public function getError ()
  {
    return $this->_error;
  }
  
  // Returns the number of rows from the result stored under $num
  // $num the number defined in the $query call
  public function getNumRows ($num=1)
  {
    if ($this->_result[$num] == false)
    {
       return 0;
    }
    
    if ($this->_isMySqli ())
    {
      return $this->_db->affected_rows;
    }
    elseif ($this->_isODBC ())
    {
      return odbc_num_rows ($this->_result);
    }
    else
    {
      $this->_typeError ();
    }
  }
  
  // Returns the last inserted key (ID)
  
  public function getInsertId ()
  {
    global $mgmt_config;
    
    if ($this->_isMySqli ())
    {
      return $this->_db->insert_id;
    }
    elseif ($this->_isODBC ())
    {
      return odbc_cursor ($this->_result);
    }
    else
    {
      $this->_typeError ();
    }
  }
  
  // Closes the database connection and frees all results
  public function close()
  {
    if($this->_isMySqli ())
    {
      foreach ($this->_result as $result)
      {
        if ($result instanceof mysqli_result) $result->free ();
      }
      
      $this->_db->close();
    }
    elseif ($this->_isODBC ())
    {
      foreach ($this->_result as $result)
      {
        if ($result != false) @odbc_free_result ($result);
      }
      
      odbc_close ($this->_db);
    }
    else
    {
      $this->_typeError ();
    }
  }
  
  // Returns a row from the result set
  // $num the number defined in the $query call
  // $rowNumber optionally a rownumber
  // Returns the resultArray or NULL
  public function getResultRow ($num=1, $rowNumber=NULL)
  {
    if (empty ($this->_result[$num]) || $this->_result[$num] == false)
    {
       return NULL;
    }
    
    if ($this->_isMySqli ())
    {
      if (!is_null ($rowNumber))
      {
        $this->_result[$num]->data_seek ($rowNumber);
      }
      
      $return = $this->_result[$num]->fetch_array (MYSQLI_ASSOC);
           
      return $return;
    }
    elseif ($this->_isODBC ())
    {
      if (is_null ($rowNumber))
      {
        return @odbc_fetch_array ($this->_result[$num]);
      }
      else
      {
        return @odbc_fetch_array ($this->_result[$num], $rowNumber);
      }
    }
    else
    {
      $this->_typeError ();
    }
  }
  
  protected function _isMySqli ()
  {
    return ($this->_db instanceof mysqli);
  }
  
  protected function _isODBC ()
  {
    return (is_resource ($this->_db) && get_resource_type ($this->_db) == 'odbc link' );
  }
  
  protected function _typeError ()
  {
    die (self::$_ERR_TYPE);
  }
}

// ------------------------------------------------ ODBC escape string ------------------------------------------------

// description:
// Alternative to mysql_real_escape_string (PHP odbc_prepare would be optimal)

function odbc_escape_string ($connection, $value)
{
  if ($value != "")
  {
    $value = addslashes ($value);
    return $value;
  }
  else return "";
}

// ------------------------------------------------ convert dbcharset ------------------------------------------------

// function: convert_dbcharset()
// input: character set
// output: true / false

// description:
// Conversions from mySQL charset names to PHP charset names.

function convert_dbcharset ($charset)
{
  if ($charset != "")
  {
    $charset = strtolower ($charset);
    
    if ($charset == "utf8") $result = "UTF-8";
    elseif ($charset == "latin1") $result = "ISO-8859-1";
    elseif ($charset == "latin2") $result = "ISO-8859-2";
    else $result = false;
    
    return $result;
  }
  else return false;
}
 
// ------------------------------------------------ create object -------------------------------------------------

// function: rdbms_createobject()
// input: container ID, object path, template name, content container name, user name
// output: true / false

// description:
// Creates new object in database.

function rdbms_createobject ($container_id, $object, $template, $container, $user)
{
  global $mgmt_config;

  if (intval ($container_id) > 0 && $object != "" && $template != "" && (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0))
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
      
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
        
    $container_id = intval($container_id);
    $object = $db->escape_string($object);
    $template = $db->escape_string($template);
    if ($container != "") $container = $db->escape_string($container);
    if ($user != "") $user = $db->escape_string($user);
        
    $date = date ("Y-m-d H:i:s", time());
    $hash = createuniquetoken ();
    $object = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $object);
    if (strtolower (strrchr ($object, ".")) == ".off") $object = substr ($object, 0, -4);
    
    // check for existing object with same path (duplicate due to possible database error)
    $container_id_duplicate = rdbms_getobject_id ($object);
    
    if ($container_id_duplicate != "")
    {
      $result_delete = rdbms_deleteobject ($object);
      
      if ($result_delete)
      {
        $errcode = "20911";
        $error[] = $mgmt_config['today']."|db_connect_rdbms.inc.php|error|$errcode|duplicate object $object (ID: $container_id_duplicate) already existed in database and has been deleted";
      
        savelog (@$error);
      }
    }
    
    // insert values in table object
    $sql = 'INSERT INTO object (id, hash, objectpath, template) ';
    $sql .= 'VALUES ('.intval ($container_id).', "'.$hash.'", "'.$object.'", "'.$template.'")';
    
    $errcode = "50001";
    $db->query ($sql, $errcode, $mgmt_config['today']);

    // insert filetype in table media
    $file_ext = strrchr ($object, ".");
    $filetype = getfiletype ($file_ext);
        
    // insert values in table container
    if (!empty ($container) && !empty ($user) && !empty ($_SESSION['hcms_temp_latitude']) && is_numeric ($_SESSION['hcms_temp_latitude']) && !empty ($_SESSION['hcms_temp_longitude']) && is_numeric ($_SESSION['hcms_temp_longitude']))
    {
      $sql = 'INSERT INTO container (id, container, createdate, date, latitude, longitude, user) ';
      $sql .= 'VALUES ('.$container_id.', "'.$container.'", "'.$date.'", "'.$date.'", '.floatval($_SESSION['hcms_temp_latitude']).', '.floatval($_SESSION['hcms_temp_longitude']).', "'.$user.'")';
    }
    elseif (!empty ($container) && !empty ($user))
    {
      $sql = 'INSERT INTO container (id, container, createdate, date, user) ';
      $sql .= 'VALUES ('.$container_id.', "'.$container.'", "'.$date.'", "'.$date.'", "'.$user.'")';
    }
    elseif (!empty ($user))
    {
      $sql = 'UPDATE container SET user="'.$user.'", date="'.$date.'" ';
      $sql .= 'WHERE id='.$container_id.'';
    }
    else
    {
      $sql = 'UPDATE container SET date="'.$date.'" ';
      $sql .= 'WHERE id='.$container_id.'';
    }
    
    $errcode = "50002";
    $db->query ($sql, $errcode, $mgmt_config['today']);      

    // save log
    savelog ($db->getError ());          
    $db->close();
   
    return true;
  }
  else return false;
}

// ----------------------------------------------- get content -------------------------------------------------

// function: rdbms_copycontent()
// input: source container ID, destination container ID, user name
// output: true / false

// description:
// Selects content for a container in the database and inserts it for another container.

function rdbms_copycontent ($container_id_source, $container_id_dest, $user)
{
  global $mgmt_config;

  if (intval ($container_id_source) > 0 && intval ($container_id_dest) > 0 && valid_objectname ($user))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $container_id_source = intval($container_id_source);  
    $container_id_dest = intval($container_id_dest);
    $user = $db->escape_string($user);

    // copy textnodes
    $sql = 'SELECT * FROM textnodes WHERE id="'.$container_id_source.'"';
               
    $errcode = "50101";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'textnodes');

    if ($done)
    {
      while ($row = $db->getResultRow ('textnodes'))
      {
        $sql = 'INSERT INTO textnodes (id, text_id, textcontent, object_id, type, user) ';
        $sql .= 'VALUES ('.$container_id_dest.', "'.$row['text_id'].'", "'.$row['textcontent'].'", "'.$row['object_id'].'", "'.$row['type'].'", "'.$user.'")';
        
        $errcode = "50102";
        $db->query ($sql, $errcode, $mgmt_config['today']);
      }
    }
    
    // copy media
    $sql = 'SELECT * FROM media WHERE id="'.$container_id_source.'"';
               
    $errcode = "50103";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'media');

    if ($done)
    {
      if ($row = $db->getResultRow ('media'))
      {
        $sql = 'INSERT INTO media (id, filesize, filetype, width, height, red, green, blue, colorkey, imagetype, md5_hash) ';
        $sql .= 'VALUES ('.$container_id_dest.', "'.$row['filesize'].'", "'.$row['filetype'].'", "'.$row['width'].'", "'.$row['height'].'", "'.$row['red'].'", "'.$row['green'].'", "'.$row['blue'].'", "'.$row['colorkey'].'", "'.$row['imagetype'].'", "'.$row['md5_hash'].'")';
        
        $errcode = "50104";
        $db->query ($sql, $errcode, $mgmt_config['today']);
      }
    }
    
    // copy keywords
    $sql = 'SELECT * FROM keywords_container WHERE id="'.$container_id_source.'"';
               
    $errcode = "50105";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'keywords');

    if ($done)
    {
      while ($row = $db->getResultRow ('keywords'))
      {
        $sql = 'INSERT INTO keywords_container (id, keyword_id) ';
        $sql .= 'VALUES ('.$container_id_dest.', "'.$row['keyword_id'].'")';
        
        $errcode = "50106";
        $db->query ($sql, $errcode, $mgmt_config['today']);
      }
    }
    
    // copy taxonomy
    $sql = 'SELECT * FROM taxonomy WHERE id="'.$container_id_source.'"';
               
    $errcode = "50107";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'taxonomy');

    if ($done)
    {
      while ($row = $db->getResultRow ('taxonomy'))
      {
        $sql = 'INSERT INTO taxonomy (id, text_id, taxonomy_id, lang) ';
        $sql .= 'VALUES ('.$container_id_dest.', "'.$row['text_id'].'", "'.$row['taxonomy_id'].'", "'.$row['lang'].'")';
        
        $errcode = "50108";
        $db->query ($sql, $errcode, $mgmt_config['today']);
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();

    return true;
  }
  else return false;
}

// ----------------------------------------------- set content -------------------------------------------------

// function: rdbms_setcontent()
// input: publication name, container ID, content as array in form of array[text-ID]=text-content (optional), type as array in form of array[text-ID]=type (optional), user name (optional)
// output: true / false

// description:
// Saves the content in database.

function rdbms_setcontent ($site, $container_id, $text_array="", $type_array="", $user="")
{
  global $mgmt_config;

  if (intval ($container_id) > 0)
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $container_id = intval ($container_id);
    if ($user != "") $user = $db->escape_string($user);
    
    $date = date ("Y-m-d H:i:s", time());
    
    // update container
    $sql_attr = array();
    $sql_attr[0] = 'date="'.$date.'"';
    if ($user != "") $sql_attr[1] = 'user="'.$user.'"';
    
    if (is_array ($sql_attr) && sizeof ($sql_attr) > 0)
    {
      $sql = 'UPDATE container SET ';
      $sql .= implode (", ", $sql_attr).' ';    
      $sql .= 'WHERE id="'.$container_id.'"';
      
      $errcode = "50003";
      $db->query ($sql, $errcode, $mgmt_config['today'], 1);
    }

    // update text nodes
    if (is_array ($text_array) && sizeof ($text_array) > 0)
    {
      reset ($text_array);
      
      $i = 1;
      $update = false;
      
      while (list ($text_id, $text) = each ($text_array))
      {
        $i++;
        
        if ($text_id != "") 
        {
          $sql = 'SELECT id, textcontent, object_id FROM textnodes WHERE id="'.$container_id.'" AND text_id="'.$text_id.'"';
               
          $errcode = "50004";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], $i);

          if ($done)
          {
            $row = $db->getResultRow ($i);
            
            // define type
            if (!empty ($type_array[$text_id]))
            {
              $type = $type_array[$text_id];
              
              // add text prefix if only text type letter has been provided
              if ($type == "u" || $type == "f" || $type == "l" || $type == "c" || $type == "d" || $type == "k") $type = "text".$type;
            }
            else $type = "";
            
            // only save content in database if content has been changed
            if (empty ($row['text_id']) || ($row['textcontent'] != cleancontent ($text, convert_dbcharset ($mgmt_config['dbcharset'])) && $row['object_id']."|".$row['textcontent'] != cleancontent ($text, convert_dbcharset ($mgmt_config['dbcharset']))))
            {
              // content has been changed
              $update = true;

              // prepare text value for link and media items
              if ((strpos ("_".$text_id, "link:") > 0 || strpos ("_".$text_id, "media:") > 0 || strpos ("_".$text_id, "comp:") > 0) && strpos ("_".$text, "|") > 0)
              {
                // delete entries for multiple components, example for text ID: comp:compname:0
                if (strpos ("_".$text_id, "comp:") > 0 && substr_count ($text_id, ":") == 2)
                {
                  $text_id_base = substr ($text_id, 0, strrpos ($text_id, ":"));
                  $sql = 'DELETE FROM textnodes WHERE id="'.$container_id.'" AND text_id LIKE "'.$text_id.':%"';
                       
                  $errcode = "50007";
                  $done = $db->query ($sql, $errcode, $mgmt_config['today'], $i);
                }
              
                // extract object ID
                $object_id = substr ($text, 0, strpos ($text, "|"));
                $text = substr ($text, strpos ($text, "|") + 1);
                
                // check and get object ID from object path
                if ($object_id != "" && intval ($object_id) < 1) $object_id = rdbms_getobject_id ($object_id);
                
                // recheck
                if ($object_id != "" && intval ($object_id) < 1) $object_id = 0;
              }
              else $object_id = 0;
              
              // clean text (will also HTML decode)
              if ($text != "")
              {
                $text = cleancontent ($text, convert_dbcharset ($mgmt_config['dbcharset']));
                $text = $db->escape_string($text);
              }
              
              $num_rows = $db->getNumRows ($i);          
            
              if ($num_rows > 0)
              {
                // query 
                $sql = 'UPDATE textnodes SET textcontent="'.$text.'", object_id="'.$object_id.'", user="'.$user.'" ';
                if ($type != "") $sql .= ', type="'.$type.'" ';
                $sql .= 'WHERE id="'.$container_id.'" AND text_id="'.$text_id.'"';
  
                $errcode = "50005";
                $db->query ($sql, $errcode, $mgmt_config['today'], ++$i);
              }
              elseif ($num_rows == 0)
              {
                // query    
                $sql = 'INSERT INTO textnodes (id, text_id, textcontent, object_id'.($type != "" ? ', type' : '').', user) ';
                $sql .= 'VALUES ('.$container_id.', "'.$text_id.'", "'.$text.'", "'.$object_id.'"'.($type != "" ? ', "'.$type.'"' : '').', "'.$user.'")';
  
                $errcode = "50006";
                $db->query ($sql, $errcode, $mgmt_config['today'], ++$i);
              }
            }
          }
        }
      }
      
      if ($update == true)
      {
        // set taxonomy
        settaxonomy ($site, $container_id);
      
        // set keywords
        rdbms_setkeywords ($site, $container_id);
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();
    
    return true;
  }
  else return false;
}

// ----------------------------------------------- set keywords -------------------------------------------------

// function: rdbms_setkeywords()
// input: publication name, container ID, content as array in form of array[text-ID]=text-content
// output: true / false

// description:
// Analyzes the keyword content regarding its keywords, saves results in database.

function rdbms_setkeywords ($site, $container_id)
{
  global $mgmt_config;

  if (valid_publicationname ($site) && intval ($container_id) > 0)
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $container_id = intval ($container_id);

    // memory for all used keyword IDs
    $memory = array();
    $keywords_array = array();
    
    // select keyword content for container
    $sql = 'SELECT textcontent FROM textnodes WHERE id="'.$container_id.'" AND type="textk"';
    
    $errcode = "50300";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'select1');
    
    if ($done)
    {
      while ($row = $db->getResultRow ('select1'))
      {
        // extract keywords
        if (trim ($row['textcontent']) != "")
        {
          $keywords_add = splitkeywords ($row['textcontent']);

          if (is_array ($keywords_add)) $keywords_array = array_merge ($keywords_array, $keywords_add);
        }
      }
    }

    // if keywords have been extracted
    if (is_array ($keywords_array) && sizeof ($keywords_array) > 0)
    {
      // remove duplicates
      $keywords_array = array_unique ($keywords_array);

      foreach ($keywords_array as $keyword)
      {
        // select keyword ID
        $sql = 'SELECT keyword_id FROM keywords WHERE keyword="'.$keyword.'"';
             
        $errcode = "50301";
        $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'select2');

        // keyword exists  
        if ($done && $db->getNumRows ('select2') > 0)
        {
          $row = $db->getResultRow ('select2');
          
          $memory[] = $keyword_id = $row['keyword_id'];

          // select container ID
          $sql = 'SELECT id FROM keywords_container WHERE keyword_id='.$keyword_id.' AND id='.$container_id;
        
          $errcode = "50302";
          $db->query ($sql, $errcode, $mgmt_config['today'], 'select2');
        
          // container ID does not exist
          if ($db->getNumRows ('select2') < 1)
          {
            // insert new taxonomy entries    
            $sql = 'INSERT INTO keywords_container (id, keyword_id) VALUES ('.$container_id.', '.$keyword_id.')';

            $errcode = "50303";
            $db->query ($sql, $errcode, $mgmt_config['today'], 'insert1');
          }
        }
        // keyword does not exist
        else
        {
          $keyword = $db->escape_string($keyword);
          
          // insert new keyword  
          $sql = 'INSERT INTO keywords (keyword) VALUES ("'.$keyword.'");';
          
          $errcode = "50304";
          $db->query ($sql, $errcode, $mgmt_config['today'], 'insert2');

          // get last keyword ID
          $memory[] = $keyword_id = $db->getInsertId();
          
          // insert new keyword container relationship    
          $sql = 'INSERT INTO keywords_container (id, keyword_id) VALUES ('.$container_id.', '.$keyword_id.')';

          $errcode = "50305";
          $db->query ($sql, $errcode, $mgmt_config['today'], 'insert3');
        }
      }
    }

    // remove all unused keywords for container
    if (sizeof ($memory) > 0)
    {
      $sql = 'DELETE FROM keywords_container WHERE id='.$container_id.' AND keyword_id NOT IN ('.implode (",", $memory).')';
    }
    // no keywords provided by container
    else
    {
      $sql = 'DELETE FROM keywords_container WHERE id='.$container_id.'';
    }

    $errcode = "50307";
    $db->query ($sql, $errcode, $mgmt_config['today'], 'delete');

    // save log
    savelog ($db->getError ());    
    $db->close();
  
    return true;
  }
  else return false;
}

// ----------------------------------------------- set keywords for a publication ------------------------------------------------- 

// function: rdbms_setpublicationkeywords()
// input: publication name, recreate [true,false]
// output: true / false

// description:
// Saves all keywords of a publication in database.

function rdbms_setpublicationkeywords ($site, $recreate=false)
{
  global $mgmt_config;

  if (valid_publicationname ($site))
  {
    // remove all taxonomy entries from publication
    if ($recreate == true) rdbms_deletepublicationkeywords ($site);
    
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    // clean input
    $site_escaped = $db->escape_string ($site);

    // select container IDs with keywords
    $sql = "SELECT DISTINCT textnodes.id FROM textnodes INNER JOIN object ON textnodes.id=object.id WHERE textnodes.textcontent!='' AND textnodes.type='textk'";
    $sql .= " AND (object.objectpath LIKE _utf8'*page*/".$site_escaped."/%' COLLATE utf8_bin OR object.objectpath LIKE _utf8'*comp*/".$site_escaped."/%' COLLATE utf8_bin)";

    $errcode = "50033";
    $done = $db->query ($sql, $errcode, $mgmt_config['today']);
  
    if ($done)  
    {
      $text_array = array();
      
      while ($row = $db->getResultRow ())
      {
        rdbms_setkeywords ($site, $row['id']);
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();    
      
    return true;
  }
  else return false;
}

// ----------------------------------------------- set taxonomy -------------------------------------------------

// function: rdbms_settaxonomy()
// input: publication name, container ID, taxonomy array in form of array[text-ID][lang][taxonomy-ID]=keyword
// output: true / false

// description:
// Saves the used taxonomy IDs of a container in database if the taxonomy is enabled for the publication.

function rdbms_settaxonomy ($site, $container_id, $taxonomy_array)
{
  global $mgmt_config;
  
  if (valid_publicationname ($site) && intval ($container_id) > 0 && is_array ($taxonomy_array) && is_array ($mgmt_config))
  {
    // load publication management config
    if (!isset ($mgmt_config[$site]['taxonomy']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
    {
      require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
    }
    
    // taxonomy is enabled
    if (!empty ($mgmt_config[$site]['taxonomy']))
    {
      $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
  
      $container_id = intval ($container_id);
      
      // taxonomy_array syntax:
      // $taxonomy_array[text_id][lang][taxonomy_id] = taxonomy_keyword
      foreach ($taxonomy_array as $text_id=>$tx_lang_array)
      {
        // delete taxonomy entries with same text ID
        $sql = 'DELETE FROM taxonomy WHERE id="'.$container_id.'" AND text_id="'.$text_id.'"';
             
        $errcode = "50201";
        $db->query ($sql, $errcode, $mgmt_config['today'], 'delete');
              
        foreach ($tx_lang_array as $lang=>$tx_keyword_array)
        {
          foreach ($tx_keyword_array as $taxonomy_id=>$taxonomy_keyword)
          {
            if ($text_id != "" && intval ($taxonomy_id) >= 0 && $lang != "")
            {
              $text_id = $db->escape_string($text_id);
              $taxonomy_id = intval ($taxonomy_id);
              $lang = $db->escape_string($lang);

              // insert new taxonomy entries    
              $sql = 'INSERT INTO taxonomy (id, text_id, taxonomy_id, lang) ';      
              $sql .= 'VALUES ('.$container_id.', "'.$text_id.'", "'.$taxonomy_id.'", "'.$lang.'")';  
      
              $errcode = "50202";
              $db->query ($sql, $errcode, $mgmt_config['today'], 'insert');
            }
          }
        }
      }
      

      // save log
      savelog ($db->getError ());    
      $db->close();
    
      return true;
    }
    return false;
  }
  else return false;
}

// ----------------------------------------- set taxonomy for a publication --------------------------------------------

// function: rdbms_setpublicationtaxonomy()
// input: publication name, recreate [true,false]
// output: true / false

// description:
// Saves all taxonomy keywords of a publication in the database.

function rdbms_setpublicationtaxonomy ($site, $recreate=false)
{
  global $mgmt_config;
  
  // load publication management config
  if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['taxonomy']) && is_file ($mgmt_config['abs_path_data']."config/".$site.".conf.php"))
  {
    require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
  }
  
  // if taxonomy is enabled
  if (!empty ($mgmt_config[$site]['taxonomy']))
  {
    // remove all taxonomy entries from publication
    if ($recreate == true) rdbms_deletepublicationtaxonomy ($site, true);
    
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // select containers of publication
    if ($recreate == true)
    {
      $sql = 'SELECT id FROM object WHERE objectpath LIKE _utf8"*comp*/'.$site.'/" COLLATE utf8_bin OR objectpath LIKE _utf8"*page*/'.$site.'/" COLLATE utf8_bin';
    }
    else
    {
      $sql = 'SELECT object.id FROM object INNER JOIN textnodes ON textnodes.id=object.id LEFT JOIN taxonomy ON taxonomy.id=object.id WHERE (object.objectpath LIKE _utf8"*comp*/'.$site.'/%" COLLATE utf8_bin OR object.objectpath LIKE _utf8"*page*/'.$site.'/%" COLLATE utf8_bin) AND textnodes.textcontent!="" AND taxonomy.id IS NULL';
    }
    
    $errcode = "50353";
    $containers = $db->query($sql, $errcode, $mgmt_config['today'], 'containers');
    
    if ($containers)
    {
      while ($row = $db->getResultRow ('containers'))
      {
        // set taxonomy for container
        if (!empty ($row1['id'])) settaxonomy ($site, $row1['id']);
      }
    }
    
    // save log
    savelog ($db->getError ());    
    $db->close();
        
    return true;
  }
  else return false;
}

// ----------------------------------------------- set template -------------------------------------------------

// function: rdbms_settemplate()
// input: object path, template file name
// output: true / false

// description:
// Saves the template for an object in the database.

function rdbms_settemplate ($object, $template)
{
  global $mgmt_config;
  
  if ($object != "" && $template != "" && (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0))
  {    
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
      
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $object = $db->escape_string ($object);
    $template = $db->escape_string ($template);
            
    $object = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $object);

    // update object
    $sql = 'UPDATE object SET template="'.$template.'" WHERE objectpath=_utf8"'.$object.'" COLLATE utf8_bin'; 
    
    $errcode = "50007";
    $db->query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->getError ());    
    $db->close();
        
    return true;
  }
  else return false;
} 

// ----------------------------------------------- set media attributes -------------------------------------------------

// function: rdbms_setmedia()
// input: container ID, file size in KB (optional), file type (optional), width in pixel (optional), heigth in pixel (optional), red color (optional), green color (optional), blue color (optional), colorkey (optional), image type (optional), MD5 hash (optional)
// output: true / false

// description:
// Saves media attributes in the database.

function rdbms_setmedia ($id, $filesize="", $filetype="", $width="", $height="", $red="", $green="", $blue="", $colorkey="", $imagetype="", $md5_hash="")
{
  global $mgmt_config;
  
  if ($id != "" && ($filesize != "" || $filetype != "" || $width != "" || $height != "" || $red != "" || $green != "" || $blue != "" || $colorkey != "" || $imagetype != "" || $md5_hash != ""))
  {    
    
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    if ($filesize != "") $filesize = $db->escape_string ($filesize);
    if ($width != "") $width = $db->escape_string ($width);
    if ($height != "") $height = $db->escape_string ($height);
    if ($red != "") $red = $db->escape_string ($red);
    if ($green != "") $green = $db->escape_string ($green);
    if ($blue != "") $blue = $db->escape_string ($blue);
    if ($colorkey != "") $colorkey = $db->escape_string ($colorkey);
    if ($imagetype != "") $imagetype = $db->escape_string ($imagetype);
    if ($md5_hash != "") $md5_hash = $db->escape_string ($md5_hash);
        
    // check for existing record
    $sql = 'SELECT id FROM media WHERE id='.intval($id); 
    
    $errcode = "50008";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {
      $num_rows = $db->getNumRows('select');

      // insert media attributes
      if ($num_rows == 0)
      {
        $sql = 'INSERT INTO media (id, filesize, filetype, width, height, red, green, blue, colorkey, imagetype, md5_hash) '; 
        $sql .= 'VALUES ('.intval($id).','.intval($filesize).',"'.$filetype.'",'.intval($width).','.intval($height).','.intval($red).','.intval($green).','.intval($blue).',"'.$colorkey.'","'.$imagetype.'","'.$md5_hash.'")';      
      }
      // update media attributes
      else
      {
        $sql_update = array();

        if ($filesize != "") $sql_update[] = 'filesize='.intval($filesize);
        if ($filetype != "") $sql_update[] = 'filetype="'.$filetype.'"'; 
        if ($width != "") $sql_update[] = 'width='.intval($width);
        if ($height != "") $sql_update[] = 'height='.intval($height);
        if ($red != "") $sql_update[] = 'red='.intval($red);
        if ($green != "") $sql_update[] = 'green='.intval($green);
        if ($blue != "") $sql_update[] = 'blue='.intval($blue);
        if ($colorkey != "") $sql_update[] = 'colorkey="'.$colorkey.'"';
        if ($imagetype != "") $sql_update[] = 'imagetype="'.$imagetype.'"';
        if ($md5_hash != "") $sql_update[] = 'md5_hash="'.$md5_hash.'"';

        if (sizeof ($sql_update) > 0)
        {
          $sql = 'UPDATE media SET ';
          $sql .= implode (", ", $sql_update);
          $sql .= ' WHERE id="'.intval($id).'"';
        }
      }

      $errcode = "50009";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'update');
    }

    // save log
    savelog ($db->getError ());    
    $db->close();
        
    return true;
  }
  else return false;
}

// ------------------------------------------------ get media attributes -------------------------------------------------

// function: rdbms_getmedia()
// input: container ID, extended media object information [true,false] (optional)
// output: result array with media object details / false on error

// description:
// Reads all media object details.

function rdbms_getmedia ($container_id, $extended=false)
{
  global $mgmt_config;

  if ($container_id != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // clean input
    $container_id = intval ($container_id);  
    
    // get media info
    if ($extended == true) $sql = 'SELECT med.*, cnt.createdate, cnt.date, cnt.latitude, cnt.longitude, cnt.user FROM media AS med, container AS cnt WHERE med.id=cnt.id AND med.id='.intval($container_id).'';   
    else $sql = 'SELECT * FROM media WHERE id="'.$container_id.'"';   

    $errcode = "50067";
    $done = $db->query ($sql, $errcode, $mgmt_config['today']);
    
    if ($done && $row = $db->getResultRow ())
    {
      $media = $row;   
    }

    // save log
    savelog ($db->getError());    
    $db->close();      
         
    if (!empty ($media) && is_array ($media)) return $media;
    else return false;
  }
  else return false;
}

// ------------------------------------------------ get duplicate file -------------------------------------------------

function rdbms_getduplicate_file ($site, $md5_hash)
{
  global $mgmt_config;

  if ($site != "" && $md5_hash != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // clean input
    $md5_hash = $db->escape_string ($md5_hash);
    $site = $db->escape_string ($site);
    
    // get media info
    $sql = 'SELECT * FROM media INNER JOIN object ON object.id=media.id WHERE md5_hash="'.$md5_hash.'" AND objectpath LIKE "*comp*/'.$site.'/%"';

    $errcode = "50067";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'main');
    
    $media = array();
    
    if ($done)
    {
      while ($row = $db->getResultRow ('main'))
      {
        $row['objectpath'] = str_replace (array("*page*", "*comp*"), array("%page%", "%comp%"), $row['objectpath']);
        $media[] = $row;
      }
    }
    
    // save log
    savelog ($db->getError());    
    $db->close();      
         
    if (is_array ($media) && !empty($media)) return $media;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- rename object -------------------------------------------------

function rdbms_renameobject ($object_old, $object_new)
{
  global $mgmt_config;
  
  if ($object_old != "" && $object_new != "" && (substr_count ($object_old, "%page%") > 0 || substr_count ($object_old, "%comp%") > 0) && (substr_count ($object_new, "%page%") > 0 || substr_count ($object_new, "%comp%") > 0))
  {  
    // correct object names
    if (strtolower (strrchr ($object_old, ".")) == ".off") $object_old = substr ($object_old, 0, -4);
    if (strtolower (strrchr ($object_new, ".")) == ".off") $object_new = substr ($object_new, 0, -4);
    
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // remove seperator
    $object_old = str_replace ("|", "", $object_old);
    $object_new = str_replace ("|", "", $object_new); 
    
    $object_old = $db->escape_string ($object_old);
    $object_new = $db->escape_string ($object_new);
       
    // replace %
    $object_old = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $object_old);
    $object_new = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $object_new);
    
    // query
    $sql = 'SELECT object_id, id, objectpath FROM object '; 
    $sql .= 'WHERE objectpath LIKE _utf8"'.$object_old.'%" COLLATE utf8_bin';
    
    $errcode = "50010";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');

    $i = 1;
    
    if ($done)
    {
      while ($row = $db->getResultRow ('select'))
      {
        $object_id = $row['object_id'];
        $container_id = $row['id'];
        $object = $row['objectpath'];
        $object = str_replace ($object_old, $object_new, $object);
        $fileext = strrchr ($object, ".");
        $filetype = getfiletype ($fileext);

        // update object 
        $sql = 'UPDATE object SET objectpath="'.$object.'" WHERE object_id="'.$object_id.'"';
        
        $errcode = "50011";
        $db->query ($sql, $errcode, $mgmt_config['today'], $i++);        
        
        // update media file-type
        if ($filetype != "")
        {
          $sql = 'UPDATE media SET filetype="'.$filetype.'" WHERE id="'.$container_id.'"';
  
          $errcode = "50012";
          $db->query ($sql, $errcode, $mgmt_config['today'], $i++);
        }
      }
    }
    
    // save log
    savelog ($db->getError ());    
    $db->close();
         
    return true;
  }
  else return false;
} 

// ----------------------------------------------- delete object ------------------------------------------------- 

function rdbms_deleteobject ($object, $object_id="")
{
  global $mgmt_config;

  if (($object != "" && (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0)) || $object_id > 0)
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
    
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    if ($object != "")
    {
      $object = $db->escape_string ($object);
    
      // replace %
      $object = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $object);
    }
    
    // query
    $sql = 'SELECT id FROM object ';
    
    if ($object != "") $sql .= 'WHERE objectpath=_utf8"'.$object.'" COLLATE utf8_bin';
    elseif ($object_id > 0) $sql .= 'WHERE object_id="'.intval ($object_id).'"';
       
    $errcode = "50012";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select1');
    
    if ($done)
    {
      $row = $db->getResultRow('select1');
    
      if ($row)
      {
        $container_id = $row['id']; 

        $sql = 'SELECT object_id FROM object ';
        $sql .= 'WHERE id='.$container_id.'';

        $errcode = "50013";
        $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select2');
        
        if ($done)
        {
          $row_id = $db->getResultRow ('select2');
          $num_rows = $db->getNumRows ('select2');
        }
        
        // delete all entries for this id since no connected objects exists
        if ($row_id && $num_rows == 1)
        {
          // delete object
          $sql = 'DELETE FROM object WHERE id="'.$container_id.'"';

          $errcode = "50014";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete1');

          // delete container
          $sql = 'DELETE FROM container WHERE id="'.$container_id.'"';   

          $errcode = "50014";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete2');

          // delete textnodes  
          $sql = 'DELETE FROM textnodes WHERE id="'.$container_id.'"';

          $errcode = "50015";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete3');

          // delete taxonomy  
          $sql = 'DELETE FROM taxonomy WHERE id="'.$container_id.'"';

          $errcode = "50024";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete3');
          
          // delete keywords
          $sql = 'DELETE FROM keywords_container WHERE id="'.$container_id.'"';

          $errcode = "50025";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete4');
          
          // delete media attributes  
          $sql = 'DELETE FROM media WHERE id="'.$container_id.'"';

          $errcode = "50016";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete5');

          // delete dailytstat 
          $sql = 'DELETE FROM dailystat WHERE id="'.$container_id.'"';

          $errcode = "50017";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete6');        

          // delete queue
          $sql = 'DELETE FROM queue WHERE object_id="'.$row_id['object_id'].'"';

          $errcode = "50018";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete7');
          
          // delete accesslink
          $sql = 'DELETE FROM accesslink WHERE object_id="'.$row_id['object_id'].'"';

          $errcode = "50019";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete8');
          
          // delete task
          $sql = 'DELETE FROM task WHERE object_id="'.$row_id['object_id'].'"';

          $errcode = "50023";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete9');    
        }
        // delete only the object reference and queue entry
        elseif ($row_id && $num_rows > 1)
        {
          $sql = 'DELETE FROM object WHERE objectpath=_utf8"'.$object.'" COLLATE utf8_bin';

          $errcode = "50020";
          $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete10');
        }

        // delete queue
        $sql = 'DELETE FROM queue WHERE object_id="'.$row_id['object_id'].'"';   

        $errcode = "50021";
        $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete11');
        
        // delete notification
        $sql = 'DELETE FROM notify WHERE object_id="'.$row_id['object_id'].'"';   

        $errcode = "50022";
        $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'delete12');
        
        // delete/update textnodes
        $sql = 'UPDATE textnodes SET object_id="" WHERE object_id="'.$row_id['object_id'].'"';   

        $errcode = "50023";
        $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'update1');
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();
         
    return true;
  }
  else return false;
}

// ----------------------------------------------- delete content -------------------------------------------------

function rdbms_deletecontent ($site, $container_id, $text_id)
{
  global $mgmt_config;
  
  if (intval ($container_id) > 0 && $text_id != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $container_id = intval ($container_id);
    $text_id = $db->escape_string ($text_id);
    
    // delete textnodes
    $sql = 'DELETE FROM textnodes WHERE id="'.$container_id.'" AND text_id="'.$text_id.'"';
       
    $errcode = "50021";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // delete taxonomy
    $sql = 'DELETE FROM taxonomy WHERE id="'.$container_id.'" AND text_id="'.$text_id.'"';
       
    $errcode = "50028";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // save log
    savelog ($db->getError ());    
    $db->close();
        
    return true;
  }
  else return false;
}

// ------------------------------------------ delete keywords of a publication --------------------------------------------

function rdbms_deletepublicationkeywords ($site)
{
  global $mgmt_config;
  
  // load publication management config
  if (valid_publicationname ($site))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // select containers of publication
    $sql = 'SELECT DISTINCT id FROM object WHERE objectpath LIKE _utf8"*comp*/'.$site.'/" COLLATE utf8_bin OR objectpath LIKE _utf8"*page*/'.$site.'/" COLLATE utf8_bin';
       
    $errcode = "50053";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');
    
    if ($done)
    {
      while ($row = $db->getResultRow ('select'))
      {
        // delete taxonomy
        $sql = 'DELETE FROM keywords_container WHERE id="'.$row['id'].'"';
           
        $errcode = "50054";
        $db->query ($sql, $errcode, $mgmt_config['today']);
      }
    }
    
    // save log
    savelog ($db->getError ());    
    $db->close();
        
    return true;
  }
  else return false;
}

// ------------------------------------------ delete taxonomy of a publication --------------------------------------------

function rdbms_deletepublicationtaxonomy ($site, $force=false)
{
  global $mgmt_config;
  
  // load publication management config
  if (valid_publicationname ($site) && !isset ($mgmt_config[$site]['taxonomy']))
  {
    require ($mgmt_config['abs_path_data']."config/".$site.".conf.php");
  }
  
  // if taxonomy is disabled
  if (empty ($mgmt_config[$site]['taxonomy']) || $force == true)
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // select containers of publication
    $sql = 'SELECT DISTINCT id FROM object WHERE objectpath LIKE _utf8"*comp*/'.$site.'/" COLLATE utf8_bin OR objectpath LIKE _utf8"*page*/'.$site.'/" COLLATE utf8_bin';
       
    $errcode = "50053";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');
    
    if ($done)
    {
      while ($row = $db->getResultRow ('select'))
      {
        // delete taxonomy
        $sql = 'DELETE FROM taxonomy WHERE id="'.$row['id'].'"';
           
        $errcode = "50054";
        $db->query ($sql, $errcode, $mgmt_config['today']);
      }
    }
    
    // save log
    savelog ($db->getError ());    
    $db->close();
        
    return true;
  }
  else return false;
}

// ----------------------------------------------- search content ------------------------------------------------- 

// function: rdbms_searchcontent()
// input: location (optional), exlude locations/folders (optional), object-type (optional), filter for start modified date (optional), filter for end modified date (optional), 
//        filter for template name (optional), search expression as array (optional), search expression for object/file name (optional), 
//        filter for files size in KB in form of [>=,<=]file-size-in-KB (optional), image width in pixel (optional), image height in pixel (optional), primary image color as array (optional), image-type (optional), 
//        SW geo-border as float value (optional), NE geo-border as float value (optional), maximum search results/hits to return (optional), count search result entries [true,false] (optional), log search expression [true/false] (optional), taxonomy level to include  as integer (optional)
// output: result array with object paths of all found objects / false

// description:
// Searches one or more expressions in the content.

function rdbms_searchcontent ($folderpath="", $excludepath="", $object_type="", $date_from="", $date_to="", $template="", $expression_array="", $expression_filename="", $filesize="", $imagewidth="", $imageheight="", $imagecolor="", $imagetype="", $geo_border_sw="", $geo_border_ne="", $maxhits=1000, $count=false, $search_log=true, $taxonomy_level=2)
{
  // user will be provided as global for search expression logging
  global $mgmt_config, $lang, $user;
  
  // enable search log by default
  $mgmt_config['search_log'] = true;

  // set object_type if the search is image or video related
  if (!is_array ($object_type) && (!empty ($imagewidth) || !empty ($imageheight) || !empty ($imagecolor) || !empty ($imagetype)))
  {
    $object_type = array("image", "video", "flash");
  }
  
  // if hierarchy URL has been provided
  if (!empty ($expression_array[0]) && strpos ("_".$expression_array[0], "%hierarchy%/") > 0)
  {
    // disable search log
    $mgmt_config['search_log'] = false;

    // analyze hierarchy
    $hierarchy_url = trim ($expression_array[0], "/");
    $hierarchy_array = explode ("/", $hierarchy_url);
    
    if (is_array ($hierarchy_array))
    {
      // look for the exact expression
      $mgmt_config['search_exact'] = true;
      
      // analyze hierarchy URL
      $domain = $hierarchy_array[0];
      $site = $hierarchy_array[1];
      $name = $hierarchy_array[2];
      $level = $hierarchy_array[3];
      
      $expression_array = array();
      
      foreach ($hierarchy_array as $hierarchy_element)
      {
        if (strpos ($hierarchy_element, "=") > 0)
        {
          list ($key, $value) = explode ("=", $hierarchy_element);

          // unescape /, : and = in value
          $value = str_replace ("&#47;", "/", $value);
          $value = str_replace ("&#58;", ":", $value);
          $value = str_replace ("&#61;", "=", $value);

          $expression_array[$key] = $value;
        }
      }
    }
  }
  else
  {
    // get publication for taxonomy based search (only applies if search is publication specific)
    if (!empty ($folderpath))
    {
      if (is_string ($folderpath))
      {
        $site = getpublication ($folderpath);
      }
      elseif (is_array ($folderpath) && sizeof ($folderpath) > 0)
      {
        foreach ($folderpath as $temp)
        {
          $site = getpublication ($temp);
  
          // if the name changed
          if (!empty ($site_prev) && $site != $site_prev)
          {
            unset ($site);
            break;
          }
          
          // remember as previous name
          $site_prev = $site;
        }
      }
    }
  }

  if (!empty ($folderpath) || is_array ($object_type) || !empty ($date_from) || !empty ($date_to) || !empty ($template) || is_array ($expression_array) || !empty ($expression_filename) || !empty ($filesize) || !empty ($imagewidth) || !empty ($imageheight) || !empty ($imagecolor) || !empty ($imagetype))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    if (is_array ($object_type)) foreach ($object_type as &$value) $value = $db->escape_string ($value);
    if ($date_from != "") $date_from = $db->escape_string ($date_from);
    if ($date_to != "") $date_to = $db->escape_string ($date_to);
    if ($template != "") $template = $db->escape_string ($template);
    if ($maxhits != "")
    {
      if (strpos ($maxhits, ",") > 0)
      {
        list ($starthits, $endhits) = explode (",", $maxhits);
        $starthits = $db->escape_string (trim ($starthits));
        $endhits = $db->escape_string (trim ($endhits));
      }
      else $maxhits = $db->escape_string ($maxhits);
    }
    
    // AND/OR operator for the search in texnodes 
    if (isset ($mgmt_config['search_operator']) && (strtoupper ($mgmt_config['search_operator']) == "AND" || strtoupper ($mgmt_config['search_operator']) == "OR"))
    {
      $operator = strtoupper ($mgmt_config['search_operator']);
    }
    else $operator = "AND";
    
    $sql_table = array();
    $sql_where = array();

    // folder path => consider folderpath only when there is no filenamecheck
    if (!empty ($folderpath))
    {
      if (!is_array ($folderpath) && $folderpath != "") $folderpath = array ($folderpath);      
      $sql_puffer = array();
      
      foreach ($folderpath as $path)
      {
        if ($path != "")
        {
          //escape characters depending on dbtype
          $path = $db->escape_string ($path);
          // replace %
          $path = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $path);
          // where clause for folderpath
          $sql_puffer[] = 'obj.objectpath LIKE _utf8"'.$path.'%" COLLATE utf8_bin';
        }
      }
      
      if (is_array ($sql_puffer) && sizeof ($sql_puffer) > 0) $sql_where['folderpath'] = '('.implode (" OR ", $sql_puffer).')';
    }
    
    // excludepath path
    if (!empty ($excludepath))
    {
      if (!is_array ($excludepath) && $excludepath != "") $excludepath = array ($excludepath);
      $sql_puffer = array();
      
      foreach ($excludepath as $path)
      {
        if ($path != "")
        {
          // explicitly exclude folders from result
          if ($path == "/.folder")
          {
            // where clause for excludepath
            $sql_puffer[] = 'obj.objectpath NOT LIKE _utf8"%'.$path.'" COLLATE utf8_bin';
          }
          else
          {
            //escape characters depending on dbtype
            $path = $db->escape_string ($path);
            // replace %
            $path = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $path);
            // where clause for excludepath
            $sql_puffer[] = 'obj.objectpath NOT LIKE _utf8"'.$path.'%" COLLATE utf8_bin';
          }
        }
      }
      
      if (is_array ($sql_puffer) && sizeof ($sql_puffer) > 0) $sql_where['excludepath'] = '('.implode (" AND ", $sql_puffer).')';
    }
    
    // add file name search if expression array is of size 1
    if (empty ($expression_filename) && is_array ($expression_array) && sizeof ($expression_array) == 1 && !empty ($expression_array[0]) && strpos ("_".$expression_array[0], "%taxonomy%/") < 1 && strpos ("_".$expression_array[0], "%keyword%/") < 1) 
    {
      $expression_filename = $expression_array[0];
    }

    // query file name (transform special characters)
    if (!empty ($expression_filename))
    {
      $expression_filename = str_replace ("*", "-hcms_A-", $expression_filename); 
      $expression_filename = str_replace ("?", "-hcms_Q-", $expression_filename); 
      $expression_filename = specialchr_encode ($expression_filename); 
      $expression_filename = str_replace ("-hcms_A-", "*", $expression_filename); 
      $expression_filename = str_replace ("-hcms_Q-", "?", $expression_filename);
       
      $expression_filename_conv = $expression_filename;
      $expression_filename_conv = str_replace ("*", "%", $expression_filename_conv);
      $expression_filename_conv = str_replace ("?", "_", $expression_filename_conv);      
      if (substr_count ($expression_filename_conv, "%") == 0) $expression_filename_conv = "%".$expression_filename_conv."%";
      
      $expression_filename_conv = $db->escape_string ($expression_filename_conv);

      $sql_where['filename'] = 'obj.objectpath LIKE _utf8"'.$expression_filename_conv.'"';
    }   
    
    // query dates and geo location (add table container)
    if ((!empty ($date_from) || !empty ($date_to)) || (!empty ($geo_border_sw) && !empty ($geo_border_ne)))
    {
      $sql_table['container'] = "LEFT JOIN container AS cnt ON obj.id=cnt.id";
      
      // dates
      if ($date_from != "") $sql_where['datefrom'] = 'DATE(cnt.date)>="'.$date_from.'"';
      if ($date_to != "") $sql_where['dateto'] = 'DATE(cnt.date)<="'.$date_to.'"';
      
      // geo location
      if (!empty ($geo_border_sw) && !empty ($geo_border_ne))
      {
        if (!empty ($geo_border_sw))
        {
          $geo_border_sw = str_replace (array("(",")"), "", $geo_border_sw);
          list ($latitude, $longitude) = explode (",", $geo_border_sw);
          
          if (is_numeric ($latitude) && is_numeric ($longitude)) $sql_where['geo_border_sw'] = 'cnt.latitude>='.trim($latitude).' AND cnt.longitude>='.trim($longitude);
        }
        
        if (!empty ($geo_border_ne))
        {
          $geo_border_ne = str_replace (array("(",")"), "", $geo_border_ne);
          list ($latitude, $longitude) = explode (",", $geo_border_ne);
          
          if (is_numeric ($latitude) && is_numeric ($longitude)) $sql_where['geo_border_ne'] = 'cnt.latitude<='.trim($latitude).' AND cnt.longitude<='.trim($longitude);
        }
      }
    }

    // query template
    if (!empty ($template))
    {
      $sql_where['template'] = 'obj.template="'.$template.'"';
    }
    
    // query search expression
    $sql_table['textnodes'] = "";
    $sql_expr_advanced = array();
    $sql_where_textnodes = "";

    if (is_array ($expression_array) && sizeof ($expression_array) > 0)
    {
      $i = 1;
      $i_kc = 1;
      $i_tx = 1;
      $i_tn = 1;
      
      reset ($expression_array);
      $expression_log = array();
      
      foreach ($expression_array as $key => $expression)
      {
        // define search log entry
        if (!empty ($mgmt_config['search_log']) && $expression != "" && is_string ($expression) && strpos ("_".$expression, "%taxonomy%/") < 1 && strpos ("_".$expression, "%keyword%/") < 1)
        {
          $expression_log[] = $mgmt_config['today']."|".$user."|".$expression;
        }
        
        // extract type from text ID
        if (strpos ($key, ":") > 0)
        {
          list ($type, $key) = explode (":", $key);
        }
        else $type = "";
        
        // search for specific keyword
        if (strpos ("_".$expression, "%keyword%/") > 0)
        {
          $keyword_id = getobject ($expression);
        
          if ($keyword_id > 0)
          {
            // add keywords_container table
            if ($i_kc == 1)
            {
              $sql_table['textnodes'] .= ' LEFT JOIN keywords_container AS kc1 ON obj.id=kc1.id';
            }
            elseif ($i_kc > 1)
            {
              $j = $i_kc - 1;
              $sql_table['textnodes'] .= ' LEFT JOIN keywords_container AS kc'.$i_kc.' ON kc'.$j.'.id=kc'.$i_kc.'.id';
            }
          
            $sql_expr_advanced[$i] .= 'kc'.$i_kc.'.keyword_id='.intval ($keyword_id);
            
            $i_kc++;
          }
          // objects with no keywords
          else
          {
            $sql_table['textnodes'] .= ' INNER JOIN textnodes AS tn1 ON obj.id=tn1.id';
            $sql_expr_advanced[$i] .= 'tn'.$i_kc.'.type="textk" AND tn'.$i_kc.'.textcontent=""';
          }
        }
        // search for expression (using taxonomy if enabled or full text index)
        else
        {
          // search in taxonomy
          if (!empty ($mgmt_config[$site]['taxonomy']))
          {
            // if no exact search for the expression is requested, use taxonomy
            if (empty ($mgmt_config['search_exact']))
            {
              // look up expression in taxonomy (in all languages)
              $taxonomy_ids = gettaxonomy_childs (@$site, "", $expression, 1, true);
            }
              
            // search in taxonomy table
            if (!empty ($taxonomy_ids) && is_array ($taxonomy_ids) && sizeof ($taxonomy_ids) > 0)
            {
              // advanced text-ID based search in taxonomy
              if ($expression != "" && $key != "" && $key != "0")
              {
                // add taxonomy table
                if ($i_tx == 1)
                {
                  $sql_table['textnodes'] .= ' LEFT JOIN taxonomy AS tx1 ON obj.id=tx1.id';
                }
                elseif ($i_tx > 1)
                {
                  $j = $i_tx - 1;
                  $sql_table['textnodes'] .= ' LEFT JOIN taxonomy AS tx'.$i_tx.' ON tx'.$j.'.id=tx'.$i_tx.'.id';
                }
  
                $sql_expr_advanced[$i] .= '(tx'.$i_tx.'.text_id="'.$key.'" AND tx'.$i_tx.'.taxonomy_id IN ('.implode (",", array_keys ($taxonomy_ids)).'))';
                
                $i_tx++;
              }
              // general search in taxonomy (only one search expression possible -> break out of loop)
              elseif ($expression != "")
              {
                // add taxonomy table
                $sql_table['textnodes'] .= ' LEFT JOIN taxonomy AS tx1 ON obj.id=tx1.id';
                
                $sql_expr_advanced[$i] = 'tx1.taxonomy_id IN ('.implode (",", array_keys ($taxonomy_ids)).')';
    
                break;
              }
            }
          }
          // search in textnodes table
          else
          {
            // advanced text-ID based search in textnodes
            if ((!empty ($mgmt_config['search_exact']) || $expression != "") && $key != "" && $key != "0")
            {         
              // get synonyms
              if (empty ($mgmt_config['search_exact'])) $synonym_array = getsynonym ($expression, @$lang);
              else $synonym_array = array ($expression);
    
              $r = 0;
              $sql_expr_advanced[$i] = "";
              
              if (is_array ($synonym_array) && sizeof ($synonym_array) > 0)
              {
                // add textnodes table
                if ($i_tn == 1)
                {
                  $sql_table['textnodes'] .= ' INNER JOIN textnodes AS tn1 ON obj.id=tn1.id';
                }
                elseif ($i_tn > 1)
                {
                  $j = $i_tn - 1;
                  $sql_table['textnodes'] .= ' INNER JOIN textnodes AS tn'.$i_tn.' ON tn'.$j.'.id=tn'.$i_tn.'.id';
                }

                foreach ($synonym_array as $expression)
                {
                  $expression = html_decode ($expression, convert_dbcharset ($mgmt_config['dbcharset']));
                  $expression_esc = html_encode ($expression, convert_dbcharset ($mgmt_config['dbcharset']));
                  
                  // transform wild card characters for search
                  $expression = str_replace ("%", '\%', $expression);
                  $expression = str_replace ("_", '\_', $expression);          
                  $expression = str_replace ("*", "%", $expression);
                  $expression = str_replace ("?", "_", $expression);
                  
                  $expression = $db->escape_string ($expression);
                  
                  // use OR for synonyms
                  if ($r > 0) $sql_expr_advanced[$i] .= ' OR ';
        
                  // look for exact expression except for keyword
                  if (!empty ($mgmt_config['search_exact']) && $type != "textk")
                  {
                    $sql_expr_advanced[$i] .= '(tn'.$i_tn.'.text_id="'.$key.'" AND LOWER(tn'.$i_tn.'.textcontent)=LOWER("'.$expression.'"))';
                  }
                  // look for expression in content
                  else
                  {
                    if ($expression != $expression_esc) $sql_expr_advanced[$i] .= '(tn'.$i_tn.'.text_id="'.$key.'" AND (tn'.$i_tn.'.textcontent LIKE _utf8"%'.$expression.'%" OR tn'.$i_tn.'.textcontent LIKE _utf8"%'.$expression_esc.'%"))';
                    else $sql_expr_advanced[$i] .= '(tn'.$i_tn.'.text_id="'.$key.'" AND tn'.$i_tn.'.textcontent LIKE _utf8"%'.$expression.'%")';
                  }

                  // add brackets since OR is used
                  if ($sql_expr_advanced[$i] != "") $sql_expr_advanced[$i] = "(".$sql_expr_advanced[$i].")";
 
                  $r++;
                }

                $i_tn++;
              }
            }
            // general search in all textnodes (only one search expression possible -> break out of loop)
            elseif (!empty ($mgmt_config['search_exact']) || $expression != "")
            {
              // get synonyms
              if (empty ($mgmt_config['search_exact'])) $synonym_array = getsynonym ($expression, @$lang);
              else $synonym_array = array ($expression);
              
              $r = 0;
              $sql_where_textnodes = "";

              if (is_array ($synonym_array) && sizeof ($synonym_array) > 0)
              { 
                // add textnodes table (LEFT JOIN is important!)
                $sql_table['textnodes'] .= 'LEFT JOIN textnodes AS tn1 ON obj.id=tn1.id '.$sql_table['textnodes'];
                  
                foreach ($synonym_array as $expression)
                {
                  $expression = html_decode ($expression, convert_dbcharset ($mgmt_config['dbcharset']));
                  $expression_esc = html_encode ($expression, convert_dbcharset ($mgmt_config['dbcharset']));
                
                  // transform wild card characters for search
                  $expression = str_replace ("%", '\%', $expression);
                  $expression = str_replace ("_", '\_', $expression);        
                  $expression = str_replace ("*", "%", $expression);
                  $expression = str_replace ("?", "_", $expression);

                  $expression = $db->escape_string ($expression);
                  
                  // use OR for synonyms
                  if ($r > 0) $sql_where_textnodes .= ' OR ';
                  
                  // look for exact expression
                  if (!empty ($mgmt_config['search_exact']))
                  {
                    $sql_where_textnodes .= 'tn1.textcontent="'.$expression.'"';
                  }
                  // look for expression in content
                  else
                  {
                    if ($expression != $expression_esc) $sql_where_textnodes .= '(tn1.textcontent LIKE _utf8"%'.$expression.'%" OR tn1.textcontent LIKE _utf8"%'.$expression_esc.'%")';
                    else $sql_where_textnodes .= 'tn1.textcontent LIKE _utf8"%'.$expression.'%"';
                  }
                  
                  $r++;
                }

                // add brackets since OR is used
                if ($sql_where_textnodes != "") $sql_where_textnodes = "(".$sql_where_textnodes.")";
              }
  
              break;
            }
          }
        }
        
        $i++;
      }
      
      // save search expression in search expression log
      if ($search_log) savelog ($expression_log, "search");
      
      // combine all text_id based search conditions using the operator (default is AND)
      if (isset ($sql_expr_advanced) && is_array ($sql_expr_advanced) && sizeof ($sql_expr_advanced) > 0)
      {
        $sql_where_textnodes = "(".implode (" ".$operator." ", $sql_expr_advanced).")";
      }

      // add search in object names and create final SQL where statement for search in content and object names
      if (!empty ($sql_where['filename']))
      {
        $sql_where['textnodes'] = "(".$sql_where_textnodes." OR ".$sql_where['filename'].")";
        // clear where condition for file name
        unset ($sql_where['filename']);
      }
      else $sql_where['textnodes'] = $sql_where_textnodes;
    }

    // query object type
    if (!empty ($filesize) || (is_array ($object_type) && sizeof ($object_type) > 0))
    {
      // add media table
      $sql_table['media'] = "";
      $sql_where['format'] = "";
      $sql_where['object'] = "";
      
      if (is_array ($object_type) && sizeof ($object_type) > 0)
      {
        foreach ($object_type as $search_type)
        {
          $search_type = strtolower ($search_type);
          
          // page or component object
          if ($search_type == "page" || $search_type == "comp") 
          {
            if ($sql_where['object'] != "") $sql_where['object'] .= " OR ";
            $sql_where['object'] .= 'obj.template LIKE "%.'.$search_type.'.tpl"';
          }
  
          // media file-type (audio, document, text, image, video, compressed, flash, binary, unknown)
          if (in_array ($search_type, array("audio","document","text","image","video","compressed","flash","binary","unknown"))) 
          {
            if (!empty ($sql_where['format'])) $sql_where['format'] .= " OR ";
            $sql_where['format'] .= 'med.filetype="'.$search_type.'"';
          }
        }
      }

      // add brackets for OR operators for media format
      if (!empty ($sql_where['format']))
      {
        // add meta as object type if formats are set
        $sql_where['format'] = '(('.$sql_where['format'].') AND obj.template LIKE "%.meta.tpl")';
        
        if (!empty ($sql_where['object']))
        {
          $sql_where['format'] = '('.$sql_where['format'].' OR ('.$sql_where['object'].'))';
          unset ($sql_where['object']);
        }
      }
      else unset ($sql_where['format']);
      
      // if object conditions still exist, use brackets
      if (!empty ($sql_where['object']))
      {
        $sql_where['object'] = '('.$sql_where['object'].')';
      }
      
      // join media table
      if (!empty ($sql_where['format']) || !empty ($filesize)) $sql_table['media'] = 'LEFT JOIN media AS med on obj.id=med.id';
    }
    
    $sql_where['media'] = "";
    
    // query file size
    if (!empty ($filesize))
    {
      // set default operator
      $filesize_operator = ">=";
      
      // filesize includes operator
      if ($filesize < 1)
      {
        // >=
        if (strpos ("_".$filesize, "&gt;=") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 5);
          $filesize = substr ($filesize, 5);
        }
        // >=
        elseif (strpos ("_".$filesize, ">=") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 2);
          $filesize = substr ($filesize, 2);
        }
        // <=
        elseif (strpos ("_".$filesize, "&lt;=") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 5);
          $filesize = substr ($filesize, 5);
        }
        // <=
        elseif (strpos ("_".$filesize, "<=") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 2);
          $filesize = substr ($filesize, 2);
        }
        // >
        elseif (strpos ("_".$filesize, "&gt;") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 4);
          $filesize = substr ($filesize, 4);
        }
        // >
        elseif (strpos ("_".$filesize, ">") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 1);
          $filesize = substr ($filesize, 1);
        }
        // <
        elseif (strpos ("_".$filesize, "&lt;") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 4);
          $filesize = substr ($filesize, 4);
        }
        // <
        elseif (strpos ("_".$filesize, "<") > 0)
        {
          $filesize_operator = substr ($filesize, 0, 1);
          $filesize = substr ($filesize, 1);
        }
      }
    
      if ($filesize > 0)
      {
        if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
      
        $sql_where['media'] .= 'med.filesize'.$filesize_operator.intval($filesize);
      }
    }
    
    // query image and video
    if (isset ($object_type) && is_array ($object_type) && (in_array ("image", $object_type) || in_array ("video", $object_type)))
    {
      if (!empty ($filesize) || !empty ($imagewidth) || !empty ($imageheight) || (isset ($imagecolor) && is_array ($imagecolor)) || !empty ($imagetype))
      {
        // parameter imagewidth can be used as general image size parameter, only if height = ""
        // search for image_size (area)
        if (!empty ($imagewidth) && substr_count ($imagewidth, "-") == 1)
        {
          list ($imagewidth_min, $imagewidth_max) = explode ("-", $imagewidth);
          $sql_where['media'] .= (($sql_where['media'] == '') ? '' : ' AND ').'(med.width>='.intval($imagewidth_min).' OR med.height>='.intval($imagewidth_min).') AND (med.width<='.intval($imagewidth_max).' OR med.height<='.intval($imagewidth_max).')';
        }
        else
        {			
          //search for exact image width
          if (!empty ($imagewidth) && $imagewidth > 0)
          {
            if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
            
            $sql_where['media'] .= 'med.width='.intval($imagewidth);
          }
               
          // search for exact image height
          if (!empty ($imageheight) && $imageheight > 0)
          {
            if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
            
            $sql_where['media'] .= 'med.height='.intval($imageheight);
          }
        }
        
        if (isset ($imagecolor) && is_array ($imagecolor))
        {
          foreach ($imagecolor as $colorkey)
          {
            if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
            
            $sql_where['media'] .= 'INSTR(med.colorkey,"'.$colorkey.'")>0';
          }
        }
        
        if (!empty ($imagetype))
        {
          if (!empty ($sql_where['media'])) $sql_where['media'] .= ' AND ';
          
          $sql_where['media'] .= 'med.imagetype="'.$imagetype.'"';
        }
      }
    }
    
    // remove empoty array elements
    $sql_table = array_filter ($sql_table);
    $sql_where = array_filter ($sql_where);

    // build SQL statement
    $sql = 'SELECT DISTINCT obj.objectpath, obj.hash FROM object AS obj';
    if (isset ($sql_table) && is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= ' '.implode (' ', $sql_table);
    if (isset ($sql_where) && is_array ($sql_where) && sizeof ($sql_where) > 0) $sql .= ' WHERE '.implode (' AND ', $sql_where);
    // removed "order by" due to poor DB performance and moved to array sort
    // $sql .= ' ORDER BY SUBSTRING_INDEX(obj.objectpath,"/",-1)';

    if (isset ($starthits) && intval($starthits) >= 0 && isset ($endhits) && intval($endhits) > 0) $sql .= ' LIMIT '.intval($starthits).','.intval($endhits);
    elseif (isset ($maxhits) && intval($maxhits) > 0) $sql .= ' LIMIT 0,'.intval($maxhits);

    $errcode = "50082";
    $done = $db->query ($sql, $errcode, $mgmt_config['today']);

    if ($done)
    {    
      // for search after SQL-result in the file name
      if ($expression_filename != "")
      {
        $expression_filename = str_replace ("*", "", $expression_filename);
        $expression_filename = str_replace ("?", "", $expression_filename);
        $expression_filename = specialchr_encode ($expression_filename);        
      }
      
      while ($row = $db->getResultRow ())
      {
        if ($row['objectpath'] != "") $objectpath[$row['hash']] = str_replace (array("*page*", "*comp*"), array("%page%", "%comp%"), $row['objectpath']);
      }      
    }

    //count searchresults
    if (!empty ($count))
    {
      $sql = 'SELECT COUNT(DISTINCT obj.objectpath) as cnt FROM object AS obj';
      if (is_array ($sql_table)) $sql .= ' '.implode (" ", $sql_table);
      $sql .= ' WHERE ';
    
      if (isset ($sql_table) && is_array ($sql_where)) 
      {
        $sql .= implode (" AND ", $sql_where);
      }
      
      $errcode = "50081";
      $done = $db->query ($sql, $errcode, $mgmt_config['today']);

      if ($done && ($row = $db->getResultRow ()))
      {         
        if ($row['cnt'] != "") $objectpath['count'] = $row['cnt']; 
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();
    
    if (isset ($objectpath) && is_array ($objectpath))
    {
      // sort result by objectpath (groups result by location and does not present a sort by object name)
      natcasesort ($objectpath);
      reset ($objectpath);
      
      return $objectpath;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------------- replace content -------------------------------------------------

// function: rdbms_replacecontent()
// input: location, object-type (optional), filter for start modified date (optional), filter for end modified date (optional), search expression, replace expression, user name (optional)
// output: result array with object paths of all touched objects / false

// description:
// Replaces an expression by another in the content.

function rdbms_replacecontent ($folderpath, $object_type="", $date_from="", $date_to="", $search_expression, $replace_expression, $user="sys")
{
  global $mgmt_config;

  if ($folderpath != "" && $search_expression != "")
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $folderpath = $db->escape_string ($folderpath);
    if (is_array ($object_type)) foreach ($object_type as &$value) $value = $db->escape_string ($value);
    if ($date_from != "") $date_from = $db->escape_string ($date_from);
    if ($date_to != "") $date_to = $db->escape_string ($date_to);
    if ($user != "") $user = $db->escape_string ($user);
        
    // replace %
    $folderpath = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $folderpath);
    
    // query object type
    if (is_array ($object_type) && sizeof ($object_type) > 0)
    {
      // add media table
      $sql_table['media'] = "";
      $sql_where['format'] = "";
      $sql_where['object'] = "";
      
      if (is_array ($object_type) && sizeof ($object_type) > 0)
      {
        foreach ($object_type as $search_type)
        {
          $search_type = strtolower ($search_type);
          
          // page or component object
          if ($search_type == "page" || $search_type == "comp") 
          {
            if ($sql_where['object'] != "") $sql_where['object'] .= " OR ";
            $sql_where['object'] .= 'obj.template LIKE "%.'.$search_type.'.tpl"';
          }
  
          // media file-type (audio, document, text, image, video, compressed, flash, binary, unknown)
          if (in_array ($search_type, array("audio","document","text","image","video","compressed","flash","binary","unknown"))) 
          {
            if (!empty ($sql_where['format'])) $sql_where['format'] .= " OR ";
            $sql_where['format'] .= 'med.filetype="'.$search_type.'"';
          }
        }
      }

      // add brackets for OR operators for media format
      if (!empty ($sql_where['format']))
      {
        // add meta as object type if formats are set
        $sql_where['format'] = '(('.$sql_where['format'].') AND obj.template LIKE "%.meta.tpl")';
        
        if (!empty ($sql_where['object']))
        {
          $sql_where['format'] = '('.$sql_where['format'].' OR ('.$sql_where['object'].'))';
          unset ($sql_where['object']);
        }
      }
      else unset ($sql_where['format']);
      
      // if object conditions still exist, use brackets
      if (!empty ($sql_where['object']))
      {
        $sql_where['object'] = '('.$sql_where['object'].')';
      }
      
      // join media table
      if (!empty ($sql_where['format']) || !empty ($filesize)) $sql_table['media'] = 'LEFT JOIN media AS med ON obj.id=med.id';
    }  
    
    // folder path
    $sql_where['filename'] = 'obj.objectpath LIKE _utf8"'.$folderpath.'%" COLLATE utf8_bin';
 
    // dates
    if (!empty ($date_from)) $sql_where['datefrom'] = 'DATE(cnt.date)>="'.$date_from.'"';
    if (!empty ($date_to)) $sql_where['dateto'] = 'DATE(cnt.date)<="'.$date_to.'"'; 
    
    // search expression
    if ($search_expression != "")
    {
      $expression = $search_expression;
      
      $expression = html_decode ($expression, convert_dbcharset ($mgmt_config['dbcharset']));
      $expression_esc = html_encode ($expression, convert_dbcharset ($mgmt_config['dbcharset']));

      // transform wild card characters for search
      $expression = str_replace ("%", '\%', $expression);
      $expression = str_replace ("_", '\_', $expression);      
      $expression = str_replace ("*", "%", $expression);
      $expression = str_replace ("?", "_", $expression);

      $expression = $db->escape_string ($expression);

      if ($expression != $expression_esc) $sql_where['expression'] = '(tn1.textcontent LIKE _utf8"%'.$expression.'%" COLLATE utf8_bin OR tn1.textcontent LIKE _utf8"%'.$expression_esc.'%" COLLATE utf8_bin)';
      else $sql_where['textnodes'] = 'tn1.textcontent LIKE _utf8"%'.$expression.'%" COLLATE utf8_bin';
    }    
    
    $sql = 'SELECT obj.objectpath, cnt.id, cnt.container, tn1.text_id, tn1.textcontent FROM object AS obj INNER JOIN container AS cnt ON cnt.id=obj.id INNER JOIN textnodes AS tn1 ON tn1.id=cnt.id';
    if (is_array ($sql_table) && sizeof ($sql_table) > 0) $sql .= ' '.implode (" ", $sql_table);
    $sql .= ' WHERE obj.id=cnt.id AND cnt.id=tn1.id AND';    
    if (is_array ($sql_where) && sizeof ($sql_where) > 0) $sql .= ' '.implode (" AND ", $sql_where);

    $errcode = "50063";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], "select");
    
    $container_id_prev = "";
    $containerdata = "";

    if ($done)
    {
      // transform search expression
      $search_expression = str_replace ("*", "", $search_expression);
      $search_expression = str_replace ("?", "", $search_expression);
      
      $search_expression_esc = html_encode ($search_expression, convert_dbcharset ($mgmt_config['dbcharset']));
      $search_expression = $db->escape_string ($search_expression);
      
      // transform replace expression
      $replace_expression_esc = html_encode ($replace_expression, convert_dbcharset ($mgmt_config['dbcharset']));
      $replace_expression = $db->escape_string ($replace_expression);
        
      $num_rows = $db->getNumRows ("select");
 
      if ($num_rows > 0)
      {
        for ($i = 0; $i < $num_rows; $i++)
        {
          $row = $db->getResultRow ("select", $i);
        
          $objectpath[] = str_replace (array("*page*", "*comp*"), array("%page%", "%comp%"), $row['objectpath']);
          $id = $row['id'];
          $container_file = $row['container'];
          $text_id = $row['text_id'];
          $textcontent = $row['textcontent'];

          if ($id != "")
          {
            // replace expression for update in RDBMS
            $textcontent = str_replace ($search_expression, $replace_expression_esc, $textcontent); 
            $textcontent = str_replace ($search_expression_esc, $replace_expression_esc, $textcontent);                 

            // replace expression in container
            $container_id = substr ($container_file, 0, strpos ($container_file, ".xml"));
            
            // save container, execute query and load container if container ID changed
            if ($container_id != $container_id_prev)
            {
              if ($containerdata != "" && $containerdata != false)
              {
                // save container
                $result_save = savecontainer ($container_id_prev, "work", $containerdata, $user);
                
                if ($result_save == false)
                {
                  $errcode = "10911";
                  $error[] = $mgmt_config['today']."|db_connect_rdbms.php|error|$errcode|container ".$container_id_prev." could not be saved\n";  
                  
                  // save log
                  savelog ($error);                                    
                }
                else
                {
                  // update content in database
                  $errcode = "50024";
                  
                  foreach ($sql_array as $sql)
                  {
                    $db->query ($sql, $errcode, $mgmt_config['today'], "update");
                  }
                }
                
              }
              
              // Emptying collected sql statements
              $sql_array = array();
              
              // load container
              $containerdata = loadcontainer ($container_id, "work", $user);
            }
            
            // set previous container ID
            $container_id_prev = $container_id;
  
            // save content container
            if ($containerdata != "" && $containerdata != false)
            {       
              $xml_search_array = selectcontent ($containerdata, "<text>", "<text_id>", $text_id);
       
              if ($xml_search_array != false)
              {
                $xml_content = getxmlcontent ($xml_search_array[0], "<textcontent>");
                
                if ($xml_content != false && $xml_content[0] != "")
                {
                  if (substr_count ($xml_content[0], $search_expression_esc) > 0 || substr_count ($xml_content[0], $search_expression) > 0)
                  {
                    // replace expression in textcontent
                    $xml_replace = str_replace ($search_expression, $replace_expression_esc, $xml_content[0]);
                    
                    if ($search_expression != $search_expression_esc)
                    {
                      $xml_replace = str_replace ($search_expression_esc, $replace_expression_esc, $xml_replace);
                    }
                    
                    // replace textcontent in text
                    $xml_replace = str_replace($xml_content[0], $xml_replace, $xml_search_array[0]);

                    // replace text in container
                    $containerdata = str_replace ($xml_search_array[0], $xml_replace, $containerdata);
                  }
                  
                  // update content in database
                  $sql_array[] = 'UPDATE textnodes SET textcontent="'.$textcontent.'" WHERE id='.$id.' AND text_id="'.$text_id.'"';
                }  
              }       
            }       
          }  
        }
      }
    }

    // save last container
    $result_save = savecontainer ($container_id_prev, "work", $containerdata, $user);
    
    if ($result_save == false)
    {
      $errcode = "10911";
      $error[] = $mgmt_config['today']."|db_connect_rdbms.php|error|$errcode|container ".$container_id_prev." could not be saved\n";  
      
      // save log
      savelog ($error);                                    
    }
    else
    {
      // update content in database
      $errcode = "50040";
      
      foreach ($sql_array as $sql)
      {
        $db->query ($sql, $errcode, $mgmt_config['today'], "update");
      }
    }
    
    // save log
    savelog ($db->getError ());    
    $db->close(); 
    
    if (isset ($objectpath) && is_array ($objectpath))
    {
      $objectpath = array_unique ($objectpath);
      return $objectpath;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------------- search user ------------------------------------------------- 

// function: rdbms_searchuser()
// input: publication name, user name, max. hits (optional)
// output: objectpath array with hashcode as key and path as value / false

// description:
// Queries all objects of a user.

function rdbms_searchuser ($site, $user, $maxhits=1000)
{
  global $mgmt_config;

  if ($user != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    if ($site != "" && $site != "*Null*") $site = $db->escape_string ($site);
    $user = $db->escape_string ($user);
    $maxhits = intval ($maxhits);
    
    $sql = 'SELECT obj.objectpath, obj.hash FROM object AS obj, container AS cnt WHERE obj.id=cnt.id AND cnt.user="'.$user.'"';
    if ($site != "" && $site != "*Null*") $sql .= ' AND (obj.objectpath LIKE _utf8"*page*/'.$site.'/%" COLLATE utf8_bin OR obj.objectpath LIKE _utf8"*comp*/'.$site.'/%" COLLATE utf8_bin)';
    $sql .= ' ORDER BY cnt.date DESC';
    if ($maxhits > 0) $sql .= ' LIMIT 0,'.intval($maxhits);

    $errcode = "50025";
    $done = $db->query($sql, $errcode, $mgmt_config['today']);
    
    if ($done)
    {
      $objectpath = array();
      
      while ($row = $db->getResultRow ())
      {
        if ($row['objectpath'] != "")
        {
          $hash = $row['hash'];
          $objectpath[$hash] = str_replace (array("*page*", "*comp*"), array("%page%", "%comp%"), $row['objectpath']);
        }   
      }
    }
    else $objectpath = Null;

    // save log
    savelog ($db->getError ());    
    $db->close();
      
    if (is_array ($objectpath) && sizeof ($objectpath) > 0) return $objectpath;
    else return false;
  }
  else return false;
} 

// ----------------------------------------------- get content -------------------------------------------------

// function: rdbms_getcontent()
// input: publication name, container ID, filter for text-ID (optional), filter for type (optional), filter for user name (optional)
// output: result array with text ID as key and content as value / false

// description:
// Selects content for a container in the database.

function rdbms_getcontent ($site, $container_id, $text_id="", $type="", $user="")
{
  global $mgmt_config;
  
  if (intval ($container_id) > 0)
  {
    $result = array();
    
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    if ($type == "u" || $type == "f" || $type == "l" || $type == "c" || $type == "d" || $type == "k") $type = "text".$type;
    
    $container_id = intval ($container_id);
    if ($text_id != "") $text_id = $db->escape_string($text_id);
    if ($type != "") $type = $db->escape_string($type);
    if ($user != "") $user = $db->escape_string($user);

    $sql = 'SELECT text_id, textcontent FROM textnodes WHERE id="'.$container_id.'"';
    if ($text_id != "") $sql .= ' AND text_id="'.$text_id.'"';
    if ($type != "") $sql .= ' AND type="'.$type.'"';
    if ($user != "") $sql .= ' AND user="'.$user.'"';
               
    $errcode = "50199";
    $done = $db->query ($sql, $errcode, $mgmt_config['today']);

    if ($done)
    {
      while ($row = $db->getResultRow ())
      {
        $result[$row['text_id']] = $row['textcontent'];
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();
    
    if (is_array ($result) && sizeof ($result) > 0) return $result;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- get keywords ------------------------------------------------- 

// function: rdbms_getkeywords()
// input: publication names as string or array (optional)
// output: result array with keyword ID as key and keyword and count as value / false

// description:
// Selects all keywords in the database.

function rdbms_getkeywords ($sites="")
{
  global $mgmt_config;
  
  $result = array();

  $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
  
  $sql = 'SELECT keywords.keyword_id, keywords.keyword, COUNT(keywords_container.id) AS count FROM keywords INNER JOIN keywords_container ON keywords.keyword_id=keywords_container.keyword_id';
  
  if (is_array ($sites))
  {
    $i = 0;
    
    foreach ($sites as $site)
    {
      $site = $db->escape_string ($site);
      
      if ($i < 1) $sql .= ' INNER JOIN object ON object.id=keywords_container.id WHERE (object.objectpath LIKE _utf8"*page*/'.$site.'/%" COLLATE utf8_bin OR object.objectpath LIKE _utf8"*comp*/'.$site.'/%" COLLATE utf8_bin)';
      else $sql .= ' OR (object.objectpath LIKE _utf8"*page*/'.$site.'/%" COLLATE utf8_bin OR object.objectpath LIKE _utf8"*comp*/'.$site.'/%" COLLATE utf8_bin)';
      
      $i++;
    }
  }
  else if ($sites != "" && $sites != "*Null*")
  {
    $site = $db->escape_string ($sites);
    $sql .= ' INNER JOIN object ON object.id=keywords_container.id WHERE (object.objectpath LIKE _utf8"*page*/'.$site.'/%" COLLATE utf8_bin OR object.objectpath LIKE _utf8"*comp*/'.$site.'/%" COLLATE utf8_bin)';
  }
  
  $sql .= ' GROUP BY keywords.keyword_id ORDER BY keywords.keyword';

  $errcode = "50541";
  $done = $db->query($sql, $errcode, $mgmt_config['today']);
  
  if ($done)
  {
    while ($row = $db->getResultRow ())
    {
      if ($row['keyword_id'] != "" && $row['keyword'] != "" && $row['count'] > 0)
      {
        $id = $row['keyword_id'];
        $count = $row['count'];
        
        $result[$id][$count] = $row['keyword'];
      }
    }
  }

  // save log
  savelog ($db->getError ());    
  $db->close();
    
  if (is_array ($result) && sizeof ($result) > 0) return $result;
  else return false;
}

// ----------------------------------------------- get empty keywords ------------------------------------------------- 

// function: rdbms_getemptykeywords()
// input: publication names as string or array (optional)
// output: number of objects without keywords / false

// description:
// Queries the number of objects without keywords.

function rdbms_getemptykeywords ($sites="")
{
  global $mgmt_config;

  $result = array();

  $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

  $sql = 'SELECT COUNT(object.id) AS count FROM object INNER JOIN textnodes ON textnodes.id=object.id WHERE';
  
  if (is_array ($sites))
  {
    $i = 0;
    $sql_objectpath = "";
    
    foreach ($sites as $site)
    {
      $site = $db->escape_string ($site);
      
      if ($i < 1) $sql_objectpath .= ' (object.objectpath LIKE _utf8"*page*/'.$site.'/%" COLLATE utf8_bin OR object.objectpath LIKE _utf8"*comp*/'.$site.'/%" COLLATE utf8_bin)';
      else $sql_objectpath .= ' OR (object.objectpath LIKE _utf8"*page*/'.$site.'/%" COLLATE utf8_bin OR object.objectpath LIKE _utf8"*comp*/'.$site.'/%" COLLATE utf8_bin)';
      
      $i++;
    }
    
    $sql .= '('.$sql_objectpath.')';
  }
  else if ($sites != "" && $sites != "*Null*")
  {
    $site = $db->escape_string ($sites);
    $sql .= ' (object.objectpath LIKE _utf8"*page*/'.$site.'/%" COLLATE utf8_bin OR object.objectpath LIKE _utf8"*comp*/'.$site.'/%" COLLATE utf8_bin)';
  }
  
  $sql .= ' AND textnodes.type="textk" AND textnodes.textcontent=""';
  
  $errcode = "50542";
  $done = $db->query($sql, $errcode, $mgmt_config['today']);
  
  if ($done)
  {
    if ($row = $db->getResultRow ())
    {
      if ($row['count']) $result = $row['count'];
    }
  }

  // save log
  savelog ($db->getError ());    
  $db->close();
    
  if (!empty ($result)) return $result;
  else return 0;
}

// ----------------------------------------------- get hierarchy sublevel ------------------------------------------------- 

// function: rdbms_gethierarchy_sublevel()
// input: publication name, text ID that holds the content, conditions array with text ID as key and content as value (optional)
// output: array with hashcode as key and path as value / false

// description:
// Queries all values for a hierarachy level.

function rdbms_gethierarchy_sublevel ($site, $get_text_id, $text_id_array="")
{
  global $mgmt_config;

  if ($site != "" && $get_text_id != "")
  {
    $result = array();
    $sql_textnodes = array();
    
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $site = $db->escape_string ($site);
    $get_text_id = $db->escape_string ($get_text_id);
    
    // extract type from text ID
    if (strpos ($get_text_id, ":") > 0)
    {
      list ($type, $get_text_id) = explode (":", $get_text_id);
    }
    
    // query database
    $sql = 'SELECT DISTINCT tn1.textcontent, tn1.type FROM textnodes AS tn1';
    
    if (is_array ($text_id_array) && sizeof ($text_id_array) > 0)
    {
      $i = 2;

      foreach ($text_id_array as $text_id => $value)
      {
        // extract type from text ID
        if (strpos ($text_id, ":") > 0)
        {
          list ($type, $text_id) = explode (":", $text_id);
        }
        else $type = "textu";
      
        $j = $i - 1;
        
        $sql .= ' INNER JOIN textnodes AS tn'.$i.' ON tn'.$j.'.id=tn'.$i.'.id';
        
        $value = html_decode ($value, convert_dbcharset ($mgmt_config['dbcharset']));
        $value_esc = html_encode ($value, convert_dbcharset ($mgmt_config['dbcharset']));

        $text_id = $db->escape_string ($text_id);
        $value = $db->escape_string ($value);

        // search for exact expression except for keyword
        if ($type != "textk")
        {
          if ($value !=  $value_esc) $sql_textnodes[] = 'tn'.$i.'.text_id="'.$text_id.'" AND (LOWER(tn'.$i.'.textcontent)=LOWER("'.$value.'") OR LOWER(tn'.$i.'.textcontent)=LOWER("'.$value_esc.'"))';
          else $sql_textnodes[] = 'tn'.$i.'.text_id="'.$text_id.'" AND LOWER(tn'.$i.'.textcontent)=LOWER("'.$value.'")';
        }
        else
        {
          if ($value !=  $value_esc) $sql_textnodes[] = 'tn'.$i.'.text_id="'.$text_id.'" AND (tn'.$i.'.textcontent LIKE "%'.$value.'%" OR tn'.$i.'.textcontent LIKE "%'.$value_esc.'%")';
          else $sql_textnodes[] = 'tn'.$i.'.text_id="'.$text_id.'" AND tn'.$i.'.textcontent LIKE "%'.$value.'%"';
        }
        
        $i++;
      }
    }
    
    $sql .= ' INNER JOIN object ON object.id=tn1.id';
    $sql .= ' WHERE (tn1.type="textu" OR tn1.type="textl" OR tn1.type="textc" OR tn1.type="textd" OR tn1.type="textk")';
    $sql .= ' AND (object.objectpath LIKE _utf8"*page*/'.$site.'/%" COLLATE utf8_bin OR object.objectpath LIKE _utf8"*comp*/'.$site.'/%" COLLATE utf8_bin)';
    $sql .= ' AND tn1.text_id="'.$get_text_id.'"';
    if (is_array ($sql_textnodes) && sizeof ($sql_textnodes) > 0) $sql .= ' AND '.implode (" AND ", $sql_textnodes);
    
    $errcode = "50542";
    $done = $db->query($sql, $errcode, $mgmt_config['today']);

    if ($done)
    {
      while ($row = $db->getResultRow ())
      {
        // split keywords
        if ($row['type'] == "textk")
        {
          $result_add = splitkeywords ($row['textcontent']);
          
          if (is_array ($result_add)) $result = array_merge ($result, $result_add);
        }
        else $result[] = $row['textcontent'];
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();
      
    if (is_array ($result) && sizeof ($result) > 0)
    {
      $result = array_iunique ($result);
      return $result;
    }
    else return false;
  }
  else return false;
}

// ----------------------------------------------- get object_id ------------------------------------------------- 

function rdbms_getobject_id ($object)
{
  global $mgmt_config;

  if ($object != "")
  {
    // correct object name 
    // if unpublished object
    if (strtolower (strrchr ($object, ".")) == ".off")
    {
      $object = substr ($object, 0, -4);
    }
    // if object is a folder
    elseif (is_dir (deconvertpath ($object, "file")))
    {
      if (substr ($object, -1) != "/") $object = $object."/.folder";
      else $object = $object.".folder";
    }
      
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $object = $db->escape_string ($object);
    
    // object path
    if (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0)
    { 
      $object = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $object);

      $sql = 'SELECT object_id FROM object WHERE objectpath=_utf8"'.$object.'" COLLATE utf8_bin';
    }
    // object hash
    else
    {
      $sql = 'SELECT object_id FROM object WHERE hash=_utf8"'.$object.'" COLLATE utf8_bin';
    }
    
    $errcode = "50026";
    $done = $db->query ($sql, $errcode, $mgmt_config['today']);
    
    if ($done && $row = $db->getResultRow ())
    {
      $object_id = $row['object_id'];
    }
    
    // save log
    savelog ($db->getError ());
    $db->close();
      
    if (!empty ($object_id))
    {
      return $object_id;
    }
    else
    {
      // if object is a root folder (created since version 5.6.3)
      if (substr_count ($object, "/") == 2)
      {
        $object_esc = str_replace (array("*page*", "*comp*"), array("%page%", "%comp%"), $object);
        $createobject = createobject (getpublication ($object_esc), getlocation ($object_esc), ".folder", "default.meta.tpl", "sys");
 
        if ($createobject['result'] == true) return $object_id = rdbms_getobject_id ($object_esc);
        else return false;
      }
      else return false;
    }
  }
  else return false;
}

// ----------------------------------------------- get object_hash ------------------------------------------------- 

function rdbms_getobject_hash ($object="", $container_id="")
{
  global $mgmt_config;

  // object can be an object path or object ID, second input parameter can only be the container ID
  if ($object != "" || $container_id != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
      
    // if object path
    if (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0)
    {
      // correct object name 
      if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
      
      // if unpublished object
      if (strtolower (strrchr ($object, ".")) == ".off")
      {
        $object = substr ($object, 0, -4);
      }
      // if object is a folder
      elseif (is_dir (deconvertpath ($object, "file")))
      {
        if (substr ($object, -1) != "/") $object = $object."/.folder";
        else $object = $object.".folder";
      }
      
      $object = $db->escape_string ($object);          
      $object = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $object);
  
      $sql = 'SELECT hash FROM object WHERE objectpath=_utf8"'.$object.'" COLLATE utf8_bin LIMIT 1';
    }
    // if object id
    elseif (intval ($object) > 0)
    {
      $sql = 'SELECT hash FROM object WHERE object_id="'.intval($object).'" LIMIT 1';
    }
    // if container id
    elseif (intval ($container_id) > 0)
    {
      $sql = 'SELECT hash FROM object WHERE id="'.intval($container_id).'" LIMIT 1';
    }

    if (!empty ($sql))
    {
      $errcode = "50026";
      $done = $db->query ($sql, $errcode, $mgmt_config['today']);
      
      if ($done && $row = $db->getResultRow ())
      {
        $hash = $row['hash'];   
      }

      // save log
      savelog ($db->getError ());    
      $db->close();
        
      if (!empty ($hash))
      {
        return $hash;
      }
      else
      {
        // if object is a root folder (created since version 5.6.3)
        if (substr_count ($object, "/") == 2)
        {
          $object_esc = str_replace (array("*page*", "*comp*"), array("%page%", "%comp%"), $object);
          $createobject = createobject (getpublication ($object_esc), getlocation ($object_esc), ".folder", "default.meta.tpl", "sys");
          
          if ($createobject['result'] == true) return $hash = rdbms_getobject_hash ($object_esc);
          else return false;
        }
        else return false;
      }
    }
    else return false;
  }
  else return false;
} 

// -------------------------------------------- get object by unique id or hash ----------------------------------------------- 

function rdbms_getobject ($object_identifier)
{
  global $mgmt_config;

  if ($object_identifier != "")
  {
    $objectpath = "";
    
    // if object identifier is already a location
    if (strpos ("_".$object_identifier, "%page%") > 0 || strpos ("_".$object_identifier, "%comp%") > 0) return $object_identifier;
    
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // clean input
    $object_identifier = $db->escape_string ($object_identifier);
    
    // try table object if public download is allowed
    if ($mgmt_config['publicdownload'] == true)
    {
      if (is_numeric ($object_identifier)) $sql = 'SELECT objectpath FROM object WHERE object_id='.intval($object_identifier);
      else $sql = 'SELECT objectpath FROM object WHERE hash="'.$object_identifier.'"';
  
      $errcode = "50027";
      $done = $db->query ($sql, $errcode, $mgmt_config['today']);
      
      if ($done && $row = $db->getResultRow ())
      {
        if ($row['objectpath'] != "") $objectpath = str_replace (array("*page*", "*comp*"), array("%page%", "%comp%"), $row['objectpath']);  
      }
    }

    // try table accesslink
    if ($objectpath == "" && !is_numeric ($object_identifier))
    {
      $sql = 'SELECT obj.objectpath, al.deathtime, al.formats FROM accesslink AS al, object AS obj WHERE al.hash="'.$object_identifier.'" AND al.object_id=obj.object_id';
      
      $errcode = "50028";
      $done = $db->query ($sql, $errcode, $mgmt_config['today'], "select2");
      
      if ($done)
      {
        $row = $db->getResultRow ("select2");
        
        // if time of death for link is set
        if ($row['deathtime'] > 0)
        {
          // if deathtime was passed
          if ($row['deathtime'] < time())
          {
            $sql = 'DELETE FROM accesslink WHERE hash="'.$object_identifier.'"';
             
            $errcode = "50029";
            $db->query ($sql, $errcode, $mgmt_config['today'], "delete");
          }
          elseif ($row['objectpath'] != "") $objectpath = str_replace (array("*page*", "*comp*"), array("%page%", "%comp%"), $row['objectpath']);
        }
        elseif ($row['objectpath'] != "") $objectpath = str_replace (array("*page*", "*comp*"), array("%page%", "%comp%"), $row['objectpath']);  
      }
    }
    
    // save log
    savelog ($db->getError ());    
    $db->close();     
      
    if ($objectpath != "") return $objectpath;
    else return false;
  }
  else return false;
} 

// ----------------------------------------------- get objects by container_id ------------------------------------------------- 

function rdbms_getobjects ($container_id, $template="")
{
  global $mgmt_config;

  if ($container_id != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // clean input
    $container_id = $db->escape_string ($container_id);
    if ($template != "") $template = $db->escape_string ($template);
    
    $container_id = intval ($container_id);
    
    $sql = 'SELECT objectpath, hash FROM object WHERE id='.$container_id;
    if ($template != "") $sql .= ' AND template="'.$template.'"';
    
    $errcode = "50030";
    $done = $db->query ($sql, $errcode, $mgmt_config['today']);
    $objectpath = array();
    
    if ($done)  
    {
      while ($row = $db->getResultRow ())
      {
        if (trim ($row['objectpath']) != "")
        {
          $hash = $row['hash'];
          $objectpath[$hash] = str_replace (array("*page*", "*comp*"), array("%page%", "%comp%"), $row['objectpath']);
        }
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();    
      
    if (sizeof ($objectpath) > 0) return $objectpath;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- create accesslink -------------------------------------------------

function rdbms_createaccesslink ($hash, $object_id, $type="al", $user="", $lifetime=0, $formats="")
{
  global $mgmt_config;
  
  if ($hash != "" && $object_id != "" && (($type == "al" && valid_objectname ($user)) || $type == "dl"))
  { 
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $hash = $db->escape_string ($hash);
    $object_id = $db->escape_string ($object_id);
    $type = $db->escape_string ($type);
    if ($user != "") $user = $db->escape_string ($user);
    if ($lifetime != "") $lifetime = $db->escape_string ($lifetime);
    if ($formats != "") $formats = $db->escape_string ($formats);
    
    // date
    $date = date ("Y-m-d H:i", time());
    
    // define time of death based on lifetime
    if ($lifetime > 0) $deathtime = time() + intval ($lifetime);
    else $deathtime = 0;

    // insert access link info
    $sql = 'INSERT INTO accesslink (hash, date, object_id, type, user, deathtime, formats) ';    
    $sql .= 'VALUES ("'.$hash.'", "'.$date.'", "'.intval ($object_id).'", "'.$type.'", "'.$user.'", '.intval ($deathtime).', "'.$formats.'")';
         
    $errcode = "50007";
    $db->query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->getError ());    
    $db->close();
        
    return true;
  }
  else return false;
} 

// ------------------------------------------------ get access info -------------------------------------------------

function rdbms_getaccessinfo ($hash)
{
  global $mgmt_config;
 
  if ($hash != "")
  {
    $result = array();
    
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $hash = $db->escape_string ($hash);
  
    $sql = 'SELECT date, object_id, type, user, deathtime, formats FROM accesslink WHERE hash="'.$hash.'"';

    $errcode = "50071";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], "select");

    if ($done)
    {
      $row = $db->getResultRow ("select");
      
      $result['date'] = $row['date'];
      $result['object_id'] = $row['object_id']; 
      $result['type'] = $row['type']; 
      $result['user'] = $row['user']; 
      $result['deathtime'] = $row['deathtime'];
      $result['formats'] = $row['formats'];
      
      // if time of death vor link is set
      if ($result['deathtime'] > 0)
      {
        // if deathtime was passed
        if ($result['deathtime'] < time())
        {
          $sql = 'DELETE FROM accesslink WHERE hash="'.$hash.'"';
           
          $errcode = "50072";
          $db->query ($sql, $errcode, $mgmt_config['today'], "delete");
          
          $result = false;
        }
      }
    }

    // save log
    savelog ($db->getError ());    
    $db->close();
    
    if (is_array ($result) && sizeof ($result) > 0) return $result;
    else return false;
  }
  else return false;
}

// ------------------------------------------------ create recipient -------------------------------------------------

function rdbms_createrecipient ($object, $from_user, $to_user, $email)
{
  global $mgmt_config;
 
  if ($object != "" && $from_user != "" && $to_user != "" && $email != "")
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
      
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);

    $date = date ("Y-m-d H:i:s", time());
    
    $object = $db->escape_string ($object);
    $from_user = $db->escape_string ($from_user);
    $to_user = $db->escape_string ($to_user);
    $email = $db->escape_string ($email);
    
    $object = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $object);    
    
    // get object ids of all objects (also all object of folders)
    if (getobject ($object) == ".folder") $sql = 'SELECT object_id FROM object WHERE objectpath LIKE _utf8"'.substr (trim($object), 0, strlen (trim($object))-7).'%" COLLATE utf8_bin';
    else $sql = 'SELECT object_id FROM object WHERE objectpath=_utf8"'.$object.'" COLLATE utf8_bin';

    $errcode = "50029";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');
    
    if ($done)
    {
      $i = 1;
      
      while ($object_id = $db->getResultRow ('select'))
      {
        $sql = 'INSERT INTO recipient (object_id, date, from_user, to_user, email) ';    
        $sql .= 'VALUES ("'.intval ($object_id['object_id']).'", "'.$date.'", "'.$from_user.'", "'.$to_user.'", "'.$email.'")';
        
        $errcode = "50030";
        $done = $db->query ($sql, $errcode, $mgmt_config['today'], $i++);
      }
    }

    // save log
    savelog ($db->getError());    
    $db->close();   
         
    return true;
  }
  else return false;
}

// ------------------------------------------------ get recipients -------------------------------------------------

function rdbms_getrecipients ($object)
{
  global $mgmt_config;

  if ($object != "")
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
      
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // clean input
    $object = $db->escape_string ($object);    
    $object = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $object);    
    
    // get recipients
    $sql = 'SELECT rec.recipient_id, rec.object_id, rec.date, rec.from_user, rec.to_user, rec.email FROM recipient AS rec, object AS obj WHERE obj.object_id=rec.object_id AND obj.objectpath=_utf8"'.$object.'" COLLATE utf8_bin';   

    $errcode = "50031";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'select');
    
    if ($done)
    {
      $i = 0;
      $recipient = array();
      
      while ($row = $db->getResultRow ('select'))
      {
        $recipient[$i]['recipient_id'] = $row['recipient_id'];
        $recipient[$i]['object_id'] = $row['object_id'];
        $recipient[$i]['date'] = $row['date'];
        $recipient[$i]['from_user'] = $row['from_user']; 
        $recipient[$i]['to_user'] = $row['to_user'];  
        $recipient[$i]['email'] = $row['email'];
               
        $i++;
      }
    }

    // save log
    savelog ($db->getError());    
    $db->close();      
         
    if (!empty ($recipient) && sizeof ($recipient) > 0) return $recipient;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- delete recipient -------------------------------------------------

function rdbms_deleterecipient ($recipient_id)
{
  global $mgmt_config;
  
  if ($recipient_id != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // clean input
    $recipient_id = $db->escape_string ($recipient_id);
        
    $sql = 'DELETE FROM recipient WHERE recipient_id='.$recipient_id;
     
    $errcode = "50032";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // save log
    savelog ($db->getError ());    
    $db->close();      
         
    return true;
  }
  else return false;
}

// ----------------------------------------------- create queue entry -------------------------------------------------

function rdbms_createqueueentry ($action, $object, $date, $published_only=0, $user)
{
  global $mgmt_config;

  if ($action != "" && $object != "" && is_date ($date, "Y-m-d H:i") && $user != "" && (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0))
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
 
    $object_id = rdbms_getobject_id ($object);
    
    if ($object_id != false)
    {
      $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
      
      // clean input
      $action = $db->escape_string ($action);
      $object = $db->escape_string ($object);
      $date = $db->escape_string ($date);
      if ($published_only != "") $published_only = $db->escape_string ($published_only);
      $user = $db->escape_string ($user);
      
      $sql = 'INSERT INTO queue (object_id, action, date, published_only, user) ';    
      $sql .= 'VALUES ('.intval ($object_id).', "'.$action.'", "'.$date.'", '.intval ($published_only).', "'.$user.'")';
      
      $errcode = "50033";
      $done = $db->query ($sql, $errcode, $mgmt_config['today']); 
        
      // save log
      savelog ($db->getError());
    
      $db->close();
      
      return $done;
    }
    else return false;
  }
  else return false;
}

// ------------------------------------------------ get queue entries -------------------------------------------------

function rdbms_getqueueentries ($action="", $site="", $date="", $user="", $object="")
{
  global $mgmt_config;

  if (is_array ($mgmt_config))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    // check object (can be valid path or ID)
    if (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0) $object_id = rdbms_getobject_id ($object);
    elseif (is_numeric ($object)) $object_id = $object; 
    elseif ($object != "") return false;  
    
    // clean input
    if (!empty ($action)) $action = $db->escape_string ($action);
    if (!empty ($site)) $site = $db->escape_string ($site);
    if (!empty ($date)) $date = $db->escape_string ($date);
    if (!empty ($user)) $user = $db->escape_string ($user);
    if (!empty ($object_id)) $object_id = $db->escape_string ($object_id);

    // get recipients
    $sql = 'SELECT que.queue_id, que.action, que.date, que.published_only, que.user, obj.objectpath FROM queue AS que, object AS obj WHERE obj.object_id=que.object_id';
    if (!empty ($action)) $sql .= ' AND que.action="'.$action.'"';
    if (!empty ($site)) $sql .= ' AND (obj.objectpath LIKE _utf8"*page*/'.$site.'/%" COLLATE utf8_bin OR obj.objectpath LIKE _utf8"*comp*/'.$site.'/%" COLLATE utf8_bin)';
    if (!empty ($date)) $sql .= ' AND que.date<="'.$date.'"'; 
    if (!empty ($user)) $sql .= ' AND que.user="'.$user.'"';
    if (!empty ($object_id)) $sql .= ' AND que.object_id="'.$object_id.'"';
    $sql .= ' ORDER BY que.date';
  
    $errcode = "50034";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');
    
    $queue = array();
          
    if ($done)
    {  
      $i = 0;
      
      // insert recipients
      while ($row = $db->getResultRow ('select'))
      {
        $queue[$i]['queue_id'] = $row['queue_id'];
        $queue[$i]['action'] = $row['action'];
        $queue[$i]['objectpath'] = str_replace (array("*page*", "*comp*"), array("%page%", "%comp%"), $row['objectpath']);
        $queue[$i]['date'] = $row['date'];
        $queue[$i]['published_only'] = $row['published_only'];
        $queue[$i]['user'] = $row['user'];        
        $i++;
      }        
    }
  
    // save log
    savelog ($db->getError());
    
    $db->close();
    
    if (is_array ($queue) && sizeof ($queue) > 0) return $queue;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- delete queue entry -------------------------------------------------

function rdbms_deletequeueentry ($queue_id)
{
  global $mgmt_config;
  
  if ($queue_id != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    // clean input
    $queue_id = $db->escape_string ($queue_id);
    
    // query
    $sql = 'DELETE FROM queue WHERE queue_id='.$queue_id;
     
    $errcode = "50035";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // save log
    savelog ($db->getError ());

    $db->close();
         
    return true;
  }
  else return false;
}

// ----------------------------------------------- create notification -------------------------------------------------

function rdbms_createnotification ($object, $events, $user)
{
  global $mgmt_config;

  if ($object != "" && is_array ($events) && $user != "")
  {
    // correct object name 
    if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
    
    // check object (can be path or ID)
    if (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0) $object_id = rdbms_getobject_id ($object);
    elseif (is_numeric ($object)) $object_id = $object;
    else $object_id = false;
    
    if ($object_id != false)
    {
      $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
      
      // clean input
      $user = $db->escape_string ($user);
      if (array_key_exists ("oncreate", $events) && $events['oncreate'] == 1) $oncreate = 1;
      else $oncreate = 0;
      if (array_key_exists ("onedit", $events) && $events['onedit'] == 1) $onedit = 1;
      else $onedit = 0;
      if (array_key_exists ("onmove", $events) && $events['onmove'] == 1) $onmove = 1;
      else $onmove = 0;
      if (array_key_exists ("ondelete", $events) && $events['ondelete'] == 1) $ondelete = 1;
      else $ondelete = 0;
      
      $sql = 'SELECT count(*) AS count FROM notify WHERE object_id="'.$object_id.'" AND user="'.$user.'"';
      
      $errcode = "50193";
      $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'select');
      
      if ($done)
      {
        $result = $db->getResultRow ('select', 0);
        $count = $result['count'];
  
        if ($count == 0)
        {
          $sql = 'INSERT INTO notify (object_id, user, oncreate, onedit, onmove, ondelete) ';    
          $sql .= 'VALUES ('.intval ($object_id).', "'.$user.'", '.$oncreate.', '.$onedit.', '.$onmove.', '.$ondelete.')';
        }
        else
        {
          $sql = 'UPDATE notify SET oncreate='.$oncreate.', onedit='.$onedit.', onmove='.$onmove.', ondelete='.$ondelete.' WHERE object_id='.$object_id.' AND user="'.$user.'"';
        }
        
        $errcode = "50093";
        $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'insert'); 
      }
      
      // save log
      savelog ($db->getError());
    
      $db->close();
      
      return $done;
    }
    else return false;
  }
  else return false;
}

// ------------------------------------------------ get notifications -------------------------------------------------

function rdbms_getnotification ($event="", $object="", $user="")
{
  global $mgmt_config;

  if (is_array ($mgmt_config))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    if (!empty($event))
    {
      $valid_events = array ("oncreate", "onedit", "onmove", "ondelete");
      if (!in_array (strtolower($event), $valid_events)) $event = "";
    }
    
    if ($object != "")
    {
      $object_id_array = array();
      
      // correct object name 
      if (strtolower (@strrchr ($object, ".")) == ".off") $object = @substr ($object, 0, -4);
      
      // get publication
      $site = getpublication ($object);
      $fileinfo = getfileinfo ($site, $object, "");
      if (getobject ($object) == ".folder") $object = getlocation ($object);

      // clean input
      $object = $db->escape_string ($object);
      $object = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $object);

      // get connected objects
      $sql = 'SELECT DISTINCT object_id, id FROM object WHERE objectpath=_utf8"'.$object.'" COLLATE utf8_bin';
      
      $errcode = "50097";
      $done = $db->query($sql, $errcode, $mgmt_config['today'], 'connected');
      
      if ($done)
      {
        // get object ID and container ID of object
        if ($row = $db->getResultRow ('connected'))
        {
          $object_id = $row['object_id'];
          $container_id = $row['id'];
        }
        
        // get object IDs of connected objects
        if (!empty ($container_id))
        {
          $sql = 'SELECT DISTINCT object_id FROM object WHERE id="'.$container_id.'" AND object_id!="'.$object_id.'"';
      
          $errcode = "50298";
          $done = $db->query($sql, $errcode, $mgmt_config['today'], 'connected');
          
          if ($done)
          {
            while ($row = $db->getResultRow ('connected'))
            {
              $object_id_array[] = $row['object_id'];
            }
          }
        }
      }
      
      // get objects that referred to the object
      if (!empty ($object_id))
      {
        $sql = 'SELECT object.object_id FROM textnodes INNER JOIN object ON object.id=textnodes.id WHERE textnodes.object_id="'.$object_id.'"';
        
        $errcode = "50299";
        $done = $db->query($sql, $errcode, $mgmt_config['today'], 'linked');
        
        if ($done)
        {
          while ($row = $db->getResultRow ('linked'))
          {
            $object_id_array[] = $row['object_id'];
          }
        }
      }
    }
    
    if ($user != "") $user = $db->escape_string ($user);
        
    // get recipients
    $sql = 'SELECT nfy.notify_id, nfy.object_id, obj.objectpath, nfy.user, nfy.oncreate, nfy.onedit, nfy.onmove, nfy.ondelete FROM notify AS nfy, object AS obj WHERE obj.object_id=nfy.object_id';
    if ($event != "") $sql .= ' AND nfy.'.$event.'=1';
    if ($object != "") $sql .= ' AND (obj.objectpath="'.$object.'" OR (INSTR(obj.objectpath, ".folder") > 0 AND INSTR("'.$object.'", SUBSTR(obj.objectpath, 1, INSTR(obj.objectpath, ".folder") - 1)) > 0))';
    if (!empty ($object_id_array) && sizeof ($object_id_array) > 0) $sql .= ' OR nfy.object_id IN ('.implode (",", $object_id_array).')'; 
    if ($user != "") $sql .= ' AND nfy.user="'.$user.'"';
    $sql .= ' ORDER BY obj.objectpath';

    $errcode = "50094";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {  
      $i = 0;
      // insert recipients
      while ($row = $db->getResultRow ('select'))
      {
        $queue[$i]['notify_id'] = $row['notify_id'];
        $queue[$i]['object_id'] = $row['object_id'];
        $queue[$i]['objectpath'] = str_replace (array("*page*", "*comp*"), array("%page%", "%comp%"), $row['objectpath']);
        $queue[$i]['user'] = $row['user']; 
        $queue[$i]['oncreate'] = $row['oncreate'];
        $queue[$i]['onedit'] = $row['onedit'];
        $queue[$i]['onmove'] = $row['onmove'];
        $queue[$i]['ondelete'] = $row['ondelete'];

        $i++;
      }        
    }

    // save log
    savelog ($db->getError());    
    $db->close();
    
    if (is_array (@$queue)) return $queue;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- delete notification -------------------------------------------------

function rdbms_deletenotification ($notify_id, $object="", $user="")
{
  global $mgmt_config;
  
  if ($notify_id != "" || $object != "" || $user != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    if ($object != "")
    {
      // check object (can be path or ID)
      if (substr_count ($object, "%page%") > 0 || substr_count ($object, "%comp%") > 0) $object_id = rdbms_getobject_id ($object);
      elseif (is_numeric ($object)) $object_id = $object;
      else $object_id = false;
    }
    
    // clean input
    if (!empty($notify_id)) $notify_id = $db->escape_string ($notify_id);
    elseif (!empty($object_id)) $object_id = $db->escape_string ($object_id);
    elseif (!empty($user)) $user = $db->escape_string ($user);
        
    if (!empty($notify_id)) $sql = 'DELETE FROM notify WHERE notify_id="'.$notify_id.'"';
    elseif (!empty($object_id)) $sql = 'DELETE FROM notify WHERE object_id="'.$object_id.'"';
    elseif (!empty($user)) $sql = 'DELETE FROM notify WHERE user="'.$user.'"';
     
    $errcode = "50092";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // save log
    savelog ($db->getError ());    
    $db->close();      
         
    return true;
  }
  else return false;
}

// ----------------------------------------------- license notification -------------------------------------------------

function rdbms_licensenotification ($folderpath, $text_id, $date_begin, $date_end, $format="%Y-%m-%d")
{
  global $mgmt_config;
  
  if ($folderpath != "" && $text_id != "" && $date_begin != "" && $date_end != "")
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    $folderpath = $db->escape_string ($folderpath);
    $text_id = $db->escape_string ($text_id);
    $date_begin = $db->escape_string ($date_begin);
    $date_end = $db->escape_string ($date_end);
    $format = $db->escape_string ($format);
    
    $folderpath = str_replace (array("%page%", "%comp%"), array("*page*", "*comp*"), $folderpath);
   
    $sql = 'SELECT DISTINCT obj.objectpath as path, tnd.textcontent as cnt FROM object AS obj, textnodes AS tnd ';
    $sql .= 'WHERE obj.id=tnd.id AND obj.objectpath LIKE _utf8"'.$folderpath.'%" COLLATE utf8_bin AND tnd.text_id=_utf8"'.$text_id.'" COLLATE utf8_bin  AND "'.$date_begin.'" <= STR_TO_DATE(tnd.textcontent, "'.$format.'") AND "'.$date_end.'" >= STR_TO_DATE(tnd.textcontent, "'.$format.'")';    
    $errcode = "50036";
    $done = $db->query($sql, $errcode, $mgmt_config['today']);

    if ($done)
    {
      $i = 0;
      
      while ($row = $db->getResultRow ())
      {
        $objectpath = str_replace (array("*page*", "*comp*"), array("%page%", "%comp%"), $row['path']);
        $licenseend = $row['cnt']; 
        $site = getpublication ($objectpath);
        $location = getlocation ($objectpath);    
        $object = getobject ($objectpath);
        $cat = getcategory ($site, $location);
     
        $result[$i]['publication'] = $site;
        $result[$i]['location'] = $location;
        $result[$i]['object'] = $object;
        $result[$i]['category'] = $cat;
        $result[$i]['date'] = $licenseend;
        $i++;
      }
    }

    // save log
    savelog ($db->getError());
    $db->close();
    
    if (!empty ($result) && is_array ($result)) return $result;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- daily statistics -------------------------------------------------
// Update the daily statistics after a loggable event.
// The dailystat table contains a counter for each 'activity' (i.e. download) for each object (i.e. media file of container) per day.

function rdbms_insertdailystat ($activity, $container_id, $user="")
{
  global $mgmt_config;
  
  if ($activity != "" && $container_id != "")
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    // clean input    
    $activity = $db->escape_string ($activity);
    $container_id = $db->escape_string ($container_id);
    if ($user != "") $user = $db->escape_string ($user);

    // get current date
    $date = date ("Y-m-d", time());

    // set user if not defined
    if ($user == "")
    {
      if (!empty ($_SESSION['hcms_user'])) $user = $_SESSION['hcms_user'];
      else $user = getuserip ();
    }
    
    // check to see if there is a row
    $sql = 'SELECT count(*) AS count FROM dailystat WHERE date="'.$date.'" AND user="'.$user.'" AND activity="'.$activity.'" AND id='.$container_id;
    
    $errcode = "50037";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'select');
    
    if ($done)
    {
      $result = $db->getResultRow ('select', 0);
      $count = $result['count'];

      if ($count == 0)
      {
        // insert
        $sql = 'INSERT INTO dailystat (id,user,activity,date,count) VALUES ('.$container_id.',"'.$user.'","'.$activity.'","'.$date.'",1)';
      }
      else
      {
        // update
        $sql = 'UPDATE dailystat SET count=count+1 WHERE date="'.$date.'" AND user="'.$user.'" AND activity="'.$activity.'" AND id='.$container_id;
      }

      $errcode = "50038";
      $db->query ($sql, $errcode, $mgmt_config['today'], 'insertupdate');

      // save log
      savelog ($db->getError());
      $db->close();    

      return true;  
    }
    else
    {
      // save log
      savelog ($db->getError());
      $db->close();    
      
      return false;
    }
  }
  else return false;  
}

// ----------------------------------------------- get statistics from dailystat -------------------------------------------------

function rdbms_getmediastat ($date_from="", $date_to="", $activity="", $container_id="", $objectpath="", $user="", $type="media")
{
  global $mgmt_config;

  // mySQL connect
  $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
  
  // clean input
  if ($date_from != "") $date_from = $db->escape_string ($date_from);
  if ($date_to != "") $date_to = $db->escape_string ($date_to);
  if ($activity != "") $activity = $db->escape_string ($activity);
  if ($container_id != "") $container_id = $db->escape_string (intval($container_id));
  if ($user != "") $user = $db->escape_string ($user);
  
  // get object info
  if ($objectpath != "")
  {
    $site = getpublication ($objectpath);
    $cat = getcategory ($site, $objectpath);
    $object_info = getfileinfo ($site, $objectpath, $cat);
    if (getobject ($objectpath) == ".folder") $location = getlocation ($objectpath);
  }
  
  // media file
  if ($type == "media")
  {
    if ($objectpath != "")
    {
      $objectpath = $db->escape_string ($objectpath);
      $objectpath = str_replace ('%', '*', $objectpath);

      $sqlfilesize = ', SUM(media.filesize) filesize';
      $sqltable = ", media, object";
      $sqlwhere = " WHERE dailystat.id = media.id";
    }
    else
    {
      $sqlfilesize = ', media.filesize';
      $sqltable = ", media";
      $sqlwhere = " WHERE dailystat.id = media.id";
    }
  }
  // object
  else
  {
    $sqlfilesize = "";
    $sqltable = "";
    $sqlwhere = " WHERE dailystat.id!=''";
  }
  
  $sql = 'SELECT dailystat.id, dailystat.date, dailystat.activity, SUM(dailystat.count) count'.$sqlfilesize.', user FROM dailystat'.$sqltable.' '.$sqlwhere; 
  
  if ($objectpath != "")
  {
    // search by objectpath
    $sql .= ' AND dailystat.id = object.id';
    
    if ($object_info['type'] == 'Folder') $sql .= ' AND object.objectpath like "'.$location.'%"';
    else $sql .= ' AND object.objectpath = "'.$objectpath.'"';
  }
  elseif ($container_id != "")
  { 
    // search by containerid
    $sql .= ' AND dailystat.id='.$container_id;
  }
  
  if ($date_from != "") $sql .= ' AND dailystat.date>="'.date("Y-m-d", strtotime($date_from)).'"';
  if ($date_to != "") $sql .= ' AND dailystat.date<="'.date("Y-m-d", strtotime($date_to)).'"';
  if ($activity != "") $sql .= ' AND dailystat.activity="'.$activity.'"';
  if ($user != "") $sql .= ' AND dailystat.user="'.$user.'"';
  $sql .= ' GROUP BY dailystat.date, dailystat.user ORDER BY dailystat.date';

  $errcode = "50039";
  $done = $db->query ($sql, $errcode, $mgmt_config['today']);

  if ($done)
  {
    $i = 0;
    
    // stats array
    while ($row = $db->getResultRow ())
    {
      $dailystat[$i]['container_id'] = $row['id'];
      $dailystat[$i]['date'] = $row['date'];
      $dailystat[$i]['activity'] = $row['activity'];
      $dailystat[$i]['count'] = $row['count'];
      $dailystat[$i]['filesize'] = $row['filesize'];
      $dailystat[$i]['user'] = $row['user'];
      $i++;
    }
  }     

  // save log
  savelog ($db->getError ());
  $db->close();
       
  if (is_array (@$dailystat)) return $dailystat;
  else return false;
}

// ----------------------------------------------- get filesize from media -------------------------------------------------

function rdbms_getfilesize ($container_id="", $objectpath="")
{
  global $mgmt_config;
  
  if ($container_id != "" || $objectpath != "")
  {
    // mySQL connect
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    // get file size based on
    // container id
    if ($container_id != "")
    {
      $container_id = $db->escape_string ($container_id);
      
      $sqladd = ' WHERE media.id='.$container_id;
      
      $sqlfilesize = 'filesize';
    }
    // full media storage
    elseif ($objectpath == "%hcms%")
    {
      $sqladd = '';
      $sqlfilesize = 'SUM(filesize) AS filesize';
    }
    // object path
    elseif ($objectpath != "")
    {
      $site = getpublication ($objectpath);
      $cat = getcategory ($site, $objectpath);
      $object_info = getfileinfo ($site, $objectpath, $cat);
      
      $objectpath = $db->escape_string ($objectpath);
      $objectpath = str_replace ('%', '*', $objectpath);
      
      if (getobject ($objectpath) == ".folder") $objectpath = getlocation ($objectpath);
      
      $sqladd = ', object WHERE media.id = object.id';
      
      if ($object_info['type'] == "Folder") $sqladd .= ' AND object.objectpath LIKE "'.$objectpath.'%"';
      else $sqladd .= ' AND object.objectpath = "'.$objectpath.'"';
      
      $sqlfilesize = 'SUM(filesize) AS filesize';
    }
    
    $sql = 'SELECT '.$sqlfilesize.' FROM media '.$sqladd;
    
    $errcode = "50543";
    $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'selectfilesize');
    
    if ($done)
    {
      $row = $db->getResultRow ('selectfilesize');
      $result['filesize'] = $row['filesize'];
      $result['count'] = 1;
    }

    // count files
    if ($objectpath != "" && !empty ($object_info['type']) && $object_info['type'] == "Folder")
    {
      $sql = 'SELECT count(objectpath) AS count FROM object WHERE objectpath LIKE "'.$objectpath.'%"'; 

      $errcode = "50042";
      $done = $db->query ($sql, $errcode, $mgmt_config['today'], 'selectcount');
      
      if ($done)
      {
        $row = $db->getResultRow ('selectcount');
        $result['count'] = $row['count'];
      }
    }

    // save log
    savelog ($db->getError ());
    $db->close();
         
    if (isset ($result) && is_array ($result)) return $result;
    else return false;
  } 
  else return false;
}

// ----------------------------------------------- create task -------------------------------------------------

function rdbms_createtask ($object_id, $project_id=0, $from_user="", $to_user, $startdate="", $finishdate="", $category="", $taskname, $description="", $priority="low", $planned="")
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php") && $taskname != "" && strlen ($taskname) <= 200 && strlen ($description) <= 3600 && in_array (strtolower ($priority), array("low","medium","high")))
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {
      // get object id
      $object_id = rdbms_getobject_id ($object_id);
    }
    
    // get current date
    if ($startdate == "") $startdate = date ("Y-m-d H:i:s", time());
    else $startdate = $db->escape_string ($startdate);
    
    // clean input    
    if ($object_id != "") $object_id = intval ($object_id);
    else $object_id = 0;
    if ($project_id != "") $project_id = intval ($project_id);
    else $project_id = 0;
    if ($from_user != "") $from_user = $db->escape_string ($from_user);
    if ($to_user != "") $to_user = $db->escape_string ($to_user);
    if ($finishdate != "") $finishdate = $db->escape_string ($finishdate);
    if ($category != "") $category = $db->escape_string ($category);
    else $category = "user";
    $taskname = $db->escape_string ($taskname);
    if ($description != "") $description = $db->escape_string ($description);
    if ($priority != "") $priority = $db->escape_string (strtolower ($priority));
    if ($planned != "") $planned = $db->escape_string (correctnumber ($planned));

    // set user if not defined
    if ($from_user == "")
    {
      if (!empty ($_SESSION['hcms_user'])) $from_user = $_SESSION['hcms_user'];
      elseif (getuserip () != "") $from_user = getuserip ();
      else $from_user = "System";
    }

    // insert
    $sql = 'INSERT INTO task (object_id,project_id,task,from_user,to_user,startdate,finishdate,category,description,priority,planned,status) VALUES ('.$object_id.','.$project_id.',"'.$taskname.'","'.$from_user.'","'.$to_user.'","'.$startdate.'","'.$finishdate.'","'.$category.'","'.$description.'","'.$priority.'","'.$planned.'",0)';

    $errcode = "50048";
    $db->query ($sql, $errcode, $mgmt_config['today'], 'insert');

    // save log
    savelog ($db->getError());
    $db->close();

    return true;
  } 
  else return false;
}

// ----------------------------------------------- set task -------------------------------------------------

function rdbms_settask ($task_id, $object_id="", $project_id="", $to_user="", $startdate="", $finishdate="", $taskname="", $description="", $priority="", $status="", $planned="", $actual="")
{
  global $mgmt_config;
  
  if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php") && $task_id != "" && ($taskname == "" || strlen ($taskname) <= 200) && ($description == "" || strlen ($description) <= 3600) && ($priority == "" || in_array (strtolower ($priority), array("low","medium","high"))))
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // clean input
    $sql_update = array();
    
    if ($object_id != "") $sql_update[] = 'object_id="'.intval($object_id).'"';
    if ($project_id != "") $sql_update[] = 'project_id="'.intval($project_id).'"';
    if ($to_user != "") $sql_update[] = 'to_user="'.$db->escape_string ($to_user).'"';
    if ($startdate != "") $sql_update[] = 'startdate="'.$db->escape_string ($startdate).'"';
    if ($finishdate != "") $sql_update[] = 'finishdate="'.$db->escape_string ($finishdate).'"';
    if ($taskname != "") $sql_update[] = 'task="'.$db->escape_string ($taskname).'"';
    if ($description != "") $sql_update[] = 'description="'.$db->escape_string ($description).'"';
    if ($priority != "") $sql_update[] = 'priority="'.$db->escape_string (strtolower ($priority)).'"';
    if ($status != "") $sql_update[] = 'status="'.intval ($status).'"';
    if ($planned != "") $sql_update[] = 'planned="'.correctnumber($planned).'"';
    if ($actual != "") $sql_update[] = 'actual="'.correctnumber($actual).'"';

    // insert
    $sql = 'UPDATE task SET ';
    $sql .= implode (", ", $sql_update);
    $sql .= ' WHERE task_id='.intval($task_id);

    $errcode = "50058";
    $db->query ($sql, $errcode, $mgmt_config['today'], 'update');

    // save log
    savelog ($db->getError());
    $db->close();

    return true;
  } 
  else return false;
}

// ------------------------------------------------ get task -------------------------------------------------

function rdbms_gettask ($task_id="", $object_id="", $project_id="", $from_user="", $to_user="", $startdate="", $finishdate="", $order_by="startdate DESC")
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php") && is_array ($mgmt_config))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {
      // get object id
      $object_id = rdbms_getobject_id ($object_id);
    }
    
    // clean input
    if ($task_id > 0) $task_id = intval ($task_id);
    if ($object_id > 0) $object_id = intval ($object_id);
    if ($project_id > 0) $project_id = intval ($project_id);
    if ($from_user != "") $from_user = $db->escape_string ($from_user);
    if ($to_user != "") $to_user = $db->escape_string ($to_user);
    if ($startdate != "") $startdate = $db->escape_string ($startdate);
    if ($finishdate != "") $finishdate = $db->escape_string ($finishdate);
    if ($order_by != "") $order_by = $db->escape_string ($order_by);
        
    // get recipients
    $sql = 'SELECT task_id, object_id, project_id, task, from_user, to_user, startdate, finishdate, category, description, priority, status, planned, actual FROM task';
    
    if ($task_id > 0)
    {
      $sql .= ' WHERE task_id="'.$task_id.'"';
    }
    else
    {
      $sql .= ' WHERE 1=1';
      if ($object_id > 0) $sql .= ' AND object_id="'.$object_id.'"';
      if ($project_id > 0) $sql .= ' AND project_id="'.$project_id.'"';  
      if ($from_user != "") $sql .= ' AND from_user="'.$from_user.'"';
      if ($to_user != "") $sql .= ' AND to_user="'.$to_user.'"';
      if ($startdate != "") $sql .= ' AND startdate="'.$startdate.'"';
      if ($finishdate != "") $sql .= ' AND finishdate="'.$finishdate.'"';
    }
    if ($order_by != "") $sql .= ' ORDER BY '.$order_by;

    $errcode = "50094";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {
      $result = array();
      $i = 0;
      
      // insert recipients
      while ($row = $db->getResultRow ('select'))
      {
        $result[$i]['task_id'] = $row['task_id'];
        $result[$i]['object_id'] = $row['object_id'];
        $result[$i]['objectpath'] = rdbms_getobject($row['object_id']);
        $result[$i]['project_id'] = $row['project_id'];
        $result[$i]['taskname'] = $row['task'];
        $result[$i]['from_user'] = $row['from_user']; 
        $result[$i]['to_user'] = $row['to_user'];
        $result[$i]['startdate'] = $row['startdate'];
        $result[$i]['finishdate'] = $row['finishdate'];
        $result[$i]['category'] = $row['category'];
        $result[$i]['description'] = $row['description'];
        $result[$i]['priority'] = $row['priority'];
        $result[$i]['status'] = $row['status'];
        $result[$i]['planned'] = $row['planned'];
        $result[$i]['actual'] = $row['actual'];

        $i++;
      }        
    }

    // save log
    savelog ($db->getError());    
    $db->close();
    
    if (!empty ($result) && is_array (@$result)) return $result;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- delete task -------------------------------------------------

function rdbms_deletetask ($task_id="", $object_id="", $to_user="")
{
  global $mgmt_config;
  
  if (is_file ($mgmt_config['abs_path_cms']."task/task_list.php") && $task_id != "" || $object_id != "" || $to_user != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {
      // get object id
      $object_id = rdbms_getobject_id ($object_id);
    }
    
    // clean input
    if (!empty ($task_id)) $task_id = intval ($task_id);
    elseif (!empty ($object_id)) $object_id = intval ($object_id);
    elseif (!empty ($to_user)) $to_user = $db->escape_string ($to_user);
        
    if (!empty ($task_id)) $sql = 'DELETE FROM task WHERE task_id="'.$task_id.'"';
    elseif (!empty ($object_id)) $sql = 'DELETE FROM task WHERE object_id="'.$object_id.'"';
    elseif (!empty ($to_user)) $sql = 'DELETE FROM task WHERE to_user="'.$to_user.'"';
     
    $errcode = "50098";
    $db->query ($sql, $errcode, $mgmt_config['today']);
    
    // save log
    savelog ($db->getError ());    
    $db->close();      
         
    return true;
  }
  else return false;
}

// ----------------------------------------------- create project -------------------------------------------------

function rdbms_createproject ($subproject_id, $object_id=0, $user="", $projectname, $description="")
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."project/project_list.php") && $projectname != "" && strlen ($projectname) <= 200 && strlen ($description) <= 3600)
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {
      // get object id
      $object_id = rdbms_getobject_id ($object_id);
    }
    
    // clean input
    if ($subproject_id != "") $subproject_id = intval ($subproject_id);
    else $subproject_id = 0;
    if ($object_id != "") $object_id = intval ($object_id);
    else $object_id = 0;
    if ($user != "") $user = $db->escape_string ($user);
    $projectname = $db->escape_string ($projectname);
    if ($description != "") $description = $db->escape_string ($description);

    // set user if not defined
    if ($user == "")
    {
      if (!empty ($_SESSION['hcms_user'])) $user = $_SESSION['hcms_user'];
      elseif (getuserip () != "") $user = getuserip ();
      else $user = "System";
    }

    // insert
    $sql = 'INSERT INTO project (subproject_id,object_id,createdate,project,user,description) VALUES ('.$subproject_id.','.$object_id.',"'.date ("Y-m-d H:i:s", time()).'","'.$projectname.'","'.$user.'","'.$description.'")';

    $errcode = "50068";
    $db->query ($sql, $errcode, $mgmt_config['today'], 'insert');

    // save log
    savelog ($db->getError());
    $db->close();

    return true;
  } 
  else return false;
}

// ----------------------------------------------- set project -------------------------------------------------

function rdbms_setproject ($project_id, $subproject_id=0, $object_id="", $user="", $projectname="", $description="")
{
  global $mgmt_config;
  
  if (is_file ($mgmt_config['abs_path_cms']."project/project_list.php") && $project_id > 0 && ($projectname == "" || strlen ($projectname) <= 200) && ($description == "" || strlen ($description) <= 3600))
  {
    $db = new hcms_db ($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    

    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {      
      $object_id = rdbms_getobject_id ($object_id);
    }
    
    // clean input
    $sql_update = array();
    
    if ($subproject_id != "") $sql_update[] = 'subproject_id="'.intval($subproject_id).'"';
    if ($object_id != "") $sql_update[] = 'object_id="'.intval ($object_id).'"';
    if ($user != "") $sql_update[] = 'user="'.$db->escape_string ($user).'"';
    if ($projectname != "") $sql_update[] = 'project="'.$db->escape_string ($projectname).'"';
    if ($description != "") $sql_update[] = 'description="'.$db->escape_string ($description).'"';

    // insert
    $sql = 'UPDATE project SET ';
    $sql .= implode (", ", $sql_update);
    $sql .= ' WHERE project_id='.intval($project_id);
    
    $errcode = "50069";
    $db->query ($sql, $errcode, $mgmt_config['today'], 'update');

    // save log
    savelog ($db->getError());
    $db->close();

    return true;
  } 
  else return false;
}

// ------------------------------------------------ get project -------------------------------------------------

function rdbms_getproject ($project_id="", $subproject_id="", $object_id="", $user="", $order_by="project")
{
  global $mgmt_config;

  if (is_file ($mgmt_config['abs_path_cms']."project/project_list.php") && is_array ($mgmt_config))
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);    
    
    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {      
      $object_id = rdbms_getobject_id ($object_id);
    }
    
    // clean input
    if ($project_id != "") $project_id = intval ($project_id);
    if (is_int ($subproject_id)) $subproject_id = intval ($subproject_id);
    if ($object_id != "") $object_id = intval ($object_id);
    if ($user != "") $user = $db->escape_string ($user);
    if ($order_by != "") $order_by = $db->escape_string ($order_by);
        
    // get recipients
    $sql = 'SELECT project_id, subproject_id, object_id, project, user, description FROM project WHERE 1=1';
    
    if ($project_id > 0 && $subproject_id < 1) $sql .= ' AND project_id="'.$project_id.'"';
    elseif ($project_id < 1 && $subproject_id >= 0) $sql .= ' AND subproject_id="'.$subproject_id.'"';
    elseif ($project_id > 0 && $subproject_id >= 0) $sql .= ' AND (project_id="'.$project_id.'" OR subproject_id="'.$subproject_id.'")';
    
    if ($object_id != "") $sql .= ' AND object_id="'.$object_id.'"';    
    if ($user != "") $sql .= ' AND user="'.$user.'"';
    if ($order_by != "") $sql .= ' ORDER BY '.$order_by;

    $errcode = "50064";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    {
      $result = array();
      $i = 0;
      
      // insert recipients
      while ($row = $db->getResultRow ('select'))
      {
        $result[$i]['project_id'] = $row['project_id'];
        $result[$i]['subproject_id'] = $row['subproject_id'];
        $result[$i]['object_id'] = $row['object_id'];
        $result[$i]['objectpath'] = rdbms_getobject ($row['object_id']);
        $result[$i]['projectname'] = $row['project'];
        $result[$i]['user'] = $row['user']; 
        $result[$i]['description'] = $row['description'];
        if ($row['subproject_id'] > 0) $result[$i]['type'] = "Subproject";
        else $result[$i]['type'] = "Project";

        $i++;
      }        
    }

    // save log
    savelog ($db->getError());    
    $db->close();
    
    if (!empty ($result) && is_array ($result)) return $result;
    else return false;
  }
  else return false;
}

// ----------------------------------------------- delete project -------------------------------------------------

function rdbms_deleteproject ($project_id="", $object_id="", $user="")
{
  global $mgmt_config;
  
  if (is_file ($mgmt_config['abs_path_cms']."project/project_list.php") && $project_id != "" || $object_id != "" || $user != "")
  {   
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    // try to get object_id from object path
    if ($object_id != "" && intval ($object_id) < 1)
    {      
      $object_id = rdbms_getobject_id ($object_id);
    }
    
    // clean input
    if (!empty ($project_id)) $project_id = intval ($project_id);
    elseif (!empty ($object_id)) $object_id = intval ($object_id);
    elseif (!empty ($user)) $user = $db->escape_string ($user);
        
    if (!empty ($project_id)) $sql = 'DELETE FROM project WHERE project_id="'.$project_id.'"';
    elseif (!empty ($object_id)) $sql = 'DELETE FROM project WHERE object_id="'.$object_id.'"';
    elseif (!empty ($to_user)) $sql = 'DELETE FROM project WHERE user="'.$user.'"';
     
    $errcode = "50070";
    $db->query ($sql, $errcode, $mgmt_config['today']);

    // save log
    savelog ($db->getError ());    
    $db->close();      
         
    return true;
  }
  else return false;
}

// ----------------------------------------------- get table information -------------------------------------------------

function rdbms_gettableinfo ($table)
{
  global $mgmt_config;
  
  if ($table != "")
  {
    $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
    
    $table = $db->escape_string ($table);
    $sql = 'SHOW COLUMNS FROM `'.$table.'`';
    
    $errcode = "50099";
    $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');

    if ($done)
    { 
      $info = array();
      $i = 0;
      
      while ($row = $db->getResultRow ('select'))
      {
        $info[$i]['name'] = $row['Field'];
        $info[$i]['type'] = $row['Type'];
        $info[$i]['key'] = $row['Key'];
        $info[$i]['default'] = $row['Default'];
        $info[$i]['extra'] = $row['Extra'];
        $i++;
      }
    } 

    // save log
    savelog ($db->getError());    
    $db->close();
    
    if (!empty ($info) && is_array (@$info)) return $info;
    else return false;
  }
  else return false;
}

// -----------------------------------------------  external SQL query-------------------------------------------------

function rdbms_externalquery ($sql, $concat_by="")
{
  global $mgmt_config;
  
  if ($sql != "")
  {
    // anaylze SQL query regarding write operations
    $check_query = sql_clean_functions ($sql);
    
    if ($check_query['result'] == true)
    {
      $db = new hcms_db($mgmt_config['dbconnect'], $mgmt_config['dbhost'], $mgmt_config['dbuser'], $mgmt_config['dbpasswd'], $mgmt_config['dbname'], $mgmt_config['dbcharset']);
      
      // correct %comp% and %page% for query
      $sql = str_replace (array("%comp%/", "%page%/"), array("*comp*/", "*page*/"), $sql);
  
      $errcode = "50101";
      $done = $db->query($sql, $errcode, $mgmt_config['today'], 'select');
  
      if ($done)
      {
        $result = array();
        $i = 0;
        
        while ($row = $db->getResultRow ('select'))
        {
          // transform objectpath
          if (!empty ($row['objectpath'])) $row['objectpath'] = str_replace (array("*comp*/","*page*/"), array("%comp%/","%page%/"), $row['objectpath']);
        
          if ($concat_by != "" && !empty ($row[$concat_by]))
          {
            $i = $row[$concat_by];

            foreach ($row as $key=>$value)
            {
              // if result item is not set
              if (!isset ($result[$i][$key])) $result[$i][$key] = $value;
              // if value is number
              elseif (!empty ($result[$i][$key]) && is_numeric ($value)) $result[$i][$key] += $value;
              // if value is string
              elseif (empty ($result[$i][$key]) && $value != "") $result[$i][$key] .= $value;
            }
          }
          else
          {
            $result[$i] = $row;
            $i++;
          }
        }
      }
  
      // save log
      savelog ($db->getError());    
      $db->close();
      
      if (!empty ($result) && is_array (@$result)) return $result;
      else return false;
    }
    else return false;
  }
  else return false;
}
?>