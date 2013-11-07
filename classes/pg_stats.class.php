<?php

if(!defined('LF')) define('LF',"\n");

/**
 * Class pg_stats
 */
class pg_stats {

    public $total = 0;
    private $info = null;
    private $collections = null;
    private $cache = array();

    /**
     * @param array $info array with plugins
     * @param array $collections
     */
    public function __construct(&$info, &$collections) {
        $this->info = &$info;
        $this->total = count($info);
        $this->collections = &$collections;
    }

    /**
     * Callback function
     *
     * @param $info
     * @param $plugin
     * @param $dev_error_msg
     */
    private function checkDevError($info, $plugin, $dev_error_msg) {
        if (in_array(strtolower($info['developer']), $this->collections['trackedDevelopers'])) {
            if (!$this->collections['trackedDevErr'][$info['developer']][$dev_error_msg] || !in_array($plugin, $this->collections['trackedDevErr'][$info['developer']][$dev_error_msg])) {
                $this->collections['trackedDevErr'][$info['developer']][$dev_error_msg][] = $plugin;
            }
        }
    }

    /**
     *
     *
     * @param $expression
     * @param $dev_error_msg
     * @param bool $addinfo
     * @return array
     */
    private function filter($expression, $dev_error_msg, $addinfo = false) {
        $retval = array();

        $func = create_function('$info' , "return ($expression);");
        $plugins = array_filter($this->info, $func);

        $retval['cnt'] = count($plugins);
        $retval['plugins'] = array_keys($plugins);
        if ($addinfo) {
            $retval['infos'] = $plugins;
            $retval['values'] = array_map($func, $plugins);
        }

        if ($dev_error_msg) {
            array_walk($plugins, array($this,'checkDevError'), $dev_error_msg);
        }
        return $retval;
    }

    /**
     * Wiki syntax of link to plugin wiki page
     *
     * @param $plugin
     * @return string
     */
    public function wiki_link($plugin) {
        return "[[plugin:$plugin]]";
    }

