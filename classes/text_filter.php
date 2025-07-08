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
 * Filter version and other meta-data are defined here.
 *
 * @package     filter_competvetsuivi
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace filter_competvetsuivi;

use context_system;
use core\exception\moodle_exception;
use Exception;
use local_competvetsuivi\matrix\matrix;
use local_competvetsuivi\ueutils;
use local_competvetsuivi\utils;
use moodle_text_filter;

/**
 * This filter is used to insert graph in labels or other filterable content
 *
 * @copyright   2019 CALL Learning <laurent@call-learning.fr>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class text_filter extends moodle_text_filter {
    /**
     * Allowed types of graphs
     */
    const GRAPH_TYPES = ['studentprogress', 'ucdetails', 'ucsummary'];

    /**
     * Returns the name of the filter
     *
     * @return string
     */
    public static function get_name() {
        return get_string('filtername', 'filter_competvetsuivi');
    }

    /**
     * The filtering function
     *
     * @param array $matches matches provided by preg_replace_callback
     * @return string the filtered content or empty if error
     */
    public static function filter_competvetsuivi_replacebygraph(array $matches): string {
        global $USER, $PAGE, $DB, $CFG;

        $text = "";
        $realxmltext = str_replace(["[", "]"], ["<", ">"], $matches[0]);
        try {
            $comptag = simplexml_load_string($realxmltext);
        } catch (Exception $e) {
            return $e->getMessage();
        }
        // Default values.
        $userid = $USER->id;
        $matrixid = 0;
        $uename = "";
        $graphtype = 'ucdetailsucdetails';

        $error = false; // Check if there is an error.

        foreach ($comptag->attributes() as $aname => $value) {
            $value = (string) $value;
            switch ($aname) {
                case 'type':
                    if (!in_array($value, self::GRAPH_TYPES)) {
                        $error = true;
                    } else {
                        $graphtype = $value;
                    }
                    break;
                case 'userid':
                    if ($value || is_numeric($value)) {
                        if ($USER->id != $value) {
                            if (has_capability('block/competvetsuivi:canseeother', context_system::instance(), $USER)) {
                                $userid = $value;
                                $matrixid = utils::get_matrixid_for_user($userid);
                            } else {
                                $error = true;
                            }
                        }
                    } else {
                        $error = true;
                    }
                    break;

                case 'uename':
                    $uename = $value;
                    break;

                case 'matrix':
                    if ($DB->record_exists(
                        \local_competvetsuivi\matrix\matrix::CLASS_TABLE, ['shortname' => $value])) {
                        $matrixid = $DB->get_field(
                            \local_competvetsuivi\matrix\matrix::CLASS_TABLE, 'id', ['shortname' => $value]);
                    } else {
                        $error = true;
                    }
                    break;
            }
        }

        if (!$matrixid) {
            $error = true;
        }

        if (!$error) {
            $matrix = new matrix($matrixid);
            $matrix->load_data();
            switch ($graphtype) {
                case 'studentprogress':
                    $user = \core_user::get_user($userid);
                    $userdata = \local_competvetsuivi\userdata::get_user_data($user->email);
                    $strandlist = [matrix::MATRIX_COMP_TYPE_KNOWLEDGE, matrix::MATRIX_COMP_TYPE_ABILITY];
                    $lastseenue = \local_competvetsuivi\userdata::get_user_last_ue_name($user->email);
                    $currentsemester = ueutils::get_current_semester_index($lastseenue, $matrix);
                    $compidparamname = \local_competvetsuivi\renderable\competency_progress_overview::PARAM_COMPID;
                    $currentcompid = optional_param($compidparamname, 0, PARAM_INT);
                    $currentcomp = null;
                    if ($currentcompid) {
                        $currentcomp = $matrix->comp[$currentcompid];
                    }

                    $progressoverview = new \local_competvetsuivi\renderable\competency_progress_overview(
                        $currentcomp,
                        $matrix,
                        $strandlist,
                        $userdata,
                        $currentsemester,
                        $user->id
                    );
                    $renderer = $PAGE->get_renderer('local_competvetsuivi');
                    $text = \html_writer::div($renderer->render($progressoverview), "container-fluid w-75");
                    break;
                case 'ucdetails':
                    try {
                        $ue = $matrix->get_matrix_ue_by_criteria('shortname', $uename);
                    } catch (moodle_exception $e) {
                        return $text;
                    }

                    $compidparamname = \local_competvetsuivi\renderable\uevscompetency_details::PARAM_COMPID;
                    $currentcompid = optional_param($compidparamname, 0, PARAM_INT);
                    $currentcomp = null;
                    if ($currentcompid) {
                        $currentcomp = $matrix->comp[$currentcompid];
                    }

                    $progressoverview = new \local_competvetsuivi\renderable\uevscompetency_details(
                        $matrix,
                        $ue->id,
                        $currentcomp
                    );

                    $renderer = $PAGE->get_renderer('local_competvetsuivi');
                    $text = \html_writer::div($renderer->render($progressoverview), "container-fluid w-75");
                    break;
                case 'ucsummary':
                    try {
                        $ue = $matrix->get_matrix_ue_by_criteria('shortname', $uename);
                    } catch (moodle_exception $e) {
                        return $text;
                    }
                    $compidparamname = \local_competvetsuivi\renderable\uevscompetency_details::PARAM_COMPID;
                    $currentcompid = optional_param($compidparamname, 0, PARAM_INT);
                    $currentcomp = null;
                    if ($currentcompid) {
                        $currentcomp = $matrix->comp[$currentcompid];
                    }

                    $progresspercent = new \local_competvetsuivi\renderable\uevscompetency_summary(
                        $matrix,
                        $ue->id,
                        $currentcomp
                    );
                    $renderer = $PAGE->get_renderer('local_competvetsuivi');
                    $detaillinkurl = new \moodle_url(
                        $CFG->wwwroot . '/local/competvetsuivi/pages/ucdetails.php',
                        ['returnurl' => qualified_me(), 'ueid' => $ue->id, 'matrixid' => $matrix->id]
                    );
                    $text = \html_writer::link($detaillinkurl,
                        \html_writer::div(
                            $renderer->render($progresspercent),
                            "container-fluid w-75"));
                    break;
            }
        }
        return $text;

    }

    /**
     * Usual filter function. Replace all [competvetsuivi ] tags by the respective graphs
     *
     * @param string $text
     * @param array $options
     * @return string|string[]|null
     */
    public function filter($text, array $options = []) {
        if (!is_string($text) || empty($text)) {
            // Non-string data can not be filtered anyway.
            return $text;
        }

        if (false === stripos($text, '[competvetsuivi')) {
            // Performance shortcut - if there is no starting tag, nothing can match.
            return $text;
        }

        $text = preg_replace_callback(
            '/(\[competvetsuivi[^]]*\][^[]*\[\/competvetsuivi\])/',
            self::class . '::filter_competvetsuivi_replacebygraph',
            $text);
        return $text;
    }
}

