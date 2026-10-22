/**
 * Lateral roll for the home-banner sphere.
 * The center stops where the silhouette meets the frame, so the circumference
 * touches the edge. The rebound reverses the roll along the path actually
 * traveled, without an extra spin.
 */
var RADIUS = 1;
var BASE_SPEED = 0.32;
var MIN_SPEED = 0.24;
var MAX_SPEED = 0.44;
var CONTACT_MARGIN = 0.012;

function silhouetteAngle(x, cameraZ) {
  var distance = Math.hypot(x, cameraZ);
  if (!(distance > RADIUS)) {
    return Math.PI;
  }
  return Math.atan2(Math.abs(x), cameraZ) + Math.asin(RADIUS / distance);
}

/**
 * Largest |x| whose circumference stays inside the frame.
 * `halfWidth` is the visible half-width on the plane z = 0.
 */
function travelLimit(halfWidth, cameraZ) {
  if (!(halfWidth > 0) || !(cameraZ > RADIUS)) {
    return 0;
  }
  var frame = Math.atan(halfWidth / cameraZ);
  if (!(frame > silhouetteAngle(0, cameraZ) + CONTACT_MARGIN)) {
    return 0;
  }

  var low = 0;
  var high = halfWidth;
  var step;
  for (step = 0; step < 28; step += 1) {
    var mid = (low + high) / 2;
    if (silhouetteAngle(mid, cameraZ) + CONTACT_MARGIN <= frame) {
      low = mid;
    } else {
      high = mid;
    }
  }
  return low > 0.02 ? low : 0;
}

function reflectedSpeed(velocity, random) {
  var speed = Math.abs(velocity) * (0.9 + random() * 0.18);
  if (speed < MIN_SPEED) {
    speed = MIN_SPEED;
  } else if (speed > MAX_SPEED) {
    speed = MAX_SPEED;
  }
  return (velocity >= 0 ? -1 : 1) * speed;
}

function createRollState(limit, random) {
  var direction = random() < 0.5 ? -1 : 1;
  return {
    x: 0,
    velocityX: direction * BASE_SPEED,
    limit: limit,
  };
}

/**
 * Advance one step. `random` returns a number in [0, 1).
 * `rollAngle` matches the signed distance the center really moves.
 */
function stepSphere(state, dt, random) {
  var x = state.x;
  var velocity = state.velocityX;
  var limit = state.limit;
  if (!(dt > 0) || !(limit > 0.02)) {
    return { x: x, velocityX: velocity, rollAngle: 0, bounced: false };
  }
  if (x > limit) {
    x = limit;
  } else if (x < -limit) {
    x = -limit;
  }

  var remaining = dt;
  var traveled = 0;
  var bounced = false;
  var passes = 0;
  while (remaining > 0.0000001 && passes < 8) {
    passes += 1;
    var bound = velocity >= 0 ? limit : -limit;
    var timeToBound = velocity === 0 ? Infinity : (bound - x) / velocity;
    if (!(timeToBound >= 0) || timeToBound > remaining) {
      var freeDistance = velocity * remaining;
      x += freeDistance;
      traveled += freeDistance;
      remaining = 0;
      break;
    }
    var distanceToBound = velocity * timeToBound;
    x = bound;
    traveled += distanceToBound;
    remaining -= timeToBound;
    velocity = reflectedSpeed(velocity, random);
    bounced = true;
  }

  return {
    x: x,
    velocityX: velocity,
    rollAngle: -traveled / RADIUS,
    bounced: bounced,
  };
}

/**
 * Shadow center and width, in percent of the banner.
 * `halfWidth` is the visible half-width at the sphere, in world units.
 * `impact` is 1 on a bounce and eases back to 0.
 */
function shadowPlacement(x, halfWidth, impact) {
  var safeHalf = halfWidth > 0.001 ? halfWidth : 0.001;
  var strength = impact > 0 ? impact : 0;
  if (strength > 1) {
    strength = 1;
  }
  var width = (100 / safeHalf) * (0.64 + strength * 0.18);
  if (width > 72) {
    width = 72;
  }
  return {
    left: 50 + (x / (2 * safeHalf)) * 100,
    width: width,
  };
}

module.exports = {
  BASE_SPEED: BASE_SPEED,
  MAX_SPEED: MAX_SPEED,
  MIN_SPEED: MIN_SPEED,
  RADIUS: RADIUS,
  createRollState: createRollState,
  shadowPlacement: shadowPlacement,
  silhouetteAngle: silhouetteAngle,
  stepSphere: stepSphere,
  travelLimit: travelLimit,
};
