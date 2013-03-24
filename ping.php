<?php
	// === configuration ===

	define('DEBUG',				1);
	define('PORT_CHECK_TIMEOUT',		3);

	ini_set('display_errors', 		DEBUG);

	error_reporting(DEBUG ? E_ALL : 0);

	header('Content-type: text/plain');

	// === functions ===

	function die_msg($msg)
	{
		if (DEBUG) {
			debug_print_backtrace();
			die($msg);
		}
		die();
	}

	function check_port($ip, $port)
	{
		return @fsockopen($ip, $port, $errno, $errstr, PORT_CHECK_TIMEOUT);
	}

	function updatedbinfo($gameinfo) {
		global $db;
		$fields = array_keys($gameinfo);

		$query  = $db->prepare('INSERT OR ABORT INTO `servers` ('.implode(', ', $fields).') VALUES (:'.implode(', :', $fields).')');
		$result = $query->execute($gameinfo);
		if (!$result) {
			$query  = $db->prepare('UPDATE OR FAIL `servers` SET '.implode('=?, ', $fields).'=? WHERE address = :address');
			$result = $query->execute(array_merge(array_values($gameinfo), array(':address' => $gameinfo['address'])));
		}

		if (DEBUG) $query->debugDumpParams();
		return $result;
	}

	// === body ===

	// make sure everything required is actually set.
	foreach(array('port', 'name', 'state', 'map', 'mods', 'players') as $key)
		if(!isset($_REQUEST[$key]))
			die_msg('field "'.$key.'" is not set');

	try 
	{
		$db = new PDO('sqlite:db/openra.db');

		$ip   = $_SERVER['REMOTE_ADDR'];
		$port = $_REQUEST['port'];
		$addr = $ip.':'.$port;
		$name = urldecode($_REQUEST['name']);
		
		if ($_REQUEST['state'] == 3)
		{
			$remove = $db->prepare('DELETE FROM `server` WHERE address = :addr');
			$remove->bindValue(':addr', $addr, PDO::PARAM_STR);
			$remove->execute();
			unset($db);
			exit;
		}

		if (isset($_REQUEST['new']))
		{
			if(!check_port($ip, $port))
				$name = '[down]' . $name;

			$games = file_get_contents("games.txt");
			file_put_contents("games.txt", $games + 1);
		}

		updatedbinfo(
			array(
				'name'		=> $name,
				'address'	=> $addr,
				'players'	=> $_REQUEST['players'],
				'state'		=> $_REQUEST['state'],
				'ts'		=> time(),
				'map'		=> $_REQUEST['map'], 
				'mods'		=> $_REQUEST['mods'],
			)
		);
		unset($db);
	}
	catch (PDOException $e)
	{
		die($e->getMessage());
	}

?>
