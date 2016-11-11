<?php
	set_include_path('../../');

	require_once 'config/Environments.php';
	require_once 'config/MyEnv.php';
	require_once 'php/data/LoginSession.php';
	$user = null;
	try {
		$obj = new LoginSession();
		echo 'Got the object. Running testFetchUser.....<br>';
		$user = $obj->testFetchUser('mm', 'clicktate1'); //loginsession.php
	}
	catch (Exception $e) {
		echo 'Got ERROR: <pre>' . $e->getMessage() . '</pre>';
	}
	
	echo 'User is a ' . gettype($user) . ' ' . var_dump($user);
	
	$testPassed = false;
	
	if (gettype($user) == 'Object') {
		$testPassed = true;
	}
	
	include('postTestProcedures.php');
?>