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

/**
 * Reusable card renderer for SCEH pages.
 *
 * @package    local_sceh_rules
 * @copyright  2026 SCEH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_sceh_rules\output;

defined('MOODLE_INTERNAL') || die();

/**
 * Pragmatic Phase 1 card renderer.
 */
class sceh_card {
    /**
     * Render a simple navigation card.
     *
     * @param array $config
     * @return string
     */
    public static function simple(array $config): string {
        $title = (string)($config['title'] ?? '');
        $icon = (string)($config['icon'] ?? 'fa-circle');
        $color = (string)($config['color'] ?? 'blue');
        $url = self::normalize_url($config['url'] ?? '#');
        $sizeclass = self::size_class($config['size'] ?? 'small');
        $subtitle = (string)($config['subtitle'] ?? '');

        $inner = self::render_icon($icon, '3x');
        $inner .= \html_writer::div($title, 'sceh-card-title');
        if ($subtitle !== '') {
            $inner .= \html_writer::div($subtitle, 'sceh-card-subtitle');
        }

        if (!empty($config['value']) || !empty($config['label'])) {
            $inner .= self::render_stat((string)($config['value'] ?? ''), (string)($config['label'] ?? ''));
        }

        if (!empty($config['url'])) {
            $inner = \html_writer::tag('a', $inner, [
                'href' => $url->out(false),
                'class' => 'sceh-card-link',
            ]);
        }

        return \html_writer::div($inner, 'sceh-card sceh-card-' . $color . ' ' . $sizeclass);
    }

    /**
     * Render a metric card (deferred implementation placeholder).
     *
     * @param array $config
     * @return string
     */
    public static function metric(array $config): string {
        $title = (string)($config['title'] ?? '');
        $value = (string)($config['value'] ?? '-');
        $trend = (string)($config['trend'] ?? '');
        $details = (string)($config['details'] ?? '');
        $status = (string)($config['status'] ?? 'info');
        $sizeclass = self::size_class($config['size'] ?? 'small');
        $icon = (string)($config['icon'] ?? 'fa-chart-bar');
        $url = self::normalize_url($config['url'] ?? '#');

        $content = \html_writer::div(
            self::render_icon($icon, 'lg') . \html_writer::span($title),
            'sceh-card-header'
        );
        $content .= \html_writer::div($value, 'sceh-metric-value');
        if ($trend !== '') {
            $content .= \html_writer::div($trend, 'sceh-metric-trend');
        }
        if ($details !== '') {
            $content .= self::status_indicator($status, $details);
        }

        if (!empty($config['url'])) {
            $content = \html_writer::tag('a', $content, [
                'href' => $url->out(false),
                'class' => 'sceh-card-link',
            ]);
        }

        return \html_writer::div($content, 'sceh-card sceh-card-system ' . $sizeclass);
    }

    /**
     * Render a list card (deferred implementation placeholder).
     *
     * @param array $config
     * @return string
     */
    public static function list(array $config): string {
        $title = (string)($config['title'] ?? '');
        $icon = (string)($config['icon'] ?? 'fa-list');
        $sizeclass = self::size_class($config['size'] ?? 'medium');
        $status = (string)($config['status'] ?? 'info');
        $count = isset($config['count']) ? (string)$config['count'] : '';
        $items = $config['items'] ?? [];
        $footeractions = $config['footer_actions'] ?? [];
        $badges = $config['badges'] ?? [];

        $headerconfig = [
            'title' => $title,
            'icon' => $icon,
            'count' => $count,
            'badges' => $badges,
        ];
        $content = self::render_card_header($headerconfig);

        $listitemshtml = '';
        foreach ($items as $item) {
            $listitemshtml .= self::render_list_item($item);
        }
        $content .= \html_writer::div($listitemshtml, 'sceh-list-items');

        if (!empty($footeractions)) {
            $actionhtml = '';
            foreach ($footeractions as $action) {
                $actionhtml .= self::render_button(
                    (string)($action['text'] ?? ''),
                    self::normalize_url($action['url'] ?? '#'),
                    (string)($action['style'] ?? 'secondary'),
                    (array)($action['attributes'] ?? [])
                );
            }
            $content .= \html_writer::div($actionhtml, 'sceh-card-actions');
        }

        $content .= self::status_indicator($status, (string)($config['status_text'] ?? ''));

        return \html_writer::div($content, 'sceh-card sceh-card-system ' . $sizeclass);
    }

