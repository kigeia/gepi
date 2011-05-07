<?php
/*
* $Id$
*
* Copyright 2001, 2011 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun
*
* This file is part of GEPI.
*
* GEPI is free software; you can redistribute it and/or modify
* it under the terms of the GNU General Public License as published by
* the Free Software Foundation; either version 2 of the License, or
* (at your option) any later version.
*
* GEPI is distributed in the hope that it will be useful,
* but WITHOUT ANY WARRANTY; without even the implied warranty of
* MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
* GNU General Public License for more details.
*
* You should have received a copy of the GNU General Public License
* along with GEPI; if not, write to the Free Software
* Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA
*/

// Initialisations files
require_once("../lib/initialisations.inc.php");

// Resume session

$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
	header("Location: ../utilisateurs/mon_compte.php?change_mdp=yes");
	die();
} else if ($resultat_session == '0') {
	header("Location: ../logout.php?auto=1");
	die();
}

if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}

// Initialisation des variables utilis�es dans le formulaire

$chemin_retour=isset($_GET['chemin_retour']) ? $_GET['chemin_retour'] : (isset($_POST['chemin_retour']) ? $_POST["chemin_retour"] : NULL);
$ancre=isset($_GET['ancre']) ? $_GET['ancre'] : (isset($_POST['ancre']) ? $_POST["ancre"] : NULL);

$id_classe = isset($_GET['id_classe']) ? $_GET['id_classe'] : (isset($_POST['id_classe']) ? $_POST["id_classe"] : NULL);
$id_groupe = isset($_GET['id_groupe']) ? $_GET['id_groupe'] : (isset($_POST['id_groupe']) ? $_POST["id_groupe"] : NULL);

if (!is_numeric($id_groupe)) {$id_groupe = 0;}
$current_group = get_group($id_groupe);
$reg_nom_groupe = $current_group["name"];
$reg_nom_complet = $current_group["description"];
$reg_matiere = $current_group["matiere"]["matiere"];
$reg_id_classe = $current_group["classes"]["list"][0];
$reg_clazz = $current_group["classes"]["list"];
$reg_professeurs = (array)$current_group["profs"]["list"];

/*
foreach($reg_clazz as $key => $value) {
echo "\$reg_clazz[$key]=$value<br />";
}
*/

$mode = isset($_GET['mode']) ? $_GET['mode'] : (isset($_POST['mode']) ? $_POST["mode"] : null);
if ($mode == null and $id_classe == null) {
	$mode = "groupe";
} else if ($mode == null and $current_group) {
	if (count($current_group["classes"]["list"]) > 1) {
		$mode = "regroupement";
	} else {
		$mode = "groupe";
	}
}

foreach ($current_group["periodes"] as $period) {
	$reg_eleves[$period["num_periode"]] = $current_group["eleves"][$period["num_periode"]]["list"];
}

