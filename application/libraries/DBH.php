<?php
defined('PATH') || die("Access denied");

/**
 * Just a Test Work
 *
 * A private application development framework for PHP
 *
 * @package JTW
 * @author Salabai Dmitrii <sneakquie@gmail.com>
 */

/**
 * Database Handler Class
 *
 * Class helps to manipulate and send
 * queries to Database, using simple methods
 *
 * @package JTW
 * @subpackage Libraries
 * @author Salabai Dmitrii <sneakquie@gmail.com>
 */

class DBH
{

    /**
     * Needs for self::setFetchMode()
     * Associative fetch mode
     *
     * @var string
     */
    const FETCH_ASSOC = 'assoc';

    /**
     * Needs for self::setFetchMode()
     * Number fetch mode
     *
     * @var string
     */
    const FETCH_NUM   = 'row';

    /**
     * Needs for self::setFetchMode()
     * Both (associative and number) fetch mode
     *
     * @var string
     */
    const FETCH_ARRAY = 'array';

    /**
     * Needs for self::setFetchMode()
     * Object fetch mode
     *
     * @var string
     */
    const FETCH_OBJ   = 'object';


    /**
     * Contains 'where' conditions
     *
     * @access private
     * @var string
     */
    private $_where = '';

    /**
     * Contains 'order' conditions
     *
     * @access private
     * @var string
     */
    private $_order = '';

    /**
     * Contains 'group' conditions
     *
     * @access private
     * @var string
     */
    private $_group = '';

    /**
     * Contains offset
     *
     * @access private
     * @var integer
     */
    private $_offset = 0;

    /**
     * Contains results limit
     *
     * @access private
     * @var string
     */
    private $_limit = 0;

    /**
     * Contains joins
     *
     * @access private
     * @var string
     */
    private $_join   = '';

    /**
     * Contains havings
     *
     * @access private
     * @var string
     */
    private $_having = '';

    /**
     * Use prefix
     *
     * @access private
     * @var boolean
     */
    private $_usePrefix = '';

    /**
     * Contains prefix to Database
     *
     * @access private
     * @var string
     */
    private static $_prefix = '';

    /**
     * Display errors
     *
     * @access private
     * @var boolean
     */
    private static $_displayErrors = true;

    /**
     * Contains fetch mode (assoc|row|object|array)
     *
     * @access private
     * @var string
     */
    private static $_fetchMode = 'assoc';

    /**
     * Last query
     *
     * @access private
     * @var string
     */
    private static $_lastQuery = '';

    /**
     * Contains link to connection, created by self::connect()
     *
     * @access private
     * @var null (not connected) | resource (connected)
     */
    private static $_handler = null;

    /**
     * Contains count of queries
     *
     * @access private
     * @var integer
     */
    private static $_queryCount = 0;

    public function all()
    {
        return array(
            array('building_id' => 1, 'asd' => 'sdfsdf'),
            array('building_id' => 4, 'asd' => 'sdfsdf'),
            array('building_id' => 6, 'asd' => 'sdfsdf'),
            array('building_id' => 5, 'asd' => 'sdfsdf'),
            array('building_id' => 10, 'asd' => 'sdfsdf'),
        );
    }

    /**
     * Create new DBH object
     *
     * @param  string $table
     * @access private
     * @return void
     */
    private function __construct($table, $usePrefix)
    {
        $this->_table = $table;
        $usePrefix    = (boolean) $usePrefix;

        $this->_usePrefix = $usePrefix;
    }

    /**
     * Create connection with Database table
     *
     * @param  string  $table
     * @param  boolean $usePrefix (Use it, if you want to create connection without prefix)
     * @return object  Return DBH Object
     */
    public static function table($table, $usePrefix = true)
    {
        
        /*
         * Not connected to Database
         */
        if(!self::isConnected()) {
            self::_error('Database Error: ' . __METHOD__ . ': not connected to Database');
            return null;
        }

        $table = trim((string) $table);

        /*
         * Table is invalid
         */
        if(empty($table)) {
            self::_error('Database Error: ' . __METHOD__ . ': table not found');
            return null;
        }

        return new DBH($table, $usePrefix);
    }

