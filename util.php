<?php
/*
* Copyright (C) 2013 Google Inc.
*
* Licensed under the Apache License, Version 2.0 (the "License");
* you may not use this file except in compliance with the License.
* You may obtain a copy of the License at
*
*      http://www.apache.org/licenses/LICENSE-2.0
*
* Unless required by applicable law or agreed to in writing, software
* distributed under the License is distributed on an "AS IS" BASIS,
* WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
* See the License for the specific language governing permissions and
* limitations under the License.
*/
//  Author: Jenny Murphy - http://google.com/+JennyMurphy

require_once 'config.php';
require_once 'mirror-client.php';
require_once 'google-api-php-client/src/Google_Client.php';
require_once 'google-api-php-client/src/contrib/Google_MirrorService.php';

function store_credentials($user_id, $credentials) {
  $db = init_db();
  
  $user_id = $db->real_escape_string(strip_tags($user_id));
  $credentials = $db->real_escape_string(strip_tags($credentials));

  $insert = "insert into credentials values (null, '$user_id', '$credentials')";
  $db->real_query($insert);
}

function get_credentials($user_id) {
  $db = init_db();
  $user_id = $db->real_escape_string(strip_tags($user_id));

  $query = "select * from credentials where userid = '$user_id'";
  
  $result = $db->query($query);
  if($result){
    $val = FALSE;
    while ($row = $result->fetch_object()){
      $val = $row;
    }
    // Free result set
    $result->close();
    return $val->credentials;
  }
  else {
    return FALSE;
  }
}

function list_credentials() {
  $db = init_db();

  $query = "select * from credentials";
  
  $result = $db->query($query);
  $out = array();
  $id = 0;
  if($result){
    while ($row = $result->fetch_object()){
      $val = $row;
      $out[$id]['userid'] = $row->userid;
      $out[$id]['credentials'] = $row->credentials;
      $id++;
    }
    // Free result set
    $result->close();
  }
  
  return $out;
}

// Create the credential storage if it does not exist
function init_db() {
  global $db_host;
  global $db_user;
  global $db_pass;
  global $db_db;
  
  $mysqli = new mysqli($db_host, $db_user, $db_pass, $db_db);
  return $mysqli;
}

function bootstrap_new_user() {
  global $base_url;

  $client = get_google_api_client();
  $client->setAccessToken(get_credentials($_SESSION['userid']));

  // A glass service for interacting with the Mirror API
  $mirror_service = new Google_MirrorService($client);

  $timeline_item = new Google_TimelineItem();
  $timeline_item->setText("Welcome to the Mirror API PHP Quick Start");

  insert_timeline_item($mirror_service, $timeline_item, null, null);

  insert_contact($mirror_service, "php-quick-start", "PHP Quick Start",
      $base_url . "/static/images/chipotle-tube-640x360.jpg");

  subscribe_to_notifications($mirror_service, "timeline",
    $_SESSION['userid'], $base_url . "/notify.php");
}

