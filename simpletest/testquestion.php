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
 * Unit tests for the pycode question definition class.
 *
 * @package    qtype
 * @subpackage pycode
 * @copyright  2011 Richard Lobb, University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/engine/simpletest/helpers.php');
require_once($CFG->dirroot . '/question/type/pycode/question.php');

$GLOBALS['SANDBOX'] = $SANDBOX;

/**
 * Unit tests for the matching question definition class.
 */
class qtype_pycode_question_test extends UnitTestCase {
    public function setUp() {
        $this->qtype = new qtype_pycode_question();
        $this->goodcode = "def sqr(n): return n * n";
    }


    public function tearDown() {
        $this->qtype = null;
    }


    public function test_get_question_summary() {
        $q = test_question_maker::make_question('pycode', 'sqr');
        $this->assertEqual('Write a function sqr(n) that returns n squared',
                $q->get_question_summary());
    }


    public function test_summarise_response() {
        $s = $this->goodcode;
        $q = test_question_maker::make_question('pycode', 'sqr');
        $this->assertEqual($s,
               $q->summarise_response(array('answer' => $s)));
    }


    public function test_sandbox() {
        $testlist = array(array('double(1)', '2'));
        $code = 'def double(n): return 2 * n';
        $testsetjson = json_encode(array($code, $testlist));
    	$testsetencoded = base64_encode($testsetjson);
        $cmd = "{$GLOBALS['SANDBOX']} \"$testsetencoded\"";
        $lines = array();
        try {
            exec($cmd, $lines);
    	}
    	catch (Exception $e) {
            $err = $e->getMessage();
            debugging("Exception $err on calling sandboxed pypy");
    	}
        //var_dump($lines);
        $this->assertEqual($lines[0], 'Yes');
        $this->assertEqual(decodeHex($lines[1]), "2\n");
    }


    public function test_grade_response_right() {
        $q = test_question_maker::make_question('pycode', 'sqr');
        $response = array('answer' => $this->goodcode);
        $result = $q->grade_response($response);
        $this->assertEqual($result[0], 1);
        $this->assertEqual($result[1], question_state::$gradedright);
        $this->assertTrue(isset($result[2]['_testresults']));
        $testResults = unserialize($result[2]['_testresults']);

        foreach ($testResults as $tr) {
            $this->assertTrue($tr->isCorrect);
        }
    }


    public function test_grade_response_wrong_ans() {
        $q = test_question_maker::make_question('pycode', 'sqr');
        $code = "def sqr(x): return x * x * x / abs(x)";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        $this->assertEqual($result[0], 0);
        $this->assertEqual($result[1], question_state::$gradedwrong);
        $this->assertTrue(isset($result[2]['_testresults']));
    }


    public function test_grade_syntax_error() {
        $q = test_question_maker::make_question('pycode', 'sqr');
        $code = "def sqr(x): return x  x";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        $this->assertEqual($result[0], 0);
        $this->assertEqual($result[1], question_state::$gradedwrong);
        $this->assertTrue(isset($result[2]['_testresults']));
        $testResults = unserialize($result[2]['_testresults']);
        $this->assertEqual(count($testResults), 1);
        $this->assertFalse($testResults[0]->isCorrect);
    }


    public function test_grade_runtime_error() {
        $q = test_question_maker::make_question('pycode', 'sqr');
        $code = "def sqr(x): return x * y";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        $this->assertEqual($result[0], 0);
        $this->assertEqual($result[1], question_state::$gradedwrong);
        $this->assertTrue(isset($result[2]['_testresults']));
        $testResults = unserialize($result[2]['_testresults']);
        $this->assertEqual(count($testResults), 1);
        $this->assertFalse($testResults[0]->isCorrect);
    }


