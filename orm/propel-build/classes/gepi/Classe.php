<?php



/**
 * Skeleton subclass for representing a row from the 'classes' table.
 *
 * Classe regroupant des eleves
 *
 * You should add additional methods to this class to meet the
 * application requirements.  This class will only be generated as
 * long as it does not already exist in the output directory.
 *
 * @package    propel.generator.gepi
 */
class Classe extends BaseClasse {

	/**
	 *
	 * Retourne les emplacements de cours de l'heure temps reel. retourne une collection vide si pas pas de cours actuel
	 *
	 * @return     PropelObjectCollection EdtEmplacementCours[]
	 */
	public function getEdtEmplacementCours($v) {
        
        if ( getSettingValue("autorise_edt_tous") != 'y') {
        	return new PropelObjectCollection();
        }
        
	    // we treat '' as NULL for temporal objects because DateTime('') == DateTime('now')
	    // -- which is unexpected, to say the least.
	    //$dt = new DateTime();
	    if ($v === null || $v === '') {
		    $dt = null;
	    } elseif ($v instanceof DateTime) {
		    $dt = clone $v;
	    } else {
		    // some string/numeric value passed; we normalize that so that we can
		    // validate it.
		    try {
			    if (is_numeric($v)) { // if it's a unix timestamp
				    $dt = new DateTime('@'.$v, new DateTimeZone('UTC'));
				    // We have to explicitly specify and then change the time zone because of a
				    // DateTime bug: http://bugs.php.net/bug.php?id=43003
				    $dt->setTimeZone(new DateTimeZone(date_default_timezone_get()));
			    } else {
				    $dt = new DateTime($v);
			    }
		    } catch (Exception $x) {
			    throw new PropelException('Error parsing date/time value: ' . var_export($v, true), $x);
		    }
	    }
	    
		$query = EdtEmplacementCoursQuery::create()->filterByGroupe($this->getGroupes())
		    ->filterByIdCalendrier(0)
		    ->addOr(EdtEmplacementCoursPeer::ID_CALENDRIER, NULL);
	    
		if ($v instanceof EdtCalendrierPeriode) {
			$query->addOr(EdtEmplacementCoursPeer::ID_CALENDRIER, $v->getIdCalendrier());
		} else {
			$periodeCalendrier = EdtCalendrierPeriodePeer::retrieveEdtCalendrierPeriodeActuelle($v);
			if ($periodeCalendrier != null) {
				$query->addOr(EdtEmplacementCoursPeer::ID_CALENDRIER, $periodeCalendrier->getIdCalendrier());
			}
		}

	    $edtCoursCol = $query->find();

		require_once("helpers/EdtEmplacementCoursHelper.php");
		return EdtEmplacementCoursHelper::getColEdtEmplacementCoursActuel($edtCoursCol, $v);
	}

	/**
	 *
	 * Retourne tous les emplacements de cours pour la periode précisée du calendrier.
	 * On recupere aussi les emplacements dont la periode n'est pas definie ou vaut 0.
	 *
	 * @return PropelObjectCollection EdtEmplacementCours une collection d'emplacement de cours ordonnée chronologiquement
	 */
	public function getEdtEmplacementCourssPeriodeCalendrierActuelle($v = 'now'){
		if ( getSettingValue("autorise_edt_tous") != 'y') {
                return new PropelObjectCollection();
        }
        
		$query = EdtEmplacementCoursQuery::create()->filterByGroupe($this->getGroupes())
		    ->filterByIdCalendrier(0)
		    ->addOr(EdtEmplacementCoursPeer::ID_CALENDRIER, NULL);

	    if ($v instanceof EdtCalendrierPeriode) {
		$query->addOr(EdtEmplacementCoursPeer::ID_CALENDRIER, $v->getIdCalendrier());
	    } else {
		$periodeCalendrier = EdtCalendrierPeriodePeer::retrieveEdtCalendrierPeriodeActuelle($v);
		if ($periodeCalendrier != null) {
		       $query->addOr(EdtEmplacementCoursPeer::ID_CALENDRIER, $periodeCalendrier->getIdCalendrier());
		}
	    }

	    $edtCoursCol = $query->find();
	    require_once("helpers/EdtEmplacementCoursHelper.php");
	    EdtEmplacementCoursHelper::orderChronologically($edtCoursCol);

	    return $edtCoursCol;
	}

	
  public function getEctsGroupesByCategories() {
      // On commence par rÃ©cupÃ©rer tous les groupes
      $groupes = $this->getGroupes();
      // Ensuite, il nous faut les catÃ©gories.
      $categories = array();
      $c = new Criteria();
      $c->add(JCategoriesMatieresClassesPeer::CLASSE_ID,$this->getId());
      $c->addAscendingOrderByColumn(JCategoriesMatieresClassesPeer::PRIORITY);
      foreach(JCategoriesMatieresClassesPeer::doSelect($c) as $j) {
          $cat = $j->getCategorieMatiere();
          $categories[$cat->getId()] = array(0 => $cat, 1 => array());
      }
      // Maintenant, on mets tout Ã§a ensemble
      foreach($groupes as $groupe) {
          if ($groupe->allowsEctsCredits($this->getId())) {
              $cat = $groupe->getCategorieMatiere($this->getId());
              $categories[$cat->getId()][1][$groupe->getId()] = $groupe;
          }
      }

      foreach($categories as $cat) {
          if (count($cat[1]) == 0) {
              $id = $cat[0]->getId();
              unset($categories[$id]);
          }
      }

      // On renvoie un table multi-dimensionnel, qui contient les catÃ©gories
      // dans le bon ordre, et les groupes sous chaque catÃ©gorie.
      return $categories;
  }



