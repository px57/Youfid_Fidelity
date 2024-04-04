<?php
require_once(dirname(__FILE__) . "/../include/database.class.php");
require_once(dirname(__FILE__) . "/../include/session.class.php");
$session = new Session();
 

require_once("db_functions.php");
require_once ('push_message.php');
require_once("../dev/service/utils.php");
require_once("Logger.class.php");
require_once("../dev/service/dbLogInfo.php");
$tbl_name = "marchand_has_mobileuser";

if (!isset($logger))
		$logger = new Logger($_SERVER['DOCUMENT_ROOT'] . "/logs/debug/");
		
$logger->log('debug', 'send-promos.php', "GetIn::sendPromos", Logger::GRAN_MONTH);

//// Login ////
/* $login_url = $url_loyalty . 'services/user/login';
  $json_login = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "login" : "youfid", "password":"youfid"}';
  $result =  postRequest($login_url, $json_login);
  $loginResult = json_decode($result, true); */
$loginResult['wsAccess']['wsAccessToken'] = '7e17880d34734a43b83848f76b1452b3';

	///////////
	/// Connection to the db
	mysql_connect("$host", "$username", "$password")or die("cannot connect");
	mysql_select_db("$db_name")or die("cannot select DB");
	$marchand_id= $_SESSION['selector'];

		$timestart=microtime(true);
		$logger->log('debug', 'send-promos.php', "GetIn::linkMessageToUsers", Logger::GRAN_MONTH);
	
	$i = 0;
	$userArray = array();

		$testUse = "Select * from marchand_has_mobileuser mhm JOIN mobileuser m WHERE mhm.mobileuser_id = m.id AND mhm.nb_use > 0 && mhm.marchand_id = $marchand_id";
		$resultUse = mysql_query($testUse);
		while ($row = mysql_fetch_array($resultUse)) {
			$userArray[$i] = $row['mobileuser_id'];
			$i += 1;
		}
	
			$timeend1=microtime(true);
			$time=$timeend1-$timestart;	
			$page_load_time = number_format($time, 3);
			$logger->log('debug', 'send-promos.php', "GetOut::AfterFirstSelect (= ". $testUse . ")::ProcessedIn:: " . $page_load_time . " seconds", Logger::GRAN_MONTH);
			

$arrayRem['titre'] = $_POST['titre'];
$arrayRem['contenu'] = $_POST['contenu'];
$arrayRem['date_debut'] = $_POST['date_debut'];
$arrayRem['date_fin'] = $_POST['date_fin'];
$arrayRem['fidelity'] = 0;
$arrayRem['fid'] = $_POST['fid'];
$arrayRem['last_authent'] = 0;
$arrayRem['combien'] = $_POST['combien'];
$arrayRem['chrono'] = $_POST['chrono'];
$arrayRem['min_points'] = 0;
$arrayRem['points'] = $_POST['points'];
$arrayRem['ancient'] = 0;
$arrayRem['nb_ancient'] = $_POST['nb_ancient'];
$arrayRem['next_present'] = 0;
$arrayRem['next_cadeau'] = $_POST['next_cadeau'];

$arrayRem['scanloc'] = 0;
$arrayRem['localisation'] = $_POST['localisation'];
$arrayRem['time'] = 0;
$arrayRem['combien'] = $_POST['combien'];
$arrayRem['chrono'] = $_POST['chrono'];

$i = 0;
while ($userArray[$i]) {
    $sqlGetCustomer = "SELECT * FROM $tbl_name WHERE `mobileuser_id` = '"
            . mysql_real_escape_string($userArray[$i])
            . "' && `marchand_id` = '"
            . mysql_real_escape_string($marchand_id)
            . "'";
    $result2 = mysql_query($sqlGetCustomer);
    $rowNb = mysql_num_rows($result2);
    if ($rowNb < 1) {
        unset($userArray[$i]);
    }
    $i += 1;
}
$userArray = array_values($userArray);

if (isset($_POST['fidelity'])) {
    $arrayRem['fidelity'] = 1;
    if (!ctype_digit($_POST['fid']) || intval($_POST['fid']) <= 0) {
        $_SESSION['sent'] = 2;
        header("location:../marchand_promos.php");
    }
    $i = 0;
    $sqlFidel = "Select * from $tbl_name where `marchand_id` = '"
            . mysql_real_escape_string($marchand_id)
            . "' && `nb_use` > 0"
            . " ORDER BY `nb_use` DESC LIMIT "
            . mysql_real_escape_string($_POST['fid']);
    $result2 = mysql_query($sqlFidel);
    while ($row2 = mysql_fetch_array($result2)) {
        $userOK[$i] = $row2['mobileuser_id'];
        $i += 1;
    }
    $i = 0;
    $j = 0;
    $newArray = array();
    while ($userOK[$i]) {
        if (in_array($userOK[$i], $userArray)) {
            $newArray[$j] = $userOK[$i];
            $j += 1;
        }
        $i += 1;
    }
    $userArray = array_values($newArray);
}


