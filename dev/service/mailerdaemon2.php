#!/usr/bin/php -q
<?php

error_reporting(E_STRICT);

define('DB_HOST','db.youfid.fr');
define('DB_PORT','3306');
define('DB_NAME','youfid');
define('DB_USER','youfid');
define('DB_PASS','youfid');
//define('SENDGRID_USER','Youfiduser');
//define('SENDGRID_APIKEY','youfid75');
define('SENDGRID_APIKEY','');

require_once 'System/Daemon.php';

System_Daemon::setOptions(array(
    'appName' => 'youfidmailer2',
    'appDir' => dirname(__FILE__),
    'appDescription' => 'Sends mails queue',
    'authorName' => 'Baptiste Deleplace',
    'authorEmail' => 'baptiste@5inq.fr',
    'sysMaxExecutionTime' => '0',
    'sysMaxInputTime' => '0',
    'sysMemoryLimit' => '1024M',
));
System_Daemon::start();

if (($initd_location = System_Daemon::writeAutoRun()) === false) {
  System_Daemon::notice('unable to write init.d script');
} else {
  System_Daemon::info('sucessfully written startup script: %s', $initd_location);
}

while(true)
{

  $database = new PDO('mysql:host=' . DB_HOST . ';port=' . DB_PORT . ';dbname=' . DB_NAME . ';charset=utf8', DB_USER, DB_PASS);

  $q1 = $database->query("
    SELECT *
    FROM  `sendmail_queue`
    WHERE `status` LIKE 'created' AND (delay_until IS NULL OR delay_until < NOW())
    ORDER BY `id`
    LIMIT 100
  ");

  while($mail = $q1->fetch(PDO::FETCH_OBJ))
  {

    require __DIR__ . '/mailerdaemon2/conf.php';

    if(!isset($_conf['template'][$mail->template]))
      $mail->template = 'youfid';

    # to
    $to = $mail->to_email;
    // $to = 'baptiste@5inq.fr';

    # toname
    $toname = $mail->to_name;
    if(empty($mail->to_name))
      $toname = $mail->to_email;

    # from
    $from = $_conf['template'][$mail->template]['from'];

    # fromname
    $fromname = $_conf['template'][$mail->template]['fromname'];

    # subject
    $subject = $_conf['template'][$mail->template]['subject_prefix'] . $_conf['context'][$mail->context]['subject'];

    # html
    $html = file_get_contents(__DIR__ . '/mailerdaemon2/' . $mail->template . '.template.html');
    $html = str_replace('{{ to_email }}', $mail->to_email, $html);
    $html = str_replace('{{ title }}', $_conf['context'][$mail->context]['title'], $html);
    $html = str_replace('{{ message }}', $mail->message, $html);

    # category
    $category = ['youfid', 'youfid_' . $mail->template, 'youfid_' . $mail->context];

    $payload = [
      'personalizations' => [
        [
          'to' => [ [ 'email' => $to, 'name' => $toname ] ],
          'subject' => $subject
        ]
      ],
      'content' => [
        [ 'type' => 'text/html', 'value' => $html ]
      ],
      'from' => [ 'email' => $from, 'name' => $fromname ],
      'reply_to' => [ 'email' => $from, 'name' => $fromname ]
    ];   

    ob_start();
    $ch = curl_init();

    curl_setopt($ch, CURLOPT_URL, 'https://api.sendgrid.com/v3/mail/send');
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'POST');
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
      'Content-Type: application/json',
      'Authorization: Bearer ' . SENDGRID_APIKEY
    ]);

    $output = curl_exec($ch);
    $info = curl_getinfo($ch);
    
    curl_close($ch);
    ob_end_clean();

    $status = 'success';
    if($info['http_code'] != '202' && $info['http_code'] != '200') {
      $status = 'error';
    }

    $q2 = $database->prepare("
      UPDATE `sendmail_queue`
      SET
        `status` = :status,
        `api_response_json` = :api_response_json
      WHERE `id` = :id
    ");
    $q2->execute([
      'status' => $status,
      'api_response_json' => $output,
      'id' => $mail->id,
    ]);

  }

  $q1->closeCursor(); // Close statement cursor
  $database = null; // Close connection

  sleep(5);

}

System_Daemon::stop();

