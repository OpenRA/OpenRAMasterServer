<?php
    date_default_timezone_set('UTC');

    if (php_sapi_name() != 'cli')
        die("error: not command line");
    $drop = False;
    try
    {
        $db = new PDO('sqlite:db/openra.db');
        echo "Connection to DB established.\n";

        if ($drop)
        {
            if ($db->query('DROP TABLE servers')
                    && $db->query('DROP TABLE finished')
                    && $db->query('DROP TABLE map_stats')
                    && $db->query('DROP TABLE started')
                    && $db->query('DROP TABLE clients'))
                echo "Dropped all tables.\n";
        }

        $schema = 'CREATE TABLE servers (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    name VARCHAR,
                    address VARCHAR UNIQUE,
                    players INTEGER,
                    state INTEGER,
                    ts INTEGER,
                    map VARCHAR,
                    mods VARCHAR,
                    bots VARCHAR default 0,
                    spectators INTEGER DEFAULT 0,
                    maxplayers INTEGER DEFAULT 0,
                    protected BOOLEAN DEFAULT 0,
                    started DATETIME
        )';
        if ($db->query($schema))
            echo "Created table 'servers'.\n";

        $schema = 'CREATE TABLE finished (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    game_id INTEGER,
                    name VARCHAR,
                    address VARCHAR,
                    map VARCHAR,
                    game_mod VARCHAR,
                    version VARCHAR,
                    protected BOOLEAN DEFAULT 0,
                    started DATETIME,
                    finished DATETIME
        )';
        if ($db->query($schema))
            echo "Created table 'finished'.\n";

        $schema = 'CREATE TABLE map_stats (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    map VARCHAR UNIQUE,
                    played_counter INTEGER,
                    last_change DATETIME
        )';
        if ($db->query($schema))
            echo "Created table 'map_stats'.\n";

        $schema = 'CREATE TABLE started (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    game_id INTEGER,
                    name VARCHAR,
                    address VARCHAR,
                    map VARCHAR,
                    game_mod VARCHAR,
                    version VARCHAR,
                    players INTEGER,
                    spectators INTEGER,
                    bots INTEGER,
                    protected BOOLEAN DEFAULT 0,
                    started DATETIME
        )';
        if ($db->query($schema))
            echo "Created table 'started'.\n";

        $schema = 'CREATE TABLE clients (
                    address VARCHAR,
                    client VARCHAR,
                    spawn_id INTEGER DEFAULT 0,
                    ts INTEGER
        )';
        if ($db->query($schema))
            echo "Created table 'clients'.\n";

        $schema = 'CREATE TABLE sysinfo (
                    system_id STRING PRIMARY KEY,
                    updated DATETIME,
                    platform STRING,
                    os STRING,
                    x64 BOOL,
                    runtime STRING,
                    gl STRING,
                    windowsize STRING,
                    lang STRING,
                    version STRING,
                    mod STRING,
                    modversion STRING,
                    sysinfoversion INTEGER
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
