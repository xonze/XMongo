<?php
require_once 'XMongo/Db.php';
require_once 'XMongo/Exception.php';
require_once 'XMongo/Utility.php';
/**
 * XMongo是数据映射模型的抽象类
 * @author xonze|李文祥(xonze@sohu.com)
 * @package XMongo
 */
abstract class XMongo
{
    /**
     * XMongo_Db 实例
     * @var XMongo_Db
     */
    protected $_db;
    
    /** 所映射的db name */
    protected $_dbname;
    
    /** 所映射的collection */
    protected $_table = '';//兼容关系数据库的习惯
    protected $_collection = '';
    
    /** 主键 */
    protected $_prikey = '_id';
    /** 数据验证规则 */
    protected $_autov = array();
    
    /** 地球平均半径（单位： km） = (赤道半径6378.140×2+极半径6356.755)×(1/3) */
    const EARTH_RADIUS = 6371;
    
    protected  $_errors = array();
    protected  $_errnum = 0;

    /**
     * 构造基于Mongodb的Model
     * @param XMongo_Db $db
     */
    public function __construct (XMongo_Db $db= null)
    {
        if ($db) {
            $this->_db = $db;
        }else{
            $this->_db = XMongo_Db::getInstance();
        }
        
        if ($this->_dbname) {
            $this->_db->switch_db($this->_dbname);
        }
        if (!$this->_collection) {
            $this->_collection = $this->_table;
        }
    }
    
    
    public function __call($method, $args)
    {
        throw new XMongo_Exception("Invalid method:$method");
    }
    
    /**
     * 统计文档数量
     * @param array $where
     * @return int
     */
    public function count($where = array())
    {
        return $this->_db->count($this->_collection,$where);
        
    }
    
    /**
     * 获取文档列表
     * @param array|string $select 支持 name,age,address写法
     * @param array $where
     * @param array|string $order 支持name desc,age asc写法
     * @param int $count
     * @param int $offset
     * @return array
     */
    public function getAll($select = array(),array $where = array(),$order = array(),$count = 20, $offset = 0)
    {
        if (is_string($select)) {
            $select = XMongo_Utility::select($select);
        }
        $order = XMongo_Utility::order_by($order);
        
        return $this->_db->getAll($this->_collection,$select,$where,$order,$count,$offset);
        
    }
    
    /**
     * 获取单个文档
     * @param string|array $select 支持 name,age,address写法
     * @param array $where
     */
    public function getOne($select = array(),array $where = array())
    {
        if (empty($where)) {
            throw new XMongo_Exception('查询条件不能为空');
        }
        if (is_string($select)) {
            $select = XMongo_Utility::select($select);
        }
        
        return $this->_db->getOne($this->_collection,$select,$where);
    }
    
    /**
     * 删除单个文档，批量删除请使用deleteAll()方法
     * @param array $where
     * @return boolean
     */
    public function delete($where = array())
    {
        if (empty($where)) {
            throw new XMongo_Exception('删除条件不能为空');
        }
        $this->_db->wheres = $where;
        return $this->_db->delete($this->_collection);
    }
    
    /**
     * 批量删除，删除单个文档请使用delete()方法
     * @param array $where
     * @return boolean
     */
    public function deleteAll($where = array())
    {
//         if (empty($where)) {
//             throw new XMongo_Exception('删除条件不能为空');
//         }
        $this->_db->wheres = $where;
    
        return $this->_db->delete_all($this->_collection);
    }
    

    /**
     * 根据id取得全部字段信息
     * 为了兼容基于oracle模型里的习惯
     *
     * @param string|MongoId $id
     * @return mixed array|object
     */
    public function get_info($id, $fields = array(), $excludes = array())
    {
        return $this->loadById($id, $fields, $excludes);
        
    }
    
    /**
     * 根据id取得指定字段的信息
     * @param string|MongoId $id
     * @param string|array $fields 返回哪些字段
     * @param string|array $excludes 不返回哪些自动
     */
    public function loadById($id, $fields = array(),$excludes = array())
    {
        if ($this->_prikey == XMongo_Db::DEAFAULT_ID && !($id instanceof MongoId)) {
            $id = new MongoId($id);
        }
        $select = XMongo_Utility::select($fields,$excludes);
        $query = array($this->_prikey => $id);
        
        return $this->_db->getOne($this->_collection,$select,$query);
    }
    
    
    /**
     *  添加一条记录
     *
     *  @param  array $data
     *  @return mixed
     */
    public function add(array $data)
    {
        if (empty($data) || !$this->_dataEnough($data))
        {
            return false;
        }

        $data = $this->_valid($data);
        if (!$data)
        {
            throw new XMongo_Exception('no_valid_data');
        }
        
        return $this->_db->insert($this->_collection, $data);
    }
    /**
     * 插入一条数据 see $this->add($data);
     * @param array $data
     * @return mixed
     */
    public function insert(array $data)
    {
        return $this->add($data);
    }
    
