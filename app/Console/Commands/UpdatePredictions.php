<?php

namespace App\Console\Commands;

use App\Libraries\ItemBasedCF;
use App\Models\Prediction;
use App\Models\Product;
use App\Models\Rating;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Auth;

class UpdatePredictions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update-predictions {user_id?} {product_id?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update prediction data';

    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $userId = $this->argument("user_id");        
        $productId = $this->argument("product_id");
        
        if ($userId) {
            $user = User::findOrFail($userId);
            if ($productId) {
                $product = Product::findOrFail($productId);
                return $this->updatePrediction($user, $product);
            }

            return $this->updateUserPredictions($user);
        }

        return $this->updateAllPredictions();
    }

    public function updateAllPredictions()
    {        
        Prediction::truncate();
        $users = User::where('type', User::TYPE_MEMBER)->get();
        $products = Product::all();
        foreach ($users as $user) {
            foreach ($products as $product) {
                $this->updatePrediction($user, $product);
            }
        }
    }

    public function updateUserPredictions(User $user)
    {
        $products = Product::all();
        foreach ($products as $product) {
            $this->updatePrediction($user, $product);
        }
    }

    public function updatePrediction(User $user, Product $product)
    {
        // if ($this->userHasRating($user, $product)) {            
        //     return $this->updatePrediction($user, $product);
        // }

        $cf = new ItemBasedCF($user, $product);
        $prediction = $cf->predict();
        Prediction::savePrediction($user, $product, $prediction);

        $userId = $user->getKey();
        $productId = $product->getKey();
        $this->info("Set prediction user:$userId product:$productId = $prediction");
    }

    public function userHasRating(User $user, Product $product)
    {
        return Rating::query()
            ->where('user_id', $user->getKey())
            ->where('product_id', $product->getKey())
            ->count() > 0;
    }

    public function removePrediction(User $user, Product $product)
    {
        $userId = $user->getKey();
        $productId = $product->getKey();
        Prediction::removePrediction($user, $product);
        $this->warn("Remove prediction user:{$userId} product:$productId");
    }
}
