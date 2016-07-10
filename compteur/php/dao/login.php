<?php
include ('../modele/modele.php');
include ('../utils/reponse.php');

// recuperer les informations de la requete http
$postdata = file_get_contents ( "php://input" );

// decoder la requete pour separer les donnees
$request = json_decode ( $postdata );

// connection a la base
$link = mysqli_connect ( 'localhost', 'root', '', 'compteur' );
if (! $link) {
  $reponse = new Reponse ( 'Erreur de connexion (' . mysqli_connect_errno () . ') ' . mysqli_connect_error (), '' );
  exit ( json_encode ( $reponse ) );
}

// recuperer le nom envoye de la vue
$name = mysqli_real_escape_string ( $link, $request->name );

// on verifie si l'utilisateur existe dans la base
$requestGetUser = "SELECT idUtilisateur, nom FROM Utilisateur WHERE nom = '" . $name . "'";

// execution de la requete
$result = mysqli_query ( $link, $requestGetUser );
if (! $result) {
  $reponse = new Reponse ( 'Erreur sur la requete : ' . $requestGetUser . ' : ' . mysqli_error ( $link ), '' );
  exit ( json_encode ( $reponse ) );
}

// si l'utilisateur n'existe pas, on le cree
if (mysqli_num_rows ( $result ) == 0) {
  $requestSetUser = "INSERT INTO Utilisateur (nom) VALUES ('" . $name . "')";
  $result = mysqli_query ( $link, $requestSetUser );
  if (! $result) {
    $reponse = new Reponse ( 'Erreur sur la requete : ' . $requestSetUser . ' : ' . mysqli_error ( $link ), '' );
    exit ( json_encode ( $reponse ) );
  }
  
  // recuperation des donnees de l'utilisateur cree
  $result = mysqli_query ( $link, $requestGetUser );
  if (! $result) {
    $reponse = new Reponse ( 'Erreur sur la requete : ' . $requestGetUser . ' : ' . mysqli_error ( $link ), '' );
    exit ( json_encode ( $reponse ) );
  }
}

// on recupere les donnees de l'utilisateur
$row = mysqli_fetch_assoc ( $result );

// on initialise l'utilisateur avec ses donnees
$utilisateur = new Utilisateur ( $row ["idUtilisateur"], $row ["nom"] );

// Liberation du jeu de resultats
mysqli_free_result ( $result );

// requete de recuperation des projets de l'utilisateur
$requestGetProject = "SELECT idProjet, titre FROM projet WHERE idUtilisateur = " . $utilisateur->id . " ORDER BY titre";

// execution de la requete
$resultProjet = mysqli_query ( $link, $requestGetProject );
if (! $resultProjet) {
  $reponse = new Reponse ( 'Erreur sur la requete : ' . $requestGetProject . ' : ' . mysqli_error ( $link ), '' );
  exit ( json_encode ( $reponse ) );
}

// on recupere les donnees du projet
while ( $rowProjet = mysqli_fetch_assoc ( $resultProjet ) ) {
  // initialisation du projet
  $projet = new Projet ( $rowProjet ["idProjet"], $rowProjet ["titre"] );
  
  // on recupere les compteurs pour chaque projet
  $requestGetCompteur = "SELECT date, mots FROM compteur_projet WHERE idProjet = " . $rowProjet ["idProjet"];
  
  // execution de la requete
  $result = mysqli_query ( $link, $requestGetCompteur );
  if (! $result) {
    $reponse = new Reponse ( 'Erreur sur la requete : ' . $requestGetCompteur . ' : ' . mysqli_error ( $link ), '' );
    exit ( json_encode ( $reponse ) );
  }
  
  // parcour des compteurs
  while ( $row = mysqli_fetch_assoc ( $result ) ) {
    // initialisation du compteur
    $compteur = new Compteur ( $rowProjet ["idProjet"], $row ["date"], $row ["mots"] );
    // ajout du compteur au projet
    $projet->add_compteur ( $compteur );
    // mise a jour des compteurs utilisateurs
    $dateCompteur = date_parse ( $compteur->date );
    if ($dateCompteur ["year"] == getdate () ["year"]) {
      $utilisateur->compteur_annuel += $compteur->nb_mots;
      if ($dateCompteur ["month"] == getdate () ["mon"]) {
        $utilisateur->compteur_mensuel += $compteur->nb_mots;
        if ($dateCompteur ["day"] == getdate () ["mday"]) {
          $utilisateur->compteur_quotidien += $compteur->nb_mots;
        }
      }
    }
  }
  
  // Liberation du jeu de resultats
  mysqli_free_result ( $result );
  
  // on alimente les projets de l'utilisateur
  $utilisateur->add_projet ( $projet );
}

