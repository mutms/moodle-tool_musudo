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
 * Category context of a privilege, values are context ids.
 *
 * @package     tool_musudo
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class privilege_category extends \tool_mulib\muform\autocomplete\base {
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
            "SELECT ctx.id, cat.name
               FROM {course_categories} cat
               JOIN {context} ctx ON ctx.instanceid = cat.id AND ctx.contextlevel = :catlevel
              WHERE 1 = 1 /* search */
           ORDER BY cat.sortorder ASC",
            ['catlevel' => CONTEXT_COURSECAT]
        );
        $query = trim($query);
        if ($query !== '') {
            $search = \tool_mulib\local\search_util::get_search_query($query, ['name', 'idnumber'], 'cat');
            $sql = $sql->replace_comment('search', $search->wrap('AND ', ''));
        }
        $categories = $DB->get_records_sql_menu($sql->sql, $sql->params, 0, $maxitems + 1);
        if (count($categories) > $maxitems) {
            return null;
        }
        $labels = [];
        foreach (array_keys($categories) as $contextid) {
            $labels[(string)$contextid] = self::get_context_label((int)$contextid);
        }
        \core_collator::asort($labels);
        return array_map('clean_text', $labels);
    }

    #[\Override]
    public function label(string $value): ?string {
        if (!preg_match('/^[1-9][0-9]*$/D', $value)) {
            return null;
        }
        $context = \core\context::instance_by_id((int)$value, IGNORE_MISSING);
        if (!$context || $context->contextlevel != CONTEXT_COURSECAT) {
            return null;
        }
        return clean_text(self::get_context_label($context->id));
    }

    /**
     * Category path.
     *
     * @param int $contextid
     * @return string plain text
     */
    private static function get_context_label(int $contextid): string {
        $context = \core\context::instance_by_id($contextid);
        $names = [];
        foreach (array_reverse($context->get_parent_contexts(true)) as $c) {
            if ($c->contextlevel == CONTEXT_SYSTEM) {
                continue;
            }
            $names[] = $c->get_context_name(false);
        }
        return implode(' / ', $names);
    }
}
