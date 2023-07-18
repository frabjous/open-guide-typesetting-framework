<?php

$in = json_decode(file_get_contents('csljson.json'));

$out = new StdClass();

$out->types = $in->types;

$out->properties = new StdClass();

foreach ($in->properties as $key => $val) {
    $newval = '';
    if (isset($val->type)) {
        $newval = $val->type;
    }
    if (isset($val->items->{'$ref'}) && 
        mb_ereg_match('.*name-variable',$val->items->{'$ref'})) {
        $newval = "names";
    }
    if (isset($val->{'$ref'}) && ($val->{'$ref'} == 'dateparts')) {
        $newval = 'dateparts';
    }
    if (isset($val->{'$ref'}) && mb_ereg_match('.*date-variable',
        $val->{'$ref'})) {
        $newval = 'date';
    }
    if (is_array($newval)) {
        if (in_array("number", $newval)) {
            $newval = "number";
        } else {
            $newval = "string";
        }
    }
    $out->properties->{$key} = $newval;
}

echo json_encode($out, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE);
