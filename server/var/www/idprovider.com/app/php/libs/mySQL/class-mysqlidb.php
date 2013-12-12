<?php
/**
* @author Jonathan Gotti <nathan at the-ring dot homelinux dot net>
* @copyleft (l) 2003-2004  Jonathan Gotti
* @package DB
* @license http://opensource.org/licenses/gpl-license.php GNU Public License
* @subpackage MYSQL
* @changelog 2013-12-12 added mysqli support where needed (by Hookflash)
*            2005-02-28 new method optimize 
*            2004-12-03 now the associative_array_from_q2a_res method won't automaticly ksort the results 
*            2004-12-02 use the show fields query in place of a select statement and add extended_info mode to the get_fields method
*            2004-11-26 first version
*/
date_default_timezone_set('UTC');
/** class to deal with mysql databases */
class mysqldb{
  /**array of error number and msgs*/
  var $error;
  /**the last error array*/
  var $last_error;
  /**resource mysql connection*/
  var $conn;
  /**Db hostname*/
  var $host;
  /**mysql username*/
  var $user;
  /**mysql password*/
  var $pass;
  /**mysql selected database*/
  var $dbname;
  /**resource mysql db selected*/
  var $db;
  /** resource result handler*/
  var $last_qres;
  /**array of last query to array results*/
  var $last_q2a_res;
  var $beverbose = FALSE;
  var $autoconnect = TRUE;
  var $debug = FALSE;
  function mysqldb($dbname,$dbhost='localhost',$dbuser='root',$dbpass=''){ # most common config ?
    $this->host   = $dbhost;
    $this->user   = $dbuser;
    $this->pass   = $dbpass;
    $this->dbname = $dbname;
    $this->autoconnect= TRUE;
    $this->open();
    $this->beverbose  = FALSE;
  }
  function open(){  # only for convenience and because backport of sqlitedb
    return $this->check_conn('active');
  }
  function close(){
    return $this->check_conn('kill');
  }
  /**
  * Select the database to work on (it's the same as the use db command or mysqli_select_db function)
  * @param string $dbname
  * @return bool
  */
  function select_db($dbname=null){
    if(! ($dbname ||$this->dbname) )
      return FALSE;
    if($dbname)
      $this->dbname = $dbname;
    if(! $this->db = @mysqli_select_db($this->dbname,$this->conn)){
      $this->verbose("FATAL ERROR CAN'T CONNECT TO database ".$this->dbname);
      $this->set_error();
      return FALSE;
    }else{
      return $this->db;
    }
  }
  /**
  * check and activate db connection
  * @param string $action (active, kill, check) active by default
  */
  function check_conn($action = ''){ //var_dump($this->host, $this->user,$this->pass,TRUE,131074,mysqli_connect($this->host,$this->user,$this->pass,TRUE,131074),mysqli_connect_errno());die();
    if(! $host = @mysqli_get_host_info($this->conn)){
      switch ($action){
        case 'kill':
          return $host;
          break;
        case 'check':
          return $host;
          break;
        default:
        case 'active':
          if(! $this->conn = @mysqli_connect($this->host,$this->user,$this->pass,TRUE,131074)){
            $this->verbose("CONNECTION TO $this->host FAILED");
            return FALSE;
          }
          $this->verbose("CONNECTION TO $this->host ESTABLISHED");
          $this->select_db();
          return @mysqli_get_host_info($this->conn);
          break;
      }
    }else{
      switch($action){
        case 'kill':
          @mysqli_close($this->conn);
          $this->conn = $this->db = null;
          return true;
          break;
        case 'check':
          return $host;
          break;
        default:
        case 'active':
          return $host;
          break;
      }
    }
  }
  /**
  * send a select query to $table with arr $fields requested (all by default) and with arr $conditions
  * sample conds array is array(0=>'field1 = field2','ORDER'=>'field desc','GROUP'=>'fld')
  * @param string|array $Table
  * @param string|array $fields
  * @param string|array $conditions
  * @param MYSQLI_CONST $res_type MYSQLI_ASSOC, MYSQLI_NUM et MYSQLI_BOTH
  * @Return  array | false
  **/
  function select_to_array($tables,$fields = '*', $conds = null,$result_type = MYSQLI_ASSOC){
    //we make the table list for the Q_str
    if(! $tb_str = $this->array_to_str($tables))
      return FALSE;
    //we make the fields list for the Q_str
    if(! $fld_str =  $this->array_to_str($fields))
      $fld_str = '*';
    //now the WHERE str
    if($conds)
      $conds_str = $this->process_conds($conds);
    $Q_str = "SELECT $fld_str FROM $tb_str $conds_str";
    if ($this->debug) echo "<p>$Q_str";
    # echo "SQL : $Q_str\n;";
    return $this->query_to_array($Q_str,$result_type);
  }
  /**
  * Same as select_to_array but return only the first row.
  * equal to $res = select_to_array followed by $res = $res[0];
  * @see select_to_array for details
  * @return array of fields
  */
  function select_single_to_array($tables,$fields = '*', $conds = null,$result_type = MYSQLI_ASSOC){
    if(! $res = $this->select_to_array($tables,$fields,$conds,$result_type))
      return FALSE;
    return $res[0];
  }
  /**
  * just a quick way to do a select_to_array followed by a associative_array_from_q2a_res
  * see both thoose method for more information about parameters or return values
  */
  function select2associative_array($tables,$fields='*',$conds=null,$index_field='id',$value_fields=null,$keep_index=FALSE){
    if(! $this->select_to_array($tables,$fields,$conds))
      return FALSE;
    return $this->associative_array_from_q2a_res($index_field,$value_fields,null,$keep_index);
  }
  /**
  * select a single value in database
  * @param string $table
  * @param string $field the field name where to pick-up value
  * @param mixed conds
  * @return mixed or FALSE
  */
  function select_single_value($table,$field,$conds=null){
    if($res = $this->select_single_to_array($table,$field,$conds,MYSQLI_NUM))
      return $res[0];
    else
      return FALSE;
  }
  /**
  * return the result of a query to an array
  * @param string $Q_str SQL query
  * @return array | false if no result
  */
  function query_to_array($Q_str,$result_type=MYSQLI_ASSOC){
    unset($this->last_q2a_res);
    # echo "$Q_str\n";
    if(! $this->query($Q_str)){
      //echo "QSTR $Q_str\n";
      # echo "return FALSE\n";
      # $this->set_error();
      return FALSE;
    }
    while($res[]=@mysqli_fetch_array($this->last_qres,$result_type));
    unset($res[count($res)-1]);//unset last empty row

    $this->num_rows = @mysqli_affected_rows($this->conn);
    return $this->last_q2a_res = count($res)?$res:FALSE;
  }
  /**
  * Send an insert query to $table
  * @param string $table
  * @param array $values (arr(FLD=>VALUE,)
  * @param bool $return_id the function will return the inserted_id if $return_id is true (the default value), else it'll return only true or false.
  * @return insert id or FALSE
  **/
  function insert($table,$values,$return_id=TRUE){
    if(!is_array($values))
      return FALSE;
    foreach( $values as $k=>$v){
      $fld[]= "`$k`";
      $val[]= "'".mysqli_real_escape_string($v)."'";
    }
    $Q_str = "INSERT INTO $table (".$this->array_to_str($fld).") VALUES (".$this->array_to_str($val).")";
    if ($this->debug) echo "<p>$Q_str";
    if(! $this->query_affected_rows($Q_str)){
      //echo $Q_str;
      return FALSE;
    }
    $this->last_id = @mysqli_insert_id($this->conn);
    return $return_id?$this->last_id:TRUE;
  }
  /**
  * Send a delete query to $table
  * @param string $table
  * @param mixed $conds
  * @return int affected_rows
  **/
  function delete($table,$conds){
    //now the WHERE str
    if($conds)
      $conds_str = $this->process_conds($conds);
    $Q_str = "DELETE FROM $table $conds_str";
    # echo $Q_str;
    return $this->query_affected_rows($Q_str);
  }
  /**
  * Send an update query to $table
  * @param string $table
  * @param string|array $values (arr(FLD=>VALUE,)
  * @return int affected_rows
  **/
  function update($table,$values,$conds = null){
  	$str = null;
    if(is_array($values)){
      foreach( $values as $k=>$v)
		if (is_null($v)) { 
      		$str[]= " `$k` = null";
		} else {
      		$str[]= " `$k` = '".mysqli_escape_string($v)."'";
		}
    }elseif(! is_string($values)){
      return FALSE;
    }
    # now the WHERE str
    if($conds)
      $conds_str = $this->process_conds($conds);
      
    $Q_str = "UPDATE $table SET ".(is_array($str)?$this->array_to_str($str):$values)." $conds_str";
    
    if ($this->debug) print $Q_str;
     
    return $this->query_affected_rows($Q_str);
  }
  /**
  * optimize table statement query
  * @param string $table name of the table to optimize
  * @return bool
  */
  function optimize($table){
    return $this->query("OPTIMIZE TABLE $table");
  }
  /**
  * perform a query on the database
  * @param string $Q_str
  * @return= result id | FALSE
  **/
  function query($Q_str){
  	
  	if ($this->debug) echo "<p>$Q_str";
  	
    if(! $this->db ){
      if(! ($this->autoconnect && $this->check_conn('check')))
        return FALSE;
    }
    # echo "\n**SQL QUERY on $this->db :\n$Q_str\n";
    if(! $this->last_qres = mysqli_query($Q_str,$this->conn))
      $this->set_error();
    return $this->last_qres;
  }
  /**
  * perform a query on the database like query but return the affected_rows instead of result
  * give a most suitable answer on query such as INSERT OR DELETE
  * @param string $Q_str
  * @return int affected_rows
  */
  function query_affected_rows($Q_str){
    if(! $this->db ){
      if(! ($this->autoconnect && $this->check_conn('check')))
        return FALSE;
    }
    # echo "\n**SQL QUERY on $this->db :\n$Q_str\n";
    $this->last_qres = @mysqli_query($Q_str,$this->conn);
    $num = @mysqli_affected_rows($this->conn);

    if( $num == -1)
      $this->set_error();
    else
      return $num;
  }
  /**
  * return the list of field in $table
  * @param string $table name of the sql table to work on
  * @param bool $extended_info will return the result of a show field query in a query_to_array fashion
  */
  function get_fields($table,$extended_info=FALSE){
    if(! $res = $this->query_to_array("SHOW FIELDS FROM $table"))
      return FALSE;
    if($extended_info)
      return $res;
    foreach($res as $row){
      $res_[]=$row['Field'];
    }
    return $res_;
  }
  /**
  * get the number of row in $table
  * @param string $table table name
  * @return int
  */
  function get_count($table){
    return $this->select_single_value($table,'count(*) as c');
  }
  /**
  * return an array of databases names on server
  * @return array
  */
  function list_dbs(){
    if(! $dbs = $this->query_to_array("SHOW databases",MYSQLI_NUM))
      return FALSE;
    # showvar($dbs);
    foreach($dbs as $db){
      $dbs_[]=$db[0];
    }
    return $dbs_;
  }
  /**
  * get the table list from $this->dbname
  * @return array
  */
  function list_tables(){
    if(! $tables = $this->query_to_array('SHOW tables',MYSQLI_NUM) )
      return FALSE;
    foreach($tables as $v){
      $ret[] = $v[0];
    }
    return $ret;
  }
  /**
  * get the fields list of table
  * @param string $table
  * @param bool $indexed_by_name the return array will be indexed by the fields name if set to true (default is FALSE)
  * @return array
  * @TODO prendre en compte le second argument qui pose probleme
  */
  function list_fields($table,$indexed_by_name=FALSE){
    if(! $this->query_to_array("Show fields from $table"))
      return FALSE;
    return $this->associative_array_from_q2a_res('Field',null,null,TRUE);
  }
  function show_table_keys($table){
    return $this->query_to_array("SHOW KEYS FROM $table");
  }
  /**
  * dump the database to a file
  * @param string $out_file name of the output file
  * @param bool $droptables add 'drop table'  if set to true (defult=TRUE)
  * @param bool $gziped (default = TRUE) if set to true output will be compressed
  * @param gtkprogress &$progress is an optional progressbar to trace activity (will received a value between 0 to 100)
  */
  function dump_to_file($out_file,$droptables=TRUE,$gziped=TRUE){
    if($gziped){
      if(! $fout = gzopen($out_file,'w'))
        return FALSE;
    }else{
      if(! $fout = fopen($out_file,'w'))
        return FALSE;
    }
    $entete = "# PHP class mysqldb SQL Dump\n#\n# Host: $this->host\n# generate on: ".date("Y-m-d")."\n#\n# Db name: `$this->dbname`\n#\n#\n# --------------------------------------------------------\n\n";
    if($gziped)
      gzwrite($fout,$entete);
    else
      fwrite($fout,$entete);
    $tables = $this->list_tables();
    foreach($tables as $table){
      $table_create = $this->query_to_array("SHOW CREATE TABLE $table",MYSQLI_NUM);
      $table_create = $table_create[0]; # now we have the create statement
      $create_str = "\n\n#\n# Table Structure `$table`\n#\n\n".($droptables?"DROP TABLE IF EXISTS $table;\n":'').$table_create[1].";\n";
      if($gziped)
        gzwrite($fout,$create_str);
      else
        fwrite($fout,$create_str);
      $i=0;#initialiser au debut d'une table compteur de ligne
      if($tabledatas = $this->select_to_array($table)){ # si on a des donn�es ds la table on les mets
        if($gziped)
          gzwrite($fout,"\n# `$table` DATAS\n\n");
        else
          fwrite($fout,"\n# `$table` DATAS\n\n");
        unset($stringsfields);$z=0;
        
        foreach($tabledatas as $row){
          unset($values,$fields);
          foreach($row as $field=>$value){
            if($i==0){ # on the first line we get fields 
              $fields[] = "`$field`";
              if( @mysqli_fetch_field_direct($this->last_qres,$z++) == 'string') # will permit to correctly protect number in string fields
                $stringsfields[$field]  = TRUE;
            }
            if(preg_match("!^-?\d+(\.\d+)?$!",$value) && !$stringsfields[$field])
              $value = $value;
            elseif($value==null)
              $value =  $stringsfields[$field]?"''":"NULL";
            else
              $value = "'".mysqli_escape_string($value)."'";
            $values[] = $value;
          }
          $insert_str = ($i==0?"INSERT INTO `$table` (".implode(',',$fields).")\n       VALUES ":",\n")."(".implode(',',$values).')';
          if($gziped)
            gzwrite($fout,$insert_str);
          else
            fwrite($fout,$insert_str);
          $i++; # increment line number
        }
        if($gziped)
          gzwrite($fout,";\n\n");
        else
          fwrite($fout,";\n\n");
      }
    }
    if($gziped)
      gzclose($fout);
    else
      fclose($fout);
  }
  /**
  *return an associative array indexed by $index_field with values $value_fields from
  *a mysqldb->select_to_array result
  *@param string $index_field default value is id
  *@param mixed $value_fields (string field name or array of fields name default is null so keep all fields
  *@param array $res the mysqldb->select_to_array result
  *@param bool $keep_index if set to true then the index field will be keep in the values associated (unused if $value_fields is string)
  *@param bool $sort_keys will automaticly sort the array by key if set to true @deprecated argument
  *@return array
  */
  function associative_array_from_q2a_res($index_field='id',$value_fields=null,$res = null,$keep_index=FALSE,$sort_keys=FALSE){
    if($res===null)
      $res = $this->last_q2a_res;
      
    if(! is_array($res)){
      $this->verbose("[error] mysqldb::associative_array_from_q2a_res with invalid result\n");
      return FALSE;
    }
    # then verify index exists
    if(!isset($res[0][$index_field])){
      $this->verbose("[error] mysqldb::associative_array_from_q2a_res with invalid index field '$index_field'\n");
      return FALSE;
    }
    # then we do the trick
    if(is_string($value_fields)){
      foreach($res as $row){
          $associatives_res[$row[$index_field]] = $row[$value_fields];
      }
    }elseif(is_array($value_fields)||$value_fields===null){
      foreach($res as $row){
        $associatives_res[$row[$index_field]] = $row;
        if(!$keep_index)
          unset($associatives_res[$row[$index_field]][$index_field]);
      }
    }
    if(! count($associatives_res))
      return FALSE;
    if($sort_keys)
      ksort($associatives_res); 
    return $this->last_q2a_res = $associatives_res;
  }
  /*########## INTERNAL METHOD ##########*/
  /**
  * used by other methods to parse the conditions param of a QUERY
  * @param string|array $conds
  * @return string
  * @private
  */
  function process_conds($conds=null){
    if(is_array($conds)){
      $WHERE = ($conds[WHERE]?'WHERE '.$this->array_to_str($conds[WHERE]):'');
      $WHERE.= ($WHERE?' ':'').$this->array_to_str($conds);
      $GROUP = ($conds[GROUP]?'GROUP BY '.$this->array_to_str($conds[GROUP]):'');
      $ORDER = ($conds[ORDER]?'ORDER BY '.$this->array_to_str($conds[GROUP]):'');
      $LIMIT = ($conds[LIMIT]?'LIMIT '.$conds[LIMIT]:'');
      $conds_str = "$WHERE $ORDER $GROUP $LIMIT";
    }elseif(is_string($conds)){
      $conds_str = $conds;
    }
    return $conds_str;
  }
  /**
  * Handle mysql Error
  * @private
  */
  function set_error(){
    static $i=0;
    if(! $this->db ){
      $this->error[$i]['nb'] =$this->error['nb'] = null;
      $this->error[$i]['str'] =$this->error['str'] = '[ERROR] No Db Handler';
    }else{
      $this->error[$i]['nb'] = $this->error['nb'] = mysqli_errno($this->conn);
      $this->error[$i]['str']= $this->error['str'] = mysqli_error($this->conn);
    }
    $this->last_error = $this->error[$i];
    $this->verbose($this->error[$i]['str']);
    print_r($this->last_error['str']);
    $i++;
  }
  function array_to_str($var,$sep=','){
    if(is_string($var)){
      return $var;
    }elseif(is_array($var)){
      return implode($sep,$var);
    }else{
      return FALSE;
    }
  }

  /**
  * print a msg on STDOUT if $this->beverbose is true
  * @param string $string
  * @private
  */
  function verbose($string){
    if($this->beverbose)
      echo $string;
  }
}
?>
