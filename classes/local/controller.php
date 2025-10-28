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

namespace block_lessonmenu\local;

/**
 * Class controller
 *
 * @package    block_lessonmenu
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller {
    /**
     * Get available content types with their icons.
     * @return array Associative array of content types, their icons and labels.
     */
    public static function get_content_types(): array {
        $contenttypesconfig = get_config('block_lessonmenu', 'contenttypes');
        $contenttypes = [];
        if ($contenttypesconfig) {
            $lines = explode("\n", $contenttypesconfig);
            foreach ($lines as $line) {
                $parts = explode('|', trim($line));
                if (count($parts) < 2) {
                    continue;
                }

                $code = trim($parts[0]);
                if (empty($code)) {
                    continue;
                }

                $type = new \stdClass();
                $type->code = $code;
                $type->icon = trim($parts[1]);
                $type->label = isset($parts[2]) ? trim($parts[2]) : '';
                $contenttypes[$type->code] = $type;
            }
        }

        return $contenttypes;
    }
}
