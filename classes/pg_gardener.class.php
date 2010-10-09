<?php
class pg_gardener {

    var $manager = NULL;
    var $info = NULL;
    var $collections = NULL;
    var $cfg = NULL;

    function pg_gardener(&$manager) {
        $this->manager = & $manager;
        $this->info = & $manager->info;
        $this->collections = & $manager->collections;
        $this->cfg = & $manager->cfg;
    }

    function execute() {
	    // overridden
    }

    /**
     * Copy with recursive sub-directory support
	 * from plugin:plugin
     */
    function dircopy($src, $dst) {
        global $conf;

        if (is_dir($src)) {
            if (!$dh = @opendir($src)) return false;

            if ($ok = io_mkdir_p($dst)) {
                while ($ok && (false !== ($f = readdir($dh)))) {
                    if ($f == '..' || $f == '.') continue;
                    $ok = $this->dircopy("$src/$f", "$dst/$f");
                }
            }

            closedir($dh);
            return $ok;

        } else {
            $exists = @file_exists($dst);

            if (!@copy($src,$dst)) return false;
            if (!$exists && !empty($conf['fperm'])) chmod($dst, $conf['fperm']);
            @touch($dst,filemtime($src));
        }

        return true;
    }

    /**
     * delete, with recursive sub-directory support
	 * from plugin:plugin
     */
    function dir_delete($path) {
        if (!is_string($path) || $path == "") return false;

        if (is_dir($path)) {
            if (!$dh = @opendir($path)) return false;

            while ($f = readdir($dh)) {
                if ($f == '..' || $f == '.') continue;
                $this->dir_delete("$path/$f");
            }

            closedir($dh);
            return @rmdir($path);
        } else {
            return @unlink($path);
        }

        return false;
    }

}