    /**
     * Render a detail card (deferred implementation placeholder).
     *
     * @param array $config
     * @return string
     */
    public static function detail(array $config): string {
        $sizeclass = self::size_class($config['size'] ?? 'large');
        $status = (string)($config['status'] ?? 'info');

        $content = self::render_card_header([
            'title' => (string)($config['title'] ?? ''),
            'icon' => (string)($config['icon'] ?? 'fa-info-circle'),
            'subtitle' => (string)($config['subtitle'] ?? ''),
            'badges' => $config['badges'] ?? [],
        ]);

        $stats = $config['stats'] ?? [];
        if (!empty($stats)) {
            $stathtml = '';
            foreach ($stats as $stat) {
                $stathtml .= self::render_stat(
                    (string)($stat['value'] ?? ''),
                    (string)($stat['label'] ?? '')
                );
            }
            $content .= \html_writer::div($stathtml, 'sceh-stat-grid');
        }

        $sections = $config['sections'] ?? [];
        foreach ($sections as $section) {
            $sectiontitle = (string)($section['title'] ?? '');
            $sectioncontent = $section['content'] ?? '';
            $content .= \html_writer::start_div('sceh-detail-section');
            if ($sectiontitle !== '') {
                $content .= \html_writer::div($sectiontitle, 'sceh-detail-section-title');
            }
            $content .= \html_writer::div((string)$sectioncontent, 'sceh-detail-section-content');
            $content .= \html_writer::end_div();
        }

        $actions = $config['actions'] ?? [];
        if (!empty($actions)) {
            $actionhtml = '';
            foreach ($actions as $action) {
                $actionhtml .= self::render_button(
                    (string)($action['text'] ?? ''),
                    self::normalize_url($action['url'] ?? '#'),
                    (string)($action['style'] ?? 'secondary'),
                    (array)($action['attributes'] ?? [])
                );
            }
            $content .= \html_writer::div($actionhtml, 'sceh-card-actions');
        }

        $content .= self::status_indicator($status, (string)($config['status_text'] ?? ''));

        return \html_writer::div($content, 'sceh-card sceh-card-system ' . $sizeclass);
    }

    /**
     * Render an icon block.
     *
     * @param string $icon
     * @param string $size
     * @return string
     */
    private static function render_icon(string $icon, string $size = 'lg'): string {
        return \html_writer::div(
            \html_writer::tag('i', '', ['class' => 'fa ' . $icon . ' fa-' . $size]),
            'sceh-card-icon'
        );
    }

    /**
     * Render a badge.
     *
     * @param string $text
     * @param string $type
     * @return string
     */
    private static function render_badge(string $text, string $type = 'info'): string {
        return \html_writer::tag('span', $text, ['class' => 'badge text-bg-' . $type]);
    }

    /**
     * Render a button.
     *
     * @param string $text
     * @param \moodle_url $url
     * @param string $style
     * @return string
     */
    private static function render_button(
        string $text,
        \moodle_url $url,
        string $style = 'primary',
        array $attributes = []
    ): string {
        $attributes['class'] = trim(($attributes['class'] ?? '') . ' btn btn-' . $style);
        return \html_writer::link($url, $text, $attributes);
    }

    /**
     * Render a stat pair.
     *
     * @param string $value
     * @param string $label
     * @return string
     */
    private static function render_stat(string $value, string $label): string {
        $html = \html_writer::div(
            \html_writer::div($value, 'sceh-stat-value') .
            \html_writer::div($label, 'sceh-stat-label'),
            'sceh-stat'
        );
        return $html;
    }

