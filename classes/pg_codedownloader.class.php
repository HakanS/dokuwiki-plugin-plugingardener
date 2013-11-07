<?php

/**
 * Class pg_codedownloader
 *
 * Download plugins code
 */
class pg_codedownloader extends pg_gardener {

    /**
     * Performs the downloads
     */
    public function execute() {
        echo "<h4>DownloadCode</h4>\n";
        echo "<ul>";
        
        $localpluginsdir = $this->cfg['localdir'].'plugins/';
        if (!file_exists($localpluginsdir)) {
            mkdir($localpluginsdir);
        }

		if ($this->cfg['downloadplugins'] && !$this->cfg['offline']) {
            foreach ($this->collections['plugins'] as $plugin) {
                set_time_limit(60);

                echo "<li><a href=\"https://www.dokuwiki.org/plugin:$plugin\">$plugin</a></li><ul>\n";
                if (!$this->_downloadPlugin($plugin, $localpluginsdir)) {
                    $this->_extractCodeBlock($plugin, $localpluginsdir);
                }
                echo "</ul>";
            }
        } else {
            echo "<li>No download</li>\n";
        }
        echo "</ul>";
    }

    /**
     * Extract code from downloadable code block in plugin homepage
     *
     * @param string $plugin plugin name
     * @param string $localpluginsdir
     */
    private function _extractCodeBlock($plugin, $localpluginsdir) {
        $localpluginhomepage = $this->cfg['localdir'].'pages/'.$plugin.'.htm';
        $markup = file_get_contents($localpluginhomepage);
        if (preg_match_all('/<pre class="code[^&]+&lt;\?php.*?<\/pre>/s', $markup, $matches)) {
            $code = $matches[0][0];
            $code = strip_tags($code);
            $code = html_entity_decode($code);

            // Ensure complete overwrite
            $plugindir = $localpluginsdir.$plugin.'/';
            if (file_exists($plugindir) && !$this->cfg['overwrite']) return;
            if (file_exists($plugindir)) {
                $this->dir_delete($plugindir);
            }
            mkdir($plugindir.$plugin, 0777, true);
            file_put_contents($plugindir.$plugin.'/extracted__code__block.php',$code);
            echo "<li>$plugin codeblock extracted</li>\n";
        } else {
            echo "<li>** $plugin failed **</li>\n";
        }
    }

    /**
     *
     *
     * @param string $plugin plugin name
     * @param string $localpluginsdir
     * @return bool
     */
    private function _downloadPlugin($plugin, $localpluginsdir) {
        $plugindir = $localpluginsdir.$plugin.'/';

        if (file_exists($plugindir) && !file_exists($plugindir."$plugin/extracted__code__block.php") && !$this->cfg['overwrite']) return true;

        if ($this->info[$plugin]['bundled']) { // TODO check repo instead
            if (file_exists($plugindir)) {
                $this->dir_delete($plugindir);
            }
            mkdir($plugindir.$plugin, 0777, true);
            if ($this->dircopy($this->cfg['bundledsourcedir'].$plugin, $plugindir.$plugin.'/')) {
                echo "<li>$plugin bundled</li>\n";
                return true;
            }
        }

        // check the url
        if (!$this->info[$plugin]['download']) return false;
        echo "<ul>";
        foreach ($this->info[$plugin]['download'] as $url) {
            if ($this->_download_file($plugin, $url, $localpluginsdir)) {
                echo "</ul>\n";
                return true;
            }
        }
        echo "</ul>\n";
        return false;
    }

    /**
     * Download file
     *
     * @param string $plugin plugin name
     * @param string $url download url
     * @param $localpluginsdir
     * @return bool
     */
    private function _download_file($plugin, $url, $localpluginsdir) {
    	if (!$url) return false;
        $plugindir = $localpluginsdir.$plugin.'/';
        set_time_limit(5*60);
        echo '<li><a href="'.$url.'">$url</a> - ';

        $error = '';
        $retval = true;

        $matches = array();
        if (!preg_match("/[^\/]*$/", $url, $matches) || !$matches[0]) {
            $error = 'badurl/slashes';
            $retval = false;
        }
        $file = $matches[0];

        $tmp = false;
        if (!$error && !($tmp = io_mktmpdir())) {
            $error = 'cant create tmp dir';
            $retval = false;
        }

        if (!$error && !$file = io_download($url, "$tmp/", true, $file)) {
            $error = 'download error';
            $retval = false;
        }

        // Abort if download url has changed since last successfull download
        if (!$error && file_exists($plugindir.'url.txt') && file_get_contents($plugindir.'url.txt') != $url) {
            $error = 'Download url changed, aborted';
            $retval = true;
        }

        if (!$error && !$this->decompress("$tmp/$file", $tmp)) {
            $error = 'decompress error';
            $retval = false;
        }

        // search $tmp for the folder(s) that has been created
        // move the folder(s) to lib/plugins/
        if (!$error) {
            // Ensure complete overwrite, flag already checked

            if (file_exists($plugindir)) {
                $this->dir_delete($plugindir);
            }

            $result = array('old'=>array(), 'new'=>array());
            if($this->find_folders($result,$tmp)){
                // choose correct result array
                if(count($result['new'])){
                    $install = $result['new'];
                }else{
                    $install = $result['old'];
                }

                // now install all found items
                foreach($install as $item){
                    // where to install?
                    if($item['type'] == 'template'){
                        $localtemplatesdir = $this->cfg['localdir'].'templates/';
                        if (!file_exists($localtemplatesdir)) {
                            mkdir($localtemplatesdir);
                        }
                        $templatedir = $localtemplatesdir.$plugin.'/';
                        mkdir($templatedir, 0777, true);

                        $target = $templatedir.$item['base'];
                    }else{
                        $target = $plugindir.$item['base'];
                    }

                    // copy action
                    if (!$this->dircopy($item['tmp'], $target)) {
                        $error = "copy error<br/>". $item['tmp'] ."<br/>$target";
                        $retval = false;
                    }
                }
                 
            } else {
                $error = "find folders returned nothing";
                $retval = false;
            }
        }

        // cleanup
        if ($tmp) $this->dir_delete($tmp);

        if ($error) {
            $this->info[$plugin]['downloadfail'] = $error;
            echo "$error</li>";
        } else {
            file_put_contents($plugindir.'url.txt',$url);
            echo "successfully downloaded</li>";
            $retval = true;
        }
        return $retval;
    }

