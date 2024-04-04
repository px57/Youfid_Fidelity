<?php 
// GO CHECKEZ VAR SESION DEFINIT AU LOG
//$role = "marchand";

$role = $_SESSION['role'];

if ($role == "youfid_commerciaux") 
{
?>
<ul id="nav">
<li <?php if ($_SESSION['selector_current_location'] == "commerciaux_moncompte.php"){echo "class='current'";}?>><a href="commerciaux_moncompte.php"> Mon Compte </a></li>
<li <?php if ($_SESSION['selector_current_location'] == "commerciaux_programmedefid.php"){echo "class='current'";}?>><a href="commerciaux_programmedefid.php"> Programme de fidélité </a></li>
</ul>
<?php 
}
if ($role == "youfid_master" || $role == "admin_4g") 
{
?>
<ul id="nav">
<li <?php if ($_SESSION['selector_current_location'] == "youfid_master_moncompte.php"){echo "class='current'";}?>><a href="youfid_master_moncompte.php">Mon Compte</a></li>
<li  <?php if ($_SESSION['selector_current_location'] == "youfid_master_programmedefid.php"){echo "class='current'";}?>><a href="youfid_master_programmedefid.php">Programme de fidélité</a></li>
<li <?php if ($_SESSION['selector_current_location'] == "youfid_master_clients.php"){echo "class='current'";}?>><a href="youfid_master_clients.php">Clients</a></li>
<li <?php if ($_SESSION['selector_current_location'] == "youfid_master_stats.php" || $_SESSION['selector_current_location'] == "youfid_master_stats_marchand.php"){echo "class='current'";}?>><a href="youfid_master_stats.php">Stats</a></li>
<li <?php if ($_SESSION['selector_current_location'] == "youfid_master_promos.php"){echo "class='current'";}?>><a href="youfid_master_promos.php">Promotions</a></li>
<li <?php if ($_SESSION['selector_current_location'] == "youfid_master_pushgeo.php"){echo "class='current'";}?>><a href="youfid_master_pushgeo.php">Push</a></li>
<li <?php if ($_SESSION['selector_current_location'] == "youfid_master_validationmes.php"){echo "class='current'";}?>><a href="youfid_master_validationmes.php">Validation messages</a></li>

</ul>


<?php 
}
if ($role == "marchands") 
{
?>

<ul id="nav">
<li <?php if ($_SESSION['selector_current_location'] == "marchand_moncompte.php"){echo "class='current'";}?>><a href="marchand_moncompte.php">Mon Compte</a></li>
<li <?php if ($_SESSION['selector_current_location'] == "marchand_programmedefid.php"){echo "class='current'";}?>><a href="marchand_programmedefid.php">Programme de fidélité</a></li>
<li <?php if ($_SESSION['selector_current_location'] == "marchand_clients.php"){echo "class='current'";}?>><a href="marchand_clients.php">Clients</a></li>
<li <?php if ($_SESSION['selector_current_location'] == "marchand_stats.php"){echo "class='current'";}?>><a href="marchand_stats.php">Stats</a></li>
<li <?php if ($_SESSION['selector_current_location'] == "marchand_promos.php"){echo "class='current'";}?>><a href="marchand_promos.php">Promotions</a></li>
<li <?php if ($_SESSION['selector_current_location'] == "marchand_pushgeo.php"){echo "class='current'";}?>><a href="marchand_pushgeo.php">Push</a></li>
</ul>

<?php 
}
?>


</body>
</html>