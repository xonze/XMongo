<?php
/**
 * mongoDB 工具类  
 * 用于组装查询条件更新语句等
 * @author lwx
 *
 */
require_once 'XMongo.php';
require_once 'XMongo/Db.php';
require_once 'XMongo/Exception.php';
class XMongo_Utility
{
    /**
     * 组装返回的文档字段属性集
     * 
     * @param array|string $includes 包含哪些字段属性
     * @param array|string $excludes 排除哪些字段属性
     * @return array
     */
    
    static public function select($includes = array(), $excludes = array())
    {
        $selects = array();
        if ( ! is_array($includes))
        {
            $f = array();
            if (is_string($includes) && !in_array($includes, array('','*'))) {
                $tmp = explode(',', $includes);
                foreach ($tmp as $t) {
                    $f[] = trim($t);
                }
            }
            $includes = $f;
        }
    
        if ( ! is_array($excludes))
        {
            $f = array();
            if (is_string($excludes)) {
                $tmp = explode(',', $excludes);
                foreach ($tmp as $t) {
                    $f[] = trim($t);
                }
            }
            $excludes = $f;
        }
    
        if ( ! empty($includes))
        {
            foreach ($includes as $col)
            {
                $selects[$col] = 1;
            }
        }
        else
        {
            foreach ($excludes as $col)
            {
                $selects[$col] = 0;
            }
        }
        return $selects;
    }
    
    /**
     * where 条件组装
     * @param string|array $wheres
     * @param string $value
     * @param array $where
     * @return array
     * 
     * @example
     *     XMongo_Utility::where(array('foo' => 'bar'));<br/>
     *     XMongo_Utility::where('foo', 'bar');
     */
    static public function where($wheres, $value = null,$whereData=array())
    {
        if (is_array($wheres))
        {
            foreach ($wheres as $wh => $val)
            {
                if ($wh == XMongo_Db::DEAFAULT_ID && !$val instanceof MongoId) {
                    $whereData[$wh] = new MongoId($val);
                }else{
                    $whereData[$wh] = $val;
                }
            }
        }else{
            if ($wheres == XMongo_Db::DEAFAULT_ID && !$value instanceof MongoId) {
                $whereData[$wheres] = new MongoId($value);
            }else{
                $whereData[$wheres] = $value;
            }
        }
    
        return $whereData;
    }
    
    /**
     * or条件， $or操作符的拼装
     * @param array $wheres
     * @param array $whereData 
     * @return array
     * @example : XMongo_Utility::or_where(array('foo'=>'bar', 'bar'=>'foo'));
     */
    static public function or_where($wheres = array(), $whereData=array())
    {
        foreach ($wheres as $wh => $val)
        {
            if ($wh == XMongo_Db::DEAFAULT_ID && !$val instanceof MongoId && !is_array($val)) {
                $val =  new MongId($val);
            }
            if (is_array($val)) {
                $whereData['$or'][] = $val;
            }else{
                $whereData['$or'][] = array($wh=>$val);
            }
        }
         
        return $whereData;
    }
    
    /**
     * in条件，$in 操作符的拼装
     * @param string $field
     * @param array $in
     * @param array $whereData
     * @return array
     * @example  XMongo_Utility::where_in('foo', array('bar', 'zoo', 'blah'));
     */
    static public function where_in($field = "", $in = array(),$whereData=array())
    {
        if ($field == XMongo_Db::DEAFAULT_ID) {
            foreach ($in as &$_id){
                if (!$_id instanceof MongoId) {
                    $_id = new MongoId($_id);
                }
            }
        }
        $whereData[$field]['$in'] = $in;
        
        return $whereData;
    }
    
    /**
     * in all条件，$all 操作符的拼装
     * @param string $field
     * @param array $in
     * @param array $whereData
     * @return array
     * @example  
     *     XMongo_Utility::where_in_all('foo', array('bar', 'zoo', 'blah'));//查询foo字段中同时具有'bar', 'zoo', 'blah'三个属性值的文档
     */
    static public function where_in_all($field = "", $in = array(),$whereData=array())
    {
        $whereData[$field]['$all'] = $in;
        
        return $whereData;
    }
    
    /**
     * not in条件，功能与sql中的not in()类似
     * @param string $field
     * @param array $in
     * @param array $whereData
     * @return array
     * @example  XMongo_Utility::where_not_in('foo', array('bar', 'zoo', 'blah'));
     */
    static public function where_not_in($field = "", $in = array(),$whereData=array())
    {
        if ($field == XMongo_Db::DEAFAULT_ID) {
            foreach ($in as &$_id){
                if (!$_id instanceof MongoId) {
                    $_id = new MongoId($_id);
                }
            }
        }
        $whereData[$field]['$nin'] = $in;
        
        return $whereData;
    }
    
    /**
     * 大于条件
     * @param sring $field
     * @param  $x
     * @param array $whereData
     * @return array
     * @example XMongo_Utility::where_gt('foo', 20);//foo>20
     */
    static public function where_gt($field = "", $x,$whereData=array())
    {
        $whereData[$field]['$gt'] = $x;
        
        return $whereData;
    }
    
