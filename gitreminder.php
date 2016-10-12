<?php
include('includes/config.php');

# get buddy vs slack user translation data
$user_lookup = R::findAll('user_lookup');
if($user_lookup){
	foreach($user_lookup as $item){
		$user['slack'][$item->buddy_name] = $item->slack_name;
		$user['buddy'][$item->slack_name] = $item->buddy_name;
	}
}


# get all timedude-enabled slack chanels
$channels = R::findAll('channels');
if($channels){
	foreach($channels as $channel){

		# get all git projects for this slackchannel, there can be several
		$gits = explode(',',$channel->buddy_names);

		echo "Timedude project: ". $channel->channel;
		echo "\n";

		# get all git users wokring in the project today
		foreach($gits as $git){
			$commiting_users = array();

			if($git){
				echo "- Buddy repo: ".$git;
				echo "\n";
				$result = get_todays_commits($git);
				if($result){
					foreach($result as $item=>$message){
						$commiting_users[] = $user['slack'][$item];
					}
				}
			}

			# get all reported times in timedude for this project today
			$times = R::findAll('times', 'WHERE created_at = ? AND channel = ?', array(date('Y-m-d'), $channel->channel));
			$active_users = array();
			if($times){
				foreach($times as $time){
					$active_users[] = $time->user;
				}
			}
			$active_users = array_unique($active_users);

			# check if a user has made a commit in this project, but not used timedude for this project, today
			$unreported_users = array();
			if($commiting_users){
				foreach($commiting_users as $curruser){
					if(!in_array($curruser, $active_users)){
						$unreported_users[] = $curruser;
					}
				}
			}
			
			if($unreported_users){
				foreach($unreported_users as $unreported_user){
					echo file_get_contents('https://slack.com/api/chat.postMessage?token='.$slacktoken.'&channel=%40'.$unreported_user.'&link_names=1&username=Timedude&as_user=false&icon_emoji=neutral_face&text='.urlencode("Hey hey, it seems you did something in the git repo '".$git."' today. This is a friendly reminder to maybe add this time to timedude"));
				}
				echo "-- Notifying ".$unreported_user."\n";
			}else{
				echo "- All good.\n";
			}

			unset($unreported_users);
			unset($users);
			unset($commits);
			unset($commiting_users);

			echo "\n";

		}


	}
}



function get_todays_commits($project = ''){

	# since-requests doesn't work, will filter in php instead
	$curl_url = 'https://git.urtp.pl/api/workspaces/earth-people/projects/'.$project.'/repository/commits?page=1&per_page=250&since='.date('Y-m-d').'T00:00:00&until='.date('Y-m-d').'T23:59:59';

	$ch = curl_init($curl_url);
	#echo $curl_url;
	curl_setopt($ch, CURLOPT_HTTPHEADER, array(
		'Authorization: Bearer '.$buddytoken,
		'Content-Type: application/json'
	));
	curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
	curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
	curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
	$result = curl_exec($ch);
	#echo $result;
	curl_close($ch);
	$json_result = json_decode($result);

	$gits = array();

	if(isset($json_result->commits)){
		foreach($json_result->commits as $commit){
			if(date('Y-m-d', strtotime($commit->commit_date)) == date('Y-m-d') ){
				$gits[$commit->committer->name] = $commit->message;
				#print_r($commit);
			}
		}
	}
	return $gits;
}