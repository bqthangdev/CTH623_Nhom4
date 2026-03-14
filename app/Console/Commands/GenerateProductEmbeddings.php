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
            $vectors = [];

            // Compute a separate embedding for every product image.
            foreach ($product->images as $image) {
                $path = Storage::disk('public')->path($image->image_path);

                if (! file_exists($path)) {
                    continue;
                }

                $response = Http::timeout(30)
                    ->attach('image', file_get_contents($path), basename($path))
                    ->post("{$aiUrl}/api/embeddings/compute");

                if ($response->successful()) {
                    $vectors[] = $response->json('embedding');
                }
            }

            if (empty($vectors)) {
                $failed++;
                $bar->advance();
                continue;
            }

            // Average all per-image vectors and L2-normalise. Storing the mean
            // vector ensures the product embedding represents all its photos,
            // so a search using any of the product's images yields a high score.
            $averaged = $this->averageEmbeddings($vectors);

            $response = Http::timeout(30)
                ->post("{$aiUrl}/api/embeddings/store", [
                    'product_id' => $product->id,
                    'embedding'  => $averaged,
                ]);

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

    /**
     * Average an array of embedding vectors and return the L2-normalised result.
     *
     * @param  array<int, array<int, float>>  $vectors
     * @return array<int, float>
     */
    private function averageEmbeddings(array $vectors): array
    {
        $dim = count($vectors[0]);
        $avg = array_fill(0, $dim, 0.0);

        foreach ($vectors as $vec) {
            foreach ($vec as $i => $val) {
                $avg[$i] += (float) $val;
            }
        }

        $count = count($vectors);
        $avg   = array_map(fn ($v) => $v / $count, $avg);

        // L2 normalize
        $norm = sqrt(array_sum(array_map(fn ($v) => $v ** 2, $avg)));

        return $norm > 0
            ? array_map(fn ($v) => $v / $norm, $avg)
            : $avg;
    }
}