    /**
     * Connect to database
     * Using user, passwd, MySQL server, prefix
     *
     * <pre>
     *   try {
     *     DBH::connect(array('user'     => 'root', 
     *                        'database' => 'test',
     *                  ));
     *   }
     *   catch(Exception $e) {
     *     die($e->getMessage());
     *   }
     * </pre>
     *
     * @access public
     * @param  array $data An array of data, need to connect
     * @return void
     */
    public static function connect($user, $database, $password = '', $server = 'localhost', $charset = 'utf8')
    {

        /*
         * Already connected to DB - close connection
         */
        if(self::isConnected()) {
            self::close();
        }

        $user     = trim((string) $user);
        $database = trim((string) $database);
        $password = (string) $password;
        $server   = (string) $server;

        if(empty($user)) {
            throw new Exception('Database Error: "Incorrect user login"');
        } elseif(empty($database)) {
            throw new Exception('Database Error: "Incorrect database"');
        }

        /*
         * Try to connect to server
         */
        $resource = mysqli_connect($server, $user, $password, $database);
        if(false === $resource) {
            throw new Exception('Database Error: (#' . mysqli_connect_errno() . ') Error: "' . mysqli_connect_error() . '"');
        }

        /*
         * Set handler, free memory
         */
        self::$_handler = $resource;
        unset($resource);

        /*
         * Set charset (if charset isn't set, default charset - utf8)
         */
        if(version_compare(mysqli_get_server_info(self::$_handler), '5.0.7', '>=')) {
            mysqli_set_charset(self::$_handler, $data['charset']);
        } else {
            self::query('SET NAMES ' . $data['charset']);
        }

        if(!empty($data['prefix'])) {
            self::$_prefix = $data['prefix'];
        }
    }

    /**
     * Add WHERE condition to query
     *
     * <pre>
     *   DBH::table('users')->where('login', '%admin%', 'LIKE')
     *                      ->get();
     * </pre>
     *
     * @param  string $subject
     * @param  string $param
     * @param  mixed $predicate
     * @return object Return DBH object
     */
    public function where($subject, $predicate, $param = '=')
    {

        /*
         * Filtrate values
         */
        $subject   = mysql_real_escape_string(trim($subject), self::$_handler);
        $param     = mysql_real_escape_string(trim($param), self::$_handler);
        $predicate = mysql_real_escape_string(trim($predicate), self::$_handler);

        /*
         * Some data is empty
         */
        if( empty($subject)
         || empty($param)
         || empty($predicate)
         || !is_scalar($predicate)
        ) {
            self::_error('Predicate is empty');
            return $this;
        }

        /*
         * Form condition
         */
        $where = $subject . ' ' . $param . ' "' . $predicate . '"';

        /*
         * Where already exists
         */
        if(  isset($this->_where)
         && !empty($this->_where)
        ) {
            $this->_where .= ' AND ' . $where;
        }
        else {
            $this->_where  = ' WHERE ' . $where;
        }

        return $this;
    }

    /**
     * Add WHERE IN condition to query
     *
     * <pre>
     *   DBH::table('users')->whereIn('id', array(1, 2, 3))
     *                      ->get();
     * </pre>
     *
     * @param  string        $subject
     * @param  array|string  $predicate  Can be an array or string
     * @return object                    Return DBH object      
     */
    public function whereIn($subject, $predicate)
    {

        /*
         * Filtrate values
         */
        $subject   = mysql_real_escape_string(trim($subject), self::$_handler);
        $predicate = self::_createIn($predicate);

        /*
         * Some data is empty
         */
        if( empty($subject)
         || empty($predicate)
        ) {
            self::_error('Predicate is empty');
            return $this;
        }

        /*
         * Form condition
         */
        $where = $subject . ' IN (' . $predicate . ')';

        /*
         * Where already exists
         */
        if(  isset($this->_where)
         && !empty($this->_where)
        ) {
            $this->_where .= ' AND ' . $where;
        }
        else {
            $this->_where  = ' WHERE ' . $where;
        }

        return $this;
    }

