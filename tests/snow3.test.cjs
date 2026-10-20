/* Motion and projection expectations below were recorded once from the shipped
 * Snow3 1.2 engine (commit d201197: js/three.js + the PHP Particle3D script) with
 * the same seeded Math.random. The legacy code itself is no longer kept here. */
const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const vm = require('node:vm');
const path = require('node:path');
const repo = path.resolve(__dirname, '..');
const source = fs.readFileSync(path.join(repo, 'js/snow3.js'), 'utf8');
function seeded() { let state = 42; return () => ((state = (1664525 * state + 1013904223) >>> 0) / 4294967296); }
class Events {
    constructor() { this.listeners = new Map(); }
    addEventListener(type, fn) { if (!this.listeners.has(type)) this.listeners.set(type, new Set()); this.listeners.get(type).add(fn); }
    removeEventListener(type, fn) { this.listeners.get(type)?.delete(fn); }
    emit(type, event = {}) { for (const fn of this.listeners.get(type) || []) fn(event); }
    count() { return [...this.listeners.values()].reduce((n, set) => n + set.size, 0); }
}
function harness(input = {}) {
    const draws = [], frames = new Map(); let next = 1;
    const root = new Events(); root.isConnected = true; root.children = [];
    root.bounds = { width: 1280, height: 720, left: 0, top: 0 };
    root.getBoundingClientRect = () => root.bounds;
    root.appendChild = child => { root.children.push(child); child.parent = root; };
    const context = { setTransform() {}, clearRect() { draws.length = 0; }, fillRect() {}, createRadialGradient() { return { addColorStop() {} }; }, drawImage(...args) { draws.push(args); } };
    const document = new Events(); document.hidden = false; document.readyState = 'complete'; document.baseURI = 'https://test.invalid/';
    document.querySelectorAll = () => [];
    document.createElement = () => ({ width: 22, height: 22, setAttribute() {}, getContext: () => context, remove() { this.parent.children.splice(this.parent.children.indexOf(this), 1); } });
    const window = new Events(); window.location = new URL(document.baseURI); window.devicePixelRatio = 2;
    const media = new Events(); media.matches = false; window.matchMedia = () => media;
    window.requestAnimationFrame = fn => { const id = next++; frames.set(id, fn); return id; };
    window.cancelAnimationFrame = id => frames.delete(id);
    const timers = []; window.setTimeout = fn => timers.push(fn);
    class Image { constructor() { this.width = 20; this.height = 18; } set src(value) { this.url = value; } }
    const math = Object.create(Math); math.random = seeded();
    vm.runInNewContext(source, { window, document, Image, URL, Math: math, console });
    const options = { style: 2, texture: '/img/Snow2.png', ...input };
    const snow = window.Snow3.create(root, options); snow.image.onload();
    const advance = time => { const callbacks = [...frames.values()]; frames.clear(); callbacks.forEach(fn => fn(time)); };
    return { root, window, document, media, frames, draws, snow, advance, timers };
}
function close(actual, expected, tolerance = 1e-7) { assert.ok(Math.abs(actual - expected) < tolerance, `${actual} != ${expected}`); }

const legacy = {
    // Seed 42: first three particles per option set, [x, y, z, vx, vy, vz].
    initial: [
        [{speed: 0.1}, [
            [154.562396463007, -554.891468025744, -248.679605778307, -0.0199440429637306, -0.0925282986800368, -0.032260643106404],
            [-763.079992495477, 747.627411503345, 989.268550649285, -0.0220508536971313, -0.0735030003664733, 0.0641176168330821],
            [284.001892898232, 722.91237488389, 192.939552944154, 0.000106998551673572, -0.0850000785976145, -0.0526780332748794]]],
        [{way: -4, speed: 3, front: 2, rotateX: 27, rotateY: 170, gravity: 0.1}, [
            [154.562396463007, -554.891468025744, -248.679605778307, -3.54042686659987, -3.38124175049331, 2.24334166523488],
            [-763.079992495477, 747.627411503345, 989.268550649285, -0.473564515493826, -3.56979427184784, 4.00403615197808],
            [284.001892898232, 722.91237488389, 192.939552944154, 2.50691233116821, -2.18176653454126, 4.23736773865481]]],
        [{way: 8, speed: 0, front: -2, rotateX: 30, rotateY: -60, gravity: 0}, [
            [154.562396463007, -554.891468025744, -248.679605778307, 8.14410188719888, 0.512892174278252, -1.18766412275853],
            [-763.079992495477, 747.627411503345, 989.268550649285, 7.93215020887781, 0.953095313188792, 2.04269488367138],
            [284.001892898232, 722.91237488389, 192.939552944154, 7.86138345384985, -0.722994097856369, 2.38242098001186]]]
    ],
    // First particle's velocity and position after N 40 Hz steps from (0, 900, 0).
    physics: {
        options: {way: 1, speed: 2, front: 0.3, gravity: 0.01},
        velocity: [0.597666835593666, -1.96434921265817, -0.934947337747257],
        checkpoints: {
            1: [0.597666835593666, 898.025650787342, -0.934947337747257],
            50: [29.8833417796833, 789.032539367092, -46.7473668873628],
            100: [59.7666835593665, 653.065078734184, -93.4947337747257],
            200: [119.533367118734, 306.13015746837, -186.989467549451]
        }
    },
    // THREE.Projector output for a particle at (100, 120, -200), 75° camera, 1280×720.
    projections: [
        {camera: {x: 0, y: 0}, x: 0.0610886893519315, y: 0.130322537284121, scaleX: 0.000610886893519312, scaleY: 0.00108602114403436},
        {camera: {x: 190, y: -120}, x: 0.0817589199371175, y: 0.105446861152168, scaleX: 0.00060305496320584, scaleY: 0.00107209771236594},
        {camera: {x: -360, y: 220}, x: 0.0151058823211572, y: 0.165664443644723, scaleX: 0.00057339038696742, scaleY: 0.00101936068794209}
    ]
};

