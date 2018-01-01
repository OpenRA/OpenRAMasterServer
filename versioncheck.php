<?php
if (in_array($_REQUEST['mod'], array('ra', 'cnc', 'd2k')))
{
    try
    {
        $data = json_decode(file_get_contents("http://www.openra.net/versions.json"), true);
        $version = $_REQUEST['version'];
        if (!in_array($version, $data['known_versions']))
            print('unknown');
        else if ($version != $data['release'] && $version != $data['playtest'])
            print('outdated');
        else if ($version == $data['release'] && $data['playtest'] != '')
            print('playtest');
    }
    catch (Exception $e) { }
}
?>
