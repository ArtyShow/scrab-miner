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
td#name_group { 
    font-weight: bold;
    text-align: left; }
</STYLE>

<BODY>

<?php

$db = new SQLite3('/sqlite_bd/miners.db');
echo "<h1><b>Summary statistic:</b></h1><br>";
echo "<table border='0'>";
$count_devices = $db->querySingle('SELECT count(*) as c FROM miners_device');
echo "<tr><td>Devices:</td><td>$count_devices</td></tr>";
$count_bad_devices = $db->querySingle('SELECT count(DISTINCT d.ip) as c FROM miners_device as d 
                                       JOIN pools as p ON d.ip = p.ip JOIN asic as a ON d.ip = a.ip
                                       WHERE  d.status = 0 OR d.type = "0" OR p.status = "Dead" OR  a.status = "Dead" OR a.countX > 0 OR d.last_check != d.last_success_check');
$count_good_devices = $count_devices - $count_bad_devices;
echo "<tr><td>Good work devices:</td><td>$count_good_devices</td></tr>";
echo "<tr><td>Devices with errors:</td><td>$count_bad_devices</td></tr>";
$count_off_devices = $db->querySingle('SELECT count(*) as c FROM miners_device WHERE status = 0');
echo "<tr><td>Offline devices:</td><td>$count_off_devices</td></tr>";
$count_notsupp_devices = $db->querySingle('SELECT count(*) as c FROM miners_device WHERE status > 0 AND type = 0');
echo "<tr><td>Not supported devices:</td><td>$count_notsupp_devices</td></tr>";
echo "</table>";
echo "<br>";
echo "<a href='/edit.php?devices'>Добавление устройства</a>";
echo "<br>";
echo "<a href='/edit.php?groups'>Редактирование групп</a>";
echo "<br>";

echo "<TABLE border='1' >";
echo "<tr><th>IP адрес</th><th>Пользователь</th><th>Тип</th><th>Summary GH/S 5s</th><th>Summary GH/S avg</th>
      <th>Время работы</th><th>Время проверки</th><th>Подробнее</th></tr>";

$devices = $db->query('SELECT * FROM miners_device ORDER BY device_group ASC');
$groups_code = -1;

while ($device = $devices->fetchArray()) {
    if ($groups_code != $device['device_group']){
        $groups_code = $device['device_group'];
        if($groups_code == 0){
            $name_group = "Default";
        }else{
            $name_group = $db->querySingle("SELECT name FROM group_of_devices WHERE rowid='".$groups_code."'");
        }
        echo "<tr><td id='name_group' colspan='8'>$name_group</td></tr>";
    }
    $flag_error = 0;
    $str_table = "<td><a href='http://".$device['ip']."' target='_blank'>".$device['ip']."</a></td>";
    $pools = $db->query("SELECT * FROM pools WHERE ip='".$device['ip']."'");
    $pools_device = $pools->fetchArray();
    $pools_user = $pools_device['user'];
    $unique_pools_user = $pools_user;
    $pools_url = $pools_device['url'];
    $pools_status = $pools_device['status'];
    if ($pools_device['status'] == 'Dead'){
        $flag_error = 1;
    }
    while ($pools_device = $pools->fetchArray()){
        if (strcasecmp($unique_pools_user, $pools_device['user']) != 0 && strpos($unique_pools_user, $pools_device['user']) === false) {
            $unique_pools_user .= "<br>".$pools_device['user'];
        }
        $pools_user .= "<br>".$pools_device['user'];
        $pools_url .= "<br>".$pools_device['url'];
        $pools_status .= "<br>".$pools_device['status'];
        if ($pools_device['status'] == 'Dead'){
            $flag_error = 1;
        }
    }
    $str_table .= "<td>$unique_pools_user</td><td>".$device['minertype']."</td><td>".$device['ghs5s']."</td><td>".$device['ghsavg']."</td>";
    $str_table_pools = "<td>$pools_user</td><td>$pools_url</td><td>$pools_status</td>";
    $asics = $db->query("SELECT * FROM asic WHERE ip='".$device['ip']."'");
    $asics_device = $asics->fetchArray();
    $asics_chain = $asics_device['chain'];
    $asics_MHS = $asics_device['MHS'];
    $asics_ASIC = $asics_device['ASIC'];
    $asics_tmin = $asics_device['t_min'];
    $asics_tmax = $asics_device['t_max'];
    $asics_tavg = $asics_device['t_avg'];
    $asics_status = $asics_device['status'];
    if ($asics_device['status'] == 'Dead'){
        $flag_error = 1;
    }
    $asics_tPCB = $asics_device['t_PCB'];
    $asics_tchip = $asics_device['t_chip'];
    $asics_x = $asics_device['countX'];
    if ($asics_device['countX'] > 0){
        $flag_error = 1;
    }
    $asics_o = $asics_device['countO'];
    while ($asics_device = $asics->fetchArray()){
        $asics_chain .= "/".$asics_device['chain'];
        $asics_MHS .= "/".$asics_device['MHS'];
        $asics_ASIC .= "/".$asics_device['ASIC'];
        $asics_tmin .= "/".$asics_device['t_min'];
        $asics_tmax .= "/".$asics_device['t_max'];
        $asics_tavg .= "/".$asics_device['t_avg'];
        $asics_status .= "/".$asics_device['status'];
        if ($asics_device['status'] == 'Dead'){
            $flag_error = 1;
        }
        $asics_tPCB .= "/".$asics_device['t_PCB'];
        $asics_tchip .= "/".$asics_device['t_chip'];
        $asics_x .= "/".$asics_device['countX'];
        if ($asics_device['countX'] > 0){
            $flag_error = 1;
        }
        $asics_o .= "/".$asics_device['countO'];
    }
    $str_table_chains = "<td>$asics_chain</td><td>$asics_MHS</td><td>$asics_ASIC</td><td>$asics_tmax</td><td>$asics_status</td><td>$asics_tPCB</td><td>$asics_tchip</td><td>$asics_o</td><td>$asics_x</td>";
    $fans = $db->query("SELECT * FROM fans WHERE ip='".$device['ip']."'");
    $fan_speed = $fans->fetchArray()['speed'];
    while ($fans_device = $fans->fetchArray()){
        $fan_speed .= "/".$fans_device['speed'];
    }
    $str_table .= "<td>".$device['uptime']."</td><td>".$device['last_success_check']."</td>";
    if($device['last_check'] != $device['last_success_check']){
        $flag_error = 1;
    }
    $str_table_chains .= "<td>$fan_speed</td>";
    $str_table .= "<td><input type='button' id='btn_".str_replace('.','_',$device['ip'])."' value='Подробнее' data-selector='".str_replace('.','_',$device['ip'])."' ></td>";

    if ($device['type'] == 0 or $device['status'] == 0){
            $flag_error = 2;
    }

    if($flag_error == 0){
        echo "<tr>$str_table</tr>";
    }elseif($flag_error == 2){
        echo "<tr bgcolor='red'>$str_table</tr>";
    }else{
        echo "<tr bgcolor='orange'>$str_table</tr>";
    }
    echo "<tr style='display: none;' id='row_".str_replace('.','_',$device['ip'])."' ><td colspan='8'>";
    echo "<table border='1'><tr id='device_info_pools'><th colspan='3'>Pools</th></tr><tr><th>User</th><th>URL pool</th><th>Status pool</th></tr><tr>".$str_table_pools."</tr></table><br>";
    echo "<table border='1'><tr id='device_info_miner'><th colspan='10'>Miner</th></tr><tr><th>chain</th><th>MHS</th><th>ASIC</th><th>t_max</th><th>status</th><th>t PCB</th><th>t chip</th><th>O</th><th>X</th><th>Fans</th></tr><tr>".$str_table_chains."</tr></table><br>";
    echo "<a href='edit.php?devices=".$device['ip']."'>Изменить</a><br>";
    echo "<a href='edit.php?devices=".$device['ip']."&del'>Удалить</a>";
    echo "</td></tr>";
}


$db->close();
?>
</TABLE>
</BODY>
</HTML>


