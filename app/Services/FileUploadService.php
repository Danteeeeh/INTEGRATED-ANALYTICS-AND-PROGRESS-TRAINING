<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\MediaFile;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FileUploadService
{
    protected array $allowedMimeTypes = [
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'image/jpeg',
        'image/png',
        'image/gif',
        'video/mp4',
        'audio/mpeg',
        'audio/mp3',
        'application/zip',
        'text/plain',
    ];

    protected array $allowedExtensions = [
        'pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx',
        'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'zip', 'txt',
    ];

    protected int $maxFileSize = 10485760; // 10MB in bytes

    public function uploadFile(UploadedFile $file, string $folder = 'uploads', array $options = []): MediaFile
    {
        $this->validateFile($file);

        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $mimeType = $file->getMimeType();
        $size = $file->getSize();

        // Generate safe filename
        $safeName = $this->generateSafeName($originalName, $extension);
        $path = $folder.'/'.$safeName;

        // Store file
        $disk = $options['disk'] ?? 'local';
        $isPublic = $options['public'] ?? false;

        if ($isPublic) {
            $filePath = $file->storeAs($folder, $safeName, 'public');
            $fullPath = Storage::disk('public')->path($filePath);
        } else {
            $filePath = $file->storeAs($folder, $safeName, $disk);
            $fullPath = Storage::disk($disk)->path($filePath);
        }

        // Create media file record
        $mediaFile = MediaFile::create([
            'file_name' => $originalName,
            'file_path' => $filePath,
            'file_size' => $size,
            'file_type' => $mimeType,
            'extension' => $extension,
            'disk' => $disk,
            'is_public' => $isPublic,
            'uploaded_by' => auth()->id(),
            'uploadable_type' => $options['uploadable_type'] ?? null,
            'uploadable_id' => $options['uploadable_id'] ?? null,
        ]);

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'upload',
            'resource' => 'media_file',
            'resource_id' => $mediaFile->id,
            'details' => [
                'file_name' => $originalName,
                'file_size' => $size,
                'file_type' => $mimeType,
            ],
        ]);

        return $mediaFile;
    }

    public function uploadMultipleFiles(array $files, string $folder = 'uploads', array $options = []): array
    {
        $uploadedFiles = [];

        foreach ($files as $file) {
            if ($file instanceof UploadedFile) {
                $uploadedFiles[] = $this->uploadFile($file, $folder, $options);
            }
        }

        return $uploadedFiles;
    }

    public function validateFile(UploadedFile $file): void
    {
        // Check if file is valid
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'The file upload failed.',
            ]);
        }

        // Check file size
        if ($file->getSize() > $this->maxFileSize) {
            $maxSizeMB = round($this->maxFileSize / 1048576, 2);
            throw ValidationException::withMessages([
                'file' => "The file must not exceed {$maxSizeMB}MB.",
            ]);
        }

        // Check MIME type
        $mimeType = $file->getMimeType();
        if (! in_array($mimeType, $this->allowedMimeTypes)) {
            throw ValidationException::withMessages([
                'file' => 'The file type is not allowed.',
            ]);
        }

        // Check extension
        $extension = strtolower($file->getClientOriginalExtension());
        if (! in_array($extension, $this->allowedExtensions)) {
            throw ValidationException::withMessages([
                'file' => 'The file extension is not allowed.',
            ]);
        }

        // Check for executable files
        $this->checkForExecutableFile($file);
    }

    protected function checkForExecutableFile(UploadedFile $file): void
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $dangerousExtensions = ['exe', 'bat', 'sh', 'php', 'js', 'jar', 'com', 'scr', 'pif'];

        if (in_array($extension, $dangerousExtensions)) {
            throw ValidationException::withMessages([
                'file' => 'Executable files are not allowed for security reasons.',
            ]);
        }

        // Check file content for suspicious patterns
        $content = file_get_contents($file->getPathname());
        $suspiciousPatterns = [
            '/<\?php/i',
            '/<script/i',
            '/javascript:/i',
            '/vbscript:/i',
            '/onload=/i',
            '/onerror=/i',
        ];

        foreach ($suspiciousPatterns as $pattern) {
            if (preg_match($pattern, $content)) {
                throw ValidationException::withMessages([
                    'file' => 'The file contains suspicious content and cannot be uploaded.',
                ]);
            }
        }
    }

    protected function generateSafeName(string $originalName, string $extension): string
    {
        // Remove extension
        $nameWithoutExt = pathinfo($originalName, PATHINFO_FILENAME);

        // Sanitize filename
        $safeName = Str::slug($nameWithoutExt, '_');

        // Add timestamp to prevent conflicts
        $timestamp = now()->format('YmdHis');

        // Reconstruct filename
        return "{$safeName}_{$timestamp}.{$extension}";
    }

    public function deleteFile(MediaFile $mediaFile): bool
    {
        $disk = $mediaFile->disk;

        // Delete from storage
        if (Storage::disk($disk)->exists($mediaFile->file_path)) {
            Storage::disk($disk)->delete($mediaFile->file_path);
        }

        // Delete record
        $mediaFile->delete();

        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => 'delete',
            'resource' => 'media_file',
            'resource_id' => $mediaFile->id,
            'details' => [
                'file_name' => $mediaFile->file_name,
            ],
        ]);

        return true;
    }

    public function getFileUrl(MediaFile $mediaFile): string
    {
        if ($mediaFile->is_public) {
            return Storage::disk('public')->url($mediaFile->file_path);
        }

        // For private files, we need a secure download route
        return route('files.download', $mediaFile->id);
    }

    public function serveFile(MediaFile $mediaFile)
    {
        $disk = $mediaFile->disk;

        if (! Storage::disk($disk)->exists($mediaFile->file_path)) {
            abort(404, 'File not found');
        }

        $file = Storage::disk($disk)->get($mediaFile->file_path);
        $mimeType = $mediaFile->file_type;

        return response($file, 200)
            ->header('Content-Type', $mimeType)
            ->header('Content-Disposition', 'inline; filename="'.$mediaFile->file_name.'"')
            ->header('Content-Length', strlen($file));
    }

    public function downloadFile(MediaFile $mediaFile)
    {
        $disk = $mediaFile->disk;

        if (! Storage::disk($disk)->exists($mediaFile->file_path)) {
            abort(404, 'File not found');
        }

        return Storage::disk($disk)->download(
            $mediaFile->file_path,
            $mediaFile->file_name
        );
    }

    public function setMaxFileSize(int $sizeInBytes): void
    {
        $this->maxFileSize = $sizeInBytes;
    }

    public function setAllowedMimeTypes(array $mimeTypes): void
    {
        $this->allowedMimeTypes = $mimeTypes;
    }

    public function setAllowedExtensions(array $extensions): void
    {
        $this->allowedExtensions = $extensions;
    }
}
