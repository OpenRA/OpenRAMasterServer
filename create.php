<?php
    header( 'Content-type: text/plain' );
    try
    {
        $db = new PDO('sqlite:db/openra.db');
        echo "Connection to DB established.\n";
        if ($db->query('DROP TABLE servers'))
            echo "Dropped table `servers`.\n";
        if ($db->query('DROP TABLE players'))
            echo "Dropped table `players`.\n";
        $schema = 'CREATE TABLE servers (
                  id INTEGER PRIMARY KEY  AUTOINCREMENT,
                  name varchar(255), 
                  address varchar(255) UNIQUE,
                  players integer,
                  state integer,
                  ts integer,
                  map varchar(255),
                  mods varchar(255),
                  maxplayers integer,
                  title varchar(255),
                  description varchar(255),
                  type varchar(255),
                  width integer,
                  height integer,
                  tileset varchar(255),
                  author varchar(255)
                  )';
        if ($db->query($schema))
            echo "Created table `servers`.\n";
        $schema = 'CREATE TABLE players (
                  server_address varchar(255),
                  name varchar(255),
                  faction varchar(255),
                  team integer
                  )';
        if ($db->query($schema))
            echo "Created table `players`.\n";
        $db = null;
    }
    catch (PDOException $e)
    {
        echo $e->getMessage();
    }
?>
