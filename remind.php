<?php

include('includes/config.php');

$now = date('H:i');
$weekday = date('w');
echo $now;
echo "\n";
if(!in_array($weekday, array(1,2,3,4,5))){
	exit(0);
}
$reminders = R::findAll('reminders','time = ?', array($now));
if($reminders){
	foreach($reminders as $reminder){
		echo file_get_contents('https://slack.com/api/chat.postMessage?token='.$slacktoken.'&channel=%40'.$reminder->user.'&link_names=1&username=Timedude&as_user=false&icon_emoji=neutral_face&text='.urlencode("It's time to report todays hours for #").$reminder->channel);
		echo 1;
		echo "\n";
	}
}

# clean up csv folder
if ($handle = opendir($csvpath)) {
	while (false !== ($file = readdir($handle))) { 
		$filelastmodified = filemtime($csvpath . $file);
		if((time() - $filelastmodified) > 1*3600){
			unlink($csvpath . $file);
		}
	}
	closedir($handle); 
}