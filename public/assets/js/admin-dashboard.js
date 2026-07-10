// Admin dashboard: hand-drawn 30-day logins line chart on <canvas>.
(function () {
    'use strict';
    var canvas = document.getElementById('logins-chart');
    if (!canvas) return;
    var series = JSON.parse(canvas.dataset.series || '[]');
    if (!series.length) return;

    function css(name, fallback) {
        var v = getComputedStyle(document.documentElement).getPropertyValue(name).trim();
        return v || fallback;
    }

    function draw() {
        var dpr = window.devicePixelRatio || 1;
        var cssWidth = canvas.clientWidth || canvas.parentElement.clientWidth;
        var cssHeight = 220;
        canvas.width = cssWidth * dpr;
        canvas.height = cssHeight * dpr;
        var ctx = canvas.getContext('2d');
        ctx.scale(dpr, dpr);

        var pad = { l: 34, r: 10, t: 12, b: 26 };
        var w = cssWidth - pad.l - pad.r;
        var h = cssHeight - pad.t - pad.b;
        var max = Math.max.apply(null, series.map(function (p) { return p.value; }).concat([4]));
        var stepY = Math.ceil(max / 4);
        max = stepY * 4;

        var textColor = css('--color-text-muted', '#6b7280');
        var gridColor = css('--color-border', '#e5e7eb');
        var lineColor = css('--color-primary', '#4f46e5');

        ctx.clearRect(0, 0, cssWidth, cssHeight);
        ctx.font = '11px sans-serif';
        ctx.fillStyle = textColor;
        ctx.strokeStyle = gridColor;
        ctx.lineWidth = 1;

        // horizontal grid + y labels
        for (var g = 0; g <= 4; g++) {
            var yv = stepY * g;
            var y = pad.t + h - (yv / max) * h;
            ctx.beginPath();
            ctx.moveTo(pad.l, y);
            ctx.lineTo(pad.l + w, y);
            ctx.stroke();
            ctx.fillText(String(yv), 6, y + 4);
        }
        // x labels (every 5th day)
        series.forEach(function (p, i) {
            if (i % 5 !== 0 && i !== series.length - 1) return;
            var x = pad.l + (i / (series.length - 1)) * w;
            ctx.fillText(p.label, x - 12, cssHeight - 8);
        });

        // area fill
        ctx.beginPath();
        series.forEach(function (p, i) {
            var x = pad.l + (i / (series.length - 1)) * w;
            var y = pad.t + h - (p.value / max) * h;
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        });
        ctx.lineTo(pad.l + w, pad.t + h);
        ctx.lineTo(pad.l, pad.t + h);
        ctx.closePath();
        ctx.fillStyle = lineColor + '22';
        ctx.fill();

        // line
        ctx.beginPath();
        ctx.strokeStyle = lineColor;
        ctx.lineWidth = 2;
        series.forEach(function (p, i) {
            var x = pad.l + (i / (series.length - 1)) * w;
            var y = pad.t + h - (p.value / max) * h;
            if (i === 0) ctx.moveTo(x, y); else ctx.lineTo(x, y);
        });
        ctx.stroke();

        // points
        ctx.fillStyle = lineColor;
        series.forEach(function (p, i) {
            var x = pad.l + (i / (series.length - 1)) * w;
            var y = pad.t + h - (p.value / max) * h;
            ctx.beginPath();
            ctx.arc(x, y, 2.5, 0, Math.PI * 2);
            ctx.fill();
        });
    }

    draw();
    window.addEventListener('resize', draw);
})();
