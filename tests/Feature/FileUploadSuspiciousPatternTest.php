<?php

namespace Tests\Feature;

use App\Services\FileUploadService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class ShortReadStream
{
    public $context;

    public static string $payload = '';

    private string $content = '';

    private int $position = 0;

    public function stream_open(string $path, string $mode, int $options, ?string &$openedPath): bool
    {
        $this->content = self::$payload;

        return true;
    }

    public function stream_read(int $count): string
    {
        $chunk = substr($this->content, $this->position, min($count, 10));
        $this->position += strlen($chunk);

        return $chunk;
    }

    public function stream_eof(): bool
    {
        return $this->position >= strlen($this->content);
    }

    public function stream_stat(): array
    {
        return [];
    }
}

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

        if (! in_array('lms-short-read', stream_get_wrappers(), true)) {
            stream_wrapper_register('lms-short-read', ShortReadStream::class);
        }
    }

    protected function tearDown(): void
    {
        if (in_array('lms-short-read', stream_get_wrappers(), true)) {
            stream_wrapper_unregister('lms-short-read');
        }

        parent::tearDown();
    }

    private function service(): FileUploadService
    {
        return new FileUploadService;
    }

    /** @return array<string, array{0: int, 1: string}> */
    public static function suspiciousPayloads(): array
    {
        return [
            'php open tag' => [0, "<?php echo 'safe'; ?>"],
            'short echo tag' => [1, "<?= 'safe' ?>"],
            'short echo tag without whitespace' => [1, '<?=1?>'],
            'short echo tag with a variable' => [1, '<?=$value?>'],
            'short echo tag inside markup' => [1, '<p><?= $name ?></p>'],
            'external script' => [2, '<script src="//evil.example/x.js"></script>'],
            'encoded eval' => [3, "eval(base64_decode('ZWNobyAxOw=='));"],
            'powershell encoded command' => [4, 'powershell -EncodedCommand ZQBjAGgAbwAgADEACgA='],
            'system command' => [5, "system('id');"],
            'proc_open' => [6, 'proc_open("id", [], $pipes);'],
            'passthru' => [7, 'passthru("id");'],
            'assert superglobal' => [8, 'assert($_GET["cmd"]);'],
        ];
    }

    /** @return array<string, array{0: string}> */
    public static function dangerousFiles(): array
    {
        return [
            'exe' => ['payload.exe'],
            'bat' => ['payload.bat'],
            'cmd' => ['payload.cmd'],
            'com' => ['payload.com'],
            'scr' => ['payload.scr'],
            'pif' => ['payload.pif'],
            'sh' => ['payload.sh'],
            'bash' => ['payload.bash'],
            'zsh' => ['payload.zsh'],
            'php' => ['payload.php'],
            'php3' => ['payload.php3'],
            'php4' => ['payload.php4'],
            'php5' => ['payload.php5'],
            'php7' => ['payload.php7'],
            'php8' => ['payload.php8'],
            'phtml' => ['payload.phtml'],
            'pht' => ['payload.pht'],
            'phtm' => ['payload.phtm'],
            'phps' => ['payload.phps'],
            'phar' => ['payload.phar'],
            'inc' => ['payload.inc'],
            'cgi' => ['payload.cgi'],
            'pl' => ['payload.pl'],
            'shtml' => ['payload.shtml'],
            'asp' => ['payload.asp'],
            'aspx' => ['payload.aspx'],
            'jsp' => ['payload.jsp'],
            'jspx' => ['payload.jspx'],
            'htaccess' => ['.htaccess'],
            'js' => ['payload.js'],
            'jse' => ['payload.jse'],
            'jar' => ['payload.jar'],
            'war' => ['payload.war'],
            'ps1' => ['payload.ps1'],
            'psm1' => ['payload.psm1'],
            'vbs' => ['payload.vbs'],
            'vbe' => ['payload.vbe'],
            'double extension php.pdf' => ['payload.php.pdf'],
            'double extension phtml.jpg' => ['payload.phtml.jpg'],
            'double extension phar.png' => ['payload.phar.png'],
            'double extension inc.txt' => ['payload.inc.txt'],
        ];
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

    /**
     * Each payload must match only the named scanner rule. Keeping the payloads
     * isolated prevents one rule from masking a broken or removed pattern.
     *
     * @dataProvider suspiciousPayloads
     */
    public function test_each_suspicious_pattern_rejects_its_payload(int $patternIndex, string $payload): void
    {
        $patterns = (new \ReflectionProperty(FileUploadService::class, 'suspiciousPatterns'))
            ->getValue($this->service());

        $matchingPatterns = array_keys(array_filter(
            $patterns,
            fn (string $pattern) => preg_match($pattern, $payload) === 1
        ));

        $this->assertSame(
            [$patternIndex],
            $matchingPatterns,
            'The payload must match exactly its own rule, not another pattern.'
        );

        $file = UploadedFile::fake()->createWithContent('payload.txt', $payload);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The file contains suspicious content and cannot be uploaded.');

        $this->service()->uploadFile($file, 'lesson_materials');
    }

    /**
     * The ordinary upload validator rejects unknown extensions before the
     * executable check, so this exercises the shared scanner directly.
     *
     * @dataProvider dangerousFiles
     */
    public function test_dangerous_extensions_and_double_extensions_are_rejected(string $filename): void
    {
        $file = UploadedFile::fake()->createWithContent($filename, 'harmless content');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Executable files are not allowed for security reasons.');

        $this->service()->assertSafeContent($file);
    }

    public function test_a_normal_double_extension_without_an_executable_segment_is_allowed(): void
    {
        $file = UploadedFile::fake()->createWithContent('report.final.pdf', 'harmless content');

        $media = $this->service()->uploadFile($file, 'lesson_materials');

        $this->assertSame('report.final.pdf', $media->original_name);
    }

    public function test_a_pattern_ending_exactly_at_the_64kb_scan_limit_is_rejected(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'boundary.txt',
            str_repeat('A', 65536 - 5).'<?php'
        );

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The file contains suspicious content and cannot be uploaded.');

        $this->service()->uploadFile($file, 'lesson_materials');
    }

    public function test_a_pattern_immediately_after_the_64kb_scan_limit_is_not_read(): void
    {
        $file = UploadedFile::fake()->createWithContent(
            'boundary.txt',
            str_repeat('A', 65536).'<?php'
        );

        $media = $this->service()->uploadFile($file, 'lesson_materials');

        $this->assertSame('boundary.txt', $media->original_name);
    }

    public function test_short_reads_still_fill_the_64kb_scan_window(): void
    {
        ShortReadStream::$payload = str_repeat('A', 65536 - 5).'<?php';

        $path = tempnam(sys_get_temp_dir(), 'lms-short-read-');
        $this->assertNotFalse($path);
        file_put_contents($path, '');

        $file = new class($path, 'boundary.txt', 'text/plain', null, true) extends UploadedFile
        {
            public function getPathname(): string
            {
                return 'lms-short-read://payload';
            }
        };

        try {
            $this->expectException(ValidationException::class);
            $this->expectExceptionMessage('The file contains suspicious content and cannot be uploaded.');

            $this->service()->assertSafeContent($file);
        } finally {
            unlink($path);
        }
    }

    public function test_a_regex_failure_rejects_the_upload(): void
    {
        $service = new class extends FileUploadService
        {
            protected array $suspiciousPatterns = ['/unclosed['];
        };

        $file = UploadedFile::fake()->createWithContent('payload.txt', 'harmless content');

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The file could not be scanned for unsafe content.');

        $service->uploadFile($file, 'lesson_materials');
    }

    public function test_a_file_that_cannot_be_opened_for_scanning_is_rejected(): void
    {
        $path = tempnam(sys_get_temp_dir(), 'lms-scan-');
        $this->assertNotFalse($path);

        $file = new UploadedFile($path, 'payload.pdf', 'application/pdf', null, true);

        // The upload looks valid, but the bytes are gone before the scanner
        // can read them. That must reject the upload, not skip the scan.
        $this->assertTrue(unlink($path));

        $service = new class extends FileUploadService
        {
            public function scan(UploadedFile $file): void
            {
                $this->checkForExecutableFile($file);
            }
        };

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('The file could not be scanned for unsafe content.');

        $service->scan($file);
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