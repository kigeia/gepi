<?php

/**
 * ajax_appreciations.php
 * Fichier qui permet la sauvegarde automatique des appr�ciations au fur et � mesure de leur saisie
 *
 * @version $Id$
 * @copyright 2007-2011
 */

// ============== Initialisation ===================
$affiche_connexion = 'yes';
$niveau_arbo = 1;

// Initialisations files
require_once("../lib/initialisations.inc.php");

// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
   header("Location:utilisateurs/mon_compte.php?change_mdp=yes&retour=accueil#changemdp");
   die();
} else if ($resultat_session == '0') {
    header("Location: ../logout.php?auto=1");
    die();
}

//echo "A";

function log_ajax_app($texte) {
	$debug="n";
	$fichier="/tmp/log_ajax_app.txt";
	if($debug=="y") {
		$ladate=strftime("%b %d %H:%M:%S");
		$f=fopen($fichier,"a+");
		fwrite($f,$ladate." : ".$texte."\n");
		fclose($f);
	}
}

// S�curit�
if (!checkAccess()) {
	log_ajax_app("Echec checkAccess().");
    header("Location: ../logout.php?auto=2");
    die();
}

//echo "B";

if(isset($_SESSION['login'])) {
	log_ajax_app($_SESSION['login']." (".$_SESSION['statut'].").");
}

// Le check_token doit �tre � false parce qu'il va se produire sans charger une nouvelle page, avec un header HTML d�j� transmis
check_token(false);

log_ajax_app("Test check_token() depasse.");

//echo "C";

header('Content-Type: text/html; charset=ISO-8859-1');

// Initialisation des variables
$var1 = isset($_POST["var1"]) ? $_POST["var1"] : (isset($_GET["var1"]) ? $_GET["var1"] : NULL);
$var2 = isset($_POST["var2"]) ? $_POST["var2"] : (isset($_GET["var2"]) ? $_GET["var2"] : NULL);
$appreciation = isset($_POST["var3"]) ? $_POST["var3"] : (isset($_GET["var3"]) ? $_GET["var3"] : NULL);
$professeur = isset($_SESSION["statut"]) ? $_SESSION["statut"] : NULL;

$mode=isset($_POST['mode']) ? $_POST['mode'] : "";

// ========== Fin de l'initialisation de la page =============

// On d�termine si les variables envoy�es sont bonnes ou pas
$verif_var1 = explode("_t", $var1);

// On v�rifie que le login de l'�l�ve soit valable et qu'il corresponde � l'enseignement envoy� par var2
$temoin_eleve=0;
if($_SESSION['statut']=='professeur') {
	$sql="SELECT login FROM j_eleves_groupes
			WHERE login = '".$verif_var1[0]."'
			AND id_groupe = '".$var2."'
			AND periode = '".$verif_var1[1]."'";
	log_ajax_app("$sql");
	$verif_eleve = mysql_query($sql)
			or die('Erreur de verif_var1 : '.mysql_error());
	log_ajax_app("Test passe.");
	$temoin_eleve=mysql_num_rows($verif_eleve);

	// On v�rifie que le prof logu� peut saisir ces appr�ciations
	//$verif_prof = mysql_query("SELECT login FROM j_groupes_professeurs WHERE id_groupe = '".$var2."'");
	if($mode!="verif") {
		$verif_prof = mysql_query("SELECT login FROM j_groupes_professeurs WHERE id_groupe = '".$var2."' AND login='".$_SESSION['login']."'");
		if (mysql_num_rows($verif_prof) >= 1) {
			// On ne fait rien
			$temoin_prof=mysql_num_rows($verif_prof);
		} else {
			log_ajax_app("Vous ne pouvez pas saisir d'appreciations pour cet eleve");
			die('Vous ne pouvez pas saisir d\'appr&eacute;ciations pour cet &eacute;l&egrave;ve');
		}
	}
	else {
		$temoin_prof=1;
	}
}

