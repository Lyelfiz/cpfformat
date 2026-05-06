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
 * AJAX handler for Brazilian cities autocomplete search.
 *
 * @module local_cpfformat/search_cities
 * @copyright 2024
 * @license http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'core/ajax'], function($, ajax) {
    return {
        handler: function(query, callback) {
            if (query.length < 2) {
                callback([]);
                return;
            }

            ajax.call([
                {
                    methodname: 'local_cpfformat_cities',
                    args: {
                        query: query
                    },
                    done: function(results) {
                        callback(results);
                    },
                    fail: function(err) {
                        console.error('Error loading cities:', err);
                        callback([]);
                    }
                }
            ]);
        }
    };
});