if (isset($_POST['scanloc'])) {
    $arrayRem['scanloc'] = 1;
    $sqlLoc = "Select id from marchand where zip_code = '"
            . mysql_real_escape_string($_POST['localisation'])
            . "'";
    $resultmarchand = mysql_query($sqlLoc);
    $a = 0;
    while ($rowzip = mysql_fetch_array($resultmarchand)) {
        $marchandOK[$a] = $rowzip['id'];
        $a += 1;
    }
    $i = 0;
    while ($userArray[$i]) {
        $userOK = FALSE;
        $j = 0;
        while ($marchandOK[$j]) {
            $sqlGetCustomer = "SELECT * FROM $tbl_name WHERE `mobileuser_id` = '"
                    . mysql_real_escape_string($userArray[$i])
                    . "' && `marchand_id` = '"
                    . mysql_real_escape_string($marchandOK[$j])
                    . "'";
            $result2 = mysql_query($sqlGetCustomer);
            $rowNb = mysql_num_rows($result2);
            if ($rowNb >= 1) {
                $userOK = TRUE;
                break;
            }
            $j += 1;
        }
        if ($userOK == FALSE) {
            unset($userArray[$i]);
        }
        $i += 1;
    }

    $userArray = array_values($userArray);
}


if (isset($_POST['time'])) {
    $arrayRem['time'] = 1;
    $mult = 1;
    if ($_POST['chrono'] == "Semaines")
        $mult = 7;
    else if ($_POST['chrono'] == "Mois") {
        $mult = 30;
    } elseif ($_POST['chrono'] == "Années") {
        $mult = 365;
    }
    $last = intval($_POST['combien']);
    $maxdays = $last * $mult;
    $i = 0;
    while ($userArray[$i]) {
        $sqlGetCustomer = "SELECT * FROM authentification WHERE `mobileuser_id` = '"
                . mysql_real_escape_string($userArray[$i])
                . "' AND `marchand_id` = '"
                . mysql_real_escape_string($marchand_id)
                . "' AND TO_DAYS(NOW()) - TO_DAYS(`authent_date`) <= "
                . mysql_real_escape_string($maxdays);

        $result2 = mysql_query($sqlGetCustomer);
        $rowNb = mysql_num_rows($result2);
        if ($rowNb < 1) {
            unset($userArray[$i]);
        }
        $i += 1;
    }
    $userArray = array_values($userArray);
}

if (isset($_POST['last_authent'])) {
    $arrayRem['last_authent'] = 1;
    $mult = 1;
    if ($_POST['chrono'] == "Semaines")
        $mult = 7;
    else if ($_POST['chrono'] == "Mois") {
        $mult = 30;
    } elseif ($_POST['chrono'] == "Années") {
        $mult = 365;
    }
    $last = intval($_POST['combien']);
    $nbdays = $last * $mult;
    $i = 0;
    while ($userArray[$i]) {
        $lastAuth = "Select * from authentification  WHERE `mobileuser_id` = '"
                . mysql_real_escape_string($userArray[$i])
                . "' && `marchand_id` = '"
                . mysql_real_escape_string($marchand_id)
                . "' && TO_DAYS(NOW()) - TO_DAYS(`authent_date`) >= '"
                . mysql_real_escape_string($nbdays)
                . "'";
        $result2 = mysql_query($lastAuth);
        $rowNb = mysql_num_rows($result2);
        if ($rowNb < 1) {
            unset($userArray[$i]);
        }
        $i += 1;
    }
    $userArray = array_values($userArray);
}

if (isset($_POST['min_points'])) {
    $arrayRem['min_points'] = 1;
    if (!ctype_digit($_POST['points']) || intval($_POST['points']) <= 0) {
        $_SESSION['sent'] = 2;
        header("location:../marchand_promos.php");
    }
    $i = 0;
    $sqlMarchand = "SELECT * FROM marchand WHERE id = "
            . mysql_real_escape_string($marchand_id);
    $resultMarchand = mysql_query($sqlMarchand);
    $rowMarchand = mysql_fetch_array($resultMarchand);
    while ($userArray[$i]) {
        $getPubId = "Select * from mobileuser where id ="
                . mysql_real_escape_string($userArray[$i]);
        $result2 = mysql_query($getPubId);
        $row2 = mysql_fetch_array($result2);

        $pts_url = $url_loyalty . 'services/mobileuser/mobiuserapp';
        $json_pts = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "wsAccessToken" : "'
                . $loginResult['wsAccess']['wsAccessToken'] . '", "mobileUserPublicId":"'
                . $row2['public_id'] . '", "applicationPublicId":"' . $rowMarchand['application_id'] . '"}';
        $resultPts = postRequest($pts_url, $json_pts);
        $ptsResult = json_decode($resultPts, true);
        $ptsResult = json_decode($resultPts, true);
        $userPts = $ptsResult['mobileUserApplication']['totalPoints'];
        if ($_POST['points'] > $userPts) {
            unset($userArray[$i]);
        }
        $i += 1;
    }
    $userArray = array_values($userArray);
}


