<?
        $inVer = substr($_GET["v"], strpos($_GET["v"], "-")+1);
        $verFile = "VERSION";
        $handle = fopen($verFile, 'r');

        $version = fread($handle, filesize($verFile));
        fclose($handle);

        $version = substr($version, strpos($version, "-")+1);
        
        if ($inVer < $version)
        {
                echo "New version available. Please go to http://open-ra.org to upgrade";
        }
        else
        {
                echo "Welcome to OpenRA. Read news and more at http://reddit.com/r/openra.";
        }
?>
