<?php

declare(strict_types=1);

use Modules\Settings\Services\SettingValidationService;
use PHPUnit\Framework\TestCase;

require_once dirname(__DIR__, 2) . '/cms/system/Settings/Services/SettingValidationService.php';

final class SettingValidationServiceTest extends TestCase
{
    public function testRejectsHeaderInjection(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SettingValidationService())->validate([
            'field_type' => 'textarea',
            'validation_rule' => 'header',
            'setting_label' => 'Header',
        ], "same-origin\r\nX-Evil: yes");
    }

    public function testRequiresDefaultSrcForCsp(): void
    {
        $this->expectException(InvalidArgumentException::class);
        (new SettingValidationService())->validate([
            'field_type' => 'textarea',
            'validation_rule' => 'csp',
            'setting_label' => 'CSP',
        ], "script-src 'self'");
    }

    public function testNormalizesNumber(): void
    {
        self::assertSame('15', (new SettingValidationService())->validate([
            'field_type' => 'number',
            'min_value' => 1,
            'max_value' => 20,
        ], '15.9'));
    }
}