    /**
     * Add WHERE NOT IN condition to query
     *
     * <pre>
     *   DBH::table('users')->whereNotIn('id', array(1, 2, 3))
     *                      ->get();
     * </pre>
     *
     * @param  string        $subject
     * @param  array|string  $predicate  Can be an array or string
     * @return object                    Return DBH object      
     */
    public function whereNotIn($subject, $predicate)
    {

        /*
         * Filtrate values
         */
        $subject   = mysql_real_escape_string(trim($subject), self::$_handler);
        $predicate = self::_createIn($predicate);

        /*
         * Some data is empty
         */
        if( empty($subject)
         || empty($predicate)
        ) {
            self::_error('Predicate is empty');
            return $this;
        }

        /*
         * Form condition
         */
        $where = $subject . ' NOT IN (' . $predicate . ')';

        /*
         * Where already exists
         */
        if(  isset($this->_where)
         && !empty($this->_where)
        ) {
            $this->_where .= ' AND ' . $where;
        }
        else {
            $this->_where  = ' WHERE ' . $where;
        }

        return $this;
    }

    /**
     * Add WHERE condition to another condition
     *
     * <pre>
     *   DBH::table('users')->where('login', 'LIKE', '%admin%')
     *                      ->orWhere('id', '=', '1')
     *                      ->get();
     * </pre>
     *
     * @param  string $subject
     * @param  string $param
     * @param  mixed $precicate
     * @return object Return DBH object 
     */
    public function orWhere($subject, $predicate, $param = '=')
    {

        /*
         * Where condition isn't already set
         */
        if(!isset($this->_where)
         || empty($this->_where)
        ) {
            return $this;
        }

        /*
         * Filtrate values
         */
        $subject   = mysql_real_escape_string(trim($subject), self::$_handler);
        $param     = mysql_real_escape_string(trim($param), self::$_handler);
        $predicate = mysql_real_escape_string(trim($predicate), self::$_handler);

        /*
         * Some data is empty
         */
        if( empty($subject)
         || empty($param)
         || empty($predicate)
         || !is_scalar($predicate)
        ) {
            self::_error('Predicate is empty');
            return $this;
        }

        /*
         * Form condition
         */
        $this->_where .= ' OR ' . $subject . ' ' . $param . ' "' . $predicate . '"';

        return $this;
    }

    /**
     * Add OR WHERE IN condition to query
     *
     * <pre>
     *   DBH::table('users')->where('login', 'user')
     *                      ->orWhereIn('id', array(1, 2, 3))
     *                      ->get();
     * </pre>
     *
     * @param  string $subject
     * @param  mixed  $predicate  Can be an array or string '1, 2, 3'
     * @return object             Return DBH object     
     */
    public function orWhereIn($subject, $predicate)
    {

        /*
         * Where condition isn't already set
         */
        if(!isset($this->_where)
         || empty($this->_where)
        ) {
            return $this;
        }

        /*
         * Filtrate values
         */
        $subject   = mysql_real_escape_string(trim($subject), self::$_handler);
        $predicate = self::_createIn($predicate);

        /*
         * Some data is empty
         */
        if( empty($subject)
         || empty($predicate)
        ) {
            self::_error('Predicate is empty');
            return $this;
        }

        /*
         * Form condition
         */
        $this->_where .= ' OR ' . $subject . ' IN (' . $predicate . ')';

        return $this;
    }

