<?php

/*
	$Id$
*/
if(function_exists("mb_detect_encoding")&&function_exists("mb_convert_encoding")){
	$string = "���������������������զ����ݾ�������������������������������";
	$encoding = mb_detect_encoding($string, "UTF-8, ISO-8859-1");
	$string = mb_convert_encoding($string, "UTF-8", $encoding);
	//$string = mb_convert_encoding($string, "ISO-8859-1", $encoding);
} else {
	$string = "";
}
//echo $string;

	$rech_nom=isset($_POST['rech_nom']) ? $_POST['rech_nom'] : NULL;
	$rech_prenom=isset($_POST['rech_prenom']) ? $_POST['rech_prenom'] : NULL;

	//$rech_nom=my_ereg_replace("[^A-Za-zÂÄÀÁÃÄÅÇÊËÈÉÎÏÌÍÑÔÖÒÓÕŠÛÜÙÚÝŸŽáàâäãåçéèêëîïìíñôöðòóõšûüùúýÿž]","",$rech_nom);

	$page=isset($_POST['page']) ? $_POST['page'] : "";

	//if(($page!="visu_eleve.php")&&($page!="export_bull_eleve.php.php")) {
	//if(($page!="visu_eleve.php")&&($page!="export_bull_eleve.php")&&($page!="import_bull_eleve.php")) {
	if(($page!="fiche_eleve.php")&&($page!="visu_eleve.php")&&($page!="export_bull_eleve.php")&&($page!="import_bull_eleve.php")) {
		$page="../logout.php?auto=2";
		// Remarque: Cela n'empêche pas de bricoler l'adresse destination des liens affichés...
	}

	//echo "rech_prenom=$rech_prenom<br />";
	//echo "string=$string<br />";

	$nb_ele=0;

	if(isset($rech_nom)) {
		$rech_nom=preg_replace("/[^A-Za-z$string]/","",$rech_nom);

		$sql="SELECT * FROM eleves WHERE nom LIKE '%$rech_nom%' ORDER BY nom, prenom;";
		$res_ele=mysql_query($sql);
	
		$nb_ele=mysql_num_rows($res_ele);
	
		if($nb_ele==0){
			// On ne devrait pas arriver là.
			//echo "<p>Aucun nom d'élève ne contient la chaine $rech_nom.</p>\n";
			echo "<p>Aucun nom d'&eacute;l&egrave;ve ne contient la chaine $rech_nom.</p>\n";
		}
	}
	elseif(isset($rech_prenom)) {
		$rech_prenom=preg_replace("/[^A-Za-z$string]/","",$rech_prenom);
		//echo "rech_prenom=$rech_prenom<br />";

		$sql="SELECT * FROM eleves WHERE prenom LIKE '%$rech_prenom%' ORDER BY nom, prenom;";
		$res_ele=mysql_query($sql);
	
		$nb_ele=mysql_num_rows($res_ele);
	
		if($nb_ele==0){
			// On ne devrait pas arriver là.
			//echo "<p>Aucun nom d'élève ne contient la chaine $rech_nom.</p>\n";
			echo "<p>Aucun pr&eacute;nom d'&eacute;l&egrave;ve ne contient la chaine $rech_prenom.</p>\n";
		}
	}

	if($nb_ele>0){
		//echo "<p>La recherche a retourné <b>$nb_ele</b> réponse(s):</p>\n";
		echo "<p>La recherche a retourn&eacute; <b>$nb_ele</b> r&eacute;ponse";
		if($nb_ele>1) {echo "s";}
		echo ":</p>\n";
		echo "<table border='1' class='boireaus' summary='Liste des élèves'>\n";
		echo "<tr>\n";
		//echo "<th>Elève</th>\n";
		echo "<th>El&egrave;ve</th>\n";
		echo "<th>Classe(s)</th>\n";
		echo "</tr>\n";
		$alt=1;
		while($lig_ele=mysql_fetch_object($res_ele)) {
			$ele_login=$lig_ele->login;
			$ele_nom=$lig_ele->nom;
			$ele_prenom=$lig_ele->prenom;
			//echo "<b>$ele_nom $ele_prenom</b>";
			$alt=$alt*(-1);
			echo "<tr class='lig$alt'>\n";
			echo "<td>\n";
			//echo "<a href='visu_eleve.php?ele_login=$ele_login'>$ele_nom $ele_prenom</a>";
			echo "<a href='$page?ele_login=$ele_login'>".htmlentities("$ele_nom $ele_prenom")."</a>";

			$sql="SELECT DISTINCT c.* FROM classes c, j_eleves_classes jec WHERE jec.login='$ele_login' AND c.id=jec.id_classe ORDER BY jec.periode;";
			$res_clas=mysql_query($sql);
			if(mysql_num_rows($res_clas)==0) {
				//echo " (<i>";
				echo "<td>\n";
				echo "aucune classe";
				//echo "</i>)\n";
				//echo "<br />\n";
				echo "</td>\n";
			}
			else {
				//echo "(<i>";
				echo "<td>\n";
				$cpt=0;
				while($lig_clas=mysql_fetch_object($res_clas)) {
					if($cpt>0) {echo ", ";}
					//echo $lig_clas->classe;
					echo htmlentities($lig_clas->classe);
					$cpt++;
				}
				//echo "</i>)";
				//echo "<br />\n";
				echo "</td>\n";
			}
			echo "</tr>\n";
		}
		echo "</table>\n";
	}
?>
