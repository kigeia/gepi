<?php
@set_time_limit(0);
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

if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}

//================================================
// Fonction de g�n�ration de mot de passe r�cup�r�e sur TotallyPHP
// Aucune mention de licence pour ce script...

/*
 * The letter l (lowercase L) and the number 1
 * have been removed, as they can be mistaken
 * for each other.
*/

function createRandomPassword() {
    $chars = "abcdefghijkmnopqrstuvwxyz023456789";
    srand((double)microtime()*1000000);
    $i = 0;
    $pass = '' ;

    //while ($i <= 7) {
    while ($i <= 5) {
        $num = rand() % 33;
        $tmp = substr($chars, $num, 1);
        $pass = $pass . $tmp;
        $i++;
    }

    return $pass;
}
//================================================

function affiche_debug($texte){
	// Passer � 1 la variable pour g�n�rer l'affichage des infos de debug...
	$debug=0;
	if($debug==1){
		echo "<font color='green'>".$texte."</font>";
		flush();
	}
}


$liste_tables_del = array(
//"absences",
//"aid",
//"aid_appreciations",
//"aid_config",
//"avis_conseil_classe",
//"classes",
//"droits",
//"eleves",
//"responsables",
//"etablissements",
//"j_aid_eleves",
"j_aid_utilisateurs",
"j_aid_utilisateurs_gest",
"j_groupes_professeurs",
//"j_eleves_classes",
//"j_eleves_etablissements",
"j_eleves_professeurs",
//"j_eleves_regime",
//"j_professeurs_matieres",
//"log",
//"matieres",
"matieres_appreciations",
"matieres_notes",
"matieres_appreciations_grp",
"matieres_appreciations_tempo",
//"observatoire",
//"observatoire_comment",
//"observatoire_config",
//"observatoire_niveaux",
"observatoire_j_resp_champ",
//"observatoire_suivi",
//"periodes",
//"periodes_observatoire",
"tempo2",
//"temp_gep_import",
"tempo",
//"utilisateurs",
"cn_cahier_notes",
"cn_conteneurs",
"cn_devoirs",
"cn_notes_conteneurs",
"cn_notes_devoirs",
//"setting"
);

//**************** EN-TETE *****************
$titre_page = "Outil d'initialisation de l'ann�e : Importation des professeurs";
require_once("../lib/header.inc");
//**************** FIN EN-TETE *****************

require_once("init_xml_lib.php");

// On v�rifie si l'extension d_base est active
//verif_active_dbase();

?>
<p class="bold"><a href="index.php"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour accueil initialisation</a></p>
<?php
echo "<center><h3 class='gepi'>Quatri�me phase d'initialisation<br />Importation des professeurs</h3></center>\n";

if (!isset($step1)) {
	$j=0;
	$flag=0;
	$chaine_tables="";
	while (($j < count($liste_tables_del)) and ($flag==0)) {
		$test = mysql_num_rows(mysql_query("SHOW TABLES LIKE '$liste_tables_del[$j]'"));
		if($test==1) {
			if (mysql_result(mysql_query("SELECT count(*) FROM $liste_tables_del[$j]"),0)!=0) {
				$flag=1;
			}
		}
		$j++;
	}

	for($loop=0;$loop<count($liste_tables_del);$loop++) {
		if($chaine_tables!="") {$chaine_tables.=", ";}
		$chaine_tables.="'".$liste_tables_del[$loop]."'";
	}

	$test = mysql_result(mysql_query("SELECT count(*) FROM utilisateurs WHERE statut='professeur'"),0);
	if ($test != 0) {$flag=1;}

	if ($flag != 0){
		echo "<p><b>ATTENTION ...</b><br />\n";
		echo "Des donn�es concernant les professeurs sont actuellement pr�sentes dans la base GEPI<br /></p>\n";
		echo "<p>Si vous poursuivez la proc�dure les donn�es telles que notes, appr�ciations, ... seront effac�es.</p>\n";

		echo "<p>Les tables vid�es seront&nbsp;: $chaine_tables</p>\n";

		echo "<ul><li>Seule la table contenant les utilisateurs (professeurs, admin, ...) et la table mettant en relation les mati�res et les professeurs seront conserv�es.</li>\n";
		echo "<li>Les professeurs de l'ann�e pass�e pr�sents dans la base GEPI et non pr�sents dans la base CSV de cette ann�e ne sont pas effac�s de la base GEPI mais simplement d�clar�s \"inactifs\".</li>\n";
		echo "</ul>\n";

		echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post'>\n";
		echo add_token_field();
		echo "<input type=hidden name='step1' value='y' />\n";
		echo "<input type='submit' name='confirm' value='Poursuivre la proc�dure' />\n";
		echo "</form>\n";
		echo "</div>\n";
		echo "</body>\n";
		echo "</html>\n";
		die();
	}
}

