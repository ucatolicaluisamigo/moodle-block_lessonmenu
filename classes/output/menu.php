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
 * Class menu
 *
 * @package    block_lessonmenu
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class menu implements renderable, templatable {
    /**
     * @var int The block instance id.
     */
    private $instanceid;

    /**
     * @var object The lesson object.
     */
    private $lesson;

    /**
     * Constructor.
     *
     * @param int $instanceid The block instance id.
     * @param object $lesson The lesson object.
     */
    public function __construct(int $instanceid, object $lesson) {
        $this->instanceid = $instanceid;
        $this->lesson = $lesson;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output The renderer.
     * @return array The data for the template.
     */
    public function export_for_template(renderer_base $output): array {
        global $DB, $pageid, $USER;

        $currentpageid = optional_param('pageid', $pageid ?? 0, PARAM_INT);

        $instance = $DB->get_record('block_instances', ['id' => $this->instanceid, 'blockname' => 'lessonmenu'], '*', MUST_EXIST);
        $menuitems = controller::get_menu_items($instance, $this->lesson);
        $configdata = empty($instance->configdata) ? (new \stdClass()) : unserialize(base64_decode($instance->configdata));

        $startcollapsed = isset($configdata->startcollapsed) ? (bool)$configdata->startcollapsed : false;
        $freenavigation = isset($configdata->freenavigation) ? (bool)$configdata->freenavigation : true;
        $displaytime = isset($configdata->displaytime) ? (bool)$configdata->displaytime : true;
        $displaystats = isset($configdata->displaystats) ? (bool)$configdata->displaystats : true;

        $stats = null;
        $canedit = has_capability('mod/lesson:manage', $this->lesson->context);
        if ($displaystats && !$canedit) {
            // Calculate time spent and current grade.
            $params = ['lessonid' => $this->lesson->id, 'userid' => $USER->id];
            $timer = $DB->get_records('lesson_timer', $params, 'starttime DESC', '*', 0, 1);
            if (!empty($timer)) {
                // Read the most recent timer.
                $timer = reset($timer);
                $timespent = $timer->lessontime - $timer->starttime;
            } else {
                $timespent = 0;
            }

            $sql = 'SELECT SUM(correct) AS points
                FROM {lesson_attempts}
                WHERE userid = :userid AND lessonid = :lessonid
                GROUP BY userid';
            $params = ['userid' => $USER->id, 'lessonid' => $this->lesson->id];
            $points = $DB->get_record_sql($sql, $params);

            $sql = 'SELECT lessonid, SUM(score) AS total
                FROM {lesson_answers}
                WHERE lessonid = :lessonid
                GROUP BY lessonid';
            $params = ['lessonid' => $this->lesson->id];
            $totalpoints = $DB->get_record_sql($sql, $params);

            $stats = (object)[
                'timespent' => round($timespent / 60), // In minutes.
                'timespentformated' => gmdate('H:i', $timespent),
                'points' => $totalpoints && $points ? round(($points->points / $totalpoints->total) * 100) : 0,
            ];
        }

        // Load page information by user.
        $visitedpages = $DB->get_records('lesson_branch', [
            'lessonid' => $this->lesson->id,
            'userid' => $USER->id,
        ], '', 'DISTINCT pageid AS id');

        $attemptquestions = $DB->get_records('lesson_attempts', [
            'lessonid' => $this->lesson->id,
            'userid' => $USER->id,
        ], '', 'DISTINCT pageid AS id');

        $previousitem = null;
        $totalvisited = 0;
        $countpages = 0;
        $totaltime = 0;
        foreach ($menuitems as $key => $section) {
            if ($section->title) {
                $section->collapsed = $startcollapsed;
            } else {
                $section->collapsed = false;
            }

            $completed = 0;
            foreach ($section->items as $itemkey => $item) {
                $item->iscurrent = false;
                if (!empty($currentpageid)) {
                    $item->iscurrent = $item->pageid == $currentpageid;
                }

                $totaltime += (int)$item->duration;

                if (!$configdata->displayquestions && $item->page->qtype != 20) {
                    unset($section->items[$itemkey]);
                    continue;
                }

                $item->visited = isset($visitedpages[$item->pageid]) || isset($attemptquestions[$item->pageid]);

                if ($item->visited) {
                    $completed++;
                    $totalvisited++;
                }

                $item->blocked = !(
                    $freenavigation ||
                    $item->visited ||
                    ($previousitem && ($previousitem->visited || ($previousitem->page->qtype == 20 && $previousitem->iscurrent))));

                if ($canedit || (!$item->iscurrent && !$item->blocked)) {
                    $params = ['id' => $this->lesson->cm->id, 'pageid' => $item->pageid];
                    $item->url = (string)(new \moodle_url('/mod/lesson/view.php', $params));
                }

                $previousitem = $item;
            }

            if (empty($section->items)) {
                unset($menuitems[$key]);
                continue;
            } else {
                // Reindex items.
                $section->items = array_values($section->items);
                $section->completed = (count($section->items) == $completed);
                $countpages += count($section->items);
            }
        }

        // Reindex sections because if a section is empty, it is removed from the menu.
        $menuitems = array_values($menuitems);

        if ($stats) {
            $stats->totaltimeformated = gmdate('H:i', $totaltime * 60); // Total time is in minutes.
            $stats->totaltime = round($totaltime); // In minutes.
        }

        return [
            'sesskey' => sesskey(),
            'menuitems' => $menuitems,
            'startcollapsed' => $startcollapsed,
            'displaytime' => $displaytime,
            'progress' => $this->lesson->calculate_progress(),
            'stats' => $stats,
        ];
    }
}