    /**
     * Add having condition to query
     *
     * @param  string $column
     * @param  string $value
     * @param  string $param
     * @return object Return DBH Object
     */
    public function having($column, $value, $param)
    {

        /*
         * Filtrate data
         */
        $column = mysql_real_escape_string(trim($column), self::$_handler);
        $value  = mysql_real_escape_string(trim($value), self::$_handler);
        $param  = mysql_real_escape_string(trim($param), self::$_handler);

        /*
         * HAVING data is empty
         */
        if( empty($column)
         || empty($value)
         || empty($param)
        ) {
            self::_error('Database Error: ' . __METHOD__ . ': HAVING data empty');
            return $this;
        }

        $this->_having = ' HAVING ' . $column . ' ' . $param . ' ' . $value;
        return $this;
    }

    /**
     * Add join to SQL query
     *
     * <pre>
     *   DBH::table('users')->join('posts', '`users`.id', '`posts`.author_id')
     *                      ->get();
     * </pre>
     *
     * @param  string $table
     * @param  string $first
     * @param  string $second
     * @param  string $type  (INNER|LEFT|RIGHT)
     * @return object Return DBH Object
     */
    public function join($table, $first, $second, $type = 'INNER')
    {

        /*
         * Filtrate data
         */
        $table  = mysql_real_escape_string(trim($table), self::$_handler);
        $first  = mysql_real_escape_string(trim($first), self::$_handler);
        $second = mysql_real_escape_string(trim($second), self::$_handler);
        $type   = trim($type);

        /*
         * Some join value is empty
         */
        if( empty($table)
         || empty($first)
         || empty($second)
        ) {
            self::_error('Database Error: ' . __METHOD__ . ': JOIN data empty');
            return $this;
        }

        /*
         * Type is invalid
         */
        elseif(empty($type)
           || !in_array(strtoupper($type), array('INNER', 'LEFT', 'RIGHT'))
        ) {
            $type = 'INNER';
        }

        $this->_join .= ' ' . $type . ' JOIN ' . $table . ' ON ' . $first . ' = ' . $second;
        return $this;
    }

    /**
     * Add limit to SQL query
     *
     * <pre>
     *   DBH::table('users')->take(10)
     *                      ->get();
     * </pre>
     *
     * @param  integer $count
     * @return object  Return DBH object
     */
    public function take($count)
    {
        $count = intval($count);

        /*
         * Incorrect count
         */
        if($count <= 0) {
            self::_error('Database Error: ' . __METHOD__ . ': count is invalid');
            return $this;
        }
        $this->_limit = $count;
    }

    /**
     * Add offset to SQL query
     *
     * <pre>
     *   DBH::table('users')->take(10)
     *                      ->offset(10)
     *                      ->get();
     * </pre>
     *
     * @param  integer $offset
     * @return object  Return DBH object
     */
    public function offset($offset)
    {
        $offset = intval($offset);

        /*
         * Incorrect offset
         */
        if($offset < 0) {
            self::_error('Database Error: ' . __METHOD__ . ': offset is invalid');
            return $this;
        }
        $this->_offset = $offset;
    }

    /**
     * Set order on SQL query
     *
     * <pre>
     *   DBH::table('comments')->orderBy('date')
     *                         ->get();
     * </pre>
     *
     * @param  string $by
     * @param  string $value (ASC|DESC)
     * @return object Return DBH Object
     */
    public function orderBy($by, $value = 'ASC')
    {
        $by    = trim($by);
        $value = trim($value);

        /*
         * `by` is empty
         */
        if(empty($by)) {
            self::_error('Database Error: ' . __METHOD__ . ': `by` param is empty');
            return $this;
        }

        /*
         * Value is invalid
         */
        elseif(empty($value)
           || !in_array(strtoupper($value), array('ASC', 'DESC'))
        ) {
            $value = 'ASC';
        }

        /*
         * Already sets
         */
        if(!empty($this->_order)) {
            $this->_order .= ', ' . $by . ' ' . $value;
        }
        else {
            $this->_order = ' ORDER BY ' . $by . ' ' . $value;
        }
        return $this;
    }

