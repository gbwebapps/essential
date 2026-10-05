<?php declare(strict_types = 1);

namespace Tests\Support\Libraries;

use App\Models\Backend\SettingsModel;
use CodeIgniter\Config\Factories;

trait MocksSettings
{
    protected function mockSettings(array $settings): SettingsModel
    {
        helper('settings');

        $model = $this->createMock(SettingsModel::class);
        $model->method('getSettings')
            ->willReturnCallback(static function(string $namespace) use ($settings): array {
                if ( ! array_key_exists($namespace, $settings)):
                    throw new \UnexpectedValueException('Configurazione di test non definita: ' . $namespace);
                endif;

                return $settings[$namespace];
            });

        Factories::injectMock('models', SettingsModel::class, $model);

        return $model;
    }

    protected function resetSettingsMocks(): void
    {
        Factories::reset();
    }
}
