<?php
/**
* @author Jonathan Gotti <jgotti at jgotti dot org>
* @copyleft (l) 2003-2008  Jonathan Gotti
* @package class-db
* @file
* @license http://opensource.org/licenses/lgpl-license.php GNU Lesser General Public License
* @since 2004-11-26 first splitted version
* @svnInfos:
*            - $LastChangedDate: 2008-10-28 00:12:44 +0100 (mar 28 oct 2008) $
*            - $LastChangedRevision$
*            - $LastChangedBy$
*            - $HeadURL: http://trac.jgotti.net/svn/class-db/trunk/class-mysqldb.php $
* @changelog
*            - 2011-08-05 - make usage of protect_field_names() in optimize(), list_table_fields(), show_table_keys()
*            - 2010-07-07 - introduce freeRestults method
*            - 2010-02-22 - now error on select_db at open/check_connection('active') time will return false
*            - 2008-05-12 - add parameter $setNames to select_db() that will default to new static property
*                           $setNamesOnSelectDb if both are null then nothing will happen else it will perform
*                           a SET NAMES query on the selected database.
*            - 2008-04-06 - autoconnect is now a static property
*            - 2008-03-20 - new static parameter (bool) $useNewLink used as mysql_connect new_link parameter
*                           (really usefull when working on different databases on the same host.
*                           you'd better set this to false as default if you don't need that feature.)
*            - 2007-11-20 - changing call to vebose() method according to changed made in class-db
*            - 2007-03-28 - move last_q2a_res assignment from fetch_res() method to query_to_array() (seems more logical to me)
*            - 2007-01-12 - now dump_to_file() use method escape_string instead of mysql_escape_string
*            - 2005-02-28 - add method optimize
*            - 2004-12-03 - now the associative_array_from_q2a_res method won't automaticly ksort the results
*            - 2004-12-02 - use the show fields query in place of a select statement and add extended_info mode to the get_fields method
* @todo revoir la methode check_conn() et open et close de facon a ce que check_conn aille dans base_db
*/

/**
* exented db class to use with mysql databases.
* @class mysqldb
* @example sample-mysqldb.php
*/
class mysqlidb extends db{

        /**
        * used to perform a query "SET NAMES '$dfltEncoding'" when select a database on the server
        * leave null if you don't want this to be done
        */
        static public $setNamesOnSelectDb='utf8';

        function __construct($dbname,$dbhost='localhost',$dbuser='root',$dbpass=''){ # most common config ?
                $this->host   = $dbhost;
                $this->user   = $dbuser;
                $this->pass   = $dbpass;
                $this->dbname = $dbname;
                if(db::$autoconnect)
                        $this->open();
        }

        /** open connection to database */
        function open(){  # only for convenience and because backport of sqlitedb
                return $this->check_conn('active');
        }

  /** close connection to previously opened database */
        function close(){
                return $this->check_conn('kill');
        }

        /**
        * Select the database to work on (it's the same as the use db command or mysql_select_db function)
        * @param string $dbname
        * @param string $setNames permit to enforce encoding connection to the given character set
        *                         if null will default to self::$setNamesOnSelectDb.
        *                         if both are null then no SET NAMES will be performed
        * @note Parameter setNames is only called like this to ease migration of mysqldb class users in fact
        *       in this version it use the mysqli_set_charset instead of a "SET NAMES" query as in mysqldb
        *       and as recommanded in the php manual
        * @return bool
        */
        function select_db($dbname=null,$setNames=null){
                if(! ($dbname || $this->dbname) )
                        return FALSE;
                if($dbname)
                        $this->dbname = $dbname;
                if(! $this->db = mysqli_select_db($this->conn,$this->dbname)){
                        $this->verbose("can't connect to database ".$this->dbname,__FUNCTION__,1);
                        $this->set_error(__FUNCTION__);
                        return FALSE;
                }else{
                        if( null=== $setNames && null !== mysqlidb::$setNamesOnSelectDb )
                                $setNames = mysqlidb::$setNamesOnSelectDb;
                        if( null!== $setNames)
                                mysqli_set_charset($this->conn,$setNames);
                        return $this->db;
                }
        }