    /**
     * 大于等于条件
     * @param sring $field
     * @param  $x
     * @param array $whereData
     * @return array
     * @example XMongo_Utility::where_gte('foo', 20);//foo>=20
     */
    static public function where_gte($field = "", $x, $whereData=array())
    {
        $whereData[$field]['$gte'] = $x;
        
        return $whereData;
    }
    
    /**
     * 小于条件
     * @param sring $field
     * @param  $x
     * @param array $whereData
     * @return array
     * @example XMongo_Utility::where_lt('foo', 20);//foo<20
     */
    static public function where_lt($field = "", $x, $whereData=array())
    {
        $whereData[$field]['$lt'] = $x;
        
        return $whereData;
    }
    
    /**
     * 小于等于条件
     * @param sring $field
     * @param  $x
     * @param array $whereData
     * @return array
     * @example XMongo_Utility::where_lte('foo', 20);//foo<=20
     */
    static public function where_lte($field = "", $x, $whereData=array())
    {
        $whereData[$field]['$lte'] = $x;
        
        return $whereData;
    }
    
    /**
     * between条件，$x,$y两个值之间，包含这两个值
     * @param string $field
     * @param  $x
     * @param  $y
     * @param array $whereData
     * @return array
     * @example  XMongo_Utility::where_between('foo', 20, 30); //20 <= foo <= 30
     */
    static public function where_between($field = "", $x, $y,$whereData=array())
    {
        $whereData[$field]['$gte'] = $x;
        $whereData[$field]['$lte'] = $y;
        
        return $whereData;
    }
    
    /**
     * between条件，但不包含$x,$y两个值
     * @param string $field
     * @param  $x
     * @param  $y
     * @param array $whereData
     * @return array
     * @example  XMongo_Utility::where_between('foo', 20, 30);//20 < foo < 30
     */
    static public function where_between_ne($field = "", $x, $y,$whereData=array())
    {
        $whereData[$field]['$gt'] = $x;
        $whereData[$field]['$lt'] = $y;
        
        return $whereData;
    }
    
    /**
     * 不等于
     * @param string $field
     * @param  $x
     * @param array $whereData
     * @return array
     * @example  XMongo_Utility::where_ne('foo', 1);
     */
    static public function where_ne($field = '', $x,$whereData=array())
    {
        if ($field == XMongo_Db::DEAFAULT_ID && !$val instanceof MongoId) {
            $x = new MongoId($x);
        }
        $whereData[$field]['$ne'] = $x;
        
        return $whereData;
    }
    
    /**
     * near条件，geo查询
     * @param string $field
     * @param array $co 坐标
     * @param float $maxDistance 最大距离，单位km 
     * @param array $whereData
     * @return array
     * @example  XMongo_Utility::where_near('foo', array('50','50'));
     */
    static public function where_near($field = '', $co = array(),$maxDistance = null,$whereData=array())
    {
        $whereData[$field]['$near'] = $co;
        if ($maxDistance) {
            $whereData[$field]['$maxDistance'] = $maxDistance / XMongo_Db::EARTH_RADIUS;
        }
        
        return $whereData;
    }
    
    /**
     * nearSphere 以球面方式计算，geo查询
     * @param string $field
     * @param array $co 坐标
     * @param float $maxDistance 最大距离，单位km
     * @param array $whereData
     * @return array
     * @example  XMongo_Utility::where_nearSphere('foo', array('50','50'));
     */
     static public function where_nearSphere($field = '', $co = array(),$maxDistance = null,$whereData = null)
    {
        $whereData[$field]['$nearSphere'] = $co;
        if ($maxDistance) {
            $whereData[$field]['$maxDistance'] = $maxDistance / XMongo_Db::EARTH_RADIUS;
        }
        return $whereData;
    }
    
    /**
     *	--------------------------------------------------------------------------------
     *	Like模糊查询
     *	--------------------------------------------------------------------------------
     *
     *	@param $flags
     *	Allows for the typical regular expression flags:
     *		i = case insensitive 忽略大小写
     *		m = multiline 支持多行文本匹配
     *		x = can contain comments 包含注释
     *		l = locale
     *		s = dotall, "." matches everything, including newlines
     *		u = match unicode
     *
     *	@param $enable_start_wildcard 正则中的 "^" 
     *	If set to anything other than TRUE, a starting line character "^" will be prepended
     *	to the search value, representing only searching for a value at the start of
     *	a new line.
     *
     *	@param $enable_end_wildcard 正则中的 "$" 
     *	If set to anything other than TRUE, an ending line character "$" will be appended
     *	to the search value, representing only searching for a value at the end of
     *	a line.
     *
     *	@example  XMongo_Utility::like('foo', 'bar', 'im', FALSE, TRUE);
     */
    
    static public function like($field = "", $value = "",$whereData=array(), $flags = "i", $enable_start_wildcard = TRUE, $enable_end_wildcard = TRUE)
    {
        $field = (string) trim($field);
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
        $whereData[$field] = new MongoRegex($regex);
        return $whereData;
    }
    
