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
 * Unit tests for the pycode question type class.
 *
 * @package    qtype
 * @subpackage pycode
 * @copyright  2011 Richard Lobb, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/engine/simpletest/helpers.php');


/**
 * Unit tests for the pycode question type class.
 * 
 * Just a few trivial sanity checks. If this fails, something's seriously broken.
 *
 * @copyright  2011 Richard Lobb, The University of Canterbury
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_pycode_test extends UnitTestCase {
    protected $qtype;

    public function setUp() {
        $this->qtype = new qtype_pycode();
    }

    public function tearDown() {
        $this->qtype = null;
    }

    protected function get_test_question_data() {
        $q = new stdClass();
        $q->id = 1;

        return $q;
    }

    public function test_name() {
        $this->assertEqual($this->qtype->name(), 'pycode');
    }


    public function test_get_random_guess_score() {
        $q = $this->get_test_question_data();
        $this->assertEqual(0, $this->qtype->get_random_guess_score($q));
    }

    public function test_get_possible_responses() {
        $q = $this->get_test_question_data();
        $this->assertEqual(array(), $this->qtype->get_possible_responses($q));
    }
}