if (isset($_POST['is_posted'])) {
	check_token();

	$msg="";
	$error = false;
	//=======================================
	// MODIF: boireaus
	/*
	$reg_nom_groupe = $_POST['groupe_nom_court'];
	$reg_nom_complet = $_POST['groupe_nom_complet'];
	*/
	$reg_nom_groupe = html_entity_decode_all_version($_POST['groupe_nom_court']);
	$reg_nom_complet = html_entity_decode_all_version($_POST['groupe_nom_complet']);
	//=======================================
	$reg_matiere = $_POST['matiere'];

	if (empty($reg_nom_groupe)) {
		$error = true;
		$msg .= "Vous devez donner un nom court au groupe.<br />\n";
	}

	if (empty($reg_nom_groupe)) {
		$error = true;
		$msg .= "Vous devez donner un nom complet au groupe.<br />\n";
	}

	$clazz = array();

	// Classes

	if ($_POST['mode'] == "groupe") {
		// Ajout s�curit�:
		if((!isset($id_classe))||($id_classe=='')) {$id_classe=$current_group['classes']['list'][0];}

		$clazz[] = $id_classe;
		$reg_id_classe = $id_classe;
		$mode = "groupe";
	} else if ($_POST['mode'] == "regroupement") {
		$mode = "regroupement";
		foreach ($_POST as $key => $value) {
			if (preg_match("/^classe\_/", $key)) {
				$temp = explode("_", $key);
				$id = $temp[1];
				$clazz[] = $id;
			}
		}



		foreach ($_POST as $key => $value) {
			if (preg_match("/^precclasse\_/", $key)) {
				$temp = explode("_", $key);
				$tmpid = $temp[1];
				// On v�rifie si la classe a �t� d�coch�e:
				if(!isset($_POST['classe_'.$tmpid])){
					// On v�rifie si l'identifiant de classe $tmpid peut �tre d�coch�.
		
					unset($tabtmp);
					$tabtmp=array();
					$test=0;
					$test2=0;
					$sql="SELECT DISTINCT login FROM j_eleves_classes WHERE id_classe='$tmpid'";
					$res_tmp=mysql_query($sql);
					while($lig_tmp=mysql_fetch_object($res_tmp)){
						$sql="SELECT 1=1 FROM matieres_notes WHERE id_groupe='$id_groupe' AND login='$lig_tmp->login'";
						//echo "$sql<br />\n";
						$res_test=mysql_query($sql);
						if(mysql_num_rows($res_test)>0){
							//echo "$lig_tmp->login<br />\n";
							if(!in_array($lig_tmp->login,$tabtmp)){$tabtmp[]=$lig_tmp->login;}
							$test++;
						}
						$sql="SELECT 1=1 FROM matieres_appreciations WHERE id_groupe='$id_groupe' AND login='$lig_tmp->login'";
						//echo "$sql<br />\n";
						$res_test=mysql_query($sql);
						if(mysql_num_rows($res_test)>0){
							//echo "$lig_tmp->login<br />\n";
							if(!in_array($lig_tmp->login,$tabtmp)){$tabtmp[]=$lig_tmp->login;}
							$test2++;
						}
					}
		
					$sql="SELECT classe FROM classes WHERE id='$tmpid'";
					$res_tmp=mysql_query($sql);
					$lig_tmp=mysql_fetch_object($res_tmp);
					$clas_tmp=$lig_tmp->classe;
		
					//if(!$verify){
					if(($test>0)||($test2>0)){
						/*
						$sql="SELECT classe FROM classes WHERE id='$tmpid'";
						$res_tmp=mysql_query($sql);
						$lig_tmp=mysql_fetch_object($res_tmp);
						$clas_tmp=$lig_tmp->classe;
						*/
		
						$error = true;
						$msg .= "Des donn�es existantes bloquent la suppression de la classe $clas_tmp du groupe.<br />\nAucune note ni appr�ciation du bulletin ne doit avoir �t� saisie pour les �l�ves de ce groupe pour permettre la suppression du groupe.<br />\n";
						if(count($tabtmp)==1){
							$msg.="L'�l�ve ayant des moyennes ou appr�ciations saisies est $tabtmp[0].<br />\n";
						}
						else{
							$msg.="Les �l�ves ayant des moyennes ou appr�ciations saisies sont $tabtmp[0]";
							for($i=1;$i<count($tabtmp);$i++){
								$msg.=", $tabtmp[$i]";
							}
							$msg.=".<br />\n";
						}
						// Et on remet la classe dans la liste des classes:
								$clazz[] = $tmpid;
					}
					else{
						// On teste aussi si il y a des �l�ves de la classe dans le groupe.
						$sql="SELECT jeg.login FROM j_eleves_groupes jeg, j_eleves_classes jec WHERE
									jeg.login=jec.login AND
									jeg.periode=jec.periode AND
									jeg.id_groupe='$id_groupe' AND
									jec.id_classe='$tmpid'";
						//echo "$sql<br />\n";
						$res_ele_clas_grp=mysql_query($sql);
						if(mysql_num_rows($res_ele_clas_grp)>0){
							$error = true;
							$msg .= "Des donn�es existantes bloquent la suppression de la classe $clas_tmp du groupe.<br />\nAucun �l�ve de la classe ne doit �tre inscrit dans le groupe.<br />\n<a href='edit_eleves.php?id_groupe=$id_groupe&id_classe=$tmpid'>Enlevez les �l�ves du groupe</a> avant.<br />\n";
							// Et on remet la classe dans la liste des classes:
							$clazz[] = $tmpid;
						}
					}
				}
			}
		}


	}

	// On ajoute un test pour s'assurer qu'on n'a pas un tableau vide pour les classes
	if (count($clazz) == 0) {
		$clazz[0] = $id_classe;
	}

	// Professeurs
	$reg_professeurs = array();
	foreach ($_POST as $key => $value) {
		if (preg_match("/^prof\_/", $key)) {
			$id = preg_replace("/^prof\_/", "", $key);
			$proflogin = $_POST["proflogin_".$id];
			// Normalement on a un traitement anti-injection sur $_POST, donc pas de soucis.
			// Mais �a serait bien de faire un test quand m�me. Si un dev passe par l�...
			//$reg_professeurs[] = $proflogin;

			$sql="SELECT 1=1 FROM j_professeurs_matieres WHERE id_professeur='$proflogin' AND id_matiere='$reg_matiere';";
			$test_prof_matiere=mysql_query($sql);
			if(mysql_num_rows($test_prof_matiere)>0) {
				$reg_professeurs[] = $proflogin;
			}
		}
	}

	$reg_clazz = $clazz;

	/*
	echo "Apres modif:<br />";
	foreach($reg_clazz as $key => $value) {
		echo "\$reg_clazz[$key]=$value<br />";
	}
	*/

	if (empty($reg_clazz)) {
		$error = true;
		$msg .= "Vous devez s�lectionner au moins une classe.<br />\n";
	}

	if (!$error) {
		// pas d'erreur : on continue avec la mise � jour du groupe
		$create = update_group($id_groupe, $reg_nom_groupe, $reg_nom_complet, $reg_matiere, $reg_clazz, $reg_professeurs, $reg_eleves);
		if (!$create) {
			$msg .= "Erreur lors de la mise � jour du groupe.";
		} else {
			//======================================
			// MODIF: boireaus
			//$msg = "Le groupe a bien �t� mis � jour.";
			$msg = "L'enseignement ". stripslashes($reg_nom_complet) . " a bien �t� mis � jour.";
			$msg = urlencode($msg);

			if(isset($chemin_retour)) {
				if(strstr($chemin_retour,'utilisateurs/index.php')) {
					// On n'arrive sur edit_group.php en venant de utilisateurs/index.php que depuis la partie Gestion de comptes utilisateurs Personnels de l'�tablissement
					if(isset($ancre)) {
						header("Location: $chemin_retour?&msg=$msg&mode=personnels#$ancre");
					}
					else {
						header("Location: $chemin_retour?&msg=$msg&mode=personnels");
					}
				}
				else {
					header("Location: $chemin_retour?&msg=$msg");
				}
			}
			else{
				header("Location: ./edit_class.php?id_classe=$id_classe&msg=$msg");
			}
			//======================================
		}
		$current_group = get_group($id_groupe);
	}
}
/* DEBUG
echo "<pre>\n";
print_r($_POST);
echo "</pre>\n";
echo html_entity_decode_all_version("prof_ERIC_ALARY");

echo "<pre>\n";
print_r($current_group);
echo "</pre>\n";

echo "<pre>\n";
print_r($reg_professeurs);
echo "</pre>\n";
*/