    /**
     * Add group by in SQL query
     *
     * <pre>
     *   DBH::table('users')->groupBy('name')
     *                      ->get(array('MAX(score)', 'MIN(score)'));
     * </pre>
     *
     * @param  string $column
     * @return object Return DBH Object
     */
    public function groupBy($column)
    {
        $column = trim($column);

        /*
         * Column is empty
         */
        if(empty($column)) {
            self::_error('Database Error: ' . __METHOD__ . ': `column` param is empty');
            return $this;
        }
        elseif(!empty($this->_group)) {
            $this->_group .= ', ' . $column;
        }
        else {
            $this->_group = ' GROUP BY ' . $column;
        }
        return $this;
    }

    /**
     * Execute select query
     *
     * <pre>
     *   DBH::table('users')->get();
     * </pre>
     *
     * @param  array|string  $values
     * @param  array|boolean $modes  (SQL_CALC_FOUND_ROWS etc)
     * @return resource
     */
    public function get($values = '*', $modes = false)
    {
        if( is_array($values)
         && sizeof($values) > 0
        ) {

            /*
             * Filtrate each value if is scalar
             */
            foreach($values as $key => $value) {
                if(!is_scalar($value)) {
                    unset($values[$key]);
                }
                else {
                    $values[$key] = mysql_real_escape_string(trim($value), self::$_handler);
                }
            }

            $values = implode(',', $values);
        }

        /*
         * Values is string
         */
        elseif(is_string($values)) {
            if(empty($values)) {
                $values = '*';
            }
            else {
                $values = mysql_real_escape_string(trim($values), self::$_handler); 
            }
        }
        else {
            self::_error('Database Error: ' . __METHOD__ . ': unknown type of values');
            $values = '*';
        }

        /*
         * SQL Modes are set
         */
        if( is_array($modes)
         && sizeof($modes) > 0
        ) {

            /*
             * Create string from modes
             */
            foreach ($modes as $key => $value) {
                if(!is_scalar($value)) {
                    unset($modes[$key]);
                }
                else {
                    $modes[$key] = mysql_real_escape_string(trim($value), self::$_handler);
                }
            }

            $modes = implode(' ', $modes);
        }

        /*
         * Forming query
         */
        $query = 'SELECT ' . $modes . ' ' . $values . ' FROM ';

        if($this->_usePrefix == true) {
            $query .= self::$_prefix;
        }

        $query .= $this->_table . $this->_join . $this->_where . $this->_group . $this->_having . $this->_order;

        /*
         * Limit, offset
         */
        if($this->_limit > 0) {
            $query .= ' LIMIT ';

            if($this->_offset >= 0) {
                $query .= $this->_offset . ', ';
            }

            $query .= $this->_limit;
        }

        return self::query($query);
    }

    /**
     * Execute select query with limit = 1
     *
     * <pre>
     *   DBH::table('users')->orderBy('register_date')
     *                      ->getOne();
     * </pre>
     *
     * @param  array|string $values
     * @return resource
     */
    public function getOne($values = '*')
    {
        $this->_limit = 1;
        return $this->get($values);
    }

    /**
     * Execute update query
     *
     * <pre>
     *   DBH::table('users')->set(array('login' => 'noname'));
     * </pre>
     *
     * @param  array $values
     * @return resource
     */
    public function set($values)
    {

        /*
         * Array values isn't valid
         */
        if(!is_array($values)
         || sizeof($values) == 0
        ) {
            self::_error('Database Error: ' . __METHOD__ . ': data array is empty');
            return false;
        }

        $set = '';

        /*
         * Set columns and values
         */
        foreach($values as $key => $value) {
            if(!is_scalar($value)) {
                continue;
            }
            $set .= mysql_real_escape_string(trim($key), self::$_handler) . ' = "' . mysql_real_escape_string(trim($value), self::$_handler) . '",';
        }

        $set   = rtrim($set, ',');

        /*
         * Start forming query
         */
        $query = 'UPDATE ';

        /*
         * Using prefix
         */
        if($this->_usePrefix == true) {
            $query .= self::$_prefix;
        }

        $query .= $this->_table . ' SET ' . $set . $this->_where . $this->_order;

        /*
         * Using limit
         */
        if($this->_limit > 0) {
            $query .= ' LIMIT ' . $this->_limit;
        }

        return self::query($query);
    }

