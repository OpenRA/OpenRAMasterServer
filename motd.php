<?php
        $inVer = substr($_GET["v"], strpos($_GET["v"], "-")+1);

        $inVer_min = 1;
        $i = strpos($inVer, "-");
        if ($i !== false)
        {
            $inVer_min = substr($inVer, $i+1);
            $inVer = substr($inVer, 0, $i);
        }

        $verFile = "VERSION";
        $handle = fopen($verFile, 'r');

        $version = fread($handle, filesize($verFile));
        fclose($handle);

        $version = trim(substr($version, strpos($version, "-")+1));
        $version_min = 1;

        $i = strpos($version, "-");
        if ($i !== false)
        {
            $version_min = trim(substr($version, $i+1));
            $version = substr($version, 0, $i);
        }

        if ($inVer < $version || ($inVer == $version && $inVer_min < $version_min))
        {
                echo "New version available. Please go to http://open-ra.org to upgrade";
        }
        else
        {
                echo "Welcome to OpenRA. Read news and more at http://reddit.com/r/openra.";
        }
?>
