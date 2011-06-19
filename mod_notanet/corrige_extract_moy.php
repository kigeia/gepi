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
// En Gepi 1.5.0:
// INSERT INTO droits VALUES('/mod_notanet/corrige_extract_moy.php','V','F','F','F','F','F','F','Extraction des moyennes pour Notanet','');
// En Gepi 1.5.1:
// INSERT INTO droits VALUES('/mod_notanet/corrige_extract_moy.php','V','F','F','F','F','F','F','F','Extraction des moyennes pour Notanet','');
// Pour d�commenter le passage, il suffit de supprimer le 'slash-etoile' ci-dessus et l'�toile-slash' ci-dessous.
if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}
//======================================================================================


$extract_mode=isset($_POST['extract_mode']) ? $_POST['extract_mode'] : (isset($_GET['extract_mode']) ? $_GET['extract_mode'] : NULL);
$nb_tot_eleves=isset($_POST['nb_tot_eleves']) ? $_POST['nb_tot_eleves'] : (isset($_GET['nb_tot_eleves']) ? $_GET['nb_tot_eleves'] : NULL);

echo '<link rel="stylesheet" type="text/css" href="mod_notanet.css">';

//**************** EN-TETE *****************
$titre_page = "Notanet: Correction/Extraction des moyennes";
//echo "<div class='noprint'>\n";
require_once("../lib/header.inc");
//echo "</div>\n";
//**************** FIN EN-TETE *****************

// Biblioth�que pour Notanet et Fiches brevet
include("lib_brevets.php");

echo "<div class='noprint'>\n";
echo "<p class='bold'><a href='../accueil.php'>Accueil</a> | <a href='index.php'>Retour � l'accueil Notanet</a>";

$sql="SELECT DISTINCT type_brevet FROM notanet_ele_type ORDER BY type_brevet";
$res=mysql_query($sql);
if(mysql_num_rows($res)==0) {
	echo "</p>\n";
	echo "</div>\n";

	echo "<p>Aucune association �l�ve/type de brevet n'a encore �t� r�alis�e.<br />Commencez par <a href='select_eleves.php'>s�lectionner les �l�ves</a></p>\n";

	require("../lib/footer.inc.php");
	die();
}

$sql="SELECT DISTINCT type_brevet FROM notanet_corresp ORDER BY type_brevet";
$res=mysql_query($sql);
if(mysql_num_rows($res)==0) {
	echo "</p>\n";
	echo "</div>\n";

	echo "<p>Aucune association mati�res/type de brevet n'a encore �t� r�alis�e.<br />Commencez par <a href='select_matieres.php'>s�lectionner les mati�res</a></p>\n";

	require("../lib/footer.inc.php");
	die();
}

