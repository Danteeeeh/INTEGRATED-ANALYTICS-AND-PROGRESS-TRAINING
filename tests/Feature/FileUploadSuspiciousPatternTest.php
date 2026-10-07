<?php

namespace Tests\Feature;

use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

/**
 * FileUploadService::checkForExecutableFile().
 *
 * It looped over $suspiciousPatterns, but that property was never declared, so
 * every upload that reached this check died with
 * "Undefined variable $suspiciousPatterns". PDFs appeared to work only because
 * assignment uploads bypass this service and call storeAs() directly — module
 * attachments, lesson materials and the file API all crashed.
 */
class FileUploadSuspiciousPatternTest extends TestCase
{
    protected const DOCX_MIME = 'application/vnd.openxmlformats-officedocument.wordprocessingml.document';

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('local');
    }

    private function service(): FileUploadService
    {
        return new FileUploadService;
    }

    /** @return array<string, array{0: string, 1: int, 2: string}> */
    public static function allowedFiles(): array
    {
        return [
            'pdf' => ['report.pdf', 20, 'application/pdf'],
            'docx' => ['reflection.docx', 12, self::DOCX_MIME],
            'plain text' => ['notes.txt', 5, 'text/plain'],
        ];
    }

    /**
     * @dataProvider allowedFiles
     */
    public function test_ordinary_documents_are_accepted(string $name, int $kilobytes, string $mime): void
    {
        $media = $this->service()->uploadFile(
            UploadedFile::fake()->create($name, $kilobytes, $mime),
            'lesson_materials'
        );

        $this->assertNotNull($media->id);
        Storage::disk('local')->assertExists($media->path);
    }

    public function test_a_zip_based_office_file_does_not_trip_the_scanner(): void
    {
        // .docx/.xlsx are ZIP containers, so the scan sees compressed binary.
        // A realistic payload (ZIP magic + deflate bytes) must not be mistaken
        // for a script.
        $body = "PK\x03\x04".random_bytes(2048);

        $file = UploadedFile::fake()->createWithContent('paper.docx', $body);

        $media = $this->service()->uploadFile($file, 'lesson_materials');

        $this->assertSame('paper.docx', $media->original_name);
    }

    public function test_a_document_carrying_php_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'payload.pdf',
            "harmless header\n<?php system(\$_GET['c']); ?>\n"
        );

        $this->expectException(ValidationException::class);

        $this->service()->uploadFile($file, 'lesson_materials');
    }

    public function test_a_document_carrying_php_short_open_tag_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent('payload.txt', "<?= system('id') ?>");

        $this->expectException(ValidationException::class);

        $this->service()->uploadFile($file, 'lesson_materials');
    }

    public function test_the_pattern_list_actually_exists(): void
    {
        // Guards the exact regression: an undeclared property is a fatal at the
        // first upload, and nothing else in the suite would notice.
        $property = new \ReflectionProperty(FileUploadService::class, 'suspiciousPatterns');

        $this->assertTrue($property->isProtected());
        $this->assertNotEmpty($property->getValue($this->service()));
    }
}