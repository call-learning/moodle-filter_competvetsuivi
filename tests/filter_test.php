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
 * Unit tests.
 *
 * @package     filter_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/filter/competvetsuivi/filter.php');
require_once($CFG->dirroot . '/local/competvetsuivi/tests/lib.php');

/**
 * Tests for filter_multilang.
 *
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_competvetsuivi_filter_testcase extends competvetsuivi_tests {
    public function setUp() {
        parent::setUp();

        $this->resetAfterTest(true);

        // Enable competvetsuivi filter at top level.
        filter_set_global_state('competvetsuivi', TEXTFILTER_ON);
    }

    /**
     * Test the filter display the graphs.
     *
     */
    public function test_filter_ucgraph() {
        $this->resetAfterTest();

        $filter = new filter_competvetsuivi(context_system::instance(), array('originalformat' => FORMAT_HTML));

        $input = '[competvetsuivi uename="UC51" matrix="MATRIXAAA" type="ucdetails"][/competvetsuivi]';
        $expected = '';

        $this->assertEquals($expected, $filter->filter($input, [
            'originalformat' => FORMAT_HTML,
        ]));

        $input = '[competvetsuivi uename="UC51" matrix="MATRIX1" type="ucdetails"][/competvetsuivi]';
        $this->assertContains(
            "Percentage the UC51 contributes to competencies and knowledge for the <strong>whole cursus</strong>",
            $filter->filter($input, ['originalformat' => FORMAT_HTML]));
    }

    /**
     * Tests the filter doesn't break anything if there is an error in the markup.
     *
     */
    public function test_filter_ucgraph_error() {
        $this->resetAfterTest();

        $filter = new filter_competvetsuivi(context_system::instance(), array('originalformat' => FORMAT_HTML));

        $input = '[competvetsuivi uename="UC51" matrix="MATRIXAAA" type="ucdetails"' .
            'userid=<error>][/competvetsuivi]';
        $expected = "simplexml_load_string(): Entity: line 1: parser error : attributes construct error";

        $this->assertEquals($expected, $filter->filter($input, [
            'originalformat' => FORMAT_HTML,
        ]));
    }

    /**
     * Tests the filter display multiple graphs in one.
     *
     */
    public function test_filter_ucgraph_multiple() {
        $this->resetAfterTest();

        $filter = new filter_competvetsuivi(context_system::instance(), array('originalformat' => FORMAT_HTML));

        $input = '[competvetsuivi uename="UC51" matrix="MATRIX1" type="ucdetails"][/competvetsuivi]
        [competvetsuivi uename="UC51" matrix="MATRIX1" type="ucsummary"][/competvetsuivi]';
        $result = $filter->filter($input, [
            'originalformat' => FORMAT_HTML,
        ]);
        $this->assertContains(
            "Percentage the UC51 contributes to competencies and knowledge for the <strong>whole cursus</strong>",
            $result);
    }

    /**
     * Test the filter does not break if content is mixed form.
     *
     */
    public function test_filter_ucgraph_mixedcontent() {
        $this->resetAfterTest();

        $filter = new filter_competvetsuivi(context_system::instance(), array('originalformat' => FORMAT_HTML));

        $input = '<div><p>[competvetsuivi uename="UC51" matrix="MATRIX1" type="ucdetails"]AAAAAAA
        <br/>[/competvetsuivi][competvetsuivi uename="UC51" matrix="MATRIX1" type="ucsummary"][/competvetsuivi]</p></div>';
        $result = $filter->filter($input, [
            'originalformat' => FORMAT_HTML,
        ]);
        $this->assertContains(
            "Percentage the UC51 contributes to competencies and knowledge for the <strong>whole cursus</strong>",
            $result);
    }
}