if (($_SESSION['statut']=='scolarite') || ($_SESSION['statut']=='cpe') || (($temoin_eleve !== 0 AND $temoin_prof !== 0))) {
	if($mode=='verif') {
		$sql="CREATE TABLE IF NOT EXISTS vocabulaire (id INT(11) NOT NULL auto_increment,
			terme VARCHAR(255) NOT NULL DEFAULT '',
			terme_corrige VARCHAR(255) NOT NULL DEFAULT '',
			PRIMARY KEY (id)) ENGINE=MyISAM;";
		//echo "$sql<br />";
		log_ajax_app($sql);
		$create_table=mysql_query($sql);
		if(!$create_table) {
			echo "<span style='color:red'>Erreur lors de la cr�ation de la table 'vocabulaire'.</span>";
		}
		else {
			$sql="SELECT * FROM vocabulaire;";
			//echo "$sql<br />";
			$res=mysql_query($sql);
			if(mysql_num_rows($res)>0) {
				while($lig_voc=mysql_fetch_object($res)) {
					$tab_voc[]=$lig_voc->terme;
					$tab_voc_corrige[]=$lig_voc->terme_corrige;
				}

				/*
				$tab_tmp=explode(" ",preg_replace("//"," ",$appreciation);
				for($loop=0;$loop<count($tab_tmp);$loop++) {
					
				}
				*/
				$appreciation_test=" ".preg_replace("/[',;\.]/"," ",casse_mot($appreciation,'min'))." ";
				$chaine_retour="";
				for($loop=0;$loop<count($tab_voc);$loop++) {
					if(preg_match("/ ".$tab_voc[$loop]." /i",$appreciation_test)) {
						if($chaine_retour=="") {$chaine_retour.="<span style='font-weight:bold'>Suspicion de faute de frappe&nbsp;: </span>";}
						$chaine_retour.=$tab_voc[$loop]." / ".$tab_voc_corrige[$loop]."<br />";
					}
				}
				if($chaine_retour!="") {
					echo $chaine_retour;
				}
			}
		}
	}
	elseif($_SESSION['statut']=='professeur') {
		$insertion_ou_maj_tempo="y";
		$sql="SELECT appreciation FROM matieres_appreciations WHERE login = '".$verif_var1[0]."' AND id_groupe = '".$var2."' AND periode = '".$verif_var1[1]."';";
		log_ajax_app($sql);
		$test_app_enregistree=mysql_query($sql);
		if(mysql_num_rows($test_app_enregistree)>0) {
			$lig_app_enregistree=mysql_fetch_object($test_app_enregistree);
			if($lig_app_enregistree->appreciation==utf8_decode($appreciation)) {
				// On supprime l'enregistrement tempo pour �viter de conserver un tempo qui est d�j� enregistr� dans la table principale.
				$sql="DELETE FROM matieres_appreciations_tempo WHERE login = '".$verif_var1[0]."' AND id_groupe = '".$var2."' AND periode = '".$verif_var1[1]."';";
				log_ajax_app($sql);
				$menage=mysql_query($sql);
				$insertion_ou_maj_tempo="n";
			}
		}
	
		if($insertion_ou_maj_tempo=="y") {
			// On v�rifie si cette appr�ciation existe d�j� ou non
			$verif_appreciation = mysql_query("SELECT appreciation FROM matieres_appreciations_tempo WHERE login = '".$verif_var1[0]."' AND id_groupe = '".$var2."' AND periode = '".$verif_var1[1]."'");
			// Si elle existe, on la met � jour
			if (mysql_num_rows($verif_appreciation) == 1) {
				$sql="UPDATE matieres_appreciations_tempo SET appreciation = '".utf8_decode($appreciation)."' WHERE login = '".$verif_var1[0]."' AND id_groupe = '".$var2."' AND periode = '".$verif_var1[1]."'";
				log_ajax_app($sql);
				$miseajour = mysql_query($sql);
			} else {
				//sinon on cr�e une nouvelle appr�ciation si l'appr�ciation n'est pas vide
				if ($appreciation != "") {
					$sql="INSERT INTO matieres_appreciations_tempo SET login = '".$verif_var1[0]."', id_groupe = '".$var2."', periode = '".$verif_var1[1]."', appreciation = '".utf8_decode($appreciation)."'";
					log_ajax_app($sql);
					$sauvegarde = mysql_query($sql);
				}
			}
		}
		// et on renvoie une r�ponse valide
		header("HTTP/1.0 200 OK");
		echo ' ';
	}
}
else {
	// et on renvoie une r�ponse valide
	header("HTTP/1.0 200 OK");
	echo ' ';
}
?>