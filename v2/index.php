<?php

require_once __DIR__ . '/vendor/autoload.php';

$app = new Silex\Application();
$app['debug'] = true;

define('ACCESS_TOKEN', 'ae0305a9427a91f6f63e55af0eaa1d9c4c02af07f672d15e4a77d99b65327822');

require_once __DIR__ . '/helpers/db.php';

require_once __DIR__ . '/controllers/user.php';
require_once __DIR__ . '/controllers/coupon.php';
require_once __DIR__ . '/controllers/ticket.php';
require_once __DIR__ . '/controllers/shop.php';

# running ...
header('Access-Control-Allow-Origin: *');
$app->run();
