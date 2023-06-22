<?php

function optimitize_pdf($filename) {

    if (!file_exists($filename)) {
        return false;
    }

    $temp_output = substr($filename,0,-4) . '-temporary.pdf';
    $fin_output = substr($filename,0,-4) . '-optimized.pdf';

    exec('ps2pdf -dCompatibilityLevel=1.5 -dPrinted=false -dNEWPDF=false "' . 
        $filename . '" "' . $temp_output . '" && qpdf "' . $temp_output . '" --linearize "' .
        $fin_output . '" && rm "' . $temp_output . '"', $o, $e);

    if ($e != 0) {
        return false;
    }

    if (!file_exists($fin_output)) {
        return false;
    }

    return $fin_output;

}
