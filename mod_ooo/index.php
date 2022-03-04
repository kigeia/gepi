<?php
/*
 * Copyright 2001, 2013 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun
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

//**************** EN-TETE *****************
$titre_page = "Modèle Open Office";
require_once("../lib/header.inc.php");
//**************** FIN EN-TETE *****************

//debug_var();

echo "<p class='bold'><a href='../accueil.php'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>";
echo " | <a href='publipostage_ooo.php'>Publipostage OOo</a>";
echo "</p>\n";

echo "<p>Ce module est destiné à gérer les modèles Open Office de Gepi.</p>\n";

$phrase_commentaire="";
$_SESSION['retour']=$_SERVER['PHP_SELF'] ;


//Début de la table configuration
if(($_SESSION['statut']=='administrateur')||
(($_SESSION['statut']=='cpe')&&((getSettingAOui('OOoUploadCpeAbs2'))||(getSettingAOui('OOoUploadCpeDiscipline'))||(getSettingAOui('OOoUploadCpeNotanet'))))||
(($_SESSION['statut']=='scolarite')&&((getSettingAOui('OOoUploadScolAbs2'))||(getSettingAOui('OOoUploadScolDiscipline'))||(getSettingAOui('OOoUploadScolNotanet'))))) {
  echo "<table class='menu' summary='Modele Open Office'>\n";
  echo "<tr>\n";
  echo "<th colspan='2'><img src='../images/icons/control-center.png' alt='Configuration du module Modèle Open Office' class='link'/> - Configuration du module</th>\n";
  echo "</tr>\n";
  echo "<tr>\n";
  echo "<td width='30%'><a href='../mod_ooo/gerer_modeles_ooo.php'>Gérer les modèles de document OOo de l'établissement</a>";
  echo "</td>\n";
  echo "<td>Gérer ses propres modèles de document Open Office</td>\n";
  echo "</tr>\n"; 
  echo "</table>\n";
}
//fin de la table configuration

// Table Formulaires
echo "<table class='menu' summary='Modele Open Office'>\n";
echo "<tr>\n";
echo "<th colspan='2'><img src='../images/icons/saisie.png' alt='Formulaires Open Office' class='link'/> - Liste des formulaires</th>\n";
echo "</tr>\n";
echo "<tr>\n";
echo "<td width='30%'><a href='../mod_ooo/formulaire_retenue.php'>Retenue</a>";
echo "</td>\n";
echo "<td>Saisir le formulaire de retenue pour l'imprimer</td>\n";
echo "</tr>\n";
echo "</table>\n";

echo $phrase_commentaire;

echo "<p><br /></p>\n";


echo "<p><br /></p>\n";

require("../lib/footer.inc.php");
?>