$themessage  = 'Des informations ont �t� modifi�es. Voulez-vous vraiment quitter sans enregistrer ?';
//**************** EN-TETE **************************************
$titre_page = "Gestion des groupes";
require_once("../lib/header.inc");
//**************** FIN EN-TETE **********************************

//debug_var();

//echo "\$_SERVER['HTTP_REFERER']=".$_SERVER['HTTP_REFERER']."<br />\n";

/*
foreach ($reg_clazz as $tmp_classe) {
	echo "\$tmp_classe=$tmp_classe<br />\n";
}
*/
?>
<p class="bold">
<?php
//============================
// MODIF: boireaus
//if(isset($_GET['chemin_retour'])){
if(isset($chemin_retour)){
	echo "<a href=\"".$_GET['chemin_retour']."\" onclick=\"return confirm_abandon (this, change, '$themessage')\"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a> | ";
}
else{
	echo "<a href=\"edit_class.php?id_classe=$id_classe\" onclick=\"return confirm_abandon (this, change, '$themessage')\"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a> | ";
}
//============================

echo "<a href='mes_listes.php?id_groupe=$id_groupe'>Exporter la composition du groupe</a> | ";
?>
<a href="edit_class.php?id_classe=<?php echo $id_classe;?>&amp;action=delete_group&amp;id_groupe=<?php echo $id_groupe;?><?php echo add_token_in_url();?>" onclick="return confirmlink(this, 'ATTENTION !!! LISEZ CET AVERTISSEMENT : La suppression d\'un enseignement est irr�versible. Une telle suppression ne devrait pas avoir lieu en cours d\'ann�e. Si c\'est le cas, cela peut entra�ner la pr�sence de donn�es orphelines dans la base. Si des donn�es officielles (notes et appr�ciations du bulletin) sont pr�sentes, la suppression sera bloqu�e. Dans le cas contraire, toutes les donn�es li�es au groupe seront supprim�es, incluant les notes saisies par les professeurs dans le carnet de notes ainsi que les donn�es pr�sentes dans le cahier de texte. Etes-vous *VRAIMENT S�R* de vouloir continuer ?', 'Confirmation de la suppression')"> Supprimer le groupe</a>
<?php
if ($mode == "groupe") {
	echo "<h3>Modifier le groupe</h3>\n";
} elseif ($mode == "regroupement") {
	echo "<h3>Modifier le regroupement</h3>\n";
}
?>
<form enctype="multipart/form-data" action="edit_group.php" method="post">
<div style="width: 95%;">
<div style="width: 45%; float: left;">
<p>Nom court : <input type=text size=30 name=groupe_nom_court value = "<?php echo $reg_nom_groupe; ?>" /></p>

