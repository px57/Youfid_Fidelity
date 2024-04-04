<?php

if(function_exists('newrelic_set_appname'))
	newrelic_set_appname('youfid_prod');

include '_security.php';


$database = new PDO('mysql:host=db.youfid.fr;port=3306;dbname=youfid;charset=utf8', 'youfid', 'youfid');

$statement = $database->prepare("SELECT * FROM backoffice_usr WHERE login = :login AND password = :password");
$statement->execute([
	'login' => $_POST['login'],
	'password' => $_POST['password'],
]);
$checklogin = $statement->fetch(PDO::FETCH_OBJ);

if(!$checklogin)
{
	header('Location: ip-authentification-login.php?error=' . urlencode("Votre login ou votre mot de passe est incorrect"));
	exit;
}

$ip = @$_SERVER['REMOTE_ADDR'] . '_' . @$_SERVER['HTTP_X_REMOTE_IP'];

$database->prepare("
	UPDATE  `security_ip`
	SET
		`status` = :status,
		`comment` = :comment
	WHERE
		`ip` LIKE :ip
")->execute([
	'ip' => $ip,
	'status' => 'ok',
	'comment' => $_POST['login'],
]);


header("Location: ip-authentification-login.php?error=" . urlencode("Votre IP a bien été enregistrée"));

