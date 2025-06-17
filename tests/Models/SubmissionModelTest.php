<?php

namespace Littleboy130491\Sumimasen\Tests\Models;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Littleboy130491\Sumimasen\Models\Submission;
use Littleboy130491\Sumimasen\Tests\TestCase;

class SubmissionModelTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_create_a_submission()
    {
        $submission = Submission::create([
            'fields' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
                'message' => 'Test message',
            ],
        ]);

        $this->assertDatabaseHas('submissions', [
            'id' => $submission->id,
        ]);
    }

    /** @test */
    public function it_casts_fields_to_array()
    {
        $fields = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'phone' => '+1234567890',
            'message' => 'This is a test submission',
            'preferences' => [
                'newsletter' => true,
                'notifications' => false,
            ],
        ];

        $submission = Submission::create([
            'fields' => $fields,
        ]);

        $this->assertIsArray($submission->fields);
        $this->assertEquals($fields, $submission->fields);
    }

    /** @test */
    public function it_can_store_form_data_with_various_field_types()
    {
        $formData = [
            'contact_form' => [
                'first_name' => 'John',
                'last_name' => 'Doe',
                'email' => 'john.doe@example.com',
                'phone' => '+1-555-123-4567',
                'subject' => 'Website Inquiry',
                'message' => 'I would like to know more about your services.',
                'source' => 'website_contact_form',
                'interests' => ['web_development', 'seo', 'marketing'],
                'budget_range' => '$5000-$10000',
                'preferred_contact_method' => 'email',
                'agreed_to_terms' => true,
                'subscribe_newsletter' => false,
            ],
            'metadata' => [
                'ip_address' => '192.168.1.100',
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'referrer' => 'https://google.com',
                'submitted_at' => '2023-12-01 10:30:00',
                'form_version' => '1.2.3',
            ],
        ];

        $submission = Submission::create([
            'fields' => $formData,
        ]);

        $this->assertEquals($formData, $submission->fields);
        $this->assertEquals('John', $submission->fields['contact_form']['first_name']);
        $this->assertEquals('john.doe@example.com', $submission->fields['contact_form']['email']);
        $this->assertTrue($submission->fields['contact_form']['agreed_to_terms']);
        $this->assertFalse($submission->fields['contact_form']['subscribe_newsletter']);
        $this->assertIsArray($submission->fields['contact_form']['interests']);
        $this->assertCount(3, $submission->fields['contact_form']['interests']);
        $this->assertEquals('192.168.1.100', $submission->fields['metadata']['ip_address']);
    }

    /** @test */
    public function it_handles_empty_fields_correctly()
    {
        $submission = Submission::create([
            'fields' => [],
        ]);

        $this->assertIsArray($submission->fields);
        $this->assertEmpty($submission->fields);
    }

    /** @test */
    public function it_handles_null_fields_correctly()
    {
        $submission = Submission::create([
            'fields' => null,
        ]);

        $this->assertNull($submission->fields);
    }

    /** @test */
    public function it_can_update_fields()
    {
        $submission = Submission::create([
            'fields' => [
                'name' => 'John Doe',
                'email' => 'john@example.com',
            ],
        ]);

        $updatedFields = [
            'name' => 'Jane Doe',
            'email' => 'jane@example.com',
            'phone' => '+1234567890',
        ];

        $submission->update(['fields' => $updatedFields]);

        $this->assertEquals($updatedFields, $submission->fresh()->fields);
        $this->assertEquals('Jane Doe', $submission->fresh()->fields['name']);
        $this->assertEquals('+1234567890', $submission->fresh()->fields['phone']);
    }
}
