<?php
/**
 * example3.php
 * 综合利用XMongo、XMongo_Db、XMongo_Utility
 */
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(dirname(dirname(__FILE__)) . '/library'),
    get_include_path(),
)));
require_once 'XMongo.php';
//$config = 'mongodb://localhost/test';
$config = include 'config.inc.php';
XMongo_Db::getInstance($config);

/**
 * M_T3 是MongoDB数据库的 t collection的映射
 * @author x
 */
class M_T3 extends XMongo
{
    protected $_collection = 't3';
    /**
     * 查询出80后
     */
    public function get80hou()
    {
        //1.计算80后当前的年龄段
        $maxAge = intval((time()-mktime(0,0,0,1,1,1980))/(86400*365));
        $minAge = intval((time()-mktime(0,0,0,1,1,1990))/(86400*365));
        //2.组装条件（年龄在$minAge~$maxAge之间）
        $where = XMongo_Utility::where_between('age', $minAge,$maxAge);
        //3.查询
        $r = $this->getAll('*',$where);
        return $r;
    }
    
    /**
     * 批量插入测试数据
     */
    public function testData()
    {
        //drop掉当前collection
        $this->deleteAll();
        
        $data = array(
                    array('username'=>'jc','age'=>59),
                    array('username'=>'jl','age'=>50),
                    array('username'=>'xonze','age'=>29),
                    array('username'=>'sg','age'=>25),
                );
        //批量插入数据
        $this->batchInsert($data);
    }   
    
}

$t = new M_T3();
$t->testData();
$r = $t->get80hou();

var_dump($r);