    /**
     * Execute insert query
     *
     * <pre>
     *   DBH::table('users')->insert(array('name' => 'Dmitrii', 'login' => 'sneakquie'));
     * </pre>
     *
     * @param  array $data
     * @return resource
     */
    public function insert($data)
    {

        /*
         * Values array isn't valid
         */
        if(!is_array($data)
         || sizeof($data) == 0
        ) {
            self::_error('Database Error: ' . __METHOD__ . ': data array is empty');
            return false;
        }

        $keys = $values = '(';

        /*
         * Set columns and values
         */
        foreach($data as $key => $value) {
            if(!is_scalar($value)) {
                continue;
            }
            $keys   .= mysql_real_escape_string(trim($key), self::$_handler) . ',';
            $values .= '"' . mysql_real_escape_string(trim($value), self::$_handler) . '",';
        }

        /*
         * Delete last ','
         */
        $keys   = rtrim($keys, ',');
        $values = rtrim($values, ',');

        $keys   .= ')';
        $values .= ')';

        /*
         * Things with prefix
         */
        $query = 'INSERT INTO ';
        if($this->_usePrefix == true) {
            $query .= self::$_prefix;
        }
        $query .= $this->_table . ' ' . $keys . ' VALUES ' . $values;

        return self::query($query);
    }

    /**
     * Execute delete query
     *
     * <pre>
     *   DBH::table('users')->where('id', 1)
     *                      ->delete();
     * </pre>
     *
     * @return resource
     */
    public function delete()
    {

        /*
         * Prepare query
         */
        $query = 'DELETE FROM ';

        /*
         * Things with prefix
         */
        if($this->_usePrefix == true) {
            $query .= self::$_prefix;
        }
        $query .= $this->_table . $this->_where . $this->_order;

        /*
         * Limit exists
         */
        if($this->_limit > 0) {
            $query .= ' LIMIT ' . $this->_limit;
        }

        return self::query($query);
    }

    /**
     * Truncates table
     *
     * <pre>
     *   DBH::table('users')->truncate();
     * </pre>
     *
     * @return boolean
     */
    public function truncate()
    {
        
        /*
         * Using prefix
         */
        if($this->_usePrefix == true) {
            return self::query('TRUNCATE TABLE ' . self::$_prefix . $this->_table);
        }
        return self::query('TRUNCATE TABLE ' . $this->_table);
    }

    /**
     * Deletes table
     *
     * <pre>
     *   DBH::table('users')->drop();
     * </pre>
     *
     * @return boolean
     */
    public function drop()
    {
        
        /*
         * Using prefix
         */
        if($this->_usePrefix == true) {
            return self::query('DROP TABLE ' . self::$_prefix . $this->_table);
        }
        return self::query('DROP TABLE ' . $this->_table);
    }

    /**
     * Return number of rows
     *
     * <pre>
     *   $num_rows = DBH::numRows($result);
     * </pre>
     *
     * @param  resource $query
     * @return integer
     */
    public static function numRows($query)
    {

        /*
         * Param type isn't resource
         */
        if(!self::isMysqlResult($query)) {
            self::_error('Database Error: ' . __METHOD__ . ': argument isn\'t resource');
            return 0;
        }
        return mysql_num_rows($query);
    }

    /**
     * Return number of affected rows
     *
     * <pre>
     *   $affected_rows = DBH::affectedRows();
     * </pre>
     *
     * @return integer
     */
    public static function affectedRows()
    {

        /*
         * Param type isn't resource
         */
        if(!self::isConnected()) {
            self::_error('Database Error: ' . __METHOD__ . ': not conneted to Database');
            return 0;
        }
        return mysql_affected_rows(self::$_handler);
    }

