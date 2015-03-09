<?php
/**
 * @file
 * A block module that displays recent blog and forum posts.
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
 
function current_posts_help($path, $arg) {
	switch (strtolower($path)){
		case "admin/help#current_posts":
			return '<p>' . t("Displays links to nodes created on this date") . '</p>';
			break;
	}	

}

/**
 * Implements hook_permission().
 */
function current_posts_permission() {
  return array(
    'access current_posts content' => array(
      'title' => t('Access content for the Current posts module'),
    )
  );
}


/**
 * Implements hook_menu().
 */

function current_posts_menu() {
	$items = array();

	$items['admin/config/content/current_posts'] = array(
	'title' => 'Current posts',
	'description' => 'Configuration for Current posts module',
	'page callback' => 'drupal_get_form',
	'page arguments' => array('current_posts_formcurrent_posts_form'),
	'access arguments' => array('access administration pages'),
	'type' => MENU_NORMAL_ITEM,
	);
	$items['current_posts'] = array(
		'title' => 'Current posts',
		'page callback' => '_current_posts_page',
		'access arguments' => array('access current_posts content'),
		'type' => MENU_CALLBACK, //Will appear in Navigation menu.
	  );

  return $items;
}
 
/**
 * Implements hook_block_info()
 */
function current_posts_block_info() {
	$blocks['current_posts']	= array(
		// the name that will appear in the block list.
		'info' => t('Current posts'),
		// Default setting.
		'cache' => DRUPAL_CACHE_PER_ROLE,
		);
	return $blocks;
}

/**
 * Custom content function
 *
 * Set beginning and end dates, retrieve posts from database
 * saved in that time period.
 *
 * @return
 *   A result set of the targeted posts.
 */
function current_posts_contents($display) {
  //Get today's date.
  $today = getdate();
  //Calculate midnight a week ago.
  $start_time = mktime(0, 0, 0,$today['mon'],($today['mday'] - 7), $today['year']);
  //Get all posts from one week ago to the present.
  $end_time = time();

 //NEW LINE 
 $max_num = variable_get('current_posts_max', 3);

  //Use Database API to retrieve current posts.
  $query = db_select('node', 'n')
    ->fields('n', array('nid', 'title', 'created'))
    ->condition('status', 1) //Published.
    ->condition('created', array($start_time, $end_time), 'BETWEEN')
    ->orderBy('created', 'DESC'); //Most recent first. Query paused here

	if (strtolower($display) == 'block') {
	// restrict the range if called with the 'block' argument
	$query->range(0, $max_num);
	} // now execute
	// if called by page, query proceeds directly to execute().
	return $query->execute();
}
/**
 * Implements hook_block_view().
 * 
 * Prepares the contents of the block.
 */
function current_posts_block_view($delta = '') {
  switch ($delta) {
    case 'current_posts':
      $block['subject'] = t('Current posts');
      if (user_access('access content')) {
        // Use our custom function to retrieve data.
        $result = current_posts_contents('block');
        // Array to contain items for the block to render.
        $items = array();
        // Iterate over the resultset and format as links.
        foreach ($result as $node) {
          $items[] = array(
            'data' => l($node->title, 'node/' . $node->nid),
          ); 
        }
       // No content in the last week.
        if (empty($items)) {
          $block['content'] = t('No posts available.');  
        } 
        else {
          // Pass data through theme function.
		  $block['content']['posts']	= array(
			'#theme'	=> 'item_list__current_posts__block',
			'#items'	=> $items,
		  );
		  /*
          $block['content'] = theme('item_list', array(
            'items' => $items));
			*/
 		$block['content']['more']	= array(
			'#theme'	=> 'more_link__current_posts',
			'#url'		=> 'current_posts',
			'#title'	=> t('See the full list of current posts.'),
		);
       }
      }
    return $block;
  }
 }

 
/**
 * Custom page callback function, declared in current_posts_menu()
 */
	function _current_posts_page() {
		$results	= current_posts_contents('page');
		// Array to contain items for the page to render
		$items	= array();
		// Iterate over the result set amd form as links
		foreach ($results as $node) {
			$items[]	= array(
			
			'data'	=> l($node->title, 'node/' . $node->nid),
			);
		}
		
		if (empty($items)) { // no content in the last week
			$page_array['current_posts_arguments'] = array(
				// Titlt serves as a page subtitle
				'#title' 	=> t('All posts from the last week'),
				'#markup'	=> t('No posts avaiable'),
			);
		} else {
			$page_array['current_posts_arguments'] = array(
				"#title"	=> t('All posts from the past week'),
				"#items"	=> $items,
				// Theme hook with suggestion
				'#theme'	=> 'item_list__current_posts',
			);
		}
	return $page_array;
	}
	
	
/**
 * Page callback: Current posts settings
 *
 * @see current_posts_menu()
 */
function current_posts_form($form, &$form_state) {
  $form['current_posts_max'] = array(
    '#type' => 'textfield',
    '#title' => t('Maximum number of posts'),
    '#default_value' => variable_get('current_posts_max', 3),
    '#size' => 2,
    '#maxlength' => 2,
    '#description' => t('The maximum number of links to display in the block.'),
    '#required' => TRUE,
  );

  return system_settings_form($form);
}
/**
 * Implements validation from the Form API.
 * 
 * @param $form
 *   A structured array containing the elements and properties of the form.
 * @param $form_state
 *   An array that stores information about the form's current state 
 *   during processing.
 */
function current_posts_form_validate($form, &$form_state){
  $max_num = $form_state['values']['current_posts_max'];
  if (!is_numeric($max_num)){
    form_set_error('current_posts_max', t('You must enter a number for the maximum number of posts to display.'));
  }
  elseif ($max_num <= 0){
    form_set_error('current_posts_max', t('Maximum number of posts to display must be positive.'));
  }
}

