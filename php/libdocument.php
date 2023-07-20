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
            return array(
                implode(PHP_EOL,
                    array_slice($lines, 0 , (($ln>0) ? ($ln-1) : 0))),
                implode(PHP_EOL,
                    array_map(
                        function($l) {
                            // remove asterisks
                            $s = mb_ereg_replace('\*','',$l);
                            // remove double quotes
                            $s = mb_ereg_replace('["“”]','',$s);
                            return $s;
                        },
                        array_values(array_filter(
                            array_slice($lines, $ln+1),
                            function ($l) {
                                return mb_ereg_match('.*[A-Z]', $l); 
                            }
                        ))
                    )
                )
            );
        }
    }
    return array($markdown, '');
}
