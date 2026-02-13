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

namespace filter_competvetsuivi;

use context_system;
use local_competvetsuivi\tests\competvetsuivi_tests;

/**
 * Tests for filter_competvetsuivi.
 *
 * @copyright 2020 CALL Learning <laurent@call-learning.fr>
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass  \filter_competvetsuivi\filter_test
 */
final class filter_test extends competvetsuivi_tests {
    public function setUp(): void {
        parent::setUp();

        $this->resetAfterTest(true);

        // Enable competvetsuivi filter at top level.
        filter_set_global_state('competvetsuivi', TEXTFILTER_ON);
    }

    /**
     * Test the filter display the graphs.
     * @covers ::filter_ucgraph
     */
    public function test_filter_ucgraph(): void {
        $this->resetAfterTest();

        $filter = new text_filter(context_system::instance(), ['originalformat' => FORMAT_HTML]);

        $input = '[competvetsuivi uename="UC51" matrix="MATRIXAAA" type="ucdetails"][/competvetsuivi]';
        $expected = '';

        $this->assertEquals($expected, $filter->filter($input, [
            'originalformat' => FORMAT_HTML,
        ]));

        $input = '[competvetsuivi uename="UC51" matrix="MATRIX1" type="ucdetails"][/competvetsuivi]';
        $this->assertStringContainsString(
            "Contribution details to competencies and knowledge for the UC51",
            $filter->filter($input, ['originalformat' => FORMAT_HTML])
        );
    }

    /**
     * Tests the filter doesn't break anything if there is an error in the markup.
     * @covers ::filter_ucgraph
     */
    public function test_filter_ucgraph_error(): void {
        $this->resetAfterTest();

        $filter = new text_filter(context_system::instance(), ['originalformat' => FORMAT_HTML]);

        $input = '[competvetsuivi uename="UC51" matrix="MATRIXAAA" type="ucdetails"' .
            'userid=<error>][/competvetsuivi]';
        $expected = "simplexml_load_string(): Entity: line 1: parser error : attributes construct error";

        $this->assertEquals($expected, $filter->filter($input, [
            'originalformat' => FORMAT_HTML,
        ]));
    }

    /**
     * Tests the filter display multiple graphs in one.
     * @covers ::filter_ucgraph
     */
    public function test_filter_ucgraph_multiple(): void {
        $this->resetAfterTest();

        $filter = new text_filter(context_system::instance(), ['originalformat' => FORMAT_HTML]);

        $input = '[competvetsuivi uename="UC51" matrix="MATRIX1" type="ucdetails"][/competvetsuivi]
        [competvetsuivi uename="UC51" matrix="MATRIX1" type="ucsummary"][/competvetsuivi]';
        $result = $filter->filter($input, [
            'originalformat' => FORMAT_HTML,
        ]);
        $this->assertStringContainsString(
            "Contribution details to competencies and knowledge for the UC51",
            $result
        );
    }

    /**
     * Test the filter does not break if content is mixed form.
     * @covers ::filter_ucgraph
     */
    public function test_filter_ucgraph_mixedcontent(): void {
        $this->resetAfterTest();

        $filter = new text_filter(context_system::instance(), ['originalformat' => FORMAT_HTML]);

        $input = '<div><p>[competvetsuivi uename="UC51" matrix="MATRIX1" type="ucdetails"]AAAAAAA
        <br/>[/competvetsuivi][competvetsuivi uename="UC51" matrix="MATRIX1" type="ucsummary"][/competvetsuivi]</p></div>';
        $result = $filter->filter($input, [
            'originalformat' => FORMAT_HTML,
        ]);
        $this->assertStringContainsString(
            "ontribution details to competencies and knowledge for the UC51",
            $result
        );
    }
}
