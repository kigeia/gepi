<?php
/* $Id$ */
/*
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





//======================================================================================
// Section checkAccess() � d�commenter en prenant soin d'ajouter le droit correspondant:
// INSERT INTO droits VALUES('/mod_notanet/index.php','V','V','F','F','F','F','F','F','Acc�s � l accueil Notanet','');
// Pour d�commenter le passage, il suffit de supprimer le 'slash-etoile' ci-dessus et l'�toile-slash' ci-dessous.
if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}
//======================================================================================

//==============================================
/* Ajout des droits pour fiches_brevet.php dans la table droits */
$sql="SELECT 1=1 FROM droits WHERE id='/mod_notanet/OOo/fiches_brevet.php';";
$test=mysql_query($sql);
if(mysql_num_rows($test)==0) {
$sql="INSERT INTO droits SET id='/mod_notanet/OOo/fiches_brevet.php',
administrateur='V',
professeur='F',
cpe='F',
scolarite='F',
eleve='F',
responsable='F',
secours='F',
autre='F',
description='Fiches brevet OpenOffice',
statut='';";
$insert=mysql_query($sql);
}

/* Ajout des droits pour imprime_ooo.php dans la table droits */
$sql="SELECT 1=1 FROM droits WHERE id='/mod_notanet/OOo/imprime_ooo.php';";
$test=mysql_query($sql);
if(mysql_num_rows($test)==0) {
$sql="INSERT INTO droits SET id='/mod_notanet/OOo/imprime_ooo.php',
administrateur='V',
professeur='F',
cpe='F',
scolarite='F',
eleve='F',
responsable='F',
secours='F',
autre='F',
description='Imprime fiches brevet OpenOffice',
statut='';";
$insert=mysql_query($sql);
}

//==============================================

$sql="SELECT 1=1 FROM droits WHERE id='/mod_notanet/fb_rouen_pdf.php';";
$test=mysql_query($sql);
if(mysql_num_rows($test)==0) {
$sql="INSERT INTO droits SET id='/mod_notanet/fb_rouen_pdf.php',
administrateur='V',
professeur='F',
cpe='F',
scolarite='F',
eleve='F',
responsable='F',
secours='F',
autre='F',
description='Fiches brevet PDF pour Rouen',
statut='';";
$insert=mysql_query($sql);
}

$sql="SELECT 1=1 FROM droits WHERE id='/mod_notanet/fb_montpellier_pdf.php';";
$test=mysql_query($sql);
if(mysql_num_rows($test)==0) {
$sql="INSERT INTO droits SET id='/mod_notanet/fb_montpellier_pdf.php',
administrateur='V',
professeur='F',
cpe='F',
scolarite='F',
eleve='F',
responsable='F',
secours='F',
autre='F',
description='Fiches brevet PDF pour Montpellier',
statut='';";
$insert=mysql_query($sql);
}

$sql="SELECT 1=1 FROM droits WHERE id='/mod_notanet/fb_creteil_pdf.php';";
$test=mysql_query($sql);
if(mysql_num_rows($test)==0) {
$sql="INSERT INTO droits SET id='/mod_notanet/fb_creteil_pdf.php',
administrateur='V',
professeur='F',
cpe='F',
scolarite='F',
eleve='F',
responsable='F',
secours='F',
autre='F',
description='Fiches brevet PDF pour Creteil',
statut='';";
$insert=mysql_query($sql);
}

$sql="SELECT 1=1 FROM droits WHERE id='/mod_notanet/fb_lille_pdf.php';";
$test=mysql_query($sql);
if(mysql_num_rows($test)==0) {
$sql="INSERT INTO droits SET id='/mod_notanet/fb_lille_pdf.php',
administrateur='V',
professeur='F',
cpe='F',
scolarite='F',
eleve='F',
responsable='F',
secours='F',
autre='F',
description='Fiches brevet PDF pour Lille',
statut='';";
$insert=mysql_query($sql);
}


$sql="SELECT 1=1 FROM droits WHERE id='/mod_notanet/saisie_param.php';";
$test=mysql_query($sql);
if(mysql_num_rows($test)==0) {
$sql="INSERT INTO droits SET id='/mod_notanet/saisie_param.php',
administrateur='V',
professeur='F',
cpe='F',
scolarite='F',
eleve='F',
responsable='F',
secours='F',
autre='F',
description='Fiches brevet: Saisie des param�tres',
statut='';";
$insert=mysql_query($sql);
}

