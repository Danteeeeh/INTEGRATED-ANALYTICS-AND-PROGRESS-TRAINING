<?php

namespace App\Http\Controllers;

use App\Http\Requests\FileUploadRequest;
use App\Models\MediaFile;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    public function upload(FileUploadRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $folder = $request->input('folder', 'uploads');

            $mediaFile = $this->fileUploadService->uploadFile($file, $folder);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'id' => $mediaFile->id,
                    'file_name' => $mediaFile->original_name,
                    'file_path' => $mediaFile->path,
                    'file_size' => $mediaFile->size,
                    'file_type' => $mediaFile->mime_type,
                    'url' => $this->fileUploadService->getFileUrl($mediaFile),
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function uploadMultiple(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'files' => 'required|array|max:10',
                'files.*' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,mp4,mp3,zip,txt',
            ]);

            $files = $request->file('files');
            $folder = $request->input('folder', 'uploads');

            $uploadedFiles = $this->fileUploadService->uploadMultipleFiles($files, $folder);

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'data' => collect($uploadedFiles)->map(function ($mediaFile) {
                    return [
                        'id' => $mediaFile->id,
                        'file_name' => $mediaFile->original_name,
                        'file_path' => $mediaFile->path,
                        'file_size' => $mediaFile->size,
                        'file_type' => $mediaFile->mime_type,
                        'url' => $this->fileUploadService->getFileUrl($mediaFile),
                    ];
                }),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function download(MediaFile $mediaFile)
    {
        // Authorization check - require authentication
        if (! auth()->check()) {
            abort(403, 'Unauthorized access to this file.');
        }

        // A row with no bytes behind it is a storage problem, not a missing
        // page. Saying so beats an unexplained 404 the instructor cannot act on.
        abort_unless($mediaFile->fileExists(), 404, 'This file is no longer available on the server. The record exists but its contents are missing from storage.');

        return $this->fileUploadService->downloadFile($mediaFile);
    }

    public function serve(MediaFile $mediaFile)
    {
        // Authorization check - require authentication
        if (! auth()->check()) {
            abort(403, 'Unauthorized access to this file.');
        }

        // download() already guards this; serve() did not, so previewing a file
        // whose bytes were missing raised a 500 instead of saying what happened.
        abort_unless(
            $mediaFile->fileExists(),
            404,
            'This file is no longer available on the server. The record exists but its contents are missing from storage.'
        );

        // Inline rather than as an attachment, so a PDF or image can be
        // previewed in place. BinaryFileResponse still honours Range requests,
        // which is what makes seeking work in the video and audio players.
        return response()->file(
            Storage::disk($mediaFile->disk)->path($mediaFile->path),
            ['Content-Disposition' => 'inline']
        );
    }

    public function delete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_id' => 'required|exists:media_files,id',
            ]);

            $mediaFile = MediaFile::find($request->file_id);

            // Authorization check
            if (auth()->id() !== $mediaFile->uploader_id) {
                abort(403, 'You are not authorized to delete this file.');
            }

            $this->fileUploadService->deleteFile($mediaFile);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File deletion failed: '.$e->getMessage(),
            ], 500);
        }
    }

    public function info(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_id' => 'required|exists:media_files,id',
            ]);

            $mediaFile = MediaFile::find($request->file_id);

            // Authorization check - require authentication
            if (! auth()->check()) {
                abort(403, 'Unauthorized access to this file.');
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $mediaFile->id,
                    'file_name' => $mediaFile->original_name,
                    'file_size' => $mediaFile->size,
                    'file_type' => $mediaFile->mime_type,
                    'extension' => $mediaFile->extension,
                    'uploaded_at' => $mediaFile->created_at->format('Y-m-d H:i:s'),
                    'url' => $this->fileUploadService->getFileUrl($mediaFile),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve file info: '.$e->getMessage(),
            ], 500);
        }
    }
}
