<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * pycode question definition classes.
 *
 * @package    qtype
 * @subpackage pycode
 * @copyright  Richard Lobb, 2011, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

$SANDBOX = "/usr/local/pypy-sandbox-4-pycode/pypy/translator/sandbox/pycodeTest.py";
//$SANDBOX = "/usr/local/sandbox/python3/pycodeTest.py";

$GLOBALS['SANDBOX'] = $SANDBOX; // So it works in any context

require_once($CFG->dirroot . '/question/type/pycode/progcode/question.php');

/**
 * Represents a Python 'pycode' question.
 */
class qtype_pycode_question extends qtype_progcode_question {

    // Check the correctness of a student's Python code given the
    // response and and a set of testCases.
    // Return value is an array of test-result objects, each have just an
    // isCorrect field and an output field (the actual output).
    // If an error occurs, all further tests are aborted so the returned array may be shorter
    // than the input array
    protected function run_tests($code, $testcases) {
        global $SANDBOX;
    	$testlist = array();
    	$hidden = array();
        foreach($testcases as $testcase) {
            if (isset($testcase->stdin)) {
                $testlist[] = array($testcase->testcode, $testcase->stdin, $testcase->output);
            }
            else {
                $testlist[] = array($testcase->testcode, $testcase->output);
            }
        }

        $testsetjson = json_encode(array($code, $testlist));
    	$testsetencoded = base64_encode($testsetjson);
        $cmd = "{$GLOBALS['SANDBOX']} \"$testsetencoded\"";
    	$lines = array();

    	try {
            exec("$cmd", $lines);
    	}
    	catch (Exception $e) {
            $err = $e->getMessage();
            debugging("Exception $err on calling sandboxed pypy");
    	}

        while (count($lines) > 0 && substr($lines[0], 0, 1) == '[') {
            // Filter out any error messages from the sandbox, such as
            // [sandlib: timeout]
            array_shift($lines);
        }

    	if (count($lines) == 0) {
            $lines = array('Tester failed', '*** SYSTEM ERROR ***');
    	}

    	$testResults = array();

    	for ($i = 0; $i < count($lines) - 1; $i += 2) {
            $outcome = $lines[$i];
            $output = decodeHex($lines[$i + 1]);
            $testresult = new stdClass;
            $testresult->isCorrect = $lines[$i] == 'Yes';
            $testresult->output = $output;
            $testResults[] = $testresult;
    	}

    	return $testResults;
    }

}

// *** Utility functions ***
function decodeHex($hex){
    $string='';
    for ($i=0; $i < strlen($hex)-1; $i+=2){
        $string .= chr(hexdec($hex[$i].$hex[$i+1]));
    }
    return $string;
}