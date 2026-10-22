/**
 * Maps the hunt image onto a sphere for the home banner.
 * The sphere rolls sideways, rebounds on the left and right edges, and
 * a contact shadow follows it. Rebuild the footer bundle with:
 * npx esbuild assets/js/src/hunt-banner-sphere.js --bundle --format=iife --minify
 *   --target=es2018 --legal-comments=inline --outfile=assets/js/hunt-banner-sphere.js
 */
import {
  AmbientLight,
  DirectionalLight,
  Mesh,
  MeshStandardMaterial,
  PerspectiveCamera,
  Quaternion,
  Scene,
  SphereGeometry,
  SRGBColorSpace,
  TextureLoader,
  Vector3,
  WebGLRenderer,
} from 'three';
import {
  RADIUS,
  createRollState,
  shadowPlacement,
  stepSphere,
  travelLimit,
} from './hunt-banner-sphere-motion';

var reduceMotionQuery = window.matchMedia('(prefers-reduced-motion: reduce)');

function mount(root) {
  var url = root.getAttribute('data-image') || '';
  var canvas = root.querySelector('.hunt-banner__canvas');
  var fallback = root.querySelector('.hunt-banner__fallback');
  if (!url || !canvas) {
    root.classList.remove('is-pending');
    root.classList.add('is-fallback');
    return;
  }

  var renderer;
  try {
    renderer = new WebGLRenderer({
      canvas: canvas,
      alpha: true,
      antialias: true,
      powerPreference: 'low-power',
    });
  } catch (error) {
    root.classList.remove('is-pending');
    root.classList.add('is-fallback');
    return;
  }

  renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, 2));
  renderer.outputColorSpace = SRGBColorSpace;
  renderer.setClearColor(0x000000, 0);

  var scene = new Scene();
  var camera = new PerspectiveCamera(32, 1, 0.1, 20);

  scene.add(new AmbientLight(0xffffff, 0.62));
  var key = new DirectionalLight(0xffffff, 1.35);
  key.position.set(-2.4, 1.8, 3.2);
  scene.add(key);
  var fill = new DirectionalLight(0xb7d4e8, 0.38);
  fill.position.set(2.4, -0.6, 1.4);
  scene.add(fill);

  var material = new MeshStandardMaterial({
    color: 0xffffff,
    roughness: 0.76,
    metalness: 0.02,
  });
  var mesh = new Mesh(new SphereGeometry(RADIUS, 64, 48), material);
  scene.add(mesh);

  var roll = createRollState(0, Math.random);
  var axis = new Vector3(roll.rollAxis.x, roll.rollAxis.y, roll.rollAxis.z);
  var kick = new Vector3();
  var spin = new Quaternion();
  var viewHalfWidth = 1;
  var impact = 0;
  var running = false;
  var onScreen = true;
  var frameId = 0;
  var lastTime = 0;
  var resizeObserver = null;
  var screenObserver = null;
  var onResize = null;

  function placeShadow() {
    var placement = shadowPlacement(mesh.position.x, viewHalfWidth, impact);
    root.style.setProperty('--hunt-sphere-x', placement.left.toFixed(2) + '%');
    root.style.setProperty('--hunt-sphere-shadow', placement.width.toFixed(2) + '%');
  }

  function resize() {
    var width = root.clientWidth || 1;
    var height = root.clientHeight || 1;
    renderer.setSize(width, height, false);
    camera.aspect = width / height;
    var halfFov = (camera.fov * Math.PI) / 360;
    camera.position.z = 2.4 / (2 * Math.tan(halfFov));
    camera.updateProjectionMatrix();
    viewHalfWidth = camera.position.z * Math.tan(halfFov) * camera.aspect;
    roll.limit = travelLimit(viewHalfWidth);
    if (roll.x > roll.limit) {
      roll.x = roll.limit;
    } else if (roll.x < -roll.limit) {
      roll.x = -roll.limit;
    }
    mesh.position.x = roll.x;
    placeShadow();
  }

  function canRun() {
    return onScreen && !document.hidden && !root.classList.contains('is-fallback');
  }

  function stop() {
    running = false;
    if (frameId) {
      window.cancelAnimationFrame(frameId);
      frameId = 0;
    }
  }

  function start() {
    if (running || !canRun()) {
      return;
    }
    running = true;
    lastTime = 0;
    frameId = window.requestAnimationFrame(frame);
  }

  function frame(now) {
    if (!running) {
      return;
    }
    var time = typeof now === 'number' ? now : 0;
    var dt = lastTime > 0 ? (time - lastTime) / 1000 : 0;
    lastTime = time;
    if (dt > 0.05) {
      dt = 0.05;
    }

      if (reduceMotionQuery.matches) {
      if (mesh.position.x !== 0 || mesh.position.y !== 0 || impact > 0) {
        mesh.position.x = 0;
        mesh.position.y = 0;
        roll.x = 0;
        impact = 0;
        mesh.scale.set(1, 1, 1);
        placeShadow();
      }
    } else if (dt > 0) {
      var step = stepSphere(roll, dt, Math.random);
      roll.x = step.x;
      roll.velocityX = step.velocityX;
      mesh.position.x = step.x;
      axis.set(roll.rollAxis.x, roll.rollAxis.y, roll.rollAxis.z);
      spin.setFromAxisAngle(axis, step.rollAngle);
      mesh.quaternion.premultiply(spin);
      if (step.bounced) {
        roll.rollAxis = step.rollAxis;
        kick.set(step.kickAxis.x, step.kickAxis.y, step.kickAxis.z);
        spin.setFromAxisAngle(kick, step.kickAngle);
        mesh.quaternion.premultiply(spin);
        impact = 1;
      }
      if (impact > 0) {
        impact -= dt * 2.4;
        if (impact < 0) {
          impact = 0;
        }
      }
      mesh.position.y = -impact * 0.05;
      mesh.scale.set(1 + impact * 0.035, 1 - impact * 0.05, 1 + impact * 0.035);
      mesh.quaternion.normalize();
      placeShadow();
    }

    renderer.render(scene, camera);
    if (running) {
      frameId = window.requestAnimationFrame(frame);
    }
  }

  function fail() {
    stop();
    if (resizeObserver) {
      resizeObserver.disconnect();
    }
    if (screenObserver) {
      screenObserver.disconnect();
    }
    if (onResize) {
      window.removeEventListener('resize', onResize);
    }
    material.dispose();
    mesh.geometry.dispose();
    renderer.dispose();
    root.classList.remove('is-ready');
    root.classList.remove('is-pending');
    root.classList.add('is-fallback');
    if (fallback) {
      fallback.removeAttribute('aria-hidden');
    }
  }

  resize();

  if (typeof ResizeObserver === 'function') {
    resizeObserver = new ResizeObserver(resize);
    resizeObserver.observe(root);
  } else {
    onResize = resize;
    window.addEventListener('resize', onResize);
  }

  if (typeof IntersectionObserver === 'function') {
    screenObserver = new IntersectionObserver(function (entries) {
      onScreen = false;
      var index;
      for (index = 0; index < entries.length; index += 1) {
        if (entries[index].isIntersecting) {
          onScreen = true;
        }
      }
      if (onScreen) {
        start();
      } else {
        stop();
      }
    });
    screenObserver.observe(root);
  }

  document.addEventListener('visibilitychange', function () {
    if (document.hidden) {
      stop();
      return;
    }
    start();
  });

  start();

  var loader = new TextureLoader();
  loader.setCrossOrigin('anonymous');
  loader.load(url, function (texture) {
    if (root.classList.contains('is-fallback')) {
      texture.dispose();
      return;
    }
    texture.colorSpace = SRGBColorSpace;
    texture.anisotropy = Math.min(8, renderer.capabilities.getMaxAnisotropy());
    material.map = texture;
    material.needsUpdate = true;
    root.classList.add('is-ready');
    if (fallback) {
      fallback.setAttribute('aria-hidden', 'true');
    }
  }, undefined, fail);
}

function boot() {
  document.querySelectorAll('[data-hunt-sphere]').forEach(mount);
}

if (document.readyState === 'loading') {
  document.addEventListener('DOMContentLoaded', boot);
} else {
  boot();
}
