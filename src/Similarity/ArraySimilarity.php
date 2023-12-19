<?php

namespace Mr4Lc\Recommendation\Similarity;

use Exception;

class ArraySimilarity
{
    use \Mr4Lc\Recommendation\Traits\ConsoleOutput;
    use \Mr4Lc\Recommendation\Traits\FilePath;

    protected $products       = [];
    protected $featureWeight  = 1;
    protected $categoryWeight = 1;
    protected $priceWeight    = 1;
    protected $priceHighRange = 1000;
    protected $algorithm      = '';

    public function __construct(array $products, $featureWeight = null, $categoryWeight = null, $priceWeight = null, $priceHighRange = null, $algorithm = 'hammingFeaturesEuclideanPriceJaccardCategories')
    {
        if (isset($featureWeight)) {
            $this->featureWeight = $featureWeight;
        } else {
            $this->featureWeight = config('mr4recommendation.featureWeight', 1);
        }
        if (isset($categoryWeight)) {
            $this->categoryWeight = $categoryWeight;
        } else {
            $this->categoryWeight = config('mr4recommendation.categoryWeight', 1);
        }
        if (isset($priceWeight)) {
            $this->priceWeight = $priceWeight;
        } else {
            $this->priceWeight = config('mr4recommendation.priceWeight', 1);
        }
        if (isset($priceHighRange)) {
            $this->priceHighRange = $priceHighRange;
        } else {
            $this->priceHighRange = config('mr4recommendation.priceHighRange', 1000);
        }
        $this->products       = $products;
        $this->priceHighRange = max(array_column($products, 'price'));
        $this->algorithm = $algorithm;
    }

    public function setFeatureWeight(float $weight): void
    {
        $this->featureWeight = $weight;
    }

    public function setPriceWeight(float $weight): void
    {
        $this->priceWeight = $weight;
    }

    public function setCategoryWeight(float $weight): void
    {
        $this->categoryWeight = $weight;
    }

    public function calculateSimilarityMatrix(): array
    {
        $matrix = [];

        foreach ($this->products as $product) {
            $similarityScores = [];

            foreach ($this->products as $_product) {
                if ($product->id === $_product->id) {
                    continue;
                }
                $similarityScores['product_id_' . $_product->id] = $this->calculateSimilarityScore($product, $_product);
            }
            $matrix['product_id_' . $product->id] = $similarityScores;
        }
        return $matrix;
    }

    public function getProductsSortedBySimularity(int $productId, array $matrix): array
    {
        $similarities   = $matrix['product_id_' . $productId] ?? null;
        $sortedProducts = [];

        if (is_null($similarities)) {
            throw new Exception('Can\'t find product with that ID.');
        }
        arsort($similarities);

        foreach ($similarities as $productIdKey => $similarity) {
            $id       = intval(str_replace('product_id_', '', $productIdKey));
            $products = array_filter($this->products, function ($product) use ($id) {
                return $product->id === $id;
            });
            if (!count($products)) {
                continue;
            }
            $product = $products[array_keys($products)[0]];
            $product->similarity = $similarity;
            $sortedProducts[] = $product;
        }
        return $sortedProducts;
    }

    protected function calculateSimilarityScore($productA, $productB)
    {
        switch ($this->algorithm) {
            default:
                $productAFeatures = implode('', get_object_vars($productA->features));
                $productBFeatures = implode('', get_object_vars($productB->features));

                return array_sum([
                    (Similarity::hamming($productAFeatures, $productBFeatures) * $this->featureWeight),
                    (Similarity::euclidean(
                        Similarity::minMaxNorm([$productA->price], 0, $this->priceHighRange),
                        Similarity::minMaxNorm([$productB->price], 0, $this->priceHighRange)
                    ) * $this->priceWeight),
                    (Similarity::jaccard($productA->categories, $productB->categories) * $this->categoryWeight)
                ]) / ($this->featureWeight + $this->priceWeight + $this->categoryWeight);
        }
    }
}
