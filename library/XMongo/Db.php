<?php
/**
 * MongoDB操作类
 * @author lwx
 *
 */
require_once 'XMongo.php';
require_once 'XMongo/Utility.php';
require_once 'XMongo/Exception.php';
class XMongo_Db {
    /**
     * @var null|XMongo_Db
     */
    protected static $_instance = null;

	private $connection;
	private $db;

	private $server;
	private $dbname = 'test';
	/** 安全写操作（insert update delete） */
	private $safe = true;

	private $selects = array();
	public  $wheres = array(); // Public to make debugging easier
	private $sorts = array();
	public $updates = array(); // Public to make debugging easier

	private $limit = 999999;
	private $offset = 0;
	
	const DEAFAULT_ID = '_id';
	
	/** 地球平均半径（单位： km） = (赤道半径6378.140×2+极半径6356.755)×(1/3) */
    const EARTH_RADIUS = 6371;
    
    /**
     * 以单例模式获取一个XMongo_Db实例
     * @param array $config
     * @return XMongo_Db
     */
    static public function getInstance($config = null)
    {
        if (null === self::$_instance) {
            self::$_instance = new self($config);
        }
    
        return self::$_instance;
    }

	/**
	 * 初始化创建连接，并在之前做PHP Mongo 扩展是否可用的检查
	 * @param string|array|null $config
	 * @throws XMongo_Exception
	 * @return XMongo_Db
	 */

