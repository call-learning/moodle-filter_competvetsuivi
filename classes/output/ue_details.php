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
 * UE details renderable for filter_competvetsuivi
 *
 * @package     filter_competvetsuivi
 * @copyright   2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
namespace filter_competvetsuivi\output;

use local_envasyllabus\output\course_syllabus;
use renderable;
use renderer_base;
use stdClass;
use templatable;

/**
 * UE details renderable - extends course_syllabus to reuse header logic
 *
 * @package     filter_competvetsuivi
 * @copyright   2025 Bas Brands <bas@sonsbeekmedia.nl>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class ue_details extends course_syllabus implements renderable, templatable {
    /** @var string Detail link URL */
    protected $detailurl;

    /** @var string Rendered progress percent */
    protected $progresshtml;

    /**
     * Constructor
     *
     * @param int $courseid Course ID
     * @param string $detailurl Detail link URL
     * @param string $progresshtml Rendered progress percent HTML
     */
    public function __construct(int $courseid, string $detailurl, string $progresshtml) {
        parent::__construct($courseid);
        $this->detailurl = $detailurl;
        $this->progresshtml = $progresshtml;
    }

    /**
     * Export for template - simplified version without PAGE requirements
     *
     * @param renderer_base $output
     * @return stdClass
     */
    public function export_for_template(renderer_base $output) {
        global $DB;

        $context = new stdClass();
        $context->detailurl = $this->detailurl;
        $context->progresshtml = $this->progresshtml;

        // Get course data for the header template.
        $course = $DB->get_record('course', ['id' => $this->courseid]);
        $coursecontext = \context_course::instance($this->courseid);

        // Get custom field info.
        $handler = \core_customfield\handler::get_handler('core_course', 'course');
        $cfdata = $handler->get_instance_data($this->courseid, true);
        $customfields = [];
        foreach ($cfdata as $cfdatacontroller) {
            $shortname = $cfdatacontroller->get_field()->get('shortname');
            $customfields[$shortname] = $cfdatacontroller->export_value();
        }

        // Set programme and totals (from parent class).
        $this->set_programme_and_totals($cfdata);

        // Get header data using parent's method.
        $context->headerdata = $this->get_header_data(self::CF_HEADER_DEFINITION, $customfields);

        return $context;
    }
}
