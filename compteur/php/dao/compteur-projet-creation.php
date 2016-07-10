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
$project = $request->project;

// si la date est renseignee, on s'en sert pour le traitement, sinon, on prend la date du jour
if ($project->dateCompteur != "") {
  $date = $project->dateCompteur;
} else {
  $date = date ( "o-m-d" );
}

// requete de creation d'un nouveau compteur pour le projet
$requestCreateCompteur = "INSERT INTO compteur_projet (idProjet, date, mots) VALUES (" . $project->id . ", '" . $date . "', " . $project->nouveauxMots . ")";

// Execute la requete
$result = mysqli_query ( $link, $requestCreateCompteur );

// si l'insertion echoue
if (! $result) {
  if (mysqli_errno ( $link ) == 1062) {
    // le compteur existe pour ce jour, donc on le met a jour
    $requestUpdateCompteur = "UPDATE compteur_projet SET mots = " . $project->nouveauxMots . " WHERE idProjet = " . $project->id . " AND date = '" . $date . "'";
    
    // Execute la requete
    $result = mysqli_query ( $link, $requestUpdateCompteur ) or die ( 'Erreur sur la requete : ' . $requestUpdateCompteur . ' erreur : ' . mysqli_error ( $link ) );
  } else {
    $reponse = new Reponse ( 'Erreur sur la requete : ' . $requestUpdateCompteur . ' : ' . mysqli_error ( $link ), '' );
    exit ( json_encode ( $reponse ) );
  }
}

// requete du compteur cree
$requestGetCompteur = "SELECT idProjet, date, mots FROM compteur_projet WHERE idProjet = " . $project->id . " AND date = '" . $date . "'";

// execution de la requete
$result = mysqli_query ( $link, $requestGetCompteur );
if (! $result) {
  $reponse = new Reponse ( 'Erreur sur la requete : ' . $requestGetCompteur . ' : ' . mysqli_error ( $link ), '' );
  exit ( json_encode ( $reponse ) );
}

// recuperation du compteur nouvellement cree
$row = mysqli_fetch_assoc ( $result );

// on recupere les donnees des projets de l'utilisateur
$reponse = new Reponse ( '', new Compteur ( $row ["idProjet"], $row ["date"], $row ["mots"] ) );
echo json_encode ( $reponse, JSON_FORCE_OBJECT );

// Liberation du jeu de resultats
mysqli_free_result ( $result );

mysqli_close ( $link );

?>