    public function test_grade_delayed_runtime_error() {
        $q = test_question_maker::make_question('pycode', 'sqr');
        $code = "def sqr(x):\n  if x != 11:\n    return x * x\n  else:\n    return y";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        $this->assertEqual($result[0], 0);
        $this->assertEqual($result[1], question_state::$gradedwrong);
        $this->assertTrue(isset($result[2]['_testresults']));
        $testResults = unserialize($result[2]['_testresults']);
        $this->assertEqual(count($testResults), 3);
        $this->assertFalse($testResults[2]->isCorrect);
    }


    public function test_triple_quotes() {
        $q = test_question_maker::make_question('pycode', 'sqr');
        $code = <<<EOCODE
def sqr(x):
    """This is a function
       that squares its parameter"""
    return x * x
EOCODE;
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        $this->assertEqual($result[0], 1);
        $this->assertEqual($result[1], question_state::$gradedright);
        $this->assertTrue(isset($result[2]['_testresults']));
        $testResults = unserialize($result[2]['_testresults']);
        $n = count($testResults);
        $i = 0;
        foreach ($testResults as $tr) {
            $i++;
            $this->assertTrue($tr->isCorrect);
        }
    }


    public function test_helloFunc() {
        // Check a question type with a function that prints output
        $q = test_question_maker::make_question('pycode', 'helloFunc');
        $code = "def sayHello(name):\n  print 'Hello ' + name";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        $this->assertEqual($result[0], 1);
        $this->assertEqual($result[1], question_state::$gradedright);
        $this->assertTrue(isset($result[2]['_testresults']));
        $testResults = unserialize($result[2]['_testresults']);
        $this->assertEqual(count($testResults), 4);
    }


    public function test_copyStdin() {
        // Check a question that reads stdin and writes to stdout
        $q = test_question_maker::make_question('pycode', 'copyStdin');
        $code = <<<EOCODE
def copyStdin(n):
  for i in range(n):
    line = raw_input()
    print line
EOCODE;
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        $this->assertEqual($result[0], 0);
        $this->assertEqual($result[1], question_state::$gradedwrong);
        $this->assertTrue(isset($result[2]['_testresults']));
        $testResults = unserialize($result[2]['_testresults']);
        $this->assertEqual(count($testResults), 4);
        $this->assertTrue($testResults[0]->isCorrect);
        $this->assertTrue($testResults[1]->isCorrect);
        $this->assertTrue($testResults[2]->isCorrect);
        $this->assertFalse($testResults[3]->isCorrect);
     }

     public function test_timeout() {
         // Check a question that loops forever. Should cause sandbox timeout
        $q = test_question_maker::make_question('pycode', 'timeout');
        $code = "def timeout():\n  while (1):\n    pass";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        $this->assertEqual($result[0], 0);
        $this->assertEqual($result[1], question_state::$gradedwrong);
        $this->assertTrue(isset($result[2]['_testresults']));
        $testResults = unserialize($result[2]['_testresults']);
        $this->assertEqual(count($testResults), 1);
        $this->assertFalse($testResults[0]->isCorrect);
        // Need to accommodate different error messages from the 2 sandboxes
        $this->assertTrue($testResults[0]->output == 'SIGTERM (timeout or too much memory?)'
                or $testResults[0]->output == "***Time Limit Exceeded***");
     }

     public function test_exceptions() {
         // Check a function that conditionally throws exceptions
        $q = test_question_maker::make_question('pycode', 'exceptions');
        $code = "def checkOdd(n):\n  if n & 1:\n    raise ValueError()";
        $response = array('answer' => $code);
        $result = $q->grade_response($response);
        $this->assertEqual($result[0], 1);
        $this->assertEqual($result[1], question_state::$gradedright);
        // Rechecking the outputs is redundant but makes debugging easier
        $this->assertTrue(isset($result[2]['_testresults']));
        $testResults = unserialize($result[2]['_testresults']);
        $this->assertEqual(count($testResults), 2);
        $this->assertEqual(trim($testResults[0]->output), 'Exception');
        $this->assertEqual($testResults[1]->output, "Yes\nYes\nNo\nNo\nYes\nNo\n");
     }
}

