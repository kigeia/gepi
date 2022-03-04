<?php
/*
 * Copyright 2001, 2011 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, Christian Chapel
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

//INSERT INTO droits VALUES ('/impression/password_pdf.php', 'V', 'F', 'F', 'F', 'F', 'F', 'F','Impression des des mots de passe. Module PDF', '');

//=============================
// Initialisations files
require_once("../lib/initialisations.inc.php");
//=============================

$date=date("Ymd_Hi");
$nom_releve = "export_csv_password_".$date.".pdf";

send_file_download_headers('application/pdf',$nom_releve);

require_once('../fpdf/fpdf.php');


define('LargeurPage','210');
define('HauteurPage','297');

require_once("./class_pdf.php");
require_once ("./liste.inc.php"); //fonction qui retourne le nombre d'élèves par classe (ou groupe) pour une période donnée.

// Lorsque qu'on utilise une session PHP, parfois, IE n'affiche pas le PDF
// C'est un problème qui affecte certaines versions d'IE.
// Pour le contourner, on ajoutez la ligne suivante avant session_start() :
session_cache_limiter('private');

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

// LES OPTIONS DEBUT
if (!isset($_SESSION['marge_haut'])) { $MargeHaut = 8; } else {$MargeHaut =  $_SESSION['marge_haut'];}
if (!isset($_SESSION['marge_droite'])) { $MargeDroite = 10 ; } else {$MargeDroite =  $_SESSION['marge_droite'];}
if (!isset($_SESSION['marge_gauche'])) { $MargeGauche = 10 ; } else {$MargeGauche =  $_SESSION['marge_gauche'];}
if (!isset($_SESSION['marge_bas'])) { $MargeBas = 8 ; } else {$MargeBas =  $_SESSION['marge_bas'];}
if (!isset($_SESSION['marge_reliure'])) { $avec_reliure = 0 ; } else {$avec_reliure =  $_SESSION['marge_reliure'];}
if (!isset($_SESSION['avec_emplacement_trous'])) { $avec_emplacement_trous = 0 ; } else {$avec_emplacement_trous =  $_SESSION['avec_emplacement_trous'];}

//Gestion de la marge à gauche pour une reliure éventuelle ou des feuilles perforées.
if ($avec_reliure==1) {
  if ($MargeGauche < 18) {$MargeGauche = 18;}
}

//Calcul de la Zone disponible
$EspaceX = LargeurPage - $MargeDroite - $MargeGauche ;
$EspaceY = HauteurPage - $MargeHaut - $MargeBas;
$X_tableau = $MargeGauche;


// Définition de la page
$pdf=new rel_PDF("P","mm","A4");
$pdf->SetTopMargin($MargeHaut);
$pdf->SetRightMargin($MargeDroite);
$pdf->SetLeftMargin($MargeGauche);
$pdf->SetAutoPageBreak(true, $MargeBas);

//On récupère la session
if (!isset($_SESSION['donnees_export_csv_password'])) { $MargeHaut = false ; } else {$donnees_personne_csv =  $_SESSION['donnees_export_csv_password'];}
$nb_enr_tableau = sizeof ($donnees_personne_csv['login']);

$texte_presentation = 'Attention : Votre mot de passe est confidentiel. A votre première connexion, vous devrez changer votre mot de passe.';

//recherche du dossier racine de GEPI pour obtenir l'adresse de l'application à saisir dans le navigateur
$url = parse_url($_SERVER['REQUEST_URI']);
$temp = $url['path'];
$d = mb_strlen($temp) - mb_strlen("impression/password_pdf.php") ;
$gepi_path = mb_substr($temp, 0, $d);

if (!isset($_SERVER['HTTPS']) OR (isset($_SERVER['HTTPS']) AND strtolower($_SERVER['HTTPS']) != "on")) {
   $adresse_site_gepi = "HTTP://".$_SERVER["SERVER_NAME"] . $gepi_path;         
} else {
   $adresse_site_gepi = "HTTPS://".$_SERVER["SERVER_NAME"] . $gepi_path;         
}

$pdf->AddPage("P");
// Couleur des traits
$pdf->SetDrawColor(0,0,0);
// caractère utilisé dans le document
$caractere_utilise = 'DejaVu';
$y_tmp = $MargeHaut;
$j=0;
if (($donnees_personne_csv)) {
	// Cette boucle crée les différentes pages du PDF
	for ($i=0; $i<$nb_enr_tableau ; $i++) {
		
        $classe = $donnees_personne_csv['classe'][$i];
		$login = $donnees_personne_csv['login'][$i];
		$nom = $donnees_personne_csv['nom'][$i];
		$prenom = $donnees_personne_csv['prenom'][$i];
		$password = $donnees_personne_csv['new_password'][$i];
		$email = $donnees_personne_csv['user_email'][$i];
		
		$pdf->SetLineWidth(0.2);
		$pdf->SetFont('DejaVu','',9);
		$pdf->SetDash(4,4);


		$pdf->Setxy($X_tableau,$y_tmp);
		$pdf->SetFont('DejaVu','B',8);
		$texte = "\nA l'attention de ".$prenom." ".$nom." , classe de ".$classe.
				 " :                         Voici vos identifiant et mot de passe pour accéder à vos notes.\nIdentifiant : ".$login.
				 "\nMot de passe : ".$password.
				// "\nEmail : ".$email.
				 "\nAdresse du site Gepi à saisir dans votre navigateur Internet : ".$adresse_site_gepi."\n".$texte_presentation."\n\n";
		//$pdf->MultiCell($EspaceX,3.5,$texte,'B',2,'L',0);
		$pdf->MultiCell($EspaceX,3.5,$texte,'B','L',0);
				
		$y_tmp = $pdf->GetY();
		
		if ($j==10) { // saut de page  après 8 fiches sur la page.
		  $pdf->AddPage("P");
		  $y_tmp = $MargeHaut;
		  $j=0;
		}
        $j++;
		
		//génération d'un saut de page PDF pour un changement de classe
		$classe_elv = $classe;
		if ($i+1<$nb_enr_tableau) { //pour éviter le débordement sur le dernier elv
		   $classe_elv_suivant = $donnees_personne_csv['classe'][$i+1];
		} else {
		  $classe_elv_suivant = $classe;
		}
		
		if ( $classe_elv != $classe_elv_suivant) {
		  $pdf->AddPage("P");
		  $y_tmp = $MargeHaut;
		  $j=0;
		}
		
		} // FOR
} else {  //variable de session OK 		
// problème de variable de session
  $pdf->CellFitScale($l_cell_avis,$h_cell,"Erreur de session export PDF",1,0,'L',0); //le quadrillage
}

$pref_output_mode_pdf=get_output_mode_pdf();

// sortie PDF sur écran
$pdf->Output($nom_releve,$pref_output_mode_pdf);
?>
