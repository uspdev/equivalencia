<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Uspdev\Forms\Http\Controllers\DefinitionController;
use Uspdev\Forms\Models\FormDefinition;
use Uspdev\Workflow\Models\WorkflowDefinition;
use function Laravel\Prompts\form;

class WorkflowSync extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'workflow:sync';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sincroniza o baco de dados com as novas definições de formulário para cada workflow, tratando edições, adições e remoções.';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $forms_dir = config('uspdev-forms.forms_storage_dir');
        $forms_arr = scandir($forms_dir);
        foreach($forms_arr as $forms_json_filename)
        {
            if($forms_json_filename != '.' && $forms_json_filename != '..')
            {
                if($forms_json_filename != 'forms.json')
                {   
                    $definition_json = '';
                    $forms_json_file = fopen($forms_dir . '/' . $forms_json_filename,'r');
                    while(true)
                    {
                        $append = fgets($forms_json_file);
                        if($append == false){break;}
                        $definition_json = $definition_json . $append;
                    }

                    $form_definition = json_decode($definition_json,true);
                    FormDefinition::updateOrCreate(
                        ['name' => $form_definition['name']],
                        [
                            'group' => $form_definition['group'],
                            'description' => $form_definition['description'],
                            'fields' => $form_definition['fields']
                        ]
                        );
                    fclose(($forms_json_file));
                }
            }
        }

        $workflow_dir = config('workflow.storagePath');
        $workflow_arr = scandir($workflow_dir);
        foreach($workflow_arr as $workflow_json_filename)
        {
            if($workflow_json_filename != '.' && $workflow_json_filename != '..')
            {
                $wf_definition_json = '';
                $workflow_json_file = fopen($workflow_dir . '/' . $workflow_json_filename,'r');
                 while(true)
                {
                    $append = fgets($workflow_json_file);
                    if($append == false){break;}
                    $wf_definition_json = $wf_definition_json . $append;
                }
                $workflow_def = json_decode($wf_definition_json,true);
                WorkflowDefinition::updateOrCreate(
                    ['name' => $workflow_def['name']],
                    [
                        'description' => $workflow_def['description'],
                        'definition' => $workflow_def,  
                    ]
                );
                fclose($workflow_json_file);
            }
        }
    }
}
