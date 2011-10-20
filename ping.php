<?php
    define('PORT_CHECK_TIMEOUT', 3);
    
    function check_port($ip, $port)
    {
        $sock = @fsockopen($ip, $port, $errno, $errstr, PORT_CHECK_TIMEOUT);
        if (!$sock)
            return false;
        fclose($sock);
        return true;
    }

    // make sure everything required is actually set.
    if (!isset( $_REQUEST['port'] )) exit();
    if (!isset( $_REQUEST['name'] )) exit();
    if (!isset( $_REQUEST['state'] )) exit();
    if (!isset( $_REQUEST['players'] )) exit();
    if (!isset( $_REQUEST['mods'] )) exit();
    if (!isset( $_REQUEST['map'] )) exit();
    
    header( 'Content-type: text/plain' );
    try 
    {
        $db = new PDO('sqlite:db/openra.db');
        
        $ip = $_SERVER['REMOTE_ADDR'];
        $port = $_REQUEST['port'];
        $addr = $ip . ':' . $port;
        $name = urldecode( $_REQUEST['name'] );
        $state = $_REQUEST['state'];
        $players = (int)$_REQUEST['players'];
        
		if (isset( $_REQUEST['new']))
		{
            $connectable = check_port($ip, $port);
            if (!$connectable)
                $name = '[down]' . $name;
                $state = -1;
		}
		
		// required support of old clients
		function req_or_default($var, $def)
		{
			if ( isset( $_REQUEST[$var] ) ) { return $_REQUEST[$var]; }
			else return $def;
		}

		$maxplayers = req_or_default( 'maxplayers', 0 );
		$title = req_or_default( 'title', '' );
		$description = req_or_default( 'description', '' );
		$type = req_or_default( 'type', '' );
		$width = req_or_default( 'width', 0 );
		$height = req_or_default( 'height', 0 );
		$tileset = req_or_default( 'tileset', '' );
		$author = req_or_default( 'author', '' );
		
        $insert = $db->prepare('INSERT OR REPLACE INTO servers 
            (name, address, players, state, ts, map, mods, maxplayers, title, description, type, width, height, tileset, author) 
            VALUES (:name, :addr, :players, :state, :time, :map, :mods, :maxplayers, :title, :description, :type, :width, :height, :tileset, :author)');
        $insert->bindValue(':name', $name, PDO::PARAM_STR);
        $insert->bindValue(':addr', $addr, PDO::PARAM_STR);
        $insert->bindValue(':players', $players, PDO::PARAM_INT);
        $insert->bindValue(':state', $state, PDO::PARAM_INT);
        $insert->bindValue(':time', time(), PDO::PARAM_INT);
        $insert->bindValue(':map', $_REQUEST['map'], PDO::PARAM_STR);
        $insert->bindValue(':mods', $_REQUEST['mods'], PDO::PARAM_STR);
        $insert->bindValue(':maxplayers', $maxplayers, PDO::PARAM_INT);
        $insert->bindValue(':title', $title, PDO::PARAM_STR);
        $insert->bindValue(':description', $description, PDO::PARAM_STR);
        $insert->bindValue(':type', $type, PDO::PARAM_STR);
        $insert->bindValue(':width', $width, PDO::PARAM_INT);
        $insert->bindValue(':height', $height, PDO::PARAM_INT);
        $insert->bindValue(':tileset', $tileset, PDO::PARAM_STR);
        $insert->bindValue(':author', $author, PDO::PARAM_STR);
        $insert->execute();
        
        // title is set, client is supported
        if (isset( $_REQUEST['title'] ))
        {
			$delete = $db->prepare("DELETE FROM players WHERE server_address = '" . $addr . "'");
			$delete->execute();
        
			$i = 0;
			while ($i < $players)
			{
				$playerName = $_REQUEST['playerName'.(string)$i];
				$playerFaction = $_REQUEST['playerFaction'.(string)$i];
				$playerTeam = $_REQUEST['playerTeam'.(string)$i];
				$insert = $db->prepare('INSERT INTO players
				(server_address, name, faction, team) 
				VALUES (:server_address, :name, :faction, :team)');
				$insert->bindValue(':server_address', $addr, PDO::PARAM_STR);
				$insert->bindValue(':name', $playerName, PDO::PARAM_STR);
				$insert->bindValue(':faction', $playerFaction, PDO::PARAM_STR);
				$insert->bindValue(':team', $playerTeam, PDO::PARAM_INT);
				$insert->execute();
				$i++;
			}
		}
		
        if (isset( $_REQUEST['new']))
        {
            $select = $db->prepare('SELECT id FROM servers WHERE address = :addr');
            $select->bindValue(':addr', $addr, PDO::PARAM_STR);

            $select->execute();

            echo (int)$select->fetchColumn();
    
            $games = file_get_contents("games.txt");
            file_put_contents("games.txt", $games + 1);
        }

        $db = null;
    }
    catch (PDOException $e)
    {
        echo $e->getMessage();
    }
?>
