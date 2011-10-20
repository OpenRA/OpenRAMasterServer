<?php
    header( 'Content-type: text/plain' );

    try
    {
        $db = new PDO('sqlite:db/openra.db');
        $stale = 60 * 5;
        $result = $db->query('SELECT * FROM servers WHERE (' . time() . ' - ts < ' . $stale . ') ORDER BY name');
	$n = 0;
        foreach ( $result as $row )
        {
            echo "Game@" . $n++ . ":\n";
            echo "\tId: " . $row['id'] . "\n";
            echo "\tName: " . $row['name'] . "\n";
            echo "\tAddress: " . $row['address'] . "\n";
            echo "\tState: " . $row['state'] . "\n";
            echo "\tMods: " . $row['mods'] . "\n";
            echo "\tPlayers: " . $row['players'] . "\n";
            echo "\tMap Hash: " . $row['map'] . "\n";
            echo "\tMap Title: " . $row['title'] . "\n";
            echo "\tMap Description: " . $row['description'] . "\n";
            echo "\tMax Players: " . $row['maxplayers'] . "\n";
            echo "\tMap Type: " . $row['type'] . "\n";
            echo "\tMap Width: " . $row['width'] . "\n";
            echo "\tMap Height: " . $row['height'] . "\n";
            echo "\tMap Tileset: " . $row['tileset'] . "\n";
            echo "\tMap Author: " . $row['author'] . "\n";
            echo "\tTTL: " . ($stale - (time() - $row['ts'])) . "\n";
            $select = $db->query("SELECT * FROM players WHERE server_address = '" . $row['address'] ."'");
            $m = 0;
            foreach ( $select as $player )
            {
				echo "\tPlayer@" . $m++ . ":\n";
				echo "\t\tName: " . $player['name'] . "\n";
				echo "\t\tFaction: " . $player['faction'] . "\n";
				echo "\t\tTeam: " . $player['team'] . "\n";
			}
        }
        $db = null;
    }
    catch (PDOException $e)
    {
        echo $e->getMessage();
    }
?>

