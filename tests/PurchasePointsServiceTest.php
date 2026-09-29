<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Points\PointsService;
use ChassesAuTresor\Core\Points\PurchasePointsService;
use PHPUnit\Framework\TestCase;

if (!function_exists('__')) {
    function __(string $text, string $domain = 'default'): string
    {
        return $text;
    }
}

require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsRepository.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Points/PointsService.php';
require_once __DIR__
    . '/../wp-content/plugins/chassesautresor-core/src/Points/PurchasePointsService.php';

class PurchasePointsRecorder extends PointsService
{
    /** @var array<int, mixed> */
    public array $operation = [];

    public function __construct()
    {
    }

    public function add(
        int $userId,
        int $amount,
        string $reason = '',
        string $originType = 'admin',
        ?int $originId = null
    ): void {
        $this->operation = [$userId, $amount, $reason, $originType, $originId];
    }
}

class PurchasePointsServiceTest extends TestCase
{
    public function testAwardOrderCreditsPackAndMarksOrder(): void
    {
        $points = new PurchasePointsRecorder();
        $order = new PurchasePointsOrder(18, 7, [new PurchasePointsItem('pack-500-points', 2)]);
        $service = new PurchasePointsService($points);

        $this->assertSame(1000, $service->awardOrder($order));
        $this->assertSame([7, 1000, 'Achat de 1000 points (commande #18)', 'achat', 18], $points->operation);
        $this->assertTrue($order->awarded);
        $this->assertTrue($order->saved);
        $this->assertSame(['✅ 1000 points ajoutés.'], $order->notes);

        $this->assertSame(0, $service->awardOrder($order));
    }
}

class PurchasePointsOrder
{
    public bool $awarded = false;
    public bool $saved = false;

    /** @var string[] */
    public array $notes = [];

    /** @param PurchasePointsItem[] $items */
    public function __construct(private int $id, private int $userId, private array $items)
    {
    }

    public function get_meta(string $key): bool
    {
        return $this->awarded;
    }

    public function get_user_id(): int
    {
        return $this->userId;
    }

    public function get_id(): int
    {
        return $this->id;
    }

    /** @return PurchasePointsItem[] */
    public function get_items(): array
    {
        return $this->items;
    }

    public function add_order_note(string $note): void
    {
        $this->notes[] = $note;
    }

    public function update_meta_data(string $key, bool $value): void
    {
        $this->awarded = $value;
    }

    public function save(): void
    {
        $this->saved = true;
    }
}

class PurchasePointsItem
{
    public function __construct(private string $slug, private int $quantity)
    {
    }

    public function get_product(): object
    {
        return new class ($this->slug) {
            public function __construct(private string $slug)
            {
            }

            public function get_slug(): string
            {
                return $this->slug;
            }
        };
    }

    public function get_quantity(): int
    {
        return $this->quantity;
    }
}
