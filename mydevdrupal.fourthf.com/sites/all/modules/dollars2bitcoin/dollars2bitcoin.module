<?php

// what needs to be in a module:
/*
 * 1. overall @file comment
 * 2. hook_help implementation
 * 3. hook_permission implementation
 * 4. hook_menu implementation
 * 5. custom functions for getting stuff done
 * 6. hook_block_info implementation
 * 7. hook_block_view implementation
 * 8. hook_form implementation
 * 9. hook_form_validate implementation
 * 10. hook_form_submit implementation (for special things to do before storing data??)
 */
 
/**
 * @file
 * A block module that displays a form to allow users to enter a USD amount less than $1.00 and get the bitcoin equivalent.
 */

 /**
 * Implements hook_help().
 *
 * Displays help and module information.
 *
 * @param path 
 *   Which path of the site we're using to display help
 * @param arg 
 *   Array that holds the current path as returned from arg() function
 */ 
 
function dollars2bitcoin_help($path, $arg) {
	switch (strtolower($path)){
		case "admin/help#dollars2bitcoin":
			return '<p>' . t("Displays a page that allows one to enter USD money amounts (less than $1.00) and get the bitcoin equivalent") . '</p>';
			break;
	}	
}
/// TODO - permissions for the page that lets an admin enter the API credentials for the bitcoin api
/**
 * Implements hook_permission().
 */
function dollars2bitcoin_permission() {
  return array(
    'access dollars2bitcoin content' => array(
      'title' => t('Access content for the Dollars to Bitcoin conversion module'),
    ),
	'access api keys pages' => array(
		'title' => t('Access to add API Keys'),
	),
  );
}

 
/**
 * Implements hook_block_info()
 */
function dollars2bitcoin_block_info() {
	$blocks['dollars2bitcoin']	= array(
		// the name that will appear in the block list.
		'info' 	=> t('Dollars to Bitcoin Conversion'),
		// Default setting.
		'cache' => DRUPAL_CACHE_PER_ROLE,
		);
	return $blocks;
}

/// need 2 urls via the hook_menu implementation
/// 1. to the home page where one enters the dollar amount
/// 2. to the admin config section where an admin can enter API credentials
function dollars2bitcoin_menu() {
	$items	= array();
	$items['dollars2bitcoin']	= array(
		'title'				=> 'Dollars to Bitcoin',
		'description'		=> 'Enter dollars and submit to get bitcoin equivalent',
		'page callback'		=> 'drupal_get_form',
		'page arguments'	=> array('dollars2bitcoin_conversion_form'),
		'access arguments'	=> array('access dollars2bitcoin content'),
		'type'				=> MENU_NORMAL_ITEM
		);
	$items['admin/config/content/dollars2bitcoin'] 	= array(
		'title'				=> 'Dollars to Bitcoin',
		'description'		=> 'Enter API credentials for the Bitcoin API',
		'page callback'		=> 'drupal_get_form',
		'page arguments'	=> array('dollars2bitcoin_api_admin_form'),
		'access arguments'	=> array('access api keys pages'),
		'type'				=> MENU_NORMAL_ITEM // appears in Navigation section
		);
	return $items;
}
/*
 * form to enter a dollar amount, submit, call an API, & return BitCoin value
 */
function dollars2bitcoin_conversion_form($form, &$form_state) {
  $form['dollars2bitcoin_dollar_amount'] = array(
    '#type' 			=> 'textfield',
    '#title' 			=> t('Enter Dollar Amount to Convert to Bitcoin (Don\'t enter the "$")'),
    '#default_value' 	=> variable_get('dollars2bitcoin_dollar_amount'),
    '#size' 			=> 15,
    '#maxlength' 		=> 30,
    '#description' 		=> t('Enter the dollar amount (USD) you want to convert to Bitcoin value'),
    '#required' 		=> TRUE,
  );
  $form['submit'] = array(
    '#type' => 'submit',
    '#value' => t('Get Conversion'),
  );
	return $form;
  //return system_settings_form($form);
}

/**
 * validate the submission of the conversion form
 */
function dollars2bitcoin_conversion_form_validate($form, &$form_state) {

// TODO - validate that the submitted value is a dollar value
	if ($form_state['values']['dollars2bitcoin_dollar_amount'] == "666") {
		form_set_error('dollars2bitcoin_dollar_amount', t('Are you the devil?!'));
		return;
	}
}

