<?php

require_once(DOKU_PLUGIN.'/plugingardener/classes/pg_stats.class.php');

class pg_reportwriter extends pg_gardener {

    function execute() {
        echo "<h4>ReportWriter</h4>\n";
        echo "<ul>";
        $localoutputdir = $this->cfg['localdir'].'output/';
        if (!file_exists($localoutputdir)) {
            mkdir($localoutputdir);
        }

        // create csv files with ALL information
        $this->_export_csv($localoutputdir,false);
        $this->_export_csv($localoutputdir,true);
        
        // unlink 'not' plugins
        foreach ($this->collections['notPlugins'] as $notplugin) {
            unset($this->info[$notplugin]);
        }
        
        $s = new pg_stats($this->info, $this->collections);

        $this->_export_summary($localoutputdir, $s);
        $this->_export_deployment($localoutputdir, $s);
        $this->_export_compatibility($localoutputdir, $s);
        $this->_export_codestyle($localoutputdir, $s);
        $this->_export_events($localoutputdir, $s);
        $this->_export_friendliness($localoutputdir, $s);
        $this->_export_developers($localoutputdir, $s);
        $this->_export_trackedDevErrors($localoutputdir, $s);
        $this->_export_teamtodolist($localoutputdir, $s);

        $this->_export_references($localoutputdir, $s);

        echo "</ul>";
        return true;
    }


