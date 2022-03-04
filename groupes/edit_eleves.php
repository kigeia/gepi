<?php
/*
 *
 * Copyright 2001, 2012 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun
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

if(($_SESSION['statut']=='cpe')&&(!getSettingAOui('CpeEditElevesGroupes'))) {
	header("Location: ../accueil.php?msg=Accès non autorisé");
	die();
}
elseif(($_SESSION['statut']=='scolarite')&&(!getSettingAOui('ScolEditElevesGroupes'))) {
	header("Location: ../accueil.php?msg=Accès non autorisé");
	die();
}

// Initialisation des variables utilisées dans le formulaire

$id_classe = isset($_GET['id_classe']) ? $_GET['id_classe'] : (isset($_POST['id_classe']) ? $_POST["id_classe"] : NULL);
$id_groupe = isset($_GET['id_groupe']) ? $_GET['id_groupe'] : (isset($_POST['id_groupe']) ? $_POST["id_groupe"] : NULL);
$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : (isset($_POST['order_by']) ? $_POST["order_by"] : "classe");

if(isset($_POST['id_groupe_reference'])) {
	$_SESSION['id_groupe_reference_copie_assoc']=$_POST['id_groupe_reference'];
}

function debug_edit_eleves($texte) {
	$debug_edit_eleves=0;
	if($debug_edit_eleves==1) {
		echo "<span style='color:green'>$texte</span><br />\n";
	}
}

debug_edit_eleves("id_groupe=$id_groupe");
if (!is_numeric($id_groupe)) $id_groupe = 0;
debug_edit_eleves("id_groupe=$id_groupe");
$current_group = get_group($id_groupe);
$reg_nom_groupe = $current_group["name"];
debug_edit_eleves("reg_nom_groupe=$reg_nom_groupe");
$reg_nom_complet = $current_group["description"];
$reg_matiere = $current_group["matiere"]["matiere"];

if(!isset($id_classe)) {
	$id_classe=$current_group['classes']['list'][0];
}

$reg_id_classe = $id_classe;
$reg_clazz = $current_group["classes"]["list"];
$reg_professeurs = (array)$current_group["profs"]["list"];
$mode = isset($_GET['mode']) ? $_GET['mode'] : "groupe";

if (count($current_group["classes"]["list"]) > 1) {
	$multiclasses = true;
} else {
	$multiclasses = false;
}

$reg_eleves = array();
foreach ($current_group["periodes"] as $period) {
	//echo '$period["num_periode"]='.$period["num_periode"]."<br />";
	if($period["num_periode"]!=""){
		$reg_eleves[$period["num_periode"]] = $current_group["eleves"][$period["num_periode"]]["list"];
		//$msg.="\$reg_eleves[\$period[\"num_periode\"]]=\$reg_eleves[".$period["num_periode"]."]=".$reg_eleves[$period["num_periode"]]."<br />";
	}
}
$msg = null;
if (isset($_POST['is_posted'])) {
	check_token();

	$error = false;

	// On vide les signalements par un prof lors de l'enregistrement
	$sql="DELETE FROM j_signalement WHERE id_groupe='$id_groupe' AND nature='erreur_affect';";
	//echo "$sql<br />";
	$del=mysql_query($sql);

	// Elèves
	$sql="SELECT DISTINCT login FROM j_eleves_groupes WHERE id_groupe='$id_groupe' ORDER BY login";
	debug_edit_eleves($sql);
	$result_liste_eleves_du_grp=mysql_query($sql);
	while($lig_eleve=mysql_fetch_object($result_liste_eleves_du_grp)){
		$temoin_nettoyage="";
		foreach($current_group["periodes"] as $period) {
			//$sql="SELECT * FROM matieres_notes WHERE login='$lig_eleve->login' AND id_groupe='$id_groupe' AND periode='$period'";
			$sql="SELECT * FROM matieres_notes WHERE login='$lig_eleve->login' AND id_groupe='$id_groupe' AND periode='".$period['num_periode']."';";
			debug_edit_eleves($sql);
			$res_liste_notes=mysql_query($sql);
			//$sql="SELECT * FROM matieres_appreciations WHERE login='$lig_eleve->login' AND id_groupe='$id_groupe' AND periode='$period'";
			//$sql="SELECT * FROM matieres_appreciations WHERE login='$lig_eleve->login' AND id_groupe='$id_groupe' AND periode='$period'";
			$sql="SELECT * FROM matieres_appreciations WHERE login='$lig_eleve->login' AND id_groupe='$id_groupe' AND periode='".$period['num_periode']."';";
			debug_edit_eleves($sql);
			$res_liste_appreciations=mysql_query($sql);
			if((mysql_num_rows($res_liste_notes)==0)&&(mysql_num_rows($res_liste_appreciations)==0)){
				//$sql="DELETE FROM j_eleves_groupes WHERE id_groupe='$id_groupe' AND login='$lig_eleve->login'";
				//$sql="DELETE FROM j_eleves_groupes WHERE id_groupe='$id_groupe' AND login='$lig_eleve->login' AND periode='$period'";
				$sql="DELETE FROM j_eleves_groupes WHERE id_groupe='$id_groupe' AND login='$lig_eleve->login' AND periode='".$period['num_periode']."';";
				debug_edit_eleves($sql);
				//echo "$sql<br />\n";
				$resultat_nettoyage_initial=mysql_query($sql);
			}
		}
	}

	//=========================
	// AJOUT: boireaus 20071010
	$login_eleve=$_POST['login_eleve'];
	//$setting_coef=$_POST['setting_coef'];
	//=========================

	$reg_eleves = array();

	// On travaille période par période

	$flag = array();
	foreach($current_group["periodes"] as $period) {
		/*
		foreach ($_POST as $key => $value) {
			$pattern = "/^eleve\_" . $period["num_periode"] . "\_/";
			if (preg_match($pattern, $key)) {
				$id = preg_replace($pattern, "", $key);
				$reg_eleves[$period["num_periode"]][] = $id;
				// Settings spécifiques
				$coef = array();
				if (!in_array($id, $flag)) {
					$coef[] = $_POST["setting_coef_".$id];
					$res = set_eleve_groupe_setting($id, $id_groupe, "coef", $coef);
					$flag[] = $id;
				}
			}
		}
		*/
		$reg_eleves[$period["num_periode"]]=array();

		for($i=0;$i<count($login_eleve);$i++) {
			if(isset($_POST['eleve_'.$period["num_periode"].'_'.$i])) {
				$id=$login_eleve[$i];
				$reg_eleves[$period["num_periode"]][] = $id;
				debug_edit_eleves("\$reg_eleves[".$period["num_periode"]."][]=$id");
				// Settings spécifiques
				$coef = array();
				if (!in_array($id, $flag)) {
					$coef[] = $_POST["setting_coef_".$i];
					$res = set_eleve_groupe_setting($id, $id_groupe, "coef", $coef);
					$flag[] = $id;
				}
			}
		}
	}
	$flag = null;

	if (!$error) {
		// pas d'erreur : on continue avec la mise à jour du groupe
		/*
		$msg.="count(\$reg_eleves)=count($reg_eleves)=".count($reg_eleves)."<br />";
		$msg.="count(\$reg_clazz)=count($reg_clazz)=".count($reg_clazz)."<br />";
		$msg.="\$reg_clazz[0]=".$reg_clazz[0]."<br />";
		$msg.="update_group($id_groupe, $reg_nom_groupe, $reg_nom_complet, $reg_matiere, $reg_clazz, $reg_professeurs, $reg_eleves);<br />";
		*/
		//==========================================
		// MODIF: boireaus
		if(count($reg_eleves)!=0){
			$create = update_group($id_groupe, $reg_nom_groupe, $reg_nom_complet, $reg_matiere, $reg_clazz, $reg_professeurs, $reg_eleves);
			debug_edit_eleves("update_group($id_groupe, $reg_nom_groupe, $reg_nom_complet, $reg_matiere, \$reg_clazz, \$reg_professeurs, \$reg_eleves);");
			if (!$create) {
				$msg .= "Erreur lors de la mise à jour du groupe.";
			} else {
				$msg .= "Le groupe a bien été mis à jour.";
			}
		}
		else {
			// Sauf erreur, $reg_eleves est toujours initialisé, on ne passe plus ici.
			$login_eleve=$_POST['login_eleve'];
			debug_edit_eleves("count(\$login_eleve)=".count($login_eleve));
			foreach($current_group["periodes"] as $period) {
				//echo "<!-- \$period[\"num_periode\"]=".$period["num_periode"]." -->\n";
				for($i=0;$i<count($login_eleve);$i++) {
					if (test_before_eleve_removal($login_eleve[$i], $id_groupe, $period["num_periode"])) {
						debug_edit_eleves("test_before_eleve_removal($login_eleve[$i], $id_groupe, ".$period["num_periode"].")");
						//$res = mysql_query("delete from j_eleves_groupes where (id_groupe = '" . $_id_groupe . "' and login = '" . $login_eleve[$i] . "' and periode = '" . $period["num_periode"] . "')");
						$sql="delete from j_eleves_groupes where (id_groupe = '" . $id_groupe . "' and login = '" . $login_eleve[$i] . "' and periode = '" . $period["num_periode"] . "')";
						debug_edit_eleves($sql);
						//echo "<!-- sql=$sql -->\n";
						$res = mysql_query("delete from j_eleves_groupes where (id_groupe = '" . $id_groupe . "' and login = '" . $login_eleve[$i] . "' and periode = '" . $period["num_periode"] . "')");
						if (!$res) $errors = true;
					} else {
						$msg .= "Erreur lors de la suppression de l'élève ayant le login '" . $login_eleve[$i] . "', pour la période '" . $period["num_periode"] . " (des notes ou appréciations existent).<br/>";
					}
				}
			}
		}
		//==========================================
	}

	debug_edit_eleves("id_groupe=$id_groupe");
	$current_group = get_group($id_groupe);
	// On réinitialise $reg_eleves
	$reg_eleves = array();
	foreach ($current_group["periodes"] as $period) {
		if($period["num_periode"]!=""){
			debug_edit_eleves("\$period[\"num_periode\"]=".$period["num_periode"]);
			$reg_eleves[$period["num_periode"]] = $current_group["eleves"][$period["num_periode"]]["list"];
			debug_edit_eleves("\$reg_eleves[".$period["num_periode"]."] = \$current_group[\"eleves\"][".$period["num_periode"]."][\"list\"]");
		}
	}
}

