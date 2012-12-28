<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd">
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<link href="statics/style.css" rel="stylesheet" type="text/css">
<title>example4：XMongo工具包综合利用</title>
</head>
<body>
    <p>&nbsp;<p>
	<h1>CD集(PHP+MongoDb)</h1>
	<p>基于 <b class="red">XMongo</b> 的一个很简单的CD程序.<p>
	<h2>我的CD集</h2>

	<table border="0" cellpadding="0" cellspacing="0">
		<tr bgcolor="#f87820">
			<td><img src="statics/blank.gif" width="10" height="25"></td>
			<td class="tabhead"><img src="statics/blank.gif" width="200" height="6"><br>
			<b>艺术家</b></td>
			<td class="tabhead"><img src="statics/blank.gif" width="200" height="6"><br>
			<b>标题</b></td>
			<td class="tabhead"><img src="statics/blank.gif" width="50" height="6"><br>
			<b>年份</b></td>
			<td class="tabhead"><img src="statics/blank.gif" width="50" height="6"><br>
			<b>操作</b></td>
			<td><img src="statics/blank.gif" width="10" height="25"></td>
		</tr>
<?php
if ($list):
    $i = 0;
    foreach ($list as $doc):
?>

        <?php if ($i > 0):?>
        <tr valign="bottom">
            <td bgcolor="#ffffff" background="statics/strichel.gif" colspan="6">
                <img src="statics/blank.gif" width="1" height="1">
            </td>
        </tr>
        <?php endif;?>

		<tr valign="center">
			<td class="tabval"><img src="statics/blank.gif" width="10" height="20"></td>
			<td class="tabval"><b><?php echo $doc['author']?></b></td>
			<td class="tabval"><?php echo $doc['title']?>&nbsp;</td>
			<td class="tabval"><?php echo $doc['year']?>&nbsp;</td>
			<td class="tabval"><a onclick="return confirm('确定?');" 
			    href="index.php?action=del&id=<?php echo $doc['id']?>"><span class="red">[删除CD]</span></a>
			</td>
			<td class="tabval"></td>
		</tr>
<?php
        $i++;
    endforeach;
endif;
?>		
	</table>


	<p>&nbsp;<p>
	<h2>添加CD</h2>

	<form method="post">
		<table border="0" cellpadding="0" cellspacing="0">
			<tr>
				<td>艺术家:</td>
				<td><input type="text" size="30" name="author"></td>
			</tr>
			<tr>
				<td>标题:</td>
				<td><input type="text" size="30" name="title"></td>
			</tr>
			<tr>
				<td>年份:</td>
				<td><input type="text" size="5" name="year"></td>
			</tr>
			<tr>
				<td></td>
				<td><input type="submit" border="0" value="添加CD"></td>
			</tr>
		</table>
	</form>
</body>
</html>
