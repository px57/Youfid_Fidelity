<?php

/* Clear IPs */

$db = new PDO('mysql:host=db.youfid.fr;port=3306;dbname=youfid;charset=utf8', 'youfid', 'youfid');

# removes unknown and blocked older than 48h

$sometimeago = time() - ( 48 * 3600 );
$sometimeago = date('Y-m-d H:i:s', $sometimeago);

$db->prepare(" DELETE FROM `security_ip` WHERE `status` = 'unknown' AND `blocked_at` < :sometimeago ")
   ->execute(['sometimeago' => $sometimeago]);

# removes ok older than 12h

$sometimeago = time() - ( 12 * 3600 );
$sometimeago = date('Y-m-d H:i:s', $sometimeago);

$db->prepare(" DELETE FROM `security_ip` WHERE `status` = 'ok' AND `updated_at` < :sometimeago ")
   ->execute(['sometimeago' => $sometimeago]);

# reset all count

$db->prepare(" UPDATE `security_ip` SET `count` = 0 ")
   ->execute(['ip' => $yf_security_instance->ip]);
