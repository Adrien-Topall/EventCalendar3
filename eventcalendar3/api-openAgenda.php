<?php
/*
Copyright (c) 2015, Adrien Topall

This program is free software; you can redistribute it and/or
modify it under the terms of the GNU General Public License
as published by the Free Software Foundation; either version 2
of the License, or (at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
/*************************************************************************
*             Définition des variables
**************************************************************************/
global $wpdb;
global $ec3;

$apikey = $ec3->OpenAgandaKey;
$secretKey = $ec3->OpenAgandaSecretKey;
$slugNameAgenda = $ec3->OpenAgandaSlugName;

$randomnumber = rand(100000, 999999);

$accessToken = '';
$location_uid = '';
$aganda_uid = '';
$aganda_title = '';
$aganda_url = '';
$aganda_description = '';
$listeEvents = '';


/*************************************************************************
*             Récuperation de l' accesToken
**************************************************************************/
function oa_connect($secretKey){
  global $accessToken;
  $ch = curl_init('https://api.openagenda.com/v1/requestAccessToken');

  curl_setopt($ch, CURLOPT_POST, true);
  curl_setopt($ch, CURLOPT_POSTFIELDS, array(
    'grant_type' => 'authorization_code', 
    'code' => $secretKey
  ));
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  $received_content = curl_exec($ch);

  if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200){
    $data = json_decode($received_content, true);
    $accessToken = $data["access_token"];
    return $accessToken;
  }

}
//oa_connect($secretKey);



/*************************************************************************
*             Création d'un lieux
**************************************************************************/
function oa_createLocation($accessToken, $id_lieux){
  global $wpdb;
  $randomnumber = rand(100000, 999999);
  $table_lieux = $wpdb->prefix . 'ec3_lieux';

  $placeInfo = $wpdb->get_row('SELECT * FROM '.$table_lieux.' WHERE lieux_id = '.$id_lieux.' LIMIT 1 ');
  
  if ( $placeInfo->latitude == 0 && $placeInfo->longitude == 0 ) {
    $adresse = wp_strip_all_tags( $placeInfo->adresse );

    // 1 . je construit une fonction pour récup' les coords
    function get_coords($a){
      // je construit une URL avec l'adresse
      $map_url = 'http://maps.google.com/maps/api/geocode/json?address=' . urlencode( $a ) . '&sensor=false';
      // je récupère ça
      $request = wp_remote_get( $map_url );
      $json = wp_remote_retrieve_body( $request );
      // si c'est vide, je kill...
      if( empty( $json ) )
      return false;
      // je parse et je choppe la latitude et la longitude
      $json = json_decode( $json );
      $lat = $json->results[ 0 ]->geometry->location->lat;
      $long = $json->results[ 0 ]->geometry->location->lng;
      // je retourne les valeurs sous forme de tableau
      return compact( 'lat', 'long' );
    }
    // 2. je lance ma fonction, l'adresse en parametre
    $coords = get_coords( $adresse );
    // 3. si j'ai récupéré des coordonnées, je sauvegarde
    if( $coords != '' ){
      $placeInfo->latitude = $coords['lat'];
      $placeInfo->longitude = $coords['long'];
      $wpdb->query('UPDATE '.$table_lieux.' SET longitude = '.$placeInfo->longitude.', latitude = '.$placeInfo->latitude.' WHERE lieux_id = '.$id_lieux.' ');
    }
  }

  $ch_lieux = curl_init("https://api.openagenda.com/v1/locations");

  //curl_setopt($ch_lieux, CURLOPT_CUSTOMREQUEST, 'PUT');
  curl_setopt( $ch_lieux, CURLOPT_POST, true );

  curl_setopt($ch_lieux, CURLOPT_POSTFIELDS, array(
    'access_token' => $accessToken,
    'nonce' => $randomnumber,
    'data' => json_encode(array(
      'placename' => $placeInfo->nom_lieux,
      'address' => $placeInfo->adresse,
      'latitude' => $placeInfo->latitude,
      'longitude' => $placeInfo->longitude
    ))
  ));
  curl_setopt($ch_lieux, CURLOPT_RETURNTRANSFER, TRUE);

  $received_content = curl_exec($ch_lieux);

  if (curl_getinfo($ch_lieux, CURLINFO_HTTP_CODE) == 200)
  {
    $data2 = json_decode($received_content, true);
    $location_uid = $data2['uid'];
  }

  if (!empty($location_uid)) {
    //inserer l'uid dans la db ec3_lieux
    $wpdb->query('UPDATE '.$table_lieux.' SET lieux_uid = '.$location_uid.' WHERE lieux_id = '.$id_lieux.' ');
  }
  else{
    print_r($received_content);
  }
  return $location_uid;
  
}


