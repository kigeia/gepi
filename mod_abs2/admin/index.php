<?php
/*
 *
 *$Id$
 *
 * Copyright 2010-2011 Josselin Jacquard
 *
 * This file and the mod_abs2 module is distributed under GPL version 3, or
 * (at your option) any later version.
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

$niveau_arbo = 2;
// Initialisations files
require_once("../../lib/initialisations.inc.php");
//mes fonctions
//include("../lib/functions.php");

// Resume session
$resultat_session = $session_gepi->security_check();
if ($resultat_session == 'c') {
    header("Location: ../../utilisateurs/mon_compte.php?change_mdp=yes");
    die();
} else if ($resultat_session == '0') {
    header("Location: ../../logout.php?auto=1");
    die();
}

// Check access
if (!checkAccess()) {
    header("Location: ../../logout.php?auto=1");
    die();
}

$retour=$_SESSION['retour'];
$_SESSION['retour']=$_SERVER['PHP_SELF'] ;

$msg = '';

if (isset($_POST['is_posted'])) {
	if ($_POST['is_posted']=='1') {

		if (isset($_POST['activer'])) {
			if (!saveSetting("active_module_absence", $_POST['activer'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation !";
			}
		}
		if (isset($_POST['activer_prof'])) {
			if (!saveSetting("active_module_absence_professeur", $_POST['activer_prof'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la saisie par les professeurs !";
			}
		}
		if (isset($_POST['activer_resp'])) {
			if (!saveSetting("active_absences_parents", $_POST['activer_resp'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la consultation par les responsables �l�ves !";
			}
		}
		if (isset($_POST['gepiAbsenceEmail'])) {
			if (!saveSetting("gepiAbsenceEmail", $_POST['gepiAbsenceEmail'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre gestion absence email !";
			}
		}
		if (isset($_POST['abs2_sms_prestataire'])) {
			if (!saveSetting("abs2_sms_prestataire", $_POST['abs2_sms_prestataire'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre prestataire sms !";
			}
		}
		if (isset($_POST['abs2_sms_username'])) {
			if (!saveSetting("abs2_sms_username", $_POST['abs2_sms_username'])) {
				$msg = "Erreur lors de l'enregistrement du nom d'utilisateur prestataire sms !";
			}
		}
		if (isset($_POST['abs2_sms_password'])) {
			if (!saveSetting("abs2_sms_password", $_POST['abs2_sms_password'])) {
				$msg = "Erreur lors de l'enregistrement du mot de passe prestataire sms !";
			}
		}
		if (isset($_POST['abs2_retard_critere_duree'])) {
			if (!saveSetting("abs2_retard_critere_duree", $_POST['abs2_retard_critere_duree'])) {
				$msg = "Erreur lors de l'enregistrement de abs2_retard_critere_duree !";
			}
		}
		if (isset($_POST['abs2_heure_demi_journee'])) {
			try {
				$heure = new DateTime($_POST['abs2_heure_demi_journee']);
				if (!saveSetting("abs2_heure_demi_journee", $heure->format('H:i'))) {
					$msg = "Erreur lors de l'enregistrement de abs2_heure_demi_journee !";
				}
			} catch (Exception $x) {
				$message_enregistrement .= "Mauvais format d'heure.<br/>";
			}
		}

		if (isset($_POST['abs2_alleger_abs_du_jour'])) {
			if (!saveSetting("abs2_alleger_abs_du_jour", $_POST['abs2_alleger_abs_du_jour'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_alleger_abs_du_jour";
			}
		} else {
			if (!saveSetting("abs2_alleger_abs_du_jour", 'n')) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_alleger_abs_du_jour";
			}
		}

		if (isset($_POST['abs2_import_manuel_bulletin'])) {
			if (!saveSetting("abs2_import_manuel_bulletin", $_POST['abs2_import_manuel_bulletin'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_import_manuel_bulletin";
			}
		} else {
			if (!saveSetting("abs2_import_manuel_bulletin", 'n')) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_import_manuel_bulletin";
			}
		}

                if (isset($_POST['abs2_saisie_prof_decale_journee'])) {
			if (!saveSetting("abs2_saisie_prof_decale_journee", $_POST['abs2_saisie_prof_decale_journee'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la saisie d�cal�e sur la journ�e pour les professeurs !";
			}
		} else {
			if (!saveSetting("abs2_saisie_prof_decale_journee", 'n')) {
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la saisie d�cal�e sur la journ�e pour les professeurs !";
			}
		}

		if (isset($_POST['abs2_saisie_prof_decale'])) {
			if (!saveSetting("abs2_saisie_prof_decale", $_POST['abs2_saisie_prof_decale'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la saisie d�cal�e sans limite pour les professeurs !";
			}
		} else {
			if (!saveSetting("abs2_saisie_prof_decale", 'n')) {
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la saisie d�cal�e sans limite pour les professeurs !";
			}
		}

		if (isset($_POST['abs2_saisie_prof_hors_cours'])) {
			if (!saveSetting("abs2_saisie_prof_hors_cours", $_POST['abs2_saisie_prof_hors_cours'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la saisie par les professeurs hors cours pr�vu !";
			}
		} else {
			if (!saveSetting("abs2_saisie_prof_hors_cours", 'n')) {
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la saisie par les professeurs hors cours pr�vu !";
			}
		}

		if (isset($_POST['abs2_modification_saisie_une_heure'])) {
			if (!saveSetting("abs2_modification_saisie_une_heure", $_POST['abs2_modification_saisie_une_heure'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la modification saisie par les professeurs dans l'heure suivant la saisie !";
			}
		} else {
			if (!saveSetting("abs2_modification_saisie_une_heure", 'n')) {
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la modification saisie par les professeurs dans l'heure suivant la saisie !";
			}
		}

		if (isset($_POST['abs2_sms'])) {
			if (!saveSetting("abs2_sms", $_POST['abs2_sms'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la modification saisie par les professeurs dans l'heure suivant la saisie !";
			}
		} else {
			if (!saveSetting("abs2_sms", 'n')) {
				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la modification saisie par les professeurs dans l'heure suivant la saisie !";
			}
		}

		if (isset($_POST['abs2_saisie_par_defaut_sans_manquement'])) {
			if (!saveSetting("abs2_saisie_par_defaut_sans_manquement", $_POST['abs2_saisie_par_defaut_sans_manquement'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_saisie_par_defaut_sans_manquement";
			}
		} else {
			if (!saveSetting("abs2_saisie_par_defaut_sans_manquement", 'n')) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_saisie_par_defaut_sans_manquement";
			}
		}

		if (isset($_POST['abs2_saisie_multi_type_sans_manquement'])) {
			if (!saveSetting("abs2_saisie_multi_type_sans_manquement", $_POST['abs2_saisie_multi_type_sans_manquement'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_saisie_multi_type_sans_manquement !";
			}
		} else {
			if (!saveSetting("abs2_saisie_multi_type_sans_manquement", 'n')) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_saisie_multi_type_sans_manquement !";
			}
		}

		if (isset($_POST['abs2_saisie_par_defaut_sous_responsabilite_etab'])) {
			if (!saveSetting("abs2_saisie_par_defaut_sous_responsabilite_etab", $_POST['abs2_saisie_par_defaut_sous_responsabilite_etab'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_saisie_par_defaut_sous_responsabilite_etab !";
			}
		} else {
			if (!saveSetting("abs2_saisie_par_defaut_sous_responsabilite_etab", 'n')) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_saisie_par_defaut_sous_responsabilite_etab !";
			}
		}

		if (isset($_POST['abs2_saisie_multi_type_sous_responsabilite_etab'])) {
			if (!saveSetting("abs2_saisie_multi_type_sous_responsabilite_etab", $_POST['abs2_saisie_multi_type_sous_responsabilite_etab'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_saisie_multi_type_sous_responsabilite_etab !";
			}
		} else {
			if (!saveSetting("abs2_saisie_multi_type_sous_responsabilite_etab", 'n')) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_saisie_multi_type_sous_responsabilite_etab !";
			}
		}

		if (isset($_POST['abs2_saisie_multi_type_non_justifiee'])) {
			if (!saveSetting("abs2_saisie_multi_type_non_justifiee", $_POST['abs2_saisie_multi_type_non_justifiee'])) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_saisie_multi_type_non_justifiee !";
			}
		} else {
			if (!saveSetting("abs2_saisie_multi_type_non_justifiee", 'n')) {
				$msg = "Erreur lors de l'enregistrement du param�tre abs2_saisie_multi_type_non_justifiee !";
			}
		}
//		if (isset($_POST['abs2_modification_saisie_sans_limite'])) {
//			if (!saveSetting("abs2_modification_saisie_sans_limite", $_POST['abs2_modification_saisie_sans_limite'])) {
//				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la modification sasie par les professeurs dans l'heure suivant la saisie !";
//			}
//		} else {
//			if (!saveSetting("abs2_modification_saisie_sans_limite", 'n')) {
//				$msg = "Erreur lors de l'enregistrement du param�tre activation/d�sactivation de la modification sasie par les professeurs dans l'heure suivant la saisie !";
//			}
//		}

	}
}


if (isset($_POST['classement'])) {
	if (!saveSetting("absence_classement_top", $_POST['classement'])) {
		$msg = "Erreur lors de l'enregistrement du param�tre de classement des absences (TOP 10) !";
	}
}
if (isset($_POST['installation_base'])) {
            // Remise � z�ro de la table des droits d'acc�s
	$result = "";
        require '../../utilitaires/updates/access_rights.inc.php';
	require '../../utilitaires/updates/mod_abs2.inc.php';
}

if (isset($_POST['is_posted']) and ($msg=='')) $msg = "Les modifications ont �t� enregistr�es !";

// A propos du TOP 10 : r�cup�ration du setting pour le select en bas de page
$selected10 = $selected20 = $selected30 = $selected40 = $selected50 = NULL;

if (getSettingValue("absence_classement_top") == '10'){
  $selected10 = ' selected="selected"';
}elseif (getSettingValue("absence_classement_top") == '20') {
  $selected20 = ' selected="selected"';
}elseif (getSettingValue("absence_classement_top") == '30') {
  $selected30 = ' selected="selected"';
}elseif (getSettingValue("absence_classement_top") == '40') {
  $selected40 = ' selected="selected"';
}elseif (getSettingValue("absence_classement_top") == '50') {
  $selected50 = ' selected="selected"';
}

// header
$titre_page = "Gestion du module absence";
require_once("../../lib/header.inc");


echo "<p class='bold'><a href=\"../../accueil_modules.php\"><img src='../../images/icons/back.png' alt='Retour' class='back_link'/> Retour</a>";
echo "</p>";
    if (isset ($result)) {
	    echo "<center><table width=\"80%\" border=\"1\" cellpadding=\"5\" cellspacing=\"1\" summary='R�sultat de mise � jour'><tr><td><h2 align=\"center\">R�sultat de la mise � jour</h2>";
	    echo $result;
	    echo "</td></tr></table></center>";
    }
?>
<h2>Gestion des absences par les CPE</h2>
<p style="font-style: italic;">La d�sactivation du module de la gestion des absences n'entra�ne aucune
suppression des donn�es. Lorsque le module est d�sactiv�, les CPE n'ont pas acc�s au module.</p>

<form action="index.php" name="form1" method="post">
<?php
echo add_token_field();
?>
<p>
	<input type="radio" id="activerY" name="activer" value="y"
	<?php if (getSettingValue("active_module_absence")=='y') echo ' checked="checked"'; ?> />
	<label for="activerY">&nbsp;Activer le module de la gestion des absences</label>
</p>
<p>
	<input type="radio" id="activer2" name="activer" value="2"
	<?php if (getSettingValue("active_module_absence")=='2') echo ' checked="checked"'; ?> />
	<label for="activer2">&nbsp;Activer le module de la gestion des absences version 2</label>
</p>
<p>
	<input type="radio" id="activerN" name="activer" value="n"
	<?php if (getSettingValue("active_module_absence")=='n') echo ' checked="checked"'; ?> />
	<label for="activerN">&nbsp;D�sactiver le module de la gestion des absences</label>
	<input type="hidden" name="is_posted" value="1" />
</p>
<p>
	<input type="checkbox" name="abs2_import_manuel_bulletin" value="y"
	<?php if (getSettingValue("abs2_import_manuel_bulletin")=='y') echo " checked='checked'"; ?> />
	<label for="abs2_import_manuel_bulletin">&nbsp;Utiliser un import (manuel, gep ou sconet) pour les bulletins et fiches �l�ve.</label>
</p>
<p>
	<input type="checkbox" name="abs2_alleger_abs_du_jour" value="y"
	<?php if (getSettingValue("abs2_alleger_abs_du_jour")=='y') echo " checked='checked'"; ?> />
	<label for="abs2_alleger_abs_du_jour">&nbsp;Alleger les calculs de la page absence du jour : d�sactive la recherche des saisies contradictoires et des pr�sences.</label>
</p>
<p>
E-mail gestion absence �tablissement :
<input type="text" name="gepiAbsenceEmail" size="20" value="<?php echo(getSettingValue("gepiAbsenceEmail")); ?>"/>
</p>

<h2>Saisie des absences par les professeurs</h2>
<p style="font-style: italic;">La d�sactivation du module de la gestion des absences n'entra�ne aucune suppression des donn�es saisies par les professeurs. Lorsque le module est d�sactiv�, les professeurs n'ont pas acc�s au module.
Normalement, ce module ne devrait �tre activ� que si le module ci-dessus est lui-m�me activ�.</p>
<p>
	<input type="radio" id="activerProfY" name="activer_prof" value="y"
	<?php if (getSettingValue("active_module_absence_professeur")=='y') echo " checked='checked'"; ?> />
	<label for="activerProfY">&nbsp;Activer le module de la saisie des absences par les professeurs</label>
</p>
<p>
	<input type="radio" id="activerProfN" name="activer_prof" value="n"
	<?php if (getSettingValue("active_module_absence_professeur")=='n') echo " checked='checked'"; ?> />
	<label for="activerProfN">&nbsp;D�sactiver le module de la saisie des absences par les professeurs</label>
	<input type="hidden" name="is_posted" value="1" />
</p>
<p>
	<input type="checkbox" name="abs2_saisie_prof_decale_journee" value="y"
	<?php if (getSettingValue("abs2_saisie_prof_decale_journee")=='y') echo " checked='checked'"; ?> />
	<label for="abs2_saisie_prof_decale_journee">&nbsp;Permettre la saisie d�cal�e sur une m�me journ�e par les professeurs</label>
</p>
<p>
	<input type="checkbox" name="abs2_saisie_prof_decale" value="y"
	<?php if (getSettingValue("abs2_saisie_prof_decale")=='y') echo " checked='checked'"; ?> />
	<label for="abs2_saisie_prof_decale">&nbsp;Permettre la saisie d�cal�e sans limite de temps par les professeurs</label>
</p>
<p>
	<input type="checkbox" name="abs2_saisie_prof_hors_cours" value="y"
	<?php if (getSettingValue("abs2_saisie_prof_hors_cours")=='y') echo " checked='checked'"; ?> />
	<label for="abs2_saisie_prof_hors_cours">&nbsp;Permettre la saisie d'une absence hors des cours pr�vus dans l'emploi du temps du professeur</label>
</p>
<p>
	<input type="checkbox" name="abs2_modification_saisie_une_heure" value="y"
	<?php if (getSettingValue("abs2_modification_saisie_une_heure")=='y') echo " checked='checked'"; ?> />
	<label for="abs2_modification_saisie_une_heure">&nbsp;Permettre la modification d'une saisie dans l'heure qui a suivi sa cr�ation</label>
</p>
<!--p>
	<input type="checkbox" name="abs2_modification_saisie_sans_limite" value="y"
	<?php //if (getSettingValue("abs2_modification_saisie_sans_limite")=='y') echo " checked='checked'"; ?> />
	<label for="abs2_modification_saisie_sans_limite">&nbsp;Permettre la modification d'une saisie sans limite de temps</label>
</p-->

<h2>Envoi des SMS</h2>
<p>
	<input type="checkbox" id="abs2_sms" name="abs2_sms" value="y"
	<?php if (getSettingValue("abs2_sms")=='y') echo " checked='checked'"; ?> />
	<label for="abs2_sms">&nbsp;Activer l'envoi des sms</label>
</p>
<?php
  $extensions = get_loaded_extensions();
  if(!in_array('curl',$extensions)) {
      echo "<p style='font-style: italic; color:red'>ATTENTION : Il semble que votre serveur ne soit pas configur� pour l'envoi de SMS. Cette fonctionnalit� n�c�ssite l'extension PHP CURL.";
      echo "</p>";
  };
 ?>
<p>
    <label for="abs2_sms_prestataire">Choisissez un prestataire</label>
	<select id="abs2_sms_prestataire" name="abs2_sms_prestataire">
	<option value=''></option>
	<option value='tm4b' <?php if (getSettingValue("abs2_sms_prestataire")=='tm4b') echo " selected "; ?> >www.tm4b.com</option>
    <option value='pluriware' <?php if (getSettingValue("abs2_sms_prestataire")=='pluriware') echo " selected "; ?> >Pluriware (agr��e EN)</option>
	<option value='123-sms' <?php if (getSettingValue("abs2_sms_prestataire")=='123-sms') echo " selected "; ?> >www.123-sms.net</option>
	</select><br/>
	Nom d'utilisateur du service <input type="text" name="abs2_sms_username" size="20" value="<?php echo(getSettingValue("abs2_sms_username")); ?>"/><br/>
	Mot de passe <input type="text" name="abs2_sms_password" size="20" value="<?php echo(getSettingValue("abs2_sms_password")); ?>"/><br/>
</p>

<h2>Configuration des saisies</h2>
<p>
	<input type="checkbox" id="abs2_saisie_par_defaut_sans_manquement" name="abs2_saisie_par_defaut_sans_manquement" value="y"
	<?php if (getSettingValue("abs2_saisie_par_defaut_sans_manquement")=='y') echo " checked='checked'"; ?> />
	<label for="abs2_saisie_par_defaut_sans_manquement">&nbsp;Dans le cas d'une saisie sans type, consid�rer que l'�l�ve ne manque pas � ses obligations.
	   (Donc ces saisies ne seront pas compt�es dans les bulletins)</label>
</p>
<p>
	<input type="checkbox" id="abs2_saisie_multi_type_sans_manquement" name="abs2_saisie_multi_type_sans_manquement" value="y"
	<?php if (getSettingValue("abs2_saisie_multi_type_sans_manquement")=='y') echo " checked='checked'"; ?> />
	<label for="abs2_saisie_multi_type_sans_manquement">&nbsp;Dans le cas de plusieurs saisies simultan�es avec des types contradictoires, consid�rer que l'�l�ve ne manque pas � ses obligations.
	   (Donc ces saisies ne seront pas compt�es dans les bulletins)</label>
</p>
<p>
	<input type="checkbox" id="abs2_saisie_multi_type_non_justifiee" name="abs2_saisie_multi_type_non_justifiee" value="y"
	<?php if (getSettingValue("abs2_saisie_multi_type_non_justifiee")=='y') echo " checked='checked'"; ?> />
	<label for="abs2_saisie_multi_type_non_justifiee">&nbsp;Dans le cas de plusieurs saisies simultan�es avec des types contradictoires, consid�rer que la saisie n'est pas justifi�e.</label>
</p>
<p>
	<input type="checkbox" id="abs2_saisie_par_defaut_sous_responsabilite_etab" name="abs2_saisie_par_defaut_sous_responsabilite_etab" value="y"
	<?php if (getSettingValue("abs2_saisie_par_defaut_sous_responsabilite_etab")=='y') echo " checked='checked'"; ?> />
	<label for="abs2_saisie_par_defaut_sous_responsabilite_etab">&nbsp;Dans le cas d'une saisie sans type, consid�rer que l'�l�ve est par d�faut sous la responsabilit� de l'�tablissement.</label>
</p>
<p>
	<input type="checkbox" id="abs2_saisie_multi_type_sous_responsabilite_etab" name="abs2_saisie_multi_type_sous_responsabilite_etab" value="y"
	<?php if (getSettingValue("abs2_saisie_multi_type_sous_responsabilite_etab")=='y') echo " checked='checked'"; ?> />
	<label for="abs2_saisie_multi_type_sous_responsabilite_etab">&nbsp;Dans le cas de plusieurs saisies simultan�es avec des types contradictoires, consid�rer que l'�l�ve est par d�faut sous la responsabilit� de l'�tablissement.</label>
</p>
<p>
	<?php if (getSettingValue("abs2_retard_critere_duree") == null || getSettingValue("abs2_retard_critere_duree") == '') saveSetting("abs2_retard_critere_duree", 30); ?>
	Configuration du bulletin : Dans le d�compte des demi-journ�es d'absence, consid�rer les saisie inf�rieures �
	<select name="abs2_retard_critere_duree">
		<option value="00" <?php if (getSettingValue("abs2_retard_critere_duree") == '00') echo " selected"; ?>>00</option>
		<option value="10" <?php if (getSettingValue("abs2_retard_critere_duree") == '10') echo " selected"; ?>>10</option>
		<option value="20" <?php if (getSettingValue("abs2_retard_critere_duree") == '20') echo " selected"; ?>>20</option>
		<option value="30" <?php if (getSettingValue("abs2_retard_critere_duree") == '30') echo " selected"; ?>>30</option>
		<option value="40" <?php if (getSettingValue("abs2_retard_critere_duree") == '40') echo " selected"; ?>>40</option>
		<option value="50" <?php if (getSettingValue("abs2_retard_critere_duree") == '50') echo " selected"; ?>>50</option>
	</select>
	min comme des retards.<br/>
	Note : si les cr�neaux durent 45 minutes et que ce param�tre est r�gl� sur 50 min, la plupart de vos saisies seront d�compt�es comme retard.<br/>
	Note : sont consid�r�es comme retard les saisies de dur�es inf�rieures au param�tre ci-dessus et les saisies dont le type est d�compt� comme retard
	(voir la page <a href="admin_types_absences.php?action=visualiser">D�finir les types d'absence</a>).<br/>

</p>
<br/>
<p>
	<?php if (getSettingValue("abs2_heure_demi_journee") == null || getSettingValue("abs2_heure_demi_journee") == '') saveSetting("abs2_heure_demi_journee", '11:50'); ?>
	<input style="font-size:88%;" name="abs2_heure_demi_journee" value="<?php echo getSettingValue("abs2_heure_demi_journee")?>" type="text" maxlength="5" size="4"/>
	Heure de bascule de demi-journ�e pour le d�compte des demi-journ�es
</p>

<!--h2>G&eacute;rer l'acc&egrave;s des responsables d'&eacute;l&egrave;ves</h2>
<p style="font-style: italic">Vous pouvez permettre aux responsables d'acc&eacute;der aux donn&eacute;es brutes
entr&eacute;es dans Gepi par le biais du module absences.</p>
<p>
	<input type="radio" id="activerRespOk" name="activer_resp" value="y"
	<?php if (getSettingValue("active_absences_parents") == 'y') echo ' checked="checked"'; ?> />
	<label for="activerRespOk">Permettre l'acc&egrave;s aux responsables</label>
</p>
<p>
	<input type="radio" id="activerRespKo" name="activer_resp" value="n"
	<?php if (getSettingValue("active_absences_parents") == 'n') echo ' checked="checked"'; ?> />
	<label for="activerRespKo">Ne pas permettre cet acc&egrave;s</label>
</p-->
	
<br/>
<div class="centre"><input type="submit" value="Enregistrer" style="font-variant: small-caps;"/></div>

</form>

<br/><br/>
<h2>Configuration avanc�e</h2>
<blockquote>
	<a href="admin_types_absences.php?action=visualiser">D�finir les types d'absence</a><br />
	<a href="admin_motifs_absences.php?action=visualiser">D�finir les motifs des absences</a><br />
    <a href="admin_lieux_absences.php?action=visualiser">D�finir les lieux des absences</a><br />
	<a href="admin_justifications_absences.php?action=visualiser">D�finir les justifications</a><br />
	<a href="../../mod_ooo/gerer_modeles_ooo.php">G�rer ses propres mod�les de documents du module</a>
</blockquote>

<?PHP
require("../../lib/footer.inc.php");
?>
