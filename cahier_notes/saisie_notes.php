<?php
/*
 * @version: $Id$
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
//==============================
// PREPARATIFS boireaus 20080422
// Pour passer � no_anti_inject comme pour les autres saisies d'appr�ciations
// On indique qu'il faut creer des variables non prot�g�es (voir fonction cree_variables_non_protegees())
$mode_commentaire_20080422="";
//$mode_commentaire_20080422="no_anti_inject";

if($mode_commentaire_20080422=="no_anti_inject") {
	$variables_non_protegees = 'yes';
}
//==============================

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

//On v�rifie si le module est activ�
if (getSettingValue("active_carnets_notes")!='y') {
	die("Le module n'est pas activ�.");
}
unset($id_devoir);
$id_devoir = isset($_POST["id_devoir"]) ? $_POST["id_devoir"] : (isset($_GET["id_devoir"]) ? $_GET["id_devoir"] : NULL);
unset($affiche_message);
$affiche_message = isset($_POST["affiche_message"]) ? $_POST["affiche_message"] : (isset($_GET["affiche_message"]) ? $_GET["affiche_message"] : NULL);

$order_by = isset($_GET['order_by']) ? $_GET['order_by'] : (isset($_POST['order_by']) ? $_POST["order_by"] : "classe");

if ($id_devoir)  {
	$sql="SELECT * FROM cn_devoirs WHERE id ='$id_devoir';";
	//echo "$sql<br />";
	$appel_devoir = mysql_query($sql);
	$nom_devoir = mysql_result($appel_devoir, 0, 'nom_court');
	$ramener_sur_referentiel_dev_choisi=mysql_result($appel_devoir, 0, 'ramener_sur_referentiel');
	$note_sur_dev_choisi=mysql_result($appel_devoir, 0, 'note_sur');

	$sql="SELECT id_conteneur, id_racine FROM cn_devoirs WHERE id = '$id_devoir';";
	$query = mysql_query($sql);
	$id_racine = mysql_result($query, 0, 'id_racine');
	$id_conteneur = mysql_result($query, 0, 'id_conteneur');
} else if ((isset($_POST['id_conteneur'])) or (isset($_GET['id_conteneur']))) {
	$id_conteneur = isset($_POST['id_conteneur']) ? $_POST['id_conteneur'] : (isset($_GET['id_conteneur']) ? $_GET['id_conteneur'] : NULL);
	$query = mysql_query("SELECT id_racine FROM cn_conteneurs WHERE id = '$id_conteneur'");
	if(mysql_num_rows($query)==0) {
		$msg="Le conteneur numero $id_conteneur n'existe pas. C'est une anomalie.";
		header("Location: index.php?msg=$msg");
		die();
	}
	else {
		$id_racine = mysql_result($query, 0, 'id_racine');
	}
} else {
	//debug_var();

	if (($_SESSION['statut']=='professeur')&&(isset($_GET['id_groupe'])&&(isset($_GET['periode_num'])))) {
		//if (isset($id_groupe) and isset($periode_num)) {
		$id_groupe = $_GET['id_groupe'];
		$periode_num = $_GET['periode_num'];

		if (is_numeric($id_groupe) && $id_groupe > 0) {
			$current_group = get_group($id_groupe);

			$login_prof = $_SESSION['login'];
			$appel_cahier_notes = mysql_query("SELECT id_cahier_notes FROM cn_cahier_notes WHERE (id_groupe='$id_groupe' and periode='$periode_num')");
			$nb_cahier_note = mysql_num_rows($appel_cahier_notes);
			if ($nb_cahier_note == 0) {
				$nom_complet_matiere = $current_group["matiere"]["nom_complet"];
				$nom_court_matiere = $current_group["matiere"]["matiere"];
				$reg = mysql_query("INSERT INTO cn_conteneurs SET id_racine='', nom_court='".traitement_magic_quotes($current_group["description"])."', nom_complet='". traitement_magic_quotes($nom_complet_matiere)."', description = '', mode = '2', coef = '1.0', arrondir = 's1', ponderation = '0.0', display_parents = '0', display_bulletin = '1', parent = '0'");
				if ($reg) {
					$id_racine = mysql_insert_id();
					$reg = mysql_query("UPDATE cn_conteneurs SET id_racine='$id_racine', parent = '0' WHERE id='$id_racine'");
					$reg = mysql_query("INSERT INTO cn_cahier_notes SET id_groupe = '$id_groupe', periode = '$periode_num', id_cahier_notes='$id_racine'");
				}
			} else {
				$id_racine = mysql_result($appel_cahier_notes, 0, 'id_cahier_notes');
			}
			$id_conteneur=$id_racine;
		}
		else {
			header("Location: ../logout.php?auto=1");
			die();
		}
	}
	else {
		header("Location: ../logout.php?auto=1");
		die();
	}
}


//Initialisation pour le pdf
$w_pdf=array();
$w1 = "i"; //largeur de la premi�re colonne
$w1b = "d"; //largeur de la colonne "classe" si pr�sente
$w2 = "n"; // largeur des colonnes "notes"
$w3 = "c"; // largeur des colonnes "commentaires"
$header_pdf=array();
$data_pdf=array();

$mode_utf8_pdf=getSettingValue("mode_utf8_visu_notes_pdf");
if($mode_utf8_pdf=="") {$mode_utf8_pdf="n";}

$appel_conteneur = mysql_query("SELECT * FROM cn_conteneurs WHERE id ='$id_conteneur'");
$nom_conteneur = mysql_result($appel_conteneur, 0, 'nom_court');
$mode = mysql_result($appel_conteneur, 0, 'mode');
$arrondir = mysql_result($appel_conteneur, 0, 'arrondir');
$ponderation = mysql_result($appel_conteneur, 0, 'ponderation');
$display_bulletin = mysql_result($appel_conteneur, 0, 'display_bulletin');

// On teste si le carnet de notes appartient bien � la personne connect�e
if (!(Verif_prof_cahier_notes ($_SESSION['login'],$id_racine))) {
	$mess=rawurlencode("Vous tentez de p�n�trer dans un carnet de notes qui ne vous appartient pas !");
	header("Location: index.php?msg=$mess");
	die();
}

//
// On dispose donc pour la suite des trois variables :
// id_racine
// id_conteneur
// id_devoir

$appel_cahier_notes = mysql_query("SELECT * FROM cn_cahier_notes WHERE id_cahier_notes ='$id_racine'");
$id_groupe = mysql_result($appel_cahier_notes, 0, 'id_groupe');
$current_group = get_group($id_groupe);
$id_classe = $current_group["classes"]["list"][0];
$periode_num = mysql_result($appel_cahier_notes, 0, 'periode');
if (count($current_group["classes"]["list"]) > 1) {
	$multiclasses = true;
} else {
	$multiclasses = false;
	$order_by = "nom";
}
include "../lib/periodes.inc.php";

// On teste si la periode est v�rouill�e !
if (($current_group["classe"]["ver_periode"]["all"][$periode_num] <= 1) and (isset($id_devoir)) and ($id_devoir!='') ) {
	$mess=rawurlencode("Vous tentez de p�n�trer dans un carnet de notes dont la p�riode est bloqu�e !");
	header("Location: index.php?msg=$mess");
	die();
}


$matiere_nom = $current_group["matiere"]["nom_complet"];
$matiere_nom_court = $current_group["matiere"]["matiere"];
$nom_classe = $current_group["classlist_string"];


$periode_query = mysql_query("SELECT * FROM periodes WHERE id_classe = '$id_classe' ORDER BY num_periode");
$nom_periode = mysql_result($periode_query, $periode_num-1, "nom_periode");

//
// D�termination des sous-conteneurs
//
$nom_sous_cont = array();
$id_sous_cont  = array();
$coef_sous_cont = array();
$display_bulletin_sous_cont = array();
$nb_sous_cont = 0;
if ($mode==1) {
	// on s'int�resse � tous les conteneurs fils, petit-fils, ...
	sous_conteneurs($id_conteneur,$nb_sous_cont,$nom_sous_cont,$coef_sous_cont,$id_sous_cont,$display_bulletin_sous_cont,'all');
} else {
	// On s'int�resse uniquement au conteneurs fils
	sous_conteneurs($id_conteneur,$nb_sous_cont,$nom_sous_cont,$coef_sous_cont,$id_sous_cont,$display_bulletin_sous_cont,'');
}

//debug_var();
//-------------------------------------------------------------------------------------------------------------------
if (isset($_POST['notes'])) {
	check_token();
	//=======================================================
	// MODIF: boireaus
	// J'ai d�plac� vers le bas l'alert Javascript qui lors d'un import des notes �tait inscrit avant m�me la balise <html>
	// Cela faisait une page HTML non valide.
	$temp = $_POST['notes']." 1";
	//echo "<pre>";
	//echo "\$temp=$temp<br />";
	$temp = my_ereg_replace("\\\\r","\r",$temp);
	$temp = my_ereg_replace("\\\\n","\n",$temp);
	//echo "\$temp=$temp<br />";
	//echo "</pre>";
	$longueur = strlen($temp);
	$i = 0;
	$fin_note = 'yes';
	$indice = $_POST['debut_import']-2;
	$tempo = '';
	if(!isset($note_sur_dev_choisi)) {$note_sur_dev_choisi=20;}
	while (($i < $longueur) and ($indice < $_POST['fin_import'])) {
		$car = substr($temp, $i, 1);
		//echo "<p>\$car='$car'<br />";
		//if (my_ereg ("^[0-9\.\,\a-z\A-Z\-]{1}$", $car)) {
		if (my_ereg('^[0-9.,a-zA-Z-]{1}$', $car)) {
			if (($fin_note=='yes') or ($i == $longueur-1)) {
				$fin_note = 'no';
				if (is_numeric($tempo)) {
					//echo "is_numeric($tempo)<br />";
					if ($tempo <= $note_sur_dev_choisi) {
						$note_import[$indice] = $tempo;
						$indice++;
					} else {
						$note_import[$indice] = "0";
						$indice++;
					}
				} else {
					//echo "NO is_numeric($tempo)<br />";
					$note_import[$indice] = $tempo;
					$indice++;
				}
				$tempo = '';
			}
			$tempo=$tempo.$car;
			//echo "\$tempo='$tempo'<br />";
		} else {
			$fin_note = 'yes';
		}
		//echo "\$fin_note=$fin_note<br />";
		$i++;
	}
}

// Ajout delineau -> fonctionnalit� de copier/coller d'appr�ciations
//-------------------------------------------------------------------------------------------------------------------
if (isset($_POST['appreciations'])) {
	check_token();

	$temp = $_POST['appreciations']." 1";
	$temp = my_ereg_replace("\\\\r","`",$temp);
	$temp = my_ereg_replace("\\\\n","",$temp);
	$temp = unslashes($temp);
 	$longueur = strlen($temp);
	$i = 0;
	$fin_app = 'yes';
	$indice = $_POST['debut_import']-2;
	$tempo = "";
	while (($i < $longueur) and ($indice < $_POST['fin_import'])) {
		$car = substr($temp, $i, 1);
		if (!my_ereg ("^[`]{1}$", $car)) {
			if (($fin_app=='yes') or ($i == $longueur-1)) {
				$fin_app = 'no';
				$appreciations_import[$indice] = $tempo;
				$indice++;
				$tempo = '';
			}
			$tempo=$tempo.$car;
		} else {
  			$fin_app = 'yes';
		}
		$i++;
	}
}
// Fin ajout delineau -> fonctionnalit� de copier/coller d'appr�ciations


if (isset($_POST['is_posted'])) {
	check_token();

	//=========================
	// AJOUT: boireaus 20071010
	$log_eleve=$_POST['log_eleve'];
	$note_eleve=$_POST['note_eleve'];
	if($mode_commentaire_20080422!="no_anti_inject") {
		$comment_eleve=$_POST['comment_eleve'];
	}
	//=========================

	$indice_max_log_eleve=$_POST['indice_max_log_eleve'];

	//for($i=0;$i<count($log_eleve);$i++){
	for($i=0;$i<$indice_max_log_eleve;$i++){
		if(isset($log_eleve[$i])) {
			// La p�riode est-elle ouverte?
			$reg_eleve_login=$log_eleve[$i];
			if(isset($current_group["eleves"][$periode_num]["users"][$reg_eleve_login]["classe"])){
				$id_classe = $current_group["eleves"][$periode_num]["users"][$reg_eleve_login]["classe"];
				if ($current_group["classe"]["ver_periode"][$id_classe][$periode_num] == "N") {
					$note=$note_eleve[$i];
					$elev_statut='';

					//==============================
					// PREPARATIFS boireaus 20080422
					// Pour passer � no_anti_inject comme pour les autres saisies d'appr�ciations
					if($mode_commentaire_20080422!="no_anti_inject") {
						// Probl�me: les accents sont cod�s en HTML...
						$comment=$comment_eleve[$i];
						//$comment=traitement_magic_quotes(corriger_caracteres($comment));
						//$comment=html_entity_decode($comment);
						//$comment=addslashes(my_ereg_replace("&#039;","'",html_entity_decode($comment)));
						//$comment=addslashes(my_ereg_replace("\r\n",'<br />',my_ereg_replace("&#039;","'",html_entity_decode($comment))));
						//$comment=addslashes(my_ereg_replace("\r\n",'<br />',stripslashes(my_ereg_replace("&#039;","'",html_entity_decode($comment)))));
						//$comment=addslashes(nl2br(my_ereg_replace("&#039;","'",html_entity_decode($comment))));
						//$comment=addslashes(my_ereg_replace('(\\\r\\\n)+',"<br />",my_ereg_replace("&#039;","'",html_entity_decode($comment))));
						// Cela fonctionne chez moi avec cette correction (accents, apostrophes et retours � la ligne):
						$comment=addslashes(my_ereg_replace('(\\\r\\\n)+',"\r\n",my_ereg_replace("&#039;","'",html_entity_decode($comment))));
					}
					else {
						if (isset($NON_PROTECT["comment_eleve".$i])){
							$comment = traitement_magic_quotes(corriger_caracteres($NON_PROTECT["comment_eleve".$i]));
						}
						else{
							$comment = "";
						}
						//echo "$i: $comment<br />";
						// Contr�le des saisies pour supprimer les sauts de lignes surnum�raires.
						$comment=my_ereg_replace('(\\\r\\\n)+',"\r\n",$comment);
					}
					//==============================


					if (($note == 'disp')||($note == 'd')) {
						$note = '0';
						$elev_statut = 'disp';
					}
					else if (($note == 'abs')||($note == 'a')) {
						$note = '0';
						$elev_statut = 'abs';
					}
					else if (($note == '-')||($note == 'n')) {
						$note = '0';
						$elev_statut = '-';
					}
					else if (my_ereg ("^[0-9\.\,]{1,}$", $note)) {
						$note = str_replace(",", ".", "$note");
                                                $appel_note_sur = mysql_query("SELECT NOTE_SUR FROM cn_devoirs WHERE id = '$id_devoir'");
                                                $note_sur_verif = mysql_result($appel_note_sur,0 ,'note_sur');
						if (($note < 0) or ($note > $note_sur_verif)) {
							$note = '';
							$elev_statut = 'v';
						}
					}
					else {
						$note = '';
						$elev_statut = 'v';
					}

					$test_eleve_note_query = mysql_query("SELECT * FROM cn_notes_devoirs WHERE (login='$reg_eleve_login' AND id_devoir = '$id_devoir')");
					$test = mysql_num_rows($test_eleve_note_query);
					if ($test != "0") {
						$sql="UPDATE cn_notes_devoirs SET comment='".$comment."', note='$note',statut='$elev_statut' WHERE (login='".$reg_eleve_login."' AND id_devoir='".$id_devoir."');";
						//echo "$sql<br />";
						$register = mysql_query($sql);
					} else {
						$sql="INSERT INTO cn_notes_devoirs SET login='".$reg_eleve_login."', id_devoir='".$id_devoir."',note='".$note."',statut='".$elev_statut."',comment='".$comment."';";
						$register = mysql_query($sql);
					}

				}
			}
		}
	}

	/*
	foreach ($current_group["eleves"][$periode_num]["users"] as $eleve) {
		$reg_eleve_login = $eleve["login"];
		$id_classe = $current_group["eleves"][$periode_num]["users"][$reg_eleve_login]["classe"];
		if ($current_group["classe"]["ver_periode"][$id_classe][$periode_num] == "N") {
			$nom_log = $reg_eleve_login."_note";
			$note = '';
			if (isset($_POST[$nom_log])) $note = $_POST[$nom_log];
			$elev_statut = '';
			$nom_log = $reg_eleve_login."_comment";
			$comment = '';
			if (isset($_POST[$nom_log])) $comment = $_POST[$nom_log];
			if (($note == 'disp')) { $note = '0'; $elev_statut = 'disp';
			} else if (($note == 'abs')) { $note = '0'; $elev_statut = 'abs';
			} else if (($note == '-')) { $note = '0'; $elev_statut = '-';
			} else if (my_ereg ("^[0-9\.\,]{1,}$", $note)) {
				$note = str_replace(",", ".", "$note");
				if (($note < 0) or ($note > 20)) { $note = ''; $elev_statut = '';}
			} else {
				$note = ''; $elev_statut = 'v';
			}
			$test_eleve_note_query = mysql_query("SELECT * FROM cn_notes_devoirs WHERE (login='$reg_eleve_login' AND id_devoir = '$id_devoir')");
			$test = mysql_num_rows($test_eleve_note_query);
			if ($test != "0") {
				$register = mysql_query("UPDATE cn_notes_devoirs SET comment='".$comment."', note='$note',statut='$elev_statut' WHERE (login='".$reg_eleve_login."' AND id_devoir='".$id_devoir."')");
			} else {
				$register = mysql_query("INSERT INTO cn_notes_devoirs SET login='".$reg_eleve_login."', id_devoir='".$id_devoir."',note='".$note."',statut='".$elev_statut."',comment='".$comment."'");
			}
		}
		//$j++;
	}
	*/
	//
	// Mise � jour des moyennes du conteneur et des conteneurs parent, grand-parent, etc...
	//
	$arret = 'no';
	mise_a_jour_moyennes_conteneurs($current_group, $periode_num,$id_racine,$id_conteneur,$arret);

    //==========================================================
    // Ajout d'un test:
    // Si on modifie un devoir alors que des notes ont �t� report�es sur le bulletin, il faut penser � mettre � jour la recopie vers le bulletin.
    $sql="SELECT 1=1 FROM matieres_notes WHERE periode='".$periode_num."' AND id_groupe='".$id_groupe."';";
    $test_bulletin=mysql_query($sql);
    if(mysql_num_rows($test_bulletin)>0) {
        $msg=" ATTENTION: Des notes sont pr�sentes sur le bulletin.<br />Si vous avez modifi� ou ajout� des notes, pensez � mettre � jour la recopie vers le bulletin.";
    }
    //==========================================================

	$affiche_message = 'yes';
}

