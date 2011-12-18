<?php

if(!defined('LF')) define('LF',"\n");

class pg_stats {

    public $total = 0;
    private $info = null;
    private $collections = null;
    private $cache = array();

    function __construct(&$info, &$collections) {
        $this->info = &$info;
        $this->total = count($info);
        $this->collections = &$collections;
    }

    private function checkDevError($info, $plugin, $dev_error_msg) {
        if (in_array(strtolower($info['developer']), $this->collections['trackedDevelopers'])) {
            if (!$this->collections['trackedDevErr'][$info['developer']][$dev_error_msg] || !in_array($plugin, $this->collections['trackedDevErr'][$info['developer']][$dev_error_msg])) {
                $this->collections['trackedDevErr'][$info['developer']][$dev_error_msg][] = $plugin;
            }
        }
    }

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

    function wiki_link($plugin) {
        return "[[plugin:$plugin]]";
    }

    function infos($expression, $dev_error_msg = null) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || !$cache[$key]['infos'] || $dev_error_msg) {
            $cache[$key] = $this->filter($expression, $dev_error_msg, true);
        }
        return $cache[$key]['infos'];
    }

    function count($expression, $dev_error_msg = null) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || $dev_error_msg) {
            $cache[$key] = $this->filter($expression, $dev_error_msg);
        }
        return $cache[$key]['cnt'];
    }

    /*
     *  returns "X plugins (Y%)"
     */
    function cnt($expression, $format = null, $dev_error_msg = null) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || $dev_error_msg) {
            $cache[$key] = $this->filter($expression, $dev_error_msg);
        }
        if ($format) {
            return sprintf($format, (string)$cache[$key]['cnt'], '('.round($cache[$key]['cnt']/$this->total*100).'%)');
        } else {
            return $cache[$key]['cnt'].' plugins ('.round($cache[$key]['cnt']/$this->total*100).'%)';
        }
    }

    /*
     *  returns list of links
     */
    function plugins($expression, $dev_error_msg = null) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || $dev_error_msg) {
            $cache[$key] = $this->filter($expression, $dev_error_msg);
        }
        return $this->plugins_from_array($cache[$key]['plugins']);
    }

    function plugins_from_array($plugins, $linksonly = false) {
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

    /*
     *  returns pivot table WITHOUT table header
     */
    function pivot($expression, $showpercent = false, $sortlinkcount = false, $sortdesc = false, $num = null, $showlinks = false) {
        $result = array();
        $func = create_function('$info' , "return ($expression);");
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
        foreach($result as $key => $links) {
            $retval .= '|  '.$key.'  |  ';
            $retval .= count($links);
            if ($showpercent) {
                $retval .= ' ('.round(count($links)/$this->total*100).'%)';
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
        $retval .= '  '.count($single).' rows ('.round(count($single)/count($result)*100).'%) contain only one plugin.'.LF;
        $retval .= '-----------------------------------------------------------------------------'.LF;
        return $retval;
    }

    function max($expression, $num = 1) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || !$cache[$key]['infos']) {
            $cache[$key] = $this->filter($expression, null, true);
        }
        if (!$cache[$key]['values']) return;

        arsort($cache[$key]['values']);
        foreach($cache[$key]['values'] as $name => $value) {
            $retval .= $value;
            if ($num == 1) break;
            $retval .= ' '.$name.LF;
            if (++$cnt >= $num) break;
        }
        return $retval;
    }

    function min($expression, $num = 1) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || !$cache[$key]['infos']) {
            $cache[$key] = $this->filter($expression, null, true);
        }
        if (!$cache[$key]['values']) return;

        asort($cache[$key]['values']);
        foreach($cache[$key]['values'] as $name => $value) {
            $retval .= $value;
            if ($num == 1) break;
            $retval .= ' '.$name.LF;
            if (++$cnt >= $num) break;
        }
        return $retval;
    }

    function sum($expression) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || !$cache[$key]['infos']) {
            $cache[$key] = $this->filter($expression, null, true);
        }
        if (!$cache[$key]['values']) return;

        return array_sum($cache[$key]['values']);
    }

    function median($expression) {
        $key = hsc(str_replace(' ','',$expression));
        if (!$cache[$key] || !$cache[$key]['infos']) {
            $cache[$key] = $this->filter($expression, null, true);
        }
        if (!$cache[$key]['values']) return;

        $array = array_values($cache[$key]['values']);
        sort($array);

        if (count($array) == 1) return $array[0];
        if (count($array) % 2 == 0) {
            $idx = count($array)/2 - 1;
            return ($array[$idx]+$array[$idx+1])/2;
        } else {
            return $array[(count($array)-1)/2];
        }
    }

}