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

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/mod/lesson/lib.php');

/**
 * Block Lesson menu
 *
 * Documentation: {@link https://moodledev.io/docs/apis/plugintypes/blocks}
 *
 * @package    block_lessonmenu
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_lessonmenu extends block_base {

    /**
     * Block initialisation
     */
    public function init() {
        $this->title = get_string('pluginname', 'block_lessonmenu');
    }

    /**
     * Which page types this block may appear on.
     *
     * @return array page-type prefix => true/false.
     */
    public function applicable_formats() {
        return ['mod-lesson-view' => true];
    }

    /**
     * This block allows instance configuration.
     *
     * @return bool
     */
    public function instance_allow_config() {
        return true;
    }

    /**
     * Get content
     *
     * @return stdClass
     */
    public function get_content() {
        global $lesson;

        if ($this->content !== null) {
            return $this->content;
        }

        $this->content = (object)[
            'footer' => '',
            'text' => '',
        ];

        if (empty($lesson)) {
            return $this->content;
        }

        $displayleft = $lesson->displayleft;
        $lesson->displayleft = true;
        $bc = lesson_menu_block_contents($this->page->cm->id, $lesson);
        $lesson->displayleft = $displayleft;

        $this->content->text = $bc ? $bc->content : '';

        return $this->content;
    }

    /**
     * Returns the role that best describes the blog menu block.
     *
     * @return string
     */
    public function get_aria_role() {
        return 'navigation';
    }
}
