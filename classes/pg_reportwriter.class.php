<?php
class pg_reportwriter extends pg_gardener {

    function execute() {
        echo "<h4>ReportWriter</h4>\n";
        echo "<ul>";
        $localoutputdir = $this->cfg['localdir'].'output/';
        if (!file_exists($localoutputdir)) {
            mkdir($localoutputdir);
        }

        $this->_export_csv($localoutputdir,false);
        $this->_export_csv($localoutputdir,true);
        
        // unlink not plugins
        foreach ($this->collections['notPlugins'] as $notplugin) {
            unset($this->info[$notplugin]);
        }

        $this->_export_summary($localoutputdir);
        $this->_export_compatibility($localoutputdir);

        $this->_export_codestyle($localoutputdir);
        $this->_export_events($localoutputdir);

//        $this->_export_navigation($localoutputdir);
        $this->_export_tags($localoutputdir);
        $this->_export_alphaindex($localoutputdir);

//        $this->_export_references($localoutputdir);

        $this->_export_friendliness($localoutputdir);
        $this->_export_developers($localoutputdir);
        $this->_export_trackedDevErrors($localoutputdir);
        echo "</ul>";
        return true;
    }

    function _sharedFuncs() {
    }

    function _checkDev($plugin,$info,$error) {
        if (in_array(strtolower($info['developer']), $this->collections['trackedDevelopers'])) {
            $this->collections['trackedDevErr'][$info['developer']][$error][] = pl($plugin);
        }
    }

    function _export_summary($localoutputdir) {
        function pl($name) {
 //            return "[[doku>plugin:$name|$name]]";
           return "[[plugin:$name]]";  // --------------------------------------------------- DEBUG -------------- changes link style -------------------
        }

        $resultFile = $localoutputdir.'summary.txt';
        echo "<li>$resultFile</li>";
        $total = count($this->info);
        foreach ($this->info as $name => $info) {
            if ($info['bundled']) {
                $bundled += 1;
                $bundled_p[] = pl($name);
            }
            if ($info['maindownload']) {
                $downloadbutton += 1;
            } elseif (!$info['bundled']) {
                $this->_checkDev($name, $info, "Missing download button");
            }
            if ($info['bugs']) {
                $bugsbutton += 1;
            }
            if ($info['repo']) {
                $repobutton += 1;
            }
            if ($info['donate']) {
                $donatebutton += 1;
            }
            if ($info['downloadfail']) {
                $broken += 1;
                $broken_p[] = pl($name);
            }
            if ($info['manualdownload'] && !$info['bundled']) {
                $manualdownload += 1;
                $this->_checkDev($name, $info, "Needed manual download");
            }
            if ($info['externalpage'] == 'yes') {
                $externalpages += 1;
            }
            if ($info['externalpage'] == 'broken') {
                $externalbroken_p[] = pl($name);
            }
            if ($info['code']) {
                if ($info['download']) {
                    $codeandlink += 1;
                } else {
                    $codeonly += 1;
                }
            }
            if ($info['download']) {
                $download += 1;
                if (preg_match('/github/i',$info['download'][0])) {
                    $github_p[] = pl($name);
                }
                if (preg_match('/googlecode/i',$info['download'][0])) {
                    $google_p[] = pl($name);
                }
                if (preg_match('/bz2/i',$info['download'][0])) {
                    $this->_checkDev($name, $info, "Uses BZ2 compression");
                    $bz2_p[] = pl($name);
                }
            } elseif ($info['popularity'] > 200) {
                $popular_nolink_p[] = pl($name);
            }
            if ($info['downloadexamined']) $downloadexamined += 1;
        }

        $fp = fopen($resultFile, 'w');
        fwrite($fp,"====== Plugin Survey ".date('Y')." ======\n");
        fwrite($fp,"This is a survey of all [[:plugins]] present in the plugin namespace of %%www.dokuwiki.org%%.\n");
        fwrite($fp,"Data was collected by an automated script 2010-09-05 with some additional manual data download and mining.\n");
        fwrite($fp,"$manualdownload ouch!\n");
        fwrite($fp,"A total of ".count($this->collections['plugins'])." plugin pages containing $total plugins was examined and source code for $downloadexamined plugins (". round($downloadexamined/$total*100) ."%) downloaded and parsed.\n");
        fwrite($fp,($total - $this->cfg['previousYearTotal'])." new plugins has been released since previous survey in august 2009 giving a groowth figure of ". round(100*$total/$this->cfg['previousYearTotal']-100) ."%/year.\n");
        fwrite($fp,"  * ");
        foreach ($this->collections['newPlugins'] as $name) {
			if ($this->info[$name]['popularity']) {
                fwrite($fp,pl($name));
            }
		}
        fwrite($fp,"\n");
        fwrite($fp,"This is not plugins\n");
        fwrite($fp,"  * ");
        foreach ($this->collections['notPlugins'] as $name) {
			fwrite($fp,pl($name));
		}
        fwrite($fp,"\n");
        fwrite($fp,"\n");
        fwrite($fp,"====== Plugin Survey ".date('Y')." - Deployment ======\n");
        fwrite($fp,"\n");
        fwrite($fp,"===== Bundled =====\n");
        fwrite($fp,"$bundled plugins (". round($bundled/$total*100) ."%) are marked [[plugintag>!bundled]] (i.e. included in the release).\n");
        fwrite($fp,"  * ".($bundled_p?implode(', ', $bundled_p):'')."\n");
        fwrite($fp,"\n");
        fwrite($fp,"===== Download package =====\n");
        fwrite($fp,"A convenient way of installing is by giving a link to the [[plugin:plugin|plugin manager]].\n");
        fwrite($fp,"$download plugin pages (". round($download/$total*100) ."%) have some sort of download link. ");
        fwrite($fp,"Among those were $broken broken at the time of survey.\n");
        fwrite($fp,"  * ".($broken_p?implode(', ', $broken_p):'')."\n");
        fwrite($fp,"\n");
		
        fwrite($fp,"New repository function released in december? - download button is used by $downloadbutton plugins (". round($downloadbutton/$total*100) ."%).\n");
        fwrite($fp,"^Button ^ Plugins ^\n");
        fwrite($fp,"|Download | $downloadbutton (". round($downloadbutton/$total*100) ."%)|\n");
        fwrite($fp,"|Bugs | $bugsbutton (". round($bugsbutton/$total*100) ."%)|\n");
        fwrite($fp,"|Repo | $repobutton (". round($repobutton/$total*100) ."%)|\n");
        fwrite($fp,"|Donate | $donatebutton (". round($donatebutton/$total*100) ."%)|\n");
        fwrite($fp,"\n");
				
        fwrite($fp,"There is a visible trend for a common repository. Previous year 22 plugins where avaliable at GitHub. Including GoogleCode plugins represented 10% of the plugins with download link.");
        fwrite($fp,"Now ".count($github_p). " plugins are located on [[http://www.github.com|GitHub]] and ");
        fwrite($fp,count($google_p). " plugins are located on [[http://www.googlecode.com|GoogleCode]]. ");
        fwrite($fp,"Together this accounts for ". round((count($github_p)+count($google_p))/$download*100) ."% of the plugins with download link.\n");
        fwrite($fp,"\n");
		
        fwrite($fp,"=== External sites ===\n");
        fwrite($fp,"$externalpages plugins (". round($externalpages/$total*100) ."%) have their “Details and download” page somewhere outside www.dokuwiki.org ");
        fwrite($fp,"and ". count($externalbroken_p) ." were broken at the time of survey.\n");
        fwrite($fp,"  * ".($externalbroken_p?implode(', ', $externalbroken_p):'')."\n");
        fwrite($fp,"=== BZ2 compression ===\n");
        fwrite($fp,count($bz2_p) ." plugins with bz2.\n");
        fwrite($fp,"  * ".($bz2_p?implode(', ', $bz2_p):'')."\n");
        fwrite($fp,"\n");
        fwrite($fp,"===== Without plugin package =====\n");
        fwrite($fp,"Another way of installing plugins are by cut-n-paste code snippets from the plugin homepage following the [[:plugin installation instructions]]. ");
        fwrite($fp,"There are $codeonly plugins (". round($codeonly/$total*100) ."%) that only offer manual installation. ");
        fwrite($fp,"$codeandlink plugins has at least one php %%<code>%% section besides a download link.\n");
        fwrite($fp,"\n");
        fwrite($fp,count($popular_nolink_p). " popular((more than 200 installations)) plugins are missing a download package.\n");
        fwrite($fp,"  * ".($popular_nolink_p?implode(', ', $popular_nolink_p):'')."\n");
        fclose($fp);
    }

