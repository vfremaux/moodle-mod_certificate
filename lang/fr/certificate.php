<?php

// This file is part of the Certificate module for Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for the certificate module
 *
 * @package    mod
 * @subpackage certificate
 * @copyright  Mark Nelson <markn@moodle.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

// Capabilities
$string['certificate:view'] = 'Voir l\'attestation';
$string['certificate:manage'] = 'Gérer l\attestation';
$string['certificate:apply'] = 'Etre attesté';
$string['certificate:printteacher'] = 'Est mentionné sur l\'atestation comme formateur';
$string['certificate:isauthority'] = 'Est autorité de certification';
$string['certificate:getown'] = 'Retirer son propre certificat';
$string['certificate:apply'] = 'Peut obtenir une attestation';
$string['certificate:deletecertificates'] = 'Peut détruire des certificats';

$string['certification'] = 'Certification';
$string['certificationmatchednotdeliverable'] = 'Vous avez validé les attendus de ce module pour pouvoir activer votre certificat. Cependant, vous ne pouvez pas retirer par vous même le certificat qui doit vous être remis par votre responsable de formation.';

$string['addlinklabel'] = 'Ajouter un nouveau lien vers une activité';
$string['addlinktitle'] = 'Cliquer pour ajouter un nouveau lien vers une activité';
$string['areaintro'] = 'Description';
$string['awarded'] = 'Decerné à ';
$string['awardedto'] = 'Décerné à ';
$string['back'] = 'Revenir';
$string['border'] = 'Bordure';
$string['borderblack'] = 'Noir';
$string['borderblue'] = 'Bleu';
$string['borderbrown'] = 'Marron';
$string['bordercolor'] = 'Couleur de bordure';
$string['bordergreen'] = 'Vert';
$string['borderlines'] = 'Lignes de bordure';
$string['borderstyle'] = 'Image de bordure';
$string['certificate'] = 'Vérification du code de l\'attestation :';
$string['certificatecaption'] = 'Titre de l\'attestation';
$string['certificatename'] = 'Nom de l\'attestation';
$string['certificatereport'] = 'Rapport des attestations';
$string['certificateremoved'] = 'Attestation supprimée';
$string['certificatesfor'] = 'Attestation pour';
$string['certificatetype'] = 'Type d\'attestation';
$string['certificateverification'] = 'Vérification de certificat';
$string['code'] = 'Code';
$string['completiondate'] = 'Achèvement du cours';
$string['course'] = 'pour le cours';
$string['coursechaining'] = 'Chaînage de cours';
$string['coursedependencies'] = 'Cours dépendants';
$string['courseenddate'] = 'Date de fin de formation (doit être renseignée!)';
$string['coursegrade'] = 'Note des cours';
$string['coursename'] = 'Nom du Cours';
$string['coursetime'] = 'Crédit horaire de formation requis';
$string['coursetimedependency'] = 'Temps minimum requis dans le cours';
$string['coursetimereq'] = 'Minutes minimum dans le cours';
$string['credithours'] = 'Crédit d\'heures';
$string['customtext'] = 'Texte personnalisé';
$string['date'] = 'le';
$string['datefmt'] = 'Format de date';
$string['datehelp'] = 'Date';
$string['deletissuedcertificates'] = 'Supprimer les attestations délivrées';
$string['delivery'] = 'Délivrance';
$string['designoptions'] = 'Mise en forme';
$string['download'] = 'Forcer le téléchargement';
$string['emailcertificate'] = 'Mél (doit être sauvegardé!)';
$string['emailothers'] = 'Autres destinataires';
$string['emailstudenttext'] = 'Votre attestation pour le cours {$a->course} est joint en pièce attachée.';
$string['emailteachers'] = 'Envoyer un mél aux formateurs';
$string['entercode'] = 'Entrer le code de l\'attestation à vérifier :';
$string['errornocapabilitytodelete'] = 'vous n\'avez pas les capacités pour détruire des certificats';
$string['errorinvalidinstance'] = 'Erreur : cette instance de certificat n\'existe pas';
$string['expiredon'] = 'Expiré le';
$string['getcertificate'] = 'Obtenez votre attestation';
$string['previewcertificate'] = 'Prévisualiser l\'attestation';
$string['grade'] = 'avec la note';
$string['gradedate'] = 'Date des évaluations';
$string['gradefmt'] = 'Format de note';
$string['gradeletter'] = 'Barème lettre';
$string['gradepercent'] = 'Barème en pourcentages';
$string['gradepoints'] = 'Barèmes par points';
$string['invalidcode'] = 'Code invalide';
$string['state'] = 'Statut';
$string['viewall'] = 'Voir tout';
$string['viewless'] = 'En voir moins';
$string['viewalladvice'] = 'Attention ! de grands groupes d\'apprenants peuvent générer une très forte charge sur le serveur et votre navigateur';
$string['withsel'] = 'Avec la sélection : ';
$string['generateselection'] = 'Générer les attestations ';
$string['destroyselection'] = 'Détruire les attestations ';
$string['releaseselection'] = 'Valider les attestations ';
$string['generate'] = 'Générer';
$string['imagetype'] = 'Image Type';
$string['incompletemessage'] = 'Afin de télécharger l\'attestation, vous devez d\'abord avoir terminé toutes les activités requises. Veuillez retourner dansvos parcours pour terminer les activités pédagogiques proposées.';
$string['intro'] = 'Introduction';
$string['issued'] = 'Validé';
$string['issueddate'] = 'Date de validation';
$string['issueoptions'] = 'Eléments';
$string['landscape'] = 'Paysage';
$string['lastviewed'] = 'Vous avez visualisé cette attestation le :';
$string['letter'] = 'Letter';
$string['linkedactivity'] = 'Activités liées';
$string['linkedcourse'] = 'Cours';
$string['lockingoptions'] = 'Conditions d\'acquisition';
$string['modulename'] = 'Attestation';
$string['modulenameplural'] = 'Attestations';
$string['mycertificates'] = 'Mes attestations';
$string['nocertifiables'] = 'Aucun utilisateur à attester';
$string['nocertificates'] = 'Aucune attestation';
$string['nocertificatesissued'] = 'Aucune attestation n\'a été délivrée';
$string['nocertificatesreceived'] = 'n\'a jamais reçu d\'attestation.';
$string['nofileselected'] = 'Vous devez choisir un fichier !';
$string['nogrades'] = 'Aucune évaluation disponible';
$string['notapplicable'] = 'N/A';
$string['notfound'] = 'Le numéro d\'attestation n\'a pas pu être validé.';
$string['notissued'] = 'Non validé';
$string['notissuedyet'] = 'Non encore validé';
$string['notreceived'] = 'Vous n\'avez pas reçu cette attestation';
$string['needsmorework'] = 'Ce certificat nécessite plus de travail';
$string['openbrowser'] = 'ouvrir dans une nouvelle fenêtre';
$string['opendownload'] = 'Cliquez sur le bouton ci-dessous pour sauvegarder cette attestation sur votre ordinateur.';
$string['openemail'] = 'Cliquez sur le bouton ci-dessous pour recevoir votre attestation par mél.';
$string['openwindow'] = 'Cliquez le bouton ci-dessous pour voir votre attestation dans un nouveau navigateur.';
$string['or'] = 'Or';
$string['orientation'] = 'Orientation';
$string['pluginadministration'] = 'Administration de l\'attestation';
$string['pluginname'] = 'Attestation';
$string['portrait'] = 'Portrait';
$string['printdate'] = 'Imprimer la date';
$string['printerfriendly'] = 'Version imprimable';
$string['printgrade'] = 'Imprimer les évaluations';
$string['printhours'] = 'Imprimer le crédit d\'heures';
$string['printnumber'] = 'Print Code';
$string['printoutcome'] = 'Imprimer l\'objectif atteint';
$string['printseal'] = 'Image sceau ou logo';
$string['printsignature'] = 'Image de signature';
$string['printteacher'] = 'Imprimer le nom du formateur';
$string['printwmark'] = 'Filigrane';
$string['receivedcerts'] = 'Certificats reçus';
$string['receiveddate'] = 'Date de réception';
$string['removecert'] = 'Les attestations ont été détruites';
$string['report'] = 'Rapport';
$string['reportcert'] = 'Rapports sur les attestations';
$string['requiredtimenotmet'] = 'Vous devez avoir passé au moins {$a->requiredtime} minutes dans ce cours avant de pouvoir retirer votre attestations.';
$string['requiredtimenotvalid'] = 'Le temps passé doit être une grandeur supérieure à 0';
$string['reviewcertificate'] = 'Revoir votre attestation';
$string['savecert'] = 'Sauvegarder l\'attestation';
$string['seal'] = 'Sceau';
$string['sigline'] = 'ligne';
$string['signature'] = 'Signature';
$string['statement'] = 'a achevé le cours';
$string['summaryofattempts'] = 'Liste des attestations délivrées';
$string['textoptions'] = 'Options de texte';
$string['title'] = 'ATTESTATION DE PARTICIPATION';
$string['to'] = 'Décernée à ';
$string['tryothercode'] = 'Essayer un autre code';
$string['typeA4_embedded'] = 'A4 avec polices';
$string['typeA4_non_embedded'] = 'A4 sans polices';
$string['typeletter_embedded'] = 'Letter US avec polices';
$string['typeletter_non_embedded'] = 'Letter US sans polices';
$string['unsupportedfiletype'] = 'Le fichier doit être une image jpg ou png';
$string['uploadimage'] = 'Télécharger une image';
$string['uploadimagedesc'] = 'Ce bouton vous amène à un autre écran où vous pouvez téléverser une image.';
$string['userdateformat'] = 'Format de date de l\'utilisateur';
$string['validate'] = 'Vérifier';
$string['verifycertificate'] = 'Vérifier l\'attestation';
$string['viewcertificateviews'] = 'Voir les {$a} attestations délivrées';
$string['viewed'] = 'Vous avez reçu cette attestation le :';
$string['viewtranscript'] = 'Voir les attestations';
$string['watermark'] = 'Filigrane';
$string['generateall'] = 'Générer les {$a} certificats disponibles';
$string['totalcount'] = 'Utilisateurs concernés';
$string['yetcertified'] = 'Générés';
$string['yetcertifiable'] = 'Prêts à générer';
$string['notyetcertifiable'] = 'Non prêt';
$string['summary'] = 'Résumé';
$string['validity'] = 'Validité';
$string['validitytime'] = 'Temps de validité';
$string['validuntil'] = 'Valide jusque ';
$string['definitive'] = 'Valide (définitif)';
$string['certificateverifiedstate'] = 'Le code de certificat que vous avez demandé est reconnu et correspond à l\'enregistrment ci-dessous :';
$string['certificatefile'] = 'Attestation (Document) ';
$string['certificatefilenoaccess'] = 'Vous devez avoir un compte valide et être connecté pour accéder à cette information.';