    function _export_summary($localoutputdir, $s) {

        $resultFile = $localoutputdir.'summary.txt';
        echo "<li>$resultFile</li>";
        /*
         * collections['plugins']   contains all pages in plugin namespace as taken from index page 
         * $total                   number of entries in $this->info[] AFTER 'not plugins' has been unlinked
         *                          The csv export does include the 'not plugins' but the text reports doesn't 
         */
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"====== Plugin Survey ".date('Y')." ======\n");
        fwrite($fp,"This is a survey of all [[:plugins]] present in the plugin namespace of %%www.dokuwiki.org%%.\n");
        fwrite($fp,"Data was collected by an automated script 2011-11-23 with some additional manual data download and mining.\n");
        fwrite($fp,"A total of ".count($this->collections['plugins'])." plugin pages containing ".$s->total." plugins was examined and source code for ");
        fwrite($fp,$s->cnt('$info["downloadexamined"] == "yes"')." downloaded and parsed.\n");
        fwrite($fp,"\n");
        fwrite($fp,($s->total - $this->cfg['previousYearTotal'])." new plugins has been released since previous survey in september 2010 giving a groowth figure of ");
        fwrite($fp,round($s->total/$this->cfg['previousYearTotal']*100-100) ."% a year.\n");
        fwrite($fp,"\n");
        fwrite($fp,"^Year ^Plugins ^\n");
        fwrite($fp,"| 2009 |  539|\n");
        fwrite($fp,"| 2010 |  672|\n");
        fwrite($fp,"| 2011 |  $s->total|\n");
        fwrite($fp,"\n");
        fwrite($fp,$s->cnt('$info["new"]', 'The %s new plugins')."\n");
        fwrite($fp,$s->plugins('$info["new"]'));
        fwrite($fp,"\n");
        fwrite($fp,"===== Not every plugin is a plugin =====\n");
        fwrite($fp,"\n");
        fwrite($fp,"What about the ".count($this->collections['notPlugins'])." other pages?\n");
        fwrite($fp,"\n");
        foreach ($this->collections['notPlugins'] as $name) {
			fwrite($fp,"  * ".$s->wiki_link($name)." -- text\n");
		}
        fwrite($fp,"\n");
        fclose($fp);
    }

    function _export_deployment($localoutputdir, $s) {

        $resultFile = $localoutputdir.'deployment.txt';
        echo "<li>$resultFile</li>";
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"====== Plugin Survey ".date('Y')." - Deployment ======\n");
        fwrite($fp,"\n");
        fwrite($fp,"===== Bundled =====\n");
        fwrite($fp,$s->cnt('$info["bundled"]')." are marked [[plugintag>!bundled]] (i.e. included in the release).\n");
        fwrite($fp,$s->plugins('$info["bundled"]'));
        fwrite($fp,"\n");

        fwrite($fp,"===== Download package =====\n");
        fwrite($fp,"A convenient way of installing is by giving a link to the [[plugin:plugin|plugin manager]].\n");
        fwrite($fp,$s->cnt('$info["download"]', '%s pages %s')." have some sort of download link.\n");
        fwrite($fp,"In some cases, saved downloads from last years surveys could be used for code analysis.\n");
        $downloadpackage = $s->count('$info["downloadexamined"] == "yes" && $info["downloadstyle"] != "Code Block"');
        fwrite($fp,"In total $downloadpackage downloaded packages were analyzed.\n");
        fwrite($fp,"There were ".$s->cnt('$info["downloadfail"]',null,'Broken download')." broken download links at the time of survey.\n");
        fwrite($fp,$s->plugins('$info["downloadfail"]'));
        fwrite($fp,"\n");

        fwrite($fp,"=== Repository Buttons ===\n");
        fwrite($fp,"Late 2009 the [[plugin:repository|repository plugin]] introduced the possibility to add links to source code, bug tracker and a donate button. Since then developers and the [[teams:plugins_templates|plugin & templates team]] has spread their use.");
        fwrite($fp,"^Button ^ Plugins 2010 ^ Plugins 2011 ^\n");
        fwrite($fp,"|Download |  213 (%)|   ".$s->cnt('$info["downloadbutton"]','%s %s')."|\n");
        fwrite($fp,"|Bugs     |  150 (%)|   ".$s->cnt('$info["bugsbutton"]','%s %s')."|\n");
        fwrite($fp,"|Repo     |  138 (%)|   ".$s->cnt('$info["repobutton"]','%s %s')."|\n");
        fwrite($fp,"|Donate   |   85 (%)|   ".$s->cnt('$info["donatebutton"]','%s %s')."|\n");
        fwrite($fp,"\n");
        $s->count('!$info["downloadbutton"] && !$info["bundled"]','Missing download button');

        fwrite($fp,"=== Public repositories ===\n");
        fwrite($fp,"....\n");
        fwrite($fp,"But there are good alternatives as well. There is a clear trend for a common repository.\n");
        fwrite($fp,"Previous year 126 plugins where avaliable at [[http://www.github.com|GitHub]]. ");
        $github = $s->count('preg_match("/github/i",$info["download"][0])');
        fwrite($fp,"Now ".$s->cnt('preg_match("/github/i",$info["download"][0])')." are located on GitHub.\n");
        fwrite($fp,"This accounts for ". round($github/$s->count('$info["download"]')*100) ."% of the plugins with download link.\n");
        fwrite($fp,"\n");
        fwrite($fp,"^Year ^ Plugins@GitHub ^\n");
        fwrite($fp,"|  2009  |  22  |\n");
        fwrite($fp,"|  2010  |  126  |\n");
        fwrite($fp,"|  2011  |  ".$github."  |\n");
        fwrite($fp,"\n");
		
        fwrite($fp,"=== External sites ===\n");
        fwrite($fp,$s->cnt('$info["externalpage"] == "yes"')." have their \"Details and download\" page somewhere outside www.dokuwiki.org ");
        fwrite($fp,"and ".$s->cnt('$info["externalpage"] == "broken"',null,'Broken external link')." were broken at the time of survey. Here is a list of the broken links:\n");
        fwrite($fp,$s->plugins('$info["externalpage"] == "broken"'));
        fwrite($fp,"\n");

        fwrite($fp,"=== BZ2 compression ===\n");
        fwrite($fp,$s->cnt('preg_match("/bz2/i",$info["download"][0])','Uses BZ2 compression')." with first (main) download as bz2.\n");
        fwrite($fp,$s->plugins('preg_match("/bz2/i",$info["download"][0])'));
        fwrite($fp,"\n");

        fwrite($fp,"=== plugin.info.txt ===\n");
        fwrite($fp,"Plugins are no longer required to implement a getInfo() function used by the [[plugin:plugin|plugin manager]].\n");
        fwrite($fp,"Since DokuWiki 2009-12-25 release they may instead add an ''plugin.info.txt'' file. This adds better protection against download packages with faulty folder names.\n");
        fwrite($fp,"Now ".round($s->count('$info["plugininfotxt"]')/$downloadpackage*100)."% of the plugins with download packages includes this file.\n");
        fwrite($fp,"\n");
        fwrite($fp,"^ Survey ^ Plugins ^\n");
        fwrite($fp,"|  2010  |  94  |\n");
        fwrite($fp,"|  2011  |  ".$s->count('$info["plugininfotxt"]')."  |\n");
        fwrite($fp,"\n");
        $s->count('!$info["plugininfotxt"]','Doesn\'t use info.txt');

        fwrite($fp,"=== Bad folder structure ===\n");
        // TODO -- Bad folder structure
        fwrite($fp,"FIXME\n");
        fwrite($fp,"\n");

        fwrite($fp,"===== Without plugin package =====\n");
        fwrite($fp,"Another way of installing plugins are by cut-n-paste code snippets from the plugin homepage following the [[:plugin installation instructions]].\n");
        fwrite($fp,"There are ".$s->cnt('$info["code"] && !$info["download"]')." that only offer manual installation.\n");
        fwrite($fp,$s->cnt('$info["code"] && $info["download"]')." has at least one php %%<code>%% section besides a download link.\n");
        fwrite($fp,"New plugins since last survey without download package: \n");
        fwrite($fp,$s->plugins('!$info["download"] && $info["new"] && !$info["bundled"]'));
        fwrite($fp,"\n");
        fwrite($fp,$s->count('!$info["download"] && $info["popularity"] > 200'). " popular((more than 200 installations)) plugins are missing a download package.\n");
        fwrite($fp,$s->plugins('!$info["download"] && $info["popularity"] > 200'));
        fwrite($fp,"\n");
        fclose($fp);
    }

    function _export_compatibility($localoutputdir, $s) {

        $lastyear = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y")-1));
        $releases = array('2011-11-10', '2011-05-25', '2010-11-07', '2009-12-25', '2009-02-14', '2008-05-05', '2007-06-26', '2006-11-06');

        $resultFile = $localoutputdir.'compatibility.txt';
        echo "<li>$resultFile</li>";
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"====== Plugin Survey ".date('Y')." - Compatibility ======\n");
        fwrite($fp,"\n");
        fwrite($fp,$s->cnt('$info["pagemodified"] > "'.$lastyear.'"','%s homepages %s')." are modified within last 12 months.\n");
        fwrite($fp,"\n");
        fwrite($fp,$s->cnt('$info["pagemodified"] > "'.$releases[0].'"','%s homepages %s')." are modified after latest release ".$releases[0].".\n");
        fwrite($fp,"\n");
        fwrite($fp,$s->cnt('$info["pagemodified"] > "'.$releases[1].'"','%s homepages %s')." are modified after the release before that.\n");
        fwrite($fp,"\n");
        fwrite($fp,$s->cnt('$info["lastupdate"] > "'.$releases[0].'"')." are updated after latest release.\n");
        fwrite($fp,"\n");
        fwrite($fp,$s->cnt('$info["lastupdate"] > "'.$releases[1].'"')." are updated after the release before that.\n");
        fwrite($fp,"\n");
        fwrite($fp,$s->cnt('$info["bestcompat"] >= "'.$releases[0].'"')." explicitly states compatibility with latest release ".$releases[0].".\n");
        fwrite($fp,"\n");
        fwrite($fp,$s->cnt('$info["bestcompat"] >= "'.$releases[3].'"')." explicitly states compatibility with at least one of the 4 recent releases (".$releases[3]." or later).\n");
        fwrite($fp,"\n");
        fwrite($fp,$s->cnt('!$info["bestcompat"]',null,'No, old or unclear compatibility')." have no, old or unclear compatibility stated.\n");
        fwrite($fp,"\n");
        fwrite($fp,"^Release ^ Plugins ^\n");
        fwrite($fp,$s->pivot('$info["repo"]["compatible"]["release"]',true,false,true));
        fwrite($fp,"\n");
        fwrite($fp,"^ Release ^ Plugins Bestcompat ^\n");
        fwrite($fp,$s->pivot('$info["bestcompat"]',true,false,true));
        fwrite($fp,"\n");

        fwrite($fp,"=== Pagemodified chart ===\n");
        fwrite($fp,"\n");
        fwrite($fp,"^ Modified date  ^ Plugins  ^\n");
        fwrite($fp,$s->pivot('substr($info["pagemodified"],0,-3)'));
        fwrite($fp,"\n");

        fwrite($fp,"=== Lastupdate chart ===\n");
        fwrite($fp,"\n");
        fwrite($fp,"^ Last update date  ^ Plugins  ^\n");
        fwrite($fp,$s->pivot('substr($info["lastupdate"],0,-3)'));
        fwrite($fp,"\n");

        fwrite($fp,"===== Tagged broken =====\n");
        $broken_arg = '$info["tags"] && (in_array("!broken", $info["tags"]) || in_array("!maybe.broken", $info["tags"]))';
        fwrite($fp,$s->cnt($broken_arg,null,'Tagged !broken')." are tagged \"!broken\" or \"!maybe.broken\" which is an obvious way of stating incompatibility with the current DokuWiki version.\n");
        fwrite($fp,$s->plugins($broken_arg));
        fwrite($fp,"\n");

        fwrite($fp,"===== Dependencies =====\n");
        fwrite($fp,$s->pivot('$info["depends"]',false,false,false,null,true));
        fwrite($fp,"\n");

        fwrite($fp,"===== Marked conflicting =====\n");
        fwrite($fp,"\n");
        $conflictgrp = array();
        foreach ($this->info as $name => $info) {
            if ($info['conflicts']) {
                $found = false;
                foreach ($conflictgrp as &$grp) {
                    if (array_intersect($grp, $info['conflicts'])) {
                        // already in any grp, then add all to that
                        $grp = array_unique(array_merge($grp, $info['conflicts'], array($name)));
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    // otherwise add all to new group
                    $conflictgrp[] = array_merge($info['conflicts'], array($name));
                }
            }
        }
        uasort($conflictgrp, create_function('$a,$b' , 'return (count($b)-count($a));'));
        fwrite($fp,$s->cnt('$info["conflicts"]',null,'Marked as conflicting')." (**+28** since last year) state conflict with one or more other plugins divided into ".count($conflictgrp)." separate \"conflict groups\". \n");
        fwrite($fp,"Since 2009 the number has more than doubled, reason for this is unclear. One reason might be the large total increase of plugins, another might be that functionality in older plugins are enhanced by releasing a new similar plugin instead of improving the old one. \n");
        fwrite($fp,"Here is the list of conflict groups after the more obvious, like [[plugin:addnewpage]] v.s. [[plugin:addnewpage_old]], are removed.\n");
        fwrite($fp,"\n");
        foreach ($conflictgrp as $conflicts) {
            $plugins = array_map('pg_stats::wiki_link', $conflicts);
            fwrite($fp,"  * ".implode(' v.s. ', $plugins)."\n");
        }
        fwrite($fp,"\n");

        fwrite($fp,"Conflict pivot\n");
        fwrite($fp,$s->pivot('$info["conflicts"]',false,true,true,null,true));
        fwrite($fp,"\n");

        fwrite($fp,"===== Shared class names ======\n");
        fwrite($fp,"Sharing class names with another plugin might cause problems. PHP don't allow a class being declared more than once\n");
        fwrite($fp,"\n");

        foreach ($this->info as $name => $info) {
            if (!$info['plugin']) continue;
            foreach ($info['plugin'] as $module) {
                if ($module['name'] && strpos($module['name'], '_') === false) {
                    if (strcasecmp($module['name'], $name) != 0) {
                        $nameconflict[$module['name']][] = $name;
                    }
                }
            }
        }
        fwrite($fp,"^Original (gets popularity) ^New version (no popularity) ^\n");
        foreach ($nameconflict as $name => $plugins) {
            $plugins = array_unique($plugins);
            if ($this->info[$name]) {
                fwrite($fp,'|'.$s->wiki_link($name).' |'.$s->plugins_from_array($plugins, true)."|\n");
            }
        }
        fwrite($fp,"\n");

        fwrite($fp,"===== Syntax =====\n");
        /*
         * Collect and analyze regexp's for syntax plugins
         */
        $regfind = array('"', "'",'\\x3c', '\\x3e', '\\');
        $regrepl = array('' , '' ,'<' ,    '>', '');
        $syntax_special = array();
        $syntax_entryexit = array();
        $syntax_gready = array();
        $syntax_wing = array();
        $syntax_gull = array();
        $syntax_xml = array();
        $syntax_nolookahead = array();
        foreach ($this->info as $name => $info) {
            if (!$info['plugin']) continue;
            foreach ($info['plugin'] as $module) {
                if ($module['regexp_special']) {
                    foreach ($module['regexp_special'] as $reginfo) {
                        $sort = str_replace($regfind,$regrepl, strtolower($reginfo));
                        if (preg_match('/[^=]\.\*[^?]/',$reginfo)) $syntax_gready[] = array($name, $reginfo);
                        $syntax_special[] = array("%%$sort%%", "%%$reginfo%%", $name);
                        if (preg_match('/\\\\?{\\\\?{/',$reginfo)) $syntax_wing[] = $name;
                        if (preg_match('/\\\\?~\\\\?~/',$reginfo)) $syntax_gull[] = $name;
                        if (preg_match('/["\']?</',$reginfo)) $syntax_xml[] = $name;
                    }
                }
                if ($module['regexp_entry']) {
                    foreach ($module['regexp_entry'] as $reginfo) {
                        $sort = str_replace($regfind,$regrepl, strtolower($reginfo));
                        if (preg_match('/[^=]\.\*[^?]/',$reginfo)) $syntax_gready[] = array($name, $reginfo);
                        if (preg_match('/\(\?=/',$reginfo)) {
                            $syntax_lookout = '';
                        } else {
                            $syntax_lookout = ':-\ ';
                            $syntax_nolookahead[] = $name;
                        }
                        if (preg_match('/["\']?</',$reginfo)) $syntax_xml[] = $name;
                        if ($module['regexp_exit']) {
                            $syntax_entryexit[] = array("%%$sort%%", "%%$reginfo%%",$name,'%%'.$module['regexp_exit'][0].'%%', $syntax_lookout); // TODO !!!!
                        } else {
                            $syntax_entryexit[] = array("%%$sort%%", "%%$reginfo%%",$name,'', $syntax_lookout);
                        }
                    }
                }
            }
        }
        $sortfunc = create_function('$a,$b','return strcmp($a[0],$b[0]);');
        uasort($syntax_special, $sortfunc);
        uasort($syntax_entryexit, $sortfunc);
        $syntax_wing = array_unique($syntax_wing);
        $syntax_gull = array_unique($syntax_gull);
        $syntax_xml = array_unique($syntax_xml);
        $syntax_nolookahead = array_unique($syntax_nolookahead);
        /*
         * END::Collect and analyze regexp's for syntax plugins
         */
        fwrite($fp,"Are the wiki syntax used by [[devel:syntax_plugins]] compatible? The ".$s->count('$info["downloadexamined"] == "yes"')." downloaded plugins have been scanned for\n");
        fwrite($fp,"  * addEntryPattern()\n");
        fwrite($fp,"  * addExitPattern()\n");
        fwrite($fp,"  * addSpecialPattern()\n");
        fwrite($fp,"Although there are no rules for syntax [[devel:syntax_plugins|mentioned]] some \"de facto\" standard has evolved. In this survey it looks like a majority of special patterns are either %%{{%%...%%}}%% (".count($syntax_wing)." cases) ");
        fwrite($fp,"or %%~~%%...%%~~%% (".count($syntax_gull)." cases). A very common entry/exit pattern (".count($syntax_xml)." plugins) is something like an XML tag even if some use upper case letters.\n");
        fwrite($fp,"\n");

        fwrite($fp,"Automatic checking of regex strings is beyond SurveyBot's abilities, it even fails resolving static variables. But some observations can be made reading the list. Here are a couple of cases of greedy regex'es\n");
        fwrite($fp,"^Plugin ^Regex ^\n");
        foreach ($syntax_gready as $data) {
            fwrite($fp,"|".$s->wiki_link($data[0])." |%%$data[1]%% |\n");
        }
        fwrite($fp,"\n");

        fwrite($fp,"A lot of plugins still have the workaround with %%\x3C%% instead of '<' for early versions of the DokuWiki lexer lookahead bug described in [[devel:syntax_plugins#patterns]]. \n");
        fwrite($fp,count($syntax_nolookahead)." plugins do not feature a lookahead pattern.\n");
        fwrite($fp,$s->plugins_from_array($syntax_nolookahead));
        fwrite($fp,"\n");

        fwrite($fp,"===== Replacement Renderers =====\n");
        fwrite($fp,"[[devel:renderer_plugins#replacement_default_renderer|Replacement renderers]] are inherently conflicting with each other because only one can be [[config:renderer_xhtml|selected]] at a time.\n");
        fwrite($fp,"There are ".$s->count('$info["canRender"]')." replacement renderers.\n");
        fwrite($fp,$s->plugins('$info["canRender"]'));
        fwrite($fp,"\n");
        fclose($fp);

        /*
         *  syntax page output here while gathering syntax info is expensive
         */
        $resultFile = $localoutputdir.'syntax.txt';
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"====== Plugin Survey ".date('Y')." - Syntax ======\n");
        fwrite($fp,"<sup>(This is a part of the [[start|plugin survey ".date('Y')."]])</sup>\n");
        fwrite($fp,"\n");
        fwrite($fp,$s->count('$info["downloadexamined"] == "yes"')." plugins, downloaded during the survey, has been scanned for\n");
        fwrite($fp,"  * addSpecialPattern() - found ".count($syntax_special)." patterns.\n");
        fwrite($fp,"  * addEntryPattern() and addExitPattern() - found ".count($syntax_entryexit)." patterns.\n");
        fwrite($fp,"This is the result, read more about [[compatibility#syntax|syntax compatibility]].\n");
        fwrite($fp,"\n");

        fwrite($fp,"===== Special patterns =====\n");
        fwrite($fp,"^Special Pattern ^Plugin ^ \n");
        foreach ($syntax_special as $reginfo) {
            fwrite($fp,"|$reginfo[1] |".$s->wiki_link($reginfo[2])." |\n");
        }
        fwrite($fp,"\n");
        fwrite($fp,"===== Entry/exit patterns =====\n");
        fwrite($fp,"\n");
        fwrite($fp,"^Entry Pattern ^Exit Pattern ^Plugin ^Look ahead ^ \n");
        foreach ($syntax_entryexit as $reginfo) {
            fwrite($fp,"|$reginfo[1] |$reginfo[3]  |".$s->wiki_link($reginfo[2])." |  $reginfo[4]  |\n");
        }
        fwrite($fp,"\n");
        fwrite($fp,"<sub>Back to <= [[Compatibility#syntax|Compatibility]]</sub>\n");
        fclose($fp);
    }

    function _export_codestyle($localoutputdir, $s) {

        // !!! changing "total" to make percentage against number of examined !!!
        $downloadexamined = $s->count('$info["downloadexamined"] == "yes"');
        $s->total = $downloadexamined;

        $resultFile = $localoutputdir.'codestyle.txt';
        echo "<li>$resultFile</li>";
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"====== Plugin Survey ".date('Y')." - Source Code ======\n");
        fwrite($fp,"The $downloadexamined downloaded plugins could be analysed and commented in a number of ways, here are some ;-).\n");
        fwrite($fp,"Metrics was done by [[http://cloc.sourceforge.net/|CLOC]] and comparisions are with DokuWiki 2009-02-14b (bundled plugins included).\n");
        fwrite($fp,"\n");

        fwrite($fp,"===== PHP =====\n");
        fwrite($fp,"Downloaded plugins contain a total of ".$s->sum('$info["php_lines"]')." lines of PHP, compared to DokuWiki's approx. 114,000 (+19,000 since last survey) lines of code. \n");
        fwrite($fp,"Smallest plugin **wysiwyg_nicedit** is ".$s->min('$info["php_lines"]')." lines and only contains info for plugin manager, actual plugin in JavaScript. \n");
        fwrite($fp,"The median plugin is a syntax plugin with ".$s->median('$info["php_lines"]')." lines of PHP. Finally there are ".$s->count('$info["php_lines"] > 2000')." plugins with more than 2,000 lines of code, \n");
        fwrite($fp,"largest is **dw2pdf** with ".$s->max('$info["php_lines"]')." lines of code.\n");
        fwrite($fp,"\n");
        fwrite($fp,"Five smallest\n");
        fwrite($fp,$s->min('$info["php_lines"]', 5));
        fwrite($fp,"\n");
        fwrite($fp,"Five largest\n");
        fwrite($fp,$s->max('$info["php_lines"]', 5));
        fwrite($fp,"\n");

        fwrite($fp,"=== Config ===\n");
        fwrite($fp,"DokuWiki framework enable plugins or templates to be [[devel:configuration|configurable]] by the [[plugin:config|configuration manager]] which will handle/display the options. Plugins should provide\n");
        fwrite($fp,"  * ''<plugin>/conf/default.php'' which will hold the default settings and\n"); 
        fwrite($fp,"  * ''<plugin>/conf/metadata.php'' which holds the describing [[devel:configuration#configuration metadata]].\n");
        fwrite($fp,"During the survey ".$s->cnt('$info["conf"]')." were found compatible with the configuration manager. ");
        fwrite($fp,$s->count('$info["conf"] == "nometa"')." plugins are missing the metadata file, unknown whether this is intentional or not.\n");
        fwrite($fp,$s->plugins('$info["conf"] == "nometa"'));
        fwrite($fp,"\n");

        fwrite($fp,"=== PHP5 ===\n");
        fwrite($fp,"Since the 2009-12-25c \"Lemming\" release DokuWiki requires PHP version 5.1.2 which supports object oriented concepts like visibility (public, private, inherit) etc. \n");
        fwrite($fp,"Technically plugins now could be written in this style but only ".$s->cnt('$info["php5"]')." is found by looking for 'private function'.\n");
        fwrite($fp,"But in the population of new plugins there are more than ".round($s->count('$info["php5"] && $info["new"]')/max(1,$s->count('$info["new"]'))*100)."% written in PHP 5.\n");
        fwrite($fp,"\n");

        fwrite($fp,"=== Saving data on action SAVE ===\n");
        fwrite($fp,"DokuWiki release 2010-11-07a \"Anteater\" changed the behavior on page save. Functions handle() and render() are in most cases no longer called during save.\n");
        fwrite($fp,"The change means that relying on ''\$ACT == 'save''' doesn't work anymore. ".$s->count('$info["save_on_act"]')." plugins are still found using this technique.\n");
        fwrite($fp,$s->plugins('$info["save_on_act"]'));
        fwrite($fp,"\n");

        fwrite($fp,"===== Toolbar =====\n");
        fwrite($fp,"The [[::toolbar]] makes DokuWiki easy to use even for novice users. Sometimes plugins need to extend the toolbar with another button or interact in other ways. \n");
        fwrite($fp,"There are more than one way to achieve this, 40 plugins are [[devel:toolbar#using PHP]] and the [[devel:event:toolbar_define|TOOLBAR DEFINE]] event to add toolbar buttons. \n");
        fwrite($fp,"See the [[events#TOOLBAR_DEFINE|survey event list]] for a detailed list. \n");
        fwrite($fp,"\n");
        fwrite($fp,"Adding a button [[devel:toolbar#using JavaScript]] is just as simple as doing it in PHP, 21 plugins interacts with the toolbar array this way. ");
        $syntaxplugins = $s->count('$info["t_syntax"]');
        fwrite($fp,"Together with those using PHP event they represent x% of the $syntaxplugins syntax plugins.\n");
        fwrite($fp,"\n");
        fwrite($fp,$s->cnt('$info["javatoolbar"] == "yes"')." are using the static data method accessing ''toolbar[…]''\n");
        fwrite($fp,$s->plugins('$info["javatoolbar"] == "yes"'));
        fwrite($fp,"\n");
        fwrite($fp,$s->cnt('$info["javatoolbar"] == "dynamic"')." are using dynamic method based on ''getElementById('tool%%__%%bar')'' or ''jQuery('tool%%__%%bar')''\n");
        fwrite($fp,$s->plugins('$info["javatoolbar"] == "dynamic"'));
        fwrite($fp,"\n");

        fwrite($fp,"===== JavaScript =====\n");
        fwrite($fp,"Almost ".$s->cnt('$info["javascript"]')." of the downloaded plugins use [[devel:javascript]] to enhance the user experience.\n");
        fwrite($fp,"There is a total of ".$s->sum('$info["java_lines"]')." lines of JavaScript compared to DokuWiki's approx. 2,500 lines.\n");
        fwrite($fp,"Five FCK editors ([[plugin:fckw]], [[plugin:grensladawritezor]], [[plugin:wysiwyg]], [[plugin:fckg]], [[plugin:fckglite]]) contain about 30,000+ rows each and ");
        fwrite($fp,$s->count('$info["java_lines"] > 4000')." other are 4,000+ lines of code but the median is only ".$s->median('$info["java_lines"]')." lines of code. A few scripts are also compressed to single line without whitespace.\n");
        fwrite($fp,"\n");
        fwrite($fp,"Five smallest\n");
        fwrite($fp,$s->min('$info["java_lines"]', 5));
        fwrite($fp,"\n");
        fwrite($fp,"Twenty largest\n");
        fwrite($fp,$s->max('$info["java_lines"]', 20));
        fwrite($fp,"\n");

        fwrite($fp,"DokuWiki handles plugins without any PHP code but the plugin manager is not able to display information about the plugin.\n");
        fwrite($fp,$s->plugins('$info["downloadexamined"] == "yes" && $info["javascript"] && !$info["php_lines"]'));
        fwrite($fp,"do not have an accompanying PHP plugin class\n");
        fwrite($fp,"\n");

        fwrite($fp,"=== JavaScript include ===\n");
        fwrite($fp,"DokuWiki's JavaScript dispatcher allows you to use special JavaScript comments to include other script files. This is useful for cases where usually only a single JavaScript file would be parsed, e.g. in templates or plugins.\n");
        fwrite($fp,"Following ".$s->count('$info["javainclude"]')." plugins uses [[devel:javascript#include_syntax|DokuWiki JavaScript include]].\n");
        fwrite($fp,$s->plugins('$info["javainclude"]'));
        fwrite($fp,"\n");

        fwrite($fp,"=== jQuery ===\n");
        fwrite($fp,"The [[http://www.jquery.com/|jQuery]] library was introduced with DokuWiki release 2011-11-10 \"Angua\". This enables plugin and template developers to more stuff with fewer lines of Java. \n");
        fwrite($fp,"jQuery code is found in ".$s->count('$info["jquery"]')." plugins.\n");
        fwrite($fp,$s->plugins('$info["jquery"]'));
        fwrite($fp,"\n");

        fwrite($fp,"=== AJAX ===\n");
        fwrite($fp,"By using the event [[devel:event:ajax_call_unknown|AJAX_CALL_UNKNOWN]] that is signalled from [[xref>lib/exe/ajax.php]] if the AJAX call is not recognized [[devel:action_plugins]] can create interactive web applications. SurveyBot tried three ways to identify plugins using AJAX.\n");
        fwrite($fp,"\n");
        $ajaxevent = '$info["events"] && in_array("AJAX_CALL_UNKNOWN",$info["events"])';
        $a_event = array_keys($s->infos($ajaxevent));
        fwrite($fp,$s->cnt($ajaxevent)." contains a [[devel:event:ajax_call_unknown|AJAX_CALL_UNKNOWN]] ''register_hook()''\n");
        fwrite($fp,$s->plugins($ajaxevent));
        fwrite($fp,"\n");

        $runajax = '$info["java_ajax"]';
        $a_run = array_diff(array_keys($s->infos($runajax)), $a_event);
        fwrite($fp,"An additional ".count($a_run)." plugins are found by looking in JavaScript for 'runAJAX'.\n");
        fwrite($fp,$s->plugins_from_array($a_run));
        fwrite($fp,"\n");

        $ajaxtag = '$info["tags"] && in_array("ajax",$info["tags"])';
        $a_tag = array_diff(array_keys($s->infos($ajaxtag)), $a_run, $a_event);
        fwrite($fp,"At last ".count($a_tag)." more plugins are [[plugintag>axaj|tagged ajax]]\n");
        fwrite($fp,$s->plugins_from_array($a_tag));
        fwrite($fp,"\n");

        fwrite($fp,"===== CSS =====\n");
        fwrite($fp,"Presentation can be controlled by [[devel:CSS]] stylesheets. The downloaded plugins contain a total of ".$s->sum('$info["css_lines"]'));
        fwrite($fp," lines of CSS divided among ".$s->cnt('$info["css_lines"]')." (i.e. xx% uses CSS). \n");
        fwrite($fp,"**syntaxhighlighter3** has one of the largest files with ".$s->max('$info["css_lines"]')." lines but the median is only ".$s->median('$info["css_lines"]')." lines of CSS. \n");
        fwrite($fp,"There are ".$s->count('$info["css_lines"] > 500')." plugins with more than 500 lines of CSS: \n");
        fwrite($fp,$s->plugins('$info["css_lines"] > 500'));
        fwrite($fp,"\n");

        fwrite($fp,"\n");
        fwrite($fp,"Five smallest\n");
        fwrite($fp,$s->min('$info["css_lines"]', 5));
        fwrite($fp,"\n");
        fwrite($fp,"Five largest\n");
        fwrite($fp,$s->max('$info["css_lines"]', 5));
        fwrite($fp,"\n");

        fwrite($fp,"=== Modes ===\n");
        fwrite($fp,"DokuWiki knows three types of [[devel:css#stylesheet_modes|stylesheet modes]]. Most plugins just use the **screen** mode by adding ''styles.css'', [[devel:css#Plugins styles]] hints about the possibility to support other modes, SurveyBot found some examples:\n");
        fwrite($fp,"\n");
        fwrite($fp,$s->count('$info["cssprint"]')." plugins with ''Print.css''.\n");
        fwrite($fp,$s->plugins('$info["cssprint"]'));
        fwrite($fp,"\n");
        fwrite($fp,$s->count('$info["cssrtl"]')." plugins with ''rtl.css''.\n");
        fwrite($fp,$s->plugins('$info["cssrtl"]'));
        fwrite($fp,"\n");

        fwrite($fp,"=== Guaranteed color placeholders ===\n");
        fwrite($fp,"DokuWiki's CSS dispatcher is able to replace place holders in the loaded stylesheets. From 2006-08-05 on they have been renamed to be more semantically correct. \n");
        fwrite($fp,"SurveyBot found ".$s->count('$info["cssreplacements"]','Uses old CSS replacements')." plugins that still uses now obsolete css [[devel:css#replacements]].\n");

        $searchresult = $s->infos('$info["cssreplacements"]','Uses old CSS replacements');
        $oldreplacements = array();
        foreach($searchresult as $name => $info) {
            foreach ($info['cssreplacements'] as $rep) {
                $oldreplacements[$rep][] = $s->wiki_link($name);
            }
        }
        fwrite($fp,"\n^Old replacements ^Plugins ^\n");
        foreach ($oldreplacements as $name => $plugins) {
            fwrite($fp,"|%%$name%% |".$s->plugins_from_array(array_unique($plugins),true)."|\n");
        }
        fwrite($fp,"\n");

        fclose($fp);

        // !!! changing back "total" to make percentage against number of plugins !!!
        $s->total = count($this->info);
    }

    function _export_events($localoutputdir, $s) {
        $resultFile = $localoutputdir.'events.txt';
        echo "<li>$resultFile</li>";
        $fp = fopen($resultFile, 'w');

        fwrite($fp,"====== Plugin Survey ".date('Y')." - Events ======\n");
        fwrite($fp,"\n");
        $eventlist = $this->collections['eventlist'];
        $eventplugins = array();
        $notaction = array();
        $urlmissing = array();
        foreach ($this->info as $name => $info) {
            if (!$info['plugin']) continue;
            foreach ($info['plugin'] as $module) {
                if (!$module['events']) continue;
                $eventplugins[] = $name;
                if ($module['type'] != 'action') {
                    $notaction[$event][] = $name;
                }
                foreach ($module['events'] as $event) {
                    if (!$eventlist[$event]['plugins'] || !in_array($name, $eventlist[$event]['plugins'])) {
                        $eventlist[$event]['plugins'][] = $name;
                        if (!$eventlist[$event]['url']) {
                            $urlmissing[$event][] = $name;
                        }
                    }
                }
            }
        }
        $eventplugins = array_unique($eventplugins);
        $used_events = array_filter($eventlist, create_function('$a','return $a["plugins"];'));

        fwrite($fp,"A survey of DokuWiki plugins would not be complete without answering \"Who uses this Event?\" The [[devel:events|event system]] allows custom handling in addition to or instead of the standard processing. ");
        fwrite($fp,"In the analyzed code there are ".count($eventplugins)." plugins (".round(count($eventplugins)/$s->total*100)."% of all plugins) using ".count($used_events)." different events.\n");

        uasort($eventlist, create_function('$a, $b','return count($a["plugins"]) < count($b["plugins"]);'));
        fwrite($fp,"\n");
        fwrite($fp,"^Event ^Plugins ^\n");
        $i = 1;
        foreach ($eventlist as $name => $event) {
            if ($i++ > 5) break;
            if ($event['plugins']) {
                fwrite($fp,"|[[".$event['url']."|$name]] |".count($event['plugins'])." plugins |\n");
            }
        }
        fwrite($fp,"\n");

        fwrite($fp,"===== Deprecated events =====\n");
        fwrite($fp,"Events surrounding the usage of [[:namespace_templates|namespace templates]] when a new page is loaded into the editor has changed more than once. The original HTML_PAGE_FROMTEMPLATE was changed to COMMON_PAGE_FROMTEMPLATE and then to COMMON_PAGETPL_LOAD creating a dependency between plugins and specific DokuWiki releases. Two plugins currently support both latest events, one even all three of the events.\n");
        fwrite($fp,"\n");
        fwrite($fp,"^Before 2010-03-10 ^Last compatible release 2009-12-25c \"Lemming\" ^^\n");
        fwrite($fp,"|[[".$eventlist['HTML_PAGE_FROMTEMPLATE']['url']."|HTML_PAGE_FROMTEMPLATE]] |".$s->plugins_from_array($eventlist['HTML_PAGE_FROMTEMPLATE']['plugins'],true)." |\n");
        fwrite($fp,"^During 2010-03-10 -- 2011-02-03 ^Only compatible with DokuWiki 2010-11-07a \"Anteater\" ^^\n");
        fwrite($fp,"|[[".$eventlist['COMMON_PAGE_FROMTEMPLATE']['url']."|COMMON_PAGE_FROMTEMPLATE]] |".$s->plugins_from_array($eventlist['COMMON_PAGE_FROMTEMPLATE']['plugins'],true)." |\n");
        fwrite($fp,"^Since 2011-02-03 ^Compatible with DokuWiki 2011-05-25 \"Rincewind\" and later ^^\n");
        fwrite($fp,"|[[".$eventlist['COMMON_PAGETPL_LOAD']['url']."|COMMON_PAGETPL_LOAD]] |".$s->plugins_from_array($eventlist['COMMON_PAGETPL_LOAD']['plugins'],true)." |\n");
        fwrite($fp,"\n");

        fwrite($fp,"===== Non-action plugins =====\n");
        fwrite($fp,"Although mostly used by [[action plugins]] events can be included in any plugin or template script. ");
        fwrite($fp,"These plugins may not be executed immediately or at all for any given page and execution pathway.\n");
        fwrite($fp,"\n");
        fwrite($fp,"^Event ^Plugins (other than Action) ^^\n");
        ksort($urlmissing);
        foreach ($notaction as $name => $plugins) {
            fwrite($fp,"|[[".$eventlist[$name]['url']."|$name]] |".count($plugins)." |".$s->plugins_from_array($plugins, true)."|\n");
        }
        fwrite($fp,"\n");

        fwrite($fp,"===== Plugin specific events =====\n");
        fwrite($fp,"It is also possible to introduce plugin specific events not included in the [[devel:events_list]] as shown by these developers. They may be [[devel:events#signalling_an_event|signaled]] from the plugin itself or from another plugin. \n");
        fwrite($fp,"\n");
        fwrite($fp,"^Plugins ^^\n");
        ksort($urlmissing);
        foreach ($urlmissing as $name => $plugins) {
            fwrite($fp,"|$name |".count($plugins)." |".$s->plugins_from_array($plugins, true)."|\n");
        }
        fwrite($fp,"\n");

        fwrite($fp,"===== DokuWiki events =====\n");
        fwrite($fp,"And now for the complete list of [[devel:events]] found at time of [[start|survey]]. Events are merged with the [[devel:events_list]] showing 'unused' events.\n");
        fwrite($fp,"**Disclaimer** - this list was generated by a script, there may be plugins that are missing.\n");
        fwrite($fp,"\n");
        fwrite($fp,"^Event ^Plugins ^^\n");
        ksort($eventlist);
        foreach ($eventlist as $name => $event) {
            $eventlink = (($event['url']) ? "[[".$event['url']."|$name]]" : "%%$name%%");
            if ($name == 'HTTPCLIENT_REQUEST_SEND') {
                $extra = "((known to be used by http://rg42.org/wiki/dokuoauth_simple))";
            } else {
                $extra = '';
            }
            if ($event['plugins']) {
                fwrite($fp,"|$eventlink |".count($event['plugins'])." |".$s->plugins_from_array($event['plugins'], true)."$extra|\n");
            } else {
                fwrite($fp,"|$eventlink | | --- //not found in any examined plugin// -- $extra|\n");
            }
        }
        fclose($fp);
    }

    function _export_friendliness($localoutputdir, $s) {
        $resultFile = $localoutputdir.'friendliness.txt';
        echo "<li>$resultFile</li>";
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"====== Plugin Survey ".date('Y')." - Friendliness ======\n");
        fwrite($fp,"The plugin system was created to make it easy to share and reuse extensions and modification made by the DokuWiki community. \n");
        fwrite($fp,"Counted from the first DokuWiki release in august 2004 there has been an average of ".round($s->total/((2011-2004)*52),1)." new plugins every week! Today there is a wealth of plugins of different kinds.\n");
        fwrite($fp,"\n");
