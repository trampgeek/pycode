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
///////////////////
/// pycode ///
///////////////////
/// PYCODE QUESTION TYPE CLASS //////////////////
// A pycode question consists of a specification for a simple
// python function (which mustn't do any I/O or system calls).
// The student's response must be Python source code that defines
// the specified function. The student's code is executed by
// a set of test cases, all of which must pass for the question
// to be marked correct. The code execution takes place in an
// external sandboxed Python interpreter (e.g. using pypy,
// see http://codespeak.net/pypy/dist/pypy/doc/sandbox.html).
// There are no part marks -- the question is marked 100% or
// zero. It is expected that each pycode question will have its
// own submit button and students will keep submitting until
// they pass all tests, so that their mark will be based on
// the number of submissions and the penalty per wrong
// submissions.

/**
 * @package 	qtype
 * @subpackage 	pycode
 * @copyright 	&copy; 2011 Richard Lobb
 * @author 	Richard Lobb richard.lobb@canterbury.ac.nz
 */

require_once($CFG->dirroot . '/question/type/pycode/progcode/questiontype.php');

/**
 * qtype_pcode extends the base qtype_progcode to
 * pycode-specific functionality.
 * A pycode question requires an additional DB table, question_pycode_testcases,
 * that contains the definitions for the testcases associated with a pycode
 * question. There are an arbitrary number of these, so they can't be handled
 * by adding columns to the pycode_question table.
 */
class qtype_pycode extends qtype_progcode {

    public function name() {
        return 'pycode';
    }
}

