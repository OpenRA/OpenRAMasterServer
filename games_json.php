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
            $game_result['mods'] = $row['mods'];
            $game_result['name'] = $row['name'];
            $game_result['ttl'] = ($stale - (time() - $row['ts']));
            $game_result['players'] = $row['players'];
            $game_result['state'] = $row['state'];
            $game_result['address'] = $row['address'];
            $game_result['id'] = $row['id'];
            $game_result['maxplayers'] = $row['maxplayers'];
            $game_result['bots'] = $row['bots'];
            $game_result['spectators'] = $row['spectators'];
            $game_result['protected'] = $row['protected'] != 0 ? 'true' : 'false';
            if ($row['state'] == 2 && $row['started'] != '')
                $game_result['started'] = $row['started'];
            $country = explode(":", $row['address']);
            array_pop($country);
            $country = implode(":", $country);
            $country = geoip_country_name_by_name($country);
            if ($country)
                $game_result['location'] = $country;

            $query = $db->prepare('SELECT client FROM clients WHERE address = :addr');
            $query->bindValue(':addr', $row['address'], PDO::PARAM_STR);
            $query->execute();
            $res = $query->fetchAll();
            if ($res)
            {
                $clients = array();
                foreach ($res as $client)
                    array_push($clients, base64_encode($client['client']));
                $game_result['clients'] = $clients;
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
