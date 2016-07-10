<?php
class Logs {
  
  public static $ERREUR = "erreur";
  
  public static $INFO = "info";
  
  public static $DEBUG = "debug";
  
  public static function loguer($niveau, $message) {
    // Analyse sans sections
    $proprietes = parse_ini_file("../../log.ini");
    error_log($message, 3, $proprietes['chemin.erreurs']);
  }
  
}
?>