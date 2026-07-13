// Analytics dashboard: line charts (reuses the same canvas technique as the
// admin dashboard chart) + an hour-of-day heatmap row. No libraries.
(function () {
    'use strict';

    function css(name, fallback) {
        var v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return v || fallback;
    }

    function drawLine(canvas) {
        var series = JSON.parse(canvas.dataset.series || '[]');
        if (!series.length) return;
        var dpr = window.devicePixelRatio || 1;
        var cssWidth = canvas.clientWidth || canvas.parentElement.clientWidth;
        var cssHeight = parseInt(canvas.getAttribute('height'), 10) || 180;
        canvas.width = cssWidth * dpr;
        canvas.height = cssHeight * dpr;
        var ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);

        var pad = { l: 34, r: 10, t: 12, b: 22 };
        var w = cssWidth - pad.l - pad.r;
        var h = cssHeight - pad.t - pad.b;
        var max = Math.max.apply(null, series.map(function (p) { return p.value; }).concat([4]));
        var stepY = Math.ceil(max / 4) || 1;
        max = stepY * 4;

        var textColor = css('--color-text-muted', '#6b7280');
        var gridColor = css('--color-border', '#e5e7eb');
        var lineColor = css('--color-primary', '#4f46e5');

        ctx.clearRect(0, 0, cssWidth, cssHeight);
        ctx.font = '11px sans-serif';
        ctx.fillStyle = textColor;
        ctx.strokeStyle = gridColor;
        ctx.lineWidth = 1;

        for (var g = 0; g <= 4; g++) {
            var yv = stepY * g;
            var y = pad.t + h - (yv / max) * h;
            ctx.beginPath();
            ctx.moveTo(pad.l, y);
            ctx.lineTo(pad.l + w, y);
            ctx.stroke();
            ctx.fillText(String(yv), 4, y + 4);
        }
        series.forEach(function (p, i) {
            if (series.length > 14 && i % Math.ceil(series.length / 8) !== 0 && i !== series.length - 1) return;
            var x = pad.l + (series.length > 1 ? (i / (series.length - 1)) * w : 0);
            ctx.fillText(p.label, x - 10, cssHeight - 6);
        });

        ctx.beginPath();
        series.forEach(function (p, i) {
            var x = pad.l + (series.length > 1 ? (i / (series.length - 1)) * w : 0);
            var y = pad.t + h - (p.value / max) * h;
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        });
        ctx.lineTo(pad.l + w, pad.t + h);
        ctx.lineTo(pad.l, pad.t + h);
        ctx.closePath();
        ctx.fillStyle = lineColor + '22';
        ctx.fill();

        ctx.beginPath();
        ctx.strokeStyle = lineColor;
        ctx.lineWidth = 2;
        series.forEach(function (p, i) {
            var x = pad.l + (series.length > 1 ? (i / (series.length - 1)) * w : 0);
            var y = pad.t + h - (p.value / max) * h;
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        });
        ctx.stroke();
    }

    ['chart-dau', 'chart-wau', 'chart-views'].forEach(function (id) {
        var el = document.getElementById(id);
        if (el) { drawLine(el); window.addEventListener('resize', function () { drawLine(el); }); }
    });

    var heatmap = document.getElementById('heatmap');
    if (heatmap) {
        var values = JSON.parse(heatmap.dataset.values || '[]');
        var max = Math.max.apply(null, values.concat([1]));
        values.forEach(function (v, hour) {
            var cell = document.createElement('div');
            cell.className = 'heatmap-cell';
            var intensity = max > 0 ? v / max : 0;
            cell.style.background = 'color-mix(in srgb, var(--color-primary) ' + Math.round(intensity * 90) + '%, var(--color-surface-2))';
            cell.title = hour + ':00 — ' + v + ' views';
            cell.textContent = hour;
            heatmap.appendChild(cell);
        });
    }
})();