// Liberation du jeu de resultats
mysqli_free_result ( $resultProjet );

// on recupere les compteurs pour chaque projet
$requestGetDateDebut = "SELECT MIN(date) as dateDebut
                        FROM   compteur_projet 
                        JOIN   projet
                          ON   projet.idProjet = compteur_projet.idProjet
                        WHERE  projet.idUtilisateur = " . $utilisateur->id;

// execution de la requete
$result = mysqli_query ( $link, $requestGetDateDebut );
if (! $result) {
  $reponse = new Reponse ( 'Erreur sur la requete : ' . $requestGetDateDebut . ' : ' . mysqli_error ( $link ), '' );
  exit ( json_encode ( $reponse ) );
}

// recuperation du resultat
$row = mysqli_fetch_assoc ( $result );

// si la date est renseignee, on s'en sert pour le traitement, sinon, on prend la date du jour
if ($row ["dateDebut"] != null) {
  $dateDebut = $row ["dateDebut"];
} else {
  $dateDebut = date ( "o-m-d" );
}

// Liberation du jeu de resultats
mysqli_free_result ( $result );

// calcule des moyennes
$utilisateur->date_debut_ecriture = $dateDebut;

// requete de recuperation des objectifs de l'utilisateur
$requestGetObjectifs = "SELECT    date_debut, date_fin, titre_objectif, objectif_utilisateur.mots, SUM(compteur_projet.mots) as compteur 
                        FROM      objectif_utilisateur
                        LEFT JOIN projet
                               ON projet.idUtilisateur = objectif_utilisateur.idUtilisateur
                        LEFT JOIN compteur_projet
                               ON compteur_projet.idProjet = projet.idProjet
                              AND date >= date_debut
                              AND date <= '" . date ( "o-m-d" ) . "'
                        WHERE     objectif_utilisateur.idUtilisateur = " . $utilisateur->id . "
                              AND date_fin >= '" . date ( "o-m-d" ) . "'
                        GROUP BY  date_debut, date_fin, objectif_utilisateur.mots";

// execution de la requete
$resultObjectifs = mysqli_query ( $link, $requestGetObjectifs );
if (! $resultObjectifs) {
  $reponse = new Reponse ( 'Erreur sur la requete : ' . $requestGetObjectifs . ' : ' . mysqli_error ( $link ), '' );
  exit ( json_encode ( $reponse ) );
}

// on recupere les donnees de l'objectif
while ( $rowObjectif = mysqli_fetch_assoc ( $resultObjectifs ) ) {
  
  $compteur = $rowObjectif ["compteur"] == null ? 0 : $rowObjectif ["compteur"];
  
  // initialisation de l'objectif
  $objectif = new Objectif ( $utilisateur->id, $rowObjectif ["date_debut"], $rowObjectif ["date_fin"], $rowObjectif ["titre_objectif"], $rowObjectif ["mots"], $compteur );
  
  // nombre jour entre maintenant et la date butoire
  $nbjour = date_diff ( new DateTime (), new DateTime ( $objectif->date_fin ) );
  // calcul nombre de mots a ecrire par jour
  $objectif->nb_mots_quotidiens = round ( ($objectif->nb_mots - $objectif->compteur) / ($nbjour->format ( '%a' ) + 1) );
  
  // on alimente les projets de l'utilisateur
  $utilisateur->add_objectif ( $objectif );
  
  if (($objectif->date == date("o-m-d", mktime(0, 0, 0, getdate () ["mon"], 1, getdate () ["year"]))) 
      && ($objectif->date_fin == date("o-m-d", mktime(0, 0, 0, getdate () ["mon"] + 1, 0, getdate () ["year"])))) {
    $utilisateur->has_objectif_mensuel = true;
  }
}

// Liberation du jeu de resultats
mysqli_free_result ( $resultObjectifs );

// on recupere les donnees des projets de l'utilisateur
$reponse = new Reponse ( '', $utilisateur );
echo json_encode ( $reponse, JSON_FORCE_OBJECT );

// fermeture de la connection
mysqli_close ( $link );

?>