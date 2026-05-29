(function () {
    if (typeof Chart !== 'undefined' || window.Chart) return;

    class SimpleChart {
        constructor(canvas, config) {
            this.canvas = canvas;
            this.config = config || {};
            this.data = this.config.data || { labels: [], datasets: [] };
            this.type = this.config.type || 'bar';
            this.draw();
        }

        update() {
            this.draw();
        }

        draw() {
            if (!this.canvas || !this.canvas.getContext) return;
            const ctx = this.canvas.getContext('2d');
            const width = this.canvas.width;
            const height = this.canvas.height;
            ctx.clearRect(0, 0, width, height);
            if (this.type === 'doughnut') {
                this.drawDoughnut(ctx, width, height);
            } else {
                this.drawBars(ctx, width, height);
            }
        }

        drawBars(ctx, width, height) {
            const values = (this.data.datasets?.[0]?.data || []).map(Number);
            const labels = this.data.labels || [];
            const max = Math.max(1, ...values);
            const padding = 34;
            const gap = 12;
            const barWidth = Math.max(8, (width - padding * 2 - gap * Math.max(0, values.length - 1)) / Math.max(1, values.length));

            ctx.strokeStyle = 'rgba(255,255,255,0.12)';
            ctx.lineWidth = 1;
            ctx.beginPath();
            ctx.moveTo(padding, padding);
            ctx.lineTo(padding, height - padding);
            ctx.lineTo(width - padding, height - padding);
            ctx.stroke();

            values.forEach((value, index) => {
                const x = padding + index * (barWidth + gap);
                const barHeight = (height - padding * 2) * (value / max);
                const y = height - padding - barHeight;
                const gradient = ctx.createLinearGradient(0, y, 0, height - padding);
                gradient.addColorStop(0, '#e50914');
                gradient.addColorStop(1, 'rgba(229,9,20,0.2)');
                ctx.fillStyle = gradient;
                this.roundRect(ctx, x, y, barWidth, barHeight, 6);
                ctx.fill();

                if (labels[index]) {
                    ctx.fillStyle = 'rgba(255,255,255,0.58)';
                    ctx.font = '12px sans-serif';
                    ctx.textAlign = 'center';
                    ctx.fillText(String(labels[index]), x + barWidth / 2, height - 10);
                }
            });
        }

        drawDoughnut(ctx, width, height) {
            const values = (this.data.datasets?.[0]?.data || []).map(Number);
            const colors = this.data.datasets?.[0]?.backgroundColor || ['#e50914', '#2563eb', '#8a8a8a'];
            const total = values.reduce((sum, value) => sum + value, 0) || 1;
            const cx = width / 2;
            const cy = height / 2;
            const radius = Math.min(width, height) * 0.36;
            const inner = radius * 0.7;
            let start = -Math.PI / 2;

            values.forEach((value, index) => {
                const end = start + (value / total) * Math.PI * 2;
                ctx.beginPath();
                ctx.arc(cx, cy, radius, start, end);
                ctx.arc(cx, cy, inner, end, start, true);
                ctx.closePath();
                ctx.fillStyle = colors[index] || '#8a8a8a';
                ctx.fill();
                start = end;
            });
        }

        roundRect(ctx, x, y, width, height, radius) {
            const r = Math.min(radius, width / 2, height / 2);
            ctx.beginPath();
            ctx.moveTo(x + r, y);
            ctx.lineTo(x + width - r, y);
            ctx.quadraticCurveTo(x + width, y, x + width, y + r);
            ctx.lineTo(x + width, y + height);
            ctx.lineTo(x, y + height);
            ctx.lineTo(x, y + r);
            ctx.quadraticCurveTo(x, y, x + r, y);
            ctx.closePath();
        }
    }

    Chart = SimpleChart;
    try {
        window.Chart = SimpleChart;
    } catch (error) {
    }
})();
