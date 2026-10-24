<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Notifications\NotificationService;
use ChassesAuTresor\Core\Notifications\NotificationStore;
use ChassesAuTresor\Core\Progress\AnswerResultNotificationService;
use ChassesAuTresor\Core\Progress\AttemptAlertContext;
use ChassesAuTresor\Core\Progress\AttemptAlertService;
use ChassesAuTresor\Core\Progress\RiddleStatisticsRepository;
use PHPUnit\Framework\TestCase;

final class AttemptAlertContextDouble extends AttemptAlertContext
{
    public bool $demo = false;
    public string $mode = 'automatique';
    public bool $staffPlayer = false;
    public int $solved = 1;
    /** @var int[] */
    public array $staff = [4, 8];
    public string $organizerEmail = 'orga@example.test';
    public string $adminEmail = 'admin@example.test';
    public string $reviewUrl = 'https://example.test/outils/?enigme=3&section=tentatives&uid=abc';
    public string $riddleUrl = 'https://example.test/enigme';
    public string $title = 'La porte';
    /** @var array{login:string,email:string,name:string}|null */
    public ?array $player = [
        'login' => 'alice',
        'email' => 'alice@example.test',
        'name' => 'Alice',
    ];

    public function isDemoMode(): bool
    {
        return $this->demo;
    }

    /** @return int[] */
    public function staffUserIds(int $riddleId): array
    {
        return $this->staff;
    }

    public function playerIsStaff(int $userId, int $riddleId): bool
    {
        return $this->staffPlayer;
    }

    public function organizerEmail(int $riddleId): string
    {
        return $this->organizerEmail;
    }

    public function adminEmail(): string
    {
        return $this->adminEmail;
    }

    public function isEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public function validationMode(int $riddleId): string
    {
        return $this->mode;
    }

    public function eligibleSolvedCount(int $riddleId): int
    {
        return $this->solved;
    }

    public function riddleTitle(int $riddleId): string
    {
        return $this->title;
    }

    /** @return array{login:string,email:string,name:string}|null */
    public function player(int $userId): ?array
    {
        return $this->player;
    }

    public function reviewUrl(int $riddleId, string $attemptUid): string
    {
        return $this->reviewUrl;
    }

    public function riddleUrl(int $riddleId): string
    {
        return $this->riddleUrl;
    }
}

final class AttemptAlertStore implements NotificationStore
{
    /** @var array<int, array<string, mixed>> */
    public array $notifications = [];
    /** @var array<int, array<int, true>> */
    public array $recipients = [];

    public function create(array $notification): int
    {
        foreach ($this->notifications as $id => $existing) {
            if ($existing['event_key'] === $notification['event_key']) {
                return $id;
            }
        }
        $id = count($this->notifications) + 1;
        $this->notifications[$id] = $notification;

        return $id;
    }

    public function eventId(string $eventKey): int
    {
        foreach ($this->notifications as $id => $existing) {
            if ($existing['event_key'] === $eventKey) {
                return (int) $id;
            }
        }

        return 0;
    }

    public function addRecipient(int $notificationId, int $userId): void
    {
        $this->recipients[$notificationId][$userId] = true;
    }

    public function listForUser(int $userId, int $limit = 50, int $offset = 0): array
    {
        return [];
    }

    public function countForUser(int $userId): int
    {
        return 0;
    }

    public function unreadCount(int $userId): int
    {
        return 0;
    }

    public function markRead(int $notificationId, int $userId): bool
    {
        return true;
    }

    public function markAllRead(int $userId): void
    {
    }

    public function nextOverlay(int $userId): ?array
    {
        return null;
    }

    public function renewOverlayClaim(int $notificationId, int $userId, string $claimToken): bool
    {
        return false;
    }

    public function markOverlaySeen(int $notificationId, int $userId, string $claimToken): bool
    {
        return false;
    }
}

final class AttemptAlertStatisticsDatabase
{
    public string $prefix = 'wp_';
    public string $query = '';
    /** @var array<int, mixed> */
    public array $arguments = [];

