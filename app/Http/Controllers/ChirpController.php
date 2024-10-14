<?php

namespace App\Http\Controllers;

use App\Models\Chirp;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use ZipArchive;

class ChirpController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $chirps = Chirp::with('user')
            ->where('user_id', auth()->id())
            ->latest()
            ->get();

        return view('chirps.index', [
            'chirps' => $chirps,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
{
    $validated = $request->validate([
        'message' => 'nullable|string|max:255',
        'files.*' => 'nullable|file|max:100240', // Accept multiple files with validation
    ]);

    $filePaths = [];

    if ($request->hasFile('files')) {
        foreach ($request->file('files') as $file) {
            // Use a unique identifier to prevent overwriting
            $timestamp = now()->format('Ymd_His');
            $originalExtension = $file->getClientOriginalExtension();
            $uniqueId = uniqid(); // Generate a unique ID
            $newFileName = 'sys_' . $timestamp . '_' . $uniqueId . '.' . $originalExtension;

            $path = $file->storeAs('uploads/chirps', $newFileName, 'public');
            $filePaths[] = $path;
        }
    }

    // Store the paths as a JSON string
    $chirpData = array_merge($validated, ['files' => json_encode($filePaths)]);
    $chirp = $request->user()->chirps()->create($chirpData);

    // Generate the download URL if multiple files were uploaded
    if (count($filePaths) > 1) {
        $chirp->download_url = route('chirps.download', ['id' => $chirp->id]);
        $chirp->save();
    }

    return redirect(route('chirps.index'));
}


public function downloadChirpFiles($id)
{
    $chirp = Chirp::findOrFail($id);
    $filePaths = json_decode($chirp->files, true);

    $zip = new ZipArchive();
    $zipFileName = 'chirp_files_' . $id . '.zip';

    if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
        foreach ($filePaths as $filePath) {
            $fullPath = storage_path('app/public/' . $filePath);
            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, basename($filePath));
            }
        }
        $zip->close();
    }

    return response()->stream(function () use ($zipFileName) {
        readfile($zipFileName);
        unlink($zipFileName); // Clean up the temporary file
    }, 200, [
        'Content-Type' => 'application/zip',
        'Content-Disposition' => 'attachment; filename="' . $zipFileName . '"',
    ]);
}   
}
