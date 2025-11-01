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

require_once($CFG->dirroot . '/blocks/lessonmenu/backup/moodle2/restore_lessonmenu_block_decode_content.class.php');

/**
 * Specialised restore task for the html block
 * (requires encode_content_links in some configdata attrs)
 *
 * @package    block_lessonmenu
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_lessonmenu_block_task extends restore_block_task {
    /**
     * If the block declares own backup settings defined in the file backup_foobar_settingslib.php, add them here.
     * Most blocks just leave the method body empty.
     *
     */
    protected function define_my_settings() {
    }

    /**
     * Blocks that do not have their own database tables usually leave this method empty.
     * Otherwise this method consists of one or more $this->add_step() calls where you
     * define the task as a sequence of steps to execute.
     *
     */
    protected function define_my_steps() {
    }

    /**
     * Returns the array of file area names within the block context.
     *
     * @return array
     */
    public function get_fileareas() {
        return ['content_footer'];
    }

    /**
     * Instead of using their own tables, blocks usually use the configuration tables to hold their data.
     * This method returns the array of all config elements that must be processed before they are stored
     * in the backup.
     * This is typically used when the stored config elements holds links to embedded media.
     * Most blocks just return empty array here.
     *
     * @return array
     */
    public function get_configdata_encoded_attributes() {
        return ['htmlfooter']; // We need to encode some attrs in configdata.
    }

    /**
     * Define the decode contents for the block.
     *
     * @return array
     */
    public static function define_decode_contents() {

        $contents = [];

        $contents[] = new restore_lessonmenu_block_decode_content('block_instances', 'configdata', 'block_instance');

        return $contents;
    }

    /**
     * Define the decode rules for the block.
     *
     * @return array
     */
    public static function define_decode_rules() {
        return [];
    }
}
