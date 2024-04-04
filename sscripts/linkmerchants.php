<?php

if (count($argv) < 3) {
  die("Usage: please provide merchant and merchants to link to it\n");
}

$super_merc_id = 0;
if(is_numeric($argv[1])) {
  $super_merc_id = (int) $argv[1];
} else {
  die("Super merchant id must be numeric");
}

$merc_tolink = array();
for ($i = 2; $i < count($argv); $i++) {
  if(is_numeric($argv[$i])) {
    array_push($merc_tolink, (int)$argv[$i]);
  } else {
    die("Merchant id must be numeric");
  }
}

try {

  $user = 'root';
  $pass = 'YouFID';
  $dbh = new PDO('mysql:host=db.youfid.fr;dbname=youfid', $user, $pass);
  $dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $dbloy = new PDO('mysql:host=db.youfid.fr;dbname=loyalty', $user, $pass);
  $dbloy->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

  $dbh->beginTransaction();

  $merc_qry = $dbh->prepare('select id, name, is_supermarchand, supermarchand_id, application_id from marchand where id=:id and is_supermarchand = 1');
  $merc_qry->bindParam(':id', $super_merc_id, PDO::PARAM_INT);
  $merc_qry->execute();

  if($merc_qry->rowCount() > 0) {
    $super_result = $merc_qry->fetch(PDO::FETCH_OBJ);
  } else {
    throw new Exception("Could not retrieve super merchant");
  }

  $dbh->exec("update marchand set group_loyalty = 1 where id = " . $super_merc_id);

  $super_merc_qry = $dbh->prepare("select * from marchand where id=:id");
  $merc_instance_qry = $dbh->prepare("select id, application_id, name from marchand where id=:id");
  $merc_link_update_qry = $dbh->prepare("update marchand set supermarchand_id=:supermarchandId, application_id=:applicationId where id=:id");
  $merc_users_select_qry = $dbh->prepare("select * from marchand_has_mobileuser where marchand_id=:marchandId");
  $merc_users_insert_qry = $dbh->prepare("insert into `marchand_has_mobileuser` (`marchand_id`, `mobileuser_id`, `last_notif`, `nb_use`, `date_localisation`, `creation_date`) " .
                                        "values (:marchandId, :mobileuserId, :lastNotif, :nbUse, :dateLocalisation, :creationDate)");
  $merc_users_check_qry = $dbh->prepare("select * from marchand_has_mobileuser where marchand_id=? and mobileuser_id=?");

  $res = array();
  $applications = array();

  foreach($merc_tolink as $merc_id) {

    $merc_users_select_qry->bindParam(':marchandId', $merc_id, PDO::PARAM_INT);
    $merc_users_select_qry->execute();
    $count = 0;
    if($merc_users_select_qry->rowCount() > 0) {
      while($merc_usr_row = $merc_users_select_qry->fetch(PDO::FETCH_OBJ)) {
        $merc_users_check_qry->execute(array($super_merc_id, $merc_usr_row->mobileuser_id));

        if($merc_users_check_qry->rowCount() == 0) {

          $merc_users_insert_qry->execute(array(
            "marchandId" => (int) $super_merc_id,
            "mobileuserId" => (int) $merc_usr_row->mobileuser_id,
            "lastNotif" => (string) $merc_usr_row->last_notif,
            "nbUse" => (int) $merc_usr_row->nb_use,
            "dateLocalisation" => (string) $merc_usr_row->date_localisation,
            "creationDate" => (string) $merc_usr_row->creation_date
          ));
        }

        $count++;
      }
    }

    $merc_instance_qry->execute(array(
      "id" => $merc_id
    ));

    $merc_instance = $merc_instance_qry->fetch(PDO::FETCH_OBJ);

    array_push($applications, $merc_instance->application_id);

    $merc_link_update_qry->execute(array(
      'supermarchandId' => $super_merc_id,
      'applicationId' => $super_result->application_id,
      'id' => $merc_id
    ));

    array_push($res, array(
      "merchant" => $merc_id,
      "applicationId" => $merc_instance->application_id,
      "userCount" => $count
    ));
  }

  $in_clause = "'" . implode("', '", $applications) . "'";
  //echo "IN Clause: $in_clause";

  // Loy applications to merge
  $loy_apps_sql = "select * from application where public_id in (" . $in_clause . ")";
  echo "Loy apps qry: " . $loy_apps_sql . "\n";
  $loy_apps_qry = $dbloy->prepare($loy_apps_sql);
  $loy_apps_qry->execute();
  $loy_apps_ids = array();

  while($app = $loy_apps_qry->fetch(PDO::FETCH_OBJ)) {
    array_push($loy_apps_ids, $app->id);
  }

  //$ids_clause = "'" . implode("', '", $loy_apps_ids) . "'";

  $loy_application_qry = $dbloy->query("select * from application where public_id like '" . $super_result->application_id . "'");
  $loy_application = $loy_application_qry->fetch(PDO::FETCH_OBJ);

  $dbloy->beginTransaction();

  $loy_check_qry = $dbloy->prepare("select * from mobile_user_application where mobile_user_id=? and application_id=?");
  $loy_ins_qry = $dbloy->prepare("insert into mobile_user_application (mobile_user_id, application_id, total_points) values (?, ?, ?)");
  $loy_update_qry = $dbloy->prepare("update mobile_user_application set total_points = total_points + ? where mobile_user_id=? and application_id=?");
  $loy_app_users_qry = $dbloy->prepare("select * from mobile_user_application where application_id=?");

  // update loyalty
  // For each applications
  foreach($loy_apps_ids as $loy_app_id) {
    // Get application users
    $loy_app_users_qry->execute(array($loy_app_id));
    // If any
    if($loy_app_users_qry->rowCount() > 0) {
      // For each of them
      while($loy_app_user = $loy_app_users_qry->fetch(PDO::FETCH_OBJ)) {
        // Checks if super marchand and user are already linked
        $loy_check_qry->execute(array($loy_app_user->mobile_user_id, $loy_application->id));
        if($loy_check_qry->rowCount() === 1) { // YES
          // Update link by cumulating points
          $loy_update_qry->execute(array($loy_app_user->total_points, $loy_app_user->mobile_user_id, $loy_application->id));
        } else { // NO
          // Creates new link beteween user and super marchand
          $loy_ins_qry->execute(array($loy_app_user->mobile_user_id, $loy_application->id, $loy_app_user->total_points));
        }
      }
    }
  }
  /*
  $loyUpdateQry = "insert into mobile_user_application select mobile_user_id, '" . $loy_application->id . "' as application_id, sum(total_points) as total_points " .
                  "from mobile_user_application where application_id in (" . $ids_clause . ") group by mobile_user_id";
  $dbloy->exec($loyUpdateQry);
  */
  print_r($res);
  $dbloy->commit();
  $dbh->commit();
} catch(PDOException $e) {
  echo("Rollback modifs ...\n");
  print_r($e);
  try {
    $dbloy->rollBack();
    $dbh->rollBack();
  } catch(Exception $x) {
  } finally {
    die($ex);
  }
  die($e);
} catch(Exception $ex) {
  echo("Rollback modifs ...\n");
  print_r($ex);
  try {
    $dbloy->rollBack();
    $dbh->rollBack();
  } catch(Exception $x) {
  } finally {
    die($ex);
  }
}
?>