$message_enregistrement = "Les modifications ont �t� enregistr�es !";
$themessage  = 'Des notes ont �t� modifi�es. Voulez-vous vraiment quitter sans enregistrer ?';
//**************** EN-TETE *****************
$titre_page = "Saisie des notes";
require_once("../lib/header.inc");
//**************** FIN EN-TETE *****************
//debug_var();

unset($_SESSION['chemin_retour']);

//=======================================================
/*
// MODIF: boireaus
// Avertissement redescendu ici pour �viter d'avoir une page web avec une section Javascript avant m�me la balise <html>
// C'est apparemment encore trop haut avec M$Excel+Firefox
if (isset($_POST['notes'])) {
	echo "<script type=\"text/javascript\" language=\"javascript\">
	<!--
	alert(\"Attention, les notes import�es ne sont pas encore enregistr�es dans la base GEPI. Vous devez confirmer l'importation (bouton 'Enregistrer') !\");
	//-->
	</script>\n";
}
*/
//=======================================================
?>
<script type="text/javascript" language=javascript>
chargement = false;
</script>

<?php
//$titre =  "Bo�te : ".$nom_conteneur." (".$nom_periode.")";
if($id_conteneur==$id_racine){
	if($nom_conteneur==""){
		$titre=htmlentities($current_group['description'])." (".$nom_periode.")";
	}
	else{
		$titre=$nom_conteneur." (".$nom_periode.")";
	}
}
else{
	$titre=htmlentities(ucfirst(strtolower(getSettingValue("gepi_denom_boite"))))." : ".$nom_conteneur." (".$nom_periode.")";
}

//$titre_pdf = urlencode($titre);
//$titre_pdf = urlencode(traite_accents_utf8($titre));
$titre_pdf = urlencode(traite_accents_utf8(html_entity_decode($titre)));
if ($id_devoir != 0) {$titre .= " - SAISIE";} else {$titre .= " - VISUALISATION";}

echo "<script type=\"text/javascript\" language=\"javascript\">";
if (isset($_POST['debut_import'])) {
	$temp = $_POST['debut_import']-1;
	if ((isset($note_import[$temp])) and ($note_import[$temp] != '')) {echo "change = 'yes';";} else {echo "change = 'no';";}
} else {
	echo "change = 'no';";
}
echo "</script>";


// D�termination du nombre de devoirs � afficher
$appel_dev = mysql_query("select * from cn_devoirs where (id_conteneur='$id_conteneur' and id_racine='$id_racine') order by date");
$nb_dev  = mysql_num_rows($appel_dev);

// D�termination des noms et identificateurs des devoirs
$j = 0;
while ($j < $nb_dev) {
	$nom_dev[$j] = mysql_result($appel_dev, $j, 'nom_court');
	$id_dev[$j] = mysql_result($appel_dev, $j, 'id');
	$coef[$j] = mysql_result($appel_dev, $j, 'coef');
	$note_sur[$j] = mysql_result($appel_dev, $j, 'note_sur');
	$ramener_sur_referentiel[$j] = mysql_result($appel_dev, $j, 'ramener_sur_referentiel');
	$facultatif[$j] = mysql_result($appel_dev, $j, 'facultatif');
	$display_parents[$j] = mysql_result($appel_dev, $j, 'display_parents');
	$date = mysql_result($appel_dev, $j, 'date');
	$annee = substr($date,0,4);
	$mois =  substr($date,5,2);
	$jour =  substr($date,8,2);
	$display_date[$j] = $jour."/".$mois."/".$annee;
	$j++;
}

//echo "<form enctype=\"multipart/form-data\" name= \"form1\" action=\"index.php\" method=\"get\">\n";
echo "<form enctype=\"multipart/form-data\" name= \"form1\" action=\"saisie_notes.php\" method=\"get\">\n";
echo "<p class='bold'>\n";
echo "<a href=\"../accueil.php\" onclick=\"return confirm_abandon (this, change, '$themessage')\"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour accueil </a>|";
echo "<a href='index.php'  onclick=\"return confirm_abandon (this, change, '$themessage')\"> Mes enseignements </a>|";




if(($_SESSION['statut']=='professeur')||($_SESSION['statut']=='secours')) {
	if($_SESSION['statut']=='professeur') {
		$login_prof_groupe_courant=$_SESSION["login"];
	}
	else {
		$tmp_current_group=get_group($id_groupe);

		$login_prof_groupe_courant=$tmp_current_group["profs"]["list"][0];
	}

	$tab_groups = get_groups_for_prof($login_prof_groupe_courant,"classe puis mati�re");

	if(!empty($tab_groups)) {

		$chaine_options_classes="";

		$num_groupe=-1;
		$nb_groupes_suivies=count($tab_groups);

		//echo "count(\$tab_groups)=".count($tab_groups)."<br />";

		$id_grp_prec=0;
		$id_grp_suiv=0;
		$temoin_tmp=0;
		//foreach($tab_groups as $tmp_group) {
		for($loop=0;$loop<count($tab_groups);$loop++) {
			// On ne retient que les groupes qui ont un nombre de p�riodes au moins �gal � la p�riode s�lectionn�e
			if($tab_groups[$loop]["nb_periode"]>=$periode_num) {
				if($tab_groups[$loop]['id']==$id_groupe){
					$num_groupe=$loop;

					//$chaine_options_classes.="<option value='".$tab_groups[$loop]['id']."' selected='true'>".$tab_groups[$loop]['name']." (".$tab_groups[$loop]['classlist_string'].")</option>\n";
					$chaine_options_classes.="<option value='".$tab_groups[$loop]['id']."' selected='true'>".$tab_groups[$loop]['description']." (".$tab_groups[$loop]['classlist_string'].")</option>\n";

					$temoin_tmp=1;
					if(isset($tab_groups[$loop+1])){
						$id_grp_suiv=$tab_groups[$loop+1]['id'];

						//$chaine_options_classes.="<option value='".$tab_groups[$loop+1]['id']."'>".$tab_groups[$loop+1]['name']." (".$tab_groups[$loop+1]['classlist_string'].")</option>\n";
					}
					else{
						$id_grp_suiv=0;
					}
				}
				else {
					//$chaine_options_classes.="<option value='".$tab_groups[$loop]['id']."'>".$tab_groups[$loop]['name']." (".$tab_groups[$loop]['classlist_string'].")</option>\n";
					$chaine_options_classes.="<option value='".$tab_groups[$loop]['id']."'>".$tab_groups[$loop]['description']." (".$tab_groups[$loop]['classlist_string'].")</option>\n";
				}

				if($temoin_tmp==0){
					$id_grp_prec=$tab_groups[$loop]['id'];

					//$chaine_options_classes.="<option value='".$tab_groups[$loop]['id']."'>".$tab_groups[$loop]['name']." (".$tab_groups[$loop]['classlist_string'].")</option>\n";
				}
			}
		}
		// =================================

		/*
		if(isset($id_grp_prec)){
			if($id_grp_prec!=0){
				echo " | <a href='".$_SERVER['PHP_SELF']."?id_groupe=$id_grp_prec&amp;periode_num=$periode_num";
				echo "' onclick=\"return confirm_abandon (this, change, '$themessage')\">Enseignement pr�c�dent</a>";
			}
		}
		*/

		if(($chaine_options_classes!="")&&($nb_groupes_suivies>1)) {

			echo "<script type='text/javascript'>
	// Initialisation
	change='no';

	function confirm_changement_classe(thechange, themessage)
	{
		//alert('thechange='+thechange+' '+document.getElementById('id_groupe').selectedIndex+' '+document.getElementById('id_groupe').options[document.getElementById('id_groupe').selectedIndex].value);
		if (!(thechange)) thechange='no';
		if (thechange != 'yes') {
			document.form1.submit();
		}
		else {
			var is_confirmed = confirm(themessage);
			if(is_confirmed){
				document.form1.submit();
			}
			else{
				document.getElementById('id_groupe').selectedIndex=$num_groupe;
			}
		}
	}
</script>\n";

			echo "<input type='hidden' name='periode_num' value='$periode_num' />\n";
			//echo " | <select name='id_classe' onchange=\"document.forms['form1'].submit();\">\n";
			echo "P�riode $periode_num: <select name='id_groupe' id='id_groupe' onchange=\"confirm_changement_classe(change, '$themessage');\">\n";
			echo $chaine_options_classes;
			echo "</select> | \n";
		}

		/*
		if(isset($id_grp_suiv)){
			if($id_grp_suiv!=0){
				echo " | <a href='".$_SERVER['PHP_SELF']."?id_groupe=$id_grp_suiv&amp;periode_num=$periode_num";
				echo "' onclick=\"return confirm_abandon (this, change, '$themessage')\">Enseignement suivant</a>";
				}
		}
		*/
	}
	// =================================
}

