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
 * @package    moodlecore
 * @subpackage backup-moodle2
 * @copyright  &copy; 2010 Richard Lobb
 * @author     Richard Lobb richard.lobb@canterbury.ac.nz
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

// TODO: test Version 2.1 backup/restore

/**
 * Provides the information to backup progcode questions.
 */
abstract class backup_qtype_progcode_plugin extends backup_qtype_plugin {
    
    // Subclasses mplement this method to define the question type, e.g. pycode, ccode.
    abstract protected function qtype();
	
   /**
    * Add the testcases to a progcode question backup structure. 
    * @param nested_backup_element $element The backup structure for the base question
    */
    protected function add_question_progcode_testcases($element) {
        // Check $element is one nested_backup_element
        $qtype = $this->qtype();
        if (! $element instanceof backup_nested_element) {
            throw new backup_step_exception("question_{$qtype}_testcases_bad_parent_element", $element);
        }

        // Define the elements
        $testcases = new backup_nested_element('testcases');
        $testcase = new backup_nested_element('testcase', array('id'), array(
            'testcode', 'output', 'useasexample', 'display', 'hiderestiffail', 'stdin'));

        // Build the tree
        $element->add_child($testcases);
        $testcases->add_child($testcase);

        // Set the sources
        $testcase->set_source_table("question_{$qtype}_testcases", array('questionid' => backup::VAR_PARENTID));

        // TODO Find out what the next line means
        // don't need to annotate ids nor files
    }

    /**
     * Returns the qtype information to attach to question element
     */
    protected function define_question_plugin_structure() {

        // Define the virtual plugin element with the condition to fulfill
        $plugin = $this->get_plugin_element(null, '../../qtype', $this->qtype());

        // Create one standard named plugin element (the visible container)
        $pluginwrapper = new backup_nested_element($this->get_recommended_name());

        // connect the visible container ASAP
        $plugin->add_child($pluginwrapper);

        // Add in the testcases
        $this->add_question_progcode_testcases($pluginwrapper);

        // don't need to annotate ids nor files
        // TODO Check what the above line means

        return $plugin;
    }
}