$themessage  = 'Des informations ont été modifiées. Voulez-vous vraiment quitter sans enregistrer ?';
//**************** EN-TETE **************************************
$titre_page = "Gestion des groupes";
require_once("../lib/header.inc.php");
//**************** FIN EN-TETE **********************************

//debug_var();

//=========================
// AJOUT: boireaus 20071010
$nb_periode=$current_group['nb_periode'];
//=========================

$tab_autres_sig=array();
$sql="SELECT DISTINCT id_groupe FROM j_signalement WHERE nature='erreur_affect' AND id_groupe!='$id_groupe';";
//echo "$sql<br />";
$res_autres_sig=mysql_query($sql);
if(mysql_num_rows($res_autres_sig)>0) {
	while($lig_autres_sig=mysql_fetch_object($res_autres_sig)) {
		$tab_autres_sig[]=$lig_autres_sig->id_groupe;
	}
}

$tab_sig=array();
$sql="SELECT * FROM j_signalement WHERE id_groupe='$id_groupe' AND nature='erreur_affect' ORDER BY periode, login;";
//echo "$sql<br />";
$res_sig=mysql_query($sql);
if(mysql_num_rows($res_sig)>0) {
	while($lig_sig=mysql_fetch_object($res_sig)) {
		$tab_sig[$lig_sig->periode][$lig_sig->login]=my_ereg_replace("_"," ",$lig_sig->valeur)." selon ".affiche_utilisateur($lig_sig->declarant,$id_classe);
		//$tab_sig[$lig_sig->periode][]=$lig_sig->login;
	}
}

?>
<script type='text/javascript' language='javascript'>

function CocheCase(boul) {

	nbelements = document.formulaire.elements.length;
	for (i = 0 ; i < nbelements ; i++) {
		if (document.formulaire.elements[i].type =='checkbox') {
			document.formulaire.elements[i].checked = boul ;
		}
	}

}

<?php
//=========================
// MODIF: boireaus 20071006
echo "function CocheLigne(ki) {
	for (var i=1;i<$nb_periode;i++) {
		if(document.getElementById('case_'+i+'_'+ki)){
			document.getElementById('case_'+i+'_'+ki).checked = true;
		}
	}
}
";

echo "function DecocheLigne(ki) {
	for (var i=1;i<$nb_periode;i++) {
		if(document.getElementById('case_'+i+'_'+ki)){
			document.getElementById('case_'+i+'_'+ki).checked = false;
		}
	}
}
";

?>

/*
function CochePeriode() {
    nbParams = CochePeriode.arguments.length;
    for (var i=0;i<nbParams;i++) {
        theElement = CochePeriode.arguments[i];
        if (document.formulaire.elements[theElement])
            document.formulaire.elements[theElement].checked = true;
    }
}

function DecochePeriode() {
    nbParams = DecochePeriode.arguments.length;
    for (var i=0;i<nbParams;i++) {
        theElement = DecochePeriode.arguments[i];
        if (document.formulaire.elements[theElement])
            document.formulaire.elements[theElement].checked = false;
    }
}
//=========================
*/
</script>

<?php

$_SESSION['chemin_retour'] = $_SERVER['REQUEST_URI'];

echo "<script type='text/javascript'>
	// Initialisation
	change='no';
</script>\n";

echo "<div style='float:left;'>";
echo "<form enctype='multipart/form-data' action='edit_eleves.php' name='form_passage_a_un_autre_groupe' method='post'>\n";

