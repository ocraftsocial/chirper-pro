<?php

namespace App\Http\Controllers;

use App\Models\Chirp;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;
use ZipArchive;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

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
                $timestamp = now()->format('d-m-Y_H-i-s');
                $originalExtension = $file->getClientOriginalExtension();
                $uniqueId = uniqid(); // Generate a unique ID
                $newFileName = 'sys_' . $timestamp . '___' . $uniqueId . '.' . $originalExtension;

                $path = $file->storeAs('uploads/chirps', $newFileName, 'local');
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
                $fullPath = storage_path('app/local/' . $filePath);
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



    public function downloadChirpFile($id, $file)
    {
        $chirp = Chirp::findOrFail($id);
        $filePaths = json_decode($chirp->files, true);

        // Check if the requested file exists in the chirp's files
        $requestedFilePath = null;
        foreach ($filePaths as $filePath) {
            if (basename($filePath) === $file) {
                $requestedFilePath = $filePath;
                break;
            }
        }

        if (!$requestedFilePath) {
            abort(404); // File not found in the chirp's files
        }

        $fullPath = storage_path('app/local/' . $requestedFilePath);

        // Check if the file exists
        if (!file_exists($fullPath)) {
            abort(404);
        }

        return response()->download($fullPath);
    }

    public function getImage($id, $file)
    {
        $chirp = Chirp::findOrFail($id);

        // Check if the authenticated user is the author of the chirp
        if (Gate::allows('view-chirp', $chirp)) {
            $filePaths = json_decode($chirp->files, true);

            // Check if the requested file exists in the chirp's files
            $requestedFilePath = null;
            foreach ($filePaths as $filePath) {
                if (basename($filePath) === $file) {
                    $requestedFilePath = $filePath;
                    break;
                }
            }

            if ($requestedFilePath) {
                $path = storage_path('app/local/' . $requestedFilePath);

                if (file_exists($path)) {
                    return response()->file($path);
                }
            }

            abort(404); // File not found
        }

        abort(403); // Forbidden if the user is not the au

    }

    public function shareFile(Request $request, $id)
{
    $chirp = Chirp::findOrFail($id);
    $chirp->view_count = 0;

    // Validate the request for a view limit and time limit
    $request->validate([
        'view_limit' => 'nullable|integer|min:1',
        'time_limit' => 'nullable|integer|min:0', // New validation for time limit
    ]);

    // Set the view limit and time limit
    $chirp->view_limit = $request->input('view_limit', 1); // Default to 1 if not provided
    $chirp->time_limit = $request->input('time_limit', 0); // Default to 0 (unlimited)

    // Decode the file paths from JSON
    $filePaths = json_decode($chirp->files, true);

    if (empty($filePaths)) {
        return response()->json(['message' => 'No files associated with this chirp.'], 404);
    }

    // Copy each file to the public directory
    foreach ($filePaths as $filePath) {
        $source = 'uploads/chirps/' . basename($filePath);
        $dest = 'public/' . basename($filePath);

        if (Storage::disk('local')->exists($source)) {
            $fileContents = Storage::disk('local')->get($source);
            Storage::disk('public')->put(basename($filePath), $fileContents);
        } else {
            return response()->json(['message' => 'File not found: ' . $filePath], 404);
        }
    }

    // Generate a unique share token
    $shareToken = Str::random(40); // Generates a random string
    $chirp->share_token = $shareToken;
    $chirp->save();

    return response()->json([
        'message' => 'Files shared successfully.',
        'share_link' => route('chirps.shared', ['token' => $shareToken]),
    ], 200);
}

    public function showSharedChirp($token)
    {
        // Find the chirp by share token
        $chirp = Chirp::where('share_token', $token)->with('user')->first();
    
        // If the chirp is not found, return a 404 error
        if (!$chirp) {
            return abort(404); // Not Found
        }
    
        // Check if the chirp has already been viewed the maximum number of times
        if ($chirp->view_count >= $chirp->view_limit) {
            // Delete the copied files from the public storage
            $filePaths = json_decode($chirp->files, true);
            foreach ($filePaths as $filePath) {
                // Delete the file from public storage
                Storage::disk('public')->delete(basename($filePath));
            }
    
            // Optionally, you can delete the chirp or set it to inactive
            // $chirp->delete(); // Uncomment to delete the chirp entirely
    
            return abort(403); // Forbidden: Already viewed
        }
    
        // Increment the view count
        $chirp->increment('view_count');
    
        // Decode the file paths
        $filePaths = json_decode($chirp->files, true);
    
        // Pass data to the view
        return view('chirps.shared', compact('chirp', 'filePaths'));
    }
        
    public function downloadSharedChirpFile($token, $file)
    {
        // Find the chirp by share token
        $chirp = Chirp::where('share_token', $token)->first();
    
        // If the chirp is not found, return a 404 error
        if (!$chirp) {
            abort(404); // Not Found
        }
    
        // Decode the file paths
        $filePaths = json_decode($chirp->files, true);
    
        // Check if the requested file exists in the chirp's files
        $requestedFilePath = null;
        foreach ($filePaths as $filePath) {
            if (basename($filePath) === $file) {
                $requestedFilePath = $filePath;
                break;
            }
        }
    
        if (!$requestedFilePath) {
            abort(404); // File not found in the chirp's files
        }
    
        // Full path to the public file
        $fullPath = storage_path('app/public/' . basename($requestedFilePath));
    
        // Check if the file exists
        if (!file_exists($fullPath)) {
            abort(404); // File not found
        }
    
        return response()->download($fullPath);
    }
    
    public function downloadSharedChirpFiles($token)
    {
        // Find the chirp by share token
        $chirp = Chirp::where('share_token', $token)->first();
    
        // If the chirp is not found, return a 404 error
        if (!$chirp) {
            abort(404); // Not Found
        }
    
        // Decode the file paths
        $filePaths = json_decode($chirp->files, true);
    
        $zip = new ZipArchive();
        $zipFileName = 'shared_chirp_files_' . $chirp->id . '.zip';
    
        if ($zip->open($zipFileName, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            foreach ($filePaths as $filePath) {
                $fullPath = storage_path('app/public/' . basename($filePath));
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
