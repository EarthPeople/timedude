<?php

include('../includes/config.php');

function createDateRangeArray($strDateFrom,$strDateTo)
{

    $aryRange=array();

    $iDateFrom=mktime(1,0,0,substr($strDateFrom,5,2), substr($strDateFrom,8,2),substr($strDateFrom,0,4));
    $iDateTo=mktime(1,0,0,substr($strDateTo,5,2), substr($strDateTo,8,2),substr($strDateTo,0,4));

    if ($iDateTo>=$iDateFrom)
    {
        array_push($aryRange,date('Y-m-d',$iDateFrom)); // first entry
        while ($iDateFrom<$iDateTo)
        {
            $iDateFrom+=86400; // add 24 hours
            array_push($aryRange,date('Y-m-d',$iDateFrom));
        }
    }
    array_pop($aryRange);
    return $aryRange;
}
function getProperColor($range, $value)
{
    foreach($range as $key => $color)
    {
        if ($value <= $key)
            return $color;
    }
    return $color;
}
?><html>
<meta charset="UTF-8">
<body>

<?php
if(isset($_GET['uid'])){

	$daterange['thismonth'] = date('Y-m');
	$daterange['lastmonth'] = date('Y-m', strtotime('last month'));
	$daterange['nextmonth'] = date('Y-m', strtotime('next month'));

	$dates = createDateRangeArray($daterange['lastmonth'].'-01', $daterange['nextmonth'].'-01');

	$old_times = R::findAll('times',' MD5(user) = ? ORDER BY created_at DESC LIMIT 500', array($_GET['uid']));
	if($old_times){
		$dates_worked = array();
		foreach($old_times as $old_time){
			$dates_worked[$old_time->created_at][$old_time->channel] = $old_time->hours;
		}
	}
	ksort($dates_worked);

	if($old_times){

		echo "<h1>TIMEDUDE CAL VIEW</h1>";

		$this_month = R::getRow('SELECT SUM(hours) as total_hours FROM times WHERE MD5(user) = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?', 
			array($_GET['uid'], date('Y'), date('m'))
		);
		$last_month = R::getRow('SELECT SUM(hours) as total_hours FROM times WHERE MD5(user) = ? AND YEAR(created_at) = ? AND MONTH(created_at) = ?', 
			array($_GET['uid'], date('Y', strtotime('last month')), date('m', strtotime('last month')))
		);

		echo "This month: ".($this_month['total_hours']+0).' hours';
		echo "<br/>";
		echo "Last month: ".($last_month['total_hours']+0).' hours';
		echo "<br/>";
		echo "<br/>";

		echo '<table border="1">';
		rsort($dates);
		foreach($dates as $date){
			$datestr = '';
			$datehours = 0;

			if($dates_worked[$date]){
				foreach($dates_worked[$date] as $project=>$hours){
					$datestr .= ''.$project.': '.$hours.', ';
					$datehours += $hours;
				}
				$datestr = trim($datestr, ', ');
			}
			$colorRange = array(
				2 => 'red',
				4 => 'orange',
				6 => 'green'
			);
			$datebgcolor = getProperColor($colorRange, $datehours);
			
			$dayofweek = date('N', strtotime($date));

			if($dayofweek == 6 || $dayofweek == 7){

				$datebgcolor = "#ccc";
			}
?>
		<tr>
			<td><?php echo date('D', strtotime($date))?> <?php echo $date?></td>
			<td bgcolor="<?php echo $datebgcolor?>"><?php echo $datehours?></td>
			<td><?php echo $datestr?></td>

		</tr>
<?php
			if(date('j', strtotime($date)) == 1){
?>
		<tr>
			<td colspan="3"> - </td>
		</tr>
<?php
			}
		}
		echo '<table>';
	}
}
?>


</body>
</html>