    /**
     * Sends query to MySQL server
     *
     * <pre>
     *   DBH::query('SET NAMES utf8');
     * </pre>
     *
     * @param  string $query
     * @return boolean (on NON SELECT queries, or on error) | resource
     */
    public static function query($query)
    {

        /*
         * Not connected to Database
         */
        if(!self::isConnected()) {
            self::_error('Database Error: ' . __METHOD__ . ': not conneted to Database');
            return false;
        }

        $query = trim($query);

        if(empty($query)) {
            self::_error('Database Error: ' . __METHOD__ . ': query is empty');
            return false;
        }

        /*
         * Execute query
         */
        $result = @mysql_query($query, self::$_handler);

        if($result === false) {
            self::_error(mysql_error(self::$_handler), $query);
        }

        self::$_queryCount++;
        self::$_lastQuery = $query;

        return $result;
    }

    /**
     * Set fetching mode
     *
     * <pre>
     *   DBH::setFetchMode(DBH::FETCH_ASSOC);
     * </pre>
     *
     * @param  string $mode
     * @return boolean
     */
    public static function setFetchMode($mode)
    {
        $mode = trim(strtolower($mode));

        /*
         * Mode is empty or invalid
         */
        if( empty($mode)
         || !in_array($mode, array('assoc', 'row', 'array', 'object'))
        ) {
            self::_error('Database Error: ' . __METHOD__ . ': fetch mode isn\'t available');
            return false;
        }
        self::$_fetchMode = $mode;
        return true;
    }

    /**
     * Return an array of fetched data
     *
     * <pre>
     *   $result = DBH::fetch($query, DBH::FETCH_ASSOC);
     * </pre>
     *
     * @param  resource $query
     * @param  string   $mode
     * @return array
     */
    public static function fetch($query, $mode = null)
    {

        /*
         * Param isn't resource
         */
        if(!self::isMysqlResult($query)) {
            self::_error('Database Error: ' . __METHOD__ . ': argument isn\'t resource');
            return null;
        }

        /*
         * Num of rows = 0
         */
        elseif(mysql_num_rows($query) == 0) {
            return array();
        }

        /*
         * Fetch mode is valid
         */
        elseif(!is_null($mode)) {
            $mode = trim(strtolower($mode));

            if(  empty($mode)
             || !in_array($mode, array('assoc', 'row', 'array', 'object'))
            ) {
                $mode = self::$_fetchMode;
            }
        }
        else {
            $mode = self::$_fetchMode;
        }

        /*
         * Fetch mode
         */
        $fetch = 'mysql_fetch_' . $mode;

        return $fetch($query);
    }

    /**
     * Return an array containing all of
     * the result set rows
     *
     * <pre>
     *   $comments = DBH::fetchAll($query, DBH::FETCH_NUM);
     * </pre>
     *
     * @param  resource $query
     * @param  string   $mode
     * @return array
     */
    public static function fetchAll($query, $mode = null)
    {

        /*
         * Param isn't resource
         */
        if(!self::isMysqlResult($query)) {
            self::_error('Database Error: ' . __METHOD__ . ': argument isn\'t resource');
            return null;
        }

        /*
         * Num of rows = 0
         */
        elseif(mysql_num_rows($query) == 0) {
            return array();
        }

        /*
         * Fetch mode is valid
         */
        elseif(!is_null($mode)) {
            $mode = trim(strtolower($mode));

            if(  empty($mode)
             || !in_array($mode, array('assoc', 'row', 'array', 'object'))
            ) {
                $mode = self::$_fetchMode;
            }
        }
        else {
            $mode = self::$_fetchMode;
        }

        /*
         * Fetch mode
         */
        $fetch = 'mysql_fetch_' . $mode;

        /*
         * Set an array of all fetched data
         */
        while($fetchAll[] = $fetch($query)) {

        }

        /*
         * Delete last empty array
         */
        array_pop($fetchAll);

        return $fetchAll;
    }

    /**
     * Set self::$_displayErrors
     *
     * <pre>
     *   DBH::setDisplayErrors(false);
     * </pre>
     *
     * @param  boolean $param
     * @return void
     */
    public static function setDisplayErrors($param)
    {
        self::$_displayErrors = (boolean)$param;
    }