/* Ajout des droits pour saisie_socle_commun.php dans la table droits */
$sql="SELECT 1=1 FROM droits WHERE id='/mod_notanet/saisie_socle_commun.php';";
$test=mysql_query($sql);
if(mysql_num_rows($test)==0) {
$sql="INSERT INTO droits SET id='/mod_notanet/saisie_socle_commun.php',
administrateur='V',
professeur='F',
cpe='F',
scolarite='F',
eleve='F',
responsable='F',
secours='F',
autre='F',
description='Notanet: Saisie socle commun',
statut='';";
$insert=mysql_query($sql);
}



if(!isset($msg)) {$msg="";}
//===========================================================
// Modification du type des champs id_mat pour pouvoir d�passer 127
$query=mysql_query("ALTER TABLE notanet CHANGE id_mat id_mat INT( 4 ) NOT NULL;");
if(!$query) {
	$msg.="Erreur lors de la modification du type du champ 'id_mat' de la table 'notanet'.<br />Cela risque de poser probl�me si vous devez saisir des notes de Langue Vivante R�gionale.<br />";
}

$query = mysql_query("ALTER TABLE notanet_corresp CHANGE id_mat id_mat INT( 4 ) NOT NULL;");
if(!$query) {
	$msg.="Erreur lors de la modification du type du champ 'id_mat' de la table 'notanet_corresp'.<br />Cela risque de poser probl�me si vous devez saisir des notes de Langue Vivante R�gionale.<br />";
}

$query = mysql_query("ALTER TABLE notanet_app CHANGE id_mat id_mat INT( 4 ) NOT NULL;");
if(!$query) {
	$msg.="Erreur lors de la modification du type du champ 'id_mat' de la table 'notanet_app'.<br />Cela risque de poser probl�me si vous devez saisir des notes de Langue Vivante R�gionale.<br />";
}
//===========================================================



//**************** EN-TETE *****************
$titre_page = "Notanet: Accueil";
//echo "<div class='noprint'>\n";
require_once("../lib/header.inc");
//echo "</div>\n";
//**************** FIN EN-TETE *****************

// Biblioth�que pour Notanet et Fiches brevet
//include("lib_brevets.php");

echo "<div class='noprint'>\n";
echo "<p class='bold'><a href='../accueil.php'>Accueil</a>";
echo "</p>\n";
echo "</div>\n";


$sql="CREATE TABLE IF NOT EXISTS notanet (
  login varchar(50) NOT NULL default '',
  ine text NOT NULL,
  id_mat tinyint(4) NOT NULL,
  notanet_mat varchar(255) NOT NULL,
  matiere varchar(50) NOT NULL,
  note varchar(4) NOT NULL default '',
  note_notanet varchar(4) NOT NULL,
  id_classe smallint(6) NOT NULL default '0'
);";
$create_table=mysql_query($sql);

$sql="CREATE TABLE IF NOT EXISTS notanet_app (
  login varchar(50) NOT NULL,
  id_mat tinyint(4) NOT NULL,
  matiere varchar(50) NOT NULL,
  appreciation text NOT NULL,
  id int(11) NOT NULL auto_increment,
  PRIMARY KEY  (id)
);";
$create_table=mysql_query($sql);

$sql="CREATE TABLE IF NOT EXISTS notanet_corresp (
  id int(11) NOT NULL auto_increment,
  type_brevet tinyint(4) NOT NULL,
  id_mat tinyint(4) NOT NULL,
  notanet_mat varchar(255) NOT NULL default '',
  matiere varchar(50) NOT NULL default '',
  statut enum('imposee','optionnelle','non dispensee dans l etablissement') NOT NULL default 'imposee',
  PRIMARY KEY  (id)
);";
$create_table=mysql_query($sql);

$sql="CREATE TABLE IF NOT EXISTS notanet_ele_type (
  login varchar(50) NOT NULL,
  type_brevet tinyint(4) NOT NULL,
  PRIMARY KEY  (login)
);";
$create_table=mysql_query($sql);