// Recuperer la liste des cahiers de notes
$sql="SELECT * FROM cn_cahier_notes ccn where id_groupe='$id_groupe' ORDER BY periode;";
$res_cn=mysql_query($sql);
if(mysql_num_rows($res_cn)>1) {
	// On ne propose pas de champ SELECT pour un seul canier de notes
	$chaine_options_periodes="";
	while($lig_cn=mysql_fetch_object($res_cn)) {
		$chaine_options_periodes.="<option value='$lig_cn->id_cahier_notes'";
		if($lig_cn->periode==$periode_num) {$chaine_options_periodes.=" selected='true'";}
		$chaine_options_periodes.=">$lig_cn->periode</option>\n";
	}

	$index_num_periode=$periode_num-1;

	echo "<script type='text/javascript'>
	// Initialisation
	change='no';

	function confirm_changement_periode(thechange, themessage)
	{
		if (!(thechange)) thechange='no';
		if (thechange != 'yes') {
			document.getElementById('form1b_id_conteneur').value=document.getElementById('id_conteneur').options[document.getElementById('id_conteneur').selectedIndex].value;
			document.form1b.submit();
		}
		else{
			var is_confirmed = confirm(themessage);
			if(is_confirmed){
				document.getElementById('form1b_id_conteneur').value=document.getElementById('id_conteneur').options[document.getElementById('id_conteneur').selectedIndex].value;
				document.form1b.submit();
			}
			else{
				document.getElementById('id_conteneur').selectedIndex=$index_num_periode;
			}
		}
	}
</script>\n";

	//echo " | <select name='id_classe' onchange=\"document.forms['form1'].submit();\">\n";
	echo "<span title='Acc�der au cahier de notes de la p�riode (ne sont propos�es que les p�riodes pour lesquelles le cahier de notes a �t� initialis�)'>P�riode </span><select name='tmp_id_conteneur' id='id_conteneur' onchange=\"confirm_changement_periode(change, '$themessage');\">\n";
	echo $chaine_options_periodes;
	echo "</select> | \n";

}

echo "<a href=\"index.php?id_racine=$id_racine\" onclick=\"return confirm_abandon (this, change, '$themessage')\"> Mes �valuations </a>|";

if((isset($id_devoir))&&($id_devoir!=0)) {echo "<a href=\"saisie_notes.php?id_conteneur=$id_racine\" onclick=\"return confirm_abandon (this, change, '$themessage')\"> Visualisation du CN </a>|";}

if ($current_group["classe"]["ver_periode"]["all"][$periode_num] >= 2) {
	//echo "<a href='add_modif_conteneur.php?id_racine=$id_racine&amp;mode_navig=retour_saisie&amp;id_retour=$id_conteneur' onclick=\"return confirm_abandon (this, change,'$themessage')\">Cr�er une bo�te</a>|";
	echo "<a href='add_modif_conteneur.php?id_racine=$id_racine&amp;mode_navig=retour_saisie&amp;id_retour=$id_conteneur' onclick=\"return confirm_abandon (this, change,'$themessage')\"> Cr�er un";

	if(getSettingValue("gepi_denom_boite_genre")=='f'){echo "e";}

	echo " ".htmlentities(strtolower(getSettingValue("gepi_denom_boite")))." </a>|";

	echo "<a href='add_modif_dev.php?id_conteneur=$id_racine&amp;mode_navig=retour_saisie&amp;id_retour=$id_conteneur' onclick=\"return confirm_abandon (this, change,'$themessage')\"> Cr�er une �valuation </a>|";
}
//echo "<a href=\"../fpdf/imprime_pdf.php?titre=$titre_pdf&amp;id_groupe=$id_groupe&amp;periode_num=$periode_num\" target=\"_blank\" onclick=\"return VerifChargement()\">Imprimer au format PDF</a>|";
//echo "<a href=\"../fpdf/imprime_pdf.php?titre=$titre_pdf&amp;id_groupe=$id_groupe&amp;periode_num=$periode_num&amp;nom_pdf_en_detail=oui\" target=\"_blank\" onclick=\"return VerifChargement()\">Imprimer au format PDF</a>|";
echo "<a href=\"../fpdf/imprime_pdf.php?titre=$titre_pdf&amp;id_groupe=$id_groupe&amp;periode_num=$periode_num&amp;nom_pdf_en_detail=oui\" onclick=\"return VerifChargement()\"> Imprimer au format PDF </a>|";

//echo "<a href=\"../groupes/signalement_eleves.php?id_groupe=$id_groupe&amp;id_conteneur=$id_conteneur\"> Signaler des erreurs d'affectation</a>";
echo "<a href=\"../groupes/signalement_eleves.php?id_groupe=$id_groupe&amp;chemin_retour=../cahier_notes/saisie_notes.php?id_conteneur=$id_conteneur\"> Signaler des erreurs d'affectation</a>";
echo "</p>\n";
echo "</form>\n";


// Affichage ou non les colonnes "commentaires"
// Affichage ou non de tous les devoirs
if (isset($_POST['ok'])) {
	if (isset($_POST['affiche_comment'])) {
		$_SESSION['affiche_comment'] = 'no';
	} else {
		$_SESSION['affiche_comment'] = 'yes';
	}
	if (isset($_POST['affiche_tous'])) {
		$_SESSION['affiche_tous'] = 'yes';
	} else {
		$_SESSION['affiche_tous'] = 'no';
	}

}
if (!isset($_SESSION['affiche_comment'])) {$_SESSION['affiche_comment'] = 'yes';}
if (!isset($_SESSION['affiche_tous'])) {$_SESSION['affiche_tous'] = 'no';}
$nb_dev_sous_cont = 0;

// Premier formulaire pour masquer ou non les colonnes "commentaires" non vides des �valuations verrouill�es
if ($id_devoir == 0) {
	//echo "<form enctype=\"multipart/form-data\" action=\"saisie_notes.php\" method=post name=\"form1\">\n";
	echo "<form enctype=\"multipart/form-data\" action=\"saisie_notes.php\" method=post name=\"form1b\">\n";
	echo "<fieldset style=\"padding-top: 0px; padding-bottom: 0px;  margin-left: 0px; margin-right: 100px;\">\n";
	echo "<table summary='Param�tres'><tr><td>Masquer les colonnes \"commentaires\" non vides (mode visualisation uniquement) :
	</td><td><input type=\"checkbox\" name=\"affiche_comment\"  ";
	if ($_SESSION['affiche_comment'] != 'yes') {echo "checked";}
	echo " /></td><td><input type=\"submit\" name=\"ok\" value=\"OK\" /></td></tr>\n";
	$nb_dev_sous_cont = mysql_num_rows(mysql_query("select d.id from cn_devoirs d, cn_conteneurs c where (d.id_conteneur = c.id and c.parent='$id_conteneur')"));
	if ($nb_dev_sous_cont != 0) {
		//echo "<tr><td>Afficher les �valuations des \"sous-bo�tes\" : </td><td><input type=\"checkbox\" name=\"affiche_tous\"  ";
		echo "<tr><td>Afficher les �valuations des \"sous-".htmlentities(strtolower(getSettingValue("gepi_denom_boite")))."s\" : </td><td><input type=\"checkbox\" name=\"affiche_tous\"  ";
		if ($_SESSION['affiche_tous'] == 'yes') {echo "checked";}
		echo " /></td><td></td></tr>\n";
	}
	echo "</table></fieldset>\n";
	echo "<input type='hidden' name='id_conteneur' id='form1b_id_conteneur' value=\"".$id_conteneur."\" />\n";
	echo "<input type='hidden' name='id_devoir' value=\"".$id_devoir."\" />\n";
	echo "</form>\n";
}
else {
	// Formulaire destin� � permettre via javascript de passer � une autre p�riode... on acc�de alors au mode Visualisation parce que le $id_devoir n'existe pas dans une autre p�riode.
	echo "<form enctype=\"multipart/form-data\" action=\"saisie_notes.php\" method=post name=\"form1b\">\n";
	echo "<input type='hidden' name='id_conteneur' id='form1b_id_conteneur' value=\"".$id_conteneur."\" />\n";
	//echo "<input type='hidden' name='id_devoir' value=\"".$id_devoir."\" />\n";
	echo "</form>\n";
}
// Fin du premier formulaire

// Construction de la variable $detail qui affiche dans un pop-up le mode de calcul de la moyenne
$detail = "Mode de calcul de la moyenne :\\n";
$detail = $detail."La moyenne s\\'effectue sur les colonnes rep�r�es par les cellules de couleur violette.\\n";
if (($nb_dev_sous_cont != 0) and ($_SESSION['affiche_tous'] == 'no'))
	//$detail = $detail."ATTENTION : cliquez sur \'Afficher les �valuations des sous-bo�tes\' pour faire appara�tre toutes les �valuations qui interviennent dans la moyenne.\\n";
	$detail = $detail."ATTENTION : cliquez sur \'Afficher les �valuations des sous-".htmlentities(strtolower(getSettingValue("gepi_denom_boite")))."s\' pour faire appara�tre toutes les �valuations qui interviennent dans la moyenne.\\n";
if ($arrondir == 's1') $detail = $detail."La moyenne est arrondie au dixi�me de point sup�rieur.\\n";
if ($arrondir == 's5') $detail = $detail."La moyenne est arrondie au demi-point sup�rieur.\\n";
if ($arrondir == 'se') $detail = $detail."La moyenne est arrondie au point entier sup�rieur.\\n";
if ($arrondir == 'p1') $detail = $detail."La moyenne est arrondie au dixi�me de point le plus proche.\\n";
if ($arrondir == 'p5') $detail = $detail."La moyenne est arrondie au demi-point le plus proche.\\n";
if ($arrondir == 'pe') $detail = $detail."La moyenne est arrondie au point entier le plus proche.\\n";
if ($ponderation != 0) $detail = $detail."Pond�ration : ".$ponderation." (s\\'ajoute au coefficient de la meilleur note de chaque �l�ve).\\n";

// Titre
echo "<h2 class='gepi'>".$titre."</h2>\n";
if (($nb_dev == 0) and ($nb_sous_cont==0)) {

	//echo "<p class=cn>La bo�te $nom_conteneur ne contient aucune �valuation. </p>\n";
	echo "<p class=cn>";
	if(getSettingValue("gepi_denom_boite_genre")=='f'){echo "La ";}else{echo "Le ";}
	echo htmlentities(strtolower(getSettingValue("gepi_denom_boite")))." $nom_conteneur ne contient aucune �valuation. </p>\n";
	echo "</body></html>\n";
	die();
}

// D�but du deuxi�me formulaire
echo "<form enctype=\"multipart/form-data\" action=\"saisie_notes.php\" method=post  name=\"form2\">\n";
if ($id_devoir != 0) {
	echo add_token_field();
	echo "<center><input type='submit' value='Enregistrer' /></center>\n";
}

// Couleurs utilis�es
$couleur_devoirs = '#AAE6AA';
$couleur_moy_cont = '#96C8F0';
$couleur_moy_sous_cont = '#FAFABE';
$couleur_calcul_moy = '#AAAAE6';
$note_sur_verif = 20;
if ($id_devoir != 0) {
        $appel_note_sur = mysql_query("SELECT NOTE_SUR FROM cn_devoirs WHERE id = '$id_devoir'");
        $note_sur_verif = mysql_result($appel_note_sur,'0' ,'note_sur');
	//echo "<p class='cn'>Taper une note de 0 � 20 pour chaque �l�ve, ou � d�faut le code 'abs' pour 'absent', le code 'disp' pour 'dispens�', le code '-' pour absence de note.</p>\n";
	echo "<p class='cn'>Taper une note de 0 � ".$note_sur_verif." pour chaque �l�ve, ou � d�faut le code 'a' pour 'absent', le code 'd' pour 'dispens�', le code '-' ou 'n' pour absence de note.</p>\n";
	echo "<p class='cn'>Vous pouvez �galement <b>importer directement vos notes par \"copier/coller\"</b> � partir d'un tableur ou d'une autre application : voir tout en bas de cette page.</p>\n";

}
echo "<p class=cn><b>Enseignement : ".$current_group['description']." (" . $current_group["classlist_string"] . ")";
echo "</b></p>\n";

//=============================================================
// MODIF: boireaus
echo "
<script type='text/javascript' language='JavaScript'>

function verifcol(num_id){
	document.getElementById('n'+num_id).value=document.getElementById('n'+num_id).value.toLowerCase();
	if(document.getElementById('n'+num_id).value=='a'){
		document.getElementById('n'+num_id).value='abs';
	}
	if(document.getElementById('n'+num_id).value=='d'){
		document.getElementById('n'+num_id).value='disp';
	}
	if(document.getElementById('n'+num_id).value=='n'){
		document.getElementById('n'+num_id).value='-';
	}
	note=document.getElementById('n'+num_id).value;
	if((note!='-')&&(note!='disp')&&(note!='abs')&&(note!='')){
		//if((note.search(/^[0-9.]+$/)!=-1)&&(note.lastIndexOf('.')==note.indexOf('.',0))){
		if(((note.search(/^[0-9.]+$/)!=-1)&&(note.lastIndexOf('.')==note.indexOf('.',0)))||
	((note.search(/^[0-9,]+$/)!=-1)&&(note.lastIndexOf(',')==note.indexOf(',',0)))){
			if((note>".$note_sur_verif.")||(note<0)){
				couleur='red';
			}
			else{
				couleur='$couleur_devoirs';
			}
		}
		else{
			couleur='red';
		}
	}
	else{
		couleur='$couleur_devoirs';
	}
	eval('document.getElementById(\'td_'+num_id+'\').style.background=couleur');
}
</script>
";
//=============================================================

