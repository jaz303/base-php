<?php
// returns true iff $lambda($v) returns true for all values $v in $iterable
function all($iterable, $lambda) {
    foreach ($iterable as $v) {
        if (!$lambda($v)) return false;
    }
    return true;
}

// returns true iff $lambda($v) returns true for any value $v in $iterable
function any($iterable, $lambda) {
    foreach ($iterable as $v) {
        if ($lambda($v)) return true;
    }
    return false;
}

// call $lambda($v, $i) for every value $v in $iterable, with sequential index $i
function every($iterable, $lambda) {
    $c = 0;
    foreach ($iterable as $v) $lambda($v, $c++);
}

// call $lambda($k, $v, $i) for every key/value pair ($k, $v) in $iterable, with sequential index $i
function kevery($iterable, $lambda) {
    $c = 0;
    foreach ($iterable as $k => $v) $lambda($k, $v, $c++);
}

function map($iterable, $lambda) {
    $out = array();
    foreach ($iterable as $v) $out[] = $lambda($v);
    return $out;
}

function kmap($iterable, $lambda) {
    $out = array();
    foreach ($iterable as $k => $v) $out[$k] = $lambda($v);
}

function inject($iterable, $memo, $lambda) {
    foreach ($iterable as $v) $memo = $lambda($memo, $v);
    return $memo;
}

function kinject($iterable, $memo, $lambda) {
    foreach ($iterable as $k => $v) $memo = $lambda($memo, $k, $v);
    return $memo;
}

// filters $iterable, returning only those values for which $lambda($v) is true
function filter($iterable, $lambda) {
    $out = array();
    foreach ($iterable as $v) if ($lambda($v)) $out[] = $v;
    return $out;
}

// as filter(), but preserves keys
function kfilter($iterable, $lambda) {
    $out = array();
    foreach ($iterable as $k => $v) if ($lambda($v)) $out[$k] = $v;
    return $out;
}

// filters $iterable, removing those values for which $lambda($v) is true
function reject($iterable, $lambda) {
    $out = array();
    foreach ($iterable as $v) if (!$lambda($v)) $out[] = $v;
    return $out;
}

// as reject(), but preserves keys
function kreject($iterable, $lambda) {
    $out = array();
    foreach ($iterable as $k => $v) if (!$lambda($v)) $out[$k] = $v;
    return $out;
}
?>