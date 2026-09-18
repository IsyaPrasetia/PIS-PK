<?php

namespace Tests\Unit;

use App\Models\AiTrainingLog;
use App\Support\AiPredictor;
use App\Support\AiTrainer;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiTrainerTest extends TestCase
{
    use RefreshDatabase;

    private string $modelDir;

    protected function setUp(): void
    {
        parent::setUp();

        $this->modelDir = sys_get_temp_dir().DIRECTORY_SEPARATOR.'pispk-ml-'.uniqid();
        config(['services.ai.models_path' => $this->modelDir]);
    }

    protected function tearDown(): void
    {
        $this->removeDir($this->modelDir);

        parent::tearDown();
    }

    public function test_train_requires_minimum_samples(): void
    {
        AiTrainingLog::create([
            'catatan' => 'istri ikut KB suntik',
            'final_json' => ['indikator' => ['kb' => 'Y']],
            'created_by' => null,
            'created_at' => now(),
        ]);

        $report = (new AiTrainer)->train('kb', AiTrainingLog::all());

        $this->assertFalse($report['trained']);
        $this->assertLessThan(AiTrainer::MIN_SAMPLES_PER_INDICATOR, $report['samples']);
        $this->assertArrayHasKey('reason', $report);
    }

    public function test_softmax_classifier_learns_stroke_like_keywords(): void
    {
        for ($i = 0; $i < AiTrainer::MIN_SAMPLES_PER_INDICATOR; $i++) {
            $kb = $i % 2 === 0 ? 'Y' : 'T';
            $note = $kb === 'Y'
                ? "istri ikut KB suntik, anggota keluarga {$i}"
                : "belum ikut program KB, anggota keluarga {$i}";

            AiTrainingLog::create([
                'catatan' => $note,
                'final_json' => ['indikator' => ['kb' => $kb]],
                'created_by' => null,
                'created_at' => now(),
            ]);
        }

        $report = (new AiTrainer)->train('kb', AiTrainingLog::all());
        $this->assertTrue($report['trained']);
        $this->assertFileExists($report['path'] ?? $this->modelPath('kb'));

        $predictor = new AiPredictor;

        $yes = $predictor->predict('kb', 'istri ikut KB suntik');
        $no = $predictor->predict('kb', 'belum ikut program KB');

        $this->assertSame('Y', $yes['value'] ?? null);
        $this->assertSame('T', $no['value'] ?? null);
    }

    public function test_predict_returns_null_without_model(): void
    {
        $this->assertNull((new AiPredictor)->predict('kb', 'istri ikut KB suntik'));
    }

    private function modelPath(string $id): string
    {
        return $this->modelDir.DIRECTORY_SEPARATOR.'ind_'.$id.'.rbx';
    }

    private function removeDir(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        foreach (glob($dir.'/*') ?: [] as $file) {
            is_dir($file) ? $this->removeDir($file) : @unlink($file);
        }

        @rmdir($dir);
    }
}
