<?php
    date_default_timezone_set('UTC');

    header('Content-Type: application/javascript');
    header('Access-Control-Allow-Origin: *');

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
            $game_result['mod'] = $row['mod'];
            $game_result['version'] = $row['version'];
            $game_result['mods'] = $row['mod'] . "@" . $row['version'];
            $game_result['name'] = $row['name'];
            $game_result['ttl'] = ($stale - (time() - $row['ts']));
            $game_result['players'] = intval($row['players']);
            $game_result['state'] = intval($row['state']);
            $game_result['address'] = $row['address'];
            $game_result['id'] = intval($row['id']);
            $game_result['maxplayers'] = intval($row['maxplayers']);
            $game_result['bots'] = intval($row['bots']);
            $game_result['spectators'] = intval($row['spectators']);
            $game_result['protected'] = $row['protected'] != 0;

            if ($row['state'] == 2 && $row['started'] != '')
            {
                $game_result['started'] = $row['started'];
                $game_result['playtime'] = ($row['ts'] - strtotime($row['started']));
            }

            $country = explode(":", $row['address']);
            array_pop($country);
            $country = implode(":", $country);
            $country = geoip_country_name_by_name($country);
            if ($country)
                $game_result['location'] = $country;

            $query = $db->prepare('SELECT * FROM clients WHERE address = :address');
            $query->bindValue(':address', $row['address'], PDO::PARAM_STR);
            $query->execute();

            if ($clients = $query->fetchAll())
            {
                $game_result['clients'] = array();
                foreach ($clients as $client)
                {
                    $game_result['clients'][] = array(
                        'name' => $client['name'],
                        'color' => $client['color'],
                        'faction' => $client['faction'],
                        'team' => intval($client['team']),
                        'spawnpoint' => intval($client['spawnpoint']),
                        'isadmin' => $client['isadmin'] != 0,
                        'isspectator' => $client['isspectator'] != 0,
                        'isbot' => $client['isbot'] != 0
                    );
                }
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