echo "<p class='bold'>\n";
if(acces('/groupes/edit_class.php', $_SESSION['statut'])) {
	if(!$multiclasses) {
		echo "<a href='edit_class.php?id_classe=$id_classe'";
		echo " onclick=\"return confirm_abandon (this, change, '$themessage')\"";
		echo "><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>\n";
	}
	else {
		$cpt_tmp_clas=0;
		foreach($current_group['classes']['classes'] as $tmp_id_classe => $tmp_tab_clas_grp) {
			if(	$cpt_tmp_clas>0) {echo " | ";}
			echo "<a href='edit_class.php?id_classe=".$tmp_id_classe."'";
			echo " onclick=\"return confirm_abandon (this, change, '$themessage')\"";
			echo "><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour ".$tmp_tab_clas_grp['classe']."</a>\n";
			$cpt_tmp_clas++;
		}
	}
}
else {
	echo "<a href='../accueil.php'";
	echo " onclick=\"return confirm_abandon (this, change, '$themessage')\"";
	echo "><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>\n";
}

//$sql="SELECT DISTINCT jgc.id_groupe FROM groupes g, j_groupes_classes jgc, j_eleves_groupes jeg WHERE jgc.id_classe='$id_classe' AND jeg.id_groupe=jgc.id_groupe AND g.id=jgc.id_groupe AND jgc.id_groupe!='$id_groupe' ORDER BY g.name;";
$sql="SELECT DISTINCT jgc.id_groupe FROM groupes g, j_groupes_classes jgc WHERE jgc.id_classe='$id_classe' AND g.id=jgc.id_groupe ORDER BY g.name;";
//echo "$sql<br />\n";
$res_grp=mysql_query($sql);
if(mysql_num_rows($res_grp)>1) {
	echo " | ";

	echo "<input type='hidden' name='id_classe' value='$id_classe' />\n";
	echo "<select name='id_groupe' id='id_groupe_a_passage_autre_grp' onchange=\"confirm_changement_grp(change, '$themessage');\">\n";
	$cpt_grp=0;
	$chaine_js=array();
	//echo "<option value=''>---</option>\n";
	$indice_grp_courant=0;
	while($lig_grp=mysql_fetch_object($res_grp)) {

		$tmp_grp=get_group($lig_grp->id_groupe);

		echo "<option value='$lig_grp->id_groupe'";
		if($lig_grp->id_groupe==$id_groupe) {echo " selected";$indice_grp_courant=$cpt_grp;}
		echo ">".$tmp_grp['description']." (".$tmp_grp['name']." en ".$tmp_grp["classlist_string"].")</option>\n";
		$cpt_grp++;
	}
	echo "</select>\n";
	echo " <input type='submit' id='button_submit_passage_autre_groupe' value='Go'>\n";

	echo "<script type='text/javascript'>
	// Initialisation faite plus haut
	//change='no';

	document.getElementById('button_submit_passage_autre_groupe').style.display='none';

	function confirm_changement_grp(thechange, themessage)
	{
		if (!(thechange)) thechange='no';
		if (thechange != 'yes') {
			document.forms['form_passage_a_un_autre_groupe'].submit();
		}
		else{
			var is_confirmed = confirm(themessage);
			if(is_confirmed){
				document.forms['form_passage_a_un_autre_groupe'].submit();
			}
			else{
				document.getElementById('id_groupe_a_passage_autre_grp').selectedIndex=$indice_grp_courant;
			}
		}
	}
</script>\n";

}

echo " | <a href='edit_group.php?id_groupe=$id_groupe&amp;id_classe=".$current_group["classes"]["list"][0]."'";
echo " onclick=\"return confirm_abandon (this, change, '$themessage')\"";
echo ">Éditer l'enseignement</a> ";

if(acces('/groupes/repartition_ele_grp.php', $_SESSION['statut'])) {
	echo " | <a href='repartition_ele_grp.php'";
	echo " onclick=\"return confirm_abandon (this, change, '$themessage')\"";
	echo " title=\"Répartir des élèves entre plusieurs groupes\">Répartir</a> ";
}
echo "</p>";
echo "</form>\n";
echo "</div>\n";

// Formulaire pour passer à un autre groupe de la même matière éventuellement dans une autre classe
$sql="SELECT DISTINCT jgc.id_groupe FROM groupes g, j_groupes_classes jgc, classes c, j_groupes_matieres jgm WHERE jgc.id_classe=c.id AND g.id=jgc.id_groupe AND g.id=jgm.id_groupe AND jgm.id_matiere='".$current_group['matiere']['matiere']."' ORDER BY c.classe, g.name;";
//echo "$sql<br />\n";
$res_grp=mysql_query($sql);
if(mysql_num_rows($res_grp)>1) {

	echo "<div style='float:left;'>";
	echo "<form enctype='multipart/form-data' action='edit_eleves.php' name='form_passage_a_un_autre_groupe2' method='post'>\n";

	echo "<p class='bold'>";

	echo " | ";

	$indice_grp_courant=0;
	echo "<select name='id_groupe' id='id_groupe_a_passage_autre_grp2' onchange=\"confirm_changement_grp2(change, '$themessage');\">\n";
	$cpt_grp=0;
	$chaine_js=array();
	//echo "<option value=''>---</option>\n";
	while($lig_grp=mysql_fetch_object($res_grp)) {

		$tmp_grp=get_group($lig_grp->id_groupe);

		echo "<option value='$lig_grp->id_groupe'";
		if($lig_grp->id_groupe==$id_groupe) {echo " selected";$indice_grp_courant=$cpt_grp;}
		echo ">".$tmp_grp['description']." (".$tmp_grp['name']." en ".$tmp_grp["classlist_string"].")</option>\n";
		$cpt_grp++;
	}
	echo "</select>\n";

	echo " <input type='submit' id='button_submit_passage_autre_groupe2' value='Go'>\n";

	echo "<script type='text/javascript'>

	document.getElementById('button_submit_passage_autre_groupe2').style.display='none';

	function confirm_changement_grp2(thechange, themessage)
	{
		if (!(thechange)) thechange='no';
		if (thechange != 'yes') {
			document.forms['form_passage_a_un_autre_groupe2'].submit();
		}
		else{
			var is_confirmed = confirm(themessage);
			if(is_confirmed){
				document.forms['form_passage_a_un_autre_groupe2'].submit();
			}
			else{
				document.getElementById('id_groupe_a_passage_autre_grp2').selectedIndex=$indice_grp_courant;
			}
		}
	}
</script>\n";

	echo "</p>";

	echo "</form>\n";
	echo "</div>\n";
}

