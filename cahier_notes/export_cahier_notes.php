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

// INSERT INTO `droits` VALUES ('/cahier_notes/export_cahier_notes.php', 'F', 'V', 'F', 'F', 'F', 'F', 'V', 'Export CSV/ODS du cahier de notes', '');
if (!checkAccess()) {
    header("Location: ../logout.php?auto=1");
    die();
}


//On v�rifie si le module est activ�
if (getSettingValue("active_carnets_notes")!='y') {
    die("Le module n'est pas activ�.");
}



unset($id_racine);
$id_racine=isset($_POST["id_racine"]) ? $_POST["id_racine"] : (isset($_GET["id_racine"]) ? $_GET["id_racine"] : NULL);
//$is_posted=isset($_POST["is_posted"]) ? $_POST["is_posted"] : NULL;
$type_export=isset($_POST["type_export"]) ? $_POST["type_export"] : NULL;

$nettoyage=isset($_GET["nettoyage"]) ? $_GET["nettoyage"] : NULL;

/*
if(!isset($id_racine)) {
	if((isset($id_groupe))&&(isset($periode_num))) {
	}
}
*/

if($_SESSION['user_temp_directory']!='y') {
	$type_export="CSV";
}
else {
	if(getSettingValue("export_cn_ods")=='y') {
		$user_tmp=get_user_temp_directory();
		if(!$user_tmp) {
			/*
			$msg="Votre dossier temporaire n'est pas accessible.";
			header("Location: index.php?msg=".rawurlencode($msg));
			die();
			*/
			// L'export ODS n'est pas possible.
			$msg="Votre dossier temporaire n'est pas accessible. Seul l'export CSV est possible.";
			$type_export="CSV";
		}

		//$chemin_temp="../temp/".getSettingValue("temp_directory");
		$chemin_temp="../temp/".$user_tmp;


		$chemin_modele_ods="export_cn_modele_ods";

		if(isset($nettoyage)) {
			check_token();

			$id_groupe=isset($_GET['id_groupe']) ? $_GET['id_groupe'] : NULL;
			$periode_num=isset($_GET['periode_num']) ? $_GET['periode_num'] : NULL;

			if(!preg_match("/.ods$/i",$nettoyage)) {
				$msg="Le fichier n'est pas d'extension ODS.";
			}
			elseif(!preg_match("/^".$_SESSION['login']."/",$nettoyage)) {
				$msg="Vous tentez de supprimer des fichiers qui ne vous appartiennent pas.";
			}
			else {
				if(strlen(preg_replace("/[a-zA-Z0-9_\.]/","",strtr($nettoyage,"-","_")))!=0) {
					$msg="Le fichier propos� n'est pas valide: '".preg_replace("/[a-zA-Z0-9_\.]/","",strtr($nettoyage,"-","_"))."'";
				}
				else{
					if(!file_exists("$chemin_temp/$nettoyage")) {
						$msg="Le fichier choisi n'existe pas.";
					}
					else{
						unlink("$chemin_temp/$nettoyage");
						$msg=rawurlencode("Suppression r�ussie!");
					}
				}
			}

			// LES VARIABLES DEVRAIENT TOUJOURS ETRE RENSEIGNEES
			if((isset($id_groupe))&&(isset($periode_num))) {
				header("Location: index.php?id_groupe=$id_groupe&periode_num=$periode_num&msg=$msg");
			}
			else {
				header("Location: index.php?msg=$msg");
			}
			die();
		}
	}
}

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
	// ON NE DOIT JAMAIS ENTRER ICI
	// CELA RESSEMBLE A UNE SCORIE
	// POUR L'IMPORT CE TEST SERAIT UTILE, MAIS PAS POUR L'EXPORT
	// VERIFIER ET VIRER
    $mess=rawurlencode("Vous tentez de p�n�trer dans un carnet de notes dont la p�riode est bloqu�e !");
    header("Location: index.php?msg=$mess");
    die();
}


$matiere_nom = $current_group["matiere"]["nom_complet"];
$matiere_nom_court = $current_group["matiere"]["matiere"];
$nom_classe = $current_group["classlist_string"];

$periode_query = mysql_query("SELECT * FROM periodes WHERE id_classe = '$id_classe' ORDER BY num_periode");
$nom_periode = mysql_result($periode_query, $periode_num-1, "nom_periode");


//echo "\$is_posted=$is_posted<br />";
//echo "\$type_export=$type_export<br />";

