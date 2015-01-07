<?php

//require_once(DOKU_PLUGIN.'/plugingardener/readability.php');
require_once(DOKU_PLUGIN.'/plugingardener/Text-Statistics/TextStatistics.php');

class pg_dokuwikiwebexaminer extends pg_gardener {

    function execute() {
        echo "<h4>ExamineDokuwikiWeb</h4>\n";
        echo "<ul>";

        $plugins = $this->_getPluginList();
        $plugins = $this->_applyPluginLimits($plugins);
        $this->collections['plugins'] = $plugins;
        if (count($plugins) == 0) return false;
        
        $this->_examineHomepages($plugins);
        $this->_identifyNewPlugins($plugins);
        $this->_getPopularityData();
        $this->_getOldPopularityData();
        $this->_getPluginRepo();
        $this->_examineExternalHomepages();
        $this->_getEventList();
        echo "</ul>";
        return true;
    }

    function _getPluginRepo() {
        echo "<li>Plugin repo ";
        $localcopy = $this->cfg['localdir'].'repo.xml';
        if (($this->cfg['downloadindex'] || !file_exists($localcopy)) && !$this->cfg['offline']) {
            $markup = file_get_contents($this->cfg['doku_repo_uri']);
            file_put_contents($localcopy, $markup);
            echo "downloaded</li>\n";
        } else {
            $markup = file_get_contents($localcopy);
            echo "read from file</li>\n";
        }
        if (empty($markup)){
            echo "--> Error - no plugin repo\n";
            return;
        }
        $obj = new SimpleXMLElement($markup);
        $array = $this->obj_array($obj);
        foreach($array['plugin'] as $repo) {
            if ($this->info[$repo['id']]) {
                $this->info[$repo['id']]['repo'] = $repo;
                if (is_array($repo['compatible']['release'])) {
                    $this->info[$repo['id']]['bestcompat'] = $repo['compatible']['release'][0];
                } elseif ($repo['compatible']['release']) {
                    $this->info[$repo['id']]['bestcompat'] = $repo['compatible']['release'];
                }
            }
        }
    }
    /**
     * Converts objects to arrays     // TODO may be should be kept under parseutils??
     */
    function obj_array($obj) {
        $data = array();
        if (is_object($obj))
            $obj = get_object_vars($obj);
        if (is_array($obj) && count($obj)) {
            foreach ($obj as $index => $value) {
                if (is_object($value) || is_array($value))
                    $value = $this->obj_array($value);
                $data[$index] = $value;
            }
        }
        return count($data)? $data : null;
    }

    // list of plugins is taken from namespace index, local copy is kept after download
    function _getPluginList() {
        echo "<li>Plugin index ";
        $localcopy = $this->cfg['localdir'].'index.htm';
        if (($this->cfg['downloadindex'] || !file_exists($localcopy)) && !$this->cfg['offline']) {
            $markup = file_get_contents($this->cfg['doku_index_uri'].'?idx=plugin');
            file_put_contents($localcopy, $markup);
            echo "downloaded</li>\n";
        } else {
            $markup = file_get_contents($localcopy);
            echo "read from file</li>\n";
        }
        if (empty($markup)){
            echo "--> Error - no plugin index\n";
            return array();
        }
        preg_match_all('/\/plugin:([-.\w]*)/', $markup, $matches); 
        return $matches[1];
    }

    function _applyPluginLimits($plugins) {
        $retval = array();
        if ($this->cfg['firstplugin'] || $this->cfg['lastplugin']) {
            echo "<li>Limited to <b>".$this->cfg['firstplugin']."</b> through <b>".$this->cfg['lastplugin']."</b></li>\n";
        }
        foreach ($plugins as $plugin) {
            if (strcmp($plugin, $this->cfg['firstplugin']) < 0) continue;
            $retval[] = $plugin;
            if ($this->cfg['lastplugin'] && (strcmp($plugin, $this->cfg['lastplugin']) >= 0)) break;
        }
        return $retval;
    }

