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
 * Test helpers for the progcode question type.
 *
 * @package    qtype
 * @subpackage progcode
 * @copyright  2012 Richard Lobb, University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();


/**
 * Test helper class for the progcode question type.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_progcode_test_helper extends question_test_helper {
    public function get_test_questions() {
        return array('sqr', 'helloFunc', 'copyStdin', 'timeout', 'exceptions');
    }

    /**
     * Makes a progcode question asking for a sqr() function
     * @return qtype_progcode_question
     */
    public function make_progcode_question_sqr() {
        question_bank::load_question_definition_classes('progcode');
        $progcode = new qtype_progcode_question();
        test_question_maker::initialise_a_question($progcode);
        $progcode->name = 'Function to square a number n';
        $progcode->questiontext = 'Write a function int sqr(int n) that returns n squared.';
        $progcode->generalfeedback = 'No feedback available for progcode questions.';
        $progcode->testcases = array(
            (object) array('testcode'       => 'printf("%d", sqr(0))',
                           'output'         => '0',
                           'hidden'         => 0,
                           'useasexample'   => 1),
            (object) array('testcode'       => 'printf("%d", sqr(7))',
                           'output'         => '49',
                           'hidden'         => 0,
                           'useasexample'   => 1),
            (object) array('testcode'       => 'printf("%d", sqr(-11))',
                           'output'         => '121',
                           'hidden'         => 0,
                           'useasexample'   => 0),
           (object) array('testcode'       => 'printf("%d", sqr(-16))',
                           'output'         => '256',
                           'hidden'         => 1,
                           'useasexample'   => 0)
        );
        $progcode->qtype = question_bank::get_qtype('progcode');
        $progcode->unitgradingtype = 0;
        $progcode->unitpenalty = 0.2;
        return $progcode;
    }
    
    /**
     * Makes a progcode question to write a function that just print 'Hello <name>'
     * This test also tests multiline expressions.
     * @return qtype_progcode_question
     */
    public function make_progcode_question_helloProg() {
        question_bank::load_question_definition_classes('progcode');
        $progcode = new qtype_progcode_question();
        test_question_maker::initialise_a_question($progcode);
        $progcode->name = 'Program to print "Hello ENCN260"';
        $progcode->questiontext = 'Write a program that prints "Hello ENCN260"';
        $progcode->generalfeedback = 'No feedback available for progcode questions.';
        $progcode->testcases = array(
            (object) array('testcode' => '',
                          'output'    => 'Hello ENCN260 ',
                          'hidden'    => 0)
        );
        $progcode->qtype = question_bank::get_qtype('progcode');
        $progcode->unitgradingtype = 0;
        $progcode->unitpenalty = 0.2;
        return $progcode;
    }
    
    /**
     * Makes a progcode question to write a program that reads n lines of stdin
     * and writes them to stdout.
     * @return qtype_progcode_question
     */
    public function make_progcode_question_copyStdin() {
        question_bank::load_question_definition_classes('progcode');
        $progcode = new qtype_progcode_question();
        test_question_maker::initialise_a_question($progcode);
        $progcode->name = 'Function to copy n lines of stdin to stdout';
        $progcode->questiontext = 'Write a function copyLines(n) that reads stdin to stdout';
        $progcode->generalfeedback = 'No feedback available for progcode questions.';
        $progcode->testcases = array(
            (object) array('testcode' => '',
                          'stdin'     => '',
                          'output'    => '',
                          'hidden'    => 0),
            (object) array('testcode' => '',
                          'stdin'     => "Line1\n",
                          'output'    => "Line1\n",
                          'hidden'    => 0),
            (object) array('testcode' => '',
                          'stdin'     => "Line1\nLine2\n",
                          'output'    => "Line1\nLine2\n",
                          'hidden'    => 0)
        );
        $progcode->qtype = question_bank::get_qtype('progcode');
        $progcode->unitgradingtype = 0;
        $progcode->unitpenalty = 0.2;
        return $progcode;
    }
    
    /**
     * Makes a progcode question that loops forever, to test sandbox timeout.
     * @return qtype_progcode_question
     */
    public function make_progcode_question_timeout() {
        question_bank::load_question_definition_classes('progcode');
        $progcode = new qtype_progcode_question();
        test_question_maker::initialise_a_question($progcode);
        $progcode->name = 'Program to generate a timeout';
        $progcode->questiontext = 'Write a program that loops forever';
        $progcode->generalfeedback = 'No feedback available for progcode questions.';
        $progcode->testcases = array(
            (object) array('testcode' => '',
                          'stdin'     => '',
                          'output'    => '',
                          'hidden'    => 0)
        );
        $progcode->qtype = question_bank::get_qtype('progcode');
        $progcode->unitgradingtype = 0;
        $progcode->unitpenalty = 0.2;
        return $progcode;
    }   
}
