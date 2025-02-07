<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Enums\ImageDriver;
use Spatie\Image\Image;
use Spatie\ImageOptimizer\OptimizerChain;
use Spatie\ImageOptimizer\Optimizers\Jpegoptim;

class ImageCompressorController extends Controller
{
    public function index()
    {
        return Inertia::render('ImageCompressor');
    }

    public function compressSingle(Request $request)
    {
        $request->validate([
            'image' => 'required|image', // Max 10MB
            'quality' => 'required|integer|min:1|max:100',
        ]);

        $image = $request->file('image');
        $quality = $request->input('quality');

        // Create a unique name for this image
        $originalName = pathinfo($image->getClientOriginalName(), PATHINFO_FILENAME);
        $uniqueName = $originalName . '-min';
        $tempPath = storage_path('app/public/temp/' . $uniqueName . '.jpg');

        // Ensure the directory exists
        if (!file_exists(dirname($tempPath))) {
            mkdir(dirname($tempPath), 0755, true);
        }



        // Create intervention image instance and compress
        $img = Image::useImageDriver(ImageDriver::Gd)
            ->loadFile($image)
            ->quality($quality)
            ->optimize()
            ->save($tempPath);

        // Schedule cleanup for this single file
        $this->scheduleCleanup(null, $tempPath);

        return response()->json([
            'compressedSize' => filesize($tempPath),
            'compressedPath' => $tempPath
        ]);
    }

    public function createZip(Request $request)
    {
        $request->validate([
            'processedImages' => 'required|string',
        ]);

        $processedImages = json_decode($request->input('processedImages'), true);

        if (empty($processedImages)) {
            return response()->json(['error' => 'No images to compress'], 400);
        }

        // Create a unique ZIP file
        $batchId = uniqid();
        $zipPath = storage_path('app/public/temp/' . $batchId . '.zip');

        // Create ZIP file
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) === TRUE) {
            foreach ($processedImages as $file) {
                if (file_exists($file)) {
                    $zip->addFile($file, basename($file));
                }
            }
            $zip->close();
        }

        // Schedule cleanup for the ZIP file
        $this->scheduleCleanup(null, $zipPath);

        return response()->json([
            'zipUrl' => asset('storage/temp/' . $batchId . '.zip')
        ]);
    }


    private function scheduleCleanup($tempDir = null, $filePath = null)
    {
        // Schedule cleanup after 1 hour
        dispatch(function () use ($tempDir, $filePath) {
            if ($tempDir && is_dir($tempDir)) {
                array_map('unlink', glob("$tempDir/*.*"));
                rmdir($tempDir);
            }

            if ($filePath && file_exists($filePath)) {
                unlink($filePath);
            }
        })->delay(now()->addHour());
    }
}
