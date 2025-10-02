/**
 * Copyright (C) 2025 Quentin VIAL--GOUTEYRON
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 3 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with this program. If not, see <http://www.gnu.org/licenses/>.
 */

(() => {
    document.addEventListener('DOMContentLoaded', () => {
        const dataElement = document.getElementById('discountrules-pagedata');
        if (!dataElement) return;

        const pageData = JSON.parse(dataElement.textContent);
        const linesData = pageData.lines; // Keyed by database rowid

        /**
         * Attaches rate metadata to DOM rows by matching the database rowid.
         * This is more robust than relying on a simple counter.
         */
        const attachDataToDomRows = () => {
            for (const lineNum in linesData) {
                const data = linesData[lineNum];
                const row = document.getElementById(`row-${lineNum}`);
                if (row) {
                    row.dataset.minRate = data.minRate;
                    row.dataset.rateType = data.rateType;
                    row.dataset.imgWarning = data.imgWarning;
                }
            }
        };

        /**
         * Analyzes a single table row and displays the pre-generated warning if necessary.
         * @param {HTMLElement} trElement - The <tr> element of the line to check.
         */
        const checkLineRate = (trElement) => {
            const {minRate, rateType, imgWarning} = trElement.dataset;
            if (minRate === undefined || rateType === undefined) return;
            const targetTd = trElement.querySelector(
                rateType === 'MarginRate' ? '.linecolmargin2' : '.linecolmark1'
            );
            if (!targetTd) return;

            targetTd.querySelector('.discountrules-warning')?.remove();

            const rawText = targetTd.textContent.trim();
            const currentValue = parseFloat(rawText.replace(',', '.').replace('%', ''));

            if (!isNaN(currentValue) && currentValue < parseFloat(minRate)) {
                // The warning HTML is already fully prepared, we just need to append it.
                // We use a temporary div to parse the HTML string into a DOM element.
                const tempDiv = document.createElement('div');
                tempDiv.innerHTML = imgWarning.trim();
                const warningElement = tempDiv.firstChild;

                if (warningElement) {
                    warningElement.classList.add('discountrules-warning');
                    warningElement.style.marginLeft = '4px';
                    targetTd.append(warningElement);
                }
            }
        };

        attachDataToDomRows();
        document.querySelectorAll('tr[id^="row"]').forEach(checkLineRate);

        $(document).ajaxComplete(function (event, xhr, settings) {
            if (settings.url.includes('/quickcustomerprice/script/interface.php')) {
                $('tr[id^="row-"]').each(function () {
                    checkLineRate(this);
                });
            }
        });
    });
})();