        /**
        * check and activate db connection
        * @param string $action (active, kill, check) active by default
        * @return string or bool
        */
        function check_conn($action = ''){
                $host = false;
                if((! $this->conn) || ! $host = mysqli_get_host_info($this->conn)){
                        switch ($action){
                                case 'kill':
                                        return $host;
                                        break;
                                case 'check':
                                        return $host;
                                        break;
                                default:
                                case 'active':
                                        preg_match('!^([^:]+)(:(\d+))?$!',$this->host,$m);
                                        if(! $this->conn = mysqli_connect($m[1],$this->user,$this->pass,null,empty($m[3])?null:$m[3])){
                                                $this->verbose("connection to $this->host failed",__FUNCTION__,1);
                                                return FALSE;
                                        }
                                        $this->verbose("connection to $this->host established",__FUNCTION__,2);
                                        if( false === $this->select_db()){
                                                $this->close();
                                                return false;
                                        }
                                        return mysqli_get_host_info($this->conn);
                                        break;
                        }
                }else{
                        switch($action){
                                case 'kill':
                                        mysqli_close($this->conn);
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
        * take a resource result set and return an array of type 'ASSOC','NUM','BOTH'
        * @see sqlitedb or mysqldb implementation for exemple
        * @return array
        */
        function fetch_res($result_set,$result_type='ASSOC'){
                $result_type = strtoupper($result_type);
                if(! in_array($result_type,array('NUM','ASSOC','BOTH')) )
                        $result_type = 'ASSOC';
                $result_type = constant('MYSQLI_'.strtoupper($result_type));
                while($res[]=mysqli_fetch_array($result_set,$result_type));
                unset($res[count($res)-1]);//unset last empty row
                $this->num_rows = mysqli_affected_rows($this->conn);
                return count($res)?$res:FALSE;
        }

        /**
        *return the last inserted id if insert is made on a table with autoincrement field
        *@return mixed (certainly int)
        */
        function last_insert_id(){
                return $this->conn?mysqli_insert_id($this->conn):FALSE;
        }

        /**
        * there's a base method you should replace in the extended class, to use the appropriate escape func regarding the database implementation
        * @param string $quotestyle (both/single/double) which type of quote to escape
        * @return str
        */
        function escape_string($string,$quotestyle='both'){
                $string = mysqli_real_escape_string($this->conn,$string);
                switch(strtolower($quotestyle)){
                        case 'double':
                        case 'd':
                        case '"':
                                $string = str_replace("\'","'",$string);
                        case 'single':
                        case 's':
                        case "'":
                                $string = str_replace("\"",'"',$string);
                                break;
                        case 'both':
                        case 'b':
                        case '"\'':
                        case '\'"':
                                break;
                }
                return $string;
        }

        /**
        * perform a query on the database
        * @param string $Q_str
        * @return result id | FALSE
        **/
        function query($Q_str){
                if(! $this->db ){
                        if(! (db::$autoconnect && $this->check_conn('active')))
                                return FALSE;
                }
                $this->verbose($Q_str,__FUNCTION__,2);
                if(! $this->last_qres = mysqli_query($this->conn,$Q_str))
                        $this->set_error(__FUNCTION__);
                return $this->last_qres;
        }
        /**
        * perform a query on the database like query but return the affected_rows instead of result
        * give a most suitable answer on query such as INSERT OR DELETE
        * @param string $Q_str
        * @return int affected_rows or FALSE on error!
        */
        function query_affected_rows($Q_str){
                if(! $this->query($Q_str) )
                        return FALSE;
                $num = mysqli_affected_rows($this->conn);
                if( $num == -1){
                        $this->set_error(__FUNCTION__);
                        return FALSE;
                }else{
                        return $num;
                }
        }
        public function freeResults(){
                if( is_resource($this->last_qres)){
                        mysqli_free_result($this->last_qres);
                }
                $this->last_qres = null;
                parent::freeResults();
                return $this;
        }
        /**
        * get the table list from $this->dbname
        * @return array
        */
        function list_tables(){
                if(! $tables = $this->query_to_array('SHOW tables','NUM') )
                        return FALSE;
                foreach($tables as $v){
                        $ret[] = $v[0];
                }
                return $ret;
        }
        /*
        * return the list of field in $table
        * @param string $table name of the sql table to work on
        * @param bool $extended_info if true will return the result of a show field query in a query_to_array fashion
        *                           (indexed by fieldname instead of int if false)
        * @return array
        */
        function list_table_fields($table,$extended_info=FALSE){
                if(! $res = $this->query_to_array("SHOW FIELDS FROM ".$this->protect_field_names($table)))
                        return FALSE;
                if($extended_info)
                        return $res;
                foreach($res as $row){
                        $res_[]=$row['Field'];
                }
                return $res_;
        }

        /** Verifier si cette methode peut s'appliquer a SQLite */
        function show_table_keys($table){
                return $this->query_to_array("SHOW KEYS FROM ".$this->protect_field_names($table));
        }
        /**
        * optimize table statement query
        * @param string $table name of the table to optimize
        * @return bool
        */
        function optimize($table){
                return $this->query("OPTIMIZE TABLE ".$this->protect_field_names($table));
        }
        function error_no(){
                return $this->conn?mysqli_errno($this->conn):FALSE;
        }

        /**
        * @param void $errno only there for compatibility with other db implementation so totally unused there
        */
        function error_str($errno=null){
                return mysqli_error($this->conn);
        }
        /**
        * return an array of databases names on server
        * @return array
        */
        function list_dbs(){
                if(! $dbs = $this->query_to_array("SHOW databases",'NUM'))
                        return FALSE;
                foreach($dbs as $db){
                        $dbs_[]=$db[0];
                }
                return $dbs_;
        }

        function __destruct(){
                parent::__destruct();
        }
}