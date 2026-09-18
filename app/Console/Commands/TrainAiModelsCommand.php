<?php

namespace App\Console\Commands;

use App\Models\AiTrainingLog;
use App\Support\AiTrainer;
use Illuminate\Console\Command;

class TrainAiModelsCommand extends Command
{
    protected $signature = 'ai:train
        {--indicator= : Latih hanya satu indikator (id: kb, tb, ...)}
        {--force : Latih walau sampel kurang}';

    protected $description = 'Latih classifier per indikator dari data ai_training_logs';

    public function handle(AiTrainer $trainer): int
    {
        $only = $this->option('indicator');

        if ($only) {
            $report = [$only => $trainer->train($only, AiTrainingLog::query()->whereNotNull('final_json')->get())];
        } else {
            $report = $trainer->trainAll();
        }

        $rows = [];

        foreach ($report as $id => $row) {
            $rows[] = [
                $id,
                $row['samples'],
                $row['trained'] ? 'terlatih' : $row['reason'],
            ];
        }

        $this->table(['Indikator', 'Sampel', 'Status'], $rows);

        return self::SUCCESS;
    }
}