$string['unlimited'] = "Illimité";
$string['oneday'] = "Un jour";
$string['oneweek'] = "Une semaine";
$string['onemonth'] = "Un mois";
$string['threemonths'] = "Trois mois";
$string['sixmonths'] = "Six mois";
$string['oneyear'] = "Un an";
$string['twoyears'] = "Deux ans";
$string['threeyears'] = "Trois ans";
$string['fiveyears'] = "Cinq ans";
$string['tenyears'] = "Dix ans";

// Help strings

$string['validitytime_help'] = 'L\'attestation sera déclarée comme invalide à la vérification après ce délai à compter de sa date d\'émission.';

$string['coursetimereq_help'] = 'Entrez le temps minimum en minutes, que le candidat doit passer connecté à ce cours avant de délivrer l\'attestation.';

$string['datefmt_help'] = 'Choisir un format de date pour l\'impression. Vous pouvez aussi demander à ce que la date soit imprimée au format standard correspondant à la langue du candidat.';

$string['emailothers_help'] = 'Entrez les adresses de courriel, séparées par des virgules, des personnes qui doivent être alertée de la délivrance des attestations.';

$string['borderstyle_help'] = 'L\'image de contour permet de choisir un fichier image pour générer la bordure de l\'attestation dans le répertoire certificate/pix/borders. Vous pouvez aussi choisir de ne pas générer de bordure.';

