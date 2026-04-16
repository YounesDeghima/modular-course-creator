<?php

namespace App\Console\Commands;

use App\Models\Block;
use Illuminate\Console\Command;
use Storage;

class CleanupBlockFiles extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:cleanup-block-files';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        // 1. Get all files physically in your storage folder
        // Adjust 'uploads' to the folder name you actually use
        $allFiles = Storage::disk('public')->files('blocks');


        // 2. Get all file paths referenced in your Block model
        // We only care about blocks that actually have a file path in the 'content' or 'file' column
        $referencedFiles = Block::whereNotNull('content')->whereIn('type',['photo','video'])
            ->pluck('content')
            ->toArray();

        // 3. Find files on disk that are NOT in the database
        $orphanedFiles = array_diff($allFiles, $referencedFiles);

        // 4. Delete them
        if (!empty($orphanedFiles)) {
            Storage::disk('public')->delete($orphanedFiles);
            $this->info(count($orphanedFiles) . " orphaned files removed.");
        }
    }
}
