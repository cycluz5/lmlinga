/**
 * Interactive regression for resources/js/pages/dashboard-sidebar.js
 *
 * Runtime: Node.js built-in test runner (node:test) — no Vitest/Jest/Playwright.
 * The project has no existing frontend test framework; this keeps coverage
 * zero-dependency beyond Node itself.
 */

import assert from 'node:assert/strict';
import { describe, it } from 'node:test';
import { pathToFileURL } from 'node:url';
import path from 'node:path';

import {
    mountHealthRecordsFixture,
    paintedChildLabels,
    templateCount,
} from './support/sidebar-mini-dom.mjs';

const sidebarModuleUrl = pathToFileURL(
    path.resolve('resources/js/pages/dashboard-sidebar.js')
).href;

const { initDashboardSidebarCollapseActiveState } = await import(sidebarModuleUrl);

describe('dashboard-sidebar Health Records toggle', () => {
    it('expands from template without marking Health Records route-active', () => {
        const { doc, row, toggle, panel } = mountHealthRecordsFixture({
            hasActiveChild: false,
            startExpanded: false,
            dashboardActive: true,
        });

        initDashboardSidebarCollapseActiveState(doc);

        assert.equal(toggle.getAttribute('aria-expanded'), 'false');
        assert.equal(panel.hasAttribute('hidden'), true);
        assert.equal(templateCount(panel), 1);
        assert.deepEqual(paintedChildLabels(panel), []);
        assert.equal(row.classList.contains('lml-sidebar__link--parent-active'), false);
        assert.equal(row.classList.contains('lml-sidebar__link--parent-expanded'), false);
        assert.equal(doc.querySelectorAll('[aria-current="page"]').length, 1);

        toggle.focus();
        toggle.click(1);

        assert.equal(toggle.getAttribute('aria-expanded'), 'true');
        assert.equal(panel.hasAttribute('hidden'), false);
        assert.equal(panel.classList.contains('is-open'), true);
        assert.equal(panel.getAttribute('aria-hidden'), 'false');
        assert.equal(templateCount(panel), 0);
        assert.deepEqual(paintedChildLabels(panel), [
            'Child Care',
            'Risk Assessment',
            'Family Planning',
            'Maternal',
            'Death',
        ]);
        assert.equal(row.classList.contains('lml-sidebar__link--parent-active'), false);
        assert.equal(row.classList.contains('lml-sidebar__link--parent-expanded'), false);
        assert.equal(doc.querySelectorAll('[aria-current="page"]').length, 1);
        assert.equal(
            doc.querySelector('.lml-sidebar__link--active')?.getAttribute('aria-current'),
            'page'
        );
    });

    it('collapses without duplicate materialization on repeated toggles', () => {
        const { doc, toggle, panel } = mountHealthRecordsFixture({
            startExpanded: false,
            dashboardActive: true,
        });

        initDashboardSidebarCollapseActiveState(doc);

        toggle.click(1);
        assert.equal(paintedChildLabels(panel).length, 5);
        assert.equal(templateCount(panel), 0);

        toggle.click(1);
        assert.equal(toggle.getAttribute('aria-expanded'), 'false');
        assert.equal(panel.hasAttribute('hidden'), true);
        assert.equal(panel.classList.contains('is-open'), false);
        assert.equal(panel.getAttribute('aria-hidden'), 'true');
        // Children remain in the light DOM but panel is hidden; template is not recreated.
        assert.equal(templateCount(panel), 0);
        assert.equal(paintedChildLabels(panel).length, 5);

        toggle.click(1);
        assert.equal(toggle.getAttribute('aria-expanded'), 'true');
        assert.equal(templateCount(panel), 0);
        assert.equal(paintedChildLabels(panel).length, 5);
    });

    it('keeps the active child as the only aria-current when expanded with active-child context', () => {
        const { doc, row, toggle, panel } = mountHealthRecordsFixture({
            hasActiveChild: true,
            startExpanded: true,
            activeChildLabel: 'Child Care',
            dashboardActive: false,
        });

        initDashboardSidebarCollapseActiveState(doc);

        assert.equal(toggle.getAttribute('aria-expanded'), 'true');
        assert.equal(panel.classList.contains('is-open'), true);
        assert.equal(row.classList.contains('lml-sidebar__link--parent-expanded'), true);
        assert.equal(row.classList.contains('lml-sidebar__link--parent-active'), false);
        assert.equal(doc.querySelectorAll('[aria-current="page"]').length, 1);
        assert.equal(
            panel.querySelector('.lml-sidebar__sublink--active')?.getAttribute('aria-current'),
            'page'
        );

        toggle.click(1);

        assert.equal(toggle.getAttribute('aria-expanded'), 'false');
        assert.equal(panel.hasAttribute('hidden'), true);
        assert.equal(row.classList.contains('lml-sidebar__link--parent-active'), true);
        assert.equal(row.classList.contains('lml-sidebar__link--parent-expanded'), false);
        // Child keeps aria-current in markup; parent does not receive aria-current.
        assert.equal(row.querySelector('[aria-current="page"]'), null);
        assert.equal(doc.querySelectorAll('[aria-current="page"]').length, 1);
    });

    it('preserves focus on keyboard-style activation (detail === 0)', () => {
        const { doc, toggle } = mountHealthRecordsFixture({
            startExpanded: false,
            dashboardActive: true,
        });

        initDashboardSidebarCollapseActiveState(doc);
        toggle.focus();
        assert.equal(doc.activeElement, toggle);

        toggle.click(0);

        assert.equal(toggle.getAttribute('aria-expanded'), 'true');
        assert.equal(doc.activeElement, toggle);
    });
});