$string['bordercolor_help'] = 'Comme les contours en image peuvent accroître substanciellement la taille des fichiers pdf file, vous pouvez également choisir une option de traçage de lignes de couleur (assurez vous dans ce cas que le choix de l\'option "contour" est bien sur "aucun contour"). L\'option de lignes de bordure imprimera trois lignes de différentes largeur dans la couleur choisie.';

$string['printwmark_help'] = 'Un fichier filigrane peut être imprimé en fond de l\'attestation. Un filigrane est une image dont le contraste a été poussé pour être très claire. Il peut s\'agir d\'un sigle, écusson ou tout autre image pouvant servir de fond.';

$string['printoutcome_help'] = 'Vous pouvez choisir d\'afficher une mention d\'objectif.';

$string['printdate_help'] = 'Ceci définit la date qui sera imprimée, si l\'option d\'impression de date est activée. Si la date de fin de cours est choisie, mais le candidat n\'a pas terminé tous les achèvements du cours lorsque l\'attesation est émise, alors la date d\'émission de l\'attestation sera utilisée. Vous pouvez aussi choisir une date à laquelle une des activités a été évaluée. Si une attestation est émise avant que cette activité ne soit évaluée, alors c\'est la date d\'émission qui sera utilisée.';

$string['printhours_help'] = 'Entrez le nombre d\'heures créditées de formation à afficher sur l\'attestation. Ce nombre d\'heures est une mention manuelle car il ne correspond pas nécessairement au temps réellement passé par le candidat.';

