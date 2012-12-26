<?php
/**
 * example2.php
 * 演示从抽象类XMongo继承实现一个collection的映射，并做简单的查询
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
 * M_T 是MongoDB数据库的 t collection的映射
 * @author x
 */
class M_T extends XMongo
{
    protected $_collection = 't';
}

$t = new M_T();
$r = $t->getAll();

var_dump($r);