	public function __construct($config = null)
	{
		if ( ! class_exists('Mongo'))
		{
			throw new XMongo_Exception("The MongoDB PECL extension has not been installed or enabled");
		}
		$server = null;
		$options = array();
		
		switch (gettype($config)) {
		    case 'string'://exp: mongodb://localhost:27017/test
		        $server  = $config;
		        $params = parse_url($server);
		        if (isset($params['path']) && $params['path'] != '/') {
		            $this->dbname = substr($params['path'], 1);
		        }
    		    break;
		    
		    case 'array':
		        $server  = $config['server'];
		        $options = $config['options'];
		        $this->dbname = $config['dbname'];
		        $this->safe = ($config['options']['safe']);
		        break;
		    
		    default://NULL and other type
		        $server = "mongodb://localhost:27017";
		        $options = array();
		}
		
		//Connect to mongodb server
	    try
		{
			$this->connection = new Mongo($server, $options);
			$this->db = $this->connection->{$this->dbname};
			return ($this);	
		}catch (MongoConnectionException $e){
			throw new XMongo_Exception("Unable to connect to MongoDB: {$e->getMessage()}");
		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	//! Switch database
	*	--------------------------------------------------------------------------------
	*
	*	Switch from default database to a different db
	*
	*	$this->mongo_db->switch_db('foobar');
	*/

	public function switch_db($database = '')
	{
		if (empty($database))
		{
			throw new XMongo_Exception("To switch MongoDB databases, a new database name must be specified");
		}

		$this->dbname = $database;

		try
		{
			$this->db = $this->connection->{$this->dbname};
			return (TRUE);
		}
		catch (Exception $e)
		{
			throw new XMongo_Exception("Unable to switch Mongo Databases: {$e->getMessage()}");
		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	//! Drop database
	*	--------------------------------------------------------------------------------
	*
	*	Drop a Mongo database
	*	@usage: $this->mongo_db->drop_db("foobar");
	*/
	public function drop_db($database = '')
	{
		if (empty($database))
		{
			throw new XMongo_Exception('Failed to drop MongoDB database because name is empty');
		}

		else
		{
			try
			{
				$this->connection->{$database}->drop();
				return (TRUE);
			}
			catch (Exception $e)
			{
				throw new XMongo_Exception("Unable to drop Mongo database `{$database}`: {$e->getMessage()}");
			}

		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	//! Drop collection
	*	--------------------------------------------------------------------------------
	*
	*	Drop a Mongo collection
	*	@usage: $this->mongo_db->drop_collection('foo', 'bar');
	*/
	public function drop_collection($db = "", $col = "")
	{
		if (empty($db))
		{
			throw new XMongo_Exception('Failed to drop MongoDB collection because database name is empty');
		}

		if (empty($col))
		{
			throw new XMongo_Exception('Failed to drop MongoDB collection because collection name is empty');
		}

		else
		{
			try
			{
				$this->connection->{$db}->{$col}->drop();
				return TRUE;
			}
			catch (Exception $e)
			{
				throw new XMongo_Exception("Unable to drop Mongo collection `{$col}`: {$e->getMessage()}");
			}
		}

		return($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	//! Select
	*	--------------------------------------------------------------------------------
	*
	*	Determine which fields to include OR which to exclude during the query process.
	*	Currently, including and excluding at the same time is not available, so the 
	*	$includes array will take precedence over the $excludes array.  If you want to 
	*	only choose fields to exclude, leave $includes an empty array().
	*
	*	@usage: $this->mongo_db->select(array('foo', 'bar'))->get('foobar');
	*/

	public function select($includes = array(), $excludes = array())
	{
	 	if ( ! is_array($includes))
	 	{
	 		$includes = array();
	 	}

	 	if ( ! is_array($excludes))
	 	{
	 		$excludes = array();
	 	}

	 	if ( ! empty($includes))
	 	{
	 		foreach ($includes as $col)
	 		{
	 			$this->selects[$col] = 1;
	 		}
	 	}
	 	else
	 	{
	 		foreach ($excludes as $col)
	 		{
	 			$this->selects[$col] = 0;
	 		}
	 	}
	 	return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	//! Where
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents based on these search parameters.  The $wheres array should 
	*	be an associative array with the field as the key and the value as the search
	*	criteria.
	*
	*	@usage : $this->mongo_db->where(array('foo' => 'bar'))->get('foobar');
	*/

	public function where($wheres, $value = null)
	{
		if (is_array($wheres))
		{
			foreach ($wheres as $wh => $val)
			{
				$this->wheres[$wh] = $val;
			}
		}

		else
		{
			$this->wheres[$wheres] = $value;
		}

		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	or where
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field may be something else
	*
	*	@usage : $this->mongo_db->or_where(array('foo'=>'bar', 'bar'=>'foo'))->get('foobar');
	*/

	public function or_where($wheres = array())
	{
		if (count($wheres) > 0)
		{
			if ( ! isset($this->wheres['$or']) || ! is_array($this->wheres['$or']))
			{
				$this->wheres['$or'] = array();
			}

			foreach ($wheres as $wh => $val)
			{
				$this->wheres['$or'][] = array($wh=>$val);
			}
		}
		return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Where in
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is in a given $in array().
	*
	*	@usage : $this->mongo_db->where_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	*/

	public function where_in($field = "", $in = array())
	{
		$this->_where_init($field);
		$this->wheres[$field]['$in'] = $in;
		return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Where in all
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is in all of a given $in array().
	*
	*	@usage : $this->mongo_db->where_in_all('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	*/

	public function where_in_all($field = "", $in = array())
	{
		$this->_where_init($field);
		$this->wheres[$field]['$all'] = $in;
		return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Where not in
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is not in a given $in array().
	*
	*	@usage : $this->mongo_db->where_not_in('foo', array('bar', 'zoo', 'blah'))->get('foobar');
	*/

	public function where_not_in($field = "", $in = array())
	{
		$this->_where_init($field);
		$this->wheres[$field]['$nin'] = $in;
		return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Where greater than
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is greater than $x
	*
	*	@usage : $this->mongo_db->where_gt('foo', 20);
	*/

	public function where_gt($field = "", $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$gt'] = $x;
		return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Where greater than or equal to
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is greater than or equal to $x
	*
	*	@usage : $this->mongo_db->where_gte('foo', 20);
	*/

	public function where_gte($field = "", $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$gte'] = $x;
		return($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Where less than
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is less than $x
	*
	*	@usage : $this->mongo_db->where_lt('foo', 20);
	*/

	public function where_lt($field = "", $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$lt'] = $x;
		return($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Where less than or equal to
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is less than or equal to $x
	*
	*	@usage : $this->mongo_db->where_lte('foo', 20);
	*/

	public function where_lte($field = "", $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$lte'] = $x;
		return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Where between
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is between $x and $y
	*
	*	@usage : $this->mongo_db->where_between('foo', 20, 30);
	*/

	public function where_between($field = "", $x, $y)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$gte'] = $x;
		$this->wheres[$field]['$lte'] = $y;
		return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Where between and but not equal to
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is between but not equal to $x and $y
	*
	*	@usage : $this->mongo_db->where_between_ne('foo', 20, 30);
	*/

	public function where_between_ne($field = "", $x, $y)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$gt'] = $x;
		$this->wheres[$field]['$lt'] = $y;
		return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Where not equal
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents where the value of a $field is not equal to $x
	*
	*	@usage : $this->mongo_db->where_ne('foo', 1)->get('foobar');
	*/

	public function where_ne($field = '', $x)
	{
		$this->_where_init($field);
		$this->wheres[$field]['$ne'] = $x;
		return ($this);
	}

	/**
     * near条件，geo查询
     * @param string $field
     * @param array $co 坐标
     * @param float $maxDistance 最大距离，单位km 
     * @param array $whereData
     * @return array
     * @example  $this->mongo_db->where_near('foo', array('50','50'));
     */
	public function where_near($field = '', $co = array(),$maxDistance=null)
	{
		$this->__where_init($field);
		$this->wheres[$field]['$near'] = $co;
		if ($maxDistance) {
		    $this->wheres[$field]['$maxDistance'] = $maxDistance / self::EARTH_RADIUS;
		}
		return ($this);
	}
	
	/**
     * nearSphere 以球面方式计算，geo查询
     * @param string $field
     * @param array $co 坐标
     * @param float $maxDistance 最大距离，单位km
     * @param array $whereData
     * @return array
     * @example  $this->mongo_db->where_nearSphere('foo', array('50','50'));
     */
	public function where_nearSphere($field = '', $co = array(),$maxDistance = null)
	{
	    $this->_where_init($field);
	    $this->wheres[$field]['$nearSphere'] = $co;
	    if ($maxDistance) {
	        $this->wheres[$field]['$maxDistance'] = $maxDistance / self::EARTH_RADIUS;
	    }
	    return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Like
	*	--------------------------------------------------------------------------------
	*	
	*	Get the documents where the (string) value of a $field is like a value. The defaults
	*	allow for a case-insensitive search.
	*
	*	@param $flags
	*	Allows for the typical regular expression flags:
	*		i = case insensitive
	*		m = multiline
	*		x = can contain comments
	*		l = locale
	*		s = dotall, "." matches everything, including newlines
	*		u = match unicode
	*
	*	@param $enable_start_wildcard
	*	If set to anything other than TRUE, a starting line character "^" will be prepended
	*	to the search value, representing only searching for a value at the start of 
	*	a new line.
	*
	*	@param $enable_end_wildcard
	*	If set to anything other than TRUE, an ending line character "$" will be appended
	*	to the search value, representing only searching for a value at the end of 
	*	a line.
	*
	*	@usage : $this->mongo_db->like('foo', 'bar', 'im', FALSE, TRUE);
	*/

	public function like($field = "", $value = "", $flags = "i", $enable_start_wildcard = TRUE, $enable_end_wildcard = TRUE)
	 {
	 	$field = (string) trim($field);
	 	$this->_where_init($field);
	 	$value = (string) trim($value);
	 	$value = quotemeta($value);

	 	if ($enable_start_wildcard !== TRUE)
	 	{
	 		$value = "^" . $value;
	 	}

	 	if ($enable_end_wildcard !== TRUE)
	 	{
	 		$value .= "$";
	 	}

	 	$regex = "/$value/$flags";
	 	$this->wheres[$field] = new MongoRegex($regex);
	 	return ($this);
	 }

	/**
	*	--------------------------------------------------------------------------------
	*	// Order by
	*	--------------------------------------------------------------------------------
	*
	*	Sort the documents based on the parameters passed. To set values to descending order,
	*	you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	*	set to 1 (ASC).
	*
	*	@usage : $this->mongo_db->order_by(array('foo' => 'ASC'))->get('foobar');
	*/

	public function order_by($fields = array())
	{
		foreach ($fields as $col => $val)
		{
			if ($val == -1 || $val === FALSE || strtolower($val) == 'desc')
			{
				$this->sorts[$col] = -1; 
			}
			else
			{
				$this->sorts[$col] = 1;
			}
		}
		return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	// Limit results
	*	--------------------------------------------------------------------------------
	*
	*	Limit the result set to $x number of documents
	*
	*	@usage : $this->mongo_db->limit($x);
	*/

	public function limit($x = 99999)
	{
		if ($x !== NULL && is_numeric($x) && $x >= 1)
		{
			$this->limit = (int) $x;
		}
		return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	// Offset
	*	--------------------------------------------------------------------------------
	*
	*	Offset the result set to skip $x number of documents
	*
	*	@usage : $this->mongo_db->offset($x);
	*/

	public function offset($x = 0)
	{
		if ($x !== NULL && is_numeric($x) && $x >= 1)
		{
			$this->offset = (int) $x;
		}
		return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	// Get where
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents based upon the passed parameters
	*
	*	@usage : $this->mongo_db->get_where('foo', array('bar' => 'something'));
	*/

	public function get_where($collection = "", $where = array())
	{
		return ($this->where($where)->get($collection));
	}

	/**
	*	--------------------------------------------------------------------------------
	*	// Get
	*	--------------------------------------------------------------------------------
	*
	*	Get the documents based upon the passed parameters
	*
	*	@usage : $this->mongo_db->get('foo');
	*/

	 public function get($collection = "")
	 {
	 	if (empty($collection))
	 	{
	 		throw new XMongo_Exception("In order to retreive documents from MongoDB, a collection name must be passed");
	 	}

	 	$documents = $this->db->{$collection}->find($this->wheres, $this->selects)->limit((int) $this->limit)->skip((int) $this->offset)->sort($this->sorts);

	 	// Clear
	 	$this->_clear();

	 	$returns = array();

	 	while ($documents->hasNext())
	     {
	         $temp = $documents->getNext();
	         if (isset($temp[self::DEAFAULT_ID]) AND !isset($temp['created'])){
	             $temp['created'] = $temp[self::DEAFAULT_ID]->getTimestamp();
	             $temp['id'] = (string)$temp[self::DEAFAULT_ID];
	         }
	         	
	         $returns[] = (array) $temp;
	     }
	 
	     return $returns;
	 }
	 /**
	  * 获取文档列表
	  * @param string $collection
	  * @param array $selects
	  * @param array $where
	  * @param array $order
	  * @param int $count
	  * @param int $offset
	  * 
	  * @return array
	  */
	 public function getAll($collection = "",$selects = array(), $where = array(), $order = array(), $count = 0, $offset = 0)
	 {
	     if (empty($collection))
	     {
	         throw new XMongo_Exception("In order to retreive documents from MongoDB, a collection name must be passed");
	     }
	 
	     $documents = $this->db->{$collection}->find($where, $selects)->skip((int) $offset)->sort($order);
	     if ($count > 0) {
	         $documents->limit((int) $count);
	     }
	     $returns = array();
	 
	     while ($documents->hasNext())
	     {
	         $temp = $documents->getNext();
	         if (isset($temp[self::DEAFAULT_ID]) AND !isset($temp['created'])){
	             $temp['created'] = $temp[self::DEAFAULT_ID]->getTimestamp();
	             $temp['id'] = (string)$temp[self::DEAFAULT_ID];
	         }
	         	
	         $returns[] = (array) $temp;
	     }
	 
	     return $returns;
	 
	 }
	 
	 /**
	  * 获取单个文档
	  * @param sring $collection
	  * @param array $selects
	  * @param array $where
	  * 
	  * @return array
	  */
	 public function getOne($collection = "",$selects = array(), $where = array())
	 {
	     if (empty($collection))
	     {
	         throw new XMongo_Exception("In order to retreive documents from MongoDB, a collection name must be passed");
	     }
	 
	     $documents = $this->db->{$collection}->findOne($where, $selects);
	 
	     if (isset($documents[self::DEAFAULT_ID]) AND !isset($documents['created'])){
	         $documents['created'] = $documents[self::DEAFAULT_ID]->getTimestamp();
	         $documents['id'] = (string)$documents[self::DEAFAULT_ID];
	     }
	 
	     return $documents;
	 
	 }
	 

	/**
	*	--------------------------------------------------------------------------------
	*	Count
	*	--------------------------------------------------------------------------------
	*
	*	Count the documents based upon the passed parameters
	*
	*	@usage : $this->mongo_db->count('foo');
	*/

	public function count($collection = "", $where = null) {
		if (empty($collection))
		{
			throw new XMongo_Exception("In order to retreive a count of documents from MongoDB, a collection name must be passed");
		}
		
		//统计时传入$where参数则根据参数中的条件统计，没有传参则根据已有的$this->wheres条件统计。
		if ($where === null){
		    $where = $this->wheres;
		}

		$count = $this->db->{$collection}->find($where)->count();
		//$this->_clear();
		return ($count);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	//! Insert
	*	--------------------------------------------------------------------------------
	*
	*	Insert a new document into the passed collection
	*
	*	@usage : $this->mongo_db->insert('foo', $data = array());
	*/

	public function insert($collection = "", $insert = array())
	{
		if (empty($collection))
		{
			throw new XMongo_Exception("No Mongo collection selected to insert into");
		}

		if (count($insert) == 0 || !is_array($insert))
		{
			throw new XMongo_Exception("Nothing to insert into Mongo collection or insert is not an array");
		}

		try
		{
			$this->db->{$collection}->insert($insert, array('safe' => $this->safe));
			if (isset($insert[self::DEAFAULT_ID]))
			{
				return ($insert[self::DEAFAULT_ID]);
			}
			else
			{
				return (FALSE);
			}
		}
		catch (MongoCursorException $e)
		{
			throw new XMongo_Exception("Insert of data into MongoDB failed: {$e->getMessage()}");
		}
	}
    
    /**
    * --------------------------------------------------------------------------------
    * Batch Insert
    * --------------------------------------------------------------------------------
    *
    * Insert a multiple new document into the passed collection
    *
    * @usage : $this->mongo_db->batch_insert('foo', $data = array());
    */
    public function batch_insert($collection = "", $insert = array())
    {
        if (empty($collection)) {
            throw new XMongo_Exception("No Mongo collection selected to insert into");
        }
        
        if (count($insert) == 0 || ! is_array($insert)) {
            throw new XMongo_Exception("Nothing to insert into Mongo collection or insert is not an array");
        }
        
        try {
            $this->db->{$collection}->batchInsert($insert, array('safe' => $this->safe));
            $ids = array();
            foreach ($insert as $doc)
            {
                $ids[] = $doc[self::DEAFAULT_ID];
            }
            if (empty($ids)) {
                return false;
            } else {
                return $ids;
            }
        } catch (MongoCursorException $e) {
            throw new XMongo_Exception("Insert of data into MongoDB failed: {$e->getMessage()}");
        }
    }


	/**
	*	--------------------------------------------------------------------------------
	*	//! Update
	*	--------------------------------------------------------------------------------
	*
	*	Updates a single document
	*
	*	@usage: $this->mongo_db->update('foo', $data = array());
	*/

	public function update($collection = "", $data = array(), $options = array())
	{
		if (empty($collection))
		{
			throw new XMongo_Exception("No Mongo collection selected to update");
		}

		if (is_array($data) && count($data) > 0)
		{
			$this->updates = array_merge($data, $this->updates);
		}

		if (count($this->updates) == 0)
		{
			throw new XMongo_Exception("Nothing to update in Mongo collection or update is not an array");	
		}

		try
		{
			$options = array_merge($options, array('safe' => $this->safe, 'multiple' => FALSE));
			$this->db->{$collection}->update($this->wheres, $this->updates, $options);
			$this->_clear();
			return (TRUE);
		}
		catch (MongoCursorException $e)
		{
			throw new XMongo_Exception("Update of data into MongoDB failed: {$e->getMessage()}");
		}
	}


	/**
	*	--------------------------------------------------------------------------------
	*	Update all
	*	--------------------------------------------------------------------------------
	*
	*	Updates a collection of documents
	*
	*	@usage: $this->mongo_db->update_all('foo', $data = array());
	*/

	public function update_all($collection = "", $data = array(), $options = array())
	{
		if (empty($collection))
		{
			throw new XMongo_Exception("No Mongo collection selected to update");
		}

		if (is_array($data) && count($data) > 0)
		{
			$this->updates = array_merge($data, $this->updates);
		}

		if (count($this->updates) == 0)
		{
			throw new XMongo_Exception("Nothing to update in Mongo collection or update is not an array");	
		}

		try
		{
			$options = array_merge($options, array('safe' => $this->safe, 'multiple' => TRUE));
			$this->db->{$collection}->update($this->wheres, $this->updates, $options);
			$this->_clear();
			return (TRUE);
		}
		catch (MongoCursorException $e)
		{
			throw new XMongo_Exception("Update of data into MongoDB failed: {$e->getMessage()}");
		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Inc 增长
	*	--------------------------------------------------------------------------------
	*
	*	Increments the value of a field
	*
	*	@usage: $this->mongo_db->where(array('blog_id'=>123))->inc(array('num_comments' => 1))->update('blog_posts');
	*/

	public function inc($fields = array(), $value = 0)
	{
		$this->_update_init('$inc');

		if (is_string($fields))
		{
			$this->updates['$inc'][$fields] = $value;
		}

		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$inc'][$field] = $value;
			}
		}

		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Dec 减少
	*	--------------------------------------------------------------------------------
	*
	*	Decrements the value of a field
	*
	*	@usage: $this->mongo_db->where(array('blog_id'=>123))->dec(array('num_comments' => 1))->update('blog_posts');
	
    //该方法与$inc作用一样
	public function dec($fields = array(), $value = 0)
	{
		$this->_update_init('$inc');

		if (is_string($fields))
		{
			$this->updates['$inc'][$fields] = $value;
		}

		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$inc'][$field] = $value;
			}
		}

		return $this;
	}
*/
	/**
	*	--------------------------------------------------------------------------------
	*	Set
	*	--------------------------------------------------------------------------------
	*
	*	Sets a field to a value
	*
	*	@usage: $this->mongo_db->where(array('blog_id'=>123))->set('posted', 1)->update('blog_posts');
	*	@usage: $this->mongo_db->where(array('blog_id'=>123))->set(array('posted' => 1, 'time' => time()))->update('blog_posts');
	*/

	public function set($fields, $value = NULL)
	{
		$this->_update_init('$set');

		if (is_string($fields))
		{
			$this->updates['$set'][$fields] = $value;
		}

		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$set'][$field] = $value;
			}
		}

		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Unset
	*	--------------------------------------------------------------------------------
	*
	*	Unsets a field (or fields)
	*
	*	@usage: $this->mongo_db->where(array('blog_id'=>123))->unset('posted')->update('blog_posts');
	*	@usage: $this->mongo_db->where(array('blog_id'=>123))->set(array('posted','time'))->update('blog_posts');
	*/

	public function unset_field($fields)
	{
		$this->_update_init('$unset');

		if (is_string($fields))
		{
			$this->updates['$unset'][$fields] = 1;
		}

		elseif (is_array($fields))
		{
			foreach ($fields as $field)
			{
				$this->updates['$unset'][$field] = 1;
			}
		}

		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Add to set
	*	--------------------------------------------------------------------------------
	*
	*	Adds value to the array only if its not in the array already
	*
	*	@usage: $this->mongo_db->where(array('blog_id'=>123))->addtoset('tags', 'php')->update('blog_posts');
	*	@usage: $this->mongo_db->where(array('blog_id'=>123))->addtoset('tags', array('php', 'codeigniter', 'mongodb'))->update('blog_posts');
	*/

	public function addtoset($field, $values)
	{
		$this->_update_init('$addToSet');

		if (is_string($values))
		{
			$this->updates['$addToSet'][$field] = $values;
		}

		elseif (is_array($values))
		{
			$this->updates['$addToSet'][$field] = array('$each' => $values);
		}

		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Push
	*	--------------------------------------------------------------------------------
	*
	*	Pushes values into a field (field must be an array)
	*
	*	@usage: $this->mongo_db->where(array('blog_id'=>123))->push('comments', array('text'=>'Hello world'))->update('blog_posts');
	*	@usage: $this->mongo_db->where(array('blog_id'=>123))->push(array('comments' => array('text'=>'Hello world')), 'viewed_by' => array('Alex')->update('blog_posts');
	*/

	public function push($fields, $value = array())
	{
		$this->_update_init('$push');

		if (is_string($fields))
		{
			$this->updates['$push'][$fields] = $value;
		}

		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$push'][$field] = $value;
			}
		}

		return $this;
	}

	/*public function push_all($fields, $value = array())
	{
		$this->_update_init('$pushAll');
		
		if (is_string($fields))
		{
			$this->updates['$pushAll'][$fields] = $value;
		}
		
		elseif (is_array($fields))
		{
			foreach ($fields as $field => $value)
			{
				$this->updates['$pushAll'][$field] = $value;
			}
		}
		
		return $this;
	}*/

	/**
	*	--------------------------------------------------------------------------------
	*	Pop
	*	--------------------------------------------------------------------------------
	*
	*	Pops the last value from a field (field must be an array)
	*
	*	@usage: $this->mongo_db->where(array('blog_id'=>123))->pop('comments')->update('blog_posts');
	*	@usage: $this->mongo_db->where(array('blog_id'=>123))->pop(array('comments', 'viewed_by'))->update('blog_posts');
	*/

	public function pop($field)
	{
		$this->_update_init('$pop');

		if (is_string($field))
		{
			$this->updates['$pop'][$field] = -1;
		}

		elseif (is_array($field))
		{
			foreach ($field as $pop_field)
			{
				$this->updates['$pop'][$pop_field] = -1;
			}
		}

		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*
	*	Pull
	*	--------------------------------------------------------------------------------
	*
	*	Removes by an array by the value of a field
	*
	*	@usage: $this->mongo_db->pull('comments', array('comment_id'=>123))->update('blog_posts');
	*/

	public function pull($field = "", $value = array())
	{
		$this->_update_init('$pull');

		$this->updates['$pull'] = array($field => $value);

		return $this;
	}

	/*public function pull_all($field = "", $value = array())
	{
		$this->_update_init('$pullAll');
	
		$this->updates['$pullAll'] = array($field => $value);
		
		return $this;
	}*/

	/**
	*	--------------------------------------------------------------------------------
	*	Rename field
	*	--------------------------------------------------------------------------------
	*
	*	Renames a field
	*
	*	@usage: $this->mongo_db->where(array('blog_id'=>123))->rename_field('posted_by', 'author')->update('blog_posts');
	*/

	public function rename_field($old, $new)
	{
		$this->_update_init('$rename');

		$this->updates['$rename'][] = array($old => $new);

		return $this;
	}

	/**
	*	--------------------------------------------------------------------------------
	*	//! Delete
	*	--------------------------------------------------------------------------------
	*
	*	delete document from the passed collection based upon certain criteria
	*
	*	@usage : $this->mongo_db->delete('foo');
	*/

	public function delete($collection = "")
	{
		if (empty($collection))
		{
			throw new XMongo_Exception("No Mongo collection selected to delete from");
		}

		try
		{
			$msg = $this->db->{$collection}->remove($this->wheres, array('safe' => $this->safe, 'justOne' => TRUE));
			$this->_clear();
			return (bool)$msg['n'];
		}
		catch (MongoCursorException $e)
		{
			throw new XMongo_Exception("Delete of data into MongoDB failed: {$e->getMessage()}");
		}

	}

	/**
	*	--------------------------------------------------------------------------------
	*	Delete all
	*	--------------------------------------------------------------------------------
	*
	*	Delete all documents from the passed collection based upon certain criteria
	*
	*	@usage : $this->mongo_db->delete_all('foo', $data = array());
	*/

	 public function delete_all($collection = "")
	 {
		if (empty($collection))
		{
			throw new XMongo_Exception("No Mongo collection selected to delete from");
		}

		try
		{
			$msg = $this->db->{$collection}->remove($this->wheres, array('safe' => $this->safe, 'justOne' => FALSE));
			$this->_clear();
			return (bool)$msg['n'];
		}
		catch (MongoCursorException $e)
		{
			throw new XMongo_Exception("Delete of data into MongoDB failed: {$e->getMessage()}");
		}

	}

	/**
	*	--------------------------------------------------------------------------------
	*	*	//! Command
	*	--------------------------------------------------------------------------------
	*
	*	Runs a MongoDB command (such as GeoNear). See the MongoDB documentation for more usage scenarios:
	*	http://dochub.mongodb.org/core/commands
	*
	*	@usage : $this->mongo_db->command(array('geoNear'=>'buildings', 'near'=>array(53.228482, -0.547847), 'num' => 10, 'nearSphere'=>true));
	*/

	public function command($query = array())
	{
		try
		{
			$run = $this->db->command($query);
			return $run;
		}

		catch (MongoCursorException $e)
		{
			throw new XMongo_Exception("MongoDB command failed to execute: {$e->getMessage()}");
		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	//! Add indexes
	*	--------------------------------------------------------------------------------
	*
	*	Ensure an index of the keys in a collection with optional parameters. To set values to descending order,
	*	you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	*	set to 1 (ASC).
	*
	*	@usage : $this->mongo_db->add_index($collection, array('first_name' => 'ASC', 'last_name' => -1), array('unique' => TRUE));
	*/

	public function add_index($collection = "", $keys = array(), $options = array())
	{
		if (empty($collection))
		{
			throw new XMongo_Exception("No Mongo collection specified to add index to");
		}

		if (empty($keys) || ! is_array($keys))
		{
			throw new XMongo_Exception("Index could not be created to MongoDB Collection because no keys were specified");
		}

		foreach ($keys as $col => $val)
		{
			if($val == -1 || $val === FALSE || strtolower($val) == 'desc')
			{
				$keys[$col] = -1; 
			}
			else
			{
				$keys[$col] = 1;
			}
		}

		if ($this->db->{$collection}->ensureIndex($keys, $options) == TRUE)
		{
			$this->_clear();
			return ($this);
		}
		else
		{
			throw new XMongo_Exception("An error occured when trying to add an index to MongoDB Collection");
		}
	}



	/**
	*	--------------------------------------------------------------------------------
	*	Remove index
	*	--------------------------------------------------------------------------------
	*
	*	Remove an index of the keys in a collection. To set values to descending order,
	*	you must pass values of either -1, FALSE, 'desc', or 'DESC', else they will be
	*	set to 1 (ASC).
	*
	*	@usage : $this->mongo_db->remove_index($collection, array('first_name' => 'ASC', 'last_name' => -1));
	*/

	public function remove_index($collection = "", $keys = array())
	{
		if (empty($collection))
		{
			throw new XMongo_Exception("No Mongo collection specified to remove index from");
		}

		if (empty($keys) || ! is_array($keys))
		{
			throw new XMongo_Exception("Index could not be removed from MongoDB Collection because no keys were specified");
		}

		if ($this->db->{$collection}->deleteIndex($keys, $options) == TRUE)
		{
			$this->_clear();
			return ($this);
		}
		else
		{
			throw new XMongo_Exception("An error occured when trying to remove an index from MongoDB Collection");
		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Remove all indexes
	*	--------------------------------------------------------------------------------
	*
	*	Remove all indexes from a collection.
	*
	*	@usage : $this->mongo_db->remove_all_index($collection);
	*/

	public function remove_all_indexes($collection = "")
	{
		if (empty($collection))
		{
			throw new XMongo_Exception("No Mongo collection specified to remove all indexes from");
		}
		$this->db->{$collection}->deleteIndexes();
		$this->_clear();
		return ($this);
	}

	/**
	*	--------------------------------------------------------------------------------
	*	List indexes
	*	--------------------------------------------------------------------------------
	*
	*	Lists all indexes in a collection.
	*
	*	@usage : $this->mongo_db->list_indexes($collection);
	*/
	public function list_indexes($collection = "")
	{
		if (empty($collection))
		{
			throw new XMongo_Exception("No Mongo collection specified to remove all indexes from");
		}

		return ($this->db->{$collection}->getIndexInfo());
	}
    
    /**
     *	--------------------------------------------------------------------------------
	 *	Mongo Date
	 *	--------------------------------------------------------------------------------
	 *
	 *	Create new MongoDate object from current time or pass timestamp to create
     *  mongodate.
	 *
	 *	@usage : $this->mongo_db->date($timestamp);
     */
    public function date($stamp = FALSE)
    {
            if ( $stamp == FALSE )
            {   
                    return new MongoDate();
            }
            
            return new MongoDate($stamp);            
    }
    
    /**
     *	--------------------------------------------------------------------------------
	 *	Get Database Reference
	 *	--------------------------------------------------------------------------------
	 *
	 *	Get mongo object from database reference using MongoDBRef
	 *
	 *	@usage : $this->mongo_db->get_dbref($object);
     */    
    public function get_dbref($obj)
    {
        if (empty($obj) OR !isset($obj))
        {
                throw new XMongo_Exception('To use MongoDBRef::get() ala get_dbref() you must pass a valid reference object');
        }

        return MongoDBRef::get($this->db, $obj);
    }

    /**
     *	--------------------------------------------------------------------------------
	 *	Create Database Reference
	 *	--------------------------------------------------------------------------------
	 *
	 *	Create mongo dbref object to store later
	 *
	 *	@usage : $ref = $this->mongo_db->create_dbref($collection, $id);
     */    
    public function create_dbref($collection = "", $id = "", $database = FALSE )
    {
        if (empty($collection))
        {
            throw new XMongo_Exception("In order to retreive documents from MongoDB, a collection name must be passed");
        }

        if (empty($id) OR !isset($id))
        {
                throw new XMongo_Exception('To use MongoDBRef::create() ala create_dbref() you must pass a valid id field of the object which to link');
        }

        $db = $database ? $database : $this->db;

        return MongoDBRef::create($collection, $id, $db);
    }


	/**
	*	--------------------------------------------------------------------------------
	*	_clear
	*	--------------------------------------------------------------------------------
	*
	*	Resets the class variables to default settings
	*/

	private function _clear()
	{
		$this->selects	= array();
		$this->updates	= array();
		$this->wheres	= array();
		$this->limit	= 999999;
		$this->offset	= 0;
		$this->sorts	= array();
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Where initializer
	*	--------------------------------------------------------------------------------
	*
	*	Prepares parameters for insertion in $wheres array().
	*/

	private function _where_init($param)
	{
		if ( ! isset($this->wheres[$param]))
		{
			$this->wheres[ $param ] = array();
		}
	}

	/**
	*	--------------------------------------------------------------------------------
	*	Update initializer
	*	--------------------------------------------------------------------------------
	*
	*	Prepares parameters for insertion in $updates array().
	*/

	private function _update_init($method)
	{
		if ( ! isset($this->updates[$method]))
		{
			$this->updates[ $method ] = array();
		}
	}
	
}
