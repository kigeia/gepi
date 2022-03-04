<?php
/*
 *
 * Copyright 2001, 2012 Thomas Belliard, Laurent Delineau, Edouard Hue, Eric Lebrun, Christian Chapel
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
 
// Global configuration file
// Quand on est en SSL, IE n'arrive pas à ouvrir le PDF.
//Le problème peut être résolu en ajoutant la ligne suivante :

//=============================
// Initialisations files
require_once("../lib/initialisations.inc.php");
//=============================
//debug_var();
if (!defined('FPDF_VERSION')) {
	require_once('../fpdf/fpdf.php');
}


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
//if (!isset($_SESSION['avis_pdf_marge_haut'])) { $MargeHaut = 10 ; } else {$MargeHaut =  $_SESSION['avis_pdf_marge_haut'];}
$MargeHaut=getPref($_SESSION['login'],'avis_pdf_marge_gauche',10);

//if (!isset($_SESSION['avis_pdf_marge_droite'])) { $MargeDroite = 10 ; } else {$MargeDroite =  $_SESSION['avis_pdf_marge_droite'];}
$MargeDroite=getPref($_SESSION['login'],'avis_pdf_marge_droite',10);

//if (!isset($_SESSION['avis_pdf_marge_gauche'])) { $MargeGauche = 10 ; } else {$MargeGauche =  $_SESSION['avis_pdf_marge_gauche'];}
$MargeGauche=getPref($_SESSION['login'],'avis_pdf_marge_gauche',10);

//if (!isset($_SESSION['avis_pdf_marge_bas'])) { $MargeBas = 10 ; } else {$MargeBas =  $_SESSION['avis_pdf_marge_bas'];}
$MargeBas=getPref($_SESSION['login'],'avis_pdf_marge_bas',10);

//if (!isset($_SESSION['avis_pdf_marge_reliure'])) { $avec_reliure = 1 ; } else {$avec_reliure =  $_SESSION['avis_pdf_marge_reliure'];}
$avec_reliure=getPref($_SESSION['login'],'avis_pdf_marge_reliure',1);

//if (!isset($_SESSION['avis_pdf_avec_emplacement_trous'])) { $avec_emplacement_trous = 1 ; } else {$avec_emplacement_trous =  $_SESSION['avis_pdf_avec_emplacement_trous'];}
$avec_emplacement_trous=getPref($_SESSION['login'],'avis_pdf_avec_emplacement_trous',1);

//Gestion de la marge à gauche pour une reliure éventuelle ou des feuilles perforées.
if ($avec_reliure==1) {
  if ($MargeGauche < 18) {$MargeGauche = 18;}
}

//Calcul de la Zone disponible
$EspaceX = LargeurPage - $MargeDroite - $MargeGauche ;
$EspaceY = HauteurPage - $MargeHaut - $MargeBas;
$X_tableau = $MargeGauche;

//entête classe et année scolaire
$L_entete_classe = 65;
$H_entete_classe = 14;
$X_entete_classe = $EspaceX - $L_entete_classe + $MargeGauche;
$Y_entete_classe = $MargeHaut;

$X_entete_matiere = $MargeGauche;
$Y_entete_matiere = $MargeHaut;
$L_entete_discipline = 65;
$H_entete_discipline = 14;

// LES OPTIONS suite
//if (!isset($_SESSION['avis_pdf_h_ligne'])) { $h_cell = 8 ; } else {$h_cell =  $_SESSION['avis_pdf_h_ligne'];} //hauteur d'une ligne du tableau
$h_cell=getPref($_SESSION['login'],'avis_pdf_h_ligne',8);

//if (!isset($_SESSION['avis_pdf_l_nomprenom'])) { $l_cell_nom = 40 ; } else {$l_cell_nom =  $_SESSION['avis_pdf_l_nomprenom'];} // la largeur de la colonne nom - prénom
$l_cell_nom=getPref($_SESSION['login'],'avis_pdf_l_nomprenom',40);

//if (!isset($_SESSION['avis_pdf_affiche_pp'])) { $option_affiche_pp = 1 ; } else {$option_affiche_pp =  $_SESSION['avis_pdf_affiche_pp'];}// 0 On n'affiche pas le PP 1 on l'affiche
$option_affiche_pp=getPref($_SESSION['login'],'avis_pdf_affiche_pp',1);

//if (!isset($_SESSION['avis_pdf_une_seule_page'])) { $option_tout_une_page = 1 ; } else {$option_tout_une_page =  $_SESSION['avis_pdf_une_seule_page'];} // Faire tenir sur une seule page la classe 0 nom - 1 oui
$option_tout_une_page=getPref($_SESSION['login'],'avis_pdf_une_seule_page',1);

$ligne_texte = "Avis du conseil de classe." ;
$texte = '';

// Définition de la page

$pdf=new rel_PDF("P","mm","A4");
$pdf->SetTopMargin($MargeHaut);
$pdf->SetRightMargin($MargeDroite);
$pdf->SetLeftMargin($MargeGauche);
$pdf->SetAutoPageBreak(true, $MargeBas);



//On recupère les variables pour l'affichage et on traite leur existance.
// DE   IMPRIME.PHP
$id_classe=isset($_GET['id_classe']) ? $_GET["id_classe"] : NULL;
$id_groupe=isset($_GET['id_groupe']) ? $_GET["id_groupe"] : NULL;
$id_periode=isset($_GET['periode_num']) ? $_GET["periode_num"] : NULL;

//On recupère les variables pour l'affichage
// DE  IMPRIME_SERIE.PHP
// les tableaux contienent la liste des id.
$id_liste_classes=isset($_POST['id_liste_classes']) ? $_POST["id_liste_classes"] : NULL;
$id_liste_groupes=isset($_POST['id_liste_groupes']) ? $_POST["id_liste_groupes"] : NULL;
//$id_liste_periodes=isset($_POST['id_liste_periodes']) ? $_POST["id_liste_periodes"] : NULL;


//if ($id_periode==NULL){$id_periode=isset($_POST['id_periode']) ? $_POST["id_periode"] : NULL;} 
if (!(is_numeric($id_periode))) {
	$id_periode=1;
	$nb_periodes=1;
}

$nb_pages = 0;
$nb_eleves = 0;

// DEFINIR LE NOMBRE DE BOUCLES A FAIRE
// Impressions RAPIDES
if ($id_groupe!=NULL) { // C'est un groupe
    $nb_pages=1;
}

if ($id_classe!=NULL) { // C'est une classe
    $nb_pages=1;
} //fin c'est une classe

//IMPRESSION A LA CHAINE
if ($id_liste_classes!=NULL) {
    $nb_pages = sizeof($id_liste_classes);
//echo $nb_pages;
}

//IMPRESSION A LA CHAINE
if ($id_liste_groupes!=NULL) {
    $nb_pages = sizeof($id_liste_groupes);
//echo $nb_pages;
}

//IMPRESSION A LA CHAINE
if (!isset($_GET['periode_num'])) {

	//On récupère dans la session
	if ($_SESSION['id_liste_periodes']!=NULL) {
		$id_liste_periodes=$_SESSION['id_liste_periodes'];
	//unset($_SESSION['id_liste_periodes']);
		$id_periode=$id_liste_periodes[0];
		//debug_var();
	}

	if ($id_liste_periodes!=NULL) {
		//print_r($id_liste_periodes);
	    $nb_periodes = sizeof($id_liste_periodes);
		//echo $nb_periodes;		
	} 
} else {
   $nb_periodes=1;
}

//echo " ".$nb_pages;
//$nb_pages=$nb_pages*$nb_periodes;
		
// Cette boucle crée les différentes pages du PDF (page = un entête et des lignes par élèves.
for ($i_pdf=0; $i_pdf<$nb_pages ; $i_pdf++) {

	$nb_eleves=0;
	// Impressions RAPIDES
	if ($id_groupe!=NULL) { // C'est un groupe
		$id_liste_periodes[0]=$id_periode;
		$donnees_eleves = traite_donnees_groupe($id_groupe,$id_liste_periodes,$nb_eleves);
		$id_classe=$donnees_eleves[0]['id_classe'];
	} elseif ($id_classe!=NULL) { // C'est une classe
		$id_liste_periodes[0]=$id_periode;
		$donnees_eleves = traite_donnees_classe($id_classe,$id_liste_periodes,$nb_eleves);
	} //fin c'est une classe
		
	//IMPRESSION A LA CHAINE
	if ($id_liste_groupes!=NULL) {	     
		$donnees_eleves = traite_donnees_groupe($id_liste_groupes[$i_pdf],$id_liste_periodes,$nb_eleves);
		$id_groupe=$id_liste_groupes[$i_pdf];
		//$id_classe=$donnees_eleves[0]['id_classe'];		
	}

	if ($id_liste_classes!=NULL) {
		$donnees_eleves = traite_donnees_classe($id_liste_classes[$i_pdf],$id_liste_periodes,$nb_eleves);
		$id_classe=$id_liste_classes[$i_pdf];
		//$id_classe=$donnees_eleves[0]['id_classe'];
	}


	//Info pour le debug.	
	$affiche='n';
	if ($affiche=='y') {
		echo "<pre>";
		print_r($donnees_eleves);
		echo "</pre>";
	}
	
	//Si plusieurs périodes, on trie les données par nom et période.
	if ($nb_periodes>1) {
		foreach($donnees_eleves as $sortarray)
		{
			//$column[] = $sortarray['id_classe'];
			//@array_multisort($column, SORT_ASC, $donnees_eleves);
			$column[] = $sortarray['nom'];
			$column1[] = $sortarray['prenom'];
			$column2[] = $sortarray['id_periode'];
			@array_multisort($column, SORT_ASC, $column1, SORT_ASC, $column2, SORT_ASC, $donnees_eleves);
		}
		unset($column);
		unset($column1);
		unset($column2);
		$option_tout_une_page = 0 ;
	}
	
	if ($affiche=='y') {
		echo "<pre>";
		print_r($donnees_eleves);
		echo "</pre>";
	}

	// CALCUL de VARIABLES
	// Calcul de la hauteur de la ligne dans le cas de l'option tout sur une ligne
	if ($option_tout_une_page == 1) {
		$hauteur_disponible = HauteurPage - $MargeHaut - $MargeBas - $H_entete_classe - 5 - 2.5; //2.5 ==> avant le pied de page

		$hauteur_disponible = $hauteur_disponible - 14.5;
		
		// le nombre de lignes demandées.
		$nb_ligne_demande = $nb_eleves;

		$h_cell = $hauteur_disponible / $nb_ligne_demande ;

		/*
		$f=fopen("/tmp/debug_avis_pdf.txt","w+");
		fwrite($f, "\$hauteur_disponible=$hauteur_disponible\n");
		fwrite($f, "\$nb_ligne_demande=$nb_ligne_demande\n");
		fwrite($f, "\$h_cell=$h_cell\n");
		fclose($f);
		*/
	}

	$pdf->AddPage("P");
	// Couleur des traits
	$pdf->SetDrawColor(0,0,0);

	// caractère utilisé dans le document
	$caractere_utilise = 'DejaVu';

	// on appelle une nouvelle page pdf
	$nb_eleves_i = 0;

	//Entête du PDF
	$pdf->SetLineWidth(0.7);
	$pdf->SetFont('DejaVu','B',14);
	$pdf->Setxy($X_entete_classe,$Y_entete_classe);

	if ($id_classe != NULL) {
		$calldata = mysql_query("SELECT * FROM classes WHERE id = '$id_classe'");
		$current_classe = mysql_result($calldata, 0, "classe");
	} else {
		$sql = "SELECT * FROM classes WHERE id = '$id_classe'";
		$calldata = mysql_query($sql);
		$current_classe = mysql_result($calldata, 0, "classe");
	}

	if (($option_affiche_pp==1)) {
		$pdf->CellFitScale($L_entete_classe,$H_entete_classe / 2,'Classe de '.$current_classe,'LTR',2,'C');
		$pdf->SetFont('DejaVu','I',8.5);

		//PP de la classe
		if ($id_groupe != NULL) {
			$id_classe=$donnees_eleves['id_classe'][0];
		}
		//$sql = "SELECT professeur FROM j_eleves_professeurs WHERE (login = '".$donnees_eleves['login'][0]."' and id_classe='$id_classe')";
		$sql = "SELECT professeur FROM j_eleves_professeurs WHERE (login = '".$donnees_eleves[0]['login']."' and id_classe='$id_classe')";
		//echo "$sql<br />\n";
		$call_profsuivi_eleve = mysql_query($sql);
		$current_eleve_profsuivi_login = @mysql_result($call_profsuivi_eleve, '0', 'professeur');

		$pdf->CellFitScale($L_entete_classe,$H_entete_classe / 2,casse_mot(getSettingValue("gepi_prof_suivi"),'majf2').' : '.affiche_utilisateur($current_eleve_profsuivi_login,$id_classe),'LRB',0,'L');//'Année scolaire '.getSettingValue('gepiYear')
	} else {

		if ($id_groupe != NULL) {
		//$current_classe = $donnees_eleves['id_classe'][0]; // on suppose qu'il n'y a dans un groupe que des personnes d'une même classe ... Bof Bof
		$current_classe = $donnees_eleves[0]['id_classe']; // on suppose qu'il n'y a dans un groupe que des personnes d'une même classe ... Bof Bof
		}
		$pdf->CellFitScale($L_entete_classe,$H_entete_classe,'Classe de '.$current_classe,'LTRB',2,'C');
	}

	$pdf->Setxy($X_entete_matiere,$Y_entete_matiere);
	$pdf->SetFont('DejaVu','B',14);


	//Si on peut connaître le nom de la matière (id_groupe existe !)
	if ($id_groupe != NULL) {
		$current_group = get_group($id_groupe);
		$matiere = $current_group["description"];
			//echo $matiere."<br/>";
		$pdf->CellFitScale($L_entete_discipline,$H_entete_discipline /2 ,$matiere,'LTR',2,'C');
		$pdf->SetFont('DejaVu','I',11);
		$pdf->Cell($L_entete_classe,$H_entete_classe / 2,'Année scolaire '.getSettingValue('gepiYear'),'LRB',2,'C');
	} else {
		// On demande une classe ==> on ajoute la période.
		$pdf->SetFont('DejaVu','I',11);

		if ($nb_periodes==1) {
			$sql="SELECT num_periode,nom_periode FROM periodes WHERE id_classe='$id_classe' AND num_periode='".$donnees_eleves[0]['id_periode']."' ORDER BY num_periode";
			$res_per=mysql_query($sql);
			if(mysql_num_rows($res_per)==0){
				die("Problème avec les infos de la classe $id_classe</body></html>");
			}
			else{
				$lig_tmp=mysql_fetch_object($res_per);
				$periode=$lig_tmp->nom_periode;			
				$pdf->Cell($L_entete_classe,$H_entete_classe / 2,'Année scolaire '.getSettingValue('gepiYear'),'TLR',2,'C');
				$pdf->CellFitScale($L_entete_discipline,$H_entete_classe / 2 ,$periode,'LBR',2,'C');
			}
		} else {
			$pdf->Cell($L_entete_classe,$H_entete_classe ,'Année scolaire '.getSettingValue('gepiYear'),'LTRB',2,'C');
		}
	}

	$Y_courant=$pdf->GetY()+2.5;
	$pdf->Setxy($MargeGauche,$Y_courant);

	//La ligne de texte après les entêtes
	$pdf->CellFitScale(0,10,$ligne_texte,'',2,'C');
	$Y_courant=$pdf->GetY()+2.5;

	// requete à faire pour récupérer les Avis pour la classe / la période !!!
	//debut tableau;
	$pdf->SetLineWidth(0.3);
	$pdf->SetFont('DejaVu','',9);
	$y_tmp = $Y_courant;
	$y_tmp_ini = $y_tmp;

	// Le tableau

	// Haut du tableau pour la premiere page de la classe (tenant compte de l'entete)
	$y_top_tableau=$y_tmp;

	// Largeur de la colonne Avis du conseil:
	$l_cell_avis=$EspaceX - $l_cell_nom;

	// Boucle sur les eleves de la classe courante:
	$compteur_eleves_page=0;
	while($nb_eleves_i < $nb_eleves) {	
		$login_elv = $donnees_eleves[$nb_eleves_i]['login'];
		$sql_current_eleve_avis = "SELECT avis FROM avis_conseil_classe WHERE (login='$login_elv' AND periode='".$donnees_eleves[$nb_eleves_i]['id_periode']."')";
		//echo "$sql_current_eleve_avis<br />\n";
		$current_eleve_avis_query = mysql_query($sql_current_eleve_avis);
		$current_eleve_avis = @mysql_result($current_eleve_avis_query, 0, "avis");

		//if(strtr($y_tmp,",",".")+strtr($h_cell,",",".")>297-$MargeBas-5) {
		//if(strtr($y_tmp,",",".")+strtr($h_cell,",",".")>297-$MargeBas-$h_cell-5) {
		//if(strtr($y_tmp,",",".")+strtr($h_cell,",",".")>297-$MargeBas-$MargeHaut-5) {
		if(strtr($y_tmp,",",".")+strtr($h_cell,",",".")>297-$MargeBas-$MargeHaut-2) {
			/*
			$f=fopen("/tmp/debug_avis_pdf.txt","a+");
			fwrite($f, "\$y_tmp+\$h_cell=$y_tmp+$h_cell=".(strtr($y_tmp,",",".")+strtr($h_cell,",","."))."\n");
			fwrite($f, "297-\$MargeBas-\$MargeHaut-5=".(297-$MargeBas-$MargeHaut-5)."\n");
			fclose($f);
			*/

			// Haut du tableau pour la deuxieme, troisieme,... page de la classe
			// Pour la deuxieme, troisieme,... page d'une classe, on n'a pas d'entete:
			$y_top_tableau=$MargeHaut;

			$pdf->AddPage("P");
			$pdf->Setxy($X_tableau,$y_top_tableau);
			$compteur_eleves_page=0;
		}

		// Ordonnee courante pour l'eleve n°$compteur_eleves_page de la page:
		$y_tmp = $y_top_tableau+$compteur_eleves_page*$h_cell;

		// Colonne Nom_Prenom
		$pdf->SetXY($X_tableau,$y_tmp);
		$pdf->SetFont('DejaVu','B',9);		
		$texte = my_strtoupper($donnees_eleves[$nb_eleves_i]['nom'])." ".casse_mot($donnees_eleves[$nb_eleves_i]['prenom'],'majf2');

		$taille_max_police=9;
		$taille_min_police=ceil($taille_max_police/3);
		$largeur_dispo=$l_cell_nom;
		//$info_debug=$y_tmp;
		$info_debug="";
		//cell_ajustee("<b>".$texte."</b>".$info_debug,$pdf->GetX(),$y_tmp,$largeur_dispo,$h_cell,$taille_max_police,$taille_min_police,'LRBT');
		cell_ajustee("<b>".$texte."</b>".$info_debug,$X_tableau,$y_tmp,$largeur_dispo,$h_cell,$taille_max_police,$taille_min_police,'LRBT');

		//================================
		// Colonne Avis du conseil de classe:

		// On reforce l'ordonnee pour la colonne Avis du conseil de classe
		$y_tmp = $y_top_tableau+$compteur_eleves_page*$h_cell;

		$pdf->Setxy($X_tableau+$l_cell_nom,$y_tmp);

		if ($nb_periodes==1) {
			if ($current_eleve_avis != '') {
				$avis = $current_eleve_avis;
			} else {
				$avis =' ';
			}
		} else { // Si plusieurs périodes, on indique la période concernée entre parenthèse à côté du nom.
			$texte = "P".$donnees_eleves[$nb_eleves_i]['id_periode']." : ";
			if ($current_eleve_avis != '') {
				$avis = $texte." ".$current_eleve_avis;
			} else {
				$avis =$texte." ";
			}
		}
		
		$pdf->SetFont('DejaVu','',7.5);
		//$pdf->CellFitScale($l_cell_avis,$h_cell,$avis,1,0,'L',0); //le quadrillage

		$hauteur_caractere_appreciation=9;
		$taille_max_police=$hauteur_caractere_appreciation;
		$taille_min_police=ceil($taille_max_police/3);
		$largeur_dispo=$l_cell_avis;
		//cell_ajustee($avis,$pdf->GetX(),$pdf->GetY(),$largeur_dispo,$h_cell,$taille_max_police,$taille_min_police,'LRBT');
		cell_ajustee($avis,$pdf->GetX(),$y_tmp,$largeur_dispo,$h_cell,$taille_max_police,$taille_min_police,'LRBT');

		$pdf->SetFont('DejaVu','',7.5);

		//$pdf->Setxy($X_tableau+$l_cell_nom,$y_tmp+$h_cell);

		$nb_eleves_i++;
		$compteur_eleves_page++;
	}
	$y_tmp = $pdf->GetY();
} // FOR (boucle classe)

$pref_output_mode_pdf=get_output_mode_pdf();

// sortie PDF sur écran
$nom_releve=date("Ymd_Hi");
$nom_releve = 'Avis_conseil_'.$nom_releve.'.pdf';
$pdf->Output($nom_releve,$pref_output_mode_pdf);
?>
