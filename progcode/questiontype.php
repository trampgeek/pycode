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
// 
///////////////////
/// progcode ///
///////////////////
/// PROGCODE QUESTION TYPE CLASS //////////////////
// The base class for programming code questions like pycode and ccode
// A progcode question consists of a specification for piece of program
// code, which might be a function or a complete program or (possibly in the
// future) a fragment of code. 
// The student's response must be source code that defines
// the specified function. The student's code is executed by
// a set of test cases, all of which must pass for the question
// to be marked correct. The code execution takes place in an external
// sandbox.
// There are no part marks -- the question is marked 100% or
// zero. It is expected that each progcode question will have its
// own submit button and students will keep submitting until
// they pass all tests, so that their mark will be based on
// the number of submissions and the penalty per wrong
// submissions.

/**
 * @package 	qtype
 * @subpackage 	progcode
 * @copyright 	&copy; 2012 Richard Lobb
 * @author 	Richard Lobb richard.lobb@canterbury.ac.nz
 */


/**
 * qtype_progcode extends the base question_type to progcode-specific functionality.
 * A progcode question requires an additional DB table
 * that contains the definitions for the testcases associated with a programming code
 * question. There are an arbitrary number of these, so they can't be handled
 * by adding columns to the standard question table.
 * Each subclass cas its own testcase database table.
 */
class qtype_progcode extends question_type {
    
    /**
     * Whether this question type can perform a frequency analysis of student
     * responses.
     *
     * If this method returns true, you must implement the get_possible_responses
     * method, and the question_definition class must implement the
     * classify_response method.
     *
     * @return bool whether this report can analyse all the student reponses
     * for things like the quiz statistics report.
     */
    public function can_analyse_responses() {
        return FALSE;  // TODO Consider if this functionality should be enabled
    }
    
    
    /**
     * Abstract function implemented by each question type. It runs all the code
     * required to set up and save a question of any type for testing purposes.
     * Alternate DB table prefix may be used to facilitate data deletion.
     */
    public function generate_test($name, $courseid=null) {
        // Closer inspection shows that this method isn't actually implemented
        // by even the standard question types and wouldn't be called for any
        // non-standard ones even if implemented. I'm leaving the stub in, in
        // case it's ever needed, but have set it to throw and exception, and
        // I've removed the actual test code
        // which would need to be different for each subclass anyway.
        throw new coding_exception('Unexpected call to generate_test. Read code for details.');
    }
    
    
    // Function to copy testcases from form fields into question->testcases
    private function copy_testcases_from_form(&$question) {
        $testcases = array();
        $numTests = count($question->testcode);
        assert(count($question->output) == $numTests);
        for($i = 0; $i < $numTests; $i++) {
            $input = filterCrs($question->testcode[$i]);
            $stdin = filterCrs($question->stdin[$i]);
            $output = filterCrs($question->output[$i]);
            if ($input == '' && $stdin == '' && $output == '') {
                continue;
            }
            $testcase = new stdClass;
            $testcase->questionid = isset($question->id) ? $question->id : 0;
            $testcase->testcode = $input;
            $testcase->stdin = $stdin;
            $testcase->output = $output;
            $testcase->useasexample = isset($question->useasexample[$i]);
            $testcase->display = $question->display[$i];
            $testcase->hiderestiffail = isset($question->hiderestiffail[$i]);
            $testcases[] = $testcase;
        }

        $question->testcases = $testcases;
    }

    // This override saves the set of testcases to the database
    // Note that the parameter isn't a question object, but the question form
    // (or a mock-up of it). See questiontypebase.php. 
    // Can't use the default get/save_question_options methods as there
    // are arbitrarily many testcases.

