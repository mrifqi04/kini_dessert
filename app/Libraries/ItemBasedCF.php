<?php

namespace App\Libraries;

use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Arr;

class ItemBasedCF
{
    public function __construct(User $user, Product $product, $countNeighbors = 2)
    {
        $this->user = $user;
        $this->product = $product;
        $this->countNeighbors = $countNeighbors;
    }

    public function predict()
    {
        $user = $this->user;
        $product = $this->product;
        $countNeighbors = $this->countNeighbors;

        // Step 1: menghitung similaritas buku yang ingin diprediksi dengan buku-buku lain
        $similarities = $this->getSimilarities($product);

        // Step 2: mencari tahu neighbors
        $userRatings = $user->ratings()->pluck('rating', 'product_id')->toArray();
        $neighbors = $this->getNeighbors($similarities, $userRatings, $countNeighbors);

        // Step 3: menghitung prediksi dengan weighted sum terhadap neighbors dan userRatings
        $prediction = $this->calculateWeightedSum($neighbors, $userRatings);

        return $prediction;
    }

    public function getSimilarities(Product $product)
    {
        $otherProducts = Product::whereNotIn('product_id', [$product->getKey()])->get();
        $sims = [];
        $productRatings = $product->ratings()->pluck('rating', 'user_id')->toArray();

        foreach ($otherProducts as $otherProduct) {
            $otherProductRatings = $otherProduct->ratings()->pluck('rating', 'user_id')->toArray();

            $pearsonCorrelation = new PearsonCorrelation($productRatings, $otherProductRatings);
            $sims[$otherProduct->getKey()] = $pearsonCorrelation->calculate();
        }
        return $sims;
    }

    public function getNeighbors($similarities, $userRatings, $countNeighbors)
    {
        // Hapus similarities <= 0
        $similarities = array_filter($similarities, function($sim) {
            return $sim > 0;
        });

        // Ambil similaritas dari buku yang pernah dirating user saja
        $hasRatingProductIds = array_keys($userRatings);
        $similarities = Arr::only($similarities, $hasRatingProductIds);

        // Urutkan similaritas dari yang tertinggi
        uasort($similarities, function($a, $b) {
            return $b > $a;
        });

        // Kembalikan sebanyak jumlah yang ditentukan
        return array_slice($similarities, 0, $countNeighbors, true);
    }

    public function calculateWeightedSum($neighbors, $userRatings)
    {
        $top = 0;
        $bottom = 0;

        foreach ($neighbors as $productId => $sim) {
            $rating = $userRatings[$productId];
            $top += ($rating * $sim);
            $bottom += abs($sim);
        }

        if ($bottom == 0) {
            return 0;
        }

        return $top / $bottom;
    }

    /**
     * @return Explainer
     */
    public function explain()
    {
        $explainer = new Explainer;

        $user = $this->user;
        $product = $this->product;
        $countNeighbors = $this->countNeighbors;
        $userRatings = $user->ratings()->pluck('rating', 'product_id')->toArray();
        $productId = $product->getKey();

        $inputs = [
            ["user", $user->name],
            ["product", $product->title],
            ["countNeighbours", $countNeighbors],
        ];
        $explainer->addTable("INPUT", ["Parameter", "Value"], $inputs);

        // Step 1: menghitung similaritas buku yang ingin diprediksi dengan buku-buku lain
        $similarities = $this->getSimilarities($product);
        $sims = [];
        foreach ($similarities as $otherProductId => $sim) {
            $product_name = Product::where('product_id', $productId)->first('title');        
            $other_product_name = Product::where('product_id', $otherProductId)->first('title');
            $sims[] = [$product_name->title, $other_product_name->title, $sim, Arr::get($userRatings, $otherProductId)];
        }
        $explainer->addTable("PRODUCT SIMILARITIES", ["product_id", "other_product_id", "similarity", "user_rating"], $sims);

        // Step 2: mencari tahu neighbors
        $neighbors = $this->getNeighbors($similarities, $userRatings, $countNeighbors);
        $nhs = [];
        foreach ($neighbors as $otherProductId => $sim) {
            $product_name = Product::where('product_id', $productId)->first('title');        
            $other_product_name = Product::where('product_id', $otherProductId)->first('title');                    
            $nhs[] = [$product_name->title, $other_product_name->title, $sim, Arr::get($userRatings, $otherProductId)];
        }
        $explainer->addTable("NEIGHBORS", ["product_id", "other_product_id", "similarity", "user_rating"], $nhs);

        // Step 3: menghitung prediksi dengan weighted sum terhadap neighbors dan userRatings
        $prediction = $this->calculateWeightedSum($neighbors, $userRatings);
        $this->explainWeightedSum($explainer, $neighbors, $userRatings);

        return $explainer;
    }

    public function explainWeightedSum(Explainer $explainer, $neighbors, $userRatings)
    {
        $result = $this->calculateWeightedSum($neighbors, $userRatings);
        $top = [];
        $bottom = [];

        foreach ($neighbors as $productId => $sim) {
            $rating = $userRatings[$productId];
            $top[] = "($rating * $sim)";
            $bottom[] = "|{$sim}|";
        }

        $explainer->add("WEIGHTED SUM", "(".implode(" + ", $top).') / ('.implode(" + ", $bottom).") = {$result}");
    }
}