if (isset($_POST['ancient'])) {
    $arrayRem['ancient'] = 1;
    if (!ctype_digit($_POST['nb_ancient']) || intval($_POST['nb_ancient']) <= 0) {
        $_SESSION['sent'] = 2;
        header("location:../marchand_promos.php");
    }
    $i = 0;
    $j = 0;
    $nb_passed = 0;
    $sqlAncient = "SELECT * FROM mobileuser ORDER BY `date_inscription` ASC";
    $result = mysql_query($sqlAncient);
    while (($row2 = mysql_fetch_array($result)) && $nb_passed < intval($_POST['nb_ancient'])) {
        $sqlGetCustomer = "SELECT * FROM $tbl_name WHERE `mobileuser_id` = '"
                . mysql_real_escape_string($row2['id'])
                . "' && `marchand_id` = '"
                . mysql_real_escape_string($marchand_id)
                . "'";
        $result2 = mysql_query($sqlGetCustomer);
        $rowNb = mysql_num_rows($result2);
        if ($rowNb >= 1) {
            if (in_array($row2['id'], $userArray)) {
                $newArray[$j] = $row2['id'];
                $j += 1;
            }
            $nb_passed += 1;
        }
    }
    $userArray = array_values($newArray);
}


if (isset($_POST['next_present'])) {
    $arrayRem['next_present'] = 1;
    $sqlMarchand = "SELECT * FROM marchand WHERE id = '"
            . mysql_real_escape_string($marchand_id)
            . "'";
    $resultMarchand = mysql_query($sqlMarchand);
    $rowMarchand = mysql_fetch_array($resultMarchand);
    if ($_POST['next_cadeau'] == "Tous les cadeaux") {
        $next_present = "Select * from cadeau where marchand_id ='"
                . mysql_real_escape_string($marchand_id)
                . "'";
    } else {
        $next_present = "Select * from cadeau where marchand_id ='"
                . mysql_real_escape_string($marchand_id)
                . "' && `nom` = '"
                . mysql_real_escape_string($_POST['next_cadeau'])
                . "'";
    }
    $result = mysql_query($next_present);
    $ValidUser = array();
    while ($rowCadeau = mysql_fetch_array($result)) {
        $pts_needed = $rowCadeau['cout'];
        $i = 0;
        if ($rowMarchand['is_accueil_client'] == '1') {
            while ($userArray[$i]) {
                $getPubId = "Select * from mobileuser where id ="
                        . mysql_real_escape_string($userArray[$i]);
                $result2 = mysql_query($getPubId);
                $rowUser = mysql_fetch_array($result2);

                $pts_url = $url_loyalty . 'services/mobileuser/mobiuserapp';
                $json_pts = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "wsAccessToken" : "'
                        . $loginResult['wsAccess']['wsAccessToken'] . '", "mobileUserPublicId":"'
                        . $rowUser['public_id'] . '", "applicationPublicId":"' . $rowMarchand['application_id'] . '"}';
                $resultPts = postRequest($pts_url, $json_pts);
                $ptsResult = json_decode($resultPts, true);
                $ptsUser = $ptsResult['mobileUserApplication']['totalPoints'];
                $nextPtsUser = $ptsUser + $rowMarchand['points_for_accueil'];
                $totest = $nextPtsUser - $pts_needed;
                if (!($totest < 0 || $totest >= $rowMarchand['points_for_accueil'])) {
                    array_push($ValidUser, $userArray[$i]);
                }
                $i += 1;
            }
        } else {
            while ($userArray[$i]) {
                $getPubId = "Select * from mobileuser where id ="
                        . mysql_real_escape_string($userArray[$i]);
                $result2 = mysql_query($getPubId);
                $rowUser = mysql_fetch_array($result2);

                $pts_url = $url_loyalty . 'services/mobileuser/mobiuserapp';
                $json_pts = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "wsAccessToken" : "'
                        . $loginResult['wsAccess']['wsAccessToken'] . '", "mobileUserPublicId":"'
                        . $rowUser['public_id'] . '", "applicationPublicId":"' . $rowMarchand['application_id'] . '"}';
                $resultPts = postRequest($pts_url, $json_pts);
                $ptsResult = json_decode($resultPts, true);
                $ptsUser = $ptsResult['mobileUserApplication']['totalPoints'];

                $queryMoyenne = "SELECT AVG(value) FROM transaction WHERE mobileuser_id = '"
                        . mysql_real_escape_string($userArray[$i])
                        . "' && marchand_id ='"
                        . mysql_real_escape_string($marchand_id)
                        . "'";
                $moyenne1 = mysql_query($queryMoyenne);
                $moyenne = mysql_result($moyenne1, 0);

                $ptsMoyen = intval($moyenne);
                $nextPtsUser = $ptsUser + $ptsMoyen;
                $totest = $nextPtsUser - $pts_needed;
                if (!($totest < 0 || $totest >= $ptsMoyen)) {
                    array_push($ValidUser, $userArray[$i]);
                }

                $i += 1;
            }
        }
    }
    $h = 0;
    while ($userArray[$h]) {
        if (!in_array($userArray[$h], $ValidUser)) {
            unset($userArray[$h]);
        }
        $h += 1;
    }

    $userArray = array_values($userArray);
}

