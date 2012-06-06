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
 * Strings for component 'qtype_pycode', language 'en', branch 'MOODLE_20_STABLE'
 *
 * @package   qtype_pycode
 * @copyright 1999 onwards Martin Dougiamas  {@link http://moodle.com}
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['pycode'] = 'Python code';
$string['pycodesummary'] = 'Answer is Python code that is executed in the context of a set of test cases to determine its correctness.';
$string['pycode_help'] = 'In response to a question, which is a specification for a Python function or program, the respondent enters Python source code that satisfies the specification.';
$string['pycode_link'] = 'question/type/pycode';
$string['editingpycode'] = 'Editing a Python Code Question';
$string['addingpycode'] = 'Adding a new Python Code Question';
$string['xmlpycodeformaterror'] = 'XML format error in pycode question';

// The rest of the strings belong to progcode, but because it's not a component
// itself (it's the superclass of pycode and ccode) it can't be placed in a
// lang folder of its own. This design is a bit broken, but Moodle doesn't
// support abstract question types in a clean manner.

$string['aborted'] = 'Testing was aborted due to error.';
$string['allok'] = 'Passed all tests! ';
$string['atleastonetest'] = 'You must provide at least one test case for this question.';
$string['allornothing'] = 'Test code must be provided either for all testcases or for none.';
$string['testcode'] = 'Test code';
$string['stdin'] = 'Standard Input (only for programs that explicitly read stdin)';
$string['failedhidden'] = 'Your code failed one or more hidden tests.';
$string['filloutoneanswer'] = 'You must enter source code that satisfies the specification. The code you enter will be executed by an interpreter to determine its correctness and a grade awarded accordingly.';
$string['hidden'] = 'Hidden';
$string['missingoutput'] = 'You must supply the expected output from this test case.';
$string['noerrorsallowed'] = 'Your code must pass all tests to earn any marks. Try again.';
$string['qWrongBehaviour'] = 'Detailed test results unavailable. Perhaps question not using Adaptive Mode?';
$string['output'] = 'Output';
$string['pycode'] = 'Python Code';
$string['testcase'] = 'Test case.';
$string['useasexample'] = 'Use as example';
