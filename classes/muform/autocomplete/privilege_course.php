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

namespace tool_musudo\muform\autocomplete;

use tool_mulib\local\sql;
use tool_musudo\local\util;

/**
 * Course context of a privilege, site home included, values are context ids.
 *
 * @package     tool_musudo
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class privilege_course extends \tool_mulib\muform\autocomplete\base {
    /**
     * Constructor.
     */
    public function __construct() {
        util::require_admin();
    }

    #[\Override]
    public function get_args(): array {
        return [];
    }

    #[\Override]
    public function search(string $query, int $maxitems): ?array {
        global $DB;

        $sql = new sql(
            "SELECT ctx.id, c.fullname
               FROM {course} c
               JOIN {context} ctx ON ctx.instanceid = c.id AND ctx.contextlevel = :courselevel
              WHERE 1 = 1 /* search */
           ORDER BY c.fullname ASC, c.id ASC",
            ['courselevel' => CONTEXT_COURSE]
        );
        $query = trim($query);
        if ($query !== '') {
            $fields = ['fullname', 'shortname', 'idnumber'];
            $search = \tool_mulib\local\search_util::get_search_query($query, $fields, 'c');
            $sql = $sql->replace_comment('search', $search->wrap('AND ', ''));
        }
        $courses = $DB->get_records_sql_menu($sql->sql, $sql->params, 0, $maxitems + 1);
        if (count($courses) > $maxitems) {
            return null;
        }
        $result = [];
        foreach ($courses as $contextid => $unused) {
            $result[(string)$contextid] = self::get_context_label((int)$contextid);
        }
        return $result;
    }

    #[\Override]
    public function label(string $value): ?string {
        if (!preg_match('/^[1-9][0-9]*$/D', $value)) {
            return null;
        }
        $context = \core\context::instance_by_id((int)$value, IGNORE_MISSING);
        if (!$context || $context->contextlevel != CONTEXT_COURSE) {
            return null;
        }
        return self::get_context_label($context->id);
    }

    /**
     * Course name.
     *
     * @param int $contextid
     * @return string html
     */
    private static function get_context_label(int $contextid): string {
        $context = \core\context::instance_by_id($contextid);
        return clean_text($context->get_context_name(false));
    }
}
