<?php
    define('DEBUG',			0);
    define('PORT_CHECK_TIMEOUT',	3);
    define('SQLITE_PATH_DB',		'db/openra.db');
    define('SQLITE_TABLE_SERVERS',	'servers');

    ini_set('display_errors', 		DEBUG);

    error_reporting(DEBUG ? E_ALL : 0);

    header('Content-type: text/plain');
    
    // === functions ===

    function die_msg($msg) {
	if(DEBUG) {
		debug_backtrace();
		die($msg);
	}
	die();
    }

    function check_port($ip, $port)
    {
        $sock = @fsockopen($ip, $port, $errno, $errstr, PORT_CHECK_TIMEOUT);
        if (!$sock)
            return false;
        fclose($sock);
        return true;
    }

    function _updatedbinfo($db, $gameinfo, $sqlaction='INSERT', $sqldupaction='FAIL') {
	$fields = array_keys($gameinfo);

	switch($sqlaction) {
		case 'INSERT':
			$querystr='INSERT OR '.$sqldupaction.' INTO '.SQLITE_TABLE_SERVERS.' ('.join(', ', $fields).') VALUES (:'.join(', :', $fields).')';
			break;
		case 'UPDATE':
			$fields_s=array(); foreach($fields as $field) $fields_s[] = $field.' = :'.$field;
			$querystr='UPDATE OR '.$sqldupaction.' '.SQLITE_TABLE_SERVERS.' SET '.join(', ', $fields_s).' WHERE address = :address';
			break;
		default:
			die_msg('unknown sqlaction: "'.$sqlaction.'"');
	}
	if(DEBUG) print $querystr."\n";
	$query = $db->prepare($querystr);
	//	for example "INSERT OR ABORT servers (name, address, players, state, ts, map, mods) VALUES (:name, :address, :players, :state, :ts, :map, :mods)"

	foreach($gameinfo as $key => $value)
        	$query->bindValue(':'.$key,	$value[0], $value[1]);

	if(DEBUG) $query->debugDumpParams();
        return $query->execute();
    }

    function updatedbinfo($gameinfo) {
	global $db;
	foreach(array('name', 'address', 'ts') as $key) if(!isset($gameinfo[$key])) die_msg('field "'.$key.'" is not set');
	return 	_updatedbinfo($db, $gameinfo, 'INSERT', 'ABORT') or 
		_updatedbinfo($db, $gameinfo, 'UPDATE', 'FAIL');
    }

    // === body ===

    // make sure everything required is actually set.
    foreach(array('port', 'name', 'state', 'map', 'mods', 'players') as $key)
	if(!isset($_REQUEST[$key]))
	    die_msg('field "'.$key.'" is not set');

    $isnew = isset($_REQUEST['new']);

    try 
    {
        $db = new PDO('sqlite:'.SQLITE_PATH_DB);
        $ip = $_SERVER['REMOTE_ADDR'];
        $port = $_REQUEST['port'];
        $addr = $ip . ':' . $port;
        $name = urldecode( $_REQUEST['name'] );
        
        if($_REQUEST['state'] == 3)
        {
               $remove = $db->prepare('DELETE FROM `'.SQLITE_TABLE_SERVERS.'` WHERE address = :addr');
               $remove->bindValue(':addr', $addr, PDO::PARAM_STR);
               $remove->execute();
               $db = null;
               exit;
        }

        if($isnew)
        {
            $connectable = check_port($ip, $port);
            if (!$connectable)
                $name = '[down]' . $name;
        }

	updatedbinfo(array(
		'name'		=> array($name, 		PDO::PARAM_STR),
		'address'	=> array($addr, 		PDO::PARAM_STR), 
		'players'	=> array($_REQUEST['players'],	PDO::PARAM_INT), 
		'state'		=> array($_REQUEST['state'],	PDO::PARAM_INT), 
		'ts'		=> array(time(),		PDO::PARAM_INT), 
		'map'		=> array($_REQUEST['map'],	PDO::PARAM_STR), 
		'mods'		=> array($_REQUEST['mods'],	PDO::PARAM_STR),
		)
	);


        if($isnew)
        {
	    if(defined("PDO::lastInsertId")) {
		$lastid=PDO::lastInsertId;
	    } else {	// fallback. TODO: remove this:
		$select = $db->prepare('SELECT id FROM servers WHERE address = :addr');
		$select->bindValue(':addr', $addr, PDO::PARAM_STR);

		$select->execute();
		$lastid = (int)$select->fetchColumn();
	    }

	    echo $lastid;
    
            $games = file_get_contents("games.txt");
            file_put_contents("games.txt", $games + 1);
        }
	unset($db);
//        $db = null;
    }
    catch (PDOException $e)
    {
        echo $e->getMessage();
    }
?>
