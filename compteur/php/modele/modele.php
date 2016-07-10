<?php

class Utilisateur {
  var $id;
  var $nom;
  var $projets;
  var $compteur_quotidien;
  var $compteur_mensuel;
  var $compteur_annuel;
  var $date_debut_ecriture;
  var $objectifs;
  var $has_objectif_mensuel;

  function __construct($id, $nom) {
    $this->id = $id;
    $this->nom = $nom;
    $this->projets = array ();
    $this->compteur_quotidien = 0;
    $this->compteur_mensuel = 0;
    $this->compteur_annuel = 0;
    $this->objectifs = array ();
    $this->has_objectif_mensuel = false;
  }

  function add_projet($projet) {
// tableau associatif = objet en json
    $this->projets[$projet->id] = $projet;
//        array_push ( $this->projets, $projet );
  }

  function add_objectif($objectif) {
//    -> tableau associatif = objet en json
    $this->objectifs[$objectif->id] = $objectif; 
//     array_push ( $this->objectifs, $objectif );
  }
}

class Projet {
  var $id;
  var $titre;
  var $visible;
  var $compteurs;
  var $objectifs;

  function __construct($id, $titre) {
    $this->id = $id;
    $this->titre = $titre;
    $this->visible = false;
    $this->compteurs = array ();
    $this->objectifs = array ();
  }

  function add_objectif($objectif) {
//     -> tableau associatif = objet en json
    $this->objectifs[$objectif->id] = $objectif; 
//         array_push ( $this->objectifs, $objectif );
  }

  function add_compteur($compteur) {
//     -> tableau associatif = objet en json
    $this->compteurs[$compteur->id] = $compteur;
//     array_push ( $this->compteurs, $compteur );
  }
}

class Compteur {
  var $id;
  var $date;
  var $nb_mots;

  function __construct($id, $date, $nb_mots) {
    $this->id = $id . $date;
    $this->date = $date;
    $this->nb_mots = $nb_mots;
  }
}

class Objectif extends Compteur {
  var $date_fin;
  var $titre;
  var $compteur;
  var $nb_mots_quotidiens;

  function __construct($id, $date_debut, $date_fin, $titre, $nb_mots, $compteur) {
    parent::__construct ( $id, $date_debut, $nb_mots );
    $this->id = $this->id . $date_fin;
    $this->date_fin = $date_fin;
    $this->compteur = $compteur;
    $this->titre = $titre;
    $this->nb_mots_quotidiens = 0;
  }
}
?>