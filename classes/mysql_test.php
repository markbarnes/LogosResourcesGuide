<?php

	$host = '127.0.1.1';
	$dbase = 'logos_newtome';
	$username = 'logos_newtome';
	$password = 'H1nB#rYT$!yYuDp5!8t^oymIiWLg%G3t';

	//PDO
	$db = new PDO ("mysql:host={$host};dbname={$dbase};charset=utf8", $username, $password, array(PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES 'utf8'"));
	for ($i = 0; $i < 10; $i++) {
		$db->exec('SELECT * FROM types ORDER BY rand() LIMIT 0, 50');
	}

	// mysqli
	$db = mysqli_connect($host, $username, $password, $dbase);
	$statement = mysqli_prepare ($db, 'SELECT * FROM types ORDER BY rand() LIMIT 0, 50');
	for ($i = 0; $i < 10; $i++) {
		mysqli_stmt_execute($statement);
	}

	// mysqli (OOP)
	$link = new mysqli ($host, $username, $password, $dbase);
	$statement = $link->prepare ('SELECT * FROM types ORDER BY rand() LIMIT 0, 50');
	for ($i = 0; $i < 10; $i++) {
		$statement->execute();
	}

?>