// Formulaire pour passer à un autre groupe avec erreur d'affectation
if(count($tab_autres_sig)>0) {

	echo "<div style='float:left;'>";
	echo "<form enctype='multipart/form-data' action='edit_eleves.php' name='form_passage_a_un_autre_groupe3' method='post'>\n";

	echo "<p class='bold'>";

	echo " | ";

	echo "<span title=\"Des erreurs d'affectation ont été signalées pour un ou des enseignements\">Erreurs:<select name='id_groupe' id='id_groupe_a_passage_autre_grp3' onchange=\"confirm_changement_grp3(change, '$themessage');\">\n";
	$cpt_grp=0;
	$chaine_js=array();
	$indice_grp_courant=0;
	echo "<option value=''>---</option>\n";
	for($loop=0;$loop<count($tab_autres_sig);$loop++) {

		$tmp_grp=get_group($tab_autres_sig[$loop], array('classes'));

		echo "<option value='".$tab_autres_sig[$loop]."'";
		if($tab_autres_sig[$loop]==$id_groupe) {echo " selected";$indice_grp_courant=$cpt_grp;}
		echo ">".$tmp_grp['description']." (".$tmp_grp['name']." en ".$tmp_grp["classlist_string"].")</option>\n";
		$cpt_grp++;
	}
	echo "</select><img src='../images/icons/flag2.gif' width='16' height='16' /></span>\n";

	echo " <input type='submit' id='button_submit_passage_autre_groupe3' value='Go'>\n";

	echo "<script type='text/javascript'>

	document.getElementById('button_submit_passage_autre_groupe3').style.display='none';

	function confirm_changement_grp3(thechange, themessage)
	{
		if (!(thechange)) thechange='no';
		if (thechange != 'yes') {
			document.forms['form_passage_a_un_autre_groupe3'].submit();
		}
		else{
			var is_confirmed = confirm(themessage);
			if(is_confirmed){
				document.forms['form_passage_a_un_autre_groupe3'].submit();
			}
			else{
				document.getElementById('id_groupe_a_passage_autre_grp3').selectedIndex=$indice_grp_courant;
			}
		}
	}
</script>\n";

	echo "</p>";

	echo "</form>\n";
	echo "</div>\n";
}
echo "<div style='clear:both;'></div>\n";

?>

<?php
	echo "<h3>Gérer les élèves de l'enseignement : ";
	echo htmlspecialchars($current_group["description"]) . " (<i>" . $current_group["classlist_string"] . "</i>)";
	echo "</h3>\n";
	//$temp["profs"]["users"][$p_login] = array("login" => $p_login, "nom" => $p_nom, "prenom" => $p_prenom, "civilite" => $civilite);
	if(count($current_group["profs"]["users"])>0){
		echo "<p>Cours dispensé par ";
		$cpt_prof=0;
		foreach($current_group["profs"]["users"] as $tab_prof){
			if($cpt_prof>0){echo ", ";}
			echo casse_mot($tab_prof['prenom'],'majf2')." ".my_strtoupper($tab_prof['nom']);
			$cpt_prof++;
		}
		echo ".</p>\n";
	}

	// Effectifs des classes associées au groupe:
	$tab_eff_clas_grp=array();
	for($loop=0;$loop<count($current_group["classes"]["list"]);$loop++) {
		$tab_eff_clas_grp[$current_group["classes"]["list"][$loop]]=array();

		for($loop_per=1;$loop_per<$current_group["nb_periode"];$loop_per++) {
			$sql="SELECT DISTINCT login FROM j_eleves_classes WHERE id_classe='".$current_group["classes"]["list"][$loop]."' AND periode='".$loop_per."';";
			//echo "$sql<br />";
			$res_compte=mysql_query($sql);
			$tab_eff_clas_grp[$current_group["classes"]["list"][$loop]][$loop_per]=mysql_num_rows($res_compte);
		}
	}

	echo "<div style='float:right; text-align:center;'>\n";

	$sql="SELECT DISTINCT jgc.id_groupe FROM groupes g, j_groupes_classes jgc, j_eleves_groupes jeg WHERE jgc.id_classe='$id_classe' AND jeg.id_groupe=jgc.id_groupe AND g.id=jgc.id_groupe AND jgc.id_groupe!='$id_groupe' ORDER BY g.name;";
	//echo "$sql<br />\n";
	$res_grp_avec_eleves=mysql_query($sql);
	if(mysql_num_rows($res_grp_avec_eleves)>0) {
		echo "<form enctype='multipart/form-data' action='edit_eleves.php' name='form_copie_ele' method='post'>\n";
		echo "<p>\n";
		echo "<select name='choix_modele_copie' id='choix_modele_copie'>\n";
		$cpt_ele_grp=0;
		$chaine_js=array();
		$id_groupe_js=array();
		//echo "<option value=''>---</option>\n";
		while($lig_grp_avec_eleves=mysql_fetch_object($res_grp_avec_eleves)) {

			$tmp_grp=get_group($lig_grp_avec_eleves->id_groupe);

			$temoin_classe_entiere="y";
			for($loop=0;$loop<count($tmp_grp["classes"]["list"]);$loop++) {
				for($loop_per=1;$loop_per<$current_group["nb_periode"];$loop_per++) {
					if((isset($tab_eff_clas_grp[$current_group["classes"]["list"][$loop]][$loop_per]))&&(count($tmp_grp["eleves"][$loop_per]["telle_classe"][$tmp_grp["classes"]["list"][$loop]])!=$tab_eff_clas_grp[$current_group["classes"]["list"][$loop]][$loop_per])) {
						$temoin_classe_entiere="n";
						break;
					}
				}
			}

			$chaine_js[$cpt_ele_grp]="";
			for($loop=0;$loop<count($tmp_grp["eleves"]["all"]["list"]);$loop++) {
				$chaine_js[$cpt_ele_grp].=",\"".$tmp_grp["eleves"]["all"]["list"][$loop]."\"";
			}
			$chaine_js[$cpt_ele_grp]=mb_substr($chaine_js[$cpt_ele_grp],1);

			$id_groupe_js[$cpt_ele_grp]=$lig_grp_avec_eleves->id_groupe;

			echo "<option value='$cpt_ele_grp'";
			if($temoin_classe_entiere=="y") {echo " style='color:grey;' title='Classe(s) entière(s)'";}
			else {echo " title='Sous-groupe'";}
			if((isset($_SESSION['id_groupe_reference_copie_assoc']))&&($_SESSION['id_groupe_reference_copie_assoc']==$lig_grp_avec_eleves->id_groupe)) {echo " selected='true'";}
			echo ">".$tmp_grp['description']." (".$tmp_grp['name']." en ".$tmp_grp["classlist_string"].")</option>\n";
			//echo ">".$tmp_grp['description']." (".$tmp_grp['name']." en ".$tmp_grp["classlist_string"].") ".count($tmp_grp["eleves"][1]["list"])." élèves</option>\n";

			$cpt_ele_grp++;
		}
		echo "</select>\n";
		echo "<br />\n";
		echo "<input type='button' name='Copie' value='Recopie des élèves associés' onclick=\"recopie_grp_ele(document.getElementById('choix_modele_copie').selectedIndex);changement();\" />\n";
		echo "<br />\n";
		echo "<input type='button' name='Copie' value='Copie INVERSE des élèves associés' onclick=\"recopie_inverse_grp_ele(document.getElementById('choix_modele_copie').selectedIndex);changement();\" />\n";
		echo "</p>\n";

		echo "<script type='text/javascript'>\n";
		for($loop=0;$loop<count($chaine_js);$loop++) {
			echo "tab_grp_ele_".$loop."=new Array(".$chaine_js[$loop].");\n";
			echo "id_groupe_js_".$loop."=".$id_groupe_js[$loop].";\n";
		}
		echo "</script>\n";

		echo "</form>\n";
		echo "<br />\n";
	}
