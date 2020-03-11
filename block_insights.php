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
 * Form for editing Analytics Insight block instances.
 *
 * @package   block_insights
 * @copyright 2020 NYIAJ LLC
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class block_insights extends block_base {

    function init() {
        $this->title = get_string('pluginname', 'block_insights');
    }

    function has_config() {
        return true;
    }

    function applicable_formats() {
        return ['all' => true];
    }

    function specialization() {
        if (isset($this->config->title)) {
            $this->title = $this->title = format_string($this->config->title, true, ['context' => $this->context]);
        } else {
            $this->title = get_string('pluginname', 'block_insights');
        }
    }

    function instance_allow_multiple() {
        return true;
    }

    function get_content() {
        global $CFG, $PAGE;

        if ($this->content !== NULL) {
            return $this->content;
        }

        $page = optional_param('page', 0, PARAM_INT);
        $perpage = optional_param('perpage', 100, PARAM_INT);

        if ($perpage > 1000) {
            $perpage = 1000;
        }

        $context = context::instance_by_id($this->instance->parentcontextid);

        $modelid = 5;

        $renderer = $PAGE->get_renderer('report_insights');
        $model = new \core_analytics\model($modelid);

        // Get all models that are enabled, trained and have predictions at this context.
        $othermodels = \core_analytics\manager::get_all_models(true, true, $context);
        array_filter($othermodels, function($model) use ($context) {

            // Discard insights that are not linked unless you are a manager.
            if (!$model->get_target()->link_insights_report()) {
                try {
                    \core_analytics\manager::check_can_manage_models();
                } catch (\required_capability_exception $e) {
                    return false;
                }
            }
            return true;
        });

        if (!$modelid && count($othermodels)) {
            // Autoselect the only available model.
            $model = reset($othermodels);
            $modelid = $model->get_id();
        }
        if ($modelid) {
            unset($othermodels[$modelid]);
        }

        $renderable = new \report_insights\output\insights_list($model, $context, $othermodels, $page, $perpage);

        $this->content = new stdClass;
        $this->content->text = $renderer->render($renderable);;
        $this->content->footer = '';

        return $this->content;
    }
}
