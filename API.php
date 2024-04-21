<?php
// Inclut le fichier parameters.php situé dans le même répertoire que ce script
require_once(__DIR__.'/parameters.php');

/*
** Script de mise à jour du statut des banques chez budgea
** Usage : php update_meteo_banques.php >> <file_out>
** Exemple : php update_meteo_banques.php >> update_meteo_banques.log 2>&1
*/

// Inclut des fichiers nécessaires depuis le répertoire parent
require_once(__DIR__."/../api/Helpers/database.php");
require_once(__DIR__."/../api/Helpers/logger.php");

// Chargement des configurations depuis le fichier config.ini
$config = parse_ini_file(__DIR__.'/config.ini', true);

// Initialisation du logger avec chemin et mode debug
Logger::instance([
  'path' => __DIR__.'/logs/update_meteo_banques.log',
  'debug' => true
]);

// Initialisation de la base de données avec les configurations MySQL
Database::instance($config['mysql_egd']);

// Récupération des informations de tous les états de banques
$etat_banques = get_all_etat_banques_info();

// Boucle sur chaque banque pour mettre à jour son statut
foreach($etat_banques as $etat_banque) {
  $id = $etat_banque["id"];
  $status = $etat_banque["stability"]->status;
  // Connexion directe à la base de données pour effectuer la mise à jour
  $mysqli = new mysqli(DB_SERVER, DB_LOGIN, DB_PASSWD, DATABASE_EGD);
  $query = "UPDATE ecommerce.support_client SET xStatut = '".$status."' WHERE externalId = ".$id." AND xType ='infoService' AND Categorie = 'Banque' LIMIT 1";
  $mysqli->query($query);
}

// Fonction pour obtenir les informations sur l'état de toutes les banques
function get_all_etat_banques_info() {
  global $config;
  $url = "https://proxima.biapi.pro/2.0"."/connectors/?expand=source";
  $data = fetch($url);
  $etat_banques = [];
  foreach($data->connectors as $connectors) {
    $etat_banques[] = array_pluck($connectors);
  }
  return $etat_banques;  
}

// Fonction pour récupérer les données d'une API en utilisant cURL
function fetch($url, $debug_key = 'budgea_fetch') {
  global $config;
  $headers = [
    'Authorization: Bearer le_token_confidentiel'
  ];

  Logger::instance()->log("url : ".$url);
  $ch = curl_init($url);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
  Logger::instance()->timer($debug_key);
  $json = curl_exec($ch);
  Logger::instance()->timer($debug_key);
  curl_close($ch);
  return json_decode($json);
}

// Fonction pour extraire des éléments spécifiques d'un tableau
function array_pluck($array) {
  $output_keys = ['id', 'stability'];
  $res = [];
  
  foreach($output_keys as $key) {
    if(property_exists($array, $key)) {
      $res[$key] = $array->{$key};
    } else {
      $res[$key] = NULL;
    }
  }
  
  return $res;
}
