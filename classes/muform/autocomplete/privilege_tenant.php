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
 * Tenant context of a privilege, values are context ids.
 *
 * @package     tool_musudo
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class privilege_tenant extends \tool_mulib\muform\autocomplete\base {
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
            "SELECT ctx.id, t.name
               FROM {tool_mutenancy_tenant} t
               JOIN {context} ctx ON ctx.instanceid = t.id AND ctx.contextlevel = :tenantlevel
              WHERE 1 = 1 /* search */
           ORDER BY t.name ASC, t.id ASC",
            ['tenantlevel' => \core\context\tenant::LEVEL]
        );
        $query = trim($query);
        if ($query !== '') {
            $search = \tool_mulib\local\search_util::get_search_query($query, ['name', 'idnumber'], 't');
            $sql = $sql->replace_comment('search', $search->wrap('AND ', ''));
        }
        $tenants = $DB->get_records_sql_menu($sql->sql, $sql->params, 0, $maxitems + 1);
        if (count($tenants) > $maxitems) {
            return null;
        }
        $syscontext = \context_system::instance();
        return array_map(fn($name) => format_string($name, true, ['context' => $syscontext]), $tenants);
    }

    #[\Override]
    public function label(string $value): ?string {
        global $DB;
        if (!preg_match('/^[1-9][0-9]*$/D', $value)) {
            return null;
        }
        $context = \core\context::instance_by_id((int)$value, IGNORE_MISSING);
        if (!$context || $context->contextlevel != \core\context\tenant::LEVEL) {
            return null;
        }
        $name = $DB->get_field('tool_mutenancy_tenant', 'name', ['id' => $context->instanceid]);
        if ($name === false) {
            return null;
        }
        return format_string($name, true, ['context' => \context_system::instance()]);
    }
}