$_SESSION['remember'] = $arrayRem;
$_SESSION['nb_client'] = $userArray;

$_SESSION['sent'] = 1;
$i = 0;
$valid = '0';
if ($_SESSION['role'] == "youfid_master") {
    $valid = '1';
} else {
    // Envoi mail to admin pour le prevenir qu'il a un nouveau message à valider
    $m_sql = 'SELECT * FROM marchand WHERE id = ' . $marchand_id;
    $m_result = mysql_query($m_sql);
    $m_name = "Merchant";
    while ($m_row = mysql_fetch_array($m_result)) {
        $m_name = $m_row['name'];
    }

    $m_message = "";
    if (isset($_POST['titre']) && isset($_POST['contenu']) && !empty($_POST['titre']) && !empty($_POST['contenu'])) {
        $m_message = $_POST['titre'] . " - " . $_POST['contenu'];
    }

    $m_admin = 'rlaib@youfid.fr';
    $m_title = 'Nouvelle campagne promotionnelle à vérifier';
    $m_url = 'http://backoffice.youfid.fr/youfid_master_validationmes.php';
    $m_body = $m_name . ' a soumis une nouvelle campagne promotionelle. <br/>Le message est le suivant : "' . $m_message . '". <br/>Vous pouvez gérer la validation des campagnes promotionelles à l\'adresse suivante: ' . $m_url;

    mail_youfid($m_admin, $m_title, $m_body);
}

//mysql_query("SET NAMES utf8");
$insertPromo = " Insert into message SET marchand_id='"
        . mysql_real_escape_string($marchand_id)
        . "', type='"
        . mysql_real_escape_string("promo")
        . "', message='"
        . mysql_real_escape_string($_POST['titre'])
        . "', detail='"
        . mysql_real_escape_string($_POST['contenu'])
        . "', start_date='"
        . mysql_real_escape_string(sql_date_format($_POST['date_debut']))
        . "', finish_date='"
        . mysql_real_escape_string(sql_date_format($_POST['date_fin']))
        . "', is_validated='"
        . mysql_real_escape_string($valid)
        . "'";
mysql_query($insertPromo);
$idPromo = mysql_insert_id();
while ($userArray[$i]) {
    $insertPro = "  INSERT into message_has_mobileuser SET message_id='"
            . mysql_real_escape_string($idPromo)
            . "', mobileuser_id='"
            . mysql_real_escape_string($userArray[$i])
            . "', date_creation=NOW()";
    $result = mysql_query($insertPro);
    //push

    $i += 1;
}

$today = date('Y-m-d');
$getMarchandId = "Select * from message WHERE id='" . $idPromo . "' && start_date <= '$today' && finish_date >= '$today'";
$marchandResult = mysql_query($getMarchandId);

$logger->log('debug', 'send-promos.php', "SQL get marchand id = :: " . $getMarchandId . " and num rows = " . mysql_num_rows($marchandResult), Logger::GRAN_MONTH);

if ($_SESSION['role'] == "youfid_master" && !empty($idPromo)) {
    $rowTab = array();
    $bigtab = array();
    $rowTab['id_msg'] = $idPromo;
    $rowTab['id_marchand'] = $marchand_id;
    $rowTab['id_users'] = $userArray;
    array_push($bigtab, $rowTab);
    //send_push_msg($bigtab);
    $push_content = serialize($bigtab);
	$query = "INSERT INTO `push_cron` VALUES (NULL, '"
				     . mysql_real_escape_string($push_content) 
				     . "')";
				mysql_query($query);
}
//<script> alert("IN"); </script>
//header("location:../marchand_promos.php");
echo '{"result" : "La promo a été envoyée avec succès"}';
?>