$i=0;
while ($i < $nb_dev) {
	$nocomment[$i]='yes';
	$i++;
}

// Tableau destiner � stocker l'id du champ de saisie de note (n$num_id) correspondant � l'�l�ve $i
$indice_ele_saisie=array();

$i = 0;
$num_id=10;
$current_displayed_line = 0;

// On commence par mettre la liste dans l'ordre souhait�
if ($order_by != "classe") {
	$liste_eleves = $current_group["eleves"][$periode_num]["users"];
} else {
	// Ici, on tri par classe
	// On va juste cr�er une liste des �l�ves pour chaque classe
	$tab_classes = array();
	foreach($current_group["classes"]["list"] as $classe_id) {
		$tab_classes[$classe_id] = array();
        //echo "\$tab_classes[$classe_id]=".$tab_classes[$classe_id]."<br />";
	}
	// On passe maintenant �l�ve par �l�ve et on les met dans la bonne liste selon leur classe
	foreach($current_group["eleves"][$periode_num]["list"] as $e_login) {
		$classe = $current_group["eleves"][$periode_num]["users"][$e_login]["classe"];
		$tab_classes[$classe][$e_login] = $current_group["eleves"][$periode_num]["users"][$e_login];
	}
	// On met tout �a � la suite
	$liste_eleves = array();
	foreach($current_group["classes"]["list"] as $classe_id) {
		$liste_eleves = array_merge($liste_eleves, $tab_classes[$classe_id]);
	}
}

$prev_classe = null;

//=========================
// AJOUT: boireaus 20080607
$tab_graph=array();
//=========================

foreach ($liste_eleves as $eleve) {
	$eleve_login[$i] = $eleve["login"];
	$eleve_nom[$i] = $eleve["nom"];
	$eleve_prenom[$i] = $eleve["prenom"];
	$eleve_classe[$i] = $current_group["classes"]["classes"][$eleve["classe"]]["classe"];
	$eleve_id_classe[$i] = $current_group["classes"]["classes"][$eleve["classe"]]["id"];
	$somme_coef = 0;

	$k=0;
	while ($k < $nb_dev) {
		$note_query = mysql_query("SELECT * FROM cn_notes_devoirs WHERE (login='$eleve_login[$i]' AND id_devoir='$id_dev[$k]')");
		// ==========================
		// MODIF: boireaus 20070913
		//$eleve_statut = @mysql_result($note_query, 0, "statut");
		//$eleve_note = @mysql_result($note_query, 0, "note");
		//$eleve_comment = @mysql_result($note_query, 0, "comment");
		if($note_query){
			if(mysql_num_rows($note_query)>0){
				$eleve_statut = @mysql_result($note_query, 0, "statut");
				$eleve_note = @mysql_result($note_query, 0, "note");
				$eleve_comment = @mysql_result($note_query, 0, "comment");
			}
			else{
				$eleve_statut = "";
				$eleve_note = "";
				$eleve_comment = "";
			}
		}
		else{
			$eleve_statut = "";
			$eleve_note = "";
			$eleve_comment = "";
		}
		// ==========================
		if ($eleve_comment != '') { $nocomment[$k]='no'; }
		$eleve_login_note = $eleve_login[$i]."_note";
		$eleve_login_comment = $eleve_login[$i]."_comment";
		if ($id_dev[$k] != $id_devoir) {
			//
			//
			$mess_note[$i][$k] = '';
			$mess_note[$i][$k] =$mess_note[$i][$k]."<td class=cn bgcolor=$couleur_devoirs><center><b>";
			if (($eleve_statut != '') and ($eleve_statut != 'v')) {
				$mess_note[$i][$k] = $mess_note[$i][$k].$eleve_statut;
				$mess_note_pdf[$i][$k] = $eleve_statut;
			} else if ($eleve_statut == 'v') {
				$mess_note[$i][$k] =$mess_note[$i][$k]."&nbsp;";
				$mess_note_pdf[$i][$k] = "";
			} else {
				if ($eleve_note != '') {
					$mess_note[$i][$k] =$mess_note[$i][$k].number_format($eleve_note,1, ',', ' ');
					$mess_note_pdf[$i][$k] = number_format($eleve_note,1, ',', ' ');

					//=========================
					// AJOUT: boireaus 20080607
					$tab_graph[$k][]=number_format($eleve_note,1, '.', ' ');
					//=========================
				} else {
					$mess_note[$i][$k] =$mess_note[$i][$k]."&nbsp;";
					$mess_note_pdf[$i][$k] = "";
				}
			}
			$mess_note[$i][$k] =$mess_note[$i][$k]."</b></center></td>\n";
			if ($eleve_comment != '') {
				$mess_comment[$i][$k] = "<td class=cn>".$eleve_comment."</td>\n";
				//$mess_comment_pdf[$i][$k] = $eleve_comment;
				$mess_comment_pdf[$i][$k] = traite_accents_utf8($eleve_comment);

			} else {
				$mess_comment[$i][$k] = "<td class=cn>&nbsp;</td>\n";
				$mess_comment_pdf[$i][$k] = "";
			}
		} else {
			$mess_note[$i][$k] = "<td class='cn' id='td_$num_id' style='text-align:center; background-color:$couleur_devoirs;'>\n";

			// ========================
			// AJOUT: boireaus 20071010
			$mess_note[$i][$k].="<input type='hidden' name='log_eleve[$i]' id='log_eleve_$i' value='$eleve_login[$i]' />\n";
			// ========================

			if ($current_group["classe"]["ver_periode"][$eleve_id_classe[$i]][$periode_num] == "N") {
				// ========================
				// MODIF: boireaus 20071010
				//$mess_note[$i][$k] .= "<input id=\"n".$num_id."\" onKeyDown=\"clavier(this.id,event);\" type=\"text\" size=\"4\" name=\"".$eleve_login_note."\" value=\"";

				$indice_ele_saisie[$i]=$num_id;
				$mess_note[$i][$k] .= "<input id=\"n".$num_id."\" onKeyDown=\"clavier(this.id,event);\" type=\"text\" size=\"4\" name=\"note_eleve[$i]\" ";
				$mess_note[$i][$k] .= "autocomplete='off' ";
				$mess_note[$i][$k] .= "value=\"";
				// ========================
			}

			if ((isset($note_import[$current_displayed_line])) and  ($note_import[$current_displayed_line] != '')) {
				$mess_note[$i][$k]=$mess_note[$i][$k].$note_import[$current_displayed_line];
				$mess_note_pdf[$i][$k] = $note_import[$current_displayed_line];
			}
			else {
				//echo "<p>\$eleve_login[$i]=$eleve_login[$i] \$i=$i et \$k=$j<br />\$eleve_statut=$eleve_statut<br />\$eleve_note=$eleve_note<br />";
				if (($eleve_statut != '') and ($eleve_statut != 'v')) {
					$mess_note[$i][$k] = $mess_note[$i][$k].$eleve_statut;
					$mess_note_pdf[$i][$k] = $eleve_statut;
				} else if ($eleve_statut == 'v') {
					$mess_note_pdf[$i][$k] = "";
				} else {
					$mess_note[$i][$k] = $mess_note[$i][$k].$eleve_note;

					if($eleve_note=="") {
						// Ca ne devrait pas arriver... si: quand le devoir est cr��, mais qu'aucune note n'est saisie, ni enregistrement encore effectu�.
						// Le simple fait de cliquer sur Enregistrer remplit la table cn_notes_devoirs avec eleve_note='0.0' et eleve_statut='v' et on n'a plus l'erreur
						$mess_note_pdf[$i][$k] = $eleve_note;
					}
					else {
						if((preg_match("/^[0-9]*.[0-9]*$/",$eleve_note))||
						(preg_match("/^[0-9]*,[0-9]*$/",$eleve_note))||
						(preg_match("/^[0-9]*$/",$eleve_note))) {
							$mess_note_pdf[$i][$k] = number_format($eleve_note,1, ',', ' ');
			
							//=========================
							// AJOUT: boireaus 20080607
							$tab_graph[$k][]=number_format($eleve_note,1, '.', ' ');
							//=========================
						}
						else {
							echo "<p style='color:red;'>BIZARRE: \$eleve_login[$i]=$eleve_login[$i] \$i=$i et \$k=$j<br />\$eleve_statut=$eleve_statut<br />\$eleve_note=$eleve_note<br />\n";
						}
					}
				}
			}
			if ($current_group["classe"]["ver_periode"][$eleve_id_classe[$i]][$periode_num] == "N") {
				//$mess_note[$i][$k] = $mess_note[$i][$k]."\" onfocus=\"javascript:this.select()\" onchange=\"verifcol($num_id);changement()\" />";
				//$mess_note[$i][$k] = $mess_note[$i][$k]."\" onfocus=\"javascript:this.select()\" onchange=\"verifcol($num_id);calcul_moy_med();changement()\" />";
				$mess_note[$i][$k] = $mess_note[$i][$k]."\" onfocus=\"javascript:this.select()";

				if(getSettingValue("gepi_pmv")!="n"){
					$sql="SELECT elenoet FROM eleves WHERE login='$eleve_login[$i]';";
					$res_ele=mysql_query($sql);
					if(mysql_num_rows($res_ele)>0) {
						$lig_ele=mysql_fetch_object($res_ele);
						//$_photo_eleve = (isset ($multisite) AND $multisite == 'y') ? $eleve_login[$i] : $lig_ele->elenoet;
						//if(file_exists("../photos/eleves/".$_photo_eleve.".jpg")) {
						if (nom_photo($lig_ele->elenoet)){
							//$mess_note[$i][$k].=";affiche_photo('".$_photo_eleve.".jpg','".addslashes(strtoupper($eleve_nom[$i])." ".ucfirst(strtolower($eleve_prenom[$i])))."')";
							$mess_note[$i][$k].=";affiche_photo('".nom_photo($lig_ele->elenoet)."','".addslashes(strtoupper($eleve_nom[$i])." ".ucfirst(strtolower($eleve_prenom[$i])))."')";
						}
						else {
							$mess_note[$i][$k].=";document.getElementById('div_photo_eleve').innerHTML='';";
						}
					}
					else {
						$mess_note[$i][$k].=";document.getElementById('div_photo_eleve').innerHTML='';";
					}
				}
				$mess_note[$i][$k].="\" onchange=\"verifcol($num_id);calcul_moy_med();changement();\" />";
			}
			$mess_note[$i][$k] .= "</td>\n";
			//=========================================================
			//$mess_comment[$i][$k] = "<td class='cn' bgcolor='$couleur_devoirs'><textarea id=\"1".$num_id."\" onKeyDown=\"clavier(this.id,event);\" name='".$eleve_login_comment."' rows=1 cols=30 wrap='virtual' onchange=\"changement()\">".$eleve_comment."</textarea></td>\n";
			$mess_comment[$i][$k] = "<td class='cn' bgcolor='$couleur_devoirs'>";
			if ($current_group["classe"]["ver_periode"][$eleve_id_classe[$i]][$periode_num] == "N"){
				// ========================
				// MODIF: boireaus 20071010
				//$mess_comment[$i][$k] .= "<textarea id=\"n1".$num_id."\" onKeyDown=\"clavier(this.id,event);\" name='".$eleve_login_comment."' rows=1 cols=30 wrap='virtual' onchange=\"changement()\">".$eleve_comment."</textarea></td>\n";
				//==============================
				// PREPARATIFS boireaus 20080422
				// Pour passer � no_anti_inject comme pour les autres saisies d'appr�ciations
				if($mode_commentaire_20080422!="no_anti_inject") {
					//$mess_comment[$i][$k] .= "<textarea id=\"n1".$num_id."\" onKeyDown=\"clavier(this.id,event);\" name='comment_eleve[$i]' rows=1 cols=30 wrap='virtual' onchange=\"changement()\">".$eleve_comment."</textarea></td>\n";
					// Ajout delineau -> fonctionnalit� de copier/coller d'appr�ciations
					if ((isset($appreciations_import[$current_displayed_line])) and  ($appreciations_import[$current_displayed_line] != '')) {
				     $eleve_comment = $appreciations_import[$current_displayed_line];
				  }
					// Fin ajout delineau -> fonctionnalit� de copier/coller d'appr�ciations

					$mess_comment[$i][$k] .= "<textarea id=\"n1".$num_id."\" onKeyDown=\"clavier(this.id,event);\" name='comment_eleve[$i]' rows=1 cols=60 class='wrap' onchange=\"changement()\">".$eleve_comment."</textarea></td>\n";
				}
				else {
					//$mess_comment[$i][$k] .= "<textarea id=\"n1".$num_id."\" onKeyDown=\"clavier(this.id,event);\" name='no_anti_inject_comment_eleve".$i."' rows=1 cols=30 wrap='virtual' onchange=\"changement()\">".$eleve_comment."</textarea></td>\n";
					$mess_comment[$i][$k] .= "<textarea id=\"n1".$num_id."\" onKeyDown=\"clavier(this.id,event);\" name='no_anti_inject_comment_eleve".$i."' rows=1 cols=30 class='wrap' onchange=\"changement()\">".$eleve_comment."</textarea></td>\n";
				}
				//==============================

				// ========================
			}
			else{
				$mess_comment[$i][$k] .= $eleve_comment."</td>\n";
			}
			//=========================================================
			//$mess_comment_pdf[$i][$k] = $eleve_comment;
			$mess_comment_pdf[$i][$k] = traite_accents_utf8($eleve_comment);
			$num_id++;
		}
		$k++;
	}
	$current_displayed_line++;
	$i++;
}

