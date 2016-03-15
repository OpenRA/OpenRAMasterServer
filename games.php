<?php
    date_default_timezone_set('UTC');
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
            echo "\tPlayers: " . $row['players'] . "\n";
            echo "\tMaxPlayers: " . $row['maxplayers'] . "\n";
            echo "\tBots: " . $row['bots'] . "\n";
            echo "\tSpectators: " . $row['spectators'] . "\n";
            echo "\tMap: " . $row['map'] . "\n";
            echo "\tMods: " . $row['mods'] . "\n";
            $protected = $row['protected'] != 0 ? 'true' : 'false';
            echo "\tTTL: " . ($stale - (time() - $row['ts'])) . "\n";
            echo "\tProtected: " . $protected . "\n";
            if ($row['state'] == 2 && $row['started'] != '')
                echo "\tStarted: " . $row['started'] . "\n";
            $country = explode(":", $row['address']);
            array_pop($country);
            $country = implode(":", $country);
            $country = geoip_country_name_by_name($country);
            if ($country)
                echo "\tLocation: " . $country . "\n";

            $query = $db->prepare('SELECT client FROM clients WHERE address = :addr');
            $query->bindValue(':addr', $row['address'], PDO::PARAM_STR);
            $query->execute();
            $res = $query->fetchAll();
            if ($res)
            {
                echo "\tClients:\n";
                foreach ($res as $client)
                    echo "\t\t" . base64_encode($client['client']) . "\n";
            }
        }
        $db = null;
    }
    catch (PDOException $e)
    {
        echo $e->getMessage();
    }
?>
