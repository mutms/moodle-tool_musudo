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

namespace tool_musudo\mureport\system_report;

use tool_mureport\mureport\cell;
use tool_mureport\mureport\column;
use tool_mureport\mureport\entity\user;
use tool_mureport\mureport\export_value;
use tool_mureport\mureport\filter;
use tool_mureport\mureport\join;
use tool_mureport\mureport\system_report;
use tool_musudo\mureport\entity\sudoer;
use core\lang_string;
use core\url;
use stdClass;

/**
 * Privileged users, for site configuration managers.
 *
 * @package     tool_musudo
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class sudoers extends system_report {
    /**
     * Make report instance.
     *
     * @return self
     */
    public static function make_instance(): self {
        return self::from_constraints([]);
    }

    #[\Override]
    public static function get_title(): string {
        return get_string('sudoers', 'tool_musudo');
    }

    #[\Override]
    public static function get_url(): ?url {
        return new url('/admin/tool/musudo/index.php');
    }

    #[\Override]
    protected function check_access(): void {
        $this->require_capability('moodle/site:config');
    }

    #[\Override]
    protected function define(): void {
        $this->set_main_entity(new sudoer('sudoer'));
        $this->add_entity(new user('auser'), new join('sudoer', 'auser.id = sudoer.userid', inner: true));
        $this->set_row_muted('auser.suspended = 1', ['auser']);

        $this->set_default_columns([
            'auser:fullname',
            'auser:identityfields',
            'auser:tenant',
            'sudoer:note',
            'sudoer:mfarequired',
            'sudoer:privileges',
            'sudoer:actions',
        ]);
        $this->set_default_filters([
            'auser:fullname' => ['multisearch' => true],
            'auser:email' => ['multisearch' => true],
            'auser:tenant',
            'sudoer:note' => ['multisearch' => true],
            'sudoer:mfarequired',
        ]);
        $this->set_multisearch_label(new lang_string('searchusers', 'tool_mureport'));
        $this->set_export_allowed(false);
        $this->set_default_sorting(['auser:fullname' => 'asc']);
    }

    #[\Override]
    public function get_column_names(): array {
        return array_merge(parent::get_column_names(), ['auser:identityfields', 'auser:tenant']);
    }

    #[\Override]
    public function get_filter_names(): array {
        return array_merge(parent::get_filter_names(), ['auser:tenant']);
    }

    #[\Override]
    protected function define_columns(string $selector): array {
        if ($selector === 'auser:identityfields') {
            if ($this->is_inspectmode()) {
                $title = new lang_string('identityfields', 'tool_mureport');
                return [new column('auser', 'identityfields', $title, column::TYPE_PLACEHOLDER)];
            }
            $columns = [];
            foreach ($this->get_entity('auser')->get_identity_column_ids($this->get_context()) as $id) {
                $columns += parent::define_columns($id);
            }
            return $columns;
        }
        if ($selector === 'auser:tenant') {
            $syscontext = \core\context\system::instance();
            $name = fn(cell $cell, bool $escape): string => $cell->get_value() === null ? ''
                : format_string($cell->get_value(), true, ['context' => $syscontext, 'escape' => $escape]);
            $column = (new column('auser', 'tenant', new lang_string('tenant', 'tool_musudo'), column::TYPE_TEXT))
                ->add_field('tenant', '(SELECT t.name FROM {tool_mutenancy_tenant} t WHERE t.id = auser.tenantid)')
                ->set_sortable(true)
                ->set_available(fn(): bool => \tool_musudo\local\util::is_mutenancy_active())
                ->add_formatter(
                    'name',
                    fn(cell $cell): string => $name($cell, true),
                    fn(cell $cell): export_value => export_value::text($name($cell, false))
                )
                ->set_default_formatter('name');
            return ['auser:tenant' => $column];
        }
        return parent::define_columns($selector);
    }

    #[\Override]
    protected function define_filters(string $selector): array {
        if ($selector === 'auser:tenant') {
            $filter = (new filter\picker('auser', 'tenant', new lang_string('tenant', 'tool_musudo'), 'auser.tenantid'))
                ->set_integer_values()
                ->set_labels(fn(array $values): array => self::get_tenant_labels($values))
                ->set_available(fn(): bool => \tool_musudo\local\util::is_mutenancy_active());
            return ['auser:tenant' => $filter];
        }
        return parent::define_filters($selector);
    }

    /**
     * Returns tenant names.
     *
     * @param string[] $tenantids
     * @return array tenant id => plain text name
     */
    private static function get_tenant_labels(array $tenantids): array {
        global $DB;
        if (!$tenantids) {
            return [];
        }
        $syscontext = \core\context\system::instance();
        $records = $DB->get_records_list('tool_mutenancy_tenant', 'id', array_map('intval', $tenantids), '', 'id, name');
        return array_map(fn(stdClass $tenant): string
            => format_string($tenant->name, true, ['context' => $syscontext, 'escape' => false]), $records);
    }
}
