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
    if (!isset( $_REQUEST['maxplayers'] )) exit();
    if (!isset( $_REQUEST['title'] )) exit();
    if (!isset( $_REQUEST['description'] )) exit();
    if (!isset( $_REQUEST['type'] )) exit();
    if (!isset( $_REQUEST['width'] )) exit();
    if (!isset( $_REQUEST['height'] )) exit();
    if (!isset( $_REQUEST['tileset'] )) exit();
    if (!isset( $_REQUEST['author'] )) exit();
    
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
        $insert->bindValue(':maxplayers', $_REQUEST['maxplayers'], PDO::PARAM_INT);
        $insert->bindValue(':title', $_REQUEST['title'], PDO::PARAM_STR);
        $insert->bindValue(':description', $_REQUEST['description'], PDO::PARAM_STR);
        $insert->bindValue(':type', $_REQUEST['type'], PDO::PARAM_STR);
        $insert->bindValue(':width', $_REQUEST['width'], PDO::PARAM_INT);
        $insert->bindValue(':height', $_REQUEST['height'], PDO::PARAM_INT);
        $insert->bindValue(':tileset', $_REQUEST['tileset'], PDO::PARAM_STR);
        $insert->bindValue(':author', $_REQUEST['author'], PDO::PARAM_STR);
        $insert->execute();
        
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