?>

<p>
<b>
<a href="javascript:CocheCase(true);changement();">Tout cocher</a> - 
<a href="javascript:CocheCase(false);changement();">Tout décocher</a></b> - <br />

<a href="javascript:CocheFrac(true, 1);changement();">Cocher la première moitié</a> - 
<a href="javascript:CocheFrac(false, 1);changement();">Décocher la première moitié</a> - <br />
<a href="javascript:CocheFrac(true, 2);changement();">Cocher la seconde moitié</a> - 
<a href="javascript:CocheFrac(false, 2);changement();">Décocher la seconde moitié</a> - <br />

<a href="javascript:griser_degriser('griser');changement();">Griser</a> - 
<a href="javascript:griser_degriser('degriser');changement();">Dégriser</a>
</p>

<?php
	echo "</div>\n";
?>


<form enctype="multipart/form-data" action="edit_eleves.php" name="formulaire" method='post'>
<p><input type='submit' value='Enregistrer' /></p>
<?php

echo add_token_field();

// Edition des élèves

echo "<p>Cochez les élèves qui suivent cet enseignement, pour chaque période : </p>\n";

echo "<table border='1' class='boireaus' summary='Suivi de cet enseignement par les élèves en fonction des périodes'>\n";
echo "<tr>\n";
echo "<th><a href='edit_eleves.php?id_groupe=$id_groupe&amp;id_classe=$id_classe&amp;order_by=nom' onclick=\"return confirm_abandon (this, change, '$themessage')\">Nom/Prénom</a></th>\n";
if ($multiclasses) {
	echo "<th><a href='edit_eleves.php?id_groupe=$id_groupe&amp;id_classe=$id_classe&amp;order_by=classe' onclick=\"return confirm_abandon (this, change, '$themessage')\">Classe</a></th>\n";
}

$acces_mes_listes="y";
if(!acces('/groupes/mes_listes.php', $_SESSION['statut'])) {
	$acces_mes_listes="n";
}

foreach ($current_group["periodes"] as $period) {
	if($period["num_periode"]!=""){
		echo "<th>" . $period["nom_periode"];
		if($acces_mes_listes=="y") {
			echo " <a href='mes_listes.php?id_groupe=$id_groupe&amp;periode_num=".$period["num_periode"]."' onclick=\"return confirm_abandon (this, change, '$themessage')\" title='Exporter la liste des élèves de cet enseignement pour la période ".$period["nom_periode"]." au format '><img src='../images/icons/csv.png' width='16' height='16' /></a>";
		}
		echo "</th>\n";
	}
}
echo "<th>&nbsp;</th>";
echo "<th>Coef</th>";
echo "</tr>\n";

$conditions = "e.login = j.login and (";
foreach ($current_group["classes"]["list"] as $query_id_classe) {
	$conditions .= "j.id_classe = '" . $query_id_classe . "' or ";
}
$conditions = mb_substr($conditions, 0, -4);
$conditions .= ") and c.id = j.id_classe";

// Définition de l'ordre de la liste
if ($order_by == "classe") {
	// Classement par classe puis nom puis prénom
	//$order_conditions = "j.id_classe, e.nom, e.prenom";
	$order_conditions = "c.classe, j.id_classe, e.nom, e.prenom";
} elseif ($order_by == "nom") {
	$order_conditions = "e.nom, e.prenom";
}

//=============================
// AJOUT: boireaus
echo "<tr><th>";
//=============================

//=========================
// AJOUT: boireaus 20071010
unset($login_eleve);
//=========================

$calldata = mysql_query("SELECT distinct(j.login), j.id_classe, c.classe, e.nom, e.prenom FROM eleves e, j_eleves_classes j, classes c WHERE (" . $conditions . ") ORDER BY ".$order_conditions);
$nb = mysql_num_rows($calldata);
$eleves_list = array();
$eleves_list["list"]=array();
for ($i=0;$i<$nb;$i++) {
	$e_login = mysql_result($calldata, $i, "login");
	//================================
	// AJOUT: boireaus
	//echo "<input type='hidden' name='login_eleve[$i]' value='$e_login' />\n";
	echo "<input type='hidden' name='login_eleve[$i]' id='login_eleve_$i' value='$e_login' />\n";
	//=========================
	// AJOUT: boireaus 20071010
	$login_eleve[$i]=$e_login;
	//=========================
	//================================
	$e_nom = mysql_result($calldata, $i, "nom");
	$e_prenom = mysql_result($calldata, $i, "prenom");
	$e_id_classe = mysql_result($calldata, $i, "id_classe");
	$classe = mysql_result($calldata, $i, "classe");
	$eleves_list["list"][] = $e_login;
	$eleves_list["users"][$e_login] = array("login" => $e_login, "nom" => $e_nom, "prenom" => $e_prenom, "classe" => $classe, "id_classe" => $e_id_classe);
}
//echo "count(\$eleves_list)=".count($eleves_list)."<br />";
$total_eleves = $eleves_list["list"];
//echo "count(\$total_eleves)=".count($total_eleves)."<br />";

foreach ($current_group["periodes"] as $period) {
	if($period["num_periode"]!=""){
		//$total_eleves = array_merge($total_eleves, (array)$reg_eleves[$period["num_periode"]]);
		if(count($reg_eleves[$period["num_periode"]])>0) {$total_eleves = array_merge($total_eleves, (array)$reg_eleves[$period["num_periode"]]);}
		//echo "count(\$reg_eleves[".$period["num_periode"]."])=".count($reg_eleves[$period["num_periode"]])."<br />";
	}
}
//echo "count(\$total_eleves)=".count($total_eleves)."<br />";
$total_eleves = array_unique($total_eleves);
//echo "count(\$total_eleves)=".count($total_eleves)."<br />";

$elements = array();
foreach ($current_group["periodes"] as $period) {
	$elements[$period["num_periode"]] = null;
	foreach($total_eleves as $e_login) {
		$elements[$period["num_periode"]] .= "'eleve_" . $period["num_periode"] . "_"  . $e_login  . "',";
	}
    $elements[$period["num_periode"]] = mb_substr($elements[$period["num_periode"]], 0, -1);
}

//=============================
// MODIF: boireaus
//echo "<tr><td>&nbsp;</td>";
echo "&nbsp;</td>\n";
//=============================