test('initial particle velocity and position match Snow3 1.2 across motion parameters', () => {
    for (const [options, expected] of legacy.initial) {
        const { snow } = harness({ ...options, num: 3 });
        snow.particles.forEach((particle, i) => ['x', 'y', 'z', 'vx', 'vy', 'vz'].forEach((key, j) => close(particle[key], expected[i][j], 1e-9)));
    }
});

test('40 Hz gravity and movement match Snow3 1.2 for 200 steps inside world bounds', () => {
    const { snow } = harness({ ...legacy.physics.options, num: 1 });
    Object.assign(snow.particles[0], {x: 0, y: 900, z: 0});
    ['vx', 'vy', 'vz'].forEach((key, j) => close(snow.particles[0][key], legacy.physics.velocity[j], 1e-9));
    for (let i = 1; i <= 200; i++) {
        snow.update();
        const expected = legacy.physics.checkpoints[i];
        if (expected) ['x', 'y', 'z'].forEach((axis, j) => close(snow.particles[0][axis], expected[j], 1e-7));
    }
});

test('perspective centers and texture dimensions agree with THREE.Projector from Snow3 1.2', () => {
    const { snow, draws } = harness({num: 1});
    for (const result of legacy.projections) {
        Object.assign(snow.camera, result.camera); Object.assign(snow.particles[0], {x: 100, y: 120, z: -200}); snow.render();
        const [, x, y, width, height] = draws[0];
        close(x + width / 2, 640 + result.x * 640, .001);
        close(y + height / 2, 360 - result.y * 360, .001);
        close(width, result.scaleX * 640 * 20, .001); close(height, result.scaleY * 360 * 18, .001);
    }
});

test('refresh rate does not change 40 Hz motion', () => {
    for (const rate of [30, 60, 120, 144]) {
        const { snow, advance } = harness({num: 1, speed: 1, rotateX: 0, rotateY: 0});
        Object.assign(snow.particles[0], {x: 0, y: 900, z: 0});
        advance(0); for (let i = 1; i <= rate; i++) advance(i * 1000 / rate);
        close(snow.particles[0].y, 860);
    }
});

test('visibility, BFCache, reduced motion, resize, singleton and destroy lifecycle', () => {
    const {snow, window, document, media, frames, root, advance} = harness({num: 5});
    assert.equal(window.Snow3.create(root), snow); assert.equal(root.children.length, 1);
    document.hidden = true; document.emit('visibilitychange'); assert.equal(frames.size, 0); assert.equal(snow.canvas.hidden, true);
    document.hidden = false; document.emit('visibilitychange'); assert.equal(frames.size, 1);
    window.emit('pagehide'); assert.equal(frames.size, 0); window.emit('pageshow'); assert.equal(frames.size, 1);
    media.matches = true; root.bounds.width = 0; media.emit('change'); assert.equal(frames.size, 0);
    media.matches = false; root.bounds.width = 390; root.bounds.height = 844; media.emit('change');
    assert.equal(frames.size, 1); assert.equal(snow.canvas.width, 780); assert.equal(snow.canvas.height, 1688);
    // While animating, a burst of resize events is applied once on the next frame.
    let reads = 0; const getBounds = root.getBoundingClientRect;
    root.getBoundingClientRect = () => { reads++; return getBounds(); };
    root.bounds.width = 768; for (let i = 0; i < 5; i++) window.emit('resize');
    assert.equal(snow.canvas.width, 780); assert.equal(reads, 0);
    advance(0); assert.equal(snow.canvas.width, 1536); assert.equal(reads, 1); assert.ok(frames.size);
    // Paused: resize applies immediately; an unchanged size keeps the backing store.
    window.emit('pagehide'); root.bounds.width = 640; window.emit('resize'); assert.equal(snow.canvas.width, 1280);
    const canvas = snow.canvas; let writes = 0; let width = canvas.width;
    Object.defineProperty(canvas, 'width', { get: () => width, set: value => { writes++; width = value; } });
    window.emit('resize'); assert.equal(writes, 0); window.emit('pageshow');
    snow.destroy();
    assert.equal(frames.size, 0); assert.equal(root.children.length, 0); assert.equal(window.count() + document.count() + media.count(), 0);
    assert.equal(snow.particles.length, 0); snow.destroy();
});

