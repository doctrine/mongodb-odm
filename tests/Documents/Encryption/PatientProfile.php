<?php

declare(strict_types=1);

namespace Documents\Encryption;

use Doctrine\ODM\MongoDB\Mapping\Attribute as ODM;

#[ODM\Document(collection: 'patient_profile')]
class PatientProfile
{
    #[ODM\Id]
    public ?string $id;

    public function __construct(
        #[ODM\Field]
        #[ODM\Encrypt]
        public string $nationalId,
        #[ODM\EmbedOne(targetDocument: Insurance::class)]
        public Insurance $insurance,
        #[ODM\EmbedOne(targetDocument: Contact::class)]
        public Contact $contact,
        #[ODM\EmbedOne(targetDocument: Contact::class)]
        public Contact $emergencyContact,
    ) {
    }
}
