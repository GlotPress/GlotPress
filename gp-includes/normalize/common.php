<?php

function glotpress_extract_invariants($str)
{
    // Extract URLs.
    preg_match_all('/https?:\/\/[^\s"»]+/u', $str, $matches1);

    // Extract html tags.
    preg_match_all('/<[^>]+>/u', $str, $matches2);

    // Extract fprintf placeholders.
    preg_match_all('/%\d*\$[sdf]/u', $str, $matches3);

    return array_merge($matches1[0], $matches2[0], $matches3[0]);
}

function glotpress_insert_placeholder_invariants($str, $invariants)
{
    foreach ($invariants as $invariant) {
        $str = str_replace($invariant, 'INVARIANT'.md5($invariant), $str);
    }

    return $str;
}

function glotpress_replace_placeholder_invariants($str, $invariants)
{
    foreach ($invariants as $invariant) {
        $str = str_replace('INVARIANT'.md5($invariant), $invariant, $str);
    }

    return $str;
}
