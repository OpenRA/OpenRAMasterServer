<?php

date_default_timezone_set('UTC');

include('./config.php');

function order_servers($servers)
{
	// Create an array of server names, with any non-alphanumeric characters replaced with z
	$names = array();
	foreach ($servers as $key => $server)
		$names[$key] = strtolower(preg_replace("/[^\p{L}|\p{N}]/u", 'z', Normalizer::normalize($server['name'])));

	// Create a Collator and UTF-8 sort
	$collator = new Collator('en_US');
	$collator->asort($names);

	// Finally create a sorted array using the result from the UTF-8 sort
	$result = array();
	foreach ($names as $key => $value)
		$result[] = $servers[$key];

	return $result;
}

function query_games($protocol)
{
    try
    {
        $db = new PDO(DATABASE);

        $query = $db->prepare('SELECT * FROM servers WHERE ts > :recent');
        $query->bindValue(':recent', time() - STALE_GAME_TIMEOUT, PDO::PARAM_INT);
        $query->execute();

        $rows = order_servers($query->fetchAll());

        $servers = array();
        foreach ($rows as $row)
        {
            // Attempt country lookup for consumers that don't have their own GeoIP facilities
            if (ENABLE_GEOIP)
            {
                $country = explode(":", $row['address']);
                array_pop($country);
                $country = implode(":", $country);
                $country = geoip_country_name_by_name($country);
            }

            $ttl = $row['ts'] + STALE_GAME_TIMEOUT - time();

            if ($protocol == 1)
            {
                // Original protocol returned everything as strings
                // and combined the mod and version into a single field
                $server = array(
                    'id' => $row['id'],
                    'name' => $row['name'],
                    'address' => $row['address'],
                    'state' => $row['state'],
                    'ttl' => $ttl,
                    'mods' => $row['mod'] . "@" . $row['version'],
                    'map' => $row['map'],
                    'players' => $row['players'],
                    'maxplayers' => $row['maxplayers'],
                    'bots' => $row['bots'],
                    'spectators' => $row['spectators'],
                    'protected' => $row['protected'] != 0 ? 'true' : 'false'
                );

                if (isset($country))
                    $server['location'] = $country;

                if ($row['state'] == 2 && $row['started'])
                    $server['started'] = $row['started'];
            }
            else
            {
                // Version 2 used correct types, separates mod and version fields,
                // adds a playtime field, and client data
                $server = array(
                    'id' => intval($row['id']),
                    'name' => $row['name'],
                    'address' => $row['address'],
                    'state' => intval($row['state']),
                    'ttl' => $ttl,
                    'mod' => $row['mod'],
                    'version' => $row['version'],
                    'map' => $row['map'],
                    'players' => intval($row['players']),
                    'maxplayers' => intval($row['maxplayers']),
                    'bots' => intval($row['bots']),
                    'spectators' => intval($row['spectators']),
                    'protected' => $row['protected'] != 0,
                );

                if (isset($country))
                    $server['location'] = $country;

                if ($row['state'] == 2 && $row['started'])
                {
                    $server['started'] = $row['started'];
                    $server['playtime'] = time() - strtotime($row['started']);
                }

                $server['clients'] = array();

                $client_query = $db->prepare('SELECT * FROM clients WHERE address = :address');
                $client_query->bindValue(':address', $row['address'], PDO::PARAM_STR);
                $client_query->execute();
                while ($client = $client_query->fetch())
                {
                    $server['clients'][] = array(
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

            $servers[] = $server;
        }

        return $servers;
    }
    catch (Exception $e)
    {
        return array();
    }
}

function try_print_yaml_node($key, $value, $name_map, $indent)
{
    if (!array_key_exists($key, $name_map))
        return false;

    if ($value === true)
        $value = 'true';
    if ($value === false)
        $value = 'false';

    print(str_repeat("\t", $indent) . $name_map[$key] . ": " . $value . "\n");
    return true;
}

$output_json = isset($_REQUEST['type']) && $_REQUEST['type'] == 'json';
$protocol = isset($_REQUEST['protocol']) ? intval($_REQUEST['protocol']) : 1;
if ($protocol < 1)
    $protocol = 1;

if ($output_json)
{
    header('Content-Type: application/javascript');
    header('Access-Control-Allow-Origin: *');
    print(json_encode(query_games($protocol)));
}
else
{
    header('Content-type: text/plain');

    $name_map = array(
        'id' => 'Id',
        'name' => 'Name',
        'address' => 'Address',
        'state' => 'State',
        'ttl' => 'TTL',
        'mods' => 'Mods',
        'mod' => 'Mod',
        'version' => 'Version',
        'map' => 'Map',
        'players' => 'Players',
        'maxplayers' => 'MaxPlayers',
        'bots' => 'Bots',
        'spectators' => 'Spectators',
        'protected' => 'Protected',
        'location' => 'Location',
        'started' => 'Started',
        'playtime' => 'PlayTime',
    );

    $client_name_map = array(
        'name' => 'Name',
        'color' => 'Color',
        'faction' => 'Faction',
        'team' => 'Team',
        'spawnpoint' => 'SpawnPoint',
        'isadmin' => 'IsAdmin',
        'isspectator' => 'IsSpectator',
        'isbot' => 'IsBot'
    );

    $i = 0;
    $data = query_games($protocol);
    foreach ($data as $game)
    {
        print("Game@" . $i++ . ":\n");
        foreach ($game as $key => $value)
            try_print_yaml_node($key, $value, $name_map, 1);

        if (array_key_exists('clients', $game))
        {
            $j = 0;
            print("\tClients:\n");
            foreach ($game['clients'] as $client)
            {
                print("\t\tClient@" . $j++ . ":\n");
                foreach ($client as $key => $value)
                    try_print_yaml_node($key, $value, $client_name_map, 3);
            }
        }
    }
}

?>