    /**
     * 批量插入数据
     * @param array $data
     * @return mixed
     */
    public function batchInsert(array $data)
    {
        foreach ($data as &$doc){
            if (empty($doc) || !$this->_dataEnough($doc))
            {
                return false;
            }
            
            $doc = $this->_valid($doc);
            if (!$doc)
            {
                throw new XMongo_Exception('no_valid_data');
                return false;
            }
        }
        
        return $this->_db->batch_insert($this->_collection,$data);
    }
    
    /**
     * 简化更新操作
     * @param array $data
     * @param string|MongoId $id
     * @param boolean $set 默认更新模式为set
     * @return boolean
     */
    public function edit($id, array $data, $set = true)
    {
        if (empty($data) && empty($id))
        {
            return false;
        }
        
        if ($this->_prikey == XMongo_Db::DEAFAULT_ID && !($id instanceof MongoId)) {
            $id = new MongoId($id);
        }
        
        $data = $this->_valid($data);
        if (!$data)
        {
            return false;
        }
        
        $this->_db->wheres = array($this->_prikey => $id);
        if ($set) {
            $data = XMongo_Utility::set($data);
        }
        return $this->_db->update($this->_collection,$data);
        
    }
    
    /**
     * 更新操作
     * @param array $where
     * @param array $data
     * @param boolean $set
     * @return boolean
     */
    public function update(array $where, array $data, $set = true)
    {
        if (empty($data) && empty($where))
        {
            return false;
        }
    
        $data = $this->_valid($data);
        if (!$data)
        {
            return false;
        }
    
        $this->_db->wheres = $where;
        if ($set) {
            $data = XMongo_Utility::set($data);
        }
        return $this->_db->update_all($this->_collection,$data);
    
    }
    
    
    /**
     *  验证数据合法性，当只验证vrule中指定的字段，并且只当$data中设置了其值时才验证
     *
     *  @param  array $data
     *  @return mixed
     */
    protected function _valid($data)
    {
        if (empty($this->_autov) || empty($data) || !is_array($data))
        {
            return $data;
        }
        $max = $filter = $reg = $default = $valid = '';
        reset($data);
        $is_multi = (key($data) === 0 && is_array($data[0]));
        if (!$is_multi)
        {
            $data = array($data);
        }
        foreach ($this->_autov as $_k => $_v)
        {
            if (is_array($_v))
            {
                $required = (isset($_v['required']) && $_v['required']) ? true : false;
                $type  = isset($this->_autov[$_k]['type']) ? $this->_autov[$_k]['type'] : 'string';
                $min  = isset($this->_autov[$_k]['min']) ? $this->_autov[$_k]['min'] : 0;
                $max  = isset($this->_autov[$_k]['max']) ? $this->_autov[$_k]['max'] : 0;
                $filter = isset($this->_autov[$_k]['filter']) ? $this->_autov[$_k]['filter'] : '';
                $valid= isset($this->_autov[$_k]['valid']) ? $this->_autov[$_k]['valid'] : '';
                $reg  = isset($this->_autov[$_k]['reg']) ? $this->_autov[$_k]['reg'] : '';
                $default = isset($this->_autov[$_k]['default']) ? $this->_autov[$_k]['default'] : '';
            }
            else
            {
                preg_match_all('/([a-z]+)(\((\d+),(\d+)\))?/', $_v, $result);
                $type = $result[1];
                $min  = $result[3];
                $max  = $result[4];
            }
            foreach ($data as $_sk => $_sd)
            {
                $has_set = isset($data[$_sk][$_k]);
                if (!$has_set)
                {
                    continue;
                }
    
                if ($required && $data[$_sk][$_k] == '')
                {
                    $this->_error("required_field", $_k);
    
                    return false;
                }
    
                /* 运行到此，说明该字段不是必填项可以为空 */
    
                $value = $data[$_sk][$_k];
    
                /* 默认值 */
                if (!$value && $default)
                {
                    $data[$_sk][$_k] = function_exists($default) ? $default() : $default;
                    continue;
                }
    
                /* 若还是空值，则没必要往下验证长度，正则，自定义和过滤，因为其已经是一个空值了 */
                if (!$value)
                {
                    continue;
                }
    
                /* 大小|长度限制 */
                if ($type == 'string')
                {
                    $strlen = strlen($value);
                    if ($min != 0 && $strlen < $min)
                    {
                        $this->_error('autov_length_lt_min', $_k);
    
                        return false;
                    }
                    if ($max != 0 && $strlen > $max)
                    {
                        $this->_error('autov_length_gt_max', $_k);
    
                        return false;
                    }
                }
                else
                {
                    if ($min != 0 && $value < $min)
                    {
                        $this->_error('autov_value_lt_min', $_k);
    
                        return false;
                    }
                    if ($max != 0 && $value > $max)
                    {
                        $this->_error('autov_value_gt_max', $_k);
    
                        return false;
                    }
                }
    
                /* 正则 */
                if ($reg)
                {
                    if (!preg_match($reg, $value))
                    {
                        $this->_error('check_match_error', $_k);
                        return false;
                    }
                }
    
                /* 自定义验证 */
                if ($valid && function_exists($valid))
                {
                    $result = $valid($value);
                    if ($result !== true)
                    {
                        $this->_error($result);
    
                        return false;
                    }
                }
    
                /* 过滤 */
                if ($filter)
                {
                    $funs    = explode(',', $filter);
                    foreach ($funs as $fun)
                    {
                        function_exists($fun) && $value = $fun($value);
                    }
                    $data[$_sk][$_k] = $value;
                }
            }
        }
        if (!$is_multi)
        {
            $data = $data[0];
        }
    
        return $data;
    }
    