//
// Affichage du tableau
//
//echo "<table border='1' cellspacing='2' cellpadding='1'>\n";
echo "<table class='boireaus' cellspacing='2' cellpadding='1' summary=\"Tableau de notes\">\n";
//
// Premi�re ligne
//

// on calcule le nombre de colonnes � scinder
if ($id_devoir==0) {
	// Mode consultation
	$nb_colspan = $nb_dev;
	//$nb_colspan = $nb_dev+1; // +1 pour la colonne Moyenne // Il y a un pb de colspan sur la premi�re ligne selon que la p�riode est ouverte ou non dirait-on.
	$i = 0;
	while ($i < $nb_dev) {
		if ((($nocomment[$i]!='yes') and ($_SESSION['affiche_comment'] == 'yes')) or ($id_dev[$i] == $id_devoir)) {$nb_colspan++;}
		$i++;
	}
} else {
	// En mode saisie, on n'affiche que le devoir � saisir
	$nb_colspan = 2;
}

// Affichage premi�re ligne

echo "<tr><th class='cn'>&nbsp;</th>\n";
if ($multiclasses) {echo "<th class='cn'>&nbsp;</th>\n";}
echo "\n";
if ($nb_dev != 0) {
	if($nom_conteneur!=""){
		//echo "<th class='cn' colspan='$nb_colspan' valign='top'><center>$nom_conteneur</center></th>\n";
		echo "<th class='cn' colspan='$nb_colspan' valign='top'>\n";
		//echo "<center>$nom_conteneur</center>\n";
		echo "<div style='float:right; width:16;'><a href='javascript:affichage_quartiles();'><img src='../images/icons/histogramme.png' width='16' height='16' alt='Afficher les quartiles' title='Afficher les quartiles' /></a></div>\n";

		echo "$nom_conteneur\n";
		echo "</th>\n";
	}
	else{
		echo "<th class='cn' colspan='$nb_colspan' valign='top'><center>&nbsp;</center></th>\n";
	}
}

// on affiche les sous-conteneurs et les devoirs des sous-conteneurs si on n'est pas en mode saisie ($id_devoir == 0)
if ($id_devoir==0) {
	$i=0;
	while ($i < $nb_sous_cont) {
		// on affiche les devoirs des sous-conteneurs si l'utilisateur a fait le choix de tout afficher
		if (($_SESSION['affiche_tous'] == 'yes') and ($id_devoir==0)) {
			$query_nb_dev = mysql_query("SELECT * FROM cn_devoirs where (id_conteneur='$id_sous_cont[$i]' and id_racine='$id_racine') order by date");
			$nb_dev_s_cont[$i]  = mysql_num_rows($query_nb_dev);
			$m = 0;
			while ($m < $nb_dev_s_cont[$i]) {
				$id_s_dev[$i][$m] = mysql_result($query_nb_dev, $m, 'id');
				$nom_sous_dev[$i][$m] = mysql_result($query_nb_dev, $m, 'nom_court');
				$coef_s_dev[$i][$m]  = mysql_result($query_nb_dev, $m, 'coef');
				$note_sur_s_dev[$i][$m] = mysql_result($query_nb_dev, $m, 'note_sur');
				$ramener_sur_referentiel_s_dev[$i][$m] = mysql_result($query_nb_dev, $m, 'ramener_sur_referentiel');
				$fac_s_dev[$i][$m]  = mysql_result($query_nb_dev, $m, 'facultatif');
				$date = mysql_result($query_nb_dev, $m, 'date');
				$annee = substr($date,0,4);
				$mois =  substr($date,5,2);
				$jour =  substr($date,8,2);
				$display_date_s_dev[$i][$m] = $jour."/".$mois."/".$annee;

				$m++;
			}
			// ===============================
			// MODIF: boireaus
			//if ($nb_dev_s_cont[$i] != 0) echo "<th class=cn colspan='$nb_dev_s_cont[$i]' valign='top'><center>$nom_sous_cont[$i]</center></th>\n";
			if($nom_sous_cont[$i]!=""){
				$cellule_nom_sous_cont=$nom_sous_cont[$i];
			}
			else{
				$cellule_nom_sous_cont="&nbsp;";
			}
			if ($nb_dev_s_cont[$i] != 0) { echo "<th class=cn colspan='$nb_dev_s_cont[$i]' valign='top'><center>$cellule_nom_sous_cont</center></th>\n";}
			// ===============================
		}
		if($nom_sous_cont[$i]!=""){
			echo "<td class=cn valign='top'><center><b>$nom_sous_cont[$i]</b><br />\n";
		}
		else{
			echo "<td class=cn valign='top'><center><b>&nbsp;</b><br />\n";
		}
		if ($current_group["classe"]["ver_periode"]["all"][$periode_num] >= 3){
			echo "<a href=\"./add_modif_conteneur.php?mode_navig=retour_saisie&amp;id_conteneur=$id_sous_cont[$i]&amp;id_retour=$id_conteneur\"  onclick=\"return confirm_abandon (this, change,'$themessage')\">Configuration</a><br />\n";
		}

		echo "<a href=\"./saisie_notes.php?id_conteneur=$id_sous_cont[$i]\"  onclick=\"return confirm_abandon (this, change,'$themessage')\">Visualisation</a>\n";
		if ($display_bulletin_sous_cont[$i] == '1') { echo "<br /><font color='red'>Aff.&nbsp;bull.</font>\n";}
		echo "</center></td>\n";
		$i++;
	}
}
// En mode saisie, on n'affiche que le devoir � saisir
if (($current_group["classe"]["ver_periode"]["all"][$periode_num] >= 2) and ($id_devoir==0)){
	if($nom_conteneur!=""){
		echo "<td class=cn  valign='top'><center><b>$nom_conteneur</b><br />";
	}
	else{
		echo "<td class=cn  valign='top'><center><b>&nbsp;</b><br />";
	}
	echo "<a href=\"./add_modif_conteneur.php?mode_navig=retour_saisie&amp;id_conteneur=$id_conteneur&amp;id_retour=$id_conteneur\"  onclick=\"return confirm_abandon (this, change,'$themessage')\">Configuration</a><br /><br /><font color='red'>Aff.&nbsp;bull.</font></center></td>\n";
}
/*
// COMMENT�: boireaus 20071010
else{
	echo "<td class=cn  valign='top' bidule=''>&nbsp;</td>\n";
}
*/
echo "</tr>\n";

//echo "<tr><td>".$current_group["classe"]["ver_periode"]["all"][$periode_num]."</td><td>\$id_devoir=$id_devoir</td><td>&nbsp;</td></tr>\n";

// Deuxi�me ligne
echo "<tr>\n";
echo "<td class=cn valign='top'>&nbsp;</td>\n";
$header_pdf[] = "Evaluation :";
if ($multiclasses) $header_pdf[] = "";
$w_pdf[] = $w1;
//if ($multiclasses) $w_pdf[] = $w1b;
if ($multiclasses) echo "<td class='cn'>&nbsp;</td>\n";
if ($multiclasses) $w_pdf[] = $w2;
$i = 0;
while ($i < $nb_dev) {
	// En mode saisie, on n'affiche que le devoir � saisir
	if (($id_devoir==0) or ($id_dev[$i] == $id_devoir)) {
		if ($coef[$i] != 0) {$tmp = " bgcolor = $couleur_calcul_moy ";} else {$tmp = '';}
		//$header_pdf[] = $nom_dev[$i]." (".$display_date[$i].")";
		$header_pdf[] = traite_accents_utf8($nom_dev[$i]." (".$display_date[$i].")");
		$w_pdf[] = $w2;
		if ($current_group["classe"]["ver_periode"]["all"][$periode_num] >= 2) {
			echo "<td class=cn".$tmp." valign='top'><center><b><a href=\"./add_modif_dev.php?mode_navig=retour_saisie&amp;id_retour=$id_conteneur&amp;id_devoir=$id_dev[$i]\"  onclick=\"return confirm_abandon (this, change,'$themessage')\">$nom_dev[$i]</a></b><br /><font size=-2>($display_date[$i])</font>\n";
			if($display_parents[$i]!=0) {
				echo " <img src='../images/icons/visible.png' width='19' height='16' title='Evaluation visible sur le relev� de notes' alt='Evaluation visible sur le relev� de notes' />\n";
			}
			else {
				echo " <img src='../images/icons/invisible.png' width='19' height='16' title='Evaluation non visible sur le relev� de notes' alt='Evaluation non visible sur le relev� de notes' />\n";
			}
			echo "</center></td>\n";
		}
		else {
			echo "<td class=cn".$tmp." valign='top'><center><b>".$nom_dev[$i]."</b><br /><font size=-2>($display_date[$i])</font>\n";
			if($display_parents[$i]!=0) {
				echo " <img src='../images/icons/visible.png' width='19' height='16' title='Evaluation visible sur le relev� de notes' alt='Evaluation visible sur le relev� de notes' />\n";
			}
			else {
				echo " <img src='../images/icons/invisible.png' width='19' height='16' title='Evaluation non visible sur le relev� de notes' alt='Evaluation non visible sur le relev� de notes' />\n";
			}
			echo "</center></td>\n";
		}

		if ((($nocomment[$i]!='yes') and ($_SESSION['affiche_comment'] == 'yes')) or ($id_dev[$i] == $id_devoir)) {
			echo "<td class=cn  valign='top'><center>Commentaire&nbsp;*\n";
			echo "</center></td>\n";
			$header_pdf[] = "Commentaire";
			//$w_pdf[] = $w2;
			//if ($multiclasses) $w_pdf[] = $w2;
			$w_pdf[] = $w3;
		}
	}
	$i++;
}

// on affiche les sous-conteneurs et les devoirs des sous-conteneurs si on n'est pas en mode saisie ($id_devoir == 0)
if ($id_devoir==0) {
	$i=0;
	while ($i < $nb_sous_cont) {
		$tmp = '';
		if ($_SESSION['affiche_tous'] == 'yes') {
			$m = 0;
			while ($m < $nb_dev_s_cont[$i]) {
				$tmp = '';
				if (($mode == 1) and ($coef_s_dev[$i][$m] != 0)) $tmp = " bgcolor = $couleur_calcul_moy ";
				//$header_pdf[] = $nom_sous_dev[$i][$m]." (".$display_date_s_dev[$i][$m].")";
				$header_pdf[] = traite_accents_utf8($nom_sous_dev[$i][$m]." (".$display_date_s_dev[$i][$m].")");
				$w_pdf[] = $w2;
				echo "<td class=cn".$tmp." valign='top'><center><b><a href=\"./add_modif_dev.php?mode_navig=retour_saisie&amp;id_retour=$id_conteneur&amp;id_devoir=".$id_s_dev[$i][$m]."\"  onclick=\"return confirm_abandon (this, change,'$themessage')\">".$nom_sous_dev[$i][$m]."</a></b><br /><font size=-2>(".$display_date_s_dev[$i][$m].")</font></center></td>\n";
				$m++;
			}
			$tmp = '';
			if (($mode == 2) and ($coef_sous_cont[$i] != 0)) $tmp = " bgcolor = $couleur_calcul_moy ";
		}
		echo "<td class=cn".$tmp." valign='top'><center>Moyenne</center></td>\n";
		//$header_pdf[] = "Moyenne : ".$nom_sous_cont[$i];
		$header_pdf[] = traite_accents_utf8("Moyenne : ".$nom_sous_cont[$i]);
		$w_pdf[] = $w2;
		$i++;
	}
}
// En mode saisie, on n'affiche que le devoir � saisir
if ($id_devoir==0) {
	echo "<td class=cn valign='top'><center><b>Moyenne</b></center></td>\n";
	$header_pdf[] = "Moyenne";
	$w_pdf[] = $w2;
}
echo "</tr>";