<p>Nom complet : <input type=text size=50 name=groupe_nom_complet value = "<?php echo $reg_nom_complet; ?>" /></p>

<?php

echo add_token_field();

// Classes

if ($mode == "groupe") {
	echo "<p>\n";
	if((isset($current_group))&&(count($current_group["eleves"]["all"]["list"])==0)) {
		echo "S�lectionnez la classe � laquelle appartient le groupe :\n";
		echo "<select name='id_classe' size='1'";
		echo " onchange='changement();'";
		echo ">\n";
	
		$call_data = mysql_query("SELECT * FROM classes ORDER BY classe");
		$nombre_lignes = mysql_num_rows($call_data);
		if ($nombre_lignes != 0) {
			$i = 0;
			while ($i < $nombre_lignes){
				$id_classe2 = mysql_result($call_data, $i, "id");
				$classe = mysql_result($call_data, $i, "classe");
				if (get_period_number($id_classe2) != "0") {
					echo "<option value='" . $id_classe2 . "'";
					if (in_array($id_classe2, $reg_clazz)) echo " SELECTED";
					echo ">$classe</option>\n";
				}
			$i++;
			}
		} else {
			echo "<option value='false'>Aucune classe d�finie !</option>\n";
		}
		echo "</select>\n";
		//echo "<br />[-> <a href='edit_group.php?id_classe=".$id_classe."&id_groupe=".$id_groupe."&mode=regroupement'>s�lectionner plusieurs classes</a>]</p>\n";
		echo "<br />\n";
	}
	else {
		echo "<input type='hidden' name='id_classe' value='$id_classe' />\n";
		if(isset($current_group)) {
			echo "Enseignement en <b>".$current_group['classlist_string']."</b>.";
			echo "<br />\n";
		}
	}

	echo "[-> <a href='edit_group.php?id_classe=".$id_classe."&amp;id_groupe=".$id_groupe."&amp;mode=regroupement'>s�lectionner plusieurs classes</a>]\n";

	// On ne propose de fusionner le groupe avec un/des groupes existants que si le groupe n'a pas d�j� de notes,...
	// ... NON: On fera le test sur les groupes � y associer seulement.
	//          Ce sont les autres groupes qui seraient susceptibles de voir leurs notes disparaitre
	echo "<br />[-> <a href='fusion_group.php?id_classe=".$id_classe."&amp;id_groupe=".$id_groupe."'>fusionner le groupe avec un ou des groupes existants</a>]";

	// AJOUTER UN TEST: sur le fait que le groupe est vide...
	//echo "<br />[-> <a href='scinder_group.php?id_classe=".$id_classe."&amp;id_groupe=".$id_groupe."'>scinder le groupe</a>]";

	echo "</p>\n";

} else if ($mode == "regroupement") {
	echo "<input type='hidden' name='id_classe' value='".$id_classe."' />\n";
	echo "<p>S�lectionnez les classes auxquelles appartient le regroupement :";

	//$call_data = mysql_query("SELECT * FROM classes ORDER BY classe");
	//$sql="SELECT * FROM classes c, periodes p WHERE p.id_classe=c.id AND MAX(p.num_periode)='".get_period_number($id_classe)."' ORDER BY classe;";
	$sql="SELECT * FROM classes ORDER BY classe;";
	//echo "$sql<br />";
	$call_data = mysql_query($sql);
	$nombre_lignes = mysql_num_rows($call_data);
	if ($nombre_lignes != 0) {

		$i = 0;

		$tmp_tab_classe=array();
		$tmp_tab_id_classe=array();
		while ($i < $nombre_lignes){
			$id_classe_temp=mysql_result($call_data, $i, "id");
			$classe=mysql_result($call_data, $i, "classe");
			if (get_period_number($id_classe_temp) == get_period_number($id_classe)) {
				$tmp_tab_classe[]=$classe;
				$tmp_tab_id_classe[]=$id_classe_temp;
			}
			$i++;
		}

		echo "<table width='100%'>\n";
		echo "<tr valign='top' align='left'>\n";
		echo "<td>\n";
		//$nb_class_par_colonne=round($nombre_lignes/3);
		$nb_class_par_colonne=round(count($tmp_tab_classe)/3);
		for($i=0;$i<count($tmp_tab_classe);$i++) {
			if(($i>0)&&(round($i/$nb_class_par_colonne)==$i/$nb_class_par_colonne)){
				echo "</td>\n";
				echo "<td>\n";
			}

			$id_classe_temp=$tmp_tab_id_classe[$i];
			$classe=$tmp_tab_classe[$i];

			//echo "<br /><input type='checkbox' name='classe_" . $id_classe_temp . "' value='yes'";
			echo "<input type='checkbox' name='classe_" . $id_classe_temp . "' id='classe_" . $id_classe_temp . "' value='yes'";
			if (in_array($id_classe_temp, $reg_clazz)){
				echo " checked";
			}
			//echo " />$classe</option>\n";
			echo " onchange='changement();'";
			echo " /><label for='classe_".$id_classe_temp."' style='cursor: pointer;'>$classe</label>\n";
			if (in_array($id_classe_temp, $reg_clazz)){
				// Pour contr�ler les suppressions de classes.
				// On conserve la liste des classes pr�c�demment coch�es:
				echo "<input type='hidden' name='precclasse_".$id_classe_temp."' value='y' />\n";
			}
			echo "<br />\n";
		}

		/*
		echo "<table width='100%'>\n";
		echo "<tr valign='top' align='left'>\n";
		echo "<td>\n";
		$nb_class_par_colonne=round($nombre_lignes/3);
		while ($i < $nombre_lignes){
			if(($i>0)&&(round($i/$nb_class_par_colonne)==$i/$nb_class_par_colonne)){
				echo "</td>\n";
				echo "<td>\n";
			}

			$id_classe_temp = mysql_result($call_data, $i, "id");
			$classe = mysql_result($call_data, $i, "classe");
			if (get_period_number($id_classe_temp) == get_period_number($id_classe)) {
				//echo "<br /><input type='checkbox' name='classe_" . $id_classe_temp . "' value='yes'";
				echo "<input type='checkbox' name='classe_" . $id_classe_temp . "' id='classe_" . $id_classe_temp . "' value='yes'";
				if (in_array($id_classe_temp, $reg_clazz)){
					echo " checked";
				}
				//echo " />$classe</option>\n";
				echo " onchange='changement();'";
				echo " /><label for='classe_".$id_classe_temp."' style='cursor: pointer;'>$classe</label>\n";
				if (in_array($id_classe_temp, $reg_clazz)){
					// Pour contr�ler les suppressions de classes.
					// On conserve la liste des classes pr�c�demment coch�es:
					echo "<input type='hidden' name='precclasse_".$id_classe_temp."' value='y' />\n";
				}
				echo "<br />\n";
			}
			$i++;
		}
		*/
		//echo "</p>\n";
		echo "</td>\n";
		echo "</tr>\n";
		echo "</table>\n";

		// On ne propose de fusionner le groupe avec un/des groupes existants que si le groupe n'a pas d�j� de notes,...
		// ... NON: On fera le test sur les groupes � y associer seulement.
		//          Ce sont les autres groupes qui seraient susceptibles de voir leurs notes disparaitre
		echo "<p>[-> <a href='fusion_group.php?id_classe=".$id_classe."&amp;id_groupe=".$id_groupe."'>fusionner le groupe avec un ou des groupes existants</a>]";

		echo "</p>\n";

	} else {
		echo "<p>Aucune classe d�finie !</p>\n";
	}
}

