<?xml version="1.0" encoding="utf-8"?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<HTML>
<HEAD>
<TITLE>Statistic for miner</TITLE>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
<script type="text/javascript" src="js/jquery.min.js"></script>
<script type="text/javascript">

$( document ).ready(function(){
    $('input').click(function() {
       var selector = $(this).data('selector');
       var value = $("input#btn_"+selector).val();
       if(value == "Подробнее"){
           $("input#btn_"+selector).val("Скрыть");
       }else{
           $("input#btn_"+selector).val("Подробнее");
       }
       $("tr#row_"+selector).toggle();
    });
});
</script>
</HEAD>

<STYLE>
BODY { text-align: center; background-color: #FCF7EC;}
TD { text-align: right; }
tr#device_info_miner { text-align: left; }
tr#device_info_pools { text-align: left; }
</STYLE>

<BODY>

<?php


$db = new SQLite3('miners.db');
if (!$db) exit("Не удалось прочитать базу данных!"); 
echo "<a href='index.php'><- Назад</a><br>";

if (isset($_GET["devices"])){
    //add device
    if (isset($_POST["dev_add"]) && isset($_POST["ip_address"]) && !empty($_POST['ip_address']) && isset($_POST["login"]) && !empty($_POST['login']) && isset($_POST["password"]) && !empty($_POST['password'])) {
        if($db->querySingle("SELECT count(*) as c FROM miners_device WHERE ip='".$_POST['ip_address']."'") > 0){
            echo "<b>Error: Такое устройство уже есть</b><br>";
        }else{
            if (preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $_POST["ip_address"], $matches)) {
                if ($matches[1]>0 && $matches[1]<255 && $matches[2]>=0 && $matches[2]<255 && $matches[3]>=0 && $matches[3]<255 && $matches[4]>=0 && $matches[4]<255){
                    if(!$db->exec("INSERT INTO miners_device (ip,login,password,device_group) VALUES('".$_POST['ip_address']."','".$_POST['login']."','".$_POST['password']."','".$_POST['group_selected']."')")){
                        echo "Error SQL: Не получается добавить устройство<br>";
                    }
                }else{echo "<b>ERROR: Введен не верный ip address</b><br>";}
            }else{
                echo "<b>ERROR: Введен не верный ip address</b><br>";
            }
        }
    }
    //edit device
    if (isset($_POST["dev_edit"]) && isset($_POST["ip_address"]) && !empty($_POST['ip_address']) && isset($_POST["login"]) && !empty($_POST['login']) && isset($_POST["password"]) && !empty($_POST['password'])) {
        if (preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $_POST["ip_address"], $matches)) {
            if ($matches[1]>0 && $matches[1]<255 && $matches[2]>=0 && $matches[2]<255 && $matches[3]>=0 && $matches[3]<255 && $matches[4]>=0 && $matches[4]<255){
                if(!$db->exec("UPDATE miners_device SET ip='".$_POST['ip_address']."', login='".$_POST['login']."', password='".$_POST['password']."', device_group='".$_POST['group_selected']."' WHERE rowid='".$_POST['dev_edit']."'")){
                    echo "Error SQL: Не получается изменить устройство<br>";
                }
            }else{echo "<b>ERROR: Введен не верный ip address</b><br>";}
        }else{
            echo "<b>ERROR: Введен не верный ip address</b><br>";
        }
    }
    //delete device
    if (isset($_POST["dev_del"]) && isset($_POST["ip_address"]) && !empty($_POST['ip_address']) && isset($_POST["login"]) && !empty($_POST['login']) && isset($_POST["password"]) && !empty($_POST['password'])) {
        if (preg_match("/^(\d{1,3})\.(\d{1,3})\.(\d{1,3})\.(\d{1,3})$/", $_POST["ip_address"], $matches)) {
            if ($matches[1]>0 && $matches[1]<255 && $matches[2]>=0 && $matches[2]<255 && $matches[3]>=0 && $matches[3]<255 && $matches[4]>=0 && $matches[4]<255){
                if(!$db->exec("DELETE FROM pools WHERE ip='".$_POST['ip_address']."'; DELETE FROM asic WHERE ip='".$_POST['ip_address']."'; DELETE FROM fans WHERE ip='".$_POST['ip_address']."'")){
                    echo "Error SQL: Не получается удалить данные устройства<br>";
                }else{
                    if(!$db->exec("DELETE FROM miners_device WHERE rowid='".$_POST['dev_del']."' AND ip='".$_POST['ip_address']."'")){
                        echo "Error SQL: Не получается удалить устройство<br>";
                    }
                }
            }else{echo "<b>ERROR: Введен не верный ip address</b><br>";}
        }else{
            echo "<b>ERROR: Введен не верный ip address</b><br>";
        }
    }

    echo "<form name='devices' method='post' action='edit.php?devices'><p>";
    echo "<table border='1'><tr><th>IP address</th><th>Login</th><th>Password</th><th>Group</th><th>to do</th></tr><tr>";

    if (!empty($_GET["devices"])){
        $devices = $db->query('SELECT ip, login, password, device_group, rowid FROM miners_device WHERE ip="'.$_GET["devices"].'"');
        $device = $devices->fetchArray();
        echo "<td><input required maxlength='15' name='ip_address' value='".$device['ip']."'></td>";
        echo "<td><input required maxlength='10' name='login' value='".$device['login']."'></td>";
        echo "<td><input maxlength='10' type='password' required name='password' value='".$device['password']."'></td>";
    }else{
        echo "<td><input required maxlength='15' placeholder='Введите ip' name='ip_address'></td>";
        echo "<td><input required maxlength='10' placeholder='Введите login' name='login'></td>";
        echo "<td><input maxlength='10' type='password' required placeholder='Введите password' name='password'></td>";
    }
    echo "<td><select name='group_selected'>";
    echo "<option ";
    if (empty($_GET["devices"])){
        echo "selected ";
    }
    echo "value='0'>Default</option>";
    $groups = $db->query('SELECT rowid, name FROM group_of_devices');
    while ($group = $groups->fetchArray()) {
        echo "<option ";
        if (!empty($_GET["devices"])){
            if($group['rowid'] == $device['device_group']){echo "selected ";}
        }
        echo "value='".$group['rowid']."'>".$group['name']."</option>";
    }
    echo "</select></td>";
    if (!empty($_GET["devices"]) && !isset($_GET['del'])){
        echo "<td><input type='hidden' name='dev_edit' value='".$device['rowid']."'><input type='submit' value='Изменить'></td>";
    }elseif (!empty($_GET["devices"]) && isset($_GET['del'])){
        echo "<td><input type='hidden' name='dev_del' value='".$device['rowid']."'><input type='submit' value='Удалить'></td>";
    }else{
        echo "<td><input type='hidden' name='dev_add'><input type='submit' value='Добавить'></td>";
    }
    echo "</tr></table></form>";
}