/*************************************************************************
*             Recupere info agenda à partir du slug Name
**************************************************************************/
function oa_getUidAgenda($key, $slugNameAgenda){
  $ch_agenda = curl_init("https://api.openagenda.com/v1/agendas/uid/".$slugNameAgenda."?key=".$key."");

  curl_setopt($ch_agenda, CURLOPT_POST, false);
  curl_setopt($ch_agenda, CURLOPT_RETURNTRANSFER, TRUE);

  $received_content = curl_exec($ch_agenda);

  if (curl_getinfo($ch_agenda, CURLINFO_HTTP_CODE) == 200)
  {
    $data4 = json_decode($received_content, true);
    return $data4['data']['uid'];
  }
  else{
    print_r($received_content);
  }
/*
  $ch_agendas = curl_init("https://api.openagenda.com/v1/agendas/".$agenda_uid."?key=".$key."");

  curl_setopt($ch_agendas, CURLOPT_RETURNTRANSFER, TRUE);

  $received_content2 = curl_exec($ch_agendas);

  if (curl_getinfo($ch_agendas, CURLINFO_HTTP_CODE) == 200)
  {
    $data5 = json_decode($received_content2, true);
    $agenda_title = $data5['data']['title'];
    $agenda_url = $data5['data']['url'];
    $agenda_description = $data5['data']['description'];
  }
*/
  // Enregistrer les information dans la db ec3_oa_agenda

}
/*oa_getUidAgenda($key, $slugNameAgenda);
echo '<p> Agenda : '.$slugNameAgenda.' </br>'.
     '=>  aganda uid : '.$agenda_uid.'</br>'.
     '=>  aganda title : '.$agenda_title.'</br>'.
     '=>  aganda url : '.$agenda_url.'</br>'.
     '=>  aganda description : '.$agenda_description.'</p>';*/

/*************************************************************************
*             Création d'un event
**************************************************************************/

/*************   Format attendue pour les dates *************
$date = array(
    array('2015-10-24', '12:00', '20:00'),
    array('2015-10-28', '14:00', '24:00'),
    array('2015-10-30', '08:00', '12:00'),
  );
**************************************************************/

function oa_createEvent( $accessToken, $title, $description='', $freeText='', $tags='', $location_uid, $date, $imagePath='' ){
  $randomnumber = rand(100000, 999999);
  $ch_event = curl_init('https://api.openagenda.com/v1/events');

  $liseDate = array();
  foreach ($date as $value) {
    $arrayDate = array("date" => $value[0], "timeStart" => $value[1], "timeEnd" => $value[2]);
    array_push($liseDate, $arrayDate);
  }

  curl_setopt($ch_event, CURLOPT_POST, true);
  curl_setopt($ch_event, CURLOPT_RETURNTRANSFER, TRUE);

  // information à recuperer dans Post / ec3_schedule / ec3_lieux
  $eventData = array(
    'title' => $title, // max 140 char
    'description' => $description,  // max 200 char
    'freeText' => $freeText,  // max 6000 char
    'tags' => $tags,  // max 255 char
    'locations' => array( 
      array('uid' => $location_uid, 'dates' => $liseDate ),
    )
  );

  curl_setopt($ch_event, CURLOPT_POSTFIELDS, array(
    'access_token' => $accessToken,
    'nonce' => $randomnumber,
    'lang' => 'fr',
    'image' => $imagePath,
    'publish' => true, // do not publish this event at creation
    'data' => json_encode($eventData)
  ));

  $received_content = curl_exec($ch_event);

  if (curl_getinfo($ch_event, CURLINFO_HTTP_CODE) == 200)
  {
    $data3 = json_decode($received_content, true);
    $event_uid = $data3['uid'];
    return $event_uid;
  }
  else{
    return 'false';
  }
}

