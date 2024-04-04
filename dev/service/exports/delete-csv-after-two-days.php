<?php

proc_nice(4);

//Browse folder and list all csv files
$csvFiles = glob(__DIR__ . '/*.csv');

//Check files last modification date and delete them if more than 48h
foreach ($csvFiles as $csvFile) {
	$fileAge = time()- filemtime($csvFile);

	if($fileAge > (2*24*3600))
		unlink($csvFile);
}