    public function prepare(string $query, ...$arguments): string
    {
        $this->query = $query;
        $this->arguments = $arguments;

        return $query;
    }

    public function get_var(string $query): string
    {
        return '1';
    }
}

final class AttemptAlertServiceTest extends TestCase
{
    /** @var array<int, array<int, mixed>> */
    private array $emails = [];

    protected function setUp(): void
    {
        $this->emails = [];
        $this->bootStrings();
    }

    public function testManualSubmissionMailsTheOrganizerAndNotifiesStaffWithoutTheAnswer(): void
    {
        $store = new AttemptAlertStore();
        $context = new AttemptAlertContextDouble();
        $service = $this->service($store, $context);

        $sent = $service->notifyManualSubmission(7, 3, 'phrase-secrete', 'uid-1');
        $again = $service->notifyManualSubmission(7, 3, 'phrase-secrete', 'uid-1');

        self::assertTrue($sent);
        self::assertTrue($again);
        self::assertCount(1, $this->emails);
        self::assertSame('orga@example.test', $this->emails[0][0]);
        self::assertStringContainsString('phrase-secrete', $this->emails[0][2]);
        self::assertStringContainsString('uid=abc', $this->emails[0][2]);
        self::assertCount(1, $store->notifications);
        $notification = $store->notifications[1];
        self::assertSame('center', $notification['channel']);
        self::assertSame('sha256:' . hash('sha256', 'manual-attempt:uid-1'), $notification['event_key']);
        self::assertStringNotContainsString('phrase-secrete', (string) $notification['body']);
        self::assertSame([4 => true, 8 => true], $store->recipients[1]);
    }

    public function testManualSubmissionIsSilentInDemoMode(): void
    {
        $store = new AttemptAlertStore();
        $context = new AttemptAlertContextDouble();
        $context->demo = true;

        self::assertFalse($this->service($store, $context)->notifyManualSubmission(7, 3, 'phrase-secrete', 'uid-1'));
        self::assertSame([], $this->emails);
        self::assertSame([], $store->notifications);
    }

    public function testMissingOrganizerAddressFallsBackToTheSiteAdmin(): void
    {
        $store = new AttemptAlertStore();
        $context = new AttemptAlertContextDouble();
        $context->organizerEmail = '';

        $this->service($store, $context)->notifyManualSubmission(7, 3, 'phrase-secrete', 'uid-1');

        self::assertSame('admin@example.test', $this->emails[0][0]);
    }

    public function testDecisionStaysInThePlayerNotificationCenter(): void
    {
        $store = new AttemptAlertStore();
        $service = $this->service($store, new AttemptAlertContextDouble());

        $approved = $service->notifyManualDecision(7, 3, 'bon', 'uid-1');
        $repeated = $service->notifyManualDecision(7, 3, 'bon', 'uid-1');
        $refused = $service->notifyManualDecision(7, 3, 'faux', 'uid-2');

        self::assertSame($approved, $repeated);
        self::assertSame('Réponse validée', $store->notifications[$approved]['title']);
        self::assertStringContainsString('Mon compte, Tentatives', (string) $store->notifications[$approved]['body']);
        self::assertSame([7 => true], $store->recipients[$approved]);
        self::assertSame('Réponse invalidée', $store->notifications[$refused]['title']);
        self::assertSame('center', $store->notifications[$refused]['channel']);
    }

    public function testFirstAutomaticSolveAlertsStaffOnceOutsideDemo(): void
    {
        $store = new AttemptAlertStore();
        $context = new AttemptAlertContextDouble();
        $service = $this->service($store, $context);

        self::assertTrue($service->notifyFirstAutomaticSolve(7, 3));
        self::assertTrue($service->notifyFirstAutomaticSolve(7, 3));
        self::assertCount(1, $store->notifications);
        self::assertSame('orga@example.test', $this->emails[0][0]);
        self::assertCount(1, $this->emails);
        self::assertSame('Première résolution', $store->notifications[1]['title']);
        self::assertSame('sha256:' . hash('sha256', 'first-solve:3'), $store->notifications[1]['event_key']);
        self::assertSame([4 => true, 8 => true], $store->recipients[1]);
    }