if ($multiclasses) { echo "<td>&nbsp;</td>\n"; }
echo "\n";
foreach ($current_group["periodes"] as $period) {
	if($period["num_periode"]!=""){
		//echo "<td>";
		//echo "<a href=\"javascript:CochePeriode(" . $elements[$period["num_periode"]] . ")\">Tout</a> <br/> <a href=\"javascript:DecochePeriode(" . $elements[$period["num_periode"]] . ")\">Aucun</a>";
		echo "<th>";
		//=========================
		// MODIF: boireaus 20071010
		//echo "<a href=\"javascript:CochePeriode(" . $elements[$period["num_periode"]] . ")\"><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a> / <a href=\"javascript:DecochePeriode(" . $elements[$period["num_periode"]] . ")\"><img src='../images/disabled.png' width='15' height='15' alt='Tout décocher' /></a>";

		if(count($total_eleves)>0) {
			echo "<a href=\"javascript:CocheColonne(".$period["num_periode"].");changement();griser_degriser(etat_grisage);\"><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' title='Tout cocher' /></a> / <a href=\"javascript:DecocheColonne(".$period["num_periode"].");changement();griser_degriser(etat_grisage);\"><img src='../images/disabled.png' width='15' height='15' alt='Tout décocher' title='Tout décocher' /></a>";

			if($period["num_periode"]>1) {
				echo " / <a href=\"javascript:copieElevesPeriode1(".$period["num_periode"].");changement();griser_degriser(etat_grisage);\"><img src='../images/icons/copy-16.png' width='16' height='16' alt='Copier les affectations de la première période' title='Copier les affectations de la première période' /></a>";
			}
		}
		//=========================
		echo "<br/>Inscrits : " . count($current_group["eleves"][$period["num_periode"]]["list"]);
		echo "</th>\n";
	}
}
echo "<th>\n";
if((isset($tab_sig))&&(count($tab_sig)>0)) {
	echo "<span id='prise_en_compte_signalement_toutes_periodes'>&nbsp;&nbsp;<a href=\"javascript:prise_en_compte_signalement('prise_en_compte_signalement_toutes_periodes');changement();griser_degriser(etat_grisage);\"><img src='../images/icons/flag2.gif' width='16' height='16' alt='Prendre en compte tous les signalements d erreurs pour toutes les périodes.' title='Prendre en compte tous les signalements d erreurs pour toutes les périodes.' /></a></span>";
}
else {
	echo "&nbsp;";
}
echo "</th>
<th>&nbsp;</th>\n";
echo "</tr>\n";

// Marqueurs pour identifier quand on change de classe dans la liste
$prev_classe = 0;
$new_classe = 0;
$empty_td = false;

//=====================================
$chaine_sql_classe="(";
for($i=0;$i<count($current_group["classes"]["list"]);$i++) {
	if($i>0) {$chaine_sql_classe.=" OR ";}
	$chaine_sql_classe.="id_classe='".$current_group["classes"]["list"][$i]."'";
}
$chaine_sql_classe.=")";
//=====================================

$acces_eleve_options="y";
if(!acces('/classes/eleve_options.php', $_SESSION['statut'])) {
	$acces_eleve_options="n";
}

$acces_prepa_conseil_edit_limite="y";
if(!acces('/prepa_conseil/edit_limite.php', $_SESSION['statut'])) {
	$acces_prepa_conseil_edit_limite="n";
}

