<?php
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
  header("HTTP/1.1 405 Method not allowed", true, 405);
  echo(json_encode(array(
    "status" => "error",
    "message" => "Bad request: mthod not allowed, only POST method allowed"
  )));

  exit();
}

require_once 'utils.php';
require_once 'dbLogInfo.php';

$youfidDb = new PDO($youfid_pdo_connection_string, $username, $password);

$input = file_get_contents('php://input');
if(empty($input)) {
  header("HTTP/1.1 401 Bad request", true, 400);
  echo(json_encode(array(
    "status" => "error",
    "message" => "Bad request: request is empty"
  )));

  exit();
}

$request = json_decode($input);
// error_log("Withdraw request: " . print_r($request, true));

if(isset($request->merchant_id)) {
  $merchantId = $request->merchant_id;
}

if(isset($request->user_id)) {
  $userId = $request->user_id;
}

if(isset($request->qr_code)) {
  $qrCode = $request->qr_code;
}

if(isset($request->points)) {
  $points = $request->points;
}

if (empty($merchantId) || !is_numeric($merchantId)
  || (empty($userId) && empty($qrCode)) || (!empty($userId) && !is_numeric($userId))
  || empty($points) || !is_numeric($points)) {

  header("HTTP/1.1 401 Bad request", true, 400);
  echo(json_encode(array(
    "status" => "error",
    "message" => "Bad request: merchantId or userId or points missing"
  )));
  exit();
}

$points = intval($points);
if($points < 0) {
  header("HTTP/1.1 401 Bad request", true, 400);
  echo(json_encode(array(
    "status" => "error",
    "message" => "Bad request: points must be positive"
  )));
  exit();
}

try {
  if(!empty($userId)) {
    $user = getUserById($youfidDb, $userId);
    if(!$user) {
      header("HTTP/1.1 401 Bad request", true, 400);
      echo(json_encode(array(
        "status" => "error",
        "message" => "Bad request: mobile user not found"
      )));
      exit();
    }
  } else {
    $user = getUserByQrCode($youfidDb, $qrCode);
    if(!$user) {
      header("HTTP/1.1 401 Bad request", true, 400);
      echo(json_encode(array(
        "status" => "error",
        "message" => "Bad request: mobile user not found"
      )));
      exit();
    }
  }

  $merchant = getMerchant($youfidDb, $merchantId);
  if(!$merchant) {
    header("HTTP/1.1 401 Bad request", true, 400);
    echo(json_encode(array(
      "status" => "error",
      "message" => "Bad request: merchand not found"
    )));
    exit();
  }

  $loyaltyRequest = array(
    "wsAccessPublicKey" => "8293582c-1e0c-40ff-9d59-10cb18834855",
    "wsAccessToken" => "7e17880d34734a43b83848f76b1452b3",
    "mobileUserPublicId" => $user->public_id,
    "applicationPublicId" => $merchant->application_id,
    "points" => $points
  );

  $youfidDb->beginTransaction();

  $loyRawRes = postRequest($url_loyalty . "services/transaction/withdrawpoints", json_encode($loyaltyRequest));
  
  if(!empty($loyRawRes)) {
    $loyRes = json_decode($loyRawRes);
    if($loyRes->error->code === 0) {
      $loyTrans = $loyRes->transaction;
      $loyMobileUserApplication = $loyRes->mobileUserApplication;

      $query = $youfidDb->prepare(
        "INSERT INTO `transaction` (id, mobileuser_id, marchand_id, id_loyalty, `value`, amount, nb_cadeaux, transaction_date)
         VALUES(NULL, :userId, :merchandId, :loyaltyTransactionId, :points, :amount, :nbCadeaux, NOW())"
      );
      $query->execute(array(
        "userId" => $user->id,
        "merchandId" => $merchant->id,
        "loyaltyTransactionId" => $loyTrans->publicId,
        "points" => $points,
        "amount" => 0,
        "nbCadeaux" => 0
      ));

      header("HTTP/1.1 200 OK", true, 200);
      echo(json_encode(array(
        "status" => "ok",
        "usr_id" => $user->id,
        "first_name" => $user->prenom,
        "last_name" => $user->nom,
        "fid_status" => $user->fid_status,
        "total_pts" => $loyMobileUserApplication->totalPoints
      )));
    } else {
      throw new Exception("Unexpected error occured: " . implode($loyRes->error->messages, " "));
    }
  }

  $youfidDb->commit();
} catch(Exception $ex) {
  $youfidDb->rollback();
  header("HTTP/1.1 500 Server error", true, 500);
  echo(json_encode(array(
    "status" => "error",
    "message" => $ex->getMessage()
  )));
}

/**
 * Gets user by id.
 * 
 * @param $youfidDb the youfid db handler.
 * @param $userId the user id.
 */
function getUserById($youfidDb, $userId) {
  $query = $youfidDb->prepare("SELECT * FROM mobileuser WHERE id = :id");
  $query->execute(array(
    "id" => $userId
  ));

  return fetchOne($query);
}


/**
 * Gets user by QR code.
 * 
 * @param $youfidDb the youfid db handler.
 * @param $userId the user id.
 */
function getUserByQrCode($youfidDb, $qrCode) {
  $query = $youfidDb->prepare("SELECT * FROM mobileuser WHERE qr_code = :qrCode");
  $query->execute(array(
    "qrCode" => $qrCode
  ));

  return fetchOne($query);
}

/**
 * Gets merchant by id.
 * 
 * @param $youfidDb youfid db handler.
 * @param $merchantId the merchant id.
 */
function getMerchant($youfidDb, $merchantId) {
  $query = $youfidDb->prepare('SELECT * FROM marchand WHERE id = :id');
  $query->execute(array(
    "id" => intval($merchantId)
  ));

  return fetchOne($query);
}
?>