if (!isset($is_posted)) {
	if(isset($step1)) {
		$dirname=get_user_temp_directory();

		$sql="SELECT * FROM j_professeurs_matieres WHERE ordre_matieres='1';";
		$res_matiere_principale=mysql_query($sql);
		if(mysql_num_rows($res_matiere_principale)>0) {
			$fich_mp=fopen("../temp/".$dirname."/matiere_principale.csv","w+");
			if($fich_mp) {
				echo "<p>Cr�ation d'un fichier de sauvegarde de la mati�re principale de chaque professeur.</p>\n";
				while($lig_mp=mysql_fetch_object($res_matiere_principale)) {
					fwrite($fich_mp,"$lig_mp->id_professeur;$lig_mp->id_matiere\n");
				}
				fclose($fich_mp);
			}
			else {
				echo "<p style='color:red'>Echec de la cr�ation d'un fichier de sauvegarde de la mati�re principale de chaque professeur.</p>\n";
			}
		}

		check_token(false);
		$j=0;
		while ($j < count($liste_tables_del)) {
			$test = mysql_num_rows(mysql_query("SHOW TABLES LIKE '$liste_tables_del[$j]'"));
			if($test==1){
				if (mysql_result(mysql_query("SELECT count(*) FROM $liste_tables_del[$j]"),0)!=0) {
					$del = @mysql_query("DELETE FROM $liste_tables_del[$j]");
				}
			}
			$j++;
		}
	}
	$del = @mysql_query("DELETE FROM tempo2");

	echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post'>\n";
	echo add_token_field();
	//echo "<p>Importation du fichier <b>F_wind.csv</b> contenant les donn�es relatives aux professeurs.";

	echo "<p>Importation du fichier <b>sts.xml</b> contenant les donn�es relatives aux professeurs.\n";
	//echo "<p>Veuillez pr�ciser le nom complet du fichier <b>F_wind.csv</b>.";
	echo "<input type=hidden name='is_posted' value='yes' />\n";
	echo "<input type=hidden name='step1' value='y' />\n";
	//echo "<p><input type='file' size='80' name='dbf_file' />";
	echo "<br /><br /><p>Quelle formule appliquer pour la g�n�ration du login ?</p>\n";

	if(getSettingValue("use_ent")!='y') {
		$default_login_gen_type=getSettingValue('login_gen_type');
		if($default_login_gen_type=='') {$default_login_gen_type='name';}
	}
	else {
		$default_login_gen_type="";
	}

	echo "<input type='radio' name='login_gen_type' id='login_gen_type_name' value='name' ";
	if($default_login_gen_type=='name') {
		echo "checked ";
	}
	echo "/> <label for='login_gen_type_name'  style='cursor: pointer;'>nom</label>\n";
	echo "<br />\n";

	echo "<input type='radio' name='login_gen_type' id='login_gen_type_name8' value='name8' ";
	if($default_login_gen_type=='name8') {
		echo "checked ";
	}
	echo "/> <label for='login_gen_type_name8'  style='cursor: pointer;'>nom (tronqu� � 8 caract�res)</label>\n";
	echo "<br />";

	echo "<input type='radio' name='login_gen_type' id='login_gen_type_fname8' value='fname8' ";
	if($default_login_gen_type=='fname8') {
		echo "checked ";
	}
	echo "/> <label for='login_gen_type_fname8'  style='cursor: pointer;'>pnom (tronqu� � 8 caract�res)</label>\n";
	echo "<br />\n";

	echo "<input type='radio' name='login_gen_type' id='login_gen_type_fname19' value='fname19' ";
	if($default_login_gen_type=='fname19') {
		echo "checked ";
	}
	echo "/> <label for='login_gen_type_fname19'  style='cursor: pointer;'>pnom (tronqu� � 19 caract�res)</label>\n";
	echo "<br />\n";

	echo "<input type='radio' name='login_gen_type' id='login_gen_type_firstdotname' value='firstdotname' ";
	if($default_login_gen_type=='firstdotname') {
		echo "checked ";
	}
	echo "/> <label for='login_gen_type_firstdotname'  style='cursor: pointer;'>prenom.nom</label>\n";
	echo "<br />\n";

	echo "<input type='radio' name='login_gen_type' id='login_gen_type_firstdotname19' value='firstdotname19' ";
	if($default_login_gen_type=='firstdotname19') {
		echo "checked ";
	}
	echo "/> <label for='login_gen_type_firstdotname19'  style='cursor: pointer;'>prenom.nom (tronqu� � 19 caract�res)</label>\n";
	echo "<br />\n";

	echo "<input type='radio' name='login_gen_type' id='login_gen_type_namef8' value='namef8' ";
	if($default_login_gen_type=='namef8') {
		echo "checked ";
	}
	echo "/> <label for='login_gen_type_namef8'  style='cursor: pointer;'>nomp (tronqu� � 8 caract�res)</label>\n";
	echo "<br />\n";

	echo "<input type='radio' name='login_gen_type' id='login_gen_type_lcs' value='lcs' ";
	if($default_login_gen_type=='lcs') {
		echo "checked ";
	}
	echo "/> <label for='login_gen_type_lcs'  style='cursor: pointer;'>pnom (fa�on LCS)</label>\n";
	echo "<br />\n";

	if (getSettingValue("use_ent") == "y") {
		echo "<input type='radio' name='login_gen_type' id='login_gen_type_ent' value='ent' checked=\"checked\" />\n";
		echo "<label for='login_gen_type_ent'  style='cursor: pointer;'>
			Les logins sont produits par un ENT (<span title=\"Vous devez adapter le code du fichier ci-dessus vers la ligne 710.\">Attention !</span>)</label>\n";
		echo "<br />\n";
	}
	echo "<br />\n";

	// Modifications jjocal dans le cas o� c'est un serveur CAS qui s'occupe de tout
	if (getSettingValue("use_sso") == "cas") {
		$checked1 = ' checked="checked"';
		$checked0 = '';
	}else{
		$checked1 = '';
		$checked0 = ' checked="checked"';
	}

	echo "<p>Ces comptes seront-ils utilis�s en Single Sign-On avec CAS ou LemonLDAP ? (laissez 'non' si vous ne savez pas de quoi il s'agit)</p>\n";
	echo "<input type='radio' name='sso' id='sso_n' value='no'".$checked0." /> <label for='sso_n' style='cursor: pointer;'>Non</label>\n";
	echo "<br /><input type='radio' name='sso' id='sso_y' value='yes'".$checked1." /> <label for='sso_y' style='cursor: pointer;'>Oui (aucun mot de passe ne sera g�n�r�)</label>\n";
	echo "<br />\n";
	echo "<br />\n";


	echo "<p>Dans le cas o� la r�ponse � la question pr�c�dente est Non, voulez-vous:</p>\n";
	echo "<p><input type=\"radio\" name=\"mode_mdp\" id='mode_mdp_alea' value=\"alea\" checked /> <label for='mode_mdp_alea' style='cursor: pointer;'>G�n�rer un mot de passe al�atoire pour chaque professeur</label>.<br />\n";
	echo "<input type=\"radio\" name=\"mode_mdp\" id='mode_mdp_date' value=\"date\" /> <label for='mode_mdp_date' style='cursor: pointer;'>Utiliser plut�t la date de naissance au format 'aaaammjj' comme mot de passe initial (<i>il devra �tre modifi� au premier login</i>)</label>.</p>\n";
	echo "<br />\n";

	echo "<p><input type='submit' value='Valider' /></p>\n";
	echo "</form>\n";
	echo "<p><br /></p>\n";

}
else {
	check_token();

	if(isset($_POST['login_gen_type'])) {
		saveSetting('login_gen_type',$_POST['login_gen_type']);
	}

	$tempdir=get_user_temp_directory();
	if(!$tempdir){
		echo "<p style='color:red'>Il semble que le dossier temporaire de l'utilisateur ".$_SESSION['login']." ne soit pas d�fini!?</p>\n";
		// Il ne faut pas aller plus loin...
		// SITUATION A GERER
	}

	$dest_file="../temp/".$tempdir."/sts.xml";
	/*
	$fp=fopen($dest_file,"r");
	if(!$fp){
		echo "<p>Le XML STS Emploi du temps n'a pas l'air pr�sent dans le dossier temporaire.<br />Auriez-vous saut� une �tape???</p>\n";
		require("../lib/footer.inc.php");
		die();
	}
	*/

	$sts_xml=simplexml_load_file($dest_file);
	if(!$sts_xml) {
		echo "<p style='color:red;'>ECHEC du chargement du fichier avec simpleXML.</p>\n";
		require("../lib/footer.inc.php");
		die();
	}

	$nom_racine=$sts_xml->getName();
	if(strtoupper($nom_racine)!='STS_EDT') {
		echo "<p style='color:red;'>ERREUR: Le fichier XML fourni n'a pas l'air d'�tre un fichier XML STS_EMP_&lt;RNE&gt;_&lt;ANNEE&gt;.<br />Sa racine devrait �tre 'STS_EDT'.</p>\n";
		require("../lib/footer.inc.php");
		die();
	}

	echo "<p>";
	echo "Analyse du fichier pour extraire les informations de la section INDIVIDUS...<br />\n";

	$prof=array();
	$i=0;

	$tab_champs_personnels=array("NOM_USAGE",
	"NOM_PATRONYMIQUE",
	"PRENOM",
	"SEXE",
	"CIVILITE",
	"DATE_NAISSANCE",
	"GRADE",
	"FONCTION");

	$prof=array();
	$i=0;

	foreach($sts_xml->DONNEES->INDIVIDUS->children() as $individu) {
		$prof[$i]=array();

		//echo "<span style='color:orange'>\$individu->NOM_USAGE=".$individu->NOM_USAGE."</span><br />";

		foreach($individu->attributes() as $key => $value) {
			// <INDIVIDU ID="4189" TYPE="epp">
			$prof[$i][strtolower($key)]=trim(traite_utf8($value));
		}

		// Champs de l'individu
		foreach($individu->children() as $key => $value) {
			if(in_array(strtoupper($key),$tab_champs_personnels)) {
				if(strtoupper($key)=='SEXE') {
					$prof[$i]["sexe"]=trim(preg_replace("/[^1-2]/","",$value));
				}
				elseif(strtoupper($key)=='CIVILITE') {
					$prof[$i]["civilite"]=trim(preg_replace("/[^1-3]/","",$value));
				}
				elseif((strtoupper($key)=='NOM_USAGE')||
				(strtoupper($key)=='NOM_PATRONYMIQUE')||
				(strtoupper($key)=='NOM_USAGE')) {
					$prof[$i][strtolower($key)]=trim(preg_replace("/[^A-Za-z -]/","",traite_utf8($value)));
				}
				elseif(strtoupper($key)=='PRENOM') {
					$prof[$i][strtolower($key)]=trim(preg_replace("/[^A-Za-z�漽".$liste_caracteres_accentues." -]/","",traite_utf8($value)));
				}
				elseif(strtoupper($key)=='DATE_NAISSANCE') {
					$prof[$i][strtolower($key)]=trim(preg_replace("/[^0-9-]/","",traite_utf8($value)));
				}
				elseif((strtoupper($key)=='GRADE')||
					(strtoupper($key)=='FONCTION')) {
					$prof[$i][strtolower($key)]=trim(preg_replace('/"/','',traite_utf8($value)));
				}
				else {
					$prof[$i][strtolower($key)]=trim(traite_utf8($value));
				}
				//echo "\$prof[$i][".strtolower($key)."]=".$prof[$i][strtolower($key)]."<br />";
			}
		}

		if(isset($individu->PROFS_PRINC)) {
		//if($temoin_prof_princ>0) {
			$j=0;
			foreach($individu->PROFS_PRINC->children() as $prof_princ) {
				//$prof[$i]["prof_princ"]=array();
				foreach($prof_princ->children() as $key => $value) {
					$prof[$i]["prof_princ"][$j][strtolower($key)]=trim(traite_utf8(preg_replace('/"/',"",$value)));
					$temoin_au_moins_un_prof_princ="oui";
				}
				$j++;
			}
		}

		//if($temoin_discipline>0) {
		if(isset($individu->DISCIPLINES)) {
			$j=0;
			foreach($individu->DISCIPLINES->children() as $discipline) {
				foreach($discipline->attributes() as $key => $value) {
					if(strtoupper($key)=='CODE') {
						$prof[$i]["disciplines"][$j]["code"]=trim(traite_utf8(preg_replace('/"/',"",$value)));
						break;
					}
				}

				foreach($discipline->children() as $key => $value) {
					$prof[$i]["disciplines"][$j][strtolower($key)]=trim(traite_utf8(preg_replace('/"/',"",$value)));
				}
				$j++;
			}
		}

		if($debug_import=='y') {
			echo "<pre style='color:green;'><b>Tableau \$prof[$i]&nbsp;:</b>";
			print_r($prof[$i]);
			echo "</pre>";
		}

		$i++;
	}

	// Les $prof[$i]["disciplines"] ne sont pas utilis�es sauf � titre informatif � l'affichage...
	// Les $prof[$i]["prof_princ"][$j]["code_structure"] peuvent �tre exploit�es � ce niveau pour d�signer les profs principaux.

	//========================================================

	// On commence par rendre inactifs tous les professeurs
	$req = mysql_query("UPDATE utilisateurs set etat='inactif' where statut = 'professeur'");

	// on efface la ligne "display_users" dans la table "setting" de fa�on � afficher tous les utilisateurs dans la page  /utilisateurs/index.php
	$req = mysql_query("DELETE from setting where NAME = 'display_users'");


	echo "<p>Dans le tableau ci-dessous, les identifiants en rouge correspondent � des professeurs nouveaux dans la base GEPI. les identifiants en vert correspondent � des professeurs d�tect�s dans les fichiers CSV mais d�j� pr�sents dans la base GEPI.<br /><br />Il est possible que certains professeurs ci-dessous, bien que figurant dans le fichier CSV, ne soient plus en exercice dans votre �tablissement cette ann�e. C'est pourquoi il vous sera propos� en fin de proc�dure d'initialsation, un nettoyage de la base afin de supprimer ces donn�es inutiles.</p>\n";
	echo "<table border='1' class='boireaus' cellpadding='2' cellspacing='2' summary='Tableau des professeurs'>\n";
	echo "<tr><th><p class=\"small\">Identifiant du professeur</p></th><th><p class=\"small\">Nom</p></th><th><p class=\"small\">Pr�nom</p></th><th>Mot de passe *</th></tr>\n";

	srand();

	$nb_reg_no = 0;

	$tab_nouveaux_profs=array();

	$alt=1;
	for($k=0;$k<count($prof);$k++){
		if(isset($prof[$k]["fonction"])){
			if($prof[$k]["fonction"]=="ENS"){
				if($prof[$k]["sexe"]=="1"){
					$civilite="M.";
				}
				else{
					$civilite="Mme";
				}

				switch($prof[$k]["civilite"]){
					case 1:
						$civilite="M.";
						break;
					case 2:
						$civilite="Mme";
						break;
					case 3:
						$civilite="Mlle";
						break;
				}

				if($_POST['mode_mdp']=="alea"){
					$mdp=createRandomPassword();
				}
				else{
					$date=str_replace("-","",$prof[$k]["date_naissance"]);
					$mdp=$date;
				}

				//echo $prof[$k]["nom_usage"].";".$prof[$k]["prenom"].";".$civi.";"."P".$prof[$k]["id"].";"."ENS".";".$date."<br />\n";
				//$chaine=$prof[$k]["nom_usage"].";".$prof[$k]["prenom"].";".$civi.";"."P".$prof[$k]["id"].";"."ENS".";".$mdp;


				$prenoms = explode(" ",$prof[$k]["prenom"]);
				$premier_prenom = $prenoms[0];
				$prenom_compose = '';
				if (isset($prenoms[1])) $prenom_compose = $prenoms[0]."-".$prenoms[1];


				// On effectue d'abord un test sur le NUMIND
				$sql="select login from utilisateurs where (
				numind='P".$prof[$k]["id"]."' and
				numind!='' and
				statut='professeur')";
				//echo "<tr><td>$sql</td></tr>";
				$test_exist = mysql_query($sql);
				$result_test = mysql_num_rows($test_exist);
				if ($result_test == 0) {
					// On tente ensuite une reconnaissance sur nom/pr�nom, si le test NUMIND a �chou�
					$sql="select login from utilisateurs where (
					nom='".traitement_magic_quotes($prof[$k]["nom_usage"])."' and
					prenom = '".traitement_magic_quotes($premier_prenom)."' and
					statut='professeur')";

					// Pour debug:
					//echo "$sql<br />";
					$test_exist = mysql_query($sql);
					$result_test = mysql_num_rows($test_exist);
					if ($result_test == 0) {
						if ($prenom_compose != '') {
							$test_exist2 = mysql_query("select login from utilisateurs
							where (
							nom='".traitement_magic_quotes($prof[$k]["nom_usage"])."' and
							prenom = '".traitement_magic_quotes($prenom_compose)."' and
							statut='professeur'
							)");
							$result_test2 = mysql_num_rows($test_exist2);
							if ($result_test2 == 0) {
								$exist = 'no';
							} else {
								$exist = 'yes';
								$login_prof_gepi = mysql_result($test_exist2,0,'login');
							}
						} else {
							$exist = 'no';
						}
					} else {
						$exist = 'yes';
						$login_prof_gepi = mysql_result($test_exist,0,'login');
					}
				} else {
					$exist = 'yes';
					$login_prof_gepi = mysql_result($test_exist,0,'login');
				}

				if ($exist == 'no') {

					// Aucun professeur ne porte le m�me nom dans la base GEPI. On va donc rentrer ce professeur dans la base

					$prof[$k]["prenom"]=traitement_magic_quotes(corriger_caracteres($prof[$k]["prenom"]));

					if ($_POST['login_gen_type'] == "name") {
						$temp1 = $prof[$k]["nom_usage"];
						$temp1 = strtoupper($temp1);
						$temp1 = my_ereg_replace(" ","", $temp1);
						$temp1 = my_ereg_replace("-","_", $temp1);
						$temp1 = my_ereg_replace("'","", $temp1);
						$temp1 = strtoupper(remplace_accents($temp1,"all"));
						//$temp1 = substr($temp1,0,8);

					} elseif ($_POST['login_gen_type'] == "name8") {
						$temp1 = $prof[$k]["nom_usage"];
						$temp1 = strtoupper($temp1);
						$temp1 = my_ereg_replace(" ","", $temp1);
						$temp1 = my_ereg_replace("-","_", $temp1);
						$temp1 = my_ereg_replace("'","", $temp1);
						$temp1 = strtoupper(remplace_accents($temp1,"all"));
						$temp1 = substr($temp1,0,8);
					} elseif ($_POST['login_gen_type'] == "fname8") {
						$temp1 = $prof[$k]["prenom"]{0} . $prof[$k]["nom_usage"];
						$temp1 = strtoupper($temp1);
						$temp1 = my_ereg_replace(" ","", $temp1);
						$temp1 = my_ereg_replace("-","_", $temp1);
						$temp1 = my_ereg_replace("'","", $temp1);
						$temp1 = strtoupper(remplace_accents($temp1,"all"));
						$temp1 = substr($temp1,0,8);
					} elseif ($_POST['login_gen_type'] == "fname19") {
						$temp1 = $prof[$k]["prenom"]{0} . $prof[$k]["nom_usage"];
						$temp1 = strtoupper($temp1);
						$temp1 = my_ereg_replace(" ","", $temp1);
						$temp1 = my_ereg_replace("-","_", $temp1);
						$temp1 = my_ereg_replace("'","", $temp1);
						$temp1 = strtoupper(remplace_accents($temp1,"all"));
						$temp1 = substr($temp1,0,19);
					} elseif ($_POST['login_gen_type'] == "firstdotname") {
						if ($prenom_compose != '') {
							$firstname = $prenom_compose;
						} else {
							$firstname = $premier_prenom;
						}

						$temp1 = $firstname . "." . $prof[$k]["nom_usage"];
						$temp1 = strtoupper($temp1);

						$temp1 = my_ereg_replace(" ","", $temp1);
						$temp1 = my_ereg_replace("-","_", $temp1);
						$temp1 = my_ereg_replace("'","", $temp1);
						$temp1 = strtoupper(remplace_accents($temp1,"all"));
						//$temp1 = substr($temp1,0,19);
					} elseif ($_POST['login_gen_type'] == "firstdotname19") {
						if ($prenom_compose != '') {
							$firstname = $prenom_compose;
						} else {
							$firstname = $premier_prenom;
						}

						$temp1 = $firstname . "." . $prof[$k]["nom_usage"];
						$temp1 = strtoupper($temp1);
						$temp1 = my_ereg_replace(" ","", $temp1);
						$temp1 = my_ereg_replace("-","_", $temp1);
						$temp1 = my_ereg_replace("'","", $temp1);
						$temp1 = strtoupper(remplace_accents($temp1,"all"));
						$temp1 = substr($temp1,0,19);
					} elseif ($_POST['login_gen_type'] == "namef8") {
						$temp1 =  substr($prof[$k]["nom_usage"],0,7) . $prof[$k]["prenom"]{0};
						$temp1 = strtoupper($temp1);
						$temp1 = my_ereg_replace(" ","", $temp1);
						$temp1 = my_ereg_replace("-","_", $temp1);
						$temp1 = my_ereg_replace("'","", $temp1);
						$temp1 = strtoupper(remplace_accents($temp1,"all"));
						//$temp1 = substr($temp1,0,8);
					} elseif ($_POST['login_gen_type'] == "lcs") {
						$nom = $prof[$k]["nom_usage"];
						$nom = strtolower($nom);
						if (preg_match("/\s/",$nom)) {
							$noms = preg_split("/\s/",$nom);
							$nom1 = $noms[0];
							if (strlen($noms[0]) < 4) {
								$nom1 .= "_". $noms[1];
								$separator = " ";
							} else {
								$separator = "-";
							}
						} else {
							$nom1 = $nom;
							$sn = ucfirst($nom);
						}
						$firstletter_nom = $nom1{0};
						$firstletter_nom = strtoupper($firstletter_nom);
						$prenom = $prof[$k]["prenom"];
						$prenom1 = $prof[$k]["prenom"]{0};
						$temp1 = $prenom1 . $nom1;
						$temp1 = remplace_accents($temp1,"all");
					}elseif($_POST['login_gen_type'] == 'ent'){

						if (getSettingValue("use_ent") == "y") {
							// Charge � l'organisme utilisateur de pourvoir � cette fonctionnalit�
							// le code suivant n'est qu'une m�thode propos�e pour relier Gepi � un ENT
							$bx = 'oui';
							if (isset($bx) AND $bx == 'oui') {
								// On va chercher le login de l'utilisateur dans la table cr��e
								$sql_p = "SELECT login_u FROM ldap_bx
											WHERE nom_u = '".strtoupper($prof[$k]["nom_usage"])."'
											AND prenom_u = '".strtoupper($prof[$k]["prenom"])."'
											AND statut_u = 'teacher'";
								$query_p = mysql_query($sql_p);
								$nbre = mysql_num_rows($query_p);
								if ($nbre >= 1 AND $nbre < 2) {
									$temp1 = mysql_result($query_p, 0,"login_u");
								}else{
									// Il faudrait alors proposer une alternative � ce cas
									$temp1 = "erreur_".$k;
								}
							}
						}else{
							Die('Vous n\'avez pas autoris� Gepi � utiliser un ENT');
						}
					}

					$login_prof = $temp1;
					//$login_prof = remplace_accents($temp1,"all");
					// On teste l'unicit� du login que l'on vient de cr�er
					$m = 2;
					$test_unicite = 'no';
					$temp = $login_prof;
					while ($test_unicite != 'yes') {
						$test_unicite = test_unique_login($login_prof);

						if ($test_unicite != 'yes') {
							$login_prof = $temp.$m;
							$m++;
						}
					}
					$prof[$k]["nom_usage"] = traitement_magic_quotes(corriger_caracteres($prof[$k]["nom_usage"]));
					// Mot de passe et change_mdp

					$changemdp = 'y';

					//echo "<tr><td colspan='4'>strlen($affiche[5])=".strlen($affiche[5])."<br />\$affiche[4]=$affiche[4]<br />\$_POST['sso']=".$_POST['sso']."</td></tr>";
					if (strlen($mdp)>2 and $prof[$k]["fonction"]=="ENS" and $_POST['sso'] == "no") {
						//
						$pwd = md5(trim($mdp)); //NUMEN
						//$mess_mdp = "NUMEN";
						if($_POST['mode_mdp']=='alea'){
							$mess_mdp = "$mdp";
						}
						else{
							$mess_mdp = "Mot de passe d'apr�s la date de naissance";
						}
						//echo "<tr><td colspan='4'>NUMEN: $affiche[5] $pwd</td></tr>";
					} elseif ($_POST['sso']== "no") {
						$pwd = md5(rand (1,9).rand (1,9).rand (1,9).rand (1,9).rand (1,9).rand (1,9));
						$mess_mdp = $pwd;
						//echo "<tr><td colspan='4'>Choix 2: $pwd</td></tr>";
						// $mess_mdp = "Inconnu (compte bloqu�)";
					} elseif ($_POST['sso'] == "yes") {
						$pwd = '';
						$mess_mdp = "aucun (sso)";
						$changemdp = 'n';
						//echo "<tr><td colspan='4'>sso</td></tr>";
					}

					// utilise le pr�nom compos� s'il existe, plut�t que le premier pr�nom

					//$res = mysql_query("INSERT INTO utilisateurs VALUES ('".$login_prof."', '".$prof[$k]["nom_usage"]."', '".$premier_prenom."', '".$civilite."', '".$pwd."', '', 'professeur', 'actif', 'y', '')");
					//$sql="INSERT INTO utilisateurs SET login='$login_prof', nom='".$prof[$k]["nom_usage"]."', prenom='$premier_prenom', civilite='$civilite', password='$pwd', statut='professeur', etat='actif', change_mdp='y'";
					$sql="INSERT INTO utilisateurs SET login='$login_prof', nom='".$prof[$k]["nom_usage"]."', prenom='$premier_prenom', civilite='$civilite', password='$pwd', statut='professeur', etat='actif', change_mdp='".$changemdp."', numind='P".$prof[$k]["id"]."'";
					$res = mysql_query($sql);
					// Pour debug:
					//echo "<tr><td colspan='4'>$sql</td></tr>";

					$tab_nouveaux_profs[]="$login_prof|$mess_mdp";

					if(!$res){$nb_reg_no++;}
					$res = mysql_query("INSERT INTO tempo2 VALUES ('".$login_prof."', '"."P".$prof[$k]["id"]."')");

					$alt=$alt*(-1);
					echo "<tr class='lig$alt'>\n";
					echo "<td><p><font color='red'>".$login_prof."</font></p></td><td><p>".$prof[$k]["nom_usage"]."</p></td><td><p>".$premier_prenom."</p></td><td>".$mess_mdp."</td></tr>\n";
				} else {
					//$res = mysql_query("UPDATE utilisateurs set etat='actif' where login = '".$login_prof_gepi."'");
					// On corrige aussi les nom/pr�nom/civilit� et numind parce que la reconnaissance a aussi pu se faire sur le nom/pr�nom
					$res = mysql_query("UPDATE utilisateurs set etat='actif', nom='".$prof[$k]["nom_usage"]."', prenom='$premier_prenom', civilite='$civilite', numind='P".$prof[$k]["id"]."' where login = '".$login_prof_gepi."'");
					if(!$res) $nb_reg_no++;
					$res = mysql_query("INSERT INTO tempo2 VALUES ('".$login_prof_gepi."', '"."P".$prof[$k]["id"]."')");

					$alt=$alt*(-1);
					echo "<tr class='lig$alt'>\n";
					echo "<td><p><font color='green'>".$login_prof_gepi."</font></p></td><td><p>".$prof[$k]["nom_usage"]."</p></td><td><p>".$prof[$k]["prenom"]."</p></td><td>Inchang�</td></tr>\n";
				}
			}
		}
	}
	echo "</table>\n";


	if((isset($tab_nouveaux_profs))&&(count($tab_nouveaux_profs)>0)) {
		echo "<form action='../utilisateurs/impression_bienvenue.php' method='post' target='_blank'>\n";
		echo "<p>Imprimer les fiches bienvenue pour les nouveaux professeurs&nbsp;: \n";
		for($i=0;$i<count($tab_nouveaux_profs);$i++) {
			$tmp_tab=explode('|',$tab_nouveaux_profs[$i]);
			echo "<input type='hidden' name='user_login[]' value='$tmp_tab[0]' />\n";
			echo "<input type='hidden' name='mot_de_passe[]' value='$tmp_tab[1]' />\n";
		}
		echo "<input type='submit' value='Imprimer' /></p>\n";
		echo "</form>\n";
	}

	if ($nb_reg_no != 0) {
		echo "<p>Lors de l'enregistrement des donn�es il y a eu <span style='color:red;'>$nb_reg_no erreurs</span>. Essayez de trouvez la cause de l'erreur et recommencez la proc�dure avant de passer � l'�tape suivante.\n";
	}
	else {
		echo "<p>L'importation des professeurs dans la base GEPI a �t� effectu�e avec succ�s !</p>\n";

		/*
		echo "<p><b>* Pr�cision sur les mots de passe (en non-SSO) :</b><br />
		(il est conseill� d'imprimer cette page)</p>
		<ul>
		<li>Lorsqu'un nouveau professeur est ins�r� dans la base GEPI, son mot de passe lors de la premi�re
		connexion � GEPI est son NUMEN.</li>
		<li>Si le NUMEM n'est pas disponible dans le fichier F_wind.csv, GEPI g�n�re al�atoirement
		un mot de passe.</li></ul>";
		*/
		echo "<p><b>* Pr�cision sur les mots de passe (en non-SSO) :</b></p>\n";
		echo "<ul>
		<li>Lorsqu'un nouveau professeur est ins�r� dans la base GEPI, son mot de passe lors de la premi�re
		connexion � GEPI est celui choisi � l'�tape pr�c�dente:<br />
			<ul>
			<li>Mot de passe dapr�s la date de naissance au format 'aaaammjj', ou</li>
			<li>un mot de passe g�n�r� al�atoirement par GEPI.<br />(il est alors conseill� d'imprimer cette page)</li>
			</ul>
		</ul>\n";
		if ($_POST['sso'] != "yes") {
			echo "<p><b>Dans tous les cas le nouvel utilisateur est amen� � changer son mot de passe lors de sa premi�re connexion.</b></p>\n";
		}
		echo "<br />\n<p>Vous pouvez proc�der � la cinqui�me phase d'affectation des mati�res � chaque professeur, d'affectation des professeurs dans chaque classe et de d�finition des options suivies par les �l�ves.</p>\n";
	}


	// Cr�ation du f_div.csv pour l'import des profs principaux plus loin
	affiche_debug("Cr�ation du f_div.csv pour l'import des profs principaux lors d'une autre �tape.<br />\n");
	$fich=fopen("../temp/$tempdir/f_div.csv","w+");
	$chaine="DIVCOD;NUMIND";
	if($fich){
		fwrite($fich,html_entity_decode_all_version($chaine)."\n");
	}
	affiche_debug($chaine."<br />\n");

	$tabchaine=array();
	for($m=0;$m<count($prof);$m++){
		if(isset($prof[$m]["prof_princ"])){
			for($n=0;$n<count($prof[$m]["prof_princ"]);$n++){
				$tabchaine[]=$prof[$m]["prof_princ"][$n]["code_structure"].";"."P".$prof[$m]["id"];
				//$chaine=$prof[$m]["prof_princ"][$n]["code_structure"].";"."P".$prof[$m]["id"];
				//if($fich){
				//	fwrite($fich,html_entity_decode_all_version($chaine)."\n");
				//}
				affiche_debug($chaine."<br />\n");
			}
		}
	}
	sort($tabchaine);
	for($i=0;$i<count($tabchaine);$i++){
		if($fich){
			fwrite($fich,html_entity_decode_all_version($tabchaine[$i])."\n");
		}
	}
	fclose($fich);


	if (getSettingValue("use_ent") == "y"){

		echo '<p style="text-align: center; font-weight: bold;"><a href="../mod_ent/gestion_ent_profs.php">V�rifier les logins avant de poursuivre</a></p>'."\n";

	} else {
		/*
		echo '<p style="text-align: center; font-weight: bold;"><a href="prof_disc_classe_csv.php?a=a'.add_token_in_url().'">Proc�der � la cinqui�me phase d\'initialisation</a></p>'."\n";

		echo "<p style='text-align: center; font-weight: bold;'>Si la remont�e vers STS n'a pas encore �t� effectu�e, vous pouvez effectuer l'initialisation des enseignements � partir d'un export CSV de UnDeuxTemps&nbsp;: <a href='traite_csv_udt.php?a=a".add_token_in_url()."'>Proc�der � la cinqui�me phase d'initialisation</a><br />(<i>proc�dure encore exp�rimentale... il se peut que vous ayez des groupes en trop</i>)</p>\n";
		*/

		$nb_modes=2;
		if(file_exists('init_alternatif.php')) {
			$nb_modes=3;
		}

		echo "<p>La cr�ation des enseignements peut se faire de $nb_modes fa�ons diff�rentes (<i>par ordre de pr�f�rence</i>)&nbsp;:</p>\n";

		echo "<ul>\n";
		echo "<li>\n";
		//  style="text-align: center; font-weight: bold;"
		echo "<p>";
		echo "Si votre emploi du temps est remont� vers STS, vous disposez d'un fichier <b>sts_emp_RNE_ANNEE.xml</b>&nbsp;:";
		echo "<br />";
		echo "<a href='prof_disc_classe_csv.php?a=a".add_token_in_url()."'>Proc�der � la cinqui�me phase d'initialisation</a></p>\n";
		echo "</li>\n";

		echo "<li>\n";
		echo "<p>Si la remont�e vers STS n'a pas encore �t� effectu�e, vous pouvez effectuer l'initialisation des enseignements � partir d'un export CSV de UnDeuxTemps&nbsp;: <br /><a href='traite_csv_udt.php?a=a".add_token_in_url()."'>Proc�der � la cinqui�me phase d'initialisation</a><br />(<i>proc�dure encore exp�rimentale... il se peut que vous ayez des groupes en trop</i>)</p>\n";
		echo "</li>\n";

		if($nb_modes==3) {
			echo "<li>\n";
			echo "<p>Si vous n'avez pas non plus d'export CSV d'UnDeuxTemps&nbsp;: <br /><a href='init_alternatif.php?'>Initialisation alternative des enseignements</a><br />(<i>le mode le plus fastidieux</i>)</p>\n";
			echo "</li>\n";
		}

		echo "</ul>\n";

	}

	echo "<p><br /></p>\n";
}

require("../lib/footer.inc.php");
?>