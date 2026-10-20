/* Snow3 2.0.0 — GPL-2.0-or-later. Readable source; no build step.
 * Reconstructed from Snow3 1.2 (Jacky / 清馨雅致).
 * World coordinates, rotation order, 75° camera and 40 Hz physics follow 1.2.
 */
(function (window, document) {
    'use strict';

    if (window.Snow3) {
        window.Snow3.scan();
        return;
    }

    const instances = new WeakMap();
    const defaults = Object.freeze({
        style: 1, way: 0, speed: 0.5, front: 0,
        rotateX: 45, rotateY: 360, gravity: 0, num: 500, texture: ''
    });
    const limits = {
        way: [-1000, 1000], speed: [0, 1000], front: [-1000, 1000],
        rotateX: [-360, 360], rotateY: [-360, 360], gravity: [-1, 1], num: [0, 2000]
    };
    const radians = Math.PI / 180;
    const stepMs = 1000 / 40;

    function normalize(input) {
        input = input && typeof input === 'object' ? input : {};
        const options = { ...defaults };
        for (const [key, range] of Object.entries(limits)) {
            const value = typeof input[key] === 'number' ||
                (typeof input[key] === 'string' && input[key].trim() !== '')
                ? Number(input[key]) : NaN;
            options[key] = Number.isFinite(value)
                ? Math.min(range[1], Math.max(range[0], value)) : defaults[key];
        }
        options.num = Math.floor(options.num);
        options.style = Number(input.style) === 2 ? 2 : 1;
        // Any HTTP(S) texture is allowed: WordPress may serve plugin assets from a
        // CDN or a siteurl that differs from home, and drawImage never reads pixels
        // back, so a cross-origin sprite is safe. Invalid URLs use a soft sprite.
        try {
            if (typeof input.texture === 'string' && input.texture) {
                const url = new URL(input.texture, document.baseURI);
                if (/^https?:$/.test(url.protocol)) {
                    options.texture = url.href;
                }
            }
        } catch (_) { /* Invalid texture: draw the fallback sprite. */ }
        return options;
    }

    function randomRange(min, max) { return min + Math.random() * (max - min); }
    function wrap(value) {
        // Most particles remain inside the world; avoid two remainders per axis.
        if (value >= -1000 && value < 1000) return value;
        if (value >= 1000 && value < 3000) return value - 2000;
        if (value < -1000 && value >= -3000) return value + 2000;
        return ((value + 1000) % 2000 + 2000) % 2000 - 1000;
    }

    function makeParticle(options) {
        const angleX = randomRange(-options.rotateX, options.rotateX) * radians;
        const angleY = randomRange(0, options.rotateY) * radians;
        const y = -options.speed * Math.cos(angleX) + options.front * Math.sin(angleX);
        const z = options.speed * Math.sin(angleX) + options.front * Math.cos(angleX);
        return {
            x: randomRange(-1000, 1000), y: randomRange(-1000, 1000), z: randomRange(-1000, 1000),
            vx: options.way * Math.cos(angleY) + z * Math.sin(angleY), vy: y,
            vz: -options.way * Math.sin(angleY) + z * Math.cos(angleY)
        };
    }

    function fallbackSprite() {
        const sprite = document.createElement('canvas');
        sprite.width = sprite.height = 22;
        const context = sprite.getContext('2d');
        const glow = context.createRadialGradient(11, 11, 0, 11, 11, 11);
        glow.addColorStop(0, 'rgba(255,255,255,1)');
        glow.addColorStop(0.25, 'rgba(255,255,255,.9)');
        glow.addColorStop(1, 'rgba(255,255,255,0)');
        context.fillStyle = glow;
        context.fillRect(0, 0, 22, 22);
        return sprite;
    }

    class Snowfall {
        constructor(root, options) {
            this.root = root;
            this.options = normalize(options);
            this.canvas = document.createElement('canvas');
            this.canvas.setAttribute('aria-hidden', 'true');
            this.context = this.canvas.getContext('2d');
            this.particles = Array.from({ length: this.options.num }, () => makeParticle(this.options));
            this.camera = { x: 0, y: 0 };
            this.pointer = { x: 0, y: 0 };
            this.frame = null;
            this.lastTime = null;
            this.accumulator = 0;
            this.destroyed = false;
            this.pageHidden = false;
            this.motion = window.matchMedia('(prefers-reduced-motion: reduce)');
            this.sizeDirty = false;
            // Mobile toolbars fire resize repeatedly while scrolling; while animating,
            // apply at most one resize per frame instead of one per event.
            this.onResize = () => {
                if (this.frame !== null) { this.sizeDirty = true; return; }
                this.resize(); this.sync();
            };
            this.onVisibility = () => this.sync();
            this.onMotion = () => { this.resize(); this.sync(); };
            this.onPageHide = () => { this.pageHidden = true; this.sync(); };
            this.onPageShow = () => { this.pageHidden = false; this.resize(); this.sync(); };
            this.onPointer = event => {
                if (event.pointerType === 'touch') return;
                // .Snow is fixed; its bounds are cached on resize rather than
                // forcing a layout read for every coalesced pointer event.
                this.pointer.x = event.clientX - this.left - this.width / 2;
                this.pointer.y = event.clientY - this.top - this.height / 2;
            };
            this.tick = time => {
                this.frame = null;
                if (!this.root.isConnected) { this.destroy(); return; }
                if (this.sizeDirty) { this.sizeDirty = false; this.resize(); }
                if (!this.canRun()) { this.sync(); return; }
                // Cap catch-up work after a stall; a sleeping tab never fast-forwards snow.
                const firstFrame = this.lastTime === null;
                const elapsed = firstFrame ? 0 : Math.max(0, Math.min(100, time - this.lastTime));
                this.lastTime = time;
                this.accumulator += elapsed;
                let changed = firstFrame || this.needsRender;
                while (this.accumulator >= stepMs) {
                    this.update();
                    this.accumulator -= stepMs;
                    changed = true;
                }
                // Camera and snow both change only at a physics step. Repainting
                // unchanged pixels at 60/120/144 Hz adds work without smoothing.
                if (changed) {
                    this.render();
                    this.needsRender = false;
                }
                this.frame = window.requestAnimationFrame(this.tick);
            };
            if (!this.context) { this.destroyed = true; return; }
            root.appendChild(this.canvas);
            window.addEventListener('resize', this.onResize);
            window.addEventListener('pointermove', this.onPointer, { passive: true });
            window.addEventListener('pagehide', this.onPageHide);
            window.addEventListener('pageshow', this.onPageShow);
            document.addEventListener('visibilitychange', this.onVisibility);
            this.motion.addEventListener('change', this.onMotion);
            this.resize();
            this.loadTexture();
        }

        loadTexture() {
            const image = new Image();
            this.image = image;
            image.onload = () => {
                if (this.destroyed) return;
                this.sprite = image;
                this.sync();
            };
            image.onerror = () => {
                if (this.destroyed) return;
                this.sprite = fallbackSprite();
                this.sync();
            };
            if (this.options.texture) image.src = this.options.texture;
            else image.onerror();
        }

        resize() {
            const bounds = this.root.getBoundingClientRect();
            this.left = bounds.left;
            this.top = bounds.top;
            this.width = Math.max(0, bounds.width);
            this.height = Math.max(0, bounds.height);
            // Limit backing-store memory on Retina and very large displays.
            const area = Math.max(1, this.width * this.height);
            const dpr = Math.min(window.devicePixelRatio || 1, 2, Math.sqrt(8388608 / area));
            const canvasWidth = Math.max(1, Math.round(this.width * dpr));
            const canvasHeight = Math.max(1, Math.round(this.height * dpr));
            // Reassigning canvas size reallocates the backing store; skip it when unchanged.
            if (dpr !== this.dpr || canvasWidth !== this.canvas.width || canvasHeight !== this.canvas.height) {
                this.dpr = dpr;
                this.canvas.width = canvasWidth;
                this.canvas.height = canvasHeight;
                this.context.setTransform(dpr, 0, 0, dpr, 0, 0);
            }
            this.focal = this.height / (2 * Math.tan(75 * radians / 2));
            this.needsRender = true;
        }

        canRun() {
            return !this.destroyed && !this.pageHidden && !document.hidden &&
                !this.motion.matches && this.options.num > 0 && this.sprite && this.width > 0 && this.height > 0;
        }

        sync() {
            if (this.destroyed) return;
            if (this.canRun()) {
                this.canvas.hidden = false;
                if (this.frame === null) this.frame = window.requestAnimationFrame(this.tick);
            } else {
                if (this.frame !== null) window.cancelAnimationFrame(this.frame);
                this.frame = null;
                this.lastTime = null;
                this.accumulator = 0;
                this.context.clearRect(0, 0, this.width, this.height);
                this.canvas.hidden = true;
            }
        }

        update() {
            const particles = this.particles;
            const gravity = this.options.gravity;
            for (let i = 0; i < particles.length; i++) {
                const particle = particles[i];
                particle.vy -= gravity;
                particle.x = wrap(particle.x + particle.vx);
                particle.y = wrap(particle.y + particle.vy);
                particle.z = wrap(particle.z + particle.vz);
            }
            this.camera.x += (this.pointer.x - this.camera.x) * 0.005;
            this.camera.y += (-this.pointer.y - this.camera.y) * 0.005;
        }

        render() {
            const context = this.context;
            const width = this.width, height = this.height;
            const halfWidth = width / 2, halfHeight = height / 2;
            const sprite = this.sprite, spriteWidth = sprite.width, spriteHeight = sprite.height;
            const focal = this.focal, particles = this.particles;
            context.clearRect(0, 0, width, height);
            // Orthonormal camera basis: camera at (x, y, 1000), looking at the origin.
            const { x: cx, y: cy } = this.camera;
            const distance = Math.hypot(cx, cy, 1000);
            const fx = -cx / distance, fy = -cy / distance, fz = -1000 / distance;
            const rightLength = Math.hypot(fz, fx);
            const rx = -fz / rightLength, rz = fx / rightLength;
            const ux = -rz * fy, uy = rz * fx - rx * fz, uz = rx * fy;
            for (let i = 0; i < particles.length; i++) {
                const particle = particles[i];
                const x = particle.x - cx, y = particle.y - cy, z = particle.z - 1000;
                const depth = x * fx + y * fy + z * fz;
                if (depth <= 1 || depth >= 10000) continue;
                const scale = focal / depth;
                const screenX = halfWidth + (x * rx + z * rz) * scale;
                const screenY = halfHeight - (x * ux + y * uy + z * uz) * scale;
                const drawWidth = spriteWidth * scale, drawHeight = spriteHeight * scale;
                if (drawWidth > width * 2 || drawHeight > height * 2 ||
                    screenX + drawWidth / 2 < 0 || screenX - drawWidth / 2 > width ||
                    screenY + drawHeight / 2 < 0 || screenY - drawHeight / 2 > height) continue;
                context.drawImage(sprite, screenX - drawWidth / 2, screenY - drawHeight / 2, drawWidth, drawHeight);
            }
        }

        destroy() {
            if (this.destroyed) return;
            this.destroyed = true;
            if (this.frame !== null) window.cancelAnimationFrame(this.frame);
            this.frame = null;
            window.removeEventListener('resize', this.onResize);
            window.removeEventListener('pointermove', this.onPointer);
            window.removeEventListener('pagehide', this.onPageHide);
            window.removeEventListener('pageshow', this.onPageShow);
            document.removeEventListener('visibilitychange', this.onVisibility);
            this.motion.removeEventListener('change', this.onMotion);
            this.image.onload = this.image.onerror = null;
            this.canvas.remove();
            this.particles.length = 0;
            instances.delete(this.root);
        }
    }

    function create(root, options) {
        if (!root || !root.isConnected) return null;
        if (instances.has(root)) return instances.get(root);
        const instance = new Snowfall(root, options);
        if (instance.destroyed) return null;
        instances.set(root, instance);
        return instance;
    }

    function scan() {
        document.querySelectorAll('.Snow[data-snow3]').forEach(root => {
            let options;
            try { options = JSON.parse(root.getAttribute('data-snow3')); }
            catch (_) { options = {}; }
            create(root, options);
        });
    }

    // Snow is decoration: start once the page has loaded and the main thread is
    // idle, so particle setup and the texture request never compete with content.
    function start() {
        if (window.requestIdleCallback) window.requestIdleCallback(scan, { timeout: 2000 });
        else window.setTimeout(scan, 1);
    }

    window.Snow3 = Object.freeze({ version: '2.0.0', create, scan });
    if (document.readyState === 'complete') start();
    else window.addEventListener('load', start, { once: true });
})(window, document);
