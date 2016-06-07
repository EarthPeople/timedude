<?php

include('includes/config.php');

$error = false;

if(!isset($_POST['text'])){
	echo "Error.";
	exit(0);
}

$words = explode(' ', $_POST['text']);

$channel = $_POST['channel_name'];
$command = array_shift($words);
$params = implode($words, ' ');
$user = $_POST['user_name'];

if($channel === 'directmessage' || $channel === ''){
	echo "Hey, you can't message this bot. Use a shared channel.";
	$error = true;
}else{
	# functionality start
	if($command === 'add'){
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
				$response .= ": ";
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
				$response .= ": ";
				$response .= $old_time->user;
				$response .= ": ";
				$response .= $old_time->description;
				$response .= "\n";
			}
		}else{
			$response = 'Nothing, nada, etc';
			$error = true;
		}


	}else if($command === 'stats'){
		
		$query = R::exec("SELECT user, ROUND(SUM(hours) / (SELECT SUM(hours) FROM times WHERE channel = ?) *100, 1) as percentage FROM times WHERE channel 	= ? GROUP BY user ORDER BY percentage DESC", array($channel, $channel));
		#print_R($query);
		#$response = 'http://chart.apis.google.com/chart?chs=300x200&cht=p&chl=adam|peder|sanna&chd=t:10,20,70&chtt=This%20month';
	
		#$response = $query;
	
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
	}else{
		$response = ':paperclip: Here are the available commands: add <HOURS>, clear, export, reminder <hh:mm or OFF>';
		$error = true;
	}
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
	$emojis = array('smile','laughing','blush','smiley','relaxed','smirk','heart_eyes','kissing_heart','kissing_closed_eyes','flushed','relieved','satisfied','grin','wink','stuck_out_tongue_winking_eye','stuck_out_tongue_closed_eyes','grinning','kissing','kissing_smiling_eyes','stuck_out_tongue','sleeping','worried','frowning','anguished','open_mouth','grimacing','confused','hushed','expressionless','unamused','sweat_smile','sweat','disappointed_relieved','weary','pensive','disappointed','confounded','fearful','cold_sweat','persevere','cry','sob','joy','astonished','scream','neckbeard','tired_face','angry','rage','triumph','sleepy','yum','mask','sunglasses','dizzy_face','imp','smiling_imp','neutral_face','no_mouth','innocent','alien','yellow_heart','blue_heart','purple_heart','heart','green_heart','broken_heart','heartbeat','heartpulse','two_hearts','revolving_hearts','cupid','sparkling_heart','sparkles','star','star2','dizzy','boom','collision','anger','exclamation','question','grey_exclamation','grey_question','zzz','weat_drops','notes','musical_note','fire','thumbsup','thumbsdown','punch','facepunch','fist','wave','hand','raised_hand','open_hands','point_up','point_down','point_left','point_right','raised_hands','pray','point_up_2','clap','muscle','metal','fu','runner','running','couple','family','two_men_holding_hands','two_women_holding_hands','dancer','dancers','ok_woman','no_good','information_desk_person','raising_hand','bride_with_veil','person_with_pouting_face','person_frowning','bow','couplekiss','couple_with_heart','massage','haircut','nail_care','boy','girl','woman','man','baby','older_woman','older_man','person_with_blond_hair','man_with_gua_pi_mao','man_with_turban','construction_worker','cop','angel','princess','smiley_cat','smile_cat','heart_eyes_cat','kissing_cat','smirk_cat','scream_cat','crying_cat_face','joy_cat','pouting_cat','japanese_ogre','japanese_goblin','see_no_evil','hear_no_evil','speak_no_evil','guardsman','skull','feet','lips','kiss','droplet','ear','eyes','nose','tongue','love_letter','rat','squirrel');
	return ':'.$emojis[array_rand($emojis)].':';
}
