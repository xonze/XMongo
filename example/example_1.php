<?php
/**
 * example1.php
 * 演示利用XMongo_Db连接MongoDB，并做简单的插入和查询
 */
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(dirname(dirname(__FILE__)) . '/library'),
    get_include_path(),
)));
require_once 'XMongo.php';
//$config = 'mongodb://localhost/test';
$config = include 'config.inc.php';
$m = XMongo_Db::getInstance($config);
$m->batch_insert('t',array(
            array('username'=>'jc','age'=>59),
            array('username'=>'jl','age'=>50),
            array('username'=>'xonze','age'=>29),
            array('username'=>'sg','age'=>25),
        ));

var_dump($m->get('t'));