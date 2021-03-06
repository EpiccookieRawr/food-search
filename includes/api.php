<?php

class nutritionix_api {
  var $app_id,
      $app_key,
      $remote_user_id,
      $api_url;

  function __construct() {
    $this->app_id = '6e98d1e1';
    $this->app_key = '7b82616788e8bf3ea2fe38aa3ff0afbb';
    $this->remote_user_id = '0';
    $this->api_url = 'https://trackapi.nutritionix.com/v2';
  }

  public function instant($keyword) {
    if($keyword == '') return false;
    $params = array(
      'query' => $keyword
    );
    $response = $this->curl_request('/search/instant', $params);

    return $this->return_response($response);
  }

  public function search($product_id) {
    if($product_id == '') return false;
    $params = array(
      'nix_item_id' => $product_id
    );
    $response = $this->curl_request('/search/item', $params);

    return $this->return_response($response);
  }

  private function return_response($response) {
    if($response != false) {
      return array(
        'status' => 'success',
        'response' => $response
      );
    } else {
      return array(
        'status' => 'failure',
        'response' => 'API failure, please try again'
      );
    }
  }

  private function curl_request($request, $query_params = array(), $method = 'GET') {
    $headers = array();
    $headers[] = 'Content-Type: application/json';
    $headers[] = 'x-app-id:' . $this->app_id;
    $headers[] = 'x-app-key:' . $this->app_key;
    $headers[] = 'x-remote-user-id:' . $this->remote_user_id;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 60);
    if($method == 'POST') {
      curl_setopt($ch, CURLOPT_POST, 1);
      curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($query_params));
      curl_setopt($ch, CURLOPT_URL, $this->api_url . $request);
    } else if($method == 'GET') {
      curl_setopt($ch, CURLOPT_URL, $this->api_url . $request . '?' . http_build_query($query_params));
    }

    $response = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    if (curl_errno($ch) || $httpcode != 200) {
        return false;
    } else {
      $response = json_decode($response,true);
    }

    curl_close($ch);

    return $response;
  }
}

$nutritionix_api = new nutritionix_api();

if(isset($_GET['search'])) {
  $response = $nutritionix_api->instant($_GET['search']);
  echo json_encode($response);
}

if(isset($_GET['productID'])){
  $response = $nutritionix_api->search($_GET['productID']);
  if($response['status'] != 'success') {
    $get_cached_data = json_decode(file_get_contents('../cache/cached_data.json'), true); 
    $cached_array_keys = array_keys($get_cached_data);
    $random_key = $cached_array_keys[rand(0, count($get_cached_data) - 1)];
    $get_cached_response = array(
      'status' => 'success',
      'response' => array( //add cached results
        'foods' => array($get_cached_data[$random_key])
      ),
      'cached' => true
    );
    echo json_encode($get_cached_response);
  } else {
    echo json_encode($response);
  }
}