<div class="myContent">
	
		<?php
			/// Il faudra changer le logo par le logo youfid ainsi que changer le path
			$logoPath = "static/logos/logo.png";
			
			if(isset($_SESSION['logopath']) && !empty($_SESSION['logopath']))
			{
				$logoPath = $_SESSION['logopath'];
			}
			
			//onChange="combo(this, 'theinput')"
		?>
	
        <div class="clear"></div>
        
        <div class="gauche left">
        	<?php echo($_SESSION['selector_current_location']) ?>
        	<form method="post" action="<?php echo($_SESSION['selector_current_location']) ?>">
	        	<select name="shoplist"	onchange="form.submit()">
					<?php 
						if ($_SESSION['role'] == "youfid_commerciaux")
							echo("<option>NEW</option>");	
							
						require_once("lib/spinner_list_shop.php")
					?>
				</select>
			</form>
			 
        </div>
        <div class="milieu left">
         	<img src=<?php echo($logoPath) ?> width="70%" ></img>
        </div>
        <div class="droite left">
            <?php echo("Log: " . $_SESSION['role']) ?>
        </div>

        <div class="clear"></div>
    </div>
