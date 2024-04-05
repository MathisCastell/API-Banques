<?php
require_once(__DIR__.'/parameters.php');
/*
**
** Script de mise à jour du statut des banques chez budgea
** Usage : php update_meteo_banques.php >> <file_out>
** Exemple : php update_meteo_banques.php >> update_meteo_banques.log 2>&1
**
*/

require_once(__DIR__."/../api/Helpers/database.php");
require_once(__DIR__."/../api/Helpers/logger.php");

// initialisation des singletons
$config = parse_ini_file(__DIR__.'/config.ini', true);
Logger::instance([
  'path' => __DIR__.'/logs/update_meteo_banques.log',
  'debug' => true
]);
Database::instance($config['mysql_egd']);

$etat_banques = get_all_etat_banques_info();

foreach($etat_banques as $etat_banque) {
  // var_dump($etat_banque["id"]);
  // var_dump($etat_banque["stability"]->status);
  $id = $etat_banque["id"];
  $status = $etat_banque["stability"]->status;
  // $mysqli = Database::instance()->getConnector();
  $mysqli = new mysqli(DB_SERVER, DB_LOGIN, DB_PASSWD, DATABASE_EGD);
  $query = "UPDATE ecommerce.support_client SET xStatut = '".$status."' WHERE externalId = ".$id." AND xType ='infoService' AND Categorie = 'Banque' LIMIT 1";
  // $query = "SELECT * FROM ecommerce.support_client WHERE externalId = ".$id." LIMIT 1";
  // var_dump($query);
  $mysqli->query($query);
}

function get_all_etat_banques_info() {
  global $config;
  // $url = $config['budgea']['api_url']."/users/all/accounts?all";
  $url = "https://proxima.biapi.pro/2.0"."/connectors/?expand=source";
  $data = fetch($url);
  // $formatted_account = json_decode($data, true);
  $etat_banques = [];
  foreach($data->connectors as $connectors) {
    $etat_banques[] = array_pluck($connectors);
  }
  return $etat_banques;  
}

function fetch($url, $debug_key = 'budgea_fetch') {
  global $config;

  $headers = [
    // 'Authorization: Bearer '.$config['budgea']['admin_token']
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