if(count($total_eleves)>0) {
	$alt=1;
	foreach($total_eleves as $e_login) {

		//=========================
		// Récupération du numéro de l'élève:
		$num_eleve=-1;
		for($i=0;$i<count($login_eleve);$i++){
			if($e_login==$login_eleve[$i]){
				$num_eleve=$i;
				break;
			}
		}
		if($num_eleve!=-1) {

			//=========================
			// AJOUT: boireaus 20080229
			// Test de l'appartenance à plusieurs classes
			$sql="SELECT DISTINCT id_classe FROM j_eleves_classes WHERE login='$e_login';";
			$test_plusieurs_classes=mysql_query($sql);
			if(mysql_num_rows($test_plusieurs_classes)==1) {
				$temoin_eleve_changeant_de_classe="n";
			}
			else {
				$temoin_eleve_changeant_de_classe="y";
			}
			//=========================

			//=========================
			//$new_classe = $eleves_list["users"][$e_login]["id_classe"];
			if(isset($eleves_list["users"][$e_login])) {
				$new_classe = $eleves_list["users"][$e_login]["id_classe"];
			}
			else {
				$new_classe="BIZARRE";
			}
			//echo "$e_login -&gt; $new_classe<br />";

			if ($new_classe != $prev_classe and $order_by == "classe" and $multiclasses) {
				echo "<tr style='background-color: #CCCCCC;'>\n";
				echo "<td colspan='3' style='padding: 5px; font-weight: bold;'>";
				echo "Classe de : " . $eleves_list["users"][$e_login]["classe"];
				echo "</td>\n";
				foreach ($current_group["periodes"] as $period) {
					echo "<td>&nbsp;</td>\n";
				}
				echo "<td>&nbsp;</td>\n";
				echo "</tr>\n";
				$prev_classe = $new_classe;
			}

			$alt=$alt*(-1);
			echo "<tr id='tr_$num_eleve' class='lig$alt white_hover'>\n";
			if (array_key_exists($e_login, $eleves_list["users"])) {
				/*
				echo "<td>" . $eleves_list["users"][$e_login]["prenom"] . " " .
					$eleves_list["users"][$e_login]["nom"] .
					"</td>";
				*/
				echo "<td>";
				if($acces_eleve_options=="y") {
					echo "<a href='../classes/eleve_options.php?login_eleve=$e_login&id_classe=".$eleves_list["users"][$e_login]['id_classe']."' title=\"Consulter les matières suivies par ".$eleves_list["users"][$e_login]["nom"]." ".$eleves_list["users"][$e_login]["prenom"]." en classe de ".$eleves_list["users"][$e_login]["classe"]."\"";
					echo " onclick=\"return confirm_abandon (this, change, '$themessage')\"";
					echo ">";
					echo $eleves_list["users"][$e_login]["nom"];
					echo " ";
					echo $eleves_list["users"][$e_login]["prenom"];
					echo "</a>\n";
				}
				else {
					echo $eleves_list["users"][$e_login]["nom"];
					echo " ";
					echo $eleves_list["users"][$e_login]["prenom"];
				}
				//echo "<pre>".print_r($eleves_list["users"][$e_login])."</pre>";
				echo "</td>\n";

				if ($multiclasses) {echo "<td>" . $eleves_list["users"][$e_login]["classe"] . "</td>\n";}
				echo "\n";
			}
			else {
				/*
				echo "<td>" . $e_login . "</td>" .
					"<td>" . $current_group["eleves"]["users"][$e_login]["prenom"] . " " .
					$current_group["eleves"]["users"][$e_login]["nom"] .
					"</td>";
				*/
				echo "<td>";
				if($new_classe=="BIZARRE"){
					echo "<font color='red'>$e_login</font>";
				}
				else{
					echo "$e_login";
				}
				echo "</td>\n";
				if ($multiclasses) {echo "<td>" . $current_group["eleves"]["users"][$e_login]["classe"] . "</td>\n";}
				echo "\n";
			}
	
	
			foreach ($current_group["periodes"] as $period) {
				if($period["num_periode"]!="") {
					echo "<td align='center'>";
	
					//=========================
					// MODIF: boireaus 20080229
					//$sql="SELECT 1=1 FROM j_eleves_classes WHERE login='$e_login' AND id_classe='".$new_classe."' AND periode='".$period["num_periode"]."'";
					$sql="SELECT 1=1 FROM j_eleves_classes WHERE login='$e_login' AND $chaine_sql_classe AND periode='".$period["num_periode"]."'";
					//=========================
					$res_test=mysql_query($sql);
					if(mysql_num_rows($res_test)>0){
						//=========================
						// MODIF: boireaus 20071010
						//echo "<input type='checkbox' name='eleve_".$period["num_periode"] . "_" . $e_login."' ";
						echo "<input type='checkbox' name='eleve_".$period["num_periode"]."_".$num_eleve."' id='case_".$period["num_periode"]."_".$num_eleve."' ";
						//=========================
						echo " onchange='changement();'";
						if (in_array($e_login, (array)$current_group["eleves"][$period["num_periode"]]["list"])) {
							echo " checked />";
						} else {
							echo " />";
						}


						// Test sur la présence de notes dans cn ou de notes/app sur bulletin
						if (!test_before_eleve_removal($e_login, $current_group['id'], $period["num_periode"])) {
							if($acces_prepa_conseil_edit_limite=="y") {
								echo "<a href='../prepa_conseil/edit_limite.php?choix_edit=2&login_eleve=".$e_login."&id_classe=".$eleves_list["users"][$e_login]["id_classe"]."&periode1=".$period["num_periode"]."&periode2=".$period["num_periode"]."' target='_blank'>";
								echo "<img id='img_bull_non_vide_".$period["num_periode"]."_".$num_eleve."' src='../images/icons/bulletin_16.png' width='16' height='16' title='Bulletin non vide' alt='Bulletin non vide' />";
								echo "</a>";
							}
							else {
								echo "<img id='img_bull_non_vide_".$period["num_periode"]."_".$num_eleve."' src='../images/icons/bulletin_16.png' width='16' height='16' title='Bulletin non vide' alt='Bulletin non vide' />";
							}
						}

						$sql="SELECT DISTINCT id_devoir FROM cn_notes_devoirs cnd, cn_devoirs cd, cn_cahier_notes ccn WHERE (cnd.login = '".$e_login."' AND cnd.statut='' AND cnd.id_devoir=cd.id AND cd.id_racine=ccn.id_cahier_notes AND ccn.id_groupe = '".$current_group['id']."' AND ccn.periode = '".$period["num_periode"]."')";
						$test_cn=mysql_query($sql);
						$nb_notes_cn=mysql_num_rows($test_cn);
						if($nb_notes_cn>0) {
							echo "<img id='img_cn_non_vide_".$period["num_periode"]."_".$num_eleve."' src='../images/icons/cn_16.png' width='16' height='16' title='Carnet de notes non vide: $nb_notes_cn notes' alt='Carnet de notes non vide: $nb_notes_cn notes' />";
							//echo "$sql<br />";
						}

						if((isset($tab_sig[$period["num_periode"]]))&&(isset($tab_sig[$period["num_periode"]][$e_login]))) {
							$info_erreur=$tab_sig[$period["num_periode"]][$e_login];
							echo "<img id='img_erreur_affect_".$period["num_periode"]."_".$num_eleve."' src='../images/icons/flag2.gif' width='17' height='18' title='".$info_erreur."' alt='".$info_erreur."' />";

							//$chaine_sig.=",'case_".$period["num_periode"]."_".$num_eleve."'";
						}

						//=========================
						// AJOUT: boireaus 20080229
						if($temoin_eleve_changeant_de_classe=="y") {
							$sql="SELECT c.classe FROM classes c, j_eleves_classes jec WHERE jec.login='$e_login' AND jec.id_classe=c.id AND jec.periode='".$period["num_periode"]."';";
							$res_classe_ele=mysql_query($sql);
							if(mysql_num_rows($res_classe_ele)>0){
								$lig_tmp=mysql_fetch_object($res_classe_ele);
								echo " $lig_tmp->classe";
							}
						}
						//=========================
					}
					else{
						echo "&nbsp;\n";
						//echo "<input type='hidden' name='eleve_".$period["num_periode"] . "_" . $e_login."' />\n";
					}
					echo "</td>\n";
				}
			}
	
			$elementlist = null;
			foreach ($current_group["periodes"] as $period) {
				if($period["num_periode"]!="") {
					$elementlist .= "'eleve_" . $period["num_periode"] . "_" . $e_login . "',";
				}
			}
			$elementlist = mb_substr($elementlist, 0, -1);
	
			echo "<td><a href=\"javascript:CocheLigne($num_eleve);changement();griser_degriser(etat_grisage);\"><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a> / <a href=\"javascript:DecocheLigne($num_eleve);changement();griser_degriser(etat_grisage);\"><img src='../images/disabled.png' width='15' height='15' alt='Tout décocher' /></a></td>\n";
			$setting = get_eleve_groupe_setting($e_login, $id_groupe, "coef");
			if (!$setting) {$setting = array(null);}
			//echo "<td><input type='text' size='3' name='setting_coef[".$num_eleve."]' value='".$setting[0]."' /></td>\n";
			echo "<td><input type='text' size='3' name='setting_coef_".$num_eleve."' value='".$setting[0]."' onchange='changement();' /></td>\n";
			//=========================
	
			echo "</tr>\n";
		}
	}

	echo "<tr>\n";
	echo "<th>\n";
	echo "&nbsp;\n";
	echo "</th>\n";
	if ($multiclasses) {
		echo "<th>&nbsp;</th>\n";
	}
	echo "\n";
	foreach ($current_group["periodes"] as $period) {
		if($period["num_periode"]!="") {
			echo "<th>";
			if(count($total_eleves)>0) {
				echo "<a href=\"javascript:DecocheColonne_si_bull_et_cn_vide(".$period["num_periode"].");changement();griser_degriser(etat_grisage);\"><img src='../images/icons/wizard.png' width='16' height='16' alt='Décocher les élèves sans note/app sur les bulletin et carnet de notes' title='Décocher les élèves sans note/app sur les bulletin et carnet de notes' /></a>";

				if((isset($tab_sig))&&(count($tab_sig)>0)) {
					echo "<span id='prise_en_compte_signalement_".$period["num_periode"]."'>&nbsp;&nbsp;<a href=\"javascript:prise_en_compte_signalement(".$period["num_periode"].");changement();griser_degriser(etat_grisage);\"><img src='../images/icons/flag2.gif' width='16' height='16' alt='Prendre en compte tous les signalements d erreurs pour la période ".$period["num_periode"]."' title='Prendre en compte tous les signalements d erreurs pour la période ".$period["num_periode"]."' /></a></span>";
				}
			}
			echo "</th>\n";
		}
	}
	echo "<th>";
	if((isset($tab_sig))&&(count($tab_sig)>0)) {
		echo "<span id='prise_en_compte_signalement_toutes_periodes'>&nbsp;&nbsp;<a href=\"javascript:prise_en_compte_signalement('prise_en_compte_signalement_toutes_periodes');changement();griser_degriser(etat_grisage);\"><img src='../images/icons/flag2.gif' width='16' height='16' alt='Prendre en compte tous les signalements d erreurs pour toutes les périodes.' title='Prendre en compte tous les signalements d erreurs pour toutes les périodes.' /></a></span>";
	}
	else {
		echo "&nbsp;";
	}
	echo "</th>\n";
	echo "<th>&nbsp;</th>\n";
	echo "</tr>\n";


	echo "</table>\n";
	echo "<input type='hidden' name='is_posted' value='1' />\n";
	echo "<input type='hidden' name='mode' value='" . $mode . "' />\n";
	echo "<input type='hidden' name='id_groupe' value='" . $id_groupe . "' />\n";
	echo "<input type='hidden' name='id_classe' value='" . $id_classe . "' />\n";

	echo "<input type='hidden' name='id_groupe_reference' id='id_groupe_reference' value='' />\n";

	echo "<p align='center'><input type='submit' value='Enregistrer' /></p>\n";
	
	
	$nb_eleves=count($total_eleves);
	
	echo "<script type='text/javascript'>
	var etat_grisage='griser';

	function griser_degriser(mode) {
		if(mode=='griser') {
			griser_degriser('degriser');

			for (var ki=0;ki<$nb_eleves;ki++) {
				temoin='n';
				for(i=0;i<=".count($current_group["periodes"]).";i++) {
					if(document.getElementById('case_'+i+'_'+ki)){
						if(document.getElementById('case_'+i+'_'+ki).checked == true) {
							temoin='y';
						}
					}
				}

				if(temoin=='n') {
					if(document.getElementById('tr_'+ki)) {
						document.getElementById('tr_'+ki).style.backgroundColor='grey';
					}
				}
			}
		}
		else {
			for (var ki=0;ki<$nb_eleves;ki++) {
				if(document.getElementById('tr_'+ki)) {
					document.getElementById('tr_'+ki).style.backgroundColor='';
				}
			}
		}
		etat_grisage=mode;
	}

	griser_degriser('griser');

	function CocheColonne(i) {
		for (var ki=0;ki<$nb_eleves;ki++) {
			if(document.getElementById('case_'+i+'_'+ki)){
				document.getElementById('case_'+i+'_'+ki).checked = true;
			}
		}
	}

	function DecocheColonne(i) {
		for (var ki=0;ki<$nb_eleves;ki++) {
			if(document.getElementById('case_'+i+'_'+ki)){
				document.getElementById('case_'+i+'_'+ki).checked = false;
			}
		}
	}

	function DecocheColonne_si_bull_et_cn_vide(i) {
		for (var ki=0;ki<$nb_eleves;ki++) {
			if((document.getElementById('case_'+i+'_'+ki))&&(!document.getElementById('img_bull_non_vide_'+i+'_'+ki))&&(!document.getElementById('img_cn_non_vide_'+i+'_'+ki))) {
				document.getElementById('case_'+i+'_'+ki).checked = false;
			}
		}
	}

	function copieElevesPeriode1(num_periode) {
		for (var ki=0;ki<$nb_eleves;ki++) {
			if((document.getElementById('case_1_'+ki))&&(document.getElementById('case_'+num_periode+'_'+ki))) {
				document.getElementById('case_'+num_periode+'_'+ki).checked=document.getElementById('case_1_'+ki).checked;
			}
		}
	}

	function recopie_grp_ele(num) {
		tab=eval('tab_grp_ele_'+num);
		//alert('tab[0]='+tab[0]);

		document.getElementById('id_groupe_reference').value=eval('id_groupe_js_'+num);

		for(j=0;j<$nb_eleves;j++) {
			DecocheLigne(j);
		}
	
		for(i=0;i<tab.length;i++) {
			for(j=0;j<$nb_eleves;j++) {
	
				if(document.getElementById('login_eleve_'+j).value==tab[i]) {
					CocheLigne(j);
				}
			}
		}
	}

	function recopie_inverse_grp_ele(num) {
		tab=eval('tab_grp_ele_'+num);
		//alert('tab[0]='+tab[0]);

		document.getElementById('id_groupe_reference').value=eval('id_groupe_js_'+num);

		for(j=0;j<$nb_eleves;j++) {
			CocheLigne(j);
		}

		for(i=0;i<tab.length;i++) {
			for(j=0;j<$nb_eleves;j++) {
				if(document.getElementById('login_eleve_'+j).value==tab[i]) {
					DecocheLigne(j);
				}
			}
		}
	}
";

	if((isset($tab_sig))&&(count($tab_sig)>0)) {
		echo "
	function prise_en_compte_signalement(num_periode) {
		if(num_periode=='prise_en_compte_signalement_toutes_periodes') {
			for(num_periode=0;num_periode<=".count($current_group["periodes"]).";num_periode++) {
				for(j=0;j<$nb_eleves;j++) {
					if(document.getElementById('img_erreur_affect_'+num_periode+'_'+j)) {
						if(document.getElementById('case_'+num_periode+'_'+j)) {
							if(document.getElementById('case_'+num_periode+'_'+j).checked) {
								document.getElementById('case_'+num_periode+'_'+j).checked=false;
							}
							else {
								document.getElementById('case_'+num_periode+'_'+j).checked=true;
							}
						}
					}
				}
				if(document.getElementById('prise_en_compte_signalement_'+num_periode)) {
					document.getElementById('prise_en_compte_signalement_'+num_periode).style.display='none';
				}
				document.getElementById('prise_en_compte_signalement_toutes_periodes').style.display='none';
			}
		}
		else {
			for(j=0;j<$nb_eleves;j++) {
				if(document.getElementById('img_erreur_affect_'+num_periode+'_'+j)) {
					if(document.getElementById('case_'+num_periode+'_'+j)) {
						if(document.getElementById('case_'+num_periode+'_'+j).checked) {
							document.getElementById('case_'+num_periode+'_'+j).checked=false;
						}
						else {
							document.getElementById('case_'+num_periode+'_'+j).checked=true;
						}
					}
				}
			}
			document.getElementById('prise_en_compte_signalement_'+num_periode).style.display='none';
		}
	}
";
	}

	echo "
function CocheFrac(mode, part) {
	for(i=0;i<$nb_eleves;i++) {
		for(num_periode=0;num_periode<=".count($current_group["periodes"]).";num_periode++) {
			if(document.getElementById('case_'+num_periode+'_'+i)) {
				if(part==1) {
					if(i<$nb_eleves/2) {
						document.getElementById('case_'+num_periode+'_'+i).checked=mode;
					}
				}
				else {
					if(i>=$nb_eleves/2) {
						document.getElementById('case_'+num_periode+'_'+i).checked=mode;
					}
				}
			}
		}
	}
	griser_degriser(etat_grisage);
}
";

	echo "</script>\n";

	echo "<p><br /></p>\n";

	//echo "<a href='javascript:DecocheColonne_si_bull_et_cn_vide(1)'>1</a>";

	echo "<p><i>NOTE&nbsp;:</i></p>\n";

	echo "<p style='margin-left:3em;'>On ne peut désinscrire que des élèves qui n'ont pas de note ni d'appréciation sur les bulletins.<br />En revanche, la présence de notes dans le carnet de notes n'empêche pas la désinscription.</p>\n";
}
else {
	echo "</table>\n";

	echo "<p style='color:red;'>La ou les classes associées à l'enseignement ne comportent encore aucun élève.</p>\n";
}
?>
</form>
<?php require("../lib/footer.inc.php");?>
