<?php
/**
 * Plugin for a supervising Dokuwiki plugins
 *
 * !!! edits has been done in php.ini to allow_url_fopen !!!
 *
 * @license    GPL 2 (http://www.gnu.org/licenses/gpl.html)
 * @author     H�kan Sandell <hakan.sandell@home.se>
 */

// must be run within Dokuwiki
if(!defined('DOKU_INC')) die();

if(!defined('DOKU_PLUGIN'))
    define('DOKU_PLUGIN', DOKU_INC.'lib/plugins/');

require_once(DOKU_PLUGIN.'/plugingardener/classes/pg_gardener.class.php');
require_once(DOKU_PLUGIN.'/plugingardener/classes/pg_dokuwikiwebexaminer.class.php');
require_once(DOKU_PLUGIN.'/plugingardener/classes/pg_codedownloader.class.php');
require_once(DOKU_PLUGIN.'/plugingardener/classes/pg_codeexaminer.class.php');
require_once(DOKU_PLUGIN.'/plugingardener/classes/pg_reportwriter.class.php');

/**
 * All DokuWiki plugins to extend the admin function
 * need to inherit from this class
 */
class admin_plugin_plugingardener_gardener extends DokuWiki_Admin_Plugin {

    public $info = array();
    public $collections = array();
    // danger ! localdir is hardcode in other admin
    public $cfg = array(
        'doku_eventlist_uri'  => 'https://www.dokuwiki.org/devel:events_list',
        'doku_repo_uri'       => 'https://www.dokuwiki.org/lib/plugins/pluginrepo/repository.php',
        'doku_index_uri'      => 'https://www.dokuwiki.org/plugins',
        'doku_pluginbase_uri' => 'https://www.dokuwiki.org/plugin:',
        'bundledsourcedir'    => '/home/gerrit/dokuwiki/dokuwiki/lib/plugins/',
        'localdir'            => '/home/gerrit/dokuwiki/tmp20131107/',
        'previousYearTotal'   => 672,
        'offline'             => false, // enable/disable downloads from web
        'downloadindex'       => true,  // download page cfg[doku_index_uri]?idx=plugin always or only when nonexists
                                        // download page cfg[doku_index_uri]?pluginsort=p always or only when nonexists
                                        // download xml at cfg[doku_repo_uri] always or only when nonexists
        'downloadpages'       => false, // download plugin wiki pages from cfg[doku_pluginbase_uri] always or only when nonexists
                                        // download external pages from founded pageurls always or only when nonexists
                                        // download page cfg[doku_eventlist_uri] always or only when nonexists
        'downloadplugins'     => false, // download code of plugins
        'overwrite'           => false,
        'firstplugin'         => '',    // handle a subset of all plugins starting at this plugin
        'lastplugin'          => '',    // handle a subset of all plugins ending at this plugin
        'fasteval'            => true,  // skip readibility score calculation, cloc and jslint
        'cloc'                => false, // is cloc.exe available? for counting lines of code
        'jslint'              => false  // is running of jslint possible?
    );

    public function handle() {
    }

