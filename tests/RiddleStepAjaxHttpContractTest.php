<?php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;

final class RiddleStepAjaxHttpContractTest extends TestCase {
    /**
     * @dataProvider nonceProvider
     */
    public function testRejectsInvalidNonceAtTheHttpBoundary(string $handler, string $nonceAction): void {
        $response = $this->runRequest($handler, 'invalid_nonce');

        self::assertSame(403, $response['status']);
        self::assertSame('-1', $response['body']);
        self::assertSame($nonceAction, $response['nonce_action']);
    }

    /** @return array<string,array{string,string}> */
    public function nonceProvider(): array {
        return [
            'text answer' => ['text', 'riddle_step_answer'],
            'click confirmation' => ['click', 'riddle_step_click'],
        ];
    }

    public function testTextHandlerReturnsTheExpectedJsonErrorEnvelope(): void {
        $response = $this->runRequest('text', 'invalid_payload');

        self::assertSame(200, $response['status']);
        self::assertSame([
            'success' => false,
            'data' => ['message' => 'Réponse invalide.'],
        ], $response['body']);
    }

    public function testClickHandlerReturnsAccessDeniedForAnonymousPlayer(): void {
        $response = $this->runRequest('click', 'anonymous');

        self::assertSame(200, $response['status']);
        self::assertSame([
            'success' => false,
            'data' => ['message' => 'Accès refusé.'],
        ], $response['body']);
    }

    /** @return array{status:int,body:mixed,nonce_action:string} */
    private function runRequest(string $handler, string $scenario): array {
        $command = sprintf(
            '%s %s %s %s',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__DIR__ . '/fixtures/riddle-step-ajax-request.php'),
            escapeshellarg($handler),
            escapeshellarg($scenario)
        );
        exec($command, $output, $exitCode);

        self::assertSame(0, $exitCode, implode("\n", $output));
        $response = json_decode(implode("\n", $output), true, 512, JSON_THROW_ON_ERROR);
        self::assertIsArray($response);

        return $response;
    }
}