	/**
	 *
	 * Renvoi sous forme d'une collection la liste des eleves d'une classe. 
	 * Si la periode de note est null, cela renvoi les eleves de la periode actuelle, ou tous les eleves si il n'y a aucune periode actuelle
	 *
	 * @return     PropelObjectCollection Eleves[]
	 *
	 */
	public function getEleves($periode = NULL) {
		if ($periode == NULL) {
		    if ($this->getPeriodeNoteOuverte() != null) {
			$periode = $this->getPeriodeNoteOuverte()->getNumPeriode();
		    }
		}
		$query = EleveQuery::create();
		if ($periode != NULL) {
		    $query->useJEleveClasseQuery()->filterByClasse($this)->filterByPeriode($periode)->endUse();
		} else {
		    $query->useJEleveClasseQuery()->filterByClasse($this)->endUse();
 		}
		$query->orderByNom()->orderByPrenom()->distinct();
		return $query->find();
	}

	public function getElevesByProfesseurPrincipal($login_prof) {
		$eleves = new PropelObjectCollection();
		$criteria = new Criteria();
		$criteria->add(JEleveProfesseurPrincipalPeer::PROFESSEUR,$login_prof);
		foreach($this->getJEleveProfesseurPrincipalsJoinEleve($criteria) as $ref) {
		    if ($ref->getEleve() != null) {
			$eleves->add($ref->getEleve());
		    }
		}
		return $eleves;
	}

	/**
	 *
	 * Ajoute un eleve a une classe et sauve la relation. Si la periode de note est nulle, cela ajoute l'eleve la periode actuelle
	 *
	 * @param      PropelPDO $con (optional) The PropelPDO connection to use.
	 */
	public function addEleve(Eleve $eleve, $num_periode_notes = null) {
		if ($eleve->getId() == null) {
			throw new PropelException("Eleve id ne doit pas etre null");
		}
		if ($num_periode_notes == null) {
		    $periode = $this->getPeriodeNoteOuverte();
		    if ($periode != null) {
			$num_periode_notes = $periode->getNumPeriode();
		    }
		}
		$jEleveClasse = new JEleveClasse();
		$jEleveClasse->setEleve($eleve);
		$jEleveClasse->setPeriode($num_periode_notes);
		$this->addJEleveClasse($jEleveClasse);
		$jEleveClasse->save();
		$eleve->clearPeriodeNotes();
	}

 	/**
	 * Retourne la periode de note actuellement ouverte pour une classe donnee.
	 *
	 * @return     PeriodeNote $periode la periode actuellement ouverte
	 */
	public function getPeriodeNoteOuverte() {
		$count_verrouiller_n = 0;
		$count_verrouiller_p = 0;
		foreach ($this->getPeriodeNotes() as $periode) {
		    if ($periode->getVerouiller() == 'N') {
			$count_verrouiller_n = $count_verrouiller_n + 1;
			if (!isset($periode_verrouiller_n)
				|| $periode_verrouiller_n == null
				|| $periode_verrouiller_n->getNumPeriode() > $periode->getNumPeriode())
			$periode_verrouiller_n = $periode;
		    }
		    if ($periode->getVerouiller() == 'P') {
			$count_verrouiller_p = $count_verrouiller_p + 1;
			if (!isset($periode_verrouiller_p)
				||$periode_verrouiller_p == null
				|| $periode_verrouiller_p->getNumPeriode() > $periode->getNumPeriode())
			$periode_verrouiller_p = $periode;
		    }
		}

		if ($count_verrouiller_n == 1) {
		    //si on a une seule periode ouverte alors c'est la periode actuelle
		    return $periode_verrouiller_n;
		} elseif ($count_verrouiller_n == 0 && $count_verrouiller_p == 1) {
		    //si on a une seule periode partiellement ouverte et aucune ouverte alors c'est la periode actuelle
		    return $periode_verrouiller_p;
		}

		//on verifie si il y a une periode du calendrier avec une periode de note precisee
		$calendrier_periode = EdtCalendrierPeriodePeer::retrieveEdtCalendrierPeriodeActuelle();
		if ($calendrier_periode != null && $calendrier_periode->getNumeroPeriode() != null && $calendrier_periode->getNumeroPeriode() != 0) {
		    $criteria = new Criteria();
		    $criteria->add(PeriodeNotePeer::NUM_PERIODE,$calendrier_periode->getNumeroPeriode());
		    $periodes = $this->getPeriodeNotes($criteria);
		    return $periodes->getFirst();
		}

		//on va prendre la periode de numero la plus petite non verrouillee
		if (isset($periode_verrouiller_n) && $periode_verrouiller_n != null) {
		    return $periode_verrouiller_n;
		} elseif (isset($periode_verrouiller_p) && $periode_verrouiller_p != null) {
		    return $periode_verrouiller_p;
		}
		return null;
	}


