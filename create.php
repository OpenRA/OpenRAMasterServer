<?php
    date_default_timezone_set('UTC');

    if (php_sapi_name() != 'cli')
        die("error: not command line");

    include('./config.php');

    $drop = False;
    try
    {
        $db = new PDO(DATABASE);
        echo "Connection to DB established.\n";

        if ($drop)
        {
            if ($db->query('DROP TABLE servers')
                    && $db->query('DROP TABLE clients')
                    && $db->query('DROP TABLE started')
                    && $db->query('DROP TABLE map_stats'))
                echo "Dropped all tables.\n";
        }

        // Holds currently active games
        $schema = 'CREATE TABLE servers (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR,
                    address VARCHAR UNIQUE,
                    ts INTEGER,
                    state INTEGER,
                    map VARCHAR,
                    mod VARCHAR,
                    version VARCHAR,
                    modtitle VARCHAR,
                    modwebsite VARCHAR,
                    modicon32 VARCHAR,
                    protected BOOLEAN DEFAULT 0,
                    authentication BOOLEAN DEFAULT 0,
                    players INTEGER,
                    bots VARCHAR default 0,
                    spectators INTEGER DEFAULT 0,
                    maxplayers INTEGER DEFAULT 0,
                    disabled_spawn_points VARCHAR,
                    started DATETIME
        )';
        if ($db->query($schema))
            echo "Created table 'servers'.\n";

        // Holds clients for currently active games
        $schema = 'CREATE TABLE clients (
                    address VARCHAR,
                    name VARCHAR,
                    fingerprint VARCHAR,
                    color VARCHAR,
                    faction VARCHAR,
                    team INTEGER DEFAULT 0,
                    spawnpoint INTEGER DEFAULT 0,
                    isadmin BOOLEAN DEFAULT 0,
                    isspectator BOOLEAN DEFAULT 0,
                    isbot BOOLEAN DEFAULT 0,
                    ts INTEGER
        )';
        if ($db->query($schema))
            echo "Created table 'clients'.\n";

        // Permanently records games that were started (and hopefully finished)
        $schema = 'CREATE TABLE started (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    protocol INTEGER DEFAULT 1,
                    game_id INTEGER UNIQUE,
                    name VARCHAR,
                    address VARCHAR,
                    map VARCHAR,
                    mod VARCHAR,
                    version VARCHAR,
                    protected BOOLEAN DEFAULT 0,
                    authentication BOOLEAN DEFAULT 0,
                    players INTEGER,
                    bots VARCHAR default 0,
                    spectators INTEGER DEFAULT 0,
                    maxplayers INTEGER DEFAULT 0,
                    disabled_spawn_points VARCHAR,
                    started DATETIME,
                    finished DATETIME
        )';
        if ($db->query($schema))
            echo "Created table 'started'.\n";

        // Records aggregate play counts for each map
        $schema = 'CREATE TABLE map_stats (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    map VARCHAR UNIQUE,
                    played_counter INTEGER,
                    last_change DATETIME
        )';

        if ($db->query($schema))
            echo "Created table 'map_stats'.\n";

        $db = new PDO(SYSINFO_DATABASE);
        echo "Connection to sysinfo DB established.\n";

        // Records opt-in system information
        $schema = 'CREATE TABLE sysinfo (
                    system_id STRING PRIMARY KEY,
                    updated DATETIME,
                    platform STRING,
                    os STRING,
                    x64 BOOL DEFAULT 1,
                    runtime STRING,
                    gl STRING,
                    windowsize STRING DEFAULT "0x0",
                    windowscale STRING DEFAULT "1.00",
                    uiscale STRING DEFAULT "1.00",
                    lang STRING,
                    version STRING,
                    mod STRING,
                    modversion STRING,
                    sysinfoversion INTEGER DEFAULT 1
        )';

        if ($db->query($schema))
            echo "Created table 'sysinfo'.\n";

        $db = null;
    }
    catch (PDOException $e)
    {
        echo $e->getMessage();
    }
?>
