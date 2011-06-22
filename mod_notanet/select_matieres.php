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
// INSERT INTO droits VALUES('/mod_notanet/select_matieres.php','V','F','F','F','F','F','F','F','Notanet: Association Types de brevet/Mati�res','');
// Pour d�commenter le passage, il suffit de supprimer le 'slash-etoile' ci-dessus et l'�toile-slash' ci-dessous.
if (!checkAccess()) {
	header("Location: ../logout.php?auto=1");
	die();
}
//======================================================================================


// Type de brevet:
$type_brevet=isset($_POST['type_brevet']) ? $_POST['type_brevet'] : (isset($_GET['type_brevet']) ? $_GET['type_brevet'] : NULL);
if(($type_brevet!=0)&&
($type_brevet!=0)&&
($type_brevet!=1)&&
($type_brevet!=2)&&
($type_brevet!=3)&&
($type_brevet!=4)&&
($type_brevet!=5)&&
($type_brevet!=6)&&
($type_brevet!=7)) {
	$type_brevet=NULL;
}

if(!isset($msg)) {$msg="";}

// Biblioth�que pour Notanet et Fiches brevet
include("lib_brevets.php");

$id_matiere=array();
//for($j=101;$j<=$indice_max_matieres;$j++){
for($j=$indice_premiere_matiere;$j<=$indice_max_matieres;$j++){
	if(isset($_POST['id_matiere'.$j])){
		$id_matiere[$j]=$_POST['id_matiere'.$j];

	}
}
$statut_matiere=isset($_POST['statut_matiere']) ? $_POST['statut_matiere'] : NULL;

$choix_matieres=isset($_POST['choix_matieres']) ? $_POST['choix_matieres'] : NULL;
$is_posted=isset($_POST['is_posted']) ? $_POST['is_posted'] : NULL;

//if((isset($choix_matieres))&&(isset($type_brevet))) {
if((isset($is_posted))&&(isset($type_brevet))) {
	check_token();

	//echo "\$choix_matieres=$choix_matieres<br />";
	//echo "\$type_brevet=$type_brevet<br />";

	$tabmatieres=tabmatieres($type_brevet);

	// Nettoyage des choix de mati�res dans 'notanet_corresp'
	$sql="DELETE FROM notanet_corresp WHERE type_brevet='$type_brevet';";
	$res_nettoyage=mysql_query($sql);
	if(!$res_nettoyage){
		$msg.="ERREUR lors du nettoyage de la table 'notanet_corresp'.<br />\n";
	}
	else {
		$nb_err=0;
		$cpt_enr=0;
		// Enregistrement des choix de mati�res dans 'notanet_corresp'
		for($j=$indice_premiere_matiere;$j<=$indice_max_matieres;$j++){
			//if($tabmatieres[$j][0]!=''){
			//if(($tabmatieres[$j][0]!='')&&($tabmatieres[$j]['socle']=='n')) {
			if($tabmatieres[$j][0]!=''){
				//if(($tabmatieres[$j]['socle']=='n') {
					if(isset($id_matiere[$j])){
						for($i=0;$i<count($id_matiere[$j]);$i++){
							$sql="INSERT INTO notanet_corresp SET notanet_mat='".$tabmatieres[$j][0]."',
																	matiere='".$id_matiere[$j][$i]."',
																	statut='".$statut_matiere[$j]."',
																	id_mat='$j',
																	type_brevet='$type_brevet';";
							//echo "$sql<br />";
							$res_insert=mysql_query($sql);
							if(!$res_insert) {$nb_err++;}else{$cpt_enr++;}
						}
					}
					else{
						// Cas de mati�res non dispens�es...
						$sql="INSERT INTO notanet_corresp SET notanet_mat='".$tabmatieres[$j][0]."',
																matiere='',
																statut='".$statut_matiere[$j]."',
																id_mat='$j',
																type_brevet='$type_brevet';";
						//echo "$sql<br />";
						$res_insert=mysql_query($sql);
						if(!$res_insert) {$nb_err++;}else{$cpt_enr++;}
					}
				//else {
				//}
			}
		}

		$j_matiere=isset($_POST['j_matiere']) ? $_POST['j_matiere'] : NULL;
		$matiere_a_ajouter=isset($_POST['matiere_a_ajouter']) ? $_POST['matiere_a_ajouter'] : NULL;

		//echo "\$j_matiere=$j_matiere<br />";
		//echo "\$matiere_a_ajouter=$matiere_a_ajouter<br />";

		if(($j_matiere!='')&&($matiere_a_ajouter!='')) {
			$sql="INSERT INTO notanet_corresp SET notanet_mat='".$tabmatieres[$j_matiere][0]."',
													matiere='$matiere_a_ajouter',
													statut='".$statut_matiere[$j_matiere]."',
													id_mat='$j_matiere',
													type_brevet='$type_brevet';";
			//echo "$sql<br />";
			$res_insert=mysql_query($sql);
			if(!$res_insert) {$nb_err++;}else{$cpt_enr++;}

			if($nb_err==0) {$msg.="Enregistrement effectu� pour $cpt_enr mati�re(s).";}
			header("Location: ".$_SERVER['PHP_SELF']."?type_brevet=$type_brevet&msg=".urlencode($msg)."#ancre_$j_matiere");
		}

		if($nb_err==0) {$msg.="Enregistrement effectu� pour $cpt_enr mati�re(s).<br />\n";}
	}
}