/*************************************************************************
*             liste des event d'un aganda
**************************************************************************/
function oa_pushEventAgenda( $agenda_uid, $event_uid, $description, $accessToken ){
  $randomnumber = rand(100000, 999999);
  $ch_push = curl_init('https://api.openagenda.com/v1/agendas/'.$agenda_uid.'/events');

  curl_setopt($ch_push, CURLOPT_POST, false);
  curl_setopt($ch_push, CURLOPT_RETURNTRANSFER, TRUE);

  $shareData = array(
    'event_uid' => $event_uid,
    'article' => $description
    //'category' => 'Folklore'
  );

  curl_setopt($ch_push, CURLOPT_POSTFIELDS, array(
    'access_token' => $accessToken,
    'nonce' => $randomnumber,
    'data' => json_encode($shareData),
  ));

  $received_content = curl_exec($ch_push);

  if (curl_getinfo($ch_push, CURLINFO_HTTP_CODE) == 200)
  {
    $data6 = json_decode($received_content, true);
    return 'ok';
  }
  else{
    print_r($received_content);
    return 'false';
  }


}


/*************************************************************************
*             liste des event d'un aganda
**************************************************************************/
function oa_getEvents( $agenda_uid, $key ){
  global $listeEvents;
  $ch_listeEvent = curl_init("https://api.openagenda.com/v1/agendas/".$agenda_uid."/events?key=".$key."");

  curl_setopt($ch_listeEvent, CURLOPT_RETURNTRANSFER, TRUE);

  $received_content = curl_exec($ch_listeEvent);

  if (curl_getinfo($ch_listeEvent, CURLINFO_HTTP_CODE) == 200)
  {
    $data4 = json_decode($received_content, true);
    $listeEvents = $data4['data'];
  }
}
/*oa_getEvents( $agenda_uid, $key );
?><pre><?php print_r($listeEvents); ?></pre><?php */


/*************************************************************************
*             Supprimer un Event
**************************************************************************/
function oa_delEvent($event_uid, $accessToken){

  $randomnumber = rand(100000, 999999);
  $ch = curl_init("https://api.openagenda.com/v1/events/".$event_uid."");

  curl_setopt($ch, CURLOPT_POSTFIELDS, array(
    'access_token' => $accessToken,
    'nonce' => $randomnumber,
  ));

  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

  $received_content = curl_exec($ch);

  if (curl_getinfo($ch, CURLINFO_HTTP_CODE)==200)
  {
    return 'successfull';
  }
}
//oa_delEvent($listeEvents[0]['uid'], $accessToken);

/*************************************************************************
*             Editer un Event
**************************************************************************/

function oa_editEvent($event_Uid, $accessToken, $title, $date, $location_uid, $tags='' ){
  $randomnumber = rand(100000, 999999);
  $ch = curl_init("https://api.openagenda.com/v1/events/".$event_Uid."");

  $liseDate = array();
  foreach ($date as $value) {
    $arrayDate = array("date" => $value[0], "timeStart" => $value[1], "timeEnd" => $value[2]);
    array_push($liseDate, $arrayDate);
  }

  $eventData = array(
    'title' => $title,
    'tags' => $tags,
      // add a location to your event. If location is already associated to event, dates are looked at and added if new
    'locations' => array( 
      array('uid' => $location_uid, 'dates' => $liseDate ),
    )
  );

  curl_setopt($ch, CURLOPT_POST, 1);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);

  curl_setopt($ch, CURLOPT_POSTFIELDS, array(
    'access_token' => $accessToken,
    'nonce' => $randomnumber,
    'lang' => 'fr',
    'data' => json_encode($eventData)
  ));

  $received_content = curl_exec($ch); 

  if (curl_getinfo($ch, CURLINFO_HTTP_CODE) == 200)
  {
    $data = json_decode($received_content, true);
    $event_uid = $data['uid'];
    return $event_uid;
  }
  else{
    print_r($received_content);
    return 'false';
  }
}
