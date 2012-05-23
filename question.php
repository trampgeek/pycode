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

/*
 * Useful SQL to see what's happening in the DB during development.
 * Written here simply because I wanted to write it down somewhere and why
 * not here.

select q.id as qid, qa.id as qaid, qas.id as qasid, state, qasd.name, value
from mdl_question as q, 
          mdl_question_attempts as qa,
          mdl_question_attempt_steps as qas,
          mdl_question_attempt_step_data as qasd
where qa.questionid = q.id
and    qas.questionattemptid = qa.id
and    qasd.attemptstepid = qas.id
order by q.id, qa.id, qas.id, qasd.id

 */


defined('MOODLE_INTERNAL') || die();

$FUNC_MIN_LENGTH = 20;
$SANDBOX = "/usr/local/pypy-sandbox-4-pycode/pypy/translator/sandbox/pycodeTest.py";

$GLOBALS['SANDBOX'] = $SANDBOX; // So it works in any context

require_once($CFG->dirroot . '/question/behaviour/adaptive/behaviour.php');
require_once($CFG->dirroot . '/question/engine/questionattemptstep.php');
require_once($CFG->dirroot . '/question/behaviour/adaptive_adapted_for_pycode/behaviour.php');

/**
 * Represents a Python 'pycode' question.
 */
class qtype_pycode_question extends question_graded_automatically {
    
    public $testcases;    // Array of testcases
    
    /**
     * Override default behaviour so that we can use a specialised behaviour
     * that caches test results returned by the call to grade_response().
     *
     * @param question_attempt $qa the attempt we are creating an behaviour for.
     * @param string $preferredbehaviour the requested type of behaviour.
     * @return question_behaviour the new behaviour object.
     */
    public function make_behaviour(question_attempt $qa, $preferredbehaviour) {
        if ($preferredbehaviour == 'adaptive') {
            return  new qbehaviour_adaptive_adapted_for_pycode($qa, $preferredbehaviour);
        }
        else {
            return parent::make_behaviour($qa, $preferredbehaviour);
        }
    }
        

    public function get_expected_data() {
        return array('answer' => PARAM_RAW, 'rating' => PARAM_INT);
    }
    
    
    public function summarise_response(array $response) {
        if (isset($response['answer'])) {
            return $response['answer'];
        } else {
            return null;
        }
    }

    
    public function is_complete_response(array $response) {
        global $FUNC_MIN_LENGTH;
        return !empty($response['answer']) && strlen($response['answer']) > $FUNC_MIN_LENGTH;
    }
    
    
    /**
     * In situations where is_gradable_response() returns false, this method
     * should generate a description of what the problem is.
     * @return string the message.
     */
    public function get_validation_error(array $response) {
        return "Empty or nearly-empty function declaration";
    }

    
    public function is_same_response(array $prevresponse, array $newresponse) {
        if (!question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer')) {
            return false;
        }
        return true;
    }
    
    
    public function get_correct_response() {
        return $this->get_correct_answer();
    }
    
    
    public function get_correct_answer() {
        // Allow for the possibility in the future of providing a sample answer
        return isset($this->answer) ? array('answer' => $this->answer) : array();
    }
    
    
    // Grade the given 'response'.
    // This implementation assumes a modified behaviour that will accept a
    // third array element in its response, containing data to be cached and
    // served up again in the response on subsequent calls.
    // It will still work with an unmodified behaviour but will be very
    // inefficient as multiple regradings will occur.
    
    public function grade_response(array $response) {
        if (empty($response['_testresults'])) {
            // debugging('Running Python tests');
            $code = $response['answer'];
            $testResults = $this->run_tests($code, $this->testcases);
            $testResultsSerial = serialize($testResults);
        }
        else {
            $testResultsSerial = $response['_testresults'];
            $testResults = unserialize($testResultsSerial);
        }

        $dataToCache = array('_testresults' => $testResultsSerial);
        if ($response['rating'] > 0) {
            $dataToCache['rating'] = $response['rating'];
        }
        if ($this->count_errors($testResults) != 0) {
            return array(0, question_state::$gradedwrong, $dataToCache);
        }
        else {
            return array(1, question_state::$gradedright, $dataToCache);
        }
    }
    
    // Check the correctness of a student's Python code given the
    // response and and a set of testCases.
    // Return value is an array of test-result objects.
    // If an error occurs, all further tests are aborted so the returned array may be shorter
    // than the input array
    private function run_tests($code, $testcases) {
        global $SANDBOX;
    	$testlist = array();
    	$hidden = array();
        foreach($testcases as $testcase) {
            if (isset($testcase->stdin)) {
                $testlist[] = array($testcase->shellinput, $testcase->stdin, $testcase->output);
            }
            else {
                $testlist[] = array($testcase->shellinput, $testcase->output);
            }
            $hidden[] = $testcase->hidden;  // Store for easy access ($testcases is indexed by id not 0...n)
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
            $testresult->outcome = $lines[$i];
            $test = $testlist[$i / 2];
            $testresult->shellinput = $test[0];
            $testresult->expected = $test[count($test) - 1];
            $testresult->output = $output;
            $testresult->hidden = $hidden[$i / 2];
            $testresult->mark = $testresult->outcome == 'Yes' ? 1.0 : 0.0;
            $testResults[] = $testresult;
    	}

    	return $testResults;
    }
    
    
    // Count the number of errors in the given array of test results.
    // If $hiddenonly is true, count only the errors in the hidden tests
    private function count_errors($testResults, $hiddenonly = False) {
    	$cnt = 0;
    	foreach ($testResults as $test) {
            if ($test->outcome != 'Yes' && (!$hiddenonly || $test->hidden)) {
                $cnt++;
            }
    	}
    	return $cnt;
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