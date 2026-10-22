const motion = require('../../wp-content/themes/chassesautresor/assets/js/src/hunt-banner-sphere-motion');

describe('hunt banner sphere roll', () => {
  const cameraZ = 4.185;

  test('stops where the circumference meets the frame', () => {
    const halfWidth = 3;
    const frame = Math.atan(halfWidth / cameraZ);
    const naive = halfWidth - motion.RADIUS - 0.08;
    const limit = motion.travelLimit(halfWidth, cameraZ);

    expect(motion.silhouetteAngle(naive, cameraZ)).toBeGreaterThan(frame);
    expect(motion.silhouetteAngle(limit, cameraZ)).toBeLessThanOrEqual(frame);
    expect(limit).toBeGreaterThan(0);
    expect(limit).toBeLessThan(naive);
    expect(motion.travelLimit(1, cameraZ)).toBe(0);
  });

  test('rolls without slipping between the edges', () => {
    const state = {
      x: 0,
      velocityX: motion.BASE_SPEED,
      limit: 2,
    };

    const step = motion.stepSphere(state, 0.5, Math.random);

    expect(step.bounced).toBe(false);
    expect(step.x).toBeCloseTo(motion.BASE_SPEED * 0.5, 5);
    expect(step.velocityX).toBe(motion.BASE_SPEED);
    expect(step.rollAngle).toBeCloseTo(-(motion.BASE_SPEED * 0.5) / motion.RADIUS, 5);
  });

  test('does not travel when the banner is too narrow', () => {
    const state = {
      x: 0.2,
      velocityX: motion.BASE_SPEED,
      limit: 0.01,
    };

    const step = motion.stepSphere(state, 0.5, Math.random);

    expect(step.x).toBe(0.2);
    expect(step.rollAngle).toBe(0);
    expect(step.bounced).toBe(false);
  });

  test('leaves a side faster than it arrived, on a new spin axis', () => {
    const incoming = 0.22;
    const state = {
      x: 1,
      velocityX: incoming,
      limit: 1,
      exitSpeed: 0.6,
      arrivalSpeed: incoming,
      tumbleX: 0,
      tumbleY: 0,
    };
    const picked = motion.pickBounceSpeeds(incoming, () => 0);
    const tumble = motion.pickTumble(() => 0);
    const slope = (picked.arrivalSpeed - picked.exitSpeed) / 2;
    const nextSpeed = picked.exitSpeed * Math.exp(slope * 0.05);
    const distance = (nextSpeed - picked.exitSpeed) / slope;
    const step = motion.stepSphere(state, 0.05, () => 0);
    const spin = step.spins[0];

    expect(step.bounced).toBe(true);
    expect(step.exitSpeed).toBeCloseTo(picked.exitSpeed, 5);
    expect(step.arrivalSpeed).toBeCloseTo(picked.arrivalSpeed, 5);
    expect(step.arrivalSpeed).toBeLessThan(step.exitSpeed);
    expect(step.velocityX).toBeCloseTo(-nextSpeed, 5);
    expect(Math.abs(step.velocityX)).toBeGreaterThan(incoming + 0.1);
    expect(step.x).toBeCloseTo(1 - distance, 5);
    expect(step.rollAngle).toBeCloseTo(-(step.x - state.x) / motion.RADIUS, 5);
    expect(step.tumbleX).toBeCloseTo(tumble.x, 5);
    expect(step.tumbleY).toBeCloseTo(tumble.y, 5);
    expect(Math.hypot(step.tumbleX, step.tumbleY)).toBeGreaterThan(0.8);
    expect(Math.abs(spin.x) + Math.abs(spin.y)).toBeGreaterThan(0.2);
    expect(spin.z * spin.angle).toBeCloseTo(step.rollAngle, 5);
    expect(step.kickAngle).toBeUndefined();
  });

  test('a harder rebound leaves faster than a soft one', () => {
    const state = {
      x: -1,
      velocityX: -0.2,
      limit: 1,
      exitSpeed: 0.55,
      arrivalSpeed: 0.2,
      tumbleX: 0.2,
      tumbleY: 0.2,
    };

    const soft = motion.stepSphere(state, 0.05, () => 0);
    const hard = motion.stepSphere(state, 0.05, () => 1);

    expect(soft.bounced).toBe(true);
    expect(hard.bounced).toBe(true);
    expect(soft.velocityX).toBeGreaterThan(0.2);
    expect(hard.velocityX).toBeGreaterThan(soft.velocityX + 0.15);
    expect(hard.exitSpeed).toBeLessThanOrEqual(motion.PEAK_SPEED);
    expect(Math.hypot(hard.tumbleX - soft.tumbleX, hard.tumbleY - soft.tumbleY)).toBeGreaterThan(0.3);
    expect(soft.kickAngle).toBeUndefined();
    expect(hard.kickAngle).toBeUndefined();
  });

  test('eases from the rebound speed down to the far edge', () => {
    const exitSpeed = 0.7;
    const arrivalSpeed = 0.2;
    const limit = 2;
    let state = {
      x: -limit,
      velocityX: exitSpeed,
      limit,
      exitSpeed,
      arrivalSpeed,
      tumbleX: 1.1,
      tumbleY: -0.4,
    };
    let previous = exitSpeed;
    let slowed = false;
    let bounce = null;

    for (let frame = 0; frame < 4000; frame += 1) {
      const step = motion.stepSphere(state, 1 / 60, () => 0.35);
      expect(Math.abs(step.x)).toBeLessThanOrEqual(limit + 1e-9);
      expect(step.rollAngle).toBeCloseTo(-(step.x - state.x) / motion.RADIUS, 5);
      if (step.bounced) {
        bounce = step;
        expect(Math.abs(step.velocityX)).toBeGreaterThan(previous + 0.1);
        expect(Math.hypot(step.tumbleX - state.tumbleX, step.tumbleY - state.tumbleY)).toBeGreaterThan(0.2);
        break;
      }
      expect(step.velocityX).toBeLessThan(previous);
      if (step.velocityX < previous - 0.001) {
        slowed = true;
      }
      previous = step.velocityX;
      state = {
        x: step.x,
        velocityX: step.velocityX,
        limit,
        exitSpeed: step.exitSpeed,
        arrivalSpeed: step.arrivalSpeed,
        tumbleX: step.tumbleX,
        tumbleY: step.tumbleY,
      };
    }

    expect(slowed).toBe(true);
    expect(previous).toBeLessThan(0.28);
    expect(previous).toBeGreaterThan(arrivalSpeed - 0.02);
    expect(bounce).not.toBeNull();
  });

  test('turns around at the circumference without leaving the frame', () => {
    const state = {
      x: 0.9,
      velocityX: 0.4,
      limit: 1,
    };
    const step = motion.stepSphere(state, 0.5, () => 0);

    expect(step.bounced).toBe(true);
    expect(step.velocityX).toBeLessThan(0);
    expect(Math.abs(step.velocityX)).toBeGreaterThan(0.4);
    expect(Math.abs(step.x)).toBeLessThanOrEqual(1);
    expect(step.x).toBeLessThan(1);
    expect(step.rollAngle).toBeCloseTo(-(step.x - state.x) / motion.RADIUS, 4);
    expect(step.spins.length).toBeGreaterThan(1);
    expect(Math.hypot(step.tumbleX, step.tumbleY)).toBeGreaterThan(0.8);
  });

  test('keeps every rebound inside the frame and between the speed bounds', () => {
    const state = motion.createRollState(1.4, () => 0.2);
    let bounces = 0;
    let previousX = state.x;
    const tumbles = [];

    for (let frame = 0; frame < 1600; frame += 1) {
      const step = motion.stepSphere(state, 1 / 60, Math.random);
      expect(Math.abs(step.x - previousX)).toBeLessThanOrEqual(motion.PEAK_SPEED / 60 + 1e-6);
      expect(Math.abs(step.velocityX)).toBeGreaterThanOrEqual(motion.MIN_SPEED - 1e-6);
      expect(Math.abs(step.velocityX)).toBeLessThanOrEqual(motion.PEAK_SPEED + 1e-6);
      expect(Math.abs(step.x)).toBeLessThanOrEqual(state.limit + 1e-9);
      previousX = step.x;
      state.x = step.x;
      state.velocityX = step.velocityX;
      state.exitSpeed = step.exitSpeed;
      state.arrivalSpeed = step.arrivalSpeed;
      state.tumbleX = step.tumbleX;
      state.tumbleY = step.tumbleY;
      if (step.bounced) {
        bounces += 1;
        tumbles.push([step.tumbleX, step.tumbleY]);
        expect(step.arrivalSpeed).toBeLessThan(step.exitSpeed);
      }
    }

    expect(bounces).toBeGreaterThan(2);
    const separated = tumbles.some((tumble, index) => {
      if (index === 0) {
        return false;
      }
      const previous = tumbles[index - 1];
      return Math.hypot(tumble[0] - previous[0], tumble[1] - previous[1]) > 0.15;
    });
    expect(separated).toBe(true);
  });

  test('starts halfway between the rebound speed and the far-edge speed', () => {
    let index = 0;
    const values = [0.1, 0.2, 0.4, 0.8, 0.3];
    const state = motion.createRollState(2, () => {
      const value = values[index];
      index += 1;
      return value;
    });

    expect(state.x).toBe(0);
    expect(state.velocityX).toBeLessThan(0);
    expect(Math.abs(state.velocityX)).toBeCloseTo((state.exitSpeed + state.arrivalSpeed) / 2, 5);
    expect(state.arrivalSpeed).toBeLessThan(state.exitSpeed);
    expect(state.exitSpeed).toBeLessThanOrEqual(motion.PEAK_SPEED);
    expect(state.arrivalSpeed).toBeGreaterThanOrEqual(motion.MIN_SPEED);
    expect(Math.hypot(state.tumbleX, state.tumbleY)).toBeGreaterThan(0.8);
  });

  test('places the shadow under the sphere and widens it on impact', () => {
    const calm = motion.shadowPlacement(0, 2, 0);
    const hit = motion.shadowPlacement(1, 2, 1);

    expect(calm.left).toBeCloseTo(50, 5);
    expect(calm.width).toBeCloseTo(32, 5);
    expect(hit.left).toBeCloseTo(75, 5);
    expect(hit.width).toBeGreaterThan(calm.width);
    expect(motion.shadowPlacement(0, 0.2, 0).width).toBe(72);
  });
});
