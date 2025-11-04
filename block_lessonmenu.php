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
     * This block has settings.
     *
     * @return bool
     */
    public function has_config() {
        return true;
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
     * Set the block title from config data.
     *
     * @return void
     */
    function specialization() {
        if (isset($this->config->title)) {
            $this->title = $this->title = format_string($this->config->title, true, ['context' => $this->context]);
        }
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
        global $lesson, $pageid;

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

        $currentpageid = optional_param('pageid', $pageid ?? 0, PARAM_INT);

        if (empty($currentpageid)) {
            return $this->content;
        }

        $filteropt = new \stdClass();
        $filteropt->overflowdiv = true;
        $filteropt->noclean = true;

        $this->content->text = \block_lessonmenu\local\controller::get_block_contents($this->instance->id, $lesson);

        if (isset($this->config->htmlfooter)) {
            // Rewrite url.
            $htmlfooter = file_rewrite_pluginfile_urls(
                $this->config->htmlfooter,
                'pluginfile.php',
                $this->context->id,
                'block_lessonmenu',
                'content_footer',
                0
            );
            // Default to FORMAT_HTML.
            $htmlfooterformat = FORMAT_HTML;
            // Check to see if the format has been properly set on the config.
            if (isset($this->config->htmlfooterformat)) {
                $htmlfooterformat = $this->config->htmlfooterformat;
            }

            if (is_array($htmlfooter)) {
                $htmlfooter = $htmlfooter['text'];
            }

            $this->content->footer .= format_text($htmlfooter, $htmlfooterformat, $filteropt);
        }

        if (!empty($this->config->css)) {
            $this->content->footer .= \html_writer::tag('style', $this->config->css, ['type' => 'text/css']);
        }

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

    /**
     * Return a block_contents object representing the full contents of this block.
     *
     * This internally calls ->get_content(), and then adds the editing controls etc.
     *
     * @param object $output The output renderer from the parent context (e.g. page renderer)
     * @return block_contents a representation of the block, for rendering.
     */
    public function get_content_for_output($output) {
        $bc = parent::get_content_for_output($output);

        if (
            empty($bc->controls) ||
            !$this->page->user_is_editing() ||
            !has_capability('block/lessonmenu:addinstance', $this->context)
        ) {
            return $bc;
        }

        $str = get_string('editstructure', 'block_lessonmenu');

        $newcontrols = [];
        foreach ($bc->controls as $control) {
            $newcontrols[] = $control;
            // Append our new item onto the controls if we're on the correct item.
            if (strpos($control->attributes['class'], 'editing_edit') !== false) {
                $icon = new pix_icon('e/increase_indent', $str, 'moodle', ['class' => 'iconsmall']);
                $newcontrols[] = new action_menu_link_secondary(
                    new moodle_url('/blocks/lessonmenu/edit.php', ['id' => $this->instance->id]),
                    $icon,
                    $str,
                    ['class' => 'editing_manage']
                );
            }
        }

        $bc->controls = $newcontrols;
        return $bc;
    }

    /**
     * Serialize and store config data.
     *
     * @param object $data
     * @param boolean $nolongerused
     * @return void
     */
    public function instance_config_save($data, $nolongerused = false) {
        $config = clone($data);

        // Move embedded files into a proper filearea and adjust HTML links to match.
        $config->htmlfooter = file_save_draft_area_files(
            $data->htmlfooter['itemid'],
            $this->context->id,
            'block_lessonmenu',
            'content_footer',
            0,
            ['subdirs' => true],
            $data->htmlfooter['text']
        );
        $config->htmlfooterformat = $data->htmlfooter['format'];

        // If codemirror was used, extract the CSS text.
        if (is_array($config->css) && isset($config->css['text'])) {
            $config->css = $config->css['text'];
        }

        parent::instance_config_save($config, $nolongerused);
    }
}
