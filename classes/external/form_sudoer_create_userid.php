<?php
// This file is part of MuTMS suite of plugins for Moodle™ LMS.
//
// This framework is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// This framework is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with this framework.  If not, see <https://www.gnu.org/licenses/>.

// phpcs:disable moodle.Files.BoilerplateComment.CommentEndedTooSoon

namespace tool_musudo\external;

use core_external\external_function_parameters;
use core_external\external_value;
use tool_musudo\local\util;

/**
 * Provides list of candidates for sudo users.
 *
 * @package     tool_musudo
 * @copyright   2025 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class form_sudoer_create_userid extends \tool_mulib\external\form_autocomplete_field {
    /**
     * True means returned field data is array, false means value is scalar.
     *
     * @return bool
     */
    public static function is_multi_select_field(): bool {
        return false;
    }

    /**
     * Describes the external function arguments.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'The search query', VALUE_REQUIRED),
        ]);
    }

    /**
     * Finds users with the identity matching the given query.
     *
     * @param string $query The search request
     * @return array
     */
    public static function execute(string $query): array {
        global $DB, $CFG;

        ['query' => $query] = self::validate_parameters(self::execute_parameters(), ['query' => $query]);

        util::require_admin();

        $syscontext = \context_system::instance();
        self::validate_context($syscontext);

        $fields = \core_user\fields::for_name()->with_identity($syscontext, false);
        $extrafields = $fields->get_required_fields([\core_user\fields::PURPOSE_IDENTITY]);

        list($searchsql, $searchparams) = users_search_sql($query, 'usr', true, $extrafields);
        list($sortsql, $sortparams) = users_order_by_sql('usr', $query, $syscontext);
        $params = array_merge($searchparams, $sortparams);

        $admins = explode(',', $CFG->siteadmins);
        $admins = array_map('intval', $admins);
        $admins = implode(',', $admins);

        $additionalfields = $fields->get_sql('usr')->selects;
        $sqlquery = <<<SQL
            SELECT usr.id {$additionalfields}
              FROM {user} usr
         LEFT JOIN {tool_musudo_sudoer} su ON su.userid = usr.id
             WHERE {$searchsql}
                   AND su.id IS NULL AND usr.deleted = 0 AND usr.confirmed = 1
                   AND usr.id NOT IN ($admins)
          ORDER BY {$sortsql}
SQL;

        $rs = $DB->get_recordset_sql($sqlquery, $params, 0, $CFG->maxusersperpage + 1);

        return self::prepare_user_list($rs, $extrafields);
    }

    /**
     * Return function that return label for given value.
     *
     * @param array $arguments
     * @return callable
     */
    public static function get_label_callback(array $arguments): callable {
        return function($value) use ($arguments): string {
            global $DB;

            if (!$value) {
                return get_string('notset', 'tool_mulib');
            }

            $record = $DB->get_record('user', ['id' => $value]);
            if (!$record) {
                return get_string('error');
            }

            $syscontext = \context_system::instance();
            return self::prepare_user_label($record, $syscontext);
        };
    }

    /**
     * Validate values.
     *
     * @param array $arguments
     * @param mixed $value
     * @return string|null error message, NULL means value is ok
     */
    public static function validate_form_value(array $arguments, $value): ?string {
        global $DB;

        if (!$value) {
            return get_string('error');
        }

        $user = $DB->get_record('user', ['id' => $value]);
        if (!$user || $user->deleted || !$user->confirmed) {
            return get_string('error');
        }

        if ($DB->record_exists('tool_musudo_sudoer', ['userid' => $user->id])) {
            return get_string('error');
        }

        if (is_siteadmin($user->id)) {
            return get_string('error');
        }

        return null;
    }
}
