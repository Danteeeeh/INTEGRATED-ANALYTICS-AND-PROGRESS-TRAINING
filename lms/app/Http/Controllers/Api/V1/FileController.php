<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\FileUploadRequest;
use App\Models\MediaFile;
use App\Services\FileUploadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class FileController extends Controller
{
    protected FileUploadService $fileUploadService;

    public function __construct(FileUploadService $fileUploadService)
    {
        $this->fileUploadService = $fileUploadService;
    }

    /**
     * Upload a file
     */
    public function upload(FileUploadRequest $request): JsonResponse
    {
        try {
            $file = $request->file('file');
            $folder = $request->input('folder', 'uploads');
            $isPublic = $request->boolean('public', false);

            $mediaFile = $this->fileUploadService->uploadFile($file, $folder, [
                'public' => $isPublic,
                'uploadable_type' => $request->input('uploadable_type'),
                'uploadable_id' => $request->input('uploadable_id'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'File uploaded successfully',
                'data' => [
                    'id' => $mediaFile->id,
                    'file_name' => $mediaFile->file_name,
                    'file_path' => $mediaFile->file_path,
                    'file_size' => $mediaFile->file_size,
                    'file_type' => $mediaFile->file_type,
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

    /**
     * Upload multiple files
     */
    public function uploadMultiple(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'files' => 'required|array|max:10',
                'files.*' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,mp4,mp3,zip,txt',
            ]);

            $files = $request->file('files');
            $folder = $request->input('folder', 'uploads');
            $isPublic = $request->boolean('public', false);

            $uploadedFiles = $this->fileUploadService->uploadMultipleFiles($files, $folder, [
                'public' => $isPublic,
                'uploadable_type' => $request->input('uploadable_type'),
                'uploadable_id' => $request->input('uploadable_id'),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Files uploaded successfully',
                'data' => $uploadedFiles->map(function ($mediaFile) {
                    return [
                        'id' => $mediaFile->id,
                        'file_name' => $mediaFile->file_name,
                        'file_path' => $mediaFile->file_path,
                        'file_size' => $mediaFile->file_size,
                        'file_type' => $mediaFile->file_type,
                        'url' => $this->fileUploadService->getFileUrl($mediaFile),
                    ];
                }),
            ], 201);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File upload failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete a file
     */
    public function delete(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_id' => 'required|exists:media_files,id',
            ]);

            $mediaFile = MediaFile::find($request->file_id);

            // Authorization check
            if (! $mediaFile->is_public && auth()->id() !== $mediaFile->uploaded_by) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not authorized to delete this file.',
                ], 403);
            }

            $this->fileUploadService->deleteFile($mediaFile);

            return response()->json([
                'success' => true,
                'message' => 'File deleted successfully',
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'File deletion failed: '.$e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get file info
     */
    public function info(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'file_id' => 'required|exists:media_files,id',
            ]);

            $mediaFile = MediaFile::find($request->file_id);

            // Authorization check
            if (! $mediaFile->is_public && ! auth()->check()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized access to this file.',
                ], 403);
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'id' => $mediaFile->id,
                    'file_name' => $mediaFile->file_name,
                    'file_size' => $mediaFile->file_size,
                    'file_type' => $mediaFile->file_type,
                    'extension' => $mediaFile->extension,
                    'uploaded_at' => $mediaFile->created_at->format('Y-m-d H:i:s'),
                    'url' => $this->fileUploadService->getFileUrl($mediaFile),
                ],
            ]);

        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to retrieve file info: '.$e->getMessage(),
            ], 500);
        }
    }
}
