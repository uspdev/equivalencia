<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Uspdev\Forms\Services\FormDefinitionSyncService;

class FormDefinitionsTableSeeder extends Seeder
{
    public function __construct(private readonly FormDefinitionSyncService $forms) {}

    public function run(): void
    {
        foreach (config('equivalencia-forms.definitions', []) as $name => $definition) {
            $fields = $definition['fields'] ?? [[
                'name' => $definition['name'],
                'type' => $definition['type'] ?? 'textarea',
                'label' => $definition['label'] ?? $name,
                'required' => $definition['required'] ?? false,
            ]];

            $this->forms->syncDefinition([
                'name' => $name,
                'version' => (int) ($definition['version'] ?? 1),
                'status' => $definition['status'] ?? 'active',
                'group' => $definition['group'] ?? config('equivalencia-forms.group', 'equivalencia'),
                'description' => $definition['description'] ?? null,
                'fields' => $fields,
            ]);
        }
    }
}