if(!isset($extract_mode)) {
	echo "</p>\n";
	echo "</div>\n";

	echo "<p>Voulez-vous: ";
	//echo "<br />\n";
	echo "</p>\n";
	echo "<ul>\n";
	echo "<li><a href='".$_SERVER['PHP_SELF']."?extract_mode=select'>S�lectionner des �l�ves individuellement.</a></li>\n";
	echo "<li><a href='".$_SERVER['PHP_SELF']."?extract_mode=tous'>Afficher tous les �l�ves pour lesquels l'extraction/enregistrement des moyennes a �chou�.</a><br />Tous les �l�ves pour lesquels il n'y a pas d'enregistrement dans la table 'notanet' seront affich�s.</li>\n";
	echo "</ul>\n";
}
else {
	echo " | <a href='".$_SERVER['PHP_SELF']."'>Choisir un autre mode d'extraction</a>";
	echo "</p>\n";
	echo "</div>\n";

	//=========================================================
	unset($tab_mat);
	$sql="SELECT * FROM notanet_corresp ORDER BY type_brevet;";
	$res1=mysql_query($sql);
	while($lig1=mysql_fetch_object($res1)) {
		$sql="SELECT * FROM notanet_corresp WHERE type_brevet='$lig1->type_brevet';";
		$res2=mysql_query($sql);

		unset($id_matiere);
		unset($statut_matiere);

		while($lig2=mysql_fetch_object($res2)) {
			$id_matiere[$lig2->id_mat][]=$lig2->matiere;
			//$statut_matiere[$lig2->id_mat][]=$lig2->statut;
			$statut_matiere[$lig2->id_mat]=$lig2->statut;
		}

		$tab_mat[$lig1->type_brevet]=array();
		/*
		for($j=$indice_premiere_matiere;$j<=$indice_max_matieres;$j++) {
			$tab_mat[$lig1->type_brevet][$j]=$id_matiere[$j];
		}
		*/
		$tab_mat[$lig1->type_brevet]['id_matiere']=$id_matiere;
		$tab_mat[$lig1->type_brevet]['statut_matiere']=$statut_matiere;
	}
	//=========================================================

	if(!isset($_POST['enregistrer_extract_moy'])) {
		$compteur_champs_notes=0;

		if($extract_mode=="tous") {

			$sql="SELECT DISTINCT jec.id_classe FROM j_eleves_classes jec,
													notanet_ele_type n,
													notanet_corresp nc
						WHERE n.login=jec.login AND
							n.type_brevet=nc.type_brevet
						ORDER BY id_classe";
			$res=mysql_query($sql);
			if(mysql_num_rows($res)==0) {
				echo "<p>Il semble que des associations soient manquantes.<br />Auriez-vous saut� des �tapes?</p>\n";

				require("../lib/footer.inc.php");
				die();
			}
			else {
				unset($id_classe);

				$cpt=0;
				while($lig=mysql_fetch_object($res)) {
					$id_classe[$cpt]=$lig->id_classe;
					$cpt++;
				}
			}


			$sql="SELECT net.login FROM notanet_ele_type net
				LEFT JOIN notanet n ON net.login=n.login
				WHERE n.login is NULL;";
			$test_na=mysql_query($sql);
			//if($test_na){
			if(mysql_num_rows($test_na)==0){
				echo "<p>Tous les �l�ves associ�s � un type de brevet ont bien des enregistrements dans la table 'notanet'.</p>\n";
				require("../lib/footer.inc.php");
				die();
			}


			echo "<form action='".$_SERVER['PHP_SELF']."' name='form_extract' method='post' target='_blank'>\n";
			echo add_token_field();



			// Boucle �l�ves:
			$num_eleve=0;
			/*
			for($i=0;$i<count($id_classe);$i++){
				$classe=get_classe_from_id($id_classe[$i]);
				echo "<h4>Classe de ".$classe."</h4>\n";
				echo "<blockquote>\n";

				//$call_eleve = mysql_query("SELECT DISTINCT e.* FROM eleves e, j_eleves_classes c WHERE (c.id_classe='$id_classe[$i]' and e.login = c.login) order by c.id_classe,nom,prenom");
				$sql="SELECT DISTINCT e.*,n.type_brevet FROM eleves e,
								j_eleves_classes jec,
								notanet_ele_type n
							WHERE (jec.id_classe='$id_classe[$i]' AND
									e.login=jec.login AND
									n.login=e.login)
							ORDER BY jec.id_classe,e.nom,e.prenom";
			*/
			while($lig_ele=mysql_fetch_object($test_na)) {

				$sql="SELECT DISTINCT e.*,n.type_brevet FROM eleves e,
								notanet_ele_type n
							WHERE (e.login='".$lig_ele->login."' AND n.login=e.login);";
				//echo $sql;
				$call_eleve = mysql_query($sql);
				$nombreligne = mysql_num_rows($call_eleve);
				while($ligne=mysql_fetch_object($call_eleve)){
					unset($tab_ele);
					$tab_ele=array();

					$tab_ele['nom']=$ligne->nom;
					$tab_ele['prenom']=$ligne->prenom;
					$tab_ele['login']=$ligne->login;
					$tab_ele['no_gep']=$ligne->no_gep;
					$tab_ele['type_brevet']=$ligne->type_brevet;

					// La classe n'est utilis�e dans tab_extract_moy($tab_ele, $id_clas) que pour r�cup�rer la liste des p�riodes.
					$sql="SELECT id_classe FROM j_eleves_classes WHERE login='".$lig_ele->login."' ORDER BY periode DESC LIMIT 1;";
					//echo "$sql<br />";
					$res_clas=mysql_query($sql);
					$lig_clas=mysql_fetch_object($res_clas);
					$id_clas=$lig_clas->id_classe;

					/*
					$sql="SELECT type_brevet FROM notanet_ele_type WHERE login='$ligne->login';";
					$res2=mysql_query($sql);
					$type_brevet
					*/

					// ********************************************************************************
					// VERIFIER SI LES ASSOCIATIONS SONT FAITES POUR LE TYPE BREVET $ligne->type_brevet
					// ********************************************************************************
					$sql="SELECT 1=1 FROM notanet_corresp WHERE type_brevet='$ligne->type_brevet';";
					$res=mysql_query($sql);
					if(mysql_num_rows($res)>0) {
						//tab_extract_moy($tab_ele, $id_classe[$i]);
						tab_extract_moy($tab_ele, $id_clas);
						flush();
					}
					else {
						echo "<p><b>".strtoupper($ligne->nom)." ".ucfirst(strtolower($ligne->prenom))."</b>: <span style='color:red;'>Pas d'associations de mati�res effectu�es pour <b>".$tab_type_brevet[$ligne->type_brevet]."</b></span></p>\n";

						echo "INE: <input type='hidden' name='INE[$num_eleve]' value='$ligne->no_gep' />\n";
						echo "<input type='hidden' name='nom_eleve[$num_eleve]' value=\"".$tab_ele['nom']." ".$tab_ele['prenom']." ($classe)\" />\n";
					}
					$num_eleve++;
				}
				echo "</blockquote>\n";
			}

			echo "<input type='hidden' name='extract_mode' value='$extract_mode' />\n";
			echo "<input type='hidden' name='nb_tot_eleves' value='$num_eleve' />\n";
			//echo "<input type='submit' name='choix_corrections' value='Valider les corrections' />\n";
			echo "<input type='submit' name='enregistrer_extract_moy' value='Enregistrer' />\n";
			//echo "<p>Valider les corrections ci-dessus permet de g�n�rer un nouveau fichier d'export tenant compte de vos modifications.</p>";
			echo "</form>\n";

		}
		elseif($extract_mode=="select") {

			//if(!isset($_POST['valider_select_eleve'])) {
			if(!isset($_POST['afficher_select_eleve'])) {
				echo "<form action='".$_SERVER['PHP_SELF']."' name='form_extract' method='post'>\n";

				// A FAIRE...
				$cpt=0;
				$sql="SELECT DISTINCT id_classe FROM j_eleves_classes jec, notanet_ele_type net WHERE (jec.login=net.login) ORDER BY id_classe;";
				//echo "$sql<br />";
				$res_clas=mysql_query($sql);
				while($lig_clas=mysql_fetch_object($res_clas)) {

					$classe=get_classe_from_id($lig_clas->id_classe);
					echo "<h4>Classe de ".$classe."</h4>\n";
					echo "<blockquote>\n";

					$sql="SELECT DISTINCT e.*,n.type_brevet FROM eleves e,
									j_eleves_classes jec,
									notanet_ele_type n
								WHERE (jec.id_classe='".$lig_clas->id_classe."' AND
										e.login=jec.login AND
										n.login=e.login)
								ORDER BY e.nom,e.prenom";
					//echo "$sql<br />";
					$res_ele=mysql_query($sql);
					if(mysql_num_rows($res_ele)==0) {
						echo "<p>Aucun �l�ve dans la classe $classe ne semble avoir �t� trouv�.</p>\n";
					}
					else{
						echo "<table class='boireaus' border='1'>\n";
						echo "<tr>\n";
						echo "<th>El�ve</th>\n";
						echo "<th>";
						echo "S�lectionner";
						echo "</th>\n";
						echo "</tr>\n";
						$alt=1;
						//$cpt=0;
						while($lig_ele=mysql_fetch_object($res_ele)) {
							$alt=$alt*(-1);
							echo "<tr class='lig$alt'>\n";

							echo "<td><label for='ele_login_$cpt'>$lig_ele->nom $lig_ele->prenom</label></td>\n";
							echo "<td><input type='checkbox' id='ele_login_$cpt' name='ele_login[]' value=\"$lig_ele->login\" /></td>\n";

							echo "</tr>\n";
							$cpt++;
						}
						echo "</table>\n";
					}
					echo "<input type='submit' name='valider_select_eleve_$lig_clas->id_classe' value='Afficher les �l�ves s�lectionn�s' />\n";
					echo "</blockquote>\n";
				}
				echo "<input type='hidden' name='extract_mode' value='$extract_mode' />\n";
				echo "<input type='hidden' name='afficher_select_eleve' value='y' />\n";
				echo "<input type='submit' name='valider_select_eleve' value='Afficher les �l�ves s�lectionn�s' />\n";
				echo "</form>\n";
				echo "<p><br /></p>\n";
			}
			else {

				$num_eleve=0;
				$ele_login=isset($_POST['ele_login']) ? $_POST['ele_login'] : NULL;
				if(!isset($ele_login)) {
					echo "<p>Vous n'avez s�lectionn� aucun �l�ve.</p>\n";
				}
				else{
					echo "<form action='".$_SERVER['PHP_SELF']."' name='form_extract' method='post' target='_blank'>\n";
					echo add_token_field();

					for($i=0;$i<count($ele_login);$i++) {

						$sql="SELECT DISTINCT e.*,n.type_brevet FROM eleves e,
										notanet_ele_type n
									WHERE (n.login=e.login AND
											e.login='".$ele_login[$i]."')
									ORDER BY e.nom,e.prenom;";
						//echo "$sql<br />";
						$call_eleve = mysql_query($sql);

						if(mysql_num_rows($call_eleve)==0) {
							echo "<p>L'�l�ve dont le login est ".$ele_login[$i]." n'est pas dans la table 'eleves' ou alors il n'est pas associ� � un type de brevet.</p>\n";
						}
						else {
							$ligne=mysql_fetch_object($call_eleve);
							unset($tab_ele);
							$tab_ele=array();

							$tab_ele['nom']=$ligne->nom;
							$tab_ele['prenom']=$ligne->prenom;
							$tab_ele['login']=$ligne->login;
							$tab_ele['no_gep']=$ligne->no_gep;
							$tab_ele['type_brevet']=$ligne->type_brevet;

							// La classe n'est utilis�e dans tab_extract_moy($tab_ele, $id_clas) que pour r�cup�rer la liste des p�riodes.
							$sql="SELECT id_classe FROM j_eleves_classes WHERE login='".$ele_login[$i]."' ORDER BY periode DESC LIMIT 1;";
							//echo "$sql<br />";
							$res_clas=mysql_query($sql);
							$lig_clas=mysql_fetch_object($res_clas);
							$id_clas=$lig_clas->id_classe;

							/*
							$sql="SELECT type_brevet FROM notanet_ele_type WHERE login='$ligne->login';";
							$res2=mysql_query($sql);
							$type_brevet
							*/

							// ********************************************************************************
							// VERIFIER SI LES ASSOCIATIONS SONT FAITES POUR LE TYPE BREVET $ligne->type_brevet
							// ********************************************************************************
							$sql="SELECT 1=1 FROM notanet_corresp WHERE type_brevet='$ligne->type_brevet';";
							$res=mysql_query($sql);
							if(mysql_num_rows($res)>0) {
								//tab_extract_moy($tab_ele, $id_classe[$i]);
								tab_extract_moy($tab_ele, $id_clas);
								flush();
							}
							else {
								echo "<p><b>".strtoupper($ligne->nom)." ".ucfirst(strtolower($ligne->prenom))."</b>: <span style='color:red;'>Pas d'associations de mati�res effectu�es pour <b>".$tab_type_brevet[$ligne->type_brevet]."</b></span></p>\n";

								echo "INE: <input type='hidden' name='INE[$num_eleve]' value='$ligne->no_gep' />\n";
								echo "<input type='hidden' name='nom_eleve[$num_eleve]' value=\"".$tab_ele['nom']." ".$tab_ele['prenom']." ($classe)\" />\n";
							}
							$num_eleve++;
						}

					}

					echo "<input type='hidden' name='extract_mode' value='$extract_mode' />\n";
					echo "<input type='hidden' name='nb_tot_eleves' value='$num_eleve' />\n";
					//echo "<input type='submit' name='choix_corrections' value='Valider les corrections' />\n";
					echo "<input type='submit' name='enregistrer_extract_moy' value='Enregistrer' />\n";
					//echo "<p>Valider les corrections ci-dessus permet de g�n�rer un nouveau fichier d'export tenant compte de vos modifications.</p>";
					echo "</form>\n";

				}
				echo "<p><br /></p>\n";

			}
		}

		echo "<p><i>NOTES:</i></p>\n";
		echo "<ul>\n";
		echo "<li><p><i>Rappel:</i> Seuls les �l�ves pour lesquels aucune erreur/ind�termination n'est signal�e auront leur exportation r�alis�e.</p></li>\n";
		echo "<li><p>Si pour une raison ou une autre (<i>d�part en cours d'ann�e,...</i>), vous souhaitez ne pas effectuer l'export pour un/des �l�ve(s) particulier(s), il suffit de vider la moyenne dans une mati�re non optionnelle.</p></li>\n";
		echo "<li><p id='js_retablir_notes_enregistrees' style='display:none'>Si vous souhaitez r�injecter vos modifications pr�c�demment enregistr�es, vous pouvez cependant utiliser le lien suivant&nbsp;<br /><a href='#' onclick='retablir_notes_enregistrees(); return false;'>R�tablir toutes les notes pr�c�demment enregistr�es</a></p>\n";
		echo "</li>\n";
		echo "</ul>\n";


		echo "<script type='text/javascript'>
/*
function bourriner_les_notes() {
	for(i=0;i<=$compteur_champs_notes;i++) {
		if(document.getElementById('n'+i)) {
			document.getElementById('n'+i).value='AB';
		}
	}
}
*/
temoin='n';
for(i=0;i<=$compteur_champs_notes;i++) {
	if(document.getElementById('note_precedemment_enregistree_'+i)) {
		temoin='y';
		break;
	}
}
if(temoin=='y') {
	document.getElementById('js_retablir_notes_enregistrees').style.display='';
}

function retablir_notes_enregistrees() {
	for(i=0;i<=$compteur_champs_notes;i++) {
		if(document.getElementById('note_precedemment_enregistree_'+i)) {
			if(document.getElementById('n'+i)) {
				document.getElementById('n'+i).value=document.getElementById('note_precedemment_enregistree_'+i).innerHTML;
			}
		}
	}
}
</script>\n";

	}
	else {
		check_token(false);

		//echo "<form action='generer_csv.php' name='form_generer_csv' method='post' target='_blank'>\n";

		$INE=$_POST['INE'];
		$nom_eleve=$_POST['nom_eleve'];
		$login_eleve="";
		$id_classe_eleve=0;
		//$fich_notanet=$_POST['fich_notanet'];


		/*
		echo "<p>Suppression d'�ventuels enregistrements ant�rieurs.</p>\n";
		if($extract_mode=="tous") {
			$sql="DELETE FROM notanet;";
			$nettoyage=mysql_query($sql);
		}
		elseif((my_ereg("[0-9]",$extract_mode))&&(strlen(my_ereg_replace("[0-9]","",$extract_mode))==0)) {
			$sql="SELECT login FROM notanet_ele_type WHERE type_brevet='$extract_mode';";
			$res=mysql_query($sql);
			if(mysql_num_rows($res)>0) {
				while($lig=mysql_fetch_object($res)) {
					$sql="DELETE FROM notanet WHERE login='$lig->login';";
					$nettoyage=mysql_query($sql);
				}
			}
		}
		*/

		// Boucle sur la liste des �l�ves...
		//for($m=0;$m<count($INE);$m++){
		for($m=0;$m<$nb_tot_eleves;$m++) {
			unset($moy_NOTANET);
			$erreur="";
			//echo "INE[$m]=$INE[$m]<br />";
			echo "<p><b>$nom_eleve[$m]</b><br />\n";
			if($INE[$m]==""){
				echo "<span style='color:red'>ERREUR</span>: Pas de num�ro INE pour cet �l�ve.<br />\n";
				$erreur="oui";
			}
			else{
				$sql="SELECT login FROM eleves WHERE no_gep='".$INE[$m]."'";
				$res_login_ele=mysql_query($sql);
				if(mysql_num_rows($res_login_ele)>0){
					$lig_login_ele=mysql_fetch_object($res_login_ele);
					$login_eleve=$lig_login_ele->login;

					$sql="SELECT id_classe FROM j_eleves_classes WHERE login='$login_eleve' ORDER BY periode DESC";
					$res_classe_ele=mysql_query($sql);
					if(mysql_num_rows($res_classe_ele)>0){
						$lig_classe_ele=mysql_fetch_object($res_classe_ele);
						$id_classe_eleve=$lig_classe_ele->id_classe;
					}
					else{
						echo "<span style='color:red'>ERREUR</span>: La classe de l'�l�ve n'a pas �t� r�cup�r�e.<br />Sa fiche brevet ne sera pas g�n�r�e.<br />\n";
					}
				}
				else{
					echo "<span style='color:red'>ERREUR</span>: Le LOGIN de l'�l�ve n'a pas �t� r�cup�r�.<br />Son export notanet ne sera pas g�n�r�, pas plus que sa fiche brevet.<br />\n";
					$erreur="oui";
				}
			}


			if($erreur!="oui"){
				// On ne poursuit que si on a pu r�cup�rer un login d'�l�ve.


				$sql="DELETE FROM notanet WHERE login='$login_eleve';";
				$nettoyage=mysql_query($sql);


				$sql="SELECT n.type_brevet FROM notanet_ele_type n
							WHERE n.login='$login_eleve';";
				//echo "$sql<br />";
				$res_type_brevet_eleve=mysql_query($sql);
				if(mysql_num_rows($res_type_brevet_eleve)==0) {
					echo "<span style='color:red'>ERREUR</span>: Le type de brevet n'a pas �t� choisi pour cet �l�ve.<br />\n";
				}
				else {
					$lig_type_brevet_eleve=mysql_fetch_object($res_type_brevet_eleve);

					echo "(<i><span style='font-size:x-small;'>".$tab_type_brevet[$lig_type_brevet_eleve->type_brevet]."</span></i>)<br />";

					$tabmatieres=tabmatieres($lig_type_brevet_eleve->type_brevet);

					if(!isset($tab_mat[$lig_type_brevet_eleve->type_brevet])) {
						echo "<span style='color:red'>ERREUR</span>: Les associations de mati�res n'ont pas �t� d�finies pour le type de brevet ".$tab_type_brevet[$lig_type_brevet_eleve->type_brevet].".<br />\n";
					}
					else {
						$id_matiere=$tab_mat[$lig_type_brevet_eleve->type_brevet]['id_matiere'];
						$statut_matiere=$tab_mat[$lig_type_brevet_eleve->type_brevet]['statut_matiere'];

						$sql="DELETE FROM notanet WHERE login='$login_eleve';";
						//echo "$sql<br />";
						$nettoyage=mysql_query($sql);

						unset($tab_opt_matiere_eleve);
						$tab_opt_matiere_eleve=array();
						for($j=$indice_premiere_matiere;$j<=$indice_max_matieres;$j++){
							//if($tabmatieres[$j][0]!=''){
							if(($tabmatieres[$j][0]!='')&&($statut_matiere[$j]!='non dispensee dans l etablissement')){
								// Liste des valeurs sp�ciales autoris�es pour la mati�re courante:
								unset($tabvalautorisees);
								$tabvalautorisees=explode(" ",$tabmatieres[$j][-3]);

								if($tabmatieres[$j]['socle']=='n') {

									$temoin_moyenne=0;
									// On passe en revue les diff�rentes options d'une m�me mati�re (LV1($j): AGL1 ou ALL1($k))
									for($k=0;$k<count($id_matiere[$j]);$k++){

										// R�cup�ration des moyennes post�es via le formulaire
										//$moy[$j][$k]=$_POST['moy_'.$j.'_'.$k];
										$moy[$j][$k]=isset($_POST['moy_'.$j.'_'.$k]) ? $_POST['moy_'.$j.'_'.$k] : NULL;
										//if($moy[$j][$k][$m]!=""){
										if((isset($moy[$j][$k][$m]))&&($moy[$j][$k][$m]!="")) {
//echo "<br />\$moy[$j][$k][$m]=".$moy[$j][$k][$m]."<br />";
											$temoin_moyenne++;


											// L'�l�ve fait-il ALL1 ou AGL1 parmi les options de LV1
											$tab_opt_matiere_eleve[$j]=$id_matiere[$j][$k];


											// A EFFECTUER: Contr�le des valeurs
											//...
											//if(($moy[$j][$k][$m]!="AB")&&($moy[$j][$k][$m]!="DI")&&($moy[$j][$k][$m]!="NN")){
											// Il faudrait pour chaque mati�re ($j) contr�ler les valeurs autoris�es pour la mati�re...
											$test_valeur_speciale_autorisee="non";
											for($n=0;$n<count($tabvalautorisees);$n++){
												if($moy[$j][$k][$m]==$tabvalautorisees[$n]){
													$test_valeur_speciale_autorisee="oui";
												}
											}
											if($test_valeur_speciale_autorisee!="oui"){
												if(strlen(preg_replace("/[0-9\.]/","",$moy[$j][$k][$m]))!=0){
													echo "<br /><span style='color:red'>ERREUR</span>: La valeur saisie n'est pas valide: ";
													echo $id_matiere[$j][$k]."=".$moy[$j][$k][$m];
													echo "<br />\n";
													$erreur="oui";
												}
												else{
													// Le test ci-dessous convient parce que la premi�re mati�re n'est pas optionnelle...
													//if(($j!=101)||($k!=0)){
													if(($j!=$indice_premiere_matiere)||($k!=0)){
														echo " - ";
													}
													// On affiche la correspondance AGL1=12.0,...
													echo $id_matiere[$j][$k]."=".$moy[$j][$k][$m];
													$moy_NOTANET[$j]=round($moy[$j][$k][$m]*2)/2;
												}
											}
											else{
												// Le test ci-dessous convient parce que la premi�re mati�re n'est pas optionnelle...
												//if(($j!=101)||($k!=0)){
												if(($j!=$indice_premiere_matiere)||($k!=0)){
													echo " - ";
												}
												echo "<span style='color:purple;'>".$id_matiere[$j][$k]."=".$moy[$j][$k][$m]."</span>";
												$moy_NOTANET[$j]=$moy[$j][$k][$m];
											}
										}
									}
//echo "<br />\$moy_NOTANET[$j]=".$moy_NOTANET[$j]."<br />";
									if($temoin_moyenne==0){
										if($statut_matiere[$j]=="imposee"){
											//echo "<br /><span style='color:red'>ERREUR</span>: Pas de moyenne � une mati�re non optionnelle.";
											echo "<br /><span style='color:red'>ERREUR</span>: Pas de moyenne � une mati�re non optionnelle: ".$id_matiere[$j][0]."<br />(<i>valeurs non num�riques autoris�es: ".$tabmatieres[$j][-3]."</i>)";
											echo "<br />\n";
											$erreur="oui";
										}
									}
									else{
										if($temoin_moyenne==1){
											// OK!
											// On n'a pas d'erreur jusque l�...
										}
										else{
											echo "<br /><span style='color:red'>ERREUR</span>: Il y a plus d'une moyenne � deux options d'une m�me mati�re: ";
											for($k=0;$k<count($id_matiere[$j]);$k++){
												if($moy[$j][$k][$m]!=""){
													echo $id_matiere[$j][$k]."=".$moy[$j][$k][$m]." -\n";
												}
											}
											echo "<br />\n";
											$erreur="oui";
										}
									}
								}
								else {
									// SOCLES B2I ET A2
									$k=0;
									$moy[$j][$k]=isset($_POST['moy_'.$j.'_'.$k]) ? $_POST['moy_'.$j.'_'.$k] : NULL;

									if((isset($moy[$j][$k][$m]))&&($moy[$j][$k][$m]!="")) {

										$test_valeur_speciale_autorisee="non";
										for($n=0;$n<count($tabvalautorisees);$n++){
											if($moy[$j][$k][$m]==$tabvalautorisees[$n]){
												$test_valeur_speciale_autorisee="oui";
											}
										}
										if($test_valeur_speciale_autorisee!="oui"){
											if(strlen(preg_replace("/[0-9\.]/","",$moy[$j][$k][$m]))!=0){
												echo "<br /><span style='color:red'>ERREUR</span>: La valeur saisie n'est pas valide: ";
												echo $tabmatieres[$j][0]."=".$moy[$j][$k][$m];
												echo "<br />\n";
												$erreur="oui";
											}
											else{
												// Le test ci-dessous convient parce que la premi�re mati�re n'est pas optionnelle...
												//if(($j!=101)||($k!=0)){
												if(($j!=$indice_premiere_matiere)||($k!=0)){
													echo " - ";
												}
												// On affiche la correspondance AGL1=12.0,...
												echo $id_matiere[$j][$k]."=".$moy[$j][$k][$m];
												$moy_NOTANET[$j]=round($moy[$j][$k][$m]*2)/2;
											}
										}
										else{
											// Le test ci-dessous convient parce que la premi�re mati�re n'est pas optionnelle...
											//if(($j!=101)||($k!=0)){
											if(($j!=$indice_premiere_matiere)||($k!=0)){
												echo " - ";
											}
											echo "<span style='color:purple;'>".$tabmatieres[$j][0]."=".$moy[$j][$k][$m]."</span>";
											$moy_NOTANET[$j]=$moy[$j][$k][$m];
										}

									}


								}
							}
						}
						echo "<br />\n";
						if($erreur!="oui"){
							// On g�n�re l'export pour cet �l�ve:
							$TOT=0;
							for($j=$indice_premiere_matiere;$j<=$indice_max_matieres;$j++){
								//if(isset($tabmatieres[$j][0])){
								//if(isset($statut_matiere[$j])){
								if(isset($moy_NOTANET[$j])) {
//echo "\$moy_NOTANET[$j]=".$moy_NOTANET[$j]."<br />";
//if($moy_NOTANET[$j]!="") {echo "\$moy_NOTANET[$j] non vide<br />";}
//if("$moy_NOTANET[$j]"!="") {echo "\$moy_NOTANET[$j] non vide<br />";}
									if(($tabmatieres[$j][0]!='')&&($statut_matiere[$j]!='non dispensee dans l etablissement')&&("$moy_NOTANET[$j]"!="")) {
										$ligne_NOTANET=$INE[$m]."|".sprintf("%03d",$j);
										//$ligne_NOTANET=$ligne_NOTANET."|".formate_note_notanet($moy_NOTANET[$j])."|";
//echo "plop<br />";
										$note_notanet="";

										if($tabmatieres[$j]['socle']=='n') {
											switch($tabmatieres[$j][-1]){
												case "POINTS":
													if(("$moy_NOTANET[$j]"!="AB")&&("$moy_NOTANET[$j]"!="DI")&&("$moy_NOTANET[$j]"!="NN")){
//echo "plip<br />";
														$ligne_NOTANET=$ligne_NOTANET."|".formate_note_notanet($moy_NOTANET[$j]*$tabmatieres[$j][-2])."|";
														//$TOT=$TOT+round($moy_NOTANET[$j]*2)/2;
														$TOT=$TOT+round($moy_NOTANET[$j]*$tabmatieres[$j][-2]*2)/2;
														$note_notanet=formate_note_notanet($moy_NOTANET[$j]*$tabmatieres[$j][-2]);
													}
													else{
														$ligne_NOTANET=$ligne_NOTANET."|".$moy_NOTANET[$j]."|";
														$note_notanet=$moy_NOTANET[$j];
													}
													break;
												case "PTSUP":
													if(("$moy_NOTANET[$j]"!="AB")&&("$moy_NOTANET[$j]"!="DI")&&("$moy_NOTANET[$j]"!="NN")){
														$ptsup=$moy_NOTANET[$j]-10;
														if($ptsup>0){
															$ligne_NOTANET=$ligne_NOTANET."|".formate_note_notanet($ptsup)."|";
															//$TOT=$TOT+$ptsup;
															//$TOT=$TOT+round($ptsup*2)/2;
															//$note_notanet=formate_note_notanet($ptsup);
															$TOT=$TOT+round($ptsup*$tabmatieres[$j][-2]*2)/2;
															$note_notanet=formate_note_notanet($ptsup*$tabmatieres[$j][-2]);
														}
														else{
															$ligne_NOTANET=$ligne_NOTANET."|".formate_note_notanet(0)."|";
															$note_notanet=formate_note_notanet(0);
														}
													}
													else {
														$ligne_NOTANET=$ligne_NOTANET."|".$moy_NOTANET[$j]."|";
														$note_notanet=$moy_NOTANET[$j];
													}
													break;
												case "NOTNONCA":
													if(("$moy_NOTANET[$j]"!="AB")&&("$moy_NOTANET[$j]"!="DI")&&("$moy_NOTANET[$j]"!="NN")){
														$ligne_NOTANET=$ligne_NOTANET."|".formate_note_notanet($moy_NOTANET[$j])."|";
														$note_notanet=formate_note_notanet($moy_NOTANET[$j]);
													}
													else {
														$ligne_NOTANET=$ligne_NOTANET."|".$moy_NOTANET[$j]."|";
														$note_notanet=$moy_NOTANET[$j];
													}
													break;
											}
										}
										else {
											$ligne_NOTANET=$ligne_NOTANET."|".$moy_NOTANET[$j]."|";
											$note_notanet=$moy_NOTANET[$j];
										}


										echo "<input type='hidden' name='lig_notanet[]' value=\"$ligne_NOTANET\" />\n";
										echo colore_ligne_notanet($ligne_NOTANET)."<br />\n";
										$tabnotanet[]=$ligne_NOTANET;

										//echo "\$id_classe_eleve=$id_classe_eleve et \$login_eleve=$login_eleve<br />";

										if(($id_classe_eleve!=0)&&($login_eleve!="")){
											/*
											$sql="INSERT INTO notanet SET login='$login_eleve',
																		ine='".$INE[$m]."',
																		id_mat='".$j."',
																		matiere='".$tabmatieres[$j][0]."',";
											*/
											$sql="INSERT INTO notanet SET login='$login_eleve',
																		ine='".$INE[$m]."',
																		id_mat='".$j."',
																		notanet_mat='".$tabmatieres[$j][0]."',";
											if(isset($tab_opt_matiere_eleve[$j])){
												//$sql.="mat='".$tab_opt_matiere_eleve[$j]."',";
												$sql.="matiere='".$tab_opt_matiere_eleve[$j]."',";
											}
											//if(($moy_NOTANET[$j]!="AB")&&($moy_NOTANET[$j]!="DI")&&($moy_NOTANET[$j]!="NN")){
											//if(($moy_NOTANET[$j]!="MS")&&($moy_NOTANET[$j]!="ME")&&($moy_NOTANET[$j]!="MN")&&($moy_NOTANET[$j]!="AB")&&($moy_NOTANET[$j]!="DI")&&($moy_NOTANET[$j]!="NN")){
											//if(($moy_NOTANET[$j]!="MS")&&($moy_NOTANET[$j]!="ME")&&($moy_NOTANET[$j]!="MN")&&($moy_NOTANET[$j]!="AB")&&($moy_NOTANET[$j]!="DI")&&($moy_NOTANET[$j]!="NN")&&($moy_NOTANET[$j]!="VA")&&($moy_NOTANET[$j]!="NV")){
											if(!in_array($moy_NOTANET[$j],$tab_liste_notes_non_numeriques)) {
												$sql.="note='".formate_note_notanet($moy_NOTANET[$j])."',";
											}
											else{
												$sql.="note='".$moy_NOTANET[$j]."',";
											}
											$sql.="note_notanet='".$note_notanet."',";
											$sql.="id_classe='$id_classe_eleve'";
											//echo "$sql<br />";
											$res_insert=mysql_query($sql);
											if(!$res_insert){
												echo "<span style='color:red'>ERREUR</span> lors de l'insertion des informations dans la table 'notanet'.<br />La fiche brevet ne pourra pas �tre g�n�r�e.<br />\n";
											}
										}
									}
								}
							}

							// Dans le cas brevet PRO, il ne faut retenir qu'une seule des deux mati�res 103 et 104
							if(($lig_type_brevet_eleve->type_brevet==2)||($lig_type_brevet_eleve->type_brevet==3)) {
								$num_matiere_LV1=103;
								$num_matiere_ScPhy=104;
								if(("$moy_NOTANET[$num_matiere_LV1]"!="AB")&&("$moy_NOTANET[$num_matiere_LV1]"!="DI")&&("$moy_NOTANET[$num_matiere_LV1]"!="NN")){
									if(("$moy_NOTANET[$num_matiere_ScPhy]"!="AB")&&("$moy_NOTANET[$num_matiere_ScPhy]"!="DI")&&("$moy_NOTANET[$num_matiere_ScPhy]"!="NN")) {
										// Il ne faut retenir qu'une seule des deux notes
										if($moy_NOTANET[$num_matiere_ScPhy]>$moy_NOTANET[$num_matiere_LV1]) {
											$TOT-=round($moy_NOTANET[$num_matiere_LV1]*$tabmatieres[$num_matiere_LV1][-2]*2)/2;
										}
										else {
											$TOT-=round($moy_NOTANET[$num_matiere_ScPhy]*$tabmatieres[$num_matiere_ScPhy][-2]*2)/2;
										}
									}
								}
							}

							echo colore_ligne_notanet($INE[$m]."|TOT|".sprintf("%02.2f",$TOT)."|")."<br />\n";
							$tabnotanet[]=$INE[$m]."|TOT|".sprintf("%02.2f",$TOT)."|";

							echo "<input type='hidden' name='lig_notanet[]' value=\"".$INE[$m]."|TOT|".sprintf("%02.2f",$TOT)."|\" />\n";

							// Pour afficher 95 sous la forme 095.00:
							//echo colore_ligne_notanet($INE[$m]."|TOT|".sprintf("%06.2f",$TOT)."|")."<br />\n";
							//$tabnotanet[]=$INE[$m]."|TOT|".sprintf("%06.2f",$TOT)."|";
						}
					}
				}
			}
			echo "=========================</p>\n";
		}

		//echo "<input type='submit' name='generer_csv' value='G�n�rer un CSV de cet enregistrement' />\n";
		//echo "</form>\n";
		echo "<p><br /></p>\n";
	}
}


require("../lib/footer.inc.php");
?>