// TODO week count
        fwrite($fp,"===== Plugin repository =====\n");
        fwrite($fp,"How do I find my favourite plugin? It is not an easy task to navigate and choose among ".$s->total." plugins.\n");
        fwrite($fp,"\n");

        fwrite($fp,"=== Tags ===\n");
        fwrite($fp,"One of the main search mechanisms for plugins are a tag cloud in the [[:plugins|plugin list]]. Each plugin may have one or more tags attached to the plugin homepage. \n");
        fwrite($fp,"**Two years** ago there were **745** different tags. Thanks to a tag cleanup campain, **360** tags were found last year. ");
        fwrite($fp,"During this survey xx different tags where found, xx% of these were only used by a single plugin each. \n");
        fwrite($fp,"\n");
        fwrite($fp,"^ Tags ^ Plugins ^\n");
        fwrite($fp,$s->pivot('$info["tags"]',true,true,true,10));
        fwrite($fp,"\n");

        fwrite($fp,"=== Excluded plugins ===\n");
        fwrite($fp,"There are also some plugins that are not shown in the plugin list. ".$s->cnt('$info["security"]',null,'Active security warning')." are excluded from showing in [[plugins]] list for security reasons. ");
        fwrite($fp,"All these have problems with XSS vulnerability allowing arbitrary JavaScript insertion. Authors are informed.\n");
        fwrite($fp,$s->plugins('$info["security"]'));
        fwrite($fp,"\n");
        fwrite($fp,$s->cnt('$info["security_w"]')." plugins with security warnings.\n");
        fwrite($fp,$s->plugins('$info["security_w"]'));
        fwrite($fp,"\n");

        fwrite($fp,"===== Homepage appearance =====\n");
        fwrite($fp,"New users may become intimidated by large pages with a lot of confusing comments, plugins marked \"experimental\" or not stating explicit [[compatibility]].\n");
        fwrite($fp,"\n");

        fwrite($fp,"=== Homepage size ===\n");
        fwrite($fp,"There are at least ".$s->count('$info["textsize"] < 1000',null,'Small homepage')." plugin homepages that could be considered thin, most contain only a “Details & Download” link. \n");
        fwrite($fp,"The following ".$s->count('$info["pagesize"] > 100000','Need to cleanup plugin homepage')." plugins represent the other extreme having the largest pages with lots of <code> blocks and comments. They are in a desperate need of cleanup. \n");
        fwrite($fp,$s->plugins('$info["pagesize"] > 100000'));
        fwrite($fp,"\n");

        fwrite($fp,"=== Readablility ===\n");
        fwrite($fp,"First [[wp>Gunning fog index]] - The index estimates the years of formal education needed to understand the text on a first reading. ");
        fwrite($fp,"The median score for all plugins is ".$s->median('$info["readability_gf"]').".\n");
        fwrite($fp,"\n");
        fwrite($fp,"^Gunning-Fog ^ Plugins ^ ^\n");
        fwrite($fp,"| < 8 | ".$s->cnt('$info["readability_gf"] < 8')." |Suitable for a universal audiance |\n");
        fwrite($fp,"| 8 - 12 | ".$s->cnt('$info["readability_gf"] >= 8 && $info["readability_gf"] < 12')." |Suitable for a wide audiance |\n");
        fwrite($fp,"| >= 12 | ".$s->cnt('$info["readability_gf"] >= 12')." |Hard to understand |\n");
        fwrite($fp,$s->plugins('$info["readability_gf"] >= 12'));
        fwrite($fp,"\n");
        fwrite($fp,"An alternative measure is the [[wp>Flesch–Kincaid Readability Test|Flesch Reading Ease]] score, higher scores indicate material that is easier to read. ");
        fwrite($fp,"The median score is ".$s->median('$info["readability_fs"]').".\n");
        fwrite($fp,"\n");
        fwrite($fp,"^Flesh score ^ Plugins ^ ^\n");
        fwrite($fp,"| > 60 | ".$s->cnt('$info["readability_fs"] > 60')." |Easily understandable by 13- to 15-year-old students |\n");
        fwrite($fp,"| > 30 | ".$s->cnt('$info["readability_fs"] > 30 && $info["readability_fs"] <= 60')." |Hard to understand  by 13- to 15-year-old students |\n");
        fwrite($fp,"| %%<=%% 30 | ".$s->cnt('$info["readability_fs"] <= 30')." |Best understood by university graduates |\n");
        fwrite($fp,$s->plugins('$info["readability_fs"] <= 30'));
        fwrite($fp,"\n");
        fwrite($fp,"Below a graph of the distribution of [[wp>SMOG]] score that estimates the years of education needed to understand a piece of writing. ");
        fwrite($fp,"The median score is ".$s->median('$info["readability_sm"]').".\n");
        fwrite($fp,"\n");
        fwrite($fp,"^ SMOG ^ Plugins ^\n");
        fwrite($fp,$s->pivot('round($info["readability_sm"])'));
        fwrite($fp,"\n");

        fwrite($fp,"=== Images ===\n");
        $havingimage = '$info["homepageimage"] && ($info["t_syntax"] || $info["t_admin"])';
        $maxpopulation = $s->count('$info["t_syntax"] || $info["t_admin"]');
        fwrite($fp,"\"A picture is worth a thousand words\" –- Only ");
        fwrite($fp,$s->count($havingimage)." (".round($s->count($havingimage)/max(1,$maxpopulation)*100)."%) of the syntax and admin plugin homepages have one or more images showing the plugin in action on their homepage. This is a great way to explain complicated stuff. \n");
        fwrite($fp,"\n");

        fwrite($fp,"=== Devel & experimental ===\n");
        fwrite($fp,"Last year 21 plugins referred to [[devel:develonly]], this has grown to ".$s->count('$info["develonly"]','Develonly referred')." in this survey. ");
        fwrite($fp,"These plugins rely on features which are currently only available in the development version of DokuWiki.\n");
        fwrite($fp,$s->plugins('$info["develonly"]'));
        fwrite($fp,"\n");
        fwrite($fp,$s->cnt('$info["experimental"]',null,'Tagged !experimental')." are marked experimental.\n");
        fwrite($fp,$s->plugins('$info["experimental"]'));
        fwrite($fp,"\n");

        fwrite($fp,"===== Plugins content =====\n");
        fwrite($fp,"What does the downloaded plugin contain? Is it localized or did it contain a [[devel:darcs]] repro?\n");
        fwrite($fp,"\n");

        fwrite($fp,"=== Unrelated content ===\n");
        fwrite($fp,"All plugin authors have not removed unnecessary content like SVN directories before zipping their plugins. ");
        fwrite($fp,"These ".$s->count('$info["junk"]','Unrelated content in download')." plugins (**one less than previous year**) contained unrelated content at time of survey. \n");
        fwrite($fp,$s->plugins('$info["junk"]'));
        fwrite($fp,"\n");

        fwrite($fp,"=== Correct name ===\n");
        $nameconflict = array();
        foreach ($this->info as $name => $info) {
            if (!$info['plugin']) continue;
            foreach ($info['plugin'] as $module) {
                if ($module['name'] && strpos($module['name'], '_') === false) {
                    if (strcasecmp($module['name'], $name) != 0) {
                        $nameconflict[] = $name;
                    }
                }
            }
        }
        $nameconflict = array_unique($nameconflict);
        fwrite($fp,"Although most users never notice, there are ".count($nameconflict)." plugins that have a homepage name that differs from the plugin class name. Beside the risk of [[compatibility]] problems, the developer will not see any popularity rating in the [[::plugins|plugin list]]. It will also confuse users that actually read the code, question arises \"Have I downloaded the right file?\".\n");
        fwrite($fp,"\n");
        fwrite($fp,$s->plugins_from_array($nameconflict));
        fwrite($fp,"\n");

        fwrite($fp,"===== Language =====\n");
        fwrite($fp,"Plugins can be [[::localization|localized]] by using the [[devel:common_plugin_functions#localisation|plugin framework]], ");
        fwrite($fp,"this feature was found in approximately ".$s->cnt('$info["lang"]')." of the plugins. ");
        fwrite($fp,"Compared to the 60 languages included in the DokuWiki 2011-05-25 release, \n");
        fwrite($fp,"\n");
        foreach ($this->info as $name => $info) {
            if ($info['lang']) {
				foreach ($info['lang'] as $lang) {
                        $langs[$lang] += 1;
                    if (strpos($lang,'.') === false) {
                    }
                }
            }
        }
        arsort($langs);
        fwrite($fp,"^Language  ^ Plugins ^^\n");
        foreach ($langs as $lang => $value) {
            if ($value < 0) break;
            fwrite($fp,"|$lang |$value |". $this->manager->languageCodes[$lang] ." |\n");
        }
        fwrite($fp,"\n");

        fwrite($fp,"Finally a list of plugins with 10 or more languages.\n");
        fwrite($fp,"\n");
        fwrite($fp,"^Languages ^ Plugins ^^\n");
        fwrite($fp,$s->pivot('count($info["lang"])',false,false,true,20,true));
        fwrite($fp,"\n");
        fclose($fp);
    }

    function _export_developers($localoutputdir, $s) {
        function sortdevplugins_callback($a, $b) {
            if (count($a['plugins']) == count($b['plugins'])) {
                return strcasecmp($a['developer'], $b['developer']);
            }
            return (count($a['plugins']) > count($b['plugins'])) ? -1 : 1;
        }

        function sortpopularity_callback($a, $b) {
            if ($a['popularity'] == $b['popularity']) {;
                return strcasecmp($a['developer'], $b['developer']);
            }
            return ($a['popularity'] > $b['popularity']) ? -1 : 1;
        }

        /*
         * collect popularity points and number of plugins for each developer
         */
        $developer = array();
        $newdeveloper = array();
        foreach ($this->info as $name => $plugin) {
            $devname = str_replace('&#039;','"',htmlspecialchars_decode($plugin['developer']));
            $devname = ucwords(str_replace(',',' &',$devname));
            $newdeveloper[] = $devname;
            $developer[$devname]['developer'] = $devname;
            $developer[$devname]['plugins'][] = $name;
            $developer[$devname]['popularity'] += $plugin['popularity'];
        }
        unset($developer['']);
        uasort($developer, "sortdevplugins_callback");
        foreach ($developer as $name => $dev) {
            if ($i++ >= 10) break;
            $topcontrib += count($dev['plugins']);
        }
        $newdeveloper = array_unique($newdeveloper);
        $newdeveloper = array_diff($newdeveloper, $this->collections['previousDevelopers']);
        $singleplugindevs = count(array_filter($developer,create_function('$a','return count($a["plugins"]) == 1;')));
        /*
         * END::collect popularity points and number of plugins for each developer
         */

        $resultFile = $localoutputdir.'developers.txt';
        echo "<li>$resultFile</li>";
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"===== Plugin Survey ".date('Y')." - Developers =====\n");
        fwrite($fp,"It must be easy to write a plugin, or at least very fun ;-). ");
        fwrite($fp,"There are ".count($developer)." (last year 333) different developers which is almost exactly ".round($s->total/count($developer),1)." plugins/developer");
        fwrite($fp,"in the [[start|survey]] but the wast majority (".round($singleplugindevs/count($developer)*100)."%) of the developers are single plugin authors. Our top ten most productive developers has written $topcontrib plugins together. \n");
        fwrite($fp,"They have become a little less influential, as this is only ".round($topcontrib/$s->total*100)."% of the total number of plugins, compared to previous survey when they had written 26% of all plugins.\n");
        fwrite($fp,"\n");

        fwrite($fp,"We welcome these ".count($newdeveloper)." new plugin authors.\n");
        fwrite($fp,$s->plugins_from_array($newdeveloper));
        fwrite($fp,"\n");

        fwrite($fp,"===== By number of plugins =====\n");
        fwrite($fp,"\n");
        fwrite($fp,"^ ^Developer ^Plugins ^^\n");
        
        $i = 1;
        $place = 1;
        $previouscount = -1;
        foreach ($developer as $name => $dev) {
//            if (count($dev['plugins']) < 2) break;
            if ($previouscount != count($dev['plugins'])) {
                $place = $i;
                $previouscount = count($dev['plugins']);
                if ($previouscount == 1) break;
                fwrite($fp,"|  $place.|$name |  ".count($dev['plugins']).'|'.$s->plugins_from_array($dev['plugins'],true)."|\n");
            } else {
                fwrite($fp,"| ::: |$name |  ".count($dev['plugins']).'|'.$s->plugins_from_array($dev['plugins'],true)."|\n");
            }
            $i++;
        }
        fwrite($fp,"\n");
        fwrite($fp,"$singleplugindevs developers with only one plugin are not published here.\n");
        fwrite($fp,"\n");

        fwrite($fp,"===== By popularity =====\n");
        fwrite($fp,"Ranking could be done in many ways and some plugins are used by more installations than others.\n");
        fwrite($fp,"Thanks to the [[plugin:popularity]] plugin some usage data is available. Here are developers sorted by their plugins\n");
        fwrite($fp,"total popularity points. IMHO Andi should have some extra points for the bundled plugins that all have zero points in the plugin list.\n");
        fwrite($fp,"\n");
        fwrite($fp,"^ ^Developer ^Popularity  ^Plugins ^\n");
        uasort($developer, "sortpopularity_callback");
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
                    fwrite($fp,"|  $place.|$name |  ".$dev['popularity'].' points|'.$s->plugins_from_array($dev['plugins'],true)."|\n");
                } else {
                    fwrite($fp,"| ::: |$name |  ".$dev['popularity'].' points|'.$s->plugins_from_array($dev['plugins'],true)."|\n");
                }
                $i++;
            }
        }
        fwrite($fp,"\n");
        fwrite($fp,"$lowpop developers with one or zero popularity points are not published. ");
        fwrite($fp,"The single point is assumed to be the developer themselves. One way to gain zero popularity is by having an [[http://www.freelists.org/post/dokuwiki/incorrect-plugin-page-names|incorrect page name]] for the plugin homepage.\n");
        fwrite($fp,"\n");

        fwrite($fp,"===== Rising stars =====\n");
        fwrite($fp,"\n");
        fwrite($fp,"\n");
        fwrite($fp,"\n");

        fwrite($fp,"<sub>Return to <= [[start|Beginning of survey]]</sub>\n");
        fwrite($fp,"\n");
        fclose($fp);
    }

    function _export_trackedDevErrors($localoutputdir, $s) {
        $resultFile = $localoutputdir.'trackedDevErrors.txt';
        echo "<li>$resultFile</li>";
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"====== Plugin Survey ".date('Y')." - Developers Todo ======\n");

        fwrite($fp,"^Developer ^Error ^ Plugin^\n");
        $previousName = '';
        foreach ($this->collections['trackedDevErr'] as $name => $errors) {
            foreach ($errors as $err => $plugins) {
                if ($previousName != $name) {
                    fwrite($fp,"|$name  | $err  |".$s->plugins_from_array($plugins,true)."|\n");
                    $previousName = $name;
                } else {
                    fwrite($fp,"| :::  | $err  |".$s->plugins_from_array($plugins,true)."|\n");
                }
            }
        }
        fclose($fp);
    }

    function _export_teamtodolist($localoutputdir, $s) {
        $resultFile = $localoutputdir.'teamTodoList.txt';
        echo "<li>$resultFile</li>";

        $fp = fopen($resultFile, 'w');
        fwrite($fp,"====== Plugin & Templates team - Todo ======\n");
        fwrite($fp,"\n");

        fwrite($fp,"==== Unpopular ? ====\n");
        $fouryearsago = date('Y-m-d', mktime(0, 0, 0, date("m"), date("d"), date("Y")-3));
        $unpopular = '$info["popularity"] == 0 && $info["lastupdate"] < "'.$fouryearsago.'"';
        fwrite($fp,$s->cnt($unpopular)." are not updated during last 3 years and still have 0 popularity.\n");
        fwrite($fp,$s->plugins($unpopular));
        fwrite($fp,"\n");

        fwrite($fp,"==== Missing download button ? ====\n");
        $missingbutton = '!$info["downloadbutton"] && $info["download"]';
        fwrite($fp,$s->cnt($missingbutton)." are missing download button but may have a downlink on their pages.\n");
        fwrite($fp,$s->plugins($missingbutton));
        fwrite($fp,"\n");

        fclose($fp);
    }

    function _export_references($localoutputdir, $s) {
        $resultFile = $localoutputdir.'references.txt';
        echo "<li>$resultFile</li>";
        $fp = fopen($resultFile, 'w');
        fwrite($fp,"===== References =====\n");
        fwrite($fp,"\n");
        fwrite($fp,"^References ^Plugins ^^\n");
        fwrite($fp,$s->pivot('$info["references"]',true,true,true));
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
        $plugin_info[] = 'textsize';
        $plugin_info[] = 'Gunning-Fog';
        $plugin_info[] = 'Flesh score';
        $plugin_info[] = 'SMOG';
        $plugin_info[] = 'homepageimage';
        $plugin_info[] = 'toc';
        $plugin_info[] = 'popularity';
        $plugin_info[] = 'popularity_old';
        $plugin_info[] = 'lastupdate';
        $plugin_info[] = 'bundled';
        $plugin_info[] = 'security';
        $plugin_info[] = 'security_w';
        $plugin_info[] = 'experimental';
        $plugin_info[] = 'junk';
        $plugin_info[] = 'code_on_page';
        $plugin_info[] = 'php_lines';
        $plugin_info[] = 'php5';
        $plugin_info[] = 'javascript';
        $plugin_info[] = 'jquery';
        $plugin_info[] = 'java_lines';
        $plugin_info[] = 'java_lint';
        $plugin_info[] = 'css';
        $plugin_info[] = 'css_lines';
        $plugin_info[] = 'strftime';
        $plugin_info[] = 'dformat';
        $plugin_info[] = 'downloadstyle';
        $plugin_info[] = 'downloadfail';
        $plugin_info[] = 'downloadexamined';
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
        $plugin_info[] = 'conf';
        $plugin_info[] = 'images';
        $plugin_info[] = 'lang';
        $plugin_info[] = 't_syntax';
        $plugin_info[] = 't_admin';
        $plugin_info[] = 't_renderer';
        $plugin_info[] = 't_action';
        $plugin_info[] = 't_helper';
        $plugin_info[] = 'regexp';
        $plugin_info[] = 'event';
        fputcsv($fp, $plugin_info);

        // data
        foreach ($this->info as $name => $info) {
            $plugin_info = array();
            $plugin_info[] = $name;
            $plugin_info[] = $info['new'];
            $plugin_info[] = (in_array($name, $this->collections['notPlugins']) ? 'notPlugin': '');
            $plugin_info[] = $info['dirdiff'];
            $plugin_info[] = $info['developer'];
            $plugin_info[] = $info['pagemodified'];
            $plugin_info[] = $info['pagesize'];
            $plugin_info[] = $info['textsize'];
            $plugin_info[] = $info['readability_gf'] ? str_replace('.',',',$info['readability_gf']) : '';
            $plugin_info[] = $info['readability_fs'] ? str_replace('.',',',$info['readability_fs']) : '';
            $plugin_info[] = $info['readability_sm'] ? str_replace('.',',',$info['readability_sm']) : '';
            $plugin_info[] = $info['homepageimage'];
            $plugin_info[] = $info['toc'];
            $plugin_info[] = $info['popularity'];
            $plugin_info[] = $info['popularity_old'];
            $plugin_info[] = $info['lastupdate'];
            $plugin_info[] = $info['bundled'];
            $plugin_info[] = $info['security'];
            $plugin_info[] = $info['security_w'];
            $plugin_info[] = $info['experimental'];
            $plugin_info[] = $info['junk'];
            $plugin_info[] = $info['code'];
            $plugin_info[] = $info['php_lines'];
            $plugin_info[] = $info['php5'];
            $plugin_info[] = $info['javascript'];
            $plugin_info[] = $info['jquery'];
            $plugin_info[] = $info['java_lines'];
            $plugin_info[] = $info['java_lint'];
            $plugin_info[] = $info['css'];
            $plugin_info[] = $info['css_lines'];
            $plugin_info[] = $info['strftime'];
            $plugin_info[] = $info['dformat'];
            $plugin_info[] = $info['downloadstyle'];
            $plugin_info[] = $info['downloadfail'];
            $plugin_info[] = $info['downloadexamined'];
            $plugin_info[] = $info['time'];
            $plugin_info[] = $info['externalpage'];
            $plugin_info[] = $info['donatebutton'];
            $plugin_info[] = $info['bugsbutton'];
            $plugin_info[] = $info['repobutton'];
            $plugin_info[] = $info['downloadbutton'];
            $plugin_info[] = $info['mediaurl'];
            $plugin_info[] = ($info['download'] ? implode(',', $info['download']): '');
            $plugin_info[] = ($info['tags'] ? implode(',', $info['tags']): '');
            $plugin_info[] = ($info['references'] ? implode(',', $info['references']): '');
            $plugin_info[] = count($info['links']);
            $plugin_info[] = ($info['dokulinks'] ? $info['dokulinks'][0]: '');
            $plugin_info[] = $info['license'];
            $plugin_info[] = $info['conf'];
            $plugin_info[] = $info['images'];
            $plugin_info[] = count($info['lang']);
            $plugin_info[] = $info['t_syntax'];
            $plugin_info[] = $info['t_admin'];
            $plugin_info[] = $info['t_renderer'];
            $plugin_info[] = $info['t_action'];
            $plugin_info[] = $info['t_helper'];

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

