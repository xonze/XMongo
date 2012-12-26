<?php

//---修改本文件请务必小心!并做好相应备份---
return array(
        //'server' => 'mongodb://192.168.20.60:27017,192.168.20.60:27018',
    	'server'  => 'localhost',
        'dbname'  => 'test',
        'options' => array(
                'timeout'    => 10,
                'connect'    => true,   //生产环境建议false
                'replicaSet' => false,  //指定副本集名称
                'slaveOkay'  => false,  //自动读写分离，前提是使用了副本集replicaSet = true，
                'safe'       => true,
            ),
);
