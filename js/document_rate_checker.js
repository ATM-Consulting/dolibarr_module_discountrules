/**
 * @file Handles real-time validation of margin/markup rates on Dolibarr document lines.
 * This version maps data using the database rowid found within each table row.
 * @author ATM Consulting
 * @version 1.1.0
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
            const { minRate, rateType, imgWarning } = trElement.dataset;
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

        // --- Execution Sequence ---
        attachDataToDomRows();
        document.querySelectorAll('tr[id^="row"]').forEach(checkLineRate);

        const table = document.getElementById('tablelines');
        if (table) {
            table.addEventListener('change', (event) => {
                if (event.target.matches('input[name^="qty"], input[name^="price"], input[name^="remise_percent"]')) {
                    const changedRow = event.target.closest('tr');
                    if (changedRow) {
                        setTimeout(() => checkLineRate(changedRow), 100);
                    }
                }
            });
        }
    });
})();