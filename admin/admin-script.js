(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {
        var dateFrom = document.getElementById('date_from');
        var dateTo = document.getElementById('date_to');

        if (dateFrom && dateTo) {
            var today = new Date().toISOString().split('T')[0];
            dateFrom.setAttribute('max', today);
            dateTo.setAttribute('max', today);

            dateFrom.addEventListener('change', function () {
                if (dateTo.value && dateFrom.value > dateTo.value) {
                    dateTo.value = dateFrom.value;
                }
                dateTo.setAttribute('min', dateFrom.value);
            });

            dateTo.addEventListener('change', function () {
                if (dateFrom.value && dateTo.value < dateFrom.value) {
                    dateFrom.value = dateTo.value;
                }
                dateFrom.setAttribute('max', dateTo.value);
            });

            if (dateFrom.value) {
                dateTo.setAttribute('min', dateFrom.value);
            }
            if (dateTo.value) {
                dateFrom.setAttribute('max', dateTo.value);
            }
        }

        // Confirm bulk delete
        var bulkForm = document.querySelector('.wa-submissions-wrap form');
        if (bulkForm) {
            bulkForm.addEventListener('submit', function (e) {
                var action = document.querySelector('select[name="action"]');
                if (action && action.value === 'delete') {
                    var checked = document.querySelectorAll('input[name="submission_ids[]"]:checked');
                    if (checked.length === 0) {
                        e.preventDefault();
                        alert('Please select at least one submission.');
                        return;
                    }
                    if (!confirm('Are you sure you want to delete ' + checked.length + ' submission(s)? This cannot be undone.')) {
                        e.preventDefault();
                    }
                }
            });
        }
    });
})();
