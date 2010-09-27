<?php
/**
 *
 *
 * @version $Id$
 *
 * Copyright 2001, 2007 Thomas Belliard, Laurent Delineau, Eric Lebrun, Stephane Boireau, Julien Jocal
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

/**
 * Classe de helpers sur les types, motifs, justifications et actions des absences
 */
class AbsencesNotificationHelper {

  /**
   * Merge une notification avec son modele
   *
   * @param AbsenceEleveNotification $notification
   * @param String $modele chemin du modele tbs
   * @return clsTinyButStrong $TBS deroulante des types d'absences
   */
  public static function MergeNotification($notification, $modele){
    // load the TinyButStrong libraries
    if (version_compare(PHP_VERSION,'5')<0) {
	include_once('../tbs/tbs_class.php'); // TinyButStrong template engine for PHP 4
    } else {
	include_once('../tbs/tbs_class_php5.php'); // TinyButStrong template engine
    }
    include_once('../tbs/plugins/tbsdb_php.php');

    $TBS = new clsTinyButStrong; // new instance of TBS
    if (substr($modele, -3) == "odt") {
	include_once('../tbs/plugins/tbs_plugin_opentbs.php');
	$TBS->Plugin(TBS_INSTALL, OPENTBS_PLUGIN); // load OpenTBS plugin
    }
    $TBS->LoadTemplate($modele);

    //merge des champs commun
    $TBS->MergeField('nom_etab',getSettingValue("gepiSchoolName"));
    $TBS->MergeField('tel_etab',getSettingValue("gepiSchoolTel"));
    $TBS->MergeField('fax_etab',getSettingValue("gepiSchoolFax"));
    $email_abs_etab = getSettingValue("gepiAbsenceEmail");
    if ($email_abs_etab == null || $email_abs_etab == '') {
	$email_abs_etab = getSettingValue("gepiSchoolEmail");
    }
    $TBS->MergeField('mail_etab', $email_abs_etab);
    $TBS->MergeField('notif_id',$notification->getId());
    
    //on r�cup�re la liste des noms d'eleves
    $eleve_col = new PropelCollection();
    if ($notification->getAbsenceEleveTraitement() != null) {
	foreach ($notification->getAbsenceEleveTraitement()->getAbsenceEleveSaisies() as $saisie) {
	    $eleve_col->add($saisie->getEleve());
	}
    }

    $TBS->MergeBlock('el_col',$eleve_col);

    if ($notification->getAbsenceEleveTraitement() != null) {
	$query_string = 'AbsenceEleveSaisieQuery::create()->filterByEleveId(%p1%)
	    ->useJTraitementSaisieEleveQuery()
	    ->filterByATraitementId('.$notification->getAbsenceEleveTraitement()->getId().')->endUse()
		->orderBy("DebutAbs", Criteria::ASC)
		->find()';
    } else {
	$query_string = 'AbsenceEleveSaisieQuery::create()->filterByEleveId(%p1%)
	    ->where(0 <> 0)->find()';
    }

    $TBS->MergeBlock('saisies', 'php', $query_string);


    if ($notification->getTypeNotification() == AbsenceEleveNotification::$TYPE_COURRIER) {
	//on va mettre les champs dans des variables simple
	if ($notification->getResponsableEleveAdresse() != null && $notification->getResponsableEleveAdresse()->getResponsableEleves()->count() == 1) {
	    //echo 'dest1';
	    $responsable = $notification->getResponsableEleveAdresse()->getResponsableEleves()->getFirst();
	    $destinataire = $responsable->getCivilite().' '.strtoupper($responsable->getNom()).' '.strtoupper($responsable->getPrenom());
	} elseif ($notification->getResponsableEleveAdresse() != null) {
	    //echo 'dest2';
	    $responsable = $notification->getResponsableEleveAdresse()->getResponsableEleves()->getFirst();
	    $destinataire = $responsable->getCivilite().' '.strtoupper($responsable->getNom());
	    $responsable = $notification->getResponsableEleveAdresse()->getResponsableEleves()->getNext();
	    $destinataire .= '  '.strtoupper($responsable->getCivilite()).' '.strtoupper($responsable->getNom());
	} else {
	    $destinataire = '';
	}
	$TBS->MergeField('destinataire',$destinataire);

	$adr = $notification->getResponsableEleveAdresse();
	if ($adr == null) {
	    $adr = new ResponsableEleveAdresse();
	}
	$TBS->MergeField('adr',$adr);


	$adr_etablissement = new ResponsableEleveAdresse();
	$adr_etablissement->setAdr1(getSettingValue("gepiSchoolAdress1"));
	$adr_etablissement->setAdr2(getSettingValue("gepiSchoolAdress2"));
	$adr_etablissement->setCp(getSettingValue("gepiSchoolZipCode"));
	$adr_etablissement->setCommune(getSettingValue("gepiSchoolCity"));
	$TBS->MergeField('adr_etab',$adr_etablissement);

    } else if ($notification->getTypeNotification() == AbsenceEleveNotification::$TYPE_EMAIL) {
	$destinataire = '';
	foreach ($notification->getResponsableEleves() as $responsable) {
	    $destinataire .= $responsable->getCivilite().' '.strtoupper($responsable->getNom()).' '.strtoupper($responsable->getPrenom()).' ';
	}
	$TBS->MergeField('destinataire',$destinataire);
    } else if ($notification->getTypeNotification() == AbsenceEleveNotification::$TYPE_SMS) {
	$destinataire = '';
	foreach ($notification->getResponsableEleves() as $responsable) {
	    $destinataire .= $responsable->getCivilite().' '.strtoupper($responsable->getNom()).' '.strtoupper($responsable->getPrenom()).' ';
	}
	$TBS->MergeField('destinataire',$destinataire);
    }

    $TBS->Show(TBS_NOTHING);
    return $TBS;
  }

/**
   * Envoi une notification (email ou sms uniquement)
   *
   * @param AbsenceEleveNotification $notification
   * @param String $message le message texte a envoyer
   * @return String message d'erreur si envoi �chou�
   */
  public static function EnvoiNotification($notification, $message){
    $return_message = '';
    if ($notification->getStatutEnvoi() != AbsenceEleveNotification::$STATUT_INITIAL && $notification->getStatutEnvoi() != AbsenceEleveNotification::$STATUT_INITIAL) {
	return 'Seul une notification de statut initial ou prete � envoyer peut �tre envoy�e avec cette m�thode';
    }
    if ($notification->getTypeNotification() != AbsenceEleveNotification::$TYPE_EMAIL &&
	    $notification->getTypeNotification() != AbsenceEleveNotification::$TYPE_SMS) {
	return 'Seul une notification de type email ou sms peut �tre envoy�e avec cette m�thode';
    } elseif ($notification->getTypeNotification() == AbsenceEleveNotification::$TYPE_EMAIL) {
	if ($notification->getEmail() == null || $notification->getEmail() == '') {
	    $notification->setErreurMessageEnvoi('email non renseign�');
	    $notification->save();
	    return 'Echec de l\'envoi : email non renseign�.';
	}

	include('../lib/email_validator.php');
	if (!validEmail($notification->getEmail())) {
	    $notification->setErreurMessageEnvoi('adresse email non valide');
	    $notification->save();
	    return 'Erreur : adresse email non valide.';
	}

	$email_abs_etab = getSettingValue("gepiAbsenceEmail");
	if ($email_abs_etab == null || $email_abs_etab == '') {
	    $email_abs_etab = getSettingValue("gepiSchoolEmail");
	}
	$envoi = mail($notification->getEmail(),
		"Notification d'absence ".getSettingValue("gepiSchoolName").' - Ref : '.$notification->getId().' -',
		$message,
	       "From: ".$email_abs_etab."\r\n"
	       ."X-Mailer: PHP/" . phpversion());

	$notification->setDateEnvoi('now');
	if ($envoi) {
	    $notification->setStatutEnvoi(AbsenceEleveNotification::$STATUT_SUCCES);
	    $return_message = '';
	} else {
	    $return_message = 'Non accept� pour livraison.';
	    $notification->setErreurMessageEnvoi($return_message);
	    $notification->setStatutEnvoi(AbsenceEleveNotification::$STATUT_ECHEC);
	}
	$notification->save();
	return $return_message;

    } else if ($notification->getTypeNotification() == AbsenceEleveNotification::$TYPE_SMS) {
	if (getSettingValue("abs2_sms")!='y') {
	    return 'Erreur : envoi de sms d�sactiv�.';
	}

	// Load the template
	if (getSettingValue("abs2_sms_prestataire")=='tm4b') {
	    $url = "http://www.tm4b.com/client/api/http.php";
	    $hote = "tm4b.com";
	    $script = "/client/api/http.php";
	    $param['username'] = getSettingValue("abs2_sms_username"); // identifiant de notre compte TM4B
	    $param['password'] = getSettingValue("abs2_sms_password"); // mot de passe de notre compte TM4B
	    $param['type'] = 'broadcast'; // envoi de sms
	    $param['msg'] = $message; // message que l'on d�sire envoyer

	    $tel = $notification->getTelephone();
	    if (substr($tel, 0, 1) == '0') {
		$tel = '33'.substr($tel, 1, 9);
	    }
	    $param['to'] = $tel; // num�ros de t�l�phones auxquels on envoie le message
	    $param['from'] = getSettingValue("gepiSchoolName"); // exp�diteur du message (first class uniquement)
	    $param['route'] = 'business'; // type de route (pour la france, business class uniquement)
	    $param['version'] = '2.1';
	    $param['sim'] = 'yes'; // on active le mode simulation, pour tester notre script
	} else if (getSettingValue("abs2_sms_prestataire")=='123-sms') {
	    $url = "http://www.123-SMS.net/http.php";
	    $hote = "123-SMS.net";
	    $script = "/http.php";
	    $param['email'] = getSettingValue("abs2_sms_username"); // identifiant de notre compte TM4B
	    $param['pass'] = getSettingValue("abs2_sms_password"); // mot de passe de notre compte TM4B
	    $param['message'] = $message; // message que l'on d�sire envoyer
	    $param['numero'] = $notification->getTelephone(); // num�ros de t�l�phones auxquels on envoie le message
	}

	$requete = '';
	foreach($param as $clef => $valeur)  {
	    $requete .= $clef . '=' . urlencode($valeur); // il faut bien formater les valeurs
	    $requete .= '&';
	}

	if (in_array  ('curl', get_loaded_extensions())) {
	    //on utilise curl pour la requete au service sms
	    $ch = curl_init();
	    curl_setopt($ch, CURLOPT_URL, $url);
	    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	    curl_setopt($ch, CURLOPT_POST, 1);
	    curl_setopt($ch, CURLOPT_POSTFIELDS, $requete);
	    $reponse = curl_exec($ch);
	    curl_close($ch);
	} else {
	    $longueur_requete = strlen($requete);
	    $methode = "POST";
	    $entete = $methode . " " . $script . " HTTP/1.1\r\n";
	    $entete .= "Host: " . $hote . "\r\n";
	    $entete .= "Content-Type: application/x-www-form-urlencoded\r\n";
	    $entete .= "Content-Length: " . $longueur_requete . "\r\n";
	    $entete .= "Connection: close\r\n\r\n";
	    $entete .= $requete . "\r\n";
	    $socket = fsockopen($hote, 80, $errno, $errstr);
	    if($socket) {
		fputs($socket, $entete); // envoi de l'entete
		while(!feof($socket)) {
		    $reponseArray[] = fgets($socket); // recupere les resultats
		}
		$reponse = $reponseArray[8];
		fclose($socket);
	    } else {
		$reponse = 'error : no socket available.';
	    }
	}

	$notification->setDateEnvoi('now');

	//traitement de la r�ponse
	if (getSettingValue("abs2_sms_prestataire")=='tm4b') {
	    if (substr($reponse, 0, 5) == 'error') {
		$return_message = 'Erreur : message non envoy�. Code erreur : '.$reponse;
		$notification->setStatutEnvoi(AbsenceEleveNotification::$STATUT_ECHEC);
		$notification->setErreurMessageEnvoi($reponse);
	    } else {
		$notification->setStatutEnvoi(AbsenceEleveNotification::$STATUT_SUCCES);
	    }
	} else if (getSettingValue("abs2_sms_prestataire")=='123-sms') {
	    if ($reponse != '80') {
		$return_message = 'Erreur : message non envoy�. Code erreur : '.$reponse;
		$notification->setStatutEnvoi(AbsenceEleveNotification::$STATUT_ECHEC);
		$notification->setErreurMessageEnvoi($reponse);
	    } else {
		$notification->setStatutEnvoi(AbsenceEleveNotification::$STATUT_SUCCES);
	    }
	}
	$notification->save();
	return $return_message;
    }
  }
}

// utilis� pour formater certain champs dans les modele tbs
function tbs_str($FieldName,&$CurrRec) {
    $CurrRec = html_entity_decode($CurrRec,ENT_QUOTES);
    $CurrRec = str_replace('\"','"',str_replace("\'","'",$CurrRec));
    $CurrRec = str_replace('\\'.htmlspecialchars('"',ENT_QUOTES),htmlspecialchars('"',ENT_QUOTES),str_replace("\\".htmlspecialchars("'",ENT_QUOTES),htmlspecialchars("'",ENT_QUOTES),$CurrRec));

    $CurrRec = stripslashes($CurrRec);
}
?>