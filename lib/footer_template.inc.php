<?php
/**
 * Préparation du pied de page des gabarits
 * 
 * @package General
 * @subpackage Affichage
*/

	//echo "<div id='temoin_messagerie_non_vide' style='position:fixed; right:1em; top:300px;'></div>\n";

	// Affichage de la durée de chargement de la page

	if (!isset($niveau_arbo)) $niveau_arbo = 1;
	 if ($niveau_arbo == "0") {
       /**
        * Appel de microtime_template.php
        * @see microtime_template.php
        */
	   require ("./lib/microtime_template.php");
	   $gepiPath2=".";
	} elseif ($niveau_arbo == "1") {
       /**
        * Appel de microtime_template.php
        * @see microtime_template.php
        */
	   require ("../lib/microtime_template.php");
	   $gepiPath2="..";
	} elseif ($niveau_arbo == "2") {
       /**
        * Appel de microtime_template.php
        * @see microtime_template.php
        */
	    require ("../../lib/microtime_template.php");
	   $gepiPath2="../..";
	} elseif ($niveau_arbo == "3") {
       /**
        * Appel de microtime_template.php
        * @see microtime_template.php
        */
	    require ("../../../lib/microtime_template.php");
	   $gepiPath2="../../..";
	}
	if(getSettingValue("gepi_pmv")!="n"){
		if (file_exists($gepiPath2."/pmv.php")) {
       /**
        * Appel de pmv.php
        */
          require ($gepiPath2."/pmv.php");
        }
	}
?>
