<?php

namespace App\Http\Requests\Concerns;

use App\Exceptions\ApiException;
use Illuminate\Http\UploadedFile;

trait ValidatesEvidenceFile
{
    protected function assertValidEvidence(?UploadedFile $file): void
    {
        if ($file === null) {
            return;
        }

        if ($file->getSize() > 5 * 1024 * 1024) {
            throw ApiException::payloadTooLarge('El archivo supera el límite de 5 MB.', 'FILE01');
        }

        if (! in_array($file->getMimeType(), ['image/jpeg', 'image/png', 'application/pdf'], true)) {
            throw ApiException::unsupportedMediaType('Tipo de archivo no permitido. Solo JPG, PNG o PDF.', 'FILE02');
        }
    }
}
