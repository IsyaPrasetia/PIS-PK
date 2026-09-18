<?php

namespace App\Support;

use Rubix\ML\Datasets\Unlabeled;
use Rubix\ML\PersistentModel;
use Rubix\ML\Persisters\Filesystem;

/**
 * Prediktor ML untuk satu indikator: memuat model terlatih lalu menebak
 * jawaban (Y/T/N) berikut keyakinannya. Dipakai oleh AiController untuk
 * mengoreksi/validasi hasil aturan; bila confidence di bawah ambang,
 * hasil aturan tetap menang.
 */
final class AiPredictor
{
    /** Confidence minimum agar hasil ML dipakai menggantikan aturan. */
    public const MIN_CONFIDENCE = 0.75;

    /**
     * @return array{value: string, confidence: float}|null
     */
    public function predict(string $indicatorId, string $note): ?array
    {
        $path = (new AiTrainer)->modelPath($indicatorId);

        if (! is_file($path)) {
            return null;
        }

        try {
            $estimator = PersistentModel::load(new Filesystem($path));
            $dataset = Unlabeled::build([[$this->features($note)]]);
            $proba = $estimator->proba($dataset);
        } catch (\Throwable) {
            return null;
        }

        if (! isset($proba[0]) || ! is_array($proba[0])) {
            return null;
        }

        $row = $proba[0];
        arsort($row);

        $value = array_key_first($row);
        $confidence = (float) reset($row);
        $threshold = (float) config('services.ai.ml_confidence', self::MIN_CONFIDENCE);

        if (! in_array($value, ['Y', 'T', 'N'], true) || $confidence < $threshold) {
            return null;
        }

        return ['value' => $value, 'confidence' => $confidence];
    }

    private function features(string $note): string
    {
        return mb_strtolower($note);
    }
}
