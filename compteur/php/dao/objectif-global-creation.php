<?php
include ('../modele/modele.php');
include ('../utils/reponse.php');

// recuperer les informations de la requete http
$postdata = file_get_contents ( "php://input" );

// decoder la requete pour separer les donnees
$request = json_decode ( $postdata );

// connection a la base de donnees
$link = mysqli_connect ( 'localhost', 'root', '', 'compteur' );
if (! $link) {
  $reponse = new Reponse ( 'Erreur de connexion (' . mysqli_connect_errno () . ') ' . mysqli_connect_error (), '' );
  exit ( json_encode ( $reponse ) );
}

// recuperer les infos envoyees de la vue
$mots = $request->wordGoal;
$date_debut = $request->dateStart;
$date_fin = $request->dateGoal;
$user = $request->user;
$titre = $request->title;

// requete de creation d'un nouvel objectif
$requestCreateObjectif = "INSERT INTO objectif_utilisateur (idUtilisateur, date_debut, date_fin, titre_objectif, mots) VALUES (" . $user . ", '" . $date_debut . "', '" . $date_fin . "', '" . $titre . "', " . $mots . ")";

// Execute la requete
$result = mysqli_query ( $link, $requestCreateObjectif );
if (! $result) {
  if (mysqli_errno ( $link ) == 1062) {
    // le compteur existe pour ce jour, donc on le met a jour
    $requestUpdateCompteur = "UPDATE objectif_utilisateur SET mots = " . $project->nouveauxMots . " WHERE idProjet = " . $project->id . " AND date = '" . $date . "'";
    
    // Execute la requete
    $result = mysqli_query ( $link, $requestUpdateCompteur ) or die ( 'Erreur sur la requete : ' . $requestUpdateCompteur . ' erreur : ' . mysqli_error ( $link ) );
  } else {
    $reponse = new Reponse ( 'Erreur sur la requete : ' . $requestCreateObjectif . ' : ' . mysqli_error ( $link ), '' );
    exit ( json_encode ( $reponse ) );
  }
}

// requete de recuperation de l'objectifs cree
$requestGetObjectifs = "SELECT    date_debut, date_fin, titre_objectif, objectif_utilisateur.mots, SUM(compteur_projet.mots) as compteur
                        FROM      objectif_utilisateur
                        LEFT JOIN projet
                               ON projet.idUtilisateur = objectif_utilisateur.idUtilisateur
                        LEFT JOIN compteur_projet
                               ON compteur_projet.idProjet = projet.idProjet
                              AND date >= date_debut
                        WHERE     objectif_utilisateur.idUtilisateur = " . $user . "
                        AND       date_debut = '" . $date_debut . "'
                        AND       date_fin = '" . $date_fin . "'
                        GROUP BY  date_debut, date_fin, objectif_utilisateur.mots";

// Execute la requete
$resultObjectifs = mysqli_query ( $link, $requestGetObjectifs );
if (! $resultObjectifs) {
  $reponse = new Reponse ( 'Erreur sur la requete : ' . $requestGetObjectifs . ' : ' . mysqli_error ( $link ), '' );
  exit ( json_encode ( $reponse ) );
}

$rowObjectif = mysqli_fetch_assoc ( $resultObjectifs );

$compteur = $rowObjectif ["compteur"] == null ? 0 : $rowObjectif ["compteur"];

// Liberation du jeu de resultats
mysqli_free_result ( $resultObjectifs );

$objectif = new Objectif ( $user, $date_debut, $date_fin, $rowObjectif ["titre_objectif"], $mots, $rowObjectif ["compteur"] );

// nombre jour entre maintenant et la date butoire
$nbjour = date_diff ( new DateTime (), new DateTime ( $objectif->date_fin ) );
// calcul nombre de mots a ecrire par jour
$objectif->nb_mots_quotidiens = round ( ($objectif->nb_mots - $objectif->compteur) / $nbjour->format ( '%a' ) );

// on envoie les donnees
$reponse = new Reponse ( '', $objectif );
echo json_encode ( $reponse, JSON_FORCE_OBJECT );

mysqli_close ( $link );

?>