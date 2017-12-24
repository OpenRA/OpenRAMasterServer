<?php
    date_default_timezone_set('UTC');

    define('DATABASE', 'sqlite:db/openra.db');

    header('Content-type: text/plain');

    try
    {
        $db = new PDO(DATABASE);
        $stale = 60 * 5;
        $result = $db->query('SELECT * FROM servers WHERE (' . time() . ' - ts < ' . $stale . ') ORDER BY name');
        $n = 0;
        foreach ($result as $row)
        {
            echo "Game@" . $n++ . ":\n";
            echo "\tId: " . $row['id'] . "\n";
            echo "\tName: " . $row['name'] . "\n";
            echo "\tAddress: " . $row['address'] . "\n";
            echo "\tState: " . $row['state'] . "\n";
            echo "\tPlayers: " . $row['players'] . "\n";
            echo "\tMaxPlayers: " . $row['maxplayers'] . "\n";
            echo "\tBots: " . $row['bots'] . "\n";
            echo "\tSpectators: " . $row['spectators'] . "\n";
            echo "\tMap: " . $row['map'] . "\n";
            echo "\tMods: " . $row['mod'] . "@" . $row['version'] . "\n";
            echo "\tMod: " .$row['mod'] . "\n";
            echo "\tVersion: " . $row['version'] . "\n";

            $protected = $row['protected'] != 0 ? 'true' : 'false';
            echo "\tTTL: " . ($stale - (time() - $row['ts'])) . "\n";
            echo "\tProtected: " . $protected . "\n";
            if ($row['state'] == 2 && $row['started'] != '')
            {
                echo "\tStarted: " . $row['started'] . "\n";
                echo "\tPlayTime: " . ($row['ts'] - strtotime($row['started'])) . "\n";
            }

            $country = explode(":", $row['address']);
            array_pop($country);
            $country = implode(":", $country);
            $country = geoip_country_name_by_name($country);
            if ($country)
                echo "\tLocation: " . $country . "\n";

            $query = $db->prepare('SELECT * FROM clients WHERE address = :address');
            $query->bindValue(':address', $row['address'], PDO::PARAM_STR);
            $query->execute();
            if ($clients = $query->fetchAll())
            {
                echo "\tClients:\n";
                $i = 0;
                foreach ($clients as $client)
                {
                    echo "\t\tClient@" . $i++ . ":\n";
                    echo "\t\t\tName: " . $client['name'] . "\n";
                    echo "\t\t\tColor: " . $client['color'] . "\n";
                    echo "\t\t\tFaction: " . $client['faction'] . "\n";
                    echo "\t\t\tTeam: " . $client['team'] . "\n";
                    echo "\t\t\tSpawnPoint: " . $client['spawnpoint'] . "\n";
                    echo "\t\t\tIsAdmin: " . ($client['isadmin'] != 0 ? 'true' : 'false') . "\n";
                    echo "\t\t\tIsSpectator: " . ($client['isspectator'] != 0 ? 'true' : 'false') . "\n";
                    echo "\t\t\tIsBot: " . ($client['isbot'] != 0 ? 'true' : 'false') . "\n";
                }
            }
        }

        $db = null;
    }
    catch (PDOException $e)
    {
        echo $e->getMessage();
    }
?>