$string['printgrade_help'] = 'Vous pouvez choisir n\'importe quel élément de note du carnet de note pour l\'imprimer comme score de l\'attestation. Les éléments d\'évaluation sont affichés dans l\'ordre où ils sont définis dans le carnet de notes. Choisissez le format d\'affichage de la note ci-dessous.';

$string['printnumber_help'] = 'Un code unique à 10 caractères alphanumériques est imprimé sur l\'attestation. Ce numéro permet une vérification de la validité d\'une attestation.';

$string['printseal_help'] = 'This option allows you to select a seal or logo to print on the certificate from the certificate/pix/seals folder. By default, this image is placed in the lower right corner of the certificate.';

$string['printsignature_help'] = 'This option allows you to print a signature image from the certificate/pix/signatures folder.  You can print a graphic representation of a signature, or print a line for a written signature. By default, this image is placed in the lower left of the certificate.';

$string['printteacher_help'] = 'For printing the teacher name on the certificate, set the role of teacher at the module level.  Do this if, for example, you have more than one teacher for the course or you have more than one certificate in the course and you want to print different teacher names on each certificate.  Click to edit the certificate, then click on the Locally assigned roles tab.  Then assign the role of Teacher (editing teacher) to the certificate (they do not HAVE to be a teacher in the course--you can assign that role to anyone).  Those names will be printed on the certificate for teacher.';

$string['reportcert_help'] = 'Si vous activez cette option, aors les dates de réception, numéro de code, et le nom du cours seront mentionnés dans les rapports. Si vous avez opté pour la mention du score, alors le score sera également affiché dans les rapports.';

$string['savecert_help'] = 'Si vous activez cette option, alors une copie pdf de l\'attestation est stockée physiquement dans les fichiers du module. Un lien vers ces fichiers sera disponible dans les rapports d\'attestation pour chaque candidat attesté.';

$string['orientation_help'] = 'Choisissez le format d\'impression de l\'attestation (portrait ou paysage).';

$string['emailteachers_help'] = 'Si activé, les enseignants recevront une notification par couriel dès qu\'un candidat reçoit ou retire son attestation.';

$string['certificatetype_help'] = 'Vous déterminez ici la mise en forme de l\'attestation. Le répertoire des modèles d\'attestation contient quatre sous-répertoires de modèles par défaut :

A4 avec polices : imprime l\'attestation au format A4 avec inclusion des polices de caractères.

A4 sans polices : imprime l\'attestation au format A4 sans inclusion des polices de caractères.

Lettre US avec polices : imprime l\'attestation au format Letter US sans inclusion des polices de caractères.

Lettre US sans polices : imprime l\'attestation au format Letter US sans inclusion des polices de caractères.

Le modèle sans polices incluses fait référence aux polices Helvetica et Times. Si vous savez que vos utilisateurs ne disposent pas de manière fiable des polices Helvetica et Times sur leur ordinateur, ou si l\'impression utilise des caractères non compatibles avec ces polices, alors il est conseillé d\'utiliser les formats à polices incluses. Le format à policss incluses contient les polices Dejavusans et Dejavuserif. Ceci accroit de manière significative la taille des fichiers PDF générés. N\'utilisez le transport des polices que si vous ne pouvez faire autrement.

