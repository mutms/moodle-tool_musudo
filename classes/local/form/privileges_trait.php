<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This program is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This program is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this program.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_musudo\local\form;

use stdClass;
use tool_mulib\muform\element\autocomplete;
use tool_mulib\muform\element\buttons;
use tool_mulib\muform\element\hidden;
use tool_mulib\muform\element\number;
use tool_mulib\muform\element\radios;
use tool_mulib\muform\element\reload;
use tool_mulib\muform\element\section;
use tool_mulib\muform\element\select;
use tool_mulib\muform\validator\required_if_visible;
use tool_musudo\local\sudoer;
use tool_musudo\muform\autocomplete\privilege_category;
use tool_musudo\muform\autocomplete\privilege_course;
use tool_musudo\muform\autocomplete\privilege_tenant;

/**
 * Privilege rows of privileged user forms.
 *
 * Every row has a stable number N used in element names, the hidden privilegerows
 * element lists the rendered rows. Add and delete buttons reload the form, the rows
 * are recalculated from the submitted list before any element is added.
 *
 * The context of a row is chosen by level: system, tenant (with multi-tenancy only),
 * category and course have their own pickers (tenantcontextid_N, categorycontextid_N,
 * coursecontextid_N), any other context is entered as a context id (contextid_N).
 *
 * @package     tool_musudo
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
trait privileges_trait {
    /** @var int maximum number of rows */
    private const int MAXROWS = 50;

    /**
     * Add privilege rows followed by the add button.
     */
    protected function add_privileges(): void {
        $rows = $this->get_privilege_rows();

        $list = new hidden('privilegerows');
        $list->set_default(implode(',', $rows));
        $this->add($list);

        $roles = ['' => get_string('choosedots')] + sudoer::get_role_options();
        $tenants = \tool_mulib\local\mulib::is_mutenancy_active();
        $levels = ['system' => get_string('coresystem')];
        if ($tenants) {
            $levels['tenant'] = get_string('tenant', 'tool_mutenancy');
        }
        $levels += [
            'category' => get_string('category'),
            'course' => get_string('course'),
            'context' => get_string('contextid', 'tool_musudo'),
        ];
        $dm = $this->get_display_manager();
        foreach ($rows as $i => $n) {
            $no = (string)($i + 1);

            $section = "privilege_$n";
            $this->add(new section($section, str_replace('{no}', $no, get_string('privilege_heading', 'tool_musudo'))));

            $roleid = new select("roleid_$n", get_string('role'), $roles);
            $roleid->set_required(true);
            $this->add($roleid, $section);

            $level = new radios("level_$n", get_string('context'), $levels, true);
            $level->set_default('system');
            $level->set_required(true);
            $this->add($level, $section);

            if ($tenants) {
                $tenant = new autocomplete("tenantcontextid_$n", get_string('tenant', 'tool_mutenancy'), new privilege_tenant());
                $tenant->set_required_marker(true);
                $tenant->add_validator(new required_if_visible());
                $this->add($tenant, $section);
                $dm->hide_if("tenantcontextid_$n", "level_$n", 'neq', 'tenant');
            }

            $category = new autocomplete("categorycontextid_$n", get_string('category'), new privilege_category());
            $category->set_required_marker(true);
            $category->add_validator(new required_if_visible());
            $this->add($category, $section);
            $dm->hide_if("categorycontextid_$n", "level_$n", 'neq', 'category');

            $course = new autocomplete("coursecontextid_$n", get_string('course'), new privilege_course());
            $course->set_required_marker(true);
            $course->add_validator(new required_if_visible());
            $this->add($course, $section);
            $dm->hide_if("coursecontextid_$n", "level_$n", 'neq', 'course');

            $contextid = new number("contextid_$n", get_string('contextid', 'tool_musudo'), ['min' => 1, 'width' => 'small']);
            $contextid->set_required_marker(true);
            $contextid->add_validator(new required_if_visible());
            $this->add($contextid, $section);
            $dm->hide_if("contextid_$n", "level_$n", 'neq', 'context');

            $this->add(new buttons("privilege_buttons_$n"), $section);
            $delete = new reload("privilege_delete_$n", str_replace('{no}', $no, get_string('privilege_delete', 'tool_musudo')));
            $this->add($delete, "privilege_buttons_$n");
        }

        $this->add(new buttons('privilege_buttons'));
        $this->add(new reload('privilege_add', get_string('privilege_more', 'tool_musudo')), 'privilege_buttons');
    }

    /**
     * Validate privilege rows.
     *
     * @param array $data
     * @param array $allerrors
     */
    protected function validate_privileges(array $data, array &$allerrors): void {
        global $DB;

        $rows = self::get_rows_from_list($data['privilegerows']);
        if (!$rows) {
            $allerrors['privilegerows'][] = get_string('required');
            return;
        }
        $contextids = [];
        foreach ($rows as $n) {
            $contextid = self::get_row_contextid($data, $n);
            $elname = self::get_row_elname($data["level_$n"], $n);
            if ($contextid) {
                $context = \context::instance_by_id($contextid, IGNORE_MISSING);
                if (!$context || isset($contextids[$context->id])) {
                    $allerrors[$elname][] = get_string('error');
                } else {
                    $contextids[$context->id] = true;
                }
            }
            $roleid = $data["roleid_$n"];
            if ($roleid && !$DB->record_exists('role', ['id' => $roleid])) {
                $allerrors["roleid_$n"][] = get_string('error');
            }
        }
    }

    /**
     * Converts submitted rows to contextid and roleid arrays expected by sudoer API.
     *
     * @param stdClass $data form data
     * @return stdClass data with contextid and roleid arrays
     */
    public static function get_privileges_data(stdClass $data): stdClass {
        $data->contextid = [];
        $data->roleid = [];
        foreach (self::get_rows_from_list($data->privilegerows) as $n) {
            $data->contextid[] = self::get_row_contextid((array)$data, $n);
            $data->roleid[] = $data->{"roleid_$n"};
            foreach (['roleid', 'level', 'tenantcontextid', 'categorycontextid', 'coursecontextid', 'contextid'] as $name) {
                unset($data->{"{$name}_$n"});
            }
        }
        unset($data->privilegerows);
        return $data;
    }

    /**
     * Current data of privilege rows.
     *
     * @param array $privileges list of objects with contextid and roleid properties
     * @return array
     */
    public static function get_privileges_current_data(array $privileges): array {
        $tenants = \tool_mulib\local\mulib::is_mutenancy_active();
        $result = [];
        foreach (array_values($privileges) as $n => $privilege) {
            $result["roleid_$n"] = $privilege->roleid;
            $context = \context::instance_by_id($privilege->contextid, IGNORE_MISSING);
            if ($context && $context->contextlevel == CONTEXT_SYSTEM) {
                $result["level_$n"] = 'system';
            } else if ($tenants && $context && $context->contextlevel == \core\context\tenant::LEVEL) {
                $result["level_$n"] = 'tenant';
                $result["tenantcontextid_$n"] = $context->id;
            } else if ($context && $context->contextlevel == CONTEXT_COURSECAT) {
                $result["level_$n"] = 'category';
                $result["categorycontextid_$n"] = $context->id;
            } else if ($context && $context->contextlevel == CONTEXT_COURSE) {
                $result["level_$n"] = 'course';
                $result["coursecontextid_$n"] = $context->id;
            } else {
                $result["level_$n"] = 'context';
                $result["contextid_$n"] = $privilege->contextid;
            }
        }
        return $result;
    }

    /**
     * Context id of a row.
     *
     * @param array $data form data
     * @param int $n row number
     * @return int|null
     */
    private static function get_row_contextid(array $data, int $n): ?int {
        $level = $data["level_$n"];
        if ($level === 'system') {
            return \context_system::instance()->id;
        }
        $value = $data[self::get_row_elname($level, $n)] ?? null;
        return $value ? (int)$value : null;
    }

    /**
     * Name of the element with the context of a row.
     *
     * @param string|null $level
     * @param int $n row number
     * @return string
     */
    private static function get_row_elname(?string $level, int $n): string {
        return match ($level) {
            'tenant' => "tenantcontextid_$n",
            'category' => "categorycontextid_$n",
            'course' => "coursecontextid_$n",
            'context' => "contextid_$n",
            default => "level_$n",
        };
    }

    /**
     * Rendered rows: the submitted list adjusted by pressed add or delete buttons,
     * rows found in current data for new forms.
     *
     * @return int[]
     */
    private function get_privilege_rows(): array {
        $post = $this->get_post_data();
        if ($post === null || !isset($post['privilegerows']) || !is_string($post['privilegerows'])) {
            $rows = [];
            foreach (array_keys($this->get_current_data()) as $key) {
                if (preg_match('/^(roleid|level)_(\d+)$/D', $key, $matches)) {
                    $rows[(int)$matches[2]] = (int)$matches[2];
                }
            }
            ksort($rows);
            return array_values($rows);
        }

        $rows = [];
        foreach (self::get_rows_from_list($post['privilegerows']) as $n) {
            if (empty($post["privilege_delete_$n"])) {
                $rows[] = $n;
            }
        }
        if (!empty($post['privilege_add']) && count($rows) < self::MAXROWS) {
            $rows[] = $rows ? max($rows) + 1 : 0;
        }
        return $rows;
    }

    /**
     * Parse list of row numbers.
     *
     * @param string|null $list comma separated row numbers
     * @return int[]
     */
    private static function get_rows_from_list(?string $list): array {
        $rows = [];
        foreach (explode(',', (string)$list) as $n) {
            if (preg_match('/^\d{1,3}$/D', $n)) {
                $rows[(int)$n] = (int)$n;
            }
        }
        return array_slice(array_values($rows), 0, self::MAXROWS);
    }
}