//
// Troisi�me ligne
//
echo "<tr><td class=cn valign='top'>&nbsp;</td>";
if ($multiclasses) echo "<td class='cn'>&nbsp;</td>";
echo "\n";
$i = 0;
while ($i < $nb_dev) {
	// En mode saisie, on n'affiche que le devoir � saisir
	if (($id_devoir==0) or ($id_dev[$i] == $id_devoir)) {
		if ($id_dev[$i] == $id_devoir) {
			echo "<td class=cn valign='top'><center><a href=\"saisie_notes.php?id_conteneur=$id_conteneur&amp;id_devoir=0\" onclick=\"return confirm_abandon (this, change,'$themessage')\">Visualiser</a></center></td>\n";
//            echo "<td class=cn valign='top'><center><a href=\"saisie_notes.php?id_conteneur=$id_conteneur&id_devoir=0&affiche_message=$affiche_message\" onclick=\"form2.submit(); return false;\">verrouiller</a></center></td>";
//            echo "<td class=cn valign='top'><center><a href=\"saisie_notes.php?id_conteneur=$id_conteneur&id_devoir=0&affiche_message=$affiche_message\" onclick=\"form2.submit();\">verrouiller</a></center></td>";
			echo "<td class=cn valign='top'>&nbsp;</td>\n";
		} else {
			if ($current_group["classe"]["ver_periode"]["all"][$periode_num] >= 2)
			echo "<td class=cn valign='top'><center><a href=\"saisie_notes.php?id_conteneur=$id_conteneur&amp;id_devoir=$id_dev[$i]\" onclick=\"return confirm_abandon (this, change,'$themessage')\">saisir</a></center></td>\n";
			else
				echo "<td class=cn valign='top'>&nbsp;</td>\n";
			if (($nocomment[$i]!='yes')  and ($_SESSION['affiche_comment'] == 'yes')) echo "<td class=cn valign='top'>&nbsp;</td>\n";
		}
	}
	$i++;
}
// on affiche les sous-conteneurs et les devoirs des sous-conteneurs si on n'est pas en mode saisie ($id_devoir == 0)
if ($id_devoir==0) {
	$i=0;
	while ($i < $nb_sous_cont) {
		if ($_SESSION['affiche_tous'] == 'yes') {
			$m = 0;
			while ($m < $nb_dev_s_cont[$i]) {
				//echo "<td class=cn valign='top'><center><a href=\"saisie_notes.php?id_conteneur=".$id_sous_cont[$i][$m]."&amp;id_devoir=".$id_s_dev[$i][$m]."\" onclick=\"return confirm_abandon (this, change,'$themessage')\">saisir</a></center></td>\n";
				echo "<td class=cn valign='top'><center><a href=\"saisie_notes.php?id_conteneur=".$id_sous_cont[$i]."&amp;id_devoir=".$id_s_dev[$i][$m]."\" onclick=\"return confirm_abandon (this, change,'$themessage')\">saisir</a></center></td>\n";
				$m++;
			}
		}
		echo "<td class=cn valign='top'><center>&nbsp;</center></td>\n";
		$i++;
	}
}
// En mode saisie, on n'affiche que le devoir � saisir
if ($id_devoir==0) echo "<td class='cn' valign='top'>&nbsp;</td>\n";
echo "</tr>";

//
// quatri�me ligne
//
echo "<tr><td class='cn' valign='top'><b>" .
		"<a href='saisie_notes.php?id_conteneur=".$id_conteneur."&amp;id_devoir=".$id_devoir."&amp;order_by=nom' onclick=\"return confirm_abandon (this, change,'$themessage')\">Nom Pr�nom</a></b></td>";
if ($multiclasses) echo "<td><a href='saisie_notes.php?id_conteneur=".$id_conteneur."&amp;id_devoir=".$id_devoir."&amp;order_by=classe' onclick=\"return confirm_abandon (this, change,'$themessage')\">Classe</a></td>";
echo "\n";

//$data_pdf[0][] = "Nom Pr�nom\Coef.";
//$data_pdf[0][] = traite_accents_utf8("Nom Pr�nom\Coef.");

if(getSettingValue("note_autre_que_sur_referentiel")=="V") {
	$data_pdf[0][] = traite_accents_utf8("Nom Pr�nom /Note sur (coef)");
} else {
	$data_pdf[0][] = traite_accents_utf8("Nom Pr�nom \ (coef)");
}

if ($multiclasses) $data_pdf[0][] = "";
$i = 0;
while ($i < $nb_dev) {
	// En mode saisie, on n'affiche que le devoir � saisir
	if (($id_devoir==0) or ($id_dev[$i] == $id_devoir)) {
		echo "<td class='cn' valign='top'>";
		if(getSettingValue("note_autre_que_sur_referentiel")=="V" || $note_sur[$i]!=getSettingValue("referentiel_note")) {
			$data_pdf[0][] = "/".$note_sur[$i]." (".number_format($coef[$i],1, ',', ' ').")";
			if ($ramener_sur_referentiel[$i] != 'V') {
				echo "<font size=-2>Note sur ".$note_sur[$i]."<br />";
			} else {
				//$tabdiv_infobulle[]=creer_div_infobulle('ramenersurReferentiel_'.$i,"Ramener sur referentiel","","La note est ramen�e sur ".getSettingValue("referentiel_note")." pour le calcul de la moyenne","",15,0,'y','y','n','n');
				$tabdiv_infobulle[]=creer_div_infobulle('ramenersurReferentiel_'.$i,"","","La note est ramen�e sur ".getSettingValue("referentiel_note")." pour le calcul de la moyenne","",15,0,'n','n','n','n');
				echo "<a href='#' onmouseover=\"delais_afficher_div('ramenersurReferentiel_$i','y',-150,20,1500,10,10);\"";
				echo " onmouseout=\"cacher_div('ramenersurReferentiel_".$i."');\"";
				echo ">";

				echo "<font size=-2>Note sur ".$note_sur[$i];
				echo "</a><br />";
			}
		} else {
			$data_pdf[0][] = "(".number_format($coef[$i],1, ',', ' ').")";
		}
		echo "coef : ".number_format($coef[$i],1, ',', ' ');
		if (($facultatif[$i] == 'B') or ($facultatif[$i] == 'N')) echo "<br />Bonus";
		echo "</center></td>\n";
		if ($id_dev[$i] == $id_devoir) {
			echo "<td class='cn' valign='top'>&nbsp;</td>\n";
			$data_pdf[0][] = "";
		} else {
			if (($nocomment[$i]!='yes') and ($_SESSION['affiche_comment'] == 'yes')) {
				echo "<td class='cn' valign='top'>&nbsp;</td>\n";
				$data_pdf[0][] = "";
			}
		}
	}
	$i++;
}

// on affiche les sous-conteneurs et les devoirs des sous-conteneurs si on n'est pas en mode saisie ($id_devoir == 0)
if ($id_devoir==0) {
	$i=0;
	while ($i < $nb_sous_cont) {
		if ($_SESSION['affiche_tous'] == 'yes') {
			$m = 0;
			while ($m < $nb_dev_s_cont[$i]) {
				echo "<td class='cn' valign='top'>";
				if(getSettingValue("note_autre_que_sur_referentiel")=="V" || $note_sur_s_dev[$i][$m]!=getSettingValue("referentiel_note")) {
					$data_pdf[0][] = "/".$note_sur_s_dev[$i][$m]." (".number_format($coef_s_dev[$i][$m],1, ',', ' ').")";
					//if ($ramener_sur_referentiel[$i] != 'V') {
					if ($ramener_sur_referentiel_s_dev[$i][$m] != 'V') {
						echo "<font size=-2>Note sur ".$note_sur_s_dev[$i][$m]."<br />";
					} else {
						//$tabdiv_infobulle[]=creer_div_infobulle("ramenersurReferentiel_s_dev_".$i."_".$m,"Ramener sur referentiel","","La note est ramen�e sur ".getSettingValue("referentiel_note")." pour le calcul de la moyenne","",15,0,'y','y','n','n');
						$tabdiv_infobulle[]=creer_div_infobulle("ramenersurReferentiel_s_dev_".$i."_".$m,"","","La note est ramen�e sur ".getSettingValue("referentiel_note")." pour le calcul de la moyenne","",15,0,'n','n','n','n');
						echo "<a href='#' onmouseover=\"delais_afficher_div('ramenersurReferentiel_s_dev_".$i."_".$m."','y',-150,20,1500,10,10);\"";
						echo " onmouseout=\"cacher_div('ramenersurReferentiel_s_dev_".$i."_".$m."');\"";
						echo ">";
						echo "<font size=-2>Note sur ".$note_sur_s_dev[$i][$m];
						echo "</a><br />";
					}
				} else {
					$data_pdf[0][] = "(".number_format($coef_s_dev[$i][$m],1, ',', ' ').")";
				}


				echo "<center>coef : ".number_format($coef_s_dev[$i][$m],1, ',', ' ');
				if (($fac_s_dev[$i][$m] == 'B') or ($fac_s_dev[$i][$m] == 'N')) echo "<br />Bonus";
				echo "</center></td>\n";
				$m++;
			}
		}
		if ($mode==2) {
			echo "<td class='cn' valign='top'><center>coef : ".number_format($coef_sous_cont[$i],1, ',', ' ')."</center></td>\n";
			$data_pdf[0][] = number_format($coef_sous_cont[$i],1, ',', ' ');
		} else {
			echo "<td class='cn' valign='top'><center>&nbsp;</center></td>\n";
			$data_pdf[0][] = "";
		}
		$i++;
	}
}

// En mode saisie, on n'affiche que le devoir � saisir
if ($id_devoir==0)  {
	echo "<td class='cn' valign='top'><center><a href=\"javascript:alert('".$detail."')\">Informations</a></center></td>\n";
	$data_pdf[0][] = "";
}
//echo "<td class=cn>mode = $mode<br />arrondir = $arrondir<br />";
//if ($ponderation != 0) echo "pond�ration = $ponderation";
//echo "</td>

echo "</tr>\n";

//========================
// AJOUT boireaus 20080607
$graphe_largeurTotale=200;
$graphe_hauteurTotale=150;
$graphe_titre="R�partition des notes";
$graphe_taille_police=3;
$graphe_epaisseur_traits=2;
$graphe_nb_tranches=5;

//for($k=0;$k<count($tab_graph);$k++) {

$n_dev_0=0;
$n_dev_fin=$nb_dev;
if($id_devoir>0) {
	for($k=0;$k<$nb_dev;$k++) {
		//echo "<!-- \$id_dev[$k]=$id_dev[$k] -->\n";
		if($id_dev[$k]==$id_devoir) {
			$n_dev_0=$k;
			$n_dev_fin=$k+1;
			break;
		}
	}
	echo "<tr>\n";
	echo "<td>R�partition des notes";
	echo "</td>\n";
	if ($multiclasses) {echo "<td>&nbsp;</td>\n";}
}
elseif($nb_sous_cont==0) {
	echo "<tr>\n";
	echo "<td>R�partition des notes";
	echo "</td>\n";
	if ($multiclasses) {echo "<td>&nbsp;</td>\n";}
}

if(($id_devoir>0)||($nb_sous_cont==0)) {
	//echo "<!-- $n_dev_0 -->\n";
	for($k=$n_dev_0;$k<$n_dev_fin;$k++) {
		//$tab_graph[$k]

		echo "<td>";
		//echo "aaa";
		if(isset($tab_graph[$k])) {
			//echo "bbbb";
			$graphe_serie="";
			for($l=0;$l<count($tab_graph[$k]);$l++) {
				if($l>0) {$graphe_serie.="|";}
				$graphe_serie.=$tab_graph[$k][$l];
			}

			//$titre="R�partition des notes de ".$nom_dev[$k];
			//$titre="<center>".$nom_dev[$k]."</center>";
			$titre=$nom_dev[$k];

			$texte="<div align='center'><object data='../lib/graphe_svg.php?";
			$texte.="serie=$graphe_serie";
			$texte.="&amp;note_sur_serie=$note_sur[$k]";
			$texte.="&amp;nb_tranches=$graphe_nb_tranches";
			$texte.="&amp;titre=$graphe_titre";
			$texte.="&amp;v_legend1=Notes";
			$texte.="&amp;v_legend2=Effectif";
			$texte.="&amp;largeurTotale=$graphe_largeurTotale";
			$texte.="&amp;hauteurTotale=$graphe_hauteurTotale";
			$texte.="&amp;taille_police=$graphe_taille_police";
			$texte.="&amp;epaisseur_traits=$graphe_epaisseur_traits";
			$texte.="'";
			$texte.=" width='$graphe_largeurTotale' height='$graphe_hauteurTotale'";
			$texte.=" type=\"image/svg+xml\"></object></div>\n";

			$tabdiv_infobulle[]=creer_div_infobulle('repartition_notes_'.$k,$titre,"",$texte,"",14,0,'y','y','n','n');

			//echo " <a href='#' onmouseover=\"afficher_div('repartition_notes_$k','y',-100,20);\"";
			echo " <a href='#' onmouseover=\"delais_afficher_div('repartition_notes_$k','y',-100,20,1500,10,10);\"";
			echo ">";
			echo "<img src='../images/icons/histogramme.png' alt='R�partition des notes' />";
			echo "</a>";
		}
		else {
			echo "&nbsp;";
		}
		echo "</td>\n";
		if($nocomment[$k]=='no') {
			echo "<td>&nbsp;</td>\n";
		}
	}
	if($id_devoir==0) {
		// Colonne Moyenne de l'�l�ve
		echo "<td>";
		//echo "&nbsp;";
		echo " <a href='#' onmouseover=\"delais_afficher_div('repartition_notes_moyenne','y',-100,20,1500,10,10);\"";
		echo ">";
		echo "<img src='../images/icons/histogramme.png' alt='R�partition des notes' />";
		echo "</a>";
		echo "</td>\n";
	}
	echo "</tr>\n";
}
//========================

// Pour permettre d'afficher moyenne, m�diane, quartiles,... en mode Visualisation du carnet de notes
$chaine_input_moy="";