Il est possible de customiser ces modèle en ajoutant des répertoires. Demandez à un intégrateur Moodle de constituer les ressources nécessaires.';

$string['customtext_help'] = 'Si vous ne voulez pas que l\'attestation génère les noms des enseignants automatiquement ou ne signe l\'attestation, désactivez l\'option "Afficher les enseignants" ainsi que toute image de signature (vous pouvez conserver l\'image de contour).  Entrez le texte personnalisé dans la zone de texte.  Par défaut, ce texte est imprimé dans la zone inférieure gauche de l\'attestation. Les tags HTML suivants peuvent être utilisés : &lt;br&gt;, &lt;p&gt;, &lt;b&gt;, &lt;i&gt;, &lt;u&gt;, &lt;img&gt; ("src" et "width" (ou "height") sont requis), &lt;a&gt; ("href" est requis), &lt;font&gt; (les attributs possibles sont: "color", (hex color code), "face", (arial, times, courier, helvetica, symbol)).';

$string['delivery_help'] = 'Choisissez la façon dont le candidat recevra son attestation.

Ouvrir dans le navigateur : Visualise l\'atttestaton dans une fenêtre du navigateur.

Force le télécargement : Déclenche l\'ouverture d\une popup de téléchargement dans le navigateur.

Par courriel : L\'attestation est envoyée comme pièce attachée d\'un courriel.
Une fois que l\'attestation a été envoyée, les candidats pourront retrouver la date effective d\'envoi et visualiser l\'attstation par un lien dans la page principale du cours.
';

$string['emailteachermail'] = '
{$a->student} a reçu son attestation : \'{$a->certificate}\'
pour le cours {$a->course}.

Vous pouvez la visualiser ici :

    {$a->url}';

$string['emailteachermailhtml'] = '
{$a->student} a reçu son attestation : \'<i>{$a->certificate}</i>\'
pour le cours {$a->course}.

Vous pouvez la visualiser ici :

    <a href="{$a->url}">Rapport d\'attestation</a>.';

$string['gradefmt_help'] = 'Trois formats de score sont disponibles si vous voulez imprimer le score sur l\'attestation :

Pourcentage : Affiche un pourcentage de réalisation.

Points : La note est affichée en valeur absolue de points reçus.

Lettrage : Un barème à lettres est utilisé.
';

// Extensions
$string['none'] = '(Aucun)';
$string['noauthority'] = 'Pas d\'autorité';
$string['coursedependencies'] = 'Cours dépendants';
$string['linkedcourse'] = 'Lié au cours';
$string['mandatoryreq'] = 'Prérequis obligatoire';
$string['rolereq'] = 'Role';
$string['addcourselabel'] = 'Ajouter un cours';
$string['coursechaining'] = 'Chainage de cours';
$string['setcertificationcontext'] = 'Contexte';
$string['setcertificationcontext_help'] = 'Le contexte dans le quel le rôle sera donné sur attestation';
$string['certifierid'] = 'Autorité attestante';
$string['certifierid_help'] = 'Définir une autorité attestante imprimera le nom de l\'autorité sur l\'attestation si son modèle le permet';
$string['setcertification'] = 'Role donné sur délivrance';
$string['setcertification_help'] = 'Le rôle qui sera attribué lors de la délivrance. Notez qu\'il ne s\'agit pas d\'une inscription. Pour inscrire le bénéficiaire à un nouveau cours, vous devrez utiliser le chainage de cours.';
$string['thiscourse'] = 'Ce cours';
$string['thiscategory'] = 'Cette catégorie';
$string['sitecourse'] = 'La page d\'acueil';
$string['system'] = 'Niveau système';
$string['chaining'] = 'Chaînage';
$string['chaining_help'] = 'Le chaînage permet à un bénéficiaire d\'être inscrit dans un nouveau cours comme conséquence de la délivrance de l\'attestation';
$string['addcoursetitle'] = 'Ajouter le titre du cours';

$string['userstocertify'] = 'Reste à attester : {$a}';
$string['usersgenerated'] = 'Attestations générées non retirées : {$a}';
$string['usersdelivered'] = 'Attestations délivrées : {$a}';