    public function testFirstSolveSkipsDemoManualModeStaffAndLaterSolvers(): void
    {
        $store = new AttemptAlertStore();
        $context = new AttemptAlertContextDouble();
        $service = $this->service($store, $context);

        $context->demo = true;
        self::assertFalse($service->notifyFirstAutomaticSolve(7, 3));
        $context->demo = false;
        $context->mode = 'manuelle';
        self::assertFalse($service->notifyFirstAutomaticSolve(7, 3));
        $context->mode = 'automatique';
        $context->staffPlayer = true;
        self::assertFalse($service->notifyFirstAutomaticSolve(7, 3));
        $context->staffPlayer = false;
        $context->solved = 2;
        self::assertFalse($service->notifyFirstAutomaticSolve(7, 3));
        self::assertSame([], $this->emails);
        self::assertSame([], $store->notifications);
    }

    public function testFirstSolveEmailDoesNotFallBackToTheSiteAdmin(): void
    {
        $store = new AttemptAlertStore();
        $context = new AttemptAlertContextDouble();
        $context->organizerEmail = '';

        self::assertTrue($this->service($store, $context)->notifyFirstAutomaticSolve(7, 3));
        self::assertSame([], $this->emails);
        self::assertCount(1, $store->notifications);
    }

    public function testEligibleSolvedCountExcludesStaff(): void
    {
        $database = new AttemptAlertStatisticsDatabase();
        $repository = new RiddleStatisticsRepository($database);

        self::assertSame(1, $repository->countEligibleSolvedPlayers(3, [4, 0, 4]));
        self::assertStringContainsString('NOT IN (%d)', $database->query);
        self::assertSame([3, 4], $database->arguments);
    }

    /**
     * @runInSeparateProcess
     * @preserveGlobalState disabled
     */
    public function testRefusalEmailSaysTheAnswerWasInvalidated(): void
    {
        eval(<<<'PHP'
function get_userdata($id) {
    return (object) [
        'user_email' => 'alice@example.test',
        'display_name' => 'Alice',
        'user_login' => 'alice',
    ];
}
function get_the_title($id) { return 'La porte'; }
function get_permalink($id) { return 'https://example.test/enigme'; }
function get_field($key, $id = 0, $format = true) { return ''; }
function get_option($key) { return 'admin@example.test'; }
function is_email($email) {
    return $email === 'alice@example.test' || $email === 'admin@example.test';
}
function wp_mail($to, $subject, $message, $headers = []) {
    $GLOBALS['attempt_alert_mail'] = [$to, $subject, $message, $headers];
    return true;
}
PHP);
        $GLOBALS['attempt_alert_mail'] = [];

        self::assertTrue((new AnswerResultNotificationService())->notify(7, 3, 'faux'));

        self::assertSame('alice@example.test', $GLOBALS['attempt_alert_mail'][0]);
        self::assertStringContainsString('Réponse invalidée', $GLOBALS['attempt_alert_mail'][1]);
        self::assertStringContainsString('Votre réponse a été invalidée.', $GLOBALS['attempt_alert_mail'][2]);
        self::assertStringNotContainsString('incorrecte', $GLOBALS['attempt_alert_mail'][2]);
    }

    private function service(AttemptAlertStore $store, AttemptAlertContextDouble $context): AttemptAlertService
    {
        return new AttemptAlertService(
            new NotificationService($store),
            $context,
            function (...$arguments): bool {
                $this->emails[] = $arguments;

                return true;
            }
        );
    }

    private function bootStrings(): void
    {
        if (!function_exists('__')) {
            eval('function __($text, $domain = null) { return $text; }');
        }
        if (!function_exists('esc_html')) {
            eval('function esc_html($text) { return (string) $text; }');
        }
        if (!function_exists('esc_html__')) {
            eval('function esc_html__($text, $domain = null) { return (string) $text; }');
        }
        if (!function_exists('esc_url')) {
            eval('function esc_url($text) { return (string) $text; }');
        }
    }
}