    // compare with previous year index
    function _identifyNewPlugins($plugins) {
        $markup = file_get_contents($this->cfg['localdir'].'index_old.htm');
        if (empty($markup)) return;

        preg_match_all('/\/plugin:([-.\w]*)/', $markup, $matches); 
        $previous_plugins = $matches[1];
        $newplugins = array_values(array_diff($plugins, $previous_plugins));
        foreach ($newplugins as $name) {
            $this->info[$name]['new'] = 'new';
        }
        echo "<li>New plugins identified</li>\n";
    }

    // download plugins main index page to get popularity
    function _getPopularityData() {
        echo "<li>Plugin popularity ";
        $localcopy = $this->cfg['localdir'].'plugins.htm';
        if (($this->cfg['downloadindex'] || !file_exists($localcopy)) && !$this->cfg['offline']) {
            $markup = file_get_contents($this->cfg['doku_index_uri'].'?pluginsort=p');
            file_put_contents($localcopy, $markup);
            echo "downloaded</li>\n";
        } else {
            $markup = file_get_contents($localcopy);
            echo "read from file</li>\n";
        }
        if (empty($markup)){
            echo "--> Error - no popularity page</li>\n";
            return;
        }
        preg_match_all('/\/plugin:([-.\w]*)(.*?)\<div class="prog-border" title="(\d+)/', $markup, $matches, PREG_SET_ORDER); 
        foreach ($matches as $plugin) {
            if (strcmp($plugin[1], $this->cfg['firstplugin']) < 0) continue;
            $this->info[$plugin[1]]['popularity'] = $plugin[3];
            if ($this->cfg['lastplugin'] && (strcmp($plugin[1], $this->cfg['lastplugin']) >= 0)) break;
        }
    }

    function _getOldPopularityData() {
        echo "<li>Plugin old popularity ";
        $localcopy = $this->cfg['localdir'].'plugins_old.htm';
        if (file_exists($localcopy)) {
            $markup = file_get_contents($localcopy);
            echo "read from file</li>\n";
        }
        if (empty($markup)){
            echo "--> Error - no popularity page</li>\n";
            return;
        }
        preg_match_all('/\/plugin:([-.\w]*)(.*?)\<div class="prog-border" title="(\d+)/', $markup, $matches, PREG_SET_ORDER); 
        foreach ($matches as $plugin) {
            if (strcmp($plugin[1], $this->cfg['firstplugin']) < 0) continue;
            if (!$this->info[$plugin[1]]) continue;
            $this->info[$plugin[1]]['popularity_old'] = $plugin[3];
            if ($this->cfg['lastplugin'] && (strcmp($plugin[1], $this->cfg['lastplugin']) >= 0)) break;
        }
    }

    // download list of DokuWiki events
    function _getEventList() {
        echo "<li>Event list page ";
        $localcopy = $this->cfg['localdir'].'eventlist.htm';
//        if (($this->cfg['downloadpages'] || !file_exists($localcopy)) && !$this->cfg['offline']) {
        if (($this->cfg['downloadpages'] || !file_exists($localcopy))) {
            $markup = file_get_contents($this->cfg['doku_eventlist_uri']);
            file_put_contents($localcopy,$markup);
            echo "downloaded</li>\n";
        } else {
            $markup = file_get_contents($localcopy);
            echo "read from file</li>\n";
        }
        if (empty($markup)){
            echo "--> Error - no events page</li>\n";
            return;
        }
        preg_match_all('/\<td\><a href=\"\/([^\"]+)\"[^>]+>(.*?)\<\/a\>.*?\<td\>([-\d]+)\<\/td\>/', $markup, $matches, PREG_SET_ORDER); 
        foreach ($matches as $event) {
            $this->collections['eventlist'][$event[2]]['url'] = $event[1];
            $this->collections['eventlist'][$event[2]]['date'] = $event[3];
        }
    }

