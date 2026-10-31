<?php

namespace Tests\Unit;

use App\Models\Participant;
use Tests\TestCase;

class ParticipantMaskedNameTest extends TestCase
{
    public function test_masked_name_hides_last_name(): void
    {
        $participant = new Participant(['full_name' => 'Andi Pencari']);

        $this->assertSame('Andi P.', $participant->maskedName());
    }

    public function test_single_word_name_is_returned_unchanged(): void
    {
        $participant = new Participant(['full_name' => 'Sukarno']);

        $this->assertSame('Sukarno', $participant->maskedName());
    }
}
