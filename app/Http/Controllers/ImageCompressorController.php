<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\ImageManager;

class ImageCompressorController extends Controller
{
    public function index()
    {
        return Inertia::render('ImageCompressor');
    }

    public function compress(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*' => 'required|image|max:10240', // Max 10MB per image
            'quality' => 'required|integer|min:1|max:100',
        ]);

        $images = $request->file('images');
        $quality = $request->input('quality');
        $manager = ImageManager::imagick();

        // Create a unique directory for this batch
        $batchId = uniqid();
        $tempDir = storage_path('app/public/temp/' . $batchId);
        $zipPath = storage_path('app/public/temp/' . $batchId . '.zip');

        // Ensure the directory exists
        if (!file_exists($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $compressedSizes = [];
        $compressedFiles = [];

        // Process each image
        foreach ($images as $image) {
            $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
            $tempPath = $tempDir . '/' . $originalName . '_compressed.jpg';

            // Create intervention image instance and compress
            $img = $manager->read($image);
            $img->save($tempPath, $quality);

            $compressedSizes[] = filesize($tempPath);
            $compressedFiles[] = $tempPath;
        }

        // Create ZIP file
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($compressedFiles as $file) {
                $zip->addFile($file, basename($file));
            }
            $zip->close();
        }

        // Schedule cleanup
        $this->scheduleCleanup($tempDir, $zipPath);

        return Inertia::render('ImageCompressor', [
            'flash' => [
                'compressedSizes' => $compressedSizes,
                'zipUrl' => asset('storage/temp/' . $batchId . '.zip')
            ]
        ]);
    }

    private function scheduleCleanup($tempDir, $zipPath)
    {
        // Schedule cleanup after 1 hour
        dispatch(function () use ($tempDir, $zipPath) {
            // Delete temporary directory and its contents
            if (is_dir($tempDir)) {
                array_map('unlink', glob("$tempDir/*.*"));
                rmdir($tempDir);
            }

            // Delete ZIP file
            if (file_exists($zipPath)) {
                unlink($zipPath);
            }
        })->delay(now()->addHour());
    }
    
}
