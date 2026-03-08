<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class GenerateProductEmbeddings extends Command
{
    protected $signature = 'embeddings:generate';

    protected $description = 'Generate and store AI embeddings for all product images';

    public function handle(): int
    {
        $aiUrl    = config('services.ai.url');
        $products = Product::with('images')->whereHas('images')->get();

        if ($products->isEmpty()) {
            $this->warn('No products with images found.');
            return self::SUCCESS;
        }

        $bar = $this->output->createProgressBar($products->count());
        $bar->start();

        $failed = 0;

        foreach ($products as $product) {
            $image = $product->images->sortByDesc('is_primary')->first();
            $path  = Storage::disk('public')->path($image->image_path);

            if (! file_exists($path)) {
                $failed++;
                $bar->advance();
                continue;
            }

            $response = Http::timeout(30)
                ->attach('image', file_get_contents($path), basename($path))
                ->post("{$aiUrl}/api/embeddings/generate?product_id={$product->id}");

            if ($response->failed()) {
                $failed++;
            }

            $bar->advance();
        }

        $bar->finish();
        $this->newLine();

        $succeeded = $products->count() - $failed;
        $this->info("Done. {$succeeded} embeddings generated, {$failed} failed.");

        return self::SUCCESS;
    }
}
