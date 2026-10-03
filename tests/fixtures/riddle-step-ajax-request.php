<?php

declare(strict_types=1);

use ChassesAuTresor\Core\Progress\RiddleStepClickAjaxHandler;
use ChassesAuTresor\Core\Progress\RiddleStepTextAjaxHandler;

final class RiddleStepAjaxTermination extends RuntimeException {
    /** @var mixed */
    public $responseBody;

    /** @param mixed $responseBody */
    public function __construct($responseBody, int $statusCode) {
        parent::__construct('AJAX request terminated', $statusCode);
        $this->responseBody = $responseBody;
    }
}

$handler = $argv[1] ?? '';
$scenario = $argv[2] ?? '';
$GLOBALS['riddle_step_ajax_nonce_action'] = null;
$GLOBALS['riddle_step_ajax_scenario'] = $scenario;

$_POST = [
    'enigme_id' => '42',
    'etape_id' => '84',
    'nonce' => $scenario === 'invalid_nonce' ? 'invalid' : 'valid',
];
if ($handler === 'text') {
    $_POST['reponse'] = '';
}

function check_ajax_referer($action, $queryArgument = false): bool {
    $GLOBALS['riddle_step_ajax_nonce_action'] = $action;
    $nonce = $queryArgument !== false ? ($_POST[(string) $queryArgument] ?? '') : '';
    if ($nonce !== 'valid') {
        throw new RiddleStepAjaxTermination('-1', 403);
    }

    return true;
}

function get_current_user_id(): int {
    return $GLOBALS['riddle_step_ajax_scenario'] === 'anonymous' ? 0 : 7;
}

function get_post_type($postId): string {
    if ((int) $postId === 42) {
        return 'enigme';
    }

    return (int) $postId === 84 ? 'enigme_etape' : '';
}

function utilisateur_peut_voir_enigme($riddleId, $userId): bool {
    return (int) $riddleId === 42 && (int) $userId === 7;
}

function utilisateur_peut_modifier_post($postId): bool {
    return false;
}

function get_field($field, $postId = null) {
    $values = [
        'etape_enigme_associee' => 42,
        'etape_reponse_widget' => $GLOBALS['riddle_step_ajax_handler'] ?? 'text',
        'etape_reponses_texte' => "answer\nother answer",
        'etape_reponse_casse' => false,
        'etape_reponses_variantes' => '',
        'etape_reponse_bouton' => 'Continue',
    ];

    return $values[$field] ?? null;
}

function sanitize_text_field($value): string {
    return trim((string) $value);
}

function wp_unslash($value) {
    return $value;
}

function __($text, $domain = 'default'): string {
    return (string) $text;
}

function wp_send_json_error($data = null, $statusCode = null): void {
    throw new RiddleStepAjaxTermination(
        ['success' => false, 'data' => $data],
        $statusCode === null ? 200 : (int) $statusCode
    );
}

require_once dirname(__DIR__) . '/bootstrap.php';

$GLOBALS['riddle_step_ajax_handler'] = $handler === 'click' ? 'click' : 'text';

try {
    if ($handler === 'click') {
        RiddleStepClickAjaxHandler::submit();
    } elseif ($handler === 'text') {
        RiddleStepTextAjaxHandler::submit();
    } else {
        throw new InvalidArgumentException('Unknown handler');
    }
} catch (RiddleStepAjaxTermination $termination) {
    echo json_encode([
        'status' => $termination->getCode(),
        'body' => $termination->responseBody,
        'nonce_action' => $GLOBALS['riddle_step_ajax_nonce_action'],
    ], JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    exit(0);
}

exit(1);