//if((!isset($is_posted))||(!isset($type_export))) {
if(!isset($type_export)) {
	//**************** EN-TETE *****************
	$titre_page = "Export des notes";
	require_once("../lib/header.inc");
	//**************** FIN EN-TETE *****************

	//$titre=htmlentities($current_group['description'])." (".$nom_periode.")";
	$titre=htmlentities($current_group['name'])." ".$current_group["classlist_string"]." (".$nom_periode.")";
	$titre.=" - EXPORT";

	// Mettre la ligne de liens de retour,...
    echo "<div class='norme'>\n";
	echo "<form enctype=\"multipart/form-data\" name= \"form1\" action=\"".$_SERVER['PHP_SELF']."\" method=\"get\">\n";
    echo "<p class='bold'>\n";

	//echo "<a href=\"../accueil.php\"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour accueil </a>|\n";
    //echo "<a href='index.php?id_groupe=".$current_group["id"]."&amp;periode_num=$periode_num'> ".htmlentities($current_group['name'])." ".$current_group["classlist_string"]." (".$nom_periode.")"." </a>|\n";

    echo "<a href='index.php?id_groupe=".$current_group["id"]."&amp;periode_num=$periode_num'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour ".htmlentities($current_group['name'])." ".$current_group["classlist_string"]." (".$nom_periode.")"." </a>|\n";


	if($_SESSION['statut']=='professeur') {
		$login_prof_groupe_courant=$_SESSION["login"];
	}
	else {
		//$tmp_current_group=get_group($id_groupe);
		//$login_prof_groupe_courant=$tmp_current_group["profs"]["list"][0];
		$login_prof_groupe_courant=$current_group["profs"]["list"][0];
	}

	$tab_groups = get_groups_for_prof($login_prof_groupe_courant,"classe puis mati�re");

	if(!empty($tab_groups)) {

		$chaine_options_enseignements="";

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

					//$chaine_options_enseignements.="<option value='".$tab_groups[$loop]['id']."' selected='true'>".$tab_groups[$loop]['description']." (".$tab_groups[$loop]['classlist_string'].")</option>\n";
					$chaine_options_enseignements.="<option value='".$id_racine."' selected='true'>".$tab_groups[$loop]['description']." (".$tab_groups[$loop]['classlist_string'].")</option>\n";

					$temoin_tmp=1;
					if(isset($tab_groups[$loop+1])){
						$id_grp_suiv=$tab_groups[$loop+1]['id'];

						//$chaine_options_enseignements.="<option value='".$tab_groups[$loop+1]['id']."'>".$tab_groups[$loop+1]['name']." (".$tab_groups[$loop+1]['classlist_string'].")</option>\n";
					}
					else{
						$id_grp_suiv=0;
					}
				}
				else {
					$tmp_id_cahier_notes=get_cn_from_id_groupe_periode_num($tab_groups[$loop]['id'], $periode_num);
					if($tmp_id_cahier_notes!='') {
						//$chaine_options_enseignements.="<option value='".$tab_groups[$loop]['id']."'>".$tab_groups[$loop]['description']." (".$tab_groups[$loop]['classlist_string'].")</option>\n";
						$chaine_options_enseignements.="<option value='".$tmp_id_cahier_notes."'>".$tab_groups[$loop]['description']." (".$tab_groups[$loop]['classlist_string'].")</option>\n";
					}
				}

				if($temoin_tmp==0){
					$id_grp_prec=$tab_groups[$loop]['id'];

					//$chaine_options_enseignements.="<option value='".$tab_groups[$loop]['id']."'>".$tab_groups[$loop]['name']." (".$tab_groups[$loop]['classlist_string'].")</option>\n";
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

		if(($chaine_options_enseignements!="")&&($nb_groupes_suivies>1)) {

			echo "<script type='text/javascript'>
	// Initialisation
	change='no';

	function confirm_changement_enseignement(thechange, themessage)
	{
		if (!(thechange)) thechange='no';
		if (thechange != 'yes') {
			document.form1.submit();
		}
		else{
			var is_confirmed = confirm(themessage);
			if(is_confirmed){
				document.form1.submit();
			}
			else{
				document.getElementById('id_racine').selectedIndex=$num_groupe;
			}
		}
	}
</script>\n";

			echo "<input type='hidden' name='periode_num' id='periode_num' value='$periode_num' />\n";
			//echo " | <select name='id_classe' onchange=\"document.forms['form1'].submit();\">\n";
			echo "Export en p�riode $periode_num: <select name='id_racine' id='id_racine' onchange=\"confirm_changement_enseignement(change, '$themessage');\">\n";
			echo $chaine_options_enseignements;
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
	echo "</form>\n";
	echo "</div>";


	echo "<h2>$titre</h2>\n";
	echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>\n";
	echo add_token_field();
	echo "<p>Vous pouvez effectuer un export:<br />\n";
	echo "<input type='hidden' name='id_racine' value='$id_racine' />\n";
	//echo "<input type='hidden' name='is_posted' value='yes' />\n";
	echo "<input type='radio' name='type_export' id='type_export_csv' value='CSV' checked /><label for='type_export_csv' style='cursor: pointer;'> fichier CSV</label><br />\n";

	if(getSettingValue("export_cn_ods")=='y') {
		echo "<input type='radio' name='type_export' id='type_export_ods' value='ODS' /><label for='type_export_ods' style='cursor: pointer;'> feuille de tableur ODS</label><br />\n";
	}
	echo "<input type='submit' name='envoyer' value='Valider' /></p>\n";
	echo "</form>\n";

	// On pourrait ajouter le m�nage des fichiers de $chemin_ods ici en virant tout ce qui commence par $_SESSION['login'] et se termine par '.ods'...

	require("../lib/footer.inc.php");
	die();
}

check_token(false);

$nom_fic=$_SESSION['login'];
$nom_fic.="_cn";
$nom_fic.="_".preg_replace("/[^a-zA-Z0-9_\. - ]/","",remplace_accents($current_group['description'],'all'));
$nom_fic.="_".preg_replace("/[^a-zA-Z0-9_\. - ]/","",remplace_accents($current_group["classlist_string"],'all'));
$nom_fic.="_".preg_replace("/[^a-zA-Z0-9_\. - ]/","",remplace_accents($nom_periode,'all'));

if($type_export=="CSV") {

	// G�n�ration du CSV

	$nom_fic.=".csv";

	$now=gmdate('D, d M Y H:i:s').' GMT';
	send_file_download_headers('text/x-csv',$nom_fic);

	// Initialisation du contenu du fichier:
	$fd='';


	// On fait la liste des devoirs de ce carnet de notes
	$appel_dev = mysql_query("select * from cn_devoirs where (id_racine='$id_racine') order by id_conteneur,date");
	$nb_dev  = mysql_num_rows($appel_dev);

	$ligne_entete="GEPI_INFOS;GEPI_LOGIN_ELEVE;NOM;PRENOM;CLASSE;MOYENNE;GEPI_COL_1ER_DEVOIR";
	//echo "$ligne_entete<br />\n";
	$fd.="$ligne_entete\n";

	unset($id_dev);
	$id_dev=array();

	unset($ligne_info_dev);
	$ligne_info_dev=array();

	$ligne_info_dev[]="GEPI_DEV_NOM_COURT;;;;;Nom court du devoir:";
	$ligne_info_dev[]="GEPI_DEV_COEF;;;;;Coefficient:";
	$ligne_info_dev[]="GEPI_DEV_NOTE_SUR;;;;;Notation sur:";
	$ligne_info_dev[]="GEPI_DEV_DATE;;;;;Date:";
	$ligne_info_dev[]="INFOS;Login;Nom;Pr�nom;Classe;Moyennes:";

	// Pour mettre dans un tableur et am�liorer... voir http://christianwtd.free.fr/index.php?rubrique=LecNotesClasse
	// On peut faire plus simple: La fonction MOYENNE de OpenOffice Calc tient compte des valeurs non num�riques.

	$cpt=0;
	while($lig_dev=mysql_fetch_object($appel_dev)) {

		$id_dev[$cpt]=$lig_dev->id;
		$nomc_dev[$cpt]=$lig_dev->nom_court;
		// Probl�me avec les 17.5 qui sont convertis en dates
		//$coef_dev[$cpt]=$lig_dev->coef;
		$coef_dev[$cpt]=strtr($lig_dev->coef,".",",");
		$note_sur_dev[$cpt]=$lig_dev->note_sur;
		// Probl�me avec le format DATETIME
		//$date_dev[$cpt]=$lig_dev->date;
		$tmptab=explode(" ",$lig_dev->date);
		//$date_dev[$cpt]=$tmptab[0];
		$tmptab2=explode("-",$tmptab[0]);
		$date_dev[$cpt]=$tmptab2[2]."/".$tmptab2[1]."/".$tmptab2[0];

		$ligne_info_dev[0].=";".$nomc_dev[$cpt];
		$ligne_info_dev[1].=";".$coef_dev[$cpt];
		$ligne_info_dev[2].=";".$note_sur_dev[$cpt];
		$ligne_info_dev[3].=";".$date_dev[$cpt];
		// A am�liorer par la suite: calculer la moyenne de la classe:
		//$ligne_info_dev[3].="Moyenne_classe;";
		//$ligne_info_dev[3].=";";

		//$sql="SELECT SUM(note) AS somme,COUNT(note) AS nb FROM cn_devoirs WHERE (id='$id_dev[$cpt]' AND statut='')";
		$sql="SELECT SUM(note) AS somme,COUNT(note) AS nb FROM cn_notes_devoirs WHERE (id_devoir='$id_dev[$cpt]' AND statut='')";
		$res_moy=mysql_query($sql);
		if($res_moy) {
			$lig_moy=mysql_fetch_array($res_moy);
			if($lig_moy[1]!=0) {
				$moy=strtr(round(10*$lig_moy[0]/$lig_moy[1])/10,".",",");
				//echo "$lig_moy[0]/$lig_moy[1]=".$moy."<br />";
				$ligne_info_dev[4].=";".$moy;
			}
			else{
				$ligne_info_dev[4].=";";
			}
		}
		else{
			$ligne_info_dev[4].=";";
		}

		$cpt++;
	}

	for($i=0;$i<count($ligne_info_dev);$i++) {
		//echo "$ligne_info_dev[$i]<br />\n";
		$fd.="$ligne_info_dev[$i]\n";
	}



	$i = 0;
	//$num_id=10;
	//$current_displayed_line = 0;

	$order_by="";

	// On commence par mettre la liste dans l'ordre souhait�
	if ($order_by != "classe") {
		$liste_eleves = $current_group["eleves"][$periode_num]["users"];
	} else {
		// Ici, on tri par classe
		// On va juste cr�er une liste des �l�ves pour chaque classe
		$tab_classes = array();
		foreach($current_group["classes"]["list"] as $classe_id) {
			$tab_classes[$classe_id] = array();
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

	foreach ($liste_eleves as $eleve) {
		$eleve_login[$i] = $eleve["login"];
		$eleve_nom[$i] = $eleve["nom"];
		$eleve_prenom[$i] = $eleve["prenom"];
		$eleve_classe[$i] = $current_group["classes"]["classes"][$eleve["classe"]]["classe"];
		$eleve_id_classe[$i] = $current_group["classes"]["classes"][$eleve["classe"]]["id"];
		$somme_coef = 0;

		$k=0;

		$ligne_eleve="GEPI_LOGIN_ELEVE;".$eleve_login[$i].";".$eleve_nom[$i].";".$eleve_prenom[$i].";".$eleve_classe[$i];

		// A am�liorer par la suite: R�cup�rer/calculer la moyenne de l'�l�ve
		//$ligne_eleve.="Moyenne_calcul�e;";
		$ligne_eleve.=";";
		// Calculer la moyenne de l'�l�ve est assez illusoire si on ne g�re pas les boites et leurs coefficients...

		while ($k < $nb_dev) {
			//echo "<p>id_dev[$k]=$id_dev[$k]<br />\n";

			$note_query = mysql_query("SELECT * FROM cn_notes_devoirs WHERE (login='$eleve_login[$i]' AND id_devoir='$id_dev[$k]')");

			if(mysql_num_rows($note_query)==0) {
				$eleve_note='';
				$eleve_statut='-';
			}
			else{
				$eleve_statut = @mysql_result($note_query, 0, "statut");
				$eleve_note = @mysql_result($note_query, 0, "note");
			}

			//$eleve_login_note = $eleve_login[$i]."_note";

			//echo "$eleve_login[$i]: $eleve_note - $eleve_statut<br />";

			// Probl�me avec les 17.5 qui sont convertis en dates -> 17/05/07
			$eleve_note=strtr($eleve_note,".",",");

			if($eleve_statut=='v') {
				// Pas de note saisie -> statut = v pour vide
				$eleve_note="-";
			}
			elseif($eleve_statut!='') {
				$eleve_note=$eleve_statut;
			}

			$ligne_eleve.=";".$eleve_note;

			$k++;
		}

		//echo "$ligne_eleve<br />\n";
		$fd.="$ligne_eleve\n";

		$i++;
	}

	// On renvoye le fichier vers le navigateur:
	echo $fd;
	die();
}
//elseif($type_export=="ODS") {
elseif(($type_export=="ODS")&&(getSettingValue("export_cn_ods")=='y')) {

	// G�n�ration d'un fichier tableur...
	// ... il faudra pr�voir un nettoyage.
	// Il faudrait que l'option g�n�rer des ODS soit activable/d�sactivable par l'admin

	// On fait la liste des devoirs de ce carnet de notes
	$appel_dev = mysql_query("select * from cn_devoirs where (id_racine='$id_racine') order by id_conteneur,date");
	$nb_dev  = mysql_num_rows($appel_dev);

	unset($id_dev);
	$id_dev=array();

	$cpt=0;
	while($lig_dev=mysql_fetch_object($appel_dev)) {

		$id_dev[$cpt]=$lig_dev->id;
		// Certains caract�res comme le '�' que l'on met par exemple dans 'Devoir n�2' posent pb...
		$nomc_dev[$cpt]=preg_replace("/[^a-zA-Z0-9_\. - ]/","",remplace_accents($lig_dev->nom_court,'all'));

		// Probl�me avec les 17.5 qui sont convertis en dates
		//$coef_dev[$cpt]=$lig_dev->coef;
		$coef_dev[$cpt]=strtr($lig_dev->coef,".",",");
		$note_sur_dev[$cpt]=$lig_dev->note_sur;

		// Probl�me avec le format DATETIME
		//$date_dev[$cpt]=$lig_dev->date;
		$tmptab=explode(" ",$lig_dev->date);
		$date_dev[$cpt]=$tmptab[0];
		// Pour le fichier ODS, on veut des dates au format aaaa-mm-jj
		$tmptab2=explode("-",$tmptab[0]);
		if(strlen($tmptab2[0])==4) {$tmptab2[0]=substr($tmptab2[0],2,2);}
		$date_dev_fr[$cpt]=$tmptab2[2]."/".$tmptab2[1]."/".$tmptab2[0];


		// Moyenne
		$moy="";
		$sql="SELECT SUM(note) AS somme,COUNT(note) AS nb FROM cn_notes_devoirs WHERE (id_devoir='$id_dev[$cpt]' AND statut='')";
		$res_moy=mysql_query($sql);
		if($res_moy) {
			$lig_moy=mysql_fetch_array($res_moy);
			if($lig_moy[1]!=0) {
				$moy=strtr(round(10*$lig_moy[0]/$lig_moy[1])/10,".",",");
			}
		}
		$moy_dev[$cpt]=$moy;

		$cpt++;
	}


	// G�n�ration du fichier ODS
	//$nom_fic.=".ods";

	$instant=getdate();
	$heure=$instant['hours'];
	$minute=$instant['minutes'];
	$seconde=$instant['seconds'];
	$mois=$instant['mon'];
	$jour=$instant['mday'];
	$annee=$instant['year'];
	$chaine_tmp="$annee-".sprintf("%02d",$mois)."-".sprintf("%02d",$jour)."-".sprintf("%02d",$heure)."-".sprintf("%02d",$minute)."-".sprintf("%02d",$seconde);

	$nom_fic.="_".$chaine_tmp.".ods";



	// Fichier content.xml
	//echo "\$tmp_fich=$tmp_fich<br />\n";
	$tmp_fich="content_".$_SESSION['login'];
	//echo "\$tmp_fich=$tmp_fich<br />\n";
	$tmp_fich.="_".strtr(microtime()," ","_");
	//echo "\$tmp_fich=$tmp_fich<br />\n";
	$tmp_fich.=".xml";
	//echo "\$tmp_fich=$tmp_fich<br />\n";

	$tmp_fich=$chemin_temp."/".$tmp_fich;

	$fichier_tmp_xml=fopen("$tmp_fich","w+");
	$ecriture=fwrite($fichier_tmp_xml,'<?xml version="1.0" encoding="UTF-8"?>
<office:document-content xmlns:office="urn:oasis:names:tc:opendocument:xmlns:office:1.0" xmlns:style="urn:oasis:names:tc:opendocument:xmlns:style:1.0" xmlns:text="urn:oasis:names:tc:opendocument:xmlns:text:1.0" xmlns:table="urn:oasis:names:tc:opendocument:xmlns:table:1.0" xmlns:draw="urn:oasis:names:tc:opendocument:xmlns:drawing:1.0" xmlns:fo="urn:oasis:names:tc:opendocument:xmlns:xsl-fo-compatible:1.0" xmlns:xlink="http://www.w3.org/1999/xlink" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:meta="urn:oasis:names:tc:opendocument:xmlns:meta:1.0" xmlns:number="urn:oasis:names:tc:opendocument:xmlns:datastyle:1.0" xmlns:svg="urn:oasis:names:tc:opendocument:xmlns:svg-compatible:1.0" xmlns:chart="urn:oasis:names:tc:opendocument:xmlns:chart:1.0" xmlns:dr3d="urn:oasis:names:tc:opendocument:xmlns:dr3d:1.0" xmlns:math="http://www.w3.org/1998/Math/MathML" xmlns:form="urn:oasis:names:tc:opendocument:xmlns:form:1.0" xmlns:script="urn:oasis:names:tc:opendocument:xmlns:script:1.0" xmlns:ooo="http://openoffice.org/2004/office" xmlns:ooow="http://openoffice.org/2004/writer" xmlns:oooc="http://openoffice.org/2004/calc" xmlns:dom="http://www.w3.org/2001/xml-events" xmlns:xforms="http://www.w3.org/2002/xforms" xmlns:xsd="http://www.w3.org/2001/XMLSchema" xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance" office:version="1.0"><office:scripts/><office:font-face-decls><style:font-face style:name="Courier 10 Pitch" svg:font-family="&apos;Courier 10 Pitch&apos;" style:font-pitch="fixed"/><style:font-face style:name="DejaVu Sans" svg:font-family="&apos;DejaVu Sans&apos;" style:font-family-generic="swiss" style:font-pitch="variable"/><style:font-face style:name="DejaVu Sans1" svg:font-family="&apos;DejaVu Sans&apos;" style:font-family-generic="system" style:font-pitch="variable"/></office:font-face-decls><office:automatic-styles><style:style style:name="co1" style:family="table-column"><style:table-column-properties fo:break-before="auto" style:column-width="0.245cm"/></style:style><style:style style:name="co2" style:family="table-column"><style:table-column-properties fo:break-before="auto" style:column-width="2.267cm"/></style:style><style:style style:name="co3" style:family="table-column"><style:table-column-properties fo:break-before="auto" style:column-width="3.166cm"/></style:style><style:style style:name="co4" style:family="table-column"><style:table-column-properties fo:break-before="auto" style:column-width="4.394cm"/></style:style><style:style style:name="ro1" style:family="table-row"><style:table-row-properties style:row-height="0.254cm" fo:break-before="auto" style:use-optimal-row-height="false"/></style:style><style:style style:name="ro2" style:family="table-row"><style:table-row-properties style:row-height="0.51cm" fo:break-before="auto" style:use-optimal-row-height="true"/></style:style><style:style style:name="ro3" style:family="table-row"><style:table-row-properties style:row-height="0.436cm" fo:break-before="auto" style:use-optimal-row-height="true"/></style:style><style:style style:name="ro4" style:family="table-row"><style:table-row-properties style:row-height="0.459cm" fo:break-before="auto" style:use-optimal-row-height="true"/></style:style><style:style style:name="ta1" style:family="table" style:master-page-name="Default"><style:table-properties table:display="true" style:writing-mode="lr-tb"/></style:style><number:date-style style:name="N37" number:automatic-order="true"><number:day number:style="long"/><number:text>/</number:text><number:month number:style="long"/><number:text>/</number:text><number:year/></number:date-style><style:style style:name="ce1" style:family="table-cell" style:parent-style-name="Default"><style:text-properties style:font-name="Courier 10 Pitch"/></style:style><style:style style:name="ce2" style:family="table-cell" style:parent-style-name="Default"><style:table-cell-properties fo:border="0.002cm solid #000000"/><style:text-properties style:font-name="Courier 10 Pitch" fo:font-weight="bold"/></style:style><style:style style:name="ce3" style:family="table-cell" style:parent-style-name="Default"><style:table-cell-properties fo:border="none"/><style:text-properties style:font-name="Courier 10 Pitch"/><style:map style:condition="is-true-formula(OR(ISBLANK([.C6]);ISBLANK([.$C6])))" style:apply-style-name="Default" style:base-cell-address="Notes.C6"/><style:map style:condition="is-true-formula(ISODD(ROW([.$C6])))" style:apply-style-name="Impair" style:base-cell-address="Notes.C6"/><style:map style:condition="is-true-formula(ISEVEN(ROW([.$C6])))" style:apply-style-name="Pair" style:base-cell-address="Notes.C6"/></style:style><style:style style:name="ce4" style:family="table-cell" style:parent-style-name="Default"><style:table-cell-properties style:text-align-source="fix" style:repeat-content="false" fo:border="0.002cm solid #000000"/><style:paragraph-properties fo:text-align="center" fo:margin-left="0cm"/><style:text-properties style:font-name="Courier 10 Pitch" fo:font-weight="bold"/></style:style><style:style style:name="ce5" style:family="table-cell" style:parent-style-name="Default"><style:table-cell-properties style:text-align-source="fix" style:repeat-content="false" fo:border="none"/><style:paragraph-properties fo:text-align="center" fo:margin-left="0cm"/><style:text-properties style:font-name="Courier 10 Pitch"/><style:map style:condition="is-true-formula(OR(ISBLANK([.C6]);ISBLANK([.$C6])))" style:apply-style-name="Default" style:base-cell-address="Notes.C6"/><style:map style:condition="is-true-formula(ISODD(ROW([.$C6])))" style:apply-style-name="Impair" style:base-cell-address="Notes.C6"/><style:map style:condition="is-true-formula(ISEVEN(ROW([.$C6])))" style:apply-style-name="Pair" style:base-cell-address="Notes.C6"/></style:style><style:style style:name="ce6" style:family="table-cell" style:parent-style-name="Default"><style:table-cell-properties style:text-align-source="fix" style:repeat-content="false" fo:border="none"/><style:paragraph-properties fo:text-align="center" fo:margin-left="0cm"/><style:text-properties style:font-name="Courier 10 Pitch"/><style:map style:condition="is-true-formula(NOT(ISBLANK([.G2])))" style:apply-style-name="Entete" style:base-cell-address="Notes.G2"/></style:style><style:style style:name="ce7" style:family="table-cell" style:parent-style-name="Default" style:data-style-name="N37"><style:table-cell-properties style:text-align-source="fix" style:repeat-content="false" fo:border="none"/><style:paragraph-properties fo:text-align="center" fo:margin-left="0cm"/><style:text-properties style:font-name="Courier 10 Pitch"/><style:map style:condition="is-true-formula(NOT(ISBLANK([.G2])))" style:apply-style-name="Entete" style:base-cell-address="Notes.G2"/></style:style><style:style style:name="ce8" style:family="table-cell" style:parent-style-name="Default"><style:table-cell-properties style:text-align-source="fix" style:repeat-content="false" fo:border="none"/><style:paragraph-properties fo:text-align="center" fo:margin-left="0cm"/><style:text-properties style:font-name="Courier 10 Pitch"/><style:map style:condition="is-true-formula(NOT(ISBLANK([.G2])))" style:apply-style-name="Entete" style:base-cell-address="Notes.G5"/></style:style>');

	// AJOUT du style gras pour le feuillet Infos:
	$ecriture=fwrite($fichier_tmp_xml,'<style:style style:name="ce14" style:family="table-cell" style:parent-style-name="Default"><style:text-properties fo:font-weight="bold"/></style:style>');

	//$ecriture=fwrite($fichier_tmp_xml,'</office:automatic-styles><office:body><office:spreadsheet><table:table table:name="Notes" table:style-name="ta1" table:print="false">');

	$ecriture=fwrite($fichier_tmp_xml,'<style:style style:name="P1" style:family="paragraph"><style:paragraph-properties fo:text-align="center"/></style:style>');

	$ecriture=fwrite($fichier_tmp_xml,'</office:automatic-styles><office:body><office:spreadsheet><table:table table:name="Notes" table:style-name="ta1" table:print="false">');

	$ecriture=fwrite($fichier_tmp_xml,'<office:forms form:automatic-focus="false" form:apply-design-mode="false"><form:form form:name="Standard" form:apply-filter="true" form:command-type="table" form:control-implementation="ooo:com.sun.star.form.component.Form" office:target-frame="" xlink:href=""><form:properties><form:property form:property-name="GroupBy" office:value-type="string" office:string-value=""/><form:property form:property-name="HavingClause" office:value-type="string" office:string-value=""/><form:property form:property-name="MaxRows" office:value-type="float" office:value="0"/><form:property form:property-name="UpdateCatalogName" office:value-type="string" office:string-value=""/><form:property form:property-name="UpdateSchemaName" office:value-type="string" office:string-value=""/><form:property form:property-name="UpdateTableName" office:value-type="string" office:string-value=""/></form:properties><form:button form:name="PushButton" form:control-implementation="ooo:com.sun.star.form.component.CommandButton" form:id="control1" form:label="Export_CSV" office:target-frame="" xlink:href="" form:image-data="" form:delay-for-repeat="PT0.50S" form:image-position="center"><form:properties><form:property form:property-name="DefaultControl" office:value-type="string" office:string-value="com.sun.star.form.control.CommandButton"/></form:properties><office:event-listeners><script:event-listener script:language="ooo:script" script:event-name="form:performaction" xlink:href="vnd.sun.star.script:Standard.Module1.Export_CSV?language=Basic&amp;location=document"/></office:event-listeners></form:button></form:form></office:forms>');


	// ===================

	$ecriture=fwrite($fichier_tmp_xml,'<table:table-column table:style-name="co1" table:visibility="collapse" table:default-cell-style-name="Default"/><table:table-column table:style-name="co2" table:visibility="collapse" table:default-cell-style-name="Default"/><table:table-column table:style-name="co3" table:default-cell-style-name="ce3"/><table:table-column table:style-name="co2" table:default-cell-style-name="ce3"/><table:table-column table:style-name="co2" table:default-cell-style-name="ce5"/><table:table-column table:style-name="co4" table:default-cell-style-name="ce5"/><table:table-column table:style-name="co2" table:number-columns-repeated="46" table:default-cell-style-name="ce5"/>');

	// ===================
	// Ligne 1

	$ecriture=fwrite($fichier_tmp_xml,'<table:table-row table:style-name="ro1" table:visibility="collapse">');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell office:value-type="string"><text:p>GEPI_INFOS</text:p></table:table-cell>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell office:value-type="string"><text:p>GEPI_LOGIN_ELEVE</text:p></table:table-cell>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="Default" office:value-type="string"><text:p>NOM</text:p></table:table-cell>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="Default" office:value-type="string"><text:p>PRENOM</text:p></table:table-cell>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="Default" office:value-type="string"><text:p>CLASSE</text:p></table:table-cell>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="Default" office:value-type="string"><text:p>MOYENNE</text:p></table:table-cell>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="Default" office:value-type="string"><text:p>GEPI_COL_1ER_DEVOIR</text:p></table:table-cell>');

	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="Default" table:number-columns-repeated="45"/>');
	$ecriture=fwrite($fichier_tmp_xml,'</table:table-row>');

	// ===================
	// Noms courts:

	$ecriture=fwrite($fichier_tmp_xml,'<table:table-row table:style-name="ro2">');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell office:value-type="string"><text:p>GEPI_DEV_NOM_COURT</text:p></table:table-cell>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell/>');


	//$ecriture=fwrite($fichier_tmp_xml,'');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce1"><draw:control table:end-cell-address="Notes.E4" table:end-x="0.039cm" table:end-y="0.022cm" draw:z-index="0" draw:text-style-name="P1" svg:width="2.406cm" svg:height="0.622cm" svg:x="3.044cm" svg:y="0.414cm" draw:control="control1"/></table:table-cell>');

	//$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce1" table:number-columns-repeated="3"/>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce1" table:number-columns-repeated="2"/>');

	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce4" office:value-type="string"><text:p>Nom court du devoir</text:p></table:table-cell>');


	//$ecriture=fwrite($fichier_tmp_xml,'');

	//for($i=0;$i<count($nomc_dev);$i++) {
	for($i=0;$i<$nb_dev;$i++) {
		$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce6" office:value-type="string"><text:p>'.$nomc_dev[$i].'</text:p></table:table-cell>');
	}

	// PB: J'ai pr�vu un maximum de 46 colonnes de devoirs...
	//$nb_vide=46-count($nomc_dev);
	$nb_vide=46-$nb_dev;
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce6" table:number-columns-repeated="'.$nb_vide.'"/>');
	$ecriture=fwrite($fichier_tmp_xml,'</table:table-row>');

	// ===================
	// Coefficients:

	$ecriture=fwrite($fichier_tmp_xml,'<table:table-row table:style-name="ro2">');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell office:value-type="string"><text:p>GEPI_DEV_COEF</text:p></table:table-cell>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell/>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce1" table:number-columns-repeated="3"/>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce4" office:value-type="string"><text:p>Coefficient</text:p></table:table-cell>');

	//for($i=0;$i<count($coef_dev);$i++) {
	for($i=0;$i<$nb_dev;$i++) {
		$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce6" office:value-type="float" office:value="'.strtr($coef_dev[$i],",",".").'"><text:p>'.$coef_dev[$i].'</text:p></table:table-cell>');
	}

	// PB: J'ai pr�vu un maximum de 46 colonnes de devoirs...
	//$nb_vide=46-count($coef_dev);
	$nb_vide=46-$nb_dev;
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:number-columns-repeated="'.$nb_vide.'" table:style-name="ce6"/>');
	$ecriture=fwrite($fichier_tmp_xml,'</table:table-row>');

	// ===================
	// Note sur:

	$ecriture=fwrite($fichier_tmp_xml,'<table:table-row table:style-name="ro2">');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell office:value-type="string"><text:p>GEPI_DEV_NOTE_SUR</text:p></table:table-cell>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell/>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce1" table:number-columns-repeated="3"/>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce4" office:value-type="string"><text:p>Note sur :</text:p></table:table-cell>');

	//for($i=0;$i<count($coef_dev);$i++) {
	for($i=0;$i<$nb_dev;$i++) {
		$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce6" office:value-type="float" office:value="'.strtr($note_sur_dev[$i],",",".").'"><text:p>'.$note_sur_dev[$i].'</text:p></table:table-cell>');
	}

	// PB: J'ai pr�vu un maximum de 46 colonnes de devoirs...
	//$nb_vide=46-count($coef_dev);
	$nb_vide=46-$nb_dev;
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:number-columns-repeated="'.$nb_vide.'" table:style-name="ce6"/>');
	$ecriture=fwrite($fichier_tmp_xml,'</table:table-row>');

	// ===================
	// Dates:

	$ecriture=fwrite($fichier_tmp_xml,'<table:table-row table:style-name="ro2">');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell office:value-type="string"><text:p>GEPI_DEV_DATE</text:p></table:table-cell>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell/>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce1" table:number-columns-repeated="3"/>');
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce4" office:value-type="string"><text:p>Date</text:p></table:table-cell>');

	//for($i=0;$i<count($date_dev);$i++) {
	for($i=0;$i<$nb_dev;$i++) {
		$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce7" office:value-type="date" office:date-value="'.$date_dev[$i].'"><text:p>'.$date_dev_fr[$i].'</text:p></table:table-cell>');
	}

	// PB: J'ai pr�vu un maximum de 46 colonnes de devoirs...
	//$nb_vide=46-count($date_dev);
	$nb_vide=46-$nb_dev;
	$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce7" table:number-columns-repeated="'.$nb_vide.'"/>');
	$ecriture=fwrite($fichier_tmp_xml,'</table:table-row>');

	// ===================

	$ecriture=fwrite($fichier_tmp_xml,'<table:table-row table:style-name="ro2"><table:table-cell office:value-type="string"><text:p>INFOS</text:p></table:table-cell><table:table-cell office:value-type="string"><text:p>Login</text:p></table:table-cell><table:table-cell table:style-name="ce2" office:value-type="string"><text:p>Nom</text:p></table:table-cell><table:table-cell table:style-name="ce2" office:value-type="string"><text:p>Prenom</text:p></table:table-cell><table:table-cell table:style-name="ce4" office:value-type="string"><text:p>Classe</text:p></table:table-cell><table:table-cell table:style-name="ce4" office:value-type="string"><text:p>Moyennes</text:p></table:table-cell>');

	$alphabet="ABCDEFGHIJKLMNOPQRSTUVWXYZ";
	$tabcol=array();
	for($i=0;$i<strlen($alphabet);$i++) {
		$tabcol[$i]=substr($alphabet,$i,1);
	}
	for($i=strlen($alphabet);$i<2*strlen($alphabet);$i++) {
		$tabcol[$i]="A".substr($alphabet,$i-strlen($alphabet),1);
	}

	/*
	for($i=0;$i<count($tabcol);$i++) {
		echo $tabcol[$i]." - ";
	}
	*/

	// OpenOffice recalcule les valeurs lors de l'ouverture du document...
	$valeur_defaut=0;
	// On pourrait ne pas mettre de valeur...

	$num_col=6;

	//for($i=0;$i<count($moy_dev);$i++) {
	for($i=0;$i<$nb_dev;$i++) {
		//$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce8" table:formula="oooc:=IF([.'.$tabcol[$num_col].'3]=&quot;&quot;;&quot;&quot;;IF(ISERROR(AVERAGE([.'.$tabcol[$num_col].'6:.'.$tabcol[$num_col].'100]));&quot;&quot;;AVERAGE([.'.$tabcol[$num_col].'6:.'.$tabcol[$num_col].'100])))" office:value-type="float" office:value="'.$valeur_defaut.'"><text:p>'.$valeur_defaut.'</text:p></table:table-cell>');

		$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce8" table:formula="oooc:=IF([.'.$tabcol[$num_col].'3]=&quot;&quot;;&quot;&quot;;IF(ISERROR(AVERAGE([.'.$tabcol[$num_col].'7:.'.$tabcol[$num_col].'100]));&quot;&quot;;AVERAGE([.'.$tabcol[$num_col].'7:.'.$tabcol[$num_col].'100])))" office:value-type="float" office:value="'.strtr($moy_dev[$i],',','.').'"><text:p>'.$moy_dev[$i].'</text:p></table:table-cell>');
		//echo "$num_col - ";
		$num_col++;
	}

	for($i=$num_col;$i<count($tabcol);$i++) {
		$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:style-name="ce8" table:formula="oooc:=IF([.'.$tabcol[$num_col].'3]=&quot;&quot;;&quot;&quot;;IF(ISERROR(AVERAGE([.'.$tabcol[$num_col].'7:.'.$tabcol[$num_col].'100]));&quot;&quot;;AVERAGE([.'.$tabcol[$num_col].'7:.'.$tabcol[$num_col].'100])))"><text:p/></table:table-cell>');
		//echo "$num_col - ";
		$num_col++;
	}

	$ecriture=fwrite($fichier_tmp_xml,'</table:table-row>');

	// ===================

	$i = 0;

	$order_by="";

	// On commence par mettre la liste dans l'ordre souhait�
	if($order_by != "classe") {
		$liste_eleves = $current_group["eleves"][$periode_num]["users"];
	}
	else{
		// Ici, on tri par classe
		// On va juste cr�er une liste des �l�ves pour chaque classe
		$tab_classes = array();
		foreach($current_group["classes"]["list"] as $classe_id) {
			$tab_classes[$classe_id] = array();
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

	$num_lig=7;
	foreach ($liste_eleves as $eleve) {
		$eleve_login[$i]=$eleve["login"];
		$eleve_nom[$i]=$eleve["nom"];
		$eleve_prenom[$i]=$eleve["prenom"];
		$eleve_classe[$i]=$current_group["classes"]["classes"][$eleve["classe"]]["classe"];
		$eleve_id_classe[$i]=$current_group["classes"]["classes"][$eleve["classe"]]["id"];
		$somme_coef=0;

		$k=0;

		//$ligne_eleve="GEPI_LOGIN_ELEVE;".$eleve_login[$i].";".$eleve_nom[$i].";".$eleve_prenom[$i].";".$eleve_classe[$i];

		$ecriture=fwrite($fichier_tmp_xml,'<table:table-row table:style-name="ro2">');
		$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell office:value-type="string"><text:p>GEPI_LOGIN_ELEVE</text:p></table:table-cell>');

		$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell office:value-type="string"><text:p>'.$eleve_login[$i].'</text:p></table:table-cell><table:table-cell office:value-type="string"><text:p>'.caract_ooo($eleve_nom[$i]).'</text:p></table:table-cell><table:table-cell office:value-type="string"><text:p>'.caract_ooo($eleve_prenom[$i]).'</text:p></table:table-cell><table:table-cell office:value-type="string"><text:p>'.caract_ooo($eleve_classe[$i]).'</text:p></table:table-cell>');


		// OpenOffice recalcule les valeurs lors de l'ouverture du document...
		$valeur_defaut=0;

		//$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:formula="oooc:=IF([.B'.$num_lig.']=&quot;&quot;;&quot;&quot;;IF(ISERROR(ROUND(SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.'];[.G$3:.AZ$3])/(SUM([.G$3:.AZ$3])-SUMIF([.G'.$num_lig.':.AZ'.$num_lig.'];&quot;abs&quot;;[.G$3:.AZ$3])-SUMIF([.G'.$num_lig.':.AZ'.$num_lig.'];&quot;disp&quot;;[.G$3:.AZ$3])-SUMIF([.G'.$num_lig.':.AZ'.$num_lig.'];&quot;-&quot;;[.G$3:.AZ$3])-SUMIF([.G'.$num_lig.':.AZ'.$num_lig.'];&quot;&quot;;[.G$3:.AZ$3]));1));&quot;-&quot;;ROUND(SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.'];[.G$3:.AZ$3])/(SUM([.G$3:.AZ$3])-SUMIF([.G'.$num_lig.':.AZ'.$num_lig.'];&quot;abs&quot;;[.G$3:.AZ$3])-SUMIF([.G'.$num_lig.':.AZ'.$num_lig.'];&quot;disp&quot;;[.G$3:.AZ$3])-SUMIF([.G'.$num_lig.':.AZ'.$num_lig.'];&quot;-&quot;;[.G$3:.AZ$3])-SUMIF([.G'.$num_lig.':.AZ'.$num_lig.'];&quot;&quot;;[.G$3:.AZ$3]));1)))" office:value-type="float" office:value="'.$valeur_defaut.'"><text:p>'.$valeur_defaut.'</text:p></table:table-cell>');
		$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:formula="oooc:=IF([.B'.$num_lig.']=&quot;&quot;;&quot;&quot;;IF(ISERROR(ROUND(20*SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.'];[.G$3:.AZ$3])/(SUMPRODUCT([.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.']=&quot;abs&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.']=&quot;disp&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.']=&quot;-&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.']=&quot;&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.']=&quot;v&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4]));1));&quot;-&quot;;ROUND(20*SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.'];[.G$3:.AZ$3])/(SUMPRODUCT([.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.']=&quot;abs&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.']=&quot;disp&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.']=&quot;-&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.']=&quot;&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$num_lig.':.AZ'.$num_lig.']=&quot;v&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4]));1)))" office:value-type="float" office:value="'.$valeur_defaut.'"><text:p>'.$valeur_defaut.'</text:p></table:table-cell>');

		$num_col=6;
		while ($k < $nb_dev) {
			$note_query=mysql_query("SELECT * FROM cn_notes_devoirs WHERE (login='$eleve_login[$i]' AND id_devoir='$id_dev[$k]')");

			if(mysql_num_rows($note_query)==0) {
				$eleve_note='';
				$eleve_statut='-';
			}
			else{
				$eleve_statut = @mysql_result($note_query, 0, "statut");
				$eleve_note = @mysql_result($note_query, 0, "note");
			}

			//$eleve_login_note=$eleve_login[$i]."_note";

			if($eleve_statut=='v') {
				// Pas de note saisie -> statut = v pour vide
				$eleve_note="-";

				$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell office:value-type="string"><text:p>'.$eleve_note.'</text:p></table:table-cell>');
			}
			elseif($eleve_statut!='') {
				$eleve_note=$eleve_statut;

				$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell office:value-type="string"><text:p>'.$eleve_note.'</text:p></table:table-cell>');
			}
			else{
				// Probl�me avec les 17.5 qui sont convertis en dates -> 17/05/07
				$eleve_note_virg=strtr($eleve_note,".",",");

				$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell office:value-type="float" office:value="'.$eleve_note.'"><text:p>'.$eleve_note_virg.'</text:p></table:table-cell>');
			}

			//$ligne_eleve.=";".$eleve_note;
			$k++;
			$num_col++;
		}

		$nb_vide=46-$num_col+6;
		$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:number-columns-repeated="'.$nb_vide.'"/>');

		$ecriture=fwrite($fichier_tmp_xml,'</table:table-row>');

		$i++;
		$num_lig++;
	}

	// ===================

	for($i=$num_lig;$i<=100;$i++) {
		//'.$tabcol[$num_col].'

		$ecriture=fwrite($fichier_tmp_xml,'<table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell/><table:table-cell table:number-columns-repeated="3"/>');

		$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:formula="oooc:=IF([.B'.$i.']=&quot;&quot;;&quot;&quot;;IF(ISERROR(ROUND(20*SUMPRODUCT([.G'.$i.':.AZ'.$i.'];[.G$3:.AZ$3])/(SUMPRODUCT([.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$i.':.AZ'.$i.']=&quot;abs&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$i.':.AZ'.$i.']=&quot;disp&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$i.':.AZ'.$i.']=&quot;-&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$i.':.AZ'.$i.']=&quot;&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$i.':.AZ'.$i.']=&quot;v&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4]));1));&quot;-&quot;;ROUND(20*SUMPRODUCT([.G'.$i.':.AZ'.$i.'];[.G$3:.AZ$3])/(SUMPRODUCT([.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$i.':.AZ'.$i.']=&quot;abs&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$i.':.AZ'.$i.']=&quot;disp&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$i.':.AZ'.$i.']=&quot;-&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$i.':.AZ'.$i.']=&quot;&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4])-SUMPRODUCT([.G'.$i.':.AZ'.$i.']=&quot;v&quot;;[.G$3:.AZ$3];[.G$4:.AZ$4]));1)))"><text:p/></table:table-cell>');

		$nb_max_dev=46;
		$ecriture=fwrite($fichier_tmp_xml,'<table:table-cell table:number-columns-repeated="'.$nb_max_dev.'"/>');

		$ecriture=fwrite($fichier_tmp_xml,'</table:table-row>');
	}

	// ===================

	//$ecriture=fwrite($fichier_tmp_xml,'');

	$ecriture=fwrite($fichier_tmp_xml,'</table:table>');

	// ===================

	//$ecriture=fwrite($fichier_tmp_xml,'<table:table table:name="Infos" table:style-name="ta1" table:print="false"><table:table-column table:style-name="co2" table:number-columns-repeated="6" table:default-cell-style-name="Default"/><table:table-row table:style-name="ro3"><table:table-cell table:number-columns-repeated="6"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>Feuille de calcul destinée à:</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. une saisie hors ligne des notes</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. une conservation des résultats dans un tableur,...</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell table:number-columns-repeated="6"/></table:table-row><table:table-row table:style-name="ro4"><table:table-cell/><table:table-cell office:value-type="string"><text:p>L&apos;import des notes dans GEPI se fait ainsi:</text:p></table:table-cell><table:table-cell table:number-columns-repeated="3"/><table:table-cell office:value-type="string"><text:p><text:s/></text:p></table:table-cell></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. dans le feuillet &apos;Notes&apos;, cliquer sur le menu &apos;Fichier/Enregistrer sous&apos;</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro4"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. choisir &apos;Fichier CSV&apos; dans &apos;Type de fichiers&apos; et cocher &apos;Editer les paramètres du filtre&apos;</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. cliquer sur &apos;Enregistrer&apos;</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. valider &apos;Oui&apos; l&apos;avertissement comme quoi seul le feuillet actif va être enregistré</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. dans les paramètres CSV, choisir le point-virgule comme séparateur de champs et supprimer le séparateur de texte</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell table:number-columns-repeated="6"/></table:table-row><table:table-row table:style-name="ro4"><table:table-cell/><table:table-cell office:value-type="string"><text:p>Quelques conseils et remarques pour permettre un import des notes dans GEPI:</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. Les champs importés sont repérés par le contenu de la colonne A et de la ligne 1 avec un préfixe GEPI_</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. Les colonnes moyennes ne sont pas prises en compte dans GEPI; elles sont recalculées d&apos;après les notes importées.</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. Ne pas fusionner de cellules sans quoi le format CSV sera perturbé.</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. Les formules permettent des calculs jusqu&apos;à la ligne 100 et jusqu&apos;à la colonne AZ.</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row></table:table></office:spreadsheet></office:body></office:document-content>');


	$ecriture=fwrite($fichier_tmp_xml,'<table:table table:name="Infos" table:style-name="ta1" table:print="false"><table:table-column table:style-name="co2" table:number-columns-repeated="6" table:default-cell-style-name="Default"/><table:table-row table:style-name="ro3"><table:table-cell table:number-columns-repeated="6"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell table:style-name="ce14" office:value-type="string"><text:p>Feuille de calcul destinée à:</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. une saisie hors ligne des notes</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. une conservation des résultats dans un tableur,...</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell table:number-columns-repeated="6"/></table:table-row><table:table-row table:style-name="ro4"><table:table-cell/><table:table-cell table:style-name="ce14" office:value-type="string"><text:p>L&apos;import des notes dans GEPI se fait ainsi:</text:p></table:table-cell><table:table-cell table:number-columns-repeated="3"/><table:table-cell office:value-type="string"><text:p><text:s/></text:p></table:table-cell></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. dans le feuillet &apos;Notes&apos;, cliquer sur le menu &apos;Fichier/Enregistrer sous&apos;</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. choisir &apos;Fichier CSV&apos; dans &apos;Type de fichiers&apos; et cocher &apos;Editer les paramètres du filtre&apos;</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. cliquer sur &apos;Enregistrer&apos;</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. valider &apos;Oui&apos; l&apos;avertissement comme quoi seul le feuillet actif va être enregistré</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. dans les paramètres CSV, choisir le point-virgule comme séparateur de champs et supprimer le séparateur de texte</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell table:number-columns-repeated="6"/></table:table-row><table:table-row table:style-name="ro4"><table:table-cell/><table:table-cell table:style-name="ce14" office:value-type="string"><text:p>Quelques conseils et remarques pour permettre un import des notes dans GEPI:</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. Les champs importés sont repérés par le contenu de la colonne A (masquée par défaut) et de la ligne 1 avec un préfixe GEPI_</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. Les colonnes moyennes ne sont pas prises en compte dans GEPI; elles sont recalculées d&apos;après les notes importées.</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. Ne pas fusionner de cellules sans quoi le format CSV sera perturbé.</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. Les formules permettent des calculs jusqu&apos;à la ligne 100 et jusqu&apos;à la colonne AZ.</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>. Ne pas insérer de ligne pour ajouter un élève (à moins de remplir correctement aussi la colonne A (masquée par défaut))</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell table:number-columns-repeated="6"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell table:style-name="ce14" office:value-type="string"><text:p>Astuce:</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>Vous pouvez saisir plusieurs trimestres en insérant un nouveau feuillet (copie du premier) pour saisir tous les trimestres dans un même fichier tableur. </text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro3"><table:table-cell/><table:table-cell office:value-type="string"><text:p>Il est indispensable que les lignes et colonnes masquées soient copiées avec le reste de la page.</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row><table:table-row table:style-name="ro4"><table:table-cell/><table:table-cell office:value-type="string"><text:p>Lors de l&apos;export, la macro Export_CSV prend en compte le feuillet en courant.</text:p></table:table-cell><table:table-cell table:number-columns-repeated="4"/></table:table-row></table:table></office:spreadsheet></office:body></office:document-content>');

	$fermeture=fclose($fichier_tmp_xml);




	//set_time_limit(3000);
	require_once("../lib/ss_zip.class.php");

	$zip= new ss_zip('',6);
	//$zip->add_file('sxc/content.xml');
	$zip->add_file("$tmp_fich",'content.xml');

	// On n'ajoute pas les dossiers, ni les fichiers vides... ss_zip ne le supporte pas...
	// ... et OpenOffice a l'air de supporter l'absence de ces dossiers/fichiers.

	$zip->add_file($chemin_modele_ods.'/Basic/script-lc.xml', 'Basic/script-lc.xml');
	$zip->add_file($chemin_modele_ods.'/Basic/Standard/script-lb.xml', 'Basic/Standard/script-lb.xml');
	$zip->add_file($chemin_modele_ods.'/Basic/Standard/Module1.xml', 'Basic/Standard/Module1.xml');

	// On ne met pas ce fichier parce que sa longueur vide fait une blague pour ss_zip.
	//$zip->add_file($chemin_modele_ods.'/Configurations2/accelerator/current.xml', 'Configurations2/accelerator/current.xml');

	//$zip->add_file($chemin_modele_ods.'/Configurations2', 'Configurations2');
	//$zip->add_file($chemin_modele_ods.'/Configurations2/floater', 'Configurations2/floater');
	//$zip->add_file($chemin_modele_ods.'/Configurations2/images', 'Configurations2/images');
	//$zip->add_file($chemin_modele_ods.'/Configurations2/images/Bitmaps', 'Configurations2/images/Bitmaps');
	//$zip->add_file($chemin_modele_ods.'/Configurations2/accelerator', 'Configurations2/accelerator');
	//$zip->add_file($chemin_modele_ods.'/Configurations2/accelerator/current.xml', 'Configurations2/accelerator/current.xml');
	//$zip->add_file($chemin_modele_ods.'/Configurations2/statusbar', 'Configurations2/statusbar');
	//$zip->add_file($chemin_modele_ods.'/Configurations2/progressbar', 'Configurations2/progressbar');
	//$zip->add_file($chemin_modele_ods.'/Configurations2/toolbar', 'Configurations2/toolbar');
	//$zip->add_file($chemin_modele_ods.'/Configurations2/popupmenu', 'Configurations2/popupmenu');
	//$zip->add_file($chemin_modele_ods.'/Configurations2/menubar', 'Configurations2/menubar');
	//$zip->add_file($chemin_modele_ods.'/META-INF', 'META-INF');
	$zip->add_file($chemin_modele_ods.'/META-INF/manifest.xml', 'META-INF/manifest.xml');
	$zip->add_file($chemin_modele_ods.'/settings.xml', 'settings.xml');
	$zip->add_file($chemin_modele_ods.'/meta.xml', 'meta.xml');
	//$zip->add_file($chemin_modele_ods.'/Thumbnails', 'Thumbnails');
	$zip->add_file($chemin_modele_ods.'/Thumbnails/thumbnail.png', 'Thumbnails/thumbnail.png');
	$zip->add_file($chemin_modele_ods.'/mimetype', 'mimetype');
	$zip->add_file($chemin_modele_ods.'/styles.xml', 'styles.xml');

	$zip->save("$tmp_fich.zip");


	if(file_exists("$chemin_temp/$nom_fic")) {unlink("$chemin_temp/$nom_fic");}
	//rename("$tmp_fich.zip","$chemin_ods/$chaine_tmp.$nom_fic");
	rename("$tmp_fich.zip","$chemin_temp/$nom_fic");

	// Suppression du fichier content...xml
	unlink($tmp_fich);

	//**************** EN-TETE *****************
	$titre_page = "Export des notes";
	require_once("../lib/header.inc");
	//**************** FIN EN-TETE *****************

	//$titre=htmlentities($current_group['description'])." (".$nom_periode.")";
	$titre=htmlentities($current_group['name'])." ".$current_group["classlist_string"]." (".$nom_periode.")";
	$titre.=" - EXPORT";

	// Mettre la ligne de liens de retour,...
    echo "<div class='norme'><p class='bold'>\n";
    echo "<a href=\"../accueil.php\"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour accueil </a>|\n";
    echo "<a href='index.php?id_groupe=".$current_group["id"]."&amp;periode_num=$periode_num'> ".htmlentities($current_group['name'])." ".$current_group["classlist_string"]." (".$nom_periode.")"." </a>|\n";
	echo "</div>\n";


	echo "<h2>$titre</h2>\n";

	echo "<p>T�l�charger: <a href='$chemin_temp/$nom_fic'>$nom_fic</a></p>\n";

	/*
	echo "filetype($chemin_ods/$nom_fic)=".filetype("$chemin_ods/$nom_fic")."<br />\n";

	$fp=fopen("$chemin_ods/$nom_fic","r");
	$ligne=fgets($fp, 4096);
	fclose($fp);

	echo "<pre>$ligne</pre>";
	*/

	// AJOUTER UN LIEN POUR FAIRE LE MENAGE... et permettre � l'admin de faire le m�nage.
	echo "<p>Pour ne pas encombrer inutilement le serveur et par soucis de confidentialit�, il est recommand� de supprimer le fichier du serveur apr�s r�cup�ration du fichier ci-dessus.<br />\n";
	//echo "<a href='".$_SERVER['PHP_SELF']."?nettoyage=$nom_fic'>Supprimer le fichier</a>.";
	echo "<a href='".$_SERVER['PHP_SELF']."?nettoyage=$nom_fic&amp;id_groupe=$id_groupe&amp;periode_num=$periode_num".add_token_in_url()."'>Supprimer le fichier</a>.";
	//echo "<a href='".$_SERVER['PHP_SELF']."?nettoyage=".$nom_fic."_truc'>Supprimer le fichier 2</a>.";
	//echo "<a href='".$_SERVER['PHP_SELF']."?nettoyage=_truc".$nom_fic."'>Supprimer le fichier 3</a>.";
	echo "</p>\n";


	require("../lib/footer.inc.php");
	die();
}
else{
	echo "<p>Tiens, c'est bizarre! Ce cas ne devrait pas arriver.</p>\n";
}
require("../lib/footer.inc.php");
?>
