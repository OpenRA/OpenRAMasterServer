<?php

header('Content-Type: application/javascript');

    try
    {
        $db = new PDO('sqlite:db/openra.db');
        $stale = 60 * 5;
        $result = $db->query('SELECT * FROM servers WHERE (' . time() . ' - ts < ' . $stale . ') ORDER BY name');
        $n = 0;
        $json_result_array = array();
        foreach ( $result as $row )
        {
            $game_result = array();
            $game_result['map'] = $row['map'];
            $game_result['mods'] = $row['mods'];
            $game_result['name'] = $row['name'];
            $game_result['ttl'] = ($stale - (time() - $row['ts']));
            $game_result['players'] = $row['players'];
            $game_result['state'] = $row['state'];
            $game_result['address'] = $row['address'];
            $game_result['id'] = $row['id'];
	    if (isset( $_REQUEST['location'] ))
	    {
		$ip_addr = split(":", $row['address']);
		array_pop($ip_addr);
		$ip_addr = implode(":", $ip_addr);
		$content = file_get_contents('http://api.hostip.info/country.php?ip=' . $ip_addr);
		if( $content !== FALSE )
		    $game_result['location'] = $content;
	    }
            $json_result_array[] = $game_result;
            unset($game_result);
        }
        print(json_encode($json_result_array));
        $db = null;
    }
    catch (PDOException $e)
    {
        echo $e->getMessage();
    }
?>
