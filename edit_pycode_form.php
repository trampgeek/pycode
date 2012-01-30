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

defined('MOODLE_INTERNAL') || die();

/**
 * Defines the editing form for the pycode question type.
 *
 * @package 	questionbank
 * @subpackage 	questiontypes
 * @copyright 	&copy; 2010 Richard Lobb
 * @author 		Richard Lobb richard.lobb@canterbury.ac.nz
 * @license 	http://www.gnu.org/copyleft/gpl.html GNU Public License
 */
define("NUM_TESTCASES_START", 5); // Num empty test cases with new questions
define("NUM_TESTCASES_ADD", 3);   // Extra empty test cases to add

/**
 * pycode editing form definition.
 */
class qtype_pycode_edit_form extends question_edit_form {

    /**shellinput
     * Add question-type specific form fields.
     *
     * @param MoodleQuickForm $mform the form being built.
     */
    var $_textarea_or_htmleditor_generalfb;   //addElement type for general feedback
    var $_editor_options_generalfb;           //in dependence of editor type set a different array for its options

    
    function definition_inner(&$mform) {
        // TODO: do I need the next 2 lines?
        $mform->addElement('static', 'answersinstruct');
        $mform->closeHeaderBefore('answersinstruct');
        $gradeoptions = array(); // Unused
        if (isset($this->question->testcases)) {
            $numTestcases = count($this->question->testcases) + NUM_TESTCASES_ADD;
        }
        else {
            $numTestcases = NUM_TESTCASES_START;
        }
        $this->add_per_answer_fields($mform, get_string('testcase', 'qtype_pycode'), $gradeoptions, $numTestcases);
        $this->add_interactive_settings();
    }
    
    
    /*
     *  Overridden so each 'answer' is a test case containing a Python shellinput to be evaluated
     * and a Python expected output value.
     */
    function get_per_answer_fields(&$mform, $label, $gradeoptions, &$repeatedoptions, &$answersoption) {
        $repeated = array();
        $repeated[] = & $mform->createElement('header', 'answerhdr', $label);
        $repeated[] = & $mform->createElement('textarea', 'shellinput',
                get_string('shellinput', 'qtype_pycode'),
                array('cols' => 80, 'rows' => 4, 'class' => 'testcaseexpression'));
        $repeated[] = & $mform->createElement('textarea', 'stdin',
                get_string('stdin', 'qtype_pycode'),
                array('cols' => 80, 'rows' => 4, 'class' => 'testcasestdin'));
        $repeated[] = & $mform->createElement('textarea', 'output',
                get_string('output', 'qtype_pycode'),
                array('cols' => 80, 'rows' => 4, 'class' => 'testcaseresult'));
        $repeated[] = & $mform->createElement('checkbox', 'useasexample', get_string('useasexample', 'qtype_pycode'), false);
        $repeated[] = & $mform->createElement('checkbox', 'hidden', get_string('hidden', 'qtype_pycode'), false);
        $repeatedoptions['output']['type'] = PARAM_RAW;
        $answersoption = '';  // Not actually using the options field to hold answers
        return $repeated;
    }

    
    // TODO: consider overriding this to remove unwanted form elements
    protected function definition() {
        parent::definition();
    }
    
    

    function data_preprocessing($question) {
        // Although it's not wildly obvious from the documentation, this method
        // needs to set up fields of the current question whose names match those
        // specified in get_per_answer_fields. These are used to load the
        // data into the form.
        if (isset($question->testcases)) { // Reloading a saved question?
            $question->shellinput = array();
            $question->output = array();
            $question->useasexample = array();
            $question->hidden = array();
            foreach ($question->testcases as $tc) {
                $question->shellinput[] = $tc->shellinput;
                $question->stdin[] = $tc->stdin;
                $question->output[] = $tc->output;
                $question->useasexample[] = $tc->useasexample;
                $question->hidden[] = $tc->hidden;
            }
        }
        return $question;
    }

    
    function validation($data, $files) {

        $errors = parent::validation($data, $files);
        $shellinputs = $data['shellinput'];
        $stdins = $data['stdin'];
        $outputs = $data['output'];
        $count = 0;
        $num = max(count($shellinputs), count($stdins), count($outputs));
        for ($i = 0; $i < $num; $i++) {
            $shellinput = trim($shellinputs[$i]);
            $stdin = trim($stdins[$i]);
            $output = trim($outputs[$i]);
            if ($shellinput !== '' || $stdin != '' || $output !== '') {
                $count++;
            }
        }
        
        if ($count == 0) {
            $errors["shellinput[0]"] = get_string('atleastonetest', 'qtype_pycode');
        }
        return $errors;
    }

    
    function qtype() {
        return 'pycode';
    }

}
