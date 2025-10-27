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
/*
 * @package    local_wb_news
 * @copyright  Wunderbyte GmbH
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const SELECTORS = {
    ALLINSTANCES: '[data-id="wb-categoryfilter-instance"]',
    ITEMCONTAINER: '[data-id="wb-categoryfilter-items"]',
};

function buildDropdown(categories, onSelect) {
    const wrapper = document.createElement('div');
    wrapper.className = 'dropdown mb-3';
    wrapper.innerHTML = `
<button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-id="wb-categoryfilter-button">Kategorie: Alle</button>
<ul class="dropdown-menu" data-id="wb-categoryfilter-menu"></ul>`;
    const ul = wrapper.querySelector('[data-id="wb-categoryfilter-menu"]');

    const addItem = (label, value) => {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.className = 'dropdown-item';
        a.href = '#';
        a.setAttribute('data-value', value);
        a.textContent = label;
        li.appendChild(a);
        ul.appendChild(li);
    };

    addItem('Alle', '__all__');
    categories.forEach(c => addItem(c, c));

    wrapper.addEventListener('click', e => {
        const a = e.target.closest('a.dropdown-item');
        if (!a) return;
        e.preventDefault();
        const value = a.getAttribute('data-value');
        const btn = wrapper.querySelector('[data-id="wb-categoryfilter-button"]');
        btn.textContent = 'Kategorie: ' + (value === '__all__' ? 'Alle' : a.textContent);
        onSelect(value);
    });

    return wrapper;
}

function normalizeList(val) {
    return (val || '')
        .split(/[,;|]/)
        .map(s => s.trim())
        .filter(Boolean);
}

function setupInstance(container) {
    const listRoot = container.querySelector(SELECTORS.ITEMCONTAINER) || container;
    const items = Array.from(listRoot.querySelectorAll('[data-category]'));

    const categories = Array.from(new Set(
        items.flatMap(el => normalizeList(el.getAttribute('data-category')))
    )).sort((a, b) => a.localeCompare(b));

    const dropdown = buildDropdown(categories, value => {
        items.forEach(el => {
            if (value === '__all__') {
                el.classList.remove('d-none');
                return;
            }
            const cats = normalizeList(el.getAttribute('data-category'));
            const match = cats.includes(value);
            el.classList.toggle('d-none', !match);
        });
    });

    container.insertBefore(dropdown, listRoot);
}

export const init = () => {
    const containers = document.querySelectorAll(SELECTORS.ALLINSTANCES);
    containers.forEach(container => {
        if (container.dataset.initialized) return;
        container.dataset.initialized = 'true';
        setupInstance(container);
        container.addEventListener('click', e => {
            const t = e.target;
            if (t && t.dataset && t.dataset.action) {
                // reserviert für spätere Aktionen; aktuell nicht benötigt
            }
        });
    });
};