$sql="CREATE TABLE IF NOT EXISTS notanet_verrou (
id_classe TINYINT NOT NULL ,
type_brevet TINYINT NOT NULL ,
verrouillage CHAR( 1 ) NOT NULL
);";
$create_table=mysql_query($sql);

$sql="CREATE TABLE IF NOT EXISTS notanet_socles (
login VARCHAR( 50 ) NOT NULL ,
b2i ENUM( 'MS', 'ME', 'MN', 'AB', '' ) NOT NULL ,
a2 ENUM( 'MS', 'ME', 'MN', 'AB', '' ) NOT NULL ,
lv VARCHAR( 50 ) NOT NULL ,
PRIMARY KEY ( login )
);";
$create_table=mysql_query($sql);

$sql="CREATE TABLE IF NOT EXISTS notanet_avis (
login VARCHAR( 50 ) NOT NULL ,
favorable ENUM( 'O', 'N', '' ) NOT NULL ,
avis TEXT NOT NULL ,
PRIMARY KEY ( login )
);";
$create_table=mysql_query($sql);

$sql="CREATE TABLE IF NOT EXISTS notanet_lvr (
id int(11) NOT NULL auto_increment,
intitule VARCHAR( 255 ) NOT NULL ,
PRIMARY KEY ( id )
);";
$create_table=mysql_query($sql);

$sql="CREATE TABLE IF NOT EXISTS notanet_lvr_ele (
id int(11) NOT NULL auto_increment,
login VARCHAR( 255 ) NOT NULL ,
id_lvr INT( 11 ) NOT NULL ,
note ENUM ('', 'VA','NV') NOT NULL DEFAULT '',
PRIMARY KEY ( id )
);";
$create_table=mysql_query($sql);

$sql="CREATE TABLE IF NOT EXISTS notanet_socle_commun (
id INT(11) NOT NULL auto_increment,
login VARCHAR( 50 ) NOT NULL ,
champ VARCHAR( 10 ) NOT NULL ,
valeur ENUM( 'MS', 'ME', 'MN', 'AB', '' ) NOT NULL ,
PRIMARY KEY ( id )
);";
$create_table=mysql_query($sql);


if($_SESSION['statut']=="administrateur") {
	$truncate_tables=isset($_GET['truncate_tables']) ? $_GET['truncate_tables'] : NULL;
	if($truncate_tables=='y') {
		check_token();
		echo "<p>Nettoyage des tables Notanet</p>\n";
		$sql="TRUNCATE TABLE notanet;";
		$del=mysql_query($sql);
		$sql="TRUNCATE TABLE notanet_avis;";
		$del=mysql_query($sql);
		$sql="TRUNCATE TABLE notanet_app;";
		$del=mysql_query($sql);
		$sql="TRUNCATE TABLE notanet_verrou;";
		$del=mysql_query($sql);
		$sql="TRUNCATE TABLE notanet_socles;";
		$del=mysql_query($sql);
		$sql="TRUNCATE TABLE notanet_ele_type;";
		$del=mysql_query($sql);
	}
}

