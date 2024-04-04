<?php

if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
  exit;
}

// BE CAREFULL : LOGS CAN NOTBE INCLUDED IN PREPROD.

//require_once($_SERVER['DOCUMENT_ROOT'] . "/lib/Logger.class.php");
//require_once('Logger.class.php');

require_once('utils.php');
require_once('./Smarty/Smarty.class.php');
require_once('Logger.class.php');

yf_security_log_event('scanuserifexists');

$tbl_name="marchand_has_mobileuser";
require_once('dbLogInfo.php');

if (!isset($logger)) {
  //$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . "/dev/service/logs/");
  $logger = new Logger('logs/');
}

function doLog($message) {
  global $logger;

  if (isset($logger)) {
    $logger->log('debug', 'testmarchandCheckScanUserIfExists', $message, Logger::GRAN_MONTH);
  }
}

function utf8_converter($array) {
  array_walk_recursive($array, function(&$item, $key){
    if(!mb_detect_encoding($item, 'utf-8', true)){
      $item = utf8_encode($item);
    }
  });

  return $array;
}

////////////////////////////////////////
// Error properties
$error = false;
$errorMsg = "";
$isReg = true;

try {

  $youfidDb = new PDO("mysql:host=$host;dbname=$db_name", $username, $password);

  $json = file_get_contents('php://input');
  $headers = array();
  if(function_exists('apache_request_headers')) {
    $headers = apache_request_headers();
  } else {
    $headers = array(
      'Authorization' => $_SERVER['HTTP_AUTHORIZATION']
    );
  }

  $authorization = checkRequestAuthorization($youfidDb, $headers, $json);

  $jsonArray = json_decode($json);

  //$logger->log('debug', 'checkScanUser', "Request=" . $json, Logger::GRAN_MONTH);

  // $logger->log('debug', 'checkScanUser', "$authorization = " . json_encode($authorization), Logger::GRAN_MONTH);
  $jsonResult = [];

  $jsonResult['is_user'] = 0;
  $jsonResult['questions'] = 0;
  $jsonResult['survey'] = 0;
  $jsonResult['ask_phone'] = 1;
  $jsonResult['promo'] = 0;
  $jsonResult['gift'] = 0;


  if(isset($jsonArray->merchant_id)) {
    $query = $youfidDb->prepare("SELECT * FROM marchand WHERE `id` = :id");
    $query->execute(array(
      'id' => $jsonArray->merchant_id
    ));
 
    $rowMarchand = false;
    if($query->rowCount() === 1) {
      $rowMarchand = $query->fetch(PDO::FETCH_ASSOC);
      // doLog("rowMarchand: " . print_r($rowMarchand, true));
      // doLog("Merchant found " . $rowMarchand['name']);

      if(isSecurityActivated($rowMarchand) && (!$authorization || !$authorization->granted || $rowMarchand['id'] != $authorization->merchantId)) {
        $error = TRUE;
        $errorMsg = 'Not authorized: ' . $authorization->error;

        header("HTTP/1.1 401 Unauthorized");
        die(json_encode(
          array(
            'status' => "error",
            'message' => $errorMsg,
            'code' => 'ERR0401'
          )
        ));
      }
    } else {
      doLog("Merchant with id " . $jsonArray->merchant_id . " not found");
    }
  } else {
    header("HTTP/1.1 400 Bad request");
    die(json_encode(
      array(
        'status' => 'error',
        'message' => 'Merchant id is missing',
        'code' => 'ERR0400.1'
      )
    ));
  }

 /*
  * Check if mobileuser exists
  */
  $rowUser = get_mobile_user_by_qrcode($youfidDb, $jsonArray->qr_code, $rowMarchand, doLog);

  if(!empty($rowUser) && !empty($rowMarchand)) {
    $jsonResult['is_user'] = 1;
    $jsonResult['usr_id'] = $rowUser['id'];
  }

  /*
   * Check if marchand_has_mobileuser exists
   */
  if(isset($rowMarchand) && isset($rowUser)) {
    $query = $youfidDb->prepare(
      "SELECT DATE(creation_date) FROM marchand_has_mobileuser
      WHERE `mobileuser_id` = :user_id 
      AND `marchand_id` = :marchand_id"
    );

    $query->execute(array(
      "user_id" => $rowUser['id'],
      "marchand_id" => $rowMarchand['id']
    ));

    $rowRelation = false;
    if($query->rowCount() > 0) {
      $rowRelation = $query->fetch(PDO::FETCH_ASSOC);
    }
  }

  if($rowMarchand['ask_phone'] && strlen($rowUser['phone']) > 3 || $rowUser['phone'] == '-1') {
    $jsonResult['ask_phone'] = 0;
  }

  $user_points = get_user_points($rowUser['public_id'], $rowMarchand['application_id']);
  $jsonResult['points'] = $user_points;

  /**
   * Check if UBERISATION exists
   */
  if(isset($rowMarchand) && isset($rowUser) && $rowMarchand['survey_desk'] && $rowMarchand['supermarchand_id'] < 0) {
    $query = $youfidDb->prepare(
      "SELECT sq.id, sq.question, sr.result
       FROM survey_questions sq
       LEFT JOIN survey_results sr
       ON sr.question_id = sq.id AND sr.user_id = :user_id
       WHERE sq.marchand_id = :marchand_id"
    );

    $query->execute(array(
      "user_id" => $rowUser['id'],
      "marchand_id" => $rowMarchand['id']
    ));

    if($query->rowCount() > 0) {
      $resultQuestions = $query->fetchAll();
      $nonAnswaredQuestions = array_filter($resultQuestions, function($q) {
        return empty($q['result']);
      });

      if(count($nonAnswaredQuestions) > 0) {
        $rowQuestion = $nonAnswaredQuestions[0];
        $jsonResult['survey'] = array('id' => $rowQuestion['id'], 'question' => $rowQuestion['question']);
      }
    }
  }

  // Rotation enquete supermarchand
  if(isset($rowMarchand) && isset($rowUser) && $rowMarchand['survey_desk'] && $rowMarchand['supermarchand_id'] > 0) {

    // Get dernier passage dans reseau du super
    $query = $youfidDb->prepare(
      "SELECT tr.marchand_id, tr.transaction_date
      FROM transaction tr
      LEFT JOIN marchand m ON tr.marchand_id = m.id
      WHERE tr.mobileuser_id = :user_id
      AND m.supermarchand_id = :super_id
      ORDER BY tr.transaction_date DESC LIMIT 1"
    );
 
    $query->execute(array(
      "user_id" => $rowUser['id'],
      "super_id" => $rowMarchand['supermarchand_id']
    ));

    $rowPassage = false;
    if($query->rowCount()) {
      $rowPassage = $query->fetch(PDO::FETCH_ASSOC);
    }
 
    // Get if results since last passage
    $rowResult = false;
    if($rowPassage) {
      $query = $youfidDb->prepare(
        "SELECT *
        FROM survey_results res
        WHERE res.user_id = :user_id
        AND res.marchand_id = :marchand_id
        AND res.added > :last_trans_date
        LIMIT 1"
      );

      $query->execute(array(
        "user_id" => $rowUser['id'],
        "marchand_id" => $rowPassage['marchand_id'],
        "last_trans_date" => $rowPassage['transaction_date']
      ));

      if($query->rowCount() === 1) {
        $rowResult = $query->fetch(PDO::FETCH_ASSOC);
      }
    }

    if($rowPassage && !$rowResult) {
      $query = $youfidDb->prepare(
        "SELECT id, `name`
        FROM marchand
        WHERE id = :id"
      );
      
      $query->execute(array(
        "id" => $rowPassage['marchand_id']
      ));
      
      $rowLastMarchand = $query->fetch(PDO::FETCH_ASSOC);

      $query = $youfidDb->prepare(
        "SELECT * FROM survey_results
         WHERE  supermarchand_id = :super_id 
         AND user_id = :user_id
         AND result IS NULL"
      );

      $query->execute(array(
        "super_id" => $rowMarchand['supermarchand_id'],
        "user_id" => $rowUser['id']
      ));
      
      // doLog("\nSurvey search query: " . $query . "\n");
      // $surveyResults = mysql_query($query);
      if($query->rowCount() > 0) {
        $rowSurvey = $query->fetch(PDO::FETCH_ASSOC);
        doLog("Found non answered survey: " . print_r($rowSurvey, true));

        $query = $youfidDb->prepare("SELECT * FROM survey_questions WHERE id = :question_id");
        $query->execute(array(
          "question_id" => $rowSurvey['question_id']
        ));
        $rowQuestion = $query->fetch(PDO::FETCH_ASSOC);

        $jsonResult['survey'] = array(
          'id' => $rowQuestion['id'],
          'question' => $rowQuestion['question'],
          'marchandName'=>$rowLastMarchand['name'],
          'marchandId'=>$rowLastMarchand['id']
        );

        doLog("Survey: " . print_r($jsonResult['survey'], true));
      } else {
        // Get questions
        $query = $youfidDb->prepare(
          "SELECT sq.id, sq.question, sr.result
          FROM survey_questions sq
          LEFT JOIN survey_results sr ON sr.question_id = sq.id 
          AND sr.user_id = :user_id 
          AND sr.added > :last_transaction_date
          WHERE sq.marchand_id = :marchand_id"
        );

        $query->execute(array(
          "user_id" => $rowUser['id'],
          "last_transaction_date" => $rowPassage['transaction_date'],
          "marchand_id" => $rowLastMarchand['id']
        ));

        $questions = $query->fetchAll();

        shuffle($questions);

        foreach($questions as $rowQuestion)
        {
          if($rowQuestion['result'] == null) {
            $jsonResult['survey'] = array('id' => $rowQuestion['id'], 'question' => $rowQuestion['question'], 'marchandName'=>$rowLastMarchand['name'], 'marchandId'=>$rowLastMarchand['id']);
            break;
          }
        }
      }
    } else {
      if(empty($rowPassage) && !empty($rowUser) && !empty($rowUser['mail']) && empty($rowSurvey) && !empty($jsonResult['survey'])) {
        $surveyData = $jsonResult['survey'];
        doLog("Survey DATA: " . print_r($surveyData, true));
        // Insert survey question
        $query = $youfidDb->prepare(
          "INSERT INTO survey_results 
          VALUES(NULL, ?, ?, ?, NULL, NOW(), ?)"
        )->execute([
          $surveyData['id'],
          $rowMarchand['id'],
          $rowUser['id'],
          $rowMarchand['supermarchand_id']
        ]);

        $surveyId = $youfidDb->lastInsertId();

        // Send survey mail if user has mail defined
        $fullName = $rowUser['prenom'] . " " . $rowUser['nom'];
        $now = new DateTime('NOW');
        $now = $now->add(new DateInterval('PT3M'));
        $delayDate = $now->format('Y-m-d H:i:s');

        $smarty = new Smarty();
        $smarty->assign('surveyLink', 'http://www.youfid.fr/membres/surveys/' . $rowMarchand['id'] . '/' . $rowUser['id'] . '/' . $surveyId . "?single=true&ts=" . time());
        $smarty->assign('email', $rowUser['mail']);
        $smarty->assign('merchantName', $rowMarchand['name']);
        $smarty->assign('merchantLogo', $rowMarchand['logo']);
        $message = $smarty->fetch('./mailerdaemon2/survey.tpl');

        $surveyMailQry = "INSERT DELAYED INTO sendmail_queue (created_at, context, status, template, from_email, from_name,
        replyto_email,replyto_name, to_email, to_name, subject, message, delay_until)
        VALUES (NOW(), 'survey', 'created', 'survey', 'bienvenue@youfid.fr', 'YouFID Team',
        'contact@youfid.fr', 'YouFID Team', ?, ?,
        'Enquete de satisfaction', ?, ?)";

        $youfidDb->prepare($surveyMailQry)->execute([
          $rowUser['mail'],
          $fullName,
          $message,
          $delayDate
        ]);
      }

      $jsonResult['survey'] = 0;
    }
  }

  /*
  * Check if promo running
  */
  if(isset($rowRelation) && isset($rowMarchand) && isset($rowUser) && $rowMarchand['is_promo']) {

    $query = $youfidDb->prepare(
      "SELECT *
      FROM `message`
      WHERE `type` = 'promo'
      AND `marchand_id` = :marchand_id 
      AND DATE(NOW()) BETWEEN `start_date` AND `finish_date`
      ORDER BY finish_date DESC
      LIMIT 1"
    );
    #AND DATE(NOW()) BETWEEN `start_date` AND `finish_date`
    #AND DATE(NOW()) BETWEEN '2003-01-01' AND '2016-12-12'
    #ORDER BY finish_date DESC
    #LIMIT 1

    $query->execute(array(
      "marchand_id" => $rowMarchand['id']
    ));
    
    $rowPromo = false;
    if($query->rowCount() > 0) {
      $rowPromo = $query->fetch(PDO::FETCH_ASSOC);  
    }

    if($rowPromo && $rowRelation['creation_date'] <= $rowPromo['start_date']) {
      $jsonResult['promo'] = $rowPromo;
    }
  }

  /*
   * Checks loyalty program
   */
  if(!empty($rowMarchand) && !empty($rowMarchand['loyalty_program_id'])) {
    $lpid = $rowMarchand['loyalty_program_id'];

    // doLog("Program ID: " . $lpid);
    $loyalty_program = get_loyalty_program($youfidDb, $lpid);
    // doLog("Program: " . print_r($loyalty_program, true));
    if($loyalty_program && strtoupper($loyalty_program['program_type']) === 'DOMINOS') {
      $user_program = get_user_loyalty_program($youfidDb, $lpid, $rowUser['id']);
      $merc_gifts = get_marchant_gifts($youfidDb, $rowMarchand['id']);

      // doLog("User program: " . print_r($user_program, true));
      // doLog("Merc gifts: " . print_r($merc_gifts, true));

      if(count($merc_gifts)) {
        $next_gift = false;

        if(!empty($user_program['last_received_gift'])) {
          $lastGiftId = $user_program['last_received_gift'];
          $last_gift = array_filter($merc_gifts, function($gift) use ($lastGiftId) {
            return ($gift['id'] == $lastGiftId);
          });
          $last_gift = array_values($last_gift);
          // doLog("Last Gift: " . print_r($last_gift, true));
          
          for($i = 0; $i < count($merc_gifts); $i++) {
            $gift = $merc_gifts[$i];
            // doLog("Compare (" . $gift['id'] ."): ". $last_gift[0]['cout'] . " and " . $gift['cout']);
            if($last_gift[0]['cout'] == $gift['cout']) {
              if($i > 0) {
                $next_gift = $merc_gifts[$i - 1];
              } else {
                $next_gift = $merc_gifts[0];
              }
              break;
            }
          }

          $jsonResult['last_gift_id'] = intval($last_gift[0]['id']);
        } else {
          $next_gift = $merc_gifts[count($merc_gifts) - 1];
          $jsonResult['last_gift_id'] = 0;
        }

        // doLog("Next gift: " . print_r($next_gift, true));

        if(!empty($next_gift)) {
          $giftCost = intval($next_gift['cout']);
          if($user_points >= $giftCost) {
            $jsonResult['gift'] = array(
              "id" => $next_gift['id'],
              "name" => $next_gift['nom'],
              "cost" => $next_gift['cout'],
              "type" => $next_gift['type']
            );
          }
        }
      }
    }
  } else {
    // GET available gifts
    $giftsQuery = $youfidDb->prepare('SELECT id, nom, cout, photo FROM cadeau WHERE marchand_id = :merchantId AND cout <= :points');
    $giftsQuery->execute(array(
      'merchantId' => $rowMarchand['id'],
      'points' => $user_points
    ));
    $gifts = fetchCollection($giftsQuery, PDO::FETCH_ASSOC);
    $jsonResult['gifts'] = $gifts;
  }

  echo(json_encode(utf8_converter($jsonResult)));
  doLog("JSON Result: " . print_r($jsonResult, true));
} catch(PDOException $e) {
  die(
    json_encode(
      array(
        "status" => "error",
        "message" => "Error: " . $e->getMessage()
      )
    )
  );
}

function get_loyalty_program($pdo, $id) {
  $query = $pdo->prepare("SELECT * FROM loyalty_program WHERE id = :id");
  $query->execute(array(
    "id" => $id
  ));

  if($query->rowCount() > 0) {
    return $query->fetch(PDO::FETCH_ASSOC);
  }

  return false;
}

function get_user_loyalty_program($pdo, $lpid, $user_id) {
  $query = $pdo->prepare(
    "SELECT * FROM mobileuser_loyalty_program WHERE mobileuser_id = :user_id AND loyalty_program_id = :lpid"
  );

  $query->execute(array(
    "user_id" => $user_id,
    "lpid" => $lpid
  ));

  if($query->rowCount() > 0) {
    return $query->fetch(PDO::FETCH_ASSOC);
  }

  return false;
}

function get_marchant_gifts($pdo, $marchand_id) {
  $query = $pdo->prepare("SELECT * FROM `cadeau` WHERE `marchand_id` = :marchand_id ORDER BY `cout` DESC");
  $query->execute(array(
    "marchand_id" => $marchand_id
  ));

  if($query->rowCount()) {
    return $query->fetchAll();
  }

  return array();
}

?>