//
// Affichage des lignes "el�ves"
//
$alt=1;
$i = 0;
$pointer = 0;
$tot_data_pdf = 1;
$nombre_lignes = count($current_group["eleves"][$periode_num]["list"]);
while($i < $nombre_lignes) {
	$pointer++;
	$tot_data_pdf++;
	//$data_pdf[$pointer][] = $eleve_nom[$i]." ".$eleve_prenom[$i];
	//if ($multiclasses) $data_pdf[$pointer][] = $eleve_classe[$i];
	$data_pdf[$pointer][] = traite_accents_utf8($eleve_nom[$i]." ".$eleve_prenom[$i]);
	if ($multiclasses) {$data_pdf[$pointer][] = traite_accents_utf8($eleve_classe[$i]);}
	$alt=$alt*(-1);
	//echo "<tr>";
	echo "<tr class='lig$alt'>\n";
	if ($eleve_classe[$i] != $prev_classe && $prev_classe != null && $order_by == "classe") {
		//echo "<td class=cn style='border-top: 2px solid blue;'>$eleve_nom[$i] $eleve_prenom[$i]</td>";
		echo "<td class=cn style='border-top: 2px solid blue; text-align:left;'>\n";

		//echo "<div style='float:right; width:16;'><a href='javascript:affiche_div_photo();'><img src='../images/icons/buddy.png' width='16' height='16' alt='Afficher la photo �l�ve' title='Afficher la photo �l�ve' /></a></div>\n";

		echo "$eleve_nom[$i] $eleve_prenom[$i]\n";
		echo "</td>";
		if ($multiclasses) echo "<td style='border-top: 2px solid blue;'>$eleve_classe[$i]</td>";
		echo "\n";
	} else {
		//echo "<td class=cn>$eleve_nom[$i] $eleve_prenom[$i]</td>";
		echo "<td class=cn style='text-align:left;'>\n";

		if($id_devoir!=0) {
			echo "<div style='float:right; width:16;'><a href=\"javascript:";
			if(isset($indice_ele_saisie[$i])) {
				echo "if(document.getElementById('n'+".$indice_ele_saisie[$i].")) {document.getElementById('n'+".$indice_ele_saisie[$i].").focus()};";
				echo "affiche_div_photo();";
			}
			else {
				echo "affiche_div_photo();";
			}
			echo "\"><img src='../images/icons/buddy.png' width='16' height='16' alt='Afficher la photo �l�ve' title='Afficher la photo �l�ve' /></a></div>\n";
		}

		echo "$eleve_nom[$i] $eleve_prenom[$i]\n";
		echo "</td>";
		if ($multiclasses) echo "<td>$eleve_classe[$i]</td>";
		echo "\n";
	}
	$prev_classe = $eleve_classe[$i];
	$k=0;
	while ($k < $nb_dev) {
		// En mode saisie, on n'affiche que le devoir � saisir
		if (($id_devoir==0) or ($id_dev[$k] == $id_devoir)) {
			echo $mess_note[$i][$k];
			$data_pdf[$pointer][] = $mess_note_pdf[$i][$k];
			if ((($nocomment[$k]!='yes') and ($_SESSION['affiche_comment'] == 'yes')) or ($id_dev[$k] == $id_devoir)) {
				echo $mess_comment[$i][$k];
				//$data_pdf[$pointer][] = $mess_comment_pdf[$i][$k];
				$data_pdf[$pointer][] = traite_accents_utf8($mess_comment_pdf[$i][$k]);
			}
		}
		$k++;
	}
	//
	// Affichage de la moyenne de tous les sous-conteneurs
	//

	// on affiche les sous-conteneurs et les devoirs des sous-conteneurs si on n'est pas en mode saisie ($id_devoir == 0)
	if ($id_devoir==0) {
		$k=0;
		while ($k < $nb_sous_cont) {
			if ($_SESSION['affiche_tous'] == 'yes') {
				$m = 0;
				while ($m < $nb_dev_s_cont[$k]) {
					$temp = $id_s_dev[$k][$m];
					$note_query = mysql_query("SELECT * FROM cn_notes_devoirs WHERE (login='$eleve_login[$i]' AND id_devoir='$temp')");
					// ===================================
					// AJOUT: boireaus 20071101
					// Ajout d'un test pour se pr�munir du bug rencontr� par certains chez Free avec les @mysql_result
					if($note_query){
						$eleve_statut = @mysql_result($note_query, 0, "statut");
						$eleve_note = @mysql_result($note_query, 0, "note");
						if (($eleve_statut != '') and ($eleve_statut != 'v')) {
							$tmp = $eleve_statut;
							$data_pdf[$pointer][] = $eleve_statut;
						} else if ($eleve_statut == 'v') {
							$tmp = "&nbsp;";
							$data_pdf[$pointer][] = "";
						} else {
							if ($eleve_note != '') {
								$tmp = number_format($eleve_note,1, ',', ' ');
								$data_pdf[$pointer][] = number_format($eleve_note,1, ',', ' ');
							} else {
								$tmp = "&nbsp;";
								$data_pdf[$pointer][] = "";
							}
						}
					}
					else{
						$eleve_statut = "";
						$eleve_note = "";
						$tmp = "&nbsp;";
						$data_pdf[$pointer][] = "";
					}
					// ===================================
					echo "<td class='cn' bgcolor='$couleur_devoirs'><center><b>$tmp</b></center></td>\n";

					$m++;
				}
			}

			$moyenne_query = mysql_query("SELECT * FROM cn_notes_conteneurs WHERE (login='$eleve_login[$i]' AND id_conteneur='$id_sous_cont[$k]')");
			// ===================================
			// AJOUT: boireaus 20071101
			// Ajout d'un test pour se pr�munir du bug rencontr� par certains chez Free avec les @mysql_result
			if($moyenne_query){
				$statut_moy = @mysql_result($moyenne_query, 0, "statut");
				if ($statut_moy == 'y') {
					$moy = @mysql_result($moyenne_query, 0, "note");
					$moy = number_format($moy,1, ',', ' ');
					$data_pdf[$pointer][] = $moy;
				} else {
					$moy = '&nbsp;';
					$data_pdf[$pointer][] = "";
				}
			}
			else{
				$statut_moy = "";
				$moy = '&nbsp;';
				$data_pdf[$pointer][] = "";
			}
			// ===================================
			echo "<td class='cn' bgcolor='$couleur_moy_sous_cont'><center>$moy</center></td>\n";
			$k++;
		}
	}
	//
	// affichage des moyennes du conteneur
	//
	// En mode saisie, on n'affiche que le devoir � saisir
	if ($id_devoir==0)  {
		$moyenne_query = mysql_query("SELECT * FROM cn_notes_conteneurs WHERE (login='$eleve_login[$i]' AND id_conteneur='$id_conteneur')");
		// ===================================
		// AJOUT: boireaus 20071101
		// Ajout d'un test pour se pr�munir du bug rencontr� par certains chez Free avec les @mysql_result
		if($moyenne_query) {
			$statut_moy = @mysql_result($moyenne_query, 0, "statut");
			if ($statut_moy == 'y') {
				$moy = @mysql_result($moyenne_query, 0, "note");
				//echo "$eleve_login[$i] : $moy -&gt; ";
				$moy = number_format($moy,1, ',', ' ');
				//echo "$moy -&gt; ";
				$data_pdf[$pointer][] = $moy;

				//=========================
				// AJOUT: boireaus 20080607
				$tab_graph_moy[]=number_format(strtr($moy,",","."),1, '.', ' ');
				//echo number_format(strtr($moy,",","."),1, '.', ' ')."<br />";
				//=========================

			} else {
				$moy = '&nbsp;';
				$data_pdf[$pointer][] = "";
			}
		}
		else {
			$statut_moy = "";
			$moy = '&nbsp;';
			$data_pdf[$pointer][] = "";
		}
		// ===================================
		echo "<td class='cn' bgcolor='$couleur_moy_cont'><center><b>$moy</b></center></td>\n";
		if($moy=='&nbsp;') {
			$chaine_input_moy.="<input type='hidden' name='n$i' id='n$i' value='' />\n";
		}
		else {
			$chaine_input_moy.="<input type='hidden' name='n$i' id='n$i' value='$moy' />\n";
		}
	}
	echo "</tr>\n";

	$i++;
}
$nombre_lignes=$i;

//=========================
// AJOUT: boireaus 20080607
// G�n�ration de l'infobulle pour $tab_graph_moy[]
if($id_devoir==0) {
	$graphe_serie="";
	if(isset($tab_graph_moy)) {
		for($l=0;$l<count($tab_graph_moy);$l++) {
			if($l>0) {$graphe_serie.="|";}
			$graphe_serie.=$tab_graph_moy[$l];
		}
	}

	//$titre="R�partition des notes de ".$nom_dev[$k];
	//$titre="<center>".$nom_dev[$k]."</center>";
	$titre="Moyenne";

	$texte="<div align='center'><object data='../lib/graphe_svg.php?";
	$texte.="serie=$graphe_serie";
	$texte.="&amp;note_sur_serie=20";
	$texte.="&amp;nb_tranches=5";
	$texte.="&amp;titre=$graphe_titre";
	$texte.="&amp;v_legend1=Notes";
	$texte.="&amp;v_legend2=Effectif";
	$texte.="&amp;largeurTotale=$graphe_largeurTotale";
	$texte.="&amp;hauteurTotale=$graphe_hauteurTotale";
	$texte.="&amp;taille_police=$graphe_taille_police";
	$texte.="&amp;epaisseur_traits=$graphe_epaisseur_traits";
	$texte.="'";
	$texte.=" width='$graphe_largeurTotale' height='$graphe_hauteurTotale'";
	$texte.=" type=\"image/svg+xml\"></object></div>\n";

	$tabdiv_infobulle[]=creer_div_infobulle('repartition_notes_moyenne',$titre,"",$texte,"",14,0,'y','y','n','n');
}
//=========================


//
// Derni�re ligne
//
echo "<tr>";
if ($multiclasses) {
	echo "<td class=cn colspan=2>";
} else {
	echo "<td class=cn>";
}

echo "<input type='hidden' name='indice_max_log_eleve' value='$i' />\n";

echo "<b>Moyennes :</b></td>\n";
$w_pdf[] = $w2;
$data_pdf[$tot_data_pdf][] = "Moyennes";
if ($multiclasses) $data_pdf[$tot_data_pdf][] = "";
$k='0';
while ($k < $nb_dev) {
	// En mode saisie, on n'affiche que le devoir � saisir
	if (($id_devoir==0) or ($id_dev[$k] == $id_devoir)) {
		$call_moyenne[$k] = mysql_query("SELECT round(avg(n.note),1) moyenne FROM cn_notes_devoirs n, j_eleves_groupes j WHERE (
		j.id_groupe='$id_groupe' AND
		j.periode = '$periode_num' AND
		j.login = n.login AND
		n.statut='' AND
		n.id_devoir='$id_dev[$k]'
		)");
		$moyenne[$k] = mysql_result($call_moyenne[$k], 0, "moyenne");
		if ($moyenne[$k] != '') {
			echo "<td class='cn'><center><b>".number_format($moyenne[$k],1, ',', ' ')."</b></center></td>\n";
			$data_pdf[$tot_data_pdf][] = number_format($moyenne[$k],1, ',', ' ');

		} else {
			echo "<td class='cn'>&nbsp;</td></td>\n";
			$data_pdf[$tot_data_pdf][] = "";
		}
		if ((($nocomment[$k]!='yes') and ($_SESSION['affiche_comment'] == 'yes')) or ($id_dev[$k] == $id_devoir)) {
		echo "<td class='cn'>&nbsp;</td>\n";
		$data_pdf[$tot_data_pdf][] = "";
		}
	}
	$k++;
}
//
// Moyenne des moyennes des sous-conteneurs
//
// on affiche les sous-conteneurs et les devoirs des sous-conteneurs si on n'est pas en mode saisie ($id_devoir == 0)
if ($id_devoir==0) {
	$k=0;
	while ($k < $nb_sous_cont) {
		if ($_SESSION['affiche_tous'] == 'yes') {
			$m = 0;
			while ($m < $nb_dev_s_cont[$k]) {
				$temp = $id_s_dev[$k][$m];
				$call_moy = mysql_query("SELECT round(avg(n.note),1) moyenne FROM cn_notes_devoirs n, j_eleves_groupes j WHERE (
				j.id_groupe='$id_groupe' AND
				j.periode = '$periode_num' AND
				j.login = n.login AND
				n.statut='' AND
				n.id_devoir='$temp'
				)");
				$moy_s_dev = mysql_result($call_moy, 0, "moyenne");
				if ($moy_s_dev != '') {
					echo "<td class='cn'><center><b>".number_format($moy_s_dev,1, ',', ' ')."</b></center></td>\n";
					$data_pdf[$tot_data_pdf][] = number_format($moy_s_dev,1, ',', ' ');
				} else {
					//echo "<td class=cn>&nbsp;</td></td>";
					echo "<td class='cn'>&nbsp;</td>\n";
					$data_pdf[$tot_data_pdf][] = "";
				}
				$m++;
			}
		}
		$call_moy_moy = mysql_query("SELECT round(avg(n.note),1) moyenne FROM cn_notes_conteneurs n, j_eleves_groupes j WHERE (
		j.id_groupe='$id_groupe' AND
		j.login = n.login AND
		j.periode = '$periode_num' AND
		n.statut='y' AND
		n.id_conteneur='$id_sous_cont[$k]'
		)");
		$moy_moy = mysql_result($call_moy_moy, 0, "moyenne");
		if ($moy_moy != '') {
			echo "<td class='cn'><center><b>".number_format($moy_moy,1, ',', ' ')."</b></center></td>\n";
			$data_pdf[$tot_data_pdf][] = number_format($moy_moy,1, ',', ' ');
		} else {
			echo "<td class='cn'>&nbsp;</td>\n";
			$data_pdf[$tot_data_pdf][] = "";
		}
		$k++;
	}
}
//
// Moyenne des moyennes du conteneur
//
// on affiche les sous-conteneurs et les devoirs des sous-conteneurs si on n'est pas en mode saisie ($id_devoir == 0)
if ($id_devoir==0) {

	$call_moy_moy = mysql_query("SELECT round(avg(n.note),1) moyenne FROM cn_notes_conteneurs n, j_eleves_groupes j WHERE (
	j.id_groupe='$id_groupe' AND
	j.login = n.login AND
	j.periode = '$periode_num' AND
	n.statut='y' AND
	n.id_conteneur='$id_conteneur'
	)");
	$moy_moy = mysql_result($call_moy_moy, 0, "moyenne");
	if ($moy_moy != '') {
		echo "<td class='cn'><center><b>".number_format($moy_moy,1, ',', ' ')."</b></center></td>\n";
		$data_pdf[$tot_data_pdf][] = number_format($moy_moy,1, ',', ' ');
	} else {
		echo "<td class='cn'>&nbsp;</td>\n";
		$data_pdf[$tot_data_pdf][] = "";
	}
}
echo "</tr></table>\n";