    // download every plugin homepage and examine
    function _examineHomepages($plugins) {
        $localpagedir = $this->cfg['localdir'].'pages/';
        if (!file_exists($localpagedir)) {
            mkdir($localpagedir);
        }
        echo "<li>Examine plugin homepages...</li><ul>";
        foreach ($plugins as $plugin) {
            set_time_limit(60);
            $localcopy = $localpagedir.$plugin.'.htm';
			if (($this->cfg['downloadpages'] || !file_exists($localcopy)) && !$this->cfg['offline']) {
                $markup = file_get_contents($this->cfg['doku_pluginbase_uri'].$plugin);
                file_put_contents($localcopy, $markup);
            } else {
                $markup = file_get_contents($localcopy);
            }
            $this->_examinePluginHomepage($plugin, $markup);
            if ($this->info[$plugin]['download']) {
                $this->info[$plugin]['download'] = array_unique($this->info[$plugin]['download']);
            }
        }
        echo "</ul><li>...done</li>\n";
    }

    function _readabilityIndex($plugin, $page) {
        $page = explode('<ul id="pluginrepo__foldout">', $page, 2);
        $page = explode('</ul>', $page[1], 2);
        $page = explode('<!-- wikipage stop -->', $page[1]);
        $page = preg_replace('/<pre .*?<\/pre>/s','',$page[0]); //remove code blocks
        $page = preg_replace('!</(li|h[1-5])>!i','. ',$page); //make sentences from those tags
        $page = strip_tags($page);
        $this->info[$plugin]['textsize'] = strlen($page);

        // calc readability
        if ($this->cfg['fasteval']) return;

        $statistics = new TextStatistics('utf8');
        $this->info[$plugin]['readability_gf'] = $statistics->gunning_fog_score($page);
        $this->info[$plugin]['readability_fs'] = $statistics->flesch_kincaid_reading_ease($page);
        $this->info[$plugin]['readability_sm'] = $statistics->smog_index($page);
    }