    /**
     * Create error message
     *
     * @access private
     * @param  string $query
     * @param  string $error
     * @return void
     */
    private static function _error($error, $query = null)
    {
        // JTW_Logger::write($error, // JTW_Logger::ERROR);

        if(self::$_displayErrors == true) {
            ob_clean();
            echo $error;
        }
    }

    /**
     * Return escaped string, using link to Database
     *
     * <pre>
     *   $string = DBH::quote($string);
     * </pre>
     *
     * @param  string $string
     * @return string Return escaped string
     */
    public static function quote($string)
    {

        /*
         * Connected to server
         */
        if(self::isConnected()) {
            return mysql_real_escape_string($string, self::$_handler);
        }
        return mysql_real_escape_string($string);
    }

    /**
     * Return last inserted id
     *
     * <pre>
     *   DBH::table('users')->insert(array('login' => 'admin'));
     *   $id = DBH::lastInsertId();
     * </pre>
     *
     * @return integer
     */
    public static function lastInsertId()
    {

        /*
         * Connected to Database
         */
        if(self::isConnected()) {
            return mysql_insert_id(self::$_handler);
        }
        return mysql_insert_id();
    }

    /**
     * Close connection with MySQL server
     *
     * <pre>
     *   DBH::connect(array('user' => 'root', 'database' => 'test'));
     *   DBH::close();
     * </pre>
     *
     * @return boolean
     */
    public static function close()
    {

        /*
         * Connected to Database
         */
        if(self::isConnected()) {
            mysql_close(self::$_handler);
            self::$_handler = null;
            return true;
        }

        return @mysql_close();
    }

    /**
     * Free results of query
     *
     * <pre>
     *   DBH::free($result);
     * </pre>
     *
     * @param  resource $query
     * @return boolean
     */
    public static function free($query)
    {

        /*
         * Argument isn't resource
         */
        if(!self::isMysqlResult($query)) {
            self::_error('Database Error: ' . __METHOD__ . ': argument isn\'t resource');
            return false;
        }
        return @mysql_free_result($query);
    }

    /**
     * Create correct IN() condition from array
     *
     * @param  string|array
     * @return string
     */
    private static function _createIn($predicate)
    {

        /*
         * Predicate is array
         */
        if( is_array($predicate)
         && sizeof($predicate) > 0
        ) {

            /*
             * Filtrate each value if is scalar
             */
            foreach($predicate as $key => $value) {
                if(!is_scalar($value)) {
                    unset($predicate[$key]);
                    continue;
                }
                $predicate[$key] = "'" . mysql_real_escape_string(trim($value), self::$_handler) . "'";
            }

            $predicate = implode(',', $predicate);
        }

        /*
         * Predicate is string
         */
        elseif(is_string($predicate)) {
            $predicate = trim($predicate);
        }
        else {
            self::_error('Database Error: ' . __METHOD__ . ': unknown type of predicate');
            $predicate = '';
        }
        return $predicate;
    }

    /**
     * Return last query
     *
     * <pre>
     *   DBH::lastQuery();
     * </pre>
     *
     * @return string
     */
    public static function lastQuery()
    {
        return self::$_lastQuery;
    }

    /**
     * Return number of queries
     *
     * <pre>
     *   DBH::queryCount();
     * </pre>
     *
     * @return integer
     */
    public static function queryCount()
    {
        return self::$_queryCount;
    }

    /**
     * Check MySQL connection
     *
     * <pre>
     *   DBH::isConnected();
     * </pre>
     *
     * @return boolean
     */
    public static function isConnected()
    {
        return is_resource(self::$_handler);
    }

    /**
     * Check, is mysql resource or not
     *
     * <pre>
     *   DBH::isMysqlResult($query);
     * </pre>
     *
     * @param  resource $query
     * @return boolean
     */
    public static function isMysqlResult($query)
    {
        return (is_resource($query) && get_resource_type($query) == 'mysql result');
    }
}