/**
 * hook_submit
 */
function dollars2bitcoin_conversion_form_submit($form, &$form_state) {

	
	/*
	* Huddle up -- here's the story. The Open Exchange Rates Free API account limits API calls to 1,000/month
	* also the latest.json call gets exchange rates that are updated every 1 hour
	* So, let's do some management here:
	* 1. Store the BitCoin exchange rate in the variable table and add a date/time stamp
	* 2. Whenever the conversion rate form is submitted, let's:
	*    2a. Grab the Bitcoin rate from the variable table
	*    2b. Check if the date/time stamp is older than 1 hour or if there is no value in the variable table
	*        2b(1) Yes:
	*				2b(1)(a) get the latest exchange rate via cURL
	*				2b(1)(b) store only the BitCoin exchange rate plus a date/time stamp
	*				2b(1)(c) Use this exchange rate to calculate the dollars to Bitcoin exchange
	*		 2b(2) No:
	*		 		2b(2)(a) Use the stored exchange rate to calculate the dollars to Bitcoin exchange
	* Ready... break!
	*/
	
	variable_set('dollars2bitcoin_dollar_amount', $form_state['values']['dollars2bitcoin_dollar_amount']);
	// first - ensure the submitted number is a USD format
	if (!preg_match('#^[0-9]+(\.[0-9]{0,2})?$#', $form_state['values']['dollars2bitcoin_dollar_amount']) ) {
	// giving credit:
	// http://chrishacia.com/2012/03/php-verify-a-dollar-amount/
		drupal_set_message(t('Sorry mate, but it looks like you submitted a value that is not a US Dollar: [' . $form_state['values']['dollars2bitcoin_dollar_amount'] . ']. I would suggest you try again?'), 'error');
		return;
	}
	$saved_dollars2bitcoin_exchange_rate	= variable_get('dollars2bitcoin_exchange_rate');
	$use_saved_exchange_rate				= false; // initialize
	if ($saved_dollars2bitcoin_exchange_rate) {
		//drupal_set_message(t('woot - found a saved rate: ' . $saved_dollars2bitcoin_exchange_rate . '.'), 'status');
		
		if (_dollars2bitcoin_is_exchange_rate_current() ) {
			//drupal_set_message(t('awesome -- our stored exchange rate is current (less than an hour old).'), 'status');
			$use_saved_exchange_rate	= true;
		} else {
			$use_saved_exchange_rate	= false;
		}
	}
	if ($use_saved_exchange_rate ){
	
		//drupal_set_message(t("Woot we have a current BTC conversion rate in our database - won't this save time and resources!"), 'status');
	
		// let's split stuff up please (we store the BitCoin rate and date/time as a concatenated value with "|||||" between the values)
		// ---> Why? The variable table doesn't have a date/time field so let's just append the date/time to the value, shall we -- see, simple solution
		
		list($bitcoin_exchange_rate, $date_time_saved) 		= explode("|||||", variable_get('dollars2bitcoin_exchange_rate'));
		/// now we need to see if the date/time stamp is older than 1 hour
		
		$dollars2bitcoin_exchange	=  $form_state['values']['dollars2bitcoin_dollar_amount'] * $bitcoin_exchange_rate;
	
	} else { // well what a bummer - we don't have an exchange rate in the table or it's over 1-hour old -- let's get the current rate shall we?
	
		$curl_url 					= variable_get('dollars2bitcoin_curl_url');
		$bitcoin_app_id				= variable_get('dollars2bitcoin_api_key');
		
		if (!$curl_url || !$bitcoin_app_id) {
			drupal_set_message(t('Sorry, it appears we were not able to get the BitCoin Exchange Rate. Please ensure the IT Department knows about this problem'), 'error');
			return;
		}
		$curl_url					.= "?app_id=$bitcoin_app_id";
		$conversion_rates_json 		= local_curl_service_send_request($curl_url);
		
		/// we need to get only the BTC value of the returned json object
		$bitcoin_exchange_rate		= json_decode($conversion_rates_json)->rates->BTC;
		/// then we need to get the current date time
		$date_time_saved			= date("Y-m-d H:i:s");
		/// then we need to set the variable
		variable_set('dollars2bitcoin_exchange_rate', $bitcoin_exchange_rate . "|||||" . $date_time_saved);
		
		$dollars2bitcoin_exchange	=  $form_state['values']['dollars2bitcoin_dollar_amount'] * $bitcoin_exchange_rate;
		
	}
	drupal_set_message(t('The BitCoin Exchange rate is: ' . $bitcoin_exchange_rate), 'status');
	drupal_set_message(t('Here is your conversion: $' . $form_state['values']['dollars2bitcoin_dollar_amount'] . ' = '. $dollars2bitcoin_exchange .' Bitcoins!'), 'status');
	return;
}


