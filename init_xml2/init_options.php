<?php
@set_time_limit(0);
/*
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
extract($_POST, EXTR_OVERWRITE);

// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
	header("Location: ../utilisateurs/mon_compte.php?change_mdp=yes");
	die();
} else if ($resultat_session == '0') {
	header("Location: ../logout.php?auto=1");
	die();
}

//===========================================
if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}

check_token();

/*
if($_SESSION['statut']!='administrateur') {
	header("Location: ../logout.php?auto=1");
	die();
}
*/
//===========================================

//**************** EN-TETE *****************
$titre_page = "Outil d'initialisation de l'année | Initialisation  des options par GEP";
require_once("../lib/header.inc.php");
//**************** FIN EN-TETE *****************

include("init_xml_lib.php");
?>
<p class="bold"><a href="index.php"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour accueil initialisation</a></p>
<?php

echo "<center><h3 class='gepi'>Cinquième phase d'initialisation<br />Affectation des matières à chaque professeur,<br />Affectation des professeurs dans chaque classe,<br />Importation des options suivies par les élèves</h3></center>";

echo "<h3 class='gepi'>Deuxième étape : importation des options suivies par les élèves.</h3>";

$test1 = mysql_result(mysql_query("SELECT count(*) FROM eleves"),0);
if ($test1 ==0) {
	echo "<p class='grand'>Aucun élève actuellement dans la base : la procédure d'initialisation des options ne peut continuer !</p>";
	require("../lib/footer.inc.php");
	die();
} else {
	$test2 = mysql_result(mysql_query("SELECT count(*) FROM j_eleves_classes"),0);
	if ($test2 ==0) {
		echo "<p class='grand'>Les classes n'ont pas encore été constituées : la procédure d'initialisation des options ne peut continuer !</p>";
		require("../lib/footer.inc.php");
		die();
	} else {

		//$test3 = mysql_result(mysql_query("SELECT count(*) FROM temp_gep_import WHERE LOGIN !=''"),0);
		$test3 = mysql_result(mysql_query("SELECT count(*) FROM temp_gep_import2 WHERE LOGIN !=''"),0);
		if ($test3 ==0) {
			//echo "<p class='grand'>Afin de procéder à la phase de définition des options suivies par les élèves, vous devez d'abord effectuer la première phase d'importation des élèves à partir du fichier ELEVES.CSV</p>";
			echo "<p class='grand'>Afin de procéder à la phase de définition des options suivies par les élèves, vous devez d'abord effectuer la première phase d'importation des élèves à partir du fichier ElevesSansAdresses.xml</p>";
			require("../lib/footer.inc.php");
			die();
		}
	}
}

if(!isset($_GET['confirmer'])) {
	$sql="SELECT 1=1 FROM matieres_notes;";
	$test1=mysql_query($sql);

	$sql="SELECT 1=1 FROM matieres_appreciations;";
	$test2=mysql_query($sql);

	$sql="SELECT 1=1 FROM cn_cahier_notes ccn, cn_conteneurs cc, cn_devoirs cd, cn_notes_devoirs cnd WHERE ccn.id_cahier_notes=cc.id_racine AND cc.id=cd.id_conteneur AND cd.id=cnd.id_devoir;";
	$test3=mysql_query($sql);

	if((mysql_num_rows($test1)!=0)||(mysql_num_rows($test2)!=0)||(mysql_num_rows($test3)!=0)) {
		echo "<p><span style='font-weight:bold; color:red'>ATTENTION&nbsp;:</span> Il existe des notes de devoirs, des notes sur les bulletins ou des appréciations sur les bulletins.</p>\n";

		echo "<p>En poursuivant ici, vous risquez de suprimer ces informations pour certains élèves (<em>plus pécisemment, vous risquez de désinscrire des élèves de groupes dans lesquels ils ont des notes ou appréciations</em>).</p>\n";

		echo "<p>La présente page n'est normalement consultée qu'en début d'année pour l'initialisation de l'année.</p>\n";

		echo "<p>Etes-vous sûr de vouloir poursuivre&nbsp;?</p>\n";

		echo "<p><a href='".$_SERVER['PHP_SELF']."?confirmer=y".add_token_in_url()."'>Confirmer malgré tout la prise en compte des options de la table 'temp_gep_import2'</a></p>\n";

		require("../lib/footer.inc.php");
		die();
	}
}

$sql="SELECT id, classe FROM classes;";
//echo "$sql<br />\n";
$appel_donnees_classes = mysql_query($sql);
$nb_classe = mysql_num_rows($appel_donnees_classes);
$z=0;

