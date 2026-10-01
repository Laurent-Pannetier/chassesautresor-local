<?php

declare(strict_types=1);

namespace ChassesAuTresor\Core\Progress;

/** Render riddle statistics bars without theme template parts. */
final class RiddleBarRenderer {
    public function row(string $label, int $rate, string $fillStyle = ''): string {
        $inside = $rate >= 50;
        $style = $fillStyle === '' ? '' : $fillStyle . ';';
        $outsideStyle = $rate === 0 ? 'left:4px;' : 'left:calc(' . $rate . '% + 4px);';
        ob_start();
        ?>
        <div class="bar-row">
            <span class="bar-label"><?= esc_html($label); ?></span>
            <div class="bar-wrapper">
                <div class="bar-fill" style="<?= esc_attr($style); ?>width:<?= esc_attr($rate); ?>%;">
                    <?php if ($inside) : ?><span class="bar-value"><?= esc_html($rate); ?>%</span><?php endif; ?>
                </div>
                <?php if (!$inside) : ?>
                    <span class="bar-value bar-value--outside" style="<?= esc_attr($outsideStyle); ?>">
                        <?= esc_html($rate); ?>%
                    </span>
                <?php endif; ?>
            </div>
        </div>
        <?php
        return (string) ob_get_clean();
    }

    public function section(string $title, int $userRate, int $averageRate, string $class): string {
        return '<section class="' . esc_attr($class) . '"><h3>' . esc_html($title)
            . '</h3><div class="stats-bar-chart">'
            . $this->row(__('Vous', 'chassesautresor-com'), $userRate, 'background-color:var(--color-primary)')
            . $this->row(__('Moyenne', 'chassesautresor-com'), $averageRate)
            . '</div></section>';
    }

    public function subsection(
        string $title,
        int $userRate,
        int $averageRate,
        string $class,
        string $helpMessage = '',
        string $helpLabel = ''
    ): string {
        return '<div class="' . esc_attr($class) . '"><p class="aside-subsection-title">'
            . esc_html($title) . $this->help($helpMessage, $helpLabel)
            . '</p><div class="stats-bar-chart">'
            . $this->row(__('Vous', 'chassesautresor-com'), $userRate, 'background-color:var(--color-primary)')
            . $this->row(__('Moyenne', 'chassesautresor-com'), $averageRate)
            . '</div></div>';
    }

    public function singleSubsection(
        string $title,
        int $rate,
        string $class,
        string $helpMessage = '',
        string $helpLabel = ''
    ): string {
        return '<div class="' . esc_attr($class) . '"><p class="aside-subsection-title">'
            . esc_html($title) . $this->help($helpMessage, $helpLabel)
            . '</p><div class="stats-bar-chart">' . $this->row($title, $rate) . '</div></div>';
    }

    private function help(string $message, string $label): string {
        if ($message === '') {
            return '';
        }

        $accessibleLabel = $label !== '' ? $label : $message;

        return ' <span class="mode-fin-aide stat-help" role="img" tabindex="0" aria-label="'
            . esc_attr($accessibleLabel) . '" title="' . esc_attr($message) . '">?</span>';
    }
}
