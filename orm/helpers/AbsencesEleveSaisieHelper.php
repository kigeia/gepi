<?php
/**
 *
 *
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
 * Classe de helpers sur les saisies
 */
class AbsencesEleveSaisieHelper {

    /**
     * Compte les demi-journees saisies. Les saisies doivent ètre triées par ordre de début.
     * Cette méthode ne travaille que sur les dates, et prend en compte les fermeture de l'établissement
     *
     * @param PropelObjectCollection $abs_saisie_col collection d'objets AbsenceEleveSaisie
     *
     * @return PropelCollection une collection de date time par demi journee comptee (un datetime pour le matin et un datetime pour l'apres midi
     */

    public static function compte_demi_journee($abs_saisie_col, $date_debut_iteration = null, $date_fin_iteration = null) {
        if ($abs_saisie_col->isEmpty()) {
            return new PropelCollection();
        }
        
        $abs_saisie_col->uasort(array("AbsencesEleveSaisieHelper", "compare_debut_absence"));
        
        //on récupère l'heure de demi-journée
        $heure_demi_journee = 11;//11:50 par défaut si rien n'est précisé dans les settings
        $minute_demi_journee = 50;
        if (getSettingValue("abs2_heure_demi_journee") != null) {
            try {
                $dt_demi_journee = new DateTime(getSettingValue("abs2_heure_demi_journee"));
                $heure_demi_journee = $dt_demi_journee->format('H');
                $minute_demi_journee = $dt_demi_journee->format('i');
            } catch (Exception $x) {
            }
        }
         
        //on va regarder la date du début pour notre algorithme
        if ($date_debut_iteration == null) {
            $date_debut_iteration = $abs_saisie_col->getFirst()->getDebutAbs(null);
        }
        if ($date_debut_iteration->format('Hi') < $heure_demi_journee.$minute_demi_journee) {
            $date_debut_iteration->setTime(0, 0, 0);
        } else {
            $date_debut_iteration->setTime(12, 0, 0);
        }
              
        //on va regarder la date du fin pour notre algorithme
        if ($date_fin_iteration == null) {
            foreach ($abs_saisie_col as $saisie) {
                if ($date_fin_iteration == null || $saisie->getFinAbs('U') > $date_fin_iteration->format('U')) {
                    $date_fin_iteration = $saisie->getFinAbs(null);
                }
            }
        }
        if ($date_fin_iteration->format('Hi') < $heure_demi_journee.$minute_demi_journee) {
            $date_fin_iteration->setTime(12, 0, 0);
        } else {
            $date_fin_iteration->setTime(23, 59, 59);
            $date_fin_iteration->modify("+1 second");
        }
        $date_fin_iteration->modify("+2 hours");//on ajout deux heures pour prendre en compte un décalage dans la date de compteur (+1h35) dans l'algorithme plus bas
        

        $result = new PropelCollection();
        $date_compteur = clone $date_debut_iteration;
        $horaire_tab = EdtHorairesEtablissementPeer::retrieveAllEdtHorairesEtablissementArrayCopy();
        require_once(dirname(__FILE__)."/EdtHelper.php");
        foreach($abs_saisie_col as $saisie) {
            if ($date_compteur->format('U') < $saisie->getDebutAbs('U')) {
                $date_compteur = clone $saisie->getDebutAbs(null);
            }
            if ($date_compteur >= $date_fin_iteration) {
                break;
            }
            
            while ($date_compteur <= $saisie->getFinAbs(null) && $date_compteur < $date_fin_iteration) {
                //est-ce un jour de la semaine ouvert ?
                if (!EdtHelper::isJourneeOuverte($date_compteur)) {
                    //etab fermé on va passer au lendemain
                    $date_compteur->setTime(23, 59, 59);
                    $date_compteur->modify("+2 hours");
                    continue;
                } elseif (!EdtHelper::isHoraireOuvert($date_compteur)) {
                    $horaire = $horaire_tab[EdtHelper::$semaine_declaration[$date_compteur->format("w")]];
                    if ($date_compteur->format('Hi') < $horaire->getOuvertureHoraireEtablissement('Hi')) {
                        //c'est le matin, on règle sur l'heure d'ouverture
                        $date_compteur->setTime($horaire->getOuvertureHoraireEtablissement('H'), $horaire->getOuvertureHoraireEtablissement('i'));
                    } else {
                        //on est apres la fermeture, on va passer au lendemain
                        $date_compteur->setTime(23, 59, 59);
                        $date_compteur->modify("+2 hours");
                    }
                    continue;
                } elseif ($date_compteur < $saisie->getDebutAbs(null) && !EdtHelper::isHoraireOuvert($saisie->getDebutAbs(null))) {
                    $date_compteur->modify("+19 minutes");
                    continue;
                }

                if ($date_compteur->format('Hi') < $heure_demi_journee.$minute_demi_journee) {
                    $date_compteur->setTime(0, 0, 0);
                } else {
                    $date_compteur->setTime(12, 0, 0);
                }
                $date_compteur_suivante = clone $date_compteur;
                $date_compteur_suivante->modify("+15 hours");//en ajoutant 15 heure on est sur de passer a la demi-journee suivante
                if ($date_compteur_suivante->format('H') < 12) {
                    $date_compteur_suivante->setTime(0, 0, 0);
                } else {
                    $date_compteur_suivante->setTime($heure_demi_journee, $minute_demi_journee, 0);
                }
                
                if ($saisie->getDebutAbs(null) < $date_compteur_suivante && $saisie->getFinAbs(null) > $date_compteur) {
                    $result->append(clone $date_compteur);
                    //on ajoute 1h35
                    //pour eviter le cas ou on a une saisie par exemple sur 11h45 -> 13h et de la compter comme deux demi-journees
                    $date_compteur_suivante->modify("+1 hour");
                    $date_compteur_suivante->modify("+45 minutes");
                }
                
                $date_compteur = $date_compteur_suivante;
                $saisie->clearAllReferences();
            }
        }
        return $result;
    }
    
    public static function compare_debut_absence(AbsenceEleveSaisie $arg1, AbsenceEleveSaisie $arg2) {
        if ($arg1 === null && $arg2 != null) return 1;
        if ($arg1 === null && $arg2 === null) return 0;
        if ($arg1 != null && $arg2 === null) return -1;
        if ($arg1->getDebutAbs() > $arg2->getDebutAbs()) return 1;
        if ($arg1->getDebutAbs() == $arg2->getDebutAbs()) return 0;
        if ($arg1->getDebutAbs() < $arg2->getDebutAbs()) return -1;
    }
}

?>