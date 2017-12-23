<?php
    date_default_timezone_set('UTC');

    // === configuration ===
    define('DATABASE', 'sqlite:db/openra.db');
    define('DEBUG', 0);
    define('PORT_CHECK_TIMEOUT', 3);
    ini_set('display_errors', DEBUG);
    error_reporting(DEBUG ? E_ALL : 0);

    header('Content-type: text/plain');

    // === functions ===

    function check_port($ip, $port)
    {
        return @fsockopen($ip, $port, $errno, $errstr, PORT_CHECK_TIMEOUT);
    }

    function insert_columns_sql($columns)
    {
        return "('" . implode("', '", array_keys($columns)) . "') VALUES (:" . implode(', :', array_keys($columns)) . ")";
    }

    function update_columns_sql($columns)
    {
        return "SET " . implode(', ', array_map(function($k) { return "'".$k."' = :".$k; }, array_keys($columns)));
    }

    function bind_columns($query, $columns, $data)
    {
        foreach ($columns as $column => $type)
        {
            // Force some basic type safety
            $value = $data[$column];
            switch ($type)
            {
                case PDO::PARAM_INT: $value = intval($value); break;
                case PDO::PARAM_STR: $value = htmlspecialchars($value); break;
            }

            $query->bindValue(':'.$column, $value, $type);
        }
    }

    function update_db_info($gameinfo)
    {
        $db = new PDO(DATABASE);

        $server_columns = array(
            'name' => PDO::PARAM_STR,
            'address' => PDO::PARAM_STR,
            'players' => PDO::PARAM_INT,
            'state' => PDO::PARAM_INT,
            'ts' => PDO::PARAM_INT,
            'map' => PDO::PARAM_STR,
            'mods' => PDO::PARAM_STR,
            'bots' => PDO::PARAM_INT,
            'spectators' => PDO::PARAM_INT,
            'maxplayers' => PDO::PARAM_INT,
            'protected' => PDO::PARAM_INT,
            'started' => PDO::PARAM_STR,
        );

        $started_columns = array(
            'game_id' => PDO::PARAM_INT,
            'name' => PDO::PARAM_STR,
            'address' => PDO::PARAM_STR,
            'map' => PDO::PARAM_STR,
            'game_mod' => PDO::PARAM_STR,
            'version' => PDO::PARAM_STR,
            'players' => PDO::PARAM_INT,
            'spectators' => PDO::PARAM_INT,
            'bots' => PDO::PARAM_INT,
            'protected' => PDO::PARAM_INT,
            'started' => PDO::PARAM_STR,
        );

        $finished_columns = array(
            'game_id' => PDO::PARAM_INT,
            'name' => PDO::PARAM_STR,
            'address' => PDO::PARAM_STR,
            'map' => PDO::PARAM_STR,
            'game_mod' => PDO::PARAM_STR,
            'version' => PDO::PARAM_STR,
            'protected' => PDO::PARAM_INT,
            'started' => PDO::PARAM_STR,
            'finished' => PDO::PARAM_STR,
        );

        $client_columns = array(
            'address' => PDO::PARAM_STR,
            'client' => PDO::PARAM_STR,
            'ts' => PDO::PARAM_INT,
        );

        // Check the last state of the server
        $gameinfo['last_state'] = 1;
        $gameinfo['started'] = '';
        $query_state = $db->prepare('SELECT id, state, started FROM servers WHERE address = :address');
        $query_state->bindValue(':address', $gameinfo['address'], PDO::PARAM_STR);
        $query_state->execute();
        if ($row = $query_state->fetch())
        {
            $gameinfo['id'] = $row['id'];
            $gameinfo['last_state'] = $row['state'];
            $gameinfo['started'] = $row['started'];
        }

        // Update latest server metadata
        $update_server = $db->prepare("UPDATE servers " . update_columns_sql($server_columns) . " WHERE address = :address");
        bind_columns($update_server, $server_columns, $gameinfo);
        $update_server->execute();
        if (!$update_server->rowCount())
        {
            $update_server = $db->prepare("INSERT INTO servers " . insert_columns_sql($server_columns));
            bind_columns($update_server, $server_columns, $gameinfo);
            $update_server->execute();
        }

        // Update latest client metadata
        $delete_clients = $db->prepare('DELETE FROM clients WHERE address = :address');
        $delete_clients->bindValue(':address', $gameinfo['address'], PDO::PARAM_STR);
        $delete_clients->execute();

        foreach ($gameinfo['clients'] as $client)
        {
            $insert_client = $db->prepare("INSERT INTO clients " . insert_columns_sql($client_columns));
            $client_data = array(
                'address' => $gameinfo['address'],
                'client' => base64_decode($client),
                'ts' => time()
            );

            bind_columns($insert_client, $client_columns, $client_data);
            $insert_client->execute();
        }

        // Game has just started
        if ($gameinfo['last_state'] == 1 && $gameinfo['state'] == 2)
        {
            // Set the started field in the servers table
            $set_started = $db->prepare("UPDATE OR FAIL `servers` SET 'started' = :started WHERE address = :address");
            $gameinfo['started'] = date('Y-m-d H:i:s');
            $set_started->bindValue(':started', $gameinfo['started'], PDO::PARAM_STR);
            $set_started->bindValue(':address', $gameinfo['address'], PDO::PARAM_STR);
            $set_started->execute();

            if (DEBUG)
                $set_started->debugDumpParams();

            // Copy server record to the started table
            // HACK: Why is this data interesting?
            // TODO: Remove it?
            $copy_started = $db->prepare("INSERT INTO started " . insert_columns_sql($started_columns));
            $started_data = array(
                'game_id' => $gameinfo['id'],
                'name' => $gameinfo['name'],
                'address' => $gameinfo['address'],
                'map' => $gameinfo['map'],
                'game_mod' => $gameinfo['mod'],
                'version' => $gameinfo['version'],
                'players' => $gameinfo['players'],
                'spectators' => $gameinfo['spectators'],
                'bots' => $gameinfo['bots'],
                'protected' => $gameinfo['protected'],
                'started' => $gameinfo['started'],
            );

            bind_columns($copy_started, $started_columns, $started_data);
            $copy_started->execute();
        }

        // Game has just finished
        else if ($gameinfo['state'] == 3)
        {
            // Game actually started
            if ($gameinfo['last_state'] == 2)
            {
                // Update map stats
                $update_map_plays = $db->prepare('UPDATE map_stats SET played_counter = played_counter + 1 WHERE map = :map');
                $update_map_plays->bindValue(':map', $gameinfo['map'], PDO::PARAM_STR);
                $update_map_plays->execute();
                if (!$update_map_plays->rowCount())
                {
                    $update_map_plays = $db->prepare("INSERT INTO map_stats ('map', 'played_counter', 'last_change')
                        VALUES (:map, 1, :last_change)");
                    $update_map_plays->bindValue(':map', $gameinfo['map'], PDO::PARAM_STR);
                    $update_map_plays->bindValue(':last_change', date('Y-m-d H:i:s'), PDO::PARAM_STR);
                    $update_map_plays->execute();
                }

                if (DEBUG)
                    $update_map_plays->debugDumpParams();

                // Copy server record to the finished table
                $copy_finished = $db->prepare("INSERT INTO finished " . insert_columns_sql($finished_columns));
                $finished_data = array(
                    'game_id' => $gameinfo['id'],
                    'name' => $gameinfo['name'],
                    'address' => $gameinfo['address'],
                    'map' => $gameinfo['map'],
                    'game_mod' => $gameinfo['mod'],
                    'version' => $gameinfo['version'],
                    'protected' => $gameinfo['protected'],
                    'started' => $gameinfo['started'],
                    'finished' => date('Y-m-d H:i:s'),
                );

                bind_columns($copy_finished, $finished_columns, $finished_data);
                $copy_finished->execute();
                if (DEBUG)
                    $copy_finished->debugDumpParams();
            }

            $remove = $db->prepare('DELETE FROM `servers` WHERE address = :addr');
            $remove->bindValue(':addr', $gameinfo['address'], PDO::PARAM_STR);
            $remove->execute();
            $db->query('DELETE FROM servers WHERE (' . time() . ' - ts > 300)');

            $remove = $db->prepare('DELETE FROM clients WHERE address = :addr OR (' . time() . ' - ts > 300)');
            $remove->bindValue(':addr', $gameinfo['address'], PDO::PARAM_STR);
            $remove->execute();
        }

        unset($db);
        return true;
    }

    // === body ===

    // make sure everything required is actually set.
    foreach(array('port', 'name', 'state', 'map', 'mods', 'players') as $key)
        if(!isset($_REQUEST[$key]))
            die('[003] Advertisement data is not in the expected format');

    try
    {
        $ip   = $_SERVER['REMOTE_ADDR'];
        $port = $_REQUEST['port'];
        $addr = $ip.':'.$port;

        // don't get spammed so easily
        if ($_REQUEST['state'] == 1)
            if (!check_port($ip, $port))
                die('[001] game server "'.$addr.'" does not respond');

        $version_arr = explode('@', $_REQUEST['mods']);
        $mod = array_shift($version_arr);
        $mod_version = implode('@', $version_arr);

        update_db_info(array(
            'name'      => urldecode($_REQUEST['name']),
            'address'   => $addr,
            'players'   => $_REQUEST['players'],
            'state'     => $_REQUEST['state'],
            'ts'        => time(),
            'map'       => $_REQUEST['map'],
            'mods'      => $_REQUEST['mods'],
            'bots'      => isset($_REQUEST['bots']) ? $_REQUEST['bots'] : 0,
            'spectators'=> isset($_REQUEST['spectators']) ? $_REQUEST['spectators'] : 0,
            'maxplayers'=> isset($_REQUEST['maxplayers']) ? $_REQUEST['maxplayers'] : 0,
            'protected' => isset($_REQUEST['protected']) ? $_REQUEST['protected'] : 0,
            'clients'   => isset($_REQUEST['clients']) ? explode(",", $_REQUEST['clients']) : array(),
            'mod'       => $mod,
            'version'   => $mod_version,
        ));
    }
    catch (Exception $e)
    {
        die('[004] Failed to update server database');
        error_log($e->getMessage());
    }
?>
