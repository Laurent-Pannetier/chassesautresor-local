/**
 * Lateral roll for the home-banner sphere.
 * The sphere travels on X, rolls without slipping, and rebounds on the
 * left and right edges with a nearby speed and a slightly new facing.
 */
var RADIUS = 1;
var EDGE_MARGIN = 0.08;
var BASE_SPEED = 0.32;
var MIN_SPEED = 0.24;
var MAX_SPEED = 0.44;

function travelLimit(halfWidth) {
  var limit = halfWidth - RADIUS - EDGE_MARGIN;
  return limit > 0 ? limit : 0;
}

function unitAxis(x, y, z) {
  var length = Math.hypot(x, y, z);
  if (length < 0.000001) {
    return { x: 0, y: 0, z: 1 };
  }
  return { x: x / length, y: y / length, z: z / length };
}

function tiltRollAxis(random) {
  return unitAxis(
    (random() - 0.5) * 0.34,
    (random() - 0.5) * 0.22,
    1
  );
}

function randomAxis(random) {
  return unitAxis(random() * 2 - 1, random() * 2 - 1, random() * 2 - 1);
}

function createRollState(limit, random) {
  var direction = random() < 0.5 ? -1 : 1;
  return {
    x: 0,
    velocityX: direction * BASE_SPEED,
    rollAxis: tiltRollAxis(random),
    limit: limit,
  };
}

/**
 * Advance one step. `random` returns a number in [0, 1).
 * `rollAngle` is the world rotation, in radians, around the axis held
 * before this step. A bounce replaces that axis afterwards.
 */
function stepSphere(state, dt, random) {
  var next = {
    x: state.x,
    velocityX: state.velocityX,
    rollAxis: state.rollAxis,
    rollAngle: 0,
    bounced: false,
    kickAngle: 0,
    kickAxis: { x: 0, y: 0, z: 1 },
  };
  if (!(dt > 0) || !(state.limit > 0.02)) {
    return next;
  }

  var distance = state.velocityX * dt;
  var x = state.x + distance;
  var bounced = false;
  if (x > state.limit) {
    x = state.limit;
    bounced = true;
  } else if (x < -state.limit) {
    x = -state.limit;
    bounced = true;
  }

  next.x = x;
  next.rollAngle = -distance / RADIUS;
  if (!bounced) {
    return next;
  }

  var speed = Math.abs(state.velocityX) * (0.9 + random() * 0.18);
  if (speed < MIN_SPEED) {
    speed = MIN_SPEED;
  } else if (speed > MAX_SPEED) {
    speed = MAX_SPEED;
  }
  next.velocityX = (state.velocityX >= 0 ? -1 : 1) * speed;
  next.rollAxis = tiltRollAxis(random);
  next.kickAxis = randomAxis(random);
  next.kickAngle = 0.1 + random() * 0.22;
  next.bounced = true;
  return next;
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
  stepSphere: stepSphere,
  travelLimit: travelLimit,
};