    // HOMEPAGE
    function _examinePluginHomepage($plugin, $markup) {

        if (!preg_match('/<!-- TOC START -->/', $markup)) {
            $this->info[$plugin]['toc'] = 'no TOC';
        }

        $page = explode('<!-- wikipage start -->', $markup);
        $page = explode('bar__bottom', $page[1]);
        $page = $page[0];
        $this->info[$plugin]['pagesize'] = strlen($page);
        $this->_readabilityIndex($plugin, $page);

        preg_match_all('/by <a ([^>]+(mailto:[^>]+)"[^>]+)\>(.*?)\<\/a\>/', $page, $matches);
        $this->info[$plugin]['developer'] = $matches[3][0];

        if (preg_match('/\<span class="conflicts"\>Conflicts with \<em\>(.*?)\<\/em\>/', $page, $match)) {
            preg_match_all('/\/plugin:([-.\w]*)/', $match[1], $matches);
            $this->info[$plugin]['conflicts'] = $matches[1];
        }

        if (preg_match('/\<span class="depends"\>Requires \<em\>(.*?)\<\/em\>/', $page, $match)) {
            preg_match_all('/\/plugin:([-.\w]*)/', $match[1], $matches);
            $this->info[$plugin]['depends'] = $matches[1];
        }

        preg_match_all('/Last modified: (\d+[\/]\d+[\/]\d+)/', $page, $matches);
        $this->info[$plugin]['pagemodified'] = str_replace('/', '-', $matches[1][0]);

        preg_match_all('/\<span class="lastupd">Last updated on \<em\>(.*?)\<\/em\>/', $page, $matches);
        $this->info[$plugin]['lastupdate'] = $matches[1][0];

        if (preg_match('/&lt;\?php/', $page, $matches)) { // TODO: enhance for better match and count size and number of divs
            $this->info[$plugin]['code'] = 'yes';
        }

        preg_match_all('/img src="\/lib\/exe\/fetch\.php/', $page, $matches);
        $this->info[$plugin]['homepageimage'] = count($matches[0]);

        if (preg_match('/class="security".*?\<i\>(.*?)\<\/i\>/', $page, $matches)) {
            $this->info[$plugin]['security'] = $matches[1];
        }

        if (preg_match('/class="securitywarning".*?\<i\>(.*?)\<\/i\>/', $page, $matches)) {
            $this->info[$plugin]['security_w'] = $matches[1];
        }

        if (preg_match('/class="download" href="(.*?)"\>/', $page, $matches)) {
            $this->info[$plugin]['downloadbutton'] = $matches[1];
            $this->info[$plugin]['download'][] = $matches[1];
        }

        if (preg_match('/class="repo" href="(.*?)"\>/', $page, $matches)) {
            $this->info[$plugin]['repobutton'] = $matches[1];
        }

        if (preg_match('/class="bugs" href="(.*?)"\>/', $page, $matches)) {
            $this->info[$plugin]['bugsbutton'] = $matches[1];
        }

        if (preg_match('/class="donate" href="(.*?)"\>/', $page, $matches)) {
            $this->info[$plugin]['donatebutton'] = $matches[1];
        }

        if (preg_match('/\/devel:develonly/', $page)) {
            $this->info[$plugin]['develonly'] = 'yes';
        }

        preg_match_all('/<a [^>]+plugin:(\w*)[^>]+\>.*?\<\/a\>/', $page, $matches);
        $this->info[$plugin]['references'] = array_unique($matches[1]);

        preg_match_all('/<a [^>]+plugintag=([-\w]*)[^>]+\>(.*?)\<\/a\>/', $page, $matches);
        $this->info[$plugin]['tags'] = $matches[2];

        if (in_array('!bundled',$matches[2])) {
            $this->info[$plugin]['bundled'] = 'bundled';
        }
        if (in_array('!experimental',$matches[2]) || in_array('experimental',$matches[2])) {
            $this->info[$plugin]['experimental'] = 'yes';
        }

        $this->_examineLinks($plugin, $page, 'http://www.dokuwiki.org');
        return;
    }

    function _examineLinks($plugin, $page, $domain) {
        preg_match_all('/<a ([^>]+)>(.*?)<\/a>/', $page, $matches, PREG_SET_ORDER);
        foreach ($matches as $link) {
            if (preg_match('/<\/span>/', $link[2])) continue;
            if (preg_match('/<img /', $link[2])) continue;
            if (preg_match('/playground/', $link[1])) continue;

            if (preg_match('/href="(http:[^"]+)"/', $link[1], $url)) {
                if (!$this->_getDownloadLink($plugin, $url[1])) {
                    if (preg_match('/www.dokuwiki.org/', $url[1])) {
                        $this->info[$plugin]['dokulinks'][] = $url[1];
                    } else {
                        $this->info[$plugin]['links'][] = array($url[1],$link[2]);
                    }
                }

            } elseif (preg_match('/href="(\/[^"]+)"/', $link[1], $url)) {
                $this->_getDownloadLink($plugin, $domain.$url[1]);
            }
        }
    }

    // check if link looks like download url
    function _getDownloadLink($plugin, $url) {
        if (preg_match('/http:\/\/www\.splitbrain\.org/i', $url)) return false;
        if (preg_match('/\.(?:gz|tar|tgz)/i', $url)) {
            if (preg_match('/media=http/', $url)) {
				$this->info[$plugin]['mediaurl'] = 'yes';
                $url = explode('media=',$url);
                $url = $url[1];
            }
            $url = str_replace('%2F','/',str_replace('%3A',':',$url));
            $this->info[$plugin]['download'][] = $url;
            return true;

        } elseif (preg_match('/\.zip/i', $url) && !preg_match('/zip\./i', $url)) {
            if (preg_match('/media=http/', $url)) {
				$this->info[$plugin]['mediaurl'] = 'yes';
                $url = explode('media=',$url);
                $url = $url[1];
            }
            $url = str_replace('%2F','/',str_replace('%3A',':',$url));
            $this->info[$plugin]['download'][] = $url;
            return true;
        } elseif (preg_match('/http:\/\/github.com\/.*?ball/i',$url)) {
            $this->info[$plugin]['download'][] = $url;
            return true;
        }
        return false;
    }

