<?php

/***************************************************************************\
 *  SPIP, Systeme de publication pour l'internet                           *
 *                                                                         *
 *  Copyright (c) 2001-2005                                                *
 *  Arnaud Martin, Antoine Pitrou, Philippe Riviere, Emmanuel Saint-James  *
 *                                                                         *
 *  Ce programme est un logiciel libre distribue sous licence GNU/GPL.     *
 *  Pour plus de details voir le fichier COPYING.txt ou l'aide en ligne.   *
\***************************************************************************/

  // ce script n'est la que pour compatibilite avec d'anciens liens
  // Il redirige pour pouvoir utiliser le chargement automatique 
  // du fichier homonyme avec inc_ comme prefixe.
  // Le nom "statistiques" rentre en conflit avec le chargement automatique
  // pour inc_cron: ca pourrait cohabiter, mais ca ralentirait le chargement

header("Location: " . str_replace('statistiques.php3', 'statistiques_repartition.php',  $_SERVER['REQUEST_URI']));

exit;
?>