	/**
	 * Retourne la periode de note correspondante à la date donnée en paramètre.
         * On regarde proritairement les dates de fin des périodes de notes,
         * puis les renseignements de l'edt.
         * Si aucune période n'est trouvée on retourne la dernière période ouverte pour l'ordre de nommage,
         * Si toujours aucune période n'est trouvée on renvoi null
	 *
	 * @return     PeriodeNote $periode la periode de la date précisée, ou null si non trouvé
	 */
	public function getPeriodeNote($v = 'now') {
            // we treat '' as NULL for temporal objects because DateTime('') == DateTime('now')
	    // -- which is unexpected, to say the least.
	    //$dt = new DateTime();
	    if ($v === null || $v === '') {
		    $dt = null;
	    } elseif ($v instanceof DateTime) {
		    $dt = clone $v;
	    } else {
		    // some string/numeric value passed; we normalize that so that we can
		    // validate it.
		    try {
			    if (is_numeric($v)) { // if it's a unix timestamp
				    $dt = new DateTime('@'.$v, new DateTimeZone('UTC'));
				    // We have to explicitly specify and then change the time zone because of a
				    // DateTime bug: http://bugs.php.net/bug.php?id=43003
				    $dt->setTimeZone(new DateTimeZone(date_default_timezone_get()));
			    } else {
				    $dt = new DateTime($v);
			    }
		    } catch (Exception $x) {
			    throw new PropelException('Error parsing date/time value: ' . var_export($v, true), $x);
		    }
	    }

		foreach ($this->getPeriodeNotes() as $periode) {
		    if ($periode->getDateDebut('U') <= $dt->format('U')
				    && $periode->getDateFin(null) != null && $periode->getDateFin('U') > $dt->format('U')) {
                        return $periode;
                    }
		}

                //si on est là on a trouvé aucune période renseignée qui convienne. On va regarder l'edt
		//on verifie si il y a une periode du calendrier avec une periode de note precisee
		$calendrier_periode = EdtCalendrierPeriodePeer::retrieveEdtCalendrierPeriodeActuelle($dt);
		if ($calendrier_periode != null && $calendrier_periode->getNumeroPeriode() != null && $calendrier_periode->getNumeroPeriode() != 0) {
		    $criteria = new Criteria();
		    $criteria->add(PeriodeNotePeer::NUM_PERIODE,$calendrier_periode->getNumeroPeriode());
		    $periodes = $this->getPeriodeNotes($criteria);
		    return $periodes->getFirst();
		}

                //si on est là on a toujours trouvé aucune période. On renvoi la première période qui peut convenir
                //et qui n'est pas encore achevée
		foreach ($this->getPeriodeNotes() as $periode) {
                    if ($periode->getDateDebut('U') <= $dt->format('U')
                        && ($periode->getDateFin(null) === null || $periode->getDateFin('U') > $dt->format('U'))) {
                        return $periode;
                    }
		}

                //si on a toujours aucune période, on renvoi la dernière période dans l'ordre des numéro, ou null si on ne trouve rien
                $query = PeriodeNoteQuery::create();
                return $query->filterByIdClasse($this->getId())->orderByNumPeriode(Criteria::DESC)->findOne();
	}

 	/**
	 * Retourne la collection de periode de note privée a des fins d'optimisation
	 *
	 * @return     PropelObjectCollection
	 */
	public function getProtectedCollPeriodeNote() {
	    return $this->collPeriodeNotes;
	}


        	/**
	 *
	 * Renvoi une collection des mefs des eleves de ce groupe. Un seul mef de chaque type sera retourné.
	 *
	 * @periode integer numero de la periode
	 * @return     PropelObjectCollection Eleves[]
	 *
	 */
	public function getMefs($periode = null) {
            $mef_collection = new PropelObjectCollection();
            foreach($this->getEleves($periode) as $eleve) {
                $mef_collection->add($eleve->getMef());
            }
            return $mef_collection;
        }

} // Classe
