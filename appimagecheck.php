<?php
$mod = $_REQUEST['mod'];
$channel = $_REQUEST['channel'];
if (in_array($mod, array('ra', 'cnc', 'd2k')) && in_array($channel, array('release', 'playtest')))
{
    try
    {
        $data = json_decode(file_get_contents("https://www.openra.net/appimages.json"), true);
        if (array_key_exists($channel, $data) && array_key_exists($mod, $data[$channel]))
        {
            header('Location: ' . $data[$channel][$mod]);
            exit();
        }
    }
    catch (Exception $e) { }
}

header("HTTP/1.0 404 Not Found");
?>
