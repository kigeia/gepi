<?php
/*
 *
 *
 * Copyright 2001, 2012 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, Julien Jocal
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

$niveau_arbo = 1;

// Initialisations files
require_once("../lib/initialisations.inc.php");

// fonctions complémentaires et/ou librairies utiles

// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == "c") {
   header("Location:utilisateurs/mon_compte.php?change_mdp=yes&retour=accueil#changemdp");
   die();
} else if ($resultat_session == "0") {
    header("Location: ../logout.php?auto=1");
    die();
}

$sql="SELECT 1=1 FROM droits WHERE id='/statistiques/index.php';";
$test=mysql_query($sql);
if(mysql_num_rows($test)==0) {
$sql="INSERT INTO droits SET id='/statistiques/index.php',
administrateur='V',
professeur='V',
cpe='V',
scolarite='V',
eleve='F',
responsable='F',
secours='F',
autre='F',
description='Statistiques',
statut='';";
$insert=mysql_query($sql);
}

if (!checkAccess()) {
    header("Location: ../logout.php?auto=2");
    die();
}

// ===================== entete Gepi ======================================//
$titre_page = "Statistiques: Index";
require_once("../lib/header.inc.php");
// ===================== fin entete =======================================//

//debug_var();

echo "<p class='bold'><a href='../accueil.php'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>";
echo "</p>\n";

echo "<ul>\n";
echo "<li><a href='classes_effectifs.php'>Classes, effectifs,...</a></li>\n";
if($_SESSION['statut']=='administrateur') {
	if(getSettingAOui('active_bulletins')) {
		echo "<li><a href='export_donnees_bulletins.php'>Export de données des bulletins</a></li>\n";
	}
	echo "<li><a href='stat_connexions.php'>Statistiques de connexion</a></li>\n";
}
elseif($_SESSION['statut']=='scolarite') {
	if(getSettingAOui('active_bulletins')) {
		echo "<li><a href='export_donnees_bulletins.php'>Export de données des bulletins</a></li>\n";
	}
	if((getSettingAOui('AccesStatConnexionEleScolarite'))||
	(getSettingAOui('AccesDetailConnexionEleScolarite'))||
	(getSettingAOui('AccesStatConnexionRespScolarite'))||
	(getSettingAOui('AccesDetailConnexionRespScolarite'))) {
		echo "<li><a href='stat_connexions.php'>Statistiques de connexion</a></li>\n";
	}
}
elseif($_SESSION['statut']=='cpe') {
	/*
	if(getSettingAOui('active_bulletins')) {
		echo "<li><a href='export_donnees_bulletins.php'>Export de données des bulletins</a></li>\n";
	}
	*/
	if((getSettingAOui('AccesStatConnexionEleCpe'))||
	(getSettingAOui('AccesDetailConnexionEleCpe'))||
	(getSettingAOui('AccesStatConnexionRespCpe'))||
	(getSettingAOui('AccesDetailConnexionRespCpe'))) {
		echo "<li><a href='stat_connexions.php'>Statistiques de connexion</a></li>\n";
	}
}
elseif($_SESSION['statut']=='professeur') {
	$acces="n";

	if((getSettingAOui('AccesStatConnexionEleProfesseur'))||
	(getSettingAOui('AccesDetailConnexionEleProfesseur'))||
	(getSettingAOui('AccesStatConnexionRespProfesseur'))||
	(getSettingAOui('AccesDetailConnexionRespProfesseur'))) {
		$acces="y";
	}

	if($acces=="n") {
		if(is_pp($_SESSION['login'])) {
			if((getSettingAOui('AccesStatConnexionEleProfP'))||
			(getSettingAOui('AccesDetailConnexionEleProfP'))||
			(getSettingAOui('AccesStatConnexionRespProfP'))||
			(getSettingAOui('AccesDetailConnexionRespProfP'))) {
				$acces="y";
			}
		}
	}

	if($acces=="y") {
		echo "<li><a href='stat_connexions.php'>Statistiques de connexion</a></li>\n";
	}
}

if(getSettingAOui('active_mod_discipline')) {
	if(acces("/mod_discipline/stats2/index.php", $_SESSION['statut'])) {
		echo "<li>Discipline&nbsp;:<br />
	<ul>
		<li><a href='../mod_discipline/stats2/index.php'>Statistiques</a></li>
		<li><a href='../mod_discipline/disc_stat.php'>Statistiques (<em>plus rudimentaires</em>)</a></li>
	</ul>
</li>\n";
	}
}
echo "</ul>\n";


require_once("../lib/footer.inc.php");
?>
