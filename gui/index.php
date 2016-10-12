<?php

include('../includes/config.php');

?><html>
<meta charset="UTF-8">
<body>

<?php
if(isset($_GET['view']) && $_GET['view'] == 'resources' && isset($_GET['resource'])){
	# RESOURCES

	$old_times = R::findAll('times',' user = ? ORDER BY created_at DESC LIMIT 200', array($_GET['resource']));
	if($old_times){

		echo "<h1>Resource: ".$_GET['resource']."</h1>";

		$this_month = R::getRow('SELECT SUM(hours) as total_hours FROM times WHERE user = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?', 
			array($_GET['resource'], date('Y'), date('m'))
		);
		$last_month = R::getRow('SELECT SUM(hours) as total_hours FROM times WHERE user = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?', 
			array($_GET['resource'], date('Y', strtotime('last month')), date('m', strtotime('last month')))
		);

		echo "This month: ".($this_month['total_hours']+0).' hours';
		echo "<br/>";
		echo "Last month: ".($last_month['total_hours']+0).' hours';
		echo "<br/>";
		echo "<br/>";

		echo '<table border="1">';
		foreach($old_times as $old_time){
?>
		<tr>
			<td>
				<?php echo $old_time->created_at?>
			</td>
			<td>
				<?php echo $old_time->hours?>
			</td>
			<td>
				<?php echo $old_time->channel?>
			</td>
			<td>
				<?php echo $old_time->description?>
			</td>
		</tr>
<?php
		}
		echo '<table>';
	}


}else if(isset($_GET['view']) && $_GET['view'] == 'projects' && isset($_GET['project'])){
	# LIST PROJECT ACTIVITY

	$old_times = R::findAll('times',' channel = ? ORDER BY created_at DESC', array($_GET['project']));
	if($old_times){

		echo "<h1>Project: ".$_GET['project']."</h1>";

?>
	<table border="10">
		<td valign="top">
			<h2>This month</h2>
<?php
		$this_month = R::getAll('SELECT SUM(hours) as h, user FROM times WHERE channel = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ? GROUP BY user', 
			array($_GET['project'], date('Y'), date('m'))
		);
		if($this_month){
			foreach($this_month as $user){
				echo $user['user'].": ".$user['h']." hours";
				echo "<br/>";
				$total_h += $user['h'];
			}
		}
		echo "- TOTAL: ".$total_h.' hours';
		unset($total_h);
?>
	</td><td valign="top">
		<h2>Last month</h2>
<?php
		$last_month = R::getAll('SELECT SUM(hours) as h, user FROM times WHERE channel = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ? GROUP BY user', 
			array($_GET['project'], date('Y', strtotime('last month')), date('m', strtotime('last month')))
		);
		if($last_month){
			foreach($last_month as $user){
				echo $user['user'].": ".$user['h']." hours";
				echo "<br/>";
				$total_h += $user['h'];
			}
		}
		echo "- TOTAL: ".$total_h.' hours';
		unset($total_h);
?>
	</td>
</table>

<?php

		echo "<br/><br/>";

		echo '<table border="1">';
		foreach($old_times as $old_time){
?>
		<tr>
			<td>
				<?php echo $old_time->created_at?>
			</td>
			<td>
				<?php echo $old_time->hours?>
			</td>
			<td>
				<?php echo $old_time->user?>
			</td>
			<td>
				<?php echo $old_time->description?>
			</td>
		</tr>
<?php
		}
		echo '<table>';
	}




}else{
	# DASHBOARD

?>
<h1>Timedude GUI</h1>

<h2>Projects</h2>
<ul>
<?php
	$channels = R::findAll('channels', 'ORDER BY channel ASC');
	if($channels){
		foreach($channels as $channel){
?>
		<li><a href="/timedude/gui/?view=projects&amp;project=<?php echo $channel->channel?>"><?php echo $channel->channel?></a></li>
<?php
		}
	echo "</table>";
	}
?>
	</li>
</ul>

<h2>Resources</h2>
<ul>
<?php
	$users = R::getAll('SELECT user FROM times GROUP by user ORDER BY user ASC');
	if($users){
		foreach($users as $user){
?>
		<li><a href="/timedude/gui/?view=resources&amp;resource=<?php echo $user['user']?>"><?php echo $user['user']?></a></li>
<?php
		}
?>
</ul>
<?php
	}
?>

<?php
}
?>


</body>
</html>
