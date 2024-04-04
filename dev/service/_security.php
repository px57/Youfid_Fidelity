<?php

function sendgrid_mail($from, $to, $subject, $body_html) {
	$apiKey = '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';

	$payload = [
		'personalizations' => [
			[
				'to' => [ $to ],
				'subject' => $subject
			]
		],
		'content' => [
			[ 'type' => 'text/html', 'value' => $body_html ]
		],
		'from' => $from,
		'reply_to' => $from
	];

	$json_payload = json_encode($payload);

	ob_start();
	$ch = curl_init();

	curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
	curl_setopt($ch, CURLOPT_POSTFIELDS, $json_payload);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
	curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_HTTPHEADER, [
		'Content-Type: application/json',
		'Authorization: Bearer ' . $apiKey
	]);

	$output = curl_exec($ch);
	$info = curl_getinfo($ch);

	print_r($info);

	curl_close($ch);
	ob_end_clean();

	return true;
}

function yf_security_get_ip_string()
{

	$ip = @$_SERVER['REMOTE_ADDR'] . '_' . @$_SERVER['HTTP_X_REMOTE_IP'] . '_' . @$_SERVER['HTTP_X_FORWARDED_FOR'] . '_' . @$_SERVER['HTTP_CLIENT_IP'];

	return $ip;
}

function yf_security_get_db()
{
	return new PDO('mysql:host=db.youfid.fr;port=3306;dbname=youfid;charset=utf8', 'youfid', 'youfid');
}

function yf_security_get_user($db = null)
{
	$ip = yf_security_get_ip_string();

	$db->prepare(" INSERT INTO `security_ip` (`created_at`, `ip`, `status`) VALUES (NOW(), :ip, :status) ")
	   ->execute(['ip' => $ip, 'status' => 'unknown']);

	$statement = $db->prepare(" SELECT * FROM `security_ip` WHERE `ip` LIKE :ip ");
	$statement->execute(['ip' => $ip]);

	$yf_security_user = $statement->fetch(PDO::FETCH_OBJ);

	return $yf_security_user;
}

function yf_security_get_quota_left($yf_security_user = null)
{
	$quota = 30;

	# pic du midi
	if(date('H') == '11') $quota = 90;
	if(date('H') == '12') $quota = 90;
	if(date('H') == '13') $quota = 90;
	if(date('H') == '14') $quota = 90;

	# pic du soir
	if(date('H') == '19') $quota = 90;
	if(date('H') == '20') $quota = 90;
	if(date('H') == '21') $quota = 90;
	if(date('H') == '22') $quota = 90;
	if(date('H') == '23') $quota = 90;

	if($yf_security_user->status == 'ok')
	    $quota = 200;

	$quota_left = $quota - $yf_security_user->count;

	return max(0, $quota_left);
}

function yf_security_init()
{
    // Suspension temporaire de la security_ip probleme avec la table en base de donnés.  
   return false;

    if(!$ip = yf_security_get_ip_string())
        return false;

	# avoid security if internal IPs
	foreach ([
		'176.31.111.214',  // preprod 5inq
		'163.172.105.233', // prod 5inq
		'37.187.140.82',   // prod youfid
		'178.33.234.191',  // prod youfid
		'37.187.86.156',   // loadbalancer youfid
		'37.187.162.36',   // slave youfid
		'95.131.143.228', '95.131.143.229', '95.131.143.226', '195.68.64.4', '195.68.64.2', // TheOZ
	] as $internal_ip)
		if(strstr($ip, $internal_ip))
			return false;

	# avoid security if using ip-authentification
	if(strstr(@$_SERVER['REQUEST_URI'], 'ip-authentification'))
		return false;

	$db = yf_security_get_db();
	$user = yf_security_get_user($db);

	if(!$user)
		return false;

	# kills process if blocked
	if($user->blocked_at != '0000-00-00 00:00:00')
	    exit;

	# increments counter
	$next_count = $user->count + 1;

	# recompute status
	$next_status = 'unknown';

	if(strstr($user->event_log, 'authok'))
		$next_status = 'ok';

	# ask for blocking if over quota
	if(yf_security_get_quota_left($user) == 0)
		$next_status = 'blocked';

	# ask for blocking if failed auth too many times
	if(strstr($user->event_log, 'authko-authko-authko-authko-authko-authko-authko-authko-authko-authko'))
		$next_status = 'blocked';

	# updates values
	$db->prepare(" UPDATE `security_ip` SET `count` = :next_count, `status` = :next_status, `last_request_uri` = :last_request_uri, `last_dump_server` = :last_dump_server WHERE `ip` LIKE :ip ")
       ->execute([
       		'next_count' => $next_count,
       		'next_status' => $next_status,
       		'last_request_uri' => @$_SERVER['REQUEST_URI'],
       		'last_dump_server' => json_encode($_SERVER),
       		'ip' => $user->ip,
       	]);

	# ends of security if ok
	if($next_status == 'ok')
		return true;

	# slows if unknown
	if($next_status == 'unknown')
	{
		usleep(500 * 1000); // 500ms
		return true;
	}

	$db->prepare(" UPDATE `security_ip` SET `blocked_at` = NOW() WHERE `ip` LIKE :ip ")
			->execute(['ip' => $user->ip]);

	$from = [ 'email' => 'sav@youfid.fr', 'name' => 'sav@youfid.fr' ];
	$to = [ 'email' => 'contact@youfid.fr', 'name' => 'contact@youfid.fr' ];
	$subject = 'youfid > security > IP blocked';
	$body_html = 'IP:' . $user->ip . ' has shown an agressive behaviour (' . $user->event_log . ') and will be blocked for 48h.';
	sendgrid_mail($from, $to, $subject, $body_html);

	exit; # ... because, its blocked!

}

function yf_security_log_event($event = '')
{
  // Suspension temporaire de la security_ip probleme avec la table en base de donnés.  
  return true;

    if(!yf_security_get_ip_string())
        return false;

	$yf_security_db = yf_security_get_db();
	$yf_security_user = yf_security_get_user($yf_security_db);

	$event_log = $event . '-' . $yf_security_user->event_log;
	$event_log = substr($event_log, 0, 100);

	$yf_security_db->prepare(" UPDATE `security_ip` SET `event_log` = :event_log WHERE `ip` LIKE :ip ")
	               ->execute(['event_log' => $event_log, 'ip' => yf_security_get_ip_string()]);

	return true;
}

yf_security_init();

// yf_security_log_event('test');

