<?php
	@set_time_limit(0);

	// $Id$

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
	$titre_page = "Outil d'initialisation de l'ann�e : Importation des mati�res";
	require_once("../lib/header.inc");
	//**************** FIN EN-TETE *****************

	require_once("init_xml_lib.php");

	function extr_valeur($lig){
		unset($tabtmp);
		$tabtmp=explode(">",my_ereg_replace("<",">",$lig));
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
		}
	}

		// Etape...
	$step=isset($_POST['step']) ? $_POST['step'] : (isset($_GET['step']) ? $_GET['step'] : NULL);

	$verif_tables_non_vides=isset($_POST['verif_tables_non_vides']) ? $_POST['verif_tables_non_vides'] : NULL;

	if(isset($_GET['ad_retour'])){
		$_SESSION['ad_retour']=$_GET['ad_retour'];
	}
	//echo "\$_SESSION['ad_retour']=".$_SESSION['ad_retour']."<br />";

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
"groupes",
//"j_aid_eleves",
//"j_aid_utilisateurs",
//"j_eleves_classes",
//"j_eleves_etablissements",
"j_eleves_groupes",
"j_groupes_matieres",
"j_groupes_professeurs",
"j_groupes_classes",
"j_signalement",
"eleves_groupes_settings",
//"j_eleves_professeurs",
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
//"observatoire_j_resp_champ",
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
"ct_devoirs_entry",
"ct_documents",
"ct_entry",
"ct_devoirs_documents",
"ct_private_entry",
"ct_sequences",
//"setting"
"edt_classes",
"edt_cours"
);




	// On va uploader les fichiers XML dans le tempdir de l'utilisateur (administrateur, ou scolarit� pour les m�j Sconet)
	$tempdir=get_user_temp_directory();
	if(!$tempdir){
		echo "<p style='color:red'>Il semble que le dossier temporaire de l'utilisateur ".$_SESSION['login']." ne soit pas d�fini!?</p>\n";
		// Il ne faut pas aller plus loin...
		// SITUATION A GERER
	}


	// =======================================================
	// EST-CE ENCORE UTILE?
	if(isset($_GET['nettoyage'])){
		check_token(false);

		//echo "<h1 align='center'>Suppression des CSV</h1>\n";
		echo "<h2>Suppression des XML</h2>\n";
		echo "<p class=bold><a href='";
		if(isset($_SESSION['ad_retour'])){
			echo $_SESSION['ad_retour'];
		}
		else{
			echo "index.php";
		}
		echo "'> <img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>";
		echo "<a href='".$_SERVER['PHP_SELF']."'> | Autre import</a></p>\n";
		//echo "</div>\n";

		echo "<p>Si des fichiers XML existent, ils seront supprim�s...</p>\n";
		//$tabfich=array("f_ele.csv","f_ere.csv");
		$tabfich=array("sts.xml","nomenclature.xml");

		for($i=0;$i<count($tabfich);$i++){
			if(file_exists("../temp/".$tempdir."/$tabfich[$i]")) {
				echo "<p>Suppression de $tabfich[$i]... ";
				if(unlink("../temp/".$tempdir."/$tabfich[$i]")){
					echo "r�ussie.</p>\n";
				}
				else{
					echo "<font color='red'>Echec!</font> V�rifiez les droits d'�criture sur le serveur.</p>\n";
				}
			}
		}

		require("../lib/footer.inc.php");
		die();
	}
	// =======================================================
	else{
		echo "<center><h3 class='gepi'>Importation des mati�res</h3></center>\n";
		//echo "<h2>Pr�paration des donn�es �l�ves/classes/p�riodes/options</h2>\n";
		echo "<p class=bold><a href='";
		if(isset($_SESSION['ad_retour'])){
			// On peut venir de l'index init_xml, de la page de conversion ou de la page de mise � jour Sconet
			echo $_SESSION['ad_retour'];
		}
		else{
			echo "index.php";
		}
		echo "'><img src='../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>";

		//echo " | <a href='".$_SERVER['PHP_SELF']."'>Autre import</a>";
		echo " | <a href='".$_SERVER['PHP_SELF']."?nettoyage=oui".add_token_in_url()."'>Suppression des fichiers XML existants</a>";
		echo "</p>\n";
		//echo "</div>\n";

		//if(!isset($_POST['is_posted'])){
		if(!isset($step)){

			if(!isset($verif_tables_non_vides)) {
				$j=0;
				$flag=0;
				$chaine_tables="";
				while (($j < count($liste_tables_del)) and ($flag==0)) {
					if (mysql_result(mysql_query("SELECT count(*) FROM $liste_tables_del[$j]"),0)!=0) {
						$flag=1;
					}
					$j++;
				}
				for($loop=0;$loop<count($liste_tables_del);$loop++) {
					if($chaine_tables!="") {$chaine_tables.=", ";}
					$chaine_tables.="'".$liste_tables_del[$loop]."'";
				}

				if ($flag != 0){
					echo "<p><b>ATTENTION ...</b><br />\n";
					echo "Des donn�es concernant les mati�res sont actuellement pr�sentes dans la base GEPI<br /></p>\n";
					echo "<p>Si vous poursuivez la proc�dure les donn�es telles que notes, appr�ciations, ... seront effac�es.</p>\n";
					echo "<p>Seules la table contenant les mati�res et la table mettant en relation les mati�res et les professeurs seront conserv�es.</p>\n";

					echo "<p>Les tables vid�es seront&nbsp;: $chaine_tables</p>\n";

					echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post'>\n";
					echo add_token_field();
					echo "<input type=hidden name='verif_tables_non_vides' value='y' />\n";
					echo "<input type='submit' name='confirm' value='Poursuivre la proc�dure' />\n";
					echo "</form>\n";
					echo "</div>\n";
					echo "</body>\n";
					echo "</html>\n";
					die();
				}
			}


			if(isset($verif_tables_non_vides)) {
				check_token(false);

				$j=0;
				while ($j < count($liste_tables_del)) {
					if (mysql_result(mysql_query("SELECT count(*) FROM $liste_tables_del[$j]"),0)!=0) {
						$del = @mysql_query("DELETE FROM $liste_tables_del[$j]");
					}
					$j++;
				}
			}

			echo "<p><b>ATTENTION ...</b><br />Vous ne devez proc�der � cette op�ration que si la constitution des classes a �t� effectu�e !</p>\n";

			echo "<p>Cette page permet d'uploader un fichier qui servira � remplir les tables de GEPI avec les informations professeurs, mati�res,...</p>\n";

			echo "<p>Il faut lui fournir un Export XML r�alis� depuis l'application STS-web.<br />Demandez gentiment � votre secr�taire d'acc�der � STS-web et d'effectuer 'Mise � jour/Exports/Emplois du temps'.</p>\n";

			echo "<form enctype='multipart/form-data' action='".$_SERVER['PHP_SELF']."' method='post'>\n";
			echo add_token_field();
			echo "<p>Veuillez fournir le fichier XML <b>sts_emp_<i>RNE</i>_<i>ANNEE</i>.xml</b>&nbsp;: \n";
			echo "<p><input type=\"file\" size=\"65\" name=\"xml_file\" />\n";
			echo "<p><input type=\"hidden\" name=\"step\" value=\"0\" />\n";
			echo "<input type='hidden' name='is_posted' value='yes' />\n";
			echo "</p>\n";


			echo "<input type='hidden' name='is_posted' value='yes' />\n";

			echo "<p><input type='submit' value='Valider' /></p>\n";
			echo "</form>\n";
		}
		else{
			check_token(false);

			$post_max_size=ini_get('post_max_size');
			$upload_max_filesize=ini_get('upload_max_filesize');
			$max_execution_time=ini_get('max_execution_time');
			$memory_limit=ini_get('memory_limit');

			if($step==0){
				$xml_file=isset($_FILES["xml_file"]) ? $_FILES["xml_file"] : NULL;

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
					$dest_file="../temp/".$tempdir."/sts.xml";
					$res_copy=copy("$source_file" , "$dest_file");

					if(!$res_copy){
						echo "<p style='color:red;'>La copie du fichier vers le dossier temporaire a �chou�.<br />V�rifiez que l'utilisateur ou le groupe apache ou www-data a acc�s au dossier temp/$tempdir</p>\n";
						// Il ne faut pas aller plus loin...
						// SITUATION A GERER
						require("../lib/footer.inc.php");
						die();
					}
					else{
						echo "<p>La copie du fichier vers le dossier temporaire a r�ussi.</p>\n";

						// Table destin�e � stocker l'association code/code_gestion utilis�e dans d'autres parties de l'initialisation
						$sql="CREATE TABLE IF NOT EXISTS temp_matieres_import (
								code varchar(40) NOT NULL default '',
								code_gestion varchar(40) NOT NULL default '',
								libelle_court varchar(40) NOT NULL default '',
								libelle_long varchar(255) NOT NULL default '',
								libelle_edition varchar(255) NOT NULL default ''
								);";
						$create_table = mysql_query($sql);

						$sql="TRUNCATE TABLE temp_matieres_import;";
						$vide_table = mysql_query($sql);


						/*
						// On va lire plusieurs fois le fichier pour remplir des tables temporaires.
						$fp=fopen($dest_file,"r");
						if($fp){
							echo "<p>Lecture du fichier STS Emploi du temps...<br />\n";
							//echo "<blockquote>\n";
							while(!feof($fp)){
								$ligne[]=fgets($fp,4096);
							}
							fclose($fp);
							//echo "<p>Termin�.</p>\n";
						}
						*/
						flush();

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

						// On commence par la section MATIERES.
						echo "Analyse du fichier pour extraire les informations de la section MATIERES...<br />\n";

						$tab_champs_matiere=array("CODE_GESTION",
						"LIBELLE_COURT",
						"LIBELLE_LONG",
						"LIBELLE_EDITION");

						$matiere=array();
						// Compteur matieres:
						$i=0;
				
						foreach($sts_xml->NOMENCLATURES->MATIERES->children() as $objet_matiere) {
				
							foreach($objet_matiere->attributes() as $key => $value) {
								// <MATIERE CODE="090100">
								$matiere[$i][strtolower($key)]=trim(traite_utf8($value));
							}
				
							// Champs de la mati�re
							foreach($objet_matiere->children() as $key => $value) {
								if(in_array(strtoupper($key),$tab_champs_matiere)) {
									if(strtoupper($key)=='CODE_GESTION') {
										$matiere[$i][strtolower($key)]=trim(preg_replace("/[^a-zA-Z0-9&_. -]/","",html_entity_decode_all_version(traite_utf8($value))));
									}
									elseif(strtoupper($key)=='LIBELLE_COURT') {
										$matiere[$i][strtolower($key)]=trim(preg_replace("/[^A-Za-z�漽".$liste_caracteres_accentues."0-9&_. -]/","",html_entity_decode_all_version(traite_utf8($value))));
									}
									else {
										$matiere[$i][strtolower($key)]=traitement_magic_quotes(corriger_caracteres(trim(preg_replace('/"/','',traite_utf8($value)))));
									}
								}
							}

							if($debug_import=='y') {
								echo "<pre style='color:green;'><b>Tableau \$adresses[$i]&nbsp;:</b>";
								print_r($adresses[$i]);
								echo "</pre>";
							}
				
							$i++;
						}

						$i=0;
						$nb_err=0;
						$stat=0;
						while($i<count($matiere)){
							//$sql="INSERT INTO temp_resp_pers_import SET ";
							$sql="INSERT INTO temp_matieres_import SET ";
							$sql.="code='".$matiere[$i]["code"]."', ";
							$sql.="code_gestion='".$matiere[$i]["code_gestion"]."', ";
							$sql.="libelle_court='".$matiere[$i]["libelle_court"]."', ";
							$sql.="libelle_long='".$matiere[$i]["libelle_long"]."', ";
							$sql.="libelle_edition='".$matiere[$i]["libelle_edition"]."';";
							affiche_debug("$sql<br />\n");
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



						echo "<p>Dans le tableau ci-dessous, les identifiants en rouge correspondent � des nouvelles mati�res dans la base GEPI. les identifiants en vert correspondent � des identifiants de mati�res d�tect�s dans le fichier GEP mais d�j� pr�sents dans la base GEPI.<br /><br />Il est possible que certaines mati�res ci-dessous, bien que figurant dans le fichier CSV, ne soient pas utilis�es dans votre �tablissement cette ann�e. C'est pourquoi il vous sera propos� en fin de proc�dure d'initialsation, un nettoyage de la base afin de supprimer ces donn�es inutiles.</p>\n";

						echo "<table border='1' class='boireaus' cellpadding='2' cellspacing='2' summary='Tableau des mati�res'>\n";

						echo "<tr><th><p class=\"small\">Identifiant de la mati�re</p></th><th><p class=\"small\">Nom complet</p></th></tr>\n";

						$i=0;
						//$nb_err=0;
						$nb_reg_no=0;
						//$stat=0;

						$alt=1;
						while($i<count($matiere)){
							$sql="select matiere, nom_complet from matieres where matiere='".$matiere[$i]['code_gestion']."';";
							$verif=mysql_query($sql);
							$resverif = mysql_num_rows($verif);
							if($resverif==0) {
								$sql="insert into matieres set matiere='".$matiere[$i]['code_gestion']."', nom_complet='".$matiere[$i]['libelle_court']."', priority='0',matiere_aid='n',matiere_atelier='n';";
								$req=mysql_query($sql);
								if(!$req) {
									$nb_reg_no++;
									echo mysql_error();
								}
								else {
									$alt=$alt*(-1);
									echo "<tr class='lig$alt'>\n";
									echo "<td><p><font color='red'>".$matiere[$i]['code_gestion']."</font></p></td><td><p>".htmlentities($matiere[$i]['libelle_court'])."</p></td></tr>\n";
								}
							} else {
								$nom_complet = mysql_result($verif,0,'nom_complet');
								$alt=$alt*(-1);
								echo "<tr class='lig$alt'>\n";
								echo "<td><p><font color='green'>".$matiere[$i]['code_gestion']."</font></p></td><td><p>".htmlentities($nom_complet)."</p></td></tr>\n";
							}

							$i++;
						}

						echo "</table>\n";

						if ($nb_reg_no != 0) {
							echo "<p>Lors de l'enregistrement des donn�es il y a eu $nb_reg_no erreurs. Essayez de trouvez la cause de l'erreur et recommencez la proc�dure avant de passer � l'�tape suivante.";
						} else {
							echo "<p>L'importation des mati�res dans la base GEPI a �t� effectu�e avec succ�s !<br />Vous pouvez proc�der � la quatri�me phase d'importation des professeurs.</p>";
						}

						//echo "<center><p><a href='prof_csv.php'>Importation des professeurs</a></p></center>";
						//echo "<p align='center'><a href='".$_SERVER['PHP_SELF']."?step=1'>Importation des professeurs</a></p>\n";
						echo "<p align='center'><a href='professeurs.php'>Importation des professeurs</a></p>\n";
						echo "<p><br /></p>\n";

						require("../lib/footer.inc.php");
						die();
					}
				}
			}
		}
	}
	require("../lib/footer.inc.php");
?>