/**
 * function to calculate if the stored BitCoin exchange rate (previously retrieved) is less than an hour old
 * @return true or false
 */
function _dollars2bitcoin_is_exchange_rate_current() {
	//drupal_set_message(t('well i made it here! AND the dollars2bitcoin_ehchange_rate was: ' .variable_get('dollars2bitcoin_exchange_rate')), 'status');
	///	 comparing datetime stamps
	// date_default_timezone_set('America/Phoenix'); /// NEEDED?
	list($bitcoin_rate, $date_timestored)	= explode("|||||", variable_get('dollars2bitcoin_exchange_rate'));
	//drupal_set_message(t('The BitCoin Exchange rate is: ' . $bitcoin_rate), 'status');
	$date_to_timestored4math				= strtotime($date_timestored);
	$current_date_time						= strtotime(date("Y-m-d H:i:s"));
	
	///echo "<hr />current: " . date("Y-m-d H:i:s") . "<hr />";


	// we need to current time minus stored time and check the difference >1
	// echo "Date Stored: $date_stored <br />";date_timestored
	// echo "Now: \t\t $current_date_time<br />";

	// echo "need the difference between now and when the bitcoin XR was stored:<br />";
	$max_difference	=	($current_date_time - $date_to_timestored4math)/ (60 * 60);
	//drupal_set_message(t('Here is the max_difference: ' . $max_difference), 'status');
	//echo "DIFFERENCE: " . $time_difference . "<br /><hr />";


	if ( $max_difference >= 1) {

		return false; //echo "Too old, get another!";
	} else {
		return true; //echo "less than an hour, use current!";
	}
}

/*
 * form to enter API credentials (secret squirrel stuff here - so admin only please!)
 */
function dollars2bitcoin_api_admin_form($form, &$form_state) {
  $form['dollars2bitcoin_api_key'] = array(
    '#type' 			=> 'textfield',
    '#title' 			=> t('App ID'),
    '#default_value'	=> variable_get('dollars2bitcoin_api_key'),
    '#size' 			=> 35,
    '#maxlength' 		=> 35,
    '#description' 		=> t('Enter the BitCoin App ID'),
    '#required' 		=> TRUE,
  );
  
  $form['dollars2bitcoin_curl_url']	= array(
	'#type'				=> 'textfield',
	'#title'			=> t('Conversion Rates URL'),
	'#default_value'	=> variable_get('dollars2bitcoin_curl_url'),
	'#size'				=> 150,
	'#maxlength'		=> 256,
	'#description'		=> t('Enter the base URL for the conversion API'),
	'#required'			=> TRUE,
  );

  // $form['dollars2bitcoin_api_password'] = array(
    // '#type' 			=> 'textfield',
    // '#title' 			=> t('API Password'),
    // '#default_value' 	=> variable_get('dollars2bitcoin_api_password'),
    // '#size' 			=> 35,
    // '#maxlength' 		=> 35,
    // '#description' 		=> t('Enter the API password'),
    // '#required'			=> TRUE,
  // );
  
  // $form['submit']	= array(
	// '#type'				=> 'submit',
	// '#value'			=> t('Save API Credentials')
	// );
	
	//return $form;
  return system_settings_form($form);
}

/**
 * function to validate the form when api key and passwords are set (if we need it)
 */
function dollars2bitcoin_api_admin_form_validate($form, &$form_state) {

}
/**
 * function to submit the form when api key and passwords are set (if we need it)
 */
function dollars2bitcoin_api_admin_form_submit($form, &$form_state) {
	$api_key		= variable_get('dollars2bitcoin_api_key');
	$api_curl_url	= variable_get('dollars2bitcoin_curl_url');
	$api_password	= variable_get('dollars2bitcoin_api_password');
	
	drupal_set_message("Here is the KEY: $api_key and the cURL URL: $api_curl_url ... ", 'status');
	return;

}