while ($classe_row = mysql_fetch_object($appel_donnees_classes)) {
	$id_classe = $classe_row->id;
	$classe = $classe_row->classe;

	// Initialisation de la variable pour indiquer qu'un groupe n'existe pas pour la matière indiquée en option
	$no_group = array();

	$sql="SELECT count(*) FROM periodes WHERE id_classe = '" . $id_classe . "';";
	//echo "$sql<br />\n";
	$nb_per = mysql_result(mysql_query($sql), 0);

	$nb_options = 0;
	$i = 1;
	$tempo = null;
	while ($i < 13) {
		$tempo .= "ELEOPT".$i.", ";
		$i++;
	}
	$tempo = mb_substr($tempo, 0, -2);

	//$call_data = mysql_query("SELECT $tempo FROM temp_gep_import WHERE DIVCOD = '$classe'");
	$sql="SELECT $tempo FROM temp_gep_import2 WHERE DIVCOD = '$classe'";
	//echo "$sql<br />\n";
	$call_data = mysql_query($sql);
	$tab_options = array();
	while ($row = mysql_fetch_object($call_data)) {
		$i = 1;
		while ($i < 13) {
			$tempo = "ELEOPT".$i;
			$temp = $row->$tempo;
			if ($temp!='') {
				// On s'assure de ne pas ranger dans le tableau tab_options, plusieurs fois la même option
				$n = 0;
				$double = 'no';

				if (in_array($temp, $tab_options)) {$double = 'yes';}

				if ($double == 'no') {
					$tab_options[$nb_options] = $temp;
					$nb_options++;
				}
			}
			$i++;
		}
	}

	if($debug_import=="y") {
		echo "<pre>Tableau des options en classe de $classe : \$tab_options=$tab_options<br />\n";
		echo print_r($tab_options)."<br />\n";
		echo "</pre>\n";
	}

	$sql="SELECT e.login FROM eleves e, j_eleves_classes j WHERE (e.login = j.login and j.id_classe = '$id_classe');";
	//echo "$sql<br />\n";
	$appel_donnees_eleves = mysql_query($sql);

	while($i = mysql_fetch_object($appel_donnees_eleves)) {
		$current_eleve_login = $i->login;
		//$call_data = mysql_query("SELECT ELEOPT1,ELEOPT2,ELEOPT3,ELEOPT4,ELEOPT5,ELEOPT6,ELEOPT7,ELEOPT8,ELEOPT9,ELEOPT10,ELEOPT11,ELEOPT12 FROM temp_gep_import WHERE LOGIN = '$current_eleve_login'");
		$sql="SELECT ELEOPT1,ELEOPT2,ELEOPT3,ELEOPT4,ELEOPT5,ELEOPT6,ELEOPT7,ELEOPT8,ELEOPT9,ELEOPT10,ELEOPT11,ELEOPT12 FROM temp_gep_import2 WHERE LOGIN = '$current_eleve_login';";
		if($debug_import=="y") {echo "<p>$sql<br />\n";}
		$call_data = mysql_query($sql);
		while ($row = mysql_fetch_array($call_data, MYSQL_NUM)) {
			$j="0";
			while ($j < $nb_options) {

				$suit_option = 'no';
				if (in_array($tab_options[$j], $row)) {
					$suit_option = 'yes';
					if($debug_import=="y") {
						echo "<span style='color:green'>$current_eleve_login suit l'option \$tab_options[$j]=$tab_options[$j]</span><br />";
					}
				}
				else {
					if($debug_import=="y") {
						echo "<span style='color:red'>$current_eleve_login ne suit pas l'option \$tab_options[$j]=$tab_options[$j]</span><br />";
					}
				}

				if ($suit_option == 'no') {

					// On commence par récupérer l'ID du groupe concerné

					if (!in_array($tab_options[$j], $no_group)) {
						/*
						$group_id = @mysql_result(mysql_query("SELECT g.id FROM groupes g, j_groupes_classes jgc, j_groupes_matieres jgm where (" .
								"g.id = jgm.id_groupe and " .
								"jgm.id_matiere = '" . $tab_options[$j] . "' and " .
								"jgm.id_groupe = jgc.id_groupe and " .
								"jgc.id_classe = '" . $id_classe . "')"), 0);
						if (is_numeric($group_id)) {
							$reg = mysql_query("DELETE FROM j_eleves_groupes WHERE (id_groupe = '" . $group_id . "' and login='". $current_eleve_login . "')");
							// DEBUG :: echo "<br/>DELETED FROM GROUPE : " . $group_id;
						} else {
							$no_group[] = $tab_options[$j];
						}
						*/
						$sql="SELECT g.id FROM groupes g, j_groupes_classes jgc, j_groupes_matieres jgm where (" .
								"g.id = jgm.id_groupe and " .
								"jgm.id_matiere = '" . $tab_options[$j] . "' and " .
								"jgm.id_groupe = jgc.id_groupe and " .
								"jgc.id_classe = '" . $id_classe . "')";
						if($debug_import=="y") {echo "$sql<br />\n";}
						$res_grp=mysql_query($sql);
						if(mysql_num_rows($res_grp)>0) {
							while($lig_grp=mysql_fetch_object($res_grp)) {
								$sql="DELETE FROM j_eleves_groupes WHERE (id_groupe = '" . $lig_grp->id . "' and login='". $current_eleve_login . "');";
								if($debug_import=="y") {echo "$sql<br />\n";}
								$reg = mysql_query($sql);
							}
						}
						else {
							$no_group[] = $tab_options[$j];
						}
					}

				}
				$j++;
			}
		}
	}
}

//echo "<p>L'importation des options suivies par les élèves dans la base GEPI a été effectuée avec succès !<br />Vous pouvez procéder à l'étape suivante de nettoyage des tables GEPI.</p>\n";
//echo "<center><p><a href='clean_tables.php'>Suppression des données inutiles</a></p></center>\n";
echo "<p>L'importation des options suivies par les élèves dans la base GEPI a été effectuée avec succès !<br />Vous pouvez procéder à l'étape suivante d'import des professeurs principaux.</p>\n";
echo "<center><p><a href='init_pp.php'>Import des professeurs principaux</a></p></center>\n";
require("../lib/footer.inc.php");
?>
