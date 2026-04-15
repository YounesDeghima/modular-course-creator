

    MathParser = (() => {

    function toJS(expr) {
        return expr.trim()
            // implicit multiplication:  2x  2(  )(  )x
            .replace(/(\d)([a-zA-Z(])/g, '$1*$2')
            .replace(/([a-zA-Z)])(\d)/g, '$1*$2')
            .replace(/\)\s*\(/g, ')*(')
            // exponent
            .replace(/\^/g, '**')
            // trig / math words → Math.*
            .replace(/\basin\b/g, 'Math.asin')
            .replace(/\bacos\b/g, 'Math.acos')
            .replace(/\batan2\b/g, 'Math.atan2')
            .replace(/\batan\b/g, 'Math.atan')
            .replace(/\bsinh\b/g, 'Math.sinh')
            .replace(/\bcosh\b/g, 'Math.cosh')
            .replace(/\btanh\b/g, 'Math.tanh')
            .replace(/\bsin\b/g, 'Math.sin')
            .replace(/\bcos\b/g, 'Math.cos')
            .replace(/\btan\b/g, 'Math.tan')
            .replace(/\bsqrt\b/g, 'Math.sqrt')
            .replace(/\bcbrt\b/g, 'Math.cbrt')
            .replace(/\babs\b/g, 'Math.abs')
            .replace(/\bln\b/g, 'Math.log')
            .replace(/\blog10\b/g, 'Math.log10')
            .replace(/\blog2\b/g, 'Math.log2')
            .replace(/\blog\b/g, 'Math.log')
            .replace(/\bexp\b/g, 'Math.exp')
            .replace(/\bfloor\b/g, 'Math.floor')
            .replace(/\bceil\b/g, 'Math.ceil')
            .replace(/\bround\b/g, 'Math.round')
            .replace(/\bsign\b/g, 'Math.sign')
            .replace(/\bmax\b/g, 'Math.max')
            .replace(/\bmin\b/g, 'Math.min')
            .replace(/\bpi\b/gi, 'Math.PI')
            .replace(/\be\b/g, 'Math.E');
    }


    function compile(raw) {
    const eq = raw.trim();
    const eqIdx = eq.indexOf('=');
    let lhs, rhs;
    if (eqIdx === -1) {
    lhs = eq; rhs = '0';
} else {
    lhs = eq.slice(0, eqIdx);
    rhs = eq.slice(eqIdx + 1);
}
    const jsExpr = `(${toJS(lhs)}) - (${toJS(rhs)})`;
    try {
    // eslint-disable-next-line no-new-func
    const fn = new Function('x', 'y', `"use strict"; return ${jsExpr};`);
    // quick smoke-test
    fn(0, 0);
    return { fn, error: null };
} catch(e) {
    return { fn: null, error: e.message };
}
}

    return { compile };
})();


    ImplicitPlotter = (() => {

    // colour helpers
    function hexToRgb(hex) {
        const r = parseInt(hex.slice(1,3),16);
        const g = parseInt(hex.slice(3,5),16);
        const b = parseInt(hex.slice(5,7),16);
        return {r,g,b};
    }

    function cssVar(name, fallback='') {
    return getComputedStyle(document.documentElement)
    .getPropertyValue(name).trim() || fallback;
}

    // linear-interpolation to find where F crosses 0 on a segment
    function lerp(t0, t1, f0, f1) {
    if (f0 === f1) return (t0+t1)/2;
    return t0 + (0 - f0) * (t1 - t0) / (f1 - f0);
}

    /* marching squares — returns array of [x1,y1,x2,y2] line segments
       in *math* coordinates                                          */
    function marchingSquares(fn, xMin, xMax, yMin, yMax, resolution) {
    const segments = [];
    const cols = Math.ceil((xMax - xMin) / resolution);
    const rows = Math.ceil((yMax - yMin) / resolution);
    const dx   = (xMax - xMin) / cols;
    const dy   = (yMax - yMin) / rows;

    // Pre-compute grid of F values
    const grid = [];
    for (let r = 0; r <= rows; r++) {
    grid[r] = [];
    const cy = yMin + r * dy;
    for (let c = 0; c <= cols; c++) {
    const cx = xMin + c * dx;
    let v;
    try { v = fn(cx, cy); } catch(e) { v = NaN; }
    grid[r][c] = isFinite(v) ? v : NaN;
}
}

    for (let r = 0; r < rows; r++) {
    for (let c = 0; c < cols; c++) {
    const x0 = xMin + c * dx,       y0 = yMin + r * dy;
    const x1 = x0 + dx,             y1 = y0 + dy;
    const bl = grid[r][c],           br = grid[r][c+1];
    const tl = grid[r+1][c],         tr = grid[r+1][c+1];

    // Skip cells with NaN corners
    if ([bl,br,tl,tr].some(isNaN)) continue;

    // Marching squares lookup — 4-bit case index
    let idx = 0;
    if (tl > 0) idx |= 8;
    if (tr > 0) idx |= 4;
    if (br > 0) idx |= 2;
    if (bl > 0) idx |= 1;

    if (idx === 0 || idx === 15) continue; // no crossing

    // edge midpoints (lerped)
    const left   = lerp(y0, y1, bl, tl);  // x=x0, y=left
    const right  = lerp(y0, y1, br, tr);  // x=x1, y=right
    const bottom = lerp(x0, x1, bl, br);  // y=y0, x=bottom
    const top    = lerp(x0, x1, tl, tr);  // y=y1, x=top

    // Standard marching-squares edge table (15 unique cases)
    switch(idx) {
    case 1:  case 14: segments.push([x0,left, bottom,y0]); break;
    case 2:  case 13: segments.push([bottom,y0, x1,right]); break;
    case 3:  case 12: segments.push([x0,left, x1,right]); break;
    case 4:  case 11: segments.push([top,y1, x1,right]); break;
    case 5:           // ambiguous — split into two
    segments.push([x0,left,  top,y1]);
    segments.push([bottom,y0, x1,right]);
    break;
    case 10:          // ambiguous — split into two
    segments.push([x0,left,  bottom,y0]);
    segments.push([top,y1,   x1,right]);
    break;
    case 6:  case 9:  segments.push([bottom,y0, top,y1]); break;
    case 7:  case 8:  segments.push([x0,left, top,y1]); break;
}
}
}
    return segments;
}

    /* Convert math coords → canvas pixels */
    function toCanvas(mx, my, xMin, xMax, yMin, yMax, W, H) {
    return [
    (mx - xMin) / (xMax - xMin) * W,
    H - (my - yMin) / (yMax - yMin) * H
    ];
}

    /* ---- main render function ---- */
    function render(canvas, opts) {
    const {
    equation, xMin, xMax, yMin, yMax,
    color = '#4f46e5',
    resolution = 0.05,
} = opts;

    const dpr = window.devicePixelRatio || 1;
    const displayW = canvas.clientWidth  || 600;
    const displayH = canvas.clientHeight || 320;

    // Only resize if needed (avoids flicker)
    const needW = Math.round(displayW * dpr);
    const needH = Math.round(displayH * dpr);
    if (canvas.width !== needW || canvas.height !== needH) {
    canvas.width  = needW;
    canvas.height = needH;
}

    const ctx = canvas.getContext('2d');
    ctx.scale(dpr, dpr);  // will be reset below

    const W = displayW, H = displayH;
    ctx.setTransform(dpr, 0, 0, dpr, 0, 0);

    // --- Theme colors ---
    const bgColor     = cssVar('--bg',        '#ffffff');
    const gridColor   = cssVar('--border',     '#e5e7eb');
    const axisColor   = cssVar('--text-faint', '#9ca3af');
    const labelColor  = cssVar('--text',       '#111827');

    // Background
    ctx.fillStyle = bgColor;
    ctx.fillRect(0, 0, W, H);

    // --- Grid + tick labels ---
    const nTicks = 8; // approx ticks per axis
    function niceStep(range) {
    const rough = range / nTicks;
    const exp   = Math.floor(Math.log10(rough));
    const frac  = rough / Math.pow(10, exp);
    let nice;
    if (frac < 1.5)      nice = 1;
    else if (frac < 3.5) nice = 2;
    else if (frac < 7.5) nice = 5;
    else                 nice = 10;
    return nice * Math.pow(10, exp);
}

    const xStep = niceStep(xMax - xMin);
    const yStep = niceStep(yMax - yMin);

    ctx.font = `${10 * Math.min(dpr,1.5)}px system-ui, sans-serif`;
    ctx.textBaseline = 'top';
    ctx.textAlign = 'center';

    // vertical grid lines
    const xStart = Math.ceil(xMin / xStep) * xStep;
    ctx.strokeStyle = gridColor;
    ctx.lineWidth = 1;
    for (let xv = xStart; xv <= xMax + xStep*0.001; xv += xStep) {
    const [px] = toCanvas(xv, 0, xMin, xMax, yMin, yMax, W, H);
    ctx.beginPath();
    ctx.setLineDash([3,3]);
    ctx.moveTo(px, 0); ctx.lineTo(px, H);
    ctx.stroke();
    ctx.setLineDash([]);
    // label
    const label = parseFloat(xv.toPrecision(5)).toString();
    ctx.fillStyle = axisColor;
    const [, yAxis] = toCanvas(0, 0, xMin, xMax, yMin, yMax, W, H);
    const ly = Math.min(Math.max(yAxis + 3, 3), H - 14);
    ctx.fillText(label, px, ly);
}

    // horizontal grid lines
    const yStart = Math.ceil(yMin / yStep) * yStep;
    ctx.textAlign = 'right';
    ctx.textBaseline = 'middle';
    for (let yv = yStart; yv <= yMax + yStep*0.001; yv += yStep) {
    const [, py] = toCanvas(0, yv, xMin, xMax, yMin, yMax, W, H);
    ctx.beginPath();
    ctx.strokeStyle = gridColor;
    ctx.setLineDash([3,3]);
    ctx.moveTo(0, py); ctx.lineTo(W, py);
    ctx.stroke();
    ctx.setLineDash([]);
    const label = parseFloat(yv.toPrecision(5)).toString();
    ctx.fillStyle = axisColor;
    const [xAxis] = toCanvas(0, 0, xMin, xMax, yMin, yMax, W, H);
    const lx = Math.min(Math.max(xAxis - 4, 30), W - 4);
    ctx.fillText(label, lx, py);
}

    // --- Axes ---
    ctx.strokeStyle = axisColor;
    ctx.lineWidth = 1.5;
    ctx.setLineDash([]);

    // X-axis
    const [, yZero] = toCanvas(0, 0, xMin, xMax, yMin, yMax, W, H);
    if (yZero >= 0 && yZero <= H) {
    ctx.beginPath();
    ctx.moveTo(0, yZero); ctx.lineTo(W, yZero);
    ctx.stroke();
}
    // Y-axis
    const [xZero] = toCanvas(0, 0, xMin, xMax, yMin, yMax, W, H);
    if (xZero >= 0 && xZero <= W) {
    ctx.beginPath();
    ctx.moveTo(xZero, 0); ctx.lineTo(xZero, H);
    ctx.stroke();
}

    // Arrowheads on axes
    ctx.fillStyle = axisColor;
    const aw = 6, ah = 4;
    if (yZero >= 0 && yZero <= H) { // →
    ctx.beginPath();
    ctx.moveTo(W,     yZero);
    ctx.lineTo(W-aw,  yZero-ah);
    ctx.lineTo(W-aw,  yZero+ah);
    ctx.fill();
}
    if (xZero >= 0 && xZero <= W) { // ↑
    ctx.beginPath();
    ctx.moveTo(xZero, 0);
    ctx.lineTo(xZero-ah, aw);
    ctx.lineTo(xZero+ah, aw);
    ctx.fill();
}

    // --- Parse & compile equation ---
    const { fn, error } = MathParser.compile(equation);
    const errDiv = document.getElementById('func-error-' + canvas.id.replace('func-canvas-','').replace('preview-func-',''));
    if (errDiv) {
    if (error) { errDiv.style.display=''; errDiv.textContent='⚠ ' + error; }
    else        { errDiv.style.display='none'; }
}
    if (!fn) return;

    // --- Marching squares ---
    const segments = marchingSquares(fn, xMin, xMax, yMin, yMax, resolution);

    // Draw segments
    const rgb = hexToRgb(color);
    ctx.strokeStyle = `rgb(${rgb.r},${rgb.g},${rgb.b})`;
    ctx.lineWidth = 2.5;
    ctx.lineCap = 'round';
    ctx.lineJoin = 'round';
    ctx.setLineDash([]);

    for (const [mx1, my1, mx2, my2] of segments) {
    const [px1, py1] = toCanvas(mx1, my1, xMin, xMax, yMin, yMax, W, H);
    const [px2, py2] = toCanvas(mx2, my2, xMin, xMax, yMin, yMax, W, H);
    ctx.beginPath();
    ctx.moveTo(px1, py1);
    ctx.lineTo(px2, py2);
    ctx.stroke();
}
}

    return { render };
})();

    (function setupEditorPlots() {
    function readOpts(editor) {
        const bid = editor.dataset.blockId;
        const eq  = editor.querySelector('input[name*="func_expression"]')?.value ?? 'y=sin(x)';
        return {
            equation:   eq,
            xMin:       parseFloat(editor.querySelector('input[name*="x_min"]')?.value)  || -10,
            xMax:       parseFloat(editor.querySelector('input[name*="x_max"]')?.value)  ||  10,
            yMin:       parseFloat(editor.querySelector('input[name*="y_min"]')?.value)  ||  -6,
            yMax:       parseFloat(editor.querySelector('input[name*="y_max"]')?.value)  ||   6,
            color:      editor.querySelector('input[name*="color"]')?.value              || '#4f46e5',
            resolution: parseFloat(editor.querySelector('input[name*="step"]')?.value)   || 0.05,
            blockId:    bid,
        };
    }

    function renderEditor(editor) {
    const opts   = readOpts(editor);
    const canvas = document.getElementById('func-canvas-' + opts.blockId);
    if (!canvas) return;

    // Set a fixed display size the first time
    if (!canvas.style.height) canvas.style.height = '240px';

    ImplicitPlotter.render(canvas, opts);

    // Update hidden JSON input
    const hidden = editor.closest('.block-row')?.querySelector('.function-content-hidden');
    if (hidden) {
    hidden.value = JSON.stringify({
    function: opts.equation,
    x_min:    opts.xMin,
    x_max:    opts.xMax,
    y_min:    opts.yMin,
    y_max:    opts.yMax,
    color:    opts.color,
    step:     opts.resolution,
});
}
}

    function debounce(fn, ms) {
    let t;
    return function(...a) { clearTimeout(t); t = setTimeout(() => fn(...a), ms); };
}

    // Initial render after fonts/styles loaded
    document.addEventListener('DOMContentLoaded', function() {
    setTimeout(() => {
    document.querySelectorAll('.function-editor').forEach(e => renderEditor(e));
}, 120);
});

    // Re-render on any input inside a function-editor
    document.addEventListener('input', debounce(function(ev) {
    const editor = ev.target.closest('.function-editor');
    if (editor) renderEditor(editor);
}, 120));

    // Also re-render on window resize (canvas needs DPR recalc)
    window.addEventListener('resize', debounce(function() {
    document.querySelectorAll('.function-editor').forEach(e => renderEditor(e));
}, 200));
})();


    /* =========================================================
    PREVIEW  — render blocks accumulated by the inline scripts
    (blocks_blade.php pushes to window._funcBlocks)
    ========================================================= */
    (function setupPreviewPlots() {
    function renderAll() {
        (window._funcBlocks || []).forEach(({ id, data }) => {
            const canvas = document.getElementById('preview-func-' + id);
            if (!canvas) return;
            canvas.style.height = '320px';
            ImplicitPlotter.render(canvas, {
                equation:   data.function || 'y=sin(x)',
                xMin:       parseFloat(data.x_min)  || -10,
                xMax:       parseFloat(data.x_max)  ||  10,
                yMin:       parseFloat(data.y_min)  ||  -6,
                yMax:       parseFloat(data.y_max)  ||   6,
                color:      data.color              || '#4f46e5',
                resolution: parseFloat(data.step)   || 0.05,
            });
        });

        // KaTeX labels (optional)
        if (window.katex) {
            document.querySelectorAll('.katex-eq').forEach(el => {
                try {
                    katex.render(el.dataset.eq || '', el, { throwOnError: false, displayMode: false });
                } catch(e) { /* leave as text */ }
            });
        }
    }

    if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', renderAll);
} else {
    renderAll();
}

    window.addEventListener('resize', function() {
    clearTimeout(window._previewResizeTimer);
    window._previewResizeTimer = setTimeout(renderAll, 200);
});
})();