//-- Fin classes


?>



<!--p>S�lectionnez la mati�re enseign�e � ce groupe :-->
<?php
/*
$query = mysql_query("SELECT matiere, nom_complet FROM matieres ORDER BY matiere");
$nb_mat = mysql_num_rows($query);

echo "<select name='matiere' size='1'>\n";

for ($i=0;$i<$nb_mat;$i++) {
	$matiere = mysql_result($query, $i, "matiere");
	$nom_matiere = mysql_result($query, $i, "nom_complet");
	echo "<option value='" . $matiere . "'";
	if ($reg_matiere == $matiere) echo " SELECTED";
	//echo ">" . $nom_matiere . "</option>\n";
	echo ">" . htmlentities($nom_matiere) . "</option>\n";
}
echo "</select>\n";
//echo "</p>\n";
*/
echo "</div>\n";
// Edition des professeurs
echo "<div style='width: 45%; float: right;'>\n";

//=================================================
echo "<p>S�lectionnez la mati�re enseign�e � ce groupe : ";

$query = mysql_query("SELECT matiere, nom_complet FROM matieres ORDER BY matiere");
$nb_mat = mysql_num_rows($query);

echo "<select name='matiere' size='1'";
echo " onchange='changement();'";
echo ">\n";

for ($i=0;$i<$nb_mat;$i++) {
	$matiere = mysql_result($query, $i, "matiere");
	$nom_matiere = mysql_result($query, $i, "nom_complet");
	echo "<option value='" . $matiere . "'";
	if ($reg_matiere == $matiere) echo " SELECTED";
	//echo ">" . $nom_matiere . "</option>\n";
	//echo ">" . html_entity_decode($nom_matiere) . "</option>\n";
	echo ">" . htmlentities($nom_matiere) . "</option>\n";
}
echo "</select>\n";
echo "</p>\n";
//=================================================

