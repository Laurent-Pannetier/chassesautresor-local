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

  test('leaves the right edge along the path actually traveled', () => {
    const state = {
      x: 1,
      velocityX: 0.32,
      limit: 1,
    };

    const step = motion.stepSphere(state, 0.05, () => 0);
    const speed = 0.288;

    expect(step.bounced).toBe(true);
    expect(step.velocityX).toBeCloseTo(-speed, 5);
    expect(step.x).toBeCloseTo(1 - speed * 0.05, 5);
    expect(step.rollAngle).toBeCloseTo((speed * 0.05) / motion.RADIUS, 5);
    expect(step.kickAngle).toBeUndefined();
  });

  test('leaves the left edge with a slightly higher speed', () => {
    const state = {
      x: -1,
      velocityX: -0.32,
      limit: 1,
    };

    const step = motion.stepSphere(state, 0.05, () => 1);

    expect(step.bounced).toBe(true);
    expect(step.x).toBeCloseTo(-1 + 0.3456 * 0.05, 5);
    expect(step.velocityX).toBeCloseTo(0.3456, 5);
    expect(step.kickAngle).toBeUndefined();
  });

  test('turns around at the circumference without spinning past it', () => {
    const state = {
      x: 0.9,
      velocityX: 0.4,
      limit: 1,
    };
    const dt = 0.5;
    const step = motion.stepSphere(state, dt, () => 0);
    const timeToWall = 0.1 / 0.4;
    const speed = 0.36;
    const timeAfter = dt - timeToWall;
    const traveled = 0.1 - speed * timeAfter;

    expect(step.bounced).toBe(true);
    expect(step.velocityX).toBeCloseTo(-speed, 5);
    expect(step.x).toBeCloseTo(1 - speed * timeAfter, 5);
    expect(step.rollAngle).toBeCloseTo(-traveled / motion.RADIUS, 5);
    expect(Math.abs(step.x)).toBeLessThanOrEqual(1);
  });

  test('keeps the speed in a narrow band across repeated bounces', () => {
    const state = {
      x: 0,
      velocityX: motion.MAX_SPEED,
      limit: 0.5,
    };
    let bounces = 0;
    let previousX = state.x;

    for (let frame = 0; frame < 800; frame += 1) {
      const step = motion.stepSphere(state, 1 / 60, Math.random);
      expect(Math.abs(step.x - previousX)).toBeLessThanOrEqual(motion.MAX_SPEED / 60 + 1e-9);
      previousX = step.x;
      state.x = step.x;
      state.velocityX = step.velocityX;
      if (step.bounced) {
        bounces += 1;
        expect(Math.abs(step.velocityX)).toBeGreaterThanOrEqual(motion.MIN_SPEED);
        expect(Math.abs(step.velocityX)).toBeLessThanOrEqual(motion.MAX_SPEED);
      }
      expect(Math.abs(state.x)).toBeLessThanOrEqual(0.5 + 1e-9);
    }

    expect(bounces).toBeGreaterThan(2);
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