    function _export_compatibility($localoutputdir) {
        function sortsubarray_callback($a, $b) {
            if ($a[0] == $b[0]) return 0;
            return ($a[0] < $b[0]) ? -1 : 1;
        }

        $resultFile = $localoutputdir.'compatibility.txt';
        echo "<li>$resultFile</li>";

        $total = count($this->info);
        $lastyear = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
        $conflicts = array();
        $conflictkeys = array();
        $gready = array();
        $regexp = array();
        $regexp_special = array();
        $nameconflict = array();
		$conflictgrp = array();
        $renderer_p = array();
        $canrender_p = array();
        $lookahead_p = array();
        $wing_p = array();
        $gull_p = array();
        $tagsyntax_p = array();
        $regfind = array('"', "'",'\\x3c', '\\x3e', '\\');
        $regrepl = array('' , '' ,'<' ,    '>', '');
        $releases = array('2009-12-25', '2009-02-14', '2008-05-05', '2007-06-26', '2006-11-06');
        foreach ($this->info as $name => $info) {
            $pagemod[substr($info['pagemodified'],0,-3)] += 1;
            $lastmod[substr($info['lastmodified'],0,-3)] += 1;
            if ($info['pagemodified'] > $lastyear) $pagemodified += 1;
            if ($info['pagemodified'] > $releases[0]) $pagemodifiedlastrelease += 1;
            if (!$info['compability']) {
                $nocompat += 1;
                $this->_checkDev($name, $info, "No compability info");
            }
            if ($info['canRender']) {
                $canrender_p[] = pl($name);
            }
            foreach ($releases as $key => $rel) {
                if (preg_match('/'.$rel.'/', $info['compability'])) {
                    $releasecount[$key] += 1;
                }
            }
            if (preg_match('/(later|\+)/',$info['compability'])) {
                $andorlater += 1;
            }
            if ($info['tags'] && (in_array('!broken', $info['tags']) || in_array('!maybe.broken', $info['tags']))) {
                $broken += 1;
                $broken_p[] = pl($name);
                $this->_checkDev($name, $info, "Tagged !broken");
            }
            if ($info['conflicts']) {
                $this->_checkDev($name, $info, "Marked as conflicting");
                $tmp = array();
                $tmp[] = $name;
                $conflictkeys[] = $name;
                foreach ($info['conflicts'] as $conflict) {
                    $tmp[] = $conflict;
                    $conflictkeys[] = $conflict;
                    $conflicts[$name][$conflict]= ':-(';
                }
                $conflictgrp[] = $tmp;
            }
            if (!$info['plugin']) continue; // code module analyzed
            foreach ($info['plugin'] as $module) {
                if ($module['name']) $nameconflict[$module['name']][] = pl($name);
                if ($module['type'] == 'renderer') {
                    $renderer_p[] = pl($name);
                }
                if ($module['regexp_special']) {
                    foreach ($module['regexp_special'] as $reginfo) {
                        $sort = str_replace($regfind,$regrepl, strtolower($reginfo));
                //        $sort = str_replace($regfind2,$regrepl2, $sort);
                        if (preg_match('/[^=]\.\*[^?]/',$reginfo)) $gready[] = array(pl($name), $reginfo);
                        $config = (preg_match('/$[a-zA-Z]]/',$reginfo) ? 'configurated': '');
                        $regexp_special[] = array("%%$sort%%", "%%$reginfo%%", $name, $config);
                        if (preg_match('/\\\\?{\\\\?{/',$reginfo)) $wing_p[] = $name;
                        if (preg_match('/\\\\?~\\\\?~/',$reginfo)) $gull_p[] = $name;
                        if (preg_match('/["\']?</',$reginfo)) $tagsyntax_p[] = $name;
                    }
                }
                if ($module['regexp_entry']) {
                    foreach ($module['regexp_entry'] as $reginfo) {
                        $sort = str_replace($regfind,$regrepl, strtolower($reginfo));
                //        $sort = str_replace($regfind2,$regrepl2, $sort);
                        if (preg_match('/[^=]\.\*[^?]/',$reginfo)) $gready[] = array(pl($name), $reginfo);
                        $config = (preg_match('/$[a-zA-Z]]/',$reginfo) ? 'configurated': '');
                        if (preg_match('/\(\?=/',$reginfo)) {
                            $lookahead = '';
                        } else {
                            $lookahead = ':-(';
                            $lookahead_p[] = $name;
                            $this->_checkDev($name, $info, "Missing lookahead pattern?");
                        }
                        if (preg_match('/["\']?</',$reginfo)) $tagsyntax_p[] = $name;
                        if ($module['regexp_exit']) {
                            $regexp[] = array("%%$sort%%", "%%$reginfo%%",$name,'%%'.$module['regexp_exit'][0].'%%', $config, $lookahead); // TODO !!!!
                        } else {
                            $regexp[] = array("%%$sort%%", "%%$reginfo%%",$name,'', $config, $lookahead);
                        }
                    }
                }
//                if ($module['regexp_exit']) {
//                    foreach ($module['regexp_exit'] as $regexp)
//                        fwrite($fp,"%%$regexp%%\\\\ ");
            }
        }


        uasort($regexp, "sortsubarray_callback");
        uasort($regexp_special, "sortsubarray_callback");
        $lookahead_p = array_unique($lookahead_p);
        $wing_p = array_unique($wing_p);
        $gull_p = array_unique($gull_p);
        $tagsyntax_p = array_unique($tagsyntax_p);
        $conflictkeys = array_unique($conflictkeys);
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"====== Plugin Survey ".date('Y')." - Compatibility ======\n");
        fwrite($fp,"Are we up to date? Hard to tell, only ".$releasecount[0]." (".round($releasecount[0]/$total*100)."%) explicitly states compatibility with Dokuwiki ".$releases[0]." ");
        fwrite($fp,"and an additional $andorlater plugins (". round($andorlater/$total*100) ."%) uses \"or later\" or \"...+\".\n");
        fwrite($fp,"$nocompat plugins (". round($nocompat/$total*100) ."%) has no compatibility information at all.\n");
        fwrite($fp,"But more than half ($pagemodifiedlastrelease plugins) have homepages modified __after__ current release, even if some now has comment like \"It doesn't work anymore\" ((citation needed)).\n");
        fwrite($fp,"Anyway the community is active, more than ". round($pagemodified/$total*100) ."% of the plugin homepages are modified within last 12 months.\n");
        fwrite($fp,"\n");
        fwrite($fp,"^Release ^ Plugins ^\n");
        foreach ($releases as $key => $rel) {
            fwrite($fp,"|$rel |  ".$releasecount[$key]." (".round($releasecount[$key]/$total*100)."%) |\n");
        }
        fwrite($fp,"\n");
        fwrite($fp,"===== Broken =====\n");
        fwrite($fp,"$broken plugins (". round($broken/$total*100) ."%) are tagged \"!broken\" or \"!maybe.broken\" which is an obvious way of stating incompatibility with the current DokuWiki version.\n");
        fwrite($fp,"  * ".($broken_p?implode(', ', $broken_p):'')."\n");
        fwrite($fp,"\n");
        fwrite($fp,"===== Conflicts =====\n");
        fwrite($fp,count($conflictkeys)." plugins states conflict with one or more other.\n");
        foreach ($conflictgrp as $conflicts_p) {
            fwrite($fp,"  * ".($conflicts_p?implode(', ', $conflicts_p):'')."\n");
        }
        fwrite($fp,"\n");
        fwrite($fp,'| ');
        foreach ($conflictkeys as $key2) {
            fwrite($fp,'^'.$key2);
        }
        fwrite($fp,"^\n");
        foreach ($conflictkeys as $key1) {
            fwrite($fp,'^'.$key1);
            foreach ($conflictkeys as $key2) {
                if ($conflicts[$key1][$key2]) {
                    fwrite($fp,' |'.$conflicts[$key1][$key2]);
                } else {
                    fwrite($fp,' |');
                }
            }
            fwrite($fp,"|\n");
        }
        fwrite($fp,"\n");
        fwrite($fp,"Plugins sharing the same class name.\n");
        fwrite($fp,"^Class ^Plugins ^\n");
        foreach ($nameconflict as $name => $plugins) {
            $plugins = array_unique($plugins);
            if (count($plugins) > 1) {
                fwrite($fp,"|$name |". implode(', ', $plugins) ." |\n");
            }
        }
        fwrite($fp,"\n");
        fwrite($fp,"\n");
        fwrite($fp,"===== Syntax =====\n");
        fwrite($fp,"Are the syntax used by [[syntax_plugins]] compatible? Hard to tell here is a list of the regex arguments used by number of plugins. ");
        fwrite($fp,"The downloaded code has been scanned for addEntryPattern, addExitPattern and addSpecialPattern.\n");
        fwrite($fp,"It looks like %%{{%% ".count($wing_p)." v.s. %%~~%% ".count($gull_p)." v.s. <tag> ".count($tagsyntax_p)."\n");
        fwrite($fp,"\n");
        fwrite($fp,"Beware of the totaly configurated, gready things\n");
        fwrite($fp,"^Plugin ^Regex ^\n");
        foreach ($gready as $data) {
            fwrite($fp,"|$data[0] |%%$data[1]%% |\n");
        }
        $lookahead_p = array_unique($lookahead_p);
        fwrite($fp,"\n");
        fwrite($fp,count($lookahead_p)." plugins are probably missing lookahead patterns.\n");
        fwrite($fp,"  * ". implode(', ', $lookahead_p) ." |\n");
        fwrite($fp,"\n");
        fwrite($fp,"\nSee the complete list of [[plugin_survey_syntax|syntaxes]] at time of survey.\n\n");

/*        foreach ($this->info as $name => $info) {
            if (!$info['plugin']) continue; // code module analyzed
            foreach ($info['plugin'] as $module) {
                if (!$module['regexp_special']) continue;
                fwrite($fp,"|$name |");
                foreach ($module['regexp_special'] as $regexp)
                    fwrite($fp,"%%$regexp%%\\\\ ");
                fwrite($fp," |\n");
            }
        }
        fwrite($fp,"\n");
        fwrite($fp,"^Plugin ^Entry Pattern ^Exit Pattern ^Look ahead ^ \n");
        foreach ($this->info as $name => $info) {
            if (!$info['plugin']) continue; // code module analyzed
            foreach ($info['plugin'] as $module) {
                if (!$module['regexp_entry']) continue;
                fwrite($fp,"|$name |");
                foreach ($module['regexp_entry'] as $regexp)
                    fwrite($fp,"%%$regexp%%\\\\ ");
                fwrite($fp," |");
                if ($module['regexp_exit']) {
                    foreach ($module['regexp_exit'] as $regexp)
                        fwrite($fp,"%%$regexp%%\\\\ ");
                }
                fwrite($fp," | |\n");
            }
        } */
        fwrite($fp,"\n");
        fwrite($fp,"===== JavaScript =====\n");
        fwrite($fp,"FIXME watch out for Inline java handlers [[javascript#event_handling]]\n");
        fwrite($fp,"\n");
        fwrite($fp,"===== Replacement Renderers =====\n");
        fwrite($fp,"Only Analyzed code! Inherently conflict , one at a time\n");
        fwrite($fp,count($renderer_p)." plugins have a render module.\n");
        fwrite($fp,"  * ".($renderer_p?implode(', ', $renderer_p):'')."\n");
        fwrite($fp,"\n");
        fwrite($fp,"REPLACEMENT -> ".count($canrender_p)." plugins uses canRender() function.\n");
        fwrite($fp,"  * ".($canrender_p?implode(', ', $canrender_p):'')."\n");
        fwrite($fp,"\n");
        $renderer_p = array_diff($renderer_p, $canrender_p);
        fwrite($fp,count($renderer_p)." diff\n");
        fwrite($fp,"  * ".($renderer_p?implode(', ', $renderer_p):'')."\n");
        fwrite($fp,"\n");
        fwrite($fp,"\n");
        fwrite($fp,"-----------------------------------------------------------------------------------\n");
        fwrite($fp,"pagemodified chart\n");
        ksort($pagemod);
        foreach ($pagemod as $month => $number) {
            fwrite($fp,"$month\t$number.\n");
        }
        fwrite($fp,"lastmodified chart\n");
        ksort($lastmod);
        foreach ($lastmod as $month => $number) {
            fwrite($fp,"$month\t$number.\n");
        }
        fclose($fp);

