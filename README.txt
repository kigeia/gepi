GEPI-1.5.4
==============

Eric ABGRALL <eric.abgrall@free.fr>
Thomas BELLIARD <thomas.belliard@free.fr>
Didier BLANQUI <didier.blanqui@ac-toulouse.fr>
Stephane BOIREAU <stephane.boireau@ac-rouen.fr>
Regis BOUGUIN <regis.bouguin@laposte.net>
Laurent DELINEAU <laurent.delineau@ac-poitiers.fr>
Jerome ETHEVE <jerome.etheve@gmail.com>
Pascal FAUTRERO <admin.paulbert@gmail.com>
Josselin JACQUARD <josselin.jacquard@gmail.com>
Julien JOCAL <collegerb@free.fr>
Eric LEBRUN <eric.lebrun@ac-poitiers.fr>

http://gepi.mutualibre.org/

GEPI (Gestion des El�ves Par Internet) est une application d�velopp�e en PHP/MySQL/HTML
dont les fonctions s'articulent autour d'un objectif : permettre la gestion et surtout le suivi des r�sultats scolaires
des �l�ves, et tout ce qui y est attach�, par une interface Web. Cela signifie, entre autre,

* la saisie des notes via un module "carnet de notes",
* leur consultation,
* la saisie des appr�ciations des professeurs,
* l'�dition des bulletins scolaires,
* un module "cahier de texte",
* un outil trombinoscope
* un module de saisie des absences par les professeurs
* un module emploi du temps / calendrier

1. Installation
2. Licence
3. Remarques concernant la s�curit�
4. D�veloppements en cours
5. Documentation


1. Installation
=======================================

Pour obtenir une description compl�te de la proc�dure d'installation,
veuillez vous reporter au fichier "INSTALL.txt".

Pour une installation simplifi�e, d�compressez simplement cette archive sur un
serveur, et indiquez l'adresse o� se trouvent les fichiers extraits dans un navigateur (ex: http://www.monsite.fr/gepi).

* Pr�alables pour l'installation automatis�e :
- disposer d'un espace FTP sur un serveur avec PHP 5 ou sup�rieur, pour y transf�rer les fichiers
- disposer d'une base de donn�es MySQL (adresse du serveur MySQL, login, mot
  de passe)


2. Licence
=======================================

GEPI est publi� sous les termes de la GNU General Public Licence, dont le
contenu est disponible dans le fichier "COPYING.txt", en anglais.
GEPI est gratuit, vous pouvez le copier, le distribuer, et le modifier, �
condition que chaque partie de GEPI r�utilis�e ou modifi�e reste sous licence
GNU GPL.
Par ailleurs et dans un soucis d'efficacit�, merci de rester en contact avec
l'�quipe de d�veloppement de GEPI pour �ventuellement int�grer vos
contributions � une distribution ult�rieure.

Enfin, GEPI est livr� en l'�tat sans aucune garantie. Les auteurs de cet outil
ne pourront en aucun cas �tre tenus pour responsables d'�ventuels bugs.


3. Remarques concernant la s�curit�
=======================================

La s�curisation de GEPI est un point crucial, �tant donn� la sensibilit� des
donn�es enregistr�es. Malheureusement la s�curisation de GEPI est d�pendante
de celle du serveur. Nous vous recommandons d'utiliser un serveur Apache sous
Linux, en utilisant le protocole https (transferts de donn�es crypt�es), et en
veillant � toujours utiliser les derni�res versions des logiciels impliqu�s
(notamment Apache et PHP). GEPI n'a pas encore �t� test� sur d'autres
serveurs.

L'EQUIPE DE DEVELOPPEMENT DE GEPI NE SAURAIT EN AUCUN CAS ETRE TENUE
POUR RESPONSABLE EN CAS D'INTRUSION EXTERIEURE LIEE A UNE FAIBLESSE DE GEPI OU
DE SON SUPPORT SERVEUR.

Abonnez-vous � la liste de diffusion 'gepi-news' pour �tre tenu inform� des
mises � jours en mati�re de s�curit�, et � la liste 'gepi-users' pour participer
aux discussions relatives � l'utilisation et au d�veloppement de Gepi.


4. D�veloppements en cours
=======================================

Les d�veloppeurs de Gepi travaillent en fonction des besoins de leurs �tablissements
respectifs. N'h�sitez pas � leur sugg�rer des fonctionnalit�s, par le biais
de la liste de diffusion des utilisateurs.


5. Documentation
=======================================

La documentation de Gepi se trouve � l'adresse suivante :
http://www.sylogix.org/projects/gepi/wiki