//===================================
if((isset($id_devoir))&&($id_devoir!=0)) {
	echo "<div id='div_q_p' style='position: fixed; top: 220px; right: 200px; text-align:center;'>\n";
		echo "<div id='div_quartiles' style='text-align:center; display:none;'>\n";
		//javascript_tab_stat('tab_stat_',$nombre_lignes);
		javascript_tab_stat('tab_stat_',$num_id);
		echo "</div>\n";

		echo "<br />\n";

		echo "<div id='div_photo_eleve' style='text-align:center; display:none;'></div>\n";
	echo "</div>\n";

	echo "<script type='text/javascript'>
	function affiche_div_photo() {
		if(document.getElementById('div_photo_eleve').style.display=='none') {
			document.getElementById('div_photo_eleve').style.display='';
		}
		else {
			document.getElementById('div_photo_eleve').style.display='none';
		}
	}

	function affiche_photo(photo,nom_prenom) {
 		document.getElementById('div_photo_eleve').innerHTML='<img src=\"'+photo+'\" width=\"150\" alt=\"Photo\" /><br />'+nom_prenom;
	}
</script>\n";
}
//===================================

// Pr�paration du pdf
$header_pdf=serialize($header_pdf);
$_SESSION['header_pdf']=$header_pdf;

$w_pdf=serialize($w_pdf);
$_SESSION['w_pdf']=$w_pdf;

$data_pdf=serialize($data_pdf);
$_SESSION['data_pdf']=$data_pdf;

if ($id_devoir) echo "<input type='hidden' name='is_posted' value=\"yes\" />\n";

?>

<input type="hidden" name="id_conteneur" value="<?php echo "$id_conteneur";?>" />
<input type="hidden" name="id_devoir" value="<?php echo "$id_devoir";?>" />
<?php if ($id_devoir != 0) echo "<br /><center><div id=\"fixe\"><input type='submit' value='Enregistrer' /></div></center>\n"; ?>
</form>
<?php

//===================================

// Affichage des quartiles flottants

//echo "\$id_devoir=$id_devoir<br />";
//if((isset($id_devoir))&&($id_devoir==0)) {
//if((!isset($id_devoir))||($id_devoir=='')) {
if((!isset($id_devoir))||($id_devoir=='')||($id_devoir=='0')) {
	echo "<form action='".$_SERVER['PHP_SELF']."' method='post' name='form_calcul_mediane'>\n";
	echo $chaine_input_moy;
	echo "</form>\n";

	//echo "<div id='div_quartiles' style='position: fixed; top: 220px; right: 200px; text-align:center;'>\n";
	echo "<div id='div_q_p' style='position: fixed; top: 220px; right: 200px; text-align:center;'>\n";
		echo "<div id='div_quartiles' style='text-align:center; display:none;'>\n";
		javascript_tab_stat('tab_stat_',$nombre_lignes);
		echo "</div>\n";
	echo "</div>\n";
}
//===================================

if ($id_devoir) {

	$chaine_indices="";
	for($i=0;$i<$nombre_lignes;$i++) {
		if(isset($indice_ele_saisie[$i])) {
			if($chaine_indices!="") {$chaine_indices.=",";}
			$chaine_indices.=$indice_ele_saisie[$i];
		}
	}

	echo "<p id='p_tri'></p>\n";
	echo "<script type='text/javascript'>
	function affiche_lien_tri() {
		var tab_indices=new Array($chaine_indices);

		chaine1='';
		chaine2='';
		for(i=0;i<$nombre_lignes;i++) {
			//num=eval(10+i);
			num=tab_indices[i];

			if(document.getElementById('n'+num)) {
				if(chaine1!='') {chaine1=chaine1+'|';}
				if(chaine2!='') {chaine2=chaine2+'|';}

				chaine1=chaine1+document.getElementById('log_eleve_'+i).value;
				chaine2=chaine2+document.getElementById('n'+num).value;
			}
		}
		//alert(chaine1);
		//alert(chaine2);
		document.getElementById('p_tri').innerHTML='<a href=\'affiche_tri.php?titre=Notes&chaine1='+chaine1+'&chaine2='+chaine2+'\' onclick=\"effectuer_tri(); afficher_div(\'div_tri\',\'y\',-150,20); return false;\" target=\'_blank\'>Afficher les notes tri�es</a>';
	}

	function effectuer_tri() {
		var tab_indices=new Array($chaine_indices);

		chaine1='';
		chaine2='';
		for(i=0;i<$nombre_lignes;i++) {
			//num=eval(10+i);
			num=tab_indices[i];

			if(document.getElementById('n'+num)) {
				if(chaine1!='') {chaine1=chaine1+'|';}
				if(chaine2!='') {chaine2=chaine2+'|';}

				chaine1=chaine1+document.getElementById('log_eleve_'+i).value;
				chaine2=chaine2+document.getElementById('n'+num).value;
			}
		}

		new Ajax.Updater($('notes_triees'),'affiche_tri.php?titre=Notes&chaine1='+chaine1+'&chaine2='+chaine2+'".add_token_in_url(false)."',{method: 'get'});
	}

	affiche_lien_tri();
</script>\n";
	$titre_infobulle="Notes tri�es";
	$texte_infobulle="<div id='notes_triees'></div>";
	$tabdiv_infobulle[]=creer_div_infobulle('div_tri',$titre_infobulle,"",$texte_infobulle,"",30,0,'y','y','n','n');


	echo "<fieldset style=\"padding-top: 8px; padding-bottom: 8px;  margin-left: 8px; margin-right: 100px;\">\n";
	echo "<form enctype=\"multipart/form-data\" action=\"saisie_notes.php\" method=post>\n";
	echo add_token_field();
	echo "<h3 class='gepi'>Importation directe des notes par copier/coller � partir d'un tableur</h3>\n";
	echo "<table summary=\"Tableau d'import\"><tr>\n";
	echo "<td>De la ligne : ";
		echo "<SELECT name='debut_import' size='1'>\n";
	$k = 1;
	while ($k < $current_displayed_line+1) {
		echo "<option value='$k'>$k</option>\n";
		$k++;
	}
	echo "</select>\n";

	echo "<br /> � la ligne : \n";
	echo "<SELECT name='fin_import' size='1'>\n";
	$k = 1;
	while ($k < $current_displayed_line+1) {
		echo "<option value='$k'";
		if ($k == $current_displayed_line) echo " SELECTED ";
		echo ">$k</option>\n";
		$k++;
	}
	echo "</select>\n";
	echo "</td><td>\n";
	echo "Coller ci-dessous les donn�es � importer : <br />\n";
	if (isset($_POST['notes'])) {$notes=preg_replace("/\\\\n/","\n",preg_replace("/\\\\r/","\r",$_POST['notes']));} else {$notes='';}
	//echo "<textarea name='notes' rows='3' cols='40' wrap='virtual'>$notes</textarea>\n";
	echo "<textarea name='notes' rows='3' cols='40' class='wrap'>$notes</textarea>\n";
	echo "</td></tr></table>\n";

	echo "<input type='hidden' name='id_conteneur' value='$id_conteneur' />\n";
	echo "<input type='hidden' name='id_devoir' value='$id_devoir' />\n";

	//=========================
	// AJOUT: boireaus 20071128
	echo "<input type='hidden' name='order_by' value='$order_by' />\n";
	//=========================

	echo "<center><input type='submit' value='Importer'  onclick=\"return confirm_abandon (this, change, '$themessage')\" /></center>\n";
	echo "<p><b>Remarque importante :</b> l'importation ne prend en compte que les �l�ves dont le nom est affich� ci-dessus !<br />Soyez donc vigilant � ne coller que les notes de ces �l�ves, dans le bon ordre.</p>\n";
	echo "</form></fieldset>\n";

	//=======================================================
	// MODIF: boireaus
	// Avertissement redescendu ici pour �viter d'avoir une page web avec une section Javascript avant m�me la balise <html>
	if (isset($_POST['notes'])) {
		echo "<script type=\"text/javascript\" language=\"javascript\">
		<!--
		alert(\"Attention, les notes import�es ne sont pas encore enregistr�es dans la base GEPI. Vous devez confirmer l'importation (bouton 'Enregistrer') !\");
		changement();
		//-->
		</script>\n";
	}
	// Ajout delineau -> fonctionnalit� de copier/coller d'appr�ciations
  if (isset($_POST['appreciations'])) {
  	echo "<script type=\"text/javascript\" language=\"javascript\">
  	<!--
  	alert(\"Attention, les appr�ciations import�es ne sont pas encore enregistr�es dans la base GEPI. Vous devez confirmer l'importation (bouton 'Enregistrer') !\");
  	//-->
  	</script>\n";
  }
	// Fin ajout delineau -> fonctionnalit� de copier/coller d'appr�ciations

	//=======================================================

}

// Ajout delineau -> fonctionnalit� de copier/coller d'appr�ciations
if ($id_devoir) {
	echo "<fieldset style=\"padding-top: 8px; padding-bottom: 8px;  margin-left: 8px; margin-right: 100px;\">\n";
	echo "<form enctype=\"multipart/form-data\" action=\"saisie_notes.php\" method=post>\n";
	echo add_token_field();
	echo "<h3 class='gepi'>Importation directe des appr�ciations par copier/coller � partir d'un tableur</h3>\n";
	echo "<table summary=\"Tableau d'import\"><tr>\n";
	echo "<td>De la ligne : ";
		echo "<SELECT name='debut_import' size='1'>\n";
	$k = 1;
	while ($k < $current_displayed_line+1) {
		echo "<option value='$k'>$k</option>\n";
		$k++;
	}
	echo "</select>\n";

	echo "<br /> � la ligne : \n";
	echo "<SELECT name='fin_import' size='1'>\n";
	$k = 1;
	while ($k < $current_displayed_line+1) {
		echo "<option value='$k'";
		if ($k == $current_displayed_line) echo " SELECTED ";
		echo ">$k</option>\n";
		$k++;
	}
	echo "</select>\n";
	echo "</td><td>\n";
	echo "Coller ci-dessous les donn�es � importer&nbsp;: <br />\n";
	//if (isset($_POST['appreciations'])) $appreciations = $_POST['appreciations']; $appreciations='';
	if (isset($_POST['appreciations'])) {$appreciations = preg_replace("/\\\\n/","\n",preg_replace("/\\\\r/","\r",$_POST['appreciations']));} else {$appreciations='';}
	echo "<textarea name='appreciations' rows='3' cols='40' class='wrap'>$appreciations</textarea>\n";
	echo "</td></tr></table>\n";
	echo "<input type='hidden' name='id_conteneur' value='$id_conteneur' />\n";
	echo "<input type='hidden' name='id_devoir' value='$id_devoir' />\n";
	echo "<input type='hidden' name='order_by' value='$order_by' />\n";
	echo "<center><input type='submit' value='Importer'  onclick=\"return confirm_abandon (this, change, '$themessage')\" /></center>\n";
	echo "<p><b>Remarque importante :</b> l'importation ne prend en compte que les �l�ves dont le nom est affich� ci-dessus !<br />Soyez donc vigilant � ne coller que les appr�ciations de ces �l�ves, dans le bon ordre.</p>\n";
	echo "</form></fieldset>\n";
}
// Fin ajout delineau -> fonctionnalit� de copier/coller d'appr�ciations

// Pour qu'un professeur puisse avoir une pr�f�rence d'affichage par d�faut ou non des quartiles:
$aff_quartiles_par_defaut=getPref($_SESSION['login'],'aff_quartiles_cn',"n");
$aff_photo_cn_par_defaut=getPref($_SESSION['login'],'aff_photo_cn',"n");
//echo $aff_quartiles_par_defaut."<br />";
?>
<br />
* En conformit� avec la CNIL, le professeur s'engage � ne faire figurer dans le carnet de notes que des notes et commentaires port�s � la connaissance de l'�l�ve (note et commentaire port�s sur la copie, ...).
<script type="text/javascript" language="javascript">
chargement = true;

// La v�rification ci-dessous est effectu�e apr�s le remplacement des notes sup�rieures � 20 par des z�ros.
// Ces �ventuelles erreurs de frappe ne sauteront pas aux yeux.
for(i=10;i<<?php echo $num_id; ?>;i++){
	eval("verifcol("+i+")");
}

// On donne le focus � la premi�re cellule lors du chargement de la page:
if(document.getElementById('n10')){
	document.getElementById('n10').focus();
}

function affichage_quartiles() {
	if(document.getElementById('div_quartiles').style.display=='none') {
		document.getElementById('div_quartiles').style.display='';
	}
	else {
		document.getElementById('div_quartiles').style.display='none';
	}
}
<?php
if($aff_quartiles_par_defaut=='y') {
	echo "affichage_quartiles();\n";
}
if($aff_photo_cn_par_defaut=='y') {
	echo "affiche_div_photo();\n";
}
?>
</script>
<?php require("../lib/footer.inc.php");?>
