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
                $type->label = !empty($parts[2]) ? trim($parts[2]) : $code;
                $contenttypes[$type->code] = $type;
            }
        }

        return $contenttypes;
    }

    /**
     * Get default sections.
     *
     * @return array List of default section titles.
     */
    public static function get_default_sections(): array {
        $defaultsectionsconfig = get_config('block_lessonmenu', 'defaultsections');
        $defaultsections = [];
        if ($defaultsectionsconfig) {
            $lines = explode("\n", $defaultsectionsconfig);
            foreach ($lines as $line) {
                $line = trim($line);

                if (empty($line)) {
                    continue;
                }

                $parts = explode('|', $line);
                $title = trim($parts[0]);
                $elements = isset($parts[1]) ? explode(';', trim($parts[1])) : [];

                if (empty($title) || empty($elements)) {
                    continue;
                }

                $section = new \stdClass();
                $section->title = $title;
                $section->elements = array_map('trim', $elements);
                $section->dataelements = json_encode($section->elements);

                $uitext = '';
                $uitext = implode(', ', $elements);
                $section->uitext = $title . ': ' . $uitext;

                $defaultsections[] = $section;
            }
        }

        return $defaultsections;
    }

    /**
     * Get menu items from lesson tree.
     *
     * @param object $instance The block instance.
     * @param object $lesson The lesson object.
     * @return array List of menu items.
     */
    public static function get_menu_items(object $instance, object $lesson): array {

        $configdata = empty($instance->configdata) ? (new \stdClass()) : unserialize(base64_decode($instance->configdata));
        $menuitems = [];

        if (property_exists($configdata, 'menuitems')) {
            $menuitems = @json_decode($configdata->menuitems) ?? [];
        }
        $pages = $lesson->load_all_pages();

        if (empty($menuitems)) {
            $items = [];
            foreach ($pages as $page) {
                $items[] = (object)[
                    'pageid' => $page->id,
                    'page' => $page,
                    'contenttype' => '',
                    'duration' => 0,
                    'indentation' => 0,
                    'completed' => false,
                    'contenttypeinfo' => (object)[
                        'code' => 'noticon',
                        'icon' => 'e/fullscreen',
                        'label' => get_string('changecontenttype', 'block_lessonmenu'),
                    ],
                ];
            }

            $menuitems = [
                (object)[
                    'title' => null,
                    'items' => $items,
                    'root' => true,
                ],
            ];
        } else {
            // Link pages to menu items.
            foreach ($menuitems as $menuitem) {
                foreach ($menuitem->items as $key => $item) {
                    if (isset($pages[$item->pageid])) {
                        $item->page = $pages[$item->pageid];
                    } else {
                        // The page was deleted, remove from menu.
                        unset($menuitem->items[$key]);
                    }
                }
            }
        }

        return $menuitems;
    }
}