    /**
     *    获取必须的字段列表
     *
     *    @return    array
     */
    public function getRequiredFields()
    {
        $fields = array();
        if (is_array($this->_autov))
        {
            foreach ($this->_autov as $key => $value)
            {
                if (isset($value['required']) && $value['required'])
                {
                    $fields[] = $key;
                }
            }
        }
    
        return $fields;
    }
    
    /**
     *    检查数据是否足够
     *
     *    @param     array $data
     *    @return    bool[true:足够,false:不足]
     */
    protected function _dataEnough($data)
    {
        $required_fields = $this->getRequiredFields();
        if (empty($required_fields))
        {
            return true;
        }
        $is_multi = (key($data) === 0 && is_array($data[0]));
        foreach ($required_fields as $field)
        {
            if ($is_multi)
            {
                foreach ($data as $key => $value)
                {
                    if (!isset($value[$field]))
                    {
                        $this->_error('data_not_enough', $field);
    
                        return false;
                    }
                }
            }
            else
            {
                if (!isset($data[$field]))
                {
                    $this->_error('data_not_enough', $field);
    
                    return false;
                }
            }
        }
    
        return true;
    }
    
    
    /**
     * 返回坐标点附近的标注数据，结果中包含距离信息
     * 
     * 使用场景：获取某一坐标点附近的标注物
     * @param array $location
     * @param float $distance 单位：km
     * @param int $num 返回的数量
     * @param array $query 查询条件
     * @return boolean|array
     */
    public function geoNear(array $location = array(), $distance=0, $num = 10, $query = array())
    {
        $query = array('geoNear'=>$this->_collection, 'nearSphere'=>true,'query'=>$query);
        
        if (empty($location)) {
            
            throw new XMongo_Exception('location params error.', $location);
        }
        
        $query['near'] = $location;
        
        if ($distance > 0) {
            //将公里数转换为弧度
            $query['maxDistance'] = $distance / self::EARTH_RADIUS;
        }
        
        if ($num > 0) {
            $query['num'] = $num;
        }
        
        $res = $this->_db->command($query);
        
        foreach ($res['results'] as &$loc)
        {
            //将弧度转换为km公里数
            $loc['dis'] = $loc['dis'] * self::EARTH_RADIUS;
        }
        
        return $res['results'];
    }
    
    
    /**
     *    触发错误
     *
     *    @author    Garbin
     *    @param     string $errmsg
     *    @return    void
     */
    protected function _error($msg, $obj = '')
    {
        if (is_array($msg))
        {
            $this->_errors = array_merge($this->_errors, $msg);
            $this->_errnum += count($msg);
        }
        else
        {
            $this->_errors[] = compact('msg', 'obj');
            $this->_errnum++;
        }
    }
    
    /**
     *    检查是否存在错误
     *
     *    @author    Garbin
     *    @return    int
     */
    public function has_error()
    {
        return $this->_errnum;
    }
    
    /**
     *    获取错误列表
     *
     *    @author    Garbin
     *    @return    array
     */
    public function get_error()
    {
        return $this->_errors;
    }
    
}
