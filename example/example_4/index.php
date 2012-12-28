<?php
/**
 * example4
 * XMongo工具包综合利用
 * @author
 */
//1.将XMongo工具类库的目录设置为include_path
set_include_path(implode(PATH_SEPARATOR, array(
    realpath(dirname(dirname(dirname(__FILE__))) . '/library'),
    get_include_path(),
)));

//2.初始化数据库连接
include 'db.cfg.php';

//3.初始化数据映射模型CDs
require_once 'cds.model.php';
$cds = new CDs();

//3.POST提交处理过程
if (!empty($_POST)) {
    $data = array(
                'author' => $_POST['author'],
                'title' => $_POST['title'],
                'year' => $_POST['year'],
            );
    try {
        $cds->insert($data);
        header('location:index.php');
        exit;
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    
}

//4.删除cd处理过程
if (isset($_GET['action']) && $_GET['action'] == 'del' && isset($_GET['id']) && $_GET['id'] != '' ) {
    try {
        $cds->deleteById(trim($_GET['id']));
        header('location:index.php');
        exit;
    } catch (Exception $e) {
        echo $e->getMessage();
    }
    
}

//5.UI展现
$list = $cds->getAll();
include 'index.tpl.php';