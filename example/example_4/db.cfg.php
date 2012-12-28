<?php
//---修改本文件请务必小心!并做好相应备份---
$config = array(
        //'server' => 'mongodb://192.168.20.60:27017,192.168.20.60:27018',
        'server'  => 'localhost',
        'dbname'  => 'example4',
        'options' => array(
                'timeout'    => 10,
                'connect'    => true,   //生产环境建议false
                'replicaSet' => false,  //是否部署了副本集
                'slaveOkay'  => false,  //自动读写分离，前提是使用了副本集replicaSet = true，
                'safe'       => true,
        ),
);

//初始化数据库连接
require_once 'XMongo.php';
try {
    XMongo_Db::getInstance($config);
} catch (XMongo_Exception $e) {
    echo '<pre>';
    echo $e;
    echo '</pre>';
    exit;
}