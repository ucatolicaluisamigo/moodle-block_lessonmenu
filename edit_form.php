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
 * Form for editing block instances.
 *
 * @package    block_lessonmenu
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Form for editing block instances.
 *
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class block_lessonmenu_edit_form extends block_edit_form {
    /**
     * Define the form fields.
     *
     * @param MoodleQuickForm $mform The form being defined.
     * @return void
     */
    protected function specific_definition($mform) {
        // Fields for editing HTML block title and contents.
        $mform->addElement('header', 'configheader', get_string('blocksettings', 'block'));

        $mform->addElement('text', 'config_title', get_string('configtitle', 'block_lessonmenu'));
        $mform->setType('config_title', PARAM_TEXT);

        $notyes = [0 => get_string('no'), 1 => get_string('yes')];
        $mform->addElement(
            'select',
            'config_freenavigation',
            get_string('configfreenavigation', 'block_lessonmenu'),
            $notyes,
            1
        );

        $mform->addElement(
            'select',
            'config_startcollapsed',
            get_string('configstartcollapsed', 'block_lessonmenu'),
            $notyes,
            0
        );

        $mform->addElement(
            'select',
            'config_displayquestions',
            get_string('configdisplayquestions', 'block_lessonmenu'),
            $notyes,
            0
        );
        $mform->addHelpButton('config_displayquestions', 'configdisplayquestions', 'block_lessonmenu');

        $mform->addElement(
            'select',
            'config_displaystats',
            get_string('configdisplaystats', 'block_lessonmenu'),
            $notyes,
            1
        );

        $mform->addElement(
            'select',
            'config_displaytime',
            get_string('configdisplaytime', 'block_lessonmenu'),
            $notyes,
            1
        );

        if (\block_lessonmenu\local\controller::codemirror_present()) {
            $mform->addElement('editor', 'config_css', get_string('configcss', 'block_lessonmenu'));
        } else {
            $mform->addElement('textarea', 'config_css', get_string('configcss', 'block_lessonmenu'));
        }
        $mform->setType('config_css', PARAM_RAW);

        $editoroptions = ['maxfiles' => EDITOR_UNLIMITED_FILES, 'noclean' => true, 'context' => $this->block->context];

        // Footer HTML editor.
        $mform->addElement('editor', 'config_htmlfooter', get_string('confightmlfooter', 'block_lessonmenu'), null, $editoroptions);
        $mform->setType('config_htmlfooter', PARAM_RAW); // XSS is prevented when printing the block contents and serving files.
    }

    /**
     * Set the default values for the form.
     *
     * @param stdClass $defaults The default values.
     * @return void
     */
    function set_data($defaults) {
        if (!empty($this->block->config) && !empty($this->block->config->htmlfooter)) {
            $htmlfooter = $this->block->config->htmlfooter;
            $draftideditor = file_get_submitted_draft_itemid('config_htmlfooter');

            if (empty($htmlfooter)) {
                $currenthtmlfooter = '';
            } else {
                $currenthtmlfooter = $htmlfooter;
            }

            $defaults->config_htmlfooter['text'] = file_prepare_draft_area(
                $draftideditor,
                $this->block->context->id,
                'block_lessonmenu',
                'content_footer',
                0,
                ['subdirs' => true],
                $currenthtmlfooter
            );
            $defaults->config_htmlfooter['itemid'] = $draftideditor;
            $defaults->config_htmlfooter['format'] = $this->block->config->htmlfooterformat ?? FORMAT_MOODLE;
        } else {
            $htmlfooter = '';
        }

        // Have to delete structure here because is edited in a different form.
        unset($this->block->config->structure);

        // Have to delete html here, otherwise parent::set_data will empty content of editors.
        unset($this->block->config->htmlfooter);
        parent::set_data($defaults);

        if (!isset($this->block->config)) {
            $this->block->config = new stdClass();
        }

        $this->block->config->htmlfooter = $htmlfooter;
    }
}
