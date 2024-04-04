<?php

require_once '_security.php';

/**********  Get User From QR CODE *************/
/*
   Permet d'obtenir l'ensemble des informations d'un user à partir de son QR code
   -> utilisé pour digitaliser une carte physique sur l'application mobile.

  request : {"qr_code":"23435674"}
  response : {
    "status":"ok",
    "usr_id":"143",
    "mail":"alexcrouan@gmail.com",
    "prenom":"alex",
    "nom":"crouan"
  }
*/

die(json_encode(
  array(
    'status' => 'error',
    'message' => 'QR Transfert bloqué'
  )
));

$json = file_get_contents('php://input');
$obj = json_decode($json);

if (!isset($obj)) {
  echo json_encode(array('status'=>'error','message'=>"Json input missing"));
  return;
}

if (!isset($obj->{'qr_code'})) {
  echo json_encode(array('status'=>'error','message'=>"qr_code missing"));
  return;
}

// Objects
$qr_code = $obj->{'qr_code'};

if (substr($qr_code, 0, 1) == "1") {
  echo json_encode(array('status'=>'error','message'=>"app account"));
  return;
}

if (!is_numeric($qr_code)) {
  echo json_encode(array('status'=>'error','message'=>"qr_code mis formated"));
  return;
}

// SQL
$host="db.youfid.fr";
$username="youfid";
$password="youfid";
$db_name="youfid";

mysql_connect($host, $username, $password)or die('{"status":"error", "message":"cannot connect to DB"}');
mysql_select_db("$db_name") or die('{"status":"error", "message":"cannot select DB"}');



// Obtention des informations
$sql = "SELECT * FROM `mobileuser` WHERE `qr_code` = '" . $qr_code . "'";
$result = mysql_query($sql);

$row = mysql_fetch_assoc($result);

if (!$row) {
  echo json_encode(array('status'=>'error','message'=>"not existing"));
  return;
}

if(!$row['birthdate'])
  $row['birthdate'] = '2000-01-01';

echo json_encode(array(
  'status' => 'ok',
  'usr_id' => $row['id'],
  'mail' => $row['mail'],
  'email' => $row['mail'],
  'first_name' => $row['prenom'],
  'prenom' => $row['prenom'],
  'last_name' => $row['nom'],
  'nom' => $row['nom'],
  'birthdate' => date('Y-m-d', strtotime($row['birthdate'])),
));
