<?php
// LICENSE: GNU GPL v3 You should have received a copy of the GNU General
// Public License along with this program. If not, see
// https://www.gnu.org/licenses/.

///////////////////////// libdocument.php ///////////////////////////////
// functions for working with markdown documents                       //
/////////////////////////////////////////////////////////////////////////

function extract_bibliography($markdown) {
    $lines = explode(PHP_EOL, $markdown);
    $ln = count($lines);
    while ($ln > 0) {
        $ln--;
        $line = $lines[$ln];
        $condensed = mb_ereg_replace('[^a-z]','',strtolower($line));
        if (in_array($condensed, array('bibliography',
            'workscited','references','thebibliography'))) {
            $bibarray = array_values(array_filter(
                array_slice($lines, $ln+1),
                    function ($l) {
                        return mb_ereg_match('.*[A-Z]', $l); 
                    }
            ));
            $savedname = '';
            for ($i=0; $i<count($bibarray); $i++) {
                $l = $bibarray[$i];
                // remove asterisks
                $s = mb_ereg_replace('\*','',$l);
                // remove double quotes
                $s = mb_ereg_replace('["“”]','',$s);
                // remove leading hyphens
                $s = mb_ereg_replace('^-*\s*','',$s);
                // remove escaped hyphens
                $s = mb_ereg_replace('\\\-','',$s);
                // remove escaped single quotes
                $s = mb_ereg_replace("\\\'","'",$s);
                // remove escaped brackers
                $s = mb_ereg_replace('\\\\[\[]','[',$s);
                $s = mb_ereg_replace('\\\\[\]]',']',$s);
                // add name from previous check if starts with numeral
                if (mb_ereg_match('[0-9].*', $s)) {
                    if ($savedname != '') {
                        $s = $savedname . ' ' . $s;
                    }
                } else {
                    $savedname = trim(mb_ereg_replace('[0-9].*','',$s));
                }
                // swapout
                $bibarray[$i] = $s;
            }

            return array(
                implode(PHP_EOL,
                    array_slice($lines, 0 , (($ln>0) ? ($ln-1) : 0))),
                implode(PHP_EOL, $bibarray)
            );
        }
    }
    return array($markdown, '');
}

function fix_markdown($markdown, $metadata, $importreplacements,
    $splitsentences = false) {
    $lines = explode(PHP_EOL, $markdown);
    $outcome = '';
    $found_acknowledgements = false;
    for ($ln=0; $ln<count($lines); $ln++) {
        $line = $lines[$ln];
        // do import replacements
        foreach ($importreplacements as $regex => $repl) {
            $line = mb_ereg_replace($regex, $repl, $line);
        }
        // look for title, abstract, author, affiliation in first few lines
        // remove them (by skipping them with continue) if found
        if ($line != '' && (($ln <6) || (($ln+6)>count($lines)))) {
            if (mb_ereg_match('\*+Abstract', $line)) {
                continue;
            }
            if (mb_ereg_match('Abstract: ', $line)) {
                continue;
            }
            if ((isset($metadata->title)) &&
                (squish($metadata->title) == squish($line))) {
                continue;
            }
            if ((isset($metadata->author)) &&
                ((squish(join_authors($metadata->author,false,false)) == squish($line)) ||
                (squish(join_authors($metadata->author,false,true)) == squish($line)) ||
                (squish(join_authors($metadata->author,true,false)) == squish($line)) ||
                (squish(join_authors($metadata->author,true,true)) == squish($line)))) {
                continue;
            }
            if ((isset($metadata->author) &&
                (squish($metadata->author[0]->email) == squish($line)) ||
                (squish($metadata->author[0]->affiliation) == squish($line)))) {
                continue;
            }
        }

        if ((squish($line) == 'acknowledgements') || (squish($line) == 'thanks')) {
            $found_acknowledgements = true;
            // add blank line before if need be
            if (($ln > 0) && ($lines[$ln - 1] != '')) {
                $outcome .= PHP_EOL;
            }
            $outcome .= '## Acknowledgements {.unnumbered}' . PHP_EOL;
            // add a blank line afterwards if need be
            if (($ln < (count($lines) - 1)) && ($lines[$ln + 1] != '')) {
                $outcome .= PHP_EOL;
            }
            continue;
        }
        // pseudo sections/subsections by lazy people; i.e. bold
        // things without periods
        if (mb_ereg_match('\*\*\s*[0-9]+\.\s*[^\.]+[^\s]\s*\*\*$', $line)) {
            $fixedline =
                mb_ereg_replace('\*\*\s*[0-9]+\.\s*([^\.]+[^\s])\s*\*\*$', '# \1', $line);
            // add blank line before if need be
            if (($ln > 0) && ($lines[$ln - 1] != '')) {
                $outcome .= PHP_EOL;
            }
            $outcome .= $fixedline . PHP_EOL;
            // add a blank line afterwards if need be
            if (($ln < (count($lines) - 1)) && ($lines[$ln + 1] != '')) {
                $outcome .= PHP_EOL;
            }
            continue;
        } // the same but with italics for subsections?
        if (mb_ereg_match('\*\s*[0-9]+\.\s*[^\.]+[^\s]\s*\*$', $line)) {
            $fixedline =
                mb_ereg_replace('\*\s*[0-9]+\.\s*([^\.]+[^\s])\s*\*$', '## \1', $line);
            // add blank line before if need be
            if (($ln > 0) && ($lines[$ln - 1] != '')) {
                $outcome .= PHP_EOL;
            }
            $outcome .= $fixedline . PHP_EOL;
            // add a blank line afterwards if need be
            if (($ln < (count($lines) - 1)) && ($lines[$ln + 1] != '')) {
                $outcome .= PHP_EOL;
            }
            continue;
        }
        // pad around headers
        if (mb_ereg_match('#',$line)) {
            if (($ln > 0) && ($lines[$ln - 1] != '')) {
                $outcome .= PHP_EOL;
            }
            $outcome .= $line . PHP_EOL;
            // add a blank line afterwards if need be
            if (($ln < (count($lines) - 1)) && ($lines[$ln + 1] != '')) {
                $outcome .= PHP_EOL;
            }
            continue;
        }

        // regular paragraphs to split?
        if ($splitsentences && mb_ereg_match('[\(]?[A-Z]',$line)) {
            $outcome .= split_into_sentences($line) . PHP_EOL;
            continue;
        }
        // normal line, just push it to result
        $outcome .= $line . PHP_EOL;
    }
    if (!$found_acknowledgements) {
        // add blank line beforehand if need be
        if ($lines[ (count($lines) - 1) ] != '') {
            $outcome .= PHP_EOL;
        }
        $outcome .= '## Acknowledgements {.unnumbered}' . PHP_EOL . PHP_EOL;
    }
    return $outcome;
}

