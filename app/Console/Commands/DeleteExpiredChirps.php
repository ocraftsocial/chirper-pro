<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Models\Chirp;
use Illuminate\Support\Facades\Storage;



class DeleteExpiredChirps extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-expired-chirps';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */public function handle()
{
    // Current time
    $now = now();

    // Find chirps where the time limit has been exceeded
    $chirps = Chirp::whereNotNull('time_limit')
        ->where('updated_at', '<', $now->subMinutes(function ($chirp) {
            return $chirp->time_limit;
        }))
        ->get();

    foreach ($chirps as $chirp) {
        // Delete files from public storage
        $filePaths = json_decode($chirp->files, true);
        foreach ($filePaths as $filePath) {
            Storage::disk('public')->delete(basename($filePath));
        }

        // Delete the chirp itself
        $chirp->delete();
    }
}

}
