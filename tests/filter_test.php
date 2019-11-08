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

/**
 * Tests for filter_multilang.
 *
 * @copyright 2019 The Open University
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class filter_competvetsuivi_filter_testcase extends advanced_testcase {
    public function setUp() {
        parent::setUp();

        $this->resetAfterTest(true);

        // Enable glossary filter at top level.
        filter_set_global_state('competvetsuivi', TEXTFILTER_ON);
    }


    /**
     * Tests the filter doesn't break anything if activated but no emoticons available.
     *
     */
    public function test_filter_ucgraph() {
        global $CFG;
        $this->resetAfterTest();
        // Empty the emoticons array.
        $CFG->emoticons = null;

        $filter = new filter_competvetsuivi(context_system::instance(), array('originalformat' => FORMAT_HTML));

        $input = '<competvetsuivi uename="UC51" matrix="MATRIX" wholecursus type="ucoverview"></competvetsuivi>';
        $expected = '';

        $this->assertEquals($expected, $filter->filter($input, [
                'originalformat' => FORMAT_HTML,
        ]));
    }
}