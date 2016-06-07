<?php
date_default_timezone_set('Europe/Stockholm');

require('rb.php');
R::setup('mysql:host=localhost;dbname=timedude','', '');
R::setAutoResolve( TRUE );
R::freeze( TRUE );

$slacktoken = '';
$csvpath = '';
$csvurl = '';