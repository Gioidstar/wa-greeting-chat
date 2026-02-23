(function () {
    'use strict';

    var data = window.waDashboard || {};

    document.addEventListener('DOMContentLoaded', function () {
        renderSummaryCards();
        renderServiceChart();
        renderMonthlyChart();
        renderTopCompanies();
    });

    function renderSummaryCards() {
        var s = data.summary || {};
        animateCount('card-total-all', s.total_all || 0);
        animateCount('card-total-month', s.total_month || 0);
        animateCount('card-total-week', s.total_week || 0);
        animateCount('card-total-today', s.total_today || 0);
    }

    function animateCount(id, target) {
        var el = document.getElementById(id);
        if (!el) return;
        target = parseInt(target, 10);
        if (target === 0) {
            el.textContent = '0';
            return;
        }
        var current = 0;
        var step = Math.max(1, Math.floor(target / 30));
        var interval = setInterval(function () {
            current += step;
            if (current >= target) {
                current = target;
                clearInterval(interval);
            }
            el.textContent = current.toLocaleString();
        }, 30);
    }

    function renderServiceChart() {
        var groups = data.serviceGroups || [];
        var canvas = document.getElementById('wa-service-chart');
        var emptyMsg = document.getElementById('wa-service-empty');

        if (!canvas) return;

        if (groups.length === 0) {
            canvas.style.display = 'none';
            if (emptyMsg) emptyMsg.style.display = 'block';
            return;
        }

        var labels = groups.map(function (g) { return g.label; });
        var values = groups.map(function (g) { return g.total; });

        var colors = [
            '#3b82f6', '#ef4444', '#10b981', '#f59e0b', '#8b5cf6',
            '#ec4899', '#06b6d4', '#84cc16', '#f97316', '#6366f1',
            '#14b8a6', '#e11d48', '#a855f7', '#0ea5e9', '#22c55e'
        ];

        new Chart(canvas, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: values,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 16,
                            usePointStyle: true,
                            pointStyleWidth: 12,
                            font: { size: 12 }
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var total = ctx.dataset.data.reduce(function (a, b) { return a + b; }, 0);
                                var pct = ((ctx.raw / total) * 100).toFixed(1);
                                return ctx.label + ': ' + ctx.raw + ' (' + pct + '%)';
                            }
                        }
                    }
                }
            }
        });
    }

    function renderMonthlyChart() {
        var trend = data.monthlyTrend || {};
        var canvas = document.getElementById('wa-monthly-chart');
        if (!canvas) return;

        new Chart(canvas, {
            type: 'line',
            data: {
                labels: trend.labels || [],
                datasets: [{
                    label: 'Submissions',
                    data: trend.data || [],
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.1)',
                    borderWidth: 2,
                    fill: true,
                    tension: 0.3,
                    pointBackgroundColor: '#3b82f6',
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            stepSize: 1,
                            font: { size: 12 }
                        },
                        grid: { color: 'rgba(0,0,0,0.06)' }
                    },
                    x: {
                        ticks: { font: { size: 11 } },
                        grid: { display: false }
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                return ctx.raw + ' submission' + (ctx.raw !== 1 ? 's' : '');
                            }
                        }
                    }
                }
            }
        });
    }

    function renderTopCompanies() {
        var companies = data.topCompanies || [];
        var tbody = document.getElementById('wa-top-companies-body');
        if (!tbody) return;

        if (companies.length === 0) {
            tbody.innerHTML = '<tr><td colspan="3" style="text-align:center;color:#646970;">No data available.</td></tr>';
            return;
        }

        var html = '';
        companies.forEach(function (c, i) {
            html += '<tr>';
            html += '<td>' + (i + 1) + '</td>';
            html += '<td><strong>' + escapeHtml(c.company) + '</strong></td>';
            html += '<td>' + c.total + '</td>';
            html += '</tr>';
        });
        tbody.innerHTML = html;
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.appendChild(document.createTextNode(str));
        return div.innerHTML;
    }
})();
