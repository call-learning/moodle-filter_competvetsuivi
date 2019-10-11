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

defined('MOODLE_INTERNAL') || die();
use local_competvetsuivi\ueutils;
use local_competvetsuivi\utils;
use local_competvetsuivi\matrix\matrix;

class filter_competvetsuivi extends moodle_text_filter {

    public function setup($page, $context) {
        if ($page->requires->should_create_one_time_item_now('filter_competvetsuivi')) {
            // TODO remove this function if deemed not necessary
        }
    }


    public function filter($text, array $options = array()) {
        global $USER;
        if (!is_string($text) or empty($text)) {
            // Non-string data can not be filtered anyway.
            return $text;
        }

        if (stripos($text, '<competvetsuivi') === false) {
            // Performance shortcut - if there is no </a> tag, nothing can match.
            return $text;
        }

        $text = preg_replace_callback(
                '/(<competvetsuivi.*<\/competvetsuivi>)/',
                'filter_competvetsuivi_replacebygraph',
                $text);
        return $text;
    }

    const GRAPH_TYPES=['studentprogress','ucoverview'];
}

function filter_competvetsuivi_replacebygraph($matches) {
    global $USER, $PAGE, $DB;

    $text = "";
    $doc = new DOMDocument();
    $fragment = $doc->createDocumentFragment();
    $fragment->appendXML($matches[0]);
    $doc->appendChild($fragment);

    $comptag = $doc->getElementsByTagName('competvetsuivi');

    if (!$comptag || !$comptag->count()) {
        return $text;
    }
    $comptag = $comptag->item(0);
    $graphtype = $comptag->attributes->getNamedItem('type')? $comptag->attributes->getNamedItem('type')->value: '';
    $canproceed = $graphtype && in_array($graphtype,filter_competvetsuivi::GRAPH_TYPES);


    // Default values
    $userid = $USER->id;
    $matrixid = 0;
    $uename = "";
    $samesemester = true;

    if ($graphtype == 'studentprogress') {
        $userid = $comptag->attributes->getNamedItem('userid')->value;

        if (!$userid || !is_numeric($userid)) {
            $userid = $USER->id;
        }
        $matrixid = utils::get_matrixid_for_user($userid);
        if ($USER->id != $userid) {
            $canproceed = has_capability('block/competvetsuivi:canseeother', context_system::instance(), $USER);
        }
    } else {
        $uename = $comptag->attributes->getNamedItem('uename')? $comptag->attributes->getNamedItem('uename')->value:'';
        $matrixsn = $comptag->attributes->getNamedItem('matrix')? $comptag->attributes->getNamedItem('matrix')->value:'';
        $samesemester = $comptag->attributes->getNamedItem('wholeyear')? false :true;
        $canproceed = $canproceed && $matrixsn && $DB->record_exists(
                                local_competvetsuivi\matrix\matrix::CLASS_TABLE,array('shortname'=>$matrixsn));

        $matrixid = $DB->get_field(
                local_competvetsuivi\matrix\matrix::CLASS_TABLE,'id',array('shortname'=>$matrixsn));
    }
    $canproceed = $canproceed && $matrixid;

    if ($canproceed) {
        $matrix = new matrix($matrixid);
        $matrix->load_data();
        switch($graphtype) {
            case 'studentprogress':
                $user = \core_user::get_user($userid);
                $userdata = local_competvetsuivi\userdata::get_user_data($user->email);
                $strandlist = array(matrix::MATRIX_COMP_TYPE_KNOWLEDGE, matrix::MATRIX_COMP_TYPE_ABILITY);
                $lastseenue = local_competvetsuivi\userdata::get_user_last_ue_name($user->email);
                $currentsemester = ueutils::get_current_semester_index($lastseenue, $matrix);
                $currentcompid = optional_param('competencyid', 0, PARAM_INT);
                $currentcomp = null;
                if ($currentcompid) {
                    $currentcomp = $matrix->comp[$currentcompid];
                }

                $progress_overview = new \local_competvetsuivi\output\competency_progress_overview(
                        $currentcomp,
                        $matrix,
                        $strandlist,
                        $userdata,
                        0,
                        $currentsemester
                );
                $renderer = $PAGE->get_renderer('local_competvetsuivi');
                $text = $renderer->render($progress_overview);
                break;
            case 'ucoverview':
                try {
                    $ue = $matrix->get_matrix_ue_by_criteria('shortname', $uename);
                } catch( moodle_exception $e) {
                    return $text;
                }

                $strandlist = array(matrix::MATRIX_COMP_TYPE_KNOWLEDGE, matrix::MATRIX_COMP_TYPE_ABILITY);


                $progress_overview = new \local_competvetsuivi\output\uevscompetency_overview(
                        $matrix,
                        $ue->id,
                        $strandlist,
                        0,
                        $samesemester
                );

                $renderer = $PAGE->get_renderer('local_competvetsuivi');
                $text = $renderer->render($progress_overview);
                break;
        }
    }
    return $text;

}