test('cross-origin HTTP(S) textures are kept; other schemes use the fallback sprite', () => {
    assert.equal(harness({num: 1, texture: 'https://cdn.example.com/img/Snow2.png'}).snow.options.texture, 'https://cdn.example.com/img/Snow2.png');
    assert.equal(harness({num: 1, texture: 'javascript:alert(1)'}).snow.options.texture, '');
    assert.equal(harness({num: 1, texture: 'data:image/png;base64,AA=='}).snow.options.texture, '');
});

test('invalid config, missing texture, zero particles and large steps stay bounded', () => {
    const h = harness({num: Infinity, speed: '1e309', gravity: [], way: -5000, front: 9000});
    assert.equal(h.snow.options.num, 500); assert.equal(h.snow.options.speed, 0.5); assert.equal(h.snow.options.gravity, 0);
    assert.equal(h.snow.options.way, -1000); assert.equal(h.snow.options.front, 1000);
    h.snow.image.onerror(); assert.ok(h.snow.sprite); h.advance(0); h.advance(100000); assert.equal(h.frames.size, 1);
    for (const particle of h.snow.particles) for (const axis of ['x', 'y', 'z']) assert.ok(particle[axis] >= -1000 && particle[axis] < 1000);
    const zero = harness({num: 0}); assert.equal(zero.frames.size, 0); assert.equal(zero.snow.canvas.hidden, true);
    const detached = harness({num: 1}); detached.root.isConnected = false; detached.advance(0); assert.equal(detached.snow.destroyed, true);
});


test('negative gravity and X rotation work (1.2 emitted a double-minus syntax error)', () => {
    const {snow} = harness({num: 1, gravity: -.1, rotateX: -30});
    const before = snow.particles[0].vy; snow.update(); close(snow.particles[0].vy, before + .1);
});


test('performance: paint only after a 40 Hz physics step, at any refresh rate', () => {
    for (const hz of [30, 60, 120, 144]) {
        const h = harness({num: 12, speed: 1});
        let renders = 0; const render = h.snow.render.bind(h.snow);
        h.snow.render = () => { renders++; render(); };
        for (let frame = 0; frame <= hz; frame++) h.advance(frame * 1000 / hz);
        // First frame plus at most one paint per physics step.
        assert.ok(renders <= 41, `${hz} Hz painted ${renders} times`);
        if (hz >= 60) assert.ok(renders < hz);
        assert.ok(h.draws.length > 0 && h.draws.length <= 12);
    }
});

test('auto start waits for page load and an idle period', () => {
    const h = harness({num: 1});
    let scans = 0;
    h.document.querySelectorAll = () => { scans++; return []; };
    // readyState is "complete" in the harness: start() queued one idle callback
    // (setTimeout fallback) instead of scanning while the page is still busy.
    assert.equal(scans, 0); assert.equal(h.timers.length, 1);
    h.timers[0](); assert.equal(scans, 1);
});

test('performance: pointer events avoid layout reads; resize still invalidates the cached drawing', () => {
    const h = harness({num: 1}); let reads = 0;
    const getBounds = h.root.getBoundingClientRect;
    h.root.getBoundingClientRect = () => { reads++; return getBounds(); };
    for (let i = 0; i < 100; i++) h.window.emit('pointermove', {clientX: 100, clientY: 100, pointerType: 'mouse'});
    assert.equal(reads, 0); assert.equal(h.snow.pointer.x, -540);
    let renders = 0; const render = h.snow.render.bind(h.snow);
    h.snow.render = () => { renders++; render(); };
    h.advance(0); h.advance(10); assert.equal(renders, 1);
    h.root.bounds = {width: 390, height: 844, left: 10, top: 20};
    h.window.emit('resize'); h.advance(20);
    assert.equal(reads, 1); assert.equal(renders, 2);
    h.window.emit('pointermove', {clientX: 100, clientY: 100, pointerType: 'mouse'});
    assert.equal(h.snow.pointer.x, -105); assert.equal(h.snow.pointer.y, -342);
});

test('fast wrapping agrees with full modulo across boundary and multi-world jumps', () => {
    const {snow} = harness({num: 1});
    for (const value of [-1e8, -9000, -3000, -1000.01, -1000, 0, 999.99, 1000, 2999.99, 3000, 1e8]) {
        Object.assign(snow.particles[0], {x: value, y: value, z: value, vx: 0, vy: 0, vz: 0});
        snow.update();
        const expected = ((value + 1000) % 2000 + 2000) % 2000 - 1000;
        for (const axis of ['x','y','z']) close(snow.particles[0][axis], expected, 1e-6);
    }
});
