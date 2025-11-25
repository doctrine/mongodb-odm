<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document(collection: 'patients')]
class Patient
{
    #[ODM\Id]
    public ?string $id;

    public function __construct(
        #[ODM\Field]
        public string $patientName,
        #[ODM\Field]
        public int $patientId,
        #[ODM\EmbedOne(targetDocument: PatientRecord::class)]
        public PatientRecord $patientRecord,
    ) {
    }
}
