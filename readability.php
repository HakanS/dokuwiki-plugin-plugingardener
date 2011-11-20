<?php
/**
 * Readability Analysis Plugin for DokuWiki
 *
 * @author  Andreas Gohr <andi@splitbrain.org>
 * @author  Dave Child <dave@ilovejackdaniels.com>
 * @link    http://www.ilovejackdaniels.com/resources/readability-score/
 * @license GPL 2
 */

/**
 * Calculate the Gunning-Fog score
 *
 * @author  Dave Child <dave@ilovejackdaniels.com>
 */
function gunning_fog_score($text) {
    return ((average_words_sentence($text) +
             percentage_number_words_three_syllables($text)) * 0.4);
}

/**
 * Calculate the Flesch-Kinkaid reading ease score
 *
 * @author  Dave Child <dave@ilovejackdaniels.com>
 */
function calculate_flesch($text) {
    return (206.835 - (1.015 * average_words_sentence($text)) -
            (84.6 * average_syllables_word($text)));
}

/**
 * Calculate the Flesch-Kinkaid Grade level
 *
 * @author  Dave Child <dave@ilovejackdaniels.com>
 */
function calculate_flesch_grade($text) {
    return ((.39 * average_words_sentence($text)) +
            (11.8 * average_syllables_word($text)) - 15.59);
}

/**
 * Calculate the percentage of words with more than 3 syllables
 *
 * @author  Dave Child <dave@ilovejackdaniels.com>
 */
function percentage_number_words_three_syllables($text) {
    $syllables = 0;
    $words = explode(' ', $text);
    for ($i = 0; $i < count($words); $i++) {
        if (count_syllables($words[$i]) > 2) {
            $syllables ++;
        }
    }
    $score = number_format((($syllables / count($words)) * 100));

    return ($score);
}

/**
 * Calculate the ratio of words to sentences
 *
 * @author  Dave Child <dave@ilovejackdaniels.com>
 */
function average_words_sentence($text) {
    $sentences = strlen(preg_replace('/[^\.!?]/', '', $text));
    $words = strlen(preg_replace('/[^ ]/', '', $text));
    if($sentences == 0) $sentences = 1;
    return ($words/$sentences);
}

/**
 * Calculate the average number of syllables per word
 *
 * @author  Dave Child <dave@ilovejackdaniels.com>
 */
function average_syllables_word($text) {
    $words = explode(' ', $text);
    for ($i = 0; $i < count($words); $i++) {
        $syllables = $syllables + count_syllables($words[$i]);
    }
    return ($syllables/count($words));
}

/**
 * Count the number of syllables in the given word
 *
 * @author  Dave Child <dave@ilovejackdaniels.com>
 */
function count_syllables($word) {

    $subsyl = Array(
        'cial',
        'tia',
        'cius',
        'cious',
        'giu',
        'ion',
        'iou',
        'sia$',
        '.ely$'
    );

    $addsyl = Array(
        'ia',
        'riet',
        'dien',
        'iu',
        'io',
        'ii',
        '[aeiouym]bl$',
        '[aeiou]{3}',
        '^mc',
        'ism$',
        '([^aeiouy])\1l$',
        '[^l]lien',
        '^coa[dglx].',
        '[^gq]ua[^auieo]',
        'dnt$'
    );

    // Based on Greg Fast's Perl module Lingua::EN::Syllables
    $word = preg_replace('/[^a-z]/is', '', strtolower($word));
    $word_parts = preg_split('/[^aeiouy]+/', $word);
    foreach ($word_parts as $key => $value) {
        if ($value <> '') {
            $valid_word_parts[] = $value;
        }
    }

    $syllables = 0;
    foreach ($subsyl as $syl) {
        if (strpos($word, $syl) !== false) {
            $syllables--;
        }
    }
    foreach ($addsyl as $syl) {
        if (strpos($word, $syl) !== false) {
            $syllables++;
        }
    }
    if (strlen($word) == 1) {
        $syllables++;
    }
    $syllables += count($valid_word_parts);
    $syllables = ($syllables == 0) ? 1 : $syllables;
    return $syllables;
}