if (isset($_GET["groups"])){

    //add group
    if (isset($_POST["group_name"]) && !empty($_POST['group_name'])) {
        if($db->querySingle("SELECT count(*) as c FROM group_of_devices WHERE name='".$_POST['group_name']."'") > 0){
            echo "<b>Error: Такая группа уже есть</b><br>";
        }else{
            if(!$db->exec("INSERT INTO group_of_devices (name) VALUES('".$_POST['group_name']."')")){
                echo "Error SQL<br>";
            }
        }
    }
    //edit group
    if (isset($_POST["group_do_edit"]) && !empty($_POST['group_do_edit']) && isset($_POST["group_name_edit"]) && !empty($_POST['group_name_edit'])) {
        if(!$db->exec("UPDATE group_of_devices SET name='".$_POST["group_name_edit"]."' WHERE rowid = ".$_POST['group_do_edit']."")){
            echo "Error SQL: can't to edit group<br>";
        }
    }
    //delete group
    if (isset($_POST["group_delete"]) && !empty($_POST['group_delete'])) {
        if(!$db->exec("UPDATE miners_device SET device_group = 0 WHERE device_group = ".$_POST['group_delete']."")){
            echo "Error SQL, can't delete devices from this group<br>";
        }
        if(!$db->exec("DELETE FROM group_of_devices WHERE rowid = ".$_POST['group_delete']."")){
            echo "Error SQL: can't delete group<br>";
        }
    }


    echo "<form name='add_group' method='post' action='edit.php?groups'><p>";
    if (isset($_POST["group_edit"])){
        $name_group = $db->querySingle("SELECT name FROM group_of_devices WHERE rowid='".$_POST['group_edit']."'");
        echo "<input type='hidden' name='group_do_edit' value='".$_POST['group_edit']."'><input value='".$name_group."' name='group_name_edit'><input type='submit' value='Изменить'></p>";
    }else{
        echo "<input placeholder='Введите название' name='group_name'><input type='submit' value='Добавить'></p>";
    }
    echo "</form>";

    echo "<table border='1'><tr><th>Название</th><th>Редактирование</th><th>Удаление</th></tr>";
    $groups = $db->query('SELECT rowid, name FROM group_of_devices');

    while ($group = $groups->fetchArray()) {
        echo "<tr><td>".$group['name']."</td>";
        echo "<td><form name='edit_group' method='post' action='edit.php?groups'><input type='hidden' name='group_edit' value='".$group['rowid']."'><input type='submit' value='Редактировать'></form></td>";
        echo "<td><form name='del_group' method='post' action='edit.php?groups'><input type='hidden' name='group_delete' value='".$group['rowid']."'><input type='submit' value='Удалить'></form></td></tr>";
    }
    echo "</table>";
}

$db->close();
?>

</BODY>
</HTML>


