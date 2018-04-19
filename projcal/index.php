<?php
include('../includes/config.php');
?><html>
<meta charset="UTF-8">
<body>

<?php
if(isset($_GET['uid'])){
	$old_times = R::findAll('times',' MD5(channel) = ? AND created_at>"2017-01-01" ORDER BY created_at DESC LIMIT 5000', array($_GET['uid']));
	echo "<h1>TIMEDUDE PROJECT OVERVIEW</h1><table>";
	foreach($old_times as $old_time){
?>
		<tr>
			<td><?php echo $old_time->created_at?></td>
			<td><?php echo $old_time->hours?></td>
			<td><?php echo $old_time->description?></td>
			<td><?php echo $old_time->user?></td>
		</tr>
<?php
		echo '<table>';
	}
}
?>


</body>
</html>
