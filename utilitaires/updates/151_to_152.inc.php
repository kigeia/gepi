<?php

		$result .= "<br /><br /><b>Mise � jour vers la version 1.5.2" . $rc . $beta . " :</b><br />";

		$req_test=mysql_query("SELECT value FROM setting WHERE name = 'sso_display_portail'");
		$res_test=mysql_num_rows($req_test);
		if ($res_test==0){
			$result_inter = traite_requete("INSERT INTO setting VALUES ('sso_display_portail','no');");
			if ($result_inter == '') {
				$result.="<font color=\"green\">D�finition du param�tre sso_display_portail � 'no': Ok !</font><br />";
			} else {
				$result.="<font color=\"red\">D�finition du param�tre sso_display_portail � 'no': Erreur !</font><br />";
			}
		} else {
			$result .= "<font color=\"blue\">Le param�tre sso_use_portail existe d�j� dans la table setting.</font><br />";
		}

		$req_test=mysql_query("SELECT value FROM setting WHERE name = 'sso_url_portail'");
		$res_test=mysql_num_rows($req_test);
		if ($res_test==0){
			$result_inter = traite_requete("INSERT INTO setting VALUES ('sso_url_portail', 'https://www.example.com');");
			if ($result_inter == '') {
				$result.="<font color=\"green\">D�finition du param�tre sso_url_portail � 'https://www.example.com': Ok !</font><br />";
			} else {
				$result.="<font color=\"red\">D�finition du param�tre sso_url_portail � 'https://www.example.com': Erreur !</font><br />";
			}
		} else {
			$result .= "<font color=\"blue\">Le param�tre denomination_eleves existe d�j� dans la table setting.</font><br />";
		}

		$req_test=mysql_query("SELECT value FROM setting WHERE name = 'sso_hide_logout'");
		$res_test=mysql_num_rows($req_test);
		if ($res_test==0){
			$result_inter = traite_requete("INSERT INTO setting VALUES ('sso_hide_logout', 'no');");
			if ($result_inter == '') {
				$result.="<font color=\"green\">D�finition du param�tre sso_hide_logout � 'no': Ok !</font><br />";
			} else {
				$result.="<font color=\"red\">D�finition du param�tre sso_hide_logout � 'no': Erreur !</font><br />";
			}
		} else {
			$result .= "<font color=\"blue\">Le param�tre sso_hide_logout existe d�j� dans la table setting.</font><br />";
		}

		// Module discipline
		$test = sql_query1("SHOW TABLES LIKE 's_incidents'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 's_incidents'. ";
			$sql="CREATE TABLE IF NOT EXISTS s_incidents (
id_incident INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
declarant VARCHAR( 50 ) NOT NULL ,
date DATE NOT NULL ,
heure VARCHAR( 20 ) NOT NULL ,
id_lieu INT( 11 ) NOT NULL ,
nature VARCHAR( 255 ) NOT NULL ,
description TEXT NOT NULL,
etat VARCHAR( 20 ) NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 's_incidents': ".$result_inter."<br />";
			}
		}
		// Avec cette table on ne g�re pas un historique des modifications de d�claration...


		$test = sql_query1("SHOW TABLES LIKE 's_qualites'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 's_qualites'. ";
			$sql="CREATE TABLE IF NOT EXISTS s_qualites (
id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
qualite VARCHAR( 50 ) NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 's_qualites': ".$result_inter."<br />";
			}
			else {
				$tab_qualite=array("Responsable","Victime","T�moin","Autre");
				for($loop=0;$loop<count($tab_qualite);$loop++) {
					$sql="SELECT 1=1 FROM s_qualites WHERE qualite='".$tab_qualite[$loop]."';";
					//echo "$sql<br />";
					$test=mysql_query($sql);
					if(mysql_num_rows($test)==0) {
						$sql="INSERT INTO s_qualites SET qualite='".$tab_qualite[$loop]."';";
						$insert=mysql_query($sql);
					}
				}
			}
		}

		$test = sql_query1("SHOW TABLES LIKE 's_types_sanctions'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 's_types_sanctions'. ";
			$sql="CREATE TABLE IF NOT EXISTS s_types_sanctions (
id_nature INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
nature VARCHAR( 255 ) NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 's_types_sanctions': ".$result_inter."<br />";
			}
			else {
				$tab_type=array("Avertissement travail","Avertissement comportement");
				for($loop=0;$loop<count($tab_type);$loop++) {
					$sql="SELECT 1=1 FROM s_types_sanctions WHERE nature='".$tab_type[$loop]."';";
					//echo "$sql<br />";
					$test=mysql_query($sql);
					if(mysql_num_rows($test)==0) {
						$sql="INSERT INTO s_types_sanctions SET nature='".$tab_type[$loop]."';";
						$insert=mysql_query($sql);
					}
				}
			}
		}

		$test = sql_query1("SHOW TABLES LIKE 's_autres_sanctions'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 's_autres_sanctions'. ";
			$sql="CREATE TABLE IF NOT EXISTS s_autres_sanctions (
id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
id_sanction INT( 11 ) NOT NULL ,
id_nature INT( 11 ) NOT NULL ,
description TEXT NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 's_autres_sanctions': ".$result_inter."<br />";
			}
		}

		$test = sql_query1("SHOW TABLES LIKE 's_mesures'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 's_mesures'. ";
			$sql="CREATE TABLE IF NOT EXISTS s_mesures (
id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
type ENUM('prise','demandee') ,
mesure VARCHAR( 50 ) NOT NULL ,
commentaire TEXT NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 's_mesures': ".$result_inter."<br />";
			}
			else {
				// Mesures prises
				$tab_mesure=array("Travail suppl�mentaire","Mot dans le carnet de liaison");
				for($loop=0;$loop<count($tab_mesure);$loop++) {
					$sql="SELECT 1=1 FROM s_mesures WHERE mesure='".$tab_mesure[$loop]."';";
					//echo "$sql<br />";
					$test=mysql_query($sql);
					if(mysql_num_rows($test)==0) {
						$sql="INSERT INTO s_mesures SET mesure='".$tab_mesure[$loop]."', type='prise';";
						$insert=mysql_query($sql);
					}
				}

				// Mesures demand�es
				$tab_mesure=array("Retenue","Exclusion");
				for($loop=0;$loop<count($tab_mesure);$loop++) {
					$sql="SELECT 1=1 FROM s_mesures WHERE mesure='".$tab_mesure[$loop]."';";
					//echo "$sql<br />";
					$test=mysql_query($sql);
					if(mysql_num_rows($test)==0) {
						$sql="INSERT INTO s_mesures SET mesure='".$tab_mesure[$loop]."', type='demandee';";
						$insert=mysql_query($sql);
					}
				}
			}
		}

		$test = sql_query1("SHOW TABLES LIKE 's_traitement_incident'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 's_traitement_incident'. ";
			$sql="CREATE TABLE IF NOT EXISTS s_traitement_incident (
id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
id_incident INT( 11 ) NOT NULL ,
login_ele VARCHAR( 50 ) NOT NULL ,
login_u VARCHAR( 50 ) NOT NULL ,
id_mesure INT( 11 ) NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 's_traitement_incident': ".$result_inter."<br />";
			}
		}

		$test = sql_query1("SHOW TABLES LIKE 's_lieux_incidents'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 's_lieux_incidents'. ";
			$sql="CREATE TABLE IF NOT EXISTS s_lieux_incidents (
id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
lieu VARCHAR( 255 ) NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 's_lieux_incidents': ".$result_inter."<br />";
			}
			else {
				$tab_lieu=array("Classe","Couloir","Cour","R�fectoire","Autre");
				for($loop=0;$loop<count($tab_lieu);$loop++) {
					$sql="SELECT 1=1 FROM s_lieux_incidents WHERE lieu='".$tab_lieu[$loop]."';";
					//echo "$sql<br />";
					$test=mysql_query($sql);
					if(mysql_num_rows($test)==0) {
						$sql="INSERT INTO s_lieux_incidents SET lieu='".$tab_lieu[$loop]."';";
						$insert=mysql_query($sql);
					}
				}
			}
		}

		$test = sql_query1("SHOW TABLES LIKE 's_protagonistes'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 's_incidents'. ";
			$sql="CREATE TABLE IF NOT EXISTS s_protagonistes (
id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
id_incident INT NOT NULL ,
login VARCHAR( 50 ) NOT NULL ,
statut VARCHAR( 50 ) NOT NULL ,
qualite VARCHAR( 50 ) NOT NULL,
avertie ENUM('N','O') NOT NULL DEFAULT 'N'
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 's_protagonistes': ".$result_inter."<br />";
			}
		}

		$test = sql_query1("SHOW TABLES LIKE 's_sanctions'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 's_sanctions'. ";
			$sql="CREATE TABLE IF NOT EXISTS s_sanctions (
id_sanction INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
login VARCHAR( 50 ) NOT NULL ,
description TEXT NOT NULL ,
nature VARCHAR( 255 ) NOT NULL ,
effectuee ENUM( 'N', 'O' ) NOT NULL ,
id_incident INT( 11 ) NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 's_sanctions': ".$result_inter."<br />";
			}
		}

		$test = sql_query1("SHOW TABLES LIKE 's_communication'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 's_communication'. ";
			$sql="CREATE TABLE IF NOT EXISTS s_communication (
id_communication INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
id_incident INT( 11 ) NOT NULL ,
login VARCHAR( 50 ) NOT NULL ,
nature VARCHAR( 255 ) NOT NULL ,
description TEXT NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 's_communication': ".$result_inter."<br />";
			}
		}

		$test = sql_query1("SHOW TABLES LIKE 's_travail'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 's_travail'. ";
			$sql="CREATE TABLE IF NOT EXISTS s_travail (
id_travail INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
id_sanction INT( 11 ) NOT NULL ,
date_retour DATE NOT NULL ,
heure_retour VARCHAR( 20 ) NOT NULL ,
travail TEXT NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 's_travail': ".$result_inter."<br />";
			}
		}

		$test = sql_query1("SHOW TABLES LIKE 's_retenues'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 's_retenues'. ";
			$sql="CREATE TABLE IF NOT EXISTS s_retenues (
id_retenue INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
id_sanction INT( 11 ) NOT NULL ,
date DATE NOT NULL ,
heure_debut VARCHAR( 20 ) NOT NULL ,
duree FLOAT NOT NULL ,
travail TEXT NOT NULL ,
lieu VARCHAR( 255 ) NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 's_retenues': ".$result_inter."<br />";
			}
		}

		$test = sql_query1("SHOW TABLES LIKE 's_exclusions'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 's_exclusions'. ";
			$sql="CREATE TABLE IF NOT EXISTS s_exclusions (
id_exclusion INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
id_sanction INT( 11 ) NOT NULL ,
date_debut DATE NOT NULL ,
heure_debut VARCHAR( 20 ) NOT NULL ,
date_fin DATE NOT NULL ,
heure_fin VARCHAR( 20 ) NOT NULL,
travail TEXT NOT NULL ,
lieu VARCHAR( 255 ) NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 's_exclusions': ".$result_inter."<br />";
			}
		}

		// Fin du module discipline

		//module carnet de note
		$result .= "<br />Modification de la table 'cn_devoirs'. ";
		$testcn_devoirs_note_sur = mysql_num_rows(mysql_query("SHOW COLUMNS FROM cn_devoirs LIKE 'note_sur'"));
		if ($testcn_devoirs_note_sur == 0) {
			$query = mysql_query("ALTER TABLE `cn_devoirs` ADD `note_sur` INT(11) DEFAULT '20' AFTER `coef` ;");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font>";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		} else {
			$result .= "<br /><font color=\"blue\">La colonne 'note_sur' existe deja.</font><br />";
		}

		$testcn_devoirs_ramener_sur_referentiel = mysql_num_rows(mysql_query("SHOW COLUMNS FROM cn_devoirs LIKE 'ramener_sur_referentiel'"));
		if ($testcn_devoirs_ramener_sur_referentiel == 0) {
			$result_inter = traite_requete("ALTER TABLE `cn_devoirs` ADD `ramener_sur_referentiel` CHAR(1) NOT NULL DEFAULT 'F' AFTER `note_sur` ;");
			if ($query) {
				$result .= "<font color=\"green\"> Ok !</font><br />";
			} else {
				$result .= "<br />Erreur sur la modification de la table 'cn_devoirs': ".$result_inter."<br />";
			}
		} else {
			$result .= "<font color=\"blue\">La colonne 'ramener_sur_referentiel' existe deja.</font><br />";
		}

		$req_test = mysql_query("SELECT VALUE FROM setting WHERE NAME = 'note_autre_que_sur_referentiel'");
		$res_test = mysql_num_rows($req_test);
		if ($res_test == 0){
			$result_inter .= traite_requete("INSERT INTO setting VALUES ('note_autre_que_sur_referentiel', 'F');");
		}

		$req_test = mysql_query("SELECT VALUE FROM setting WHERE NAME = 'referentiel_note'");
		$res_test = mysql_num_rows($req_test);
		if ($res_test == 0){
			$result_inter .= traite_requete("INSERT INTO setting VALUES ('referentiel_note', '20');");
		}


		//fin module carnet de note

		$sql="SELECT 1=1 FROM setting WHERE name='unzipped_max_filesize';";
		$query = mysql_query($sql);
		if (mysql_num_rows($query)==0) {
			$result .= "<br />Initialisation de la taille maximale d'un fichier extrait d'une archive ZIP&nbsp;: ";
			$sql="INSERT INTO setting SET name='unzipped_max_filesize',value='10';";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result.="<font color=\"red\">Erreur</font><br />";
			}
			else {
				$result.="<font color=\"green\">OK</font><br />";
			}
		}

		// Module ann�e ant�rieure
		$result .= "<br />Mise � jour de la table archivage_types_aid.<br />";
		$result_inter = traite_requete("ALTER TABLE archivage_types_aid ADD outils_complementaires ENUM( 'y', 'n' ) NOT NULL DEFAULT 'n' AFTER display_bulletin");
		if ($result_inter == '')
		$result .= "<font color=\"green\">Le champ outils_complementaires de la table archivage_types_aid a �t� ajout� !</font><br />";
		else
		$result .= $result_inter."<br />";


		$result .= "&nbsp;->Cr�ation de la absences_repas<br />";
		$test = mysql_num_rows(mysql_query("SHOW TABLES LIKE 'absences_repas'"));
		if ($test == 0) {
			$query3 = mysql_query("CREATE TABLE `absences_repas` (`id` int(5) NOT NULL auto_increment, `date_repas` date NOT NULL default '0000-00-00', `id_groupe` varchar(8) NOT NULL, `eleve_id` varchar(30) NOT NULL, `pers_id` varchar(30) NOT NULL, PRIMARY KEY  (`id`));");
			if ($query3) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		} else {
			$result .= "<font color=\"blue\">La table absences_repas existe d�j�</font><br />";
		}

		//module cahier de texte 2
		$test = sql_query1("SHOW TABLES LIKE 'ct_devoirs_documents'");
		if ($test == -1) {
			$sql="CREATE TABLE ct_devoirs_documents
		(
		id INTEGER  NOT NULL AUTO_INCREMENT COMMENT 'Id document',
		id_ct_devoir INTEGER default 0 NOT NULL COMMENT 'Id devoir du cahier de texte',
		titre VARCHAR(255)  NOT NULL COMMENT 'titre du document',
		taille INTEGER default 0 NOT NULL COMMENT 'Taille du document',
		emplacement VARCHAR(255)  NOT NULL COMMENT 'chemin vers le document',
		PRIMARY KEY (id),
		INDEX ct_devoirs_documents_FI_1 (id_ct_devoir)
		);";
		}
		$result_inter = traite_requete($sql);
		if ($result_inter != '') {
			$result .= "<br />Erreur sur la cr�ation de la table 'ct_devoirs_documents': ".$result_inter."<br />";
		}

		$req_test = mysql_query("SELECT VALUE FROM setting WHERE NAME = 'GepiCahierTexteVersion'");
		$res_test = mysql_num_rows($req_test);
		if ($res_test == 0) {
			$result_inter .= traite_requete("INSERT INTO setting VALUES ('GepiCahierTexteVersion', '1');");
		}

		//ajout de la possibilit� null sur certaines colonnes
		$result_inter .= traite_requete("ALTER TABLE ct_entry MODIFY id_login varchar(32);");
		$result_inter .= traite_requete("ALTER TABLE ct_devoirs_entry MODIFY id_login varchar(32);");

		$test = sql_query1("SHOW TABLES LIKE 'ct_private_entry'");
		if ($test == -1) {
			$sql="CREATE TABLE ct_private_entry
		(
		id_ct INTEGER  NOT NULL AUTO_INCREMENT COMMENT 'Cle primaire de la cotice privee',
		heure_entry TIME default '00:00:00' NOT NULL COMMENT 'heure de l\'entree',
		date_ct INTEGER default 0 NOT NULL COMMENT 'date du compte rendu',
		contenu TEXT  NOT NULL COMMENT 'contenu redactionnel du compte rendu',
		id_groupe INTEGER  NOT NULL COMMENT 'Cle etrangere du groupe auquel appartient le compte rendu',
		id_login VARCHAR(32)  COMMENT 'Cle etrangere de l\'utilisateur auquel appartient le compte rendu',
		PRIMARY KEY (id_ct),
		INDEX ct_private_entry_FI_1 (id_groupe),
		CONSTRAINT ct_private_entry_FK_1
		FOREIGN KEY (id_groupe)
		REFERENCES groupes (id)
		ON DELETE CASCADE,
		INDEX ct_private_entry_FI_2 (id_login),
		CONSTRAINT ct_private_entry_FK_2
		FOREIGN KEY (id_login)
		REFERENCES utilisateurs (login)
		ON DELETE CASCADE
		)ENGINE=MyISAM COMMENT='Notice privee du cahier de texte';";
		}
		$result_inter = traite_requete($sql);
		if ($result_inter != '') {
			$result .= "<br />Erreur sur la cr�ation de la table 'ct_private_entry': ".$result_inter."<br />";
		}

		//fin module cahier texte 2


		$result .= "&nbsp;->On autorise 40 caract�res pour le champ 'message' de la table 'aid_config'<br />";
		$sql = "ALTER TABLE aid_config CHANGE message message VARCHAR( 40 ) NOT NULL;";
		$result_inter = traite_requete($sql);
		if ($result_inter != '') {
			$result .= "<br />Erreur lors de l'augmentation � 40 caract�res du champ 'message' de la table 'aid_config': ".$result_inter."<br />";
		}

		$result .= "&nbsp;->Ajout d'un champ 'date_verrouillage' � la table 'periodes': ";
		$test1 = mysql_num_rows(mysql_query("SHOW COLUMNS FROM periodes LIKE 'date_verrouillage'"));
		if ($test1 == 0) {
			$query = mysql_query("ALTER TABLE periodes ADD date_verrouillage TIMESTAMP NOT NULL ;");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		}
		else {
			$result .= "<font color=\"blue\">Champ d�j� pr�sent</font><br />";
		}

		///Module OOO
		$req_test = mysql_query("SELECT VALUE FROM setting WHERE NAME = 'active_mod_ooo'");
		$res_test = mysql_num_rows($req_test);
		if ($res_test == 0) {
			$result_inter .= traite_requete("INSERT INTO setting VALUES ('active_mod_ooo', 'n');");
		}

		// Module ECTS
		$req_test = mysql_query("SELECT VALUE FROM setting WHERE NAME = 'active_mod_ects'");
		$res_test = mysql_num_rows($req_test);
		if ($res_test == 0) {
			$result_inter .= traite_requete("INSERT INTO setting VALUES ('active_mod_ects', 'n');");
		}

		$req_test = mysql_query("SELECT VALUE FROM setting WHERE NAME = 'GepiAccesSaisieEctsPP'");
		$res_test = mysql_num_rows($req_test);
		if ($res_test == 0) {
			$result_inter .= traite_requete("INSERT INTO setting VALUES ('GepiAccesSaisieEctsPP', 'no');");
		}

		$req_test = mysql_query("SELECT VALUE FROM setting WHERE NAME = 'GepiAccesSaisieEctsScolarite'");
		$res_test = mysql_num_rows($req_test);
		if ($res_test == 0) {
			$result_inter .= traite_requete("INSERT INTO setting VALUES ('GepiAccesSaisieEctsScolarite', 'yes');");
		}

		$req_test = mysql_query("SELECT VALUE FROM setting WHERE NAME = 'GepiAccesEditionDocsEctsPP'");
		$res_test = mysql_num_rows($req_test);
		if ($res_test == 0) {
			$result_inter .= traite_requete("INSERT INTO setting VALUES ('GepiAccesEditionDocsEctsPP', 'no');");
		}

		$req_test = mysql_query("SELECT VALUE FROM setting WHERE NAME = 'GepiAccesEditionDocsEctsScolarite'");
		$res_test = mysql_num_rows($req_test);
		if ($res_test == 0) {
			$result_inter .= traite_requete("INSERT INTO setting VALUES ('GepiAccesEditionDocsEctsScolarite', 'yes');");
		}

		$req_test = mysql_query("SELECT VALUE FROM setting WHERE NAME = 'gepiSchoolStatut'");
		$res_test = mysql_num_rows($req_test);
		if ($res_test == 0) {
			$result_inter .= traite_requete("INSERT INTO setting VALUES ('gepiSchoolStatut', 'public');");
		}

        $req_test = mysql_query("SELECT VALUE FROM setting WHERE NAME = 'gepiSchoolAcademie'");
		$res_test = mysql_num_rows($req_test);
		if ($res_test == 0) {
			$result_inter .= traite_requete("INSERT INTO setting VALUES ('gepiSchoolAcademie', '');");
		}

		$result .= "&nbsp;->Ajout d'un champ 'saisie_ects' � la table 'j_groupes_classes': ";
		$test1 = mysql_num_rows(mysql_query("SHOW COLUMNS FROM j_groupes_classes LIKE 'saisie_ects'"));
		if ($test1 == 0) {
			$query = mysql_query("ALTER TABLE j_groupes_classes ADD saisie_ects TINYINT(1) NOT NULL DEFAULT 0;");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		}
		else {
			$result .= "<font color=\"blue\">Champ d�j� pr�sent</font><br />";
		}

		$result .= "&nbsp;->Ajout d'un champ 'valeur_ects' � la table 'j_groupes_classes': ";
		$test1 = mysql_num_rows(mysql_query("SHOW COLUMNS FROM j_groupes_classes LIKE 'valeur_ects'"));
		if ($test1 == 0) {
			$query = mysql_query("ALTER TABLE j_groupes_classes ADD valeur_ects DECIMAL(3,1) NOT NULL;");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		}
		else {
			$result .= "<font color=\"blue\">Champ d�j� pr�sent</font><br />";
		}


        $result .= "&nbsp;->Cr�ation de la table ects_credits<br />";
		$test = mysql_num_rows(mysql_query("SHOW TABLES LIKE 'ects_credits'"));
		if ($test == 0) {
			$query2 = mysql_query("CREATE TABLE ects_credits
                                    (
                                        id INTEGER(11)  NOT NULL AUTO_INCREMENT,
                                        id_eleve INTEGER(11)  NOT NULL COMMENT 'Identifiant de l\'eleve',
                                        num_periode INTEGER(11)  NOT NULL COMMENT 'Identifiant de la periode',
                                        id_groupe INTEGER(11)  NOT NULL COMMENT 'Identifiant du groupe',
                                        valeur DECIMAL(3,1) NOT NULL COMMENT 'Nombre de crédits obtenus par l\'eleve',
                                        mention VARCHAR(255)  NOT NULL COMMENT 'Mention obtenue',
                                        PRIMARY KEY (id,id_eleve,num_periode,id_groupe),
                                        INDEX ects_credits_FI_1 (id_eleve),
                                        CONSTRAINT ects_credits_FK_1
                                            FOREIGN KEY (id_eleve)
                                            REFERENCES eleves (id_eleve),
                                        INDEX ects_credits_FI_2 (id_groupe),
                                        CONSTRAINT ects_credits_FK_2
                                            FOREIGN KEY (id_groupe)
                                            REFERENCES groupes (id)
                                    )");
			if ($query2) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		} else {
			$result .= "<font color=\"blue\">La table existe d�j�</font><br />";
		}

        $result .= "&nbsp;->Cr�ation de la table ects_global_credits<br />";
		$test = mysql_num_rows(mysql_query("SHOW TABLES LIKE 'ects_global_credits'"));
		if ($test == 0) {
			$query2 = mysql_query("CREATE TABLE ects_global_credits
                                    (
                                        id INTEGER(11)  NOT NULL AUTO_INCREMENT,
                                        id_eleve INTEGER(11)  NOT NULL COMMENT 'Identifiant de l\'eleve',
                                        mention VARCHAR(255)  NOT NULL COMMENT 'Mention obtenue',
                                        PRIMARY KEY (id,id_eleve),
                                        INDEX ects_global_credits_FI_1 (id_eleve),
                                        CONSTRAINT ects_global_credits_FK_1
                                            FOREIGN KEY (id_eleve)
                                            REFERENCES eleves (id_eleve)
                                    )");
			if ($query2) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		} else {
			$result .= "<font color=\"blue\">La table existe d�j�</font><br />";
		}



        $result .= "&nbsp;->Cr�ation de la table archivage_ects<br />";
		$test = mysql_num_rows(mysql_query("SHOW TABLES LIKE 'archivage_ects'"));
		if ($test == 0) {
			$query2 = mysql_query("CREATE TABLE archivage_ects
                                    (
                                        id INTEGER(11)  NOT NULL AUTO_INCREMENT,
                                        annee VARCHAR(255)  NOT NULL COMMENT 'Annee scolaire',
                                        ine VARCHAR(255)  NOT NULL COMMENT 'Identifiant de l\'eleve',
                                        classe VARCHAR(255)  NOT NULL COMMENT 'Classe de l\'eleve',
                                        num_periode INTEGER(11)  NOT NULL COMMENT 'Identifiant de la periode',
                                        nom_periode VARCHAR(255)  NOT NULL COMMENT 'Nom complet de la periode',
                                        special VARCHAR(255)  NOT NULL COMMENT 'Cle utilisee pour isoler certaines lignes (par exemple un credit ECTS pour une periode et non une matiere)',
                                        matiere VARCHAR(255) COMMENT 'Nom de l\'enseignement',
                                        profs VARCHAR(255) COMMENT 'Liste des profs de l\'enseignement',
                                        valeur DECIMAL  NOT NULL COMMENT 'Nombre de crédits obtenus par l\'eleve',
                                        mention VARCHAR(255)  NOT NULL COMMENT 'Mention obtenue',
                                        PRIMARY KEY (id,ine,num_periode,special),
                                        INDEX archivage_ects_FI_1 (ine),
                                        CONSTRAINT archivage_ects_FK_1
                                            FOREIGN KEY (ine)
                                            REFERENCES eleves (no_gep)
                                    )");
			if ($query2) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		} else {
			$result .= "<font color=\"blue\">La table existe d�j�</font><br />";
		}


		$result .= "&nbsp;->Ajout d'un champ 'ects_type_formation' � la table 'classes': ";
		$test1 = mysql_num_rows(mysql_query("SHOW COLUMNS FROM classes LIKE 'ects_type_formation'"));
		if ($test1 == 0) {
			$query = mysql_query("ALTER TABLE classes ADD ects_type_formation VARCHAR(255) NOT NULL DEFAULT '';");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		}
		else {
			$result .= "<font color=\"blue\">Champ d�j� pr�sent</font><br />";
		}

		$result .= "&nbsp;->Ajout d'un champ 'ects_parcours' � la table 'classes': ";
		$test1 = mysql_num_rows(mysql_query("SHOW COLUMNS FROM classes LIKE 'ects_parcours'"));
		if ($test1 == 0) {
			$query = mysql_query("ALTER TABLE classes ADD ects_parcours VARCHAR(255) NOT NULL DEFAULT '';");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		}
		else {
			$result .= "<font color=\"blue\">Champ d�j� pr�sent</font><br />";
		}

		$result .= "&nbsp;->Ajout d'un champ 'ects_code_parcours' � la table 'classes': ";
		$test1 = mysql_num_rows(mysql_query("SHOW COLUMNS FROM classes LIKE 'ects_code_parcours'"));
		if ($test1 == 0) {
			$query = mysql_query("ALTER TABLE classes ADD ects_code_parcours VARCHAR(255) NOT NULL DEFAULT '';");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		}
		else {
			$result .= "<font color=\"blue\">Champ d�j� pr�sent</font><br />";
		}

		$result .= "&nbsp;->Ajout d'un champ 'ects_domaines_etude' � la table 'classes': ";
		$test1 = mysql_num_rows(mysql_query("SHOW COLUMNS FROM classes LIKE 'ects_domaines_etude'"));
		if ($test1 == 0) {
			$query = mysql_query("ALTER TABLE classes ADD ects_domaines_etude VARCHAR(255) NOT NULL DEFAULT '';");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		}
		else {
			$result .= "<font color=\"blue\">Champ d�j� pr�sent</font><br />";
		}

		$result .= "&nbsp;->Ajout d'un champ 'ects_fonction_signataire_attestation' � la table 'classes': ";
		$test1 = mysql_num_rows(mysql_query("SHOW COLUMNS FROM classes LIKE 'ects_fonction_signataire_attestation'"));
		if ($test1 == 0) {
			$query = mysql_query("ALTER TABLE classes ADD ects_fonction_signataire_attestation VARCHAR(255) NOT NULL DEFAULT '';");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		}
		else {
			$result .= "<font color=\"blue\">Champ d�j� pr�sent</font><br />";
		}



		// Ajouts d'index
		$result .= "&nbsp;->Ajout de l'index 'annee' � la table archivage_disciplines<br />";
		//$req_test = mysql_query("SHOW INDEX FROM archivage_disciplines WHERE Key_name = 'annee'");
		//$req_res = mysql_num_rows($req_test);
		$req_res=0;
		$req_test = mysql_query("SHOW INDEX FROM archivage_disciplines ");
		if (mysql_num_rows($req_test)!=0) {
			while ($enrg = mysql_fetch_object($req_test)) {
				if ($enrg-> Key_name == 'annee') {$req_res++;}
			}
		}
		if ($req_res == 0) {
			$query = mysql_query("ALTER TABLE `archivage_disciplines` ADD INDEX annee ( `annee` )");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		} else {
			$result .= "<font color=\"blue\">L'index existe d�j�.</font><br />";
		}

		// Ajouts d'index
		$result .= "&nbsp;->Ajout de l'index 'INE' � la table archivage_disciplines<br />";
		//$req_test = mysql_query("SHOW INDEX FROM archivage_disciplines WHERE Key_name = 'INE'");
		//$req_res = mysql_num_rows($req_test);
		$req_res=0;
		$req_test = mysql_query("SHOW INDEX FROM archivage_disciplines ");
		if (mysql_num_rows($req_test)!=0) {
			while ($enrg = mysql_fetch_object($req_test)) {
				if ($enrg-> Key_name == 'INE') {$req_res++;}
			}
		}
		if ($req_res == 0) {
			$query = mysql_query("ALTER TABLE `archivage_disciplines` ADD INDEX INE ( `INE` )");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		} else {
			$result .= "<font color=\"blue\">L'index existe d�j�.</font><br />";
		}


		$result .= "&nbsp;->Ajout d'un champ 'mode_moy' � la table 'j_groupes_classes'<br />";
		$test1 = mysql_num_rows(mysql_query("SHOW COLUMNS FROM j_groupes_classes LIKE 'mode_moy'"));
		if ($test1 == 0) {
			$query = mysql_query("ALTER TABLE j_groupes_classes ADD mode_moy ENUM('-','sup10','bonus') NOT NULL DEFAULT '-' AFTER valeur_ects;");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		}
		else {
			$result .= "<font color=\"blue\">Le champ est d�j� pr�sent</font><br />";
		}

    $result .= "&nbsp;->Ajout d'un setting sur l'utilisation unique du cahier de textes de Gepi<br />";
    $req_test = mysql_query("SELECT VALUE FROM setting WHERE NAME = 'use_only_cdt'");
		$res_test = mysql_num_rows($req_test);
		if ($res_test == 0){
      if (mysql_query("INSERT INTO setting VALUES ('use_only_cdt', 'n');")){
        $result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
    }else{
      $result .= "<font color=\"blue\">Le setting est d�j� pr�sent</font><br />";
    }

    $result .= "&nbsp;->Ajout d'un setting sur le droit pour le professeur de saisir son edt<br />";
    $req_test = mysql_query("SELECT VALUE FROM setting WHERE NAME = 'edt_remplir_prof'");
		$res_test = mysql_num_rows($req_test);
		if ($res_test == 0){
      if (mysql_query("INSERT INTO setting VALUES ('edt_remplir_prof', 'n');")){
        $result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
    }else{
      $result .= "<font color=\"blue\">Le setting est d�j� pr�sent</font><br />";
    }

    $test = sql_query1("SHOW TABLES LIKE 'ct_sequences'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 'ct_sequences'. ";
			$sql="CREATE TABLE IF NOT EXISTS ct_sequences (
id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
titre VARCHAR( 255 ) NOT NULL ,
description TEXT NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 'ct_sequences': ".$result_inter."<br />";
			}
		}

    $result .= "&nbsp;->Ajout d'un champ 'id_sequence' � la table 'ct_entry'<br />";
		$test1 = mysql_num_rows(mysql_query("SHOW COLUMNS FROM ct_entry LIKE 'id_sequence'"));
		if ($test1 == 0) {
			$query = mysql_query("ALTER TABLE ct_entry ADD id_sequence INT ( 11 ) NOT NULL DEFAULT '0' AFTER id_login;");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		}
		else {
			$result .= "<font color=\"blue\">Le champ est d�j� pr�sent</font><br />";
		}

    $result .= "&nbsp;->Ajout d'un champ 'id_sequence' � la table 'ct_devoirs_entry'<br />";
		$test1 = mysql_num_rows(mysql_query("SHOW COLUMNS FROM ct_devoirs_entry LIKE 'id_sequence'"));
		if ($test1 == 0) {
			$query = mysql_query("ALTER TABLE ct_devoirs_entry ADD id_sequence INT ( 11 ) NOT NULL DEFAULT '0';");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		}
		else {
			$result .= "<font color=\"blue\">Le champ est d�j� pr�sent</font><br />";
		}

    $result .= "&nbsp;->Ajout d'un champ 'id_sequence' � la table 'ct_private_entry'<br />";
		$test1 = mysql_num_rows(mysql_query("SHOW COLUMNS FROM ct_private_entry LIKE 'id_sequence'"));
		if ($test1 == 0) {
			$query = mysql_query("ALTER TABLE ct_private_entry ADD id_sequence INT ( 11 ) NOT NULL DEFAULT '0';");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		}
		else {
			$result .= "<font color=\"blue\">Le champ est d�j� pr�sent</font><br />";
		}

    $test = sql_query1("SHOW TABLES LIKE 'plugins'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 'plugins'. ";
			$sql="CREATE TABLE IF NOT EXISTS plugins (
id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
nom VARCHAR( 100 ) NOT NULL,
repertoire VARCHAR( 255 ) NOT NULL,
description LONGTEXT NOT NULL,
ouvert CHAR( 1 ) default 'n',
  UNIQUE KEY `nom` (`nom`)
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 'plugins': ".$result_inter."<br />";
			}
		}

    $test = sql_query1("SHOW TABLES LIKE 'plugins_autorisations'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 'plugins_autorisations'. ";
			$sql="CREATE TABLE IF NOT EXISTS plugins_autorisations (
id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
plugin_id INT( 11 ) NOT NULL,
fichier VARCHAR( 100 ) NOT NULL,
user_statut VARCHAR( 50 ) NOT NULL,
auth CHAR( 1 ) default 'n'
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 'plugins_autorisations': ".$result_inter."<br />";
			}
		}

		$test = sql_query1("SHOW TABLES LIKE 'plugins_menus'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 'plugins_menus'. ";
			$sql="CREATE TABLE IF NOT EXISTS plugins_menus (
id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY ,
plugin_id INT( 11 ) NOT NULL,
user_statut VARCHAR( 50 ) NOT NULL,
titre_item VARCHAR ( 255 ) NOT NULL,
lien_item VARCHAR( 255 ) NOT NULL,
description_item VARCHAR( 255 ) NOT NULL
);";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 'plugins_menus': ".$result_inter."<br />";
			}
		}

		//==========================================================
		$result .= "<br />&nbsp;->Contr�le des valeurs autoris�es pour le champ 'acces' de la table 'matieres_appreciations_acces'&nbsp;: ";
		$query3 = mysql_query(" ALTER TABLE `matieres_appreciations_acces` CHANGE `acces` `acces` ENUM( 'y', 'n', 'date', 'd' ) NOT NULL DEFAULT 'y';");
		if ($query3) {
			$result .= "<font color=\"green\">Ok !</font><br />";
		} else {
			$result .= "<font color=\"red\">Erreur</font><br />";
		}
		//==========================================================

		$result .= "&nbsp;->Ajout d'un champ 'date_ele_resp' � la table 'cn_devoirs'<br />";
		$test1 = mysql_num_rows(mysql_query("SHOW COLUMNS FROM cn_devoirs LIKE 'date_ele_resp'"));
		if ($test1 == 0) {
			$query = mysql_query("ALTER TABLE cn_devoirs ADD date_ele_resp DATETIME NOT NULL ;");
			if ($query) {
				$result .= "<font color=\"green\">Ok !</font><br />";
			} else {
				$result .= "<font color=\"red\">Erreur</font><br />";
			}
		}
		else {
			$result .= "<font color=\"blue\">Le champ est d�j� pr�sent</font><br />";
		}

		//==========================================================

		$test = sql_query1("SHOW TABLES LIKE 'ref_wiki'");
		if ($test == -1) {
			$result .= "<br />Cr�ation de la table 'ref_wiki'. ";
			$sql="CREATE TABLE ref_wiki (id INT( 11 ) NOT NULL AUTO_INCREMENT PRIMARY KEY , ref VARCHAR( 255 ) NOT NULL , url VARCHAR( 255 ) NOT NULL , INDEX ( ref ) );";
			$result_inter = traite_requete($sql);
			if ($result_inter != '') {
				$result .= "<br />Erreur sur la cr�ation de la table 'ref_wiki': ".$result_inter."<br />";
			}
			else {
				$result .= "<br />&nbsp;->Ajout d'un enregistrement pour 'enseignement_invisible' dans 'ref_wiki'<br />";
				$query = mysql_query("INSERT INTO ref_wiki VALUES ('','enseignement_invisible', 'http://www.sylogix.org/wiki/gepi/Enseignement_invisible');");
				if ($query) {
					$result .= "<font color=\"green\">Ok !</font><br />";
				} else {
					$result .= "<font color=\"red\">Erreur</font><br />";
				}
			}
		}
		else {
			$result .= "<br />La table 'ref_wiki' existe d�j�. ";
		}

		$result .= "&nbsp;->Migration des fichiers joint des devoirs au format cdt2<br />";
		$query = mysql_query("insert into ct_devoirs_documents (id_ct_devoir, titre, taille, emplacement) select id_ct as id_ct_devoir ,titre, taille, emplacement from ct_documents where emplacement like '%/documents/cl_dev%' ;");
		if ($query == true) {
			$result .= "<font color=\"blue\">Migration effectu�e</font><br />";
			$result .= "&nbsp;->Suppression des fichiers joints devoir au format cdt1<br/>";

			$query = mysql_query("DELETE FROM ct_documents where emplacement like '%/documents/cl_dev%';");
			if ($query == true) {
			    $result .= "<font color=\"blue\">Suppression effectu�e</font><br />";
			} else {
			    $result .= "<font color=\"red\">Erreur</font><br />";
			    $result .= "Error: (" . mysql_errno() . ") " . mysql_error() . "< br/>";
			}
		} else {
			$result .= "<font color=\"red\">Erreur</font><br />";
			$result .= "Error: (" . mysql_errno() . ") " . mysql_error() . "< br/>";
		}


	// CORRECTIF: Pour remettre les choses d'�querre sur des base qui ont connu l'�poque avec param�trage diff�renci� entre eleves et responsables
	$nb_err_synchro_acces_app=0;
	$sql="SELECT DISTINCT id FROM classes;";
	//echo "$sql<br />";
	$res_classe=mysql_query($sql);
	if(mysql_num_rows($res_classe)>0) {
		$result.="&nbsp;->Synchronisation des param�trages �l�ves et responsables pour l'acc�s aux appr�ciations.<br />";
		while($lig_clas=mysql_fetch_object($res_classe)) {
			$sql="SELECT * FROM matieres_appreciations_acces WHERE statut='eleve' AND id_classe='$lig_clas->id';";
			//echo "$sql<br />";
			$res_ele=mysql_query($sql);
			if(mysql_num_rows($res_ele)>0) {
				while($lig_ele=mysql_fetch_object($res_ele)) {
					$sql="SELECT 1=1 FROM matieres_appreciations_acces WHERE statut='responsable' AND id_classe='$lig_clas->id' AND periode='$lig_ele->periode';";
					//echo "$sql<br />";
					$test_resp=mysql_query($sql);
					if(mysql_num_rows($test_resp)>0) {
						$sql="UPDATE matieres_appreciations_acces SET acces='$lig_ele->acces', date='$lig_ele->date' WHERE statut='responsable' AND id_classe='$lig_clas->id' AND periode='$lig_ele->periode';";
						//echo "$sql<br />";
						$query=mysql_query($sql);
						if(!$query) {$nb_err_synchro_acces_app++;}
					}
					else {
						$sql="INSERT INTO matieres_appreciations_acces SET acces='$lig_ele->acces', date='$lig_ele->date', statut='responsable', id_classe='$lig_clas->id', periode='$lig_ele->periode';";
						//echo "$sql<br />";
						$query=mysql_query($sql);
						if(!$query) {$nb_err_synchro_acces_app++;}
					}
				}
			}
	
			$sql="SELECT * FROM matieres_appreciations_acces WHERE statut='responsable' AND id_classe='$lig_clas->id';";
			//echo "$sql<br />";
			$res_resp=mysql_query($sql);
			if(mysql_num_rows($res_resp)>0) {
				while($lig_resp=mysql_fetch_object($res_resp)) {
					$sql="SELECT 1=1 FROM matieres_appreciations_acces WHERE statut='eleve' AND id_classe='$lig_clas->id' AND periode='$lig_resp->periode';";
					//echo "$sql<br />";
					$test_ele=mysql_query($sql);
					if(mysql_num_rows($test_ele)>0) {
						$sql="UPDATE matieres_appreciations_acces SET acces='$lig_resp->acces', date='$lig_resp->date' WHERE statut='eleve' AND id_classe='$lig_clas->id' AND periode='$lig_resp->periode';";
						//echo "$sql<br />";
						$query=mysql_query($sql);
						if(!$query) {$nb_err_synchro_acces_app++;}
					}
					else {
						$sql="INSERT INTO matieres_appreciations_acces SET acces='$lig_resp->acces', date='$lig_resp->date', statut='eleve', id_classe='$lig_clas->id', periode='$lig_resp->periode';";
						//echo "$sql<br />";
						$query=mysql_query($sql);
						if(!$query) {$nb_err_synchro_acces_app++;}
					}
				}
			}
		}
		if($nb_err_synchro_acces_app==0) {
			$result .= "<font color=\"green\">Ok !</font><br />";
		}
		else {
			$result .= "<font color=\"red\">Erreur</font><br />";
		}
	}

	# Il semble que sur certaines bases le champ ait pu �tre manquant:
	$sql = "SHOW TABLES LIKE 'absences_creneaux'";
	$req_existence = mysql_query($sql);
	$result .= $req_existence;
	if (mysql_num_rows($req_existence) != 0) {
	    $result .= "&nbsp;->Ajout d'un champ 'type_creneaux' � la table 'absences_creneaux'<br />";
	    $test_type_creneaux=mysql_num_rows(mysql_query("SHOW COLUMNS FROM absences_creneaux LIKE 'type_creneaux';"));
	    if ($test_type_creneaux>0) {
		    $result .= "<font color=\"blue\">Le champ existe d�j�.</font><br />";
	    }
	    else {
		    $query = mysql_query("ALTER TABLE absences_creneaux ADD type_creneaux VARCHAR( 15 ) NOT NULL;");
		    if ($query) {
				    $result .= "<font color=\"green\">Ok !</font><br />";
		    } else {
				    $result .= "<font color=\"red\">Erreur</font><br />";
		    }
	    }
	} else {
	    $result .= "<p style=\"color:blue;\">La table 'absences_creneaux' n'existe plus</p>";
	}

?>
