#!/usr/bin/php -q
<?php

// Initialisations, pour avoir l'environnement disponible.
require_once ("../lib/initialisations.inc.php");
require_once ("./update_functions.php");

// Initialisation des options
$force = false; // Force une application de tous les scripts de mise � jour
$start_from = $gepiSettings['version']; // Permet d'appliquer les mises � jour � partir d'une version donn�e

$script_error=false;

if ($argc != 2) {
    $script_error = true;
} else {
    // Premier argument (obligatoire, pour �viter les accidents)
    if (isset($argv[1]) && in_array($argv[1], array('1.4.4','1.5.0','1.5.1','1.5.2','1.5.3','1.5.3.1','defaut','forcer'))) {
        if ($argv[1] == 'forcer') {
            $force = true;
        } elseif($argv[1] == 'defaut'){
            $start_from = $gepiSettings['version'];
            // Si la version actuelle est un trunk, on force une mise � jour compl�te.
            if ($start_from == 'trunk') $force = true;
        }
        $start_from = $argv[1];
    } else {
        $script_error = true;
    }
}

if ($script_error || in_array($argv[1], array('--help', '-help', '-h', '-?'))) {
?>

Ce script requiert des options.

Utilisation :
<?php echo $argv[0]; ?> <version>

<version> peut prendre trois valeurs diff�rentes :
    
    - defaut : indique au script qu'il doit d�terminer lui-m�me la version actuelle
               de votre Gepi (� titre indicatif, pour votre installation : <?php echo $gepiSettings['version'];?>)

    - forcer : le script appliquera la totalit� des mises � jour disponible.
               Cette op�ration est en principe sans risque.
               
    - un num�ro de version (ex: 1.5.1) : en sp�cifiant manuellement un num�ro de
               version, le script d�marrera explicitement la mise � jour � partir
               de cette version. Soyez s�r de vous si vous sp�cifiez une version
               manuellement !

En sp�cifiant seulement --help, -help, -h, et -?, vous pouvez afficher � nouveau ce
message d'aide.

Exemples d'utilisation :

./maj.sh defaut
    Lance une mise � jour avec calcul automatique de la version actuelle de votre
    Gepi.

./maj.sh forcer
    Force une mise � jour compl�te, depuis le script le plus ancien disponible
    avec votre installation de Gepi.

./maj.sh 1.5.0
    Applique les mises � jour depuis la version 1.5.0.


<?php
} else {
// Si on arrive ici, c'est qu'on a les bons arguments, et qu'on peut appliquer
// la mise � jour.

    // Num�ro de version effective
    $version_old = $gepiSettings['version'];
    // Num�ro de version RC effective
    $versionRc_old = $gepiSettings['versionRc'];
    // Num�ro de version Beta effective
    $versionBeta_old = $gepiSettings['versionBeta'];

    $rc_old = '';
    if ($versionRc_old != '') {
            $rc_old = "-RC" . $versionRc_old;
    }
    $rc = '';
    if ($gepiRcVersion != '') {
            $rc = "-RC" . $gepiRcVersion;
    }

    $beta_old = '';
    if ($versionBeta_old != '') {
            $beta_old = "-beta" . $versionBeta_old;
    }
    $beta = '';
    if ($gepiBetaVersion != '') {
            $beta = "-beta" . $gepiBetaVersion;
    }


    $pb_maj = '';
    $result = '';
    $result_inter = '';

    // Remise � z�ro de la table des droits d'acc�s
    require './updates/access_rights.inc.php';


    if ($force || $start_from == '1.4.4') {
        require './updates/144_to_150.inc.php';
    }


    if ($force || $start_from == '1.5.0') {
        require './updates/150_to_151.inc.php';
    }


    if ($force || $start_from == '1.5.1') {
        require './updates/151_to_152.inc.php';
    }


    if ($force || $start_from == '1.5.2') {
        require './updates/152_to_153.inc.php';
    }

    if ($force || $start_from == '1.5.3') {
        require './updates/153_to_1531.inc.php';
    }

    if ($force || $start_from == '1.5.3.1') {
        require './updates/1531_to_154.inc.php';
    }

// Nettoyage pour envoyer le r�sultat dans la console
    $result = str_replace('<br />',"\n",$result);
    $result = str_replace('<br/>',"\n",$result);
    $result = str_replace('&nbsp;','',$result);
    $result = preg_replace('/<font\b[^>]*>/','',$result);
    $result = preg_replace('/<\/font>/','',$result);
    $result = preg_replace('/<b>/','',$result);
    $result = preg_replace('/<\/b>/','',$result);
    echo $result;

    // Mise � jour du num�ro de version
    saveSetting("version", $gepiVersion);
    saveSetting("versionRc", $gepiRcVersion);
    saveSetting("versionBeta", $gepiBetaVersion);
    saveSetting("pb_maj", $pb_maj);

}
?>
