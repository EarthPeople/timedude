<?php

include('includes/config.php');

$error = false;

if(!isset($_POST['text'])){
	echo "Error.";
	exit(0);
}

if($_POST['channel_name'] === 'directmessage' || $_POST['channel_name'] === ''){

	$words = explode(' ', $_POST['text']);
	$command = array_shift($words);
	$params = implode($words, ' ');
	$user = $_POST['user_name'];

	if($command === 'me'){
		$old_times = R::findAll('times',' user = ? ORDER BY created_at DESC, channel ASC LIMIT 50', array($user));
		if($old_times){
			foreach($old_times as $old_time){
				$response .= $old_time->created_at.': '.$old_time->channel.' '.$old_time->hours.'h - '.$old_time->description;
				$response .= "\n";
			}
		}else{
			$response = 'Nothing here yo';
			$error = true;
		}
	}else if($command === 'listprojects'){
		$channels = R::findAll('channels');
		if($channels){
			foreach($channels as $channel){
				$response .= '#'.$channel->channel.' ';
			}
		}
	}else if($command === '' || $command === 'help'){
		$response = help();
	}
	#$response = json_encode($command);

	#echo "Hey, you can't message this bot. Use a shared channel.";
	#$error = true;
	#print_r($_POST);
}else{

	$words = explode(' ', $_POST['text']);
	$channel = $_POST['channel_name'];
	$command = array_shift($words);
	$params = implode($words, ' ');
	$user = $_POST['user_name'];

	# functionality start
	if($command === 'add'){

		$is_enabled = R::findOne('channels','channel = ?', array($channel));
		if(!$is_enabled){
			$response = 'This is not a timedude channel!';
			$error = true;
		}else{

			$args = explode(' ', $params);
	
			# which date?
			if(strtolower($args[0]) === 'yesterday'){
				$date_to_add = date('Y-m-d', strtotime('yesterday'));
				array_shift($args); #remove the word yesterday
			}else if(seems_to_be_date($args[0])){
				$date_to_add = $args[0];
				array_shift($args); #remove the word containing the specific date
			}else{
				$date_to_add = date('Y-m-d'); #today
			}
	
			$args[0] = trim($args[0],'h');
			if($hours = floatval($args[0])){
				array_shift($args); #remove first word, contains the hours-value
				if(strlen($args[0]) > 2){
					$times = R::dispense('times');
					$times->channel = $channel;
					$times->user = $user;
					$times->hours = $hours;
					$times->description = implode($args, ' ');
					$times->created_at = $date_to_add;
					R::store($times);
					$response = 'Added '.$hours.'h to this project for '.$date_to_add.' '.random_emoji()."\nType clear if you wish to remove this entire day from your reports.";
				}else{
					$response = 'You need to provide some kind of description, like 8h haxxing';
					$error = true;
				}
			}else{
				$response = 'You need to start with some hours, like 8h haxxing';
				$error = true;
			}
		}
	
	}else if($command === 'clear'){

		# which date?
		if(strtolower($params) === 'yesterday'){
			$date_to_add = date('Y-m-d', strtotime('yesterday'));
		}else if(seems_to_be_date($params)){
			$date_to_add = $params;
		}else{
			$date_to_add = date('Y-m-d'); #today
		}

		$old_times = R::findAll('times',' user = ? AND channel = ? AND created_at = ?', array($user, $channel, $date_to_add));
		if($old_times){
			foreach($old_times as $old_time){
				R::trash($old_time);
			}
			$response = 'Ok, cleared your day. You currently have 0 hours reported '.$date_to_add;
		}else{
			$response = 'No need to clear your day, there\'s nothing there...';
			$error = true;
		}
	
	}else if($command === 'export'){
		
		$csvfile = $channel."_".date('Ymdhis');
		R::exec("SELECT created_at, user, hours, description FROM times WHERE channel = ? ORDER BY created_at, user INTO OUTFILE '".$csvpath.$csvfile.".csv' FIELDS TERMINATED BY ',' ENCLOSED BY '\"' LINES TERMINATED BY '\n';", array($channel));
		$response = "Download the full report for this project here:\nhttp://miscbox.earthpeople.se/timedude/csv/".$csvfile.".csv";

	}else if($command === 'list'){

		if((int)$params > 0){
			$limit = $params;
		}else{
			$limit = 100;
		}

		$old_times = R::findAll('times',' user = ? AND channel = ? ORDER BY created_at ASC LIMIT '.$limit, array($user, $channel));
		if($old_times){
			foreach($old_times as $old_time){
				$response .= $old_time->created_at;
				$response .= " ";
				$response .= $old_time->hours;
				$response .= "h: ";
				$response .= $old_time->description;
				$response .= "\n";
			}
		}else{
			$response = 'Nothing, nada, etc';
			$error = true;
		}

	}else if($command === 'listall'){

		if((int)$params > 0){
			$limit = $params;
		}else{
			$limit = 100;
		}

		$old_times = R::findAll('times',' channel = ? ORDER BY created_at ASC LIMIT '.$limit, array($channel));
		if($old_times){
			foreach($old_times as $old_time){
				$response .= $old_time->created_at;
				$response .= " ";
				$response .= $old_time->hours;
				$response .= "h: ";
				$response .= $old_time->user;
				$response .= ": ";
				$response .= $old_time->description;
				$response .= "\n";
			}
		}else{
			$response = 'Nothing, nada, etc';
			$error = true;
		}


	}else if($command === 'enable'){
		
		$old_channel = R::findOne('channels','channel = ?', array($channel));
		if($old_channel){
			$response = 'Channel already enabled';
		}else{
			$channels = R::dispense('channels');
			$channels->channel = $channel;
			R::store($channels);
			$response = 'Ok, enabled this channel';
		}

	}else if($command === 'disable'){
		
		$old_channel = R::findOne('channels','channel = ?', array($channel));
		if($old_channel){
			R::trash($old_channel);
			$response = 'Ok, disabled this channel';
		}else{
			$response = 'This channel was already disabled';
		}

	}else if($command === 'reminder'){
	
		if(strtoupper($params) === 'OFF'){
			$old_reminder = R::findOne('reminders',' user = ? AND channel = ?', array($user, $channel));
			if($old_reminder){
				R::trash($old_reminder);
				$response = 'Ok, you won\'t get any more reminders';
			}else{
				$response = 'You have do reminders but sure whatever';
			}
	
		}else if($time = valid_time($params)){
			$old_reminder = R::findOne('reminders',' user = ? AND channel = ?', array($user, $channel));
			if($old_reminder){
				R::trash($old_reminder);
			}
			$reminders = R::dispense('reminders');
			$reminders->channel = $channel;
			$reminders->user = $user;
			$reminders->time = $time;
			$reminders->created_at = date('Y-m-d');
			R::store($reminders);
			$response = 'Yeah, your reminder for this project is set for '.$params;
		}else{
			$response = 'Uh I could not understand you. Enter time, like 16:30 or OFF';
			$error = true;
		}
	}else if($command === 'help'){
		$response = help();
	}else{
		$response = ':paperclip: /timedude help for man page';
		$error = true;
	}
}