    /**
     * @param $expression
     * @param null $dev_error_msg
     * @return mixed
     */
    public function infos($expression, $dev_error_msg = null) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || !$cache[$key]['infos'] || $dev_error_msg) {
            $cache[$key] = $this->filter($expression, $dev_error_msg, true);
        }
        return $cache[$key]['infos'];
    }

    /**
     * @param $expression
     * @param null $dev_error_msg
     * @return mixed
     */
    public function count($expression, $dev_error_msg = null) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || $dev_error_msg) {
            $cache[$key] = $this->filter($expression, $dev_error_msg);
        }
        return $cache[$key]['cnt'];
    }

    /**
     * returns "X plugins (Y%)"
     *
     * @param $expression
     * @param null $format
     * @param null $dev_error_msg
     * @return string
     */
    public function cnt($expression, $format = null, $dev_error_msg = null) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || $dev_error_msg) {
            $cache[$key] = $this->filter($expression, $dev_error_msg);
        }
        if ($format) {
            return sprintf($format, (string)$cache[$key]['cnt'], '('.$this->percentage($cache[$key]['cnt'], $this->total).'%)');
        } else {
            return $cache[$key]['cnt'].' plugins ('. $this->percentage($cache[$key]['cnt'], $this->total).'%)';
        }
    }

    /**
     * returns list of links
     *
     * @param $expression
     * @param null $dev_error_msg
     * @return string
     */
    public function plugins($expression, $dev_error_msg = null) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || $dev_error_msg) {
            $cache[$key] = $this->filter($expression, $dev_error_msg);
        }
        return $this->plugins_from_array($cache[$key]['plugins']);
    }

    /**
     * @param $plugins
     * @param bool $linksonly
     * @return string
     */
    public function plugins_from_array($plugins, $linksonly = false) {
        if (count($plugins) == 0) {
            return '  * No plugins matched expression'.LF;
        } else {
            $plugins = array_map('pg_stats::wiki_link', $plugins);
            if ($linksonly) {
                return implode(', ', $plugins);
            } else {
                return '  * '.implode(', ', $plugins).LF;
            }
        }
    }

    /**
     * returns pivot table WITHOUT table header
     *
     * @param $expression
     * @param bool $showpercent
     * @param bool $sortlinkcount
     * @param bool $sortdesc
     * @param null $num
     * @param bool $showlinks
     * @return string
     */
    public function pivot($expression, $showpercent = false, $sortlinkcount = false, $sortdesc = false, $num = null, $showlinks = false) {
        $result = array();
        $func = create_function('$info' , "return ($expression);");
        $plugin_cnt = 0;
        foreach($this->info as $name => $info) {
            $key = $func($info);
            if (is_array($key)) {
                foreach($key as $k) {
                    $result[$k][] = $this->wiki_link($name);
                }
                $plugin_cnt++;
            } else {
                if ($key) {
                    $result[$key][] = $this->wiki_link($name);
                    $plugin_cnt++;
                }
            }
        }
        if (!$result) {
            return '| No plugins matched expression |'.LF;
        }

        if ($sortlinkcount) {
            if ($sortdesc) {
                uasort($result, create_function('$a,$b' , 'return (count($b)-count($a));'));
            } else {
                uasort($result, create_function('$a,$b' , 'return (count($a)-count($b));'));
            }
        } else {
            if ($sortdesc) {
                krsort($result);
            } else {
                ksort($result);
            }
        }
        $retval = '';
        $cnt = 0;
        foreach($result as $key => $links) {
            $retval .= '|  '.$key.'  |  ';
            $retval .= count($links);
            if ($showpercent) {
                $retval .= ' ('.$this->percentage(count($links), $this->total).'%)';
            }
            $retval .= ' | ';
            if ($showlinks) {
                $retval .= ($links ? implode(', ', $links) : '').' |';
            }
            $retval .= LF;
            if ($num && ++$cnt >= $num) break;
        }
        $single = array_filter($result, create_function('$a' , 'return (count($a) == 1);'));
        $retval .= '-----------------------------------------------------------------------------'.LF;
        $retval .= '  Pivot generated '.count($result).' rows and contain '.$plugin_cnt.' plugins.'.LF;
        $retval .= '  '.count($single).' rows ('.$this->percentage(count($single), count($result)).'%) contain only one plugin.'.LF;
        $retval .= '-----------------------------------------------------------------------------'.LF;
        return $retval;
    }

    /**
     * @param $expression
     * @param int $num
     * @return string
     */
    public function max($expression, $num = 1) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || !$cache[$key]['infos']) {
            $cache[$key] = $this->filter($expression, null, true);
        }
        if (!$cache[$key]['values']) return 0;

        arsort($cache[$key]['values']);
        $retval = '';
        $cnt = 0;
        foreach($cache[$key]['values'] as $name => $value) {
            $retval .= $value;
            if ($num == 1) break;
            $retval .= ' '.$name.LF;
            if (++$cnt >= $num) break;
        }
        return $retval;
    }

    /**
     * @param $expression
     * @param int $num
     * @return string
     */
    public function min($expression, $num = 1) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || !$cache[$key]['infos']) {
            $cache[$key] = $this->filter($expression, null, true);
        }
        if (!$cache[$key]['values']) return 0;

        asort($cache[$key]['values']);

        $retval = '';
        $cnt = 0;
        foreach($cache[$key]['values'] as $name => $value) {
            $retval .= $value;
            if ($num == 1) break;
            $retval .= ' '.$name.LF;
            if (++$cnt >= $num) break;
        }
        return $retval;
    }

    /**
     * @param $expression
     * @return number
     */
    public function sum($expression) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || !$cache[$key]['infos']) {
            $cache[$key] = $this->filter($expression, null, true);
        }
        if (!$cache[$key]['values']) return 0;

        return array_sum($cache[$key]['values']);
    }

    /**
     * @param $expression
     * @return float
     */
    public function median($expression) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || !$cache[$key]['infos']) {
            $cache[$key] = $this->filter($expression, null, true);
        }
        if (!$cache[$key]['values']) return 0;

        $array = array_values($cache[$key]['values']);
        sort($array);

        if (count($array) == 1) return $array[0];
        if (count($array) % 2 == 0) {
            $idx = (int) (count($array)/2 - 1);
            return ($array[$idx]+$array[$idx+1])/2;
        } else {
            return $array[(int)((count($array)-1)/2)];
        }
    }

    /**
     * Calculate percentage
     *
     * @param $amount
     * @param $total
     * @return int
     */
    public function percentage($amount, $total) {
        if(!$total) {
            return 0;
        }
        return (int) round($amount / $total * 100);
    }

}