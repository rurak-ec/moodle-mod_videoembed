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
 * YouTube IFrame API error handling for the Video activity.
 *
 * Coordinates with the YouTube IFrame API (loaded from PHP via $PAGE->requires->js)
 * and surfaces a localised warning when the embedded player reports an error.
 *
 * @module     mod_videoembed/player
 * @copyright  2026 Rurak
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Reveal the warning box with a message mapped from a YouTube error code.
 *
 * @param {String} warningId DOM id of the warning box.
 * @param {Object} errorMap Map of YouTube error code (string) to message.
 * @param {String} genericMessage Fallback message for unmapped codes.
 * @param {(Number|String)} code The YouTube error code.
 */
const showWarning = (warningId, errorMap, genericMessage, code) => {
    const node = document.getElementById(warningId);
    if (!node) {
        return;
    }
    node.textContent = errorMap[String(code)] || genericMessage;
    node.removeAttribute('hidden');
};

/**
 * Initialise the YouTube player error handler.
 *
 * @param {String} playerId DOM id of the YouTube iframe.
 * @param {String} warningId DOM id of the warning box.
 * @param {Object} errorMap Map of YouTube error code (string) to localised message.
 * @param {String} genericMessage Fallback message for unmapped codes.
 */
export const init = (playerId, warningId, errorMap, genericMessage) => {
    let playerInstance = null;

    const buildPlayer = () => {
        if (playerInstance || !window.YT || !window.YT.Player) {
            return;
        }
        if (!document.getElementById(playerId)) {
            return;
        }
        playerInstance = new window.YT.Player(playerId, {
            events: {
                'onError': (event) => showWarning(warningId, errorMap, genericMessage, event.data),
            },
        });
    };

    // Chain onto any existing handler so multiple players on a page all initialise.
    const previousReady = window.onYouTubeIframeAPIReady;
    window.onYouTubeIframeAPIReady = () => {
        if (typeof previousReady === 'function') {
            previousReady();
        }
        buildPlayer();
    };

    // The API may already be loaded (for example a second activity on the page).
    if (window.YT && window.YT.Player) {
        buildPlayer();
    }
};