    public function save_question_options($question) {
        global $DB;

        if (!isset($question->testcases)) {
            $this->copy_testcases_from_form($question);
        }
        $q_type = $this->name();
        $table_name = "question_{$q_type}_testcases";

        if (!$oldtestcases = $DB->get_records($table_name,
                array('questionid' => $question->id), 'id ASC')) {
            $oldtestcases = array();
        }


        foreach ($question->testcases as $tc) {
            if (($oldtestcase = array_shift($oldtestcases))) { // Existing testcase, so reuse it
                $tc->id = $oldtestcase->id;
                $DB->update_record($table_name, $tc);
            } else {
                // A new testcase
                $tc->questionid = $question->id;
                $testcase->id = $DB->insert_record($table_name, $tc);
            }
        }


        // delete old testcase records
        foreach ($oldtestcases as $otc) {
            $DB->delete_records($table_name, array('id' => $otc->id));
        }

        return true;
    }

    // Load the question options (namely testcases) from the database
    // into the 'question' (which is actually a pycode/ccode/etc question edit form).
    public function get_question_options(&$question) {
        global $CFG, $DB, $OUTPUT;
        
        $q_type = $this->name();
        $table_name = "question_" . $this->name() . "_testcases";
        
        if (!$question->testcases = $DB->get_records_sql("
                    SELECT * 
                    FROM {" . $table_name ."}
                    WHERE questionid = ?", array($question->id))) {

            echo $OUTPUT->notification("Failed to load testcases from the table $table_name for question id {$question->id}");
            return false;
        }
        
        $question->stats = $this->get_question_stats($question->id);

        return true;
    }
    
    
    // The 'questiondata' here is actually (something like) a pycode/ccode/etc question
    // edit form, and we need to extend the baseclass method to copy the
    // testcases and stats across to the under-creation question definition.
    protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
        $question->testcases = $questiondata->testcases;
        $question->stats = $questiondata->stats;
    }

    
    // Delete the testcases when this question is deleted.
    public function delete_question($questionid, $contextid) {
        global $DB;
        $q_type = $this->name();
        $table_name = "question_{$q_type}_testcases";
        $success = $DB->delete_records($table_name, array('questionid' => $questionid));
        return $success && parent::delete_question($questionid, $contextid);
    }

      
    
    // Query the database to get the statistics of attempts and ratings for
    // a given question.
    private function get_question_stats($question_id) {
        global $DB;
        $attempts = $DB->get_records_sql("
            SELECT questionattemptid, fraction, rating, sequencenumber
            FROM mdl_question_attempts as qa,
                        mdl_question_attempt_steps as qas
            LEFT JOIN 
                ( SELECT value as rating, attemptstepid
                  FROM  mdl_question_attempt_step_data
                  WHERE name = 'rating'
                ) as qasd
            ON qasd.attemptstepid = qas.id
            WHERE questionattemptid = qa.id

            AND questionid = ?
            AND qas.sequencenumber =
                ( SELECT max(mdl_question_attempt_steps.sequencenumber)
                  FROM mdl_question_attempt_steps
                  WHERE mdl_question_attempt_steps.questionattemptid = qa.id
                )",
            array($question_id));
  
        $num_attempts = 0;
        $num_successes = 0;
        $counts = array(0, 0, 0, 0);
        $num_steps = 0;
        foreach ($attempts as $attempt) {
            $rating = isset($attempt->rating) ? $attempt->rating : 0;
            $counts[$rating]++;  
            if ($attempt->fraction > 0.0) {
                $num_successes++;
            }
            if ($attempt->sequencenumber > 0) {
                $num_attempts++;
                $num_steps += $attempt->sequencenumber;
            }
        }
        
        $success_percent = $num_attempts == 0 ? 0 : intval(100.0 * $num_successes / $num_attempts);
        $average_retries = $num_attempts == 0 ? 0 : $num_steps / $num_attempts;
        $stats = (object) array(
            'question_id' => $question_id,
            'attempts' => $num_attempts,
            'success_percent' => $success_percent,
            'average_retries' => $average_retries,
            'likes' => $counts[1],
            'neutrals' => $counts[2],
            'dislikes' => $counts[3]);
        //if ($stats->likes)   debugging(print_r($stats, TRUE));
        return $stats;
    }


