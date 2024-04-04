<?

$settings = '';
$settings['host'] = 'db.youfid.fr';
$settings['dbname'] = 'youfid';
$settings['dbusername'] = 'youfid';
$settings['dbpassword'] = 'youfid';

try
{
	$database = new PDO("mysql:host=".$settings['host'].";dbname=".$settings['dbname'].";charset=utf8", $settings['dbusername'], $settings['dbpassword'],
               array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
}
catch(Exception $error)
{
        die('Error : '.$error->getMessage());
}

if(isset($_POST['adminpassword']) && $_POST['adminpassword'] == 'Youfid1234') {
	$backoffice_usr = $database->prepare('UPDATE backoffice_usr SET login = ?, password = ? WHERE id_marchand = ? AND id_role = 4');
	$backoffice_usr->execute(array($_POST['login'], $_POST['password'], $_POST['marchand_id']));
	$is_backoffice_usr = $backoffice_usr->rowCount();
}

$marchands = $database->prepare('SELECT * FROM marchand WHERE is_active = ? ORDER BY name ASC');
$marchands->execute(array(1));

?>
<!DOCTYPE html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>YouFid</title>

    <!-- Latest compiled and minified CSS -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap.min.css">

	<!-- Optional theme -->
	<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/css/bootstrap-theme.min.css">
    <style type="text/css">
      body {
        padding-top: 50px;
      }
      .starter-template {
        padding: 40px 15px;
        text-align: center;
      }
    </style>
	<!-- Latest compiled and minified JavaScript -->

    <!-- HTML5 shim and Respond.js for IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
      <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
      <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
  </head>
  <body>
    <nav class="navbar navbar-inverse navbar-fixed-top">
      <div class="container">
        <div class="navbar-header">
          <button type="button" class="navbar-toggle collapsed" data-toggle="collapse" data-target="#navbar" aria-expanded="false" aria-controls="navbar">
            <span class="sr-only">Toggle navigation</span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
            <span class="icon-bar"></span>
          </button>
          <a class="navbar-brand" href="#">YouFid admin</a>
        </div>
        <div id="navbar" class="collapse navbar-collapse">
          <ul class="nav navbar-nav">
            <li class="active"><a href="#">Change password</a></li>
          </ul>
        </div><!--/.nav-collapse -->
      </div>
    </nav>
    <div class="starter-template">
    <div class="container">
    <? if($is_backoffice_usr && $_POST['adminpassword']) { ?>
    <div class="alert alert-success" role="alert">Success</div>
    <? } else if($_POST['adminpassword']) { ?>
    <div class="alert alert-danger" role="alert">Error</div>
    <? } ?>
    <div class="well">
    <form action="?" method="POST">
  <div class="form-group">
    <label for="marchand_id">Marchand</label>
<select class="form-control" name="marchand_id" id="marchand_id">
  <? while($marchands_d = $marchands->fetch()) { ?>
                <option value="<?=$marchands_d['id']?>">
                <?=utf8_decode($marchands_d['name'])?>
                </option>
                <? } ?>
</select>  </div>
  <div class="form-group">
    <label for="login">New login</label>
    <input type="text" class="form-control" name="login" id="login" placeholder="Login">
  </div>
  <div class="form-group">
    <label for="password">New password</label>
    <input type="password" class="form-control"  name="password" id="password" placeholder="Password">
  </div>
  
  <div class="form-group">
    <label for="password">Admin password</label>
    <input type="password" class="form-control"  name="adminpassword" id="adminpassword" placeholder="Password">
  </div>
 
  <button type="submit" class="btn btn-primary">Modifier</button>
</form>
    </div>
    </div>
    </div>

    <!-- jQuery (necessary for Bootstrap's JavaScript plugins) -->
    <script src="https://ajax.googleapis.com/ajax/libs/jquery/1.11.2/jquery.min.js"></script>
    <!-- Include all compiled plugins (below), or include individual files as needed -->
    <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.2/js/bootstrap.min.js"></script>

  </body>
</html>