echo "<p>Cochez les professeurs qui participent � cet enseignement : </p>\n";


//$calldata = mysql_query("SELECT u.login, u.nom, u.prenom, u.civilite FROM utilisateurs u, j_professeurs_matieres j WHERE (j.id_matiere = '$reg_matiere' and j.id_professeur = u.login and u.etat!='inactif') ORDER BY u.login");
$sql="SELECT u.login, u.nom, u.prenom, u.civilite, u.statut FROM utilisateurs u, j_professeurs_matieres j WHERE (j.id_matiere = '$reg_matiere' and j.id_professeur = u.login and u.etat!='inactif') ORDER BY u.nom;";
//echo "$sql<br />";
$calldata = mysql_query($sql);
$nb = mysql_num_rows($calldata);
$prof_list = array();
$prof_list["list"] = array();
for ($i=0;$i<$nb;$i++) {
	$prof_login = mysql_result($calldata, $i, "login");
	$prof_nom = mysql_result($calldata, $i, "nom");
	$prof_prenom = mysql_result($calldata, $i, "prenom");
	$civilite = mysql_result($calldata, $i, "civilite");
	$prof_statut = mysql_result($calldata, $i, "statut");

	$prof_list["list"][] = $prof_login;
	//$prof_list["users"][$prof_login] = array("login" => $prof_login, "nom" => $prof_nom, "prenom" => $prof_prenom, "civilite" => $civilite);
	$prof_list["users"][$prof_login] = array("login" => $prof_login, "nom" => $prof_nom, "prenom" => $prof_prenom, "civilite" => $civilite, "statut" => $prof_statut);
}

