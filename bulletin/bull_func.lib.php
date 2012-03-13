<?php

/* $Id$ */

include("../cahier_notes/visu_releve_notes_func.lib.php");

function nbsp_au_lieu_de_vide($texte) {
	if($texte=="") {
		echo "&nbsp;";
	}
	else {
		echo $texte;
	}
}


/*
function decompte_debug($motif,$texte) {
	global $tab_instant, $debug;
	$instant=time();
	if(isset($tab_instant[$motif])) {
		$diff=$instant-$tab_instant[$motif];
		if($debug=="y") {
			echo "<p>$texte: ".$diff." s</p>\n";
		}
	}
	else {
		if($debug=="y") {
			echo "<p>$texte</p>\n";
		}
	}
	$tab_instant[$motif]=$instant;
}

function decompte_debug($motif,$texte) {
	global $tab_instant, $debug;
	if($debug=="y") {
		$instant=microtime();
		if(isset($tab_instant[$motif])) {
			$tmp_tab1=explode(" ",$instant);
			$tmp_tab2=explode(" ",$tab_instant[$motif]);
			if($tmp_tab1[1]!=$tmp_tab2[1]) {
				$diff=$tmp_tab1[1]-$tmp_tab2[1];
			}
			else {
				$diff=$tmp_tab1[0]-$tmp_tab2[0];
			}
			//if($debug=="y") {
				echo "<p style='color:green;'>$texte: ".$diff." s</p>\n";
			//}
		}
		else {
			//if($debug=="y") {
				echo "<p style='color:green;'>$texte</p>\n";
			//}
		}
		$tab_instant[$motif]=$instant;
	}
}
*/

function regime($id_reg) {
	switch($id_reg) {
		case "d/p":
			$regime="demi-pensionnaire";
			break;
		case "ext.":
			$regime="externe";
			break;
		case "int.":
			$regime="interne";
			break;
		case "i-e":
			$regime="interne-extern�";
			break;
		default:
			$regime="R�gime inconnu???";
			break;
	}

	return $regime;
}

function redimensionne_image_b($photo){
	global $bull_photo_largeur_max, $bull_photo_hauteur_max;

	// prendre les informations sur l'image
	$info_image=getimagesize($photo);
	// largeur et hauteur de l'image d'origine
	$largeur=$info_image[0];
	$hauteur=$info_image[1];

	// calcule le ratio de redimensionnement
	$ratio_l=$largeur/$bull_photo_largeur_max;
	$ratio_h=$hauteur/$bull_photo_hauteur_max;
	$ratio=($ratio_l>$ratio_h)?$ratio_l:$ratio_h;

	// d�finit largeur et hauteur pour la nouvelle image
	$nouvelle_largeur=round($largeur/$ratio);
	$nouvelle_hauteur=round($hauteur/$ratio);

	return array($nouvelle_largeur, $nouvelle_hauteur);
}

/*
function texte_html_ou_pas($texte){
	// Si le texte contient des < et >, on affiche tel quel
	if((strstr($texte,">"))||(strstr($texte,"<"))){
		$retour=$texte;
	}
	// Sinon, on transforme les retours � la ligne en <br />
	else{
		$retour=nl2br($texte);
	}
	return $retour;
}
*/

// $tab_bulletin[$id_classe][$periode_num]
// $i indice �l�ve
function bulletin_html($tab_bull,$i,$tab_rel) {
	//echo "DEBUG";
	global
		//============================================
		// Param�tres g�n�raux:
		// En admin, dans Gestion g�n�rale/Configuration g�n�rale
		$gepi_prof_suivi,

		$RneEtablissement,
		$gepiSchoolName,
		$gepiSchoolAdress1,
		$gepiSchoolAdress2,
		$gepiSchoolZipCode,
		$gepiSchoolCity,
		$gepiSchoolPays,
		$gepiSchoolTel,
		$gepiSchoolFax,
		$gepiYear,

		$logo_etab,
		//============================================
		// Param�tres d'impression des bulletins HTML:

		// Mise en page du bulletin scolaire
		$bull_body_marginleft,
		// $titlesize, $textsize, $p_bulletin_margin sont r�cup�r�s plus haut dans l'ent�te pour �crire les styles
		$largeurtableau,

		$col_matiere_largeur,
		$col_note_largeur,
		$col_boite_largeur,
		$col_hauteur,		// La hauteur minimale de ligne n'est exploit�e que dans les boites/conteneurs
		$cellpadding,
		$cellspacing,
		$bull_ecart_entete,
		$bull_espace_avis,
		// $bull_bordure_classique permet de renseigner $class_bordure
		$class_bordure,

		$bull_categ_font_size,
		$bull_categ_bgcolor,
		//======================
		$bull_categ_font_size_avis,
		$bull_police_avis,
		$bull_font_style_avis,
		// Ils sont utilis�s dans l'ent�te pour g�n�rer les styles
		//======================
		$genre_periode,
		$bull_affich_nom_etab,
		$bull_affich_adr_etab,

		// Informations devant figurer sur le bulletin scolaire
		$bull_mention_nom_court,
		$bull_mention_doublant,
		$bull_affiche_eleve_une_ligne,
		$bull_affiche_appreciations,
		$bull_affiche_absences,
		$bull_affiche_avis,
		$bull_affiche_aid,
		$bull_affiche_numero,		// affichage du num�ro du bulletin
		// L'affichage des graphes devrait provenir des Param�tres d'impression des bulletins HTML, mais le param�tre a �t� stock� dans $tab_bull
		$bull_affiche_signature,	// affichage du nom du PP et du chef d'�tablissement
		$bull_affiche_etab,			// Etablissement d'origine


		$activer_photo_bulletin,
		// $bull_photo_largeur_max et $bull_photo_hauteur_max sont r�cup�r�es via global dans redimensionne_image()

		$bull_affiche_tel,
		$bull_affiche_fax,
		$bull_intitule_app,
		$bull_affiche_INE_eleve,
		$bull_affiche_formule,
		$bull_formule_bas,
		// Nom du fichier d�termin� d'apr�s le param�tre choix_bulletin
		$fichier_bulletin,
		$min_max_moyclas,

		// Bloc adresse responsable
		$addressblock_padding_right,
		$addressblock_padding_top,
		$addressblock_padding_text,
		$addressblock_length,
		$addressblock_font_size,
		//addressblock_logo_etab_prop correspond au pourcentage $largeur1 et $largeur2 est le compl�ment � 100%
		$largeur1,
		$largeur2,
		// Pourcentage calcul� par rapport au tableau contenant le bloc Classe, Ann�e,...
		$addressblock_classe_annee2,
		// Nombre de sauts de ligne entre le bloc Logo+Etablissement et le bloc Nom, pr�nom,... de l'�l�ve
		$bull_ecart_bloc_nom,
		$addressblock_debug,

		// Page de garde
		$page_garde_imprime,
		$affiche_page_garde,
		// Les autres param�tres de la page de garde sont r�cup�r�s directement dans page_garde.php
		// Il faudrait ramener ici les variables pour �viter de faire les requ�tes autant de fois qu'il y a de bulletins

		//============================================
		// Param�tre transmis depuis la page d'impression des bulletins
		$un_seul_bull_par_famille,

		//============================================
		// Tableaux provenant de /lib/global.inc
		$type_etablissement,
		$type_etablissement2,

		//============================================
		// Param�tre du module trombinoscope
		// En admin, dans Gestion des modules
		$active_module_trombinoscopes,

		//$avec_coches_mentions,
		$gepi_denom_mention;

	// R�cup�rer avant le nombre de bulletins � imprimer
	// - que le premier resp
	// - tous les resp si adr diff�rentes
	// et le passer via global
	//================================

	// Initialisation:
	$nb_bulletins=1;

	unset($tab_adr_ligne1);
	unset($tab_adr_ligne2);
	unset($tab_adr_ligne3);
	if ( $affiche_page_garde == 'yes' OR $tab_bull['affiche_adresse'] == 'y') {
		// Pr�paration des lignes adresse responsable
		if (!isset($tab_bull['eleve'][$i]['resp'][0])) {
			$tab_adr_ligne1[0]="<font color='red'><b>ADRESSE MANQUANTE</b></font>";
			$tab_adr_ligne2[0]="";
			$tab_adr_ligne3[0]="";
		}
		else {
			if (isset($tab_bull['eleve'][$i]['resp'][1])) {
				if((isset($tab_bull['eleve'][$i]['resp'][1]['adr1']))&&
					(isset($tab_bull['eleve'][$i]['resp'][1]['adr2']))&&
					(isset($tab_bull['eleve'][$i]['resp'][1]['adr3']))&&
					(isset($tab_bull['eleve'][$i]['resp'][1]['adr4']))&&
					(isset($tab_bull['eleve'][$i]['resp'][1]['cp']))&&
					(isset($tab_bull['eleve'][$i]['resp'][1]['commune']))
				) {
					// Le deuxi�me responsable existe et est renseign�
				if ((strtolower($tab_bull['eleve'][$i]['resp'][0]['adr_id'])==strtolower($tab_bull['eleve'][$i]['resp'][1]['adr_id'])) OR
					(
						(strtolower($tab_bull['eleve'][$i]['resp'][0]['adr1'])==strtolower($tab_bull['eleve'][$i]['resp'][1]['adr1']))&&
						(strtolower($tab_bull['eleve'][$i]['resp'][0]['adr2'])==strtolower($tab_bull['eleve'][$i]['resp'][1]['adr2']))&&
						(strtolower($tab_bull['eleve'][$i]['resp'][0]['adr3'])==strtolower($tab_bull['eleve'][$i]['resp'][1]['adr3']))&&
						(strtolower($tab_bull['eleve'][$i]['resp'][0]['adr4'])==strtolower($tab_bull['eleve'][$i]['resp'][1]['adr4']))&&
						(strtolower($tab_bull['eleve'][$i]['resp'][0]['cp'])==strtolower($tab_bull['eleve'][$i]['resp'][1]['cp']))&&
						(strtolower($tab_bull['eleve'][$i]['resp'][0]['commune'])==strtolower($tab_bull['eleve'][$i]['resp'][1]['commune']))
					)
				) {
						// Les adresses sont identiques
						$nb_bulletins=1;

					if((strtolower($tab_bull['eleve'][$i]['resp'][0]['nom'])!=strtolower($tab_bull['eleve'][$i]['resp'][1]['nom']))&&
							($tab_bull['eleve'][$i]['resp'][1]['nom']!="")) {
							// Les noms des responsables sont diff�rents
							//$tab_adr_ligne1[0]=$tab_bull['eleve'][$i]['resp'][0]['civilite']." ".$tab_bull['eleve'][$i]['resp'][0]['nom']." ".$tab_bull['eleve'][$i]['resp'][0]['prenom']." et ".$tab_bull['eleve'][$i]['resp'][1]['civilite']." ".$tab_bull['eleve'][$i]['resp'][1]['nom']." ".$tab_bull['eleve'][$i]['resp'][1]['prenom'];
							$tab_adr_ligne1[0]=$tab_bull['eleve'][$i]['resp'][0]['civilite']." ".$tab_bull['eleve'][$i]['resp'][0]['nom']." ".$tab_bull['eleve'][$i]['resp'][0]['prenom'];
							//$tab_adr_ligne1[0].=" et ";
							$tab_adr_ligne1[0].="<br />\n";
							$tab_adr_ligne1[0].="et ";
							$tab_adr_ligne1[0].=$tab_bull['eleve'][$i]['resp'][1]['civilite']." ".$tab_bull['eleve'][$i]['resp'][1]['nom']." ".$tab_bull['eleve'][$i]['resp'][1]['prenom'];
						}
						else{
							if(($tab_bull['eleve'][$i]['resp'][0]['civilite']!="")&&($tab_bull['eleve'][$i]['resp'][1]['civilite']!="")) {
								$tab_adr_ligne1[0]=$tab_bull['eleve'][$i]['resp'][0]['civilite']." et ".$tab_bull['eleve'][$i]['resp'][1]['civilite']." ".$tab_bull['eleve'][$i]['resp'][0]['nom']." ".$tab_bull['eleve'][$i]['resp'][0]['prenom'];
							}
							else {
								$tab_adr_ligne1[0]="M. et Mme ".$tab_bull['eleve'][$i]['resp'][0]['nom']." ".$tab_bull['eleve'][$i]['resp'][0]['prenom'];
							}
						}

						$tab_adr_ligne2[0]=$tab_bull['eleve'][$i]['resp'][0]['adr1'];
						if($tab_bull['eleve'][$i]['resp'][0]['adr2']!=""){
							$tab_adr_ligne2[0].="<br />\n".$tab_bull['eleve'][$i]['resp'][0]['adr2'];
						}
						if($tab_bull['eleve'][$i]['resp'][0]['adr3']!=""){
							$tab_adr_ligne2[0].="<br />\n".$tab_bull['eleve'][$i]['resp'][0]['adr3'];
						}
						if($tab_bull['eleve'][$i]['resp'][0]['adr4']!=""){
							$tab_adr_ligne2[0].="<br />\n".$tab_bull['eleve'][$i]['resp'][0]['adr4'];
						}
						$tab_adr_ligne3[0]=$tab_bull['eleve'][$i]['resp'][0]['cp']." ".$tab_bull['eleve'][$i]['resp'][0]['commune'];

						if(($tab_bull['eleve'][$i]['resp'][0]['pays']!="")&&(strtolower($tab_bull['eleve'][$i]['resp'][0]['pays'])!=strtolower($gepiSchoolPays))) {
							if($tab_adr_ligne3[0]!=" "){
								$tab_adr_ligne3[0].="<br />";
							}
							$tab_adr_ligne3[0].=$tab_bull['eleve'][$i]['resp'][0]['pays'];
						}
					}
					else {
						// Les adresses sont diff�rentes
						//if ($un_seul_bull_par_famille!="oui") {
						// On teste en plus si la deuxi�me adresse est valide
						if (($un_seul_bull_par_famille!="oui")&&
							($tab_bull['eleve'][$i]['resp'][1]['adr1']!="")&&
							($tab_bull['eleve'][$i]['resp'][1]['commune']!="")
						) {
							$nb_bulletins=2;
						}
						else {
							$nb_bulletins=1;
						}

						for($cpt=0;$cpt<$nb_bulletins;$cpt++) {
							if($tab_bull['eleve'][$i]['resp'][$cpt]['civilite']!="") {
								$tab_adr_ligne1[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['civilite']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['nom']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['prenom'];
							}
							else {
								$tab_adr_ligne1[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['nom']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['prenom'];
							}

							$tab_adr_ligne2[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['adr1'];
							if($tab_bull['eleve'][$i]['resp'][$cpt]['adr2']!=""){
								$tab_adr_ligne2[$cpt].="<br />\n".$tab_bull['eleve'][$i]['resp'][$cpt]['adr2'];
							}
							if($tab_bull['eleve'][$i]['resp'][$cpt]['adr3']!=""){
								$tab_adr_ligne2[$cpt].="<br />\n".$tab_bull['eleve'][$i]['resp'][$cpt]['adr3'];
							}
							if($tab_bull['eleve'][$i]['resp'][$cpt]['adr4']!=""){
								$tab_adr_ligne2[$cpt].="<br />\n".$tab_bull['eleve'][$i]['resp'][$cpt]['adr4'];
							}
							$tab_adr_ligne3[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['cp']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['commune'];

							if(($tab_bull['eleve'][$i]['resp'][$cpt]['pays']!="")&&(strtolower($tab_bull['eleve'][$i]['resp'][$cpt]['pays'])!=strtolower($gepiSchoolPays))) {
								if($tab_adr_ligne3[$cpt]!=" "){
									$tab_adr_ligne3[$cpt].="<br />";
								}
								$tab_adr_ligne3[$cpt].=$tab_bull['eleve'][$i]['resp'][$cpt]['pays'];
							}

						}

					}
				}
				else {
					// Il n'y a pas de deuxi�me adresse, mais il y aurait un deuxi�me responsable???
					// CA NE DEVRAIT PAS ARRIVER ETANT DONN� LA REQUETE EFFECTUEE QUI JOINT resp_pers ET resp_adr...
						if ($un_seul_bull_par_famille!="oui") {
							$nb_bulletins=2;
						}
						else {
							$nb_bulletins=1;
						}

						for($cpt=0;$cpt<$nb_bulletins;$cpt++) {
							if($tab_bull['eleve'][$i]['resp'][$cpt]['civilite']!="") {
								$tab_adr_ligne1[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['civilite']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['nom']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['prenom'];
							}
							else {
								$tab_adr_ligne1[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['nom']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['prenom'];
							}

							$tab_adr_ligne2[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['adr1'];
							if($tab_bull['eleve'][$i]['resp'][$cpt]['adr2']!=""){
								$tab_adr_ligne2[$cpt].="<br />\n".$tab_bull['eleve'][$i]['resp'][$cpt]['adr2'];
							}
							if($tab_bull['eleve'][$i]['resp'][$cpt]['adr3']!=""){
								$tab_adr_ligne2[$cpt].="<br />\n".$tab_bull['eleve'][$i]['resp'][$cpt]['adr3'];
							}
							if($tab_bull['eleve'][$i]['resp'][$cpt]['adr4']!=""){
								$tab_adr_ligne2[$cpt].="<br />\n".$tab_bull['eleve'][$i]['resp'][$cpt]['adr4'];
							}
							$tab_adr_ligne3[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['cp']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['commune'];

							if(($tab_bull['eleve'][$i]['resp'][$cpt]['pays']!="")&&(strtolower($tab_bull['eleve'][$i]['resp'][$cpt]['pays'])!=strtolower($gepiSchoolPays))) {
								if($tab_adr_ligne3[$cpt]!=" "){
									$tab_adr_ligne3[$cpt].="<br />";
								}
								$tab_adr_ligne3[$cpt].=$tab_bull['eleve'][$i]['resp'][$cpt]['pays'];
							}
						}
				}
			}
			else {
				// Il n'y a pas de deuxi�me responsable
				$nb_bulletins=1;

				if($tab_bull['eleve'][$i]['resp'][0]['civilite']!="") {
					$tab_adr_ligne1[0]=$tab_bull['eleve'][$i]['resp'][0]['civilite']." ".$tab_bull['eleve'][$i]['resp'][0]['nom']." ".$tab_bull['eleve'][$i]['resp'][0]['prenom'];
				}
				else {
					$tab_adr_ligne1[0]=$tab_bull['eleve'][$i]['resp'][0]['nom']." ".$tab_bull['eleve'][$i]['resp'][0]['prenom'];
				}

				$tab_adr_ligne2[0]=$tab_bull['eleve'][$i]['resp'][0]['adr1'];
				if($tab_bull['eleve'][$i]['resp'][0]['adr2']!=""){
					$tab_adr_ligne2[0].="<br />\n".$tab_bull['eleve'][$i]['resp'][0]['adr2'];
				}
				if($tab_bull['eleve'][$i]['resp'][0]['adr3']!=""){
					$tab_adr_ligne2[0].="<br />\n".$tab_bull['eleve'][$i]['resp'][0]['adr3'];
				}
				if($tab_bull['eleve'][$i]['resp'][0]['adr4']!=""){
					$tab_adr_ligne2[0].="<br />\n".$tab_bull['eleve'][$i]['resp'][0]['adr4'];
				}
				$tab_adr_ligne3[0]=$tab_bull['eleve'][$i]['resp'][0]['cp']." ".$tab_bull['eleve'][$i]['resp'][0]['commune'];

				if(($tab_bull['eleve'][$i]['resp'][0]['pays']!="")&&(strtolower($tab_bull['eleve'][$i]['resp'][0]['pays'])!=strtolower($gepiSchoolPays))) {
					if($tab_adr_ligne3[0]!=" "){
						$tab_adr_ligne3[0].="<br />";
					}
					$tab_adr_ligne3[0].=$tab_bull['eleve'][$i]['resp'][0]['pays'];
				}
			}
		}
	}
	// Fin de la pr�paration des lignes adresse responsable

	// Pour afficher deux moyennes g�n�rales: avec les coeff de Gestion des bases/<Classe> Enseignements et avec des coef � 1
	$affiche_deux_moy_gen=$tab_bull['affiche_moyenne_general_coef_1'];

	// D�but des bulletins
	for ($bulletin=0; $bulletin<$nb_bulletins; $bulletin++) {
		echo "\n<!-- D�but du bulletin n�$bulletin pour ".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom'].", ".$tab_bull['eleve'][$i]['classe']." -->\n\n";

		// Page de garde
		if ( $affiche_page_garde == 'yes' OR $tab_bull['affiche_adresse'] == 'y') {

			// Affectation des lignes adresse responsable avec les lignes correspondant au bulletin en cours
			$ligne1=$tab_adr_ligne1[$bulletin];
			$ligne2=$tab_adr_ligne2[$bulletin];
			$ligne3=$tab_adr_ligne3[$bulletin];

			// Info affich�e en haut de la page de garde
			$info_eleve_page_garde="El�ve: ".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom'].", ".$tab_bull['eleve'][$i]['classe'];

			if ($affiche_page_garde == "yes") {
				echo "\n<!-- D�but de la page de garde -->\n\n";
				include "./page_garde.php";
				echo "\n<!-- Fin de la page de garde -->\n\n";
				// Saut de page
				echo "<p class='saut'>&nbsp;</p>\n";
			}
		}


		echo "\n<!-- D�but de l'affichage de l'ent�te du bulletin n�$bulletin pour ".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom'].", ".$tab_bull['eleve'][$i]['classe']." -->\n";

		if($tab_bull['affiche_adresse'] == 'y') {
			//-------------------------------
			// Maintenant, on affiche l'en-t�te : Les donn�es de l'�l�ve, le bloc adresse responsable et l'adresse du lyc�e.
			//-------------------------------

			echo "\n<!-- D�but du cadre ent�te -->\n";
			echo "<div style='";
			//if($addressblock_debug=="y"){echo " style='border:1px solid red;'";}
			if($addressblock_debug=="y"){echo "border:1px solid red;";}
			else {echo "border:1px dashed white;";}
			echo "'>\n";

			// Pour �viter que le cadre Adresse responsable ne vienne remonter sur la page pr�c�dente:
			echo "<div style='clear: both; font-size: xx-small;'>&nbsp;</div>\n";

			// Cadre adresse du responsable:
			echo "<div style='float:right;
width:".$addressblock_length."mm;
padding-top:".$addressblock_padding_top."mm;
padding-bottom:".$addressblock_padding_text."mm;
padding-right:".$addressblock_padding_right."mm;\n";
			if($addressblock_debug=="y"){echo "border: 1px solid blue;\n";}
			echo "font-size: ".$addressblock_font_size."pt;
'>
<div align='left'>
$ligne1<br />
$ligne2<br />
$ligne3
</div>
</div>\n";


			// Cadre contenant le tableau Logo+Ad_etab et le nom, pr�nom,... de l'�l�ve:
			echo "<div style='float:left;
left:0px;
top:0px;
width:".$largeur1."%;\n";
			if($addressblock_debug=="y"){echo "border: 1px solid green;\n";}
			echo "'>\n";

			echo "<table summary='Tableau du logo et infos �tablissement'";
			if($addressblock_debug=="y"){echo " border='1'";}
			echo ">\n";
			echo "<tr>\n";

			$nom_fic_logo = $logo_etab;
			$nom_fic_logo_c = "../images/".$nom_fic_logo;

			if (($nom_fic_logo != '') and (file_exists($nom_fic_logo_c))) {
				echo "<td style=\"text-align: left;\"><img src=\"".$nom_fic_logo_c."\" border=\"0\" alt=\"Logo\" /></td>\n";
			}
			echo "<td style='text-align: center;'>";
			echo "<p class='bulletin'>";
			if($bull_affich_nom_etab=="y"){
				echo "<span class=\"bgrand\">".$gepiSchoolName."</span>";
			}
			if($bull_affich_adr_etab=="y"){
				echo "<br />\n".$gepiSchoolAdress1."<br />\n".$gepiSchoolAdress2."<br />\n".$gepiSchoolZipCode." ".$gepiSchoolCity;
				if($bull_affiche_tel=="y"){echo "<br />\nTel: ".$gepiSchoolTel;}
				if($bull_affiche_fax=="y"){echo "<br />\nFax: ".$gepiSchoolFax;}
			}
			echo "</p>\n";

			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";

			echo "<br />";


			// On rajoute des lignes vides
			$n = 0;
			while ($n < $bull_ecart_bloc_nom) {
				echo "<br />";
				$n++;
			}

			if ($activer_photo_bulletin=='y' and $active_module_trombinoscopes=='y') {
				$photo=nom_photo($tab_bull['eleve'][$i]['elenoet']);
				//echo "$photo";
				//if("$photo"!=""){
				if($photo){
					//$photo="../photos/eleves/".$photo;
					//if(file_exists($photo)){
						//$dimphoto=redimensionne_image($photo);
						$dimphoto=redimensionne_image_b($photo);
						echo '<img src="'.$photo.'" style="width: '.$dimphoto[0].'px; height: '.$dimphoto[1].'px; border: 0px; border-right: 3px solid #FFFFFF; float: left;" alt="" />'."\n";
					//}
				}
			}


			//affichage des donn�es sur une seule ligne ou plusieurs
			if  ($bull_affiche_eleve_une_ligne == 'no') { // sur plusieurs lignes
				echo "<p class='bulletin'>\n";
				echo "<b><span class=\"bgrand\">".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom']."</span></b><br />";
				echo "N�";
				if (strtoupper($tab_bull['eleve'][$i]['sexe'])=="F") {echo "e";}
				echo "&nbsp;le&nbsp;".$tab_bull['eleve'][$i]['naissance'];
				//Eric Ajout
				echo "<br />";
				if ($tab_bull['eleve'][$i]['regime'] == "d/p") {echo "Demi-pensionnaire";}
				if ($tab_bull['eleve'][$i]['regime'] == "ext.") {echo "Externe";}
				if ($tab_bull['eleve'][$i]['regime'] == "int.") {echo "Interne";}
				if ($tab_bull['eleve'][$i]['regime'] == "i-e"){
					echo "Interne&nbsp;extern�";
					if (strtoupper($tab_bull['eleve'][$i]['sexe'])=="F") {echo "e";}
				}
				//Eric Ajout
				if ($bull_mention_doublant == 'yes'){
					if ($tab_bull['eleve'][$i]['doublant'] == 'R'){
					echo "<br />";
					echo "Redoublant";
					if (strtoupper($tab_bull['eleve'][$i]['sexe'])=="F") {echo "e";}
					}
				}

				if ($bull_mention_nom_court == 'no') {
					//Eric Ajout et supp
					//echo "<BR />";
					//echo ", $current_classe";
				} else {
					echo "<br />";
					echo $tab_bull['eleve'][$i]['classe'];
				}
			}
			else { //sur une ligne
				echo "<p class='bulletin'>\n";
				echo "<b><span class=\"bgrand\">".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom']."</span></b><br />";
				echo "N�";
				if (strtoupper($tab_bull['eleve'][$i]['sexe'])=="F") {echo "e";}
				echo "&nbsp;le&nbsp;".$tab_bull['eleve'][$i]['naissance'];
				if ($tab_bull['eleve'][$i]['regime'] == "d/p") {echo ", Demi-pensionnaire";}
				if ($tab_bull['eleve'][$i]['regime'] == "ext.") {echo ", Externe";}
				if ($tab_bull['eleve'][$i]['regime'] == "int.") {echo ", Interne";}
				if ($tab_bull['eleve'][$i]['regime'] == "i-e"){
					echo ", Interne&nbsp;extern�";
					if (strtoupper($tab_bull['eleve'][$i]['sexe'])=="F") {echo "e";}
				}
				if ($bull_mention_doublant == 'yes'){
					if ($tab_bull['eleve'][$i]['doublant'] == 'R'){
						echo ", Redoublant";
						if (strtoupper($tab_bull['eleve'][$i]['sexe'])=="F") {echo "e";}
					}
				}
				if ($bull_mention_nom_court == 'yes') {
					echo ", ".$tab_bull['eleve'][$i]['classe'];
				}
			}

			if($bull_affiche_INE_eleve=="y"){
				echo "<br />\n";
				echo "Num�ro INE: ".$tab_bull['eleve'][$i]['no_gep'];
			}

			if($bull_affiche_etab=="y"){
				if ((isset($tab_bull['eleve'][$i]['etab_nom']))&&($tab_bull['eleve'][$i]['etab_nom']!='')) {
					echo "<br />\n";
					if ($tab_bull['eleve'][$i]['etab_id'] != '990') {
						if ($RneEtablissement != $tab_bull['eleve'][$i]['etab_id']) {
							echo "Etablissement d'origine : ";
							echo $tab_bull['eleve'][$i]['etab_niveau_nom']." ".$tab_bull['eleve'][$i]['etab_type']." ".$tab_bull['eleve'][$i]['etab_nom']." (".$tab_bull['eleve'][$i]['etab_cp']." ".$tab_bull['eleve'][$i]['etab_ville'].")\n";
						}
					} else {
						echo "Etablissement d'origine : ";
						echo "hors de France\n";
					}
				}
			}

			echo "</p>\n";

			echo "</div>\n";

			//echo "<spacer type='vertical' size='10'>";


			// Tableau contenant le nom de la classe, l'ann�e et la p�riode.
			echo "<table width='".$largeur2."%' ";
			if($addressblock_debug=="y"){echo "border='1' ";}
			echo "cellspacing='".$cellspacing."' cellpadding='".$cellpadding."' summary='Tableau des nom de classe, ann�e et p�riode'>\n";
			echo "<tr>\n";
			echo "<td class='empty'>\n";
			echo "&nbsp;\n";
			echo "</td>\n";
			echo "<td style='width:".$addressblock_classe_annee2."%;'>\n";
			echo "<p class='bulletin' align='center'><span class=\"bgrand\">Classe de ".$tab_bull['eleve'][$i]['classe_nom_complet']."<br />Ann�e scolaire ".$gepiYear."</span><br />\n";
			$temp = strtolower($tab_bull["nom_periode"]);
			echo "Bulletin&nbsp;";
			if($genre_periode=="M"){
				echo "du ";
			}
			else{
				echo "de la ";
			}
			echo "$temp</p>";
			echo "</td>\n";
			echo "</tr>\n";
			echo "</table>\n";

			// Pour que le tableau des appr�ciations ne vienne pas s'encastrer dans les DIV float:
			echo "<div style='clear: both; font-size: xx-small;'>&nbsp;</div>\n";

			// Fin du cadre ent�te:
			echo "</div>\n";

		}
		else{
			//-------------------------------
			// Maintenant, on affiche l'en-t�te : Les donn�es de l'�l�ve, et l'adresse du lyc�e.
			// sans bloc adresse responsable
			//-------------------------------

			echo "<table width='$largeurtableau' border='0' cellspacing='".$cellspacing."' cellpadding='".$cellpadding."' summary='Tableau des donn�es �l�ve et �tablissement'>\n";

			echo "<tr>\n";
			echo "<td style=\"width: 30%;\">\n";
			if ($activer_photo_bulletin=='y' and $active_module_trombinoscopes=='y') {
				$photo=nom_photo($tab_bull['eleve'][$i]['elenoet']);
				//echo "$photo";
				if("$photo"!=""){
					//$photo="../photos/eleves/".$photo;
					if(file_exists($photo)){
						echo '<img src="'.$photo.'" style="width: 60px; height: 80px; border: 0px; border-right: 3px solid #FFFFFF; float: left;" alt="" />'."\n";
					}
				}
			}

				//affichage des donn�es sur une seule ligne ou plusieurs
			if  ($bull_affiche_eleve_une_ligne == 'no') { // sur plusieurs lignes
				echo "<p class='bulletin'>\n";
				echo "<b><span class=\"bgrand\">".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom']."</span></b><br />";
				echo "N�";
				if (strtoupper($tab_bull['eleve'][$i]['sexe'])=="F") {echo "e";}
				echo "&nbsp;le&nbsp;".$tab_bull['eleve'][$i]['naissance'];
				//Eric Ajout
				echo "<br />";
				if ($tab_bull['eleve'][$i]['regime'] == "d/p") {echo "Demi-pensionnaire";}
				if ($tab_bull['eleve'][$i]['regime'] == "ext.") {echo "Externe";}
				if ($tab_bull['eleve'][$i]['regime'] == "int.") {echo "Interne";}
				if ($tab_bull['eleve'][$i]['regime'] == "i-e"){
					echo "Interne&nbsp;extern�";
					if (strtoupper($tab_bull['eleve'][$i]['sexe'])=="F") {echo "e";}
				}
				//Eric Ajout
				if ($bull_mention_doublant == 'yes'){
					if ($tab_bull['eleve'][$i]['doublant'] == 'R'){
					echo "<br />";
					echo "Redoublant";
					if (strtoupper($tab_bull['eleve'][$i]['sexe'])=="F") {echo "e";}
					}
				}


				if ($bull_mention_nom_court == 'no') {
					//Eric Ajout et supp
					//echo "<BR />";
					//echo ", $current_classe";
				} else {
					echo "<br />";
					echo $tab_bull['eleve'][$i]['classe'];
				}

			} else { //sur une ligne
				echo "<p class='bulletin'>\n";
				echo "<b><span class=\"bgrand\">".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom']."</span></b><br />";
				echo "N�";
				if (strtoupper($tab_bull['eleve'][$i]['sexe'])=="F") {echo "e";}
				echo "&nbsp;le&nbsp;".$tab_bull['eleve'][$i]['naissance'];

				if ($tab_bull['eleve'][$i]['regime'] == "d/p") {echo ", Demi-pensionnaire";}
				elseif ($tab_bull['eleve'][$i]['regime'] == "ext.") {echo ", Externe";}
				elseif ($tab_bull['eleve'][$i]['regime'] == "int.") {echo ", Interne";}
				elseif ($tab_bull['eleve'][$i]['regime'] == "i-e"){
					echo ", Interne&nbsp;extern�";
					if (strtoupper($tab_bull['eleve'][$i]['sexe'])=="F") {echo "e";}
				}
				//Eric Ajout
				if ($bull_mention_doublant == 'yes'){
					if ($tab_bull['eleve'][$i]['doublant'] == 'R'){
					echo ", Redoublant";
					if (strtoupper($tab_bull['eleve'][$i]['sexe'])=="F") {echo "e";}
					}
				}
				if ($bull_mention_nom_court == 'yes') {
					echo ", ".$tab_bull['eleve'][$i]['classe'];
				}
			}


			if($bull_affiche_etab=="y"){
				if ((isset($tab_bull['eleve'][$i]['etab_nom']))&&($tab_bull['eleve'][$i]['etab_nom']!='')) {
					echo "<br />\n";
					if ($tab_bull['eleve'][$i]['etab_id'] != '990') {
						if ($RneEtablissement != $tab_bull['eleve'][$i]['etab_id']) {
							echo "Etablissement d'origine : ";
							echo $tab_bull['eleve'][$i]['etab_niveau_nom']." ".$tab_bull['eleve'][$i]['etab_type']." ".$tab_bull['eleve'][$i]['etab_nom']." (".$tab_bull['eleve'][$i]['etab_cp']." ".$tab_bull['eleve'][$i]['etab_ville'].")\n";
						}
					} else {
						echo "Etablissement d'origine : ";
						echo "hors de France\n";
					}
				}
			}

			echo "</p></td>\n<td style=\"width: 40%;text-align: center;\">\n";

			if ($tab_bull['affiche_adresse'] != "y") {
				echo "<p class='bulletin'><span class=\"bgrand\">Classe de ".$tab_bull['eleve'][$i]['classe_nom_complet']."<br />Ann�e scolaire ".$gepiYear."</span><br />\n";
				$temp = strtolower($tab_bull['nom_periode']);
				echo "Bulletin&nbsp;";
				if($genre_periode=="M"){
					echo "du ";
				}
				else{
					echo "de la ";
				}
				echo "$temp</p>\n";
			} else {
				echo "&nbsp;";
			}

			$nom_fic_logo = $logo_etab;
			$nom_fic_logo_c = "../images/".$nom_fic_logo;
			if (($nom_fic_logo != '') and (file_exists($nom_fic_logo_c))) {
				echo "</td>\n<td style=\"text-align: right;\"><img src=\"".$nom_fic_logo_c."\" border=\"0\" alt=\"Logo\" />";
			} else {
				echo "</td>\n<td>&nbsp;";
			}
			echo "</td>\n";
			echo "<td style=\"width: 20%;text-align: center;\">";
			echo "<p class='bulletin'>";
			if($bull_affich_nom_etab=="y"){
				echo "<span class=\"bgrand\">".$gepiSchoolName."</span>";
			}
			if($bull_affich_adr_etab=="y"){
				//echo "<span class=\"bgrand\">".$gepiSchoolName."</span>";
				if($bull_affich_nom_etab=="y"){echo "<br />\n";}
				echo $gepiSchoolAdress1."<br />\n";
				echo $gepiSchoolAdress2."<br />\n";
				echo $gepiSchoolZipCode." ".$gepiSchoolCity;

				if($bull_affiche_tel=="y"){echo "<br />\nTel: ".$gepiSchoolTel;}
				if($bull_affiche_fax=="y"){echo "<br />\nFax: ".$gepiSchoolFax;}
			}
			echo "</p>\n";

			echo "</td>\n</tr>\n</table>\n";
			//-------------------------------
			// Fin de l'en-t�te
		}

		echo "\n<!-- Fin de l'affichage de l'ent�te du bulletin n�$bulletin pour ".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom'].", ".$tab_bull['eleve'][$i]['classe']." -->\n\n";

		// On rajoute des lignes vides
		$n = 0;
		while ($n < $bull_ecart_entete) {
			echo "<br />\n";
			$n++;
		}



		echo "\n<!-- D�but de l'affichage du tableau des mati�res du bulletin n�$bulletin pour ".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom'].", ".$tab_bull['eleve'][$i]['classe']." -->\n\n";
        //=============================================

		// Tableau des mati�res/notes/appr�ciations
		$k=$i+1;
		include ($fichier_bulletin);

        //=============================================
		echo "\n<!-- Fin de l'affichage du tableau des mati�res du bulletin n�$bulletin pour ".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom'].", ".$tab_bull['eleve'][$i]['classe']." -->\n\n";


		// Absences et retards
		//if($tab_bull['affiche_absences']=='y') {
		if($bull_affiche_absences=='y') {
			echo "\n<!-- D�but de l'affichage du tableau des absences du bulletin n�$bulletin pour ".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom'].", ".$tab_bull['eleve'][$i]['classe']." -->\n\n";

            echo "<table width='$largeurtableau' border='0' cellspacing='".$cellspacing."' cellpadding='".$cellpadding."' summary='Tableau des absences'>\n";
			echo "<tr>\n";
			echo "<td style='vertical-align: top;'>\n";
			echo "<p class='bulletin'>";
			if ($tab_bull['eleve'][$i]['eleve_absences'] == '0') {
				echo "<i>Aucune demi-journ�e d'absence</i>.";
			} else {
				echo "<i>Nombre de demi-journ�es d'absence ";
				if ($tab_bull['eleve'][$i]['eleve_nj'] == '0') {echo "justifi�es ";}
				echo ": </i><b>".$tab_bull['eleve'][$i]['eleve_absences']."</b>";
				if ($tab_bull['eleve'][$i]['eleve_nj'] != '0') {
					echo " (dont <b>".$tab_bull['eleve'][$i]['eleve_nj']."</b> non justifi�e"; if ($tab_bull['eleve'][$i]['eleve_nj'] != '1') {echo "s";}
					echo ")";
				}
				echo ".";
			}
			if ($tab_bull['eleve'][$i]['eleve_retards'] != '0') {
				echo "<i> Nombre de retards : </i><b>".$tab_bull['eleve'][$i]['eleve_retards']."</b>";
			}
			echo "  (C.P.E. charg�";

			if($tab_bull['eleve'][$i]['cperesp_civilite']!="M.") {
				echo "e";
			}

			echo " du suivi : ". affiche_utilisateur($tab_bull['eleve'][$i]['cperesp_login'],$tab_bull['id_classe']) . ")";
			if ($tab_bull['eleve'][$i]['appreciation_absences']!="") {echo "<br />".texte_html_ou_pas($tab_bull['eleve'][$i]['appreciation_absences']);}
			echo "</p>\n";
			echo "</td>\n</tr>\n</table>\n";

			echo "\n<!-- Fin de l'affichage du tableau des absences du bulletin n�$bulletin pour ".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom'].", ".$tab_bull['eleve'][$i]['classe']." -->\n\n";

		}



		//=============================================


		// Avis du conseil de classe � ramener par l�

		if (($bull_affiche_avis == 'y')||($bull_affiche_signature == 'y')) {

			echo "\n<!-- D�but de l'affichage du tableau de l'avis du conseil/signature du bulletin n�$bulletin pour ".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom'].", ".$tab_bull['eleve'][$i]['classe']." -->\n\n";

			// Tableau de l'avis des conseil de classe
			echo "<table $class_bordure width='$largeurtableau' border='1' cellspacing='".$cellspacing."' cellpadding='".$cellpadding."' summary=\"Tableau de l'avis du conseil de classe\">\n";
			echo "<tr>\n";
		}

        if ($bull_affiche_avis == 'y') {
			//
			// Case de gauche : avis des conseils de classe
			//
			echo "<td style='vertical-align: top; text-align: left;'>\n";
			// 1) l'avis
			echo "<span class='bulletin'><i>Avis du conseil de classe:</i></span><br />\n";

			if($tab_bull['avis'][$i]!="") {
				echo "<span class='avis_bulletin'>";
				/*
				if((strstr($tab_bull['avis'][$i],">"))||(strstr($tab_bull['avis'][$i],"<"))){
					echo $tab_bull['avis'][$i];
				}
				else{
					echo nl2br($tab_bull['avis'][$i]);
				}
				*/
				echo texte_html_ou_pas($tab_bull['avis'][$i]);
				echo "</span>";

				// **** AJOUT POUR LES MENTIONS ****
				if(getSettingValue('bull_affich_mentions')!="n") {
					if((!isset($tableau_des_mentions_sur_le_bulletin))||(!is_array($tableau_des_mentions_sur_le_bulletin))||(count($tableau_des_mentions_sur_le_bulletin)==0)) {
						$tableau_des_mentions_sur_le_bulletin=get_mentions();
					}
					//if((trim($tab_bull['id_mention'][$i])!="")||($avec_coches_mentions=="y")) {
					if(isset($tableau_des_mentions_sur_le_bulletin[$tab_bull['id_mention'][$i]])) {
						echo "<br/>\n";
						if(getSettingValue('bull_affich_intitule_mentions')!="n") {
							echo "<b>".ucfirst($gepi_denom_mention)." : </b>";
						}
						echo texte_html_ou_pas(traduction_mention($tab_bull['id_mention'][$i]));
					}
				}
				// **** FIN D'AJOUT POUR LES MENTIONS ****

				if($bull_affiche_signature == 'y'){
					echo "<br />\n";
				}
			}
			else {
				// Compteur des lignes vides � ajouter
				$n = 0;

				// **** AJOUT POUR LES MENTIONS ****
				if(getSettingValue('bull_affich_mentions')!="n") {
					if((!isset($tableau_des_mentions_sur_le_bulletin))||(!is_array($tableau_des_mentions_sur_le_bulletin))||(count($tableau_des_mentions_sur_le_bulletin)==0)) {
						$tableau_des_mentions_sur_le_bulletin=get_mentions();
					}
					//if((trim($tab_bull['id_mention'][$i])!="")||($avec_coches_mentions=="y")) {
					if(isset($tableau_des_mentions_sur_le_bulletin[$tab_bull['id_mention'][$i]])) {
						echo "<br/>\n";
						if(getSettingValue('bull_affich_intitule_mentions')!="n") {
							echo "<b>".ucfirst($gepi_denom_mention)." : </b>";
						}
						echo texte_html_ou_pas(traduction_mention($tab_bull['id_mention'][$i]));
						$n++;
					}
				}
				// **** FIN D'AJOUT POUR LES MENTIONS ****

				if($n==0) {
					echo "&nbsp;";
				}

				// Si il n'y a pas d'avis, on rajoute des lignes vides selon les param�tres d'impression
				if ($bull_espace_avis >0){
					while ($n < $bull_espace_avis) {
						echo "<br />\n";
						$n++;
					}
				}
			}
		}
        elseif ($bull_affiche_signature == 'y') {
            echo "<td style=\"vertical-align: top;\">";
        }

        if ($bull_affiche_signature == 'y') {
            // 2) Le nom du professeur principal
			/*
			if(isset($tab_bull['eleve'][$i]['pp']['login'])) {
				echo "<b>".ucfirst($gepi_prof_suivi)."</b> ";
				echo "<i>".affiche_utilisateur($tab_bull['eleve'][$i]['pp']['login'],$tab_bull['eleve'][$i]['id_classe'])."</i>";
			}
			*/
			if(isset($tab_bull['eleve'][$i]['pp'][0])) {
				echo "<b>".ucfirst($gepi_prof_suivi)."</b> ";
				echo "<i>".affiche_utilisateur($tab_bull['eleve'][$i]['pp'][0]['login'],$tab_bull['eleve'][$i]['id_classe'])."</i>";
				for($i_pp=1;$i_pp<count($tab_bull['eleve'][$i]['pp']);$i_pp++) {
					echo ", ";
					echo "<i>".affiche_utilisateur($tab_bull['eleve'][$i]['pp'][$i_pp]['login'],$tab_bull['eleve'][$i]['id_classe'])."</i>";
				}
			}

			echo "</td>\n";
			//
			// Case de droite : paraphe du proviseur
			//
			echo "<td style='vertical-align: top; text-align: left;' width='30%'>\n";
			echo "<!-- Case: paraphe du proviseur -->\n";
			if($tab_bull['formule']!='') {echo "<span class='bulletin'><b>".$tab_bull['formule']."</b>:</span><br />";}
			if($tab_bull['suivi_par']!='') {echo "<span class='bulletin'><i>".$tab_bull['suivi_par']."</i></span>";}
		}

        // Si une des deux variables 'bull_affiche_avis' ou 'bull_affiche_signature' est � 'y', il faut fermer le tableau
        if (($bull_affiche_avis == 'y')||($bull_affiche_signature == 'y')) {
            echo "</td>\n";
            // Fin du tableau
            echo "</tr>\n";
			echo "</table>\n";

			echo "\n<!-- Fin de l'affichage du tableau de l'avis du conseil/signature du bulletin n�$bulletin pour ".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom'].", ".$tab_bull['eleve'][$i]['classe']." -->\n\n";

        }
		//================================



		// Affichage de la formule de bas de page

		if (($bull_formule_bas != '') and ($bull_affiche_formule == 'y')) {
			// Pas d'affichage dans le cas d'un bulletin d'une p�riode "examen blanc"
			echo "<table width='$largeurtableau' style='margin-left:5px; margin-right:5px;' border='0' cellspacing='".$cellspacing."' cellpadding='".$cellpadding."' summary='Formule de bas de page'>\n";
			echo "<tr>";
			echo "<td><p align='center' class='bulletin'>".$bull_formule_bas."</p></td>\n";
			echo "</tr></table>";
		}


		echo "\n<!-- Fin du bulletin n�$bulletin pour ".$tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom'].", ".$tab_bull['eleve'][$i]['classe']." -->\n\n";


		// Insertion du relev� de notes si r�clam�:
		if(count($tab_rel)!=0) {
			echo "<p class='saut'>&nbsp;</p>\n";
			// Il y a un d�calage sur les indices dans le cas o� on n'imprime pas la classe enti�re
			//releve_html($tab_rel,$i,$bulletin);
			if(isset($tab_rel['eleve'])) {
				for($k=0;$k<count($tab_rel['eleve']);$k++) {
					if($tab_rel['eleve'][$k]['login']==$tab_bull['eleve'][$i]['login']) {
						releve_html($tab_rel,$k,$bulletin);
						break;
					}
				}
			}
			else {
				echo "<p style='color:red;'>Il semble que le tableau des relev�s de notes soit vide.</p>\n";
			}
		}

		if(($bulletin==0)&&($nb_bulletins==2)){
			echo "<p class='saut'>&nbsp;</p>\n";
		}

	}
}

function bulletin_pdf($tab_bull,$i,$tab_rel) {
	//echo "DEBUG";
	global
		//==============
		//Ajout J.Etheve
		//$coefficients_a_1,
		//==============
		//============================================
		// Param�tres g�n�raux:
		// En admin, dans Gestion g�n�rale/Configuration g�n�rale
		$gepi_prof_suivi,

		$RneEtablissement,
		$gepiSchoolName,
		$gepiSchoolAdress1,
		$gepiSchoolAdress2,
		$gepiSchoolZipCode,
		$gepiSchoolCity,
		$gepiSchoolPays,
		$gepiSchoolTel,
		$gepiSchoolFax,
		$gepiSchoolEmail,
		$gepiYear,

		$logo_etab,

		$bull_intitule_app,

		$bull_formule_bas,

		// Param�tre transmis depuis la page d'impression des bulletins
		$un_seul_bull_par_famille,

		$compteur_bulletins,

		// Datation du bulletin (param�tre initi� dans l'ent�te du bulletin PDF)
		$date_bulletin,

		// Param�tres du mod�le PDF
		$tab_modele_pdf,

		$use_cell_ajustee,

		// Pour permettre de r�cup�rer via global dans releve_pdf() le num�ro du parent dont on imprime le bulletin avec au verso le relev� de notes:
		$num_resp_bull,

		// Pour r�cup�rer le 1 relev� par page en verso du bulletin... variable r�cup�r�e via 'global' dans la fonction releve_pdf()
		$nb_releve_par_page,

		//20100615
		//$moyennes_periodes_precedentes,
		//$evolution_moyenne_periode_precedente,

		//$avec_coches_mentions,
		$gepi_denom_mention,

		// Objet PDF initi� hors de la pr�sente fonction donnant la page du bulletin pour un �l�ve
		$pdf;
		//=========================================

		if (isset($GLOBALS['multisite']) AND $GLOBALS['multisite'] == 'y') {
			// On r�cup�re le RNE de l'�tablissement
			$rep_photos="../photos/".$_COOKIE['RNE']."/eleves/";
		}else{
			$rep_photos="../photos/eleves/";
		}

	//==============
	//Ajout J.Etheve
	// ***** flag pour l'affichage de la moyenne g�n�rale non coefficient�e
	/*
	if ($coefficients_a_1!="oui") {
		$affiche_deux_moy_gen=1;
	}
	else {
		$affiche_deux_moy_gen==0;
	}
	*/
	$affiche_deux_moy_gen=$tab_bull['affiche_moyenne_general_coef_1'];
	// *****

	$affiche_numero_responsable=$tab_bull['affiche_numero_responsable'];
	
	if(($nb_releve_par_page!=1)||($nb_releve_par_page!=2)) {
		// Actuellement, on n'a qu'un bulletin par page/recto donc qu'un relev� de notes par verso, mais sait-on jamais un jour...
		$nb_releve_par_page=1;
	}

	// Pr�paration des lignes d'adresse

	//echo "\$i=$i et \$nb_bulletins=$nb_bulletins<br />";

	// Initialisation:
	for($loop=0;$loop<=1;$loop++) {
		$tab_adr_ligne1[$loop]="";
		$tab_adr_ligne2[$loop]="";
		$tab_adr_ligne3[$loop]="";
		$tab_adr_ligne4[$loop]="";
		$tab_adr_ligne5[$loop]="";
		$tab_adr_ligne6[$loop]="";
	}

	// ON N'UTILISE PAS LE CHAMP adr4 DE L'ADRESSE DANS resp_adr
	// IL FAUDRA VOIR COMMENT LE RECUPERER

	if (!isset($tab_bull['eleve'][$i]['resp'][0])) {
		//$tab_adr_ligne1[0]="<font color='red'><b>ADRESSE MANQUANTE</b></font>";
		$tab_adr_ligne1[0]="ADRESSE MANQUANTE";
		$tab_adr_ligne2[0]="";
		$tab_adr_ligne3[0]="";
		$tab_adr_ligne4[0]="";
		$tab_adr_ligne5[0]="";

		// Initialisation parce qu'on a des blagues s'il n'y a pas de resp:
		$nb_bulletins=1;
	}
	else {
		if (isset($tab_bull['eleve'][$i]['resp'][1])) {
			if((isset($tab_bull['eleve'][$i]['resp'][1]['adr1']))&&
				(isset($tab_bull['eleve'][$i]['resp'][1]['adr2']))&&
				(isset($tab_bull['eleve'][$i]['resp'][1]['adr3']))&&
				(isset($tab_bull['eleve'][$i]['resp'][1]['adr4']))&&
				(isset($tab_bull['eleve'][$i]['resp'][1]['cp']))&&
				(isset($tab_bull['eleve'][$i]['resp'][1]['commune']))
			) {
				// Le deuxi�me responsable existe et est renseign�
				if (($tab_bull['eleve'][$i]['resp'][0]['adr_id']==$tab_bull['eleve'][$i]['resp'][1]['adr_id']) OR
					(
						(strtolower($tab_bull['eleve'][$i]['resp'][0]['adr1'])==strtolower($tab_bull['eleve'][$i]['resp'][1]['adr1']))&&
						(strtolower($tab_bull['eleve'][$i]['resp'][0]['adr2'])==strtolower($tab_bull['eleve'][$i]['resp'][1]['adr2']))&&
						(strtolower($tab_bull['eleve'][$i]['resp'][0]['adr3'])==strtolower($tab_bull['eleve'][$i]['resp'][1]['adr3']))&&
						(strtolower($tab_bull['eleve'][$i]['resp'][0]['adr4'])==strtolower($tab_bull['eleve'][$i]['resp'][1]['adr4']))&&
						($tab_bull['eleve'][$i]['resp'][0]['cp']==$tab_bull['eleve'][$i]['resp'][1]['cp'])&&
						(strtolower($tab_bull['eleve'][$i]['resp'][0]['commune'])==strtolower($tab_bull['eleve'][$i]['resp'][1]['commune']))
					)
				) {
					// Les adresses sont identiques
					$nb_bulletins=1;

					if(($tab_bull['eleve'][$i]['resp'][0]['nom']!=$tab_bull['eleve'][$i]['resp'][1]['nom'])&&
						($tab_bull['eleve'][$i]['resp'][1]['nom']!="")) {
						// Les noms des responsables sont diff�rents
						$tab_adr_ligne1[0]=$tab_bull['eleve'][$i]['resp'][0]['civilite']." ".$tab_bull['eleve'][$i]['resp'][0]['nom']." ".$tab_bull['eleve'][$i]['resp'][0]['prenom']." et ".$tab_bull['eleve'][$i]['resp'][1]['civilite']." ".$tab_bull['eleve'][$i]['resp'][1]['nom']." ".$tab_bull['eleve'][$i]['resp'][1]['prenom'];

						/*
						$tab_adr_ligne1[0]=$tab_bull['eleve'][$i]['resp'][0]['civilite']." ".$tab_bull['eleve'][$i]['resp'][0]['nom']." ".$tab_bull['eleve'][$i]['resp'][0]['prenom'];
						//$tab_adr_ligne1[0].=" et ";
						$tab_adr_ligne1[0].="<br />\n";
						$tab_adr_ligne1[0].="et ";
						$tab_adr_ligne1[0].=$tab_bull['eleve'][$i]['resp'][1]['civilite']." ".$tab_bull['eleve'][$i]['resp'][1]['nom']." ".$tab_bull['eleve'][$i]['resp'][1]['prenom'];
						*/
					}
					else{
						if(($tab_bull['eleve'][$i]['resp'][0]['civilite']!="")&&($tab_bull['eleve'][$i]['resp'][1]['civilite']!="")) {
							$tab_adr_ligne1[0]=$tab_bull['eleve'][$i]['resp'][0]['civilite']." et ".$tab_bull['eleve'][$i]['resp'][1]['civilite']." ".$tab_bull['eleve'][$i]['resp'][0]['nom']." ".$tab_bull['eleve'][$i]['resp'][0]['prenom'];
						}
						else {
							$tab_adr_ligne1[0]="M. et Mme ".$tab_bull['eleve'][$i]['resp'][0]['nom']." ".$tab_bull['eleve'][$i]['resp'][0]['prenom'];
						}
					}

					$tab_adr_ligne2[0]=$tab_bull['eleve'][$i]['resp'][0]['adr1'];
					if($tab_bull['eleve'][$i]['resp'][0]['adr2']!=""){
						$tab_adr_ligne3[0]=$tab_bull['eleve'][$i]['resp'][0]['adr2'];
					}
					if($tab_bull['eleve'][$i]['resp'][0]['adr3']!=""){
						$tab_adr_ligne4[0]=$tab_bull['eleve'][$i]['resp'][0]['adr3'];
					}
					//if($tab_bull['eleve'][$i]['resp'][0]['adr4']!=""){
					//	$tab_adr_ligne2[0]=$tab_bull['eleve'][$i]['resp'][0]['adr4'];
					//}
					$tab_adr_ligne5[0]=$tab_bull['eleve'][$i]['resp'][0]['cp']." ".$tab_bull['eleve'][$i]['resp'][0]['commune'];


					if(($tab_bull['eleve'][$i]['resp'][0]['pays']!="")&&(strtolower($tab_bull['eleve'][$i]['resp'][0]['pays'])!=strtolower($gepiSchoolPays))) {
						$tab_adr_ligne6[0]=$tab_bull['eleve'][$i]['resp'][0]['pays'];
					}

				}
				else {
					// Les adresses sont diff�rentes
					//if ($un_seul_bull_par_famille!="oui") {
					// On teste en plus si la deuxi�me adresse est valide
					if (($un_seul_bull_par_famille!="oui")&&
						($tab_bull['eleve'][$i]['resp'][1]['adr1']!="")&&
						($tab_bull['eleve'][$i]['resp'][1]['commune']!="")
					) {
						$nb_bulletins=2;
					}
					else {
						$nb_bulletins=1;
					}

					for($cpt=0;$cpt<$nb_bulletins;$cpt++) {
						if($tab_bull['eleve'][$i]['resp'][$cpt]['civilite']!="") {
							$tab_adr_ligne1[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['civilite']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['nom']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['prenom'];
						}
						else {
							$tab_adr_ligne1[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['nom']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['prenom'];
						}

						$tab_adr_ligne2[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['adr1'];
						if($tab_bull['eleve'][$i]['resp'][$cpt]['adr2']!=""){
							$tab_adr_ligne3[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['adr2'];
						}
						if($tab_bull['eleve'][$i]['resp'][$cpt]['adr3']!=""){
							$tab_adr_ligne4[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['adr3'];
						}
						/*
						if($tab_bull['eleve'][$i]['resp'][$cpt]['adr4']!=""){
							$tab_adr_ligne2[$cpt].="<br />\n".$tab_bull['eleve'][$i]['resp'][$cpt]['adr4'];
						}
						*/
						$tab_adr_ligne5[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['cp']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['commune'];

						if(($tab_bull['eleve'][$i]['resp'][$cpt]['pays']!="")&&(strtolower($tab_bull['eleve'][$i]['resp'][$cpt]['pays'])!=strtolower($gepiSchoolPays))) {
							$tab_adr_ligne6[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['pays'];
						}
					}

				}
			}
			else {
				// Il n'y a pas de deuxi�me adresse, mais il y aurait un deuxi�me responsable???
				// CA NE DEVRAIT PAS ARRIVER ETANT DONN� LA REQUETE EFFECTUEE QUI JOINT resp_pers ET resp_adr...
				if ($un_seul_bull_par_famille!="oui") {
					$nb_bulletins=2;
				}
				else {
					$nb_bulletins=1;
				}

				for($cpt=0;$cpt<$nb_bulletins;$cpt++) {
					if($tab_bull['eleve'][$i]['resp'][$cpt]['civilite']!="") {
						$tab_adr_ligne1[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['civilite']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['nom']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['prenom'];
					}
					else {
						$tab_adr_ligne1[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['nom']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['prenom'];
					}

					$tab_adr_ligne2[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['adr1'];
					if($tab_bull['eleve'][$i]['resp'][$cpt]['adr2']!=""){
						$tab_adr_ligne3[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['adr2'];
					}
					if($tab_bull['eleve'][$i]['resp'][$cpt]['adr3']!=""){
						$tab_adr_ligne4[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['adr3'];
					}
					/*
					if($tab_bull['eleve'][$i]['resp'][$cpt]['adr4']!=""){
						$tab_adr_ligne2[$cpt].="<br />\n".$tab_bull['eleve'][$i]['resp'][$cpt]['adr4'];
					}
					*/
					$tab_adr_ligne5[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['cp']." ".$tab_bull['eleve'][$i]['resp'][$cpt]['commune'];

					if(($tab_bull['eleve'][$i]['resp'][$cpt]['pays']!="")&&(strtolower($tab_bull['eleve'][$i]['resp'][$cpt]['pays'])!=strtolower($gepiSchoolPays))) {
						$tab_adr_ligne6[$cpt]=$tab_bull['eleve'][$i]['resp'][$cpt]['pays'];
					}
				}
			}
		}
		else {
			// Il n'y a pas de deuxi�me responsable
			$nb_bulletins=1;

			if($tab_bull['eleve'][$i]['resp'][0]['civilite']!="") {
				$tab_adr_ligne1[0]=$tab_bull['eleve'][$i]['resp'][0]['civilite']." ".$tab_bull['eleve'][$i]['resp'][0]['nom']." ".$tab_bull['eleve'][$i]['resp'][0]['prenom'];
			}
			else {
				$tab_adr_ligne1[0]=$tab_bull['eleve'][$i]['resp'][0]['nom']." ".$tab_bull['eleve'][$i]['resp'][0]['prenom'];
			}

			$tab_adr_ligne2[0]=$tab_bull['eleve'][$i]['resp'][0]['adr1'];
			if($tab_bull['eleve'][$i]['resp'][0]['adr2']!=""){
				$tab_adr_ligne3[0]=$tab_bull['eleve'][$i]['resp'][0]['adr2'];
			}
			if($tab_bull['eleve'][$i]['resp'][0]['adr3']!=""){
				$tab_adr_ligne4[0]=$tab_bull['eleve'][$i]['resp'][0]['adr3'];
			}
			/*
			if($tab_bull['eleve'][$i]['resp'][0]['adr4']!=""){
				$tab_adr_ligne2[0].="<br />\n".$tab_bull['eleve'][$i]['resp'][0]['adr4'];
			}
			*/
			$tab_adr_ligne5[0]=$tab_bull['eleve'][$i]['resp'][0]['cp']." ".$tab_bull['eleve'][$i]['resp'][0]['commune'];

			if(($tab_bull['eleve'][$i]['resp'][0]['pays']!="")&&(strtolower($tab_bull['eleve'][$i]['resp'][0]['pays'])!=strtolower($gepiSchoolPays))) {
				$tab_adr_ligne6[0]=$tab_bull['eleve'][$i]['resp'][0]['pays'];
			}
		}
	}
	//=========================================

	//echo "\$i=$i et \$nb_bulletins=$nb_bulletins<br />";

	//+++++++++++++++++++++++++++++++++++++++++++
	// A FAIRE
	// Mettre ici une boucle pour $nb_bulletins
	// Et tenir compte par la suite de la demande d'intercaler le relev� de notes ou non
	//+++++++++++++++++++++++++++++++++++++++++++

	for($num_resp_bull=0;$num_resp_bull<$nb_bulletins;$num_resp_bull++) {
		$pdf->AddPage(); //ajout d'une page au document
		$pdf->SetFont('Arial');

		//================================
		// On ins�re le footer d�s que la page est cr��e:
		//Positionnement � 1 cm du bas et 0,5cm + 0,5cm du cot� gauche
		$pdf->SetXY(5,-10);
		//Police Arial Gras 6
		$pdf->SetFont('Arial','B',8);
		// $fomule = 'Bulletin � conserver pr�cieusement. Aucun duplicata ne sera d�livr�. - GEPI : solution libre de gestion et de suivi des r�sultats scolaires.'
		$pdf->Cell(0,4.5, traite_accents_utf8($bull_formule_bas),0,0,'C');
		//================================

		// A VERIFIER: CETTE VARIABLE NE DOIT PAS ETRE UTILE
		// SI LES VALEURS AFFICHEES PROVIENNENT DE L'EXTRACTION HORS DE LA FONCTION
		$total_coef_en_calcul=0;

		// quand on change d'�l�ve on vide les variables suivantes
		$categorie_passe = '';
		$total_moyenne_classe_en_calcul = 0;
		$total_moyenne_min_en_calcul = 0;
		$total_moyenne_max_en_calcul = 0;
		$total_coef_en_calcul = 0;

		// ...
		$hauteur_pris=0;


		//=========================================

		// R�cup�ration de l'identifiant de la classe:
		$classe_id=$tab_bull['eleve'][$i]['id_classe'];

		//=========================================

		if($tab_modele_pdf["affiche_filigrame"][$classe_id]==='1'){
			$pdf->SetFont('Arial','B',50);
			$pdf->SetTextColor(255,192,203);
			//$pdf->TextWithRotation(40,190,$texte_filigrame[$classe_id],45);
			$pdf->TextWithRotation(40,190,$tab_modele_pdf["texte_filigrame"][$classe_id],45);
			$pdf->SetTextColor(0,0,0);
		}

		//=========================================

		// ============= DEBUT BLOC ETABLISSEMENT ==========================

		// Bloc identification etablissement
		$logo = '../images/'.getSettingValue('logo_etab');
		$format_du_logo = strtolower(str_replace('.','',strstr(getSettingValue('logo_etab'), '.')));

		// Logo
		//if($tab_modele_pdf["affiche_logo_etab"][$classe_id]==='1' and file_exists($logo) and getSettingValue('logo_etab') != '' and ($format_du_logo==='jpg' or $format_du_logo==='png'))
		if($tab_modele_pdf["affiche_logo_etab"][$classe_id]==='1' and file_exists($logo) and getSettingValue('logo_etab') != '' and ($format_du_logo==='jpg' or $format_du_logo==='png'))
		{
			$valeur=redimensionne_image($logo, $tab_modele_pdf["L_max_logo"][$classe_id], $tab_modele_pdf["H_max_logo"][$classe_id]);
			$X_logo = 5;
			$Y_logo = 5;
			$L_logo = $valeur[0];
			$H_logo = $valeur[1];
			$X_etab = $X_logo + $L_logo + 1;
			$Y_etab = $Y_logo;

			if ( !isset($tab_modele_pdf["centrage_logo"][$classe_id]) or empty($tab_modele_pdf["centrage_logo"][$classe_id]) ) {
				$tab_modele_pdf["centrage_logo"][$classe_id] = '0';
			}

			if ( $tab_modele_pdf["centrage_logo"][$classe_id] === '1' ) {
				// centrage du logo
				$centre_du_logo = ( $H_logo / 2 );
				$Y_logo = $tab_modele_pdf["Y_centre_logo"][$classe_id] - $centre_du_logo;
			}

			//logo
			$pdf->Image($logo, $X_logo, $Y_logo, $L_logo, $H_logo);
		}

		//$pdf->SetXY(100,5);
		//$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
		//$pdf->Cell(90,7, "\$format_du_logo=$format_du_logo",0,2,'');

		//=========================================

		// Adresse �tablissement
		if ( !isset($X_etab) or empty($X_etab) ) {
			$X_etab = '5';
			$Y_etab = '5';
		}
		$pdf->SetXY($X_etab,$Y_etab);
		$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',14);

		//=========================
		// AJOUT: boireaus 20081224
		//        Ajout du test $tab_modele_pdf["affiche_nom_etab"][$classe_id] et $tab_modele_pdf["affiche_adresse_etab"][$classe_id]
		//=========================
		//$tab_modele_pdf["affiche_nom_etab"][$classe_id]=0;
		if(((isset($tab_modele_pdf["affiche_nom_etab"][$classe_id]))&&($tab_modele_pdf["affiche_nom_etab"][$classe_id]!="0"))||
			(!isset($tab_modele_pdf["affiche_nom_etab"][$classe_id]))) {
			// mettre en gras le nom de l'�tablissement si $nom_etab_gras = 1
			if ( $tab_modele_pdf["nom_etab_gras"][$classe_id] === '1' ) {
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',14);
			}
			$pdf->Cell(90,7, traite_accents_utf8($gepiSchoolName),0,2,'');
		}

		//$tab_modele_pdf["affiche_adresse_etab"][$classe_id]=0;
		if(((isset($tab_modele_pdf["affiche_adresse_etab"][$classe_id]))&&($tab_modele_pdf["affiche_adresse_etab"][$classe_id]!="0"))||
			(!isset($tab_modele_pdf["affiche_adresse_etab"][$classe_id]))) {
			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);

			if ( $gepiSchoolAdress1 != '' ) {
				$pdf->Cell(90,5, traite_accents_utf8($gepiSchoolAdress1),0,2,'');
			}
			if ( $gepiSchoolAdress2 != '' ) {
				$pdf->Cell(90,5, traite_accents_utf8($gepiSchoolAdress2),0,2,'');
			}

			$pdf->Cell(90,5, traite_accents_utf8($gepiSchoolZipCode." ".$gepiSchoolCity),0,2,'');
		}

		$passealaligne = '0';
		// ent�te t�l�phone
		// emplacement du cadre t�l�com
		$x_telecom = $pdf->GetX();
		$y_telecom = $pdf->GetY();

		// Affichage du tel de l'�tablissement
		if( $tab_modele_pdf["entente_tel"][$classe_id]==='1' ) {
			$grandeur = ''; 
			$text_tel = '';
			if ( $tab_modele_pdf["tel_image"][$classe_id] != '' ) {
				$a = $pdf->GetX();
				$b = $pdf->GetY();
				$ima = '../images/imabulle/'.$tab_modele_pdf["tel_image"][$classe_id].'.jpg';
				$valeurima=redimensionne_image($ima, 15, 15);
				$pdf->Image($ima, $a, $b, $valeurima[0], $valeurima[1]);
				$text_tel = '      '.$gepiSchoolTel;
				$grandeur = $pdf->GetStringWidth($text_tel);
				$grandeur = $grandeur + 2;
			}
			else {
				if ($tab_modele_pdf["tel_texte"][$classe_id]!= '') {
					$text_tel = $tab_modele_pdf["tel_texte"][$classe_id];
				}
				$text_tel .= $gepiSchoolTel;
				$grandeur = $pdf->GetStringWidth($text_tel);
			}
			/*
			if ( $tab_modele_pdf["tel_texte"][$classe_id] != '' and $tab_modele_pdf["tel_image"][$classe_id] === '' ) {
				$text_tel = $tab_modele_pdf["tel_texte"][$classe_id].''.$gepiSchoolTel;
				$grandeur = $pdf->GetStringWidth($text_tel);
			}
			*/

			$pdf->Cell($grandeur,5, $text_tel,0,$passealaligne,'');
		}

		$passealaligne = '2';
		// ent�te fax
		if( $tab_modele_pdf["entente_fax"][$classe_id]==='1' ) {
			$text_fax = '';
			if ( $tab_modele_pdf["fax_image"][$classe_id] != '' ) {
				$a = $pdf->GetX();
				$b = $pdf->GetY();
				$ima = '../images/imabulle/'.$tab_modele_pdf["fax_image"][$classe_id].'.jpg';
				$valeurima=redimensionne_image($ima, 15, 15);
				$pdf->Image($ima, $a, $b, $valeurima[0], $valeurima[1]);
				$text_fax = '      '.$gepiSchoolFax;
			}
			else {
				if ( $tab_modele_pdf["fax_texte"][$classe_id] != '' ) {
					$text_fax = $tab_modele_pdf["fax_texte"][$classe_id];
				}
				$text_fax .= $gepiSchoolFax;

			}
			/*
			if ( $tab_modele_pdf["fax_texte"][$classe_id] != '' and $tab_modele_pdf["fax_image"][$classe_id] === '' ) {
				$text_fax = $tab_modele_pdf["fax_texte"][$classe_id].''.$gepiSchoolFax;
			}
			*/

			if( $tab_modele_pdf["entente_tel"][$classe_id]==='1' ) {
				$text_fax=" ".$text_fax;
			}
			$pdf->Cell(90,5, $text_fax,0,$passealaligne,'');
		}

		if($tab_modele_pdf["entente_mel"][$classe_id]==='1') {
			$text_mel = '';
			$y_telecom = $y_telecom + 5;
			$pdf->SetXY($x_telecom,$y_telecom);

			//$text_mel = $gepiSchoolEmail;
			if ( $tab_modele_pdf["courrier_image"][$classe_id] != '' ) {
				$a = $pdf->GetX();
				$b = $pdf->GetY();
				$ima = '../images/imabulle/'.$tab_modele_pdf["courrier_image"][$classe_id].'.jpg';
				$valeurima=redimensionne_image($ima, 15, 15);
				$pdf->Image($ima, $a, $b, $valeurima[0], $valeurima[1]);
				$text_mel = '      '.$gepiSchoolEmail;
			}
			else {
				if ( $tab_modele_pdf["courrier_texte"][$classe_id] != '' ) {
					$text_mel = $tab_modele_pdf["courrier_texte"][$classe_id];
				}
				$text_mel .= $gepiSchoolEmail;
			}
			/*
			if ( $tab_modele_pdf["courrier_texte"][$classe_id] != '' and $tab_modele_pdf["courrier_image"][$classe_id] === '' ) {
				$text_mel = $tab_modele_pdf["courrier_texte"][$classe_id].' '.$gepiSchoolEmail;
			}
			*/
			$pdf->Cell(90,5, $text_mel,0,2,'');
		}

		// Lignes suppl�mentaires � prendre en compte...
		if(($tab_modele_pdf["entete_info_etab_suppl"][$classe_id]=='y')&&(($tab_modele_pdf["entete_info_etab_suppl_texte"][$classe_id]!='')||($tab_modele_pdf["entete_info_etab_suppl_valeur"][$classe_id]!=''))) {

			$y_telecom = $y_telecom + 5;
			$pdf->SetXY($x_telecom,$y_telecom);

			$texte = $tab_modele_pdf["entete_info_etab_suppl_texte"][$classe_id]." : ".$tab_modele_pdf["entete_info_etab_suppl_valeur"][$classe_id];

			$pdf->Cell(90,5, $texte,0,2,'');
		}

		// ============= FIN BLOC ETABLISSEMENT ==========================

		//=========================================

		// A VOIR: REMPLACER LE $i PAR AUTRE CHOSE POUR EVITER LA COLLISION AVEC L'INDICE $i pass� � la fonction
		//$i = $nb_eleve_aff;

		//$id_periode = $periode_classe[$id_classe_selection][$cpt_info_periode];
		$id_periode = $tab_bull['num_periode'];

		// AJOUT ERIC
		//$classe_id=$id_classe_selection;

		$pdf->SetFont('Arial','B',12);

		// gestion des styles
		$pdf->SetStyle2("b","arial","B",8,"0,0,0");
		$pdf->SetStyle2("i","arial","I",8,"0,0,0");
		$pdf->SetStyle2("u","arial","U",8,"0,0,0");

		// style pour la case appr�ciation g�n�rale
		// identit� du professeur principal
		if ( $tab_modele_pdf["taille_profprincipal_bloc_avis_conseil"][$classe_id] != '' and $tab_modele_pdf["taille_profprincipal_bloc_avis_conseil"][$classe_id] < '15' ) {
			$taille = $tab_modele_pdf["taille_profprincipal_bloc_avis_conseil"][$classe_id];
		} else {
			$taille = '10';
		}
		$pdf->SetStyle2("bppc","arial","B",$taille,"0,0,0");
		$pdf->SetStyle2("ippc","arial","I",$taille,"0,0,0");

		// ============= DEBUT BLOC ADRESSE PARENTS ==========================

		// bloc affichage de l'adresse des parents
		if($tab_modele_pdf["active_bloc_adresse_parent"][$classe_id]==='1') {
			$pdf->SetXY($tab_modele_pdf["X_parent"][$classe_id],$tab_modele_pdf["Y_parent"][$classe_id]);
			// d�finition des Largeur - hauteur
			if ( $tab_modele_pdf["largeur_bloc_adresse"][$classe_id] != '' and $tab_modele_pdf["largeur_bloc_adresse"][$classe_id] != '0' ) {
				$longeur_cadre_adresse = $tab_modele_pdf["largeur_bloc_adresse"][$classe_id];
			} else {
				$longeur_cadre_adresse = '90';
			}
			if ( $tab_modele_pdf["hauteur_bloc_adresse"][$classe_id] != '' and $tab_modele_pdf["hauteur_bloc_adresse"][$classe_id] != '0' ) {
				$hauteur_cadre_adresse = $tab_modele_pdf["hauteur_bloc_adresse"][$classe_id];
			} else {
				$hauteur_cadre_adresse = '1';
			}

			//=========================

			$texte_1_responsable = trim($tab_adr_ligne1[$num_resp_bull]);
			$hauteur_caractere=12;
			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',$hauteur_caractere);
			$val = $pdf->GetStringWidth($texte_1_responsable);
			$taille_texte = $longeur_cadre_adresse;
			$grandeur_texte='test';
			while($grandeur_texte != 'ok') {
				if($taille_texte < $val){
					$hauteur_caractere = $hauteur_caractere-0.3;
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',$hauteur_caractere);
					$val = $pdf->GetStringWidth($texte_1_responsable);
				} else {
					$grandeur_texte = 'ok';
				}
			}
			$pdf->Cell(90,7, traite_accents_utf8($texte_1_responsable),0,2,'');
			
			// ERIC
			if ($tab_modele_pdf["affiche_numero_responsable"][$classe_id] == '1') {
			    //Ajout Eric le 13-11-2010 Num du Resp l�gal sur le bulletin
				$pdf->SetXY($tab_modele_pdf["X_parent"][$classe_id]+90-8,$tab_modele_pdf["Y_parent"][$classe_id]-3);
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',6); //6==> hauteur de caract�re
				$num=$num_resp_bull+1; // on se base sur le nombre de bulletin � imprimer
				$num_legal= "(Resp ".$num.")";
				$pdf->Cell(90,7,$num_legal,0,2,'');
				// On remet le curseur � la bonne position pour la suite de l'adresse
				$pdf->SetXY($tab_modele_pdf["X_parent"][$classe_id],$tab_modele_pdf["Y_parent"][$classe_id]+7);
				// Fin modif Eric
            }   
				
			$texte_1_responsable = $tab_adr_ligne2[$num_resp_bull];
			$hauteur_caractere=10;
			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
			$val = $pdf->GetStringWidth($texte_1_responsable);
			$taille_texte = $longeur_cadre_adresse;
			$grandeur_texte='test';
			while($grandeur_texte!='ok') {
				if($taille_texte<$val){
					$hauteur_caractere = $hauteur_caractere-0.3;
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
					$val = $pdf->GetStringWidth($texte_1_responsable);
				} else {
					$grandeur_texte='ok';
				}
			}
			$pdf->Cell(90,5, traite_accents_utf8($texte_1_responsable),0,2,'');

			$texte_1_responsable = $tab_adr_ligne3[$num_resp_bull];
			$hauteur_caractere=10;
			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
			$val = $pdf->GetStringWidth($texte_1_responsable);
			$taille_texte = $longeur_cadre_adresse;
			$grandeur_texte='test';
			while($grandeur_texte!='ok') {
				if($taille_texte<$val){
					$hauteur_caractere = $hauteur_caractere-0.3;
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
					$val = $pdf->GetStringWidth($texte_1_responsable);
				} else {
					$grandeur_texte='ok';
				}
			}
			$pdf->Cell(90,5, traite_accents_utf8($texte_1_responsable),0,2,'');

			// Suppression du saut de ligne pour mettre la ligne 3 de l'adresse
			//$pdf->Cell(90,5, '',0,2,'');

			$texte_1_responsable = $tab_adr_ligne4[$num_resp_bull];
			$hauteur_caractere=10;
			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
			$val = $pdf->GetStringWidth($texte_1_responsable);
			$taille_texte = $longeur_cadre_adresse;
			$grandeur_texte='test';
			while($grandeur_texte!='ok') {
				if($taille_texte<$val){
					$hauteur_caractere = $hauteur_caractere-0.3;
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
					$val = $pdf->GetStringWidth($texte_1_responsable);
				} else {
					$grandeur_texte='ok';
				}
			}
			$pdf->Cell(90,5, traite_accents_utf8($texte_1_responsable),0,2,'');

			//$texte_1_responsable = $cp_parents[$ident_eleve_aff][$responsable_place]." ".$ville_parents[$ident_eleve_aff][$responsable_place];
			$texte_1_responsable = $tab_adr_ligne5[$num_resp_bull];
			$hauteur_caractere=10;
			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
			$val = $pdf->GetStringWidth($texte_1_responsable);
			$taille_texte = $longeur_cadre_adresse;
			$grandeur_texte='test';
			while($grandeur_texte!='ok') {
				if($taille_texte<$val){
					$hauteur_caractere = $hauteur_caractere-0.3;
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
					$val = $pdf->GetStringWidth($texte_1_responsable);
				} else {
					$grandeur_texte='ok';
				}
			}
			$pdf->Cell(90,5, traite_accents_utf8($texte_1_responsable),0,2,'');


			//============================
			//if((strtolower($gepiSchoolPays)!=strtolower($pays_parents[$ident_eleve_aff][$responsable_place]))&&($pays_parents[$ident_eleve_aff][$responsable_place]!="")) {
			if(isset($tab_adr_ligne6[$num_resp_bull])) {
				$texte_1_responsable = $tab_adr_ligne6[$num_resp_bull];
				$hauteur_caractere=10;
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
				$val = $pdf->GetStringWidth($texte_1_responsable);
				$taille_texte = $longeur_cadre_adresse;
				$grandeur_texte='test';
				while($grandeur_texte!='ok') {
					if($taille_texte<$val){
						$hauteur_caractere = $hauteur_caractere-0.3;
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
						$val = $pdf->GetStringWidth($texte_1_responsable);
					} else {
						$grandeur_texte='ok';
					}
				}
				$pdf->Cell(90,5, traite_accents_utf8($texte_1_responsable),0,2,'');
			}
			//============================

			$texte_1_responsable = '';
			if ( $tab_modele_pdf["cadre_adresse"][$classe_id] != 0 ) {
				if($hauteur_cadre_adresse=='1') {
					// Patch tout pourri... pour faire un encadrement � peu pr�s correct, m�me si la hauteur du cadre adresse parent n'a pas �t� saisie dans le mod�le
					$h_tmp=$pdf->GetY()-$tab_modele_pdf["Y_parent"][$classe_id]-2;

					$pdf->Rect($tab_modele_pdf["X_parent"][$classe_id], $tab_modele_pdf["Y_parent"][$classe_id], $longeur_cadre_adresse, $h_tmp, 'D');
				}
				else {
					$pdf->Rect($tab_modele_pdf["X_parent"][$classe_id], $tab_modele_pdf["Y_parent"][$classe_id], $longeur_cadre_adresse, $hauteur_cadre_adresse, 'D');
				}

				// Remarque: L'encadrement r�alis� ne tient pas compte du texte saisi.
				//           Si on met des valeurs trop r�duites, �a ne diminue pas la taille du texte de l'adresse
				//           On ne fait ici que mettre une d�co qui ne va pas n�cessairement co�ncider
				//           A REVOIR...
			}
		}

		// ============= FIN BLOC ADRESSE PARENTS ==========================

		//=========================================

		// ============= DEBUT BLOC ELEVE ==========================

		// Bloc affichage information sur l'�l�ve
		if($tab_modele_pdf["active_bloc_eleve"][$classe_id]==='1') {
			$pdf->SetXY($tab_modele_pdf["X_eleve"][$classe_id],$tab_modele_pdf["Y_eleve"][$classe_id]);
			// d�finition des Lageur - hauteur
			if ( $tab_modele_pdf["largeur_bloc_eleve"][$classe_id] != '' and $tab_modele_pdf["largeur_bloc_eleve"][$classe_id] != '0' ) {
				$longeur_cadre_eleve = $tab_modele_pdf["largeur_bloc_eleve"][$classe_id];
			} else {
				$longeur_cadre_eleve = $pdf->GetStringWidth($tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom']);
				$rajout_cadre_eleve = 100-$longeur_cadre_eleve;
				$longeur_cadre_eleve = $longeur_cadre_eleve + $rajout_cadre_eleve;
			}
			if ( $tab_modele_pdf["hauteur_bloc_eleve"][$classe_id] != '' and $tab_modele_pdf["hauteur_bloc_eleve"][$classe_id] != '0' ) {
				$hauteur_cadre_eleve = $tab_modele_pdf["hauteur_bloc_eleve"][$classe_id];
			} else {
				$nb_ligne = 5;
				$hauteur_ligne = 6;
				$hauteur_cadre_eleve = $nb_ligne*$hauteur_ligne;
			}

			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',14);

			if($tab_modele_pdf["cadre_eleve"][$classe_id]!=0) {
				$pdf->Rect($tab_modele_pdf["X_eleve"][$classe_id], $tab_modele_pdf["Y_eleve"][$classe_id], $longeur_cadre_eleve, $hauteur_cadre_eleve, 'D');
			}

			$X_eleve_2 = $tab_modele_pdf["X_eleve"][$classe_id]; $Y_eleve_2=$tab_modele_pdf["Y_eleve"][$classe_id];

			//photo de l'�l�ve
			if ( !isset($tab_modele_pdf["ajout_cadre_blanc_photo"][$classe_id]) or empty($tab_modele_pdf["ajout_cadre_blanc_photo"][$classe_id]) ) {
				$tab_modele_pdf["ajout_cadre_blanc_photo"][$classe_id] = '0';
			}
			if ( $tab_modele_pdf["ajout_cadre_blanc_photo"][$classe_id] === '1' ) {
				$ajouter = '1';
			} else {
				$ajouter = '0';
			}

			/*
			$photo[$i]="../photos/eleves/".$tab_bull['eleve'][$i]['elenoet'].".jpg";
			if(!file_exists($photo[$i])) {
				$photo[$i]="../photos/eleves/0".$tab_bull['eleve'][$i]['elenoet'].".jpg";
			}
			*/
			$photo[$i]=$rep_photos.$tab_bull['eleve'][$i]['elenoet'].".jpg";
			if(!file_exists($photo[$i])) {
				$photo[$i]=$rep_photos."0".$tab_bull['eleve'][$i]['elenoet'].".jpg";
			}


			if($tab_modele_pdf["active_photo"][$classe_id]==='1' and $photo[$i]!='' and file_exists($photo[$i])) {
				$L_photo_max = ($hauteur_cadre_eleve - ( $ajouter * 2 )) * 2.8;
				$H_photo_max = ($hauteur_cadre_eleve - ( $ajouter * 2 )) * 2.8;
				$valeur=redimensionne_image($photo[$i], $L_photo_max, $H_photo_max);
				$X_photo = $tab_modele_pdf["X_eleve"][$classe_id]+ 0.20 + $ajouter;
				$Y_photo = $tab_modele_pdf["Y_eleve"][$classe_id]+ 0.25 + $ajouter;
				$L_photo = $valeur[0];
				$H_photo = $valeur[1];
				$X_eleve_2 = $tab_modele_pdf["X_eleve"][$classe_id] + $L_photo + $ajouter + 1;
				$Y_eleve_2 = $Y_photo;
				$pdf->Image($photo[$i], $X_photo, $Y_photo, $L_photo, $H_photo);
				$longeur_cadre_eleve = $longeur_cadre_eleve - ( $valeur[0] + $ajouter );
			}


			$pdf->SetXY($X_eleve_2,$Y_eleve_2);

			//$pdf->Cell(90,7, traite_accents_utf8($tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom']),0,2,'');
			$nom_prenom=traite_accents_utf8($tab_bull['eleve'][$i]['nom']." ".$tab_bull['eleve'][$i]['prenom']);

			$hauteur_caractere_nom_prenom=14;
			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',$hauteur_caractere_nom_prenom);
			$val = $pdf->GetStringWidth($nom_prenom);
			if($tab_modele_pdf["active_photo"][$classe_id]==='1' and $photo[$i]!='' and file_exists($photo[$i])) {
				$taille_texte = 90-$L_photo; // Espace max
			}
			else {
				$taille_texte = 90; // Espace max
			}
			if($taille_texte<10) {$taille_texte=90;} // S�curit� pour ne pas risquer une boucle infinie
			$grandeur_texte='test';
			while($grandeur_texte!='ok') {
				if($taille_texte<$val) {
					$hauteur_caractere_nom_prenom=$hauteur_caractere_nom_prenom-0.3;
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',$hauteur_caractere_nom_prenom);
					$val = $pdf->GetStringWidth($nom_prenom);
				} else {
					$grandeur_texte='ok';
				}
			}
			$grandeur_texte='test';
			$pdf->Cell(90,7, $nom_prenom,0,2,'');


			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
			if($tab_modele_pdf["affiche_date_naissance"][$classe_id]==='1') {
				if($tab_bull['eleve'][$i]['naissance']!="") {
					$info_naissance="N�";
					if($tab_bull['eleve'][$i]['sexe']=="F") {$info_naissance.="e";}
					$info_naissance.=" le ".$tab_bull['eleve'][$i]['naissance'];

					if((getSettingValue('ele_lieu_naissance')=='y')&&($tab_modele_pdf["affiche_lieu_naissance"][$classe_id]==='1')) {
						$info_naissance.=" � ".$tab_bull['eleve'][$i]['lieu_naissance'];
					}

					$pdf->Cell(90,5, traite_accents_utf8($info_naissance),0,2,'');
				}
			}


			$rdbt = '';
			if($tab_modele_pdf["affiche_dp"][$classe_id]==='1') {
				if($tab_modele_pdf["affiche_doublement"][$classe_id]==='1') {
					//if($tab_bull['eleve'][$i]['doublant']!="") {
					if($tab_bull['eleve'][$i]['doublant']=="R") {
						//$rdbt=" ; ".$doublement[$i];
						//$rdbt=" ; redoublant";
						$rdbt="redoublant";
						if($tab_bull['eleve'][$i]['sexe']=="F") {
							$rdbt.="e";
						}
					}
					//if(isset($tab_bull['eleve'][$i]['regime'])) {
					if((isset($tab_bull['eleve'][$i]['regime']))&&($tab_bull['eleve'][$i]['regime']!="")) {
						if($rdbt=="") {
							$pdf->Cell(90,4, traite_accents_utf8(regime($tab_bull['eleve'][$i]['regime'])),0,2,'');
						}
						else {
							$pdf->Cell(90,4, traite_accents_utf8(regime($tab_bull['eleve'][$i]['regime'])."; ".$rdbt),0,2,'');
						}
					} else {
						$pdf->Cell(90,4,traite_accents_utf8($rdbt),0,2,'');
					}
				}
			} else {
				if($tab_modele_pdf["affiche_doublement"][$classe_id]==='1') {
					//if($tab_bull['eleve'][$i]['doublant']!="") {
					if($tab_bull['eleve'][$i]['doublant']=="R") {
						//$pdf->Cell(90,4.5, $doublement[$i],0,2,'');
						//$rdbt=" ; redoublant";
						$rdbt="redoublant";
						if($tab_bull['eleve'][$i]['sexe']=="F") {
							$rdbt.="e";
						}
						$pdf->Cell(90,4.5, traite_accents_utf8($rdbt),0,2,'');
					}
				}
			}

			// affiche le nom court de la classe
			if ( $tab_modele_pdf["affiche_nom_court"][$classe_id] === '1' )
			{
				if($tab_bull['eleve'][$i]['classe']!="")
				{
					// si l'affichage du num�ro INE est activ� alors on ne passe pas
					$passe_a_la_ligne = 0;
					//if ( $tab_modele_pdf["affiche_ine"][$classe_id] != '1' or $tab_modele_pdf["INE_eleve"][$i] == '' )
					if ( $tab_modele_pdf["affiche_ine"][$classe_id] != '1' or $tab_bull['eleve'][$i]['no_gep'] == '' )
					{
						$passe_a_la_ligne = 1;
					}
					$pdf->Cell(45,4.5, traite_accents_utf8(unhtmlentities($tab_bull['eleve'][$i]['classe'])),0, $passe_a_la_ligne,'');
				}
			}

			// affiche l'INE de l'�l�ve
			if ( $tab_modele_pdf["affiche_ine"][$classe_id] === '1' )
			{
				if ( $tab_bull['eleve'][$i]['no_gep'] != '' )
				{
					$pdf->Cell(45,4.5, 'INE: '.$tab_bull['eleve'][$i]['no_gep'], 0, 1,'');
				}
			}

			// Affichage du num�ro d'impression
			$pdf->SetX($X_eleve_2);

			if($tab_modele_pdf["affiche_effectif_classe"][$classe_id]==='1') {
				if($tab_modele_pdf["affiche_numero_impression"][$classe_id]==='1') {
					$pass_ligne = '0';
				} else {
					$pass_ligne = '2';
				}
				if($tab_bull['eff_classe']!="") {
					$pdf->Cell(45,4.5, traite_accents_utf8('Effectif : '.$tab_bull['eff_classe'].' �l�ves'),0,$pass_ligne,'');
				}
			}
			if($tab_modele_pdf["affiche_numero_impression"][$classe_id]==='1') {
				//+++++++++++++++++++
				//+++++++++++++++++++
				// A VOIR... CE $i...
				// Si on n'imprime que certains bulletins, on r�cup�re le num�ro d'ordre (alphab�tique) de l'�l�ve dans la classe.
				//+++++++++++++++++++
				//+++++++++++++++++++
				//$num_ordre = $i;
				$num_ordre = $i+1;
				$pdf->Cell(45,4, 'Bulletin N� '.$num_ordre,0,2,'');
			}

			// Affichage de l'�tablissement d'origine
			// On n'affiche pas l'�tablissement d'origine si c'est le m�me que l'�tablissement actuel: $RneEtablissement
			//if($tab_modele_pdf["affiche_etab_origine"][$classe_id]==='1' and !empty($etablissement_origine[$i]) ) {
			//if($tab_modele_pdf["affiche_etab_origine"][$classe_id]==='1' and isset($tab_bull['eleve'][$i]['etab_id']) and !empty($tab_bull['eleve'][$i]['etab_id']) ) {
			if(($tab_modele_pdf["affiche_etab_origine"][$classe_id]==='1')&&(isset($tab_bull['eleve'][$i]['etab_id']))&&(!empty($tab_bull['eleve'][$i]['etab_id']))&&(strtolower($tab_bull['eleve'][$i]['etab_id'])!=strtolower($RneEtablissement))) {
				$pdf->SetX($X_eleve_2);
				$hauteur_caractere_etaborigine = '10';
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_etaborigine);
				//$val = $pdf->GetStringWidth('Etab. Origine : '.$tab_bull['eleve'][$i]['etab_niveau']." ".$tab_bull['eleve'][$i]['etab_nom']." (".$tab_bull['eleve'][$i]['etab_ville'].")");

				$chaine_etab_origine='Etab. Origine : '.$tab_bull['eleve'][$i]['etab_niveau_nom']." ".$tab_bull['eleve'][$i]['etab_nom']." (".$tab_bull['eleve'][$i]['etab_ville'].")";
				//$chaine_etab_origine='Etab. Origine : '.$tab_bull['eleve'][$i]['etab_niveau_nom']." ".$tab_bull['eleve'][$i]['etab_type'].." ".$tab_bull['eleve'][$i]['etab_nom']." (".$tab_bull['eleve'][$i]['etab_ville'].")";
				$val = $pdf->GetStringWidth($chaine_etab_origine);

				$taille_texte = $longeur_cadre_eleve-3;
				$grandeur_texte='test';
				while($grandeur_texte!='ok') {
					if($taille_texte<$val) {
						$hauteur_caractere_etaborigine = $hauteur_caractere_etaborigine-0.3;
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_etaborigine);
						$val = $pdf->GetStringWidth($chaine_etab_origine);
					} else {
						$grandeur_texte='ok';
					}
				}
				$grandeur_texte='test';
				$pdf->Cell(90,4, traite_accents_utf8($chaine_etab_origine),0,2);
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
			}
		} // fin du bloc affichage information sur l'�l�ves

		// ============= FIN BLOC ELEVE ==========================

		//=========================================

		// ============= DEBUT BLOC CLASSE/PERIODE/DATATION ==========================

		// Bloc affichage datation du bulletin:
		// Classe, p�riode,...
		if($tab_modele_pdf["active_bloc_datation"][$classe_id]==='1') {
			$pdf->SetXY($tab_modele_pdf["X_datation_bul"][$classe_id], $tab_modele_pdf["Y_datation_bul"][$classe_id]);

			// d�finition des Largeur - hauteur
			if ( $tab_modele_pdf["largeur_bloc_datation"][$classe_id] != '' and $tab_modele_pdf["largeur_bloc_datation"][$classe_id] != '0' ) {
				$longeur_cadre_datation_bul = $tab_modele_pdf["largeur_bloc_datation"][$classe_id];
			} else {
				$longeur_cadre_datation_bul = '95';
			}
			if ( $tab_modele_pdf["hauteur_bloc_datation"][$classe_id] != '' and $tab_modele_pdf["hauteur_bloc_datation"][$classe_id] != '0' ) {
				$hauteur_cadre_datation_bul = $tab_modele_pdf["hauteur_bloc_datation"][$classe_id];
			} else {
				$nb_ligne_datation_bul = 3;
				$hauteur_ligne_datation_bul = 6;
				$hauteur_cadre_datation_bul = $nb_ligne_datation_bul*$hauteur_ligne_datation_bul;
			}

			if($tab_modele_pdf["cadre_datation_bul"][$classe_id]!=0) {
				$pdf->Rect($tab_modele_pdf["X_datation_bul"][$classe_id], $tab_modele_pdf["Y_datation_bul"][$classe_id], $longeur_cadre_datation_bul, $hauteur_cadre_datation_bul, 'D');
			}
			$taille_texte = '14'; $type_texte = 'B';
			if ( $tab_modele_pdf["taille_texte_classe"][$classe_id] != '' and $tab_modele_pdf["taille_texte_classe"][$classe_id] != '0' ) {
				$taille_texte = $tab_modele_pdf["taille_texte_classe"][$classe_id];
			} else {
				$taille_texte = '14';
			}
			if ( $tab_modele_pdf["type_texte_classe"][$classe_id] != '' ) {
				if ( $tab_modele_pdf["type_texte_classe"][$classe_id] === 'N' ) {
					$type_texte = '';
				} else {
					$type_texte = $tab_modele_pdf["type_texte_classe"][$classe_id];
				}
			} else {
				$type_texte = 'B';
			}
			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id], $type_texte, $taille_texte);
			$pdf->Cell(90,7, traite_accents_utf8("Classe de ".unhtmlentities($tab_bull['classe_nom_complet'])),0,2,'C');
			$taille_texte = '12'; $type_texte = '';
			if ( $tab_modele_pdf["taille_texte_annee"][$classe_id] != '' and $tab_modele_pdf["taille_texte_annee"][$classe_id] != '0') {
				$taille_texte = $tab_modele_pdf["taille_texte_annee"][$classe_id];
			} else {
				$taille_texte = '12';
			}

			if ( $tab_modele_pdf["type_texte_annee"][$classe_id] != '' ) {
				if ( $tab_modele_pdf["type_texte_annee"][$classe_id] === 'N' ) {
					$type_texte = '';
				}
				else {
					$type_texte = $tab_modele_pdf["type_texte_annee"][$classe_id];
				}
			}
			else {
				$type_texte = '';
			}
			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id], $type_texte, $taille_texte);
			$annee_scolaire = $gepiYear;
			$pdf->Cell(90,5, traite_accents_utf8("Ann�e scolaire ".$annee_scolaire),0,2,'C');
			$taille_texte = '10'; $type_texte = '';
			if ( $tab_modele_pdf["taille_texte_periode"][$classe_id] != '' and $tab_modele_pdf["taille_texte_periode"][$classe_id] != '0' ) {
				$taille_texte = $tab_modele_pdf["taille_texte_periode"][$classe_id];
			}
			else {
				$taille_texte = '10';
			}
			if ( $tab_modele_pdf["type_texte_periode"][$classe_id] != '' ) {
				if ( $tab_modele_pdf["type_texte_periode"][$classe_id] === 'N' ) {
					$type_texte = '';
				}
				else {
					$type_texte = $tab_modele_pdf["type_texte_periode"][$classe_id];
				}
			}
			else {
				$type_texte = '';
			}
			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id], $type_texte, $taille_texte);
			$pdf->Cell(90,5, traite_accents_utf8("Bulletin du ".unhtmlentities($tab_bull['nom_periode'])),0,2,'C');
			$taille_texte = '8';
			$type_texte = '';

			if ( $tab_modele_pdf["affiche_date_edition"][$classe_id] === '1' ) {
				if ( $tab_modele_pdf["taille_texte_date_edition"][$classe_id] != '' and $tab_modele_pdf["taille_texte_date_edition"][$classe_id] != '0' ) {
					$taille_texte = $tab_modele_pdf["taille_texte_date_edition"][$classe_id];
				}
				else {
					$taille_texte = '8';
				}
				if ( $tab_modele_pdf["type_texte_date_datation"][$classe_id] != '' ) {
					if ( $tab_modele_pdf["type_texte_date_datation"][$classe_id] === 'N' ) {
						$type_texte = '';
					}
					else {
						$type_texte = $tab_modele_pdf["type_texte_date_datation"][$classe_id];
					}
				}
				else {
					$type_texte = '';
				}
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id], $type_texte, $taille_texte);
				$pdf->Cell(95,7, traite_accents_utf8($date_bulletin),0,2,'R');
			}

			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
		}

		// ============= FIN BLOC CLASSE/PERIODE/DATATION ==========================

		//=========================================

		// ============= DEBUT BLOC NOTES ET APPRECIATIONS ==========================

		// Bloc notes et appr�ciations
		//nombre de matieres � afficher
		//$nb_matiere = $info_bulletin[$ident_eleve_aff][$id_periode]['nb_matiere'];
		$nb_matiere=0;
		//$fich=fopen("/tmp/infos_matieres_eleve.txt","a+");
		//fwrite($fich,"\$tab_bull['eleve'][$i]['nom']=".$tab_bull['eleve'][$i]['nom']."\n");
		for($j=0;$j<count($tab_bull['groupe']);$j++) {
			if(isset($tab_bull['note'][$j][$i])) {
				// Si l'�l�ve suit l'option, sa note est affect�e (�ventuellement vide)
				//fwrite($fich,"\$tab_bull['groupe'][$j]['matiere']['matiere']=".$tab_bull['groupe'][$j]['matiere']['matiere']." ");
				//fwrite($fich,"\$tab_bull['note'][$j][$i]=".$tab_bull['note'][$j][$i]."\n");
				$nb_matiere++;


				// Si les cat�gories doivent �tre affich�es, il faut r�ordonner les mati�res pour ne pas avoir:
				//     LIT
				//     SCI
				//     AUT
				//     LIT � nouveau
				// Sinon, les calculs de hauteur sont fauss�s et �a ne permet pas d'avoir des ordres tr�s diff�rents avec et sans cat�gories
				// Il vaudrait mieux refaire ce tri au d�part, avant d'�tre dans les boucles �l�ves


			}
		}

		if(isset($tab_bull['eleve'][$i]['aid_b'])) {
			$nb_matiere+=count($tab_bull['eleve'][$i]['aid_b']);
		}

		if(isset($tab_bull['eleve'][$i]['aid_e'])) {
			$nb_matiere+=count($tab_bull['eleve'][$i]['aid_e']);
		}

		//fclose($fich);

		/*
		//++++++++++++++++
		// Pour debug:
		$pdf->SetXY(100, 25);
		$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
		$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], 8, "nb_matiere=$nb_matiere",1,0,'C');
		//++++++++++++++++
		*/

		if($tab_modele_pdf["active_bloc_note_appreciation"][$classe_id]==='1' and $nb_matiere!='0') {
			$pdf->Rect($tab_modele_pdf["X_note_app"][$classe_id], $tab_modele_pdf["Y_note_app"][$classe_id], $tab_modele_pdf["longeur_note_app"][$classe_id], $tab_modele_pdf["hauteur_note_app"][$classe_id], 'D');
			//ent�te du tableau des notes et app
			$nb_entete_moyenne = $tab_modele_pdf["active_moyenne_eleve"][$classe_id]+$tab_modele_pdf["active_moyenne_classe"][$classe_id]+$tab_modele_pdf["active_moyenne_min"][$classe_id]+$tab_modele_pdf["active_moyenne_max"][$classe_id]; //min max classe eleve
			$hauteur_entete = 8;
			$hauteur_entete_pardeux = $hauteur_entete/2;
			$pdf->SetXY($tab_modele_pdf["X_note_app"][$classe_id], $tab_modele_pdf["Y_note_app"][$classe_id]);
			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
			$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $hauteur_entete, traite_accents_utf8($tab_modele_pdf["titre_entete_matiere"][$classe_id]),1,0,'C');
			$largeur_utilise = $tab_modele_pdf["largeur_matiere"][$classe_id];

			// coefficient mati�re
			if($tab_modele_pdf["active_coef_moyenne"][$classe_id]==='1') {
				$pdf->SetXY($tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise, $tab_modele_pdf["Y_note_app"][$classe_id]);
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
				$pdf->Cell($tab_modele_pdf["largeur_coef_moyenne"][$classe_id], $hauteur_entete, traite_accents_utf8($tab_modele_pdf["titre_entete_coef"][$classe_id]),'LRB',0,'C');
				$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_coef_moyenne"][$classe_id];
			}

			// nombre de notes
			// 20081118
			//if($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1') {
			if(($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1')&&($tab_modele_pdf["active_nombre_note"][$classe_id]!='1')) {
				$pdf->SetXY($tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise, $tab_modele_pdf["Y_note_app"][$classe_id]);
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
				$pdf->Cell($tab_modele_pdf["largeur_nombre_note"][$classe_id], $hauteur_entete, traite_accents_utf8($tab_modele_pdf["titre_entete_nbnote"][$classe_id]),'LRB',0,'C');
				$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_nombre_note"][$classe_id];
			}

			// eleve | min | classe | max | rang | niveau | appreciation |
			if ( $tab_modele_pdf["ordre_entete_model_bulletin"][$classe_id] === '1' ) {
				$ordre_moyenne[0] = 'eleve';
				$ordre_moyenne[1] = 'min';
				$ordre_moyenne[2] = 'classe';
				$ordre_moyenne[3] = 'max';
				$ordre_moyenne[4] = 'rang';
				$ordre_moyenne[5] = 'niveau';
				$ordre_moyenne[6] = 'appreciation';
			}

			// min | classe | max | eleve | niveau | rang | appreciation |
			if ( $tab_modele_pdf["ordre_entete_model_bulletin"][$classe_id] === '2' ) {
				$ordre_moyenne[0] = 'min';
				$ordre_moyenne[1] = 'classe';
				$ordre_moyenne[2] = 'max';
				$ordre_moyenne[3] = 'eleve';
				$ordre_moyenne[4] = 'niveau';
				$ordre_moyenne[5] = 'rang';
				$ordre_moyenne[6] = 'appreciation';
			}

			// eleve | niveau | rang | appreciation | min | classe | max
			if ( $tab_modele_pdf["ordre_entete_model_bulletin"][$classe_id] === '3' ) {
				$ordre_moyenne[0] = 'eleve';
				$ordre_moyenne[1] = 'niveau';
				$ordre_moyenne[2] = 'rang';
				$ordre_moyenne[3] = 'appreciation';
				$ordre_moyenne[4] = 'min';
				$ordre_moyenne[5] = 'classe';
				$ordre_moyenne[6] = 'max';
			}

			// eleve | classe | min | max | rang | niveau | appreciation |
			if ( $tab_modele_pdf["ordre_entete_model_bulletin"][$classe_id] === '4' ) {
				$ordre_moyenne[0] = 'eleve';
				$ordre_moyenne[1] = 'classe';
				$ordre_moyenne[2] = 'min';
				$ordre_moyenne[3] = 'max';
				$ordre_moyenne[4] = 'rang';
				$ordre_moyenne[5] = 'niveau';
				$ordre_moyenne[6] = 'appreciation';
			}

			// eleve | min | classe | max | niveau | rang | appreciation |
			if ( $tab_modele_pdf["ordre_entete_model_bulletin"][$classe_id] === '5' ) {
				$ordre_moyenne[0] = 'eleve';
				$ordre_moyenne[1] = 'min';
				$ordre_moyenne[2] = 'classe';
				$ordre_moyenne[3] = 'max';
				$ordre_moyenne[4] = 'niveau';
				$ordre_moyenne[5] = 'rang';
				$ordre_moyenne[6] = 'appreciation';
			}

			// min | classe | max | eleve | rang | niveau | appreciation |
			//if ( $ordre_entete_model_bulletin[$classe_id] === '6' ) {
			if ( $tab_modele_pdf["ordre_entete_model_bulletin"][$classe_id] === '6' ) {
				$ordre_moyenne[0] = 'min';
				$ordre_moyenne[1] = 'classe';
				$ordre_moyenne[2] = 'max';
				$ordre_moyenne[3] = 'eleve';
				$ordre_moyenne[4] = 'rang';
				$ordre_moyenne[5] = 'niveau';
				$ordre_moyenne[6] = 'appreciation';
			}


			$cpt_ordre = 0;
			$chapeau_moyenne = 'non';
			while ( !empty($ordre_moyenne[$cpt_ordre]) ) {

				// Je ne saisis pas pourquoi cette variable est initialis�e � ce niveau???
				//$categorie_passe_count = 0;

				// le chapeau des moyennes
				$ajout_espace_au_dessus = 4;
				if ( $tab_modele_pdf["entete_model_bulletin"][$classe_id] === '1' and $nb_entete_moyenne > 1 and ( $ordre_moyenne[$cpt_ordre] === 'classe' or $ordre_moyenne[$cpt_ordre] === 'min' or $ordre_moyenne[$cpt_ordre] === 'max' or $ordre_moyenne[$cpt_ordre] === 'eleve' ) and $chapeau_moyenne === 'non' and $tab_modele_pdf["ordre_entete_model_bulletin"][$classe_id] != '3' )
				{
					$largeur_moyenne = $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id] * $nb_entete_moyenne;
					$text_entete_moyenne = 'Moyenne';
					$pdf->SetXY($tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise, $tab_modele_pdf["Y_note_app"][$classe_id]);
					$pdf->Cell($largeur_moyenne, $hauteur_entete_pardeux, traite_accents_utf8($text_entete_moyenne),1,0,'C');
					$chapeau_moyenne = 'oui';
				}

				//if ( ($tab_modele_pdf["entete_model_bulletin"][$classe_id] === '2' and $nb_entete_moyenne > 1 and ( $ordre_moyenne[$cpt_ordre] === 'classe' or $ordre_moyenne[$cpt_ordre] === 'min' or $ordre_moyenne[$cpt_ordre] === 'max' ) and $chapeau_moyenne === 'non' ) or ( $tab_modele_pdf["entete_model_bulletin"][$classe_id] === '1' and $tab_modele_pdf["ordre_entete_model_bulletin"][$classe_id] === '3' and $chapeau_moyenne === 'non' and ( $ordre_moyenne[$cpt_ordre] === 'classe' or $ordre_moyenne[$cpt_ordre] === 'min' or $ordre_moyenne[$cpt_ordre] === 'max' )  ) )
				if (($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]!=1)&&
					( ($tab_modele_pdf["entete_model_bulletin"][$classe_id] === '2' and $nb_entete_moyenne > 1 and ( $ordre_moyenne[$cpt_ordre] === 'classe' or $ordre_moyenne[$cpt_ordre] === 'min' or $ordre_moyenne[$cpt_ordre] === 'max' ) and $chapeau_moyenne === 'non' ) or ( $tab_modele_pdf["entete_model_bulletin"][$classe_id] === '1' and $tab_modele_pdf["ordre_entete_model_bulletin"][$classe_id] === '3' and $chapeau_moyenne === 'non' and ( $ordre_moyenne[$cpt_ordre] === 'classe' or $ordre_moyenne[$cpt_ordre] === 'min' or $ordre_moyenne[$cpt_ordre] === 'max' )  ) )
				) {
					$largeur_moyenne = $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id] * ( $nb_entete_moyenne - 1 );
					$text_entete_moyenne = 'Pour la classe';
					$pdf->SetXY($tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise, $tab_modele_pdf["Y_note_app"][$classe_id]);
					$hauteur_caractere=10;
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
					$val = $pdf->GetStringWidth($text_entete_moyenne);
					$taille_texte = $largeur_moyenne;
					$grandeur_texte='test';
					while($grandeur_texte!='ok') {
						if($taille_texte<$val)
						{
							$hauteur_caractere = $hauteur_caractere-0.3;
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
							$val = $pdf->GetStringWidth($text_entete_moyenne);
						}
						else {
							$grandeur_texte='ok';
						}
					}
					$pdf->Cell($largeur_moyenne, $hauteur_entete_pardeux, traite_accents_utf8($text_entete_moyenne),1,0,'C');
					$chapeau_moyenne = 'oui';
				}

				//eleve
				if($tab_modele_pdf["active_moyenne_eleve"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'eleve' ) {
					$ajout_espace_au_dessus = 4;
					$hauteur_de_la_cellule = $hauteur_entete_pardeux;
					if ( $tab_modele_pdf["entete_model_bulletin"][$classe_id] === '2' and $tab_modele_pdf["active_moyenne_eleve"][$classe_id] === '1' and $nb_entete_moyenne > 1 )
					{
						$hauteur_de_la_cellule = $hauteur_entete;
						$ajout_espace_au_dessus = 0;
					}
					$pdf->SetXY($tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise, $tab_modele_pdf["Y_note_app"][$classe_id]+$ajout_espace_au_dessus);
					$pdf->SetFillColor($tab_modele_pdf["couleur_reperage_eleve1"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve2"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve3"][$classe_id]);
					$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $hauteur_de_la_cellule, traite_accents_utf8("El�ve"),1,0,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
					$pdf->SetFillColor(0, 0, 0);
					$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
				}

				//classe
				//if($tab_modele_pdf["active_moyenne_classe"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'classe' ) {
				if (($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]!=1)&&($tab_modele_pdf["active_moyenne_classe"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'classe' )) {
					$pdf->SetXY($tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise, $tab_modele_pdf["Y_note_app"][$classe_id]+4);
					$hauteur_caractere = '8.5';

					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
					$text_moy_classe = 'Classe';
					if ( $tab_modele_pdf["entete_model_bulletin"][$classe_id] === '2' ) {
						$text_moy_classe = 'Moy.';
					}
					$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $hauteur_entete_pardeux, traite_accents_utf8($text_moy_classe),1,0,'C');
					$X_moyenne_classe = $tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise;
					$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
				}
				//min
				//if($tab_modele_pdf["active_moyenne_min"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'min' ) {
				if(($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]!=1)&&($tab_modele_pdf["active_moyenne_min"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'min' )) {

					$pdf->SetXY($tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise, $tab_modele_pdf["Y_note_app"][$classe_id]+4);
					$hauteur_caractere = '8.5';
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
					$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $hauteur_entete_pardeux, "Min.",1,0,'C');
					$X_min_classe = $tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise;
					$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
				}
				//max
				//if($tab_modele_pdf["active_moyenne_max"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'max' ) {
				if(($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]!=1)&&($tab_modele_pdf["active_moyenne_max"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'max' )) {
					$pdf->SetXY($tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise, $tab_modele_pdf["Y_note_app"][$classe_id]+4);
					$hauteur_caractere = '8.5';
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
					$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $hauteur_entete_pardeux, "Max.",1,0,'C');
					$X_max_classe = $tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise;
					$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
				}

				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);

				// rang de l'�l�ve
				if( $tab_modele_pdf["active_rang"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'rang' ) {
					$pdf->SetXY($tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise, $tab_modele_pdf["Y_note_app"][$classe_id]);
					$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $hauteur_entete, traite_accents_utf8($tab_modele_pdf["titre_entete_rang"][$classe_id]),'LRB',0,'C');
					//$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $hauteur_entete, $tab_modele_pdf["titre_entete_rang"][$classe_id],'LRB',0,'C');
					$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_rang"][$classe_id];
				}

				// graphique de niveau
				if( $tab_modele_pdf["active_graphique_niveau"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'niveau' ) {
					$pdf->SetXY($tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise, $tab_modele_pdf["Y_note_app"][$classe_id]);
					$hauteur_caractere = '10';
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
					$pdf->Cell($tab_modele_pdf["largeur_niveau"][$classe_id], $hauteur_entete_pardeux, "Niveau",'LR',0,'C');
					$pdf->SetXY($tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise, $tab_modele_pdf["Y_note_app"][$classe_id]+4);
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
					$pdf->Cell($tab_modele_pdf["largeur_niveau"][$classe_id], $hauteur_entete_pardeux, "ABC+C-DE",'LRB',0,'C');
					$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_niveau"][$classe_id];
				}

				//appreciation
				$hauteur_caractere = '10';
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere);
				if($tab_modele_pdf["active_appreciation"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'appreciation' ) {
					$pdf->SetXY($tab_modele_pdf["X_note_app"][$classe_id]+$largeur_utilise, $tab_modele_pdf["Y_note_app"][$classe_id]);
					if ( !empty($ordre_moyenne[$cpt_ordre+1]) ) {
						$cpt_ordre_sous = $cpt_ordre + 1;
						$largeur_appret = 0;
						while ( !empty($ordre_moyenne[$cpt_ordre_sous]) ) {
							if ( $ordre_moyenne[$cpt_ordre_sous] === 'eleve' ) { $largeur_appret = $largeur_appret + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id]; }
							if ( $ordre_moyenne[$cpt_ordre_sous] === 'rang' ) { $largeur_appret = $largeur_appret + $tab_modele_pdf["largeur_rang"][$classe_id]; }
							if ( $ordre_moyenne[$cpt_ordre_sous] === 'niveau' ) { $largeur_appret = $largeur_appret + $tab_modele_pdf["largeur_niveau"][$classe_id]; }
							if ( $ordre_moyenne[$cpt_ordre_sous] === 'min' ) { $largeur_appret = $largeur_appret + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id]; }
							if ( $ordre_moyenne[$cpt_ordre_sous] === 'classe' ) { $largeur_appret = $largeur_appret + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id]; }
							if ( $ordre_moyenne[$cpt_ordre_sous] === 'max' ) { $largeur_appret = $largeur_appret + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id]; }
							$cpt_ordre_sous = $cpt_ordre_sous + 1;
						}
						$largeur_appreciation = $tab_modele_pdf["longeur_note_app"][$classe_id] - $largeur_utilise - $largeur_appret;
					} else {
						$largeur_appreciation = $tab_modele_pdf["longeur_note_app"][$classe_id]-$largeur_utilise;
					}
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);

					//$titre_entete_appreciation=$bull_intitule_app;
					$titre_entete_appreciation=$tab_modele_pdf['titre_entete_appreciation'][$classe_id];

					$pdf->Cell($largeur_appreciation, $hauteur_entete, traite_accents_utf8($titre_entete_appreciation),'LRB',0,'C');
					$largeur_utilise = $largeur_utilise + $largeur_appreciation;
				}
				$cpt_ordre = $cpt_ordre + 1;
			}
			$largeur_utilise = 0;
			// fin de boucle d'ordre

			//===============================================

			//emplacement des blocs mati�re et note et appr�ciation

			//si cat�gorie activ� il faut compter le nombre de cat�gories
			$nb_categories_select=0;
			//$categorie_passe_for='';

			if($tab_modele_pdf["active_regroupement_cote"][$classe_id]==='1' or $tab_modele_pdf["active_entete_regroupement"][$classe_id]==='1') {
				/* A CONVERTIR AVEC $tab_bull
				for($x=0;$x<$nb_matiere;$x++) {
					if($matiere[$ident_eleve_aff][$id_periode][$x]['categorie']!=$categorie_passe_for) {
						$nb_categories_select=$nb_categories_select+1;
					}
					$categorie_passe_for=$matiere[$ident_eleve_aff][$id_periode][$x]['categorie'];
				}
				*/
				//$nb_categories_select=count($tab_bull['cat_id']);
				$nb_categories_select=count(array_count_values($tab_bull['cat_id']));

				// Il faut revoir le compte des cat�gories pour ne retenir que celles de l'�l�ve
				// celles dans lesquelles l'�l�ve est inscrit � une des mati�res
				/*
				$nb_categories_select=0;
				for($m=0; $m<count($tab_bull['groupe']); $m++) {
					// Si c'est une mati�re suivie par l'�l�ve
					if(isset($tab_bull['note'][$m][$i])) {
						$nb_categories_select++;
					}
				}
				*/
			}

			fich_debug_bull("======================\n");
			fich_debug_bull("El�ve ".$tab_bull['eleve'][$i]['login']."\n");
			fich_debug_bull("\$nb_categories_select=$nb_categories_select\n");

			//$nb_matiere=count($tab_bull['eleve'][$i][]);
			/*
			$nb_matiere=0;
			for($j=0;$j<count($tab_bull['groupe']);$j++) {
				if(isset($tab_bull['note'][$j][$i])) {
					// Si l'�l�ve suit l'option, sa note est affect�e (�ventuellement vide)
					$nb_matiere++;
				}
			}
			*/

			$X_bloc_matiere=$tab_modele_pdf["X_note_app"][$classe_id]; $Y_bloc_matiere=$tab_modele_pdf["Y_note_app"][$classe_id]+$hauteur_entete;
			$longeur_bloc_matiere=$tab_modele_pdf["longeur_note_app"][$classe_id];
			// calcul de la hauteur totale que peut prendre le cadre mati�re dans sa globalit�
			if ( $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne_general"][$classe_id] === '1' )
			{
				// si les moyennes et la moyenne g�n�rale sont activ�es alors on les ajoute � ce qu'il faudra soustraire au cadre global matiere
				$hauteur_toute_entete = $hauteur_entete + $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id];

				if($affiche_deux_moy_gen==1) {
					$hauteur_toute_entete+=$tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id];
				}
			}
			else {
				$hauteur_toute_entete = $hauteur_entete;
			}

			$hauteur_bloc_matiere=$tab_modele_pdf["hauteur_note_app"][$classe_id]-$hauteur_toute_entete;
			$X_note_moy_app = $tab_modele_pdf["X_note_app"][$classe_id];
			$Y_note_moy_app = $tab_modele_pdf["Y_note_app"][$classe_id]+$tab_modele_pdf["hauteur_note_app"][$classe_id]-$hauteur_entete;

			if($tab_modele_pdf["active_entete_regroupement"][$classe_id]==='1') {
				$espace_entre_matier = ($hauteur_bloc_matiere-($nb_categories_select*5))/$nb_matiere;
			}
			else {
				$espace_entre_matier = $hauteur_bloc_matiere/$nb_matiere;
			}

			fich_debug_bull("\$hauteur_bloc_matiere=$hauteur_bloc_matiere\n");
			fich_debug_bull("\$nb_matiere=$nb_matiere\n");
			fich_debug_bull("\$espace_entre_matier=$espace_entre_matier\n");

			/*
			//++++++++++++++++
			// Pour debug:
			$pdf->SetXY(100, 30);
			$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
			$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], 8, "espace_entre_matier=$espace_entre_matier",1,0,'C');
			//++++++++++++++++
			*/

			$pdf->SetXY($X_bloc_matiere, $Y_bloc_matiere);
			$Y_decal = $Y_bloc_matiere;

			fich_debug_bull("\$Y_decal=$Y_decal\n");


			//======================================================
			// DEBUT DES AID AFFICH�S AVANT LES MATIERES
			if(isset($tab_bull['eleve'][$i]['aid_b'])) {
				//echo "count(\$tab_bull['eleve'][$i]['aid_b']=".count($tab_bull['eleve'][$i]['aid_b'])."<br />";
				for($m=0;$m<count($tab_bull['eleve'][$i]['aid_b']);$m++) {
					$pdf->SetXY($X_bloc_matiere, $Y_decal);

					// Si c'est une mati�re suivie par l'�l�ve
					//if(isset($tab_bull['eleve'][$i]['note'][$m][$i])) {

						// calcul la taille du titre de la mati�re
						$hauteur_caractere_matiere=10;
						if ( $tab_modele_pdf["taille_texte_matiere"][$classe_id] != '' and $tab_modele_pdf["taille_texte_matiere"][$classe_id] != '0' and $tab_modele_pdf["taille_texte_matiere"][$classe_id] < '11' )
						{
							$hauteur_caractere_matiere = $tab_modele_pdf["taille_texte_matiere"][$classe_id];
						}
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',$hauteur_caractere_matiere);

						// Pour parer au bug sur la suppression de mati�re alors que des groupes sont conserv�s:
						if(isset($tab_bull['eleve'][$i]['aid_b'][$m]['nom_complet'])) {
							$info_nom_matiere=$tab_bull['eleve'][$i]['aid_b'][$m]['nom_complet'];
						}
						else {
							$info_nom_matiere=$tab_bull['eleve'][$i]['aid_b'][$m]['nom'];
						}

						$val = $pdf->GetStringWidth($info_nom_matiere);
						$taille_texte = $tab_modele_pdf["largeur_matiere"][$classe_id] - 2;
						$grandeur_texte='test';
						while($grandeur_texte!='ok') {
							if($taille_texte<$val)
							{
								$hauteur_caractere_matiere = $hauteur_caractere_matiere-0.3;
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',$hauteur_caractere_matiere);
								$val = $pdf->GetStringWidth($info_nom_matiere);
							}
							else {
								$grandeur_texte='ok';
							}
						}
						$grandeur_texte='test';
						$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $espace_entre_matier/2, traite_accents_utf8($info_nom_matiere),'LR',1,'L');
						$Y_decal = $Y_decal+($espace_entre_matier/2);
						$pdf->SetXY($X_bloc_matiere, $Y_decal);
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);

						fich_debug_bull("\$info_nom_matiere=$info_nom_matiere\n");
						fich_debug_bull("\$Y_decal=$Y_decal\n");

						// nom des professeurs

						if ( isset($tab_bull['eleve'][$i]['aid_b'][$m]['aid_prof_resp_login'][0]) )
						{

							$nb_prof_matiere = count($tab_bull['eleve'][$i]['aid_b'][$m]['aid_prof_resp_login']);
							$espace_matiere_prof = $espace_entre_matier/2;
							if($nb_prof_matiere>0){
								$espace_matiere_prof = $espace_matiere_prof/$nb_prof_matiere;
							}
							$nb_pass_count = '0';
							$text_prof = '';
							while ($nb_prof_matiere > $nb_pass_count)
							{
								$tmp_login_prof=$tab_bull['eleve'][$i]['aid_b'][$m]['aid_prof_resp_login'][$nb_pass_count];
								$text_prof=affiche_utilisateur($tmp_login_prof,$tab_bull['eleve'][$i]['id_classe']);

								if ( $nb_prof_matiere <= 2 ) { $hauteur_caractere_prof = 8; }
								elseif ( $nb_prof_matiere == 3) { $hauteur_caractere_prof = 5; }
								elseif ( $nb_prof_matiere > 3) { $hauteur_caractere_prof = 2; }
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_prof);
								$val = $pdf->GetStringWidth($text_prof);
								$taille_texte = ($tab_modele_pdf["largeur_matiere"][$classe_id]);
								$grandeur_texte='test';
								while($grandeur_texte!='ok') {
									if($taille_texte<$val)
									{
										$hauteur_caractere_prof = $hauteur_caractere_prof-0.3;
										$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_prof);
										$val = $pdf->GetStringWidth($text_prof);
									}
									else {
										$grandeur_texte='ok';
									}
								}
								$grandeur_texte='test';
								$pdf->SetX($X_bloc_matiere);
								if( empty($tab_bull['eleve'][$i]['aid_b'][$m]['aid_prof_resp_login'][$nb_pass_count+1]) ) {
									$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $espace_matiere_prof, traite_accents_utf8($text_prof),'LRB',1,'L');
								}
								if( !empty($tab_bull['eleve'][$i]['aid_b'][$m]['aid_prof_resp_login'][$nb_pass_count+1]) ) {
									$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $espace_matiere_prof, traite_accents_utf8($text_prof),'LR',1,'L');
								}
								$nb_pass_count = $nb_pass_count + 1;
							}
						}
						$largeur_utilise = $tab_modele_pdf["largeur_matiere"][$classe_id];

						// coefficient mati�re
						if($tab_modele_pdf["active_coef_moyenne"][$classe_id]==='1') {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
							//$pdf->Cell($tab_modele_pdf["largeur_coef_moyenne"][$classe_id], $espace_entre_matier, $tab_bull['eleve'][$i]['coef_eleve'][$i][$m],1,0,'C');
							$pdf->Cell($tab_modele_pdf["largeur_coef_moyenne"][$classe_id], $espace_entre_matier, '',1,0,'C');
							$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_coef_moyenne"][$classe_id];
						}

						//permet le calcul total des coefficients
						// if(empty($moyenne_min[$id_classe][$id_periode])) {
							//$total_coef_en_calcul=$total_coef_en_calcul+$tab_bull['eleve'][$i]['coef_eleve'][$i][$m];
						//}

						// nombre de note
						// 20081118
						//if($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1') {
						if(($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1')&&($tab_modele_pdf["active_nombre_note"][$classe_id]!='1')) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
							//$valeur = $tab_bull['eleve'][$i]['nbct'][$m][$i] . "/" . $tab_bull['eleve'][$i]['groupe'][$m]['nbct'];
							$valeur = '';
							$pdf->Cell($tab_modele_pdf["largeur_nombre_note"][$classe_id], $espace_entre_matier, $valeur,1,0,'C');
							$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_nombre_note"][$classe_id];
						}

						// les moyennes eleve, classe, min, max
						$cpt_ordre = 0;
						while (!empty($ordre_moyenne[$cpt_ordre]) ) {
							//eleve
							if($tab_modele_pdf["active_moyenne_eleve"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'eleve' ) {
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',10);
								$pdf->SetFillColor($tab_modele_pdf["couleur_reperage_eleve1"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve2"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve3"][$classe_id]);

								// calcul nombre de sous affichage

								$nb_sousaffichage='1';
								//20090908 if(empty($active_coef_sousmoyene)) { $active_coef_sousmoyene = ''; }

								//if($active_coef_sousmoyene==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; }
								if($tab_modele_pdf["active_coef_sousmoyene"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; }
								if($tab_modele_pdf["active_nombre_note"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; }
								if($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]==='1') { if($tab_modele_pdf["active_moyenne_classe"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; } }
								if($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]==='1') { if($tab_modele_pdf["active_moyenne_min"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; } }
								if($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]==='1') { if($tab_modele_pdf["active_moyenne_max"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; } }

								// On filtre si la moyenne est vide, on affiche seulement un tiret
								if ($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note']=="-") {
									$valeur = "-";
								}
								elseif ($tab_bull['eleve'][$i]['aid_b'][$m]['aid_statut']!="") {
									if($tab_bull['eleve'][$i]['aid_b'][$m]['aid_statut']=="other") {
										$valeur = "-";
									}
									else {
										$valeur=$tab_bull['eleve'][$i]['aid_b'][$m]['aid_statut'];
									}
								}
								else {
									$valeur = present_nombre($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
								}
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, $valeur,1,2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
								$valeur = "";

								if($tab_modele_pdf["active_coef_sousmoyene"][$classe_id]==='1') {
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',7);
									//$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'coef. '.$tab_bull['eleve'][$i]['coef_eleve'][$i][$m],'LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, '','LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
								}

								if($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]==='1') {
									// On affiche toutes les moyennes dans la m�me colonne
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',7);
									if($tab_modele_pdf["active_moyenne_classe"][$classe_id]==='1') {
										//if ($tab_bull['eleve'][$i]['moy_classe_grp'][$m]=="-") {
										if (($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_moyenne']=="-")||($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_moyenne']=="")) {
											$valeur = "-";
										}
										else {
											$valeur = present_nombre($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_moyenne'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
										}
										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'cla.'.$valeur,'LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									}
									if($tab_modele_pdf["active_moyenne_min"][$classe_id]==='1') {
										//if ($tab_bull['eleve'][$i]['moy_min_classe_grp'][$m]=="-") {
										if (($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_min']=="-")||($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_min']=="")) {
											$valeur = "-";
										} else {
											$valeur = present_nombre($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_min'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
										}
										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'min.'.$valeur,'LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									}
									if($tab_modele_pdf["active_moyenne_max"][$classe_id]==='1') {
										//if ($tab_bull['eleve'][$i]['moy_max_classe_grp'][$m]=="-") {
										if (($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_max']=="-")||($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_max']=="")) {
											$valeur = "-";
										} else {
											$valeur = present_nombre($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_max'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
										}
										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'max.'.$valeur,'LRD',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
										$valeur = ''; // on remet � vide.
									}
								}

								if($tab_modele_pdf["active_nombre_note"][$classe_id]==='1') {
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',7);
									$espace_pour_nb_note = $espace_entre_matier/$nb_sousaffichage;
									$espace_pour_nb_note = $espace_pour_nb_note / 2;
									$valeur1 = ''; $valeur2 = '';
									/*
									if ($tab_bull['eleve'][$i]['nbct'][$m][$i]!= 0 ) {
										$valeur1 = $tab_bull['eleve'][$i]['nbct'][$m][$i].' note';
										if($tab_bull['eleve'][$i]['nbct'][$m][$i]>1){$valeur1.='s';}
										$valeur2 = 'sur '.$tab_bull['eleve'][$i]['groupe'][$m]['nbct'];
									}
									*/
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_pour_nb_note, $valeur1, 'LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_pour_nb_note, $valeur2, 'LRB',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									$valeur1 = ''; $valeur2 = '';
								}
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
								$pdf->SetFillColor(0, 0, 0);
								$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];

							} // Fin affichage �l�ve

							//classe
							//if( $tab_modele_pdf["active_moyenne_classe"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'classe' ) {
							if(($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]!=1)&&( $tab_modele_pdf["active_moyenne_classe"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'classe' )) {
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								//if ($tab_bull['eleve'][$i]['moy_classe_grp'][$m]=="-") {
								if (($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_moyenne']=="-")||($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_moyenne']=="")) {
									$valeur = "-";
								} else {
									$valeur = present_nombre($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_moyenne'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
								}
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier, $valeur,'TLRB',0,'C');
								$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
							}
							//min
							//if( $tab_modele_pdf["active_moyenne_min"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'min' ) {
							if(($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]!=1)&&( $tab_modele_pdf["active_moyenne_min"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'min' )) {
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
								//if ($tab_bull['eleve'][$i]['moy_min_classe_grp'][$m]=="-") {
								if (($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_min']=="-")||($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_min']=="")) {
									$valeur = "-";
								} else {
									$valeur = present_nombre($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_min'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
								}
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier, $valeur,'TLRB',0,'C');
								$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
							}
							//max
							//if( $tab_modele_pdf["active_moyenne_max"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'max' ) {
							if(($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]!=1)&&( $tab_modele_pdf["active_moyenne_max"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'max' )) {
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								//if ($tab_bull['eleve'][$i]['moy_max_classe_grp'][$m]== "-") {
								if (($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_max']=="-")||($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_max']=="")) {
									$valeur = "-";
								} else {
									$valeur = present_nombre($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note_max'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
								}
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier, $valeur,'TLRB',0,'C');
								$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
							}
							//$largeur_utilise = $largeur_utilise+$largeur_moyenne;


							// rang de l'�l�ve
							if($tab_modele_pdf["active_rang"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'rang' ) {
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
								// A REVOIR: J'AI l'EFFECTIF DU GROUPE, mais faut-il compter les �l�ves ABS, DISP,...?
								//if((isset($tab_bull['eleve'][$i]['rang'][$i][$m]))&&(isset($tab_bull['eleve'][$i]['groupe'][$m]['effectif']))) {
									//$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $espace_entre_matier, $tab_bull['eleve'][$i]['rang'][$i][$m].'/'.$tab_bull['eleve'][$i]['groupe'][$m]['effectif'],1,0,'C');

								//if((isset($tab_bull['eleve'][$i]['rang'][$m][$i]))&&(isset($tab_bull['eleve'][$i]['groupe'][$m]['effectif']))) {
								//	$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $espace_entre_matier, $tab_bull['eleve'][$i]['rang'][$m][$i].'/'.$tab_bull['eleve'][$i]['groupe'][$m]['effectif_avec_note'],1,0,'C');
								//}
								//else {
									$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $espace_entre_matier, '',1,0,'C');
								//}
								$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_rang"][$classe_id];
							}

							// graphique de niveau
							if($tab_modele_pdf["active_graphique_niveau"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'niveau' ) {
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
								//$id_groupe_graph = $tab_bull['eleve'][$i]['groupe'][$m]['id'];
								// placement de l'�l�ve dans le graphique de niveau

								// AJOUT: La variable n'�tait pas initialis�e dans le bulletin_pdf_avec_modele...
								$place_eleve='';

								if ($tab_bull['eleve'][$i]['aid_b'][$m]['aid_note']!="") {
									/*
									if ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<5) { $place_eleve=5;}
									if (($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=5) and ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<8))  { $place_eleve=4;}
									if (($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=8) and ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<10)) { $place_eleve=3;}
									if (($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=10) and ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<12)) {$place_eleve=2;}
									if (($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=12) and ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<15)) { $place_eleve=1;}
									if ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=15) { $place_eleve=0;}
									*/
									if(isset($tab_bull['eleve'][$i]['aid_b'][$m]['place_eleve'])) {
										//$place_eleve=$tab_bull['eleve'][$i]['aid_b'][$m]['place_eleve'];
										$place_eleve=$tab_bull['eleve'][$i]['aid_b'][$m]['place_eleve']-1;
									}
								}
								$data_grap=array();
								if(isset($tab_bull['eleve'][$i]['aid_b'][$m]['quartile1_classe'])) {$data_grap[0]=$tab_bull['eleve'][$i]['aid_b'][$m]['quartile1_classe'];}
								if(isset($tab_bull['eleve'][$i]['aid_b'][$m]['quartile2_classe'])) {$data_grap[1]=$tab_bull['eleve'][$i]['aid_b'][$m]['quartile2_classe'];}
								if(isset($tab_bull['eleve'][$i]['aid_b'][$m]['quartile3_classe'])) {$data_grap[2]=$tab_bull['eleve'][$i]['aid_b'][$m]['quartile3_classe'];}
								if(isset($tab_bull['eleve'][$i]['aid_b'][$m]['quartile4_classe'])) {$data_grap[3]=$tab_bull['eleve'][$i]['aid_b'][$m]['quartile4_classe'];}
								if(isset($tab_bull['eleve'][$i]['aid_b'][$m]['quartile5_classe'])) {$data_grap[4]=$tab_bull['eleve'][$i]['aid_b'][$m]['quartile5_classe'];}
								if(isset($tab_bull['eleve'][$i]['aid_b'][$m]['quartile6_classe'])) {$data_grap[5]=$tab_bull['eleve'][$i]['aid_b'][$m]['quartile6_classe'];}
								//if (array_sum($data_grap[$id_periode][$id_groupe_graph]) != 0) {
								if (array_sum($data_grap) != 0) {
									//$pdf->DiagBarre($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2), $tab_modele_pdf["largeur_niveau"][$classe_id], $espace_entre_matier, $data_grap[$id_periode][$id_groupe_graph], $place_eleve);
									$pdf->DiagBarre($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2), $tab_modele_pdf["largeur_niveau"][$classe_id], $espace_entre_matier, $data_grap, $place_eleve);
								}
								$place_eleve=''; // on vide la variable
								$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_niveau"][$classe_id];
							}

							//appr�ciation
							if($tab_modele_pdf["active_appreciation"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'appreciation' ) {
								// si on autorise l'affichage des sous mati�re et s'il y en a alors on les affiche
								//$id_groupe_select = $tab_bull['eleve'][$i]['groupe'][$m]['id'];
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								$X_sous_matiere = 0;
								$largeur_sous_matiere=0;

								/*
								if($tab_modele_pdf["autorise_sous_matiere"][$classe_id]==='1' and !empty($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom'])) {
									$X_sous_matiere = $X_note_moy_app+$largeur_utilise;
									$Y_sous_matiere = $Y_decal-($espace_entre_matier/2);
									$n=0;
									$largeur_texte_sousmatiere=0; $largeur_sous_matiere=0;
									while( !empty($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom'][$n]) )
									{
										$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
										$largeur_texte_sousmatiere = $pdf->GetStringWidth($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom'][$n].': '.$tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_note'][$n]);
										if($largeur_sous_matiere<$largeur_texte_sousmatiere) { $largeur_sous_matiere=$largeur_texte_sousmatiere; }
										$n = $n + 1;
									}
									if($largeur_sous_matiere!='0') { $largeur_sous_matiere = $largeur_sous_matiere + 2; }
									$n=0;
									while( !empty($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom'][$n]) )
									{
										$pdf->SetXY($X_sous_matiere, $Y_sous_matiere);
										$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
										$pdf->Cell($largeur_sous_matiere, $espace_entre_matier/count($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom']), traite_accents_utf8($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom'][$n].': '.$tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_note'][$n]),1,0,'L');
										$Y_sous_matiere = $Y_sous_matiere+$espace_entre_matier/count($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom']);
										$n = $n + 1;
									}
									$largeur_utilise = $largeur_utilise+$largeur_sous_matiere;
								}
								*/
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								// calcul de la taille du texte des appr�ciation
								$hauteur_caractere_appreciation = 9;
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_appreciation);

								//suppression des espace en d�but et en fin
								//$app_aff = trim($tab_bull['eleve'][$i]['aid_b'][$m]['aid_appreciation']);
								$app_aff="";
								if($tab_bull['eleve'][$i]['aid_b'][$m]['message']!='') {
									$app_aff.=$tab_bull['eleve'][$i]['aid_b'][$m]['message'];
								}
								//if($app_aff!='') {$app_aff.=" ";}
								if(($tab_bull['eleve'][$i]['aid_b'][$m]['display_nom']=='y')&&($tab_bull['eleve'][$i]['aid_b'][$m]['aid_nom']!='')) {
									if($app_aff!='') {$app_aff.=" ";}
									$app_aff.=$tab_bull['eleve'][$i]['aid_b'][$m]['aid_nom'];
								}
								if($app_aff!='') {$app_aff.="\n";}
								$app_aff.=trim($tab_bull['eleve'][$i]['aid_b'][$m]['aid_appreciation']);

								fich_debug_bull("__________________________________________\n");
								fich_debug_bull("$app_aff\n");
								fich_debug_bull("__________________________________________\n");

								// DEBUT AJUSTEMENT TAILLE APPRECIATION
								$taille_texte_total = $pdf->GetStringWidth($app_aff);
								$largeur_appreciation2 = $largeur_appreciation - $largeur_sous_matiere;

								if($use_cell_ajustee=="n") {
									//$taille_texte = (($espace_entre_matier/3)*$largeur_appreciation2);
									$nb_ligne_app = '2.8';
									//$nb_ligne_app = '3.8';
									//$nb_ligne_app = '4.8';
									$taille_texte_max = $nb_ligne_app * ($largeur_appreciation2-4);
									//$taille_texte_max = $nb_ligne_app * ($largeur_appreciation2);
									$grandeur_texte='test';
	
									fich_debug_bull("\$taille_texte_total=$taille_texte_total\n");
									fich_debug_bull("\$largeur_appreciation2=$largeur_appreciation2\n");
									fich_debug_bull("\$nb_ligne_app=$nb_ligne_app\n");
									//fich_debug_bull("\$taille_texte_max = \$nb_ligne_app * (\$largeur_appreciation2-4)=$nb_ligne_app * ($largeur_appreciation2-4)=$taille_texte_max\n");
									fich_debug_bull("\$taille_texte_max = \$nb_ligne_app * (\$largeur_appreciation2)=$nb_ligne_app * ($largeur_appreciation2)=$taille_texte_max\n");
	
									while($grandeur_texte!='ok') {
										if($taille_texte_max < $taille_texte_total)
										{
											$hauteur_caractere_appreciation = $hauteur_caractere_appreciation-0.3;
											//$hauteur_caractere_appreciation = $hauteur_caractere_appreciation-0.1;
											$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_appreciation);
											$taille_texte_total = $pdf->GetStringWidth($app_aff);
										}
										else {
											$grandeur_texte='ok';
										}
									}
									$grandeur_texte='test';
									$pdf->drawTextBox(traite_accents_utf8($app_aff), $largeur_appreciation2, $espace_entre_matier, 'J', 'M', 1);
								}
								else {
									//$texte="Bla bla\nbli ".$app_aff;
									$texte=$app_aff;
									$taille_max_police=$hauteur_caractere_appreciation;
									$taille_min_police=ceil($taille_max_police/3);

									$largeur_dispo=$largeur_appreciation2;
									$h_cell=$espace_entre_matier;

									if(getSettingValue('suppr_balises_app_prof')=='y') {$texte=preg_replace('/<(.*)>/U','',$texte);}
									cell_ajustee(traite_accents_utf8($texte),$pdf->GetX(),$pdf->GetY(),$largeur_dispo,$h_cell,$taille_max_police,$taille_min_police,'LRBT');
								}

								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
								$largeur_utilise = $largeur_utilise + $largeur_appreciation2;
								//$largeur_utilise = 0;
							}

							$cpt_ordre = $cpt_ordre + 1;
						}
						$largeur_utilise = 0;
						// fin de boucle d'ordre
						$Y_decal = $Y_decal+($espace_entre_matier/2);
					//}
				}
			}
			// FIN DES AID AFFICH�S AVANT LES MATIERES
			//======================================================


			// Compteur du nombre de mati�res dans la cat�gorie
			$categorie_passe_count=0;

			//for($m=0; $m<$nb_matiere; $m++)
			for($m=0; $m<count($tab_bull['groupe']); $m++)
			{
				$pdf->SetXY($X_bloc_matiere, $Y_decal);

				fich_debug_bull("\n");
				fich_debug_bull("Cat�gorie pr�c�dente: \$categorie_passe=$categorie_passe\n");
				//fich_debug_bull("Cat�gorie courante :  \$tab_bull['nom_cat_complet'][$m]=".$tab_bull['nom_cat_complet'][$m]."\n");
				if(isset($tab_bull['nom_cat_complet'][$m])) {
                  fich_debug_bull("Cat�gorie courante :  \$tab_bull['nom_cat_complet'][$m]=".$tab_bull['nom_cat_complet'][$m]."\n");
                }
                else {
                  fich_debug_bull("Cat�gorie courante :  \$tab_bull['nom_cat_complet'][$m] non affect�e.\n");
                }
				fich_debug_bull("\$tab_bull['groupe'][$m]['matiere']['matiere']=".$tab_bull['groupe'][$m]['matiere']['matiere']."\n");
				fich_debug_bull("\$X_bloc_matiere=$X_bloc_matiere\n");
				fich_debug_bull("\$Y_decal=$Y_decal\n");

				// si on affiche les cat�gories
				if($tab_modele_pdf["active_entete_regroupement"][$classe_id]==='1') {
					//si on affiche les moyennes des cat�gories
					//if($matiere[$ident_eleve_aff][$id_periode][$m]['categorie']!=$categorie_passe)
					//if($tab_bull['cat_id'][$m]!=$categorie_passe)
					if($tab_bull['nom_cat_complet'][$m]!=$categorie_passe)
					{
						$hauteur_caractere_catego = '10';
						if ( $tab_modele_pdf["taille_texte_categorie"][$classe_id] != '' and $tab_modele_pdf["taille_texte_categorie"][$classe_id] != '0' ) {
							$hauteur_caractere_catego = $tab_modele_pdf["taille_texte_categorie"][$classe_id];
						}
						else {
							$hauteur_caractere_catego = '10';
						}
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_catego);
						$tt_catego = unhtmlentities($tab_bull['nom_cat_complet'][$m]);
						$val = $pdf->GetStringWidth($tt_catego);
						$taille_texte = ($tab_modele_pdf["largeur_matiere"][$classe_id]);
						$grandeur_texte='test';
						while($grandeur_texte!='ok') {
							if($taille_texte<$val)
							{
								$hauteur_caractere_catego = $hauteur_caractere_catego-0.3;
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_catego);
								$val = $pdf->GetStringWidth($tt_catego);
							}
							else {
								$grandeur_texte='ok';
							}
						}
						$grandeur_texte='test';
						$pdf->SetFillColor($tab_modele_pdf["couleur_categorie_entete1"][$classe_id], $tab_modele_pdf["couleur_categorie_entete2"][$classe_id], $tab_modele_pdf["couleur_categorie_entete3"][$classe_id]);

						fich_debug_bull("On �crit $tt_catego � \$Y_decal=$Y_decal\n");

						$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], traite_accents_utf8(unhtmlentities($tt_catego)),'TLB',0,'L',$tab_modele_pdf["couleur_categorie_entete"][$classe_id]);
						$largeur_utilise = $tab_modele_pdf["largeur_matiere"][$classe_id];

						// coefficient mati�re
						if($tab_modele_pdf["active_coef_moyenne"][$classe_id]==='1') {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal);
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
							$pdf->Cell($tab_modele_pdf["largeur_coef_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], '','T',0,'C',$tab_modele_pdf["couleur_categorie_entete"][$classe_id]);
							$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_coef_moyenne"][$classe_id];
						}

						// nombre de notes
						// 20081118
						//if($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1') {
						if(($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1')&&($tab_modele_pdf["active_nombre_note"][$classe_id]!='1')) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal);
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
							$pdf->Cell($tab_modele_pdf["largeur_nombre_note"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], '','T',0,'C',$tab_modele_pdf["couleur_categorie_entete"][$classe_id]);
							$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_nombre_note"][$classe_id];
						}
						$pdf->SetFillColor(0, 0, 0);

						// les moyennes eleve, classe, min, max par cat�gorie
						$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal);

						$cpt_ordre = 0;
						$chapeau_moyenne = 'non';
						while ( !empty($ordre_moyenne[$cpt_ordre]) ) {
							// Moyenne de l'�l�ve dans la cat�gorie
							if($tab_modele_pdf["active_moyenne_eleve"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'eleve' ) {
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal);
								if($tab_modele_pdf["active_moyenne_regroupement"][$classe_id]==='1') {
									//$categorie_passage=$matiere[$ident_eleve_aff][$id_periode][$m]['categorie'];
									$categorie_passage=$tab_bull['nom_cat_complet'][$m];
									//if($matiere[$ident_eleve_aff][$id_periode][$m]['affiche_moyenne']==='1')
									if(isset($tab_bull['moy_cat_eleve'][$i][$tab_bull['cat_id'][$m]]))
									{
										// On va afficher la moyenne de l'�l�ve pour la cat�gorie
										if (($tab_bull['moy_cat_eleve'][$i][$tab_bull['cat_id'][$m]]=="")||($tab_bull['moy_cat_eleve'][$i][$tab_bull['cat_id'][$m]]=="-")) {
											$valeur = "-";
										} else {
											//$calcule_moyenne_eleve_categorie[$categorie_passage]=$matiere[$ident_eleve_aff][$id_periode][$categorie_passage]['moy_eleve']/$matiere[$ident_eleve_aff][$id_periode][$categorie_passage]['coef_tt_catego'];
											$valeur = present_nombre(preg_replace("/,/",".",$tab_bull['moy_cat_eleve'][$i][$tab_bull['cat_id'][$m]]), $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
											//$valeur =$tab_bull['moy_cat_eleve'][$i][$tab_bull['cat_id'][$m]];
										}
										$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',8);
										$pdf->SetFillColor($tab_modele_pdf["couleur_reperage_eleve1"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve2"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve3"][$classe_id]);
										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id],$valeur,1,0,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
										$pdf->SetFillColor(0, 0, 0);
										$valeur = "";
									} else {
										$pdf->SetFillColor(255, 255, 255);
										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], '','TL',0,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									}
								} else {
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], '','T',0,'C');
								}

								$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
							}

							// Moyenne de la classe dans la cat�gorie
							//if($tab_modele_pdf["active_moyenne_classe"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'classe' ) {
							if(($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]!=1)&&($tab_modele_pdf["active_moyenne_classe"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'classe' )) {
								$pdf->SetXY($X_moyenne_classe, $Y_decal);
								if($tab_modele_pdf["active_moyenne_regroupement"][$classe_id]==='1') {
									//$categorie_passage=$matiere[$ident_eleve_aff][$id_periode][$m]['categorie'];
									$categorie_passage=$tab_bull['nom_cat_complet'][$m];
									//if($matiere[$ident_eleve_aff][$id_periode][$m]['affiche_moyenne']==='1')
									if(isset($tab_bull['moy_cat_classe'][$i][$tab_bull['cat_id'][$m]]))
									{
										// On va afficher la moyenne de la classe pour la cat�gorie
										/*
										if($matiere[$ident_eleve_aff][$id_periode][$categorie_passage]['coef_tt_catego']!=0){
											$calcule_moyenne_classe_categorie[$categorie_passage]=$matiere[$ident_eleve_aff][$id_periode][$categorie_passage]['moy_classe']/$matiere[$ident_eleve_aff][$id_periode][$categorie_passage]['coef_tt_catego'];
										}
										else{
											$calcule_moyenne_classe_categorie[$categorie_passage]="";
										}
										$calcule_moyenne_classe_categorie[$categorie_passage]=$calcule_moyenne_classe_categorie[$categorie_passage];
										*/
										//================================================
										$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
										//$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], present_nombre($tab_bull['moy_cat_classe'][$i][$tab_bull['cat_id'][$m]], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]),'TLR',0,'C');
										//$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], $tab_bull['moy_cat_classe'][$i][$tab_bull['cat_id'][$m]], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id],'TLR',0,'C');

										// Patch mal foutu parce que present_nombre() attend un nombre au format 16.5 et que la moyenne de cat�gorie est d�j� format�e avec virgule 16,5... du coup on perdait la partie d�cimale.
										//$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], present_nombre(preg_replace("/,/",".",$tab_bull['moy_cat_classe'][$i][$tab_bull['cat_id'][$m]]), $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]),'TLR',0,'C');

										if (($tab_bull['moy_cat_classe'][$i][$tab_bull['cat_id'][$m]]=="")||($tab_bull['moy_cat_classe'][$i][$tab_bull['cat_id'][$m]]=="-")) {
											$valeur = "-";
										} else {
											$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], present_nombre($tab_bull['moy_cat_classe'][$i][$tab_bull['cat_id'][$m]], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]),'TLR',0,'C');
										}
									} else {
										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], '','T',0,'C');
									}
								} else {
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], '','T',0,'C');
								}
								$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
							}

							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
							// Moyenne minimale de la classe dans la cat�gorie
							//if($tab_modele_pdf["active_moyenne_min"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'min' ) {
							if(($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]!=1)&&($tab_modele_pdf["active_moyenne_min"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'min' )) {
								$pdf->SetXY($X_min_classe, $Y_decal);
								if($tab_modele_pdf["active_moyenne_regroupement"][$classe_id]==='1') {
									$categorie_passage=$tab_bull['nom_cat_complet'][$m];

									//if($matiere[$ident_eleve_aff][$id_periode][$m]['affiche_moyenne']==='1')
									if(isset($tab_bull['moy_cat_min'][$i][$tab_bull['cat_id'][$m]]))
									{
										// On va afficher la moyenne min de la classe pour la cat�gorie
										/*
										// JE NE L'AI PAS EXTRAITE...
										if($matiere[$ident_eleve_aff][$id_periode][$categorie_passage]['coef_tt_catego']!=0){
											$calcule_moyenne_classe_categorie[$categorie_passage]=$matiere[$ident_eleve_aff][$id_periode][$categorie_passage]['moy_cat_min']/$matiere[$ident_eleve_aff][$id_periode][$categorie_passage]['coef_tt_catego'];
										}
										else{
											$calcule_moyenne_classe_categorie[$categorie_passage]="";
										}
										//================================================

										$calcule_moyenne_classe_categorie[$categorie_passage]=$calcule_moyenne_classe_categorie[$categorie_passage];
										*/

										//$calcule_moyenne_classe_categorie[$categorie_passage]=preg_replace("/,/",".",$tab_bull['moy_cat_min'][$i][$tab_bull['cat_id'][$m]]);

										$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
										if (($tab_bull['moy_cat_min'][$i][$tab_bull['cat_id'][$m]]=="")||($tab_bull['moy_cat_min'][$i][$tab_bull['cat_id'][$m]]=="-")) {
											$valeur = "-";
										} else {
											$calcule_moyenne_classe_categorie[$categorie_passage]=preg_replace("/,/",".",$tab_bull['moy_cat_min'][$i][$tab_bull['cat_id'][$m]]);

											$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], present_nombre($calcule_moyenne_classe_categorie[$categorie_passage], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]),'TLR',0,'C');
										}
									} else {
										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], '','T',0,'C');
									}
								} else {
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], '','T',0,'C');
								}
								$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
							}

							// Moyenne maximale de la classe dans la cat�gorie
							//if($tab_modele_pdf["active_moyenne_max"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'max' ) {
							if(($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]!=1)&&($tab_modele_pdf["active_moyenne_max"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'max' )) {
								$pdf->SetXY($X_max_classe, $Y_decal);

								if($tab_modele_pdf["active_moyenne_regroupement"][$classe_id]==='1') {
									$categorie_passage=$tab_bull['nom_cat_complet'][$m];

									//if($matiere[$ident_eleve_aff][$id_periode][$m]['affiche_moyenne']==='1')
									if(isset($tab_bull['moy_cat_max'][$i][$tab_bull['cat_id'][$m]]))
									{
										// On va afficher la moyenne max de la classe pour la cat�gorie
										/*
										if($matiere[$ident_eleve_aff][$id_periode][$categorie_passage]['coef_tt_catego']){
											$calcule_moyenne_classe_categorie[$categorie_passage]=$matiere[$ident_eleve_aff][$id_periode][$categorie_passage]['moy_max']/$matiere[$ident_eleve_aff][$id_periode][$categorie_passage]['coef_tt_catego'];
										}
										else{
											$calcule_moyenne_classe_categorie[$categorie_passage]="";
										}
										//================================================

										$calcule_moyenne_classe_categorie[$categorie_passage]=$calcule_moyenne_classe_categorie[$categorie_passage];
										*/

										//$calcule_moyenne_classe_categorie[$categorie_passage]=preg_replace("/,/",".",$tab_bull['moy_cat_max'][$i][$tab_bull['cat_id'][$m]]);

										//$pdf->SetFont($caractere_utilse[$classe_id],'',8);
										$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
										//$pdf->Cell($largeur_d_une_moyenne[$classe_id], $hauteur_info_categorie[$classe_id], present_nombre($calcule_moyenne_classe_categorie[$categorie_passage], $arrondie_choix[$classe_id], $nb_chiffre_virgule[$classe_id], $chiffre_avec_zero[$classe_id]),'TLR',0,'C');
										if (($tab_bull['moy_cat_max'][$i][$tab_bull['cat_id'][$m]]=="")||($tab_bull['moy_cat_max'][$i][$tab_bull['cat_id'][$m]]=="-")) {
											$valeur = "-";
										} else {
											$calcule_moyenne_classe_categorie[$categorie_passage]=preg_replace("/,/",".",$tab_bull['moy_cat_max'][$i][$tab_bull['cat_id'][$m]]);

											$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], present_nombre($calcule_moyenne_classe_categorie[$categorie_passage], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]),'TLR',0,'C');
										}
									} else {

										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], '','T',0,'C');
									}
								} else {
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], '','T',0,'C');
								}
								$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
							}
							$cpt_ordre = $cpt_ordre + 1;
						}
						$largeur_utilise = 0;
						// fin de boucle d'ordre

						// Rang de l'�l�ve
						if($tab_modele_pdf["active_rang"][$classe_id]==='1') {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal);
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
							$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], '','T',0,'C');
							$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_rang"][$classe_id];
						}
						// Graphique de niveau
						if($tab_modele_pdf["active_graphique_niveau"][$classe_id]==='1') {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal);
							$pdf->Cell($tab_modele_pdf["largeur_niveau"][$classe_id], $tab_modele_pdf["hauteur_info_categorie"][$classe_id], '','T',0,'C');
							$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_niveau"][$classe_id];
						}
						// Appreciation
						if($tab_modele_pdf["active_appreciation"][$classe_id]==='1') {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal);
							$pdf->Cell($largeur_appreciation, $tab_modele_pdf["hauteur_info_categorie"][$classe_id], '','TB',0,'C');
							$largeur_utilise=0;
						}
						$Y_decal = $Y_decal + 5;

					}
				}

				fich_debug_bull("Apr�s les cat�gories\n");
				fich_debug_bull("\$Y_decal=$Y_decal\n");

				//============================
				// Modif: boireaus 20070828
				if($tab_modele_pdf["active_regroupement_cote"][$classe_id]==='1' or $tab_modele_pdf["active_entete_regroupement"][$classe_id]==='1') {
					//if($matiere[$ident_eleve_aff][$id_periode][$m]['categorie']===$categorie_passe) {
					/*
					if(isset($tab_bull['note'][$m][$i])) {
						if($tab_bull['nom_cat_complet'][$m]===$categorie_passe) {
							$categorie_passe_count=$categorie_passe_count+1;
						}
						else {
							$categorie_passe_count=0;
						}

						//if($matiere[$ident_eleve_aff][$id_periode][$m]['categorie']!=$categorie_passe) {
						if($tab_bull['nom_cat_complet'][$m]!=$categorie_passe) {
							$categorie_passe_count=$categorie_passe_count+1;
						}
					}
					*/
					if($tab_bull['nom_cat_complet'][$m]!=$categorie_passe) {
						$categorie_passe_count=0;

						$Y_categ_cote=$Y_decal;
					}
					/*
					else {
						$categorie_passe_count++;
					}
					if(!isset($tab_bull['note'][$m][$i])) {
						$categorie_passe_count--;
					}
					*/

					if(isset($tab_bull['note'][$m][$i])) {
						$categorie_passe_count++;
					}
					// fin des moyen par cat�gorie
				}

				fich_debug_bull("\$categorie_passe_count=$categorie_passe_count\n");

				//============================

				// si on affiche les cat�gories sur le c�t�

				if(!isset($tab_bull['nom_cat_complet'][$m+1])) {
					//$matiere[$ident_eleve_aff][$id_periode][$m+1]['categorie']='';
					$tab_bull['nom_cat_complet'][$m+1]='';
				}

				if($tab_modele_pdf["active_regroupement_cote"][$classe_id]==='1') {
					if($tab_bull['nom_cat_complet'][$m]!=$tab_bull['nom_cat_complet'][$m+1] and $categorie_passe!='')
					{
						//hauteur du regroupement hauteur des matier * nombre de matier de la cat�gorie
						//$hauteur_regroupement=$espace_entre_matier*($categorie_passe_count+1);
						$hauteur_regroupement=$espace_entre_matier*$categorie_passe_count;

						fich_debug_bull("\$espace_entre_matier=$espace_entre_matier\n");
						fich_debug_bull("\$categorie_passe_count=$categorie_passe_count\n");
						fich_debug_bull("\$hauteur_regroupement=$hauteur_regroupement\n");

						//placement du cadre
						//if($nb_eleve_aff===0) { $enplus = 5; }
						//if($nb_eleve_aff!=0) { $enplus = 0; }
						//if($compteur_bulletins===0) { $enplus = 5; }
						//if($compteur_bulletins!=0) { $enplus = 0; }

						fich_debug_bull("Position du cadre $categorie_passe\n");
						$tmp_val=$Y_decal-$hauteur_regroupement+$espace_entre_matier;
						fich_debug_bull("\$Y_decal-\$hauteur_regroupement+\$espace_entre_matier=".$Y_decal."-".$hauteur_regroupement."+".$espace_entre_matier."=".$tmp_val."\n");

						//$pdf->SetXY($X_bloc_matiere-5,$Y_decal-$hauteur_regroupement+$espace_entre_matier);
						$pdf->SetXY($X_bloc_matiere-5,$Y_categ_cote);


						$pdf->SetFillColor($tab_modele_pdf["couleur_categorie_cote1"][$classe_id], $tab_modele_pdf["couleur_categorie_cote2"][$classe_id], $tab_modele_pdf["couleur_categorie_cote3"][$classe_id]);
						if($tab_modele_pdf["couleur_categorie_cote"][$classe_id] === '1') {
							$mode_choix_c = '2';
						}
						else {
							$mode_choix_c = '1';
						}
						$pdf->drawTextBox("", 5, $hauteur_regroupement, 'C', 'T', $mode_choix_c);
						//texte � afficher
						$hauteur_caractere_vertical = '8';
						if ( $tab_modele_pdf["taille_texte_categorie_cote"][$classe_id] != '' and $tab_modele_pdf["taille_texte_categorie_cote"][$classe_id] != '0') {
							$hauteur_caractere_vertical = $tab_modele_pdf["taille_texte_categorie_cote"][$classe_id];
						}
						else {
							$hauteur_caractere_vertical = '8';
						}
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_vertical);
						$text_s = unhtmlentities($tab_bull['nom_cat_complet'][$m]);
						$longeur_test_s = $pdf->GetStringWidth($text_s);

						// gestion de la taille du texte vertical
						$taille_texte = $hauteur_regroupement;
						$grandeur_texte = 'test';
						while($grandeur_texte != 'ok') {
							if($taille_texte < $longeur_test_s)
							{
								$hauteur_caractere_vertical = $hauteur_caractere_vertical-0.3;
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_vertical);
								$longeur_test_s = $pdf->GetStringWidth($text_s);
							}
							else {
								$grandeur_texte = 'ok';
							}
						}


						//d�calage pour centre le texte
						$deca = ($hauteur_regroupement-$longeur_test_s)/2;
						$deca = 0;
						$deca = ($hauteur_regroupement-$longeur_test_s)/2;

						//place le texte dans le cadre
						//$placement = $Y_decal+$espace_entre_matier-$deca;
						$placement = $Y_categ_cote+$hauteur_regroupement-$deca;
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_vertical);
						$pdf->TextWithDirection($X_bloc_matiere-1,$placement,traite_accents_utf8(unhtmlentities($text_s)),'U');
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
						$pdf->SetFillColor(0, 0, 0);
					}
				}

				fich_debug_bull("Apr�s les cat�gories sur le c�t�\n");
				fich_debug_bull("\$Y_decal=$Y_decal\n");

				if($tab_modele_pdf["active_regroupement_cote"][$classe_id]==='1' or $tab_modele_pdf["active_entete_regroupement"][$classe_id]==='1') {
					// fin d'affichage cat�gorie sur le cot�
					$categorie_passe=$tab_bull['nom_cat_complet'][$m];
					// fin de gestion de cat�gorie
				}
				//============================

				// Lignes de Mati�re, Note, Rang,... Appr�ciation

				$pdf->SetXY($X_bloc_matiere, $Y_decal);

				// Si c'est une mati�re suivie par l'�l�ve
				if(isset($tab_bull['note'][$m][$i])) {
					//echo "\$tab_bull['eleve'][$i]['nom']=".$tab_bull['eleve'][$i]['nom']."<br />\n";

					// calcul la taille du titre de la mati�re
					$hauteur_caractere_matiere=10;
					if ( $tab_modele_pdf["taille_texte_matiere"][$classe_id] != '' and $tab_modele_pdf["taille_texte_matiere"][$classe_id] != '0' and $tab_modele_pdf["taille_texte_matiere"][$classe_id] < '11' )
					{
						$hauteur_caractere_matiere = $tab_modele_pdf["taille_texte_matiere"][$classe_id];
					}
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',$hauteur_caractere_matiere);



					if(getSettingValue('bul_rel_nom_matieres')=='nom_groupe') {
						$info_nom_matiere=$tab_bull['groupe'][$m]['name'];
					}
					elseif(getSettingValue('bul_rel_nom_matieres')=='description_groupe') {
						$info_nom_matiere=$tab_bull['groupe'][$m]['description'];
					}
					else {
						// Pour parer au bug sur la suppression de mati�re alors que des groupes sont conserv�s:
						if(isset($tab_bull['groupe'][$m]['matiere']['nom_complet'])) {
							$info_nom_matiere=$tab_bull['groupe'][$m]['matiere']['nom_complet'];
						}
						else {
							$info_nom_matiere=$tab_bull['groupe'][$m]['name']." (".$tab_bull['groupe'][$m]['id'].")";
						}
					}



					$val = $pdf->GetStringWidth($info_nom_matiere);
					$taille_texte = $tab_modele_pdf["largeur_matiere"][$classe_id] - 2;
					$grandeur_texte='test';
					while($grandeur_texte!='ok') {
						if($taille_texte<$val)
						{
							$hauteur_caractere_matiere = $hauteur_caractere_matiere-0.3;
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',$hauteur_caractere_matiere);
							$val = $pdf->GetStringWidth($info_nom_matiere);
						}
						else {
							$grandeur_texte='ok';
						}
					}
					$grandeur_texte='test';
					$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $espace_entre_matier/2, traite_accents_utf8($info_nom_matiere),'LR',1,'L');
					$Y_decal = $Y_decal+($espace_entre_matier/2);
					$pdf->SetXY($X_bloc_matiere, $Y_decal);
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);

					fich_debug_bull("\$info_nom_matiere=$info_nom_matiere\n");
					fich_debug_bull("\$Y_decal=$Y_decal\n");

					// nom des professeurs

					if ( isset($tab_bull['groupe'][$m]["profs"]["list"]) )
					{
						if($tab_modele_pdf["presentation_proflist"][$classe_id]!="2") {
							// Pr�sentation en colonne des profs
							$nb_prof_matiere = count($tab_bull['groupe'][$m]["profs"]["list"]);
							$espace_matiere_prof = $espace_entre_matier/2;
							if($nb_prof_matiere>0){
								$espace_matiere_prof = $espace_matiere_prof/$nb_prof_matiere;
							}
							$nb_pass_count = '0';
							$text_prof = '';
							while ($nb_prof_matiere > $nb_pass_count)
							{
								//$text_prof="";
								//for($loop_prof_grp=0;$loop_prof_grp<$nb_prof_par_ligne;$loop_prof_grp++) {
									// calcul de la hauteur du caract�re du prof
									//$tmp_login_prof=$tab_bull['groupe'][$m]["profs"]["list"][$nb_pass_count+$loop_prof_grp];
									$tmp_login_prof=$tab_bull['groupe'][$m]["profs"]["list"][$nb_pass_count];
									/*
									$text_prof=$tab_bull['groupe'][$m]["profs"]["users"]["$tmp_login_prof"]["civilite"];
									$text_prof.=" ".$tab_bull['groupe'][$m]["profs"]["users"]["$tmp_login_prof"]["nom"];
									$text_prof.=" ".substr($tab_bull['groupe'][$m]["profs"]["users"]["$tmp_login_prof"]["prenom"],0,1);
									*/
									//if($loop_prof_grp>0) {$text_prof.=", ";}
									$text_prof=affiche_utilisateur($tmp_login_prof,$tab_bull['eleve'][$i]['id_classe']);
								//}
	
								if ( $nb_prof_matiere <= 2 ) { $hauteur_caractere_prof = 8; }
								elseif ( $nb_prof_matiere == 3) { $hauteur_caractere_prof = 5; }
								elseif ( $nb_prof_matiere > 3) { $hauteur_caractere_prof = 2; }
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_prof);
								$val = $pdf->GetStringWidth($text_prof);
								$taille_texte = ($tab_modele_pdf["largeur_matiere"][$classe_id]);
								$grandeur_texte='test';
								while($grandeur_texte!='ok') {
									if($taille_texte<$val)
									{
										$hauteur_caractere_prof = $hauteur_caractere_prof-0.3;
										$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_prof);
										$val = $pdf->GetStringWidth($text_prof);
									}
									else {
										$grandeur_texte='ok';
									}
								}
								$grandeur_texte='test';
								$pdf->SetX($X_bloc_matiere);
								if( empty($tab_bull['groupe'][$m]["profs"]["list"][$nb_pass_count+1]) ) {
									$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $espace_matiere_prof, traite_accents_utf8($text_prof),'LRB',1,'L');
								}
								if( !empty($tab_bull['groupe'][$m]["profs"]["list"][$nb_pass_count+1]) ) {
									$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $espace_matiere_prof, traite_accents_utf8($text_prof),'LR',1,'L');
								}
								$nb_pass_count = $nb_pass_count + 1;
							}
						}
						else {
							// Pr�sentation en ligne des profs
							$text_prof=$tab_bull['groupe'][$m]["profs"]["proflist_string"]."  ";
							if($text_prof!="") {
								$espace_matiere_prof = $espace_entre_matier/2;
								$hauteur_caractere_prof = 8;

								if($use_cell_ajustee=="n") {
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_prof);
									$val = $pdf->GetStringWidth($text_prof);
									$taille_texte = ($tab_modele_pdf["largeur_matiere"][$classe_id]);
									$grandeur_texte='test';
									while($grandeur_texte!='ok') {
										if($taille_texte<$val)
										{
											$hauteur_caractere_prof = $hauteur_caractere_prof-0.3;
											$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_prof);
											$val = $pdf->GetStringWidth($text_prof);
										}
										else {
											$grandeur_texte='ok';
										}
									}
									$grandeur_texte='test';
									$pdf->SetX($X_bloc_matiere);
									$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $espace_matiere_prof, traite_accents_utf8($text_prof),'LR',1,'L');
								}
								else {
									$texte=$text_prof;
									$taille_max_police=$hauteur_caractere_prof;
									$taille_min_police=ceil($hauteur_caractere_prof/3);
	
									$largeur_dispo=$tab_modele_pdf["largeur_matiere"][$classe_id];
									$h_cell=$espace_matiere_prof;

									$pdf->SetX($X_bloc_matiere);
	
									cell_ajustee(traite_accents_utf8($texte),$pdf->GetX(),$pdf->GetY(),$largeur_dispo,$h_cell,$taille_max_police,$taille_min_police,'');
								}
							}
						}
					}
					$largeur_utilise = $tab_modele_pdf["largeur_matiere"][$classe_id];

					// coefficient mati�re
					if($tab_modele_pdf["active_coef_moyenne"][$classe_id]==='1') {
						$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
						$pdf->Cell($tab_modele_pdf["largeur_coef_moyenne"][$classe_id], $espace_entre_matier, $tab_bull['coef_eleve'][$i][$m],1,0,'C');
						$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_coef_moyenne"][$classe_id];
					}

					//permet le calcul total des coefficients
					// if(empty($moyenne_min[$id_classe][$id_periode])) {
						$total_coef_en_calcul=$total_coef_en_calcul+$tab_bull['coef_eleve'][$i][$m];
					//}

					// nombre de note
					// 20081118
					//if($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1') {
					if(($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1')&&($tab_modele_pdf["active_nombre_note"][$classe_id]!='1')) {
						$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
						$valeur = $tab_bull['nbct'][$m][$i] . "/" . $tab_bull['groupe'][$m]['nbct'];
						$pdf->Cell($tab_modele_pdf["largeur_nombre_note"][$classe_id], $espace_entre_matier, $valeur,1,0,'C');
						$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_nombre_note"][$classe_id];
					}

					// les moyennes eleve, classe, min, max
					$cpt_ordre = 0;
					while (!empty($ordre_moyenne[$cpt_ordre]) ) {
						//eleve
						if($tab_modele_pdf["active_moyenne_eleve"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'eleve' ) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
							$pdf->SetFillColor($tab_modele_pdf["couleur_reperage_eleve1"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve2"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve3"][$classe_id]);

							// calcul nombre de sous affichage

							$nb_sousaffichage='1';
							//20090908 if(empty($active_coef_sousmoyene)) { $active_coef_sousmoyene = ''; }

							//++++++++++++++
							//20100615
							//if((isset($moyennes_periodes_precedentes))&&($moyennes_periodes_precedentes=="y")) {
							//if($tab_modele_pdf["moyennes_periodes_precedentes"][$classe_id]=='y') {
							if(($tab_modele_pdf["moyennes_periodes_precedentes"][$classe_id]=='y')&&(isset($tab_bull['login_prec']))) {
								$nb_sousaffichage+=count($tab_bull['login_prec']); // Il faut r�cup�rer le nombre de p�riodes...
							}
							//++++++++++++++

							if($tab_modele_pdf["active_coef_sousmoyene"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; }

							//20100615
							//if((!isset($moyennes_periodes_precedentes))||($moyennes_periodes_precedentes!="y")) {
							if($tab_modele_pdf["moyennes_periodes_precedentes"][$classe_id]!='y') {
								// On n'affiche pas tout ce qui suit en plus, si on affiche les moyennes de toutes les p�riodes d�j�
								if($tab_modele_pdf["active_nombre_note"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; }
								if($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]==='1') { if($tab_modele_pdf["active_moyenne_classe"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; } }
								if($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]==='1') { if($tab_modele_pdf["active_moyenne_min"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; } }
								if($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]==='1') { if($tab_modele_pdf["active_moyenne_max"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; } }
							}


							//20100615
							//if((!isset($moyennes_periodes_precedentes))||($moyennes_periodes_precedentes!="y")) {
							if($tab_modele_pdf["moyennes_periodes_precedentes"][$classe_id]!='y') {
								//if((isset($evolution_moyenne_periode_precedente))&&($evolution_moyenne_periode_precedente=="y")) {
								if($tab_modele_pdf["evolution_moyenne_periode_precedente"][$classe_id]=='y') {
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',8);
								}
								else {
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',10);
								}

								$fleche_evolution="";

								// On filtre si la moyenne est vide, on affiche seulement un tiret
								if ($tab_bull['note'][$m][$i]=="-") {
									$valeur = "-";
								}
								elseif ($tab_bull['statut'][$m][$i]!="") {
									$valeur=$tab_bull['statut'][$m][$i];
								}
								else {
									$valeur = present_nombre($tab_bull['note'][$m][$i], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);

									//if((isset($evolution_moyenne_periode_precedente))&&($evolution_moyenne_periode_precedente=="y")) {
									if (($tab_modele_pdf["evolution_moyenne_periode_precedente"][$classe_id]=='y')&&(isset($tab_bull['login_prec']))) {
										//$fleche_evolution="";

										foreach($tab_bull['login_prec'] as $key => $value) {
											// Il faut r�cup�rer l'id_groupe et l'indice de l'�l�ve... dans les tableaux r�cup�r�s de calcul_moy_gen.inc.php
											// Tableaux d'indices [$j][$i] (groupe, �l�ve)
											$indice_eleve=-1;
											for($loop_l=0;$loop_l<count($tab_bull['login_prec'][$key]);$loop_l++) {
												//echo "\$tab_bull['login_prec'][$key][$loop_l]=".$tab_bull['login_prec'][$key][$loop_l]." et \$tab_bull['eleve'][$i]['login']=".$tab_bull['eleve'][$i]['login']."<br />\n";
												if($tab_bull['login_prec'][$key][$loop_l]==$tab_bull['eleve'][$i]['login']) {$indice_eleve=$loop_l;break;}
											}
											//echo "\$indice_eleve=$indice_eleve<br />\n";
		
											if($indice_eleve!=-1) {
												// Recherche du groupe
												$indice_grp=-1;
												for($loop_l=0;$loop_l<count($tab_bull['group_prec'][$key]);$loop_l++) {
													//echo "\$tab_bull['group_prec'][$key][$loop_l]['id']=".$tab_bull['group_prec'][$key][$loop_l]['id']." et \$tab_bull['groupe'][$m]['id']=".$tab_bull['groupe'][$m]['id']."<br />\n";
													if($tab_bull['group_prec'][$key][$loop_l]['id']==$tab_bull['groupe'][$m]['id']) {$indice_grp=$loop_l;break;}
												}
												//echo "\$indice_grp=$indice_grp<br />\n";
		
												if($indice_grp!=-1) {
													if(isset($tab_bull['statut_prec'][$key][$indice_grp][$indice_eleve])) {
														if ($tab_bull['statut_prec'][$key][$indice_grp][$indice_eleve]=="") {
															//echo "\$tab_bull['note'][$m][$i]=".$tab_bull['note'][$m][$i]."<br />\n";
															//echo "\$tab_bull['note_prec'][$key][$indice_grp][$indice_eleve]=".$tab_bull['note_prec'][$key][$indice_grp][$indice_eleve]."<br />\n";
															if($tab_bull['note'][$m][$i]>$tab_bull['note_prec'][$key][$indice_grp][$indice_eleve]) {
																$fleche_evolution="+";
															}
															elseif($tab_bull['note'][$m][$i]<$tab_bull['note_prec'][$key][$indice_grp][$indice_eleve]) {
																$fleche_evolution="-";
															}
															else {
																$fleche_evolution="";
															}
															//echo "\$fleche_evolution=".$fleche_evolution."<br />\n";

															//$valeur = present_nombre($tab_bull['note_prec'][$key][$indice_grp][$indice_eleve], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
														}

													}
												}
											}
		
										}


									}
								}
								if($fleche_evolution!="") {$fleche_evolution=" ".$fleche_evolution;}
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, $valeur.$fleche_evolution,1,2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
								$valeur = "";

								if($tab_modele_pdf["active_coef_sousmoyene"][$classe_id]==='1') {
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',7);
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'coef. '.$tab_bull['coef_eleve'][$i][$m],'LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
								}

								if($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]==='1') {
									// On affiche toutes les moyennes dans la m�me colonne
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',7);
									if($tab_modele_pdf["active_moyenne_classe"][$classe_id]==='1') {
										//if ($tab_bull['moy_classe_grp'][$m]=="-") {
										if (($tab_bull['moy_classe_grp'][$m]=="-")||($tab_bull['moy_classe_grp'][$m]=="")) {
											$valeur = "-";
										}
										else {
											$valeur = present_nombre($tab_bull['moy_classe_grp'][$m], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
										}
										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'cla.'.$valeur,'LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									}
									if($tab_modele_pdf["active_moyenne_min"][$classe_id]==='1') {
										//if ($tab_bull['moy_min_classe_grp'][$m]=="-") {
										if (($tab_bull['moy_min_classe_grp'][$m]=="-")||($tab_bull['moy_min_classe_grp'][$m]=="")) {
											$valeur = "-";
										} else {
											$valeur = present_nombre($tab_bull['moy_min_classe_grp'][$m], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
										}
										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'min.'.$valeur,'LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									}
									if($tab_modele_pdf["active_moyenne_max"][$classe_id]==='1') {
										//if ($tab_bull['moy_max_classe_grp'][$m]=="-") {
										if (($tab_bull['moy_max_classe_grp'][$m]=="-")||($tab_bull['moy_max_classe_grp'][$m]=="")) {
											$valeur = "-";
										} else {
											$valeur = present_nombre($tab_bull['moy_max_classe_grp'][$m], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
										}
										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'max.'.$valeur,'LRD',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
										$valeur = ''; // on remet � vide.
									}
								}

								if($tab_modele_pdf["active_nombre_note"][$classe_id]==='1') {
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',7);
									$espace_pour_nb_note = $espace_entre_matier/$nb_sousaffichage;
									$espace_pour_nb_note = $espace_pour_nb_note / 2;
									$valeur1 = ''; $valeur2 = '';
									if ($tab_bull['nbct'][$m][$i]!= 0 ) {
										$valeur1 = $tab_bull['nbct'][$m][$i].' note';
										if($tab_bull['nbct'][$m][$i]>1){$valeur1.='s';}
										$valeur2 = 'sur '.$tab_bull['groupe'][$m]['nbct'];
									}
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_pour_nb_note, $valeur1, 'LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_pour_nb_note, $valeur2, 'LRB',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									$valeur1 = ''; $valeur2 = '';
								}

							}
							else {
								//20100615
								// On affiche les moyennes de l'�l�ve pour les autres p�riodes dans la m�me colonne
								// Il faut r�cup�rer l'indice de l'�l�ve... dans les tableaux r�cup�r�s de calcul_moy_gen.inc.php
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',6);

								if(isset($tab_bull['login_prec'])) {
									//for($loop_p=1;$loop_p<count($tab_bull['login_prec']);$loop_p++) {
									foreach($tab_bull['login_prec'] as $key => $value) {
										// Il faut r�cup�rer l'id_groupe et l'indice de l'�l�ve... dans les tableaux r�cup�r�s de calcul_moy_gen.inc.php
										// Tableaux d'indices [$j][$i] (groupe, �l�ve)
										//$tab_bull['note_prec'][$loop_p]=$current_eleve_note;
										//$tab_bull['statut_prec'][$loop_p]=$current_eleve_statut;
										$indice_eleve=-1;
										//for($loop_l=0;$loop_l<count($tab_bull['login_prec'][$loop_p]);$loop_l++) {
										for($loop_l=0;$loop_l<count($tab_bull['login_prec'][$key]);$loop_l++) {
											//echo "\$tab_bull['login_prec'][$key][$loop_l]=".$tab_bull['login_prec'][$key][$loop_l]." et \$tab_bull['eleve'][$i]['login']=".$tab_bull['eleve'][$i]['login']."<br />\n";
											if($tab_bull['login_prec'][$key][$loop_l]==$tab_bull['eleve'][$i]['login']) {$indice_eleve=$loop_l;break;}
										}
										//echo "\$indice_eleve=$indice_eleve<br />\n";
	
										if($indice_eleve!=-1) {
											// Recherche du groupe
											$indice_grp=-1;
											for($loop_l=0;$loop_l<count($tab_bull['group_prec'][$key]);$loop_l++) {
												//echo "\$tab_bull['group_prec'][$key][$loop_l]['id']=".$tab_bull['group_prec'][$key][$loop_l]['id']." et \$tab_bull['groupe'][$m]['id']=".$tab_bull['groupe'][$m]['id']."<br />\n";
												if($tab_bull['group_prec'][$key][$loop_l]['id']==$tab_bull['groupe'][$m]['id']) {$indice_grp=$loop_l;break;}
											}
											//echo "\$indice_grp=$indice_grp<br />\n";
	
											if($indice_grp!=-1) {
												if(isset($tab_bull['statut_prec'][$key][$indice_grp][$indice_eleve])) {
													if ($tab_bull['statut_prec'][$key][$indice_grp][$indice_eleve]!="") {
														$valeur = $tab_bull['statut_prec'][$key][$indice_grp][$indice_eleve];
													}
													else {
														$valeur = present_nombre($tab_bull['note_prec'][$key][$indice_grp][$indice_eleve], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
													}
													if($key==1) {$bordure_top='T';} else {$bordure_top='';}
													$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'P'.$key.': '.$valeur,'LR'.$bordure_top,2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
												}
											}
										}
	
									}
								}

								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',10);

								// On filtre si la moyenne est vide, on affiche seulement un tiret
								if ($tab_bull['note'][$m][$i]=="-") {
									$valeur = "-";
								}
								elseif ($tab_bull['statut'][$m][$i]!="") {
									$valeur=$tab_bull['statut'][$m][$i];
								}
								else {
									$valeur = present_nombre($tab_bull['note'][$m][$i], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
								}
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, $valeur,1,2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
								$valeur = "";

								// On affiche �ventuellement le coef
								if($tab_modele_pdf["active_coef_sousmoyene"][$classe_id]==='1') {
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',6);
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'coef. '.$tab_bull['coef_eleve'][$i][$m],'LRB',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
								}

							}

							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
							$pdf->SetFillColor(0, 0, 0);
							$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];

						} // Fin affichage �l�ve


						//classe
						//if( $tab_modele_pdf["active_moyenne_classe"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'classe' ) {
						if(($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]!=1)&&( $tab_modele_pdf["active_moyenne_classe"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'classe' )) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
							//if ($tab_bull['moy_classe_grp'][$m]=="-") {
							if (($tab_bull['moy_classe_grp'][$m]=="-")||($tab_bull['moy_classe_grp'][$m]=="")) {
								$valeur = "-";
							} else {
								$valeur = present_nombre($tab_bull['moy_classe_grp'][$m], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
							}
							$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier, $valeur,'TLRB',0,'C');
							/*
							//permet le calcul de la moyenne g�n�ral de la classe
							if(empty($moyenne_classe[$id_classe][$id_periode])) {
								$total_moyenne_classe_en_calcul=$total_moyenne_classe_en_calcul+($matiere[$ident_eleve_aff][$id_periode][$m]['moy_classe']*$matiere[$ident_eleve_aff][$id_periode][$m]['coef']);
							}
							*/
							$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
						}
						//min
						//if( $tab_modele_pdf["active_moyenne_min"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'min' ) {
						if(($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]!=1)&&( $tab_modele_pdf["active_moyenne_min"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'min' )) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
							//if ($tab_bull['moy_min_classe_grp'][$m]=="-") {
							if (($tab_bull['moy_min_classe_grp'][$m]=="-")||($tab_bull['moy_min_classe_grp'][$m]=="")) {
								$valeur = "-";
							} else {
								$valeur = present_nombre($tab_bull['moy_min_classe_grp'][$m], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
							}
							$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier, $valeur,'TLRB',0,'C');
							/*
							//permet le calcul de la moyenne mini
							if(empty($moyenne_min[$id_classe][$id_periode])) {
								$total_moyenne_min_en_calcul=$total_moyenne_min_en_calcul+($matiere[$ident_eleve_aff][$id_periode][$m]['moy_min']*$matiere[$ident_eleve_aff][$id_periode][$m]['coef']);
							}
							*/
							$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
						}
						//max
						//if( $tab_modele_pdf["active_moyenne_max"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'max' ) {
						if(($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]!=1)&&( $tab_modele_pdf["active_moyenne_max"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'max' )) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
							//if ($tab_bull['moy_max_classe_grp'][$m]== "-") {
							if (($tab_bull['moy_max_classe_grp'][$m]=="-")||($tab_bull['moy_max_classe_grp'][$m]=="")) {
								$valeur = "-";
							} else {
								$valeur = present_nombre($tab_bull['moy_max_classe_grp'][$m], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
							}
							$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier, $valeur,'TLRB',0,'C');
							/*
							//permet le calcul de la moyenne maxi
							if(empty($moyenne_max[$id_classe][$id_periode])) {
								$total_moyenne_max_en_calcul=$total_moyenne_max_en_calcul+($matiere[$ident_eleve_aff][$id_periode][$m]['moy_max']*$matiere[$ident_eleve_aff][$id_periode][$m]['coef']);
							}
							*/
							$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
						}
						//$largeur_utilise = $largeur_utilise+$largeur_moyenne;


						// rang de l'�l�ve
						if($tab_modele_pdf["active_rang"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'rang' ) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
							// A REVOIR: J'AI l'EFFECTIF DU GROUPE, mais faut-il compter les �l�ves ABS, DISP,...?
							//if((isset($tab_bull['rang'][$i][$m]))&&(isset($tab_bull['groupe'][$m]['effectif']))) {
								//$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $espace_entre_matier, $tab_bull['rang'][$i][$m].'/'.$tab_bull['groupe'][$m]['effectif'],1,0,'C');
							if((isset($tab_bull['rang'][$m][$i]))&&(isset($tab_bull['groupe'][$m]['effectif']))) {
								$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $espace_entre_matier, $tab_bull['rang'][$m][$i].'/'.$tab_bull['groupe'][$m]['effectif_avec_note'],1,0,'C');
							}
							else {
								$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $espace_entre_matier, '',1,0,'C');
							}
							$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_rang"][$classe_id];
						}

						// graphique de niveau
						if($tab_modele_pdf["active_graphique_niveau"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'niveau' ) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
							$id_groupe_graph = $tab_bull['groupe'][$m]['id'];
							// placement de l'�l�ve dans le graphique de niveau

							// AJOUT: La variable n'�tait pas initialis�e dans le bulletin_pdf_avec_modele...
							$place_eleve='';

							if ($tab_bull['note'][$m][$i]!="") {
								/*
								if ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<5) { $place_eleve=5;}
								if (($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=5) and ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<8))  { $place_eleve=4;}
								if (($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=8) and ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<10)) { $place_eleve=3;}
								if (($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=10) and ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<12)) {$place_eleve=2;}
								if (($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=12) and ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<15)) { $place_eleve=1;}
								if ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=15) { $place_eleve=0;}
								*/
								if(isset($tab_bull['place_eleve'][$m][$i])) {
									//$place_eleve=$tab_bull['place_eleve'][$m][$i];
									$place_eleve=$tab_bull['place_eleve'][$m][$i]-1;
								}
							}
							$data_grap[0]=$tab_bull['quartile1_grp'][$m];
							$data_grap[1]=$tab_bull['quartile2_grp'][$m];
							$data_grap[2]=$tab_bull['quartile3_grp'][$m];
							$data_grap[3]=$tab_bull['quartile4_grp'][$m];
							$data_grap[4]=$tab_bull['quartile5_grp'][$m];
							$data_grap[5]=$tab_bull['quartile6_grp'][$m];
							//if (array_sum($data_grap[$id_periode][$id_groupe_graph]) != 0) {
							if (array_sum($data_grap) != 0) {
								//$pdf->DiagBarre($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2), $tab_modele_pdf["largeur_niveau"][$classe_id], $espace_entre_matier, $data_grap[$id_periode][$id_groupe_graph], $place_eleve);
								$pdf->DiagBarre($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2), $tab_modele_pdf["largeur_niveau"][$classe_id], $espace_entre_matier, $data_grap, $place_eleve);
							}
							$place_eleve=''; // on vide la variable
							$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_niveau"][$classe_id];
						}

						//appr�ciation
						if($tab_modele_pdf["active_appreciation"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'appreciation' ) {
							// si on autorise l'affichage des sous mati�re et s'il y en a alors on les affiche
							$id_groupe_select = $tab_bull['groupe'][$m]['id'];
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
							$X_sous_matiere = 0; $largeur_sous_matiere=0;

							if($tab_modele_pdf["autorise_sous_matiere"][$classe_id]==='1' and !empty($tab_bull['groupe'][$m][$i]['cn_nom'])) {
								$X_sous_matiere = $X_note_moy_app+$largeur_utilise;
								$Y_sous_matiere = $Y_decal-($espace_entre_matier/2);
								$n=0;
								$largeur_texte_sousmatiere=0; $largeur_sous_matiere=0;
								while( !empty($tab_bull['groupe'][$m][$i]['cn_nom'][$n]) )
								{
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
									$largeur_texte_sousmatiere = $pdf->GetStringWidth($tab_bull['groupe'][$m][$i]['cn_nom'][$n].': '.$tab_bull['groupe'][$m][$i]['cn_note'][$n]);
									if($largeur_sous_matiere<$largeur_texte_sousmatiere) { $largeur_sous_matiere=$largeur_texte_sousmatiere; }
									$n = $n + 1;
								}
								if($largeur_sous_matiere!='0') { $largeur_sous_matiere = $largeur_sous_matiere + 2; }
								$n=0;
								while( !empty($tab_bull['groupe'][$m][$i]['cn_nom'][$n]) )
								{
									$pdf->SetXY($X_sous_matiere, $Y_sous_matiere);
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
									$pdf->Cell($largeur_sous_matiere, $espace_entre_matier/count($tab_bull['groupe'][$m][$i]['cn_nom']), traite_accents_utf8($tab_bull['groupe'][$m][$i]['cn_nom'][$n].': '.$tab_bull['groupe'][$m][$i]['cn_note'][$n]),1,0,'L');
									$Y_sous_matiere = $Y_sous_matiere+$espace_entre_matier/count($tab_bull['groupe'][$m][$i]['cn_nom']);
									$n = $n + 1;
								}
								$largeur_utilise = $largeur_utilise+$largeur_sous_matiere;
							}
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
							// calcul de la taille du texte des appr�ciation
							$hauteur_caractere_appreciation = 9;
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_appreciation);

							//suppression des espace en d�but et en fin
							$app_aff = trim($tab_bull['app'][$m][$i]);

							fich_debug_bull("__________________________________________\n");
							fich_debug_bull("$app_aff\n");
							fich_debug_bull("__________________________________________\n");

							// DEBUT AJUSTEMENT TAILLE APPRECIATION
							$taille_texte_total = $pdf->GetStringWidth($app_aff);
							$largeur_appreciation2 = $largeur_appreciation - $largeur_sous_matiere;

							if($use_cell_ajustee=="n") {
								//$taille_texte = (($espace_entre_matier/3)*$largeur_appreciation2);
								$nb_ligne_app = '2.8';
								//$nb_ligne_app = '3.8';
								//$nb_ligne_app = '4.8';
								$taille_texte_max = $nb_ligne_app * ($largeur_appreciation2-4);
								//$taille_texte_max = $nb_ligne_app * ($largeur_appreciation2);
								$grandeur_texte='test';
	
								fich_debug_bull("\$taille_texte_total=$taille_texte_total\n");
								fich_debug_bull("\$largeur_appreciation2=$largeur_appreciation2\n");
								fich_debug_bull("\$nb_ligne_app=$nb_ligne_app\n");
								//fich_debug_bull("\$taille_texte_max = \$nb_ligne_app * (\$largeur_appreciation2-4)=$nb_ligne_app * ($largeur_appreciation2-4)=$taille_texte_max\n");
								fich_debug_bull("\$taille_texte_max = \$nb_ligne_app * (\$largeur_appreciation2)=$nb_ligne_app * ($largeur_appreciation2)=$taille_texte_max\n");
	
								while($grandeur_texte!='ok') {
									if($taille_texte_max < $taille_texte_total)
									{
										$hauteur_caractere_appreciation = $hauteur_caractere_appreciation-0.3;
										//$hauteur_caractere_appreciation = $hauteur_caractere_appreciation-0.1;
										$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_appreciation);
										$taille_texte_total = $pdf->GetStringWidth($app_aff);
									}
									else {
										$grandeur_texte='ok';
									}
								}
								$grandeur_texte='test';
								$pdf->drawTextBox(traite_accents_utf8($app_aff), $largeur_appreciation2, $espace_entre_matier, 'J', 'M', 1);
							}
							else {
								$texte=$app_aff;
								//$texte="Bla bla\nbli ".$app_aff;
								$taille_max_police=$hauteur_caractere_appreciation;
								$taille_min_police=ceil($taille_max_police/3);

								$largeur_dispo=$largeur_appreciation2;
								$h_cell=$espace_entre_matier;

								if(getSettingValue('suppr_balises_app_prof')=='y') {$texte=preg_replace('/<(.*)>/U','',$texte);}
								cell_ajustee(traite_accents_utf8($texte),$pdf->GetX(),$pdf->GetY(),$largeur_dispo,$h_cell,$taille_max_police,$taille_min_police,'LRBT');
							}

							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
							$largeur_utilise = $largeur_utilise + $largeur_appreciation2;
							//$largeur_utilise = 0;
						}

						$cpt_ordre = $cpt_ordre + 1;
					}
					$largeur_utilise = 0;
					// fin de boucle d'ordre
					$Y_decal = $Y_decal+($espace_entre_matier/2);
				}
			}


			//======================================================
			// DEBUT DES AID AFFICH�S APRES LES MATIERES
			if(isset($tab_bull['eleve'][$i]['aid_e'])) {
				//echo "count(\$tab_bull['eleve'][$i]['aid_e']=".count($tab_bull['eleve'][$i]['aid_e'])."<br />";
				for($m=0;$m<count($tab_bull['eleve'][$i]['aid_e']);$m++) {
					$pdf->SetXY($X_bloc_matiere, $Y_decal);

					// Si c'est une mati�re suivie par l'�l�ve
					//if(isset($tab_bull['eleve'][$i]['note'][$m][$i])) {

						// calcul la taille du titre de la mati�re
						$hauteur_caractere_matiere=10;
						if ( $tab_modele_pdf["taille_texte_matiere"][$classe_id] != '' and $tab_modele_pdf["taille_texte_matiere"][$classe_id] != '0' and $tab_modele_pdf["taille_texte_matiere"][$classe_id] < '11' )
						{
							$hauteur_caractere_matiere = $tab_modele_pdf["taille_texte_matiere"][$classe_id];
						}
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',$hauteur_caractere_matiere);

						// Pour parer au bug sur la suppression de mati�re alors que des groupes sont conserv�s:
						if(isset($tab_bull['eleve'][$i]['aid_e'][$m]['nom_complet'])) {
							$info_nom_matiere=$tab_bull['eleve'][$i]['aid_e'][$m]['nom_complet'];
						}
						else {
							$info_nom_matiere=$tab_bull['eleve'][$i]['aid_e'][$m]['nom'];
						}

						$val = $pdf->GetStringWidth($info_nom_matiere);
						$taille_texte = $tab_modele_pdf["largeur_matiere"][$classe_id] - 2;
						$grandeur_texte='test';
						while($grandeur_texte!='ok') {
							if($taille_texte<$val)
							{
								$hauteur_caractere_matiere = $hauteur_caractere_matiere-0.3;
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',$hauteur_caractere_matiere);
								$val = $pdf->GetStringWidth($info_nom_matiere);
							}
							else {
								$grandeur_texte='ok';
							}
						}
						$grandeur_texte='test';
						$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $espace_entre_matier/2, traite_accents_utf8($info_nom_matiere),'LR',1,'L');
						$Y_decal = $Y_decal+($espace_entre_matier/2);
						$pdf->SetXY($X_bloc_matiere, $Y_decal);
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);

						fich_debug_bull("\$info_nom_matiere=$info_nom_matiere\n");
						fich_debug_bull("\$Y_decal=$Y_decal\n");

						// nom des professeurs

						if ( isset($tab_bull['eleve'][$i]['aid_e'][$m]['aid_prof_resp_login'][0]) )
						{

							$nb_prof_matiere = count($tab_bull['eleve'][$i]['aid_e'][$m]['aid_prof_resp_login']);
							$espace_matiere_prof = $espace_entre_matier/2;
							if($nb_prof_matiere>0){
								$espace_matiere_prof = $espace_matiere_prof/$nb_prof_matiere;
							}
							$nb_pass_count = '0';
							$text_prof = '';
							while ($nb_prof_matiere > $nb_pass_count)
							{
								$tmp_login_prof=$tab_bull['eleve'][$i]['aid_e'][$m]['aid_prof_resp_login'][$nb_pass_count];
								$text_prof=affiche_utilisateur($tmp_login_prof,$tab_bull['eleve'][$i]['id_classe']);

								if ( $nb_prof_matiere <= 2 ) { $hauteur_caractere_prof = 8; }
								elseif ( $nb_prof_matiere == 3) { $hauteur_caractere_prof = 5; }
								elseif ( $nb_prof_matiere > 3) { $hauteur_caractere_prof = 2; }
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_prof);
								$val = $pdf->GetStringWidth($text_prof);
								$taille_texte = ($tab_modele_pdf["largeur_matiere"][$classe_id]);
								$grandeur_texte='test';
								while($grandeur_texte!='ok') {
									if($taille_texte<$val)
									{
										$hauteur_caractere_prof = $hauteur_caractere_prof-0.3;
										$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_prof);
										$val = $pdf->GetStringWidth($text_prof);
									}
									else {
										$grandeur_texte='ok';
									}
								}
								$grandeur_texte='test';
								$pdf->SetX($X_bloc_matiere);
								if( empty($tab_bull['eleve'][$i]['aid_e'][$m]['aid_prof_resp_login'][$nb_pass_count+1]) ) {
									$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $espace_matiere_prof, traite_accents_utf8($text_prof),'LRB',1,'L');
								}
								if( !empty($tab_bull['eleve'][$i]['aid_e'][$m]['aid_prof_resp_login'][$nb_pass_count+1]) ) {
									$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $espace_matiere_prof, traite_accents_utf8($text_prof),'LR',1,'L');
								}
								$nb_pass_count = $nb_pass_count + 1;
							}
						}
						$largeur_utilise = $tab_modele_pdf["largeur_matiere"][$classe_id];

						// coefficient mati�re
						if($tab_modele_pdf["active_coef_moyenne"][$classe_id]==='1') {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
							//$pdf->Cell($tab_modele_pdf["largeur_coef_moyenne"][$classe_id], $espace_entre_matier, $tab_bull['eleve'][$i]['coef_eleve'][$i][$m],1,0,'C');
							$pdf->Cell($tab_modele_pdf["largeur_coef_moyenne"][$classe_id], $espace_entre_matier, '',1,0,'C');
							$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_coef_moyenne"][$classe_id];
						}

						//permet le calcul total des coefficients
						// if(empty($moyenne_min[$id_classe][$id_periode])) {
							//$total_coef_en_calcul=$total_coef_en_calcul+$tab_bull['eleve'][$i]['coef_eleve'][$i][$m];
						//}

						// nombre de note
						// 20081118
						//if($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1') {
						if(($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1')&&($tab_modele_pdf["active_nombre_note"][$classe_id]!='1')) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
							//$valeur = $tab_bull['eleve'][$i]['nbct'][$m][$i] . "/" . $tab_bull['eleve'][$i]['groupe'][$m]['nbct'];
							$valeur = '';
							$pdf->Cell($tab_modele_pdf["largeur_nombre_note"][$classe_id], $espace_entre_matier, $valeur,1,0,'C');
							$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_nombre_note"][$classe_id];
						}

						// les moyennes eleve, classe, min, max
						$cpt_ordre = 0;
						while (!empty($ordre_moyenne[$cpt_ordre]) ) {
							//eleve
							if($tab_modele_pdf["active_moyenne_eleve"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'eleve' ) {
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',10);
								$pdf->SetFillColor($tab_modele_pdf["couleur_reperage_eleve1"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve2"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve3"][$classe_id]);

								// calcul nombre de sous affichage

								$nb_sousaffichage='1';
								//20090908 if(empty($active_coef_sousmoyene)) { $active_coef_sousmoyene = ''; }

								if($tab_modele_pdf["active_coef_sousmoyene"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; }
								if($tab_modele_pdf["active_nombre_note"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; }
								if($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]==='1') { if($tab_modele_pdf["active_moyenne_classe"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; } }
								if($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]==='1') { if($tab_modele_pdf["active_moyenne_min"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; } }
								if($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]==='1') { if($tab_modele_pdf["active_moyenne_max"][$classe_id]==='1') { $nb_sousaffichage = $nb_sousaffichage + 1; } }

								// On filtre si la moyenne est vide, on affiche seulement un tiret
								if ($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note']=="-") {
									$valeur = "-";
								}
								elseif ($tab_bull['eleve'][$i]['aid_e'][$m]['aid_statut']!="") {
									if($tab_bull['eleve'][$i]['aid_e'][$m]['aid_statut']=="other") {
										$valeur = "-";
									}
									else {
										$valeur=$tab_bull['eleve'][$i]['aid_e'][$m]['aid_statut'];
									}
								}
								else {
									$valeur = present_nombre($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
								}
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, $valeur,1,2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
								$valeur = "";

								if($tab_modele_pdf["active_coef_sousmoyene"][$classe_id]==='1') {
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',7);
									//$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'coef. '.$tab_bull['eleve'][$i]['coef_eleve'][$i][$m],'LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, '','LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
								}

								if($tab_modele_pdf["toute_moyenne_meme_col"][$classe_id]==='1') {
									// On affiche toutes les moyennes dans la m�me colonne
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',7);
									if($tab_modele_pdf["active_moyenne_classe"][$classe_id]==='1') {
										//if ($tab_bull['eleve'][$i]['moy_classe_grp'][$m]=="-") {
										if (($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_moyenne']=="-")||($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_moyenne']=="")) {
											$valeur = "-";
										}
										else {
											$valeur = present_nombre($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_moyenne'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
										}
										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'cla.'.$valeur,'LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									}
									if($tab_modele_pdf["active_moyenne_min"][$classe_id]==='1') {
										//if ($tab_bull['eleve'][$i]['moy_min_classe_grp'][$m]=="-") {
										if (($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_min']=="-")||($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_min']=="")) {
											$valeur = "-";
										} else {
											$valeur = present_nombre($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_min'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
										}
										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'min.'.$valeur,'LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									}
									if($tab_modele_pdf["active_moyenne_max"][$classe_id]==='1') {
										//if ($tab_bull['eleve'][$i]['moy_max_classe_grp'][$m]=="-") {
										if (($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_max']=="-")||($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_max']=="")) {
											$valeur = "-";
										} else {
											$valeur = present_nombre($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_max'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
										}
										$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier/$nb_sousaffichage, 'max.'.$valeur,'LRD',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
										$valeur = ''; // on remet � vide.
									}
								}

								if($tab_modele_pdf["active_nombre_note"][$classe_id]==='1') {
									$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',7);
									$espace_pour_nb_note = $espace_entre_matier/$nb_sousaffichage;
									$espace_pour_nb_note = $espace_pour_nb_note / 2;
									$valeur1 = ''; $valeur2 = '';
									/*
									if ($tab_bull['eleve'][$i]['nbct'][$m][$i]!= 0 ) {
										$valeur1 = $tab_bull['eleve'][$i]['nbct'][$m][$i].' note';
										if($tab_bull['eleve'][$i]['nbct'][$m][$i]>1){$valeur1.='s';}
										$valeur2 = 'sur '.$tab_bull['eleve'][$i]['groupe'][$m]['nbct'];
									}
									*/
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_pour_nb_note, $valeur1, 'LR',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_pour_nb_note, $valeur2, 'LRB',2,'C',$tab_modele_pdf["active_reperage_eleve"][$classe_id]);
									$valeur1 = ''; $valeur2 = '';
								}
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
								$pdf->SetFillColor(0, 0, 0);
								$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];

							} // Fin affichage �l�ve

							//classe
							if( $tab_modele_pdf["active_moyenne_classe"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'classe' ) {
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								//if ($tab_bull['eleve'][$i]['moy_classe_grp'][$m]=="-") {
								if (($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_moyenne']=="-")||($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_moyenne']=="")) {
									$valeur = "-";
								} else {
									$valeur = present_nombre($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_moyenne'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
								}
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier, $valeur,'TLRB',0,'C');
								$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
							}
							//min
							if( $tab_modele_pdf["active_moyenne_min"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'min' ) {
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
								//if ($tab_bull['eleve'][$i]['moy_min_classe_grp'][$m]=="-") {
								if (($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_min']=="-")||($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_min']=="")) {
									$valeur = "-";
								} else {
									$valeur = present_nombre($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_min'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
								}
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier, $valeur,'TLRB',0,'C');
								$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
							}
							//max
							if( $tab_modele_pdf["active_moyenne_max"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'max' ) {
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								//if ($tab_bull['eleve'][$i]['moy_max_classe_grp'][$m]== "-") {
								if (($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_max']=="-")||($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_max']=="")) {
									$valeur = "-";
								} else {
									$valeur = present_nombre($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note_max'], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
								}
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $espace_entre_matier, $valeur,'TLRB',0,'C');
								$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
							}
							//$largeur_utilise = $largeur_utilise+$largeur_moyenne;


							// rang de l'�l�ve
							if($tab_modele_pdf["active_rang"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'rang' ) {
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
								// A REVOIR: J'AI l'EFFECTIF DU GROUPE, mais faut-il compter les �l�ves ABS, DISP,...?
								//if((isset($tab_bull['eleve'][$i]['rang'][$i][$m]))&&(isset($tab_bull['eleve'][$i]['groupe'][$m]['effectif']))) {
									//$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $espace_entre_matier, $tab_bull['eleve'][$i]['rang'][$i][$m].'/'.$tab_bull['eleve'][$i]['groupe'][$m]['effectif'],1,0,'C');

								//if((isset($tab_bull['eleve'][$i]['rang'][$m][$i]))&&(isset($tab_bull['eleve'][$i]['groupe'][$m]['effectif']))) {
								//	$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $espace_entre_matier, $tab_bull['eleve'][$i]['rang'][$m][$i].'/'.$tab_bull['eleve'][$i]['groupe'][$m]['effectif_avec_note'],1,0,'C');
								//}
								//else {
									$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $espace_entre_matier, '',1,0,'C');
								//}
								$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_rang"][$classe_id];
							}

							// graphique de niveau
							if($tab_modele_pdf["active_graphique_niveau"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'niveau' ) {
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
								//$id_groupe_graph = $tab_bull['eleve'][$i]['groupe'][$m]['id'];
								// placement de l'�l�ve dans le graphique de niveau

								// AJOUT: La variable n'�tait pas initialis�e dans le bulletin_pdf_avec_modele...
								$place_eleve='';

								//if ($tab_bull['eleve'][$i]['note'][$m][$i]!="") {
								if ($tab_bull['eleve'][$i]['aid_e'][$m]['aid_note']!="") {
									/*
									if ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<5) { $place_eleve=5;}
									if (($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=5) and ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<8))  { $place_eleve=4;}
									if (($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=8) and ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<10)) { $place_eleve=3;}
									if (($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=10) and ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<12)) {$place_eleve=2;}
									if (($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=12) and ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']<15)) { $place_eleve=1;}
									if ($matiere[$ident_eleve_aff][$id_periode][$m]['moy_eleve']>=15) { $place_eleve=0;}
									*/
									if(isset($tab_bull['eleve'][$i]['aid_e'][$m]['place_eleve'])) {
										//$place_eleve=$tab_bull['eleve'][$i]['aid_e'][$m]['place_eleve'];
										$place_eleve=$tab_bull['eleve'][$i]['aid_e'][$m]['place_eleve']-1;
									}
								}
								$data_grap=array();
								if(isset($tab_bull['eleve'][$i]['aid_e'][$m]['quartile1_classe'])) {$data_grap[0]=$tab_bull['eleve'][$i]['aid_e'][$m]['quartile1_classe'];}
								if(isset($tab_bull['eleve'][$i]['aid_e'][$m]['quartile2_classe'])) {$data_grap[1]=$tab_bull['eleve'][$i]['aid_e'][$m]['quartile2_classe'];}
								if(isset($tab_bull['eleve'][$i]['aid_e'][$m]['quartile3_classe'])) {$data_grap[2]=$tab_bull['eleve'][$i]['aid_e'][$m]['quartile3_classe'];}
								if(isset($tab_bull['eleve'][$i]['aid_e'][$m]['quartile4_classe'])) {$data_grap[3]=$tab_bull['eleve'][$i]['aid_e'][$m]['quartile4_classe'];}
								if(isset($tab_bull['eleve'][$i]['aid_e'][$m]['quartile5_classe'])) {$data_grap[4]=$tab_bull['eleve'][$i]['aid_e'][$m]['quartile5_classe'];}
								if(isset($tab_bull['eleve'][$i]['aid_e'][$m]['quartile6_classe'])) {$data_grap[5]=$tab_bull['eleve'][$i]['aid_e'][$m]['quartile6_classe'];}								//if (array_sum($data_grap[$id_periode][$id_groupe_graph]) != 0) {
								if (array_sum($data_grap) != 0) {
									//$pdf->DiagBarre($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2), $tab_modele_pdf["largeur_niveau"][$classe_id], $espace_entre_matier, $data_grap[$id_periode][$id_groupe_graph], $place_eleve);
									$pdf->DiagBarre($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2), $tab_modele_pdf["largeur_niveau"][$classe_id], $espace_entre_matier, $data_grap, $place_eleve);
								}
								$place_eleve=''; // on vide la variable
								$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_niveau"][$classe_id];
							}

							//appr�ciation
							if($tab_modele_pdf["active_appreciation"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'appreciation' ) {
								// si on autorise l'affichage des sous mati�re et s'il y en a alors on les affiche
								//$id_groupe_select = $tab_bull['eleve'][$i]['groupe'][$m]['id'];
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								$X_sous_matiere = 0;
								$largeur_sous_matiere=0;

								/*
								if($tab_modele_pdf["autorise_sous_matiere"][$classe_id]==='1' and !empty($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom'])) {
									$X_sous_matiere = $X_note_moy_app+$largeur_utilise;
									$Y_sous_matiere = $Y_decal-($espace_entre_matier/2);
									$n=0;
									$largeur_texte_sousmatiere=0; $largeur_sous_matiere=0;
									while( !empty($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom'][$n]) )
									{
										$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
										$largeur_texte_sousmatiere = $pdf->GetStringWidth($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom'][$n].': '.$tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_note'][$n]);
										if($largeur_sous_matiere<$largeur_texte_sousmatiere) { $largeur_sous_matiere=$largeur_texte_sousmatiere; }
										$n = $n + 1;
									}
									if($largeur_sous_matiere!='0') { $largeur_sous_matiere = $largeur_sous_matiere + 2; }
									$n=0;
									while( !empty($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom'][$n]) )
									{
										$pdf->SetXY($X_sous_matiere, $Y_sous_matiere);
										$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
										$pdf->Cell($largeur_sous_matiere, $espace_entre_matier/count($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom']), traite_accents_utf8($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom'][$n].': '.$tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_note'][$n]),1,0,'L');
										$Y_sous_matiere = $Y_sous_matiere+$espace_entre_matier/count($tab_bull['eleve'][$i]['groupe'][$m][$i]['cn_nom']);
										$n = $n + 1;
									}
									$largeur_utilise = $largeur_utilise+$largeur_sous_matiere;
								}
								*/
								$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_decal-($espace_entre_matier/2));
								// calcul de la taille du texte des appr�ciation
								$hauteur_caractere_appreciation = 9;
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_appreciation);

								//suppression des espace en d�but et en fin
								//$app_aff = trim($tab_bull['eleve'][$i]['aid_e'][$m]['aid_appreciation']);
								$app_aff="";
								if($tab_bull['eleve'][$i]['aid_e'][$m]['message']!='') {
									$app_aff.=$tab_bull['eleve'][$i]['aid_e'][$m]['message'];
								}
								//if($app_aff!='') {$app_aff.=" ";}
								//if($tab_bull['eleve'][$i]['aid_e'][$m]['aid_nom']!='') {
								if(($tab_bull['eleve'][$i]['aid_e'][$m]['display_nom']=='y')&&($tab_bull['eleve'][$i]['aid_e'][$m]['aid_nom']!='')) {
									if($app_aff!='') {$app_aff.=" ";}
									$app_aff.=$tab_bull['eleve'][$i]['aid_e'][$m]['aid_nom'];
								}
								if($app_aff!='') {$app_aff.="\n";}
								$app_aff.=trim($tab_bull['eleve'][$i]['aid_e'][$m]['aid_appreciation']);

								fich_debug_bull("__________________________________________\n");
								fich_debug_bull("$app_aff\n");
								fich_debug_bull("__________________________________________\n");

								// DEBUT AJUSTEMENT TAILLE APPRECIATION
								$taille_texte_total = $pdf->GetStringWidth($app_aff);
								$largeur_appreciation2 = $largeur_appreciation - $largeur_sous_matiere;

								if($use_cell_ajustee=="n") {
									//$taille_texte = (($espace_entre_matier/3)*$largeur_appreciation2);
									$nb_ligne_app = '2.8';
									//$nb_ligne_app = '3.8';
									//$nb_ligne_app = '4.8';
									$taille_texte_max = $nb_ligne_app * ($largeur_appreciation2-4);
									//$taille_texte_max = $nb_ligne_app * ($largeur_appreciation2);
									$grandeur_texte='test';
	
									fich_debug_bull("\$taille_texte_total=$taille_texte_total\n");
									fich_debug_bull("\$largeur_appreciation2=$largeur_appreciation2\n");
									fich_debug_bull("\$nb_ligne_app=$nb_ligne_app\n");
									//fich_debug_bull("\$taille_texte_max = \$nb_ligne_app * (\$largeur_appreciation2-4)=$nb_ligne_app * ($largeur_appreciation2-4)=$taille_texte_max\n");
									fich_debug_bull("\$taille_texte_max = \$nb_ligne_app * (\$largeur_appreciation2)=$nb_ligne_app * ($largeur_appreciation2)=$taille_texte_max\n");
	
									while($grandeur_texte!='ok') {
										if($taille_texte_max < $taille_texte_total)
										{
											$hauteur_caractere_appreciation = $hauteur_caractere_appreciation-0.3;
											//$hauteur_caractere_appreciation = $hauteur_caractere_appreciation-0.1;
											$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_caractere_appreciation);
											$taille_texte_total = $pdf->GetStringWidth($app_aff);
										}
										else {
											$grandeur_texte='ok';
										}
									}
									$grandeur_texte='test';
									$pdf->drawTextBox(traite_accents_utf8($app_aff), $largeur_appreciation2, $espace_entre_matier, 'J', 'M', 1);
								}
								else {
									$texte=$app_aff;
									//$texte="Bla bla\nbli ".$app_aff;
									$taille_max_police=$hauteur_caractere_appreciation;
									$taille_min_police=ceil($taille_max_police/3);
	
									$largeur_dispo=$largeur_appreciation2;
									$h_cell=$espace_entre_matier;

									if(getSettingValue('suppr_balises_app_prof')=='y') {$texte=preg_replace('/<(.*)>/U','',$texte);}
									cell_ajustee(traite_accents_utf8($texte),$pdf->GetX(),$pdf->GetY(),$largeur_dispo,$h_cell,$taille_max_police,$taille_min_police,'LRBT');
								}

								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
								$largeur_utilise = $largeur_utilise + $largeur_appreciation2;
								//$largeur_utilise = 0;
							}

							$cpt_ordre = $cpt_ordre + 1;
						}
						$largeur_utilise = 0;
						// fin de boucle d'ordre
						$Y_decal = $Y_decal+($espace_entre_matier/2);
					//}
				}
			}
			// FIN DES AID AFFICH�S APRES LES MATIERES
			//======================================================


			//echo "\$tab_modele_pdf['active_moyenne'][$classe_id]=".$tab_modele_pdf["active_moyenne"][$classe_id]."<br />";

			// Ligne moyenne g�n�rale
			// bas du tableau des notes et app si les moyennes g�n�rales ne sont pas affich�es, le bas du tableau ne sera pas affich�
			if ( $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne_general"][$classe_id] === '1' ) {
				$X_note_moy_app = $tab_modele_pdf["X_note_app"][$classe_id];
				$Y_note_moy_app = $tab_modele_pdf["Y_note_app"][$classe_id]+$tab_modele_pdf["hauteur_note_app"][$classe_id]-$tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id];

				if ($affiche_deux_moy_gen==1) {
					// On a r�serv� une ligne de plus pour la moyenne g�n�rale avec coefficients 1
					$Y_note_moy_app-=$tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id];
				}

				$pdf->SetXY($X_note_moy_app, $Y_note_moy_app);
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
				$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
				//================
				// Ajout: J.Etheve
				if ($affiche_deux_moy_gen==1) {
					$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], traite_accents_utf8("Moy.g�n.coef."),1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
				}
				else {
					$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], traite_accents_utf8("Moyenne g�n�rale"),1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
				}
				//================
				$largeur_utilise = $tab_modele_pdf["largeur_matiere"][$classe_id];

				// coefficient mati�re
				//echo "\$tab_modele_pdf['active_coef_moyenne'][$classe_id]=".$tab_modele_pdf["active_coef_moyenne"][$classe_id]."<br />";
				if($tab_modele_pdf["active_coef_moyenne"][$classe_id]==='1') {
					$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
					$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);

					//$pdf->Cell($tab_modele_pdf["largeur_coef_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], "",1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);

					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
					//echo "\$tab_modele_pdf['affiche_totalpoints_sur_totalcoefs'][$classe_id]=".$tab_modele_pdf["affiche_totalpoints_sur_totalcoefs"][$classe_id]."<br />\n";
					if($tab_modele_pdf["affiche_totalpoints_sur_totalcoefs"][$classe_id]=='1') {
						$info_tot_et_coef=$tab_bull['tot_points_eleve'][$i]."/".$tab_bull['total_coef_eleve'][$i];
					}
					elseif($tab_modele_pdf["affiche_totalpoints_sur_totalcoefs"][$classe_id]=='2') {
						$info_tot_et_coef=$tab_bull['total_coef_eleve'][$i];
					}
					else {
						$info_tot_et_coef='-';
					}

					$val = $pdf->GetStringWidth($info_tot_et_coef);
					$taille_texte = $tab_modele_pdf["largeur_coef_moyenne"][$classe_id];
					$grandeur_texte='test';
					$hauteur_tmp=10;
					while($grandeur_texte!='ok') {
						if($taille_texte<$val)
						{
							$hauteur_tmp=$hauteur_tmp-0.3;
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_tmp);
							$val = $pdf->GetStringWidth($info_tot_et_coef);
						}
						else {
							$grandeur_texte='ok';
						}
					}
					$grandeur_texte='test';

					$pdf->Cell($tab_modele_pdf["largeur_coef_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], $info_tot_et_coef,1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);

					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);

					$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_coef_moyenne"][$classe_id];
				}

				// nombre de note
				// 20081118
				//if($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1') {
				if(($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1')&&($tab_modele_pdf["active_nombre_note"][$classe_id]!='1')) {
					$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
					$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
					$pdf->Cell($tab_modele_pdf["largeur_nombre_note"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], "",1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
					$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_nombre_note"][$classe_id];
				}

				$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);

				$cpt_ordre = 0;
				while ( !empty($ordre_moyenne[$cpt_ordre]) ) {
					//eleve
					if($tab_modele_pdf["active_moyenne_eleve"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'eleve' ) {
						$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',10);
						$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);

						// On a deux param�tres de couleur qui se croisent. On utilise une variable tierce.
						$utilise_couleur = $tab_modele_pdf["couleur_moy_general"][$classe_id];
						if($tab_modele_pdf["active_reperage_eleve"][$classe_id]==='1') {
							// Si on affiche une couleur sp�cifique pour les moyennes de l'�l�ve,
							// on utilise cette couleur ici aussi, quoi qu'il arrive
							$pdf->SetFillColor($tab_modele_pdf["couleur_reperage_eleve1"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve2"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve3"][$classe_id]);
							$utilise_couleur = 1;
						}

						if(($tab_bull['moy_gen_eleve'][$i]=="")||($tab_bull['moy_gen_eleve'][$i]=="-")) {
							$val_tmp="-";
						}
						else {
							//$val_tmp=present_nombre($tab_bull['moy_gen_eleve'][$i], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
							//$val_tmp=$tab_bull['moy_gen_eleve'][$i];
							$val_tmp=present_nombre(preg_replace("/,/",'.',$tab_bull['moy_gen_eleve'][$i]), $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
							/*
							$tmp_fich=fopen("/tmp/test_moy_gen.txt","a+");
							fwrite($tmp_fich,$tab_bull['eleve'][$i]['login']." present_nombre(\$tab_bull['moy_gen_eleve'][$i], \$tab_modele_pdf[\"arrondie_choix\"][$classe_id], \$tab_modele_pdf[\"nb_chiffre_virgule\"][$classe_id], \$tab_modele_pdf[\"chiffre_avec_zero\"][$classe_id])=present_nombre(".$tab_bull['moy_gen_eleve'][$i].", ".$tab_modele_pdf["arrondie_choix"][$classe_id].", ".$tab_modele_pdf["nb_chiffre_virgule"][$classe_id].",". $tab_modele_pdf["chiffre_avec_zero"][$classe_id].")=".present_nombre($tab_bull['moy_gen_eleve'][$i], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id])."\n");

							fwrite($tmp_fich,$tab_bull['eleve'][$i]['login']." present_nombre(preg_replace("/,/",'.',\$tab_bull['moy_gen_eleve'][$i]), \$tab_modele_pdf[\"arrondie_choix\"][$classe_id], \$tab_modele_pdf[\"nb_chiffre_virgule\"][$classe_id], \$tab_modele_pdf[\"chiffre_avec_zero\"][$classe_id])=".present_nombre(preg_replace("/,/",'.',$tab_bull['moy_gen_eleve'][$i]), $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id])."\n");

							fclose($tmp_fich);
							*/
						}

						//$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], present_nombre($tab_bull['moy_gen_eleve'][$i], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]),1,0,'C',$utilise_couleur);
						$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], $val_tmp,1,0,'C',$utilise_couleur);

						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
						$pdf->SetFillColor(0, 0, 0);
						$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
					}

					//classe
					if($tab_modele_pdf["active_moyenne_classe"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'classe' ) {
						$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
						$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);

						/*
						if( $total_coef_en_calcul != 0){
							$moyenne_classe = $total_moyenne_classe_en_calcul / $total_coef_en_calcul;
						}
						else{
							$moyenne_classe = '-';
						}
						*/
						if(($tab_bull['moy_generale_classe']=="")||($tab_bull['moy_generale_classe']=="-")) {
							$moyenne_classe = '-';
						}
						else{
							$moyenne_classe = present_nombre(preg_replace("/,/",'.',$tab_bull['moy_generale_classe']), $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
						}

						if ( $moyenne_classe != '-' ) {
							//$moyenne_classe=$tab_bull['moy_generale_classe'];
							$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], $moyenne_classe,1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
						} else {
							$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], '-',1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
						}
						$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
					}

					//min
					if($tab_modele_pdf["active_moyenne_min"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'min' ) {
						$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
						$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);

						/*
						if($total_coef_en_calcul != 0 and $tab_modele_pdf["affiche_moyenne_mini_general"][$classe_id] === '1' ){
							$moyenne_min = $total_moyenne_min_en_calcul / $total_coef_en_calcul;
						}
						else{
							$moyenne_min = '-';
						}
						*/

						if ($tab_bull['moy_min_classe']!='-') {
							//$moyenne_min=$tab_moy_min_classe[$classe_id][$id_periode];
							$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], present_nombre(preg_replace("/,/",'.',$tab_bull['moy_min_classe']), $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]),1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
						} else {
							$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], '-',1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
						}
						$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
					}

					//max
					if($tab_modele_pdf["active_moyenne_max"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'max' ) {
						$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
						$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);

						/*
						if($total_coef_en_calcul != 0 and $tab_modele_pdf["affiche_moyenne_maxi_general"][$classe_id] === '1' ){
							$moyenne_max = $total_moyenne_max_en_calcul / $total_coef_en_calcul;
						} else {
							$moyenne_max = '-';
						}
						*/

						if ($tab_bull['moy_max_classe']!='-') {
							$moyenne_max=$tab_bull['moy_max_classe'];
							$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], present_nombre(preg_replace("/,/",'.',$tab_bull['moy_max_classe']), $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]),1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
						} else {
							$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], '-',1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
						}
						$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
					}

					// rang de l'�l�ve
					if($tab_modele_pdf["active_rang"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'rang') {
						$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
						$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
						if ($tab_bull['rang_classe'][$i]!= 0) {
							$rang_a_afficher=$tab_bull['rang_classe'][$i].'/'.$tab_bull['eff_classe'];
						} else {
							$rang_a_afficher = "";
						}
						$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], $rang_a_afficher ,'TLRB',0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
						$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_rang"][$classe_id];
					}

					// graphique de niveau
					if($tab_modele_pdf["active_graphique_niveau"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'niveau' ) {
						$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
						$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
						// placement de l'�l�ve dans le graphique de niveau
						//if ($tab_bull['moy_gen_eleve'][$i]!="") {
						if (($tab_bull['moy_gen_eleve'][$i]!="")&&($tab_bull['moy_gen_eleve'][$i]!="-")) {
							/*
							if ($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']<5) { $place_eleve=5;}
							if (($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']>=5) and ($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']<8))  { $place_eleve=4;}
							if (($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']>=8) and ($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']<10)) { $place_eleve=3;}
							if (($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']>=10) and ($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']<12)) {$place_eleve=2;}
							if (($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']>=12) and ($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']<15)) { $place_eleve=1;}
							if ($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']>=15) { $place_eleve=0;}
							*/
							//$place_eleve=$tab_bull['place_eleve_classe'][$i];
							$place_eleve=$tab_bull['place_eleve_classe'][$i]-1;
						}
						$data_grap_classe[0]=$tab_bull['quartile1_classe_gen'];
						$data_grap_classe[1]=$tab_bull['quartile2_classe_gen'];
						$data_grap_classe[2]=$tab_bull['quartile3_classe_gen'];
						$data_grap_classe[3]=$tab_bull['quartile4_classe_gen'];
						$data_grap_classe[4]=$tab_bull['quartile5_classe_gen'];
						$data_grap_classe[5]=$tab_bull['quartile6_classe_gen'];

						if (array_sum($data_grap_classe) != 0) {
							//$pdf->DiagBarre($X_note_moy_app+$largeur_utilise, $Y_note_moy_app, $tab_modele_pdf["largeur_niveau"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], $data_grap_classe[$id_periode][$id_classe_selection], $place_eleve);
							$pdf->DiagBarre($X_note_moy_app+$largeur_utilise, $Y_note_moy_app, $tab_modele_pdf["largeur_niveau"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], $data_grap_classe, $place_eleve);
						}
						$place_eleve=''; // on vide la variable
						$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_niveau"][$classe_id];
					}
					//appr�ciation
					if($tab_modele_pdf["active_appreciation"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'appreciation' ) {
						$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
						$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
						$pdf->Cell($largeur_appreciation, $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], '','TLRB',0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
						$largeur_utilise = $largeur_utilise + $largeur_appreciation;
					}
					$cpt_ordre = $cpt_ordre + 1;
				}
				$largeur_utilise = 0;
				// fin de boucle d'ordre
				$pdf->SetFillColor(0, 0, 0);
			}

			//================
			// Ajout: J.Etheve
			// *****------------------------------------ ajout moyenne g�n�rale non coefficient�e
			if ($affiche_deux_moy_gen==1) {
				// Ligne moyenne g�n�rale coefficient�e
				//bas du tableau des note et app si les affichage des moyennes ne sont pas affich� le bas du tableau ne seras pas affich�
				
				if ( $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $tab_modele_pdf["active_moyenne_general"][$classe_id] === '1' ) {
					$X_note_moy_app = $tab_modele_pdf["X_note_app"][$classe_id];
					$Y_note_moy_app = $tab_modele_pdf["Y_note_app"][$classe_id]+$tab_modele_pdf["hauteur_note_app"][$classe_id];//-$tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id];

					// On a remont� d'une ligne la moyenne g�n�rale classique
					$Y_note_moy_app-=$tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id];

					$pdf->SetXY($X_note_moy_app, $Y_note_moy_app);
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
					$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
					$pdf->Cell($tab_modele_pdf["largeur_matiere"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], traite_accents_utf8("Moy.g�n.non coef."),1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
					$largeur_utilise = $tab_modele_pdf["largeur_matiere"][$classe_id];
	
					// coefficient mati�re
					if($tab_modele_pdf["active_coef_moyenne"][$classe_id]==='1') {
						$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
						$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);

						//$pdf->Cell($tab_modele_pdf["largeur_coef_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], "",1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);

						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
						$info_tot_et_coef=$tab_bull['tot_points_eleve1'][$i]."/".$tab_bull['total_coef_eleve1'][$i];
						$val = $pdf->GetStringWidth($info_tot_et_coef);
						$taille_texte = $tab_modele_pdf["largeur_coef_moyenne"][$classe_id];
						$grandeur_texte='test';
						$hauteur_tmp=10;
						while($grandeur_texte!='ok') {
							if($taille_texte<$val)
							{
								$hauteur_tmp=$hauteur_tmp-0.3;
								$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',$hauteur_tmp);
								$val = $pdf->GetStringWidth($info_tot_et_coef);
							}
							else {
								$grandeur_texte='ok';
							}
						}
						$grandeur_texte='test';
	
						$pdf->Cell($tab_modele_pdf["largeur_coef_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], $info_tot_et_coef,1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
	
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);


						$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_coef_moyenne"][$classe_id];
					}
	
					// nombre de note
					// 20081118
					//if($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1') {
					if(($tab_modele_pdf["active_nombre_note_case"][$classe_id]==='1')&&($tab_modele_pdf["active_nombre_note"][$classe_id]!='1')) {
						$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
						$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
						$pdf->Cell($tab_modele_pdf["largeur_nombre_note"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], "",1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
						$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_nombre_note"][$classe_id];
					}
	
					$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
	
					$cpt_ordre = 0;
					while ( !empty($ordre_moyenne[$cpt_ordre]) ) {
						//eleve
						if($tab_modele_pdf["active_moyenne_eleve"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'eleve' ) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',10);
							$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
	
							// On a deux param�tres de couleur qui se croisent. On utilise une variable tierce.
							$utilise_couleur = $tab_modele_pdf["couleur_moy_general"][$classe_id];
							if($tab_modele_pdf["active_reperage_eleve"][$classe_id]==='1') {
								// Si on affiche une couleur sp�cifique pour les moyennes de l'�l�ve,
								// on utilise cette couleur ici aussi, quoi qu'il arrive
								$pdf->SetFillColor($tab_modele_pdf["couleur_reperage_eleve1"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve2"][$classe_id], $tab_modele_pdf["couleur_reperage_eleve3"][$classe_id]);
								$utilise_couleur = 1;
							}
	
							if(($tab_bull['moy_gen_eleve_noncoef'][$i]=="")||($tab_bull['moy_gen_eleve_noncoef'][$i]=="-")) {
								$val_tmp="-";
							}
							else {
								//$val_tmp=present_nombre($tab_bull['moy_gen_eleve'][$i], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
								//$val_tmp=$tab_bull['moy_gen_eleve'][$i];
								$val_tmp=present_nombre(preg_replace("/,/",'.',$tab_bull['moy_gen_eleve_noncoef'][$i]), $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
								/*
								$tmp_fich=fopen("/tmp/test_moy_gen.txt","a+");
								fwrite($tmp_fich,$tab_bull['eleve'][$i]['login']." present_nombre(\$tab_bull['moy_gen_eleve'][$i], \$tab_modele_pdf[\"arrondie_choix\"][$classe_id], \$tab_modele_pdf[\"nb_chiffre_virgule\"][$classe_id], \$tab_modele_pdf[\"chiffre_avec_zero\"][$classe_id])=present_nombre(".$tab_bull['moy_gen_eleve'][$i].", ".$tab_modele_pdf["arrondie_choix"][$classe_id].", ".$tab_modele_pdf["nb_chiffre_virgule"][$classe_id].",". $tab_modele_pdf["chiffre_avec_zero"][$classe_id].")=".present_nombre($tab_bull['moy_gen_eleve'][$i], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id])."\n");
	
								fwrite($tmp_fich,$tab_bull['eleve'][$i]['login']." present_nombre(preg_replace("/,/",'.',\$tab_bull['moy_gen_eleve'][$i]), \$tab_modele_pdf[\"arrondie_choix\"][$classe_id], \$tab_modele_pdf[\"nb_chiffre_virgule\"][$classe_id], \$tab_modele_pdf[\"chiffre_avec_zero\"][$classe_id])=".present_nombre(preg_replace("/,/",'.',$tab_bull['moy_gen_eleve'][$i]), $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id])."\n");
	
								fclose($tmp_fich);
								*/
							}
	
							//$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], present_nombre($tab_bull['moy_gen_eleve'][$i], $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]),1,0,'C',$utilise_couleur);
							$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], $val_tmp,1,0,'C',$utilise_couleur);
	
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
							$pdf->SetFillColor(0, 0, 0);
							$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
						}
	
						//classe
						if($tab_modele_pdf["active_moyenne_classe"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'classe' ) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
							$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
	
							/*
							if( $total_coef_en_calcul != 0){
								$moyenne_classe = $total_moyenne_classe_en_calcul / $total_coef_en_calcul;
							}
							else{
								$moyenne_classe = '-';
							}
							*/
							if(($tab_bull['moy_generale_classe_noncoef']=="")||($tab_bull['moy_generale_classe_noncoef']=="-")) {
								$moyenne_classe = '-';
							}
							else{
								$moyenne_classe = present_nombre(preg_replace("/,/",'.',$tab_bull['moy_generale_classe_noncoef']), $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]);
							}
	
							if ( $moyenne_classe != '-' ) {
								//$moyenne_classe=$tab_bull['moy_generale_classe'];
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], $moyenne_classe,1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
							} else {
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], '-',1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
							}
							$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
						}
	
						//min
						if($tab_modele_pdf["active_moyenne_min"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'min' ) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
							$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
	
							/*
							if($total_coef_en_calcul != 0 and $tab_modele_pdf["affiche_moyenne_mini_general"][$classe_id] === '1' ){
								$moyenne_min = $total_moyenne_min_en_calcul / $total_coef_en_calcul;
							}
							else{
								$moyenne_min = '-';
							}
							*/
	
							if ($tab_bull['moy_min_classe']!='-') {
								//$moyenne_min=$tab_moy_min_classe[$classe_id][$id_periode];
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], present_nombre(preg_replace("/,/",'.',$tab_bull['moy_min_classe_noncoef']), $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]),1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
							} else {
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], '-',1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
							}
							$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
						}
	
						//max
						if($tab_modele_pdf["active_moyenne_max"][$classe_id]==='1' and $tab_modele_pdf["active_moyenne"][$classe_id] === '1' and $ordre_moyenne[$cpt_ordre] === 'max' ) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
							$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
	
							/*
							if($total_coef_en_calcul != 0 and $tab_modele_pdf["affiche_moyenne_maxi_general"][$classe_id] === '1' ){
								$moyenne_max = $total_moyenne_max_en_calcul / $total_coef_en_calcul;
							} else {
								$moyenne_max = '-';
							}
							*/
	
							if ($tab_bull['moy_max_classe_noncoef']!='-') {
								$moyenne_max=$tab_bull['moy_max_classe_noncoef'];
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], present_nombre(preg_replace("/,/",'.',$tab_bull['moy_max_classe_noncoef']), $tab_modele_pdf["arrondie_choix"][$classe_id], $tab_modele_pdf["nb_chiffre_virgule"][$classe_id], $tab_modele_pdf["chiffre_avec_zero"][$classe_id]),1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
							} else {
								$pdf->Cell($tab_modele_pdf["largeur_d_une_moyenne"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], '-',1,0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
							}
							$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_d_une_moyenne"][$classe_id];
						}
	
						// rang de l'�l�ve
						if($tab_modele_pdf["active_rang"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'rang') {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
							$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
							$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
							if ($tab_bull['rang_classe'][$i]!= 0) {
								$rang_a_afficher=$tab_bull['rang_classe'][$i].'/'.$tab_bull['eff_classe'];
							} else {
								$rang_a_afficher = "";
							}
							$pdf->Cell($tab_modele_pdf["largeur_rang"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], $rang_a_afficher ,'TLRB',0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
							$largeur_utilise = $largeur_utilise + $tab_modele_pdf["largeur_rang"][$classe_id];
						}
	
						// graphique de niveau
						if($tab_modele_pdf["active_graphique_niveau"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'niveau' ) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
							$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
							// placement de l'�l�ve dans le graphique de niveau
							//if ($tab_bull['moy_gen_eleve'][$i]!="") {
							if (($tab_bull['moy_gen_eleve'][$i]!="")&&($tab_bull['moy_gen_eleve'][$i]!="-")) {
								/*
								if ($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']<5) { $place_eleve=5;}
								if (($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']>=5) and ($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']<8))  { $place_eleve=4;}
								if (($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']>=8) and ($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']<10)) { $place_eleve=3;}
								if (($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']>=10) and ($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']<12)) {$place_eleve=2;}
								if (($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']>=12) and ($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']<15)) { $place_eleve=1;}
								if ($info_bulletin[$ident_eleve_aff][$id_periode]['moy_general_eleve']>=15) { $place_eleve=0;}
								*/
								//$place_eleve=$tab_bull['place_eleve_classe'][$i];
								$place_eleve=$tab_bull['place_eleve_classe'][$i]-1;
							}
							$data_grap_classe[0]=$tab_bull['quartile1_classe_gen'];
							$data_grap_classe[1]=$tab_bull['quartile2_classe_gen'];
							$data_grap_classe[2]=$tab_bull['quartile3_classe_gen'];
							$data_grap_classe[3]=$tab_bull['quartile4_classe_gen'];
							$data_grap_classe[4]=$tab_bull['quartile5_classe_gen'];
							$data_grap_classe[5]=$tab_bull['quartile6_classe_gen'];
	
							if (array_sum($data_grap_classe) != 0) {
								//$pdf->DiagBarre($X_note_moy_app+$largeur_utilise, $Y_note_moy_app, $tab_modele_pdf["largeur_niveau"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], $data_grap_classe[$id_periode][$id_classe_selection], $place_eleve);
								$pdf->DiagBarre($X_note_moy_app+$largeur_utilise, $Y_note_moy_app, $tab_modele_pdf["largeur_niveau"][$classe_id], $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], $data_grap_classe, $place_eleve);
							}
							$place_eleve=''; // on vide la variable
							$largeur_utilise = $largeur_utilise+$tab_modele_pdf["largeur_niveau"][$classe_id];
						}
						//appr�ciation
						if($tab_modele_pdf["active_appreciation"][$classe_id]==='1' and $ordre_moyenne[$cpt_ordre] === 'appreciation' ) {
							$pdf->SetXY($X_note_moy_app+$largeur_utilise, $Y_note_moy_app);
							$pdf->SetFillColor($tab_modele_pdf["couleur_moy_general1"][$classe_id], $tab_modele_pdf["couleur_moy_general2"][$classe_id], $tab_modele_pdf["couleur_moy_general3"][$classe_id]);
							$pdf->Cell($largeur_appreciation, $tab_modele_pdf["hauteur_entete_moyenne_general"][$classe_id], '','TLRB',0,'C', $tab_modele_pdf["couleur_moy_general"][$classe_id]);
							$largeur_utilise = $largeur_utilise + $largeur_appreciation;
						}
						$cpt_ordre = $cpt_ordre + 1;
					}
					$largeur_utilise = 0;
					// fin de boucle d'ordre
					$pdf->SetFillColor(0, 0, 0);
				}
			}
			// *****-------------------------------------fin moyenne g�n�rale non coefficient�e ---
			//================



		}




			// =============== bloc absence ==================
			if($tab_modele_pdf["active_bloc_absence"][$classe_id]==='1') {
				$pdf->SetXY($tab_modele_pdf["X_absence"][$classe_id], $tab_modele_pdf["Y_absence"][$classe_id]);
				$origine_Y_absence = $tab_modele_pdf["Y_absence"][$classe_id];
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',8);
				$info_absence='';
				if($tab_bull['eleve'][$i]['eleve_absences'] != '?') {
					if($tab_bull['eleve'][$i]['eleve_absences'] == '0')
					{
						$info_absence="<i>Aucune demi-journ�e d'absence</i>.";
					} else {
						$info_absence="<i>Nombre de demi-journ�es d'absence ";
						if ($tab_bull['eleve'][$i]['eleve_nj'] == '0' or $tab_bull['eleve'][$i]['eleve_nj'] == '?') {
							$info_absence = $info_absence."justifi�es ";
						}
						$info_absence = $info_absence.": </i><b>".$tab_bull['eleve'][$i]['eleve_absences']."</b>";
						if ($tab_bull['eleve'][$i]['eleve_nj'] != '0' and $tab_bull['eleve'][$i]['eleve_nj'] != '?')
						{
							$info_absence = $info_absence." (dont <b>".$tab_bull['eleve'][$i]['eleve_nj']."</b> non justifi�e";
							if ($tab_bull['eleve'][$i]['eleve_nj'] != '1') { $info_absence = $info_absence."s"; }
							$info_absence = $info_absence.")";
						}
						$info_absence = $info_absence.".";
					}
				}
				if($tab_bull['eleve'][$i]['eleve_retards'] != '0' and $tab_bull['eleve'][$i]['eleve_retards'] != '?')
				{
					$info_absence = $info_absence."<i> Nombre de retards : </i><b>".$tab_bull['eleve'][$i]['eleve_retards']."</b>";
				}

				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);

				$info_absence = $info_absence." (C.P.E. charg�";
				if($tab_bull['eleve'][$i]['cperesp_civilite']!="M.") {
					$info_absence = $info_absence."e";
				}
				/*
				$sql="SELECT civilite FROM utilisateurs WHERE login='".$cperesp_login[$i]."'";
				$res_civi=mysql_query($sql);
				if(mysql_num_rows($res_civi)>0){
					$lig_civi=mysql_fetch_object($res_civi);
					if($lig_civi->civilite!="M."){
						$info_absence = $info_absence."e";
					}
				}
				*/
				$info_absence = $info_absence." du suivi : <i>".affiche_utilisateur($tab_bull['eleve'][$i]['cperesp_login'],$tab_bull['id_classe'])."</i>)";
				//$pdf->MultiCellTag(200, 5, $info_absence, '', 'J', '');
				//$pdf->MultiCellTag(200, 5, traite_accents_utf8($info_absence), '', 'J', '');
				//$pdf->MultiCellTag($tab_modele_pdf["largeur_cadre_absences"][$classe_id], 5, traite_accents_utf8($info_absence), '', 'J', '');
				$pdf->ext_MultiCellTag($tab_modele_pdf["largeur_cadre_absences"][$classe_id], 5, traite_accents_utf8($info_absence), '', 'J', '');

				//=========================
				// MODIF: boireaus 20081220
				// D�sactivation de ce qui provoquait un d�calage progressif du bloc Avis du conseil,...
				//if ( isset($Y_avis_cons_init) ) { $tab_modele_pdf["Y_avis_cons"][$classe_id] = $Y_avis_cons_init; }
				//if ( isset($Y_sign_chef_init) ) { $tab_modele_pdf["Y_sign_chef"][$classe_id] = $Y_sign_chef_init; }
				//=========================
				// Il y a une ligne pour les absences, retards,... donc on compte une ligne (+0.5) de d�calage pour le bloc Avis du conseil et celui de la signature du chefetab
				//if ( !isset($Y_avis_cons_init) ) { $Y_avis_cons_init = $tab_modele_pdf["Y_avis_cons"][$classe_id] + 0.5; }
				//if ( !isset($Y_sign_chef_init) ) { $Y_sign_chef_init = $tab_modele_pdf["Y_sign_chef"][$classe_id] + 0.5; }
				$Y_avis_cons_init = $tab_modele_pdf["Y_avis_cons"][$classe_id] + 0.5;
				$Y_sign_chef_init = $tab_modele_pdf["Y_sign_chef"][$classe_id] + 0.5;

				//=========================
				// MODIF: boireaus 20081220
				// D�sactivation de ce qui provoquait une r�duction progressive de la hauteur du bloc Avis du conseil,...
				//if ( isset($hauteur_avis_cons_init) ) { $tab_modele_pdf["hauteur_avis_cons"][$classe_id] = $hauteur_avis_cons_init; }
				//if ( isset($hauteur_sign_chef_init) ) { $tab_modele_pdf["hauteur_sign_chef"][$classe_id] = $hauteur_sign_chef_init; }
				//=========================
				//if ( !isset($hauteur_avis_cons_init) ) { $hauteur_avis_cons_init = $tab_modele_pdf["hauteur_avis_cons"][$classe_id] - 0.5; }
				//if ( !isset($hauteur_sign_chef_init) ) { $hauteur_sign_chef_init = $tab_modele_pdf["hauteur_sign_chef"][$classe_id] - 0.5; }
				$hauteur_avis_cons_init = $tab_modele_pdf["hauteur_avis_cons"][$classe_id] - 0.5;
				$hauteur_sign_chef_init = $tab_modele_pdf["hauteur_sign_chef"][$classe_id] - 0.5;

$hauteur_pris_app_abs=0;

				if($tab_bull['eleve'][$i]['appreciation_absences'] != "")
				{
					// supprimer les espaces
					$text_absences_appreciation = trim(str_replace(array("\r\n","\r","\n"), ' ', unhtmlentities($tab_bull['eleve'][$i]['appreciation_absences'])));
					$info_absence_appreciation = "<i>Avis CPE :</i> <b>".$text_absences_appreciation."</b>";
					$text_absences_appreciation = '';
					$pdf->SetXY($tab_modele_pdf["X_absence"][$classe_id], $tab_modele_pdf["Y_absence"][$classe_id]+4);
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',8);
					//$pdf->MultiCellTag(200, 3, traite_accents_utf8($info_absence_appreciation), '', 'J', '');
					//$pdf->MultiCellTag($tab_modele_pdf["largeur_cadre_absences"][$classe_id], 3, traite_accents_utf8($info_absence_appreciation), '', 'J', '');
					$pdf->ext_MultiCellTag($tab_modele_pdf["largeur_cadre_absences"][$classe_id], 3, traite_accents_utf8($info_absence_appreciation), '', 'J', '');
					$val = $pdf->GetStringWidth($info_absence_appreciation);
					// nombre de lignes que prend la remarque cpe
					//Arrondi � l'entier sup�rieur : ceil()
					$nb_ligne = 1;
					$nb_ligne = ceil($val / 200);
					$hauteur_pris = $nb_ligne * 3;

$hauteur_pris_app_abs=$hauteur_pris;

					//$tab_modele_pdf["Y_avis_cons"][$classe_id] = $tab_modele_pdf["Y_avis_cons"][$classe_id] + $hauteur_pris;
					$Y_avis_cons_init=$Y_avis_cons_init+$hauteur_pris;

					//$tab_modele_pdf["hauteur_avis_cons"][$classe_id] = $tab_modele_pdf["hauteur_avis_cons"][$classe_id] - ( $hauteur_pris + 0.5 );
					$hauteur_avis_cons_init=$hauteur_avis_cons_init - ( $hauteur_pris + 0.5 );

					//$tab_modele_pdf["Y_sign_chef"][$classe_id] = $tab_modele_pdf["Y_sign_chef"][$classe_id] + $hauteur_pris;
					$Y_sign_chef_init=$Y_sign_chef_init+$hauteur_pris;

					//$tab_modele_pdf["hauteur_sign_chef"][$classe_id] = $tab_modele_pdf["hauteur_sign_chef"][$classe_id] - ( $hauteur_pris + 0.5 );
					$hauteur_sign_chef_init=$hauteur_sign_chef_init - ( $hauteur_pris + 0.5 );

					$hauteur_pris = 0;
				}
				else {
					if($Y_avis_cons_init!=$tab_modele_pdf["Y_avis_cons"][$classe_id])
					{
						//$tab_modele_pdf["Y_avis_cons"][$classe_id] = $tab_modele_pdf["Y_avis_cons"][$classe_id] - $hauteur_pris;
						$Y_avis_cons_init=$Y_avis_cons_init-$hauteur_pris;

						//$tab_modele_pdf["hauteur_avis_cons"][$classe_id] = $tab_modele_pdf["hauteur_avis_cons"][$classe_id] + $hauteur_pris;
						$hauteur_avis_cons_init=$hauteur_avis_cons_init + $hauteur_pris;

						//$tab_modele_pdf["Y_sign_chef"][$classe_id] = $tab_modele_pdf["Y_sign_chef"][$classe_id] - $hauteur_pris;
						$Y_sign_chef_init=$Y_sign_chef_init-$hauteur_pris;

						//$tab_modele_pdf["hauteur_sign_chef"][$classe_id] = $tab_modele_pdf["hauteur_sign_chef"][$classe_id] + $hauteur_pris;
						$hauteur_sign_chef_init=$hauteur_sign_chef_init + $hauteur_pris;

						$hauteur_pris = 0;
					}
				}
				$info_absence = '';
				$info_absence_appreciation = '';
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
			}

			// sinon, si le bloc absence n'est pas activ�
			if($tab_modele_pdf["active_bloc_absence"][$classe_id] != '1') {
				//=========================
				// MODIF: boireaus 20081220
				// D�sactivation de ce qui provoquait un d�calage progressif du bloc Avis du conseil,...
				//if ( isset($Y_avis_cons_init) ) { $tab_modele_pdf["Y_avis_cons"][$classe_id] = $Y_avis_cons_init; }
				//if ( isset($Y_sign_chef_init) ) { $tab_modele_pdf["Y_sign_chef"][$classe_id] = $Y_sign_chef_init; }
				//=========================
				//if ( !isset($Y_avis_cons_init) ) { $Y_avis_cons_init = $tab_modele_pdf["Y_avis_cons"][$classe_id]; }
				//if ( !isset($Y_sign_chef_init) ) { $Y_sign_chef_init = $tab_modele_pdf["Y_sign_chef"][$classe_id]; }
				$Y_avis_cons_init = $tab_modele_pdf["Y_avis_cons"][$classe_id];
				$Y_sign_chef_init = $tab_modele_pdf["Y_sign_chef"][$classe_id];

				$hauteur_avis_cons_init = $tab_modele_pdf["hauteur_avis_cons"][$classe_id] - 0.5;
				$hauteur_sign_chef_init = $tab_modele_pdf["hauteur_sign_chef"][$classe_id] - 0.5;
			}
			// fin

			//=========================
			// MODIF: boireaus 20081220
			/*
			if($Y_avis_cons_init!=$tab_modele_pdf["Y_avis_cons"][$classe_id]) {
				$Y_avis_cons[$classe_id] = $tab_modele_pdf["Y_avis_cons"][$classe_id] + 0.5;
				$Y_sign_chef[$classe_id] = $tab_modele_pdf["Y_sign_chef"][$classe_id] + 0.5;
			}
			*/
			//=========================

			// ================ bloc avis du conseil de classe =================
			if($tab_modele_pdf["active_bloc_avis_conseil"][$classe_id]==='1') {
				if($tab_modele_pdf["cadre_avis_cons"][$classe_id]!=0) {
					//$pdf->Rect($tab_modele_pdf["X_avis_cons"][$classe_id], $tab_modele_pdf["Y_avis_cons"][$classe_id], $tab_modele_pdf["longeur_avis_cons"][$classe_id], $tab_modele_pdf["hauteur_avis_cons"][$classe_id], 'D');
					$pdf->Rect($tab_modele_pdf["X_avis_cons"][$classe_id], $Y_avis_cons_init, $tab_modele_pdf["longeur_avis_cons"][$classe_id], $hauteur_avis_cons_init, 'D');
				}
				//$pdf->SetXY($tab_modele_pdf["X_avis_cons"][$classe_id],$tab_modele_pdf["Y_avis_cons"][$classe_id]);
				$pdf->SetXY($tab_modele_pdf["X_avis_cons"][$classe_id],$Y_avis_cons_init);

				if ( $tab_modele_pdf["taille_titre_bloc_avis_conseil"][$classe_id] != '' and $tab_modele_pdf["taille_titre_bloc_avis_conseil"][$classe_id] < '15' ) {
					$taille = $tab_modele_pdf["taille_titre_bloc_avis_conseil"][$classe_id];
				} else {
					$taille = '10';
				}
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',$taille);
				if ( $tab_modele_pdf["titre_bloc_avis_conseil"][$classe_id] != '' ) {
					$tt_avis = $tab_modele_pdf["titre_bloc_avis_conseil"][$classe_id];
				} else {
					$tt_avis = 'Avis du Conseil de classe :';
				}

//$tt_avis.='\n$tab_modele_pdf["taille_titre_bloc_avis_conseil"][$classe_id]='.$tab_modele_pdf["taille_titre_bloc_avis_conseil"][$classe_id]."\n";
//$tt_avis='$taille='.$taille."\n";

//$tt_avis='$tab_modele_pdf["longeur_avis_cons"]['.$classe_id.']='.$tab_modele_pdf["longeur_avis_cons"][$classe_id]."\n";

				$pdf->Cell($tab_modele_pdf["longeur_avis_cons"][$classe_id],5, $tt_avis,0,2,'');

				//$pdf->SetXY($tab_modele_pdf["X_avis_cons"][$classe_id]+2.5,$tab_modele_pdf["Y_avis_cons"][$classe_id]+5);
				$pdf->SetXY($tab_modele_pdf["X_avis_cons"][$classe_id]+2.5,$Y_avis_cons_init+5);

				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
				//$texteavis = $tab_bull['avis'][$i];
				$texteavis = preg_replace("/&#8217;/","'", $tab_bull['avis'][$i]);
				// ***** AJOUT POUR LES MENTIONS *****
				//$textmention = $tab_bull['id_mention'][$i];

				if((!isset($tableau_des_mentions_sur_le_bulletin))||(!is_array($tableau_des_mentions_sur_le_bulletin))||(count($tableau_des_mentions_sur_le_bulletin)==0)) {
					$tableau_des_mentions_sur_le_bulletin=get_mentions($classe_id);
				}

				if(isset($tableau_des_mentions_sur_le_bulletin[$tab_bull['id_mention'][$i]])) {
					$textmention=$tableau_des_mentions_sur_le_bulletin[$tab_bull['id_mention'][$i]];
				}
				else {$textmention="-";}
				// ***** FIN DE L'AJOUT POUR LES MENTIONS *****

				//$pdf->drawTextBox(traite_accents_utf8($texteavis), $tab_modele_pdf["longeur_avis_cons"][$classe_id]-5, $tab_modele_pdf["hauteur_avis_cons"][$classe_id]-10, 'J', 'M', 0);

				//$avec_coches_mentions="y";
				//if($avec_coches_mentions=="y") {
				if($tab_modele_pdf["affich_coches_mentions"][$classe_id]!="n") {

					if((!isset($tableau_des_mentions_sur_le_bulletin))||(!is_array($tableau_des_mentions_sur_le_bulletin))||(count($tableau_des_mentions_sur_le_bulletin)==0)) {
						$tableau_des_mentions_sur_le_bulletin=get_mentions($classe_id);
					}

					if(count($tableau_des_mentions_sur_le_bulletin)>0) {
						$marge_droite_avis_cons=40;
					}
					else {
						$marge_droite_avis_cons=5;
					}
				}
				else {
					$marge_droite_avis_cons=5;
					//if(($textmention!="")&&($textmention!="-")) {
					if(($tab_modele_pdf["affich_mentions"][$classe_id]!="n")&&($textmention!="")&&($textmention!="-")) {
						//$texteavis.="\n".traduction_mention($textmention);
						if($use_cell_ajustee=="n") {
							if($tab_modele_pdf["affich_intitule_mentions"][$classe_id]!="n") {
								$texteavis.="\n".ucfirst($gepi_denom_mention)." : ";
							}
							$texteavis.=$textmention;
						}
						else {
							if($tab_modele_pdf["affich_intitule_mentions"][$classe_id]!="n") {
								$texteavis.="\n"."<b>".ucfirst($gepi_denom_mention)." :</b> ";
							}
							$texteavis.=$textmention;
						}
					}
				}

				if($use_cell_ajustee=="n") {
					$pdf->drawTextBox(traite_accents_utf8($texteavis), $tab_modele_pdf["longeur_avis_cons"][$classe_id]-$marge_droite_avis_cons, $hauteur_avis_cons_init-10, 'J', 'M', 0);
				}
				else {
					$texte=$texteavis;
					$taille_max_police=10;
					$taille_min_police=ceil($taille_max_police/3);

					$largeur_dispo=$tab_modele_pdf["longeur_avis_cons"][$classe_id]-$marge_droite_avis_cons;
					$h_cell=$hauteur_avis_cons_init-10;

					cell_ajustee(traite_accents_utf8($texte),$pdf->GetX(),$pdf->GetY(),$largeur_dispo,$h_cell,$taille_max_police,$taille_min_police,'');
				}

				//=========================
				// MODIF: boireaus 20081220
				// DEBUG:
				//$pdf->drawTextBox(traite_accents_utf8($texteavis." \$Y_avis_cons_init=".$Y_avis_cons_init." \$tab_modele_pdf[\"hauteur_avis_cons\"][$classe_id]=".$tab_modele_pdf["hauteur_avis_cons"][$classe_id]." \$hauteur_pris_app_abs=".$hauteur_pris_app_abs), $tab_modele_pdf["longeur_avis_cons"][$classe_id]-5, $tab_modele_pdf["hauteur_avis_cons"][$classe_id]-10, 'J', 'M', 0);
				//=========================
				$X_pp_aff=$tab_modele_pdf["X_avis_cons"][$classe_id];

				//$Y_pp_aff=$tab_modele_pdf["Y_avis_cons"][$classe_id]+$tab_modele_pdf["hauteur_avis_cons"][$classe_id]-5;
				$Y_pp_aff=$Y_avis_cons_init+$hauteur_avis_cons_init-5;

				$pdf->SetXY($X_pp_aff,$Y_pp_aff);
				if ( $tab_modele_pdf["taille_profprincipal_bloc_avis_conseil"][$classe_id] != '' and $tab_modele_pdf["taille_profprincipal_bloc_avis_conseil"][$classe_id] < '15' ) {
					$taille = $tab_modele_pdf["taille_profprincipal_bloc_avis_conseil"][$classe_id];
				} else {
					$taille = '10';
				}
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',$taille);
				// Le nom du professeur principal
				$pp_classe[$i]="";
				//if(isset($tab_bull['eleve'][$i]['pp']['login'])) {
				if(isset($tab_bull['eleve'][$i]['pp'][0]['login'])) {
					$pp_classe[$i]="<b>".ucfirst($gepi_prof_suivi)."</b> : ";
					$pp_classe[$i].="<i>".affiche_utilisateur($tab_bull['eleve'][$i]['pp'][0]['login'],$tab_bull['eleve'][$i]['id_classe'])."</i>";
					for($i_pp=1;$i_pp<count($tab_bull['eleve'][$i]['pp']);$i_pp++) {
						$pp_classe[$i].=", ";
						$pp_classe[$i].="<i>".affiche_utilisateur($tab_bull['eleve'][$i]['pp']['login'],$tab_bull['eleve'][$i]['id_classe'])."</i>";
					}
				}
				else {
					$pp_classe[$i]="";
				}
				//$pdf->MultiCellTag(200, 5, traite_accents_utf8($pp_classe[$i]), '', 'J', '');
				$pdf->ext_MultiCellTag(200, 5, traite_accents_utf8($pp_classe[$i]), '', 'J', '');
			}

			//if($avec_coches_mentions=="y") {
			if($tab_modele_pdf["affich_coches_mentions"][$classe_id]!="n") {
				// ***** AJOUT POUR LES MENTIONS *****
				// Essai pour ajouter un bloc renseignant les mentions du CC
				// A COMPLETER...
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',9);
				$X_pp_aff=$tab_modele_pdf["X_avis_cons"][$classe_id]+$tab_modele_pdf["longeur_avis_cons"][$classe_id]-35;
				$Y_pp_aff=$tab_modele_pdf["Y_avis_cons"][$classe_id]+5;
				$pdf->SetXY($X_pp_aff,$Y_pp_aff);

				/*
				$pdf->Cell(35,4, 'F�licitations      ',0,2,'R');
				$pdf->Cell(35,4, 'Mention honorable      ',0,2,'R');
				$pdf->Cell(35,4, 'Encouragements      ',0,2,'R');
				*/
				if((!isset($tableau_des_mentions_sur_le_bulletin))||(!is_array($tableau_des_mentions_sur_le_bulletin))||(count($tableau_des_mentions_sur_le_bulletin)==0)) {
					$tableau_des_mentions_sur_le_bulletin=get_mentions($classe_id);
				}

				//for($loop_mention=0;$loop_mention<count($tableau_des_mentions_sur_le_bulletin);$loop_mention++) {
				$loop_mention=0;
				foreach($tableau_des_mentions_sur_le_bulletin as $key_mention => $value_mention) {
					//$pdf->Cell(35,4, $value_mention,0,2,'R');
					$pdf->Cell(35,4, $value_mention,0,2,'L');
					$loop_mention++;
				}

				/*
				$pdf->Rect($X_pp_aff+30, $Y_pp_aff+0.3, 2.4, 3);
				$pdf->Rect($X_pp_aff+30, $Y_pp_aff+4.3, 2.4, 3);
				$pdf->Rect($X_pp_aff+30, $Y_pp_aff+8.3, 2.4, 3);
				$pdf->Rect($X_pp_aff, $Y_pp_aff+0.1, 0.01, 12);
				*/
				//for($loop_mention=0;$loop_mention<count($tableau_des_mentions_sur_le_bulletin);$loop_mention++) {
				$loop_mention=0;
				foreach($tableau_des_mentions_sur_le_bulletin as $key_mention => $value_mention) {
					$pdf->Rect($X_pp_aff+30, $Y_pp_aff+4*$loop_mention+0.3, 2.4, 3);

					if($key_mention==$tab_bull['id_mention'][$i]) {
						$pdf->SetXY($X_pp_aff-1.73,$Y_pp_aff+$loop_mention*4);
						$pdf->Cell(35,4, 'X',0,2,'R');
					}
					$loop_mention++;
				}
				$pdf->Rect($X_pp_aff, $Y_pp_aff+0.1, 0.01, $loop_mention*4);
	
				/*
				// Si f�licitations (� modifier...)
				if($textmention=="F") {
					$pdf->SetXY($X_pp_aff-1.73,$Y_pp_aff);
					$pdf->Cell(35,4, 'X',0,2,'R');
				}
				// Si mention honorable (� modifier...)
				if($textmention=="M") {
					$pdf->SetXY($X_pp_aff-1.73,$Y_pp_aff+4);
					$pdf->Cell(35,4, 'X',0,2,'R');
				}
				// Si encouragements (� modifier...)
				if($textmention=="E") {
					$pdf->SetXY($X_pp_aff-1.73,$Y_pp_aff+8);
					$pdf->Cell(35,4, 'X',0,2,'R');
				}
				*/
				// Fin de l'essai
				// ***** FIN DE L'AJOUT POUR LES MENTIONS *****
			}

			// ======================= bloc du pr�sident du conseil de classe ================
			if( $tab_modele_pdf["active_bloc_chef"][$classe_id] === '1' ) {
				if( $tab_modele_pdf["cadre_sign_chef"][$classe_id] != 0 ) {
					//$pdf->Rect($tab_modele_pdf["X_sign_chef"][$classe_id], $tab_modele_pdf["Y_sign_chef"][$classe_id], $tab_modele_pdf["longeur_sign_chef"][$classe_id], $tab_modele_pdf["hauteur_sign_chef"][$classe_id], 'D');
					$pdf->Rect($tab_modele_pdf["X_sign_chef"][$classe_id], $Y_sign_chef_init, $tab_modele_pdf["longeur_sign_chef"][$classe_id], $hauteur_sign_chef_init, 'D');
				}
				//$pdf->SetXY($tab_modele_pdf["X_sign_chef"][$classe_id],$tab_modele_pdf["Y_sign_chef"][$classe_id]);
				$pdf->SetXY($tab_modele_pdf["X_sign_chef"][$classe_id],$Y_sign_chef_init);

				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'',10);
				if( $tab_modele_pdf["affichage_haut_responsable"][$classe_id] === '1' ) {
					if ( $tab_modele_pdf["affiche_fonction_chef"][$classe_id] === '1' ){
						if ( $tab_modele_pdf["taille_texte_fonction_chef"][$classe_id] != '' and $tab_modele_pdf["taille_texte_fonction_chef"][$classe_id] != '0' and $tab_modele_pdf["taille_texte_fonction_chef"][$classe_id] < '15' ) {
							$taille = $tab_modele_pdf["taille_texte_fonction_chef"][$classe_id];
						} else {
							$taille = '10';
						}
						$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',$taille);
						$pdf->Cell($tab_modele_pdf["longeur_sign_chef"][$classe_id],5, traite_accents_utf8($tab_bull['formule']),0,2,'');
					}
					if ( $tab_modele_pdf["taille_texte_identitee_chef"][$classe_id] != '' and $tab_modele_pdf["taille_texte_identitee_chef"][$classe_id] != '0' and $tab_modele_pdf["taille_texte_identitee_chef"][$classe_id] < '15' ) {
						$taille = $tab_modele_pdf["taille_texte_identitee_chef"][$classe_id];
					} else {
						$taille_avis = '8';
					}
					$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'I',$taille);
					$pdf->Cell($tab_modele_pdf["longeur_sign_chef"][$classe_id],5, traite_accents_utf8($tab_bull['suivi_par']),0,2,'');
				} else {
					//$pdf->MultiCell($longeur_sign_chef[$classe_id],5, "Visa du Chef d'�tablissement\nou de son d�l�gu�",0,2,'');
					$pdf->MultiCell($tab_modele_pdf["longeur_sign_chef"][$classe_id],5, traite_accents_utf8("Visa du Chef d'�tablissement\nou de son d�l�gu�"),0,2,'');
					//$pdf->ext_MultiCell($tab_modele_pdf["longeur_sign_chef"][$classe_id],5, traite_accents_utf8("Visa du Chef d'�tablissement\nou de son d�l�gu�"),0,2,'');
				}
			}

//		}

		// Insertion du relev� de notes si r�clam�:
		/*
		if(count($tab_rel)!=0) {
			releve_pdf($tab_rel,$i);
		}
		*/
		if(count($tab_rel)!=0) {
			$temoin_releve_trouve='n';
			if(isset($tab_rel['eleve'])) {
				//echo "\$tab_bull['eleve'][$i]['login']=".$tab_bull['eleve'][$i]['login']."<br />";
				//for($k=0;$k<count($tab_rel['eleve']);$k++) {
				//for($k=0;$k<$tab_bull['eff_total_classe'];$k++) {
				for($k=0;$k<count($tab_rel['eleve']);$k++) {
					//echo "\$tab_rel['eleve'][$k]['login']=".$tab_rel['eleve'][$k]['login']."<br />";
					if(isset($tab_rel['eleve'][$k]['login'])) {
						if($tab_rel['eleve'][$k]['login']==$tab_bull['eleve'][$i]['login']) {
							releve_pdf($tab_rel,$k);
							$temoin_releve_trouve='y';
							break;
						}
					}
				}
			}
			/*
			else {
				echo "<p style='color:red;'>Il semble que le tableau des relev�s de notes soit vide.</p>\n";
			}
			*/

			if($temoin_releve_trouve=='n') {
				$pdf->AddPage("P");
				$pdf->SetFontSize(10);
				$pdf->SetXY(20,20);
				$pdf->SetFont($tab_modele_pdf["caractere_utilse"][$classe_id],'B',14);
				$pdf->Cell(90,7,"Relev� de notes non trouv� pour ".strtoupper($tab_bull['eleve'][$i]['nom'])." ".ucfirst($tab_bull['eleve'][$i]['prenom']),0,2,'');

			}
		}

	}
}


function releve_pdf_20090429($tab_rel,$i) {
	global $annee_scolaire,
		$gepi_prof_suivi,

		$RneEtablissement,
		$gepiSchoolName,
		$gepiSchoolAdress1,
		$gepiSchoolAdress2,
		$gepiSchoolZipCode,
		$gepiSchoolCity,
		$gepiSchoolPays,
		$gepiSchoolTel,
		$gepiSchoolFax,
		$gepiYear,

		$logo_etab,

		$un_seul_bull_par_famille,

		$X_cadre_eleve,
		$cadre_titre,

		$X_entete_etab,
		$caractere_utilse,
		$affiche_logo_etab,
		$entente_mel,
		$entente_tel,
		$entente_fax,
		$L_max_logo,
		$H_max_logo,

		$active_bloc_adresse_parent,
		$X_parent,
		$Y_parent,

		$annee_scolaire,
		$X_cadre_eleve,

		$titre_du_cadre,
		$largeur_cadre_matiere,
		$texte_observation,
		$cadre_titre,
		$largeur_cadre_note_global,
		$hauteur_dun_regroupement,

		$hauteur_du_titre,
		$largeur_cadre_note,
		$X_cadre_note,
		$hauteur_cachet,

		// Param�tres du mod�le PDF
		$tab_modele_pdf,

		// Objet PDF initi� hors de la pr�sente fonction donnant la page du bulletin pour un �l�ve
		$pdf;


	$id_classe=$tab_rel['id_classe'];
	$classe_id=$id_classe;

	// Pr�paration des lignes d'adresse

	// Initialisation:
	for($loop=0;$loop<=1;$loop++) {
		$tab_adr_ligne1[$loop]="";
		$tab_adr_ligne2[$loop]="";
		$tab_adr_ligne3[$loop]="";
		$tab_adr_ligne4[$loop]="";
		$tab_adr_ligne5[$loop]="";
		$tab_adr_ligne6[$loop]="";
	}

	// ON N'UTILISE PAS LE CHAMP adr4 DE L'ADRESSE DANS resp_adr
	// IL FAUDRA VOIR COMMENT LE RECUPERER

	if (!isset($tab_rel['eleve'][$i]['resp'][0])) {
		//$tab_adr_ligne1[0]="<font color='red'><b>ADRESSE MANQUANTE</b></font>";
		$tab_adr_ligne1[0]="ADRESSE MANQUANTE";
		$tab_adr_ligne2[0]="";
		$tab_adr_ligne3[0]="";
		$tab_adr_ligne4[0]="";
		$tab_adr_ligne5[0]="";
	}
	else {
		if (isset($tab_rel['eleve'][$i]['resp'][1])) {
			if((isset($tab_rel['eleve'][$i]['resp'][1]['adr1']))&&
				(isset($tab_rel['eleve'][$i]['resp'][1]['adr2']))&&
				(isset($tab_rel['eleve'][$i]['resp'][1]['adr3']))&&
				(isset($tab_rel['eleve'][$i]['resp'][1]['adr4']))&&
				(isset($tab_rel['eleve'][$i]['resp'][1]['cp']))&&
				(isset($tab_rel['eleve'][$i]['resp'][1]['commune']))
			) {
				// Le deuxi�me responsable existe et est renseign�
				if (($tab_rel['eleve'][$i]['resp'][0]['adr_id']==$tab_rel['eleve'][$i]['resp'][1]['adr_id']) OR
					(
						($tab_rel['eleve'][$i]['resp'][0]['adr1']==$tab_rel['eleve'][$i]['resp'][1]['adr1'])&&
						($tab_rel['eleve'][$i]['resp'][0]['adr2']==$tab_rel['eleve'][$i]['resp'][1]['adr2'])&&
						($tab_rel['eleve'][$i]['resp'][0]['adr3']==$tab_rel['eleve'][$i]['resp'][1]['adr3'])&&
						($tab_rel['eleve'][$i]['resp'][0]['adr4']==$tab_rel['eleve'][$i]['resp'][1]['adr4'])&&
						($tab_rel['eleve'][$i]['resp'][0]['cp']==$tab_rel['eleve'][$i]['resp'][1]['cp'])&&
						($tab_rel['eleve'][$i]['resp'][0]['commune']==$tab_rel['eleve'][$i]['resp'][1]['commune'])
					)
				) {
					// Les adresses sont identiques
					$nb_bulletins=1;

					if(($tab_rel['eleve'][$i]['resp'][0]['nom']!=$tab_rel['eleve'][$i]['resp'][1]['nom'])&&
						($tab_rel['eleve'][$i]['resp'][1]['nom']!="")) {
						// Les noms des responsables sont diff�rents
						$tab_adr_ligne1[0]=$tab_rel['eleve'][$i]['resp'][0]['civilite']." ".$tab_rel['eleve'][$i]['resp'][0]['nom']." ".$tab_rel['eleve'][$i]['resp'][0]['prenom']." et ".$tab_rel['eleve'][$i]['resp'][1]['civilite']." ".$tab_rel['eleve'][$i]['resp'][1]['nom']." ".$tab_rel['eleve'][$i]['resp'][1]['prenom'];

						/*
						$tab_adr_ligne1[0]=$tab_rel['eleve'][$i]['resp'][0]['civilite']." ".$tab_rel['eleve'][$i]['resp'][0]['nom']." ".$tab_rel['eleve'][$i]['resp'][0]['prenom'];
						//$tab_adr_ligne1[0].=" et ";
						$tab_adr_ligne1[0].="<br />\n";
						$tab_adr_ligne1[0].="et ";
						$tab_adr_ligne1[0].=$tab_rel['eleve'][$i]['resp'][1]['civilite']." ".$tab_rel['eleve'][$i]['resp'][1]['nom']." ".$tab_rel['eleve'][$i]['resp'][1]['prenom'];
						*/
					}
					else{
						if(($tab_rel['eleve'][$i]['resp'][0]['civilite']!="")&&($tab_rel['eleve'][$i]['resp'][1]['civilite']!="")) {
							$tab_adr_ligne1[0]=$tab_rel['eleve'][$i]['resp'][0]['civilite']." et ".$tab_rel['eleve'][$i]['resp'][1]['civilite']." ".$tab_rel['eleve'][$i]['resp'][0]['nom']." ".$tab_rel['eleve'][$i]['resp'][0]['prenom'];
						}
						else {
							$tab_adr_ligne1[0]="M. et Mme ".$tab_rel['eleve'][$i]['resp'][0]['nom']." ".$tab_rel['eleve'][$i]['resp'][0]['prenom'];
						}
					}

					$tab_adr_ligne2[0]=$tab_rel['eleve'][$i]['resp'][0]['adr1'];
					if($tab_rel['eleve'][$i]['resp'][0]['adr2']!=""){
						$tab_adr_ligne3[0]=$tab_rel['eleve'][$i]['resp'][0]['adr2'];
					}
					if($tab_rel['eleve'][$i]['resp'][0]['adr3']!=""){
						$tab_adr_ligne4[0]=$tab_rel['eleve'][$i]['resp'][0]['adr3'];
					}
					//if($tab_rel['eleve'][$i]['resp'][0]['adr4']!=""){
					//	$tab_adr_ligne2[0]=$tab_rel['eleve'][$i]['resp'][0]['adr4'];
					//}
					$tab_adr_ligne5[0]=$tab_rel['eleve'][$i]['resp'][0]['cp']." ".$tab_rel['eleve'][$i]['resp'][0]['commune'];


					if(($tab_rel['eleve'][$i]['resp'][0]['pays']!="")&&(strtolower($tab_rel['eleve'][$i]['resp'][0]['pays'])!=strtolower($gepiSchoolPays))) {
						$tab_adr_ligne6[0]=$tab_rel['eleve'][$i]['resp'][0]['pays'];
					}

				}
				else {
					// Les adresses sont diff�rentes
					//if ($un_seul_bull_par_famille!="oui") {
					// On teste en plus si la deuxi�me adresse est valide
					if (($un_seul_bull_par_famille!="oui")&&
						($tab_rel['eleve'][$i]['resp'][1]['adr1']!="")&&
						($tab_rel['eleve'][$i]['resp'][1]['commune']!="")
					) {
						$nb_bulletins=2;
					}
					else {
						$nb_bulletins=1;
					}

					for($cpt=0;$cpt<$nb_bulletins;$cpt++) {
						if($tab_rel['eleve'][$i]['resp'][$cpt]['civilite']!="") {
							$tab_adr_ligne1[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['civilite']." ".$tab_rel['eleve'][$i]['resp'][$cpt]['nom']." ".$tab_rel['eleve'][$i]['resp'][$cpt]['prenom'];
						}
						else {
							$tab_adr_ligne1[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['nom']." ".$tab_rel['eleve'][$i]['resp'][$cpt]['prenom'];
						}

						$tab_adr_ligne2[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['adr1'];
						if($tab_rel['eleve'][$i]['resp'][$cpt]['adr2']!=""){
							$tab_adr_ligne3[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['adr2'];
						}
						if($tab_rel['eleve'][$i]['resp'][$cpt]['adr3']!=""){
							$tab_adr_ligne4[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['adr3'];
						}
						/*
						if($tab_rel['eleve'][$i]['resp'][$cpt]['adr4']!=""){
							$tab_adr_ligne2[$cpt].="<br />\n".$tab_rel['eleve'][$i]['resp'][$cpt]['adr4'];
						}
						*/
						$tab_adr_ligne5[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['cp']." ".$tab_rel['eleve'][$i]['resp'][$cpt]['commune'];

						if(($tab_rel['eleve'][$i]['resp'][$cpt]['pays']!="")&&(strtolower($tab_rel['eleve'][$i]['resp'][$cpt]['pays'])!=strtolower($gepiSchoolPays))) {
							$tab_adr_ligne6[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['pays'];
						}
					}

				}
			}
			else {
				// Il n'y a pas de deuxi�me adresse, mais il y aurait un deuxi�me responsable???
				// CA NE DEVRAIT PAS ARRIVER ETANT DONN� LA REQUETE EFFECTUEE QUI JOINT resp_pers ET resp_adr...
				if ($un_seul_bull_par_famille!="oui") {
					$nb_bulletins=2;
				}
				else {
					$nb_bulletins=1;
				}

				for($cpt=0;$cpt<$nb_bulletins;$cpt++) {
					if($tab_rel['eleve'][$i]['resp'][$cpt]['civilite']!="") {
						$tab_adr_ligne1[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['civilite']." ".$tab_rel['eleve'][$i]['resp'][$cpt]['nom']." ".$tab_rel['eleve'][$i]['resp'][$cpt]['prenom'];
					}
					else {
						$tab_adr_ligne1[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['nom']." ".$tab_rel['eleve'][$i]['resp'][$cpt]['prenom'];
					}

					$tab_adr_ligne2[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['adr1'];
					if($tab_rel['eleve'][$i]['resp'][$cpt]['adr2']!=""){
						$tab_adr_ligne3[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['adr2'];
					}
					if($tab_rel['eleve'][$i]['resp'][$cpt]['adr3']!=""){
						$tab_adr_ligne4[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['adr3'];
					}
					/*
					if($tab_rel['eleve'][$i]['resp'][$cpt]['adr4']!=""){
						$tab_adr_ligne2[$cpt].="<br />\n".$tab_rel['eleve'][$i]['resp'][$cpt]['adr4'];
					}
					*/
					$tab_adr_ligne5[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['cp']." ".$tab_rel['eleve'][$i]['resp'][$cpt]['commune'];

					if(($tab_rel['eleve'][$i]['resp'][$cpt]['pays']!="")&&(strtolower($tab_rel['eleve'][$i]['resp'][$cpt]['pays'])!=strtolower($gepiSchoolPays))) {
						$tab_adr_ligne6[$cpt]=$tab_rel['eleve'][$i]['resp'][$cpt]['pays'];
					}
				}
			}
		}
		else {
			// Il n'y a pas de deuxi�me responsable
			$nb_bulletins=1;

			if($tab_rel['eleve'][$i]['resp'][0]['civilite']!="") {
				$tab_adr_ligne1[0]=$tab_rel['eleve'][$i]['resp'][0]['civilite']." ".$tab_rel['eleve'][$i]['resp'][0]['nom']." ".$tab_rel['eleve'][$i]['resp'][0]['prenom'];
			}
			else {
				$tab_adr_ligne1[0]=$tab_rel['eleve'][$i]['resp'][0]['nom']." ".$tab_rel['eleve'][$i]['resp'][0]['prenom'];
			}

			$tab_adr_ligne2[0]=$tab_rel['eleve'][$i]['resp'][0]['adr1'];
			if($tab_rel['eleve'][$i]['resp'][0]['adr2']!=""){
				$tab_adr_ligne3[0]=$tab_rel['eleve'][$i]['resp'][0]['adr2'];
			}
			if($tab_rel['eleve'][$i]['resp'][0]['adr3']!=""){
				$tab_adr_ligne4[0]=$tab_rel['eleve'][$i]['resp'][0]['adr3'];
			}
			/*
			if($tab_rel['eleve'][$i]['resp'][0]['adr4']!=""){
				$tab_adr_ligne2[0].="<br />\n".$tab_rel['eleve'][$i]['resp'][0]['adr4'];
			}
			*/
			$tab_adr_ligne5[0]=$tab_rel['eleve'][$i]['resp'][0]['cp']." ".$tab_rel['eleve'][$i]['resp'][0]['commune'];

			if(($tab_rel['eleve'][$i]['resp'][0]['pays']!="")&&(strtolower($tab_rel['eleve'][$i]['resp'][0]['pays'])!=strtolower($gepiSchoolPays))) {
				$tab_adr_ligne6[0]=$tab_rel['eleve'][$i]['resp'][0]['pays'];
			}
		}
	}
	//=========================================


	$pdf->AddPage("P");
	$pdf->SetFontSize(10);

	/*
	if($nb_releve_par_page === '1' and $active_bloc_adresse_parent != '1') { $hauteur_cadre_note_global = 250; }
	if($nb_releve_par_page === '1' and $active_bloc_adresse_parent === '1') { $hauteur_cadre_note_global = 205; }
	if($nb_releve_par_page === '2') { $hauteur_cadre_note_global = 102; }
	*/

	// Pour un relev� en recto/verso avec le bulletin,
	// il ne faut qu'un relev� par page, mais si on devait utiliser cette fonction
	// pour remplacer un jour le dispositif relev� PDF, il faudrait revoir cela:
	$nb_releve_par_page=1;


	//$active_bloc_adresse_parent=0;
	$active_bloc_adresse_parent=($tab_rel['rn_adr_resp']=='y') ? 1 : 0;
	//$hauteur_cadre_note_global = 250;
	if($active_bloc_adresse_parent!=1) { $hauteur_cadre_note_global = 250; }
	if($active_bloc_adresse_parent==1) { $hauteur_cadre_note_global = 205; }

	// A FAIRE:
	// Pour la hauteur, prendre en compte la saisie d'une formule $tab_rel['rn_formule'] (non vide)
	// et le caract�re vide ou non de getSettingValue("bull_formule_bas")

	//$affiche_bloc_observation=1;
	$affiche_bloc_observation=($tab_rel['rn_bloc_obs']=='y') ? 1 : 0;

	//$affiche_cachet_pp=1;
	$affiche_cachet_pp=($tab_rel['rn_sign_pp']=='y') ? 1 : 0;
	//$affiche_signature_parent=1;
	$affiche_signature_parent=($tab_rel['rn_sign_resp']=='y') ? 1 : 0;

	if(($affiche_cachet_pp==1)||($affiche_signature_parent==1)) {$affiche_bloc_observation=1;}

	$texte_observation="Observations:";

	//$aff_classe_nom=1;
	$aff_classe_nom=$tab_rel['rn_aff_classe_nom'];

	// BIZARRE:
	$hauteur_cadre_matiere=20;
	$classe_aff="NOM_CLASSE";

	$passage_i=1;

	// login de l'�l�ve
	//$eleve_select=$login[$nb_eleves_i];
	//$eleve_select=$tab_rel['eleve'][$i]['login'];
	//if(isset($tab_rel['eleve'][$i]['login'])) {
		$eleve_select=$tab_rel['eleve'][$i]['login'];

		/*
		// diff�rente Y pour les pr�sentation sur 1 ou 2 par page avec ident parents
		if($nb_releve_par_page=='1' and $passage_i == '1' and $active_bloc_adresse_parent!='1') { $Y_cadre_note = '32'; $Y_cadre_eleve = '5'; $Y_entete_etab='5'; }
		if($nb_releve_par_page=='1' and $passage_i == '1' and $active_bloc_adresse_parent==='1') { $Y_cadre_note = '75'; $Y_cadre_eleve = '5'; $Y_entete_etab='5'; }
		if($nb_releve_par_page=='2' and $passage_i == '1') { $Y_cadre_note = '32'; $Y_cadre_eleve = '5'; $Y_entete_etab='5'; }
		if($nb_releve_par_page=='2' and $passage_i == '2') { $Y_cadre_note = $Y_cadre_note+145; $Y_cadre_eleve = $Y_cadre_eleve+145; $Y_entete_etab=$Y_entete_etab+145; }
		*/

		/*
		$Y_cadre_note = '32';
		$Y_cadre_eleve = '5';
		$Y_entete_etab='5';
		*/
		if($active_bloc_adresse_parent!='1') {
			$Y_cadre_note = '32';
			$Y_cadre_eleve = '5';
			$Y_entete_etab='5';
		}
		else {
			$Y_cadre_note = '75';
			$Y_cadre_eleve = '5';
			$Y_entete_etab='5';
		}


		//BLOC IDENTITE ELEVE
		$pdf->SetXY($X_cadre_eleve,$Y_cadre_eleve);
		$pdf->SetFont($caractere_utilse,'B',14);
		$pdf->Cell(90,7,strtoupper($tab_rel['eleve'][$i]['nom'])." ".ucfirst($tab_rel['eleve'][$i]['prenom']),0,2,'');
		$pdf->SetFont($caractere_utilse,'',10);
		//$pdf->Cell(90,5,'N� le '.affiche_date_naissance($naissance[$nb_eleves_i]).',�demi-pensionnaire',0,2,'');
		if($tab_rel['eleve'][$i]['sexe']=="M"){$e_au_feminin="";}else{$e_au_feminin="e";}

		//$pdf->Cell(90,5,'N�'.$e_au_feminin.' le '.affiche_date_naissance($tab_rel['eleve'][$i]['naissance']).',�'.regime($tab_rel['eleve'][$i]['regime']),0,2,'');
		$pdf->Cell(90,5,'N�'.$e_au_feminin.' le '.$tab_rel['eleve'][$i]['naissance'].',�'.regime($tab_rel['eleve'][$i]['regime']),0,2,'');

		$pdf->Cell(90,5,'',0,2,'');

		/*
		if ( $aff_classe_nom === '1' or $aff_classe_nom === '3' ) {
			$classe_aff = $pdf->WriteHTML('Classe de <B>'.unhtmlentities($tab_rel['classe_nom_complet']).'<B>');
		}
		if ( $aff_classe_nom === '2' ) {
			$classe_aff = $pdf->WriteHTML('Classe de <B>'.unhtmlentities($tab_rel['classe']).'<B>');
		}
		if ( $aff_classe_nom === '3' ) {
			$classe_aff = $pdf->WriteHTML(' ('.unhtmlentities($tab_rel['classe']).')');
		}
		*/
		//$classe_aff = $pdf->WriteHTML('Classe de <B>'.unhtmlentities($tab_rel['classe_nom_complet']).'<B>');
		//$classe_aff = $pdf->WriteHTML('Classe de <B>'.unhtmlentities($tab_rel['classe']).'<B>');
		//$classe_aff = $pdf->WriteHTML(' ('.unhtmlentities($tab_rel['classe']).')');
		// A REVOIR...
		//$classe_aff=$pdf->WriteHTML(' '.unhtmlentities($tab_rel['classe_nom_complet']).' ('.unhtmlentities($tab_rel['classe']).')');

		if($aff_classe_nom==1) {
			$classe_aff=$pdf->WriteHTML('Classe de '.unhtmlentities($tab_rel['classe_nom_complet']));
		}
		elseif($aff_classe_nom==2) {
			$classe_aff=$pdf->WriteHTML('Classe de '.unhtmlentities($tab_rel['classe']));
		}
		else {
			$classe_aff=$pdf->WriteHTML(' '.unhtmlentities($tab_rel['classe_nom_complet']).' ('.unhtmlentities($tab_rel['classe']).')');
		}

		$pdf->Cell(90,5,$classe_aff,0,2,'');
		$pdf->SetX($X_cadre_eleve);
		$pdf->SetFont($caractere_utilse,'',10);
		$pdf->Cell(90,5,'Ann�e scolaire '.$annee_scolaire,0,2,'');

		// BLOC IDENTITE DE L'ETABLISSEMENT
		$logo = '../images/'.getSettingValue('logo_etab');
		$format_du_logo = str_replace('.','',strstr(getSettingValue('logo_etab'), '.'));
		//if($affiche_logo_etab==='1' and file_exists($logo) and getSettingValue('logo_etab') != '' and ($format_du_logo==='jpg' or $format_du_logo==='png')) {
		//if($tab_modele_pdf["affiche_logo_etab"][$classe_id]==='1' and file_exists($logo) and getSettingValue('logo_etab') != '' and ($format_du_logo==='jpg' or $format_du_logo==='png')) {
		if($tab_modele_pdf["affiche_logo_etab"][$classe_id]==1 and file_exists($logo) and getSettingValue('logo_etab') != '' and ($format_du_logo=='jpg' or $format_du_logo=='png')) {
			$valeur=redimensionne_image($logo, $L_max_logo, $H_max_logo);
			//$X_logo et $Y_logo; placement du bloc identite de l'�tablissement
			$X_logo=$X_entete_etab;
			$Y_logo=$Y_entete_etab;
			$L_logo=$valeur[0];
			$H_logo=$valeur[1];
			$X_etab=$X_logo+$L_logo;
			$Y_etab=$Y_logo;
			//logo
			$pdf->Image($logo, $X_logo, $Y_logo, $L_logo, $H_logo);
		}
		else {
			$X_etab = $X_entete_etab; $Y_etab = $Y_entete_etab;
		}

		// BLOC ADRESSE ETABLISSEMENT
		$pdf->SetXY($X_etab,$Y_etab);
		$pdf->SetFont($caractere_utilse,'',14);
		//$gepiSchoolName = getSettingValue('gepiSchoolName');
		$pdf->Cell(90,7, $gepiSchoolName,0,2,'');
		$pdf->SetFont($caractere_utilse,'',10);
		//$gepiSchoolAdress1 = getSettingValue('gepiSchoolAdress1');
		$pdf->Cell(90,5, $gepiSchoolAdress1,0,2,'');
		//$gepiSchoolAdress2 = getSettingValue('gepiSchoolAdress2');
		$pdf->Cell(90,5, $gepiSchoolAdress2,0,2,'');
		//$gepiSchoolZipCode = getSettingValue('gepiSchoolZipCode');
		//$gepiSchoolCity = getSettingValue('gepiSchoolCity');
		$pdf->Cell(90,5, $gepiSchoolZipCode." ".$gepiSchoolCity,0,2,'');
		//$gepiSchoolTel = getSettingValue('gepiSchoolTel');
		//$gepiSchoolFax = getSettingValue('gepiSchoolFax');
		if($tab_modele_pdf["entente_tel"][$classe_id]==='1' and $tab_modele_pdf["entente_fax"][$classe_id]==='1') {
			$entete_communic = 'T�l: '.$gepiSchoolTel.' / Fax: '.$gepiSchoolFax;
		}
		if($tab_modele_pdf["entente_tel"][$classe_id]==='1' and empty($entete_communic)) {
			$entete_communic = 'T�l: '.$gepiSchoolTel;
		}
		if($tab_modele_pdf["entente_fax"][$classe_id]==='1' and empty($entete_communic)) {
			$entete_communic = 'Fax: '.$gepiSchoolFax;
		}
		if(isset($entete_communic) and $entete_communic!='') {
			$pdf->Cell(90,5, $entete_communic,0,2,'');
		}
		if($tab_modele_pdf["entente_mel"][$classe_id]==='1') {
			$gepiSchoolEmail = getSettingValue('gepiSchoolEmail');
			$pdf->Cell(90,5, $gepiSchoolEmail,0,2,'');
		}

		// BLOC ADRESSE DES PARENTS
		// Nom des variables � revoir
		//if($active_bloc_adresse_parent==='1' and $nb_releve_par_page==='1') {
		if($active_bloc_adresse_parent==1 and $nb_releve_par_page==1) {

			//+++++++++++++++
			// A REVOIR
			$num_resp=0;
			//+++++++++++++++

			//$ident_eleve_aff=$login[$nb_eleves_i];
			$pdf->SetXY($X_parent,$Y_parent);
			//$texte_1_responsable = $civilite_parents[$ident_eleve_aff][$responsable_place]." ".$nom_parents[$ident_eleve_aff][$responsable_place]." ".$prenom_parents[$ident_eleve_aff][$responsable_place];
			$texte_1_responsable=$tab_adr_ligne1[$num_resp];
			$hauteur_caractere=12;
			$pdf->SetFont($caractere_utilse,'B',$hauteur_caractere);
			$val = $pdf->GetStringWidth($texte_1_responsable);
			$taille_texte = 90;
			$grandeur_texte='test';
			while($grandeur_texte!='ok') {
				if($taille_texte<$val) {
					$hauteur_caractere = $hauteur_caractere-0.3;
					$pdf->SetFont($caractere_utilse,'B',$hauteur_caractere);
					$val = $pdf->GetStringWidth($texte_1_responsable);
				}
				else {
					$grandeur_texte='ok';
				}
			}
			$pdf->Cell(90,7, $texte_1_responsable,0,2,'');

			$pdf->SetFont($caractere_utilse,'',10);
			//$texte_1_responsable = $adresse1_parents[$ident_eleve_aff][$responsable_place];
			$texte_1_responsable=$tab_adr_ligne2[$num_resp];
			$hauteur_caractere=10;
			$pdf->SetFont($caractere_utilse,'',$hauteur_caractere);
			$val = $pdf->GetStringWidth($texte_1_responsable);
			$taille_texte = 90;
			$grandeur_texte='test';
			while($grandeur_texte!='ok') {
				if($taille_texte<$val) {
					$hauteur_caractere = $hauteur_caractere-0.3;
					$pdf->SetFont($caractere_utilse,'',$hauteur_caractere);
					$val = $pdf->GetStringWidth($texte_1_responsable);
				}
				else {
					$grandeur_texte='ok';
				}
			}

			$pdf->Cell(90,5, $texte_1_responsable,0,2,'');
			//$texte_1_responsable = $adresse2_parents[$ident_eleve_aff][$responsable_place];
			$texte_1_responsable=$tab_adr_ligne3[$num_resp];
			$hauteur_caractere=10;
			$pdf->SetFont($caractere_utilse,'',$hauteur_caractere);
			$val = $pdf->GetStringWidth($texte_1_responsable);
			$taille_texte = 90;
			$grandeur_texte='test';
			while($grandeur_texte!='ok') {
				if($taille_texte<$val) {
					$hauteur_caractere = $hauteur_caractere-0.3;
					$pdf->SetFont($caractere_utilse,'',$hauteur_caractere);
					$val = $pdf->GetStringWidth($texte_1_responsable);
				}
				else {
					$grandeur_texte='ok';
				}
			}

			$pdf->Cell(90,5, $texte_1_responsable,0,2,'');
			$pdf->Cell(90,5, '',0,2,'');
			//$texte_1_responsable = $cp_parents[$ident_eleve_aff][$responsable_place]." ".$ville_parents[$ident_eleve_aff][$responsable_place];
			$texte_1_responsable=$tab_adr_ligne5[$num_resp];
			$hauteur_caractere=10;
			$pdf->SetFont($caractere_utilse,'',$hauteur_caractere);
			$val = $pdf->GetStringWidth($texte_1_responsable);
			$taille_texte = 90;
			$grandeur_texte='test';
			while($grandeur_texte!='ok') {
				if($taille_texte<$val) {
					$hauteur_caractere = $hauteur_caractere-0.3;
					$pdf->SetFont($caractere_utilse,'',$hauteur_caractere);
					$val = $pdf->GetStringWidth($texte_1_responsable);
				}
				else {
					$grandeur_texte='ok';
				}
			}
			$pdf->Cell(90,5, $texte_1_responsable,0,2,'');
		}

		// BLOC NOTATION ET OBSERVATION
		//Titre du tableau
		$pdf->SetXY($X_cadre_note,$Y_cadre_note);
		$pdf->SetFont($caractere_utilse,'B',12);
		//if($cadre_titre==='1') { $var_encadrement_titre='LTR'; } else { $var_encadrement_titre=''; }
		if($cadre_titre==1) { $var_encadrement_titre='LTR'; } else { $var_encadrement_titre=''; }

		//$pdf->Cell(0, $hauteur_du_titre, $titre_du_cadre.' '.date_frc($_SESSION['date_debut_aff']).' au '.date_frc($_SESSION['date_fin_aff']), $var_encadrement_titre,0,'C');
		// A REVOIR...
		//$pdf->Cell(0, $hauteur_du_titre, $titre_du_cadre.' P�riode '.$tab_rel['nom_periode'], $var_encadrement_titre,0,'C');
		$pdf->Cell(0, $hauteur_du_titre, $titre_du_cadre.$tab_rel['nom_periode'], $var_encadrement_titre,0,'C');

		$hauteur_utilise = $hauteur_du_titre;

		/*
		$nb_matiere=0;
		for($j=0;$j<count($tab_rel['eleve'][$i]['groupe']);$j++) {
			if(isset($tab_bull['note'][$j][$i])) {
				// Si l'�l�ve suit l'option, sa note est affect�e (�ventuellement vide)
				$nb_matiere++;
			}
		}
		*/
		$nb_matiere=count($tab_rel['eleve'][$i]['groupe']);


		//s'il y des notes alors on affiche le cadre avec les notes
		//if(isset($nb_matiere[$eleve_select]) and !empty($nb_matiere[$eleve_select])) {
		if($nb_matiere>0) {
			// Hauteur d'une ligne pour une mati�re
			/*
			if($active_entete_regroupement === '1') {
				$hauteur_cadre_matiere=($hauteur_cadre_note_global-($nb_regroupement[$eleve_select]*$hauteur_dun_regroupement))/$nb_matiere[$eleve_select];
			}
			if($active_entete_regroupement != '1') {
			*/
				$hauteur_cadre_matiere=$hauteur_cadre_note_global/$nb_matiere;
			//}

			// Tableau des mati�res et des notes de l'�l�ve
			$cpt_i='1';
			$nom_regroupement_passer='';
			//while($cpt_i<=$nb_matiere[$eleve_select])
			//{
			for($m=0; $m<count($tab_rel['eleve'][$i]['groupe']); $m++) {

				// Si c'est une mati�re suivie par l'�l�ve
				if(isset($tab_rel['eleve'][$i]['groupe'][$m])) {

					//$id_groupe_selectionne=$groupe_select[$eleve_select][$cpt_i];
					$id_groupe_selectionne=$tab_rel['eleve'][$i]['groupe'][$m]['id_groupe'];
					//MATIERE
					$pdf->SetXY($X_cadre_note,$Y_cadre_note+$hauteur_utilise);

					// on affiche les nom des regroupements
					/*
					if($nom_regroupement[$eleve_select][$cpt_i]!=$nom_regroupement_passer and $active_entete_regroupement === '1')
					{
						$pdf->SetFont($caractere_utilse,'',8);
						$pdf->Cell($largeur_cadre_matiere, $hauteur_dun_regroupement, unhtmlentities($nom_regroupement[$eleve_select][$cpt_i]), 'LTB', 2, '');
						$hauteur_utilise=$hauteur_utilise+$hauteur_dun_regroupement;
						$nom_regroupement_passer=$nom_regroupement[$eleve_select][$cpt_i];
						$pdf->SetXY($X_cadre_note,$Y_cadre_note+$hauteur_utilise);
					}
					*/
					$pdf->SetFont($caractere_utilse,'B','9');
					$nom_matiere = $tab_rel['eleve'][$i]['groupe'][$m]['matiere_nom_complet'];
					$hauteur_caractere = 9;
					$pdf->SetFont($caractere_utilse,'B',$hauteur_caractere);
					$val = $pdf->GetStringWidth($nom_matiere);
					$taille_texte = $largeur_cadre_matiere;
					$grandeur_texte='test';
					while($grandeur_texte!='ok') {
						if($taille_texte<$val) {
							$hauteur_caractere = $hauteur_caractere-0.3;
							$pdf->SetFont($caractere_utilse,'B',$hauteur_caractere);
							$val = $pdf->GetStringWidth($nom_matiere);
						}
						else {
							$grandeur_texte='ok';
						}
					}
					$pdf->Cell($largeur_cadre_matiere, $hauteur_cadre_matiere/2, $nom_matiere, 'LRT', 2, '');
					//$pdf->Cell($largeur_cadre_matiere, $hauteur_cadre_matiere/2, $nom_matiere." ".count($tab_rel['eleve'][$i]['groupe'][$m]['prof_login']), 'LRT', 2, '');
					//$pdf->Cell($largeur_cadre_matiere, $hauteur_cadre_matiere/2, $nom_matiere." ".$tab_rel['eleve'][$i]['groupe'][$m]['prof_login'][0], 'LRT', 2, '');
					$nom_matiere = '';

					$nb_prof_matiere = count($tab_rel['eleve'][$i]['groupe'][$m]['prof_login']);
					$espace_matiere_prof = $hauteur_cadre_matiere/2;
					$nb_pass_count = '0';
					$text_prof = '';

					//if ( $nb_releve_par_page === '2' ) {
					if ($nb_releve_par_page==2) {
						$nb_pass_count_2 = 0;
						while ( !empty($tab_rel['eleve'][$i]['groupe'][$m]['prof_login'][$nb_pass_count_2]) )
						{
							if ( $nb_pass_count_2 === 0 ) {
								$text_prof = affiche_utilisateur($tab_rel['eleve'][$i]['groupe'][$m]['prof_login'][$nb_pass_count_2],$id_classe);
							}
							if ( $nb_pass_count_2 != 0 ) {
								$text_prof = $text_prof.', '.affiche_utilisateur($tab_rel['eleve'][$i]['groupe'][$m]['prof_login'][$nb_pass_count_2],$id_classe);
							}
							$nb_pass_count_2 = $nb_pass_count_2 + 1;
						}
						//$nb_prof_matiere = 1;
					}
					if ( $nb_prof_matiere != 1 ) {
						$espace_matiere_prof = $espace_matiere_prof/$nb_prof_matiere;
					}

					while ($nb_prof_matiere > $nb_pass_count) {

						// calcul de la hauteur du caract�re du prof
						//if ( $nb_releve_par_page === '1' ) {
						if ($nb_releve_par_page==1) {
							$text_prof = affiche_utilisateur($tab_rel['eleve'][$i]['groupe'][$m]['prof_login'][$nb_pass_count],$id_classe);
						}
						if ( $nb_prof_matiere <= 2 ) { $hauteur_caractere_prof = 9; }
						elseif ( $nb_prof_matiere == 3) { $hauteur_caractere_prof = 7; }
						elseif ( $nb_prof_matiere > 3) { $hauteur_caractere_prof = 2; }
						$pdf->SetFont($caractere_utilse,'',$hauteur_caractere_prof);
						$val = $pdf->GetStringWidth($text_prof);
						$taille_texte = ($largeur_cadre_matiere-0.6);
						$grandeur_texte='test';
						while($grandeur_texte!='ok') {
							if($taille_texte<$val)
							{
								$hauteur_caractere_prof = $hauteur_caractere_prof-0.3;
								$pdf->SetFont($caractere_utilse,'',$hauteur_caractere_prof);
								$val = $pdf->GetStringWidth($text_prof);
							}
							else {
								$grandeur_texte='ok';
							}
						}
						$grandeur_texte='test';
						$pdf->SetX($X_cadre_note);
						//$pdf->Cell($largeur_cadre_matiere, $espace_matiere_prof, 'prof '.$text_prof, 'LRB', 2, '');

						if( empty($tab_rel['eleve'][$i]['groupe'][$m]['prof_login'][$nb_pass_count+1]) or $nb_prof_matiere === 1 ) {
							$pdf->Cell($largeur_cadre_matiere, $espace_matiere_prof, $text_prof, 'LRB', 2, '');
						}
						if( !empty($tab_rel['eleve'][$i]['groupe'][$m]['prof_login'][$nb_pass_count+1]) and $nb_prof_matiere != 1 ) {
							$pdf->Cell($largeur_cadre_matiere, $espace_matiere_prof, $text_prof, 'LR', 2, '');
						}

						$nb_pass_count = $nb_pass_count + 1;
					}

					//			if(isset($prof_groupe[$id_groupe_selectionne][0]) and $prof_groupe[$id_groupe_selectionne][0] != '') { $prof_1 = $prof_groupe[$id_groupe_selectionne][0]; } else { $prof_1 = ''; }
					//			if(isset($prof_groupe[$id_groupe_selectionne][1]) and $prof_groupe[$id_groupe_selectionne][1] != '') { $prof_2 = $prof_groupe[$id_groupe_selectionne][1]; } else { $prof_2 = ''; }
					//			if(isset($prof_groupe[$id_groupe_selectionne][2]) and $prof_groupe[$id_groupe_selectionne][2] != '') { $prof_3 = $prof_groupe[$id_groupe_selectionne][2]; } else { $prof_3 = ''; }
					/*			 $nom_prof = $prof_1;
					$hauteur_caractere = 8;
					$pdf->SetFont($caractere_utilse,'I',$hauteur_caractere);
					$val = $pdf->GetStringWidth($nom_prof);
					$taille_texte = $largeur_cadre_matiere;
					$grandeur_texte='test';
					while($grandeur_texte!='ok') {
					if($taille_texte<$val)
					{
						$hauteur_caractere = $hauteur_caractere-0.3;
						$pdf->SetFont($caractere_utilse,'I',$hauteur_caractere);
						$val = $pdf->GetStringWidth($nom_prof);
					} else { $grandeur_texte='ok'; }
						}

					$pdf->Cell($largeur_cadre_matiere, $hauteur_cadre_matiere/2, $nom_prof, 'LRB', 2, '');*/
					//$pdf->Cell($largeur_cadre_matiere, $hauteur_cadre_matiere/3, $prof_2, 'LR', 2, '');
					//$pdf->Cell($largeur_cadre_matiere, $hauteur_cadre_matiere/4, $prof_3, 'LRB', 2, '');
					$hauteur_utilise=$hauteur_utilise+$hauteur_cadre_matiere;

				}
				$cpt_i=$cpt_i+1;
			}
		}

		$hauteur_utilise = $hauteur_du_titre;

		$cpt_i='1';
		$nom_regroupement_passer='';


		//while($cpt_i<=$nb_matiere[$eleve_select]) {
		for($m=0; $m<count($tab_rel['eleve'][$i]['groupe']); $m++) {
			//NOTES
			$largeur_utilise=$largeur_cadre_matiere;
			//=======================
			// AJOUT: chapel 20071019
			//if ( $affiche_bloc_observation === '1' ) {
			if ( $affiche_bloc_observation==1) {
				$largeur_cadre_note = $largeur_cadre_note;
			}
			else {
				$largeur_cadre_note = $largeur_cadre_note_global - $largeur_utilise;
			}
			//=======================
			$pdf->SetXY($X_cadre_note+$largeur_utilise,$Y_cadre_note+$hauteur_utilise);
			// on affiche les nom des regroupement
			/*
			if($nom_regroupement[$eleve_select][$cpt_i]!=$nom_regroupement_passer and $active_entete_regroupement === '1')
			{
				$pdf->SetFont($caractere_utilse,'',8);
				$pdf->Cell($largeur_cadre_note, $hauteur_dun_regroupement, '', 'RTB', 2, '');
				$hauteur_utilise=$hauteur_utilise+$hauteur_dun_regroupement;
				$nom_regroupement_passer=$nom_regroupement[$eleve_select][$cpt_i];
				$pdf->SetXY($X_cadre_note+$largeur_utilise,$Y_cadre_note+$hauteur_utilise);
			}
			*/

			$chaine_notes="";
			if(isset($tab_rel['eleve'][$i]['groupe'][$m]['devoir'])) {
				$kk=0;
				for($k=0;$k<count($tab_rel['eleve'][$i]['groupe'][$m]['devoir']);$k++) {
					// A FAIRE: TENIR COMPTE DE TOUS LES PARAMETRES POUR VOIR CE QU'IL FAUT AFFICHER
					if($kk>0) {$chaine_notes.=" - ";}
					if($tab_rel['eleve'][$i]['groupe'][$m]['devoir'][$k]['statut']!='v') {
						if($tab_rel['eleve'][$i]['groupe'][$m]['devoir'][$k]['statut']!='') {
							$chaine_notes.=$tab_rel['eleve'][$i]['groupe'][$m]['devoir'][$k]['statut'];
						}
						else {
							$chaine_notes.=$tab_rel['eleve'][$i]['groupe'][$m]['devoir'][$k]['note'];
						}

						if($tab_rel['rn_nomdev']=='y') {
							$chaine_notes.=" (".$tab_rel['eleve'][$i]['groupe'][$m]['devoir'][$k]['nom_court'].")";
						}

						if($tab_rel['rn_datedev']=='y') {
							$chaine_notes.=" (".formate_date($tab_rel['eleve'][$i]['groupe'][$m]['devoir'][$k]['date']).")";
						}

						if($tab_rel['rn_coefdev_si_diff']=='y') {
							if($tab_rel['eleve'][$i]['groupe'][$m]['differents_coef']=='y') {
								$chaine_notes.=" (coef ".$tab_rel['eleve'][$i]['groupe'][$m]['devoir'][$k]['coef'].")";
							}
						}
						else {
							if($tab_rel['rn_toutcoefdev']=='y') {
								$chaine_notes.=" (coef ".$tab_rel['eleve'][$i]['groupe'][$m]['devoir'][$k]['coef'].")";
							}
						}

						$kk++;
					}
				}
			}

			// d�termine la taille de la police de caract�re
			// on peut allez jusqu'a 275mm de caract�re dans trois cases de notes
			$hauteur_caractere_notes=9;
			$pdf->SetFont($caractere_utilse,'',$hauteur_caractere_notes);
			$val = $pdf->GetStringWidth($chaine_notes);
			$taille_texte = (($hauteur_cadre_matiere/4)*$largeur_cadre_note);
			$grandeur_texte='test';
			while($grandeur_texte!='ok') {
				if($taille_texte<$val) {
					$hauteur_caractere_notes = $hauteur_caractere_notes-0.3;
					$pdf->SetFont($caractere_utilse,'',$hauteur_caractere_notes);
					$val = $pdf->GetStringWidth($chaine_notes);
				}
				else {
					$grandeur_texte='ok';
				}
			}
			$pdf->drawTextBox($chaine_notes, $largeur_cadre_note, $hauteur_cadre_matiere, 'J', 'M', 1);
			$hauteur_utilise=$hauteur_utilise+$hauteur_cadre_matiere;

			//$cpt_i=$cpt_i+1;
		}


		// BLOC OBSERVATION
		//=======================
		// MODIF: chapel 20071019
		//if($affiche_bloc_observation === '1')
		if($affiche_bloc_observation==1)
		{
			$largeur_utilise=$largeur_cadre_matiere+$largeur_cadre_note;
			$largeur_restant=$largeur_cadre_note_global-$largeur_utilise;
			$hauteur_utilise = $hauteur_du_titre;
			//if($affiche_cachet_pp==='1' or $affiche_signature_parent==='1')
			if($affiche_cachet_pp==1 or $affiche_signature_parent==1)
			{
				$hauteur_cadre_observation=$hauteur_cadre_note_global-$hauteur_cachet;
			}
			else {
				$hauteur_cadre_observation=$hauteur_cadre_note_global;
			}
			$pdf->Rect($X_cadre_note+$largeur_utilise, $Y_cadre_note+$hauteur_utilise, $largeur_restant, $hauteur_cadre_observation, 'D');
			$pdf->SetXY($X_cadre_note+$largeur_utilise, $Y_cadre_note+$hauteur_utilise);
			$pdf->SetFont($caractere_utilse,'',11);
			$pdf->Cell($largeur_restant,7, $texte_observation,0,1,'C');
		}
		//=======================

		// BLOC SIGNATURE
		//if($affiche_cachet_pp==='1' or $affiche_signature_parent==='1')
		if($affiche_cachet_pp==1 or $affiche_signature_parent==1)
		{
			$nb_col_sign = 0;
			//if($affiche_cachet_pp==='1') { $nb_col_sign=$nb_col_sign+1; }
			//if($affiche_signature_parent==='1') { $nb_col_sign=$nb_col_sign+1; }
			if($affiche_cachet_pp==1) { $nb_col_sign=$nb_col_sign+1; }
			if($affiche_signature_parent==1) { $nb_col_sign=$nb_col_sign+1; }
			$largeur_utilise=$largeur_cadre_matiere+$largeur_cadre_note;

			$X_signature = $X_cadre_note+$largeur_utilise;
			$Y_signature = $Y_cadre_note+$hauteur_cadre_observation+$hauteur_du_titre;
			$hauteur_cadre_signature=$hauteur_cadre_note_global-$hauteur_cadre_observation;
			$largeur_cadre_signature=$largeur_cadre_note_global-$largeur_utilise;

			$pdf->SetFont($caractere_utilse,'',8);
			$pdf->Rect($X_signature, $Y_signature, $largeur_cadre_signature, $hauteur_cadre_signature, 'D');

			//if($affiche_cachet_pp==='1')
			if($affiche_cachet_pp==1)
			{
				$pdf->SetXY($X_signature, $Y_signature);
				$pdf->Cell($largeur_cadre_signature/$nb_col_sign,4, 'Signature','LTR',2,'C');
				$pdf->Cell($largeur_cadre_signature/$nb_col_sign,4, $gepi_prof_suivi,'LR',2,'C');
				$pdf->Cell($largeur_cadre_signature/$nb_col_sign,$hauteur_cachet-8, '','LR',2,'C');
				$X_signature = $X_signature+($largeur_restant/$nb_col_sign);
			}
			//if($affiche_signature_parent==='1')
			if($affiche_signature_parent==1)
			{
				$pdf->SetXY($X_signature, $Y_signature);
				$pdf->Cell($largeur_cadre_signature/$nb_col_sign,4, 'Signatures','LTR',2,'C');
				$pdf->Cell($largeur_cadre_signature/$nb_col_sign,4, 'des parents','LR',2,'C');
				$pdf->Cell($largeur_cadre_signature/$nb_col_sign,$hauteur_cachet-8, '','LR',2,'C');
			}
		}
		//}

		/*
		//PUB ;)
		$pdf->SetXY($X_cadre_note, $Y_cadre_note+$hauteur_cadre_note_global+$hauteur_du_titre);
		$pdf->SetFont('arial','',8);
		$pdf->Cell(200,5,'GEPI - Solution libre de Gestion des �l�ves par Internet',0,1,'');
		// CA ENTRE EN COLLISION AVEC LA FORMULE DU BULLETIN (ins�r�e via la fonction Footer() de class_php/gepi_pdf.class.php)
		*/
	//}

	/*
		$passage_i=$passage_i+1;
		$nb_eleves_i = $nb_eleves_i + 1;
	}

	// on pr�pare la 2�me boucle pour faire R1 et R2 != R1 si n�cessaire
	if ($nb_eleves_i > $nb_eleves) { // dans ce cas on a fait la premi�re boucle, on pr�pare la 2�me pour les R2 != � R1
		$nb_boucle++;
		$responsable_place = 1;
		$nb_eleves_i = 1;
	}
	*/

//}

// vider les variables de session
//    unset($_SESSION["classe"]);
//    unset($_SESSION["eleve"]);
//    unset($_SESSION["type"]);
//    unset($_SESSION["date_debut"]);
//    unset($_SESSION["date_fin"]);
//    unset($_SESSION["date_debut_aff"]);
//    unset($_SESSION["date_fin_aff"]);
//    unset($_SESSION["avec_nom_devoir"]);

/*
// sortie PDF sur �cran
$nom_releve=date("Ymd_Hi");
$nom_releve = 'Releve_'.$nom_releve.'.pdf';
$pdf->Output($nom_releve,'I');

// Le PDF n'est g�n�r� qu'en fin de boucle sur les bulletins
*/
}

function fich_debug_bull($texte){
	$fichier_debug="/tmp/bulletin_pdf.txt";

	// Passer la variable � "y" pour activer le remplissage du fichier de debug pour calcule_moyenne()
	$local_debug="n";
	if($local_debug=="y") {
		$fich=fopen($fichier_debug,"a+");
		fwrite($fich,$texte);
		fclose($fich);
	}
}

?>
