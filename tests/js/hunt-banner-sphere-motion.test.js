const motion = require('../../wp-content/themes/chassesautresor/assets/js/src/hunt-banner-sphere-motion');

describe('hunt banner sphere roll', () => {
  test('keeps the sphere inside the frame', () => {
    expect(motion.travelLimit(3)).toBeCloseTo(1.92, 5);
    expect(motion.travelLimit(1)).toBe(0);
  });

  test('rolls without slipping between the edges', () => {
    const state = {
      x: 0,
      velocityX: motion.BASE_SPEED,
      rollAxis: { x: 0, y: 0, z: 1 },
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
      rollAxis: { x: 0, y: 0, z: 1 },
      limit: 0.01,
    };

    const step = motion.stepSphere(state, 0.5, Math.random);

    expect(step.x).toBe(0.2);
    expect(step.rollAngle).toBe(0);
    expect(step.bounced).toBe(false);
  });

  test('rebounds to the left with a slightly lower speed', () => {
    const state = {
      x: 1,
      velocityX: 0.32,
      rollAxis: { x: 0, y: 0, z: 1 },
      limit: 1,
    };

    const step = motion.stepSphere(state, 0.05, () => 0);

    expect(step.bounced).toBe(true);
    expect(step.x).toBe(1);
    expect(step.velocityX).toBeCloseTo(-0.288, 5);
    expect(step.rollAngle).toBeCloseTo(-0.016, 5);
    expect(step.kickAngle).toBeCloseTo(0.1, 5);
    expect(step.rollAxis.z).toBeLessThan(1);
    expect(Math.hypot(step.rollAxis.x, step.rollAxis.y, step.rollAxis.z)).toBeCloseTo(1, 5);
  });

  test('rebounds to the right with a slightly higher speed', () => {
    const state = {
      x: -1,
      velocityX: -0.32,
      rollAxis: { x: 0, y: 0, z: 1 },
      limit: 1,
    };

    const step = motion.stepSphere(state, 0.05, () => 1);

    expect(step.bounced).toBe(true);
    expect(step.x).toBe(-1);
    expect(step.velocityX).toBeCloseTo(0.3456, 5);
    expect(step.kickAngle).toBeCloseTo(0.32, 5);
  });

  test('keeps the speed in a narrow band across repeated bounces', () => {
    const state = {
      x: 0,
      velocityX: motion.MAX_SPEED,
      rollAxis: { x: 0, y: 0, z: 1 },
      limit: 0.5,
    };
    let bounces = 0;

    for (let frame = 0; frame < 800; frame += 1) {
      const step = motion.stepSphere(state, 1 / 60, Math.random);
      state.x = step.x;
      state.velocityX = step.velocityX;
      if (step.bounced) {
        bounces += 1;
        state.rollAxis = step.rollAxis;
        expect(Math.abs(step.velocityX)).toBeGreaterThanOrEqual(motion.MIN_SPEED);
        expect(Math.abs(step.velocityX)).toBeLessThanOrEqual(motion.MAX_SPEED);
        expect(step.kickAngle).toBeGreaterThanOrEqual(0.1);
        expect(step.kickAngle).toBeLessThanOrEqual(0.32);
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
