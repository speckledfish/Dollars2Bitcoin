<?php

/**
 * @file
 * Module to process curl requests
 */

/// a series of functions to send and receive data to APIs via cURL


/**
 * send a curl request
 *
 */
// function local_curl_service_send_request($service, $url, $parameter=false, $type='get') {
function local_curl_service_send_request($url, $parameter=false, $type='get') {
	//switch (strtolower($service)) {
		//case "bitcoin":
			// TO DO:
			// get the API Key
			// get the API password
			//$api_key		= variable_get('dollars2bitcoin_api_key');
			// $api_password	= variable_get('dollars2bitcoin_api_password');
			///// --> these are both set through configuration at /admin/config/dollars2bitcoin/content
			//$value		= "your data was $data";
			//$response	= " WOOT your API Key was [ $api_key ] and Password was [ $api_password ]";
			//break;
	//}
	// using the cURL library to send a cURL request to anywhere - woot!

	try {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);

		/* allow redirects */
		//curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
		/* return a response into a variable */
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		/* times out after 30s */
		curl_setopt($ch, CURLOPT_TIMEOUT, 30);
		/* set POST method */
		switch (strtolower($type)){
			case 'post':
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter);
				break;
			case 'put':
				//echo "<hr />$parameter<hr />";
				//$put_file   = fopen($parameter, "rb");
				//echo "the Put File: $put_file <hr />";
				//echo "<hr>the filesize: " . filesize($parameter);
				curl_setopt($ch, CURLOPT_PUT, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS, $parameter);
				break;
			case 'get':
				curl_setopt($ch, CURLOPT_HTTPGET, 1);
				break;
		}
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);
		/* execute the cURL */
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	} catch (Exception $exception) {
		drupal_set_message('Exception Message: ' . $exception->getMessage(), 'error' );
		drupal_set_message('Exception Trace: ' . $exception->getTraceAsString(), 'error');
	}
}

 /**
  * function to set the parameter for any post type curl requests.
  */
function local_curl_service_setParameter($key, $value, $parameter="") {
	if ($parameter === "" || strlen($parameter) == 0) {
		$parameter = $key . '=' . $value;
	} else {
		$parameter .= '&' . $key . '=' . $value;
	}
	return $parameter;
}

