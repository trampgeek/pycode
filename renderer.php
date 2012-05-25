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
 * Multiple choice question renderer classes.
 *
 * @package    qtype
 * @subpackage pycode
 * @copyright  2009 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/pycode/progcode/renderer.php');


/**
 * Subclass for generating the bits of output specific to pycode questions.
 *
 * @copyright  Richard Lobb, University of Canterbury.
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pycode_renderer extends qtype_progcode_renderer {

    // Count the number of errors in the given array of test results.
    // If $hidden_only is true, count only the errors in the hidden tests
    protected function count_errors($testResults, $hiddenonly = False) {
        $cnt = 0;
        foreach ($testResults as $test) {
            if ($test->outcome != 'Yes' && (!$hiddenonly || $test->hidden)) {
                $cnt++;
            }
        }
        return $cnt;
    }

}