// note: join_authors is based on metadata name arrays
// and join_names is based on csl json name arrays
function join_authors($authors, $withemails = false, $withaffils = false) {
    $rv = '';
    foreach ($authors as $n => $authorinfo) {
        if ($n > 0) {
            if ($n < (count($authors) - 1)) {
                $rv .= ', ';
            } else {
                $rv .= ' and ';
            }
        }
        $rv .= $authorinfo->name;
        if ($withemails) {
            $rv .= ' ' . $authorinfo->email;
        }
        if ($withaffils) {
            $rv .= ' ' . $authorinfo->affiliation;
        }
    }
    return $rv;
}

function join_names($names) {
    $rv = '';
    foreach ($names as $n => $name) {
        if ($n > 0) {
            if ($n < (count($names) - 1)) {
                $rv .= ', ';
            } else {
                $rv .= ' and ';
            }
        }
        if (isset($name->given)) {
            $rv .= $name->given . ' ';
        }
        if (isset($name->{"non-dropping-particle"})) {
            $rv .= $name->{"non-dropping-particle"} . ' ';
        }
        if (isset($name->family)) {
            $rv .= $name->family;
        }
    }
    return $rv;
}

function split_into_sentences($line) {
    $exploded = explode(' ', $line);
    $rv = '';
    foreach ($exploded as $n => $word) {
        // just add last word; no space at end
        if ($n == (count($exploded) - 1)) {
            $rv .= $word;
            break;
        }
        // get rid of double spaces
        if ($word == '') { continue; }
        $nextword = $exploded[$n+1];
        // if next word starts with a capital and this
        // word ends with a lowercase letter and a punctuation
        // mark ending a sentence, it's a sentence break
        if (mb_ereg_match('[\*\(\'"]*[A-Z]', $nextword) &&
            mb_ereg_match('.*[0-9a-z]["\'\*\)]*[\.\?!]$', $word)) {
            $rv .= $word . PHP_EOL;
            continue;
        }
        // if next word starts with a capital and this one
        // ends with a footnote, it's a sentence break
        if (mb_ereg_match('[\*\(\'"]*[A-Z]', $nextword) &&
            mb_ereg_match('.*[\.\?!]\[\^[0-9]+\]', $word)) {
            $rv .= $word . PHP_EOL;
            continue;
        }
        // otherwise add back the word and a space
        $rv .= $word . ' ';
    }
    return $rv;
}

function squish($s) {
    return strtolower(mb_ereg_replace('[^A-Za-z]','',$s));
}
