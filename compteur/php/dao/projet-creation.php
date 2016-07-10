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
$project = mysqli_real_escape_string ( $link, $request->project );
$user = $request->user;
$id = $request->id;

if ($id == "") {
  // requete de creation d'un nouveau projet
  $request = "INSERT INTO projet (titre, idUtilisateur) VALUES ('" . $project . "', " . $user . ")";
} else {
  $request = "UPDATE projet SET titre = '" . $project . "' WHERE idProjet = " . $id;
}

// Execute la requete
$result = mysqli_query ( $link, $request );
if (! $result) {
  $reponse = new Reponse ( 'Erreur sur la requete : ' . $request . ' : ' . mysqli_error ( $link ), '' );
  exit ( json_encode ( $reponse ) );
}

// on recupere les donnees du projet cree
$requestGetProject = "SELECT idProjet, titre FROM projet WHERE titre = '" . $project . "' AND idUtilisateur = '" . $user . "'";

// execution de la requete
$result = mysqli_query ( $link, $requestGetProject );
if (! $result) {
  $reponse = new Reponse ( 'Erreur sur la requete : ' . $requestGetProject . ' : ' . mysqli_error ( $link ), '' );
  exit ( json_encode ( $reponse ) );
}

// on recupere les donnees de la requete
$row = mysqli_fetch_assoc ( $result );

// on envoie les donnees
$reponse = new Reponse ( '', new Projet ( $row ["idProjet"], $row ["titre"] ) );
echo json_encode ( $reponse, JSON_FORCE_OBJECT );

// Liberation du jeu de resultats
mysqli_free_result ( $result );

mysqli_close ( $link );

?>