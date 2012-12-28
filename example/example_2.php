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

/**
 * M_T 是MongoDB数据库的 t collection的映射
 * @author x
 */
class M_T extends XMongo
{
    protected $_collection = 't';
}
/**
 * 数据映射模型实例化前未曾初始化数据库连接的话，
 * 则XMongo会根据PHP的默认配置去初始化数据库连接
 */
$t = new M_T();
$r = $t->getAll();

var_dump($r);