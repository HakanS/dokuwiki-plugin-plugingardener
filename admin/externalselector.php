<?php
/**
 * Plugin for a supervising Dokuwiki plugins
 *
 * !!! edits has been done in php.ini to allow_url_fopen !!!
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     Håkan Sandell <hakan.sandell@home.se>
 */

 // must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN')) 
    define('DOKU_PLUGIN',DOKU_INC.'lib/plugins/');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_plugingardener_externalselector extends DokuWiki_Admin_Plugin {

    var $externalpages = array();

    function getMenuText($language) {
        return 'Plugin gardener - External page selector';
    }

    function handle() {
 
        $external_fn = 'C:/DokuWikiStickNew/tmp2011/externalpages.txt';
        if (file_exists($external_fn)) {
            $this->externalpages = unserialize(file_get_contents($external_fn));
        }

        if ($_REQUEST['cmd'] = 'save') {
            if (!checkSecurityToken()) return;

            foreach ($this->externalpages as $plugin => $external) {
                if ($_REQUEST['file'][$plugin]) {
                    $sel = $_REQUEST['file'][$plugin][0];
                    $this->externalpages[$plugin]['selected'] = $external['links'][$sel][0];
                } else {
                    $this->externalpages[$plugin]['selected'] = '';
                }
            }
            file_put_contents($external_fn, serialize($this->externalpages));
        }      
    }
 
    /**
     * output appropriate html
     */
    function html() {
        ptln('<h1>External page selector</h1>');

        ptln('<form action="'.wl($ID).'" method="post">');
        ptln('  <input type="hidden" name="do"   value="admin" />');
        ptln('  <input type="hidden" name="page" value="plugingardener_externalselector" />');
        formSecurityToken();

        ptln('<table class="inline">');
        $oddeven = 0;
        foreach ($this->externalpages as $plugin => $external) {
            $rowspan = count($external['links']);
            $oddeven++;
            $fileItr = 0;
            foreach ($external['links'] as $link) {
                if ($oddeven % 2 == 0) {
                    echo '<tr>';
                } else {
                    echo '<tr style="background-color:#ddd;">';
                }
                if ($rowspan > 1) {
                    echo '<td rowspan="'.$rowspan.'">'.$this->echolink($plugin).'</td>';
                    $rowspan = 0;
                } elseif ($rowspan == 1) {
                    echo '<td>'.$this->echolink($plugin).'</td>';
                }
                echo '<td><a href="'.$link[0].'">'.$link[0].'</a></td>';
                echo '<td><b>'.$link[1].'</b></td>';
                echo '<td>';
                if ($link[0] == $external['selected']) {
                    $checked = 'checked';
                } else {
                    $checked = '';
                }
                echo '<input type="checkbox" name="file['.$plugin.'][]" '.$checked.' value="'.$fileItr.'" />';
                echo '</td>';
                echo '</tr>';
                $fileItr++;
            }
        }
        ptln('</table>');

        ptln('<input type="submit" name="cmd"  value="Save" />');
        ptln('</form>');
    }

    function echolink($plugin) {
        return "<a href=\"http://www.dokuwiki.org/plugin:$plugin\">$plugin</a>";
    }

}
