<?php

namespace App\Http\Requests\Concerns;

use App\Exceptions\ApiException;
use Illuminate\Http\UploadedFile;

trait ValidatesEvidenceFile
{
    /**
     * @param  list<string>  $allowedMimes
     */
    protected function assertValidEvidence(
        ?UploadedFile $file,
        int $maxSizeMb = 5,
        array $allowedMimes = ['image/jpeg', 'image/png', 'application/pdf'],
    ): void {
        if ($file === null) {
            return;
        }

        if ($file->getSize() > $maxSizeMb * 1024 * 1024) {
            throw ApiException::payloadTooLarge("El archivo supera el límite de {$maxSizeMb} MB.", 'FILE01');
        }

        if (! in_array($file->getMimeType(), $allowedMimes, true)) {
            throw ApiException::unsupportedMediaType('Tipo de archivo no permitido. Solo JPG, PNG o PDF.', 'FILE02');
        }
    }
}