        $resultFile = $localoutputdir.'syntax.txt';
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"====== Plugin Survey ".date('Y')." - Syntax ======\n");
        fwrite($fp,"<sup>(This is a part of the [[start|plugin survey ".date('Y')."]])</sup>\n");
        fwrite($fp,"$total plugins, downloaded during the survey, has been scanned for\n");
        fwrite($fp,"\n");
        fwrite($fp,"  * addSpecialPattern() - found ".count($regexp_special)."\n");
        fwrite($fp,"  * addEntryPattern() and addExitPattern() - found ".count($regexp)."\n");
        fwrite($fp,"\n");
        fwrite($fp,"This is the result, read more about [[compatibility#syntax|syntax compatibility]].\n");

        fwrite($fp,"===== Special patterns =====\n");
        fwrite($fp,"^Plugin ^Special Pattern ^ \n");
        foreach ($regexp_special as $reginfo) {
            fwrite($fp,"|$reginfo[1] |".pl($reginfo[2])." |\n");
        }
        fwrite($fp,"\n");
        fwrite($fp,"===== Entry/exit patterns =====\n");
        fwrite($fp,"\n");
        fwrite($fp,"^Plugin ^Entry Pattern ^Exit Pattern ^Look ahead ^ \n");
        foreach ($regexp as $reginfo) {
            fwrite($fp,"|$reginfo[1] |$reginfo[3]  |".pl($reginfo[2])." |  $reginfo[5]  |\n");
        }
        fwrite($fp,"\n");
        fwrite($fp,"<sub>Back to <= [[Compatibility#syntax|Compatibility]]</sub>\n");
        fclose($fp);
    }

    function _export_codestyle($localoutputdir) {
        function median($array) {
            if (count($array) == 0) return 0;
            if (count($array) == 1) return $array[0];
            if (count($array) % 2 == 0) {
                $idx = floor(count($array)/2);
                return ($array[$idx]+$array[$idx+1])/2;
            } else {
                return $array[floor(count($array)/2)];
            }
        }

        $resultFile = $localoutputdir.'codestyle.txt';
        echo "<li>$resultFile</li>";

        $total = count($this->info);
        $php_lines = array();
        $java_lines = array();
        $css_lines = array();
		$version_dev = array();
		$conftohash_dev = array();
        $oldreplacements = array();
        $cssprint = array();
        $cssrtl = array();
        $plugininfotxt_dev = array();
        foreach ($this->info as $name => $info) {
            if ($info['downloadexamined']) $downloadexamined += 1;
            if ($info['php_lines']) {
                $php_lines[] = $info['php_lines'];
            }
            if ($info['java_lines']) $java_lines[] = $info['java_lines'];
            if ($info['css_lines']) $css_lines[] = $info['css_lines'];
            if ($info['conf']) {
                $conf += 1;
                if ($info['conf'] == 'nometa') $conf_p[] = pl($name);
            }
            if ($info['version']) {
                $version[] = pl($name);
                $version_dev[] = $info['developer'];
            }
            if ($info['conftohash']) {
                $conftohash[] = pl($name);
                $conftohash_dev[] = $info['developer'];
            }
            if ($info['plugininfotxt']) {
                $plugininfotxt[] = pl($name);
                $plugininfotxt_dev[] = $info['developer'];
            } else {
                $this->_checkDev($name, $info, "Doesn't use info.txt");
            }
            if ($info['tags'] && in_array('ajax', $info['tags'])) {
                $ajax_p[] = pl($name);
            }
            if ($info['css']) {
                $css_p[] = pl($name);
            }
            if ($info['cssprint']) {
                $cssprint[] = pl($name);
            }
            if ($info['cssrtl']) {
                $cssrtl[] = pl($name);
            }
            if ($info['cssreplacements']) {
                $this->_checkDev($name, $info, "Uses old CSS replacements");
                foreach ($info['cssreplacements'] as $rep) {
                    $oldreplacements[$rep][] = pl($name);
                }
            }
            if ($info['javascript']) {
                $javascript += 1;
                $javascript_p[] = pl($name);
            }
            if ($info['javainclude']) {
                $javainclude[] = pl($name);
            }
            if ($info['java_ajax']) {
                $java_ajax[] = pl($name);
            }
            if ($info['javatoolbar']) {
                if ($info['javatoolbar'] == 'yes') {
                    $javatoolbar[] = pl($name);
                } else {
                    $javatoolbar_d[] = pl($name);
                }
            }
        }
        if ($downloadexamined == 0) $downloadexamined = 1;
        sort($php_lines);
        sort($java_lines);
        $version_dev = array_unique($version_dev);
        $conftohash_dev = array_unique($conftohash_dev);
        $conftohash_dev = array_unique($plugininfotxt_dev);
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"===== Plugin Survey ".date('Y')." - Code =====\n");
        fwrite($fp,"The $downloadexamined downloaded plugins could be analysed and commented in a number of ways, here are some ;-). ");
        fwrite($fp,"Metrics was done by [[http://cloc.sourceforge.net/|CLOC]] and comparisions are with DokuWiki 2009-02-14b (bundled plugins included).\n");

        fwrite($fp,"\n");
        fwrite($fp,"Some text about README, __FILE__ and configtohash.\n");
        fwrite($fp,count($plugininfotxt)." Uses plugin.info.txt\n");
        fwrite($fp,"  * ".($plugininfotxt?implode(', ', $plugininfotxt):'')."\n");
        fwrite($fp,"  * ".($plugininfotxt_dev?implode(', ', $plugininfotxt_dev):'')."\n");
        fwrite($fp,count($conftohash)." Uses conftohash\n");
        fwrite($fp,"  * ".($conftohash?implode(', ', $conftohash):'')."\n");
        fwrite($fp,"  * ".($conftohash_dev?implode(', ', $conftohash_dev):'')."\n");
        fwrite($fp,count($version)." Uses 'date'   => @file_get_contents(DOKU_PLUGIN.'adminhomepage/VERSION')\n");
        fwrite($fp,"  * ".($version?implode(', ', $version):'')."\n");
        fwrite($fp,"  * ".($version_dev?implode(', ', $version_dev):'')."\n");
        fwrite($fp,"\n");

        fwrite($fp,"==== PHP ====\n");
        fwrite($fp,"Plugins contains a total of ".array_sum($php_lines)." lines of PHP, that could be compared to DokuWiki's approx. 82,000 lines of code.\n");
        if (count($php_lines) > 0) {
            fwrite($fp,"Largest plugin is [[plugin:openid]]?? with ".max($php_lines)." lines of code and smallest is ".min($php_lines)." lines.\n");
        }
        fwrite($fp,"FIXME only to provide info, java only plugin\n");
        fwrite($fp,"Median plugin is a syntax plugin with ".median($php_lines)." lines of code.\n");

        fwrite($fp,"=== Config ===\n");
        fwrite($fp,"To make a plugin or template configurable you have to provide a ''lib/plugins/<plugin>/conf/default.php'' which will \n");
        fwrite($fp,"hold the default settings and a ''lib/plugins/<plugin>/conf/metadata.php'' which holds the describing\n");
        fwrite($fp,"[[#Configuration Metadata]] which is used by the [[plugin:config|Configuration Manager]] to handle/display the options\n");
        fwrite($fp,"see [[configuration]]\n");
        fwrite($fp,"$conf plugins are configurable through the admin.\n");
        fwrite($fp,count($conf_p)." plugins are missing metadata.\n");
        fwrite($fp,"  * ".($conf_p?implode(', ', $conf_p):'')."\n");

        fwrite($fp,"=== Toolbar ===\n");
        fwrite($fp," Uses TOOLBAR_DEFINE to add toolbar buttons.\n");

        fwrite($fp,"==== JavaScript ====\n");
        fwrite($fp,"$javascript plugins (". round($javascript/$downloadexamined*100) ."%) uses [[javascript]] to enhance the user experiance.\n");
        fwrite($fp,"There is a total of ".array_sum($java_lines)." lines of JavaScript compared to DokuWiki's approx. 2,300 lines. [[plugin:remotescript]]?? is one of the largest with ");
        if (count($java_lines) > 0) {
            fwrite($fp, max($java_lines)." lines of code JsHttpRequest but the median is only ".median($java_lines)." lines of code.\n");
        }
        fwrite($fp,"  * ".($javascript_p?implode(', ', $javascript_p):'')."\n");
        fwrite($fp,"Uses DokuWiki java include\n");
        fwrite($fp,"  * ".($javainclude?implode(', ', $javainclude):'')."\n");

        fwrite($fp,"=== Toolbar ===\n");
        fwrite($fp,"Adding a button [[devel:toolbar#using JavaScript]] is just as simple as doing it in PHP, but not nearly as popular.\n");
        fwrite($fp,"\n");
        fwrite($fp,count($javatoolbar)." plugins are using the static data method accessing ''toolbar[...]''\n");
        fwrite($fp,"  * ".($javatoolbar?implode(', ', $javatoolbar):'')."\n");
        fwrite($fp,count($javatoolbar_d)." plugins are using dynamic method based on ''getElementById('tool%%__%%bar')''\n");
        fwrite($fp,"  * ".($javatoolbar_d?implode(', ', $javatoolbar_d):'')."\n");
        fwrite($fp,"\n");

        fwrite($fp,"=== AJAX ===\n");
        fwrite($fp,count($ajax_p)." are tagged AJAX\n");
        fwrite($fp,"  * ".($ajax_p?implode(', ', $ajax_p):'')."\n");
        fwrite($fp,"Some are [[plugintag>axaj|tagged ajax]] and some uses the event...\n");
        fwrite($fp,"  *\n");
        fwrite($fp,count($java_ajax)." Found in js (runAJAX).\n");
        fwrite($fp,"  * ".($java_ajax?implode(', ', $java_ajax):'')."\n");

        fwrite($fp,"==== CSS ====\n");
        fwrite($fp,count($css_lines)." (". round(count($css_lines)/$downloadexamined*100) ."%) uses [[css]].\n");
        fwrite($fp,"There is a total of ".array_sum($css_lines)." lines of CSS. ?? is one of the largest with ");
        if (count($css_lines) > 0) {
            fwrite($fp, max($css_lines)." lines but the median is only ".median($css_lines)." lines of CSS.\n");
        }
        fwrite($fp,"  * ".($css_p?implode(', ', $css_p):'')."\n");
        fwrite($fp,"=== Modes ===\n");
        fwrite($fp,"\n");
        fwrite($fp,count($cssprint)." plugins with ''Print.css''.\n");
        fwrite($fp,"  * ".($cssprint?implode(', ', $cssprint):'')."\n");
        fwrite($fp,"\n");
        fwrite($fp,count($cssrtl)." plugins with ''rtl.css''.\n");
        fwrite($fp,"  * ".($cssrtl?implode(', ', $cssrtl):'')."\n");

        fwrite($fp,"=== Guaranteed color placeholders ===\n");
        fwrite($fp,"Plugins using obsolete css [[devel:css#replacements]] before 2006-08-05.\n");

        fwrite($fp,"\n^Old replacements ^Plugins ^\n");
        foreach ($oldreplacements as $name => $plugins) {
            fwrite($fp,"|%%$name%% |".implode(', ', $plugins)."|\n");
        }

        fclose($fp);
    }

    function _export_events($localoutputdir) {
        function sortplugins_callback($a, $b) {
            if (count($a['plugins']) == count($b['plugins'])) return 0;
            return (count($a['plugins']) > count($b['plugins'])) ? -1 : 1;
        }

        $resultFile = $localoutputdir.'events.txt';
        echo "<li>$resultFile</li>";

        $eventlist = $this->collections['eventlist'];
        $eventplugins = 0;
        $notaction = array();
        $urlmissing = array();
        foreach ($this->info as $name => $info) {
            if (!$info['plugin']) continue;
            foreach ($info['plugin'] as $plugin) {
                if (!$plugin['events']) continue;
                $eventplugins += 1;
                if ($plugin['type'] != 'action') {
                    $notaction[$event][] = pl($name);
                }
                foreach ($plugin['events'] as $event) {
                    $eventlist[$event]['plugins'][] = pl($name);
                    if (!$eventlist[$event]['url']) {
                        $urlmissing[$event][] = pl($name);
                    }
                }
            }
        }

        $fp = fopen($resultFile, 'w');
        fwrite($fp,"===== DokuWiki events =====\n");
        fwrite($fp,"And now for the complete list of [[devel:events]] found at time of [[start|survey]].\n");
        fwrite($fp,"Events are merged with the [[devel:events list]] showing 'unused' events.\n");
        fwrite($fp,"\n");
        fwrite($fp,"Disclaimer - this list was generated by a script, there may be plugins that are missing.\n");
        fwrite($fp,"\n");
        fwrite($fp,"^Event ^Plugins ^^\n");
        $eventsused = 0;
        foreach ($eventlist as $name => $event) {
            $eventlink = (($event['url']) ? "[[".$event['url']."|$name]]" : "%%$name%%");
            if ($event['plugins']) {
                $eventsused += 1;
                fwrite($fp,"|$eventlink |".count($event['plugins'])." |".implode(', ', $event['plugins'])."|\n");
            } else {
                fwrite($fp,"|$eventlink | | --- //not found in any examined plugin// -- |\n");
            }
        }

        fwrite($fp,"===== Plugin Survey ".date('Y')." - Events =====\n");
        fwrite($fp,"\nThe [[devel:events|event system]] allows custom handling in addition to or instead of the standard processing.\n");
        fwrite($fp,"In the analyzed code((xx of ".count($this->info)." plugins)) there are $eventplugins plugins using $eventsused different events.\n");
        fwrite($fp,"\n");
        fwrite($fp,"Here is the top 5 list:\n");

        uasort($eventlist, "sortplugins_callback");
        fwrite($fp,"\n^Event ^Plugins ^^\n");
        $i = 1;
        foreach ($eventlist as $name => $event) {
            if ($i++ > 5) break;
            if ($event['plugins']) {
                fwrite($fp,"|[[".$event['url']."|$name]] |".count($event['plugins'])." plugins |\n");
            }
        }
        fwrite($fp,"\nSee also the complete list of [[plugin_survey_events|events]] at time of survey (including unused).\n\n");
        fwrite($fp,"\n");
        fwrite($fp,"===== Non-action plugins =====\n");
        fwrite($fp,"Although mostly used by [[action plugins]] events can be included in any plugin or template script.\n");
        fwrite($fp,"These plugins may not be executed immediately or at all for any given page and execution pathway.\n");

        fwrite($fp,"\n^Event ^Plugins (other than Action) ^^\n");
        foreach ($notaction as $name => $plugins) {
            fwrite($fp,"|[[".$eventlist[$name]['url']."|$name]] |".count($plugins)." |".implode(', ', $plugins)."|\n");
        }
        fwrite($fp,"\n");
        fwrite($fp,"===== Plugin specific events =====\n");
        fwrite($fp,"^Plugins ^^\n");
        foreach ($urlmissing as $name => $plugins) {
            fwrite($fp,"|$name |".count($plugins)." |".implode(', ', $plugins)."|\n");
        }
        fclose($fp);
    }

    function _export_navigation($localoutputdir) {
        $resultFile = $localoutputdir.'navigation.txt';

        $unpopular = array();
        foreach ($this->info as $name => $info) {
            if ($this->popularity[$name] == 0) {
                $unpopular[] = pl($name);
            }
        }

        $fp = fopen($resultFile, 'w');
        fwrite($fp,"===== Navigation =====\n");
        fwrite($fp,"One plugin is removed by deleting the database header ".pl('definition_list').".\n");
        fwrite($fp,"One could probable be considered spam ".pl('newpagebutton')." although i have no access to the page history.\n");
        fwrite($fp,"One is not a plugin but a mod that should be in the tips namespace ".pl('hideindex').".\n");
//        fwrite($fp,count($unpopular) ."(". round($unpopular/count($this->info)*100) ."%) plugins with 0 points from popularity plugin.\n");
        fwrite($fp,"\n");
        fwrite($fp,count($unpopular)." plugins (". round(count($unpopular)/count($this->info)*100) ."%) have zero popularity.\n");
        fwrite($fp,"  * ".($unpopular?implode(', ', $unpopular):'')."\n");

        fclose($fp);
    }

    function _export_alphaindex($localoutputdir) {
        $resultFile = $localoutputdir.'alphaindex.txt';

        $alphaindex = array();
        foreach ($this->info as $name => $info) {
            if (!$info['maindownload'] && ($info['download'] || $info['download'])) {
                $alphaindex[substr($name,0,1)][] = pl($name);
            }
        }

        $fp = fopen($resultFile, 'w');
        fwrite($fp,"==== Alpha index ====\n");
        fwrite($fp,"Plugins missing download button\n");

        fwrite($fp,"^Plugins ^^^\n");
        foreach ($alphaindex as $name => $plugins) {
            fwrite($fp,"|".strtoupper($name)." |".count($plugins)." |".implode(', ', $plugins)."|\n");
        }
        fclose($fp);
    }

    function _export_trackedDevErrors($localoutputdir) {
        $resultFile = $localoutputdir.'trackedDevErrors.txt';
        echo "<li>$resultFile</li>";

        $fp = fopen($resultFile, 'w');
        fwrite($fp,"==== Tracked developers ====\n");

        fwrite($fp,"^Developer ^Error ^ Plugin^\n");
        $previousName = '';
        foreach ($this->collections['trackedDevErr'] as $name => $errors) {
            foreach ($errors as $err => $plugins) {
                if ($previousName != $name) {
                    fwrite($fp,"|$name  | $err  |".implode(', ', $plugins)."|\n");
                    $previousName = $name;
                } else {
                    fwrite($fp,"| :::  | $err  |".implode(', ', $plugins)."|\n");
                }
            }
        }
        fclose($fp);
    }

    function _export_tags($localoutputdir) {
        $resultFile = $localoutputdir.'tags.txt';
        echo "<li>$resultFile</li>";

        $tags = array();
        foreach ($this->info as $name => $info) {
            if (!$info['tags']) continue;
            foreach ($info['tags'] as $tag) {
                $tags[$tag]['plugins'][] = pl($name);
            }
        }

        uasort($tags, "sortplugins_callback");
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"==== Tags ====\n");
        fwrite($fp,"The plugins uses ".count($tags)." different tags.\n");

        fwrite($fp,"^Tag ^Plugins ^^\n");
        foreach ($tags as $name => $plugins) {
            fwrite($fp,"|[[plugintag>$name]] |".count($plugins['plugins'])." |\n");
//            fwrite($fp,"|[[plugintag>$name]] |".count($plugins['plugins'])." |".implode(', ', $plugins['plugins'])."|\n");
            if (count($plugins['plugins']) == 1) $singletag += 1;
        }
        fwrite($fp,"\n");
        fwrite($fp,"There are $singletag (".$singletag/count($tags)."%) tags with a single plugin.\n");
        fclose($fp);
    }

    function _export_references($localoutputdir) {
        $resultFile = $localoutputdir.'references.txt';

        $references = array();
        foreach ($this->info as $name => $info) {
            if (!$info['references']) continue;
            foreach ($info['references'] as $ref) {
                $references[$ref]['plugins'][] = pl($name);
            }
        }

        uasort($references, "sortplugins_callback");
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"===== References =====\n");

        fwrite($fp,"^References ^Plugins ^^\n");
        foreach ($references as $name => $plugins) {
            fwrite($fp,"|$name |".count($plugins['plugins'])." |".implode(', ', $plugins['plugins'])."|\n");
        }
        fclose($fp);
    }

    function _export_friendliness($localoutputdir) {
        $resultFile = $localoutputdir.'friendliness.txt';
        echo "<li>$resultFile</li>";

        $total = count($this->info);
        $langs = array();
        $pluginlangs = array();
        $missingimage_p = array();
        $nomodule = array();
        $renamemodule = array();
        $dirdiff = array();
        foreach ($this->info as $name => $info) {
            if ($info['lang'] && !$info['bundled']) {
				$pluginlangs[$name] = count($info['lang']);
				foreach ($info['lang'] as $lang) {
                    $langs[$lang] += 1;
                }
            }
            if ($info['security']) {
                $security += 1;
                $security_p[] = pl($name);
                $this->_checkDev($name, $info, "Active security warning");
            }
            if ($info['experimental']) {
                $experimental += 1;
                $experimental_p[] = pl($name);
                $this->_checkDev($name, $info, "Tagged !experimental");
            }
            if ($info['lang']) {
                $langplugin += 1;
            }
            if ($info['homepageimage'] == 0) {
                $missingimage_p[] = pl($name); 
            }
            if ($info['develonly']) {
                $devel_p[] = pl($name);
                $this->_checkDev($name, $info, "Develonly referred");
            }
            if ($info['pagesize'] > 100000) {
                $largepage_p[] = pl($name);
                $this->_checkDev($name, $info, "Need to cleanup plugin homepage");
            }
            if ($info['pagesize'] < 6000) {
                $smallpage += 1;
            }
            if ($info['license']) {
                $license += 1;
                $license_p[] = $info['license'];
            }
            if ($info['junk']) {
                $junk_p[] = pl($name);
                $this->_checkDev($name, $info, "Unrelated content in download");
            }
            if ($info['dirdiff']) {
                $dirdiff[$name] = $info['dirdiff'];
            }
            if ($info['plugin']) {
                foreach ($info['plugin'] as $class_name => $module) {
                    if (!$module['name']) continue;
                    $firstname = explode('_',$module['name'],2);
                    if ($name != strtolower($firstname[0])) {
                        $renamemodule[] = pl($name);
                        $this->_checkDev($name, $info, "Class name not same as plugin page");
                    }
                    break;
                }
            } elseif ($info['downloadexamined']) {
                $nomodule[] = pl($name);
            }
        }
        arsort($langs);
        arsort($pluginlangs);
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"====== Plugin Survey ".date('Y')." - Friendliness ======\n");
        fwrite($fp,"Counted from the first DokuWiki release in august 2004 there has been an average of ".$total/((2010-2004)*52)." new plugins every week! Today there is a wealth of plugins of different kinds.\n");
        fwrite($fp,"\n");
        fwrite($fp,"===== Plugin list =====\n");
        fwrite($fp,"\n");
        fwrite($fp,"==== Tags ====\n");
        fwrite($fp,"\n");
        fwrite($fp,"==== Excluded plugins ====\n");
        fwrite($fp,"$security plugins (". round($security/$total*100) ."%) are excluded from showing in [[plugins]] list for security reasons. All these have problems with XSS vulnerability allowing arbitrary JavaScript insertion. Authors are informed.\n");
        fwrite($fp,"  * ".($security_p?implode(', ', $security_p):'')."\n");
        fwrite($fp,"\n");

        fwrite($fp,"===== Homepage appearance =====\n");
        fwrite($fp,"\n");
        fwrite($fp,"==== Homepage size ====\n");
        fwrite($fp,"$smallpage plugin can be considered small. The following ".count($largepage_p)." are large.\n");
        fwrite($fp,"  * ".($largepage_p?implode(', ', $largepage_p):'')."\n");
        fwrite($fp,"\n");
        fwrite($fp,"==== Images ====\n");
        fwrite($fp,count($missingimage_p)." plugins (". round(count($missingimage_p)/$total*100) ."%) don't have an image shown the plugin in action.\n");
        fwrite($fp,"  * ".($missingimage_p?implode(', ', $missingimage_p):'')."\n");
        fwrite($fp,"\n");
        fwrite($fp,"==== Devel & experimental ====\n");
        fwrite($fp,count($devel_p)." mentions develonly.\n");
        fwrite($fp,"  * ".($devel_p?implode(', ', $devel_p):'')."\n");
        fwrite($fp,"$experimental plugins (". round($experimental/$total*100) ."%) are marked experimental.\n");
        fwrite($fp,"  * ".($experimental_p?implode(', ', $experimental_p):'')."\n");
        fwrite($fp,"\n");
        fwrite($fp,"===== Plugins =====\n");
        fwrite($fp,"\n");
        fwrite($fp,"==== Unrelated content ====\n");
        fwrite($fp,count($junk_p)." plugins with .svn etc.\n".($junk_p?implode(', ', $junk_p):'')."\n");
        fwrite($fp,"\n");
        fwrite($fp,"==== Correct name ====\n");
        fwrite($fp,"\n");
        fwrite($fp,count($renamemodule)." plugins with different pagename -> no populariy\n");
        fwrite($fp,"  * ".($renamemodule?implode(', ', $renamemodule):'')."\n");
        fwrite($fp,count($nomodule)." plugins without module\n");
        fwrite($fp,"  * ".($nomodule?implode(', ', $nomodule):'')."\n");
        fwrite($fp,"\n");
        fwrite($fp,"zip file made at wrong level, see lib dir in database plugin.\n");
        fwrite($fp,"\n");
        fwrite($fp,"Directory name doesn't match plugin page name.\n");
        fwrite($fp,"^Plugin page ^Class name ^\n");
        foreach ($dirdiff as $name => $plugin) {
            fwrite($fp,"|". pl($name) ." |$plugin |\n");
        }
        fwrite($fp,"\n");
        fwrite($fp,"===== Language =====\n");
        fwrite($fp,"Beside the bundled plugins (each 52 languages) the lang popularity is\n");
        fwrite($fp,"$langplugin (".($langplugin/$total*100)."%) plugins are using translations.\n");
        foreach ($langs as $lang => $value) {
            if ($value < 0) break;
            fwrite($fp,"|$lang |$value |". $this->manager->languageCodes[$lang] ." |\n");
        }
        fwrite($fp,"\n");
        fwrite($fp,"The five plugins beside the bundled with most translations are\n");
        foreach ($pluginlangs as $plg => $value) {
            if ($value < 5) break;
            fwrite($fp,"|".pl($plg)." |$value |\n");
        }
        fwrite($fp,"\n");
        fwrite($fp,"===== License =====\n");
        fwrite($fp,"$license ( ". round($license/$total*100) ."% ) is explicity refering one of the following licences: ".($license_p?implode(', ', array_unique($license_p)):'')."\n");
        fclose($fp);
    }

    function _export_developers($localoutputdir) {
        function sortdevplugins_callback($a, $b) {
            if (count($a['plugins']) == count($b['plugins'])) {
                if ($a['developer'] == $b['developer']) return 0;
                return ($a['developer'] > $b['developer']) ? 1 : -1;
            }
            return (count($a['plugins']) > count($b['plugins'])) ? -1 : 1;
        }

        function sortpopularity_callback($a, $b) {
            if ($a['popularity'] == $b['popularity']) {;
                if ($a['developer'] == $b['developer']) return 0;
                return ($a['developer'] > $b['developer']) ? 1 : -1;
            }
            return ($a['popularity'] > $b['popularity']) ? -1 : 1;
        }

        $resultFile = $localoutputdir.'developers.txt';
        echo "<li>$resultFile</li>";

        $newdeveloper = array();
        foreach ($this->info as $name => $plugin) {
            $devname = str_replace('&#039;','"',htmlspecialchars_decode($plugin['developer']));
            $developer[$devname]['developer'] = $devname;
            $newdeveloper[] = $devname;
            $developer[$devname]['plugins'][] = pl($name);
            $developer[$devname]['popularity'] += $plugin['popularity'];
        }
        unset($developer['']);

        $devcount = count($developer);
        $plugincount = count($this->info);
        uasort($developer, "sortdevplugins_callback");
        foreach ($developer as $name => $dev) {
            if (($i++ >= 10) || ($i >= $devcount)) break;
            $topcontrib += count($dev['plugins']);
        }
        $newdeveloper = array_diff(array_unique($newdeveloper), array_unique($this->collections['previousDevelopers']));
        
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"===== Plugin Survey ".date('Y')." - Developers =====\n");
        fwrite($fp,"[[start|survey]]\n");
        fwrite($fp,"\n\n--- **some text about all are important** ---\n\n");
        fwrite($fp,"The $plugincount plugins are made by $devcount different developers.\n");
        fwrite($fp,"That is about ".round($plugincount/$devcount,1)." plugins/developer and the top ten\n");
        fwrite($fp,"most productive developers has together $topcontrib plugins (".round($topcontrib/$plugincount*100)."%).\n");
        fwrite($fp,"\n");
        fwrite($fp,"We welcome these ".count($newdeveloper)." new  plugin authors.\n");
        fwrite($fp,"  * ".($newdeveloper?implode(', ', $newdeveloper):'')."\n");
        fwrite($fp,"\n");
        fwrite($fp,"===== By number of plugins =====\n");
        fwrite($fp,"\n");
        fwrite($fp,"^ ^Developer ^Plugins ^^\n");
        $i = 1;
        $place = 1;
        $previouscount = -1;
        foreach ($developer as $name => $dev) {
            if (count($dev['plugins']) < 2) break;
            if ($previouscount != count($dev['plugins'])) {
                $place = $i;
                $previouscount = count($dev['plugins']);
                if ($previouscount == 1) break;
                fwrite($fp,"|  $place.|$name |  ".count($dev['plugins']).'|'.implode(', ', $dev['plugins'])."|\n");
            } else {
                fwrite($fp,"| ::: |$name |  ".count($dev['plugins']).'|'.implode(', ', $dev['plugins'])."|\n");
            }
            $i++;
        }
        fwrite($fp,"\n");
        $i = 0;
        foreach ($developer as $name => $dev) {
            if (count($dev['plugins']) == 1) {
                $i++;
            }
        }
        fwrite($fp,"\n");
        fwrite($fp,"$i developers with only one plugin are not published here.\n");
        fwrite($fp,"\n");
        fwrite($fp,"===== By popularity =====\n");
        fwrite($fp,"Ranking could be done in many ways and some plugins are used by more installations than others.\n");
        fwrite($fp,"Thanks to the [[plugin:popularity]] plugin some usage data is available. Here are developers sorted by their plugins\n");
        fwrite($fp,"total popularity points. IMHO Andi should have some extra points for the bundled plugins that all have zero points in the plugin list.\n");

        uasort($developer, "sortpopularity_callback");
        fwrite($fp,"\n^ ^Developer ^Popularity  ^Plugins ^\n");
        $i = 1;
        $place = 1;
        $previouspop = -1;
        foreach ($developer as $name => $dev) {
            if ($dev['popularity'] < 2) {
                $lowpop++;
            } else {
                if ($previouspop != $dev['popularity']) {
                    $place = $i;
                    $previouspop = $dev['popularity'];
                //    if ($previouspop == 0) break;
                    fwrite($fp,"|  $place.|$name |  ".$dev['popularity'].' points|'.implode(', ', $dev['plugins'])."|\n");
                } else {
                    fwrite($fp,"| ::: |$name |  ".$dev['popularity'].' points|'.implode(', ', $dev['plugins'])."|\n");
                }
                $i++;
            }
        }
        fwrite($fp,"\n");
        fwrite($fp,"\n");
        fwrite($fp,"$lowpop developers with one or zero popularity points are not published. ");
        fwrite($fp,"The single point is assumed to be the developer themselves. One way to gain zero popularity is by having an [[http://www.freelists.org/post/dokuwiki/incorrect-plugin-page-names|incorrect page name]] for the plugin homepage.");
        fwrite($fp,"\n");
        fwrite($fp,"\n");
        fwrite($fp,"<sub>Return to <= [[start|Beginning of survey]]</sub>\n");
        fwrite($fp,"\n");
        fclose($fp);
    }

    function _export_csv($localoutputdir, $onlyplugin) {
        if ($onlyplugin) {
            $resultFile = $localoutputdir.'plugin_gardener.csv';
        } else {
            $resultFile = $localoutputdir.'pluginmodule_gardener.csv';
        }
        echo "<li>$resultFile</li>";
        $fp = fopen($resultFile, 'w');
        // header
        $plugin_info[] = 'plugin_name';
        $plugin_info[] = 'new';
        $plugin_info[] = 'notPlugin';
        $plugin_info[] = 'dirdiff';
        $plugin_info[] = 'developer';
        $plugin_info[] = 'pagemodified';
        $plugin_info[] = 'pagesize';
        $plugin_info[] = 'Gunning-Fog';
        $plugin_info[] = 'Flesh score';
        $plugin_info[] = 'Flesh grade';
        $plugin_info[] = 'homepageimage';
        $plugin_info[] = 'toc';
        $plugin_info[] = 'popularity';
        $plugin_info[] = 'lastmodified';
        $plugin_info[] = 'bundled';
        $plugin_info[] = 'security';
        $plugin_info[] = 'experimental';
        $plugin_info[] = 'junk';
        $plugin_info[] = 'compability';
        $plugin_info[] = 'code';
        $plugin_info[] = 'php_lines';
        $plugin_info[] = 'javascript';
        $plugin_info[] = 'java_lines';
        $plugin_info[] = 'java_lint';
        $plugin_info[] = 'css';
        $plugin_info[] = 'css_lines';
        $plugin_info[] = 'strftime';
        $plugin_info[] = 'dformat';
        $plugin_info[] = 'downloadexamined';
        $plugin_info[] = 'manualdownload';
        $plugin_info[] = 'time';
        $plugin_info[] = 'externalpage';
        $plugin_info[] = 'donate_button';
        $plugin_info[] = 'bugs_button';
        $plugin_info[] = 'repo_button';
        $plugin_info[] = 'download_button';
        $plugin_info[] = 'media_url';
        $plugin_info[] = 'download';
        $plugin_info[] = 'tags';
        $plugin_info[] = 'references';
        $plugin_info[] = 'links';
        $plugin_info[] = 'dokulinks';
        $plugin_info[] = 'license';
        $plugin_info[] = 'downloadfail';
        $plugin_info[] = 'conf';
        $plugin_info[] = 'images';
        $plugin_info[] = 'lang';
        $plugin_info[] = 'module_name';
        $plugin_info[] = 'module_type';
        $plugin_info[] = 'regexp';
        $plugin_info[] = 'event';
        fputcsv($fp, $plugin_info);

        // data
        foreach ($this->info as $name => $info) {
            $plugin_info = array();
            $plugin_info[] = $name;
            $plugin_info[] = (in_array($name, $this->collections['newPlugins']) ? 'new': '');
            $plugin_info[] = (in_array($name, $this->collections['notPlugins']) ? 'notPlugin': '');
            $plugin_info[] = $info['dirdiff'];
            $plugin_info[] = $info['developer'];
            $plugin_info[] = $info['pagemodified'];
            $plugin_info[] = $info['pagesize'];
            $plugin_info[] = $info['readbility_gf'];
            $plugin_info[] = $info['readbility_fs'];
            $plugin_info[] = $info['readbility_fg'];
            $plugin_info[] = $info['homepageimage'];
            $plugin_info[] = $info['toc'];
            $plugin_info[] = $info['popularity'];
            $plugin_info[] = $info['lastmodified'];
            $plugin_info[] = $info['bundled'];
            $plugin_info[] = $info['security'];
            $plugin_info[] = $info['experimental'];
            $plugin_info[] = $info['junk'];
            $plugin_info[] = $info['compability'];
            $plugin_info[] = $info['code'];
            $plugin_info[] = $info['php_lines'];
            $plugin_info[] = $info['javascript'];
            $plugin_info[] = $info['java_lines'];
            $plugin_info[] = $info['java_lint'];
            $plugin_info[] = $info['css'];
            $plugin_info[] = $info['css_lines'];
            $plugin_info[] = $info['strftime'];
            $plugin_info[] = $info['dformat'];
            $plugin_info[] = $info['downloadexamined'];
            $plugin_info[] = $info['manualdownload'];
            $plugin_info[] = $info['time'];
            $plugin_info[] = $info['externalpage'];
            $plugin_info[] = $info['donate'];
            $plugin_info[] = $info['bugs'];
            $plugin_info[] = $info['repo'];
            $plugin_info[] = $info['maindownload'];
            $plugin_info[] = $info['mediaurl'];
            $plugin_info[] = ($info['download'] ? implode(',', $info['download']): '');
            $plugin_info[] = ($info['tags'] ? implode(',', $info['tags']): '');
            $plugin_info[] = ($info['references'] ? implode(',', $info['references']): '');
            $plugin_info[] = count($info['links']);
            $plugin_info[] = ($info['dokulinks'] ? $info['dokulinks'][0]: '');
            $plugin_info[] = $info['license'];
            $plugin_info[] = $info['downloadfail'];
            $plugin_info[] = $info['conf'];
            $plugin_info[] = $info['images'];
            $plugin_info[] = count($info['lang']);

            if ($info['plugin'] && !$onlyplugin) {
                foreach ($info['plugin'] as $class_name => $module) {
                    $module_info = array();
                    $module_info[] = $module['name'];
                    $module_info[] = $module['type'];
                    $module_info[] = ''; // ($module['regexp'] ? implode(',', $module['regexp']): '');
                    $module_info[] = ($module['events'] ? implode(',', $module['events']): '');
                    fputcsv($fp, array_merge($plugin_info, $module_info));
                }
            } else {
                fputcsv($fp, $plugin_info);
            }
        }
        fclose($fp);
    }

}