    // TODO Override the default submit button so can hook in javascript to prevent
    // multiple clicking while a submission is being marked.
//    function print_question_submit_buttons(&$question, &$state, $cmoptions, $options) {
//        if (($cmoptions->optionflags & QUESTION_ADAPTIVE) and !$options->readonly) {
//            echo '<input type="submit" name="', $question->name_prefix, 'submit" value="',
//            get_string('mark', 'quiz'), '" class="submit btn" onclick="submitClicked(event)" />';
//        }
//    }


/// IMPORT/EXPORT FUNCTIONS /////////////////

    /*
     * Imports question from the Moodle XML format
     *
     * Overrides default since progcode questions contain a list of testcases,
     * not a list of answers.
     */
    function import_from_xml($data, $question, $format, $extra=null) {
        $question_type = $data['@']['type'];
        if ($question_type != $this->name()) {
            return false;
        }

        $qo = $format->import_headers($data);  // All the basic stuff
        $qo->qtype = $question_type;

        $testcases = $data['#']['testcases'][0]['#']['testcase'];

        $qo->testcases = array();

        foreach ($testcases as $testcase) {
            $tc = new stdClass;
            $tc->testcode = $testcase['#']['testcode'][0]['#']['text'][0]['#'];
            $tc->stdin = $testcase['#']['stdin'][0]['#']['text'][0]['#'];
            $tc->output = $testcase['#']['output'][0]['#']['text'][0]['#'];
            $tc->display = 'SHOW';
            if (isset($testcase['@']['hidden']) && $testcase['@']['hidden'] == "1") {
                $tc->display = 'HIDE';  // Handle old-style export too
            }
            if (isset($testcase['#']['display'])) {
                $tc->display = $testcase['#']['display'][0]['#']['text'][0]['#'];
            }
            if (isset($testcase['@']['hiderestiffail'] )) {
                $tc->hiderestiffail = $testcase['@']['hiderestiffail'] == "1" ? 1 : 0;
            }
            else {
                $tc->hiderestiffail = 0;
            }
            $tc->useasexample = $testcase['@']['useasexample'] == "1" ? 1 : 0;
            $qo->testcases[] = $tc;
        }
        return $qo;
    }

    /*
     * Export question to the Moodle XML format
     *
     * We override the default method because we don't have 'answers' but
     * testcases.
     */

    function export_to_xml($question, $format, $extra=null) {
        if ($extra !== null) {
            return false;
        }

        $expout = "    <testcases>\n";
        foreach ($question->testcases as $testcase) {
            $useasexample = $testcase->useasexample ? 1 : 0;
            $hiderestiffail = $testcase->hiderestiffail ? 1 : 0;
            $expout .= "      <testcase useasexample=\"$useasexample\" hiderestiffail=\"$hiderestiffail\">\n";
            $expout .= "        <testcode>\n";
            $expout .= $format->writetext($testcase->testcode, 4);
            $expout .= "        </testcode>\n";
            $expout .= "        <stdin>\n";
            $expout .= $format->writetext($testcase->stdin, 4);
            $expout .= "        </stdin>\n";            
            $expout .= "        <output>\n";
            $expout .= $format->writetext($testcase->output, 4);
            $expout .= "        </output>\n";
            $expout .= "        <display>\n";
            $expout .= $format->writetext($testcase->display, 4);
            $expout .= "    </testcase>\n";
        }
        $expout .= "    </testcases>\n";
        return $expout;
    }
}

// === Utility funcs

/** Remove all '\r' chars from $s and also trim trailing newlines */
function filterCrs($s) {
    $s = str_replace("\r", "", $s);
    while (substr($s, strlen($s) - 1, 1) == '\n') {
        $s = substr($s, 0, strlen($s) - 1);
    }
    return $s;
}