    function _examineExternalHomepages() {
        $localdir = $this->cfg['localdir'];
        // read whitelist of allowed external links
/*        $allowedpages = array();
        $text = file_get_contents($localdir.'externalpages_ok.txt');
        preg_match_all('/^([^\t]+)\t([^\t]+)\t/m', $text ,$matches, PREG_SET_ORDER);
        foreach ($matches as $match) {
            $allowedpages[$match[1]] = $match[2];
        }

        // create list to check, write unknown to file
        $checkpages = array();
        $fp = fopen($localdir.'externalpages_new.txt', 'w');
        foreach ($this->info as $name => $plugin) {
            if (!$plugin['download'] && $plugin['links']) {
                $url = htmlspecialchars_decode($plugin['links'][0][0]);
                if ($allowedpages[$name] && $allowedpages[$name] == $url) {
                    $checkpages[$name] = $url;
                } else {
                    fwrite($fp,"$name\t". $url ."\t". $plugin['links'][0][1] ."\n");
                }
            }
        }
        fclose($fp);
*/
        $external_fn = $localdir.'externalpages.txt';
        $externalpages = array();
        $checkpages = array();
        if (file_exists($external_fn)) {
            $externalpages = unserialize(file_get_contents($external_fn));
        }

        foreach ($this->info as $name => $plugin) {
            if ((!$plugin['downloadbutton'] || $plugin['textsize'] < 250) && $plugin['links']) {
                $externalpages[$name]['links'] = $plugin['links'];
                $plugin['externalpage'] = 'maybe';
                $selectedUrlvalid = false;
                foreach ($plugin['links'] as $link) {
                    if ($link[0] == $externalpages[$name]['selected']) {
                        $selectedUrlvalid = true;
                    }
                }
                if (!$selectedUrlvalid) {
                    $externalpages[$name]['selected'] = '';
                } else {
                    $checkpages[$name] = $externalpages[$name]['selected'];
                }
            } else {
                unset($externalpages[$name]);
            }
        }
        file_put_contents($external_fn, serialize($externalpages));
        echo "<li>List of external pages created</li><ul>\n";

        // download every page and examine
        $localpagedir = $localdir.'pages/';
        foreach ($checkpages as $plugin => $pageurl) {
            set_time_limit(120);
            if (($this->cfg['downloadpages'] || !file_exists($localpagedir.$plugin.'.external.htm')) && !$this->cfg['offline']) {
                echo "<li><a href=\"http://www.dokuwiki.org/plugin:$plugin\">$plugin</a> - <a href=\"$pageurl\">$pageurl</a></li>\n";
                $markup = @file_get_contents($pageurl);
                if ($markup == false) {
                    $this->info[$plugin]['externalpage'] = 'broken';
                    continue;
                } else {
                    file_put_contents($localpagedir.$plugin.'.external.htm',$markup);
                }
            } else {
                $markup = @file_get_contents($localpagedir.$plugin.'.external.htm');
                if ($markup == false) {
                    $this->info[$plugin]['externalpage'] = 'broken';
                    continue;
                }
            }

            $this->info[$plugin]['externalpage'] = 'yes';
            preg_match('/^http:\/\/[^\/]+/', $pageurl, $domain);
            $this->_examineLinks($plugin, $markup, $domain[0]);
            if ($this->info[$plugin]['download']) {
                $this->info[$plugin]['download'] = array_unique($this->info[$plugin]['download']);
            }
        }
        echo "</ul><li>External pages examined</li>\n";
    }

}