    /**
     * Find out what was in the extracted directory
     *
     * Correct folders are searched recursively using the "*.info.txt" configs
     * as indicator for a root folder. When such a file is found, it's base
     * setting is used (when set). All folders found by this method are stored
     * in the 'new' key of the $result array.
     *
     * For backwards compatibility all found top level folders are stored as
     * in the 'old' key of the $result array.
     *
     * When no items are found in 'new' the copy mechanism should fall back
     * the 'old' list.
     *
     *  --> copied from plugin:plugin
     *
     * @author Andreas Gohr <andi@splitbrain.org>
     * @param array &$result - results are stored here
     * @param string $base - the temp directory where the package was unpacked to
     * @param string $dir - a subdirectory. do not set. used by recursion
     * @return bool - false on error
     */
    private function find_folders(&$result,$base,$dir=''){
        $dh = @opendir("$base/$dir");
        if(!$dh) return false;
        while (false !== ($f = readdir($dh))) {
            if ($f == '.' || $f == '..' || $f == 'tmp') continue;

            if(!is_dir("$base/$dir/$f")){
                // it's a file -> check for config
                if($f == 'plugin.info.txt'){
                    $info = array();
                    $info['type'] = 'plugin';
                    $info['tmp']  = "$base/$dir";
                    $conf = confToHash("$base/$dir/$f");
                    $info['base'] = utf8_basename($conf['base']);
                    if(!$info['base']) $info['base'] = utf8_basename("$base/$dir");
                    $result['new'][] = $info;
                }elseif($f == 'template.info.txt'){
                    $info = array();
                    $info['type'] = 'template';
                    $info['tmp']  = "$base/$dir";
                    $conf = confToHash("$base/$dir/$f");
                    $info['base'] = utf8_basename($conf['base']);
                    if(!$info['base']) $info['base'] = utf8_basename("$base/$dir");
                    $result['new'][] = $info;
                }
            }else{
                // it's a directory -> add to dir list for old method, then recurse
                if(!$dir){
                    $info = array();
                    $info['type'] = 'plugin';
                    $info['tmp']  = "$base/$dir/$f";
                    $info['base'] = $f;
                    $result['old'][] = $info;
                }
                $this->find_folders($result,$base,"$dir/$f");
            }
        }
        closedir($dh);
        return true;
    }

    /**
     * Decompress a given file to the given target directory
     *
     * Determines the compression type from the file extension
     *
     *  --> copied from plugin:plugin
     *
     * @param string $file path to file
     * @param string $target directory
     * @return bool
     */
    private function decompress($file, $target) {
        global $conf;

        // decompression library doesn't like target folders ending in "/"
        if (substr($target, -1) == "/") $target = substr($target, 0, -1);

        $ext = $this->guess_archive($file);
        if (in_array($ext, array('tar','bz','gz'))) {
            switch($ext){
                case 'bz':
                    $compress_type = Tar::COMPRESS_BZIP;
                    break;
                case 'gz':
                    $compress_type = Tar::COMPRESS_GZIP;
                    break;
                default:
                    $compress_type = Tar::COMPRESS_NONE;
            }

            $tar = new Tar();
            try {
                $tar->open($file, $compress_type);
                $tar->extract($target);
                return true;
            }catch(Exception $e){
                if($conf['allowdebug']){
                    msg('Tar Error: '.$e->getMessage().' ['.$e->getFile().':'.$e->getLine().']',-1);
                }
                return false;
            }
        } else if ($ext == 'zip') {

            $zip = new ZipLib();
            $ok = $zip->Extract($file, $target);

            // FIXME sort something out for handling zip error messages meaningfully
            return ($ok==-1?false:true);

        }

        // unsupported file type
        return false;
    }

    /**
     * Determine the archive type of the given file
     *
     * Reads the first magic bytes of the given file for content type guessing,
     * if neither bz, gz or zip are recognized, tar is assumed.
     *
     *  --> copied from plugin:plugin
     * @author Andreas Gohr <andi@splitbrain.org>
     *
     * @param string $file path to file
     * @return boolean|string false if the file can't be read, otherwise an "extension"
     */
    private function guess_archive($file){
        $fh = fopen($file,'rb');
        if(!$fh) return false;
        $magic = fread($fh,5);
        fclose($fh);

        if(strpos($magic,"\x42\x5a") === 0) return 'bz';
        if(strpos($magic,"\x1f\x8b") === 0) return 'gz';
        if(strpos($magic,"\x50\x4b\x03\x04") === 0) return 'zip';
        return 'tar';
    }
}

