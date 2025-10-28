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

namespace block_lessonmenu\output;

use renderable;
use renderer_base;
use templatable;
use block_lessonmenu\local\controller;

/**
 * Class editstructure
 *
 * @package    block_lessonmenu
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class editstructure implements renderable, templatable {

    /**
     * @var int The block instance id.
     */
    private $instanceid;

    /**
     * @var array List of menu items to print.
     */
    private $menuitems;

    /**
     * Constructor.
     *
     * @param int $instanceid The block instance id.
     * @param array $menuitems List of menu items to print.
     */
    public function __construct(int $instanceid, array $menuitems) {
        $this->instanceid = $instanceid;
        $this->menuitems = $menuitems;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output The renderer.
     * @return array The data for the template.
     */
    public function export_for_template(renderer_base $output): array {

        $contenttypes = controller::get_content_types();

        foreach ($this->menuitems as $section) {
            // Adjust indentation levels to ensure they don't skip levels.
            $indentation = -1;
            foreach ($section->items as $item) {
                if (($indentation - $item->indentation) > 1) {
                    $item->indentation = $indentation + 1;
                } else {
                    $indentation = $item->indentation;
                }
            }

            if (isset($contenttypes[$item->contenttype])) {
                $item->contenttypeinfo = $contenttypes[$item->contenttype];
            } else {
                $item->contenttypeinfo = null;
            }
        }

        return [
            'instanceid' => $this->instanceid,
            'menuitems' => $this->menuitems,
        ];
    }
}