if (count($prof_list["list"]) == "0") {
	echo "<p><font color='red'>ERREUR !</font> Aucun professeur n'a �t� d�fini comme comp�tent dans la mati�re consid�r�e.</p>\n";
} else {
	$total_profs = array_merge($prof_list["list"], $reg_professeurs);
	$total_profs = array_unique($total_profs);

	$p = 0;
	echo "<table class='boireaus'>\n";
	$alt=1;
	$temoin_nettoyage_requis='n';
	foreach($total_profs as $prof_login) {
		$alt=$alt*(-1);
		if($prof_list["users"][$prof_login]["statut"]=='professeur') {
			echo "<tr class='lig$alt'>\n";
			echo "<td>\n";
			echo "<input type='hidden' name='proflogin_".$p."' value='".$prof_login."' />\n";
			echo "<input type='checkbox' name='prof_".$p."' id='prof_".$p."' ";
			echo "onchange='changement();'";
			if (in_array($prof_login, $reg_professeurs)) {
				if (array_key_exists($prof_login, $current_group["profs"]["users"])){
					echo " checked />\n";
					echo "</td>\n";
					echo "<td style='text-align:left;'>\n";
					echo "<label for='prof_".$p."' style='cursor: pointer;'>". $current_group["profs"]["users"][$prof_login]["civilite"] . " " .
						$current_group["profs"]["users"][$prof_login]["prenom"] . " " .
						$current_group["profs"]["users"][$prof_login]["nom"] . "</label><br />\n";
				} else {
					echo " checked />\n";
					echo "</td>\n";
					echo "<td style='text-align:left;'>\n";
					echo "<label for='prof_".$p."' style='cursor: pointer;'>". $prof_list["users"][$prof_login]["civilite"] . " " .
						$prof_list["users"][$prof_login]["prenom"] . " " .
						$prof_list["users"][$prof_login]["nom"] . "</label><br />\n";
				}
			} else {
				echo " />\n";
				echo "</td>\n";
				echo "<td style='text-align:left;'>\n";
				echo "<label for='prof_".$p."' style='cursor: pointer;'>". $prof_list["users"][$prof_login]["civilite"] . " " .
						$prof_list["users"][$prof_login]["prenom"] . " " .
						$prof_list["users"][$prof_login]["nom"] . "</label><br />\n";
			}
			echo "</td>\n";
			echo "</tr>\n";
			$p++;
		}
		else {
			echo "<tr class='lig$alt'>\n";
			echo "<td>\n";
			echo "&nbsp;&nbsp;";
			echo "</td>\n";
			echo "<td style='text-align:left;'>\n";
			echo "<b>ANOMALIE</b>&nbsp;:";
			echo " " . $prof_list["users"][$prof_login]["nom"] . " " . $prof_list["users"][$prof_login]["prenom"];
			echo " (<i style='color:red'>compte ".$prof_list["users"][$prof_login]["statut"]."</i>)";
			echo "<br />\n";
			$temoin_nettoyage_requis='y';
			//echo "Un <a href='../utilitaires/clean_tables.php'>nettoyage des tables</a> s'impose.";
			echo "</td>\n";
			echo "</tr>\n";
		}
	}
	echo "</table>\n";
	if($temoin_nettoyage_requis!='n') {
		echo "Un <a href='../utilitaires/clean_tables.php'>nettoyage des tables</a> s'impose.";
	}
}

echo "</div>\n";
echo "<div style='float: left; width: 100%'>\n";
echo "<input type='hidden' name='is_posted' value='1' />\n";
echo "<input type='hidden' name='mode' value='" . $mode . "' />\n";
echo "<input type='hidden' name='id_groupe' value='" . $id_groupe . "' />\n";
//============================
// MODIF: boireaus
if(isset($chemin_retour)){
	echo "<input type='hidden' name='chemin_retour' value='$chemin_retour' />\n";
}
if(isset($ancre)){
	echo "<input type='hidden' name='ancre' value='$ancre' />\n";
}
echo "<p align='center'><input type='submit' value='Valider' /></p>\n";
echo "</div>\n";
echo "</div>\n";

echo "</form>\n";

require("../lib/footer.inc.php");
?>
