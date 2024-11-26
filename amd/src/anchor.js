// This file is part of Moodle - http://moodle.org/
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

/*
 * @package    local_wb_news
 * @copyright  Wunderbyte GmbH <info@wunderbyte.at>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

export const init = () => {
    // eslint-disable-next-line no-console
    console.log('anchor loaded');
    // Get the anchor from the URL (e.g., #instance-1)
    const hash = window.location.hash;

    if (hash) {
        // Select the element with the id matching the hash
        const targetElement = document.querySelector(hash);
        if (targetElement && targetElement.classList.contains('collapse')) {
            // Show the element by changing its display style
            targetElement.classList.add('show');
        }
    }

    // Optional: Toggle collapse on clicking the anchor link
    document.querySelectorAll('#page-local-wb_news-index a[href^="#"]').forEach(anchor => {
        anchor.addEventListener("click", (e) => {
            const target = document.querySelector(e.currentTarget.getAttribute("href"));
            if (target && target.classList.contains("collapse")) {
                // Toggle the display property
                target.style.display = target.style.display === "block" ? "none" : "block";
            }
        });
    });
};