    /**
     * 排序方法
     * @param sring|array $fields
     * @return array
     * @example
     *    Mongo_db::order_by(array('foo' => 'ASC','bar'=>1)); <br/>
     *    Mongo_db::order_by('foo ASC,bar desc');
     *    Mongo_db::order_by('foo -1,bar 1');
     */
    static public function order_by($fields = array())
    {
        $sorts = array();
        if (is_string($fields)) {
            $tmp = explode(',', $fields);
            $tmp = preg_replace('/\s+/',' ',$tmp);
            //将数组中的多个连续空格替换为一个
            $fields = array();
            foreach ($tmp as $t) {
                $t = explode(' ', trim($t));
                $fields[$t[0]] = $t[1];
            }
        }
        foreach ($fields as $col => $val)
        {
            if ($val == -1 || $val === FALSE || strtolower($val) == 'desc')
            {
                $sorts[$col] = -1;
            }
            else
            {
                $sorts[$col] = 1;
            }
        }
        return $sorts;
    }
    
    /**
     * Inc 增长更新
     * @param string|array $fields
     * @param string|array $value
     * @param array $updates 已有的更新数据
     * @return array
     * @example XMongo_Utility::inc(array('click' => 1));
     */
    static public function inc($fields = array(), $value = 0,$updates= array())
    {
    
        if (is_string($fields))
        {
            $updates['$inc'][$fields] = $value;
        }
    
        elseif (is_array($fields))
        {
            foreach ($fields as $field => $value)
            {
                $updates['$inc'][$field] = $value;
            }
        }
    
        return $updates;
    }
    
    /**
     * set方式更新，在原有数据基础上更新
     * @param string|array $fields
     * @param string|array $value
     * @param array $updates 已有的更新数据
     * @return array
     * @example XMongo_Utility::set(array('posted' => 1, 'time' => time()));
     */
    static public function set($fields, $value = NULL,$updates= array())
    {
        $updates = array();
    
        if (is_string($fields))
        {
            $updates['$set'][$fields] = $value;
        }
    
        elseif (is_array($fields))
        {
            foreach ($fields as $field => $value)
            {
                $updates['$set'][$field] = $value;
            }
        }
    
        return $updates;
    }
    
    /**
     * unset_field删除一个属性
     * @param string|array $fields
     * @param array $updates
     * @return array
     * @example XMongo_Utility::unset(array('posted','time'));
     */
    static public function unset_field($fields,$updates= array())
    {
        $updates = array();
    
        if (is_string($fields))
        {
            $updates['$unset'][$fields] = 1;
        }
    
        elseif (is_array($fields))
        {
            foreach ($fields as $field)
            {
                $updates['$unset'][$field] = 1;
            }
        }
    
        return $updates;
    }
    
    /**
     * addtoset
     * @param string $field
     * @param string|array $values
     * @param array $updates
     * @return array
     * @example XMongo_Utility::addtoset('tags', array('php', 'codeigniter', 'mongodb'));
     */
    static public function addtoset($field, $values,$updates= array())
    {
    
        if (is_string($values))
        {
            $updates['$addToSet'][$field] = $values;
        }
    
        elseif (is_array($values))
        {
            $updates['$addToSet'][$field] = array('$each' => $values);
        }
    
        return $updates;
    }
    
    /**
     * Push
     * @param string $field
     * @param string|array $values
     * @param array $updates
     * @return array
     * @example XMongo_Utility::push(array('comments' => array('text'=>'Hello world')), 'viewed_by' => array('Alex');
     */
    static public function push($fields, $value = array(),$updates= array())
    {
    
        if (is_string($fields))
        {
            $updates['$push'][$fields] = $value;
        }
    
        elseif (is_array($fields))
        {
            foreach ($fields as $field => $value)
            {
                $updates['$push'][$field] = $value;
            }
        }
    
        return $updates;
    }
    
    /**
     * appends each value in value_array to field, if field is an existing array,
     * otherwise sets field to the array value_array if field is not present.
     * If field is present but is not an array, an error condition is raised.
     * @param string|array $fields
     * @param array $value
     */
    static public function push_all($fields, $value = array(),$updates= array())
    {
    
        if (is_string($fields))
        {
            $updates['$pushAll'][$fields] = $value;
        }
    
        elseif (is_array($fields))
        {
            foreach ($fields as $field => $value)
            {
                $updates['$pushAll'][$field] = $value;
            }
        }
    
        return $updates;
    }
    
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
    
    static public function pop($field,$updates= array())
    {
    
        if (is_string($field))
        {
            $updates['$pop'][$field] = -1;
        }
    
        elseif (is_array($field))
        {
            foreach ($field as $pop_field)
            {
                $updates['$pop'][$pop_field] = -1;
            }
        }
    
        return $updates;
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
    
    static public function pull($field = "", $value = array(),$updates= array())
    {
    
        $updates['$pull'] = array($field => $value);
    
        return $updates;
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
    
    static public function rename_field($old, $new,$updates= array())
    {
    
        $updates['$rename'][] = array($old => $new);
    
        return $updates;
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
    static public function date($stamp = FALSE)
    {
        if ( $stamp == FALSE )
        {
            return new MongoDate();
        }
    
        return new MongoDate($stamp);
    }
}
