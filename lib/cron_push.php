<?php

// CRON push - permet de traiter les envois de pushs en background (depuis la page d'envoi de promos du back-office, appellé depuis lib/calc_promo2.php et lib/send-promo.php

include(dirname(__FILE__) . "/push_message.php");

mysql_connect("$host", "$username", "$password")or die("cannot connect");
mysql_select_db("$db_name")or die("cannot select DB");

$result = mysql_query("SELECT * FROM `push_cron` LIMIT 1");

if(mysql_num_rows($result) == 1) {
        $data = mysql_fetch_assoc($result);
        //print_r($data);
        $serialized = $data['data'];
        $unserialized = unserialize($serialized);
        send_push_msg($unserialized);
        mysql_query("DELETE FROM `push_cron` WHERE `id` = " . $data['id']);
}

?>
