<?php

$pts_url = 'http://localhost:8080/services/mobileuser/mobiuserapp';
$json_pts = '{"wsAccessPublicKey" : "8293582c-1e0c-40ff-9d59-10cb18834855", "wsAccessToken" : "'
	    . $loginResult['wsAccess']['wsAccessToken']  . '", "mobileUserPublicId":"'
            .  $row2['public_id'] . '", "applicationPublicId":"'. $rowMarchand['application_id'] . '"}';
$resultPts =  postRequest($pts_url, $json_pts);
                                                $ptsResult = json_decode($resultPts, true);
                                                echo($resultPts);


?>
