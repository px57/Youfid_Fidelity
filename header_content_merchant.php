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
        	<img src="static/images/blank.png"></img>
        </div>
        
         <div class="milieu left">
         	<img height="100%" width="60%" src=<?php echo($logoPath); ?>></img>
        </div>
        
        <div class="droite left">
            <img height="60%" width="47%" src="static/logos/mini_logoyoufid_hd.png"></img>
            <form method="GET" action="lib/logout.php">
            	<input type="submit" value="Logout"/>
            </form>
        </div>

        <div class="clear"></div>
</div>
