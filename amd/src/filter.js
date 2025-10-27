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
    ROOT: '[data-id="wb-categoryfilter-instance"]',
    ROWS: '[data-id="wb-categoryfilter-items"]',
    ITEM: '[data-category]',
    SLIDE: '.carousel-item'
};

function normalizeList(val) {
    return (val || '')
        .split(/[,;|]/)
        .map(s => s.trim())
        .filter(Boolean);
}

function buildDropdown(categories, onSelect) {
    const wrap = document.createElement('div');
    wrap.className = 'dropdown mb-3';
    wrap.innerHTML = `
<button class="btn btn-outline-secondary dropdown-toggle" type="button" data-toggle="dropdown" aria-expanded="false" data-id="wb-categoryfilter-button">Kategorie: Alle</button>
<ul class="dropdown-menu" data-id="wb-categoryfilter-menu"></ul>`;
    const ul = wrap.querySelector('[data-id="wb-categoryfilter-menu"]');

    const add = (label, value) => {
        const li = document.createElement('li');
        const a = document.createElement('a');
        a.className = 'dropdown-item';
        a.href = '#';
        a.dataset.value = value;
        a.textContent = label;
        li.appendChild(a);
        ul.appendChild(li);
    };

    add('Alle', '__all__');
    categories.forEach(c => add(c, c));

    wrap.addEventListener('click', e => {
        const a = e.target.closest('a.dropdown-item');
        if (!a) return;
        e.preventDefault();
        const value = a.dataset.value;
        const btn = wrap.querySelector('[data-id="wb-categoryfilter-button"]');
        btn.textContent = 'Kategorie: ' + (value === '__all__' ? 'Alle' : a.textContent);
        onSelect(value);
    });

    return wrap;
}

function updateSlidesVisibility(root) {
    const slides = Array.from(root.querySelectorAll(SELECTORS.SLIDE));
    let firstVisible = null;

    slides.forEach(slide => {
        const row = slide.querySelector(SELECTORS.ROWS);
        const visibleCount = row ? row.querySelectorAll(`${SELECTORS.ITEM}:not(.d-none)`).length : 0;
        const isActive = slide.classList.contains('active');
        const shouldHide = visibleCount === 0;
        slide.classList.toggle('d-none', shouldHide);
        if (shouldHide && isActive) {
            slide.classList.remove('active');
        }
        if (!shouldHide && !firstVisible) {
            firstVisible = slide;
        }
    });

    const anyActive = slides.some(s => s.classList.contains('active') && !s.classList.contains('d-none'));
    if (!anyActive && firstVisible) {
        firstVisible.classList.add('active');
    }
}

function setupOneCarousel(root) {
    const rows = Array.from(root.querySelectorAll(SELECTORS.ROWS));
    const items = rows.flatMap(r => Array.from(r.querySelectorAll(SELECTORS.ITEM)));

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
            el.classList.toggle('d-none', !cats.includes(value));
        });
        updateSlidesVisibility(root);
    });

    root.insertBefore(dropdown, root.firstElementChild || null);
    updateSlidesVisibility(root);
}

export const init = () => {
    const roots = Array.from(document.querySelectorAll(SELECTORS.ROOT));
    roots.forEach(root => {
        if (root.dataset.initialized) return;
        root.dataset.initialized = 'true';
        setupOneCarousel(root);
    });
};