echo "<p>Voulez-vous: ";
//echo "<br />\n";
echo "</p>\n";
//echo "<ul>\n";
if($_SESSION['statut']=="administrateur") {
	echo "<ol>\n";
	echo "<li><a href='saisie_param.php'>Saisir les param�tres Acad�mie, Session,...</a>.</li>\n";
	echo "<li><a href='select_eleves.php'>Effectuer les associations El�ves/Type de brevet</a></li>\n";

	echo "<li><a href='select_matieres.php'>Effectuer les associations Type de brevet/Mati�res</a>  (<i>en pr�cisant le statut: impos�es et options</i>)</li>\n";

	//echo "<li><a href='saisie_b2i_a2.php'>Saisir les 'notes' B2i et niveau A2 de langue</a> (<i>n�cessaire pour r�aliser ensuite l'extraction des moyennes</i>)</li>\n";

	echo "<li><a href='saisie_lvr.php'>Saisir les 'notes' de Langue Vivante R�gionale</a> (<i>si un tel enseignement est �valu� dans l'�tablissement</i>)</li>\n";

	echo "<li><a href='saisie_socle_commun.php'>Saisir ou importer les r�sultats du Socle commun.</li>\n";

	echo "<li><a href='extract_moy.php'>Effectuer une extraction des moyennes, affichage et traitement des cas particuliers</a></li>\n";

	echo "<li><a href='corrige_extract_moy.php'>Corriger l'extraction des moyennes</a></li>\n";

	echo "<li><a href='choix_generation_csv.php?extract_mode=tous'>G�n�rer un export Notanet</a> pour tous les �l�ves de telle(s) ou telle(s) classe(s) ou juste une s�lection (cf. select_eleves.php)</li>\n";

	echo "<li><a href='verrouillage_saisie_app.php'>Verrouiller/d�verrouiller la saisie des appr�ciations pour les fiches brevet</a><br />La saisie n'est possible pour les professeurs que si l'extraction des moyennes a �t� effectu�e.</li>\n";

	echo "<li><a href='saisie_avis.php'>Saisir l'avis du chef d'�tablissement</a>.</li>\n";

	echo "<li><p>G�n�rer les fiches brevet selon le mod�le de:</p>
	<ul>\n";
	/*
	echo "		<li><a href='poitiers/fiches_brevet.php'>Poitiers</a></li>
		<li><a href='rouen/fiches_brevet.php'>Rouen (<i>version HTML</i>)</a> - <a href='fb_rouen_pdf.php'>version PDF</a></li>
		<li><a href='fb_montpellier_pdf.php'>Montpellier (<i>version PDF</i>)</a></li>
		<li><a href='fb_creteil_pdf.php'>Creteil (<i>version PDF</i>)</a></li>
		<li><a href='fb_lille_pdf.php'>Lille (<i>version PDF</i>)</a></li>\n";

	$gepi_version=getSettingValue('version');
	if(($gepi_version!='1.5.1')&&($gepi_version!='1.5.0')) {  
	*/
		echo "		<li><a href='OOo/imprime_ooo.php'>Mod�le au format OpenOffice</a> <a href='https://www.sylogix.org/projects/gepi/wiki/GepiDoc_fbOooCalc'><img src='../images/icons/ico_question.png' alt='aide construction gabarit' title='Aide pour utiliser les gabarits .ods pour �diter les fiches brevets' title='Aide pour utiliser les gabarits .ods pour �diter les fiches brevets' /></a></li>\n";
	//}
	echo "	</ul>
</li>\n";
	//echo "<li><a href='#'>Vider les tables notanet</a></li>\n";
	//echo "<li><a href=''></a></li>\n";
	echo "</ol>\n";

	echo "<p>Au changement d'ann�e: <a href='".$_SERVER['PHP_SELF']."?truncate_tables=y".add_token_in_url()."'>Vider les saisies Notanet ant�rieures</a>.</p>\n";

	echo "<p><b>NOTES:</b> Pour un bon fonctionnement du dispositif, il faut parcourir les points ci-dessus dans l'ordre.</p>\n";
}
elseif($_SESSION['statut']=="scolarite") {
	echo "<ul>\n";
	//echo "<li><a href='saisie_b2i_a2.php'>Saisir les 'notes' B2i et niveau A2 de langue</a> (<i>n�cessaire pour r�aliser ensuite l'extraction des moyennes</i>)</li>\n";

	echo "<li><a href='saisie_lvr.php'>Saisir les 'notes' de Langue Vivante R�gionale</a> (<i>si un tel enseignement est �valu� dans l'�tablissement</i>)</li>\n";

	echo "<li><a href='saisie_avis.php'>Saisir l'avis du chef d'�tablissement</a>.</li>\n";
	echo "</ul>\n";

	echo "<p><b>NOTES:</b> Pour un bon fonctionnement du dispositif, plusieurs op�rations doivent auparavant �tre r�alis�es en statut administrateur.</p>\n";
}
else {
	echo "<ul>\n";
	echo "<li><a href='saisie_app.php'>Saisir les appr�ciations pour les fiches brevet</a></li>\n";
	echo "</ul>\n";
}

//<a href="notes_structure_pdf.php">Test PDF</a>

?>
<?php
require("../lib/footer.inc.php");
?>
