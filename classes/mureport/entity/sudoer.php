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

namespace tool_musudo\mureport\entity;

use tool_mureport\mureport\action;
use tool_mureport\mureport\cell;
use tool_mureport\mureport\column;
use tool_mureport\mureport\entity;
use tool_mureport\mureport\export_value;
use tool_mureport\mureport\filter;
use tool_mulib\local\sql;
use core\lang_string;
use core\url;
use stdClass;

/**
 * Privileged user entity, sudoers in tool_musudo_sudoer.
 *
 * @package     tool_musudo
 * @copyright   2026 Petr Skoda
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class sudoer extends entity {
    #[\Override]
    public function get_default_source(): sql {
        return new sql('{tool_musudo_sudoer}');
    }

    #[\Override]
    protected function get_default_title(): string {
        return get_string('sudoer', 'tool_musudo');
    }

    #[\Override]
    public function get_column_names(): array {
        return ['note', 'mfarequired', 'privileges', 'timecreated', 'actions'];
    }

    #[\Override]
    protected function define_columns(string $selector): array {
        $alias = $this->alias;
        $column = match ($selector) {
            'note' => $this->create_column('note', new lang_string('sudoer_note', 'tool_musudo'), column::TYPE_TEXT)
                ->add_field('note', "$alias.note"),
            'mfarequired' => $this->create_column(
                'mfarequired',
                new lang_string('mfarequired', 'tool_musudo'),
                column::TYPE_BOOLEAN
            )
                ->add_field('mfarequired', "$alias.mfarequired")
                ->set_sortable(true)
                ->set_available(fn(): bool => \tool_musudo\local\mfa::is_mfa_enabled()),
            'privileges' => $this->create_column('privileges', new lang_string('privileges', 'tool_musudo'), column::TYPE_TEXT)
                ->add_field('privilegesjson', "$alias.privilegesjson")
                ->add_formatter(
                    'list',
                    fn(cell $cell): string => \tool_musudo\local\sudoer::get_privileges_description($cell->get_fields(), true),
                    fn(cell $cell): export_value => export_value::text_from_html(
                        \tool_musudo\local\sudoer::get_privileges_description($cell->get_fields())
                    )
                )
                ->set_default_formatter('list'),
            'timecreated' => $this->create_column(
                'timecreated',
                new lang_string('timecreated', 'core_reportbuilder'),
                column::TYPE_TIMESTAMP
            )
                ->add_field('timecreated', "$alias.timecreated")
                ->set_sortable(true),
            'actions' => $this->define_actions_column(),
            default => null,
        };
        return $column ? [$column] : [];
    }

    #[\Override]
    public function get_filter_names(): array {
        return ['note', 'mfarequired'];
    }

    #[\Override]
    protected function define_filters(string $selector): array {
        $alias = $this->alias;
        $filter = match ($selector) {
            'note' => new filter\text($alias, 'note', new lang_string('sudoer_note', 'tool_musudo'), "$alias.note"),
            'mfarequired' => (new filter\boolean(
                $alias,
                'mfarequired',
                new lang_string('mfarequired', 'tool_musudo'),
                "$alias.mfarequired"
            ))
                ->set_available(fn(): bool => \tool_musudo\local\mfa::is_mfa_enabled()),
            default => null,
        };
        return $filter ? [$filter] : [];
    }

    /**
     * Define sudoer actions column, privileged users are managed by site administrators only.
     *
     * @return column
     */
    private function define_actions_column(): column {
        $alias = $this->alias;
        $column = $this->create_actions_column('actions', new lang_string('actions'))
            ->add_field('id', "$alias.id");
        $url = fn(string $script): \Closure => fn(stdClass $fields): url
            => new url("/admin/tool/musudo/management/$script.php", ['id' => $fields->id]);
        $column->add_action(new action('update', new lang_string('sudoer_update', 'tool_musudo'), 'i/settings'))
            ->set_allowed(is_siteadmin())
            ->set_url($url('sudoer_update'))
            ->set_dialog('lg');
        $column->add_action(new action('delete', new lang_string('sudoer_delete', 'tool_musudo'), 'i/delete'))
            ->set_allowed(is_siteadmin())
            ->set_url($url('sudoer_delete'))
            ->set_dialog('sm')
            ->set_destructive();
        return $column;
    }
}
