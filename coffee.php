<?php

	$auth_token = '[SLACK AUTH TOKEN HERE]';

	$trigger_word = '!coffee';
	$responses = array(
		"It's about time {{Aaron}} put the kettle on - off you trot!",
		"Pop the kettle on {{Jim}} - it's your turn to make a cuppa",
		"Who wants a drink? {{Aaron}} is heading to the kitchen to make one",
		"Coffee? Tea? Sugar? Battery acid? Get your orders in as {{Jim}} is making a round",
		"That's very nice of {{Aaron}} to make a round of tea!",
		"Mine is milk 2 sugars, please, {{Jim}} - what about everyone else?",
		"The coffee maker is... {{Aaron}}! Get brewing."
	);

	// Include slack library from https://github.com/10w042/slack-api
	include 'coffee_class.php';

	// Remove keyword from the text to extract name to exclude
	$exclude = str_replace($trigger_word . ' ', '', $_POST['text']);

	// Connect to Slack
	// Use authentication token found here: https://api.slack.com/
	// Scroll to the bottom and issue a token
	$Slack = new Slack($auth_token);

	// Get the info for the channel requested from
	$data = $Slack->call('channels.info', array('channel' => $_POST['channel_id']));

	$coffeeMakers = array();

	// Loop through channel members
	foreach ($data['channel']['members'] as $m) {
		// Get user data
		$userData = $Slack->call('users.info', array('user' => $m));
		// Check to see if the user is online before adding them to list of brewers
		$presence = $Slack->call('users.getPresence', array('user' => $m));

		$user = $userData['user'];

		// If there is an exclude, check to see if it matches a user real name (lowercase)
		// If it does not, add it to the $coffeeMakers array
		if($presence['presence'] == 'active')
			if($exclude) {
				if(!(strpos(strtolower($user['real_name']), strtolower($exclude)) !== false))
					$coffeeMakers[] = $user;
			} else {
				$coffeeMakers[] = $user;
			}
	}

	// Shuffle shuffle shuffle the arrays
	function pickOne($array) {
		shuffle($array);
		return $array[mt_rand(0, (count($array) - 1))];
	}

	// Get a random user from the array
	$user = pickOne($coffeeMakers);

	// SEND OUT THE JSON!! Enjoy your brew
	header('Content-Type: application/json');
	echo json_encode(array(
		'text' => str_replace('{{USER}}', '<@' . $user['id'] . '>', pickOne($responses))
	));

