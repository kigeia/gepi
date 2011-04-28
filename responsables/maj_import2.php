<?php
/*
 * $Id$
 *
 * Copyright 2001-2011 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun
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

//extract($_GET, EXTR_OVERWRITE);
//extract($_POST, EXTR_OVERWRITE);

// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
    header("Location: ../utilisateurs/mon_compte.php?change_mdp=yes");
    die();
} else if ($resultat_session == '0') {
    header("Location: ../logout.php?auto=1");
    die();
}

// INSERT INTO `droits` VALUES ('/responsables/maj_import2.php', 'V', 'F', 'F', 'F', 'F', 'F', 'F', 'Mise � jour Sconet', '');
if (!checkAccess()) {
    header("Location: ../logout.php?auto=1");
    die();
}

$ele_lieu_naissance=getSettingValue("ele_lieu_naissance") ? getSettingValue("ele_lieu_naissance") : "n";
$ne_pas_tester_les_changements_de_classes=getSettingValue("no_test_chgt_clas") ? getSettingValue("no_test_chgt_clas") : "y";
// INSERT INTO setting SET name='no_test_chgt_clas', value='n';
// UPDATE setting SET value='n' WHERE name='no_test_chgt_clas';

$auth_sso=getSettingValue("auth_sso") ? getSettingValue("auth_sso") : "";

if($auth_sso=='lcs') {
	function connect_ldap($l_adresse,$l_port,$l_login,$l_pwd) {
		$ds = @ldap_connect($l_adresse, $l_port);
		if($ds) {
			// On dit qu'on utilise LDAP V3, sinon la V2 par d?faut est utilis? et le bind ne passe pas.
			$norme = @ldap_set_option($ds, LDAP_OPT_PROTOCOL_VERSION, 3);
			// Acc?s non anonyme
			if ($l_login != '') {
				// On tente un bind
				$b = @ldap_bind($ds, $l_login, $l_pwd);
			} else {
				// Acc?s anonyme
				$b = @ldap_bind($ds);
			}
			if ($b) {
				return $ds;
			} else {
				return false;
			}
		} else {
			return false;
		}
	}

	// Initialisation
	$lcs_ldap_people_dn = 'ou=people,'.$lcs_ldap_base_dn;
	$lcs_ldap_groups_dn = 'ou=groups,'.$lcs_ldap_base_dn;

}

function extr_valeur($lig){
	unset($tabtmp);
	//$tabtmp=explode(">",my_ereg_replace("<",">",$lig));
	$tabtmp=explode(">",strtr($lig,"<",">"));
	return trim($tabtmp[2]);
}

function ouinon($nombre){
	if($nombre==1){return "O";}elseif($nombre==0){return "N";}else{return "";}
}
function sexeMF($nombre){
	//if($nombre==2){return "F";}else{return "M";}
	if($nombre==2){return "F";}elseif($nombre==1){return "M";}else{return "";}
}

function affiche_debug($texte){
	// Passer � 1 la variable pour g�n�rer l'affichage des infos de debug...
	$debug=0;
	if($debug==1){
		echo "<font color='green'>".$texte."</font>";
		flush();
	}
}

// Initialisation du r�pertoire actuel de sauvegarde
$dirname = getSettingValue("backup_directory");

function info_debug($texte,$mode=0) {
	global $step;
	global $dirname;

	$debug=0;
	if($debug==1) {
		if($mode==1) {
			$fich_debug=fopen("../backup/".$dirname."/debug_maj_import2.txt","w+");
			fwrite($fich_debug,"$step;$texte;".time()."\n");
			fclose($fich_debug);
		}
		elseif($mode==2) {
			echo "<p><a href='../backup/".$dirname."/debug_maj_import2.txt' target='_blank'>Fichier debug</a></p>";
		}
		else {
			//$fich_debug=fopen("/tmp/debug_maj_import2.txt","a+");
			$fich_debug=fopen("../backup/".$dirname."/debug_maj_import2.txt","a+");
			fwrite($fich_debug,"$step;$texte;".time()."\n");
			fclose($fich_debug);
		}
	}
}

function maj_ini_prenom($prenom){
	$prenom2="";
	$tab1=explode("-",$prenom);
	for($i=0;$i<count($tab1);$i++){
		if($i>0){
			$prenom2.="-";
		}
		$tab2=explode(" ",$tab1[$i]);
		for($j=0;$j<count($tab2);$j++){
			if($j>0){
				$prenom2.=" ";
			}
			$prenom2.=ucfirst(strtolower($tab2[$j]));
		}
	}
	return $prenom2;
}

// Etape...
$step=isset($_POST['step']) ? $_POST['step'] : (isset($_GET['step']) ? $_GET['step'] : NULL);

$parcours_diff=isset($_POST['parcours_diff']) ? $_POST['parcours_diff'] : NULL;

$tab_ele_id=isset($_POST['tab_ele_id']) ? $_POST['tab_ele_id'] : NULL;
$tab_ele_id_diff=isset($_POST['tab_ele_id_diff']) ? $_POST['tab_ele_id_diff'] : NULL;
$nb_parcours=isset($_POST['nb_parcours']) ? $_POST['nb_parcours'] : NULL;

$tab_pers_id=isset($_POST['tab_pers_id']) ? $_POST['tab_pers_id'] : NULL;
$tab_pers_id_diff=isset($_POST['tab_pers_id_diff']) ? $_POST['tab_pers_id_diff'] : NULL;

$total_pers_diff=isset($_POST['total_pers_diff']) ? $_POST['total_pers_diff'] : NULL;

$valid_pers_id=isset($_POST['valid_pers_id']) ? $_POST['valid_pers_id'] : NULL;
$liste_pers_id=isset($_POST['liste_pers_id']) ? $_POST['liste_pers_id'] : NULL;

$tab_adr_id=isset($_POST['tab_adr_id']) ? $_POST['tab_adr_id'] : NULL;
$tab_adr_id_diff=isset($_POST['tab_adr_id_diff']) ? $_POST['tab_adr_id_diff'] : NULL;

/*
$tab_resp_id=isset($_POST['tab_resp_id']) ? $_POST['tab_resp_id'] : NULL;
$tab_resp_id_diff=isset($_POST['tab_resp_id_diff']) ? $_POST['tab_resp_id_diff'] : NULL;
*/

$tab_resp=isset($_POST['tab_resp']) ? $_POST['tab_resp'] : NULL;
$tab_resp_diff=isset($_POST['tab_resp_diff']) ? $_POST['tab_resp_diff'] : NULL;

$total_diff=isset($_POST['total_diff']) ? $_POST['total_diff'] : NULL;

$liste_assoc=isset($_POST['liste_assoc']) ? $_POST['liste_assoc'] : NULL;

$ne_pas_proposer_resp_sans_eleve=isset($_POST['ne_pas_proposer_resp_sans_eleve']) ? $_POST['ne_pas_proposer_resp_sans_eleve'] : (isset($_GET['ne_pas_proposer_resp_sans_eleve']) ? $_GET['ne_pas_proposer_resp_sans_eleve'] : (isset($_SESSION['ne_pas_proposer_resp_sans_eleve']) ? $_SESSION['ne_pas_proposer_resp_sans_eleve'] : "si"));

$alert_diff_mail_resp=isset($_POST['alert_diff_mail_resp']) ? $_POST['alert_diff_mail_resp'] : (isset($_GET['alert_diff_mail_resp']) ? $_GET['alert_diff_mail_resp'] : (isset($_SESSION['alert_diff_mail_resp']) ? $_SESSION['alert_diff_mail_resp'] : "n"));

$alert_diff_mail_ele=isset($_POST['alert_diff_mail_ele']) ? $_POST['alert_diff_mail_ele'] : (isset($_GET['alert_diff_mail_ele']) ? $_GET['alert_diff_mail_ele'] : (isset($_SESSION['alert_diff_mail_ele']) ? $_SESSION['alert_diff_mail_ele'] : "n"));

$alert_diff_etab_origine=isset($_POST['alert_diff_etab_origine']) ? $_POST['alert_diff_etab_origine'] : (isset($_GET['alert_diff_etab_origine']) ? $_GET['alert_diff_etab_origine'] : (isset($_SESSION['alert_diff_etab_origine']) ? $_SESSION['alert_diff_etab_origine'] : "n"));

$stop=isset($_POST['stop']) ? $_POST['stop'] : (isset($_GET['stop']) ? $_GET['stop'] :'n');

//$style_specifique="responsables/maj_import2";

$gepiSchoolRne=getSettingValue("gepiSchoolRne") ? getSettingValue("gepiSchoolRne") : "";

$mysql_collate=getSettingValue("mysql_collate") ? getSettingValue("mysql_collate") : "";
$chaine_mysql_collate="";
if($mysql_collate!="") {$chaine_mysql_collate="COLLATE $mysql_collate";}

// 29/09/2010
$chaine_collate="";
$sql="show full columns from eleves WHERE Field='nom';";
$res_col_eleves=mysql_query($sql);
if(mysql_num_rows($res_col_eleves)>0) {
	$lig_col_eleves=mysql_fetch_object($res_col_eleves);
	if($lig_col_eleves->Collation!='utf8_unicode_ci') {$chaine_collate="COLLATE latin1_bin ";}
}
// A REVOIR: Avec cette recherche, on pourrait cr�er temp_gep_import2 avec la bonne collation.

//**************** EN-TETE *****************
$titre_page = "Mise � jour eleves/responsables";
require_once("../lib/header.inc");
//**************** FIN EN-TETE *****************

require_once("../init_xml2/init_xml_lib.php");

//debug_var();

if(isset($step)) {
	if(($step==0)||
		($step=="0b")||
		($step==1)||
		($step==2)||
		($step==3)||
		($step==10)||
		($step==11)||
		($step==12)||
		($step==13)||
		($step==14)||
		($step==18)
		) {
//		($step==17)

		echo "<div style='float: right; border: 1px solid black; width: 4em;'>
<form name='formstop' action='".$_SERVER['PHP_SELF']."' method='post'>
<input type='checkbox' name='stop' id='stop' value='y' onchange='stop_change()' ";
//if(isset($stop)){
if($stop=='y'){
	echo "checked ";
}
echo "/> <a href='#' onmouseover=\"afficher_div('div_stop','y',10,20);\">Stop</a>
</form>\n";
		echo "</div>\n";

		echo creer_div_infobulle("div_stop","","","Ce bouton permet s'il est coch� d'interrompre les passages automatiques � la page suivante","",12,0,"n","n","y","n");

		echo "<script type='text/javascript'>
	temporisation_chargement='ok';
	cacher_div('div_stop');
</script>\n";


							echo "<script type='text/javascript'>
function stop_change(){
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}
	if(document.getElementById('id_form_stop')){
		document.getElementById('id_form_stop').value=stop;
	}
}

function test_stop(num){
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}
	//document.getElementById('id_form_stop').value=stop;
	if(stop=='n'){
		//setTimeout(\"document.location.replace('".$_SERVER['PHP_SELF']."?step=1')\",2000);
		//document.location.replace('".$_SERVER['PHP_SELF']."?step='+num+'&amp;stop='+stop);
		document.location.replace('".$_SERVER['PHP_SELF']."?step='+num+'&stop='+stop);
	}
}

function test_stop_bis(num,cpt_saut_lignes){
//function test_stop_bis(num,cpt){
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}
	//document.getElementById('id_form_stop').value=stop;
	if(stop=='n'){
		//stop='y';
		//document.location.replace('".$_SERVER['PHP_SELF']."?step='+num+'&amp;stop='+stop+'&amp;cpt_saut_lignes='+cpt_saut_lignes);
		//document.location.replace('".$_SERVER['PHP_SELF']."?step='+num+'&amp;stop='+stop+'&amp;cpt_saut_lignes='+cpt);
		//document.location.replace('".$_SERVER['PHP_SELF']."?cpt_saut_lignes='+cpt+'&amp;step='+num+'&amp;stop='+stop);
		//document.location.replace('".$_SERVER['PHP_SELF']."?cpt_saut_lignes='+cpt+'&step='+num+'&stop='+stop);
		document.location.replace('".$_SERVER['PHP_SELF']."?cpt_saut_lignes='+cpt_saut_lignes+'&step='+num+'&stop='+stop);
	}
}

function test_stop2(){
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}
	document.getElementById('id_form_stop').value=stop;
	if(stop=='n'){
		//setTimeout(\"document.forms['formulaire'].submit();\",1000);
		document.forms['formulaire'].submit();
	}
}


function test_stop_suite(num){
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}

	//document.location.replace('".$_SERVER['PHP_SELF']."?step='+num+'&amp;stop='+stop);
	document.location.replace('".$_SERVER['PHP_SELF']."?step='+num+'&stop='+stop);
}

function test_stop_suite_bis(num,cpt_saut_lignes){
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}

	//document.location.replace('".$_SERVER['PHP_SELF']."?step='+num+'&amp;stop='+stop+'&amp;cpt_saut_lignes='+cpt_saut_lignes);
	document.location.replace('".$_SERVER['PHP_SELF']."?step='+num+'&stop='+stop+'&cpt_saut_lignes='+cpt_saut_lignes);
}

</script>\n";

	}
}

echo "<p class='bold'>";
if(isset($_SESSION['retour_apres_maj_sconet'])) {
	echo "<a href=\"".$_SESSION['retour_apres_maj_sconet']."\"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>";
}
else {
	echo "<a href=\"index.php\"><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>";
}
//echo "</p>\n";


// On fournit les fichiers CSV g�n�r�s depuis les XML de SCONET...
//if (!isset($is_posted)) {
if(!isset($step)) {
	echo "</p>\n";

	//echo time()."<br />\n";

	echo "<h2>Import/mise � jour des �l�ves</h2>\n";

	echo "<p>Cette page est destin�e � effectuer l'import des �l�ves et responsables d'apr�s les modifications et ajouts effectu�s sur Sconet.</p>\n";

	echo "<p>Vous allez importer des fichiers d'exports XML de Sconet.<br />\nLes fichiers requis au cours de la proc�dure sont dans un premier temps ElevesAvecAdresses.xml, puis le fichier ResponsablesAvecAdresses.xml</p>\n";

	echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post'>\n";
	echo add_token_field();

	//echo "<input type=hidden name='is_posted' value='yes' />\n";
	echo "<input type=hidden name='step' value='0' />\n";
	//echo "<input type=hidden name='mode' value='1' />\n";
	echo "<p>S�lectionnez le fichier <b>ElevesAvecAdresses.xml</b> (<i>ou ElevesSansAdresses.xml</i>):<br />\n";
	echo "<input type=\"file\" size=\"80\" name=\"eleves_xml_file\" /><br />\n";
	if ($gepiSettings['unzipped_max_filesize']>=0) {
		echo "<p style=\"font-size:small; color: red;\"><i>REMARQUE&nbsp;:</i> Vous pouvez fournir � Gepi le fichier compress� issu directement de SCONET. (Ex : ElevesAvecAdresses.zip)</p>";
	}

	$sql_ele_tmp="SELECT 1=1 FROM utilisateurs WHERE statut='eleve';";
	$test_comptes_ele=mysql_query($sql_ele_tmp);
	if(mysql_num_rows($test_comptes_ele)==0) {
		echo "<input type='hidden' name='alert_diff_mail_ele' id='alert_diff_mail_ele_y' value='y' />\n";
	}
	else {
		echo "<p>Pour les �l�ves qui disposent d'un compte d'utilisateur, <br />\n";
		echo "<input type='radio' name='alert_diff_mail_ele' id='alert_diff_mail_ele_y' value='y' checked />\n";
		echo "<label for='alert_diff_mail_ele_y' style='cursor: pointer;'> signaler";
		echo " les diff�rences d'adresse Mail entre Sconet et le compte d'utilisateur.</label><br />\n";
		echo "<input type='radio' name='alert_diff_mail_ele' id='alert_diff_mail_ele_n' value='n' />\n";
		echo "<label for='alert_diff_mail_ele_n' style='cursor: pointer;'> ne pas signaler";
		echo " les diff�rences d'adresse Mail entre Sconet et le compte d'utilisateur.</label></p>\n";
	}

	echo "<p>\n";
	echo "<input type='radio' name='alert_diff_etab_origine' id='alert_diff_etab_origine_y' value='y' checked />\n";
	echo "<label for='alert_diff_etab_origine_y' style='cursor: pointer;'> signaler";
	echo " les modifications d'�tablissement d'origine.</label><br />\n";
	echo "<input type='radio' name='alert_diff_etab_origine' id='alert_diff_etab_origine_n' value='n' />\n";
	echo "<label for='alert_diff_etab_origine_n' style='cursor: pointer;'> ne pas signaler";
	echo " les modifications d'�tablissement d'origine.</label></p>\n";

	//==============================
	// AJOUT pour tenir compte de l'automatisation ou non:
	//echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
	echo "<input type='checkbox' name='stop' id='id_form_stop' value='y' /><label for='id_form_stop' style='cursor: pointer;'> D�sactiver le mode automatique.</label>\n";
	//==============================

	echo "<p><input type='submit' value='Valider' /></p>\n";
	echo "</form>\n";

	echo "<p>Il est recommand� d'importer les informations �l�ves et de ne passer qu'ensuite � l'import des informations responsables.<br />\n";
	echo "<a href='".$_SERVER['PHP_SELF']."?is_posted=y&amp;step=9'>Passer n�anmoins � la page d'importation des responsables</a></p>\n";

	echo "<p><br /></p>\n";

	echo "<p><i>NOTES&nbsp;:</i></p>
	<ul>
		<li>Apr�s une phase d'analyse des diff�rences, les diff�rences seront affich�es et des cases � cocher seront propos�es pour valider les modifications.</li>
		<li>Pour proc�der � la mise � jour deux fichiers seront requis&nbsp;:<br />
		Les fichiers '<b>ElevesSansAdresses.xml</b>' et '<b>ResponsablesAvecAdresses.xml</b>' doivent �tre r�cup�r�s depuis l'application web Sconet.<br />
		Demandez gentiment � votre secr�taire de se rendre dans 'Sconet/Acc�s Base �l�ves mode normal/Exploitation/Exports standard/Exports XML g�n�riques' pour r�cup�rer les fichiers '<b>ElevesSansAdresses.xml</b>' et '<b>ResponsablesAvecAdresses.xml</b>'.</li>
	</ul>\n";

	// Pour afficher le lien vers le fichier de debug.
	info_debug("",2);
}
else{
	if($step>0){
		echo " | <a href=\"".$_SERVER['PHP_SELF']."\">Mise � jour Sconet</a>";
	}
	echo "</p>\n";

	//echo "\$step=$step<br />\n";

	/*
	if(($step==0)||
		($step==1)||
		($step==2)||
		($step==3)||
		($step==7)||
		($step==8)||
		($step==9)||
		($step==10)||
		($step==11)||
		($step==14)
		) {
		echo "<div style='float: right; border: 1px solid black; width: 4em;'>
<form name='formstop' action='".$_SERVER['PHP_SELF']."' method='post'>
<input type='checkbox' name='stop' id='stop' value='y' /> Stop
</form>
</div>\n";
	}
	*/

	// On va uploader les fichiers XML dans le tempdir de l'utilisateur (administrateur, ou scolarit� pour les m�j Sconet)
	$tempdir=get_user_temp_directory();
	if(!$tempdir){
		echo "<p style='color:red'>Il semble que le dossier temporaire de l'utilisateur ".$_SESSION['login']." ne soit pas d�fini!?</p>\n";
		// Il ne faut pas aller plus loin...
		// SITUATION A GERER
	}

	$post_max_size=ini_get('post_max_size');
	$upload_max_filesize=ini_get('upload_max_filesize');
	$max_execution_time=ini_get('max_execution_time');
	$memory_limit=ini_get('memory_limit');

	//if(!isset($_POST['step'])){
	switch($step){
		case "0":
			// Affichage des informations �l�ves
			echo "<h2>Import/mise � jour des �l�ves</h2>\n";

			check_token(false);

			$_SESSION['alert_diff_mail_ele']=$alert_diff_mail_ele;
			$_SESSION['alert_diff_etab_origine']=$alert_diff_etab_origine;

			$xml_file = isset($_FILES["eleves_xml_file"]) ? $_FILES["eleves_xml_file"] : NULL;

			if(!is_uploaded_file($xml_file['tmp_name'])) {
				echo "<p style='color:red;'>L'upload du fichier a �chou�.</p>\n";

				echo "<p>Les variables du php.ini peuvent peut-�tre expliquer le probl�me:<br />\n";
				echo "post_max_size=$post_max_size<br />\n";
				echo "upload_max_filesize=$upload_max_filesize<br />\n";
				echo "</p>\n";

				// Il ne faut pas aller plus loin...
				// SITUATION A GERER
				require("../lib/footer.inc.php");
				die();
			}
			else{
				if(!file_exists($xml_file['tmp_name'])){
					echo "<p style='color:red;'>Le fichier aurait �t� upload�... mais ne serait pas pr�sent/conserv�.</p>\n";

					echo "<p>Les variables du php.ini peuvent peut-�tre expliquer le probl�me:<br />\n";
					echo "post_max_size=$post_max_size<br />\n";
					echo "upload_max_filesize=$upload_max_filesize<br />\n";
					echo "et le volume de ".$xml_file['name']." serait<br />\n";
					echo "\$xml_file['size']=".volume_human($xml_file['size'])."<br />\n";
					echo "</p>\n";
					// Il ne faut pas aller plus loin...
					// SITUATION A GERER
					require("../lib/footer.inc.php");
					die();
				}

				echo "<p>Le fichier a �t� upload�.</p>\n";

				/*
				echo "\$xml_file['tmp_name']=".$xml_file['tmp_name']."<br />\n";
				echo "\$tempdir=".$tempdir."<br />\n";

				echo "<p>Les variables du php.ini peuvent peut-�tre expliquer le probl�me:<br />\n";
				echo "post_max_size=$post_max_size<br />\n";
				echo "upload_max_filesize=$upload_max_filesize<br />\n";
				echo "\$xml_file['size']=".volume_human($xml_file['size'])."<br />\n";
				echo "</p>\n";
				*/

				//$source_file=stripslashes($xml_file['tmp_name']);
				$source_file=$xml_file['tmp_name'];
				$dest_file="../temp/".$tempdir."/eleves.xml";
				$res_copy=copy("$source_file" , "$dest_file");

				//===============================================================
				// ajout prise en compte des fichiers ZIP: Marc Leygnac

				$unzipped_max_filesize=getSettingValue('unzipped_max_filesize')*1024*1024;
				// $unzipped_max_filesize = 0    pas de limite de taille pour les fichiers extraits
				// $unzipped_max_filesize < 0    extraction zip d�sactiv�e
				if($unzipped_max_filesize>=0) {
					$fichier_emis=$xml_file['name'];
					$extension_fichier_emis=strtolower(strrchr($fichier_emis,"."));
					if (($extension_fichier_emis==".zip")||($xml_file['type']=="application/zip"))
						{
						require_once('../lib/pclzip.lib.php');
						$archive = new PclZip($dest_file);

						if (($list_file_zip = $archive->listContent()) == 0) {
							echo "<p style='color:red;'>Erreur : ".$archive->errorInfo(true)."</p>\n";
							require("../lib/footer.inc.php");
							die();
						}

						if(sizeof($list_file_zip)!=1) {
							echo "<p style='color:red;'>Erreur : L'archive contient plus d'un fichier.</p>\n";
							require("../lib/footer.inc.php");
							die();
						}

						/*
						echo "<p>\$list_file_zip[0]['filename']=".$list_file_zip[0]['filename']."<br />\n";
						echo "\$list_file_zip[0]['size']=".$list_file_zip[0]['size']."<br />\n";
						echo "\$list_file_zip[0]['compressed_size']=".$list_file_zip[0]['compressed_size']."</p>\n";
						*/
						//echo "<p>\$unzipped_max_filesize=".$unzipped_max_filesize."</p>\n";

						if(($list_file_zip[0]['size']>$unzipped_max_filesize)&&($unzipped_max_filesize>0)) {
							echo "<p style='color:red;'>Erreur : La taille du fichier extrait (<i>".$list_file_zip[0]['size']." octets</i>) d�passe la limite param�tr�e (<i>$unzipped_max_filesize octets</i>).</p>\n";
							require("../lib/footer.inc.php");
							die();
						}

						$res_extract=$archive->extract(PCLZIP_OPT_PATH, "../temp/".$tempdir);
						if ($res_extract != 0) {
							echo "<p>Le fichier upload� a �t� d�zipp�.</p>\n";
							$fichier_extrait=$res_extract[0]['filename'];
							unlink("$dest_file"); // Pour Wamp...
							$res_copy=rename("$fichier_extrait" , "$dest_file");
						}
						else {
							echo "<p style='color:red'>Echec de l'extraction de l'archive ZIP.</p>\n";
							require("../lib/footer.inc.php");
							die();
						}
					}
				}
				//fin  ajout prise en compte des fichiers ZIP
				//===============================================================

				if(!$res_copy){
					echo "<p style='color:red;'>La copie du fichier vers le dossier temporaire a �chou�.<br />V�rifiez que l'utilisateur ou le groupe apache ou www-data a acc�s au dossier temp/$tempdir</p>\n";
					// Il ne faut pas aller plus loin...
					// SITUATION A GERER
					require("../lib/footer.inc.php");
					die();
				}
				else{
					echo "<p>La copie du fichier vers le dossier temporaire a r�ussi.</p>\n";

					info_debug("Mise � jour sconet",1);

					$sql="DROP TABLE IF EXISTS temp_gep_import2;";
					info_debug($sql);
					$suppr_table = mysql_query($sql);

					$sql="CREATE TABLE IF NOT EXISTS `temp_gep_import2` (
					`ID_TEMPO` varchar(40) NOT NULL default '',
					`LOGIN` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELENOM` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEPRE` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELESEXE` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEDATNAIS` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELENOET` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELE_ID` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEDOUBL` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELENONAT` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEREG` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`DIVCOD` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ETOCOD_EP` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEOPT1` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEOPT2` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEOPT3` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEOPT4` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEOPT5` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEOPT6` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEOPT7` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEOPT8` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEOPT9` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEOPT10` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEOPT11` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`ELEOPT12` varchar(40) $chaine_mysql_collate NOT NULL default '',
					`LIEU_NAISSANCE` varchar(50) $chaine_mysql_collate NOT NULL default '',
					`MEL` varchar(255) $chaine_mysql_collate NOT NULL default ''
					);";
					info_debug($sql);
					$create_table = mysql_query($sql);

					$sql="TRUNCATE TABLE temp_gep_import2;";
					info_debug($sql);
					$vide_table = mysql_query($sql);

					//echo "<p style='color:red;'>DEBUG \$tempdir=$tempdir</p>";

					echo "<script type='text/javascript'>
	setTimeout(\"test_stop('0b')\",3000);
</script>\n";

					echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=0b&amp;stop=y' onClick=\"test_stop_suite('0b'); return false;\">Suite</a></p>\n";
	
					require("../lib/footer.inc.php");
					die();

					//================================
					// D�coupage... n'est plus utilis�
					/*
					$fp=fopen($dest_file,"r");
					if($fp){

						// On commence par la section STRUCTURES pour ne r�cup�rer que les ELE_ID d'�l�ves qui sont dans une classe.
						echo "<p>\n";
						//echo "Analyse du fichier pour extraire les informations de la section STRUCTURES pour ne conserver que les identifiants d'�l�ves affect�s dans une classe...<br />\n";
						echo "D�coupage du fichier ElevesSansAdresses suivant les sections ELEVES, OPTIONS et STRUCTURES.<br />\n";

						// PARTIE <STRUCTURES>
						$cpt=0;
						$eleves=array();
						$temoin_structures=0;
						$temoin_struct_ele=-1;
						$temoin_struct=-1;
						$i=-1;
						//while($cpt<count($ligne)){
						while(!feof($fp)){
							$ligne=fgets($fp,4096);

							if(strstr($ligne,"<ELEVES>")){
								echo "D�but de la section ELEVES � la ligne <span style='color: blue;'>$cpt</span><br />\n";
								//$temoin_eleves++;
								$fich=fopen("../temp/".$tempdir."/section_eleves.xml","w+");
							}
							elseif(strstr($ligne,"</ELEVES>")){
								echo "Fin de la section ELEVES � la ligne <span style='color: blue;'>$cpt</span><br />\n";
								fclose($fich);
								unset($fich);
								//break;
							}
							elseif(strstr($ligne,"<OPTIONS>")){
								echo "D�but de la section OPTIONS � la ligne <span style='color: blue;'>$cpt</span><br />\n";
								//$temoin_options++;
								$fich=fopen("../temp/".$tempdir."/section_options.xml","w+");
							}
							elseif(strstr($ligne,"</OPTIONS>")){
								echo "Fin de la section OPTIONS � la ligne <span style='color: blue;'>$cpt</span><br />\n";
								//$temoin_options++;
								fclose($fich);
								unset($fich);
								//break;
							}
							elseif(strstr($ligne,"<STRUCTURES>")){
								echo "D�but de la section STRUCTURES � la ligne <span style='color: blue;'>$cpt</span><br />\n";
								//$temoin_structures++;
								$fich=fopen("../temp/".$tempdir."/section_structures.xml","w+");
							}
							elseif(strstr($ligne,"</STRUCTURES>")){
								echo "Fin de la section STRUCTURES � la ligne <span style='color: blue;'>$cpt</span><br />\n";
								//$temoin_structures++;
								fclose($fich);
								unset($fich);
								break;
							}
							elseif(isset($fich)) {
								fwrite($fich,$ligne);
							}

							$cpt++;
						}
						fclose($fp);

						//if($nb_err==0) {
							//echo "<p>La premi�re phase s'est pass�e sans erreur.</p>\n";

							echo "<script type='text/javascript'>
	setTimeout(\"test_stop('0b')\",3000);
</script>\n";
						//echo "<p><a href='".$_SERVER['PHP_SELF']."?etape=1&amp;step=1'>Suite</a></p>\n";
						//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=1&amp;stop=$stop'>Suite</a></p>\n";
						//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=1&amp;stop=y' onClick=\"test_stop_suite('1'); return false;\">Suite</a></p>\n";
						echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=0b&amp;stop=y' onClick=\"test_stop_suite('0b'); return false;\">Suite</a></p>\n";

						require("../lib/footer.inc.php");
						die();

					}
					else{
						echo "<p>ERREUR: Il n'a pas �t� possible d'ouvrir le fichier en lecture...</p>\n";

						require("../lib/footer.inc.php");
						die();
					}
					*/

				}
			}
			break;
		case "0b":

			echo "<h2>Import/mise � jour des �l�ves</h2>\n";

			$dest_file="../temp/".$tempdir."/eleves.xml";

			$ele_xml=simplexml_load_file($dest_file);
			if(!$ele_xml) {
				echo "<p style='color:red;'>ECHEC du chargement du fichier avec simpleXML.</p>\n";
				require("../lib/footer.inc.php");
				die();
			}

			$nom_racine=$ele_xml->getName();
			if(strtoupper($nom_racine)!='BEE_ELEVES') {
				echo "<p style='color:red;'>ERREUR: Le fichier XML fourni n'a pas l'air d'�tre un fichier XML El�ves.<br />Sa racine devrait �tre 'BEE_ELEVES'.</p>\n";
				require("../lib/footer.inc.php");
				die();
			}

			echo "<p>\n";
			echo "Analyse de la section STRUCTURES pour ne conserver que les identifiants d'�l�ves affect�s dans une classe...<br />\n";


			$tab_champs_struct=array("CODE_STRUCTURE","TYPE_STRUCTURE");
			$tab_ele_id=array();

			$i=-1;
			$objet_structures=($ele_xml->DONNEES->STRUCTURES);
			foreach ($objet_structures->children() as $structures_eleve) {
				//echo("<p><b>Structure</b><br />");
		
				$chaine_structures_eleve="STRUCTURES_ELEVE";
				foreach($structures_eleve->attributes() as $key => $value) {
					//echo("$key=".$value."<br />");

					if(strtoupper($key)=='ELEVE_ID') {
						// On teste si l'ELEVE_ID existe d�j�: �a ne devrait pas arriver
						if(in_array($value,$tab_ele_id)) {
							echo "<b style='color:red;'>ANOMALIE&nbsp;:</b> Il semble qu'il y a plusieurs sections STRUCTURES_ELEVE pour l'ELEVE_ID '$value'.<br />";
						}
						else {
							$i++;
							$eleves[$i]=array();

							$eleves[$i]['eleve_id']=$value;

							$eleves[$i]["structures"]=array();
							$j=0;
							//foreach($objet_structures->STRUCTURES_ELEVE->children() as $structure) {
							foreach($structures_eleve->children() as $structure) {
								$eleves[$i]["structures"][$j]=array();
								foreach($structure->children() as $key => $value) {
									if(in_array(strtoupper($key),$tab_champs_struct)) {
										$eleves[$i]["structures"][$j][strtolower($key)]=preg_replace('/"/','',trim(traite_utf8($value)));
										//my_echo("\$structure->$key=".$value."<br />)";
									}
								}
								$j++;
							}

							if($debug_import=='y') {
								echo "<pre style='color:green;'><b>Tableau \$eleves[$i]&nbsp;:</b>";
								print_r($eleves[$i]);
								echo "</pre>";
							}
						}
					}
				}
			}

			$nb_err=0;
			// $cpt: Identifiant id_tempo
			$id_tempo=1;
			for($i=0;$i<count($eleves);$i++){

				$temoin_div_trouvee="";
				if(isset($eleves[$i]["structures"])){
					if(count($eleves[$i]["structures"])>0){
						for($j=0;$j<count($eleves[$i]["structures"]);$j++){
							if($eleves[$i]["structures"][$j]["type_structure"]=="D"){
								$temoin_div_trouvee="oui";
								break;
							}
						}
						if($temoin_div_trouvee!=""){
							$eleves[$i]["classe"]=$eleves[$i]["structures"][$j]["code_structure"];
						}
					}
				}

				if($temoin_div_trouvee=='oui'){
					$sql="INSERT INTO temp_gep_import2 SET id_tempo='$id_tempo', ";
					$sql.="ele_id='".$eleves[$i]['eleve_id']."', ";
					$sql.="divcod='".$eleves[$i]['classe']."';";
					//echo "$sql<br />\n";
					info_debug($sql);
					$res_insert=mysql_query($sql);
					if(!$res_insert){
						echo "Erreur lors de la requ�te $sql<br />\n";
						$nb_err++;
					}
					$id_tempo++;
				}
			}
			if($nb_err==0) {
				echo "<p style='bold'>La premi�re phase s'est pass�e sans erreur.</p>\n";

				echo "<script type='text/javascript'>
	setTimeout(\"test_stop('1')\",3000);
</script>\n";
			}
			elseif($nb_err==1) {
				echo "<p>$nb_err erreur.</p>\n";
			}
			else{
				echo "<p>$nb_err erreurs</p>\n";
			}

			$stat=$id_tempo-1-$nb_err;
			echo "<p>$stat associations identifiant �l�ve/classe ont �t� ins�r�(s) dans la table 'temp_gep_import2'.</p>\n";

			//echo "<p><a href='".$_SERVER['PHP_SELF']."?etape=1&amp;step=1'>Suite</a></p>\n";
			//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=1&amp;stop=$stop'>Suite</a></p>\n";
			echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=1&amp;stop=y' onClick=\"test_stop_suite('1'); return false;\">Suite</a></p>\n";

			require("../lib/footer.inc.php");
			die();

			break;
		case "1":
			echo "<h2>Import/mise � jour des �l�ves</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");


			// 20090722
			$cpt_saut_lignes=isset($_POST['cpt_saut_lignes']) ? $_POST['cpt_saut_lignes'] : (isset($_GET['cpt_saut_lignes']) ? $_GET['cpt_saut_lignes'] : 0);
			if($cpt_saut_lignes==0) {
				$sql="TRUNCATE TABLE tempo2;";
				info_debug($sql);
				$res0=mysql_query($sql);
			}
			$cpt_saut_lignes_ini=$cpt_saut_lignes;

			/*
			//$dest_file="../temp/".$tempdir."/eleves.xml";
			$dest_file="../temp/".$tempdir."/section_eleves.xml";
			$fp=fopen($dest_file,"r");
			if(!$fp){
				echo "<p>Le XML �l�ve n'a pas l'air pr�sent dans le dossier temporaire.<br />Auriez-vous saut� une �tape???</p>\n";
				require("../lib/footer.inc.php");
				die();
			}
			else{
			*/
				$dest_file="../temp/".$tempdir."/eleves.xml";

				$ele_xml=simplexml_load_file($dest_file);
				if(!$ele_xml) {
					echo "<p style='color:red;'>ECHEC du chargement du fichier avec simpleXML.</p>\n";
					require("../lib/footer.inc.php");
					die();
				}

				$nom_racine=$ele_xml->getName();
				if(strtoupper($nom_racine)!='BEE_ELEVES') {
					echo "<p style='color:red;'>ERREUR: Le fichier XML fourni n'a pas l'air d'�tre un fichier XML El�ves.<br />Sa racine devrait �tre 'BEE_ELEVES'.</p>\n";
					require("../lib/footer.inc.php");
					die();
				}


				// On r�cup�re les ele_id des �l�ves qui sont affect�s dans une classe
				$sql="SELECT ele_id FROM temp_gep_import2 ORDER BY id_tempo";
				info_debug($sql);
				$res_ele_id=mysql_query($sql);
				affiche_debug("count(\$res_ele_id)=".count($res_ele_id)."<br />");

				unset($tab_ele_id);
				$tab_ele_id=array();
				$cpt=0;
				// Pourquoi est-ce que cela ne fonctionne pas en mysql_fetch_object()???
				// TROUV�: C'EST SENSIBLE � LA CASSE: IL FAUDRAIT $lig->ELE_ID
				//while($lig=mysql_fetch_object($res_ele_id)){
				while($lig=mysql_fetch_array($res_ele_id)){
					//$tab_ele_id[$cpt]="$lig->ele_id";
					$tab_ele_id[$cpt]=$lig[0];
					affiche_debug("\$tab_ele_id[$cpt]=$tab_ele_id[$cpt]<br />");
					$cpt++;
				}

				/*
				echo "<p>Lecture du fichier El�ves...<br />\n";
				//echo "<blockquote>\n";
				while(!feof($fp)){
					$ligne[]=fgets($fp,4096);
				}
				fclose($fp);
				//echo "<p>Termin�.</p>\n";
				*/

				echo "<p>";
				//echo "Analyse du fichier pour extraire les informations de la section ELEVES...<br />\n";
				echo "Traitement de la section ELEVES...<br />\n";
				//echo "<blockquote>\n";


				$eleves=array();

				//Compteur �l�ve:
				$i=-1;

				$tab_champs_eleve=array("ID_NATIONAL",
				"ELENOET",
				"NOM",
				"PRENOM",
				"DATE_NAISS",
				"DOUBLEMENT",
				"DATE_SORTIE",
				"CODE_REGIME",
				"DATE_ENTREE",
				"CODE_MOTIF_SORTIE",
				"CODE_SEXE",
				"CODE_COMMUNE_INSEE_NAISS",
				"CODE_PAYS",
				"VILLE_NAISS",
				"MEL"
				);

				$tab_champs_scol_an_dernier=array("CODE_STRUCTURE",
				"CODE_RNE",
				"SIGLE",
				"DENOM_PRINC",
				"DENOM_COMPL",
				"LIGNE1_ADRESSE",
				"LIGNE2_ADRESSE",
				"LIGNE3_ADRESSE",
				"LIGNE4_ADRESSE",
				"BOITE_POSTALE",
				"MEL",
				"TELEPHONE",
				"CODE_COMMUNE_INSEE",
				"LL_COMMUNE_INSEE"
				);

				$avec_scolarite_an_dernier="y";

				$ele_xml=simplexml_load_file($dest_file);
				if(!$ele_xml) {
					echo "<p style='color:red;'>ECHEC du chargement du fichier avec simpleXML.</p>\n";
					require("../lib/footer.inc.php");
					die();
				}

				$nom_racine=$ele_xml->getName();
				if(strtoupper($nom_racine)!='BEE_ELEVES') {
					echo "<p style='color:red;'>ERREUR: Le fichier XML fourni n'a pas l'air d'�tre un fichier XML El�ves.<br />Sa racine devrait �tre 'BEE_ELEVES'.</p>\n";
					require("../lib/footer.inc.php");
					die();
				}

				$objet_eleves=($ele_xml->DONNEES->ELEVES);
				foreach ($objet_eleves->children() as $eleve) {
					$i++;
					//echo "<p><b>El�ve $i</b><br />";
			
					$eleves[$i]=array();
			
					foreach($eleve->attributes() as $key => $value) {
						//echo "$key=".$value."<br />";
			
						$eleves[$i][strtolower($key)]=trim(traite_utf8($value));
					}

					foreach($eleve->children() as $key => $value) {
						if(in_array(strtoupper($key),$tab_champs_eleve)) {
							$eleves[$i][strtolower($key)]=preg_replace('/"/','',trim(traite_utf8($value)));
							//echo "\$eleve->$key=".$value."<br />";
						}

						if(($avec_scolarite_an_dernier=='y')&&(strtoupper($key)=='SCOLARITE_AN_DERNIER')) {
							$eleves[$i]["scolarite_an_dernier"]=array();
			
							foreach($eleve->SCOLARITE_AN_DERNIER->children() as $key2 => $value2) {
								//echo "\$eleve->SCOLARITE_AN_DERNIER->$key2=$value2<br />";
								if(in_array(strtoupper($key2),$tab_champs_scol_an_dernier)) {
									$eleves[$i]["scolarite_an_dernier"][strtolower($key2)]=preg_replace('/"/','',trim(traite_utf8($value2)));
								}
							}
						}
					}

					if(isset($eleves[$i]["date_naiss"])){
						//echo $eleves[$i]["date_naiss"]."<br />\n";
						unset($naissance);
						$naissance=explode("/",$eleves[$i]["date_naiss"]);
						//$eleve_naissance_annee=$naissance[2];
						//$eleve_naissance_mois=$naissance[1];
						//$eleve_naissance_jour=$naissance[0];
						if(isset($naissance[2])){
							$eleve_naissance_annee=$naissance[2];
						}
						else{
							$eleve_naissance_annee="";
						}
						if(isset($naissance[1])){
							$eleve_naissance_mois=$naissance[1];
						}
						else{
							$eleve_naissance_mois="";
						}
						if(isset($naissance[0])){
							$eleve_naissance_jour=$naissance[0];
						}
						else{
							$eleve_naissance_jour="";
						}

						$eleves[$i]["date_naiss"]=$eleve_naissance_annee.$eleve_naissance_mois.$eleve_naissance_jour;
					}

					if($debug_import=='y') {
						echo "<pre style='color:green;'><b>Tableau \$eleves[$i]&nbsp;:</b>";
						print_r($eleves[$i]);
						echo "</pre>";
					}
				}

				flush();

				affiche_debug("count(\$eleves)=".count($eleves)."<br />\n");
				affiche_debug("count(\$tab_ele_id)=".count($tab_ele_id)."<br />\n");

				//===========================
				// A FAIRE: boireaus 20071115
				// Ins�rer ici un tableau comme dans la partie ADRESSES pour simuler une barre de progression
				//===========================

				$stat=0;
				$nb_err=0;
				for($i=0;$i<count($eleves);$i++){
					if(in_array($eleves[$i]['eleve_id'],$tab_ele_id)){
						/*
						if(!isset($eleves[$i]["code_sexe"])){
							$remarques[]="Le sexe de l'�l�ve <a href='#sexe_manquant_".$i."'>".$eleves[$i]["nom"]." ".$eleves[$i]["prenom"]."</a> n'est pas renseign� dans Sconet.";
						}
						*/

						$temoin_date_sortie="n";
						if(isset($eleves[$i]['date_sortie'])) {
							echo $eleves[$i]['prenom']." ".$eleves[$i]['nom']." a quitt� l'�tablissement le ".$eleves[$i]['date_sortie']."<br />\n";

							$tmp_tab_date=explode("/",$eleves[$i]['date_sortie']);
							if(checkdate($tmp_tab_date[1],$tmp_tab_date[0],$tmp_tab_date[2])) {
								$timestamp_sortie=mktime(0,0,0,$tmp_tab_date[1],$tmp_tab_date[0],$tmp_tab_date[2]);
								$timestamp_instant=time();
								if($timestamp_instant>$timestamp_sortie){
									$temoin_date_sortie="y";
								}
							}
						}

						if($temoin_date_sortie=="y") {
							$sql="DELETE FROM temp_gep_import2 WHERE ele_id='".$eleves[$i]['eleve_id']."';";
							info_debug($sql);
							$nettoyage=mysql_query($sql);

							$sql="INSERT INTO tempo2 SET col1='ele_id_eleve_parti', col2='".$eleves[$i]['eleve_id']."';";
							info_debug($sql);
							$insert=mysql_query($sql);
//Eric	
// Enregistrement de l'information de la date de sortie pour l'�l�ve (� partir de son id)					
							$sql="INSERT INTO tempo2 SET col1='".$eleves[$i]['eleve_id']."', col2='".$eleves[$i]['date_sortie']."';";
							info_debug($sql);
							$insert=mysql_query($sql);
// Fin Eric
						}
						else {
							$sql="UPDATE temp_gep_import2 SET ";
							$sql.="elenoet='".$eleves[$i]['elenoet']."', ";
							if(isset($eleves[$i]['id_national'])) {$sql.="elenonat='".$eleves[$i]['id_national']."', ";}
							//$sql.="elenom='".addslashes($eleves[$i]['nom'])."', ";
							$sql.="elenom='".addslashes(strtoupper($eleves[$i]['nom']))."', ";

							//$sql.="elepre='".addslashes($eleves[$i]['prenom'])."', ";
							// On ne retient que le premier pr�nom:
							$tab_prenom = explode(" ",$eleves[$i]['prenom']);
							$sql.="elepre='".addslashes(maj_ini_prenom($tab_prenom[0]))."'";

							//$sql.="elesexe='".sexeMF($eleves[$i]["code_sexe"])."', ";
							if(isset($eleves[$i]["code_sexe"])) {
								$sql.=", elesexe='".sexeMF($eleves[$i]["code_sexe"])."'";
							}
							else {
								echo "<span style='color:red'>Sexe non d�fini dans Sconet pour ".maj_ini_prenom($tab_prenom[0])." ".strtoupper($eleves[$i]['nom'])."</span><br />\n";
								$sql.=", elesexe='M'";
							}
							$sql.=", eledatnais='".$eleves[$i]['date_naiss']."'";
							$sql.=", eledoubl='".ouinon($eleves[$i]["doublement"])."'";
							if(isset($eleves[$i]["scolarite_an_dernier"]["code_rne"])){$sql.=", etocod_ep='".$eleves[$i]["scolarite_an_dernier"]["code_rne"]."'";}
							if(isset($eleves[$i]["code_regime"])){$sql.=", elereg='".$eleves[$i]["code_regime"]."'";}

							//affiche_debug("eleve_id=".$eleves[$i]["eleve_id"]."<br />");
							//affiche_debug("code_pays=".$eleves[$i]["code_pays"]."<br />");
							//affiche_debug("ville_naiss=".$eleves[$i]["ville_naiss"]."<br />");
							//affiche_debug("code_commune_insee_naiss=".$eleves[$i]["code_commune_insee_naiss"]."<br />");

							if((isset($eleves[$i]["code_pays"]))&&($eleves[$i]["code_pays"]!='')&&
								(isset($eleves[$i]["ville_naiss"]))&&($eleves[$i]["ville_naiss"]!='')) {
									$sql.=", lieu_naissance='".$eleves[$i]["code_pays"]."@".addslashes($eleves[$i]["ville_naiss"])."'";
							}
							elseif(isset($eleves[$i]["code_commune_insee_naiss"])) {
								$sql.=", lieu_naissance='".$eleves[$i]["code_commune_insee_naiss"]."'";
							}

							if(isset($eleves[$i]['mel'])) {$sql.=", mel='".$eleves[$i]['mel']."'";}

							//$sql=substr($sql,0,strlen($sql)-2);
							$sql.=" WHERE ele_id='".$eleves[$i]['eleve_id']."';";
							affiche_debug("$sql<br />\n");
							info_debug($sql);
							$res_insert=mysql_query($sql);
							if(!$res_insert){
								echo "Erreur lors de la requ�te $sql<br />\n";
								$nb_err++;
								flush();
							}
							else{
								$stat++;
							}
						}
					}
					/*
					else{
						echo $eleves[$i]['prenom']." ".$eleves[$i]['nom']." n'est pas dans \$tab_ele_id donc pas dans une classe...<br />";
						// On devrait supprimer l'�l�ve de la table l�, non?
					}
					*/
				}

				/*
				if($the_end=="n") {
					$suite="1";
				}
				else {
				*/
					$suite="2";
				//}

				if($nb_err==0) {
					/*
					if($the_end=="n") {
						echo "<p>Parcours d'une tranche de la deuxi�me phase (<i><b>$cpt_saut_lignes_ini</b> -&gt; <b>$cpt_saut_lignes</b></i>)...</p>\n";

						echo "<script type='text/javascript'>
	setTimeout(\"test_stop_bis('$suite','$cpt_saut_lignes')\",1000);
</script>\n";
					}
					else {
					*/
						echo "<p style='bold'>La deuxi�me phase s'est pass�e sans erreur.</p>\n";

						echo "<script type='text/javascript'>
	setTimeout(\"test_stop('$suite')\",1000);
</script>\n";
					//}
				}
				elseif($nb_err==1) {
					echo "<p>$nb_err erreur.</p>\n";
				}
				else{
					echo "<p>$nb_err erreurs</p>\n";
				}
			//}

			echo "<p>$stat enregistrement(s) ont �t� mis � jour dans la table 'temp_gep_import2'.</p>\n";

			//echo "<p><a href='".$_SERVER['PHP_SELF']."?etape=1&amp;step=2'>Suite</a></p>\n";

			// ON SAUTE L'ETAPE 2 QUI CORRESPOND AUX OPTIONS DES ELEVES... NON PRISES EN CHARGE POUR LE MOMENT.
			//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=2'>Suite</a></p>\n";
			//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=3&amp;stop=$stop'>Suite</a></p>\n";
			//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=3&amp;stop=$stop' onClick=\"test_stop_suite('3'); return false;\">Suite</a></p>\n";
			//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=2&amp;stop=$stop' onClick=\"test_stop_suite('2'); return false;\">Suite</a></p>\n";

			/*
			if($the_end=="n") {
				echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=1&amp;cpt_saut_lignes=$cpt_saut_lignes&amp;stop=$stop' onClick=\"test_stop_suite_bis('$suite','$cpt_saut_lignes'); return false;\">Suite</a></p>\n";
			}
			else {
			*/
				echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=2&amp;stop=$stop' onClick=\"test_stop_suite('2'); return false;\">Suite</a></p>\n";
			//}

			require("../lib/footer.inc.php");
			die();

			break;
		case "2":
			echo "<h2>Import/mise � jour des �l�ves</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			// CETTE PHASE D'ANALYSE DES OPTIONS EST A REVOIR: Il faudrait le fichier Nomenclature pour pouvoir proposer les bonnes options lors de l'inscription de nouveaux �l�ves (ou stocker dans une table les correspondances de codes/mati�res).
			//
			// Par contre, on y fait quand m�me des tests pour les �l�ves partis... ne pas squizzer �a si on supprime l'�tape

			/*
			//$dest_file="../temp/".$tempdir."/eleves.xml";
			$dest_file="../temp/".$tempdir."/section_options.xml";
			$fp=fopen($dest_file,"r");
			if(!$fp){
				echo "<p>Le XML �l�ve n'a pas l'air pr�sent dans le dossier temporaire.<br />Auriez-vous saut� une �tape???</p>\n";
				require("../lib/footer.inc.php");
				die();
			}
			else{
			*/

				$dest_file="../temp/".$tempdir."/eleves.xml";
				$ele_xml=simplexml_load_file($dest_file);
				if(!$ele_xml) {
					echo "<p style='color:red;'>ECHEC du chargement du fichier avec simpleXML.</p>\n";
					require("../lib/footer.inc.php");
					die();
				}

				$nom_racine=$ele_xml->getName();
				if(strtoupper($nom_racine)!='BEE_ELEVES') {
					echo "<p style='color:red;'>ERREUR: Le fichier XML fourni n'a pas l'air d'�tre un fichier XML El�ves.<br />Sa racine devrait �tre 'BEE_ELEVES'.</p>\n";
					require("../lib/footer.inc.php");
					die();
				}

				// 20090722
				$cpt_saut_lignes=isset($_POST['cpt_saut_lignes']) ? $_POST['cpt_saut_lignes'] : (isset($_GET['cpt_saut_lignes']) ? $_GET['cpt_saut_lignes'] : 0);
				$the_end="";
				$saut_effectue="n";
				$cpt_saut_lignes_ini=$cpt_saut_lignes;


				// On r�cup�re les ele_id des �l�ves qui sont affect�s dans une classe
				$sql="SELECT ele_id FROM temp_gep_import2 ORDER BY id_tempo";
				info_debug($sql);
				$res_ele_id=mysql_query($sql);
				//echo "count(\$res_ele_id)=".count($res_ele_id)."<br />";

				unset($tab_ele_id);
				$tab_ele_id=array();
				$cpt=0;
				// Pourquoi est-ce que cela ne fonctionne pas en mysql_fetch_object()???
				// TROUV�: C'EST SENSIBLE � LA CASSE: IL FAUDRAIT $lig->ELE_ID
				//while($lig=mysql_fetch_object($res_ele_id)){
				while($lig=mysql_fetch_array($res_ele_id)){
					//$tab_ele_id[$cpt]="$lig->ele_id";
					$tab_ele_id[$cpt]=$lig[0];
					affiche_debug("\$tab_ele_id[$cpt]=$tab_ele_id[$cpt]<br />");
					$cpt++;
				}

				/*
				echo "<p>Lecture du fichier El�ves...<br />\n";
				//echo "<blockquote>\n";
				while(!feof($fp)){
					$ligne[]=fgets($fp,4096);
				}
				fclose($fp);
				//echo "<p>Termin�.</p>\n";
				*/
				flush();

				echo "<p>";
				echo "Analyse du fichier pour extraire les informations de la section OPTIONS...<br />\n";
				//echo "<blockquote>\n";

				// PARTIE <OPTIONS>
				$tab_champs_opt=array("NUM_OPTION","CODE_MODALITE_ELECT","CODE_MATIERE");

				$i=-1;

				// PARTIE <OPTIONS>
				$objet_options=($ele_xml->DONNEES->OPTIONS);
				foreach ($objet_options->children() as $option) {
					// $option est un <OPTION ELEVE_ID="145778" ELENOET="2643">
					//echo "<p><b>Option</b><br />";

					$i++;
					//echo "<p><b>El�ve $i</b><br />";
			
					$eleves[$i]=array();
			
					foreach($option->attributes() as $key => $value) {
						//echo "$key=".$value."<br />";
						$eleves[$i][strtolower($key)]=trim(traite_utf8($value));
					}

					$eleves[$i]["options"]=array();
					$j=0;
					//foreach($option->OPTIONS_ELEVE->children() as $key => $value) {
		
					// $option fait r�f�rence � un �l�ve
					// Les enfants sont des OPTIONS_ELEVE
					foreach($option->children() as $options_eleve) {
						foreach($options_eleve->children() as $key => $value) {
							// Les enfants indiquent NUM_OPTION, CODE_MODALITE_ELECT, CODE_MATIERE
							if(in_array(strtoupper($key),$tab_champs_opt)) {
								$eleves[$i]["options"][$j][strtolower($key)]=preg_replace('/"/','',trim(traite_utf8($value)));
								//echo "\$eleve->$key=".$value."<br />";
								//echo "\$eleves[$i][\"options\"][$j][".strtolower($key)."]=".$value."<br />";
							}
						}
						$j++;
					}
		
					if($debug_import=='y') {
						echo "<pre style='color:green;'><b>Tableau \$eleves[$i]&nbsp;:</b>";
						print_r($eleves[$i]);
						echo "</pre>";
					}
				}

				// Insertion des codes num�riques d'options
				$nb_err=0;
				$stat=0;
				for($i=0;$i<count($eleves);$i++){
					if(in_array($eleves[$i]['eleve_id'],$tab_ele_id)){
						for($j=0;$j<count($eleves[$i]["options"]);$j++){
							$k=$j+1;
							$sql="UPDATE temp_gep_import2 SET ";
							$sql.="eleopt$k='".$eleves[$i]["options"][$j]['code_matiere']."'";
							$sql.=" WHERE ele_id='".$eleves[$i]['eleve_id']."';";
							affiche_debug("$sql<br />\n");
							info_debug($sql);
							$res_update=mysql_query($sql);
							if(!$res_update){
								echo "Erreur lors de la requ�te $sql<br />\n";
								flush();
								$nb_err++;
							}
							else{
								$stat++;
							}
						}
					}
				}


				if($the_end=="n") {
					$suite="2";
				}
				else {
					//$sql="SELECT 1=1 FROM tempo2 WHERE col1='ele_id_eleve_parti';";
					$sql="SELECT 1=1 FROM tempo2 WHERE col1='ele_id_eleve_parti' LIMIT 1;";
					info_debug($sql);
					$test=mysql_query($sql);
					if(mysql_num_rows($test)==0) {$suite="3";} else {$suite="2b";}
				}


				if($nb_err==0) {
					if($the_end=="n") {
						echo "<p>Parcours d'une tranche de la troisi�me phase (<i><b>$cpt_saut_lignes_ini</b> -&gt; <b>$cpt_saut_lignes</b></i>)...</p>\n";
						echo "<script type='text/javascript'>
	setTimeout(\"test_stop_bis('$suite','$cpt_saut_lignes')\",1000);
</script>\n";
					}
					else {
						echo "<p style='bold'>La troisi�me phase s'est pass�e sans erreur.</p>\n";

						echo "<script type='text/javascript'>
	setTimeout(\"test_stop('$suite')\",3000);
</script>\n";
					}
				}
				elseif($nb_err==1) {
					echo "<p>$nb_err erreur.</p>\n";
				}
				else{
					echo "<p>$nb_err erreurs</p>\n";
				}

				echo "<p>$stat option(s) ont �t� mises � jour dans la table 'temp_gep_import2'.</p>\n";

				//echo "<p><a href='".$_SERVER['PHP_SELF']."?etape=1&amp;step=3'>Suite</a></p>\n";
				//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=3&amp;stop=$stop'>Suite</a></p>\n";

				if($the_end=="n") {
					echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=$suite&amp;stop=$stop&amp;cpt_saut_lignes=$cpt_saut_lignes' onClick=\"test_stop_suite_bis('$suite','$cpt_saut_lignes'); return false;\">Suite</a></p>\n";
				}
				else {
					echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=$suite&amp;stop=$stop' onClick=\"test_stop_suite('$suite'); return false;\">Suite</a></p>\n";
				}

				require("../lib/footer.inc.php");
				die();
			//}

			break;


		case "2b":
			echo "<h2>Import/mise � jour des �l�ves</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			echo "<p class='bold'>Contr�le des d�parts d'�l�ves...</p>\n";

			echo "<form action='".$_SERVER['PHP_SELF']."' name='formulaire' method='post'>\n";
			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//==============================

			$sql="SELECT col2 FROM tempo2 WHERE col1='ele_id_eleve_parti';";
			info_debug($sql);
			$res=mysql_query($sql);
			if(mysql_num_rows($res)==0) {
				echo "<p>Aucun �l�ve n'a quitt� l'�tablissement.</p>\n";

				echo "<input type='hidden' name='step' value='3' />\n";
				echo "<p><input type='submit' value='Passer � la suite' /></p>\n";
				echo "</form>\n";
			}
			else {
			    echo "<p>La date de sortie de l'�tablissement not�s dans Sconet est enregistr�e dans GEPI.</p>\n";
				echo "<p>Les �l�ves not�s dans Sconet comme ayant quitt� l'�tablissement peuvent �tre d�sinscrits des classes et enseignements sur les p�riodes futures. On recherche ci-dessous les p�riodes sur lesquelles les �l�ves n'ont pas de note ni quoi que ce soit sur le bulletin.</p>\n";
	
				echo "<p>Cochez les p�riodes pour lesquelles vous souhaitez d�sinscrire le ou les �l�ves qui ont quitt� l'�tablissement et validez en bas de page pour passer � la suite.</p>\n";
	
				echo "<p>";
				echo "<a href=\"javascript:modifcase('coche')\">";
				echo "Cocher tous les �l�ves qu'il est possible de d�sinscrire</a>";
				echo " / ";
				echo "<a href=\"javascript:modifcase('decoche')\">";
				echo "Tout d�cocher</a></p>\n";
	
				$cpt=0;
				while($lig=mysql_fetch_object($res)) {
					$ele_id=$lig->col2;
//Eric traitement de la date de sortie
// Recherche de la date de sortie pour l'�l�ve
					$sql_date_sortie="SELECT col2 FROM tempo2 WHERE col1='$ele_id';";
					$res_date_sortie=mysql_query($sql_date_sortie);
					if(mysql_num_rows($res_date_sortie)>0) {
						$lig_date_sortie=mysql_fetch_object($res_date_sortie); 
						// MAJ de la date de sortie pour l'�l�ve $ele_id
						$sql_maj="UPDATE eleves SET `date_sortie` ='".traite_date_sortie_to_timestamp($lig_date_sortie->col2)."' WHERE `ele_id`='$ele_id';";
						$res_date_sortie=mysql_query($sql_maj);
					}
// Fin Eric					
					$sql="SELECT * FROM eleves WHERE ele_id='$ele_id';";
					info_debug($sql);
					$res_ele=mysql_query($sql);
					if(mysql_num_rows($res_ele)>0) {
						$lig_ele=mysql_fetch_object($res_ele);
	
						echo "<p>".strtoupper($lig_ele->nom)." ".ucfirst(strtolower($lig_ele->prenom))."</p>\n";
						echo "<blockquote>\n";
						// On cherche les p�riodes pour lesquelles l'�l�ve n'a pas de notes ni d'appr�ciations ni dans le carnet de notes ni sur le bulletin.
						$sql="SELECT DISTINCT jec.id_classe, c.classe, jec.periode FROM j_eleves_classes jec, classes c WHERE jec.id_classe=c.id AND jec.login='$lig_ele->login' ORDER BY periode,classe;";
						info_debug($sql);
						$res_class=mysql_query($sql);
						if(mysql_num_rows($res_class)==0){
							echo "Il n'est inscrit dans aucune classe.";
						}
						else {
							$alt=1;
							echo "<table class='boireaus' summary='El�ve n�$ele_id'>\n";
							echo "<tr class='lig$alt'>\n";
							echo "<th>Classe</th>\n";
							echo "<th>P�riode</th>\n";
							echo "<th>Carnet de notes</th>\n";
							echo "<th>Notes sur le bulletin</th>\n";
							echo "<th>Appr�ciations sur le bulletin</th>\n";
							echo "<th>Avis du conseil de classe</th>\n";
							echo "<th>\n";
							echo "D�sinscrire\n";
							echo "</th>\n";
							echo "</tr>\n";
	
							while($lig_clas=mysql_fetch_object($res_class)) {
								$temoin_periode="y";
	
								$alt=$alt*(-1);
								echo "<tr class='lig$alt'>\n";
								echo "<td>$lig_clas->classe</td>\n";
								echo "<td>$lig_clas->periode</td>\n";
								echo "<td>\n";
								$sql="SELECT 1=1 FROM cn_cahier_notes ccn, 
														cn_conteneurs cc, 
														cn_devoirs cd, 
														cn_notes_devoirs cnd WHERE
													ccn.periode='$lig_clas->periode' AND
													ccn.id_cahier_notes=cc.id_racine AND
													cc.id=cd.id_conteneur AND
													cd.id=cnd.id_devoir AND
													cnd.login='$lig_ele->login';";
								info_debug($sql);
								$test1=mysql_query($sql);
								$nb_notes=mysql_num_rows($test1);
								if($nb_notes==0) {
									echo "<span style='color:green;'>Vide</span>";
								}
								else {
									echo "<span style='color:red;'>$nb_notes notes</span>";
									$temoin_periode="n";
								}
								echo "</td>\n";
		
								echo "<td>\n";
								$sql="SELECT 1=1 FROM matieres_notes WHERE periode='$lig_clas->periode' AND login='$lig_ele->login';";
								info_debug($sql);
								$test2=mysql_query($sql);
								$nb_notes_bull=mysql_num_rows($test2);
								if($nb_notes_bull==0) {
									echo "<span style='color:green;'>Vide</span>";
								}
								else {
									echo "<span style='color:red;'>$nb_notes_bull notes</span>";
									$temoin_periode="n";
								}
								echo "</td>\n";
		
								echo "<td>\n";
								$sql="SELECT 1=1 FROM matieres_appreciations WHERE periode='$lig_clas->periode' AND login='$lig_ele->login';";
								info_debug($sql);
								$test3=mysql_query($sql);
								$nb_app_bull=mysql_num_rows($test3);
								if($nb_app_bull==0) {
									echo "<span style='color:green;'>Vide</span>";
								}
								else {
									echo "<span style='color:red;'>$nb_app_bull appr�ciations</span>";
									$temoin_periode="n";
								}
								echo "</td>\n";
	
								echo "<td>\n";
								$sql="SELECT 1=1 FROM avis_conseil_classe WHERE periode='$lig_clas->periode' AND login='$lig_ele->login';";
								info_debug($sql);
								$test4=mysql_query($sql);
								$nb_avis=mysql_num_rows($test4);
								if($nb_avis==0) {
									echo "<span style='color:green;'>Vide</span>";
								}
								else {
									echo "<span style='color:red;'>$nb_avis avis</span>";
									$temoin_periode="n";
								}
								echo "</td>\n";
	
								echo "<td>\n";
								if($temoin_periode=='y') {
									// On propose de d�sinscrire des classes et des groupes
									echo "<input type='checkbox' name='desinscription[]' id='desinscription_$cpt' value=\"$lig_ele->login|$lig_clas->periode\" />\n";
								}
								else {
									echo "&nbsp;";
								}
								echo "</td>\n";
	
								echo "</tr>\n";
	
								$cpt++;
	
							}
							echo "</table>\n";
	
						}
						echo "</blockquote>\n";
		
					}
				}

				echo "<input type='hidden' name='step' value='2c' />\n";
				echo "<p><input type='submit' value='Valider' /></p>\n";

				echo "<script type='text/javascript'>
	function modifcase(mode){
		for(i=0;i<$cpt;i++){
			if(document.getElementById('desinscription_'+i)){
				if(mode=='coche'){
					document.getElementById('desinscription_'+i).checked=true;
				}
				else{
					document.getElementById('desinscription_'+i).checked=false;
				}
			}
		}
	}
</script>\n";
	
				echo "<p><i>NOTES&nbsp;:</i></p>\n";
				echo "<blockquote>\n";
				echo "<p>Les �l�ves not�s dans Sconet comme ayant quitt� l'�tablissement peuvent �tre d�sinscrits des classes et enseignements sur les p�riodes futures.<br />On recherche ci-dessus les p�riodes sur lesquelles les �l�ves n'ont pas de note ni quoi que ce soit sur le bulletin.</p>\n";
				echo "</blockquote>\n";

				echo add_token_field();

				echo "</form>\n";
			}

			//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=3&amp;stop=$stop' onClick=\"test_stop_suite('3'); return false;\">Suite</a></p>\n";

			break;

		case "2c":

			echo "<h2>Import/mise � jour des �l�ves</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			check_token(false);

			// On vide la table dont on va se resservir plus tard:
			$sql="TRUNCATE TABLE tempo2;";
			info_debug($sql);
			$res0=mysql_query($sql);


			if(!isset($_POST['desinscription'])) {
				echo "<p>Aucune d�sinscription n'a �t� valid�e.</p>\n";
			}
			else {
				$desinscription=$_POST['desinscription'];
				echo "<p>";
				for($i=0;$i<count($desinscription);$i++) {
					$tab=explode("|",$desinscription[$i]);
					$ele_login=$tab[0];
					$periode=$tab[1];

					$sql="SELECT * FROM eleves WHERE login='$ele_login';";
					info_debug($sql);
					$res_ele=mysql_query($sql);
					$lig_ele=mysql_fetch_object($res_ele);

					echo "D�sinscription des classes et des enseignements de ".strtoupper($lig_ele->nom)." ".ucfirst(strtolower($lig_ele->prenom))." pour la p�riode $periode: ";

					$sql="DELETE FROM j_eleves_groupes WHERE login='$ele_login' AND periode='$periode';";
					info_debug($sql);
					if(!mysql_query($sql)) {
						echo "<span style='color:red;'>ERREUR lors de la d�sinscription des enseignements</span>";
					}
					else {
						$sql="DELETE FROM j_eleves_classes WHERE login='$ele_login' AND periode='$periode';";
						info_debug($sql);
						if(!mysql_query($sql)) {
							echo "<span style='color:red;'>ERREUR lors de la d�sinscription de la classe</span>";
						}
						else {
							echo "<span style='color:green;'>OK</span>";
						}
					}
					echo "<br />\n";

					$sql="SELECT 1=1 FROM j_eleves_classes WHERE login='$ele_login';";
					$test_encore_dans_une_classe_sur_une_periode=mysql_query($sql);
					if(mysql_num_rows($test_encore_dans_une_classe_sur_une_periode)==0) {
						$sql="DELETE FROM j_eleves_cpe WHERE e_login='$ele_login';";
						if(!mysql_query($sql)) {
							echo "<span style='color:red;'>ERREUR lors de la suppression de la responsabilit� CPE.</span><br />\n";
						}
						$sql="DELETE FROM j_eleves_professeurs WHERE login='$ele_login';";
						if(!mysql_query($sql)) {
							echo "<span style='color:red;'>ERREUR lors de la suppression de la responsabilit� professeur principal.</span><br />\n";
						}
					}
				}
				echo "</p>\n";
			}

			echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=3&amp;stop=$stop' onClick=\"test_stop_suite('3'); return false;\">Passer � l'�tape suivante</a>";
			if($stop=='n') {echo "<br />(<i style='color:red;'>un CLIC est requis pour confirmer que vous avez pris le temps de lire;o</i>)";}
			echo "</p>\n";

			break;

		case "3":
			echo "<h2>Import/mise � jour des �l�ves</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			if(file_exists("../temp/".$tempdir."/eleves.xml")) {
				echo "<p>Suppression de eleves.xml... ";
				if(unlink("../temp/".$tempdir."/eleves.xml")){
					echo "r�ussie.<br />\n";
				}
				else{
					echo "<font color='red'>Echec!</font> V�rifiez les droits d'�criture sur le serveur.<br />\n";
				}

				/*
				echo "Suppression de section_eleves.xml... ";
				if(unlink("../temp/".$tempdir."/section_eleves.xml")){
					echo "r�ussie.<br />\n";
				}
				else{
					echo "<font color='red'>Echec!</font> V�rifiez les droits d'�criture sur le serveur.<br />\n";
				}

				echo "Suppression de section_options.xml... ";
				if(unlink("../temp/".$tempdir."/section_options.xml")){
					echo "r�ussie.<br />\n";
				}
				else{
					echo "<font color='red'>Echec!</font> V�rifiez les droits d'�criture sur le serveur.<br />\n";
				}

				echo "Suppression de section_structures.xml... ";
				if(unlink("../temp/".$tempdir."/section_structures.xml")){
					echo "r�ussie.</p>\n";
				}
				else{
					echo "<font color='red'>Echec!</font> V�rifiez les droits d'�criture sur le serveur.</p>\n";
				}
				*/
			}

			if(!isset($parcours_diff)){
				$sql="TRUNCATE TABLE tempo2;";
				info_debug($sql);
				$res0=mysql_query($sql);

				$sql="SELECT ele_id,naissance FROM eleves";
				info_debug($sql);
				$res1=mysql_query($sql);
				//$nb_eleves=mysql_num_rows($res1);
				//if($nb_eleves==0){
				if(mysql_num_rows($res1)==0){
					echo "<p>La table 'eleves' est vide???<br />Avez-vous proc�d� � l'initialisation de l'ann�e?</p>\n";

					// ON POURRAIT PEUT-�TRE PERMETTRE DE POURSUIVRE... en effectuant les �tapes init_xml2/step2.php et init_xml2/step3.php

					require("../lib/footer.inc.php");
					die();
				}

				// Il faut prendre la table temp_gep_import2 comme r�f�rence pour les diff�rences pour ne pas passer � c�t� des nouveaux �l�ves.
				$sql="SELECT ELE_ID,ELEDATNAIS FROM temp_gep_import2";
				info_debug($sql);
				$res2=mysql_query($sql);
				$nb_eleves=mysql_num_rows($res2);
				if($nb_eleves==0){
					echo "<p>La table 'temp_gep_import2' est vide???</p>\n";
					require("../lib/footer.inc.php");
					die();
				}

				echo "<p>Les ".$nb_eleves." �l�ves vont �tre parcourus par tranches de 20 � la recherche de diff�rences.</p>\n";

				echo "<p>Parcours de la tranche <b>1</b>.</p>\n";
			}
			else{
				echo "<p>Parcours de la tranche <b>$parcours_diff/$nb_parcours</b>.</p>\n";
			}

			flush();

			echo "<form action='".$_SERVER['PHP_SELF']."' name='formulaire' method='post'>\n";
			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//==============================

			/*
			$cpt=0;
			$tabrq=array();
			$cpt2=-1;
			while($lig=mysql_fetch_object($res1)){
				$tab_naissance=explode("-",$lig->naissance);
				$naissance=$tab_naissance[0].$tab_naissance[1].$tab_naissance[2];
				$sql="INSERT INTO tempo2 SET col1='$lig->ele_id', col2='$naissance';";
				$insert=mysql_query($sql);
				if($cpt%20==0){
					$cpt2++;
					$tabrq[$cpt2]="e.ele_id='$lig->ele_id'";
				}
				else{
					$tabrq[$cpt2].=" OR e.ele_id='$lig->ele_id'";
				}
				$cpt++;
			}

			for($i=0;$i<count($tabrq);$i++){
				//echo "<p>\$tabrq[$i]=$tabrq[$i]</p>\n";

				$sql="SELECT e.ele_id FROM eleves e, temp_gep_import2 t, tempo2 t2
								WHERE e.ele_id=t.ELE_ID AND
										e.ele_id=t2.col1 AND
										(
											e.nom!=t.ELENOM OR
											e.prenom!=t.ELEPRE OR
											e.sexe!=t.ELESEXE OR
											t2.col2!=t.ELEDATNAIS OR
											e.no_gep!=t.ELENONAT
										)
										AND ($tabrq[$i])
										";
				//echo "$sql<br />";
				$test=mysql_query($sql);

				echo "$i: mysql_num_rows(\$test)=".mysql_num_rows($test)."<br />";
				flush();
			}
			*/

			if(!isset($parcours_diff)){
				// La date de naissance n'est pas au m�me format dans les tables eleves et temp_gep_import2
				// Une mise au m�me format est op�r�e dans une table interm�diaire.
				$tab_ele_id=array();

				$cpt=0;
				$chaine_nouveaux="";
				//while($lig=mysql_fetch_object($res1)){
				while($lig=mysql_fetch_object($res2)){
					//$tab_naissance=explode("-",$lig->naissance);
					//$naissance=$tab_naissance[0].$tab_naissance[1].$tab_naissance[2];
					$naissance=substr($lig->ELEDATNAIS,0,4)."-".substr($lig->ELEDATNAIS,4,2)."-".substr($lig->ELEDATNAIS,6,2);
					//$sql="INSERT INTO tempo2 SET col1='$lig->ele_id', col2='$naissance';";
					$sql="INSERT INTO tempo2 SET col1='$lig->ELE_ID', col2='$naissance';";
					info_debug($sql);
					$insert=mysql_query($sql);

					// Est-ce un nouvel �l�ve?
					$sql="SELECT 1=1 FROM eleves e, temp_gep_import2 t WHERE e.ele_id=t.ELE_ID AND t.ELE_ID='$lig->ELE_ID'";
					//echo "$sql<br />\n";
					info_debug($sql);
					//$test=mysql_query($sql);
					if(!$test=mysql_query($sql)) {
						echo "<p>Une <span style='color:red;'>erreur</span> s'est produite sur la requ�te&nbsp;:<br /><span style='color:green;'>".$sql."</span><br />\n";
						//Illegal mix of collations
						if(my_eregi("Illegal mix of collations",mysql_error())) {
							echo "Il semble qu'il y ait un probl�me de 'collation' entre les champs 'eleves.ele_id' et 'temp_gep_import2.ele_id'&nbsp;:<br />\n";
							echo "<span style='color:red'>".mysql_error()."</span><br />\n";
							echo "Il faudrait supprimer la table 'temp_gep_import2', renseigner la valeur de 'mysql_collate' dans la table 'setting' en mettant la m�me collation que pour votre champ 'eleves.ele_id'.<br />\n";
							echo "Si par exemple, le champ 'eleves.ele_id' a pour collation 'latin1_general_ci', il faudrait ex�cuter une requ�te du type <span style='color:green;'>INSERT INTO setting SET name='mysql_collate', value='latin1_general_ci';</span> ou si la valeur existe d�j� <span style='color:green;'>UPDATE setting SET value='latin1_general_ci' WHERE name='mysql_collate';</span><br />\n";
						}
						echo "</p>\n";

						require("../lib/footer.inc.php");
						die();
					}

					if(mysql_num_rows($test)==0){
						if($cpt>0){$chaine_nouveaux.=", ";}
						$chaine_nouveaux.=$lig->ELE_ID;
						echo "<input type='hidden' name='tab_ele_id_diff[]' value='$lig->ELE_ID' />\n";
						$cpt++;
					}
					else{
						//$tab_ele_id[]=$lig->ele_id;
						$tab_ele_id[]=$lig->ELE_ID;
					}
				}

				//if($chaine_nouveaux==1){
				if($cpt==1){
					echo "<p>L'ELE_ID d'un nouvel �l�ve a �t� trouv�: $chaine_nouveaux</p>\n";
				}
				//elseif($chaine_nouveaux>1){
				elseif($cpt>1){
					echo "<p>Les ELE_ID de $cpt nouveaux �l�ves ont �t� trouv�s: $chaine_nouveaux</p>\n";
				}

				$nb_parcours=ceil(count($tab_ele_id)/20);
			}
			else{
				if(isset($tab_ele_id_diff)){
					if(count($tab_ele_id_diff)==1){
						echo "<p>L'ELE_ID, pour lequel une ou des diff�rences ont d�j� �t� rep�r�es, est: \n";
					}
					else{
						echo "<p>Le(s) ELE_ID, pour lesquels une ou des diff�rences ont d�j� �t� rep�r�es, sont: \n";
					}
					$chaine_ele_id_diff="";
					for($i=0;$i<count($tab_ele_id_diff);$i++){
						if($i>0){$chaine_ele_id_diff.=", ";}
						$chaine_ele_id_diff.=$tab_ele_id_diff[$i];
						//echo "$i: ";
						echo "<input type='hidden' name='tab_ele_id_diff[]' value='$tab_ele_id_diff[$i]' />\n";
						//echo "<br />\n";
					}
					echo $chaine_ele_id_diff;
					echo "</p>\n";
				}
			}

			echo "<input type='hidden' name='nb_parcours' value='$nb_parcours' />\n";


			// On construit la chaine des 20 ELE_ID retenus pour la requ�te � venir:
			$chaine="";
			//for($i=0;$i<count($tab_ele_id);$i++){
			for($i=0;$i<min(20,count($tab_ele_id));$i++){
				if($i>0){$chaine.=" OR ";}
				$chaine.="e.ele_id='$tab_ele_id[$i]'";

				// On teste s'il s'agit d'un nouvel �l�ve:
				//$sql="SELECT 1=1 FROM";
			}

			//echo "\$chaine=$chaine<br />\n";

			// Liste des ELE_ID restant � parcourir:
			for($i=20;$i<count($tab_ele_id);$i++){
				//echo "$i: ";
				echo "<input type='hidden' name='tab_ele_id[]' value='$tab_ele_id[$i]' />\n";
				//echo "<br />\n";
			}


			/*
			$sql="SELECT e.ele_id FROM eleves e, temp_gep_import2 t, tempo2 t2
							WHERE e.ele_id=t.ELE_ID AND
									e.ele_id=t2.col1 AND
									(
										e.nom!=t.ELENOM OR
										e.prenom!=t.ELEPRE OR
										e.sexe!=t.ELESEXE OR
										t2.col2!=t.ELEDATNAIS OR
										e.no_gep!=t.ELENONAT
									)
									AND ($chaine)
									";
			*/




			/*
			$sql="SELECT e.ele_id FROM eleves e, temp_gep_import2 t, tempo2 t2
							WHERE e.ele_id=t.ELE_ID AND
									e.ele_id=t2.col1 AND
									(
										e.nom!=t.ELENOM OR
										e.prenom!=t.ELEPRE OR
										e.sexe!=t.ELESEXE OR
										e.naissance!=t2.col2 OR
										e.no_gep!=t.ELENONAT
									)
									AND ($chaine)
									";

			echo "$sql<br />\n";
			echo strftime("%H:%M:%S")."<br />\n";
			flush();

			$test=mysql_query($sql);
			echo strftime("%H:%M:%S")."<br />\n";
			flush();

			$cpt=0;
			if(mysql_num_rows($test)>0){
				echo "<p>Une ou des diff�rences ont �t� trouv�es dans la tranche �tudi�e � cette phase.";
				echo "<br />\n";
				echo "En voici le(s) ELE_ID: ";
				//$cpt=0;
				$chaine_ele_id="";
				while($lig=mysql_fetch_object($test)){
					if($cpt>0){$chaine_ele_id.=", ";}
					$chaine_ele_id.=$lig->ele_id;
					echo "<input type='hidden' name='tab_ele_id_diff[]' value='$lig->ele_id' />\n";
					//echo "<br />\n";
					// Pour le cas o� on est dans la derni�re tranche:
					$tab_ele_id_diff[]=$lig->ele_id;
					$cpt++;
				}
				echo $chaine_ele_id;
			}
			*/

			$cpt_tab_ele_id_diff=count($tab_ele_id_diff);
			$cpt=0;
			for($i=0;$i<min(20,count($tab_ele_id));$i++){
				/*
				// AJOUTER SELON LA VALEUR DE ele_lieu_naissance
				// LE TEST DE DIFFERENCE SUR lieu_naissance
				if($ele_lieu_naissance=="y") {
					$sql="SELECT e.ele_id FROM eleves e, temp_gep_import2 t, tempo2 t2
							WHERE e.ele_id=t.ELE_ID AND
									e.ele_id=t2.col1 AND
									(
										e.nom!=t.ELENOM OR
										e.prenom!=t.ELEPRE OR
										e.sexe!=t.ELESEXE OR
										e.naissance!=t2.col2 OR
										e.lieu_naissance!=t.LIEU_NAISSANCE OR
										e.no_gep!=t.ELENONAT
									)
									AND e.ele_id='$tab_ele_id[$i]';";
				}
				else {
					$sql="SELECT e.ele_id FROM eleves e, temp_gep_import2 t, tempo2 t2
							WHERE e.ele_id=t.ELE_ID AND
									e.ele_id=t2.col1 AND
									(
										e.nom!=t.ELENOM OR
										e.prenom!=t.ELEPRE OR
										e.sexe!=t.ELESEXE OR
										e.naissance!=t2.col2 OR
										e.no_gep!=t.ELENONAT
									)
									AND e.ele_id='$tab_ele_id[$i]';";
				}
				*/

				if($ele_lieu_naissance=="y") {
					$sql="SELECT e.ele_id FROM eleves e, temp_gep_import2 t, tempo2 t2
							WHERE e.ele_id=t.ELE_ID AND
									e.ele_id=t2.col1 AND
									(
										e.nom $chaine_collate!= t.ELENOM OR
										e.prenom $chaine_collate!= t.ELEPRE OR
										e.sexe!=t.ELESEXE OR
										e.naissance!=t2.col2 OR
										e.lieu_naissance!=t.LIEU_NAISSANCE OR
										e.no_gep!=t.ELENONAT";
					if((getSettingValue('mode_email_ele')=='')||(getSettingValue('mode_email_ele')=='sconet')) {
						$sql.="						OR e.email!=t.mel";
					}
					$sql.="				)
									AND e.ele_id='$tab_ele_id[$i]';";
				}
				else {
					$sql="SELECT e.ele_id FROM eleves e, temp_gep_import2 t, tempo2 t2
							WHERE e.ele_id=t.ELE_ID AND
									e.ele_id=t2.col1 AND
									(
										e.nom $chaine_collate!= t.ELENOM OR
										e.prenom $chaine_collate!= t.ELEPRE OR
										e.sexe!=t.ELESEXE OR
										e.naissance!=t2.col2 OR
										e.no_gep!=t.ELENONAT";
					if((getSettingValue('mode_email_ele')=='')||(getSettingValue('mode_email_ele')=='sconet')) {
						$sql.="						OR e.email!=t.mel";
					}
					$sql.="									)
									AND e.ele_id='$tab_ele_id[$i]';";
				}
				//if(($tab_ele_id[$i]==352022)||($tab_ele_id[$i]==374123)||($tab_ele_id[$i]==392276)) {echo "$sql<br />";}
				//if($tab_ele_id[$i]=='305034') {echo "$sql<br />";}
				//$reserve_sql=$sql;
				info_debug($sql);
				//echo "$sql<br />";
				$test=mysql_query($sql);

				$temoin_chgt_ancien_etab="n";
				//if ($gepiSchoolRne!="") {
				if (($gepiSchoolRne!="")&&($alert_diff_etab_origine=='y')) {
					// Ancien �tablissement pr�c�demment enregistr�
					$sql="SELECT id_etablissement FROM j_eleves_etablissements jee, eleves e WHERE jee.id_eleve=e.elenoet AND e.elenoet!='' AND e.ele_id='".$tab_ele_id[$i]."';";
					info_debug($sql);
					//echo "$sql<br />";
					$test_ee=mysql_query($sql);
					if(mysql_num_rows($test_ee)>0) {
						$lig_ee=mysql_fetch_object($test_ee);
						$rne_ancien_etab=$lig_ee->id_etablissement;
					}
					else {
						$rne_ancien_etab="";
					}

					// Test de modification de l'ancien �tablissement
					$sql="SELECT ETOCOD_EP FROM temp_gep_import2 t WHERE t.ELE_ID='".$tab_ele_id[$i]."' AND t.ETOCOD_EP!='';";
					info_debug($sql);
					//echo "$sql<br />";
					$test_nouvel_ancien_etb=mysql_query($sql);
					if(mysql_num_rows($test_nouvel_ancien_etb)>0) {
						$lig_nee=mysql_fetch_object($test_nouvel_ancien_etb);
						$rne_ancien_etab2=$lig_nee->ETOCOD_EP;
					}
					else {
						$rne_ancien_etab2="";
					}

					if((strtolower($rne_ancien_etab)!=strtolower($rne_ancien_etab2))&&(strtolower($rne_ancien_etab2)!=strtolower($gepiSchoolRne))) {
						$temoin_chgt_ancien_etab="y";
						//echo "\$temoin_chgt_ancien_etab=$temoin_chgt_ancien_etab<br />";
					}
				}

				//if(mysql_num_rows($test)>0) {
				if((mysql_num_rows($test)>0)||($temoin_chgt_ancien_etab=="y")) {
					if($cpt==0){
						echo "<p>Une ou des diff�rences ont �t� trouv�es dans la tranche �tudi�e � cette phase.";
						echo "<br />\n";
						echo "En voici le(s) ELE_ID: ";
					}
					else{
						echo ", ";
					}
					// $lig->ele_id n'est pas affect� dans le cas o� on n'a rep�r� qu'un changement dans l'�tablissement pr�c�dent.
					//if(mysql_num_rows($test)>0) {$lig=mysql_fetch_object($test);}
					//echo "<input type='hidden' id='c' name='tab_ele_id_diff[]' value='$lig->ele_id' />\n";
					//echo $lig->ele_id;
					echo "<input type='hidden' name='tab_ele_id_diff[]' value='".$tab_ele_id[$i]."' />\n";
					echo $tab_ele_id[$i];

					$tab_ele_id_diff[]=$tab_ele_id[$i];

					//echo "$reserve_sql<br/>";
					//echo "\$temoin_chgt_ancien_etab=$temoin_chgt_ancien_etab<br />";
					flush();
					$cpt++;
					$cpt_tab_ele_id_diff++;
				}
				else{
					// Inutile de tester les diff�rences sur le r�gime si des diff�rences ont d�j� �t� rep�r�es et que l'ELE_ID est d�j� en tab_ele_id_diff[]

					$temoin_test_regime='n';

					if(!isset($tab_ele_id_diff)){
						$temoin_test_regime='y';
					}
					elseif(!in_array($tab_ele_id[$i],$tab_ele_id_diff)){
						$temoin_test_regime='y';
					}

					if($temoin_test_regime=='y'){
						$sql="SELECT jer.regime, t.elereg FROM j_eleves_regime jer, eleves e, temp_gep_import2 t
								WHERE e.ele_id='$tab_ele_id[$i]' AND
										jer.login=e.login AND
										t.ele_id=e.ele_id";
						//=============
						//DEBUG
						if($tab_ele_id[$i]=='782611') {echo "$sql<br />";}
						//=============
						//echo "$sql<br />";
						info_debug($sql);
						$test=mysql_query($sql);
						if(mysql_num_rows($test)>0){
							$lig=mysql_fetch_object($test);
							$tmp_reg=traite_regime_sconet($lig->elereg);
							if("$tmp_reg"!="$lig->regime"){
								// BIZARRE CE $cpt... on n'�crit rien apr�s la virgule...
								if($cpt==0){
									echo "<p>Une ou des diff�rences ont �t� trouv�es dans la tranche �tudi�e � cette phase.";
									echo "<br />\n";
									echo "En voici le(s) ELE_ID: ";
								}
								else{
									echo ", ";
								}

								echo $tab_ele_id[$i];
								echo "<input type='hidden' name='tab_ele_id_diff[]' value='".$tab_ele_id[$i]."' />\n";
								//echo "<br />\n";
								// Pour le cas o� on est dans la derni�re tranche:
								$tab_ele_id_diff[]=$tab_ele_id[$i];
								$cpt++;
								$cpt_tab_ele_id_diff++;
							}
						}




						$temoin_test_doublant='n';

						if(!isset($tab_ele_id_diff)){
							$temoin_test_doublant='y';
						}
						elseif(!in_array($tab_ele_id[$i],$tab_ele_id_diff)){
							$temoin_test_doublant='y';
						}

						if($temoin_test_doublant=='y'){
							$sql="SELECT 1=1 FROM j_eleves_regime jer, eleves e, temp_gep_import2 t
									WHERE e.ele_id='$tab_ele_id[$i]' AND
											jer.login=e.login AND
											t.ele_id=e.ele_id AND
											((jer.doublant='-' AND t.ELEDOUBL='O') OR (jer.doublant!='-' AND t.ELEDOUBL='N'));";
							info_debug($sql);
							//echo "$sql<br />";
							$test=mysql_query($sql);
							if(mysql_num_rows($test)>0){
								if($cpt==0){
									echo "<p>Une ou des diff�rences ont �t� trouv�es dans la tranche �tudi�e � cette phase.";
									echo "<br />\n";
									echo "En voici le(s) ELE_ID: ";
								}
								else{
									echo ", ";
								}

								echo $tab_ele_id[$i];
								echo "<input type='hidden' name='tab_ele_id_diff[]' value='".$tab_ele_id[$i]."' />\n";
								//echo "<br />\n";
								// Pour le cas o� on est dans la derni�re tranche:
								$tab_ele_id_diff[]=$tab_ele_id[$i];
								$cpt++;
								$cpt_tab_ele_id_diff++;
							}
						}

					}
				}
			}


			if($ne_pas_tester_les_changements_de_classes!='y') {
				//echo "<p>Contr�le des changements de classes&nbsp;: ";
				for($i=0;$i<min(20,count($tab_ele_id));$i++){
					//==============================================
					// Recherche des changements de classes
					if(!isset($tab_ele_id_diff)) {$tab_ele_id_diff=array();}
					if(!in_array($tab_ele_id[$i],$tab_ele_id_diff)) {
						$sql="SELECT classe FROM classes c, eleves e, j_eleves_classes jec WHERE c.id=jec.id_classe AND jec.login=e.login AND e.ele_id='$tab_ele_id[$i]' ORDER BY jec.periode DESC LIMIT 1;";
						$test_clas1=mysql_query($sql);
		
						if(mysql_num_rows($test_clas1)>0) {
							$lig_clas1=mysql_fetch_object($test_clas1);
		
							$sql="SELECT DIVCOD FROM temp_gep_import2 t WHERE t.ELE_ID='$tab_ele_id[$i]';";
							$test_clas2=mysql_query($sql);
							if(mysql_num_rows($test_clas2)>0) {
								$lig_clas2=mysql_fetch_object($test_clas2);
		
								if(strtolower($lig_clas1->classe)!=strtolower($lig_clas2->DIVCOD)) {
									if($cpt==0){
										echo "<p>Une ou des diff�rences ont �t� trouv�es dans la tranche �tudi�e � cette phase.";
										echo "<br />\n";
										echo "En voici le(s) ELE_ID: ";
									}
									else{
										echo ", ";
									}

									//echo "<span style='color:green'>";
									echo $tab_ele_id[$i];
									//echo "</span>";
									echo "<input type='hidden' name='tab_ele_id_diff[]' value='".$tab_ele_id[$i]."' />\n";
									//echo "<br />\n";
									// Pour le cas o� on est dans la derni�re tranche:
									$tab_ele_id_diff[]=$tab_ele_id[$i];
									$cpt++;
									$cpt_tab_ele_id_diff++;
								}
							}
						}
					}
					//==============================================
				}
			}

			/*
			for($i=0;$i<min(20,count($tab_ele_id));$i++){

				$temoin_test_regime='n';

				if(!isset($tab_ele_id_diff)){
					$temoin_test_regime='y';
				}
				elseif(!in_array($tab_ele_id[$i],$tab_ele_id_diff)){
					$temoin_test_regime='y';
				}

				if($temoin_test_regime=='y'){
					$sql="SELECT jer.regime, t.elereg FROM j_eleves_regime jer, eleves e, temp_gep_import2 t
							WHERE e.ele_id='$tab_ele_id[$i]' AND
									jer.login=e.login AND
									t.ele_id=e.ele_id";
					$test=mysql_query($sql);
					if(mysql_num_rows($test)>0){
						$lig=mysql_fetch_object($test);
						$tmp_reg=traite_regime_sconet($lig->elereg);
						if("$tmp_reg"!="$lig->regime"){
							// BIZARRE CE $cpt... on n'�crit rien apr�s la virgule...
							if($cpt==0){
								echo "<p>Une ou des diff�rences ont �t� trouv�es dans la tranche �tudi�e � cette phase.";
								echo "<br />\n";
								echo "En voici le(s) ELE_ID: ";
							}
							else{
								echo ", ";
							}
							echo $tab_ele_id[$i];
							echo "<input type='hidden' name='tab_ele_id_diff[]' value='".$tab_ele_id[$i]."' />\n";
							//echo "<br />\n";
							// Pour le cas o� on est dans la derni�re tranche:
							$tab_ele_id_diff[]=$tab_ele_id[$i];
							$cpt++;
						}
					}
				}
			}
			*/



			if(!isset($parcours_diff)){$parcours_diff=1;}
			$parcours_diff++;
			//echo "<input type='hidden' name='parcours_diff' value='$parcours_diff' />\n";

			if(count($tab_ele_id)>20){
				echo "<input type='hidden' name='parcours_diff' value='$parcours_diff' />\n";

				echo "<input type='hidden' name='step' value='3' />\n";
				echo "<p><input type='submit' value='Suite' /></p>\n";

				echo "<script type='text/javascript'>
	/*
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}
	//stop='y'
	if(stop=='n'){
		setTimeout(\"document.forms['formulaire'].submit();\",1000);
	}
	*/
	//test_stop2();
	setTimeout(\"test_stop2()\",3000);
</script>\n";
			}
			else{
				echo "<p>Le parcours des diff�rences est termin�.</p>\n";

				echo "<input type='hidden' name='step' value='4' />\n";
				echo "<p>Parcourir les diff�rences par tranches de <input type='text' name='eff_tranche' value='".min($cpt_tab_ele_id_diff,10)."' size='3' /> sur $cpt_tab_ele_id_diff<br />\n";
				echo "<input type='submit' value='Afficher les diff�rences' /></p>\n";

				// On vide la table dont on va se resservir:
				$sql="TRUNCATE TABLE tempo2;";
				info_debug($sql);
				$res0=mysql_query($sql);
			}
			//echo "<input type='hidden' name='is_posted' value='yes' />\n";
			echo "</form>\n";

			//echo "$i: mysql_num_rows(\$test)=".mysql_num_rows($test)."<br />";


			break;
		case "4":
			echo "<h2>Import/mise � jour des �l�ves</h2>\n";

			$eff_tranche=isset($_POST['eff_tranche']) ? $_POST['eff_tranche'] : 10;
			if(my_ereg("[^0-9]",$eff_tranche)) {$eff_tranche=10;}

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			if(!isset($tab_ele_id_diff)){
				echo "<p>Aucune diff�rence n'a �t� trouv�e.</p>\n";

				echo "<p>Voulez-vous <a href='".$_SERVER['PHP_SELF']."?is_posted=y&amp;step=9'>passer � la page d'importation/mise � jour des responsables</a></p>\n";
			}
			else{
				echo "<p>".count($tab_ele_id_diff)." �l�ve(s) restant � parcourir (<i>nouveau(x) ou modifi�(s)</i>).</p>\n";
				/*
				echo "<p>Liste des diff�rences rep�r�es: <br />\n";
				for($i=0;$i<count($tab_ele_id_diff);$i++){
					echo "\$tab_ele_id_diff[$i]=$tab_ele_id_diff[$i]";
					//echo "<input type='text' name='tab_ele_id_diff[]' value='$tab_ele_id_diff[$i]' />\n";
					echo "<br />\n";
				}
				*/


				// ?????????????????????????????????????????????????????????????????????
				// ?????????????????????????????????????????????????????????????????????
				// ?????????????????????????????????????????????????????????????????????
				// A CE NIVEAU IL FAUDRAIT POUVOIR GERER LE CAS D'UN TROP GRAND NOMBRE DE CORRECTIONS A EFFECTUER...
				// ... LES AFFICHER PAR TRANCHES...
				// APRES VALIDATION, STOCKER DANS UNE TABLE LES ELE_ID POUR LESQUELS temp_gep_import2 DOIT ECRASER eleves ET CEUX CORRESPONDANT A DE NOUVEAUX ELEVES
				// ?????????????????????????????????????????????????????????????????????
				// ?????????????????????????????????????????????????????????????????????
				// ?????????????????????????????????????????????????????????????????????
				//$eff_tranche=min(3,count($tab_ele_id_diff));

				//$eff_tranche=10;


				// Les cases valid�es � l'�tape 4 pr�c�dente:
				$modif=isset($_POST['modif']) ? $_POST['modif'] : NULL;
				$new=isset($_POST['new']) ? $_POST['new'] : NULL;

				if(isset($modif)){
					for($i=0;$i<count($modif);$i++){
						$sql="INSERT INTO tempo2 SET col1='modif', col2='$modif[$i]'";
						info_debug($sql);
						$insert=mysql_query($sql);
					}
				}

				if(isset($new)){
					for($i=0;$i<count($new);$i++){
						$sql="INSERT INTO tempo2 SET col1='new', col2='$new[$i]'";
						info_debug($sql);
						$insert=mysql_query($sql);

						// A CE STADE OU AU SUIVANT, IL FAUDRAIT AUSSI PROPOSER D'AFFECTER LES ELEVES DANS LES CLASSES INDIQUEES... AVEC CHOIX DES PERIODES.
						// ET UNE CASE A COCHER POUR:
						// - METTRE DANS TOUS LES GROUPES OU NON
						// OU ALORS PROPOSER LE TABLEAU eleves_options.php
					}
				}

				// D�doublonnage
				//for($loop=0;$loop<count($tab_ele_id_diff);$loop++) {echo "\$tab_ele_id_diff[$loop]=$tab_ele_id_diff[$loop]<br />";}
				$tab_ele_id_diff=array_unique($tab_ele_id_diff);
				//echo "<p>Apr�s array_unique():<br />";
				//for($loop=0;$loop<count($tab_ele_id_diff);$loop++) {echo "\$tab_ele_id_diff[$loop]=$tab_ele_id_diff[$loop]<br />";}

				/*
				if(!isset($parcours_diff)){
					$nblignes=count($tab_ele_id_diff);
				}
				*/
				$nblignes=min($eff_tranche,count($tab_ele_id_diff));
				//echo "\$nblignes=$nblignes<br />";


				echo "<form action='".$_SERVER['PHP_SELF']."' name='formulaire' method='post'>\n";
				//==============================
				// AJOUT pour tenir compte de l'automatisation ou non:
				echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
				//==============================
				echo "<input type='hidden' name='eff_tranche' value='$eff_tranche' />\n";

				for($i=$eff_tranche;$i<count($tab_ele_id_diff);$i++){
					//echo "$i: ";
					// BIZARRE: Il semble que certains indices puissent ne pas �tre affect�s???
					// Peut-�tre � cause du array_unique() -> certains �l�ves qui ont des modifs de nom, date, INE,... et de r�gime peuvent �tre compt�s deux fois...
					if(isset($tab_ele_id_diff[$i])){
						echo "<input type='hidden' name='tab_ele_id_diff[]' value='$tab_ele_id_diff[$i]' />\n";
					}
					//echo "<br />\n";
				}


				$titre_infobulle="Adresse mail non mise � jour";
				$texte_infobulle="L'adresse mail ne sera pas modifi�e, parce que votre param�trage des adresses �l�ves est&nbsp;: <b>".getSettingValue('mode_email_ele')."</b>";
				$tabdiv_infobulle[]=creer_div_infobulle('chgt_email_non_pris_en_compte',$titre_infobulle,"",$texte_infobulle,"",18,0,'y','y','n','n');


				echo "<p align='center'><input type='submit' value='Valider' /></p>\n";
				//echo "<p align='center'><input type=submit value='Enregistrer les modifications' /></p>\n";

				//echo "<table border='1'>\n";
				//echo "<table class='majimport'>\n";
				echo "<table class='boireaus'>\n";
				//echo "<tr style='background-color: rgb(150, 200, 240);'>\n";
				echo "<tr>\n";
				//echo "<td style='text-align: center; font-weight: bold;'>Enregistrer<br />\n";
				echo "<th>Modifier<br />\n";

				echo "<a href=\"javascript:modifcase('coche')\">";
				echo "<img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>";
				echo " / ";
				echo "<a href=\"javascript:modifcase('decoche')\">";
				echo "<img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>";

				echo "</th>\n";

				echo "<th>Statut</th>\n";
				echo "<th>elenoet</th>\n";
				echo "<th>ele_id</th>\n";
				echo "<th>Nom</th>\n";
				echo "<th>Pr�nom</th>\n";
				echo "<th>Sexe</th>\n";
				echo "<th>Naissance</th>\n";
				echo "<th>Doublement</th>\n";
				echo "<th>N�NAT</th>\n";
				echo "<th>R�gime</th>\n";

				echo "<th>Email</th>\n";

				echo "<th>Classe</th>\n";
				echo "<th>Etablissement d'origine</th>\n";
				echo "</tr>\n";
				$cpt=0;
				$cpt_modif=0;
				$cpt_new=0;
				$alt=1;
				$cpt_chgt_classe=0;
				for($k = 1; ($k < $nblignes+1); $k++){
					$temoin_modif="";
					$temoin_nouveau="";
					//if(!feof($fp)){
						//$ligne = fgets($fp, 4096);

					$w=$k-1;
					$sql="SELECT DISTINCT * FROM temp_gep_import2 WHERE ELE_ID='$tab_ele_id_diff[$w]';";
					info_debug($sql);
					//echo "<tr><td colspan='13'>$sql</td></tr>\n";
					$res1=mysql_query($sql);
					if(mysql_num_rows($res1)==0){
						echo "<tr><td colspan='13' style='text-align:left;'>ele_id=\$tab_ele_id_diff[$w]='$tab_ele_id_diff[$w]' non trouv� dans 'temp_gep_import2' ???</td></tr>\n";
					}
					else{
						$lig=mysql_fetch_object($res1);
						$affiche=array();

						$affiche[0]=traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($lig->ELENOM))));
						// IL FAUDRAIT FAIRE ICI LE MEME TRAITEMENT QUE DANS /init_xml/step3.php POUR LES PRENOMS COMPOS�S ET SAISIE DE PLUSIEURS PR�NOMS...
						$affiche[1]=traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($lig->ELEPRE))));
						$affiche[2]=traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($lig->ELESEXE))));
						$affiche[3]=traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($lig->ELEDATNAIS))));
						$affiche[4]=traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($lig->ELENOET))));
						$affiche[5]=traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($lig->ELE_ID))));
						$affiche[6]=traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($lig->ELEDOUBL))));
						$affiche[7]=traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($lig->ELENONAT))));
						$affiche[8]=traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($lig->ELEREG))));
						$affiche[9]=traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($lig->DIVCOD))));

						//echo "<tr><td colspan='13' style='text-align:left;'>'$lig->ELENOM' et '$affiche[0]' - '$lig->ELEPRE' et '$affiche[1]'</td></tr>\n";

						$affiche[10]=traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($lig->ETOCOD_EP))));

						if($ele_lieu_naissance=="y") {
							$affiche[11]=traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($lig->LIEU_NAISSANCE))));

							/*
							if($affiche[0]=='KILIC') {
								echo "<tr><td colspan='13' style='text-align:left;'>DEBUG: ";
								echo "\$lig->LIEU_NAISSANCE=$lig->LIEU_NAISSANCE<br />";
								echo "corriger_caracteres(dbase_filter(trim(\$lig->LIEU_NAISSANCE)))=".corriger_caracteres(dbase_filter(trim($lig->LIEU_NAISSANCE)))."<br />";
								echo "\$affiche[11]=$affiche[11]<br />";
								echo "</td></tr>\n";
							}
							*/
						}

						$affiche[12]=traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($lig->MEL))));

						//if(trim($ligne)!=""){
							//$tabligne=explode(";",$ligne);
							//$affiche=array();
							//for($i = 0; $i < count($tabchamps); $i++) {
							//	$affiche[$i] = traitement_magic_quotes(corriger_caracteres(dbase_filter(trim($tabligne[$tabindice[$i]]))));
							//}

							//$sql="SELECT * FROM eleves WHERE elenoet='$affiche[4]'";
							$sql="SELECT * FROM eleves WHERE (elenoet='$affiche[4]' OR elenoet='".sprintf("%05d",$affiche[4])."')";
							info_debug($sql);
							//echo "<tr><td colspan='13'>$sql</td></tr>\n";
							$res1=mysql_query($sql);
							if(mysql_num_rows($res1)>0){
								//$sql="UPDATE eleves SET ele_id='$affiche[5]' WHERE elenoet='$affiche[4]'";

								// FAUT-IL FAIRE LES UPDATE SANS CONTR�LE OU SIGNALER LES MODIFS SEULEMENT...
								//$sql="UPDATE eleves SET ele_id='$affiche[5]' WHERE elenoet='$affiche[4]'";

								// STOCKER DANS UN TABLEAU ET AFFICHER SEULEMENT LES MODIFS DANS UN PREMIER TEMPS
								// CASES A COCHER POUR VALIDER


								//$res_update=mysql_query($sql);
								//if(!$res_update){
								//	$erreur++;
								//}

								//$eleves[$cpt]

								$lig_ele=mysql_fetch_object($res1);
								//$tabtmp=explode("/",$affiche[3]);
								// $lig_ele->naissance!=$tabtmp[2]."-".$tabtmp[1]."-".$tabtmp[0])||


								$test_diff_email="n";
								if((getSettingValue('mode_email_ele')=='')||(getSettingValue('mode_email_ele')=='sconet')) {
									$test_diff_email="y";
								}


								$new_date=substr($affiche[3],0,4)."-".substr($affiche[3],4,2)."-".substr($affiche[3],6,2);

								// Des stripslashes() pour les apostrophes dans les noms
								if($ele_lieu_naissance=="y") {
									if((stripslashes($lig_ele->nom)!=stripslashes($affiche[0]))||
									(stripslashes($lig_ele->prenom)!=stripslashes($affiche[1]))||
									($lig_ele->sexe!=$affiche[2])||
									($lig_ele->naissance!=$new_date)||
									//($lig_ele->lieu_naissance!=$affiche[11])||
									($lig_ele->lieu_naissance!=stripslashes($affiche[11]))||
									($lig_ele->no_gep!=$affiche[7])||
									(($test_diff_email=="y")&&($lig_ele->email!=$affiche[12]))
									){
										$temoin_modif='y';
										$cpt_modif++;
									}
									else{
										if($lig_ele->ele_id!=$affiche[5]){
											// GROS PROBLEME SI LES elenoet et ele_id ne sont plus des cl�s primaires
										}
									}
								}
								else {
									if((stripslashes($lig_ele->nom)!=stripslashes($affiche[0]))||
									(stripslashes($lig_ele->prenom)!=stripslashes($affiche[1]))||
									($lig_ele->sexe!=$affiche[2])||
									($lig_ele->naissance!=$new_date)||
									($lig_ele->no_gep!=$affiche[7])||
									(($test_diff_email=="y")&&($lig_ele->email!=$affiche[12]))
									){
										$temoin_modif='y';
										$cpt_modif++;
									}
									else{
										if($lig_ele->ele_id!=$affiche[5]){
											// GROS PROBLEME SI LES elenoet et ele_id ne sont plus des cl�s primaires
										}
									}
								}
								// TESTER DANS j_eleves_regime pour doublant et regime
								//	table -> $affiche[]
								//	ext. -> 0
								//	d/p -> 2

								//	if ($reg_regime == "0") {$regime = "ext.";}
								//	if ($reg_regime == "2") {$regime = "d/p";}
								//	if ($reg_regime == "3") {$regime = "int.";}
								//	if ($reg_regime == "4") {$regime = "i-e";}


								//	R pour doublant -> O
								//	- pour doublant -> N


								$sql="SELECT * FROM j_eleves_regime WHERE (login='$lig_ele->login')";
								info_debug($sql);
								$res2=mysql_query($sql);
								if(mysql_num_rows($res2)>0){
									$tmp_regime="";
									$lig2=mysql_fetch_object($res2);
									//=========================
									// MODIF: boireaus 20071024
									$tmp_new_regime=traite_regime_sconet($affiche[8]);
									//switch($affiche[8]){
									/*
									switch($tmp_new_regime){
										case 0:
											$tmp_regime="ext.";
											break;
										case 2:
											$tmp_regime="d/p";
											break;
										case 3:
											$tmp_regime="int.";
											break;
										case 4:
											$tmp_regime="i-e";
											break;
									}
									*/
									$temoin_pb_regime_inhabituel="n";
									if("$tmp_new_regime"=="ERR"){
										$tmp_regime="d/p";
										$temoin_pb_regime_inhabituel="y";
									}
									else{
										$tmp_regime=$tmp_new_regime;
									}
									//=========================


									if($tmp_regime!=$lig2->regime){
										$temoin_modif='y';
										$cpt_modif++;
									}

									$tmp_doublant="";
									switch($affiche[6]){
										case "O":
											$tmp_doublant="R";
											break;
										case "N":
											$tmp_doublant="-";
											break;
									}
									if($tmp_doublant!=$lig2->doublant){
										$temoin_modif='y';
										$cpt_modif++;
									}
								}
								else{
									// Apparemment, aucune info n'est encore saisie dans j_eleves_regime
								}

								// Rechercher s'il y a un changement de classe?
								$temoin_chgt_classe="n";
								if($ne_pas_tester_les_changements_de_classes!='y') {
									$sql="SELECT c.classe, c.id FROM classes c, eleves e, j_eleves_classes jec WHERE c.id=jec.id_classe AND jec.login=e.login AND e.ele_id='$tab_ele_id_diff[$w]' ORDER BY jec.periode DESC LIMIT 1;";
									$test_clas1=mysql_query($sql);
					
									if(mysql_num_rows($test_clas1)>0) {
										$lig_clas1=mysql_fetch_object($test_clas1);

										if(strtolower($lig_clas1->classe)!=strtolower($lig->DIVCOD)) {
											$temoin_chgt_classe="y";
											$temoin_modif='y';
											$cpt_modif++;
											$cpt_chgt_classe++;
										}
										/*
										$sql="SELECT DIVCOD FROM temp_gep_import2 t WHERE t.ELE_ID='$tab_ele_id[$i]';"
										$test_clas2=mysql_query($sql);
										if(mysql_num_rows($test_clas2)>0) {
											$lig_clas2=mysql_fetch_object($test_clas2);
					
											if(strtolower($lig_clas1->classe)!=strtolower($lig_clas2->DIVCOD)) {
											}
										}
										*/
									}
								}

								// Rechercher s'il y a un changement dans l'�tablissement d'origine
								$sql="SELECT id_etablissement FROM j_eleves_etablissements jee WHERE jee.id_eleve='$lig_ele->elenoet';";
								info_debug($sql);
								$res_ee=mysql_query($sql);
								if(mysql_num_rows($res_ee)>0) {
									$lig_ee=mysql_fetch_object($res_ee);
									$rne_etab_prec=$lig_ee->id_etablissement;
								}
								else {
									$rne_etab_prec="";
								}

								//if(strtolower($affiche[10])!=strtolower($gepiSchoolRne)) {
								if((strtolower($affiche[10])!=strtolower($gepiSchoolRne))&&($alert_diff_etab_origine=='y')) {
									if(strtolower($affiche[10])!=strtolower($rne_etab_prec)) {
										$temoin_modif='y';
										$cpt_modif++;
									}
								}
							}
							else{
								$temoin_nouveau='y';
								$cpt_new++;
								// C'est un nouvel arrivant...

								// AFFICHER ET STOCKER DANS UN TABLEAU...
								// SUR VALIDATION, INS�RER DANS 'eleves' ET PAR LA SUITE AFFECTER DANS DES CLASSES POUR TELLES ET TELLES PERIODES ET COCHER LES OPTIONS POUR TELLES ET TELLES PERIODES.

								// TRANSMETTRE VIA UN FORMULAIRE POUR PROCEDER AUX AJOUTS, ET POUR LES eleves ENCHAINER AVEC LE CHOIX DE CLASSE ET D'OPTIONS
							}

							//echo "<tr><td>$k</td><td>\$temoin_modif=$temoin_modif</td><td>\$temoin_nouveau=$temoin_nouveau</td></tr>";

							if($temoin_modif=='y'){
								//echo "<tr style='background-color:green;'>\n";
								//echo "<tr>\n";
								$alt=$alt*(-1);
								/*
								echo "<tr style='background-color:";
								if($alt==1){
									echo "silver";
								}
								else{
									echo "white";
								}
								echo ";'>\n";
								*/

								if(getSettingValue('mode_email_ele')=='mon_compte') {
									unset($tmp_email_utilisateur_eleve);
									$sql="SELECT email FROM utilisateurs WHERE login='$lig_ele->login' AND statut='eleve';";
									$res_email_utilisateur_eleve=mysql_query($sql);
									if(mysql_num_rows($res_email_utilisateur_eleve)>0) {
										$lig_email_utilisateur_eleve=mysql_fetch_object($res_email_utilisateur_eleve);
										$tmp_email_utilisateur_eleve=$lig_email_utilisateur_eleve->email;
									}
								}

								echo "<tr class='lig$alt'>\n";

								echo "<td style='text-align: center;'>";
								//echo "<input type='checkbox' id='check_".$cpt."' name='modif[]' value='$cpt' />";
								// ELE_ID:
								echo "<input type='checkbox' id='check_".$cpt."' name='modif[]' value='$affiche[5]' />";
								echo "</td>\n";

								//echo "<td style='text-align: center; background-color: lightgreen;'>Modif</td>\n";
								echo "<td class='modif'>Modif</td>\n";

								// ELENOET:
								echo "<td style='text-align: center;'>";
								echo "$affiche[4]";
								echo "<input type='hidden' name='modif_".$cpt."_elenoet' value='$affiche[4]' />\n";
								echo "</td>\n";
								// ELE_ID:
								echo "<td style='text-align: center;'>";
								echo "$affiche[5]";
								echo "<input type='hidden' name='modif_".$cpt."_eleid' value='$affiche[5]' />\n";
								echo "<input type='hidden' name='modif_".$cpt."_login' value='$lig_ele->login' />\n";
								echo "</td>\n";

								//echo "<td style='text-align: center;";
								echo "<td";
								if(stripslashes($lig_ele->nom)!=stripslashes($affiche[0])){
									//echo " background-color:lightgreen;'>";
									echo " class='modif'>";
									if($lig_ele->nom!=''){
										echo stripslashes($lig_ele->nom)." <font color='red'>-&gt;</font>\n";
									}
								}
								else{
									//echo "'>";
									echo ">";
								}
								echo stripslashes($affiche[0]);
								echo "<input type='hidden' name='modif_".$cpt."_nom' value=\"$affiche[0]\" />\n";
								echo "</td>\n";

								//echo "<td style='text-align: center;";
								echo "<td";
								if(stripslashes($lig_ele->prenom)!=stripslashes($affiche[1])){
									//echo " background-color:lightgreen;'>";
									echo " class='modif'>";
									if($lig_ele->prenom!=''){
										echo stripslashes($lig_ele->prenom)." <font color='red'>-&gt;</font>\n";
									}
								}
								else{
									//echo "'>";
									echo ">";
								}
								echo stripslashes($affiche[1]);
								echo "<input type='hidden' name='modif_".$cpt."_prenom' value=\"$affiche[1]\" />\n";
								echo "</td>\n";

								//echo "<td style='text-align: center;";
								echo "<td";
								if($lig_ele->sexe!=$affiche[2]){
									//echo " background-color:lightgreen;'>";
									echo " class='modif'>";
									if($lig_ele->sexe!=''){
										echo "$lig_ele->sexe <font color='red'>-&gt;</font>\n";
									}
								}
								else{
									//echo "'>";
									echo ">";
								}
								echo "$affiche[2]";
								echo "<input type='hidden' name='modif_".$cpt."_sexe' value='$affiche[2]' />\n";
								echo "</td>\n";

								//echo "<td style='text-align: center;";


								if($ele_lieu_naissance=="y") {
									echo "<td";
									//if(($lig_ele->naissance!=$new_date)||($lig_ele->lieu_naissance!=$affiche[11])) {
									if(($lig_ele->naissance!=$new_date)||($lig_ele->lieu_naissance!=stripslashes($affiche[11]))) {
										//echo " background-color:lightgreen;'>";
										echo " class='modif'>";
										if(($lig_ele->naissance!='')||($lig_ele->lieu_naissance!='')) {
											if($lig_ele->naissance!='') {
												echo "$lig_ele->naissance ";
											}
											if($lig_ele->lieu_naissance!='') {
												echo "� ".get_commune($lig_ele->lieu_naissance,1)." ";
											}
											echo "<font color='red'>-&gt;</font>\n";
										}
									}
									else{
										//echo "'>";
										echo ">";
									}
									echo "$new_date";

//echo "_".$ele_lieu_naissance;

									if($affiche[11]!="") {echo " � ".get_commune($affiche[11],1);}
									echo "<input type='hidden' name='modif_".$cpt."_naissance' value='$new_date' />\n";
									echo "<input type='hidden' name='modif_".$cpt."_lieu_naissance' value=\"".stripslashes($affiche[11])."\" />\n";
									echo "</td>\n";
								}
								else {
									echo "<td";
									if($lig_ele->naissance!=$new_date){
										//echo " background-color:lightgreen;'>";
										echo " class='modif'>";
										if($lig_ele->naissance!=''){
											echo "$lig_ele->naissance <font color='red'>-&gt;</font>\n";
										}
									}
									else{
										//echo "'>";
										echo ">";
									}
									echo "$new_date";
									echo "<input type='hidden' name='modif_".$cpt."_naissance' value='$new_date' />\n";
									echo "</td>\n";
								}

								//echo "<td style='text-align: center;'>$affiche[6]</td>\n";
								//echo "<td style='text-align: center;";
								echo "<td";
								//if($tmp_doublant!=$affiche[6]){
								if($tmp_doublant!=$lig2->doublant){
									//echo " background-color:lightgreen;'>";
									echo " class='modif'>";
									if($lig2->doublant!=''){
										echo "$lig2->doublant <font color='red'>-&gt;</font>\n";
									}
								}
								else{
									//echo "'>";
									echo ">";
								}
								//echo "$affiche[6]";
								echo "$tmp_doublant";
								echo "<input type='hidden' name='modif_".$cpt."_doublant' value='$tmp_doublant' />\n";
								echo "</td>\n";


								//echo "<td style='text-align: center;";
								echo "<td";
								if($lig_ele->no_gep!=$affiche[7]){
									//echo " background-color:lightgreen;'>";
									echo " class='modif'>";
									if($lig_ele->no_gep!=''){
										echo "$lig_ele->no_gep <font color='red'>-&gt;</font>\n";
									}
								}
								else{
									//echo "'>";
									echo ">";
								}
								echo "$affiche[7]";
								echo "<input type='hidden' name='modif_".$cpt."_nonat' value='$affiche[7]' />\n";
								echo "</td>\n";

								//echo "<td style='text-align: center;'>$affiche[8]</td>\n";
								//echo "<td style='text-align: center;";
								echo "<td";
								if($tmp_regime!=$lig2->regime){
									//echo " background-color:lightgreen;'>";
									echo " class='modif'>";
									if($lig2->regime!=''){
										echo "$lig2->regime <font color='red'>-&gt;</font>\n";
									}
								}
								else{
									//echo "'>";
									echo ">";
								}
								//echo "$affiche[8]";
								if($temoin_pb_regime_inhabituel=="y"){
									echo "<span style='color:red'>$tmp_regime</span>";
								}
								else{
									echo "$tmp_regime";
								}
								//echo " <span style='color:red'>DEBUG: ".$affiche[8]."</span> ";
								echo "<input type='hidden' name='modif_".$cpt."_regime' value=\"$tmp_regime\" />\n";
								echo "</td>\n";


								echo "<td";
								if(stripslashes($lig_ele->email)!=stripslashes($affiche[12])){
									//echo " background-color:lightgreen;'>";
									echo " class='modif'>";
									if($lig_ele->email!=''){
										echo stripslashes($lig_ele->email)." <font color='red'>-&gt;</font>\n";
									}
								}
								else{
									//echo "'>";
									echo ">";
								}
								echo $affiche[12];
								echo "<input type='hidden' name='modif_".$cpt."_email' value=\"$affiche[12]\" />\n";
								if(isset($tmp_email_utilisateur_eleve)) {
									//if($tmp_email_utilisateur_eleve!=$affiche[12]) {
									if(($tmp_email_utilisateur_eleve!=$affiche[12])&&($alert_diff_mail_ele=='y')) {
										echo "<a href='#' onmouseover=\"afficher_div('chgt_email_non_pris_en_compte','y',-20,20);\"><img src=\"../images/info.png\" alt=\"Information\" title=\"Information\" height=\"29\" width=\"29\" align=\"middle\" border=\"0\" /></a>";

										$info_action_titre="Adresse mail non synchro pour ".remplace_accents(stripslashes($lig_ele->nom)."_".stripslashes($lig_ele->prenom));
										$info_action_texte="Vous devriez mettre � jour Sconet pour <a href='eleves/modify_eleve.php?eleve_login=$lig_ele->login'>".remplace_accents(stripslashes($lig_ele->nom)."_".stripslashes($lig_ele->prenom))."</a><br />L'adresse email renseign�e par l'�l�ve via 'G�rer mon compte' est diff�rente de l'adresse enregistr�e dans Sconet (".$affiche[12].").";
										$info_action_destinataire=array("administrateur","scolarite");
										$info_action_mode="statut";
										enregistre_infos_actions($info_action_titre,$info_action_texte,$info_action_destinataire,$info_action_mode);
									}
								}
								echo "</td>\n";


								// Classe
								//echo "<td style='text-align: center; background-color: white;'>";
								echo "<td style='text-align: center;";
								if($temoin_chgt_classe=="y") {
									echo " background-color: red;";
									echo "'>";
									echo "<a href='../classes/classes_const.php?id_classe=$lig_clas1->id&amp;msg=A_EFFECTUER_Changement_de_classe_vers_".remplace_accents(stripslashes($affiche[9]))."_pour_".remplace_accents(stripslashes($lig_ele->nom)."_".stripslashes($lig_ele->prenom),'all')."' target='_blank'>";
									echo "$lig_clas1->classe -&gt; $affiche[9]";
									echo "</a>";

// RENSEIGNER UNE TABLE AVEC L'INDICATION QU'IL Y AURA UNE MODIF A FAIRE...

									$info_action_titre="Changement de classe � effectuer pour ".remplace_accents(stripslashes($lig_ele->nom)."_".stripslashes($lig_ele->prenom));
									$info_action_texte="Effectuer le <a href='classes/classes_const.php?id_classe=$id_classe_actuelle&amp;msg=".rawurlencode("Le changement de classe de ".remplace_accents(stripslashes($lig_ele->nom)."_".stripslashes($lig_ele->prenom))." a �t� signal� lors de la mise � jour Sconet.")."'>changement de classe</a>";
									$info_action_destinataire="administrateur";
									$info_action_mode="statut";
									enregistre_infos_actions($info_action_titre,$info_action_texte,$info_action_destinataire,$info_action_mode);

								}
								else {
									echo "'>";
									echo "$affiche[9]";
								}
								echo "</td>\n";




								$sql="SELECT id_etablissement FROM j_eleves_etablissements WHERE id_eleve='$lig_ele->elenoet';";
								info_debug($sql);
								$res_ee=mysql_query($sql);
								if(mysql_num_rows($res_ee)) {
									$lig_ee=mysql_fetch_object($res_ee);
									$rne_ancien_etab=$lig_ee->id_etablissement;
								}
								else {
									$rne_ancien_etab="";
								}

								//if(strtolower($affiche[10])!=strtolower($gepiSchoolRne)) {
								if((strtolower($affiche[10])!=strtolower($gepiSchoolRne))&&($alert_diff_etab_origine=='y')) {
									echo "<td";
									if($rne_ancien_etab!=$affiche[10]){
										echo " class='modif'>";
										if($rne_ancien_etab!=''){
											echo "$rne_ancien_etab <font color='red'>-&gt;</font>\n";
										}
									}
									else{
										echo ">";
									}
									echo "$affiche[10]";
									echo "<input type='hidden' name='modif_".$cpt."_id_etab' value='$affiche[10]' />\n";
									echo "</td>\n";
								}
								else {
									echo "<td>";
									//echo "$affiche[10]";
									//echo "<input type='hidden' name='modif_".$cpt."_id_etab' value='$affiche[10]' />\n";
									//echo "&nbsp;";
									echo $rne_ancien_etab;
									//echo "<input type='hidden' name='modif_".$cpt."_id_etab' value='' />\n";
									echo "</td>\n";
								}


								echo "</tr>\n";
							}
							elseif($temoin_nouveau=='y'){
								//echo "<tr style='background-color:yellow;'>\n";
								//echo "<tr>\n";
								$alt=$alt*(-1);
								/*
								echo "<tr style='background-color:";
								if($alt==1){
									echo "silver";
								}
								else{
									echo "white";
								}
								echo ";'>\n";
								*/
								echo "<tr class='lig$alt'>\n";

								//echo "<td style='text-align: center;'><input type='checkbox' id='check_".$cpt."' name='new[]' value='$cpt' /></td>\n";
								echo "<td style='text-align: center;'><input type='checkbox' id='check_".$cpt."' name='new[]' value='$affiche[5]' /></td>\n";

								//echo "<td style='text-align: center; background-color: rgb(150, 200, 240);'>Nouveau</td>\n";
								echo "<td class='nouveau'>Nouveau</td>\n";


								echo "<td style='text-align: center;'>";
								echo "$affiche[4]";
								echo "<input type='hidden' name='new_".$cpt."_elenoet' value='$affiche[4]' />\n";
								echo "</td>\n";

								echo "<td style='text-align: center;'>";
								echo "$affiche[5]";
								echo "<input type='hidden' name='new_".$cpt."_eleid' value='$affiche[5]' />\n";
								echo "</td>\n";

								echo "<td style='text-align: center;'>";
								echo stripslashes($affiche[0]);
								echo "<input type='hidden' name='new_".$cpt."_nom' value=\"$affiche[0]\" />\n";
								echo "</td>\n";

								echo "<td style='text-align: center;'>";
								echo stripslashes($affiche[1]);
								echo "<input type='hidden' name='new_".$cpt."_prenom' value=\"$affiche[1]\" />\n";
								echo "</td>\n";

								echo "<td style='text-align: center;'>";
								echo "$affiche[2]";
								echo "<input type='hidden' name='new_".$cpt."_sexe' value='$affiche[2]' />\n";
								echo "</td>\n";

								$new_date=substr($affiche[3],0,4)."-".substr($affiche[3],4,2)."-".substr($affiche[3],6,2);
								echo "<td style='text-align: center;'>";
								echo "$new_date";
								if($ele_lieu_naissance=="y") {
									echo " � ".get_commune($affiche[11],1);
									echo "<input type='hidden' name='new_".$cpt."_lieu_naissance' value=\"".stripslashes($affiche[11])."\" />\n";
								}
								echo "<input type='hidden' name='new_".$cpt."_naissance' value='$new_date' />\n";
								echo "</td>\n";


								$tmp_doublant="";
								switch($affiche[6]){
									case "O":
										$tmp_doublant="R";
										break;
									case "N":
										$tmp_doublant="-";
										break;
								}

								echo "<td style='text-align: center;'>";
								echo "$tmp_doublant";
								echo "<input type='hidden' name='new_".$cpt."_doublant' value='$tmp_doublant' />\n";
								echo "</td>\n";

								echo "<td style='text-align: center;'>";
								echo "$affiche[7]";
								echo "<input type='hidden' name='new_".$cpt."_nonat' value='$affiche[7]' />\n";
								echo "</td>\n";


								$tmp_regime="";
								//=========================
								// MODIF: boireaus 20071024
								$tmp_new_regime=traite_regime_sconet($affiche[8]);
								//switch($affiche[8]){
								/*
								switch($tmp_new_regime){
									case 0:
										$tmp_regime="ext.";
										break;
									case 2:
										$tmp_regime="d/p";
										break;
									case 3:
										$tmp_regime="int.";
										break;
									case 4:
										$tmp_regime="i-e";
										break;
								}
								*/
								if("$tmp_new_regime"=="ERR"){
									$tmp_regime="d/p";

									echo "<td style='text-align: center;'>\n";
									echo "<span style='color:red'>$tmp_regime</span>";
									//echo " <span style='color:red'>DEBUG: ".$affiche[8]."</span> ";
									echo "<input type='hidden' name='new_".$cpt."_regime' value='$tmp_regime' />\n";
								}
								else{
									$tmp_regime=$tmp_new_regime;

									echo "<td style='text-align: center;'>\n";
									echo "$tmp_regime";
									//echo " <span style='color:red'>DEBUG: ".$affiche[8]."</span> ";
									echo "<input type='hidden' name='new_".$cpt."_regime' value='$tmp_regime' />\n";
								}
								//=========================

								echo "</td>\n";

								echo "<td style='text-align: center;'>";
								echo "$affiche[12]";
								echo "</td>\n";

								echo "<td style='text-align: center;'>";
								echo "$affiche[9]";
								echo "</td>\n";


								echo "<td style='text-align: center;'>";
								if(strtolower($affiche[10])!=strtolower($gepiSchoolRne)) {
									echo "$affiche[10]";
									echo "<input type='hidden' name='new_".$cpt."_id_etab' value='$affiche[10]' />\n";
								}
								else {
									echo "&nbsp;";
									//echo "<input type='hidden' name='new_".$cpt."_id_etab' value='' />\n";
								}
								echo "</td>\n";


								echo "</tr>\n";
							}

							$cpt++;
						//}
					}
				}
				echo "</table>\n";
				//echo "<p>On compte $cpt_modif champs modifi�s et $cpt_new nouveaux �l�ves.</p>\n";
				//fclose($fp);

				if($cpt_chgt_classe>0) {
					echo "<p><span style='font-weight:bold; color:red;'>Attention</span>&nbsp;: Un changement de classe au moins a �t� rep�r�.<br />Pour effectuer le changement de classe, cliquez sur la cellule rouge correspondante.</p>\n";
				}

				echo "<script type='text/javascript'>
	function modifcase(mode){
		for(i=0;i<$cpt;i++){
			if(document.getElementById('check_'+i)){
				if(mode=='coche'){
					document.getElementById('check_'+i).checked=true;
				}
				else{
					document.getElementById('check_'+i).checked=false;
				}
			}
		}
	}
</script>\n";

				//echo "<input type='hidden' name='cpt' value='$cpt' />\n";
				if(count($tab_ele_id_diff)>$eff_tranche){
					echo "<input type='hidden' name='step' value='4' />\n";
				}
				else{
					echo "<input type='hidden' name='step' value='5' />\n";
				}

				echo "<p align='center'><input type=submit value='Valider' /></p>\n";
				//echo "<p align='center'><input type=submit value='Enregistrer les modifications' /></p>\n";

				echo add_token_field();

				echo "</form>\n";
			}

			break;
		case "5":
			echo "<h2>Import/mise � jour des �l�ves</h2>\n";

			check_token(false);

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			$modif=isset($_POST['modif']) ? $_POST['modif'] : NULL;
			$new=isset($_POST['new']) ? $_POST['new'] : NULL;

			// Ceux valid�s dans la derni�re phase:
			if(isset($modif)){
				for($i=0;$i<count($modif);$i++){
					$sql="INSERT INTO tempo2 SET col1='modif', col2='$modif[$i]'";
					info_debug($sql);
					$insert=mysql_query($sql);
				}
			}

			if(isset($new)){
				for($i=0;$i<count($new);$i++){
					$sql="INSERT INTO tempo2 SET col1='new', col2='$new[$i]'";
					info_debug($sql);
					$insert=mysql_query($sql);
				}
			}
			// Si on rafraichit la page, les derniers ins�r�s le sont � plusieurs reprises.
			// Les DISTINCT des requ�tes qui suivent permettent de ne pas tenir compte des doublons.


			// CHANGEMENT DE MODE DE FONCTIONNEMENT:
			// On recherche dans tempo2 la liste des ELE_ID correspondant � modif ou new
			// Et on remplit/met � jour 'eleves' avec les enregistrements correspondants de temp_gep_import2

			$erreur=0;
			$cpt=0;
			$sql="SELECT DISTINCT t.* FROM temp_gep_import2 t, tempo2 t2 WHERE t.ELE_ID=t2.col2 AND t2.col1='modif'";
			info_debug($sql);
			$res_modif=mysql_query($sql);
			if(mysql_num_rows($res_modif)>0){
				echo "<p>Mise � jour des informations pour ";
				while($lig=mysql_fetch_object($res_modif)){
					//echo "Modif: $lig->ELE_ID : $lig->ELENOM $lig->ELEPRE<br />\n";

					if($cpt>0){echo ", ";}

					$naissance=substr($lig->ELEDATNAIS,0,4)."-".substr($lig->ELEDATNAIS,4,2)."-".substr($lig->ELEDATNAIS,6,2);

					/*
					switch($lig->ELEREG){
						case 0:
							$regime="ext.";
							break;
						case 2:
							$regime="d/p";
							break;
						case 3:
							$regime="int.";
							break;
						case 4:
							$regime="i-e";
							break;
					}
					*/
					$regime=traite_regime_sconet($lig->ELEREG);
					/*
					if("$regime"=="ERR"){
						$regime="d/p";
					}
					*/

					switch($lig->ELEDOUBL){
						case "O":
							$doublant="R";
							break;
						case "N":
							$doublant="-";
							break;
					}

					$sql="UPDATE eleves SET nom='".addslashes($lig->ELENOM)."',
											prenom='".addslashes($lig->ELEPRE)."',
											sexe='".$lig->ELESEXE."',
											naissance='".$naissance."',
											no_gep='".$lig->ELENONAT."'";

					if($ele_lieu_naissance=="y") {
						$sql.=", lieu_naissance='".addslashes($lig->LIEU_NAISSANCE)."'";
					}

					if(getSettingValue('mode_email_ele')!="mon_compte") {
						$sql.=", email='".addslashes($lig->MEL)."'";
					}

					// Je ne pense pas qu'on puisse corriger un ELENOET manquant...
					// Si on fait des imports avec Sconet, l'ELENOET n'est pas vide.
					// Et l'interface ne permet pas actuellement de saisir/corriger un ELE_ID
					$sql_tmp="SELECT elenoet,login FROM eleves WHERE ele_id='$lig->ELE_ID';";
					info_debug($sql);
					//echo "$sql_tmp<br />";
					$res_tmp=mysql_query($sql_tmp);
					if(mysql_num_rows($res_tmp)>0) {
						// L'�l�ve a �t� trouv� dans la table 'eleves' d'apr�s son ELE_ID
						// L'ELE_ID �tait correctement renseign�
						$lig_tmp=mysql_fetch_object($res_tmp);
						if($lig_tmp->elenoet==""){
							$sql.=", elenoet='".$lig->ELENOET."'";
						}
						$login_eleve=$lig_tmp->login;

						$sql.=" WHERE ele_id='".$lig->ELE_ID."';";
						//echo "============<br />";
						//echo "$sql<br />";
						info_debug($sql);
						$update=mysql_query($sql);
						if($update){
							echo "\n<span style='color:darkgreen;'>";

							if(getSettingValue('mode_email_ele')!="mon_compte") {
								$sql="UPDATE utilisateurs SET email='$lig->MEL' WHERE statut='eleve' AND login IN (SELECT login FROM eleves WHERE ele_id='$lig->ELE_ID');";
								$update_email_utilisateur_eleve=mysql_query($sql);
							}

						}
						else{
							echo "\n<span style='color:red;'>";
							$erreur++;
						}
						//echo "$sql<br />\n";
						echo "$lig->ELEPRE $lig->ELENOM";
						echo "</span>";

						$sql="UPDATE j_eleves_regime SET doublant='$doublant'";
						if("$regime"!="ERR"){
							$sql.=", regime='$regime'";
						}
						$sql.=" WHERE (login='$login_eleve');";
						info_debug($sql);
						$res2=mysql_query($sql);
						if(!$res2){
							echo " <span style='color:red;'>(*)</span>";
							$erreur++;
						}
					}
					else {
						// L'�l�ve n'a pas �t� trouv� dans la table 'eleves' d'apr�s son ELE_ID
						// L'ELE_ID n'est pas correctement renseign� dans 'eleves'
						// La reconnaissance de 'modif' a d� se faire sur l'ELENOET
						$sql_tmp="SELECT ele_id,login FROM eleves WHERE elenoet='$lig->ELENOET';";
						//echo "$sql_tmp<br />";
						info_debug($sql);
						$res_tmp=mysql_query($sql_tmp);
						if(mysql_num_rows($res_tmp)>0) {
							$lig_tmp=mysql_fetch_object($res_tmp);
							/*
							if($lig_tmp->elenoet==""){
								$sql.=", elenoet='".$lig->ELENOET."'";
							}
							*/
							$old_ele_id=$lig_tmp->ele_id;
							$sql.=", ele_id='".$lig->ELE_ID."'";

							if(getSettingValue('mode_email_ele')!="mon_compte") {
								$sql.=", email='".addslashes($lig->$MEL)."'";
							}

							$login_eleve=$lig_tmp->login;

							$sql.=" WHERE elenoet='".$lig->ELENOET."';";
							//echo "============<br />";
							//echo "$sql<br />";
							info_debug($sql);
							$update=mysql_query($sql);
							if($update){
								echo "\n<span style='color:darkgreen;'>";

								if(getSettingValue('mode_email_ele')!="mon_compte") {
									$sql="UPDATE utilisateurs SET email='$lig->MEL' WHERE statut='eleve' AND login IN (SELECT login FROM eleves WHERE ele_id='$lig->ELE_ID');";
									$update_email_utilisateur_eleve=mysql_query($sql);
								}
							}
							else{
								echo "\n<span style='color:red;'>";
								$erreur++;
							}
							//echo "$sql<br />\n";
							echo "$lig->ELEPRE $lig->ELENOM";
							echo "</span>";

							$sql="UPDATE j_eleves_regime SET doublant='$doublant'";
							if("$regime"!="ERR"){
								$sql.=", regime='$regime'";
							}
							$sql.=" WHERE (login='$login_eleve');";
							info_debug($sql);
							$res2=mysql_query($sql);
							if(!$res2){
								echo " <span style='color:red;'>(*)</span>";
								$erreur++;
							}

							$sql="UPDATE responsables2 SET ele_id='$lig->ELE_ID' WHERE ele_id='$old_ele_id';";
							info_debug($sql);
							$correction2=mysql_query($sql);
							if(!$correction2){
								echo " <span style='color:plum;'>(*)</span>";
								$erreur++;
							}

						}
						else {
							// On ne devrait pas arriver l�.
							// Si la reconnaissance de modif a �t� r�alis�e, c'est qu'on avait une correspondance soit sur l'ELE_ID soit sur l'ELENOET
							echo "\n<span style='color:purple;'>";
							$erreur++;
							echo "$lig->ELEPRE $lig->ELENOM";
							echo "</span>";
						}
					}

					if(strtolower($lig->ETOCOD_EP)!=strtolower($gepiSchoolRne)) {
						$sql="SELECT 1=1 FROM j_eleves_etablissements WHERE id_eleve='$lig->ELENOET';";
						info_debug($sql);
						$test_ee=mysql_query($sql);
						//if(mysql_num_rows($test_ee)>0) {
						if((mysql_num_rows($test_ee)>0)&&($alert_diff_etab_origine=='y')) {
							if($lig->ETOCOD_EP!="") {
								$sql="UPDATE j_eleves_etablissements SET id_etablissement='$lig->ETOCOD_EP' WHERE id_eleve='$lig->ELENOET';";
								info_debug($sql);
								$update_ee=mysql_query($sql);
							}
							else {
								$sql="DELETE FROM j_eleves_etablissements WHERE id_eleve='$lig->ELENOET';";
								info_debug($sql);
								$del_ee=mysql_query($sql);
							}
						}
						else {
							$sql="INSERT INTO j_eleves_etablissements SET id_eleve='$lig->ELENOET', id_etablissement='$lig->ETOCOD_EP';";
							info_debug($sql);
							$insert_ee=mysql_query($sql);
						}
					}

					$cpt++;
				}
				echo "</p>\n";
			}

			$cpt=0;
			$sql="SELECT DISTINCT t.* FROM temp_gep_import2 t, tempo2 t2 WHERE t.ELE_ID=t2.col2 AND t2.col1='new'";
			info_debug($sql);
			$res_new=mysql_query($sql);
			if(mysql_num_rows($res_new)>0){

				$sql="DROP TABLE IF EXISTS temp_ele_classe;";
				info_debug($sql);
				$nettoyage = mysql_query($sql);

				$sql="CREATE TABLE IF NOT EXISTS temp_ele_classe (
				`ele_id` varchar(40) $chaine_mysql_collate NOT NULL default '',
				`divcod` varchar(40) $chaine_mysql_collate NOT NULL default ''
				);";
				info_debug($sql);
				$create_table = mysql_query($sql);

				$sql="TRUNCATE TABLE temp_ele_classe;";
				info_debug($sql);
				$vide_table = mysql_query($sql);

				//echo "<p>\$auth_sso=$auth_sso</p>";
				if($auth_sso=='lcs') {
					// On se connecte au LDAP
					$ds = connect_ldap($lcs_ldap_host,$lcs_ldap_port,"","");
					//echo "<p>CONNEXION AU LDAP</p>";
				}

				/*
				if(($auth_sso!='')&&($auth_sso!='lcs')) {
					// Probl�me... si on fait �a on bloque �ventuellement des coll�gues qui ne donnaient pas l'acc�s aux �l�ves mais avaient une auth sso
					echo "<p style='color:red'>Vous �tes auth_sso=$auth_sso<br />Il faut ajouter manuellement les comptes �l�ves avec le login appropri� (<i>celui correspondant � votre authentification</i>) et le bon num�ro gep (<i>elenoet</i>)&nbsp;:<br />\n";

					while($lig=mysql_fetch_object($res_new)){
						// ON VERIFIE QU'ON N'A PAS DEJA UN ELEVE DE MEME ele_id DANS eleves
						// CELA PEUT ARRIVER SI ON JOUE AVEC F5
						$sql="SELECT 1=1 FROM eleves WHERE ele_id='$lig->ELE_ID'";
						info_debug($sql);
						$test=mysql_query($sql);
						if(mysql_num_rows($test)==0){
							if($cpt>0){echo ", ";}
							echo addslashes($lig->ELENOM)." ".addslashes($lig->ELEPRE);
							$cpt++;
						}
					}

					echo "</p>\n";
				}
				else {
				*/
					echo "<p>Ajout de ";
					while($lig=mysql_fetch_object($res_new)){
						// ON VERIFIE QU'ON N'A PAS DEJA UN ELEVE DE MEME ele_id DANS eleves
						// CELA PEUT ARRIVER SI ON JOUE AVEC F5
						$sql="SELECT 1=1 FROM eleves WHERE ele_id='$lig->ELE_ID'";
						info_debug($sql);
						$test=mysql_query($sql);
						if(mysql_num_rows($test)==0){
							//echo "New: $lig->ELE_ID : $lig->ELENOM $lig->ELEPRE<br />";
	
							if($cpt>0){echo ", ";}
	
							$naissance=substr($lig->ELEDATNAIS,0,4)."-".substr($lig->ELEDATNAIS,4,2)."-".substr($lig->ELEDATNAIS,6,2);
	
							/*
							switch($lig->ELEREG){
								case 0:
									$regime="ext.";
									break;
								case 2:
									$regime="d/p";
									break;
								case 3:
									$regime="int.";
									break;
								case 4:
									$regime="i-e";
									break;
							}
							*/
							$regime=traite_regime_sconet($lig->ELEREG);
							// Si le r�gime est en erreur, on impose 'd/p' comme le moins mauvais choix dans ce cas
							if("$regime"=="ERR"){
								$regime="d/p";
							}
	
							switch($lig->ELEDOUBL){
								case "O":
									$doublant="R";
									break;
								case "N":
									$doublant="-";
									break;
							}

							// Initialisation
							$login_eleve="";

							if($auth_sso=='lcs') {

								// LDAP attribute
								$ldap_people_attr = array(
								"uid",               // login
								"cn",                // Prenom  Nom
								"sn",               // Nom
								"givenname",            // Pseudo
								"mail",              // Mail
								"homedirectory",           // Home directory personnal web space
								"description",
								"loginshell",
								"gecos",             // Date de naissance,Sexe (F/M),
								"employeenumber"    // identifiant gep
								);

								//$filtre = "(employeenumber=".$lig->ELENOET.")";
								$filtre="(|(employeenumber=".$lig->ELENOET.")(employeenumber=".sprintf("%05d",$lig->ELENOET)."))";
								$result= ldap_search ($ds, $lcs_ldap_people_dn, $filtre);
								if ($result) {
									$info = @ldap_get_entries( $ds, $result );
									if($info[0]["uid"]["count"]==0) {
										echo "<span style='color:red;'>Aucun enregistrement n'a �t� trouv� dans le LDAP pour l'�l�ve ".$lig->ELENOM." ".$lig->ELEPRE."</span><br />\n";
										$erreur++;
									}
									if($info[0]["uid"]["count"]>1) {
										echo "<span style='color:red;'>Plusieurs enregistrements ont �t� trouv�s dans le LDAP pour l'�l�ve ".$lig->ELENOM." ".$lig->ELEPRE." avec l'employeenumber '$lig->ELENOET'.<br />C'est une anomalie.</span><br />\n";
										$erreur++;
									}
									elseif($info[0]["uid"]["count"]==1) {
										$login_eleve=$info[0]["uid"][0];
	
										/*
										for ( $u = 0; $u < $info[0]["uid"]["count"] ; $u++ ) {
											$uid = $info[0]["memberuid"][$u] ;
											if (trim($uid) !="") {
												$eleve_de[$current_classe_id]=$uid;
												// Extraction des infos sur l'�l�ve :
												$result2 = @ldap_read ( $ds, "uid=".$uid.",".$lcs_ldap_people_dn, "(objectclass=posixAccount)", $ldap_people_attr );
												if ($result2) {
													$info2 = @ldap_get_entries ( $ds, $result2 );
													if ( $info2["count"]) {
														// Traitement du champ gecos pour extraction de date de naissance, sexe
														$gecos = $info2[0]["gecos"][0];
														$tmp = split ("[\,\]",$info2[0]["gecos"][0],4);
														$ret_people = array (
														"uid"         => $info2[0]["uid"][0],
														"nom"         => stripslashes( utf8_decode($info2[0]["sn"][0]) ),
														"fullname"        => stripslashes( utf8_decode($info2[0]["cn"][0]) ),
														"pseudo"      => utf8_decode($info2[0]["givenname"][0]),
														"email"       => $info2[0]["mail"][0],
														"homedirectory"   => $info2[0]["homedirectory"][0],
														"description" => utf8_decode($info2[0]["description"][0]),
														"shell"           => $info2[0]["loginshell"][0],
														"sexe"            => $tmp[2],
														"naissance"       => $tmp[1],
														"no_gep"          => $info2[0]["employeenumber"][0]
														);
														$long = strlen($ret_people["fullname"]) - strlen($ret_people["nom"]);
														$prenom = substr($ret_people["fullname"], 0, $long) ;
							
							
														$add = add_eleve($uid,$ret_people["nom"],$prenom,$tmp[2],$tmp[1],$ret_people["no_gep"]);
														$get_periode_num = mysql_result(mysql_query("SELECT count(*) FROM periodes WHERE (id_classe = '" . $current_classe_id . "')"), 0);
														$check = mysql_result(mysql_query("SELECT count(*) FROM j_eleves_classes WHERE (login = '" . $uid . "')"), 0);
														if ($check > 0)
															$del = mysql_query("DELETE from j_eleves_classes WHERE login = '" . $uid . "'");
														for ($k=1;$k<$get_periode_num+1;$k++) {
															$res = mysql_query("INSERT into j_eleves_classes SET login = '" . $uid . "', id_classe = '" . $current_classe_id . "', periode = '" . $k . "'");
														}
														$check = mysql_result(mysql_query("SELECT count(*) FROM j_eleves_regime WHERE (login = '" . $uid . "')"), 0);
														if ($check > 0)
															$del = mysql_query("DELETE from j_eleves_regime WHERE login = '" . $uid . "'");
														$res = mysql_query("INSERT into j_eleves_regime SET login = '" . $uid . "',
														regime  = 'd/p',
														doublant  = '-'");
													}
													@ldap_free_result ( $result2 );
												}
												$date_naissance = substr($tmp[1],6,2)."-".substr($tmp[1],4,2)."-".substr($tmp[1],0,4) ;
												echo "<tr><td>".$current_classe."</td><td>".$uid."</td><td>".$ret_people["nom"]."</td><td>".$prenom."</td><td>".$tmp[2]."</td><td>".$date_naissance."</td><td>".$ret_people["no_gep"]."</td></tr>\n";
											}
										}
										*/
									}

									@ldap_free_result ( $result );
								}
								else {
									echo "<p>Echec de la recherche dans le LDAP de l'ELENOET pour $lig->ELENOET ($lig->ELENOM $lig->ELEPRE).</p>";
								}
							}
							else {
								// G�n�ration d'un login �l�ve type auth_native_gepi: NOM_P

								$tmp_nom=strtr($lig->ELENOM,"������������������������������","aaaeeeeiioouuucAAAEEEEIIOOUUUC");
								$tmp_prenom=strtr($lig->ELEPRE,"������������������������������","aaaeeeeiioouuucAAAEEEEIIOOUUUC");
		
								// G�n�rer un login...
								$temp1 = strtoupper($tmp_nom);
								$temp1 = preg_replace('/[^0-9a-zA-Z_]/',"", $temp1);
								$temp1 = strtr($temp1, " '-", "___");
								$temp1 = substr($temp1,0,7);
								$temp2 = strtoupper($tmp_prenom);
								$temp2 = preg_replace('/[^0-9a-zA-Z_]/',"", $temp2);
								$temp2 = strtr($temp2, " '-", "___");
								$temp2 = substr($temp2,0,1);
								$login_eleve = $temp1.'_'.$temp2;
		
								// On teste l'unicit� du login que l'on vient de cr�er
								$k = 2;
								$test_unicite = 'no';
								$temp = $login_eleve;
								while ($test_unicite != 'yes') {
									//$test_unicite = test_unique_e_login($login_eleve,$i);
									$test_unicite = test_unique_login($login_eleve);
									if ($test_unicite != 'yes') {
										$login_eleve = $temp.$k;
										$k++;
									}
								}
							}
	
							if($login_eleve=='') {
								echo "<p style='color:red;'>Le login de $lig->ELENOM $lig->ELEPRE n'a pas pu �tre g�n�r� ni r�cup�r�.</p>\n";
							}
							else {
								// On ne renseigne plus l'ERENO et on n'a pas l'EMAIL dans temp_gep_import2
								$sql="INSERT INTO eleves SET login='$login_eleve',
														nom='".addslashes($lig->ELENOM)."',
														prenom='".addslashes($lig->ELEPRE)."',
														sexe='".$lig->ELESEXE."',
														naissance='".$naissance."',
														no_gep='".$lig->ELENONAT."',
														elenoet='".$lig->ELENOET."',
														ele_id='".$lig->ELE_ID."'";
								if($ele_lieu_naissance=="y") {
									$sql.=", lieu_naissance='".$lig->LIEU_NAISSANCE."'";
								}
								$sql.=", email='".$lig->MEL."'";
								$sql.=";";
								info_debug($sql);
								$insert=mysql_query($sql);
								if($insert){
									echo "\n<span style='color:blue;'>";
								}
								else{
									echo "\n<span style='color:red;'>";
									$erreur++;
								}
								//echo "$sql<br />\n";
								echo "$lig->ELEPRE $lig->ELENOM";
								echo "</span>";
		
		
								$sql="INSERT INTO j_eleves_regime SET doublant='$doublant',
											regime='$regime',
											login='$login_eleve';";
								info_debug($sql);
								$res2=mysql_query($sql);
								if(!$res2){
									echo " <span style='color:red;'>(*)</span>";
									$erreur++;
								}
		
		
								if(strtolower($lig->ETOCOD_EP)!=strtolower($gepiSchoolRne)) {
									$sql="SELECT 1=1 FROM j_eleves_etablissements WHERE id_eleve='$lig->ELENOET';";
									info_debug($sql);
									$test_ee=mysql_query($sql);
									if(mysql_num_rows($test_ee)>0) {
										if($lig->ETOCOD_EP!="") {
											$sql="UPDATE j_eleves_etablissements SET id_etablissement='$lig->ETOCOD_EP' WHERE id_eleve='$lig->ELENOET';";
											info_debug($sql);
											$update_ee=mysql_query($sql);
										}
										else {
											$sql="DELETE FROM j_eleves_etablissements WHERE id_eleve='$lig->ELENOET';";
											info_debug($sql);
											$del_ee=mysql_query($sql);
										}
									}
									else {
										$sql="INSERT INTO j_eleves_etablissements SET id_eleve='$lig->ELENOET', id_etablissement='$lig->ETOCOD_EP';";
										info_debug($sql);
										$insert_ee=mysql_query($sql);
									}
								}
		
		
								// On remplit aussi une table pour l'association avec la classe:
								// On fait le m�me traitement que dans step2.php
								// (dans step1.php, on a fait le m�me traitement que pour le remplissage de temp_gep_import2 ici)
								$classe=traitement_magic_quotes(corriger_caracteres($lig->DIVCOD));
								$sql="INSERT INTO temp_ele_classe SET ele_id='".$lig->ELE_ID."', divcod='$classe'";
								info_debug($sql);
								$insert=mysql_query($sql);
							}
							$cpt++;
						}
					}
					echo "</p>\n";
				//}
			}

			echo "<p><br /></p>\n";




			if($cpt==0){
				// Pas de nouveau:
				switch($erreur){
					case 0:
						echo "<p>Passer � l'�tape d'<a href='".$_SERVER['PHP_SELF']."?step=9&amp;stop=$stop'>import/mise � jour des personnes (<i>responsables</i>) et adresses</a>.</p>\n";
						break;

					case 1:
						echo "<p><font color='red'>Une erreur s'est produite.</font><br />\nVous devriez en chercher la cause avant de passer � l'�tape d'<a href='".$_SERVER['PHP_SELF']."?step=9&amp;stop=$stop'>import/mise � jour des personnes (<i>responsables</i>) et adresses</a>.</p>\n";
						break;

					default:
						echo "<p><font color='red'>$erreur erreurs se sont produites.</font><br />\nVous devriez en chercher la cause avant de passer � l'�tape d'<a href='".$_SERVER['PHP_SELF']."?step=9&amp;stop=$stop'>import/mise � jour des personnes (<i>responsables</i>) et adresses</a>.</p>\n";
						break;
				}
			}
			else{
				switch($erreur){
					case 0:
						echo "<p>Passer � l'�tape d'<a href='".$_SERVER['PHP_SELF']."?step=6&amp;stop=$stop'>affectation des nouveaux �l�ves dans leurs classes</a>.</p>\n";
						break;

					case 1:
						echo "<p><font color='red'>Une erreur s'est produite.</font><br />\nVous devriez en chercher la cause avant de passer � l'�tape d'<a href='".$_SERVER['PHP_SELF']."?step=6&amp;stop=$stop'>affectation des nouveaux �l�ves dans leurs classes</a>.</p>\n";
						break;

					default:
						echo "<p><font color='red'>$erreur erreurs se sont produites.</font><br />\nVous devriez en chercher la cause avant de passer � l'�tape d'<a href='".$_SERVER['PHP_SELF']."?step=6&amp;stop=$stop'>affectation des nouveaux �l�ves dans leurs classes</a>.</p>\n";
						break;
				}
			}

			break;

// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++

// INSERER ICI: le traitement d'affectation dans les classes des nouveaux �l�ves...
//              ... et d'affectation dans les options?

// ++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++
		case "6":
			echo "<h2>Import/mise � jour des �l�ves</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			echo "<p>Affectation des nouveaux �l�ves dans leurs classes:</p>\n";

			echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>\n";
			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//==============================

			// DISTINCT parce qu'on peut avoir plusieurs enregistrements d'un m�me �l�ve dans 'temp_ele_classe' si on a jou� avec F5.
			// ERREUR: Il faut r�gler le probl�me plus haut parce que si on ins�re plusieurs fois l'�l�ve, il est plusieurs fois dans 'eleves' avec des logins diff�rents.
			$sql="SELECT DISTINCT e.*,t.divcod FROM temp_ele_classe t,eleves e WHERE t.ele_id=e.ele_id ORDER BY e.nom,e.prenom";
			info_debug($sql);
			$res_ele=mysql_query($sql);

			//echo mysql_num_rows($res_ele);

			if(mysql_num_rows($res_ele)==0){
				echo "<p>Bizarre: il semble que la table 'temp_ele_classe' ne contienne aucun identifiant de nouvel �l�ve.</p>\n";
				// FAUT-IL SAUTER A UNE AUTRE ETAPE?
			}
			else{

				$sql="SELECT DISTINCT num_periode FROM periodes ORDER BY num_periode DESC LIMIT 1";
				info_debug($sql);
				$res_per=mysql_query($sql);

				if(mysql_num_rows($res_per)==0){
					echo "<p>Bizarre: il semble qu'aucune p�riode ne soit encore d�finie.</p>\n";
					// FAUT-IL SAUTER A UNE AUTRE ETAPE?
				}
				else{

					$lig_per=mysql_fetch_object($res_per);
					$max_per=$lig_per->num_periode;
					echo "<input type='hidden' name='maxper' value='$max_per' />\n";

					echo "<p align='center'><input type='submit' value='Valider' /></p>\n";

					//echo "<table class='majimport'>\n";
					echo "<table class='boireaus'>\n";
					echo "<tr>\n";
					echo "<th rowspan='2'>El�ve</th>\n";
					echo "<th rowspan='2'>Classe</th>\n";
					echo "<th colspan='$max_per'>P�riodes</th>\n";

					$chaine_coche="";
					$chaine_decoche="";
					for($i=1;$i<=$max_per;$i++){
						$chaine_coche.="modif_case($i,\"col\",true);";
						$chaine_decoche.="modif_case($i,\"col\",false);";
					}

					//echo "<th rowspan='2'>&nbsp;</th>\n";
					echo "<th rowspan='2'>\n";
					echo "<a href='javascript:$chaine_coche'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>/\n";
					echo "<a href='javascript:$chaine_decoche'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
					echo "</th>\n";
					echo "</tr>\n";

					echo "<tr>\n";
					for($i=1;$i<=$max_per;$i++){
						echo "<th>\n";
						echo "P�riode $i\n";
						echo "<br />\n";
						echo "<a href='javascript:modif_case($i,\"col\",true)'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>/\n";
						echo "<a href='javascript:modif_case($i,\"col\",false)'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
						echo "</th>\n";

						$chaine_coche.="modif_case($i,\"col\",true);";
						$chaine_decoche.="modif_case($i,\"col\",false);";
					}
					echo "</tr>\n";





					$cpt=0;
					$alt=-1;
					while($lig_ele=mysql_fetch_object($res_ele)){
						$alt=$alt*(-1);
						/*
						echo "<tr style='background-color:";
						if($alt==1){
							echo "silver";
						}
						else{
							echo "white";
						}
						echo ";'>\n";
						*/
						echo "<tr class='lig$alt'>\n";

						echo "<td>";
						echo "$lig_ele->nom $lig_ele->prenom";
						echo "<input type='hidden' name='login_eleve[$cpt]' value='".$lig_ele->login."' />\n";
						echo "</td>\n";

						if($auth_sso=='lcs') {
							$sql="SELECT c.id FROM classes c WHERE c.classe='".my_ereg_replace("'","_",my_ereg_replace(" ","_",$lig_ele->divcod))."';";
						}
						else {
							$sql="SELECT c.id FROM classes c WHERE c.classe='$lig_ele->divcod';";
						}
						info_debug($sql);
						$res_classe=mysql_query($sql);
						if(mysql_num_rows($res_classe)>0){
							$lig_classe=mysql_fetch_object($res_classe);

							echo "<td>";
							echo $lig_ele->divcod;
							echo "<input type='hidden' name='id_classe[$cpt]' value='$lig_classe->id' />\n";
							echo "</td>\n";

							if($auth_sso=='lcs') {
								$sql="SELECT p.num_periode FROM periodes p, classes c
													WHERE p.id_classe=c.id AND
															c.classe='".my_ereg_replace("'","_",my_ereg_replace(" ","_",$lig_ele->divcod))."'
													ORDER BY num_periode;";
							}
							else {
								$sql="SELECT p.num_periode FROM periodes p, classes c
													WHERE p.id_classe=c.id AND
															c.classe='$lig_ele->divcod'
													ORDER BY num_periode;";
							}
							info_debug($sql);
							$res_per=mysql_query($sql);
							$cpt_periode=1;
							while($lig_per=mysql_fetch_object($res_per)){
								echo "<td>\n";
								echo "<input type='checkbox' name='periode_".$cpt."_[$cpt_periode]' id='case".$cpt."_".$cpt_periode."' value='$cpt_periode' />\n";
								echo "</td>\n";
								$cpt_periode++;
							}
							for($i=$cpt_periode;$i<=$max_per;$i++){
								echo "<td style='background-color: darkgray;'>\n";
								echo "</td>\n";
							}
						}
						else{
							// La classe n'a pas �t� identifi�e
							$sql="SELECT DISTINCT id,classe FROM classes ORDER BY classe";
							info_debug($sql);
							$res_classe=mysql_query($sql);
							echo "<td>\n";
							if(mysql_num_rows($res_classe)>0){
								echo "<select name='id_classe[$cpt]'>\n";
								echo "<option value=''>---</option>\n";
								while($lig_classe=mysql_fetch_object($res_classe)){
									echo "<option value='$lig_classe->id'>$lig_classe->classe</option>\n";
								}
								echo "</select>\n";
							}
							echo "</td>\n";

							for($i=1;$i<=$max_per;$i++){
								echo "<td style='background-color: orange;'>\n";
								echo "<input type='checkbox' name='periode_".$cpt."_[$i]' id='case".$cpt."_".$i."' value='$i' />\n";
								echo "</td>\n";
							}
						}

						echo "<td>\n";
						echo "<a href='javascript:modif_case($cpt,\"lig\",true)'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>/\n";
						echo "<a href='javascript:modif_case($cpt,\"lig\",false)'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
						echo "</td>\n";

						echo "</tr>\n";
						$cpt++;
					}
					echo "</table>\n";
				}
			}


			echo "<script type='text/javascript' language='javascript'>
	function modif_case(rang,type,statut){
		// type: col ou lig
		// rang: le num�ro de la colonne ou de la ligne
		// statut: true ou false
		if(type=='col'){
			for(k=0;k<$cpt;k++){
				if(document.getElementById('case'+k+'_'+rang)){
					document.getElementById('case'+k+'_'+rang).checked=statut;
				}
			}
		}
		else{
			for(k=1;k<=$max_per;k++){
				if(document.getElementById('case'+rang+'_'+k)){
					document.getElementById('case'+rang+'_'+k).checked=statut;
				}
			}
		}
		changement();
	}
</script>\n";

			echo "<p><br /></p>\n";

			//echo "<input type='hidden' name='step' value='6_1' />\n";
			echo "<input type='hidden' name='step' value='7' />\n";
			echo "<p align='center'><input type='submit' value='Valider' /></p>\n";

			echo add_token_field();

			echo "</form>\n";
			break;

		//case "6_1":
		case "7":
			echo "<h2>Import/mise � jour des �l�ves</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			$login_eleve=isset($_POST['login_eleve']) ? $_POST['login_eleve'] : NULL;
			$id_classe=isset($_POST['id_classe']) ? $_POST['id_classe'] : NULL;
			$maxper=isset($_POST['maxper']) ? $_POST['maxper'] : NULL;

			if(!isset($login_eleve)) {
				echo "<p>Vous n'avez affect� aucun �l�ve.</p>\n";
			}
			else {

				check_token(false);

				echo "<p>\n";
				for($i=0;$i<count($login_eleve);$i++){
					$sql="SELECT nom, prenom FROM eleves WHERE login='$login_eleve[$i]'";
					//echo $sql."<br />";
					info_debug($sql);
					$res_ele=mysql_query($sql);
					if(mysql_num_rows($res_ele)>0){
						$lig_ele=mysql_fetch_object($res_ele);

						echo "Affectation de $lig_ele->prenom $lig_ele->nom ";

						//if(is_int($id_classe[$i])){
						if(is_numeric($id_classe[$i])){
							$tab_periode=isset($_POST['periode_'.$i.'_']) ? $_POST['periode_'.$i.'_'] : NULL;

							if(isset($tab_periode)){
								$sql="SELECT classe FROM classes WHERE id='$id_classe[$i]'";
								info_debug($sql);
								$test=mysql_query($sql);
								if(mysql_num_rows($test)>0){
									$lig_classe=mysql_fetch_object($test);

									echo "en $lig_classe->classe pour ";
									if(count($tab_periode)==1){
										echo "la p�riode ";
									}
									else{
										echo "les p�riodes ";
									}

									$cpt_per=0;
									for($j=1;$j<=$maxper;$j++){
										if(isset($tab_periode[$j])){
											//if(is_int($tab_periode[$j])){
											if(is_numeric($tab_periode[$j])){
												$sql="SELECT 1=1 FROM periodes WHERE id_classe='$id_classe[$i]' AND num_periode='$tab_periode[$j]'";
												info_debug($sql);
												$test=mysql_query($sql);

												if(mysql_num_rows($test)>0){
													// VERIFICATION: Si on fait F5 pour rafraichir la page, on risque d'ins�rer plusieurs fois le m�me enregistrement.
													$sql="SELECT 1=1 FROM j_eleves_classes WHERE login='$login_eleve[$i]' AND
																						id_classe='$id_classe[$i]' AND
																						periode='$tab_periode[$j]'";
													info_debug($sql);
													$test=mysql_query($sql);

													if(mysql_num_rows($test)==0){
														$sql="INSERT INTO j_eleves_classes SET login='$login_eleve[$i]',
																							id_classe='$id_classe[$i]',
																							periode='$tab_periode[$j]',
																							rang='0'";
														info_debug($sql);
														$insert=mysql_query($sql);
													}
													if($cpt_per>0){echo ", ";}
													echo "$j";
													$cpt_per++;
												}
											}
										}
									}
								}
								else{
									echo "dans aucune classe (<i>identifiant de classe invalide</i>).";
								}
							}
							else{
								echo "dans aucune classe (<i>aucune p�riode coch�e</i>).";
							}
						}
						else{
							echo "dans aucune classe (<i>identifiant de classe invalide</i>).";
						}
						echo "<br />\n";
					}
				}
				echo "</p>\n";
			}

			echo "<p>Passer � l'�tape d'<a href='".$_SERVER['PHP_SELF']."?step=8&amp;stop=$stop'>inscription des nouveaux �l�ves dans les groupes</a>.</p>\n";

			break;

		case "8":

			echo "<h2>Import/mise � jour des �l�ves</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			$opt_eleve=isset($_POST['opt_eleve']) ? $_POST['opt_eleve'] : NULL;
			$eleve=isset($_POST['eleve']) ? $_POST['eleve'] : NULL;

			echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>\n";
			echo add_token_field();

			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//==============================

			if(!isset($opt_eleve)){
				$sql="SELECT e.* FROM eleves e, temp_ele_classe t WHERE t.ele_id=e.ele_id ORDER BY e.nom,e.prenom";
				info_debug($sql);
				$res_ele=mysql_query($sql);

				if(mysql_num_rows($res_ele)==0){
					// CA NE DEVRAIT PAS ARRIVER

					echo "<p>Il semble qu'il n'y ait aucun �l�ve � affecter.</p>\n";

					// METTRE LE LIEN VERS L'ETAPE SUIVANTE

					echo "</form>\n";
					require("../lib/footer.inc.php");
					die();
				}

				$lig_ele=mysql_fetch_object($res_ele);
				$nom_eleve=$lig_ele->nom;
				$prenom_eleve=$lig_ele->prenom;
				$login_eleve=$lig_ele->login;
				$ele_id=$lig_ele->ele_id;

				while($lig_ele=mysql_fetch_object($res_ele)){
					echo "<input type='hidden' name='eleve[]' value='$lig_ele->ele_id' />\n";
				}

			}
			else{

				check_token(false);

				$login_eleve=isset($_POST['login_eleve']) ? $_POST['login_eleve'] : NULL;
				$id_classe=isset($_POST['id_classe']) ? $_POST['id_classe'] : NULL;
				$sql="SELECT * FROM periodes WHERE id_classe='$id_classe' ORDER BY num_periode";
				info_debug($sql);
				$res_per=mysql_query($sql);
				$nb_periode=mysql_num_rows($res_per)+1;

				$cpe_resp=isset($_POST['cpe_resp']) ? $_POST['cpe_resp'] : NULL;

				if(isset($cpe_resp)){
					if("$cpe_resp"!=""){
						// Par pr�caution:
						$sql="DELETE FROM j_eleves_cpe WHERE e_login='$login_eleve' AND cpe_login='$cpe_resp'";
						info_debug($sql);
						$nettoyage_cpe=mysql_query($sql);

						$sql="INSERT INTO j_eleves_cpe SET e_login='$login_eleve', cpe_login='$cpe_resp'";
						info_debug($sql);
						$insert_cpe=mysql_query($sql);
					}
				}

				$pp_resp=isset($_POST['pp_resp']) ? $_POST['pp_resp'] : NULL;

				if(isset($pp_resp)){
					if("$pp_resp"!=""){
						// Par pr�caution:
						$sql="DELETE FROM j_eleves_professeurs WHERE login='$login_eleve' AND professeur='$pp_resp' AND id_classe='$id_classe';";
						// DEBUG:
						//echo "$sql<br />\n";
						info_debug($sql);
						$nettoyage_pp=mysql_query($sql);

						$sql="INSERT INTO j_eleves_professeurs SET login='$login_eleve', professeur='$pp_resp', id_classe='$id_classe';";
						// DEBUG:
						//echo "$sql<br />\n";
						info_debug($sql);
						$insert_pp=mysql_query($sql);
					}
				}
				/*
				$cpt=1;
				while($lig_per=mysql_fetch_object($res_per)){
					$nom_periode[$cpt]=$lig_per->nom_periode;
					$cpt++;
				}
				*/

				$j = 1;
				while ($j < $nb_periode) {
					$call_group = mysql_query("SELECT DISTINCT g.id, g.name FROM groupes g, j_groupes_classes jgc WHERE (g.id = jgc.id_groupe and jgc.id_classe = '" . $id_classe ."') ORDER BY jgc.priorite, g.name");
					$nombre_ligne = mysql_num_rows($call_group);
					$i=0;
					while ($i < $nombre_ligne) {
						$id_groupe = mysql_result($call_group, $i, "id");
						$nom_groupe = mysql_result($call_group, $i, "name");
						$id_group[$j] = $id_groupe."_".$j;
						$test_query = mysql_query("SELECT 1=1 FROM j_eleves_groupes WHERE (" .
								"id_groupe = '" . $id_groupe . "' and " .
								"login = '" . $login_eleve . "' and " .
								"periode = '" . $j . "')");
						$test = mysql_num_rows($test_query);
						if (isset($_POST[$id_group[$j]])) {
							if ($test == 0) {
								$req = mysql_query("INSERT INTO j_eleves_groupes SET id_groupe = '" . $id_groupe . "', login = '" . $login_eleve . "', periode = '" . $j ."'");
							}
						} else {
							$test1 = mysql_query("SELECT 1=1 FROM matieres_notes WHERE (id_groupe = '".$id_groupe."' and login = '".$login_eleve."' and periode = '$j')");
							$nb_test1 = mysql_num_rows($test1);
							$test2 = mysql_query("SELECT 1=1 FROM matieres_appreciations WHERE (id_groupe = '".$id_groupe."' and login = '".$login_eleve."' and periode = '$j')");
							$nb_test2 = mysql_num_rows($test2);
							if (($nb_test1 != 0) or ($nb_test2 != 0)) {
								$msg = $msg."--> Impossible de supprimer cette option pour l'�l�ve $login_eleve car des moyennes ou appr�ciations ont d�j� �t� rentr�es pour le groupe $nom_groupe pour la p�riode $j ! Commencez par supprimer ces donn�es !<br />";
							} else {
								if ($test != "0")  $req = mysql_query("DELETE FROM j_eleves_groupes WHERE (login='".$login_eleve."' and id_groupe='".$id_groupe."' and periode = '".$j."')");
							}
						}
						$i++;
					}
					$j++;
				}



				if(isset($eleve)){
					$sql="SELECT e.* FROM eleves e WHERE e.ele_id='$eleve[0]'";
					info_debug($sql);
					$res_ele=mysql_query($sql);

					$lig_ele=mysql_fetch_object($res_ele);
					$nom_eleve=$lig_ele->nom;
					$prenom_eleve=$lig_ele->prenom;
					$login_eleve=$lig_ele->login;
					$ele_id=$lig_ele->ele_id;

					for($i=1;$i<count($eleve);$i++){
						echo "<input type='hidden' name='eleve[]' value='$eleve[$i]' />\n";
					}
				}
				else{
					echo "<p>Tous les �l�ves ont �t� parcourus.</p>\n";

					// METTRE LE LIEN VERS L'ETAPE SUIVANTE

					echo "<input type='hidden' name='step' value='9' />\n";
					echo "<p><input type='submit' value='Etape suivante: Responsables' /></p>\n";

					echo "</form>\n";
					require("../lib/footer.inc.php");
					die();
				}
			}

			echo "<input type='hidden' name='opt_eleve' value='y' />\n";

			$sql="SELECT DISTINCT c.id,c.classe FROM classes c,j_eleves_classes jec
									WHERE jec.id_classe=c.id AND
										jec.login='$login_eleve'";
			info_debug($sql);
			$res_classe=mysql_query($sql);

			if(mysql_num_rows($res_classe)==0){
				echo "<p>$prenom_eleve $nom_eleve n'est dans aucune classe.</p>\n";

				// PASSER AU SUIVANT...

				echo "<input type='hidden' name='step' value='8' />\n";
				echo "<p><input type='submit' value='Suite' /></p>\n";

				echo "</form>\n";
			}
			else{
				$lig_classe=mysql_fetch_object($res_classe);
				$id_classe=$lig_classe->id;

				$sql="SELECT * FROM periodes WHERE id_classe='$id_classe' ORDER BY num_periode";
				info_debug($sql);
				$res_per=mysql_query($sql);

				if(mysql_num_rows($res_per)==0){
					echo "<p>L'�l�ve $prenom_eleve $ele_nom_eleve serait dans une classe sans p�riode???</p>\n";

					// PASSER AU SUIVANT...
					echo "</form>\n";
				}
				else{

					echo "<p><b>$prenom_eleve $nom_eleve</b> (<i>$lig_classe->classe</i>)</p>\n";

					//===========================
					// A FAIRE: boireaus 20071129
					//          Ajouter l'association avec le PP et le CPE
					$sql="SELECT login, nom, prenom FROM utilisateurs WHERE statut='cpe' ORDER BY nom, prenom;";
					info_debug($sql);
					$res_cpe=mysql_query($sql);

					echo "<table border='0'>\n";
					if(mysql_num_rows($res_cpe)>0){
						echo "<tr><td>CPE responsable: </td><td><select name='cpe_resp'>\n";
						echo "<option value=''>---</option>\n";
						while($lig_cpe=mysql_fetch_object($res_cpe)){
							echo "<option value='$lig_cpe->login'";
							if(mysql_num_rows($res_cpe)==1) {echo " selected";}
							echo ">$lig_cpe->nom $lig_cpe->prenom</option>\n";
						}
						echo "</select>\n";
						echo "</td>\n";
						echo "</tr>\n";
					}

					$sql="SELECT DISTINCT u.login, u.nom, u.prenom FROM utilisateurs u, j_eleves_professeurs jep
										WHERE jep.id_classe='$id_classe' AND
												jep.professeur=u.login
										ORDER BY u.nom, u.prenom;";
					info_debug($sql);
					$res_pp=mysql_query($sql);
					if(mysql_num_rows($res_pp)>0){
						echo "<tr><td>".ucfirst(getSettingValue('gepi_prof_suivi')).": </td><td><select name='pp_resp'>\n";
						echo "<option value=''>---</option>\n";
						while($lig_pp=mysql_fetch_object($res_pp)){
							echo "<option value='$lig_pp->login'";
							if(mysql_num_rows($res_pp)==1) {echo " selected";}
							echo ">$lig_pp->nom $lig_pp->prenom</option>\n";
						}
						echo "</select>\n";
						echo "</td>\n";
						echo "</tr>\n";
					}
					echo "</table>\n";
					echo "<p>&nbsp;</p>\n";

					//===========================

					$nb_periode=mysql_num_rows($res_per)+1;

					$cpt=1;
					while($lig_per=mysql_fetch_object($res_per)){
						$nom_periode[$cpt]=$lig_per->nom_periode;
						$cpt++;
					}


					echo "<p>Affectation dans les groupes de l'�l�ve $prenom_eleve $nom_eleve (<i>$lig_classe->classe</i>)</p>\n";
					echo "<p align='center'><input type='submit' value='Valider' /></p>\n";

					echo "<input type='hidden' name='id_classe' value='$id_classe' />\n";
					echo "<input type='hidden' name='login_eleve' value='$login_eleve' />\n";


					$sql="SELECT DISTINCT g.id, g.name FROM groupes g,
															j_groupes_classes jgc
									WHERE (g.id = jgc.id_groupe AND
											jgc.id_classe = '" . $id_classe ."')
									ORDER BY jgc.priorite, g.name";
					info_debug($sql);
					$call_group=mysql_query($sql);
					$nombre_ligne=mysql_num_rows($call_group);

					//echo "<table border = '1' cellpadding='5' cellspacing='0'>\n";
					//echo "<table class='majimport' cellpadding='5' cellspacing='0'>\n";
					echo "<table class='boireaus' cellpadding='5' cellspacing='0'>\n";
					//echo "<tr align='center'><td><b>Mati�re</b></td>";
					echo "<tr align='center'><th><b>Mati�re</b></th>\n";

					$j = 1;
					$chaine_coche="";
					$chaine_decoche="";
					while ($j < $nb_periode) {
						//echo "<td><b>".$nom_periode[$j]."</b><br />\n";
						echo "<th><b>".$nom_periode[$j]."</b><br />\n";
						echo "<a href='javascript:modif_case($j,\"col\",true)'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>/\n";
						echo "<a href='javascript:modif_case($j,\"col\",false)'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
						//echo "</td>";
						echo "</th>\n";

						$chaine_coche.="modif_case($j,\"col\",true);";
						$chaine_decoche.="modif_case($j,\"col\",false);";

						$j++;
					}
					//echo "<td>&nbsp;</td>\n";
					//echo "<th>&nbsp;</th>\n";
					echo "<th>\n";

					echo "<a href='javascript:$chaine_coche'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>/\n";
					echo "<a href='javascript:$chaine_decoche'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";

					echo "</th>\n";
					echo "</tr>\n";

					$tab_champs_grp=array('matieres','profs','classes');

					$nb_erreurs=0;
					$i=0;
					$alt=-1;
					while ($i < $nombre_ligne) {
						$id_groupe = mysql_result($call_group, $i, "id");
						$nom_groupe = mysql_result($call_group, $i, "name");

						$tmp_group=get_group($id_groupe,$tab_champs_grp);
						$chaine_profs="";
						//for($loop=0;$loop<count($tmp_group[])) {}
						foreach($tmp_group["profs"]["users"] as $login_prof) {
							$chaine_profs.=", ";
							$chaine_profs.=$login_prof['civilite']."&nbsp;".$login_prof['nom']." ".substr($login_prof['prenom'],0,1);
						}
						if($chaine_profs!='') {$chaine_profs=substr($chaine_profs,2);}

						$alt=$alt*(-1);
						/*
						echo "<tr style='background-color:";
						if($alt==1){
							echo "silver";
						}
						else{
							echo "white";
						}
						echo ";'>\n";
						*/
						echo "<tr class='lig$alt white_hover'>\n";
						echo "<td>".$nom_groupe;
						echo " <span style='font-size: x-small'>(".$tmp_group['classlist_string'].")</span>";
						echo "<br />";
						//echo "<span style='font-size: x-small'>".$tmp_group['description']."</span>";
						//echo "<br />";
						echo "<span style='font-size: x-small'>".$chaine_profs."</span>";
						echo "</td>\n";
						$j = 1;
						while ($j < $nb_periode) {
							$test=mysql_query("SELECT 1=1 FROM j_eleves_groupes WHERE (" .
									"id_groupe = '" . $id_groupe . "' and " .
									"login = '" . $login_eleve . "' and " .
									"periode = '" . $j . "')");

							$sql="SELECT * FROM j_eleves_classes WHERE login='$login_eleve' AND periode='$j' AND id_classe='$id_classe'";
							// CA NE VA PAS... SUR LES GROUPES A REGROUPEMENT, IL FAUT PRENDRE DES PRECAUTIONS...
							info_debug($sql);
							$res_test_class_per=mysql_query($sql);
							if(mysql_num_rows($res_test_class_per)==0){
								if (mysql_num_rows($test) == "0") {
									echo "<td>&nbsp;</td>\n";
								}
								else{
									$sql="SELECT DISTINCT id_classe FROM j_groupes_classes WHERE id_groupe='$id_groupe'";
									info_debug($sql);
									$res_grp=mysql_query($sql);
									$temoin="";
									while($lig_clas=mysql_fetch_object($res_grp)){
										$sql="SELECT 1=1 FROM j_eleves_classes WHERE id_classe='$lig_clas->id_classe' AND login='$login_eleve' AND periode='$j'";
										info_debug($sql);
										$res_test_ele=mysql_query($sql);
										if(mysql_num_rows($res_test_ele)==1){
											$sql="SELECT classe FROM classes WHERE id='$lig_clas->id_classe'";
											info_debug($sql);
											$res_tmp=mysql_query($sql);
											$lig_tmp=mysql_fetch_object($res_tmp);
											$clas_tmp=$lig_tmp->classe;

											$temoin=$clas_tmp;
										}
									}

									if($temoin!=""){
										echo "<td><center>".$temoin."<input type=hidden name=".$id_groupe."_".$j." value='checked' /></center></td>\n";
									}
									else{
										$msg_erreur="Cette case est valid�e et ne devrait pas l �tre. Validez le formulaire pour corriger.";
										echo "<td><center><a href='#' alt='$msg_erreur' title='$msg_erreur'><font color='red'>ERREUR</font></a></center></td>\n";
										$nb_erreurs++;
									}
								}
							}
							else{

								/*
								// Un autre test � faire:
								// Si l'�l�ve est rest� dans le groupe alors qu'il n'est plus dans cette classe pour la p�riode
								$sql="SELECT 1=1 FROM j_eleves_classes WHERE id_classe='$id_classe' AND periode='$j' AND login='$login_eleve'";
								*/

								//=========================
								// MODIF: boireaus
								if (mysql_num_rows($test) == "0") {
									//echo "<td><center><input type=checkbox name=".$id_groupe."_".$j." /></center></td>\n";
									echo "<td><center><input type=checkbox id=case".$i."_".$j." name=".$id_groupe."_".$j." onchange='changement();' /></center></td>\n";
								} else {
									//echo "<td><center><input type=checkbox name=".$id_groupe."_".$j." CHECKED /></center></td>\n";
									echo "<td><center><input type=checkbox id=case".$i."_".$j." name=".$id_groupe."_".$j." onchange='changement();' checked /></center></td>\n";
								}
								//=========================
							}
							$j++;
						}
						//=========================
						// AJOUT: boireaus
						echo "<td>\n";
						//echo "<input type='button' name='coche_lig_$i' value='C' onClick='modif_case($i,\"lig\",true)' />/\n";
						//echo "<input type='button' name='decoche_lig_$i' value='D' onClick='modif_case($i,\"lig\",false)' />\n";
						echo "<a href='javascript:modif_case($i,\"lig\",true)'><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>/\n";
						echo "<a href='javascript:modif_case($i,\"lig\",false)'><img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>\n";
						echo "</td>\n";
						//=========================
						echo "</tr>\n";
						$i++;
					}
					echo "</table>\n";


					echo "<script type='text/javascript' language='javascript'>
	function modif_case(rang,type,statut){
		// type: col ou lig
		// rang: le num�ro de la colonne ou de la ligne
		// statut: true ou false
		if(type=='col'){
			for(k=0;k<$nombre_ligne;k++){
				if(document.getElementById('case'+k+'_'+rang)){
					document.getElementById('case'+k+'_'+rang).checked=statut;
				}
			}
		}
		else{
			for(k=1;k<$nb_periode;k++){
				if(document.getElementById('case'+rang+'_'+k)){
					document.getElementById('case'+rang+'_'+k).checked=statut;
				}
			}
		}
		changement();
	}
</script>\n";

					echo "<input type='hidden' name='step' value='8' />\n";
					echo "<p align='center'><input type='submit' value='Valider' /></p>\n";
					echo "</form>\n";
				}

			}


			break;

		case "9":
			echo "<h2>Import/mise � jour des responsables</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post'>\n";
			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			//echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//==============================
			echo "<p>Veuillez fournir le fichier ResponsablesAvecAdresses.xml:<br />\n";
			echo "<input type=\"file\" size=\"80\" name=\"responsables_xml_file\" /><br />\n";
			echo "<input type='hidden' name='step' value='10' />\n";
			//echo "<input type='hidden' name='is_posted' value='yes' />\n";
			if ($gepiSettings['unzipped_max_filesize']>=0) {
				echo "<p style=\"font-size:small; color: red;\"><i>REMARQUE&nbsp;:</i> Vous pouvez fournir � Gepi le fichier compress� issu directement de SCONET. (Ex : ResponsablesAvecAdresses.zip)</p>";
			}

			echo add_token_field();

			echo "<input type='checkbox' name='ne_pas_proposer_resp_sans_eleve' id='ne_pas_proposer_resp_sans_eleve' value='non' checked />\n";
			//$ne_pas_proposer_resp_sans_eleve
			echo "<label for='ne_pas_proposer_resp_sans_eleve' style='cursor: pointer;'> Ne pas proposer d'ajouter les responsables non associ�s � des �l�ves.</label><br />(<i>de telles entr�es peuvent subsister en tr�s grand nombre dans Sconet</i>)<br />\n";

			$sql_resp_tmp="SELECT 1=1 FROM utilisateurs WHERE statut='eleve';";
			$test_comptes_resp=mysql_query($sql_resp_tmp);
			if(mysql_num_rows($test_comptes_resp)==0) {
				echo "<input type='hidden' name='alert_diff_mail_resp' id='alert_diff_mail_ele_y' value='y' />\n";
			}
			else {
				echo "Pour les responsables qui disposent d'un compte d'utilisateur, <br />\n";
				echo "<input type='radio' name='alert_diff_mail_resp' id='alert_diff_mail_resp_y' value='y' checked />\n";
				echo "<label for='alert_diff_mail_resp_y' style='cursor: pointer;'> signaler";
				echo " les diff�rences d'adresse Mail entre Sconet et le compte d'utilisateur.</label><br />\n";
				echo "<input type='radio' name='alert_diff_mail_resp' id='alert_diff_mail_resp_n' value='n' />\n";
				echo "<label for='alert_diff_mail_resp_n' style='cursor: pointer;'> ne pas signaler";
				echo " les diff�rences d'adresse Mail entre Sconet et le compte d'utilisateur.</label><br />\n";
			}

			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			//echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			echo "<input type='checkbox' name='stop' id='id_form_stop' value='y' ";
			if("$stop"=="y"){echo "checked ";}
			echo "/><label for='id_form_stop' style='cursor: pointer;'> D�sactiver le mode automatique.</label>";
			//echo "</p>\n";
			//==============================

			echo "<p><input type='submit' value='Valider' /></p>\n";
			echo "</form>\n";

			echo "<p><br /></p>\n";

			echo "<p><i>NOTE:</i></p>\n";
			echo "<blockquote>\n";
			echo "<p>Apr�s une phase d'analyse des diff�rences, les diff�rences seront affich�es et des cases � cocher seront propos�es pour valider les modifications.</p>\n";
			echo "<p>Les diff�rences concernant les personnes, puis les adresses sont recherch�es.<br />Ensuite seulement, il vous est propos� de valider les modifications concernant les personnes et adresses.</p>\n";
			echo "<p>Un troisi�me parcours des diff�rences est ensuite effectu� pour rechercher les changements dans les associations responsables/�l�ves.</p>\n";
			echo "</blockquote>\n";

			require("../lib/footer.inc.php");
			die();

			break;
		case "10":
			echo "<h2>Import/mise � jour des responsables</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			check_token(false);

			$_SESSION['ne_pas_proposer_resp_sans_eleve']=$ne_pas_proposer_resp_sans_eleve;
			$_SESSION['alert_diff_mail_resp']=$alert_diff_mail_resp;

			$post_max_size=ini_get('post_max_size');
			$upload_max_filesize=ini_get('upload_max_filesize');
			$max_execution_time=ini_get('max_execution_time');
			$memory_limit=ini_get('memory_limit');


			$xml_file = isset($_FILES["responsables_xml_file"]) ? $_FILES["responsables_xml_file"] : NULL;
			if(!is_uploaded_file($xml_file['tmp_name'])) {
				echo "<p style='color:red;'>L'upload du fichier a �chou�.</p>\n";

				echo "<p>Les variables du php.ini peuvent peut-�tre expliquer le probl�me:<br />\n";
				echo "post_max_size=$post_max_size<br />\n";
				echo "upload_max_filesize=$upload_max_filesize<br />\n";
				echo "</p>\n";

				// Il ne faut pas aller plus loin...
				// SITUATION A GERER
				require("../lib/footer.inc.php");
				die();
			}
			else{
				if(!file_exists($xml_file['tmp_name'])){
					echo "<p style='color:red;'>Le fichier aurait �t� upload�... mais ne serait pas pr�sent/conserv�.</p>\n";

					echo "<p>Les variables du php.ini peuvent peut-�tre expliquer le probl�me:<br />\n";
					echo "post_max_size=$post_max_size<br />\n";
					echo "upload_max_filesize=$upload_max_filesize<br />\n";
					echo "et le volume de ".$xml_file['name']." serait<br />\n";
					echo "\$xml_file['size']=".volume_human($xml_file['size'])."<br />\n";
					echo "</p>\n";
					// Il ne faut pas aller plus loin...
					// SITUATION A GERER
					require("../lib/footer.inc.php");
					die();
				}

				echo "<p>Le fichier a �t� upload�.</p>\n";

				//$source_file=stripslashes($xml_file['tmp_name']);
				$source_file=$xml_file['tmp_name'];
				$dest_file="../temp/".$tempdir."/responsables.xml";
				$res_copy=copy("$source_file" , "$dest_file");

				//===============================================================
				// ajout prise en compte des fichiers ZIP: Marc Leygnac

				$unzipped_max_filesize=getSettingValue('unzipped_max_filesize')*1024*1024;
				// $unzipped_max_filesize = 0    pas de limite de taille pour les fichiers extraits
				// $unzipped_max_filesize < 0    extraction zip d�sactiv�e
				if($unzipped_max_filesize>=0) {
					$fichier_emis=$xml_file['name'];
					$extension_fichier_emis=strtolower(strrchr($fichier_emis,"."));
					if (($extension_fichier_emis==".zip")||($xml_file['type']=="application/zip"))
						{
						require_once('../lib/pclzip.lib.php');
						$archive = new PclZip($dest_file);

						if (($list_file_zip = $archive->listContent()) == 0) {
							echo "<p style='color:red;'>Erreur : ".$archive->errorInfo(true)."</p>\n";
							require("../lib/footer.inc.php");
							die();
						}

						if(sizeof($list_file_zip)!=1) {
							echo "<p style='color:red;'>Erreur : L'archive contient plus d'un fichier.</p>\n";
							require("../lib/footer.inc.php");
							die();
						}

						/*
						echo "<p>\$list_file_zip[0]['filename']=".$list_file_zip[0]['filename']."<br />\n";
						echo "\$list_file_zip[0]['size']=".$list_file_zip[0]['size']."<br />\n";
						echo "\$list_file_zip[0]['compressed_size']=".$list_file_zip[0]['compressed_size']."</p>\n";
						*/
						//echo "<p>\$unzipped_max_filesize=".$unzipped_max_filesize."</p>\n";

						if(($list_file_zip[0]['size']>$unzipped_max_filesize)&&($unzipped_max_filesize>0)) {
							echo "<p style='color:red;'>Erreur : La taille du fichier extrait (<i>".$list_file_zip[0]['size']." octets</i>) d�passe la limite param�tr�e (<i>$unzipped_max_filesize octets</i>).</p>\n";
							require("../lib/footer.inc.php");
							die();
						}

						$res_extract=$archive->extract(PCLZIP_OPT_PATH, "../temp/".$tempdir);
						if ($res_extract != 0) {
							echo "<p>Le fichier upload� a �t� d�zipp�.</p>\n";
							$fichier_extrait=$res_extract[0]['filename'];
							unlink("$dest_file"); // Pour Wamp...
							$res_copy=rename("$fichier_extrait" , "$dest_file");
						}
						else {
							echo "<p style='color:red'>Echec de l'extraction de l'archive ZIP.</p>\n";
							require("../lib/footer.inc.php");
							die();
						}
					}
				}
				//fin  ajout prise en compte des fichiers ZIP
				//===============================================================

				if(!$res_copy){
					echo "<p style='color:red;'>La copie du fichier vers le dossier temporaire a �chou�.<br />V�rifiez que l'utilisateur ou le groupe apache ou www-data a acc�s au dossier temp/$tempdir</p>\n";
					// Il ne faut pas aller plus loin...
					// SITUATION A GERER
					require("../lib/footer.inc.php");
					die();
				}
				else{
					echo "<p>La copie du fichier vers le dossier temporaire a r�ussi.</p>\n";

					//$sql="CREATE TABLE IF NOT EXISTS resp_pers (
					/*
					$sql="CREATE TABLE IF NOT EXISTS temp_resp_pers_import (
							`pers_id` varchar(10) NOT NULL,
							`login` varchar(50) NOT NULL,
							`nom` varchar(30) NOT NULL,
							`prenom` varchar(30) NOT NULL,
							`civilite` varchar(5) NOT NULL,
							`tel_pers` varchar(255) NOT NULL,
							`tel_port` varchar(255) NOT NULL,
							`tel_prof` varchar(255) NOT NULL,
							`mel` varchar(100) NOT NULL,
							`adr_id` varchar(10) NOT NULL,
						PRIMARY KEY  (`pers_id`));";
					*/
					$sql="DROP TABLE IF EXISTS temp_resp_pers_import;";
					info_debug($sql);
					$nettoyage = mysql_query($sql);

					$sql="CREATE TABLE IF NOT EXISTS temp_resp_pers_import (
							`pers_id` varchar(10) $chaine_mysql_collate NOT NULL,
							`login` varchar(50) $chaine_mysql_collate NOT NULL,
							`nom` varchar(30) $chaine_mysql_collate NOT NULL,
							`prenom` varchar(30) $chaine_mysql_collate NOT NULL,
							`civilite` varchar(5) $chaine_mysql_collate NOT NULL,
							`tel_pers` varchar(255) $chaine_mysql_collate NOT NULL,
							`tel_port` varchar(255) $chaine_mysql_collate NOT NULL,
							`tel_prof` varchar(255) $chaine_mysql_collate NOT NULL,
							`mel` varchar(100) $chaine_mysql_collate NOT NULL,
							`adr_id` varchar(10) $chaine_mysql_collate NOT NULL,
							`statut` varchar(100) $chaine_mysql_collate NOT NULL,
						PRIMARY KEY  (`pers_id`));";
					info_debug($sql);
					$create_table = mysql_query($sql);

					$sql="TRUNCATE TABLE temp_resp_pers_import;";
					//$sql="TRUNCATE TABLE resp_pers;";
					info_debug($sql);
					$vide_table = mysql_query($sql);

					flush();

					echo "<p>Analyse du fichier pour extraire les informations de la section PERSONNES...<br />\n";

					//$dest_file="../temp/".$tempdir."/responsables.xml";
	
					$resp_xml=simplexml_load_file($dest_file);
					if(!$resp_xml) {
						echo "<p style='color:red;'>ECHEC du chargement du fichier avec simpleXML.</p>\n";
						require("../lib/footer.inc.php");
						die();
					}
	
					$nom_racine=$resp_xml->getName();
					if(strtoupper($nom_racine)!='BEE_RESPONSABLES') {
						echo "<p style='color:red;'>ERREUR: Le fichier XML fourni n'a pas l'air d'�tre un fichier XML Responsables.<br />Sa racine devrait �tre 'BEE_RESPONSABLES'.</p>\n";
						require("../lib/footer.inc.php");
						die();
					}

					// PARTIE <PERSONNES>
					// Compteur personnes:
					$i=-1;
					$personnes=array();

					$tab_champs_personne=array("NOM",
					"PRENOM",
					"LC_CIVILITE",
					"TEL_PERSONNEL",
					"TEL_PORTABLE",
					"TEL_PROFESSIONNEL",
					"MEL",
					"ACCEPTE_SMS",
					"ADRESSE_ID",
					"CODE_PROFESSION",
					"COMMUNICATION_ADRESSE"
					);

					$objet_personnes=($resp_xml->DONNEES->PERSONNES);
					foreach ($objet_personnes->children() as $personne) {
						//echo("<p><b>Personne</b><br />");

						$i++;
						$personnes[$i]=array();

						foreach($personne->attributes() as $key => $value) {
							// <PERSONNE PERSONNE_ID="294435">
							$personnes[$i][strtolower($key)]=traitement_magic_quotes(corriger_caracteres(trim($value)));
						}

						foreach($personne->children() as $key => $value) {
							if(in_array(strtoupper($key),$tab_champs_personne)) {
								$personnes[$i][strtolower($key)]=traitement_magic_quotes(corriger_caracteres(preg_replace('/"/','',trim(traite_utf8($value)))));
								//echo "\$structure->$key=".$value."<br />";
							}
						}

						if($debug_import=='y') {
							echo "<pre style='color:green;'><b>Tableau \$personnes[$i]&nbsp;:</b>";
							print_r($personnes[$i]);
							echo "</pre>";
						}
					}


						//traitement_magic_quotes(corriger_caracteres())
						$nb_err=0;
						$stat=0;
						$i=0;
						while($i<count($personnes)){
							$sql="INSERT INTO temp_resp_pers_import SET ";
							//$sql="INSERT INTO resp_pers SET ";
							$sql.="pers_id='".$personnes[$i]["personne_id"]."', ";
							$sql.="nom='".$personnes[$i]["nom"]."', ";
							$sql.="prenom='".$personnes[$i]["prenom"]."', ";
							if(isset($personnes[$i]["lc_civilite"])){
								$sql.="civilite='".ucfirst(strtolower($personnes[$i]["lc_civilite"]))."', ";
							}
							if(isset($personnes[$i]["tel_personnel"])){
								$sql.="tel_pers='".$personnes[$i]["tel_personnel"]."', ";
							}
							if(isset($personnes[$i]["tel_portable"])){
								$sql.="tel_port='".$personnes[$i]["tel_portable"]."', ";
							}
							if(isset($personnes[$i]["tel_professionnel"])){
								$sql.="tel_prof='".$personnes[$i]["tel_professionnel"]."', ";
							}
							if(isset($personnes[$i]["mel"])){
								$sql.="mel='".$personnes[$i]["mel"]."', ";
							}
							if(isset($personnes[$i]["adresse_id"])){
								$sql.="adr_id='".$personnes[$i]["adresse_id"]."';";
							}
							else{
								$sql.="adr_id='';";
								// IL FAUDRAIT PEUT-ETRE REMPLIR UN TABLEAU
								// POUR SIGNALER QUE CE RESPONSABLE RISQUE DE POSER PB...
								// ... CEPENDANT, CEUX QUE J'AI REP�R�S ETAIENT resp_legal=0
								// ILS NE DEVRAIENT PAS ETRE DESTINATAIRES DE BULLETINS,...
							}
							affiche_debug("$sql<br />\n");
							info_debug($sql);
							$res_insert=mysql_query($sql);
							if(!$res_insert){
								echo "Erreur lors de la requ�te $sql<br />\n";
								flush();
								$nb_err++;
							}
							else{
								$stat++;
							}

							$i++;
						}

						/*
						if($nb_err==0) {
							echo "<p>La premi�re phase s'est pass�e sans erreur.</p>\n";
						}
						elseif($nb_err==1) {
							echo "<p>$nb_err erreur.</p>\n";
						}
						else{
							echo "<p>$nb_err erreurs</p>\n";
						}
						*/

						echo "<p><br /></p>\n";

						if ($nb_err != 0) {
							echo "<p>Lors de l'enregistrement des donn�es PERSONNES, il y a eu $nb_err erreurs. Essayez de trouvez la cause de l'erreur et recommencez la proc�dure avant de passer � l'�tape suivante.</p>\n";
						} else {
							echo "<p>L'importation des personnes (responsables) dans la base GEPI a �t� effectu�e avec succ�s (".$stat." enregistrements au total).</p>\n";

							echo "<script type='text/javascript'>
	/*
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}
	if(stop=='n'){
		setTimeout(\"document.location.replace('".$_SERVER['PHP_SELF']."?step=11')\",2000);
	}
	*/
	setTimeout(\"test_stop('11')\",3000);
</script>\n";
						}

						//echo "<p>$stat enregistrement(s) ont �t� ins�r�(s) dans la table 'temp_resp_pers_import'.</p>\n";
						//echo "<p>$stat enregistrement(s) ont �t� ins�r�(s) dans la table 'resp_pers'.</p>\n";

						//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=11&amp;stop=$stop'>Suite</a></p>\n";
						echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=11&amp;stop=$stop' onClick=\"test_stop_suite('11'); return false;\">Suite</a></p>\n";

						//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=11&amp;stop=y&amp;ne_pas_proposer_resp_sans_eleve=$ne_pas_proposer_resp_sans_eleve'>Suite</a></p>\n";

						require("../lib/footer.inc.php");
						die();
					/*
					}
					else{
						echo "<p>ERREUR: Il n'a pas �t� possible d'ouvrir le fichier en lecture.</p>\n";

						require("../lib/footer.inc.php");
						die();
					}
					*/
				}
			}

			break;
		case "11":
			echo "<h2>Import/mise � jour des responsables</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			$dest_file="../temp/".$tempdir."/responsables.xml";
			/*
			$fp=fopen($dest_file,"r");
			if(!$fp){
				echo "<p>Le XML responsables n'a pas l'air pr�sent dans le dossier temporaire.<br />Auriez-vous saut� une �tape???</p>\n";
				require("../lib/footer.inc.php");
				die();
			}
			else{
			*/

				$resp_xml=simplexml_load_file($dest_file);
				if(!$resp_xml) {
					echo "<p style='color:red;'>ECHEC du chargement du fichier avec simpleXML.</p>\n";
					require("../lib/footer.inc.php");
					die();
				}

				$nom_racine=$resp_xml->getName();
				if(strtoupper($nom_racine)!='BEE_RESPONSABLES') {
					echo "<p style='color:red;'>ERREUR: Le fichier XML fourni n'a pas l'air d'�tre un fichier XML Responsables.<br />Sa racine devrait �tre 'BEE_RESPONSABLES'.</p>\n";
					require("../lib/footer.inc.php");
					die();
				}


				$sql="DROP TABLE IF EXISTS temp_responsables2_import;";
				info_debug($sql);
				$nettoyage = mysql_query($sql);

				//$sql="CREATE TABLE IF NOT EXISTS responsables2 (
				$sql="CREATE TABLE IF NOT EXISTS temp_responsables2_import (
						`ele_id` varchar(10) $chaine_mysql_collate NOT NULL,
						`pers_id` varchar(10) $chaine_mysql_collate NOT NULL,
						`resp_legal` varchar(1) $chaine_mysql_collate NOT NULL,
						`pers_contact` varchar(1) $chaine_mysql_collate NOT NULL
						);";
				info_debug($sql);
				$create_table = mysql_query($sql);

				$sql="TRUNCATE TABLE temp_responsables2_import;";
				//$sql="TRUNCATE TABLE responsables2;";
				info_debug($sql);
				$vide_table = mysql_query($sql);

				/*
				echo "<p>Lecture du fichier Responsables...<br />\n";
				while(!feof($fp)){
					$ligne[]=fgets($fp,4096);
				}
				fclose($fp);
				*/
				flush();

				echo "<p>";
				echo "Analyse du fichier pour extraire les informations de la section RESPONSABLES...<br />\n";

				$responsables=array();

				$tab_champs_responsable=array("ELEVE_ID",
				"PERSONNE_ID",
				"RESP_LEGAL",
				"CODE_PARENTE",
				"RESP_FINANCIER",
				"PERS_PAIMENT",
				"PERS_CONTACT"
				);

				// PARTIE <RESPONSABLES>
				// Compteur responsables:
				$i=-1;

				$objet_resp=($resp_xml->DONNEES->RESPONSABLES);
				foreach ($objet_resp->children() as $responsable_eleve) {
					//echo("<p><b>Personne</b><br />");

					$i++;
					$responsables[$i]=array();

					foreach($responsable_eleve->children() as $key => $value) {
						if(in_array(strtoupper($key),$tab_champs_responsable)) {
							$responsables[$i][strtolower($key)]=traitement_magic_quotes(corriger_caracteres(preg_replace('/"/','',trim(traite_utf8($value)))));
							//echo "\$structure->$key=".$value."<br />";
						}
					}

					if($debug_import=='y') {
						echo "<pre style='color:green;'><b>Tableau \$responsables[$i]&nbsp;:</b>";
						print_r($responsables[$i]);
						echo "</pre>";
					}
				}

				$nb_err=0;
				$stat=0;
				$i=0;
				while($i<count($responsables)){
					$sql="INSERT INTO temp_responsables2_import SET ";
					//$sql="INSERT INTO responsables2 SET ";
					$sql.="ele_id='".$responsables[$i]["eleve_id"]."', ";
					$sql.="pers_id='".$responsables[$i]["personne_id"]."', ";
					$sql.="resp_legal='".$responsables[$i]["resp_legal"]."', ";
					$sql.="pers_contact='".$responsables[$i]["pers_contact"]."';";
					affiche_debug("$sql<br />\n");
					info_debug($sql);
					$res_insert=mysql_query($sql);
					if(!$res_insert){
						echo "Erreur lors de la requ�te $sql<br />\n";
						flush();
						$nb_err++;
					}
					else{
						$stat++;
					}

					$i++;
				}

				echo "<p><br /></p>\n";

				if ($nb_err!=0) {
					echo "<p>Lors de l'enregistrement des donn�es de RESPONSABLES, il y a eu $nb_err erreurs. Essayez de trouvez la cause de l'erreur et recommencez la proc�dure avant de passer � l'�tape suivante.</p>\n";
				}
				else {
					echo "<p>L'importation des relations eleves/responsables dans la base GEPI a �t� effectu�e avec succ�s (".$stat." enregistrements au total).</p>\n";

					echo "<script type='text/javascript'>
	/*
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}
	if(stop=='n'){
		setTimeout(\"document.location.replace('".$_SERVER['PHP_SELF']."?step=12')\",2000);
	}
	*/
	setTimeout(\"test_stop('12')\",3000);
</script>\n";
				}

				//echo "<p>$stat enregistrement(s) ont �t� ins�r�(s) dans la table 'temp_responsables2_import'.</p>\n";

				//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=12&amp;stop=$stop'>Suite</a></p>\n";
				echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=12&amp;stop=$stop' onClick=\"test_stop_suite('12'); return false;\">Suite</a></p>\n";

				require("../lib/footer.inc.php");
				die();
			//}

			break;
		case "12":
			echo "<h2>Import/mise � jour des responsables</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			$dest_file="../temp/".$tempdir."/responsables.xml";

			/*
			$fp=fopen($dest_file,"r");
			if(!$fp){
				echo "<p>Le XML responsables n'a pas l'air pr�sent dans le dossier temporaire.<br />Auriez-vous saut� une �tape???</p>\n";
				require("../lib/footer.inc.php");
				die();
			}
			else{
			*/

				$resp_xml=simplexml_load_file($dest_file);
				if(!$resp_xml) {
					echo "<p style='color:red;'>ECHEC du chargement du fichier avec simpleXML.</p>\n";
					require("../lib/footer.inc.php");
					die();
				}

				$nom_racine=$resp_xml->getName();
				if(strtoupper($nom_racine)!='BEE_RESPONSABLES') {
					echo "<p style='color:red;'>ERREUR: Le fichier XML fourni n'a pas l'air d'�tre un fichier XML Responsables.<br />Sa racine devrait �tre 'BEE_RESPONSABLES'.</p>\n";
					require("../lib/footer.inc.php");
					die();
				}

				$sql="DROP TABLE IF EXISTS temp_resp_adr_import;";
				info_debug($sql);
				$nettoyage = mysql_query($sql);

				$sql="CREATE TABLE IF NOT EXISTS temp_resp_adr_import (
						`adr_id` varchar(10) $chaine_mysql_collate NOT NULL,
						`adr1` varchar(100) $chaine_mysql_collate NOT NULL,
						`adr2` varchar(100) $chaine_mysql_collate NOT NULL,
						`adr3` varchar(100) $chaine_mysql_collate NOT NULL,
						`adr4` varchar(100) $chaine_mysql_collate NOT NULL,
						`cp` varchar(6) $chaine_mysql_collate NOT NULL,
						`pays` varchar(50) $chaine_mysql_collate NOT NULL,
						`commune` varchar(50) $chaine_mysql_collate NOT NULL,
						`statut` varchar(100) $chaine_mysql_collate NOT NULL,
					PRIMARY KEY  (`adr_id`));";
				info_debug($sql);
				//echo "$sql<br />";
				$create_table = mysql_query($sql);

				$sql="TRUNCATE TABLE temp_resp_adr_import;";
				//$sql="TRUNCATE TABLE resp_adr;";
				info_debug($sql);
				$vide_table = mysql_query($sql);

				/*
				echo "<p>Lecture du fichier Responsables...<br />\n";
				while(!feof($fp)){
					$ligne[]=fgets($fp,4096);
				}
				fclose($fp);
				*/
				flush();

				echo "Analyse du fichier pour extraire les informations de la section ADRESSES...<br />\n";

				$adresses=array();

				$tab_champs_adresse=array("LIGNE1_ADRESSE",
				"LIGNE2_ADRESSE",
				"LIGNE3_ADRESSE",
				"LIGNE4_ADRESSE",
				"CODE_POSTAL",
				"LL_PAYS",
				"CODE_DEPARTEMENT",
				"LIBELLE_POSTAL",
				"COMMUNE_ETRANGERE"
				);

				// PARTIE <ADRESSES>
				// Compteur adresses:
				$i=-1;

				$objet_adresses=($resp_xml->DONNEES->ADRESSES);
				foreach ($objet_adresses->children() as $adresse) {
					//echo("<p><b>Adresse</b><br />");

					$i++;
					$adresses[$i]=array();

					foreach($adresse->attributes() as $key => $value) {
						// <ADRESSE ADRESSE_ID="228114">
						$adresses[$i][strtolower($key)]=$value;
					}

					foreach($adresse->children() as $key => $value) {
						if(in_array(strtoupper($key),$tab_champs_adresse)) {
							$adresses[$i][strtolower($key)]=traitement_magic_quotes(corriger_caracteres(preg_replace('/"/','',trim(traite_utf8($value)))));
							//echo "\$structure->$key=".$value."<br />";
						}
					}

					if($debug_import=='y') {
						echo "<pre style='color:green;'><b>Tableau \$adresses[$i]&nbsp;:</b>";
						print_r($adresses[$i]);
						echo "</pre>";
					}
				}

				$nb_err=0;
				$stat=0;
				$i=0;
				while($i<count($adresses)){
					$sql="INSERT INTO temp_resp_adr_import SET ";
					//$sql="INSERT INTO resp_adr SET ";
					$sql.="adr_id='".$adresses[$i]["adresse_id"]."', ";
					if(isset($adresses[$i]["ligne1_adresse"])){
						$sql.="adr1='".$adresses[$i]["ligne1_adresse"]."', ";
					}
					if(isset($adresses[$i]["ligne2_adresse"])){
						$sql.="adr2='".$adresses[$i]["ligne2_adresse"]."', ";
					}
					if(isset($adresses[$i]["ligne3_adresse"])){
						$sql.="adr3='".$adresses[$i]["ligne3_adresse"]."', ";
					}
					if(isset($adresses[$i]["ligne4_adresse"])){
						$sql.="adr4='".$adresses[$i]["ligne4_adresse"]."', ";
					}
					if(isset($adresses[$i]["code_postal"])){
						$sql.="cp='".$adresses[$i]["code_postal"]."', ";
					}
					if(isset($adresses[$i]["ll_pays"])){
						$sql.="pays='".$adresses[$i]["ll_pays"]."', ";
					}
					if(isset($adresses[$i]["libelle_postal"])){
						$sql.="commune='".$adresses[$i]["libelle_postal"]."', ";
					} elseif(isset($adresses[$i]["commune_etrangere"])) {
						$sql.="commune='".$adresses[$i]["commune_etrangere"]."', ";
					}
					$sql=substr($sql,0,strlen($sql)-2);
					$sql.=";";
					affiche_debug("$sql<br />\n");
					info_debug($sql);
					$res_insert=mysql_query($sql);
					if(!$res_insert){
						echo "Erreur lors de la requ�te $sql<br />\n";
						flush();
						$nb_err++;
					}
					else{
						$stat++;
					}

					$i++;
				}

				echo "<p><br /></p>\n";

				if ($nb_err != 0) {
					echo "<p>Lors de l'enregistrement des donn�es ADRESSES des responsables, il y a eu $nb_err erreurs. Essayez de trouvez la cause de l'erreur et recommencez la proc�dure avant de passer � l'�tape suivante.</p>\n";
				} else {
					echo "<p>L'importation des adresses de responsables dans la base GEPI a �t� effectu�e avec succ�s (".$stat." enregistrements au total).</p>\n";

					echo "<script type='text/javascript'>
	/*
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}
	if(stop=='n'){
		setTimeout(\"document.location.replace('".$_SERVER['PHP_SELF']."?step=13')\",2000);
	}
	*/
	setTimeout(\"test_stop('13')\",3000);
</script>\n";
				}
				//echo "<p>$stat enregistrement(s) ont �t� mis � jour dans la table 'temp_resp_adr_import'.</p>\n";

				//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=13&amp;stop=$stop'>Suite</a></p>\n";
				echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=13&amp;stop=$stop' onClick=\"test_stop_suite('13'); return false;\">Suite</a></p>\n";

				require("../lib/footer.inc.php");
				die();
			//}
			break;
		case "13":
			// On va commencer les comparaisons...
			// - resp_pers
			// - resp_adr en rappelant la liste des personnes auxquelles l'adresse est rattach�e...
			//     . enchainer avec une proposition de nettoyage des adresses qui ne sont plus rattach�es � personne
			// - responsables2:
			//     . Nouvelles responsabilit�s
			//     . Responsabilit�s supprim�es

			echo "<h2>Import/mise � jour des responsables</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			if(file_exists("../temp/".$tempdir."/responsables.xml")) {
				echo "<p>Suppression du fichier responsables.xml... ";
				if(unlink("../temp/".$tempdir."/responsables.xml")){
					echo "r�ussie.</p>\n";
				}
				else{
					echo "<font color='red'>Echec!</font> V�rifiez les droits d'�criture sur le serveur.</p>\n";
				}
			}

			echo "<h3>Section PERSONNES</h3>\n";

			echo "<form action='".$_SERVER['PHP_SELF']."' name='formulaire' method='post'>\n";
			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//==============================


			if(!isset($parcours_diff)){
				info_debug("==================================================");
				info_debug("Avant parcours_diff PERSONNES");
				echo "<p>On va commencer les comparaisons...</p>\n";

				$sql="SELECT COUNT(pers_id) AS nb_pers FROM temp_resp_pers_import;";
				info_debug($sql);
				$res0=mysql_query($sql);
				$lig=mysql_fetch_object($res0);

				$nb_pers=$lig->nb_pers;

				echo "<p>Les ".$nb_pers." personnes responsables vont �tre parcourus par tranches de 20 � la recherche de diff�rences.</p>\n";

				$nb_parcours=ceil($nb_pers/20);
			}
			$num_tranche=isset($_POST['num_tranche']) ? $_POST['num_tranche'] : 1;
			echo "<input type='hidden' name='nb_parcours' value='$nb_parcours' />\n";



			//echo "<input type='hidden' name='num_tranche'value='$num_tranche' />\n";

			//echo "<p>Parcours de la tranche <b>$num_tranche/$nb_parcours</b>.</p>\n";
			//flush();

			$sql="SELECT pers_id FROM temp_resp_pers_import WHERE statut='' LIMIT 20;";
			//echo "$sql<br />";
			info_debug($sql);
			$res1=mysql_query($sql);
			//echo "mysql_num_rows(\$res1)=".mysql_num_rows($res1)."<br />";

			if(mysql_num_rows($res1)==0) {
				// On a termin� le parcours
				echo "<p>Le parcours des diff�rences concernant les personnes est termin�.</p>\n";
				info_debug("parcours_diff personnes termin�");

				// On stocke dans la table tempo2 la liste des pers_id pour lesquels un changement a eu lieu:
				$sql="TRUNCATE TABLE tempo2;";
				info_debug($sql);
				$res0=mysql_query($sql);

				//=======================================================
				// STOCKAGE DES pers_id DISPARUS DE temp_resp_pers_import
				$sql="insert into tempo2 SELECT rp.pers_id,rp.pers_id FROM resp_pers rp WHERE rp.pers_id NOT IN (SELECT pers_id FROM temp_resp_pers_import);";
				info_debug($sql);
				$insert=mysql_query($sql);
				$sql="UPDATE tempo2 SET col1='pers_id_disparu';";
				info_debug($sql);
				$update=mysql_query($sql);
				//=======================================================

				$sql="SELECT pers_id FROM temp_resp_pers_import WHERE statut='nouveau' OR statut='modif';";
				//echo "$sql<br />";
				info_debug($sql);
				$res2=mysql_query($sql);
				if(mysql_num_rows($res2)>0) {
					while($lig2=mysql_fetch_object($res2)) {
						$sql="INSERT INTO tempo2 SET col1='pers_id', col2='$lig2->pers_id'";
						info_debug($sql);
						$insert=mysql_query($sql);
					}
				}

				info_debug("fin du remplissage de tempo2");

				echo "<input type='hidden' name='step' value='14' />\n";
				//echo "<p><input type='submit' value='Afficher les diff�rences' /></p>\n";
				echo "<p><input type='submit' value=\"Parcourir les diff�rences d'adresses\" /></p>\n";

				echo "<script type='text/javascript'>
	/*
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}
	if(stop=='n'){
		setTimeout(\"document.forms['formulaire'].submit();\",5000);
	}
	*/
	setTimeout(\"test_stop2()\",3000);
</script>\n";

				info_debug("==================================================");
			}
			else {
				info_debug("========================");
				info_debug("parcours de la tranche $num_tranche/$nb_parcours");
				echo "<p>Parcours de la tranche <b>$num_tranche/$nb_parcours</b>.</p>\n";

				echo "<input type='hidden' name='num_tranche'value='$num_tranche' />\n";

				// Afficher les diff�rences d�j� trouv�es...
				$sql="SELECT COUNT(pers_id) AS nb_nouveau FROM temp_resp_pers_import WHERE statut='nouveau';";
				info_debug($sql);
				$res0=mysql_query($sql);
				$lig=mysql_fetch_object($res0);
				$nb_nouveau=$lig->nb_nouveau;
				if($nb_nouveau!=0) {echo "<p>$nb_nouveau nouveau(x) trouv�(s) auparavant.</p>\n";}

				$sql="SELECT COUNT(pers_id) AS nb_modif FROM temp_resp_pers_import WHERE statut='modif';";
				info_debug($sql);
				$res0=mysql_query($sql);
				$lig=mysql_fetch_object($res0);
				$nb_modif=$lig->nb_modif;
				if($nb_modif!=0) {echo "<p>$nb_modif modification(s) trouv�e(s) auparavant.</p>\n";}

				flush();


				echo "<p>Recherche des diff�rences sur la tranche parcourue: ";

				$cpt=0;
				//$chaine_nouveaux="";
				while($lig=mysql_fetch_object($res1)){
					$sql="SELECT 1=1 FROM resp_pers rp, temp_resp_pers_import t WHERE rp.pers_id=t.pers_id AND t.pers_id='$lig->pers_id'";
					info_debug($sql);
					$test=mysql_query($sql);
					info_debug("Test diff $lig->pers_id");
					if(mysql_num_rows($test)==0){
						// On ne va consid�rer comme nouveau responsable qu'une personne associ�e � un �l�ve effectivement accept� dans la table 'eleves':
						info_debug("$lig->pers_id semble �tre un nouveau");
						$sql="SELECT 1=1 FROM temp_resp_pers_import trp,
												temp_responsables2_import tr,
												eleves e
										WHERE trp.pers_id='$lig->pers_id' AND
												trp.pers_id=tr.pers_id AND
												tr.ele_id=e.ele_id";
						//echo "$sql<br />";
						info_debug($sql);
						//$test=mysql_query($sql);

						if(!$test=mysql_query($sql)) {
							echo "<p>Une <span style='color:red;'>erreur</span> s'est produite sur la requ�te&nbsp;:<br /><span style='color:green;'>".$sql."</span><br />\n";
							//Illegal mix of collations
							if(my_eregi("Illegal mix of collations",mysql_error())) {
								//echo "<span style='color:red'>".mysql_error()."</span>\n";
								echo "Il semble qu'il y ait un probl�me de 'collation' entre les champs 'eleves.ele_id' et 'temp_responsables2_import.ele_id'&nbsp;:<br />\n";
								echo "<span style='color:red'>".mysql_error()."</span><br />\n";
								echo "Il faudrait supprimer la table 'temp_responsables2_import', renseigner la valeur de 'mysql_collate' dans la table 'setting' en mettant la m�me collation que pour votre champ 'eleves.ele_id'.<br />\n";
								echo "Si par exemple, le champ 'eleves.ele_id' a pour collation 'latin1_general_ci', il faudrait ex�cuter une requ�te du type <span style='color:green;'>INSERT INTO setting SET name='mysql_collate', value='latin1_general_ci';</span> ou si la valeur existe d�j� <span style='color:green;'>UPDATE setting SET value='latin1_general_ci' WHERE name='mysql_collate';</span><br />\n";
							}
							echo "</p>\n";
		
							require("../lib/footer.inc.php");
							die();
						}


						if(mysql_num_rows($test)>0){
							info_debug("$lig->pers_id est bien un nouveau");
							if($cpt>0){
								//$chaine_nouveaux.=", ";
								echo ", ";
							}
							//$chaine_nouveaux.=$lig->pers_id;
							echo "<span style='color:blue;'>".$lig->pers_id."</span>";
							//echo "<input type='hidden' name='tab_pers_id_diff[]' value='$lig->pers_id' />\n";
							$sql="UPDATE temp_resp_pers_import SET statut='nouveau' WHERE pers_id='$lig->pers_id';";
							info_debug($sql);
							//echo "$sql<br />";
							$update=mysql_query($sql);
							$cpt++;
						}
						else {
							info_debug("$lig->pers_id n'est associ� � personne");
							// Ce 'nouveau' responsable n'est associ� � aucun �l�ve de 'eleves'...
							// Pour ne pas laisser le statut vide (signe qu'on n'a pas encore test� ce pers_id):
							$sql="UPDATE temp_resp_pers_import SET statut='-' WHERE pers_id='$lig->pers_id';";
							info_debug($sql);
							$update=mysql_query($sql);
						}
					}
					else{
						info_debug("$lig->pers_id est d�j� dans resp_pers");
						//$tab_pers_id[]=$lig->pers_id;
						//$sql="SELECT rp.pers_id FROM resp_pers rp, temp_resp_pers_import t
						$sql="SELECT 1=1 FROM resp_pers rp, temp_resp_pers_import t
										WHERE rp.pers_id=t.pers_id AND
												(
													rp.nom!=t.nom OR
													rp.prenom!=t.prenom OR
													rp.civilite!=t.civilite OR
													rp.tel_pers!=t.tel_pers OR
													rp.tel_port!=t.tel_port OR
													rp.tel_prof!=t.tel_prof OR";
						//if((getSettingValue('mode_email_resp')=='')||(getSettingValue('mode_email_resp')=='sconet')) {
						if((getSettingValue('mode_email_resp')=='')||(getSettingValue('mode_email_resp')=='sconet')) {
							$sql.="						rp.mel!=t.mel OR";
						}
						$sql.="						rp.adr_id!=t.adr_id
												)
												AND rp.pers_id='".$lig->pers_id."';";
						//echo "$sql<br />\n";
						info_debug($sql);
						//$test=mysql_query($sql);
						if(!$test=mysql_query($sql)) {
							echo "<p>Une <span style='color:red;'>erreur</span> s'est produite sur la requ�te&nbsp;:<br /><span style='color:green;'>".$sql."</span><br />\n";
							//Illegal mix of collations
							if(my_eregi("Illegal mix of collations",mysql_error())) {
								//echo "<span style='color:red'>".mysql_error()."</span>\n";
								echo "Il semble qu'il y ait un probl�me de 'collation' entre les tables 'resp_pers' et 'temp_resp_pers_import'&nbsp;:<br />\n";
								echo "<span style='color:red'>".mysql_error()."</span><br />\n";
								echo "Il faudrait supprimer la table 'temp_resp_pers_import', renseigner la valeur de 'mysql_collate' dans la table 'setting' en mettant la m�me collation que pour vos champs 'resp_pers'.<br />\n";
								echo "Si par exemple, les champs de 'temp_resp_pers_import' ont pour collation 'latin1_general_ci', il faudrait ex�cuter une requ�te du type <span style='color:green;'>INSERT INTO setting SET name='mysql_collate', value='latin1_general_ci';</span> ou si la valeur existe d�j� <span style='color:green;'>UPDATE setting SET value='latin1_general_ci' WHERE name='mysql_collate';</span><br />\n";
							}
							echo "</p>\n";
		
							require("../lib/footer.inc.php");
							die();
						}

						if(mysql_num_rows($test)>0){
							info_debug("... avec une diff au moins dans resp_pers");
							if($cpt>0) {
								echo ", ";
							}

							echo "<span style='color:green;'>".$lig->pers_id."</span>";
							//echo "<input type='hidden' name='tab_pers_id_diff[]' value='$lig->pers_id' />\n";
							$sql="UPDATE temp_resp_pers_import SET statut='modif' WHERE pers_id='$lig->pers_id';";
							info_debug($sql);
							$update=mysql_query($sql);
							$cpt++;
						}
						else {
							info_debug("... sans diff dans resp_pers");
							// Pour ne pas laisser le statut vide (signe qu'on n'a pas encore test� ce pers_id):
							$sql="UPDATE temp_resp_pers_import SET statut='-' WHERE pers_id='$lig->pers_id';";
							info_debug($sql);
							$update=mysql_query($sql);
						}
					}
				}

				$num_tranche++;
				echo "<input type='hidden' name='num_tranche'value='$num_tranche' />\n";

				echo "<input type='hidden' name='parcours_diff' value='$parcours_diff' />\n";
				echo "<input type='hidden' name='step' value='13' />\n";
				echo "<p><input type='submit' value='Suite' /></p>\n";

				echo "<script type='text/javascript'>
	/*
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}
	if(stop=='n'){
		setTimeout(\"document.forms['formulaire'].submit();\",1000);
	}
	*/
	setTimeout(\"test_stop2()\",3000);
</script>\n";

			}


			echo "</form>\n";


			break;

		/*
		// INSERER A CE NIVEAU DES TESTS SUPPLEMENTAIRES
		case "13b":
			// 20090331

			// Remplir une table temporaire avec les membres de resp_pers et chercher s'ils sont toujours dans temp_resp_pers
			// S'ils n'y sont pas, les noter comme 'suppr' ou 'disparu' dans 
			//$sql="UPDATE temp_resp_pers_import SET statut='disparu' WHERE pers_id='$lig->pers_id';";

			//Boucle sur $cpt avec
			//$sql="SELECT pers_id FROM resp_pers LIMIT $cpt,20";
			// Et remplir une table temporaire... puis passer en revue la table temporaire

			// Ou:
			// INSERT INTO tempo3 SELECT pers_id FROM resp_pers;
			// Ou s'il faut plusieurs champs dans tempo3:
			// INSERT INTO tempo3 SELECT pers_id,autre_champ FROM resp_pers;
			// Et si le pers_id n'est pas dans temp_resp_pers, inscrire dans tempo2 pers_id,$pers_id et quand on ne trouve pas le pers_id par la sutie dans temp_resp_pers, c'est qu'on a une suppression... ou stocker plus pr�cis�ment l'info ailleurs
			// Conserver les infos dans la table tempo3 (vider au fur et � mesure la table tempo3 quand le pers_id est dans temp_resp_pers

			echo "<form action='".$_SERVER['PHP_SELF']."' name='formulaire' method='post'>\n";
			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//==============================

			if(!isset($_POST['cpt'])) {
				//$sql="INSERT INTO tempo3 SELECT pers_id,autre_champ FROM resp_pers;";
				$cpt=0;
			}
			else {
				$cpt=$_POST['cpt'];
				//$sql="SELECT "
			}

			$sql="SELECT pers_id FROM resp_pers LIMIT $cpt,100";
			$res=mysql_query($sql);
			if(mysql_num_rows($res)>0) {
				while($lig=mysql_fetch_object($res)) {


				}
			}
			else {
				// FIN DU PARCOURS
			}




			$cpt+=100;
			echo "<input type='hidden' name='cpt' value='$cpt' />\n";

			echo "</form>\n";
			break;
		*/


		case "14":
			// DEBUG:
			//echo "step=$step<br />";
			//debug_var();

			echo "<h2>Import/mise � jour des responsables</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			echo "<h3>Section ADRESSES</h3>\n";

			echo "<form action='".$_SERVER['PHP_SELF']."' name='formulaire' method='post'>\n";
			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//==============================



			if(!isset($parcours_diff)){
				info_debug("=======================================================");
				info_debug("Avant parcours_diff ADRESSES");
				echo "<p>On va commencer les comparaisons...</p>\n";

				$sql="SELECT COUNT(adr_id) AS nb_adr FROM temp_resp_adr_import;";
				info_debug($sql);
				$res0=mysql_query($sql);
				$lig=mysql_fetch_object($res0);

				$nb_adr=$lig->nb_adr;

				echo "<p>Les ".$nb_adr." adresses de personnes responsables vont �tre parcourues par tranches de 20 � la recherche de diff�rences.</p>\n";

				$nb_parcours=ceil($nb_adr/20);
			}
			$num_tranche=isset($_POST['num_tranche']) ? $_POST['num_tranche'] : 1;
			echo "<input type='hidden' name='nb_parcours' value='$nb_parcours' />\n";



			//echo "<input type='hidden' name='num_tranche'value='$num_tranche' />\n";

			//echo "<p>Parcours de la tranche <b>$num_tranche/$nb_parcours</b>.</p>\n";
			//flush();


			$sql="SELECT DISTINCT adr_id FROM temp_resp_adr_import WHERE statut='' LIMIT 20;";
			info_debug($sql);
			//echo "$sql<br />";
			$res1=mysql_query($sql);
			//echo "mysql_num_rows(\$res1)=".mysql_num_rows($res1)."<br />";

			if(mysql_num_rows($res1)==0) {
				info_debug("Fin parcours_diff adresses");
				// On a termin� le parcours
				echo "<p>Le parcours des diff�rences concernant les personnes est termin�.</p>\n";

				flush();

				$sql="SELECT adr_id FROM temp_resp_adr_import WHERE statut='nouveau' OR statut='modif';";
				info_debug($sql);
				//echo "$sql<br />";
				$res2=mysql_query($sql);
				if(mysql_num_rows($res2)>0) {
					info_debug(mysql_num_rows($res2)." nouvelles adresses ou modifs...");
					while($lig2=mysql_fetch_object($res2)) {

						$sql="SELECT DISTINCT pers_id FROM resp_pers WHERE adr_id='".$lig2->adr_id."';";
						info_debug($sql);
						$test=mysql_query($sql);

						if(mysql_num_rows($test)>0){
							while($lig3=mysql_fetch_object($test)){
								$sql="INSERT INTO tempo2 SET col1='pers_id', col2='".$lig3->pers_id."';";
								info_debug($sql);
								$insert=mysql_query($sql);
								info_debug("Modif adresse $lig2->adr_id pour resp_pers.pers_id=$lig3->pers_id");
							}
						}
						else{
							$sql="SELECT DISTINCT pers_id FROM temp_resp_pers_import WHERE adr_id='".$lig2->adr_id."';";
							info_debug($sql);
							$test=mysql_query($sql);

							if(mysql_num_rows($test)>0){
								while($lig3=mysql_fetch_object($test)){
									$sql="INSERT INTO tempo2 SET col1='pers_id', col2='$lig3->pers_id'";
									info_debug($sql);
									$insert=mysql_query($sql);
									info_debug("Nouvelle adresse $lig2->adr_id pour temp_resp_pers_import.pers_id=$lig3->pers_id");
								}
							}
							// Les doublons importent peu.
							// On fait des recherches en DISTINCT par la suite.
						}
					}
				}



				if($ne_pas_proposer_resp_sans_eleve=="si"){
					//echo "<input type='hidden' name='step' value='15' />\n";
					echo "<input type='hidden' name='step' value='16' />\n";
					echo "<p><input type='submit' value='Afficher les diff�rences' /></p>\n";
				}
				else{
					//echo "<input type='hidden' name='step' value='a15' />\n";
					$sql="SELECT 1=1 FROM tempo2 WHERE col1='pers_id_disparu' LIMIT 1;";
					info_debug($sql);
					$test=mysql_query($sql);
					if (mysql_num_rows($test)>0) {
						echo "<input type='hidden' name='step' value='14b' />\n";
					}
					else {
						echo "<input type='hidden' name='step' value='15' />\n";
					}
					echo "<p><input type='submit' value='Effectuer un nettoyage avant affichage des diff�rences' /></p>\n";
				}

				echo "<script type='text/javascript'>
	/*
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}
	if(stop=='n'){
		setTimeout(\"document.forms['formulaire'].submit();\",5000);
	}
	*/
	setTimeout(\"test_stop2()\",3000);
</script>\n";


				info_debug("==================================================");
			}
			else {
				info_debug("========================");
				echo "<p>Parcours de la tranche <b>$num_tranche/$nb_parcours</b>.</p>\n";
				info_debug("Parcours de la tranche $num_tranche/$nb_parcours");

				echo "<input type='hidden' name='num_tranche'value='$num_tranche' />\n";

				// Afficher les diff�rences d�j� trouv�es...
				$sql="SELECT COUNT(adr_id) AS nb_nouveau FROM temp_resp_adr_import WHERE statut='nouveau';";
				info_debug($sql);
				$res0=mysql_query($sql);
				$lig=mysql_fetch_object($res0);
				$nb_nouveau=$lig->nb_nouveau;
				if($nb_nouveau!=0) {echo "<p>$nb_nouveau nouveau(x) trouv�(s) auparavant.</p>\n";}

				$sql="SELECT COUNT(adr_id) AS nb_modif FROM temp_resp_adr_import WHERE statut='modif';";
				info_debug($sql);
				$res0=mysql_query($sql);
				$lig=mysql_fetch_object($res0);
				$nb_modif=$lig->nb_modif;
				if($nb_modif!=0) {echo "<p>$nb_modif modification(s) trouv�e(s) auparavant.</p>\n";}

				flush();


				echo "<p>Recherche des diff�rences sur la tranche parcourue: ";

				$cpt=0;
				while($lig=mysql_fetch_object($res1)){
					//$time1=time();
					// Est-ce une nouvelle adresse responsable?
					$sql="SELECT 1=1 FROM resp_adr ra WHERE ra.adr_id='$lig->adr_id'";
					info_debug($sql);
					$test1=mysql_query($sql);

					if(mysql_num_rows($test1)==0){
						// L'adresse est nouvelle, mais on n'a pas v�rifi� � ce stade si elle est bien associ�e � une personne
						if($cpt>0){
							echo ", ";
						}
						echo "<span style='color:blue;'>".$lig->adr_id."</span>";
						$sql="UPDATE temp_resp_adr_import SET statut='nouveau' WHERE adr_id='$lig->adr_id';";
						//echo "$sql<br />";
						info_debug($sql);
						$update=mysql_query($sql);

						info_debug("Nouvelle adresse adr_id=$lig->adr_id");

						$cpt++;
					}
					else {
						$debug_time=time();
						$sql="SELECT ra.adr_id FROM resp_adr ra, temp_resp_adr_import t
										WHERE ra.adr_id=t.adr_id AND
												(
													ra.adr1!=t.adr1 OR
													ra.adr2!=t.adr2 OR
													ra.adr3!=t.adr3 OR
													ra.adr4!=t.adr4 OR
													ra.cp!=t.cp OR
													ra.commune!=t.commune OR
													ra.pays!=t.pays
												)
												AND ra.adr_id='".$lig->adr_id."';";
						//echo "$sql<br />\n";
						info_debug($sql);
						$test=mysql_query($sql);
						$diff_debug_time=time()-$debug_time;
						info_debug("Test modif adr_id=$lig->adr_id (dur�e: $diff_debug_time)");
						if(mysql_num_rows($test)>0){
							if($cpt>0){
								echo ", ";
							}
							echo "<span style='color:green;'>".$lig->adr_id."</span>";
							$sql="UPDATE temp_resp_adr_import SET statut='modif' WHERE adr_id='$lig->adr_id';";
							info_debug($sql);
							//echo "$sql<br />";
							$update=mysql_query($sql);
							info_debug("Adresse modifi�e adr_id=$lig->adr_id");
							$cpt++;
						}
						else {
							// Pas de diff�rence sur l'adresse
							// Pour ne pas laisser le statut vide (signe qu'on n'a pas encore test� ce pers_id):
							$sql="UPDATE temp_resp_adr_import SET statut='-' WHERE adr_id='$lig->adr_id';";
							info_debug($sql);
							$update=mysql_query($sql);
							info_debug("Adresse adr_id=$lig->adr_id inchang�e.");
						}
					}
					flush();
				}

				$num_tranche++;
				echo "<input type='hidden' name='num_tranche'value='$num_tranche' />\n";

				echo "<input type='hidden' name='parcours_diff' value='$parcours_diff' />\n";
				echo "<input type='hidden' name='step' value='14' />\n";
				echo "<p><input type='submit' value='Suite' /></p>\n";

				echo "<script type='text/javascript'>
	/*
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}
	if(stop=='n'){
		setTimeout(\"document.forms['formulaire'].submit();\",1000);
	}
	*/
	setTimeout(\"test_stop2()\",3000);
</script>\n";
			}

			echo "</form>\n";



			break;


		// 20090331
		// INSERER LA LE CONTROLE DES col1=pers_id_disparu DANS tempo2
		case "14b":
			// A l'�tape pr�c�dente passer � 14b s'il y a des col1=pers_id_disparu  et passer � 15 sinon
			echo "<h2>Import/mise � jour des responsables</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>\n";
			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//echo "<input type='hidden' name='step' value='15' />\n";
			echo "<input type='hidden' name='step' value='14c' />\n";
			echo add_token_field();
			//==============================

			$sql="SELECT col2 FROM tempo2 WHERE col1='pers_id_disparu';";
			info_debug($sql);
			$test=mysql_query($sql);
			$nb_disparus=mysql_num_rows($test);

			echo "<p>$nb_disparus responsables pr�sents dans votre table 'resp_pers' ne sont plus pr�sents dans Sconet.<br />Vous allez devoir d�cider si vous souhaitez conserver ces responsables ou si vous voulez les supprimer de votre base.</p>\n";

			echo "<table class='boireaus' summary='Tableau des responsables disparus de Sconet'>\n";

			$ligne_entete_tableau="<tr>\n";
			$ligne_entete_tableau.="<td style='text-align: center; font-weight: bold;'>Supprimer<br />\n";

			$ligne_entete_tableau.="<a href=\"javascript:modifcase('coche')\">";
			$ligne_entete_tableau.="<img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>";
			$ligne_entete_tableau.=" / ";
			$ligne_entete_tableau.="<a href=\"javascript:modifcase('decoche')\">";
			$ligne_entete_tableau.="<img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>";
			$ligne_entete_tableau.="</td>\n";

			$ligne_entete_tableau.="<td style='text-align:center; font-weight: bold;'>Statut</td>\n";

			$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>pers_id</td>\n";
			$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Nom</td>\n";
			$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Pr�nom</td>\n";
			$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Civilit�</td>\n";

			$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: #FAFABE;'>Responsable de</td>\n";
			$ligne_entete_tableau.="</tr>\n";

			// Ent�te du tableau:
			echo $ligne_entete_tableau;

			$alt=1;
			$cpt=0;
			//echo "mysql_num_rows(\$test)=".mysql_num_rows($test)."<br />";
			while($lig1=mysql_fetch_object($test)){
				$pers_id=$lig1->col2;

				$sql="SELECT * FROM resp_pers WHERE pers_id='$pers_id'";
				info_debug($sql);
				$res_pers1=mysql_query($sql);
				if(mysql_num_rows($res_pers1)==0){
					// CA NE DEVRAIT PAS ARRIVER
					echo "<tr style='color:red;'><td colspan='7'>Anomalie: Aucun responsable ne correspond � pers_id=$pers_id</td></tr>\n";
				}
				else{
					$lig_pers1=mysql_fetch_object($res_pers1);

					$nom1=$lig_pers1->nom;
					$prenom1=$lig_pers1->prenom;
					$civilite1=$lig_pers1->civilite;

					$adr_id1=$lig_pers1->adr_id;

					$alt=$alt*(-1);
					$ligne_parent="<tr class='lig$alt'>\n";
	
					$ligne_parent.="<td style='text-align: center;'>\n";
					$ligne_parent.="<input type='checkbox' id='check_".$cpt."' name='valid_pers_id[]' value='$pers_id' />\n";
					$ligne_parent.="<input type='hidden' name='liste_pers_id[]' value='$pers_id' />\n";
					$ligne_parent.="</td>\n";

					$ligne_parent.="<td>Disparu</td>\n";
	
					$ligne_parent.="<td style='text-align:center;'><a href='modify_resp.php?pers_id=$pers_id' target='_blank'>$pers_id</a>";
					$ligne_parent.="</td>\n";
		
					$ligne_parent.="<td>";
					$ligne_parent.=stripslashes($nom1);
					$ligne_parent.="</td>\n";
	
					$ligne_parent.="<td>";
					$ligne_parent.=stripslashes($prenom1);
					$ligne_parent.="</td>\n";

					$ligne_parent.="<td>";
					$ligne_parent.=ucfirst($civilite1);
					$ligne_parent.="</td>\n";

					$ligne_parent.="<td>\n";
					$sql="SELECT e.login, e.nom, e.prenom, r.resp_legal FROM eleves e, responsables2 r WHERE r.pers_id='$pers_id' AND r.ele_id=e.ele_id ORDER BY e.prenom;";
					info_debug($sql);
					//$ligne_parent.="$sql<br />";
					$res_ele=mysql_query($sql);
					if(mysql_num_rows($res_ele)==0) {
						//$ligne_parent.="&nbsp;\n";
						$ligne_parent.="<span style='color:red;'>X</span>\n";
					}
					else {
						$cpt_tmp=0;
						while($lig2=mysql_fetch_object($res_ele)){
							if($cpt_tmp>0) {$ligne_parent.="<br />\n";}
							$tmp_classes=get_class_from_ele_login($lig2->login);
							if(isset($tmp_classes['liste'])) {
								$info_classe=$tmp_classes['liste'];
							}
							else {
								$info_classe='Aucune classe';
							}
							if($lig2->resp_legal==0) {$ligne_parent.="<span style='font-size:x-small;'>";}
							$ligne_parent.="$lig2->nom $lig2->prenom (".$info_classe.")";
							if($lig2->resp_legal==0) {$ligne_parent.="</span>";}
							$cpt_tmp++;
						}
					}
					$ligne_parent.="</td>\n";
	
					$ligne_parent.="</tr>\n";

					echo $ligne_parent;

				}

				$cpt++;
			}

			echo $ligne_entete_tableau;

			echo "</table>\n";

			echo "<script type='text/javascript'>
	function modifcase(mode){
		for(i=0;i<$cpt;i++){
			if(document.getElementById('check_'+i)){
				if(mode=='coche'){
					document.getElementById('check_'+i).checked=true;
				}
				else{
					document.getElementById('check_'+i).checked=false;
				}
			}
		}
	}
</script>\n";

			echo "<p><input type='submit' value='Supprimer les personnes coch�es et passer � la suite' /></p>\n";

			echo "</form>\n";
			break;

		case "14c":
			// 20090401
			// EFFECTUER LES SUPPRESSIONS COCHEES EN SUPRIMANT DANS resp_pers et responsables2

			//debug_var();

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			check_token(false);

			$valid_pers_id=isset($_POST['valid_pers_id']) ? $_POST['valid_pers_id'] : NULL;

			if(is_array($valid_pers_id)) {

				for($i=0;$i<count($valid_pers_id);$i++) {
					$sql="SELECT nom, prenom, civilite FROM resp_pers WHERE pers_id='".$valid_pers_id[$i]."';";
					info_debug($sql);
					$res=mysql_query($sql);
					if(mysql_num_rows($res)==0) {
						echo "<p style='color:red;'>Le responsable n�".$valid_pers_id[$i]." n'existe pas.</p>\n";
					}
					else {
						$lig=mysql_fetch_object($res);
						echo "<p>Suppression du responsable n�".$valid_pers_id[$i].": $lig->civilite ".strtoupper($lig->nom)." ".ucfirst(strtolower($lig->prenom)).":<br />\n";
						// Supprimer les responsabilit�s
						echo "Suppression des responsabilit�s: ";
						$sql="DELETE FROM responsables2 WHERE pers_id='".$valid_pers_id[$i]."';";
						info_debug($sql);
						//echo "$sql<br />\n";
						if(mysql_query($sql)) {echo "<span style='color:green;'>OK</span>";} else {echo "<span style='color:red;'>ERREUR</span>";}

						echo "<br />\n";

						$sql="SELECT u.login, u.statut FROM utilisateurs u, resp_pers rp WHERE u.login=rp.login AND rp.login!='' AND rp.pers_id='".$valid_pers_id[$i]."';";
						$test_utilisateur=mysql_query($sql);
						if(mysql_num_rows($test_utilisateur)>0) {
							$lig_u=mysql_fetch_object($test_utilisateur);
							if($lig_u->statut=='responsable') {
								echo "Suppression du compte d'utilisateur associ� � la personne: ";
								$sql="DELETE FROM utilisateurs WHERE login='".$lig_u->login."';";
								info_debug($sql);
								//echo "$sql<br />\n";
								if(mysql_query($sql)) {echo "<span style='color:green;'>OK</span>";} else {echo "<span style='color:red;'>ERREUR</span>";}
							}
							else {
								echo "<span style='color:red;'>ANOMALIE</span>&nbsp;: Le responsable n�".$valid_pers_id[$i]." �tait associ� au compte d'utilisateur '$lig_u->login' dont le statut est '$lig_u->statut'.<br />Vous devriez chercher comment cela a pu se produire.";
							}
						}
						echo "<br />\n";

						// Supprimer la personne
						echo "Suppression de la personne de la base: ";
						$sql="DELETE FROM resp_pers WHERE pers_id='".$valid_pers_id[$i]."';";
						info_debug($sql);
						//echo "$sql<br />\n";
						if(mysql_query($sql)) {echo "<span style='color:green;'>OK</span>";} else {echo "<span style='color:red;'>ERREUR</span>";}

						echo "</p>\n";
					}
				}

			}
			else {
				echo "<p>Aucune suppression n'a �t� r�percut�e dans la base.</p>\n";
			}

			echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>\n";
			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//echo "<input type='hidden' name='step' value='15' />\n";
			echo "<input type='hidden' name='step' value='15' />\n";
			//==============================
			echo "<p><input type='submit' value='Suite' /></p>\n";
			echo "</form>\n";

			break;

		case "15":
			echo "<h2>Import/mise � jour des responsables</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>\n";
			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//echo "<input type='hidden' name='step' value='15' />\n";
			echo "<input type='hidden' name='step' value='16' />\n";
			//==============================

			$sql="SELECT col2 FROM tempo2 WHERE col1='pers_id';";
			info_debug($sql);
			$test=mysql_query($sql);

			//echo "<p>mysql_num_rows(\$test)=".mysql_num_rows($test)."</p>\n";
			echo "<p>Les ".mysql_num_rows($test)." personnes vont �tre contr�l�es pour s'assurer qu'elles sont bien associ�es � des �l�ves.</p>\n";

			echo "<p>Suppression des responsables fant�mes de la table temporaire: ";
			echo "<span style='font-size:xx-small;'>";
			$cpt=0;
			while($lig=mysql_fetch_object($test)){
				//$sql="SELECT 1=1 FROM temp_resp_pers_import trp,
				$debug_time=time();
				$sql="SELECT trp.nom,trp.prenom FROM temp_resp_pers_import trp,
										temp_responsables2_import tr,
										eleves e
								WHERE trp.pers_id='$lig->col2' AND
										trp.pers_id=tr.pers_id AND
										tr.ele_id=e.ele_id";
				info_debug($sql);
				$test2=mysql_query($sql);
				$diff_debug_time=time()-$debug_time;
				info_debug("Contr�le de pers_id=$lig->col2 (dur�e: $diff_debug_time)");

				if(mysql_num_rows($test2)==0){
					if($cpt>0){echo ", ";}
					//$liste_resp_sans_eleve.="'$pers_id'";
					echo $lig->col2;

					//echo " (<span style='font-size:xx-small;'>$cpt</span>)";

					$sql="DELETE FROM tempo2 WHERE col1='pers_id' AND col2='$lig->col2';";
					info_debug($sql);
					$suppr=mysql_query($sql);

					// On supprime aussi les entr�es dans la table temporaire jointure ele_id/pers_id
					$sql="DELETE FROM temp_responsables2_import WHERE pers_id='$lig->col2';";
					info_debug($sql);
					$suppr=mysql_query($sql);

					$cpt++;
					flush();
				}
			}
			echo "</span>\n";
			echo "</p>\n";

			echo "<p>$cpt fant�me(s) supprim�(s) de la table temporaire.</p>\n";

			$sql="SELECT DISTINCT col2 FROM tempo2 WHERE col1='pers_id';";
			info_debug($sql);
			//echo "$sql<br />";
			$test=mysql_query($sql);
			$nb_tmp_modif=mysql_num_rows($test);
			echo "<p>Parcourir les diff�rences par tranches de <input type='text' name='eff_tranche' value='".min(20,$nb_tmp_modif)."' size='3' /> sur un total de $nb_tmp_modif.<br />\n";

			echo "<input type='submit' value='Afficher les diff�rences' /></p>\n";

			echo "<p><input type='checkbox' name='ne_pas_proposer_redoublonnage_adresse' id='ne_pas_proposer_redoublonnage_adresse' value='y' checked='true' /><label for='ne_pas_proposer_redoublonnage_adresse' style='cursor:pointer;'> Ne pas proposer de r�tablir des doublons d'adresses identiques avec identifiant diff�rent pour des parents qui conservent la m�me adresse.</label></p>\n";

			echo "</form>\n";

			break;

		//case 15:
		case "16":
			echo "<h2>Import/mise � jour des responsables</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			$eff_tranche=isset($_POST['eff_tranche']) ? $_POST['eff_tranche'] : 20;
			if(preg_match("/[^0-9]/",$eff_tranche)) {$eff_tranche=20;}

			$ne_pas_proposer_redoublonnage_adresse=isset($_POST['ne_pas_proposer_redoublonnage_adresse']) ? $_POST['ne_pas_proposer_redoublonnage_adresse'] : "n";

			echo "<form action='".$_SERVER['PHP_SELF']."' method='post'>\n";
			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//==============================
			echo "<input type='hidden' name='ne_pas_proposer_redoublonnage_adresse' value='$ne_pas_proposer_redoublonnage_adresse' />\n";
			echo "<input type='hidden' name='eff_tranche' value='$eff_tranche' />\n";
			echo add_token_field();

			if(!isset($parcours_diff)) {
				info_debug("========================================================");
				//$sql="SELECT 1=1 FROM tempo2 WHERE col1='pers_id';";
				$sql="SELECT DISTINCT col2 FROM tempo2 WHERE col1='pers_id';";
				info_debug($sql);
				//echo "$sql<br />";
				$test=mysql_query($sql);
				//echo "mysql_num_rows(\$test)=".mysql_num_rows($test)."<br />";

				//echo "<p>".count($tab_pers_id_diff)." personnes...</p>\n";

				//echo "<p>".mysql_num_rows($test)." personnes/adresses modifi�es requi�rent votre attention.</p>\n";
				$nb_tmp_modif=mysql_num_rows($test);
				if($nb_tmp_modif==0){
					echo "<p>Aucune modification ne requiert votre attention (<i>personnes/adresses</i>).</p>\n";
				}
				elseif($nb_tmp_modif==1){
					echo "<p>Une personne/adresse modifi�e requiert votre attention.</p>\n";
				}
				else{
					echo "<p>$nb_tmp_modif personnes/adresses modifi�es requi�rent votre attention.</p>\n";
				}

				$sql="SELECT DISTINCT col2 FROM tempo2 WHERE col1='pers_id';";
				info_debug($sql);
				//echo "$sql<br />";
				$test2=mysql_query($sql);
				//echo "mysql_num_rows(\$test2)=".mysql_num_rows($test2)."<br />";

				//echo "<input type='hidden' name='total_pers_diff' value='".count($tab_pers_id_diff)."' />\n";
				echo "<input type='hidden' name='total_pers_diff' value='".mysql_num_rows($test)."' />\n";
			}
			else{
				check_token(false);

				info_debug("========================");
				info_debug("Enregistrement des validations/refus de la tranche...");
				if(isset($valid_pers_id)){
					// On modifie la valeur de col1 pour les pers_id confirm�s pour ne pas les re-parcourir:
					for($i=0;$i<count($valid_pers_id);$i++){
						$sql="UPDATE tempo2 SET col1='pers_id_confirm' WHERE col2='$valid_pers_id[$i]';";
						info_debug($sql);
						$update=mysql_query($sql);
					}

					for($i=0;$i<count($liste_pers_id);$i++){
						if(!in_array($liste_pers_id[$i],$valid_pers_id)){
							$sql="UPDATE tempo2 SET col1='pers_id_refus' WHERE col2='$liste_pers_id[$i]';";
							info_debug($sql);
							$update=mysql_query($sql);
						}
					}
				}
				else{
					if(isset($liste_pers_id)){
						for($i=0;$i<count($liste_pers_id);$i++){
							$sql="UPDATE tempo2 SET col1='pers_id_refus' WHERE col2='$liste_pers_id[$i]';";
							info_debug($sql);
							$update=mysql_query($sql);
						}
					}
				}
				info_debug("... fin de l'enregistrement des validations/refus de la tranche.");

				//$sql="SELECT 1=1 FROM tempo2 WHERE col1='pers_id';";
				$sql="SELECT DISTINCT col2 FROM tempo2 WHERE col1='pers_id';";
				info_debug($sql);
				$test=mysql_query($sql);

				echo "<p>".mysql_num_rows($test)." personnes/adresses restantes sur un total de $total_pers_diff.</p>\n";
				echo "<input type='hidden' name='total_pers_diff' value='".$total_pers_diff."' />\n";
			}

			echo "<input type='hidden' name='parcours_diff' value='y' />\n";

			// Il faut encore parcourir les changements d'adresses...
			// ... et faire une premi�re tranche de corrections?
			// Ou alors on le fait s�paremment...

			$titre_infobulle="Adresse mail non mise � jour";
			$texte_infobulle="L'adresse mail ne sera pas modifi�e, parce que votre param�trage des adresses responsables est&nbsp;: <b>".getSettingValue('mode_email_resp')."</b>";
			$tabdiv_infobulle[]=creer_div_infobulle('chgt_email_non_pris_en_compte',$titre_infobulle,"",$texte_infobulle,"",18,0,'y','y','n','n');

			//$eff_tranche=20;

			$sql="SELECT DISTINCT col2 FROM tempo2 WHERE col1='pers_id' LIMIT $eff_tranche";
			info_debug($sql);
			$res1=mysql_query($sql);

			if(mysql_num_rows($res1)>0){

				//echo "<p align='center'><input type='submit' value='Poursuivre' /></p>\n";
				echo "<p align='center'><input type='submit' value='Valider' /></p>\n";

				// Affichage du tableau
				//echo "<table border='1'>\n";
				//echo "<table class='majimport'>\n";
				echo "<table class='boireaus'>\n";

				/*
				echo "<tr>\n";
				echo "<td style='text-align: center; font-weight: bold;'>Enregistrer<br />\n";
				echo "<a href=\"javascript:modifcase('coche')\">";
				echo "<img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>";
				echo " / ";
				echo "<a href=\"javascript:modifcase('decoche')\">";
				echo "<img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>";
				echo "</td>\n";

				echo "<td style='text-align:center;'>&nbsp;</td>\n";

				echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>pers_id</td>\n";
				echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Nom</td>\n";
				echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Pr�nom</td>\n";
				echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Civilit�</td>\n";
				echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>T�l�phone / mel</td>\n";
				//echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Tel perso</td>\n";
				//echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Tel port</td>\n";
				//echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Tel prof</td>\n";
				//echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Mel</td>\n";

				// Pour l'adresse, on teste si l'adr_id a chang�:
				// - si oui on indique le changement en piochant la nouvelle adresse dans temp_resp_adr_import2
				// - sinon on indique 'Identifiant d adresse inchang�'
				echo "<td style='text-align:center; font-weight:bold; background-color: #FAFABE;'>Adresse</td>\n";
				echo "</tr>\n";
				*/

				$ligne_entete_tableau="<tr>\n";
				//$ligne_entete_tableau.="<td style='text-align: center; font-weight: bold;'>Enregistrer<br />\n";
				$ligne_entete_tableau.="<td style='text-align: center; font-weight: bold;'>Modifier<br />\n";
				//$ligne_entete_tableau.="<th style='text-align: center; font-weight: bold;'>Enregistrer<br />\n";

				$ligne_entete_tableau.="<a href=\"javascript:modifcase('coche')\">";
				$ligne_entete_tableau.="<img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>";
				$ligne_entete_tableau.=" / ";
				$ligne_entete_tableau.="<a href=\"javascript:modifcase('decoche')\">";
				$ligne_entete_tableau.="<img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>";
				$ligne_entete_tableau.="</td>\n";
				//$ligne_entete_tableau.="</th>\n";

				//$ligne_entete_tableau.="<td style='text-align:center; background-color: rgb(150, 200, 240);'>Statut</td>\n";
				$ligne_entete_tableau.="<td style='text-align:center; font-weight: bold;'>Statut</td>\n";
				//$ligne_entete_tableau.="<th style='text-align:center; font-weight: bold;'>Statut</th>\n";

				$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>pers_id</td>\n";
				$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Nom</td>\n";
				$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Pr�nom</td>\n";
				$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Civilit�</td>\n";
				$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>T�l�phone / mel</td>\n";
				//$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Tel perso</td>\n";
				//$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Tel port</td>\n";
				//$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Tel prof</td>\n";
				//$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Mel</td>\n";

				// Pour l'adresse, on teste si l'adr_id a chang�:
				// - si oui on indique le changement en piochant la nouvelle adresse dans temp_resp_adr_import2
				// - sinon on indique 'Identifiant d adresse inchang�'
				$ligne_entete_tableau.="<td style='text-align:center; font-weight:bold; background-color: #FAFABE;'>Adresse</td>\n";
				$ligne_entete_tableau.="</tr>\n";

				$texte_infobulle="<center>La personne n'est associ�e � aucun �l�ve.</center>";
				$tabdiv_infobulle[]=creer_div_infobulle('nouveau_resp_sans_eleve',"","",$texte_infobulle,"",14,0,'y','y','n','n');

				$liste_resp_sans_eleve="";

				// Ent�te du tableau:
				echo $ligne_entete_tableau;

				$nb_chgt_adresse_inapproprie_non_affiche=0;

				$alt=1;
				$cpt=0;
				while($lig1=mysql_fetch_object($res1)){
				//for($i=0;$i<count($pers_modif);$i++){
					//$pers_id=$pers_modif[$i];

					// T�moin pour permettre de ne pas afficher la ligne si les adresses de deux responsables associ�s sont identiques mais avec des adr_id diff�rents dans Sconet alors que la correction (fusion des adr_id) a �t� effectu�e dans Gepi.
					$temoin_chgt_adresse_inapproprie="n";
					// T�moin d'une diff�rence autre que celle ci-dessus
					$temoin_diff_autre="n";
					// Ligne � afficher ou non:
					$ligne_parent="";

					$pers_id=$lig1->col2;

					// Est-ce un nouveau ou une modif?
					$sql="SELECT * FROM resp_pers WHERE pers_id='$pers_id'";
					info_debug($sql);
					$res_pers1=mysql_query($sql);
					$nouveau=0;
					if(mysql_num_rows($res_pers1)==0){
						$nouveau=1;

						$login_resp1="";
						$nom1="";
						$prenom1="";
						$civilite1="";
						$tel_pers1="";
						$tel_port1="";
						$tel_prof1="";
						$mel1="";
						$adr_id1="";
					}
					else{
						$lig_pers1=mysql_fetch_object($res_pers1);

						$login_resp1=$lig_pers1->login;
						$nom1=$lig_pers1->nom;
						$prenom1=$lig_pers1->prenom;
						$civilite1=$lig_pers1->civilite;
						$tel_pers1=$lig_pers1->tel_pers;
						$tel_port1=$lig_pers1->tel_port;
						$tel_prof1=$lig_pers1->tel_prof;
						$mel1=$lig_pers1->mel;
						$adr_id1=$lig_pers1->adr_id;
					}

					$alt=$alt*(-1);
					$ligne_parent.="<tr class='lig$alt'>\n";

					$ligne_parent.="<td style='text-align: center;'>\n";
					$ligne_parent.="<input type='checkbox' id='check_".$cpt."' name='valid_pers_id[]' value='$pers_id' />\n";
					$ligne_parent.="<input type='hidden' name='liste_pers_id[]' value='$pers_id' />\n";
					$ligne_parent.="</td>\n";

					if($nouveau==0){
						$ligne_parent.="<td class='modif'>Modif</td>\n";
					}
					else{
						$sql="SELECT 1=1 FROM temp_resp_pers_import trp,
												temp_responsables2_import tr,
												eleves e
										WHERE trp.pers_id='$pers_id' AND
												trp.pers_id=tr.pers_id AND
												tr.ele_id=e.ele_id";
						info_debug($sql);
						$test=mysql_query($sql);
						if(mysql_num_rows($test)>0){
							$ligne_parent.="<td class='nouveau'>Nouveau</td>\n";
						}
						else{
							if($liste_resp_sans_eleve!=""){$liste_resp_sans_eleve.=",";}
							//$liste_resp_sans_eleve.="'$pers_id'";
							$liste_resp_sans_eleve.="'$cpt'";
							$ligne_parent.="<td style='background-color:orange;'>";
							$ligne_parent.="<a href='#' onmouseover=\"afficher_div('nouveau_resp_sans_eleve','y',-20,20);\"";
							$ligne_parent.=" onmouseout=\"cacher_div('nouveau_resp_sans_eleve')\" onclick=\"return false;\"";
							$ligne_parent.=">";
							$ligne_parent.="Nouveau<br />(*)";
							$ligne_parent.="</a>";
							$ligne_parent.="</td>\n";
						}
					}

					$ligne_parent.="<td style='text-align:center;'><a href='modify_resp.php?pers_id=$pers_id' target='_blank'>$pers_id</a>";
					//$ligne_parent.="<input type='hidden' name='modif_".$cpt."_pers_id' value='$pers_id' />\n";
					//$ligne_parent.="<input type='text' name='modif_".$cpt."_pers_id' value='$pers_id' />\n";
					$ligne_parent.="</td>\n";


					$sql="SELECT * FROM temp_resp_pers_import WHERE (pers_id='$pers_id')";
					info_debug($sql);
					$res_pers2=mysql_query($sql);
					$lig_pers2=mysql_fetch_object($res_pers2);

					$ligne_parent.="<td";
					if($nouveau==0){
						if(stripslashes($lig_pers2->nom)!=stripslashes($nom1)){
							$ligne_parent.=" class='modif'>";
							if($nom1!=''){
								$ligne_parent.=stripslashes($nom1)." <font color='red'>-&gt;</font>\n";
							}

							$temoin_diff_autre="y";
						}
						else{
							$ligne_parent.=">";
						}
					}
					else{
						$ligne_parent.=">";
					}
					$ligne_parent.=stripslashes($lig_pers2->nom);
					$ligne_parent.="</td>\n";

					$ligne_parent.="<td";
					if($nouveau==0){
						if(stripslashes($lig_pers2->prenom)!=stripslashes($prenom1)){
							$ligne_parent.=" class='modif'>";
							if($prenom1!=''){
								$ligne_parent.=stripslashes($prenom1)." <font color='red'>-&gt;</font>\n";
							}

							$temoin_diff_autre="y";
						}
						else{
							$ligne_parent.=">";
						}
					}
					else{
						$ligne_parent.=">";
					}
					$ligne_parent.=stripslashes($lig_pers2->prenom);
					$ligne_parent.="</td>\n";


					//======================================
					$ligne_parent.="<td";
					if($nouveau==0){
						if(ucfirst(strtolower(stripslashes($lig_pers2->civilite)))!=ucfirst(strtolower(stripslashes($civilite1)))){
							$ligne_parent.=" class='modif'>";
							if($civilite1!=''){
								$ligne_parent.=stripslashes($civilite1)." <font color='red'>-&gt;</font>\n";
							}

							$temoin_diff_autre="y";
						}
						else{
							$ligne_parent.=">";
						}
					}
					else{
						$ligne_parent.=">";
					}
					$ligne_parent.=ucfirst(strtolower(stripslashes($lig_pers2->civilite)));
					$ligne_parent.="</td>\n";
					//======================================


					$ligne_parent.="<td style='text-align:center; padding: 2px;'>";
						$ligne_parent.="<table class='majimport' width='100%'>\n";
						$ligne_parent.="<tr>\n";
						$ligne_parent.="<td style='text-align:center; font-weight:bold;'>Tel</td>\n";
						$ligne_parent.="<td";
						if($nouveau==0){
							if($lig_pers2->tel_pers!=$tel_pers1) {
								if(($lig_pers2->tel_pers!='')||($tel_pers1!='')){
									$ligne_parent.=" class='modif'>";
									if($tel_pers1!=''){
										$ligne_parent.=$tel_pers1." <font color='red'>-&gt;</font>\n";
									}

									$temoin_diff_autre="y";
								}
								else{
									$ligne_parent.=">";
								}
							}
							else{
								$ligne_parent.=">";
							}
						}
						else{
							$ligne_parent.=">";
						}
						$ligne_parent.=$lig_pers2->tel_pers;
						$ligne_parent.="</td>\n";
						$ligne_parent.="</tr>\n";

						$ligne_parent.="<tr>\n";
						$ligne_parent.="<td style='text-align:center; font-weight:bold;'>TPo</td>\n";
						$ligne_parent.="<td";
						if($nouveau==0){
							if($lig_pers2->tel_port!=$tel_port1) {
								if(($lig_pers2->tel_port!='')||($tel_port1!='')){
									$ligne_parent.=" class='modif'>";
									if($tel_port1!=''){
										$ligne_parent.=$tel_port1." <font color='red'>-&gt;</font>\n";
									}

									$temoin_diff_autre="y";
								}
								else{
									$ligne_parent.=">";
								}
							}
							else{
								$ligne_parent.=">";
							}
						}
						else{
							$ligne_parent.=">";
						}
						$ligne_parent.=$lig_pers2->tel_port;
						$ligne_parent.="</td>\n";
						$ligne_parent.="</tr>\n";

						$ligne_parent.="<tr>\n";
						$ligne_parent.="<td style='text-align:center; font-weight:bold;'>TPr</td>\n";
						$ligne_parent.="<td";
						if($nouveau==0){
							if($lig_pers2->tel_prof!=$tel_prof1) {
								if(($lig_pers2->tel_prof!='')||($tel_prof1!='')){
									$ligne_parent.=" class='modif'>";
									if($tel_prof1!=''){
										$ligne_parent.=$tel_prof1." <font color='red'>-&gt;</font>\n";
									}

									$temoin_diff_autre="y";
								}
								else{
									$ligne_parent.=">";
								}
							}
							else{
								$ligne_parent.=">";
							}
						}
						else{
							$ligne_parent.=">";
						}
						$ligne_parent.=$lig_pers2->tel_prof;
						$ligne_parent.="</td>\n";
						$ligne_parent.="</tr>\n";

						$ligne_parent.="<tr>\n";
						$ligne_parent.="<td style='text-align:center; font-weight:bold;'>mel</td>\n";
						$ligne_parent.="<td";
						if($nouveau==0){
							if($lig_pers2->mel!=$mel1) {
								if(($lig_pers2->mel!='')||($mel1!='')){

									//if((getSettingValue('mode_email_resp')!='')&&(getSettingValue('mode_email_resp')!='sconet')) {
									if((getSettingValue('mode_email_resp')!='')&&(getSettingValue('mode_email_resp')!='sconet')&&($alert_diff_mail_resp=='y')) {

										if($login_resp1!='') {
											$sql="SELECT email FROM utilisateurs WHERE login='$login_resp1';";
											$res_email_resp=mysql_query($sql);
											if(mysql_num_rows($res_email_resp)>0) {
												$lig_email_resp=mysql_fetch_object($res_email_resp);

												if($lig_email_resp->email=='') {
													$ligne_parent.=" class='modif'>";

													$ligne_parent.="<a href='#' onmouseover=\"afficher_div('chgt_email_non_pris_en_compte','y',-20,20);\"><img src=\"../images/info.png\" alt=\"Information\" title=\"Information\" height=\"29\" width=\"29\" align=\"middle\" border=\"0\" /></a>";

													$info_action_titre="Adresse mail non synchro pour ".remplace_accents(stripslashes($lig_pers2->nom)."_".stripslashes($lig_pers2->prenom));
													$info_action_texte="Vous devriez mettre � jour Sconet pour <a href='responsables/modify_resp.php?pers_id=$lig_pers2->pers_id'>".remplace_accents(stripslashes($lig_pers2->nom)."_".stripslashes($lig_pers2->prenom))."</a><br />L'adresse email renseign�e par la personne via 'G�rer mon compte' est vide contrairement � l'adresse enregistr�e dans Sconet ($lig_pers2->mel).";
													$info_action_destinataire=array("administrateur","scolarite");
													$info_action_mode="statut";
													enregistre_infos_actions($info_action_titre,$info_action_texte,$info_action_destinataire,$info_action_mode);
												}
												else {
													if($lig_email_resp->email!=$lig_pers2->mel) {
														// L'email Sconet diff�re de celui non vide d�clar� dans G�rer mon compte
														$ligne_parent.=" class='modif'>";

														$ligne_parent.="<a href='#' onmouseover=\"afficher_div('chgt_email_non_pris_en_compte','y',-20,20);\"><img src=\"../images/info.png\" alt=\"Information\" title=\"Information\" height=\"29\" width=\"29\" align=\"middle\" border=\"0\" /></a>";

														$info_action_titre="Adresse mail non synchro pour ".remplace_accents(stripslashes($lig_pers2->nom)."_".stripslashes($lig_pers2->prenom),'all');
														$info_action_texte="Vous devriez mettre � jour Sconet pour <a href='responsables/modify_resp.php?pers_id=$lig_pers2->pers_id'>".remplace_accents(stripslashes($lig_pers2->nom)."_".stripslashes($lig_pers2->prenom),'all')."</a><br />L'adresse email renseign�e par la personne via 'G�rer mon compte' ($lig_email_resp->email) diff�re de l'adresse enregistr�e dans Sconet ($lig_pers2->mel).";
														$info_action_destinataire=array("administrateur","scolarite");
														$info_action_mode="statut";
														enregistre_infos_actions($info_action_titre,$info_action_texte,$info_action_destinataire,$info_action_mode);
													}
													else {
														$ligne_parent.=" class='modif'>";
														// Bizarre... si le responsable a mise � jour son adresse par G�rer mon compte en mode 'mon_compte', on devrait avoir la synchro... ou alors la mise � jour 'mode_email_resp' est intervenue entre temps
														// ... faudrait-il aussi tester l'ancien resp_pers.mel et le utilisateurs.email?

														$info_action_titre="Adresse mail non synchro pour ".remplace_accents(stripslashes($lig_pers2->nom)."_".stripslashes($lig_pers2->prenom));
														$info_action_texte="Vous devriez mettre � jour Sconet pour <a href='responsables/modify_resp.php?pers_id=$lig_pers2->pers_id'>".remplace_accents(stripslashes($lig_pers2->nom)."_".stripslashes($lig_pers2->prenom))."</a><br />L'adresse email renseign�e par la personne via 'G�rer mon compte' ($lig_email_resp->email) diff�re de l'adresse enregistr�e dans Sconet ($lig_pers2->mel).";
														$info_action_destinataire=array("administrateur","scolarite");
														$info_action_mode="statut";
														enregistre_infos_actions($info_action_titre,$info_action_texte,$info_action_destinataire,$info_action_mode);
													}
												}
											}
											else {
												// Pas de compte utilisateur pour ce responsable
												$ligne_parent.=" class='modif'>";
												// Il faudrait prendre en compte la m�j
											}
										}
										else {
											$ligne_parent.=" class='modif'>";
										}
									}
									else {
										$ligne_parent.=" class='modif'>";
									}

									if($mel1!=''){
										$ligne_parent.=$mel1." <font color='red'>-&gt;</font>\n";
									}

									$temoin_diff_autre="y";
								}
								else{
									//$ligne_parent.="'>";
									$ligne_parent.=">";
								}
							}
							else{
								//$ligne_parent.="'>";
								$ligne_parent.=">";
							}
						}
						else{
							//$ligne_parent.="'>";
							$ligne_parent.=">";
						}
						$ligne_parent.=$lig_pers2->mel;
						$ligne_parent.="</td>\n";
						$ligne_parent.="</tr>\n";
						$ligne_parent.="</table>\n";

						//$ligne_parent.="\$lig_pers2->adr_id=$lig_pers2->adr_id";
					$ligne_parent.="</td>\n";



					// Adresse
					$ligne_parent.="<td";

					if($lig_pers2->adr_id!=""){
						$sql="SELECT * FROM temp_resp_adr_import WHERE (adr_id='".$lig_pers2->adr_id."')";
						info_debug($sql);
						$res_adr2=mysql_query($sql);
						if(mysql_num_rows($res_adr2)==0){
							$adr1_2="";
							$adr2_2="";
							$adr3_2="";
							$adr4_2="";
							$cp2="";
							$commune2="";
							$pays2="";
						}
						else{
							$lig_adr2=mysql_fetch_object($res_adr2);

							$adr1_2=$lig_adr2->adr1;
							$adr2_2=$lig_adr2->adr2;
							$adr3_2=$lig_adr2->adr3;
							$adr4_2=$lig_adr2->adr4;
							$cp2=$lig_adr2->cp;
							$commune2=$lig_adr2->commune;
							$pays2=$lig_adr2->pays;
						}
					}
					else{
						$adr1_2="";
						$adr2_2="";
						$adr3_2="";
						$adr4_2="";
						$cp2="";
						$commune2="";
						$pays2="";
					}

					if($nouveau==0){
						if($adr_id1!=""){
							$sql="SELECT * FROM resp_adr WHERE (adr_id='".$adr_id1."')";
							info_debug($sql);
							//$adr_id=$personne[$pers_id]["adr_id"];
							$res_adr1=mysql_query($sql);
							if(mysql_num_rows($res_adr1)==0){
								$adr1_1="";
								$adr2_1="";
								$adr3_1="";
								$adr4_1="";
								$cp1="";
								$commune1="";
								$pays1="";
							}
							else{
								$lig_adr1=mysql_fetch_object($res_adr1);

								$adr1_1=$lig_adr1->adr1;
								$adr2_1=$lig_adr1->adr2;
								$adr3_1=$lig_adr1->adr3;
								$adr4_1=$lig_adr1->adr4;
								$cp1=$lig_adr1->cp;
								$commune1=$lig_adr1->commune;
								$pays1=$lig_adr1->pays;
							}
						}
						else{
							$adr1_1="";
							$adr2_1="";
							$adr3_1="";
							$adr4_1="";
							$cp1="";
							$commune1="";
							$pays1="";
						}

						$chaine_adr1="";
						if(($adr1_1!="")||($adr2_1!="")||($adr3_1!="")||($adr4_1!="")||($cp1!="")||($commune1!="")||($pays1!="")){
							if($adr1_1!=""){
								$chaine_adr1.=stripslashes("$adr1_1, ");
							}
							if($adr2_1!=""){
								$chaine_adr1.=stripslashes("$adr2_1, ");
							}
							if($adr3_1!=""){
								$chaine_adr1.=stripslashes("$adr3_1, ");
							}
							if($adr4_1!=""){
								$chaine_adr1.=stripslashes("$adr4_1, ");
							}
							if($cp1!=""){
								$chaine_adr1.=stripslashes("$cp1, ");
							}
							if($commune1!=""){
								$chaine_adr1.=stripslashes("$commune1, ");
							}
							if($pays1!=""){
								$chaine_adr1.=stripslashes("$pays1");
							}
						}

						$chaine_adr2="";
						if(($adr1_2!="")||($adr2_2!="")||($adr3_2!="")||($adr4_2!="")||($cp2!="")||($commune2!="")||($pays2!="")){
							if($adr1_2!=""){
								$chaine_adr2.=stripslashes("$adr1_2, ");
							}
							if($adr2_2!=""){
								$chaine_adr2.=stripslashes("$adr2_2, ");
							}
							if($adr3_2!=""){
								$chaine_adr2.=stripslashes("$adr3_2, ");
							}
							if($adr4_2!=""){
								$chaine_adr2.=stripslashes("$adr4_2, ");
							}
							if($cp2!=""){
								$chaine_adr2.=stripslashes("$cp2, ");
							}
							if($commune2!=""){
								$chaine_adr2.=stripslashes("$commune2, ");
							}
							if($pays2!=""){
								$chaine_adr2.=stripslashes("$pays2");
							}
						}

						if($chaine_adr1!=$chaine_adr2){
							$ligne_parent.=" class='modif'>";
							$ligne_parent.=$chaine_adr1;
							$ligne_parent.=" <font color='red'>-&gt;</font><br />\n";

							$temoin_diff_autre="y";
						}
						elseif(($adr_id1!="")&&($lig_pers2->adr_id!="")&&($adr_id1!=$lig_pers2->adr_id)) {
							$ligne_parent.=" class='modif'>";

							// Mettre une infobulle pour d�tailler la situation:
							$titre="Modification adresse";
							$texte="<div style='text-align:center; font-size:small;'>\n";
							$texte.="<b>Adresse actuelle:</b><br />\n";

							$sql="SELECT * FROM resp_pers WHERE adr_id='$adr_id1' AND pers_id!='$pers_id';";
							info_debug($sql);
							$test_adr_id=mysql_query($sql);
							if(mysql_num_rows($test_adr_id)>0) {
								$lig_autre_resp_adr_partagee=mysql_fetch_object($test_adr_id);
								//$texte.="$civilite1 $nom1 $prenom1 partageait l'adresse suivante avec $lig_autre_resp_adr_partagee->civilite $lig_autre_resp_adr_partagee->nom $lig_autre_resp_adr_partagee->prenom:<br />\n";
								$infos_adresse="Partag�e avec $lig_autre_resp_adr_partagee->civilite $lig_autre_resp_adr_partagee->nom $lig_autre_resp_adr_partagee->prenom";

								$temoin_chgt_adresse_inapproprie="y";
							}
							else {
								//$texte.="$civilite1 $nom1 $prenom1 avait l'adresse:<br />\n";
								$infos_adresse="Adresse non partag�e";
							}
							$texte.="<table class='boireaus' border='1'>
<tr>
	<th>Adr_id</th>
	<th>Adr1</th>
	<th>Adr2</th>
	<th>Adr3</th>
	<th>Adr4</th>
	<th>CP</th>
	<th>Commune</th>
	<th>Pays</th>
	<th>Infos</th>
</tr>
<tr>
	<td>$lig_pers1->adr_id</td>
	<td>$adr1_1</td>
	<td>$adr2_1</td>
	<td>$adr3_1</td>
	<td>$adr4_1</td>
	<td>$cp1</td>
	<td>$commune1</td>
	<td>$pays1</td>
	<td>$infos_adresse</td>
</tr>
</table>";
							$texte.="<br />\n";
							$texte.="<b>Nouvelle adresse:</b><br />\n";

							$sql="SELECT * FROM temp_resp_pers_import WHERE adr_id='$lig_pers2->adr_id' AND pers_id!='$pers_id';";
							info_debug($sql);
							$test_adr_id=mysql_query($sql);
							if(mysql_num_rows($test_adr_id)>0) {
								$lig_autre_resp_adr_partagee=mysql_fetch_object($test_adr_id);
								//$texte.="$civilite1 $nom1 $prenom1 partageait l'adresse suivante avec $lig_autre_resp_adr_partagee->civilite $lig_autre_resp_adr_partagee->nom $lig_autre_resp_adr_partagee->prenom:<br />\n";
								$infos_adresse="Partag�e avec $lig_autre_resp_adr_partagee->civilite $lig_autre_resp_adr_partagee->nom $lig_autre_resp_adr_partagee->prenom";

								$temoin_chgt_adresse_inapproprie="y";
							}
							else {
								//$texte.="$civilite1 $nom1 $prenom1 avait l'adresse:<br />\n";
								$infos_adresse="Adresse non partag�e";
							}
							$texte.="<table class='boireaus' border='1'>
<tr>
	<th>Adr_id</th>
	<th>Adr1</th>
	<th>Adr2</th>
	<th>Adr3</th>
	<th>Adr4</th>
	<th>CP</th>
	<th>Commune</th>
	<th>Pays</th>
	<th>Infos</th>
</tr>
<tr>
	<td>$lig_pers2->adr_id</td>
	<td>$adr1_2</td>
	<td>$adr2_2</td>
	<td>$adr3_2</td>
	<td>$adr4_2</td>
	<td>$cp2</td>
	<td>$commune2</td>
	<td>$pays2</td>
	<td>$infos_adresse</td>
</tr>
</table>";

							$texte.="</div>\n";

							$tabdiv_infobulle[]=creer_div_infobulle('chgt_adr_'.$cpt,$titre,"",$texte,"",40,0,'y','y','n','n');

							$ligne_parent.="<a href='#' onmouseover=\"afficher_div('chgt_adr_".$cpt."','y',-20,20);\">";
							$ligne_parent.="<img src='../images/info.png' width='29' height='29'  align='middle' border='0' alt='Information' title='Information' />";
							$ligne_parent.="</a> ";

						}
						else {
							$ligne_parent.=">";
						}
						$ligne_parent.=$chaine_adr2;

					}
					else{
						//$ligne_parent.="'>";
						$ligne_parent.=">";
						// Indiquer l'adresse pour cette nouvelle personne responsable

						if(($adr1_2!="")||($adr2_2!="")||($adr3_2!="")||($adr4_2!="")||($cp2!="")||($commune2!="")||($pays2!="")){
							$chaine_adr="";
							if($adr1_2!=""){
								$chaine_adr.=stripslashes("$adr1_2, ");
							}
							if($adr2_2!=""){
								$chaine_adr.=stripslashes("$adr2_2, ");
							}
							if($adr3_2!=""){
								$chaine_adr.=stripslashes("$adr3_2, ");
							}
							if($adr4_2!=""){
								$chaine_adr.=stripslashes("$adr4_2, ");
							}
							if($cp2!=""){
								$chaine_adr.=stripslashes("$cp2, ");
							}
							if($commune2!=""){
								$chaine_adr.=stripslashes("$commune2, ");
							}
							if($pays2!=""){
								$chaine_adr.=stripslashes("$pays2");
							}
							$ligne_parent.=$chaine_adr;
						}
						else{
							$ligne_parent.="<span color='red'>Adresse vide</span>\n";
						}
					}
					$ligne_parent.="</td>\n";


					$ligne_parent.="</tr>\n";


					if($ne_pas_proposer_redoublonnage_adresse=="n") {
						// Si on n'a pas demand� � ne pas afficher les situations de redoublonnage, on affiche la ligne
						echo $ligne_parent;
					}
					else {
						if($temoin_chgt_adresse_inapproprie=="n") {
							// S'il n'y a pas de redoublonnage d'adresse, on affiche la ligne
							echo $ligne_parent;
						}
						elseif($temoin_diff_autre=="y") {
							// M�me si un redoublonnage d'adresse est rep�r�, on affiche la ligne s'il y a d'autres diff�rences
							echo $ligne_parent;
						}
						else {

							echo "<tr style='display:none;'><td colspan='8'>Avant...";
							//echo "<tr><td colspan='8'>Avant...";
							//echo "<input type='hidden' name='valid_pers_id[]' value='$pers_id' />\n";
							echo "<input type='hidden' name='liste_pers_id[]' value='$pers_id' />\n";
							echo "</td></tr>\n";
							//echo $ligne_parent;
							//echo "<tr style='display:none;'><td colspan='8'>... apr�s</td></tr>\n";
							//echo "<tr><td colspan='8'>... apr�s</td></tr>\n";

							$nb_chgt_adresse_inapproprie_non_affiche++;
						}
					}

					$cpt++;
				}

				echo $ligne_entete_tableau;
				echo "</table>\n";

				if($liste_resp_sans_eleve!=""){
					echo "<p>Une ou des personnes apparaissent comme nouvelles, mais ne sont associ�es � aucun �l�ve (<i>ni dans l'actuelle table 'responsables2', ni dans la table temporaire 'temp_responsables2_import'</i>).<br />Pour ne cocher que les responsables r�ellement associ�s � des �l�ves, cliquez ici: <a href=\"javascript:modifcase2()\"><img src='../images/enabled.png' width='15' height='15' alt='Tout cocher intelligemment' /></a></p>\n";
				}

				if($nb_chgt_adresse_inapproprie_non_affiche==1) {
					echo "<p>$nb_chgt_adresse_inapproprie_non_affiche personne � cette �tape n'a pas �t� propos�e pour un re-doublonnage d'adresse.</p>\n";
				}
				elseif($nb_chgt_adresse_inapproprie_non_affiche>1) {
					echo "<p>$nb_chgt_adresse_inapproprie_non_affiche personnes � cette �tape n'ont pas �t� propos�es pour un re-doublonnage d'adresse.</p>\n";
				}

				echo "<script type='text/javascript'>
	function modifcase(mode){
		for(i=0;i<$cpt;i++){
			if(document.getElementById('check_'+i)){
				if(mode=='coche'){
					document.getElementById('check_'+i).checked=true;
				}
				else{
					document.getElementById('check_'+i).checked=false;
				}
			}
		}
	}
";

				if($liste_resp_sans_eleve!=""){
					echo "	function modifcase2(){
		modifcase('coche');

		fauxresp=new Array($liste_resp_sans_eleve);

		for(i=0;i<fauxresp.length;i++){
			if(document.getElementById('check_'+fauxresp[i])){
				document.getElementById('check_'+fauxresp[i]).checked=false;
			}
		}
	}
";
				}

				echo "</script>\n";

				//echo "<input type='hidden' name='step' value='15' />\n";
				echo "<input type='hidden' name='step' value='16' />\n";
				//echo "<p align='center'><input type='submit' value='Poursuivre' /></p>\n";
				echo "<p align='center'><input type='submit' value='Valider' /></p>\n";
			}
			else{
				// On est � la fin on peut passer � step=12 et effectuer les changements confirm�s.
				echo "<p>Toutes les diff�rences concernant les personnes ont �t� parcourues.</p>\n";

				//echo "<input type='hidden' name='step' value='16' />\n";
				echo "<input type='hidden' name='step' value='17' />\n";
				echo "<p><input type='submit' value='Valider les modifications' /></p>\n";
			}

			//echo "<input type='hidden' name='is_posted' value='yes' />\n";
			echo "</form>\n";

			break;
		//case 16:
		case "17":
			echo "<h2>Import/mise � jour des responsables</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			check_token(false);

			//echo "<p>On doit parcourir 'tempo2' en recherchant 'pers_id_confirm'.</p>\n";

			$sql="SELECT DISTINCT col2 FROM tempo2 WHERE col1='pers_id_confirm';";
			info_debug($sql);
			$res1=mysql_query($sql);
			if(mysql_num_rows($res1)==0){
				echo "<p>Aucune modification n'a �t� confirm�e/demand�e.</p>\n";

				// IL RESTE... les responsabilit�s
				//echo "<p>Passer � l'�tape de <a href='".$_SERVER['PHP_SELF']."?step=17&amp;stop=$stop'>mise � jour des responsabilit�s</a>.</p>\n";
				echo "<p>Passer � l'�tape de <a href='".$_SERVER['PHP_SELF']."?step=18&amp;stop=$stop'>mise � jour des responsabilit�s</a>.</p>\n";

			}
			else{
				$erreur=0;
				$cpt=0;
				echo "<p>Ajout ou modification de: ";
				while($lig1=mysql_fetch_object($res1)){
					$sql="SELECT DISTINCT t.* FROM temp_resp_pers_import t WHERE t.pers_id='$lig1->col2'";
					info_debug($sql);
					$res=mysql_query($sql);
					if(mysql_num_rows($res)>0){
						$lig=mysql_fetch_object($res);

						if($cpt>0){
							echo ", ";
						}

						$sql="SELECT 1=1 FROM resp_pers WHERE pers_id='$lig1->col2'";
						info_debug($sql);
						$test=mysql_query($sql);

						if(mysql_num_rows($test)==0){
							// prenom='".addslashes(ucfirst(strtolower($lig->prenom)))."',

							$sql="INSERT INTO resp_pers SET pers_id='$lig1->col2',
													nom='".addslashes(strtoupper($lig->nom))."',
													prenom='".addslashes(maj_ini_prenom($lig->prenom))."',
													civilite='".ucfirst(strtolower($lig->civilite))."',
													tel_pers='".addslashes($lig->tel_pers)."',
													tel_port='".addslashes($lig->tel_port)."',
													tel_prof='".addslashes($lig->tel_prof)."',
													mel='".addslashes($lig->mel)."',
													adr_id='".$lig->adr_id."';";
							info_debug($sql);
							$insert=mysql_query($sql);
							if($insert){
								echo "\n<span style='color:blue;'>";
							}
							else{
								echo "\n<span style='color:red;'>";
								$erreur++;
							}
							echo "$lig->prenom $lig->nom";
							echo "</span>";
						}
						else{
							$sql="UPDATE resp_pers SET nom='".addslashes(strtoupper($lig->nom))."',
													prenom='".addslashes(maj_ini_prenom($lig->prenom))."',
													civilite='".ucfirst(strtolower($lig->civilite))."',
													tel_pers='".addslashes($lig->tel_pers)."',
													tel_port='".addslashes($lig->tel_port)."',
													tel_prof='".addslashes($lig->tel_prof)."',";
							if((getSettingValue('mode_email_resp')=='')||(getSettingValue('mode_email_resp')=='sconet')) {
								$sql.="						mel='".addslashes($lig->mel)."',";
							}
							else {
								// Plusieurs cas peuvent survenir
								$sql="SELECT email FROM utilisateurs WHERE statut='responsable' AND login IN (SELECT login FROM resp_pers WHERE pers_id='$lig1->col2');";
								$res_email_resp=mysql_query($sql);
								// Si le responsable a un compte
								if(mysql_num_rows($res_email_resp)>0) {
									$lig_email_resp=mysql_fetch_object($res_email_resp);

									if($lig_email_resp->email=='') {
										// L'email du compte d'utilisateur est vide... est-ce pour �viter de recevoir des messages ou parce que l'email n'existe plus (plus relev�, changement de FAI,...)

										// Faut-il vider l'info?
									}
									else {
										if($lig_email_resp->email!=$lig_pers2->mel) {
											// Que faire?
										}
									}
								}
								// Si le responsable n'a pas de compte
								else {
									// Alors on fait la mise � jour
									$sql.="						mel='".addslashes($lig->mel)."',";
								}
							}
							$sql.="						adr_id='".$lig->adr_id."'
												WHERE pers_id='$lig1->col2';";

							unset($update_utilisateurs);

							info_debug($sql);
							$update=mysql_query($sql);
							if($update){
								echo "\n<span style='color:darkgreen;'>";

								if(getSettingValue('mode_email_resp')=='sconet') {
									$sql="UPDATE utilisateurs SET email='".addslashes($lig->mel)."' WHERE statut='responsable' AND login IN (SELECT login FROM resp_pers WHERE pers_id='$lig1->col2');";
									$update_utilisateurs=mysql_query($sql);
								}
							}
							else{
								echo "\n<span style='color:red;'>";
								$erreur++;
							}
							//echo "$sql<br />\n";
							echo "$lig->prenom $lig->nom";
							echo "</span>";

							if((isset($update_utilisateurs))&&(!$update_utilisateurs)) {echo " <span style='color:red;'>Erreur lors de la mise � jour du mail du compte utilisateur.</span><br />\n";}

						}

						if($lig->adr_id!=""){
							// Ajout ou modification valid�e, on met � jour l'adresse aussi:
							$sql="SELECT DISTINCT t.* FROM temp_resp_adr_import t WHERE t.adr_id='$lig->adr_id'";
							info_debug($sql);
							$res_adr2=mysql_query($sql);
							if(mysql_num_rows($res_adr2)>0){
								$lig_adr2=mysql_fetch_object($res_adr2);

								$adr1_2=$lig_adr2->adr1;
								$adr2_2=$lig_adr2->adr2;
								$adr3_2=$lig_adr2->adr3;
								$adr4_2=$lig_adr2->adr4;
								$cp2=$lig_adr2->cp;
								$commune2=$lig_adr2->commune;
								$pays2=$lig_adr2->pays;


								$sql="SELECT DISTINCT * FROM resp_adr WHERE adr_id='$lig->adr_id'";
								info_debug($sql);
								$res_adr1=mysql_query($sql);
								if(mysql_num_rows($res_adr1)>0){
									$lig_adr1=mysql_fetch_object($res_adr1);

									$adr1_1=$lig_adr1->adr1;
									$adr2_1=$lig_adr1->adr2;
									$adr3_1=$lig_adr1->adr3;
									$adr4_1=$lig_adr1->adr4;
									$cp1=$lig_adr1->cp;
									$commune1=$lig_adr1->commune;
									$pays1=$lig_adr1->pays;

									$sql="UPDATE resp_adr SET adr1='".addslashes($adr1_2)."',
																adr2='".addslashes($adr2_2)."',
																adr3='".addslashes($adr3_2)."',
																adr4='".addslashes($adr4_2)."',
																cp='".addslashes($cp2)."',
																commune='".addslashes($commune2)."',
																pays='".addslashes($pays2)."'
														WHERE adr_id='$lig->adr_id'";
									info_debug($sql);
									$update=mysql_query($sql);
									if(!$update){
										$erreur++;
										echo "<span style='color:red;'>(*)</span>";
									}
								}
								else{
									$adr1_1="";
									$adr2_1="";
									$adr3_1="";
									$adr4_1="";
									$cp1="";
									$commune1="";
									$pays1="";

									$sql="INSERT INTO resp_adr SET adr1='".addslashes($adr1_2)."',
																adr2='".addslashes($adr2_2)."',
																adr3='".addslashes($adr3_2)."',
																adr4='".addslashes($adr4_2)."',
																cp='".addslashes($cp2)."',
																commune='".addslashes($commune2)."',
																pays='".addslashes($pays2)."',
																adr_id='$lig->adr_id'";
									info_debug($sql);
									$insert=mysql_query($sql);
									if(!$insert){
										$erreur++;
										echo "<span style='color:red;'>(*)</span>";
									}

								}
							}
							else{
								// FAUT-IL INSERER UNE LIGNE VIDE dans resp_adr ?

								// On ne devrait pas arriver � cette situation...

							}
						}
					}
					$cpt++;
				}

				echo "<p><br /></p>\n";

				echo "<p><b>Indication:</b> En <span style='color:blue;'>bleu</span>, les personnes ajout�es et en <span style='color:darkgreen;'>vert</span> les personnes/adresses mises � jour.<br />Les <span style='color:red;'>(*)</span> �ventuellement pr�sents signalent un souci concernant l'adresse.</p>\n";

				echo "<p><br /></p>\n";

				switch($erreur){
					case 0:
						//echo "<p>Passer � l'�tape de <a href='".$_SERVER['PHP_SELF']."?step=17&amp;stop=y'>mise � jour des responsabilit�s</a>.</p>\n";
						echo "<p>Passer � l'�tape de <a href='".$_SERVER['PHP_SELF']."?step=18&amp;stop=$stop'>mise � jour des responsabilit�s</a>.</p>\n";
						break;
					case 1:
						//echo "<p><font color='red'>Une erreur s'est produite.</font><br />\nVous devriez en chercher la cause avant de passer � l'�tape de <a href='".$_SERVER['PHP_SELF']."?step=17&amp;stop=y'>mise � jour des responsabilit�s</a>.</p>\n";
						echo "<p><font color='red'>Une erreur s'est produite.</font><br />\nVous devriez en chercher la cause avant de passer � l'�tape de <a href='".$_SERVER['PHP_SELF']."?step=18&amp;stop=$stop'>mise � jour des responsabilit�s</a>.</p>\n";
						break;

					default:
						//echo "<p><font color='red'>$erreur erreurs se sont produites.</font><br />\nVous devriez en chercher la cause avant de passer � l'�tape de <a href='".$_SERVER['PHP_SELF']."?step=17&amp;stop=y'>mise � jour des responsabilit�s</a>.</p>\n";
						echo "<p><font color='red'>$erreur erreurs se sont produites.</font><br />\nVous devriez en chercher la cause avant de passer � l'�tape de <a href='".$_SERVER['PHP_SELF']."?step=18&amp;stop=$stop'>mise � jour des responsabilit�s</a>.</p>\n";
						break;
				}
			}

			break;
		//case 17:
		case "18":
			//echo "<h2>Import/mise � jour des responsabilit�s</h2>\n";

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			echo "<h2>Import/mise � jour des associations responsables/�l�ves</h2>\n";

			echo "<form action='".$_SERVER['PHP_SELF']."' name='formulaire' method='post'>\n";
			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//==============================

			$eff_tranche=20;

			if(!isset($parcours_diff)){

				echo "<p>On va commencer les comparaisons...</p>\n";

				flush();

				$sql="TRUNCATE tempo2;";
				info_debug($sql);
				$res0=mysql_query($sql);

				$sql="select ele_id, pers_id from temp_responsables2_import;";
				info_debug($sql);
				$res1=mysql_query($sql);

				if(mysql_num_rows($res1)==0){
					echo "<p style='color:red;'>Bizarre: La table 'temp_responsables2_import' est vide.<br />Auriez-vous saut� une �tape?</p>\n";

					echo "<p><br /></p>\n";
					require("../lib/footer.inc.php");
					die();
				}
				else{
					$tab_resp=array();
					while($lig=mysql_fetch_object($res1)){
						// On ne va consid�rer un couple valide que si le responsable est une personne associ�e � un �l�ve effectivement accept� dans la table 'eleves':
						/*
						$sql="SELECT 1=1 FROM temp_resp_pers_import trp,
												temp_responsables2_import tr,
												eleves e
										WHERE trp.pers_id='$lig->pers_id' AND
												trp.pers_id=tr.pers_id AND
												tr.ele_id=e.ele_id";
						*/
						$sql="SELECT 1=1 FROM temp_resp_pers_import trp,
												temp_responsables2_import tr,
												eleves e
										WHERE trp.pers_id='$lig->pers_id' AND
												trp.pers_id=tr.pers_id AND
												tr.ele_id=e.ele_id AND
												e.ele_id='$lig->ele_id'";
						info_debug($sql);
						$test=mysql_query($sql);
						if(mysql_num_rows($test)>0){
							$sql="INSERT INTO tempo2 SET col1='t', col2='t_".$lig->ele_id."_".$lig->pers_id."'";
							info_debug($sql);
							$insert=mysql_query($sql);

							$tab_resp[]="t_".$lig->ele_id."_".$lig->pers_id;
						}
					}
				}

				flush();

				/*
				if($cpt==1){
					echo "<p>L'identifiant ADR_ID d'une nouvelle adresse responsable a �t� trouv�: $chaine_nouveaux</p>\n";
				}
				elseif($cpt>1){
					echo "<p>Les identifiants ADR_ID de $cpt nouvelles adresses responsables ont �t� trouv�s: $chaine_nouveaux</p>\n";
				}
				*/

				$nb_parcours=ceil(count($tab_resp)/$eff_tranche);
			}
			else{

				echo "<p>Parcours de la tranche <b>$parcours_diff/$nb_parcours</b>.</p>\n";

				if(isset($tab_resp_diff)){
					if(count($tab_resp_diff)==1){
						echo "<p>Le couple ELE_ID/PERS_ID pour lequel une ou des diff�rences ont d�j� �t� rep�r�es, est: \n";
					}
					else{
						echo "<p>Les couples ELE_ID/PERS_ID, pour lesquels une ou des diff�rences ont d�j� �t� rep�r�es, sont: \n";
					}
					$chaine_ele_resp="";
					for($i=0;$i<count($tab_resp_diff);$i++){
						if($i>0){$chaine_ele_resp.=", ";}
						$tab_tmp=explode("_",$tab_resp_diff[$i]);
						$chaine_ele_resp.=$tab_tmp[1]."/".$tab_tmp[2];
						//echo "$i: ";
						echo "<input type='hidden' name='tab_resp_diff[]' value='$tab_resp_diff[$i]' />\n";
						//echo "<br />\n";
					}
					echo $chaine_ele_resp;
					echo "</p>\n";
				}
			}

			echo "<input type='hidden' name='nb_parcours' value='$nb_parcours' />\n";

			//echo "count(\$tab_resp)=".count($tab_resp)."<br />";

			// On construit la chaine des $eff_tranche couples retenus pour la requ�te � venir:
			//$chaine="";
			$cpt=0;
			for($i=0;$i<min($eff_tranche,count($tab_resp));$i++){
				//if($i>0){$chaine.=" OR ";}

				$tab_tmp=explode("_",$tab_resp[$i]);

				//$chaine.="(t.ele_id='$tab_tmp[1]' AND t.pers_id='$tab_tmp[2]')";

				$sql="SELECT 1=1 FROM responsables2 WHERE ele_id='$tab_tmp[1]' AND pers_id='$tab_tmp[2]';";
				info_debug($sql);
				//echo "$sql<br />";
				$test=mysql_query($sql);
				if(mysql_num_rows($test)==0){
					// C'est une nouvelle responsabilit�
					/*
					//$sql="UPDATE tempo2 SET col1='t_new' WHERE col2='t_".$tab_tmp[1]."_".$tab_tmp[2]."'";
					$sql="UPDATE tempo2 SET col1='t_diff' WHERE col2='t_".$tab_tmp[1]."_".$tab_tmp[2]."'";
					$update=mysql_query($sql);
					*/




					if($cpt==0){
						echo "<p>Une ou des diff�rences ont �t� trouv�es dans la tranche �tudi�e � cette phase.";
						echo "<br />\n";
						echo "En voici le(s) couple(s) ELE_ID/PERS_ID: ";
					}
					else{
						echo ", ";
					}
					echo "<span style='color:red;'>".$tab_tmp[1]."/".$tab_tmp[2]."</span>";

					$cpt++;




					echo "<input type='hidden' name='tab_resp_diff[]' value='t_".$tab_tmp[1]."_".$tab_tmp[2]."' />\n";

					// FAIRE UN echo POUR INDIQUER CES NOUVEAUX RESPONSABLES REP�R�S
					// REMPLIR UNE CHAINE ET L'AJOUTER A LA FIN DE LA LISTE AFFICH�E PLUS BAS
				}
				else{

					$sql="SELECT t.ele_id,t.pers_id FROM responsables2 r, temp_responsables2_import t
									WHERE r.pers_id=t.pers_id AND
											r.ele_id=t.ele_id AND
											(
												r.resp_legal!=t.resp_legal OR
												r.pers_contact!=t.pers_contact
											)
											AND (t.ele_id='$tab_tmp[1]' AND t.pers_id='$tab_tmp[2]')
											";
					info_debug($sql);
					//echo "$sql<br />\n";
					$test=mysql_query($sql);
					if(mysql_num_rows($test)>0){
						if($cpt==0){
							echo "<p>Une ou des diff�rences ont �t� trouv�es dans la tranche �tudi�e � cette phase.";
							echo "<br />\n";
							echo "En voici le(s) couple(s) ELE_ID/PERS_ID: ";
						}
						else{
							echo ", ";
						}
						$lig=mysql_fetch_object($test);

						echo $lig->ele_id."/".$lig->pers_id;
						echo "<input type='hidden' name='tab_resp_diff[]' value='t_".$lig->ele_id."_".$lig->pers_id."' />\n";
						//echo "<br />\n";
						// Pour le cas o� on est dans la derni�re tranche:
						$tab_resp_diff[]="t_".$lig->ele_id."_".$lig->pers_id;
						$cpt++;
					}
				}
			}

			//echo "\$chaine=$chaine<br />\n";

			// Liste des couples restant � parcourir:
			for($i=$eff_tranche;$i<count($tab_resp);$i++){
				//echo "$i: ";
				echo "<input type='hidden' name='tab_resp[]' value='$tab_resp[$i]' />\n";
				//echo "<br />\n";
			}


			/*
			$sql="SELECT t.ele_id,t.pers_id FROM responsables2 r, temp_responsables2_import t
							WHERE r.pers_id=t.pers_id AND
									r.ele_id=t.ele_id AND
									(
										r.resp_legal!=t.resp_legal OR
										r.pers_contact!=t.pers_contact
									)
									AND ($chaine)
									";
			//echo "$sql<br />\n";
			$test=mysql_query($sql);
			if(mysql_num_rows($test)>0){
				echo "<p>Une ou des diff�rences ont �t� trouv�es dans la tranche �tudi�e � cette phase.";
				echo "<br />\n";
				echo "En voici le(s) couple(s) ELE_ID/PERS_ID: ";
				$cpt=0;
				$chaine_ele_resp="";
				while($lig=mysql_fetch_object($test)){
					if($cpt>0){$chaine_ele_resp.=", ";}
					$chaine_ele_resp.=$lig->ele_id."/".$lig->pers_id;
					echo "<input type='hidden' name='tab_resp_diff[]' value='t_".$lig->ele_id."_".$lig->pers_id."' />\n";
					//echo "<br />\n";
					// Pour le cas o� on est dans la derni�re tranche:
					$tab_resp_diff[]="t_".$lig->ele_id."_".$lig->pers_id;
					$cpt++;
				}
				echo $chaine_ele_resp;
			}
			*/

			if(!isset($parcours_diff)){$parcours_diff=1;}
			$parcours_diff++;
			//echo "<input type='hidden' name='parcours_diff' value='$parcours_diff' />\n";

			//=====================
			// DEBUG
			//echo "count(\$tab_resp)=".count($tab_resp)." et \$eff_tranche=$eff_tranche<br />";
			//=====================

			//if(count($tab_resp)>$eff_tranche){
			if((count($tab_resp)>$eff_tranche)||($cpt>0)) {
				echo "<input type='hidden' name='parcours_diff' value='$parcours_diff' />\n";
				//echo "<input type='hidden' name='step' value='17' />\n";
				echo "<input type='hidden' name='step' value='18' />\n";
				echo "<p><input type='submit' value='Suite' /></p>\n";


				echo "<script type='text/javascript'>
	/*
	stop='n';
	if(document.getElementById('stop')){
		if(document.getElementById('stop').checked==true){
			stop='y';
		}
	}
	if(stop=='n'){
		setTimeout(\"document.forms['formulaire'].submit();\",1000);
	}
	*/
	setTimeout(\"test_stop2()\",3000);
</script>\n";


			}
			else{
				echo "<p>Le parcours des diff�rences concernant les associations �l�ves/responsables est termin�.<br />Vous allez pouvoir contr�ler les diff�rences.</p>\n";
				//echo "<p>La premi�re phase du parcours des diff�rences concernant les associations �l�ve/responsables est termin�.<br />Vous allez pouvoir passer � la deuxi�me phase avant de contr�ler les diff�rences.</p>\n";


				for($i=0;$i<count($tab_resp_diff);$i++){
					$sql="UPDATE tempo2 SET col1='t_diff' WHERE col2='$tab_resp_diff[$i]'";
					info_debug($sql);
					$update=mysql_query($sql);
				}

				//echo "<input type='hidden' name='step' value='18' />\n";
				echo "<input type='hidden' name='step' value='19' />\n";

				$sql="SELECT 1=1 FROM tempo2 WHERE col1='t_diff';";
				info_debug($sql);
				$test=mysql_query($sql);
				$nb_associations_a_consulter=mysql_num_rows($test);
				echo "<p>Parcourir les diff�rences par tranches de <input type='text' name='eff_tranche' value='".min(20,$nb_associations_a_consulter)."' size='3' /> sur un total de $nb_associations_a_consulter.<br />\n";

				echo "Ne pas proposer de supprimer des responsables non associ�s � des �l�ves <input type='checkbox' name='suppr_resp_non_assoc' value='n' /><br />\n";
				echo add_token_field();
				echo "<input type='submit' value='Afficher les diff�rences' /></p>\n";
/*
				echo "<script type='text/javascript'>
	setTimeout(\"test_stop2()\",3000);
</script>\n";
*/
			}
			echo "</form>\n";


			break;
		//case 18:
		case "19":

			//debug_var();

			echo "<h2>Import/mise � jour des associations responsables/�l�ves</h2>\n";

			check_token(false);

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			$eff_tranche=isset($_POST['eff_tranche']) ? $_POST['eff_tranche'] : 20;
			if(my_ereg("[^0-9]",$eff_tranche)) {$eff_tranche=20;}

			$suppr_resp_non_assoc=isset($_POST['suppr_resp_non_assoc']) ? $_POST['suppr_resp_non_assoc'] : 'y';
			

			echo "<form action='".$_SERVER['PHP_SELF']."' name='formulaire' method='post'>\n";
			echo add_token_field();
			//==============================
			// AJOUT pour tenir compte de l'automatisation ou non:
			echo "<input type='hidden' name='stop' id='id_form_stop' value='$stop' />\n";
			//==============================
			echo "<input type='hidden' name='eff_tranche' value='$eff_tranche' />\n";
			echo "<input type='hidden' name='suppr_resp_non_assoc' value='$suppr_resp_non_assoc' />\n";

			echo "<input type='hidden' name='temoin_phase_19' value='19' />\n";
			//if(!isset($parcours_diff)){
			if((!isset($parcours_diff))||(!isset($_POST['temoin_phase_19']))) {
				$sql="SELECT 1=1 FROM tempo2 WHERE col1='t_diff';";
				info_debug($sql);
				$test=mysql_query($sql);

				//echo "<p>".count($tab_pers_id_diff)." personnes...</p>\n";
				$nb_associations_a_consulter=mysql_num_rows($test);

				if($nb_associations_a_consulter==0){
					echo "<p>Aucune association ELE_ID/PERS_ID ne requiert votre attention.</p>\n";
				}
				elseif($nb_associations_a_consulter==1){
					echo "<p>".$nb_associations_a_consulter." association ELE_ID/PERS_ID requiert votre attention.</p>\n";
				}
				else{
					echo "<p>".$nb_associations_a_consulter." associations ELE_ID/PERS_ID requi�rent votre attention.</p>\n";
				}
				//echo "<input type='hidden' name='total_pers_diff' value='".count($tab_pers_id_diff)."' />\n";
				echo "<input type='hidden' name='total_diff' value='".$nb_associations_a_consulter."' />\n";

			}
			else{
				$modif=isset($_POST['modif']) ? $_POST['modif'] : NULL;
				$new=isset($_POST['new']) ? $_POST['new'] : NULL;

				// A VOIR: IL FAUDRAIT PEUT-ETRE VALIDER LES MODIFS D�S CE NIVEAU...
				// LES TESTS POUR NE PAS AVOIR DEUX resp_legal=1 PEUVENT ETRE PERTURB�S PAR DES ENREGISTREMENTS DIFF�R�S...

				$suppr_resp=isset($_POST['suppr_resp']) ? $_POST['suppr_resp'] : NULL;
				if(isset($suppr_resp)){
					// On modifie la valeur de col1 pour les ele_id/pers_id supprim�s pour ne pas les re-parcourir:
					for($i=0;$i<count($suppr_resp);$i++){
						$sql="UPDATE tempo2 SET col1='t_diff_suppr' WHERE col2='$suppr_resp[$i]';";
						info_debug($sql);
						$update=mysql_query($sql);
					}

					$sql="DELETE FROM responsables2 WHERE WHERE pers_id='$suppr_resp[$i]';";
					info_debug($sql);
					$nettoyage=mysql_query($sql);
				}

				if(isset($modif)){
					// On modifie la valeur de col1 pour les ele_id/pers_id confirm�s pour ne pas les re-parcourir:
					for($i=0;$i<count($modif);$i++){
						$sql="UPDATE tempo2 SET col1='t_diff_confirm' WHERE col2='$modif[$i]';";
						info_debug($sql);
						$update=mysql_query($sql);
					}

					if(isset($new)){
						// On modifie la valeur de col1 pour les ele_id/pers_id confirm�s pour ne pas les re-parcourir:
						for($i=0;$i<count($new);$i++){
							$sql="UPDATE tempo2 SET col1='t_diff_confirm' WHERE col2='$new[$i]';";
							info_debug($sql);
							$update=mysql_query($sql);
						}

						for($i=0;$i<count($liste_assoc);$i++){
							if((!in_array($liste_assoc[$i],$modif))&&(!in_array($liste_assoc[$i],$new))) {
								$sql="UPDATE tempo2 SET col1='t_diff_refus' WHERE col2='$liste_assoc[$i]';";
								info_debug($sql);
								$update=mysql_query($sql);
							}
						}
					}
					else{
						for($i=0;$i<count($liste_assoc);$i++){
							if(!in_array($liste_assoc[$i],$modif)){
								$sql="UPDATE tempo2 SET col1='t_diff_refus' WHERE col2='$liste_assoc[$i]';";
								info_debug($sql);
								$update=mysql_query($sql);
							}
						}
					}
				}
				elseif(isset($new)){
					// On modifie la valeur de col1 pour les ele_id/pers_id confirm�s pour ne pas les re-parcourir:
					for($i=0;$i<count($new);$i++){
						$sql="UPDATE tempo2 SET col1='t_diff_confirm' WHERE col2='$new[$i]';";
						info_debug($sql);
						$update=mysql_query($sql);
					}

					for($i=0;$i<count($liste_assoc);$i++){
						if(!in_array($liste_assoc[$i],$new)) {
							$sql="UPDATE tempo2 SET col1='t_diff_refus' WHERE col2='$liste_assoc[$i]';";
							info_debug($sql);
							$update=mysql_query($sql);
						}
					}
				}
				else{
					if(isset($liste_assoc)){
						for($i=0;$i<count($liste_assoc);$i++){
							$sql="UPDATE tempo2 SET col1='t_diff_refus' WHERE col2='$liste_assoc[$i]';";
							info_debug($sql);
							$update=mysql_query($sql);
						}
					}
				}

				// FAIRE LES ENREGISTREMENTS A CE NIVEAU!!!
				if(isset($modif)){
					$compteur_modifs=0;
					for($i=0;$i<count($modif);$i++){
						$tab_tmp=explode("_",$modif[$i]);
						$ele_id=$tab_tmp[1];
						$pers_id=$tab_tmp[2];

						$sql="SELECT * FROM temp_responsables2_import WHERE ele_id='$ele_id' AND pers_id='$pers_id';";
						info_debug($sql);
						$res1=mysql_query($sql);
						if(mysql_num_rows($res1)>0){
							$lig1=mysql_fetch_object($res1);

							$resp_legal=$lig1->resp_legal;
							$pers_contact=$lig1->pers_contact;

							$sql="SELECT * FROM responsables2 WHERE ele_id='$ele_id' AND pers_id='$pers_id';";
							info_debug($sql);
							$test1=mysql_query($sql);
							// Pour une modif, ce test doit toujours �tre vrai.
							if(mysql_num_rows($test1)>0){
								$sql="DELETE FROM responsables2 WHERE ele_id='$ele_id' AND pers_id='$pers_id';";
								info_debug($sql);
								$suppr=mysql_query($sql);
							}

							// Il ne peut pas y avoir 2 resp_legal 1, ni 2 resp_legal 2 pour un m�me �l�ve.
							if(($resp_legal==1)||($resp_legal==2)) {
								$sql="SELECT * FROM responsables2 WHERE ele_id='$ele_id' AND resp_legal='$resp_legal';";
								info_debug($sql);
								$test2=mysql_query($sql);

								/*
								if(mysql_num_rows($test2)>0){
									//$lig2=mysql_fetch_object($test2);
									$sql="UPDATE responsables2 SET pers_id='$pers_id',
																	pers_contact='$pers_contact'
															WHERE ele_id='$ele_id' AND
																	resp_legal='$resp_legal';";
									$update=mysql_query($sql);
								}
								else{
									$sql="INSERT INTO responsables2 SET pers_id='$pers_id',
																	pers_contact='$pers_contact',
																	ele_id='$ele_id',
																	resp_legal='$resp_legal';";
									$update=mysql_query($sql);
								}
								*/

								if(mysql_num_rows($test2)>0){
									$sql="DELETE FROM responsables2 WHERE ele_id='$ele_id' AND
																	resp_legal='$resp_legal';";
									info_debug($sql);
									$delete=mysql_query($sql);
								}

								$sql="INSERT INTO responsables2 SET pers_id='$pers_id',
																pers_contact='$pers_contact',
																ele_id='$ele_id',
																resp_legal='$resp_legal';";
								info_debug($sql);
								$insert=mysql_query($sql);
							}
							else{
								// Cas de resp_legal=0
								$sql="INSERT INTO responsables2 SET pers_id='$pers_id',
																pers_contact='$pers_contact',
																ele_id='$ele_id',
																resp_legal='$resp_legal';";
								info_debug($sql);
								$insert=mysql_query($sql);
							}
						}
					}
					//===========================
					// A FAIRE: boireaus 20071115
					// Indiquer combien d'enregistrements viennent d'�tre effectu�s.
					//===========================
				}

				if(isset($new)){
					for($i=0;$i<count($new);$i++){
						$tab_tmp=explode("_",$new[$i]);
						$ele_id=$tab_tmp[1];
						$pers_id=$tab_tmp[2];

						$sql="SELECT * FROM temp_responsables2_import WHERE ele_id='$ele_id' AND pers_id='$pers_id';";
						info_debug($sql);
						$res1=mysql_query($sql);
						if(mysql_num_rows($res1)>0){
							$lig1=mysql_fetch_object($res1);

							$resp_legal=$lig1->resp_legal;
							$pers_contact=$lig1->pers_contact;

							$sql="SELECT * FROM responsables2 WHERE ele_id='$ele_id' AND pers_id='$pers_id';";
							info_debug($sql);
							$test1=mysql_query($sql);
							// Pour une 'new', ce test doit toujours �tre faux.
							if(mysql_num_rows($test1)>0){
								$sql="DELETE FROM responsables2 WHERE ele_id='$ele_id' AND pers_id='$pers_id';";
								info_debug($sql);
								$suppr=mysql_query($sql);
							}

							// Il ne peut pas y avoir 2 resp_legal 1, ni 2 resp_legal 2 pour un m�me �l�ve.
							if(($resp_legal==1)||($resp_legal==2)) {
								$sql="SELECT * FROM responsables2 WHERE ele_id='$ele_id' AND resp_legal='$resp_legal';";
								info_debug($sql);
								$test2=mysql_query($sql);
								/*
								if(mysql_num_rows($test2)>0){
									//$lig2=mysql_fetch_object($test2);
									$sql="UPDATE responsables2 SET pers_id='$pers_id',
																	pers_contact='$pers_contact'
															WHERE ele_id='$ele_id' AND
																	resp_legal='$resp_legal';";
									$update=mysql_query($sql);
								}
								else{
									$sql="INSERT INTO responsables2 SET pers_id='$pers_id',
																	pers_contact='$pers_contact',
																	ele_id='$ele_id',
																	resp_legal='$resp_legal';";
									$update=mysql_query($sql);
								}
								*/

								if(mysql_num_rows($test2)>0){
									$sql="DELETE FROM responsables2 WHERE ele_id='$ele_id' AND
																	resp_legal='$resp_legal';";
									info_debug($sql);
									$delete=mysql_query($sql);
								}

								$sql="INSERT INTO responsables2 SET pers_id='$pers_id',
																pers_contact='$pers_contact',
																ele_id='$ele_id',
																resp_legal='$resp_legal';";
								info_debug($sql);
								$insert=mysql_query($sql);

							}
							else{
								// Cas de resp_legal=0
								$sql="INSERT INTO responsables2 SET pers_id='$pers_id',
																pers_contact='$pers_contact',
																ele_id='$ele_id',
																resp_legal='$resp_legal';";
								info_debug($sql);
								$update=mysql_query($sql);
							}
						}
					}
					//===========================
					// A FAIRE: boireaus 20071115
					// Indiquer combien d'enregistrements viennent d'�tre effectu�s.
					//===========================
				}




				$sql="SELECT 1=1 FROM tempo2 WHERE col1='t_diff';";
				info_debug($sql);
				$test=mysql_query($sql);

				if(mysql_num_rows($test)>0){
					echo "<p>".mysql_num_rows($test)." associations restantes sur un total de $total_diff.</p>\n";
				}
				else{
					echo "<p>Toutes les associations (<i>$total_diff</i>) ont �t� parcourues.</p>\n";
				}
				echo "<input type='hidden' name='total_diff' value='".$total_diff."' />\n";
			}

			flush();

			echo "<input type='hidden' name='parcours_diff' value='y' />\n";

			//$eff_tranche=20;

			$sql="SELECT col2 FROM tempo2 WHERE col1='t_diff' LIMIT $eff_tranche";
			info_debug($sql);
			//echo "$sql<br />";
			$res0=mysql_query($sql);

			if(mysql_num_rows($res0)>0){

				//echo "<p align='center'><input type='submit' value='Poursuivre' /></p>\n";
				echo "<p align='center'><input type=submit value='Valider' /></p>\n";

				// Affichage du tableau

				//echo "<table border='1'>\n";
				//echo "<table class='majimport'>\n";
				echo "<table class='boireaus'>\n";
				echo "<tr>\n";

				//echo "<td style='text-align: center; font-weight: bold;' rowspan='2'>Enregistrer<br />\n";
				echo "<td style='text-align: center; font-weight: bold;' rowspan='2'>Modifier<br />\n";
				echo "<a href=\"javascript:modifcase('coche')\">";
				echo "<img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>";
				echo " / ";
				echo "<a href=\"javascript:modifcase('decoche')\">";
				echo "<img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>";
				echo "</td>\n";

				echo "<td rowspan='2'>&nbsp;</td>\n";

				echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);' colspan='5'>Responsable</td>\n";

				echo "<td style='text-align:center; font-weight:bold; background-color: #FAFABE;' colspan='3'>El�ve</td>\n";

				//=========================
				// AJOUT: boireaus 20071129
				echo "<td style='text-align:center; font-weight:bold; background-color: red;' rowspan='2'>Suppression<br />du responsable</td>\n";
				//=========================

				echo "</tr>\n";

				echo "<tr>\n";
				echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>pers_id</td>\n";
				echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Nom</td>\n";
				echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>Pr�nom</td>\n";
				echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>resp_legal</td>\n";
				echo "<td style='text-align:center; font-weight:bold; background-color: rgb(150, 200, 240);'>pers_contact</td>\n";

				echo "<td style='text-align:center; font-weight:bold; background-color: #FAFABE;'>Nom</td>\n";
				echo "<td style='text-align:center; font-weight:bold; background-color: #FAFABE;'>Pr�nom</td>\n";
				echo "<td style='text-align:center; font-weight:bold; background-color: #FAFABE;'>ele_id</td>\n";

				echo "</tr>\n";

				$cpt_nb_lig_tab=0;

				$alt=1;

				$cpt=0;
				$nb_reg_no1=0;
				$nb_record1=0;
				//for($k = 1; ($k < $nblignes+1); $k++){
				while($lig0=mysql_fetch_object($res0)){
					$tab_tmp=explode("_",$lig0->col2);

					$temoin_suppr_resp="n";
					$ligne_courante="";

					$ele_id=$tab_tmp[1];
					$pers_id=$tab_tmp[2];

					$sql="SELECT * FROM temp_responsables2_import WHERE ele_id='$ele_id' AND pers_id='$pers_id'";
					info_debug($sql);
					//echo "$sql<br />";
					$res0b=mysql_query($sql);
					if(mysql_num_rows($res0b)==0){
						// CA NE DOIT PAS ARRIVER
						echo "<tr><td>ANOMALIE! Ce cas ne devrait pas arriver</td></tr>\n";
					}
					else{
						$lig0b=mysql_fetch_object($res0b);

						$resp_legal=$lig0b->resp_legal;
						$pers_contact=$lig0b->pers_contact;
					}


					//echo "<tr>\n";

					//$sql="SELECT * FROM responsables2 WHERE ele_id='$affiche[0]' AND pers_id='$affiche[1]'";
					$sql="SELECT * FROM responsables2 WHERE (ele_id='$ele_id' AND pers_id='$pers_id')";
					info_debug($sql);
					//echo "$sql<br />";
					$res1=mysql_query($sql);
					if(mysql_num_rows($res1)==0){
						// L'association responsable/eleve n'existe pas encore
						$resp_new[]="$ele_id:$pers_id";
						info_debug("Nouvelle association $ele_id:$pers_id\n");


						$alt=$alt*(-1);
						/*
						echo "<tr style='background-color:";
						if($alt==1){
							echo "silver";
						}
						else{
							echo "white";
						}
						echo ";'>\n";
						*/
						$ligne_courante.="<tr class='lig$alt'>\n";

						$sql="SELECT nom,prenom FROM resp_pers WHERE (pers_id='$pers_id')";
						info_debug($sql);
						$res2=mysql_query($sql);
						if(mysql_num_rows($res2)==0){
							// Probl�me: On ne peut pas importer l'association sans que la personne existe.
							// Est-ce que l'�tape d'import de la personne a �t� refus�e?
							$ligne_courante.="<td>&nbsp;</td>\n";
							$ligne_courante.="<td>&nbsp;</td>\n";

							$ligne_courante.="<td style='background-color:red;'>&nbsp;</td>\n";
							//$ligne_courante.="<td colspan='5'>Aucune personne associ�e???</td>\n";
							$ligne_courante.="<td colspan='7'>Aucune personne associ�e???</td>\n";
							info_debug("Aucune personne associ�e???\n");

							//=========================
							// AJOUT: boireaus 20071129
							//$ligne_courante.="<td style='text-align:center;'><input type='checkbox' name='suppr_resp[]' value='$pers_id' /></td>\n";
							//$ligne_courante.="<td style='text-align:center;'>&nbsp;</td>\n";
							//=========================

						}
						else{
							$lig2=mysql_fetch_object($res2);
							$ligne_courante.="<td style='text-align:center;'>\n";
							//$ligne_courante.="<input type='checkbox' id='check_".$cpt."' name='new[]' value='$cpt' />";

							// El�ve(s) associ�(s)
							$sql="SELECT nom,prenom FROM eleves WHERE (ele_id='$ele_id')";
							info_debug($sql);
							$res4=mysql_query($sql);
							if(mysql_num_rows($res4)>0){
								$ligne_courante.="<input type='checkbox' id='check_".$cpt."' name='new[]' value='$lig0->col2' />\n";
							}
							$ligne_courante.="<input type='hidden' name='liste_assoc[]' value='$lig0->col2' />\n";
							$ligne_courante.="</td>\n";

							//$ligne_courante.="<td style='text-align:center; background-color: rgb(150, 200, 240);'>Nouveau</td>\n";
							$ligne_courante.="<td class='nouveau'>Nouveau</td>\n";

							$ligne_courante.="<td style='text-align:center;'>\n";
							$ligne_courante.="$pers_id";
							//$ligne_courante.="<input type='hidden' name='new_".$cpt."_pers_id' value='$pers_id' />\n";
							$ligne_courante.="</td>\n";

							$ligne_courante.="<td style='text-align:center;'>\n";
							$ligne_courante.="$lig2->nom";
							//$ligne_courante.="<input type='hidden' name='new_".$cpt."_resp_nom' value=\"$lig2->nom\" />\n";
							$ligne_courante.="</td>\n";

							$ligne_courante.="<td style='text-align:center;'>\n";
							$ligne_courante.="$lig2->prenom";
							//$ligne_courante.="<input type='hidden' name='new_".$cpt."_resp_prenom' value=\"$lig2->prenom\" />\n";
							$ligne_courante.="</td>\n";

							// Existe-t-il d�j� un num�ro de responsable l�gal 1 ou 2 correspondant au nouvel arrivant?
							// Il peut y avoir en revanche plus d'un resp_legal=0

							//$ligne_courante.="<td style='text-align:center;";
							$ligne_courante.="<td";
							//$sql="SELECT 1=1 FROM responsables2 WHERE (pers_id!='$pers_id' AND ele_id='$ele_id' AND resp_legal='$resp_legal')";
							$sql="SELECT 1=1 FROM responsables2 WHERE (pers_id!='$pers_id' AND ele_id='$ele_id' AND resp_legal='$resp_legal' AND (resp_legal='1' OR resp_legal='2'))";
							info_debug($sql);
							$res3=mysql_query($sql);
							if(mysql_num_rows($res3)==0){
								//$ligne_courante.="'>\n";
								$ligne_courante.=">\n";
							}
							else{
								//$ligne_courante.=" background-color: lightgreen;'>\n";
								$ligne_courante.=" class='modif'>\n";
							}
							$ligne_courante.="$resp_legal";
							//$ligne_courante.="<input type='hidden' name='new_".$cpt."_resp_legal' value='$resp_legal' />\n";
							$ligne_courante.="</td>\n";

							$ligne_courante.="<td style='text-align:center;'>\n";
							$ligne_courante.="$pers_contact";
							//$ligne_courante.="<input type='hidden' name='new_".$cpt."_pers_contact' value='$pers_contact' />\n";
							$ligne_courante.="</td>\n";

							// El�ve(s) associ�(s)
							/*
							$sql="SELECT nom,prenom FROM eleves WHERE (ele_id='$ele_id')";
							$res4=mysql_query($sql);
							*/
							if(mysql_num_rows($res4)==0){
								$ligne_courante.="<td style='text-align:center; background-color:red;' colspan='3'>\n";
								$ligne_courante.="Aucun �l�ve pour ele_id=$ele_id ???";
								$ligne_courante.="</td>\n";

								//=========================
								// AJOUT: boireaus 20071129
								//$ligne_courante.="<td style='text-align:center;'><input type='checkbox' name='suppr_resp[]' value='$pers_id' /></td>\n";
								//=========================
							}
							else{
								$lig4=mysql_fetch_object($res4);
								$ligne_courante.="<td style='text-align:center;'>\n";
								$ligne_courante.="$lig4->nom";
								//$ligne_courante.="<input type='hidden' name='new_".$cpt."_ele_nom' value=\"$lig4->nom\" />\n";
								$ligne_courante.="</td>\n";

								$ligne_courante.="<td style='text-align:center;'>\n";
								$ligne_courante.="$lig4->prenom";
								//$ligne_courante.="<input type='hidden' name='new_".$cpt."_ele_prenom' value=\"$lig4->prenom\" />\n";
								$ligne_courante.="</td>\n";

								$ligne_courante.="<td style='text-align:center;'>\n";
								$ligne_courante.="$ele_id";
								//$ligne_courante.="<input type='hidden' name='new_".$cpt."_ele_id' value='$ele_id' />\n";
								$ligne_courante.="</td>\n";

								//=========================
								// AJOUT: boireaus 20071129
								//$ligne_courante.="<td style='text-align:center;'>&nbsp;</td>\n";
								//=========================
							}
						}


						//=========================
						// AJOUT: boireaus 20071129

						// TESTER SI LE RESPONSABLE EST ASSOCI� AVEC UN ELEVE EXISTANT AU MOINS
						$sql="SELECT e.ele_id FROM eleves e, resp_pers rp, temp_responsables2_import r
										WHERE e.ele_id=r.ele_id AND
												r.pers_id=rp.pers_id AND
												rp.pers_id='$pers_id'";
						info_debug($sql);
						$test=mysql_query($sql);
						if(mysql_num_rows($test)>0) {
							//$ligne_courante.="<td style='text-align:center;'>&nbsp;</td>\n";
							$ligne_courante.="<td style='text-align:center;'>";
							//while($lig_tmp_test=mysql_fetch_object($test)){$ligne_courante.="$lig_tmp_test->ele_id - ";}
							$ligne_courante.="&nbsp;\n";
							$ligne_courante.="</td>\n";
						}
						else{
							$ligne_courante.="<td style='text-align:center;'><input type='checkbox' name='suppr_resp[]' value='$pers_id' /></td>\n";
							$temoin_suppr_resp="y";
						}
						//=========================


						$ligne_courante.="</tr>\n";
						$cpt_nb_lig_tab++;
					}
					else{


						$lig1=mysql_fetch_object($res1);
						if((stripslashes($lig1->resp_legal)!=stripslashes($resp_legal))||
						(stripslashes($lig1->pers_contact)!=stripslashes($pers_contact))){
							//$ligne_courante.="temoin<br />";
							// L'un des champs resp_legal ou pers_contact au moins a chang�
							//$resp_modif[]="$affiche[0]:$affiche[1]";
							$resp_modif[]="$ele_id:$pers_id";

							info_debug("Modification association $ele_id:$pers_id -> $resp_legal\n");

							$alt=$alt*(-1);
							/*
							$ligne_courante.="<tr style='background-color:";
							if($alt==1){
								$ligne_courante.="silver";
							}
							else{
								$ligne_courante.="white";
							}
							$ligne_courante.=";'>\n";
							*/
							$ligne_courante.="<tr class='lig$alt'>\n";

							$sql="SELECT nom,prenom FROM resp_pers WHERE (pers_id='$pers_id')";
							info_debug($sql);
							$res2=mysql_query($sql);
							if(mysql_num_rows($res2)==0){
								// Probl�me: On ne peut pas importer l'association sans que la personne existe.
								// Est-ce que l'�tape d'import de la personne a �t� refus�e?
								$ligne_courante.="<td>&nbsp;</td>\n";
								$ligne_courante.="<td>&nbsp;</td>\n";

								$ligne_courante.="<td style='background-color:red;'>&nbsp;</td>\n";
								$ligne_courante.="<td colspan='5'>Aucune personne associ�e???</td>\n";
								info_debug("Aucune personne associ�e???\n");

								//=========================
								// AJOUT: boireaus 20071129
								//$ligne_courante.="<td style='text-align:center;'><input type='checkbox' name='suppr_resp[]' value='$pers_id' /></td>\n";
								//=========================
							}
							else{
								$lig2=mysql_fetch_object($res2);
								$ligne_courante.="<td style='text-align:center;'>\n";
								//$ligne_courante.="<input type='checkbox' id='check_".$cpt."' name='modif[]' value='$cpt' />";

								// El�ve(s) associ�(s)
								$sql="SELECT nom,prenom FROM eleves WHERE (ele_id='$ele_id')";
								info_debug($sql);
								$res4=mysql_query($sql);
								if(mysql_num_rows($res4)>0){
									$ligne_courante.="<input type='checkbox' id='check_".$cpt."' name='modif[]' value='$lig0->col2' />\n";
								}
								$ligne_courante.="<input type='hidden' name='liste_assoc[]' value='$lig0->col2' />\n";
								$ligne_courante.="</td>\n";

								//$ligne_courante.="<td style='text-align:center; background-color:lightgreen;'>Modif</td>\n";
								$ligne_courante.="<td class='modif'>Modif</td>\n";

								$ligne_courante.="<td style='text-align:center;'>\n";
								$ligne_courante.="$pers_id";
								//$ligne_courante.="<input type='hidden' name='modif_".$cpt."_pers_id' value='$pers_id' />\n";
								$ligne_courante.="</td>\n";

								$ligne_courante.="<td style='text-align:center;'>\n";
								$ligne_courante.="$lig2->nom";
								//$ligne_courante.="<input type='hidden' name='modif_".$cpt."_resp_nom' value=\"".addslashes($lig2->nom)."\" />\n";
								//$ligne_courante.="<input type='hidden' name='modif_".$cpt."_resp_nom' value=\"".$lig2->nom."\" />\n";
								$ligne_courante.="</td>\n";

								$ligne_courante.="<td style='text-align:center;'>\n";
								$ligne_courante.="$lig2->prenom";
								//$ligne_courante.="<input type='hidden' name='modif_".$cpt."_resp_prenom' value=\"".addslashes($lig2->nom)."\" />\n";
								//$ligne_courante.="<input type='hidden' name='modif_".$cpt."_resp_prenom' value=\"".$lig2->prenom."\" />\n";
								$ligne_courante.="</td>\n";

								// Existe-t-il d�j� un num�ro de responsable l�gal 1 ou 2 correspondant au nouvel arrivant?

								//$ligne_courante.="<td style='text-align:center;";
								$ligne_courante.="<td";
								//$sql="SELECT 1=1 FROM responsables2 WHERE (pers_id!='$pers_id' AND ele_id='$ele_id' AND resp_legal='$resp_legal')";
								$sql="SELECT 1=1 FROM responsables2 WHERE (pers_id!='$pers_id' AND ele_id='$ele_id' AND resp_legal='$resp_legal' AND (resp_legal='1' OR resp_legal='2'))";
								info_debug($sql);
								$res3=mysql_query($sql);
								if(mysql_num_rows($res3)==0){
									//$ligne_courante.="'>\n";
									$ligne_courante.=">\n";
								}
								else{
									//$ligne_courante.=" background-color: lightgreen;'>\n";
									$ligne_courante.=" class='modif'>\n";
								}
								$ligne_courante.="$resp_legal";
								//$ligne_courante.="<input type='hidden' name='modif_".$cpt."_resp_legal' value='$resp_legal' />\n";
								$ligne_courante.="</td>\n";

								$ligne_courante.="<td style='text-align:center;'>\n";
								$ligne_courante.="$pers_contact";
								//$ligne_courante.="<input type='hidden' name='modif_".$cpt."_pers_contact' value='$pers_contact' />\n";
								$ligne_courante.="</td>\n";

								// El�ve(s) associ�(s)
								//$sql="SELECT nom,prenom FROM eleves WHERE (ele_id='$ele_id')";
								//$res4=mysql_query($sql);
								if(mysql_num_rows($res4)==0){
									$ligne_courante.="<td style='text-align:center; background-color:red;' colspan='3'>\n";
									$ligne_courante.="Aucun �l�ve pour ele_id=$ele_id ???";
									$ligne_courante.="</td>\n";

									//=========================
									// AJOUT: boireaus 20071129
									//$ligne_courante.="<td style='text-align:center;'><input type='checkbox' name='suppr_resp[]' value='$pers_id' /></td>\n";
									//=========================
								}
								else{
									$lig4=mysql_fetch_object($res4);
									$ligne_courante.="<td style='text-align:center;'>\n";
									$ligne_courante.="$lig4->nom";
									//$ligne_courante.="<input type='hidden' name='modif_".$cpt."_ele_nom' value=\"".addslashes($lig4->nom)."\" />\n";
									//$ligne_courante.="<input type='hidden' name='modif_".$cpt."_ele_nom' value=\"".$lig4->nom."\" />\n";
									$ligne_courante.="</td>\n";

									$ligne_courante.="<td style='text-align:center;'>\n";
									$ligne_courante.="$lig4->prenom";
									//$ligne_courante.="<input type='hidden' name='modif_".$cpt."_ele_prenom' value=\"".addslashes($lig4->prenom)."\" />\n";
									//$ligne_courante.="<input type='hidden' name='modif_".$cpt."_ele_prenom' value=\"".$lig4->prenom."\" />\n";
									$ligne_courante.="</td>\n";

									$ligne_courante.="<td style='text-align:center;'>\n";
									$ligne_courante.="$ele_id";
									//$ligne_courante.="<input type='hidden' name='modif_".$cpt."_ele_id' value='$ele_id' />\n";
									$ligne_courante.="</td>\n";

									//=========================
									// AJOUT: boireaus 20071129
									//$ligne_courante.="<td style='text-align:center;'>&nbsp;</td>\n";
									//=========================
								}

							}

							//=========================
							// AJOUT: boireaus 20071129

							// TESTER SI LE RESPONSABLE EST ASSOCI� AVEC UN ELEVE EXISTANT AU MOINS
							$sql="SELECT e.ele_id FROM eleves e, resp_pers rp, temp_responsables2_import r
											WHERE e.ele_id=r.ele_id AND
													r.pers_id=rp.pers_id AND
													rp.pers_id='$pers_id'";
							info_debug($sql);
							$test=mysql_query($sql);
							if(mysql_num_rows($test)>0) {
								//$ligne_courante.="<td style='text-align:center;'>&nbsp;</td>\n";
								$ligne_courante.="<td style='text-align:center;'>";
								//while($lig_tmp_test=mysql_fetch_object($test)){$ligne_courante.="$lig_tmp_test->ele_id - ";}
								$ligne_courante.="&nbsp;\n";
								$ligne_courante.="</td>\n";
							}
							else{
								$ligne_courante.="<td style='text-align:center;'><input type='checkbox' name='suppr_resp[]' value='$pers_id' /></td>\n";
								$temoin_suppr_resp="y";
							}
							//=========================

							$ligne_courante.="</tr>\n";
							$cpt_nb_lig_tab++;
						}
						// Sinon, il n'est pas n�cessaire de refaire l'inscription d�j� pr�sente.
						else {
							$sql="UPDATE tempo2 SET col1='t_diff_pas_modif' WHERE col2='t_".$ele_id."_".$pers_id."'";
							info_debug($sql);
							info_debug("Pas de modif de responsabilit�\n");
							$update=mysql_query($sql);
						}
					}

					if($suppr_resp_non_assoc="y") {
						echo $ligne_courante;
					}
					elseif(($temoin_suppr_resp="n")&&($suppr_resp_non_assoc="n")) {
						echo $ligne_courante;
					}

					//echo "</tr>\n";
					$cpt++;
				}
				echo "</table>\n";

				echo "<script type='text/javascript'>
	function modifcase(mode){
		for(i=0;i<$cpt;i++){
			if(document.getElementById('check_'+i)){
				if(mode=='coche'){
					document.getElementById('check_'+i).checked=true;
				}
				else{
					document.getElementById('check_'+i).checked=false;
				}
			}
		}
	}
</script>\n";

				//echo "<input type='hidden' name='step' value='18' />\n";
				echo "<input type='hidden' name='step' value='19' />\n";

				if($cpt_nb_lig_tab==0) {
					echo "<p>Aucune ligne de diff�rence n'est propos�e apr�s contr�le.</p>\n";
				}
				else {
					echo "<p align='center'><input type=submit value='Valider' /></p>\n";
				}

				echo "<p><br /></p>\n";
				echo "<p><i>NOTES:</i></p>\n";
				echo "<ul>\n";
				echo "<li>La case de suppression d'un responsable n'est propos�e que s'il n'est associ� � aucun �l�ve effectivement pr�sent dans votre table 'eleves'.</li>\n";
				echo "<li>Le message 'Aucun �l�ve pour ele_id=...' signifie que l'import fait r�f�rence � un identifiant d'�l�ve qui n'est plus dans l'�tablissement ou qui �tait propos� � l'import des �l�ves et que vous n'avez pas coch�.<br />Cela ne signifie pas que le responsable n'est pas associ� � autre �l�ve qui lui est bien pr�sent dans votre table 'eleves'.<br />Rien n'est ins�r� dans la table 'responsables2' pour ces lignes.</li>\n";
				echo "</ul>\n";

			}
			else{
				//echo "<input type='hidden' name='step' value='19' />\n";
				echo "<input type='hidden' name='step' value='20' />\n";
/*
				echo "<p>Nettoyage des tables temporaires: ";
				unset($liste_tab_del);
				$liste_tab_del=array("temp_ele_classe", "temp_gep_import2", "temp_resp_adr_import", "temp_resp_pers_import", "temp_responsables2_import", "tempo2");
				$j=0;
				for($i=0;$i<count($liste_tab_del);$i++){
					if($liste_tab_del[$i]!=""){
						if($j>0){echo ", ";}
						echo $liste_tab_del[$i];
						$sql="TRUNCATE TABLE $liste_tab_del[$i];";
						$nettoyage=mysql_query($sql);
						$j++;
					}
				}
				echo "</p>\n";
*/

				$sql="SELECT r.pers_id,r.ele_id FROM responsables2 r LEFT JOIN eleves e ON e.ele_id=r.ele_id WHERE e.ele_id is NULL;";
				info_debug($sql);
				$test=mysql_query($sql);
				if(mysql_num_rows($test)>0){
					echo "<p>Suppression de responsabilit�s sans �l�ve.<br />Voici la liste des identifiants de responsables qui �taient associ�s � des �l�ves inexistants: \n";
					$cpt_nett=0;
					while($lig_nett=mysql_fetch_object($test)){
						if($cpt_nett>0){echo ", ";}
						echo "<a href='modify_resp.php?pers_id=$lig_nett->pers_id' target='_blank'>".$lig_nett->pers_id."</a>";
						$sql="DELETE FROM responsables2 WHERE pers_id='$lig_nett->pers_id' AND ele_id='$lig_nett->ele_id';";
						info_debug($sql);
						$nettoyage=mysql_query($sql);
						flush();
						$cpt_nett++;
					}
					echo ".</p>\n";
					echo "<p>$cpt_nett associations aberrantes supprim�es.</p>\n";
				}

				echo "<p align='center'><input type=submit value='Contr�ler les suppressions de responsabilit�s' /></p>\n";

				//echo "<p align='center'><input type=submit value='Terminer' /></p>\n";
				/*
				echo "<p>Retour �:</p>\n";
				echo "<ul>\n";
				echo "<li><a href='../accueil.php'>l'accueil</a></li>\n";
				echo "<li><a href='index.php'>l'index Responsables</a></li>\n";
				echo "<li><a href='../eleves/index.php'>l'index El�ves</a></li>\n";
				echo "</ul>\n";
				*/
			}

			echo "</form>\n";

			break;
		//case 19:
		case "20":

			info_debug("==============================================");
			info_debug("=============== Phase step $step =================");

			echo "<h2>Traitement des responsabilit�s disparues</h2>\n";

			$sql="SELECT ele_id, pers_id FROM responsables2 WHERE CONCAT(ele_id,'_',pers_id) NOT IN (SELECT CONCAT(ele_id,'_',pers_id) FROM temp_responsables2_import);";
			$res=mysql_query($sql);
			$nb=mysql_num_rows($res);
			if($nb==0) {
				echo "<p>Toutes les associations inscrites dans votre table de responsabilit�s sont bien pr�sentes dans le fichier XML import�.<br />
				Il ne reste donc pas d'association ind�sirable (<i>sous r�serve que vous ayez bien tenu compte des �ventuelles modifications propos�es lors de la phase Responsabilit�s</i>).</p>\n";

				echo "<p>Retour �:</p>\n";
				echo "<ul>\n";
				echo "<li><a href='../accueil.php'>l'accueil</a></li>\n";
				echo "<li><a href='index.php'>l'index Responsables</a></li>\n";
				echo "<li><a href='../eleves/index.php'>l'index El�ves</a></li>\n";
				echo "</ul>\n";
			}
			else {
				if($nb==1) {
					echo "<p>$nb suppression de responsabilit� a �t� relev�e.<br />Votre base comporte une responsabilit� qui n'est plus pr�sente dans le fichier XML import�.</p>\n";
				}
				else {
					echo "<p>$nb suppressions de responsabilit�s ont �t� relev�es.<br />Votre base comporte des responsabilit�s qui ne sont plus pr�sentes dans le fichier XML import�.</p>\n";
				}

				echo "<form action='".$_SERVER['PHP_SELF']."' name='formulaire' method='post'>\n";
				echo add_token_field();

				echo "<table class='boireaus' summary='Tableau des responsabilit�s disparues'>\n";
				echo "<tr>\n";
				echo "<th colspan='2'>\n";
				echo "El�ve\n";
				echo "</th>\n";
				echo "<th colspan='2'>\n";
				echo "Responsable\n";
				echo "</th>\n";
				echo "<th rowspan='2'>\n";
				echo "Suppression<br />\n";

				echo "<a href=\"javascript:modifcase('coche')\">";
				echo "<img src='../images/enabled.png' width='15' height='15' alt='Tout cocher' /></a>";
				echo " / ";
				echo "<a href=\"javascript:modifcase('decoche')\">";
				echo "<img src='../images/disabled.png' width='15' height='15' alt='Tout d�cocher' /></a>";
				echo "</th>\n";
				echo "</tr>\n";

				echo "<tr>\n";
				echo "<th>\n";
				echo "Ele_id\n";
				echo "</th>\n";
				echo "<th>\n";
				echo "Nom pr�nom\n";
				echo "</th>\n";
				echo "<th>\n";
				echo "Nom pr�nom\n";
				echo "</th>\n";
				echo "<th>\n";
				echo "Resp_legal\n";
				echo "</th>\n";
				echo "</tr>\n";

				$alt=1;
				$cpt=0;
				while($lig=mysql_fetch_object($res)) {
					$alt=$alt*(-1);
					echo "<tr class='lig$alt white_hover'>\n";
					echo "<td>\n";
					echo $lig->ele_id;
					echo "</td>\n";

					echo "<td>\n";
					$sql="SELECT nom,prenom FROM eleves WHERE ele_id='$lig->ele_id';";
					$res2=mysql_query($sql);
					if(mysql_num_rows($res2)==0) {
						echo "El�ve inconnu";
					}
					else {
						$lig2=mysql_fetch_object($res2);
						echo casse_mot($lig2->nom)." ".casse_mot($lig2->prenom,'majf2');
					}
					echo "</td>\n";


					echo "<td>\n";
					// Civilite Nom Prenom du responsable
					$sql="SELECT civilite,nom,prenom,resp_legal FROM resp_pers rp, responsables2 r WHERE rp.pers_id='$lig->pers_id' AND rp.pers_id=r.pers_id AND r.ele_id='$lig->ele_id';";
					$res2=mysql_query($sql);
					if(mysql_num_rows($res2)==0) {
						echo "Reponsable inconnu";
						echo "</td>\n";
						echo "<td>\n";
						// avec rang responsabilit� initiale
						echo "?";
					}
					else {
						$lig2=mysql_fetch_object($res2);
						echo $lig2->civilite." ".casse_mot($lig2->nom)." ".casse_mot($lig2->prenom,'majf2');
						echo "</td>\n";
						echo "<td>\n";
						// avec rang responsabilit� initiale
						echo $lig2->resp_legal;
					}
					echo "</td>\n";

					echo "<td><input type='checkbox' name='suppr_resp_ele[]' id='suppr_resp_ele_$cpt' value='".$lig->ele_id."_".$lig->pers_id."' /></td>\n";
					echo "</tr>\n";
					$cpt++;
				}


				echo "<input type='hidden' name='step' value='21' />\n";
				echo "<p align='center'><input type=submit value='Valider' /></p>\n";
				echo "</form>\n";

				echo "<script type='text/javascript'>
	function modifcase(mode){
		for(i=0;i<$cpt;i++){
			if(document.getElementById('suppr_resp_ele_'+i)){
				if(mode=='coche'){
					document.getElementById('suppr_resp_ele_'+i).checked=true;
				}
				else{
					document.getElementById('suppr_resp_ele_'+i).checked=false;
				}
			}
		}
	}
</script>\n";

			}

			break;
		case "21":
			echo "<h2>Traitement des responsabilit�s disparues</h2>\n";

			check_token(false);

			$suppr_resp_ele=isset($_POST['suppr_resp_ele']) ? $_POST['suppr_resp_ele'] : NULL;

			if(isset($suppr_resp_ele)) {
				$nb_suppr=0;
				$nb_err=0;
				for($i=0;$i<count($suppr_resp_ele);$i++) {
					//echo "<p>\$suppr_resp_ele[$i]=$suppr_resp_ele[$i]<br />";
					$tmp_tab=explode("_",$suppr_resp_ele[$i]);
					$ele_id=$tmp_tab[0];
					$pers_id=$tmp_tab[1];

					$sql="DELETE FROM responsables2 WHERE pers_id='$pers_id' AND ele_id='$ele_id';";
					//echo "$sql<br />";
					$res=mysql_query($sql);
					if(!$res) {
						$nb_err++;
					}
					else {
						$nb_suppr++;
					}
				}

				echo "<p>$nb_suppr suppression(s) de responsabilit�(s).<br />$nb_err erreur(s).</p>\n";
			}

			echo "<p><br /></p>\n";

			echo "<p>Retour �:</p>\n";
			echo "<ul>\n";
			echo "<li><a href='../accueil.php'>l'accueil</a></li>\n";
			echo "<li><a href='index.php'>l'index Responsables</a></li>\n";
			echo "<li><a href='../eleves/index.php'>l'index El�ves</a></li>\n";
			echo "</ul>\n";

			break;
	}
}

/*
echo "<p><i>NOTES:</i></p>\n";
echo "<ul>\n";
echo "<li>\n";
echo "<p>Les noms de fichiers fournis dans les champs de formulaires doivent co�ncider avec le nom indiqu� ELEVES.CSV, ADRESSES.CSV,...\n";
echo "</p>\n";
echo "</li>\n";
echo "<li>";
echo "<p>Il reste aussi � assurer l'import de l'�tablissement d'origine avec les fichiers etablissements.csv et eleves_etablissements.csv<br />\n";
echo "Par ailleurs, l'inscription des �l�ves dans telle ou telle classe, avec telle et telle option n'est pas encore assur�e par cette page d'importation/mise � jour.<br />\n";
echo "(<i>il faut donc par la suite affecter les nouveaux �l�ves dans les classes et les inscrire dans les groupes/options/mati�res</i>)<br />\n";
echo "</p>\n";
echo "</li>\n";
echo "</ul>\n";
*/

echo "<p><br /></p>\n";
require("../lib/footer.inc.php");
?>