    public function html() {
        echo "<h1>Plugin Gardener</h1>";

        // ensure output directory exists
        $localdir = $this->cfg['localdir'];
        if(!file_exists($localdir)) {
            mkdir($localdir, 0777, true);
        }

        //test write access
        if(!is_writable($this->cfg['localdir'])) {
            echo "<h2>Aborted</h2>";
            echo "Directory ".hsc($this->cfg['localdir'])." is not writable. Are the permissions correct?";
            return;
        }

        // get plugins that not are plugins (manualy managed local list)
        echo "<h5>Not plugins</h5>";
        $this->collections['notPlugins'] = file($localdir.'not_plugins.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if(!$this->collections['notPlugins']) $this->collections['notPlugins'] = array();
        $this->echodwlink($this->collections['notPlugins']);

        // get list of developers with special attention (manualy managed local list)
        $this->collections['trackedDevelopers'] = file($localdir.'tracked_developers.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if(!$this->collections['trackedDevelopers']) $this->collections['trackedDevelopers'] = array();
        $this->collections['trackedDevErr'] = array();

        // get list of previous years developers (manualy managed local list)
        $this->collections['previousDevelopers'] = file($localdir.'previous_developers.txt', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if(!$this->collections['previousDevelopers']) $this->collections['previousDevelopers'] = array();
        $this->collections['previousDevelopers'] = array_unique($this->collections['previousDevelopers']);

        // Downloads and reads plugin wiki pages, external pages and event list from the web (or local stored files)
        $handler = new pg_dokuwikiwebexaminer($this);
        if(!$handler->execute()) {
            echo "<h2>Aborted</h2>";
            return;
        }

        // Downloads source code of the plugins
        $handler = new pg_codedownloader($this);
        $handler->execute();

        // collect files info and counts content of code
        $handler = new pg_codeexaminer($this);
        $handler->execute();

        // create wiki pages for the survey report
        $handler = new pg_reportwriter($this);
        $handler->execute();

        echo "<h4>Done</h4>";
        echo "<hr/>\n";
        if($this->cfg['firstplugin'] == $this->cfg['lastplugin'] && $this->cfg['firstplugin'] != '') print_r($this->info);
        echo "<hr/>\n";
//        echo print_r($this->collections['trackedDevErr']);
    }

    /**
     * Print comma separated row of links to plugin wiki pages
     *
     * @param string|array $plugins array of plugin names or one plugin name
     */
    private function echodwlink($plugins) {
        if(!is_array($plugins)) {
            $plugins = array($plugins);
        }
        $tmp = '';
        foreach($plugins as $plugin) {
            $tmp .= "<a href=\"$this->cfg[doku_pluginbase_uri]$plugin\">$plugin</a>, ";
        }
        echo substr($tmp, 0, -2);
    }

    public $languageCodes = array(
        "aa"    => "Afar",
        "ab"    => "Abkhazian",
        "ae"    => "Avestan",
        "af"    => "Afrikaans",
        "ak"    => "Akan",
        "am"    => "Amharic",
        "an"    => "Aragonese",
        "ar"    => "Arabic",
        "as"    => "Assamese",
        "av"    => "Avaric",
        "ay"    => "Aymara",
        "az"    => "Azerbaijani",
        "ba"    => "Bashkir",
        "be"    => "Belarusian",
        "bg"    => "Bulgarian",
        "bh"    => "Bihari",
        "bi"    => "Bislama",
        "bm"    => "Bambara",
        "bn"    => "Bengali",
        "bo"    => "Tibetan",
        "br"    => "Breton",
        "bs"    => "Bosnian",
        "ca"    => "Catalan",
        "ce"    => "Chechen",
        "ch"    => "Chamorro",
        "co"    => "Corsican",
        "cr"    => "Cree",
        "cs"    => "Czech",
        "cu"    => "Church Slavic",
        "cv"    => "Chuvash",
        "cy"    => "Welsh",
        "da"    => "Danish",
        "de"    => "German",
        "dv"    => "Divehi",
        "dz"    => "Dzongkha",
        "ee"    => "Ewe",
        "el"    => "Greek",
        "en"    => "English",
        "eo"    => "Esperanto",
        "es"    => "Spanish",
        "et"    => "Estonian",
        "eu"    => "Basque",
        "fa"    => "Persian",
        "ff"    => "Fulah",
        "fi"    => "Finnish",
        "fj"    => "Fijian",
        "fo"    => "Faroese",
        "fr"    => "French",
        "fy"    => "Western Frisian",
        "ga"    => "Irish",
        "gd"    => "Scottish Gaelic",
        "gl"    => "Galician",
        "gn"    => "Guarani",
        "gu"    => "Gujarati",
        "gv"    => "Manx",
        "ha"    => "Hausa",
        "he"    => "Hebrew",
        "hi"    => "Hindi",
        "ho"    => "Hiri Motu",
        "hr"    => "Croatian",
        "ht"    => "Haitian",
        "hu"    => "Hungarian",
        "hy"    => "Armenian",
        "hz"    => "Herero",
        "ia"    => "Interlingua (International Auxiliary Language Association)",
        "id"    => "Indonesian",
        "ie"    => "Interlingue",
        "ig"    => "Igbo",
        "ii"    => "Sichuan Yi",
        "ik"    => "Inupiaq",
        "io"    => "Ido",
        "is"    => "Icelandic",
        "it"    => "Italian",
        "iu"    => "Inuktitut",
        "ja"    => "Japanese",
        "jv"    => "Javanese",
        "ka"    => "Georgian",
        "kg"    => "Kongo",
        "ki"    => "Kikuyu",
        "kj"    => "Kwanyama",
        "kk"    => "Kazakh",
        "kl"    => "Kalaallisut",
        "km"    => "Khmer",
        "kn"    => "Kannada",
        "ko"    => "Korean",
        "kr"    => "Kanuri",
        "ks"    => "Kashmiri",
        "ku"    => "Kurdish",
        "kv"    => "Komi",
        "kw"    => "Cornish",
        "ky"    => "Kirghiz",
        "la"    => "Latin",
        "lb"    => "Luxembourgish",
        "lg"    => "Ganda",
        "li"    => "Limburgish",
        "ln"    => "Lingala",
        "lo"    => "Lao",
        "lt"    => "Lithuanian",
        "lu"    => "Luba-Katanga",
        "lv"    => "Latvian",
        "mg"    => "Malagasy",
        "mh"    => "Marshallese",
        "mi"    => "Maori",
        "mk"    => "Macedonian",
        "ml"    => "Malayalam",
        "mn"    => "Mongolian",
        "mr"    => "Marathi",
        "ms"    => "Malay",
        "mt"    => "Maltese",
        "my"    => "Burmese",
        "na"    => "Nauru",
        "nb"    => "Norwegian Bokmal",
        "nd"    => "North Ndebele",
        "ne"    => "Nepali",
        "ng"    => "Ndonga",
        "nl"    => "Dutch",
        "nn"    => "Norwegian Nynorsk",
        "no"    => "Norwegian",
        "nr"    => "South Ndebele",
        "nv"    => "Navajo",
        "ny"    => "Chichewa",
        "oc"    => "Occitan",
        "oj"    => "Ojibwa",
        "om"    => "Oromo",
        "or"    => "Oriya",
        "os"    => "Ossetian",
        "pa"    => "Panjabi",
        "pi"    => "Pali",
        "pl"    => "Polish",
        "ps"    => "Pashto",
        "pt"    => "Portuguese",
        "pt-br" => "Brazilian Portuguese",
        "qu"    => "Quechua",
        "rm"    => "Raeto-Romance",
        "rn"    => "Kirundi",
        "ro"    => "Romanian",
        "ru"    => "Russian",
        "rw"    => "Kinyarwanda",
        "sa"    => "Sanskrit",
        "sc"    => "Sardinian",
        "sd"    => "Sindhi",
        "se"    => "Northern Sami",
        "sg"    => "Sango",
        "si"    => "Sinhala",
        "sk"    => "Slovak",
        "sl"    => "Slovenian",
        "sm"    => "Samoan",
        "sn"    => "Shona",
        "so"    => "Somali",
        "sq"    => "Albanian",
        "sr"    => "Serbian",
        "ss"    => "Swati",
        "st"    => "Southern Sotho",
        "su"    => "Sundanese",
        "sv"    => "Swedish",
        "sw"    => "Swahili",
        "ta"    => "Tamil",
        "te"    => "Telugu",
        "tg"    => "Tajik",
        "th"    => "Thai",
        "ti"    => "Tigrinya",
        "tk"    => "Turkmen",
        "tl"    => "Tagalog",
        "tn"    => "Tswana",
        "to"    => "Tonga",
        "tr"    => "Turkish",
        "ts"    => "Tsonga",
        "tt"    => "Tatar",
        "tw"    => "Twi",
        "ty"    => "Tahitian",
        "ug"    => "Uighur",
        "uk"    => "Ukrainian",
        "ur"    => "Urdu",
        "uz"    => "Uzbek",
        "ve"    => "Venda",
        "vi"    => "Vietnamese",
        "vo"    => "Volapuk",
        "wa"    => "Walloon",
        "wo"    => "Wolof",
        "xh"    => "Xhosa",
        "yi"    => "Yiddish",
        "yo"    => "Yoruba",
        "za"    => "Zhuang",
        "zh"    => "Chinese",
        "zu"    => "Zulu"
    );
}
//Setup VIM: ex: et ts=4 enc=utf-8 :