function help(){
	$str = "Available commands in a channel:\n";
	$str .= "add <HOURS> i made a funny gif\n";
	$str .= "add ".date('Y-m-d', strtotime('yesterday'))." <HOURS> i made a funny gif\n";
	$str .= "clear (will clear your hours in this project for today)\n";
	$str .= "export (generates a csv of the entire project)\n";
	$str .= "list (shows your reported stuff for this project)\n";
	$str .= "listall (shows everyones stuff for this project)\n";
	$str .= "reminder <hh:mm or OFF>\n";
	$str .= "enable (enabled time reporting for this channel)\n";
	$str .= "disable (disable time reporting for this channel)\n";
	$str .= "help\n\n";
	$str .= "Available commands to @slackbot:\n";
	$str .= "me (shows you all your hours reported lately)\n";
	$str .= "listprojects (shows you all timedude projects)\n";
	$str .= "help";
	return $str;
}

if($error){
	$errors = R::dispense('errors');
	$errors->raw_post = json_encode($_POST);
	$errors->user = $user;
	$errors->created_at = date('Y-m-d H:i:s');
	R::store($errors);
}

# respond!
echo $response;


function valid_time($str = ''){
	
	$hours = null;
	$minutes = null;

	$timeparts = explode(':', $str);

	if(isset($timeparts[0])){
		if($timeparts[0] <= 23 && $timeparts[1] >= 00){
			$hours = $timeparts[0];
		}
	}

	if(isset($timeparts[1])){
		if($timeparts[1] < 59 && $timeparts[1] >= 00){
			$minutes = $timeparts[1];
		}
	}

	if($hours && $minutes){
		return $hours.':'.$minutes;
	}else{
		return false;
	}

}

function seems_to_be_date($date = ''){
	return preg_match( '#^(?P<year>\d{2}|\d{4})([- /.])(?P<month>\d{1,2})\2(?P<day>\d{1,2})$#', $date, $matches ) && checkdate($matches['month'],$matches['day'],$matches['year']);
}

function random_emoji(){
	$emojis = array('smile','laughing','blush','smiley','heart_eyes','kissing_heart','kissing_closed_eyes','raised_hands','clap','muscle','mudderverk');
	return ':'.$emojis[array_rand($emojis)].':';
}