//**************** EN-TETE *****************
$titre_page = "Notanet: Associations type de brevet/mati�res";
//echo "<div class='noprint'>\n";
require_once("../lib/header.inc");
//echo "</div>\n";
//**************** FIN EN-TETE *****************

//debug_var();

echo "<div class='noprint'>\n";
echo "<p class='bold'><a href='../accueil.php'>Accueil</a> | <a href='index.php'>Retour � l'accueil Notanet</a>";

// Choix du type de Brevet:
if (!isset($type_brevet)) {
	echo "</p>\n";
	echo "</div>\n";
	echo "<h3>Choix du type de brevet</h3>\n";

	/*
	echo "<p>Choisissez un type de brevet:<br />\n";
	for($i=0;$i<count($tab_type_brevet);$i++){
		echo "<a href='".$_SERVER['PHP_SELF']."?type_brevet=$i'>$tab_type_brevet[$i]</a><br />\n";
	}
	*/

	$sql="SELECT DISTINCT type_brevet FROM notanet_ele_type ORDER BY type_brevet;";
	$res=mysql_query($sql);
	if(mysql_num_rows($res)==0){
		echo "<p>Aucun �l�ve n'est encore associ� � un type de brevet.<br />Commencez par <a href='select_eleves.php'>s�lectionner les �l�ves</a>.</p>\n";

		require("../lib/footer.inc.php");
		die();
	}
	else {
		echo "<p>Choisissez un type de brevet:<br />\n";

		while($lig=mysql_fetch_object($res)) {
			echo "<a href='".$_SERVER['PHP_SELF']."?type_brevet=$lig->type_brevet'>".$tab_type_brevet[$lig->type_brevet]."</a><br />\n";
		}

		echo "</p>\n";
	}

}
else {
	echo " | <a href=\"".$_SERVER['PHP_SELF']."\">Choisir un autre type de brevet</a>";
	echo "</p>\n";
	echo "</div>\n";

	//debug_var();

	$sql="CREATE TABLE IF NOT EXISTS notanet_corresp (
						id INT NOT NULL AUTO_INCREMENT ,
						type_brevet tinyint(4) NOT NULL,
						id_mat tinyint(4) NOT NULL ,
						notanet_mat VARCHAR( 255 ) NOT NULL ,
						matiere VARCHAR( 50 ) NOT NULL ,
						statut enum('imposee','optionnelle','non dispensee dans l etablissement') NOT NULL ,
						PRIMARY KEY  (id)
						)";
	$res_creation_table=mysql_query($sql);
	if(!$res_creation_table){
		echo "<p><b style='color:red;'>ERREUR</b> lors de la cr�ation de la table 'notanet_corresp'.</p>\n";
		require("../lib/footer.inc.php");
		die();
	}

	// Fonction d�finie dans lib_brevets.php
	$tabmatieres=tabmatieres($type_brevet);


	//if(!isset($_POST['choix_matieres'])){

		//$sql="SELECT DISTINCT jec.id_classe FROM j_eleves_classes jec, notanet_ele_type n WHERE n.login=jec.login ORDER BY id_classe";
		$sql="SELECT DISTINCT jec.id_classe FROM j_eleves_classes jec, notanet_ele_type net WHERE net.login=jec.login AND net.type_brevet='$type_brevet' ORDER BY id_classe";
		$res=mysql_query($sql);
		if(mysql_num_rows($res)==0) {
			echo "<p>Aucun �l�ve n'est encore associ� � ce type de brevet.<br />Commencez par <a href='select_eleves.php'>s�lectionner les �l�ves</a>.</p>\n";

			require("../lib/footer.inc.php");
			die();
		}
		else {
			$cpt=0;
			while($lig=mysql_fetch_object($res)) {
				$id_classe[$cpt]=$lig->id_classe;
				$cpt++;
			}
		}

		$conditions="id_classe='$id_classe[0]'";
		if(count($id_classe)==1) {
			echo "<p>La seule classe concern�e est ".get_classe_from_id($id_classe[0]);
		}
		else {
			echo "<p>Les classes concern�es sont ".get_classe_from_id($id_classe[0]);
			for($i=1;$i<count($id_classe);$i++){
				$conditions=$conditions." OR id_classe='$id_classe[$i]'";
				echo ", ".get_classe_from_id($id_classe[$i]);
			}
		}
		echo ".</p>\n";

		echo "<form action='".$_SERVER['PHP_SELF']."' name='form_choix_matieres' method='post'>\n";
		echo add_token_field();
		//echo "<input type='hidden' name='choix1' value='export' />\n";
		echo "<input type='hidden' name='type_brevet' value='$type_brevet' />\n";

		$sql="SELECT DISTINCT j_groupes_matieres.id_matiere FROM j_groupes_matieres,j_groupes_classes WHERE j_groupes_matieres.id_groupe=j_groupes_classes.id_groupe AND $conditions ORDER BY id_matiere";
		//echo "$sql<br />";
		$call_classe_infos = mysql_query($sql);

		$nombre_lignes = mysql_num_rows($call_classe_infos);
		$cpt=0;
		while($ligne=mysql_fetch_object($call_classe_infos)){
			$tab_mat_classes[$cpt]="$ligne->id_matiere";
			$cpt++;
		}

		//echo "<table border='1'>\n";
		echo "<table class='boireaus' summary='Tableau des associations mati�re notanet/mati�re gepi'>\n";
		echo "<tr style='font-weight:bold; text-align:center'>\n";

		echo "<th colspan='2'>NOTANET</th>\n";

		echo "<th colspan='3'>Mati�re</th>\n";

		//echo "<th>&nbsp;</th>\n";
		echo "<th rowspan='2'>Mati�re GEPI</th>\n";

		echo "<tr style='font-weight:bold; text-align:center'>\n";

		echo "<th>Num�ro</th>\n";
		echo "<th>Intitul� de la mati�re NOTANET</th>\n";

		echo "<th>Impos�e</th>\n";
		echo "<th>Optionnelle</th>\n";
		echo "<th>Non dispens�e dans l'�tablissement</th>\n";
		//echo "<th>Mati�re GEPI</th>\n";

		echo "</tr>\n";

		$alt=1;
		for($j=$indice_premiere_matiere;$j<=$indice_max_matieres;$j++){
			if($tabmatieres[$j][0]!=''){
			//if(($tabmatieres[$j][0]!='')&&($tabmatieres[$j]['socle']=='n')) {
				$alt=$alt*(-1);
				echo "<tr class='lig$alt'>\n";
				//echo "<td>".strtoupper($tabmatieres[$j][0])."</td>\n";
				echo "<td>";
				echo sprintf("%03d", $j);
				echo "</td>";
				echo "<td>";
				//echo "<a name='ancre_$j'></a>";
				echo strtoupper($tabmatieres[$j][0])."</td>\n";

				$sql="SELECT * FROM notanet_corresp WHERE notanet_mat='".$tabmatieres[$j][0]."' AND type_brevet='$type_brevet';";
				$res_notanet_corresp=mysql_query($sql);
				if(mysql_num_rows($res_notanet_corresp)>0){
					$lig_notanet_corresp=mysql_fetch_object($res_notanet_corresp);
					echo "<td style='text-align:center'><input type='radio' name='statut_matiere[$j]' value='imposee'";
					if($lig_notanet_corresp->statut=='imposee'){
						echo " checked='true'";
					}
					echo " /></td>\n";

					echo "<td style='text-align:center'><input type='radio' name='statut_matiere[$j]' value='optionnelle'";
					if($lig_notanet_corresp->statut=='optionnelle'){
						echo " checked='true'";
					}
					echo " /></td>\n";

					echo "<td style='text-align:center'><input type='radio' name='statut_matiere[$j]' value='non dispensee dans l etablissement'";
					if($lig_notanet_corresp->statut=='non dispensee dans l etablissement'){
						echo " checked='true'";
					}
					echo " /></td>\n";
				}
				else{
					echo "<td style='text-align:center'><input type='radio' name='statut_matiere[$j]' value='imposee'";
					echo " checked='true'";
					echo " /></td>\n";
					echo "<td style='text-align:center'><input type='radio' name='statut_matiere[$j]' value='optionnelle' /></td>\n";
					echo "<td style='text-align:center'><input type='radio' name='statut_matiere[$j]' value='non dispensee dans l etablissement' /></td>\n";
				}

				echo "<td>\n";

				//echo "$sql<br />";

				//echo "\$type_brevet=$type_brevet \$tabmatieres[$j]['socle']";
				if($tabmatieres[$j]['socle']=='n') {
					/*
					echo "<select multiple='true' size='4' name='id_matiere".$j."[]'>\n";
					echo "<option value=''>&nbsp;</option>\n";
					for($k=0;$k<$cpt;$k++){
						echo "<option value='$tab_mat_classes[$k]'";
						$sql="SELECT * FROM notanet_corresp WHERE notanet_mat='".$tabmatieres[$j][0]."' AND matiere='".$tab_mat_classes[$k]."' AND type_brevet='$type_brevet';";
						$res_test=mysql_query($sql);
						if(mysql_num_rows($res_test)>0){
							echo " selected='true'";
						}
						echo ">$tab_mat_classes[$k]</option>\n";
					}
					echo "</select>\n";
					*/

					echo "<a name='ancre_$j'></a>";

					//$sql="SELECT * FROM notanet_corresp WHERE notanet_mat='".$tabmatieres[$j][0]."' AND type_brevet='$type_brevet' ORDER BY matiere;";
					//$sql="SELECT * FROM notanet_corresp WHERE notanet_mat='".$tabmatieres[$j][0]."' AND type_brevet='$type_brevet' AND matiere!='' ORDER BY matiere;";
					$sql="SELECT * FROM notanet_corresp WHERE notanet_mat='".$tabmatieres[$j][0]."' AND type_brevet='$type_brevet' AND matiere!='' AND matiere!='0' ORDER BY matiere;";
					//echo "$sql<br />";
					$res_test=mysql_query($sql);
					if(mysql_num_rows($res_test)>0){
						$cpt=0;
						echo "<p align='left'>";
						while($lig_tmp=mysql_fetch_object($res_test)) {
							echo "<input type='checkbox' name='id_matiere".$j."[]' id='id_matiere".$j."_$cpt' value='$lig_tmp->matiere' checked /><label for='id_matiere".$j."_$cpt'>$lig_tmp->matiere</label>";
							$sql="SELECT 1=1 FROM matieres WHERE matiere='$lig_tmp->matiere';";
							//echo "$sql<br />";
							$test_matiere=mysql_query($sql);
							if(mysql_num_rows($test_matiere)==0) {echo "<img src='../images/icons/ico_attention.png' width='22' height='19' title=\"Cette mati�re ne correspond plus � une mati�re GEPI cette ann�e (un nouveau nom de mati�re existe peut-�tre cette ann�e).\" alt=\"Cette mati�re ne correspond plus � une mati�re GEPI cette ann�e (un nouveau nom de mati�re existe peut-�tre cette ann�e).\" />\n";}
							else {
								$sql="SELECT 1=1 FROM notanet n, notanet_ele_type net WHERE n.matiere='$lig_tmp->matiere' AND n.login=net.login AND net.type_brevet='$type_brevet';";
								//echo "$sql<br />";
								$test_matiere=mysql_query($sql);
								$nb_ele_matiere=mysql_num_rows($test_matiere);
								if($nb_ele_matiere>0) {
									echo "&nbsp;(<span style='font-style: italic;' title=\"Mati�re associ�e � $nb_ele_matiere enregistrement(s) dans l'extraction notanet pour le type de brevet choisi. Si aucune association n'est signal�e, c'est soit que la mati�re n'est associ�e � aucune note d'�l�ve, soit que l'extraction n'a pas �t� effectu�e (ou pas avec cette mati�re pr�sente)\">$nb_ele_matiere</span>)";
								}
							}
							echo "<br />";
							$cpt++;

						}
					}
					echo "<p align='center'>";
					echo "<a href='#' onclick=\"document.getElementById('j_matiere').value='$j';afficher_div('ajout_matiere','y',10,10);return false;\"> + </a>";

				}
				else {
					echo "<input type='hidden' name='id_matiere".$j."[]' value='' />\n";
				}
				echo "</td>\n";

				echo "</tr>\n";
			}
		}
		echo "</table>\n";

		//==================================================
		$titre="Ajout mati�re";
		$texte_checkbox_matieres="";
		$texte_checkbox_matieres.="<input type='hidden' name='j_matiere' id='j_matiere' value='' />";
		$texte_checkbox_matieres.="<input type='hidden' name='matiere_a_ajouter' id='matiere_a_ajouter' value='' />";
		$sql="SELECT matiere FROM matieres ORDER BY matiere;";
		$res=mysql_query($sql);
		if(mysql_num_rows($res)>0) {
			//$cpt=0;
			while($lig=mysql_fetch_object($res)) {
				//$texte_checkbox_matieres.="<input type='checkbox' name='matiere[]' id='matiere_$cpt' value='$lig->matiere' /><label for='matiere_$cpt'>$lig->matiere</label><br />";
				//$texte_checkbox_matieres.="<a href='#' onclick=\"document.getElementById('matiere_a_ajouter').value='$lig->matiere';return false;\">$lig->matiere</a><br />";
				$texte_checkbox_matieres.="<a href='#' onclick=\"document.getElementById('matiere_a_ajouter').value='$lig->matiere';cacher_div('ajout_matiere');document.form_choix_matieres.submit()\">$lig->matiere</a><br />";
				$cpt++;
			}
		}
		//$tabdiv_infobulle[]=creer_div_infobulle('ajout_lv1',$titre,"",$texte,"",35,0,'y','y','n','n');
		//$tabdiv_infobulle[]=creer_div_infobulle('ajout_matiere',$titre,"",$texte_checkbox_matieres,"",20,20,'y','y','n','y');
		echo creer_div_infobulle('ajout_matiere',$titre,"",$texte_checkbox_matieres,"",20,20,'y','y','n','y');
		//==================================================

		//echo "<p>Le fichier d'export Notanet doit-il avoir des fins de lignes Unix ou Dos?<br /><input type='radio' name='finsdelignes' value='dos' checked /> Fins de lignes DOS<br /><input type='radio' name='finsdelignes' value='unix' /> Fins de lignes UNIX</p>\n";

		echo "<input type='hidden' name='is_posted' value='y' />\n";
		echo "<input type='submit' name='choix_matieres' value='Enregistrer' />\n";
		echo "</form>\n";

		echo "<p><i>NOTES:</i></p>\n";
		echo "<ul>\n";
		echo "<li><p>La d�signation comme optionnelle de certaines mati�res ci-dessus ne correspond pas n�cessairement au caract�re optionnel d'une mati�re dans NOTANET, mais au fait que l'on ne consid�re pas comme une erreur le fait qu'un �l�ve n'ait pas de moyenne saisie dans cette mati�re (<i>qu'on ne trouve pas de moyenne dans la table 'matiere_notes'</i>).</p></li>\n";
		echo "<li><p>Certaines erreurs seront sans doute signal�es parce que certains �l�ves sont dispens�s, absents,... sur certaines mati�res.<br />Il sera alors possible de saisir les valeurs autoris�es DI, AB,... avant de g�n�rer un fichier CSV complet.</p></li>\n";
		//echo "<li><p></p></li>\n";
		echo "<li><p>Il est possible de s�lectionner plusieurs mati�res pour une option (<i>ex.: AGL1 et ALL1 pour la Langue vivante 1</i>) en utilisant CTRL+clic avec la souris.<br />
		(<i>on parle de s�lection multiple</i>)</p></li>\n";
		echo "<li><p>Dans le cas du 'SOCLE B2I', il n'est pas n�cessaire d'associer une mati�re.<br />L'affectation de la 'note' (<i>MS, ME, MN ou AB</i>) ne se fait pas par extraction des notes de l'ann�e.</p>
		<p>Pour le 'SOCLE NIVEAU A2 DE LANGUE', les mati�res ne sont pas exploit�es pour le filtrage... seul le statut 'imposee' ou 'optionnelle' selon le type de brevet est utilis�.</p></li>\n";
		echo "<li><p>Dans certains �tablissements, la mati�re Education Civique est consid�r�e comme une sous-mati�re de Histoire-g�ographie et EDCIV ne fait alors pas l'objet d'une moyenne s�par�e de HIGEO.<br />Dans ce cas, il convient d'associer les deux mati�res notanet Histoire-G�o et Education civique � HIGEO.<br />Dans le cas contraire, l'export CSV sera refus� par l'application Notanet acad�mique.</p></li>\n";

		if(($type_brevet==2)||($type_brevet==3)||($type_brevet==4)||($type_brevet==5)||($type_brevet==6)) {
				echo "<li><p>Dans certains �tablissements, on enseigne la LV1, mais pas les SCPHY pour les brevets PRO.<br />
				Pourtant, l'application acad�mique Notanet n'accepte pas que la mati�re 104 soit alors d�clar�e comme Non dispens�e et donc n'apparaisse pas dans le fichier CSV g�n�r� par Gepi.<br />
				Dans ce cas, il conviendra d'associer la m�me mati�re Gepi pour les deux mati�res Notanet LV1 (103) et SCPHY (104).<br />
				De cette fa�on le fichier CSV g�n�r� sera conforme � ce qui est attendu par l'application Notanet acad�mique.</p></li>\n";
		}
		echo "</ul>\n";

		if($type_brevet==2){
			echo "<p><b>ATTENTION:</b></p>\n";
			echo "<blockquote>\n";
			echo "<p>Pour le Brevet de s�rie PROFESSIONNELLE, sans option de s�rie, il faut cocher 'optionnelle' la LV1 et les Sciences-Physiques, puisque chaque �l�ve n'a de notes que dans l'une ou l'autre.<br />Ne pas cocher cette case conduirait � consid�rer qu'il manque une moyenne qui en LV1, qui en Sciences-Physiques pour chaque �l�ve et une erreur serait affich�e sans production des lignes de l'export NOTANET.</p>\n";
			echo "<p>L'inconv�nient: si un �l�ve n'a de moyenne ni en LV1, ni en Sciences-physiques, cela ne sera pas signal� comme une erreur alors que cela devrait l'�tre...<br />En attendant une �ventuelle am�lioration du dispositif, il convient de contr�ler manuellement (de visu) de tels manques.</p>\n";
			echo "<p><br /></p>\n";
			echo "<p><b>GROS DOUTE:</b> Est-ce qu'un �l�ve peut suivre les deux (LV1 et Sc-Phy) et choisir la mati�re � retenir pour le Brevet?<br />Si oui, je n'ai pas g�r� ce cas... il faut corriger (vider) la mati�re non souhait�e pour chaque �l�ve dans le prochain formulaire.</p>\n";
			echo "</blockquote>\n";
		}
	/*
	}
	else {
		echo "</div>\n";

		// Nettoyage des choix de mati�res dans 'notanet_corresp'
		$sql="DELETE FROM notanet_corresp WHERE type_brevet='$type_brevet';";
		$res_nettoyage=mysql_query($sql);
		if(!$res_nettoyage){
			echo "<p><b style='color:red;'>ERREUR</b> lors du nettoyage de la table 'notanet_corresp'.</p>\n";
		}

		// Enregistrement des choix de mati�res dans 'notanet_corresp'
		for($j=$indice_premiere_matiere;$j<=$indice_max_matieres;$j++){
			if($tabmatieres[$j][0]!=''){
				//$tabmatieres[$j][0]

				//if(count($id_matiere[$j])>0){
				if(isset($id_matiere[$j])){
					for($i=0;$i<count($id_matiere[$j]);$i++){
						$sql="INSERT INTO notanet_corresp SET notanet_mat='".$tabmatieres[$j][0]."',
																matiere='".$id_matiere[$j][$i]."',
																statut='".$statut_matiere[$j]."',
																type_brevet='$type_brevet';";
						$res_insert=mysql_query($sql);
					}
				}
				else{
					// Cas de mati�res non dispens�es...
					$sql="INSERT INTO notanet_corresp SET notanet_mat='".$tabmatieres[$j][0]."',
															matiere='',
															statut='".$statut_matiere[$j]."',
															type_brevet='$type_brevet';";
					$res_insert=mysql_query($sql);
				}
			}
		}
	}
	*/
}

require("../lib/footer.inc.php");
?>