    /**
     * Render a reusable card header.
     *
     * @param array $config
     * @return string
     */
    private static function render_card_header(array $config): string {
        $title = (string)($config['title'] ?? '');
        $icon = (string)($config['icon'] ?? '');
        $subtitle = (string)($config['subtitle'] ?? '');
        $count = (string)($config['count'] ?? '');
        $badges = $config['badges'] ?? [];

        $headerleft = '';
        if ($icon !== '') {
            $headerleft .= self::render_icon($icon, 'lg');
        }
        $headerleft .= \html_writer::span($title, 'sceh-header-title');
        if ($count !== '') {
            $headerleft .= \html_writer::span(' (' . $count . ')', 'sceh-header-count');
        }

        $headerright = '';
        foreach ($badges as $badge) {
            $headerright .= self::render_badge(
                (string)($badge['text'] ?? ''),
                (string)($badge['type'] ?? 'info')
            );
        }

        $html = \html_writer::div(
            \html_writer::div($headerleft, 'sceh-header-left') .
            \html_writer::div($headerright, 'sceh-header-right'),
            'sceh-card-header'
        );
        if ($subtitle !== '') {
            $html .= \html_writer::div($subtitle, 'sceh-card-subtitle');
        }
        return $html;
    }

    /**
     * Render list item content.
     *
     * @param array $item
     * @return string
     */
    private static function render_list_item(array $item): string {
        $icon = (string)($item['icon'] ?? 'fa-circle');
        $text = (string)($item['text'] ?? '');
        $subtext = (string)($item['subtext'] ?? '');
        $actions = $item['actions'] ?? [];

        $html = \html_writer::start_div('sceh-list-item');
        $html .= \html_writer::div(
            self::render_icon($icon, 'lg') .
            \html_writer::div(
                \html_writer::div($text, 'sceh-list-item-text') .
                \html_writer::div($subtext, 'sceh-list-item-subtext'),
                'sceh-list-item-body'
            ),
            'sceh-list-item-main'
        );

        if (!empty($actions)) {
            $actionhtml = '';
            foreach ($actions as $action) {
                $actionhtml .= self::render_button(
                    (string)($action['text'] ?? ''),
                    self::normalize_url($action['url'] ?? '#'),
                    (string)($action['style'] ?? 'link'),
                    (array)($action['attributes'] ?? [])
                );
            }
            $html .= \html_writer::div($actionhtml, 'sceh-list-item-actions');
        }
        $html .= \html_writer::end_div();

        return $html;
    }

    /**
     * Render status indicator.
     *
     * @param string $status
     * @param string $text
     * @param string|null $details
     * @return string
     */
    private static function status_indicator(string $status, string $text, ?string $details = null): string {
        if ($text === '' && ($details === null || $details === '')) {
            return '';
        }

        $icon = 'fa-circle-info';
        if ($status === 'success') {
            $icon = 'fa-circle-check';
        } else if ($status === 'warning') {
            $icon = 'fa-triangle-exclamation';
        } else if ($status === 'danger') {
            $icon = 'fa-circle-xmark';
        }

        $content = self::render_icon($icon, 'lg') . \html_writer::span($text);
        if ($details !== null && $details !== '') {
            $content .= \html_writer::div($details, 'sceh-status-details');
        }

        return \html_writer::div($content, 'sceh-status sceh-status-' . $status);
    }

    /**
     * Normalize URL values to moodle_url.
     *
     * @param mixed $url
     * @return \moodle_url
     */
    private static function normalize_url($url): \moodle_url {
        if ($url instanceof \moodle_url) {
            return $url;
        }
        return new \moodle_url((string)$url);
    }

    /**
     * Map configured size to CSS class.
     *
     * @param string $size
     * @return string
     */
    private static function size_class(string $size): string {
        if ($size === 'small') {
            return 'sceh-card-small';
        } else if ($size === 'large') {
            return 'sceh-card-large';
        } else if ($size === 'full') {
            return 'sceh-card-full';
        }
        return 'sceh-card-medium';
    }
}
