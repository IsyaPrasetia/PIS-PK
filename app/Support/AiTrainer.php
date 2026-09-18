<?php

namespace App\Support;

use App\Models\AiTrainingLog;
use Rubix\ML\Classifiers\SoftmaxClassifier;
use Rubix\ML\Datasets\Labeled;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;
use Rubix\ML\Pipeline;
use Rubix\ML\Tokenizers\Word;
use Rubix\ML\Transformers\WordCountVectorizer;

/**
 * Melatih classifier per indikator dari data feedback (ai_training_logs).
 *
 * Setiap indikator dilatih model Multi-class Softmax atas bag-of-words
 * catatan kunjungan. Model dipersist ke storage; hasil prediksi hanya
 * dipakai bila confidence tinggi (lihat AiPredictor), jika tidak ada model
 * atau confidence rendah `NoteExtractor` (aturan) yang mengambil alih.
 */
final class AiTrainer
{
    /** Model butuh minimal sampel per indikator sebelum bisa dilatih. */
    public const MIN_SAMPLES_PER_INDICATOR = 20;

    public function modelPath(string $indicatorId): string
    {
        $base = config('services.ai.models_path', storage_path('app/models/ai'));

        if (! is_dir($base)) {
            @mkdir($base, 0775, true);
        }

        return $base.DIRECTORY_SEPARATOR.'ind_'.$indicatorId.'.rbx';
    }

    /**
     * Latih model untuk seluruh indikator yang datanya cukup.
     *
     * @return array<string, array{trained: bool, samples: int, reason?: string}>
     */
    public function trainAll(): array
    {
        $logs = AiTrainingLog::query()
            ->whereNotNull('final_json')
            ->get();

        $report = [];

        foreach (Indikator::ids() as $id) {
            $report[$id] = $this->train($id, $logs);
        }

        return $report;
    }

    /**
     * Latih satu indikator.
     *
     * @param  iterable<int, AiTrainingLog>  $logs
     * @return array{trained: bool, samples: int, reason?: string}
     */
    public function train(string $indicatorId, iterable $logs): array
    {
        $samples = [];
        $labels = [];

        foreach ($logs as $log) {
            $final = $log->final_json ?? [];

            if (! isset($final['indikator'][$indicatorId])) {
                continue;
            }

            $note = is_string($log->catatan) ? trim($log->catatan) : '';

            if ($note === '') {
                continue;
            }

            $value = $final['indikator'][$indicatorId];

            if (! in_array($value, ['Y', 'T', 'N'], true)) {
                continue;
            }

            $samples[] = [$this->features($note)];
            $labels[] = $value;
        }

        $count = count($samples);

        if ($count < self::MIN_SAMPLES_PER_INDICATOR) {
            return ['trained' => false, 'samples' => $count, 'reason' => 'Sampel belum cukup (min '.self::MIN_SAMPLES_PER_INDICATOR.')'];
        }

        // Hapus model lama agar tidak tersisa saat data berubah.
        @unlink($this->modelPath($indicatorId));

        $dataset = Labeled::build($samples, $labels);

        $pipeline = new Pipeline(
            [
                new WordCountVectorizer(5000, 1, 0.9, new Word),
            ],
            new SoftmaxClassifier(100)
        );

        $estimator = new PersistentModel($pipeline, new Filesystem($this->modelPath($indicatorId), true));
        $estimator->train($dataset);
        $estimator->save();

        return ['trained' => true, 'samples' => $count];
    }

    /**
     * Fitur pembantu: normalisasi huruf kecil + kata ala Rubix dimasukkan
     * via WordCountVectorizer, jadi di sini cukup markup ringan.
     */
    private function features(string $note): string
    {
        return mb_strtolower($note);
    }
}
