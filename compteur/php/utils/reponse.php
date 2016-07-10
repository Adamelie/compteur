<?php
class Reponse {
  var $erreur;
  var $objet;

  function __construct($erreur, $objet) {
    $this->erreur = $erreur;
    $this->objet = $objet;
  }
}
?>